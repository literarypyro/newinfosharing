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
define('ISS_PROMPT_V', '8');

/* The parse blocks the page, unlike the narration, which is async -- the
 * queries cannot run until the filters are known. So it gets a much shorter
 * leash than iss_insight's 25s: a parse is a few dozen output tokens, and if
 * it has not come back in 8 the rules result is already good enough. */
define('ISS_PROMPT_TIMEOUT', 8);

/* @timeaxis -- The entry form builds its hour select 1..12 with am/pm
 * defaulting to AM, so an encoder who never touches the time controls saves
 * 01:00. Profiling found 1,443 rows at exactly that value against 83 at the
 * next most common one, and MRT-3 does not run at 01:00 -- it is a default,
 * not an observation. Excluded from HOUR-based narrowing only: the DATE on
 * those rows is real, so weekday questions keep them.
 * Set to '' to disable the exclusion. */
if (!defined('ISS_PROMPT_TIME_SENTINEL')) { define('ISS_PROMPT_TIME_SENTINEL', '01:00'); }


/* ---------------------------------------------------------------------------
 * 1. VOCABULARY
 *
 * The page owns this. Nothing outside it can ever be requested, which is what
 * makes free text safe to put next to a SQL-backed report: the parser's output
 * is not escaped and then trusted, it is MATCHED against values the page
 * supplied and discarded if it does not match one.
 * -------------------------------------------------------------------------*/
/* @timeaxis -- Default bands (Sep 2026 profiling, 24,309 rows).
 *
 * Boundaries come from operations, NOT from where the histogram drops:
 *   - service preparation starts around 04:00  (03 -> 04 is 59 -> 475)
 *   - last train operation runs 21:30 to 23:00, so hour 22 IS revenue
 *     service and the 21 -> 22 fall of 318 -> 76 is the FLEET EMPTYING as
 *     final trips complete, not the day ending. Cutting the band there
 *     would have split revenue service in two and labelled half of it
 *     "after service".
 *
 * Band totals, sentinel removed: 04 = 475, 05-08 = 8067, 09-15 = 8541,
 * 16-19 = 4435, 20-22 = 1032, 23-03 = 316.
 *
 * HOUR GRANULARITY IS A REAL LIMIT. iss_prompt_time_sql() compares hour(),
 * so a boundary falling mid-hour -- last train operation beginning at 21:30
 * -- cannot be expressed. Hour 21 therefore holds both ordinary evening
 * service and the start of last train operation. If minute precision is ever
 * needed, the helper switches from hour() to time() comparisons; it is a
 * contained change, but it is not made here on speculation.
 *
 * LABELS ARE CLOCK TIMES, NOT OPERATIONS. It is not established from this
 * data what runs between 23:00 and 04:00 -- revenue collection, track works,
 * a maintenance train, or some combination. A label like "After service"
 * would assert a service state the schema cannot confirm, which is the same
 * error as reading a coverage gap as a zero.
 *
 * COUNTS ACROSS BANDS ARE NOT LIKE FOR LIKE, and neither are counts across
 * HOURS INSIDE the evening band: the fleet on the line falls steeply from
 * 20:00 to 23:00, so 76 incidents in hour 22 may be a higher per-train rate
 * than 638 in hour 20. These are narrowing terms, not a ranking. No finding
 * should compare one band's total against another's without a denominator.
 *
 * 'match' is spliced into a \b...\b wrapper, so literal words and pipes only. */
function iss_prompt_default_dayparts() {
    return array(
        'startup'  => array('label'=>'04:00-04:59',  'from'=>4,  'to'=>4,
            'match'=>'start ?up|startup|before service|pre[- ]service|first trip|first train|depot departure'),
        'am_peak'  => array('label'=>'05:00-08:59',  'from'=>5,  'to'=>8,
            'match'=>'am peak|morning peak|morning rush|am rush|morning|early morning'),
        'midday'   => array('label'=>'09:00-15:59',  'from'=>9,  'to'=>15,
            'match'=>'midday|mid day|middle of the day|daytime|during the day|off peak daytime'),
        'pm_peak'  => array('label'=>'16:00-19:59',  'from'=>16, 'to'=>19,
            'match'=>'pm peak|afternoon peak|evening peak|pm rush|evening rush|afternoon rush|rush hour|afternoon'),
        'evening'  => array('label'=>'20:00-22:59',  'from'=>20, 'to'=>22,
            'match'=>'evening|late service|last trips?|last train|last train operation|end of service|closing|final trips?'),
        /* Wraps midnight: 23..3 is two ranges, handled in iss_prompt_time_sql. */
        'overnight'=> array('label'=>'23:00-03:59',  'from'=>23, 'to'=>3,
            'match'=>'overnight|night ?time|at night|nights|night|after hours|engineering hours|maintenance window|track works?|revenue run|money train'),
    );
}

/* @placeaxis -- Where an incident happened, from incident_description.
 *
 * The column is called `direction`, but the entry form's own list shows it is
 * a location TYPE, not a bearing: Northbound and Southbound sit in the same
 * dropdown as Depot and Control Center. Full list, with row counts from the
 * Sep 2026 profile:
 *
 *     NB  Northbound              7944      D    Depot                  4289
 *     SB  Southbound              7446      NTB  North Turnback         2602
 *     ML  Mainline                 381      IR   Insertion/Removal      291
 *     S   Station                  253      CC   Control Center          86
 *     SPT Shaw Pocket Track          6      TPT  Taft Pocket Track        4
 *
 * ONLY THE UNAMBIGUOUS CODES ARE DECLARED HERE. Four are deliberately left
 * out, because a term for them would promise a completeness the data does
 * not have:
 *
 *   S, NB, SB -- a platform incident at station 5 is encoded EITHER as S
 *     with location 5, OR as NB/SB with location 5, and both are in the
 *     data. 10,783 rows carry a single station number but only 253 are typed
 *     S. A "station" term matching S alone would miss almost all of them,
 *     and a chip reading "Station 5" would be stating something false.
 *   ML -- NB and SB rows are on the mainline too, so a "mainline" term
 *     matching ML alone would return 381 of 15,771.
 *
 * Those four wait on a decision about encoding practice, not on more data.
 * The six declared below have no such overlap: an incident is in the depot
 * or it is not.
 *
 * `location` refines these (station number, or an inter-station segment like
 * "13-12"), and is NOT modelled yet -- 73 distinct segment values still need
 * reconciling against 12 adjacent pairs. */
function iss_prompt_default_places() {
    return array(
        'depot'    => array('label'=>'Depot',              'codes'=>array('D'),
            'match'=>'depot|depots|in the depot|at the depot'),
        'turnback' => array('label'=>'North Turnback',     'codes'=>array('NTB'),
            'match'=>'turn ?back|turn ?backs|north turn ?back|ntb'),
        'ir_area'  => array('label'=>'Insertion/Removal',  'codes'=>array('IR'),
            'match'=>'insertion|removal area|insertion ?/ ?removal|i ?/ ?r area|i and r area'),
        'shaw_pt'  => array('label'=>'Shaw Pocket Track',  'codes'=>array('SPT'),
            'match'=>'shaw pocket|shaw pocket track|shaw siding'),
        'taft_pt'  => array('label'=>'Taft Pocket Track',  'codes'=>array('TPT'),
            'match'=>'taft pocket|taft pocket track|taft siding'),
        'control'  => array('label'=>'Control Center',     'codes'=>array('CC'),
            'match'=>'control cent(?:er|re)|control room|occ'),
        /* A set rather than a single code: "off the running line" is a real
           operational question and none of the six answer it alone. */
        'offline'  => array('label'=>'Off the running line',
            'codes'=>array('D','NTB','IR','SPT','TPT'),
            'match'=>'off the (?:running )?line|off line|not on the line|away from the line|non[- ]?revenue location'),
    );
}

/* @stationaxis -- Station numbers, in line order.
 *
 * VERIFY THIS AGAINST THE LOOKUP TABLE BEFORE RELYING ON THE NAMES. The
 * numbering (1 = North Avenue .. 13 = Taft) comes from departure_location on
 * the skipping table; the names here are the line in that order. The NUMBERS
 * are what the filter uses and they are safe -- a wrong name only mislabels a
 * chip, but that is still a chip stating something false, so pass a verified
 * map in the page's vocab spec if there is any doubt.
 *
 * Note incident_description.location has at least one row numbered 15, which
 * is South Turnback in the departure_location scheme -- so a second numbering
 * has leaked into this column. Values above 13 resolve to no station rather
 * than to a wrong one. */
