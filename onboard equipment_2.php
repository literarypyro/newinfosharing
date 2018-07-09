<style type='text/css'>
.rowClass {
	background-color:#eaf2d3;
}
.rowHeading {
	background-color:#a7c942;
	color:rgb(0,51,153);
}
.train_ava td{
	border: 1px solid #a7c942;
	color: rgb(0,51,153);
	cellpadding: 5px;

}
 .train_ava th {
	border: 1px solid #a7c942;
	cellpadding: 5px;
	
}
body {
	margin-left:30px;
	margin-right:30px;

}
input[type="text"]{ 
	height:25px; 
	font-weight:bold; 
	font-size:15px; 
	font-family:courier; 
	border: 1px solid #C6C6C6; 
	background-color: rgb(185, 201, 254);  
	color: rgb(0,51,153);
	border-radius: 3px;
}
#cellHeading {
	background-image: -o-linear-gradient(bottom, rgb(185, 201, 254) 38%, #4ad 62%);
	background-image: -moz-linear-gradient(bottom, rgb(185, 201, 254) 38%,#4ad 62%);
	background-image: -moz-linear-gradient(bottom, rgb(185, 201, 254) 38%, #4ad 62%);
	background-image: -webkit-gradient(linear, left bottom, left top, color-stop(0.38, rgb(185, 201, 254)), color-stop(0.62, #4ad));
	background-image: -webkit-linear-gradient(bottom, rgb(185, 201, 254) 38%,#4ad 62%);
	background-image: -ms-linear-gradient(bottom, rgb(185, 201, 254) 38%, #4ad 62%);
	background-image: linear-gradient(bottom, rgb(185, 201, 254) 38%, #4ad 62%);

	background-color: rgb(185, 201, 254);  

	color: rgb(0,51,153);
	padding:5px;
	-moz-border-radius: 5px;
	border-radius: 5px;

}
input[type="text"]:focus {
	background-color:rgb(158,27,32);
	color:white;

}
textarea:focus {
	background-color:rgb(158,27,32);
	color:white;
	font-weight:bold;

}
.date {
	text-style:bold;
	font-size:20px;

}
textarea{ 
	border: 1px solid rgb(185, 201, 254);
	background-color: rgb(185, 201, 254);  
	color: rgb(0,51,153);
	border-radius: 3px;
}
#add_form th{
background-color: #4ad;  
}

#add_form td:nth-child(odd) {
background-color: #33aa55; 
color:white;
font-weight:bold;
padding:5px;

}
#add_form td:last-child{
background-color:white;

}


#add_form td:nth-child(even) {
background-color: rgb(185, 201, 254);  
border:1px solid #4ad;

}


select { border: 1px solid rgb(185, 201, 254); color: rgb(0,51,153); background-color: rgb(185, 201, 254);  }
</style>
<body>
<?php
require("monitoring menu.php");
?>
<br>
<br>
<form action='onboard equipment.php' method='post'>
<select name='month'>

<?php
$mm=date("m");
$yy=date("Y");
$dd=date("d");

$hh=date("h");

$min=date("i");
$aa=date("a");

for($i=1;$i<13;$i++){
?>
	<option value='<?php echo $i; ?>' 
	<?php
	if($i==$mm){
		echo "selected";
	}
	?>
	>
	<?php
	echo date("F",strtotime(date("Y")."-".$i."-01"));
	?>
	</option>
<?php
}
?>
</select>
<select name='day'>
<?php
for($i=1;$i<=31;$i++){
?>
	<option value='<?php echo $i; ?>' 
	<?php
	if($i==$dd){
		echo "selected";
	}
	?>		
	>
	<?php
	
	echo $i;
	?>
	</option>
<?php
}
?>
</select>
<select name='year'>
<?php
$dateRecent=date("Y")*1+16;
for($i=1999;$i<=$dateRecent;$i++){
?>
	<option value='<?php echo $i; ?>' 
	<?php
	if($i==$yy){
		echo "selected";
	}
	?>		
	>
	<?php
	echo $i;
	?>
	</option>
<?php
}
?>
</select>
<input type=submit value='Access Monitoring' />
</form>
<br>
<?php
if(isset($_POST['month'])){

?>
<?php

$month=$_POST['month'];
$day=$_POST['day'];
$year=$_POST['year'];

$ccdr_date=date("Y-m-d",strtotime($year."-".$month."-".$day));
$ccdr_label=date("F d, Y",strtotime($ccdr_date));

$db=new mysqli("localhost","root","","transport");


$sqlCCDR="select * from train_availability inner join train_compo on train_availability.id=tar_id where train_availability.date like '".$ccdr_date."%%' and status='active' and type='revenue' group by car_no";

//$sqlCCDR="select * from train_availability where date like '".$ccdr_date."%%' and type='revenue' and status='active'";

$sqlRS=$db->query($sqlCCDR);
$sqlCCDRNM=$sqlRS->num_rows;

$cars=$sqlCCDRNM;

$sqlEquipt="select * from equipment where category='OB' order by equipment_name";
$rs=$db->query($sqlEquipt);
$nm=$rs->num_rows;
for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
	$equipment["Equipment_".$row['id']]['name']=$row['equipment_name'];
	$equipment["Equipment_".$row['id']]['id']=$row['id'];


	$sqlCount="select *, equipt from incident_description inner join incident_cars on incident_description.incident_id=incident_cars.incident_id where incident_description.incident_id in (select incident_id from train_union where trainDate like '".$ccdr_date."%%') and equipt='".$row['id']."' group by incident_cars.car_no";
	
	$countrs=$db->query($sqlCount);
	$countnm=$countrs->num_rows;

	$equipment["Equipment_".$row['id']]["nw_count"]=$countnm;

}

//$sql="SELECT count(*) as equipt_count,equipt FROM incident_report inner join where incident_date like '".$ccdr_date."%%' group by equipt";
//$sql="select count(*) as equipt_count, equipt from incident_description where incident_id in (select incident_id from train_union where trainDate like '".$ccdr_date."%%') and equipt in (select id from equipment where category='OB') group by car_no,equipt";


/*
$rs=$db->query($sql);
$nm=$rs->num_rows;
for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
	$equipment["Equipment_".$row['equipt']]["nw_count"]=$row['equipt_count'];


}
*/
?>

<table width=100%  class='train_ava'>
<tr class='rowHeading'>
<th rowspan=2><?php echo $ccdr_label; ?></th>
<th colspan=2>No. Of Cars</th>
<th rowspan=2>Remarks</th>
</tr>
<tr class='rowHeading'>
<th>Operational</th>
<th>With Defect</th>
</tr>
<?php
$rs=$db->query($sqlEquipt);
$nm=$rs->num_rows;
for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
	$count=$cars;
?>
<tr>
	<td><?php echo $equipment["Equipment_".$row['id']]['name']; ?></td>
	<td align=center>
	<?php 
	$provided=$cars-($equipment["Equipment_".$row['id']]['nw_count']*1); 
	
	if($provided<0){ $provided=0; }
	echo $provided;
	?>
	</td>
	<td align=center><?php echo $equipment["Equipment_".$row['id']]['nw_count']*1; ?></td>
	<td>
	<?php
	$nw_count=$equipment["Equipment_".$row['id']]['nw_count'];
	if($nw_count>0){



//		$sql2="SELECT * FROM incident_report where incident_date like '".$ccdr_date."%%' and equipt='".$equipment["Equipment_".$row['id']]['id']."'";
		$sql2="select * from incident_description inner join incident_report on incident_id=incident_report.id where incident_id in (select incident_id from train_union where trainDate like '".$ccdr_date."%%') and incident_description.equipt='".$equipment["Equipment_".$row['id']]['id']."'";
//		echo $sql2;
		$rs2=$db->query($sql2);
		$nm2=$rs2->num_rows;
		for($n=0;$n<$nm2;$n++){
			$row2=$rs2->fetch_assoc();
			if($n==0){

			echo "<a href='#' onclick='window.open(\"incident details.php?ir=".$row2['incident_id']."\")'>Car # ".$row2['car_no']." - See IN ".$row2['incident_no']."</a>"; 
			
			}
			else {
				echo ", <a href='#' onclick='window.open(\"incident details.php?ir=".$row2['incident_id']."\")'>Car # ".$row2['car_no']." - See IN ".$row2['incident_no']."</a>"; 
			}
		?>
			<br>	
		<?php
		}
	}
	else {
		echo "&nbsp;";
	}
	?>
	</td>	
</tr>
<?php
}
?>
</table>
<br>
<br>
<table class='train_ava'>
<tr>
<?php

$trainSQL="select * from train_availability inner join train_compo on train_availability.id=tar_id where train_availability.date like '".$ccdr_date."%%' and status='active' and type='revenue' group by car_no";
//$trainSQL="select * from train_availability inner join train_ava_time on train_availability.id=train_ava_id where date like '".$ccdr_date."%%' and status='active' and type='revenue' and insert_time is not null";
$trainRS=$db->query($trainSQL);
$trainNM=$trainRS->num_rows;
$trainNM*=1;
$lrv=$trainNM;


$trainSQL="select * from train_availability inner join train_ava_time on train_availability.id=train_ava_id where date like '".$ccdr_date."%%' and status='active' and type='unimog' and insert_time is not null";
$trainRS=$db->query($trainSQL);
$trainNM=$trainRS->num_rows;
$trainNM*=1;
$unimog=$trainNM;

$trainSQL="select * from train_availability inner join train_ava_time on train_availability.id=train_ava_id where date like '".$ccdr_date."%%' and status='active' and type='finance' and insert_time is not null";
$trainRS=$db->query($trainSQL);
$trainNM=$trainRS->num_rows;
$trainNM*=1;
$finance=$trainNM;

$trainSQL="select * from train_availability inner join train_ava_time on train_availability.id=train_ava_id where date like '".$ccdr_date."%%' and status='active' and type='test' and insert_time is not null";
$trainRS=$db->query($trainSQL);
$trainNM=$trainRS->num_rows;
$trainNM*=1;
$test=$trainNM;

?>
<td class=''>
Number of LRV Used:
</td>
<td><?php echo $lrv; ?></td>
</tr>
<tr>
<td>Finance Train</td>
<td><?php echo $finance; ?></td>
</tr>
<tr>
<td>Test Train</td>
<td><?php echo $test; ?></td>
</tr>
<tr>
<td>UNIMOG</td>
<td><?php echo $unimog; ?></td>
</tr>

</table>
<br>
<a href='#' onclick='window.open("generate_onboard.php?onboard_date=<?php echo $ccdr_date; ?>");'>Generate Printout</a>


<?php
}
?>
</body>
