<?php
/* ============================================================================
 * iss_insight_analytics.php  --  the layer that produces INSIGHT rather than
 * description. Include after iss_insight.php.
 *
 * The base findings restate the table: totals, peaks, who is worst. Useful,
 * but the reader can see all of it. Everything here answers a question the
 * table on screen CANNOT answer:
 *
 *   crosstab_shape  Is this equipment failing across the whole fleet, or on a
 *                   handful of cars? Fleet-wide and unit-specific look
 *                   identical in a by-equipment table and mean opposite things.
 *   changepoint     Did the level actually shift, and when -- as opposed to
 *                   one loud month inside a flat series?
 *   seasonality     Is this month's peak unusual, or is it what this month
 *                   always does? This one OVERRIDES the peak finding.
 *   co_movement     Do two equipment types rise and fall together?
 *   rotation        Is the worst-offender set the same as last period? If it
 *                   never rotates, nothing is being fixed.
 *   recurrence      How often does the same car fail the same way again within
 *                   30 days -- i.e. the repair did not hold.
 *
 * Each returns findings in the same shape, so the prompt, the audit gate and
 * the renderer are unchanged.
 * ==========================================================================*/

if (defined('ISS_INSIGHT_ANALYTICS')) { return; }
define('ISS_INSIGHT_ANALYTICS', 1);

/* ---- percentage helpers ---------------------------------------------------
 * These live in iss_insight.php. They are re-declared here, each guarded on
 * its OWN name, because stations are updated by hand: a station that receives
 * this file before it receives the new iss_insight.php would otherwise fatal
 * on the first percentage rather than degrade. Same rule as every other
 * helper in this suite.
 * -------------------------------------------------------------------------*/
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
if (!function_exists('iss_ins_relpct_xy')) {
    function iss_ins_relpct_xy($obs, $exp) { return ($exp > 0) ? iss_ins_relpct($obs / $exp) : null; }
}
if (!function_exists('iss_ins_relword_xy')) {
    function iss_ins_relword_xy($obs, $exp) { return ($exp > 0) ? iss_ins_relword($obs / $exp) : ''; }
}

/* ---- saying what a statistic MEANS, before quoting it ---------------------
 * The technical register is for engineers and controllers, and they want the
 * figures kept. What they do not want -- and what made this panel read as a
 * stats dump to everyone else who opened it -- is notation standing in the
 * place where a sentence should be. So every statistic now arrives as a plain
 * claim with the number parked behind it in brackets, where an engineer can
 * still find it and nobody else has to decode it to read the finding.
 * -------------------------------------------------------------------------*/
function iss_ana_strength($z) {
    $a = abs((float)$z);
    if ($a >= 4.0) { $w = 'far beyond'; }
    elseif ($a >= 3.0) { $w = 'well beyond'; }
    elseif ($a >= 2.0) { $w = 'beyond'; }
    else { $w = 'within'; }
    return $w . ' what normal month-to-month movement would give (z=' . $z
         . '; above 2 is notable, above 3 is strong, above 4 is as good as certain)';
}
function iss_ana_rword($r) {
    $a = abs((float)$r);
    $w = ($a >= 0.85) ? 'very closely' : (($a >= 0.7) ? 'closely' : 'loosely');
    return $w . ' (correlation ' . $r . ', where 1.00 means they move exactly together)';
}

/* ---- normal CDF (Abramowitz & Stegun 7.1.26) for residual significance --- */
function iss_ana_erf($x) {
    $s = ($x < 0) ? -1 : 1; $x = abs($x);
    $t = 1 / (1 + 0.3275911 * $x);
    $a1=0.254829592; $a2=-0.284496736; $a3=1.421413741; $a4=-1.453152027; $a5=1.061405429;
    $y = 1 - ((((($a5*$t + $a4)*$t + $a3)*$t + $a2)*$t + $a1)*$t) * exp(-$x*$x);
    return $s * $y;
}
function iss_ana_ptwo($z) { return 1 - iss_ana_erf(abs($z) / sqrt(2)); }

/* ==========================================================================
 * 1. CROSS-TAB SHAPE  --  fleet-wide vs unit-specific
 *
 * Adjusted standardized residual per cell:
 *     z = (obs - exp) / sqrt( exp * (1 - rowT/N) * (1 - colT/N) )
 * |z| >= 2 is notable, >= 3 is strong. Then classify each column by how many
 * of its cells are hot: none => spread, one or two => concentrated.
 * ======================================================================== */
