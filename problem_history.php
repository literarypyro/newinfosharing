<?php
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
  <h2>
<?PHP
	$sql="select * from incident_description inner join incident_report on incident_report.id=incident_description.incident_id where incident_type='".$problem."' order by incident_date desc limit 1";
	$rs2=$db->query($sql);
	$displayRow=$rs2->fetch_assoc();

	echo "Latest Entry Recorded: ".date("F d, Y",strtotime($displayRow['incident_date']));
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

		// Document frequency: count a term once per incident it appears
		// in, so one verbose description can't dominate the ranking.
		foreach(array_unique(ccsTokenize($row['description'])) as $t){
			if(!isset($termCounts[$t])) $termCounts[$t]=0;
			$termCounts[$t]++;
		}
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

	// Rank recurring terms; keep the top 8 that appear in 2+ incidents.
	arsort($termCounts);
	// @months -- A month with no incidents has no key at all, so the axis used
	// to close the gap and print two non-adjacent months side by side. Fill the
	// span so the x-axis is continuous. Months the coverage table marks missing
	// get null rather than 0: no records because none were kept is not the same
	// claim as no incidents, and Chart.js draws nothing for null.
	if(count($monthlyVolume)){
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
	<canvas id="pvVolume" width="340" height="160"></canvas>
	<canvas id="pvTiming" width="340" height="180"></canvas>
	<canvas id="pvTerms"  width="340" height="180"></canvas>
</div>

<script>
var pvCoverageNote = <?php echo json_encode(htmlspecialchars($coverageNote, ENT_QUOTES)); ?>;
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
		var imgTerms  = document.getElementById('pvTerms').toDataURL('image/png');
		var name      = escHtml(pvProblemName);
		var captured  = pvFullTableHtml();
		var tableHtml = captured.html;
		var rowCount  = captured.count;
		var omitted   = captured.omitted;
		var periodTxt = pvWindowFrom
			? (pvWindowFrom + ' to ' + pvWindow[pvWindow.length-1] + (pvTrimmed ? ' (last 24 months)' : ''))
			: 'All recorded dates';

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
				'<div class="chart"><img src="' + imgTerms + '">' + '<div class="cap">Figure 3 &mdash; Recurring words in descriptions</div></div>' +
				(pvCoverageNote ? '<p class="note" style="color:#7A1F1F;">'+pvCoverageNote+'</p>' : '') +
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