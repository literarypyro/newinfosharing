<?php
session_start();
?>
<?php
ini_set("date.timezone","Asia/Kuala_Lumpur");
require("Tmenu.php");
?>
<?php
/* =========================================================================
   incident_report_option_b.php
   Link option: Slide-over drawer (Option B)
   Operations Console redesign of incident report.php.
   PHP/JS: 100% verbatim from original.
   CSS: replaced. Inline border/color attributes on inputs cleaned up
        (cosmetic only — all name/id/onchange/data-* untouched).
   ========================================================================= */
if(isset($_POST['equipment'])){
	
	$incident_id=$_POST['incident_no']." ".$_POST['incident_suffix'];
	$description=$_POST['description'];
	$dotc_taken="";

	if(isset($_POST['dotc'])){
		$dotc_taken=$_POST['dotc'];
	}
	else if(isset($_POST['dotc_coordinated'])){
		$dotc_taken=$_POST['dotc_coordinated']." ".$_POST['coordinated_to'];
	}
	
	$maintenance_taken=$_POST['maintenance'];
	$level=$_POST['level'];
	$duration=$_POST['duration'];
	
	$incident_day=date("Y-m-d",strtotime($_POST['incident_date']));
	
	$hour=$_POST['hour'];
	$minute=$_POST['minute'];
	$amorpm=$_POST['amorpm'];

	$equipment=$_POST['equipment'];
	
	if($equipment=="others"){
		$otherEquipment=$_POST['otherEquipment'];
		
		$sqlAdd="insert into equipment(equipment_name,type,category) values ";
		$sqlAdd.="(";
		$sqlAdd.="'".$otherEquipment."',";
		$sqlAdd.="'RS','EXT'";
		$sqlAdd.=")";
		
		$rsAdd=$db->query($sqlAdd);
		$equipment=$db->insert_id;
	}
	
	if($amorpm=="pm"){
		if($hour<12){ $hour+=12; }
	}
	else {
		if($hour=="12"){ $hour=0; }
	}
	
	$reported_by=$_POST['reported_by'];
	$received_by=$_POST['received_by'];
	$level_condition=$_POST['condition'];
	
	$incident_date=date("Y-m-d H:i",strtotime($_POST['incident_day']." ".$hour.":".$minute));
	$incidentYear=$year;
	
	$type=$_POST['type'];
	
	$cancel=0;
	if(isset($_POST['cancel'])){
		if($_POST['cancel']=="more"){     $cancel=$_POST['cancel_more']; }
		else if($_POST['cancel']=="half"){ $cancel=.5; }
		else if($_POST['cancel']=="whole"){ $cancel=1; }
		else if($_POST['cancel']=="none"){ $cancel=0; }
	}
	
	$unit_no="";
	if(isset($_POST['unit_no'])){ $unit_no=$_POST['unit_no']; }
	
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
	$description=$db->real_escape_string($description);
	
	$sql="insert into incident_report ";
	$sql.="(incident_type,incident_no,level,incident_date,";
	$sql.="description,action_dotc,action_maintenance,duration,equipt,cancel,unit_no,level_condition,recommending_approval,approving_person,action_type)";
	$sql.=" values ";
	$sql.="(\"".$type."\",\"".$incident_id."\",'".$level."','".$incident_date."',";
	$sql.="\"".$description."\",\"".$dotc_taken."\",\"".$maintenance_taken."\",\"".$duration."\",'".$equipment."','".$cancel."','".$unit_no."','".$level_condition."','".$_POST['recommending_approval']."','".$_POST['approving_person']."','".$_POST['action_type']."')";

	$rs=$db->query($sql);
	$incident_code=$db->insert_id;
	$_SESSION['incident_id']=$db->insert_id;

	if($level==2){
		$update="update incident_report set l2='".$_POST['order']."' where id='".$_SESSION['incident_id']."'";
		$rs=$db->query($update);
		$update="insert into level(date,level,incident_id) values ('".date("Y-m-d",strtotime($incident_date))."','2','".$incident_code."')";
		$rs=$db->query($update);
	}
	else if($level==3){
		$update="update incident_report set l3='".$_POST['order']."' where id='".$incident_code."'";
		$rs=$db->query($update);
		$update="insert into level(date,level,incident_id) values ('".date("Y-m-d",strtotime($incident_date))."','3','".$incident_code."')";
		$rs=$db->query($update);
	}
	else if($level==1){
		$update="update incident_report set l3='".$_POST['order']."' where id='".$incident_code."'";
		$rs=$db->query($update);
		$update="insert into level(date,level,incident_id) values ('".date("Y-m-d",strtotime($incident_date))."','1','".$incident_code."')";
		$rs=$db->query($update);
	}
	else if($level==0){
		$update="update incident_report set l3='".$_POST['order']."' where id='".$incident_code."'";
		$rs=$db->query($update);
		$update="insert into level(date,level,incident_id) values ('".date("Y-m-d",strtotime($incident_date))."','0','".$incident_code."')";
		$rs=$db->query($update);
	}
	else if($level==4){
		$update="update incident_report set l4='".$_POST['order']."' where id='".$incident_code."'";
		$rs=$db->query($update);
		$update="insert into level(date,level,incident_id) values ('".date("Y-m-d",strtotime($incident_date))."','4','".$incident_code."')";
		$rs=$db->query($update);
	}

	$incidentSQL="select * from is_user_transport.incident_no where incident_id='".$incident_code."'";
	$incidentRS=$db->query($incidentSQL);
	$incidentNM=$incidentRS->num_rows;
	if($incidentNM==0){
		$insert="insert into is_user_transport.incident_no(year,incident_id,incident_number,suffix) values ('".$incidentYear."','".$incident_code."','".$_POST['incident_no']."','".$_POST['incident_suffix']."')"; 
		$insertRS=$db->query($insert);
	}

		/* ── Multi-link handler ─────────────────────────────────────────────
	   Writes each linked incident to incident_linked_reports junction table.
	   Required DDL (run once):
	     CREATE TABLE incident_linked_reports (
	       id int AUTO_INCREMENT PRIMARY KEY,
	       incident_id int NOT NULL,
	       linked_to   int NOT NULL,
	       UNIQUE KEY uq_pair (incident_id, linked_to)
	     );
	   ─────────────────────────────────────────────────────────────────── */
	if(!empty($_POST['incident_links'])){
		$links=array_filter(is_array($_POST['incident_links'])
			? $_POST['incident_links']
			: explode(',',$_POST['incident_links']));
		$first=true;
		foreach($links as $linked_id){
			$linked_id=(int)trim($linked_id);
			if($linked_id<=0) continue;
			$db->query("insert ignore into incident_linked_reports(incident_id,linked_to) values ('".$incident_code."','".$linked_id."')");
			if($first){ $db->query("update incident_report set linked_to='".$linked_id."' where id='".$incident_code."'"); $first=false; }
		}
	}
	/* Legacy single-link fallback */
	if(empty($_POST['incident_links']) && !empty($_POST['incident_link'])){
		$db->query("update incident_report set linked_to='".(int)$_POST['incident_link']."' where id='".$incident_code."'");
	}
	
	$location=$_POST['location'];
	$direction=$_POST['direction'];
	$subitem=$_POST['subitem'];
	$index_no=$_POST['index_id'];
	$car_no=$_POST['car_id'];
	
	$sql="insert into incident_description ";
	$sql.="(incident_id,location,direction,equipt,subitem,index_no,car_no,reported_by,received_by)";	
	$sql.=" values ";
	$sql.=" ('".$incident_code."','".$location."','".$direction."','".$equipment."','".$subitem."','".$index_no."','".$car_no."','".$reported_by."','".$received_by."')";
	$rs=$db->query($sql);
	
	foreach(['car_id','car_id_2','car_id_3','car_id_4'] as $car_field){
		if($_POST[$car_field]!=""){
			$sql="insert into incident_cars(incident_id,car_no) values ('".$incident_code."','".$_POST[$car_field]."')";
			$rs=$db->query($sql);
		}
	}
	
	if(isset($_GET['cancel'])){
		$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
		$sql="update train_availability set status='cancelled' where id='".$_GET['cancel']."' and status='active'";
		$rs=$db->query($sql);
		$sql="update train_ava_time set cancel_loop='1' where train_ava_id='".$_GET['cancel']."'";
		$rs=$db->query($sql);
		$sql="insert into train_incident_report(train_ava_id,incident_id) values ('".$_GET['cancel']."','".$_SESSION['incident_id']."')";
		$rs=$db->query($sql);
		echo "<script language='javascript'>window.opener.location='train_availability.php';</script>";
	}
	if(isset($_GET['add_incident'])){
		$sql="insert into train_incident_report(train_ava_id,incident_id) values ('".$_GET['add_incident']."','".$incident_code."')";
		$rs=$db->query($sql);
		if(isset($_POST['cancel'])){
			$sql="update train_ava_time set cancel_loop='".$cancel."' where train_ava_id='".$_GET['cancel']."'";
			$rs=$db->query($sql);
		}
		echo "<script language='javascript'>window.opener.location='train_availability.php';</script>";
	}
	
	if($level_condition=='3'){
		echo "<script language='javascript'>window.open('service interruption.php?incident=".$incident_code."');</script>";
	}

	$db2=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_external");
	$update="insert into incident_defects(incident_id,equipt_id,sub_item_id) (select '".$incident_code."',equipt_id,sub_item_id from temp_multiple)";
	$updateRS=$db2->query($update);
	$update="delete from temp_multiple";
	$updateRS=$db2->query($update);
}
?>

