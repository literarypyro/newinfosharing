<?php 
require("Tmenu.php");
ini_set("date.timezone","Asia/Kuala_Lumpur");
?>
<?php
/* =========================================================================
   train_operations.php  (reconciliation of train_availability.php + operations.php)

   ENGINE  : train_availability.php, verbatim.
             Same tables (train_availability, train_ava_time, train_switch,
             train_compo, train_incident_view, level, timetable_*), same POST
             field names, same processing.php AJAX endpoints, same helpers.
             All eight POST handlers below are copied unchanged.
   LAYOUT  : operations.php's structure -- two-row page header with date
             prev/today/next, timetable info strip, filter pills, refined
             table with one car per sub-row, and a right slide-panel that
             replaces the Bootstrap modal. Skinned in the Line 3 console
             palette (#00529B / #FDB813) already used across the ISS.
   DELIBERATE DIFFERENCES FROM train_availability.php (documented inline):
     - The 7 "Switch" columns become a switch trail (chips) inside the
       Index cell. Same data, same add/delete AJAX (processing.php), no cap
       of 7 lost columns of empty space.
     - L4 column KEPT (operations.php had dropped it).
     - Sub-row count follows the compo (3 cars = 3 rows) instead of a fixed 4.
   NOT PORTED FROM operations.php (no columns for them in the legacy schema):
     stabling / ready-for-insertion / planned-actual departure / loop,
     secondary driver, the Train Management board.
   INCIDENT FLOW: now a slide panel too — an iframe onto
     "incident report.php?cancel=|add_incident=...&embed=1" (embed discards
     Tmenu chrome, form still posts to itself; on save it postMessages
     'ir-saved' and this page reloads). Standalone/popup use is unchanged.
   Rename-safe: form action / reloads use $selfPage = basename(__FILE__).
   ========================================================================= */
require_once("db_connect.php"); /* shared $db + db_exec()/db_query() prepared-statement helpers (item #2) */

