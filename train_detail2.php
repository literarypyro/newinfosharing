<?php
$IR_EMBED = isset($_GET['embed']);
if($IR_EMBED){ ob_start(); }
require("Tmenu.php");
if($IR_EMBED){ ob_end_clean(); }

ini_set("date.timezone","Asia/Kuala_Lumpur");
require_once(dirname(__FILE__)."/db_connect.php");

/* =========================================================================
   train_detail.php -- one trainset, one operating day, as a slide panel.

   WHY A NEW PAGE RATHER THAN A LINK INTO TRAIN OPERATIONS
   ------------------------------------------------------
   Clicking a fleet chip on the dashboard used to jump to
   train_operations_admin.php#tr-<id>. That works, but it costs a full page
   load and a scroll to answer a question that is usually one line long:
   "why is 05 removed?". The operations page is where you EDIT; this is where
   you look. Nothing here writes, so it is safe to open from a wall display.

   READ-ONLY, DELIBERATELY. Every mutation on this data lives in
   train_operations_admin.php, which owns the POST handlers, the driver
   pickers and the switch form. Duplicating any of that here would mean two
   places to keep correct. The panel ends with a link into the real page for
   anyone who needs to change something.

   SCHEMA, as used by train_operations_admin.php:
     train_availability   id, index_no, date, status, type
     train_ava_time       train_ava_id, boundary_time, insert_time,
                          insert_driver, inserted_to, remove_time,
                          remove_driver, removed_from, removal_remarks
     train_switch         train_ava_id, new_index, date_change, train_driver
     train_incident_view  train_ava_id, incident_id, incident_no
     train_compo          tar_id, car_no
   ========================================================================= */

$taId = isset($_GET['ta']) ? (int)$_GET['ta'] : 0;

/* @tt -- Carry the auth token onto the links this page emits.

   Reaching this line at all means Tmenu.php accepted the request, so whatever
   token arrived is a valid one. Reusing it beats restating a literal and it
   survives a token that rotates. The fallback only matters if this page is
   opened by something that sends none -- in which case Tmenu would have
   redirected before we got here. */
$tdTT = (isset($_GET['tt']) && $_GET['tt'] !== '')
	? (string)$_GET['tt']
	: '2a7b85131d93ffbaacc73f7ff024b55a';

/* Driver names resolve through the same helper the operations page uses.
   Guarded on its own name: if this ever gets included somewhere that already
   defines it, the existing one wins rather than fataling. */
if(!function_exists('tdTrainDriver')){
function tdTrainDriver($id,$dbase){
	if((int)$id <= 0){ return ""; }
	$rs = db_query($dbase,"select firstName,lastName,position from train_driver where id=? limit 1",array($id));
	if($rs===false || $rs->num_rows==0){ return (string)$id; }
	$r = $rs->fetch_assoc();
	return trim($r['position']." ".substr($r['firstName'],0,1).". ".$r['lastName']);
}}

/* MySQL zero-dates and blanks all collapse to empty, the same rule dash_ts()
   applies -- a '0000-00-00 00:00:00' rendered as a time is worse than a dash. */
