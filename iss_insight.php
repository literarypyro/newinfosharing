<?php
/* ============================================================================
 * iss_insight.php  --  Report interpretation layer for the ISS
 * ----------------------------------------------------------------------------
 * Pipeline:
 *
 *   report page  ->  iss.report.v1 JSON  ->  FINDINGS (pure PHP, deterministic)
 *                                              |
 *                                              +--> narrator (LLM)  --+
 *                                              |                      +--> panel
 *                                              +--> template renderer +    / print
 *
 * The rule this file exists to enforce: EVERY NUMBER IS COMPUTED IN PHP.
 * The model is only ever asked to turn a small, already-true findings list
 * into prose. It never sees raw rows, never does arithmetic, and its output
 * is audited against the findings before it is shown. If the model is
 * unreachable, slow, or disabled, the deterministic renderer produces the
 * same JSON shape and the page is unaffected.
 *
 * PHP 5 compatible on purpose (array(), no ??, no short array syntax).
 * Guard every include the way data_coverage.php is guarded -- see wiring notes.
 * ==========================================================================*/

if (defined('ISS_INSIGHT_LOADED')) { return; }
define('ISS_INSIGHT_LOADED', 1);

define('ISS_INSIGHT_SCHEMA',  'iss.report.v1');
define('ISS_INSIGHT_PROMPT_V', '4');   /* bump to invalidate every cached narrative */

/* ---------------------------------------------------------------------------
 * 0. CONFIG
 * -------------------------------------------------------------------------*/
function iss_insight_config() {
    $cfg = array(
        'enabled'        => false,      /* master switch; off until a provider is set */
        'provider'       => 'none',     /* none | anthropic | openai_compatible      */
        'base_url'       => '',         /* openai_compatible: http://10.0.0.9:11434/v1 */
        'api_key'        => '',
        'model'          => '',
        'timeout'        => 25,
        'max_tokens'     => 1200,
        'redact_labels'  => false,      /* alias row labels before they leave the box */
        'cache_dir'      => '',
        'cache_ttl'      => 86400,
        'log_file'       => '',
    );
    $f = dirname(__FILE__) . '/iss_insight_config.php';
    if (file_exists($f)) {
        $user = include($f);
        if (is_array($user)) { $cfg = array_merge($cfg, $user); }
    }
    if ($cfg['cache_dir'] === '') {
        $cfg['cache_dir'] = rtrim(sys_get_temp_dir(), '/\\') . '/iss_insight';
    }
    return $cfg;
}

