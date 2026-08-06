<?php
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
$ccsYear  = isset($_GET['y']) && $_GET['y'] !== '' ? (int)$_GET['y'] : 0;
$ccsMonth = isset($_GET['m']) && $_GET['m'] !== '' ? (int)$_GET['m'] : 0;
if($ccsMonth < 1 || $ccsMonth > 12){ $ccsMonth = 0; }

$dateClause  = "";
$dateClause2 = "";
if($ccsYear && $ccsMonth){
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

<?php include("history_theme.php"); ?>
</head>
<body>
<div class="ccs-page">

<div class="ccs-header">
	<h1>Car #<?php echo htmlspecialchars($car_id); ?> &mdash; Incident History</h1>
	<div class="sub">Combined current &amp; legacy incident records &mdash; Line 3</div>
</div>

<div class="ccs-panel">
<div class="ccs-panel-head"><h3>Incident History</h3></div>
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
$sql="select * from incident_cars inner join incident_union on incident_cars.incident_id=incident_union.id where incident_cars.car_no*1='".$car_id."' ".$dateClause." order by incident_date desc";
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
$suggestedCounts=array(); // [ equipment => how many were auto-suggested ]
$suggestedTotal=0;
$blankTerms=array();      // [ token => number of UNPLACED incidents mentioning it ]
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
			// Document frequency, counted once per incident, over the rows
			// the classifier could not place — that is this chart's whole point.
			foreach(array_unique(ccsTokenize($row['description'])) as $t){
				if(!isset($blankTerms[$t])) $blankTerms[$t]=0;
				$blankTerms[$t]++;
			}
		}
	}

	$monthKey=date("Ym", strtotime($row['incident_date']));
	if(!isset($monthlyCounts[$monthKey])) $monthlyCounts[$monthKey]=array();
	if(!isset($monthlyCounts[$monthKey][$problemType])) $monthlyCounts[$monthKey][$problemType]=0;
	$monthlyCounts[$monthKey][$problemType]++;

	if(!isset($problemCounts[$problemType])) $problemCounts[$problemType]=0;
	$problemCounts[$problemType]++;

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

// Rank recurring terms among the rows the classifier could NOT place; keep
// the top 8 that turn up in 2+ of them.
arsort($blankTerms);
$topBlankTerms=array();
foreach($blankTerms as $t=>$c){
	if($c < 2) break;                  // sorted desc — everything after is rarer
	$topBlankTerms[]=array($t,(int)$c);
	if(count($topBlankTerms) >= 8) break;
}
?>

<!-- Print-only chart summary. Hidden on screen (the live table above is
     the on-screen view); rendered to static images and injected into the
     TableTools print window — see the script block near the bottom. -->
