<!--- Modified by Jun
//--- Date: 8/7/2014
//--- Modify: screen layout
//--- Marker: @mjun
//---------------------------------------------------
//--- Console theme + presentation pass (01302026):
//--- Reconciled this page's look with car_history.php, its drill-down
//--- destination -- same blue/gold console theme instead of grey +
//--- Comic Sans + gold-on-every-cell, link colour now signals
//--- "clickable" at rest instead of only on hover, added a caption
//--- explaining the red highlight and the two click targets. No query
//--- logic touched -- purely presentation.
//---------------------------------------------------
//--- Summary tile pass (08042026):
//--- Marker: @peakmonth
//--- The 4th KPI tile was already LABELLED "Month with the Most
//--- Failures" but still echoed $avgPerActiveCar under the old
//--- "excludes cars with none" caption, so it claimed one figure and
//--- showed another. Now computes the real peak month. Months the
//--- coverage table marks missing are excluded as candidates -- they
//--- read 0 for want of records, not for want of failures -- and when
//--- the year is only partly covered the caption names the base it
//--- was read from. Ties are shown, not silently resolved.
//--- $avgPerActiveCar dropped; nothing else read it. $monthTotals and
//--- $grandTotal were accumulated onto undefined indexes -- both now
//--- initialised up front, same class as the "total" fix above.
//--- KNOWN LIMIT: for the current year this is a part-year peak, with
//--- no marker distinguishing it from a settled one.
//---------------------------------------------------
//--- Slide-panel + clickable tile pass (08042026):
//--- Marker: @slidepanel
//--- The panel would not slide. train_operations.php holds the base
//--- .ta-panel / .ta-overlay geometry in its OWN <style> block, NOT in
//--- slide_panel.php, so requiring slide_panel.php here brought the
//--- behaviour without position:fixed or the off-screen start --
//--- .active had nothing to animate and #irPanel rendered as a plain
//--- block at the foot of the page. Base rules copied in verbatim,
//--- with literal fallbacks for the ta- custom properties that live in
//--- train_operations.php's :root and do not reach this page.
//--- Second, separate cause: the iframe URL sent &car=, but
//--- car_stats.php reads $_GET['car_id'] -- so even once sliding it
//--- would have loaded an empty report. Params are encoded now; the
//--- title carries a colon and spaces.
//--- Also: $selfPage was echoed into self.location but never defined;
//--- clearTimeout() in the opener was commented out, so reopening left
//--- the old timer running and could flash the timeout fallback over a
//--- loaded frame; backdrop restored as its OWN #irOverlay rather than
//--- reaching for taOverlay, which belongs to a panel this page has
//--- not got. Escape and backdrop-click both close.
//--- The "Most Fault-Prone Car" tile is now a whole-tile click target
//--- (a 22px digit is too small a hit area) with the affordance at
//--- rest, keyboard-reachable, and inert when $peakCar is 0.
//--- OPEN: slide_panel.php has not been reviewed. If it also declares
//--- .ta-panel the duplicate rules are identical and harmless, and the
//--- width below is on an id selector so it wins either way -- but if
//--- it also emits an #irPanel there is a duplicate id.
//--------------------------------------------------->
<?php
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");

// Which months the console actually has records for. A missing month and a
// quiet month are both a zero on a chart; this keeps them distinguishable.
//
// Loaded defensively: if data_coverage.php has not been uploaded yet, the
// stubs below report every month as covered and the page renders as it did
// before the helper existed, rather than dying on a failed require.
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
$coverage = ccsLoadCoverage($db);

function getEquipt($id,$dbname){
	$sql="select * from equipment where id='".$id."'";
	$rs=$dbname->query($sql);
	$row=$rs->fetch_assoc();
	return $row['equipment_name'];
}



// @slidepanel -- closeIncidentPanel() echoes this into a self.location; it was
// never defined,
// so the reload target rendered as an empty string.
$selfPage = basename(__FILE__);   /* reload target — rename-safe */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Car Statistics Report</title>

<style type='text/css'>
/* ===========================================================================
   LINE 3 SCHEME — shared with car_history.php / equipment_history.php
   Blue leads the structure; yellow is a small accent (title-bar stripe +
   hover highlight), never the gridlines.
   =========================================================================== */