function iss_insight_log($msg, $cfg) {
    if (empty($cfg['log_file'])) { return; }
    @error_log('[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n", 3, $cfg['log_file']);
}

/* ---------------------------------------------------------------------------
 * 1. CONTRACT
 *
 * Minimum a report page must emit. Anything absent degrades gracefully --
 * no severity block just means no severity findings.
 * -------------------------------------------------------------------------*/
function iss_insight_normalize($ctx) {
    if (!is_array($ctx)) { return null; }

    $out = array(
        'schema'  => ISS_INSIGHT_SCHEMA,
        'report'  => array(
            'id'    => iss_ins_get($ctx, array('report','id'), 'report'),
            'title' => iss_ins_get($ctx, array('report','title'), 'Report'),
            /* THE most important field. "car-level failures" and "incidents"
               are different units and the pages that mix them up produced the
               tally mismatch. State it, always. */
            'unit'  => iss_ins_get($ctx, array('report','unit'), 'records'),
            'sensitive' => (bool)iss_ins_get($ctx, array('report','sensitive'), false),
        ),
        'period'  => array(
            'from'  => iss_ins_get($ctx, array('period','from'), ''),
            'to'    => iss_ins_get($ctx, array('period','to'), ''),
            'grain' => iss_ins_get($ctx, array('period','grain'), 'month'),
        ),
        'filters'    => iss_ins_get($ctx, array('filters'), array()),
        'dimensions' => array(
            'row' => iss_ins_get($ctx, array('dimensions','row','label'), 'Row'),
            'col' => iss_ins_get($ctx, array('dimensions','col','label'), 'Period'),
        ),
        'buckets'    => iss_ins_get($ctx, array('buckets'), array()),
        'rows'       => array(),
        'coverage'   => array('uncovered' => iss_ins_get($ctx, array('coverage','uncovered_buckets'), array())),
        'severity'   => iss_ins_get($ctx, array('breakdowns','severity'), null),
        'quality'    => iss_ins_get($ctx, array('quality'), array()),
        'comparison' => iss_ins_get($ctx, array('comparison'), null),
    );

    $nb = count($out['buckets']);
    $gap = array();
    for ($i = 0; $i < $nb; $i++) {
        $gap[$i] = in_array($out['buckets'][$i], $out['coverage']['uncovered'], true);
    }
    $rows = iss_ins_get($ctx, array('rows'), array());
    foreach ($rows as $r) {
        $vals = isset($r['values']) && is_array($r['values']) ? array_values($r['values']) : array();
        while (count($vals) < $nb) { $vals[] = 0; }
        $vals = array_slice($vals, 0, $nb);
        $tot  = isset($r['total']) ? (int)$r['total'] : array_sum($vals);
        /* A gap is unknown, not zero -- at ROW level too. If these stay 0 the
           row's own median baseline collapses to 0 and every ordinary month
           trips the spike test. Mask before anything measures them. */
        for ($i = 0; $i < $nb; $i++) { if ($gap[$i]) { $vals[$i] = null; } }
        $out['rows'][] = array(
            'key'   => isset($r['key'])   ? (string)$r['key']   : '',
            'label' => isset($r['label']) ? (string)$r['label'] : (string)(isset($r['key']) ? $r['key'] : ''),
            'values'=> $vals,
            'total' => $tot,
        );
    }

    /* Column totals: trust the page if given, otherwise derive. Uncovered
       buckets become null, never 0 -- a gap is unknown, not an improvement. */
    $bt = iss_ins_get($ctx, array('totals','by_bucket'), null);
    if (!is_array($bt)) {
        $bt = array_fill(0, $nb, 0);
        foreach ($out['rows'] as $r) {
            for ($i = 0; $i < $nb; $i++) { $bt[$i] += (int)$r['values'][$i]; }
        }
    }
    $bt = array_values($bt);
    for ($i = 0; $i < $nb; $i++) {
        if (in_array($out['buckets'][$i], $out['coverage']['uncovered'], true)) { $bt[$i] = null; }
    }
    $out['totals'] = array(
        'by_bucket' => $bt,
        'grand'     => iss_ins_get($ctx, array('totals','grand'), array_sum(array_map('intval', $bt))),
    );
    return $out;
}

function iss_ins_get($a, $path, $default) {
    $cur = $a;
    foreach ($path as $k) {
        if (!is_array($cur) || !array_key_exists($k, $cur)) { return $default; }
        $cur = $cur[$k];
    }
    return ($cur === null) ? $default : $cur;
}

/* ---------------------------------------------------------------------------
 * 2. ROBUST STATS
 *
 * Counts, many zeros, small n. Mean/SD lies here; median/MAD does not.
 * -------------------------------------------------------------------------*/
function iss_ins_clean($a) {
    $o = array();
    foreach ($a as $v) { if ($v !== null && is_numeric($v)) { $o[] = (float)$v; } }
    return $o;
}
function iss_ins_median($a) {
    $a = iss_ins_clean($a); sort($a); $n = count($a);
    if ($n === 0) { return null; }
    $m = (int)floor($n / 2);
    return ($n % 2) ? $a[$m] : (($a[$m - 1] + $a[$m]) / 2.0);
}
function iss_ins_mad($a) {
    $med = iss_ins_median($a);
    if ($med === null) { return null; }
    $d = array();
    foreach (iss_ins_clean($a) as $v) { $d[] = abs($v - $med); }
    $m = iss_ins_median($d);
    return ($m === null) ? 0.0 : $m * 1.4826;   /* -> sigma-equivalent */
}
function iss_ins_slope($a) {
    /* least squares over covered points only, x = position */
    $n = 0; $sx = 0; $sy = 0; $sxy = 0; $sxx = 0;
    foreach ($a as $i => $v) {
        if ($v === null || !is_numeric($v)) { continue; }
        $n++; $sx += $i; $sy += $v; $sxy += $i * $v; $sxx += $i * $i;
    }
    if ($n < 3) { return null; }
    $den = ($n * $sxx) - ($sx * $sx);
    if ($den == 0) { return null; }
    return (($n * $sxy) - ($sx * $sy)) / $den;
}
function iss_ins_plural($w, $n) {
    if ($n == 1) { return $w; }
    $l = strtolower($w);
    if (substr($l, -1) === 's' || substr($l, -1) === 'y' && !preg_match('/[aeiou]y$/', $l)) {
        if (substr($l, -1) === 'y') { return substr($w, 0, -1) . 'ies'; }
        return $w;                                   /* Equipment(s) etc: leave */
    }
    if (in_array($l, array('equipment', 'personnel', 'rolling stock'), true)) { return $w; }
    return $w . 's';
}
function iss_ins_rowword($c, $n) { return strtolower(iss_ins_plural($c['dimensions']['row'], $n)); }
/* The unit is a caller-supplied plural ("car-level failures", "incidents").
   At a count of one it has to lose the 's', or a one-record window reads
   "1 recorded failures". */
function iss_ins_unit($c, $n) {
    $u = $c['report']['unit'];
    if ($n == 1 && substr($u, -1) === 's') { return substr($u, 0, -1); }
    return $u;
}
function iss_ins_colword($c, $n) { return strtolower(iss_ins_plural($c['dimensions']['col'], $n)); }
function iss_ins_pct($part, $whole) {
    if (!$whole) { return 0.0; }
    return round(($part / $whole) * 100, 1);
}

/* ---------------------------------------------------------------------------
 * RELATIVE SIZE AS A PERCENTAGE, never as a multiplier.
 *
 * "2.1x expected" is a statistic; "107% above expected" is a fact, and every
 * other figure this report puts in front of a reader is already a percentage.
 * The raw counts always stay in the sentence alongside it, so nothing is lost
 * -- the percentage replaces the multiplier, not the evidence.
 *
 * iss_ins_relpct() returns the signed whole number for `facts` (positive is
 * above, negative below, null if there is no baseline to compare against);
 * iss_ins_relword() returns it as words for the sentence. They are separate
 * so a caller storing the figure never has to parse a sentence to get it.
 * -------------------------------------------------------------------------*/
function iss_ins_relpct($ratio) {
    $r = (float)$ratio;
    if ($r <= 0) { return null; }
    return (int)round(($r - 1) * 100);
}
function iss_ins_relword($ratio) {
    $p = iss_ins_relpct($ratio);
    if ($p === null) { return ''; }
    if ($p === 0)    { return 'level with expected'; }
    return abs($p) . '% ' . ($p > 0 ? 'above' : 'below');
}
/* A row's total OVER THE COVERED BUCKETS ONLY.
 *
 * $r['total'] is whatever the page handed in, and normalize() fixes it BEFORE
 * it masks the gap months to null. So on any window overlapping a recording
 * gap -- which, with June 2021 to December 2024 missing, is most of them --
 * the row totals count months the grand total does not, and a share built
 * from the two is wrong. It showed up as Doors 96.7% and Air Conditioning
 * 34.7% of the same grand: 131% between two rows.
 *
 * Anything expressing a row as a PERCENTAGE OF THE GRAND uses this. Threshold
 * tests that only ask "is this row big enough to bother analysing" keep using
 * $r['total'], where the difference cannot produce a contradiction.
 */
function iss_ins_row_total($r) {
    if (!isset($r['values']) || !is_array($r['values'])) {
        return isset($r['total']) ? (int)$r['total'] : 0;
    }
    $t = 0;
    foreach ($r['values'] as $v) { if ($v !== null && is_numeric($v)) { $t += $v; } }
    return $t;
}
function iss_ins_relpct_xy($obs, $exp)  { return ($exp > 0) ? iss_ins_relpct($obs / $exp) : null; }
function iss_ins_relword_xy($obs, $exp) { return ($exp > 0) ? iss_ins_relword($obs / $exp) : ''; }
function iss_ins_halves($a) {
    $idx = array();
    foreach ($a as $i => $v) { if ($v !== null && is_numeric($v)) { $idx[] = $i; } }
    $n = count($idx);
    if ($n < 4) { return null; }
    $cut = (int)floor($n / 2);
    $first = 0; $second = 0;
    for ($i = 0; $i < $cut; $i++)  { $first  += $a[$idx[$i]]; }
    for ($i = $cut; $i < $n; $i++) { $second += $a[$idx[$i]]; }
    return array(
        'first' => $first, 'second' => $second,
        'first_n' => $cut, 'second_n' => $n - $cut,
        'from' => $idx[0], 'mid' => $idx[$cut], 'to' => $idx[$n - 1],
    );
}

/* ---------------------------------------------------------------------------
 * 3. FINDINGS  --  the analysis proper. No model involved.
 * -------------------------------------------------------------------------*/
function iss_insight_findings($c) {
    $F = array(); $n = 0;
    $unit    = $c['report']['unit'];
    $buckets = $c['buckets'];
    $bt      = $c['totals']['by_bucket'];
    $grand   = (int)$c['totals']['grand'];
    $covered = iss_ins_clean($bt);
    $ncov    = count($covered);

    /* -- F: volume ------------------------------------------------------- */
    $n++;
    $per = $ncov ? round(array_sum($covered) / $ncov, 1) : 0;
    $F[] = array(
        'id' => 'F' . $n, 'kind' => 'volume', 'severity' => 'info',
        'text' => ($ncov <= 1
                  ? sprintf('%s %s in %s.', number_format($grand), iss_ins_unit($c, $grand),
                            (count($buckets) ? $buckets[0] : 'this window'))
                  : sprintf('%s %s across %d recorded %s (%s per %s).',
                    number_format($grand), iss_ins_unit($c, $grand), $ncov,
                    iss_ins_colword($c, $ncov), $per, iss_ins_colword($c, 1))),
        'facts' => array('total' => $grand, 'buckets_covered' => $ncov, 'mean_per_bucket' => $per),
    );

    /* -- F: prior-period comparison -------------------------------------- */
    if (is_array($c['comparison']) && isset($c['comparison']['grand'])) {
        $prev = (int)$c['comparison']['grand'];
        $lbl  = isset($c['comparison']['label']) ? $c['comparison']['label'] : 'prior period';
        $pcov = isset($c['comparison']['buckets_covered']) ? (int)$c['comparison']['buckets_covered'] : $ncov;
        if ($prev > 0) {
            /* Like for like or not at all. Comparing a 5-month year against a
               12-month one is how a recording gap gets reported as a 50%
               improvement. If the bucket counts differ, compare RATES and say
               so in the sentence itself. */
            if ($pcov > 0 && $ncov > 0 && $pcov !== $ncov) {
                $ra = $grand / $ncov; $rb = $prev / $pcov;
                $delta = iss_ins_pct($ra - $rb, $rb);
                $n++;
                $F[] = array(
                    'id' => 'F' . $n, 'kind' => 'vs_prior',
                    'severity' => (abs($delta) >= 20 ? 'watch' : 'info'),
                    'text' => sprintf('Compared with %s on a per-%s basis (%d recorded %ss here versus %d there, so raw totals are NOT comparable): %s per %s versus %s, %s%%.',
                              $lbl, strtolower($c['dimensions']['col']), $ncov,
                              strtolower($c['dimensions']['col']), $pcov,
                              round($ra, 1), strtolower($c['dimensions']['col']), round($rb, 1),
                              ($delta >= 0 ? '+' : '') . $delta),
                    'facts' => array('rate_current' => round($ra, 2), 'rate_previous' => round($rb, 2),
                                     'change_pct' => $delta, 'previous_label' => $lbl,
                                     'buckets_current' => $ncov, 'buckets_previous' => $pcov,
                                     'totals_comparable' => false),
                );
            } else {
                $delta = iss_ins_pct($grand - $prev, $prev);
                $n++;
                $F[] = array(
                    'id' => 'F' . $n, 'kind' => 'vs_prior',
                    'severity' => (abs($delta) >= 20 ? 'watch' : 'info'),
                    'text' => sprintf('%s%% versus %s (%s vs %s %s).',
                              ($delta >= 0 ? '+' : '') . $delta, $lbl,
                              number_format($grand), number_format($prev), $unit),
                    'facts' => array('current' => $grand, 'previous' => $prev,
                                     'change_pct' => $delta, 'previous_label' => $lbl,
                                     'totals_comparable' => true),
                );
            }
        }
    }

    /* -- F: direction ----------------------------------------------------- */
    $h = iss_ins_halves($bt);
    if ($h !== null && $grand >= 12) {
        $fa = $h['first'] / max(1, $h['first_n']);
        $sa = $h['second'] / max(1, $h['second_n']);
        $chg = ($fa > 0) ? iss_ins_pct($sa - $fa, $fa) : null;
        $slope = iss_ins_slope($bt);
        if ($chg !== null && abs($chg) >= 15) {
            $n++;
            $F[] = array(
                'id' => 'F' . $n, 'kind' => 'trend',
                'severity' => ($chg >= 25 ? 'alert' : ($chg <= -25 ? 'info' : 'watch')),
                'text' => sprintf('%s over the period: %s per %s in the first half versus %s in the second (%s%%).',
                          ($chg > 0 ? 'Rising' : 'Falling'), round($fa, 1),
                          strtolower($c['dimensions']['col']), round($sa, 1),
                          ($chg >= 0 ? '+' : '') . $chg),
                'facts' => array('first_half_avg' => round($fa, 1), 'second_half_avg' => round($sa, 1),
                                 'change_pct' => $chg, 'slope' => ($slope === null ? null : round($slope, 3)),
                                 'split_at' => isset($buckets[$h['mid']]) ? $buckets[$h['mid']] : ''),
            );
        }
    }

    /* -- F: peak bucket, flagged only if it stands out of its own baseline */
    if ($ncov >= 3) {
        $max = max($covered); $mi = null;
        foreach ($bt as $i => $v) { if ($v !== null && $v == $max) { $mi = $i; break; } }
        $med = iss_ins_median($bt); $mad = iss_ins_mad($bt);
        $thr = ($mad > 0) ? ($med + 2 * $mad) : ($med * 2);
        $out = ($max >= 3 && $max > $thr);
        $n++;
        $F[] = array(
            'id' => 'F' . $n, 'kind' => 'peak', 'severity' => ($out ? 'alert' : 'info'),
            'text' => sprintf('Highest %s was %s with %s %s%s.',
                      strtolower($c['dimensions']['col']),
                      isset($buckets[$mi]) ? $buckets[$mi] : '?', (int)$max, $unit,
                      ($out ? sprintf(' -- an outlier against a median of %s', $med) : '')),
            'facts' => array('bucket' => isset($buckets[$mi]) ? $buckets[$mi] : '', 'value' => (int)$max,
                             'median' => $med, 'is_outlier' => $out),
        );
    }

    /* -- F: concentration (60% -- same threshold the tables highlight at) -- */
    $rows = $c['rows'];
    if (count($rows) >= 3 && $grand > 0) {
        /* Covered-only totals, so the share and the grand are counting the
           same months -- see iss_ins_row_total. Sorted on the same figure it
           reports, or the ranking and the numbers could disagree. */
        $sorted = array();
        foreach ($rows as $r) { $r['total'] = iss_ins_row_total($r); $sorted[] = $r; }
        usort($sorted, 'iss_ins_cmp_total');
        $cum = 0; $k = 0; $top = array();
        foreach ($sorted as $r) {
            if ($cum >= $grand * 0.6) { break; }
            $cum += $r['total']; $k++;
            $top[] = $r['label'] . ' (' . $r['total'] . ')';
        }
        $n++;
        $F[] = array(
            'id' => 'F' . $n, 'kind' => 'concentration',
            'severity' => ($k <= max(2, count($rows) * 0.2) ? 'watch' : 'info'),
            /* Phrased so subject-verb agreement holds whichever way the row
               word falls -- "1 of 4 equipment account for" was wrong, and so
               would be "3 of 7 cars accounts for". */
            'text' => sprintf('%s%% of the total sits with %d of %d %s: %s.',
                      iss_ins_pct($cum, $grand), $k, count($rows),
                      iss_ins_rowword($c, count($rows)),
                      implode(', ', array_slice($top, 0, 5))),
            'facts' => array('n_rows_to_60pct' => $k, 'n_rows_total' => count($rows),
                             'share_pct' => iss_ins_pct($cum, $grand),
                             'top' => array_slice($top, 0, 5)),
        );
    }

    $nrows = count($rows);

    /* -- F: per-row spikes -- the finding ops actually acts on ------------ */
    $spikes = array();
    foreach ($rows as $r) {
        if ($r['total'] < 4) { continue; }
        $v = $r['values'];
        foreach ($v as $i => $val) {
            if (isset($bt[$i]) && $bt[$i] === null) { continue; }   /* gap bucket */
            $rest = $v; unset($rest[$i]);
            $med = iss_ins_median($rest); $mad = iss_ins_mad($rest);
            if ($med === null) { continue; }
            $thr = ($mad > 0) ? ($med + 2.5 * $mad) : max(2, $med * 3);
            if ($val >= 3 && $val > $thr) {
                $spikes[] = array('row' => $r['label'], 'bucket' => isset($buckets[$i]) ? $buckets[$i] : '',
                                  'value' => (int)$val, 'baseline' => $med, 'excess' => $val - $med);
            }
        }
    }
    usort($spikes, 'iss_ins_cmp_excess');
    $seen = array(); $keep = array();
    foreach ($spikes as $s) {
        if (isset($seen[$s['row']])) { continue; }   /* worst month per row only */
        $seen[$s['row']] = true; $keep[] = $s;
        if (count($keep) >= 4) { break; }
    }
    $spikes = $keep;
    foreach ($spikes as $s) {
        $n++;
        $F[] = array(
            'id' => 'F' . $n, 'kind' => 'spike', 'severity' => 'alert',
            'text' => sprintf('%s spiked to %d %s in %s against a usual %s.',
                      $s['row'], $s['value'], $unit, $s['bucket'], $s['baseline']),
            'facts' => $s,
        );
    }

    /* -- F: emerging / receding rows -------------------------------------- */
    /* With one row the row IS the total, so emerging/receding would repeat the
       trend finding back in different words -- as on the by-category history,
       where the category is the page filter and there is no second dimension. */
    $emerging = array(); $receding = array();
    foreach (($nrows > 1 ? $rows : array()) as $r) {
        if ($r['total'] < 6) { continue; }
        $hh = iss_ins_halves($r['values']);
        if ($hh === null) { continue; }
        $a = $hh['first'] / max(1, $hh['first_n']);
        $b = $hh['second'] / max(1, $hh['second_n']);
        if ($a <= 0 && $b <= 0) { continue; }
        $chg = ($a > 0) ? iss_ins_pct($b - $a, $a) : 100.0;
        $item = array('row' => $r['label'], 'first_half_avg' => round($a, 2),
                      'second_half_avg' => round($b, 2), 'change_pct' => $chg, 'total' => $r['total']);
        if ($chg >= 50)  { $emerging[] = $item; }
        if ($chg <= -50) { $receding[] = $item; }
    }
    usort($emerging, 'iss_ins_cmp_chg_desc');
    usort($receding, 'iss_ins_cmp_chg_asc');
    if (count($emerging)) {
        $n++; $lst = array();
        foreach (array_slice($emerging, 0, 3) as $e) { $lst[] = $e['row'] . ' (+' . $e['change_pct'] . '%)'; }
        $F[] = array('id' => 'F' . $n, 'kind' => 'emerging', 'severity' => 'watch',
            'text' => 'Worsening in the second half: ' . implode(', ', $lst) . '.',
            'facts' => array_slice($emerging, 0, 3));
    }
    if (count($receding)) {
        $n++; $lst = array();
        foreach (array_slice($receding, 0, 3) as $e) { $lst[] = $e['row'] . ' (' . $e['change_pct'] . '%)'; }
        $F[] = array('id' => 'F' . $n, 'kind' => 'receding', 'severity' => 'info',
            'text' => 'Improving in the second half: ' . implode(', ', $lst) . '.',
            'facts' => array_slice($receding, 0, 3));
    }

    /* -- F: dormant rows --------------------------------------------------- */
    $zero = array();
    foreach ($rows as $r) { if ($r['total'] == 0) { $zero[] = $r['label']; } }
    if (count($zero) && count($zero) < count($rows)) {
        $n++;
        $F[] = array('id' => 'F' . $n, 'kind' => 'dormant', 'severity' => 'info',
            'text' => sprintf('%d %s recorded none at all: %s.', count($zero),
                      iss_ins_rowword($c, count($zero)), implode(', ', array_slice($zero, 0, 8))),
            'facts' => array('count' => count($zero), 'rows' => array_slice($zero, 0, 12)));
    }

    /* -- F: severity mix and shift ---------------------------------------- */
    if (is_array($c['severity']) && isset($c['severity']['series']) && is_array($c['severity']['series'])) {
        $tot = array(); $all = 0;
        foreach ($c['severity']['series'] as $lvl => $ser) {
            $s = array_sum(iss_ins_clean($ser)); $tot[$lvl] = $s; $all += $s;
        }
        if ($all > 0) {
            $mix = array();
            foreach ($tot as $lvl => $s) { $mix[] = $lvl . ' ' . iss_ins_pct($s, $all) . '%'; }
            $n++;
            $F[] = array('id' => 'F' . $n, 'kind' => 'severity_mix', 'severity' => 'info',
                'text' => 'Severity mix: ' . implode(', ', $mix) . '.',
                'facts' => array('counts' => $tot, 'total' => $all));

            $keys = array_keys($tot); sort($keys);
            $worst = end($keys);
            $hh = iss_ins_halves($c['severity']['series'][$worst]);
            if ($hh !== null) {
                $a = $hh['first'] / max(1, $hh['first_n']);
                $b = $hh['second'] / max(1, $hh['second_n']);
                if ($a > 0 && abs(iss_ins_pct($b - $a, $a)) >= 30) {
                    $n++;
                    $F[] = array('id' => 'F' . $n, 'kind' => 'severity_shift',
                        'severity' => ($b > $a ? 'alert' : 'info'),
                        'text' => sprintf('%s severity %s in the second half (%s to %s per %s).',
                                  $worst, ($b > $a ? 'increased' : 'decreased'),
                                  round($a, 1), round($b, 1), strtolower($c['dimensions']['col'])),
                        'facts' => array('level' => $worst, 'first_half_avg' => round($a, 2),
                                         'second_half_avg' => round($b, 2)));
                }
            }
        }
    }

    /* -- F: coverage. Always last, always emitted when a gap touches this
     *      period. Without it the narrator will read a gap as good news. */
    if (count($c['coverage']['uncovered'])) {
        $n++;
        $F[] = array('id' => 'F' . $n, 'kind' => 'coverage', 'severity' => 'caveat',
            'text' => sprintf('No data was recorded for %d %s in this range (%s). These are UNKNOWN, not zero, and are excluded from every figure above.',
                      count($c['coverage']['uncovered']),
                      iss_ins_colword($c, count($c['coverage']['uncovered'])),
                      implode(', ', array_slice($c['coverage']['uncovered'], 0, 10))),
            'facts' => array('uncovered' => $c['coverage']['uncovered']));
    }

    /* -- F: data quality --------------------------------------------------- */
    $q = $c['quality'];
    $qn = array();
    if (!empty($q['suggested_rows']))     { $qn[] = $q['suggested_rows'] . ' rows carry a machine-suggested category rather than a recorded one'; }
    if (!empty($q['uncategorized_rows'])) { $qn[] = $q['uncategorized_rows'] . ' rows are uncategorized'; }
    if (!empty($q['notes']) && is_array($q['notes'])) { $qn = array_merge($qn, $q['notes']); }
    if (count($qn)) {
        $n++;
        $F[] = array('id' => 'F' . $n, 'kind' => 'quality', 'severity' => 'caveat',
            'text' => 'Data quality: ' . implode('; ', $qn) . '.',
            'facts' => $q);
    }
    return $F;
}

function iss_ins_cmp_total($a, $b)     { return ($b['total'] == $a['total']) ? 0 : (($b['total'] < $a['total']) ? -1 : 1); }
function iss_ins_cmp_excess($a, $b)    { return ($b['excess'] == $a['excess']) ? 0 : (($b['excess'] < $a['excess']) ? -1 : 1); }
function iss_ins_cmp_chg_desc($a, $b)  { return ($b['change_pct'] == $a['change_pct']) ? 0 : (($b['change_pct'] < $a['change_pct']) ? -1 : 1); }
function iss_ins_cmp_chg_asc($a, $b)   { return ($a['change_pct'] == $b['change_pct']) ? 0 : (($a['change_pct'] < $b['change_pct']) ? -1 : 1); }

/* ---------------------------------------------------------------------------
 * 3b. SCORE LINE  --  the size of the thing, before the pattern.
 *
 * A summary that opens on a pattern before it has said how big the thing is
 * reads as commentary without a subject. So one line runs ahead of the
 * findings in the executive block.
 *
 * A short paragraph, not a strip of label/value pairs. The pages carrying this
 * panel already have a KPI tile row above the table, and a second tile row
 * inside the panel reads as the same furniture twice; prose sentences at
 * reading size do not compete with it.
 *
 * It states the total, the top few rows with their counts AND their shares,
 * and the heaviest period -- plus the three things no tile carries: the rate,
 * how many periods actually hold records, and the change against last year.
 * The shares are the part that earns its place next to the tiles: a tile
 * naming the highest row never says whether that row is 39% of everything or
 * 3%, and those mean opposite things.
 *
 * Computed straight from the normalised context rather than lifted out of the
 * findings, which matters twice over. It cannot disagree with the tiles above
 * it, and it survives a filter narrow enough that no pattern finding fires at
 * all -- the case that previously left the reader with an empty panel and no
 * way to tell that from the feature being broken.
 *
 * Returns '' when it has nothing the tiles do not already say.
 * -------------------------------------------------------------------------*/
function iss_insight_scoreboard($c, $F) {
    $grand   = (int)$c['totals']['grand'];
    if ($grand <= 0) { return ''; }
    $bt      = $c['totals']['by_bucket'];
    $covered = iss_ins_clean($bt);
    $ncov    = count($covered);
    $parts   = array();

    /* -- rate, coverage and the year-on-year change ------------------------
       The bare total is on a tile already. What is NOT on any tile is the
       rate, the number of months that actually hold records, and the change
       against last year -- and the middle one is what stops a recording gap
       being read as a quiet year. */
    $rate = ''; $yoy = '';
    if ($ncov > 1) {
        $rate = sprintf('%s %s over %d recorded %s, about %s a %s',
                number_format($grand), iss_ins_unit($c, $grand), $ncov,
                iss_ins_colword($c, $ncov),
                round(array_sum($covered) / $ncov, 1), iss_ins_colword($c, 1));
    }
    /* Folded in ONLY when the two totals are comparable. When they are not,
       the vs_prior sentence carries it with its warning attached; a bare
       "6% up on last year" at the top of a summary is exactly the figure that
       gets quoted without the fact that one side is short two months. */
    foreach ($F as $f) {
        if ($f['kind'] !== 'vs_prior' || empty($f['facts']['totals_comparable'])) { continue; }
        $d = $f['facts']['change_pct'];
        $yoy = sprintf('%s%% %s %s', abs($d),
               ($d > 0 ? 'more than' : ($d < 0 ? 'fewer than' : 'level with')),
               $f['facts']['previous_label']);
    }
    if ($rate !== '' || $yoy !== '') {
        if ($rate === '') {
            $rate = number_format($grand) . ' ' . iss_ins_unit($c, $grand);
        }
        $parts[] = $rate . ($yoy !== '' ? ' -- ' . $yoy : '') . '.';
    }

    /* -- the top rows, with counts AND shares -----------------------------
       Skipped on a single-row page (the row IS the total) and under ten
       records (nothing to rank). */
    $rows = is_array($c['rows']) ? $c['rows'] : array();
    if (count($rows) > 1 && $grand >= 10) {
        $sorted = array(); $live = 0;
        foreach ($rows as $r) {
            $r['total'] = iss_ins_row_total($r);
            if ($r['total'] > 0) { $live++; }
            $sorted[] = $r;
        }
        usort($sorted, 'iss_ins_cmp_total');
        if ($live > 1 && $sorted[0]['total'] > 0) {
            $top = array();
            foreach (array_slice($sorted, 0, 3) as $r) {
                if ($r['total'] <= 0) { continue; }
                /* Count inside the brackets: a label ending in a digit
                   ("Car 52") ran straight into a count placed outside them. */
                $top[] = $r['label'] . ' (' . number_format($r['total'])
                       . ', ' . iss_ins_pct($r['total'], $grand) . '%)';
            }
            $lead = iss_ins_pct($sorted[0]['total'], $grand);
            /* Twice an even split is the right test at seventy rows and an
               impossible one at two, where an even split is already 50%.
               Capped: a row holding over 40% of everything stands out
               whatever it is measured against. A tie for first is flat
               however high it sits.

               The names are listed either way -- he asked for them -- but the
               lead-in changes, because on the live by-car report the top three
               came out tied at 2.9% apiece and printing them under "most of
               them" invites reading a coin-flip as the worst offender. */
            $bar  = min(2 * (100 / $live), 40);
            $tied = (isset($sorted[2]) && $sorted[2]['total'] == $sorted[0]['total']);
            if (count($top)) {
                $parts[] = ($lead < $bar || $tied)
                    ? sprintf('No single %s dominates -- the highest are %s.',
                      iss_ins_rowword($c, 1), implode(', ', $top))
                    : sprintf('Most of them: %s.', implode(', ', $top));
            }
        }
    }

    /* -- heaviest period ---------------------------------------------------
       On the by-car report this is also a tile. On the equipment report it is
       not -- that slot holds the average per affected type -- so leaving it
       out cost the summary a figure on one of the two pages. */
    if ($ncov >= 2) {
        $max = max($covered); $mi = null;
        foreach ($bt as $i => $v) { if ($v !== null && $v == $max) { $mi = $i; break; } }
        if ($mi !== null && $max > 0) {
            $parts[] = sprintf('Heaviest %s: %s (%d).', iss_ins_colword($c, 1),
                       (isset($c['buckets'][$mi]) ? $c['buckets'][$mi] : '?'), (int)$max);
        }
    }
    return implode(' ', $parts);
}

/* ---------------------------------------------------------------------------
 * 4. DETERMINISTIC RENDERER  --  same output shape as the model
 * -------------------------------------------------------------------------*/
function iss_insight_render_offline($c, $F) {
    $byKind = array();
    foreach ($F as $f) { $byKind[$f['kind']][] = $f; }

    $head = isset($byKind['volume'][0]) ? $byKind['volume'][0]['text'] : '';
    $sum  = array($head);
    foreach (array('vs_prior', 'trend', 'peak', 'concentration') as $k) {
        if (isset($byKind[$k][0])) { $sum[] = $byKind[$k][0]['text']; }
    }

    $findings = array(); $watch = array(); $caveats = array();
    foreach ($F as $f) {
        if ($f['severity'] === 'caveat') { $caveats[] = $f['text']; continue; }
        if (in_array($f['kind'], array('volume', 'vs_prior'), true)) { continue; }
        $findings[] = array(
            'title'    => ucfirst(str_replace('_', ' ', $f['kind'])),
            'detail'   => $f['text'],
            'priority' => ($f['severity'] === 'alert' ? 'high' : ($f['severity'] === 'watch' ? 'medium' : 'low')),
            'evidence' => array($f['id']),
        );
        if ($f['severity'] === 'alert' && isset($f['facts']['row'])) {
            $watch[] = array('subject' => $f['facts']['row'], 'why' => $f['text']);
        }
    }
    $lead = '';
    foreach (array('spike', 'trend', 'severity_shift', 'peak', 'concentration') as $k) {
        if (isset($byKind[$k][0])) { $lead = $byKind[$k][0]['text']; break; }
    }
    if ($lead === '') { $lead = $head; }
    if (strlen($lead) > 92) {
        $cut  = strrpos(substr($lead, 0, 92), ' ');
        $lead = rtrim(substr($lead, 0, $cut === false ? 92 : $cut), " .,;") . '...';
    }
    return array(
        'headline'  => $c['report']['title'] . ' -- ' . $lead,
        'summary'   => implode(' ', $sum),
        'findings'  => $findings,
        'watchlist' => $watch,
        'caveats'   => $caveats,
        'questions' => array(),
        'source'    => 'deterministic',
    );
}

/* ---------------------------------------------------------------------------
 * 5. PROMPT
 * -------------------------------------------------------------------------*/
function iss_insight_system_prompt() {
    return
"You are a reliability analyst writing the interpretation section of an internal rail operations report for MRT-3 Line 3.\n\n" .
"You are given FINDINGS that have already been computed from the data. Your job is to turn them into clear operational prose.\n\n" .
"Hard rules:\n" .
"1. Use ONLY numbers that appear in the findings. Do not compute, estimate, round differently, or introduce any figure of your own.\n" .
"2. Use the counting unit exactly as given. 'Car-level failures' and 'incidents' are different things; never swap them or say 'incidents' when the unit says otherwise.\n" .
"3. Periods listed under a coverage finding are UNKNOWN, not zero. Never describe a coverage gap as an improvement, a decline, or a quiet period.\n" .
"4. Never assert a cause. You may raise at most one hypothesis, and it must be worded as a question or as 'worth checking whether'.\n" .
"5. Categories described as machine-suggested are suggestions, not records. Say so if you use them.\n" .
"6. Do not recommend engineering actions or maintenance interventions. Point to what merits a look; the engineers decide.\n" .
"7. If the findings are thin, say so plainly and write less. Do not pad.\n" .
"8. Express every comparison as a PERCENTAGE, never as a multiplier or a ratio. Write '107% above expected', not '2.1x expected', 'twice as many' or 'a ratio of 2.1'. Keep the raw counts beside it.\n" .
"9. Statistical notation may appear only in parentheses at the END of a sentence, never as the sentence's claim. Say what it means in ordinary words first: 'clearly beyond normal variation (z=4.6)', not 'z=4.6, which is significant'. The same goes for correlation and confidence figures.\n\n" .
"Tone: factual, direct, no marketing language, no exclamation marks. Filipino English conventions are fine. This text is printed in an official report.\n\n" .
"This report is read by two audiences, so write BOTH in a single reply.\n" .
"  technical  -- for the engineers and controllers who see the table. Keep every figure and\n" .
"                every statistic, but say what each one MEANS before quoting it. Detail is\n" .
"                wanted here; notation standing in place of a sentence is not.\n" .
"  executive  -- for readers who will see only your words. No z-scores, no p-values, no\n" .
"                jargon, no equipment codes. Short sentences. Say what it means and what\n" .
"                it implies for the fleet.\n\n" .
"The executive text must be plainer and shorter, NEVER vaguer-and-more-confident. Dropping\n" .
"a z-score is right. Dropping a coverage gap, a suggested-category caveat, or a hedge like\n" .
"'worth checking' is not -- those are exactly what stops a number being over-read by someone\n" .
"who cannot see the table. Carry every caveat into the executive text in plain words, and\n" .
"turn statistical hedges into English ones ('clearly', 'probably', 'possibly') rather than\n" .
"deleting them. Never state something in the executive text that the technical text hedges.\n\n" .
"The executive text must OPEN WITH THE SCORE LINE supplied under SCOREBOARD below. It is\n" .
"already computed -- quote it, do not recompute it, do not expand it. Only after it does the\n" .
"executive text say what the pattern is. A summary that opens on a pattern before it has\n" .
"said the size of the thing reads as commentary without a subject.\n\n" .
"Do NOT restate the plain total, the highest row's raw count, or the heaviest period as\n" .
"figures of their own. The report page already shows those on tiles above the table and in\n" .
"the printout's Key Figures block; a third copy is padding. The score line carries the rate,\n" .
"the coverage and the shares precisely because no tile does.\n\n" .
"Reply with ONE JSON object and nothing else -- no markdown fence, no preamble:\n" .
'{"headline":"<=100 chars","summary":"2-4 sentences","findings":[{"title":"short","detail":"1-3 sentences","priority":"high|medium|low","evidence":["F1"]}],"watchlist":[{"subject":"","why":""}],"caveats":["..."],"questions":["..."],"executive":{"bottom_line":"one sentence, the single most consequential thing","summary":"2-3 plain sentences","points":["..."],"caveats":["..."]}}' . "\n\n" .
"Every findings[] entry must cite at least one real finding id in evidence. Maximum 6 findings, 4 watchlist items, 3 questions, 5 executive points.";
}

function iss_insight_user_prompt($c, $F, $rev = null) {
    $p  = "REPORT: " . $c['report']['title'] . "\n";
    $p .= "COUNTING UNIT: " . $c['report']['unit'] . "\n";
    $p .= "PERIOD: " . $c['period']['from'] . " to " . $c['period']['to'] . " by " . $c['period']['grain'] . "\n";
    $p .= "BREAKDOWN: " . $c['dimensions']['row'] . " x " . $c['dimensions']['col'] . "\n";
    if (is_array($c['filters']) && count($c['filters'])) {
        $fl = array();
        foreach ($c['filters'] as $k => $v) { if ($v !== null && $v !== '' && $v !== 'all') { $fl[] = $k . '=' . $v; } }
        if (count($fl)) { $p .= "ACTIVE FILTERS: " . implode(', ', $fl) . "\n"; }
    }
    /* Row labels are aliased out of the findings before they leave the network
       on a sensitive report (td_history, where a row is a named person). The
       scoreboard is built from $c, which is NOT aliased, so it has to go
       through the same map or it would hand straight back the names the
       aliasing just removed. $rev is token => real, so it inverts. */
    $sb = iss_insight_scoreboard($c, $F);
    if ($sb !== '') {
        $map = array();
        if (is_array($rev)) {
            foreach ($rev as $tok => $real) { $map[$real] = $tok; }
            uksort($map, 'iss_ins_cmp_len');
        }
        foreach ($map as $real => $tok) { $sb = str_replace($real, $tok, $sb); }
        $p .= "\nSCOREBOARD -- already computed. The executive text opens with this line:\n"
            . '  ' . $sb . "\n";
    }

    $p .= "\nFINDINGS:\n";
    foreach ($F as $f) {
        $p .= $f['id'] . ' [' . $f['kind'] . '/' . $f['severity'] . '] ' . $f['text'] . "\n";
    }
    $p .= "\nWrite the interpretation.";
    return $p;
}

/* ---------------------------------------------------------------------------
 * 6. PROVIDERS
 * -------------------------------------------------------------------------*/
function iss_insight_http($url, $headers, $body, $cfg) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, (int)$cfg['timeout']);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($res === false) { return array(null, 0, $err); }
        return array($res, $code, '');
    }
    $ctx = stream_context_create(array('http' => array(
        'method' => 'POST', 'header' => implode("\r\n", $headers),
        'content' => $body, 'timeout' => (int)$cfg['timeout'], 'ignore_errors' => true,
    )));
    $res = @file_get_contents($url, false, $ctx);
    if ($res === false) { return array(null, 0, 'stream request failed'); }
    return array($res, 200, '');
}

