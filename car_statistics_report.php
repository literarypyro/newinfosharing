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
//--------------------------------------------------->
<?php
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
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



?>

<h1><?php echo "Car Incidents By Year"; ?></h1>
<div class='sub'> <?php echo "For the Year ".$year; ?> </div>
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
<input type=submit value='Submit' />
</form>
<div class="stat-legend">
	<span><span class="swatch" style="background:#00529B;"></span>Click a car number for its full-year history</span>
	<span><span class="swatch" style="background:#FDB813;"></span>Click a monthly count for that car's incidents that month</span>
	<span><span class="swatch" style="background:#F9D6D6; border:1px solid #E3A9A9;"></span>Highlighted row = among the highest incident counts this year (&ge;60% of the peak)</span>
</div>
</div>
<div class='css-panel-body'>


<table class='table table-striped table-bordered bootstrap-datatable datatable2' border=1 style='border-collapse:collapse;' width=100%>
<tr>	
	<th>Car #</th>
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
</tr>
<?php
for($i=1;$i<=73;$i++){
	for($k=1;$k<=12;$k++){
		$stats["Car_".$i]["Month_".$k]=0;
	
	}
}

$sql="SELECT car_no,month(incident_date) as mo,sum(1) as count FROM incident_cars inner join incident_report on incident_cars.incident_id=incident_report.id where incident_date like '".$year."-%%' group by incident_cars.car_no*1,month(incident_date)";

$sql.=" union ";
$sql.="SELECT car_no,month(incident_date) as mo,sum(1) as count FROM is_transport_old.incident_cars inner join is_transport_old.incident_report on is_transport_old.incident_cars.incident_id=is_transport_old.incident_report.id where incident_date like '".$year."-%%' group by incident_cars.car_no*1,month(incident_date)";

$rs=$db->query($sql);

$nm=$rs->num_rows;

if($nm>0){
	$highestCount=0;
	
	for($i=0;$i<$nm;$i++){
		$row=$rs->fetch_assoc();
		$car_id=$row['car_no']*1;
		$month=$row['mo']*1;
		
		$stats["Car_".$car_id]["Month_".$month]=$row['count'];

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
		echo "style='background-color:#F9D6D6; color:#7A1F1F;'";

}
else {

//	if($i%2>0){ echo "class='rowClass'"; } 

}


?>>
<th class='stat_hover'><a href='#' style='text-decoration:none; color:#00529B; font-weight:600;'  onclick='window.open("car_history.php?car_id=<?php echo $i; ?>&y=<?php echo $year; ?>",target="_self")' ><?php echo $i; ?></a></th>
<?php
for($k=1;$k<=12;$k++){
?>			
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:#00529B;' onclick='window.open("car_history.php?car_id=<?php echo $i; ?>&y=<?php echo $year; ?>&m=<?php echo $k; ?>",target="_self")' ><?php echo $stats["Car_".$i]["Month_".$k]; ?></a></td>
<?php
}
?>
</tr>

<?php
}
?>
</table>
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
</body>
</html>