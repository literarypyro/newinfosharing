<?php
/* Whether the console nav renders (see the require further down, in <body>). */
$NAV_SHOW = !(isset($_GET['embed']) && $_GET['embed']!='');
	// $_SESSION is used below for the sticky problem selection — nothing
	// else on this page starts the session, so do it here (guarded in
	// case an include ever starts one first).
	if(session_status()===PHP_SESSION_NONE) session_start();

	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");

// Which periods the console actually holds records for. This log can jump
// across a gap with nothing on the page explaining why — a reader would
// reasonably take the silence for "nothing happened" rather than "the records
// are missing". See data_coverage.php.
require_once("data_coverage.php");
$coverage = ccsLoadCoverage($db);
$coverageNote = ccsCoverageNote($coverage);
// @period -- Existing request variables on this page were 'problem' (sticky,
// via session) and 'car_id'. car_id was read into $car_id and then never used
// anywhere in the file, while being an unguarded $_GET — so every load without
// it raised an "Undefined array key" warning. Removed rather than guarded:
// there is nothing here for it to filter.

/* ---- Period filter ------------------------------------------------------
   Four ways to narrow, in precedence order:

     sd + ed   -> exact date range
     y + m     -> one month of one year
     y         -> one whole year
     m         -> that month across EVERY year (all Marches, say — the one
                  that answers "is this seasonal?")
     none      -> all time

   Kept in the URL rather than the session, unlike 'problem'. A period is the
   kind of thing you want to link someone to, and a sticky one that survives
   into an unrelated later visit is a good way to misread a report.          */
/* @mode -- The four modes used to be inferred from which parameters happened
   to be present, with sd/ed quietly winning over y/m. That is fine for a link
   but confusing as a form: both sets of controls sat there, and filling one
   silently disabled the other with nothing on screen saying so. The mode is now
   chosen explicitly and only its own controls are shown.
   Links without &mode= still work: the mode is inferred from what arrived. */
$phMode = isset($_GET['mode']) ? $_GET['mode'] : '';
if($phMode !== 'period' && $phMode !== 'range'){
	$phMode = (isset($_GET['sd']) && $_GET['sd'] !== '') ? 'range' : 'period';
}

$phYear  = isset($_GET['y'])  && $_GET['y']  !== '' ? (int)$_GET['y']  : 0;
$phMonth = isset($_GET['m'])  && $_GET['m']  !== '' ? (int)$_GET['m']  : 0;
if($phMonth < 1 || $phMonth > 12) $phMonth = 0;

$phSd = isset($_GET['sd']) && $_GET['sd'] !== '' ? strtotime($_GET['sd']) : false;
$phEd = isset($_GET['ed']) && $_GET['ed'] !== '' ? strtotime($_GET['ed']) : false;
$phRange = ($phMode === 'range' && $phSd !== false && $phEd !== false);
if($phMode === 'range'){ $phYear = 0; $phMonth = 0; }   /* one mode at a time */
if($phRange && $phEd < $phSd){ $t=$phSd; $phSd=$phEd; $phEd=$t; }   // swap, don't clamp

$phClause    = "";
$phLabel     = "All time";
$phMonthOnly = false;

if($phRange){
	$phClause = " and incident_date between '".date("Y-m-d",$phSd)." 00:00:00' and '".date("Y-m-d",$phEd)." 23:59:59' ";
	$phLabel  = date("d M Y",$phSd)." to ".date("d M Y",$phEd);
}
else if($phYear && $phMonth){
	$phClause = " and incident_date like '".sprintf("%04d-%02d",$phYear,$phMonth)."-%' ";
	$phLabel  = date("F Y", strtotime(sprintf("%04d-%02d-01",$phYear,$phMonth)));
}
else if($phYear){
	$phClause = " and incident_date like '".$phYear."-%' ";
	$phLabel  = "Year ".$phYear;
}
else if($phMonth){
	// Every year's copy of one month. month() rather than LIKE because the
	// year varies — this is the only one of the four that is not a contiguous
	// window, which matters for the chart below.
	$phClause    = " and month(incident_date) = ".$phMonth." ";
	$phLabel     = date("F", strtotime(sprintf("2000-%02d-01",$phMonth)))." (all years)";
	$phMonthOnly = true;
}

/* Carried on the problem dropdown's links so changing category keeps the
   period, instead of silently resetting it to all time. */
$phQS = "&mode=".$phMode;
if($phRange){
	$phQS .= "&sd=".urlencode(date("Y-m-d",$phSd))."&ed=".urlencode(date("Y-m-d",$phEd));
}
else {
	if($phYear)  $phQS .= "&y=".$phYear;
	if($phMonth) $phQS .= "&m=".$phMonth;
}

if(isset($_GET['problem'])){
	$_SESSION['problem_chart']=$_GET['problem'];

}

$problem = isset($_SESSION['problem_chart']) ? $_SESSION['problem_chart'] : '';

// First visit with nothing selected: default to the first problem type so
// the page shows real data and the dropdown matches what's displayed,
// instead of querying incident_type='' and rendering an empty table.
if($problem===''){
	$fr=$db->query("select equipment_code from equipment_type order by equipment_name limit 1");
	if($fr && ($f=$fr->fetch_assoc())) $problem=$f['equipment_code'];
}

$problemName = ($problem!=='') ? getProblemType($db,$problem) : '—';
?>
<style>
/* @period -- filter row; the theme styles selects already, this just lines
   them up and keeps the Clear link from looking like a table cell. */
/* @filterui -- The controls were a flat row of mismatched heights with no
   labels, so nothing said which select was which. Grouped into labelled
   fields on a common baseline instead. */
