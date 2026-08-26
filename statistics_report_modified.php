<?php
/* Session first, before ANY output -- the console header calls
   session_start(), and Tmenu_2.php is included further down inside <body>
   (this page keeps its own <head>, which Tmenu_2.php does NOT supply). */
if(session_id()==""){ session_start(); }

/* embed=1 suppresses the nav. month_stats.php and equipt_stats.php -- which
   this page opens in its own slide panel -- are the same shape, and
   edit_ccdr.php already carries an embed flag for exactly this reason.
   Without the guard, the logo, header and nav bar would render inside an
   820px iframe. */
$SRM_EMBED = (isset($_GET['embed']) && $_GET['embed']!='');

/* ---------------------------------------------------------------------------
   GET fallback, so the dashboard can link a date range straight in.
   This page is POST-only; it holds NOTHING in the session. The parameter
   names sd/ed/range are not invented -- they are the ones this page already
   passes to generate_statistics_report.php on the Generate Printout link,
   so the whole file now speaks one vocabulary.
   POST always wins; this only fills in on a cold GET.
   --------------------------------------------------------------------------- */
if(!isset($_POST['search_date2']) && isset($_GET['sd']) && $_GET['sd']!==''){
	$_POST['search_date2'] = $_GET['sd'];
	$_POST['search_date']  = isset($_GET['ed']) && $_GET['ed']!=='' ? $_GET['ed'] : $_GET['sd'];
	$_POST['range']        = isset($_GET['range']) ? $_GET['range'] : 'custom';
}

