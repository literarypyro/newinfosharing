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
// $car went straight into the WHERE clause unquoted-by-intent before; casting
// to int both fixes that and makes a junk car_id read as 0 rather than as SQL.
$car   = isset($_GET['car_id']) ? (int)$_GET['car_id'] : 0;
$year  = isset($_GET['year'])   ? (int)$_GET['year']   : (int)date("Y");
$month = isset($_GET['month']) && $_GET['month'] !== '' ? (int)$_GET['month'] : 0;
if($month < 1 || $month > 12){ $month = 0; }

if($month){
	$start_date1 = sprintf("%04d-%02d-01", $year, $month);
	// The month branch used to set $end_date1 to the FIRST of the month, so a
	// month drill-down returned one day. "t" gives the last day of the month.
	$end_date1   = date("Y-m-t", strtotime($start_date1));
	$period      = date("F Y", strtotime($start_date1));
}
else {
	$start_date1 = sprintf("%04d-01-01", $year);
	$end_date1   = sprintf("%04d-12-31", $year);
	$period      = "Full year ".$year;
}

// ---- Full incident history link ------------------------------------------
// @historylink -- SET THE URL HERE. This is the only line to edit; the button
// below reads it. Pre-filled with the parameter names car_statistics_report.php
// already uses for its own car_history links (car_id / y / m), so it is a
// working guess rather than a blank -- change it if yours differ.
$carHistoryUrl = "car_history.php?car_id=".$car."&y=".$year.($month ? "&m=".$month : "");

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
$where = "incident_cars.car_no*1 = ".$car."
          and incident_date between '".$start_date1." 00:00:00' and '".$end_date1." 23:59:59'";

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

// ---- Equipment breakdown -------------------------------------------------
// @tally -- name resolved in the query via LEFT JOIN rather than a getEquipt()
// call per row, which was one extra query per equipment type and per tile.
// LEFT so that a blank or unknown equipt still yields its row.
$rows = array();
$equipt_count   = 0;
$unrecordedFail = 0;   /* failures whose incident has no equipment recorded */
$sql = "select incident_report.equipt as equipt,
               equipment.equipment_name as equipment_name,
               count(1) as equipt_count
          from incident_report
          inner join incident_cars on incident_report.id=incident_cars.incident_id
          left  join equipment on equipment.id = incident_report.equipt
         where ".$where."
         group by incident_report.equipt, equipment.equipment_name
         order by equipt_count desc";
$rs = $db->query($sql);
if($rs){
	while($row = $rs->fetch_assoc()){
		$id    = $row['equipt'];
		$blank = ($id === null || trim((string)$id) === '' || (int)$id === 0);
		if($blank){
			$label = 'Not recorded';
			$unrecordedFail += (int)$row['equipt_count'];
		}
		elseif($row['equipment_name'] !== null && $row['equipment_name'] !== ''){
			$label = $row['equipment_name'];
		}
		else {
			/* id present but no matching equipment row — surfaced, not hidden */
			$label = 'Equipment #'.(int)$id.' (not in equipment table)';
		}
		$rows[] = array('id'=>$id, 'label'=>$label, 'count'=>(int)$row['equipt_count']);
		$equipt_count += (int)$row['equipt_count'];
	}
}
// Denominator for the "types affected" tile, now that there is no fixed list.
$equiptTracked = 0;
$tq = $db->query("select count(*) as c from equipment");
if($tq && ($tr = $tq->fetch_assoc())){ $equiptTracked = (int)$tr['c']; }
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