function iss_ana_crosstab($ct, $unit) {
    $out = array();
    if (!is_array($ct) || empty($ct['matrix'])) { return $out; }
    $M = $ct['matrix'];
    $rl = isset($ct['rows']) ? $ct['rows'] : array();
    $cl = isset($ct['cols']) ? $ct['cols'] : array();
    $nr = count($M); if ($nr < 2) { return $out; }
    $nc = count($M[0]); if ($nc < 2) { return $out; }

    $rt = array_fill(0, $nr, 0); $ctot = array_fill(0, $nc, 0); $N = 0;
    for ($i = 0; $i < $nr; $i++) {
        for ($j = 0; $j < $nc; $j++) {
            $v = (int)$M[$i][$j]; $rt[$i] += $v; $ctot[$j] += $v; $N += $v;
        }
    }
    if ($N < 30) { return $out; }   /* residuals are noise below this */

    /* If the cross-tab and the main table disagree, every figure below will
       quietly contradict the figures above it. Say so and stop. */
    if (isset($ct['expect_grand']) && (int)$ct['expect_grand'] > 0) {
        $eg = (int)$ct['expect_grand'];
        if (abs($N - $eg) > max(2, $eg * 0.02)) {
            return array(array(
                'kind' => 'crosstab_mismatch', 'severity' => 'caveat',
                'text' => sprintf('The %s-by-%s breakdown totals %d against %d on the main table, so the two are not counting the same thing. Cross-dimensional analysis is suppressed until they reconcile.',
                          strtolower($ct['row_label']), strtolower($ct['col_label']), $N, $eg),
                'facts' => array('crosstab_total' => $N, 'report_total' => $eg),
            ));
        }
    }

    $cols = array();
    for ($j = 0; $j < $nc; $j++) {
        if ($ctot[$j] < 8) { continue; }
        $hot = array(); $maxz = 0;
        for ($i = 0; $i < $nr; $i++) {
            if ($rt[$i] < 1) { continue; }
            $exp = ($rt[$i] * $ctot[$j]) / $N;
            if ($exp <= 0) { continue; }
            $den = sqrt($exp * (1 - $rt[$i] / $N) * (1 - $ctot[$j] / $N));
            if ($den <= 0) { continue; }
            $z = ((int)$M[$i][$j] - $exp) / $den;
            if ($z > $maxz) { $maxz = $z; }
            if ($z >= 2.0 && (int)$M[$i][$j] >= 3) {
                /* `ratio` is kept alongside `over_pct` on purpose: a station
                   running an older iss_insight_audience.php still reads it,
                   and dropping it would blank that sentence rather than just
                   phrase it the old way. over_pct is computed from the
                   unrounded expectation, so it does not inherit the rounding
                   in `exp`. */
                /* share_pct and expect_share_pct are the pair that gets
                   REPORTED. "% above expected" is unbounded -- it is a ratio
                   with a percent sign on it -- and expected falls as the row
                   count rises, so the same real pattern reads as 106% on the
                   7-row equipment report and 5744% on the 70-car one. Two
                   shares of the same column cannot leave 0-100 and mean the
                   same thing at either size. exp/colTotal reduces to this
                   row's share of all activity, which is exactly the "fair
                   share" the old phrasing was reaching for.
                   over_pct and ratio stay in facts for older callers. */
                $hot[] = array('row' => isset($rl[$i]) ? $rl[$i] : ('#' . $i),
                               'obs' => (int)$M[$i][$j], 'exp' => round($exp, 1),
                               'z' => round($z, 1), 'ratio' => round($M[$i][$j] / $exp, 1),
                               'over_pct' => iss_ins_relpct_xy($M[$i][$j], $exp),
                               'share_pct' => iss_ins_pct($M[$i][$j], $ctot[$j]),
                               'expect_share_pct' => iss_ins_pct($rt[$i], $N));
            }
        }
        usort($hot, 'iss_ana_cmp_z');
        $cols[] = array('col' => isset($cl[$j]) ? $cl[$j] : ('#' . $j),
                        'total' => $ctot[$j], 'hot' => $hot, 'maxz' => round($maxz, 1),
                        'share_in_hot' => count($hot) ? iss_ins_pct(iss_ana_sum_obs($hot), $ctot[$j]) : 0);
    }
    usort($cols, 'iss_ana_cmp_total');

    $conc = array(); $spread = array();
    foreach ($cols as $c) {
        if (count($c['hot']) && count($c['hot']) <= max(2, (int)floor($nr * 0.3))) { $conc[] = $c; }
        elseif (!count($c['hot']) && $c['total'] >= 15) { $spread[] = $c; }
    }

    foreach (array_slice($conc, 0, 3) as $c) {
        $who = array();
        foreach (array_slice($c['hot'], 0, 3) as $h) {
            $who[] = $h['row'] . ' (' . $h['obs'] . ', ' . $h['share_pct']
                   . '% against ' . $h['expect_share_pct'] . '% expected)';
        }
        $out[] = array(
            'kind' => 'concentrated_on_units', 'severity' => 'alert',
            /* Phrasing is driven by the actual column label. This runs over
               Car x Equipment on the report page and Car x Severity on the
               drill-down, and "fleet-wide" is only meaningful for the first. */
            'text' => sprintf('%s does not spread evenly across %s: %s%% of its %d %s are on %d of the %d -- %s. That is %s, so this is not just chance.',
                      $c['col'], strtolower(iss_ins_plural($ct['row_label'], 2)),
                      $c['share_in_hot'], $c['total'], $unit,
                      count($c['hot']), $nr,
                      implode(', ', $who), iss_ana_strength($c['hot'][0]['z'])),
            'facts' => array('column' => $c['col'], 'total' => $c['total'],
                             'hot' => array_slice($c['hot'], 0, 3),
                             'share_pct' => $c['share_in_hot'], 'n_rows' => $nr,
                             'row_label' => $ct['row_label']),
        );
    }
    foreach (array_slice($spread, 0, 1) as $c) {
        $out[] = array(
            'kind' => 'fleet_wide', 'severity' => 'watch',
            'text' => sprintf('%s is spread evenly across %s: %d %s, and no %s has more than its normal share would give. The %s furthest from its normal share is still %s. That points at the %s as a whole rather than at particular %s.',
                      $c['col'], strtolower(iss_ins_plural($ct['row_label'], 2)),
                      $c['total'], $unit, strtolower($ct['row_label']),
                      strtolower($ct['row_label']), iss_ana_strength($c['maxz']),
                      strtolower($ct['col_label']),
                      strtolower(iss_ins_plural($ct['row_label'], 2))),
            'facts' => array('column' => $c['col'], 'total' => $c['total'], 'max_z' => $c['maxz'],
                             'row_label' => $ct['row_label']),
        );
    }
    return $out;
}
function iss_ana_sum_obs($h) { $s = 0; foreach ($h as $x) { $s += $x['obs']; } return $s; }
function iss_ana_cmp_z($a, $b)     { return ($b['z'] == $a['z']) ? 0 : (($b['z'] < $a['z']) ? -1 : 1); }
function iss_ana_cmp_total($a, $b) { return ($b['total'] == $a['total']) ? 0 : (($b['total'] < $a['total']) ? -1 : 1); }

