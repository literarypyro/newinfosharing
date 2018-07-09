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
function getTrainDriver($db,$td_id){
	$sql="select * from train_driver where id='".$td_id."' limit 1";
	$rs=$db->query($sql);
	$row=$rs->fetch_assoc();
	
	$name=$row['firstName']." ".substr($row['midName'],0,1).". ".$row['lastName'];
	return $name;

}
function getTrainDriver2($db,$td_id){
	$sql="select * from train_driver where id='".$td_id."' limit 1";
	$rs=$db->query($sql);
	$nm=$rs->num_rows;
	if($nm>0){
	$row=$rs->fetch_assoc();
	
	$name=substr($row['firstName'],0,1).". ".substr($row['midName'],0,1).". ".$row['lastName'];
	}
	else {
	$name="";
	
	}
	return $name;

}


function getStats($problem_type,$availability_date,$dbase){
//	$db=new mysqli("localhost","root","","transport");
//	$incident_sql="select count(*) as level_count,level from train_incident_view where incident_type='".$problem_type."' and train_ava_id in (select id from train_availability where date like '".$availability_date."%%' and status='active') group by level";


	if($problem_type=="rolling"){
		$incident_sql="select count(*) as level_count,level from incident_report where incident_type in ('rolling','unload','nload') and incident_date like '".$availability_date."%%' group by level";
	}
	else {	
		$incident_sql="select count(*) as level_count,level from incident_report where incident_type='".$problem_type."' and incident_date like '".$availability_date."%%' group by level";
	}
	
	
	$incident_rs=$dbase->query($incident_sql);
	$incident_nm=$incident_rs->num_rows;
	for($n=0;$n<$incident_nm;$n++){
		$incident_row=$incident_rs->fetch_assoc();
		$incident[$incident_row['level']]=$incident_row['level_count'];
	
	}
	return $incident;

}
function getCondition($problem_type,$availability_date,$dbase){
//	$db=new mysqli("localhost","root","","transport");
//	$incident_sql="select count(*) as level_count,level,level_condition from train_incident_view where incident_type='".$problem_type."' and train_ava_id in (select id from train_availability where date like '".$availability_date."%%' and status='active') group by level,level_condition";


	if($problem_type=="rolling"){
		$incident_sql="select count(*) as level_count,level,level_condition from incident_report where incident_type in ('rolling','unload','nload') and incident_date like '".$availability_date."%%' group by level,level_condition";
	}
	else {	
	
		$incident_sql="select count(*) as level_count,level,level_condition from incident_report where incident_type='".$problem_type."' and incident_date like '".$availability_date."%%' group by level,level_condition";
	}

	$incident_rs=$dbase->query($incident_sql);
	$incident_nm=$incident_rs->num_rows;
	
	
	
	for($n=0;$n<$incident_nm;$n++){
		$incident_row=$incident_rs->fetch_assoc();
		if($incident_row['level_condition']==""){
		
		}
		else {
			$condition[$incident_row['level']]["condition_".$incident_row['level_condition']]=$incident_row['level_count'];

		}
	
	}
	return $condition;

}
?>



