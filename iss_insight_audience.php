<?php
/* ============================================================================
 * iss_insight_audience.php  --  two readings of the same findings
 * ----------------------------------------------------------------------------
 * The findings are computed once. This file renders them twice.
 *
 * The important constraint: the executive view must be SHORTER and PLAINER,
 * never VAGUER-AND-MORE-CONFIDENT. Stripping "z=4.6" is fine. Stripping the
 * coverage gap, the suggested-category caveat, or the word "worth checking"
 * is not -- those are precisely what stops a number being over-read by
 * someone who will not see the table. So the caveats survive into the
 * executive block verbatim in meaning, and hedges become plain-English
 * hedges rather than disappearing.
 *
 * Every plain sentence is rebuilt from the finding's own `facts` array, not
 * by string-munging its technical text. That keeps all layman wording in one
 * reviewable place and means the analysis files never change when the
 * phrasing is tuned.
 * ==========================================================================*/

if (defined('ISS_INSIGHT_AUDIENCE')) { return; }
define('ISS_INSIGHT_AUDIENCE', 1);
/* Bump on every change a PAGE depends on. Stations are updated by hand and
   drift out of step, so the version has to be visible from view-source rather
   than only discoverable by a page dying half-rendered. */
define('ISS_INSIGHT_AUDIENCE_V', '6');

/* Guarded on each own name -- a station may receive this file before it
   receives the new iss_insight.php. See the same block in the analytics. */
if (!function_exists('iss_ins_relpct')) {
    function iss_ins_relpct($ratio) {
        $r = (float)$ratio;
        if ($r <= 0) { return null; }
        return (int)round(($r - 1) * 100);
    }
}
if (!function_exists('iss_ins_relword')) {
    function iss_ins_relword($ratio) {
        $p = iss_ins_relpct($ratio);
        if ($p === null) { return ''; }
        if ($p === 0)    { return 'level with expected'; }
        return abs($p) . '% ' . ($p > 0 ? 'above' : 'below');
    }
}

/* Statistical confidence, said the way a person says it. Deliberately
   conservative: a z of 4 is not "certain", it is "clearly". */
function iss_aud_conf($z) {
    $z = abs((float)$z);
    if ($z >= 4.0) { return 'clearly'; }
    if ($z >= 3.0) { return 'strongly'; }
    if ($z >= 2.0) { return 'probably'; }
    return 'possibly';
}
function iss_aud_n($v) { return number_format((float)$v, (floor($v) == $v ? 0 : 1)); }
function iss_aud_list($a, $max) {
    $a = array_slice(array_values($a), 0, $max);
    $n = count($a);
    if ($n === 0) { return ''; }
    if ($n === 1) { return $a[0]; }
    return implode(', ', array_slice($a, 0, $n - 1)) . ' and ' . $a[$n - 1];
}

/* --------------------------------------------------------------------------
 * The translator. One case per finding kind, reading `facts`.
 * Returns '' for findings that should not reach an executive at all.
 * ------------------------------------------------------------------------*/
/* $opts is unused today. It stays in the signature because the two callers
   already pass it and the next register-specific tweak will want it; removing
   and re-adding a parameter across hand-copied stations is the churn worth
   avoiding. */