/* ==========================================================================
 * 2. CHANGE POINT  --  Taylor CUSUM with a permutation test.
 * "Higher in the second half" is not the same as "the level shifted in April
 * and stayed shifted". Only the second is actionable.
 * ======================================================================== */
function iss_ana_changepoint($series, $buckets, $colword) {
    $v = array(); $b = array();
    foreach ($series as $i => $x) {
        if ($x === null || !is_numeric($x)) { continue; }
        $v[] = (float)$x; $b[] = isset($buckets[$i]) ? $buckets[$i] : (string)$i;
    }
    $n = count($v);
    if ($n < 8) { return array(); }
    $mean = array_sum($v) / $n;

    $sdiff = iss_ana_cusum_range($v, $mean);
    if ($sdiff['range'] <= 0) { return array(); }

    $bigger = 0; $R = 400;
    for ($r = 0; $r < $R; $r++) {
        $p = $v; shuffle($p);
        $t = iss_ana_cusum_range($p, $mean);
        if ($t['range'] >= $sdiff['range']) { $bigger++; }
    }
    $conf = 1 - ($bigger / $R);
    if ($conf < 0.90) { return array(); }

    $k = $sdiff['at'];
    if ($k < 2 || $k > $n - 3) { return array(); }
    $before = array_slice($v, 0, $k + 1); $after = array_slice($v, $k + 1);
    $mb = array_sum($before) / count($before);
    $ma = array_sum($after) / count($after);
    if ($mb <= 0) { return array(); }
    $chg = iss_ins_pct($ma - $mb, $mb);
    if (abs($chg) < 20) { return array(); }

    return array(array(
        'kind' => 'changepoint', 'severity' => ($chg > 0 ? 'alert' : 'info'),
        'text' => sprintf('The level moved to a new one and stayed there, rather than just going up and down: a step %s at %s that did not go back. Average of %s per %s across the %d %ss before, and %s across the %d after, a change of %s%%. The same test on shuffled data says the odds of a step this clean happening by chance are under %s in 100.',
                  ($chg > 0 ? 'up' : 'down'), $b[$k + 1], round($mb, 1), $colword,
                  count($before), $colword, round($ma, 1), count($after),
                  ($chg >= 0 ? '+' : '') . $chg, max(1, 100 - round($conf * 100))),
        'facts' => array('at' => $b[$k + 1], 'mean_before' => round($mb, 1),
                         'mean_after' => round($ma, 1), 'change_pct' => $chg,
                         'confidence_pct' => round($conf * 100)),
    ));
}
function iss_ana_cusum_range($v, $mean) {
    $s = 0; $mn = 0; $mx = 0; $at = 0;
    foreach ($v as $i => $x) {
        $s += ($x - $mean);
        if ($s > $mx) { $mx = $s; $at = $i; }
        if ($s < $mn) { $mn = $s; $at = $i; }
    }
    return array('range' => $mx - $mn, 'at' => $at);
}

