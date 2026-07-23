<?php
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
.stat-toolbar select, .stat-toolbar input[type=text] {
	height:26px; border:1px solid rgba(255,255,255,.5); border-radius:4px;
	background:#FFFFFF; color:#1A2238; padding:0 8px; font-size:12px;
}
.stat-toolbar input[type=submit] {
	height:28px; border:none; border-radius:4px; background:#FDB813;
	color:#3A2D00; font-weight:700; font-size:12px; padding:0 14px; cursor:pointer;
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
.stat_hover:hover {
	background-color:#FFF1CC;
	text-decoration:underline;
	font-weight:bold;
}
</style>
<?php include("history_theme.php"); ?>

<link rel="stylesheet" href="jquery-ui-themes-1.11.1/themes/smoothness/jquery-ui.css" />
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

$(function() {
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
<div class="ccs-page">

<?php
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");

$coverage = ccsLoadCoverage($db);
$sql="select * from equipment where id in ('114','102','110','11','113','104','108','109','103','124','67','111','112','105','81','118','119','64','115','89','120','123','121','116','2','122','117','105','81','118','119','64','115','89','120','123','121','116','2','122','117') order by equipment_name";

//$sql="select * from equipment where type='RS' order by equipment_name";

$rs=$db->query($sql);

$nm=$rs->num_rows;

if(isset($_POST['search_date2'])){
//$year=date("Y",strtotime($_POST['search_date2']));

//$start_date=date("Y-m-d",strtotime($_POST['search_date2']));



$start_date=date("Y-m-d",strtotime($_POST['search_date2']));


$end_date=date("Y-m-d",strtotime($_POST['search_date']));


$dates=explode(" - ",$_POST['search_date2']);
	$period=date("F d Y", strtotime($start_date))." - ".date("F d Y", strtotime($end_date));


}
else {
//$start_date=date("Y-m-d",strtotime("first day of this month"));

$start_date=date("Y-m-d",(date("Y")."-01-01"));

$end_date=date("Y-m-d",strtotime("last day of this month"));
	$period=date("F d", strtotime($start_date))." - ".date("F d Y", strtotime($end_date));

	$level=2;
}


if(isset($_POST['level'])){
//	$year=$_POST['year'];
	$level=$_POST['level'];
}
else {
//	$year=date("Y");
	$level="2";
}
?>
<!-- <form action='statistics_report.php' method='post'> -->
<div class="ccs-header">

<h1>Equipment Failures Per Year</h1>
<div class='sub'><?php echo $period; echo " / ";echo " Level ".$level;?></div>
</div>
<div class="ccs-panel">
<div class="ccs-panel-head">
<table>

<form action='statistics_report_modified.php' method='post'>

<table cellspacing="0" cellpadding="0" class='stat-toolbar'>
<tr>
	<td style="padding:8px 14px;vertical-align:middle;white-space:nowrap;width:1%;border:none">
		<form action='statistics_report_modififed.php' method='post' >
<div width="50%" align=left>
<table>
<tr><th>Level</th>
<td>
<select name='level'>
<option <?php if($_POST['level']==1){ echo "selected"; } ?> value='1'>1</option>
<option <?php if($_POST['level']==2){ echo "selected"; } ?> value='2'>2</option>
<option <?php if($_POST['level']==3){ echo "selected"; } ?> value='3'>3</option>
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
</form>
	</td>
	
	<td style="padding:8px 14px;vertical-align:middle;text-align:right;white-space:nowrap;border:none">
	<?php
	/**

<a href='#' class="btn-generate" onclick='window.open("generate_statistics_report.php?sd=<?php echo date("Y-m-d",strtotime($_POST['search_date2'])); ?>&ed=<?php echo date("Y-m-d",strtotime($_POST['search_date'])); ?>&range=<?php echo $_POST['range']; ?>&level=<?php echo $level; ?>");'><b>Generate Printout</b></a>


	*/ ?>


	</td>
	
</tr>
</table>


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
<div class="stat-legend">
	<span><span class="swatch" style="background:#00529B;"></span>Click an equipment name to see which cars had this failure</span>
	<span><span class="swatch" style="background:#FDB813;"></span>Click a monthly count to see that month's incident list</span>
	<span><span class="swatch" style="background:#F9D6D6; border:1px solid #E3A9A9;"></span>Highlighted row = among the highest incident counts this period (&ge;60% of the peak)</span>
</div>
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






for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();

	$equipt[$i]['id']=$row['id'];
	$equipt[$i]['equipment']=$row['equipment_name'];
	for ($k=$start;$k<=$end;$k++){
		$equipt_count["Equipt_".$row['id']]["Month_".$k]=0;
		
	}
	$equipt_count["Equipt_".$row['id']]["total"]=0;

}



if(isset($_POST['level'])){
//	$year=$_POST['year'];
	$level=$_POST['level'];
}
else {
//	$year=date("Y");
	$level="2";
}

?>
<div class='ccs-panel-body'>
<?php
// The summary figures are only known once the aggregation loop inside the
// table has run, so buffer the table and emit the summary above it.
ob_start();
?>
<table class="table table-striped table-bordered bootstrap-datatable datatable2" border=1px style='border-collapse:collapse;' width=100%>
<thead>
<tr >
<th>Equipment</th>
<?php


if(isset($_POST['search_date2'])){

	
	//Convert to seconds, then convert to months
	
	
	$difference=date("m",strtotime(date("Y-m-t",strtotime($end_date)))-strtotime(date("Y-m-01",strtotime($start_date))))-1;

//	$difference2=strtotime(date("Y-m-t",strtotime($end_date)))-strtotime(date("Y-m-01",strtotime($start_date)));
//	$day=((($difference2/60*1)/60*1)/24*1)

	/* change from months to days */	

	$start=date("Ym",strtotime($start_date));
	$end=date("Ym",strtotime($end_date));
	
	
	if($_POST['range']=="yearly"){
	//	$end=12;
	}
	
//	echo date("F d, Y",strtotime($start_date."+".$day." days"));
	
}
else {
	
$difference=0;	

$start_date=date("Y-01-01");
	
$start=date("Ym",strtotime($start_date));
$end=date("Ym",strtotime($end_date));


$startM=date("m",strtotime($start_date));
$endM=date("m",strtotime($end_date));

$difference=$endM-$startM;

//	$start=1;
//	$end=12;
}
	$tag_date=date("Y-m-01",strtotime($start_date));



$limit=date("t",strtotime($tag_date));
	
	
	


for($k=0;$k<=$difference;$k++){

	
	
//	$mon=substr($k,4,2);
//	$yy=substr($k,0,4);

	
?>	
	<th>
	<?php 
	if($k==0){
		echo date("F",strtotime($start_date));
	}
	else {
	echo date("F",strtotime($tag_date."+".$k." months"));


	}
	
	?>
	</th>
	
<?php
}

$ss=$start;
$ee=$end;



//$sql="select *,count(1) as equipt_count from incident_report where level='".$level."' and incident_date between '".$start_date." 00:00:00' and '".$end_date." 23:59:59' and equipt in ('114','102','110','11','113','104','108','109','103','124','67','111','112','105','81','118','119','64','115','89','120','123','121','116','2','122','117','105','81','118','119','64','115','89','120','123','121','116','2','122','117') group by equipt";
//$rs=$db->query($sql);

//$nm=$rs->num_rows;


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




for($i=0;$i<=$difference;$i++){







/*	if($i==($ee-1)){
		$year=date("Y",strtotime($end_date));
			
	}
	else {
		$year=date("Y",strtotime($start_date));
	}
	*/
	

	if($i==0){
	$month_heading=date("F",strtotime($start_date));
	}
	else {
	
	$month_heading=date("F",strtotime($tag_date."+".$i." months"));
	}


	if($i==0){
	$date_limit=date("t",strtotime($end_date));
	}
	else {
	

	
	$date_limit=date("t",strtotime($tag_date."+".$i." months"));

	}





	if($i==0){
		

	$yy=date("Y");

	$mon=date("m",strtotime($start_date));
	
	$yy2=date("Y",strtotime($start_date));

	$mon2=date("m",strtotime($start_date));
	
		
	}
	else {
	$yy=date("Y",strtotime($tag_date."+".$i." months"));

	$mon=date("m",strtotime($tag_date."+".$i." months"));
	

	$yy2=date("Y",strtotime($tag_date."+".$i." months"));

	$mon2=date("m",strtotime($tag_date."+".$i." months"));

	$fn=date("F",strtotime($tag_date."+".$i." months"));
	
	
	}
	$label=$yy.$mon;



	$start_date1=date("Y-m-d",strtotime($yy."-".$mon."-01"));
	$end_date1=date("Y-m-d",strtotime($yy2."-".$mon2."-".$date_limit));
	
	
	if(isset($_POST['level'])){
		$level=$_POST['level'];
	}
	else {
		$level=2;
	}
	
	
	
	// Counts INCIDENT-CAR PAIRS, not incidents: the incident_cars join means an
	// incident affecting three cars contributes three. This is what makes the
	// figures here reconcile with equipment_cars_stats.php, which has always
	// counted per car. Without the join this page counted one per incident and
	// the two reports could never agree.
	$sql="select incident_report.equipt as equipt, count(1) as equipt_count
	       from incident_report
	       inner join incident_cars on incident_report.id=incident_cars.incident_id
	       where level='".$level."' and incident_date between '".$start_date1." 00:00:00' and '".$end_date1." 23:59:59'
	         and incident_report.equipt in ('114','102','110','11','113','104','108','109','103','124','67','111','112','105','81','118','119','64','115','89','120','123','121','116','2','122','117','105','81','118','119','64','115','89','120','123','121','116','2','122','117')
	       group by incident_report.equipt";
	if($i==1){
	}
	$rs=$db->query($sql);
	$nm=$rs->num_rows;
	
	for($k=0;$k<$nm;$k++){
		
		$row=$rs->fetch_assoc();
		
		$equipt_count["Equipt_".$row['equipt']]["Month_".($label*1)]+=$row['equipt_count'];
		$equipt_count["Equipt_".$row['equipt']]["total"]+=$row['equipt_count'];
		
		
	}


	// Same pair-counting rule for the external defect rows.
	$sql="select is_external.incident_defects.equipt_id as equipt_id, count(1) as equipt_count
	       from incident_report
	       inner join is_external.incident_defects on incident_report.id=is_external.incident_defects.incident_id
	       inner join incident_cars on incident_report.id=incident_cars.incident_id
	       where level='".$level."' and incident_date between '".$start_date1." 00:00:00' and '".$end_date1." 23:59:59'
	         and is_external.incident_defects.equipt_id in ('114','102','110','11','113','104','108','109','103','124','67','111','112','105','81','118','119','64','115','89','120','123','121','116','2','122','117','105','81','118','119','64','115','89','120','123','121','116','2','122','117')
	       group by is_external.incident_defects.equipt_id"; 
	$rs=$db->query($sql);
	$nm=$rs->num_rows;
	if($i==1){
//		echo $sql;
//		echo "<br>";
	}	
	for($k=0;$k<$nm;$k++){
		$row=$rs->fetch_assoc();
		$equipt_count["Equipt_".$row['equipt_id']]["Month_".($label*1)]+=$row['equipt_count'];
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
$monthKeys = array();
$monthNames = array();
for($k=0;$k<=$difference;$k++){
	$monthKeys[]  = (int)date("Ym", strtotime($tag_date." +".$k." months"));
	$monthNames[] = date("M y", strtotime($tag_date." +".$k." months"));
}

$grandTotal = 0;
$peakTotal  = 0;
$peakName   = '';
$activeEquipt = 0;
$monthTotals = array_fill(0, count($monthKeys), 0);

foreach($equipt as $idx => $e){
	$key = "Equipt_".$e['id'];
	$t = isset($equipt_count[$key]['total']) ? (int)$equipt_count[$key]['total'] : 0;
	$equipt[$idx]['total'] = $t;
	$grandTotal += $t;
	if($t > 0) $activeEquipt++;
	if($t > $peakTotal){ $peakTotal = $t; $peakName = $e['equipment']; }
	foreach($monthKeys as $mi => $mk){
		$monthTotals[$mi] += isset($equipt_count[$key]["Month_".$mk]) ? (int)$equipt_count[$key]["Month_".$mk] : 0;
	}
}

$flagThreshold = $peakTotal * 0.60;
$avgPerActive  = $activeEquipt ? round($grandTotal / $activeEquipt, 1) : 0;

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
$distinctIncidents = 0;
$dq = $db->query("select count(distinct incident_report.id) as c
                  from incident_report
                  inner join incident_cars on incident_report.id=incident_cars.incident_id
                  where level='".$level."'
                    and incident_date between '".$start_date." 00:00:00' and '".$end_date." 23:59:59'");
if($dq && ($dr = $dq->fetch_assoc())) $distinctIncidents = (int)$dr['c'];

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
<tr <?php echo $flagged ? "style='background-color:#F9D6D6; color:#7A1F1F;'" : ($i%2>0 ? "class='rowClass'" : ""); ?>>
	<th style="text-align:left;">
		<a href='#' style='text-decoration:none; color:#00529B; font-weight:600;' onclick='window.open("equipment_cars_stats.php?eq=<?php echo $e['id']; ?>&level=<?php echo $level; ?>&range=custom&sd=<?php echo $link_sd; ?>&ed=<?php echo $link_ed; ?>")'><?php echo htmlspecialchars($e['equipment']); ?></a>
		<?php if($flagged){ echo " <span title='At or above 60% of the highest equipment total' style='font-size:11px;'>&#9679;</span>"; } ?>
	</th>
<?php
	foreach($monthKeys as $mi => $mk){
		$v  = isset($equipt_count[$key]["Month_".$mk]) ? (int)$equipt_count[$key]["Month_".$mk] : 0;
		$yy = substr((string)$mk, 0, 4);
		$mon= substr((string)$mk, 4, 2);
?>
<?php
		if(ccsMonthStatus($coverage, $yy."-".$mon) === 'missing'){ echo ccsCoverageCell('missing'); }
		else {
?>
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:<?php echo $v>0 ? '#00529B' : '#B4B2A9'; ?>;' onclick='window.open("equipment_history.php?equipt=<?php echo $e['id']; ?>&y=<?php echo $yy; ?>&m=<?php echo $mon; ?>&level=<?php echo $level; ?>",target="_self")'><?php echo $v; ?></a></td>
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
	<div style="flex:1;min-width:150px;border:1px solid #E5DECC;border-radius:6px;padding:10px 12px;background:#FBFAF6;">
		<div style="font-size:11px;color:#5A6275;text-transform:uppercase;letter-spacing:.06em;">Highest equipment</div>
		<div style="font-size:15px;font-weight:600;color:#7A1F1F;line-height:1.3;margin-top:3px;"><?php echo $peakName!=='' ? htmlspecialchars($peakName) : '&mdash;'; ?></div>
		<div style="font-size:11px;color:#5A6275;"><?php echo $peakTotal; ?> failures</div>
	</div>
	<div style="flex:1;min-width:150px;border:1px solid #E5DECC;border-radius:6px;padding:10px 12px;background:#FBFAF6;">
		<div style="font-size:11px;color:#5A6275;text-transform:uppercase;letter-spacing:.06em;">Avg per affected type</div>
		<div style="font-size:22px;font-weight:600;color:#00529B;"><?php echo $avgPerActive; ?></div>
		<div style="font-size:11px;color:#5A6275;">excludes types with none</div>
	</div>
</div>

<div style="margin-bottom:14px;">
	<button type="button" onclick="srmPrintReport()" style="padding:6px 14px;border:1px solid #00529B;background:#00529B;color:#fff;border-radius:4px;cursor:pointer;font-size:13px;">Print report</button>
</div>

<div style="display:flex;flex-wrap:wrap;gap:16px;margin-bottom:16px;">
	<div><canvas id="srmByEquipt" width="340" height="230"></canvas></div>
	<div><canvas id="srmByMonth" width="340" height="200"></canvas></div>
</div>

<?php echo $tableHtml; ?>

<div style="font-size:12px;color:#5A6275;margin-top:8px;">
	<span style="display:inline-block;width:11px;height:11px;background:#F9D6D6;border:1px solid #7A1F1F;vertical-align:-1px;"></span>
	Shaded rows are equipment at or above 60% of the highest total (<?php echo round($flagThreshold,1); ?> failures) &mdash; the review threshold.
	<?php if($coverageNote !== ''){ ?>
	<div style="margin-bottom:6px;color:#7A1F1F;"><?php echo htmlspecialchars($coverageNote); ?></div>
	<?php } ?>
	Figures count <b>car-level failures</b>: an incident affecting three cars counts once against each car, so <?php echo $distinctIncidents; ?> incident<?php echo $distinctIncidents==1?'':'s'; ?> produce <?php echo $grandTotal; ?> car-level failure<?php echo $grandTotal==1?'':'s'; ?>. This is the same basis the per-car reports use, so they reconcile; the incident history logs count one row per incident and show the smaller figure.
	Click an equipment name for its per-car breakdown, or a monthly figure for that month's incident log.
</div>

<script>
var srmEquiptTotals = <?php echo json_encode($equiptTotals); ?>;
var srmMonthSeries  = <?php echo json_encode($monthSeries); ?>;
var srmPeriod       = <?php echo json_encode(isset($period) ? $period : ''); ?>;
var srmLevel        = <?php echo json_encode($level); ?>;
var srmGrandTotal   = <?php echo (int)$grandTotal; ?>;
var srmIncidents    = <?php echo (int)$distinctIncidents; ?>;
var srmActive       = <?php echo (int)$activeEquipt; ?>;
var srmPeakName     = <?php echo json_encode($peakName); ?>;
var srmUncovered    = <?php echo json_encode($uncoveredMonths); ?>;
var srmCoverageNote = <?php echo json_encode($coverageNote); ?>;
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
			plugins:{ title:{ display:true, text:'Car-level failures by month, all equipment', color:ink, font:{size:11,weight:'normal'}, padding:{bottom:6} }, legend:{ display:false } },
			scales:{ x:{ ticks:{ color:muted, font:{size:9}, maxRotation:45 }, grid:{ display:false } },
			         y:{ ticks:{ color:muted, precision:0, font:{size:10} }, grid:{ color:grid } } }
		}
	});

	window.srmPrintReport = function(){
		var imgEq    = document.getElementById('srmByEquipt').toDataURL('image/png');
		var imgMonth = document.getElementById('srmByMonth').toDataURL('image/png');
		var tbl = document.querySelector('.ccs-panel-body table');
		var tableHtml = tbl ? tbl.outerHTML : '';
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
				'.chart{ display:inline-block; vertical-align:top; width:40%; margin:0 2% 10px 0; page-break-inside:avoid; }' +
				'.chart img{ display:block; width:100%; height:auto; border:1px solid #e5e7eb; }' +
				'.chart .cap{ font-size:9px; color:#6b7280; margin-top:3px; }' +
				'.note{ font-size:9px; color:#6b7280; font-style:italic; margin:2px 0 0; }' +
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
			'</style></head><body>' +
			'<div class="rpt-head">' +
				'<div class="rpt-org">DOTr &middot; MRT-3 Line 3 &middot; Operations Control</div>' +
				'<h1 class="rpt-title">Equipment Failures Summary</h1>' +
				'<p class="rpt-subject">'+esc(srmPeriod)+'</p>' +
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
				'<div class="chart"><img src="'+imgMonth+'"><div class="cap">Figure 2 &mdash; Car-level failures by month, all equipment</div></div>' +
				(srmCoverageNote ? '<p class="note" style="color:#7A1F1F;">'+esc(srmCoverageNote)+'</p>' : '') +
				'<p class="note">Figures count car-level failures: an incident affecting several cars counts once against each car, so '+srmIncidents+' incidents produce '+srmGrandTotal+' car-level failures. This matches the per-car reports; the incident history logs count one row per incident and show the smaller figure. Shaded rows are equipment at or above 60% of the highest total.</p>' +
			'</div>' +
			'<h2 class="sec">Monthly Breakdown by Equipment</h2>' +
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