function iss_ins_plain($f, $c, $opts = array()) {
    $F    = isset($f['facts']) && is_array($f['facts']) ? $f['facts'] : array();
    $unit = $c['report']['unit'];
    $col  = iss_ins_colword($c, 1);
    $rowp = iss_ins_rowword($c, 2);
    $rows = iss_ins_rowword($c, 1);

    switch ($f['kind']) {

    case 'volume':
        /* No "averaging about N a month" when there is only one month -- the
           average and the total are the same number, and printing both makes
           a thin window look like it has more in it than it does. */
        if ($F['buckets_covered'] <= 1) {
            return sprintf('This window holds %s %s.',
                   number_format($F['total']), iss_ins_unit($c, $F['total']));
        }
        return sprintf('This window holds %s %s across %d %s, averaging about %s a %s.',
               number_format($F['total']), iss_ins_unit($c, $F['total']),
               $F['buckets_covered'], iss_ins_colword($c, $F['buckets_covered']),
               iss_aud_n($F['mean_per_bucket']), $col);

    case 'vs_prior':
        $d = $F['change_pct'];
        if (empty($F['totals_comparable'])) {
            /* Never let this one collapse into a bare percentage. The whole
               point is that the two totals are NOT comparable, and an
               executive who is given "6.7% up on last year" will quote it. */
            return sprintf('Per %s the rate is %s%% %s than %s -- but only %d %s are recorded here against %d there, so the two yearly totals cannot be compared directly.',
                   $col, abs($d), ($d > 0 ? 'higher' : ($d < 0 ? 'lower' : 'level')),
                   $F['previous_label'], $F['buckets_current'],
                   iss_ins_colword($c, $F['buckets_current']), $F['buckets_previous']);
        }
        return sprintf('That is %s%% %s %s.', abs($d),
               ($d > 0 ? 'more than' : ($d < 0 ? 'fewer than' : 'level with')),
               $F['previous_label']);

    case 'trend':
        return sprintf('The rate %s as the period went on: about %s a %s early on against %s later, %s%% either way.',
               ($F['change_pct'] > 0 ? 'got worse' : 'improved'),
               iss_aud_n($F['first_half_avg']), $col, iss_aud_n($F['second_half_avg']),
               abs($F['change_pct']));

    case 'changepoint':
        return sprintf('This was a step change rather than normal ups and downs. The level moved %s in %s and stayed there -- roughly %s a %s before, %s a %s after, a change of %s%%. There is only about a %s%% chance of a shift this clean turning up in data with no real change in it.',
               ($F['change_pct'] > 0 ? 'up' : 'down'), $F['at'],
               iss_aud_n($F['mean_before']), $col, iss_aud_n($F['mean_after']), $col,
               abs($F['change_pct']), max(1, 100 - (int)$F['confidence_pct']));

    case 'peak':
        /* The score line names the heaviest period and its count, so when it
           is present this keeps only what it ADDS -- whether the peak is
           unusual, or just what that month always does. That judgement is on
           no tile and in no other sentence, so it must not be dropped
           wholesale; only the restatement in front of it goes. */
        $seasonal = (isset($F['seasonal_index']) && $F['seasonal_index'] >= 1.25);
        $sp = isset($F['seasonal_pct']) ? $F['seasonal_pct']
            : (isset($F['seasonal_index']) ? iss_ins_relpct($F['seasonal_index']) : null);
        if (!empty($opts['have_score'])) {
            if ($seasonal) {
                return sprintf('That peak is not unusual though: this %s always runs about %s%% above average.',
                       $col, $sp);
            }
            if (!empty($F['is_outlier'])) {
                return 'That peak is well above the usual level for this period.';
            }
            return '';
        }
        $s = sprintf('The heaviest %s was %s, with %s.', $col, $F['bucket'], $F['value']);
        if ($seasonal) {
            $s .= sprintf(' That month always runs about %s%% above average though, so this is the usual seasonal pattern rather than something new.', $sp);
        } elseif (!empty($F['is_outlier'])) {
            $s .= ' That is well above the usual level for this period.';
        }
        return $s;

    case 'concentration':
        /* "60% sits with just 27 of the 70 cars" is not concentration -- on a
           wide report the 60% cut lands most of the way down the list and the
           sentence says the opposite of what it means. Kept only where the top
           really is a short list; the same threshold the finding itself uses
           to decide between watch and info. */
        if ($F['n_rows_to_60pct'] > max(2, $F['n_rows_total'] * 0.2)) { return ''; }
        return sprintf('The problem is not spread thin: %s%% of everything sits with just %d of the %d %s -- %s.',
               $F['share_pct'], $F['n_rows_to_60pct'], $F['n_rows_total'], $rowp,
               iss_aud_list($F['top'], 3));

    case 'concentrated_on_units':
        /* Use the CROSS-TAB's row word, not the report's. On the equipment
           report the rows of the table are equipment but the rows of the
           cross-tab are cars, and borrowing the wrong one produced "no single
           equipment stands out" about a per-car breakdown. */
        if (!empty($F['row_label'])) {
            $rows = strtolower($F['row_label']);
            $rowp = strtolower(iss_ins_plural($F['row_label'], 2));
        }
        $who = array();
        foreach (array_slice($F['hot'], 0, 3) as $h) {
            /* Its share of this equipment's failures, against the share its
               own activity predicts. Both are bounded, so neither runs to
               four digits on a report with seventy rows. The fallback keeps
               the sentence readable against an older analytics file that
               emits only the multiplier. */
            $who[] = isset($h['share_pct'])
                   ? ($h['row'] . ' (' . $h['share_pct'] . '% of them, against '
                      . $h['expect_share_pct'] . '% expected)')
                   : ($h['row'] . ' (' . $h['obs'] . ' against ' . $h['exp'] . ' expected)');
        }
        $nhot = min(3, count($F['hot']));
        return sprintf('%s is not a fleet-wide problem. It is %s concentrated on %s. That points at %s in particular rather than at the equipment itself, so the fix is likely to be on %s.',
               $F['column'], iss_aud_conf($F['hot'][0]['z']), iss_aud_list($who, 3),
               iss_aud_list(array_map('iss_aud_rowname', $F['hot']), 3),
               ($nhot === 1 ? 'that ' . $rows : 'those ' . $rowp));

    case 'fleet_wide':
        if (!empty($F['row_label'])) {
            $rows = strtolower($F['row_label']);
            $rowp = strtolower(iss_ins_plural($F['row_label'], 2));
        }
        return sprintf('%s fails evenly right across the fleet -- no single %s stands out. That points at the equipment type itself rather than at particular %s, so unit-by-unit repairs are unlikely to shift it.',
               $F['column'], $rows, $rowp);

    case 'spike':
        /* "against a usual 0" reads as a typo. Say it in words instead. */
        $s = ((float)$F['baseline'] <= 0)
           ? sprintf('%s had %s in %s and none in a typical %s.',
             $F['row'], $F['value'], $F['bucket'], $col)
           : sprintf('%s jumped to %s in %s, against a usual %s.',
             $F['row'], $F['value'], $F['bucket'], iss_aud_n($F['baseline']));
        if (isset($F['seasonal_index'])) { $s .= ' Some of that is seasonal, but not all of it.'; }
        return $s;

    case 'emerging':
        $l = array();
        foreach ($F as $e) { if (is_array($e) && isset($e['row'])) { $l[] = $e['row']; } }
        return 'Getting worse over the period: ' . iss_aud_list($l, 3) . '.';

    case 'receding':
        $l = array();
        foreach ($F as $e) { if (is_array($e) && isset($e['row'])) { $l[] = $e['row']; } }
        return 'Improving over the period: ' . iss_aud_list($l, 3) . '.';

    case 'rotation':
        if ((int)$F['overlap'] >= 4) {
            return sprintf('The same %s top the list as in %s. Whatever was done in between has not changed the ranking -- worth asking what would.',
                   $rowp, $F['previous_label']);
        }
        return sprintf('The worst offenders have turned over since %s. New to the top of the list: %s.',
               $F['previous_label'], iss_aud_list($F['new_entrants'], 3));

    case 'recurrence':
        if (empty($F['flagged'])) { return ''; }
        $l = array();
        foreach (array_slice($F['flagged'], 0, 2) as $w) { $l[] = $w['pair']; }
        return sprintf('Some repairs are not holding. %s %s failing again far sooner than %s own history would explain, which usually means the first fix did not address the cause.',
               iss_aud_list($l, 2), (count($l) === 1 ? 'is' : 'are'),
               (count($l) === 1 ? 'its' : 'their'));

    case 'time_of_day':
        $pk = $F['peaks'][0];
        $op = isset($pk['over_pct']) ? $pk['over_pct'] : iss_ins_relpct($pk['ratio']);
        return sprintf('These cluster at particular times of day rather than spreading across the service day -- heaviest around %02d:00, running %s%% above what an even spread would give. Whether that is when the failures happen or when they get written up is worth checking against the shift pattern.',
               $pk['hour'], $op);

    case 'seasonality':
        return sprintf('There is a repeating yearly pattern in this data, visible across %d years. Any single %s should be compared with the same %s in other years, not with the annual average.',
               $F['years'], $col, $col);

    case 'co_movement':
        $p = $F['pairs'][0];
        return sprintf('%s and %s rise and fall together. That may mean a shared cause, or simply that they get reported together -- worth a look, but it is a lead rather than a finding.',
               $p['a'], $p['b']);

    case 'severity_shift':
        return sprintf('The severe cases moved %s over the period, from about %s a %s to %s.',
               ($F['second_half_avg'] > $F['first_half_avg'] ? 'up' : 'down'),
               iss_aud_n($F['first_half_avg']), $col, iss_aud_n($F['second_half_avg']));

    /* ---- caveats. These survive into the executive view intact. --------- */
    case 'coverage':
        $n = count($F['uncovered']);
        return sprintf('Important: %d %s in this range have no records at all. They are blank, not quiet -- they must not be read as an improvement, and they are left out of every figure above.',
               $n, iss_ins_colword($c, $n));

    case 'quality':
        $b = array();
        if (!empty($F['suggested_rows']))     { $b[] = $F['suggested_rows'] . ' entries were categorised by the system\'s best guess rather than by a person'; }
        if (!empty($F['uncategorized_rows'])) { $b[] = $F['uncategorized_rows'] . ' entries have no category at all'; }
        if (!count($b)) { return ''; }
        return 'A note on the data: ' . iss_aud_list($b, 2) . '.';

    case 'crosstab_mismatch':
        return 'Two of the underlying counts disagree, so the breakdown by unit has been left out of this summary rather than shown with figures that would not add up.';

    /* Deliberately silent for an executive: interesting to an engineer,
       noise in a summary. */
    case 'severity_mix':
    case 'dormant':
    default:
        return '';
    }
}
function iss_aud_rowname($h) { return $h['row']; }

