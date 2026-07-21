<?php
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
$car_id=$_GET['car_id'];
?>
<?php include("history_theme.php"); ?>
<body>
<div class="ccs-page">

<div class="ccs-header">
	<h1>Incident History &mdash; Others</h1>
	<div class="sub">Incidents categorized as "Others" &mdash; Line 3</div>
</div>

<div class="ccs-panel">
<div class="ccs-panel-head"><h3>Incident History</h3></div>
<div class="ccs-panel-body">
<table class="table table-striped table-bordered bootstrap-datatable datatable2" width="100%" id='add_form' name='add_form' >
	<thead>
	<tr>
	<th>Index No</th>
	<th>Incident Date</th>
	<th>Problem Type</th>
	<th>Cause/Issue</th>
	<th>Incident Number</th>
	<th>Description</th>
	</tr>
	</thead>
	<tbody>
<?php

$sql="select * from incident_description inner join incident_report on incident_report.id=incident_description.incident_id where incident_type='others' order by incident_date desc";
$rs=$db->query($sql);
$nm=$rs->num_rows;

// ---- Cause map: other_problem is tiny (~5 rows), so load it once and
// resolve every row against it instead of a query per row. ----
$causeMap=array();
$cmRs=$db->query("select id, problem from other_problem");
if($cmRs){ while($cm=$cmRs->fetch_assoc()){ if(trim($cm['problem'])!=='') $causeMap[$cm['id']]=$cm['problem']; } }

// ---- Buffer all rows so we can make two passes: the categorized rows
// are the training set for the description classifier; the blanks are
// then scored against it. DB is never written — suggestions live only
// in this page's rendering. ----
$allRows=array();
for($i=0;$i<$nm;$i++){ $allRows[]=$rs->fetch_assoc(); }

// Pass 1 — learn word patterns per cause from rows that HAVE a cause.
$nbModel = ccsTrainClassifier($allRows,$causeMap);

// Pass 2 — render + aggregate. Blanks get a suggested cause when the
// classifier is confident enough; otherwise they stay Uncategorized.
$suggestedCounts=array();  // [cause] => how many were auto-suggested
$suggestedTotal=0;