function iss_prompt_default_stations() {
    return array(
         1=>'North Avenue',       2=>'Quezon Avenue',    3=>'GMA-Kamuning',
         4=>'Araneta Center-Cubao', 5=>'Santolan-Annapolis', 6=>'Ortigas',
         7=>'Shaw Boulevard',     8=>'Boni',             9=>'Guadalupe',
        10=>'Buendia',           11=>'Ayala',           12=>'Magallanes',
        13=>'Taft Avenue',
    );
}

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
        /* @timeaxis -- Hour-of-day bands. The page may override them, and
         * SHOULD if it already draws a weekday/time-band heatmap: a typed
         * question and a printed chart must not disagree about what "AM peak"
         * covers. A band whose 'to' is lower than its 'from' wraps midnight. */
        'dayparts'  => isset($spec['dayparts']) ? $spec['dayparts'] : iss_prompt_default_dayparts(),
        /* @placeaxis -- Set to array() on a page whose query cannot reach
         * incident_description, so a place question is dropped rather than
         * resolved into a filter the page will not apply. */
        'places'    => isset($spec['places']) ? $spec['places'] : iss_prompt_default_places(),
        /* @stationaxis -- array() disables station, segment and bearing terms
         * on a page that cannot reach incident_description. */
        'stations'  => isset($spec['stations']) ? $spec['stations'] : iss_prompt_default_stations(),
        /* @unitlock -- For DRILL-DOWNS, which are scoped to one unit before a
         * question is ever typed. equipment_history is one equipment type;
         * car_history is one car. A question naming a DIFFERENT one cannot be
         * honoured, and the dangerous outcome is not an error -- it is the
         * page quietly answering about its own unit under a read-back that
         * looks complete. "compare car 33 with car 41" on car 33's page must
         * say car 41 is not on this page, not return car 33's answer.
         *
         * The vocabulary still carries the FULL roster, deliberately. To
         * refuse a unit by name the parser has to recognise the name first;
         * a one-entry roster would make "Bogie" an unknown word and drop it
         * silently, which is the failure this exists to prevent.
         *
         *   array('equipment' => array('id'=>'7', 'label'=>'Air Conditioning'))
         *   array('car'       => array('id'=>33,  'label'=>'Car 33'))
         *
         * Report pages pass nothing and behave exactly as before. */
        'locked'    => isset($spec['locked']) ? $spec['locked'] : array(),
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

    /* -- time of day and weekday ------------------------------------------
       @timeaxis -- The axis no control on these pages can carry: 24 hours by
       7 weekdays is not a dropdown, which is exactly why a typed question
       earns its place here.

       Profiling (Sep 2026) confirms the hour is genuinely encoded and not a
       form default: 24,309 dated rows, 1,304 distinct clock values, exactly
       one row at midnight, on-the-hour at 8.5% (human rounding, not
       defaulting). The one exception is handled in iss_prompt_time_sql(). */
    foreach ($vocab['dayparts'] as $dk => $dp) {
        /* @delim -- ~ not /, for the reason given at the places loop below. */
        if (isset($dp['match']) && $dp['match'] !== ''
            && preg_match('~\b(?:' . $dp['match'] . ')\b~', $t, $m)) {
            $req['daypart'] = (string)$dk; $usedTxt[] = $m[0]; break;
        }
    }

    /* -- station, segment, bearing ----------------------------------------
       @stationaxis -- Runs BEFORE the clock rules on purpose: "between
       station 4 and 5" must not be read as 04:00-05:00. The literal word
       "station" is what separates them, so the segment forms all require it
       or an explicit dash.

       "4-5" and "5-4" are the same piece of track recorded in the two
       directions of travel, so a segment is stored unordered and matched
       both ways. A station is the station itself and nothing else: 4-5 and
       5-6 are in-between, technically not at station 5. */
    $stn = $vocab['stations'];
    if (count($stn)) {
        /* Full names plus the short forms people actually type, derived from
           the roster rather than hardcoded, and kept only where they are
           unambiguous -- "avenue" belongs to two stations and is dropped,
           "cubao" to one and is kept. Longest first so a full name is never
           taken by a short form that is a substring of it. */
        $names = iss_prompt_station_aliases($stn);

        $seg = null;
        if (preg_match('/\bbetween\s+stations?\s+(\d{1,2})\s+(?:and|to|-|&)\s+(?:station\s+)?(\d{1,2})\b/', $t, $m)) {
            $seg = array((int)$m[1], (int)$m[2]); $usedTxt[] = $m[0];
        } elseif (preg_match('/\bstations?\s+(\d{1,2})\s*-\s*(\d{1,2})\b/', $t, $m)) {
            $seg = array((int)$m[1], (int)$m[2]); $usedTxt[] = $m[0];
        } elseif (preg_match('/\b(\d{1,2})\s*-\s*(\d{1,2})\s+(?:segment|section|stretch|in ?between)\b/', $t, $m)) {
            $seg = array((int)$m[1], (int)$m[2]); $usedTxt[] = $m[0];
        } elseif (preg_match('/\bbetween\s+([a-z][a-z .\-]{2,24}?)\s+and\s+([a-z][a-z .\-]{2,24}?)\s*(?:$|[,.?])/', $t, $m)) {
            $a = iss_prompt_station_by_name($m[1], $names);
            $b = iss_prompt_station_by_name($m[2], $names);
            if ($a && $b) { $seg = array($a, $b); $usedTxt[] = $m[0]; }
        }
        if ($seg !== null && isset($stn[$seg[0]]) && isset($stn[$seg[1]]) && $seg[0] !== $seg[1]) {
            $req['segment'] = $seg;
        }

        /* A station only when no segment was found -- "between station 4 and
           5" names two stations and means neither of them on its own. */
        if (!count($req['segment'])) {
            if (preg_match('/\bstations?\s*(?:no\.?|number|#)?\s*(\d{1,2})\b/', $t, $m)) {
                $n = (int)$m[1];
                if (isset($stn[$n])) { $req['station'] = $n; $usedTxt[] = $m[0]; }
            } elseif ($req['place'] === '') {
                /* Skipped when a place term already matched: "taft pocket
                   track" is the pocket track, not Taft Avenue, and the place
                   rule ran first with the longer match. */
                foreach ($names as $nm => $num) {
                    if (strpos($t, ' ' . $nm . ' ') !== false
                        || strpos($t, ' ' . $nm . ',') !== false
                        || strpos($t, ' ' . $nm . '.') !== false) {
                        $req['station'] = $num; $usedTxt[] = $nm; break;
                    }
                }
            }
        }

        if (preg_match('/\b(?:north ?bound|northbound|nb bound|going north)\b/', $t, $m)) {
            $req['bearing'] = 'NB'; $usedTxt[] = $m[0];
        } elseif (preg_match('/\b(?:south ?bound|southbound|sb bound|going south)\b/', $t, $m)) {
            $req['bearing'] = 'SB'; $usedTxt[] = $m[0];
        }
    }

    /* Explicit clock bounds are kept ALONGSIDE a named band and beat it in
       the SQL: someone who typed "after 20:00" meant 20:00, not wherever the
       evening band happens to start. The lookahead keeps "after 3 months"
       and "after 2 repairs" out -- those are durations, not times. */
    $HR = '(\d{1,2})(?::(\d{2}))?\s*(am|pm|h|hrs|hours)?';
    $NT = '(?!\s*(?:months?|weeks?|days?|years?|minutes?|hours?|incidents?|failures?|repairs?|times?|cars?))';
    if (preg_match('/\bbetween\s+' . $HR . '\s+(?:and|to|-)\s+' . $HR . '\b/', $t, $m)) {
        $a = iss_prompt_hour24($m[1], isset($m[3]) ? $m[3] : '');
        $b = iss_prompt_hour24(isset($m[4]) ? $m[4] : '', isset($m[6]) ? $m[6] : '');
        if ($a >= 0 && $b >= 0) { $req['hour_from'] = $a; $req['hour_to'] = $b; $usedTxt[] = $m[0]; }
    } elseif (preg_match('/\b(?:after|from|past|later than)\s+' . $HR . '\b' . $NT . '/', $t, $m)) {
        $a = iss_prompt_hour24($m[1], isset($m[3]) ? $m[3] : '');
        if ($a >= 0) { $req['hour_from'] = $a; $usedTxt[] = $m[0]; }
    } elseif (preg_match('/\b(?:before|until|till|earlier than|up to)\s+' . $HR . '\b' . $NT . '/', $t, $m)) {
        $b = iss_prompt_hour24($m[1], isset($m[3]) ? $m[3] : '');
        /* "before 6am" is 00:00-05:59, so the inclusive bound is one hour
           back. At 0 it would wrap to 23 and invert the range, so that case
           resolves to nothing rather than to the whole day. */
        if ($b > 0) { $req['hour_from'] = 0; $req['hour_to'] = $b - 1; $usedTxt[] = $m[0]; }
    } elseif (preg_match('/\b(?:at|around|about)\s+(\d{1,2})(?::(\d{2}))?\s*(am|pm)\b/', $t, $m)
           || preg_match('/\b(?:at|around|about)\s+(\d{1,2}):(\d{2})\b/', $t, $m)) {
        $a = iss_prompt_hour24($m[1], isset($m[3]) ? $m[3] : '');
        if ($a >= 0) { $req['hour_from'] = $a; $req['hour_to'] = $a; $usedTxt[] = $m[0]; }
    }

    /* -- place ------------------------------------------------------------
       @placeaxis -- Longest match wins, so "shaw pocket track" is not taken
       by a shorter term first. Only the six unambiguous codes plus the
       off-line set are reachable; see iss_prompt_default_places(). */
    $bestK = ''; $bestLen = 0;
    foreach ($vocab['places'] as $pk => $pl) {
        if (!isset($pl['match']) || $pl['match'] === '') continue;
        /* @delim -- The delimiter is ~, not /, and this is a bug fix rather
           than a style choice. The ir_area alternation contains a literal
           slash ("insertion ?/ ?removal", "i ?/ ?r area"), which closed a
           /.../ pattern early and left " ?removal|..." being read as pattern
           modifiers. preg_match then returned FALSE with a warning on every
           single parse, so the insertion-and-removal area term never matched
           at all -- a silently dead term rather than a visible error. No
           authored pattern contains ~, and these come from this file and not
           from the question, so the delimiter is safe. */
        if (preg_match('~\b(?:' . $pl['match'] . ')\b~', $t, $m)) {
            if (strlen($m[0]) > $bestLen) { $bestK = (string)$pk; $bestLen = strlen($m[0]); $bestHit = $m[0]; }
        }
    }
    if ($bestK !== '') { $req['place'] = $bestK; $usedTxt[] = $bestHit; }

    /* Weekday. Long forms first so \b does the right thing -- "sat" cannot
       match inside "saturday" anyway, but the order documents the intent. */
    if (preg_match('/\bweek[ -]?ends?\b/', $t, $m)) {
        $req['weekday'] = 'weekend'; $usedTxt[] = $m[0];
    } elseif (preg_match('/\b(?:week[ -]?days?|working days?)\b/', $t, $m)) {
        $req['weekday'] = 'weekday'; $usedTxt[] = $m[0];
    } else {
        $wd = array('monday'=>1,'mon'=>1,'tuesday'=>2,'tuesdays'=>2,'tues'=>2,'tue'=>2,
                    'wednesday'=>3,'weds'=>3,'wed'=>3,'thursday'=>4,'thurs'=>4,'thu'=>4,
                    'friday'=>5,'fri'=>5,'saturday'=>6,'sat'=>6,'sunday'=>7,'sun'=>7);
        foreach ($wd as $w => $n) {
            if (preg_match('/\b' . $w . 's?\b/', $t, $m)) {
                $req['weekday'] = (string)$n; $usedTxt[] = $m[0]; break;
            }
        }
    }

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

