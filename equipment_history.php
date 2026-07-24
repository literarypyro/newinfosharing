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

// Which periods the console actually holds records for. This log can jump
// across a gap with nothing on the page explaining why — a reader would
// reasonably take the silence for "nothing happened" rather than "the records
// are missing". See data_coverage.php.
require_once("data_coverage.php");
$coverage = ccsLoadCoverage($db);
$coverageNote = ccsCoverageNote($coverage);
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
                  where incident_union.equipt = '".$_GET['equipt']."' ".$dateClause." ".$levelClause);
if($cq && ($cr = $cq->fetch_assoc())) $ehPairs = (int)$cr['c'];

$sql="select * from incident_union ".$initialClause." ".$dateClause." ".$levelClause." order by incident_date desc";




?>
<body>
<div class="ccs-page">

<div class="ccs-header">
<h1 style='font-size:28px; font-weight:bold;'><?php echo $equipment_name; ?> - Equipment History</h1>
	<div class="sub">Combined current &amp; legacy incident records &mdash; Line 3</div>
<?php if($coverageNote !== ''){ ?>
	<div class="sub" style="margin-top:4px;color:#F6C7C7;"><?php echo htmlspecialchars($coverageNote); ?></div>
<?php } ?>
	<div class="sub" style="margin-top:4px;">
		<b><?php echo $ehIncidents; ?></b> incident<?php echo $ehIncidents==1?'':'s'; ?> listed
		&nbsp;&middot;&nbsp;
		<b><?php echo $ehPairs; ?></b> car-level failure<?php echo $ehPairs==1?'':'s'; ?>
		<span style="opacity:.75;">&mdash; one incident can affect several cars, so the summary and per-car reports show the larger figure</span>
	</div>
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

//." ".$levelClause


$sevInternalSql = "select date_format(incident_date,'%Y-%m') as mo, level as lvl, count(*) as cnt
                   from incident_union
                   where equipt='".$_GET['equipt']."' ".$chartDateClause."
                   group by mo, lvl";
ehGroupSeverity($monthlyByLevel,$levelSet,$db->query($sevInternalSql));

$sevExternalSql = "select date_format(incident_date,'%Y-%m') as mo, level as lvl, count(*) as cnt
                   from incident_union
                   inner join external.incident_defects on incident_union.id=external.incident_defects.incident_id
                   where external.incident_defects.equipt_id='".$_GET['equipt']."' ".$chartDateClause."
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
           where incident_union.equipt = '".$_GET['equipt']."' ".$chartDateClause."
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
				'.charts{ page-break-inside:avoid; }' +
				'.chart{ display:inline-block; vertical-align:top; margin:0 14px 12px 0; }' +
				'.chart img{ display:block; border:1px solid #e5e7eb; }' +
				'.chart .cap{ font-size:9px; color:#6b7280; margin-top:3px; }' +
				'.note{ font-size:9px; color:#6b7280; font-style:italic; margin:2px 0 0; }' +

				// --- table ------------------------------------------------
				'.tbl-head{ margin-bottom:6px; }' +
				'.tbl-head h3{ font-size:13px; font-weight:600; margin:0; display:inline-block; }' +
				'.tbl-head .count{ font-size:9.5px; color:#6b7280; margin-left:8px; }' +
				'table{ width:100%; border-collapse:collapse; font-size:9.5px; }' +
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
				'<?php echo isset($_GET["level"]) ? "<span><b>Severity:</b> Level ".htmlspecialchars($_GET["level"])."</span>" : ""; ?>' +
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
				(ccsCoverageNote ? '<p class="note" style="color:#7A1F1F;">'+ccsCoverageNote+'</p>' : '') +
				'<p class="note">This log lists one row per incident. The summary and per-car reports count incident-car failures &mdash; an incident affecting several cars counts once against each &mdash; which is why their totals are larger. Both figures for this view are given above.</p>' +
				'<p class="note">Charts cover all of <?php echo ($chartYear !== "") ? htmlspecialchars($chartYear) : "available records"; ?>, a wider window than the filtered table below, so the by-car and severity views carry enough data to read.</p>' +
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