/* --------------------------------------------------------------------------
 * Three equipment types concentrated on a few cars produced three bullets to
 * the same template, differing only in the names -- the reader meets the same
 * sentence three times and stops reading it. One bullet, every name kept, and
 * the shared conclusion said once at the end where it belongs.
 * ------------------------------------------------------------------------*/
function iss_aud_merge_concentrated($items, $c) {
    /* Strongest first. The analytics emits these in column-total order, which
       on a wide cross-tab put two marginal ones ahead of the flagrant one --
       the reader met the weakest evidence first and the sentence read as
       hedging. `hot` is already sorted by strength within each finding. */
    usort($items, 'iss_aud_cmp_hotz');
    $names = array(); $bits = array(); $rowp = iss_ins_rowword($c, 2); $first = true;
    foreach ($items as $f) {
        $F = isset($f['facts']) && is_array($f['facts']) ? $f['facts'] : array();
        if (empty($F['hot']) || empty($F['column'])) { continue; }
        if (!empty($F['row_label'])) { $rowp = strtolower(iss_ins_plural($F['row_label'], 2)); }
        $names[] = $F['column'];
        $who = array();
        foreach (array_slice($F['hot'], 0, 3) as $h) { $who[] = $h['row']; }
        $b = $F['column'] . ' on ' . iss_aud_list($who, 3);
        /* The combined share is carried for the worst one only. Repeating it
           for each is how three bullets became three paragraphs. */
        if ($first && isset($F['share_pct'])) {
            $b .= sprintf(' (together %s%% of them)', $F['share_pct']);
            $first = false;
        }
        $bits[] = $b;
    }
    if (count($bits) < 2) { return ''; }
    return sprintf('%s are each landing on a handful of %s rather than across the fleet: %s. The fix for these is on the %s, not the equipment type.',
           iss_aud_list($names, 4), $rowp, implode('; ', $bits), $rowp);
}
function iss_aud_cmp_hotz($a, $b) {
    $x = isset($a['facts']['hot'][0]['z']) ? $a['facts']['hot'][0]['z'] : 0;
    $y = isset($b['facts']['hot'][0]['z']) ? $b['facts']['hot'][0]['z'] : 0;
    return ($x == $y) ? 0 : (($x < $y) ? 1 : -1);
}

