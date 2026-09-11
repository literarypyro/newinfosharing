<?php
/* ============================================================================
 * iss_insight_prompt.php  --  ask the report a question
 * ----------------------------------------------------------------------------
 * WHAT THIS IS NOT: it is not a second analysis engine, and the model in it
 * never sees a data row, never does arithmetic, and never decides what a
 * number means. That invariant is the whole reason iss_insight.php is
 * trustworthy in a printed report, and nothing here weakens it.
 *
 * What a prompt actually IS, on a report page, is two things:
 *
 *     1. a filter state  (period, level, car, equipment, grain)
 *     2. a choice of which findings matter  (the focus)
 *
 * Both of those already exist. So the prompt is translated into a REQUEST
 * over a fixed vocabulary, the request is validated against that vocabulary
 * server-side, and then the page re-runs its own queries with its own
 * filters set to the requested values. The existing pipeline -- normalize,
 * findings, advanced findings, offline render, audience toggle, band,
 * printout -- runs unchanged on the narrowed slice.
 *
 *     text  ->  RULES (instant, offline)
 *                 |
 *                 +-- leftovers? and a provider configured? --> MODEL (JSON only)
 *                 |
 *                 v
 *             VALIDATE against the whitelist  ->  REQUEST
 *                 |
 *                 +--> page filters ($level/$carFilter/$start_date/roster)
 *                 +--> finding order (focus)
 *                 +--> chips, so the reader can see and correct the parse
 *
 * Anything the validator does not recognise is DROPPED and reported, never
 * guessed at. A question that resolves to nothing says so; it does not
 * silently render the default report as though it had answered.
 *
 * PHP 5 compatible on purpose (array(), no ??, no short array syntax).
 * Guard the include the way data_coverage.php is guarded -- a station that
 * has not received this file yet must keep the page exactly as it was.
 * ==========================================================================*/

if (defined('ISS_PROMPT_LOADED')) { return; }
define('ISS_PROMPT_LOADED', 1);

/* Bump when the vocabulary or the system prompt changes: it is part of the
 * cache key, so a bump invalidates every cached parse. */
define('ISS_PROMPT_V', '4');

/* The parse blocks the page, unlike the narration, which is async -- the
 * queries cannot run until the filters are known. So it gets a much shorter
 * leash than iss_insight's 25s: a parse is a few dozen output tokens, and if
 * it has not come back in 8 the rules result is already good enough. */
define('ISS_PROMPT_TIMEOUT', 8);


/* ---------------------------------------------------------------------------
 * 1. VOCABULARY
 *
 * The page owns this. Nothing outside it can ever be requested, which is what
 * makes free text safe to put next to a SQL-backed report: the parser's output
 * is not escaped and then trusted, it is MATCHED against values the page
 * supplied and discarded if it does not match one.
 * -------------------------------------------------------------------------*/
function iss_prompt_vocab($spec) {
    $v = array(
        'report'    => isset($spec['report'])    ? $spec['report']    : 'report',
        'unit'      => isset($spec['unit'])      ? $spec['unit']      : 'records',
        'equipment' => isset($spec['equipment']) ? $spec['equipment'] : array(),
        /* 0 is a real tier -- a normal incident with no severity assigned --
         * not the absence of a level. '' is the absence of a level. */
        'levels'    => isset($spec['levels'])    ? $spec['levels']    : array('0','1','2','3','4'),
        'has_car'   => isset($spec['has_car'])   ? (bool)$spec['has_car'] : false,
        'date_min'  => isset($spec['date_min'])  ? $spec['date_min']  : '2013-01-01',
        'date_max'  => isset($spec['date_max'])  ? $spec['date_max']  : date('Y-m-d'),
        'grains'    => array('month', 'day'),
        /* level_condition rows, code => description, straight from the table.
         * Lets a question name an outcome in the words the schema uses. */
        'conditions'=> isset($spec['conditions']) ? $spec['conditions'] : array(),
        /* Concepts THIS page has no column for, declared by the page. The car
         * report has no level filter, for instance, so a level question there
         * must be told so rather than silently ignored. label => regex. */
        'lacks'     => isset($spec['lacks']) ? $spec['lacks'] : array(),
    );
    /* Focus kinds. The LABEL is what the chip shows; the MATCH list is tried
     * against each finding's own kind string, because those live in
     * iss_insight_analytics.php and this file deliberately does not depend on
     * their exact spelling -- an unmatched focus reorders nothing rather than
     * throwing away findings. */
    $v['focus'] = array(
        'concentration' => array('label'=>'Worst units',
            'match'=>array('concentrat','residual','crosstab','cross_tab','outlier','hotspot')),
        'trend'         => array('label'=>'Trend / change',
            'match'=>array('trend','change','cusum','changepoint','change_point','shift','emerging')),
        'seasonal'      => array('label'=>'Seasonality',
            'match'=>array('season','month_index','monthly_index','baseline')),
        'comovement'    => array('label'=>'Move together',
            'match'=>array('comove','co_move','correlat','pearson')),
        'pareto'        => array('label'=>'Share of total',
            'match'=>array('pareto','share','concentration_curve')),
        'recurrence'    => array('label'=>'Repeat failures',
            'match'=>array('recur','repeat','poisson','burst')),
        'service'       => array('label'=>'Service lost',
            'match'=>array('service','loops','cancel','unavail','interrupt','unload')),
        'coverage'      => array('label'=>'Data gaps',
            'match'=>array('coverage','gap','missing')),
    );
    return $v;
}


/* ---------------------------------------------------------------------------
 * 2. RULES PASS  --  instant, offline, and the only pass a station without a
 *    provider ever runs. Everything it understands, it understands for free.
 * -------------------------------------------------------------------------*/
