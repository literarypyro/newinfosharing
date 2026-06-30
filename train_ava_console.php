<?php 
require("Tmenu.php");
ini_set("date.timezone","Asia/Kuala_Lumpur");
?>
<?php
/* =========================================================================
   train_availability_console.php
   Operations Console theme applied to train_availability.php.
   PHP/JS/logic: identical to original.
   Streamlining: all per-row output built in PHP variables first,
   emitted in one echo per row — no mid-HTML <?php ?> tag switching.
   ========================================================================= */
$db = new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");

/* ── Helper functions (verbatim from original) ── */
function getTrainDriver($id,$dbase){
	$sql="select firstName,lastName,position from train_driver where id='".$id."' limit 1";
	$rs=$dbase->query($sql);
	$row=$rs->fetch_assoc();
	return $row['position']." ".substr($row['firstName'],0,1).". ".$row['lastName'];
}
function getPHTrainDriver($id,$dbase){
	$sql="select firstName,lastName from ph_trams where id='".$id."' limit 1";
	$rs=$dbase->query($sql);
	if($rs->num_rows>0){
		$row=$rs->fetch_assoc();
		return substr($row['firstName'],0,1).". ".$row['lastName'];
	}
	return $id;
}
function getLevel($id,$dbase){
	$sql="select * from level where incident_id='".$id."'";
	$rs=$dbase->query($sql);
	$row=$rs->fetch_assoc();
	return $row['order'];
}
function insertCompo($train_id,$car){
	if($car=="") return;
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
	$db->query("insert into train_compo(tar_id,car_no) values ('".$train_id."','".$car."')");
}
function ordinal_numbers($NUM){
	if(strlen($NUM)>1 && substr($NUM,-2,1)==1) return "th";
	$num=substr($NUM,-1);
	if($num==0) return "th";
	if($num==1) return "st";
	if($num==2) return "nd";
	if($num==3) return "rd";
	return "th";
}
function getOrdinal($number){
	$ends=array('th','st','nd','rd','th','th','th','th','th','th');
	if(($number%100)>=11 && ($number%100)<=13)
		return $number.'th';
	return $number.$ends[$number%10];
}

/* ── POST handlers (verbatim from original, whitespace tightened only) ── */
if(isset($_POST['index_no'])){
	$index_no=$_POST['index_no'];
	$lpam_id=$_POST['lpam_id'];
	$type=$_POST['type'];
	$car_a=$_POST['car_1']; $car_b=$_POST['car_2'];
	$car_c=$_POST['car_3']; $car_d=$_POST['car_4'];
	$year=$_POST['year']; $month=$_POST['month']; $day=$_POST['day'];
	$hour=$_POST['hour']; $minute=$_POST['minute']; $amorpm=$_POST['amorpm'];
	if($amorpm=="pm"){ if($hour<12) $hour+=12; }
	else { if($hour=="12") $hour=0; }
	$availability_date=date("Y-m-d H:i",strtotime($year."-".$month."-".$day." ".$hour.":".$minute));
	$update="insert into train_availability(index_no,date,car_a,car_b,car_c,car_d,lpam_id,status,type) values ";
	$update.="('".$index_no."','".$availability_date."','".$car_a."','".$car_b."','".$car_c."','".$car_d."','".$lpam_id."','active','".$type."')";
	$rs=$db->query($update);
	$index_id=$db->insert_id;
	insertCompo($index_id,$car_a); insertCompo($index_id,$car_b);
	insertCompo($index_id,$car_c); insertCompo($index_id,$car_d);
	if(isset($_POST['cancel_departure'])){
		$availability_date="";
		$db->query("update train_availability set status='cancelled' where id='".$index_id."'");
		echo "<script language='javascript'>window.open('incident report.php?cancel=".$index_id."');</script>";
	}
	$db->query("insert into train_ava_time(train_ava_id,boundary_time) values ('".$index_id."','".$availability_date."')");
}

if(isset($_POST['other_index_no'])){
	$index_no=$_POST['other_index_no'];
	$lpam_id=$_POST['lpam_id'];
	$year=$_POST['year']; $month=$_POST['month']; $day=$_POST['day'];
	$hour=$_POST['hour']; $minute=$_POST['minute']; $amorpm=$_POST['amorpm'];
	if($amorpm=="pm"){ if($hour<12) $hour+=12; }
	else { if($hour=="12") $hour=0; }
	$availability_date=date("Y-m-d H:i",strtotime($year."-".$month."-".$day." ".$hour.":".$minute));
	$train_type=$_POST['train_type'];
	$db->query("insert into train_availability(index_no,date,status,type) values ('".$index_no."','".$availability_date."','active','unimog')");
	$index_id=$db->insert_id;
	if(isset($_POST['cancel_departure'])){
		$db->query("update train_availability set status='cancelled' where id='".$index_id."'");
		$availability_date="";
		echo "<script language='javascript'>window.open('incident report.php?cancel=".$index_id."');</script>";
	}
	$db->query("insert into train_ava_time(train_ava_id,boundary_time) values ('".$index_id."','".$availability_date."')");
}

if(isset($_POST['insertion_id'])){
	$year=$_POST['year']; $month=$_POST['month']; $day=$_POST['day'];
	$hour=$_POST['hour']; $minute=$_POST['minute']; $amorpm=$_POST['amorpm'];
	if($amorpm=="pm"){ if($hour<12) $hour+=12; }
	else { if($hour=="12") $hour=0; }
	$availability_date=($_POST['hour']=="") ? "" :
		date("Y-m-d H:i",strtotime($year."-".$month."-".$day." ".$hour.":".$minute));
	if(isset($_POST['unimog_train_driver'])){
		$train_driver=($_POST['unimog_train_driver']=="other") ? $_POST['unimog_td_alternate'] : $_POST['unimog_train_driver'];
	} else {
		$train_driver=$_POST['train_driver'];
	}
	$rs=$db->query("select * from train_ava_time where train_ava_id='".$_POST['insertion_id']."'");
	if($rs->num_rows>0){
		$db->query("update train_ava_time set insert_time='".$availability_date."',insert_driver='".$train_driver."' where train_ava_id='".$_POST['insertion_id']."'");
		$db->query("update train_ava_time set inserted_to='".$_POST['inserted_to']."' where train_ava_id='".$_POST['insertion_id']."'");
	} else {
		$db->query("insert into train_ava_time(train_ava_id,insert_time,insert_driver,inserted_to) values ('".$_POST['insertion_id']."','".$availability_date."','".$train_driver."','".$_POST['inserted_to']."')");
	}
	$changeRow=$db->query("select * from train_availability where id='".$_POST['insertion_id']."'")->fetch_assoc();
	$train_date=$changeRow['date'];
	$_POST['year']=date("Y",strtotime($train_date)); $_POST['month']=date("m",strtotime($train_date));
	$_POST['day']=date("d",strtotime($train_date));  $_POST['hour']=date("H",strtotime($train_date));
	$_POST['minute']=date("i",strtotime($train_date)); $_POST['amorpm']=date("A",strtotime($train_date));
}

if(isset($_POST['remove_id'])){
	$year=$_POST['year']; $month=$_POST['month']; $day=$_POST['day'];
	$hour=$_POST['hour']; $minute=$_POST['minute']; $amorpm=$_POST['amorpm'];
	if(isset($_POST['unimog_train_driver'])){
		$train_driver=($_POST['unimog_train_driver']=="other") ? $_POST['unimog_td_alternate'] : $_POST['unimog_train_driver'];
	} else {
		$train_driver=$_POST['train_driver'];
	}
	if($amorpm=="pm"){ if($hour<12) $hour+=12; }
	else { if($hour=="12") $hour=0; }
	$availability_date=($_POST['hour']=="") ? "" :
		date("Y-m-d H:i",strtotime($year."-".$month."-".$day." ".$hour.":".$minute));
	$cancel_loop=$_POST['cancel_loop'];
	$db->query("update train_ava_time set remove_time='".$availability_date."',remove_driver='".$train_driver."',removal_remarks=\"".$_POST['remarks']."\" where train_ava_id='".$_POST['remove_id']."'");
	$db->query("update train_ava_time set removed_from='".$_POST['removed_from']."' where train_ava_id='".$_POST['remove_id']."'");
	$changeRow=$db->query("select * from train_availability where id='".$_POST['remove_id']."'")->fetch_assoc();
	$train_date=$changeRow['date'];
	$_POST['year']=date("Y",strtotime($train_date)); $_POST['month']=date("m",strtotime($train_date));
	$_POST['day']=date("d",strtotime($train_date));  $_POST['hour']=date("H",strtotime($train_date));
	$_POST['minute']=date("i",strtotime($train_date)); $_POST['amorpm']=date("A",strtotime($train_date));
	if(isset($_POST['cancel_loop'])){
		echo "<script language='javascript'>window.open('incident report.php?add_incident=".$_POST['remove_id']."')</script>";
	}
}