foreach($allRows as $row){

	$isSuggested=false;
	if(isset($causeMap[$row['equipt']])){
		$cause=$causeMap[$row['equipt']];
	}
	else{
		$guess=ccsClassifyDescription($nbModel,$row['description']);
		if($guess!==null){
			$cause=$guess;
			$isSuggested=true;
			if(!isset($suggestedCounts[$cause])) $suggestedCounts[$cause]=0;
			$suggestedCounts[$cause]++;
			$suggestedTotal++;
		}
		else{
			$cause='Uncategorized';
		}
	}

	$monthKey=date("Y-m", strtotime($row['incident_date']));
	if(!isset($monthlyCounts[$monthKey])) $monthlyCounts[$monthKey]=array();
	if(!isset($monthlyCounts[$monthKey][$cause])) $monthlyCounts[$monthKey][$cause]=0;
	$monthlyCounts[$monthKey][$cause]++;

	if(!isset($problemCounts[$cause])) $problemCounts[$cause]=0;
	$problemCounts[$cause]++;
?>	
	<tr>
		<td><?php echo $row['index_no']; ?></td>
		<td><?php echo "<span>".date("Y-m-d",strtotime($row['incident_date']))."</span>"; ?></td>
		<td><?php echo  getProblemType($db,$row['incident_type']); ?></td>
		<td><?php
			// Confirmed causes render plain; auto-suggested ones are
			// visibly marked so nobody mistakes inference for data.
			if($isSuggested){
				echo "<span style='font-style:italic; opacity:.75;' title='Auto-suggested from the description text — not a recorded category'>".htmlspecialchars($cause)." <small>(suggested)</small></span>";
			}
			else if($cause==='Uncategorized'){
				echo "<span style='opacity:.55;'>Uncategorized</span>";
			}
			else{
				echo htmlspecialchars($cause);
			}
			/** previous direct lookup, replaced by the preloaded $causeMap:
			echo getCategory($db,$row['equipt']);
			*/
		?></td>
		

		<td><a href='#' class='two' onclick='openSlidePanel("edit_ccdr.php?ir=<?php echo  $row['id']; ?>&embed=1","Incident - <?php echo htmlspecialchars($row['incident_no']); ?>")'><?php echo $row['incident_no']; ?></a></td>
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
<div id="ccs-print-charts" style="display:none;">
	<div style="display:flex; flex-direction:column; gap:16px;">
		<canvas id="ccsHeatmap" width="560" height="220"></canvas>
		<canvas id="ccsChartPareto" width="440" height="200"></canvas>
	</div>
</div>

<script>
// Raw aggregates from the same query/filter as the table above.
var ccsMonthlyCounts = <?php echo json_encode($monthlyCounts); ?>;
var ccsProblemCounts = <?php echo json_encode($problemCounts); ?>;
var ccsSuggested = <?php echo json_encode($suggestedCounts, JSON_FORCE_OBJECT); ?>;
var ccsSuggestedTotal = <?php echo (int)$suggestedTotal; ?>;
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
<?php require("slide_panel.php"); ?>
<?php
function getProblemType($db,$type){
	$sql="select * from other_problem where id='".$type."'";
	$rs=$db->query($sql);

	$nm=$rs->num_rows;
	$row=$rs->fetch_assoc();

	$problem=$row['problem'];
	return $problem;
}
?>
</body>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
$(function(){

	// ---- Build both charts from the PHP cause/issue aggregates ----
	// Cause/issue has a small fixed cardinality (~6 incl. Uncategorized),
	// so no top-N folding is needed — every cause gets its own heatmap row
	// and its own Pareto bar. "Uncategorized" is always sorted last.
	var palette = ['#2a78d6','#eb6834','#1baf7a','#eda100','#e87ba4','#008300','#4a3aa7'];
	var uncatColor = '#9c9a92';
	var textInk = getComputedStyle(document.documentElement).getPropertyValue('--text-primary').trim() || '#111';
	var mutedInk = getComputedStyle(document.documentElement).getPropertyValue('--text-secondary').trim() || '#555';
	var gridInk = 'rgba(137,135,129,0.20)';
	var UNCAT = 'Uncategorized';

	var months = Object.keys(ccsMonthlyCounts).sort();

	// Causes ranked by total, Uncategorized forced to the end.
	var causes = Object.keys(ccsProblemCounts).sort(function(a,b){
		if(a === UNCAT) return 1;
		if(b === UNCAT) return -1;
		return ccsProblemCounts[b] - ccsProblemCounts[a];
	});
	function causeColor(cause, idx){ return cause === UNCAT ? uncatColor : palette[idx % palette.length]; }

	// ================= Chart 1: cause/issue × month heatmap =================
	// Hand-drawn on a raw canvas (not Chart.js) so it flattens to an image
	// for the TableTools print handoff, like the Pareto below. Rows = causes,
	// columns = months, cell shaded by incident count.
	(function drawHeatmap(){
		var cv = document.getElementById('ccsHeatmap');
		var ctx = cv.getContext('2d');
		var W = cv.width, H = cv.height;
		ctx.clearRect(0,0,W,H);
		ctx.textBaseline = 'middle';

		ctx.font = '11px Arial, sans-serif'; ctx.fillStyle = textInk; ctx.textAlign = 'left';
		ctx.fillText('"Others" incidents — cause/issue by month', 0, 9);

		if(!months.length || !causes.length){ ctx.fillStyle = mutedInk; ctx.font = '10px Arial'; ctx.fillText('No data', 0, 40); return; }

		var padL = 150, padT = 30, padR = 10, padB = 22;
		var gridW = W - padL - padR, gridH = H - padT - padB;
		var cellW = gridW / months.length, cellH = gridH / causes.length;

		var maxV = 0;
		causes.forEach(function(c){ months.forEach(function(m){ maxV = Math.max(maxV, (ccsMonthlyCounts[m] && ccsMonthlyCounts[m][c]) || 0); }); });
		if(maxV === 0) maxV = 1;

		// month column headers (show every month; abbreviate to MM)
		ctx.font = '9px Arial, sans-serif'; ctx.fillStyle = mutedInk; ctx.textAlign = 'center';
		months.forEach(function(m,ci){ ctx.fillText(m.slice(5), padL + ci*cellW + cellW/2, padT - 9); });

		causes.forEach(function(cause,ri){
			var y = padT + ri*cellH;
			// row label
			ctx.font = '10px Arial, sans-serif';
			ctx.fillStyle = cause === UNCAT ? mutedInk : textInk;
			ctx.textAlign = 'right';
			var label = cause.length > 22 ? cause.slice(0,21)+'\u2026' : cause;
			ctx.fillText(label, padL - 6, y + cellH/2);
			// cells
			months.forEach(function(m,ci){
				var v = (ccsMonthlyCounts[m] && ccsMonthlyCounts[m][cause]) || 0;
				var x = padL + ci*cellW;
				var base = cause === UNCAT ? '156,154,146' : '42,120,214';
				var t = v === 0 ? 0.04 : 0.12 + (v/maxV)*0.82;
				ctx.fillStyle = 'rgba('+base+','+t.toFixed(3)+')';
				ctx.fillRect(x+1, y+1, cellW-2, cellH-2);
				if(v > 0){
					ctx.fillStyle = (v/maxV > 0.55) ? '#fff' : mutedInk;
					ctx.font = '9px Arial, sans-serif'; ctx.textAlign = 'center';
					ctx.fillText(String(v), x + cellW/2, y + cellH/2);
				}
			});
		});

		// legend
		ctx.font = '9px Arial, sans-serif'; ctx.textAlign = 'left'; ctx.fillStyle = mutedInk;
		ctx.fillText('Darker = more incidents', padL, H - 8);
	})();

	// ================= Chart 2: Pareto of causes =================
	// Two stacked layers per bar: solid = confirmed categorization,
	// lighter = auto-suggested from descriptions. Inference is always
	// visually distinct from recorded data.
	var totalIncidents = causes.reduce(function(s,c){ return s+ccsProblemCounts[c]; }, 0);
	var uncatRemaining = ccsProblemCounts[UNCAT] || 0;

	function hexToRgba(hex, a){
		var n = parseInt(hex.slice(1), 16);
		return 'rgba(' + ((n>>16)&255) + ',' + ((n>>8)&255) + ',' + (n&255) + ',' + a + ')';
	}
	function suggestedColor(cause, idx){
		return cause === UNCAT ? 'rgba(156,154,146,0.45)' : hexToRgba(palette[idx % palette.length], 0.4);
	}

	var confirmedData = causes.map(function(c){ return (ccsProblemCounts[c]||0) - (ccsSuggested[c]||0); });
	var suggestedData = causes.map(function(c){ return ccsSuggested[c]||0; });

	var paretoNote = {
		id: 'paretoNote',
		afterDraw: function(chart){
			if(!ccsSuggestedTotal) return;
			var ctx = chart.ctx, area = chart.chartArea;
			ctx.save();
			ctx.font = '10px Arial, sans-serif'; ctx.fillStyle = mutedInk;
			ctx.textAlign = 'left'; ctx.textBaseline = 'top';
			var y = chart.height - 14;
			ctx.strokeStyle = gridInk; ctx.lineWidth = 1;
			ctx.beginPath(); ctx.moveTo(area.left, y - 5); ctx.lineTo(chart.width - 8, y - 5); ctx.stroke();
			ctx.fillText('Lighter segments: ' + ccsSuggestedTotal + ' auto-suggested from descriptions \u00b7 ' + uncatRemaining + ' remain uncategorized', area.left, y);
			ctx.restore();
		}
	};

	new Chart(document.getElementById('ccsChartPareto'), {
		type: 'bar',
		data: {
			labels: causes,
			datasets: [
				{
					label: 'Confirmed',
					data: confirmedData,
					backgroundColor: causes.map(causeColor),
					categoryPercentage: 0.6,
					barPercentage: 0.9
				},
				{
					label: 'Suggested',
					data: suggestedData,
					backgroundColor: causes.map(suggestedColor),
					borderRadius: 3,
					categoryPercentage: 0.6,
					barPercentage: 0.9
				}
			]
		},
		options: {
			indexAxis: 'y',
			responsive: false,
			animation: false,
			layout: { padding: { right: 22, bottom: ccsSuggestedTotal ? 18 : 4 } },
			plugins: {
				title: { display: true, text: 'Cause/issue by total incidents', color: textInk, font: { size: 11, weight: 'normal' }, padding: { bottom: 8 } },
				legend: { display: false },
				tooltip: { callbacks: { label: function(c){
					var kind = c.datasetIndex === 1 ? ' suggested' : ' confirmed';
					var p = totalIncidents ? Math.round(c.parsed.x/totalIncidents*100) : 0;
					return c.parsed.x + kind + ' (' + p + '% of all)';
				} } }
			},
			scales: {
				x: { stacked: true, ticks: { color: mutedInk, precision: 0, font: { size: 10 } }, grid: { color: gridInk } },
				y: { stacked: true, ticks: { color: textInk, font: { size: 11 } }, grid: { display: false } }
			}
		},
		plugins: [{
			id: 'paretoValueLabels',
			afterDatasetsDraw: function(chart){
				// label the TOTAL at the end of the stacked bar (dataset 1's
				// meta ends where the full stack ends)
				var ctx = chart.ctx, meta = chart.getDatasetMeta(1);
				ctx.save(); ctx.font = '11px Arial, sans-serif'; ctx.fillStyle = textInk;
				ctx.textBaseline = 'middle'; ctx.textAlign = 'left';
				meta.data.forEach(function(bar,i){
					var total = confirmedData[i] + suggestedData[i];
					ctx.fillText(total, bar.x + 6, bar.y);
				});
				ctx.restore();
			}
		}, paretoNote]
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
		var heatmapImg     = document.getElementById('ccsHeatmap').toDataURL('image/png');
		var chartParetoImg = document.getElementById('ccsChartPareto').toDataURL('image/png');
		var tableHtml = document.getElementById('add_form').outerHTML;

		var win = window.open('', '_blank');
		win.document.write(
			'<html><head><title>Other Recorded Incidences</title>' +
			'<style>' +
				'body{font-family:Arial,sans-serif;margin:24px;color:#111;}' +
				'h1{font-size:16px;margin:0 0 2px;}' +
				'.sub{font-size:11px;color:#555;margin:0 0 16px;}' +
				'.charts{display:flex;flex-direction:column;gap:14px;margin-bottom:20px;}' +
				'.charts img{border:0.5px solid #ddd;max-width:580px;}' +
				'table{width:100%;border-collapse:collapse;font-size:11px;}' +
				'th,td{border:1px solid #ccc;padding:4px 6px;text-align:left;}' +
				'a{color:inherit;text-decoration:none;pointer-events:none;}' + // dead-link the slide-panel column in print
			'</style></head><body>' +
			'<h1>Other Recorded Incidences</h1>' +
			'<div class="sub">Printed <?php echo date("Y-m-d H:i"); ?><?php echo isset($_GET["y"]) ? " — filter: ".htmlspecialchars($_GET["y"]).(isset($_GET["m"]) ? "-".htmlspecialchars($_GET["m"]) : "") : " — all records"; ?></div>' +
			'<div class="charts">' +
				'<img src="' + heatmapImg + '">' +
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
/*
function getProblemType($db,$type){
	$sql="select * from other_problem where equipment_code='".$type."'";
	$rs=$db->query($sql);

	$nm=$rs->num_rows;
	$row=$rs->fetch_assoc();

	$problem=$row['equipment_name'];
	return $problem;
}
*/
function getCategory($db,$type){
	$sql="select * from other_problem where id='".$type."'";
	$rs=$db->query($sql);

	$nm=$rs->num_rows;
	$row=$rs->fetch_assoc();

	$problem=$row['problem'];
	return $problem;
}

// ============================================================
// Description -> cause/issue suggestion (naive Bayes, pure PHP).
//
// The categorized rows are the training set: each is a labelled example
// of "descriptions like this belong to cause X". Blanks are then scored
// against those word patterns. No hardcoded keyword lists — it re-learns
// from whatever is categorized on every page load, so it improves as
// staff categorize more rows. Suggestions are display-only; nothing is
// ever written back to the database.
// ============================================================

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

function ccsTrainClassifier($rows,$causeMap){
	$m = array('docs'=>array(), 'words'=>array(), 'wtotal'=>array(), 'vocab'=>array(), 'ndocs'=>0);
	foreach($rows as $row){
		if(!isset($causeMap[$row['equipt']])) continue;   // only categorized rows train
		$cat = $causeMap[$row['equipt']];
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

function ccsClassifyDescription($m,$desc){
	// Need at least two trained causes to discriminate between anything.
	if($m['ndocs'] < 4 || count($m['docs']) < 2) return null;

	$toks = ccsTokenize($desc);
	if(!count($toks)) return null;

	// Confidence gate 1: the description must contain at least two words
	// the model has ever seen — otherwise it has no basis to guess.
	$known=0;
	foreach($toks as $t){ if(isset($m['vocab'][$t])) $known++; }
	if($known < 2) return null;

	$V = count($m['vocab']);
	$best=null; $bestS=-INF; $secondS=-INF;
	foreach($m['docs'] as $cat=>$dc){
		$s = log($dc / $m['ndocs']);   // prior
		foreach($toks as $t){
			$wc = isset($m['words'][$cat][$t]) ? $m['words'][$cat][$t] : 0;
			$s += log(($wc + 1) / ($m['wtotal'][$cat] + $V));   // Laplace-smoothed likelihood
		}
		if($s > $bestS){ $secondS=$bestS; $bestS=$s; $best=$cat; }
		elseif($s > $secondS){ $secondS=$s; }
	}

	// Confidence gate 2: the winner must beat the runner-up by ~2x
	// likelihood (0.69 in log space). Ties stay Uncategorized — an honest
	// residual beats a confident wrong answer.
	if(($bestS - $secondS) < 0.69) return null;

	return $best;
}
?>