<link rel="stylesheet" href="jquery-ui-themes-1.11.1/themes/smoothness/jquery-ui.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.11.0/dist/tabler-icons.min.css">
<script src="jquery-ui-1.11.1/external/jquery/jquery.js"></script>
<script src="jquery-ui-1.11.1/jquery-ui.js"></script>

<style type='text/css'>
/* =========================================================================
   INCIDENT REPORT — Operations Console Theme
   Scoped under .ir-page so nothing bleeds to other pages loaded alongside.
   ========================================================================= */
:root {
	--ir-blue:    #00529B;
	--ir-gold:    #FDB813;
	--ir-dark:    #16243B;
	--ir-mid:     #41506A;
	--ir-muted:   #8A95A6;
	--ir-border:  #D2DDEA;
	--ir-row-odd: #EEF4FB;
	--ir-bg:      #F7F9FC;
	--ir-white:   #ffffff;
	--ir-sans:    "Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif;
	--ir-mono:    ui-monospace, "Cascadia Mono", "Consolas", monospace;
}

body { background: #EAEEF3; font-family: var(--ir-sans); color: var(--ir-dark); margin: 0; }

.ir-page {
	max-width: 780px;
	margin: 24px auto 48px;
	border-radius: 10px;
	overflow: hidden;
	box-shadow: 0 2px 12px rgba(0,30,80,.10), 0 1px 3px rgba(0,30,80,.07);
	background: var(--ir-white);
}

/* ── Page header ── */
.ir-page-header {
	background: var(--ir-blue);
	border-bottom: 3px solid var(--ir-gold);
	padding: 13px 20px;
	display: flex;
	align-items: center;
	gap: 12px;
}
.ir-page-header .ir-wordmark {
	background: var(--ir-gold);
	color: #3A2D00;
	font-size: 11px;
	font-weight: 600;
	letter-spacing: .5px;
	padding: 2px 8px;
	border-radius: 4px;
}
.ir-page-header h1 {
	margin: 0;
	font-size: 14px;
	font-weight: 600;
	color: #fff;
	letter-spacing: .3px;
}
.ir-page-header .ir-context {
	margin-left: auto;
	font-size: 11px;
	color: rgba(255,255,255,.6);
}

/* ── Form body ── */
.ir-form-body { padding: 0; }

/* ── Section headers ── */
.ir-section-head {
	background: var(--ir-blue);
	color: #fff;
	font-size: 11px;
	font-weight: 600;
	letter-spacing: .5px;
	text-transform: uppercase;
	padding: 7px 16px;
	border-bottom: 2px solid var(--ir-gold);
}

/* ── Field rows ── */
.ir-table {
	width: 100%;
	border-collapse: collapse;
}
.ir-table tr { border-bottom: 1px solid var(--ir-border); }
.ir-table tr:last-child { border-bottom: none; }

.ir-table td.ir-label {
	background: var(--ir-row-odd);
	color: var(--ir-dark);
	font-size: 11px;
	font-weight: 600;
	padding: 9px 16px;
	width: 200px;
	vertical-align: middle;
	white-space: nowrap;
}
.ir-table td.ir-label.ir-label--top { vertical-align: top; padding-top: 11px; }

.ir-table td.ir-field {
	background: var(--ir-white);
	padding: 7px 14px;
	vertical-align: middle;
}
.ir-table td.ir-field--top { vertical-align: top; padding-top: 9px; }

/* ── Form controls ── */
.ir-page input[type="text"],
.ir-page input[type="number"] {
	height: 28px;
	font-size: 12px;
	font-family: var(--ir-sans);
	font-weight: 400;
	border: 1px solid var(--ir-border);
	background: var(--ir-white);
	color: var(--ir-dark);
	border-radius: 4px;
	padding: 0 8px;
	box-sizing: border-box;
	transition: border-color .15s, box-shadow .15s;
}
.ir-page input[type="text"]:focus,
.ir-page input[type="number"]:focus {
	border-color: var(--ir-blue);
	outline: none;
	box-shadow: 0 0 0 2px rgba(0,82,155,.12);
}

/* Narrow inputs for short values */
.ir-page input.ir-input--xs  { width: 64px; }
.ir-page input.ir-input--sm  { width: 100px; }
.ir-page input.ir-input--md  { width: 180px; }
.ir-page input.ir-input--lg  { width: 100%; }

.ir-page select {
	height: 28px;
	font-size: 12px;
	font-family: var(--ir-sans);
	border: 1px solid var(--ir-border);
	background: var(--ir-white);
	color: var(--ir-dark);
	border-radius: 4px;
	padding: 0 6px;
	box-sizing: border-box;
}
.ir-page select:focus { border-color: var(--ir-blue); outline: none; }

/* Time selects inline */
.ir-page select.ir-sel--time { width: auto; display: inline-block; margin-right: 3px; }

/* Suffix select (next to incident no.) */
.ir-page select.ir-sel--suffix { width: auto; display: inline-block; margin-left: 6px; }

/* Equipment and sub-item selects: full-width */
.ir-page select.ir-sel--full  { width: 100%; }

.ir-page textarea {
	font-size: 12px;
	font-family: var(--ir-sans);
	border: 1px solid var(--ir-border);
	background: var(--ir-white);
	color: var(--ir-dark);
	border-radius: 4px;
	padding: 7px 9px;
	width: 100%;
	box-sizing: border-box;
	resize: vertical;
	min-height: 80px;
}
.ir-page textarea:focus { border-color: var(--ir-blue); outline: none; box-shadow: 0 0 0 2px rgba(0,82,155,.12); }

/* ── Inline field groups (index / car selects) ── */
.ir-inline { display: flex; align-items: center; flex-wrap: wrap; gap: 6px; }
.ir-inline .ir-sep { color: var(--ir-muted); font-size: 13px; font-weight: 500; }

/* ── Checkbox rows ── */
.ir-check-row { display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--ir-mid); }
.ir-check-row input[type="checkbox"] { margin: 0; accent-color: var(--ir-blue); width: 14px; height: 14px; }

