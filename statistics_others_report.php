<?php
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
for($i=1;$i<=12;$i++){
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

if(isset($_GET['year'])){
	$year=$_GET['year'];
	$level=$_GET['level'];
}
else {
	$year=date("Y");
	$level="2";
}



?>
<h2><?php echo "Year ".$year; ?></h2>
<h2><?php echo "Level ".$level; ?></h2>
<table border=1px style='border-collapse:collapse;' width=100%>
<tr>
<th>&nbsp;</th>
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

	
	$sql="select *,count(1) as equipt_count from incident_report where level='".$level."' and incident_date between '".$start_date." 00:00:00' and '".$end_date." 23:59:59' and equipt in ('105','81','118','119','64','115','89','120','123','121','116','2','122','117') group by equipt";
	$rs=$db->query($sql);
	$nm=$rs->num_rows;
	
	for($k=0;$k<$nm;$k++){
		$row=$rs->fetch_assoc();
		$equipt_count["Month_".$i]["Equipt_".$row['equipt']]=$row['equipt_count'];
	}
	$sql="select *,count(1) as equipt_count from incident_report inner join external.incident_defects on incident_report.id=external.incident_defects.incident_id where level='".$level."' and incident_date between '".$start_date." 00:00:00' and '".$end_date." 23:59:59' and external.incident_defects.equipt_id in ('105','81','118','119','64','115','89','120','123','121','116','2','122','117') group by equipt"; 
	$rs=$db->query($sql);
	$nm=$rs->num_rows;
	
	for($k=0;$k<$nm;$k++){
		$row=$rs->fetch_assoc();
		$equipt_count["Month_".$i]["Equipt_".$row['equipt_id']]+=$row['equipt_count'];
	}	
	
	
?>	
	<tr>
	<td align=center><?php echo strtoupper($month_heading); ?></td>
	<td align=center><?php echo $equipt_count["Month_".$i]["Equipt_105"]; ?></td>
	<td align=center><?php echo $equipt_count["Month_".$i]["Equipt_81"]; ?></td>
	<td align=center><?php echo $equipt_count["Month_".$i]["Equipt_118"]; ?></td>
	<td align=center><?php echo $equipt_count["Month_".$i]["Equipt_119"]; ?></td>
	<td align=center><?php echo $equipt_count["Month_".$i]["Equipt_64"]; ?></td>
	<td align=center><?php echo $equipt_count["Month_".$i]["Equipt_115"]; ?></td>
	<td align=center><?php echo $equipt_count["Month_".$i]["Equipt_89"]; ?></td>
	<td align=center><?php echo $equipt_count["Month_".$i]["Equipt_120"]; ?></td>
	<td align=center><?php echo $equipt_count["Month_".$i]["Equipt_123"]; ?></td>
	<td align=center><?php echo $equipt_count["Month_".$i]["Equipt_121"]; ?></td>
	<td align=center><?php echo $equipt_count["Month_".$i]["Equipt_116"]; ?></td>
	<td align=center><?php echo $equipt_count["Month_".$i]["Equipt_2"]; ?></td>
	<td align=center><?php echo $equipt_count["Month_".$i]["Equipt_122"]; ?></td>
	<td align=center><?php echo $equipt_count["Month_".$i]["Equipt_117"]; ?></td>
	</tr>	

<?php	
}


?>