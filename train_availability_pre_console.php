<?php 
require("Tmenu.php");
ini_set("date.timezone","Asia/Kuala_Lumpur");
?>
<!--- Modified by Jun
//--- Date: 8/1/2014
//--- Modify: Screen layout and color
//--- Marker: @mjun
//--------------------------------------------------->
<?php
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");

function getTrainDriver($id,$dbase){

//$db=new mysqli("127.0.0.1","root","","is_transport");
	$sql="select firstName,lastName,position from train_driver where id='".$id."' limit 1";

	
	$rs=$dbase->query($sql);
	$row=$rs->fetch_assoc();
	
	$name=$row['position']." ".substr($row['firstName'],0,1).". ".$row['lastName'];

	return $name;


}
function getPHTrainDriver($id,$dbase){

//$db=new mysqli("127.0.0.1","root","","is_transport");
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
//$db=new mysqli("127.0.0.1","root","","is_transport");
	$sql="select * from level where incident_id='".$id."'";

	$rs=$dbase->query($sql);
	$row=$rs->fetch_assoc();
	$level=$row['order'];
	return $level;

}
function insertCompo($train_id,$car){
	
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
	if($car==""){
	}
	else {
		$update="insert into train_compo(tar_id,car_no) values ('".$train_id."','".$car."')";
		$updateRS=$db->query($update);
	}
		
	
}


/// ORDINAL NUMBER TRANSLATOR
function ordinal_numbers($NUM)
{
	if(strlen($NUM)>1 && substr($NUM, -2, 1) == 1) // TEENS
	{ return "th"; }

	else // ALL OTHERS
	{
		$num = substr($NUM, -1); /// GET LAST NUMBER
		if($num == 0) { return "th"; }
		if($num == 1) { return "st"; }
		if($num == 2) { return "nd"; }
		if($num == 3) { return "rd"; }
		if($num >= 4 && $num <= 9) { return "th"; }
	}
}
function getOrdinal($number){
$ends = array('th','st','nd','rd','th','th','th','th','th','th');
if (($number %100) >= 11 && ($number%100) <= 13)
   $abbreviation = $number. 'th';
else
   $abbreviation = $number. $ends[$number % 10];

   
 return $abbreviation;  

}
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

	$update="insert into train_availability(index_no,date,car_a,car_b,car_c,car_d,lpam_id,status,type) values ";
	$update.="('".$index_no."','".$availability_date."','".$car_a."','".$car_b."','".$car_c."','".$car_d."','".$lpam_id."','active','".$type."')";			
	
	$rs=$db->query($update);	
	$index_id=$db->insert_id;
	
	insertCompo($index_id,$car_a);
	insertCompo($index_id,$car_b);
	insertCompo($index_id,$car_c);
	insertCompo($index_id,$car_d);

	if(isset($_POST['cancel_departure'])){
		$availability_date="";
		$update="update train_availability set status='cancelled' where id='".$index_id."'";
		$rs=$db->query($update);


		echo "<script language='javascript'>";	
		echo "window.open('incident report.php?cancel=".$index_id."');";
		echo "</script>";
		
		
		
	}
	
	$update="insert into train_ava_time(train_ava_id,boundary_time) values ('".$index_id."','".$availability_date."')";
	
	$rs=$db->query($update);		
	
	
}

if(isset($_POST['other_index_no'])){

	$index_no=$_POST['other_index_no'];
	$lpam_id=$_POST['lpam_id'];
	
//	$car_a=$_POST['car_1'];
//	$car_b=$_POST['car_2'];
//	$car_c=$_POST['car_3'];
	
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

	
	$train_type=$_POST['train_type'];
	
	$update="insert into train_availability(index_no,date,status,type) values ";
	$update.="('".$index_no."','".$availability_date."','active','unimog')";			

	$rs=$db->query($update);	
	$index_id=$db->insert_id;
	
	if(isset($_POST['cancel_departure'])){
		$update="update train_availability set status='cancelled' where id='".$index_id."'";
		$rs=$db->query($update);
	
		$availability_date="";
		echo "<script language='javascript'>";	
		echo "window.open('incident report.php?cancel=".$index_id."');";
		echo "</script>";
		
		
		
	}	
	
	
	
	$update="insert into train_ava_time(train_ava_id,boundary_time) values ('".$index_id."','".$availability_date."')";
	
//	$update="insert into train_ava_time(train_ava_id,boundary_time) values ('".$index_id."','".$availability_date."','".$train_type."')";
	$rs=$db->query($update);		
}

if(isset($_POST['insertion_id'])){
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

	if($_POST['hour']==""){
		$availability_date="";
	}
	else {
	
	
		$availability_date=date("Y-m-d H:i",strtotime($year."-".$month."-".$day." ".$hour.":".$minute));
	}
	if(isset($_POST['unimog_train_driver'])){
		
		if($_POST['unimog_train_driver']=="other"){
			$train_driver=$_POST['unimog_td_alternate'];
		
		}
		else {
			$train_driver=$_POST['unimog_train_driver'];
		}
		
	}
	else {
		$train_driver=$_POST['train_driver'];
	
	}
	
	
	$sql="select * from train_ava_time where train_ava_id='".$_POST['insertion_id']."'";
	$rs=$db->query($sql);
	$nm=$rs->num_rows;

	if($nm>0){
		$sql="update train_ava_time set insert_time='".$availability_date."',insert_driver='".$train_driver."' where train_ava_id='".$_POST['insertion_id']."'";
		$rs=$db->query($sql);


		$sql="update train_ava_time set inserted_to='".$_POST['inserted_to']."' where train_ava_id='".$_POST['insertion_id']."'";
		$rs=$db->query($sql);
	}
	else {
		$sql="insert into train_ava_time(train_ava_id,insert_time,insert_driver,inserted_to) values ('".$_POST['insertion_id']."','".$availability_date."','".$train_driver."','".$_POST['inserted_to']."')";
		$rs=$db->query($sql);	
		
	}	
	
	
	$change="select * from train_availability where id='".$_POST['insertion_id']."'";
	$changeRS=$db->query($change);
	
	$changeRow=$changeRS->fetch_assoc();
	
	$train_date=$changeRow['date'];
	
	$_POST['year']=date("Y",strtotime($train_date));
	$_POST['month']=date("m",strtotime($train_date));
	$_POST['day']=date("d",strtotime($train_date));
	
	$_POST['hour']=date("H",strtotime($train_date));
	$_POST['minute']=date("i",strtotime($train_date));
	$_POST['amorpm']=date("A",strtotime($train_date));
	
}
if(isset($_POST['remove_id'])){
	$year=$_POST['year'];
	$month=$_POST['month'];
	$day=$_POST['day'];
	
	$hour=$_POST['hour'];
	$minute=$_POST['minute'];
	$amorpm=$_POST['amorpm'];

	
	if(isset($_POST['unimog_train_driver'])){
		
		if($_POST['unimog_train_driver']=="other"){
			$train_driver=$_POST['unimog_td_alternate'];
		
		}
		else {
			$train_driver=$_POST['unimog_train_driver'];
		}
		
	}
	else {
		$train_driver=$_POST['train_driver'];
	
	}
	
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
	if($_POST['hour']==""){
		$availability_date="";
	}
	else {

		$availability_date=date("Y-m-d H:i",strtotime($year."-".$month."-".$day." ".$hour.":".$minute));

	}
	$cancel_loop=$_POST['cancel_loop'];
//	echo $cancel_loop;

	$sql="update train_ava_time set remove_time='".$availability_date."',remove_driver='".$train_driver."',removal_remarks=\"".$_POST['remarks']."\" where train_ava_id='".$_POST['remove_id']."'";
	
	$rs=$db->query($sql);

	$sql="update train_ava_time set removed_from='".$_POST['removed_from']."' where train_ava_id='".$_POST['remove_id']."'";
	
	$rs=$db->query($sql);

	$change="select * from train_availability where id='".$_POST['remove_id']."'";
	$changeRS=$db->query($change);
	
	$changeRow=$changeRS->fetch_assoc();
	
	$train_date=$changeRow['date'];
	
	$_POST['year']=date("Y",strtotime($train_date));
	$_POST['month']=date("m",strtotime($train_date));
	$_POST['day']=date("d",strtotime($train_date));
	
	$_POST['hour']=date("H",strtotime($train_date));
	$_POST['minute']=date("i",strtotime($train_date));
	$_POST['amorpm']=date("A",strtotime($train_date));
	
	
	if(isset($_POST['cancel_loop'])){

		echo "<script language='javascript'>";
		echo "window.open('incident report.php?add_incident=".$_POST['remove_id']."')";
		echo "</script>";
	
	}
	
}

if(isset($_POST['remarks_id'])){

	$search="select * from train_ava_time where train_ava_id='".$_POST['remarks_id']."'";
	$searchRS=$db->query($search);
	$searchNM=$searchRS->num_rows;
	
	if($searchNM>0){
		$sql="update train_ava_time set removal_remarks=\"".$_POST['remarks']."\" where train_ava_id='".$_POST['remarks_id']."'";
		$rs=$db->query($sql);
	}
	else {
		$sql="insert into train_ava_time(removal_remarks,train_ava_id) values (\"".$_POST['remarks']."\",'".$_POST['remarks_id']."')";
		$rs=$db->query($sql);
	}
	
}

