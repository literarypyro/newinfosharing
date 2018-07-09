<?php
session_start();
?>
<?php
$db=new mysqli("localhost","root","","transport");

$db2=new mysqli("localhost","root","","external");
$db3=new mysqli("localhost","root","","timetable");
$db4=new mysqli("localhost","root","","user_transport");

?>
<?php
if(isset($_GET['removeEquipt'])){
	$equipt=$_GET['removeEquipt'];
	$sql="delete from temp_multiple where id='".$equipt."'";
	$update=$db2->query($sql);	



}
if(isset($_GET['retrieveAdditional'])){

	$sql="select * from temp_multiple";
	$rs=$db2->query($sql);
	$nm=$rs->num_rows;
	if($nm>0){
		for($i=0;$i<$nm;$i++){
			$row=$rs->fetch_assoc();
			
			$equipment_name="";
			$sub_item="";

			$equiptSQL="select * from equipment where id='".$row['equipt_id']."'";
			$equiptRS=$db->query($equiptSQL);
			
			$equiptRow=$equiptRS->fetch_assoc();
			$equipment_name=$equiptRow['equipment_name'];
			
			
			if($row['sub_item_id']==""){
			}
			else {
				$subitemSQL="select * from sub_item where id='".$row['sub_item_id']."'";
				$subitemRS=$db->query($subitemSQL);
				$subitemNM=$subitemRS->num_rows;
				
				if($subitemNM>0){
					$subitemRow=$subitemRS->fetch_assoc();
					$sub_item=$subitemRow['sub_item'];
				}
			}

			echo $equipment_name.",".$sub_item.";";	
		}

	}
	else {
		echo "No data available";
	}
}

if(isset($_GET['debugDefects'])){
	$incident_id=$_GET['debugDefects'];
	
	$sql="delete from temp_multiple";
	$rs=$db2->query($sql);
	
	$sql="insert into temp_multiple(equipt_id,sub_item_id) (select equipt_id,sub_item_id from incident_defects where incident_id='".$incident_id."')";
	$rs=$db2->query($sql);


}



if(isset($_GET['trainDriver'])){
	$sql="select * from train_driver where position in ('TD','STDO') order by lastName";
	$rs=$db->query($sql);
	
	$nm=$rs->num_rows;
	if($nm>0){
		for($i=0;$i<$nm;$i++){
			$row=$rs->fetch_assoc();
			echo $row['id'].";";
			echo str_replace("Ñ","_ENYE_",$row['lastName']).", ".$row['firstName']."==>";
		}
	
	}
	else {
		echo "No data available";
	}
}

if(isset($_GET['supDriver'])){
	$sql="select * from train_driver where position in ('SUP') order by lastName";
	$rs=$db->query($sql);
	
	$nm=$rs->num_rows;
	if($nm>0){
		for($i=0;$i<$nm;$i++){
			$row=$rs->fetch_assoc();
			echo $row['id'].";";
			echo str_replace("Ñ","_ENYE_",$row['lastName']).", ".$row['firstName']."==>";
		}
	
	}
	else {
		echo "No data available";
	}
}


if(isset($_GET['supervisor'])){
	$sql="select * from train_driver where position in ('STDO') order by lastName";
	$rs=$db->query($sql);
	
	$nm=$rs->num_rows;
	if($nm>0){
		for($i=0;$i<$nm;$i++){
			$row=$rs->fetch_assoc();
			echo $row['id'].";";
			echo $row['lastName'].", ".$row['firstName']."==>";
		}
	
	}
	else {
		echo "No data available";
	}
}

if(isset($_GET['received_by'])){
	$sql="select * from train_driver where position in ('STDO') order by lastName";
	$rs=$db->query($sql);
	
	$nm=$rs->num_rows;
	if($nm>0){
		for($i=0;$i<$nm;$i++){
			$row=$rs->fetch_assoc();
			echo $row['id'].";";
			echo $row['lastName'].", ".$row['firstName'].", ".$row['position']."==>";
		}
	
	}
	else {
		echo "No data available";
	}
}
if(isset($_GET['scrollRolling'])){
	$sql="select * from equipment_type where equipment_code='".$_GET['scrollRolling']."'";
	$rs=$db->query($sql);
	
	$row=$rs->fetch_assoc();
	
	$incident_code=$row['incident_code'];
	
	$sql="select * from equipment where type='".$incident_code."' order by equipment_name";
//	if(($incident_code=="RS")||($incident_code=="PWR")){
	if($incident_code=="PWR"){
		$sql="select * from equipment where category='".$_GET['category']."' order by equipment_name";
		
	}
	
	
	$rs=$db->query($sql);
	$nm=$rs->num_rows;
	
	if($nm>0){
		for($i=0;$i<$nm;$i++){
			$row=$rs->fetch_assoc();
				
			echo $row['id'].";";
			echo $row['equipment_name']."==>";
		
		}
	}
	else {
		echo "No data available";
	
	}

}

if(isset($_GET['scrollOthers'])){
	$sql="select * from other_problem order by problem";
	$rs=$db->query($sql);
	$nm=$rs->num_rows;
	if($nm>0){
		for($i=0;$i<$nm;$i++){
			$row=$rs->fetch_assoc();
				
			echo $row['id'].";";
			echo $row['problem']."==>";
		
		}
	}
	else {
		echo "No data available";
	
	}

}



if(isset($_GET['scrollSubItem'])){
	$sql="select * from sub_item where equipment_id='".$_GET['scrollSubItem']."' order by sub_item";
	$rs=$db->query($sql);
	
	$nm=$rs->num_rows;
	
	if($nm>0){
		for($i=0;$i<$nm;$i++){
			$row=$rs->fetch_assoc();
				
			echo $row['id'].";";
			echo $row['sub_item']."==>";
		
		}
	}
	else {
		echo "No data available";

	}
}