function iss_insight_call($sys, $usr, $cfg) {
    if ($cfg['provider'] === 'anthropic') {
        $url = 'https://api.anthropic.com/v1/messages';
        $hdr = array('content-type: application/json',
                     'x-api-key: ' . $cfg['api_key'],
                     'anthropic-version: 2023-06-01');
        $body = json_encode(array(
            'model' => $cfg['model'], 'max_tokens' => (int)$cfg['max_tokens'],
            'temperature' => 0.2, 'system' => $sys,
            'messages' => array(array('role' => 'user', 'content' => $usr)),
        ));
        list($res, $code, $err) = iss_insight_http($url, $hdr, $body, $cfg);
        if ($res === null) { return array(null, $err); }
        $j = json_decode($res, true);
        if (!is_array($j) || !isset($j['content'][0]['text'])) {
            return array(null, 'unexpected response (HTTP ' . $code . ')');
        }
        $txt = '';
        foreach ($j['content'] as $blk) {
            if (isset($blk['type']) && $blk['type'] === 'text') { $txt .= $blk['text']; }
        }
        return array($txt, '');
    }

    if ($cfg['provider'] === 'openai_compatible') {
        /* Ollama, llama.cpp server, vLLM, LM Studio, OpenAI, Azure -- same shape. */
        $url = rtrim($cfg['base_url'], '/') . '/chat/completions';
        $hdr = array('content-type: application/json');
        if (!empty($cfg['api_key'])) { $hdr[] = 'authorization: Bearer ' . $cfg['api_key']; }
        $body = json_encode(array(
            'model' => $cfg['model'], 'temperature' => 0.2,
            'max_tokens' => (int)$cfg['max_tokens'],
            'messages' => array(
                array('role' => 'system', 'content' => $sys),
                array('role' => 'user',   'content' => $usr),
            ),
        ));
        list($res, $code, $err) = iss_insight_http($url, $hdr, $body, $cfg);
        if ($res === null) { return array(null, $err); }
        $j = json_decode($res, true);
        if (!is_array($j) || !isset($j['choices'][0]['message']['content'])) {
            return array(null, 'unexpected response (HTTP ' . $code . ')');
        }
        return array($j['choices'][0]['message']['content'], '');
    }
    return array(null, 'no provider configured');
}