if(isset($_POST['switch_id'])){
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

	$sql="insert into train_switch(train_ava_id,new_index,date_change) values ('".$_POST['switch_id']."','".$_POST['new_index']."','".$availability_date."')";
//	$sql="update train_ava_time set remove_time='".$availability_date."',remove_driver='".$_POST['train_driver']."',removal_remarks=\"".$_POST['remarks']."\" where train_ava_id='".$_POST['remove_id']."'";
	$rs=$db->query($sql);
	
	$sql="select * from train_availability where id='".$_POST['switch_id']."' limit 1";
	$rs=$db->query($sql);
	$row=$rs->fetch_assoc();
	
	$type=$row['type'];
	if($type=="reserve"){
		$update="update train_availability set type='revenue' where id='".$_POST['switch_id']."'";
		$updateRS=$db->query($update);
	}
}

if(isset($_POST['edit_id'])){

	$sql="update train_availability set index_no='".$_POST['edit_index']."' where id='".$_POST['edit_id']."'";
//	$sql="update train_ava_time set remove_time='".$availability_date."',remove_driver='".$_POST['train_driver']."',removal_remarks=\"".$_POST['remarks']."\" where train_ava_id='".$_POST['remove_id']."'";
	$rs=$db->query($sql);
	

}
if(isset($_POST['edit_car'])){

	//Train Compo
	$update="delete from train_compo where tar_id='".$_POST['edit_car']."'";
	
	$rs=$db->query($update);


	$sql="update train_availability set car_a='".$_POST['car_1']."',car_b='".$_POST['car_2']."',car_c='".$_POST['car_3']."',car_d='".$_POST['car_4']."' where id='".$_POST['edit_car']."'";
//	$sql="update train_ava_time set remove_time='".$availability_date."',remove_driver='".$_POST['train_driver']."',removal_remarks=\"".$_POST['remarks']."\" where train_ava_id='".$_POST['remove_id']."'";
	$rs=$db->query($sql);

	insertCompo($index_id,$_POST['car_1']);
	insertCompo($index_id,$_POST['car_2']);
	insertCompo($index_id,$_POST['car_3']);

	insertCompo($index_id,$_POST['car_4']);
	

}
?>

<link href="css/modal_only.css" rel="stylesheet" />
<!-- <link href="css/style.min.css" rel="stylesheet" /> -->

<link rel="stylesheet" href="jquery-ui-themes-1.11.1/themes/smoothness/jquery-ui.css" />
<script src="jquery-ui-1.11.1/external/jquery/jquery.js"></script>
<script src="jquery-ui-1.11.1/jquery-ui.js"></script>


<style type='text/css'>

.modal {
	z-index: 99999;
	
}


/* header */
.rowClass {
	 background-color:#f5f5f5; 
}

.rowHeading {
	background-color:#cccccc;
}

.train_ava td{
	border: 1px solid #A9A9A9;
	cellpadding: 5px;
}

/* outline header */
 .train_ava th {
	border: 1px solid #A9A9A9;
	cellpadding: 5px;
}

/* input color */
input[type="text"]{ 
	height:25px; 
	font-weight:bold; 
	font-size:15px; 
	font-family:courier; 
	border: 1px solid #FFD700;
	background-color: #FFFACD;  
	border-radius: 3px
}

#cellHeading {	
	background-image: -webkit-gradient(linear, left bottom, left top, color-stop(0.38, #A9A9A9), color-stop(0.62, #cccccc));
	color: black;
	padding:5px;
	-moz-border-radius: 5px;
	border-radius: 5px;
}


input[type="text"]:focus {
	background-color:#FFFFF0;
	/* color:white; */
}

textarea:focus {
	background-color:#FFFFF0;
	/*color:white;*/
	font-weight:bold;
}

.date {
	text-style:bold;
	font-size:20px;
}

textarea{ 
	border: 1px solid #FFD700;
	background-color: #FFFACD;
	border-radius: 3px;
}


/* Add form below */
/* header */
#add_form th{
background-color: #cccccc;
}

#add_form td:nth-child(odd) {
background-color: #DCDCDC; 
color:black;
font-weight:bold;
padding:5px;
}

#add_form td:last-child{
background-color:white;
}

#add_form td:nth-child(even) {
background-color: #f5f5f5;
border:1px solid #cccccc;
}

