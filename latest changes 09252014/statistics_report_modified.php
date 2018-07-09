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
$db=new mysqli("localhost","root","","transport");

for($i=1;$i<=12;$i++){
	$equipt_count["Month_".$i]["Equipt_114"]=0;
	$equipt_count["Month_".$i]["Equipt_102"]=0;
	$equipt_count["Month_".$i]["Equipt_110"]=0;
	$equipt_count["Month_".$i]["Equipt_11"]=0;
	$equipt_count["Month_".$i]["Equipt_113"]=0;
	$equipt_count["Month_".$i]["Equipt_104"]=0;
	$equipt_count["Month_".$i]["Equipt_108"]=0;
	$equipt_count["Month_".$i]["Equipt_109"]=0;
	$equipt_count["Month_".$i]["Equipt_103"]=0;
	$equipt_count["Month_".$i]["Equipt_124"]=0;
	$equipt_count["Month_".$i]["Equipt_67"]=0;
	$equipt_count["Month_".$i]["Equipt_111"]=0;
	$equipt_count["Month_".$i]["Equipt_112"]=0;
	$equipt_count["Month_".$i]["Equipt_Others"]=0;

	$equipt_count["Month_".$i]["Equipt_105"]=0;
	$equipt_count["Month_".$i]["Equipt_81"]=0;
	$equipt_count["Month_".$i]["Equipt_118"]=0;
	$equipt_count["Month_".$i]["Equipt_119"]=0;
	$equipt_count["Month_".$i]["Equipt_64"]=0;
	$equipt_count["Month_".$i]["Equipt_115"]=0;
	$equipt_count["Month_".$i]["Equipt_89"]=0;
	$equipt_count["Month_".$i]["Equipt_120"]=0;
	$equipt_count["Month_".$i]["Equipt_123"]=0;
	$equipt_count["Month_".$i]["Equipt_121"]=0;
	$equipt_count["Month_".$i]["Equipt_116"]=0;
	$equipt_count["Month_".$i]["Equipt_2"]=0;
	$equipt_count["Month_".$i]["Equipt_122"]=0;
	$equipt_count["Month_".$i]["Equipt_117"]=0;	
	
}

if(isset($_POST['year'])){
	$year=$_POST['year'];
	$level=$_POST['level'];
}
else {
	$year=date("Y");
	$level="2";
}

?>
<h2><?php echo "Year ".$year; echo " / ";echo " Level ".$level;?></h2>
<!-- <h2><?php echo "Level ".$level; ?></h2> -->
<table class='train_ava' border=1px style='border-collapse:collapse;' width=100%>
<tr class='rowHeading'>
<th>&nbsp;</th>
<th>Door Failure</th>
<th>Drive Circuit<br> Interlocking</th>
<th>Filter Undervoltage</th>
<th>Static Converter</th>
<th>Mechanical Brakes</th>
<th>Overcurrent</th>
<th>Unshaded, Weak,<br> Dropping Current</th>
<th>OCS Undervoltage</th>
<th>Communication Error</th>
<th>Start-up<br> Interlocking</th>
<th>Regulator</th>
<th>Main Chopper</th>
<th>Auxiliary Chopper</th>


<th>Burnt Smell</th>
<th>ATP</th>
<th>Disc Illumination</th>
<th>Slip/Skid</th>
<th>Line Contactor</th>
<th>Jerking</th>
<th>Warning Bell</th>
<th>Lateral</th>
<th>Tachograph</th>
<th>Diagnostic Panel</th>
<th>UMIN Test</th>
<th>Air Condition</th>
<th>Emergency Brakes</th>
<th>TRS Recommendation</th>




