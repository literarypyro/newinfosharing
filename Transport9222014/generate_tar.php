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
function getTrainDriver($id,$dbase){

//$db=new mysqli("localhost","root","","transport");
	$sql="select * from train_driver where id='".$id."'";
	$rs=$dbase->query($sql);
	$row=$rs->fetch_assoc();
	
	$name=$row['position']." ".substr($row['firstName'],0,1).". ".$row['lastName'];
	return $name;


}

function getTrainDriver2($db,$td_id){
	$sql="select * from train_driver where id='".$td_id."' limit 1";
	$rs=$db->query($sql);
	$row=$rs->fetch_assoc();
	
	$name=$row['firstName']." ".substr($row['midName'],0,1).". ".$row['lastName'];
	return $name;

}


function getPHTrainDriver($id,$dbase){

//$db=new mysqli("localhost","root","","transport");
	$sql="select firstName,lastName from ph_trams where id='".$id."' limit 1";
	
	
	$rs=$dbase->query($sql);
	$nm=$rs->num_rows;
	if($nm>0){
		$row=$rs->fetch_assoc();
	
		$name=substr($row['firstName'],0,1).". ".$row['lastName'];
	}
	else {
	
		$name=$id;
	}
	return $name;


}




function getLevel($id,$dbase){
//$db=new mysqli("localhost","root","","transport");
	$sql="select * from level where incident_id='".$id."'";
	$rs=$dbase->query($sql);
	$row=$rs->fetch_assoc();
	$level=$row['order'];
	return $level;

}