/* 12h -> 24h. Returns -1 for anything that is not a real hour, so a caller
 * can test one value instead of branching on the am/pm string. */
function iss_prompt_cmp_len($a, $b) {
    $la = strlen($a); $lb = strlen($b);
    if ($la === $lb) return strcmp($a, $b);
    return ($la < $lb) ? 1 : -1;
}

/* Tolerates the abbreviations people actually type -- "cubao" for "Araneta
 * Center-Cubao", "shaw" for "Shaw Boulevard" -- by accepting a name that is
 * contained in exactly ONE roster entry. Two matches is ambiguous and
 * resolves to nothing rather than to a guess. */
/* @stationaxis -- alias => station number, ambiguous forms discarded. */
function iss_prompt_station_aliases($stn) {
    $names = array();
    $count = array();
    foreach ($stn as $num => $nm) {
        $k = strtolower(trim($nm));
        if ($k === '') continue;
        $names[$k] = (int)$num;
        foreach (preg_split('/[^a-z0-9]+/', $k) as $w) {
            if (strlen($w) < 4) continue;              /* "gma", "st" -- too short to be safe */
            if (!isset($count[$w])) { $count[$w] = array(); }
            if (!in_array((int)$num, $count[$w])) { $count[$w][] = (int)$num; }
        }
    }
    foreach ($count as $w => $owners) {
        /* One owner only. "avenue" is North Avenue and Taft Avenue, so it goes;
           "boulevard" is Shaw alone, so it stays. A word that is already a full
           roster name is left as it is. */
        if (count($owners) === 1 && !isset($names[$w])) { $names[$w] = $owners[0]; }
    }
    uksort($names, 'iss_prompt_cmp_len');
    return $names;
}

function iss_prompt_station_by_name($s, $names) {
    $s = trim(strtolower(preg_replace('/\s+/', ' ', $s)));
    if ($s === '') return 0;
    if (isset($names[$s])) return $names[$s];
    $hit = 0; $n = 0;
    foreach ($names as $nm => $num) {
        if (strpos($nm, $s) !== false) { $hit = $num; $n++; }
    }
    return ($n === 1) ? $hit : 0;
}

function iss_prompt_hour24($h, $ap) {
    if ($h === '' || !preg_match('/^\d{1,2}$/', (string)$h)) return -1;
    $h  = (int)$h;
    $ap = strtolower(trim((string)$ap));
    if     ($ap === 'pm' && $h >= 1 && $h <= 11) { $h += 12; }
    elseif ($ap === 'am' && $h === 12)           { $h = 0;   }
    return ($h >= 0 && $h <= 23) ? $h : -1;
}