/* --------------------------------------------------------------------------
 * Executive block: bottom line, then at most five points, then the caveats.
 * ------------------------------------------------------------------------*/
function iss_insight_executive($c, $F) {
    $rank = array('alert' => 0, 'watch' => 1, 'info' => 2);
    $head = array(); $body = array(); $cav = array();

    /* The score leads. Guarded because a station may hold an older
       iss_insight.php, in which case the block simply reads as it did before
       rather than dying. */
    $score = function_exists('iss_insight_scoreboard')
             ? iss_insight_scoreboard($c, $F) : '';

    /* Collected first so the loop below knows whether to skip them. */
    $conc = array();
    foreach ($F as $f) { if ($f['kind'] === 'concentrated_on_units') { $conc[] = $f; } }
    $merged = (count($conc) >= 2) ? iss_aud_merge_concentrated($conc, $c) : '';

    foreach ($F as $f) {
        if ($merged !== '' && $f['kind'] === 'concentrated_on_units') { continue; }
        $p = iss_ins_plain($f, $c, array('have_score' => ($score !== '')));
        if ($p === '') { continue; }
        if ($f['severity'] === 'caveat') { $cav[] = $p; continue; }
        if (in_array($f['kind'], array('volume', 'vs_prior'), true)) {
            /* The score already states the total, and the change too WHERE
               THE TWO PERIODS ARE COMPARABLE. Saying it again in prose is
               padding. The non-comparable vs_prior sentence is never dropped:
               its entire content is the warning that the totals cannot be set
               against each other, and the score deliberately leaves that
               figure out for exactly that reason. */
            if ($score !== '') {
                if ($f['kind'] === 'volume') { continue; }
                if ($f['kind'] === 'vs_prior' && !empty($f['facts']['totals_comparable'])) { continue; }
            }
            $head[] = $p; continue;
        }
        $body[] = array('sev' => isset($rank[$f['severity']]) ? $rank[$f['severity']] : 3,
                        'kind' => $f['kind'], 'text' => $p);
    }
    if ($merged !== '') {
        $body[] = array('sev' => 0, 'kind' => 'concentrated_on_units', 'text' => $merged);
    }
    usort($body, 'iss_aud_cmp');

    /* The bottom line names the single most consequential thing, in the order
       an operations reader would care: something broke, something shifted,
       something is not being fixed, something is concentrated. */
    $bottom = '';
    foreach (array('recurrence', 'changepoint', 'concentrated_on_units', 'rotation',
                   'spike', 'trend', 'fleet_wide', 'concentration') as $k) {
        foreach ($body as $b) { if ($b['kind'] === $k) { $bottom = $b['text']; break 2; } }
    }

    /* Whatever became the bottom line does not also lead the bullet list --
       a summary that opens by saying the same thing twice reads as padding.
       And no more than two of any one kind: on the by-car report five of the
       five points were single-car spikes to the same template, which is how a
       summary comes to look padded even though every line in it is true. The
       full set stays in the technical view; this is the executive read. */
    $keep = array(); $perKind = array();
    foreach ($body as $b) {
        if ($b['text'] === $bottom) { continue; }
        $k = $b['kind'];
        if (!isset($perKind[$k])) { $perKind[$k] = 0; }
        if ($perKind[$k] >= 2) { continue; }
        $perKind[$k]++;
        $keep[] = $b['text'];
        if (count($keep) >= 5) { break; }
    }

    return array('score' => $score, 'bottom' => $bottom, 'context' => $head,
                 'points' => $keep, 'caveats' => $cav);
}

