<?php
/* Session before ANY output -- the console header calls session_start(). */
if(session_id()==""){ session_start(); }

/* Whether the console nav renders (see the require further down, in <body>). */
$NAV_SHOW = !(isset($_GET['embed']) && $_GET['embed']!='');
// car_history.php — Line 3 colour scheme applied to the live page.
//
// FIX (the "Cannot reinitialise DataTable" error):
//   Removed the extra  $('#add_form').DataTable(...)  block at the bottom.
//   Your template (custom.min.js / additional.js) already auto-initialises
//   .datatable2 tables, so that second init on the same table was the cause.
//   One init only now.
//
// Two more things restored after the merge:
//   * $dateClause block (your union query references it — it was undefined).
//   * Problem Type uses getProblemType($db,$row['incident_type']) again;
//     echoing $row['problem_type'] was a sample-data leftover and would
//     render that column blank against your real query.
//
// The only thing that restyles the page is the <style> block below.

	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");

// Which periods the console actually holds records for. This log can jump
// across a gap with nothing on the page explaining why — a reader would
// reasonably take the silence for "nothing happened" rather than "the records
// are missing". See data_coverage.php.
require_once("data_coverage.php");
$coverage = ccsLoadCoverage($db);
$coverageNote = ccsCoverageNote($coverage);


	// $db2 (is_transport_old) is no longer opened — that database's rows were
	// restored into is_transport, so nothing on this page reads it any more.


$car_id=$_GET['car_id'];

// @months -- Resolved once, so the chart below can tell which months the
// report is SUPPOSED to cover rather than inferring it from which months
// happened to return rows.
//
// The year-only branch also carried a bug: $dateClause2 still interpolated
// $_GET['m'], which is unset in that branch. strtotime("2026--01") returns
// false, date("m", false) reads that as timestamp 0 and yields "01" — so the
// main table was filtered to the whole year while the transport_old rows were
// silently filtered to January of it. Both clauses now say the same thing.
// @carryfilter -- car_stats.php can be narrowed to one equipment type, and its
// "Full incident history" button now passes that through as eq. Without a
// matching filter here the jump silently widened back to every equipment on
// the car, so the two pages disagreed about what was being looked at.
$ccsEquipt = isset($_GET['eq']) && $_GET['eq'] !== '' ? (int)$_GET['eq'] : 0;

/* @levelfilter -- Inherited from car_stats.php, which passes its level filter
   through on the "Full incident history" button. !== '' rather than isset():
   a blank level= means "all levels", and isset() is true for it. */
$ccsLevel = isset($_GET['level']) && $_GET['level'] !== '' ? (int)$_GET['level'] : 0;
if($ccsLevel < 0 || $ccsLevel > 4){ $ccsLevel = 0; }

/* Resolved once, so the heading and the printout name it the same way. */
$ccsEquiptName = '';
if($ccsEquipt){
	$eqr = $db->query("select equipment_name from equipment where id='".$ccsEquipt."'");
	if($eqr && ($eqw = $eqr->fetch_assoc())) $ccsEquiptName = (string)$eqw['equipment_name'];
}

$ccsYear  = isset($_GET['y']) && $_GET['y'] !== '' ? (int)$_GET['y'] : 0;
$ccsMonth = isset($_GET['m']) && $_GET['m'] !== '' ? (int)$_GET['m'] : 0;
if($ccsMonth < 1 || $ccsMonth > 12){ $ccsMonth = 0; }

/* @dayfix -- car_statistics_report's day view sent only y and m, so clicking
   day 7 of March landed here filtered to all of March. The day was simply
   dropped in transit; this page had no day concept to receive it.

   Validated against the actual month rather than 1..31: checkdate() rejects
   31 April and 29 Feb in a common year, either of which would otherwise build
   a LIKE that silently matches nothing and read as "no incidents". A day
   without a month is not a date, so it needs both. */
$ccsDay = isset($_GET['d']) && $_GET['d'] !== '' ? (int)$_GET['d'] : 0;
if(!$ccsYear || !$ccsMonth || !checkdate($ccsMonth, $ccsDay, $ccsYear)){ $ccsDay = 0; }