function iss_prompt_rules($text, $vocab) {
    $req = iss_prompt_blank();
    $t   = ' ' . strtolower(preg_replace('/\s+/', ' ', $text)) . ' ';
    $usedTxt = array();

    /* -- period ---------------------------------------------------------- */
    $p = iss_prompt_dates($t, $vocab);
    if ($p !== null) {
        $req['from'] = $p['from']; $req['to'] = $p['to'];
        $req['period_said'] = $p['said'];
        $usedTxt[] = $p['said'];
    }

    /* -- level ----------------------------------------------------------- */
    if (preg_match('/\ball levels?\b/', $t, $m)) {
        $req['level'] = ''; $req['level_set'] = true; $usedTxt[] = $m[0];
    } elseif (preg_match('/\b(?:normal incidents?|no (?:severity|severity level|level assigned))\b/', $t, $m)) {
        $req['level'] = '0'; $req['level_set'] = true; $usedTxt[] = $m[0];
    } elseif (preg_match('/\b(?:level|lvl|severity|l)[ \-]?([0-4])\b/', $t, $m)) {
        if (in_array($m[1], $vocab['levels'])) {
            $req['level'] = $m[1]; $req['level_set'] = true; $usedTxt[] = $m[0];
        }
    }

    /* -- car ------------------------------------------------------------- */
    /* No 1..73 clamp on purpose: car numbers outside the revenue range are
     * deliberate placeholders for non-revenue trains, not bad data, and a
     * range check here would refuse a legitimate question about one. */
    if ($vocab['has_car']) {
        if (preg_match('/\ball cars?\b/', $t, $m)) {
            $req['car'] = 0; $req['car_set'] = true; $usedTxt[] = $m[0];
        } elseif (preg_match('/\bcars?\s*(?:no\.?|number|#)?\s*(\d{1,4})\b/', $t, $m)) {
            $req['car'] = (int)$m[1]; $req['car_set'] = true; $usedTxt[] = $m[0];
        }
    }

    /* -- equipment ------------------------------------------------------- */
    $req['equipment'] = iss_prompt_match_equipment($t, $vocab['equipment']);
    foreach ($req['equipment'] as $eid) {
        $usedTxt[] = strtolower($vocab['equipment'][$eid]);
        foreach (iss_prompt_tokens($vocab['equipment'][$eid]) as $tk) { $usedTxt[] = $tk; }
    }

    /* -- measure ----------------------------------------------------------
       "How many cars broke down" and "how many failures were there" are
       different questions over the same rows. One counts distinct cars, the
       other counts car-failure pairs, and a car that failed nine times makes
       them differ by eight. Answering the wrong one is worse than answering
       nothing, because both are plausible numbers. */
    if (preg_match('/\b(?:how many|number of|count of|total)\s+(?:distinct |different |unique |separate )?(cars?|trains?|units?)\b/', $t, $m)) {
        $req['measure'] = 'cars'; $usedTxt[] = $m[0];
    } elseif (preg_match('/\b(?:how many|number of|count of|total)\s+(?:distinct |different |unique |separate )?(incidents?|events?|reports?)\b/', $t, $m)) {
        $req['measure'] = 'incidents'; $usedTxt[] = $m[0];
    } elseif (preg_match('/\b(?:how many|number of|count of|total)\s+(?:car[ -]level )?(failures?|faults?|breakdowns?|defects?)\b/', $t, $m)) {
        $req['measure'] = 'failures'; $usedTxt[] = $m[0];
    }

    /* -- outcome (level_condition) ---------------------------------------
       Matched against the lookup's own wording, so it follows the table. A
       question naming an outcome is asking about THAT outcome, not the
       general topic of service, and the two must not collapse together. */
    foreach ($vocab['conditions'] as $code => $desc) {
        $d = strtolower(trim($desc));
        $d = trim(preg_replace('/[^a-z0-9 ]+/', ' ', $d));
        $d = preg_replace('/\s+/', ' ', $d);
        if ($d === '') continue;
        $core = $d;
        foreach (array('cancellation of loops', 'service interruption',
                       'passenger unloading', 'train is removed', 'ticket refunds') as $frag) {
            if (strpos($d, $frag) !== false) { $core = $frag; break; }
        }
        if (strpos($t, $core) !== false) {
            $req['condition'] = (string)$code;
            $usedTxt[] = $core;
            break;
        }
    }

    /* -- grain ----------------------------------------------------------- */
    if (preg_match('/\b(?:daily|by day|per day|day by day|each day)\b/', $t, $m))      { $req['grain'] = 'day';   $usedTxt[] = $m[0]; }
    elseif (preg_match('/\b(?:monthly|by month|per month|month by month)\b/', $t, $m)) { $req['grain'] = 'month'; $usedTxt[] = $m[0]; }

    /* -- audience -------------------------------------------------------- */
    if (preg_match('/\b(?:executive|management|for the board|layman|plain|simple|non[- ]technical)\b/', $t, $m))
        { $req['audience'] = 'exec'; $usedTxt[] = $m[0]; }
    elseif (preg_match('/\b(?:technical|in detail|full detail|with figures|with numbers)\b/', $t, $m))
        { $req['audience'] = 'tech'; $usedTxt[] = $m[0]; }

    /* -- focus ----------------------------------------------------------- */
    $fmap = array(
        'concentration' => '/\b(?:worst|top|biggest|most|highest|concentrat|which (?:car|equipment|unit|type)s?|driv(?:e|es|ing|en)|drove|responsible)\b/',
        'trend'         => '/\b(?:trend|trending|increas|decreas|rising|falling|climb|drop|getting worse|improv|over time|change|changed|shift)\b/',
        'seasonal'      => '/\b(?:season|seasonal|rainy|dry season|same month|year[ -]on[ -]year|yoy|typical for|normal for|usual for|usually)\b/',
        'comovement'    => '/\b(?:together|correlat|related|linked|same time|alongside|co[- ]?occur)\b/',
        'pareto'        => '/\b(?:pareto|80\/20|share of|accounts? for|proportion|percentage of total)\b/',
        'recurrence'    => '/\b(?:repeat|repeated|again|recurr|keeps? failing|back[- ]to[- ]back|same car twice|not holding)\b/',
        'service'       => '/\b(?:loops?|cancell?ed|cancell?ations?|service lost|lost service|refunds?|unloading|passenger unloading|train unavailab|interruptions?|turned back)\b/',
        'coverage'      => '/\b(?:missing|gap|gaps|no data|not recorded|incomplete)\b/',
    );
    foreach ($fmap as $k => $re) {
        if (preg_match_all($re, $t, $mm)) {
            $req['focus'][] = $k;
            foreach ($mm[0] as $hit) { $usedTxt[] = $hit; }
        }
    }

    /* What the rules did not account for. Only used to decide whether the
     * model is worth waking up -- never shown as an error on its own. */
    $left = $t;
    foreach ($usedTxt as $u) {
        $u = trim(strtolower($u));
        if ($u !== '') { $left = str_replace($u, ' ', $left); }
    }
    $left = preg_replace('/\b(?:show|me|the|a|an|of|for|in|on|by|and|or|with|what|which|how|many|much|is|are|was|were|please|report|failures?|incidents?|data|analysis|analyse|analyze|give|list|all|from|to|between|during|over|any|there|it|this|that|do|does|did|can|you|i|we'
                          . '|anything|something|everything|up|down|problems?|issues?|faults?|breakdowns?|same|about|explain|tell|summary|compare|against|versus|vs|months?|years?|days?|weeks?|number|count|total|look|see|find|want|need|know|thanks|ok|okay|kindly|units?|types?|equipments?|equipt|cars?|na|po|ang|ng|sa)\b/', ' ', $left);
    $left = trim(preg_replace('/[^a-z0-9 ]+/', ' ', $left));
    $left = trim(preg_replace('/\s+/', ' ', $left));
    /* Single letters and 2-char stubs are stripping artefacts ("doors" minus
     * "door" leaves "s"), not words anyone typed. */
    $keep = array();
    foreach (explode(' ', $left) as $w) { if (strlen($w) >= 3) $keep[] = $w; }
    $left = implode(' ', $keep);
    $req['leftover'] = $left;

    return $req;
}

function iss_prompt_blank() {
    return array(
        'from'=>'', 'to'=>'', 'period_said'=>'',
        'level'=>'', 'level_set'=>false,
        'car'=>0,   'car_set'=>false,
        'equipment'=>array(),
        'grain'=>'', 'audience'=>'', 'condition'=>'', 'measure'=>'',
        'focus'=>array(), 'leftover'=>'',
    );
}


/* ---------------------------------------------------------------------------
 * 2a. DATE PHRASES
 *
 * Resolved to a real From/To pair here rather than handed to the model,
 * because a date is the one thing in a prompt with a single correct answer
 * and no reason to spend a network round trip on it.
 * -------------------------------------------------------------------------*/
