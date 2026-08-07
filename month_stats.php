<?php
session_start();
ini_set("date.timezone","Asia/Kuala_Lumpur");

/* =========================================================================
   car_stats.php — equipment breakdown for one car, over a year or a month.
   Reached from car_history.php / car_statistics_report.php.

   Console theme pass (08032026):
   Brought in line with statistics_report_modified.php — same ccs-page /
   ccs-header / ccs-panel shell, same KPI tile strip, same table treatment
   (real thead/tbody/tfoot, Total row, 60% highlight, legend explaining it).

   This file was previously a copy of "incident report.php" with the stats
   query grafted into the middle of it: the incident INSERT handler, the
   equipment picker JS, the link-modal markup and ~500 lines of ir-* form
   CSS were all still present but unreachable from this page, which is
   read-only and has no form. They are removed here. Nothing that this
   page's two queries or its markup referenced has been dropped.
   ========================================================================= */

/* embed=1 -> hosted inside the train_operations slide-panel iframe. Tmenu.php
   still runs (it provides $db / session / auth side effects) but its printed
   chrome is captured and discarded. Opened standalone, nothing changes. */
$IR_EMBED = isset($_GET['embed']);
if($IR_EMBED){ ob_start(); }
require("Tmenu.php");
if($IR_EMBED){ ob_end_clean(); }

// Which months the console actually has records for. Loaded defensively: if
// data_coverage.php has not been uploaded yet, the stubs report every month as
// covered and the page renders as it did before the helper existed, rather
// than dying on a failed require.
if(file_exists(dirname(__FILE__)."/data_coverage.php")){
	require_once(dirname(__FILE__)."/data_coverage.php");
}
if(!function_exists('ccsLoadCoverage')){
	function ccsLoadCoverage($db){ return array(); }
	function ccsMonthStatus($coverage,$ym){ return 'covered'; }
	function ccsMonthIsMissing($coverage,$ym){ return false; }
	function ccsCoverageCell($status,$note=''){ return ''; }
	function ccsCoverageCss(){ return ''; }
	function ccsCoverageNote($coverage,$prefix=''){ return ''; }
	function ccsUncoveredMonths($coverage,$f,$t){ return array(); }
}

$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
$coverage = ccsLoadCoverage($db);

// ---- Inputs --------------------------------------------------------------
// @monthstats -- The subject of this page is a PERIOD, not a car or an
// equipment type. The tile that opens it can name more than one month, because
// the peak-month calculation keeps ties rather than resolving them -- "Mar &
// Jul" is a real answer and both have to arrive here.
//
//   months=3,7   one or more months of $year
//   days=4,11    one or more days of $year-$month  (peak-DAY tile)
//   sd / ed      an explicit range, for callers that filter by range
//   year         required for months= and days=
//
// by=car | equipt decides what the breakdown table lists. One page rather than
// two: the period logic, the filters, the printout and the panel plumbing are
// identical, and two copies of that is how they drift apart.
$by = (isset($_GET['by']) && $_GET['by'] === 'equipt') ? 'equipt' : 'car';

$year  = isset($_GET['year']) && $_GET['year'] !== '' ? (int)$_GET['year'] : 0;
$month = isset($_GET['month']) && $_GET['month'] !== '' ? (int)$_GET['month'] : 0;
if($month < 1 || $month > 12){ $month = 0; }

/* Optional narrowing, carried through from whichever report opened the panel. */
/* Accepts either spelling: car_statistics_report.php sends car_id=, while
   statistics_report_modified.php's own filter is car=. */
$car = 0;
if(isset($_GET['car_id']) && $_GET['car_id'] !== '') $car = (int)$_GET['car_id'];
else if(isset($_GET['car']) && $_GET['car'] !== '')  $car = (int)$_GET['car'];
$equipt = isset($_GET['equipt']) && $_GET['equipt'] !== '' ? (int)$_GET['equipt'] : 0;
$level  = isset($_GET['level'])  && $_GET['level']  !== '' ? (int)$_GET['level']  : 0;

/* A comma list, deduplicated and bounded. Anything out of range is dropped
   rather than clamped -- a junk month is a caller bug, and silently turning
   it into January would hide that. */
function msList($raw, $lo, $hi){
	$out = array();
	foreach(explode(',', (string)$raw) as $v){
		$v = (int)trim($v);
		if($v >= $lo && $v <= $hi && !in_array($v, $out, true)) $out[] = $v;
	}
	sort($out);
	return $out;
}
$months = isset($_GET['months']) ? msList($_GET['months'], 1, 12) : array();
$days   = isset($_GET['days'])   ? msList($_GET['days'],   1, 31) : array();
if($month && !count($months)) $months = array($month);

$sd = isset($_GET['sd']) && $_GET['sd'] !== '' ? strtotime($_GET['sd']) : false;
$ed = isset($_GET['ed']) && $_GET['ed'] !== '' ? strtotime($_GET['ed']) : false;
$hasRange = ($sd !== false && $ed !== false);
if($hasRange && $ed < $sd){ $t=$sd; $sd=$ed; $ed=$t; }

/* ---- The period clause ---------------------------------------------------
   Several months are an OR of LIKE terms rather than a BETWEEN: the months a
   tie names are not adjacent, and a range spanning March to July would quietly
   include April, May and June -- months the tile did not name and the reader
   is not expecting. */
$mnFull = array(1=>'January','February','March','April','May','June','July','August','September','October','November','December');
$dateClause = "";
$period     = "All Time";
$grain      = 'month';   // what the period breakdown groups by

if($hasRange){
	$dateClause = " and incident_date between '".date("Y-m-d",$sd)." 00:00:00' and '".date("Y-m-d",$ed)." 23:59:59' ";
	$period     = date("d M Y",$sd)." to ".date("d M Y",$ed);
	$grain      = (date("Y-m",$sd) === date("Y-m",$ed)) ? 'day' : 'month';
	$start_date1 = date("Y-m-d",$sd);
	$end_date1   = date("Y-m-d",$ed);
}
else if($year && $month && count($days)){
	$parts = array(); $labels = array();
	foreach($days as $d){
		$ymd = sprintf("%04d-%02d-%02d", $year, $month, $d);
		$parts[]  = "incident_date like '".$ymd."%'";
		$labels[] = date("j", strtotime($ymd));
	}
	$dateClause  = " and (".implode(" or ", $parts).") ";
	$period      = implode(", ", $labels)." ".date("F Y", strtotime(sprintf("%04d-%02d-01",$year,$month)));
	$grain       = 'day';
	$start_date1 = sprintf("%04d-%02d-%02d", $year, $month, $days[0]);
	$end_date1   = sprintf("%04d-%02d-%02d", $year, $month, $days[count($days)-1]);
}
else if($year && count($months)){
	$parts = array(); $labels = array();
	foreach($months as $m){
		$parts[]  = "incident_date like '".sprintf("%04d-%02d",$year,$m)."-%'";
		$labels[] = $mnFull[$m];
	}
	$dateClause  = " and (".implode(" or ", $parts).") ";
	$period      = implode(" and ", $labels)." ".$year;
	$grain       = (count($months) === 1) ? 'day' : 'month';
	$start_date1 = sprintf("%04d-%02d-01", $year, $months[0]);
	$end_date1   = date("Y-m-t", strtotime(sprintf("%04d-%02d-01", $year, $months[count($months)-1])));
	if(count($months) === 1) $month = $months[0];
}
else if($year){
	$dateClause  = " and incident_date like '".$year."-%' ";
	$period      = "Full year ".$year;
	$start_date1 = sprintf("%04d-01-01",$year);
	$end_date1   = sprintf("%04d-12-31",$year);
}
else {
	$grain = 'year';
	$start_date1 = '';
	$end_date1   = '';
}