if(!function_exists('tdTime')){
function tdTime($v){
	if($v===null){ return ""; }
	$v = trim((string)$v);
	if($v==="" || substr($v,0,10)==="0000-00-00"){ return ""; }
	$t = strtotime($v);
	return ($t===false || $t<=0) ? "" : date("H:i", $t);
}}
if(!function_exists('tdPoint')){
function tdPoint($v){
	$v = strtolower(trim((string)$v));
	if($v===""){ return ""; }
	return ($v==='quezon') ? 'Quezon Ave.' : 'North Ave.';
}}
if(!function_exists('tdEsc')){
	function tdEsc($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

$train = null;
if($taId > 0){
	$rs = db_query($db,"select * from train_availability where id=? limit 1",array($taId));
	if($rs!==false && $rs->num_rows>0){ $train = $rs->fetch_assoc(); }
}

/* Times row. A train can have no train_ava_time row at all -- rostered but
   never touched -- so every read below is guarded rather than assumed. */
$t = array('boundary_time'=>'','insert_time'=>'','insert_driver'=>'','inserted_to'=>'',
           'remove_time'=>'','remove_driver'=>'','removed_from'=>'','removal_remarks'=>'');
if($train){
	$rs = db_query($db,"select * from train_ava_time where train_ava_id=?",array($taId));
	if($rs!==false && $rs->num_rows>0){
		$row = $rs->fetch_assoc();
		foreach($t as $k=>$_){ if(isset($row[$k])) $t[$k] = $row[$k]; }
	}
}

/* Derived state -- read the timestamps, do not trust a stored status beyond
   'cancelled'. Same precedence train_operations_admin.php uses for its row
   classes, so the panel and the operations table always agree. */
$state = 'pending'; $stateLabel = 'Not yet inserted'; $tone = 'mute';
if($train){
	if(strtolower(trim((string)$train['status']))==='cancelled'){
		$state='cancelled'; $stateLabel='Cancelled'; $tone='bad';
	} elseif(tdTime($t['remove_time'])!==""){
		$state='removed';   $stateLabel='Removed from service'; $tone='info';
	} elseif(tdTime($t['insert_time'])!==""){
		$state='service';   $stateLabel='In service';  $tone='ok';
	} elseif(tdTime($t['boundary_time'])!==""){
		$state='reserve';   $stateLabel='Reserve';     $tone='warn';
	}
}

$switches = array();
if($train){
	$rs = db_query($db,"select * from train_switch where train_ava_id=? order by date_change",array($taId));
	if($rs!==false){ while($r=$rs->fetch_assoc()){ $switches[] = $r; } }
}

$incidents = array();
if($train){
	$rs = db_query($db,"select * from train_incident_view where train_ava_id=?",array($taId));
	if($rs!==false){ while($r=$rs->fetch_assoc()){ $incidents[] = $r; } }
}

$cars = array();
if($train){
	$rs = db_query($db,"select car_no from train_compo where tar_id=? order by id",array($taId));
	if($rs!==false){ while($r=$rs->fetch_assoc()){ $cars[] = $r['car_no']; } }
}

/* The timeline. Built as a list rather than four fixed fields because the
   ORDER is the story -- reserve at 04:40, in at 05:12, out at 09:38 reads as
   a morning; the same four values in a table do not. Only events that
   actually happened are emitted, so a train still in service does not show an
   empty "Removed" row that looks like missing data. */
$events = array();
if(tdTime($t['boundary_time'])!==""){
	$events[] = array('t'=>tdTime($t['boundary_time']), 'k'=>'Reserve',
	                  'd'=>'Placed on standby at the boundary', 'tone'=>'warn');
}
if(tdTime($t['insert_time'])!==""){
	$bits = array();
	if(tdPoint($t['inserted_to'])!==""){ $bits[] = 'at '.tdPoint($t['inserted_to']); }
	$dv = tdTrainDriver($t['insert_driver'],$db);
	if($dv!==""){ $bits[] = 'driver '.$dv; }
	$events[] = array('t'=>tdTime($t['insert_time']), 'k'=>'Inserted',
	                  'd'=>(count($bits) ? implode(' &middot; ', array_map('tdEsc',$bits)) : 'Entered revenue service'),
	                  'tone'=>'ok', 'raw'=>true);
}
foreach($switches as $sw){
	$events[] = array('t'=>tdTime($sw['date_change']), 'k'=>'Index switch',
	                  'd'=>'Became index '.tdEsc($sw['new_index']), 'tone'=>'mute', 'raw'=>true);
}
if(tdTime($t['remove_time'])!==""){
	$bits = array();
	if(tdPoint($t['removed_from'])!==""){ $bits[] = 'from '.tdPoint($t['removed_from']); }
	$dv = tdTrainDriver($t['remove_driver'],$db);
	if($dv!==""){ $bits[] = 'driver '.$dv; }
	$events[] = array('t'=>tdTime($t['remove_time']), 'k'=>'Removed',
	                  'd'=>(count($bits) ? implode(' &middot; ', array_map('tdEsc',$bits)) : 'Withdrawn from service'),
	                  'tone'=>'info', 'raw'=>true);
}
?>
<!DOCTYPE html>
<html><head><meta charset="utf-8">
<title>Train detail</title>
<style>
/* Panel-native: this renders inside an iframe with no Tmenu chrome, so it
   carries only what it needs and inherits nothing. Palette matches the
   console -- #00529B structure, #FDB813 accent, never as gridlines. */
*{box-sizing:border-box}
body{margin:0;padding:16px 18px;background:#FAFAF6;color:#1A2238;
	font:13px/1.55 "Segoe UI",system-ui,-apple-system,Roboto,Arial,sans-serif}
h1{margin:0;font-size:19px;font-weight:600;letter-spacing:-.01em}
.td-head{display:flex;align-items:baseline;gap:10px;flex-wrap:wrap;
	border-bottom:2px solid #00529B;padding-bottom:9px;margin-bottom:14px}
.td-sub{font-size:12px;color:#5A6275}
.td-pill{display:inline-block;font-size:11px;font-weight:700;padding:2px 9px;
	border-radius:11px;letter-spacing:.02em}
.t-ok{background:#DFF3E4;color:#1B5E35}
.t-warn{background:#FDF0D2;color:#7A5600}
.t-info{background:#E1ECF8;color:#00437F}
.t-bad{background:#F8DEDE;color:#7A1F1F}
.t-mute{background:#EDEBE3;color:#5A6275}

.td-sec{font-size:11px;text-transform:uppercase;letter-spacing:.07em;
	color:#00529B;font-weight:600;border-bottom:1px solid #E5DECC;
	padding-bottom:4px;margin:18px 0 9px}

/* Timeline. The rule runs behind the dots rather than between them, so a
   single event still looks like a point on a day instead of an orphan. */
.td-line{position:relative;margin:0;padding:0 0 0 78px;list-style:none}
.td-line:before{content:"";position:absolute;left:63px;top:6px;bottom:8px;
	width:2px;background:#E5DECC}
.td-line li{position:relative;margin:0 0 13px}
.td-line li:last-child{margin-bottom:0}
.td-when{position:absolute;left:-78px;top:0;width:46px;text-align:right;
	font-family:Consolas,Menlo,monospace;font-size:12px;color:#5A6275}
.td-dot{position:absolute;left:-19px;top:5px;width:9px;height:9px;border-radius:50%;
	border:2px solid #FAFAF6}
.d-ok{background:#2E7D46}.d-warn{background:#C98A00}.d-info{background:#00529B}
.d-bad{background:#A32B2B}.d-mute{background:#9A968A}
.td-what{font-weight:600}
.td-why{color:#5A6275;font-size:12px}

.td-grid{display:grid;grid-template-columns:auto 1fr;gap:5px 14px;font-size:12.5px}
.td-grid dt{color:#5A6275}
.td-grid dd{margin:0}

.td-remarks{background:#FFF8E8;border:1px solid #F0DFB0;border-left:3px solid #FDB813;
	padding:9px 11px;border-radius:4px;font-size:12.5px;white-space:pre-wrap}
.td-cars span{display:inline-block;font-family:Consolas,Menlo,monospace;font-size:12px;
	background:#EEF3F9;border:1px solid #C5D8EE;border-radius:4px;
	padding:2px 8px;margin:0 4px 4px 0;color:#00529B;font-weight:600}
.td-empty{color:#8A8272;font-style:italic;font-size:12.5px}
.td-foot{margin-top:20px;padding-top:11px;border-top:1px solid #E5DECC}
.td-foot a{color:#00529B;font-weight:600;text-decoration:none;font-size:12.5px}
.td-foot a:hover{text-decoration:underline}
.td-miss{background:#F8DEDE;border:1px solid #E4B9B9;color:#7A1F1F;
	padding:11px 13px;border-radius:4px}
</style>
</head><body>

<?php if(!$train){ ?>
	<div class="td-miss">No trainset record found for this reference. It may have been
	removed from the roster, or the link may be from an older page.</div>
<?php } else { ?>

<div class="td-head">
	<h1>Index <?php echo tdEsc($train['index_no']); ?></h1>
	<span class="td-pill t-<?php echo $tone; ?>"><?php echo tdEsc($stateLabel); ?></span>
	<span class="td-sub"><?php
		echo tdEsc(date("l, d F Y", strtotime($train['date'])));
		if(trim((string)$train['type'])!==''){ echo ' &middot; '.tdEsc($train['type']); }
	?></span>
</div>

<div class="td-sec">What happened</div>
<?php if(!count($events)){ ?>
	<p class="td-empty">Nothing recorded for this trainset today &mdash; rostered but not yet
	placed on standby or inserted.</p>
<?php } else { ?>
<ul class="td-line">
<?php foreach($events as $e){ ?>
	<li>
		<span class="td-when"><?php echo tdEsc($e['t']); ?></span>
		<span class="td-dot d-<?php echo $e['tone']; ?>"></span>
		<div class="td-what"><?php echo tdEsc($e['k']); ?></div>
		<div class="td-why"><?php echo isset($e['raw']) ? $e['d'] : tdEsc($e['d']); ?></div>
	</li>
<?php } ?>
</ul>
<?php } ?>

<?php
/* The cancellation is NOT on the timeline. It has no timestamp of its own --
   it is a status on the availability row, not an event in train_ava_time --
   and inventing a position for it would put a guess next to four recorded
   times. It gets its own block instead. */
if($state === 'cancelled'){ ?>
<div class="td-sec">Cancellation</div>
<p style="margin:0 0 9px">This trainset was cancelled for the day. Cancellation is
recorded against the trainset rather than at a time, so it does not appear on the
timeline above.</p>
<?php }
if(trim((string)$t['removal_remarks'])!==''){ ?>
<div class="td-sec"><?php echo $state==='cancelled' ? 'Remarks' : 'Reason for removal'; ?></div>
<div class="td-remarks"><?php echo tdEsc($t['removal_remarks']); ?></div>
<?php } elseif($state==='removed'){ ?>
<div class="td-sec">Reason for removal</div>
<p class="td-empty">No remarks were recorded against this removal.</p>
<?php } ?>

<?php if(count($incidents)){ ?>
<div class="td-sec">Related incidents</div>
<dl class="td-grid">
<?php foreach($incidents as $in){ ?>
	<dt>IN <?php echo tdEsc($in['incident_no']); ?></dt>
	<?php /* edit_ccdr.php needs no token today -- its Tmenu require is commented
	         out -- but sending one costs nothing and stops this link breaking if
	         that guard is ever restored. */ ?>
	<dd><a href="edit_ccdr.php?ir=<?php echo (int)$in['incident_id']; ?>&amp;embed=1&amp;tt=<?php echo tdEsc($tdTT); ?>"
	       target="_top">Open incident report</a></dd>
<?php } ?>
</dl>
<?php } ?>

<?php if(count($switches)){ ?>
<div class="td-sec">Index switches</div>
<dl class="td-grid">
<?php foreach($switches as $sw){
	$dv = tdTrainDriver(isset($sw['train_driver'])?$sw['train_driver']:0, $db); ?>
	<dt><?php echo tdEsc(tdTime($sw['date_change'])); ?></dt>
	<dd>to index <?php echo tdEsc($sw['new_index']); ?><?php
		if($dv!==""){ echo ' &middot; '.tdEsc($dv); } ?></dd>
<?php } ?>
</dl>
<?php } ?>

<?php if(count($cars)){ ?>
<div class="td-sec">Consist</div>
<div class="td-cars"><?php foreach($cars as $c){ ?><span><?php echo tdEsc($c); ?></span><?php } ?></div>
<?php } ?>

<div class="td-foot">
	<?php /* target="_top" so the link escapes the iframe rather than rendering
	         the whole operations page inside a 400px panel. */ ?>
	<a href="train_operations_admin.php?tt=<?php echo tdEsc($tdTT); ?>#tr-<?php echo (int)$train['id']; ?>"
	   target="_top">Open in train operations to edit &rarr;</a>
</div>

<?php } ?>
</body></html>