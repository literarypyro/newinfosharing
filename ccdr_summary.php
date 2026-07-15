<?php
require("Tmenu.php");
ini_set("date.timezone","Asia/Kuala_Lumpur");
?>

<link rel="stylesheet" href="jquery-ui-themes-1.11.1/themes/smoothness/jquery-ui.css" />
<script src="jquery-ui-1.11.1/external/jquery/jquery.js"></script>
<script src="jquery-ui-1.11.1/jquery-ui.js"></script>

<style type='text/css'>
/* =========================================================================
   CCDR SUMMARY -- Line 3 console theme, aligned with train_operations.php /
   edit_ccdr.php / incident_report.php. Scoped under .ccs-page so nothing
   here leaks onto Tmenu.php's own chrome.

   PHP: every query, computation, and echoed value below is unchanged --
   this pass only touches how the results are wrapped and presented.

   Fixed while rebuilding: .ops-act--gold previously referenced var(--gold)
   / var(--gold-ink), neither of which was ever defined in this file, so
   the Generate Printout button was rendering with no background at all
   instead of gold. Now defined properly below.

   Level badge colors reuse edit_ccdr.php's .cc-lvl-0..4 exactly (same
   green/blue/amber/red escalation), so "Level 3" means the same color
   everywhere in the app, not just in this one file.
   ========================================================================= */
:root {
	--cf-blue:      #00529B;
	--cf-blue-dark: #013E76;
	--cf-gold:      #FDB813;
	--cf-gold-ink:  #3A2D00;
	--cf-dark:      #16243B;
	--cf-mid:       #41506A;
	--cf-muted:     #8A95A6;
	--cf-border:    #D2DDEA;
	--cf-row-odd:   #EEF4FB;
	--cf-bg:        #F7F9FC;
	--cf-white:     #ffffff;
	--cf-red:       #A32D2D;
	--cf-red-bg:    #FCEBEB;
	--cf-sans:      "Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif;
}

.ccs-page { font-family:var(--cf-sans); color:var(--cf-dark); }
.ccs-page * { box-sizing:border-box; }