.ph-filters{display:flex;flex-wrap:wrap;align-items:flex-end;gap:10px;}
.ph-field{display:flex;flex-direction:column;gap:3px;}
.ph-field > label{font-size:10px;text-transform:uppercase;letter-spacing:.06em;color:#5A6275;font-weight:600;}
.ph-inline{display:flex;align-items:center;gap:6px;}
.ph-sep{font-size:11px;color:#5A6275;}

/* One height for every control in the row. Selects and inputs pick up
   different default paddings otherwise, which is what made the row look
   ragged. */
.ph-filters select,
.ph-filters input[type=text]{
	height:30px;box-sizing:border-box;
	padding:4px 8px;border:1px solid #D8D2C2;border-radius:4px;
	font-size:12px;font-family:inherit;
	background:#FFFFFF;color:#1A2238;
}

/* @filterui -- readonly is what turned these grey: browsers give readonly
   inputs their disabled styling, and against the theme's light text that left
   the value barely legible. The field is still not typeable -- the picker owns
   it -- but it should not look switched off. */
.ph-filters input[readonly]{
	background:#FFFFFF;color:#1A2238;cursor:pointer;
	opacity:1;-webkit-text-fill-color:#1A2238;
}
.ph-filters input[readonly]:hover{border-color:#00529B;}
.ph-filters input[readonly]:focus{outline:2px solid #00529B;outline-offset:-1px;}
.ph-filters input::placeholder{color:#8A93A6;opacity:1;}

.ph-filters button{
	height:30px;background:#FDB813;color:#3A2D00;border:none;border-radius:4px;
	padding:0 16px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;
}
.ph-filters button:hover{background:#E5A50F;}
.ph-clear{font-size:11px;color:#5A6275;text-decoration:none;align-self:center;padding-bottom:7px;}
.ph-clear:hover{color:#7A1F1F;text-decoration:underline;}

/* @filterui -- The calendar was drawing UNDER the table: jQuery UI ships
   z-index 1 on .ui-datepicker and the console theme puts the table header
   above that. It is appended to <body>, so it needs to beat everything on the
   page, not just its neighbours. The rest re-skins the widget to the console
   palette -- the stock smoothness theme is grey-on-grey and does not belong
   next to this table. */
.ui-datepicker{
	z-index:10000 !important;
	font-family:"Segoe UI",system-ui,Arial,sans-serif;font-size:12px;
	background:#FFFFFF;border:1px solid #C9CFDA;border-radius:6px;
	box-shadow:0 6px 20px rgba(0,30,80,.18);padding:6px;
}
.ui-datepicker .ui-datepicker-header{
	background:#00529B;border:none;border-radius:4px;color:#FFFFFF;padding:5px 4px;
}
.ui-datepicker .ui-datepicker-title{color:#FFFFFF;font-weight:600;}
.ui-datepicker .ui-datepicker-title select{
	background:#FFFFFF;color:#1A2238;border:1px solid #00529B;border-radius:3px;
	font-size:12px;padding:1px 3px;margin:0 2px;
}
.ui-datepicker .ui-datepicker-prev,
.ui-datepicker .ui-datepicker-next{background:none;border:none;cursor:pointer;top:6px;}
.ui-datepicker .ui-datepicker-prev span,
.ui-datepicker .ui-datepicker-next span{filter:brightness(0) invert(1);}
.ui-datepicker th{background:none;color:#5A6275;font-size:10px;text-transform:uppercase;font-weight:600;border:none;padding:4px 0;}
.ui-datepicker td{border:none;padding:1px;}
.ui-datepicker td a,
.ui-datepicker td span{
	display:block;text-align:center;padding:5px 0;border:none;border-radius:3px;
	background:none;color:#1A2238;text-decoration:none;
}
.ui-datepicker td a:hover{background:#E8F0F9;color:#00529B;}
.ui-datepicker td .ui-state-active{background:#00529B !important;color:#FFFFFF !important;}
.ui-datepicker td .ui-state-highlight{background:#FFF1CC;color:#3A2D00;}
.ui-datepicker td.ui-datepicker-unselectable span{color:#B8BEC9;}
</style>
<script>
/* @mode -- show one set of controls or the other. Declared here rather than in
   the jQuery block so the inline onchange can reach it regardless of load
   order. */
function phSetMode(v){
	var p=document.getElementById('phPeriodFields');
	var r=document.getElementById('phRangeFields');
	/* @filterui -- '' restores the stylesheet's display (flex column), rather
	   than forcing inline-block as a hard-coded value would. */
	if(p) p.style.display = (v==='range') ? 'none' : '';
	if(r) r.style.display = (v==='range') ? '' : 'none';
}
</script>
<?php include("history_theme.php"); ?>
<body>
<?php
/* Console nav. Tmenu_2.php emits ONLY the header markup and the <ul id="navMenu">
   -- never <!DOCTYPE>/<html>/<head> -- so it belongs here, inside <body>, and this
   page keeps whatever document scaffolding it already had.

   Suppressed under embed=1: this page is reached from the menu today, but every
   report here also loads OTHER pages into slide_panel.php, and edit_ccdr.php
   already carries an embed flag. Without the guard, the logo, header and nav bar
   would render inside an 820px iframe. */
if($NAV_SHOW){ require("Tmenu_2.php"); }
?>
<div class="ccs-page">

<div class="ccs-header">
	<h1>Incident History &mdash; By Category</h1>
	<div class="sub">Filtered by: <?php echo htmlspecialchars($problem ? getProblemType($db,$problem) : '—'); ?>
		&mdash; <?php echo htmlspecialchars($phLabel); ?> &mdash; Line 3</div>

</div>

<div class="ccs-panel">
<div class="ccs-panel-head">
  <h3>Incident History</h3>
  <div class="ccs-panel-actions">
    <?php /* @period -- one GET form, so category and period travel together.
             Category still submits on change; the period controls wait for
             Apply, because picking a year and then a month is two actions and
             reloading between them would be maddening. */ ?>
    <form method="get" action="problem_history.php" class="ph-filters">
    <?php /* @filterui -- every control gets a visible label; the row had none,
             so two bare selects sat side by side meaning different things. */ ?>
    <div class="ph-field">
    <label for="problemFilter">Problem type</label>
    <select id="problemFilter" name="problem" onchange="this.form.submit()">
      <?php
      $sql = "select * from equipment_type order by equipment_name";
      $rs = $db->query($sql);
      while ($row = $rs->fetch_assoc()) {
          $selected = ($row['equipment_code'] === $problem) ? 'selected' : '';
          echo "<option value='".htmlspecialchars($row['equipment_code'])."' {$selected}>"
             . htmlspecialchars($row['equipment_name'])."</option>";
      }
      ?>
    </select>
    </div>

    <div class="ph-field">
    <label for="phMode">Filter by</label>
    <?php /* @mode -- switches which set of controls is shown; onchange only
             toggles visibility, it does not submit, so a half-set filter is
             never sent. */ ?>
    <select name="mode" id="phMode" onchange="phSetMode(this.value)" title="Filter by">
      <option value="period"<?php echo $phMode==='period'?' selected':''; ?>>Year / Month</option>
      <option value="range"<?php echo $phMode==='range'?' selected':''; ?>>Date range</option>
    </select>
    </div>

    <div class="ph-field" id="phPeriodFields"<?php echo $phMode==='range'?' style="display:none"':''; ?>>
    <label>Year / month</label>
    <div class="ph-inline">
    <select name="y" title="Year">
      <option value="">All years</option>
      <?php
      /* Years that actually hold records, so the list cannot offer an empty one. */
      $yrRS=$db->query("select distinct year(incident_date) as y from incident_report where incident_date is not null order by y desc");
      if($yrRS){ while($yr=$yrRS->fetch_assoc()){
          $yv=(int)$yr['y']; if($yv<=0) continue;
          echo "<option value='".$yv."'".($phYear==$yv?" selected":"").">".$yv."</option>";
      } }
      ?>
    </select>

    <select name="m" title="Month">
      <option value="">All months</option>
      <?php for($mi=1;$mi<=12;$mi++){
          echo "<option value='".$mi."'".($phMonth==$mi?" selected":"").">"
             . date("F", strtotime(sprintf("2000-%02d-01",$mi)))."</option>";
      } ?>
    </select>
    </div>
    </div>

    <div class="ph-field" id="phRangeFields"<?php echo $phMode==='range'?'':' style="display:none"'; ?>>
    <label for="ph_sd">Date range</label>
    <div class="ph-inline">
    <input type="text" name="sd" id="ph_sd" value="<?php echo $phRange ? date("Y-m-d",$phSd) : ''; ?>" placeholder="From" size="10" readonly>
    <span class="ph-sep">&rarr;</span>
    <input type="text" name="ed" id="ph_ed" value="<?php echo $phRange ? date("Y-m-d",$phEd) : ''; ?>" placeholder="To" size="10" readonly>
    </div>
    </div>

    <button type="submit">Apply</button>
    <?php if($phClause!==""){ ?><a href="problem_history.php?problem=<?php echo urlencode($problem); ?>" class="ph-clear">Clear</a><?php } ?>
    </form>
  </div>
  <h2>
<?PHP
	$sql="select * from incident_description inner join incident_report on incident_report.id=incident_description.incident_id where incident_type='".$problem."' ".$phClause." order by incident_date desc limit 1";
	$rs2=$db->query($sql);
	$displayRow=$rs2 ? $rs2->fetch_assoc() : null;

	// @period -- with no rows this fell through to strtotime(null) and printed
	// "January 01, 1970" as though that were a real entry.
	if($displayRow && !empty($displayRow['incident_date'])){
		echo "Latest Entry Recorded: ".date("F d, Y",strtotime($displayRow['incident_date']));
	} else {
		echo "No entries in this period";
	}
?>

  
  </h2>
  
</div><div class="ccs-panel-body">
<table class="table table-striped table-bordered bootstrap-datatable datatable2" width="100%" id='add_form' name='add_form' >
	<thead>
	<tr>
	<?php
	if($problem=="rolling"){
	echo "<th>Index No</th>";

	}
	?>
	<th>Incident Date/Time</th>
	<th>Time Resolved</th>
	<th>Duration</th>
	<th>Incident Number</th>
	<?php 
	if($problem=="c_loops"){
		echo "<th>Cancelled Loops</th>";
	}
	else {
		echo "<th>Description</th>";
	}
	?>
	</tr>
	</thead>
	<tbody>
	<?php
	$sql="select * from incident_description inner join incident_report on incident_report.id=incident_description.incident_id where incident_type='".$problem."' ".$phClause." order by incident_date desc";

	$rs=$db->query($sql);
	$nm=$rs->num_rows;

	// ---- Chart aggregates, built in the same pass as the table rows.
	// Only two fields reliably vary on this page (the category IS the
	// page filter, and Duration/Time Resolved are blank), so the charts
	// are built entirely from time and text: monthly volume, a weekday x
	// time-band grid, and recurring description terms.
	$monthlyVolume=array();               // ["YYYY-MM"] => count
	$timingGrid=array();                  // [weekday 0=Mon..6=Sun][band 0..3] => count
	for($d=0;$d<7;$d++){ $timingGrid[$d]=array(0,0,0,0); }

	for($i=0;$i<$nm;$i++){
		$row=$rs->fetch_assoc();

		$ts=strtotime($row['incident_date']);
		// @months -- was $mo=$ts, the raw Unix timestamp, despite the comment
		// above declaring "YYYY-MM" keys. Every incident therefore got its own
		// per-SECOND bucket, so chart 1 drew one bar per incident and labelled
		// it with a chopped timestamp ("72345678" after the .slice(2) below).
		// data-mo on each row fed the same value into the print window filter,
		// where it was string-compared against a real "YYYY-MM".
		$mo=date("Y-m",$ts);
		if(!isset($monthlyVolume[$mo])) $monthlyVolume[$mo]=0;
		$monthlyVolume[$mo]++;

		$dow=(int)date("N",$ts)-1;         // 0=Mon
		$h=(int)date("G",$ts);
		if($h>=5 && $h<9) $band=0;         // AM peak
		else if($h>=9 && $h<16) $band=1;   // midday
		else if($h>=16 && $h<20) $band=2;  // PM peak
		else $band=3;                      // evening / night
		$timingGrid[$dow][$band]++;

		// The recurring-words chart that used to mine $row['description'] here
		// has been removed on request — the words were not the signal wanted.
		// Its intended replacement is resolution time (Duration / Time
		// Resolved), but that field is mostly blank today and resolution-time
		// tracking is not implemented yet, so there is nothing reliable to put
		// in that slot. Chart 3 is dropped for now; a commented stub in the
		// chart block below marks where the resolution-time chart will go.
	?>	
		<tr data-mo="<?php echo $mo; ?>">
			<?php 
			if($problem=="rolling"){
				?>
			
			<td><?php echo $row['index_no']; ?></td>
			<?php
			}
			?>
			<td><?php echo "<span>".date("Y-m-d H:ia",strtotime($row['incident_date']))."</span>"; ?></td>
			<td><?php 
		if(date("Y-m-d",strtotime($row['resolution_date']))!=="1970-01-01"){		
		echo date("Y-m-d H:iA", strtotime($row['resolution_date'])); 
		}
		else {
			echo "&nbsp;";

		}			
		
		?></td>	
			<td><?php echo $row['duration']; ?></td>	

		<td><a href='#' class='two' onclick='openSlidePanel("edit_ccdr.php?ir=<?php echo  $row['id']; ?>&embed=1","Incident - <?php echo htmlspecialchars($row['incident_no']); ?>")'><?php echo $row['incident_no']; ?></a></td>

		<?PHP
			if($problem=="c_loops"){
		?>
			<td><?php echo $row['cancel']; ?></td>
			<?php
			}
			else {
				?>
			<td><?php echo $row['description']; ?></td>
			<?php } ?>
		</tr>
	<?php
	}

	// @months -- A month with no incidents has no key at all, so the axis used
	// to close the gap and print two non-adjacent months side by side. Fill the
	// span so the x-axis is continuous. Months the coverage table marks missing
	// get null rather than 0: no records because none were kept is not the same
	// claim as no incidents, and Chart.js draws nothing for null.
	// @period -- The month-only filter returns one month per year, so the span
	// between the first and last key is mostly months the filter EXCLUDED.
	// Filling it would draw a run of zero bars that say "no incidents" when
	// they mean "not asked for". Contiguous filters fill; this one does not.
	if($phMonthOnly){
		// Not filled, but still ordered: the query returns newest-first, which
		// would draw the chart right-to-left.
		ksort($monthlyVolume);
	}
	if(count($monthlyVolume) && !$phMonthOnly){
		$mk=array_keys($monthlyVolume);
		sort($mk);
		$cur=new DateTime($mk[0]."-01");
		$lst=new DateTime($mk[count($mk)-1]."-01");
		$filled=array();
		while($cur <= $lst){
			$ym=$cur->format("Y-m");
			if(isset($monthlyVolume[$ym]))                          $filled[$ym]=$monthlyVolume[$ym];
			else if(ccsMonthStatus($coverage,$ym) === 'missing')    $filled[$ym]=null;
			else                                                    $filled[$ym]=0;
			$cur->modify("+1 month");
		}
		$monthlyVolume=$filled;
	}

	?>	
	</tbody>
</table>
</div>
</div>
</div>

<!-- Print-only chart summary. Hidden on screen; the three canvases are
     flattened to images and injected into the TableTools print window.
     Chart titles carry the selected problem name so a printout is
     self-identifying even after the dropdown changes. -->
<div id="ccs-print-charts" style="display:none;">
	<canvas id="pvVolume" width="340" height="160"></canvas>
	<canvas id="pvTiming" width="340" height="180"></canvas>
<?php /* Chart 3 (recurring words) removed; canvas returns when resolution-time
         data is available — see the stub in the chart script below. */ ?>
</div>

<script>
var pvCoverageNote = <?php echo json_encode(htmlspecialchars($coverageNote, ENT_QUOTES)); ?>;
var pvFilterLabel  = <?php echo json_encode($phLabel); ?>;   /* @period */
// Aggregates from the same query/filter as the table above.
var pvProblemName = <?php echo json_encode($problemName); ?>;
var pvMonthly = <?php echo json_encode($monthlyVolume, JSON_FORCE_OBJECT); ?>;
var pvTiming = <?php echo json_encode($timingGrid); ?>;
</script>

<!--
		<script src="js/jquery-1.10.2.min.js"></script>
		<script src="js/jquery-ui-1.10.3.custom.min.js"></script>	
		<script src="js/bootstrap.min.js"></script>	

		<script src="js/jquery-migrate-1.2.1.min.js"></script>	
		<script src="js/jquery.ui.touch-punch.js"></script>	
		<script src="js/modernizr.js"></script>	
		<script src="js/jquery.cookie.js"></script>	
		<script src='js/fullcalendar.min.js'></script>	
		<script src='js/jquery.dataTables.js'></script>
		<script src="js/dataTables.tableTools.js"></script>
		<script src="js/core.min.js"></script>	

		<script src="js/excanvas.js"></script>
		<script src="js/jquery.flot.js"></script>
		<script src="js/jquery.flot.pie.js"></script>
		<script src="js/jquery.flot.stack.js"></script>
		<script src="js/jquery.flot.resize.min.js"></script>
		<script src="js/jquery.flot.time.js"></script>
		
		<script src="js/jquery.chosen.min.js"></script>	
		<script src="js/jquery.uniform.min.js"></script>		
		<script src="js/jquery.cleditor.min.js"></script>	
		<script src="js/jquery.noty.js"></script>	
		<script src="js/jquery.elfinder.min.js"></script>	
		<script src="js/jquery.raty.min.js"></script>	
		<script src="js/jquery.iphone.toggle.js"></script>	
		<script src="js/jquery.uploadify-3.1.min.js"></script>	
		<script src="js/jquery.gritter.min.js"></script>	
		<script src="js/jquery.imagesloaded.js"></script>	
		<script src="js/jquery.masonry.min.js"></script>	
		<script src="js/jquery.knob.modified.js"></script>	
		<script src="js/jquery.sparkline.min.js"></script>	
		<script src="js/counter.min.js"></script>	
		<script src="js/raphael.2.1.0.min.js"></script>
		<script src="js/justgage.1.0.1.min.js"></script>	
		<script src="js/jquery.autosize.min.js"></script>	
		<script src="js/retina.js"></script>
		<script src="js/jquery.placeholder.min.js"></script>
		<script src="js/wizard.min.js"></script>
		<script src="js/charts.min.js"></script>	
		<script src="js/custom.min.js"></script>
		

		<script src="js/additional.js"></script>
-->

		<script src="js/jquery-1.10.2.min.js"></script>
		<script src="js/jquery-migrate-1.2.1.min.js"></script>	
		<script src="js/jquery-ui-1.10.3.custom.min.js"></script>	
		<script src="js/jquery.ui.touch-punch.js"></script>	
		<script src="js/modernizr.js"></script>	
		<script src="js/bootstrap.min.js"></script>	
		<script src="js/jquery.cookie.js"></script>	
		<script src='js/fullcalendar.min.js'></script>	
		<script src='js/jquery.dataTables.min.js'></script>
		<script src="js/dataTables.tableTools.js"></script>
			
		<script src="js/excanvas.js"></script>
		<script src="js/jquery.flot.js"></script>
		<script src="js/jquery.flot.pie.js"></script>
		<script src="js/jquery.flot.stack.js"></script>
		<script src="js/jquery.flot.resize.min.js"></script>
		<script src="js/jquery.flot.time.js"></script>
		
		<script src="js/jquery.chosen.min.js"></script>	
		<script src="js/jquery.uniform.min.js"></script>		
		<script src="js/jquery.cleditor.min.js"></script>	
		<script src="js/jquery.noty.js"></script>	
		<script src="js/jquery.elfinder.min.js"></script>	
		<script src="js/jquery.raty.min.js"></script>	
		<script src="js/jquery.iphone.toggle.js"></script>	
		<script src="js/jquery.uploadify-3.1.min.js"></script>	
		<script src="js/jquery.gritter.min.js"></script>	
		<script src="js/jquery.imagesloaded.js"></script>	
		<script src="js/jquery.masonry.min.js"></script>	
		<script src="js/jquery.knob.modified.js"></script>	
		<script src="js/jquery.sparkline.min.js"></script>	
		<script src="js/counter.min.js"></script>	
		<script src="js/raphael.2.1.0.min.js"></script>
		<script src="js/justgage.1.0.1.min.js"></script>	
		<script src="js/jquery.autosize.min.js"></script>	
		<script src="js/retina.js"></script>
		<script src="js/jquery.placeholder.min.js"></script>
		<script src="js/wizard.min.js"></script>
		<script src="js/core.min.js"></script>	
		<script src="js/charts.min.js"></script>	
		<script src="js/custom.min.js"></script>
		<script src="js/additional.js"></script>

<!-- ============================================================
     Print-with-charts (volume + timing heatmap + recurring terms).
     Attached last, after custom.min.js / additional.js — those two
     auto-init .datatable2 and its TableTools print button, so the
     button only exists to hook into once they've run.
     ============================================================ -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
$(function(){
	/* @datepicker -- the sd/ed inputs had no binding at all, so they were plain
	   text boxes. jQuery UI is already loaded by the template above; this is
	   just the missing .datepicker() call. dateFormat has to be yy-mm-dd
	   because the PHP side parses with strtotime and stores Y-m-d.
	   readonly on the inputs keeps typing out of a field that has a picker. */
	if($.fn.datepicker){
		var dpOpts = { dateFormat:'yy-mm-dd', changeMonth:true, changeYear:true, yearRange:'c-10:c+1' };
		var $sd = $('#ph_sd'), $ed = $('#ph_ed');
		$sd.datepicker($.extend({}, dpOpts, {
			onSelect: function(d){ $ed.datepicker('option','minDate', d); }
		}));
		$ed.datepicker($.extend({}, dpOpts, {
			onSelect: function(d){ $sd.datepicker('option','maxDate', d); }
		}));
		if($sd.val()) $ed.datepicker('option','minDate',$sd.val());
		if($ed.val()) $sd.datepicker('option','maxDate',$ed.val());
	}


	var textInk  = getComputedStyle(document.documentElement).getPropertyValue('--text-primary').trim() || '#111';
	var mutedInk = getComputedStyle(document.documentElement).getPropertyValue('--text-secondary').trim() || '#555';
	var gridInk  = 'rgba(137,135,129,0.20)';
	var mainColor = '#2a78d6', termColor = '#1baf7a';

	function escHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

	// ---- Shared 24-month window ----------------------------------------
	// This page has no date filter, so an unfiltered problem type can return
	// years of records. The chart caps at the last 24 months; the printed
	// table uses the SAME boundary, both so the two agree and because
	// document.write of several thousand rows plus the chart images is enough
	// to hang or crash the print tab.
	var pvAllMonths  = Object.keys(pvMonthly).sort();
	var pvWindow     = pvAllMonths.slice(-24);
	var pvWindowFrom = pvWindow.length ? pvWindow[0] : '';   // "YYYY-MM", inclusive
	var pvTrimmed    = pvAllMonths.length > pvWindow.length;

	// ============ Chart 1: monthly volume ============
	// @months -- labels were m.slice(2), which chopped the first two characters
	// off the key. Even once the key is a real "YYYY-MM" that yields "26-03";
	// on the raw timestamps it yielded a fragment of a number. Split into two
	// lines instead, which Chart.js stacks, so the year is carried on every
	// label without needing rotation or extra width.
	var PV_MON = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
	function pvMonthLabel(m){
		var p = String(m).split('-');
		var i = parseInt(p[1], 10) - 1;
		if(!(i >= 0 && i < 12)) return String(m);
		return [PV_MON[i], p[0]];
	}
	var months = pvAllMonths;
	var volMonths = pvWindow;
	var volTitle = pvProblemName + ' \u2014 monthly volume' + (months.length > 24 ? ' (last 24 months)' : '');

	new Chart(document.getElementById('pvVolume'), {
		type: 'bar',
		data: {
			labels: volMonths.map(pvMonthLabel),
			datasets: [{ data: volMonths.map(function(m){ return pvMonthly[m]; }), backgroundColor: mainColor, borderRadius: 3 }]
		},
		options: {
			responsive: false, animation: false,
			plugins: {
				title: { display: true, text: volTitle, color: textInk, font: { size: 11, weight: 'normal' }, padding: { bottom: 6 } },
				legend: { display: false }
			},
			scales: {
				// Two-line labels do not need rotating; autoSkip still thins them
				// if 24 of them will not fit the 340px canvas.
				x: { ticks: { color: mutedInk, font: { size: 9 }, maxRotation: 0, autoSkipPadding: 4 }, grid: { display: false } },
				y: { ticks: { color: mutedInk, precision: 0, font: { size: 10 } }, grid: { color: gridInk } }
			}
		}
	});

	// ============ Chart 2: weekday x time-band heatmap ============
	// Hand-drawn on a raw canvas so it flattens to an image for the
	// TableTools print handoff like the two Chart.js canvases.
	(function drawTiming(){
		var cv = document.getElementById('pvTiming');
		var ctx = cv.getContext('2d');
		var W = cv.width, H = cv.height;
		ctx.clearRect(0,0,W,H);
		ctx.textBaseline = 'middle';

		ctx.font = '11px Arial, sans-serif'; ctx.fillStyle = textInk; ctx.textAlign = 'left';
		ctx.fillText(pvProblemName + ' \u2014 when it occurs', 0, 9);

		var days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
		var bands = ['AM peak','Midday','PM peak','Evening'];
		var padL = 46, padT = 34, padR = 8, padB = 8;
		var gridW = W - padL - padR, gridH = H - padT - padB;
		var cellW = gridW / bands.length, cellH = gridH / days.length;

		var maxV = 0;
		pvTiming.forEach(function(r){ r.forEach(function(v){ maxV = Math.max(maxV, v); }); });
		if(maxV === 0) maxV = 1;

		ctx.font = '10px Arial, sans-serif'; ctx.fillStyle = mutedInk; ctx.textAlign = 'center';
		bands.forEach(function(b,ci){ ctx.fillText(b, padL + ci*cellW + cellW/2, padT - 10); });

		days.forEach(function(d,ri){
			var y = padT + ri*cellH;
			ctx.font = '10px Arial, sans-serif'; ctx.fillStyle = mutedInk; ctx.textAlign = 'right';
			ctx.fillText(d, padL - 6, y + cellH/2);
			bands.forEach(function(b,ci){
				var v = pvTiming[ri][ci];
				var x = padL + ci*cellW;
				var t = v === 0 ? 0.04 : 0.12 + (v/maxV)*0.82;
				ctx.fillStyle = 'rgba(42,120,214,'+t.toFixed(3)+')';
				ctx.fillRect(x+1, y+1, cellW-2, cellH-2);
				if(v > 0){
					ctx.fillStyle = (v/maxV > 0.55) ? '#fff' : mutedInk;
					ctx.font = '9px Arial, sans-serif'; ctx.textAlign = 'center';
					ctx.fillText(String(v), x + cellW/2, y + cellH/2);
				}
			});
		});
	})();

	// ============ Chart 3: RESOLUTION TIME (pending) =============
	// The recurring-words chart that lived here has been removed. Its
	// replacement is a resolution-time view — average time to resolve per
	// month, or a duration distribution — which is the operational question
	// this page is really for and which works for every category, rolling
	// included. It is stubbed rather than built because Duration is mostly
	// blank today and resolution time (Time Resolved) is not implemented yet.
	//
	// When it lands, restore the <canvas id="pvTerms"> above (rename to
	// pvResolution), hand the per-month averages to the JS as pvResolution,
	// and draw here. Skeleton to fill in:
	//
	// if(pvResolution && pvResolution.length){
	//     new Chart(document.getElementById('pvResolution'), {
	//         type: 'bar',                       // or 'line' for a trend
	//         data: {
	//             labels: pvResolution.map(function(r){ return r[0]; }),   // "YYYY-MM"
	//             datasets: [{ data: pvResolution.map(function(r){ return r[1]; }),  // avg minutes, null for gap months
	//                          backgroundColor: termColor, borderRadius: 3, spanGaps: false }]
	//         },
	//         options: {
	//             responsive: false, animation: false,
	//             plugins: {
	//                 title: { display: true, text: pvProblemName + ' \u2014 average resolution time by month',
	//                          color: textInk, font: { size: 11, weight: 'normal' }, padding: { bottom: 8 } },
	//                 legend: { display: false },
	//                 tooltip: { callbacks: { label: function(c){ return (c.parsed.y === null ? 'no data' : c.parsed.y + ' min avg'); } } }
	//             },
	//             scales: {
	//                 x: { ticks: { color: mutedInk, font: { size: 9 }, maxRotation: 45 }, grid: { display: false } },
	//                 y: { ticks: { color: mutedInk, precision: 0, font: { size: 10 } }, grid: { color: gridInk }, beginAtZero: true }
	//             }
	//         }
	//     });
	// }
	//
	// Aggregate it in the PHP loop alongside $monthlyVolume, guarding for the
	// unresolved sentinel the table already uses:
	//   if(date("Y-m-d", strtotime($row['resolution_date'])) !== "1970-01-01"){
	//       $mins = (strtotime($row['resolution_date']) - strtotime($row['incident_date'])) / 60;
	//       ... accumulate per $mo, average at the end, null for coverage-missing months ...
	//   }
	// The layout below now expects TWO charts, not three — see the print block.

	// ============ Intercept the TableTools print button ============
	var printBtn = $('#add_form_wrapper').find('.DTTT_button_print, .buttons-print');
	if(printBtn.length){
		printBtn.off('click').on('click', function(e){
			e.preventDefault();
			e.stopImmediatePropagation();
			pvPrintWithCharts();
		});
	}

		// DataTables removes off-page rows from the DOM, so reading the live
	// table's outerHTML only ever captures the current page. Rebuild a full
	// copy from the DataTables API instead.
	//
	// Two APIs to handle: isDataTable()/.DataTable() only exist in 1.10+.
	// This template ships an older DataTables (TableTools is a 1.9-era
	// plugin), where the equivalent is fnGetNodes() — it returns every row
	// node regardless of pagination. bRetrieve:true asks 1.9 for the
	// EXISTING instance rather than re-initialising (which is what throws
	// "Cannot reinitialise DataTable").
	function pvFullTableHtml(){
		var el   = document.getElementById('add_form');
		var $el  = $(el);
		var rows = null;

		try{
			// DataTables 1.10+
			if($.fn.dataTable && $.fn.dataTable.isDataTable && $.fn.dataTable.isDataTable('#add_form')){
				rows = $('#add_form').DataTable()
				         .rows({ search:'applied', order:'applied' })
				         .nodes().toArray();
			}
			// DataTables 1.9 (this template)
			else if($.fn.dataTable){
				rows = $el.dataTable({ bRetrieve:true }).fnGetNodes();
			}
		}catch(e){ rows = null; }

		// Temporary diagnostic — delete once print is confirmed working.
		console.log('print capture — rows found:', rows ? rows.length : 0);

		if(!rows || !rows.length) return { html: el.outerHTML, count: $el.find('tbody tr').length, omitted: 0 };

		// Window the printed table to the same 24 months the chart covers.
		// Rows carry data-mo="YYYY-MM" from PHP, so this is a string compare
		// rather than date parsing out of the DOM. Rows without the attribute
		// are kept, so nothing unexpected is silently dropped.
		var total = rows.length, kept = rows;
		if(pvWindowFrom){
			kept = $.grep(rows, function(node){
				var mo = $(node).attr('data-mo');
				return !mo || mo >= pvWindowFrom;
			});
		}
		var omitted = total - kept.length;

		var $clone = $el.clone();
		var $tbody = $clone.find('tbody').empty();
		$.each(kept, function(i, node){ $tbody.append($(node).clone()); });

		// Strip DataTables' runtime artifacts — inline widths, sort classes
		// and ARIA attributes — so the print stylesheet has full control.
		$clone.removeAttr('style').removeAttr('width').removeClass('dataTable');
		$clone.find('th, td').removeAttr('style').removeAttr('width');
		$clone.find('th').removeAttr('class').removeAttr('aria-label')
		      .removeAttr('aria-sort').removeAttr('tabindex').removeAttr('role');
		$clone.find('tr').removeAttr('class').removeAttr('role');

		return { html: $clone[0].outerHTML, count: kept.length, omitted: omitted };
	}

	function pvPrintWithCharts(){
		var imgVolume = document.getElementById('pvVolume').toDataURL('image/png');
		var imgTiming = document.getElementById('pvTiming').toDataURL('image/png');
		// imgTerms removed with Chart 3; restore alongside the resolution-time chart.
		var name      = escHtml(pvProblemName);
		var captured  = pvFullTableHtml();
		var tableHtml = captured.html;
		var rowCount  = captured.count;
		var omitted   = captured.omitted;
		/* @period -- name the filter that produced the rows, not just the span
		   the chart happens to cover. With a filter active the two differ, and
		   the printout was reporting the second as though it were the first. */
		var periodTxt = (typeof pvFilterLabel !== 'undefined' && pvFilterLabel && pvFilterLabel !== 'All time')
			? pvFilterLabel
			: (pvWindowFrom
				? (pvWindowFrom + ' to ' + pvWindow[pvWindow.length-1] + (pvTrimmed ? ' (last 24 months)' : ''))
				: 'All recorded dates');

		var win = window.open('', '_blank');
		win.document.write(
			'<html><head><title>' + name + ' \u2014 Incident History</title>' +
			'<style>' +
				'@page{ size:A4 portrait; margin:14mm 12mm 15mm; }' +
				'*{ box-sizing:border-box; }' +
				'body{ font-family:"Segoe UI",Arial,Helvetica,sans-serif; color:#1a1a1a; margin:0;' +
					' font-size:11px; line-height:1.45;' +
					' -webkit-print-color-adjust:exact; print-color-adjust:exact; }' +
				'.rpt-head{ border-bottom:2px solid #1f4e79; padding-bottom:9px; margin-bottom:4px; }' +
				'.rpt-org{ font-size:8.5px; letter-spacing:.15em; text-transform:uppercase;' +
					' color:#6b7280; margin-bottom:3px; }' +
				'.rpt-title{ font-size:19px; font-weight:600; color:#1f4e79; margin:0 0 1px; }' +
				'.rpt-subject{ font-size:12.5px; color:#374151; margin:0; }' +
				'.rpt-meta{ margin:8px 0 0; font-size:9.5px; color:#4b5563; }' +
				'.rpt-meta span{ margin-right:20px; white-space:nowrap; }' +
				'.rpt-meta b{ color:#1f2937; font-weight:600; }' +
				'h2.sec{ font-size:11px; text-transform:uppercase; letter-spacing:.09em;' +
					' color:#1f4e79; border-bottom:1px solid #d1d5db; padding-bottom:4px;' +
					' margin:20px 0 10px; font-weight:600; }' +
				'.charts{ margin-bottom:4px; }' +
				'.chart{ display:inline-block; vertical-align:top; width:48%; margin:0 2% 10px 0;' +
					' page-break-inside:avoid; }' +
				'.chart img{ display:block; width:100%; height:auto; border:1px solid #e5e7eb; }' +
				'.chart .cap{ font-size:9px; color:#6b7280; margin-top:3px; }' +
				'.note{ font-size:9px; color:#6b7280; font-style:italic; margin:2px 0 0; }' +
				'.tbl-head{ margin-bottom:6px; }' +
				'.tbl-head h3{ font-size:13px; font-weight:600; margin:0; display:inline-block; }' +
				'.tbl-head .count{ font-size:9.5px; color:#6b7280; margin-left:8px; }' +
				/* @print -- The crop. With auto layout the widest cell decides the
				   column, and Description holds free text with no guaranteed
				   break points: one long entry pushed the table past the page
				   box and everything to the right of it was clipped, because a
				   print context has no horizontal scroll to fall back on.
				   Fixed layout plus break-anywhere keeps every column inside
				   the margin and wraps the text instead. */
				'table{ width:100%; max-width:100%; table-layout:fixed;' +
					' border-collapse:collapse; font-size:9.5px; }' +
				'th, td{ overflow-wrap:anywhere; word-break:break-word; }' +
				/* Dates and reference numbers are short and fixed-length; giving
				   them a ceiling leaves the remainder to the description rather
				   than letting fixed layout split everything evenly. */
				'th:first-child, td:first-child{ width:9%; }' +
				'th:nth-child(2), td:nth-child(2){ width:14%; }' +
				'th:nth-child(3), td:nth-child(3){ width:13%; }' +
				'th:nth-child(4), td:nth-child(4){ width:9%; }' +
				'th:nth-child(5), td:nth-child(5){ width:13%; }' +
				'thead{ display:table-header-group; }' +
				'th{ background:#1f4e79; color:#fff; text-align:left; padding:6px 7px;' +
					' font-size:9px; font-weight:600; text-transform:uppercase;' +
					' letter-spacing:.04em; border:1px solid #1f4e79; }' +
				'td{ padding:5px 6px; border:1px solid #e5e7eb; vertical-align:top; }' +
				'tbody tr:nth-child(even) td{ background:#f6f8fa; }' +
				'tr{ page-break-inside:avoid; }' +
				'a{ color:inherit; text-decoration:none; pointer-events:none; }' +
				'.rpt-foot{ margin-top:14px; border-top:1px solid #d1d5db; padding-top:6px;' +
					' font-size:8.5px; color:#6b7280; }' +
			'</style></head><body>' +
			'<div class="rpt-head">' +
				'<div class="rpt-org">DOTr &middot; MRT-3 Line 3 &middot; Operations Control</div>' +
				'<h1 class="rpt-title">Incident History by Problem Category</h1>' +
				'<p class="rpt-subject">' + name + '</p>' +
			'</div>' +
			'<div class="rpt-meta">' +
				'<span><b>Problem category:</b> ' + name + '</span>' +
				'<span><b>Report period:</b> ' + periodTxt + '</span>' +
				'<span><b>Records:</b> ' + rowCount + '</span>' +
				'<span><b>Generated:</b> <?php echo date("d M Y, H:i"); ?></span>' +
			'</div>' +

			'<h2 class="sec">Summary</h2>' +
			'<div class="charts">' +
				'<div class="chart"><img src="' + imgVolume + '">' + '<div class="cap">Figure 1 &mdash; Monthly volume</div></div>' +
				'<div class="chart"><img src="' + imgTiming + '">' + '<div class="cap">Figure 2 &mdash; When incidents occur</div></div>' +
				// Figure 3 (recurring words) removed; returns as the resolution-time chart.
			'</div>' +

			'<h2 class="sec">Incident Records</h2>' +
			'<div class="tbl-head">' +
				'<h3>' + name + ' &mdash; incident log</h3>' +
				'<span class="count">' + rowCount + ' record' + (rowCount === 1 ? '' : 's') +
					(omitted ? ' &middot; ' + omitted + ' older record' + (omitted === 1 ? '' : 's') + ' outside the 24-month window not shown' : '') + '</span>' +
			'</div>' +
			tableHtml +

			'<div class="rpt-foot">MRT-3 Information Sharing System &middot; generated <?php echo date("d M Y, H:i"); ?> &middot; for internal operational use</div>' +
			'</body></html>'
		);
		win.document.close();
		win.focus();
		// Data-URL images occasionally decode after onload; the short delay
		// keeps the charts from printing blank.
		win.onload = function(){ setTimeout(function(){ win.print(); }, 250); };
	}

});
</script>
<?php require("slide_panel.php"); ?>
</body>
<?php
function getProblemType($db,$type){
	$sql="select * from equipment_type where equipment_code='".$type."'";
	$rs=$db->query($sql);

	$nm=$rs->num_rows;
	$row=$rs->fetch_assoc();

	$problem=$row['equipment_name'];
	return $problem;
}

// Tokenizer for the recurring-terms chart (same one the auto-categorization
// on other_history/car_history uses — here it just mines term frequency, no
// classifier, since the category IS the page filter and never varies).
function ccsTokenize($text){
	if($text===null) return array();
	$text = strtolower($text);
	$text = preg_replace('/[^a-z0-9\s]/',' ',$text);
	$tokens = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
	static $stop = array('the'=>1,'a'=>1,'an'=>1,'and'=>1,'or'=>1,'of'=>1,'to'=>1,'in'=>1,'on'=>1,'at'=>1,
		'is'=>1,'was'=>1,'were'=>1,'for'=>1,'with'=>1,'from'=>1,'by'=>1,'due'=>1,'that'=>1,'this'=>1,
		'as'=>1,'be'=>1,'been'=>1,'are'=>1,'it'=>1,'its'=>1,'has'=>1,'had'=>1,'have'=>1,'per'=>1,
		'am'=>1,'pm'=>1,'hrs'=>1,'nb'=>1,'sb'=>1);
	$out=array();
	foreach($tokens as $t){
		if(strlen($t) >= 3 && !isset($stop[$t]) && !ctype_digit($t)) $out[]=$t;
	}
	return $out;
}
?>