/* ==========================================================================
 * 3. SEASONALITY  --  and it OVERRIDES the peak finding.
 * An April spike in air conditioning in Metro Manila may be exactly what
 * April does. Without multi-year history you cannot tell; with it you can.
 * ======================================================================== */
function iss_ana_seasonality($hist, $peakBucket, &$F, $colword) {
    $out = array();
    if (!is_array($hist) || empty($hist['buckets'])) { return $out; }
    $bym = array();
    foreach ($hist['buckets'] as $i => $bk) {
        if (!isset($hist['values'][$i]) || $hist['values'][$i] === null) { continue; }
        if (!preg_match('/^(\d{4})-(\d{2})/', $bk, $m)) { continue; }
        $bym[(int)$m[2]][] = (float)$hist['values'][$i];
    }
    $all = array();
    foreach ($bym as $mth => $xs) { foreach ($xs as $x) { $all[] = $x; } }
    if (count($all) < 24) { return $out; }
    $gm = array_sum($all) / count($all);
    if ($gm <= 0) { return $out; }

    $idx = array();
    foreach ($bym as $mth => $xs) {
        if (count($xs) < 2) { continue; }
        $idx[$mth] = (array_sum($xs) / count($xs)) / $gm;
    }
    if (count($idx) < 8) { return $out; }

    $high = array(); $low = array();
    foreach ($idx as $mth => $r) {
        $nm = date('F', mktime(0, 0, 0, $mth, 1, 2000));
        if ($r >= 1.25) { $high[] = $nm . ' (' . iss_ins_relword($r) . ')'; }
        if ($r <= 0.75) { $low[]  = $nm . ' (' . iss_ins_relword($r) . ')'; }
    }
    if (count($high) || count($low)) {
        $out[] = array(
            'kind' => 'seasonality', 'severity' => 'info',
            'text' => sprintf('The same yearly shape repeats across %d years of history, measured against the all-year average: %s%s. Judge a single %s against the same month in other years, not against the yearly average.',
                      (int)round(count($all) / 12),
                      (count($high) ? 'consistently heavy in ' . implode(', ', $high) : ''),
                      (count($low) ? (count($high) ? '; light in ' : 'consistently light in ') . implode(', ', $low) : ''),
                      $colword),
            'facts' => array('index' => $idx, 'years' => (int)round(count($all) / 12)),
        );
    }

    /* the override */
    if ($peakBucket && preg_match('/^\d{4}-(\d{2})/', $peakBucket, $pm)) {
        $pmi = (int)$pm[1];
        if (isset($idx[$pmi])) {
            $r = round($idx[$pmi], 2);
            foreach ($F as $k => $f) {
                if ($f['kind'] !== 'peak') { continue; }
                if ($r >= 1.25) {
                    $F[$k]['severity'] = 'info';
                    $F[$k]['text'] = rtrim($F[$k]['text'], '.') .
                        sprintf(' -- but %s historically runs %s the annual average, so this high month is what that month normally does, not something new.',
                                date('F', mktime(0, 0, 0, $pmi, 1, 2000)), iss_ins_relword($r));
                    $F[$k]['facts']['seasonal_index'] = $r;
                    $F[$k]['facts']['seasonal_pct'] = iss_ins_relpct($r);
                    $F[$k]['facts']['is_outlier'] = false;
                    foreach ($F as $k2 => $f2) {
                        if ($f2['kind'] !== 'spike') { continue; }
                        if (empty($f2['facts']['bucket']) || $f2['facts']['bucket'] !== $peakBucket) { continue; }
                        $F[$k2]['severity'] = 'watch';
                        $F[$k2]['text'] = rtrim($F[$k2]['text'], '.') .
                            sprintf(', in a month that normally runs %s the year average -- so part of this is the usual pattern for the time of year, but the size of the jump is not.', iss_ins_relword($r));
                        $F[$k2]['facts']['seasonal_index'] = $r;
                        $F[$k2]['facts']['seasonal_pct'] = iss_ins_relpct($r);
                    }
                } elseif ($r <= 0.9 && !empty($f['facts']['is_outlier'])) {
                    $F[$k]['facts']['seasonal_pct'] = iss_ins_relpct($r);
                    $F[$k]['text'] = rtrim($F[$k]['text'], '.') .
                        sprintf(' -- and %s is normally a QUIET month (%s the annual average), so the peak lands where it is least expected.',
                                date('F', mktime(0, 0, 0, $pmi, 1, 2000)), iss_ins_relword($r));
                    $F[$k]['facts']['seasonal_index'] = $r;
                }
            }
        }
    }
    return $out;
}