/* ---------------------------------------------------------------------------
 * 7. PARSE, VALIDATE, AUDIT
 * -------------------------------------------------------------------------*/
function iss_insight_parse($txt) {
    $t = trim($txt);
    $t = preg_replace('/^```(?:json)?\s*/i', '', $t);
    $t = preg_replace('/\s*```$/', '', $t);
    $a = strpos($t, '{'); $b = strrpos($t, '}');
    if ($a === false || $b === false || $b <= $a) { return null; }
    $j = json_decode(substr($t, $a, $b - $a + 1), true);
    return is_array($j) ? $j : null;
}

/* Pull every number the findings legitimately contain, so anything the model
   states that is not in this set can be flagged rather than trusted. */
function iss_insight_allowed_numbers($F, $c = null) {
    $set = array();
    $collect = array();
    /* The scoreboard is handed to the model in the prompt and the executive
       text is REQUIRED to open with it, so its figures have to be allowed
       here too. They are computed in PHP exactly like the findings are; they
       are simply not stored in $F. Without this the audit would flag the very
       numbers the prompt asked for and every model executive block would fail
       it silently, falling back to the deterministic one -- which looks, from
       the page, like the model quietly stopped working. */
    $stack = array($F);
    if (is_array($c) && function_exists('iss_insight_scoreboard')) {
        $stack[] = iss_insight_scoreboard($c, $F);
    }
    while (count($stack)) {
        $cur = array_pop($stack);
        if (is_array($cur)) { foreach ($cur as $v) { $stack[] = $v; } continue; }
        if (is_numeric($cur)) { $collect[] = (float)$cur; }
        elseif (is_string($cur)) {
            if (preg_match_all('/-?\d+(?:\.\d+)?/', $cur, $m)) {
                foreach ($m[0] as $x) { $collect[] = (float)$x; }
            }
        }
    }
    foreach ($collect as $v) {
        $set[(string)$v] = true;
        $set[(string)round($v)] = true;
        $set[(string)round($v, 1)] = true;
        $set[(string)abs($v)] = true;
    }
    return $set;
}

