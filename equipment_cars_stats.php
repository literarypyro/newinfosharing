<!--- Modified by Jun
//--- Date: 8/6/2014
//--- Modify: screen layout
//--- Marker: @mjun
//--------------------------------------------------->
<style type='text/css'>

.rowClass {background-color: #F3F3F3;}

/* color header */
.rowHeading {background-color: #cccccc;
			color:black
}
.train_ava td{
	border: 1px solid #FBCC2A;
	color: black;
	cellpadding: 5px
}

.train_ava th {
	border: 1px solid #FBCC2A;;
	cellpadding: 5px;	
	color: black
}

select { border: 1px solid rgb(185, 201, 254); color: black; background-color: #FFFACD; }

/* --- mjun -- generate */
a.two:visited {color:black;}
a.two:hover, a.two:active {font-size:120%; color:orange;}

</style>
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
<?php
$equipt_id=$_GET['eq'];
$level=$_GET['level'];
//$start_date=$_GET['sd'];
//$end_date=$_GET['ed'];
$range=$_GET['range'];

if(isset($_GET['sd'])){
$year=date("Y",strtotime($_GET['sd']));

$start_date=date("Y-m-d",strtotime($_GET['sd']));

$init_start=$start_date;


if($_GET['range']=="daily"){
$end_date=$start_date;	
}
else if($_GET['range']=="weekly"){
$end_date=date("Y-m-d",strtotime($_GET['sd']."+1 week"));
	
}

else if($_GET['range']=="monthly"){
$end_date=date("Y-m-d",strtotime($_GET['sd']."+1 month"));
	
}
else if($_GET['range']=="yearly"){
$end_date=date("Y-m-d",strtotime($_GET['sd']."+365 days"));
	
}
else if($_GET['range']=="custom"){
$end_date=date("Y-m-d",strtotime($_GET['ed']));
	
}
}
else {
$start_date=date("Y")."-01-01";
$end_date=date("Y")."-12-31";	
	
}

?>


<?php
$db=new mysqli("localhost","root","","transport");

$sql="select * from equipment where id='$equipt_id'";
$rs=$db->query($sql);
$row=$rs->fetch_assoc();
$ename=$row['equipment_name'];


$sql="SELECT * FROM `incident_cars` inner join incident_report on incident_cars.incident_id=incident_report.id where equipt='$equipt_id' and level='$level' and incident_date between '".$init_start." 00:00:00' and '".$end_date." 23:59:59' group by car_no order by car_no";
//$sql="SELECT * FROM `incident_cars` inner join incident_report on incident_cars.incident_id=incident_report.id where equipt='$equipt_id' group by car_no order by car_no";

//$sql="select * from equipment where type='RS' order by equipment_name";

$rs=$db->query($sql);

$nm=$rs->num_rows;



for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();

	$car[$i]['id']=$row['car_no'];
	$car[$i]['car']=$row['car_no'];
	for ($k=1;$k<=12;$k++){
		$car_count["Car_".$row['car_no']]["Month_".$k]=0;
		
	}
	$car_count["Car_".$row['car_no']]["total"]=0;
	
}


/*

if(isset($_POST['level'])){
//	$year=$_POST['year'];
	$level=$_POST['level'];
}
else {
//	$year=date("Y");
	$level="2";
}
*/
?>
<h2>
<?php echo $ename; ?>
<br>

<?php echo "Year ".$year; echo " / ";echo " Level ".$level;?></h2>
<!-- <h2><?php echo "Level ".$level; ?></h2> -->
<table class='train_ava' border=1px style='border-collapse:collapse;' width=100%>
<tr class='rowHeading'>
<th>&nbsp;</th>
<?php
if(isset($_GET['sd'])){
	$start=date("m",strtotime($start_date));
	$end=date("m",strtotime($end_date));
	
	if($_GET['range']=="yearly"){
		$end=12;
	}
	
	
}
else {
	$start=1;
	$end=12;
}

for($k=$start;$k<=$end;$k++){
?>	
	<th>
	<?php echo date("F",strtotime(date("Y")."-".$k."-01")); ?>
	</th>
	
<?php
}
?>
<th>Total</th>
<?php
//$sql="select *,count(1) as equipt_count from incident_report where level='".$level."' and incident_date between '".$start_date." 00:00:00' and '".$end_date." 23:59:59' and equipt in ('114','102','110','11','113','104','108','109','103','124','67','111','112','105','81','118','119','64','115','89','120','123','121','116','2','122','117','105','81','118','119','64','115','89','120','123','121','116','2','122','117') group by equipt";
//$rs=$db->query($sql);

//$nm=$rs->num_rows;


?>



</tr>
<?php

if(isset($_GET['sd'])){
	$start=date("m",$start_date);
	$end=date("m",strtotime($end_date));
	
	if($_GET['range']=="yearly"){
		$end=12;
	}
	
	
}
else {
	$start=1;
	$end=12;
}



for($i=$start;$i<=$end;$i++){
	$month_heading=date("F",strtotime($year."-".$i."-01"));
	
	
	$date_limit=date("t",strtotime($year."-".$i."-01"));
	$start_date=date("Y-m-d",strtotime($year."-".$i."-01"));
	$end_date=date("Y-m-d",strtotime($year."-".$i."-".$date_limit));

	
	$sql="select *,count(1) as car_count from incident_report inner join incident_cars on incident_report.id=incident_cars.incident_id where level='".$level."' and incident_date between '".$start_date." 00:00:00' and '".$end_date." 23:59:59' and equipt='$equipt_id' group by car_no";
	if($i==1){
//		echo $sql;
//		echo "<br>";
	}
	$rs=$db->query($sql);
	$nm=$rs->num_rows;
	
	for($k=0;$k<$nm;$k++){
		$row=$rs->fetch_assoc();
		$car_count["Car_".$row['car_no']]["Month_".$i]+=$row['car_count'];
		$car_count["Car_".$row['car_no']]["total"]+=$row['car_count'];
		
	}


	$sql="select *,count(1) as car_count from incident_union inner join external.incident_defects on incident_union.id=external.incident_defects.incident_id where level='".$level."' and incident_date between '".$start_date." 00:00:00' and '".$end_date." 23:59:59' and external.incident_defects.equipt_id='$equipt_id' group by car_no"; 
	$rs=$db->query($sql);
	$nm=$rs->num_rows;
	if($i==1){
//		echo $sql;
//		echo "<br>";
	}	
	for($k=0;$k<$nm;$k++){
		$row=$rs->fetch_assoc();
		$car_count["Car_".$row['car_no']]["Month_".$i]+=$row['car_count'];

		$car_count["Car_".$row['car_no']]["total"]+=$row['car_count'];

	}



/*	
	$sql="select *,count(1) as equipt_count from incident_report where level='".$level."' and incident_date between '".$start_date." 00:00:00' and '".$end_date." 23:59:59' and equipt in ('105','81','118','119','64','115','89','120','123','121','116','2','122','117','105','81','118','119','64','115','89','120','123','121','116','2','122','117') group by level";
	$rs=$db->query($sql);
	$nm=$rs->num_rows;

	if($nm>0){
		$row=$rs->fetch_assoc();
		$equipt_count["Equipt_Others"]["Month_".$i]=$row['equipt_count'];
	}

	$sql="select *,count(1) as equipt_count from incident_report inner join external.incident_defects on incident_report.id=external.incident_defects.incident_id where level='".$level."' and incident_date between '".$start_date." 00:00:00' and '".$end_date." 23:59:59' and external.incident_defects.equipt_id in ('105','81','118','119','64','115','89','120','123','121','116','2','122','117','105','81','118','119','64','115','89','120','123','121','116','2','122','117') group by level"; 
	$rs=$db->query($sql);
	$nm=$rs->num_rows;
	
	for($k=0;$k<$nm;$k++){
		$row=$rs->fetch_assoc();
		$equipt_count["Month_".$i]["Equipt_".$row['equipt_id']]+=$row['equipt_count'];
	}
*/

	
}	
?>	
<?php
$highestCar=$car[0];

$car_len=count($car)*1-1;
for($i=0;$i<$car_len;$i++){

	if($i==0){
		
		$car[$i]['total']=$car_count["Car_".$car[$i]['id']]['total'];
		$car[$i*1+1]['total']=$car_count["Car_".$car[$i*1+1]['id']]['total'];

		$highestCar=sortCar($car[$i],$car[$i*1+1]);

	}
	else {
		$car[$i*1+1]['total']=$car_count["Car_".$car[$i*1+1]['id']]['total'];

		$highestCar=sortCar($highestCar,$car[$i*1+1]);
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

function sortCar($car_a,$car_b){
	
	if($car_a['total']*1>$car_b['total']*1){
		
		return $car_a;

	}
	else {
		return $car_b;	

	}
	
}
?>
<?php
$sql="SELECT * FROM `incident_cars` inner join incident_report on incident_cars.incident_id=incident_report.id where equipt='$equipt_id' and level='$level' and incident_date between '".$init_start." 00:00:00' and '".$end_date." 23:59:59' group by car_no order by car_no";

$rs=$db->query($sql);

$nm=$rs->num_rows;



for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
?>
<tr <?php 

	if(($highestCar['total']*0.60)<$car_count["Car_".$car[$i]['id']]["total"]){
	echo "style='background-color:red; color:white;'";
		
	}
	else {

if($i%2>0){ echo "class='rowClass'"; } 

	}



?>>
	<th>
	<?php
	if(($highestCar['total']*0.60)<$car_count["Car_".$car[$i]['id']]["total"]){
	echo "<font color='white'>".$row['car_no']."</font>";
		
	}
	else {
		echo $row['car_no']; 
	}
	?>

	</th>

	<?php
	for($k=$start;$k<=$end;$k++){
	?>	
		<td
	<?php 
	if(($highestCar['total']*0.60)<$car_count["Car_".$car[$i]['id']]["total"]){
		
	}
	else {
		?>
		 class='stat_hover'
		<?php
	}
	?>	

	align=center>
		<?php
	if(($highestCar['total']*0.60)<$car_count["Car_".$car[$i]['id']]["total"]){
	echo "<b><font color='white'>".$car_count["Car_".$car[$i]['id']]["Month_".$k]."</font></b>";
		
	}
	else {
		echo $car_count["Car_".$car[$i]['id']]["Month_".$k]; 
	}

		?>
		</td>


	<?php
	}
	?>
	<td 
	<?php 
	if(($highestCar['total']*0.60)<$car_count["Car_".$car[$i]['id']]["total"]){
		?>
		style='background-color:red; color:white;'
		
		<?php
		
	}
	else {
		?>
		 class='stat_hover'
		<?php
	}
	?>	
	
	
	align=center>
	<?php
	echo $car_count["Car_".$car[$i]['id']]["total"];
	?>
	</td>
</tr>	
<?php	
	
}

?>

</table>
<br>
<br>



<style type='text/css'>
.stat_hover:hover {
	background-color:#fbcc2a;
	text-decoration:underline;
	font-weight:bold;
}
</style>