function iss_prompt_blank() {
    return array(
        'from'=>'', 'to'=>'', 'period_said'=>'',
        'level'=>'', 'level_set'=>false,
        'car'=>0,   'car_set'=>false,
        'equipment'=>array(),
        'grain'=>'', 'audience'=>'', 'condition'=>'', 'measure'=>'',
        /* @timeaxis -- hour_from/hour_to are -1 when unset, because 0 is a
         * real hour. A named band and an explicit clock range are separate
         * fields: the explicit one wins in the SQL, but both are kept so the
         * chips can show that the band was superseded rather than ignored. */
        'daypart'=>'', 'weekday'=>'', 'hour_from'=>-1, 'hour_to'=>-1,
        /* @placeaxis -- a key into vocab['places'], never a raw code. */
        'place'=>'',
        /* @stationaxis -- station is a number 1..N or 0 for unset; segment is
         * an unordered pair array(a,b) or array(); bearing is '', 'NB', 'SB'. */
        'station'=>0, 'segment'=>array(), 'bearing'=>'',
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
         . "  daypart   : one of " . implode(', ', array_keys($vocab['dayparts'])) . "\n"
         . "  weekday   : \"1\"-\"7\" (Monday=1), or \"weekend\", or \"weekday\"\n"
         . "  place     : one of " . implode(', ', array_keys($vocab['places'])) . "\n"
         . "  station   : station number 1-" . (count($vocab['stations']) ? max(array_keys($vocab['stations'])) : 0) . "\n"
         . "  segment   : [a,b] two DIFFERENT station numbers, for the track between them\n"
         . "  bearing   : \"NB\" or \"SB\"\n"
         . "  hour_from : integer 0-23, start of an explicit clock range\n"
         . "  hour_to   : integer 0-23, end of an explicit clock range\n"
         . "  audience  : \"exec\" or \"tech\"\n"
         . "  focus     : array from " . implode(', ', $focusKeys) . "\n\n"
         . "Rules:\n"
         . "- Use ONLY ids and values listed here. Never invent an id or a field.\n"
         . "- Today is " . date('Y-m-d') . ". Data runs " . $vocab['date_min'] . " to " . $vocab['date_max'] . ".\n"
         . "- Car numbers outside 1-73 are valid: they stand for non-revenue trains.\n"
         . "- level \"0\" means a normal incident with no severity assigned. It is a real\n"
         . "  tier, not \"all levels\" -- use \"\" when the question does not restrict level.\n"
         . "- Times are 24h. Use daypart for a NAMED window (peak, midday, night)\n"
         . "  and hour_from/hour_to only for an explicit clock range the question\n"
         . "  states. Do not translate a named band into hours yourself.\n"
         . "- station is AT the station. The track between two stations is a\n"
         . "  segment, not a station -- never answer one with the other.\n"
         . "- There is no platform-level record: platform and concourse questions\n"
         . "  cannot be answered. Never map one onto station.\n"
         . "- Resolution time, downtime and repair duration are NOT held by this\n"
         . "  system. Never emit a field for them.\n"
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
        /* @unitlock -- named, not numbered: "Not applied: car 41 (this page
           covers Car 33 only)" tells the reader what happened to their
           question. "Not applied: car" does not. */
        if (isset($vocab['locked']['car']) && $cn !== (int)$vocab['locked']['car']['id']) {
            $rej[] = 'car ' . $cn . ' (this page covers '
                   . $vocab['locked']['car']['label'] . ' only)';
        }
        else if ($cn >= 0) { $req['car'] = $cn; $req['car_set'] = true; }
        else { $rej[] = 'car'; }
    }

    if (isset($raw['equipment']) && is_array($raw['equipment'])) {
        $ok = array();
        $lockEq = isset($vocab['locked']['equipment']) ? $vocab['locked']['equipment'] : null;
        foreach ($raw['equipment'] as $id) {
            $hit = isset($vocab['equipment'][$id]) ? (string)$id
                 : (isset($vocab['equipment'][(string)(int)$id]) ? (string)(int)$id : '');
            if ($hit === '') {
                /* Unknown to the vocabulary: report the id, as before. */
                $rej[] = 'equipment ' . $id;
            }
            /* @unitlock -- recognised, but not this page's unit. Named in the
               rejection using the vocabulary's own label. */
            else if ($lockEq !== null && $hit !== (string)$lockEq['id']) {
                $rej[] = $vocab['equipment'][$hit] . ' (this page covers '
                       . $lockEq['label'] . ' only)';
            }
            else { $ok[] = $hit; }
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
    /* @timeaxis */
    if (isset($raw['daypart'])) {
        $dk = (string)$raw['daypart'];
        if ($dk === '' || isset($vocab['dayparts'][$dk])) { $req['daypart'] = $dk; }
        else { $rej[] = 'time band ' . $dk; }
    }
    if (isset($raw['place'])) {
        $pk = (string)$raw['place'];
        if ($pk === '' || isset($vocab['places'][$pk])) { $req['place'] = $pk; }
        else { $rej[] = 'place ' . $pk; }
    }
    if (isset($raw['station'])) {
        $sn = (int)$raw['station'];
        if ($sn === 0 || isset($vocab['stations'][$sn])) { $req['station'] = $sn; }
        else { $rej[] = 'station ' . $sn; }
    }
    if (isset($raw['segment']) && is_array($raw['segment'])) {
        $sg = array_values($raw['segment']);
        if (count($sg) === 2) {
            $a = (int)$sg[0]; $b = (int)$sg[1];
            if ($a !== $b && isset($vocab['stations'][$a]) && isset($vocab['stations'][$b])) {
                $req['segment'] = array($a, $b);
            } else { $rej[] = 'segment'; }
        } elseif (count($sg)) { $rej[] = 'segment'; }
    }
    if (isset($raw['bearing'])) {
        $bg = strtoupper(trim((string)$raw['bearing']));
        if ($bg === '' || $bg === 'NB' || $bg === 'SB') { $req['bearing'] = $bg; }
        else { $rej[] = 'bearing ' . $bg; }
    }
    if (isset($raw['weekday'])) {
        $wk = (string)$raw['weekday'];
        if ($wk === '' || $wk === 'weekend' || $wk === 'weekday'
            || (preg_match('/^[1-7]$/', $wk))) { $req['weekday'] = $wk; }
        else { $rej[] = 'weekday ' . $wk; }
    }
    if (isset($raw['hour_from']) || isset($raw['hour_to'])) {
        $a = isset($raw['hour_from']) ? (int)$raw['hour_from'] : -1;
        $b = isset($raw['hour_to'])   ? (int)$raw['hour_to']   : -1;
        $a = ($a >= 0 && $a <= 23) ? $a : -1;
        $b = ($b >= 0 && $b <= 23) ? $b : -1;
        if ($a >= 0 || $b >= 0) { $req['hour_from'] = $a; $req['hour_to'] = $b; }
        else { $rej[] = 'hours'; }
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
        if ($d === 'daypart')   { $req['daypart'] = ''; }
        if ($d === 'place')     { $req['place'] = ''; }
        if ($d === 'station')   { $req['station'] = 0; }
        if ($d === 'segment')   { $req['segment'] = array(); }
        if ($d === 'bearing')   { $req['bearing'] = ''; }
        if ($d === 'weekday')   { $req['weekday'] = ''; }
        if ($d === 'hours')     { $req['hour_from'] = -1; $req['hour_to'] = -1; }
        if (strpos($d, 'focus:') === 0) {
            $k = substr($d, 6); $keep = array();
            foreach ($req['focus'] as $fk) { if ($fk !== $k) $keep[] = $fk; }
            $req['focus'] = $keep;
        }
    }

    /* @unitlock -- Enforced HERE, at the end, and not only in the branches
       above, because those branches see the MODEL's raw output while the
       rules parser writes straight into the seed. A question parsed offline
       -- which is most of them -- never passes through them at all, so a lock
       applied only there would hold for the model path and silently fail for
       the common one. This runs over whatever ended up in the request, from
       either source.

       The page's own unit is never rejected: it is already the scope, so a
       question that names it is simply agreeing with the page. */
    if (isset($vocab['locked']['equipment']) && isset($req['equipment']) && is_array($req['equipment'])) {
        $lk = (string)$vocab['locked']['equipment']['id'];
        $keep = array();
        foreach ($req['equipment'] as $id) {
            if ((string)$id === $lk) { $keep[] = $id; continue; }
            $nm = isset($vocab['equipment'][(string)$id]) ? $vocab['equipment'][(string)$id] : ('equipment ' . $id);
            $msg = $nm . ' (this page covers ' . $vocab['locked']['equipment']['label'] . ' only)';
            if (!in_array($msg, $rej, true)) { $rej[] = $msg; }
        }
        $req['equipment'] = $keep;
    }
    if (isset($vocab['locked']['car']) && isset($req['car']) && !empty($req['car_set'])) {
        $lk = (int)$vocab['locked']['car']['id'];
        if ((int)$req['car'] !== $lk) {
            $msg = 'car ' . (int)$req['car'] . ' (this page covers '
                 . $vocab['locked']['car']['label'] . ' only)';
            if (!in_array($msg, $rej, true)) { $rej[] = $msg; }
            $req['car'] = 0; $req['car_set'] = false;
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

    /* Band boundaries are in the key: a page that redefines "AM peak" must
     * not serve a parse cached against the old definition. */
    $vhash = md5(ISS_PROMPT_V . '|' . json_encode($vocab['equipment'])
               . '|' . json_encode($vocab['conditions']) . '|' . $vocab['date_max']
               . '|' . json_encode($vocab['dayparts'])
               . '|' . json_encode(array_keys($vocab['places']))
               . '|' . json_encode($vocab['stations'])
               /* @unitlock -- The lock MUST be in the key. Without it a
                  drill-down and a report page produce the same hash for the
                  same words, so "Bogie failures in 2025" parsed on the report
                  page is served straight back to Air Conditioning's page with
                  Bogie still resolved -- the lock bypassed by a cache hit,
                  which is worse than no lock because it only fails the second
                  time anyone asks. */
               . '|' . json_encode($vocab['locked']));
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
            || $r['condition'] !== '' || $r['measure'] !== ''
            || $r['daypart'] !== '' || $r['weekday'] !== ''
            || $r['hour_from'] >= 0 || $r['hour_to'] >= 0
            || $r['place'] !== '' || $r['station'] > 0
            || count($r['segment']) || $r['bearing'] !== '');
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
/* @askfield -- The bar reads as one more filter unless it is given room. More
   vertical padding than the toolbar above it, so the eye registers a separate
   band rather than a continuation of the date and level controls. */
.ask-bar{ background:#F1EEE3; border:1px solid #E5DECC; border-top:none; padding:15px 16px 14px; }
.ask-row{ display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.ask-row label{ font-size:13px; font-weight:700; color:#00529B; white-space:nowrap; }
/* Sizing lives in the .ask-bar input.ask-in block below, NOT here, for exactly
   the reason the colour does: the toolbar theme styles input[type=text] at
   (0,1,1), which beats this bare class at (0,1,0) whatever the source order.
   A height set here is a height the theme can silently take back. Only the
   properties the theme does not touch stay on this rule. */
.ask-in{ flex:1 1 460px; min-width:280px; box-sizing:border-box;
         border-radius:5px; font-family:inherit; }
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
  text-indent:0 !important;
  /* @askfield -- 30px at 13px type looked like the date box beside it, which
     is why it did not read as somewhere to type a question. Forced for the
     same reason the colour is: the theme's input[type=text] rule outranks a
     bare class. */
  height:42px !important;
  font-size:15px !important;
  line-height:normal !important;
  padding:0 14px !important;
  border:1px solid #BFB7A2 !important;   /* darker than the bar, so the field has an edge */
}
.ask-bar input.ask-in::placeholder{ color:#8A92A6 !important; opacity:1 !important;
                                    -webkit-text-fill-color:#8A92A6 !important; }
.ask-bar input.ask-in::-webkit-input-placeholder{ color:#8A92A6 !important;
                                    -webkit-text-fill-color:#8A92A6 !important; }
.ask-bar input.ask-in:-ms-input-placeholder{ color:#8A92A6 !important; }
.ask-bar input.ask-in:focus{ color:#1A2238 !important; -webkit-text-fill-color:#1A2238 !important; }
.ask-bar input.ask-in:focus{ outline:none; border-color:#00529B !important;
                             box-shadow:0 0 0 3px rgba(0,82,155,.16) !important; }
.ask-go{ box-sizing:border-box; border:none; border-radius:5px; font-weight:700; cursor:pointer; }
/* Matched to the field's height, or the button floats against a taller box. */
.ask-bar input.ask-go{ background:#FDB813 !important; color:#3A2D00 !important;
                       -webkit-text-fill-color:#3A2D00 !important;
                       height:42px !important; font-size:13px !important;
                       padding:0 22px !important; }
.ask-go:hover{ background:#E5A50F; }
.ask-eg{ font-size:11.5px; color:#7A8194; }
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
    $h .= '<div class="ask-eg" style="margin-top:8px;">Try: ' . implode(' &middot; ', $links) . '</div>';

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
        $c .= iss_prompt_chip(iss_prompt_date($req['from']) . ' &ndash; '
                            . iss_prompt_date($req['to']), 'period', false);
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
    /* @timeaxis -- Band and clock range are separate chips even when both are
     * present, because the reader has to be able to see that an explicit
     * range overrode a named band and remove either one. */
    if ($req['daypart'] !== '' && isset($vocab['dayparts'][$req['daypart']]))
        $c .= iss_prompt_chip(htmlspecialchars($vocab['dayparts'][$req['daypart']]['label']),
                              'daypart', false);
    $hl = iss_prompt_hours_label($req);
    if ($hl !== '') $c .= iss_prompt_chip($hl, 'hours', false);
    $wl = iss_prompt_weekday_label($req);
    if ($wl !== '') $c .= iss_prompt_chip($wl, 'weekday', false);
    if ($req['place'] !== '' && isset($vocab['places'][$req['place']]))
        $c .= iss_prompt_chip(htmlspecialchars($vocab['places'][$req['place']]['label']),
                              'place', false);
    $sl = iss_prompt_station_label($req, $vocab);
    if ($sl !== '') $c .= iss_prompt_chip(htmlspecialchars($sl), 
                          (count($req['segment']) ? 'segment' : 'station'), false);
    if ($req['bearing'] !== '')
        $c .= iss_prompt_chip($req['bearing'] === 'NB' ? 'Northbound' : 'Southbound',
                              'bearing', false);
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

/* ---------------------------------------------------------------------------
 * 7a. TIME AXIS -- labels, and the one helper the PAGE calls
 *
 * The module resolves the question; it does not know the page's query. So the
 * page gets a ready-made SQL fragment and a note, and drops both in. Every
 * value spliced in here is an integer this file validated, or a constant --
 * there is no free text in the fragment, which is the same guarantee that
 * lets the rest of the request go straight into the page's filter variables.
 * -------------------------------------------------------------------------*/
function iss_prompt_hours_label($req) {
    $f = isset($req['hour_from']) ? (int)$req['hour_from'] : -1;
    $t = isset($req['hour_to'])   ? (int)$req['hour_to']   : -1;
    if ($f < 0 && $t < 0) return '';
    if ($f >= 0 && $t >= 0) {
        if ($f === $t) return sprintf('%02d:00 hour', $f);
        return sprintf('%02d:00&ndash;%02d:59', $f, $t);
    }
    if ($f >= 0) return sprintf('%02d:00 onwards', $f);
    return sprintf('up to %02d:59', $t);
}

function iss_prompt_weekday_label($req) {
    $w = isset($req['weekday']) ? (string)$req['weekday'] : '';
    if ($w === '') return '';
    if ($w === 'weekend') return 'Weekends';
    if ($w === 'weekday') return 'Weekdays';
    $names = array(1=>'Mondays',2=>'Tuesdays',3=>'Wednesdays',4=>'Thursdays',
                   5=>'Fridays',6=>'Saturdays',7=>'Sundays');
    $n = (int)$w;
    return isset($names[$n]) ? $names[$n] : '';
}

function iss_prompt_time_active($req) {
    return (isset($req['daypart']) && $req['daypart'] !== '')
        || (isset($req['weekday']) && $req['weekday'] !== '')
        || (isset($req['hour_from']) && $req['hour_from'] >= 0)
        || (isset($req['hour_to'])   && $req['hour_to']   >= 0);
}

/* $col is the page's own date column expression, e.g. "incident_date" or
 * "u.incident_date". Returns:
 *   sql    -- a fragment beginning with " and ", or '' when nothing applies
 *   note   -- a sentence to show the reader, or ''
 *   active -- whether anything was applied at all
 *
 * An explicit clock range BEATS a named band rather than intersecting with
 * it: intersecting "after 20:00" with an evening band that starts at 20:00
 * is a no-op, and with one that starts at 19:00 it silently narrows to
 * something the reader did not ask for. */
function iss_prompt_time_sql($req, $vocab, $col) {
    $out = array('sql'=>'', 'note'=>'', 'active'=>false);
    $w   = array();

    $from = isset($req['hour_from']) ? (int)$req['hour_from'] : -1;
    $to   = isset($req['hour_to'])   ? (int)$req['hour_to']   : -1;
    $usedBand = false;
    if ($from < 0 && $to < 0
        && isset($req['daypart']) && $req['daypart'] !== ''
        && isset($vocab['dayparts'][$req['daypart']])) {
        $dp   = $vocab['dayparts'][$req['daypart']];
        $from = (int)$dp['from'];
        $to   = (int)$dp['to'];
        $usedBand = true;
    }

    $hourFiltered = false;
    if ($from >= 0 || $to >= 0) {
        $hourFiltered = true;
        if ($from >= 0 && $to >= 0) {
            if ($from <= $to) {
                $w[] = 'hour(' . $col . ') between ' . $from . ' and ' . $to;
            } else {
                /* A band whose end is before its start wraps midnight -- an
                   evening band of 20..4 is two ranges, not an empty one. */
                $w[] = '(hour(' . $col . ') >= ' . $from . ' or hour(' . $col . ') <= ' . $to . ')';
            }
        } elseif ($from >= 0) {
            $w[] = 'hour(' . $col . ') >= ' . $from;
        } else {
            $w[] = 'hour(' . $col . ') <= ' . $to;
        }
    }

    /* MySQL dayofweek() is 1=Sunday..7=Saturday; the request stores ISO
       1=Monday..7=Sunday. (n % 7) + 1 converts, including Sunday 7 -> 1. */
    if (isset($req['weekday']) && $req['weekday'] !== '') {
        if ($req['weekday'] === 'weekend')     { $w[] = 'dayofweek(' . $col . ') in (1,7)'; }
        elseif ($req['weekday'] === 'weekday') { $w[] = 'dayofweek(' . $col . ') between 2 and 6'; }
        else {
            $n = (int)$req['weekday'];
            if ($n >= 1 && $n <= 7) { $w[] = 'dayofweek(' . $col . ') = ' . (($n % 7) + 1); }
        }
    }

    if (!count($w)) return $out;

    if ($hourFiltered && ISS_PROMPT_TIME_SENTINEL !== '') {
        $w[] = "date_format(" . $col . ", '%H:%i') <> '" . ISS_PROMPT_TIME_SENTINEL . "'";
        $out['note'] = 'Incidents recorded at exactly ' . ISS_PROMPT_TIME_SENTINEL
                     . ' are excluded from this time window. That value is the entry '
                     . 'form\'s default rather than a recorded time, so it cannot be '
                     . 'assigned to an hour.';
    }

    $out['sql']    = ' and ' . implode(' and ', $w);
    $out['active'] = true;
    $out['hour']   = $hourFiltered;   /* the sentinel only applies to hour terms */
    return $out;
}

/* @timefloor -- The time axis on a DRILL-DOWN, where the denominator is small.
 *
 * On a report page the 01:00 exclusion removes about 1,443 rows from roughly
 * 24,300 and what remains is still thousands, so a six-band breakdown is safe.
 * A page scoped to one car or one equipment type may hold a few hundred rows
 * across its whole history, and nothing says that unit's share of untouched
 * default times matches the fleet's -- a unit worked mostly by one shift can
 * sit far above 6%. Dividing eleven surviving rows into bands produces a
 * confident shape built on nothing.
 *
 * So the page counts first and asks. Below the floor the time narrowing is
 * DROPPED rather than applied, and the reader is told why: a question that
 * cannot be answered for this unit must say so, not return the unnarrowed
 * report as though the time term had been honoured.
 *
 * 30 is a working floor, not a derived one -- roughly the point below which
 * one band holding three rows and another holding none says more about who was
 * on shift than about the equipment. Raise it if the drill-downs turn out
 * thinner than expected. */
if (!defined('ISS_PROMPT_TIME_MIN_ROWS')) { define('ISS_PROMPT_TIME_MIN_ROWS', 30); }

/* @traindim -- Which unit a "which ..." question is asking about.
 *
 * A surface test on the wording, not a grammar change, and deliberately so:
 * the validator resolves FILTERS, and this resolves nothing -- it only tells
 * a page which of two rankings it already holds to hand to the band. Putting
 * it in the validator would mean every page carrying a train concept whether
 * or not it can answer for one.
 *
 * Returns 'train', 'car', or '' when the question names neither. A question
 * naming BOTH ("which car and which train") returns 'train' and the caller
 * says which one it ranked -- the band shows one ranking, and answering the
 * later-mentioned half silently is how the first screenshot came back looking
 * like it had ignored the question.
 */
function iss_prompt_rank_dimensions($text) {
    $t = ' ' . strtolower(preg_replace('/\s+/', ' ', (string)$text)) . ' ';
    $tr = preg_match('~\b(?:train|trains|trainset|consist|index(?:es|\s*no\.?|\s*number)?)\b~', $t, $mt, PREG_OFFSET_CAPTURE);
    $cr = preg_match('~\bcars?\b~', $t, $mc, PREG_OFFSET_CAPTURE);
    /* @primary -- Whichever unit is named FIRST is the one being ranked.
       "which car ... and which train" ranks cars; "which train ... and which
       car from that train" ranks trains. A fixed preference for one unit gets
       the second of those backwards, and the reader is handed a ranking of the
       wrong thing under their own question. */
    $primary = '';
    if ($tr && $cr) { $primary = ($mc[0][1] < $mt[0][1]) ? 'car' : 'train'; }
    else if ($cr)   { $primary = 'car'; }
    else if ($tr)   { $primary = 'train'; }
    return array(
        'train'   => (bool)$tr,
        'car'     => (bool)$cr,
        'primary' => $primary,
    );
}
/* @tied -- Is the second unit TIED to the first, or asked about separately?
 *
 * "which car had the worst ... and which train" names two units and wants two
 * rankings: the worst car, and separately the worst train.
 *
 * "which car had the worst ... and in which train FROM THAT CAR" names the
 * same two units but wants one ranking and then a breakdown OF IT: the worst
 * car, then which of THAT CAR's trains its failures fell in.
 *
 * Answering the first when the second was asked produces a figure the reader
 * never asked for, sitting next to one that belongs to a different population
 * -- which is how "Index 1 recorded the most with 9" came to appear beside
 * Car 52's 7.
 *
 * The test is an explicit back-reference. Without one the question is taken as
 * asking about the units independently, which is the safer default: a missed
 * tie shows one ranking too many, while a false tie silently withholds a
 * ranking that was asked for.
 */
function iss_prompt_rank_tied($text) {
    $t = ' ' . strtolower(preg_replace('/\s+/', ' ', (string)$text)) . ' ';
    return (bool)preg_match(
        '~\b(?:from|of|for|in|on|with)\s+(?:that|this|the\s+same|those)\b'
      . '|\b(?:that|this|the\s+same)\s+(?:car|train|unit|equipment|one)\b'
      . '|\bwas\s+it\s+in\b|\bdid\s+it\s+run\b|\bits\s+own\b~', $t);
}

/* Kept for callers that only need the primary. A question naming both returns
   'car', because that is the one such questions name first and it is the axis
   the drill-downs are built around; the second is carried by $d['also']. */
function iss_prompt_rank_dimension($text) {
    $d = iss_prompt_rank_dimensions($text);
    return $d['primary'];
}

function iss_prompt_time_viable($kept) {
    return ((int)$kept >= ISS_PROMPT_TIME_MIN_ROWS);
}

/* The exclusion note, carrying its own arithmetic.
 *
 * "Excludes incidents recorded at 01:00" reads as housekeeping when the
 * denominator is the fleet. On one unit the reader needs to know whether the
 * shape rests on most of the data or a third of it, so the counts go into the
 * sentence. $excluded is how many rows in the requested window sit at the
 * sentinel; $kept is how many survive it. */
function iss_prompt_time_note($excluded, $kept) {
    $excluded = (int)$excluded; $kept = (int)$kept;
    if ($excluded <= 0) { return ''; }
    $total = $excluded + $kept;
    return 'Excludes ' . $excluded . ' of the ' . $total . ' incident'
         . ($total === 1 ? '' : 's') . ' in this time window, recorded at exactly '
         . ISS_PROMPT_TIME_SENTINEL . '. That value is the entry form\'s default rather '
         . 'than a recorded time, so it cannot be placed in an hour. The figures below '
         . 'rest on the remaining ' . $kept . '.';
}

/* Said instead when the floor is not met and the narrowing was dropped. */
function iss_prompt_time_dropped($kept, $phrase) {
    $kept = (int)$kept;
    return 'The time-of-day narrowing was not applied: only ' . $kept . ' incident'
         . ($kept === 1 ? '' : 's') . ' on this page fall in ' . $phrase
         . ' once form-default times are set aside, which is too few to read a pattern '
         . 'from. The figures below cover the whole period instead.';
}

/* @placeaxis -- SQL for the place term. $dirCol is the page's expression for
 * incident_description.direction, e.g. "d.direction". Codes are compared
 * upper-cased and trimmed because the column is free-entry varchar and both
 * "i/r" and "IR" appear in the sibling location column.
 *
 * Every code spliced in comes from iss_prompt_default_places() or whatever
 * the page declared -- never from the question -- so there is no free text in
 * the fragment. The page is still responsible for HAVING joined
 * incident_description: the join is safe and needs no distinct guard (every
 * incident has exactly one row, verified Sep 2026), but a page that cannot
 * reach the table should pass places => array() so the term never resolves. */
function iss_prompt_place_sql($req, $vocab, $dirCol) {
    $out = array('sql'=>'', 'note'=>'', 'active'=>false);
    if (!isset($req['place']) || $req['place'] === '') return $out;
    if (!isset($vocab['places'][$req['place']])) return $out;

    $codes = $vocab['places'][$req['place']]['codes'];
    $q = array();
    foreach ($codes as $c) {
        if (!preg_match('/^[A-Za-z]{1,8}$/', $c)) continue;   /* belt and braces */
        $q[] = "'" . strtoupper($c) . "'";
    }
    if (!count($q)) return $out;

    $out['sql']    = ' and upper(trim(' . $dirCol . ')) in (' . implode(',', $q) . ')';
    $out['active'] = true;
    return $out;
}

/* @stationaxis -- Label for a station or a segment chip. */
function iss_prompt_station_label($req, $vocab) {
    if (isset($req['segment']) && count($req['segment']) === 2) {
        $a = (int)$req['segment'][0]; $b = (int)$req['segment'][1];
        $na = isset($vocab['stations'][$a]) ? $vocab['stations'][$a] : ('Station ' . $a);
        $nb = isset($vocab['stations'][$b]) ? $vocab['stations'][$b] : ('Station ' . $b);
        return 'Between ' . $na . ' and ' . $nb;
    }
    if (isset($req['station']) && (int)$req['station'] > 0) {
        $n = (int)$req['station'];
        return isset($vocab['stations'][$n])
             ? ($vocab['stations'][$n] . ' (station ' . $n . ')')
             : ('Station ' . $n);
    }
    return '';
}

/* @stationaxis -- SQL for station / segment / bearing.
 *
 * $dirCol and $locCol are the page's expressions for
 * incident_description.direction and .location.
 *
 * SCOPE: S, NB and SB only. Those three are the on-line codes -- S is a train
 * incident whose bearing was not recorded, NOT a platform (verified on
 * equipment mix, Sep 2026). Restricting to them keeps the handful of depot
 * and turnback rows that carry a stray location number out of a station
 * figure.
 *
 * A station is the station and nothing else. 4-5 and 5-6 are in-between and
 * are reached only by the segment term, per the operating distinction.
 *
 * A segment is stored unordered: "4-5" and "5-4" are the same track recorded
 * in the two directions of travel, so least()/greatest() matches both without
 * needing to enumerate spellings. Padding and internal spaces are handled by
 * the replace() and the cast, so "04 - 05" matches too. */
function iss_prompt_station_sql($req, $vocab, $dirCol, $locCol) {
    $out = array('sql'=>'', 'note'=>'', 'active'=>false);
    $w = array();
    $onLine = 'upper(trim(' . $dirCol . ")) in ('S','NB','SB')";
    $clean  = "replace(replace(" . $locCol . ", ' ', ''), char(9), '')";

    if (isset($req['segment']) && count($req['segment']) === 2) {
        $a = (int)$req['segment'][0]; $b = (int)$req['segment'][1];
        $lo = min($a, $b); $hi = max($a, $b);
        $p1 = "cast(substring_index(" . $clean . ", '-', 1) as unsigned)";
        $p2 = "cast(substring_index(" . $clean . ", '-', -1) as unsigned)";
        $w[] = $onLine;
        $w[] = $clean . " regexp '^[0-9]{1,2}-[0-9]{1,2}$'";
        $w[] = 'least(' . $p1 . ', ' . $p2 . ') = ' . $lo;
        $w[] = 'greatest(' . $p1 . ', ' . $p2 . ') = ' . $hi;
    } elseif (isset($req['station']) && (int)$req['station'] > 0) {
        $w[] = $onLine;
        $w[] = iss_prompt_int_col($clean) . ' = ' . (int)$req['station'];
    }

    if (isset($req['bearing']) && ($req['bearing'] === 'NB' || $req['bearing'] === 'SB')) {
        $w[] = 'upper(trim(' . $dirCol . ")) = '" . $req['bearing'] . "'";
        /* S rows have no bearing recorded, so a bearing filter silently drops
           them. 253 rows, but the reader should be told rather than left to
           wonder why the totals moved. */
        $out['note'] = 'Incidents recorded without a direction of travel are not '
                     . 'included when a bearing is specified.';
    }

    if (!count($w)) return $out;
    $out['sql']    = ' and ' . implode(' and ', $w);
    $out['active'] = true;
    return $out;
}

/* @placeaxis -- Read-time normalisation for the three free-entry numeric
 * columns in incident_description. Profiling (Sep 2026):
 *
 *     index_no   8,682 padded / 14,531 plain  -- 37%, splits trains 1-9 in two
 *     car_no     2,026 padded / 15,568 plain
 *     location      21 padded / 10,759 plain  -- 0.2%, cosmetic
 *
 * All three are text boxes, and the padded ids interleave with the plain ones
 * across the whole table, so this is ongoing keystroke variance rather than a
 * pre-standard era that ended -- normalise on read rather than cleaning rows.
 *
 * NULL, not 0, for anything non-numeric: index_no alone has 1,100 blanks and
 * 23 free-text values, and a bare cast would fold every one of them into a
 * train 0 that does not exist. */
function iss_prompt_int_col($col) {
    return "case when " . $col . " regexp '^[0-9]+$' then cast(" . $col . " as unsigned) else null end";
}

/* Prose form, for a finding sentence or a scope line. */
function iss_prompt_time_phrase($req, $vocab) {
    $bits = array();
    if (isset($req['daypart']) && $req['daypart'] !== ''
        && isset($vocab['dayparts'][$req['daypart']])
        && (!isset($req['hour_from']) || $req['hour_from'] < 0)
        && (!isset($req['hour_to'])   || $req['hour_to']   < 0)) {
        $bits[] = $vocab['dayparts'][$req['daypart']]['label'];
    }
    $h = iss_prompt_hours_label($req);
    if ($h !== '') { $bits[] = str_replace('&ndash;', '-', $h); }
    $w = iss_prompt_weekday_label($req);
    if ($w !== '') { $bits[] = $w; }
    return count($bits) ? implode(', ', $bits) : '';
}


/* One-line version for the printout, where the chips' controls are meaningless
 * but the scope of the question is exactly what a reader of the paper copy
 * needs to know. */
function iss_prompt_print_line($ask, $vocab) {
    if (trim($ask['asked']) === '') return '';
    $req = $ask['request'];
    $bits = array();
    if ($req['from'] !== '') $bits[] = iss_prompt_date($req['from']) . ' to ' . iss_prompt_date($req['to']);
    if ($req['level'] !== '') $bits[] = 'Level ' . $req['level'];
    if ($req['car'] > 0) $bits[] = 'Car ' . $req['car'];
    if (count($req['equipment'])) {
        $n = array();
        foreach ($req['equipment'] as $id) { if (isset($vocab['equipment'][$id])) $n[] = $vocab['equipment'][$id]; }
        if (count($n)) $bits[] = implode(', ', $n);
    }
    /* @timeaxis -- on paper the scope of the question is the whole point, and
     * a time window silently omitted here makes the figures unreproducible. */
    if ($req['daypart'] !== '' && isset($vocab['dayparts'][$req['daypart']]))
        $bits[] = $vocab['dayparts'][$req['daypart']]['label'];
    $hl = iss_prompt_hours_label($req); if ($hl !== '') $bits[] = $hl;
    $wl = iss_prompt_weekday_label($req); if ($wl !== '') $bits[] = $wl;
    if ($req['place'] !== '' && isset($vocab['places'][$req['place']]))
        $bits[] = $vocab['places'][$req['place']]['label'];
    $sl = iss_prompt_station_label($req, $vocab); if ($sl !== '') $bits[] = $sl;
    if ($req['bearing'] !== '') $bits[] = ($req['bearing'] === 'NB') ? 'Northbound' : 'Southbound';
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
/* @scan -- Lines, not a wall. The lead carries the answer; the rest support it
   and can be skipped by someone who already has what they came for. */
.ask-sum-line{ margin:0 0 7px; line-height:1.6; }
.ask-sum-line:last-child{ margin-bottom:0; }
.ask-sum-line.lead{ font-size:15px; }
.ask-sum-line:not(.lead){ font-size:13.5px; color:#3A4252; }
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
        /* resolution_date is a column but is NOT implemented, and `duration`
           is prose in a varchar that encoders fill in only when they judge
           there is one -- so a blank there is a judgement, not a zero. Both
           must be refused by name rather than aggregated. */
        'repair, downtime or resolution time'
            => '/\b(?:downtime|repair time|time to repair|mttr|resolution time|time resolved|resolved at|how long .{0,20}(?:down|out of service|to (?:fix|repair|resolve))|duration of (?:the )?(?:incident|failure|fault))\b/',
        /* Station, segment and bearing ARE held now. Platform and concourse
           are not: `direction` value S was tested against NB/SB on equipment
           mix and came back 94.9% rolling stock against 97.0% -- the same
           population, not a platform one. station_equipt is 3 rows on each
           side. So S is a train incident with no bearing recorded, and there
           is no platform-level record anywhere in this schema. */
        'platform or concourse level detail'
            => '/\b(?:platform|concourse|turnstile|ticket booth|on the platform)\b/',
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
        /* @lacks -- A page-supplied pattern may arrive without delimiters.
           The built-in entries above all carry /.../, so a page author copying
           the shape of a bare \b(?:...)\b gets "Delimiter must not be
           alphanumeric, backslash, or NUL", preg_match returns FALSE, and the
           guard is silently dead while emitting a warning on every parse --
           which is exactly what happened to equipment_history's resolution and
           duration entries. Wrapped here so a missing delimiter is a non-event
           rather than a dead guard nobody notices. */
        if ($re === '' || !is_string($re)) continue;
        $first = substr($re, 0, 1);
        if (ctype_alnum($first) || $first === '\\' || $first === "\0") { $re = '~' . $re . '~'; }
        if (@preg_match($re, $t)) $hits[] = $label;
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
        /* @scan -- One paragraph of five sentences cannot be scanned; the
           reader has to consume all of it to find the part they wanted.
           band_failures separates its blocks with "\n" -- the headline, the
           context, the breakdown, the second ranking -- and each becomes its
           own line here. The first gets .lead, because the answer to the
           question asked should be readable without reading the rest. */
        . '<div class="ask-band-body ask-band-sum">'
        . iss_prompt_sum_html($sum)
        . '</div>'
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

/* "A, B and C" -- the band builds several of these and they should read the
   same way in each. */
/* @tie -- How many rows share the top figure.
 *
 * A ranking sorted by count has a first row whether or not anything actually
 * came first. Reading rank[0] as "the most" turns a three-way tie into a
 * winner, and the runner-up line then prints the SAME number underneath it:
 * "DCI recorded 2 failures, more than any other equipment type. Next is Door
 * Failure with 2." Both halves are true of the data and the sentence is false.
 */
function iss_prompt_tie_count($rank) {
    if (!count($rank)) return 0;
    $top = (int)$rank[0]['total'];
    $n = 0;
    foreach ($rank as $r) { if ((int)$r['total'] === $top) $n++; else break; }
    return $n;
}

function iss_prompt_and_list($a) {
    $a = array_values($a);
    $n = count($a);
    if ($n === 0) return '';
    if ($n === 1) return (string)$a[0];
    return implode(', ', array_slice($a, 0, $n - 1)) . ' and ' . $a[$n - 1];
}

/* @scan -- "\n" is the block separator inside a summary. Branches that never
   set one still render exactly as before: a string with no newline produces a
   single paragraph. */
/* @datefmt -- "January 01, 2026", not "1 Jan 2026".
 *
 * The abbreviated day-first form is a British convention. Philippine official
 * writing uses the month name in full, day-then-comma, year -- which is also
 * the form on the reports these answers sit beside, so the panel now matches
 * the page rather than introducing a second style on the same screen.
 *
 * One function because the same date appears in the chips, the scope phrase
 * and the print line, and three copies of a format string is three chances to
 * change two of them. */
function iss_prompt_date($ymd) {
    $t = strtotime((string)$ymd);
    return $t ? date('F d, Y', $t) : (string)$ymd;
}

function iss_prompt_sum_html($sum) {
    $parts = explode("\n", (string)$sum);
    $out = ''; $first = true;
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p === '') continue;
        $out .= '<p class="ask-sum-line' . ($first ? ' lead' : '') . '">' . $p . '</p>';
        $first = false;
    }
    return $out;
}

function iss_prompt_band_failures($req, $vocab, $d, $L, &$sum, &$rows, &$notes) {
    $alsoFigs = null;   /* @also -- filled by the concentration branch, emitted last */
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
             . ($scope !== '' ? ' ' . $scope : '') . ', producing <b>' . $tot . '</b> ' . $unit . '.';
    } elseif ($tot === 0) {
        $sum = 'No ' . $unit . ' were recorded' . ($scope !== '' ? ' ' . $scope : '') . '.';
    } elseif (in_array('concentration', $req['focus']) && count($rank)) {
        /* @wording -- Both halves of this sentence used to leave the reader
           to supply the noun. "had the most, with 7 of 92 car-level failures"
           makes the unit modify the 92 rather than the 7, so the 7 is the most
           OF NOTHING NAMED; and "Next is Car 32 with 6" left 6 bare. Naming
           the unit against the leader and again against the runner-up costs a
           repetition and removes the guesswork -- which is the right trade on
           a line someone may read aloud off a printout. */
        /* @plain -- Every figure says what it counts and what it is a share
           OF. The previous form was "had the most car-level failures, with 7
           of the 93 on Static Converter": correct, but the reader had to work
           out that 7 belonged to one car while 93 belonged to the equipment,
           and "car-level failures" is internal vocabulary. Naming the row unit
           once ("more failures than any other car") does the work that the
           jargon was doing, in words anybody reads the same way. */
        $rowOne  = isset($d['row_noun'])   ? $d['row_noun']   : '';
        /* @compare -- "more than any other equipment type" is a claim about a
           field of competitors. With one row in the ranking there are none,
           and the sentence asserts a comparison that was never made -- which
           is how "ACU recorded 1 failure, more than any other equipment type"
           came to sit on a report filtered to ACU alone. */
        $only = (count($rank) === 1);
        $tied = iss_prompt_tie_count($rank);
        /* The headline first, then the context -- both branches share the
           context, so it is appended once below rather than written into each.
           Building it inside the branches is how the tie case lost its "Out of
           8 recorded failures" line entirely. */
        if ($tied > 1) {
            /* @tie -- No winner to name, so none is named. The count leads,
               because "three of them, with 2 each" is the finding. */
            $names = array();
            foreach (array_slice($rank, 0, min($tied, 4)) as $r) {
                $names[] = '<b>' . htmlspecialchars($r['label']) . '</b>';
            }
            $extra = $tied - count($names);
            $sum = '<b>' . $tied . '</b> '
                 . htmlspecialchars($rowOne !== '' ? $rowOne . 's' : 'of them')
                 . ' share the highest figure, with <b>' . (int)$rank[0]['total']
                 . '</b> failure' . ((int)$rank[0]['total'] === 1 ? '' : 's') . ' each: '
                 . ($extra > 0 ? implode(', ', $names) . ' and ' . $extra . ' more'
                               : iss_prompt_and_list($names)) . '.';
        }
        else {
            $sum = '<b>' . htmlspecialchars($rank[0]['label']) . '</b> recorded <b>'
                 . (int)$rank[0]['total'] . '</b> failure'
                 . ((int)$rank[0]['total'] === 1 ? '' : 's')
                 /* @compare -- no comparison claimed when there is nothing to
                    compare against. */
                 . ($only ? ''
                          : ($rowOne !== '' ? ', more than any other ' . htmlspecialchars($rowOne)
                                            : ', the most of any'))
                 . '.';
        }

        /* @scan -- The answer ends above. Everything from here is context, and
           context on its own line is context the reader can skip.
           @plain -- unit_plain is the reader's word for the unit; the internal
           string stays on the Figures tab and in the footnote. Singular when
           there is one: "Out of 1 recorded failures" is the kind of slip that
           makes a reader doubt the figures either side of it. */
        $sum .= "\n" . 'Out of ' . $tot . ' '
              . ($tot == 1
                 ? (isset($d['unit_one']) && $d['unit_one'] !== '' ? $d['unit_one'] : 'recorded failure')
                 : (isset($d['unit_plain']) && $d['unit_plain'] !== '' ? $d['unit_plain'] : $unit))
              . ($scope !== '' ? ' ' . $scope : '') . '.';

        /* @tie -- "Next is" must mean next LOWER. With a tie at the top,
           rank[1] holds the figure just reported as shared, and printing it
           reads as a contradiction. Skip past the tied block. */
        $nextAt = ($tied > 1) ? $tied : 1;
        if (isset($rank[$nextAt])) {
            $sum .= ' Next is ' . htmlspecialchars($rank[$nextAt]['label']) . ' with <b>'
                  . (int)$rank[$nextAt]['total'] . '</b>.';
        }
        /* @link -- What the leader is CONNECTED to. A car runs in a train, and
           over a period it runs in several, because train_availability is a
           dated composition history rather than a fixed roster. Naming the
           worst car without naming the trains it ran in leaves the reader to
           make that join by hand, which is the join they asked for.

           The map is keyed on the label the page put in `rows`, and it is read
           AFTER the band has chosen its own leader -- so the sentence can never
           describe a different row from the one the ranking named. A page that
           supplies nothing gets nothing.
             array('phrase' => 'ran in', 'noun' => 'train',
                   'plural' => 'trains',
                   'map'    => array('Car 52' => array('Index 14','Index 1')))
         */
        /* @tie -- A breakdown describes ONE leader. With the top shared there
           is no single one to break down, and picking whichever sorted first
           would present an arbitrary choice as the answer. The Figures tab
           still carries every row, so nothing is hidden -- only the sentence
           that would have implied a winner. */
        if ($tied > 1 && isset($d['link']) && is_array($d['link'])) {
            $notes[] = 'No breakdown is shown because the highest figure is shared: '
                     . 'there is no single leader to break down. The Figures tab lists '
                     . 'every row.';
        }
        if ($tied <= 1 && isset($d['link']) && is_array($d['link']) && count($rank)
            && isset($d['link']['map'][$rank[0]['label']])) {
            $lk = $d['link'];
            $to = $lk['map'][$rank[0]['label']];
            /* Entries may be plain labels, or label+count pairs. Counts are
               supplied when the question tied the two units together, because
               then the breakdown IS the answer and a bare list of names does
               not say which one carried most of it. */
            $withN = array();
            foreach ($to as $e) {
                if (is_array($e) && isset($e['label'])) {
                    $withN[] = array('label'=>$e['label'], 'total'=>(int)$e['n']);
                }
            }
            if (count($withN)) {
                usort($withN, 'iss_prompt_cmp_total');
                $lead = (int)$rank[0]['total'];
                $shown = array_slice($withN, 0, 4);
                $more  = count($withN) - count($shown);
                $bits  = array();
                foreach ($shown as $r) { $bits[] = $r['label'] . ' (' . $r['total'] . ')'; }
                /* @subject -- Normally the leader is the subject: "Those 5
                   occurred while Index 1 had failures on ...". But when the
                   link runs from equipment to trains, the thing that ran in
                   the trains is the CAR, not the equipment -- equipment does
                   not run anywhere. A page can therefore name its own subject
                   and the leader is left out of this clause. */
                $subj = (isset($lk['subject']) && $lk['subject'] !== '')
                      ? $lk['subject'] : $rank[0]['label'];
                $sum .= "\n" . 'Those ' . $lead . ' occurred while '
                      . htmlspecialchars($subj) . ' '
                      . (isset($lk['phrase']) ? $lk['phrase'] : 'was linked to') . ' <b>'
                      . count($withN) . '</b> different '
                      . htmlspecialchars(count($withN) === 1
                          ? (isset($lk['noun']) ? $lk['noun'] : 'unit')
                          : (isset($lk['plural']) ? $lk['plural'] : 'units'))
                      . ': ' . htmlspecialchars($more > 0 ? implode(', ', $bits)
                                                          : iss_prompt_and_list($bits))
                      . ($more > 0 ? ' and ' . $more . ' more' : '') . '.';
                /* Moved out of the sentence: it explains the notation rather
                   than answering anything, and in the flow it read as another
                   finding. */
                $notes[] = 'In that list, the bracketed figure is how many of the '
                         . $lead . ' fell in each.';
            }
            else if (count($to)) {
                $nn = count($to);
                $word = ($nn === 1)
                      ? (isset($lk['noun']) ? $lk['noun'] : 'unit')
                      : (isset($lk['plural']) ? $lk['plural'] : (isset($lk['noun']) ? $lk['noun'].'s' : 'units'));
                $show = array_slice($to, 0, 4);
                $more = $nn - count($show);
                /* @plain -- "Car 52 ran in 5 trains" reads as impossible to
                   anybody who knows a car sits in one formation. The period
                   is what makes it true, so the period is said. */
                $sum .= "\n" . 'Over this period ' . htmlspecialchars($rank[0]['label']) . ' '
                      . (isset($lk['phrase']) ? $lk['phrase'] : 'is linked to') . ' <b>'
                      . $nn . '</b> different ' . htmlspecialchars($word) . ': '
                      /* Plain commas when the list is truncated: an "and"
                         before the last shown item followed by "and 2 more"
                         reads as two conjunctions in a row. */
                      . htmlspecialchars($more > 0 ? implode(', ', $show)
                                                   : iss_prompt_and_list($show))
                      . ($more > 0 ? ' and ' . $more . ' more' : '') . '.';
            }
        }

        /* @also -- A second ranking, for a question that names two units:
           "which car had the worst ... and which train". Answering only one of
           them and noting which was ranked is still half an answer to a
           question asked in full. $d['also'] is optional, so a page that
           supplies nothing behaves exactly as before.
             array('noun' => 'train', 'rows' => array(label,total ...)) */
        if (isset($d['also']) && is_array($d['also'])
            && isset($d['also']['rows']) && count($d['also']['rows'])) {
            $a2 = array();
            foreach ($d['also']['rows'] as $r) {
                if (isset($r['total']) && (int)$r['total'] > 0) {
                    $a2[] = array('label'=>$r['label'], 'total'=>(int)$r['total']);
                }
            }
            usort($a2, 'iss_prompt_cmp_total');
            if (count($a2)) {
                $noun = isset($d['also']['noun']) ? $d['also']['noun'] : 'unit';
                /* @plain -- This is the sentence that was doing the damage.
                   "By train, Index 1 had the most with 9" sat beside "Car 52
                   ... 7" in the same paragraph, and 9 is larger than 7 for a
                   train that Car 52 itself ran in -- so the arithmetic looked
                   wrong. It is not: the 9 counts every car in that train and
                   the 7 counts one car. Two populations, one paragraph, and
                   nothing said which was which.
                   The basis is now stated in the sentence rather than left to
                   the reader, and the page supplies the wording because only
                   the page knows what its partner unit contains. */
                $basis = isset($d['also']['basis']) ? $d['also']['basis'] : '';
                $t2 = iss_prompt_tie_count($a2);
                if ($t2 > 1) {
                    $nm2 = array();
                    foreach (array_slice($a2, 0, min($t2, 4)) as $r) {
                        $nm2[] = '<b>' . htmlspecialchars($r['label']) . '</b>';
                    }
                    $ex2 = $t2 - count($nm2);
                    $sum .= "\n" . 'Counted by ' . htmlspecialchars($noun) . ' instead, <b>'
                          . $t2 . '</b> share the highest figure with <b>'
                          . (int)$a2[0]['total'] . '</b> each: '
                          . ($ex2 > 0 ? implode(', ', $nm2) . ' and ' . $ex2 . ' more'
                                      : iss_prompt_and_list($nm2)) . '.';
                }
                else {
                $sum .= "\n" . 'Counted by ' . htmlspecialchars($noun) . ' instead, <b>'
                      . htmlspecialchars($a2[0]['label']) . '</b> recorded the most with <b>'
                      . (int)$a2[0]['total'] . '</b>'
                      /* Bracketed. Run on unpunctuated after the figure it
                         qualifies, "with 9 counting all of its cars together"
                         reads as one clause and the qualifier is lost. */
                      . ($basis !== '' ? ' (' . htmlspecialchars($basis) . ')' : '');
                if (count($a2) > 1) {
                    $sum .= ', then ' . htmlspecialchars($a2[1]['label'])
                          . ' with ' . (int)$a2[1]['total'];
                }
                $sum .= '.';
                }
                /* Held, not appended. The main figures are assembled AFTER
                   this branch returns, so appending here puts the second
                   ranking above the totals it is secondary to. */
                $alsoFigs = array('noun' => $noun, 'rank' => $a2);
            }
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

    /* @also -- Last, under its own heading, so the reader sees the totals, then
       the primary ranking, then the second one -- the order the sentence above
       states them in. */
    if ($alsoFigs !== null) {
        $rows[] = array('<b>By ' . htmlspecialchars(ucfirst($alsoFigs['noun'])) . '</b>', '');
        $n2 = 0;
        foreach ($alsoFigs['rank'] as $r) {
            if ($n2++ >= 6) break;
            $rows[] = array(htmlspecialchars($r['label']), (int)$r['total']);
        }
        if (count($alsoFigs['rank']) > 6) {
            $rows[] = array('&nbsp;&nbsp;and ' . (count($alsoFigs['rank']) - 6) . ' more', '');
        }
    }
    if ($L !== null && $L['rostered'] > 0) {
        $rows[] = array('Loops lost (per incident)', iss_prompt_n($L['rostered']));
    }
    /* @plain -- "Failures counted per car; loops per incident" named a measure
       this answer may not contain at all: loops only appear when a page
       supplies them. The second clause is now emitted only when there is
       something for it to describe, and the first is said in full rather than
       as shorthand. */
    $notes[] = 'One incident that affects three cars counts as three failures.'
             . (($L !== null && $L['rostered'] > 0)
                ? ' Loops lost are counted once per incident, not per car.' : '');
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
        $p[] = 'between ' . iss_prompt_date($req['from'])
             . ' and ' . iss_prompt_date($req['to']);
    return implode(', ', $p);
}

function iss_prompt_cmp_total($a, $b) {
    /* @tie -- Equal totals are broken on the label, NOT left as 0.
       usort is not stable before PHP 8.0, and the live server runs 7.4, so
       returning 0 lets tied rows come out in whatever order the sort leaves
       them -- which can differ between two loads of the same page. With ties
       now reported as ties, that matters twice: the order of names in "3
       equipment types share the highest figure: A, B and C" would shuffle,
       and when a tie is longer than four and gets cut to "and 2 more", WHICH
       four are named would change too. Natural order, so Car 9 sorts before
       Car 12 rather than after it. */
    if ($a['total'] == $b['total']) {
        return strnatcasecmp((string)$a['label'], (string)$b['label']);
    }
    return ($a['total'] < $b['total']) ? 1 : -1;
}