<?php
if(isset($_GET['sccdr'])){
	$sccdr_date=$_GET['sccdr'];

	$filename="SCCDR.xls";

	$oldfilename="forms/".$filename;
	$dateSlip=date("Y-m-d His");
	$newFilename="printout/SCCDR_".$dateSlip.".xls";
	copy($oldfilename,$newFilename);
	$workSheetName="SCCDR";	
	$workbookname=$newFilename;
	$excel=loadExistingWorkbook($workbookname);

  	$ExWs=createWorksheet($excel,$workSheetName,"openActive");
	
//	$category=array('rolling','station_equipt','signaling','power','afc','depot_equipt','communication','tracks','cc_equipt','gradual','c_loops','r_trains');
	$category=array('rolling','station_equipt','signaling','power','afc','depot_equipt','communication','tracks','cc_equipt');


		
	$rowCount=17;	
	

	
	
	
	
	
	addContent(setRange("R9","T9"),$excel,date("F d, Y",strtotime($sccdr_date)),"true",$ExWs);

	addContent(setRange("R8","T8"),$excel,date("l",strtotime($sccdr_date)),"true",$ExWs);	
	
	$db=new mysqli("localhost","root","","transport");




	$timeTableSQL="select *,timetable_day.id as timeId from timetable_day inner join timetable_code on timetable_day.timetable_code=timetable_code.id where train_date like '".$sccdr_date."%%'";

	$timeTableRS=$db->query($timeTableSQL);
	$timeTableNM=$timeTableRS->num_rows;
	if($timeTableNM>0){
		$timeTableRow=$timeTableRS->fetch_assoc();
		addContent(setRange("R10","T10"),$excel,$timeTableRow['code'],"true",$ExWs);
		
		
	}		
	
	
	
	
	
	
	
	for($i=0;$i<count($category);$i++){

		$stats=getStats($category[$i],$sccdr_date,$db);
		$condition=getCondition($category[$i],$sccdr_date,$db);
		
		
		if($condition['3']['condition_1']==""){
			$condition_1="'0/";
		
		}
		else {
			$condition_1="'".($condition['3']['condition_1']*1)."/";
		
		}
		

		if($condition['4']['condition_3']==""){
			$condition_3="";
		
		}
		else {
			$condition_3="'".($condition['4']['condition_3']*1)."/";
		
		}
		if($category[$i]=="rolling"){
			$am_sql="select sum(cancel) as pm_count from incident_report where incident_type in ('rolling','unload','nload') and incident_date like '".$sccdr_date."%%'  and level in ('3')";


		}
		else {
			$am_sql="select sum(cancel) as pm_count from incident_report where incident_type='".$category[$i]."' and incident_date like '".$sccdr_date."%%'  and level in ('3')";
		}
		$am_rs=$db->query($am_sql);
		$am_nm=$am_rs->num_rows;

		if($am_nm>0){
			$am_row=$am_rs->fetch_assoc();
			if($category[$i]=="rolling"){
			$stats['3']=$am_row['pm_count']*1;
			}
			else {
			$stats['3']=0;
			}

		}

		if($problem_type=="rolling"){
			$am_sql="select sum(cancel) as pm_count from incident_report where incident_type in ('rolling','unload','nload') and incident_date like '".$sccdr_date."%%'  and level in ('4')";
		}
		else {
			$am_sql="select sum(cancel) as pm_count from incident_report where incident_type='".$category[$i]."' and incident_date like '".$sccdr_date."%%'  and level in ('4')";
		}

		$am_rs=$db->query($am_sql);
		$am_nm=$am_rs->num_rows;

		if($am_nm>0){
			$am_row=$am_rs->fetch_assoc();
			if($category[$i]=="rolling"){
			$stats['4']=$am_row['pm_count']*1;
			}
		}

		
		
		addContent(setRange("B".$rowCount,"B".$rowCount),$excel,$stats['1'],"true",$ExWs);
		addContent(setRange("C".$rowCount,"C".$rowCount),$excel,$stats['2'],"true",$ExWs);
		addContent(setRange("D".$rowCount,"E".$rowCount),$excel,$condition_1.$stats['3'],"true",$ExWs);
		addContent(setRange("F".$rowCount,"G".$rowCount),$excel,$condition_3.$stats['4'],"true",$ExWs);
		
		
	
	
		$rowCount++;
	}	
	
	$incident_sql="select * from train_incident_view where level_condition='3' and train_ava_id in (select id from train_availability where date like '".$sccdr_date."%%')";

	$incident_rs=$db->query($incident_sql);
	$incident_nm=$incident_rs->num_rows;
	
	if($incident_nm>0){
	
		addContent(setRange("A12","U12"),$excel,"With service interruption.","true",$ExWs);
	
	}
	else {
	
		addContent(setRange("A12","U12"),$excel,"No service interruption.","true",$ExWs);
	
	}

	
	
	$availability_date=$sccdr_date;
	
	$db=new mysqli("localhost","root","","transport");
	
	//$am_sql="select count(*) as am_count from train_availability where date like '".$availability_date."%%' and status='cancelled' and date between '".$availability_date." 00:00:01' and '".$availability_date." 12:00:00'";

	$am_sql="select sum(cancel) as am_count from incident_report inner join incident_description on incident_report.id=incident_description.incident_id where incident_date between '".$availability_date." 00:00:00' and '".$availability_date." 12:00:00' and level='3' and cancel>=1 and incident_type in ('rolling')";




	$am_rs=$db->query($am_sql);
	$am_nm=$am_rs->num_rows;

	$am=0;
	if($am_nm>0){
		$am_row=$am_rs->fetch_assoc();
		$am=$am_row['am_count'];
	}
	
	$car_sql3="select sum(cancel) as cancel from incident_report where incident_date between '".$availability_date." 00:00:01' and '".$availability_date." 12:00:00' and incident_type in ('gradual','c_loops')";

	//		echo $car_sql3;
	$car_rs3=$db->query($car_sql3);
	$car_nm3=$car_rs3->num_rows;
	if($car_nm3>0){
		$car_row3=$car_rs3->fetch_assoc();
		$am+=$car_row3['cancel']*1;

	}

	

	//$am_sql="select count(*) as pm_count from train_availability where date like '".$availability_date."%%' and status='cancelled' and date between '".$availability_date." 12:00:01' and '".$availability_date." 23:59:59'";

	$am_sql="select sum(cancel) as pm_count from incident_report inner join incident_description on incident_report.id=incident_description.incident_id where incident_date between '".$availability_date." 12:00:01' and '".$availability_date." 23:59:59' and level='3' and cancel>=1 and incident_type in ('rolling')";

	
	$am_rs=$db->query($am_sql);
	$am_nm=$am_rs->num_rows;

	$pm=0;
	if($am_nm>0){
		$am_row=$am_rs->fetch_assoc();
		$pm=$am_row['pm_count'];
	}

	$car_sql3="select sum(cancel) as cancel from incident_report where incident_date between '".$availability_date." 12:00:01' and '".$availability_date." 23:59:59' and incident_type in ('gradual','c_loops')";

	//		echo $car_sql3;
	$car_rs3=$db->query($car_sql3);
	$car_nm3=$car_rs3->num_rows;
	if($car_nm3>0){
		$car_row3=$car_rs3->fetch_assoc();
		$pm+=$car_row3['cancel']*1;

	}
	
	

//	$am_sql="select sum(cancel) as am_count from train_incident_view where train_ava_id in (select id from train_availability where date like '".$availability_date."%%' and status='active') and incident_date between '".$availability_date." 00:00:01' and '".$availability_date." 12:00:00'";
//	$am_sql="select sum(cancel) as am_count from train_incident_view where train_ava_id in (select id from train_availability where date like '".$availability_date."%%') and incident_date between '".$availability_date." 00:00:01' and '".$availability_date." 12:00:00'";
	$am_sql="select sum(cancel) as am_count from incident_report where incident_date between '".$availability_date." 00:00:01' and '".$availability_date." 12:00:00'  and level in ('3','4')";


	$am_rs=$db->query($am_sql);
	$am_nm=$am_rs->num_rows;

	$am_cancel=0;
	if($am_nm>0){
		$am_row=$am_rs->fetch_assoc();
		$am_cancel=$am_row['am_count']*1;
	}

//	$pm_sql="select sum(cancel) as pm_count from train_incident_view where train_ava_id in (select id from train_availability where date like '".$availability_date."%%' and status='active') and incident_date between '".$availability_date." 12:00:01' and '".$availability_date." 00:00:00'";
	//$pm_sql="select sum(cancel) as pm_count from train_incident_view where train_ava_id in (select id from train_availability where date like '".$availability_date."%%') and incident_date between '".$availability_date." 12:00:01' and '".$availability_date." 00:00:00'";

	$pm_sql="select sum(cancel) as pm_count from incident_report where incident_date between '".$availability_date." 12:00:01' and '".$availability_date." 23:59:59'  and level in ('3','4')";
	
	$pm_rs=$db->query($pm_sql);
	$pm_nm=$pm_rs->num_rows;

	$pm_cancel=0;
	if($pm_nm>0){
		$pm_row=$pm_rs->fetch_assoc();
		$pm_cancel=$pm_row['pm_count']*1;
	}

	
	$planned=0;
	$actual=0;
	$percentage=0;

	$planned_sql="select * from timetable_day inner join timetable_code on timetable_code=timetable_code.id where train_date='".$availability_date."'";
	$planned_rs=$db->query($planned_sql);
	$planned_nm=$planned_rs->num_rows;
	if($planned_nm>0){

		$planned_row=$planned_rs->fetch_assoc();
		//$am_sql="select sum(cancel) as cancel from train_incident_view where train_ava_id in (select id from train_availability where date like '".$availability_date."%%' and status='active')";
		$am_sql="select sum(cancel) as cancel from incident_report where incident_date like '".$availability_date."%%' and incident_type in ('rolling','gradual','c_loops','r_trains','unload','nload')";

		$am_rs=$db->query($am_sql);
		$am_nm=$am_rs->num_rows;
		$am_row=$am_rs->fetch_assoc();
		$planned=$planned_row['planned_loops'];
		$actual=$planned_row['planned_loops']*1-$am_row['cancel']*1;

		
		$percentage=$actual/$planned;
		
		
	}	
	
	$train_sql="select * from train_availability inner join train_compo on train_availability.id=tar_id where train_availability.date like '".$availability_date."%%' and status='active' group by car_no";

//	$train_sql="select * from train_availability where date like '".$availability_date."%%' and status='active'";
	$train_rs=$db->query($train_sql);
	$train_nm=$train_rs->num_rows;

	addContent(setRange("Q19","Q19"),$excel,$am,"true",$ExWs);
	addContent(setRange("R19","R19"),$excel,$am_cancel,"true",$ExWs);

	addContent(setRange("S19","S19"),$excel,$pm,"true",$ExWs);
	addContent(setRange("T19","T19"),$excel,$pm_cancel,"true",$ExWs);	
	
	addContent(setRange("Q21","R21"),$excel,$planned,"true",$ExWs);
	addContent(setRange("S21","T21"),$excel,$actual,"true",$ExWs);	
	
	addContent(setRange("Q23","R23"),$excel,$percentage,"true",$ExWs);
	addContent(setRange("S23","T23"),$excel,$train_nm,"true",$ExWs);	

	$personnel_date=$sccdr_date;

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
				
		addContent(setRange("D36","G36"),$excel,$recording,"true",$ExWs);
		addContent(setRange("A36","B36"),$excel,$clerk,"true",$ExWs);
		addContent(setRange("J36","O36"),$excel,$duty_manager,"true",$ExWs);

				
				
			
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
						
			addContent(setRange("Q36","T36"),$excel,$chief,"false",$ExWs);
			addContent(setRange("B8","B8"),$excel,$gm,"false",$ExWs);
			addContent(setRange("B9","B9"),$excel,$gm_office,"false",$ExWs);
			addContent(setRange("H8","H8"),$excel,$director,"false",$ExWs);

		}
		else {
			$sig2="select * from signatories where signatory_date>'".$personnel_date."' order by signatory_date asc";

			$sigRS=$db2->query($sig2);
			$sigRow=$sigRS->fetch_assoc();
			
			$chief=$sigRow['chief_transport'];	
			$gm=$sigRow['general_manager'];
			$gm_office=$sigRow['gm_office'];
			$director=$sigRow['director_ops'];
			
			addContent(setRange("Q36","T36"),$excel,$chief,"false",$ExWs);
			addContent(setRange("B8","B8"),$excel,$gm,"false",$ExWs);
			addContent(setRange("B9","B9"),$excel,$gm_office,"false",$ExWs);
			addContent(setRange("H8","H8"),$excel,$director,"false",$ExWs);
			
		
		
		}
		
	
	}	
	
	$dm['shift_1']="";
	$dm['shift_2']="";
	$dm['shift_3']="";


	$srd['sr']="";
	$srd['st']="";

	$srd['shift_2']['sr']="";
	$srd['shift_2']['st']="";

	$srd['sr']="";
	$srd['st']="";

	$td['shift_1']['receiving']="";
	$td['shift_1']['man900']="";
	$td['shift_1']['reliever']="";
	$td['shift_1']['commnications']="";
	
	$td['shift_2']['receiving']="";
	$td['shift_2']['man900']="";
	$td['shift_2']['reliever']="";
	$td['shift_2']['commnications']="";
	
	$td['shift_3']['receiving']="";
	$td['shift_3']['man900']="";
	$td['shift_3']['reliever']="";
	$td['shift_3']['commnications']="";
	
	
	
	
	
	for($i=1;$i<=3;$i++){
		$sql="select * from duty_personnel where personnel_date like '".$personnel_date."%%' and shift='".$i."'";
		$rs=$db2->query($sql);
		$nm=$rs->num_rows;
		
		if($nm>0){
			$row=$rs->fetch_assoc();
			$dm['shift_'.$i]=getTrainDriver2($db,$row['duty_manager']);

			if($i==1){
				$srd['sr']=getTrainDriver2($db,$row['sr']);
				$srd['st']=getTrainDriver2($db,$row['st']);
			}

			$td['shift_'.$i]['receiving']="".getTrainDriver2($db,$row['recording']);
			$td['shift_'.$i]['man900']=",".getTrainDriver2($db,$row['man900']);
			$td['shift_'.$i]['reliever']=",".getTrainDriver2($db,$row['reliever']);
			$td['shift_'.$i]['communications']=",".getTrainDriver2($db,$row['communications']);
			
			
			$td['shift_'.$i]['summary']=$td['shift_'.$i]['receiving']." ".$td['shift_'.$i]['man900']." ".$td['shift_'.$i]['reliever']." ".$td['shift_'.$i]['communications'];
			
			
			
		}
	
	
	}
	
	
	addContent(setRange("G28","L28"),$excel,$dm['shift_1'],"false",$ExWs);
	addContent(setRange("G29","L29"),$excel,$dm['shift_2'],"false",$ExWs);
	addContent(setRange("G30","L30"),$excel,$dm['shift_3'],"false",$ExWs);
	addContent(setRange("G31","L31"),$excel,$srd['st'],"false",$ExWs);
	addContent(setRange("G32","L32"),$excel,$srd['sr'],"false",$ExWs);
	
	addContent(setRange("O28","T28"),$excel,$td['shift_1']['summary'],"false",$ExWs);
	addContent(setRange("O29","T29"),$excel,$td['shift_2']['summary'],"false",$ExWs);
	addContent(setRange("O30","T30"),$excel,$td['shift_3']['summary'],"false",$ExWs);
	
	
	
	
	
	
	
	
	
	
	
	
	save($ExWb,$excel,$newFilename); 	
	echo "CCDR Summary has been generated!  Press right click and Save As: <a href='".$newFilename."'>Here</a>";
	
}
?>