/* The score as markup, above both registers.
 *
 * The figures are bolded so the block can be scanned rather than read, which
 * is what it is for -- at a flat weight and caption size it read as a footnote
 * to the bottom line instead of the summary of the report.
 *
 * Done by splitting the RAW text on number tokens and escaping each fragment
 * separately. Running the regex over already-escaped text would match the
 * digits inside entities -- htmlspecialchars with ENT_QUOTES turns an
 * apostrophe into &#039; -- and bolding "039" would tear the entity in half.
 */
function iss_insight_score_html($score) {
    if (!is_string($score) || $score === '') { return ''; }
    /* A comma counts as a thousands separator only when three digits follow
       it, or "(34, 5.6%)" bolds the comma along with the 34. */
    $parts = preg_split('/(\d+(?:,\d{3})*(?:\.\d+)?%?)/', $score, -1, PREG_SPLIT_DELIM_CAPTURE);
    if (!is_array($parts)) { return '<p class="ins-score">' . iss_ins_esc($score) . '</p>'; }
    $h = '';
    foreach ($parts as $i => $p) {
        if ($p === '') { continue; }
        $h .= ($i % 2) ? '<b>' . iss_ins_esc($p) . '</b>' : iss_ins_esc($p);
    }
    return '<p class="ins-score">' . $h . '</p>';
}
function iss_aud_cmp($a, $b) { return ($a['sev'] == $b['sev']) ? 0 : (($a['sev'] < $b['sev']) ? -1 : 1); }