</tr>
<?php
for($i=1;$i<=12;$i++){
	$month_heading=date("F",strtotime($year."-".$i."-01"));
	
	
	$date_limit=date("t",strtotime($year."-".$i."-01"));
	$start_date=date("Y-m-d",strtotime($year."-".$i."-01"));
	$end_date=date("Y-m-d",strtotime($year."-".$i."-".$date_limit));

	
	$sql="select *,count(1) as equipt_count from incident_report where level='".$level."' and incident_date between '".$start_date." 00:00:00' and '".$end_date." 23:59:59' and equipt in ('114','102','110','11','113','104','108','109','103','124','67','111','112') group by equipt";
	
	if($i==1){
//		echo $sql;
//		echo "<br>";
	}
	$rs=$db->query($sql);
	$nm=$rs->num_rows;
	
	for($k=0;$k<$nm;$k++){
		$row=$rs->fetch_assoc();
		$equipt_count["Month_".$i]["Equipt_".$row['equipt']]=$row['equipt_count'];
	}


	$sql="select *,count(1) as equipt_count from incident_report inner join external.incident_defects on incident_report.id=external.incident_defects.incident_id where level='".$level."' and incident_date between '".$start_date." 00:00:00' and '".$end_date." 23:59:59' and external.incident_defects.equipt_id in ('114','102','110','11','113','104','108','109','103','124','67','111','112') group by equipt_id"; 
	$rs=$db->query($sql);
	$nm=$rs->num_rows;
	if($i==1){
//		echo $sql;
//		echo "<br>";
	}	
	for($k=0;$k<$nm;$k++){
		$row=$rs->fetch_assoc();
		$equipt_count["Month_".$i]["Equipt_".$row['equipt_id']]+=$row['equipt_count'];
	}



	
	$sql="select *,count(1) as equipt_count from incident_report where level='".$level."' and incident_date between '".$start_date." 00:00:00' and '".$end_date." 23:59:59' and equipt in ('105','81','118','119','64','115','89','120','123','121','116','2','122','117','105','81','118','119','64','115','89','120','123','121','116','2','122','117') group by level";
	$rs=$db->query($sql);
	$nm=$rs->num_rows;

	if($nm>0){
		$row=$rs->fetch_assoc();
		$equipt_count["Month_".$i]["Equipt_Others"]=$row['equipt_count'];
	}

	$sql="select *,count(1) as equipt_count from incident_report inner join external.incident_defects on incident_report.id=external.incident_defects.incident_id where level='".$level."' and incident_date between '".$start_date." 00:00:00' and '".$end_date." 23:59:59' and external.incident_defects.equipt_id in ('105','81','118','119','64','115','89','120','123','121','116','2','122','117','105','81','118','119','64','115','89','120','123','121','116','2','122','117') group by level"; 
	$rs=$db->query($sql);
	$nm=$rs->num_rows;
	
	for($k=0;$k<$nm;$k++){
		$row=$rs->fetch_assoc();
		$equipt_count["Month_".$i]["Equipt_".$row['equipt_id']]+=$row['equipt_count'];
	}


	
	
?>	
	
<tr <?php if($i%2>0){ echo "class='rowClass'"; } ?>>
	<td align=center><?php echo strtoupper($month_heading); ?></td>
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:black;' onclick='window.open("equipment_history.php?equipt=114&y=<?php echo $year; ?>&m=<?php echo $i; ?>&level=<?php echo $level; ?>",target="_self")' ><?php echo $equipt_count["Month_".$i]["Equipt_114"]; ?></a></td>
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:black;' onclick='window.open("equipment_history.php?equipt=102&y=<?php echo $year; ?>&m=<?php echo $i; ?>&level=<?php echo $level; ?>",target="_self")' ><?php echo $equipt_count["Month_".$i]["Equipt_102"]; ?></a></td>
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:black;' onclick='window.open("equipment_history.php?equipt=110&y=<?php echo $year; ?>&m=<?php echo $i; ?>&level=<?php echo $level; ?>",target="_self")' ><?php echo $equipt_count["Month_".$i]["Equipt_110"]; ?></a></td>
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:black;' onclick='window.open("equipment_history.php?equipt=11&y=<?php echo $year; ?>&m=<?php echo $i; ?>&level=<?php echo $level; ?>",target="_self")' ><?php echo $equipt_count["Month_".$i]["Equipt_11"]; ?></a></td>
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:black;' onclick='window.open("equipment_history.php?equipt=113&y=<?php echo $year; ?>&m=<?php echo $i; ?>&level=<?php echo $level; ?>",target="_self")' ><?php echo $equipt_count["Month_".$i]["Equipt_113"]; ?></a></td>
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:black;' onclick='window.open("equipment_history.php?equipt=104&y=<?php echo $year; ?>&m=<?php echo $i; ?>&level=<?php echo $level; ?>",target="_self")' ><?php echo $equipt_count["Month_".$i]["Equipt_104"]; ?></a></td>
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:black;' onclick='window.open("equipment_history.php?equipt=124&y=<?php echo $year; ?>&m=<?php echo $i; ?>&level=<?php echo $level; ?>",target="_self")' ><?php echo $equipt_count["Month_".$i]["Equipt_124"]; ?></a></td>
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:black;' onclick='window.open("equipment_history.php?equipt=109&y=<?php echo $year; ?>&m=<?php echo $i; ?>&level=<?php echo $level; ?>",target="_self")' ><?php echo $equipt_count["Month_".$i]["Equipt_109"]; ?></a></td>
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:black;' onclick='window.open("equipment_history.php?equipt=103&y=<?php echo $year; ?>&m=<?php echo $i; ?>&level=<?php echo $level; ?>",target="_self")' ><?php echo $equipt_count["Month_".$i]["Equipt_103"]; ?></a></td>
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:black;' onclick='window.open("equipment_history.php?equipt=108&y=<?php echo $year; ?>&m=<?php echo $i; ?>&level=<?php echo $level; ?>",target="_self")' ><?php echo $equipt_count["Month_".$i]["Equipt_108"]; ?></a></td>
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:black;' onclick='window.open("equipment_history.php?equipt=67&y=<?php echo $year; ?>&m=<?php echo $i; ?>&level=<?php echo $level; ?>",target="_self")' ><?php echo $equipt_count["Month_".$i]["Equipt_67"]; ?></a></td>
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:black;' onclick='window.open("equipment_history.php?equipt=111&y=<?php echo $year; ?>&m=<?php echo $i; ?>&level=<?php echo $level; ?>",target="_self")' ><?php echo $equipt_count["Month_".$i]["Equipt_111"]; ?></a></td>
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:black;' onclick='window.open("equipment_history.php?equipt=112&y=<?php echo $year; ?>&m=<?php echo $i; ?>&level=<?php echo $level; ?>",target="_self")' ><?php echo $equipt_count["Month_".$i]["Equipt_112"]; ?></a></td>
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:black;' onclick='window.open("equipment_history.php?equipt=105&y=<?php echo $year; ?>&m=<?php echo $i; ?>&level=<?php echo $level; ?>",target="_self")' ><?php echo $equipt_count["Month_".$i]["Equipt_105"]; ?></a></td>
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:black;' onclick='window.open("equipment_history.php?equipt=81&y=<?php echo $year; ?>&m=<?php echo $i; ?>&level=<?php echo $level; ?>",target="_self")' ><?php echo $equipt_count["Month_".$i]["Equipt_81"]; ?></a></td>
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:black;' onclick='window.open("equipment_history.php?equipt=118&y=<?php echo $year; ?>&m=<?php echo $i; ?>&level=<?php echo $level; ?>",target="_self")' ><?php echo $equipt_count["Month_".$i]["Equipt_118"]; ?></a></td>
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:black;' onclick='window.open("equipment_history.php?equipt=119&y=<?php echo $year; ?>&m=<?php echo $i; ?>&level=<?php echo $level; ?>",target="_self")' ><?php echo $equipt_count["Month_".$i]["Equipt_119"]; ?></a></td>
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:black;' onclick='window.open("equipment_history.php?equipt=64&y=<?php echo $year; ?>&m=<?php echo $i; ?>&level=<?php echo $level; ?>",target="_self")' ><?php echo $equipt_count["Month_".$i]["Equipt_64"]; ?></a></td>
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:black;' onclick='window.open("equipment_history.php?equipt=115&y=<?php echo $year; ?>&m=<?php echo $i; ?>&level=<?php echo $level; ?>",target="_self")' ><?php echo $equipt_count["Month_".$i]["Equipt_115"]; ?></a></td>
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:black;' onclick='window.open("equipment_history.php?equipt=89&y=<?php echo $year; ?>&m=<?php echo $i; ?>&level=<?php echo $level; ?>",target="_self")' ><?php echo $equipt_count["Month_".$i]["Equipt_89"]; ?></a></td>
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:black;' onclick='window.open("equipment_history.php?equipt=120&y=<?php echo $year; ?>&m=<?php echo $i; ?>&level=<?php echo $level; ?>",target="_self")' ><?php echo $equipt_count["Month_".$i]["Equipt_120"]; ?></a></td>
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:black;' onclick='window.open("equipment_history.php?equipt=123&y=<?php echo $year; ?>&m=<?php echo $i; ?>&level=<?php echo $level; ?>",target="_self")' ><?php echo $equipt_count["Month_".$i]["Equipt_123"]; ?></a></td>
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:black;' onclick='window.open("equipment_history.php?equipt=121&y=<?php echo $year; ?>&m=<?php echo $i; ?>&level=<?php echo $level; ?>",target="_self")' ><?php echo $equipt_count["Month_".$i]["Equipt_121"]; ?></a></td>
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:black;' onclick='window.open("equipment_history.php?equipt=116&y=<?php echo $year; ?>&m=<?php echo $i; ?>&level=<?php echo $level; ?>",target="_self")' ><?php echo $equipt_count["Month_".$i]["Equipt_116"]; ?></a></td>
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:black;' onclick='window.open("equipment_history.php?equipt=2&y=<?php echo $year; ?>&m=<?php echo $i; ?>&level=<?php echo $level; ?>",target="_self")' ><?php echo $equipt_count["Month_".$i]["Equipt_2"]; ?></a></td>
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:black;' onclick='window.open("equipment_history.php?equipt=122&y=<?php echo $year; ?>&m=<?php echo $i; ?>&level=<?php echo $level; ?>",target="_self")' ><?php echo $equipt_count["Month_".$i]["Equipt_122"]; ?></a></td>
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:black;' onclick='window.open("equipment_history.php?equipt=117&y=<?php echo $year; ?>&m=<?php echo $i; ?>&level=<?php echo $level; ?>",target="_self")' ><?php echo $equipt_count["Month_".$i]["Equipt_117"]; ?></a></td>
</tr>	

<?php	
}


?>
</table>
<br>
<br>
<a href='#' class="two" onclick='window.open("generate_statistics_report.php?year=<?php echo $year; ?>&level=<?php echo $level; ?>");'><b>Generate Printout</b></a>
<style type='text/css'>
.stat_hover:hover {
	background-color:#fbcc2a;
	text-decoration:underline;
	font-weight:bold;
}
</style>
