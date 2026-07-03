<?php
session_start();
?>
<?php
require_once("phpexcel/Classes/PHPExcel.php");
require_once("phpexcel/Classes/PHPExcel/IOFactory.php");
require("excel_functions.php");

?>
<?php
require_once("db_config.php"); /* centralized credentials -- see db_config.php */
?>
<?php
ini_set("date.timezone","Asia/Manila"); /* was Asia/Kuala_Lumpur -- confirmed incorrect */
?>
<?php
if(isset($_GET['ccdr'])){
	$ccdr_date=$_GET['ccdr'];

	$filename="new INCIDENT format.xls";

	$oldfilename="forms/".$filename;
	$dateSlip=date("Y-m-d His");
	$newFilename="printout/NIS_".$dateSlip.".xls";
	copy($oldfilename,$newFilename);
	$workSheetName="NIS";	
	$workbookname=$newFilename;
	$excel=loadExistingWorkbook($workbookname);

  	$ExWs=createWorksheet($excel,$workSheetName,"openActive");
	
	$rowCount=0;	
	

	$ccdr_date2=$_GET['ccdr2'];	


if($ccdr_date2==""){
	 $dClause=" like '".$ccdr_date."%%' ";
	 	 
}
else {
	$dClause=" between '".$ccdr_date." 00:00:00' and '".$ccdr_date2." 23:23:59' ";
}

	
	
	
	$db=iss_db('transport');

	/* item #3 fix: position('' IN incident_no) always returns 1, so this sorted by
	   just the FIRST CHARACTER of the incident number -- verified against a live
	   MySQL instance. Commented out rather than removed, per your request:
	$sql="select * from incident_report inner join incident_description on incident_report.id=incident_description.incident_id where incident_date ".$dClause." order by substring(incident_no,1,position('' in incident_no))*1 ";
	*/
	$sql="select * from incident_report inner join incident_description on incident_report.id=incident_description.incident_id where incident_date ".$dClause." order by substring(incident_no,1,position(' ' in incident_no)-1)*1 ";
	$rs=$db->query($sql);

	$nm=$rs->num_rows;
	
//	$rowCount+=14;

$rowCount++;

			addContent(setRange("A".$rowCount,"O".$rowCount),$excel,"DEPARTMENT OF TRANSPORTATION","true",$ExWs);
			$excel->getActiveSheet()->getStyle("A".$rowCount.":O".$rowCount)->getFont()->setBold(true);

			$excel->getActiveSheet()->getStyle("A".$rowCount.":O".$rowCount)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

			$rowCount+=1;
			addContent(setRange("A".$rowCount,"O".$rowCount),$excel,"MRT3 YELLOW LINE","true",$ExWs);
			$excel->getActiveSheet()->getStyle("A".$rowCount.":O".$rowCount)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

			$rowCount+=1;

			addContent(setRange("A".$rowCount,"O".$rowCount),$excel,"OPERATIONS DEPARTMENT","true",$ExWs);
			$excel->getActiveSheet()->getStyle("A".$rowCount.":O".$rowCount)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

			$rowCount+=1;

			addContent(setRange("A".$rowCount,"O".$rowCount),$excel,"TRANSPORT DIVISION","true",$ExWs);
			$excel->getActiveSheet()->getStyle("A".$rowCount.":O".$rowCount)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

			$rowCount+=2;
			addContent(setRange("A".$rowCount,"O".$rowCount),$excel,"CONTROL CENTER DAILY REPORT","true",$ExWs);
			$excel->getActiveSheet()->getStyle("A".$rowCount.":O".$rowCount)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$excel->getActiveSheet()->getStyle("A".$rowCount.":O".$rowCount)->getFont()->setSize(14);
			$excel->getActiveSheet()->getStyle("A".$rowCount.":O".$rowCount)->getFont()->setBold(true);
			
			$rowCount+=2;

			$styleArray = array(
				'borders' => array(
					'outline' => array(
						'style' => PHPExcel_Style_Border::BORDER_DOUBLE,
					),
				),
			);
//			addContent(setRange("A".$rowCount,"J".$rowCount),$excel,"","true",$ExWs);
//			$excel->getActiveSheet()->getStyle("A".$rowCount.":J".$rowCount)->applyFromArray($styleArray);

			addContent(setRange("K".$rowCount,"L".$rowCount),$excel,"Cause","true",$ExWs);
			$excel->getActiveSheet()->getStyle("K".$rowCount.":L".$rowCount)->applyFromArray($styleArray);

			addContent(setRange("M".$rowCount,"N".$rowCount),$excel,"Cause Identified","true",$ExWs);
			$excel->getActiveSheet()->getStyle("M".$rowCount.":N".$rowCount)->applyFromArray($styleArray);

//			addContent(setRange("O".$rowCount,"O".$rowCount),$excel,"","true",$ExWs);
//			$excel->getActiveSheet()->getStyle("O".$rowCount.":O".$rowCount)->applyFromArray($styleArray);
			$rowCount++;


		addContent(setRange("A".($rowCount*1-1),"A".$rowCount),$excel,"IN","true",$ExWs);

		addContent(setRange("B".($rowCount*1-1),"B".$rowCount),$excel,"Date","true",$ExWs);

		addContent(setRange("C".($rowCount*1-1),"C".$rowCount),$excel,"Time","true",$ExWs);

		addContent(setRange("D".($rowCount*1-1),"D".$rowCount),$excel,"Type of Action","true",$ExWs);

		
		addContent(setRange("E".($rowCount*1-1),"E".$rowCount),$excel,"Car No.","true",$ExWs);

		addContent(setRange("F".($rowCount*1-1),"F".$rowCount),$excel,"ICN","true",$ExWs);

		
		addContent(setRange("G".($rowCount*1-1),"G".$rowCount),$excel,"TD","true",$ExWs);
		addContent(setRange("H".($rowCount*1-1),"H".$rowCount),$excel,"CTC","true",$ExWs);

		addContent(setRange("I".($rowCount*1-1),"I".$rowCount),$excel,"RA","true",$ExWs);
		addContent(setRange("J".($rowCount*1-1),"J".$rowCount),$excel,"AP","true",$ExWs);
	
		
		
		addContent(setRange("K".$rowCount,"K".$rowCount),$excel,"Prelim ID","true",$ExWs);
		addContent(setRange("L".$rowCount,"L".$rowCount),$excel,"Verified","true",$ExWs);

		addContent(setRange("M".$rowCount,"M".$rowCount),$excel,"RA","true",$ExWs);
		addContent(setRange("N".$rowCount,"N".$rowCount),$excel,"AP","true",$ExWs);
		
		addContent(setRange("O".($rowCount*1-1),"O".$rowCount),$excel,"IRO","true",$ExWs);
			
			
		$excel->getActiveSheet()->getStyle("A".($rowCount*1-1).":A".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("B".($rowCount*1-1).":B".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("C".($rowCount*1-1).":C".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("D".($rowCount*1-1).":D".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("E".($rowCount*1-1).":E".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("F".($rowCount*1-1).":F".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("G".($rowCount*1-1).":G".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("H".($rowCount*1-1).":H".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("I".($rowCount*1-1).":I".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("J".($rowCount*1-1).":J".$rowCount)->applyFromArray($styleArray);

		$excel->getActiveSheet()->getStyle("K".$rowCount.":K".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("L".$rowCount.":L".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("M".$rowCount.":M".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("N".$rowCount.":N".$rowCount)->applyFromArray($styleArray);
	
		$excel->getActiveSheet()->getStyle("O".($rowCount*1-1).":O".$rowCount)->applyFromArray($styleArray);

			$excel->getActiveSheet()->getStyle("A".($rowCount*1-1).":O".$rowCount)->getFont()->setBold(true);
			$excel->getActiveSheet()->getStyle("A".($rowCount*1-1).":O".$rowCount)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		
			
			$rowCount++;

//	$rowCount+=10;


	$page_counter=1;


//	addContent(setRange("L9","N9"),$excel,date("F d, Y",strtotime($ccdr_date)),"true",$ExWs);

//	addContent(setRange("L8","N8"),$excel,date("l",strtotime($ccdr_date)),"true",$ExWs);	
	
	$db=iss_db('transport');
	$timeTableSQL="select *,timetable_day.id as timeId from timetable_day inner join timetable_code on timetable_day.timetable_code=timetable_code.id where train_date like '".$ccdr_date."%%'";

	$timeTableRS=$db->query($timeTableSQL);
	$timeTableNM=$timeTableRS->num_rows;
	if($timeTableNM>0){
		$timeTableRow=$timeTableRS->fetch_assoc();
//		addContent(setRange("L10","N10"),$excel,$timeTableRow['code'],"true",$ExWs);
		
		
	}	

	$personnel_date=$ccdr_date;

	$db2=iss_db('user_transport');
	$psql="select * from duty_personnel where personnel_date like '".$personnel_date."%%' and shift='3'";
	//echo $psql;
	$prs=$db2->query($psql);
	$pnm=$prs->num_rows;

	if($pnm>0){
		$prow=$prs->fetch_assoc();
		$recording=getTrainDriver($db,$prow['recording']);
		$clerk=getTrainDriver($db,$prow['clerk']);
		$duty_manager=getTrainDriver($db,$prow['duty_manager']);
				
		//addContent(setRange("D190","F190"),$excel,$recording,"true",$ExWs);
		//addContent(setRange("A190","C190"),$excel,$clerk,"true",$ExWs);
		//addContent(setRange("G190","I190"),$excel,$duty_manager,"true",$ExWs);

				
				
			
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
			$maintenance=$signatoryRow['maintenance_provider'];
			
			/*addContent(setRange("J190","M190"),$excel,$chief,"false",$ExWs);

			addContent(setRange("J13","M13"),$excel,$maintenance,"false",$ExWs);

			addContent(setRange("B8","B8"),$excel,$gm,"false",$ExWs);
			addContent(setRange("B9","B9"),$excel,$gm_office,"false",$ExWs);
			addContent(setRange("G8","H8"),$excel,$director,"false",$ExWs);
*/
		}
		else {
			$sig2="select * from signatories where signatory_date>'".$personnel_date."' order by signatory_date asc";

			$sigRS=$db2->query($sig2);
			$sigRow=$sigRS->fetch_assoc();
			
			$chief=$sigRow['chief_transport'];	
			$gm=$sigRow['general_manager'];
			$gm_office=$sigRow['gm_office'];
			$director=$sigRow['director_ops'];
			$maintenance=$sigRow['maintenance_provider'];
/*
			addContent(setRange("J13","M13"),$excel,$maintenance,"false",$ExWs);

			addContent(setRange("J190","M190"),$excel,$chief,"false",$ExWs);
			addContent(setRange("B8","B8"),$excel,$gm,"false",$ExWs);
			addContent(setRange("B9","B9"),$excel,$gm_office,"false",$ExWs);
			addContent(setRange("G8","H8"),$excel,$director,"false",$ExWs);
	*/		
		
		
		
		
		}
		
	
	}	
	
	
	
	for($i=0;$i<$nm;$i++){
		$row=$rs->fetch_assoc();


		$car[0]="";
		$car[1]="";
		$car[2]="";
		$car[3]=""; /* item #2 fix: fourth car was never read here */

		$carClause="";
		$carSQL="select * from incident_cars where incident_id='".$row['incident_id']."'";
		$carRS=$db->query($carSQL);
		$carNM=$carRS->num_rows;
		
		if($carNM>0){
			for($b=0;$b<$carNM;$b++){
				$carRow=$carRS->fetch_assoc();
				$car[$b]=$carRow['car_no'];
			}			
			
			$carClause=$car[0];
			if($car[1]==""){
			}
			else {
				$carClause.=", ".$car[1];
			}
			
			if($car[2]==""){
			}
			else {
				$carClause.=", ".$car[2];
			}
			
			if($car[3]==""){
			}
			else {
				$carClause.=", ".$car[3];
			}
			
		}
		
		$newCarClause=$carClause;
		
		
		
		
		
		$compoSQL="select * from train_incident_report inner join train_availability on train_availability.id=train_incident_report.train_ava_id where train_incident_report.incident_id='".$row['incident_id']."'";
		
		$compoRS=$db->query($compoSQL);
		
		$compoRow=$compoRS->fetch_assoc();
		
		$train_compo=$compoRow['index_no']." (".$compoRow['car_a'].", ".$compoRow['car_b'].", ".$compoRow['car_c'].")";
		
		
		
		
		

		
		
		
		

		$incident_type=$row['incident_type'];
		
		$hourStamp=date("H:iA",strtotime($row['incident_date']));
		$incident_no=$row['incident_no'];
		$duration=$row['duration'];
		$description="";
		
		
		
		
		$location=$row['location'];
		$reported_by=$row['reported_by'];

		$received_by=$row['received_by'];
		
		
		if($incident_type=="rolling"){
			if($carClause==""){ } else { $carClause=" Car(s) ".$carClause.", "; }
			
			$direction=$row['direction'];
			/* item #7 fix: SB/NB never got spelled out the way D/ML do below, so the
			   description used to end with a raw code ("...  SB," / "...  NB,"). S means
			   "station" (not a direction), so it is blanked rather than spelled out --
			   matching how edit_ccdr.php already treats S on its own display. Confirmed
			   2026-07: S=Station, SB=Southbound, NB=Northbound, D=Depot, ML=Mainline. */
			if($direction=="S"){ $location="Stn. ".$location; $direction=""; }
			else if($direction=="SB"){ $location="Stn. ".$location; $direction="Southbound"; }
			else if($direction=="NB"){ $location="Stn. ".$location; $direction="Northbound"; }
			else if($direction=="D"){ $direction="Depot"; }
			else if($direction=="ML"){ $direction="Mainline"; }
			/* item #7 fix: omit the "  " separator when $direction was blanked (S), so the
			   description reads "Stn. Ayala, ..." instead of "Stn. Ayala  , ...". */
			$description="Index #".$row['index_no'].",".$carClause.$location.($direction!=""?"  ".$direction:"").", ".$row['description'].", Reported By ".$reported_by.", ";
		
		}
		else if(($incident_type=="unload")||($incident_type=='nload')){
			if($carClause==""){ } else { $carClause=" Car(s) ".$carClause.", "; }
			
			$description="Index #".$row['index_no'].",".$carClause.", ".$row['description'].", Reported By ".$reported_by.", ";



		}
		else {
			$description.=$row['description'].", Reported By ".$reported_by;
		}
		$action_dotc=$row['action_dotc'];
		$action_maintenance=$row['action_maintenance'];
		$level=$row['level'];
		
		
		$CCREsql="select * from train_driver where id='".$received_by."'";
		$CCRErs=$db->query($CCREsql);
		$CCRErow=$CCRErs->fetch_assoc();



		$engSQL="select * from engineering_mod where incident_id='".$row['incident_id']."'";
		$engRS=$db->query($engSQL);
		$engNM=$engRS->num_rows;
		if($engNM>0){
		$engRow=$engRS->fetch_assoc();

		$recommend_eng=$engRow['recommend_approval'];
		$approving_eng=$engRow['approving_officer'];
		$iro_eng=$engRow['iro'];
		}
		
		$ctc=substr($CCRErow['firstName'],0,1).". ".$CCRErow['lastName'];
		
		
/*		addContent(setRange("A".$rowCount,"A".$rowCount),$excel,$incident_no,"true",$ExWs);
		addContent(setRange("B".$rowCount,"B".$rowCount),$excel,"'".$hourStamp,"true",$ExWs);
		addContent(setRange("C".$rowCount,"C".$rowCount),$excel,$duration,"true",$ExWs);
		addContent(setRange("D".$rowCount,"F".$rowCount),$excel,$description,"true",$ExWs);
		addContent(setRange("G".$rowCount,"I".$rowCount),$excel,$action_dotc,"true",$ExWs);
		addContent(setRange("J".$rowCount,"M".$rowCount),$excel,$action_maintenance,"true",$ExWs);

		addContent(setRange("N".$rowCount,"N".$rowCount),$excel,$level,"true",$ExWs);*/
		

		addContent(setRange("A".$rowCount,"A".$rowCount),$excel,$incident_no,"true",$ExWs);

		addContent(setRange("B".$rowCount,"B".$rowCount),$excel,"'".date("m/d/Y",strtotime($row['incident_date'])),"true",$ExWs);

		addContent(setRange("C".$rowCount,"C".$rowCount),$excel,"".$hourStamp,"true",$ExWs);

		addContent(setRange("D".$rowCount,"D".$rowCount),$excel,"".$action_type,"true",$ExWs);

		
		addContent(setRange("E".$rowCount,"E".$rowCount),$excel,$newCarClause,"true",$ExWs);

		addContent(setRange("F".$rowCount,"F".$rowCount),$excel,$train_compo,"true",$ExWs);

		
		addContent(setRange("G".$rowCount,"G".$rowCount),$excel,$reported_by,"true",$ExWs);
		addContent(setRange("H".$rowCount,"H".$rowCount),$excel,$ctc,"true",$ExWs);

		addContent(setRange("I".$rowCount,"I".$rowCount),$excel,$row['recommending_approval'],"true",$ExWs);
		addContent(setRange("J".$rowCount,"J".$rowCount),$excel,$row['approving_person'],"true",$ExWs);
	
		
		
		addContent(setRange("K".$rowCount,"K".$rowCount),$excel,$description,"true",$ExWs);
		addContent(setRange("L".$rowCount,"L".$rowCount),$excel,$action_maintenance,"true",$ExWs);

		addContent(setRange("M".$rowCount,"M".$rowCount),$excel,$recommend_eng,"true",$ExWs);
		addContent(setRange("N".$rowCount,"N".$rowCount),$excel,$approving_eng,"true",$ExWs);
		
		addContent(setRange("O".$rowCount,"O".$rowCount),$excel,$iro_eng,"true",$ExWs);

		
		$styleArray = array(
			'borders' => array(
				'outline' => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN,
				),
			),
		);
		$excel->getActiveSheet()->getStyle("A".$rowCount.":A".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("B".$rowCount.":B".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("C".$rowCount.":C".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("D".$rowCount.":D".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("E".$rowCount.":E".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("F".$rowCount.":F".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("G".$rowCount.":G".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("H".$rowCount.":H".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("I".$rowCount.":I".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("J".$rowCount.":J".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("K".$rowCount.":K".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("L".$rowCount.":L".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("M".$rowCount.":M".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("N".$rowCount.":N".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("O".$rowCount.":O".$rowCount)->applyFromArray($styleArray);

		
		
		if($page_counter==24){	
			$page_counter=1;	

			$rowCount+=2;

				
			addContent(setRange("A".$rowCount,"O".$rowCount),$excel,"DEPARTMENT OF TRANSPORTATION","true",$ExWs);
			$excel->getActiveSheet()->getStyle("A".$rowCount.":O".$rowCount)->getFont()->setBold(true);

			$excel->getActiveSheet()->getStyle("A".$rowCount.":O".$rowCount)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

			$rowCount+=1;
			addContent(setRange("A".$rowCount,"O".$rowCount),$excel,"MRT3 YELLOW LINE","true",$ExWs);
			$excel->getActiveSheet()->getStyle("A".$rowCount.":O".$rowCount)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

			$rowCount+=1;

			addContent(setRange("A".$rowCount,"O".$rowCount),$excel,"OPERATIONS DEPARTMENT","true",$ExWs);
			$excel->getActiveSheet()->getStyle("A".$rowCount.":O".$rowCount)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

			$rowCount+=1;

			addContent(setRange("A".$rowCount,"O".$rowCount),$excel,"TRANSPORT DIVISION","true",$ExWs);
			$excel->getActiveSheet()->getStyle("A".$rowCount.":O".$rowCount)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

			$rowCount+=2;
			addContent(setRange("A".$rowCount,"O".$rowCount),$excel,"CONTROL CENTER DAILY REPORT","true",$ExWs);
			$excel->getActiveSheet()->getStyle("A".$rowCount.":O".$rowCount)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$excel->getActiveSheet()->getStyle("A".$rowCount.":O".$rowCount)->getFont()->setSize(14);
			$excel->getActiveSheet()->getStyle("A".$rowCount.":O".$rowCount)->getFont()->setBold(true);
			
			$rowCount+=2;

			$styleArray = array(
				'borders' => array(
					'outline' => array(
						'style' => PHPExcel_Style_Border::BORDER_DOUBLE,
					),
				),
			);
//			addContent(setRange("A".$rowCount,"J".$rowCount),$excel,"","true",$ExWs);
//			$excel->getActiveSheet()->getStyle("A".$rowCount.":J".$rowCount)->applyFromArray($styleArray);

			addContent(setRange("K".$rowCount,"L".$rowCount),$excel,"Cause","true",$ExWs);
			$excel->getActiveSheet()->getStyle("K".$rowCount.":L".$rowCount)->applyFromArray($styleArray);

			addContent(setRange("M".$rowCount,"N".$rowCount),$excel,"Cause Identified","true",$ExWs);
			$excel->getActiveSheet()->getStyle("M".$rowCount.":N".$rowCount)->applyFromArray($styleArray);

//			addContent(setRange("O".$rowCount,"O".$rowCount),$excel,"","true",$ExWs);
//			$excel->getActiveSheet()->getStyle("O".$rowCount.":O".$rowCount)->applyFromArray($styleArray);
			$rowCount++;


		addContent(setRange("A".($rowCount*1-1),"A".$rowCount),$excel,"IN","true",$ExWs);

		addContent(setRange("B".($rowCount*1-1),"B".$rowCount),$excel,"Date","true",$ExWs);

		addContent(setRange("C".($rowCount*1-1),"C".$rowCount),$excel,"Time","true",$ExWs);

		addContent(setRange("D".($rowCount*1-1),"D".$rowCount),$excel,"Type of Action","true",$ExWs);

		
		addContent(setRange("E".($rowCount*1-1),"E".$rowCount),$excel,"Car No.","true",$ExWs);

		addContent(setRange("F".($rowCount*1-1),"F".$rowCount),$excel,"ICN","true",$ExWs);

		
		addContent(setRange("G".($rowCount*1-1),"G".$rowCount),$excel,"TD","true",$ExWs);
		addContent(setRange("H".($rowCount*1-1),"H".$rowCount),$excel,"CTC","true",$ExWs);

		addContent(setRange("I".($rowCount*1-1),"I".$rowCount),$excel,"RA","true",$ExWs);
		addContent(setRange("J".($rowCount*1-1),"J".$rowCount),$excel,"AP","true",$ExWs);
	
		
		
		addContent(setRange("K".$rowCount,"K".$rowCount),$excel,"Prelim ID","true",$ExWs);
		addContent(setRange("L".$rowCount,"L".$rowCount),$excel,"Verified","true",$ExWs);

		addContent(setRange("M".$rowCount,"M".$rowCount),$excel,"RA","true",$ExWs);
		addContent(setRange("N".$rowCount,"N".$rowCount),$excel,"AP","true",$ExWs);
		
		addContent(setRange("O".($rowCount*1-1),"O".$rowCount),$excel,"IRO","true",$ExWs);
			
			
		$excel->getActiveSheet()->getStyle("A".($rowCount*1-1).":A".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("B".($rowCount*1-1).":B".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("C".($rowCount*1-1).":C".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("D".($rowCount*1-1).":D".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("E".($rowCount*1-1).":E".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("F".($rowCount*1-1).":F".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("G".($rowCount*1-1).":G".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("H".($rowCount*1-1).":H".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("I".($rowCount*1-1).":I".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("J".($rowCount*1-1).":J".$rowCount)->applyFromArray($styleArray);

		$excel->getActiveSheet()->getStyle("K".$rowCount.":K".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("L".$rowCount.":L".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("M".$rowCount.":M".$rowCount)->applyFromArray($styleArray);
		$excel->getActiveSheet()->getStyle("N".$rowCount.":N".$rowCount)->applyFromArray($styleArray);
	
		$excel->getActiveSheet()->getStyle("O".($rowCount*1-1).":O".$rowCount)->applyFromArray($styleArray);

			$excel->getActiveSheet()->getStyle("A".($rowCount*1-1).":O".$rowCount)->getFont()->setBold(true);
			$excel->getActiveSheet()->getStyle("A".($rowCount*1-1).":O".$rowCount)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		
			
			$rowCount++;
			
		
		}
		else {
			$page_counter++;
			$rowCount++;	
		}
	
										
										
										

		
		
		
		
		

	/*
		if($i==($nm-1)){
		
			$excel->getActiveSheet()->mergeCells("J".($rowCount+1).":M".($rowCount+1));

			$excel->getActiveSheet()->unmergeCells("J".($rowCount+1).":M".($rowCount+1));
			$excel->getActiveSheet()->mergeCells("J".($rowCount+5).":M".($rowCount+5));

			$excel->getActiveSheet()->unmergeCells("J".($rowCount+5).":M".($rowCount+5));

			
		
			$row_delete=186-$rowCount;
			$excel->getActiveSheet()->removeRow(($rowCount),$row_delete);
			
		}
	*/
	}
$rowCount++;
		addContent(setRange("A".$rowCount,"F".$rowCount),$excel,"Legend:","true",$ExWs);

		$rowCount++;	
		
		addContent(setRange("A".$rowCount,"F".$rowCount),$excel,"IN - Incident Number","true",$ExWs);
		addContent(setRange("H".$rowCount,"K".$rowCount),$excel,"AP - Approving Person","true",$ExWs);
			$rowCount++;	

		addContent(setRange("A".$rowCount,"F".$rowCount),$excel,"Type of Action: NI - Non-Insertion, R-Removal, U-Unloading","true",$ExWs);
		addContent(setRange("H".$rowCount,"K".$rowCount),$excel,"Prelim ID - Preliminary Identification","true",$ExWs);
			$rowCount++;	

		addContent(setRange("A".$rowCount,"F".$rowCount),$excel,"ICN-Index Car Nos","true",$ExWs);
		addContent(setRange("H".$rowCount,"K".$rowCount),$excel,"RA - Recommending Approval","true",$ExWs);
		$rowCount++;	

		addContent(setRange("A".$rowCount,"F".$rowCount),$excel,"TD-Train Driver","true",$ExWs);
		addContent(setRange("H".$rowCount,"K".$rowCount),$excel,"IRO - Incident Record Officer","true",$ExWs);
		$rowCount++;	


		addContent(setRange("A".$rowCount,"F".$rowCount),$excel,"CTC - Centralized Traffic Control Supervisor","true",$ExWs);
		addContent(setRange("H".$rowCount,"K".$rowCount),$excel,"SIR - Service Interruption Report","true",$ExWs);
		$rowCount++;	

		addContent(setRange("A".$rowCount,"F".$rowCount),$excel,"MTT - Maintenance Transition Team","true",$ExWs);
		addContent(setRange("H".$rowCount,"K".$rowCount),$excel,"DB - Driving Backward","true",$ExWs);
		$rowCount++;	
			
		addContent(setRange("A".$rowCount,"F".$rowCount),$excel,"MT -Mainline Technician","true",$ExWs);
		$rowCount++;	

											
		addContent(setRange("A".$rowCount,"F".$rowCount),$excel,"ERU - Emergency Repair Unit","true",$ExWs);
		$rowCount++;	
										
		addContent(setRange("A".$rowCount,"F".$rowCount),$excel,"ENGG - Engineering Personnel","true",$ExWs);
		$rowCount++;	
		
		if($page_counter<=28){
	
			$rowCount+=2;

			addContent(setRange("A".$rowCount,"B".$rowCount),$excel,"Prepared By:","true",$ExWs);
			addContent(setRange("H".$rowCount,"I".$rowCount),$excel,"Reviewed By:","true",$ExWs);
			addContent(setRange("L".$rowCount,"L".$rowCount),$excel,"Verified By:","true",$ExWs);
			
			$rowCount+=3;
			addContent(setRange("L".$rowCount,"M".$rowCount),$excel,"OLIVER S. CASILI","true",$ExWs);
			$excel->getActiveSheet()->getStyle("A".$rowCount.":O".$rowCount)->getFont()->setBold(true);
			
			$rowCount++;
			addContent(setRange("L".$rowCount,"M".$rowCount),$excel,"OIC, Transport Division","true",$ExWs);
			$rowCount+=2;
			$excel->getActiveSheet()->getStyle("A".$rowCount.":E".$rowCount)->getFont()->setSize(9);

			addContent(setRange("A".$rowCount,"E".$rowCount),$excel,"Printed: ".$_SESSION['user_fullname']."","true",$ExWs);
	
		}
		else {

			$rowCount+=($page_counter-28)*1;

			$page_counter=1;	


				
			addContent(setRange("A".$rowCount,"O".$rowCount),$excel,"DEPARTMENT OF TRANSPORTATION","true",$ExWs);
			$excel->getActiveSheet()->getStyle("A".$rowCount.":O".$rowCount)->getFont()->setBold(true);

			$excel->getActiveSheet()->getStyle("A".$rowCount.":O".$rowCount)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

			$rowCount+=1;
			addContent(setRange("A".$rowCount,"O".$rowCount),$excel,"MRT3 YELLOW LINE","true",$ExWs);
			$excel->getActiveSheet()->getStyle("A".$rowCount.":O".$rowCount)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

			$rowCount+=1;

			addContent(setRange("A".$rowCount,"O".$rowCount),$excel,"OPERATIONS DEPARTMENT","true",$ExWs);
			$excel->getActiveSheet()->getStyle("A".$rowCount.":O".$rowCount)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

			$rowCount+=1;

			addContent(setRange("A".$rowCount,"O".$rowCount),$excel,"TRANSPORT DIVISION","true",$ExWs);
			$excel->getActiveSheet()->getStyle("A".$rowCount.":O".$rowCount)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

			$rowCount+=2;
			addContent(setRange("A".$rowCount,"O".$rowCount),$excel,"CONTROL CENTER DAILY REPORT","true",$ExWs);
			$excel->getActiveSheet()->getStyle("A".$rowCount.":O".$rowCount)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$excel->getActiveSheet()->getStyle("A".$rowCount.":O".$rowCount)->getFont()->setSize(14);
			$excel->getActiveSheet()->getStyle("A".$rowCount.":O".$rowCount)->getFont()->setBold(true);
			
			$rowCount+=2;		

			addContent(setRange("A".$rowCount,"B".$rowCount),$excel,"Prepared By:","true",$ExWs);
			addContent(setRange("H".$rowCount,"I".$rowCount),$excel,"Reviewed By:","true",$ExWs);
			addContent(setRange("L".$rowCount,"L".$rowCount),$excel,"Verified By:","true",$ExWs);

			$rowCount+=3;
			addContent(setRange("L".$rowCount,"M".$rowCount),$excel,"OLIVER S. CASILI","true",$ExWs);
			$excel->getActiveSheet()->getStyle("A".$rowCount.":O".$rowCount)->getFont()->setBold(true);
			
			$rowCount++;
			addContent(setRange("L".$rowCount,"M".$rowCount),$excel,"OIC, Transport Division","true",$ExWs);
	

			$rowCount+=2;
			$excel->getActiveSheet()->getStyle("A".$rowCount.":E".$rowCount)->getFont()->setSize(9);

			addContent(setRange("A".$rowCount,"E".$rowCount),$excel,"Printed: ".$_SESSION['user_fullname']."","true",$ExWs);
			


	
		}
	
	
	
	save($ExWb,$excel,$newFilename); 	
	echo "CCDR has been generated!  Press right click and Save As: <a href='".$newFilename."'>Here</a>";





}

function getTrainDriver($db,$td_id){
	$sql="select * from train_driver where id='".$td_id."' limit 1";

	$rs=$db->query($sql);
	$row=$rs->fetch_assoc();
	
	$name=$row['firstName']." ".substr($row['midName'],0,1).". ".$row['lastName'];
	return $name;

}
?>