function iss_insight_audit($out, $F, $c = null) {
    $ids = array();
    foreach ($F as $f) { $ids[$f['id']] = true; }
    $allowed = iss_insight_allowed_numbers($F, $c);
    $flags = array();

    if (isset($out['findings']) && is_array($out['findings'])) {
        $keep = array();
        foreach ($out['findings'] as $f) {
            $ev = isset($f['evidence']) && is_array($f['evidence']) ? $f['evidence'] : array();
            $ok = false;
            foreach ($ev as $e) { if (isset($ids[$e])) { $ok = true; break; } }
            if (!$ok) { $flags[] = 'dropped an uncited finding: ' . (isset($f['title']) ? $f['title'] : '?'); continue; }
            $keep[] = $f;
        }
        $out['findings'] = $keep;
    }

    $blob = '';
    foreach (array('headline', 'summary') as $k) { if (isset($out[$k])) { $blob .= ' ' . $out[$k]; } }
    if (isset($out['findings'])) { foreach ($out['findings'] as $f) { $blob .= ' ' . (isset($f['detail']) ? $f['detail'] : ''); } }
    /* The executive block is audited on exactly the same terms. A plainer
       register is otherwise the easiest place for an invented figure to
       survive, and it is the block that reaches the reader who cannot check
       it against the table. */
    if (isset($out['executive']) && is_array($out['executive'])) {
        foreach (array('bottom_line', 'summary') as $k) {
            if (isset($out['executive'][$k])) { $blob .= ' ' . $out['executive'][$k]; }
        }
        foreach (array('points', 'caveats') as $k) {
            if (!empty($out['executive'][$k]) && is_array($out['executive'][$k])) {
                foreach ($out['executive'][$k] as $x) { $blob .= ' ' . $x; }
            }
        }
    }
    if (preg_match_all('/-?\d+(?:\.\d+)?/', $blob, $m)) {
        $bad = array();
        foreach (array_unique($m[0]) as $x) {
            $v = (float)$x;
            if ($v >= 1900 && $v <= 2100) { continue; }           /* years */
            if (!isset($allowed[(string)$v]) && !isset($allowed[(string)round($v, 1)])) { $bad[] = $x; }
        }
        if (count($bad)) { $flags[] = 'figures not present in the findings: ' . implode(', ', $bad); }
    }
    $out['audit'] = $flags;
    return $out;
}