if(isset($_GET['getCars'])){
	
	$sql="select * from train_incident_report inner join train_availability on train_incident_report.train_ava_id=train_availability.id where incident_id='".$_GET['getCars']."'";
	$rs=$db->query($sql);
	
	$nm=$rs->num_rows;
	
	if($nm>0){
	
		for($i=0;$i<$nm;$i++){
			$row=$rs->fetch_assoc();
			
			echo $row['car_a'].";";
			echo $row['car_b'].";";
			echo $row['car_c'].";";
		
		}
	}
	else {
		echo "No data available";
	}

}


if(isset($_GET['deleteSwitch'])){
	$sql="delete from train_switch where id='".$_GET['deleteSwitch']."'";
	
	$rs=$db->query($sql);
	echo "Data deleted.";
	


}
if(isset($_GET['checkCar'])){
	$year=$_SESSION['year'];
	$month=$_SESSION['month'];
	$day=$_SESSION['day'];
	
	$availability_date_code=date("Y-m-d",strtotime($year."-".$month."-".$day));

	$sql="select * from train_availability inner join train_ava_time on train_availability.id=train_ava_time.train_ava_id where (car_a='".$_GET['checkCar']."' or car_b='".$_GET['checkCar']."' or car_c='".$_GET['checkCar']."') and remove_time is null and status='active' and date like '".$availability_date_code."%%'";
	$rs=$db->query($sql);
	$nm=$rs->num_rows;
	if($nm>0){
		if($_GET['checkCar']==""){
			echo "No car";
		}
		else  {
		echo $_GET['car'];
		}
	}
	else {
		echo "No car";
	}
}

if(isset($_GET['removeRow'])){
	//Delete all Associations
	
	//Main Tables: train_ava_time, incident_report, train_switch

	
	
	$update="delete from train_ava_time where train_ava_id='".$_GET['removeRow']."'";
	$rs=$db->query($update);
	
	$update="delete from train_switch where train_ava_id='".$_GET['removeRow']."'";
	$rs=$db->query($update);
	

	//Train Compo
	$update="delete from train_compo where tar_id='".$_GET['removeRow']."'";
	$rs=$db->query($update);
	
	//From Incident Report: incident_description,incident_no,level,service_interruption

	$search="select * from train_incident_report where train_ava_id='".$_GET['removeRow']."'";
	$srs=$db->query($search);
	$snm=$srs->num_rows;
	
	if($snm>0){
		for($i=0;$i<$snm;$i++){
			$srow=$srs->fetch_assoc();
			clearIncidentRecords($srow['incident_id']);	
			$update="delete from incident_report where id='".$srow['incident_id']."'";
			$rs=$db->query($update);
		
	
		}
	
	}

	//delete main table: train_availability
	$update="delete from train_availability where id='".$_GET['removeRow']."'";
	$rs=$db->query($update);
	
	echo "Data deleted";
}

if(isset($_GET['removeIncident'])){
	$incident_no=$_GET['removeIncident'];
	clearIncidentRecords($incident_no);
	$update="delete from incident_report where id='".$incident_no."'";
	$rs=$db->query($update);

}
if(isset($_GET['removeTimetableHour'])){
	$hour_id=$_GET['removeTimetableHour'];
	$timetable_id=$_GET['timetable_id'];
	
	$update="delete from timetable_hour where id='".$hour_id."'";
	$rs=$db3->query($update);
	
	echo $timetable_id;

}

function clearIncidentRecords($incident){
	$db=new mysqli("localhost","root","","transport");

	$update="delete from incident_description where incident_id='".$incident."'";
	$rs=$db->query($update);
	
	$update="delete from incident_no where incident_id='".$incident."'";
	$rs=$db->query($update);

	$update="delete from level where incident_id='".$incident."'";
	$rs=$db->query($update);

	$update="delete from service_interruption where incident_id='".$incident."'";
	$rs=$db->query($update);

	$update="delete from incident_cars where incident_id='".$incident."'";
	$rs=$db->query($update);
	
}

if(isset($_GET['removeClearance'])){
	
	$clearance_id=$_GET['removeClearance'];
	$clearance_date=$_GET['removeDate'];

	
	$update="delete from clearance where clearance_no='".$clearance_id."' and date='".$clearance_date."'";
	$rs=$db->query($update);
	echo "Data deleted";

}

if(isset($_GET['removeInterruption'])){
	$interruption_id=$_GET['removeInterruption'];
	
	$update="delete from service_interruption where id='".$interruption_id."'";
	$rs=$db->query($update);
	
	echo "Data deleted";
}
if(isset($_GET['ph_trams'])){
	$sql="select * from ph_trams order by lastName";
	$rs=$db->query($sql);
	
	$nm=$rs->num_rows;
	if($nm>0){
		for($i=0;$i<$nm;$i++){
			$row=$rs->fetch_assoc();
			echo $row['id'].";";
			echo $row['lastName'].", ".$row['firstName']."==>";
		}
	
	}
	else {
		echo "No data available";
	}	


}
if(isset($_GET['checkIncidentNo'])){

	$year=$_GET['year'];
	$incident_no=$_GET['checkIncidentNo'];

	$sql="select * from incident_no where incident_number='".$incident_no."' and year='".$year."'";
	$rs=$db4->query($sql);
	$nm=$rs->num_rows;
	if($nm>0){
		echo $_GET['checkIncidentNo'];
	}
	else {
		echo "No number";
	}
}
?>