/* --------------------------------------------------------------------------
 * Both views, one block, client-side toggle. No round trip: both are already
 * computed, so switching is instant and works with no provider configured.
 * ------------------------------------------------------------------------*/
function iss_insight_html_dual($c, $F, $tech, $showAudit = false) {
    $e   = 'iss_ins_esc';
    /* Deterministic executive block by default. If a model produced one it is
       used instead -- but only after passing the same numeric audit as the
       technical text, and the computed caveats are appended either way so a
       model that quietly dropped one cannot cost the reader the warning. */
    $exe = iss_insight_executive($c, $F);
    if (!empty($tech['executive']) && is_array($tech['executive'])
        && empty($tech['audit'])) {
        $m = $tech['executive'];
        $mine = $exe['caveats'];
        $exe = array(
            /* The score is computed, never model-written, so it survives the
               override untouched -- there is nothing here for a model to get
               wrong and nothing for the audit to catch. */
            'score'   => $exe['score'],
            'bottom'  => isset($m['bottom_line']) ? $m['bottom_line'] : $exe['bottom'],
            'context' => isset($m['summary']) ? array($m['summary']) : $exe['context'],
            'points'  => (!empty($m['points']) && is_array($m['points']))
                         ? array_slice($m['points'], 0, 5) : $exe['points'],
            'caveats' => $mine,
        );
    }
    $id  = 'ins' . substr(md5($c['report']['id'] . count($F)), 0, 6);

    $h  = '<section class="ins-block" id="' . $id . '" data-view="tech">';
    $h .= '<div class="ins-bar">';
    $h .= '<span class="ins-title">Analysis and interpretation</span>';
    $h .= '<span class="ins-switch" role="group" aria-label="Choose reading level">';
    $h .= '<button type="button" class="ins-tab" data-v="exec" aria-pressed="false">Executive summary</button>';
    $h .= '<button type="button" class="ins-tab is-on" data-v="tech" aria-pressed="true">Technical detail</button>';
    $h .= '</span></div>';

    $score = iss_insight_score_html($exe['score']);

    /* ---- executive ---- */
    $h .= '<div class="ins-view ins-exec">';
    $h .= $score;
    if ($exe['bottom'] !== '') {
        $h .= '<p class="ins-head">' . $e($exe['bottom']) . '</p>';
    }
    if (count($exe['context'])) {
        $h .= '<p class="ins-summary">' . $e(implode(' ', $exe['context'])) . '</p>';
    }
    if (count($exe['points'])) {
        $h .= '<ul class="ins-findings">';
        foreach ($exe['points'] as $p) { $h .= '<li>' . $e($p) . '</li>'; }
        $h .= '</ul>';
    }
    foreach ($exe['caveats'] as $cv) { $h .= '<p class="ins-caveat">' . $e($cv) . '</p>'; }
    $h .= '</div>';

    /* ---- technical: the existing block, with the same score above it ----
       An engineer wants the totals as much as an executive does, and putting
       it in both registers means the printout carries it whichever way the
       toggle was left. */
    $h .= '<div class="ins-view ins-tech">';
    $h .= $score;
    $inner = iss_insight_html($tech, $showAudit);
    /* strip the outer section and its heading; this wrapper supplies both */
    $inner = preg_replace('#^<section class="ins-block">#', '', $inner);
    $inner = preg_replace('#</section>$#', '', $inner);
    $inner = preg_replace('#<h3 class="ins-title">.*?</h3>#', '', $inner);
    $h .= $inner . '</div>';

    $h .= '</section>';
    /* No <script> here on purpose. This block is also delivered by
       insight_ajax.php and injected with innerHTML, and scripts inserted that
       way never execute -- the toggle would render and then do nothing. The
       handler lives in iss_insight_audience_js() instead, emitted once per
       page and bound by delegation, so it keeps working across replacements. */
    return $h;
}

/* Emit ONCE per page, anywhere. Delegated, so it governs blocks that arrive
   later as well as the ones present at parse time. */