function iss_prompt_dates($t, $vocab) {
    $months = array('january'=>1,'february'=>2,'march'=>3,'april'=>4,'may'=>5,'june'=>6,
                    'july'=>7,'august'=>8,'september'=>9,'october'=>10,'november'=>11,'december'=>12,
                    'jan'=>1,'feb'=>2,'mar'=>3,'apr'=>4,'jun'=>6,'jul'=>7,'aug'=>8,
                    'sep'=>9,'sept'=>9,'oct'=>10,'nov'=>11,'dec'=>12);
    $mre = implode('|', array_keys($months));
    $yNow = (int)date('Y');
    $mNow = (int)date('n');

    /* explicit range: "March 2025 to June 2026", "2024-2026", "1 Jan 2025 - 30 Jun 2026" */
    if (preg_match('/\b(' . $mre . ')\s+(\d{4})\s*(?:to|until|through|thru|-|–|until)\s*(' . $mre . ')\s+(\d{4})\b/', $t, $m)) {
        return iss_prompt_span(iss_prompt_mstart($m[2], $months[$m[1]]),
                               iss_prompt_mend($m[4], $months[$m[3]]), $m[0], $vocab);
    }
    if (preg_match('/\b(\d{4})\s*(?:to|until|through|thru|-|–)\s*(\d{4})\b/', $t, $m)) {
        return iss_prompt_span($m[1] . '-01-01', $m[2] . '-12-31', $m[0], $vocab);
    }

    /* quarters */
    if (preg_match('/\bq([1-4])\s*(?:of\s*)?(\d{4})\b/', $t, $m)
     || preg_match('/\b(?:(first|second|third|fourth)) quarter (?:of )?(\d{4})\b/', $t, $m)) {
        $qmap = array('first'=>1,'second'=>2,'third'=>3,'fourth'=>4);
        $q = isset($qmap[$m[1]]) ? $qmap[$m[1]] : (int)$m[1];
        $s = ($q - 1) * 3 + 1;
        return iss_prompt_span(iss_prompt_mstart($m[2], $s),
                               iss_prompt_mend($m[2], $s + 2), $m[0], $vocab);
    }

    /* "last N months / weeks / years" */
    if (preg_match('/\b(?:last|past|previous|recent)\s+(\d{1,2})\s*(month|week|year)s?\b/', $t, $m)) {
        $n = (int)$m[1];
        $to = date('Y-m-d');
        if ($m[2] === 'month')      $from = date('Y-m-01', strtotime('-' . ($n - 1) . ' months'));
        elseif ($m[2] === 'week')   $from = date('Y-m-d',  strtotime('-' . $n . ' weeks'));
        else                        $from = date('Y-m-d',  strtotime('-' . $n . ' years'));
        return iss_prompt_span($from, $to, $m[0], $vocab);
    }

    /* single named month, with or without a year */
    if (preg_match('/\b(' . $mre . ')\s+(\d{4})\b/', $t, $m)) {
        return iss_prompt_span(iss_prompt_mstart($m[2], $months[$m[1]]),
                               iss_prompt_mend($m[2], $months[$m[1]]), $m[0], $vocab);
    }
    if (preg_match('/\b(?:in|for|during)\s+(' . $mre . ')\b/', $t, $m)) {
        /* Bare month means the most recent one that has happened, not a month
         * in the future -- "in March" asked in January means last March. */
        $mm = $months[$m[1]];
        $yy = ($mm > $mNow) ? $yNow - 1 : $yNow;
        return iss_prompt_span(iss_prompt_mstart($yy, $mm), iss_prompt_mend($yy, $mm), $m[0], $vocab);
    }

    /* relative words */
    if (preg_match('/\b(?:year to date|ytd)\b/', $t, $m))
        return iss_prompt_span($yNow . '-01-01', date('Y-m-d'), $m[0], $vocab);
    if (preg_match('/\bthis year\b/', $t, $m))
        return iss_prompt_span($yNow . '-01-01', $yNow . '-12-31', $m[0], $vocab);
    if (preg_match('/\blast year\b/', $t, $m))
        return iss_prompt_span(($yNow - 1) . '-01-01', ($yNow - 1) . '-12-31', $m[0], $vocab);
    if (preg_match('/\bthis month\b/', $t, $m))
        return iss_prompt_span(date('Y-m-01'), date('Y-m-t'), $m[0], $vocab);
    if (preg_match('/\blast month\b/', $t, $m))
        return iss_prompt_span(date('Y-m-01', strtotime('first day of last month')),
                               date('Y-m-t', strtotime('first day of last month')), $m[0], $vocab);

    /* "since <year>" / "since <month year>" */
    if (preg_match('/\bsince\s+(' . $mre . ')\s+(\d{4})\b/', $t, $m))
        return iss_prompt_span(iss_prompt_mstart($m[2], $months[$m[1]]), date('Y-m-d'), $m[0], $vocab);
    if (preg_match('/\bsince\s+(\d{4})\b/', $t, $m))
        return iss_prompt_span($m[1] . '-01-01', date('Y-m-d'), $m[0], $vocab);

    /* bare year, last resort so it cannot swallow "car 2025" style noise */
    if (preg_match('/(?<!car )(?<!car no )(?<!car #)(?<!car number )\b(?:in|for|during)?\s*(20[0-4]\d)\b/', $t, $m))
        return iss_prompt_span($m[1] . '-01-01', $m[1] . '-12-31', trim($m[0]), $vocab);

    return null;
}
function iss_prompt_mstart($y, $m) { return sprintf('%04d-%02d-01', (int)$y, (int)$m); }
function iss_prompt_mend($y, $m)   { return date('Y-m-t', strtotime(sprintf('%04d-%02d-01', (int)$y, (int)$m))); }

/* Clamp to the window the console actually holds. A question about 2011 is
 * answered for the earliest data there is, and the chip says so, rather than
 * returning an empty report that looks like a quiet year. */
function iss_prompt_span($from, $to, $said, $vocab) {
    if ($from > $to) { $sw = $from; $from = $to; $to = $sw; }
    if ($from < $vocab['date_min']) $from = $vocab['date_min'];
    if ($to   > $vocab['date_max']) $to   = $vocab['date_max'];
    if ($from > $to) return null;
    return array('from'=>$from, 'to'=>$to, 'said'=>trim($said));
}


/* ---------------------------------------------------------------------------
 * 2b. EQUIPMENT MATCHING
 *
 * Matched against the roster the page passed in, so it follows the equipment
 * table and needs no synonym list maintained in code. Two ways in: the whole
 * name appears in the prompt, or a distinctive token of it does -- where
 * "distinctive" means the token belongs to at most two roster entries, so
 * "door" can match a Door row but a word shared by ten entries cannot drag
 * all ten in.
 * -------------------------------------------------------------------------*/
function iss_prompt_match_equipment($t, $roster) {
    if (!count($roster)) return array();

    $tokFreq = array(); $tokOf = array();
    foreach ($roster as $id => $name) {
        $toks = iss_prompt_tokens($name);
        foreach ($toks as $tk) {
            if (!isset($tokFreq[$tk])) { $tokFreq[$tk] = 0; $tokOf[$tk] = array(); }
            $tokFreq[$tk]++; $tokOf[$tk][] = $id;
        }
    }

    $hit = array();
    foreach ($roster as $id => $name) {
        $n = strtolower(trim($name));
        if ($n !== '' && strpos($t, ' ' . $n . ' ') !== false) { $hit[$id] = true; continue; }
        if ($n !== '' && strlen($n) >= 5 && strpos($t, $n) !== false) { $hit[$id] = true; }
    }
    foreach ($tokFreq as $tk => $f) {
        if ($f > 2 || strlen($tk) < 4) continue;
        if (preg_match('/\b' . preg_quote($tk, '/') . '(?:s|es)?\b/', $t)) {
            foreach ($tokOf[$tk] as $id) { $hit[$id] = true; }
        }
    }
    return array_keys($hit);
}
function iss_prompt_tokens($s) {
    $s = strtolower(preg_replace('/[^a-z0-9 ]+/i', ' ', $s));
    $stop = array('and','the','of','for','with','system','unit','assembly','type','no','sub',
                  'failure','failures','fault','faults','error','errors','problem','problems',
                  'breakdown','breakdowns','defect','defects','test','train','trains',
                  'car','cars','equipment','incident','incidents');
    $out = array();
    foreach (preg_split('/\s+/', $s) as $w) {
        $w = trim($w);
        if ($w === '' || strlen($w) < 3 || in_array($w, $stop)) continue;
        $out[$w] = true;
    }
    return array_keys($out);
}


/* ---------------------------------------------------------------------------
 * 3. MODEL PASS  --  a PARSER, not an analyst.
 *
 * It is shown the vocabulary and the question, and asked for JSON. It never
 * sees a count, a row, or a finding, so there is nothing here for it to get
 * arithmetically wrong; the worst a bad parse can do is answer the wrong
 * question, and the chips put that in front of the reader immediately.
 *
 * Reuses iss_insight.php's provider plumbing rather than opening a second
 * one. If that file is older than this one and has no iss_insight_call(),
 * the rules result stands and nothing breaks.
 * -------------------------------------------------------------------------*/
function iss_prompt_model($text, $vocab, $seed) {
    if (!function_exists('iss_insight_config') || !function_exists('iss_insight_call')) return null;

    $cfg = iss_insight_config();
    if (empty($cfg['enabled']) || !isset($cfg['provider']) || $cfg['provider'] === 'none') return null;

    /* A parse is tiny. Do not inherit the narration's budget. */
    $cfg['timeout']    = ISS_PROMPT_TIMEOUT;
    $cfg['max_tokens'] = 400;

    $eq = array();
    foreach ($vocab['equipment'] as $id => $name) { $eq[] = $id . '=' . $name; }

    $focusKeys = array_keys($vocab['focus']);

    $sys = "You convert a question about a rail-operations report into a JSON request. "
         . "Reply with ONE JSON object and nothing else: no prose, no explanation, no markdown fences.\n\n"
         . "Keys, all optional -- omit any key the question does not settle:\n"
         . "  from      : \"YYYY-MM-DD\"\n"
         . "  to        : \"YYYY-MM-DD\"\n"
         . "  level     : one of \"" . implode('","', $vocab['levels']) . "\", or \"\" for all levels\n"
         . ($vocab['has_car'] ? "  car       : integer car number, or 0 for all cars\n" : "")
         . "  equipment : array of numeric ids from the roster below\n"
         . "  grain     : \"month\" or \"day\"\n"
         . "  audience  : \"exec\" or \"tech\"\n"
         . "  focus     : array from " . implode(', ', $focusKeys) . "\n\n"
         . "Rules:\n"
         . "- Use ONLY ids and values listed here. Never invent an id or a field.\n"
         . "- Today is " . date('Y-m-d') . ". Data runs " . $vocab['date_min'] . " to " . $vocab['date_max'] . ".\n"
         . "- Car numbers outside 1-73 are valid: they stand for non-revenue trains.\n"
         . "- level \"0\" means a normal incident with no severity assigned. It is a real\n"
         . "  tier, not \"all levels\" -- use \"\" when the question does not restrict level.\n"
         . "- If the question settles nothing, reply {}.\n\n"
         . "EQUIPMENT ROSTER (id=name):\n" . implode("\n", $eq);

    $usr = "QUESTION: " . $text . "\n\n"
         . "Already resolved by rules (keep unless the question clearly says otherwise): "
         . json_encode(array('from'=>$seed['from'], 'to'=>$seed['to'],
                             'level'=>$seed['level'], 'car'=>$seed['car'],
                             'equipment'=>$seed['equipment'], 'focus'=>$seed['focus']))
         . "\n\nJSON:";

    $r = iss_insight_call($sys, $usr, $cfg);
    if (!is_array($r) || !isset($r[0]) || $r[0] === null || $r[0] === '') return null;

    $txt = trim($r[0]);
    $txt = preg_replace('/^```(?:json)?|```$/m', '', $txt);
    $a = strpos($txt, '{'); $b = strrpos($txt, '}');
    if ($a === false || $b === false || $b <= $a) return null;

    $j = json_decode(substr($txt, $a, $b - $a + 1), true);
    return is_array($j) ? $j : null;
}


