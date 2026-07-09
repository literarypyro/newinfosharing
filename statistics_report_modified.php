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

<h1><?php echo $period; echo " / ";echo " Level ".$level;?></h1>
<div class='sub'><!-- <h2><?php echo "Level ".$level; ?></h2> --></div>
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

<a href='#' class="btn-generate" onclick='window.open("generate_statistics_report.php?sd=<?php echo date("Y-m-d",strtotime($_POST['search_date2'])); ?>&ed=<?php echo date("Y-m-d",strtotime($_POST['search_date'])); ?>&range=<?php echo $_POST['range']; ?>&level=<?php echo $level; ?>");'><b>Generate Printout</b></a>




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
<div class='css-panel-body'>
<table class="table table-striped table-bordered bootstrap-datatable datatable2" border=1px style='border-collapse:collapse;' width=100%>
<tr >
<th>&nbsp;</th>
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
	$level=2;
	
	
	
	
	$sql="select *,count(1) as equipt_count from incident_report where level='".$level."' and incident_date between '".$start_date1." 00:00:00' and '".$end_date1." 23:59:59' and equipt in ('114','102','110','11','113','104','108','109','103','124','67','111','112','105','81','118','119','64','115','89','120','123','121','116','2','122','117','105','81','118','119','64','115','89','120','123','121','116','2','122','117') group by equipt";
	if($i==1){
	}
	$rs=$db->query($sql);
	$nm=$rs->num_rows;
	
	for($k=0;$k<$nm;$k++){
		
		$row=$rs->fetch_assoc();
		
		$equipt_count["Equipt_".$row['equipt']]["Month_".($label*1)]+=$row['equipt_count'];
		$equipt_count["Equipt_".$row['equipt']]["total"]+=$row['equipt_count'];
		
		
	}


	$sql="select *,count(1) as equipt_count from incident_report inner join is_external.incident_defects on incident_report.id=is_external.incident_defects.incident_id where level='".$level."' and incident_date between '".$start_date1." 00:00:00' and '".$end_date1." 23:59:59' and is_external.incident_defects.equipt_id in ('114','102','110','11','113','104','108','109','103','124','67','111','112','105','81','118','119','64','115','89','120','123','121','116','2','122','117','105','81','118','119','64','115','89','120','123','121','116','2','122','117') group by equipt_id"; 
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
$highestCar=$equipt[0];

$car_len=count($equipt)*1-1;
for($i=0;$i<$car_len;$i++){

	if($i==0){
		
		$equipt[$i]['total']=$equipt_count["Equipt_".$equipt[$i]['id']]['total'];
		$equipt[$i*1+1]['total']=$equipt_count["Equipt_".$equipt[$i*1+1]['id']]['total'];

		$highestCar=sortCar($equipt[$i],$equipt[$i*1+1]);

	}
	else {
		$equipt[$i*1+1]['total']=$equipt_count["Equipt_".$equipt[$i*1+1]['id']]['total'];

		$highestCar=sortCar($highestCar,$equipt[$i*1+1]);
	}
/*
	$car[$i]['id']=$row['car_no'];
	$car[$i]['car']=$row['car_no'];
	for ($k=1;$k<=12;$k++){
		$car_count["Car_".$row['car_no']]["Month_".$k]=0;
		
	}
	$car_count["Car_".$row['car_no']]["total"]=0;
	*/
}

$sql="select * from equipment where id in ('114','102','110','11','113','104','108','109','103','124','67','111','112','105','81','118','119','64','115','89','120','123','121','116','2','122','117','105','81','118','119','64','115','89','120','123','121','116','2','122','117') order by equipment_name";

$rs=$db->query($sql);

$nm=$rs->num_rows;



for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();

?>
<tr 

<?php
	if(($highestCar['total']*0.60)<$equipt_count["Equipt_".$equipt[$i]['id']]["total"]){
	echo "style='background-color:#F9D6D6; color:#7A1F1F;'";
		
	}
	else {

if($i%2>0){ echo "class='rowClass'"; } 

	}

?>