function iss_insight_audience_js() {
    return "<!-- iss_insight_audience v" . ISS_INSIGHT_AUDIENCE_V . " LOADED from "
         . htmlspecialchars(basename(dirname(__FILE__)), ENT_QUOTES, 'UTF-8') . " -->"
         . '<script>(function(){
if(window.issInsightApplyView) return;
var KEY="issInsightView";
function pref(){ try{ var v=localStorage.getItem(KEY);
  return (v==="exec"||v==="tech")?v:"tech"; }catch(e){ return "tech"; } }
function paint(b,v){
  b.setAttribute("data-view",v);
  var t=b.getElementsByTagName("button"), i, on;
  for(i=0;i<t.length;i++){
    if(!t[i].getAttribute("data-v")) continue;
    on = t[i].getAttribute("data-v")===v;
    t[i].className = on?"ins-tab is-on":"ins-tab";
    t[i].setAttribute("aria-pressed", on?"true":"false");
  }
}
function apply(){
  var v=pref(), b=document.getElementsByClassName("ins-block"), i;
  for(i=0;i<b.length;i++){ paint(b[i],v); }
}
window.issInsightApplyView=apply;
function onClick(e){
  var el=e.target||e.srcElement;
  while(el && el!==document){
    if(el.getAttribute && el.getAttribute("data-v")
       && typeof el.className==="string" && el.className.indexOf("ins-tab")!==-1){
      var v=el.getAttribute("data-v");
      try{ localStorage.setItem(KEY,v); }catch(err){}
      apply();
      return;
    }
    el=el.parentNode;
  }
}
if(document.addEventListener){ document.addEventListener("click",onClick,false); }
else { document.attachEvent("onclick",onClick); }
if(document.readyState==="loading" && document.addEventListener){
  document.addEventListener("DOMContentLoaded",apply,false);
} else { apply(); }
})();</script>';
}

/* --------------------------------------------------------------------------
 * SUMMARY BAND  --  the one sentence, placed where the eye already is.
 *
 * The full panel belongs beside the table: an interpretation the reader
 * cannot check against the figures is harder to trust, and ground staff read
 * the two together. But an executive reads the top of the page and stops, so
 * the conclusion has to survive above the fold on its own.
 *
 * This hoists the bottom line ONLY, never a second copy of the analysis --
 * two blocks saying the same thing would have to be kept in agreement, and a
 * printout would carry the text twice. It is built from the same findings as
 * the panel, so it cannot drift from it.
 * ------------------------------------------------------------------------*/
function iss_insight_summary_band($c, $F, $anchor) {
    $e   = 'iss_ins_esc';
    $exe = iss_insight_executive($c, $F);

    /* A narrow filter -- one month, or a car with only a couple of records in
       the chosen year -- leaves nothing for the pattern findings to work on,
       and the band used to return empty. To the reader that is indistinguish-
       able from the feature being broken. Say what the window holds and say
       why there is no pattern read, rather than disappearing. */
    $thin = false;
    if ($exe['bottom'] === '' && !count($exe['points'])) {
        /* The volume sentence no longer lands in `context` once the score is
           carrying the total, so a thin window would otherwise fall straight
           through to the empty return -- the exact disappearing-band failure
           this branch was written to stop. Fall back to the score's own total
           line, which is present whenever there is any data at all. */
        if (count($exe['context'])) { $line = implode(' ', $exe['context']); }
        elseif ($exe['score'] !== '') { $line = $exe['score']; }
        else { return ''; }                           /* genuinely no data */
        $thin = true;
    } else {
        $line = ($exe['bottom'] !== '') ? $exe['bottom'] : $exe['points'][0];
    }

    $alert = 0; $watch = 0; $cav = 0;
    foreach ($F as $f) {
        if ($f['severity'] === 'alert')  { $alert++; }
        elseif ($f['severity'] === 'watch')  { $watch++; }
        elseif ($f['severity'] === 'caveat') { $cav++; }
    }
    $bits = array();
    if ($thin) {
        $cov = 0;
        foreach ($c['totals']['by_bucket'] as $v) { if ($v !== null && is_numeric($v)) { $cov++; } }
        $bits[] = 'too few ' . iss_ins_colword($c, 2)
                . ' in this filter for a trend or pattern read'
                . ($cov <= 1 ? ' -- widen the range to compare against others' : '');
    }
    if ($alert) { $bits[] = $alert . ' needing attention'; }
    if ($watch) { $bits[] = $watch . ' to watch'; }
    /* Caveats are counted in the band too. A reader who only ever sees this
       strip still needs to know the figures come with conditions attached. */
    if ($cav)   { $bits[] = $cav . ' data caveat' . ($cav === 1 ? '' : 's'); }

    $h  = '<div class="ins-band">';
    $h .= '<div class="ins-band-key">Bottom line</div>';
    /* The score is NOT repeated here. This band exists to hoist the bottom
       line above the fold and nothing else; the panel a few hundred pixels
       below already carries the score, and on the live page the two together
       read as the same three lines printed twice. The thin-filter branch
       above is the one exception, and there the panel has nothing else in it
       to duplicate. */
    $h .= '<div class="ins-band-body"><p class="ins-band-line">' . $e($line) . '</p>';
    if (count($bits)) {
        $safe = array();
        foreach ($bits as $x) { $safe[] = $e($x); }
        $h .= '<p class="ins-band-meta">' . implode(' &middot; ', $safe) . '</p>';
    }
    $h .= '</div>';
    $h .= '<a class="ins-band-jump" href="#' . $e($anchor) . '">Full analysis &darr;</a>';
    return $h . '</div>';
}