/* ==========================================================================
 * 4. CO-MOVEMENT  --  two types rising together suggests a shared cause,
 * or a shared reporting habit. Either is worth knowing; neither is proof.
 * ======================================================================== */
function iss_ana_comovement($rows, $rowword) {
    $out = array(); $cand = array();
    foreach ($rows as $r) {
        if ($r['total'] >= 12 && count(iss_ins_clean($r['values'])) >= 8) { $cand[] = $r; }
    }
    $n = count($cand);
    if ($n < 2) { return $out; }
    $pairs = array();
    for ($i = 0; $i < $n; $i++) {
        for ($j = $i + 1; $j < $n; $j++) {
            $r = iss_ana_pearson($cand[$i]['values'], $cand[$j]['values']);
            if ($r === null) { continue; }
            if (abs($r) >= 0.6) {
                $pairs[] = array('a' => $cand[$i]['label'], 'b' => $cand[$j]['label'], 'r' => round($r, 2));
            }
        }
    }
    if (!count($pairs)) { return $out; }
    usort($pairs, 'iss_ana_cmp_absr');
    $p = $pairs[0];
    $out[] = array(
        'kind' => 'co_movement', 'severity' => 'watch',
        'text' => sprintf('%s and %s go up and down together month to month, and they follow each other %s. Check whether they share a subsystem, a maintenance window, or just a reporting habit. Over this few months, this is something to look into, not proof.',
                  $p['a'], $p['b'], iss_ana_rword($p['r'])),
        'facts' => array('pairs' => array_slice($pairs, 0, 3)),
    );
    return $out;
}
function iss_ana_pearson($x, $y) {
    $ax = array(); $ay = array();
    foreach ($x as $i => $v) {
        if ($v === null || !isset($y[$i]) || $y[$i] === null) { continue; }
        $ax[] = (float)$v; $ay[] = (float)$y[$i];
    }
    $n = count($ax);
    if ($n < 6) { return null; }
    $mx = array_sum($ax) / $n; $my = array_sum($ay) / $n;
    $sxy = 0; $sxx = 0; $syy = 0;
    for ($i = 0; $i < $n; $i++) {
        $dx = $ax[$i] - $mx; $dy = $ay[$i] - $my;
        $sxy += $dx * $dy; $sxx += $dx * $dx; $syy += $dy * $dy;
    }
    if ($sxx <= 0 || $syy <= 0) { return null; }
    return $sxy / sqrt($sxx * $syy);
}
function iss_ana_cmp_absr($a, $b) {
    $x = abs($a['r']); $y = abs($b['r']);
    return ($x == $y) ? 0 : (($x < $y) ? 1 : -1);
}

/* ==========================================================================
 * 5. PARETO ROTATION  --  did the worst-offender set change?
 * A stable top-5 across two periods means the interventions are not landing.
 * ======================================================================== */
function iss_ana_rotation($rows, $prevRows, $rowword, $prevLabel) {
    if (!is_array($prevRows) || count($prevRows) < 3 || count($rows) < 3) { return array(); }
    $top = iss_ana_topk($rows, 5);
    $pre = iss_ana_topk($prevRows, 5);
    $same = array_values(array_intersect($top, $pre));
    $new  = array_values(array_diff($top, $pre));
    $k = count($same);
    if ($k >= 4) {
        return array(array(
            'kind' => 'rotation', 'severity' => 'alert',
            'text' => sprintf('The worst-offender set has not moved: %d of the top 5 %s are the same as %s (%s). A stable list across two periods means whatever was done in between has not changed the ranking.',
                      $k, $rowword, $prevLabel, implode(', ', array_slice($same, 0, 5))),
            'facts' => array('overlap' => $k, 'unchanged' => $same, 'new_entrants' => $new,
                             'previous_label' => $prevLabel),
        ));
    }
    if ($k <= 2 && count($new)) {
        return array(array(
            'kind' => 'rotation', 'severity' => 'watch',
            'text' => sprintf('The top 5 has changed: only %d of the top 5 %s are the same as in %s. New in the top 5: %s.',
                      $k, $rowword, $prevLabel, implode(', ', array_slice($new, 0, 5))),
            'facts' => array('overlap' => $k, 'unchanged' => $same, 'new_entrants' => $new,
                             'previous_label' => $prevLabel),
        ));
    }
    return array();
}
function iss_ana_topk($rows, $k) {
    $r = $rows; usort($r, 'iss_ins_cmp_total');
    $o = array();
    foreach (array_slice($r, 0, $k) as $x) { $o[] = $x['label']; }
    return $o;
}

