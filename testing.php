<?php
if(isset($_POST['index_no'])){

	$index_no=$_POST['index_no'];
	$lpam_id=$_POST['lpam_id'];

	$type=$_POST['type'];
	
	$car_a=$_POST['car_1'];
	$car_b=$_POST['car_2'];

	$car_c=$_POST['car_3'];
	
	$car_d=$_POST['car_4'];
	
	
	
	$year=$_POST['year'];
	$month=$_POST['month'];
	$day=$_POST['day'];
	
	$hour=$_POST['hour'];
	$minute=$_POST['minute'];
	$amorpm=$_POST['amorpm'];

	if($amorpm=="pm"){
		if($hour<12){
			$hour+=12;
			
		}
		else {
		}
	
	}
	else {
		if($hour=="12"){
			$hour=0;
			
		}
	
	}
	
		$availability_date=date("Y-m-d H:i",strtotime($year."-".$month."-".$day." ".$hour.":".$minute));

	$update="insert into train_availability(index_no,date,car_a,car_b,car_c,car_c,lpam_id,status,type) values ";
	$update.="('".$index_no."','".$availability_date."','".$car_a."','".$car_b."','".$car_c."','".$car_d."','".$lpam_id."','active','".$type."')";			
	echo "<script>alert('s');</script>";
	echo $update;
	
}
?>