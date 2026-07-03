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

	$filename="CCDR.xls";

	$oldfilename="forms/".$filename;
	$dateSlip=date("Y-m-d His");
	$newFilename="printout/CCDR_".$dateSlip.".xls";
	copy($oldfilename,$newFilename);
	$workSheetName="CCDR";	
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
	
	
	
	
	
	
	$db=iss_db('transport'); /* centralized -- see db_config.php */

	/* item #3 fix: position('' IN incident_no) always returns 1 (MySQL matches an
	   empty string at the very start), so this sorted every incident by just its
	   FIRST CHARACTER -- confirmed by direct test against a live MySQL instance.
	   Commented out rather than removed, per your request, since it might be
	   referenced again later:
	$sql="select * from incident_report inner join incident_description on incident_report.id=incident_description.incident_id where incident_date ".$dClause." order by substring(incident_no,1,position('' in incident_no))*1 ";
	*/
	$sql="select * from incident_report inner join incident_description on incident_report.id=incident_description.incident_id where incident_date ".$dClause." order by substring(incident_no,1,position(' ' in incident_no)-1)*1 ";
	$rs=$db->query($sql);

	$nm=$rs->num_rows;
	
	$rowCount+=14;
	$page_counter=1;


	addContent(setRange("L9","N9"),$excel,date("F d, Y",strtotime($ccdr_date)),"true",$ExWs);

	addContent(setRange("L8","N8"),$excel,date("l",strtotime($ccdr_date)),"true",$ExWs);	
	
	$db=iss_db('transport');
	$timeTableSQL="select *,timetable_day.id as timeId from timetable_day inner join timetable_code on timetable_day.timetable_code=timetable_code.id where train_date like '".$ccdr_date."%%'";

	$timeTableRS=$db->query($timeTableSQL);
	$timeTableNM=$timeTableRS->num_rows;
	if($timeTableNM>0){
		$timeTableRow=$timeTableRS->fetch_assoc();
		addContent(setRange("L10","N10"),$excel,$timeTableRow['code'],"true",$ExWs);
		
		
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
				
		addContent(setRange("D190","F190"),$excel,$recording,"true",$ExWs);
		addContent(setRange("A190","C190"),$excel,$clerk,"true",$ExWs);
		addContent(setRange("G190","I190"),$excel,$duty_manager,"true",$ExWs);

				
				
			
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
			
			addContent(setRange("J190","M190"),$excel,$chief,"false",$ExWs);

			addContent(setRange("J13","M13"),$excel,$maintenance,"false",$ExWs);

			addContent(setRange("B8","B8"),$excel,$gm,"false",$ExWs);
			addContent(setRange("B9","B9"),$excel,$gm_office,"false",$ExWs);
			addContent(setRange("G8","H8"),$excel,$director,"false",$ExWs);

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

			addContent(setRange("J13","M13"),$excel,$maintenance,"false",$ExWs);

			addContent(setRange("J190","M190"),$excel,$chief,"false",$ExWs);
			addContent(setRange("B8","B8"),$excel,$gm,"false",$ExWs);
			addContent(setRange("B9","B9"),$excel,$gm_office,"false",$ExWs);
			addContent(setRange("G8","H8"),$excel,$director,"false",$ExWs);
			
		
		
		
		
		}
		
	
	}	
	
	
	
	for($i=0;$i<$nm;$i++){
		$row=$rs->fetch_assoc();


		$car[0]="";
		$car[1]="";
		$car[2]="";
		$car[3]=""; /* item #2 fix: fourth car was never read here, though incident_report.php's
		               write side has stored up to 4 since our earlier multi-equipment work */

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
		


		$incident_type=$row['incident_type'];
		
		$hourStamp=date("Hi",strtotime($row['incident_date']));
		$incident_no=$row['incident_no'];
		$duration=$row['duration'];
		$description="";
		
		
		
		
		$location=$row['location'];
		$reported_by=$row['reported_by'];

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
		
		addContent(setRange("A".$rowCount,"A".$rowCount),$excel,$incident_no,"true",$ExWs);
		addContent(setRange("B".$rowCount,"B".$rowCount),$excel,"'".$hourStamp,"true",$ExWs);
		addContent(setRange("C".$rowCount,"C".$rowCount),$excel,$duration,"true",$ExWs);
		addContent(setRange("D".$rowCount,"F".$rowCount),$excel,$description,"true",$ExWs);
		addContent(setRange("G".$rowCount,"I".$rowCount),$excel,$action_dotc,"true",$ExWs);
		addContent(setRange("J".$rowCount,"M".$rowCount),$excel,$action_maintenance,"true",$ExWs);

		addContent(setRange("N".$rowCount,"N".$rowCount),$excel,$level,"true",$ExWs);
		
		if($page_counter==14){	
			$page_counter=1;	
			
			$rowCount+=14;
			
		
		}
		else {
			$page_counter++;
			$rowCount++;	
		}
	
		

	
		if($i==($nm-1)){
		
			$excel->getActiveSheet()->mergeCells("J".($rowCount+1).":M".($rowCount+1));

			$excel->getActiveSheet()->unmergeCells("J".($rowCount+1).":M".($rowCount+1));
			$excel->getActiveSheet()->mergeCells("J".($rowCount+5).":M".($rowCount+5));

			$excel->getActiveSheet()->unmergeCells("J".($rowCount+5).":M".($rowCount+5));

			
		
			$row_delete=186-$rowCount;
			$excel->getActiveSheet()->removeRow(($rowCount),$row_delete);
			
		}
	
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