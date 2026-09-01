<?php
/* =====================================================================
   iss_api_ops.php  --  the operational aggregates the mobile Trends and
   Insertions screens read.

   Split out of iss_api.php rather than merged into it: these queries hit
   tables iss_api.php never touched (timetable_day, train_compo,
   incident_report.level_condition) and every one of them is probed before
   use. A station whose schema predates any of them gets a null section and
   an explicit reason, not a fatal and not a silent zero.

   INCLUDE THIS FROM iss_api.php, immediately after its own require of
   dash_data.php:

       if(file_exists(dirname(__FILE__)."/iss_api_ops.php")){
           require_once(dirname(__FILE__)."/iss_api_ops.php");
       }

   Then add the route block at the bottom of this file's comment header
   into iss_api.php's /days/{date} handler. Both edits are listed in
   php/README.md under "Ops endpoints".

   Every figure below is traceable to a page you already run:
     severity grid    <- ccdr_summary.php  (level, level_condition)
     ampm             <- ccdr_summary.php  (sum(cancel), 00:00-12:00 / 12:00:01-23:59:59)
     loops            <- ccdr_summary.php  (timetable_day.planned_loops - sum(cancel))
     lrv              <- ccdr_summary.php  (distinct car_no, status='active')
     insertions       <- dashboard.php     (dash_recent_insertions, no limit)
   ===================================================================== */

if(!function_exists('dash_ready')){
	/* iss_api.php requires dash_data.php before this file. If it did not,
	   say so here rather than fataling on the first helper call. */
	return;
}

/* ---------------------------------------------------------------------
   Guarded column probe. dash_table_exists() answers for tables; these
   aggregates also depend on individual COLUMNS that arrived at different
   times across stations (level_condition especially).
   --------------------------------------------------------------------- */
if(!function_exists('iss_column_exists')){
function iss_column_exists($table, $column){
	static $cache = array();
	$key = $table.'.'.$column;
	if(isset($cache[$key])) return $cache[$key];
	if(!dash_ready() || !dash_table_exists($table)) return $cache[$key] = false;
	$db = dash_db();
	$rs = $db->query("show columns from `".$db->real_escape_string($table)
	                 ."` like '".$db->real_escape_string($column)."'");
	$cache[$key] = ($rs && $rs->num_rows > 0);
	return $cache[$key];
}}

/* Single scalar, or null when the query cannot run. Deliberately NOT 0:
   the caller has to be able to tell "nothing was cancelled" from "this
   station cannot answer the question". */
if(!function_exists('iss_scalar')){
function iss_scalar($sql, $col){
	if(!dash_ready()) return null;
	$rs = dash_db()->query($sql);
	if(!$rs || !$rs->num_rows) return null;
	$r = $rs->fetch_assoc();
	return isset($r[$col]) ? $r[$col] : null;
}}

/* =====================================================================
   1. Severity grid -- level x level_condition
   =====================================================================
   The CCDR summary's own cross-tab. level is the severity; level_condition
   qualifies it (the values are whatever your register uses -- they are
   passed through verbatim, never mapped to an assumed vocabulary).

   Rows with no level are kept under the key '' and labelled by the client
   as Unspecified. Dropping them would make the grid's total disagree with
   the incident count on the same screen, which is exactly the kind of
   quiet mismatch this whole API is trying to avoid.
   ===================================================================== */
if(!function_exists('iss_severity_grid')){
function iss_severity_grid($date){
	if(!dash_ready() || !dash_table_exists('incident_report')){
		return array('available'=>false, 'reason'=>'incident_report not present');
	}
	$hasCondition = iss_column_exists('incident_report','level_condition');

	$db = dash_db();
	$d  = $db->real_escape_string($date);

	$sel = $hasCondition
		? "select count(*) as n, level, level_condition"
		: "select count(*) as n, level";
	$grp = $hasCondition ? " group by level, level_condition" : " group by level";

	$rs = $db->query($sel." from incident_report where incident_date like '".$d."%'".$grp);
	if(!$rs) return array('available'=>false, 'reason'=>'query failed');

	$cells      = array();
	$levels     = array();
	$conditions = array();
	$total      = 0;

	while($r = $rs->fetch_assoc()){
		$lvl  = trim((string)$r['level']);
		/* '0' and '' both mean "no severity recorded" in this register. */
		if($lvl === '' || $lvl === '0') $lvl = '';
		$cond = $hasCondition ? trim((string)$r['level_condition']) : '';
		$n    = (int)$r['n'];

		$cells[] = array('level'=>$lvl, 'condition'=>$cond, 'n'=>$n);
		$total  += $n;
		if(!in_array($lvl,$levels,true))       $levels[]     = $lvl;
		if(!in_array($cond,$conditions,true))  $conditions[] = $cond;
	}

	/* Ascending severity, unspecified last -- the client should not have to
	   re-sort a grid it is only rendering. */
	usort($levels, 'iss_cmp_level');
	sort($conditions);

	return array(
		'available'      => true,
		'has_condition'  => $hasCondition,
		'levels'         => array_values($levels),
		'conditions'     => array_values($conditions),
		'cells'          => $cells,
		'total'          => $total
	);
}}