function getOrdinal($number){
$ends = array('th','st','nd','rd','th','th','th','th','th','th');
if (($number %100) >= 11 && ($number%100) <= 13)
   $abbreviation = $number. 'th';
else
   $abbreviation = $number. $ends[$number % 10];

   
 return $abbreviation;  

}
if(isset($_GET['tar'])){
	$tar_date=$_GET['tar'];

	$filename="TAR.xls";

	$oldfilename="forms/".$filename;
	$dateSlip=date("Y-m-d His");
	$newFilename="printout/TAR_".$dateSlip.".xls";
	copy($oldfilename,$newFilename);

	$workSheetName="TAR";	
	$workbookname=$newFilename;
	$excel=loadExistingWorkbook($workbookname);

  	$ExWs=createWorksheet($excel,$workSheetName,"openActive");


	
	
	$db=new mysqli("localhost","root","","transport");

	
	
	$personnel_date=$ccdr_date;

	$db2=new mysqli("localhost","root","","user_transport");
	$psql="select * from duty_personnel where personnel_date like '".$tar_date."%%' and shift='3'";
	//echo $psql;
	$prs=$db2->query($psql);
	$pnm=$prs->num_rows;

	if($pnm>0){
		$prow=$prs->fetch_assoc();
		$recording=getTrainDriver2($db,$prow['recording']);
		$clerk=getTrainDriver2($db,$prow['clerk']);
		$duty_manager=getTrainDriver2($db,$prow['duty_manager']);
				
		addContent(setRange("G268","I268"),$excel,$recording,"true",$ExWs);
		addContent(setRange("B268","E268"),$excel,$clerk,"true",$ExWs);
		addContent(setRange("K268","M268"),$excel,$duty_manager,"true",$ExWs);

				
				
			
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
			
		//	addContent(setRange("J190","M190"),$excel,$chief,"false",$ExWs);
			addContent(setRange("C8","C8"),$excel,$gm,"false",$ExWs);
			addContent(setRange("C9","C9"),$excel,$gm_office,"false",$ExWs);
			addContent(setRange("J8","J8"),$excel,$director,"false",$ExWs);

		}
		else {
			$sig2="select * from signatories where signatory_date>'".$personnel_date."' order by signatory_date asc";

			$sigRS=$db2->query($sig2);
			$sigRow=$sigRS->fetch_assoc();
			
			$chief=$sigRow['chief_transport'];	
			$gm=$sigRow['general_manager'];
			$gm_office=$sigRow['gm_office'];
			$director=$sigRow['director_ops'];

		//	addContent(setRange("J190","M190"),$excel,$chief,"false",$ExWs);
			addContent(setRange("C8","C8"),$excel,$gm,"false",$ExWs);
			addContent(setRange("C9","C9"),$excel,$gm_office,"false",$ExWs);
			addContent(setRange("J8","J8"),$excel,$director,"false",$ExWs);
			
		
		
		
		
		}
		
	
	}		
	
	
	
	
	
	
	
	
	
	
	addContent(setRange("O9","Q9"),$excel,date("F d, Y",strtotime($tar_date)),"true",$ExWs);

	addContent(setRange("O8","Q8"),$excel,date("l",strtotime($tar_date)),"true",$ExWs);

	$db=new mysqli("localhost","root","","transport");
	$timeTableSQL="select *,timetable_day.id as timeId from timetable_day inner join timetable_code on timetable_day.timetable_code=timetable_code.id where train_date like '".$tar_date."%%'";

	$timeTableRS=$db->query($timeTableSQL);
	$timeTableNM=$timeTableRS->num_rows;
	if($timeTableNM>0){
		$timeTableRow=$timeTableRS->fetch_assoc();
		addContent(setRange("O10","Q10"),$excel,$timeTableRow['code'],"true",$ExWs);
		
		
	}	
	
	
	$sql="select * from train_availability where date like '".$tar_date."%%' order by date";
	$rs=$db->query($sql);
	$nm=$rs->num_rows;

	$rowCount=0;
	$page_counter=1;
	
	$rowCount+=14;
	
	for($i=0;$i<$nm;$i++){
		$row=$rs->fetch_assoc();
		$train_index=$row['index_no'];	

		$start=$rowCount;	
		$end=$start*1+3;
			
		addContent(setRange("A".$start,"A".$end),$excel,$train_index,"true",$ExWs);


		

		$sql3="select * from train_switch where train_ava_id='".$row['id']."' order by date_change";
		$rs3=$db->query($sql3);
		$nm3=$rs3->num_rows;

		if($nm3>4){
			$nm3=4;
		}
		
		$col=66;
		for($n=0;$n<$nm3;$n++){
			$row3=$rs3->fetch_assoc();
			

			addContent(setRange(chr($col).$start,chr($col).($start+1)),$excel,date("H:i",strtotime($row3['date_change'])),"true",$ExWs);
			addContent(setRange(chr($col).($end-1),chr($col).$end),$excel,$row3['new_index'],"true",$ExWs);

			$col++;
			
		}	
			
		addContent(setRange("F".$start,"F".$start),$excel,$row['car_a'],"true",$ExWs);
		addContent(setRange("F".($start+1),"F".($start+2)),$excel,$row['car_b'],"true",$ExWs);
		addContent(setRange("F".($end),"F".$end),$excel,$row['car_c'],"true",$ExWs);
			
		$sql2="select * from train_ava_time where train_ava_id='".$row['id']."'";
		$rs2=$db->query($sql2);
		$row2=$rs2->fetch_assoc();

		if($row2['boundary_time']==""){
			$boundary_time="";
		}
		else {
			$boundary_time=date("H:i",strtotime($row2['boundary_time']));
		}	
		
		if($row2['insert_time']==""){
			$insert_time="";
			$insert_driver="";
		}
		else {

			if($row2['insert_time']=="0000-00-00 00:00:00"){
				$insert_date="";
				$insert_time="";
			}
			else {		
				$insert_time=date("H:i",strtotime($row2['insert_time']));
				$insert_date=date("Y-m-d",strtotime($row2['insert_time']));

				$insert_time=date("H:i",strtotime($row2['insert_time']));
				$insert_date=date("Y-m-d",strtotime($row2['insert_time']));
				if(strtotime($availability_date)>strtotime($insert_date)){

					$insert_time=$insert_date."\n".$insert_time;
				}
			}
			
			
			
			$inserted_to=$row2['inserted_to'];
			
			if($row['type']=="unimog"){
				$insert_driver=getPHTrainDriver($row2['insert_driver'],$db)."\nMAINTENANCE PROVIDER";
			}

			else if($row['type']=="test"){
				$insert_driver=getPHTrainDriver($row2['insert_driver'],$db)."\nMAINTENANCE PROVIDER";
			}
			else if($row['type']=="reserve"){
				$insert_driver=$row2['insert_driver'];
			}

			else {
				$insert_driver=getTrainDriver($row2['insert_driver'],$db);
			
			
			}
			if($inserted_to=="quezon"){ $inserted_to="Quezon Ave.\n"; }			
			else { $inserted_to=""; }			
			
			
		}		

		if($row2['remove_time']==""){
			$remove_time="";
			$remove_driver="";
			$remove_remarks="";

		}
		else {
			if($row2['remove_time']=="0000-00-00 00:00:00"){
				$remove_time="";
				$remove_date="";
			}			
			else {		
			
				$remove_date=date("Y-m-d",strtotime($row2['remove_time']));

				$remove_time=date("H:i",strtotime($row2['remove_time']));
				if(strtotime($availability_date)>strtotime($remove_date)){
					$remove_time=$remove_date."\n".$remove_time;
				}

			
			}
			if($row['type']=="unimog"){
				$remove_driver=getPHTrainDriver($row2['remove_driver'],$db)."\nMAINTENANCE PROVIDER";
			}

			else if($row['type']=="test"){
				$remove_driver=getPHTrainDriver($row2['remove_driver'],$db)."\nMAINTENANCE PROVIDER";
			}
			else if($row['type']=="reserve"){
				$remove_driver=$row2['remove_driver'];
			}

			else {
				$remove_driver=getTrainDriver($row2['remove_driver'],$db);
			}
			if($removed_from=="quezon"){ $removed_from="Quezon Ave.<br/>"; }			
			else { $removed_from=""; }
			$remove_remarks=$row2['removal_remarks'];

			
			
			
			
			
			
		}




		if($boundary_time==""){
		}
		else {
		addContent(setRange("G".$start,"G".$end),$excel,$boundary_time,"true",$ExWs);
		}

		$cancelSQL="select * from train_incident_view where train_ava_id='".$row['id']."'";
		
		
		$cancelRS=$db->query($cancelSQL);
		$incidentClause="";	

		$level2Clause="";	
		$level3Clause="";
			
		$l2Count=0;
		$l3Count=0;
		
		$cancelNM=$cancelRS->num_rows;
		if($cancelNM>0){
			for($m=0;$m<$cancelNM;$m++){
			$cancelRow=$cancelRS->fetch_assoc();		
			$level=$cancelRow['level'];			
			$order=getLevel($cancelRow['incident_id'],$db);
				if($level==1){
				}
				else {
					
				
				}
				
				if($m==0){
					$incidentClause.="SEE IN ".$cancelRow['incident_no'];
				}
				else {
					$incidentClause.=",\n";
					$incidentClause.="IN ".$cancelRow['incident_no'];
				}
				
				
				if($level==2){
					if($l2Count==0){
						$level2Clause.=getOrdinal($order);
					}
					else {
						$level2Clause.=",\n";
						$level2Clause.=getOrdinal($order);
						
					}
					$l2Count++;

				}
				else if($level==3){
					if($l3Count==0){
						$level3Clause.=getOrdinal($order);
					}
					else {
						$level3Clause.=",\n";
						$level3Clause.=getOrdinal($order);
						
					}
					$l3Count++;

				}

			}
			
		}
		
		
		
		
		$excel->getActiveSheet()->getStyle("L".$start.":O".$end)->getAlignment()->setWrapText(true);
		$excel->getActiveSheet()->getStyle("I".$start.":I".$end)->getAlignment()->setWrapText(true);
		$excel->getActiveSheet()->getStyle("K".$start.":K".$end)->getAlignment()->setWrapText(true);

		
		if($row['status']=="active"){
			addContent(setRange("H".$start,"H".$end),$excel,$inserted_to.$insert_time,"true",$ExWs);
			addContent(setRange("I".$start,"I".$end),$excel,$insert_driver,"true",$ExWs);
			addContent(setRange("J".$start,"J".$end),$excel,$removed_from.$remove_time,"true",$ExWs);
			addContent(setRange("K".$start,"K".$end),$excel,$remove_driver,"true",$ExWs);
			
			
			
			
			
		
		}
		else {
			if($boundary_time==""){
			addContent(setRange("G".$start,"K".$end),$excel,"CANCELLED","true",$ExWs);

			}
			else {
			addContent(setRange("H".$start,"K".$end),$excel,"CANCELLED","true",$ExWs);
			}


		}

		addContent(setRange("L".$start,"O".$end),$excel,$remove_remarks."\n".$incidentClause,"true",$ExWs);

		addContent(setRange("P".$start,"P".$end),$excel,$level2Clause,"true",$ExWs);
		addContent(setRange("Q".$start,"Q".$end),$excel,$level3Clause,"true",$ExWs);

		
//		addContent(setRange("I".$start,"I".$end),$excel,"First Line\nSecond Line\nFirst Line\nSecond Line","true",$ExWs);

//		$excel->getActiveSheet()->getStyle("I".$start.":I".$end)->getAlignment()->setWrapText(true);
			
	
		if($page_counter==7){	
			$page_counter=1;	
			
			$rowCount+=2;
			$rowCount+=17;
			
		
		}
		else {
			$page_counter++;
			$rowCount+=4;	
		}
		if($i==($nm-1)){

		}
	}
			$rowstart=$rowCount+4;
		
			$row_delete=257-$rowCount;
