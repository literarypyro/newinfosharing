<?php
session_start();
ini_set("date.timezone","Asia/Kuala_Lumpur");

/* =========================================================================
   equipt_stats.php — CAR breakdown for one equipment type, over a year or a
   month. The mirror image of car_stats.php: that page asks "which equipment
   failed on this car", this one asks "which cars did this equipment fail on".
   Reached from statistics_report_modified.php, in its slide panel.

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
// Cast to int so a junk equipt reads as 0 rather than as SQL.
$equipt = isset($_GET['equipt']) ? (int)$_GET['equipt'] : 0;

// @range -- statistics_report_modified.php filters by a From-To range, not by
// year/month. Anything spanning more than one calendar year used to arrive here
// with no year at all and render as All Time, which is why a Jan 2025 - Apr 2026
// report opened a panel covering everything ever recorded. The range is now
// carried through as sd/ed and used directly.
//
// Three ways in, in precedence order:
//     sd + ed        -> that exact range
//     year [+ month] -> that year, or that month
//     nothing        -> All Time
$sd = isset($_GET['sd']) && $_GET['sd'] !== '' ? strtotime($_GET['sd']) : false;
$ed = isset($_GET['ed']) && $_GET['ed'] !== '' ? strtotime($_GET['ed']) : false;
$hasRange = ($sd !== false && $ed !== false);
if($hasRange && $ed < $sd){ $t=$sd; $sd=$ed; $ed=$t; }   // swap, do not clamp

$hasYear = isset($_GET['year'])  && $_GET['year']  !== '';
$year    = $hasYear ? (int)$_GET['year'] : (int)date("Y");
$month   = isset($_GET['month']) && $_GET['month'] !== '' ? (int)$_GET['month'] : 0;
if($month < 1 || $month > 12){ $month = 0; }
if(!$hasYear){ $month = 0; }   // a month without a year is not a period

if($hasRange){
	$start_date1 = date("Y-m-d", $sd);
	$end_date1   = date("Y-m-d", $ed);
	$period      = date("d M Y", $sd)." to ".date("d M Y", $ed);
	// Inside one calendar month the useful grain is days; otherwise months.
	$sameMonth   = (date("Y-m", $sd) === date("Y-m", $ed));
	$grain       = $sameMonth ? 'day' : 'month';
	$year        = (int)date("Y", $sd);
	$month       = $sameMonth ? (int)date("n", $sd) : 0;
}
else if(!$hasYear){
	$start_date1 = '';
	$end_date1   = '';
	$period      = "All Time";
	$grain       = 'year';     // breakdown grain: year / month / day
}
else if($month){
	$start_date1 = sprintf("%04d-%02d-01", $year, $month);
	$end_date1   = date("Y-m-t", strtotime($start_date1));
	$period      = date("F Y", strtotime($start_date1));
	$grain       = 'day';
}
else {
	$start_date1 = sprintf("%04d-01-01", $year);
	$end_date1   = sprintf("%04d-12-31", $year);
	$period      = "Full year ".$year;
	$grain       = 'month';
}
$hasPeriod = ($hasRange || $hasYear);

// The subject of the page. LEFT-join semantics by hand: an id with no row in
// the equipment table still renders, named as such, rather than showing a
// blank heading that looks like a bug.
$equiptName = '';
if($equipt){
	$nq = $db->query("select equipment_name from equipment where id='".$equipt."'");
	if($nq && ($nr = $nq->fetch_assoc())){ $equiptName = (string)$nr['equipment_name']; }
	if($equiptName === ''){ $equiptName = 'Equipment #'.$equipt.' (not in equipment table)'; }
}

// ---- Full incident history link ------------------------------------------
// @historylink -- SET THE URL HERE. Pre-filled with the parameter names
// statistics_report_modified.php already uses for its own equipment_history
// links (equipt / y / m) -- change it if yours differ.

$carHistoryUrl = "equipment_history.php?equipt=".$equipt
               . ($hasYear ? "&y=".$year.($month ? "&m=".$month : "") : "");


// Inside the slide panel this page is an iframe, so a plain link would load
// car_history INSIDE the 820px panel. _top breaks it out into the full window.
// Change to "_blank" for a new tab, or "_self" to keep it in the panel.
$carHistoryTarget = $IR_EMBED ? "_top" : "_self";

// @invert -- The axis of the page. car_stats.php filters by car and groups by
// equipment; this filters by equipment and groups by car. Everything below
// follows from that one swap.
//
// No equipment IN() list here, for the same reason it was removed from
// car_stats.php: this page's Total has to match the "Equipment with the
// highest number of faults" tile on statistics_report_modified.php, and that
// tile counts every incident_cars row for the equipment.
$where = "incident_report.equipt = ".$equipt;
if($hasPeriod){
	$where .= " and incident_date between '".$start_date1." 00:00:00' and '".$end_date1." 23:59:59'";
}

// ---- Severity split ------------------------------------------------------
// The old query grouped by level but selected equipt, so $row['level'] was
// never set and every tile read from one undefined key.
$levelCounts = array();
$sql = "select incident_report.level as level, count(1) as c
          from incident_report
          inner join incident_cars on incident_report.id=incident_cars.incident_id
         where ".$where."
         group by incident_report.level";
$rs = $db->query($sql);
if($rs){
	while($row = $rs->fetch_assoc()){
		$levelCounts[(string)$row['level']] = (int)$row['c'];
	}
}

// ---- Car breakdown -------------------------------------------------------
// @invert -- One row per car. car_no is grouped with *1 so '05' and '5' fold
// together, matching how car_statistics_report.php buckets them; comparing the
// raw column would split one car across two rows.
$rows = array();
$equipt_count   = 0;
$unrecordedFail = 0;   /* failures on incidents with no car recorded */
$sql = "select incident_cars.car_no*1 as car_no, count(1) as car_count
          from incident_report
          inner join incident_cars on incident_report.id=incident_cars.incident_id
         where ".$where."
         group by incident_cars.car_no*1
         order by car_count desc";