if(!function_exists('iss_cmp_level')){
function iss_cmp_level($a, $b){
	if($a === '' && $b === '') return 0;
	if($a === '') return 1;    /* unspecified sinks */
	if($b === '') return -1;
	return ((int)$a) - ((int)$b);
}}

/* =====================================================================
   2. AM / PM cancellation split
   =====================================================================
   ccdr_summary.php sums incident_report.cancel over two windows. It does
   this THREE different ways on the same page -- filtered by level, by
   incident_type group, and joined through incident_description -- and
   those do not reconcile to one number.

   This endpoint returns the level-3-and-4 variant and SAYS SO in the
   payload ('basis'). The mobile prints that basis under the figure, so a
   supervisor comparing it against a printout can see which of the three
   they are looking at instead of assuming they disagree by accident.

   Window boundaries are ccdr_summary's verbatim, including the one-second
   gap at noon: AM ends 12:00:00, PM starts 12:00:01. Preserved rather
   than "fixed" -- a cancellation logged exactly at 12:00:00 must land in
   the same half-day here as it does on the printout.
   ===================================================================== */
if(!function_exists('iss_ampm_cancellations')){
function iss_ampm_cancellations($date){
	if(!dash_ready() || !dash_table_exists('incident_report')){
		return array('available'=>false, 'reason'=>'incident_report not present');
	}
	if(!iss_column_exists('incident_report','cancel')){
		return array('available'=>false, 'reason'=>'incident_report.cancel not present');
	}

	$db = dash_db();
	$d  = $db->real_escape_string($date);

	$am = iss_scalar("select sum(cancel) as n from incident_report"
		." where incident_date between '".$d." 00:00:00' and '".$d." 12:00:00'"
		." and level in ('3','4')", 'n');
	$pm = iss_scalar("select sum(cancel) as n from incident_report"
		." where incident_date between '".$d." 12:00:01' and '".$d." 23:59:59'"
		." and level in ('3','4')", 'n');

	/* sum() of no rows is SQL NULL, which genuinely means zero cancelled
	   trips here -- the query ran. Distinct from iss_scalar's null, which
	   means it could not run at all. */
	$amv = ($am === null) ? 0.0 : (float)$am;
	$pmv = ($pm === null) ? 0.0 : (float)$pm;

	return array(
		'available' => true,
		'am'        => $amv,
		'pm'        => $pmv,
		'total'     => $amv + $pmv,
		'basis'     => 'Level 3 and 4 only',
		'windows'   => array('am'=>'00:00-12:00', 'pm'=>'12:00-23:59')
	);
}}

/* =====================================================================
   3. Loop completion
   =====================================================================
   planned_loops comes from the day's timetable row; actual is planned
   minus the day's cancelled trips, over the six incident types
   ccdr_summary uses for this figure (a different set from the AM/PM
   figure above -- again, stated in the payload).

   Half loops are real: actual can be 0.5, and the client renders that as
   "1/2" the way the printout does. Sent as a number, formatted once on
   the client, rather than pre-formatted here.
   ===================================================================== */
if(!function_exists('iss_loops')){
function iss_loops($date){
	if(!dash_ready() || !dash_table_exists('timetable_day')){
		return array('available'=>false, 'reason'=>'timetable_day not present');
	}
	$db = dash_db();
	$d  = $db->real_escape_string($date);

	$planned = iss_scalar("select planned_loops from timetable_day"
		." where train_date='".$d."' limit 1", 'planned_loops');

	if($planned === null || (float)$planned <= 0){
		/* No timetable row for the date. NOT zero percent -- there is no
		   denominator, so there is no completion figure to report. */
		return array(
			'available' => false,
			'reason'    => 'no timetable row for this operating date'
		);
	}

	$cancelled = 0.0;
	if(iss_column_exists('incident_report','cancel')){
		$c = iss_scalar("select sum(cancel) as n from incident_report"
			." where incident_date like '".$d."%'"
			." and incident_type in ('rolling','gradual','c_loops','r_trains','unload','nload')", 'n');
		$cancelled = ($c === null) ? 0.0 : (float)$c;
	}

	$plannedV = (float)$planned;
	$actual   = $plannedV - $cancelled;

	return array(
		'available'  => true,
		'planned'    => $plannedV,
		'actual'     => $actual,
		'cancelled'  => $cancelled,
		'percentage' => round(($actual / $plannedV) * 100, 2),
		'basis'      => 'rolling, gradual, c_loops, r_trains, unload, nload'
	);
}}