/* ---------------------------------------------------------------------------
 * 4. VALIDATE
 *
 * The gate. Everything downstream of here is page-supplied values, which is
 * why the request can be dropped straight into the page's own filter
 * variables without another escaping step -- there is no free text left in it.
 * Rejections are collected, not swallowed: a question that mentioned three
 * things and got two says which one it dropped.
 * -------------------------------------------------------------------------*/
function iss_prompt_validate($raw, $seed, $vocab, $drop) {
    $req = $seed;
    $rej = array();
    if (!is_array($raw)) $raw = array();

    if (isset($raw['from']) && isset($raw['to'])) {
        $f = iss_prompt_ymd($raw['from']); $t2 = iss_prompt_ymd($raw['to']);
        if ($f && $t2) {
            $sp = iss_prompt_span($f, $t2, '', $vocab);
            if ($sp) { $req['from'] = $sp['from']; $req['to'] = $sp['to']; $req['period_said'] = ''; }
            else     { $rej[] = 'period'; }
        } else { $rej[] = 'period'; }
    }

    if (isset($raw['level'])) {
        $lv = (string)$raw['level'];
        if ($lv === '' || in_array($lv, $vocab['levels'])) { $req['level'] = $lv; $req['level_set'] = true; }
        else { $rej[] = 'level ' . $lv; }
    }

    if ($vocab['has_car'] && isset($raw['car'])) {
        $cn = (int)$raw['car'];
        if ($cn >= 0) { $req['car'] = $cn; $req['car_set'] = true; }
        else { $rej[] = 'car'; }
    }

    if (isset($raw['equipment']) && is_array($raw['equipment'])) {
        $ok = array();
        foreach ($raw['equipment'] as $id) {
            if (isset($vocab['equipment'][$id])) { $ok[] = $id; }
            else if (isset($vocab['equipment'][(string)(int)$id])) { $ok[] = (string)(int)$id; }
            else { $rej[] = 'equipment ' . $id; }
        }
        if (count($ok)) $req['equipment'] = $ok;
    }

    if (isset($raw['measure'])
        && in_array($raw['measure'], array('cars','incidents','failures'), true)) {
        $req['measure'] = $raw['measure'];
    }
    if (isset($raw['condition'])) {
        $cc = (string)$raw['condition'];
        if (isset($vocab['conditions'][$cc])) $req['condition'] = $cc;
        else $rej[] = 'outcome ' . $cc;
    }
    if (isset($raw['grain'])) {
        if (in_array($raw['grain'], $vocab['grains'])) $req['grain'] = $raw['grain'];
        else $rej[] = 'grain ' . $raw['grain'];
    }
    if (isset($raw['audience']) && ($raw['audience'] === 'exec' || $raw['audience'] === 'tech')) {
        $req['audience'] = $raw['audience'];
    }
    if (isset($raw['focus']) && is_array($raw['focus'])) {
        foreach ($raw['focus'] as $fk) {
            if (isset($vocab['focus'][$fk]) && !in_array($fk, $req['focus'])) $req['focus'][] = $fk;
            elseif (!isset($vocab['focus'][$fk])) $rej[] = 'focus ' . $fk;
        }
    }

    /* Chips the reader removed. Applied LAST so nothing the parser produces
     * can put a dismissed filter back -- otherwise removing a chip would
     * appear to work and then silently undo itself on the next submit. */
    foreach ($drop as $d) {
        if ($d === 'period')    { $req['from'] = ''; $req['to'] = ''; $req['period_said'] = ''; }
        if ($d === 'level')     { $req['level'] = ''; $req['level_set'] = true; }
        if ($d === 'car')       { $req['car'] = 0; $req['car_set'] = true; }
        if ($d === 'equipment') { $req['equipment'] = array(); }
        if ($d === 'grain')     { $req['grain'] = ''; }
        if ($d === 'condition') { $req['condition'] = ''; }
        if ($d === 'measure')   { $req['measure'] = ''; }
        if (strpos($d, 'focus:') === 0) {
            $k = substr($d, 6); $keep = array();
            foreach ($req['focus'] as $fk) { if ($fk !== $k) $keep[] = $fk; }
            $req['focus'] = $keep;
        }
    }

    $req['rejected'] = $rej;
    return $req;
}
function iss_prompt_ymd($s) {
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', trim((string)$s), $m)) return '';
    if (!checkdate((int)$m[2], (int)$m[3], (int)$m[1])) return '';
    return $m[1] . '-' . $m[2] . '-' . $m[3];
}


/* ---------------------------------------------------------------------------
 * 5. THE WHOLE PARSE, CACHED
 *
 * Same question + same vocabulary -> same request, so a reader re-submitting
 * or paging back pays nothing. The vocabulary hash is in the key because a
 * new equipment row changes what "brake" can resolve to.
 * -------------------------------------------------------------------------*/
function iss_prompt_ask($text, $vocab, $drop) {
    $out = array('asked'=>trim($text), 'source'=>'none', 'request'=>iss_prompt_blank(),
                 'rejected'=>array(), 'note'=>'');

    if (trim($text) === '') {
        $out['request'] = iss_prompt_validate(array(), iss_prompt_blank(), $vocab, $drop);
        return $out;
    }

    $vhash = md5(ISS_PROMPT_V . '|' . json_encode($vocab['equipment'])
               . '|' . json_encode($vocab['conditions']) . '|' . $vocab['date_max']);
    $key   = md5($vhash . '|' . strtolower(trim($text)) . '|' . implode(',', $drop));
    $cached = iss_prompt_cache_get($key);
    if ($cached !== null) {
        $cached['request'] = array_merge(iss_prompt_blank(), (array)$cached['request']);
        if (!isset($cached['note']))     $cached['note'] = '';
        if (!isset($cached['rejected'])) $cached['rejected'] = array();
        return $cached;
    }

    $seed = iss_prompt_rules($text, $vocab);
    $out['source'] = 'rules';

    /* The model is woken only when the rules left real words on the table.
     * "level 3 in Q2 2026" is fully resolved offline and never leaves the
     * building; "why do the doors keep playing up after the rains" does. */
    $raw = null;
    if ($seed['leftover'] !== '') {
        $raw = iss_prompt_model($text, $vocab, $seed);
        if ($raw !== null) $out['source'] = 'model';
    }

    $req = iss_prompt_validate($raw, $seed, $vocab, $drop);
    $out['request']  = $req;
    $out['rejected'] = isset($req['rejected']) ? $req['rejected'] : array();

    if (!iss_prompt_has_any($req)) {
        $out['note'] = ($out['source'] === 'rules' && !iss_prompt_provider_on())
            ? 'Nothing in that mapped to a filter on this report, and no model is configured to interpret it. The report below is unchanged.'
            : 'Nothing in that mapped to a filter on this report. The report below is unchanged.';
    }

    iss_prompt_cache_put($key, $out);
    return $out;
}
function iss_prompt_has_any($r) {
    return ($r['from'] !== '' || $r['level_set'] || $r['car_set']
            || count($r['equipment']) || $r['grain'] !== '' || count($r['focus'])
            || $r['condition'] !== '' || $r['measure'] !== '');
}
function iss_prompt_provider_on() {
    if (!function_exists('iss_insight_config')) return false;
    $c = iss_insight_config();
    return (!empty($c['enabled']) && isset($c['provider']) && $c['provider'] !== 'none');
}
function iss_prompt_cache_dir() {
    if (function_exists('iss_insight_config')) {
        $c = iss_insight_config();
        if (!empty($c['cache_dir']) && is_dir($c['cache_dir'])) return $c['cache_dir'];
    }
    return sys_get_temp_dir();
}
function iss_prompt_cache_get($k) {
    $f = iss_prompt_cache_dir() . DIRECTORY_SEPARATOR . 'issask_' . $k . '.json';
    if (!file_exists($f) || (time() - filemtime($f)) > 86400) return null;
    $j = json_decode(@file_get_contents($f), true);
    return is_array($j) ? $j : null;
}
function iss_prompt_cache_put($k, $v) {
    $f = iss_prompt_cache_dir() . DIRECTORY_SEPARATOR . 'issask_' . $k . '.json';
    @file_put_contents($f, json_encode($v));
}