select { border: 1px solid rgb(185, 201, 254); color: black; background-color: #FFFACD;  }

/* --- mjun -- generate */
a.two:visited {color:black;}
a.two:hover, a.two:active {font-size:120%; color:orange;}

/* unvisited link */
a.Llink:link { color: #FF0000;}
a.Llink:visited {color: black;}
a.Llink:hover { color: Orange;}
a.Llink:active {color: #0000FF;}

a.liR { text-decoration: none; }
a.liR:hover { font-weight: bold; color:red; border-bottom: solid 1px; border-top: solid 1px; }

a.LEdit:visited {color:blue;}
a.LDel:visited {color:red;}

.alink a.disabled {
        color: #666;
        text-decoration: none;
    }

/* ---- merged insert/remove cells ---- */
.ta-slot-cell {
    padding: 6px 8px !important;
    vertical-align: top !important;
    min-width: 100px;
}

.switch-cell {
    padding: 6px 8px !important;
    vertical-align: top !important;
    min-width: 100px;
}

.switch-index {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #1A2238;
    line-height: 1.35;
	
	
}

.switch-time {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #1A2238;
    line-height: 1.35;
	
	
}
.switch-driver {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #1A2238;
    line-height: 1.35;
	
	
}


.ta-slot-time {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #1A2238;
    line-height: 1.35;
}
.ta-slot-driver {
    display: block;
    font-size: 11px;
    color: #5A6275;
    line-height: 1.35;
    margin-top: 2px;
}
.ta-slot-actions {
    display: block;
    margin-top: 5px;
    height: 20px;
    visibility: hidden;
}

.switch-placeholder .ta-act{ opacity:0; }

.ta-slot-actions.ta-visible { visibility: visible; }
td.td-hover .ta-slot-actions { visibility: visible; }


.switch-placeholder:hover .ta-act{ opacity:1; }

.ta-act {
    display: inline-block !important;
    font-size: 10px !important;
    font-weight: 600 !important;
    text-decoration: none !important;
    padding: 2px 7px !important;
    border-radius: 3px !important;
    border: 1px solid #B8B0A2 !important;
    background: #F1EEE3 !important;
    color: #00529B !important;
    line-height: 1.5 !important;
    cursor: pointer !important;
    margin-right: 3px !important;
    float: none !important;
    width: auto !important;
}
.ta-act:hover { background:#00529B !important; color:#FFFFFF !important; border-color:#00529B !important; }
.ta-act-cancel { color:#B23A33 !important; border-color:#DDB5B3 !important; background:#FDF2F2 !important; }
.ta-act-cancel:hover { background:#B23A33 !important; color:#FFFFFF !important; border-color:#B23A33 !important; }
.ta-act-sep { font-size:10px !important; color:#C4BBAE !important; margin:0 2px; }
.ta-act.disabled { display:none !important; }

/* ---- Train Compo cell redesign ---- */
.tc-compo {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
}
.tc-car {
    display: inline-block;
    font-size: 13px;
    font-weight: 700;
    color: #00529B;
    text-decoration: none;
    background: #EEF4FB;
    border: 1px solid #C5D8EE;
    border-radius: 4px;
    padding: 2px 10px;
    min-width: 36px;
    text-align: center;
    transition: background 0.12s, color 0.12s;
    float: none !important;
    width: auto !important;
}
.tc-car:hover {
    background: #00529B;
    color: #FFFFFF;
    border-color: #00529B;
}
.tc-car.disabled { color: #888; border-color: #ddd; background: #f5f5f5; pointer-events:none; }
.tc-edit-wrap {
    margin-top: 4px;
    opacity: 0;
    transition: opacity 0.15s;
    height: 18px;
}

.editindex .ta-act {
	visibility:hidden;
	
	
}
.editindex:hover .ta-act{
	visibility:visible;
	
}


.tc-compo-cell:hover .tc-edit-wrap,
tr.tr-hover .tc-edit-wrap {
    opacity: 1;
}
.tc-edit-btn {
    font-size: 10px !important;
    font-weight: 500 !important;
    color: #5A6275 !important;
    text-decoration: none !important;
    background: transparent !important;
    border: 1px solid #D8D2C2 !important;
    border-radius: 3px !important;
    padding: 2px 7px !important;
    display: inline-block !important;
    float: none !important;
    width: auto !important;
    cursor: pointer !important;
}
.tc-edit-btn:hover {
    color: #00529B !important;
    border-color: #00529B !important;
    background: #EEF4FB !important;
}
.tc-edit-btn.disabled { display: none !important; }

</style>


<script language='javascript' src='ajax.js'></script>
<script language="javascript">
var driverHTML="";

function setHTML(){
	makeajax("processing.php?trainDriver=Y","getDriver");	

}

function setPH(){
	makeajax("processing.php?ph_trams=Y","getPhTrams");	

}

function setSchool(){
	makeajax("processing.php?supDriver=Y","getSchoolDriver");	

}

function getDriver(ajaxHTML){

	if(ajaxHTML=="None available"){
	}
	else {

		driverHTML="<select name='train_driver' id='train_driver'>";

		var driverTerms=ajaxHTML.split("==>");
		var count=(driverTerms.length)*1-1;
		
		for(var n=0;n<count;n++){
			var parts=driverTerms[n].split(";");
			driverHTML+="<option value='"+parts[0]+"'>";
			driverHTML+=parts[1].replace("_ENYE_","?");
			driverHTML+="</option>";
		
		}
		driverHTML+="</select>";
		
	}
	document.getElementById('td').innerHTML=driverHTML;
	
}


function getSchoolDriver(ajaxHTML){

	if(ajaxHTML=="None available"){
	}
	else {

		driverHTML="<select name='train_driver' id='train_driver'>";

		var driverTerms=ajaxHTML.split("==>");
		var count=(driverTerms.length)*1-1;
		
		for(var n=0;n<count;n++){
			var parts=driverTerms[n].split(";");
			driverHTML+="<option value='"+parts[0]+"'>";
			driverHTML+=parts[1].replace("_ENYE_","?");
			driverHTML+="</option>";
		
		}
		driverHTML+="</select>";
		
	}
	document.getElementById('school_tag').innerHTML=driverHTML;
	
}


function getPhTrams(ajaxHTML){

	if(ajaxHTML=="None available"){
	}
	else {

		driverHTML="<select name='unimog_train_driver' id='unimog_train_driver'>";

		var driverTerms=ajaxHTML.split("==>");
		var count=(driverTerms.length)*1-1;
		
		for(var n=0;n<count;n++){
			var parts=driverTerms[n].split(";");
			driverHTML+="<option value='"+parts[0]+"'>";
//			driverHTML+=parts[1].replace("_ENYE_","?");

			driverHTML+=parts[1];


			driverHTML+="</option>";
		
		}
		driverHTML+="<option value='other'>Other</option>";
		driverHTML+="</select>";
		
	}
	
	driverHTML+="<input style='border:1px solid gray' type=text name='unimog_td_alternate' />";	
	
	document.getElementById('ph_trams_tag').innerHTML=driverHTML;
	
}



function setDate(){
	var d=new Date();
	
	var year=d.getFullYear();
	var mmonth=d.getMonth()*1+1;
	var day=d.getDate();
	
	var tentativehour=d.getHours();
	var minute=d.getMinutes();
	var hour=0;
	
	var amorpm="AM";
	
	if(tentativehour==0){
		hour=12;
		
		amorpm="AM";
	
	}
	else {
		if(tentativehour>12){
			hour=tentativehour-12;
			amorpm="PM";
		}
		else {
			hour=tentativehour;
			amorpm="AM";
		}
	
	}
	
	
	
	dateHTML="<select name='month' id='month'>";
	dateHTML+="<option></option>";
	for(var i=1;i<=12;i++){
		d=new Date(year+"-"+i+"-1");	
		var month="";

		switch(i){
			case 1: month='January'; break;
			case 2: month='February'; break;
			case 3: month='March'; break;
			case 4: month='April'; break;
			case 5: month='May'; break;
			case 6: month='June'; break;
			case 7: month='July'; break;
			case 8: month='August'; break;
			case 9: month='September'; break;
			case 10: month='October'; break;
			case 11: month='November'; break;
			case 12: month='December'; break;
		
		}
		
		dateHTML+="<option value='"+i+"' "; 
		
		if(mmonth==i){
		dateHTML+="selected";
		}
		dateHTML+=">";
		dateHTML+=month;
		dateHTML+="</option>";
		
	}
	dateHTML+="</select>";

	
	dateHTML+="<select name='day' id='day'>";
	dateHTML+="<option></option>";

	for(var i=1;i<=31;i++){
		dateHTML+="<option value='"+i+"' ";
		if(day==i){
		dateHTML+="selected";
		}
		dateHTML+=">"+i+"</option>";
	}
	
	dateHTML+="</select>";

	yearLimit=year*1+16;
	dateHTML+="<select name='year' id='year'>";
	dateHTML+="<option></option>";

	for(var i=1999;i<=yearLimit;i++){
		dateHTML+="<option value='"+i+"' ";
		if(year==i){
		dateHTML+="selected";
		}
		dateHTML+=">"+i+"</option>";
	}
	
	dateHTML+="</select>";
	dateHTML+="<br>";
	dateHTML+="<select name='hour'>";
	dateHTML+="<option></option>";
	
	for(var i=1;i<=12;i++){
		dateHTML+="<option value='"+i+"' ";
		if(hour==i){
		dateHTML+="selected";
		}
		dateHTML+=">"+i+"</option>";
	}
	
	
	
	dateHTML+="</select>";

	dateHTML+="<select name='minute'>";
	dateHTML+="<option></option>";
	
	var label="";
	for(var i=0;i<=59;i++){
		
		if(i<10){
			label="0"+i;			
		}
		else {
			label=i;
		}
		
		dateHTML+="<option value='"+i+"' ";
		if(minute==i){
		dateHTML+="selected";
		}
		dateHTML+=">"+label+"</option>";

	}
	
	
	
	dateHTML+="</select>";
	dateHTML+="<select name='amorpm'>";
	dateHTML+="<option></option>";

	dateHTML+="<option value='am' ";
	if(amorpm=="AM"){
		dateHTML+="selected";
	}
	dateHTML+=">AM</option>";

	dateHTML+="<option value='pm' ";
	if(amorpm=="PM"){
		dateHTML+="selected";
	}
	dateHTML+=">PM</option>";

	dateHTML+="</select>";
	
	document.getElementById('cell').innerHTML=dateHTML;
		
}

function changeForm(form_type,form_id,form_extra){
	var htmlCode="";

	if(form_type=="insertion"){
		htmlCode="<table>";
		htmlCode+="<tr>";
		htmlCode+="<th colspan=2>Add Insertion</th>";
		htmlCode+="</tr>";

		htmlCode+="<tr>";
		htmlCode+="<td>Insertion Time</td>";
		htmlCode+="<td id='cell' name='cell'>";
		htmlCode+="</td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td>Train Driver</td>";
		if(form_extra=="unimog"){
	//		htmlCode+="<td><input type=text name='unimog_train_driver' />";
			htmlCode+="<td id='ph_trams_tag' name='ph_trams_tag' >";
			
			htmlCode+="</td>";
			

		}
		else if(form_extra=="test"){
//			htmlCode+="<td><input type=text name='unimog_train_driver' />";

			htmlCode+="<td id='ph_trams_tag' name='ph_trams_tag' >";
			
			htmlCode+="</td>";
			

		}
		else if(form_extra=="schooling"){
//			htmlCode+="<td><input type=text name='unimog_train_driver' />";

			htmlCode+="<td id='school_tag' name='school_tag' >";
			
			htmlCode+="</td>";
			

		}


		else if(form_extra=="reserve"){
			htmlCode+="<td><input type=text name='unimog_train_driver' />";
			
			htmlCode+="</td>";

		}

		else {
			htmlCode+="<td id='td' name='td'>";

			htmlCode+="</td>";

			setHTML();	

		}

//		else {
//			htmlCode+="<td id='td' name='td'>";
//			htmlCode+=document.getElementById('td').innerHTML;
//			htmlCode+="</td>";
//			setHTML();	
		
//		}		
		
		
		
		htmlCode+="</tr>";

		htmlCode+="<tr>";
		htmlCode+="<td>Inserted To</td>";	
		htmlCode+="<td>";	
		
		htmlCode+="<select name='inserted_to' id='inserted_to'>";
		htmlCode+="<option value='north'>North Ave.</option>";
		htmlCode+="<option value='quezon'>Quezon Ave.</option>";
		
		
		
		
		htmlCode+="</select>";
		htmlCode+="</td>";	

		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td colspan=2 class='submit' align=center>";
		htmlCode+="<input type=hidden name='insertion_id' id='insertion_id' value='"+form_id+"' />";
		htmlCode+="<input type=submit value='Submit' />";
		htmlCode+="</td>";
		htmlCode+="</tr>";
		htmlCode+="</table>";
	
	}
	else if(form_type=="removal"){
		htmlCode="<table>";
		htmlCode+="<tr>";
		htmlCode+="<th colspan=2>Add Removal</th>";
		htmlCode+="</tr>";

		htmlCode+="<tr>";
		htmlCode+="<td>Removal Time</td>";
		htmlCode+="<td id='cell' name='cell'>";
	//	htmlCode+=document.getElementById('cell').innerHTML;
		htmlCode+="</td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td>Train Driver</td>";
		
		if(form_extra=="unimog"){
//			htmlCode+="<td><input type=text name='unimog_train_driver' />";
			htmlCode+="<td id='ph_trams_tag' name='ph_trams_tag' >";
			
			htmlCode+="</td>";

		}
		else if(form_extra=="test"){
//			htmlCode+="<td ><input type=text name='unimog_train_driver' />";
			htmlCode+="<td id='ph_trams_tag' name='ph_trams_tag' >";
			
			htmlCode+="</td>";

		}

		else if(form_extra=="reserve"){
			htmlCode+="<td><input type=text name='unimog_train_driver' />";
			
			htmlCode+="</td>";

		}
		else if(form_extra=="schooling"){
//			htmlCode+="<td><input type=text name='unimog_train_driver' />";

			htmlCode+="<td id='school_tag' name='school_tag' >";
			
			htmlCode+="</td>";
			

		}
		
		
		
		else {
			htmlCode+="<td id='td' name='td'>";
			htmlCode+="</td>";
		
		}


		htmlCode+="</tr>";
		if(form_extra=="test"){
			/*
			htmlCode+="<tr>";
			htmlCode+="<td>MSD</td>";
			htmlCode+="<td><input type=text name='test_msd' /></td>";
			
			htmlCode+="</tr>";
			htmlCode+="<tr>";
			htmlCode+="<td>SSU</td>";
			htmlCode+="<td><input type=text name='test_ssu' /></td>";
			
			
			htmlCode+="</tr>";
			htmlCode+="<tr>";
			htmlCode+="<td>PH Trams</td>";
			htmlCode+="<td><input type=text name='test_maintenance' /></td>";

			
			
			htmlCode+="</tr>";
			*/

			htmlCode+="<tr>";
			
			htmlCode+="<td>Remarks/Cause of <br>Failure/Removal</td>";
			htmlCode+="<td><span name='remarks_space' id='remarks_space'>";
			htmlCode+="<textarea name='remarks' cols=50></textarea>";
			htmlCode+="</span><input type=checkbox name='remarks_check' id='remarks_check' onclick='setPreset(this)' />Preset Values</td>";	
			
			htmlCode+="</tr>";			
			
			
			
		}
		else {
		
			htmlCode+="<tr>";
			
			htmlCode+="<td>Remarks/Cause of <br>Failure/Removal</td>";
			htmlCode+="<td><span name='remarks_space' id='remarks_space'>";
			htmlCode+="<textarea name='remarks' cols=50></textarea>";
			htmlCode+="</span><input type=checkbox name='remarks_check' id='remarks_check' onclick='setPreset(this)' />Preset Values</td>";	
			
			htmlCode+="</tr>";
		}	
		htmlCode+="<tr>";
		htmlCode+="<td>Removed From</td>";	
		htmlCode+="<td>";	
		
		htmlCode+="<select name='removed_from' id='removed_from'>";
		htmlCode+="<option value='north'>North Ave.</option>";
		htmlCode+="<option value='quezon'>Quezon Ave.</option>";
		
		
		
		
		htmlCode+="</select>";
		htmlCode+="</td>";	

		htmlCode+="</tr>";

		htmlCode+="<tr>";
		htmlCode+="<td>";
		htmlCode+="Add Incident?";
		htmlCode+="</td>";	

		htmlCode+="<td>";
		htmlCode+="<input type='checkbox' name='cancel_loop' id='cancel_loop' />";
		htmlCode+="Open Incident Report</td>";	
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td colspan=2 class='submit' align=center>";
		htmlCode+="<input type=hidden name='remove_id' id='remove_id' value='"+form_id+"' />";
		htmlCode+="<input type=submit value='Submit' />";
		htmlCode+="</td>";
		htmlCode+="</tr>";
		htmlCode+="</table>";
	}
else if(form_type=="index_switch"){
		htmlCode="<table>";
		htmlCode+="<tr>";
		htmlCode+="<th colspan=2>Switch Index No.</th>";
		htmlCode+="</tr>";

		htmlCode+="<tr>";
		htmlCode+="<td>New Index No.</td>";
		htmlCode+="<td><input type=text id='new_index_input' /></td>";
		htmlCode+="</tr>";
		
		htmlCode+="<tr>";
		htmlCode+="<td>Time of Switch</td>";
		htmlCode+="<td id='cell' name='cell'></td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td colspan=2 align=center>";
		htmlCode+="<button type='button' onclick='submitSwitch("+form_id+")'>Submit</button>";
		htmlCode+="</td>";
		htmlCode+="</tr>";
		htmlCode+="</table>";
	
	}
	else if(form_type=="editIndex"){
		htmlCode="<table>";
		htmlCode+="<tr>";
		htmlCode+="<th colspan=2>Edit Index No.</th>";
		htmlCode+="</tr>";

		htmlCode+="<tr>";
		htmlCode+="<td>New Index No.</td>";
		htmlCode+="<td><input type=text name='edit_index' /></td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td colspan=2>";
		htmlCode+="<input type='submit' class='submit' value='Submit' />";
		htmlCode+="</td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<input type=hidden name='edit_id' id='edit_id' value='"+form_id+"' />";
		htmlCode+="</tr>";
		htmlCode+="</table>";	
	
	}
	else if(form_type=="editCar"){
		htmlCode="<table>";
		htmlCode+="<tr>";
		htmlCode+="<th colspan=2>Edit Car</th>";
		htmlCode+="</tr>";

		htmlCode+="<tr>";
		htmlCode+="<td>Car 1</td>";
		htmlCode+="<td><input type=text name='car_1' id='car_1' autocomplete='off' onblur='fillCar(\"first\",this.value)'  /></td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td>Car 2</td>";
		htmlCode+="<td><input type=text name='car_2' id='car_2' autocomplete='off'  onblur='fillCar(\"mid\",this.value)'  /></td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td>Car 3</td>";
		htmlCode+="<td><input type=text name='car_3' id='car_3' autocomplete='off'  onblur='fillCar(\"last\",this.value)'  /></td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td colspan=2>";
		htmlCode+="<input type='submit' class='submit' value='Submit' />";
		htmlCode+="</td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		
		htmlCode+="<input type=hidden name='edit_car' id='edit_car' value='"+form_id+"' />";
		htmlCode+="</tr>";
		htmlCode+="</table>";	
	
	}
	
	
	else if(form_type=="add_train"){
		htmlCode="<table>";
		htmlCode+="<tr>";
		htmlCode+="<th colspan=2>Add/Prep Train</th>";
		htmlCode+="</tr>";
		htmlCode+="<tr><td>Type</td>";
		htmlCode+="<td>";
		htmlCode+="<select name='type' id='type' onchange='setTrain(this.value)'>";
		htmlCode+="<option value='revenue'>Revenue Train</option>";
		htmlCode+="<option value='reserve'>Reserve Train</option>";
		htmlCode+="<option value='schooling'>Schooling Train</option>";
		htmlCode+="<option value='finance'>Finance Train</option>";
		htmlCode+="<option value='test'>Test Train</option>";
		htmlCode+="</select>";
		htmlCode+="</td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td>Index No.</td>";
		htmlCode+="<td  id='index_tag' name='index_tag'><input type=text name='index_no'  autocomplete='off'  /></td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td>Car 1</td>";
		htmlCode+="<td><input type=text name='car_1' id='car_1' autocomplete='off' onblur='fillCar(\"first\",this.value)'  /></td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td>Car 2</td>";
		htmlCode+="<td><input type=text name='car_2' id='car_2' autocomplete='off'  onblur='fillCar(\"mid\",this.value)'  /></td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td>Car 3</td>";
		htmlCode+="<td><input type=text name='car_3' id='car_3' autocomplete='off'  onblur='fillCar(\"last\",this.value)'  /></td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";


		htmlCode+="<tr>";
		htmlCode+="<td>Car 4</td>";
		htmlCode+="<td><input type=text name='car_4' id='car_4' autocomplete='off'  onblur='fillCar(\"last2\",this.value)'  /></td>";
		htmlCode+="</tr>";



		htmlCode+="<td>LPAM No.</td>";
		htmlCode+="<td><input type=text name='lpam_id'  autocomplete='off'  /></td>";		
		htmlCode+="</tr>";


		htmlCode+="<tr>";
		htmlCode+="<th colspan=2>";
		htmlCode+="<input type=checkbox name='cancel_departure' id='cancel_departure' />Cancelled";
		htmlCode+="</th>";
		htmlCode+="</tr>";


		
		htmlCode+="<tr>";
		
		htmlCode+="<td>I336 Time</td>";
		htmlCode+="<td id='cell' name='cell'>";
		htmlCode+="</td>";
		htmlCode+="</tr>";

		htmlCode+="<tr><td align=center class='submit' colspan=2>";

		htmlCode+="<input type='submit' value='Add' />";
		htmlCode+="</td>";
		htmlCode+="</table>";
	}
	else if(form_type=="unimog"){
		htmlCode="<table>";
		htmlCode+="<tr>";
		htmlCode+="<th colspan=2>Add/Prep Unimog Train</th>";
		htmlCode+="</tr>";
		htmlCode+="<tr><td>Type of Train</td>";
		htmlCode+="<td>";	
		htmlCode+="<select name='train_type'>";
		htmlCode+="<option value='unimog'>UNIMOG</option>";	
		htmlCode+="</select>";

		htmlCode+="</td>";	

		htmlCode+="</tr>";

		htmlCode+="<tr>";
		htmlCode+="<td>Index No.</td>";
		htmlCode+="<td>";
		htmlCode+="<select name='other_index_no'>";
		for(var n=80;n<=89;n++){

			htmlCode+="<option value='"+n+"'>"+n+"</option>";
		}
		htmlCode+="</select>";
		
		htmlCode+="</td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<th colspan=2>";
		htmlCode+="<input type=checkbox name='cancel_departure' id='cancel_departure' />Cancelled";
		htmlCode+="</th>";
		htmlCode+="</tr>";
		
		htmlCode+="<tr>";
		
		htmlCode+="<td>I336 Time</td>";
		htmlCode+="<td id='cell' name='cell'>";
		htmlCode+="</td>";
		htmlCode+="</tr>";

		htmlCode+="<tr><td align=center colspan=2>";

		htmlCode+="<input type='submit' class='submit' value='Add' />";
		htmlCode+="</td>";
		htmlCode+="</table>";
	}
	else if(form_type=="remarks"){
		htmlCode="<table>";
		htmlCode+="<tr>";
		htmlCode+="<th colspan=2>Add/Edit Remarks</th>";
		htmlCode+="</tr>";
/*
		if(form_extra=="test"){
		htmlCode+="<tr>";
		htmlCode+="<td>MSD</td>";
		htmlCode+="<td><input type=text name='test_msd' /></td>";
		
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td>SSU</td>";
		htmlCode+="<td><input type=text name='test_ssu' /></td>";
		
		
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td>PH Trams</td>";
		htmlCode+="<td><input type=text name='test_maintenance' /></td>";
		
		
		htmlCode+="</tr>";
		}
		else {
*/		
		htmlCode+="<tr>";
		
		htmlCode+="<td>Remarks/Cause of <br>Failure/Removal</td>";
		htmlCode+="<td><span name='remarks_space' id='remarks_space'>";
		htmlCode+="<textarea name='remarks' cols=50>";
		htmlCode+=form_extra;
		htmlCode+="</textarea>";
		htmlCode+="</span><input type=checkbox name='remarks_check' id='remarks_check' onclick='setPreset(this)' />Preset Values</td>";	
		
		htmlCode+="</tr>";
//		}	

		htmlCode+="<tr>";
		htmlCode+="<td colspan=2 class='submit' align=center>";
		htmlCode+="<input type=hidden name='remarks_id' id='remarks_id' value='"+form_id+"' />";
		htmlCode+="<input type=submit value='Submit' />";
		htmlCode+="</td>";
		htmlCode+="</tr>";
		htmlCode+="</table>";		
	
	}
	
	
	document.getElementById('add_form').innerHTML=htmlCode;
	$('#addModal').modal('show');

	setDate();
	
	if((form_type=="removal")||(form_type=="insertion")){
		if((form_extra=="test")||(form_extra=="unimog")){
			setPH();
		}
		else if(form_extra=="schooling"){
			setSchool();	
		}
		else {
			setHTML();	
		}
	}
		

}
function setPreset(check){
	var remarksHTML="";
	
	if(check.checked){
		remarksHTML="<select name='remarks' id='remarks'>";
		remarksHTML+="<option>AM Off-Peak Removal</option>";
		remarksHTML+="<option>PM Off-Peak Removal</option>";
		remarksHTML+="<option>Normal Removal</option>";
		remarksHTML+="<option>Emergency Removal</option>";
		remarksHTML+="<option>Give Way for Test Train</option>";
		remarksHTML+="<option>Give Way for Schooling Train</option>";

		remarksHTML+="</select>";
	
	}
	else {
		remarksHTML="<textarea name='remarks' cols=50></textarea>";
	
	}

	document.getElementById('remarks_space').innerHTML=remarksHTML;
}


function cancelTrain(train_id){
	var check=confirm("Cancel Train?");
	if(check){
		window.open("incident report.php?cancel="+train_id);
		
	}

}

function setTrain(train){
	var trainHTML="";
	switch(train){
		case "revenue":
			trainHTML="<input type=text name='index_no' />";
		break;
		
		case "reserve":
			trainHTML="<select name='index_no' id='index_no'>";
			for(var i=50;i<=69;i++){
				trainHTML+="<option value='"+i+"'>"+i+"</option>";
			
			}
			trainHTML+="</select>";
		break;

//		case "schooling":
		case "test":
			trainHTML="<select name='index_no' id='index_no'>";
			for(var i=70;i<=79;i++){
				trainHTML+="<option value='"+i+"'>"+i+"</option>";
			
			}
			trainHTML+="</select>";
		break;
		
		
		case "schooling":
			trainHTML="<input type=text name='index_no' />";
		break;
		
		case "finance":
			trainHTML="<input type=text name='index_no' value='90' />";
		break;
	
	
	}
	
	
	document.getElementById('index_tag').innerHTML=trainHTML;

}

function deleteSwitch(index){
	var check=confirm("Cancel Switch?");
	if(check){
		var $cell = $('td.switch-cell[data-switch-id="'+index+'"]');
		var train_id = $cell.closest('tr[data-train-id]').data('train-id');
		$.get('processing.php', { deleteSwitch: index }, function(data){
			$cell.replaceWith('<td rowspan="4" class="switch-placeholder" data-train-id="'+train_id+'"><a href="#add_form" class="<?php echo $SRemove; ?>" onclick="changeForm(\'index_switch\',\''+train_id+'\',\'\')">Switch Index No.</a></td>');
		});
	}
}
function submitSwitch(train_ava_id){
    var new_index = document.getElementById('new_index_input').value;
    if(new_index == ''){
        alert('Please enter a new index number.');
        return;
    }

    var month  = document.getElementById('month').value;
    var day    = document.getElementById('day').value;
    var year   = document.getElementById('year').value;
    var hour   = parseInt($('select[name="hour"]').val());
    var minute = parseInt($('select[name="minute"]').val());
    var amorpm = $('select[name="amorpm"]').val();

    if(amorpm == 'pm' && hour < 12) hour += 12;
    if(amorpm == 'am' && hour == 12) hour = 0;

    var hh = (hour < 10)   ? '0'+hour   : ''+hour;
    var mm = (minute < 10) ? '0'+minute : ''+minute;

    var switch_time = year+'-'+month+'-'+day+' '+hh+':'+mm;

$.get('processing.php', {
        ajaxSwitch:   1,
        train_ava_id: train_ava_id,
        new_index:    new_index,
        switch_time:  switch_time
    }, function(data){
        var res = JSON.parse(data);
        if(res.status == 'ok'){
            $('#addModal').modal('hide');

            // format time for display e.g. "14:05"
            var hh = (hour < 10)   ? '0'+hour   : ''+hour;
            var mm = (minute < 10) ? '0'+minute : ''+minute;
            var display_time = hh+':'+mm;

            // build the new switch cell html
            var newCell = '<td align="center" rowspan="4" class="switch-cell" data-switch-id="'+res.switch_id+'">'
                        + new_index + ' /<br> ' + display_time
                        + ' <a href="#" class="LDel" onclick=\'deleteSwitch("'+res.switch_id+'")\'>X</a>'
                        + '</td>';

// find the first placeholder cell for this train and replace it
            $('td.switch-placeholder[data-train-id="'+train_ava_id+'"]').first().replaceWith(newCell);
        }
    });
}
function deleteRow(index){
	var check=confirm("Remove Record?");
	if(check){
	makeajax("processing.php?removeRow="+index,"reloadPage");	
	}
}

function reloadPage(ajaxHTML){
	self.location="train_availability.php";


}
function fillCar(position,car){
	var field="car_1";
	var car_a=document.getElementById('car_1').value*1;
	var car_b=document.getElementById('car_2').value*1;
	var car_c=document.getElementById('car_3').value*1;
	var car_d=document.getElementById('car_4').value*1;

	
	var counter=0;

	if(position=="first"){
		field="car_1";
		if(car==""){
		}
		else {

		if(car==car_b){
		   counter++;
		}
		if(car==car_c){
		   counter++;
		}
		if(car==car_d){
		   counter++;
		}

		}


	}
	else if(position=="mid"){
		field="car_2";
		if(car==""){
		}
		else {
		if(car==car_a){
		   counter++;
		}
		if(car==car_c){
		   counter++;
		}
		if(car==car_d){
		   counter++;
		}

		}
		
	}
	else if(position=="last"){
		field="car_3";
		if(car==""){
		}
		else {

		if(car==car_a){
		   counter++;
		}
		if(car==car_b){
		   counter++;
		}
		if(car==car_d){
		   counter++;
		}

		}
	}




	else if(position=="last2"){
		field="car_4";
		if(car==""){
		}
		else {

		if(car==car_a){
		   counter++;
		}
		if(car==car_b){
		   counter++;
		}
		if(car==car_c){
		   counter++;
		}

		}
	}

	
	if(counter>0){
		alert("Car already in Compo of Train!");
		document.getElementById(field).value="";

	}
	else {
//		alert("processing.php?checkCar="+car+"&car="+field);
		makeajax("processing.php?checkCar="+car+"&car="+field,"confirmCar");	
	
	
	
	}
	$('#addModal').modal('show');


	
}

function confirmCar(ajaxHTML){
	

	if(ajaxHTML=="No car"){
	}
	else {

		alert("Car already in Compo of another Train!");
		document.getElementById(ajaxHTML).value="";
	}
	

}

$(function() {
    $( "#search_date" ).datepicker({
      changeMonth: true,
      changeYear: true,
      showAnim: "clip"
    });    
});

</script>

<body>
<div style="clear:both;height:0;font-size:0;line-height:0"></div>

<?php
/* -------------------------------------------------------------------------
   Date-resolution logic ? UNCHANGED from original
   ------------------------------------------------------------------------- */
$mm=date("m");
$yy=date("Y");
$dd=date("d");
$hh=date("h");
$min=date("i");
$aa=date("a");
$availability_date=date("F d, Y");
$availability_date_code=date("Y-m-d");

if(isset($_POST['search_date'])){
	$_SESSION['search_date']=$_POST['search_date'];
	$availability_date=date("F d, Y",strtotime($_POST['search_date']));
	$availability_date_code=date("Y-m-d",strtotime($_POST['search_date']));
}
if(isset($_SESSION['search_date'])){
	$availability_date=date("F d, Y",strtotime($_SESSION['search_date']));
	$availability_date_code=date("Y-m-d",strtotime($_SESSION['search_date']));
}
?>

<script type="text/javascript">
$(document).ready(function(){
    $(".alink a").each(function(){
        if($(this).hasClass("disabled")){
            $(this).removeAttr("href");
        }
    });
});
</script>

<?php
/* Permission flags ? UNCHANGED */
if ($ULev>=2){
	$SRemove = "Llink"; 
	$SRemove2 = "two pull-left";
	$SRemove3 = "liR grow";
	$SRemove4 = "LEdit";
	$SRemove5 = "LDel";
} else {
	$SRemove = "disabled";
	$SRemove2 = "disabled";
	$SRemove3 = "disabled";
	$SRemove4 = "disabled";
	$SRemove5 = "disabled";
}
?>

<!-- TOOLBAR: inline style= beats all stylesheet cascade rules -->
<table cellspacing="0" cellpadding="0" style="width:100%;background:#00529B;border-bottom:3px solid #FDB813;border-collapse:collapse;position:relative;z-index:1">
<tr>
    <td style="padding:8px 14px;vertical-align:middle;white-space:nowrap;width:1%;border:none">
        <span style="font-size:15px;font-weight:700;color:#FFFFFF"><?php
if(isset($_POST['search_date'])){
    $availability_date=date("F d, Y",strtotime($_POST['search_date']));
    $_SESSION['month']=date("m",strtotime($availability_date));
    $_SESSION['day']=date("d",strtotime($availability_date));
    $_SESSION['year']=date("Y",strtotime($availability_date));
    $availability_date_code=date("Y-m-d",strtotime($_POST['search_date']));
    echo $availability_date;
} else if(isset($_SESSION['search_date'])){
    $availability_date=date("F d, Y",strtotime($_SESSION['search_date']));
    $availability_date_code=date("Y-m-d",strtotime($_SESSION['search_date']));
    $_SESSION['month']=date("m",strtotime($availability_date));
    $_SESSION['day']=date("d",strtotime($availability_date));
    $_SESSION['year']=date("Y",strtotime($availability_date));
    echo $availability_date;
} else {
    $year=date("Y"); $month=date("m"); $day=date("d");
    $availability_date=date("F d, Y",strtotime($year."-".$month."-".$day));
    $availability_date_code=date("Y-m-d",strtotime($year."-".$month."-".$day));
    echo $availability_date;
}
        ?></span><span style="font-size:11px;color:rgba(255,255,255,.6);margin-left:8px"><?php echo date("l",strtotime($availability_date_code)); ?></span>
    </td>
    <td style="padding:8px 14px;vertical-align:middle;text-align:center;border:none">
        <form action='<?php echo $PHP_SELF; ?>' method='post' style="margin:0;padding:0;display:inline">
            <input type="text" name='search_date' id='search_date'
                style="height:26px;font-size:12px;font-weight:normal;background:#fff;color:#1A2238;border:1px solid rgba(255,255,255,.5);border-radius:4px;padding:0 7px;width:120px;vertical-align:middle">
            <input type="submit" value="Go"
                style="height:26px;font-size:11px;font-weight:700;background:#FDB813;color:#3A2D00;border:none;border-radius:4px;padding:0 12px;cursor:pointer;vertical-align:middle;margin-left:4px">
        </form>
    </td>
    <td style="padding:8px 14px;vertical-align:middle;text-align:right;white-space:nowrap;border:none">
        <?php 
		$SRemove="enabled";
		if($SRemove!="disabled"){ ?>
        <a href='#' onclick='changeForm("add_train","","")' style="display:inline-block;font-size:11px;font-weight:500;color:#fff;text-decoration:none;padding:4px 10px;border:1px solid rgba(255,255,255,.35);border-radius:3px;margin-left:6px;float:none;width:auto">+ Add Train</a>
        <a href='#' onclick='changeForm("unimog","","")' style="display:inline-block;font-size:11px;font-weight:500;color:#fff;text-decoration:none;padding:4px 10px;border:1px solid rgba(255,255,255,.35);border-radius:3px;margin-left:6px;float:none;width:auto">UNIMOG</a>
        <?php } else { ?>
        <span style="font-size:11px;color:rgba(255,255,255,.3);margin-left:6px">+ Add Train</span>
        <span style="font-size:11px;color:rgba(255,255,255,.3);margin-left:6px">UNIMOG</span>
        <?php } ?>
        <a href='#' onclick='window.open("generate_tar.php?tar=<?php echo $availability_date_code; ?>");' style="display:inline-block;font-size:11px;font-weight:600;color:#3A2D00;text-decoration:none;padding:4px 10px;border:1px solid #FDB813;border-radius:3px;margin-left:6px;background:#FDB813;float:none;width:auto">Generate Printout</a>
        <span style="display:inline-block;font-size:11px;color:rgba(255,255,255,.6);margin-left:14px;padding-left:14px;border-left:1px solid rgba(255,255,255,.2);vertical-align:middle">Timetable:&nbsp;<strong style="color:#fff"><?php
$timeTableSQL="select *,timetable_day.id as timeId from timetable_day inner join timetable_code on timetable_day.timetable_code=timetable_code.id where train_date='".$availability_date_code."'";
$timeTableRS=$db->query($timeTableSQL);
$timeTableNM=$timeTableRS->num_rows;
if($timeTableNM>0){
    $timeTableRow=$timeTableRS->fetch_assoc();
    echo $timeTableRow['code'];
?></strong>&nbsp;<a href='#' onclick='window.open("timetable_set.php?reset=<?php echo $timeTableRow['timeId']; ?>","code","height=300,width=350")' style="color:rgba(255,255,255,.5);font-size:10px;text-decoration:none;float:none;width:auto;border:none;padding:0;display:inline;background:transparent">Set/Reset</a><?php
} else { echo "______"; ?></strong>&nbsp;<a href='#' onclick='window.open("timetable_set.php?set=1","code","height=300,width=350")' style="color:rgba(255,255,255,.5);font-size:10px;text-decoration:none;float:none;width:auto;border:none;padding:0;display:inline;background:transparent">Set/Reset</a><?php } ?>
        </span>
    </td>
</tr>
</table>
<!-- hover script moved to bottom -->
<!-- end toolbar -->

<div class='alink'>
<table class='train_ava'>
<tr class='rowHeading'>
<th rowspan=2>
Index No.
</th>
<th colspan=7>
Switch
</th>
<th rowspan=2>Train Compo</th>

<th rowspan=2>Time on I336</th>

<th rowspan=2>Inserted</th>
<th rowspan=2>Removed</th>
<th rowspan=2>Remarks/Cause of Failure/Removal</th>
<th colspan=3>Removal</th>

</tr>
<tr class='rowHeading'>
	
	<?php 
		for($i=0;$i<7;$i++){
	?>	
	<th>Index No.</th>
	<?php	
		}
	?>
	<th>L2</th>
	<th>L3</th>
	<th>L4</th>
</tr>
<?php
	$year=date("Y");
	$month=date("m");
	$day=date("d");
	/*
	if(isset($_POST['day'])){
	$year=$_POST['year'];
	$month=$_POST['month'];
	$day=$_POST['day'];
	}
	
	if(isset($_SESSION['day'])){
	$year=$_SESSION['year'];
	$month=$_SESSION['month'];
	$day=$_SESSION['day'];
	
	
	}
	*/
	
//	$availability_date=date("Y-m-d",strtotime($year."-".$month."-".$day));

	$availability_date=$availability_date_code;
	
	
	
$sql="
		SELECT 
			ta.*,
			tat.boundary_time, tat.insert_time, tat.insert_driver, tat.inserted_to,
			tat.remove_time, tat.remove_driver, tat.removed_from, tat.removal_remarks
		FROM train_availability ta
		LEFT JOIN train_ava_time tat ON tat.train_ava_id = ta.id
		WHERE ta.date BETWEEN '".$availability_date." 00:00:00' AND '".$availability_date." 23:59:59'
		ORDER BY ta.date
	";
	$rs=$db->query($sql);
	$nm=$rs->num_rows;

	
	for($i=0;$i<$nm;$i++){
		$row=$rs->fetch_assoc();
		$train_index=$row['index_no'];

		$row2 = $row;
			$SRemove4="enabled";
		?>
	<tr 
		data-train-id="<?php echo $row['id']; ?>"
		<?php 
		if(($row2['remove_time']=="")||($row2['remove_time']=="0000-00-00 00:00:00")){
			if($row['status']=="active"){
			if($i%2>0){ echo "class='rowClass'"; } 
			}
			else {
echo "style='background-color:#67c2ef'";
			}
		}
		else {
 echo "style='background-color:#67c2ef'";

		}
		?>
		>
		<td align=center  class='editindex' rowspan=4><?php echo $row['index_no']; ?> <a href='#' class="ta-act <?php echo $SRemove4; ?>" onclick='changeForm("editIndex","<?php echo $row['id']; ?>","")'>Edit</a></td>
<?php
	$sql3="select * from train_switch where train_ava_id='".$row['id']."' order by date_change";
	
	$rs3=$db->query($sql3);
	$nm3=$rs3->num_rows;

	if($nm3>7){
		$nm3=7;
	}
	for($n=0;$n<$nm3;$n++){
		$row3=$rs3->fetch_assoc();
?>




<td align=center rowspan=4 class="switch-cell" data-switch-id="<?php echo $row3['id']; ?>">
				<div class="switch-index"><?php echo $row3['new_index']; ?></div>
				<div class="switch-time"><?php echo date("H:i",strtotime($row3['date_change']));; ?></div>
				<div class="switch-driver">
	<?php if($row3['train_driver']==""){ } else { echo getTrainDriver($row3['train_driver']);  } ?> 
				<a href='#' class="<?php echo $SRemove5; ?>"  onclick='deleteSwitch("<?php echo $row3['id']; ?>")'>X</a>
</div>








 </td>
<?php
	
	
	}
	
	$blank=7-($nm3*1);
	
	$SRemove4="enabled";
	for($n=0;$n<$blank;$n++){	
		if($n==0){
			?>	
<td rowspan=4 class="switch-placeholder" data-train-id="<?php echo $row['id']; ?>">


<a href='#add_form' class="ta-act <?php echo $SRemove4; ?>"  onclick='changeForm("index_switch","<?php echo $row['id']; ?>","")'>Switch Index No.</a></td>	


		<?php
		}
		else {
?>
		<td rowspan=4>&nbsp;</td>
<?php
		}
}
?>	
		<td rowspan=4 class="tc-compo-cell" style="padding:6px 8px;vertical-align:middle;text-align:center">
			<div class="tc-compo">
			<a href='car_history.php?car_id=<?php echo $row['car_a']; ?>' target='_blank' class=\"tc-car <?php echo $SRemove; ?>\"><?php echo $row['car_a']; ?></a>
				<a href='car_history.php?car_id=<?php echo $row['car_b']; ?>' target='_blank' class=\"tc-car <?php echo $SRemove; ?>\"><?php echo $row['car_b']; ?></a>
				<?php if($row['car_c']){ ?><a href='car_history.php?car_id=<?php echo $row['car_c']; ?>' target='_blank' class=\"tc-car <?php echo $SRemove; ?>\"><?php echo $row['car_c']; ?></a><?php } ?>
				<?php if($row['car_d']){ ?><a href='car_history.php?car_id=<?php echo $row['car_d']; ?>' target='_blank' class=\"tc-car <?php echo $SRemove; ?>\"><?php echo $row['car_d']; ?></a><?php } ?>
				
				<?php $SRemove4="enabled"; ?>
				<div class="tc-edit-wrap"><a href='#add_form' class="tc-edit-btn <?php echo $SRemove4; ?>" onclick='changeForm("editCar","<?php echo $row['id']; ?>","")'>&#9998; Edit Compo</a></div>
			</div>
		</td>
	<?php	
		

		
		if($row2['boundary_time']==""){
			$boundary_time="";
		}
		else {
			$boundary_time=date("H:i",strtotime($row2['boundary_time']));
		}
		if($row['status']=="active"){
		if($row2['insert_time']==""){
			$insert_time="";
			$insert_driver="";
			$inserted_to="";
		}
		else {
			if($row2['insert_time']=="0000-00-00 00:00:00"){
				$insert_date="";
				$insert_time="";
			}
			else {
				$insert_time=date("H:i",strtotime($row2['insert_time']));
				$insert_date=date("Y-m-d",strtotime($row2['insert_time']));
				if(strtotime($availability_date)>strtotime($insert_date)){

					$insert_time=$insert_date."<br> ".$insert_time;
				}

			}
			$inserted_to=$row2['inserted_to'];
			
			if($row['type']=="unimog"){
				$insert_driver=getPHTrainDriver($row2['insert_driver'],$db)."<br>MAINTENANCE PROVIDER";
			}

			else if($row['type']=="test"){
				$insert_driver=getPHTrainDriver($row2['insert_driver'],$db)."<br>MAINTENANCE PROVIDER";
			}
			else if($row['type']=="reserve"){
				$insert_driver=$row2['insert_driver'];
			}

			else {
				$insert_driver=getTrainDriver($row2['insert_driver'],$db);
			
			
			}
			if($inserted_to=="quezon"){ $inserted_to="Quezon Ave.<br/>"; }			
			else { $inserted_to=""; }
			
		}

		$remove_remarks=$row2['removal_remarks'];
		if($row2['remove_time']==""){
			$remove_time="";
			$remove_driver="";
			$removed_from="";
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
					$remove_time=$remove_date."<br> ".$remove_time;

				}

			}
			$removed_from=$row2['removed_from'];

			if($row['type']=="unimog"){
				$remove_driver=getPHTrainDriver($row2['remove_driver'],$db)."<br>MAINTENANCE PROVIDER";
			}

			else if($row['type']=="test"){
				$remove_driver=getPHTrainDriver($row2['remove_driver'],$db)."<br>MAINTENANCE PROVIDER";
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
		}
		if($row['status']=="active"){

			
			
			//$cancelSQL="select * from train_incident_view  inner join level on train_incident_view.incident_id=level.incident_id where train_ava_id='".$row['id']."'";
			$cancelSQL="select * from train_incident_view where train_ava_id='".$row['id']."'";		
			$cancelRS=$db->query($cancelSQL);
			$incidentClause="";	
			$level2Clause="";	
			
			$level3Clause="";
			$level4Clause="";
			
			
			$l2Count=0;
			$l3Count=0;
			$l4Count=0;
			
			
			$cancelNM=$cancelRS->num_rows;
			if($cancelNM>0){
				$incidentClause="<br>See ";

				for($m=0;$m<$cancelNM;$m++){
					$cancelRow=$cancelRS->fetch_assoc();	
					$level=$cancelRow['level'];
					$order=getLevel($cancelRow['incident_id'],$db);
					
					if($m==0){
						$incidentClause.="<a href='#' class='$SRemove'  onclick='window.open(\"edit_ccdr.php?ir=".$cancelRow['incident_id']."\")'>IN ".$cancelRow['incident_no']."</a>";
					
					
					
					}
					else {
						$incidentClause.=",<br>";
						$incidentClause.="<a href='#' class='$SRemove'  onclick='window.open(\"edit_ccdr.php?ir=".$cancelRow['incident_id']."\")'>IN ".$cancelRow['incident_no']."</a>";
						
					}
					
					
					if($level==2){
						if($l2Count==0){
							$level2Clause.=getOrdinal($order);
						}
						else {
							$level2Clause.=",<br>";
							$level2Clause.=getOrdinal($order);
						
						}
						$l2Count++;
//						$level3Clause="&nbsp;";
//						$level4Clause="&nbsp;";
						
					}
					
					else if($level==3){
						if($l3Count==0){
							$level3Clause.=getOrdinal($order);
						}
						else {
							$level3Clause.=",<br>";
							$level3Clause.=getOrdinal($order);
						
						}
						$l3Count++;
//						$level2Clause="&nbsp;";
//						$level4Clause="&nbsp;";
						
					}
					else if($level==4){

						if($l4Count==0){
							$level4Clause.=getOrdinal($order);
						}
						else {
							$level4Clause.=",<br>";
							$level4Clause.=getOrdinal($order);
						
						}
						$l4Count++;
//						$level3Clause="&nbsp;";
//						$level2Clause="&nbsp;";						
						
						
					}
					else {
//						$level3Clause="&nbsp;";
//						$level4Clause="&nbsp;";
//						$level2Clause="&nbsp;";						
					}
				
				}
				
				if($level2Clause==""){
					$level2Clause="&nbsp;";						
				}
				if($level3Clause==""){

					$level3Clause="&nbsp;";
				}
				if($level4Clause==""){
					$level4Clause="&nbsp;";

				}
				
			}
			
			
	?>
	
	

		
		
		
		<td rowspan=4><?php echo $boundary_time; ?></td>
		<?php $SRemove4="enabled"; ?>
		<td rowspan=4 class="ta-slot-cell">
			<div class="ta-slot">
				<div class="ta-slot-time"><?php echo ($inserted_to.$insert_time !== "") ? $inserted_to.$insert_time : "&ndash;"; ?></div>
				<div class="ta-slot-driver"><?php echo str_replace("SUP","STDO",$insert_driver); ?></div>
				<div class="ta-slot-actions">
					<a href='#add_form' class="ta-act <?php echo $SRemove4; ?>" onclick='changeForm("insertion","<?php echo $row['id']; ?>","<?php if($row['type']=="unimog"){ echo "unimog"; } else if($row['type']=="test"){ echo "test"; } else if($row['type']=="reserve"){ echo "reserve"; } else if($row['type']=="schooling"){ echo "schooling"; } ?>")'>Edit</a>
					<span class="ta-act-sep">&middot;</span>
					<a href='#' class="ta-act ta-act-cancel <?php echo $SRemove; ?>" onclick='cancelTrain("<?php echo $row['id']; ?>")'>Cancel</a>
				</div>
			</div>
		</td>
		<!--<a href='#' onclick='changeForm("index_switch","")'>Switch Index No.</a>-->
		<td rowspan=4 class="ta-slot-cell">
			<div class="ta-slot">
				<div class="ta-slot-time"><?php echo ($removed_from.$remove_time !== "") ? $removed_from.$remove_time : "&ndash;"; ?></div>
				<div class="ta-slot-driver"><?php echo str_replace("SUP","STDO",$remove_driver); ?></div>
				<div class="ta-slot-actions">
					<a href='#add_form' class="ta-act <?php echo $SRemove4; ?>" onclick='changeForm("removal","<?php echo $row['id']; ?>","<?php if($row['type']=="unimog"){ echo "unimog"; } else if($row['type']=="test"){ echo "test"; } else if($row['type']=="reserve"){ echo "reserve"; } else if($row['type']=="schooling"){ echo "schooling"; } ?>")'>Edit</a>
				</div>
			</div>
		</td>
		<td rowspan=4><?php echo $remove_remarks; ?>
		<?php echo $incidentClause; ?>
		<br><a href='#add_form'  class="<?php echo $SRemove; ?>" onclick='changeForm("remarks","<?php echo $row['id']; ?>","<?php  echo $remove_remarks;   ?>")'>Add/Edit Remarks </a>
		<br><a href='#add_form'  class="<?php echo $SRemove; ?>" onclick='window.open("incident report.php?add_incident=<?php echo $row['id']; ?>")'>Add Incident </a>
		
		
		</td>
		<td rowspan=4><?php echo $level2Clause; ?></td>
		<td rowspan=4><?php echo $level3Clause; ?></td>
		<td rowspan=4><?php echo $level4Clause; ?></td>

	<?php	

	}
			else if($row['status']=="cancelled"){
		
			
			$cancelSQL="select * from train_incident_view  inner join level on train_incident_view.incident_id=level.incident_id where train_ava_id='".$row['id']."'";
			$cancelRS=$db->query($cancelSQL);
		
			$incidentClause="";	
			$level2Clause="";	
			
			$level3Clause="";
			$level4Clause="";
			
			
			$l2Count=0;
			$l3Count=0;
			$l4Count=0;

			
			$cancelNM=$cancelRS->num_rows;

			for($m=0;$m<$cancelNM;$m++){
					$cancelRow=$cancelRS->fetch_assoc();	
					$level=$cancelRow['level'];
					$order=getLevel($cancelRow['incident_id'],$db);
					
					if($m==0){
						$incidentClause.="<a href='#' onclick='window.open(\"edit_ccdr.php?ir=".$cancelRow['incident_id']."\")'>IN ".$cancelRow['incident_no']."</a>";
					
					
					
					}
					else {
						$incidentClause.=",<br>";
						$incidentClause.="<a href='#' onclick='window.open(\"edit_ccdr.php?ir=".$cancelRow['incident_id']."\")'>IN ".$cancelRow['incident_no']."</a>";
						
					}
					
					
					if($level==2){
						if($l2Count==0){
							$level2Clause.=getOrdinal($order);
						}
						else {
							$level2Clause.=",<br>";
							$level2Clause.=getOrdinal($order);
						
						}
						$l2Count++;
//						$level3Clause="&nbsp;";
//						$level4Clause="&nbsp;";
						
					}
					
					else if($level==3){
						if($l3Count==0){
							$level3Clause.=getOrdinal($order);
						}
						else {
							$level3Clause.=",<br>";
							$level3Clause.=getOrdinal($order);
						
						}
						$l3Count++;
//						$level2Clause="&nbsp;";
//						$level4Clause="&nbsp;";
						
					}
					else if($level==4){

						if($l4Count==0){
							$level4Clause.=getOrdinal($order);
						}
						else {
							$level4Clause.=",<br>";
							$level4Clause.=getOrdinal($order);
						
						}
						$l4Count++;
//						$level3Clause="&nbsp;";
//						$level2Clause="&nbsp;";						
						
						
					}
					else {
//						$level3Clause="&nbsp;";
//						$level4Clause="&nbsp;";
//						$level2Clause="&nbsp;";						
					}				
			}
				if($level2Clause==""){
					$level2Clause="&nbsp;";						
				}
				if($level3Clause==""){

					$level3Clause="&nbsp;";
				}
				if($level4Clause==""){
					$level4Clause="&nbsp;";

				}
		
		?>
		
		<?php 
		if($boundary_time==""){
		?>	
		<td rowspan=4 colspan=6 align=center>CANCELLED</td>
		<?php
		}
		else {
		?>	
		<td rowspan=4><?php echo $boundary_time; ?></td>
		<td rowspan=4 colspan=5 align=center>CANCELLED</td>
		<?php
		}
		?>
		<td rowspan=4>
		<?php echo $remove_remarks; ?>
		<?php echo $incidentClause; ?>
		<br><a href='#add_form'  class="<?php echo $SRemove; ?>" onclick='changeForm("remarks","<?php echo $row['id']; ?>","<?php echo $remove_remarks;  ?>")'>Add/Edit Remarks </a>
		<br><a href='#'  class="<?php echo $SRemove; ?>" onclick='window.open("incident report.php?add_incident=<?php echo $row['id']; ?>")'>Add Incident </a></td>
		<td rowspan=4><?php echo $level2Clause; ?></td>
		<td rowspan=4><?php echo $level3Clause; ?></td>
		<td rowspan=4><?php echo $level4Clause; ?></td>

		<?php
		}
	?>
		<td rowspan=4 valign=center align=center><a href='#'  class="<?php echo $SRemove5; ?>" onclick='deleteRow("<?php echo $row['id']; ?>")'>X</a></td>	
	</tr>
	<tr 
		data-train-id="<?php echo $row['id']; ?>"
		<?php 
		if(($row2['remove_time']=="")||($row2['remove_time']=="0000-00-00 00:00:00")){
			if($row['status']=="active"){
			if($i%2>0){ echo "class='rowClass'"; } 
			}
			else {
//			echo "style='background-color:#f5f5f5;';";
			echo "style='background-color:#67c2ef;'";

			}
		
		}
		else {
//			echo "style='background-color:#f5f5f5;';";
			echo "style='background-color:#67c2ef;'";

		}
		
		
		
		
		?>
		
		
		
		>
		<!-- car_b now in rowspan=4 compo cell above -->
	</tr>	
	<tr 
		data-train-id="<?php echo $row['id']; ?>"
	<?php 
		if(($row2['remove_time']=="")||($row2['remove_time']=="0000-00-00 00:00:00")){
			if($row['status']=="active"){
			if($i%2>0){ echo "class='rowClass'"; } 
			}
			else {
//			echo "style='background-color:#f5f5f5;';";
			echo "style='background-color:#67c2ef;'";

			}
		
		}
		else {
//			echo "style='background-color:#f5f5f5;';";
			echo "style='background-color:#67c2ef;'";
		}
		
		
	
	
	?>
	
	
	>
		<!-- car_c now in rowspan=4 compo cell above -->

	</tr>	
	<tr 
		data-train-id="<?php echo $row['id']; ?>"
	<?php 
		if(($row2['remove_time']=="")||($row2['remove_time']=="0000-00-00 00:00:00")){
			if($row['status']=="active"){
			if($i%2>0){ echo "class='rowClass'"; } 
			}
			else {
//			echo "style='background-color:#f5f5f5;';";
			echo "style='background-color:#67c2ef;'";

			}
		
		}
		else {
//			echo "style='background-color:#f5f5f5;';";
			echo "style='background-color:#67c2ef;'";
		}
		
		
	
	
	?>
	
	
	>
		<!-- car_d now in rowspan=4 compo cell above -->

	</tr>		
	
	<?php
	}
	?>


</table>
<div class="alink" style="margin-top:10px; padding:6px 0; border-top:2px solid #00529B; display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
    <a href='#' class="<?php echo $SRemove; ?>" style='text-underline:none;' onclick='changeForm("add_train","","")'>+ Add Train</a>
    <span style="color:#ccc">|</span>
    <a href='#' class="Llink" style='text-underline:none;' onclick='changeForm("unimog","","")'>UNIMOG</a>
    <?php if ($nm<>0) { ?>
    <span style="color:#ccc">|</span>
    <a href='#' class="two" onclick='window.open("generate_tar.php?tar=<?php echo $availability_date; ?>");'><b>Generate Printout</b></a>
    <?php } ?>
</div>
<br>


		<div class="modal hide fade" id="addModal">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">?</button>
				<h3>Edit</h3>
			</div>

				<div class="modal-body">	
						<form  name='add_form' id='add_form' action='train_availability.php' method='post'>

				</form>

				</div>
						
			<div class="modal-footer">
				<a href="#" class="btn" data-dismiss="modal">Close</a>
				<button type='submit' class="btn btn-primary" id='Suc' value='Submit'>Edit </button>
			</div>
		</div>



<script>
/* Slot action hover ? fires on ALL 4 sub-rows via data-train-id */
function initSlotHover(){
    /* Per-column hover: shows buttons only in the cell being hovered */
    $(document).off("mouseenter.slot mouseleave.slot")
      /* Slot cells (Inserted / Removed): reveal buttons in THAT cell only */
      .on("mouseenter.slot",".ta-slot-cell",function(){
          $(this).addClass("td-hover");
      })
      .on("mouseleave.slot",".ta-slot-cell",function(){
          $(this).removeClass("td-hover");
      })
      /* Train Compo cell: reveal Edit Compo button (uses tr.tr-hover) */
      .on("mouseenter.slot","tr[data-train-id]",function(){
          var id=$(this).data("train-id");
          $("tr[data-train-id='"+id+"']").addClass("tr-hover");
      })
      .on("mouseleave.slot","tr[data-train-id]",function(){
          var id=$(this).data("train-id");
          $("tr[data-train-id='"+id+"']").removeClass("tr-hover");
      });
	  /*
    .on("mouseenter.slot",".switch-placeholder",function(){
          $(this).addClass("td-hover");
      })
      .on("mouseleave.slot",".switch-placeholder",function(){
          $(this).removeClass("td-hover");
      });*/
	  
	  
	  
	  
}
$(document).ready(initSlotHover);
$(window).on('load', initSlotHover);
</script>
</body>
	<script src="js/jquery-migrate-1.2.1.min.js"></script>	
		<script src="js/jquery-ui-1.10.3.custom.min.js"></script>	
		<script src="js/jquery.ui.touch-punch.js"></script>	
		<script src="js/modernizr.js"></script>	
		<script src="js/bootstrap.min.js"></script>	
		

<script src="js/date.js"></script>	
<script src='js/form.js'></script>