body { margin:24px 30px; background:#FAFAF6; color:#1A2238; font-family:"Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif; }

h2 { color:#1A2238; font-size:20px; }

.stat-toolbar {
	display:flex; align-items:center; gap:10px; flex-wrap:wrap;
	background:#00529B; border-bottom:3px solid #FDB813;
	border-radius:6px 6px 0 0; padding:10px 16px; margin-bottom:0;
}
.stat-toolbar label { color:#FFFFFF; font-weight:600; font-size:13px; }
.stat-toolbar select, .stat-toolbar input[type=text] {
	height:26px; border:1px solid rgba(255,255,255,.5); border-radius:4px;
	background:#FFFFFF; color:#1A2238; padding:0 8px; font-size:12px;
}
.stat-toolbar input[type=submit] {
	height:28px; border:none; border-radius:4px; background:#FDB813;
	color:#3A2D00; font-weight:700; font-size:12px; padding:0 14px; cursor:pointer;
}
.stat-toolbar input[type=submit]:hover { background:#E5A50F; }

.stat-legend {
	display:flex; align-items:center; gap:16px; flex-wrap:wrap;
	background:#F1EEE3; border:1px solid #E5DECC; border-top:none;
	padding:8px 16px; font-size:12px; color:#5A6275;
}
.stat-legend .swatch { display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:5px; vertical-align:middle; }

.rowHeading {background:#00529B; color:#FFFFFF; font-size:15px; font-weight:600;}
.rowHeading2 {background:#F1EEE3; color:#1A2238;}
.rowClass {background-color: #F5F2E8;}

.train_ava { border-collapse:collapse; }
.train_ava td, .train_ava th { border:1px solid #E5DECC; padding:6px 8px; }

.train_ava a { color:#00529B; text-decoration:none; }

select { border: 1px solid #D8D2C2; color: #1A2238; background-color: #FFFFFF; border-radius:4px; }

/* --- mjun -- generate */
a.two { color:#00529B; font-weight:600; text-decoration:none; }
a.two:visited {color:#00529B;}
a.two:hover, a.two:active {color:#003E76; text-decoration:underline;}



.stat_hover:hover {
	background-color:#FFF1CC;
	text-decoration:underline;
	font-weight:bold;
}

.stat_hover.car {
	cursor:pointer;
	
}

/* --- Slide-panel base ------------------------------------------- @slidepanel
   train_operations.php carries these rules in its OWN <style> block, not in
   slide_panel.php. Requiring slide_panel.php here therefore brought in the
   behaviour without the geometry: #irPanel had no position:fixed and no
   off-screen start, so adding .active had nothing to slide and the panel
   rendered as a plain block at the foot of the page. Values match
   train_operations.php exactly.

   var() fallbacks because the ta- custom properties are defined in
   train_operations.php's :root and may not reach this page. If slide_panel.php
   also declares these rules they are identical, and the width below is on an
   id selector so it wins regardless of source order. */
.ta-overlay       { position:fixed; top:0; right:0; bottom:0; left:0; background:rgba(10,25,50,.45); opacity:0; visibility:hidden; transition:opacity .2s; z-index:99998; }
.ta-overlay.active{ opacity:1; visibility:visible; }
.ta-panel         { position:fixed; top:0; right:-900px; width:480px; max-width:96vw; height:100vh; background:var(--paper,#F7F9FC); box-shadow:-6px 0 24px rgba(0,30,80,.25); transition:right .25s ease; z-index:99999; display:flex; flex-direction:column; font-family:"Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif; }
.ta-panel.active  { right:0; }
.ta-panel-head    { background:var(--rail,#00529B); border-bottom:3px solid var(--gold,#FDB813); padding:12px 16px; display:flex; align-items:center; justify-content:space-between; flex:none; }
.ta-panel-head h3 { margin:0; color:#fff; font-size:13px; font-weight:600; letter-spacing:.3px; }
.ta-panel-close   { background:none; border:none; color:rgba(255,255,255,.7); font-size:19px; line-height:1; cursor:pointer; padding:0 2px; }
.ta-panel-close:hover { color:var(--gold,#FDB813); }
.ta-panel-body    { flex:1; overflow-y:auto; padding:16px 18px; }

/* --- Clickable KPI tile ----------------------------------------- @slidepanel
   Affordance is present at rest, not only on hover — same call already made
   for the table's links on this page. */
.kpi-tile--link { cursor:pointer; transition:background .12s, border-color .12s, box-shadow .12s; }
.kpi-tile--link:hover { background:#F3F7FC !important; border-color:#00529B !important; box-shadow:0 1px 5px rgba(0,40,90,.13); }
.kpi-tile--link:hover .kpi-figure { text-decoration:underline; }
.kpi-tile--link:focus-visible { outline:2px solid #00529B; outline-offset:2px; }
.kpi-hint { font-size:11px; font-weight:600; color:#00529B; margin-top:4px; }
.kpi-tile--link:hover .kpi-hint { text-decoration:underline; }

/* Incident panel: wider variant hosting incident report.php in an iframe.
   #irPanel.ta-panel--ir (id+class) so this can't lose a specificity tie
   against the base .ta-panel width rule regardless of source order. */
#irPanel.ta-panel--ir { width:820px; }
.ta-panel-body--ir { padding:0; overflow:hidden; position:relative; }
#irFrame           { display:block; width:100%; height:100%; border:0; background:#fff; opacity:0; transition:opacity .15s; }
#irFrame.ready     { opacity:1; }
/* @slidepanel -- var() fallbacks added below: --paper/--rail/--mut/--ink are
   declared in train_operations.php's :root and are not defined on this page. */
.ir-loading, .ir-fallback { position:absolute; top:0; right:0; bottom:0; left:0; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px; background:var(--paper,#F7F9FC); text-align:center; padding:0 30px; }
.ir-loading.hidden, .ir-fallback.hidden { display:none; }
.ir-spinner { width:26px; height:26px; border:3px solid #C9D6E5; border-top-color:var(--rail,#00529B); border-radius:50%; animation:ir-spin .7s linear infinite; }
@keyframes ir-spin { to { transform:rotate(360deg); } }
.ir-loading span, .ir-fallback p { font-size:12px; color:var(--mut,#5A6678); }
.ir-fallback strong { color:var(--ink,#16243B); font-size:13px; }
.ir-fallback a { color:var(--rail,#00529B); font-weight:600; text-decoration:none; }
.ir-fallback a:hover { text-decoration:underline; }

</style>
<?php include("history_theme.php"); ?>


</head>
<body>
<div class="ccs-page">
<div class="ccs-header">
<?php
if(isset($_POST['year'])){
	$year=$_POST['year'];

}
else {
	$year=date("Y");

}

if(isset($_POST['equipt_car'])){
$equipment=$_POST['equipt_car'];
}


// @dayview -- The month <select> has a blank first <option>, so submitting
// "no month" posts month="" and isset() is TRUE for it. Every branch in this
// file tested isset(), so the blank choice fell into day mode and built its
// day count from strtotime("2026--01"). Resolve the mode ONCE, here, and let
// every branch below read $isDayView instead of re-testing $_POST.
$monthSel = (isset($_POST['month']) && $_POST['month'] !== '') ? (int)$_POST['month'] : 0;
if($monthSel < 1 || $monthSel > 12){ $monthSel = 0; }
$month      = $monthSel;                 /* the toolbar's "selected" test reads this */
$isDayView  = ($monthSel > 0);
$viewYm     = $isDayView ? sprintf("%04d-%02d", $year, $monthSel) : '';
$bucketCount= $isDayView ? (int)date("t", strtotime($viewYm."-01")) : 12;
$bucketKey  = $isDayView ? "Day_" : "Month_";
$bucketWord = $isDayView ? "day" : "month";

?>

<h1><?php echo "Car Incidents By Year"; ?></h1>
<div class='sub'> <?php echo "For the Year ".$year; ?> <?php if((isset($_POST['equipt_car']))&&($_POST['equipt_car']!="")){  echo " - ".getEquipt($_POST['equipt_car'],$db); } ?> </div>
</div>

<div class="ccs-panel">
<div class="ccs-panel-head">
<form action='car_statistics_report.php' method='post' class="stat-toolbar">
<!--
<label for='levelSelect'>Level</label>
<select name='level' id='levelSelect'>
<option value='2'>2</option>
<option value='3'>3</option>
</select>
-->
<label for='yearSelect'>Year</label>
<?php
$startYear=2013;

$endYear=date("Y")*1+16;

?>
<select name='year' id='yearSelect'>
<?php
for($k=$startYear;$k<=$endYear;$k++){
?>
<option value="<?php echo $k; ?>"<?php if($k==$year) echo " selected"; ?>><?php echo $k; ?></option>
<?php
}
?>
</select>
<label for='monthSelect'>Month</label>
<select name='month' id='monthSelect'>
<option></option>
<?php
for($k=1;$k<=12;$k++){

$monthLabel=date("F",strtotime($year."-".$k."-01"));

?>
<option value="<?php echo $k; ?>"<?php if($k==$month) echo " selected"; ?>><?php echo $monthLabel; ?></option>
<?php
}
?>
</select>



<label for='equipmentSelect'>Equipment</label>
<select name='equipt_car' id='equipmentSelect'>
	<option></option>
<?php
$sql="select * from equipment where id in ('114','102','110','11','113','104','108','109','103','124','67','111','112','105','81','118','119','64','115','89','120','123','121','116','2','122','117','105','81','118','119','64','115','89','120','123','121','116','2','122','117') order by equipment_name";
$rs=$db->query($sql);
$nm=$rs->num_rows;
for($i=0;$i<=$nm;$i++){
	$row=$rs->fetch_assoc();
	?>
	<option value="<?php echo $row['id']; ?>"><?php echo $row['equipment_name']; ?></option>
	
	<?php

}

?>

</select>

<input type=submit value='Submit' />
</form>
<div class="stat-legend">
	<span><span class="swatch" style="background:#00529B;"></span>Click a car number for its full-year history</span>
	<span><span class="swatch" style="background:#FDB813;"></span>Click a monthly count for that car's incidents that month</span>
	<span><span class="swatch" style="background:#F9D6D6; border:1px solid #E3A9A9;"></span>Highlighted row = among the highest incident counts this year (&ge;60% of the peak)</span>
</div>
</div>
<div class='ccs-panel-body'>
<?php
// The summary figures below are only known once the aggregation loop inside
// the table has run, so buffer the table and emit the summary above it.
ob_start();
?>
<table class='table table-striped table-bordered bootstrap-datatable datatable2' border=1 style='border-collapse:collapse;' width=100%>
<thead>



<tr>	

	<th>Car #</th>

<?php
// @dayview
if($isDayView){
	for($m=1;$m<=$bucketCount;$m++){ echo "<th>".$m."</th>"; }
}
else {
	?>

	<th>January</th>
	<th>February</th>
	<th>March</th>
	<th>April</th>
	<th>May</th>
	<th>June</th>
	<th>July</th>
	<th>August</th>
	<th>September</th>
	<th>October</th>
	<th>November</th>
	<th>December</th>
<?php } ?>
	<?php /* @dayview -- Total lived inside the else, so day view emitted N day
	         headers and no Total header while every body row still wrote a
	         Total cell. That column-count mismatch is what makes DataTables
	         alert on load. */ ?>
	<th>Total</th>
</tr>
</thead>
<tbody>
<?php

$CAR_MAX = 73;
for($i=1;$i<=$CAR_MAX;$i++){

	for($k=1;$k<=$bucketCount;$k++){
		$stats["Car_".$i][$bucketKey.$k]=0;
	}
	// "total" was never initialised — the += below was accumulating onto an
	// undefined index on the first hit for every car.
	$stats["Car_".$i]["total"]=0;
}
$highestCount=0;   // was only defined inside if($nm>0), but used unconditionally below

// @peakmonth -- both were accumulated onto undefined indexes further down.
// @dayview -- sized to the view: a 31-day month overran a 12-slot array.
$monthTotals=array_fill(1,$bucketCount,0);
$grandTotal=0;

// @dayview -- was interpolating $month, which at this point was still unset
// (it is only assigned inside the row loop below), and unpadded besides: a
// LIKE of '2026-3%' never matches '2026-03-...'. $viewYm is already padded.

if((isset($_POST['equipt_car']))&&($_POST['equipt_car']!="")){
	$equiptClause=" and equipt='".$_POST['equipt_car']."' ";
}
else {
	$equiptClause="";
	
}

if($isDayView){
$sql="SELECT car_no,day(incident_date) as day,sum(1) as count FROM incident_cars inner join incident_report on incident_cars.incident_id=incident_report.id where incident_date like '".$viewYm."-%' ".$equiptClause." group by incident_cars.car_no*1,day(incident_date)";

}
else {
$sql="SELECT car_no,month(incident_date) as mo,sum(1) as count FROM incident_cars inner join incident_report on incident_cars.incident_id=incident_report.id where incident_date like '".$year."-%%' ".$equiptClause." group by incident_cars.car_no*1,month(incident_date)";
}
// The is_transport_old half is GONE, and deliberately so.
//
// When is_transport was corrupted, is_transport_old's rows were restored INTO
// it. A year-by-year count confirms the duplication: 2013-2018 are identical on
// both sides (4808/4808, 5549/5549, 1880/1880, 2694/2694, 2290/2290; 2016
// differs by a single row), and everything the old database holds for 2019 is a
// subset of what the current one holds.
//
// Reading both therefore counts the same incident twice. The original UNION
// hid that by de-duplicating identical (car_no, month, count) triples — which
// is why the figures looked plausible — but it also dropped genuinely distinct
// rows, and it broke down wherever the two sides disagreed by even one row.
// UNION ALL, briefly used here, made the double counting explicit instead.
//
// With the old data already present in is_transport, the correct answer is to
// read one database. If any pre-2019 incident is ever found that exists ONLY in
// is_transport_old, restore it into is_transport rather than re-adding a query
// half here.

$rs=$db->query($sql);

$nm=$rs->num_rows;

if($nm>0){
	$highestCount=0;
	
	for($i=0;$i<$nm;$i++){
		$row=$rs->fetch_assoc();
		$car_id=$row['car_no']*1;
		
		// Was "=" not "+=": a car appearing in BOTH the current and legacy
		// databases for the same month had one of the two figures overwritten,
		// while the total below accumulated both — so the month cells and the
		// total disagreed.
		if($car_id < 1 || $car_id > $CAR_MAX) continue;   // outside the fleet range


		if($isDayView){
			$day=$row['day']*1;
		/* @dayview -- same shape as the car range guard above. The LIKE clause
		   should keep $day inside the month, but an out-of-range bucket would
		   otherwise create a 32nd column's worth of data that no cell reads. */
		if($day < 1 || $day > $bucketCount) continue;
		$stats["Car_".$car_id][$bucketKey.$day]+=$row['count'];
		
		}
		else {
			/* @dayview -- was assigning to $month, overwriting the SELECTED
			   month on every row fetched. Renamed to a loop-local. */
			$mo=$row['mo']*1;
		$stats["Car_".$car_id]["Month_".$mo]+=$row['count'];


		}

		



		$stats["Car_".$car_id]["total"]+=$row['count'];
		
		$highestCount=sortCar($highestCount,$stats["Car_".$car_id]["total"]);
		
		
		
		
	}
}




for($i=1;$i<=73;$i++){
	$isFlagged=(($highestCount*0.60)<$stats["Car_".$i]["total"]);
?>
<tr 
<?php 
if($isFlagged){
//		echo "style='background-color:#F9D6D6; color:#7A1F1F;'";

}
else {

//	if($i%2>0){ echo "class='rowClass'"; } 

}


?>>
<th class='stat_hover car' onclick="openEditIncidentPanel('<?php echo $year; ?>','<?php echo $i; ?>','Statistics Report','<?php echo $month; ?>','<?php echo $equipment; ?>')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();this.click();}"><?php echo $i; ?></th>
<?php
/**
<th class='stat_hover'><a href='#' style='text-decoration:none; color:#00529B; font-weight:600;'  onclick='window.open("car_history.php?car_id=<?php echo $i; ?>&y=<?php echo $year; ?>",target="_self")' ><?php echo $i; ?></a></th>
*/
?>
<?php

// @dayview -- $t was reused further down as a scratch int inside the
// peak-month loop. Renamed here so the two cannot collide.
for($k=1;$k<=$bucketCount;$k++){

	$monthTotals[$k]+=$stats["Car_".$i][$bucketKey.$k];
	$mon = $isDayView ? $monthSel : $k;   /* the drill-down link wants a MONTH */
	$stat=$stats["Car_".$i][$bucketKey.$k];
?>			



	<td class='stat_hover' role="button" align=center><a href='#' style='text-decoration:none; color:<?php echo $stat>0 ? '#00529B' : '#B4B2A9'; ?>;' onclick='window.open("car_history.php?car_id=<?php echo $i; ?>&y=<?php echo $year; ?>&m=<?php echo $mon; ?>",target="_self")' ><?php echo $stat; ?></a></td>
<?php
}
?>
	<td align=center style="font-weight:600;"><?php echo $stats["Car_".$i]["total"]; ?></td>
</tr>

<?php
}
?>
<tr style="background:#F1EEE3;font-weight:700;">
	<th>All cars</th>
<?php for($k=1;$k<=$bucketCount;$k++){
		$grandTotal+=$monthTotals[$k];
		/* @dayview -- in day view $k is a DAY, so "%04d-%02d" was turning day 7
		   into 2026-07 and asking the coverage table about July. Every column
		   in day view belongs to the one selected month. */
		$ym = $isDayView ? $viewYm : sprintf("%04d-%02d", $year, $k);
		if(ccsMonthStatus($coverage, $ym) === 'missing'){ echo ccsCoverageCell('missing'); continue; }
?>
	<td align=center><?php echo $monthTotals[$k]; ?></td>
<?php } ?>
	<td align=center><?php echo $grandTotal; ?></td>
</tr>
</tbody>
</table>
<?php
$tableHtml = ob_get_clean();

// ---- Derived figures for the summary strip and charts --------------------
// This page counts CAR-LEVEL FAILURES: it joins incident_cars, so an incident
// affecting three cars counts once against each. Same basis as
// equipment_cars_stats.php and the equipment summary report, so the three
// reconcile. The incident history logs count one row per incident and show a
// smaller figure.
$grandTotal2 = $grandTotal;   // already accumulated above
$carTotals = array();
$carsWithFailures=0;
for($i=1;$i<=$CAR_MAX;$i++){
	if($stats["Car_".$i]["total"] > 0){
		$carTotals[] = array($i, (int)$stats["Car_".$i]["total"]);
		$carsWithFailures++;
	
	}
}
usort($carTotals, function($a,$b){ return $b[1]-$a[1]; });

$peak=reset($carTotals);
$peakTotal=$peak[1];

$peakCar=$peak[0];
$monthSeries = array();
$mn = array(1=>'Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
$uncoveredMonths = array();
// @dayview -- labels are day numbers in day view; the coverage question is
// asked once about the selected month rather than per column.
for($k=1;$k<=$bucketCount;$k++){
	$label = $isDayView ? (string)$k : $mn[$k];
	$ym    = $isDayView ? $viewYm    : sprintf("%04d-%02d", $year, $k);
	if(ccsMonthStatus($coverage, $ym) === 'missing'){
		// null, not 0 — Chart.js draws nothing, and a footnote names the gap.
		$monthSeries[] = array($label, null);
		$uncoveredMonths[] = $isDayView ? date("F Y", strtotime($viewYm."-01")) : $mn[$k];
	}
	else { $monthSeries[] = array($label, (int)$monthTotals[$k]); }
}
$uncoveredMonths = array_values(array_unique($uncoveredMonths));
// @dayview -- this was commented out but still read three times below
// ($coverageNote !== '', htmlspecialchars(), json_encode()).
$coverageNote = ccsCoverageNote($coverage);

// ---- Month with the most car-level failures ---------------- @peakmonth ---
// Months the coverage table marks as missing are NOT candidates. They read 0
// here because no records survive for them, not because nothing failed, so
// ranking one against a recorded month compares a gap to a count.
//
// Ties are kept rather than resolved: with a fleet this size two months
// landing on the same figure is common, and silently showing whichever came
// first would make the tile look more decisive than the data is.
$mnFull = array(1=>'January','February','March','April','May','June','July','August','September','October','November','December');
$peakMonthTotal = 0;
$peakMonthKeys  = array();
$coveredMonths  = 0;
for($k=1;$k<=$bucketCount;$k++){
	$ym = $isDayView ? $viewYm : sprintf("%04d-%02d", $year, $k);
	if(ccsMonthStatus($coverage, $ym) === 'missing') continue;
	$coveredMonths++;
	$bt = (int)$monthTotals[$k];   /* @dayview -- was $t, the column count */
	if($bt > $peakMonthTotal){ $peakMonthTotal=$bt; $peakMonthKeys=array($k); }
	elseif($bt > 0 && $bt === $peakMonthTotal){ $peakMonthKeys[]=$k; }
}

if(!count($peakMonthKeys)){
	$peakMonthLabel = '&mdash;';
	$peakMonthSub   = $coveredMonths ? 'no failures recorded' : 'no '.$bucketWord.'s with data';
}
elseif(count($peakMonthKeys) === 1){
	/* @dayview -- $mnFull is a MONTH table; indexing it with a day number gave
	   "March" for day 3 and an undefined index past day 12. */
	$peakMonthLabel = $isDayView ? date("j F", strtotime($viewYm."-".sprintf("%02d",$peakMonthKeys[0])))
	                             : $mnFull[$peakMonthKeys[0]];
	$peakMonthSub   = $peakMonthTotal.' failure'.($peakMonthTotal==1?'':'s');
}
else {
	$abbr=array();
	foreach($peakMonthKeys as $k){ $abbr[] = $isDayView ? (string)$k : $mn[$k]; }
	// Beyond three the names stop fitting the tile, so state the shape instead.
	$peakMonthLabel = count($abbr)>3 ? count($abbr).'-way tie' : implode(' &amp; ', $abbr);
	$peakMonthSub   = $peakMonthTotal.' failures each';
}
// A peak read off a part-year should say what it was read from.
// ($coveredMonths of 0 already says it in the line above.)
if(count($uncoveredMonths) && $coveredMonths > 0){
	$peakMonthSub .= ' &middot; of '.$coveredMonths.' '.$bucketWord.($coveredMonths==1?'':'s').' with data';
}

// Distinct incidents behind those car-level failures, across both databases.
// The source tag keeps ids from the two schemas from colliding.
$distinctIncidents = 0;
// Single database — see the note above the main query.
$dq = $db->query("select count(distinct incident_cars.incident_id) as c
                    from incident_cars
                    inner join incident_report on incident_cars.incident_id=incident_report.id
                   where incident_date like '".$year."-%'");
if($dq && ($dr = $dq->fetch_assoc())) $distinctIncidents = (int)$dr['c'];
?>

<div style="display:flex;flex-wrap:wrap;gap:10px;margin:14px 0;">
	<div style="flex:1;min-width:140px;border:1px solid #E5DECC;border-radius:6px;padding:10px 12px;background:#FBFAF6;">
		<div style="font-size:11px;color:#5A6275;text-transform:uppercase;letter-spacing:.06em;">Car-level failures</div>
		<div style="font-size:22px;font-weight:600;color:#00529B;"><?php echo $grandTotal; ?></div>
		<div style="font-size:11px;color:#5A6275;">from <?php echo $distinctIncidents; ?> incident<?php echo $distinctIncidents==1?'':'s'; ?></div>
	</div>
	<div style="flex:1;min-width:140px;border:1px solid #E5DECC;border-radius:6px;padding:10px 12px;background:#FBFAF6;">
		<div style="font-size:11px;color:#5A6275;text-transform:uppercase;letter-spacing:.06em;">Cars affected</div>
		<div style="font-size:22px;font-weight:600;color:#00529B;"><?php echo $carsWithFailures; ?></div>
		<div style="font-size:11px;color:#5A6275;">of <?php echo $CAR_MAX; ?> in the fleet</div>
	</div>
<?php
	// @slidepanel -- whole tile is the click target now, not just the digit.
	// Only a real car opens the panel. With no failures in the year $peakCar is
	// 0, and a tile showing an em dash must not look or behave like a button.
	$peakClickable = ($peakCar > 0);
?>
	<div class="kpi-tile<?php echo $peakClickable ? ' kpi-tile--link' : ''; ?>" style="flex:1;min-width:140px;border:1px solid #E5DECC;border-radius:6px;padding:10px 12px;background:#FBFAF6;"<?php if($peakClickable){ ?> role="button" tabindex="0" aria-label="Open the equipment failure breakdown for car <?php echo $peakCar; ?>" onclick="openEditIncidentPanel('<?php echo $year; ?>','<?php echo $peakCar; ?>','Statistics Report','<?php echo $month; ?>','<?php echo $equipment; ?>')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();this.click();}"<?php } ?>>
		<div style="font-size:11px;color:#5A6275;text-transform:uppercase;letter-spacing:.06em;">Most Fault-Prone Car</div>
		<div class="kpi-figure" style="font-size:22px;font-weight:600;color:#7A1F1F;"><?php echo $peakCar>0 ? $peakCar : '&mdash;'; ?></div>
		<div style="font-size:11px;color:#5A6275;"><?php echo $peakTotal; ?> failure<?php echo $peakTotal==1?'':'s'; ?></div>
<?php if($peakClickable){ ?>
		<div class="kpi-hint">View equipment breakdown &rarr;</div>
<?php } ?>
	</div>
	<!-- @peakmonth -- was echoing $avgPerActiveCar under this label -->
	<div style="flex:1;min-width:140px;border:1px solid #E5DECC;border-radius:6px;padding:10px 12px;background:#FBFAF6;">
		<div style="font-size:11px;color:#5A6275;text-transform:uppercase;letter-spacing:.06em;"><?php echo $isDayView ? 'Day' : 'Month'; ?> with the Most Failures</div>
		<div style="font-size:22px;font-weight:600;color:#00529B;"><?php echo $peakMonthLabel; ?></div>
		<div style="font-size:11px;color:#5A6275;"><?php echo $peakMonthSub; ?></div>
	</div>
</div>

<div style="margin-bottom:14px;">
	<button type="button" onclick="csrPrintReport()" style="padding:6px 14px;border:1px solid #00529B;background:#00529B;color:#fff;border-radius:4px;cursor:pointer;font-size:13px;">Print report</button>
</div>

<div style="display:flex;flex-wrap:wrap;gap:16px;margin-bottom:16px;">
	<div><canvas id="csrByCar" width="340" height="220"></canvas></div>
	<div><canvas id="csrByMonth" width="340" height="200"></canvas></div>
</div>

<?php echo $tableHtml; ?>

<div style="font-size:12px;color:#5A6275;margin-top:8px;">
	Figures count <b>car-level failures</b>: an incident affecting three cars counts once against each car, so <?php echo $distinctIncidents; ?> incident<?php echo $distinctIncidents==1?'':'s'; ?> produce <?php echo $grandTotal; ?> car-level failure<?php echo $grandTotal==1?'':'s'; ?>. This matches the basis used by the equipment summary and per-car reports; the incident history logs count one row per incident and show the smaller figure.
	Covers all severity levels &mdash; this page has no level filter.
</div>

<script>
var csrCarTotals   = <?php echo json_encode($carTotals); ?>;
var csrMonthSeries = <?php echo json_encode($monthSeries); ?>;
var csrYear        = <?php echo json_encode($year); ?>;
var csrGrandTotal  = <?php echo (int)$grandTotal; ?>;
var csrIncidents   = <?php echo (int)$distinctIncidents; ?>;
var csrCarsAffected= <?php echo (int)$carsWithFailures; ?>;
var csrPeakCar     = <?php echo (int)$peakCar; ?>;
var csrUncovered   = <?php echo json_encode($uncoveredMonths); ?>;
var csrCoverageNote= <?php echo json_encode($coverageNote); ?>;
var csrBucketWord  = <?php echo json_encode($bucketWord); ?>;   /* @dayview */
</script>
<?php



function sortCar($count_a,$count_b){
	
	if($count_a>$count_b){
		
		return $count_a;

	}
	else {
		return $count_b;	

	}
	
}
?>
</div>
</div>
</div>
<?php require("slide_panel.php"); ?>

<script language='javascript'>
var irLoadTimer=null, irExpectingLoad=false, irNeedsReload=false;

function closeIncidentPanel(){
	var p=document.getElementById('irPanel');
	if(!p) return;
	p.classList.remove('active');
	clearTimeout(irLoadTimer);
	irExpectingLoad=false;
	document.getElementById('irFrame').src="about:blank"; /* release the framed page */
	/* @slidepanel -- overlay teardown; the taOverlay lines that were here are
	   commented out in the original because taPanel does not exist on this page. */
	var ov=document.getElementById('irOverlay');
	if(ov) ov.classList.remove('active');
	if(irNeedsReload){ irNeedsReload=false; self.location="<?php echo $selfPage; ?>"; } /* pick up field edits saved inside the panel */
}

function irFrameLoaded(){
	if(!irExpectingLoad) return; /* ignore the about:blank resets from closeIncidentPanel/initial markup */
	irExpectingLoad=false;
	clearTimeout(irLoadTimer);
	document.getElementById('irLoading').classList.add('hidden');
	document.getElementById('irFallback').classList.add('hidden');
	document.getElementById('irFrame').classList.add('ready');
}

function openEditIncidentPanel(year,car,title,month,equipt=null){
	/* @slidepanel
	   car_stats.php reads $_GET['car_id'] — this sent &car=, so the iframe
	   loaded with no car at all and rendered an empty report. That is why the
	   panel looked broken even once it was sliding correctly.
	   Values are encoded: the title carries a colon and spaces. */
	month = (month===undefined || month===null) ? '' : month;
	equipt = (equipt===undefined || equipt===null) ? '' : equipt;

	title = title || "Car with Most Failures";
	var q = "year="       + encodeURIComponent(year)
	      + "&car_id="    + encodeURIComponent(car)
	      + "&month="     + encodeURIComponent(month)
	      + "&title="     + encodeURIComponent(title)
	      + "&equipt="     + encodeURIComponent(equipt);

	document.getElementById('ir-panel-title').textContent=title;
	document.getElementById('irFallbackLink').href="car_stats.php?"+q; /* no embed=1: full standalone page */
	var frame=document.getElementById('irFrame');
	frame.classList.remove('ready');
	document.getElementById('irLoading').classList.remove('hidden');
	document.getElementById('irFallback').classList.add('hidden');
	clearTimeout(irLoadTimer);   /* was commented out: reopening left the previous
	                                timer running, which could flash the timeout
	                                fallback over an already-loaded frame */
	irExpectingLoad=true;
	frame.src="car_stats.php?"+q+"&embed=1";
	document.getElementById('irPanel').classList.add('active');
	document.getElementById('irOverlay').classList.add('active');
	irLoadTimer=setTimeout(function(){
		if(irExpectingLoad) document.getElementById('irFallback').classList.remove('hidden');
	},6000);
}

/* @slidepanel -- Escape and a backdrop click both close, as in train_operations. */
document.addEventListener('keydown',function(e){
	if(e.key==='Escape') closeIncidentPanel();
});
</script>
<!-- @slidepanel -- own backdrop; taOverlay belongs to train_operations.php -->
<div class="ta-overlay" id="irOverlay" onclick="closeIncidentPanel()"></div>
<div class="ta-panel ta-panel--ir" id="irPanel" role="dialog" aria-modal="true" aria-labelledby="ir-panel-title">
	<div class="ta-panel-head">
		<h3 id="ir-panel-title">Car Most Prone to Failure</h3>
		<button type="button" class="ta-panel-close" onclick="closeIncidentPanel()" aria-label="Close">&times;</button>
	</div>
	<div class="ta-panel-body ta-panel-body--ir">
		<iframe id="irFrame" src="about:blank" title="Incident Report" onload="irFrameLoaded()"></iframe>
		<div class="ir-loading" id="irLoading">
			<div class="ir-spinner"></div>
			<span>Loading failure table&hellip;</span>
		</div>
		<div class="ir-fallback hidden" id="irFallback">
			<strong>This is taking longer than expected.</strong>
			<p>The form may be blocked from loading inside this panel.<br>You can open it directly instead:</p>
			<a href="#" id="irFallbackLink" target="_blank" rel="noopener">Open Incident Report in a new tab &rarr;</a>
		</div>
	</div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>

<script>
(function(){
	var ink='#1A2238', muted='#5A6275', grid='rgba(137,135,129,0.20)';
	var TOP_CARS = 10;

	function valueLabels(){
		return { id:'csrLabels', afterDatasetsDraw:function(chart){
			var ctx=chart.ctx, meta=chart.getDatasetMeta(0);
			ctx.save(); ctx.font='11px Arial, sans-serif'; ctx.fillStyle=ink;
			ctx.textBaseline='middle'; ctx.textAlign='left';
			meta.data.forEach(function(bar,i){ ctx.fillText(chart.data.datasets[0].data[i], bar.x+6, bar.y); });
			ctx.restore();
		}};
	}

	var top = csrCarTotals.slice(0, TOP_CARS);
	var tail = csrCarTotals.slice(TOP_CARS);
	var tailTotal = tail.reduce(function(s,r){ return s+r[1]; }, 0);

	if(top.length){
		new Chart(document.getElementById('csrByCar'), {
			type:'bar',
			data:{ labels: top.map(function(r){ return 'Car '+r[0]; }),
			       datasets:[{ data: top.map(function(r){ return r[1]; }),
			                   backgroundColor: top.map(function(r){ return r[0]===csrPeakCar ? '#A32D2D' : '#00529B'; }),
			                   borderRadius:3, categoryPercentage:0.62, barPercentage:0.9 }] },
			options:{ indexAxis:'y', responsive:false, animation:false,
				layout:{ padding:{ right:22, bottom: tail.length ? 18 : 4 } },
				plugins:{
					title:{ display:true, text:'Car-level failures by car'+(tail.length?' (top '+TOP_CARS+')':'')+' \u2014 '+csrYear, color:ink, font:{size:11,weight:'normal'}, padding:{bottom:8} },
					legend:{ display:false },
					tooltip:{ callbacks:{ label:function(c){ return c.parsed.x+' failures'; } } }
				},
				scales:{ x:{ ticks:{ color:muted, precision:0, font:{size:10} }, grid:{ color:grid } },
				         y:{ ticks:{ color:ink, font:{size:11} }, grid:{ display:false } } }
			},
			plugins:[ valueLabels(), { id:'csrTail', afterDraw:function(chart){
				if(!tail.length) return;
				var ctx=chart.ctx, area=chart.chartArea;
				ctx.save(); ctx.font='10px Arial, sans-serif'; ctx.fillStyle=muted;
				ctx.textAlign='left'; ctx.textBaseline='top';
				var y=chart.height-14;
				ctx.strokeStyle=grid; ctx.lineWidth=1;
				ctx.beginPath(); ctx.moveTo(area.left,y-5); ctx.lineTo(chart.width-8,y-5); ctx.stroke();
				ctx.fillText('+ '+tailTotal+' across '+tail.length+' further car'+(tail.length===1?'':'s'), area.left, y);
				ctx.restore();
			}}]
		});
	}
	else{
		var cv=document.getElementById('csrByCar'), c=cv.getContext('2d');
		c.textBaseline='middle'; c.textAlign='left';
		c.font='11px Arial, sans-serif'; c.fillStyle=ink;
		c.fillText('Car-level failures by car \u2014 '+csrYear, 0, 9);
		c.font='10px Arial, sans-serif'; c.fillStyle=muted;
		c.fillText('No failures recorded for this year.', 0, 34);
	}

	// Uncovered months carry null, so Chart.js draws no bar at all. A null and
	// a zero look identical on a bar chart, so the gap is also named in a
	// footnote painted into the canvas — which survives the print handoff.
	var monthGapNote = {
		id:'csrMonthGap',
		afterDraw:function(chart){
			if(!csrUncovered.length) return;
			var ctx=chart.ctx, area=chart.chartArea;
			ctx.save(); ctx.font='10px Arial, sans-serif'; ctx.fillStyle='#7A1F1F';
			ctx.textAlign='left'; ctx.textBaseline='top';
			var y=chart.height-13;
			ctx.strokeStyle=grid; ctx.lineWidth=1;
			ctx.beginPath(); ctx.moveTo(area.left,y-5); ctx.lineTo(chart.width-8,y-5); ctx.stroke();
			ctx.fillText('No data: '+csrUncovered.join(', ')+' — not zero failures', area.left, y);
			ctx.restore();
		}
	};

	new Chart(document.getElementById('csrByMonth'), {
		type:'bar',
		data:{ labels: csrMonthSeries.map(function(r){ return r[0]; }),
		       datasets:[{ data: csrMonthSeries.map(function(r){ return r[1]; }), backgroundColor:'#00529B', borderRadius:3 }] },
		options:{ responsive:false, animation:false,
			layout:{ padding:{ bottom: csrUncovered.length ? 16 : 2 } },
			plugins:{ title:{ display:true, text:'Car-level failures by '+csrBucketWord+', whole fleet', color:ink, font:{size:11,weight:'normal'}, padding:{bottom:6} }, legend:{ display:false } },
			scales:{ x:{ ticks:{ color:muted, font:{size:10} }, grid:{ display:false } },
			         y:{ ticks:{ color:muted, precision:0, font:{size:10} }, grid:{ color:grid } } }
		},
		plugins:[monthGapNote]
	});

	window.csrPrintReport = function(){
		var imgCar   = document.getElementById('csrByCar').toDataURL('image/png');
		var imgMonth = document.getElementById('csrByMonth').toDataURL('image/png');
		var tbl = document.querySelector('.ccs-panel-body table');
		var tableHtml = tbl ? tbl.outerHTML : '';
		function esc(x){ return String(x).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

		var win = window.open('', '_blank');
		win.document.write(
			'<html><head><title>Car Incidents by Year \u2014 '+esc(csrYear)+'</title>' +
			'<style>' +
				'@page{ size:A4 landscape; margin:12mm 10mm 13mm; }' +
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
				'.charts{ margin-bottom:4px; }' +
				'.chart{ display:inline-block; vertical-align:top; width:40%; margin:0 2% 10px 0; page-break-inside:avoid; }' +
				'.chart img{ display:block; width:100%; height:auto; border:1px solid #e5e7eb; }' +
				'.chart .cap{ font-size:9px; color:#6b7280; margin-top:3px; }' +
				'.note{ font-size:9px; color:#6b7280; font-style:italic; margin:2px 0 0; }' +
				'table{ width:100%; border-collapse:collapse; font-size:8.5px; }' +
				'thead{ display:table-header-group; }' +
				// Navy fill applies to the HEADER ROW only. Each data row's first
				// cell is also a <th>, so an unscoped th rule painted the whole
				// Car # column solid navy.
				'thead th{ background:#1f4e79; color:#fff; text-align:center; padding:4px 3px; font-size:8px; font-weight:600;' +
					' text-transform:uppercase; letter-spacing:.03em; border:1px solid #1f4e79; }' +
				'tbody th{ background:#F1EFE8; color:#1a1a1a; text-align:center; padding:3px; font-size:8.5px;' +
					' font-weight:600; border:1px solid #e5e7eb; }' +
				'td{ padding:3px; border:1px solid #e5e7eb; text-align:center; }' +
				'tr{ page-break-inside:avoid; }' +
				// !important because the car-number and month links carry inline
				// colours, which would otherwise win over this rule.
				'a{ color:inherit !important; text-decoration:none !important; pointer-events:none; }' +
				'.rpt-foot{ margin-top:12px; border-top:1px solid #d1d5db; padding-top:6px; font-size:8.5px; color:#6b7280; }' +
			'</style></head><body>' +
			'<div class="rpt-head">' +
				'<div class="rpt-org">DOTr &middot; MRT-3 Line 3 &middot; Operations Control</div>' +
				'<h1 class="rpt-title">Car Incidents by Year</h1>' +
				'<p class="rpt-subject">Fleet-wide, '+esc(csrYear)+'</p>' +
			'</div>' +
			'<div class="rpt-meta">' +
				'<span><b>Year:</b> '+esc(csrYear)+'</span>' +
				'<span><b>Car-level failures:</b> '+csrGrandTotal+'</span>' +
				'<span><b>From incidents:</b> '+csrIncidents+'</span>' +
				'<span><b>Cars affected:</b> '+csrCarsAffected+'</span>' +
				'<span><b>Generated:</b> <?php echo date("d M Y, H:i"); ?></span>' +
			'</div>' +
			'<h2 class="sec">Summary</h2>' +
			'<div class="charts">' +
				'<div class="chart"><img src="'+imgCar+'"><div class="cap">Figure 1 &mdash; Car-level failures by car</div></div>' +
				'<div class="chart"><img src="'+imgMonth+'"><div class="cap">Figure 2 &mdash; Car-level failures by '+csrBucketWord+', whole fleet</div></div>' +
				'<p class="note">Figures count car-level failures: an incident affecting several cars counts once against each car, so '+csrIncidents+' incidents produce '+csrGrandTotal+' car-level failures. This matches the equipment summary and per-car reports; the incident history logs count one row per incident and show the smaller figure. Covers all severity levels. Shaded rows are cars at or above 60% of the worst car total.</p>' +
			'</div>' +
			'<h2 class="sec">Monthly Breakdown by Car</h2>' +
			tableHtml +
			'<div class="rpt-foot">MRT-3 Information Sharing System &middot; generated <?php echo date("d M Y, H:i"); ?> &middot; for internal operational use</div>' +
			'</body></html>'
		);
		win.document.close();
		win.focus();
		win.onload = function(){ setTimeout(function(){ win.print(); }, 250); };
	};
})();
</script>

</body>
</html>