/* ---------------------------------------------------------------------------
 * 8. LABEL ALIASING  --  for personnel-scoped reports, or any deployment
 *    where row labels should not leave the network.
 * -------------------------------------------------------------------------*/
function iss_insight_alias(&$F, $c) {
    $map = array(); $rev = array(); $i = 0;
    foreach ($c['rows'] as $r) {
        $i++; $tok = 'R' . $i;
        $map[$r['label']] = $tok; $rev[$tok] = $r['label'];
    }
    uksort($map, 'iss_ins_cmp_len');
    foreach ($F as $k => $f) {
        $t = $f['text'];
        foreach ($map as $real => $tok) { $t = str_replace($real, $tok, $t); }
        $F[$k]['text'] = $t;
    }
    return $rev;
}
function iss_ins_cmp_len($a, $b) { return strlen($b) - strlen($a); }

function iss_insight_unalias($out, $rev) {
    $json = json_encode($out);
    foreach ($rev as $tok => $real) {
        $json = preg_replace('/\b' . preg_quote($tok, '/') . '\b/', str_replace('$', '\\$', $real), $json);
    }
    $back = json_decode($json, true);
    return is_array($back) ? $back : $out;
}

/* ---------------------------------------------------------------------------
 * 9. CACHE
 * -------------------------------------------------------------------------*/