//			$excel->getActiveSheet()->removeRow(20,1000);	
			$excel->getActiveSheet()->mergeCells("A".$rowCount.":A".($rowstart-1));

			$excel->getActiveSheet()->mergeCells("G".($rowstart).":G".($rowstart+3));
			$excel->getActiveSheet()->mergeCells("H".($rowstart).":H".($rowstart+3));
			$excel->getActiveSheet()->mergeCells("I".($rowstart).":I".($rowstart+3));
			$excel->getActiveSheet()->mergeCells("J".($rowstart).":J".($rowstart+3));
			$excel->getActiveSheet()->mergeCells("K".($rowstart).":K".($rowstart+3));
			$excel->getActiveSheet()->mergeCells("L".($rowstart).":O".($rowstart+3));


			$excel->getActiveSheet()->mergeCells("G".($rowstart+4).":G".($rowstart+7));
			$excel->getActiveSheet()->mergeCells("H".($rowstart+4).":H".($rowstart+7));
			$excel->getActiveSheet()->mergeCells("I".($rowstart+4).":I".($rowstart+7));
			$excel->getActiveSheet()->mergeCells("J".($rowstart+4).":J".($rowstart+7));
			$excel->getActiveSheet()->mergeCells("K".($rowstart+4).":K".($rowstart+7));
			$excel->getActiveSheet()->mergeCells("L".($rowstart+4).":O".($rowstart+7));
			



			$excel->getActiveSheet()->mergeCells("G".($rowstart+8).":G".($rowstart+11));
			$excel->getActiveSheet()->mergeCells("H".($rowstart+8).":H".($rowstart+11));
			$excel->getActiveSheet()->mergeCells("I".($rowstart+8).":I".($rowstart+11));
			$excel->getActiveSheet()->mergeCells("J".($rowstart+8).":J".($rowstart+11));
			$excel->getActiveSheet()->mergeCells("K".($rowstart+8).":K".($rowstart+11));
			$excel->getActiveSheet()->mergeCells("L".($rowstart+8).":O".($rowstart+11));






			
			
			$excel->getActiveSheet()->mergeCells("B".$rowCount.":B".($rowCount+1));
			$excel->getActiveSheet()->mergeCells("C".$rowCount.":C".($rowCount+1));
			$excel->getActiveSheet()->mergeCells("D".$rowCount.":D".($rowCount+1));
			$excel->getActiveSheet()->mergeCells("E".$rowCount.":E".($rowCount+1));

			$excel->getActiveSheet()->mergeCells("B".($rowCount+2).":B".($rowCount+3));
			$excel->getActiveSheet()->mergeCells("C".($rowCount+2).":C".($rowCount+3));
			$excel->getActiveSheet()->mergeCells("D".($rowCount+2).":D".($rowCount+3));
			$excel->getActiveSheet()->mergeCells("E".($rowCount+2).":E".($rowCount+3));

			$excel->getActiveSheet()->mergeCells("B".($rowCount+4).":B".($rowCount+5));
			$excel->getActiveSheet()->mergeCells("C".($rowCount+4).":C".($rowCount+5));
			$excel->getActiveSheet()->mergeCells("D".($rowCount+4).":D".($rowCount+5));
			$excel->getActiveSheet()->mergeCells("E".($rowCount+4).":E".($rowCount+5));

			$excel->getActiveSheet()->mergeCells("B".($rowCount+8).":B".($rowCount+9));
			$excel->getActiveSheet()->mergeCells("C".($rowCount+8).":C".($rowCount+9));
			$excel->getActiveSheet()->mergeCells("D".($rowCount+8).":D".($rowCount+9));
			$excel->getActiveSheet()->mergeCells("E".($rowCount+8).":E".($rowCount+9));

			$excel->getActiveSheet()->mergeCells("B".($rowCount+10).":B".($rowCount+11));
			$excel->getActiveSheet()->mergeCells("C".($rowCount+10).":C".($rowCount+11));
			$excel->getActiveSheet()->mergeCells("D".($rowCount+10).":D".($rowCount+11));
			$excel->getActiveSheet()->mergeCells("E".($rowCount+10).":E".($rowCount+11));

			$excel->getActiveSheet()->mergeCells("B".($rowCount+12).":B".($rowCount+13));
			$excel->getActiveSheet()->mergeCells("C".($rowCount+12).":C".($rowCount+13));
			$excel->getActiveSheet()->mergeCells("D".($rowCount+12).":D".($rowCount+13));
			$excel->getActiveSheet()->mergeCells("E".($rowCount+12).":E".($rowCount+13));


			
			$excel->getActiveSheet()->mergeCells("B".($rowCount+6).":B".($rowCount+7));
			$excel->getActiveSheet()->mergeCells("C".($rowCount+6).":C".($rowCount+7));
			$excel->getActiveSheet()->mergeCells("D".($rowCount+6).":D".($rowCount+7));
			$excel->getActiveSheet()->mergeCells("E".($rowCount+6).":E".($rowCount+7));

			$excel->getActiveSheet()->unmergeCells("A".$rowCount.":A".($rowstart-1));

			$excel->getActiveSheet()->unmergeCells("B".$rowCount.":B".($rowCount+1));
			$excel->getActiveSheet()->unmergeCells("C".$rowCount.":C".($rowCount+1));
			$excel->getActiveSheet()->unmergeCells("D".$rowCount.":D".($rowCount+1));
			$excel->getActiveSheet()->unmergeCells("E".$rowCount.":E".($rowCount+1));


			$excel->getActiveSheet()->unmergeCells("B".($rowCount+2).":B".($rowCount+3));
			$excel->getActiveSheet()->unmergeCells("C".($rowCount+2).":C".($rowCount+3));
			$excel->getActiveSheet()->unmergeCells("D".($rowCount+2).":D".($rowCount+3));
			$excel->getActiveSheet()->unmergeCells("E".($rowCount+2).":E".($rowCount+3));

			$excel->getActiveSheet()->unmergeCells("B".($rowCount+4).":B".($rowCount+5));
			$excel->getActiveSheet()->unmergeCells("C".($rowCount+4).":C".($rowCount+5));
			$excel->getActiveSheet()->unmergeCells("D".($rowCount+4).":D".($rowCount+5));
			$excel->getActiveSheet()->unmergeCells("E".($rowCount+4).":E".($rowCount+5));

			$excel->getActiveSheet()->unmergeCells("B".($rowCount+6).":B".($rowCount+7));
			$excel->getActiveSheet()->unmergeCells("C".($rowCount+6).":C".($rowCount+7));
			$excel->getActiveSheet()->unmergeCells("D".($rowCount+6).":D".($rowCount+7));
			$excel->getActiveSheet()->unmergeCells("E".($rowCount+6).":E".($rowCount+7));



			$excel->getActiveSheet()->unmergeCells("B".($rowCount+8).":B".($rowCount+9));
			$excel->getActiveSheet()->unmergeCells("C".($rowCount+8).":C".($rowCount+9));
			$excel->getActiveSheet()->unmergeCells("D".($rowCount+8).":D".($rowCount+9));
			$excel->getActiveSheet()->unmergeCells("E".($rowCount+8).":E".($rowCount+9));

			$excel->getActiveSheet()->unmergeCells("B".($rowCount+10).":B".($rowCount+11));
			$excel->getActiveSheet()->unmergeCells("C".($rowCount+10).":C".($rowCount+11));
			$excel->getActiveSheet()->unmergeCells("D".($rowCount+10).":D".($rowCount+11));
			$excel->getActiveSheet()->unmergeCells("E".($rowCount+10).":E".($rowCount+11));

			$excel->getActiveSheet()->unmergeCells("B".($rowCount+12).":B".($rowCount+13));
			$excel->getActiveSheet()->unmergeCells("C".($rowCount+12).":C".($rowCount+13));
			$excel->getActiveSheet()->unmergeCells("D".($rowCount+12).":D".($rowCount+13));
			$excel->getActiveSheet()->unmergeCells("E".($rowCount+12).":E".($rowCount+13));

			$excel->getActiveSheet()->unmergeCells("G".($rowstart).":G".($rowstart+3));
			$excel->getActiveSheet()->unmergeCells("H".($rowstart).":H".($rowstart+3));
			$excel->getActiveSheet()->unmergeCells("I".($rowstart).":I".($rowstart+3));
			$excel->getActiveSheet()->unmergeCells("J".($rowstart).":J".($rowstart+3));
			$excel->getActiveSheet()->unmergeCells("K".($rowstart).":K".($rowstart+3));
			$excel->getActiveSheet()->unmergeCells("L".($rowstart).":O".($rowstart+3));


			$excel->getActiveSheet()->unmergeCells("G".($rowstart+4).":G".($rowstart+7));
			$excel->getActiveSheet()->unmergeCells("H".($rowstart+4).":H".($rowstart+7));
			$excel->getActiveSheet()->unmergeCells("I".($rowstart+4).":I".($rowstart+7));
			$excel->getActiveSheet()->unmergeCells("J".($rowstart+4).":J".($rowstart+7));
			$excel->getActiveSheet()->unmergeCells("K".($rowstart+4).":K".($rowstart+7));
			$excel->getActiveSheet()->unmergeCells("L".($rowstart+4).":O".($rowstart+7));


			$excel->getActiveSheet()->unmergeCells("G".($rowstart+8).":G".($rowstart+11));
			$excel->getActiveSheet()->unmergeCells("H".($rowstart+8).":H".($rowstart+11));
			$excel->getActiveSheet()->unmergeCells("I".($rowstart+8).":I".($rowstart+11));
			$excel->getActiveSheet()->unmergeCells("J".($rowstart+8).":J".($rowstart+11));
			$excel->getActiveSheet()->unmergeCells("K".($rowstart+8).":K".($rowstart+11));
			$excel->getActiveSheet()->unmergeCells("L".($rowstart+8).":O".($rowstart+11));

			$rowMain=$rowstart+4;
			
			$excel->getActiveSheet()->removeRow(($rowCount),$row_delete);
			$excel->getActiveSheet()->mergeCells("N".($rowMain).":P".($rowMain+3));
			$excel->getActiveSheet()->unmergeCells("N".($rowMain).":P".($rowMain+3));