// ---------------------------------------------------------------------------
// Loaded FIRST, before any markup. ccsCoverageCss() is echoed inside the <head>
// style block far above the database connection, so loading the helper down
// there meant the call happened while the function did not yet exist — a fatal
// error inside <head>, which is why this page rendered completely blank.
// ---------------------------------------------------------------------------
// Which months the console actually has records for — see data_coverage.php.
// Load the coverage helper if it is present. If data_coverage.php has not been
// uploaded yet, fall back to stubs that report every month as covered — the
// page then behaves exactly as it did before the helper existed, instead of
// dying on a failed require and rendering a blank page.
//
// dirname(__FILE__) rather than a bare relative path: a bare path resolves
// against the include_path and working directory, not the script's own folder.
if(file_exists(dirname(__FILE__)."/data_coverage.php")){
	require_once(dirname(__FILE__)."/data_coverage.php");
}
// @insight -- Analysis layer. Guarded the same way data_coverage.php is: a
// missing file must not take the page down, and the page must still render
// its table with no insight block at all.
if(file_exists(dirname(__FILE__)."/iss_insight.php")){
	require_once(dirname(__FILE__)."/iss_insight.php");
	if(file_exists(dirname(__FILE__)."/iss_insight_analytics.php")){
		require_once(dirname(__FILE__)."/iss_insight_analytics.php");
	}
	/* Executive/technical toggle. Optional like the rest: without this file
	   the page renders the technical block alone, exactly as before. */
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
?>
<!--- Modified by Jun
//--- Date: 8/6/2014
//--- Modify: screen layout
//--- Marker: @mjun
//---------------------------------------------------
//--- Console theme + presentation pass (01302026):
//--- Reconciled this page's look with equipment_history.php, its main
//--- drill-down destination -- same blue/gold console theme instead of
//--- grey, link colour now signals "clickable" at rest, added a caption
//--- explaining the red highlight AND the two different click targets
//--- (equipment name -> car breakdown, monthly count -> incident list),
//--- which previously had zero on-page explanation. Also fixed a real
//--- bug: the highest-total tracking loop below was writing into a
//--- stray, unused $car[] array (copy-paste from car_statistics_report.php)
//--- instead of $equipt[], so it silently stopped updating after the
//--- first comparison -- meaning the red-highlight threshold was being
//--- computed against the wrong "highest" value. No other query logic
//--- touched.
//--------------------------------------------------->
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Equipment Failures (by Type)</title>

<style type='text/css'>
/* ===========================================================================
   LINE 3 SCHEME — shared with car_statistics_report.php / equipment_history.php
   Blue leads the structure; yellow is a small accent (title-bar stripe +
   hover highlight), never the gridlines.
   =========================================================================== */

body { margin:24px 30px; background:#FAFAF6; color:#1A2238; font-family:"Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif; }

h2 { color:#1A2238; font-size:20px; }

.stat-toolbar {
	background:#00529B; border-bottom:3px solid #FDB813;
	border-radius:6px 6px 0 0; padding:10px 16px; margin-bottom:0;
}
.stat-toolbar table { border-collapse:collapse; }
.stat-toolbar th, .stat-toolbar td { border:none !important; padding:4px 8px; color:#FFFFFF; font-weight:600; font-size:13px; text-align:left; }
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



.stat-toolbar a.btn-generate {

	display:inline-block; height:28px; line-height:28px; border:none; border-radius:4px;

	background:#FDB813; color:#3A2D00; font-weight:700; font-size:12px; padding:0 14px;

	cursor:pointer; text-decoration:none; vertical-align:middle;

}

.stat-toolbar a.btn-generate:visited { color:#3A2D00; }

.stat-toolbar a.btn-generate:hover, .stat-toolbar a.btn-generate:active { background:#E5A50F; color:#3A2D00; text-decoration:none; }


.stat-legend {
	display:flex; align-items:center; gap:16px; flex-wrap:wrap;
	background:#F1EEE3; border:1px solid #E5DECC; border-top:none;
	padding:8px 16px; font-size:12px; color:#5A6275;
}
.stat-legend .swatch { display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:5px; vertical-align:middle; }

.rowHeading {background:#00529B; color:#FFFFFF; font-size:15px; font-weight:600;}
.rowClass {background-color: #F5F2E8;}

.train_ava { border-collapse:collapse; }
.train_ava td, .train_ava th { border:1px solid #E5DECC; padding:6px 8px; }

select { border: 1px solid #D8D2C2; color: #1A2238; background-color: #FFFFFF; border-radius:4px; }

/* --- mjun -- generate */
a.two { color:#00529B; font-weight:600; text-decoration:none; }
a.two:visited {color:#00529B;}
a.two:hover, a.two:active {color:#003E76; text-decoration:underline;}

<?php echo ccsCoverageCss(); ?>
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
/* @equiptpanel -- These three were dropped when the panel CSS was lifted across
   in two slices that met either side of them. Without them the iframe has no
   width or height rule, so it falls back to the HTML default 300x150 and the
   framed page renders as a small box in the corner of an 820px panel, and the
   body keeps its 16px/18px padding instead of letting the frame fill it. */
.ta-panel-body--ir { padding:0; overflow:hidden; position:relative; }
#irFrame           { display:block; width:100%; height:100%; border:0; background:#fff; opacity:0; transition:opacity .15s; }
#irFrame.ready     { opacity:1; }
/* @equiptpanel -- The loading overlay and its spinner. These were lost the same
   way: the slice that should have carried them was taken between two markers
   that appear in the opposite order in the source file, so it came out empty.
   Without .ir-loading.hidden{display:none} the spinner never goes away, and
   without the absolute positioning it sits in flow above the frame. */
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

/* @equiptpanel -- clickable tile + clickable equipment name, affordance at
   rest rather than only on hover, matching car_statistics_report.php. */
.kpi-tile--link { cursor:pointer; transition:background .12s, border-color .12s, box-shadow .12s; }
.kpi-tile--link:hover { background:#F3F7FC !important; border-color:#00529B !important; box-shadow:0 1px 5px rgba(0,40,90,.13); }
.kpi-tile--link:focus-visible { outline:2px solid #00529B; outline-offset:2px; }
.kpi-hint { font-size:11px; font-weight:600; color:#00529B; margin-top:4px; }
.kpi-tile--link:hover .kpi-hint { text-decoration:underline; }
.eq-link { color:#00529B; font-weight:600; text-decoration:none; cursor:pointer; }
.eq-link:hover, .eq-link:focus { text-decoration:underline; color:#003E76; }

/* @sort -- Same treatment as car_statistics_report.php. The arrow sits at low
   contrast at rest so a header reads as sortable before it is hovered.
   padding-right is smaller in dense mode: at 24 columns the arrow would
   otherwise eat width the counts need. */
#srmMatrix thead th { cursor:pointer; user-select:none; position:relative; padding-right:14px; }
#srmMatrix.stat-dense thead th { padding-right:10px; }
/* @sorthover -- was background:#003E76, a DARKER navy, written on the
   assumption that this header is navy and hover would deepen it. It is not:
   the header here renders light with dark text, so hovering inverted a single
   cell to solid navy -- a black hole in the middle of the row, and the label
   went unreadable wherever the template did not also flip the text colour.
   A light tint plus a full-strength arrow instead: enough to say "this is the
   column under the cursor" without repainting it. */
#srmMatrix thead th:hover { background:#E8F0F9; }
#srmMatrix thead th:hover::after { opacity:.85; }
/* The sorted column stays marked once the cursor leaves, which the hover
   tint alone cannot do. */
#srmMatrix thead th[aria-sort] { background:#DCE9F6; }
#srmMatrix thead th::after { content:"\2195"; position:absolute; right:3px; top:50%; margin-top:-6px; opacity:.35; font-size:10px; font-weight:400; }
#srmMatrix thead th[aria-sort="ascending"]::after  { content:"\25B2"; opacity:1; }
#srmMatrix thead th[aria-sort="descending"]::after { content:"\25BC"; opacity:1; }

.stat_hover:hover {
	background-color:#FFF1CC;
	text-decoration:underline;
	font-weight:bold;
}
</style>
<?php include("history_theme.php"); ?>

<link rel="stylesheet" href="jquery-ui-themes-1.11.1/themes/smoothness/jquery-ui.css" />
<?php
/* @filterui -- console re-skin for the From/To calendars. MUST come after the
   smoothness <link> above: most of its rules tie with smoothness on
   specificity and win on source order alone, so moving this line up silently
   restores the grey stock widget. Guarded so a server that has not received
   datepicker_theme.php yet keeps the old look instead of warning. */
if(file_exists(dirname(__FILE__)."/datepicker_theme.php")){ include(dirname(__FILE__)."/datepicker_theme.php"); }
?>
<script src="jquery-ui-1.11.1/external/jquery/jquery.js"></script>
<script src="jquery-ui-1.11.1/jquery-ui.js"></script>
<script language='javascript' src='ajax.js'></script>

<!--
<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
-->

<script language='javascript'>
/* @datepicker -- jQuery(function($){...}) rather than $(function(){...}).
   ajax.js loads immediately above this block, and old in-house ajax helpers
   very commonly define their own global $ (the document.getElementById kind).
   If that happens, $ is no longer jQuery by the time this runs, $(function(){})
   silently does nothing, and the pickers never bind -- which looks exactly like
   "they stopped working" without any error on the page.
   jQuery is never reassigned, so binding through it cannot be clobbered, and
   the inner $ is jQuery's own argument. */
jQuery(function($) {
	/*
    $( "#search_date2" ).daterangepicker(
	{
        ranges: {
           'Today': [moment(), moment()],
           'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
           'Last 7 Days': [moment().subtract(6, 'days'), moment()],
           'Last 30 Days': [moment().subtract(29, 'days'), moment()],
           'This Month': [moment().startOf('month'), moment().endOf('month')],
           'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
           'This Year': [moment().startOf('year'), moment().endOf('year')],
           'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')]
		   
        }
    });    

	*/
    $( "#search_date" ).datepicker({
      changeMonth: true,
      changeYear: true,
      showAnim: "clip"
    });    
    $( "#search_date2" ).datepicker({
      changeMonth: true,
      changeYear: true,
      showAnim: "clip"
    });    
	
	
});
</script>
</head>
<body>
<?php
/* The nav renders INSIDE body, after this page's own <head> -- Tmenu_2.php
   emits only the header markup and the <ul id="navMenu">, never the document
   scaffolding, so the page must supply that itself. */
if(!$SRM_EMBED){ require("Tmenu_2.php"); }
?>
<div class="ccs-page">

<?php
	/* Prefer the shared connector. The literal credentials below are the
	   original line, kept ONLY so this page still runs if db_config.php is
	   absent. Once iss_db() is confirmed working here, DELETE the else
	   branch and rotate that password -- it is in plaintext in a file that
	   gets copied between stations. */
	if(file_exists(dirname(__FILE__)."/db_config.php")){ require_once(dirname(__FILE__)."/db_config.php"); }
	if(function_exists('iss_db')){ $db=iss_db('transport'); }
	else { $db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport"); }
$coverage = ccsLoadCoverage($db);
$selfPage = basename(__FILE__);   /* @equiptpanel -- reload target, rename-safe */

/* ==========================================================================
   @roster -- THE population this report covers. One definition, read by every
   consumer below.

   It used to be the same literal pasted into four separate queries, and the
   list at line 371 carried 41 entries for 27 ids because it had been hand
   edited. Three of the four consumers agreed; the incident count in the KPI
   strip and the month_stats.php handoff never had it at all, which is how the
   strip came to read "27 car-level failures from 44 incidents" -- two figures
   drawn from different populations -- and how the day tile came to rank days
   over 27 equipment types while the panel it opens ranked them over all of
   them. Adding a consumer without the filter is the failure mode; there is now
   one place to look.

   The commented type='RS' query below is what this list appears to be a
   snapshot of. If the equipment table has gained a rolling-stock row since it
   was taken, this roster is already stale -- worth checking, and worth
   switching to if the two agree, because a query cannot go out of date the way
   a copied list can.
   ======================================================================== */
$rsEquiptIds = array(114,102,110,11,113,104,108,109,103,124,67,111,112,105,81,
                     118,119,64,115,89,120,123,121,116,2,122,117);
$rsEquiptIn  = "('".implode("','", $rsEquiptIds)."')";

$sql="select * from equipment where id in ".$rsEquiptIn." order by equipment_name";

//$sql="select * from equipment where type='RS' order by equipment_name";

$rs=$db->query($sql);

$nm=$rs->num_rows;

if((isset($_POST['search_date2']))&&($_POST['search_date2']!="")){
//$year=date("Y",strtotime($_POST['search_date2']));

//$start_date=date("Y-m-d",strtotime($_POST['search_date2']));
	


$start_date=date("Y-m-d",strtotime($_POST['search_date2']));


$end_date=date("Y-m-d",strtotime($_POST['search_date']));


$dates=explode(" - ",$_POST['search_date2']);
	$period=date("F d Y", strtotime($start_date))." - ".date("F d Y", strtotime($end_date));


}
else {
//$start_date=date("Y-m-d",strtotime("first day of this month"));

// @buckets -- date()'s 2nd argument is a timestamp, and this passed the STRING
// "2026-01-01". PHP 7 coerced it to 0 and silently returned 1970-01-01; PHP 8
// raises a TypeError, so a cold load with no search dates was a fatal. The
// value wanted here is already in Y-m-d form.
$start_date=date("Y")."-01-01";

$end_date=date("Y-m-d",strtotime("last day of this month"));
	$period=date("F d", strtotime($start_date))." - ".date("F d Y", strtotime($end_date));

//	$level=2;
}


// @buckets -- $level was being resolved in three places with three different
// answers, and the <select> has a blank first <option>: submitting it posted
// level="" while every branch tested isset(), which built "level='' and" and
// returned an empty report for the whole page. Resolved once, here.
// No POST at all keeps the previous default of level 2; an explicit blank
// choice now means ALL levels rather than none.
if(isset($_POST['level'])){
	$level = ($_POST['level'] !== '') ? $_POST['level'] : '';
$levelClause = ($level !== '') ? "level='".$level."' and " : "";

// @carfilter -- Mirrors the equipment filter on car_statistics_report.php, one
// axis over: that page reports one car and can narrow to one equipment, this
// one reports all equipment and can narrow to one car.
//
// Resolved once here for the same reason $level is: the <select> carries a
// blank first <option>, so "no car" posts car_no="" and an isset() test would
// read that as a request for car 0.
//
// car_no*1 rather than the raw column, so '05' and '5' fold together — the same
// coercion the per-car reports use to bucket them.
$carFilter = (isset($_POST['stat_car']) && $_POST['stat_car'] !== '') ? (int)$_POST['stat_car'] : 0;
$carClause = $carFilter ? " and incident_cars.car_no*1 = ".$carFilter." " : "";

}
else {
//	$level = "2";
$levelClause="";
}
?>
<!-- <form action='statistics_report.php' method='post'> -->
<div class="ccs-header">

<h1>Equipment Failures Per Year</h1>
<div class='sub'><?php echo $period; 

if($level!=""){
echo " / ";echo " Level ".$level;
}
/* @carfilter -- an active filter has to be visible in the heading, or a report
   showing a fraction of the fleet's failures looks like missing data. */
if($carFilter){ echo " / Car ".$carFilter; }
?>

</div>
</div>
<div class="ccs-panel">
<div class="ccs-panel-head">
<?php /* @toolbarform -- Two structural faults here, and together they put the
         date inputs outside every form in the parsed DOM:

         1. A bare <table> wrapped the toolbar and was never closed. A <form>
            sitting directly inside <table> but outside any <tr>/<td> gets
            FOSTER-PARENTED by the HTML parser -- lifted out of the table --
            which is why the surviving form ended up empty.
         2. A second <form> was opened inside the first. HTML forbids nested
            forms, so the parser DROPS the inner one outright. That inner form
            was the one wrapping Level / Car / From / To / Submit, and its
            action was misspelled 'statistics_report_modififed.php' besides.

         One form now, opened before the toolbar table and closed after it. */ ?>
<form action='statistics_report_modified.php' method='post'>
<table cellspacing="0" cellpadding="0" class='stat-toolbar'>
<tr>
	<td style="padding:8px 14px;vertical-align:middle;white-space:nowrap;width:1%;border:none">
<div width="50%" align=left>
<table>
<tr><th>Level</th>
<td>
<?php /* @buckets -- read $level, not $_POST directly: this warned on a cold
        load and did not reflect the resolved default. */ ?>
<select name='level'>
<option value=''>All Levels</option>
<option <?php if($level==1){ echo "selected"; } ?> value='1'>1</option>
<option <?php if($level==2){ echo "selected"; } ?> value='2'>2</option>
<option <?php if($level==3){ echo "selected"; } ?> value='3'>3</option>
<option <?php if($level==4){ echo "selected"; } ?> value='4'>4</option>

</select>
</td>

<?php /* @carfilter -- populated from the cars that actually appear in
        incident_cars rather than a hard-coded 1..73, so a car added later shows
        up and a retired one does not linger. car_no*1 folds '05' and '5'. */ ?>
<th>Car</th>
<td>
<select name='stat_car' id='carSelect'>
<option value=''>All cars</option>
<?php
$carRS=$db->query("select distinct car_no*1 as cn from incident_cars where car_no*1 > 0 order by cn");
if($carRS){
	while($carRow=$carRS->fetch_assoc()){
		$cn=(int)$carRow['cn'];
		echo "<option value='".$cn."'".($carFilter==$cn ? " selected" : "").">".$cn."</option>";
	}
}
?>
</select>
</td>

<th>From</th>
<td> <input type="text" name='search_date2' id='search_date2'>
</td>
<th>To</th>
<td> <input type="text" name='search_date' id='search_date'>
</td>
<th><input type=submit value='Submit' /></th>

</tr>
</table>
	</td>
	
	<td style="padding:8px 14px;vertical-align:middle;text-align:right;white-space:nowrap;border:none">
	<?php
	/**

<a href='#' class="btn-generate" onclick='window.open("generate_statistics_report.php?sd=<?php echo date("Y-m-d",strtotime($_POST['search_date2'])); ?>&ed=<?php echo date("Y-m-d",strtotime($_POST['search_date'])); ?>&range=<?php echo $_POST['range']; ?>&level=<?php echo $level; ?>");'><b>Generate Printout</b></a>


	*/ ?>


	</td>
	
</tr>
</table>
<?php /* @toolbarform -- the form's close. The only other </form> in this file
         is inside an HTML comment further down, so the form was never actually
         closed; the browser then closed it wherever its own error recovery
         decided, which is part of why the inputs ended up orphaned. */ ?>
</form>


<!--

<tr>
<th>Range</th>
<td>

<select name='range'>
<option value='daily'>Daily</option>
<option value='weekly'>Weekly</option>
<option value='monthly'>Monthly</option>
<option value='yearly'>Yearly</option>
<option value='custom'>Range</option>

</select>

</tr>
-->
<!--
</tr>
</table>
</form>

-->
</div>
<?php

/**
if($_POST['range']=="daily"){
$end_date=$start_date;	
}
else if($_POST['range']=="weekly"){
$end_date=date("Y-m-d",strtotime($_POST['search_date2']."+1 week"));
	
}

else if($_POST['range']=="monthly"){
$end_date=date("Y-m-d",strtotime($_POST['search_date2']."+1 month"));
	
}
else if($_POST['range']=="yearly"){
$end_date=date("Y-m-d",strtotime($_POST['search_date2']."+365 days"));
	
}
else if($_POST['range']=="custom"){
$end_date=date("Y-m-d",strtotime($_POST['search_date']));
	
}
}
else {
$start_date=date("Y")."-01-01";
$end_date=date("Y")."-12-31";	
	
}

*/
//if(isset($_POST['search_date2'])){

//$year=date("Y",strtotime($_POST['search_date2']));

//$start_date=date("Y-m-d",strtotime($_POST['search_date2']));

//$init_start=$start_date;
/*
if($_POST['range']=="daily"){
$end_date=$start_date;	
}
else if($_POST['range']=="weekly"){
$end_date=date("Y-m-d",strtotime($_POST['search_date2']."+1 week"));

	$start=(date("m",strtotime($start_date)))*1;
	$end=date("m",strtotime($end_date."-1 day"))*1;

	$period=date("F d, Y", strtotime($start_date))." - ".date("F d, Y", strtotime($end_date));
	
}

else if($_POST['range']=="monthly"){
$end_date=date("Y-m-d",strtotime($_POST['search_date2']."+1 month"));

	$start=date("m",strtotime($start_date))*1;
	$end=date("m",strtotime($end_date))*1;
	$end--;

	$period=date("F Y", strtotime($start_date));
	
}
else if($_POST['range']=="yearly"){
$end_date=date("Y-m-d",strtotime($_POST['search_date2']."+365 days"));

	$start=(date("m",strtotime($start_date)))*1;
	$end=date("m",strtotime($end_date."-1 day"))*1;

	$period=date("F", strtotime($start_date))." - ".date("F d, Y", strtotime($end_date));
	
}

*/

/*
if($_POST['range']=="custom"){

$dates=explode(" - ",$_POST['search_date2']);


$start_date=date("Y-m-d",strtotime($dates[0]));
$end_date=date("Y-m-d",strtotime($dates[1]));

//$end_date=date("Y-m-d",strtotime($_POST['search_date']));

	$start=(date("m",strtotime($start_date)))*1;
	$end=date("m",strtotime($end_date."-1 day"))*1;

	$period=date("F d, Y", strtotime($start_date))." - ".date("F d, Y", strtotime($end_date));
	
}

*/
	
//}
//else {
	
//	$start=1;
//	$end=12;



	
//}






// ---- Column model --------------------------------------------------------
// @buckets -- One list of columns, built once, read by the header, the
// aggregation loop, the cells, the totals row, the charts and the tiles.
//
// It replaces this, which was the whole reason a range spanning two years
// only ever drew a handful of columns:
//
//   $difference = date("m", strtotime(...end...) - strtotime(...start...)) - 1;
//
// That subtracts two timestamps, giving a DURATION in seconds, and then reads
// it as if it were a date. For Jan 2025 -> Aug 2026 the gap is about 52 000 000
// seconds; the epoch plus that lands in August 1971, so date("m") returned 08
// and the report drew 8 columns for a 20-month range. Inside a single year the
// arithmetic happens to land close enough that nobody noticed.
//
// Day view is the same rule car_statistics_report.php uses, mapped onto this
// page's inputs: there is no month dropdown here, so the trigger is a From-To
// range that begins and ends inside one calendar month.
$mStart = new DateTime(date("Y-m-01", strtotime($start_date)));
$mEnd   = new DateTime(date("Y-m-01", strtotime($end_date)));
// A To earlier than From used to yield a negative span and an empty table.
// Swap rather than clamp, so the report still answers the question asked.
if(strtotime($end_date) < strtotime($start_date)){
	$swap = $start_date; $start_date = $end_date; $end_date = $swap;
	$mStart = new DateTime(date("Y-m-01", strtotime($start_date)));
	$mEnd   = new DateTime(date("Y-m-01", strtotime($end_date)));
}
$monthSpan = ($mEnd->format('Y') - $mStart->format('Y')) * 12
           + ($mEnd->format('n') - $mStart->format('n'));

$isDayView    = ($monthSpan === 0);
$crossesYears = ($mStart->format('Y') !== $mEnd->format('Y'));
$bucketWord   = $isDayView ? 'day' : 'month';

$buckets = array();
if($isDayView){
	$d = new DateTime(date("Y-m-d", strtotime($start_date)));
	$e = new DateTime(date("Y-m-d", strtotime($end_date)));
	while($d <= $e){
		$buckets[] = array('key'=>(int)$d->format('Ymd'), 'label'=>$d->format('j'),
		                   'head'=>$d->format('j'), 'ym'=>$d->format('Y-m'),
		                   'from'=>$d->format('Y-m-d'), 'to'=>$d->format('Y-m-d'),
		                   'y'=>$d->format('Y'), 'm'=>$d->format('m'));
		$d->modify('+1 day');
	}
}
else {
	for($k=0;$k<=$monthSpan;$k++){
		// Stepping from the 1st: "+1 month" off a 31st lands in the following
		// month, which would drop February from any range containing it.
		$b = new DateTime($mStart->format('Y-m-01'));
		$b->modify("+".$k." months");
		$buckets[] = array('key'=>(int)$b->format('Ym'),
		                   'label'=>$crossesYears ? $b->format("M y") : $b->format("F"),
		                   'head' =>$crossesYears ? $b->format("M")."<br>".$b->format("y") : $b->format("F"),
		                   'ym'=>$b->format('Y-m'),
		                   'from'=>$b->format('Y-m-01'), 'to'=>$b->format('Y-m-t'),
		                   'y'=>$b->format('Y'), 'm'=>$b->format('m'));
	}
}
// Past this many columns the table needs to compress to stay on the page.
$isDense = (count($buckets) > 13);

for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();

	$equipt[$i]['id']=$row['id'];
	$equipt[$i]['equipment']=$row['equipment_name'];
	foreach($buckets as $b){
		$equipt_count["Equipt_".$row['id']]["Month_".$b['key']]=0;
	}
	$equipt_count["Equipt_".$row['id']]["total"]=0;

}



?>
<style>
/* @buckets -- a two-year range is 24 columns plus label and total. Rather than
   cap the range, the table compresses: smaller type, tighter cells, and the
   equipment column pinned so the names stay readable while the counts narrow. */
.stat-dense { font-size:11px; }
.stat-dense th, .stat-dense td { padding:2px 3px !important; }
.stat-dense thead th { font-size:10px; line-height:1.15; }
.stat-dense tbody th, .stat-dense tfoot th { font-size:11.5px; white-space:nowrap; }
.stat-dense td a { font-size:11px; }
.stat-wrap { overflow-x:auto; }
</style>
<div class='ccs-panel-body'>
<?php
// The summary figures are only known once the aggregation loop inside the
// table has run, so buffer the table and emit the summary above it.
ob_start();
?>
<div class="stat-wrap">
<table id="srmMatrix" class="table table-striped table-bordered bootstrap-datatable datatable2 ccs-rows-with<?php echo $isDense ? ' stat-dense' : ''; ?>" border=1px style='border-collapse:collapse;' width=100%>
<thead>
<tr >
<th>Equipment</th>
<?php


// @buckets -- header columns come straight off the bucket list now. The
// $difference / $tag_date / $limit arithmetic that used to live here is gone;
// see the column model above for why it could not survive a year boundary.
foreach($buckets as $b){
	echo "\t<th>".$b['head']."</th>\n";
}
?>
<th>Total
</th>
</tr>
</thead>
<tbody>
<?php
/*
if(isset($_POST['search_date2'])){
	$start=date("m",$start_date);
	$end=date("m",strtotime($end_date));
	
	if($_POST['range']=="yearly"){
		$end=12;
	}
	
	
}
else {
//	$start=1;
//	$end=12;
}
*/




// @buckets -- one pass per column, with the window taken from the bucket
// instead of rebuilt from $tag_date + N months on every iteration.
foreach($buckets as $b){

	$start_date1 = $b['from'];
	$end_date1   = $b['to'];
	$label       = $b['key'];

	// Counts INCIDENT-CAR PAIRS, not incidents: the incident_cars join means an
	// incident affecting three cars contributes three. This is what makes the
	// figures here reconcile with equipment_cars_stats.php, which has always
	// counted per car. Without the join this page counted one per incident and
	// the two reports could never agree.
	$sql="select incident_report.equipt as equipt, count(1) as equipt_count
	       from incident_report
	       inner join incident_cars on incident_report.id=incident_cars.incident_id
		   where ".$levelClause." incident_date between '".$start_date1." 00:00:00' and '".$end_date1." 23:59:59'
	         and incident_report.equipt in ".$rsEquiptIn."
	         ".$carClause."
	       group by incident_report.equipt";
	$rs=$db->query($sql);
	$nm=$rs->num_rows;

	for($k=0;$k<$nm;$k++){
		$row=$rs->fetch_assoc();
		$equipt_count["Equipt_".$row['equipt']]["Month_".$label]+=$row['equipt_count'];
		$equipt_count["Equipt_".$row['equipt']]["total"]+=$row['equipt_count'];
	}

	// Same pair-counting rule for the external defect rows.
	// @buckets -- this used a bare level='$level' while the query above used
	// $levelClause, so with no level chosen the first query counted every level
	// and this one counted none. Both use the clause now.
	$sql="select is_external.incident_defects.equipt_id as equipt_id, count(1) as equipt_count
	       from incident_report
	       inner join is_external.incident_defects on incident_report.id=is_external.incident_defects.incident_id
	       inner join incident_cars on incident_report.id=incident_cars.incident_id
	       where ".$levelClause." incident_date between '".$start_date1." 00:00:00' and '".$end_date1." 23:59:59'
	         and is_external.incident_defects.equipt_id in ".$rsEquiptIn."
	         ".$carClause."
	       group by is_external.incident_defects.equipt_id";
	$rs=$db->query($sql);
	$nm=$rs->num_rows;
	for($k=0;$k<$nm;$k++){
		$row=$rs->fetch_assoc();
		$equipt_count["Equipt_".$row['equipt_id']]["Month_".$label]+=$row['equipt_count'];
		$equipt_count["Equipt_".$row['equipt_id']]["total"]+=$row['equipt_count'];
	}
}
?>
<?php
// ---- Totals, peak equipment, and the highlight threshold -----------------
// The old pairwise sortCar() walk left $highestCar without a 'total' whenever
// the list held fewer than two entries, and never set $equipt[0]['total'].
// A straight max is clearer and safe on any list length.
//
// Month labels are the YYYYMM keys the aggregation loop above wrote, rebuilt
// here in the same order the columns are rendered.
$monthKeys  = array();
$monthNames = array();
foreach($buckets as $b){ $monthKeys[] = $b['key']; $monthNames[] = $b['label']; }

$grandTotal = 0;
$peakTotal  = 0;
$peakName   = '';
$peakId     = 0;   /* @equiptpanel -- the tile carried only a NAME; the panel
                      needs an id to query on */
$activeEquipt = 0;
$monthTotals = array_fill(0, count($monthKeys), 0);

$zeroRows = 0; $totalRows = count($equipt);   /* @zerorows */
foreach($equipt as $idx => $e){
	$key = "Equipt_".$e['id'];
	$t = isset($equipt_count[$key]['total']) ? (int)$equipt_count[$key]['total'] : 0;
	$equipt[$idx]['total'] = $t;
	$grandTotal += $t;
	if($t > 0) $activeEquipt++; else $zeroRows++;   /* @zerorows */
	if($t > $peakTotal){ $peakTotal = $t; $peakName = $e['equipment']; $peakId = $e['id']; }
	foreach($monthKeys as $mi => $mk){
		$monthTotals[$mi] += isset($equipt_count[$key]["Month_".$mk]) ? (int)$equipt_count[$key]["Month_".$mk] : 0;
	}
}

$flagThreshold = $peakTotal * 0.60;

/* ==========================================================================
   @insight -- three extra pulls the analysis layer needs. All three reuse
   $levelClause and $carClause so they describe exactly the slice the table
   above describes; anything else and the panel would contradict the page it
   sits under.

   Every one is a single query over the whole range, NOT one per bucket, so
   this adds three round trips to a page that already runs two per column.
   ======================================================================== */
$issCross   = array();   /* car x equipment -- fleet-wide vs unit-specific  */
$issHistory = array();   /* monthly totals, all years -- seasonal baseline  */
$issEvents  = array();   /* incident-level rows -- repeat-failure detection */

if(function_exists('iss_insight')){

	$eqIn = $rsEquiptIn;   /* @roster -- was a fourth copy of the literal */

	/* -- (a) the cross-tab. The page's own per-bucket query with car_no added
	      to the GROUP BY: same joins, same clauses, same counting basis, so
	      its grand total must equal $grandTotal. External defects have no car
	      mapping, so they are excluded here AND subtracted from the expected
	      total below -- otherwise the reconciliation guard fires every time. */
	$sql = "select incident_report.equipt as eq, incident_cars.car_no*1 as cn,
	               count(1) as c
	          from incident_report
	          inner join incident_cars on incident_report.id=incident_cars.incident_id
	         where ".$levelClause." incident_date between '".$start_date." 00:00:00'
	                                                 and '".$end_date." 23:59:59'
	           and incident_report.equipt in ".$eqIn."
	           ".$carClause."
	         group by incident_report.equipt, cn";
	$rs = $db->query($sql);
	$cellMap = array(); $carSeen = array(); $crossGrand = 0;
	if($rs){
		while($r = $rs->fetch_assoc()){
			$cn = (int)$r['cn'];
			if($cn <= 0) continue;                      /* unassigned car */
			$cellMap[$r['eq']][$cn] = (int)$r['c'];
			$carSeen[$cn] = true; $crossGrand += (int)$r['c'];
		}
	}
	if(count($carSeen) >= 2 && count($cellMap) >= 2){
		$carList = array_keys($carSeen); sort($carList, SORT_NUMERIC);
		$eqLabels = array(); $eqIds = array();
		foreach($equipt as $e){
			if(!isset($cellMap[$e['id']])) continue;
			$eqLabels[] = $e['equipment']; $eqIds[] = $e['id'];
		}
		$matrix = array();
		foreach($carList as $ci => $cn){
			$rowv = array();
			foreach($eqIds as $eid){ $rowv[] = isset($cellMap[$eid][$cn]) ? $cellMap[$eid][$cn] : 0; }
			$matrix[] = $rowv;
		}
		$issCross = array('row_label'=>'Car', 'col_label'=>'Equipment',
		                  'rows'=>array_map('iss_srm_carlabel', $carList),
		                  'cols'=>$eqLabels, 'matrix'=>$matrix,
		                  'expect_grand'=>$crossGrand);
	}

	/* -- (b) seasonal baseline. Same filters, date restriction removed, so a
	      single month can be judged against its own month across every year
	      the console holds. Months the coverage table marks missing are
	      dropped rather than counted as quiet. */
	$sql = "select date_format(incident_date,'%Y-%m') as ym, count(1) as c
	          from incident_report
	          inner join incident_cars on incident_report.id=incident_cars.incident_id
	         where ".$levelClause." incident_report.equipt in ".$eqIn."
	           ".$carClause."
	         group by ym order by ym";
	$rs = $db->query($sql);
	$hb = array(); $hv = array();
	if($rs){
		while($r = $rs->fetch_assoc()){
			if(ccsMonthStatus($coverage, $r['ym']) === 'missing') continue;
			$hb[] = $r['ym']; $hv[] = (int)$r['c'];
		}
	}
	/* Only useful against month buckets -- a month-of-year index cannot be
	   applied to a report whose columns are days of one month. */
	if(!$isDayView && count($hb) >= 24){
		$issHistory = array('buckets'=>$hb, 'values'=>$hv);
	}

	/* -- (c) incident-level rows for recurrence. Capped: past a few thousand
	      the Poisson pass costs more than the finding is worth, and the page
	      is already doing two queries per column. */
	$sql = "select incident_report.incident_date as d,
	               incident_report.equipt as eq, incident_cars.car_no*1 as cn
	          from incident_report
	          inner join incident_cars on incident_report.id=incident_cars.incident_id
	         where ".$levelClause." incident_date between '".$start_date." 00:00:00'
	                                                 and '".$end_date." 23:59:59'
	           and incident_report.equipt in ".$eqIn."
	           ".$carClause."
	         order by incident_report.incident_date limit 6000";
	$rs = $db->query($sql);
	$eqName = array();
	foreach($equipt as $e){ $eqName[$e['id']] = $e['equipment']; }
	if($rs){
		while($r = $rs->fetch_assoc()){
			if((int)$r['cn'] <= 0) continue;
			$issEvents[] = array(
				'date'       => substr($r['d'],0,10),
				'unit_key'   => 'car'.(int)$r['cn'],
				'unit_label' => 'Car '.(int)$r['cn'],
				'fault_key'  => $r['eq'],
				'fault_label'=> isset($eqName[$r['eq']]) ? $eqName[$r['eq']] : ('Equipment '.$r['eq']),
			);
		}
	}
}
function iss_srm_carlabel($n){ return 'Car '.$n; }

// @equiptpanel -- What period the panel opens on. Computed here, above the
// table, because BOTH entry points read it and the table renders first.
//
// This page filters by a From-To range, not by year/month, so the two only line
// up when the range sits inside one calendar year (or one month). A wider range
// is handed over as All Time rather than as a year it would misrepresent —
// equipt_stats.php reads a missing year that way by design.
// @range -- Hand over the actual From-To range. This used to send a year, and
// only when the range sat inside one, so a Jan 2025 - Apr 2026 report opened
// the panel with no year at all and equipt_stats.php rendered All Time.
// $start_date/$end_date are this page's own resolved window, so the panel shows
// exactly the period the table above it shows.
$panelFrom = $start_date;
$panelTo   = $end_date;
// @carfilter -- handed to equipt_stats.php so the panel shows the same slice
// the table does. Opening a panel that silently widens back to the whole fleet
// would make the two disagree on the same screen.
$panelCar  = $carFilter;

// ---- Highest month (or day) ----------------------------------------------
// @buckets -- replaces "Avg per affected type", matching the tile on
// car_statistics_report.php. Columns the coverage table marks as missing are
// not candidates: they read 0 for want of records, not for want of failures.
// Ties are shown rather than silently resolved.
$peakBucketTotal = 0;
$peakBucketIdx   = array();
$coveredBuckets  = 0;
foreach($buckets as $bi => $b){
	if(ccsMonthStatus($coverage, $b['ym']) === 'missing') continue;
	$coveredBuckets++;
	$bt = (int)$monthTotals[$bi];
	if($bt > $peakBucketTotal){ $peakBucketTotal=$bt; $peakBucketIdx=array($bi); }
	elseif($bt > 0 && $bt === $peakBucketTotal){ $peakBucketIdx[]=$bi; }
}

if(!count($peakBucketIdx)){
	$peakBucketLabel = '&mdash;';
	$peakBucketSub   = $coveredBuckets ? 'no failures recorded' : 'no '.$bucketWord.'s with data';
}
elseif(count($peakBucketIdx) === 1){
	$b0 = $buckets[$peakBucketIdx[0]];
	$peakBucketLabel = $isDayView ? date("j F", strtotime($b0['from']))
	                              : date("F Y", strtotime($b0['from']));
	$peakBucketSub   = $peakBucketTotal.' failure'.($peakBucketTotal==1?'':'s');
}
else {
	$abbr = array();
	foreach($peakBucketIdx as $bi){ $abbr[] = $buckets[$bi]['label']; }
	$peakBucketLabel = count($abbr) > 3 ? count($abbr).'-way tie' : implode(' &amp; ', $abbr);
	$peakBucketSub   = $peakBucketTotal.' failures each';
}
if($coveredBuckets > 0 && $coveredBuckets < count($buckets)){
	$peakBucketSub .= ' &middot; of '.$coveredBuckets.' '.$bucketWord.($coveredBuckets==1?'':'s').' with data';
}

$equiptTotals = array();
foreach($equipt as $e){ if($e['total'] > 0) $equiptTotals[] = array($e['equipment'], (int)$e['total']); }
usort($equiptTotals, function($a,$b){ return $b[1]-$a[1]; });

$monthSeries = array();
$uncoveredMonths = array();
foreach($monthKeys as $mi => $mk){
	$mks = (string)$mk;
	$ym  = substr($mks,0,4)."-".substr($mks,4,2);
	if(ccsMonthStatus($coverage, $ym) === 'missing'){
		$monthSeries[] = array($monthNames[$mi], null);   // null, not 0
		$uncoveredMonths[] = $monthNames[$mi];
	}
	else { $monthSeries[] = array($monthNames[$mi], (int)$monthTotals[$mi]); }
}
$coverageNote = ccsCoverageNote($coverage);

// Distinct incidents behind these car-level failures, on the same scope as the
// aggregation above — stated so the relationship to the incident logs is
// visible rather than inferred.
/* @roster -- This had NO equipment condition while $grandTotal had one, so the
   tile printed a rostered pair count beside an unrostered incident count. The
   two are then not comparable, and the pairing is arithmetically impossible:
   every incident in the pair count joins at least one car, so pairs can never
   be fewer than incidents. The strip read "27 car-level failures from 44
   incidents". Same population on both sides now. */
$distinctIncidents = 0;
$dq = $db->query("select count(distinct incident_report.id) as c
                  from incident_report
                  inner join incident_cars on incident_report.id=incident_cars.incident_id
                  where ".$levelClause." incident_date between '".$start_date." 00:00:00' and '".$end_date." 23:59:59'
                    and incident_report.equipt in ".$rsEquiptIn."
                  ".$carClause);
if($dq && ($dr = $dq->fetch_assoc())) $distinctIncidents = (int)$dr['c'];

/* @roster -- What the roster leaves out, counted rather than hidden.
   Narrowing the report to 27 equipment types is a deliberate scope, but blank
   and out-of-roster equipment is not rare in this system -- the description
   classifier exists because of it -- so a reader has no way to know whether
   the grand total is the whole picture unless the page says so. Same basis as
   the aggregation above (incident-car pairs, same window, same level and car
   filters), differing only in the equipment condition, so the two figures add
   up to the period's unrestricted total.

   Deliberately NOT broken down by type: naming what is missing would need a
   row per unrostered equipment, which is the wider report this page is not.
   The volume is enough to tell a reader whether to go and look. */
$excludedPairs = 0;
$xq = $db->query("select count(1) as c
                  from incident_report
                  inner join incident_cars on incident_report.id=incident_cars.incident_id
                  where ".$levelClause." incident_date between '".$start_date." 00:00:00' and '".$end_date." 23:59:59'
                    and (incident_report.equipt is null
                         or incident_report.equipt not in ".$rsEquiptIn.")
                  ".$carClause);
if($xq && ($xr = $xq->fetch_assoc())) $excludedPairs = (int)$xr['c'];

// ---- Rows -----------------------------------------------------------------
// Iterates the canonical $equipt list, so a row's label and its figures always
// come from the same record. The previous version re-queried the equipment
// table for labels while reading figures from $equipt by position, and emitted
// two near-identical copies of every cell for the flagged / unflagged cases.
foreach($equipt as $i => $e){
	$key     = "Equipt_".$e['id'];
	$rowTot  = $e['total'];
	$flagged = ($flagThreshold > 0 && $rowTot >= $flagThreshold);

	if(isset($_POST['level'])){
		$link_sd = $_POST['search_date'];
		$link_ed = $_POST['search_date2'];
	}
	else {
		$link_sd = date("Y-01-01");
		$link_ed = $end_date1;
	}
?>
<tr class="rowClass <?php echo ($e['total'] == 0) ? 'ccs-zero' : 'ccs-nonzero'; ?>">
	<th style="text-align:left;">
		<?php /* @equiptpanel -- was window.open("equipment_cars_stats.php?...").
		         Opens equipt_stats.php in the slide panel now, the same way
		         car_statistics_report.php opens car_stats.php. equipment_cars_stats.php
		         is untouched and still reachable by URL. */ ?>
		<a href="#" class="eq-link" onclick="openEquiptPanel(<?php echo htmlspecialchars(json_encode($panelFrom), ENT_QUOTES); ?>,<?php echo htmlspecialchars(json_encode($panelTo), ENT_QUOTES); ?>,'<?php echo (int)$e['id']; ?>',<?php echo htmlspecialchars(json_encode($e['equipment']), ENT_QUOTES); ?>,'<?php echo (int)$panelCar; ?>'); return false;"><?php echo htmlspecialchars($e['equipment']); ?></a>
		<?php if($flagged){ echo " <span title='At or above 60% of the highest equipment total' style='font-size:11px;'>&#9679;</span>"; } ?>
	</th>
<?php
	foreach($monthKeys as $mi => $mk){
		$v  = isset($equipt_count[$key]["Month_".$mk]) ? (int)$equipt_count[$key]["Month_".$mk] : 0;
		$yy = substr((string)$mk, 0, 4);
		$mon= substr((string)$mk, 4, 2);
		/* @dayfix -- in day view $mk is Ymd, so these two substr() calls pulled
		   the year and month out and the DD was discarded. The link then read
		   y=2026&m=03 and equipment_history widened the click back to all of
		   March.

		   Sent as a one-day range rather than a new &d= parameter: this page
		   already supports sd/ed natively, the bucket carries from/to for exactly
		   this, and the filter bar can then show and adjust the day. mode=range
		   is explicit so a stale period pair cannot win over it. */
		$dayB = ($isDayView && isset($buckets[$mi])) ? $buckets[$mi] : null;
		$ehQS = $dayB
		      ? "equipt=".$e['id']."&mode=range&sd=".urlencode($dayB['from']).
		        "&ed=".urlencode($dayB['to'])."&level=".$level
		      : "equipt=".$e['id']."&y=".$yy."&m=".$mon."&level=".$level;
?>
<?php
		if(ccsMonthStatus($coverage, $yy."-".$mon) === 'missing'){ echo ccsCoverageCell('missing'); }
		else {
?>
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:<?php echo $v>0 ? '#00529B' : '#B4B2A9'; ?>;' onclick='window.open("equipment_history.php?<?php echo $ehQS; ?>",target="_self")'><?php echo $v; ?></a></td>
<?php } ?>
<?php
	}
?>
	<td align=center style="font-weight:600;"><?php echo $rowTot; ?></td>
</tr>
<?php
}

if(!count($equipt)){
?>
<tr><td colspan="<?php echo count($monthKeys)+2; ?>" align=center style="padding:18px;opacity:.6;">No equipment failures recorded for this range.</td></tr>
<?php
}
?>
</tbody>
<tfoot>
<tr style="background:#F1EEE3;font-weight:700;">
	<th style="text-align:left;">All equipment</th>
<?php foreach($monthTotals as $mi => $mt){
	$mk = (string)$monthKeys[$mi];
	if(ccsMonthStatus($coverage, substr($mk,0,4)."-".substr($mk,4,2)) === 'missing'){ echo ccsCoverageCell('missing'); continue; }
?>
	<td align=center><?php echo $mt; ?></td>
<?php } ?>
	<td align=center><?php echo $grandTotal; ?></td>
</tr>
</tfoot>
<?php
?>


</table>
</div>
<?php
$tableHtml = ob_get_clean();
?>

<div style="display:flex;flex-wrap:wrap;gap:10px;margin:14px 0;">
	<div style="flex:1;min-width:150px;border:1px solid #E5DECC;border-radius:6px;padding:10px 12px;background:#FBFAF6;">
		<div style="font-size:11px;color:#5A6275;text-transform:uppercase;letter-spacing:.06em;">Car-level failures</div>
		<div style="font-size:22px;font-weight:600;color:#00529B;"><?php echo $grandTotal; ?></div>
		<div style="font-size:11px;color:#5A6275;">from <?php echo $distinctIncidents; ?> incident<?php echo $distinctIncidents==1?'':'s'; ?></div>
	</div>
	<div style="flex:1;min-width:150px;border:1px solid #E5DECC;border-radius:6px;padding:10px 12px;background:#FBFAF6;">
		<div style="font-size:11px;color:#5A6275;text-transform:uppercase;letter-spacing:.06em;">Equipment types affected</div>
		<div style="font-size:22px;font-weight:600;color:#00529B;"><?php echo $activeEquipt; ?></div>
		<div style="font-size:11px;color:#5A6275;">of <?php echo count($equipt); ?> tracked</div>
	</div>
<?php $peakClickable = ($peakId > 0); /* @equiptpanel -- no peak, no action */ ?>
	<div class="<?php echo $peakClickable ? 'kpi-tile--link' : ''; ?>" style="flex:1;min-width:150px;border:1px solid #E5DECC;border-radius:6px;padding:10px 12px;background:#FBFAF6;"<?php if($peakClickable){ ?> role="button" tabindex="0" aria-label="Open the car breakdown for <?php echo htmlspecialchars($peakName); ?>" onclick="openEquiptPanel(<?php echo htmlspecialchars(json_encode($panelFrom), ENT_QUOTES); ?>,<?php echo htmlspecialchars(json_encode($panelTo), ENT_QUOTES); ?>,'<?php echo (int)$peakId; ?>',<?php echo htmlspecialchars(json_encode($peakName), ENT_QUOTES); ?>,'<?php echo (int)$panelCar; ?>')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();this.click();}"<?php } ?>>
		<div style="font-size:11px;color:#5A6275;text-transform:uppercase;letter-spacing:.06em;">Equipment with the highest number of faults</div>
		<div style="font-size:15px;font-weight:600;color:#7A1F1F;line-height:1.3;margin-top:3px;"><?php echo $peakName!=='' ? htmlspecialchars($peakName) : '&mdash;'; ?></div>
		<div style="font-size:11px;color:#5A6275;"><?php echo $peakTotal; ?> failure<?php echo $peakTotal==1?'':'s'; ?></div>
<?php if($peakClickable){ ?>		<div class="kpi-hint">View car breakdown &rarr;</div>
<?php } ?>	</div>
<?php
	/* @monthpanel -- The bucket keys here are composite (YYYYMM or YYYYMMDD),
	   built that way so two Marches from different years cannot merge. Split
	   them back out for the panel, which takes a plain month or day list plus
	   the year they belong to.
	   Ties are kept, so the list can name several -- sending only the first
	   would drop half of what the tile says. */
	$peakClickableBucket = (count($peakBucketIdx) > 0);
	$pbYear = 0; $pbMonth = 0; $pbCsv = '';
	if($peakClickableBucket){
		$parts = array();
		foreach($peakBucketIdx as $bi){
			$bk = $buckets[$bi]['key'];
			if($isDayView){ $pbYear=(int)floor($bk/10000); $pbMonth=(int)floor(($bk%10000)/100); $parts[]=$bk%100; }
			else          { $pbYear=(int)floor($bk/100);   $parts[]=$bk%100; }
		}
		$pbCsv = implode(',', $parts);
		/* Buckets spanning more than one year cannot be expressed as one
		   year + a month list, so those hand over the date range instead. */
		$pbYears = array();
		foreach($peakBucketIdx as $bi){
			$bk = $buckets[$bi]['key'];
			$pbYears[$isDayView ? (int)floor($bk/10000) : (int)floor($bk/100)] = true;
		}
		$pbMultiYear = (count($pbYears) > 1);
	}
	else { $pbMultiYear = false; }
?>
	<div class="<?php echo $peakClickableBucket ? 'kpi-tile--link' : ''; ?>" style="flex:1;min-width:150px;border:1px solid #E5DECC;border-radius:6px;padding:10px 12px;background:#FBFAF6;"<?php if($peakClickableBucket){ ?> role="button" tabindex="0" aria-label="Open the breakdown for <?php echo htmlspecialchars(strip_tags(str_replace('&amp;','and',$peakBucketLabel))); ?>" onclick="openMonthPanel('<?php echo (int)$pbYear; ?>','<?php echo $pbCsv; ?>','<?php echo (int)$pbMonth; ?>',<?php echo htmlspecialchars(json_encode(strip_tags(str_replace('&amp;','and',$peakBucketLabel))), ENT_QUOTES); ?>,'<?php echo $pbMultiYear ? 1 : 0; ?>')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();this.click();}"<?php } ?>>
		<div style="font-size:11px;color:#5A6275;text-transform:uppercase;letter-spacing:.06em;"><?php echo $isDayView ? 'Day' : 'Month'; ?> with the Most Failures</div>
		<div style="font-size:22px;font-weight:600;color:#00529B;"><?php echo $peakBucketLabel; ?></div>
		<div style="font-size:11px;color:#5A6275;"><?php echo $peakBucketSub; ?></div>
<?php if($peakClickableBucket){ ?>		<div class="kpi-hint">View <?php echo $isDayView ? 'day' : 'month'; ?> breakdown &rarr;</div>
<?php } ?>	</div>
</div>

<?php
/* @insight -- COMPUTED here, PRINTED further down.
   The panel itself still belongs beside the table: an interpretation a
   reader cannot check against the figures is harder to trust. But the
   conclusion has to clear the fold, and the band below needs the
   findings, which are only available once the aggregation above has
   run. So the block runs here into a buffer and prints unchanged in its
   old position, with just the bottom line hoisted into the tile strip.
   Buffering it verbatim rather than refactoring it keeps its raw HTML,
   its <script> and its echoes exactly as they were. */
$issPanelHtml = ''; $issBandHtml = '';
ob_start();
/* @insight -- The panel sits directly under the table and above the counting
   note, so a reader meets the interpretation while the figures are still on
   screen. Rendered only when the module is present; a missing file leaves the
   page exactly as it was. */
if(function_exists('iss_insight')){

	/* Row series in the order the table renders them. Coverage-gap buckets
	   are handed over as their own list rather than as zeros -- the analysis
	   layer masks them, and a gap read as zero is what makes a recording stop
	   look like an improvement. */
	$issRows = array();
	foreach($equipt as $e){
		$key = "Equipt_".$e['id'];
		$vals = array();
		foreach($monthKeys as $mk){
			$vals[] = isset($equipt_count[$key]["Month_".$mk]) ? (int)$equipt_count[$key]["Month_".$mk] : 0;
		}
		$issRows[] = array('key'=>$e['id'], 'label'=>$e['equipment'],
		                   'values'=>$vals, 'total'=>(int)$e['total']);
	}

	/* Coverage is recorded per month. In day view the buckets are Ymd and
	   carry no 'ym', so the month is rebuilt from the bucket's own y/m --
	   otherwise every day in a missing month silently reads as covered. */
	$issUncovered = array();
	foreach($buckets as $bi => $b){
		$ym = isset($b['ym']) ? $b['ym']
		    : ((isset($b['y']) && isset($b['m'])) ? $b['y'].'-'.$b['m'] : '');
		if($ym !== '' && ccsMonthStatus($coverage, $ym) === 'missing'){
			/* Must be the LABEL, not the key: the mask is matched against
			   the bucket list, and a key here would never match a label
			   there -- the gap would silently stay unmasked. */
			$issUncovered[] = $issBucketLabels[$bi];
		}
	}

	$issBucketLabels = array();
	foreach($buckets as $b){
		if($isDayView && isset($b['y']) && isset($b['m'])){
			$issBucketLabels[] = date('j M Y', strtotime($b['y'].'-'.$b['m'].'-'.$b['label']));
		} elseif(isset($b['y']) && isset($b['m'])){
			$issBucketLabels[] = date('F Y', strtotime($b['y'].'-'.$b['m'].'-01'));
		} else {
			$issBucketLabels[] = (string)$b['key'];
		}
	}

	$issFilters = array();
	if($level > 0)      $issFilters['level'] = $level;
	if($carFilter > 0)  $issFilters['car']   = 'Car '.$carFilter;

	$issCtx = array(
		'schema' => 'iss.report.v1',
		'report' => array(
			'id'    => 'statistics_report_modified',
			'title' => 'Equipment Failures by Type',
			/* The unit the whole page is built on. Stating it is not a
			   formality: the incident histories count one row per incident
			   and would otherwise be narrated with the same word. */
			'unit'  => 'car-level failures',
		),
		'period'  => array('from'=>$start_date, 'to'=>$end_date,
		                   'grain'=>$bucketWord),
		'filters' => $issFilters,
		'dimensions' => array('row'=>array('key'=>'equipt','label'=>'Equipment'),
		                      'col'=>array('key'=>$bucketWord,
		                                   'label'=>$isDayView ? 'Day' : 'Month')),
		/* Readable bucket names, not the composite Ymd/Ym sort keys. The
		   narrative quotes these verbatim, and "spiked in 202504" is not a
		   sentence anyone should have to read in a printed report. The year
		   is always included even in single-year view -- the printout can
		   outlive the screen it was taken from. */
		'buckets'  => $issBucketLabels,
		'rows'     => $issRows,
		'coverage' => array('uncovered_buckets'=>$issUncovered),
		'totals'   => array('by_bucket'=>$monthTotals, 'grand'=>(int)$grandTotal),
	);
	if(count($issCross))   $issCtx['crosstab'] = $issCross;
	if(count($issHistory)) $issCtx['history']  = $issHistory;
	if(count($issEvents))  $issCtx['events']   = $issEvents;

	$issCtxN = iss_insight_normalize($issCtx);
	$issF    = iss_insight_findings($issCtxN);
	if(function_exists('iss_insight_findings_advanced')){
		$issF = iss_insight_findings_advanced($issCtx, $issCtxN, $issF);
	}
	$issOut = iss_insight_render_offline($issCtxN, $issF);

	echo iss_insight_css();
	if(function_exists('iss_insight_html_dual')){
		/* Both readings are computed here and shipped together, so switching
		   is instant and works with no provider configured. The reader's
		   choice persists across pages via localStorage; the print stylesheet
		   prints whichever is on screen. */
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
		   . iss_insight_html_dual($issCtxN, $issF, $issOut)
		   . '</div>';
	} else {
		echo '<div id="issInsight">'.iss_insight_html($issOut).'</div>';
	}

	/* The narrated version arrives asynchronously, if a provider is set. The
	   deterministic block above is already on screen by then, so a slow or
	   unreachable model costs the page nothing. */
	$issCfg = iss_insight_config();
	if(!empty($issCfg['enabled']) && $issCfg['provider'] !== 'none'
	   && file_exists(dirname(__FILE__)."/insight_ajax.php")){
		$issKey = iss_insight_stash($issCtx);
?>
<script>
(function(){
	var box = document.getElementById('issInsight');
	if(!box || !window.XMLHttpRequest) return;
	var x = new XMLHttpRequest();
	x.open('GET','insight_ajax.php?k=<?php echo $issKey; ?>',true);
	x.onreadystatechange = function(){
		/* Only replace on a non-empty 200. Anything else and the reader keeps
		   the computed analysis, which was never a placeholder. */
		if(x.readyState===4 && x.status===200 && x.responseText
		   && x.responseText.indexOf('ins-block') !== -1){
			box.innerHTML = x.responseText;
			/* Re-apply the reader's Executive/Technical choice: the
			   replacement markup carries no script of its own. */
			if(window.issInsightApplyView) window.issInsightApplyView();
		}
	};
	x.send();
})();
</script>
<?php
	}
}
$issPanelHtml = ob_get_clean();
if(isset($issF) && isset($issCtxN) && function_exists('iss_insight_summary_band')){
	if(function_exists('iss_insight_band_css')) $issBandHtml = iss_insight_band_css();
	$issBandHtml .= iss_insight_summary_band($issCtxN, $issF, "issInsight");
}
?>
<?php echo $issBandHtml; ?>
<div style="margin-bottom:14px;">
	<button type="button" onclick="srmPrintReport()" style="padding:6px 14px;border:1px solid #00529B;background:#00529B;color:#fff;border-radius:4px;cursor:pointer;font-size:13px;">Print report</button>
</div>

<div style="display:flex;flex-wrap:wrap;gap:16px;margin-bottom:16px;">
	<div><canvas id="srmByEquipt" width="340" height="230"></canvas></div>
	<div><canvas id="srmByMonth" width="340" height="200"></canvas></div>
</div>

<div class="stat-legend" style="margin:0 0 8px;">
	<span><span class="swatch" style="background:#00529B;"></span>Click an equipment name to see which cars had this failure</span>
	<span><span class="swatch" style="background:#FDB813;"></span>Click a monthly count to see that month's incident list</span>
	<span><span class="swatch" style="background:#F9D6D6; border:1px solid #E3A9A9;"></span>Highlighted row = among the highest incident counts this period (&ge;60% of the peak)</span>
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
table.ccs-rows-none tr.ccs-nonzero{display:none;}
@media print{.ccs-rowtabs .ccs-seg{display:none;}}
</style>
<?php if($zeroRows > 0){ ?>
<div class="ccs-rowtabs" id="srmMatrix-rowtabs">
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
   their own tab, because "which equipment types have nothing recorded?" is a real
   question to put to the depot's service log. The count is stated in every
   view so two printouts of the same period always explain their row count.

   Binding is DEFERRED. This control is printed above `echo $tableHtml`, so at
   parse time the table it governs does not exist yet -- binding inline made
   getElementById return null and the whole thing bailed silently, leaving
   tabs that rendered but did nothing. Every bail path now names itself under
   [ccs-rowtabs] rather than returning quietly. */
(function(){
	var TAG="[ccs-rowtabs srmMatrix]", bound=false;
	function init(){
		if(bound) return;
		var t=document.getElementById("srmMatrix");
		if(!t){ if(window.console&&console.info) console.info(TAG,"table not in the DOM yet"); return; }
		var bar=document.getElementById("srmMatrix-rowtabs");
		if(!bar){ if(window.console&&console.info) console.info(TAG,"control markup missing"); return; }
		var btns=bar.getElementsByTagName("button");
		if(!btns.length){ if(window.console&&console.info) console.info(TAG,"no buttons found"); return; }
		var lbl=bar.getElementsByTagName("span")[1];
		if(!lbl){ if(window.console&&console.info) console.info(TAG,"count label missing"); return; }
		bound=true;
		t.setAttribute("data-ccs-rowtabs","bound");

		var zero=<?php echo (int)$zeroRows; ?>, total=<?php echo (int)$totalRows; ?>,
		    KEY="ccsRowView-srmMatrix";
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
				"Showing "+shown+" of "+total+" equipment types. "+zero+" have no records this period.";
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
   srmPrintReport() below. Emitted after the panel so the element it clones is
   already in the document; the functions themselves are not called until the
   button is pressed, so ordering beyond that does not matter. */
if(function_exists('iss_insight_print_js')) echo iss_insight_print_js();
?>

<div style="font-size:12px;color:#5A6275;margin-top:8px;">
	Figures count <b>car-level failures</b>: an incident affecting three cars counts once against each car, so <?php echo $distinctIncidents; ?> incident<?php echo $distinctIncidents==1?'':'s'; ?> produce <?php echo $grandTotal; ?> car-level failure<?php echo $grandTotal==1?'':'s'; ?>. This is the same basis the per-car reports use, so they reconcile; the incident history logs count one row per incident and show the smaller figure.
<?php /* @roster -- The scope, stated rather than left to be inferred. Printed
         only when something was actually excluded, so a clean period does not
         carry a caveat about nothing. */
if($excludedPairs > 0){ ?>
	<br><span style="color:#7A1F1F;">This report covers <?php echo count($rsEquiptIds); ?> rolling-stock equipment types. A further <?php echo $excludedPairs; ?> car-level failure<?php echo $excludedPairs==1?'':'s'; ?> in this period <?php echo $excludedPairs==1?'was':'were'; ?> recorded against equipment outside that list, or with no equipment recorded, and <?php echo $excludedPairs==1?'is':'are'; ?> not counted above.</span>
<?php } ?>
</div>

<script>
var srmEquiptTotals = <?php echo json_encode($equiptTotals); ?>;
/* @sort -- Sorting reorders the DOM, and the printout clones the table's
   outerHTML from the LIVE DOM rather than the PHP-generated order, so whatever
   is on screen is what prints. No sort state crosses into the print handler.

   Against the table directly rather than through DataTables: the .datatable2
   auto-init may or may not run depending on the template, and a sort that
   silently does nothing on some pages is worse than none.

   "All equipment" already sits in <tfoot> here, so unlike the car report it
   needs no moving -- a tfoot row is not part of tBodies and cannot be caught
   up in the sort. */
(function(){
	var table = document.getElementById('srmMatrix');
	if(!table || !table.tBodies[0] || !table.tHead) return;
	var tbody = table.tBodies[0];

	/* Per cell, not per column: a coverage-gap cell carries a marker rather
	   than a number and must not read as 0 -- that would sort "no records" in
	   among the genuinely quiet months. Gaps sort last in BOTH directions,
	   because absent data is not a small value. The equipment column is a th
	   holding a link, so textContent is what gets compared there. */
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
		th.setAttribute('aria-label', 'Sort by ' + (th.textContent||'').replace(/\s+/g,' ').trim());
		function go(){
			var cur = th.getAttribute('aria-sort');
			/* Count columns open DESCENDING -- on a failures table the question
			   is almost always "which is worst". The Equipment column opens
			   ascending, so it sorts A-Z. */
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

var srmMonthSeries  = <?php echo json_encode($monthSeries); ?>;
var srmPeriod       = <?php echo json_encode(isset($period) ? $period : ''); ?>;
var srmLevel        = <?php echo json_encode($level); ?>;
var srmGrandTotal   = <?php echo (int)$grandTotal; ?>;
var srmIncidents    = <?php echo (int)$distinctIncidents; ?>;
var srmActive       = <?php echo (int)$activeEquipt; ?>;
var srmPeakName     = <?php echo json_encode($peakName); ?>;
var srmUncovered    = <?php echo json_encode($uncoveredMonths); ?>;
var srmCoverageNote = <?php echo json_encode($coverageNote); ?>;
var srmBucketWord   = <?php echo json_encode($bucketWord); ?>;   /* @buckets */
var srmPanelFrom    = <?php echo json_encode($panelFrom); ?>;    /* @monthpanel */
var srmPanelTo      = <?php echo json_encode($panelTo); ?>;
var srmPanelCar     = <?php echo json_encode($panelCar ? (string)$panelCar : ''); ?>;
var srmPanelLevel   = <?php echo json_encode($level !== '' ? (string)$level : ''); ?>;   /* @levelfilter */
/* @roster -- The panel inherits this report's population the same way it
   inherits level and car. Without it the day tile ranked days over the 27
   rostered types while the panel it opens ranked the same days over every
   equipment type, so a "4-way tie at 3" opened onto 7, 4, 3, 3.
   month_stats.php applies it only when sent, so car_statistics_report.php --
   which counts every incident_cars row by design and sends nothing -- keeps
   reconciling exactly as before. */
var srmPanelEquipts = <?php echo json_encode(implode(',', $rsEquiptIds)); ?>;
</script>
</div>
<br>
<br>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
(function(){
	var ink='#1A2238', muted='#5A6275', grid='rgba(137,135,129,0.20)';
	var TOP_EQ = 8;

	function valueLabels(){
		return { id:'srmLabels', afterDatasetsDraw:function(chart){
			var ctx=chart.ctx, meta=chart.getDatasetMeta(0);
			ctx.save(); ctx.font='11px Arial, sans-serif'; ctx.fillStyle=ink;
			ctx.textBaseline='middle'; ctx.textAlign='left';
			meta.data.forEach(function(bar,i){ ctx.fillText(chart.data.datasets[0].data[i], bar.x+6, bar.y); });
			ctx.restore();
		}};
	}
	function shorten(t){ return t.length > 20 ? t.slice(0,19)+'\u2026' : t; }

	var top = srmEquiptTotals.slice(0, TOP_EQ);
	var tail = srmEquiptTotals.slice(TOP_EQ);
	var tailTotal = tail.reduce(function(s,r){ return s+r[1]; }, 0);

	if(top.length){
		new Chart(document.getElementById('srmByEquipt'), {
			type:'bar',
			data:{ labels: top.map(function(r){ return shorten(r[0]); }),
			       datasets:[{ data: top.map(function(r){ return r[1]; }),
			                   backgroundColor: top.map(function(r){ return r[0]===srmPeakName ? '#A32D2D' : '#00529B'; }),
			                   borderRadius:3, categoryPercentage:0.62, barPercentage:0.9 }] },
			options:{ indexAxis:'y', responsive:false, animation:false,
				layout:{ padding:{ right:22, bottom: tail.length ? 18 : 4 } },
				plugins:{
					title:{ display:true, text:'Car-level failures by equipment'+(tail.length?' (top '+TOP_EQ+')':''), color:ink, font:{size:11,weight:'normal'}, padding:{bottom:8} },
					legend:{ display:false },
					tooltip:{ callbacks:{ title:function(items){ return top[items[0].dataIndex][0]; },
					                      label:function(c){ return c.parsed.x+' failures'; } } }
				},
				scales:{ x:{ ticks:{ color:muted, precision:0, font:{size:10} }, grid:{ color:grid } },
				         y:{ ticks:{ color:ink, font:{size:10} }, grid:{ display:false } } }
			},
			plugins:[ valueLabels(), { id:'srmTail', afterDraw:function(chart){
				if(!tail.length) return;
				var ctx=chart.ctx, area=chart.chartArea;
				ctx.save(); ctx.font='10px Arial, sans-serif'; ctx.fillStyle=muted;
				ctx.textAlign='left'; ctx.textBaseline='top';
				var y=chart.height-14;
				ctx.strokeStyle=grid; ctx.lineWidth=1;
				ctx.beginPath(); ctx.moveTo(area.left,y-5); ctx.lineTo(chart.width-8,y-5); ctx.stroke();
				ctx.fillText('+ '+tailTotal+' across '+tail.length+' further equipment type'+(tail.length===1?'':'s'), area.left, y);
				ctx.restore();
			}}]
		});
	}
	else{
		var cv=document.getElementById('srmByEquipt'), c=cv.getContext('2d');
		c.textBaseline='middle'; c.textAlign='left';
		c.font='11px Arial, sans-serif'; c.fillStyle=ink;
		c.fillText('Car-level failures by equipment', 0, 9);
		c.font='10px Arial, sans-serif'; c.fillStyle=muted;
		c.fillText('No failures recorded for this range.', 0, 34);
	}

	new Chart(document.getElementById('srmByMonth'), {
		type:'bar',
		data:{ labels: srmMonthSeries.map(function(r){ return r[0]; }),
		       datasets:[{ data: srmMonthSeries.map(function(r){ return r[1]; }), backgroundColor:'#00529B', borderRadius:3 }] },
		options:{ responsive:false, animation:false,
			plugins:{ title:{ display:true, text:'Car-level failures by '+srmBucketWord+', all equipment', color:ink, font:{size:11,weight:'normal'}, padding:{bottom:6} }, legend:{ display:false } },
			scales:{ x:{ ticks:{ color:muted, font:{size:9}, maxRotation:45 }, grid:{ display:false } },
			         y:{ ticks:{ color:muted, precision:0, font:{size:10} }, grid:{ color:grid } } }
		}
	});

	window.srmPrintReport = function(){
		var imgEq    = document.getElementById('srmByEquipt').toDataURL('image/png');
		var imgMonth = document.getElementById('srmByMonth').toDataURL('image/png');
		var tbl = document.querySelector('.ccs-panel-body table');
		var tableHtml = tbl ? tbl.outerHTML : '';
		/* @insight -- read at press time, not from $issPanelHtml: if the model
		   refinement XHR has landed, the DOM is the newer of the two. */
		var srmIns  = (typeof issInsightPrintBlock === 'function') ? issInsightPrintBlock('issInsight') : '';
		var srmLead = (typeof issInsightPrintLead  === 'function') ? issInsightPrintLead('issInsight')  : '';
		var srmRows = tbl ? tbl.getElementsByTagName('tr').length : 0;
		var srmBrk  = (srmRows > 14) ? ' brk' : '';
		function esc(x){ return String(x).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

		var win = window.open('', '_blank');
		win.document.write(
			'<html><head><title>Equipment Failures Summary</title>' +
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
				   near-empty second sheet. srmPrintReport decides and adds .brk. */
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
				// cell is also a <th>, so an unscoped th rule would paint the whole
				// Equipment column solid navy.
				'thead th{ background:#1f4e79; color:#fff; text-align:center; padding:4px 3px; font-size:8px; font-weight:600;' +
					' text-transform:uppercase; letter-spacing:.03em; border:1px solid #1f4e79; }' +
				'tbody th, tfoot th{ background:#F1EFE8; color:#1a1a1a; text-align:left; padding:3px 5px; font-size:8.5px;' +
					' font-weight:600; border:1px solid #e5e7eb; }' +
				'td{ padding:3px; border:1px solid #e5e7eb; text-align:center; }' +
				'tfoot td{ background:#F1EFE8; font-weight:700; }' +
				'tr{ page-break-inside:avoid; }' +
				// !important because the equipment and month links carry inline
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
				'<h1 class="rpt-title">Equipment Failures Summary</h1>' +
				'<p class="rpt-subject">'+esc(srmPeriod)+'</p>' +
				(srmLead ? '<p class="rpt-lead">'+esc(srmLead)+'</p>' : '') +
			'</div>' +
			'<div class="rpt-meta">' +
				'<span><b>Period:</b> '+esc(srmPeriod)+'</span>' +
				'<span><b>Level:</b> '+esc(srmLevel)+'</span>' +
				'<span><b>Car-level failures:</b> '+srmGrandTotal+'</span>' +
				'<span><b>From incidents:</b> '+srmIncidents+'</span>' +
				'<span><b>Equipment affected:</b> '+srmActive+'</span>' +
				'<span><b>Generated:</b> <?php echo date("d M Y, H:i"); ?></span>' +
			'</div>' +
			'<h2 class="sec">Summary</h2>' +
			'<div class="charts">' +
				'<div class="chart"><img src="'+imgEq+'"><div class="cap">Figure 1 &mdash; Car-level failures by equipment</div></div>' +
				'<div class="chart"><img src="'+imgMonth+'"><div class="cap">Figure 2 &mdash; Car-level failures by '+srmBucketWord+', all equipment</div></div>' +
				srmIns +
				'<p class="note">Figures count car-level failures: an incident affecting several cars counts once against each car, so '+srmIncidents+' incidents produce '+srmGrandTotal+' car-level failures. This matches the per-car reports; the incident history logs count one row per incident and show the smaller figure. Shaded rows are equipment at or above 60% of the highest total.' + (function(){var t=document.getElementById("srmMatrix");if(!t||!t.getElementsByClassName) return '';var z=t.getElementsByClassName('ccs-zero').length;if(!z) return '';var c=t.className;if(c.indexOf('ccs-rows-none')!==-1) return ' This view lists ONLY the '+z+' equipment types with no records this period; the totals above cover the full roster.';if(c.indexOf('ccs-rows-with')!==-1) return ' '+z+' equipment types with no records this period are omitted from this table; they remain included in the totals above.';return ' Includes '+z+' equipment types with no records this period.';})() + '</p>' +
			'</div>' +
			'<h2 class="sec'+srmBrk+'">Monthly Breakdown by Equipment</h2>' +
			tableHtml +
			'<div class="rpt-foot">MRT-3 Information Sharing System &middot; generated <?php echo date("d M Y, H:i"); ?> &middot; for internal operational use' +
				(srmIns ? ' &middot; analysis computed from the figures in this report; wording generated automatically' : '') + '</div>' +
			'</body></html>'
		);
		win.document.close();
		win.focus();
		win.onload = function(){ setTimeout(function(){ win.print(); }, 250); };
	};
})();
</script>
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

/* @monthpanel -- Reuses this page's existing panel, pointed at month_stats.php
   with by=equipt: an equipment report drills into equipment.
   multiYear means the tie spans a year boundary, which one year plus a month
   list cannot express -- those fall back to the report's own date range, which
   is always well defined. Any car filter travels too, so the panel shows the
   same slice the table does. */
function openMonthPanel(year, csv, dayMonth, title, multiYear){
	title = title || 'Period breakdown';
	var q = "by=equipt&title=" + encodeURIComponent(title);

	if(multiYear === '1' || multiYear === 1){
		q += "&sd=" + encodeURIComponent(srmPanelFrom) + "&ed=" + encodeURIComponent(srmPanelTo);
	}
	else {
		q += "&year=" + encodeURIComponent(year)
		   + (dayMonth && dayMonth !== '0'
		       ? "&month=" + encodeURIComponent(dayMonth) + "&days=" + encodeURIComponent(csv)
		       : "&months=" + encodeURIComponent(csv));
	}
	if(srmPanelCar) q += "&car=" + encodeURIComponent(srmPanelCar);
	/* @levelfilter -- openEquiptPanel passes the report's level filter through;
	   this one did not, so the two panels on the same page disagreed about
	   whether a filter applies. month_stats.php reads level= either way.

	   car_statistics_report.php's own openMonthPanel sends no level, correctly:
	   that report has no level filter at all, so there is nothing to inherit.
	   The tiles inside the panel still work there -- the filter is simply
	   panel-local rather than inherited. */
	if(srmPanelLevel) q += "&level=" + encodeURIComponent(srmPanelLevel);
	/* @roster -- see srmPanelEquipts. Sent on the period panel only: the
	   equipment panel below is already narrowed to ONE rostered type, so a
	   roster list there would be redundant. */
	if(srmPanelEquipts) q += "&equipts=" + encodeURIComponent(srmPanelEquipts);

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

function openEquiptPanel(sd, ed, equipt, title, car){
	/* @range -- Sends the From-To range as sd/ed, which equipt_stats.php takes
	   in preference to year/month. It picks its own grain from the span: inside
	   one calendar month it breaks down by day, otherwise by month, and month
	   labels carry the year when the span crosses one.

	   Values are encoded — an equipment name carries spaces and can carry an
	   apostrophe or ampersand. */
	title = title || "Equipment Breakdown";

	var q = "equipt=" + encodeURIComponent(equipt)
	      + "&title="  + encodeURIComponent(title);
	if(sd && ed){ q += "&sd=" + encodeURIComponent(sd) + "&ed=" + encodeURIComponent(ed); }
	if(car){ q += "&car=" + encodeURIComponent(car); }   /* @carfilter */
	/* @levelfilter -- the panel inherits this report's level filter. Without
	   it, filtering the report to level 2 and opening a panel gave two
	   different answers to the same question, one click apart. */
	if(srmPanelLevel){ q += "&level=" + encodeURIComponent(srmPanelLevel); }
	q+="&tt=2a7b85131d93ffbaacc73f7ff024b55a";

	document.getElementById('ir-panel-title').textContent=title;
	document.getElementById('irFallbackLink').href="equipt_stats.php?"+q; /* no embed=1: full standalone page */
	var frame=document.getElementById('irFrame');
	frame.classList.remove('ready');
	document.getElementById('irLoading').classList.remove('hidden');
	document.getElementById('irFallback').classList.add('hidden');
	clearTimeout(irLoadTimer);   /* was commented out: reopening left the previous
	                                timer running, which could flash the timeout
	                                fallback over an already-loaded frame */
	irExpectingLoad=true;
	frame.src="equipt_stats.php?"+q+"&embed=1";
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
		<h3 id="ir-panel-title">Equipment Breakdown</h3>
		<button type="button" class="ta-panel-close" onclick="closeIncidentPanel()" aria-label="Close">&times;</button>
	</div>
	<div class="ta-panel-body ta-panel-body--ir">
		<iframe id="irFrame" src="about:blank" title="Incident Report" onload="irFrameLoaded()"></iframe>
		<div class="ir-loading" id="irLoading">
			<div class="ir-spinner"></div>
			<span>Loading car breakdown&hellip;</span>
		</div>
		<div class="ir-fallback hidden" id="irFallback">
			<strong>This is taking longer than expected.</strong>
			<p>The page may be blocked from loading inside this panel.<br>You can open it directly instead:</p>
			<a href="#" id="irFallbackLink" target="_blank" rel="noopener">Open the breakdown in a new tab &rarr;</a>
		</div>
	</div>
</div>

</body>
</html>