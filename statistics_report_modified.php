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


<!-- <form action='statistics_report.php' method='post'> -->
<form action='statistics_report_modified.php' method='post'>
<table>
<tr><th>Level</th>
<td>
<select name='level'>
<option value='1'>1</option>
<option value='2'>2</option>
<option value='3'>3</option>
</select>
</td>
</tr>
<tr>
<td>
From</td><td> <input type="text" name='search_date2' id='search_date2'>
</td>
</tr>
<tr>
<td>
To</td><td> <input type="text" name='search_date' id='search_date'>
</td>
</tr>
<tr>
<th>Range</th>
<td>
<select name='range'>
<option value='daily'>Daily</option>
<option value='weekly'>Weekly</option>
<option value='monthly'>Monthly</option>
<option value='yearly'>Yearly</option>
<option value='custom'>Custom Range</option>

</select>

</tr>
<tr>

<th colspan=2><input type=submit value='Submit' /></th>
</tr>
</table>
</form>

<?php
$db=new mysqli("localhost","root","","transport");
$sql="select * from equipment where id in ('114','102','110','11','113','104','108','109','103','124','67','111','112','105','81','118','119','64','115','89','120','123','121','116','2','122','117','105','81','118','119','64','115','89','120','123','121','116','2','122','117') order by equipment_name";

//$sql="select * from equipment where type='RS' order by equipment_name";

$rs=$db->query($sql);

$nm=$rs->num_rows;


