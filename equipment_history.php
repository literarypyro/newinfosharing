<?php
// equipment_history.php — same Line 3 console theme as car_history.php,
// its sibling drill-down page, so the two "further history" views share
// one visual identity instead of two different half-finished looks.
// Only this comment + the wrapping doctype/head + the <style> block
// below change anything about the page; all query/business logic is
// untouched. Also dropped an unused $car_id read from $_GET['car_id']
// that was never referenced anywhere in the file (this page keys off
// $_GET['equipt'], not car_id -- leftover from wherever this was
// originally copied from).
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Equipment Incident History</title>
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

$initialClause=" where equipt='".$_GET['equipt']."' ";
$dateClause="";
if(isset($_GET['m'])){

	$dateClause.=" and incident_date like '".$_GET['y']."-".date("m",strtotime($_GET['y']."-".$_GET['m']."-01"))."%%' ";
}
else {
	if(isset($_GET['y'])){
	

		$dateClause.=" and incident_date like '".$_GET['y']."-%%' ";


	}
}
$levelClause="";
if(isset($_GET['level'])){

	$levelClause=" and level='".$_GET['level']."' ";

}

?>
<?php

$identify_equipment="select * from equipment where id='".$_GET['equipt']."' limit 1";
$identify_rs=$db->query($identify_equipment);

$identify_row=$identify_rs->fetch_assoc();

$equipment_name=$identify_row['equipment_name'];

$sql="select * from incident_union ".$initialClause." ".$dateClause." ".$levelClause." order by incident_date desc";




?>
<body>
<div class="ccs-page">

<div class="ccs-header">
<h1 style='font-size:28px; font-weight:bold;'><?php echo $equipment_name; ?> - Equipment History</h1>
	<div class="sub">Combined current &amp; legacy incident records &mdash; Line 3</div>
</div>
<div class="ccs-panel">
<div class="ccs-panel-head"><h3>Incident History</h3></div>
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
		        <td>&nbsp;</td>
        <td><?php echo $row['duration']; ?></td>

		<td><?php echo  getProblemType($db,$row['incident_type']); ?></td>
		<td>
		<a href='#' class='two' onclick='openSlidePanel("edit_ccdr.php?ir=<?php echo  $row['id']; ?>&embed=1","Incident - <?php echo htmlspecialchars($row['incident_no']); ?>")'><?php echo $row['incident_no']; ?></a></td>
		</td>
		<td><?php echo $row['description']; ?></td>
	
	</tr>
<?php
}
$initialClause=" where external.incident_defects.equipt_id='".$_GET['equipt']."'";

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
		
		<td><?php echo  getProblemType($db,$row['incident_type']); ?></td>
		
		<td>
		
		
		<a href='#' class='two' onclick='openSlidePanel("edit_ccdr.php?ir=<?php echo  $row['id']; ?>&embed=1","Incident - <?php echo htmlspecialchars($row['incident_no']); ?>")'><?php echo $row['incident_no']; ?></a></td>

		
		
		
		</td>
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
$chartYear = isset($_GET['y']) ? $_GET['y'] : '';
$chartDateClause = ($chartYear !== '') ? " and incident_date like '".$chartYear."-%%' " : "";

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

$sevInternalSql = "select date_format(incident_date,'%Y-%m') as mo, level as lvl, count(*) as cnt
                   from incident_union
                   where equipt='".$_GET['equipt']."' ".$chartDateClause." ".$levelClause."
                   group by mo, lvl";
ehGroupSeverity($monthlyByLevel,$levelSet,$db->query($sevInternalSql));

$sevExternalSql = "select date_format(incident_date,'%Y-%m') as mo, level as lvl, count(*) as cnt
                   from incident_union
                   inner join external.incident_defects on incident_union.id=external.incident_defects.incident_id
                   where external.incident_defects.equipt_id='".$_GET['equipt']."' ".$chartDateClause." ".$levelClause."
                   group by mo, lvl";
