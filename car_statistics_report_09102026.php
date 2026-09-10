<?php
/* Session before ANY output -- the console header calls session_start(). */
if(session_id()==""){ session_start(); }
/* Whether the console nav renders (see the require further down, in <body>). */
$NAV_SHOW = !(isset($_GET['embed']) && $_GET['embed']!='');
?>
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
// @insight -- Analysis layer, guarded like data_coverage.php above: a missing
// file must leave the page rendering exactly as it did before.
if(file_exists(dirname(__FILE__)."/iss_insight.php")){
	require_once(dirname(__FILE__)."/iss_insight.php");
	if(file_exists(dirname(__FILE__)."/iss_insight_analytics.php")){
		require_once(dirname(__FILE__)."/iss_insight_analytics.php");
	}
	/* @insight -- Executive/technical toggle. Optional like the rest: without
	   this file the page renders the technical block alone, exactly as before. */
	if(file_exists(dirname(__FILE__)."/iss_insight_audience.php")){
		require_once(dirname(__FILE__)."/iss_insight_audience.php");
	}
	/* @insight -- The panel on paper. Guarded on its own file like every
	   other helper here: a station that has not received iss_insight_print.php
	   prints exactly what it printed before. */
	if(file_exists(dirname(__FILE__)."/iss_insight_print.php")){
		require_once(dirname(__FILE__)."/iss_insight_print.php");
	}
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
/* @toolbaralign -- The Submit sat lower than the fields for two reasons at
   once:

     1. It was 28px while the selects and text inputs were 26px.
     2. Everything in this bar is inline-level inside table cells, so the
        default vertical-align is BASELINE. Inline-block boxes of unequal
        height align on the baseline of their last line box, which for a
        taller control sits further down the box -- so the mismatch showed as
        a vertical offset rather than just a size difference.

   Fixed on both counts: one height for every control, and vertical-align
   middle so the row cannot drift again if a height is ever changed.
   box-sizing is set explicitly because these rules give the fields a border
   and the button none; without it the same height value produces two
   different boxes. */
.stat-toolbar select,
.stat-toolbar input[type=text],
.stat-toolbar input[type=submit] {
	height:28px; box-sizing:border-box; vertical-align:middle; margin:0;
	border-radius:4px; font-size:12px; font-family:inherit;
}
.stat-toolbar select, .stat-toolbar input[type=text] {
	border:1px solid rgba(255,255,255,.5);
	background:#FFFFFF; color:#1A2238; padding:0 8px;
}
.stat-toolbar input[type=submit] {
	border:none; background:#FDB813;
	color:#3A2D00; font-weight:700; padding:0 14px; cursor:pointer;
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



/* @sort -- The arrow sits at low contrast at rest so a header reads as
   sortable before it is hovered; a control that appears only on hover cannot
   be found by someone looking for it. */
#csrMatrix thead th { cursor:pointer; user-select:none; position:relative; padding-right:15px; }
/* @sorthover -- was background:#003E76, a DARKER navy, written on the
   assumption that this header is navy and hover would deepen it. It is not:
   the header here renders light with dark text, so hovering inverted a single
   cell to solid navy -- a black hole in the middle of the row, and the label
   went unreadable wherever the template did not also flip the text colour.
   A light tint plus a full-strength arrow instead: enough to say "this is the
   column under the cursor" without repainting it. */
#csrMatrix thead th:hover { background:#E8F0F9; }
#csrMatrix thead th:hover::after { opacity:.85; }
/* The sorted column stays marked once the cursor leaves, which the hover
   tint alone cannot do. */
#csrMatrix thead th[aria-sort] { background:#DCE9F6; }
#csrMatrix thead th::after { content:"\2195"; position:absolute; right:4px; opacity:.35; font-size:10px; font-weight:400; }
#csrMatrix thead th[aria-sort="ascending"]::after  { content:"\25B2"; opacity:1; }
#csrMatrix thead th[aria-sort="descending"]::after { content:"\25BC"; opacity:1; }

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
<?php
/* Console nav. Tmenu_2.php emits ONLY the header markup and the <ul id="navMenu">
   -- never <!DOCTYPE>/<html>/<head> -- so it belongs here, inside <body>, and this
   page keeps whatever document scaffolding it already had.

   Suppressed under embed=1: this page is reached from the menu today, but every
   report here also loads OTHER pages into slide_panel.php, and edit_ccdr.php
   already carries an embed flag. Without the guard, the logo, header and nav bar
   would render inside an 820px iframe. */
if($NAV_SHOW){ require("Tmenu_2.php"); }
?>
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
</div>
<div class='ccs-panel-body'>
<?php
// The summary figures below are only known once the aggregation loop inside
// the table has run, so buffer the table and emit the summary above it.
ob_start();
?>
<table id="csrMatrix" class='table table-striped table-bordered bootstrap-datatable datatable2 ccs-rows-with' border=1 style='border-collapse:collapse;' width=100%>
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

/* @nonrevenue -- $CAR_MAX is the REVENUE fleet, 1..73. It is no longer a
   filter on what gets counted.
   Numbers above it are placeholders standing in for non-revenue trains that do
   not run the usual cars. Those are real failures on real vehicles, and the old
   guard dropped them before they reached $stats -- so this table, its month
   totals and its grand total all excluded them while month_stats.php, which has
   no such guard, counted them. That is the whole of the 75-against-77 gap on
   March 2026. Neither page said anything, so the drill-down simply disagreed
   with the cell that opened it.
   The roster is now the revenue fleet PLUS whichever placeholders appear in the
   window. Revenue cars are listed even at zero, because a car with no failures
   is a fact worth showing; placeholders are listed only when they have data,
   because that set is open-ended. The initialisation moved below the query,
   since the roster is now derived from the rows. */
$CAR_MAX = 73;
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

$nm=$rs?$rs->num_rows:0;

/* @nonrevenue -- Buffered, because the roster is derived from the rows and the
   rows must be walked twice: once to learn which placeholder numbers appear,
   once to add them up. A mysqli result only walks forwards. */
$rawRows    = array();
$extraCars  = array();   /* placeholder numbers present in this window */
$unnumbered = 0;         /* car_no of 0 or blank -- NOT a placeholder */
for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
	$car_id=$row['car_no']*1;
	/* A blank is a THIRD valid category, not bad data and not a placeholder.
	   These are real trains that carry no car numbers; why is not recorded and
	   is not this page's business to guess. They are counted like any other
	   failure and gathered into one row, because they cannot be split by a car
	   number that was never part of the record. */
	if($car_id < 1){ $unnumbered += (int)$row['count']; $row['car_no']=0; $car_id=0; }
	else if($car_id > $CAR_MAX && !in_array($car_id,$extraCars,true)){ $extraCars[]=$car_id; }
	$rawRows[]=$row;
}
sort($extraCars);
$carIds = array_merge(range(1,$CAR_MAX), $extraCars);
/* Keyed 0 and rendered last. Dropping these is what left this page's grand
   total short of the month panel's: the panel has no car column to drop them
   from, so the only way the two can agree is for the table to carry them. */
if($unnumbered){ $carIds[] = 0; }

foreach($carIds as $i){
	for($k=1;$k<=$bucketCount;$k++){
		$stats["Car_".$i][$bucketKey.$k]=0;
	}
	// "total" was never initialised — the += below was accumulating onto an
	// undefined index on the first hit for every car.
	$stats["Car_".$i]["total"]=0;
}

foreach($rawRows as $row){
	$car_id=$row['car_no']*1;

	// Was "=" not "+=": a car appearing in BOTH the current and legacy
	// databases for the same month had one of the two figures overwritten,
	// while the total below accumulated both — so the month cells and the
	// total disagreed.
	if($isDayView){
		$day=$row['day']*1;
		/* @dayview -- The LIKE clause should keep $day inside the month, but an
		   out-of-range bucket would create a 32nd column's worth of data that no
		   cell reads. Unlike the car guard this one is a real impossibility,
		   not a category of vehicle. */
		if($day < 1 || $day > $bucketCount) continue;
		$stats["Car_".$car_id][$bucketKey.$day]+=$row['count'];
	}
	else {
		/* @dayview -- was assigning to $month, overwriting the SELECTED month on
		   every row fetched. Renamed to a loop-local. */
		$mo=$row['mo']*1;
		$stats["Car_".$car_id]["Month_".$mo]+=$row['count'];
	}

	$stats["Car_".$car_id]["total"]+=$row['count'];

	/* @nonrevenue -- The 60% flag and the Most Fault-Prone Car tile stay scoped
	   to the REVENUE fleet. A placeholder stands in for a whole train, so its
	   count is not comparable with one car's, and letting it top that tile
	   would answer a different question than the tile asks. Totals are another
	   matter: those include everything, which is what makes this page reconcile
	   with the month panel. */
	if($car_id <= $CAR_MAX){
		$highestCount=sortCar($highestCount,$stats["Car_".$car_id]["total"]);
	}
}




/* @nonrevenue -- was a hardcoded 1..73, which also meant it ignored $CAR_MAX
   sitting a few lines above it. */
foreach($carIds as $i){
	$isNonRev  = ($i > $CAR_MAX);
	$isNoCar   = ($i === 0);
	$isFlagged = (!$isNonRev && !$isNoCar && ($highestCount*0.60) < $stats["Car_".$i]["total"]);
?>
<tr class="<?php echo ($stats["Car_".$i]["total"] == 0) ? 'ccs-zero' : 'ccs-nonzero'; echo $isNonRev ? ' ccs-nonrev' : ''; ?>"
<?php 
if($isFlagged){
//		echo "style='background-color:#F9D6D6; color:#7A1F1F;'";

}
else {

//	if($i%2>0){ echo "class='rowClass'"; } 

}


?>>
<?php if($isNoCar){ /* a real row; it just has no car number to drill on */ ?>
<th class='car ccs-nocar' title="Trains that carry no car numbers. Counted in the totals; the drill-down filters by car number, so there is nothing to open.">no car&nbsp;#</th>
<?php } else { ?>
<th class='stat_hover car' onclick="openEditIncidentPanel('<?php echo $year; ?>','<?php echo $i; ?>','Statistics Report','<?php echo $month; ?>','<?php echo $equipment; ?>')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();this.click();}" title="<?php echo $isNonRev ? 'Non-revenue placeholder number' : 'Revenue car'; ?>"><?php echo $i; ?><?php if($isNonRev){ ?> <span class="ccs-nr">NR</span><?php } ?></th>
<?php } ?>
<?php
/**
<th class='stat_hover'><a href='car_history.php?car_id=<?php echo $i; ?>&y=<?php echo $year; ?>' style='text-decoration:none; color:#00529B; font-weight:600;'><?php echo $i; ?></a></th>
*/
?>
<?php

// @dayview -- $t was reused further down as a scratch int inside the
// peak-month loop. Renamed here so the two cannot collide.
for($k=1;$k<=$bucketCount;$k++){

	$monthTotals[$k]+=$stats["Car_".$i][$bucketKey.$k];
	$mon = $isDayView ? $monthSel : $k;   /* the drill-down link wants a MONTH */
	/* @dayfix -- ...and in day view it also wants the DAY. $mon was already
	   being corrected to the selected month, so the link looked right; the
	   column's own identity, $k, was the part never sent. Clicking day 7 of
	   March therefore opened all of March. */
	$dayQS = $isDayView ? "&d=".(int)$k : "";
	$stat=$stats["Car_".$i][$bucketKey.$k];
?>			



	<td class='stat_hover' role="button" align=center><a href='car_history.php?car_id=<?php echo $i; ?>&y=<?php echo $year; ?>&m=<?php echo $mon; ?><?php echo $dayQS; ?>' style='text-decoration:none; color:<?php echo $stat>0 ? '#00529B' : '#B4B2A9'; ?>;'><?php echo $stat; ?></a></td>
<?php
}
?>
	<td align=center style="font-weight:600;"><?php echo $stats["Car_".$i]["total"]; ?></td>
</tr>

<?php
}
?>
</tbody>
<?php /* @sort -- "All cars" moved out of tbody into tfoot. It was the last row
         INSIDE the body, so any sort would have carried it along and dropped a
         totals row into the middle of the fleet. tfoot is also what a browser
         repeats across printed pages. */ ?>
<tfoot>
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
</tfoot>
</table>
<?php
/* @nonrevenue -- Say what the table contains, rather than leaving the reader to
   discover it by drilling down and getting a different number. Placeholders are
   named so an out-of-convention entry that is actually a typo shows up as one;
   unnumbered rows are the only thing still excluded, and now they are excluded
   out loud. */
if(count($extraCars) || $unnumbered){
	echo '<p class="ccs-fleetnote">';
	if(count($extraCars)){
		echo 'Includes <b>'.count($extraCars).'</b> non-revenue placeholder number'
		   . (count($extraCars)==1?'':'s').' (marked <span class="ccs-nr">NR</span>): '
		   . htmlspecialchars(implode(', ', $extraCars))
		   . '. These are counted in the month totals and in the grand total, but are '
		   . 'left out of the 60% flag and the Most Fault-Prone Car figure, which compare '
		   . 'single revenue cars.';
	}
	if($unnumbered){
		if(count($extraCars)) echo ' ';
		echo 'The <b>no car #</b> row holds <b>'.$unnumbered.'</b> failure'.($unnumbered==1?'':'s')
		   . ' on trains that carry no car numbers. These count in the month totals and the '
		   . 'grand total like any other, but sit outside the 60% flag and the Most '
		   . 'Fault-Prone Car figure, which compare single revenue cars, and cannot be '
		   . 'opened because the drill-down filters by car number.';
	}
	echo '</p>';
}
$tableHtml = ob_get_clean();

/* ==========================================================================
   @insight -- Supplementary pulls for the analysis layer. Each reuses
   $equiptClause and the page's own year/month window so the panel describes
   exactly the slice the table describes.

   This page has no level filter (see the note under the table), so none of
   these carry one either -- adding one here would make the panel narrower
   than the figures above it.
   ========================================================================== */
$issCross = array(); $issHistory = array(); $issEvents = array();

if(function_exists('iss_insight')){

	$issWindow = $isDayView
	           ? " and incident_date like '".$viewYm."-%' "
	           : " and incident_date like '".$year."-%%' ";

	/* -- (a) car x equipment. The page groups by car and bucket only, so this
	      axis exists nowhere on screen -- and it is the one that separates a
	      car with a single recurring fault from a car that is failing on
	      everything. Those want different responses and look identical in a
	      by-car table. */
	$sql = "select incident_cars.car_no*1 as cn, incident_report.equipt as eq,
	               count(1) as c
	          from incident_cars
	          inner join incident_report on incident_cars.incident_id=incident_report.id
	         where 1=1 ".$issWindow." ".$equiptClause."
	         group by cn, eq";
	$rs = $db->query($sql);
	$cell = array(); $eqSeen = array(); $crossGrand = 0;
	if($rs){
		while($r = $rs->fetch_assoc()){
			$cn = (int)$r['cn'];
			/* @nonrevenue -- was "same fleet range the table uses", which is no
			   longer what the table uses. Placeholders belong in the analysis
			   for the same reason they belong in the totals; excluding them
			   here would put the panel back out of step with the table it sits
			   under, in the other direction.

			   Trains with no car numbers stay out, and NOT because the record
			   is wanting -- they are valid trains. This particular question is
			   "which unit carries this fault", and it cannot be asked of a row
			   with no unit. Folding them into one synthetic unit would be worse
			   than leaving them out: several different trains would be read as
			   one, and the concentration findings are exactly what that would
			   mislead. The count is stated under the table instead. */
			if($cn < 1) continue;
			$eq = $r['eq'];
			if($eq === null || $eq === '') $eq = '__blank';
			$cell[$cn][$eq] = (isset($cell[$cn][$eq]) ? $cell[$cn][$eq] : 0) + (int)$r['c'];
			$eqSeen[$eq] = true; $crossGrand += (int)$r['c'];
		}
	}
	/* Rows with no equipment recorded are dropped from the cross-tab -- an
	   "Unspecified" column would dominate the residuals and say nothing about
	   any component. They stay in $grandTotal, so expect_grand is the
	   cross-tab's own total, not the page's. */
	unset($eqSeen['__blank']);
	if(count($eqSeen) >= 2 && count($cell) >= 2){
		$eqIds = array_keys($eqSeen);
		$eqNames = array();
		$nmq = $db->query("select id, equipment_name from equipment");
		if($nmq){ while($x = $nmq->fetch_assoc()){ $eqNames[$x['id']] = $x['equipment_name']; } }
		$cols = array(); $keep = array();
		foreach($eqIds as $eid){
			$cols[] = isset($eqNames[$eid]) ? $eqNames[$eid] : ('Equipment '.$eid);
			$keep[] = $eid;
		}
		$rowsL = array(); $matrix = array(); $ctTotal = 0;
		$carNos = array_keys($cell); sort($carNos, SORT_NUMERIC);
		foreach($carNos as $cn){
			$rv = array(); $any = 0;
			foreach($keep as $eid){
				$v = isset($cell[$cn][$eid]) ? $cell[$cn][$eid] : 0;
				$rv[] = $v; $any += $v;
			}
			if($any === 0) continue;            /* a roster of 73 mostly-empty rows
			                                       flattens every residual */
			$rowsL[] = 'Car '.$cn; $matrix[] = $rv; $ctTotal += $any;
		}
		if(count($rowsL) >= 2){
			$issCross = array('row_label'=>'Car', 'col_label'=>'Equipment',
			                  'rows'=>$rowsL, 'cols'=>$cols, 'matrix'=>$matrix,
			                  'expect_grand'=>$ctTotal);
		}
	}

	/* -- (b) seasonal baseline. Year restriction dropped on purpose: a single
	      March can only be judged against other Marches. */
	if(!$isDayView){
		$sql = "select date_format(incident_date,'%Y-%m') as ym, count(1) as c
		          from incident_cars
		          inner join incident_report on incident_cars.incident_id=incident_report.id
		         where 1=1 ".$equiptClause."
		         group by ym order by ym";
		$rs = $db->query($sql);
		$hb = array(); $hv = array();
		if($rs){
			while($r = $rs->fetch_assoc()){
				if(ccsMonthStatus($coverage, $r['ym']) === 'missing') continue;
				$hb[] = $r['ym']; $hv[] = (int)$r['c'];
			}
		}
		if(count($hb) >= 24) $issHistory = array('buckets'=>$hb, 'values'=>$hv);
	}

	/* -- (c) incident-level rows: same car, same equipment, again, soon. */
	$sql = "select incident_date as d, incident_cars.car_no*1 as cn,
	               incident_report.equipt as eq
	          from incident_cars
	          inner join incident_report on incident_cars.incident_id=incident_report.id
	         where 1=1 ".$issWindow." ".$equiptClause."
	           and incident_report.equipt is not null and incident_report.equipt <> ''
	         order by incident_date limit 6000";
	$rs = $db->query($sql);
	if($rs){
		if(!isset($eqNames)){
			$eqNames = array();
			$nmq = $db->query("select id, equipment_name from equipment");
			if($nmq){ while($x = $nmq->fetch_assoc()){ $eqNames[$x['id']] = $x['equipment_name']; } }
		}
		while($r = $rs->fetch_assoc()){
			$cn = (int)$r['cn'];
			if($cn < 1) continue;   /* @nonrevenue -- see the cross-tab note above */
			$issEvents[] = array(
				'date'=>substr($r['d'],0,10),
				'unit_key'=>'car'.$cn, 'unit_label'=>'Car '.$cn,
				'fault_key'=>$r['eq'],
				'fault_label'=>isset($eqNames[$r['eq']]) ? $eqNames[$r['eq']] : ('Equipment '.$r['eq']));
		}
	}
}

// ---- Derived figures for the summary strip and charts --------------------
// This page counts CAR-LEVEL FAILURES: it joins incident_cars, so an incident
// affecting three cars counts once against each. Same basis as
// equipment_cars_stats.php and the equipment summary report, so the three
// reconcile. The incident history logs count one row per incident and show a
// smaller figure.
$grandTotal2 = $grandTotal;   // already accumulated above
$carTotals = array();
$carsWithFailures=0;
/* @nonrevenue -- This one KEEPS the revenue range on purpose. It answers "how
   many of the fleet had no failures", and a placeholder only ever appears in
   the data when it HAS one -- counting them would quietly change the
   denominator from the fleet to the fleet-plus-whatever-turned-up. */
$zeroRows = 0; $totalRows = $CAR_MAX;   /* @zerorows */
for($i=1;$i<=$CAR_MAX;$i++){
	if($stats["Car_".$i]["total"] > 0){
		$carTotals[] = array($i, (int)$stats["Car_".$i]["total"]);
		$carsWithFailures++;
	
	}
	else { $zeroRows++; }
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
	<?php
	
	$car_tot=$CAR_MAX;
	$car_tot=72;
	?>
	
	
	<div style="flex:1;min-width:140px;border:1px solid #E5DECC;border-radius:6px;padding:10px 12px;background:#FBFAF6;">
		<div style="font-size:11px;color:#5A6275;text-transform:uppercase;letter-spacing:.06em;">Cars affected</div>
		<div style="font-size:22px;font-weight:600;color:#00529B;"><?php echo $carsWithFailures; ?></div>
		<div style="font-size:11px;color:#5A6275;">of <?php echo $car_tot; ?> in the fleet</div>
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
<?php
	/* @monthpanel -- The tile can name SEVERAL periods: the peak calculation
	   keeps ties rather than resolving them, so "March & July" is a real
	   answer. month_stats.php takes a comma list for exactly that reason --
	   sending only the first would quietly drop half of what the tile says. */
	$peakClickableMonth = (count($peakMonthKeys) > 0);
	$peakMonthCsv = implode(',', $peakMonthKeys);
?>
	<div class="<?php echo $peakClickableMonth ? 'kpi-tile--link' : ''; ?>" style="flex:1;min-width:140px;border:1px solid #E5DECC;border-radius:6px;padding:10px 12px;background:#FBFAF6;"<?php if($peakClickableMonth){ ?> role="button" tabindex="0" aria-label="Open the breakdown for <?php echo htmlspecialchars(strip_tags(str_replace('&amp;','and',$peakMonthLabel))); ?>" onclick="openMonthPanel('<?php echo $year; ?>','<?php echo $peakMonthCsv; ?>','<?php echo $isDayView ? (int)$month : 0; ?>',<?php echo htmlspecialchars(json_encode(strip_tags(str_replace('&amp;','and',$peakMonthLabel))), ENT_QUOTES); ?>)" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();this.click();}"<?php } ?>>
		<div style="font-size:11px;color:#5A6275;text-transform:uppercase;letter-spacing:.06em;"><?php echo $isDayView ? 'Day' : 'Month'; ?> with the Most Failures</div>
		<div class="kpi-figure" style="font-size:22px;font-weight:600;color:#00529B;"><?php echo $peakMonthLabel; ?></div>
		<div style="font-size:11px;color:#5A6275;"><?php echo $peakMonthSub; ?></div>
<?php if($peakClickableMonth){ ?>		<div class="kpi-hint">View <?php echo $isDayView ? 'day' : 'month'; ?> breakdown &rarr;</div>
<?php } ?>	</div>
</div>

<?php
/* @insight -- COMPUTED here, PRINTED further down.
   The panel itself still belongs beside the table: an interpretation a
   reader cannot check against the figures is harder to trust. But the
   conclusion has to clear the fold, and the band below needs the
   findings, which are only available once the aggregation above has
   run. So the block runs here into a buffer and prints unchanged in its
   old position, with just the ANALYSIS hoisted into the tile strip.
   Buffering it verbatim rather than refactoring it keeps its raw HTML,
   its <script> and its echoes exactly as they were. */
$issPanelHtml = ''; $issBandHtml = '';
ob_start();
/* @insight -- Panel sits under the table and above the counting note, so the
   interpretation is read while the figures are still on screen. */
if(function_exists('iss_insight')){

	/* Bucket labels the narrative can quote. The table's columns are 1..12 or
	   1..31; "spiked in bucket 4" is not a sentence for a printed report. */
	$issBuckets = array(); $issUncov = array();
	for($k=1; $k<=$bucketCount; $k++){
		if($isDayView){
			$lbl = date('j M Y', strtotime($viewYm.'-'.sprintf('%02d',$k)));
			$ym  = $viewYm;
		} else {
			$lbl = date('F Y', strtotime(sprintf('%04d-%02d-01', $year, $k)));
			$ym  = sprintf('%04d-%02d', $year, $k);
		}
		$issBuckets[] = $lbl;
		if(ccsMonthStatus($coverage, $ym) === 'missing') $issUncov[] = $lbl;
	}

	/* Only cars that actually appear. The roster is 73 fixed slots and most
	   are empty in any one year -- listing all of them as "recorded none at
	   all" is noise, and it drags every concentration figure toward zero. */
	$issRows = array();
	for($i=1; $i<=$CAR_MAX; $i++){
		$t = isset($stats["Car_".$i]["total"]) ? (int)$stats["Car_".$i]["total"] : 0;
		if($t === 0) continue;
		$vals = array();
		for($k=1; $k<=$bucketCount; $k++){
			$vals[] = isset($stats["Car_".$i][$bucketKey.$k]) ? (int)$stats["Car_".$i][$bucketKey.$k] : 0;
		}
		$issRows[] = array('key'=>(string)$i, 'label'=>'Car '.$i,
		                   'values'=>$vals, 'total'=>$t);
	}

	$issFilters = array();
	if(isset($_POST['equipt_car']) && $_POST['equipt_car'] !== ''){
		$issFilters['equipment'] = getEquipt($_POST['equipt_car'], $db);
	}

	$issCtx = array(
		'schema' => 'iss.report.v1',
		'report' => array('id'=>'car_statistics_report',
		                  'title'=>'Rolling Stock - Failures by Car',
		                  'unit'=>'car-level failures'),
		'period' => array(
			'from'  => $isDayView ? $viewYm.'-01' : $year.'-01-01',
			'to'    => $isDayView ? date('Y-m-t', strtotime($viewYm.'-01')) : $year.'-12-31',
			'grain' => $bucketWord),
		'filters' => $issFilters,
		'dimensions' => array('row'=>array('key'=>'car','label'=>'Car'),
		                      'col'=>array('key'=>$bucketWord,
		                                   'label'=>$isDayView ? 'Day' : 'Month')),
		'buckets'  => $issBuckets,
		'rows'     => $issRows,
		'coverage' => array('uncovered_buckets'=>$issUncov),
		/* $monthTotals is 1-indexed on this page (array_fill(1,...)), unlike
		   the equipment report. Reindexed, or every bucket would be offset by
		   one against its label and the peak month would name the wrong month. */
		'totals'   => array('by_bucket'=>array_values($monthTotals),
		                    'grand'=>(int)$grandTotal),
	);
	if(count($issCross))   $issCtx['crosstab'] = $issCross;
	if(count($issHistory)) $issCtx['history']  = $issHistory;
	if(count($issEvents))  $issCtx['events']   = $issEvents;

	/* @insight -- see the note in the history pages: per-finding minimums do
	   the filtering, so a narrow selection thins the analysis rather than
	   deleting it. */
	if(count($issRows) >= 1){
		$issN = iss_insight_normalize($issCtx);
		$issF = iss_insight_findings($issN);
		if(function_exists('iss_insight_findings_advanced')){
			$issF = iss_insight_findings_advanced($issCtx, $issN, $issF);
		}
		echo iss_insight_css();
		/* @insight -- Both readings are computed here and shipped together,
		   so switching is instant and works with no provider configured. The
		   reader's choice persists via localStorage; the print stylesheet
		   prints whichever is on screen. */
		$issRendered = iss_insight_render_offline($issN, $issF);
		if(function_exists('iss_insight_html_dual')){
			/* Each helper is guarded on ITS OWN name, not on a sibling's.
			   These live in one file that gets copied station by station with
			   no version control, so a box can easily end up with a page that
			   is newer than its iss_insight_audience.php. Guarding the whole
			   group on iss_insight_html_dual() meant an older helper file threw
			   'Call to undefined function' and killed the page mid-render --
			   taking every chart, sort and print script below it with it. */
			if(function_exists('iss_insight_audience_css')) echo iss_insight_audience_css();
			if(function_exists('iss_insight_audience_js'))  echo iss_insight_audience_js();
			echo '<div id="issInsight">'
			   . iss_insight_html_dual($issN, $issF, $issRendered)
			   . '</div>';
		} else {
			echo '<div id="issInsight">'.iss_insight_html($issRendered).'</div>';
		}

		$issCfg = iss_insight_config();
		if(!empty($issCfg['enabled']) && $issCfg['provider'] !== 'none'
		   && file_exists(dirname(__FILE__)."/insight_ajax.php")){
			$issKey = iss_insight_stash($issCtx);
			echo '<script>(function(){var b=document.getElementById("issInsight");'
			   . 'if(!b||!window.XMLHttpRequest)return;var x=new XMLHttpRequest();'
			   . 'x.open("GET","insight_ajax.php?k='.$issKey.'",true);'
			   . 'x.onreadystatechange=function(){if(x.readyState===4&&x.status===200'
			   . '&&x.responseText&&x.responseText.indexOf("ins-block")!==-1){'
			   . 'b.innerHTML=x.responseText;if(window.issInsightApplyView)window.issInsightApplyView();}};x.send();})();</script>';
		}
	}
}
$issPanelHtml = ob_get_clean();
/* This page names its normalized context $issN, not $issCtxN as the equipment
   report does. Guarded on the name that actually exists here -- the other
   spelling would simply never be set and the band would silently not appear,
   which is exactly the failure mode that has cost time twice already. */
if(isset($issF) && isset($issN) && function_exists('iss_insight_summary_band')){
	if(function_exists('iss_insight_band_css')) $issBandHtml = iss_insight_band_css();
	$issBandHtml .= iss_insight_summary_band($issN, $issF, "issInsight");
}
?>
<?php echo $issBandHtml; ?>
<div style="margin-bottom:14px;">
	<button type="button" onclick="csrPrintReport()" style="padding:6px 14px;border:1px solid #00529B;background:#00529B;color:#fff;border-radius:4px;cursor:pointer;font-size:13px;">Print report</button>
</div>

<div style="display:flex;flex-wrap:wrap;gap:16px;margin-bottom:16px;">
	<!-- Height carries TOP_CARS rows at a legible pitch. Ten rows in 220px left
	     Chart.js ~18px each, under what an 11px tick needs, so it auto-skipped
	     every other car and the figure showed five labels against ten bars. If
	     TOP_CARS changes, this wants changing with it: roughly 80 + 25 per row. -->
	<div><canvas id="csrByCar" width="340" height="330"></canvas></div>
	<div><canvas id="csrByMonth" width="340" height="200"></canvas></div>
</div>

<div class="stat-legend" style="margin:0 0 8px;">
	<span><span class="swatch" style="background:#00529B;"></span>Click a car number for its full-year history</span>
	<span><span class="swatch" style="background:#FDB813;"></span>Click a monthly count for that car's incidents that month</span>
	<span><span class="swatch" style="background:#F9D6D6; border:1px solid #E3A9A9;"></span>Highlighted row = among the highest incident counts this year (&ge;60% of the peak)</span>
</div>

<?php /* @zerorows -- Deliberately OUT here, below the buffered table.
   $zeroRows is accumulated by the aggregation loop, which runs INSIDE
   ob_start()..ob_get_clean(); emitting the control above the <table>
   tag tested the counter before it existed, so `$zeroRows > 0` was a
   comparison against an undefined variable and the whole control
   silently never rendered. */ ?>
<style>
/* @zerorows -- Display only, three states. Rows always stay in the DOM, so
   sorting, the print clone and every total keep seeing the full roster; only
   visibility changes. Nothing is filtered server-side.

   Both classes are written out explicitly rather than using :not(.ccs-zero).
   The totals row and any header row carry neither class, so they survive all
   three views -- a totals line that vanished on one tab would be worse than
   the noise this control exists to remove. */
.ccs-rowtabs{font-size:12px;color:#5A6275;margin:0 0 6px;display:flex;
  align-items:center;gap:10px;flex-wrap:wrap;}
.ccs-rowtabs .ccs-seg{display:inline-flex;border:1px solid #C9CFDA;border-radius:4px;
  overflow:hidden;background:#fff;}
.ccs-rowtabs button{-webkit-appearance:none;appearance:none;border:0;background:#fff;
  color:#4A5666;font:inherit;font-size:12px;padding:3px 11px;cursor:pointer;line-height:1.6;}
.ccs-rowtabs button + button{border-left:1px solid #C9CFDA;}
.ccs-rowtabs button:hover{background:#F2F5F9;}
.ccs-rowtabs button.is-on{background:#00529B;color:#fff;}
table.ccs-rows-with tr.ccs-zero{display:none;}
/* @nonrevenue -- Placeholder rows read as part of the fleet table but are
   visibly not one of the 73. Marked rather than moved into a block of their
   own: they belong in the totals, and separating them invites the reader to
   subtract them back out. */
tbody tr.ccs-nonrev th.car{ border-left:3px solid #7A5AA8; }
.ccs-nr{ font-size:9px; font-weight:700; letter-spacing:.04em; color:#7A5AA8;
	vertical-align:super; }
th.ccs-nocar{ color:#5A6275; cursor:default; font-style:italic; font-weight:600;
	white-space:nowrap; }
.ccs-fleetnote{ margin:6px 0 0; font-size:11.5px; color:#5A6275; }
.ccs-fleetnote b{ color:#1A2238; }
table.ccs-rows-none tr.ccs-nonzero{display:none;}
@media print{.ccs-rowtabs .ccs-seg{display:none;}}
</style>
<?php if($zeroRows > 0){ ?>
<div class="ccs-rowtabs" id="csrMatrix-rowtabs">
  <span class="ccs-seg" role="group" aria-label="Which rows to show">
    <button type="button" data-rows="all">All</button>
    <button type="button" data-rows="with">With records</button>
    <button type="button" data-rows="none">No records</button>
  </span>
  <span class="ccs-rowcount"></span>
</div>
<script>
/* @zerorows -- A zero here is ambiguous: it can mean the unit ran all period
   without failing, or that it never ran at all. The console holds no
   service-day denominator to tell those apart, so these rows default to
   hidden as uninterpretable rather than as uninteresting -- and they get
   their own tab, because "which cars have nothing recorded?" is a real
   question to put to the depot's service log. The count is stated in every
   view so two printouts of the same period always explain their row count.

   Binding is DEFERRED. This control is printed above `echo $tableHtml`, so at
   parse time the table it governs does not exist yet -- binding inline made
   getElementById return null and the whole thing bailed silently, leaving
   tabs that rendered but did nothing. Every bail path now names itself under
   [ccs-rowtabs] rather than returning quietly. */
(function(){
	var TAG="[ccs-rowtabs csrMatrix]", bound=false;
	function init(){
		if(bound) return;
		var t=document.getElementById("csrMatrix");
		if(!t){ if(window.console&&console.info) console.info(TAG,"table not in the DOM yet"); return; }
		var bar=document.getElementById("csrMatrix-rowtabs");
		if(!bar){ if(window.console&&console.info) console.info(TAG,"control markup missing"); return; }
		var btns=bar.getElementsByTagName("button");
		if(!btns.length){ if(window.console&&console.info) console.info(TAG,"no buttons found"); return; }
		var lbl=bar.getElementsByTagName("span")[1];
		if(!lbl){ if(window.console&&console.info) console.info(TAG,"count label missing"); return; }
		bound=true;
		t.setAttribute("data-ccs-rowtabs","bound");

		var zero=<?php echo (int)$zeroRows; ?>, total=<?php echo (int)$totalRows; ?>,
		    KEY="ccsRowView-csrMatrix";
		lbl.appendChild(document.createTextNode(""));
		function set(v){
			t.className = t.className.replace(/ *ccs-rows-(all|with|none)/g,"") + " ccs-rows-" + v;
			for(var i=0;i<btns.length;i++){
				var on = btns[i].getAttribute("data-rows")===v;
				btns[i].className = on ? "is-on" : "";
				btns[i].setAttribute("aria-pressed", on?"true":"false");
			}
			var shown = (v==="all") ? total : (v==="with" ? total-zero : zero);
			lbl.firstChild.nodeValue =
				"Showing "+shown+" of "+total+" cars. "+zero+" have no records this period.";
			try{ localStorage.setItem(KEY,v); }catch(e){}
		}
		for(var i=0;i<btns.length;i++){
			(function(b){ b.onclick=function(){ set(b.getAttribute("data-rows")); return false; }; })(btns[i]);
		}
		var start="with";
		try{ var s=localStorage.getItem(KEY);
		     if(s==="all"||s==="with"||s==="none") start=s; }catch(e){}
		set(start);
	}
	if(document.readyState==="complete"||document.readyState==="interactive"){ init(); }
	if(document.addEventListener){ document.addEventListener("DOMContentLoaded",init,false); }
	else if(document.attachEvent){ document.attachEvent("onreadystatechange",init); }
	if(window.addEventListener){ window.addEventListener("load",init,false); }
	/* Last resort: a short poll, same shape as the dashboard datepicker fix.
	   Costs nothing once bound and covers any template that rewrites the body. */
	var tries=0, poll=setInterval(function(){
		init(); if(bound||++tries>30) clearInterval(poll);
	},100);
})();
</script>
<?php } ?>
<?php echo $tableHtml; ?>

<?php echo $issPanelHtml; ?>
<?php
/* @insight -- Defines issInsightPrintBlock()/issInsightPrintLead() for
   csrPrintReport() below. Emitted after the panel so the element it clones is
   already in the document; the functions themselves are not called until the
   button is pressed, so ordering beyond that does not matter. */
if(function_exists('iss_insight_print_js')) echo iss_insight_print_js();
?>

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
/* @sort -- Sorting reorders the DOM, and the printout is built by cloning the
   table's outerHTML. That is the whole trick: the clone reads the LIVE DOM, not
   the PHP-generated order, so whatever is on screen is what prints. No sort
   state has to be handed to the print handler at all.

   Written against the table directly rather than through DataTables: the
   .datatable2 auto-init may or may not run depending on the template, and a
   sort that silently does nothing on some pages is worse than none. */
(function(){
	var table = document.getElementById('csrMatrix');
	if(!table || !table.tBodies[0] || !table.tHead) return;
	var tbody = table.tBodies[0];

	/* Decided per cell, not per column: a coverage-gap cell carries a marker,
	   not a number, and must not read as 0 -- that would sort "no records" in
	   among the genuinely quiet months. Gaps sort last in BOTH directions,
	   because absent data is not a small value. */
	function cellValue(row, idx){
		var cell = row.cells[idx];
		if(!cell) return { miss:true };
		if((cell.className||'').indexOf('ccs-missing') !== -1) return { miss:true };
		var txt = (cell.textContent || '').trim();
		if(txt === '' || txt === '\u2014') return { miss:true };
		var num = parseFloat(txt.replace(/[^0-9.\-]/g, ''));
		return isNaN(num) ? { miss:false, txt:txt.toLowerCase() } : { miss:false, num:num };
	}

	function sortBy(idx, dir){
		var rows = Array.prototype.slice.call(tbody.rows);
		rows.sort(function(a, b){
			var x = cellValue(a, idx), y = cellValue(b, idx);
			if(x.miss && y.miss) return 0;
			if(x.miss) return 1;
			if(y.miss) return -1;
			var r;
			if(x.num !== undefined && y.num !== undefined) r = x.num - y.num;
			else r = String(x.txt !== undefined ? x.txt : x.num)
			           .localeCompare(String(y.txt !== undefined ? y.txt : y.num));
			return dir === 'asc' ? r : -r;
		});
		rows.forEach(function(r){ tbody.appendChild(r); });
	}

	var heads = table.tHead.rows[0].cells;
	Array.prototype.forEach.call(heads, function(th, idx){
		th.setAttribute('role','button');
		th.setAttribute('tabindex','0');
		/* @sorthover -- aria-label, NOT title. A title attribute renders as a
		   native tooltip that parks itself over the first data rows a moment
		   after the cursor lands, hiding the figures the header is meant to
		   help you read. aria-label announces the same thing to a screen
		   reader and draws nothing. */
		th.setAttribute('aria-label', 'Sort by ' + (th.textContent||'').trim());
		function go(){
			var cur = th.getAttribute('aria-sort');
			/* Count columns open DESCENDING: on a failures table the question is
			   almost always "which is worst", and making that the second click
			   is a needless step. Car # opens ascending. */
			var dir = cur === 'descending' ? 'asc'
			        : (cur === 'ascending' ? 'desc' : (idx === 0 ? 'asc' : 'desc'));
			Array.prototype.forEach.call(heads, function(o){ o.removeAttribute('aria-sort'); });
			th.setAttribute('aria-sort', dir === 'asc' ? 'ascending' : 'descending');
			sortBy(idx, dir);
		}
		th.addEventListener('click', go);
		th.addEventListener('keydown', function(e){
			if(e.key === 'Enter' || e.key === ' '){ e.preventDefault(); go(); }
		});
	});
})();

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

/* @monthpanel -- Same panel, different target. month_stats.php reads
   months= / days= as comma lists and by= for the breakdown axis; a car report
   drills into cars, so by=car.
   dayMonth is the month those DAYS belong to -- sent only in day view, where
   the tile names days rather than months. */
function openMonthPanel(year, csv, dayMonth, title){
	title = title || 'Period breakdown';
	var q = "by=car&year=" + encodeURIComponent(year)
	      + (dayMonth && dayMonth !== '0'
	          ? "&month=" + encodeURIComponent(dayMonth) + "&days=" + encodeURIComponent(csv)
	          : "&months=" + encodeURIComponent(csv))
	      + "&title=" + encodeURIComponent(title);
	q+="&tt=2a7b85131d93ffbaacc73f7ff024b55a";

	document.getElementById('ir-panel-title').textContent = title;
	document.getElementById('irFallbackLink').href = "month_stats.php?" + q;
	var frame = document.getElementById('irFrame');
	frame.classList.remove('ready');
	document.getElementById('irLoading').classList.remove('hidden');
	document.getElementById('irFallback').classList.add('hidden');
	clearTimeout(irLoadTimer);
	irExpectingLoad = true;
	frame.src = "month_stats.php?" + q + "&embed=1";
	document.getElementById('irPanel').classList.add('active');
	document.getElementById('irOverlay').classList.add('active');
	irLoadTimer = setTimeout(function(){
		if(irExpectingLoad) document.getElementById('irFallback').classList.remove('hidden');
	}, 6000);
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
	q+="&tt=2a7b85131d93ffbaacc73f7ff024b55a";

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

	// A zero draws no number: the bar is already absent, and "0" floating on
	// the axis is furniture rather than information. Nulls are skipped for the
	// same reason and one more — fillText would happily paint the string
	// "null" onto the canvas, which had no guard here before.
	function valueLabels(){
		return { id:'csrLabels', afterDatasetsDraw:function(chart){
			var ctx=chart.ctx, meta=chart.getDatasetMeta(0), data=chart.data.datasets[0].data;
			ctx.save(); ctx.font='11px Arial, sans-serif'; ctx.fillStyle=ink;
			ctx.textBaseline='middle'; ctx.textAlign='left';
			meta.data.forEach(function(bar,i){
				if(!data[i]) return;          /* null, undefined and 0 alike */
				ctx.fillText(data[i], bar.x+6, bar.y);
			});
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
			                   borderRadius:3, categoryPercentage:0.7, barPercentage:0.78 }] },
			options:{ indexAxis:'y', responsive:false, animation:false,
				layout:{ padding:{ right:22, bottom: tail.length ? 18 : 4 } },
				plugins:{
					title:{ display:true, text:'Car-level failures by car'+(tail.length?' (top '+TOP_CARS+')':'')+' \u2014 '+csrYear, color:ink, font:{size:11,weight:'normal'}, padding:{bottom:8} },
					legend:{ display:false },
					tooltip:{ callbacks:{ label:function(c){ return c.parsed.x+' failures'; } } }
				},
				scales:{ x:{ ticks:{ color:muted, precision:0, font:{size:10} }, grid:{ color:grid } },
				         /* autoSkip:false is the actual fix -- without it Chart.js
				            silently thins the ticks to whatever fits, so the chart
				            claims ten bars while naming five of them. The canvas
				            height above is what makes keeping all ten legible. */
				         y:{ ticks:{ color:ink, font:{size:11}, autoSkip:false, padding:4 },
				             grid:{ display:false } } }
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

	// Counts above each column, the vertical counterpart of valueLabels().
	// Kept separate rather than generalised: the two differ in anchor, baseline
	// and alignment, and a single function taking an orientation flag would be
	// longer than both.
	//
	// Zeros and nulls both draw nothing. A zero column has no bar to label and
	// "0" sitting on the axis is furniture, not information; a null has no
	// count to print at all.
	//
	// The cost is that a recorded zero month and an uncovered month now look
	// identical on the figure. The red footnote below names the uncovered
	// buckets outright, so the distinction is still on the page — it is just
	// carried by the footnote alone rather than by the footnote and a stray
	// numeral. Drawing zeros in muted grey would keep both; it was tried and
	// judged noisier than it was worth.
	function monthValueLabels(){
		return { id:'csrMonthLabels', afterDatasetsDraw:function(chart){
			var data = chart.data.datasets[0].data, n = data.length;
			if(!n || !chart.chartArea) return;
			var ctx = chart.ctx, area = chart.chartArea, i, w, widest = 0;

			ctx.save(); ctx.font='10px Arial, sans-serif'; ctx.fillStyle=ink;
			ctx.textAlign='center'; ctx.textBaseline='bottom';

			// Room check before drawing anything. Twelve months across 340px is
			// comfortable; a weekly or daily bucket is not, and overlapping
			// numerals are worse than no numerals. Measured against the widest
			// label so the figure is all-or-nothing rather than partly legible.
			for(i = 0; i < n; i++){
				if(!data[i]) continue;        /* matches what is drawn below */
				w = ctx.measureText(String(data[i])).width;
				if(w > widest) widest = w;
			}
			if(widest + 4 <= area.width / n){
				chart.getDatasetMeta(0).data.forEach(function(bar, k){
					if(!data[k]) return;      /* null, undefined and 0 alike */
					ctx.fillText(data[k], bar.x, bar.y - 3);
				});
			}
			ctx.restore();
		}};
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
			layout:{ padding:{ top:14, bottom: csrUncovered.length ? 16 : 2 } },
			plugins:{ title:{ display:true, text:'Car-level failures by '+csrBucketWord+', whole fleet', color:ink, font:{size:11,weight:'normal'}, padding:{bottom:6} }, legend:{ display:false } },
			scales:{ x:{ ticks:{ color:muted, font:{size:10} }, grid:{ display:false } },
			         y:{ ticks:{ color:muted, precision:0, font:{size:10} }, grid:{ color:grid } } }
		},
		plugins:[monthGapNote, monthValueLabels()]
	});

	window.csrPrintReport = function(){
		var imgCar   = document.getElementById('csrByCar').toDataURL('image/png');
		var imgMonth = document.getElementById('csrByMonth').toDataURL('image/png');
		var tbl = document.querySelector('.ccs-panel-body table');
		var tableHtml = tbl ? tbl.outerHTML : '';
		/* @insight -- read at press time, not from $issPanelHtml: if the model
		   refinement XHR has landed, the DOM is the newer of the two. */
		var csrIns  = (typeof issInsightPrintBlock === 'function') ? issInsightPrintBlock('issInsight') : '';
		var csrLead = (typeof issInsightPrintLead  === 'function') ? issInsightPrintLead('issInsight')  : '';
		var csrRows = tbl ? tbl.getElementsByTagName('tr').length : 0;
		var csrBrk  = (csrRows > 14) ? ' brk' : '';
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
				/* @insight -- was width:40% with a 2% margin, so the two figures took
				   84% and the remaining 16% of the line was dead. Narrowed to 29%+2%
				   to open a column for the analysis block; a ranked bar chart with a
				   handful of labels still reads at ~80mm. */
				'.chart{ display:inline-block; vertical-align:top; width:29%; margin:0 2% 10px 0; page-break-inside:avoid; }' +
				'.rpt-insight{ display:inline-block; vertical-align:top; width:35%; margin:0 0 10px; }' +
				/* @insight -- Page 1 was already a summary page with an orphaned stub
				   of table glued underneath. Break before the grid so it starts clean
				   with its repeating thead -- but only when there is enough table to
				   be worth a page of its own, or a short filtered view would print a
				   near-empty second sheet. csrPrintReport decides and adds .brk. */
				'h2.sec.brk{ page-break-before:always; margin-top:0; }' +
				'.chart img{ display:block; width:100%; height:auto; border:1px solid #e5e7eb; }' +
				'.chart .cap{ font-size:9px; color:#6b7280; margin-top:3px; }' +
				'.note{ font-size:9px; color:#6b7280; font-style:italic; margin:2px 0 0; }' +
				/* @zerorows -- the clone carries whichever ccs-rows-* class the
				   table had on screen; without these rules the hidden rows all
				   reappear on paper and the printout stops matching what was
				   printed from. */
				'table.ccs-rows-with tr.ccs-zero{ display:none; }' +
				'table.ccs-rows-none tr.ccs-nonzero{ display:none; }' +
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
				/* @insight -- Panel CSS does not cross into the popup with the markup,
				   so it is pulled in from the shared helper rather than copied into
				   four print blocks. json_encode emits a quoted JS string literal;
				   the '' fallback keeps the concatenation valid when the helper is
				   not deployed. */
				<?php echo function_exists("iss_insight_print_css") ? json_encode(iss_insight_print_css()) : "''"; ?> +
			'</style></head><body>' +
			'<div class="rpt-head">' +
				'<div class="rpt-org">DOTr &middot; MRT-3 Line 3 &middot; Operations Control</div>' +
				'<h1 class="rpt-title">Car Incidents by Year</h1>' +
				'<p class="rpt-subject">Fleet-wide, '+esc(csrYear)+'</p>' +
				(csrLead ? '<p class="rpt-lead">'+esc(csrLead)+'</p>' : '') +
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
				csrIns +
				'<p class="note">Figures count car-level failures: an incident affecting several cars counts once against each car, so '+csrIncidents+' incidents produce '+csrGrandTotal+' car-level failures. This matches the equipment summary and per-car reports; the incident history logs count one row per incident and show the smaller figure. Covers all severity levels. Shaded rows are cars at or above 60% of the worst car total.' + (function(){var t=document.getElementById("csrMatrix");if(!t||!t.getElementsByClassName) return '';var z=t.getElementsByClassName('ccs-zero').length;if(!z) return '';var c=t.className;if(c.indexOf('ccs-rows-none')!==-1) return ' This view lists ONLY the '+z+' cars with no records this period; the totals above cover the full roster.';if(c.indexOf('ccs-rows-with')!==-1) return ' '+z+' cars with no records this period are omitted from this table; they remain included in the totals above.';return ' Includes '+z+' cars with no records this period.';})() + '</p>' +
			'</div>' +
			'<h2 class="sec'+csrBrk+'">Monthly Breakdown by Car</h2>' +
			tableHtml +
			'<div class="rpt-foot">MRT-3 Information Sharing System &middot; generated <?php echo date("d M Y, H:i"); ?> &middot; for internal operational use' +
				(csrIns ? ' &middot; analysis computed from the figures in this report; wording generated automatically' : '') + '</div>' +
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