// ---- Full incident history link ------------------------------------------
// @historylink -- SET THE URL HERE. This is the only line to edit; the button
// below reads it. Pre-filled with the parameter names car_statistics_report.php
// already uses for its own car_history links (car_id / y / m), so it is a
// working guess rather than a blank -- change it if yours differ.

/* @monthstats -- Only meaningful when the panel was opened with a car or an
   equipment filter; a period on its own has no single history page, so the
   button is simply not rendered then.
   The if(isset($year)) branch this replaces was always true -- $year is
   assigned unconditionally above -- so the else half was dead code. */
$carHistoryUrl = "";
if($car)         $carHistoryUrl = "car_history.php?car_id=".$car;
else if($equipt) $carHistoryUrl = "equipment_history.php?equipt=".$equipt;
if($carHistoryUrl !== "" && $year){
	$carHistoryUrl .= "&y=".$year.($month ? "&m=".$month : "");
}
// Inside the slide panel this page is an iframe, so a plain link would load
// car_history INSIDE the 820px panel. _top breaks it out into the full window.
// Change to "_blank" for a new tab, or "_self" to keep it in the panel.
$carHistoryTarget = $IR_EMBED ? "_top" : "_self";

// @tally -- Why this page's Total did not match the "Most Fault-Prone Car"
// tile on car_statistics_report.php. Two separate causes, both here:
//
// 1. The equipment IN() list. It restricted this page to 27 equipt ids while
//    the tile counts every incident_cars row for the car, so anything with a
//    blank, zero or out-of-list equipt was in the tile and absent here. The
//    filter is removed: the table now accounts for every failure the tile
//    counts, and rows with no equipment recorded are shown as such rather
//    than silently dropped. If the 27-id restriction was deliberate, the list
//    is preserved below -- re-add it as a WHERE clause and the two figures
//    will diverge again by design, so say so on the page if you do.
//      2,11,64,67,81,89,102,103,104,105,108,109,110,111,112,113,114,115,116,
//      117,118,119,120,121,122,123,124
//
// 2. car_no='5' is a string comparison; the tile groups by car_no*1. Any row
//    stored as '05', ' 5' or '5 ' fell into the tile's bucket for car 5 and
//    was missed here. Both sides coerce numerically now.
// @monthstats -- The period IS the subject, so it leads the clause; car and
// equipment are optional narrowing carried through from the calling report.
$where = "1=1".$dateClause;
if($car)    $where .= " and incident_cars.car_no*1 = ".$car." ";
if($equipt) $where .= " and incident_report.equipt = ".$equipt." ";

/* @levelfilter -- TWO clauses, deliberately. The severity tiles have to keep
   counting EVERY level: filtering them would leave one tile with a figure and
   the rest on zero, and no way back, because those tiles are the only control
   for level on this page. The severity query reads $whereAllLevels; the
   breakdown, the period table and the incident count read $where. */
$whereAllLevels = $where;
if($level)  $where .= " and incident_report.level = ".$level." ";
// ---- Severity split ------------------------------------------------------
// The old query grouped by level but selected equipt, so $row['level'] was
// never set and every tile read from one undefined key.
$levelCounts = array();
$sql = "select incident_report.level as level, count(1) as c
          from incident_report
          inner join incident_cars on incident_report.id=incident_cars.incident_id
         where ".$whereAllLevels."
         group by incident_report.level";
$rs = $db->query($sql);
if($rs){
	while($row = $rs->fetch_assoc()){
		$levelCounts[(string)$row['level']] = (int)$row['c'];
	}
}

// ---- Equipment breakdown -------------------------------------------------
// @tally -- name resolved in the query via LEFT JOIN rather than a getEquipt()
// call per row, which was one extra query per equipment type and per tile.
// LEFT so that a blank or unknown equipt still yields its row.
$rows = array();
$equipt_count   = 0;
$unrecordedFail = 0;   /* failures whose incident has no equipment recorded */
/* @monthstats -- The breakdown axis follows ?by=. Both branches count the same
   rows and total to the same figure; only the grouping differs, so the two
   views of a month always agree with each other and with the tile that opened
   them. */
if($by === 'equipt'){
	$sql = "select incident_report.equipt as k,
	               equipment.equipment_name as nm,
	               count(1) as c
	          from incident_report
	          inner join incident_cars on incident_report.id=incident_cars.incident_id
	          left  join equipment on equipment.id = incident_report.equipt
	         where ".$where."
	         group by incident_report.equipt, equipment.equipment_name
	         order by c desc";
}
else {
	/* car_no*1 so '05' and '5' fold together, matching how the reports bucket
	   them; comparing the raw column splits one car across two rows. */
	$sql = "select incident_cars.car_no*1 as k, '' as nm, count(1) as c
	          from incident_report
	          inner join incident_cars on incident_report.id=incident_cars.incident_id
	         where ".$where."
	         group by incident_cars.car_no*1
	         order by c desc";
}
$rs = $db->query($sql);
if($rs){
	while($row = $rs->fetch_assoc()){
		$id = $row['k'];
		if($by === 'equipt'){
			$blank = ($id === null || trim((string)$id) === '' || (int)$id === 0);
			if($blank){ $label = 'Not recorded'; $unrecordedFail += (int)$row['c']; }
			elseif($row['nm'] !== null && $row['nm'] !== ''){ $label = $row['nm']; }
			else { $label = 'Equipment #'.(int)$id.' (not in equipment table)'; }
		}
		else {
			$cn = (int)$id;
			if($cn <= 0){ $label = 'Not recorded'; $unrecordedFail += (int)$row['c']; }
			else { $label = 'Car '.$cn; }
		}
		$rows[] = array('id'=>$id, 'label'=>$label, 'count'=>(int)$row['c']);
		$equipt_count += (int)$row['c'];
	}
}