ehGroupSeverity($monthlyByLevel,$levelSet,$db->query($sevExternalSql));

ksort($levelSet);
$ehLevels = array_keys($levelSet);

// ---- Incidents by car: [car_no] => count, year-scoped. Needs the
// incident_cars join (incident_union alone has no car field). NOTE: an
// incident can involve several cars, so this counts incident-car pairs,
// not distinct incidents. External defects excluded (no car mapping). ----
$carRows = array();
$carSql = "select incident_cars.car_no as car_no, count(*) as cnt
           from incident_cars
           inner join incident_union on incident_cars.incident_id = incident_union.id
           where incident_union.equipt = '".$_GET['equipt']."' ".$chartDateClause." ".$levelClause."
           group by incident_cars.car_no
           order by cnt desc";
$carRs = $db->query($carSql);
if($carRs){ while($cr = $carRs->fetch_assoc()){ $carRows[] = array($cr['car_no'], (int)$cr['cnt']); } }
?>

<!-- Print-only chart summary. Hidden on screen; the two canvases are
     flattened to images and injected into the TableTools print window.
     Landscape, and the by-car canvas is tall enough to space its bars. -->
<div id="ccs-print-charts" style="display:none;">
	<canvas id="ehCars"  width="440" height="230"></canvas>
	<canvas id="ehTrend" width="440" height="150"></canvas>
</div>

<script>
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
					title: { display: true, text: 'Incidents by car — top ' + TOP_CARS, color: textInk, font: { size: 11, weight: 'normal' }, padding: { bottom: 10 } },
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
			data: { labels: months, datasets: datasets },
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

	function ehPrintWithCharts(){
		var imgCars   = document.getElementById('ehCars').toDataURL('image/png');
		var imgTrend  = document.getElementById('ehTrend').toDataURL('image/png');
		var tableHtml = document.getElementById('add_form').outerHTML;

		var win = window.open('', '_blank');
		win.document.write(
			'<html><head><title><?php echo htmlspecialchars($equipment_name); ?> — Equipment History</title>' +
			'<style>' +
				'body{font-family:Arial,sans-serif;margin:24px;color:#111;}' +
				'h1{font-size:16px;margin:0 0 2px;}' +
				'.sub{font-size:11px;color:#555;margin:0 0 4px;}' +
				'.chartnote{font-size:10px;color:#777;font-style:italic;margin:0 0 16px;}' +
				'.charts{display:flex;flex-direction:column;gap:14px;margin-bottom:20px;}' +
				'.charts img{border:0.5px solid #ddd;max-width:460px;}' +
				'table{width:100%;border-collapse:collapse;font-size:11px;}' +
				'th,td{border:1px solid #ccc;padding:4px 6px;text-align:left;}' +
				'a{color:inherit;text-decoration:none;pointer-events:none;}' +
			'</style></head><body>' +
			'<h1><?php echo htmlspecialchars($equipment_name); ?> — Equipment History</h1>' +
			'<div class="sub">Printed <?php echo date("Y-m-d H:i"); ?> &nbsp;·&nbsp; Table: <?php echo isset($_GET["y"]) ? htmlspecialchars($_GET["y"]).(isset($_GET["m"]) ? "-".str_pad(date("m",strtotime($_GET["y"]."-".$_GET["m"]."-01")),2,"0",STR_PAD_LEFT) : "") : "all records"; ?><?php echo isset($_GET["level"]) ? " (level ".htmlspecialchars($_GET["level"]).")" : ""; ?></div>' +
			'<div class="chartnote">Charts below cover all of <?php echo ($chartYear !== "") ? htmlspecialchars($chartYear) : "available records"; ?>, not just the filtered month — the wider window is needed for the by-car and severity views to be meaningful.</div>' +
			'<div class="charts">' +
				'<img src="' + imgCars + '">' +
				'<img src="' + imgTrend + '">' +
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
<?php require("slide_panel.php"); ?>
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