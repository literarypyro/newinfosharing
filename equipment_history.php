<?php
/* Session before ANY output -- the console header calls session_start(). */
if(session_id()==""){ session_start(); }

/* Whether the console nav renders (see the require further down, in <body>). */
$NAV_SHOW = !(isset($_GET['embed']) && $_GET['embed']!='');
// equipment_history.php — same Line 3 console theme as car_history.php,
// its sibling drill-down page, so the two "further history" views share
// one visual identity instead of two different half-finished looks.
// Only this comment + the wrapping doctype/head + the <style> block
// below change anything about the page; all query/business logic is
// untouched. Also dropped an unused $car_id read from $_GET['car_id']
// that was never referenced anywhere in the file (this page keys off
// $ehEquipt, not car_id -- leftover from wherever this was
// originally copied from).
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");

// Which periods the console actually holds records for. This log can jump
// across a gap with nothing on the page explaining why — a reader would
// reasonably take the silence for "nothing happened" rather than "the records
// are missing". See data_coverage.php.
require_once("data_coverage.php");
/* @insight -- Analysis layer, guarded so a missing file cannot blank the page. */
if(file_exists(dirname(__FILE__)."/iss_insight.php")){
	require_once(dirname(__FILE__)."/iss_insight.php");
	if(file_exists(dirname(__FILE__)."/iss_insight_analytics.php")){
		require_once(dirname(__FILE__)."/iss_insight_analytics.php");
	}
	/* @insight -- Executive/technical toggle. Optional like the rest: without
	   this file the page renders the technical block alone, exactly as before. */
	if(file_exists(dirname(__FILE__)."/iss_insight_audience.php")){
		require_once(dirname(__FILE__)."/iss_insight_audience.php");
	}
}
$coverage = ccsLoadCoverage($db);
$coverageNote = ccsCoverageNote($coverage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Equipment Incident History</title>
<?php
/* @ehfilter -- phFilterCss()/phFilterFields() give this page the same bar the
   history pages use, including the Year-Month vs Date-range toggle and the
   datepickers. Emitted BEFORE history_theme.php: the theme carries the margin
   reset the controls depend on and must come last to beat the Bootstrap rules
   it links. */
if(file_exists(dirname(__FILE__)."/period_filter.php")){
	require_once(dirname(__FILE__)."/period_filter.php");
	echo "<style type='text/css'>"; phFilterCss(); echo "</style>";
}
?>
<?php include("history_theme.php"); ?>

<link href="css/font-awesome.min.css" rel="stylesheet">
	<link href="css/bootstrap.min.css" rel="stylesheet" />
	<link href="css/bootstrap-responsive.min.css" rel="stylesheet" />
	<link href="css/style.min.css" rel="stylesheet" />
	<link href="css/style-responsive.min.css" rel="stylesheet" />
	<link href="css/retina.css" rel="stylesheet" />
	<link rel="stylesheet" type="text/css" href="css/dataTables.tableTools.css">

	<style type='text/css'>

</style>
</head>
	
<?php

/* @carryfilter -- Every request variable on this page was read straight out of
   $_GET unguarded, which warns on PHP 8 for any that is absent, and tested with
   isset(), which is TRUE for a blank "m=" or "level=" and then built
   "level=''" or strtotime("2025--01"). Resolved once, guarded, and cast.

   Three of these are new, and are the reason the filter did not survive the
   jump from equipt_stats.php:
     car_id  -- equipt_stats can be narrowed to one car and was already
                sending it; nothing here read it.
     sd/ed   -- equipt_stats works on a date RANGE when it is opened from
                statistics_report_modified, and a range has no y/m to send.
     m       -- was read for the table but NOT for the charts (see
                $chartDateClause below), so a month filter left the table
                showing March and every graph showing the whole year. */
/* @equipt -- This line read $_GET['equipt'] until a blanket rename of every
   raw $_GET['equipt'] in the file rewrote its own right-hand side, leaving
   "$ehEquipt = isset($ehEquipt) ? (int)$ehEquipt : 0;" -- self-referential, so
   it evaluated to 0 on every request. Every query then asked for equipt='0'
   and the page rendered empty regardless of which equipment or period was
   chosen. Accepts either spelling: equipt= is what equipt_stats.php and
   statistics_report_modified.php send, eq= is used by other links here. */
$ehEquipt = 0;
if(isset($_GET['equipt']) && $_GET['equipt'] !== '')   $ehEquipt = (int)$_GET['equipt'];
else if(isset($_GET['eq']) && $_GET['eq'] !== '')      $ehEquipt = (int)$_GET['eq'];

/* @ambiguous -- A rejected query rendered as an empty table, indistinguishable
   from "nothing matched". ?sqldebug=1 prints MySQL's own message instead. */
$EH_DEBUG = isset($_GET['sqldebug']);
function ehQuery($db,$sql){
	global $EH_DEBUG;
	$rs = $db->query($sql);
	if(!$rs && $EH_DEBUG){
		echo "<pre style='background:#FBE3E3;color:#7A1F1F;padding:8px;font-size:11px;white-space:pre-wrap'>"
		   . htmlspecialchars($db->error) . "\n\n" . htmlspecialchars($sql) . "</pre>";
	}
	return $rs;
}
$ehCar    = isset($_GET['car_id']) && $_GET['car_id'] !== '' ? (int)$_GET['car_id'] : 0;
$ehLevel  = isset($_GET['level'])  && $_GET['level']  !== '' ? (int)$_GET['level']  : 0;
$ehYear   = isset($_GET['y'])      && $_GET['y']      !== '' ? (int)$_GET['y']      : 0;
$ehMonth  = isset($_GET['m'])      && $_GET['m']      !== '' ? (int)$_GET['m']      : 0;
if($ehMonth < 1 || $ehMonth > 12) $ehMonth = 0;
if(!$ehYear) $ehMonth = 0;   /* a month without a year is not a period */

$ehSd = isset($_GET['sd']) && $_GET['sd'] !== '' ? strtotime($_GET['sd']) : false;
$ehEd = isset($_GET['ed']) && $_GET['ed'] !== '' ? strtotime($_GET['ed']) : false;
/* @ehfilter -- mode, mirroring period_filter.php. The bar below keeps BOTH
   sets of period inputs in the DOM and only hides one, so a stale sd/ed would
   otherwise still submit and silently win over the year the user just picked.
   Gating on mode is what makes the toggle mean what it says.
   Inferred when absent, so existing links without &mode= keep working. */
$ehMode = isset($_GET['mode']) ? $_GET['mode'] : '';
if($ehMode !== 'period' && $ehMode !== 'range'){
	$ehMode = (isset($_GET['sd']) && $_GET['sd'] !== '') ? 'range' : 'period';
}
if($ehMode === 'period'){ $ehSd = false; $ehEd = false; }
else                    { $ehYear = 0;   $ehMonth = 0; }

$ehRange = ($ehSd !== false && $ehEd !== false);
if($ehRange && $ehEd < $ehSd){ $t=$ehSd; $ehSd=$ehEd; $ehEd=$t; }   // swap, don't clamp
if($ehRange){ $ehYear = 0; $ehMonth = 0; }                          // one period at a time

$dateClause = "";
$ehPeriodLabel = "All time";
if($ehRange){
	$dateClause    = " and incident_date between '".date("Y-m-d",$ehSd)." 00:00:00' and '".date("Y-m-d",$ehEd)." 23:59:59' ";
	$ehPeriodLabel = date("d M Y",$ehSd)." to ".date("d M Y",$ehEd);
}
else if($ehYear && $ehMonth){
	$dateClause    = " and incident_date like '".sprintf("%04d-%02d",$ehYear,$ehMonth)."-%' ";
	$ehPeriodLabel = date("F Y", strtotime(sprintf("%04d-%02d-01",$ehYear,$ehMonth)));
}
else if($ehYear){
	$dateClause    = " and incident_date like '".$ehYear."-%' ";
	$ehPeriodLabel = "Year ".$ehYear;
}

$levelClause = $ehLevel ? " and level='".$ehLevel."' " : "";

/* Only the queries that join incident_cars can honour a car filter; the two
   that read incident_union alone have no car column. Kept as a separate
   variable so it is obvious which queries it is safe to append to. */
$carClause = $ehCar ? " and incident_cars.car_no*1 = ".$ehCar." " : "";

$initialClause = " where equipt='".$ehEquipt."' ";

?>
<?php

$identify_equipment="select * from equipment where id='".$ehEquipt."' limit 1";
$identify_rs=$db->query($identify_equipment);

$identify_row=$identify_rs->fetch_assoc();

$equipment_name=$identify_row['equipment_name'];

// ---- Two counts, both stated on screen rather than left to be inferred ----
// This page's table is an incident LOG: one row per incident, which is right
// for something you read line by line. The summary and per-car reports count
// incident-CAR pairs — an incident affecting three cars counts three times.
// Neither is wrong, but a reader holding both reports needs to see why the
// numbers differ, so both are shown in the header and the printout.
//
// Both counts use the table's own filter, not the wider chart window.
$ehIncidents = 0;
$ehPairs     = 0;

$cq = $db->query("select count(*) as c from incident_union ".$initialClause." ".$dateClause." ".$levelClause);
if($cq && ($cr = $cq->fetch_assoc())) $ehIncidents = (int)$cr['c'];

$cq = $db->query("select count(*) as c
                  from incident_cars
                  inner join incident_union on incident_cars.incident_id = incident_union.id
                  where incident_union.equipt = '".$ehEquipt."' ".$dateClause." ".$levelClause." ".$carClause);
if($cq && ($cr = $cq->fetch_assoc())) $ehPairs = (int)$cr['c'];

$sql="select * from incident_union ".$initialClause." ".$dateClause." ".$levelClause." order by incident_date desc";




?>
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
<h1 style='font-size:28px; font-weight:bold;'><?php echo $equipment_name; ?> - Equipment History</h1>
	<div class="sub">Combined current &amp; legacy incident records
	<?php
	/* @carryfilter -- inherited filters stated in the heading. Arriving from
	   equipt_stats at a page showing a fraction of the equipment's history,
	   with nothing saying why, reads as missing data. */
	if($ehPeriodLabel !== 'All time'){ echo " &mdash; ".htmlspecialchars($ehPeriodLabel); }
	if($ehCar){   echo " &mdash; Car ".$ehCar." only"; }
	if($ehLevel){ echo " &mdash; Level ".$ehLevel; }
	?>
	&mdash; Line 3</div>
	<div class="sub" style="margin-top:4px;">
		<b><?php echo $ehIncidents; ?></b> incident<?php echo $ehIncidents==1?'':'s'; ?> listed
		&nbsp;&middot;&nbsp;
		<b><?php echo $ehPairs; ?></b> car-level failure<?php echo $ehPairs==1?'':'s'; ?>
		<span style="opacity:.75;">&mdash; one incident can affect several cars, so the summary and per-car reports show the larger figure</span>
	</div>
</div>
<div class="ccs-panel">
<div class="ccs-panel-head">
  <h3>Incident History</h3>
  <div class="ccs-panel-actions">
  <?php
  /* @ehfilter -- Everything here was already READ from the query string --
     statistics_report_modified.php passes equipt, car, level and the date
     range through when it opens the panel -- but the page offered no way to
     change any of it once you arrived.

     Car and Level are this page's own fields, matching what the report
     filters on. The period controls come from period_filter.php so the toggle,
     the datepickers and the styling stay identical to the history pages
     instead of being a fourth copy that drifts.

     $ph is assembled by hand rather than via phResolvePeriod(), because this
     page builds its own qualified date clause (incident_union.incident_date --
     an unqualified column here is ambiguous against the joined tables) and
     that logic is left untouched. */
  $ph = array(
      'mode'    => $ehMode,
      'year'    => $ehYear,
      'month'   => $ehMonth,
      'sd'      => $ehSd,
      'ed'      => $ehEd,
      'isRange' => $ehRange,
      'active'  => ($ehYear || $ehMonth || $ehRange || $ehCar || $ehLevel)
  );

  /* Cars drawn from THIS equipment's own rows: offering a car this equipment
     never failed on is a dead end the user finds by clicking. */
  $ehCars = array();
  $q = $db->query("select distinct incident_cars.car_no*1 as cn
                     from incident_report
                     inner join incident_cars on incident_report.id=incident_cars.incident_id
                    where incident_report.equipt = ".$ehEquipt."
                      and incident_cars.car_no*1 > 0
                    order by cn");
  if($q){ while($r=$q->fetch_assoc()){ $ehCars[] = (int)$r['cn']; } }

  $ehClearUrl = "equipment_history.php?equipt=".(int)$ehEquipt;
  ?>
  <form method="get" action="equipment_history.php" class="ph-filters">
  <input type="hidden" name="equipt" value="<?php echo (int)$ehEquipt; ?>">

  <div class="ph-field">
    <label for="ehCarSel">Car</label>
    <select name="car_id" id="ehCarSel">
      <option value="">All cars</option>
      <?php foreach($ehCars as $cn){ ?>
      <option value="<?php echo $cn; ?>"<?php echo $ehCar==$cn?' selected':''; ?>><?php echo $cn; ?></option>
      <?php } ?>
    </select>
  </div>

  <div class="ph-field">
    <label for="ehLevelSel">Level</label>
    <select name="level" id="ehLevelSel">
      <option value="">All levels</option>
      <?php for($lv=1;$lv<=4;$lv++){ ?>
      <option value="<?php echo $lv; ?>"<?php echo $ehLevel==$lv?' selected':''; ?>><?php echo $lv; ?></option>
      <?php } ?>
    </select>
  </div>

  <?php phFilterFields($ph, $db, $ehClearUrl); ?>
  </form>
  </div>
</div>
<div class="ccs-panel-body">
<table class="table table-striped table-bordered bootstrap-datatable datatable2" width=80% id='add_form' name='add_form' >
	<thead>
	<tr>
	<th>Index No</th>
	<th>Incident Date/Time</th>
	        <th>Time Resolved</th>
        <th>Duration</th>
	<th>Type of Problem</th>
	<th>Incident Number</th>
	
	<th>Description</th>
	</tr>
	</thead>
	<tbody>
<?php

$sql="select * from incident_union ".$initialClause." ".$dateClause." ".$levelClause." order by incident_date desc";

$rs=$db->query($sql);
$nm=$rs->num_rows;

for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
?>	
	<tr>
		<td><?php echo $row['index_no']; ?></td>
		<td><?php echo date("Y-m-d H:iA",strtotime($row['incident_date']));  ?></td>
		        <td><?php 
		if(date("Y-m-d",strtotime($row['resolution_date']))!=="1970-01-01"){		
		echo date("Y-m-d H:iA", strtotime($row['resolution_date'])); 
		}
		else {
			echo "&nbsp;";

		}			
		
		?></td>
        <td><?php echo $row['duration']; ?></td>

		<td><?php echo  getProblemType($db,$row['incident_type']); ?></td>
		<td><a href='#' class='two' onclick='openSlidePanel("edit_ccdr.php?ir=<?php echo  $row['id']; ?>&embed=1","Incident - <?php echo htmlspecialchars($row['incident_no']); ?>")'><?php echo $row['incident_no']; ?></a></td>
		<td><?php echo $row['description']; ?></td>
	</tr>
<?php
}
$initialClause=" where external.incident_defects.equipt_id='".$ehEquipt."'";

$sql="select * from incident_union inner join external.incident_defects on incident_union.id=external.incident_defects.incident_id ".$initialClause." ".$dateClause." ".$levelClause." order by incident_date desc";

//echo "<br>";
//echo $sql;
//echo "<br>";

$rs=$db->query($sql);
$nm=$rs->num_rows;
?>
<?php
for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
?>	
	
	<tr>
		<td><?php echo $row['index_no']; ?></td>
		<td><?php echo date("Y-m-d",strtotime($row['incident_date']));  ?></td>
		<td>&nbsp;</td>
		<td><?php echo isset($row['duration']) ? $row['duration'] : '&nbsp;'; ?></td>
		<td><?php echo  getProblemType($db,$row['incident_type']); ?></td>
		<td><a href='#' class='two' onclick='openSlidePanel("edit_ccdr.php?ir=<?php echo  $row['id']; ?>&embed=1","Incident - <?php echo htmlspecialchars($row['incident_no']); ?>")'><?php echo $row['incident_no']; ?></a></td>
		<td><?php echo $row['description']; ?></td>
	</tr>
<?php

}
?>
</tbody>
</table>
</div>
</div>
</div>

<?php
// ============================================================
// CHART DATA — deliberately a WIDER window than the table.
// The table above honours the exact y/m filter (often one month).
// The charts need more than a month to show cross-car spread and a
// severity trend, so they query the whole YEAR of the selected month
// instead. When no year is selected at all, they fall back to all
// records. This is why chart totals won't match the table's row count
// — the print header says so explicitly.
// ============================================================
/* @carryfilter -- The charts had their own, weaker window: year only. A month
   or a date range narrowed the table and left every graph on the whole year,
   so the page showed two different answers at once. They share one clause now. */
$chartDateClause = $dateClause;

// ---- Severity mix over time: [YYYY-MM][level] => count, across BOTH
// sources (internal incidents + external defects), year-scoped. ----
$monthlyByLevel = array();
$levelSet = array();

function ehGroupSeverity(&$monthlyByLevel,&$levelSet,$rs){
	if(!$rs) return;
	while($row = $rs->fetch_assoc()){
		$lv = (isset($row['lvl']) && $row['lvl']!=='') ? $row['lvl'] : '—';
		$mo = $row['mo'];
		if(!isset($monthlyByLevel[$mo])) $monthlyByLevel[$mo] = array();
		if(!isset($monthlyByLevel[$mo][$lv])) $monthlyByLevel[$mo][$lv] = 0;
		$monthlyByLevel[$mo][$lv] += (int)$row['cnt'];
		$levelSet[$lv] = true;
	}
}

//." ".$levelClause


/* @charts -- Filled after both sources are grouped; see below. */
$sevInternalSql = "select date_format(incident_date,'%Y-%m') as mo, level as lvl, count(*) as cnt
                   from incident_union
                   where equipt='".$ehEquipt."' ".$chartDateClause."
                   group by mo, lvl";
ehGroupSeverity($monthlyByLevel,$levelSet,$db->query($sevInternalSql));

$sevExternalSql = "select date_format(incident_date,'%Y-%m') as mo, level as lvl, count(*) as cnt
                   from incident_union
                   inner join external.incident_defects on incident_union.id=external.incident_defects.incident_id
                   where external.incident_defects.equipt_id='".$ehEquipt."' ".$chartDateClause."
                   group by mo, lvl";
ehGroupSeverity($monthlyByLevel,$levelSet,$db->query($sevExternalSql));

ksort($levelSet);
/* @charts -- A month with no incidents had no key, so the axis closed the gap
   and drew February next to June as though they were consecutive. Unlike the
   tables elsewhere, an axis has no row labels to make the jump visible, which
   is why filling belongs here. A filled month is an empty level map, so every
   stacked bar sums to zero and simply has no height.

   The span follows the filter, not just the data: a year filter fills all
   twelve months even if only three had incidents. */
if(count($monthlyByLevel) || $ehYear || $ehRange){
	$mk = array_keys($monthlyByLevel); sort($mk);
	if($ehRange){        $ffrom = date("Y-m",$ehSd);              $fto = date("Y-m",$ehEd); }
	else if($ehYear && $ehMonth){ $ffrom = $fto = sprintf("%04d-%02d",$ehYear,$ehMonth); }
	else if($ehYear){    $ffrom = sprintf("%04d-01",$ehYear);     $fto = sprintf("%04d-12",$ehYear); }
	else if(count($mk)){ $ffrom = $mk[0];                          $fto = $mk[count($mk)-1]; }
	else {               $ffrom = $fto = ''; }

	if($ffrom !== ''){
		$cur = new DateTime($ffrom."-01");
		$end = new DateTime($fto."-01");
		$seq = array();
		while($cur <= $end){
			$k = $cur->format("Y-m");
			$seq[$k] = isset($monthlyByLevel[$k]) ? $monthlyByLevel[$k] : array();
			$cur->modify("+1 month");
		}
		// Anything outside the filtered span that still holds rows is kept:
		// losing data to a display rule would be the worse bug.
		foreach($monthlyByLevel as $k=>$v){ if(!isset($seq[$k])) $seq[$k]=$v; }
		ksort($seq);
		$monthlyByLevel = $seq;
	}
}

$ehLevels = array_keys($levelSet);

// ---- Incidents by car: [car_no] => count, year-scoped. Needs the
// incident_cars join (incident_union alone has no car field). NOTE: an
// incident can involve several cars, so this counts incident-car pairs,
// not distinct incidents. External defects excluded (no car mapping). ----
$carRows = array();
$carSql = "select incident_cars.car_no as car_no, count(*) as cnt
           from incident_cars
           inner join incident_union on incident_cars.incident_id = incident_union.id
           where incident_union.equipt = '".$ehEquipt."' ".$chartDateClause."
                 ".$levelClause." ".$carClause."
           group by incident_cars.car_no
           order by cnt desc";
/* @insight -- $levelClause added. The severity queries above leave it out on
   purpose (a severity breakdown narrowed to one severity is a single bar), but
   this chart had no such reason: with Level 3 selected the header counted
   level 3 pairs while the bars underneath counted every level. Revert this one
   clause if the unfiltered spread was deliberate. */
$carRs = $db->query($carSql);
if($carRs){ while($cr = $carRs->fetch_assoc()){ $carRows[] = array($cr['car_no'], (int)$cr['cnt']); } }
?>

<!-- Print-only chart summary. Hidden on screen; the two canvases are
     flattened to images and injected into the TableTools print window.
     Landscape, and the by-car canvas is tall enough to space its bars. -->
<?php
/* ==========================================================================
   @insight -- Analysis block for the drill-down layer.

   Scope follows the page: $dateClause is empty until a filter is applied, so
   the analysis runs on everything this equipment has on record and narrows as
   the reader narrows. The one deliberate exception is the seasonal baseline,
   which drops the date clause on purpose -- judging March against other
   Marches is impossible from inside a one-year window.

   Counting basis is car-level failures ($ehPairs), matching
   statistics_report_modified.php, so the two pages can be read side by side.
   External defects carry no car mapping and are therefore outside every
   figure here -- $ehIncidents, which does include them, is the larger number
   shown in the header.
   ========================================================================== */
$issOut2 = null;
if(function_exists('iss_insight') && $ehEquipt > 0){

	/* -- (a) car x month, the breakdown this page is really about ---------- */
	$sql = "select incident_cars.car_no*1 as cn,
	               date_format(incident_date,'%Y-%m') as mo, count(*) as c
	          from incident_cars
	          inner join incident_union on incident_cars.incident_id = incident_union.id
	         where incident_union.equipt = '".$ehEquipt."' ".$dateClause."
	               ".$levelClause." ".$carClause."
	         group by cn, mo";
	$rs = $db->query($sql);
	$cell = array(); $moSeen = array(); $carTot = array(); $issGrand = 0;
	if($rs){
		while($r = $rs->fetch_assoc()){
			$cn = (int)$r['cn']; if($cn <= 0) continue;
			$cell[$cn][$r['mo']] = (int)$r['c'];
			$moSeen[$r['mo']] = true;
			$carTot[$cn] = (isset($carTot[$cn]) ? $carTot[$cn] : 0) + (int)$r['c'];
			$issGrand += (int)$r['c'];
		}
	}
	$months = array_keys($moSeen); sort($months);

	if(count($months) >= 3 && count($cell) >= 1 && $issGrand >= 8){

		/* Gap months are handed over as their own list. Left as zeros they
		   would drag every car's baseline down and turn ordinary months into
		   spikes -- the same trap the coverage table exists to prevent. */
		$issBuckets = array(); $issUncov = array();
		foreach($months as $mo){
			$lbl = date('F Y', strtotime($mo.'-01'));
			$issBuckets[] = $lbl;
			if(ccsMonthStatus($coverage, $mo) === 'missing') $issUncov[] = $lbl;
		}

		$issRows = array(); $carNos = array_keys($cell); sort($carNos, SORT_NUMERIC);
		foreach($carNos as $cn){
			$vals = array();
			foreach($months as $mo){ $vals[] = isset($cell[$cn][$mo]) ? $cell[$cn][$mo] : 0; }
			$issRows[] = array('key'=>(string)$cn, 'label'=>'Car '.$cn,
			                   'values'=>$vals, 'total'=>(int)$carTot[$cn]);
		}
		$issByBucket = array();
		foreach($months as $mi => $mo){
			$t = 0; foreach($carNos as $cn){ $t += isset($cell[$cn][$mo]) ? $cell[$cn][$mo] : 0; }
			$issByBucket[] = $t;
		}

		/* -- (b) car x severity. The page charts severity over time and cars
		      over time, but never crosses them -- so "one car produces most of
		      the serious ones" is invisible on this screen. */
		$issCross2 = array();
		$sql = "select incident_cars.car_no*1 as cn, incident_union.level as lv, count(*) as c
		          from incident_cars
		          inner join incident_union on incident_cars.incident_id = incident_union.id
		         where incident_union.equipt = '".$ehEquipt."' ".$dateClause." ".$carClause."
		           and incident_union.level is not null and incident_union.level <> ''
		         group by cn, lv";
		$rs = $db->query($sql);
		$lvCell = array(); $lvSeen = array(); $lvGrand = 0;
		if($rs){
			while($r = $rs->fetch_assoc()){
				$cn = (int)$r['cn']; if($cn <= 0) continue;
				$lvCell[$cn][$r['lv']] = (int)$r['c'];
				$lvSeen[$r['lv']] = true; $lvGrand += (int)$r['c'];
			}
		}
		$lvList = array_keys($lvSeen); sort($lvList);
		if(count($lvList) >= 2 && count($lvCell) >= 2){
			$m = array(); $rl = array();
			foreach(array_keys($lvCell) as $cn){ $rl[] = 'Car '.$cn; }
			foreach(array_keys($lvCell) as $cn){
				$rv = array();
				foreach($lvList as $lv){ $rv[] = isset($lvCell[$cn][$lv]) ? $lvCell[$cn][$lv] : 0; }
				$m[] = $rv;
			}
			$lvLabels = array();
			foreach($lvList as $lv){ $lvLabels[] = 'Level '.$lv; }
			$issCross2 = array('row_label'=>'Car', 'col_label'=>'Severity',
			                   'rows'=>$rl, 'cols'=>$lvLabels, 'matrix'=>$m,
			                   'expect_grand'=>$lvGrand);
		}

		/* -- (c) seasonal baseline: date clause deliberately dropped -------- */
		$issHist2 = array();
		$sql = "select date_format(incident_date,'%Y-%m') as mo, count(*) as c
		          from incident_cars
		          inner join incident_union on incident_cars.incident_id = incident_union.id
		         where incident_union.equipt = '".$ehEquipt."' ".$levelClause." ".$carClause."
		         group by mo order by mo";
		$rs = $db->query($sql);
		$hb = array(); $hv = array();
		if($rs){
			while($r = $rs->fetch_assoc()){
				if(ccsMonthStatus($coverage, $r['mo']) === 'missing') continue;
				$hb[] = $r['mo']; $hv[] = (int)$r['c'];
			}
		}
		if(count($hb) >= 24) $issHist2 = array('buckets'=>$hb, 'values'=>$hv);

		/* -- (d) incident-level rows: did the repair hold? ------------------
		   The most useful question on a per-equipment page, and the one the
		   incident list cannot answer by eye. */
		$issEv2 = array();
		$sql = "select incident_date as d, incident_cars.car_no*1 as cn
		          from incident_cars
		          inner join incident_union on incident_cars.incident_id = incident_union.id
		         where incident_union.equipt = '".$ehEquipt."' ".$dateClause."
		               ".$levelClause." ".$carClause."
		         order by incident_date limit 6000";
		$rs = $db->query($sql);
		if($rs){
			while($r = $rs->fetch_assoc()){
				$cn = (int)$r['cn']; if($cn <= 0) continue;
				$issEv2[] = array('date'=>substr($r['d'],0,10),
				                  'unit_key'=>'car'.$cn, 'unit_label'=>'Car '.$cn,
				                  'fault_key'=>'eq'.$ehEquipt, 'fault_label'=>$equipment_name);
			}
		}

		/* -- (e) severity over time, reshaped from what the charts already use */
		$issSev2 = null;
		if(count($monthlyByLevel)){
			$series = array();
			foreach(array_keys($levelSet) as $lv){
				$row = array();
				foreach($months as $mo){
					$row[] = isset($monthlyByLevel[$mo][$lv]) ? (int)$monthlyByLevel[$mo][$lv] : 0;
				}
				$series['Level '.$lv] = $row;
			}
			if(count($series)) $issSev2 = array('buckets'=>$issBuckets, 'series'=>$series);
		}

		$issFil2 = array();
		if($ehLevel) $issFil2['level'] = $ehLevel;
		if($ehCar)   $issFil2['car']   = 'Car '.$ehCar;

		$issCtx2 = array(
			'schema' => 'iss.report.v1',
			'report' => array('id'=>'equipment_history',
			                  'title'=>$equipment_name.' - failures by car',
			                  'unit'=>'car-level failures'),
			'period' => array('from'=>(count($months) ? $months[0].'-01' : ''),
			                  'to'  =>(count($months) ? date('Y-m-t', strtotime(end($months).'-01')) : ''),
			                  'grain'=>'month'),
			'filters' => $issFil2,
			'dimensions' => array('row'=>array('key'=>'car','label'=>'Car'),
			                      'col'=>array('key'=>'month','label'=>'Month')),
			'buckets' => $issBuckets,
			'rows'    => $issRows,
			'coverage'=> array('uncovered_buckets'=>$issUncov),
			'totals'  => array('by_bucket'=>$issByBucket, 'grand'=>$issGrand),
		);
		if(count($issCross2)) $issCtx2['crosstab']   = $issCross2;
		if(count($issHist2))  $issCtx2['history']    = $issHist2;
		if(count($issEv2))    $issCtx2['events']     = $issEv2;
		if($issSev2 !== null) $issCtx2['breakdowns'] = array('severity'=>$issSev2);

		$issN2 = iss_insight_normalize($issCtx2);
		$issF2 = iss_insight_findings($issN2);
		if(function_exists('iss_insight_findings_advanced')){
			$issF2 = iss_insight_findings_advanced($issCtx2, $issN2, $issF2);
		}
		$issOut2 = iss_insight_render_offline($issN2, $issF2);

		echo iss_insight_css();
		/* @insight -- Both readings are computed here and shipped together,
		   so switching is instant and works with no provider configured. The
		   reader's choice persists via localStorage; the print stylesheet
		   prints whichever is on screen. */
		$issRendered = $issOut2;
		if(function_exists('iss_insight_html_dual')){
			/* Each helper is guarded on ITS OWN name, not on a sibling's.
			   These live in one file that gets copied station by station with
			   no version control, so a box can easily end up with a page that
			   is newer than its iss_insight_audience.php. Guarding the whole
			   group on iss_insight_html_dual() meant an older helper file threw
			   'Call to undefined function' and killed the page mid-render --
			   taking every chart, sort and print script below it with it. */
			if(function_exists('iss_insight_audience_css')) echo iss_insight_audience_css();
			if(function_exists('iss_insight_audience_js'))  echo iss_insight_audience_js();
			echo '<div id="issInsight2">'
			   . iss_insight_html_dual($issN2, $issF2, $issRendered)
			   . '</div>';
		} else {
			echo '<div id="issInsight2">'.iss_insight_html($issRendered).'</div>';
		}

		$issCfg2 = iss_insight_config();
		if(!empty($issCfg2['enabled']) && $issCfg2['provider'] !== 'none'
		   && file_exists(dirname(__FILE__)."/insight_ajax.php")){
			$issKey2 = iss_insight_stash($issCtx2);
			echo '<script>(function(){var b=document.getElementById("issInsight2");'
			   . 'if(!b||!window.XMLHttpRequest)return;var x=new XMLHttpRequest();'
			   . 'x.open("GET","insight_ajax.php?k='.$issKey2.'",true);'
			   . 'x.onreadystatechange=function(){if(x.readyState===4&&x.status===200'
			   . '&&x.responseText&&x.responseText.indexOf("ins-block")!==-1){'
			   . 'b.innerHTML=x.responseText;if(window.issInsightApplyView)window.issInsightApplyView();}};x.send();})();</script>';
		}
	}
}
?>

<div id="ccs-print-charts" style="display:none;">
	<canvas id="ehCars"  width="440" height="230"></canvas>
	<canvas id="ehTrend" width="440" height="150"></canvas>
</div>

<script>
var ccsCoverageNote = <?php echo json_encode(htmlspecialchars($coverageNote, ENT_QUOTES)); ?>;
// Raw aggregates from the same query/filter as the table above.
var ehMonthlyLevel = <?php echo json_encode($monthlyByLevel, JSON_FORCE_OBJECT); ?>;
var ehLevels = <?php echo json_encode(array_values($ehLevels)); ?>;
var ehCarRows = <?php echo json_encode($carRows); ?>;
var ehEquipmentName = <?php echo json_encode($equipment_name); ?>;
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
     Print-with-charts (B by-car + D severity trend + C source split).
     Attached last, after custom.min.js / additional.js — those two
     auto-init .datatable2 and its TableTools print button, so the
     button only exists to hook into once they've run.
     ============================================================ -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
$(function(){

	var textInk  = getComputedStyle(document.documentElement).getPropertyValue('--text-primary').trim() || '#111';
	var mutedInk = getComputedStyle(document.documentElement).getPropertyValue('--text-secondary').trim() || '#555';
	var gridInk  = 'rgba(137,135,129,0.20)';
	var sevPalette = ['#1baf7a','#eda100','#e34948','#a32d2d','#5f5e5a']; // low->high severity, gray for overflow
	var carColor = '#2a78d6';

	// ============ Chart: incidents by car (horizontal, top N) ============
	// Only the top N cars get bars, generously spaced (categoryPercentage
	// leaves whitespace between them). The remaining cars aren't drawn as a
	// competing "Other" bar — they're summarised in a footnote painted into
	// the canvas, so the tail is acknowledged without crowding the leaders.
	var TOP_CARS = 5; // set to 3 for top 3

	var carValueLabels = {
		id: 'carValueLabels',
		afterDatasetsDraw: function(chart){
			var ctx = chart.ctx, meta = chart.getDatasetMeta(0);
			ctx.save(); ctx.font = '11px Arial, sans-serif'; ctx.fillStyle = textInk;
			ctx.textBaseline = 'middle'; ctx.textAlign = 'left';
			meta.data.forEach(function(bar,i){ ctx.fillText(chart.data.datasets[0].data[i], bar.x + 6, bar.y); });
			ctx.restore();
		}
	};

	(function drawCars(){
		var sorted = ehCarRows.slice().sort(function(a,b){ return b[1]-a[1]; });
		var topCars = sorted.slice(0, TOP_CARS);
		var tailCars = sorted.slice(TOP_CARS);
		var tailIncidents = tailCars.reduce(function(s,r){ return s+r[1]; }, 0);

		var carFootnote = {
			id: 'carFootnote',
			afterDraw: function(chart){
				if(!tailCars.length) return;
				var ctx = chart.ctx, area = chart.chartArea;
				ctx.save();
				ctx.font = '10px Arial, sans-serif'; ctx.fillStyle = mutedInk;
				ctx.textAlign = 'left'; ctx.textBaseline = 'top';
				var y = chart.height - 16;
				ctx.strokeStyle = gridInk; ctx.lineWidth = 1;
				ctx.beginPath(); ctx.moveTo(area.left, y - 6); ctx.lineTo(chart.width - 8, y - 6); ctx.stroke();
				ctx.fillText('+ ' + tailIncidents + ' more across ' + tailCars.length + ' other car' + (tailCars.length === 1 ? '' : 's'), area.left, y);
				ctx.restore();
			}
		};

		new Chart(document.getElementById('ehCars'), {
			type: 'bar',
			data: {
				labels: topCars.map(function(r){ return 'Car ' + r[0]; }),
				datasets: [{ data: topCars.map(function(r){ return r[1]; }), backgroundColor: carColor, borderRadius: 3, categoryPercentage: 0.55, barPercentage: 0.9 }]
			},
			options: {
				indexAxis: 'y', responsive: false, animation: false,
				layout: { padding: { right: 22, bottom: tailCars.length ? 26 : 4 } },
				plugins: {
					title: { display: true, text: 'Car-level failures by car — top ' + TOP_CARS, color: textInk, font: { size: 11, weight: 'normal' }, padding: { bottom: 10 } },
					legend: { display: false },
					tooltip: { callbacks: { label: function(c){ return c.parsed.x + ' incidents'; } } }
				},
				scales: {
					x: { ticks: { color: mutedInk, precision: 0, font: { size: 10 } }, grid: { color: gridInk } },
					y: { ticks: { color: textInk, font: { size: 12 } }, grid: { display: false } }
				}
			},
			plugins: [carValueLabels, carFootnote]
		});
	})();

	// ============ Chart: severity mix over time (stacked) ============
	/* @charts -- The keys are "YYYY-MM" because that string sorts correctly;
	   they were never meant to reach the axis, but they were being used as the
	   labels verbatim. Two lines rather than "Mar 2025" on one, so twelve fit
	   a 440px canvas without rotating. */
	var EH_MON = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
	function ehMonthLabel(k){
		var t = String(k);
		if(!/^\d{4}-\d{2}$/.test(t)) return t;
		var mi = parseInt(t.slice(5,7), 10) - 1;
		if(!(mi >= 0 && mi < 12)) return t;
		return [EH_MON[mi], t.slice(0,4)];
	}

	(function drawTrend(){
		var months = Object.keys(ehMonthlyLevel).sort();
		var datasets = ehLevels.map(function(lv, idx){
			return {
				label: String(lv),
				data: months.map(function(m){ return (ehMonthlyLevel[m] && ehMonthlyLevel[m][lv]) || 0; }),
				backgroundColor: sevPalette[Math.min(idx, sevPalette.length-1)]
			};
		});

		new Chart(document.getElementById('ehTrend'), {
			type: 'bar',
			data: { labels: months.map(ehMonthLabel), datasets: datasets },
			options: {
				responsive: false, animation: false,
				plugins: {
					title: { display: true, text: 'Severity mix over time', color: textInk, font: { size: 11, weight: 'normal' }, padding: { bottom: 6 } },
					legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 }, color: mutedInk } }
				},
				scales: {
					x: { stacked: true, ticks: { color: mutedInk, font: { size: 10 } }, grid: { display: false } },
					y: { stacked: true, ticks: { color: mutedInk, precision: 0, font: { size: 10 } }, grid: { color: gridInk } }
				}
			}
		});
	})();

	// ============ Intercept the TableTools print button ============
	var printBtn = $('#add_form_wrapper').find('.DTTT_button_print, .buttons-print');
	if(printBtn.length){
		printBtn.off('click').on('click', function(e){
			e.preventDefault();
			e.stopImmediatePropagation();
			ehPrintWithCharts();
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
	function ehFullTableHtml(){
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

	function ehPrintWithCharts(){
		var imgCars   = document.getElementById('ehCars').toDataURL('image/png');
		var imgTrend  = document.getElementById('ehTrend').toDataURL('image/png');
		var captured  = ehFullTableHtml();
		var tableHtml = captured.html;
		var rowCount  = captured.count;

		var win = window.open('', '_blank');
		win.document.write(
			'<html><head><title><?php echo htmlspecialchars($equipment_name); ?> — Equipment Incident History</title>' +
			'<style>' +
				// --- page setup -------------------------------------------
				'@page{ size:A4 portrait; margin:14mm 12mm 15mm; }' +
				'*{ box-sizing:border-box; }' +
				'body{ font-family:"Segoe UI",Arial,Helvetica,sans-serif; color:#1a1a1a; margin:0;' +
					' font-size:11px; line-height:1.45;' +
					' -webkit-print-color-adjust:exact; print-color-adjust:exact; }' +

				// --- masthead ---------------------------------------------
				'.rpt-head{ border-bottom:2px solid #1f4e79; padding-bottom:9px; margin-bottom:4px; }' +
				'.rpt-org{ font-size:8.5px; letter-spacing:.15em; text-transform:uppercase;' +
					' color:#6b7280; margin-bottom:3px; }' +
				'.rpt-title{ font-size:19px; font-weight:600; color:#1f4e79; margin:0 0 1px; }' +
				'.rpt-subject{ font-size:12.5px; color:#374151; margin:0; }' +
				'.rpt-meta{ margin:8px 0 0; font-size:9.5px; color:#4b5563; }' +
				'.rpt-meta span{ margin-right:20px; white-space:nowrap; }' +
				'.rpt-meta b{ color:#1f2937; font-weight:600; }' +

				// --- section headings -------------------------------------
				'h2.sec{ font-size:11px; text-transform:uppercase; letter-spacing:.09em;' +
					' color:#1f4e79; border-bottom:1px solid #d1d5db; padding-bottom:4px;' +
					' margin:20px 0 10px; font-weight:600; }' +

				// --- charts -----------------------------------------------
				/* @printcharts -- Two faults, one cause.
				   The images had no width rule, so each printed at its natural 440px
				   and the two inline-blocks could not sit side by side: they wrapped
				   to two rows and the pair grew taller than the space left on the
				   page. page-break-inside:avoid was on the CONTAINER, so the whole
				   block was then pushed to a fresh page -- that is the blank gap --
				   and once there it was taller than one page, so the second chart
				   was clipped with no page to overflow into.
				   Fixed by sizing them to the page and moving the break rule down to
				   the individual chart, which is small enough to always honour it. */
				'.charts{ font-size:0; }' +
				'.chart{ display:inline-block; vertical-align:top; width:48%; margin:0 2% 12px 0;' +
					' page-break-inside:avoid; break-inside:avoid; font-size:9px; }' +
				'.chart img{ display:block; width:100%; height:auto; max-height:70mm;' +
					' object-fit:contain; border:1px solid #e5e7eb; }' +
				'.chart .cap{ font-size:9px; color:#6b7280; margin-top:3px; }' +
				'.note{ font-size:9px; color:#6b7280; font-style:italic; margin:2px 0 0; }' +

				// --- table ------------------------------------------------
				'.tbl-head{ margin-bottom:6px; }' +
				'.tbl-head h3{ font-size:13px; font-weight:600; margin:0; display:inline-block; }' +
				'.tbl-head .count{ font-size:9.5px; color:#6b7280; margin-left:8px; }' +
				/* @print -- The crop. With auto layout the widest cell decides the
				   column, and Description holds free text with no guaranteed
				   break points: one long entry pushed the table past the page
				   box and everything right of it was clipped, because print has
				   no horizontal scroll to fall back on. */
				'table{ width:100%; max-width:100%; table-layout:fixed;' +
					' border-collapse:collapse; font-size:9.5px; }' +
				'th, td{ overflow-wrap:anywhere; word-break:break-word; }' +
				'thead{ display:table-header-group; }' +   // repeat header on every page
				'th{ background:#1f4e79; color:#fff; text-align:left; padding:6px 7px;' +
					' font-size:9px; font-weight:600; text-transform:uppercase;' +
					' letter-spacing:.04em; border:1px solid #1f4e79; }' +
				'td{ padding:5px 7px; border:1px solid #e5e7eb; vertical-align:top; }' +
				'tbody tr:nth-child(even) td{ background:#f6f8fa; }' +
				'tr{ page-break-inside:avoid; }' +
				'a{ color:inherit; text-decoration:none; pointer-events:none; }' +

				// --- footer -----------------------------------------------
				'.rpt-foot{ margin-top:14px; border-top:1px solid #d1d5db; padding-top:6px;' +
					' font-size:8.5px; color:#6b7280; }' +
			'</style></head><body>' +

			'<div class="rpt-head">' +
				'<div class="rpt-org">DOTr &middot; MRT-3 Line 3 &middot; Operations Control</div>' +
				'<h1 class="rpt-title">Equipment Incident History</h1>' +
				'<p class="rpt-subject"><?php echo htmlspecialchars($equipment_name); ?></p>' +
			'</div>' +
			'<div class="rpt-meta">' +
				'<span><b>Report period:</b> <?php echo isset($_GET["y"]) ? htmlspecialchars($_GET["y"]).(isset($_GET["m"]) ? "-".str_pad(date("m",strtotime($_GET["y"]."-".$_GET["m"]."-01")),2,"0",STR_PAD_LEFT) : "") : "All records"; ?></span>' +
				<?php /* @levelfilter -- was isset($_GET["level"]), which is TRUE for a
				         blank level= and printed "Severity: Level ". $ehLevel is
				         the already-resolved value, so the printout and the page
				         cannot disagree. Car added: it was filtered but unstated. */ ?>
				'<?php if($ehLevel){ ?><span><b>Severity:</b> Level <?php echo (int)$ehLevel; ?> only</span><?php } ?>' +
				'<?php if($ehCar){ ?><span><b>Car:</b> <?php echo (int)$ehCar; ?> only</span><?php } ?>' +
				'<span><b>Incidents listed:</b> ' + rowCount + '</span>' +
				'<span><b>Car-level failures:</b> <?php echo (int)$ehPairs; ?></span>' +
				'<span><b>Generated:</b> <?php echo date("d M Y, H:i"); ?></span>' +
			'</div>' +

			'<h2 class="sec">Summary</h2>' +
			'<div class="charts">' +
				'<div class="chart"><img src="' + imgCars + '">' +
					'<div class="cap">Figure 1 &mdash; Car-level failures by car (counts each affected car)</div></div>' +
				'<div class="chart"><img src="' + imgTrend + '">' +
					'<div class="cap">Figure 2 &mdash; Severity mix over time</div></div>' +
				'<p class="note">This log lists one row per incident. The summary and per-car reports count incident-car failures &mdash; an incident affecting several cars counts once against each &mdash; which is why their totals are larger. Both figures for this view are given above.</p>' +
				/* @carryfilter -- This read $chartYear, which no longer exists: an
				   undefined variable raises a PHP warning, and with display_errors
				   on that warning HTML lands INSIDE this JS string and breaks the
				   whole block. The claim was also out of date -- the charts used
				   to run on a wider window than the table, and now share one. */
				'<p class="note">Charts and table cover the same window: <?php echo htmlspecialchars($ehPeriodLabel); ?><?php echo $ehCar ? ", car ".$ehCar." only" : ""; ?><?php echo $ehLevel ? ", level ".$ehLevel : ""; ?>.</p>' +
			'</div>' +

			'<h2 class="sec">Incident Records</h2>' +
			'<div class="tbl-head">' +
				'<h3><?php echo htmlspecialchars($equipment_name); ?> &mdash; incident log</h3>' +
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
<?php require("slide_panel.php"); ?>
<?php if(function_exists('phDatepickerJs')) phDatepickerJs(); /* @ehfilter */ ?>
</body>
</html>
<?php
function getProblemType($db,$type){
	$sql="select * from equipment_type where equipment_code='".$type."'";
	$rs=$db->query($sql);

	$nm=$rs->num_rows;
	$row=$rs->fetch_assoc();

	$problem=$row['equipment_name'];
	return $problem;
}
?>