/* ── Page header ── */
.ccs-header      { background:var(--cf-blue); border-bottom:3px solid var(--cf-gold); border-radius:6px 6px 0 0; padding:12px 16px; }
.ccs-header h1   { margin:0; font-size:16px; font-weight:700; color:#fff; letter-spacing:.3px; }
.ccs-header .sub { font-size:10px; color:rgba(255,255,255,.6); letter-spacing:.5px; text-transform:uppercase; margin-top:2px; }

/* ── Toolbar: date retrieve + generate printout ── */
.stat-toolbar { background:var(--cf-blue); padding:10px 16px; margin-bottom:0; }
.stat-toolbar table { border-collapse:collapse; width:100%; }
.stat-toolbar th, .stat-toolbar td { border:none !important; padding:4px 8px; color:#fff; font-weight:600; font-size:13px; text-align:left; }
.stat-toolbar select, .stat-toolbar input[type=text] {
	height:28px; border:1px solid rgba(255,255,255,.5); border-radius:4px;
	background:#fff; color:var(--cf-dark); padding:0 8px; font-size:12px; font-family:var(--cf-sans);
}
.stat-toolbar input[type=submit] {
	height:30px; border:none; border-radius:4px; background:var(--cf-gold);
	color:var(--cf-gold-ink); font-weight:700; font-size:12px; padding:0 16px; cursor:pointer;
}
.stat-toolbar input[type=submit]:hover { background:#E5A50F; }
.ops-act { display:inline-block; font-size:11px; font-weight:600; color:#fff; text-decoration:none;
	padding:6px 14px; border:1px solid rgba(255,255,255,.35); border-radius:4px; cursor:pointer; }
.ops-act:hover { background:rgba(255,255,255,.12); color:#fff; }
.ops-act--gold { background:var(--cf-gold); border-color:var(--cf-gold); color:var(--cf-gold-ink); }
.ops-act--gold:hover { background:#E5A50F; border-color:#E5A50F; color:var(--cf-gold-ink); }

/* ── Panel grid ── */
.ccs-grid { display:flex; gap:16px; align-items:flex-start; flex-wrap:wrap; padding:16px; }
.ccs-panel { background:var(--cf-white); border:1px solid var(--cf-border); border-radius:6px;
	box-shadow:0 1px 3px rgba(0,30,80,.08); flex:1 1 300px; min-width:280px; overflow:hidden; }
.ccs-panel-head { background:var(--cf-blue); border-bottom:3px solid var(--cf-gold); padding:9px 14px; }
.ccs-panel-head h3 { margin:0; font-size:12px; font-weight:700; color:#fff; letter-spacing:.4px; text-transform:uppercase; }
.ccs-panel-body { padding:14px; }

/* ── Data table (faults per discipline) ── */
table.ccdr { width:100%; border-collapse:collapse; font-size:12.5px; }
table.ccdr th { background:var(--cf-blue); color:#fff; font-weight:600; font-size:11px; text-transform:uppercase;
	letter-spacing:.3px; padding:7px 8px; border:1px solid var(--cf-blue-dark); text-align:center; }
table.ccdr td { padding:7px 8px; border:1px solid var(--cf-border); text-align:center; }
table.ccdr tr:nth-child(odd) td { background:var(--cf-row-odd); }
table.ccdr th.ccs-discipline { background:var(--cf-white); color:var(--cf-dark); font-weight:600; text-transform:none;
	letter-spacing:normal; border:1px solid var(--cf-border); text-align:left; }

/* ── Level badges (identical palette to edit_ccdr.php's .cc-lvl-0..4) ── */
.cf-lvl { display:inline-block; font-size:10px; font-weight:700; border-radius:3px; padding:2px 7px; }
.cf-lvl-1 { background:#E8F5EE; color:#0F6E4E; } .cf-lvl-2 { background:#EAF2FB; color:#0C447C; }
.cf-lvl-3 { background:#FAEEDA; color:#854F0B; } .cf-lvl-4 { background:var(--cf-red-bg); color:var(--cf-red); }

/* ── Legend panel ── */
.ccs-legend-row { display:flex; align-items:flex-start; gap:10px; padding:8px 0; border-bottom:1px solid var(--cf-border); }
.ccs-legend-row:last-child { border-bottom:none; }
.ccs-legend-row .cf-lvl { flex:none; margin-top:1px; min-width:34px; text-align:center; }
.ccs-legend-row span.desc { font-size:12.5px; color:var(--cf-mid); line-height:1.4; }

/* ── Stat panel (AM/PM cancellations, loops, LRV) ── */
.ccs-stat-group { display:flex; gap:10px; margin-bottom:14px; }
.ccs-stat-group:last-child { margin-bottom:0; }
.ccs-stat { flex:1; background:var(--cf-bg); border:1px solid var(--cf-border); border-radius:5px; padding:9px 10px; text-align:center; }
.ccs-stat .lbl { display:block; font-size:9.5px; font-weight:600; color:var(--cf-muted); text-transform:uppercase;
	letter-spacing:.4px; margin-bottom:5px; line-height:1.3; }
.ccs-stat .val { display:block; font-size:19px; font-weight:700; color:var(--cf-dark); font-family:ui-monospace,Consolas,monospace; }
.ccs-stat.ccs-stat--danger .val { color:var(--cf-red); }
.ccs-stat.ccs-stat--gold .val { color:#B9840A; }
.ccs-stat-section-label { font-size:10px; font-weight:700; color:var(--cf-muted); text-transform:uppercase;
	letter-spacing:.5px; margin:0 0 6px; }

@media (max-width:900px){ .ccs-grid { flex-direction:column; } }
</style>

<script language='javascript'>
$(function() {
    $( "#search_date" ).datepicker({
      changeMonth: true,
      changeYear: true,
      showAnim: "clip"
    });    
});
</script>

<div class="ccs-page">

<div class="ccs-header">
	<h1>CCDR Summary</h1>
	<div class="sub">Consolidated Corrective &amp; Defect Report &mdash; Line 3</div>
</div>

<table cellspacing="0" cellpadding="0" class='stat-toolbar'>
<tr>
<?php
$mm=date("m");
$yy=date("Y");
$dd=date("d");

$hh=date("h");

$min=date("i");
$aa=date("a");

if(isset($_POST['search_date'])){
	$_SESSION['search_date']=$_POST['search_date'];
	$availability_date=date("Y-m-d",strtotime($_POST['search_date']));
	$datenow=$_POST['search_date'];
}
else {
$datenow=date("m/d/Y");	
	$availability_date=date("Y-m-d");

}
?>

	<td style="padding:8px 14px;vertical-align:middle;white-space:nowrap;width:1%;border:none;">
	<?php echo date("F d, Y",strtotime($availability_date)); ?>
	</td>
	<td style="padding:8px 14px;vertical-align:middle;white-space:nowrap;width:1%;border:none">

<form action='ccdr_summary.php' method='post'>
<input type="text" name='search_date' id='search_date' />

<input type=submit value='Retrieve Date'  />


</form>
	</td>


	<td style="padding:8px 14px;vertical-align:middle;text-align:right;white-space:nowrap;border:none">
<a  class="ops-act ops-act--gold" style=margin-left:10px href='#' onclick='window.open("generate_sccdr.php?sccdr=<?php echo $availability_date; ?>");'><b>Generate Printout</b></a>

	</td>
</tr>
</table>

<div class="ccs-grid">
<div class="ccs-panel" style="flex-basis:340px;">
<div class="ccs-panel-head"><h3>Faults by Discipline</h3></div>
<div class="ccs-panel-body">
<table class='ccdr'>
<tr>
<th class="ccs-discipline" rowspan=2>Discipline</th>
<th colspan=4>Number of Faults per Level</th>
</tr>
<tr>
<th><span class="cf-lvl cf-lvl-1">L1</span></th>
<th><span class="cf-lvl cf-lvl-2">L2</span></th>
<th><span class="cf-lvl cf-lvl-3">L3</span></th>
<th><span class="cf-lvl cf-lvl-4">L4</span></th>
</tr>
<?php
	
	$availability_date=date("Y-m-d");
	if(isset($_POST['search_date'])){
	
	$availability_date=date("Y-m-d",strtotime($_POST['search_date']));
	
	}
	
	if(isset($_SESSION['day'])){
	$year=$_SESSION['year'];
	$month=$_SESSION['month'];
	$day=$_SESSION['day'];
	
	$availability_date=date("Y-m-d",strtotime($_SESSION['search_date']));

	}

	
?>	
<?php
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
$sql="select * from equipment_type where sequence is not null order by sequence";
$rs=$db->query($sql);
$nm=$rs->num_rows;

for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
	$problem_type=$row['equipment_code'];

	for($n=1;$n<=4;$n++){
	
		$incident[$n]=0;
		$condition[$n]=0;
	}
		
	if($problem_type=="rolling"){
		$incident_sql="select count(*) as level_count,level from incident_report where incident_type in ('rolling','unload','nload') and incident_date like '".$availability_date."%%' group by level";
	}
	else {
		$incident_sql="select count(*) as level_count,level from incident_report where incident_type='".$problem_type."' and incident_date like '".$availability_date."%%' group by level";
	}
	
	$incident_rs=$db->query($incident_sql);
	$incident_nm=$incident_rs->num_rows;
	for($n=0;$n<$incident_nm;$n++){
		$incident_row=$incident_rs->fetch_assoc();
		$incident[$incident_row['level']]=$incident_row['level_count'];
	}
	if($problem_type=="rolling"){
		$incident_sql="select count(*) as level_count,level,level_condition from incident_report where incident_type in ('rolling','unload','nload') and incident_date like '".$availability_date."%%' group by level,level_condition";
	}
	else {
		$incident_sql="select count(*) as level_count,level,level_condition from incident_report where incident_type='".$problem_type."' and incident_date like '".$availability_date."%%' group by level,level_condition";
	}

	$incident_rs=$db->query($incident_sql);
	$incident_nm=$incident_rs->num_rows;
	for($n=0;$n<$incident_nm;$n++){
		$incident_row=$incident_rs->fetch_assoc();
		
		if($incident_row['level_condition']==""){
		}
		else {
			$condition[$incident_row['level_condition']]=$incident_row['level_count'];		
			
			
		}
	}
	if($problem_type=="rolling"){
		$am_sql="select sum(cancel) as pm_count from incident_report where incident_type in ('rolling','unload','nload') and incident_date like '".$availability_date."%%'  and level in ('3')";
	}
	else {
		$am_sql="select sum(cancel) as pm_count from incident_report where incident_type='".$problem_type."' and incident_date like '".$availability_date."%%'  and level in ('3')";
	}

	$am_rs=$db->query($am_sql);
	$am_nm=$am_rs->num_rows;

	if($am_nm>0){
		$am_row=$am_rs->fetch_assoc();
		if($problem_type=="rolling"){
			$incident[3]=$am_row['pm_count']*1;
		}
		else {
			$incident[3]=0;
		}
	}

	if($problem_type=="rolling"){
		$am_sql="select sum(cancel) as pm_count from incident_report where incident_type in ('rolling','unload','nload') and incident_date like '".$availability_date."%%'  and level in ('4')";
	}
	else {
		$am_sql="select sum(cancel) as pm_count from incident_report where incident_type='".$problem_type."' and incident_date like '".$availability_date."%%'  and level in ('4')";
	}	

	$am_rs=$db->query($am_sql);
	$am_nm=$am_rs->num_rows;

	if($am_nm>0){
		$am_row=$am_rs->fetch_assoc();
		if($problem_type=="rolling"){
	
			$incident[4]=$am_row['pm_count']*1;
		}
	}

	
	
	
?>

<tr>
	<th class="ccs-discipline"><?php echo $row['equipment_name']; ?> </th>
	<td><?php echo $incident['1']; ?></td>
	<td><?php echo $incident['2']; ?></td>
	<td><?php 
	
	if($condition["1"]==""){
		if($problem_type=="rolling"){
			echo "0/";
		}
	}
	else {
		echo $condition['1']." / ";
	
	}
	echo $incident['3']; 
	?>
	
	
	</td>
	<td><?php 
	if($condition["3"]==""){
		if($problem_type=="rolling"){
			echo "0/";
		}
	
	
	}
	else {
		echo $condition['3']." / ";
	
	}
	
	echo $incident['4']; 
	?></td>
</tr>
<?php
}

?>

</table>
</div>
</div>

<div class="ccs-panel" style="flex-basis:280px;">
<div class="ccs-panel-head"><h3>Level Definitions</h3></div>
<div class="ccs-panel-body">
<div class="ccs-legend-row">
	<span class="cf-lvl cf-lvl-1">L1</span>
	<span class="desc">Fault normalized. No effect on the operation.</span>
</div>
<div class="ccs-legend-row">
	<span class="cf-lvl cf-lvl-2">L2</span>
	<span class="desc">Train is removed with replacement.</span>
</div>
<div class="ccs-legend-row">
	<span class="cf-lvl cf-lvl-3">L3</span>
	<span class="desc">Train is removed without replacement. Cancellation of loops and insertion.</span>
</div>
<div class="ccs-legend-row">
	<span class="cf-lvl cf-lvl-4">L4</span>
	<span class="desc">Service interruption. Cancellation of loops. Ticket refunds.</span>
</div>
</div>
</div>

<div class="ccs-panel" style="flex-basis:320px;">
<div class="ccs-panel-head"><h3>Cancellations &amp; Loop Performance</h3></div>
<div class="ccs-panel-body">

<p class="ccs-stat-section-label">Cancelled Departures &amp; Loops</p>
<?php
/* The original repeated this same pair of header cells via a for($i=0;$i<2;$i++)
   loop purely to produce two label columns (AM/PM); it had no computation
   or side effects. The combined label+value stat boxes below show the
   same information more directly, so that loop isn't needed here. */
?>
<?php
//$am_sql="select count(*) as am_count from train_availability where date like '".$availability_date."%%' and status='cancelled' and date between '".$availability_date." 00:00:00' and '".$availability_date." 12:00:00'";


$am_sql="select sum(cancel) as am_count from incident_report inner join incident_description on incident_report.id=incident_description.incident_id where incident_date between '".$availability_date." 00:00:00' and '".$availability_date." 12:00:00' and level='3' and cancel>=1 and incident_type in ('rolling')";


$am_rs=$db->query($am_sql);
$am_nm=$am_rs->num_rows;

$am=0;
if($am_nm>0){
	$am_row=$am_rs->fetch_assoc();
	$am=$am_row['am_count'];
}

$car_sql3="select sum(cancel) as cancel from incident_report where incident_date between '".$availability_date." 00:00:00' and '".$availability_date." 12:00:00' and incident_type in ('gradual','c_loops')";

//		echo $car_sql3;
$car_rs3=$db->query($car_sql3);
$car_nm3=$car_rs3->num_rows;
if($car_nm3>0){
	$car_row3=$car_rs3->fetch_assoc();
	$am+=$car_row3['cancel']*1;

}

//$am_sql="select count(*) as pm_count from train_availability where date like '".$availability_date."%%' and status='cancelled' and date between '".$availability_date." 12:00:01' and '".$availability_date." 23:59:59'";

$am_sql="select sum(cancel) as pm_count from incident_report inner join incident_description on incident_report.id=incident_description.incident_id where incident_date between '".$availability_date." 12:00:01' and '".$availability_date." 23:59:59' and level='3' and cancel>=1 and incident_type in ('rolling')";

$am_rs=$db->query($am_sql);
$am_nm=$am_rs->num_rows;

$pm=0;
if($am_nm>0){
	$am_row=$am_rs->fetch_assoc();
	$pm=$am_row['pm_count'];
}

$car_sql3="select sum(cancel) as cancel from incident_report where incident_date between '".$availability_date." 12:00:01' and '".$availability_date." 23:59:59' and incident_type in ('gradual','c_loops')";
//		echo $car_sql3;
$car_rs3=$db->query($car_sql3);
$car_nm3=$car_rs3->num_rows;
if($car_nm3>0){
	$car_row3=$car_rs3->fetch_assoc();
	$pm+=$car_row3['cancel']*1;

}



$am_sql="select sum(cancel) as am_count from incident_report where incident_date between '".$availability_date." 00:00:00' and '".$availability_date." 12:00:00'  and level in ('3','4')";
$am_rs=$db->query($am_sql);
$am_nm=$am_rs->num_rows;

$am_cancel=0;
if($am_nm>0){
	$am_row=$am_rs->fetch_assoc();
	$am_cancel=$am_row['am_count']*1;
}

$am_sql="select sum(cancel) as pm_count from incident_report where incident_date between '".$availability_date." 12:00:01' and '".$availability_date." 23:59:59'  and level in ('3','4')";

$am_rs=$db->query($am_sql);
$am_nm=$am_rs->num_rows;

$pm_cancel=0;
if($am_nm>0){
	$am_row=$am_rs->fetch_assoc();
	$pm_cancel=$am_row['pm_count']*1;
}

?>
<div class="ccs-stat-group">
	<div class="ccs-stat"><span class="lbl">AM Departures</span><span class="val"><?php echo $am; ?></span></div>
	<div class="ccs-stat ccs-stat--danger"><span class="lbl">AM Loops</span><span class="val"><?php if($am_cancel=="0.5"){ echo "1/2"; } else { echo str_replace(".5"," 1/2",$am_cancel); }?></span></div>
	<div class="ccs-stat"><span class="lbl">PM Departures</span><span class="val"><?php echo $pm; ?></span></div>
	<div class="ccs-stat ccs-stat--danger"><span class="lbl">PM Loops</span><span class="val"><?php if($pm_cancel=="0.5"){ echo "1/2"; } else { echo str_replace(".5"," 1/2",$pm_cancel); }?></span></div>
</div>

<p class="ccs-stat-section-label">Planned vs. Actual Loops per Day</p>
<?php

$planned=0;
$actual=0;
$percentage=0;

$planned_sql="select * from timetable_day inner join timetable_code on timetable_code=timetable_code.id where train_date='".$availability_date."'";
$planned_rs=$db->query($planned_sql);
$planned_nm=$planned_rs->num_rows;
if($planned_nm>0){
	$planned_row=$planned_rs->fetch_assoc();
	
	$am_sql="select sum(cancel) as cancel from incident_report where incident_date like '".$availability_date."%%' and incident_type in ('rolling','gradual','c_loops','r_trains','unload','nload')";
	
	
	$am_rs=$db->query($am_sql);
	$am_nm=$am_rs->num_rows;
	$am_row=$am_rs->fetch_assoc();
	$planned=$planned_row['planned_loops'];
	$actual=$planned_row['planned_loops']*1-$am_row['cancel']*1;
	$percentage=number_format(($actual/$planned)*100,2);
}
?>
<div class="ccs-stat-group">
	<div class="ccs-stat"><span class="lbl">Planned Loops</span><span class="val"><?php echo $planned; ?></span></div>
	<div class="ccs-stat"><span class="lbl">Actual Loops</span><span class="val"><?php if($actual=="0.5"){ echo "1/2"; } else { echo str_replace(".5"," 1/2",$actual); }?></span></div>
</div>

<p class="ccs-stat-section-label">Loop Completion &amp; LRV Utilization</p>
<?php 
$train_sql="select * from train_availability inner join train_compo on train_availability.id=tar_id where train_availability.date like '".$availability_date."%%' and status='active' group by car_no";

$train_rs=$db->query($train_sql);
$train_nm=$train_rs->num_rows;

?>
<div class="ccs-stat-group">
	<div class="ccs-stat ccs-stat--gold"><span class="lbl">Actual Loops<br>Performed</span><span class="val"><?php echo $percentage."%"; ?></span></div>
	<div class="ccs-stat"><span class="lbl">No. of LRV<br>Utilized/day</span><span class="val"><?php echo $train_nm; ?></span></div>
</div>

</div>
</div>

</div>
</div>