$dateClause  = "";
$dateClause2 = "";
if($ccsYear && $ccsMonth && $ccsDay){
	$ymd = sprintf("%04d-%02d-%02d", $ccsYear, $ccsMonth, $ccsDay);
	$dateClause  = " and incident_date like '".$ymd."%%' ";
	$dateClause2 = " and transport_old.incident_date like '".$ymd."%%' ";
}
else if($ccsYear && $ccsMonth){
	$ym = sprintf("%04d-%02d", $ccsYear, $ccsMonth);
	$dateClause  = " and incident_date like '".$ym."-%%' ";
	$dateClause2 = " and transport_old.incident_date like '".$ym."-%%' ";
}
else if($ccsYear){
	$dateClause  = " and incident_date like '".$ccsYear."-%%' ";
	$dateClause2 = " and transport_old.incident_date like '".$ccsYear."-%%' ";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Car #<?php echo $car_id; ?> &mdash; Incident History</title>

<?php
/* @carfilter -- phFilterCss() only, for the .ph-filters bar below; this page
   builds its own fields because it filters on y/m/eq/level rather than
   period_filter's mode/sd/ed scheme.
   Emitted BEFORE history_theme.php on purpose: the theme carries the margin
   reset these controls depend on, and it has to come last to beat the
   Bootstrap rules it links. */
if(file_exists(dirname(__FILE__)."/period_filter.php")){
	require_once(dirname(__FILE__)."/period_filter.php");
	echo "<style type='text/css'>"; phFilterCss(); echo "</style>";
}
?>
<?php include("history_theme.php"); ?>
</head>
<body>
<?php
/* Console nav -- the pattern car_stats.php and equipt_stats.php already use.
   Tmenu_2.php emits ONLY the header markup and the <ul id="navMenu">, never
   <!DOCTYPE>/<html>/<head>, so it belongs here inside <body> and this page
   keeps its own document.

   Suppressed under embed=1. This page is normally reached with target=_top,
   deliberately breaking OUT of the 820px slide panel into the full window --
   but car_stats.php links here from inside that panel, so the guard matters
   the day someone drops the _top. */
if($NAV_SHOW){ require("Tmenu_2.php"); }
?>
<div class="ccs-page">

<div class="ccs-header">
	<h1>Car #<?php echo htmlspecialchars($car_id); ?> &mdash; Incident History</h1>
	<div class="sub">Combined current &amp; legacy incident records
		<?php
		/* @carryfilter -- an inherited filter has to be visible, or a page
		   showing a fraction of the car's history looks like missing data.
		   The equipment name is resolved from $causeMap, which is loaded
		   further down, so this reads it after the fact via a small query
		   rather than moving the map's load point. */
		/* @dayfix -- a single day has to name itself, or a one-row page under a
		   heading that says "March 2026" reads as data loss. */
		if($ccsYear){
			if($ccsDay){      echo " &mdash; ".date("d F Y", strtotime(sprintf("%04d-%02d-%02d",$ccsYear,$ccsMonth,$ccsDay))); }
			else if($ccsMonth){ echo " &mdash; ".date("F Y", strtotime(sprintf("%04d-%02d-01",$ccsYear,$ccsMonth))); }
			else {            echo " &mdash; Year ".$ccsYear; }
		}
		/* @levelfilter -- stated in the heading; a page showing a fraction of a
		   car's history with nothing saying why reads as missing data. */
		if($ccsLevel){ echo " &mdash; Level ".$ccsLevel." only"; }
		if($ccsEquipt){
			echo " &mdash; ".htmlspecialchars($ccsEquiptName !== '' ? $ccsEquiptName : 'Equipment #'.$ccsEquipt)." only";
		}
		?>
		&mdash; Line 3</div>
</div>

<div class="ccs-panel">
<div class="ccs-panel-head">
  <h3>Incident History</h3>
  <div class="ccs-panel-actions">
  <?php
  /* @carfilter -- The page already READ y / m / eq / level -- car_stats.php has
     been passing them through on its "Full incident history" button -- but
     there was no way to change them here. Arriving with a filter and being
     unable to widen or narrow it is the awkward half of a drill-down.

     Same bar as problem_history.php: one row, labelled controls inline, GET so
     the filtered view is linkable. car_id travels as a hidden field, since it
     is the page's subject rather than a filter.

     Options come from THIS CAR's own rows, not from the whole fleet: offering
     a year or an equipment type that yields nothing for car 12 is a dead end
     the user has to discover by clicking. */
  $fy = array();
  $q = $db->query("select distinct year(incident_date) as y
                     from incident_cars
                     inner join incident_union on incident_cars.incident_id=incident_union.id
                    where incident_cars.car_no*1='".$car_id."' and incident_date is not null
                    order by y desc");
  if($q){ while($r=$q->fetch_assoc()){ if((int)$r['y']>0) $fy[]=(int)$r['y']; } }

  $fe = array();
  $q = $db->query("select distinct incident_union.equipt as id, equipment.equipment_name as nm
                     from incident_cars
                     inner join incident_union on incident_cars.incident_id=incident_union.id
                     left  join equipment on equipment.id=incident_union.equipt
                    where incident_cars.car_no*1='".$car_id."' and incident_union.equipt is not null
                    order by nm");
  if($q){ while($r=$q->fetch_assoc()){
      $id=(int)$r['id']; if($id<=0) continue;
      $fe[$id] = ($r['nm']!==null && $r['nm']!=='') ? $r['nm'] : 'Equipment #'.$id;
  } }

  $anyFilter = ($ccsYear || $ccsMonth || $ccsDay || $ccsEquipt || $ccsLevel);
  ?>
  <form method="get" action="car_history.php" class="ph-filters">
  <input type="hidden" name="car_id" value="<?php echo (int)$car_id; ?>">

  <div class="ph-field">
    <label for="chYear">Year</label>
    <select name="y" id="chYear">
      <option value="">All years</option>
      <?php foreach($fy as $yv){ ?>
      <option value="<?php echo $yv; ?>"<?php echo $ccsYear==$yv?' selected':''; ?>><?php echo $yv; ?></option>
      <?php } ?>
    </select>
  </div>

  <div class="ph-field">
    <label for="chMonth">Month</label>
    <select name="m" id="chMonth">
      <option value="">All months</option>
      <?php for($mi=1;$mi<=12;$mi++){ ?>
      <option value="<?php echo $mi; ?>"<?php echo $ccsMonth==$mi?' selected':''; ?>><?php echo date("F", strtotime(sprintf("2000-%02d-01",$mi))); ?></option>
      <?php } ?>
    </select>
  </div>

  <div class="ph-field">
    <?php /* @dayfix -- arriving on one day with no way to widen back to the
             month is the awkward half of a drill-down, same reason this bar
             exists at all. 31 options always: which are valid depends on the
             month chosen in the SAME submit, so pruning here would need JS to
             stay honest, and an invalid combination is rejected above. */ ?>
    <label for="chDay">Day</label>
    <select name="d" id="chDay">
      <option value="">All days</option>
      <?php for($di=1;$di<=31;$di++){ ?>
      <option value="<?php echo $di; ?>"<?php echo $ccsDay==$di?' selected':''; ?>><?php echo $di; ?></option>
      <?php } ?>
    </select>
  </div>

  <div class="ph-field">
    <label for="chEq">Equipment</label>
    <select name="eq" id="chEq">
      <option value="">All equipment</option>
      <?php foreach($fe as $eid=>$enm){ ?>
      <option value="<?php echo $eid; ?>"<?php echo $ccsEquipt==$eid?' selected':''; ?>><?php echo htmlspecialchars($enm); ?></option>
      <?php } ?>
    </select>
  </div>

  <div class="ph-field">
    <label for="chLevel">Level</label>
    <select name="level" id="chLevel">
      <option value="">All levels</option>
      <?php for($lv=1;$lv<=4;$lv++){ ?>
      <option value="<?php echo $lv; ?>"<?php echo $ccsLevel==$lv?' selected':''; ?>><?php echo $lv; ?></option>
      <?php } ?>
    </select>
  </div>

  <div class="ph-field ph-field--action">
    <label aria-hidden="true">&nbsp;</label>
    <button type="submit">Apply</button>
  </div>
  <?php if($anyFilter){ ?>
  <a class="ph-clear" href="car_history.php?car_id=<?php echo (int)$car_id; ?>">Clear</a>
  <?php } ?>
  </form>
  </div>
</div>
<div class="ccs-panel-body">
<table class="table table-striped table-bordered bootstrap-datatable datatable2" width="100%" id="add_form" name="add_form">
    <thead>
    <tr>
        <th>Index No</th>
        <th>Incident Date/Time</th>
        <th>Time Resolved</th>
        <th>Duration</th>
        <th>Problem Type</th>
<!--
        <th>Equipment</th>
-->
        <th>Incident Number</th>
        <th>Description</th>
    </tr>
    </thead>
    <tbody>
<?php
// Single database. The is_transport_old half that used to be UNIONed in here
// is gone: when is_transport was corrupted, the old database's rows were
// restored INTO it, so reading both returned the same incident twice. The
// UNION was hiding that by de-duplicating identical rows — which also meant it
// silently dropped genuinely distinct ones.
//
// Verified before removing: every incident_no in is_transport_old.incident_union
// is present in is_transport.incident_union, with nothing held only in the old
// database. If a pre-2019 incident is ever found missing, restore it into
// is_transport rather than re-adding a query half here.
// @carryfilter -- equipment clause alongside the date clause, so the table AND
// every chart below (they all read this one result set) narrow together.
$equiptClause = $ccsEquipt ? " and incident_union.equipt = ".$ccsEquipt." " : "";
/* @levelfilter -- Applied to the row query, which every chart on this page is
   derived from, so the table and the figures narrow together. Chart 3 is the
   exception -- see the note where it is built. */
$levelClause  = $ccsLevel  ? " and incident_union.level = ".$ccsLevel." "   : "";
$sql="select * from incident_cars inner join incident_union on incident_cars.incident_id=incident_union.id where incident_cars.car_no*1='".$car_id."' ".$dateClause." ".$equiptClause." ".$levelClause." order by incident_date desc";
$rs=$db->query($sql);
$nm=$rs->num_rows;

// ---- Equipment map: load once and resolve rows against it instead of a
// query per row. ----
$causeMap=array();
$cmRs=$db->query("select id, equipment_name from equipment");
if($cmRs){ while($cm=$cmRs->fetch_assoc()){ if(trim($cm['equipment_name'])!=='') $causeMap[$cm['id']]=$cm['equipment_name']; } }

// ---- Buffer all rows for two passes: rows with recorded equipment train the
// description classifier, then blanks are scored against it. The DB is never
// written — suggestions live only in this page's rendering. ----
$allRows=array();
for($i=0;$i<$nm;$i++){ $allRows[]=$rs->fetch_assoc(); }

// Pass 1 — learn word patterns per equipment from rows that HAVE one.
$nbModel = ccsTrainClassifier($allRows,$causeMap);

// Pass 2 — render + aggregate. Chart aggregates are built in the same pass,
// so charts and table always reflect the same $dateClause filter.
//
// Three outcomes per row: recorded equipment, an auto-suggested equipment
// where the classifier was confident, or neither. Only that last group — the
// rows the classifier ABSTAINED on — feeds the recurring-words chart, so it
// describes exactly the residue nothing else could account for.
$monthlyCounts=array();   // [ "YYYY-MM" => [ equipment => count ] ]
$problemCounts=array();   // [ equipment => count ]
$repeatDates=array();     // [ equipment => [unix ts, ...] ] — @repeat
$repeatExcluded=0;        // suggested or unplaced rows, not eligible for a repeat claim
$suggestedCounts=array(); // [ equipment => how many were auto-suggested ]
$suggestedTotal=0;
$blankTotal=0;
$sevGrid=array();         // [ equipment => [ level => count ] ]
$sevLevels=array();       // set of distinct severity levels present
$sevLevels["L1"]=0;
$sevLevels["L2"]=0;
$sevLevels["L3"]=0;
$sevLevels["L4"]=0;

foreach($allRows as $row){

	$isSuggested=false;
	if(isset($causeMap[$row['equipt']])){
		$problemType=$causeMap[$row['equipt']];
	}
	else{
		$guess=ccsClassifyDescription($nbModel,$row['description']);
		if($guess!==null){
			$problemType=$guess;
			$isSuggested=true;
			if(!isset($suggestedCounts[$problemType])) $suggestedCounts[$problemType]=0;
			$suggestedCounts[$problemType]++;
			$suggestedTotal++;
		}
		else{
			$problemType='';   // the blank bucket the charts already handle
			$blankTotal++;
		}
	}

	$monthKey=date("Ym", strtotime($row['incident_date']));
	if(!isset($monthlyCounts[$monthKey])) $monthlyCounts[$monthKey]=array();
	if(!isset($monthlyCounts[$monthKey][$problemType])) $monthlyCounts[$monthKey][$problemType]=0;
	$monthlyCounts[$monthKey][$problemType]++;

	if(!isset($problemCounts[$problemType])) $problemCounts[$problemType]=0;
	$problemCounts[$problemType]++;

	/* @repeat -- Dates per equipment, for the repeat chart. RECORDED types
	   only: a repeat is a claim about the same component failing twice, and a
	   classifier guess is not strong enough to carry that. Suggested and blank
	   rows are counted separately and reported under the chart. */
	if(!$isSuggested && $problemType !== ''){
		$rpTs = strtotime($row['incident_date']);
		if($rpTs){
			if(!isset($repeatDates[$problemType])) $repeatDates[$problemType]=array();
			$repeatDates[$problemType][] = $rpTs;
		}
	}
	else { $repeatExcluded++; }

	// Equipment x severity. The other figures answer "what fails often"; this
	// answers "what fails BADLY" — an equipment with a modest row total but a
	// hot L4 cell is a maintenance priority a frequency ranking would bury.
	// Suggested equipment is included: the severity is a recorded value even
	// when the equipment was inferred, and excluding those rows would
	// understate the serious faults. The print note says so.
	$lvRaw = isset($row['level']) ? trim($row['level']) : '';
	if($lvRaw === ''){ $lv = 'None'; }
	else { 
		if($lvRaw=="0"){
		   $lv = 'None';
		}
		else {
	$lv = (strtoupper(substr($lvRaw,0,1)) === 'L') ? strtoupper($lvRaw) : 'L'.$lvRaw; }
			
			
		}
	
	
	
	
	$eqKey = ($problemType === '') ? 'Unspecified' : $problemType;
	if(!isset($sevGrid[$eqKey])) $sevGrid[$eqKey]=array();
	if(!isset($sevGrid[$eqKey][$lv])) $sevGrid[$eqKey][$lv]=0;
	$sevGrid[$eqKey][$lv]++;
	if($lv>0){
	$sevLevels[$lv]=true;
	}
	?>
    <tr>
        <td><?php echo $row['index_no']; ?></td>
        <td><?php echo date("Y-m-d H:iA", strtotime($row['incident_date'])); ?></td>
        <td><?php 
		if(date("Y-m-d",strtotime($row['resolution_date']))!=="1970-01-01"){		
		echo date("Y-m-d H:iA", strtotime($row['resolution_date'])); 
		}
		else {
			echo "&nbsp;";

		}			
		
		?></td>
        <td><?php echo $row['duration']; ?></td>
        <td><?php
			// Recorded equipment renders plain; auto-suggested is visibly
			// marked so nobody mistakes inference for recorded data.
			if($isSuggested){
				echo "<span style='font-style:italic; opacity:.75;' title='Auto-suggested from the description text — not a recorded value'>".htmlspecialchars($problemType)." <small>(suggested)</small></span>";
			}
			else if($problemType===''){
				echo "<span style='opacity:.55;'>Unspecified</span>";
			}
			else{
				echo htmlspecialchars($problemType);
			}
		?></td>
		
		<?php 
		
		/**
		
        <td><?php echo isset($causeMap[$row['equipt']]) ? htmlspecialchars($causeMap[$row['equipt']]) : "&nbsp;"; ?></td>
	
		*/ 
		?>
	<td>
<a href='#' class='two' onclick='openSlidePanel("edit_ccdr.php?ir=<?php echo  $row['id']; ?>&embed=1","Incident - <?php echo htmlspecialchars($row['incident_no']); ?>")'><?php echo $row['incident_no']; ?></a></td>
</td>

<?php
/**
        <td><a href='#' onclick='window.open("edit_ccdr.php?ir=<?php echo $row['id']; ?>")'><?php echo $row['incident_no']; ?></a></td>
*/
?>


        <td><?php echo $row['description']; ?></td>
    </tr>
<?php } ?>
    </tbody>
</table>
</div>
</div>
</div>

<?php
// Severity columns in ascending order, with any unlevelled rows last.
$sevOrder = array_keys($sevLevels);
sort($sevOrder);
if(in_array('None',$sevOrder)){
	$sevOrder = array_diff($sevOrder, array('None'));
	$sevOrder[] = 'None';
	$sevOrder = array_values($sevOrder);
}

// Heatmap rows: equipment ranked by total, "Unspecified" always last.
$sevRowTotals=array();
foreach($sevGrid as $eq=>$byLv){ $sevRowTotals[$eq]=array_sum($byLv); }
arsort($sevRowTotals);
$sevRows=array();
foreach($sevRowTotals as $eq=>$t){ if($eq!=='Unspecified') $sevRows[]=$eq; }
if(isset($sevRowTotals['Unspecified'])) $sevRows[]='Unspecified';
$sevRows = array_slice($sevRows, 0, 8);

?>

<!-- Print-only chart summary. Hidden on screen (the live table above is
     the on-screen view); rendered to static images and injected into the
     TableTools print window — see the script block near the bottom. -->
<?php
/* @repeat -- One entry per equipment that failed 2+ times, with the span the
   failures cover. The span is what separates the two cases the raw count
   cannot: five failures over five months is a component wearing out; three in
   one week is a repair that did not hold. */
$repeatRows=array();
foreach($repeatDates as $eq=>$tsList){
	if(count($tsList) < 2) continue;             // once is not a repeat
	sort($tsList);
	$spanDays = (int)round(($tsList[count($tsList)-1] - $tsList[0]) / 86400);
	$months   = array();
	foreach($tsList as $t){ $months[date("Y-m",$t)] = true; }
	$repeatRows[] = array(
		'equipment' => $eq,
		'times'     => count($tsList),
		'spanDays'  => $spanDays,
		'months'    => count($months)
	);
}
usort($repeatRows, function($a,$b){
	if($b['times'] !== $a['times']) return $b['times'] - $a['times'];
	return $a['spanDays'] - $b['spanDays'];      // tighter cluster ranks first
});
$repeatRows = array_slice($repeatRows, 0, 6);
?>
<div id="ccs-print-charts" style="display:none;">
	<canvas id="ccsChartMonthly" width="340" height="230"></canvas>
	<canvas id="ccsChartPareto"  width="340" height="200"></canvas>
	<canvas id="ccsSeverity"     width="340" height="220"></canvas>
	<canvas id="ccsRepeat"       width="340" height="200"></canvas>
</div>

<script>
var ccsCoverageNote = <?php echo json_encode(htmlspecialchars($coverageNote, ENT_QUOTES)); ?>;
// Raw aggregates from the same query/filter as the table above.
<?php
// @months -- Chart 1 took its x-axis from whichever months returned rows, so a
// quiet month vanished and its neighbours closed up: February next to June read
// as consecutive. Fill the span the report actually covers.
//
// Unlike the tables on car_stats.php, an axis has no row labels to make the
// jump visible, which is exactly why filling belongs here and not there.
//
// A filled month is an empty type map, so every stacked category sums to 0 and
// the bar simply has no height.
$mk = array_keys($monthlyCounts);
sort($mk);
if($ccsYear && $ccsMonth){
	$fillFrom = sprintf("%04d%02d", $ccsYear, $ccsMonth);   // one month; nothing to sequence
	$fillTo   = $fillFrom;
}
else if($ccsYear){
	$fillFrom = sprintf("%04d01", $ccsYear);                // the whole filtered year
	$fillTo   = sprintf("%04d12", $ccsYear);
}
else if(count($mk)){
	$fillFrom = (string)$mk[0];                             // unfiltered: earliest to latest
	$fillTo   = (string)$mk[count($mk)-1];
}
else { $fillFrom = $fillTo = ''; }

if($fillFrom !== ''){
	$cur = new DateTime(substr($fillFrom,0,4)."-".substr($fillFrom,4,2)."-01");
	$end = new DateTime(substr($fillTo,0,4)."-".substr($fillTo,4,2)."-01");
	$seq = array();
	while($cur <= $end){
		$k = $cur->format("Ym");
		$seq[$k] = isset($monthlyCounts[$k]) ? $monthlyCounts[$k] : array();
		$cur->modify("+1 month");
	}
	// Any month outside the filtered span that still holds rows is kept rather
	// than dropped — losing data to a display rule would be the worse bug.
	foreach($monthlyCounts as $k=>$v){ if(!isset($seq[$k])) $seq[$k]=$v; }
	ksort($seq);
	$monthlyCounts = $seq;
}
?>
var ccsMonthlyCounts = <?php echo json_encode($monthlyCounts, JSON_FORCE_OBJECT); ?>;
var ccsProblemCounts = <?php echo json_encode($problemCounts); ?>;
var ccsSuggested     = <?php echo json_encode($suggestedCounts, JSON_FORCE_OBJECT); ?>;
var ccsSuggestedTotal = <?php echo (int)$suggestedTotal; ?>;
var ccsSevGrid       = <?php echo json_encode($sevGrid, JSON_FORCE_OBJECT); ?>;
var ccsSevRows       = <?php echo json_encode($sevRows); ?>;
var ccsLevelOnly     = <?php echo (int)$ccsLevel; ?>;   /* @levelfilter */
var ccsSevCols       = <?php echo json_encode(array_values($sevOrder)); ?>;
var ccsBlankTotal    = <?php echo (int)$blankTotal; ?>;
var ccsRepeat        = <?php echo json_encode($repeatRows); ?>;          /* @repeat */
var ccsRepeatSkipped = <?php echo (int)$repeatExcluded; ?>;
</script>

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
     Print-with-charts. Attached last, after custom.min.js /
     additional.js, because those two are what auto-initialise the
     .datatable2 table (and its TableTools print button) — this
     block has to run after that init exists, or there is no print
     button yet to hook into.
     ============================================================ -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
$(function(){

	// ---- 1. Build the two charts once, from the PHP-aggregated data ----
	// Only the top N categories get their own series/bar; the rest is
	// summarised in text rather than drawn as a competing bar, so a large
	// tail can't visually outrank the leaders. Rows with no matched
	// equipment (blank equipment_name) are counted separately and never
	// enter the top-N ranking.
	var TOP_N = 3;
	var BLANK_KEY = ''; // PHP null / no-match equipment_name lands here

	var palette = ['#2a78d6','#eb6834','#1baf7a','#eda100','#e87ba4','#008300'];
	var othersColor = '#9c9a92';
	var textInk = getComputedStyle(document.documentElement).getPropertyValue('--text-primary').trim() || '#111';
	var mutedInk = getComputedStyle(document.documentElement).getPropertyValue('--text-secondary').trim() || '#555';
	var gridInk = 'rgba(137,135,129,0.20)';

	var months = Object.keys(ccsMonthlyCounts).sort();

	/* @months -- The keys are "YYYYMM" because that string sorts correctly and
	   is safe as an object key; they were never meant to reach the axis, but
	   they were being used as the labels verbatim. Keep the keys for lookups
	   and render a separate label array. Two lines rather than "Aug 2026" on
	   one, so twelve of them fit a 340px canvas without rotating. */
	var CCS_MON = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
	function ccsMonthLabel(k){
		var t = String(k);
		if(!/^\d{6}$/.test(t)) return t;        /* never silently mangle a bad key */
		var mi = parseInt(t.slice(4,6), 10) - 1;
		if(!(mi >= 0 && mi < 12)) return t;
		return [CCS_MON[mi], t.slice(0,4)];
	}
	var blankCount = ccsProblemCounts[BLANK_KEY] || 0;

	/* @noothers -- The synthetic "Others" bucket is already gone. What is left
	   is a REAL equipment row literally named "Others" (or "Other"), which
	   ranks like any category and, being a catch-all, is frequently large
	   enough to take one of the three slots. It is still a legitimate record,
	   so it is not dropped -- it is held out of the ranking and reported under
	   the chart with the tail, because "the top 3 equipment types" is not a
	   useful answer when one of the three is "unspecified".

	   Matched case-insensitively on the whole label only, so a genuine type
	   containing the word (say "Other Auxiliary") is untouched. */
	function isCatchAll(t){
		var v = String(t).trim().toLowerCase();
		return v === 'others' || v === 'other' || v === 'uncategorized' || v === 'unspecified';
	}
	var catchAllCount = Object.keys(ccsProblemCounts)
		.filter(isCatchAll)
		.reduce(function(s,t){ return s + ccsProblemCounts[t]; }, 0);

	var rankedTypes = Object.keys(ccsProblemCounts)
		.filter(function(t){ return t !== BLANK_KEY && !isCatchAll(t); })
		.sort(function(a,b){ return ccsProblemCounts[b]-ccsProblemCounts[a]; });
	var topTypes = rankedTypes.slice(0, TOP_N);
	var tailTypes = rankedTypes.slice(TOP_N);

	var totalIncidents = rankedTypes.reduce(function(s,t){ return s+ccsProblemCounts[t]; }, 0) + blankCount + catchAllCount;
	var tailTotal = tailTypes.reduce(function(s,t){ return s+ccsProblemCounts[t]; }, 0);

	// ---- Chart 1: monthly stacked trend, top 3 only ----
	// @noothers -- "Others" is gone. It was the tail equipment types AND the
	// unclassified rows folded into one grey band, which on a chart captioned
	// "top 3" read as a fourth type and was routinely the tallest. Two
	// unrelated things sharing a colour, competing with the thing the chart is
	// about. The excluded volume is stated under the chart instead, where it
	// is a caveat rather than a series.
	function trendBucket(type){
		return topTypes.indexOf(type) !== -1 ? type : null;
	}
	var trendCategories = topTypes.slice();
	var monthlyBucketed = {};
	months.forEach(function(m){
		monthlyBucketed[m] = {};
		trendCategories.forEach(function(c){ monthlyBucketed[m][c] = 0; });
		Object.keys(ccsMonthlyCounts[m]).forEach(function(type){
			var b = trendBucket(type);
			if(b !== null) monthlyBucketed[m][b] += ccsMonthlyCounts[m][type];
		});
	});
	var monthlyDatasets = trendCategories.map(function(cat, idx){
		return {
			label: cat,
			data: months.map(function(m){ return monthlyBucketed[m][cat]; }),
			backgroundColor: palette[idx % palette.length]
		};
	});

	new Chart(document.getElementById('ccsChartMonthly'), {
		type: 'bar',
		data: { labels: months.map(ccsMonthLabel), datasets: monthlyDatasets },
		options: {
			responsive: false,
			animation: false,
			plugins: {
				title: { display: true, text: 'Incidents by month — top ' + TOP_N + ' equipment', color: textInk, font: { size: 11, weight: 'normal' }, padding: { bottom: 6 } },
				legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 }, color: mutedInk } }
			},
			scales: {
				x: { stacked: true, ticks: { color: mutedInk, font: { size: 10 }, maxRotation: 0, autoSkipPadding: 3 }, grid: { display: false } },
				y: { stacked: true, ticks: { color: mutedInk, precision: 0, font: { size: 10 } }, grid: { color: gridInk } }
			}
		}
	});

	// ---- Chart 2: top-3 leaders as horizontal bars — two stacked layers per
	// bar: solid = recorded equipment, lighter = auto-suggested from the
	// description. Tail and unplaced rows are drawn as a footnote *inside*
	// the canvas (so it survives the toDataURL handoff into the print window
	// — sibling HTML would not).
	function hexToRgba(hex, a){
		var n = parseInt(hex.slice(1), 16);
		return 'rgba(' + ((n>>16)&255) + ',' + ((n>>8)&255) + ',' + (n&255) + ',' + a + ')';
	}

	var footnotePlugin = {
		id: 'ccsFootnote',
		afterDraw: function(chart){
			var ctx = chart.ctx;
			var area = chart.chartArea;
			var lines = [];
			if(tailTotal > 0){
				lines.push('+ ' + tailTotal + ' more across ' + tailTypes.length + ' other equipment type' + (tailTypes.length === 1 ? '' : 's'));
			}
			if(catchAllCount > 0){
				lines.push('+ ' + catchAllCount + ' recorded only as a catch-all category');
			}
			if(ccsSuggestedTotal > 0){
				lines.push('Lighter segments: ' + ccsSuggestedTotal + ' auto-suggested from descriptions');
			}
			if(blankCount > 0){
				/* @norecurring -- was "(see next figure)", which pointed at the
				   recurring-words chart. The count still belongs here; the
				   chart it referred to is gone. */
				lines.push('+ ' + blankCount + ' with no equipment recorded and none suggested');
			}
			if(!lines.length) return;
			ctx.save();
			ctx.font = '10px Arial, sans-serif';
			ctx.fillStyle = mutedInk;
			ctx.textAlign = 'left';
			ctx.textBaseline = 'top';
			var y = chart.height - (lines.length * 13) - 6;
			ctx.strokeStyle = gridInk;
			ctx.lineWidth = 1;
			ctx.beginPath();
			ctx.moveTo(area.left, y - 6);
			ctx.lineTo(chart.width - 8, y - 6);
			ctx.stroke();
			lines.forEach(function(ln, i){ ctx.fillText(ln, area.left, y + i * 13); });
			ctx.restore();
		}
	};

	var topShare = totalIncidents ? Math.round(topTypes.reduce(function(s,t){ return s+ccsProblemCounts[t]; }, 0) / totalIncidents * 100) : 0;

	var topConfirmed = topTypes.map(function(t){ return (ccsProblemCounts[t]||0) - (ccsSuggested[t]||0); });
	var topSuggested = topTypes.map(function(t){ return ccsSuggested[t]||0; });

	new Chart(document.getElementById('ccsChartPareto'), {
		type: 'bar',
		data: {
			labels: topTypes,
			datasets: [
				{
					label: 'Recorded',
					data: topConfirmed,
					backgroundColor: topTypes.map(function(t, idx){ return palette[idx % palette.length]; }),
					barThickness: 22
				},
				{
					label: 'Suggested',
					data: topSuggested,
					backgroundColor: topTypes.map(function(t, idx){ return hexToRgba(palette[idx % palette.length], 0.4); }),
					borderRadius: 3,
					barThickness: 22
				}
			]
		},
		options: {
			indexAxis: 'y',
			responsive: false,
			animation: false,
			layout: { padding: { right: 22, bottom: 34 } },
			plugins: {
				title: { display: true, text: 'Top ' + TOP_N + ' equipment by incidents (' + topShare + '% of total)', color: textInk, font: { size: 11, weight: 'normal' }, padding: { bottom: 8 } },
				legend: { display: false },
				tooltip: { callbacks: { label: function(c){ return c.parsed.x + (c.datasetIndex === 1 ? ' suggested' : ' recorded'); } } }
			},
			scales: {
				x: { stacked: true, ticks: { color: mutedInk, precision: 0, font: { size: 10 } }, grid: { color: gridInk } },
				y: { stacked: true, ticks: { color: textInk, font: { size: 11 } }, grid: { display: false } }
			}
		},
		plugins: [footnotePlugin, {
			id: 'barTotalLabels',
			afterDatasetsDraw: function(chart){
				var ctx = chart.ctx, meta = chart.getDatasetMeta(1);
				ctx.save(); ctx.font = '11px Arial, sans-serif'; ctx.fillStyle = textInk;
				ctx.textBaseline = 'middle'; ctx.textAlign = 'left';
				meta.data.forEach(function(bar,i){ ctx.fillText(topConfirmed[i] + topSuggested[i], bar.x + 6, bar.y); });
				ctx.restore();
			}
		}]
	});

	// ---- Chart 3: equipment x severity. Hand-drawn on a raw canvas (not
	// Chart.js) so it flattens to an image for the TableTools print handoff
	// like the others. Cell shading runs on a severity ramp — green through
	// amber to red across the level columns — so a hot L4 cell reads as a
	// priority at a glance, which a count-only ranking cannot show.
	(function drawSeverity(){
		var cv = document.getElementById('ccsSeverity');
		var ctx = cv.getContext('2d');
		var W = cv.width, H = cv.height;
		ctx.clearRect(0,0,W,H);
		ctx.textBaseline = 'middle';

		ctx.font = '11px Arial, sans-serif'; ctx.fillStyle = textInk; ctx.textAlign = 'left';
		/* @levelfilter -- On car_stats.php the severity TILES stay unfiltered,
		   because they are also the control and filtering them would erase the
		   way back. This chart is not a control -- there is no level picker on
		   this page -- and its grid is built inside the row loop from the same
		   filtered set every other figure here uses. Keeping it unfiltered
		   would need a second pass over the rows purely for one chart, and
		   would put an unfiltered figure among filtered ones with nothing
		   marking it. So it narrows with the page, and says so instead. */
		ctx.fillText('Equipment \u00d7 severity' + (ccsLevelOnly ? ' \u2014 Level ' + ccsLevelOnly + ' only' : ''), 0, 9);

		if(!ccsSevRows.length || !ccsSevCols.length){
			ctx.font = '10px Arial, sans-serif'; ctx.fillStyle = mutedInk;
			ctx.fillText('No severity data to chart.', 0, 34);
			return;
		}

		// Severity ramp by column position; unlevelled rows get neutral grey.
		var ramp = ['27,175,122','237,161,0','227,73,72','163,45,45'];
		function baseFor(col, idx){
			if(col === 'None') return '156,154,146';
			return ramp[Math.min(idx, ramp.length-1)];
		}

		var padL = 118, padT = 34, padR = 8, padB = 16;
		var gridW = W - padL - padR, gridH = H - padT - padB;
		var cellW = gridW / ccsSevCols.length, cellH = gridH / ccsSevRows.length;

		var maxV = 0;
		ccsSevRows.forEach(function(r){
			ccsSevCols.forEach(function(c){
				var v = (ccsSevGrid[r] && ccsSevGrid[r][c]) || 0;
				if(v > maxV) maxV = v;
			});
		});
		if(maxV === 0) maxV = 1;

		// column headers
		ctx.font = '10px Arial, sans-serif'; ctx.fillStyle = mutedInk; ctx.textAlign = 'center';
		ccsSevCols.forEach(function(c,ci){ ctx.fillText(c, padL + ci*cellW + cellW/2, padT - 10); });

		ccsSevRows.forEach(function(rowName,ri){
			var y = padT + ri*cellH;
			ctx.font = '10px Arial, sans-serif';
			ctx.fillStyle = (rowName === 'Unspecified') ? mutedInk : textInk;
			ctx.textAlign = 'right';
			var label = rowName.length > 17 ? rowName.slice(0,16)+'\u2026' : rowName;
			ctx.fillText(label, padL - 6, y + cellH/2);

			ccsSevCols.forEach(function(col,ci){
				var v = (ccsSevGrid[rowName] && ccsSevGrid[rowName][col]) || 0;
				var x = padL + ci*cellW;
				var base = baseFor(col, ci);
				var t = v === 0 ? 0.05 : 0.15 + (v/maxV)*0.80;
				ctx.fillStyle = 'rgba('+base+','+t.toFixed(3)+')';
				ctx.fillRect(x+1, y+1, cellW-2, cellH-2);
				if(v > 0){
					ctx.fillStyle = (v/maxV > 0.55) ? '#fff' : mutedInk;
					ctx.font = '10px Arial, sans-serif'; ctx.textAlign = 'center';
					ctx.fillText(String(v), x + cellW/2, y + cellH/2);
				}
			});
		});

		ctx.font = '9px Arial, sans-serif'; ctx.fillStyle = mutedInk; ctx.textAlign = 'left';
		ctx.fillText('Colour = severity, depth = count', padL, H - 5);
	})();

	// ---- Chart 3: what is left after the classifier has done its work.
	// These are rows with no recorded equipment that the classifier ABSTAINED



	/* ============ Chart 4: equipment that failed more than once ============
	   @repeat -- Deliberately NOT a bar chart. Bars rank by count, which is
	   what the Pareto already does; a dot per separate failure makes the
	   OCCASION the unit, and the trailing note carries what a bar cannot --
	   whether the failures were spread out or clustered. Those need different
	   responses and look identical on a bar chart.
	   Hand-drawn on a raw canvas so it flattens to an image for the print
	   handoff exactly like the Chart.js canvases. */
	(function drawRepeat(){
		var cv = document.getElementById('ccsRepeat');
		if(!cv) return;
		var c = cv.getContext('2d');
		c.clearRect(0,0,cv.width,cv.height);
		c.textBaseline = 'middle';
		c.font = '11px Arial, sans-serif'; c.fillStyle = textInk; c.textAlign = 'left';
		c.fillText('Equipment that failed more than once', 0, 9);

		if(!ccsRepeat.length){
			c.font = '10px Arial, sans-serif'; c.fillStyle = mutedInk;
			c.fillText('No equipment failed more than once in this period.', 0, 34);
			return;
		}

		var rowH = 26, top = 32, labelW = 112, dotR = 5, gap = 13;
		ccsRepeat.forEach(function(r, i){
			var y = top + i*rowH;
			c.font = '10px Arial, sans-serif'; c.fillStyle = textInk; c.textAlign = 'left';
			var name = r.equipment;
			while(c.measureText(name).width > labelW - 6 && name.length > 4){ name = name.slice(0,-2); }
			if(name !== r.equipment) name += '\u2026';
			c.fillText(name, 0, y);

			/* Colour by how TIGHT the cluster is, not by volume: a same-week
			   repeat is the one worth chasing. */
			var perMonth = r.times / Math.max(r.months, 1);
			c.fillStyle = (r.spanDays <= 7) ? '#A32D2D' : (perMonth >= 2 ? '#E24B4A' : '#F09595');

			var shown = Math.min(r.times, 8);
			for(var d = 0; d < shown; d++){
				c.beginPath(); c.arc(labelW + dotR + d*gap, y, dotR, 0, Math.PI*2); c.fill();
			}
			var x = labelW + dotR + shown*gap + 4;
			if(r.times > shown){ c.font='10px Arial, sans-serif'; c.fillText('+'+(r.times-shown), x, y); x += 20; }

			c.font = '10px Arial, sans-serif'; c.fillStyle = mutedInk;
			var when = (r.spanDays <= 7)
				? 'all within ' + (r.spanDays <= 1 ? 'a day' : r.spanDays + ' days')
				: 'across ' + r.months + ' month' + (r.months === 1 ? '' : 's');
			c.fillText(r.times + ' times, ' + when, x + 4, y);
		});

		if(ccsRepeatSkipped > 0){
			c.font = '10px Arial, sans-serif'; c.fillStyle = mutedInk; c.textAlign = 'left';
			c.fillText(ccsRepeatSkipped + ' incident' + (ccsRepeatSkipped===1?'':'s') +
			           ' without a recorded equipment are not counted here', 0, cv.height - 6);
		}
	})();

	// ---- 2. Intercept the existing TableTools print button ----
	// additional.js/custom.min.js auto-init .datatable2 and, as part of
	// that, create the TableTools print button somewhere in the
	// .DTTT_container / .dt-buttons toolbar above #add_form. Rather than
	// touch the shared init (used by every other page), this page
	// replaces just that button's click handler with our own, once the
	// table (and therefore the button) exists in the DOM.
	var printBtn = $('#add_form_wrapper').find('.DTTT_button_print, .buttons-print');

	if(printBtn.length){
		printBtn.off('click').on('click', function(e){
			e.preventDefault();
			e.stopImmediatePropagation();
			ccsPrintWithCharts();
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
	function ccsFullTableHtml(){
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

		if(!rows || !rows.length) return { html: el.outerHTML, count: $el.find('tbody tr').length };

		var $clone = $el.clone();
		var $tbody = $clone.find('tbody').empty();
		$.each(rows, function(i, node){ $tbody.append($(node).clone()); });

		// Strip DataTables' runtime artifacts — inline widths, sort classes
		// and ARIA attributes — so the print stylesheet has full control.
		$clone.removeAttr('style').removeAttr('width').removeClass('dataTable');
		$clone.find('th, td').removeAttr('style').removeAttr('width');
		$clone.find('th').removeAttr('class').removeAttr('aria-label')
		      .removeAttr('aria-sort').removeAttr('tabindex').removeAttr('role');
		$clone.find('tr').removeAttr('class').removeAttr('role');

		return { html: $clone[0].outerHTML, count: rows.length };
	}

	function ccsPrintWithCharts(){
		var chartMonthlyImg = document.getElementById('ccsChartMonthly').toDataURL('image/png');
		var chartParetoImg  = document.getElementById('ccsChartPareto').toDataURL('image/png');
		var severityImg     = document.getElementById('ccsSeverity').toDataURL('image/png');
		var repeatImg       = document.getElementById('ccsRepeat').toDataURL('image/png');
		var captured  = ccsFullTableHtml();
		var tableHtml = captured.html;
		var rowCount  = captured.count;

		var win = window.open('', '_blank');
		win.document.write(
			'<html><head><title>Car <?php echo htmlspecialchars($car_id); ?> \u2014 Incident History</title>' +
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
				'table{ width:100%; border-collapse:collapse; font-size:9.5px; }' +
				'thead{ display:table-header-group; }' +
				'th{ background:#1f4e79; color:#fff; text-align:left; padding:6px 7px;' +
					' font-size:9px; font-weight:600; text-transform:uppercase;' +
					' letter-spacing:.04em; border:1px solid #1f4e79; }' +
				'td{ padding:5px 7px; border:1px solid #e5e7eb; vertical-align:top; }' +
				'tbody tr:nth-child(even) td{ background:#f6f8fa; }' +
				'tr{ page-break-inside:avoid; }' +
				'a{ color:inherit; text-decoration:none; pointer-events:none; }' +
				'.rpt-foot{ margin-top:14px; border-top:1px solid #d1d5db; padding-top:6px;' +
					' font-size:8.5px; color:#6b7280; }' +
			'</style></head><body>' +
			'<div class="rpt-head">' +
				'<div class="rpt-org">DOTr &middot; MRT-3 Line 3 &middot; Operations Control</div>' +
				'<h1 class="rpt-title">Rolling Stock Incident History</h1>' +
				'<p class="rpt-subject">Car #<?php echo htmlspecialchars($car_id); ?></p>' +
			'</div>' +
			'<div class="rpt-meta">' +
			'<span><b>Report period:</b> <?php
  if(!isset($_GET["y"])){ echo "All records"; }
  else if(isset($_GET["m"]) && $_GET["m"] !== ""){
    echo htmlspecialchars(date("F Y", strtotime((int)$_GET["y"]."-".(int)$_GET["m"]."-01")));
  }
  else { echo htmlspecialchars((int)$_GET["y"]); }
?></span>' +
				<?php /* @levelfilter -- every inherited filter stated on the printout,
				         or a report showing a fraction of the car's history has
				         nothing on the page explaining why. */ ?>
				'<?php if($ccsLevel){ ?><span><b>Level:</b> <?php echo (int)$ccsLevel; ?> only</span><?php } ?>' +
				'<?php if($ccsEquipt){ ?><span><b>Equipment:</b> <?php echo htmlspecialchars($ccsEquiptName !== "" ? $ccsEquiptName : "#".$ccsEquipt, ENT_QUOTES); ?> only</span><?php } ?>' +
				'<span><b>Records:</b> ' + rowCount + '</span>' +
				'<span><b>Generated:</b> <?php echo date("d M Y, H:i"); ?></span>' +
			'</div>' +

			'<h2 class="sec">Summary</h2>' +
			'<div class="charts">' +
				'<div class="chart"><img src="' + chartMonthlyImg + '">' + '<div class="cap">Figure 1 &mdash; Incidents by month, by equipment</div></div>' +
				'<div class="chart"><img src="' + chartParetoImg + '">' + '<div class="cap">Figure 2 &mdash; Leading equipment by incident count</div></div>' +
				'<div class="chart"><img src="' + severityImg + '">' + '<div class="cap">Figure 3 &mdash; Equipment by severity level' + (ccsLevelOnly ? ' (Level ' + ccsLevelOnly + ' only)' : '') + '</div></div>' +
				'<div class="chart"><img src="' + repeatImg + '">' + '<div class="cap">Figure 4 &mdash; Equipment that failed more than once</div></div>' +
				'<p class="note">Equipment is the recorded value where one exists. Where none was recorded, an equipment is auto-suggested from the description text when the match is confident &mdash; shown italic in the log and as lighter segments in Figure 2, and indicative only. Incidents the suggestion could not place remain unspecified. Figure 4 lists equipment that failed more than once, using recorded values only &mdash; a repeat is a claim about the same component failing twice, which a suggestion is not strong enough to carry. Figure 3 crosses equipment against recorded severity, so an equipment with few incidents but several at the highest level stands out &mdash; severity is a recorded value throughout, including on rows whose equipment was suggested.</p>' +
			'</div>' +

			'<h2 class="sec">Incident Records</h2>' +
			'<div class="tbl-head">' +
				'<h3>Car #<?php echo htmlspecialchars($car_id); ?> &mdash; incident log</h3>' +
				'<span class="count">' + rowCount + ' record' + (rowCount === 1 ? '' : 's') + '</span>' +
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
<?php
function getProblemType($db,$type){
	$sql="select * from equipment_type where equipment_code='".$type."'";
	$rs=$db->query($sql);

	$nm=$rs->num_rows;
	$row=$rs->fetch_assoc();

	$problem=$row['equipment_name'];
	return $problem;
}
function getEquipmentType($db,$type){
	$sql="select * from equipment where id='".$type."'";
	$rs=$db->query($sql);

	$nm=$rs->num_rows;
	$row=$rs->fetch_assoc();

	$problem=$row['equipment_name'];
	return $problem;
}

// ============================================================
// Description -> equipment suggestion, plus text mining for the residue.
//
// Rows with recorded equipment train a small naive Bayes classifier; blanks
// are then scored against it and get an auto-suggested equipment when the
// model is confident. Suggestions are display-only — nothing is written back
// to the database, and the model retrains on every page load.
//
// Rows the classifier ABSTAINS on stay unplaced and are reported as a count in
// the trend chart's footnote. They used to have their descriptions mined for
// recurring words as Figure 4; that chart is gone -- token frequency described
// the vocabulary operators type, not the faults that occurred, so one battery
// problem became three bars and filler like "RS" outranked real signal.
// ccsTokenize stays: the classifier itself still needs it.
// ============================================================

function ccsTokenize($text){
	if($text===null) return array();
	$text = strtolower($text);
	$text = preg_replace('/[^a-z0-9\s]/',' ',$text);
	$tokens = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
	static $stop = array('the'=>1,'a'=>1,'an'=>1,'and'=>1,'or'=>1,'of'=>1,'to'=>1,'in'=>1,'on'=>1,'at'=>1,
		'is'=>1,'was'=>1,'were'=>1,'for'=>1,'coming'=>1,'with'=>1,'not'=>1,'request'=>1,'nicolas'=>1,'failure'=>1,'train'=>1,'trains'=>1,'from'=>1,'by'=>1,'due'=>1,'that'=>1,'this'=>1,
		'as'=>1,'be'=>1,'been'=>1,'are'=>1,'it'=>1,'its'=>1,'has'=>1,'had'=>1,'have'=>1,'per'=>1,
		'am'=>1,'pm'=>1,'hrs'=>1,'nb'=>1,'sb'=>1);
	$out=array();
	foreach($tokens as $t){
		if(strlen($t) >= 3 && !isset($stop[$t]) && !ctype_digit($t)) $out[]=$t;
	}
	return $out;
}

function ccsTrainClassifier($rows,$labelMap){
	$m = array('docs'=>array(), 'words'=>array(), 'wtotal'=>array(), 'vocab'=>array(), 'ndocs'=>0);
	foreach($rows as $row){
		if(!isset($labelMap[$row['equipt']])) continue;   // only recorded rows train
		$cat = $labelMap[$row['equipt']];
		$toks = ccsTokenize(isset($row['description']) ? $row['description'] : '');
		if(!count($toks)) continue;
		if(!isset($m['docs'][$cat])){ $m['docs'][$cat]=0; $m['words'][$cat]=array(); $m['wtotal'][$cat]=0; }
		$m['docs'][$cat]++; $m['ndocs']++;
		foreach($toks as $t){
			if(!isset($m['words'][$cat][$t])) $m['words'][$cat][$t]=0;
			$m['words'][$cat][$t]++;
			$m['wtotal'][$cat]++;
			$m['vocab'][$t]=true;
		}
	}
	return $m;
}

function ccsClassifyDescription($m,$desc,$priorDamp=0.25){
	// Need at least two trained categories to discriminate between anything.
	if($m['ndocs'] < 4 || count($m['docs']) < 2) return null;

	$toks = ccsTokenize($desc);
	if(!count($toks)) return null;

	// Confidence gate 1: the description must contain at least two words the
	// model has seen before — otherwise it has no basis to guess.
	$known=0;
	foreach($toks as $t){ if(isset($m['vocab'][$t])) $known++; }
	if($known < 2) return null;

	$V = count($m['vocab']);
	$best=null; $bestS=-INF; $secondS=-INF;
	foreach($m['docs'] as $cat=>$dc){
		// Damped prior: with one dominant equipment type, a full-strength
		// prior would pull nearly every ambiguous description toward it.
		$s = $priorDamp * log($dc / $m['ndocs']);
		foreach($toks as $t){
			$wc = isset($m['words'][$cat][$t]) ? $m['words'][$cat][$t] : 0;
			$s += log(($wc + 1) / ($m['wtotal'][$cat] + $V));   // Laplace-smoothed likelihood
		}
		if($s > $bestS){ $secondS=$bestS; $bestS=$s; $best=$cat; }
		elseif($s > $secondS){ $secondS=$s; }
	}

	// Confidence gate 2: the winner must beat the runner-up by ~2x likelihood
	// (0.69 in log space). Anything closer ABSTAINS and falls through to the
	// unplaced bucket, where the recurring-words chart picks it up.
	if(($bestS - $secondS) < 0.69) return null;

	return $best;
}

?>
<?php require("slide_panel.php"); ?>
</body>
</html>