/* ── Button styles ── */
.ir-page input[type="submit"] {
	height: 34px;
	font-size: 13px;
	font-weight: 600;
	font-family: var(--ir-sans);
	background: var(--ir-blue);
	color: #fff;
	border: none;
	border-radius: 5px;
	padding: 0 28px;
	cursor: pointer;
	letter-spacing: .3px;
	transition: background .15s;
}
.ir-page input[type="submit"]:hover { background: #013E76; }

.ir-page input[type="button"],
.ir-page button[type="button"] {
	height: 28px;
	font-size: 11px;
	font-weight: 500;
	font-family: var(--ir-sans);
	background: var(--ir-white);
	color: var(--ir-blue);
	border: 1px solid var(--ir-border);
	border-radius: 4px;
	padding: 0 12px;
	cursor: pointer;
}
.ir-page input[type="button"]:hover,
.ir-page button[type="button"]:hover { background: var(--ir-row-odd); border-color: var(--ir-blue); }

/* ── Submit footer ── */
.ir-submit-row {
	background: var(--ir-bg);
	border-top: 1px solid var(--ir-border);
	padding: 16px 20px;
	text-align: right;
}

/* ── Level condition span (injected by JS getLevel) ── */
#condition select { margin-left: 10px; }

/* ── Multiple defects table (injected by JS activateMultiple) ── */
#multi_list { width: 100%; border-collapse: collapse; margin-top: 6px; }
#multi_list th { background: var(--ir-blue); color: #fff; font-size: 11px; font-weight: 600; padding: 5px 10px; border: 1px solid var(--ir-border); }
#multi_list td { background: var(--ir-row-odd); font-size: 12px; padding: 5px 10px; border: 1px solid var(--ir-border); }
#multi_list tr:nth-child(odd) td { background: var(--ir-white); }

/* ── Dropdown menu (Bootstrap autocomplete — kept as-is visually) ── */
.dropdown-menu { position:absolute; top:100%; left:0; z-index:1000; display:none; float:left; min-width:160px; padding:5px 0; margin:2px 0 0; list-style:none; background-color:#fff; border:1px solid rgba(0,0,0,0.15); border-radius:6px; box-shadow:0 5px 10px rgba(0,0,0,0.12); }
.dropdown-menu>li>a { display:block; padding:5px 16px; font-size:12px; color:#333; white-space:nowrap; text-decoration:none; }
.dropdown-menu>li>a:hover { color:#fff; background-color:var(--ir-blue); }

/* ── datepicker override ── */
.ui-datepicker { font-size: 12px; font-family: var(--ir-sans); }


/* ── Linked incident chips (shared by all link options) ── */
.ir-link-chips{display:flex;flex-wrap:wrap;gap:6px;min-height:32px;padding:6px 8px;border:1px solid var(--ir-border);border-radius:4px;background:var(--ir-bg);margin-top:8px;}
.ir-link-chip{display:inline-flex;align-items:center;gap:5px;background:var(--ir-row-odd);border:1px solid var(--ir-border);border-radius:12px;padding:2px 6px 2px 9px;font-size:11px;font-weight:500;color:var(--ir-blue);}
.ir-link-chip button{background:none;border:none;cursor:pointer;color:var(--ir-muted);padding:0;line-height:1;font-size:14px;display:flex;align-items:center;}
.ir-link-chip button:hover{color:#E24B4A;}
.ir-link-empty{font-size:11px;color:var(--ir-muted);padding:4px 2px;}
.ir-link-label{font-size:11px;font-weight:600;color:var(--ir-mid);margin-bottom:5px;margin-top:8px;}
.ir-link-search-row{display:flex;gap:7px;margin-bottom:8px;}
.ir-link-search-row input{flex:1;}
.ir-link-results{border-collapse:collapse;width:100%;font-size:11px;}
.ir-link-results th{background:var(--ir-blue);color:#fff;font-weight:500;padding:5px 8px;text-align:left;border-bottom:2px solid var(--ir-gold);}
.ir-link-results td{padding:6px 8px;border-bottom:1px solid var(--ir-border);vertical-align:middle;}
.ir-link-results tbody tr:hover td{background:var(--ir-row-odd);}
.ir-link-no{font-family:var(--ir-mono);font-weight:600;color:var(--ir-blue);}
.ir-link-muted{color:var(--ir-muted);}
.ir-lvl{display:inline-block;font-size:10px;font-weight:700;border-radius:3px;padding:1px 5px;}
.ir-lvl-0{background:#F3F4F6;color:#6B7280;} .ir-lvl-1{background:#E8F5EE;color:#0F6E4E;}
.ir-lvl-2{background:#EAF2FB;color:#0C447C;} .ir-lvl-3{background:#FAEEDA;color:#854F0B;}
.ir-lvl-4{background:#FCEBEB;color:#A32D2D;}
/* ── Option B: Slide-over drawer ── */
.ir-drawer-wrap{position:relative;}
.ir-drawer{position:absolute;top:0;right:0;width:340px;background:var(--ir-white);border:1px solid var(--ir-border);border-radius:8px;box-shadow:0 4px 24px rgba(0,30,80,.14);z-index:200;overflow:hidden;display:none;}
.ir-drawer.open{display:block;}
.ir-drawer-head{background:var(--ir-blue);border-bottom:3px solid var(--ir-gold);padding:9px 13px;display:flex;align-items:center;justify-content:space-between;}
.ir-drawer-head h4{font-size:12px;font-weight:600;color:#fff;margin:0;}
.ir-drawer-close{background:none;border:none;color:rgba(255,255,255,.7);cursor:pointer;font-size:18px;line-height:1;padding:0;}
.ir-drawer-close:hover{color:var(--ir-gold);}
.ir-drawer-body{padding:10px 12px;max-height:300px;overflow-y:auto;}
.ir-drawer-foot{padding:8px 12px;border-top:1px solid var(--ir-border);background:var(--ir-bg);display:flex;align-items:center;justify-content:space-between;}
.ir-filter-tabs{display:flex;gap:4px;margin-bottom:8px;}
.ir-filter-tab{font-size:11px;font-weight:500;padding:3px 9px;border-radius:4px;border:1px solid var(--ir-border);background:var(--ir-white);color:var(--ir-mid);cursor:pointer;}
.ir-filter-tab.active{background:var(--ir-blue);color:#fff;border-color:var(--ir-blue);}
.ir-result-count{font-size:11px;color:var(--ir-muted);}
</style>

<!-- JS: verbatim from original — no changes -->
<script language='javascript' src='js/jquery-1.10.2.min.js'></script>
<script language='javascript' src='ajax.js'></script>
<script language='javascript'>
function openLink(){
	window.open("link_incident.php","_blank");
}

function scrollCat(){
	var problemType=document.getElementById('type').value;
	var category=document.getElementById('category').value;
	if(problemType=="rolling"){
		makeajax("processing.php?scrollRolling="+problemType+"&category="+category,"scrollRolling");	
	}
	else if(problemType=="power"){
		makeajax("processing.php?scrollRolling="+problemType+"&category="+category,"scrollRolling");	
	}
	else {
		makeajax("processing.php?scrollRolling="+problemType,"scrollRolling");	
	}
}

function scrollType(element){
	var problemType=document.getElementById('type').value;

	if($("#type").find("option:selected").data("incident_type")==""){
	}
	else {
		$('#incident_suffix').val($("#type").find("option:selected").data("incident_type"));
	}

	var rollingHTML="";
	
	if((problemType=="rolling")||(problemType=="unload")){
		document.getElementById('index_id').disabled=false;
		document.getElementById('car_id').disabled=false;
	}
	else {
		if(problemType=="power"){
			rollingHTML+="<select id='category' name='category' onchange='scrollCat()'>";
			rollingHTML+="<option value='OCS'>Overhead Catenary System</option>";
			rollingHTML+="<option value='SS'>Station Substation</option>";
			rollingHTML+="<option value='TPSS'>Traction Power Substation Equipment</option>";
			rollingHTML+="</select>";	
			document.getElementById('rolling_category').innerHTML=rollingHTML;
		}
		else {
			document.getElementById('rolling_category').innerHTML=rollingHTML;
		}
		document.getElementById('index_id').disabled=true;
		document.getElementById('car_id').disabled=true;
	}
	
	var category="";
	var equiptHTML="";
	
	if(problemType=="rolling"){
		makeajax("processing.php?scrollRolling="+problemType,"scrollRolling");	
	}
	else if(problemType=="power"){
		category=document.getElementById('category').value;
		makeajax("processing.php?scrollRolling="+problemType+"&category="+category,"scrollRolling");	
	}
	else if(problemType=="others"){
		makeajax("processing.php?scrollOthers="+problemType,"scrollRolling");	
	}
	else {
		makeajax("processing.php?scrollRolling="+problemType,"scrollRolling");	
	}

	if(problemType=="cc_equipt"){
		equiptHTML="<input type='text' name='cc_equipt' id='cc_equipt' />";
		document.getElementById('equipment_space').innerHTML=equiptHTML;
	}
	else if(problemType=="station_equipt"){
		equiptHTML="<input type='text' name='station_equipt' id='station_equipt' />";
		document.getElementById('equipment_space').innerHTML=equiptHTML;
	}
	else if(problemType=="depot_equipt"){
		equiptHTML="<input type='text' name='depot' id='depot' />";
		document.getElementById('equipment_space').innerHTML=equiptHTML;
	}
	else {
		document.getElementById('equipment_space').innerHTML="";
	}

	if(problemType=="afc"){
		equiptHTML="<input type='text' name='unit_no' id='unit_no' class='ir-input--xs' />";
		document.getElementById('unit_space').innerHTML=equiptHTML;
	}
}

function subItemScroll(){
	var problemType=document.getElementById('equipment').value;
	if(problemType=="others"){
		var innerHTML="<input type='text' name='otherEquipment' />";
		document.getElementById('equipment_space').innerHTML=innerHTML;
	}
	else {
		makeajax("processing.php?scrollSubItem="+problemType,"subItem");	
	}
}

function subItem(ajaxHTML){
	var subHTML="";
	if(ajaxHTML!="No data available"){
		var subItemTerms=ajaxHTML.split("==>");
		var count=(subItemTerms.length)*1-1;
		subHTML="<select id='subitem' name='subitem'><option></option>";
		for(var n=0;n<count;n++){
			var parts=subItemTerms[n].split(";");
			subHTML+="<option value='"+parts[0]+"'>"+parts[1]+"</option>";
		}
		subHTML+="</select>";
	}
	document.getElementById('sub_item_space').innerHTML=subHTML;
}

function scrollRolling(ajaxHTML){
	var rollingHTML="<option></option>";
	if(ajaxHTML!="No data available"){
		var equipmentTerms=ajaxHTML.split("==>");
		var count=(equipmentTerms.length)*1-1;
		for(var n=0;n<count;n++){
			var parts=equipmentTerms[n].split(";");
			rollingHTML+="<option value='"+parts[0]+"'>"+parts[1]+"</option>";
		}
	}
	document.getElementById('equipment').innerHTML=rollingHTML;	
	document.getElementById('sub_item_space').innerHTML="";	
}

function getMore(cancel){
	if(cancel=="more"){ document.getElementById('cancel_more').disabled=false; }
	else { document.getElementById('cancel_more').disabled=true; }
}

function getLevel(element){
	var level=element.value;
	var conditionHTML="";
	if(level==3){
		conditionHTML+="<select name='condition'>";
		conditionHTML+="<option></option>";
		conditionHTML+="<option value='1'>Train is removed without replacement</option>";
		conditionHTML+="<option value='2'>Cancellation of loops and insertion</option>";
		conditionHTML+="<option value='5'>With Passenger Unloading</option>";
		conditionHTML+="</select>";
	}
	else if(level==4){
		conditionHTML+="<select name='condition'>";
		conditionHTML+="<option></option>";
		conditionHTML+="<option value='3'>Service interruption</option>";
		conditionHTML+="<option value='4'>Cancellation of loops. Ticket refunds.</option>";
		conditionHTML+="</select>";
	}
	document.getElementById('condition').innerHTML=conditionHTML;
}

function changeDirection(element){ var direction=element.value; }

function setPreset(check){
	var remarksHTML="";
	if(check.checked){
		remarksHTML="<select name='dotc_coordinated' id='dotc_coordinated'>";
		remarksHTML+="<option>Coordinated with</option>";
		remarksHTML+="<option>Coordinated to</option>";
		remarksHTML+="</select>";
		remarksHTML+="<input type=text name='coordinated_to' id='coordinated_to' />";
	}
	else {
		remarksHTML="<textarea rows=5 cols=50 name='dotc'></textarea>";
	}
	document.getElementById('remarks_space').innerHTML=remarksHTML;
}

function addCoordinate(){
	var coordinate=document.getElementById('dotc_coordinated').value;
	var remarksValue=document.getElementById('dotc').value;
	var additional="";
	if(coordinate=="c_with"){ additional="Coordinated with "+document.getElementById('coordinated_to').value+"."; }
	else if(coordinate=="c_to"){ additional="Coordinated to "+document.getElementById('coordinated_to').value+"."; }
	else if(coordinate=="reinitialize"){ additional="Re-initialized, ok."; }
	else if(coordinate=="recorded"){ additional="Recorded."; }
	document.getElementById('dotc').value=remarksValue+" "+additional;	
}

function activateMultiple(){
	var multipleSignal=document.getElementById('multipleFlag');
	if(multipleSignal.checked){
		var multipleTable="<table name='multi_list' id='multi_list' width=80%></table>";
		multipleTable+="<a href='#' onclick=\"window.open('multiple_defects.php?problemType=RS')\">Update</a>";	
		document.getElementById('multiple_space').innerHTML=multipleTable;
	}
	else {
		document.getElementById('multiple_space').innerHTML="";
	}
}

function retrieveDefects(){
	makeajax("processing.php?retrieveAdditional=Y","getAdditional");	
}

function getAdditional(ajaxHTML){
	var subHTML="";
	if(ajaxHTML!="No data available"){
		var subItemTerms=ajaxHTML.split(";");
		var count=(subItemTerms.length)*1-1;
		subHTML="<tr><th>Equipment</th><th>Sub-item</th></tr>";
		for(var n=0;n<count;n++){
			var parts=subItemTerms[n].split(",");
			subHTML+="<tr><td>"+parts[0]+"</td><td>"+parts[1]+"</td></tr>";
		}
	}
	document.getElementById('multi_list').innerHTML=subHTML;
}

function checkIncidentNo(element){
	var year=$('#year').val();
	var incident_no=element.value;
	$.ajax({url:"processing.php?checkIncidentNo="+incident_no+"&year="+year,success:function(result){
		confirmIncidentNo(result);
	}});
}

function confirmIncidentNo(ajaxHTML){
	if(ajaxHTML=="No number"){}
	else {}
}

/* ── Shared incident-linking helpers ───────────────────────────────────── */
var irLinked={};

function irLvlBadge(l){ return '<span class="ir-lvl ir-lvl-'+l+'">L'+l+'</span>'; }

function irSearchIncidents(q,cb){
	var stub=[
		{id:1042,no:"2024-1042 RS",type:"Rolling Stock",level:3,date:"2026-06-30",index_no:"23",description:"Door fault"},
		{id:1041,no:"2024-1041 PWR",type:"Power",level:4,date:"2026-06-30",index_no:"",description:"OCS voltage dip"},
		{id:1039,no:"2024-1039 RS",type:"Rolling Stock",level:2,date:"2026-06-30",index_no:"31",description:"ATP intervention"},
		{id:1036,no:"2024-1036 SIG",type:"Signaling",level:2,date:"2026-06-29",index_no:"",description:"ATC fault Shaw"},
		{id:1030,no:"2024-1030 RS",type:"Rolling Stock",level:3,date:"2026-06-29",index_no:"18",description:"Traction motor"},
		{id:1027,no:"2024-1027 COM",type:"Communication",level:1,date:"2026-06-28",index_no:"",description:"Radio failure"},
		{id:1025,no:"2024-1025 TRK",type:"Tracks",level:2,date:"2026-06-28",index_no:"",description:"Track circuit"},
		{id:1018,no:"2024-1018 RS",type:"Rolling Stock",level:4,date:"2026-06-27",index_no:"09",description:"Emergency unload"}
	];
	var r=q?stub.filter(function(x){
		var s=(x.no+x.type+x.description).toLowerCase();
		return s.indexOf(q.toLowerCase())>=0;
	}):stub;
	cb(r);
}

function irAddChip(id,label){
	id=String(id);
	if(irLinked[id]) return;
	irLinked[id]=label;
	var chips=document.getElementById('ir-chips');
	var empty=chips.querySelector('.ir-link-empty');
	if(empty) chips.removeChild(empty);
	var chip=document.createElement('span');
	chip.className='ir-link-chip'; chip.id='ir-chip-'+id;
	chip.innerHTML=label+"<button type=\"button\" onclick=\"irRemoveChip('"+id+"')\" title=\"Remove\">&times;</button>";
	chips.appendChild(chip);
	irSyncHidden();
}

function irRemoveChip(id){
	var el=document.getElementById('ir-chip-'+id);
	if(el) el.remove();
	delete irLinked[id];
	var chips=document.getElementById('ir-chips');
	if(!chips.querySelector('.ir-link-chip'))
		chips.innerHTML='<span class="ir-link-empty">No incidents linked yet</span>';
	irSyncHidden();
}

function irSyncHidden(){
	var ids=Object.keys(irLinked);
	document.getElementById('ir-links-hidden').value=ids.join(',');
	document.getElementById('incident_link').value=ids.length?ids[0]:'';
	document.getElementById('incident_no_link').value=ids.length?Object.values(irLinked)[0]:'';
}
/* ── Option B specifics ── */
var bSelected={};
var bTabFilter='all';

function bToggleDrawer(){
	var d=document.getElementById('b-drawer');
	d.classList.toggle('open');
	if(d.classList.contains('open')) bSearch('');
}

function bSetTab(btn,key){
	document.querySelectorAll('.ir-filter-tab').forEach(function(t){t.classList.remove('active');});
	btn.classList.add('active');
	bTabFilter=key;
	bSearch(document.getElementById('b-drawer-input').value);
}

function bSearch(q){
	irSearchIncidents(q,function(data){
		var filtered=data.filter(function(r){
			if(bTabFilter==='all') return true;
			if(bTabFilter==='today') return r.date==='<?php echo date("Y-m-d"); ?>';
			return r.type.toLowerCase().indexOf(bTabFilter)>=0;
		});
		bRenderList(filtered);
	});
}

function bRenderList(data){
	var html='<thead><tr>'
		+'<th style="width:24px"></th><th>Incident No.</th><th>Type</th><th>Date</th>'
		+'</tr></thead><tbody>';
	data.forEach(function(r){
		var chk=bSelected[String(r.id)]?'checked':'';
		html+='<tr>'
			+'<td><input type="checkbox" value="'+r.id+'" '+chk
			+" onchange=\"bToggle('"+r.id+"','"+r.no+"',this.checked)\""
			+' style="accent-color:var(--ir-blue)"></td>'
			+'<td class="ir-link-no">'+r.no+'</td>'
			+'<td>'+r.type+'</td>'
			+'<td class="ir-link-muted">'+r.date+'</td>'
			+'</tr>';
	});
	html+='</tbody>';
	document.getElementById('b-results').innerHTML=html;
	document.getElementById('b-count').textContent=data.length+' result'+(data.length===1?'':'s');
}

function bToggle(id,no,checked){
	id=String(id);
	if(checked) bSelected[id]=no; else delete bSelected[id];
}

function bAddSelected(){
	Object.keys(bSelected).forEach(function(id){ irAddChip(id,bSelected[id]); });
	bToggleDrawer();
	bSelected={};
}
</script>

<body>
<div class="ir-page">

	<!-- Page header -->
	<div class="ir-page-header">
		<span class="ir-wordmark">LINE 3</span>
		<h1>Incident Report</h1>
		<span class="ir-context">
			<?php if(isset($_GET['cancel'])){ echo "Cancelled departure"; } elseif(isset($_GET['add_incident'])){ echo "Add incident to train"; } ?>
		</span>
	</div>

	<div class="ir-form-body">
	<form action='incident report.php<?php if(isset($_GET['cancel'])){ echo "?cancel=".$_GET['cancel']; } else if(isset($_GET['add_incident'])){ echo "?add_incident=".$_GET['add_incident']; } ?>' method='post'>

	<!-- ═══════════════════════════════════════════
	     SECTION 1: Incident Details
	     ═══════════════════════════════════════════ -->
	<div class="ir-section-head">Incident Details</div>
	<table class="ir-table">

	<!-- Problem Category -->
	<tr>
		<td class="ir-label">Problem Category</td>
		<td class="ir-field">
			<select name='type' id='type' class="ir-sel--full" onchange='scrollType(this)'>
				<option data-incident_type="RS"  value='rolling' <?php if((isset($_GET['cancel']))||(isset($_GET['add_incident']))){ echo "selected"; } ?>>Rolling Stock</option>
				<option data-incident_type="CEQ" value='cc_equipt'>CC Equipment</option>
				<option data-incident_type="COM" value='communication'>Communication</option>
				<option data-incident_type="DEQ" value='depot_equipt'>Depot Equipment</option>
				<option data-incident_type="PWR" value='power'>Power</option>
				<option data-incident_type="SIG" value='signaling'>Signaling</option>
				<option data-incident_type="TRK" value='tracks'>Tracks</option>
				<option data-incident_type="AFC" value='afc'>AFC Equipment</option>
				<option data-incident_type="SEQ" value='station_equipt'>Station Equipment</option>
				<option data-incident_type="a"   value='gradual'>Gradual Removal</option>
				<option data-incident_type="a"   value='c_loops'>Cancelled Loops; Acc. Delay/Failure</option>
				<option data-incident_type="a"   value='r_trains'>Running Trains</option>
				<option data-incident_type="RS"  value='unload'>Unloading of Passengers</option>
				<option data-incident_type="RS"  value='nload'>Not Loading</option>
				<option value='others'>Others</option>
			</select>
			<span id='rolling_category' name='rolling_category'></span>
		</td>
	</tr>

	<!-- Equipment -->
	<tr>
		<td class="ir-label">Equipment / Train Unavailability</td>
		<td class="ir-field">
			<select name='equipment' id='equipment' class="ir-sel--full" onchange='subItemScroll()'>
				<option></option>
				<?php 
				$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
				$sql="select * from equipment where type='RS' order by equipment_name";
				$rs=$db->query($sql);
				$nm=$rs->num_rows;
				for($i=0;$i<$nm;$i++){
					$row=$rs->fetch_assoc();
				?>
				<option value='<?php echo $row['id']; ?>'><?php echo $row['equipment_name']; ?></option>
				<?php } ?>
				<option value='others'>OTHERS</option>
			</select>
			<span name='equipment_space' id='equipment_space'></span>
			<span id='sub_item_space' name='sub_item_space'></span>
			<span id='unit_space' name='unit_space'></span>
		</td>
	</tr>

	<!-- Additional Defects -->
	<tr>
		<td class="ir-label">Additional Defects</td>
		<td class="ir-field">
			<label class="ir-check-row">
				<input type="checkbox" name='multipleFlag' id='multipleFlag' onclick='activateMultiple()' />
				Multiple defects
			</label>
			<span id='multiple_space' name='multiple_space'></span>
		</td>
	</tr>

	<!-- Link Incident Report — Option B: Slide-over drawer -->
	<tr>
		<td class="ir-label ir-label--top" style="padding-top:11px">Link Incident Reports</td>
		<td class="ir-field" style="padding-top:9px">
			<div class="ir-drawer-wrap">

				<!-- Trigger row -->
				<div style="display:flex;gap:7px;margin-bottom:6px;">
					<input type='button' value='Browse incidents' onclick='bToggleDrawer()'
						style="background:var(--ir-blue);color:#fff;border-color:var(--ir-blue);" />
					<input type='button' value='Popup (original)' onclick='openLink()' style="opacity:.6" />
				</div>

				<!-- Drawer -->
				<div class="ir-drawer" id="b-drawer">
					<div class="ir-drawer-head">
						<h4><i class="ti ti-link" style="margin-right:5px"></i>Link incident reports</h4>
						<button class="ir-drawer-close" type="button" onclick="bToggleDrawer()">&times;</button>
					</div>
					<div class="ir-drawer-body">
						<div class="ir-filter-tabs">
							<button class="ir-filter-tab active" type="button" onclick="bSetTab(this,'all')">All</button>
							<button class="ir-filter-tab" type="button" onclick="bSetTab(this,'today')">Today</button>
							<button class="ir-filter-tab" type="button" onclick="bSetTab(this,'rolling')">Rolling Stock</button>
							<button class="ir-filter-tab" type="button" onclick="bSetTab(this,'power')">Power</button>
						</div>
						<div style="display:flex;gap:6px;margin-bottom:8px;">
							<input type='text' id='b-drawer-input' class="ir-input--lg"
								placeholder="Search incident no. or type…"
								oninput='bSearch(this.value)' autocomplete="off" />
						</div>
						<table class="ir-link-results" id="b-results"></table>
					</div>
					<div class="ir-drawer-foot">
						<span class="ir-result-count" id="b-count"></span>
						<input type='button' value='Add selected ✓' onclick='bAddSelected()'
							style="background:var(--ir-blue);color:#fff;border-color:var(--ir-blue);" />
					</div>
				</div><!-- /.ir-drawer -->

			</div><!-- /.ir-drawer-wrap -->

			<!-- Linked chips -->
			<div class="ir-link-label">Linked incidents</div>
			<div class="ir-link-chips" id="ir-chips">
				<span class="ir-link-empty">No incidents linked yet</span>
			</div>

			<!-- Hidden fields -->
			<input type='hidden' name='incident_links' id='ir-links-hidden' value=''>
			<input type='hidden' name='incident_link'  id='incident_link' value=''>
			<input type='text'   name='incident_no_link' id='incident_no_link' style='display:none' />

		</td>
	</tr>


	<!-- Index No. / Car No. -->
	<tr>
		<td class="ir-label">Index No. / Car No.</td>
		<td class="ir-field">
			<?php
			$retrieve_id="";
			if(isset($_GET['cancel']))      { $retrieve_id=$_GET['cancel']; }
			if(isset($_GET['add_incident'])){ $retrieve_id=$_GET['add_incident']; }

			$index_id="";
			if($retrieve_id!=""){
				$sql="select * from train_availability where id='".$retrieve_id."'";
				$rs=$db->query($sql);
				$row=$rs->fetch_assoc();
				$index_id=$row['index_no'];
				$switchSQL="select * from train_switch where train_ava_id='".$retrieve_id."' order by date_change desc";
				$switchRS=$db->query($switchSQL);
				if($switchRS->num_rows>0){
					$switchRow=$switchRS->fetch_assoc();
					$index_id=$switchRow['new_index'];
				}
			}
			?>
			<div class="ir-inline">
				<input name='index_id' id='index_id' type='text' class="ir-input--xs" value='<?php echo $index_id; ?>' />
				<span class="ir-sep">/</span>
				<?php
				$car_fields = ['car_id','car_id_2','car_id_3','car_id_4'];
				foreach($car_fields as $idx => $field_name){
					echo "<select name='".$field_name."' id='".$field_name."' class='ir-sel--time'>";
					echo "<option></option>";
					if($retrieve_id!=""){
						$sql="select * from train_availability where id='".$retrieve_id."'";
						$rs=$db->query($sql);
						$row=$rs->fetch_assoc();
						$selected = ($idx==0) ? " selected" : "";
						echo "<option".$selected." value='".$row['car_a']."'>".$row['car_a']."</option>";
						echo "<option value='".$row['car_b']."'>".$row['car_b']."</option>";
						if($row['car_c']) echo "<option value='".$row['car_c']."'>".$row['car_c']."</option>";
						if($row['car_d']) echo "<option value='".$row['car_d']."'>".$row['car_d']."</option>";
					}
					echo "</select>";
					if($idx < count($car_fields)-1) echo "<span class='ir-sep'>,</span>";
				}
				?>
			</div>
		</td>
	</tr>

	<!-- Cancelled Loop -->
	<tr>
		<td class="ir-label">Cancelled Loop</td>
		<td class="ir-field ir-inline">
			<select name='cancel' id='cancel' onchange='getMore(this.value)'>
				<option value='none'>0</option>
				<option value='whole'>1</option>
				<option value='half'>1/2</option>
				<option value='more'>More than 1</option>
			</select>
			<input type='text' name='cancel_more' id='cancel_more' class="ir-input--xs" disabled />
		</td>
	</tr>

	<!-- Incident No. -->
	<tr>
		<td class="ir-label">Incident No.</td>
		<td class="ir-field ir-inline">
			<input type='text' name='incident_no' id='incident_no' class="ir-input--sm" onblur='checkIncidentNo(this)' />
			<select name='incident_suffix' id='incident_suffix' class="ir-sel--suffix">
				<?php
				$sql_suffix="SELECT * FROM equipment_type where sequence is not null order by sequence";
				$rs_suffix=$db->query($sql_suffix);
				$nm=$rs_suffix->num_rows;
				for($i=0;$i<$nm;$i++){
					$row=$rs_suffix->fetch_assoc();
					echo "<option value='".$row['incident_code']."'>".$row['incident_code']."</option>";
				}
				?>
			</select>
		</td>
	</tr>

	<!-- Location / Direction -->
	<tr>
		<td class="ir-label">Location / Direction</td>
		<td class="ir-field ir-inline">
			<select name='direction'>
				<option></option>
				<option value='S'>Station</option>
				<option value='D'>Depot</option>
				<option value='ML'>Mainline</option>
				<option value='CC'>Control Center</option>
				<option value='NB'>Northbound</option>
				<option value='SB'>Southbound</option>
				<option value='NTB'>North Turnback</option>
				<option value='IR'>Insertion/Removal Area</option>
				<option value='SPT'>Shaw Pocket Track</option>
				<option value='TPT'>Taft Pocket Track</option>
			</select>
			<input type='text' name='location' id='location' class="ir-input--sm" placeholder="Station/location" />
		</td>
	</tr>

	<!-- Level -->
	<tr>
		<td class="ir-label">Level</td>
		<td class="ir-field ir-inline">
			<select name='level' id='level' onchange='getLevel(this)'>
				<option value='0'>0</option>
				<option value='1'>1</option>
				<option value='2'>2</option>
				<option value='3'>3</option>
				<option value='4'>4</option>
			</select>
			<span id='condition' name='condition'></span>
		</td>
	</tr>

	<!-- Date -->
	<tr>
		<td class="ir-label">Date</td>
		<td class="ir-field">
			<?php
			if(isset($_SESSION['month'])){
				$incident_date_label=date("m/d/Y",strtotime($_SESSION['year']."-".$_SESSION['month']."-".$_SESSION['day']));
			} else {
				$incident_date_label=date("m/d/Y");
			}
			?>
			<input type='text' name='incident_date' id='incident_date' class='datepicker ir-input--md' value='<?php echo $incident_date_label; ?>' />
		</td>
	</tr>

	<!-- Time -->
	<tr>
		<td class="ir-label">Time</td>
		<td class="ir-field ir-inline">
			<select name='hour' class="ir-sel--time">
				<?php for($i=1;$i<=12;$i++){ ?>
				<option value='<?php echo $i; ?>' <?php if($i*1==$hh*1){ echo "selected"; } ?>><?php echo $i; ?></option>
				<?php } ?>
			</select>
			<select name='minute' class="ir-sel--time">
				<?php for($i=0;$i<=59;$i++){ ?>
				<option value='<?php echo $i; ?>' <?php if($i*1==$min*1){ echo "selected"; } ?>><?php echo ($i<10?"0":"").$i; ?></option>
				<?php } ?>
			</select>
			<select name='amorpm' class="ir-sel--time">
				<option value='am' <?php if($aa=="am"){ echo "selected"; } ?>>AM</option>
				<option value='pm' <?php if($aa=="pm"){ echo "selected"; } ?>>PM</option>
			</select>
		</td>
	</tr>

	<!-- Type of Action -->
	<tr>
		<td class="ir-label">Type of Action</td>
		<td class="ir-field">
			<input type='text' name='action_type' class="ir-input--lg" />
		</td>
	</tr>

	<!-- Incident Duration -->
	<tr>
		<td class="ir-label">Incident Duration</td>
		<td class="ir-field">
			<input type='text' name='duration' class="ir-input--md" placeholder="e.g. 00:15" />
		</td>
	</tr>

	<!-- Details -->
	<tr>
		<td class="ir-label ir-label--top">Details</td>
		<td class="ir-field ir-field--top">
			<textarea rows='5' name='description' id='typeahead'
				class="span6 typeahead"
				data-provide="typeahead" data-items="4"
				data-source='[""
				<?php
				$db2=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_external");
				$sql="select * from preencoded";
				$rs=$db2->query($sql);
				$nm=$rs->num_rows;
				for($i=0;$i<$nm;$i++){
					$row=$rs->fetch_assoc();
					echo ',"'.$row['content'].'"';
				}
				?>
				]'></textarea>
		</td>
	</tr>

	</table>

	<!-- ═══════════════════════════════════════════
	     SECTION 2: Reporting
	     ═══════════════════════════════════════════ -->
	<div class="ir-section-head">Reporting</div>
	<table class="ir-table">

	<!-- Reported By -->
	<tr>
		<td class="ir-label">Reported By</td>
		<td class="ir-field">
			<input type='text' autocomplete='off' name='reported_by' id='reported_by'
				class="ir-input--lg span6 typeahead"
				data-provide="typeahead" data-items="4"
				data-source='[
				<?php
				$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
				$sql="select * from train_driver order by lastName";
				$rs=$db->query($sql);
				$nm=$rs->num_rows;
				for($i=0;$i<$nm;$i++){
					$row=$rs->fetch_assoc();
					$comma=($i==0)?"":",";;
					echo $comma.'"'.$row['position']." ".substr($row['firstName'],0,1).". ".$row['lastName'].'"';
				}
				?>
				]' />
		</td>
	</tr>

	<!-- Received By -->
	<tr>
		<td class="ir-label">Received By</td>
		<td class="ir-field">
			<select name='received_by' id='received_by' class="ir-sel--full">
				<?php
				$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
				$sql="select * from train_driver where position in ('STDO','CCRE') order by lastName";
				$rs=$db->query($sql);
				$nm=$rs->num_rows;
				for($i=0;$i<$nm;$i++){
					$row=$rs->fetch_assoc();
					$sel=($row['id']==$_SESSION['recording'])?" selected":"";
					echo "<option value='".$row['id']."'".$sel.">".$row['lastName'].", ".$row['firstName']."</option>";
				}
				?>
			</select>
		</td>
	</tr>

	<!-- Recommending Approval -->
	<tr>
		<td class="ir-label">Recommending Approval</td>
		<td class="ir-field">
			<input type='text' name='recommending_approval' class="ir-input--lg" />
		</td>
	</tr>

	<!-- Approving Person -->
	<tr>
		<td class="ir-label">Approving Person</td>
		<td class="ir-field">
			<input type='text' name='approving_person' class="ir-input--lg" />
		</td>
	</tr>

	</table>

	<!-- ═══════════════════════════════════════════
	     SECTION 3: Action Taken
	     ═══════════════════════════════════════════ -->
	<div class="ir-section-head">Action Taken</div>
	<table class="ir-table">

	<!-- DOTR -->
	<tr>
		<td class="ir-label ir-label--top">DOTR</td>
		<td class="ir-field ir-field--top">
			<span name='remarks_space' id='remarks_space'>
				<textarea rows='5' name='dotc' id='dotc'
					class="span6 typeahead"
					data-provide="typeahead" data-items="4"
					data-source='[""
					<?php
					$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_external");
					$sql="select * from preencoded";
					$rs=$db2->query($sql);
					$nm=$rs->num_rows;
					for($i=0;$i<$nm;$i++){
						$row=$rs->fetch_assoc();
						echo ',"'.$row['content'].'"';
					}
					?>
					]'></textarea>
			</span>
		</td>
	</tr>

	<!-- Verified -->
	<tr>
		<td class="ir-label ir-label--top">Verified</td>
		<td class="ir-field ir-field--top">
			<textarea rows='5' name='maintenance' id='maintenance'
				class="span6 typeahead"
				data-provide="typeahead" data-items="4"
				data-source='[""
				<?php
				$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_external");
				$sql="select * from preencoded";
				$rs=$db2->query($sql);
				$nm=$rs->num_rows;
				for($i=0;$i<$nm;$i++){
					$row=$rs->fetch_assoc();
					echo ',"'.$row['content'].'"';
				}
				?>
				]'></textarea>
		</td>
	</tr>

	</table>

	<!-- Submit footer -->
	<div class="ir-submit-row">
		<input type='submit' value='Submit Incident Report' />
	</div>

	</form>
	</div><!-- /.ir-form-body -->

</div><!-- /.ir-page -->

<script src="js/jquery-1.10.2.min.js"></script>
<script src="js/jquery-migrate-1.2.1.min.js"></script>
<script src="js/jquery-ui-1.10.3.custom.min.js"></script>
<script src="js/jquery.ui.touch-punch.js"></script>
<script src="js/modernizr.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/additional2.js"></script>
<script src="js/date.js"></script>
<script>
$(function(){
	$('.datepicker').datepicker({changeMonth:true,changeYear:true,showAnim:"clip"});
});
</script>
</body>