/* ==========================================================================
 * 6. RECURRENCE  --  same unit, same fault, again, soon.
 * The single most actionable signal in the dataset and it is invisible in
 * every monthly table: the repair did not hold.
 * ======================================================================== */
function iss_ana_recurrence($events, $windowDays, $unit) {
    if (!is_array($events) || count($events) < 20) { return array(); }
    $g = array(); $tmin = null; $tmax = null;
    foreach ($events as $e) {
        if (empty($e['date'])) { continue; }
        $t = strtotime($e['date']);
        if ($t === false) { continue; }
        if ($tmin === null || $t < $tmin) { $tmin = $t; }
        if ($tmax === null || $t > $tmax) { $tmax = $t; }
        $k = (isset($e['unit_key']) ? $e['unit_key'] : '') . '|' . (isset($e['fault_key']) ? $e['fault_key'] : '');
        $g[$k][] = array('t' => $t,
                         'unit'  => isset($e['unit_label'])  ? $e['unit_label']  : $e['unit_key'],
                         'fault' => isset($e['fault_label']) ? $e['fault_label'] : $e['fault_key']);
    }
    $span = ($tmax !== null && $tmin !== null) ? max(1, ($tmax - $tmin) / 86400) : 0;
    if ($span < $windowDays * 2) { return array(); }

    /* A raw repeat rate is not a finding. At high volume, repeats inside 30
       days are simply what volume predicts. The question is whether a pair
       repeats FASTER than its own failure rate would produce by chance, so
       every count below is measured against a homogeneous-Poisson baseline
       at that pair's own rate. */
    $obs = 0; $exp = 0.0; $total = 0; $hot = array();
    foreach ($g as $k => $list) {
        $n = count($list); $total += $n;
        if ($n < 2) { continue; }
        usort($list, 'iss_ana_cmp_t');
        $o = 0; $gaps = array();
        for ($i = 1; $i < $n; $i++) {
            $d = ($list[$i]['t'] - $list[$i - 1]['t']) / 86400;
            if ($d <= $windowDays) { $o++; $gaps[] = round($d); }
        }
        $lambda = $n / $span;                                  /* per day    */
        $pe     = 1 - exp(-$lambda * $windowDays);             /* P(gap<=W)  */
        $e      = ($n - 1) * $pe;
        $obs += $o; $exp += $e;
        if ($e > 0 && $o >= 3) {
            $z = ($o - $e) / sqrt(max(0.5, $e * (1 - $pe)));
            if ($z >= 2.0) {
                $hot[] = array('pair' => $list[0]['unit'] . ' / ' . $list[0]['fault'],
                               'repeats' => $o, 'expected' => round($e, 1), 'n' => $n,
                               'z' => round($z, 1), 'median_gap_days' => iss_ins_median($gaps),
                               'over_pct' => iss_ins_relpct_xy($o, $e));
            }
        }
    }
    /* The fixed 30-day window saturates: once a pair's own rate makes a
       repeat inside it near-certain, expected ~= observed however the
       failures are arranged in time. So run the same test again with a
       window that ADAPTS to the pair's rate -- one third of its own mean
       gap. Under a Poisson process the gaps are exponential, so the chance
       of any one gap falling inside mu/3 is a constant 1 - e^(-1/3) = 0.283
       whatever the rate. That makes the comparison scale-free: three bursts
       of three, spread across a year, read the same as three bursts of three
       inside a month.

       A coefficient-of-variation test was tried here first and rejected: on
       a clearly bursty nine-failure series it came out at 1.41, which is
       under one standard error away from the 1.0 a Poisson process gives,
       so any threshold loose enough to catch it also fired on noise. */
    foreach ($g as $k => $list) {
        $n2 = count($list);
        if ($n2 < 6) { continue; }
        usort($list, 'iss_ana_cmp_t');
        $gaps = array();
        for ($i = 1; $i < $n2; $i++) { $gaps[] = ($list[$i]['t'] - $list[$i - 1]['t']) / 86400; }
        $ng = count($gaps);
        $mu = array_sum($gaps) / $ng;
        if ($mu <= 0) { continue; }
        $w = $mu / 3;
        $tight = 0;
        foreach ($gaps as $gg) { if ($gg <= $w) { $tight++; } }
        if ($tight < 3) { continue; }
        $pp = 1 - exp(-1 / 3);                       /* 0.2835 */
        $eT = $ng * $pp;
        $zT = ($tight - $eT) / sqrt(max(0.5, $ng * $pp * (1 - $pp)));
        if ($zT < 2.5) { continue; }
        $already = false;
        foreach ($hot as $h) {
            if ($h['pair'] === $list[0]['unit'] . ' / ' . $list[0]['fault']) { $already = true; break; }
        }
        if ($already) { continue; }
        $hot[] = array('pair' => $list[0]['unit'] . ' / ' . $list[0]['fault'],
                       'repeats' => $tight, 'expected' => round($eT, 1),
                       'n' => $n2, 'intervals' => $ng, 'z' => round($zT, 1), 'bursty' => true,
                       'window' => round($w), 'median_gap_days' => iss_ins_median($gaps),
                       'over_pct' => iss_ins_relpct_xy($tight, $eT));
    }

    if ($total < 20 || (($obs < 3 || $exp <= 0) && !count($hot))) { return array(); }
    if ($exp <= 0) { $exp = max(0.1, $obs); }
    usort($hot, 'iss_ana_cmp_z');
    $ratio = round($obs / $exp, 2);

    if (!count($hot)) {
        if ($ratio < 1.25) { return array(); }   /* nothing worth saying */
        return array(array(
            'kind' => 'recurrence', 'severity' => 'watch',
            'text' => sprintf('Faults come back %s%% more often than these units\' own failure rates would give: %d came back within %d days, against %s expected. No single unit-and-fault pair explains it, so this is spread across many of them rather than a few bad repairs.',
                      iss_ins_relpct($ratio), $obs, $windowDays, round($exp, 1)),
            'facts' => array('observed' => $obs, 'expected' => round($exp, 1), 'ratio' => $ratio,
                             'over_pct' => iss_ins_relpct($ratio),
                             'window_days' => $windowDays),
        ));
    }
    $lst = array();
    foreach (array_slice($hot, 0, 3) as $w) {
        if (!empty($w['bursty'])) {
            $lst[] = sprintf('%s (%d of the %d gaps between its failures are under %s days, against %s expected at its own rate -- they come in bursts instead of being spread out)',
                     $w['pair'], $w['repeats'],
                     (isset($w['intervals']) ? $w['intervals'] : max(1, $w['n'] - 1)),
                     $w['window'], $w['expected']);
        } else {
            $lst[] = sprintf('%s (%d repeats against %s expected -- %s%% above -- typically %s days apart)',
                     $w['pair'], $w['repeats'], $w['expected'],
                     (isset($w['over_pct']) ? $w['over_pct'] : iss_ins_relpct_xy($w['repeats'], $w['expected'])),
                     $w['median_gap_days']);
        }
    }
    return array(array(
        'kind' => 'recurrence', 'severity' => 'alert',
        'text' => sprintf('%d %s came back on the same unit with the same fault within %d days. Overall that is %s what these units\' own failure rates would give, which at this volume is nothing unusual. But %s came back more often than %s own failure rate explains, which is what it looks like when a repair does not fix the cause: %s.',
                  $obs, $unit, $windowDays, iss_ins_relword($ratio),
                  (count($hot) === 1 ? 'one pair' : count($hot) . ' pairs'),
                  (count($hot) === 1 ? 'its' : 'their'), implode('; ', $lst)),
        'facts' => array('observed' => $obs, 'expected' => round($exp, 1), 'ratio' => $ratio,
                         'over_pct' => iss_ins_relpct($ratio),
                         'window_days' => $windowDays, 'flagged' => array_slice($hot, 0, 3)),
    ));
}
function iss_ana_cmp_t($a, $b) { return ($a['t'] == $b['t']) ? 0 : (($a['t'] < $b['t']) ? -1 : 1); }
function iss_ana_cmp_repeats($a, $b) { return ($b['repeats'] == $a['repeats']) ? 0 : (($b['repeats'] < $a['repeats']) ? -1 : 1); }

