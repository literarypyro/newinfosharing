<?php
	// $_SESSION is used below for the sticky problem selection — nothing
	// else on this page starts the session, so do it here (guarded in
	// case an include ever starts one first).
	if(session_status()===PHP_SESSION_NONE) session_start();

	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
$car_id=$_GET['car_id'];


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
<?php include("history_theme.php"); ?>
<body>
<div class="ccs-page">

<div class="ccs-header">
	<h1>Incident History &mdash; By Category</h1>
	<div class="sub">Filtered by: <?php echo htmlspecialchars($problem ? getProblemType($db,$problem) : '—'); ?> &mdash; Line 3</div>
</div>

<div class="ccs-panel">
<div class="ccs-panel-head">
  <h3>Incident History</h3>
  <div class="ccs-panel-actions">
    <label for="problemFilter" class="sr-only">Problem Type</label>
    <select id="problemFilter" onchange="location.href='problem_history.php?problem='+this.value">
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
</div><div class="ccs-panel-body">
<table class="table table-striped table-bordered bootstrap-datatable datatable2" width="100%" id='add_form' name='add_form' >
	<thead>
	<tr>
	<th>Index No</th>
	<th>Problem Category</th>
	<th>Incident Date/Time</th>
	<th>Time Resolved</th>
	<th>Duration</th>
	<th>Incident Number</th>
	<th>Description</th>
	</tr>
	</thead>
	<tbody>
	<?php

	$sql="select * from incident_description inner join incident_report on incident_report.id=incident_description.incident_id where incident_type='".$problem."' order by incident_date desc";
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
	$termCounts=array();                  // [token] => number of incidents it appears in

	for($i=0;$i<$nm;$i++){
		$row=$rs->fetch_assoc();

		$ts=strtotime($row['incident_date']);
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

		// Document frequency: count a term once per incident it appears
		// in, so one verbose description can't dominate the ranking.
		foreach(array_unique(ccsTokenize($row['description'])) as $t){
			if(!isset($termCounts[$t])) $termCounts[$t]=0;
			$termCounts[$t]++;
		}
	?>	
		<tr>
			<td><?php echo $row['index_no']; ?></td>
			<td><?php echo getProblemType($db,$row['incident_type']); ?></td>
			<td><?php echo "<span>".date("Y-m-d H:ia",strtotime($row['incident_date']))."</span>"; ?></td>
			<td>&nbsp;</td>	
			<td>&nbsp;</td>	

		<td><a href='#' class='two' onclick='openSlidePanel("edit_ccdr.php?ir=<?php echo  $row['id']; ?>&embed=1","Incident - <?php echo htmlspecialchars($row['incident_no']); ?>")'><?php echo $row['incident_no']; ?></a></td>
</td>
		

			<td><?php echo $row['description']; ?></td>
		
		</tr>
	<?php
	}

	// Rank recurring terms; keep the top 8 that appear in 2+ incidents.
	arsort($termCounts);
	$topTerms=array();
	foreach($termCounts as $t=>$c){
		if($c < 2) break;                  // sorted desc — everything after is rarer
		$topTerms[]=array($t,(int)$c);
		if(count($topTerms) >= 8) break;
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
	<canvas id="pvVolume" width="440" height="140"></canvas>
	<canvas id="pvTiming" width="440" height="190"></canvas>
	<canvas id="pvTerms"  width="440" height="190"></canvas>
</div>

<script>
// Aggregates from the same query/filter as the table above.
var pvProblemName = <?php echo json_encode($problemName); ?>;
var pvMonthly = <?php echo json_encode($monthlyVolume, JSON_FORCE_OBJECT); ?>;
var pvTiming = <?php echo json_encode($timingGrid); ?>;
var pvTerms = <?php echo json_encode($topTerms); ?>;
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

	var textInk  = getComputedStyle(document.documentElement).getPropertyValue('--text-primary').trim() || '#111';
	var mutedInk = getComputedStyle(document.documentElement).getPropertyValue('--text-secondary').trim() || '#555';
	var gridInk  = 'rgba(137,135,129,0.20)';
	var mainColor = '#2a78d6', termColor = '#1baf7a';

	function escHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

	// ============ Chart 1: monthly volume ============
	var months = Object.keys(pvMonthly).sort();
	var volMonths = months.slice(-24);   // no date filter on this page — cap the chart at the last 24 months
	var volTitle = pvProblemName + ' \u2014 monthly volume' + (months.length > 24 ? ' (last 24 months)' : '');

	new Chart(document.getElementById('pvVolume'), {
		type: 'bar',
		data: {
			labels: volMonths.map(function(m){ return m.slice(2); }),
			datasets: [{ data: volMonths.map(function(m){ return pvMonthly[m]; }), backgroundColor: mainColor, borderRadius: 3 }]
		},
		options: {
			responsive: false, animation: false,
			plugins: {
				title: { display: true, text: volTitle, color: textInk, font: { size: 11, weight: 'normal' }, padding: { bottom: 6 } },
				legend: { display: false }
			},
			scales: {
				x: { ticks: { color: mutedInk, font: { size: 9 }, maxRotation: 45 }, grid: { display: false } },
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

	// ============ Chart 3: recurring description terms ============
	if(pvTerms.length){
		new Chart(document.getElementById('pvTerms'), {
			type: 'bar',
			data: {
				labels: pvTerms.map(function(p){ return p[0]; }),
				datasets: [{ data: pvTerms.map(function(p){ return p[1]; }), backgroundColor: termColor, borderRadius: 3, categoryPercentage: 0.6, barPercentage: 0.9 }]
			},
			options: {
				indexAxis: 'y', responsive: false, animation: false, layout: { padding: { right: 22 } },
				plugins: {
					title: { display: true, text: pvProblemName + ' \u2014 recurring words in descriptions', color: textInk, font: { size: 11, weight: 'normal' }, padding: { bottom: 8 } },
					legend: { display: false },
					tooltip: { callbacks: { label: function(c){ return 'appears in ' + c.parsed.x + ' incidents'; } } }
				},
				scales: {
					x: { ticks: { color: mutedInk, precision: 0, font: { size: 10 } }, grid: { color: gridInk } },
					y: { ticks: { color: textInk, font: { size: 11 } }, grid: { display: false } }
				}
			},
			plugins: [{
				id: 'termValueLabels',
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
		var cv3 = document.getElementById('pvTerms');
		var c3 = cv3.getContext('2d');
		c3.textBaseline = 'middle'; c3.textAlign = 'left';
		c3.font = '11px Arial, sans-serif'; c3.fillStyle = textInk;
		c3.fillText(pvProblemName + ' \u2014 recurring words in descriptions', 0, 9);
		c3.font = '10px Arial, sans-serif'; c3.fillStyle = mutedInk;
		c3.fillText('Not enough description data to rank recurring terms.', 0, 34);
	}

	// ============ Intercept the TableTools print button ============
	var printBtn = $('#add_form_wrapper').find('.DTTT_button_print, .buttons-print');
	if(printBtn.length){
		printBtn.off('click').on('click', function(e){
			e.preventDefault();
			e.stopImmediatePropagation();
			pvPrintWithCharts();
		});
	}

	function pvPrintWithCharts(){
		var imgVolume = document.getElementById('pvVolume').toDataURL('image/png');
		var imgTiming = document.getElementById('pvTiming').toDataURL('image/png');
		var imgTerms  = document.getElementById('pvTerms').toDataURL('image/png');
		var tableHtml = document.getElementById('add_form').outerHTML;
		var name = escHtml(pvProblemName);

		var win = window.open('', '_blank');
		win.document.write(
			'<html><head><title>' + name + ' \u2014 Incident History</title>' +
			'<style>' +
				'body{font-family:Arial,sans-serif;margin:24px;color:#111;}' +
				'h1{font-size:16px;margin:0 0 2px;}' +
				'.sub{font-size:11px;color:#555;margin:0 0 16px;}' +
				'.charts{display:flex;flex-direction:column;gap:14px;margin-bottom:20px;}' +
				'.charts img{border:0.5px solid #ddd;max-width:460px;}' +
				'table{width:100%;border-collapse:collapse;font-size:11px;}' +
				'th,td{border:1px solid #ccc;padding:4px 6px;text-align:left;}' +
				'a{color:inherit;text-decoration:none;pointer-events:none;}' + // dead-link the slide-panel column in print
			'</style></head><body>' +
			'<h1>' + name + ' \u2014 Incident History</h1>' +
			'<div class="sub">Printed <?php echo date("Y-m-d H:i"); ?> \u00b7 Problem type: ' + name + ' \u00b7 all recorded dates</div>' +
			'<div class="charts">' +
				'<img src="' + imgVolume + '">' +
				'<img src="' + imgTiming + '">' +
				'<img src="' + imgTerms + '">' +
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