if(isset($_POST['remarks_id'])){
	$rs=$db->query("select * from train_ava_time where train_ava_id='".$_POST['remarks_id']."'");
	if($rs->num_rows>0){
		$db->query("update train_ava_time set removal_remarks=\"".$_POST['remarks']."\" where train_ava_id='".$_POST['remarks_id']."'");
	} else {
		$db->query("insert into train_ava_time(removal_remarks,train_ava_id) values (\"".$_POST['remarks']."\",'".$_POST['remarks_id']."')");
	}
}

if(isset($_POST['switch_id'])){
	$year=$_POST['year']; $month=$_POST['month']; $day=$_POST['day'];
	$hour=$_POST['hour']; $minute=$_POST['minute']; $amorpm=$_POST['amorpm'];
	if($amorpm=="pm"){ if($hour<12) $hour+=12; }
	else { if($hour=="12") $hour=0; }
	$availability_date=date("Y-m-d H:i",strtotime($year."-".$month."-".$day." ".$hour.":".$minute));
	$db->query("insert into train_switch(train_ava_id,new_index,date_change) values ('".$_POST['switch_id']."','".$_POST['new_index']."','".$availability_date."')");
	$switchRow=$db->query("select * from train_availability where id='".$_POST['switch_id']."' limit 1")->fetch_assoc();
	if($switchRow['type']=="reserve"){
		$db->query("update train_availability set type='revenue' where id='".$_POST['switch_id']."'");
	}
}

if(isset($_POST['edit_id'])){
	$db->query("update train_availability set index_no='".$_POST['edit_index']."' where id='".$_POST['edit_id']."'");
}

if(isset($_POST['edit_car'])){
	$db->query("delete from train_compo where tar_id='".$_POST['edit_car']."'");
	$db->query("update train_availability set car_a='".$_POST['car_1']."',car_b='".$_POST['car_2']."',car_c='".$_POST['car_3']."',car_d='".$_POST['car_4']."' where id='".$_POST['edit_car']."'");
	insertCompo($index_id,$_POST['car_1']); insertCompo($index_id,$_POST['car_2']);
	insertCompo($index_id,$_POST['car_3']); insertCompo($index_id,$_POST['car_4']);
}
?>

<link href="css/modal_only.css" rel="stylesheet" />
<link rel="stylesheet" href="jquery-ui-themes-1.11.1/themes/smoothness/jquery-ui.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.11.0/dist/tabler-icons.min.css">
<script src="jquery-ui-1.11.1/external/jquery/jquery.js"></script>
<script src="jquery-ui-1.11.1/jquery-ui.js"></script>

<style type='text/css'>

/* ── Modal ── */
.modal { z-index: 99999; }