/* ---------------------------------------------------------------------------
 * 6. FOCUS  --  reorder findings, never delete them.
 *
 * A reader who asked about repeat failures should meet the recurrence finding
 * first. But this is an official report, and a finding that was true before
 * the question was asked is still true after it: off-focus findings drop
 * below a divider rather than out of the document.
 *
 * Matching is by substring against each finding's own kind, because those
 * strings live in iss_insight_analytics.php. If none match, the order is left
 * exactly as it was -- a focus that does not bind is a no-op, not a filter
 * that quietly empties the panel.
 * -------------------------------------------------------------------------*/
function iss_prompt_focus_findings($F, $req, $vocab) {
    if (!is_array($F) || !count($F) || !count($req['focus'])) return $F;

    $want = array();
    foreach ($req['focus'] as $fk) {
        if (isset($vocab['focus'][$fk])) {
            foreach ($vocab['focus'][$fk]['match'] as $m) { $want[] = $m; }
        }
    }
    $hot = array(); $cold = array();
    foreach ($F as $f) {
        $kind = '';
        if (is_array($f)) {
            if (isset($f['kind'])) $kind = strtolower((string)$f['kind']);
            elseif (isset($f['id'])) $kind = strtolower((string)$f['id']);
        }
        $isHot = false;
        foreach ($want as $m) { if ($kind !== '' && strpos($kind, $m) !== false) { $isHot = true; break; } }
        if ($isHot) $hot[] = $f; else $cold[] = $f;
    }
    if (!count($hot)) return $F;          /* nothing bound -- leave it alone */
    return array_merge($hot, $cold);
}

/* View-source marker listing the kind strings actually present, so the focus
 * match lists above can be checked against a live page without a debugger.
 * Same idea as the ccs-dash-datepicker marker. */
function iss_prompt_kinds_marker($F) {
    if (!is_array($F)) return '';
    $k = array();
    foreach ($F as $f) { if (is_array($f) && isset($f['kind'])) $k[strtolower($f['kind'])] = true; }
    return "\n<!-- iss-prompt finding kinds: " . implode(', ', array_keys($k)) . " -->\n";
}


/* ---------------------------------------------------------------------------
 * 7. THE CONTROL
 *
 * Deliberately NOT a second form. It goes inside the page's existing toolbar
 * form, so one submit carries the question and the selects together, and the
 * chips edit the page's OWN controls. That is what makes the free text
 * optional rather than load-bearing: the reader can ask once, see it become
 * ordinary filter state, and drive it by hand from then on.
 * -------------------------------------------------------------------------*/
