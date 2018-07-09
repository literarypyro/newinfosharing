<?php
session_start();
?>
<?php
require_once("phpexcel/Classes/PHPExcel.php");
require_once("phpexcel/Classes/PHPExcel/IOFactory.php");
require("excel functions.php");

?>
<?php
ini_set("date.timezone","Asia/Kuala_Lumpur");
?>
<?php
function getEquiptCount($equipt,$onboard_date){
		
	$db=new mysqli("localhost","root","","transport");	


	
	$sqlCount="select *, equipt from incident_description inner join incident_cars on incident_description.incident_id=incident_cars.incident_id where incident_description.incident_id in (select incident_id from train_union where trainDate like '".$onboard_date."%%') and equipt='".$row['id']."' group by incident_cars.car_no";
	
//	$sql="select * from incident_description where incident_id in (select incident_id from train_union where trainDate like '".$onboard_date."%%') and equipt='".$equipt."'";
	$rs=$db->query($sqlCount);
	$nm=$rs->num_rows;

	return $nm;	
}
function getRemarks($equipt,$onboard_date){
	$db=new mysqli("localhost","root","","transport");	

	$remarks="";	

	$sql2="select * from incident_description inner join incident_report on incident_id=incident_report.id where incident_id in (select incident_id from train_union where trainDate like '".$onboard_date."%%') and incident_description.equipt='".$equipt."'";
	$rs2=$db->query($sql2);
	$nm2=$rs2->num_rows;
	for($n=0;$n<$nm2;$n++){
		$row2=$rs2->fetch_assoc();
		if($n==0){

			$remarks.="Car # ".$row2['car_no']." - See IN ".$row2['incident_no']; 
			
		}
		else {
			$remarks.=", Car # ".$row2['car_no']." - See IN ".$row2['incident_no']; 
		}
	}

	return $remarks;



}
?>
<?php
	if(isset($_GET['onboard_date'])){

		$onboard_date=$_GET['onboard_date'];
	

		$db=new mysqli("localhost","root","","transport");
		

		$sqlEquipt="select * from equipment where category='OB' order by equipment_name";
		$rs=$db->query($sqlEquipt);
		$nm=$rs->num_rows;
		for($i=0;$i<$nm;$i++){
			$row=$rs->fetch_assoc();
			$equipment["Equipment_".$row['id']]['name']=$row['equipment_name'];
			$equipment["Equipment_".$row['id']]['id']=$row['id'];


			$sqlCount="select *, equipt from incident_description inner join incident_cars on incident_description.incident_id=incident_cars.incident_id where incident_description.incident_id in (select incident_id from train_union where trainDate like '".$onboard_date."%%') and equipt='".$row['id']."' group by incident_cars.car_no";
			
			$countrs=$db->query($sqlCount);
			$countnm=$countrs->num_rows;

			$equipment["Equipment_".$row['id']]["nw_count"]=$countnm;

		}





		$sql="select * from equipment where type='RS' and category='OB' order by id";
		$rs=$db->query($sql);
		$nm=$rs->num_rows;
		
		$counter=15;
		for($i=0;$i<$nm;$i++){
			$row=$rs->fetch_assoc();

			$onboard[$i]["id"]=$row['id'];
			$onboard[$i]["counter"]=$counter;

			$onboard[$i]["sum"]=$equipment["Equipment_".$row['id']]["nw_count"];

			
			if($counter==26){
				$counter=50;
				
			}
			else {
				$counter++;
				
			}
			
		}
//		for($i=0;$i<count($onboard);$i++){
//			echo $onboard[$i]["id"].", Row ".$onboard[$i]["counter"].",Sum ".$onboard[$i]["counter"]."<br>";


//		}
		

		$filename="OnBoardEquipt.xls";

		$oldfilename="forms/".$filename;
		$dateSlip=date("Y-m-d His");
		$newFilename="printout/Onboard_".$dateSlip.".xls";
		copy($oldfilename,$newFilename);
		$workSheetName="Onboard Equipment";	
		$workbookname=$newFilename;
		$excel=loadExistingWorkbook($workbookname);
		
		$ExWs=createWorksheet($excel,$workSheetName,"openActive");

		addContent(setRange("R9","S9"),$excel,date("F d, Y",strtotime($onboard_date)),"true",$ExWs);

		addContent(setRange("R10","S10"),$excel,date("l",strtotime($onboard_date)),"true",$ExWs);	
		
		$timeTableSQL="select *,timetable_day.id as timeId from timetable_day inner join timetable_code on timetable_day.timetable_code=timetable_code.id where train_date like '".$onboard_date."%%'";

		$timeTableRS=$db->query($timeTableSQL);
		$timeTableNM=$timeTableRS->num_rows;
		if($timeTableNM>0){
			$timeTableRow=$timeTableRS->fetch_assoc();
			addContent(setRange("R11","S11"),$excel,$timeTableRow['code'],"true",$ExWs);
			
			
		}			
		
		
		
		$sqlCCDR="select * from train_availability inner join train_compo on train_availability.id=tar_id where train_availability.date like '".$onboard_date."%%' and status='active' and type='revenue' group by car_no";

		//$sqlCCDR="select * from train_availability where date like '".$ccdr_date."%%' and type='revenue' and status='active'";

		$sqlRS=$db->query($sqlCCDR);
		$sqlCCDRNM=$sqlRS->num_rows;

		$cars=$sqlCCDRNM;		
		
		
		for($i=0;$i<count($onboard);$i++){
			$provided=$cars-($onboard[$i]["sum"]*1); 
			if($provided<0){ $provided=0; }
			$sum=$onboard[$i]["sum"];
			$remarks="";
			if($sum>0){
			$remarks=getRemarks($onboard[$i]["id"],$onboard_date);
			
			}
	
			addContent(setRange("D".$onboard[$i]["counter"],"G".$onboard[$i]["counter"]),$excel,$provided,"true",$ExWs);
			addContent(setRange("H".$onboard[$i]["counter"],"K".$onboard[$i]["counter"]),$excel,$onboard[$i]["sum"],"true",$ExWs);
			addContent(setRange("L".$onboard[$i]["counter"],"Q".$onboard[$i]["counter"]),$excel,$remarks,"true",$ExWs);

			
			

		}
		


		$trainSQL="select * from train_availability inner join train_compo on train_availability.id=tar_id where train_availability.date like '".$onboard_date."%%' and status='active' and type='revenue' group by car_no";
		//$trainSQL="select * from train_availability inner join train_ava_time on train_availability.id=train_ava_id where date like '".$ccdr_date."%%' and status='active' and type='revenue' and insert_time is not null";
		$trainRS=$db->query($trainSQL);
		$trainNM=$trainRS->num_rows;
		$trainNM*=1;
		$lrv=$trainNM;


		$trainSQL="select * from train_availability inner join train_ava_time on train_availability.id=train_ava_id where date like '".$onboard_date."%%' and status='active' and type='unimog' and insert_time is not null";
		$trainRS=$db->query($trainSQL);
		$trainNM=$trainRS->num_rows;
		$trainNM*=1;
		$unimog=$trainNM;

		$trainSQL="select * from train_availability inner join train_ava_time on train_availability.id=train_ava_id where date like '".$onboard_date."%%' and status='active' and type='finance' and insert_time is not null";
		$trainRS=$db->query($trainSQL);
		$trainNM=$trainRS->num_rows;
		$trainNM*=1;
		$finance=$trainNM;

		$trainSQL="select * from train_availability inner join train_ava_time on train_availability.id=train_ava_id where date like '".$onboard_date."%%' and status='active' and type='test' and insert_time is not null";
		$trainRS=$db->query($trainSQL);
		$trainNM=$trainRS->num_rows;
		$trainNM*=1;
		$test=$trainNM;

		addContent(setRange("D60","D60"),$excel,$lrv,"true",$ExWs);
		addContent(setRange("D61","D61"),$excel,$finance,"true",$ExWs);
		addContent(setRange("D62","D62"),$excel,$test,"true",$ExWs);
		addContent(setRange("D63","D63"),$excel,$unimog,"true",$ExWs);


		$personnel_date=$onboard_date;

	$db2=new mysqli("localhost","root","","user_transport");
	$psql="select * from duty_personnel where personnel_date like '".$personnel_date."%%' and shift='3'";
	//echo $psql;
	$prs=$db2->query($psql);
	$pnm=$prs->num_rows;

	if($pnm>0){
		$prow=$prs->fetch_assoc();
		$recording=getTrainDriver($db,$prow['recording']);
		$clerk=getTrainDriver($db,$prow['clerk']);
		$duty_manager=getTrainDriver($db,$prow['duty_manager']);
				
		addContent(setRange("D70","G70"),$excel,$recording,"true",$ExWs);
		addContent(setRange("A70","B70"),$excel,$clerk,"true",$ExWs);
		addContent(setRange("J70","O70"),$excel,$duty_manager,"true",$ExWs);

				
				
			
	}	
	
	$signatorySQL="select * from signatories order by signatory_date DESC";
	$signatoryRS=$db2->query($signatorySQL);
	$signatoryNM=$signatoryRS->num_rows;

	if($signatoryNM>0){
		$signatoryRow=$signatoryRS->fetch_assoc();
		if(strtotime($personnel_date)>=strtotime($signatoryRow['signatory_date'])){
			$chief=$signatoryRow['chief_transport'];	
			$gm=$signatoryRow['general_manager'];
			$gm_office=$signatoryRow['gm_office'];
			$director=$signatoryRow['director_ops'];

			addContent(setRange("Q70","T70"),$excel,$chief,"false",$ExWs);
			addContent(setRange("B9","B9"),$excel,$gm,"false",$ExWs);
			addContent(setRange("B10","B10"),$excel,$gm_office,"false",$ExWs);
			addContent(setRange("H9","H9"),$excel,$director,"false",$ExWs);

			
		}
		else {
			$sig2="select * from signatories where signatory_date>'".$personnel_date."' order by signatory_date asc";

			$sigRS=$db2->query($sig2);
			$sigRow=$sigRS->fetch_assoc();
			
			$chief=$sigRow['chief_transport'];	
			$gm=$sigRow['general_manager'];
			$gm_office=$sigRow['gm_office'];
			$director=$sigRow['director_ops'];
			
			addContent(setRange("Q70","T70"),$excel,$chief,"false",$ExWs);
			addContent(setRange("B9","B9"),$excel,$gm,"false",$ExWs);
			addContent(setRange("B10","B10"),$excel,$gm_office,"false",$ExWs);
			addContent(setRange("H9","H9"),$excel,$director,"false",$ExWs);
			
		
		
		}
		
	
	}	
	


		
		save($ExWb,$excel,$newFilename); 	
		echo "Onboard Equipment has been generated!  Press right click and Save As: <a href='".$newFilename."'>Here</a>";


	}
	
	
	
	
	
	/*
	
	
	
	
	
	
	
	
	
	
	$oldfilename="forms/".$filename;
	$dateSlip=date("Y-m-d His");
	$newFilename="printout/Onboard_".$dateSlip.".xls";
	copy($oldfilename,$newFilename);
	$workSheetName="Onboard Equipment";	
	$workbookname=$newFilename;
	$excel=loadExistingWorkbook($workbookname);
	
  	$ExWs=createWorksheet($excel,$workSheetName,"openActive");
	
	*/	
	
	
	
	
	
	
	$rowCount=0;	
function getTrainDriver($db,$td_id){
	$sql="select * from train_driver where id='".$td_id."' limit 1";
	$rs=$db->query($sql);
	$row=$rs->fetch_assoc();
	
	$name=$row['firstName']." ".substr($row['midName'],0,1).". ".$row['lastName'];
	return $name;

}
	
?>