/* ── Form inputs inside modal ── */
#add_form th { background-color:#cccccc; }
#add_form td:nth-child(odd)  { background-color:#DCDCDC; color:black; font-weight:bold; padding:5px; }
#add_form td:last-child      { background-color:white; }
#add_form td:nth-child(even) { background-color:#f5f5f5; border:1px solid #cccccc; }
input[type="text"] { height:25px; font-weight:bold; font-size:15px; font-family:courier; border:1px solid #FFD700; background-color:#FFFACD; border-radius:3px; }
input[type="text"]:focus { background-color:#FFFFF0; }
textarea { border:1px solid #FFD700; background-color:#FFFACD; border-radius:3px; }
textarea:focus { background-color:#FFFFF0; font-weight:bold; }
select { border:1px solid rgb(185,201,254); color:black; background-color:#FFFACD; }

/* ── Link classes ── */
a.two:visited { color:black; }
a.two:hover, a.two:active { font-size:120%; color:orange; }
a.Llink:link    { color:#FF0000; }
a.Llink:visited { color:black; }
a.Llink:hover   { color:Orange; }
a.Llink:active  { color:#0000FF; }
a.liR           { text-decoration:none; }
a.liR:hover     { font-weight:bold; color:red; border-bottom:solid 1px; border-top:solid 1px; }
a.LEdit:visited { color:blue; }
a.LDel:visited  { color:red; }
.alink a.disabled { color:#666; text-decoration:none; }

/* ── Slot / compo hover system ── */
.ta-slot-cell      { padding:6px 8px !important; vertical-align:top !important; min-width:100px; }
.switch-cell       { padding:6px 8px !important; vertical-align:top !important; min-width:100px; }
.switch-index      { display:block; font-size:13px; font-weight:700; color:#1A2238; line-height:1.35; }
.switch-time       { display:block; font-size:13px; font-weight:700; color:#1A2238; line-height:1.35; }
.switch-driver     { display:block; font-size:11px; color:#5A6278; line-height:1.35; font-weight:normal; }
.ta-slot-time      { display:block; font-size:13px; font-weight:700; color:#1A2238; line-height:1.35; }
.ta-slot-driver    { display:block; font-size:11px; color:#5A6275; line-height:1.35; margin-top:2px; }
.ta-slot-actions   { display:block; margin-top:5px; height:20px; visibility:hidden; }
.ta-slot-actions.ta-visible   { visibility:visible; }
td.td-hover .ta-slot-actions  { visibility:visible; }
.switch-placeholder .ta-act        { opacity:0; }
.switch-placeholder:hover .ta-act  { opacity:1; }

/* ── .ta-act ── */
.ta-act { display:inline-block !important; font-size:10px !important; font-weight:600 !important; text-decoration:none !important; padding:2px 7px !important; border-radius:3px !important; border:1px solid #B8B0A2 !important; background:#F1EEE3 !important; color:#00529B !important; line-height:1.5 !important; cursor:pointer !important; margin-right:3px !important; float:none !important; width:auto !important; }
.ta-act:hover        { background:#00529B !important; color:#FFFFFF !important; border-color:#00529B !important; }
.ta-act-cancel       { color:#B23A33 !important; border-color:#DDB5B3 !important; background:#FDF2F2 !important; }
.ta-act-cancel:hover { background:#B23A33 !important; color:#FFFFFF !important; border-color:#B23A33 !important; }
.ta-act-sep          { font-size:10px !important; color:#C4BBAE !important; margin:0 2px; }
.ta-act.disabled     { display:none !important; }

/* ── Train compo ── */
.tc-compo { display:flex; flex-direction:column; align-items:center; gap:3px; }
.tc-car { display:inline-block; font-size:13px; font-weight:700; color:#00529B; text-decoration:none; background:#EEF4FB; border:1px solid #C5D8EE; border-radius:4px; padding:2px 10px; min-width:36px; text-align:center; transition:background .12s,color .12s; float:none !important; width:auto !important; }
.tc-car:hover    { background:#00529B; color:#FFFFFF; border-color:#00529B; }
.tc-car.disabled { color:#888; border-color:#ddd; background:#f5f5f5; pointer-events:none; }
.tc-edit-wrap { margin-top:4px; opacity:0; transition:opacity .15s; height:18px; }
.tc-compo-cell:hover .tc-edit-wrap, tr.tr-hover .tc-edit-wrap { opacity:1; }
.tc-edit-btn { font-size:10px !important; font-weight:500 !important; color:#5A6275 !important; text-decoration:none !important; background:transparent !important; border:1px solid #D8D2C2 !important; border-radius:3px !important; padding:2px 7px !important; display:inline-block !important; float:none !important; width:auto !important; cursor:pointer !important; }
.tc-edit-btn:hover    { color:#00529B !important; border-color:#00529B !important; background:#EEF4FB !important; }
.tc-edit-btn.disabled { display:none !important; }
.editindex .ta-act        { visibility:hidden; }
.editindex:hover .ta-act  { visibility:visible; }

/* =========================================================================
   OPERATIONS CONSOLE THEME
   ========================================================================= */
:root {
	--ta-sans: "Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif;
	--ta-mono: ui-monospace, "Cascadia Mono", "Consolas", "Liberation Mono", monospace;
}
.ta-grid { font-family:var(--ta-sans); color:#16243B; }

/* Toolbar */
.ta-grid.ta-console .ta-toolbar { background:#00529B; padding:9px 13px; border-bottom:3px solid #FDB813; display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
.ta-grid.ta-console .tb-wm   { font-weight:500; letter-spacing:.4px; background:#FDB813; color:#3A2D00; padding:2px 8px; border-radius:4px; font-size:11px; }
.ta-grid.ta-console .tb-date { color:#fff; font-weight:500; }
.ta-grid.ta-console .tb-day  { color:rgba(255,255,255,.6); font-size:11px; }

/* Data table */
.ta-grid.ta-console table.train_ava { border-collapse:collapse; width:100%; }
.ta-grid.ta-console table.train_ava td { border:1px solid #D2DDEA; padding:6px 8px; font-family:var(--ta-sans); }
.ta-grid.ta-console table.train_ava th { border:1px solid #0A639E; padding:6px 8px; font-family:var(--ta-sans); font-weight:500; font-size:11px; }
.ta-grid.ta-console tr.rowHeading th { background:#00529B; color:#fff; border-color:#0A639E; }
.ta-grid.ta-console tr.rowHeading:nth-child(2) th { background:#0A5FA8; }

/* Row status colours */
.ta-grid.ta-console tr.row--service   td { background:#ffffff; }
.ta-grid.ta-console tr.row--service.row--alt td { background:#f5f5f5; }
.ta-grid.ta-console tr.row--removed   td { background:#EEF4FB; }
.ta-grid.ta-console tr.row--cancelled td { background:#FCF0EE; }
.ta-grid.ta-console tr.row--reserve   td { background:#FFF8ED; }

/* Index cell left-rail stripe */
.ta-grid.ta-console td.idx-cell { position:relative; padding-left:10px !important; }
.ta-grid.ta-console td.idx-cell::before { content:""; position:absolute; left:0; top:0; bottom:0; width:3px; }
.ta-grid.ta-console tr.row--service   td.idx-cell::before { background:#1D9E75; }
.ta-grid.ta-console tr.row--reserve   td.idx-cell::before { background:#BA7517; }
.ta-grid.ta-console tr.row--removed   td.idx-cell::before { background:#378ADD; }
.ta-grid.ta-console tr.row--cancelled td.idx-cell::before { background:#E24B4A; }

/* Index number */
.ta-grid.ta-console .idx-num { display:block; font-family:var(--ta-mono); font-weight:700; font-size:17px; color:#00529B; line-height:1.1; }

/* Status pill */
.ta-grid.ta-console .status-pill { display:inline-flex; align-items:center; gap:5px; font-size:10px; font-weight:500; border-radius:10px; padding:1px 7px; margin-top:4px; }
.ta-grid.ta-console .status-pill .led { width:7px; height:7px; border-radius:50%; flex:none; }
.ta-grid.ta-console .pill--service   { background:#E1F3EA; color:#0F6E4E; } .ta-grid.ta-console .pill--service   .led { background:#1D9E75; }
.ta-grid.ta-console .pill--reserve   { background:#FAEEDA; color:#854F0B; } .ta-grid.ta-console .pill--reserve   .led { background:#BA7517; }
.ta-grid.ta-console .pill--removed   { background:#E6F1FB; color:#0C447C; } .ta-grid.ta-console .pill--removed   .led { background:#378ADD; }
.ta-grid.ta-console .pill--cancelled { background:#FCEBEB; color:#A32D2D; } .ta-grid.ta-console .pill--cancelled .led { background:#E24B4A; }

/* Console slot/compo refinements */
.ta-grid.ta-console .switch-index { color:#00529B; font-family:var(--ta-mono); }
.ta-grid.ta-console .switch-time  { font-family:var(--ta-mono); font-size:12px; color:#1A2238; }
.ta-grid.ta-console .tc-car       { font-family:var(--ta-mono); }
.ta-grid.ta-console .ta-slot-time { font-family:var(--ta-mono); }

</style>

<script language='javascript' src='ajax.js'></script>
<script language="javascript">
/* ── All JS verbatim from original ── */
var driverHTML="";

function setHTML(){ makeajax("processing.php?trainDriver=Y","getDriver"); }
function setPH(){ makeajax("processing.php?ph_trams=Y","getPhTrams"); }
function setSchool(){ makeajax("processing.php?supDriver=Y","getSchoolDriver"); }

function getDriver(ajaxHTML){
	if(ajaxHTML=="None available") return;
	driverHTML="<select name='train_driver' id='train_driver'>";
	var driverTerms=ajaxHTML.split("==>");
	var count=(driverTerms.length)*1-1;
	for(var n=0;n<count;n++){
		var parts=driverTerms[n].split(";");
		driverHTML+="<option value='"+parts[0]+"'>"+parts[1].replace("_ENYE_","?")+"</option>";
	}
	driverHTML+="</select>";
	document.getElementById('td').innerHTML=driverHTML;
}

function getSchoolDriver(ajaxHTML){
	if(ajaxHTML=="None available") return;
	driverHTML="<select name='train_driver' id='train_driver'>";
	var driverTerms=ajaxHTML.split("==>");
	var count=(driverTerms.length)*1-1;
	for(var n=0;n<count;n++){
		var parts=driverTerms[n].split(";");
		driverHTML+="<option value='"+parts[0]+"'>"+parts[1].replace("_ENYE_","?")+"</option>";
	}
	driverHTML+="</select>";
	document.getElementById('school_tag').innerHTML=driverHTML;
}

function getPhTrams(ajaxHTML){
	if(ajaxHTML=="None available") return;
	driverHTML="<select name='unimog_train_driver' id='unimog_train_driver'>";
	var driverTerms=ajaxHTML.split("==>");
	var count=(driverTerms.length)*1-1;
	for(var n=0;n<count;n++){
		var parts=driverTerms[n].split(";");
		driverHTML+="<option value='"+parts[0]+"'>"+parts[1]+"</option>";
	}
	driverHTML+="<option value='other'>Other</option></select>";
	driverHTML+="<input style='border:1px solid gray' type=text name='unimog_td_alternate' />";
	document.getElementById('ph_trams_tag').innerHTML=driverHTML;
}

function setDate(){
	var d=new Date();
	var year=d.getFullYear(), mmonth=d.getMonth()*1+1, day=d.getDate();
	var tentativehour=d.getHours(), minute=d.getMinutes(), hour=0, amorpm="AM";
	if(tentativehour==0){ hour=12; amorpm="AM"; }
	else if(tentativehour>12){ hour=tentativehour-12; amorpm="PM"; }
	else { hour=tentativehour; amorpm="AM"; }
	var months=["January","February","March","April","May","June","July","August","September","October","November","December"];
	dateHTML="<select name='month' id='month'><option></option>";
	for(var i=1;i<=12;i++) dateHTML+="<option value='"+i+"'"+(mmonth==i?" selected":"")+">"+months[i-1]+"</option>";
	dateHTML+="</select>";
	dateHTML+="<select name='day' id='day'><option></option>";
	for(var i=1;i<=31;i++) dateHTML+="<option value='"+i+"'"+(day==i?" selected":"")+">"+i+"</option>";
	dateHTML+="</select>";
	yearLimit=year*1+16;
	dateHTML+="<select name='year' id='year'><option></option>";
	for(var i=1999;i<=yearLimit;i++) dateHTML+="<option value='"+i+"'"+(year==i?" selected":"")+">"+i+"</option>";
	dateHTML+="</select><br>";
	dateHTML+="<select name='hour'><option></option>";
	for(var i=1;i<=12;i++) dateHTML+="<option value='"+i+"'"+(hour==i?" selected":"")+">"+i+"</option>";
	dateHTML+="</select>";
	dateHTML+="<select name='minute'><option></option>";
	for(var i=0;i<=59;i++){ var label=(i<10)?"0"+i:i; dateHTML+="<option value='"+i+"'"+(minute==i?" selected":"")+">"+label+"</option>"; }
	dateHTML+="</select>";
	dateHTML+="<select name='amorpm'><option></option>";
	dateHTML+="<option value='am'"+(amorpm=="AM"?" selected":"")+">AM</option>";
	dateHTML+="<option value='pm'"+(amorpm=="PM"?" selected":"")+">PM</option>";
	dateHTML+="</select>";
	document.getElementById('cell').innerHTML=dateHTML;
}

function changeForm(form_type,form_id,form_extra){
	var htmlCode="";
	if(form_type=="insertion"){
		htmlCode="<table><tr><th colspan=2>Add Insertion</th></tr>";
		htmlCode+="<tr><td>Insertion Time</td><td id='cell' name='cell'></td></tr>";
		htmlCode+="<tr><td>Train Driver</td>";
		if(form_extra=="unimog") htmlCode+="<td id='ph_trams_tag' name='ph_trams_tag'></td>";
		else if(form_extra=="test") htmlCode+="<td id='ph_trams_tag' name='ph_trams_tag'></td>";
		else if(form_extra=="schooling") htmlCode+="<td id='school_tag' name='school_tag'></td>";
		else if(form_extra=="reserve") htmlCode+="<td><input type=text name='unimog_train_driver' /></td>";
		else { htmlCode+="<td id='td' name='td'></td>"; setHTML(); }
		htmlCode+="</tr>";
		htmlCode+="<tr><td>Inserted To</td><td><select name='inserted_to' id='inserted_to'>";
		htmlCode+="<option value='north'>North Ave.</option><option value='quezon'>Quezon Ave.</option>";
		htmlCode+="</select></td></tr>";
		htmlCode+="<tr><td colspan=2 class='submit' align=center>";
		htmlCode+="<input type=hidden name='insertion_id' id='insertion_id' value='"+form_id+"' />";
		htmlCode+="<input type=submit value='Submit' /></td></tr></table>";
	}
	else if(form_type=="removal"){
		htmlCode="<table><tr><th colspan=2>Add Removal</th></tr>";
		htmlCode+="<tr><td>Removal Time</td><td id='cell' name='cell'></td></tr>";
		htmlCode+="<tr><td>Train Driver</td>";
		if(form_extra=="unimog") htmlCode+="<td id='ph_trams_tag' name='ph_trams_tag'></td>";
		else if(form_extra=="test") htmlCode+="<td id='ph_trams_tag' name='ph_trams_tag'></td>";
		else if(form_extra=="reserve") htmlCode+="<td><input type=text name='unimog_train_driver' /></td>";
		else if(form_extra=="schooling") htmlCode+="<td id='school_tag' name='school_tag'></td>";
		else htmlCode+="<td id='td' name='td'></td>";
		htmlCode+="</tr>";
		htmlCode+="<tr><td>Remarks/Cause of <br>Failure/Removal</td>";
		htmlCode+="<td><span name='remarks_space' id='remarks_space'><textarea name='remarks' cols=50></textarea></span>";
		htmlCode+="<input type=checkbox name='remarks_check' id='remarks_check' onclick='setPreset(this)' />Preset Values</td></tr>";
		htmlCode+="<tr><td>Removed From</td><td><select name='removed_from' id='removed_from'>";
		htmlCode+="<option value='north'>North Ave.</option><option value='quezon'>Quezon Ave.</option>";
		htmlCode+="</select></td></tr>";
		htmlCode+="<tr><td>Add Incident?</td><td>";
		htmlCode+="<input type='checkbox' name='cancel_loop' id='cancel_loop' />Open Incident Report</td></tr>";
		htmlCode+="<tr><td colspan=2 class='submit' align=center>";
		htmlCode+="<input type=hidden name='remove_id' id='remove_id' value='"+form_id+"' />";
		htmlCode+="<input type=submit value='Submit' /></td></tr></table>";
	}
	else if(form_type=="index_switch"){
		htmlCode="<table><tr><th colspan=2>Switch Index No.</th></tr>";
		htmlCode+="<tr><td>New Index No.</td><td><input type=text id='new_index_input' /></td></tr>";
		htmlCode+="<tr><td>Time of Switch</td><td id='cell' name='cell'></td></tr>";
		htmlCode+="<tr><td colspan=2 align=center><button type='button' onclick='submitSwitch("+form_id+")'>Submit</button></td></tr></table>";
	}
	else if(form_type=="editIndex"){
		htmlCode="<table><tr><th colspan=2>Edit Index No.</th></tr>";
		htmlCode+="<tr><td>New Index No.</td><td><input type=text name='edit_index' /></td></tr>";
		htmlCode+="<tr><td colspan=2><input type='submit' class='submit' value='Submit' /></td></tr>";
		htmlCode+="<tr><input type=hidden name='edit_id' id='edit_id' value='"+form_id+"' /></tr></table>";
	}
	else if(form_type=="editCar"){
		htmlCode="<table><tr><th colspan=2>Edit Car</th></tr>";
		htmlCode+="<tr><td>Car 1</td><td><input type=text name='car_1' id='car_1' autocomplete='off' onblur='fillCar(\"first\",this.value)' /></td></tr>";
		htmlCode+="<tr><td>Car 2</td><td><input type=text name='car_2' id='car_2' autocomplete='off' onblur='fillCar(\"mid\",this.value)' /></td></tr>";
		htmlCode+="<tr><td>Car 3</td><td><input type=text name='car_3' id='car_3' autocomplete='off' onblur='fillCar(\"last\",this.value)' /></td></tr>";
		htmlCode+="<tr><td colspan=2><input type='submit' class='submit' value='Submit' /></td></tr>";
		htmlCode+="<tr><input type=hidden name='edit_car' id='edit_car' value='"+form_id+"' /></tr></table>";
	}
	else if(form_type=="add_train"){
		htmlCode="<table><tr><th colspan=2>Add/Prep Train</th></tr>";
		htmlCode+="<tr><td>Type</td><td><select name='type' id='type' onchange='setTrain(this.value)'>";
		htmlCode+="<option value='revenue'>Revenue Train</option><option value='reserve'>Reserve Train</option>";
		htmlCode+="<option value='schooling'>Schooling Train</option><option value='finance'>Finance Train</option>";
		htmlCode+="<option value='test'>Test Train</option></select></td></tr>";
		htmlCode+="<tr><td>Index No.</td><td id='index_tag' name='index_tag'><input type=text name='index_no' autocomplete='off' /></td></tr>";
		htmlCode+="<tr><td>Car 1</td><td><input type=text name='car_1' id='car_1' autocomplete='off' onblur='fillCar(\"first\",this.value)' /></td></tr>";
		htmlCode+="<tr><td>Car 2</td><td><input type=text name='car_2' id='car_2' autocomplete='off' onblur='fillCar(\"mid\",this.value)' /></td></tr>";
		htmlCode+="<tr><td>Car 3</td><td><input type=text name='car_3' id='car_3' autocomplete='off' onblur='fillCar(\"last\",this.value)' /></td></tr>";
		htmlCode+="<tr><td>Car 4</td><td><input type=text name='car_4' id='car_4' autocomplete='off' onblur='fillCar(\"last2\",this.value)' /></td></tr>";
		htmlCode+="<tr><td>LPAM No.</td><td><input type=text name='lpam_id' autocomplete='off' /></td></tr>";
		htmlCode+="<tr><th colspan=2><input type=checkbox name='cancel_departure' id='cancel_departure' />Cancelled</th></tr>";
		htmlCode+="<tr><td>I336 Time</td><td id='cell' name='cell'></td></tr>";
		htmlCode+="<tr><td align=center class='submit' colspan=2><input type='submit' value='Add' /></td></tr></table>";
	}
	else if(form_type=="unimog"){
		htmlCode="<table><tr><th colspan=2>Add/Prep Unimog Train</th></tr>";
		htmlCode+="<tr><td>Type of Train</td><td><select name='train_type'><option value='unimog'>UNIMOG</option></select></td></tr>";
		htmlCode+="<tr><td>Index No.</td><td><select name='other_index_no'>";
		for(var n=80;n<=89;n++) htmlCode+="<option value='"+n+"'>"+n+"</option>";
		htmlCode+="</select></td></tr>";
		htmlCode+="<tr><th colspan=2><input type=checkbox name='cancel_departure' id='cancel_departure' />Cancelled</th></tr>";
		htmlCode+="<tr><td>I336 Time</td><td id='cell' name='cell'></td></tr>";
		htmlCode+="<tr><td align=center colspan=2><input type='submit' class='submit' value='Add' /></td></tr></table>";
	}
	else if(form_type=="remarks"){
		htmlCode="<table><tr><th colspan=2>Add/Edit Remarks</th></tr>";
		htmlCode+="<tr><td>Remarks/Cause of <br>Failure/Removal</td>";
		htmlCode+="<td><span name='remarks_space' id='remarks_space'><textarea name='remarks' cols=50>"+form_extra+"</textarea></span>";
		htmlCode+="<input type=checkbox name='remarks_check' id='remarks_check' onclick='setPreset(this)' />Preset Values</td></tr>";
		htmlCode+="<tr><td colspan=2 class='submit' align=center>";
		htmlCode+="<input type=hidden name='remarks_id' id='remarks_id' value='"+form_id+"' />";
		htmlCode+="<input type=submit value='Submit' /></td></tr></table>";
	}
	document.getElementById('add_form').innerHTML=htmlCode;
	$('#addModal').modal('show');
	setDate();
	if(form_type=="removal"||form_type=="insertion"){
		if(form_extra=="test"||form_extra=="unimog") setPH();
		else if(form_extra=="schooling") setSchool();
		else setHTML();
	}
}

function setPreset(check){
	var remarksHTML="";
	if(check.checked){
		remarksHTML="<select name='remarks' id='remarks'>";
		remarksHTML+="<option>AM Off-Peak Removal</option><option>PM Off-Peak Removal</option>";
		remarksHTML+="<option>Normal Removal</option><option>Emergency Removal</option>";
		remarksHTML+="<option>Give Way for Test Train</option><option>Give Way for Schooling Train</option>";
		remarksHTML+="</select>";
	} else {
		remarksHTML="<textarea name='remarks' cols=50></textarea>";
	}
	document.getElementById('remarks_space').innerHTML=remarksHTML;
}

function cancelTrain(train_id){
	if(confirm("Cancel Train?")) window.open("incident report.php?cancel="+train_id);
}

function setTrain(train){
	var trainHTML="";
	switch(train){
		case "revenue":  trainHTML="<input type=text name='index_no' />"; break;
		case "schooling": trainHTML="<input type=text name='index_no' />"; break;
		case "finance":  trainHTML="<input type=text name='index_no' value='90' />"; break;
		case "reserve":
			trainHTML="<select name='index_no' id='index_no'>";
			for(var i=50;i<=69;i++) trainHTML+="<option value='"+i+"'>"+i+"</option>";
			trainHTML+="</select>"; break;
		case "test":
			trainHTML="<select name='index_no' id='index_no'>";
			for(var i=70;i<=79;i++) trainHTML+="<option value='"+i+"'>"+i+"</option>";
			trainHTML+="</select>"; break;
	}
	document.getElementById('index_tag').innerHTML=trainHTML;
}

function deleteSwitch(index){
	if(!confirm("Cancel Switch?")) return;
	var $cell=$('td.switch-cell[data-switch-id="'+index+'"]');
	var train_id=$cell.closest('tr[data-train-id]').data('train-id');
	$.get('processing.php',{deleteSwitch:index},function(data){
		$cell.replaceWith('<td rowspan="4" class="switch-placeholder" data-train-id="'+train_id+'"><a href="#add_form" class="<?php echo $SRemove; ?>" onclick="changeForm(\'index_switch\',\''+train_id+'\',\'\')">Switch Index No.</a></td>');
	});
}

function submitSwitch(train_ava_id){
	var new_index=document.getElementById('new_index_input').value;
	if(new_index==''){alert('Please enter a new index number.');return;}
	var month=document.getElementById('month').value;
	var day=document.getElementById('day').value;
	var year=document.getElementById('year').value;
	var hour=parseInt($('select[name="hour"]').val());
	var minute=parseInt($('select[name="minute"]').val());
	var amorpm=$('select[name="amorpm"]').val();
	if(amorpm=='pm'&&hour<12) hour+=12;
	if(amorpm=='am'&&hour==12) hour=0;
	var hh=(hour<10)?'0'+hour:''+hour;
	var mm=(minute<10)?'0'+minute:''+minute;
	var switch_time=year+'-'+month+'-'+day+' '+hh+':'+mm;
	$.get('processing.php',{ajaxSwitch:1,train_ava_id:train_ava_id,new_index:new_index,switch_time:switch_time},function(data){
		var res=JSON.parse(data);
		if(res.status=='ok'){
			$('#addModal').modal('hide');
			var hh=(hour<10)?'0'+hour:''+hour, mm=(minute<10)?'0'+minute:''+minute;
			var newCell='<td align="center" rowspan="4" class="switch-cell" data-switch-id="'+res.switch_id+'">'
				+'<div class="switch-index">'+new_index+'</div>'
				+'<div class="switch-time">'+hh+':'+mm+'</div>'
				+'<div class="switch-driver"><a href="#" class="LDel" onclick=\'deleteSwitch("'+res.switch_id+'")\'>X</a></div>'
				+'</td>';
			$('td.switch-placeholder[data-train-id="'+train_ava_id+'"]').first().replaceWith(newCell);
		}
	});
}

function deleteRow(index){
	if(confirm("Remove Record?")) makeajax("processing.php?removeRow="+index,"reloadPage");
}

function reloadPage(ajaxHTML){ self.location="train_availability.php"; }

function fillCar(position,car){
	var car_a=document.getElementById('car_1').value*1;
	var car_b=document.getElementById('car_2').value*1;
	var car_c=document.getElementById('car_3').value*1;
	var car_d=document.getElementById('car_4').value*1;
	var field="car_1", counter=0;
	if(position=="first"){ field="car_1"; if(car!=""){ if(car==car_b)counter++; if(car==car_c)counter++; if(car==car_d)counter++; } }
	else if(position=="mid")  { field="car_2"; if(car!=""){ if(car==car_a)counter++; if(car==car_c)counter++; if(car==car_d)counter++; } }
	else if(position=="last") { field="car_3"; if(car!=""){ if(car==car_a)counter++; if(car==car_b)counter++; if(car==car_d)counter++; } }
	else if(position=="last2"){ field="car_4"; if(car!=""){ if(car==car_a)counter++; if(car==car_b)counter++; if(car==car_c)counter++; } }
	if(counter>0){ alert("Car already in Compo of Train!"); document.getElementById(field).value=""; }
	else { makeajax("processing.php?checkCar="+car+"&car="+field,"confirmCar"); }
	$('#addModal').modal('show');
}

function confirmCar(ajaxHTML){
	if(ajaxHTML!="No car"){ alert("Car already in Compo of another Train!"); document.getElementById(ajaxHTML).value=""; }
}

$(function(){ $("#search_date").datepicker({changeMonth:true,changeYear:true,showAnim:"clip"}); });

</script>

<body>
<div style="clear:both;height:0;font-size:0;line-height:0"></div>

<?php
/* ── Date resolution ── */
$availability_date     = date("F d, Y");
$availability_date_code = date("Y-m-d");
if(isset($_POST['search_date'])){
	$_SESSION['search_date'] = $_POST['search_date'];
	$availability_date      = date("F d, Y", strtotime($_POST['search_date']));
	$availability_date_code = date("Y-m-d",  strtotime($_POST['search_date']));
}
if(isset($_SESSION['search_date'])){
	$availability_date      = date("F d, Y", strtotime($_SESSION['search_date']));
	$availability_date_code = date("Y-m-d",  strtotime($_SESSION['search_date']));
}

/* ── Resolve display date and session month/day/year for toolbar ── */
if(isset($_POST['search_date'])){
	$availability_date      = date("F d, Y", strtotime($_POST['search_date']));
	$_SESSION['month']      = date("m",      strtotime($availability_date));
	$_SESSION['day']        = date("d",      strtotime($availability_date));
	$_SESSION['year']       = date("Y",      strtotime($availability_date));
	$availability_date_code = date("Y-m-d",  strtotime($_POST['search_date']));
} elseif(isset($_SESSION['search_date'])){
	$availability_date      = date("F d, Y", strtotime($_SESSION['search_date']));
	$availability_date_code = date("Y-m-d",  strtotime($_SESSION['search_date']));
	$_SESSION['month']      = date("m",      strtotime($availability_date));
	$_SESSION['day']        = date("d",      strtotime($availability_date));
	$_SESSION['year']       = date("Y",      strtotime($availability_date));
} else {
	$year  = date("Y"); $month = date("m"); $day = date("d");
	$availability_date      = date("F d, Y", strtotime($year."-".$month."-".$day));
	$availability_date_code = date("Y-m-d",  strtotime($year."-".$month."-".$day));
}

$dayOfWeek = date("l", strtotime($availability_date_code));

/* ── Permission flags ── */
if($ULev>=2){
	$SRemove="Llink"; $SRemove2="two pull-left"; $SRemove3="liR grow";
	$SRemove4="LEdit"; $SRemove5="LDel";
} else {
	$SRemove="disabled"; $SRemove2="disabled"; $SRemove3="disabled";
	$SRemove4="disabled"; $SRemove5="disabled";
}
$SRemove4="enabled"; /* toolbar always enables add/unimog */

/* ── Timetable ── */
$timeTableSQL="select *,timetable_day.id as timeId from timetable_day
	inner join timetable_code on timetable_day.timetable_code=timetable_code.id
	where train_date='".$availability_date_code."'";
$timeTableRS=$db->query($timeTableSQL);
if($timeTableRS->num_rows>0){
	$ttRow=$timeTableRS->fetch_assoc();
	$ttCode=$ttRow['code'];
	$ttLink='<a href=\'#\' onclick=\'window.open("timetable_set.php?reset='.$ttRow['timeId'].'","code","height=300,width=350")\' style="color:rgba(255,255,255,.5);font-size:10px;text-decoration:none;">Set/Reset</a>';
} else {
	$ttCode="______";
	$ttLink='<a href=\'#\' onclick=\'window.open("timetable_set.php?set=1","code","height=300,width=350")\' style="color:rgba(255,255,255,.5);font-size:10px;text-decoration:none;">Set/Reset</a>';
}

/* ── Add/Unimog buttons ── */
if($SRemove!="disabled"){
	$addTrainBtn='<a href=\'#\' onclick=\'changeForm("add_train","","")\' style="display:inline-block;font-size:11px;font-weight:500;color:#fff;text-decoration:none;padding:4px 10px;border:1px solid rgba(255,255,255,.35);border-radius:3px;margin-left:6px;float:none;width:auto">+ Add Train</a>';
	$unimogBtn  ='<a href=\'#\' onclick=\'changeForm("unimog","","")\' style="display:inline-block;font-size:11px;font-weight:500;color:#fff;text-decoration:none;padding:4px 10px;border:1px solid rgba(255,255,255,.35);border-radius:3px;margin-left:6px;float:none;width:auto">UNIMOG</a>';
} else {
	$addTrainBtn='<span style="font-size:11px;color:rgba(255,255,255,.3);margin-left:6px">+ Add Train</span>';
	$unimogBtn  ='<span style="font-size:11px;color:rgba(255,255,255,.3);margin-left:6px">UNIMOG</span>';
}
?>

<script type="text/javascript">
$(document).ready(function(){
	$(".alink a").each(function(){
		if($(this).hasClass("disabled")) $(this).removeAttr("href");
	});
});
</script>

<div class="ta-grid ta-console">

<!-- ── TOOLBAR ── -->
<table cellspacing="0" cellpadding="0" style="width:100%;background:#00529B;border-bottom:3px solid #FDB813;border-collapse:collapse;position:relative;z-index:1">
<tr>
	<td style="padding:8px 14px;vertical-align:middle;white-space:nowrap;width:1%;border:none">
		<span style="font-size:15px;font-weight:700;color:#FFFFFF"><?php echo $availability_date; ?></span>
		<span style="font-size:11px;color:rgba(255,255,255,.6);margin-left:8px"><?php echo $dayOfWeek; ?></span>
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
		<?php echo $addTrainBtn; echo $unimogBtn; ?>
		<a href='#' onclick='window.open("generate_tar.php?tar=<?php echo $availability_date_code; ?>");' style="display:inline-block;font-size:11px;font-weight:600;color:#3A2D00;text-decoration:none;padding:4px 10px;border:1px solid #FDB813;border-radius:3px;margin-left:6px;background:#FDB813;float:none;width:auto">Generate Printout</a>
		<span style="display:inline-block;font-size:11px;color:rgba(255,255,255,.6);margin-left:14px;padding-left:14px;border-left:1px solid rgba(255,255,255,.2);vertical-align:middle">
			Timetable:&nbsp;<strong style="color:#fff"><?php echo $ttCode; ?></strong>&nbsp;<?php echo $ttLink; ?>
		</span>
	</td>
</tr>
</table>
<!-- end toolbar -->

<!-- ── DATA TABLE ── -->
<div class='alink'>
<table class='train_ava'>
<tr class='rowHeading'>
<th rowspan=2>Index No.</th>
<th colspan=7>Switch</th>
<th rowspan=2>Train Compo</th>
<th rowspan=2>Time on I336</th>
<th rowspan=2>Inserted</th>
<th rowspan=2>Removed</th>
<th rowspan=2>Remarks/Cause of Failure/Removal</th>
<th colspan=3>Removal</th>
</tr>
<tr class='rowHeading'>
	<?php for($i=0;$i<7;$i++){ ?><th>Index No.</th><?php } ?>
	<th>L2</th><th>L3</th><th>L4</th>
</tr>

<?php
$availability_date = $availability_date_code;

$sql="
	SELECT ta.*,
		tat.boundary_time, tat.insert_time, tat.insert_driver, tat.inserted_to,
		tat.remove_time, tat.remove_driver, tat.removed_from, tat.removal_remarks
	FROM train_availability ta
	LEFT JOIN train_ava_time tat ON tat.train_ava_id = ta.id
	WHERE ta.date BETWEEN '".$availability_date." 00:00:00' AND '".$availability_date." 23:59:59'
	ORDER BY ta.date
";
$rs  = $db->query($sql);
$nm  = $rs->num_rows;
$SRemove4 = "enabled";

for($i=0; $i<$nm; $i++){
	$row  = $rs->fetch_assoc();
	$row2 = $row;   /* $row2 alias — original used a separate query; now same JOIN result */

	/* ── Row status class ── */
	$removed = ($row2['remove_time']!="" && $row2['remove_time']!="0000-00-00 00:00:00");
	if($row['status']=="cancelled"){
		$rowClass = "row--cancelled";
	} elseif(!$removed && $row['status']=="active"){
		$rowClass = ($i%2>0) ? "row--service row--alt" : "row--service";
	} elseif(!$removed){
		$rowClass = "row--reserve";
	} else {
		$rowClass = "row--removed";
	}

	/* ── Status pill ── */
	if($row['status']=="cancelled"){
		$pill = '<span class="status-pill pill--cancelled"><span class="led"></span>Cancelled</span>';
	} elseif($removed){
		$pill = '<span class="status-pill pill--removed"><span class="led"></span>Removed</span>';
	} elseif($row['status']=="active"){
		$pill = '<span class="status-pill pill--service"><span class="led"></span>In service</span>';
	} else {
		$pill = '<span class="status-pill pill--reserve"><span class="led"></span>Reserve</span>';
	}

	/* ── Switch cells ── */
	$sql3 = "select * from train_switch where train_ava_id='".$row['id']."' order by date_change";
	$rs3  = $db->query($sql3);
	$nm3  = min($rs3->num_rows, 7);
	$switchHTML = "";
	for($n=0; $n<$nm3; $n++){
		$row3 = $rs3->fetch_assoc();
		$swDriver = ($row3['train_driver']!="") ? getTrainDriver($row3['train_driver'], $db) : "";
		$switchHTML .= '<td align=center rowspan=4 class="switch-cell" data-switch-id="'.$row3['id'].'">'
			.'<div class="switch-index">'.htmlspecialchars($row3['new_index']).'</div>'
			.'<div class="switch-time">'.date("H:i",strtotime($row3['date_change'])).'</div>'
			.'<div class="switch-driver">'.htmlspecialchars($swDriver)
			.' <a href=\'#\' class="'.$SRemove5.'" onclick=\'deleteSwitch("'.$row3['id'].'")\'>X</a></div>'
			.'</td>';
	}
	/* blank switch slots */
	$blank = 7 - $nm3;
	for($n=0; $n<$blank; $n++){
		if($n==0){
			$switchHTML .= '<td rowspan=4 class="switch-placeholder" data-train-id="'.$row['id'].'">'
				.'<a href=\'#add_form\' class="ta-act '.$SRemove4.'" onclick=\'changeForm("index_switch","'.$row['id'].'","")\'>Switch Index No.</a>'
				.'</td>';
		} else {
			$switchHTML .= '<td rowspan=4>&nbsp;</td>';
		}
	}

	/* ── Train compo ── */
	$cars = "";
	foreach(['car_a','car_b','car_c','car_d'] as $car_key){
		if(!empty($row[$car_key])){
			$cars .= '<a href=\'car_history.php?car_id='.$row[$car_key].'\' target=\'_blank\' class="tc-car '.$SRemove.'">'.$row[$car_key].'</a>';
		}
	}
	$SRemove4 = "enabled";
	$compoHTML = '<td rowspan=4 class="tc-compo-cell" style="padding:6px 8px;vertical-align:middle;text-align:center">'
		.'<div class="tc-compo">'.$cars
		.'<div class="tc-edit-wrap"><a href=\'#add_form\' class="tc-edit-btn '.$SRemove4.'" onclick=\'changeForm("editCar","'.$row['id'].'","")\'>&#9998; Edit Compo</a></div>'
		.'</div></td>';

	/* ── Boundary time ── */
	$boundary_time = ($row2['boundary_time']!="" && $row2['boundary_time']!="0000-00-00 00:00:00")
		? date("H:i",strtotime($row2['boundary_time'])) : "";

	/* ── Train type string for insertion/removal form ── */
	$trainType = "";
	if($row['type']=="unimog")   $trainType="unimog";
	elseif($row['type']=="test") $trainType="test";
	elseif($row['type']=="reserve")   $trainType="reserve";
	elseif($row['type']=="schooling") $trainType="schooling";

	/* ── Active branch: resolve insert/remove times, incidents, levels ── */
	$insert_time=""; $insert_driver=""; $inserted_to="";
	$remove_time=""; $remove_driver=""; $removed_from="";
	$remove_remarks=$row2['removal_remarks'];
	$incidentClause=""; $level2Clause=""; $level3Clause=""; $level4Clause="";

	if($row['status']=="active"){
		/* Insert time */
		if($row2['insert_time']!="" && $row2['insert_time']!="0000-00-00 00:00:00"){
			$insert_time = date("H:i",strtotime($row2['insert_time']));
			$insert_date = date("Y-m-d",strtotime($row2['insert_time']));
			if(strtotime($availability_date)>strtotime($insert_date))
				$insert_time = $insert_date."<br> ".$insert_time;
			if($row['type']=="unimog"||$row['type']=="test")
				$insert_driver = getPHTrainDriver($row2['insert_driver'],$db)."<br>MAINTENANCE PROVIDER";
			elseif($row['type']=="reserve")
				$insert_driver = $row2['insert_driver'];
			else
				$insert_driver = getTrainDriver($row2['insert_driver'],$db);
			$inserted_to = ($row2['inserted_to']=="quezon") ? "Quezon Ave.<br/>" : "";
		}
		/* Remove time */
		if($row2['remove_time']!="" && $row2['remove_time']!="0000-00-00 00:00:00"){
			$remove_date = date("Y-m-d",strtotime($row2['remove_time']));
			$remove_time = date("H:i",strtotime($row2['remove_time']));
			if(strtotime($availability_date)>strtotime($remove_date))
				$remove_time = $remove_date."<br> ".$remove_time;
			if($row['type']=="unimog"||$row['type']=="test")
				$remove_driver = getPHTrainDriver($row2['remove_driver'],$db)."<br>MAINTENANCE PROVIDER";
			elseif($row['type']=="reserve")
				$remove_driver = $row2['remove_driver'];
			else
				$remove_driver = getTrainDriver($row2['remove_driver'],$db);
			$removed_from = ($row2['removed_from']=="quezon") ? "Quezon Ave.<br/>" : "";
			$remove_remarks = $row2['removal_remarks'];
		}
		/* Incidents and level clauses */
		$cancelSQL = "select * from train_incident_view where train_ava_id='".$row['id']."'";
		$cancelRS  = $db->query($cancelSQL);
		$cancelNM  = $cancelRS->num_rows;
		$l2Count=0; $l3Count=0; $l4Count=0;
		if($cancelNM>0){
			$incidentClause = "<br>See ";
			for($m=0;$m<$cancelNM;$m++){
				$cancelRow = $cancelRS->fetch_assoc();
				$level     = $cancelRow['level'];
				$order     = getLevel($cancelRow['incident_id'],$db);
				$incLink   = "<a href='#' class='$SRemove' onclick='window.open(\"edit_ccdr.php?ir=".$cancelRow['incident_id']."\")'>IN ".$cancelRow['incident_no']."</a>";
				$incidentClause .= ($m==0) ? $incLink : ",<br>".$incLink;
				if($level==2){ $level2Clause.=($l2Count>0?",<br>":"").getOrdinal($order); $l2Count++; }
				elseif($level==3){ $level3Clause.=($l3Count>0?",<br>":"").getOrdinal($order); $l3Count++; }
				elseif($level==4){ $level4Clause.=($l4Count>0?",<br>":"").getOrdinal($order); $l4Count++; }
			}
		}
		if($level2Clause=="") $level2Clause="&nbsp;";
		if($level3Clause=="") $level3Clause="&nbsp;";
		if($level4Clause=="") $level4Clause="&nbsp;";

		/* ── Build active row data cells HTML ── */
		$insertDisplay  = ($inserted_to.$insert_time!="") ? $inserted_to.$insert_time : "&ndash;";
		$removeDisplay  = ($removed_from.$remove_time!="") ? $removed_from.$remove_time : "&ndash;";
		$insertDrDisplay = str_replace("SUP","STDO",$insert_driver);
		$removeDrDisplay = str_replace("SUP","STDO",$remove_driver);

		$dataCells  = '<td rowspan=4>'.$boundary_time.'</td>';
		$SRemove4   = "enabled";
		$dataCells .= '<td rowspan=4 class="ta-slot-cell"><div class="ta-slot">'
			.'<div class="ta-slot-time">'.$insertDisplay.'</div>'
			.'<div class="ta-slot-driver">'.$insertDrDisplay.'</div>'
			.'<div class="ta-slot-actions">'
			.'<a href=\'#add_form\' class="ta-act '.$SRemove4.'" onclick=\'changeForm("insertion","'.$row['id'].'",'
			.'"'.$trainType.'")\'>Edit</a>'
			.'<span class="ta-act-sep">&middot;</span>'
			.'<a href=\'#\' class="ta-act ta-act-cancel '.$SRemove.'" onclick=\'cancelTrain("'.$row['id'].'")\'>Cancel</a>'
			.'</div></div></td>';
		$dataCells .= '<td rowspan=4 class="ta-slot-cell"><div class="ta-slot">'
			.'<div class="ta-slot-time">'.$removeDisplay.'</div>'
			.'<div class="ta-slot-driver">'.$removeDrDisplay.'</div>'
			.'<div class="ta-slot-actions">'
			.'<a href=\'#add_form\' class="ta-act '.$SRemove4.'" onclick=\'changeForm("removal","'.$row['id'].'",'
			.'"'.$trainType.'")\'>Edit</a>'
			.'</div></div></td>';
		$dataCells .= '<td rowspan=4>'.$remove_remarks.$incidentClause
			.'<br><a href=\'#add_form\' class="'.$SRemove.'" onclick=\'changeForm("remarks","'.$row['id'].'",'
			.'"'.addslashes($remove_remarks).'")\'>Add/Edit Remarks </a>'
			.'<br><a href=\'#add_form\' class="'.$SRemove.'" onclick=\'window.open("incident report.php?add_incident='.$row['id'].'")\'>Add Incident </a>'
			.'</td>';
		$dataCells .= '<td rowspan=4>'.$level2Clause.'</td>'
			.'<td rowspan=4>'.$level3Clause.'</td>'
			.'<td rowspan=4>'.$level4Clause.'</td>';

	} elseif($row['status']=="cancelled"){
		/* ── Cancelled branch: incidents, levels, CANCELLED label ── */
		$cancelSQL = "select * from train_incident_view inner join level on train_incident_view.incident_id=level.incident_id where train_ava_id='".$row['id']."'";
		$cancelRS  = $db->query($cancelSQL);
		$cancelNM  = $cancelRS->num_rows;
		$l2Count=0; $l3Count=0; $l4Count=0;
		for($m=0;$m<$cancelNM;$m++){
			$cancelRow = $cancelRS->fetch_assoc();
			$level     = $cancelRow['level'];
			$order     = getLevel($cancelRow['incident_id'],$db);
			$incLink   = "<a href='#' onclick='window.open(\"edit_ccdr.php?ir=".$cancelRow['incident_id']."\")'>IN ".$cancelRow['incident_no']."</a>";
			$incidentClause .= ($m==0) ? $incLink : ",<br>".$incLink;
			if($level==2){ $level2Clause.=($l2Count>0?",<br>":"").getOrdinal($order); $l2Count++; }
			elseif($level==3){ $level3Clause.=($l3Count>0?",<br>":"").getOrdinal($order); $l3Count++; }
			elseif($level==4){ $level4Clause.=($l4Count>0?",<br>":"").getOrdinal($order); $l4Count++; }
		}
		if($level2Clause=="") $level2Clause="&nbsp;";
		if($level3Clause=="") $level3Clause="&nbsp;";
		if($level4Clause=="") $level4Clause="&nbsp;";

		if($boundary_time==""){
			$dataCells = '<td rowspan=4 colspan=6 align=center>CANCELLED</td>';
		} else {
			$dataCells = '<td rowspan=4>'.$boundary_time.'</td>'
				.'<td rowspan=4 colspan=5 align=center>CANCELLED</td>';
		}
		$dataCells .= '<td rowspan=4>'.$remove_remarks.$incidentClause
			.'<br><a href=\'#add_form\' class="'.$SRemove.'" onclick=\'changeForm("remarks","'.$row['id'].'",'
			.'"'.addslashes($remove_remarks).'")\'>Add/Edit Remarks </a>'
			.'<br><a href=\'#\' class="'.$SRemove.'" onclick=\'window.open("incident report.php?add_incident='.$row['id'].'")\'>Add Incident </a>'
			.'</td>';
		$dataCells .= '<td rowspan=4>'.$level2Clause.'</td>'
			.'<td rowspan=4>'.$level3Clause.'</td>'
			.'<td rowspan=4>'.$level4Clause.'</td>';
	} else {
		$dataCells = ""; /* reserve/unimog/test with no active status yet */
	}

	/* ── Emit the row — one echo, no context switching ── */
	echo '<tr data-train-id="'.$row['id'].'" class="'.$rowClass.'">'
		.'<td align=center class="editindex idx-cell" rowspan=4>'
		.'<span class="idx-num">'.$row['index_no'].'</span>'.$pill
		.'<br><a href=\'#\' class="ta-act '.$SRemove4.'" onclick=\'changeForm("editIndex","'.$row['id'].'","")\'> Edit</a>'
		.'</td>'
		.$switchHTML
		.$compoHTML
		.$dataCells
		.'<td rowspan=4 valign=center align=center><a href=\'#\' class="'.$SRemove5.'" onclick=\'deleteRow("'.$row['id'].'")\'>X</a></td>'
		.'</tr>'
		.'<tr data-train-id="'.$row['id'].'" class="'.$rowClass.'"></tr>'
		.'<tr data-train-id="'.$row['id'].'" class="'.$rowClass.'"></tr>'
		.'<tr data-train-id="'.$row['id'].'" class="'.$rowClass.'"></tr>';
}
?>

</table>
<div class="alink" style="margin-top:10px; padding:6px 0; border-top:2px solid #00529B; display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
	<a href='#' class="<?php echo $SRemove; ?>" style='text-decoration:none;' onclick='changeForm("add_train","","")'>+ Add Train</a>
	<span style="color:#ccc">|</span>
	<a href='#' class="Llink" style='text-decoration:none;' onclick='changeForm("unimog","","")'>UNIMOG</a>
	<?php if($nm<>0){ ?>
	<span style="color:#ccc">|</span>
	<a href='#' class="two" onclick='window.open("generate_tar.php?tar=<?php echo $availability_date; ?>");'><b>Generate Printout</b></a>
	<?php } ?>
</div>
<br>

</div><!-- /.ta-grid -->

<div class="modal hide fade" id="addModal">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal">?</button>
		<h3>Edit</h3>
	</div>
	<div class="modal-body">
		<form name='add_form' id='add_form' action='train_availability.php' method='post'></form>
	</div>
	<div class="modal-footer">
		<a href="#" class="btn" data-dismiss="modal">Close</a>
		<button type='submit' form='add_form' class="btn btn-primary" id='Suc' value='Submit'>Edit</button>
	</div>
</div>

<script>
/* Slot action hover — fires on all 4 sub-rows via data-train-id */
function initSlotHover(){
	$(document).off("mouseenter.slot mouseleave.slot")
	.on("mouseenter.slot",".ta-slot-cell",function(){ $(this).addClass("td-hover"); })
	.on("mouseleave.slot",".ta-slot-cell",function(){ $(this).removeClass("td-hover"); })
	.on("mouseenter.slot","tr[data-train-id]",function(){
		var id=$(this).data("train-id");
		$("tr[data-train-id='"+id+"']").addClass("tr-hover");
	})
	.on("mouseleave.slot","tr[data-train-id]",function(){
		var id=$(this).data("train-id");
		$("tr[data-train-id='"+id+"']").removeClass("tr-hover");
	});
}
$(document).ready(initSlotHover);
$(window).on('load',initSlotHover);
</script>
</body>
<script src="js/jquery-migrate-1.2.1.min.js"></script>
<script src="js/jquery-ui-1.10.3.custom.min.js"></script>
<script src="js/jquery.ui.touch-punch.js"></script>
<script src="js/modernizr.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/date.js"></script>
<script src='js/form.js'></script>