<!--- Modified by Jun
//--- Date: 8/11/2014
//--- Modify: Screen layout and color
//--- Marker: @mjun
//--------------------------------------------------
<form action='statistics_report_afc.php' method='post'>
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

<form action='statistics_report_afc.php' method='post'>
<table>
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
<th>Station</th>
<td>
<select name='station'>
	<?php 
	$db=new mysqli("localhost","root","","transport");
			
	$sql="select * from station";
	$rs=$db->query($sql);
	$nm=$rs->num_rows;
			
	for($i=0;$i<$nm;$i++){
		$row=$rs->fetch_assoc();
	?>
	<option value='<?php echo $row['id']; ?>'><?php echo $row['station_name']; ?></option>
	<?php
	}
	?>	
	<option value='D'>Depot</option>

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
	$level=$_POST['level'];
	
		if($_POST['station']=='D'){
			$stationClause=" and direction='".$_POST['station']."' ";	
			$equiptClause=" and id in ('75','76 ')";
			$station="Depot";
		}
		else {
			$stationClause=" and location='".$_POST['station']."' ";	
			$equiptClause=" and id not in ('75','76 ')";
			$sql="select * from station where id='".$_POST['station']."'";
			$rs=$db->query($sql);
			$row=$rs->fetch_assoc();
			
			$station=$row['station_name'];

		}



}
	
	
	
	
else {
	$year=date("Y");
	$level="2";
	$station=$_GET['station_name'];
}

if(isset($_GET['station'])){
	if($_GET['station']=="D"){
		$stationClause=" and direction='".$_GET['station']."' ";	
		$equiptClause=" and id in ('75','76 ')";
		
		
	}
	else {
		$stationClause=" and location='".$_GET['station']."' ";
		$equiptClause=" and id not in ('75','76 ')";

	}
	$station=$_GET['station_name'];
}


$db=new mysqli("localhost","root","","transport");

$sql="select * from equipment where type='AFC'";

$rs=$db->query($sql);

$nm=$rs->num_rows;


for($i=0;$i<$nm;$i++){
	
	$row=$rs->fetch_assoc();
	for($a=1;$a<=12;$a++){
//	$equipt_count["Month_".$a]["Equipt_".$row['id']]=0;
	}
	
}
?>
<h2><?php echo "Year ".$year; ?></h2>
<h2><?php echo " ".$station; ?></h2>

<table class="train_ava" border=1px style='border-collapse:collapse;' width=100%>
<tr class='rowHeading'>
<th>&nbsp;</th>
<?php
$sql="select * from equipment where type='AFC' ".$equiptClause;

$rs=$db->query($sql);

$nm=$rs->num_rows;

for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
?>
	<th><?php echo $row['equipment_name']; ?></th>

<?php
	
}

?>


</tr>
<?php
for($i=1;$i<=12;$i++){
	$month_heading=date("F",strtotime($year."-".$i."-01"));
	$date_limit=date("t",strtotime($year."-".$i."-01"));
	$start_date=date("Y-m-d",strtotime($year."-".$i."-01"));
	$end_date=date("Y-m-d",strtotime($year."-".$i."-".$date_limit));


	
	$sql="select *,count(1) as equipt_count from incident_union where incident_date between '".$start_date." 00:00:00' and '".$end_date." 23:59:59' and incident_type='AFC' ".$stationClause." group by equipt";
	$rs=$db->query($sql);
	$nm=$rs->num_rows;
	
	for($k=0;$k<$nm;$k++){
		$row=$rs->fetch_assoc();
		$equipt_count["Month_".$i]["Equipt_".$row['equipt']]=$row['equipt_count'];
	}


	$sql="select *,count(1) as equipt_count from incident_union inner join external.incident_defects on incident_report.id=external.incident_defects.incident_id where incident_date between '".$start_date." 00:00:00' and '".$end_date." 23:59:59' and incident_type='AFC' ".$stationClause." group by equipt"; 

	$rs=$db->query($sql);
	$nm=$rs->num_rows;
	
	for($k=0;$k<$nm;$k++){
		$row=$rs->fetch_assoc();
		$equipt_count["Month_".$i]["Equipt_".$row['equipt_id']]+=$row['equipt_count'];
	}



?>

	<tr <?php if($i%2>0){ echo "class='rowClass'"; } ?>>
	<td align=center><?php echo strtoupper($month_heading); ?></td>
	<?php
		$sql="select * from equipment where type='AFC' ".$equiptClause;

		$rs=$db->query($sql);

		$nm=$rs->num_rows;

		for($n=0;$n<$nm;$n++){
			$row=$rs->fetch_assoc();
		?>						
			<td align=center><?php echo $equipt_count["Month_".$i]["Equipt_".$row['id']]; ?></td>
		<?php	
		}

	
?>	
	
	</tr>	
	
<?php	
}


?>
</table>