if(isset($_POST['search_date2'])){
$year=date("Y",strtotime($_POST['search_date2']));

$start_date=date("Y-m-d",strtotime($_POST['search_date2']));
/*$dates=explode(" - ",$_POST['search_date2']);


$start_date=date("Y-m-d",strtotime($dates[0]));
$end_date=date("Y-m-d",strtotime($dates[1]));
*/



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

for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();

	$equipt[$i]['id']=$row['id'];
	$equipt[$i]['equipment']=$row['equipment_name'];
	for ($k=1;$k<=12;$k++){
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
<h2><?php echo "Year ".$year; echo " / ";echo " Level ".$level;?></h2>
<!-- <h2><?php echo "Level ".$level; ?></h2> -->
<table class='train_ava' border=1px style='border-collapse:collapse;' width=100%>
<tr class='rowHeading'>
<th>&nbsp;</th>
<?php
if(isset($_POST['search_date2'])){
	$start=date("m",strtotime($start_date));
	$end=date("m",strtotime($end_date));
	
	if($_POST['range']=="yearly"){
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



//$sql="select *,count(1) as equipt_count from incident_report where level='".$level."' and incident_date between '".$start_date." 00:00:00' and '".$end_date." 23:59:59' and equipt in ('114','102','110','11','113','104','108','109','103','124','67','111','112','105','81','118','119','64','115','89','120','123','121','116','2','122','117','105','81','118','119','64','115','89','120','123','121','116','2','122','117') group by equipt";
//$rs=$db->query($sql);

//$nm=$rs->num_rows;


?>
<th>Total
</th>


</tr>
<?php

if(isset($_POST['search_date2'])){
	$start=date("m",$start_date);
	$end=date("m",strtotime($end_date));
	
	if($_POST['range']=="yearly"){
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

	
	$sql="select *,count(1) as equipt_count from incident_report where level='".$level."' and incident_date between '".$start_date." 00:00:00' and '".$end_date." 23:59:59' and equipt in ('114','102','110','11','113','104','108','109','103','124','67','111','112','105','81','118','119','64','115','89','120','123','121','116','2','122','117','105','81','118','119','64','115','89','120','123','121','116','2','122','117') group by equipt";
	
	if($i==1){
//		echo $sql;
//		echo "<br>";
	}
	$rs=$db->query($sql);
	$nm=$rs->num_rows;
	
	for($k=0;$k<$nm;$k++){
		$row=$rs->fetch_assoc();
		$equipt_count["Equipt_".$row['equipt']]["Month_".$i]+=$row['equipt_count'];
		$equipt_count["Equipt_".$row['equipt']]["total"]+=$row['equipt_count'];

	}


	$sql="select *,count(1) as equipt_count from incident_report inner join external.incident_defects on incident_report.id=external.incident_defects.incident_id where level='".$level."' and incident_date between '".$start_date." 00:00:00' and '".$end_date." 23:59:59' and external.incident_defects.equipt_id in ('114','102','110','11','113','104','108','109','103','124','67','111','112','105','81','118','119','64','115','89','120','123','121','116','2','122','117','105','81','118','119','64','115','89','120','123','121','116','2','122','117') group by equipt_id"; 
	$rs=$db->query($sql);
	$nm=$rs->num_rows;
	if($i==1){
//		echo $sql;
//		echo "<br>";
	}	
	for($k=0;$k<$nm;$k++){
		$row=$rs->fetch_assoc();
		$equipt_count["Equipt_".$row['equipt_id']]["Month_".$i]+=$row['equipt_count'];
		$equipt_count["Equipt_".$row['equipt_id']]["total"]+=$row['equipt_count'];

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
$highestCar=$equipt[0];

$car_len=count($equipt)*1-1;
for($i=0;$i<$car_len;$i++){

	if($i==0){
		
		$equipt[$i]['total']=$equipt_count["Equipt_".$equipt[$i]['id']]['total'];
		$equipt[$i*1+1]['total']=$equipt_count["Equipt_".$equipt[$i*1+1]['id']]['total'];

		$highestCar=sortCar($equipt[$i],$equipt[$i*1+1]);

	}
	else {
		$car[$i*1+1]['total']=$equipt_count["Equipt_".$equipt[$i*1+1]['id']]['total'];

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
	echo "style='background-color:red; color:white;'";
		
	}
	else {

if($i%2>0){ echo "class='rowClass'"; } 

	}

?>





>
	<th>

	<?php
	if(($highestCar['total']*0.60)<$equipt_count["Equipt_".$equipt[$i]['id']]["total"]){

//	if($highestCar['total']==$equipt_count["Equipt_".$equipt[$i]['id']]["total"]){
	
	?>
		<a href='#' style='text-decoration:none; color:white;' onclick='window.open("equipment_cars_stats.php?eq=<?php echo $equipt[$i]['id']; ?>&level=<?php echo $_POST['level']; ?>&range=<?php echo $_POST['range']; ?>&sd=<?php echo $_POST['search_date2']; ?>&ed=<?php echo $_POST['search_date'];?>",target="_blank")' >
	
	<?php echo $row['equipment_name']; ?>
	</a>
	

	<?php	
	}
	else {
	?>	
		<a href='#' style='text-decoration:none; color:black;' onclick='window.open("equipment_cars_stats.php?eq=<?php echo $equipt[$i]['id']; ?>&level=<?php echo $_POST['level']; ?>&range=<?php echo $_POST['range']; ?>&sd=<?php echo $_POST['search_date2']; ?>&ed=<?php echo $_POST['search_date'];?>",target="_blank")' >
	
	<?php echo $row['equipment_name']; ?>
	</a>
	
	<?php
	}
	?>
	
	</th>

	<?php
	for($k=$start;$k<=$end;$k++){
	?>	
	<td class='stat_hover' align=center>


	<?php
//	if($highestCar['total']==$equipt_count["Equipt_".$equipt[$i]['id']]["total"]){
	if(($highestCar['total']*0.60)<$equipt_count["Equipt_".$equipt[$i]['id']]["total"]){
	
	?>
	<a href='#' style='text-decoration:none; color:white;' onclick='window.open("equipment_history.php?equipt=<?php echo $equipt[$i]['id']; ?>&y=<?php echo $year; ?>&m=<?php echo $k; ?>&level=<?php echo $level; ?>",target="_self")' >
		<?php
		echo $equipt_count["Equipt_".$equipt[$i]['id']]["Month_".$k];
		?>
		</a>
	

	<?php	
	}
	else {
	?>	
	<a href='#' style='text-decoration:none; color:black;' onclick='window.open("equipment_history.php?equipt=<?php echo $equipt[$i]['id']; ?>&y=<?php echo $year; ?>&m=<?php echo $k; ?>&level=<?php echo $level; ?>",target="_self")' >
		<?php
		echo $equipt_count["Equipt_".$equipt[$i]['id']]["Month_".$k];
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
	<font color=white>
	<?php
		echo $equipt_count["Equipt_".$equipt[$i]['id']]["total"];
		?>
	
</font>
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
<br>
<br>
<a href='#' class="two" onclick='window.open("generate_statistics_report.php?sd=<?php echo date("Y-m-d",strtotime($_POST['search_date2'])); ?>&ed=<?php echo date("Y-m-d",strtotime($_POST['search_date'])); ?>&range=<?php echo $_POST['range']; ?>&level=<?php echo $level; ?>");'><b>Generate Printout</b></a>
<style type='text/css'>
.stat_hover:hover {
	background-color:#fbcc2a;
	text-decoration:underline;
	font-weight:bold;
}
</style>