/* =====================================================================
   4. LRV utilization -- distinct cars that actually ran
   =====================================================================
   ccdr_summary counts the ROWS of a group-by-car_no query. Counting
   distinct car_no directly is the same number and does not depend on
   fetching every row, but the shape of the join is kept identical so the
   two stay equal if either page's join changes.
   ===================================================================== */
if(!function_exists('iss_lrv_utilization')){
function iss_lrv_utilization($date){
	if(!dash_ready() || !dash_table_exists('train_availability')
	   || !dash_table_exists('train_compo')){
		return array('available'=>false, 'reason'=>'train_availability or train_compo not present');
	}
	$db = dash_db();
	$d  = $db->real_escape_string($date);

	$n = iss_scalar("select count(distinct train_compo.car_no) as n"
		." from train_availability"
		." inner join train_compo on train_availability.id = train_compo.tar_id"
		." where train_availability.date like '".$d."%'"
		." and train_availability.status='active'", 'n');

	if($n === null) return array('available'=>false, 'reason'=>'query failed');

	/* Per-car loop counts are NOT returned. train_compo records which cars
	   made up a set, not how many loops each ran, so a per-car loop figure
	   would have to be invented by dividing the fleet total. The screen
	   shows the count of cars utilised and says the per-car split is not
	   recorded. */
	return array('available'=>true, 'cars_utilized'=>(int)$n);
}}

/* =====================================================================
   5. Insertions -- the whole day, with the skipping flag
   =====================================================================
   dashboard.php's @insertions change: no limit, the card scrolls. The
   skipping test is the dashboard's own -- an insertion point that is not
   North Ave. means the train skipped part of the line.

   The comparison is on the raw point string, matching the console. If a
   station ever renames the terminus, BOTH pages need the change; a
   silently different test here would put the app and the console into
   disagreement about which insertions skipped.
   ===================================================================== */
if(!function_exists('iss_insertions')){
function iss_insertions($date){
	$out = array();
	if(!function_exists('dash_recent_insertions')) return $out;

	foreach(dash_recent_insertions($date, 0) as $i){
		$point = isset($i['point']) ? (string)$i['point'] : '';
		$out[] = array(
			'index_no' => isset($i['index_no']) ? (string)$i['index_no'] : '',
			'time'     => isset($i['ts']) && $i['ts'] ? date("H:i", $i['ts']) : '',
			'point'    => $point,
			'skipping' => ($point !== 'North Ave.')
		);
	}
	return $out;
}}

/* =====================================================================
   6. Trend series -- passthrough with an explicit gap contract
   =====================================================================
   dash_trend_series() already returns null-for-no-records, which the
   dashboard draws as a hatched full-height bar. That null must survive
   the JSON round trip: a gap rendered as a zero reads as a GOOD period,
   which is the most dangerous thing this figure could show.

   json_encode turns PHP null into JSON null, and the app's parser
   distinguishes null from 0 explicitly. Nothing here coerces.
   ===================================================================== */
if(!function_exists('iss_trend')){
function iss_trend($date, $grain){
	if(!function_exists('dash_trend_series')){
		return array('available'=>false, 'reason'=>'dash_data.php predates dash_trend_series()');
	}
	$g = function_exists('dash_trend_grain') ? dash_trend_grain($grain) : 'month';
	$t = dash_trend_series($date, $g);
	if(!$t) return array('available'=>false, 'reason'=>'no series');

	$buckets = array();
	$gaps    = 0;
	foreach($t['keys'] as $k){
		$n = $t['counts'][$k];
		if($n === null) $gaps++;
		$buckets[] = array(
			'key'   => (string)$k,
			'label' => isset($t['labels'][$k]) ? (string)$t['labels'][$k] : (string)$k,
			'full'  => isset($t['full'][$k])   ? (string)$t['full'][$k]   : (string)$k,
			'count' => ($n === null) ? null : (int)$n   /* null = NO RECORDS, not zero */
		);
	}

	return array(
		'available' => true,
		'grain'     => $g,
		'head'      => isset($t['head']) ? (string)$t['head'] : '',
		'buckets'   => $buckets,
		'gaps'      => $gaps,
		'sd'        => isset($t['sd']) ? $t['sd'] : '',
		'ed'        => isset($t['ed']) ? $t['ed'] : ''
	);
}}

/* =====================================================================
   Composite -- one call for the whole Trends screen.
   ===================================================================== */
if(!function_exists('iss_ops_trends')){
function iss_ops_trends($date, $grain='month'){
	return array(
		'severity'   => iss_severity_grid($date),
		'ampm'       => iss_ampm_cancellations($date),
		'loops'      => iss_loops($date),
		'lrv'        => iss_lrv_utilization($date),
		'trend'      => iss_trend($date, $grain),
		/* Kept from the original /trends payload: the app's problem-type
		   card still reads this, and dropping it would blank that card. */
		'types'      => function_exists('dash_type_breakdown') ? dash_type_breakdown($date, 8) : array()
	);
}}