/* ==========================================================================
 * 7. TIME OF DAY  --  when, within the operating day, does this happen?
 *
 * The history pages record an incident time, and clustering in it separates
 * load-driven failures (peak ridership, peak headway) from ones that fall
 * wherever the clock happens to be. Neither the monthly table nor any chart
 * on those pages can show it.
 *
 * Baseline is the observed operating window, NOT a flat 24 hours -- the line
 * does not run at 02:00, so measuring against a 24-hour uniform would make
 * every service hour look like a spike.
 * ======================================================================== */
function iss_ana_timing($hours, $unit) {
    if (!is_array($hours) || count($hours) < 24) { return array(); }
    $total = 0; $lo = null; $hi = null;
    for ($h = 0; $h < 24; $h++) {
        $v = (int)$hours[$h]; $total += $v;
        if ($v > 0) { if ($lo === null) { $lo = $h; } $hi = $h; }
    }
    if ($total < 40 || $lo === null || ($hi - $lo) < 5) { return array(); }

    $span = ($hi - $lo) + 1;
    $exp  = $total / $span;
    if ($exp <= 0) { return array(); }

    $hot = array();
    for ($h = $lo; $h <= $hi; $h++) {
        $v = (int)$hours[$h];
        if ($v < 5) { continue; }
        $z = ($v - $exp) / sqrt($exp);          /* Poisson: variance = mean */
        if ($z >= 2.5) {
            $hot[] = array('hour' => $h, 'count' => $v, 'expected' => round($exp, 1),
                           'z' => round($z, 1), 'ratio' => round($v / $exp, 1),
                           'over_pct' => iss_ins_relpct_xy($v, $exp));
        }
    }
    if (!count($hot)) { return array(); }
    usort($hot, 'iss_ana_cmp_z');

    $lbl = array();
    foreach (array_slice($hot, 0, 3) as $x) {
        $lbl[] = sprintf('%02d:00-%02d:59 (%d against %s expected -- %s%% above)',
                 $x['hour'], $x['hour'], $x['count'], $x['expected'], $x['over_pct']);
    }
    return array(array(
        'kind' => 'time_of_day', 'severity' => 'watch',
        'text' => sprintf('Within the %02d:00-%02d:59 operating window these %s do not fall evenly by hour: %s. Recorded times are when the entry was logged, so a peak can mean the failures cluster there or that the logging does -- worth checking against the shift pattern before reading it as load.',
                  $lo, $hi, $unit, implode('; ', $lbl)),
        'facts' => array('window_start' => $lo, 'window_end' => $hi,
                         'expected_per_hour' => round($exp, 1), 'peaks' => array_slice($hot, 0, 3)),
    ));
}