/* Denominator for the "affected" tile: the size of the population the
   breakdown is drawn from, so it means the same thing in both modes. */
$equiptTracked = 0;
$tq = $db->query($by === 'equipt'
	? "select count(*) as c from equipment"
	: "select count(distinct car_no*1) as c from incident_cars where car_no*1 > 0");
if($tq && ($tr = $tq->fetch_assoc())){ $equiptTracked = (int)$tr['c']; }

/* ---- Period breakdown ----------------------------------------------------
   @monthstats -- The point of this page. One month in scope breaks down by
   DAY; several break down by month, so a tie like "March and July" shows the
   two side by side instead of merging into one figure. Keys are composite so
   two Marches from different years cannot collapse into one row. */
$periodBuckets = array();
$pq = $db->query("select year(incident_date) as yr, month(incident_date) as mo,
                         day(incident_date) as dy, count(1) as c
                    from incident_report
                    inner join incident_cars on incident_report.id=incident_cars.incident_id
                   where ".$where."
                   group by year(incident_date), month(incident_date), day(incident_date)");
if($pq){
	while($pr = $pq->fetch_assoc()){
		if($grain === 'year')       $k = (int)$pr['yr'];
		else if($grain === 'month') $k = (int)$pr['yr']*100   + (int)$pr['mo'];
		else                        $k = (int)$pr['yr']*10000 + (int)$pr['mo']*100 + (int)$pr['dy'];
		if(!isset($periodBuckets[$k])) $periodBuckets[$k]=0;
		$periodBuckets[$k] += (int)$pr['c'];
	}
}
/* Empty periods omitted, as on the sibling pages: every row here is labelled,
   so a reader sees January jump to March without a blank row saying so. */
$periodBuckets = array_filter($periodBuckets, function($c){ return $c > 0; });
ksort($periodBuckets);

$peakPeriodCount = count($periodBuckets) ? max($periodBuckets) : 0;
$periodThreshold = $peakPeriodCount * 0.60;
$periodHeading   = ($grain === 'day') ? 'By day' : (($grain === 'year') ? 'By year' : 'By month');

/* Year shown on a month row whenever the table spans more than one, so two
   Marches can never appear as identical labels. */
$labelWithYear = false;
if($grain !== 'year'){
	$div = ($grain === 'month') ? 100 : 10000;
	$yrs = array();
	foreach(array_keys($periodBuckets) as $bk){ $yrs[(int)floor($bk/$div)] = true; }
	$labelWithYear = (count($yrs) > 1);
}
function msPeriodLabel($grain, $k, $showYear){
	if($grain === 'year') return (string)$k;
	if($grain === 'month'){
		$y = (int)floor($k/100); $m = $k % 100;
		return date($showYear ? "F Y" : "F", strtotime(sprintf("%04d-%02d-01", $y, $m)));
	}
	$y = (int)floor($k/10000); $m = (int)floor(($k%10000)/100); $d = $k % 100;
	return date($showYear ? "d M Y (l)" : "d M (l)", strtotime(sprintf("%04d-%02d-%02d", $y, $m, $d)));
}

$peakTotal     = count($rows) ? $rows[0]['count'] : 0;
$flagThreshold = $peakTotal * 0.60;

// Distinct incidents behind those car-level failures — the same reconciliation
// figure the other stats pages carry.
$distinctIncidents = 0;
$dq = $db->query("select count(distinct incident_report.id) as c
                    from incident_report
                    inner join incident_cars on incident_report.id=incident_cars.incident_id
                   where ".$where);
if($dq && ($dr = $dq->fetch_assoc())){ $distinctIncidents = (int)$dr['c']; }

// @nolevel -- The Unlevelled tile is gone at request: level is often not
// recorded, so it was a large uninformative number sitting in the KPI strip.
// The COUNT is still needed, for two reasons that outlive the tile:
//   - the level tiles' shares are now taken against the levelled subtotal,
//     not the period total, so they add to 100% instead of quietly falling
//     short by however many rows had no level;
//   - the figure moves to the footnote, so it is stated once rather than
//     erased. Removing the tile should hide the noise, not the fact.
$TILE_LEVELS  = array(1,2,3,4);
$otherLevel   = 0;
$levelledFail = 0;
foreach($levelCounts as $lv => $c){
	if($lv !== '' && $lv !== null && in_array((int)$lv, $TILE_LEVELS, true)){ $levelledFail += $c; }
	else { $otherLevel += $c; }
}

$coverageNote = ccsCoverageNote($coverage);
$gapMonths    = ccsUncoveredMonths($coverage, $start_date1, $end_date1);

?>
<link rel="stylesheet" href="jquery-ui-themes-1.11.1/themes/smoothness/jquery-ui.css" />
<script src="jquery-ui-1.11.1/external/jquery/jquery.js"></script>
<script src="jquery-ui-1.11.1/jquery-ui.js"></script>

<style type='text/css'>
/* ===========================================================================
   LINE 3 SCHEME — shared with statistics_report_modified.php / car_history.php
   Blue leads the structure; yellow is a small accent, never the gridlines.
   =========================================================================== */
body { margin:24px 30px; background:#FAFAF6; color:#1A2238; font-family:"Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif; }

h2 { color:#1A2238; font-size:20px; }

.stat-legend {
	display:flex; align-items:center; gap:16px; flex-wrap:wrap;
	background:#F1EEE3; border:1px solid #E5DECC; border-top:none;
	padding:8px 16px; font-size:12px; color:#5A6275;
}
.stat-legend .swatch { display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:5px; vertical-align:middle; }

.stat-scope {
	background:#00529B; border-bottom:3px solid #FDB813;
	border-radius:6px 6px 0 0; padding:10px 16px;
	color:#FFFFFF; font-size:13px; font-weight:600;
}
.stat-scope .muted { color:rgba(255,255,255,.75); font-weight:400; margin-left:8px; }
/* @historylink -- the bar is now flex so the action sits hard right. Gold on
   blue, the same pairing the toolbars on the other stats pages use for their
   submit button; a blue button would disappear into this bar. */
.stat-scope { display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap; }
.stat-scope .scope-left { min-width:0; }
.scope-btn {
	display:inline-block; flex:none;
	background:#FDB813; color:#3A2D00 !important;
	font-size:12px; font-weight:700; line-height:1;
	padding:8px 14px; border-radius:4px; border:none;
	text-decoration:none !important; cursor:pointer; white-space:nowrap;
}
.scope-btn:hover, .scope-btn:focus { background:#E5A50F; color:#3A2D00 !important; text-decoration:none !important; }
.scope-btn:focus-visible { outline:2px solid #FFFFFF; outline-offset:2px; }
/* @printout -- secondary action: outlined rather than filled, so the gold
   history link stays the one obvious thing to press. */
.scope-actions { display:flex; align-items:center; gap:8px; flex:none; }
.scope-btn--ghost {
	background:transparent; color:#FFFFFF !important;
	border:1px solid rgba(255,255,255,.65);
	font-family:inherit;
}
.scope-btn--ghost:hover, .scope-btn--ghost:focus {
	background:rgba(255,255,255,.14); color:#FFFFFF !important; border-color:#FFFFFF;
}
@media print { .scope-actions { display:none; } }

.rowHeading {background:#00529B; color:#FFFFFF; font-size:15px; font-weight:600;}
.rowClass {background-color: #F5F2E8;}

.train_ava { border-collapse:collapse; }
.train_ava td, .train_ava th { border:1px solid #E5DECC; padding:6px 8px; }

select { border: 1px solid #D8D2C2; color: #1A2238; background-color: #FFFFFF; border-radius:4px; }

a.two { color:#00529B; font-weight:600; text-decoration:none; }
a.two:visited {color:#00529B;}
a.two:hover, a.two:active {color:#003E76; text-decoration:underline;}

<?php echo ccsCoverageCss(); ?>
.stat_hover:hover {
	background-color:#FFF1CC;
	text-decoration:underline;
	font-weight:bold;
}

/* @cols -- The equipment column holds everything from "Doors" to
   "Equipment #900 (not in equipment table)", and with auto layout the widest
   label decided the whole table: the name column ran to the far edge while
   Failures and Share were squeezed into whatever was left. Fixed layout with
   declared widths pins the two numeric columns and lets long names wrap
   instead of stretching the row. */
.brk-head { font-size:12px; text-transform:uppercase; letter-spacing:.07em; color:#00529B;
	border-bottom:1px solid #E5DECC; padding-bottom:5px; margin:22px 0 8px; font-weight:600; }
.eq-table { table-layout:fixed; width:100%; }
.eq-table th, .eq-table td { padding:6px 8px; vertical-align:middle; }
.eq-table col.c-name { width:auto; }
.eq-table col.c-num  { width:110px; }
.eq-table tbody th {
	text-align:left; font-weight:600;
	overflow-wrap:anywhere; word-break:break-word; hyphens:auto;
}

/* @cols -- Regular striping, applied here rather than left to the template's
   .table-striped, which the flagged rows' inline background used to override
   anyway. Same paper tone as .rowClass elsewhere on the console. */
.eq-table tbody tr:nth-child(even) { background-color:#F5F2E8; }
.eq-table tbody tr:hover { background-color:#FFF1CC; }

/* @cols -- Rows over the review threshold: red text and weight only. The pink
   fill read as an error state and fought the striping underneath it. */
.eq-flag, .eq-flag th, .eq-flag td { color:#7A1F1F; }
.eq-flag th, .eq-flag td { font-weight:700; }
.eq-flag th a { color:#7A1F1F !important; }

/* KPI tiles — same geometry as statistics_report_modified.php's strip. */
.kpi-strip { display:flex; flex-wrap:wrap; gap:10px; margin:14px 0; }
.kpi-tile {
	flex:1; min-width:150px; border:1px solid #E5DECC; border-radius:6px;
	padding:10px 12px; background:#FBFAF6;
}
.kpi-tile .k-label { font-size:11px; color:#5A6275; text-transform:uppercase; letter-spacing:.06em; }
.kpi-tile .k-value { font-size:22px; font-weight:600; color:#00529B; }
.kpi-tile .k-value--name { font-size:15px; line-height:1.3; margin-top:3px; color:#7A1F1F; }
.kpi-tile .k-sub   { font-size:11px; color:#5A6275; }
/* @levelfilter -- the level tiles double as the filter control. */
.kpi-striphead { font-size:11.5px; color:#5A6275; margin:14px 0 -4px; line-height:1.5; }
.kpi-striphead b { color:#1A2238; }
.kpi-clear { color:#00529B; font-weight:600; text-decoration:none; margin-left:6px; }
.kpi-clear:hover { text-decoration:underline; }
a.kpi-tile { text-decoration:none; color:inherit; display:block; }
.kpi-tile--link { cursor:pointer; transition:background .12s, border-color .12s, box-shadow .12s, opacity .12s; }
.kpi-tile--link:hover { background:#F3F7FC; border-color:#00529B; box-shadow:0 1px 5px rgba(0,40,90,.13); opacity:1; }
.kpi-tile--link:focus-visible { outline:2px solid #00529B; outline-offset:2px; }
/* Filled, not outlined: hover is already an outline change, so "active filter"
   has to read differently from "your mouse is here". */
.kpi-tile--on { background:#00529B !important; border-color:#00529B !important; }
.kpi-tile--on .k-label, .kpi-tile--on .k-sub { color:rgba(255,255,255,.82) !important; }
.kpi-tile--on .k-value { color:#FFFFFF !important; }
.kpi-tile--on:hover { background:#003E76 !important; }
.kpi-tile--dim { opacity:.55; }
</style>
<?php include("history_theme.php"); ?>

<body>
<div class="ccs-page">

<div class="ccs-header">
<?php
/* @monthstats -- The heading names the period, and any narrowing the calling
   report had active is stated after it. Arriving at a page showing a fraction
   of a month with nothing saying why reads as missing data. */
$msNarrow = array();
if($car)    $msNarrow[] = 'Car '.$car;
if($equipt){
	$enm=''; $eq=$db->query("select equipment_name from equipment where id='".$equipt."'");
	if($eq && ($er=$eq->fetch_assoc())) $enm=(string)$er['equipment_name'];
	$msNarrow[] = ($enm !== '' ? $enm : 'Equipment #'.$equipt);
}
if($level)  $msNarrow[] = 'Level '.$level;
?>
<h1><?php echo htmlspecialchars($period); ?></h1>
<?php if(count($msNarrow)){ ?>
<div class='sub' style="color:#FDB813;">Filtered to <?php /* @entity -- see equipt_stats.php: escape the parts, then join,
        or htmlspecialchars() escapes the separator's own ampersand. */
     echo implode(' &middot; ', array_map('htmlspecialchars', $msNarrow)); ?></div>
<?php } ?>
<?php
/* @dupperiod -- This said the period again, directly under an <h1> that had
   just said it. Harmless over "March and July 2025", absurd over a single
   month: panel header, h1 and this line all reading "March 2026" one under
   the other. It carries the exact span instead, which the h1 does not -- "March
   2026" does not tell you the report runs 01 to 31, and a part-month range
   would look identical. */
/* CONTIGUOUS periods only. A tie names months that are not adjacent -- over
   "March and July" the span 01 Mar to 31 Jul would claim April, May and June
   are included, which is exactly what the OR-of-LIKE date clause was written
   to avoid. Same for a day tie: "11 to 14 March" would swallow the 12th and
   13th. Those selections have no single span, so they get none. */
$msContiguous = $hasRange || (!count($days) && count($months) <= 1);
if($msContiguous && $start_date1 !== '' && $end_date1 !== ''){
	$msSpan = date("d M Y", strtotime($start_date1)).' to '.date("d M Y", strtotime($end_date1));
	/* Only worth printing when it says something the heading did not. */
	if($msSpan !== $period){ ?>
<div class='sub'><?php echo htmlspecialchars($msSpan); ?></div>
<?php }
} ?>
</div>

<div class="ccs-panel">
<div class="ccs-panel-head">
<div class="stat-scope">
	<div class="scope-left">
		<?php /* @dupperiod -- was the period a third time. The bar sits directly
		         above the table, so it names what the table shows instead. */ ?>
		Failures by <?php echo $by === 'equipt' ? 'equipment' : 'car'; ?>
		<?php 
		if(isset($_POST['year'])){
			?>
		<span class="muted"><?php echo date("d M Y", strtotime($start_date1)); ?> &ndash; <?php echo date("d M Y", strtotime($end_date1)); ?></span>
		<?php
		}
		?>
	</div>
<?php if($carHistoryUrl !== '' || $rows){ /* @monthstats -- printout always; history only when a car or equipment narrows it */ ?>
	<div class="scope-actions">
		<?php /* @monthstats -- the same period, seen the other way. One click
		         rather than going back to the report and opening the other tile. */ ?>
		<a class="scope-btn scope-btn--ghost" href="?<?php
			/* @levelfilter -- $_GET carries level through, so flipping the axis
			   keeps the filter rather than silently widening the page. */
			$qs = $_GET; $qs['by'] = ($by === 'equipt' ? 'car' : 'equipt');
			echo htmlspecialchars(http_build_query($qs));
		?>">By <?php echo $by === 'equipt' ? 'car' : 'equipment'; ?></a>
		<button type="button" class="scope-btn scope-btn--ghost" onclick="csPrintReport()">Generate printout</button>
<?php if($carHistoryUrl !== ''){ ?>		<a class="scope-btn" href="<?php echo htmlspecialchars($carHistoryUrl); ?>" target="<?php echo $carHistoryTarget; ?>">Full incident history &rarr;</a><?php } ?>
	</div>
<?php } ?>
</div>
<div class="stat-legend">
	<span><span class="swatch" style="background:#00529B;"></span>Counts are car-level failures for this car</span>
	<span><span class="swatch" style="background:#7A1F1F;"></span>Red row = among the highest counts in this period (&ge;60% of the peak)</span>
</div>
</div>
<div class='ccs-panel-body'>

<div class="kpi-strip">
	<div class="kpi-tile">
		<div class="k-label">Car-level failures</div>
		<div class="k-value"><?php echo $equipt_count; ?></div>
		<div class="k-sub">from <?php echo $distinctIncidents; ?> incident<?php echo $distinctIncidents==1?'':'s'; ?></div>
	</div>
	<div class="kpi-tile">
		<div class="k-label"><?php echo $by === 'equipt' ? 'Equipment types affected' : 'Cars affected'; ?></div>
		<div class="k-value"><?php echo count($rows); ?></div>
		<div class="k-sub"><?php echo $equiptTracked ? 'of '.$equiptTracked.($by === 'equipt' ? ' in the equipment table' : ' cars on record') : 'distinct'; ?></div>
	</div>
	<div class="kpi-tile">
		<div class="k-label"><?php echo $by === 'equipt' ? 'Equipment with the highest number of faults' : 'Car with the highest number of faults'; ?></div>
		<div class="k-value k-value--name"><?php echo count($rows) ? htmlspecialchars($rows[0]['label']) : '&mdash;'; ?></div>
		<div class="k-sub"><?php echo $peakTotal; ?> failure<?php echo $peakTotal==1?'':'s'; ?></div>
	</div>
</div>

<?php
/* @levelfilter -- Same treatment as equipt_stats.php. The strip is captioned
   because the confusing part of a level filter is that these tiles keep
   showing every level while the rest of the page shows one -- without a line
   saying so, a reader has to work out for themselves which numbers moved. */
function msLevelUrl($lv){
	$q = $_GET;
	if(isset($q['level']) && (int)$q['level'] === $lv) unset($q['level']);
	else $q['level'] = $lv;
	return '?'.http_build_query($q);
}
$msClearUrl = '?'.http_build_query(array_diff_key($_GET, array('level'=>1)));
?>
<div class="kpi-striphead">
<?php if($level){ ?>
	Severity &mdash; <b>showing Level <?php echo $level; ?> only</b>.
	These counts cover <em>all</em> levels for the period, so you can switch;
	everything else on the page is Level <?php echo $level; ?>.
	<a href="<?php echo htmlspecialchars($msClearUrl); ?>" class="kpi-clear">Show all levels</a>
<?php } else { ?>
	Severity &mdash; click a level to show only its failures below.
<?php } ?>
</div>
<div class="kpi-strip">
<?php foreach($TILE_LEVELS as $lv){
	$c = isset($levelCounts[(string)$lv]) ? $levelCounts[(string)$lv] : 0;
	$isActive = ($level === $lv);
	/* A level with no failures is not worth a click: filtering to it empties
	   the page and says nothing the tile has not already said. */
	$canClick = ($c > 0);
?>
	<?php if($canClick){ ?><a class="kpi-tile kpi-tile--link<?php echo $isActive ? ' kpi-tile--on' : ''; ?><?php echo ($level && !$isActive) ? ' kpi-tile--dim' : ''; ?>" href="<?php echo htmlspecialchars(msLevelUrl($lv)); ?>" title="<?php echo $isActive ? 'Show all levels again' : 'Show only Level '.$lv; ?>"><?php } else { ?><div class="kpi-tile<?php echo $level ? ' kpi-tile--dim' : ''; ?>"><?php } ?>
		<div class="k-label">Level <?php echo $lv; ?><?php echo $isActive ? ' &mdash; showing' : ''; ?></div>
		<div class="k-value" style="<?php echo (!$isActive && $lv>=3) ? 'color:#7A1F1F;' : ''; ?>"><?php echo $c; ?></div>
		<div class="k-sub"><?php echo $levelledFail ? round($c/$levelledFail*100).'% of all levels' : '&mdash;'; ?></div>
	<?php if($canClick){ ?></a><?php } else { ?></div><?php } ?>
<?php } ?>
</div>

<h3 class="brk-head">By <?php echo $by === 'equipt' ? 'equipment' : 'car'; ?></h3>
<table id='equipt_table' class="table table-striped table-bordered bootstrap-datatable datatable2 eq-table" border=1 style='border-collapse:collapse;' width=100%>
<colgroup><col class="c-name"><col class="c-num"><col class="c-num"></colgroup>
<thead>
<tr>
	<th><?php echo $by === 'equipt' ? 'Equipment' : 'Car'; ?></th>
	<th>Failures</th>
	<th>Share</th>
</tr>
</thead>
<tbody>
<?php foreach($rows as $r){
	$isFlagged = ($peakTotal > 0 && $r['count'] >= $flagThreshold);
?>
<tr<?php if($isFlagged){ echo " class='eq-flag'"; } ?>>
	<th><?php echo htmlspecialchars($r['label']); ?></th>
	<td align=center><?php echo $r['count']; ?></td>
	<td align=center><?php echo $equipt_count ? round($r['count']/$equipt_count*100).'%' : '&mdash;'; ?></td>
</tr>
<?php } ?>
<?php if(!count($rows)){ ?>
<tr><td colspan="3" align=center style="padding:18px;opacity:.6;">No failures recorded in this period.</td></tr>
<?php } ?>
</tbody>
<tfoot>
<tr style="background:#F1EEE3;font-weight:700;">
	<th style="text-align:left;">Total</th>
	<td align=center><?php echo $equipt_count; ?></td>
	<td align=center><?php echo $equipt_count ? '100%' : '&mdash;'; ?></td>
</tr>
</tfoot>
</table>

<?php
/* @monthstats -- The period breakdown. On a page whose subject IS the period,
   this is the table that answers "was it evenly spread or one bad day", which
   the by-car / by-equipment table above cannot show at all.
   Same grain rule as the sibling pages: one month in scope breaks down by day,
   several by month. */
if(count($periodBuckets) > 1){
?>
<h3 class="brk-head"><?php echo htmlspecialchars($periodHeading); ?></h3>
<table id='period_table' class="table table-striped table-bordered bootstrap-datatable datatable2 eq-table" border=1 style='border-collapse:collapse;' width=100%>
<colgroup><col class="c-name"><col class="c-num"><col class="c-num"></colgroup>
<thead>
<tr>
	<th><?php echo $grain === 'day' ? 'Day' : ($grain === 'year' ? 'Year' : 'Month'); ?></th>
	<th>Failures</th>
	<th>Share</th>
</tr>
</thead>
<tbody>
<?php
/* Chronological, not ranked: on a period page read top to bottom this is a
   timeline, and the red rows already carry which was worst. */
foreach($periodBuckets as $pk => $pCount){
	$isFlagged = ($peakPeriodCount > 0 && $pCount >= $periodThreshold);
?>
<tr<?php if($isFlagged){ echo " class='eq-flag'"; } ?>>
	<th><?php echo htmlspecialchars(msPeriodLabel($grain, $pk, $labelWithYear)); ?></th>
	<td align=center><?php echo $pCount; ?></td>
	<td align=center><?php echo $equipt_count ? round($pCount/$equipt_count*100).'%' : '&mdash;'; ?></td>
</tr>
<?php } ?>
</tbody>
<tfoot>
<tr style="background:#F1EEE3;font-weight:700;">
	<th style="text-align:left;">Total</th>
	<td align=center><?php echo $equipt_count; ?></td>
	<td align=center><?php echo $equipt_count ? '100%' : '&mdash;'; ?></td>
</tr>
</tfoot>
</table>
<?php } ?>

<div style="font-size:12px;color:#5A6275;margin-top:8px;">
	<span style="color:#7A1F1F;font-weight:700;">Rows in red</span>
	are equipment at or above 60% of the highest total (<?php echo round($flagThreshold,1); ?> failures) &mdash; the review threshold.
<?php if($unrecordedFail > 0){ ?>
	<div style="margin-top:6px;color:#7A1F1F;">
		<?php echo $unrecordedFail; ?> of these <?php echo $equipt_count; ?> failures have no equipment recorded on the incident
		(<?php echo round($unrecordedFail/$equipt_count*100); ?>%). They are listed as &ldquo;Not recorded&rdquo; rather than dropped, so this
		page&rsquo;s Total matches the Most Fault-Prone Car figure on the summary report. Incidents whose equipment was
		captured through the <code>incident_equipt</code> junction rather than the legacy <code>incident_report.equipt</code>
		column will land here until this page is moved onto the shared resolver.
	</div>
<?php } ?>
<?php if($otherLevel > 0){ ?>
	<div style="margin-top:6px;">
		<?php echo $otherLevel; ?> of these <?php echo $equipt_count; ?> failures have no severity level recorded, so the level
		figures above are shares of the <?php echo $levelledFail; ?> that do.
	</div>
<?php } ?>
	<div style="margin-top:6px;">
		Figures count <b>car-level failures</b> for this car: an incident affecting three cars counts once against each, so
		<?php echo $distinctIncidents; ?> incident<?php echo $distinctIncidents==1?'':'s'; ?>
		produce <?php echo $equipt_count; ?> car-level failure<?php echo $equipt_count==1?'':'s'; ?>.
		This is the basis the equipment summary and per-car reports use, so they reconcile; the incident history logs count one row per incident and show the smaller figure.
	</div>
</div>

<script>
/* @printout -- This page is normally an iframe inside the train_operations
   slide panel, which rules out the two obvious approaches:
     - window.print() from in here prints the FRAME, and browsers disagree
       about whether that means the panel, the host page, or nothing;
     - a print stylesheet on the host cannot see into a cross-document frame.
   So it does what the other stats pages do: open a fresh top-level window and
   write a self-contained document into it. window.open() is unaffected by
   being framed, and the result is a real page the browser prints normally.
   No dependency on the host page, so the button also works when car_stats.php
   is opened directly. */
/* @monthstats -- csCar is a leftover from the car_stats copy this page was
   built from. On a PERIOD page there is no single car, so it is 0, and the
   printout was headed "Equipment Failures - Car 0". Kept only as the car
   FILTER value, which is what it actually means here. */
var csBy         = <?php echo json_encode($by); ?>;
<?php
/* Resolved once here rather than in the meta strip, so the printout names the
   equipment the same way the heading does. */
$msEqName = '';
if($equipt){
	$eqr = $db->query("select equipment_name from equipment where id='".$equipt."'");
	if($eqr && ($eqw = $eqr->fetch_assoc())) $msEqName = (string)$eqw['equipment_name'];
	if($msEqName === '') $msEqName = 'Equipment #'.$equipt;
}
?>
var csEquiptName = <?php echo json_encode($msEqName); ?>;
/* @monthstats -- The heading names the SUBJECT, and this page's subject is the
   period. It was naming the breakdown AXIS instead, which fails twice: the
   axis flips with ?by=, so the same report changed its own name depending on
   which tile opened it, and neither name mentioned the period the report is
   actually about.
   The sibling pages can name their axis in the heading because theirs is
   fixed -- car_stats always breaks down by equipment, equipt_stats always by
   car. This page's is a toggle, so the axis belongs where it already is: the
   section heading above each table, "By car" / "By equipment".
   "Period Breakdown" is also what the slide-panel header says, so the printout
   now matches what was on screen when it was generated. */
var csSubject    = "Period Breakdown";
var csPeriod     = <?php echo json_encode($period); ?>;
var csLevelOnly  = <?php echo (int)$level; ?>;   /* @levelfilter */
var csCarFilter  = <?php echo (int)$car; ?>;      /* @monthstats */
var csFrom       = <?php echo json_encode(date("d M Y", strtotime($start_date1))); ?>;
var csTo         = <?php echo json_encode(date("d M Y", strtotime($end_date1))); ?>;
var csTotal      = <?php echo (int)$equipt_count; ?>;
var csIncidents  = <?php echo (int)$distinctIncidents; ?>;
var csTypes      = <?php echo (int)count($rows); ?>;
var csTracked    = <?php echo (int)$equiptTracked; ?>;
var csPeakName   = <?php echo json_encode(count($rows) ? $rows[0]['label'] : ''); ?>;
var csPeakTotal  = <?php echo (int)$peakTotal; ?>;
var csUnlevelled = <?php echo (int)$otherLevel; ?>;
var csLevelled   = <?php echo (int)$levelledFail; ?>;
var csLevels     = <?php
	$lvOut=array();
	foreach($TILE_LEVELS as $lv){ $lvOut[]=array($lv, isset($levelCounts[(string)$lv]) ? (int)$levelCounts[(string)$lv] : 0); }
	echo json_encode($lvOut);
?>;
var csCoverage   = <?php echo json_encode($coverageNote); ?>;
var csThreshold  = <?php echo json_encode(round($flagThreshold,1)); ?>;

function csPrintReport(){
	function esc(x){ return String(x).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

	/* @monthstats -- collected generically: the period table only renders for
	   some periods, so naming tables individually would print a placeholder for
	   whichever is absent. Headings travel with their tables, in page order. */
	var blocks = document.querySelectorAll('.ccs-panel-body h3.brk-head, .ccs-panel-body table.eq-table');
	var tableHtml = '';
	for(var bi=0; bi<blocks.length; bi++){
		var el = blocks[bi];
		tableHtml += (el.tagName === 'H3') ? '<h2 class="sec">'+el.innerHTML+'</h2>' : el.outerHTML;
	}
	if(!tableHtml){ tableHtml = '<p>No table to print.</p>'; }

	/* @levelprint -- On screen the active level is a filled tile under a
	   caption. In print that was all lost: the table listed every level with
	   nothing marking which one the rest of the report is filtered to, so a
	   reader saw four counts disagreeing with every other figure on the page
	   and no explanation. The active row is marked, and a note says what these
	   counts are. */
	var levelRows = csLevels.map(function(r){
		var pct = csLevelled ? Math.round(r[1]/csLevelled*100)+'%' : '\u2014';
		var on  = (csLevelOnly && r[0] === csLevelOnly);
		return '<tr'+(on ? ' class="lv-on"' : '')+'><th>Level '+r[0]
		     + (on ? ' \u2190 this report' : '') + '</th><td>'+r[1]+'</td><td>'+pct+'</td></tr>';
	}).join('');

	var win = window.open('', '_blank');
	/* Framed pages are a common trigger for popup blocking, so say what
	   happened instead of leaving a button that appears to do nothing. */
	if(!win){ alert('The printout opens in a new window. Please allow pop-ups for this site and try again.'); return; }

	win.document.write(
		'<html><head><title>'+esc(csSubject)+' \u2014 '+esc(csPeriod)+'</title>' +
		'<style>' +
			'@page{ size:A4 portrait; margin:12mm 10mm 13mm; }' +
			'*{ box-sizing:border-box; }' +
			'body{ font-family:"Segoe UI",Arial,Helvetica,sans-serif; color:#1a1a1a; margin:0; font-size:11px; line-height:1.45;' +
				' -webkit-print-color-adjust:exact; print-color-adjust:exact; }' +
			'.rpt-head{ border-bottom:2px solid #1f4e79; padding-bottom:9px; margin-bottom:4px; }' +
			'.rpt-org{ font-size:8.5px; letter-spacing:.15em; text-transform:uppercase; color:#6b7280; margin-bottom:3px; }' +
			'.rpt-title{ font-size:19px; font-weight:600; color:#1f4e79; margin:0 0 1px; }' +
			'.rpt-subject{ font-size:12.5px; color:#374151; margin:0; }' +
			'.rpt-meta{ margin:8px 0 0; font-size:9.5px; color:#4b5563; }' +
			'.rpt-meta span{ margin-right:20px; white-space:nowrap; }' +
			'.rpt-meta b{ color:#1f2937; font-weight:600; }' +
			'h2.sec{ font-size:11px; text-transform:uppercase; letter-spacing:.09em; color:#1f4e79;' +
				' border-bottom:1px solid #d1d5db; padding-bottom:4px; margin:18px 0 10px; font-weight:600; }' +
			'.note{ font-size:9px; color:#6b7280; font-style:italic; margin:4px 0 0; }' +
			/* @levelprint -- the filtered level, marked so it survives to paper. */
			'tr.lv-on th, tr.lv-on td{ background:#00529B !important; color:#fff !important; font-weight:700; }' +
			/* @printtiles -- the on-screen KPI strip, rebuilt for print. Same
			   label / value / sub structure, sized for paper. table-layout
			   fixed rather than flex because print engines size flex children
			   inconsistently across page breaks. */
			'table.kpi{ width:100%; table-layout:fixed; border-collapse:separate; border-spacing:6px 0; margin:0 -6px 2px; }' +
			'table.kpi td{ border:1px solid #d1d5db; border-radius:4px; padding:7px 9px; text-align:left;' +
				' vertical-align:top; background:#FBFAF6; }' +
			'.kpi-l{ font-size:7.5px; letter-spacing:.06em; text-transform:uppercase; color:#6b7280; margin-bottom:2px; }' +
			'.kpi-v{ font-size:17px; font-weight:600; color:#1f4e79; line-height:1.1; }' +
			'.kpi-v.name{ font-size:11px; color:#7A1F1F; line-height:1.25; }' +
			'.kpi-s{ font-size:8px; color:#6b7280; margin-top:2px; }' +
			/* @tablestyle -- Matched to car_history.php's printout, which is the
			   one that reads cleanly. Two things were making these heavier:

			   1. Every data row's FIRST cell is a <th>, not a <td>. car_history
			      has no body th at all, so its 'th{background:navy}' only ever
			      hits the header. Here that rule would paint the whole label
			      column navy, so it had been given its own beige fill -- which
			      turned column one into a second header running down the page and
			      buried the striping underneath it. Body th is styled as a td now,
			      and only the leading column keeps its left alignment.
			   2. Full 1px boxes around every cell. Horizontal rules only, so the
			      stripe does the column separation instead of a grid. */
			'table{ width:100%; border-collapse:collapse; font-size:9.5px; margin-bottom:2px; }' +
			'thead{ display:table-header-group; }' +
			/* @gridlines -- I had cut these to border-bottom only. car_history.php,
			   the printout this is matched to, uses a full 1px box on every cell --
			   dropping the vertical rules left the columns floating, which is the
			   missing lines. Full grid restored, in the same hairline grey.
			   And the numeric columns are centred, not right-aligned: they were
			   centred on screen and in the previous printout, and right-alignment
			   only earns its keep when figures need decimal alignment. */
			'thead th{ background:#1f4e79; color:#fff; text-align:center; padding:6px 7px;' +
				' font-size:9px; font-weight:600; text-transform:uppercase;' +
				' letter-spacing:.04em; border:1px solid #1f4e79; }' +
			'thead th:first-child{ text-align:left; }' +
			'tbody th, tbody td{ padding:5px 7px; background:none; color:#1a1a1a;' +
				' font-weight:400; vertical-align:top; border:1px solid #e5e7eb; }' +
			'tbody th{ text-align:left; font-weight:500; overflow-wrap:anywhere; }' +
			'tbody td{ text-align:center; }' +
			/* @stripe -- every data table, not just .eq-table: the severity table
			   (.lv) is built inline and was left plain, so one report carried two
			   table styles. .kpi is excluded -- a tile strip, not a data table.
			   Must follow the tbody rule above; equal weight, later wins. */
			'table:not(.kpi) tbody tr:nth-child(even) th,' +
			'table:not(.kpi) tbody tr:nth-child(even) td{ background:#f6f8fa; }' +
			/* The Total row: one rule above it, no heavy fill competing with the
			   stripe underneath. */
			'tfoot th, tfoot td{ background:none; font-weight:700; padding:5px 7px;' +
				' border:1px solid #e5e7eb; border-top:2px solid #1f4e79; }' +
			'tfoot th{ text-align:left; } tfoot td{ text-align:center; }' +
			'table.eq-table{ table-layout:fixed; }' +
			'table.lv{ width:auto; min-width:190px; }' +
			/* Threshold rows: red text and weight, no fill -- the pink fill read
			   as an error state and fought the striping. */
			'tr.eq-flag th, tr.eq-flag td{ color:#7A1F1F !important; font-weight:700; }' +
			'tr.eq-flag th a{ color:#7A1F1F !important; }' +
			'tr{ page-break-inside:avoid; }' +
			/* The equipment names are links on screen; inline colours would win
			   without !important, and a printed link should not look clickable. */
			'a{ color:inherit !important; text-decoration:none !important; pointer-events:none; }' +
			'.rpt-foot{ margin-top:12px; border-top:1px solid #d1d5db; padding-top:6px; font-size:8.5px; color:#6b7280; }' +
		'</style></head><body>' +
		'<div class="rpt-head">' +
			'<div class="rpt-org">DOTr &middot; MRT-3 Line 3 &middot; Operations Control</div>' +
			'<h1 class="rpt-title">'+esc(csSubject)+'</h1>' +
			'<p class="rpt-subject">'+esc(csPeriod)+'</p>' +
		'</div>' +
		'<div class="rpt-meta">' +
			/* @printtiles -- failures / incidents / types moved down into the
			   tiles, so the meta strip no longer states them twice. */
			/* @monthstats -- csCar is a leftover from the car_stats copy this page
			   was built from; the subject here is the PERIOD, and any car or
			   equipment narrowing is already in the heading. Replaced with the
			   filters that actually apply. */
			'<span><b>Period:</b> '+esc(csPeriod)+'</span>' +
			(csCarFilter  ? '<span><b>Car:</b> '+csCarFilter+' only</span>' : '') +
			(csEquiptName ? '<span><b>Equipment:</b> '+esc(csEquiptName)+' only</span>' : '') +
			(csLevelOnly  ? '<span><b>Level:</b> '+csLevelOnly+' only</span>' : '') +
			'<span><b>Generated:</b> <?php echo date("d M Y, H:i"); ?></span>' +
		'</div>' +
		'<h2 class="sec">Key Figures</h2>' +
		'<table class="kpi"><tr>' +
			'<td><div class="kpi-l">Car-level failures</div>' +
				'<div class="kpi-v">'+csTotal+'</div>' +
				'<div class="kpi-s">from '+csIncidents+' incident'+(csIncidents===1?'':'s')+'</div></td>' +
			'<td><div class="kpi-l">Equipment types affected</div>' +
				'<div class="kpi-v">'+csTypes+'</div>' +
				'<div class="kpi-s">'+(csTracked ? 'of '+csTracked+' in the equipment table' : 'distinct types')+'</div></td>' +
			'<td><div class="kpi-l">Equipment with the highest number of faults</div>' +
				'<div class="kpi-v name">'+(csPeakName ? esc(csPeakName) : '\u2014')+'</div>' +
				'<div class="kpi-s">'+csPeakTotal+' failure'+(csPeakTotal===1?'':'s')+'</div></td>' +
		'</tr></table>' +
		'<h2 class="sec">Severity</h2>' +
		'<table class="lv"><thead><tr><th>Level</th><th>Failures</th><th>Share</th></tr></thead><tbody>' +
			levelRows +
		'</tbody></table>' +
		(csLevelOnly
			? '<p class="note">These counts cover <b>all</b> levels for the period. Every other figure in this report is Level '
			  + csLevelOnly + ' only.</p>'
			: '') +
		(csUnlevelled ? '<p class="note">'+csUnlevelled+' of '+csTotal+' failures have no severity level recorded; shares above are of the '+csLevelled+' that do.</p>' : '') +
		tableHtml +
		'<p class="note">Rows in red are equipment at or above 60% of the highest total ('+esc(csThreshold)+' failures) \u2014 the review threshold.</p>' +
		'<p class="note">Figures count car-level failures for this car: an incident affecting several cars counts once against each, so '+csIncidents+' incident(s) produce '+csTotal+' car-level failure(s). This is the basis the equipment summary and per-car reports use, so they reconcile; the incident history logs count one row per incident and show the smaller figure.</p>' +
		'<div class="rpt-foot">MRT-3 Information Sharing System &middot; generated <?php echo date("d M Y, H:i"); ?> &middot; for internal operational use</div>' +
		'</body></html>'
	);
	win.document.close();
	win.focus();
	win.onload = function(){ setTimeout(function(){ win.print(); }, 250); };
}
</script>

</div><!-- /.ccs-panel-body -->
</div><!-- /.ccs-panel -->
</div><!-- /.ccs-page -->

</body>