// Levels 1-4 get their own tile. Anything else the data holds (level 0, null,
// or a value beyond 4) is collected into one further tile rather than dropped
// — otherwise the tiles would silently fail to add up to the table's total.
$TILE_LEVELS = array(1,2,3,4);
$otherLevel  = 0;
foreach($levelCounts as $lv => $c){
	if(!in_array((int)$lv, $TILE_LEVELS, true) || $lv === '' || $lv === null){ $otherLevel += $c; }
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
<h1>Equipment Failures for Car <?php echo $car > 0 ? $car : '&mdash;'; ?></h1>
<div class='sub'><?php echo htmlspecialchars($period); ?></div>
</div>

<div class="ccs-panel">
<div class="ccs-panel-head">
<div class="stat-scope">
	<div class="scope-left">
		Car <?php echo $car > 0 ? $car : '&mdash;'; ?>
		<span class="muted"><?php echo date("d M Y", strtotime($start_date1)); ?> &ndash; <?php echo date("d M Y", strtotime($end_date1)); ?></span>
	</div>
<?php if($car > 0){ /* @historylink -- no car, no action to offer */ ?>
	<a class="scope-btn" href="<?php echo htmlspecialchars($carHistoryUrl); ?>" target="<?php echo $carHistoryTarget; ?>">Full incident history &rarr;</a>
<?php } ?>
</div>
<div class="stat-legend">
	<span><span class="swatch" style="background:#00529B;"></span>Counts are car-level failures for this car</span>
	<span><span class="swatch" style="background:#F9D6D6; border:1px solid #E3A9A9;"></span>Highlighted row = among the highest counts in this period (&ge;60% of the peak)</span>
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
		<div class="k-label">Equipment types affected</div>
		<div class="k-value"><?php echo count($rows); ?></div>
		<div class="k-sub"><?php echo $equiptTracked ? 'of '.$equiptTracked.' in the equipment table' : 'distinct types'; ?></div>
	</div>
	<div class="kpi-tile">
		<div class="k-label">Equipment with the highest number of faults</div>
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
		<div class="k-sub"><?php echo $equipt_count ? round($c/$equipt_count*100).'% of the period' : '&mdash;'; ?></div>
	</div>
<?php } ?>
<?php if($otherLevel > 0){ ?>
	<div class="kpi-tile">
		<div class="k-label">Unlevelled</div>
		<div class="k-value"><?php echo $otherLevel; ?></div>
		<div class="k-sub">outside levels 1&ndash;4</div>
	</div>
<?php } ?>
</div>

<table class="table table-striped table-bordered bootstrap-datatable datatable2" border=1 style='border-collapse:collapse;' width=100%>
<thead>
<tr>
	<th>Equipment</th>
	<th>Failures</th>
	<th>Share</th>
</tr>
</thead>
<tbody>
<?php foreach($rows as $r){
	$isFlagged = ($peakTotal > 0 && $r['count'] >= $flagThreshold);
?>
<tr<?php if($isFlagged){ echo " style='background-color:#F9D6D6; color:#7A1F1F;'"; } ?>>
	<th style="text-align:left;font-weight:600;"><?php echo htmlspecialchars($r['label']); ?></th>
	<td align=center><?php echo $r['count']; ?></td>
	<td align=center><?php echo $equipt_count ? round($r['count']/$equipt_count*100).'%' : '&mdash;'; ?></td>
</tr>
<?php } ?>
<?php if(!count($rows)){ ?>
<tr><td colspan="3" align=center style="padding:18px;opacity:.6;">No equipment failures recorded for this car in this period.</td></tr>
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

<div style="font-size:12px;color:#5A6275;margin-top:8px;">
	<span style="display:inline-block;width:11px;height:11px;background:#F9D6D6;border:1px solid #7A1F1F;vertical-align:-1px;"></span>
	Shaded rows are equipment at or above 60% of the highest total (<?php echo round($flagThreshold,1); ?> failures) &mdash; the review threshold.
	<?php if($coverageNote !== ''){ ?>
	<div style="margin-top:6px;color:#7A1F1F;"><?php echo htmlspecialchars($coverageNote); ?></div>
	<?php } ?>
	<?php if(count($gapMonths)){ ?>
	<div style="margin-top:6px;color:#7A1F1F;">This period includes months with no records (<?php echo htmlspecialchars(implode(', ', $gapMonths)); ?>) &mdash; the totals above are not zero failures for those months, they are absent data.</div>
	<?php } ?>
<?php if($unrecordedFail > 0){ ?>
	<div style="margin-top:6px;color:#7A1F1F;">
		<?php echo $unrecordedFail; ?> of these <?php echo $equipt_count; ?> failures have no equipment recorded on the incident
		(<?php echo round($unrecordedFail/$equipt_count*100); ?>%). They are listed as &ldquo;Not recorded&rdquo; rather than dropped, so this
		page&rsquo;s Total matches the Most Fault-Prone Car figure on the summary report. Incidents whose equipment was
		captured through the <code>incident_equipt</code> junction rather than the legacy <code>incident_report.equipt</code>
		column will land here until this page is moved onto the shared resolver.
	</div>
<?php } ?>
	<div style="margin-top:6px;">
		Figures count <b>car-level failures</b> for this car: an incident affecting three cars counts once against each, so
		<?php echo $distinctIncidents; ?> incident<?php echo $distinctIncidents==1?'':'s'; ?>
		produce <?php echo $equipt_count; ?> car-level failure<?php echo $equipt_count==1?'':'s'; ?>.
		This is the basis the equipment summary and per-car reports use, so they reconcile; the incident history logs count one row per incident and show the smaller figure.
	</div>
</div>

</div><!-- /.ccs-panel-body -->
</div><!-- /.ccs-panel -->
</div><!-- /.ccs-page -->

</body>