>
	<th>

	<?php
		if(isset($_POST['level'])){
			$level=$_POST['level'];
			$start_date=$_POST['search_date'];
			$end_date=$_POST['search_date2'];
		}
		else {
			$start_date=date("Y-01-01");
			$end_date=$end_date1;

		}			


	if(($highestCar['total']*0.60)<$equipt_count["Equipt_".$equipt[$i]['id']]["total"]){
		
		

//	if($highestCar['total']==$equipt_count["Equipt_".$equipt[$i]['id']]["total"]){
	
	?>
		<a href='#' style='text-decoration:none; color:#00529B; font-weight:600;' onclick='window.open("equipment_cars_stats.php?eq=<?php echo $equipt[$i]['id']; ?>&level=<?php echo $level; ?>&range=custom&sd=<?php echo $start_date; ?>&ed=<?php echo $end_date;?>")' >
	
	<?php echo $row['equipment_name']; ?>
	</a>
	

	<?php	
	}
	else {
	?>	
		<a href='#' style='text-decoration:none; color:#00529B; font-weight:600;' onclick='window.open("equipment_cars_stats.php?eq=<?php echo $equipt[$i]['id']; ?>&level=<?php echo $level; ?>&range=custom&sd=<?php echo $start_date; ?>&ed=<?php echo $end_date;?>")' >
	
	
	<?php echo $row['equipment_name']; ?>
	</a>
	
	<?php
	}
	?>
	
	</th>

	<?php
	for($k=0;$k<=$difference;$k++){
	?>	
	<td class='stat_hover' align=center>


	<?php
//	if($highestCar['total']==$equipt_count["Equipt_".$equipt[$i]['id']]["total"]){
	if(($highestCar['total']*0.60)<$equipt_count["Equipt_".$equipt[$i]['id']]["total"]){
	if($k==0){
	$yy=date("Y",strtotime($start_date));

	$mon=date("m",strtotime($start_date));
		$label=$yy.$mon;
		
	}
	else {
	$yy=date("Y",strtotime($tag_date."+".$k." months"));

	$mon=date("m",strtotime($tag_date."+".$k." months"));
	
	$fn=date("F",strtotime($tag_date."+".$k." months"));
	
		$label=$yy.$mon;
	
	}
	?>
	<a href='#' style='text-decoration:none; color:#00529B;' onclick='window.open("equipment_history.php?equipt=<?php echo $equipt[$i]['id']; ?>&y=<?php echo $yy; ?>&m=<?php echo $mon; ?>&level=<?php echo $level; ?>",target="_self")' >
		<?php
		

		
		
		echo $equipt_count["Equipt_".$equipt[$i]['id']]["Month_".($label*1)];

		?>
		</a>
	

	<?php	
	}
	else {
		
	if($k==0){
	$yy=date("Y",strtotime($start_date));

	$mon=date("m",strtotime($start_date));
		
		$label=$yy.$mon;
	}
	else {
	$yy=date("Y",strtotime($tag_date."+".$k." months"));

	$mon=date("m",strtotime($tag_date."+".$k." months"));
	
	$fn=date("F",strtotime($tag_date."+".$k." months"));
		$label=$yy.$mon;
	
	
	}
	?>	
	<a href='#' style='text-decoration:none; color:#00529B;' onclick='window.open("equipment_history.php?equipt=<?php echo $equipt[$i]['id']; ?>&y=<?php echo $yy; ?>&m=<?php echo $mon; ?>&level=<?php echo $level; ?>",target="_self")' >
		<?php
		

		echo $equipt_count["Equipt_".$equipt[$i]['id']]["Month_".($label*1)];
		?>
		</a>
	
	<?php
	}
	?>

		</td>
	<?php
	}
	?>
		<td class='stat_hover' align=center>

	<?php
	if(($highestCar['total']*0.60)<$equipt_count["Equipt_".$equipt[$i]['id']]["total"]){
	
	?>
	<?php
		echo $equipt_count["Equipt_".$equipt[$i]['id']]["total"];
		?>
	<?php	
	}
	else {
	?>	
		<?php
		echo $equipt_count["Equipt_".$equipt[$i]['id']]["total"];
		?>
	
	<?php
	}
	?>



		</td>

	
</tr>	
<?php	
	
}





function sortCar($equipt_a,$equipt_b){
	
	if($equipt_a['total']*1>$equipt_b['total']*1){
		
		return $equipt_a;

	}
	else {
		return $equipt_b;	

	}
	
}



?>


</table>
</div>
<br>
<br>
</div>
</body>
</html>