function iss_insight_band_css() {
    return '<style>
.ins-band{display:flex;align-items:flex-start;gap:14px;flex-wrap:wrap;
  border:1px solid #E5DECC;border-left:4px solid var(--cf-gold,#c8a028);
  border-radius:6px;background:#FBFAF6;padding:11px 14px;margin:0 0 14px;}
.ins-band-key{font-size:11px;text-transform:uppercase;letter-spacing:.06em;
  color:#5A6275;padding-top:2px;white-space:nowrap;}
.ins-band-body{flex:1;min-width:240px;}
.ins-band-line{margin:0;font-size:14.5px;line-height:1.5;color:#1c2431;}
.ins-band-meta{margin:4px 0 0;font-size:11.5px;color:#5A6275;}
.ins-band-jump{font-size:12px;color:var(--cf-blue,#00529B);white-space:nowrap;
  padding-top:2px;text-decoration:none;}
.ins-band-jump:hover{text-decoration:underline;}
/* On paper the link points nowhere, but the sentence still leads the report. */
@media print{.ins-band-jump{display:none;}
  .ins-band{break-inside:avoid;background:none;}}
</style>';
}

function iss_insight_audience_css() {
    return '<style>
.ins-bar{display:flex;align-items:center;justify-content:space-between;gap:12px;
  flex-wrap:wrap;margin:0 0 10px;}
.ins-bar .ins-title{margin:0;}
.ins-switch{display:inline-flex;border:1px solid var(--cf-line,#d7dde6);border-radius:4px;
  overflow:hidden;background:#fff;}
.ins-tab{appearance:none;border:0;background:#fff;color:#4a5666;font:inherit;
  font-size:12px;padding:4px 11px;cursor:pointer;line-height:1.6;}
.ins-tab + .ins-tab{border-left:1px solid var(--cf-line,#d7dde6);}
.ins-tab:hover{background:#f2f5f9;}
.ins-tab.is-on{background:var(--cf-blue,#00529B);color:#fff;}
.ins-view{display:none;}
.ins-block[data-view="exec"] .ins-exec{display:block;}
.ins-block[data-view="tech"] .ins-tech{display:block;}
.ins-exec .ins-head{font-size:15.5px;}
/* The score block. A tinted panel at reading size, not an indented caption:
   at 13.5px with a bare rule beside it, the summary of the whole report was
   set smaller and lighter than the bullets underneath it. Palette matches the
   summary band so the two read as the same kind of object.
   No label column, so nothing here can collide the way the old fixed-width
   one did. */
.ins-score{margin:0 0 13px;padding:10px 14px;
  background:#FBFAF6;border:1px solid #E5DECC;
  border-left:4px solid var(--cf-gold,#c8a028);border-radius:4px;
  font-size:14.5px;line-height:1.65;color:#1c2431;}
.ins-score b{font-weight:600;color:var(--cf-blue,#1f4e79);}
.ins-exec .ins-findings li{margin-bottom:7px;}
/* Print what is on screen, and drop the control itself. */
@media print{
  .ins-switch{display:none;}
  .ins-block[data-view="exec"] .ins-tech{display:none;}
  .ins-block[data-view="tech"] .ins-exec{display:none;}
}
</style>';
}