/* ── Helper functions (verbatim from original) ── */
function getTrainDriver($id,$dbase){
	$rs=db_query($dbase,"select firstName,lastName,position from train_driver where id=? limit 1",array($id));
	if($rs===false || $rs->num_rows==0) return $id; /* item #2 micro-change: old code emitted PHP warnings + a mangled '. ' name for unknown ids */
	$row=$rs->fetch_assoc();
	return $row['position']." ".substr($row['firstName'],0,1).". ".$row['lastName'];
}
function getPHTrainDriver($id,$dbase){
	$rs=db_query($dbase,"select firstName,lastName from ph_trams where id=? limit 1",array($id));
	if($rs!==false && $rs->num_rows>0){
		$row=$rs->fetch_assoc();
		return substr($row['firstName'],0,1).". ".$row['lastName'];
	}
	return $id;
}
function getLevel($id,$dbase){
	/* === item #16 PORTED 2026-07 from train_availability.php (the one fix this
	   page's fork predated -- items #2, #1, and #3 were already carried/re-derived
	   in the redesign) ===
	   `level`.`order` is a MyISAM per-(date,level) AUTO_INCREMENT -- it numbers by
	   INSERTION ORDER, so late entries, corrections (edit_ccdr deletes+reinserts
	   the level row), and deletions desync it permanently. The ordinal is computed
	   live instead: this incident's chronological position (by incident_report.
	   incident_date, ties by id) among ALL same-day, same-level incidents -- the
	   same population the stored counter numbered. The `order` column keeps being
	   written by the engine as before; it's just no longer read here. Covers both
	   call sites on this page (active and cancelled branches). === END PORT === */
	$rs=db_query($dbase,"select l.date,l.level,ir.incident_date,ir.id as ir_id
		from level l join incident_report ir on ir.id=l.incident_id
		where l.incident_id=? limit 1",array($id));
	if($rs===false || $rs->num_rows==0) return "";
	$l0=$rs->fetch_assoc();
	$rs=db_query($dbase,"select count(*)+1 as rnk
		from level l join incident_report ir on ir.id=l.incident_id
		where l.date=? and l.level=?
		and (ir.incident_date<? or (ir.incident_date=? and ir.id<?))",
		array($l0['date'],$l0['level'],$l0['incident_date'],$l0['incident_date'],$l0['ir_id']));
	$row=$rs->fetch_assoc();
	return $row['rnk'];
}
function insertCompo($train_id,$car,$dbase){
	if($car=="") return;
	db_exec($dbase,"insert into train_compo(tar_id,car_no) values (?,?)",array($train_id,$car));
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
	db_exec($db,"insert into train_availability(index_no,date,car_a,car_b,car_c,car_d,lpam_id,status,type)
		values (?,?,?,?,?,?,?,'active',?)",
		array($index_no,$availability_date,$car_a,$car_b,$car_c,$car_d,$lpam_id,$type));
	$index_id=$db->insert_id;
	insertCompo($index_id,$car_a,$db); insertCompo($index_id,$car_b,$db);
	insertCompo($index_id,$car_c,$db); insertCompo($index_id,$car_d,$db);
	if(isset($_POST['cancel_departure'])){
		$availability_date="";
		db_exec($db,"update train_availability set status='cancelled' where id=?",array($index_id));
		echo "<script>window.addEventListener('load',function(){openIncidentPanel('cancel=".$index_id."','Cancel Train');});</script>"; /* was window.open popup; panel opens after load */
	}
	db_exec($db,"insert into train_ava_time(train_ava_id,boundary_time) values (?,?)",array($index_id,$availability_date));
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
	db_exec($db,"insert into train_availability(index_no,date,status,type) values (?,?,'active','unimog')",array($index_no,$availability_date));
	$index_id=$db->insert_id;
	if(isset($_POST['cancel_departure'])){
		db_exec($db,"update train_availability set status='cancelled' where id=?",array($index_id));
		$availability_date="";
		echo "<script>window.addEventListener('load',function(){openIncidentPanel('cancel=".$index_id."','Cancel Train');});</script>"; /* was window.open popup; panel opens after load */
	}
	db_exec($db,"insert into train_ava_time(train_ava_id,boundary_time) values (?,?)",array($index_id,$availability_date));
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
	$rs=db_query($db,"select * from train_ava_time where train_ava_id=?",array($_POST['insertion_id']));
	if($rs->num_rows>0){
		/* item #2: the two consecutive UPDATEs on the same row were merged into one statement */
		db_exec($db,"update train_ava_time set insert_time=?,insert_driver=?,inserted_to=? where train_ava_id=?",
			array($availability_date,$train_driver,$_POST['inserted_to'],$_POST['insertion_id']));
	} else {
		db_exec($db,"insert into train_ava_time(train_ava_id,insert_time,insert_driver,inserted_to) values (?,?,?,?)",
			array($_POST['insertion_id'],$availability_date,$train_driver,$_POST['inserted_to']));
	}
	$changeRow=db_query($db,"select * from train_availability where id=?",array($_POST['insertion_id']))->fetch_assoc();
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
	/* item #2: removal_remarks is now a bound parameter -- quotes/apostrophes in remarks no
	   longer break or corrupt this UPDATE. The two consecutive UPDATEs on the same row
	   (remove fields, then removed_from) were merged into one statement. */
	db_exec($db,"update train_ava_time set remove_time=?,remove_driver=?,removal_remarks=?,removed_from=? where train_ava_id=?",
		array($availability_date,$train_driver,$_POST['remarks'],$_POST['removed_from'],$_POST['remove_id']));
	$changeRow=db_query($db,"select * from train_availability where id=?",array($_POST['remove_id']))->fetch_assoc();
	$train_date=$changeRow['date'];
	$_POST['year']=date("Y",strtotime($train_date)); $_POST['month']=date("m",strtotime($train_date));
	$_POST['day']=date("d",strtotime($train_date));  $_POST['hour']=date("H",strtotime($train_date));
	$_POST['minute']=date("i",strtotime($train_date)); $_POST['amorpm']=date("A",strtotime($train_date));
	if(isset($_POST['cancel_loop'])){
		echo "<script>window.addEventListener('load',function(){openIncidentPanel('add_incident=".$_POST['remove_id']."','Add Incident');});</script>"; /* was window.open popup; panel opens after load */
	}
}

if(isset($_POST['remarks_id'])){
	$rs=db_query($db,"select * from train_ava_time where train_ava_id=?",array($_POST['remarks_id']));
	if($rs->num_rows>0){
		db_exec($db,"update train_ava_time set removal_remarks=? where train_ava_id=?",array($_POST['remarks'],$_POST['remarks_id']));
	} else {
		db_exec($db,"insert into train_ava_time(removal_remarks,train_ava_id) values (?,?)",array($_POST['remarks'],$_POST['remarks_id']));
	}
}

if(isset($_POST['switch_id'])){
	/* item #2 note: this postback path is superseded by the AJAX switch
	   (processing.php?ajaxSwitch) -- converted anyway; its removal is item #4. */
	$year=$_POST['year']; $month=$_POST['month']; $day=$_POST['day'];
	$hour=$_POST['hour']; $minute=$_POST['minute']; $amorpm=$_POST['amorpm'];
	if($amorpm=="pm"){ if($hour<12) $hour+=12; }
	else { if($hour=="12") $hour=0; }
	
	$switchDriver="";
	$switchDriver=$_POST['train_driver'];
	
	$availability_date=date("Y-m-d H:i",strtotime($year."-".$month."-".$day." ".$hour.":".$minute));
	db_exec($db,"insert into train_switch(train_ava_id,new_index,date_change,train_driver) values (?,?,?,?)",array($_POST['switch_id'],$_POST['new_index'],$availability_date),$switchDriver);
	$switchRow=db_query($db,"select * from train_availability where id=? limit 1",array($_POST['switch_id']))->fetch_assoc();
	if($switchRow['type']=="reserve"){
		db_exec($db,"update train_availability set type='revenue' where id=?",array($_POST['switch_id']));
	}
}

if(isset($_POST['edit_id'])){
	db_exec($db,"update train_availability set index_no=? where id=?",array($_POST['edit_index'],$_POST['edit_id']));
}

if(isset($_POST['edit_car'])){
	/* item #1 fix: re-inserts previously used $index_id, which is never set in this
	   branch -- the real compo rows were deleted, then replaced by orphan rows under a
	   blank tar_id. Now keyed to the edited train. (Edit modal also gained a Car 4 field,
	   so a 4-car set's car_d is no longer silently blanked.) */
	$edit_car_id=$_POST['edit_car'];
	db_exec($db,"delete from train_compo where tar_id=?",array($edit_car_id));
	db_exec($db,"update train_availability set car_a=?,car_b=?,car_c=?,car_d=? where id=?",
		array($_POST['car_1'],$_POST['car_2'],$_POST['car_3'],$_POST['car_4'],$edit_car_id));
	insertCompo($edit_car_id,$_POST['car_1'],$db); insertCompo($edit_car_id,$_POST['car_2'],$db);
	insertCompo($edit_car_id,$_POST['car_3'],$db); insertCompo($edit_car_id,$_POST['car_4'],$db);
}
?>

<?php $selfPage = basename(__FILE__); /* form action / reload target — rename-safe */ ?>

<link rel="stylesheet" href="jquery-ui-themes-1.11.1/themes/smoothness/jquery-ui.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.11.0/dist/tabler-icons.min.css">
<script src="jquery-ui-1.11.1/external/jquery/jquery.js"></script>
<script src="jquery-ui-1.11.1/jquery-ui.js"></script>

<style type='text/css'>
/* =========================================================================
   LINE 3 OPERATIONS CONSOLE — operations.php layout, train_availability skin
   ========================================================================= */
:root {
	--ta-sans: "Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif;
	--ta-mono: ui-monospace, "Cascadia Mono", "Consolas", "Liberation Mono", monospace;
	--rail:      #00529B;
	--rail-dark: #013E76;
	--rail-wash: #EEF4FB;
	--gold:      #FDB813;
	--gold-ink:  #3A2D00;
	--ink:       #16243B;
	--mut:       #5A6678;
	--line:      #D2DDEA;
	--paper:     #F7F9FC;
	--c-service:   #1D9E75;
	--c-reserve:   #BA7517;
	--c-removed:   #378ADD;
	--c-cancelled: #E24B4A;
}



.ta-ops { font-family:var(--ta-sans); color:var(--ink); }
.ta-ops * { box-sizing:border-box; }

/* ── Page header (operations.php two-row header, Line 3 skin) ── */
.ops-header       { background:var(--rail); border-bottom:3px solid var(--gold); padding:10px 16px; display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap; }
.ops-title h1     { margin:0; font-size:16px; font-weight:700; color:#fff; letter-spacing:.3px; line-height:1.2; }
.ops-title .sub   { font-size:10px; color:rgba(255,255,255,.55); letter-spacing:.5px; text-transform:uppercase; }
.ops-datebar      { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
.ops-datebar form { margin:0; padding:0; display:inline-flex; align-items:center; gap:4px; }
#search_date      { height:27px; font-size:12px; font-family:var(--ta-sans); background:#fff; color:var(--ink); border:1px solid rgba(255,255,255,.5); border-radius:4px; padding:0 8px; width:118px; }
.ops-go           { height:27px; font-size:11px; font-weight:700; background:var(--gold); color:var(--gold-ink); border:none; border-radius:4px; padding:0 12px; cursor:pointer; }
.ops-nav-btn      { height:27px; min-width:27px; font-size:11px; font-weight:600; font-family:var(--ta-sans); color:#fff; background:transparent; border:1px solid rgba(255,255,255,.35); border-radius:4px; padding:0 8px; cursor:pointer; }
.ops-nav-btn:hover{ background:rgba(255,255,255,.12); }
.ops-actions      { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
.ops-act          { display:inline-block; font-size:11px; font-weight:500; color:#fff; text-decoration:none; padding:5px 11px; border:1px solid rgba(255,255,255,.35); border-radius:4px; float:none !important; width:auto !important; cursor:pointer; }
.ops-act:hover    { background:rgba(255,255,255,.12); color:#fff; }
.ops-act--gold    { background:var(--gold); border-color:var(--gold); color:var(--gold-ink); font-weight:600; }
.ops-act--gold:hover { background:#E8A606; border-color:#E8A606; color:var(--gold-ink); }
.ops-act.disabled { color:rgba(255,255,255,.3); border-color:rgba(255,255,255,.15); pointer-events:none; }

/* ── Info strip (timetable code / date / day — operations.php bottom row) ── */
.ops-strip        { background:#fff; border-bottom:1px solid var(--line); padding:7px 16px; display:flex; align-items:center; gap:26px; flex-wrap:wrap; }
.ops-info         { display:flex; align-items:center; gap:8px; }
.ops-info i       { color:var(--rail); font-size:15px; }
.ops-info .lbl    { display:block; font-size:9px; text-transform:uppercase; letter-spacing:.6px; color:var(--mut); font-weight:600; }
.ops-info .val    { display:block; font-size:12.5px; font-weight:700; color:var(--ink); font-family:var(--ta-mono); }
.ops-info .val a  { font-size:10px; font-weight:400; font-family:var(--ta-sans); color:var(--rail); text-decoration:none; margin-left:6px; }
.ops-info .val a:hover { text-decoration:underline; }

/* ── Section header + filter pills (operations.php Operations Log row) ── */
.ops-section      { padding:10px 16px 2px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
.ops-section h2   { margin:0; font-size:12px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; color:var(--rail); }
.ops-pills        { display:flex; gap:5px; flex-wrap:wrap; }
.ops-pill         { font-size:10.5px; font-weight:600; font-family:var(--ta-sans); color:var(--mut); background:#fff; border:1px solid var(--line); border-radius:12px; padding:3px 11px; cursor:pointer; }
.ops-pill:hover   { border-color:var(--rail); color:var(--rail); }
.ops-pill.active  { background:var(--rail); border-color:var(--rail); color:#fff; }

/* ── Refined table (operations.php) — Line 3 skin ── */
.ops-table-wrap   { margin:8px 16px 14px; overflow-x:auto; border-radius:6px; box-shadow:0 1px 3px rgba(0,30,80,.12); background:#fff; }
table.train_ava   { width:100%; border-collapse:separate; border-spacing:0; min-width:980px; }
table.train_ava th{ background:var(--rail); color:#fff; padding:9px 10px; font-family:var(--ta-sans); font-weight:600; font-size:11px; letter-spacing:.4px; text-transform:uppercase; text-align:center; border-right:1px solid rgba(255,255,255,.18); border-bottom:3px solid var(--gold); }
table.train_ava td{ padding:8px 10px; vertical-align:top; border-right:1px solid #E6EDF5; border-bottom:1px solid #E6EDF5; font-family:var(--ta-sans); font-size:12.5px; }
table.train_ava tr.row-first td { border-top:2px solid var(--line); }

/* Row status tints (train_availability console) */
tr.row--service   td { background:#ffffff; }
tr.row--service.row--alt td { background:#FAFCFE; }
tr.row--removed   td { background:var(--rail-wash); }
tr.row--cancelled td { background:#FCF0EE; }
tr.row--reserve   td { background:#FFF8ED; }
tr.tr-hover       td { background:#E7F1FB !important; }

/* Index cell: stripe + number + pill + switch trail + actions */
td.idx-cell       { position:relative; padding-left:14px !important; vertical-align:top !important; min-width:150px; }
td.idx-cell::before { content:""; position:absolute; left:0; top:0; bottom:0; width:3px; }
tr.row--service   td.idx-cell::before { background:var(--c-service); }
tr.row--reserve   td.idx-cell::before { background:var(--c-reserve); }
tr.row--removed   td.idx-cell::before { background:var(--c-removed); }
tr.row--cancelled td.idx-cell::before { background:var(--c-cancelled); }
.idx-num          { display:inline-block; font-family:var(--ta-mono); font-weight:700; font-size:19px; color:var(--rail); line-height:1.1; }
.status-pill      { display:inline-flex; align-items:center; gap:5px; font-size:10px; font-weight:500; border-radius:10px; padding:1px 8px; margin-left:7px; vertical-align:3px; }
.status-pill .led { width:7px; height:7px; border-radius:50%; flex:none; }
.pill--service    { background:#E1F3EA; color:#0F6E4E; } .pill--service   .led { background:var(--c-service); }
.pill--reserve    { background:#FAEEDA; color:#854F0B; } .pill--reserve   .led { background:var(--c-reserve); }
.pill--removed    { background:#E6F1FB; color:#0C447C; } .pill--removed   .led { background:var(--c-removed); }
.pill--cancelled  { background:#FCEBEB; color:#A32D2D; } .pill--cancelled .led { background:var(--c-cancelled); }

/* Switch trail — replaces the original's 7 Switch columns */
.sw-trail         { display:flex; flex-direction:column; align-items:flex-start; gap:3px; margin-top:6px; }
.sw-chip          { display:inline-flex; align-items:center; gap:6px; background:#fff; border:1px solid #C5D8EE; border-left:3px solid var(--gold); border-radius:4px; padding:2px 7px; }
.sw-chip .sw-idx  { font-family:var(--ta-mono); font-weight:700; font-size:12.5px; color:var(--rail); }
.sw-chip .sw-time { font-family:var(--ta-mono); font-size:10.5px; color:var(--mut); }
.sw-chip .sw-drv  { font-size:10px; color:var(--mut); }
.sw-chip .ta-del-sw { text-decoration:none; font-size:12px; color:#B23A33; line-height:1; }
.sw-chip .ta-del-sw.disabled { display:none; }
.idx-actions      { margin-top:6px; visibility:hidden; }
tr.tr-hover .idx-actions { visibility:visible; }

/* Car column — one car per sub-row (operations.php), linked like the original */
td.tc-car-cell    { text-align:center; vertical-align:middle !important; min-width:64px; }
.tc-car           { display:inline-block; font-family:var(--ta-mono); font-size:13px; font-weight:700; color:var(--rail); text-decoration:none; background:var(--rail-wash); border:1px solid #C5D8EE; border-radius:4px; padding:2px 10px; min-width:44px; text-align:center; transition:background .12s,color .12s; float:none !important; width:auto !important; }
.tc-car:hover     { background:var(--rail); color:#fff; border-color:var(--rail); }
.tc-none          { color:#9AA6B6; }

/* Time / slot cells (train_availability console) */
.ta-slot-cell     { padding:8px 10px !important; vertical-align:top !important; min-width:110px; }
.hl-time          { display:inline-block; font-family:var(--ta-mono); font-size:13px; font-weight:700; color:#084298; background:#DCEBFB; border:1px solid #B7D3F2; border-radius:5px; padding:3px 10px; }
.ta-slot-time     { display:block; font-family:var(--ta-mono); font-size:13px; font-weight:700; color:var(--ink); line-height:1.35; }
.ta-slot-driver   { display:block; font-size:11px; color:var(--mut); line-height:1.35; margin-top:2px; }
.ta-slot-actions  { display:block; margin-top:5px; height:20px; visibility:hidden; }
td.td-hover .ta-slot-actions { visibility:visible; }

/* Action chips (train_availability console, verbatim look) */
.ta-act { display:inline-block !important; font-size:10px !important; font-weight:600 !important; text-decoration:none !important; padding:2px 7px !important; border-radius:3px !important; border:1px solid #B8B0A2 !important; background:#F1EEE3 !important; color:var(--rail) !important; line-height:1.5 !important; cursor:pointer !important; margin-right:3px !important; float:none !important; width:auto !important; }
.ta-act:hover        { background:var(--rail) !important; color:#fff !important; border-color:var(--rail) !important; }
.ta-act-cancel       { color:#B23A33 !important; border-color:#DDB5B3 !important; background:#FDF2F2 !important; }
.ta-act-cancel:hover { background:#B23A33 !important; color:#fff !important; border-color:#B23A33 !important; }
.ta-act-sep          { font-size:10px !important; color:#C4BBAE !important; margin:0 2px; }
.ta-act.disabled     { display:none !important; }

/* Remarks + levels */
.ta-remarks       { text-align:left; min-width:200px; }
.ta-remarks a     { color:#19459B; font-weight:500; text-decoration:none; }
.ta-cancelled-flag{ font-weight:700; letter-spacing:1px; color:#A32D2D; text-align:center; vertical-align:middle !important; }
td.lvl            { font-family:var(--ta-mono); color:#854F0B; text-align:center; min-width:44px; }
td.del-cell       { text-align:center; vertical-align:middle !important; }
td.del-cell a     { color:#B23A33; text-decoration:none; font-weight:700; }
td.del-cell a.disabled { display:none; }

/* ── Slide panel (operations.php) — hosts the original generated forms ── */
.ta-overlay       { position:fixed; inset:0; background:rgba(10,25,50,.45); opacity:0; visibility:hidden; transition:opacity .2s; z-index:99998; }
.ta-overlay.active{ opacity:1; visibility:visible; }
.ta-panel         { position:fixed; top:0; right:-900px; width:480px; max-width:96vw; height:100vh; background:var(--paper); box-shadow:-6px 0 24px rgba(0,30,80,.25); transition:right .25s ease; z-index:99999; display:flex; flex-direction:column; font-family:var(--ta-sans); }
.ta-panel.active  { right:0; }
.ta-panel-head    { background:var(--rail); border-bottom:3px solid var(--gold); padding:12px 16px; display:flex; align-items:center; justify-content:space-between; flex:none; }
.ta-panel-head h3 { margin:0; color:#fff; font-size:13px; font-weight:600; letter-spacing:.3px; }
.ta-panel-close   { background:none; border:none; color:rgba(255,255,255,.7); font-size:19px; line-height:1; cursor:pointer; padding:0 2px; }
.ta-panel-close:hover { color:var(--gold); }
.ta-panel-body    { flex:1; overflow-y:auto; padding:16px 18px; }
.ta-panel-foot    { flex:none; background:#fff; border-top:1px solid var(--line); padding:10px 16px; display:flex; justify-content:flex-end; gap:8px; }
.ta-panel-foot .btn { font-size:12px; font-weight:500; padding:6px 16px; border-radius:4px; border:1px solid #C9D6E5; background:#fff; color:#41506A; text-decoration:none; cursor:pointer; }
.ta-panel-foot .btn:hover { background:var(--rail-wash); border-color:var(--rail); color:var(--rail); }
.ta-panel-foot .btn-primary { background:var(--rail); border-color:var(--rail); color:#fff; }
.ta-panel-foot .btn-primary:hover { background:var(--rail-dark); border-color:var(--rail-dark); }
.ta-panel-foot .hint { margin-right:auto; align-self:center; font-size:10px; color:var(--mut); }

/* Incident panel: wider variant hosting incident report.php in an iframe.
   #irPanel.ta-panel--ir (id+class) so this can't lose a specificity tie
   against the base .ta-panel width rule regardless of source order. */
#irPanel.ta-panel--ir { width:820px; }
.ta-panel-body--ir { padding:0; overflow:hidden; position:relative; }
#irFrame           { display:block; width:100%; height:100%; border:0; background:#fff; opacity:0; transition:opacity .15s; }
#irFrame.ready     { opacity:1; }
.ir-loading, .ir-fallback { position:absolute; top:0; right:0; bottom:0; left:0; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px; background:var(--paper); text-align:center; padding:0 30px; }
.ir-loading.hidden, .ir-fallback.hidden { display:none; }
.ir-spinner { width:26px; height:26px; border:3px solid #C9D6E5; border-top-color:var(--rail); border-radius:50%; animation:ir-spin .7s linear infinite; }
@keyframes ir-spin { to { transform:rotate(360deg); } }
.ir-loading span, .ir-fallback p { font-size:12px; color:var(--mut); }
.ir-fallback strong { color:var(--ink); font-size:13px; }
.ir-fallback a { color:var(--rail); font-weight:600; text-decoration:none; }
.ir-fallback a:hover { text-decoration:underline; }

/* Original modal-form table CSS, rescoped from #addModal to .ta-panel (verbatim rules) */
#add_form         { width:100%; border-collapse:collapse; font-size:12px; font-family:var(--ta-sans); }
#add_form th      { background:var(--rail); color:#fff; font-size:11px; font-weight:600; letter-spacing:.4px; padding:6px 10px; text-align:left; border-bottom:2px solid var(--gold); }
#add_form td:nth-child(odd)  { background:var(--rail-wash); color:#1A2238; font-weight:600; font-size:11px; padding:7px 10px; white-space:nowrap; width:130px; border-bottom:1px solid var(--line); vertical-align:middle; }
#add_form td:nth-child(even) { background:#fff; padding:5px 10px; border-bottom:1px solid var(--line); vertical-align:middle; }
#add_form td:last-child      { background:var(--paper); text-align:center; padding:10px; }
#add_form td[colspan="2"]    { background:var(--paper); text-align:center; padding:10px; border-bottom:none; }
#add_form td.submit          { background:var(--paper); text-align:center; padding:10px; }
.ta-panel input[type="text"], .ta-panel input[type="number"] { height:28px; font-size:12px; font-weight:400; font-family:var(--ta-sans); border:1px solid #C5D8EE; background:#fff; color:#1A2238; border-radius:4px; padding:0 8px; width:100%; box-sizing:border-box; }
.ta-panel input[type="text"]:focus, .ta-panel input[type="number"]:focus { border-color:var(--rail); outline:none; box-shadow:0 0 0 2px rgba(0,82,155,.12); }
.ta-panel select   { height:28px; font-size:12px; font-family:var(--ta-sans); border:1px solid #C5D8EE; background:#fff; color:#1A2238; border-radius:4px; padding:0 6px; width:100%; box-sizing:border-box; }
.ta-panel select:focus { border-color:var(--rail); outline:none; }
.ta-panel textarea { font-size:12px; font-family:var(--ta-sans); border:1px solid #C5D8EE; background:#fff; color:#1A2238; border-radius:4px; padding:6px 8px; width:100%; box-sizing:border-box; resize:vertical; min-height:70px; }
.ta-panel textarea:focus { border-color:var(--rail); outline:none; box-shadow:0 0 0 2px rgba(0,82,155,.12); }
.ta-panel input[type="submit"], .ta-panel button[type="button"]:not(.ta-panel-close) { height:30px; font-size:12px; font-weight:600; font-family:var(--ta-sans); background:var(--rail); color:#fff; border:1px solid var(--rail); border-radius:4px; padding:0 18px; cursor:pointer; }
.ta-panel input[type="submit"]:hover, .ta-panel button[type="button"]:not(.ta-panel-close):hover { background:var(--rail-dark); border-color:var(--rail-dark); }
.ta-panel input[type="checkbox"] { margin-right:5px; vertical-align:middle; }
.ta-panel select[name="month"], .ta-panel select[name="day"], .ta-panel select[name="year"],
.ta-panel select[name="hour"], .ta-panel select[name="minute"], .ta-panel select[name="amorpm"] { width:auto; display:inline-block; margin-right:4px; }

/* Legacy link classes still referenced by permission flags */
a.Llink:link { color:#FF0000; } a.Llink:visited { color:black; } a.Llink:hover { color:Orange; } a.Llink:active { color:#0000FF; }
a.LEdit:visited { color:blue; } a.LDel:visited { color:red; }
.alink a.disabled { color:#666; text-decoration:none; }

@media (max-width:768px){
	
	.ops-header, .ops-strip, .ops-section { padding-left:10px; padding-right:10px; }
	.ops-table-wrap { margin:8px 6px 12px; }
	.ta-panel { width:100vw; max-width:100vw; }
}
</style>

<script language='javascript' src='ajax.js'></script>
<script language="javascript">
/* ── All JS from train_availability.php. Only the container changed:
      openPanel()/closePanel() replace $('#addModal').modal(), and the
      switch add/delete handlers write chips instead of <td> cells. ── */
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

/* Panel titles per form type (panel header replaces the modal's static "Edit Record") */
var panelTitles={ add_train:"Add / Prep Train", unimog:"Add / Prep UNIMOG Train",
	insertion:"Add Insertion", removal:"Add Removal", remarks:"Add / Edit Remarks",
	index_switch:"Switch Index No.", editIndex:"Edit Index No.", editCar:"Edit Train Compo" };

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
		htmlCode+="<tr><td>Train Driver</td><td id='td' name='td'></td></tr>";
		htmlCode+="<tr><td colspan=2 align=center><button type='button' onclick='submitSwitch("+form_id+")'>Submit</button></td></tr></table>";
	}
	else if(form_type=="timetable"){
		htmlCode="<table><tr><th colspan=2>Set Timetable</th></tr>";
		htmlCode+="<tr><td>New Index No.</td><td><input type=text id='new_index_input' /></td></tr>";
		htmlCode+="<tr><td>Time of Switch</td><td id='cell' name='cell'></td></tr>";
		htmlCode+="<tr><td>Train Driver</td><td id='td' name='td'></td></tr>";
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
		/* item #1 fix carried over: Car 4 present so a 4-car set's car_d isn't blanked */
		htmlCode+="<tr><td>Car 4</td><td><input type=text name='car_4' id='car_4' autocomplete='off' onblur='fillCar(\"last2\",this.value)' /></td></tr>";
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
	openPanel(panelTitles[form_type]||"Edit Record");   /* was: $('#addModal').modal('show'); */
	setDate();
	if(form_type=="removal"||form_type=="insertion"||form_type=="index_switch"){
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
	if(confirm("Cancel Train?")) openIncidentPanel("cancel="+train_id,"Cancel Train");
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

/* Switch delete: same processing.php?deleteSwitch endpoint; removes the chip
   instead of rebuilding a <td> (the 7 Switch columns no longer exist). */
function deleteSwitch(index){
	if(!confirm("Cancel Switch?")) return;
	$.get('processing.php',{deleteSwitch:index},function(data){
		$('.sw-chip[data-switch-id="'+index+'"]').remove();
	});
}

/* Switch add: same processing.php?ajaxSwitch endpoint; appends a chip to the
   train's switch trail instead of replacing a placeholder <td>. */
function submitSwitch(train_ava_id){
	var new_index=document.getElementById('new_index_input').value;
	if(new_index==''){alert('Please enter a new index number.');return;}
	var driverEl=document.getElementById('train_driver');       /* populated by setHTML() into the 'td' cell */
	var train_driver=driverEl?driverEl.value:'';
	var driverLabel=driverEl?driverEl.options[driverEl.selectedIndex].text:'';
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
	/* NOTE: passes train_driver alongside the existing params. processing.php's
	   ajaxSwitch handler must accept it and write it to train_switch.train_driver
	   (the display code already reads that column) -- confirm/update that handler,
	   it wasn't part of the files provided here. */
	$.get('processing.php',{ajaxSwitch:1,train_ava_id:train_ava_id,new_index:new_index,switch_time:switch_time,train_driver:train_driver},function(data){
		var res=JSON.parse(data);
		if(res.status=='ok'){
			closePanel();
			var newChip='<span class="sw-chip" data-switch-id="'+res.switch_id+'">'
				+'<span class="sw-idx">'+new_index+'</span>'
				+'<span class="sw-time">'+hh+':'+mm+'</span>'
				+'<span class="sw-drv">'+driverLabel+'</span>'
				+'<a class="ta-del-sw" href="#" onclick=\'deleteSwitch("'+res.switch_id+'")\' aria-label="Delete switch">&times;</a>'
				+'</span>';
			$('.sw-trail[data-train-id="'+train_ava_id+'"]').append(newChip);
		}
	});
}

function deleteRow(index){
	if(confirm("Remove Record?")) makeajax("processing.php?removeRow="+index,"reloadPage");
}

function reloadPage(ajaxHTML){ self.location="<?php echo $selfPage; ?>"; }

function fillCar(position,car){
	/* item #1 fix carried over: null-safe reads -- a modal missing one of the four
	   fields used to throw here, killing the duplicate check. */
	function carVal(id){ var el=document.getElementById(id); return ((el)?el.value:"")*1; }
	var car_a=carVal('car_1');
	var car_b=carVal('car_2');
	var car_c=carVal('car_3');
	var car_d=carVal('car_4');
	var field="car_1", counter=0;
	if(position=="first"){ field="car_1"; if(car!=""){ if(car==car_b)counter++; if(car==car_c)counter++; if(car==car_d)counter++; } }
	else if(position=="mid")  { field="car_2"; if(car!=""){ if(car==car_a)counter++; if(car==car_c)counter++; if(car==car_d)counter++; } }
	else if(position=="last") { field="car_3"; if(car!=""){ if(car==car_a)counter++; if(car==car_b)counter++; if(car==car_d)counter++; } }
	else if(position=="last2"){ field="car_4"; if(car!=""){ if(car==car_a)counter++; if(car==car_b)counter++; if(car==car_c)counter++; } }
	if(counter>0){ alert("Car already in Compo of Train!"); document.getElementById(field).value=""; }
	else { makeajax("processing.php?checkCar="+car+"&car="+field,"confirmCar"); }
	/* was: $('#addModal').modal('show'); -- the panel stays open, no re-show needed */
}

function confirmCar(ajaxHTML){
	if(ajaxHTML!="No car"){ alert("Car already in Compo of another Train!"); document.getElementById(ajaxHTML).value=""; }
}

/* ── Slide panel (replaces the Bootstrap modal) ── */
function openPanel(title){
	document.getElementById('ta-panel-title').textContent=title||"Edit Record";
	document.getElementById('taPanel').classList.add('active');
	document.getElementById('taOverlay').classList.add('active');
}
function closePanel(){
	document.getElementById('taPanel').classList.remove('active');
	document.getElementById('taOverlay').classList.remove('active');
}
document.addEventListener('keydown',function(e){ if(e.key==='Escape'){ closePanel(); closeIncidentPanel(); } });

/* ── Incident panel: hosts "incident report.php" in an iframe (embed=1
      discards Tmenu chrome). The page posts to itself inside the iframe;
      on save its cancel/add_incident branch postMessages 'ir-saved' and
      we reload so the new incident/levels show on the row.
      Loading state: the iframe fades in on load; if nothing's loaded after
      6s (blocked by a security header, 404'd filename, etc.) a fallback
      offers a direct link instead of leaving a blank panel. ── */
var irLoadTimer=null, irExpectingLoad=false, irNeedsReload=false;
function openIncidentPanel(query,title){
	var url="incident report.php?"+query+"&embed=1";
	document.getElementById('ir-panel-title').textContent=title||"Incident Report";
	document.getElementById('irFallbackLink').href="incident report.php?"+query; /* no embed=1: full standalone page */
	var frame=document.getElementById('irFrame');
	frame.classList.remove('ready');
	document.getElementById('irLoading').classList.remove('hidden');
	document.getElementById('irFallback').classList.add('hidden');
	clearTimeout(irLoadTimer);
	irExpectingLoad=true;
	frame.src=url;
	document.getElementById('irPanel').classList.add('active');
	document.getElementById('taOverlay').classList.add('active');
	irLoadTimer=setTimeout(function(){
		if(irExpectingLoad) document.getElementById('irFallback').classList.remove('hidden');
	},6000);
}
function openEditIncidentPanel(query,title){
	var url="edit_ccdr.php?ir="+query+"&embed=1";
	document.getElementById('ir-panel-title').textContent=title||"Incident Report Details";
	document.getElementById('irFallbackLink').href="edit_ccdr.php?ir="+query; /* no embed=1: full standalone page */
	var frame=document.getElementById('irFrame');
	frame.classList.remove('ready');
	document.getElementById('irLoading').classList.remove('hidden');
	document.getElementById('irFallback').classList.add('hidden');
	clearTimeout(irLoadTimer);
	irExpectingLoad=true;
	frame.src=url;
	document.getElementById('irPanel').classList.add('active');
	document.getElementById('taOverlay').classList.add('active');
	irLoadTimer=setTimeout(function(){
		if(irExpectingLoad) document.getElementById('irFallback').classList.remove('hidden');
	},6000);
}


function irFrameLoaded(){
	if(!irExpectingLoad) return; /* ignore the about:blank resets from closeIncidentPanel/initial markup */
	irExpectingLoad=false;
	clearTimeout(irLoadTimer);
	document.getElementById('irLoading').classList.add('hidden');
	document.getElementById('irFallback').classList.add('hidden');
	document.getElementById('irFrame').classList.add('ready');
}
function closeIncidentPanel(){
	var p=document.getElementById('irPanel');
	if(!p) return;
	p.classList.remove('active');
	clearTimeout(irLoadTimer);
	irExpectingLoad=false;
	document.getElementById('irFrame').src="about:blank"; /* drop any half-filled form */
	if(!document.getElementById('taPanel').classList.contains('active'))
		document.getElementById('taOverlay').classList.remove('active');
	if(irNeedsReload){ irNeedsReload=false; self.location="<?php echo $selfPage; ?>"; } /* pick up field edits saved inside the panel */
}
window.addEventListener('message',function(e){
	if(e.data==='ir-saved'){ closeIncidentPanel(); self.location="<?php echo $selfPage; ?>"; }
	/* edit_ccdr.php (embed=1) announces each saved field edit with 'sp:saved'.
	   Unlike 'ir-saved' the panel stays open -- editing a CCDR is usually a
	   run of several field edits -- we just remember the table behind is now
	   stale; closeIncidentPanel() reloads once when the user is done. */
	if(e.data==='sp:saved'){ irNeedsReload=true; }
});

/* ── Date prev/today/next (operations.php header) -> posts the existing
      search_date field, so the original session logic is reused untouched. ── */
function navDate(offset){
	var f=document.getElementById('dateNavForm');
	var d;
	if(offset==='today'){ d=new Date(); }
	else { d=new Date(f.getAttribute('data-date')+"T00:00:00"); d.setDate(d.getDate()+offset); }
	var mm=d.getMonth()+1, dd=d.getDate();
	document.getElementById('nav_date').value=d.getFullYear()+"-"+(mm<10?"0"+mm:mm)+"-"+(dd<10?"0"+dd:dd);
	f.submit();
}

/* ── Filter pills (operations.php) — client-side only, no backend change ── */
function filterTrains(status,btn){
	var pills=document.querySelectorAll('.ops-pill');
	for(var i=0;i<pills.length;i++) pills[i].classList.remove('active');
	btn.classList.add('active');
	var rows=document.querySelectorAll('tr[data-train-id]');
	for(var j=0;j<rows.length;j++){
		rows[j].style.display=(status==='all'||rows[j].getAttribute('data-status')===status)?'':'none';
	}
}

$(function(){ $("#search_date").datepicker({changeMonth:true,changeYear:true,showAnim:"clip"}); });
</script>

<body>
<div style="clear:both;height:0;font-size:0;line-height:0"></div>

<?php
/* ── Date resolution (verbatim from train_availability.php) ── */
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

/* ── Resolve display date and session month/day/year (verbatim) ── */
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

/* ── Permission flags (verbatim, including the toolbar override quirk) ── */
if($ULev>=2){
	$SRemove="Llink"; $SRemove2="two pull-left"; $SRemove3="liR grow";
	$SRemove4="LEdit"; $SRemove5="LDel";
} else {
	$SRemove="disabled"; $SRemove2="disabled"; $SRemove3="disabled";
	$SRemove4="disabled"; $SRemove5="disabled";
}
$SRemove4="enabled"; /* toolbar always enables add/unimog (original behavior) */

/* ── Timetable (verbatim lookup; link restyled for the white info strip) ── */
$timeTableRS=db_query($db,"select *,timetable_day.id as timeId from timetable_day
	inner join timetable_code on timetable_day.timetable_code=timetable_code.id
	where train_date=?",array($availability_date_code));
if($timeTableRS->num_rows>0){
	$ttRow=$timeTableRS->fetch_assoc();
	$ttCode=$ttRow['code'];
	$ttLink='<a href=\'#\' onclick=\'window.open("timetable_set.php?reset='.$ttRow['timeId'].'","code","height=300,width=350")\'>Set/Reset</a>';
} else {
	$ttCode="______";
	$ttLink='<a href=\'#\' onclick=\'window.open("timetable_set.php?set=1","code","height=300,width=350")\'>Set/Reset</a>';
}

/* ── Header action buttons (same onclicks/gating; operations styling) ── */
if($SRemove!="disabled"){
	$addTrainBtn='<a href=\'#\' class="ops-act" onclick=\'changeForm("add_train","","")\'><i class="ti ti-plus" aria-hidden="true"></i> Add Train</a>';
	$unimogBtn  ='<a href=\'#\' class="ops-act" onclick=\'changeForm("unimog","","")\'>UNIMOG</a>';
} else {
	$addTrainBtn='<span class="ops-act disabled">+ Add Train</span>';
	$unimogBtn  ='<span class="ops-act disabled">UNIMOG</span>';
}
?>

<div class="ta-ops">

<!-- ── PAGE HEADER (operations.php two-row header, Line 3 skin) ── -->
<div class="ops-header">
	<div class="ops-title">
		<h1>Train Operations</h1>
		<div class="sub">Availability &amp; Operations Log &mdash; Line 3</div>
	</div>
	<div class="ops-datebar">
		<button type="button" class="ops-nav-btn" onclick="navDate(-1)" title="Previous day">&lsaquo;</button>
		<button type="button" class="ops-nav-btn" onclick="navDate('today')" title="Go to today">Today</button>
		<button type="button" class="ops-nav-btn" onclick="navDate(1)" title="Next day">&rsaquo;</button>
		<form action='<?php echo $selfPage; ?>' method='post'>
			<input type="text" name='search_date' id='search_date' placeholder="<?php echo $availability_date_code; ?>" autocomplete="off">
			<input type="submit" value="Go" class="ops-go">
		</form>
	</div>
	<div class="ops-actions">
		<?php echo $addTrainBtn; echo $unimogBtn; ?>
		<a href='#' class="ops-act ops-act--gold" onclick='window.open("generate_tar.php?tar=<?php echo $availability_date_code; ?>");'><i class="ti ti-printer" aria-hidden="true"></i> Generate Printout</a>
	</div>
</div>

<!-- Hidden form used by the prev/today/next buttons (posts search_date as usual) -->
<form id="dateNavForm" action="<?php echo $selfPage; ?>" method="post" data-date="<?php echo $availability_date_code; ?>" style="display:none">
	<input type="hidden" name="search_date" id="nav_date" value="">
</form>

<!-- ── INFO STRIP (operations.php bottom header row) ── -->
<div class="ops-strip">

<?php
/**
	<div class="ops-info">
		<i class="ti ti-file-text" aria-hidden="true"></i>
		<div><span class="lbl">Timetable Code</span>
			<span class="val"><?php echo $ttCode; ?> <?php echo $ttLink; ?></span></div>
	</div>
	
	*/ ?>
	<div class="ops-info">
		<i class="ti ti-calendar-event" aria-hidden="true"></i>
		<div><span class="lbl">Date</span>
			<span class="val"><?php echo $availability_date; ?></span></div>
	</div>
	<div class="ops-info">
		<i class="ti ti-sun" aria-hidden="true"></i>
		<div><span class="lbl">Day</span>
			<span class="val"><?php echo $dayOfWeek; ?></span></div>
	</div>
</div>

<!-- ── SECTION HEADER + FILTER PILLS (operations.php) ── -->
<div class="ops-section">
	<h2><i class="ti ti-clipboard-list" aria-hidden="true"></i> Operations Log</h2>
	<div class="ops-pills">
		<button type="button" class="ops-pill active" onclick="filterTrains('all',this)">All Trains</button>
		<button type="button" class="ops-pill" onclick="filterTrains('service',this)">In Service</button>
		<button type="button" class="ops-pill" onclick="filterTrains('removed',this)">Removed</button>
		<button type="button" class="ops-pill" onclick="filterTrains('cancelled',this)">Cancelled</button>
		<button type="button" class="ops-pill" onclick="filterTrains('reserve',this)">Reserve</button>
	</div>
</div>

<!-- ── DATA TABLE (operations.php refined table: one car per sub-row) ── -->
<div class="ops-table-wrap">
<table class='train_ava'>
<tr>
	<th>Index No.</th>
	<th>Car</th>
	<th>Time on I336</th>
	<th>Inserted</th>
	<th>Removed</th>
	<th>Remarks/Cause of Failure/Removal</th>
	<th>L2</th>
	<th>L3</th>
	<th>L4</th>
	<th>&nbsp;</th>
</tr>

<?php
$availability_date = $availability_date_code;

/* Same JOIN as train_availability.php */
$sql="
	SELECT ta.*,
		tat.boundary_time, tat.insert_time, tat.insert_driver, tat.inserted_to,
		tat.remove_time, tat.remove_driver, tat.removed_from, tat.removal_remarks
	FROM train_availability ta
	LEFT JOIN train_ava_time tat ON tat.train_ava_id = ta.id
	WHERE ta.date BETWEEN ? AND ?
	ORDER BY ta.date
";
$rs  = db_query($db, $sql, array($availability_date." 00:00:00", $availability_date." 23:59:59"));
$nm  = $rs->num_rows;
$SRemove4 = "enabled";

for($i=0; $i<$nm; $i++){
	$row  = $rs->fetch_assoc();
	$row2 = $row;

	/* ── Row status class + filter status (branches verbatim) ── */
	$removed = ($row2['remove_time']!="" && $row2['remove_time']!="0000-00-00 00:00:00");
	if($row['status']=="cancelled"){
		$rowClass = "row--cancelled";   $dataStatus = "cancelled";
	} elseif(!$removed && $row['status']=="active"){
		$rowClass = ($i%2>0) ? "row--service row--alt" : "row--service";
		$dataStatus = "service";
	} elseif(!$removed){
		$rowClass = "row--reserve";     $dataStatus = "reserve";
	} else {
		$rowClass = "row--removed";     $dataStatus = "removed";
	}

	/* ── Status pill (verbatim) ── */
	if($row['status']=="cancelled"){
		$pill = '<span class="status-pill pill--cancelled"><span class="led"></span>Cancelled</span>';
	} elseif($removed){
		$pill = '<span class="status-pill pill--removed"><span class="led"></span>Removed</span>';
	} elseif($row['status']=="active"){
		$pill = '<span class="status-pill pill--service"><span class="led"></span>In service</span>';
	} else {
		$pill = '<span class="status-pill pill--reserve"><span class="led"></span>Reserve</span>';
	}

	/* ── Switch trail: chips in the Index cell replace the 7 Switch columns.
	      Same query/order; the min(...,7) cap is gone since chips don't need
	      fixed columns. Add via panel form, delete via chip's ×. ── */
	$rs3  = db_query($db,"select * from train_switch where train_ava_id=? order by date_change",array($row['id']));
	$nm3  = $rs3->num_rows;
	$chips = "";
	for($n=0; $n<$nm3; $n++){
		$row3 = $rs3->fetch_assoc();
		$swDriver = ($row3['train_driver']!="") ? getTrainDriver($row3['train_driver'], $db) : "";
		$chips .= '<span class="sw-chip" data-switch-id="'.$row3['id'].'">'
			.'<span class="sw-idx">'.htmlspecialchars($row3['new_index']).'</span>'
			.'<span class="sw-time">'.date("H:i",strtotime($row3['date_change'])).'</span>'
			.'<span class="sw-drv">'.htmlspecialchars($swDriver).'</span>'
			.'<a class="ta-del-sw '.$SRemove5.'" href=\'#\' onclick=\'deleteSwitch("'.$row3['id'].'")\' aria-label="Delete switch">&times;</a>'
			.'</span>';
	}
	$switchTrail = '<div class="sw-trail" data-train-id="'.$row['id'].'">'.$chips.'</div>';

	/* ── Train compo -> per-sub-row cars (operations.php layout) ── */
	$carsArr = array();
	foreach(['car_a','car_b','car_c','car_d'] as $car_key){
		if(!empty($row[$car_key])) $carsArr[] = $row[$car_key];
	}
	$spanN = max(count($carsArr), 1);

	/* ── Boundary time (verbatim) ── */
	$boundary_time = ($row2['boundary_time']!="" && $row2['boundary_time']!="0000-00-00 00:00:00")
		? date("H:i",strtotime($row2['boundary_time'])) : "";

	/* ── Train type string for insertion/removal form (verbatim) ── */
	$trainType = "";
	if($row['type']=="unimog")   $trainType="unimog";
	elseif($row['type']=="test") $trainType="test";
	elseif($row['type']=="reserve")   $trainType="reserve";
	elseif($row['type']=="schooling") $trainType="schooling";

	/* ── Resolve insert/remove/incidents/levels (verbatim) ── */
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
		/* Incidents and level clauses (verbatim) */
		$cancelRS  = db_query($db,"select * from train_incident_view where train_ava_id=?",array($row['id']));
		$cancelNM  = $cancelRS->num_rows;
		$l2Count=0; $l3Count=0; $l4Count=0;
		if($cancelNM>0){
			$incidentClause = "<br>See ";
			for($m=0;$m<$cancelNM;$m++){
				$cancelRow = $cancelRS->fetch_assoc();
				$level     = $cancelRow['level'];
				$order     = getLevel($cancelRow['incident_id'],$db);
				$incLink   = "<a href='#' class='$SRemove' onclick='openEditIncidentPanel(\"".$cancelRow['incident_id']."\",\"Incident Details &mdash; Index ".$row['index_no']."\")'>IN ".$cancelRow['incident_no']."</a>";
				$incidentClause .= ($m==0) ? $incLink : ",<br>".$incLink;
				if($level==2){ $level2Clause.=($l2Count>0?",<br>":"").getOrdinal($order); $l2Count++; }
				elseif($level==3){ $level3Clause.=($l3Count>0?",<br>":"").getOrdinal($order); $l3Count++; }
				elseif($level==4){ $level4Clause.=($l4Count>0?",<br>":"").getOrdinal($order); $l4Count++; }
			}
		}
		if($level2Clause=="") $level2Clause="&nbsp;";
		if($level3Clause=="") $level3Clause="&nbsp;";
		if($level4Clause=="") $level4Clause="&nbsp;";

		/* ── Active row data cells ── */
		$insertDisplay   = ($inserted_to.$insert_time!="") ? $inserted_to.$insert_time : "&ndash;";
		$removeDisplay   = ($removed_from.$remove_time!="") ? $removed_from.$remove_time : "&ndash;";
		$insertDrDisplay = str_replace("SUP","STDO",$insert_driver);
		$removeDrDisplay = str_replace("SUP","STDO",$remove_driver);

		$dataCells  = '<td rowspan='.$spanN.' class="ta-slot-cell">'
			.(($boundary_time!="") ? '<span class="hl-time">'.$boundary_time.'</span>' : '&ndash;')
			.'</td>';
		$SRemove4   = "enabled";
		$dataCells .= '<td rowspan='.$spanN.' class="ta-slot-cell"><div class="ta-slot">'
			.'<div class="ta-slot-time">'.$insertDisplay.'</div>'
			.'<div class="ta-slot-driver">'.$insertDrDisplay.'</div>'
			.'<div class="ta-slot-actions">'
			.'<a href=\'#\' class="ta-act '.$SRemove4.'" onclick=\'changeForm("insertion","'.$row['id'].'",'
			.'"'.$trainType.'")\'>Edit</a>'
			.'<span class="ta-act-sep">&middot;</span>'
			.'<a href=\'#\' class="ta-act ta-act-cancel '.$SRemove.'" onclick=\'cancelTrain("'.$row['id'].'")\'>Cancel</a>'
			.'</div></div></td>';
		$dataCells .= '<td rowspan='.$spanN.' class="ta-slot-cell"><div class="ta-slot">'
			.'<div class="ta-slot-time">'.$removeDisplay.'</div>'
			.'<div class="ta-slot-driver">'.$removeDrDisplay.'</div>'
			.'<div class="ta-slot-actions">'
			.'<a href=\'#\' class="ta-act '.$SRemove4.'" onclick=\'changeForm("removal","'.$row['id'].'",'
			.'"'.$trainType.'")\'>Edit</a>'
			.'</div></div></td>';
		$remarksEsc = htmlspecialchars(str_replace(["\r","\n"], ' ', $remove_remarks), ENT_QUOTES);
		$dataCells .= '<td rowspan='.$spanN.' class="ta-remarks">'.$remove_remarks.$incidentClause
			.'<br><a href=\'#\' class="ta-act" onclick=\'changeForm("remarks","'.$row['id'].'","'.$remarksEsc.'")\''
			.'><i class="ti ti-edit" aria-hidden="true"></i>&nbsp;Add/Edit Remarks</a>'
			.' <a href=\'#\' class="ta-act" onclick=\'openIncidentPanel("add_incident='.$row['id'].'","Add Incident &mdash; Index '.$row['index_no'].'")\''
			.'>Add Incident</a>'
			.'</td>';
		$dataCells .= '<td rowspan='.$spanN.' class="lvl">'.$level2Clause.'</td>'
			.'<td rowspan='.$spanN.' class="lvl">'.$level3Clause.'</td>'
			.'<td rowspan='.$spanN.' class="lvl">'.$level4Clause.'</td>';

	} elseif($row['status']=="cancelled"){
		/* ── Cancelled branch: incidents, levels, CANCELLED label (logic verbatim) ── */
		$cancelRS  = db_query($db,"select * from train_incident_view inner join level on train_incident_view.incident_id=level.incident_id where train_ava_id=?",array($row['id']));
		$cancelNM  = $cancelRS->num_rows;
		$l2Count=0; $l3Count=0; $l4Count=0;
		for($m=0;$m<$cancelNM;$m++){
			$cancelRow = $cancelRS->fetch_assoc();
			$level     = $cancelRow['level'];
			$order     = getLevel($cancelRow['incident_id'],$db);
			$incLink   = "<a href='#' onclick='openEditIncidentPanel(\"".$cancelRow['incident_id']."\",\"Incident Details &mdash; Index ".$row['index_no']."\")'>IN ".$cancelRow['incident_no']."</a>";
			$incidentClause .= ($m==0) ? $incLink : ",<br>".$incLink;
			if($level==2){ $level2Clause.=($l2Count>0?",<br>":"").getOrdinal($order); $l2Count++; }
			elseif($level==3){ $level3Clause.=($l3Count>0?",<br>":"").getOrdinal($order); $l3Count++; }
			elseif($level==4){ $level4Clause.=($l4Count>0?",<br>":"").getOrdinal($order); $l4Count++; }
		}
		if($level2Clause=="") $level2Clause="&nbsp;";
		if($level3Clause=="") $level3Clause="&nbsp;";
		if($level4Clause=="") $level4Clause="&nbsp;";

		/* Original spanned 6/5 columns (it still had Switch + Compo columns);
		   here the same content spans I336+Inserted+Removed (3) or Inserted+Removed (2). */
		if($boundary_time==""){
			$dataCells = '<td rowspan='.$spanN.' colspan=3 class="ta-cancelled-flag">CANCELLED</td>';
		} else {
			$dataCells = '<td rowspan='.$spanN.' class="ta-slot-cell"><span class="hl-time">'.$boundary_time.'</span></td>'
				.'<td rowspan='.$spanN.' colspan=2 class="ta-cancelled-flag">CANCELLED</td>';
		}
		$remarksEsc = htmlspecialchars(str_replace(["\r","\n"], ' ', $remove_remarks), ENT_QUOTES);
		$dataCells .= '<td rowspan='.$spanN.' class="ta-remarks">'.$remove_remarks.$incidentClause
			.'<br><a href=\'#\' class="ta-act" onclick=\'changeForm("remarks","'.$row['id'].'","'.$remarksEsc.'")\''
			.'><i class="ti ti-edit" aria-hidden="true"></i>&nbsp;Add/Edit Remarks</a>'
			.' <a href=\'#\' class="ta-act" onclick=\'openIncidentPanel("add_incident='.$row['id'].'","Add Incident &mdash; Index '.$row['index_no'].'")\''
			.'>Add Incident</a>'
			.'</td>';
		$dataCells .= '<td rowspan='.$spanN.' class="lvl">'.$level2Clause.'</td>'
			.'<td rowspan='.$spanN.' class="lvl">'.$level3Clause.'</td>'
			.'<td rowspan='.$spanN.' class="lvl">'.$level4Clause.'</td>';
	} else {
		/* Reserve/unimog/test with no active status yet. Original emitted no
		   cells at all here (short row); padded with blanks to keep alignment. */
		$dataCells = str_repeat('<td rowspan='.$spanN.'>&nbsp;</td>', 7);
	}

	/* ── Index cell: number + pill + switch trail + hover actions ── */
	$idxCell = '<td class="idx-cell" rowspan='.$spanN.'>'
		.'<span class="idx-num">'.$row['index_no'].'</span>'.$pill
		.$switchTrail
		.'<div class="idx-actions">'
		.'<a href=\'#\' class="ta-act '.$SRemove4.'" onclick=\'changeForm("editIndex","'.$row['id'].'","")\'>Edit Index</a>'
		.'<a href=\'#\' class="ta-act '.$SRemove4.'" onclick=\'changeForm("index_switch","'.$row['id'].'","")\'><i class="ti ti-arrows-exchange" aria-hidden="true"></i> Switch</a>'
		.'<a href=\'#\' class="ta-act '.$SRemove4.'" onclick=\'changeForm("editCar","'.$row['id'].'","")\'>&#9998; Compo</a>'
		.'</div>'
		.'</td>';

	/* ── Car cell builder (car -> car_history link, blank -> dash) ── */
	$carCells = array();
	for($r=0; $r<$spanN; $r++){
		if(isset($carsArr[$r])){
			$carCells[$r] = '<td class="tc-car-cell"><a href=\'car_history.php?car_id='.$carsArr[$r].'\' target=\'_blank\' class="tc-car">'.$carsArr[$r].'</a></td>';
		} else {
			$carCells[$r] = '<td class="tc-car-cell"><span class="tc-none">&mdash;</span></td>';
		}
	}

	$delCell = '<td class="del-cell" rowspan='.$spanN.'><a href=\'#\' class="'.$SRemove5.'" onclick=\'deleteRow("'.$row['id'].'")\'>X</a></td>';

	/* ── Emit: first row carries the spanned cells; sub-rows carry one car each ── */
	echo '<tr data-train-id="'.$row['id'].'" data-status="'.$dataStatus.'" class="'.$rowClass.' row-first">'
		.$idxCell
		.$carCells[0]
		.$dataCells
		.$delCell
		.'</tr>';
	for($r=1; $r<$spanN; $r++){
		echo '<tr data-train-id="'.$row['id'].'" data-status="'.$dataStatus.'" class="'.$rowClass.'">'
			.$carCells[$r]
			.'</tr>';
	}
}
?>

</table>
</div><!-- /.ops-table-wrap -->

</div><!-- /.ta-ops -->

<!-- ── SLIDE PANEL (operations.php pattern) — hosts the same #add_form the
        POST handlers expect; footer Submit uses form='add_form' like the
        original modal footer did. ── -->
<div class="ta-overlay" id="taOverlay" onclick="closePanel();closeIncidentPanel()"></div>
<div class="ta-panel" id="taPanel" role="dialog" aria-modal="true" aria-labelledby="ta-panel-title">
	<div class="ta-panel-head">
		<h3 id="ta-panel-title">Edit Record</h3>
		<button type="button" class="ta-panel-close" onclick="closePanel()" aria-label="Close">&times;</button>
	</div>
	<div class="ta-panel-body">
		<form name='add_form' id='add_form' action='<?php echo $selfPage; ?>' method='post'></form>
	</div>
	<div class="ta-panel-foot">
		<span class="hint">Esc closes</span>
		<a href="#" class="btn" onclick="closePanel();return false;">Close</a>
		<button type='submit' form='add_form' class="btn btn-primary" id='Suc' value='Submit'>Submit</button>
	</div>
</div>

<!-- ── INCIDENT PANEL — same slide-panel pattern, body is an iframe onto
        "incident report.php?...&embed=1". All incident logic (equipment
        picker, links, presets, POST handling) stays in that file. ── -->
<div class="ta-panel ta-panel--ir" id="irPanel" role="dialog" aria-modal="true" aria-labelledby="ir-panel-title">
	<div class="ta-panel-head">
		<h3 id="ir-panel-title">Incident Report</h3>
		<button type="button" class="ta-panel-close" onclick="closeIncidentPanel()" aria-label="Close">&times;</button>
	</div>
	<div class="ta-panel-body ta-panel-body--ir">
		<iframe id="irFrame" src="about:blank" title="Incident Report" onload="irFrameLoaded()"></iframe>
		<div class="ir-loading" id="irLoading">
			<div class="ir-spinner"></div>
			<span>Loading incident form&hellip;</span>
		</div>
		<div class="ir-fallback hidden" id="irFallback">
			<strong>This is taking longer than expected.</strong>
			<p>The form may be blocked from loading inside this panel.<br>You can open it directly instead:</p>
			<a href="#" id="irFallbackLink" target="_blank" rel="noopener">Open Incident Report in a new tab &rarr;</a>
		</div>
	</div>
</div>

<script>
/* Slot/row hover — verbatim from train_availability.php; fires across all
   sub-rows of a train via data-train-id */
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