<?php
session_start();
?>
<?php
require_once("phpexcel/Classes/PHPExcel.php");
require_once("phpexcel/Classes/PHPExcel/IOFactory.php");
require("excel_functions.php");

?>
<?php
ini_set("date.timezone","Asia/Kuala_Lumpur");
?>
<?php
if(isset($_GET['clearance_date'])){
	$clearance_date=$_GET['clearance_date'];
	
	$filename="ClearanceForm.xls";

	$oldfilename="forms/".$filename;
	$dateSlip=date("Y-m-d His");	
	
	$newFilename="printout/Clearance_".$dateSlip.".xls";
	copy($oldfilename,$newFilename);

	$workSheetName="CLEARANCE";	
	$workbookname=$newFilename;
	$excel=loadExistingWorkbook($workbookname);

  	$ExWs=createWorksheet($excel,$workSheetName,"openActive");
	
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
	
	$rowCount=0;	
	
	$rowCount+=12;
	
	$sql="select * from clearance where date like '".$clearance_date."%%' order by clearance_no";
	$rs=$db->query($sql);
	$nm=$rs->num_rows;	

	addContent(setRange("N8","O8"),$excel,date("F d, Y",strtotime($clearance_date)),"true",$ExWs);

	addContent(setRange("B8","C8"),$excel,date("l",strtotime($clearance_date)),"true",$ExWs);

	
	$page_counter=1;
	for($i=0;$i<$nm;$i++){
		$row=$rs->fetch_assoc();
		$clearance_no=$row['clearance_no'];
		$location=$row['location'];
		$activity=$row['activity'];
		$person=$row['person'];
		$position=$row['position'];
		$company=$row['company'];
		$received_by=$row['received_by'];
		$login=$row['login'];
		$logout=$row['logout'];
	
		$sql2="select * from train_driver where id='".$received_by."'";
		$rs2=$db->query($sql2);
		$nm2=$rs2->num_rows;
		
		if($nm2>0){
			$row2=$rs2->fetch_assoc();
			$received_by=$row2['position']." ".substr($row2['firstName'],0,1).". ".$row2['lastName'];
		
		}
		
		if($login=="0000-00-00 00:00:00"){
			$login="";
		}
		else {
			$login=date("H:i",strtotime($row['login']));
		}
			
		if($logout=="0000-00-00 00:00:00"){
			$logout="";
		}
		else {
			$logout=date("H:i",strtotime($row['logout']));
		}
		$control_no=$row['control_no'];

		addContent(setRange("A".$rowCount,"A".$rowCount),$excel,$clearance_no,"true",$ExWs);
		addContent(setRange("B".$rowCount,"B".$rowCount),$excel,$location,"true",$ExWs);
		addContent(setRange("C".$rowCount,"F".$rowCount),$excel,$activity,"true",$ExWs);
		addContent(setRange("G".$rowCount,"H".$rowCount),$excel,$person,"true",$ExWs);
		addContent(setRange("I".$rowCount,"J".$rowCount),$excel,$position." / ".$company,"true",$ExWs);

		addContent(setRange("K".$rowCount,"L".$rowCount),$excel,$received_by,"true",$ExWs);
		addContent(setRange("M".$rowCount,"M".$rowCount),$excel,$login,"true",$ExWs);
		addContent(setRange("N".$rowCount,"N".$rowCount),$excel,$logout,"true",$ExWs);
		
		addContent(setRange("O".$rowCount,"P".$rowCount),$excel,$control_no,"true",$ExWs);
		
		
		if($page_counter==18){	
			$page_counter=1;	

			$rowCount+=12;
		}
		else {
			$page_counter++;
			$rowCount++;	
		}
		
	}
	

	

	save($ExWb,$excel,$newFilename); 	
	echo "<br>";
	echo "Clearance Form has been generated!  Press right click and Save As: <a href='".$newFilename."'>Here</a>";		
	
}

?>
