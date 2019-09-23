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
$db=new mysqli("localhost","root","","transport");

if(isset($_GET['sd'])){
$year=date("Y",strtotime($_GET['sd']));
$start_date=date("Y-m-d",strtotime($_GET['sd']));

if($_GET['range']=="daily"){
$end_date=$start_date;	
}
else if($_GET['range']=="weekly"){
$end_date=date("Y-m-d",strtotime($_GET['sd']."+1 week"));
	
}

else if($_GET['range']=="monthly"){
$end_date=date("Y-m-d",strtotime($_GET['sd']."+1 month"));
	
}
else if($_GET['range']=="yearly"){
$end_date=date("Y-m-d",strtotime($_GET['sd']."+365 days"));
	
}
else if($_GET['range']=="custom"){
$end_date=date("Y-m-d",strtotime($_GET['sd']));
	
}
}
else {
$start_date=date("Y")."-01-01";
$end_date=date("Y")."-12-31";	
	
}


if(isset($_GET['sd'])){
	//$year=$_GET['year'];
	$level=$_GET['level'];

	$filename="Statistics Report 2.xls";

	$oldfilename="forms/".$filename;
	$dateSlip=date("Y-m-d His");
	$newFilename="printout/Stats Report_".$dateSlip.".xls";
	copy($oldfilename,$newFilename);

	$workSheetName="Statistics Report";	
	$workbookname=$newFilename;
	$excel=loadExistingWorkbook($workbookname);

  	$ExWs=createWorksheet($excel,$workSheetName,"openActive");


	
	$db=new mysqli("localhost","root","","transport");
	$sql="select * from equipment where id in ('114','102','110','11','113','104','108','109','103','124','67','111','112','105','81','118','119','64','115','89','120','123','121','116','2','122','117','105','81','118','119','64','115','89','120','123','121','116','2','122','117') order by equipment_name";

	$rs=$db->query($sql);

	$nm=$rs->num_rows;

	



	for($i=0;$i<$nm;$i++){
		$row=$rs->fetch_assoc();

		$equipt[$i]['id']=$row['id'];
		$equipt[$i]['equipment']=$row['equipment_name'];
		for ($k=$start;$k<=$end;$k++){
			$equipt_count["Equipt_".$row['id']]["Month_".$k]=0;
			
		}
	}
	






	

	$styleArray = array(
				'borders' => array(
					'outline' => array(
						'style' => PHPExcel_Style_Border::BORDER_THICK,
					),
				),
			);	
			$styleArray2 = array(
				'borders' => array(
					'outline' => array(
						'style' => PHPExcel_Style_Border::BORDER_THIN,
					),
				),
			);	
			

			
			
			
if(isset($_GET['sd'])){

$year=date("Y",strtotime($_GET['sd']));

$start_date=date("Y-m-d",strtotime($_GET['sd']));

$init_start=$start_date;


if($_GET['range']=="daily"){
$end_date=$start_date;	
}
else if($_GET['range']=="weekly"){
$end_date=date("Y-m-d",strtotime($_GET['sd']."+1 week"));

	$start=(date("m",strtotime($start_date)))*1;
	$end=date("m",strtotime($end_date."-1 day"))*1;

	$period=date("F d, Y", strtotime($start_date))." - ".date("F d, Y", strtotime($end_date));
	
}

else if($_GET['range']=="monthly"){
$end_date=date("Y-m-d",strtotime($_GET['sd']."+1 month"));

	$start=date("m",strtotime($start_date))*1;
	$end=date("m",strtotime($end_date))*1;
	$end--;

	$period=date("F Y", strtotime($start_date));
	
}
else if($_GET['range']=="yearly"){
$end_date=date("Y-m-d",strtotime($_GET['sd']."+365 days"));

	$start=(date("m",strtotime($start_date)))*1;
	$end=date("m",strtotime($end_date."-1 day"))*1;

	$period=date("F", strtotime($start_date))." - ".date("F d, Y", strtotime($end_date));
	
}
else if($_GET['range']=="custom"){
$end_date=date("Y-m-d",strtotime($_GET['ed']));

	$start=(date("m",strtotime($start_date)))*1;
	$end=date("m",strtotime($end_date))*1;

	$period=date("F d, Y", strtotime($start_date))." - ".date("F d, Y", strtotime($end_date));
	
}


	
}
else {
$start_date=date("Y")."-01-01";
$end_date=date("Y")."-12-31";	
	
	$start=1;
	$end=12;


	$period=date("F", strtotime($start_date))." - ".date("F Y", strtotime($end_date));

	
}



	$rowCount=2;

	
	$ssql="select * from incident_report where incident_date between '".$start_date." 00:00:00' and '".$end_date." 23:23:59' and level_condition='5'";

	$srs=$db->query($ssql);

	$snm=$rs->num_rows;
	
	

	addContent(setRange("A".$rowCount,"G".$rowCount),$excel,"Summary of DOTr-MRT3 Causes of Passengers Unloading Data","true",$ExWs);
$excel->getActiveSheet()->getStyle("A".$rowCount.":P".$rowCount)->getFont()->setBold(true);

	$rowCount++;
	addContent(setRange("A".$rowCount,"G".$rowCount),$excel,"Total No. of Unloading Incidents: ".$snm,"true",$ExWs);
$excel->getActiveSheet()->getStyle("A".$rowCount.":P".$rowCount)->getFont()->setBold(true);

	$rowCount++;
	
	addContent(setRange("A".$rowCount,"G".$rowCount),$excel,"Period: ".$period,"true",$ExWs);
$excel->getActiveSheet()->getStyle("A".$rowCount.":P".$rowCount)->getFont()->setBold(true);

	$rowCount+=2;

		$prefix=chr((65*1));
		$excel->getActiveSheet()->getStyle($prefix.$rowCount.":".$prefix.$rowCount)->applyFromArray($styleArray);


		addContent(setRange($prefix.$rowCount,$prefix.$rowCount),$excel,"Cause of Failure","true",$ExWs);


		$n=1;
	
for($k=$start;$k<=$end;$k++){
		
		$prefix=chr((65*1+$n));
		$excel->getActiveSheet()->getStyle($prefix.$rowCount.":".$prefix.$rowCount)->applyFromArray($styleArray);


		addContent(setRange($prefix.$rowCount,$prefix.$rowCount),$excel,date("F",strtotime(date("Y")."-".$k."-01")),"true",$ExWs);

		
		$n++;
		
	}
	
		$prefix=chr((65*1+($n)));
		$excel->getActiveSheet()->getStyle($prefix.$rowCount.":".$prefix.$rowCount)->applyFromArray($styleArray);


		addContent(setRange($prefix.$rowCount,$prefix.$rowCount),$excel,"Total","true",$ExWs);
	
	
	
for($i=$start;$i<=$end;$i++){
		$month_heading=date("F",strtotime($year."-".$i."-01"));
		$date_limit=date("t",strtotime($year."-".$i."-01"));
		
		$start_date=date("Y-m-d",strtotime($year."-".$i."-01"));
		$end_date=date("Y-m-d",strtotime($year."-".$i."-".$date_limit));
		
		$sql="select *,count(1) as equipt_count from incident_report where level='".$level."' and incident_date between '".$start_date." 00:00:00' and '".$end_date." 23:59:59' and equipt in ('114','102','110','11','113','104','108','109','103','124','67','111','112','105','81','118','119','64','115','89','120','123','121','116','2','122','117','105','81','118','119','64','115','89','120','123','121','116','2','122','117') group by equipt";
		$rs=$db->query($sql);
		$nm=$rs->num_rows;
		
		for($k=0;$k<$nm;$k++){
			$row=$rs->fetch_assoc();
			$equipt_count["Equipt_".$row['equipt']]["Month_".$i]=$row['equipt_count'];
			
		}

		$sql="select *,count(1) as equipt_count from incident_report inner join external.incident_defects on incident_report.id=external.incident_defects.incident_id where level='".$level."' and incident_date between '".$start_date." 00:00:00' and '".$end_date." 23:59:59' and external.incident_defects.equipt_id in ('114','102','110','11','113','104','108','109','103','124','67','111','112','105','81','118','119','64','115','89','120','123','121','116','2','122','117','105','81','118','119','64','115','89','120','123','121','116','2','122','117') group by equipt"; 
		$rs=$db->query($sql);
		$nm=$rs->num_rows;
		
		for($k=0;$k<$nm;$k++){
			$row=$rs->fetch_assoc();
			$equipt_count["Equipt_".$row['equipt_id']]["Month_".$i]+=$row['equipt_count'];
		}		

	}



	$sql="select * from equipment where id in ('114','102','110','11','113','104','108','109','103','124','67','111','112','105','81','118','119','64','115','89','120','123','121','116','2','122','117','105','81','118','119','64','115','89','120','123','121','116','2','122','117') order by equipment_name";

	$rs=$db->query($sql);

	$nm=$rs->num_rows;


	$rowCount++;
	
	
	$start_count=$rowCount;
	
	for($i=0;$i<$nm;$i++){
		$row=$rs->fetch_assoc();

		$prefix="A";
		$excel->getActiveSheet()->getStyle($prefix.$rowCount.":".$prefix.$rowCount)->applyFromArray($styleArray2);


		addContent(setRange("A".$rowCount,"A".$rowCount),$excel,$row['equipment_name'],"true",$ExWs);

		
		for($n=0;$n<=($end-$start);$n++){
			
			$k=$n+1;
			$prefix=chr((65*1+$k));
			if($n==0){
				$start_pref=$prefix;
			}
		
			$excel->getActiveSheet()->getStyle($prefix.$rowCount.":".$prefix.$rowCount)->applyFromArray($styleArray2);
			addContent(setRange($prefix.$rowCount,$prefix.$rowCount),$excel,$equipt_count["Equipt_".$equipt[$i]['id']]["Month_".($start+$n)],"true",$ExWs);
		

		}
		
		$end_pref=$prefix;
		$k++;
		$prefix=chr((65*1+($k)));
		addContent(setRange($prefix.$rowCount,$prefix.$rowCount),$excel,"=sum(".$start_pref.$rowCount.":".$end_pref.$rowCount.")","true",$ExWs);
		$excel->getActiveSheet()->getStyle($prefix.$rowCount.":".$prefix.$rowCount)->applyFromArray($styleArray2);
		$end_pref=$prefix;

		$prefix2=chr((65*1+($k+1)));

		addContent(setRange($prefix2.$rowCount,$prefix2.$rowCount),$excel,"=if(".$prefix.$rowCount.">Z8,1,0)","true",$ExWs);
		$rowCount++;	
	}
	addContent(setRange("Z7","Z7"),$excel,"=MAX(".$end_pref.$start_count.":".$end_pref.($start_count*1+$nm).")","true",$ExWs);
	addContent(setRange("Z8","Z8"),$excel,"=Z7*.6","true",$ExWs);


$rowCount++;

addContent(setRange("A".$rowCount,"G".$rowCount),$excel,"Legend:","true",$ExWs);
$rowCount++;

addContent(setRange("A".$rowCount,"G".$rowCount),$excel,"ATP - Automatic Train Protection:","true",$ExWs);

addContent(setRange("I".$rowCount,"M".$rowCount),$excel,"ACU - Air Condition Unit","true",$ExWs);

$rowCount++;

addContent(setRange("A".$rowCount,"G".$rowCount),$excel,"DCI -  Drive Circuit Interlocking","true",$ExWs);
$rowCount++;

addContent(setRange("A".$rowCount,"G".$rowCount),$excel,"FUV - Filter Under Voltage","true",$ExWs);
$rowCount++;
$rowCount++;
$rowCount++;

addContent(setRange("C".$rowCount,"F".$rowCount),$excel,"Prepared By: ","true",$ExWs);
addContent(setRange("J".$rowCount,"L".$rowCount),$excel,"Reviewed By: ","true",$ExWs);

$rowCount++;
$rowCount++;
$rowCount++;


addContent(setRange("C".$rowCount,"F".$rowCount),$excel,"ENGR. EDWIN S. HILARIO","true",$ExWs);
addContent(setRange("J".$rowCount,"L".$rowCount),$excel,"ENGR. OLIVER S. CASILI","true",$ExWs);

$excel->getActiveSheet()->getStyle("C".$rowCount.":P".$rowCount)->getFont()->setBold(true);

$rowCount++;



addContent(setRange("C".$rowCount,"F".$rowCount),$excel,"Senior TDO, Transport Division","true",$ExWs);
addContent(setRange("J".$rowCount,"L".$rowCount),$excel,"OIC, Transport Division","true",$ExWs);

$rowCount++;










	save($ExWb,$excel,$newFilename); 	
	echo "Statistics Report has been generated!  Press right click and Save As: <a href='".$newFilename."'>Here</a>";




}
?>
