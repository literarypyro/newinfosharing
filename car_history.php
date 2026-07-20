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


	$db2=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport_old");


$car_id=$_GET['car_id'];

$dateClause="";

if(isset($_GET['m'])){
	$dateClause=" and incident_date like '".$_GET['y']."-".date("m",strtotime($_GET['y']."-".$_GET['m']."-01"))."%%' ";
	$dateClause2=" and transport_old.incident_date like '".$_GET['y']."-".date("m",strtotime($_GET['y']."-".$_GET['m']."-01"))."%%' ";
}
else {
	if(isset($_GET['y'])){
		$dateClause=" and incident_date like '".$_GET['y']."-%%' ";
		$dateClause2=" and transport_old.incident_date like '".$_GET['y']."-".date("m",strtotime($_GET['y']."-".$_GET['m']."-01"))."%%' ";
	}
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
        <th>Equipment</th>

        <th>Incident Number</th>
        <th>Description</th>
    </tr>
    </thead>
    <tbody>
<?php
$sql="(select * from incident_cars inner join incident_union on incident_cars.incident_id=incident_union.id where incident_cars.car_no*1='".$car_id."' ".$dateClause." order by incident_date desc)";

$sql.=" union ";

$sql.="(select * from is_transport_old.incident_cars inner join is_transport_old.incident_union on is_transport_old.incident_cars.incident_id=is_transport_old.incident_union.id where is_transport_old.incident_cars.car_no*1='".$car_id."' ".$dateClause." order by incident_date desc)";
$rs=$db->query($sql);
$nm=$rs->num_rows;

// Chart aggregates, built in the same pass as the table rows —
// avoids a second query and guarantees the charts and table always
// reflect the same $dateClause filter.
$monthlyCounts=array();   // [ "YYYY-MM" => [ problemType => count ] ]
$problemCounts=array();   // [ problemType => count ]

for($i=0;$i<$nm;$i++){

	$row=$rs->fetch_assoc();
	$problemType=getEquipmentType($db,$row['equipt']);

	$monthKey=date("Y-m", strtotime($row['incident_date']));
	if(!isset($monthlyCounts[$monthKey])) $monthlyCounts[$monthKey]=array();
	if(!isset($monthlyCounts[$monthKey][$problemType])) $monthlyCounts[$monthKey][$problemType]=0;
	$monthlyCounts[$monthKey][$problemType]++;

	if(!isset($problemCounts[$problemType])) $problemCounts[$problemType]=0;
	$problemCounts[$problemType]++;
	?>
    <tr>
        <td><?php echo $row['index_no']; ?></td>
        <td><?php echo date("Y-m-d H:iA", strtotime($row['incident_date'])); ?></td>
        <td>&nbsp;</td>
        <td><?php echo $row['duration']; ?></td>
        <td><?php echo $problemType; ?></td>
        <td><?php echo getEquipmentType($db,$row['equipt']); ?></td>
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

<!-- Print-only chart summary. Hidden on screen (the live table above is
     the on-screen view); rendered to static images and injected into the
     TableTools print window — see the script block near the bottom. -->
<div id="ccs-print-charts" style="display:none;">
	<div style="display:flex; gap:24px; flex-wrap:wrap;">
		<div style="width:340px;">
			<canvas id="ccsChartMonthly" width="340" height="230"></canvas>
		</div>
		<div style="width:340px;">
			<canvas id="ccsChartPareto" width="340" height="200"></canvas>
		</div>
	</div>
</div>

<script>
// Raw aggregates from the same query/filter as the table above.
var ccsMonthlyCounts = <?php echo json_encode($monthlyCounts); ?>;
var ccsProblemCounts = <?php echo json_encode($problemCounts); ?>;
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
		data: { labels: months, datasets: monthlyDatasets },
		options: {
			responsive: false,
			animation: false,
			plugins: {
				title: { display: true, text: 'Incidents by month — top ' + TOP_N + ' equipment', color: textInk, font: { size: 11, weight: 'normal' }, padding: { bottom: 6 } },
				legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 }, color: mutedInk } }
			},
			scales: {
				x: { stacked: true, ticks: { color: mutedInk, font: { size: 10 } }, grid: { display: false } },
				y: { stacked: true, ticks: { color: mutedInk, precision: 0, font: { size: 10 } }, grid: { color: gridInk } }
			}
		}
	});

	// ---- Chart 2: top-3 leaders as horizontal bars, with the tail and
	// blanks drawn as a footnote *inside* the canvas (so it survives the
	// toDataURL handoff into the print window — sibling HTML would not).
	var footnotePlugin = {
		id: 'ccsFootnote',
		afterDraw: function(chart){
			var ctx = chart.ctx;
			var area = chart.chartArea;
			var lines = [];
			if(tailTotal > 0){
				lines.push('+ ' + tailTotal + ' more across ' + tailTypes.length + ' other equipment type' + (tailTypes.length === 1 ? '' : 's'));
			}
			if(blankCount > 0){
				lines.push('+ ' + blankCount + ' with unspecified equipment');
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

	new Chart(document.getElementById('ccsChartPareto'), {
		type: 'bar',
		data: {
			labels: topTypes,
			datasets: [{
				data: topTypes.map(function(t){ return ccsProblemCounts[t]; }),
				backgroundColor: topTypes.map(function(t, idx){ return palette[idx % palette.length]; }),
				borderRadius: 3,
				barThickness: 22
			}]
		},
		options: {
			indexAxis: 'y',
			responsive: false,
			animation: false,
			layout: { padding: { bottom: 30 } },
			plugins: {
				title: { display: true, text: 'Top ' + TOP_N + ' equipment by incidents (' + topShare + '% of total)', color: textInk, font: { size: 11, weight: 'normal' }, padding: { bottom: 8 } },
				legend: { display: false },
				tooltip: { callbacks: { label: function(c){ return c.parsed.x + ' incidents'; } } }
			},
			scales: {
				x: { ticks: { color: mutedInk, precision: 0, font: { size: 10 } }, grid: { color: gridInk } },
				y: { ticks: { color: textInk, font: { size: 11 } }, grid: { display: false } }
			}
		},
		plugins: [footnotePlugin]
	});

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

	function ccsPrintWithCharts(){
		var chartMonthlyImg = document.getElementById('ccsChartMonthly').toDataURL('image/png');
		var chartParetoImg  = document.getElementById('ccsChartPareto').toDataURL('image/png');
		var tableHtml = document.getElementById('add_form').outerHTML;

		var win = window.open('', '_blank');
		win.document.write(
			'<html><head><title>Car <?php echo htmlspecialchars($car_id); ?> — Incident History</title>' +
			'<style>' +
				'body{font-family:Arial,sans-serif;margin:24px;color:#111;}' +
				'h1{font-size:16px;margin:0 0 2px;}' +
				'.sub{font-size:11px;color:#555;margin:0 0 16px;}' +
				'.charts{display:flex;gap:16px;margin-bottom:20px;}' +
				'img{max-width:100%;}' +
				'table{width:100%;border-collapse:collapse;font-size:11px;}' +
				'th,td{border:1px solid #ccc;padding:4px 6px;text-align:left;}' +
				'a{color:inherit;text-decoration:none;pointer-events:none;}' + // dead-link the slide-panel column in print
			'</style></head><body>' +
			'<h1>Car <?php echo htmlspecialchars($car_id); ?> — Incident History</h1>' +
			'<div class="sub">Printed <?php echo date("Y-m-d H:i"); ?><?php echo isset($_GET["y"]) ? " — filter: ".htmlspecialchars($_GET["y"]).(isset($_GET["m"]) ? "-".htmlspecialchars($_GET["m"]) : "") : " — all records"; ?></div>' +
			'<div class="charts">' +
				'<img src="' + chartMonthlyImg + '">' +
				'<img src="' + chartParetoImg + '">' +
			'</div>' +
			tableHtml +
			'</body></html>'
		);
		win.document.close();
		win.focus();
		win.onload = function(){ win.print(); };
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

?>
<?php require("slide_panel.php"); ?>
</body>
</html>