//			addContent(setRange("N".($rowMain),"P".($rowMain+3)),$excel,"","false",$ExWs);

			
			
	$signatorySQL="select * from signatories order by signatory_date DESC";
	$signatoryRS=$db2->query($signatorySQL);
	$signatoryNM=$signatoryRS->num_rows;

	if($signatoryNM>0){
		$signatoryRow=$signatoryRS->fetch_assoc();
		if(strtotime($tar_date)>=strtotime($signatoryRow['signatory_date'])){
			$chief=$signatoryRow['chief_transport'];	

			addContent(setRange("N".($rowMain+3),"P".($rowMain+3)),$excel,$chief,"false",$ExWs);

		}
		else {
			$sig2="select * from signatories where signatory_date>'".$tar_date."' order by signatory_date asc";

			$sigRS=$db2->query($sig2);
			$sigRow=$sigRS->fetch_assoc();
			
			$chief=$sigRow['chief_transport'];	

			addContent(setRange("N".($rowMain+3),"P".($rowMain+3)),$excel,$chief,"false",$ExWs);
			
		
		
		}
		
	
	}			
			
			
			
//			addContent(setRange("N".($rowMain+3),"P".($rowMain+3)),$excel,"JOSE RIC M. INOTORIO","false",$ExWs);

			
//			$row_delete=186-$rowCount;
//			$excel->getActiveSheet()->removeRow(($rowCount),1000);		

			
	save($ExWb,$excel,$newFilename); 	
	echo "Train Availability Report has been generated!  Press right click and Save As: <a href='".$newFilename."'>Here</a>";




}
?>
