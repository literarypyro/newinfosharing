<!--- Modified by Jun
//--- Date: 8/7/2014
//--- Modify: screen layout
//--- Marker: @mjun
//--------------------------------------------------->
<?php
$db=new mysqli("localhost","root","","transport");
?>

<style type='text/css'>

.rowClass {background-color: #F3F3F3;}

/* color header */
.rowHeading {background-color: #cccccc; 
			color:black;
			font-family: "Comic Sans MS"; 
			font-size: 16px
}

/* color header 2 */
.rowHeading2 {background-color: #D8D8D8;
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



<form action='car_statistics_report.php' method='post'>
<table>
<!--
<tr><th>Level</th>
<td>
<select name='level'>
<option value='2'>2</option>
<option value='3'>3</option>
</select>
</td>
</tr>
-->
<tr>
<th>Year</th>
<td>
<?php
$startYear=2013;

$endYear=date("Y")*1+16;

?>
<select name='year'>
<?php
for($k=$startYear;$k<=$endYear;$k++){
?>
	<option value="<?php echo $k; ?>"><?php echo $k; ?></option>

<?php
}
?>
</select>
</td>
</tr>

<tr>
<th colspan=2><input type=submit value='Submit' /></th>
</tr>
</table>
</form>
<?php
if(isset($_POST['year'])){
	$year=$_POST['year'];

}
else {
	$year=date("Y");

}



?>

<h2><?php echo "Year ".$year; ?></h2>


<table class='train_ava' border=1 style='border-collapse:collapse;' width=100%>
<tr class='rowHeading'><th colspan=13>Car Statistics Report</th></tr>
<tr class='rowHeading2'>	
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
$rs=$db->query($sql);

$nm=$rs->num_rows;

if($nm>0){
	for($i=0;$i<$nm;$i++){
		$row=$rs->fetch_assoc();
		$car_id=$row['car_no']*1;
		$month=$row['mo']*1;
		
		$stats["Car_".$car_id]["Month_".$month]=$row['count'];
		
	}
}

for($i=1;$i<=73;$i++){
?>
<tr <?php if($i%2>0){ echo "class='rowClass'"; } ?>>
<th class='stat_hover'><a href='#' style='text-decoration:none; color:black;'  onclick='window.open("car_history.php?car_id=<?php echo $i; ?>&y=<?php echo $year; ?>",target="_self")' ><?php echo $i; ?></a></th>
<?php
for($k=1;$k<=12;$k++){
?>			
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:black;' onclick='window.open("car_history.php?car_id=<?php echo $i; ?>&y=<?php echo $year; ?>&m=<?php echo $k; ?>",target="_self")' ><?php echo $stats["Car_".$i]["Month_".$k]; ?></a></td>
<?php
}
?>
</tr>

<?php
}
?>
</table>
<style type='text/css'>
.stat_hover:hover {
	background-color:#fbcc2a;
	text-decoration:underline;
	font-weight:bold;

}


</style>