function iss_insight_cache_key($c, $F, $cfg) {
    return md5(ISS_INSIGHT_PROMPT_V . '|' . $cfg['provider'] . '|' . $cfg['model'] . '|' .
               $c['report']['id'] . '|' . json_encode($F));
}
function iss_insight_cache_get($key, $cfg) {
    $f = $cfg['cache_dir'] . '/' . $key . '.json';
    if (!file_exists($f)) { return null; }
    if ((time() - filemtime($f)) > (int)$cfg['cache_ttl']) { return null; }
    $j = json_decode(@file_get_contents($f), true);
    return is_array($j) ? $j : null;
}
function iss_insight_cache_put($key, $val, $cfg) {
    if (!is_dir($cfg['cache_dir'])) { @mkdir($cfg['cache_dir'], 0770, true); }
    if (!is_dir($cfg['cache_dir'])) { return; }
    @file_put_contents($cfg['cache_dir'] . '/' . $key . '.json', json_encode($val));
}

/* ---------------------------------------------------------------------------
 * 10. ENTRY POINT
 *
 *   $result = iss_insight($contextArray);
 *
 * Never throws, never blocks past the configured timeout, always returns the
 * same shape. 'source' says which path produced it.
 * -------------------------------------------------------------------------*/
function iss_insight($ctx, $overrides = array()) {
    $cfg = array_merge(iss_insight_config(), is_array($overrides) ? $overrides : array());
    $c = iss_insight_normalize($ctx);
    if ($c === null) {
        return array('headline' => '', 'summary' => 'No report context supplied.',
                     'findings' => array(), 'watchlist' => array(),
                     'caveats' => array(), 'questions' => array(), 'source' => 'error');
    }
    $F = iss_insight_findings($c);
    /* The advanced layer runs HERE, not only in the pages. Without this the
       deterministic block on the page (which calls the analytics itself) and
       the model prompt (which comes through this function) were working from
       different finding sets -- and the ones missing from the prompt were the
       cross-tab, change-point, seasonality and recurrence findings, i.e. all
       the ones the model most needs and cannot derive on its own. */
    if (function_exists('iss_insight_findings_advanced')) {
        $F = iss_insight_findings_advanced($ctx, $c, $F);
    }
    $offline = iss_insight_render_offline($c, $F);
    $offline['findings_raw'] = $F;

    if (empty($cfg['enabled']) || $cfg['provider'] === 'none') { return $offline; }

    $key = iss_insight_cache_key($c, $F, $cfg);
    $hit = iss_insight_cache_get($key, $cfg);
    if ($hit !== null) { $hit['source'] = 'cache'; $hit['findings_raw'] = $F; return $hit; }

    $Fsend = $F; $rev = null;
    if (!empty($cfg['redact_labels']) || !empty($c['report']['sensitive'])) {
        $rev = iss_insight_alias($Fsend, $c);
    }

    list($txt, $err) = iss_insight_call(iss_insight_system_prompt(),
                                        iss_insight_user_prompt($c, $Fsend, $rev), $cfg);
    if ($txt === null) {
        iss_insight_log('provider failed: ' . $err, $cfg);
        $offline['source'] = 'deterministic (provider unavailable)';
        return $offline;
    }
    $out = iss_insight_parse($txt);
    if ($out === null) {
        iss_insight_log('unparseable model output', $cfg);
        $offline['source'] = 'deterministic (unparseable model output)';
        return $offline;
    }
    $out = iss_insight_audit($out, $Fsend, $c);
    if ($rev !== null) { $out = iss_insight_unalias($out, $rev); }
    $out['source'] = 'model:' . $cfg['model'];
    $out['generated_at'] = date('c');
    iss_insight_cache_put($key, $out, $cfg);
    $out['findings_raw'] = $F;
    return $out;
}