$rs = $db->query($sql);
if($rs){
	while($row = $rs->fetch_assoc()){
		$cn    = (int)$row['car_no'];
		$blank = ($cn <= 0);
		if($blank){
			$label = 'Not recorded';
			$unrecordedFail += (int)$row['car_count'];
		}
		else { $label = 'Car '.$cn; }
		$rows[] = array('id'=>$cn, 'label'=>$label, 'count'=>(int)$row['car_count']);
		$equipt_count += (int)$row['car_count'];
	}
}

// ---- Period breakdown ----------------------------------------------------
// @invert -- Three grains, one query. car_stats.php has two (month/day) because
// it always has a year; this page can be opened with no year at all, and a page
// whose only breakdown is "by month" would then have nothing to show. The grain
// follows the filter:
//     no year        -> by year
//     year           -> by month
//     year + month   -> by day
$periodBuckets = array();
$pq = $db->query("select year(incident_date) as yr, month(incident_date) as mo,
                         day(incident_date) as dy, count(1) as c
                    from incident_report
                    inner join incident_cars on incident_report.id=incident_cars.incident_id
                   where ".$where."
                   group by year(incident_date), month(incident_date), day(incident_date)");
if($pq){
	while($pr = $pq->fetch_assoc()){
		// @range -- Keys are composite now. A bare month number merged March
		// 2025 with March 2026 into one row over a multi-year range, and the
		// merged row then carried whichever year the label happened to assume.
		// YYYYMM / YYYYMMDD also keep ksort chronological across a year boundary.
		if($grain === 'year')       $k = (int)$pr['yr'];
		else if($grain === 'month') $k = (int)$pr['yr']*100   + (int)$pr['mo'];
		else                        $k = (int)$pr['yr']*10000 + (int)$pr['mo']*100 + (int)$pr['dy'];
		if(!isset($periodBuckets[$k])) $periodBuckets[$k]=0;
		$periodBuckets[$k] += (int)$pr['c'];
	}
}

// @period -- Periods with no failures are omitted, as on car_stats.php. Every
// row here is labelled, so a reader sees January jump to March; a month of
// blank rows is just noise. $periodsOmitted feeds the caption, so the fact that
// periods are missing is still stated rather than left to be inferred.
$periodBuckets = array_filter($periodBuckets, function($c){ return $c > 0; });
ksort($periodBuckets);
// @range -- Counted against the span actually requested rather than a fixed 12
// or a whole month, so "10 months had no failures" stays true for a 16-month
// range as well as for a calendar year.
$periodsInSpan = 0;
if($grain === 'day' && $start_date1 !== ''){
	$periodsInSpan = (int)floor((strtotime($end_date1) - strtotime($start_date1))/86400) + 1;
}
else if($grain === 'month' && $start_date1 !== ''){
	$a = new DateTime(date("Y-m-01", strtotime($start_date1)));
	$b = new DateTime(date("Y-m-01", strtotime($end_date1)));
	$periodsInSpan = ($b->format('Y') - $a->format('Y'))*12 + ($b->format('n') - $a->format('n')) + 1;
}
$periodsOmitted = $periodsInSpan ? $periodsInSpan - count($periodBuckets) : 0;
if($periodsOmitted < 0) $periodsOmitted = 0;

// Its own threshold: reusing the car peak would flag months against a car
// figure, which is not a comparison.
$peakPeriodCount = count($periodBuckets) ? max($periodBuckets) : 0;
$periodThreshold = $peakPeriodCount * 0.60;

if($grain === 'year')       $periodHeading = 'By year';
else if($grain === 'month') $periodHeading = 'By month &mdash; '.($hasRange ? htmlspecialchars($period) : $year);
else                        $periodHeading = 'By day &mdash; '.($hasRange && $start_date1 !== date("Y-m-01", strtotime($start_date1))
                                                                ? htmlspecialchars($period)
                                                                : date("F Y", strtotime($start_date1)));

// @range -- Reads the composite key rather than assuming a single year. Month
// rows carry the year whenever the span crosses one, because "March" twice in
// the same table is worse than a slightly longer label.
function esPeriodLabel($grain, $k, $showYear){
	if($grain === 'year')  return (string)$k;
	if($grain === 'month'){
		$y = (int)floor($k/100); $m = $k % 100;
		return date($showYear ? "F Y" : "F", strtotime(sprintf("%04d-%02d-01", $y, $m)));
	}
	$y = (int)floor($k/10000); $m = (int)floor(($k%10000)/100); $d = $k % 100;
	return date($showYear ? "d M Y (l)" : "d (l)", strtotime(sprintf("%04d-%02d-%02d", $y, $m, $d)));
}
// @range -- Derived from the keys actually present, not just from the requested
// span. Same answer in the normal case, but it cannot produce two rows both
// labelled "March": if two years are in the table, every row carries its year.
$labelWithYear = false;
if($grain !== 'year'){
	$div = ($grain === 'month') ? 100 : 10000;
	$yrs = array();
	foreach(array_keys($periodBuckets) as $bk){ $yrs[(int)floor($bk/$div)] = true; }
	$labelWithYear = (count($yrs) > 1);
}

// Denominator for the "cars affected" tile: the size of the fleet as the data
// knows it, not a hard-coded 73, so a car added later is counted.
$carsTracked = 0;
$tq = $db->query("select count(distinct car_no*1) as c from incident_cars where car_no*1 > 0");
if($tq && ($tr = $tq->fetch_assoc())){ $carsTracked = (int)$tr['c']; }
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
$gapMonths    = $hasYear ? ccsUncoveredMonths($coverage, $start_date1, $end_date1) : array();

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

/* @period -- the breakdown tables need headings, or three stacked tables read
   as one long list with no indication of what the middle one counts. */
.brk-note { font-size:11px; color:#5A6275; margin:5px 0 0; font-style:italic; }
.brk-head { font-size:12px; text-transform:uppercase; letter-spacing:.07em; color:#00529B;
	border-bottom:1px solid #E5DECC; padding-bottom:5px; margin:22px 0 8px; font-weight:600; }

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
</style>
<?php include("history_theme.php"); ?>

<body>
<div class="ccs-page">

<div class="ccs-header">
<h1>Car Failures for <?php echo $equipt > 0 ? htmlspecialchars($equiptName) : '&mdash;'; ?></h1>
<div class='sub'><?php echo htmlspecialchars($period); ?></div>
</div>

<div class="ccs-panel">
<div class="ccs-panel-head">
<div class="stat-scope">
	<div class="scope-left">
		<?php echo $equipt > 0 ? htmlspecialchars($equiptName) : '&mdash;'; ?>
		<?php 
		if(isset($_POST['year'])){
			?>
		<span class="muted"><?php echo date("d M Y", strtotime($start_date1)); ?> &ndash; <?php echo date("d M Y", strtotime($end_date1)); ?></span>
		<?php
		}
		?>
	</div>
<?php if($equipt > 0){ /* @historylink -- no equipment, no action to offer */ ?>
	<div class="scope-actions">
		<button type="button" class="scope-btn scope-btn--ghost" onclick="csPrintReport()">Generate printout</button>
		<a class="scope-btn" href="<?php echo htmlspecialchars($carHistoryUrl); ?>" target="<?php echo $carHistoryTarget; ?>">Full incident history &rarr;</a>
	</div>
<?php } ?>
</div>
<div class="stat-legend">
	<span><span class="swatch" style="background:#00529B;"></span>Counts are car-level failures for this equipment</span>
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
		<div class="k-label">Cars affected</div>
		<div class="k-value"><?php echo count($rows); ?></div>
		<div class="k-sub"><?php echo $carsTracked ? 'of '.$carsTracked.' cars on record' : 'distinct cars'; ?></div>
	</div>
	<div class="kpi-tile">
		<div class="k-label">Car with the highest number of faults</div>
		<div class="k-value k-value--name"><?php echo count($rows) ? htmlspecialchars($rows[0]['label']) : '&mdash;'; ?></div>
		<div class="k-sub"><?php echo $peakTotal; ?> failure<?php echo $peakTotal==1?'':'s'; ?></div>
	</div>
</div>

<div class="kpi-strip">
<?php foreach($TILE_LEVELS as $lv){
	$c = isset($levelCounts[(string)$lv]) ? $levelCounts[(string)$lv] : 0;
?>
	<div class="kpi-tile">
		<div class="k-label">Level <?php echo $lv; ?></div>
		<div class="k-value" style="<?php echo $lv>=3 ? 'color:#7A1F1F;' : ''; ?>"><?php echo $c; ?></div>
		<div class="k-sub"><?php echo $levelledFail ? round($c/$levelledFail*100).'% of levelled' : '&mdash;'; ?></div>
	</div>
<?php } ?>
</div>
<?php /* @invert -- car_stats.php guards its equipment table with if(!$equipt),
        because drilling into one equipment makes an equipment breakdown
        pointless there. The mirror of that guard on THIS page would be
        if(!$car) -- and there is no $car here, so the car table is the
        point of the page and always renders. */ ?>

<h3 class="brk-head">By car</h3>
<table id='equipt_table' class="table table-striped table-bordered bootstrap-datatable datatable2 eq-table" border=1 style='border-collapse:collapse;' width=100%>
<colgroup><col class="c-name"><col class="c-num"><col class="c-num"></colgroup>
<thead>
<tr>
	<th>Car</th>
	<th>Failures</th>
	<th>Share</th>
</tr>
</thead>
<tbody>
<?php
// @period -- the month/day totals used to be accumulated here, from the stray
// per-equipment column. They come from the grouped query now, so this loop
// only draws equipment rows. The aggregation array was also called $month,
// which overwrote the $month INPUT set at the top of the file — anything below
// this point that read $month got an array of buckets instead of the selected
// month number.
foreach($rows as $r){
	$isFlagged = ($peakTotal > 0 && $r['count'] >= $flagThreshold);
?>
<tr<?php if($isFlagged){ echo " class='eq-flag'"; } ?>>
	<th><?php echo htmlspecialchars($r['label']); ?></th>
	<td align=center><?php echo $r['count']; ?></td>
	<td align=center><?php echo $equipt_count ? round($r['count']/$equipt_count*100).'%' : '&mdash;'; ?></td>
</tr>
<?php } ?>
<?php if(!count($rows)){ ?>
<tr><td colspan="3" align=center style="padding:18px;opacity:.6;">No failures recorded for this equipment in this period.</td></tr>
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
/* @invert -- One grain-driven table replaces the separate month and day tables.
   car_stats.php needs two because it always has a year; this page has three
   possible grains, and three near-identical copies of the same markup is how
   they drift apart. */
if(count($periodBuckets) || $grain !== 'year'){
?>
<h3 class="brk-head"><?php echo $periodHeading; ?></h3>
<table id='period_table' class="table table-striped table-bordered bootstrap-datatable datatable2 eq-table" border=1 style='border-collapse:collapse;' width=100%>
<colgroup><col class="c-name"><col class="c-num"><col class="c-num"></colgroup>
<thead>
<tr>
	<th><?php echo $grain === 'year' ? 'Year' : ($grain === 'month' ? 'Month' : 'Day'); ?></th>
	<th>Failures</th>
	<th>Share</th>
</tr>
</thead>
<tbody>
<?php
// @sort -- Ranked by failures, highest first.
//
// Three things were stopping this from running:
//   1. $b=>$pCount -- "=>" is array-literal syntax and is not valid in an
//      expression, which is the parse error on this line.
//   2. <==> -- the spaceship operator is three characters, <=>.
//   3. $pCount is the foreach variable from BELOW this block, so it does not
//      exist yet here. It would not help anyway: $periodBuckets is a flat map
//      of period-key => count, so the two arguments a uasort comparator
//      receives ARE the counts. There is no field to reach into.
//
// uksort rather than uasort so ties can fall back to the key: PHP's sort is
// only guaranteed stable from 8.0, and without an explicit tie-break two
// months on the same figure could come out in either order between runs. The
// keys are already chronological from the ksort where $periodBuckets is built,
// so equal counts read in date order.
uksort($periodBuckets, function($x, $y) use ($periodBuckets){
	$byCount = $periodBuckets[$y] <=> $periodBuckets[$x];   // descending
	return $byCount !== 0 ? $byCount : ($x <=> $y);         // then chronological
});

foreach($periodBuckets as $pk => $pCount){
	$isFlagged = ($peakPeriodCount > 0 && $pCount >= $periodThreshold);
?>
<tr<?php if($isFlagged){ echo " class='eq-flag'"; } ?>>
	<th><?php echo htmlspecialchars(esPeriodLabel($grain, $pk, $labelWithYear)); ?></th>
	<td align=center><?php echo $pCount; ?></td>
	<td align=center><?php echo $equipt_count ? round($pCount/$equipt_count*100).'%' : '&mdash;'; ?></td>
</tr>
<?php } ?>
<?php if(!count($periodBuckets)){ ?>
<tr><td colspan="3" align=center style="padding:18px;opacity:.6;">No failures recorded for this equipment in this period.</td></tr>
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
<?php if($periodsOmitted > 0){ ?>
<div class="brk-note">
	<?php /* @range -- names the span requested, not a hard-coded year/month. */ ?>
	<?php echo $periodsOmitted; ?>
	<?php echo $grain === 'day' ? 'day' : 'month'; ?><?php echo $periodsOmitted==1?'':'s'; ?>
	of <?php echo $hasRange ? htmlspecialchars($period) : ($grain === 'day' ? date("F Y", strtotime($start_date1)) : $year); ?>
	had no recorded failures for this equipment and <?php echo $periodsOmitted==1?'is':'are'; ?> not listed.
</div>
<?php } ?>
<?php
}
?>
<div style="font-size:12px;color:#5A6275;margin-top:8px;">
	<span style="color:#7A1F1F;font-weight:700;">Rows in red</span>
	are cars at or above 60% of the highest total (<?php echo round($flagThreshold,1); ?> failures) &mdash; the review threshold.
<?php if($unrecordedFail > 0){ ?>
	<div style="margin-top:6px;color:#7A1F1F;">
		<?php echo $unrecordedFail; ?> of these <?php echo $equipt_count; ?> failures have no car recorded on the incident
		(<?php echo round($unrecordedFail/$equipt_count*100); ?>%). They are listed as &ldquo;Not recorded&rdquo; rather than dropped, so this
		page&rsquo;s Total matches the equipment figure on the summary report.
	</div>
<?php } ?>
<?php if($otherLevel > 0){ ?>
	<div style="margin-top:6px;">
		<?php echo $otherLevel; ?> of these <?php echo $equipt_count; ?> failures have no severity level recorded, so the level
		figures above are shares of the <?php echo $levelledFail; ?> that do.
	</div>
<?php } ?>
	<div style="margin-top:6px;">
		Figures count <b>car-level failures</b> for this equipment: an incident affecting three cars counts once against each, so
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
var csEquipt     = <?php echo json_encode($equipt); ?>;
var csEquiptName = <?php echo json_encode($equiptName); ?>;
var csPeriod     = <?php echo json_encode($period); ?>;
var csFrom       = <?php echo json_encode(date("d M Y", strtotime($start_date1))); ?>;
var csTo         = <?php echo json_encode(date("d M Y", strtotime($end_date1))); ?>;
var csTotal      = <?php echo (int)$equipt_count; ?>;
var csIncidents  = <?php echo (int)$distinctIncidents; ?>;
var csTypes      = <?php echo (int)count($rows); ?>;
var csTracked    = <?php echo (int)$carsTracked; ?>;
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

	/* @period -- collected generically rather than by id. Only one of
	   #month_table / #day_table exists on any given render, so naming them
	   individually meant the printout either missed the day breakdown or
	   printed a "No table to print" placeholder for whichever was absent.
	   This walks headings and tables in document order, so the printout keeps
	   the same sequence as the screen and picks up a fourth table for free if
	   one is ever added. */
	var blocks = document.querySelectorAll('.ccs-panel-body h3.brk-head, .ccs-panel-body table.eq-table, .ccs-panel-body div.brk-note');
	var tableHtml = '';
	for(var bi=0; bi<blocks.length; bi++){
		var el = blocks[bi];
		if(el.tagName === 'H3')      tableHtml += '<h2 class="sec">'+el.innerHTML+'</h2>';
		else if(el.tagName === 'DIV') tableHtml += '<p class="note">'+el.innerHTML+'</p>';
		else                          tableHtml += el.outerHTML;
	}
	if(!tableHtml){ tableHtml = '<p>No table to print.</p>'; }

	var levelRows = csLevels.map(function(r){
		var pct = csLevelled ? Math.round(r[1]/csLevelled*100)+'%' : '\u2014';
		return '<tr><th>Level '+r[0]+'</th><td>'+r[1]+'</td><td>'+pct+'</td></tr>';
	}).join('');

	var win = window.open('', '_blank');
	/* Framed pages are a common trigger for popup blocking, so say what
	   happened instead of leaving a button that appears to do nothing. */
	if(!win){ alert('The printout opens in a new window. Please allow pop-ups for this site and try again.'); return; }

	win.document.write(
		'<html><head><title>Car Failures \u2014 '+esc(csEquiptName)+'</title>' +
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
			'table{ width:100%; border-collapse:collapse; font-size:9px; margin-bottom:2px; }' +
			'thead{ display:table-header-group; }' +
			'thead th{ background:#1f4e79; color:#fff; text-align:center; padding:4px 3px; font-size:8px; font-weight:600;' +
				' text-transform:uppercase; letter-spacing:.03em; border:1px solid #1f4e79; }' +
			/* Each data row's first cell is a th too, so the header fill has to
			   be scoped to thead or the whole Equipment column goes navy. */
			'tbody th, tfoot th{ background:#F1EFE8; color:#1a1a1a; text-align:left; padding:3px 5px; font-size:9px;' +
				' font-weight:600; border:1px solid #e5e7eb; }' +
			'td{ padding:3px; border:1px solid #e5e7eb; text-align:center; }' +
			/* @cols -- mirrors the screen: fixed columns, wrapping names, and
			   the threshold shown as red text rather than a pink fill. */
			'table.eq-table{ table-layout:fixed; }' +
			'table.eq-table tbody th{ text-align:left; overflow-wrap:anywhere; }' +
			'table.eq-table tbody tr:nth-child(even) th,' +
			'table.eq-table tbody tr:nth-child(even) td{ background:#F7F5EE; }' +
			'tr.eq-flag th, tr.eq-flag td{ color:#7A1F1F !important; font-weight:700; }' +
			'tr.eq-flag th a{ color:#7A1F1F !important; }' +
			'tfoot td{ background:#F1EFE8; font-weight:700; }' +
			'tr{ page-break-inside:avoid; }' +
			'table.lv{ width:auto; min-width:180px; }' +
			/* The equipment names are links on screen; inline colours would win
			   without !important, and a printed link should not look clickable. */
			'a{ color:inherit !important; text-decoration:none !important; pointer-events:none; }' +
			'.rpt-foot{ margin-top:12px; border-top:1px solid #d1d5db; padding-top:6px; font-size:8.5px; color:#6b7280; }' +
		'</style></head><body>' +
		'<div class="rpt-head">' +
			'<div class="rpt-org">DOTr &middot; MRT-3 Line 3 &middot; Operations Control</div>' +
			'<h1 class="rpt-title">Car Failures</h1>' +
			'<p class="rpt-subject">'+esc(csEquiptName)+' &middot; '+esc(csPeriod)+'</p>' +
		'</div>' +
		'<div class="rpt-meta">' +
			/* @printtiles -- failures / incidents / types moved down into the
			   tiles, so the meta strip no longer states them twice. */
			'<span><b>Equipment:</b> '+esc(csEquiptName)+'</span>' +
			'<span><b>Period:</b> '+esc(csFrom)+' &ndash; '+esc(csTo)+'</span>' +
			'<span><b>Generated:</b> <?php echo date("d M Y, H:i"); ?></span>' +
		'</div>' +
		'<h2 class="sec">Key Figures</h2>' +
		'<table class="kpi"><tr>' +
			'<td><div class="kpi-l">Car-level failures</div>' +
				'<div class="kpi-v">'+csTotal+'</div>' +
				'<div class="kpi-s">from '+csIncidents+' incident'+(csIncidents===1?'':'s')+'</div></td>' +
			'<td><div class="kpi-l">Cars affected</div>' +
				'<div class="kpi-v">'+csTypes+'</div>' +
				'<div class="kpi-s">'+(csTracked ? 'of '+csTracked+' cars on record' : 'distinct cars')+'</div></td>' +
			'<td><div class="kpi-l">Car with the highest number of faults</div>' +
				'<div class="kpi-v name">'+(csPeakName ? esc(csPeakName) : '\u2014')+'</div>' +
				'<div class="kpi-s">'+csPeakTotal+' failure'+(csPeakTotal===1?'':'s')+'</div></td>' +
		'</tr></table>' +
		'<h2 class="sec">Severity</h2>' +
		'<table class="lv"><thead><tr><th>Level</th><th>Failures</th><th>Share</th></tr></thead><tbody>' +
			levelRows +
		'</tbody></table>' +
		(csUnlevelled ? '<p class="note">'+csUnlevelled+' of '+csTotal+' failures have no severity level recorded; shares above are of the '+csLevelled+' that do.</p>' : '') +
		tableHtml +
		'<p class="note">Rows in red are at or above 60% of the highest total in their own table \u2014 the review threshold.</p>' +
		'<p class="note">Figures count car-level failures for this equipment: an incident affecting several cars counts once against each, so '+csIncidents+' incident(s) produce '+csTotal+' car-level failure(s). This is the basis the equipment summary and per-car reports use, so they reconcile; the incident history logs count one row per incident and show the smaller figure.</p>' +
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