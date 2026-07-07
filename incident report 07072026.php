<?php
session_start();
?>
<?php
ini_set("date.timezone","Asia/Kuala_Lumpur");
/* embed=1 -> page is hosted inside the train_operations slide-panel iframe.
   Tmenu.php still runs (it provides $db / session / auth side effects) but
   its printed chrome is captured and discarded so only the incident form
   shows inside the panel. Opened standalone (no embed), nothing changes. */
$IR_EMBED = isset($_GET['embed']);
if($IR_EMBED){ ob_start(); }
require("Tmenu.php");
if($IR_EMBED){ ob_end_clean(); }
?>
<?php
/* =========================================================================
   incident_report_option_c.php
   Link option: Full modal overlay (Option C)
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

	/* ── Multi-equipment-with-subitem handler ─────────────────────────────
	   Writes each selected equipment item — and whichever sub-item was
	   chosen for it, if any — to incident_equipment.

	   $_POST['equipment_ids'] arrives as a comma-separated list of
	   "equipt_id:subitem_id" pairs, e.g. "104:7,108:,112:3" — the empty
	   subitem_id on the second pair means that item's sub-item dropdown
	   either had no data or the user hadn't picked one yet; it's still
	   recorded as an equipment selection with subitem_id left at 0.

	   The legacy $_POST['equipment'] / $_POST['subitem'] single pair still
	   writes into incident_description.equipt/subitem exactly as before,
	   completely untouched by this block.

	   Required DDL — note this widens the table from the previous turn's
	   version by one column; if incident_equipment already exists without
	   subitem_id, run the ALTER instead of the CREATE:
	     CREATE TABLE incident_equipment (
	       id int AUTO_INCREMENT PRIMARY KEY,
	       incident_id int NOT NULL,
	       equipt_id   int NOT NULL,
	       subitem_id  int NOT NULL DEFAULT 0,
	       UNIQUE KEY uq_pair (incident_id, equipt_id)
	     );
	     -- or, if the table from before already exists:
	     ALTER TABLE incident_equipment ADD COLUMN subitem_id int NOT NULL DEFAULT 0;
	   ─────────────────────────────────────────────────────────────────── */
	if(!empty($_POST['equipment_ids'])){
		$pairs=array_filter(is_array($_POST['equipment_ids'])
			? $_POST['equipment_ids']
			: explode(',',$_POST['equipment_ids']));
		foreach($pairs as $pair){
			$pair=trim($pair);
			if($pair==='') continue;
			$parts=explode(':',$pair);
			$equipt_id =(int)trim($parts[0]);
			$subitem_id=isset($parts[1]) ? (int)trim($parts[1]) : 0;
			if($equipt_id<=0) continue;
			$db->query("insert ignore into incident_equipment(incident_id,equipt_id,subitem_id) values ('".$incident_code."','".$equipt_id."','".$subitem_id."')");
		}
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
		if($IR_EMBED){ echo "<script>parent.postMessage('ir-saved','*');</script>"; }
		else { echo "<script language='javascript'>window.opener.location='train_availability.php';</script>"; }
	}
	if(isset($_GET['add_incident'])){
		$sql="insert into train_incident_report(train_ava_id,incident_id) values ('".$_GET['add_incident']."','".$incident_code."')";
		$rs=$db->query($sql);
		if(isset($_POST['cancel'])){
			$sql="update train_ava_time set cancel_loop='".$cancel."' where train_ava_id='".$_GET['cancel']."'";
			$rs=$db->query($sql);
		}
		if($IR_EMBED){ echo "<script>parent.postMessage('ir-saved','*');</script>"; }
		else { echo "<script language='javascript'>window.opener.location='train_availability.php';</script>"; }
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

/* ── Multi-equipment picker (Option A pattern, applied to Equipment field) ── */
.ir-eq-panel{border:1px solid var(--ir-border);border-radius:6px;overflow:hidden;margin-top:8px;}
.ir-eq-panel-head{background:var(--ir-row-odd);padding:7px 11px;font-size:11px;font-weight:600;color:var(--ir-mid);border-bottom:1px solid var(--ir-border);display:flex;align-items:center;gap:7px;}
.ir-eq-panel-body{max-height:200px;overflow-y:auto;}
.ir-eq-panel-foot{padding:7px 11px;border-top:1px solid var(--ir-border);background:var(--ir-bg);display:flex;justify-content:flex-end;gap:7px;}
.ir-eq-cb-row{display:flex;align-items:center;gap:8px;padding:6px 10px;cursor:pointer;border-bottom:1px solid var(--ir-border);}
.ir-eq-cb-row:last-child{border-bottom:none;}
.ir-eq-cb-row:hover{background:var(--ir-row-odd);}
.ir-eq-cb-row input[type=checkbox]{accent-color:var(--ir-blue);width:14px;height:14px;cursor:pointer;flex-shrink:0;}
.ir-eq-cb-row .ir-eq-name{font-size:12px;color:var(--ir-dark);flex:1;}
.ir-eq-cb-row .ir-eq-cat{font-size:10px;color:var(--ir-muted);white-space:nowrap;}
.ir-eq-chips{display:flex;flex-direction:column;gap:8px;min-height:32px;padding:8px;border:1px solid var(--ir-border);border-radius:4px;background:var(--ir-bg);margin-top:8px;}
.ir-eq-card{background:var(--ir-white);border:1px solid var(--ir-border);border-radius:6px;overflow:hidden;}
.ir-eq-card-head{display:flex;align-items:center;justify-content:space-between;padding:6px 10px;background:var(--ir-row-odd);border-bottom:1px solid var(--ir-border);}
.ir-eq-card-name{font-size:12px;font-weight:600;color:var(--ir-blue);}
.ir-eq-card-head button{background:none;border:none;cursor:pointer;color:var(--ir-muted);padding:0;line-height:1;font-size:15px;display:flex;align-items:center;}
.ir-eq-card-head button:hover{color:#E24B4A;}
.ir-eq-card-sub{padding:8px 10px;}
.ir-eq-subselect{height:28px;font-size:12px;font-family:var(--ir-sans);border:1px solid var(--ir-border);background:var(--ir-white);color:var(--ir-dark);border-radius:4px;padding:0 6px;width:100%;box-sizing:border-box;}
.ir-eq-subselect:focus{border-color:var(--ir-blue);outline:none;}
.ir-eq-loading{font-size:11px;color:var(--ir-muted);font-style:italic;}
.ir-eq-no-sub{font-size:11px;color:var(--ir-muted);font-style:italic;}
.ir-eq-empty{font-size:11px;color:var(--ir-muted);padding:4px 2px;}
.ir-eq-label{font-size:11px;font-weight:600;color:var(--ir-mid);margin-bottom:5px;margin-top:8px;}
.ir-divider{border:0;border-top:1px dashed var(--ir-border);margin:10px 0;}
.ir-subtle-note{font-size:10px;color:var(--ir-muted);font-style:italic;margin-top:4px;}
/* ── Option C: Full modal overlay ── */
.ir-modal-backdrop{position:fixed;inset:0;background:rgba(16,24,40,.38);z-index:1000;display:none;align-items:center;justify-content:center;}
.ir-modal-backdrop.open{display:flex;}
.ir-modal-box{background:var(--ir-white);border-radius:10px;overflow:hidden;width:600px;max-width:96vw;max-height:82vh;display:flex;flex-direction:column;box-shadow:0 8px 40px rgba(0,30,80,.20);}
.ir-modal-head{background:var(--ir-blue);border-bottom:3px solid var(--ir-gold);padding:11px 16px;display:flex;align-items:center;justify-content:space-between;flex:none;}
.ir-modal-head h4{font-size:13px;font-weight:600;color:#fff;margin:0;}
.ir-modal-close{background:none;border:none;color:rgba(255,255,255,.7);cursor:pointer;font-size:20px;line-height:1;padding:0;}
.ir-modal-close:hover{color:var(--ir-gold);}
.ir-modal-body{padding:14px 16px;flex:1;overflow-y:auto;}
.ir-modal-foot{padding:11px 16px;border-top:1px solid var(--ir-border);background:var(--ir-bg);display:flex;align-items:center;justify-content:space-between;flex:none;}
.ir-modal-sel-count{font-size:11px;color:var(--ir-mid);font-weight:600;}
.ir-filter-tabs{display:flex;gap:4px;margin-bottom:10px;}
.ir-filter-tab{font-size:11px;font-weight:500;padding:3px 9px;border-radius:4px;border:1px solid var(--ir-border);background:var(--ir-white);color:var(--ir-mid);cursor:pointer;}
.ir-filter-tab.active{background:var(--ir-blue);color:#fff;border-color:var(--ir-blue);}
.ir-result-scroll{max-height:240px;overflow-y:auto;border:1px solid var(--ir-border);border-radius:4px;}
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

var irSearchCallback=null;

function irSearchIncidents(q,cb){
	irSearchCallback=cb;
	/* Calls processing.php?searchIncidents=  — add this case to processing.php:
	     if(isset($_GET['searchIncidents'])){
	         $q     = $db->real_escape_string($_GET['searchIncidents']);
	         $scope = isset($_GET['scope']) ? $_GET['scope'] : 'today';
	         $sql = "select incident_report.id, incident_no, incident_type, level,
	                        incident_date, level_condition
	                 from incident_report
	                 where 1=1 ";
	         if($scope=='today'){
	             $sql .= "and date(incident_date)=curdate() ";
	         }
	         if($q!=''){
	             $sql .= "and (incident_no like '%".$q."%' or incident_type like '%".$q."%') ";
	         }
	         $sql .= "order by incident_date desc";
	         if($scope!='all' && $q==''){
	             $sql .= " limit 100"; // safety cap when browsing "today" without a search term
	         }
	         $rs = $db->query($sql);
	         $out = "";
	         while($row = $rs->fetch_assoc()){
	             $idxSQL = "select index_no from incident_description where incident_id='".$row['id']."'";
	             $idxRS  = $db->query($idxSQL);
	             $idxRow = $idxRS->fetch_assoc();
	             $index_no = $idxRow ? $idxRow['index_no'] : '';
	             $out .= $row['id'].";"
	                   . $row['incident_no'].";"
	                   . $row['incident_type'].";"
	                   . $row['level'].";"
	                   . date('Y-m-d',strtotime($row['incident_date'])).";"
	                   . $index_no
	                   . "==>";
	         }
	         echo ($out=="") ? "No data available" : $out;
	     }
	   Response format matches the existing scrollRolling/getDriver convention:
	   rows separated by "==>", fields within a row separated by ";".
	   Field order: id;incident_no;incident_type;level;date;index_no */
	/* Only the explicit "All" tab requests unscoped history. Every other
	   tab — today, rolling, power, l3 — is a same-day view, so all of them
	   send scope=today and stay within the date(incident_date)=curdate()
	   safety boundary on the server. */
	var scope = (cTabFilter==='all') ? 'all' : 'today';
	makeajax("processing.php?searchIncidents="+encodeURIComponent(q)+"&scope="+scope,"irSearchResponse");
}

function irSearchResponse(ajaxHTML){
	var results=[];
	if(ajaxHTML!=="No data available" && ajaxHTML!==""){
		var rows=ajaxHTML.split("==>");
		var count=rows.length-1; /* trailing ==> leaves one empty element */
		for(var n=0;n<count;n++){
			var parts=rows[n].split(";");
			results.push({
				id:parts[0], no:parts[1], type:parts[2],
				level:parseInt(parts[3],10)||0, date:parts[4],
				index_no:parts[5]||"", description:""
			});
		}
	}
	if(irSearchCallback) irSearchCallback(results);
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
/* ── Multi-equipment-with-subitem picker (Option A pattern) ──────────────
   Each selected equipment item gets its own card with an independent
   sub-item dropdown, fetched from the SAME processing.php?scrollSubItem=
   endpoint the legacy single-select already uses — called once per item
   instead of once total.

   IMPORTANT: every existing makeajax() call anywhere in this codebase
   (scrollRolling, getDriver, fillSuper, subItem, all of them) passes a
   single, static, hardcoded callback name. None of them ever construct
   the callback name dynamically per call. An earlier version of this
   picker tried to register a fresh "eqSubItemResponse_<id>" function on
   window for every card — that pattern has no precedent anywhere in this
   app and was never confirmed against the actual ajax.js implementation,
   which is why sub-items silently failed to appear: the assumption that
   makeajax can resolve a freshly-minted callback name was unverified and
   wrong.

   Fixed approach: ONE static callback, "eqSubItemResponse", exactly like
   every other working makeajax() call in this file. Since each card's
   fetch is triggered one user-click at a time (never genuinely
   simultaneous), a small FIFO queue tracks which equipment id the next
   incoming response belongs to — first request in, first response out,
   which matches how a single XMLHttpRequest-per-call helper like
   makeajax actually behaves in practice.
   ────────────────────────────────────────────────────────────────────── */
var eqSelected={};
var eqLinked={};          /* id → equipment name */
var eqSubItemChoice={};   /* id → currently chosen subitem_id (or '' if none yet) */
var eqPendingQueue=[];    /* FIFO of equipt_ids whose scrollSubItem fetch is in flight */

var eqSearchResults=[];

function eqSearch(q,cb,prob){
	eqSearchCallback=cb;
	/* Calls processing.php?searchEquipment=  — add this case to processing.php:
	     if(isset($_GET['searchEquipment'])){
	         $q = $db->real_escape_string($_GET['searchEquipment']);
	         $sql = "select id,equipment_name,category from equipment
	                 where type='RS' and equipment_name like '%".$q."%'
	                 order by equipment_name";
	         $rs = $db->query($sql);
	         $out = "";
	         while($row = $rs->fetch_assoc()){
	             $out .= $row['id'].";".$row['equipment_name'].";".$row['category']."==>";
	         }
	         echo ($out=="") ? "No data available" : $out;
	     }
	   Response format matches the existing scrollRolling/getDriver convention:
	   rows separated by "==>", fields within a row separated by ";". */
	makeajax("processing.php?probname="+encodeURIComponent(prob)+"&searchEquipment="+encodeURIComponent(q),"eqSearchResponse");
}

var eqSearchCallback=null;

function eqSearchResponse(ajaxHTML){
	var results=[];
	/* DIAGNOSTIC: if processing.php?searchEquipment= hasn't been added yet,
	   or the call errors, ajaxHTML will be something other than the exact
	   strings this code expects ("No data available" or a well-formed
	   "id;name;category==>" sequence). Surface that loudly instead of
	   silently parsing garbage into fake equipment rows.

	   Note: an equipment item correctly having NO sub-items is a normal,
	   legitimate result from scrollSubItem — the equipment table genuinely
	   contains a mix of items with and without sub-items. That is not, by
	   itself, evidence of anything being wrong; the diagnostic here only
	   concerns whether searchEquipment's response is well-formed at all,
	   not whether any individual item happens to have sub-items.

	   CORRECTED: the equipment_name field can legitimately be EMPTY — the
	   real equipment table has rows like (129, '', 'RS', 'EXT') with a
	   blank name, confirmed directly against the live database dump. The
	   original regex required [^;]+ (one or more characters) for the name
	   field, which wrongly rejected that real, valid row as "malformed."
	   The id field is the one part of each row that should always be
	   numeric, so that's what the pattern actually checks now instead. */
	var looksWellFormed = (ajaxHTML==="No data available") ||
		(ajaxHTML==="") ||
		(/^(\d+;[^;]*;[^;]*==>)+$/.test(ajaxHTML));

	if(!looksWellFormed){
		console.error('[eqSearchResponse] Unexpected response from processing.php?searchEquipment= — '
			+'this almost always means processing.php errored, the equipment table query '
			+'matched zero rows in an unexpected way, or something in the response isn\'t '
			+'pure data. Raw response below:');
		console.error(ajaxHTML);
		/* Escape the raw response so it displays as visible text rather than
		   being interpreted as HTML — if processing.php is leaking a PHP
		   warning or notice, that warning is often itself HTML-formatted
		   (e.g. "<br />\n<b>Warning</b>: ..."), and without escaping it the
		   browser would render it as styled markup instead of showing the
		   actual diagnostic text that explains what's wrong. */
		var escaped = String(ajaxHTML)
			.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
		document.getElementById('eq-list').innerHTML=
			'<div style="padding:9px 11px;font-size:11px;color:#A32D2D;line-height:1.5;">'
			+'<strong>Equipment search did not return usable data.</strong><br>'
			+'Raw response from processing.php?searchEquipment= shown below — '
			+'this is the actual text needed to find what is wrong:'
			+'<pre style="background:#FDF2F2;border:1px solid #DDB5B3;border-radius:4px;'
			+'padding:8px;margin-top:6px;white-space:pre-wrap;word-break:break-word;'
			+'font-family:monospace;font-size:11px;color:#7A1F1F;max-height:160px;overflow-y:auto;">'
			+(escaped===''?'(completely empty response — processing.php may not be reaching this code path at all)':escaped)
			+'</pre>'
			+'</div>';
		eqSearchResults=[];
		/* Deliberately do NOT invoke eqSearchCallback here: eqRenderList([])
		   would immediately overwrite the diagnostic message above with its
		   own "No matches" fallback, hiding the very error we're trying to
		   surface. The diagnostic message stays visible until the next
		   search attempt. */
		return;
	}

	if(ajaxHTML!=="No data available" && ajaxHTML!==""){
		var rows=ajaxHTML.split("==>");
		var count=rows.length-1;
		for(var n=0;n<count;n++){
			var parts=rows[n].split(";");
			/* Guard against malformed individual rows even within an
			   otherwise well-formed-looking response. */
			if(!parts[0] || isNaN(parseInt(parts[0],10))){
				console.warn('[eqSearchResponse] Skipping row with non-numeric id:', rows[n]);
				continue;
			}
			/* IMPORTANT: preserve name as-is, including a genuinely empty
			   string — do NOT substitute a placeholder here. A blank
			   equipment_name is real, confirmed data (see id 129 above),
			   not something to paper over at the parsing layer. Display
			   fallback labeling belongs in eqRenderList, the one place
			   that decides what the user actually sees, not duplicated
			   here where it would just get overwritten or cause
			   confusion about which layer owns that decision. */
			results.push({id:parts[0], name:parts[1]||'', category:parts[2]||""});
		}
	}
	eqSearchResults=results;
	if(eqSearchCallback) eqSearchCallback(results);
}

function eqTogglePanel(){
	var prob_type=document.getElementById('type').value;

	var p=document.getElementById('eq-panel');
	var open=p.style.display!=='none';
	p.style.display=open?'none':'block';
	if(!open){ eqSearch('',eqRenderList,prob_type); }
}

function eqFilterInput(q){
	
	var prob_type=document.getElementById('type').value;
	eqSearch(q,eqRenderList,prob_type);
	
	document.getElementById('eq-panel').style.display='block';
}

function eqRenderList(data){
	var html='';
	data.forEach(function(r){
		var chk=eqSelected[String(r.id)]?'checked':'';
		/* Some equipment rows genuinely have a blank equipment_name in the
		   database (confirmed against the live data — e.g. id 129 through
		   183 are a block of unnamed entries, category EXT). Without a
		   fallback label these would render as an empty, unreadable row.
		   Showing "(unnamed — id <n>)" keeps them identifiable and still
		   selectable rather than silently invisible. */
		var displayName = r.name && r.name.trim()!=='' ? r.name : '(unnamed — id '+r.id+')';
		var displayNameEsc = displayName.replace(/'/g,"\\'");
		html+='<label class="ir-eq-cb-row">'
			+'<input type="checkbox" value="'+r.id+'" '+chk
			+' onchange="eqToggle(\''+r.id+'\',\''+displayNameEsc+'\',this.checked)">'
			+'<span class="ir-eq-name"'+(displayName.indexOf('unnamed')>=0?' style="color:var(--ir-muted);font-style:italic;"':'')+'>'+displayName+'</span>'
			+'<span class="ir-eq-cat">'+r.category+'</span>'
			+'</label>';
	});
	document.getElementById('eq-list').innerHTML=html||
		'<div style="padding:9px 11px;font-size:11px;color:var(--ir-muted)">No matches</div>';
}

function eqToggle(id,name,checked){
	id=String(id);
	if(checked) eqSelected[id]=name; else delete eqSelected[id];
}

function eqAddSelected(){
	/* Add each chip first (synchronous, all cards appear immediately in
	   "Loading…" state). */
	var ids=Object.keys(eqSelected);
	ids.forEach(function(id){ eqAddChip(id,eqSelected[id]); });
	document.getElementById('eq-panel').style.display='none';
	eqSelected={};

	/* THEN fetch each card's sub-items ONE AT A TIME, never more than one
	   scrollSubItem request in flight simultaneously.

	   Why: makeajax(url, callbackName) takes a callback by STRING NAME,
	   not a function reference or closure — every existing call in this
	   codebase confirms that's the only contract it supports. That means
	   there is no way to attach per-request identity to an individual
	   in-flight call. An earlier version of this queued requests by SEND
	   order and assumed responses would arrive back in that same order —
	   but that assumption is false for real network requests fired back
	   to back; a later-sent request can easily complete before an
	   earlier one, especially if one equipment id's sub_item lookup
	   happens to be faster than another's. When that happened here, a
	   response correctly meant for one card got dequeued and applied to
	   a DIFFERENT card instead — exactly the bug reported: items that do
	   have sub-items showed empty, because their real response had
	   already been consumed by the wrong card's slot.

	   Serializing removes the race entirely: only one scrollSubItem
	   request exists at any moment, so there is never an ordering
	   question to get wrong. eqFetchNextInQueue is called again once
	   each response is rendered, advancing to the next card. */
	eqPendingQueue = ids.slice();
	eqFetchNextInQueue();
}

function eqFetchNextInQueue(){
	if(eqPendingQueue.length===0) return;
	var id=eqPendingQueue[0]; /* peek, not shift — eqSubItemResponse shifts after rendering */
	makeajax("processing.php?scrollSubItem="+id,"eqSubItemResponse");
}

function eqAddChip(id,label){
	id=String(id);
	if(eqLinked[id]) return;
	eqLinked[id]=label;
	eqSubItemChoice[id]='';

	var chips=document.getElementById('eq-chips');
	var empty=chips.querySelector('.ir-eq-empty');
	if(empty) chips.removeChild(empty);

	/* Card: equipment name + remove button on top, its own sub-item
	   select (loading state initially) underneath. */
	var card=document.createElement('div');
	card.className='ir-eq-card'; card.id='eq-card-'+id;
	card.innerHTML=
		'<div class="ir-eq-card-head">'
			+'<span class="ir-eq-card-name">'+label+'</span>'
			+'<button type="button" onclick="eqRemoveChip(\''+id+'\')" title="Remove">&times;</button>'
		+'</div>'
		+'<div class="ir-eq-card-sub" id="eq-sub-'+id+'">'
			+'<span class="ir-eq-loading">Loading sub-items…</span>'
		+'</div>';
	chips.appendChild(card);

	eqSyncHidden();
}

function eqSubItemResponse(ajaxHTML){
	var id=eqPendingQueue.shift(); /* this request is done — remove it, THEN advance */
	if(id===undefined) return; /* defensive: response with nothing queued */
	eqRenderSubItemSelect(id,ajaxHTML);
	eqFetchNextInQueue(); /* fire the next card's request only after this one is fully handled */
}

function eqRenderSubItemSelect(id,ajaxHTML){
	var target=document.getElementById('eq-sub-'+id);
	if(!target) return; /* card was removed before the response arrived */

	/* Same diagnostic principle as eqSearchResponse: distinguish a
	   genuinely correct "this equipment has no sub-items" response from
	   an unexpected/malformed one, instead of treating both identically.
	   id required numeric, sub-item name allowed to be empty — same
	   reasoning as the equipment-search fix above. */
	var looksWellFormed = (ajaxHTML==="No data available") ||
		(ajaxHTML==="") ||
		(/^(\d+;[^;]*==>)+$/.test(ajaxHTML));

	var html;
	if(!looksWellFormed){
		console.error('[eqRenderSubItemSelect] Unexpected response from processing.php?scrollSubItem='+id
			+' — raw response below:');
		console.error(ajaxHTML);
		var escapedSub = String(ajaxHTML)
			.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
		html='<div style="color:#A32D2D;font-size:11px;line-height:1.5;">'
			+'Could not load sub-items — raw response:'
			+'<pre style="background:#FDF2F2;border:1px solid #DDB5B3;border-radius:4px;'
			+'padding:6px;margin-top:4px;white-space:pre-wrap;word-break:break-word;'
			+'font-family:monospace;font-size:10px;color:#7A1F1F;max-height:120px;overflow-y:auto;">'
			+(escapedSub===''?'(empty response)':escapedSub)
			+'</pre></div>';
	} else if(ajaxHTML==="No data available" || ajaxHTML===""){
		html='<span class="ir-eq-no-sub">No sub-items for this equipment</span>';
	} else {
		var rows=ajaxHTML.split("==>");
		var count=rows.length-1;
		html='<select class="ir-eq-subselect" onchange="eqSetSubItem(\''+id+'\',this.value)">'
			+'<option value="">Select sub-item…</option>';
		for(var n=0;n<count;n++){
			var parts=rows[n].split(";");
			html+='<option value="'+parts[0]+'">'+(parts[1]||'(unnamed)')+'</option>';
		}
		html+='</select>';
	}
	target.innerHTML=html;
}

function eqSetSubItem(id,subitemId){
	eqSubItemChoice[String(id)]=subitemId;
	eqSyncHidden();
}

function eqRemoveChip(id){
	id=String(id);
	var el=document.getElementById('eq-card-'+id);
	if(el) el.remove();
	delete eqLinked[id];
	delete eqSubItemChoice[id];
	/* If this card's fetch is currently the in-flight request (front of
	   eqPendingQueue), or still waiting further back in the queue, it is
	   deliberately left in place rather than spliced out here. The chain
	   self-heals either way once its response arrives: eqSubItemResponse
	   still shifts it off and calls eqFetchNextInQueue to keep the chain
	   moving, and eqRenderSubItemSelect's "card was removed" guard below
	   safely no-ops instead of writing to a DOM element that's gone. The
	   only cost is one wasted network call for a card nobody will see
	   the result of — not a correctness problem, just a minor inefficiency
	   that isn't worth the complexity of splicing mid-queue. */
	var chips=document.getElementById('eq-chips');
	if(!chips.querySelector('.ir-eq-card'))
		chips.innerHTML='<span class="ir-eq-empty">No equipment selected</span>';
	eqSyncHidden();
}

function eqSyncHidden(){
	/* Each pair travels as equipt_id:subitem_id so the PHP handler can
	   split on ":" then "," — subitem_id may be empty if not yet chosen,
	   which the server treats as "no sub-item specified" rather than
	   discarding the equipment selection itself. */
	var pairs=Object.keys(eqLinked).map(function(id){
		return id+":"+(eqSubItemChoice[id]||'');
	});
	document.getElementById('eq-ids-hidden').value=pairs.join(',');
}

/* ── Option C specifics (incident linking) ── */
var cSelected={};
var cTabFilter='today'; /* default scope — server only returns today's incidents until widened */

function cOpenModal(){
	document.getElementById('c-modal').classList.add('open');
	/* Reset to the safe default each time the modal opens, regardless of
	   what scope was active last time it was closed. */
	cTabFilter='today';
	document.querySelectorAll('#c-modal .ir-filter-tab').forEach(function(t){t.classList.remove('active');});
	document.querySelector('#c-modal .ir-filter-tab').classList.add('active');
	document.getElementById('c-search-input').value='';
	cFilterSearch('');
}

function cCloseModal(){
	document.getElementById('c-modal').classList.remove('open');
}

function cSetTab(btn,key){
	document.querySelectorAll('#c-modal .ir-filter-tab').forEach(function(t){t.classList.remove('active');});
	btn.classList.add('active');
	cTabFilter=key;
	cFilterSearch(document.getElementById('c-search-input').value);
}

function cFilterSearch(q){
	/* scope=today vs scope=all is resolved server-side in irSearchIncidents.
	   Only the "All" tab requests scope=all (full history, date descending).
	   Today, Rolling Stock, Power, and Level 3+ all request scope=today —
	   the type/level narrowing below is applied on top of that same-day set,
	   not on an unscoped fetch. */
	irSearchIncidents(q,function(data){
		var filtered=data.filter(function(r){
			if(cTabFilter==='today' || cTabFilter==='all') return true;
			if(cTabFilter==='l3') return r.level>=3;
			return r.type.toLowerCase().indexOf(cTabFilter)>=0;
		});
		cRenderResults(filtered);
	});
}

function cRenderResults(data){
	var html='';
	data.forEach(function(r){
		var chk=cSelected[String(r.id)]?'checked':'';
		html+='<tr>'
			+'<td style="width:28px"><input type="checkbox" value="'+r.id+'" '+chk
			+" onchange=\"cToggle('"+r.id+"','"+r.no+"',this.checked)\""
			+' style="accent-color:var(--ir-blue)"></td>'
			+'<td class="ir-link-no">'+r.no+'</td>'
			+'<td>'+r.type+'<br><span style="font-size:10px;color:var(--ir-muted)">'+r.description+'</span></td>'
			+'<td>'+irLvlBadge(r.level)+'</td>'
			+'<td class="ir-link-muted" style="white-space:nowrap">'+r.date+'</td>'
			+'<td class="ir-link-no" style="font-size:10px">'+(r.index_no||'—')+'</td>'
			+'</tr>';
	});
	document.getElementById('c-tbody').innerHTML=html||
		'<tr><td colspan="6" style="padding:12px;text-align:center;color:var(--ir-muted)">No matches</td></tr>';
}

function cToggle(id,no,checked){
	id=String(id);
	if(checked) cSelected[id]=no; else delete cSelected[id];
	cUpdateCount();
}

function cUpdateCount(){
	var n=Object.keys(cSelected).length;
	document.getElementById('c-sel-count').textContent=n+' selected';
}

function cConfirm(){
	Object.keys(cSelected).forEach(function(id){ irAddChip(id,cSelected[id]); });
	cCloseModal();
	cSelected={};
	cUpdateCount();
}

document.addEventListener('keydown',function(e){
	if(e.key==='Escape') cCloseModal();
});
</script>

<body>
<div class="ir-page">

	<!-- Page header -->
	<div class="ir-page-header">
		<span class="ir-wordmark">LINE 3</span>
		<h1>Record Incident</h1>
		<span class="ir-context">
			<?php if(isset($_GET['cancel'])){ echo "Cancelled departure"; } elseif(isset($_GET['add_incident'])){ echo "Add incident to train"; } ?>
		</span>
	</div>

	<div class="ir-form-body">
	<form action='incident report.php<?php if(isset($_GET['cancel'])){ echo "?cancel=".$_GET['cancel']; } else if(isset($_GET['add_incident'])){ echo "?add_incident=".$_GET['add_incident']; } if($IR_EMBED){ echo (isset($_GET['cancel'])||isset($_GET['add_incident']))?"&embed=1":"?embed=1"; } ?>' method='post'>

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

	<!-- Equipment Involved — multi-select with per-item sub-items (now primary) -->
	<tr>
		<td class="ir-label ir-label--top" style="padding-top:11px">Equipment Involved</td>
		<td class="ir-field" style="padding-top:9px">

			<!-- Multi-equipment picker — Option A: Inline search panel,
			     each selected item expands into its own sub-item dropdown -->
			<div style="display:flex;gap:7px;margin-bottom:6px;">
				<input type='text' class="ir-input--sm" id='eq-search-input'
					placeholder="Search equipment…"
					oninput='eqFilterInput(this.value)'
					autocomplete="off" />
				<input type='button' value='Browse' onclick='eqTogglePanel()' />
			</div>

			<div class="ir-eq-panel" id="eq-panel" style="display:none;">
				<div class="ir-eq-panel-head">
					<i class="ti ti-search" style="color:var(--ir-muted)"></i>
					Tick equipment to add, then click Add Selected
				</div>
				<div class="ir-eq-panel-body" id="eq-list"></div>
				<div class="ir-eq-panel-foot">
					<input type='button' value='Cancel'
						onclick='document.getElementById("eq-panel").style.display="none"' />
					<input type='button' value='Add selected ✓' onclick='eqAddSelected()'
						style="background:var(--ir-blue);color:#fff;border-color:var(--ir-blue);" />
				</div>
			</div>

			<div class="ir-eq-chips" id="eq-chips">
				<span class="ir-eq-empty">No equipment selected</span>
			</div>
			<input type='hidden' name='equipment_ids' id='eq-ids-hidden' value=''>
			<div class="ir-subtle-note">Each item added above gets its own sub-item dropdown once its list loads.</div>

		</td>
	</tr>

	<!-- Equipment / Train Unavailability (legacy single-select — kept, retired to fallback) -->
	<tr>
		<td class="ir-label">Equipment <span class="ir-subtle-note" style="display:block;font-weight:400">(legacy, single item)</span></td>
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
			<div class="ir-subtle-note">Legacy field — still writes to the original equipt/subitem columns if used.</div>
		</td>
	</tr>

	<!-- Additional Defects (legacy mechanism — unchanged, kept for parity) -->
	<tr>
		<td class="ir-label">Additional Defects <span class="ir-subtle-note" style="display:block;font-weight:400">(legacy)</span></td>
		<td class="ir-field">
			<label class="ir-check-row">
				<input type="checkbox" name='multipleFlag' id='multipleFlag' onclick='activateMultiple()' />
				Multiple defects (opens separate popup)
			</label>
			<span id='multiple_space' name='multiple_space'></span>
		</td>
	</tr>

	<!-- Link Incident Report — Option C: Full modal overlay -->
	<tr>
		<td class="ir-label ir-label--top" style="padding-top:11px">Link Other Incident Report(s)</td>
		<td class="ir-field" style="padding-top:9px">

			<!-- Trigger row -->
			<div style="display:flex;gap:7px;align-items:center;margin-bottom:6px;">
				<input type='button' value='Link incidents…' onclick='cOpenModal()'
					style="background:var(--ir-blue);color:#fff;border-color:var(--ir-blue);" />
				<input type='button' value='Popup (original)' onclick='openLink()' style="opacity:.6" />
			</div>

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
		<td class="ir-label">Index Number</td>
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
				<input name='index_id' id='index_id' type='text' class="ir-input--xs" value='<?php echo $index_id; ?>' />

		</td>
	</tr>
	<tr>
		<td class="ir-label">Car Number(s)</td>
		<td class="ir-field">
			<div class="ir-inline">
				<!--
				<span class="ir-sep">/</span>
				-->
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
		<td class="ir-label">Date / Time of Incident</td>
		<td class="ir-field">
			<?php
			if(isset($_SESSION['month'])){
				$incident_date_label=date("m/d/Y",strtotime($_SESSION['year']."-".$_SESSION['month']."-".$_SESSION['day']));
			} else {
				$incident_date_label=date("m/d/Y");
			}
			?>
			<input type='text' name='incident_date' id='incident_date' class='datepicker ir-input--md' value='<?php echo $incident_date_label; ?>' />
			
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

	<tr>
		<td class="ir-label">Date / Time Resolved</td>
		<td class="ir-field ir-inline">
			<?php
			if(isset($_SESSION['month'])){
				$incident_date_label=date("m/d/Y",strtotime($_SESSION['year']."-".$_SESSION['month']."-".$_SESSION['day']));
			} else {
				$incident_date_label=date("m/d/Y");
			}
			?>
			<input type='text' name='incident_date' id='incident_date' class='datepicker ir-input--md' value='<?php echo $incident_date_label; ?>' />

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
		<td class="ir-label ir-label--top">Maintenance Provider (TESP / Other)</td>
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

<!-- ── Option C Modal (in DOM, outside the form) ── -->
<div class="ir-modal-backdrop" id="c-modal">
	<div class="ir-modal-box">
		<div class="ir-modal-head">
			<h4><i class="ti ti-link" style="margin-right:6px"></i>Link Incident Reports</h4>
			<button class="ir-modal-close" type="button" onclick="cCloseModal()">&times;</button>
		</div>
		<div class="ir-modal-body">
			<!-- Search + tabs -->
			<div style="display:flex;gap:7px;margin-bottom:8px;">
				<input type='text' id='c-search-input' class="ir-input--lg"
					placeholder="Search by incident no., type, description…"
					oninput='cFilterSearch(this.value)' autocomplete="off" />
				<input type='button' value='Clear' onclick='document.getElementById("c-search-input").value="";cFilterSearch("")' />
			</div>
			<div class="ir-filter-tabs" id="c-tabs">
				<button class="ir-filter-tab active" type="button" onclick="cSetTab(this,'today')">Today</button>
				<button class="ir-filter-tab" type="button" onclick="cSetTab(this,'all')">All (date descending)</button>
				<button class="ir-filter-tab" type="button" onclick="cSetTab(this,'rolling')">Rolling Stock (today)</button>
				<button class="ir-filter-tab" type="button" onclick="cSetTab(this,'power')">Power (today)</button>
				<button class="ir-filter-tab" type="button" onclick="cSetTab(this,'l3')">Level 3+ (today)</button>
			</div>
			<!-- Results -->
			<div class="ir-result-scroll">
				<table class="ir-link-results">
					<thead>
						<tr>
							<th style="width:28px"></th>
							<th>Incident No.</th>
							<th>Type / Description</th>
							<th>Lvl</th>
							<th>Date</th>
							<th>Index</th>
						</tr>
					</thead>
					<tbody id="c-tbody"></tbody>
				</table>
			</div>
		</div>
		<div class="ir-modal-foot">
			<span class="ir-modal-sel-count" id="c-sel-count">0 selected</span>
			<div style="display:flex;gap:8px;">
				<input type='button' value='Cancel' onclick='cCloseModal()' />
				<input type='button' value='Confirm &amp; link' onclick='cConfirm()'
					style="background:var(--ir-blue);color:#fff;border-color:var(--ir-blue);" />
			</div>
		</div>
	</div>
</div>
<!-- close modal backdrop click -->
<script>
document.getElementById('c-modal').addEventListener('click',function(e){
	if(e.target===this) cCloseModal();
});
</script>

</body>