/* ---------------------------------------------------------------------------
 * 10b. SESSION STASH  --  so the async endpoint never trusts client JSON
 *      and never has to re-run the page's queries.
 * -------------------------------------------------------------------------*/
function iss_insight_stash($ctx) {
    if (!session_id()) { @session_start(); }
    if (!isset($_SESSION['iss_insight']) || !is_array($_SESSION['iss_insight'])) {
        $_SESSION['iss_insight'] = array();
    }
    if (count($_SESSION['iss_insight']) > 8) {
        $_SESSION['iss_insight'] = array_slice($_SESSION['iss_insight'], -4, 4, true);
    }
    $k = substr(md5(uniqid('', true)), 0, 16);
    $_SESSION['iss_insight'][$k] = $ctx;
    return $k;
}
function iss_insight_unstash($k) {
    if (!session_id()) { @session_start(); }
    if (!preg_match('/^[a-f0-9]{16}$/', (string)$k)) { return null; }
    return isset($_SESSION['iss_insight'][$k]) ? $_SESSION['iss_insight'][$k] : null;
}

/* ---------------------------------------------------------------------------
 * 11. PRESENTATION  --  console panel + print block, --cf-* tokens
 * -------------------------------------------------------------------------*/
function iss_insight_html($r, $showAudit = false) {
    $e = 'iss_ins_esc';
    $h  = '<section class="ins-block">';
    $h .= '<h3 class="ins-title">Analysis &amp; Interpretation</h3>';
    if (!empty($r['headline'])) { $h .= '<p class="ins-head">' . $e($r['headline']) . '</p>'; }
    if (!empty($r['summary']))  { $h .= '<p class="ins-summary">' . $e($r['summary']) . '</p>'; }

    if (!empty($r['findings'])) {
        $h .= '<ul class="ins-findings">';
        foreach ($r['findings'] as $f) {
            $p = isset($f['priority']) ? $f['priority'] : 'low';
            $h .= '<li class="ins-f ins-' . $e($p) . '"><b>' . $e(isset($f['title']) ? $f['title'] : '') . '</b> '
                . $e(isset($f['detail']) ? $f['detail'] : '') . '</li>';
        }
        $h .= '</ul>';
    }
    if (!empty($r['watchlist'])) {
        $h .= '<p class="ins-sub">Worth a look</p><ul class="ins-watch">';
        foreach ($r['watchlist'] as $w) {
            $h .= '<li>' . $e(isset($w['subject']) ? $w['subject'] : '') . ' &mdash; '
                . $e(isset($w['why']) ? $w['why'] : '') . '</li>';
        }
        $h .= '</ul>';
    }
    if (!empty($r['caveats'])) {
        $h .= '<p class="ins-caveat">';
        foreach ($r['caveats'] as $cv) { $h .= $e($cv) . ' '; }
        $h .= '</p>';
    }
    $src = isset($r['source']) ? $r['source'] : '';
    $h .= '<p class="ins-src">Generated from the figures shown on this page' .
          ($src ? ' &middot; ' . $e($src) : '') . '. Review before circulating.</p>';
    if ($showAudit && !empty($r['audit'])) {
        $h .= '<p class="ins-audit">Audit: ' . $e(implode(' | ', $r['audit'])) . '</p>';
    }
    return $h . '</section>';
}
function iss_ins_esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function iss_insight_css() {
    return '<style>
.ins-block{border:1px solid var(--cf-line,#d7dde6);border-left:4px solid var(--cf-gold,#c8a028);
  background:var(--cf-surface,#fbfcfe);padding:14px 16px;margin:16px 0;border-radius:4px;}
.ins-title{margin:0 0 8px;font-size:13px;letter-spacing:.06em;text-transform:uppercase;
  color:var(--cf-blue,#00529B);}
.ins-head{margin:0 0 6px;font-weight:600;font-size:15px;color:#1c2431;}
.ins-summary{margin:0 0 10px;line-height:1.5;color:#33404f;}
.ins-findings,.ins-watch{margin:0 0 10px;padding-left:18px;line-height:1.5;}
.ins-findings li{margin-bottom:5px;}
.ins-f.ins-high{list-style:square;} .ins-f.ins-high b{color:#a4262c;}
.ins-f.ins-medium b{color:#8a6100;}
.ins-sub{margin:8px 0 4px;font-weight:600;font-size:13px;color:var(--cf-blue,#00529B);}
.ins-caveat{margin:8px 0 0;font-size:12.5px;color:#7a2b2b;line-height:1.45;}
.ins-src{margin:10px 0 0;font-size:11.5px;color:#6b7683;font-style:italic;}
.ins-audit{margin:4px 0 0;font-size:11.5px;color:#a4262c;}
@media print{.ins-block{break-inside:avoid;border-left-width:3px;background:none;}
  .ins-audit{display:none;}}
</style>';
}