function iss_prompt_css() {
    return "<style>
.ask-bar{ background:#F1EEE3; border:1px solid #E5DECC; border-top:none; padding:10px 16px; }
.ask-row{ display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.ask-row label{ font-size:12px; font-weight:700; color:#00529B; white-space:nowrap; }
.ask-in{ flex:1 1 340px; min-width:220px; height:30px; box-sizing:border-box;
         border:1px solid #D8D2C2; border-radius:4px; padding:0 10px; font-family:inherit; }
/* The toolbar theme paints text white for the blue bar, and it does so on
   input[type=text] -- specificity (0,1,1), which outranks a bare class
   (0,1,0) whatever the source order. That put white text on the white field:
   the typed characters and the placeholder were both there and both
   invisible. Scoped to .ask-bar and forced, because history_theme.php and
   datepicker_theme.php are copied station to station and this rule cannot
   depend on which version any one box happens to have. */
.ask-bar input.ask-in{
  color:#1A2238 !important;
  -webkit-text-fill-color:#1A2238 !important;   /* Chrome/Safari ignore color on autofill */
  background:#FFFFFF !important;
  opacity:1 !important;
  font-size:13px !important;
  text-indent:0 !important;
}
.ask-bar input.ask-in::placeholder{ color:#8A92A6 !important; opacity:1 !important;
                                    -webkit-text-fill-color:#8A92A6 !important; }
.ask-bar input.ask-in::-webkit-input-placeholder{ color:#8A92A6 !important;
                                    -webkit-text-fill-color:#8A92A6 !important; }
.ask-bar input.ask-in:-ms-input-placeholder{ color:#8A92A6 !important; }
.ask-bar input.ask-in:focus{ color:#1A2238 !important; -webkit-text-fill-color:#1A2238 !important; }
.ask-in:focus{ outline:none; border-color:#00529B; box-shadow:0 0 0 2px rgba(0,82,155,.15); }
.ask-go{ height:30px; box-sizing:border-box; border:none; border-radius:4px; padding:0 14px;
         font-weight:700; cursor:pointer; }
.ask-bar input.ask-go{ background:#FDB813 !important; color:#3A2D00 !important;
                       -webkit-text-fill-color:#3A2D00 !important; font-size:12px !important; }
.ask-go:hover{ background:#E5A50F; }
.ask-eg{ font-size:11px; color:#7A8194; }
.ask-eg a{ color:#00529B; text-decoration:none; border-bottom:1px dotted #9FB6CE; }
.ask-eg a:hover{ color:#003E76; }
.ask-read{ margin-top:9px; display:flex; align-items:center; gap:6px; flex-wrap:wrap; font-size:12px; }
.ask-read .lead{ color:#5A6275; font-weight:600; }
.ask-chip{ display:inline-flex; align-items:center; gap:6px; background:#fff;
           border:1px solid #9FB6CE; color:#00529B; border-radius:14px;
           padding:3px 6px 3px 10px; font-size:12px; font-weight:600; line-height:1.4; }
.ask-chip.focus{ border-color:#E5DECC; color:#5A6275; background:#FBF9F3; }
.ask-x{ display:inline-block; width:16px; height:16px; line-height:15px; text-align:center;
        border-radius:50%; background:#EEF2F7; color:#5A6275; font-weight:700;
        cursor:pointer; font-size:12px; text-decoration:none; }
.ask-x:hover{ background:#00529B; color:#fff; }
.ask-note{ margin-top:8px; font-size:12px; color:#8A5A00; background:#FFF6E0;
           border:1px solid #F0DCA8; border-radius:4px; padding:6px 10px; }
.ask-src{ font-size:11px; color:#9AA1B2; }
@media print{ .ask-bar .ask-row, .ask-bar .ask-eg, .ask-x{ display:none !important; }
              .ask-bar{ background:none; border:none; padding:4px 0; }
              .ask-chip{ border-color:#bbb; color:#000; } }
</style>";
}

function iss_prompt_js() {
    return "<script>
/* A chip's x adds its field to ask_drop, clears the matching control, and
   resubmits. The drop list is what stops the parser from reinstating a filter
   the reader has just dismissed. */
function askDrop(field){
  var f = document.getElementById('askDropField');
  if(!f) return false;
  f.value = (f.value ? f.value + ',' : '') + field;
  if(field === 'level'){ var s = document.getElementsByName('level'); if(s.length) s[0].value = ''; }
  if(field === 'car'){ var c = document.getElementById('carSelect'); if(c) c.value = ''; }
  if(field === 'period'){
    var a = document.getElementById('search_date2'), b = document.getElementById('search_date');
    if(a) a.value = ''; if(b) b.value = '';
  }
  var form = f.form; if(form) form.submit();
  return false;
}
function askExample(t){
  var i = document.getElementById('askField');
  if(!i) return false;
  i.value = t; i.focus();
  return false;
}
</script>";
}

function iss_prompt_box($ask, $vocab, $dropRaw) {
    $req = $ask['request'];
    $h = iss_prompt_js();
    $h .= '<div class="ask-bar">';

    $h .= '<div class="ask-row">'
        . '<label for="askField">Ask this report</label>'
        . '<input type="text" class="ask-in" id="askField" name="ask" value="'
        . htmlspecialchars($ask['asked'], ENT_QUOTES) . '" '
        . 'placeholder="e.g. which cars drove the L3 failures in Q2 2026?">'
        . '<input type="hidden" id="askDropField" name="ask_drop" value="'
        . htmlspecialchars($dropRaw, ENT_QUOTES) . '">'
        . '<input type="submit" class="ask-go" value="Ask">'
        . '</div>';

    $eg = array('worst equipment last 6 months',
                'how many loops did we lose in 2026?',
                'is anything trending up this year?');
    $links = array();
    foreach ($eg as $e) {
        $links[] = '<a href="#" onclick="return askExample(' . htmlspecialchars(json_encode($e), ENT_QUOTES) . ');">'
                 . htmlspecialchars($e) . '</a>';
    }
    $h .= '<div class="ask-eg" style="margin-top:6px;">Try: ' . implode(' &middot; ', $links) . '</div>';

    /* The read-back. This is the part that earns the feature: a wrong parse is
       visible in one glance and correctable in one click, instead of being
       discovered later in a printed report. */
    $chips = iss_prompt_chips($req, $vocab);
    if ($chips !== '') {
        $h .= '<div class="ask-read"><span class="lead">Showing:</span>' . $chips;
        if ($ask['source'] === 'model') $h .= '<span class="ask-src">interpreted</span>';
        $h .= '</div>';
    }

    if ($ask['note'] !== '')       $h .= '<div class="ask-note">' . htmlspecialchars($ask['note']) . '</div>';
    if (count($ask['rejected']))   $h .= '<div class="ask-note">Not applied: '
                                       . htmlspecialchars(implode(', ', $ask['rejected']))
                                       . ' &mdash; not something this report can filter on.</div>';

    $h .= '</div>';
    return $h;
}

function iss_prompt_chips($req, $vocab) {
    $c = '';
    if ($req['from'] !== '') {
        $c .= iss_prompt_chip(date('j M Y', strtotime($req['from'])) . ' &ndash; '
                            . date('j M Y', strtotime($req['to'])), 'period', false);
    }
    if ($req['level'] !== '')  $c .= iss_prompt_chip(
        ($req['level'] === '0') ? 'Normal (no level)' : 'Level ' . $req['level'], 'level', false);
    if ($req['car'] > 0)       $c .= iss_prompt_chip('Car ' . $req['car'], 'car', false);
    if (count($req['equipment'])) {
        $names = array();
        foreach ($req['equipment'] as $id) {
            if (isset($vocab['equipment'][$id])) $names[] = $vocab['equipment'][$id];
        }
        $lbl = (count($names) <= 2) ? implode(' + ', $names) : (count($names) . ' equipment types');
        $c .= iss_prompt_chip($lbl, 'equipment', false);
    }
    if ($req['condition'] !== '' && isset($vocab['conditions'][$req['condition']]))
        $c .= iss_prompt_chip(htmlspecialchars(rtrim($vocab['conditions'][$req['condition']], '.')),
                              'condition', false);
    if ($req['measure'] !== '') {
        $ml = array('cars'=>'Counting cars', 'incidents'=>'Counting incidents',
                    'failures'=>'Counting failures');
        $c .= iss_prompt_chip($ml[$req['measure']], 'measure', false);
    }
    if ($req['grain'] !== '')  $c .= iss_prompt_chip('By ' . $req['grain'], 'grain', false);
    foreach ($req['focus'] as $fk) {
        if (isset($vocab['focus'][$fk]))
            $c .= iss_prompt_chip($vocab['focus'][$fk]['label'], 'focus:' . $fk, true);
    }
    return $c;
}
function iss_prompt_chip($label, $field, $isFocus) {
    return '<span class="ask-chip' . ($isFocus ? ' focus' : '') . '">' . $label
         . '<a href="#" class="ask-x" title="Remove" onclick="return askDrop('
         . htmlspecialchars(json_encode($field), ENT_QUOTES) . ');">&times;</a></span>';
}

/* One-line version for the printout, where the chips' controls are meaningless
 * but the scope of the question is exactly what a reader of the paper copy
 * needs to know. */
function iss_prompt_print_line($ask, $vocab) {
    if (trim($ask['asked']) === '') return '';
    $req = $ask['request'];
    $bits = array();
    if ($req['from'] !== '') $bits[] = date('j M Y', strtotime($req['from'])) . ' to ' . date('j M Y', strtotime($req['to']));
    if ($req['level'] !== '') $bits[] = 'Level ' . $req['level'];
    if ($req['car'] > 0) $bits[] = 'Car ' . $req['car'];
    if (count($req['equipment'])) {
        $n = array();
        foreach ($req['equipment'] as $id) { if (isset($vocab['equipment'][$id])) $n[] = $vocab['equipment'][$id]; }
        if (count($n)) $bits[] = implode(', ', $n);
    }
    return 'Question: "' . $ask['asked'] . '"'
         . (count($bits) ? '  -- read as: ' . implode(' / ', $bits) : '');
}

/* ---------------------------------------------------------------------------
 * 8. DIRECT ANSWER
 *
 * The findings pipeline reports what it noticed. A question asking "how many"
 * is not asking what anyone noticed -- it wants the figure. Focus alone could
 * never serve it: focus REORDERS findings, and there is no finding about
 * loops because the analyzers were written before the service block existed.
 * Reordering a list that does not contain the answer produces a report that
 * looks responsive and answers nothing.
 *
 * So this reads the figures straight out of the context and states them. No
 * model, no analyzer, no inference -- every number here was computed by SQL
 * on the page, and if the context has nothing to say the strip does not
 * render at all rather than guessing.
 * -------------------------------------------------------------------------*/
function iss_prompt_answer_css() {
    return "<style>
.ask-ans{ border-left:4px solid #00529B; background:#F3F7FC; border-radius:0 6px 6px 0;
          padding:11px 15px; margin:14px 0; }
.ask-ans .q{ font-size:11px; color:#5A6275; text-transform:uppercase;
             letter-spacing:.06em; margin-bottom:4px; }
.ask-ans .a{ font-size:14px; color:#1A2238; line-height:1.55; }
.ask-ans .a b{ color:#00529B; font-size:17px; }
.ask-ans .cav{ font-size:12px; color:#5A6275; margin-top:6px; }
</style>";
}

function iss_prompt_answer($ask, $vocab, $d) {
    if (!is_array($ask) || trim($ask['asked']) === '') return '';
    $req = $ask['request'];

    /* Only service questions are answerable this way so far. Anything else
     * falls through to the findings panel rather than getting a hedged
     * non-answer with an official-looking border around it. */
    if (!in_array('service', $req['focus']) && $req['condition'] === '') return '';
    if (!isset($d['loops'])) return '';
    $L = $d['loops'];

    $body = '';
    $cav  = array();

    if ($req['condition'] !== '') {
        /* A named outcome. The breakdown is computed across ALL incident
         * types, not just this roster, so the figure is labelled that way --
         * quoting it as a roster number would be wrong by construction. */
        $hit = null;
        foreach ($L['by_condition'] as $bc) {
            if ((string)$bc['code'] === (string)$req['condition']) { $hit = $bc; break; }
        }
        $label = isset($vocab['conditions'][$req['condition']])
                 ? rtrim($vocab['conditions'][$req['condition']], '.')
                 : 'that outcome';
        if ($hit === null) {
            $body = 'No incidents were recorded as <b style="font-size:14px;">'
                  . htmlspecialchars($label) . '</b> in this period.';
        } else {
            $body = '<b>' . iss_prompt_n($hit['loops']) . '</b> loop'
                  . ($hit['loops'] == 1 ? '' : 's') . ' lost to <b style="font-size:14px;">'
                  . htmlspecialchars($label) . '</b>, across '
                  . (int)$hit['n'] . ' incident' . ($hit['n'] == 1 ? '' : 's') . '.';
            $cav[] = 'Counted across all incident types, not only this roster.';
        }
        $cav[] = 'Outcome is recorded on levels 3 and 4 only.';
    } else {
        $body = '<b>' . iss_prompt_n($L['rostered']) . '</b> loop'
              . ($L['rostered'] == 1 ? '' : 's') . ' lost on this roster, from '
              . (int)$L['inc_rostered'] . ' incident' . ($L['inc_rostered'] == 1 ? '' : 's') . '.';
        if ($L['all'] > $L['rostered'] + 0.001) {
            $body .= ' Across all incident types, <b style="font-size:14px;">'
                   . iss_prompt_n($L['all']) . '</b>.';
            $cav[] = iss_prompt_n($L['all'] - $L['rostered'])
                   . ' of those have no equipment attributed.';
        }
    }

    if ($L['blank'] > 0) {
        $cav[] = (int)$L['blank'] . ' incident' . ($L['blank'] == 1 ? '' : 's')
               . ' record no loop value and are excluded.';
    }

    $h  = '<div class="ask-ans"><div class="q">'
        . htmlspecialchars($ask['asked']) . '</div><div class="a">' . $body . '</div>';
    if (count($cav)) $h .= '<div class="cav">' . implode(' ', $cav) . '</div>';
    return $h . '</div>';
}

/* Half loops are real; whole ones should not print a trailing .0 */
function iss_prompt_n($v) { return rtrim(rtrim(number_format((float)$v, 1), '0'), '.'); }

/* ---------------------------------------------------------------------------
 * 9. THE ANSWER AS A BAND
 *
 * Takes the slot the generic key finding occupies, in two registers, because
 * the readers split that way: a controller wants the sentence, a supervisor
 * checking it wants the figures behind it. Technical here means MORE NUMBERS,
 * not harder sentences -- the readers are operations staff, many reading
 * English as a second language, and a register change should never be a
 * vocabulary test.
 *
 * Self-contained toggle. It does not reach into iss_insight_audience.php's
 * state, so the two cannot fight over which view is showing.
 * -------------------------------------------------------------------------*/
function iss_prompt_band_css() {
    return "<style>
.ask-band{ border-left:4px solid #FDB813; background:#FBFAF6; border:1px solid #E5DECC;
           border-left:4px solid #FDB813; border-radius:0 6px 6px 0; padding:12px 16px; margin:14px 0; }
.ask-band-top{ display:flex; align-items:baseline; gap:12px; flex-wrap:wrap; margin-bottom:7px; }
.ask-band-lab{ font-size:11px; color:#5A6275; text-transform:uppercase; letter-spacing:.06em;
               font-weight:700; white-space:nowrap; }
.ask-band-q{ font-size:12px; color:#5A6275; font-style:italic; flex:1 1 auto; }
.ask-band-tabs{ display:flex; gap:0; border:1px solid #D8D2C2; border-radius:4px; overflow:hidden; }
.ask-band-tabs button{ border:none; background:#fff; color:#5A6275; font-size:11px; font-weight:600;
                       padding:3px 11px; cursor:pointer; font-family:inherit; }
.ask-band-tabs button.on{ background:#00529B; color:#fff; }
.ask-band-body{ font-size:14px; color:#1A2238; line-height:1.6; }
.ask-band-body b{ color:#00529B; }
.ask-band-fig{ display:none; }
.ask-band.figs .ask-band-sum{ display:none; }
.ask-band.figs .ask-band-fig{ display:block; }
.ask-band table{ width:100%; border-collapse:collapse; font-size:13px; }
.ask-band table td{ padding:5px 0; border-top:1px solid #EFEADC; }
.ask-band table tr:first-child td{ border-top:none; }
.ask-band table td.v{ text-align:right; font-weight:600; color:#00529B; white-space:nowrap; }
.ask-band-note{ font-size:12px; color:#5A6275; margin-top:8px; }
.ask-band-gap{ font-size:12.5px; line-height:1.5; color:#8A5A00; background:#FFF6E0;
               border:1px solid #F0DCA8; border-radius:4px; padding:8px 11px; margin-top:10px; }
\@media print{ .ask-band-tabs{ display:none; }
              .ask-band .ask-band-fig{ display:block !important; margin-top:10px; }
              .ask-band.figs .ask-band-sum{ display:block !important; } }
</style>
<script>
function askBandView(el, mode){
  /* By id, not by walking up matching a class SUBSTRING: ask-band-tabs and
     ask-band-top both contain 'ask-band', so the walk stopped at the tabs and
     rewrote their class instead of the band's. */
  var b = document.getElementById('askBand');
  if(!b) return false;
  b.className = (mode === 'figs') ? 'ask-band figs' : 'ask-band';
  var bs = b.getElementsByTagName('button');
  for(var i=0;i<bs.length;i++){
    bs[i].className = (bs[i].getAttribute('data-m') === mode) ? 'on' : '';
  }
  return false;
}
</script>";
}

/* ---------------------------------------------------------------------------
 * 10. WHAT THIS SYSTEM DOES NOT HOLD
 *
 * A question can be half answerable. "How do cancelled loops affect our
 * planned loops" has a numerator the ISS keeps and a denominator it does not,
 * and answering only the half that computes -- with a confident figure and a
 * toggle full of numbers -- reads as though the whole question was addressed.
 * That is worse than refusing, because the reader has no cue to doubt it.
 *
 * So: name the missing half out loud, answer the rest normally. These are
 * concepts operations staff reasonably expect a report to know, that this
 * particular report has no column for. Matched on the raw question, not the
 * leftover, because the answerable words get consumed by the parser first.
 * -------------------------------------------------------------------------*/
function iss_prompt_unsupported($text, $vocab = null) {
    $t = ' ' . strtolower(preg_replace('/\s+/', ' ', $text)) . ' ';
    $map = array(
        'planned or scheduled loops'
            => '/\b(?:planned|scheduled|plan|target|timetabled|supposed to run|expected loops)\b/',
        'on-time performance'
            => '/\b(?:on[ -]?time|punctual|punctuality|lateness|delay minutes|late minutes)\b/',
        'train availability'
            => '/\b(?:availabilit|uptime|fleet available|trains available|serviceab)\b/',
        'ridership'
            => '/\b(?:ridership|passenger count|patronage|footfall|how many passengers)\b/',
        'headway'
            => '/\bheadway/',
        'time between failures'
            => '/\b(?:mtbf|mean time between|time between failures|kilometres between)\b/',
        'repair or downtime duration'
            => '/\b(?:downtime|repair time|time to repair|mttr|how long .{0,20}(?:down|out of service))\b/',
        'costs'
            => '/\b(?:cost|costs|budget|peso|php|expenditure|spend)\b/',
        'staffing'
            => '/\b(?:staff|manpower|crew|headcount|technicians available)\b/',
    );
    if (is_array($vocab) && isset($vocab['lacks'])) {
        foreach ($vocab['lacks'] as $label => $re) { $map[$label] = $re; }
    }
    $hits = array();
    foreach ($map as $label => $re) {
        if (preg_match($re, $t)) $hits[] = $label;
    }
    return $hits;
}

function iss_prompt_band($ask, $vocab, $d) {
    if (!is_array($ask) || trim($ask['asked']) === '') return '';
    $req = $ask['request'];

    /* The band fires for ANY question that resolved to something. That is the
     * whole trick: once a question has narrowed the filters, the report below
     * already IS the answer -- the grand total on a roster narrowed to Static
     * Converter is "how many cars broke down because of static converter".
     * The band says that headline back in the question's terms; it computes
     * nothing new and infers nothing.
     *
     * A question that resolved to nothing still gets no band. A confident
     * heading over an unfiltered report is worse than no heading at all. */
    /* A gap alone is worth a band: "what is our on-time performance" resolves
     * no filter, and silence would leave the reader assuming the report simply
     * had nothing to say about a period rather than nothing to say about the
     * measure. */
    $gaps = iss_prompt_unsupported($ask['asked'], $vocab);
    if (!iss_prompt_has_any($req) && !count($gaps)) return '';

    $rows = array(); $notes = array(); $sum = '';
    $L    = isset($d['loops']) ? $d['loops'] : null;
    $svc  = (in_array('service', $req['focus']) || $req['condition'] !== '');

    if (!iss_prompt_has_any($req)) {
        $sum = 'Nothing in that question maps to something this report holds.';
    } elseif ($svc && $L === null) {
        $sum = 'This report does not hold cancelled loops.';
        $gaps[] = 'cancelled loops';
    } elseif ($svc) {
        iss_prompt_band_service($req, $vocab, $L, $sum, $rows, $notes);
    } else {
        iss_prompt_band_failures($req, $vocab, $d, $L, $sum, $rows, $notes);
    }

    if (isset($d['uncovered']) && (int)$d['uncovered'] > 0) {
        $notes[] = (int)$d['uncovered'] . ' period'
                 . ((int)$d['uncovered'] == 1 ? ' has' : 's have') . ' no recorded data.';
    }

    $h  = '<div class="ask-band" id="askBand"><div class="ask-band-top">'
        . '<span class="ask-band-lab">Answer</span>'
        . '<span class="ask-band-q">' . htmlspecialchars($ask['asked']) . '</span>'
        . '<span class="ask-band-tabs">'
        . '<button type="button" data-m="sum" class="on" onclick="return askBandView(this,\'sum\');">Summary</button>'
        . '<button type="button" data-m="figs" onclick="return askBandView(this,\'figs\');">Figures</button>'
        . '</span></div>'
        . '<div class="ask-band-body ask-band-sum">' . $sum . '</div>'
        . '<div class="ask-band-body ask-band-fig"><table>';
    foreach ($rows as $r) $h .= '<tr><td>' . $r[0] . '</td><td class="v">' . $r[1] . '</td></tr>';
    $h .= '</table></div>';

    $gaps = array_values(array_unique($gaps));
    if (count($gaps)) {
        $what = (count($gaps) == 1)
              ? $gaps[0]
              : implode(', ', array_slice($gaps, 0, -1)) . ' or ' . $gaps[count($gaps)-1];
        $h .= '<div class="ask-band-gap">This report does not hold ' . htmlspecialchars($what)
            . ', so that part of the question cannot be answered from it'
            . (count($rows) ? ' &mdash; the figure above covers only the rest' : '')
            . '.</div>';
    }

    if (count($notes)) $h .= '<div class="ask-band-note">' . implode(' ', $notes) . '</div>';
    return $h . '</div>';
}

function iss_prompt_band_service($req, $vocab, $L, &$sum, &$rows, &$notes) {
    if ($req['condition'] !== '') {
        $hit = null;
        foreach ($L['by_condition'] as $bc) {
            if ((string)$bc['code'] === (string)$req['condition']) { $hit = $bc; break; }
        }
        $label = isset($vocab['conditions'][$req['condition']])
                 ? rtrim($vocab['conditions'][$req['condition']], '.') : 'that outcome';
        if ($hit === null) {
            $sum = 'No incidents were recorded as <b>' . htmlspecialchars($label) . '</b> in this period.';
        } else {
            $sum = '<b>' . iss_prompt_n($hit['loops']) . '</b> loop' . ($hit['loops'] == 1 ? '' : 's')
                 . ' were lost to <b>' . htmlspecialchars($label) . '</b>, from ' . (int)$hit['n']
                 . ' incident' . ($hit['n'] == 1 ? '' : 's') . '.';
        }
        $notes[] = 'Outcome figures cover all incident types, not only this roster.';
        $notes[] = 'Outcome is recorded on levels 3 and 4 only.';
    } else {
        $sum = '<b>' . iss_prompt_n($L['rostered']) . '</b> loop' . ($L['rostered'] == 1 ? '' : 's')
             . ' were lost on this roster, from ' . (int)$L['inc_rostered']
             . ' incident' . ($L['inc_rostered'] == 1 ? '' : 's') . '.';
        if ($L['all'] > $L['rostered'] + 0.001) {
            $sum .= ' Counting every incident type, <b>' . iss_prompt_n($L['all']) . '</b>.';
        }
        $rows[] = array('Loops lost &mdash; this roster', iss_prompt_n($L['rostered']));
        $rows[] = array('Loops lost &mdash; all incident types', iss_prompt_n($L['all']));
        $rows[] = array('Incidents cancelling &mdash; this roster', (int)$L['inc_rostered']);
    }
    foreach ($L['by_condition'] as $bc) {
        $rows[] = array(htmlspecialchars(rtrim($bc['label'], '.')) . ' &mdash; loops',
                        iss_prompt_n($bc['loops']));
    }
    if ($L['blank'] > 0) {
        $notes[] = (int)$L['blank'] . ' incident' . ($L['blank'] == 1 ? '' : 's')
                 . ' record no loop value and are left out.';
    }
    $notes[] = 'Loops counted per incident; failures per car.';
}

function iss_prompt_band_failures($req, $vocab, $d, $L, &$sum, &$rows, &$notes) {
    $unit  = isset($d['unit'])      ? $d['unit']           : 'car-level failures';
    $tot   = isset($d['total'])     ? (int)$d['total']     : 0;
    $inc   = isset($d['incidents']) ? (int)$d['incidents'] : 0;
    $dRows = (isset($d['rows']) && is_array($d['rows'])) ? $d['rows'] : array();
    $scope = iss_prompt_scope_phrase($req, $vocab);

    $rank = array();
    foreach ($dRows as $r) {
        if (isset($r['total']) && (int)$r['total'] > 0) {
            $rank[] = array('label'=>$r['label'], 'total'=>(int)$r['total']);
        }
    }
    usort($rank, 'iss_prompt_cmp_total');

    /* Distinct cars, when that is what was asked. Counted by SQL on the page;
       it cannot be derived from the failure total here, and guessing it from
       incidents would be wrong whenever an incident involved more than one. */
    $cars = isset($d['cars']) ? (int)$d['cars'] : -1;

    if ($req['measure'] === 'cars' && $cars >= 0) {
        $sum = '<b>' . $cars . '</b> car' . ($cars == 1 ? '' : 's')
             . ' were involved' . ($scope !== '' ? ' ' . $scope : '')
             . ', across ' . $tot . ' ' . $unit . '.';
        $notes[] = 'A car failing more than once is counted once here and once per failure in the totals.';
    } elseif ($req['measure'] === 'incidents') {
        $sum = '<b>' . $inc . '</b> incident' . ($inc == 1 ? '' : 's')
             . ($scope !== '' ? ' ' . $scope : '') . ', producing ' . $tot . ' ' . $unit . '.';
    } elseif ($tot === 0) {
        $sum = 'No ' . $unit . ' were recorded' . ($scope !== '' ? ' ' . $scope : '') . '.';
    } elseif (in_array('concentration', $req['focus']) && count($rank)) {
        $sum = '<b>' . htmlspecialchars($rank[0]['label']) . '</b> had the most, with <b>'
             . (int)$rank[0]['total'] . '</b> of ' . $tot . ' ' . $unit
             . ($scope !== '' ? ' ' . $scope : '') . '.';
        if (count($rank) > 1) {
            $sum .= ' Next is ' . htmlspecialchars($rank[1]['label'])
                  . ' with ' . (int)$rank[1]['total'] . '.';
        }
    } elseif (in_array('trend', $req['focus'])
              && isset($d['bucket_totals']) && count($d['bucket_totals']) >= 2) {
        $bt = array_values($d['bucket_totals']);
        $bl = isset($d['buckets']) ? array_values($d['buckets']) : array();
        $a  = (int)$bt[0]; $z = (int)$bt[count($bt)-1];
        $an = isset($bl[0])            ? $bl[0]            : 'the first period';
        $zn = isset($bl[count($bt)-1]) ? $bl[count($bt)-1] : 'the last period';
        $dir = ($z > $a) ? 'higher' : (($z < $a) ? 'lower' : 'unchanged');
        $sum = ucfirst($unit) . ($scope !== '' ? ' ' . $scope : '') . ' went from <b>' . $a
             . '</b> in ' . htmlspecialchars($an) . ' to <b>' . $z . '</b> in '
             . htmlspecialchars($zn) . ' &mdash; ' . $dir . '.';
        $notes[] = 'First and last period only. Whether that is a real shift or ordinary variation is what the analysis below tests.';
    } else {
        $sum = '<b>' . $tot . '</b> ' . $unit . ($scope !== '' ? ' ' . $scope : '')
             . ($inc > 0 ? ', from ' . $inc . ' incident' . ($inc == 1 ? '' : 's') : '') . '.';
    }

    if ($cars >= 0) $rows[] = array('Cars involved', $cars);
    $rows[] = array(ucfirst($unit), $tot);
    if ($inc > 0) $rows[] = array('Incidents', $inc);
    $n = 0;
    foreach ($rank as $r) {
        if ($n++ >= 6) break;
        $rows[] = array(htmlspecialchars($r['label']), (int)$r['total']);
    }
    if (count($rank) > 6) $rows[] = array('&nbsp;&nbsp;and ' . (count($rank) - 6) . ' more', '');
    if ($L !== null && $L['rostered'] > 0) {
        $rows[] = array('Loops lost (per incident)', iss_prompt_n($L['rostered']));
    }
    $notes[] = 'Failures counted per car; loops per incident.';
}

/* Says the scope back in the question's own terms, so a figure is never
 * quoted without what it was narrowed to. */
function iss_prompt_scope_phrase($req, $vocab) {
    $p = array();
    if (count($req['equipment'])) {
        $names = array();
        foreach ($req['equipment'] as $id) {
            if (isset($vocab['equipment'][$id])) $names[] = $vocab['equipment'][$id];
        }
        if (count($names) == 1)    $p[] = 'on ' . htmlspecialchars($names[0]);
        elseif (count($names) > 1) $p[] = 'on ' . count($names) . ' equipment types';
    }
    if ($req['level'] !== '')
        $p[] = ($req['level'] === '0') ? 'with no severity level' : 'at level ' . $req['level'];
    if ($req['car'] > 0) $p[] = 'on Car ' . (int)$req['car'];
    if ($req['from'] !== '')
        $p[] = 'between ' . date('j M Y', strtotime($req['from']))
             . ' and ' . date('j M Y', strtotime($req['to']));
    return implode(', ', $p);
}

function iss_prompt_cmp_total($a, $b) {
    if ($a['total'] == $b['total']) return 0;
    return ($a['total'] < $b['total']) ? 1 : -1;
}