/* ==========================================================================
 * ENTRY  --  append advanced findings and renumber the whole list.
 * ======================================================================== */
function iss_insight_findings_advanced($ctx, $c, $F) {
    $unit    = $c['report']['unit'];
    $colword = iss_ins_colword($c, 1);
    $rowword = iss_ins_rowword($c, 2);

    $peak = null;
    foreach ($F as $f) { if ($f['kind'] === 'peak' && isset($f['facts']['bucket'])) { $peak = $f['facts']['bucket']; } }

    $adv = array();
    $adv = array_merge($adv, iss_ana_crosstab(iss_ins_get($ctx, array('crosstab'), null), $unit));
    $adv = array_merge($adv, iss_ana_changepoint($c['totals']['by_bucket'], $c['buckets'], $colword));
    /* seasonality may rewrite the peak finding, so it takes $F by reference */
    $adv = array_merge($adv, iss_ana_seasonality(iss_ins_get($ctx, array('history'), null), $peak, $F, $colword));
    $adv = array_merge($adv, iss_ana_comovement($c['rows'], $rowword));
    $adv = array_merge($adv, iss_ana_rotation($c['rows'],
                iss_ins_get($ctx, array('comparison','rows'), null), $rowword,
                iss_ins_get($ctx, array('comparison','label'), 'the prior period')));
    $adv = array_merge($adv, iss_ana_recurrence(iss_ins_get($ctx, array('events'), null), 30, $unit));
    $adv = array_merge($adv, iss_ana_timing(iss_ins_get($ctx, array('timing','hours'), null), $unit));

    /* insight before description: caveats stay last, advanced findings ride
       just under the volume line so the reader meets them first. */
    $head = array(); $tail = array(); $cav = array();
    foreach ($F as $f) {
        if ($f['severity'] === 'caveat') { $cav[] = $f; }
        elseif (in_array($f['kind'], array('volume', 'vs_prior'), true)) { $head[] = $f; }
        else { $tail[] = $f; }
    }
    $all = array_merge($head, $adv, $tail, $cav);
    $i = 0;
    foreach ($all as $k => $f) { $i++; $all[$k]['id'] = 'F' . $i; }
    return $all;
}