<div id="ccs-print-charts" style="display:none;">
	<canvas id="ccsChartMonthly" width="340" height="230"></canvas>
	<canvas id="ccsChartPareto"  width="340" height="200"></canvas>
	<canvas id="ccsSeverity"     width="340" height="220"></canvas>
	<canvas id="ccsBlankTerms"   width="340" height="200"></canvas>
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
var ccsSevCols       = <?php echo json_encode(array_values($sevOrder)); ?>;
var ccsBlankTerms    = <?php echo json_encode($topBlankTerms); ?>;
var ccsBlankTotal    = <?php echo (int)$blankTotal; ?>;
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

	var rankedTypes = Object.keys(ccsProblemCounts)
		.filter(function(t){ return t !== BLANK_KEY; })
		.sort(function(a,b){ return ccsProblemCounts[b]-ccsProblemCounts[a]; });
	var topTypes = rankedTypes.slice(0, TOP_N);
	var tailTypes = rankedTypes.slice(TOP_N);

	var totalIncidents = rankedTypes.reduce(function(s,t){ return s+ccsProblemCounts[t]; }, 0) + blankCount;
	var tailTotal = tailTypes.reduce(function(s,t){ return s+ccsProblemCounts[t]; }, 0);

	// ---- Chart 1: monthly stacked trend, top 3 + Others (blank folded
	// into Others here — the month trend is about volume over time, so a
	// separate blank series would just add noise). ----
	function trendBucket(type){
		if(type === BLANK_KEY) return 'Others';
		return topTypes.indexOf(type) !== -1 ? type : 'Others';
	}
	var trendCategories = topTypes.concat((tailTypes.length || blankCount) ? ['Others'] : []);
	var monthlyBucketed = {};
	months.forEach(function(m){
		monthlyBucketed[m] = {};
		trendCategories.forEach(function(c){ monthlyBucketed[m][c] = 0; });
		Object.keys(ccsMonthlyCounts[m]).forEach(function(type){
			monthlyBucketed[m][trendBucket(type)] += ccsMonthlyCounts[m][type];
		});
	});
	var monthlyDatasets = trendCategories.map(function(cat, idx){
		return {
			label: cat === 'Others' ? 'Others' : cat,
			data: months.map(function(m){ return monthlyBucketed[m][cat]; }),
			backgroundColor: cat === 'Others' ? othersColor : palette[idx % palette.length]
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
			if(ccsSuggestedTotal > 0){
				lines.push('Lighter segments: ' + ccsSuggestedTotal + ' auto-suggested from descriptions');
			}
			if(blankCount > 0){
				lines.push('+ ' + blankCount + ' still unplaced (see next figure)');
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
		ctx.fillText('Equipment \u00d7 severity', 0, 9);

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
	// on — too little evidence, or two categories too close to call. Their
	// descriptions are mined for recurring words, so the residue is described
	// rather than guessed at, and whoever tidies the records can see what it
	// is made of.
	if(ccsBlankTerms.length){
		new Chart(document.getElementById('ccsBlankTerms'), {
			type: 'bar',
			data: {
				labels: ccsBlankTerms.map(function(p){ return p[0]; }),
				datasets: [{ data: ccsBlankTerms.map(function(p){ return p[1]; }), backgroundColor: '#1baf7a', borderRadius: 3, categoryPercentage: 0.6, barPercentage: 0.9 }]
			},
			options: {
				indexAxis: 'y', responsive: false, animation: false, layout: { padding: { right: 22 } },
				plugins: {
					title: { display: true, text: 'Unplaced after auto-suggestion (' + ccsBlankTotal + ') \u2014 recurring words', color: textInk, font: { size: 11, weight: 'normal' }, padding: { bottom: 8 } },
					legend: { display: false },
					tooltip: { callbacks: { label: function(c){ return 'appears in ' + c.parsed.x + ' unplaced incidents'; } } }
				},
				scales: {
					x: { ticks: { color: mutedInk, precision: 0, font: { size: 10 } }, grid: { color: gridInk } },
					y: { ticks: { color: textInk, font: { size: 11 } }, grid: { display: false } }
				}
			},
			plugins: [{
				id: 'blankTermLabels',
				afterDatasetsDraw: function(chart){
					var ctx = chart.ctx, meta = chart.getDatasetMeta(0);
					ctx.save(); ctx.font = '11px Arial, sans-serif'; ctx.fillStyle = textInk;
					ctx.textBaseline = 'middle'; ctx.textAlign = 'left';
					meta.data.forEach(function(bar,i){ ctx.fillText(chart.data.datasets[0].data[i], bar.x + 6, bar.y); });
					ctx.restore();
				}
			}]
		});
	}
	else{
		var cvB = document.getElementById('ccsBlankTerms');
		var cB = cvB.getContext('2d');
		cB.clearRect(0,0,cvB.width,cvB.height);
		cB.textBaseline='middle'; cB.textAlign='left';
		cB.font='11px Arial, sans-serif'; cB.fillStyle=textInk;
		cB.fillText('Unplaced after auto-suggestion (' + ccsBlankTotal + ') \u2014 recurring words', 0, 9);
		cB.font='10px Arial, sans-serif'; cB.fillStyle=mutedInk;
		cB.fillText(ccsBlankTotal ? 'Too few unplaced incidents to rank terms.' : 'No unplaced incidents \u2014 every row recorded or suggested.', 0, 34);
	}


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
		var blankTermsImg   = document.getElementById('ccsBlankTerms').toDataURL('image/png');
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
				'<span><b>Records:</b> ' + rowCount + '</span>' +
				'<span><b>Generated:</b> <?php echo date("d M Y, H:i"); ?></span>' +
			'</div>' +

			'<h2 class="sec">Summary</h2>' +
			'<div class="charts">' +
				'<div class="chart"><img src="' + chartMonthlyImg + '">' + '<div class="cap">Figure 1 &mdash; Incidents by month, by equipment</div></div>' +
				'<div class="chart"><img src="' + chartParetoImg + '">' + '<div class="cap">Figure 2 &mdash; Leading equipment by incident count</div></div>' +
				'<div class="chart"><img src="' + severityImg + '">' + '<div class="cap">Figure 3 &mdash; Equipment by severity level</div></div>' +
				'<div class="chart"><img src="' + blankTermsImg + '">' + '<div class="cap">Figure 4 &mdash; Recurring words among incidents left unplaced</div></div>' +
				'<p class="note">Equipment is the recorded value where one exists. Where none was recorded, an equipment is auto-suggested from the description text when the match is confident &mdash; shown italic in the log and as lighter segments in Figure 2, and indicative only. Incidents the suggestion could not place remain unspecified; Figure 4 counts words appearing in those descriptions and assigns no category. Figure 3 crosses equipment against recorded severity, so an equipment with few incidents but several at the highest level stands out &mdash; severity is a recorded value throughout, including on rows whose equipment was suggested.</p>' +
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
// Rows the classifier ABSTAINS on stay unplaced, and ccsTokenize is reused to
// mine THEIR descriptions for recurring words (Figure 4) — so the residue is
// described rather than guessed at.
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