<?php
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");

// The personnel name arrives from the search form (POST). Guard the read so a
// plain GET load doesn't raise a notice, and keep the display value separate
// from the escaped value used in the query.
//
// NOTE: this is free-text typed by the user and dropped straight into a LIKE
// clause, so it is the most injection-exposed input in the console. Escaped
// here as a minimum; a prepared statement is the proper fix when this page
// gets moved onto db_config.php.
$reportedBy    = isset($_POST['reported_by']) ? trim($_POST['reported_by']) : '';
$hasSearch     = ($reportedBy !== '');
$reportedByEsc = $db->real_escape_string($reportedBy);
$tdVariants=array();
$whereNames='';

if($hasSearch){
	// ---- Resolve the typed name to every spelling of the same person ------
	// Step 1: pull the distinct names actually present (queried through the
	// same join, so it works wherever reported_by physically lives).
	// Step 2: seed on a substring match — what the old LIKE did, and what
	//         keeps partial typing working.
	// Step 3: expand each seed to its variant group via ccsNamesMatch, so
	//         "TD A. Domingo" also pulls "A.DOMINGO" and "Domingo, A.".
	$allNames=array();
	$nRs=$db->query("select distinct reported_by from incident_description inner join incident_report on incident_report.id=incident_description.incident_id where reported_by is not null and reported_by<>''");
	if($nRs){ while($nr=$nRs->fetch_assoc()){ $allNames[]=$nr['reported_by']; } }

	$needle=strtoupper($reportedBy);
	$seeds=array();
	foreach($allNames as $n){ if(strpos(strtoupper($n),$needle)!==false) $seeds[]=$n; }
	if(!count($seeds)) $seeds=array($reportedBy);

	$vset=array();
	foreach($seeds as $seed){ $vset[$seed]=true; }
	foreach($allNames as $n){
		if(isset($vset[$n])) continue;
		foreach($seeds as $seed){
			if(ccsNamesMatch($n,$seed)){ $vset[$n]=true; break; }
		}
	}
	$tdVariants=array_keys($vset);
	sort($tdVariants);

	// Match on the exact variant list rather than a LIKE, so the result set is
	// the union of every spelling and nothing else.
	$in=array();
	foreach($tdVariants as $v){ $in[]="'".$db->real_escape_string($v)."'"; }
	$whereNames = count($in)
		? "reported_by in (".implode(",",$in).")"
		: "reported_by like '%".$reportedByEsc."%'";   // fallback: behave as before
}
?>
<?php include("history_theme.php"); ?>
<body>
<div class="ccs-page">

<div class="ccs-header">
	<h1>Incident History &mdash; By Personnel</h1>
	<div class="sub">Search incidents by reporting personnel &mdash; Line 3</div>
</div>

<table cellspacing="0" cellpadding="0" class='stat-toolbar'>
<tr>
	<td style="padding:8px 14px;vertical-align:middle;border:none">
<form action='td_history.php' method='post' style="display:flex;align-items:center;gap:10px;">
<label for="reported_by">Find by Personnel</label>
<div style='position:relative;width:220px;'>
	<input type="text" autocomplete='off' name='reported_by' id='reported_by' style='width:220px;' />
	<div id='reported_by_suggestions' style='display:none;position:absolute;top:30px;left:0;width:100%;background:#FFFFFF;border:1px solid #D8D2C2;border-radius:5px;max-height:180px;overflow-y:auto;z-index:10;color:#1A2238;'></div>
</div>
<input type=submit value='Retrieve' />
</form>
	</td>
</tr>
</table>

<script language='javascript'>
var reportedBySearchTimer=null;

document.getElementById('reported_by').addEventListener('keyup', function(e){
	// Ignore navigation/control keys so we don't fire a search on them
	if(['ArrowUp','ArrowDown','Enter','Escape','Tab'].indexOf(e.key)!==-1) return;

	var term=this.value;
	clearTimeout(reportedBySearchTimer);

	if(term.length<2){
		document.getElementById('reported_by_suggestions').style.display='none';
		return;
	}

	reportedBySearchTimer=setTimeout(function(){
		fetch("processing.php?searchReportedBy="+encodeURIComponent(term))
			.then(function(response){ return response.text(); })
			.then(reportedBySearchResults)
			.catch(function(err){ console.error("reported_by search failed:", err); });
	},250);
});

function reportedBySearchResults(ajaxHTML){
	var box=document.getElementById('reported_by_suggestions');

	if(ajaxHTML=="No data available"){
		box.style.display='none';
		box.innerHTML='';
		return;
	}

	var names=ajaxHTML.split("==>");
	var count=(names.length)*1-1; // trailing "==>" leaves one empty entry
	var html='';

	for(var n=0;n<count;n++){
		html+="<div class='reported_by_option' style='padding:5px 8px;cursor:pointer;color:#1A2238;' "
			+"onmouseover=\"this.style.background='#F1EEE3';\" onmouseout=\"this.style.background='';\" "
			+"onclick=\"document.getElementById('reported_by').value='"+names[n].replace(/'/g,"\\'")+"';"
			+"document.getElementById('reported_by_suggestions').style.display='none';\">"
			+names[n]+"</div>";
	}

	box.innerHTML=html;
	box.style.display=(count>0)?'block':'none';
}

// Hide the suggestion list when clicking elsewhere on the page
document.addEventListener('click', function(e){
	if(e.target.id!=='reported_by' && !e.target.classList.contains('reported_by_option')){
		document.getElementById('reported_by_suggestions').style.display='none';
	}
});
</script>

<div class="ccs-panel">
<div class="ccs-panel-head"><h3>Reported by: <?php echo $hasSearch ? htmlspecialchars($reportedBy) : '<span style="opacity:.55;font-weight:normal;">(no personnel selected)</span>'; ?></h3></div>
<?php if($hasSearch && count($tdVariants) > 1){ ?>
<div style="padding:8px 16px;font-size:12px;color:#5b5749;background:#FBFAF6;border-bottom:1px solid #E6E1D3;">
	Matching <?php echo count($tdVariants); ?> name spellings as one person:
	<?php
	$vEsc=array();
	foreach($tdVariants as $v){ $vEsc[]="<b>".htmlspecialchars($v)."</b>"; }
	echo implode(" &middot; ",$vEsc);
	?>
</div>
<?php } ?>
<div class="ccs-panel-body">
<?php if(!$hasSearch){ ?>
<div style="padding:14px 16px;margin-bottom:12px;border:1px solid #D8D2C2;border-radius:5px;background:#FBFAF6;color:#5b5749;font-size:13px;">
	Search for a member of personnel above to load their incident history.
</div>
<?php } ?>
<table class="table table-striped table-bordered bootstrap-datatable datatable2" width="100%" id='add_form' name='add_form' >
	<thead>
	<tr>
	<th>Index No</th>
	<th>Incident Date</th>
	<th>Type of Problem</th>
	<th>Incident Number</th>
	<th>Description</th>
	</tr>
	</thead>
	<tbody>
<?php
// Problem-type lookup loaded once instead of a query per row.
$typeMap=array();
$tmRs=$db->query("select equipment_code, equipment_name from equipment_type");
if($tmRs){ while($tm=$tmRs->fetch_assoc()){ $typeMap[$tm['equipment_code']]=$tm['equipment_name']; } }

// ---- Chart aggregates, built in the same pass as the table rows ----
$tdMonthly=array();   // ["YYYY-MM"] => count
$tdTypes=array();     // [problem type] => count
$tdTiming=array();    // [weekday 0=Mon..6=Sun][band 0..3] => count
for($d=0;$d<7;$d++){ $tdTiming[$d]=array(0,0,0,0); }
$tdTotal=0;
$termCounts=array();  // [token] => number of incidents it appears in

// Previously this ran a query for "XXXX" when nothing was searched, purely so
// it would match nothing. Skip the query entirely instead and show an honest
// empty state — a report printed from a blank search is worse than no report.
if($hasSearch){

	$sql="select * from incident_description inner join incident_report on incident_report.id=incident_description.incident_id where ".$whereNames." order by incident_date desc";
	$rs=$db->query($sql);
	$nm=$rs->num_rows;

	for($i=0;$i<$nm;$i++){
		$row=$rs->fetch_assoc();

		// Recorded type only — nothing is inferred onto a named person's
		// record. Rows with no recorded type show as "Unclassified", which
		// is honest and keeps the data gap visible.
		$ptype = isset($typeMap[$row['incident_type']]) ? $typeMap[$row['incident_type']] : 'Unclassified';

		$ts=strtotime($row['incident_date']);
		$mo=date("Y-m",$ts);
		if(!isset($tdMonthly[$mo])) $tdMonthly[$mo]=0;
		$tdMonthly[$mo]++;

		if(!isset($tdTypes[$ptype])) $tdTypes[$ptype]=0;
		$tdTypes[$ptype]++;

		$dow=(int)date("N",$ts)-1;         // 0=Mon
		$h=(int)date("G",$ts);
		if($h>=5 && $h<9) $band=0;         // AM peak
		else if($h>=9 && $h<16) $band=1;   // midday
		else if($h>=16 && $h<20) $band=2;  // PM peak
		else $band=3;                      // evening / night
		$tdTiming[$dow][$band]++;

		// Document frequency: count a term once per incident it appears in,
		// so one verbose description can't dominate the ranking.
		foreach(array_unique(ccsTokenize($row['description'])) as $t){
			if(!isset($termCounts[$t])) $termCounts[$t]=0;
			$termCounts[$t]++;
		}

		$tdTotal++;
?>	
	<tr>
		<td><?php echo $row['index_no']; ?></td>
		<td><?php echo "<span>".date("Y-m-d",strtotime($row['incident_date']))."</span>"; ?></td>
		<td><?php echo ($ptype==='Unclassified') ? "<span style='opacity:.55;'>Unclassified</span>" : htmlspecialchars($ptype); ?></td>
		<td><a href='#' class='two' onclick='openSlidePanel("edit_ccdr.php?ir=<?php echo  $row['id']; ?>&embed=1","Incident - <?php echo htmlspecialchars($row['incident_no']); ?>")'><?php echo $row['incident_no']; ?></a></td>
		<td><?php echo $row['description']; ?></td>
	</tr>
<?php
	}
}
// No placeholder row here on purpose: DataTables counts <td> elements per
// row and a colspan cell does not satisfy it, so a one-cell "no results"
// row triggers its "Requested unknown parameter" warning — which DataTables
// raises as a JS alert on page load. An empty <tbody> is correct; DataTables
// renders its own "No data available in table" message, and the guidance
// notice above the table covers the first-visit case.
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
     flattened to images and injected into the TableTools print window. -->
<div id="ccs-print-charts" style="display:none;">
	<canvas id="tdVolume" width="340" height="160"></canvas>
	<canvas id="tdTypes"  width="340" height="230"></canvas>
	<canvas id="tdTiming" width="340" height="180"></canvas>
	<canvas id="tdTerms"  width="340" height="180"></canvas>
</div>

<script>
// Aggregates from the same query as the table above.
var tdPersonName = <?php echo json_encode($reportedBy); ?>;
var tdHasSearch  = <?php echo $hasSearch ? 'true' : 'false'; ?>;
var tdMonthly    = <?php echo json_encode($tdMonthly, JSON_FORCE_OBJECT); ?>;
var tdTypeCounts = <?php echo json_encode($tdTypes, JSON_FORCE_OBJECT); ?>;
var tdTiming     = <?php echo json_encode($tdTiming); ?>;
var tdTotal      = <?php echo (int)$tdTotal; ?>;
var tdTerms      = <?php echo json_encode($topTerms); ?>;
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
<script>
// DataTables' default error mode is a JS alert box, so any column-count
// mismatch surfaces to staff as what looks like a system error. Route
// warnings to the console instead — developers still see them, users don't.
// Must run before the table is initialised (custom.min.js / additional.js).
if(window.jQuery && $.fn.dataTable){
	$.fn.dataTable.ext.errMode = function(settings, helpPage, message){ console.warn('DataTables:', message); };
}
</script>
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
     additional.js — those two auto-init .datatable2 and its
     TableTools print button, so the button only exists to hook
     into once they've run.
     ============================================================ -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
$(function(){

	var textInk  = getComputedStyle(document.documentElement).getPropertyValue('--text-primary').trim() || '#111';
	var mutedInk = getComputedStyle(document.documentElement).getPropertyValue('--text-secondary').trim() || '#555';
	var gridInk  = 'rgba(137,135,129,0.20)';
	var mainColor = '#2a78d6', otherColor = '#9c9a92';
	var TOP_TYPES = 5;

	function escHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

	function blankCanvas(id, title, msg){
		var cv = document.getElementById(id);
		var ctx = cv.getContext('2d');
		ctx.clearRect(0,0,cv.width,cv.height);
		ctx.textBaseline='middle'; ctx.textAlign='left';
		ctx.font='11px Arial, sans-serif'; ctx.fillStyle=textInk; ctx.fillText(title, 0, 9);
		ctx.font='10px Arial, sans-serif'; ctx.fillStyle=mutedInk; ctx.fillText(msg, 0, 34);
	}

	var who = tdHasSearch ? tdPersonName : '';

	// ============ Chart 1: monthly reporting volume ============
	(function drawVolume(){
		var months = Object.keys(tdMonthly).sort();
		if(!months.length){ blankCanvas('tdVolume','Monthly volume','No incidents to chart.'); return; }
		var shown = months.slice(-24);   // no date filter on this page — cap the chart

		new Chart(document.getElementById('tdVolume'), {
			type: 'bar',
			data: {
				labels: shown.map(function(m){ return m.slice(2); }),
				datasets: [{ data: shown.map(function(m){ return tdMonthly[m]; }), backgroundColor: mainColor, borderRadius: 3 }]
			},
			options: {
				responsive: false, animation: false,
				plugins: {
					title: { display: true, text: who + ' \u2014 incidents reported by month' + (months.length > 24 ? ' (last 24)' : ''), color: textInk, font: { size: 11, weight: 'normal' }, padding: { bottom: 6 } },
					legend: { display: false }
				},
				scales: {
					x: { ticks: { color: mutedInk, font: { size: 9 }, maxRotation: 45 }, grid: { display: false } },
					y: { ticks: { color: mutedInk, precision: 0, font: { size: 10 } }, grid: { color: gridInk } }
				}
			}
		});
	})();

	// ============ Chart 2: problem type mix (pie, recorded types only) ============
	// Recorded values only — nothing inferred. "Unclassified" is always shown
	// last and in grey so the data gap is visible rather than hidden.
	(function drawTypes(){
		var ranked = Object.keys(tdTypeCounts).sort(function(a,b){
			if(a === 'Unclassified') return 1;
			if(b === 'Unclassified') return -1;
			return tdTypeCounts[b]-tdTypeCounts[a];
		});
		if(!ranked.length){ blankCanvas('tdTypes','Problem type mix','No incidents to chart.'); return; }

		var top = ranked.slice(0, 6);
		var tail = ranked.slice(6);
		var labels = top.slice();
		var data = top.map(function(t){ return tdTypeCounts[t]; });
		if(tail.length){
			labels.push('Others (' + tail.length + ')');
			data.push(tail.reduce(function(s,t){ return s+tdTypeCounts[t]; }, 0));
		}
		var palette = ['#2a78d6','#eb6834','#1baf7a','#eda100','#e87ba4','#4a3aa7'];
		var colors = labels.map(function(l,i){
			return (l === 'Unclassified' || l.indexOf('Others (') === 0) ? otherColor : palette[i % palette.length];
		});

		new Chart(document.getElementById('tdTypes'), {
			type: 'pie',
			data: { labels: labels, datasets: [{ data: data, backgroundColor: colors }] },
			options: {
				responsive: false, animation: false,
				plugins: {
					title: { display: true, text: who + ' \u2014 problem types reported', color: textInk, font: { size: 11, weight: 'normal' }, padding: { bottom: 6 } },
					legend: {
						// Bottom, not right: category names like "Unloading of
						// Passengers" overflow a side legend on a narrow canvas
						// and get clipped. A bottom legend wraps across the full
						// width instead.
						position: 'bottom',
						labels: {
							boxWidth: 9, padding: 6, font: { size: 9 }, color: mutedInk,
							// Counts and percentages in the legend, because with one
							// dominant type the minority slices are too thin to label
							// on the pie itself — and those are the ones worth seeing.
							generateLabels: function(chart){
								var d = chart.data.datasets[0].data;
								var total = d.reduce(function(a,b){ return a+b; }, 0);
								return chart.data.labels.map(function(label,i){
									var pct = total ? Math.round(d[i]/total*100) : 0;
									return { text: label + ' \u2014 ' + d[i] + ' (' + pct + '%)',
									         fillStyle: chart.data.datasets[0].backgroundColor[i], index: i };
								});
							}
						}
					},
					tooltip: { callbacks: { label: function(c){ return c.label + ': ' + c.parsed; } } }
				}
			}
		});
	})();

	// ============ Chart 3: weekday x time-band heatmap ============
	// Hand-drawn on a raw canvas so it flattens to an image for print like
	// the two Chart.js canvases.
	(function drawTiming(){
		var cv = document.getElementById('tdTiming');
		var ctx = cv.getContext('2d');
		var W = cv.width, H = cv.height;
		ctx.clearRect(0,0,W,H);
		ctx.textBaseline = 'middle';

		ctx.font='11px Arial, sans-serif'; ctx.fillStyle=textInk; ctx.textAlign='left';
		ctx.fillText(who + ' \u2014 when reported incidents occur', 0, 9);

		if(!tdTotal){ ctx.font='10px Arial, sans-serif'; ctx.fillStyle=mutedInk; ctx.fillText('No incidents to chart.', 0, 34); return; }

		var days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
		var bands = ['AM peak','Midday','PM peak','Evening'];
		var padL = 46, padT = 34, padR = 8, padB = 8;
		var gridW = W - padL - padR, gridH = H - padT - padB;
		var cellW = gridW / bands.length, cellH = gridH / days.length;

		var maxV = 0;
		tdTiming.forEach(function(r){ r.forEach(function(v){ maxV = Math.max(maxV, v); }); });
		if(maxV === 0) maxV = 1;

		ctx.font='10px Arial, sans-serif'; ctx.fillStyle=mutedInk; ctx.textAlign='center';
		bands.forEach(function(b,ci){ ctx.fillText(b, padL + ci*cellW + cellW/2, padT - 10); });

		days.forEach(function(d,ri){
			var y = padT + ri*cellH;
			ctx.font='10px Arial, sans-serif'; ctx.fillStyle=mutedInk; ctx.textAlign='right';
			ctx.fillText(d, padL - 6, y + cellH/2);
			bands.forEach(function(b,ci){
				var v = tdTiming[ri][ci];
				var x = padL + ci*cellW;
				var t = v === 0 ? 0.04 : 0.12 + (v/maxV)*0.82;
				ctx.fillStyle = 'rgba(42,120,214,'+t.toFixed(3)+')';
				ctx.fillRect(x+1, y+1, cellW-2, cellH-2);
				if(v > 0){
					ctx.fillStyle = (v/maxV > 0.55) ? '#fff' : mutedInk;
					ctx.font='9px Arial, sans-serif'; ctx.textAlign='center';
					ctx.fillText(String(v), x + cellW/2, y + cellH/2);
				}
			});
		});
	})();

	// ============ Chart 4: recurring description terms ============
	// Same treatment as problem_history: the descriptions are mined for
	// frequently-recurring words rather than used to infer a category. It
	// surfaces what this person's reports actually mention — including
	// signalling or unloading terms that the recorded type may not capture —
	// without asserting a classification onto anyone's record.
	if(tdTerms.length){
		new Chart(document.getElementById('tdTerms'), {
			type: 'bar',
			data: {
				labels: tdTerms.map(function(p){ return p[0]; }),
				datasets: [{ data: tdTerms.map(function(p){ return p[1]; }), backgroundColor: '#1baf7a', borderRadius: 3, categoryPercentage: 0.6, barPercentage: 0.9 }]
			},
			options: {
				indexAxis: 'y', responsive: false, animation: false, layout: { padding: { right: 22 } },
				plugins: {
					title: { display: true, text: who + ' \u2014 recurring words in descriptions', color: textInk, font: { size: 11, weight: 'normal' }, padding: { bottom: 8 } },
					legend: { display: false },
					tooltip: { callbacks: { label: function(c){ return 'appears in ' + c.parsed.x + ' incidents'; } } }
				},
				scales: {
					x: { ticks: { color: mutedInk, precision: 0, font: { size: 10 } }, grid: { color: gridInk } },
					y: { ticks: { color: textInk, font: { size: 11 } }, grid: { display: false } }
				}
			},
			plugins: [{
				id: 'tdTermLabels',
				afterDatasetsDraw: function(chart){
					var ctx = chart.ctx, meta = chart.getDatasetMeta(0);
					ctx.save(); ctx.font='11px Arial, sans-serif'; ctx.fillStyle=textInk;
					ctx.textBaseline='middle'; ctx.textAlign='left';
					meta.data.forEach(function(bar,i){ ctx.fillText(chart.data.datasets[0].data[i], bar.x + 6, bar.y); });
					ctx.restore();
				}
			}]
		});
	}
	else{
		blankCanvas('tdTerms', 'Recurring words in descriptions', 'Not enough description data to rank recurring terms.');
	}

	// ============ Intercept the TableTools print button ============
	var printBtn = $('#add_form_wrapper').find('.DTTT_button_print, .buttons-print');
	if(printBtn.length){
		printBtn.off('click').on('click', function(e){
			e.preventDefault();
			e.stopImmediatePropagation();
			if(!tdHasSearch || !tdTotal){
				alert('Search for a member of personnel first — there is nothing to print yet.');
				return;
			}
			tdPrintWithCharts();
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
	// EXISTING instance rather than re-initialising.
	function tdFullTableHtml(){
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

	function tdPrintWithCharts(){
		var imgVolume = document.getElementById('tdVolume').toDataURL('image/png');
		var imgTypes  = document.getElementById('tdTypes').toDataURL('image/png');
		var imgTiming = document.getElementById('tdTiming').toDataURL('image/png');
		var imgTerms  = document.getElementById('tdTerms').toDataURL('image/png');
		var captured  = tdFullTableHtml();
		var tableHtml = captured.html;
		var rowCount  = captured.count;
		var name      = escHtml(tdPersonName);

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
				// Percentage widths, not pixels: 48%+2% twice fills the row exactly,
				// so the two-up layout holds whatever the canvas pixel size is.
				// (Fixed pixel widths landed ~2px over the A4 content width and
				// every chart wrapped onto its own row.)
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
				'<h1 class="rpt-title">Incident History by Reporting Personnel</h1>' +
				'<p class="rpt-subject">' + name + '</p>' +
			'</div>' +
			'<div class="rpt-meta">' +
				'<span><b>Personnel:</b> ' + name + '</span>' +
				'<span><b>Report period:</b> All recorded dates</span>' +
				'<span><b>Records:</b> ' + rowCount + '</span>' +
				'<span><b>Generated:</b> <?php echo date("d M Y, H:i"); ?></span>' +
			'</div>' +
			<?php if($hasSearch && count($tdVariants) > 1){
				$vTxt=array();
				foreach($tdVariants as $v){ $vTxt[]=htmlspecialchars($v, ENT_QUOTES); }
				echo "'<p class=\"note\">Name spellings combined as one person: ".addslashes(implode(" &middot; ",$vTxt))."</p>' +";
			} ?>

			'<h2 class="sec">Summary</h2>' +
			'<div class="charts">' +
				'<div class="chart"><img src="' + imgVolume + '">' +
					'<div class="cap">Figure 1 &mdash; Incidents reported by month</div></div>' +
				'<div class="chart"><img src="' + imgTypes + '">' +
					'<div class="cap">Figure 2 &mdash; Problem types reported</div></div>' +
				'<div class="chart"><img src="' + imgTiming + '">' +
					'<div class="cap">Figure 3 &mdash; When reported incidents occur</div></div>' +
				'<div class="chart"><img src="' + imgTerms + '">' +
					'<div class="cap">Figure 4 &mdash; Recurring words in descriptions</div></div>' +
				'<p class="note">Counts reflect incidents this person filed. They describe reporting coverage and shift pattern, not individual performance &mdash; volume depends heavily on roster, assigned area and shift. Figure 2 shows recorded problem types only; Figure 4 counts words appearing in the descriptions themselves and does not assign a category.</p>' +
			'</div>' +

			'<h2 class="sec">Incident Records</h2>' +
			'<div class="tbl-head">' +
				'<h3>' + name + ' &mdash; incident log</h3>' +
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
<?php
function getProblemType($db,$type){
	$sql="select * from equipment_type where equipment_code='".$type."'";
	$rs=$db->query($sql);

	$nm=$rs->num_rows;
	$row=$rs->fetch_assoc();

	$problem=$row['equipment_name'];
	return $problem;
}

// ============================================================
// Description text mining (shared with problem_history.php).
//
// Only the tokenizer is used here: descriptions are mined for recurring
// words to build Figure 4. No classifier — problem types on this page come
// from recorded values only, so nothing is inferred onto an individual's
// record. Stopwords include rail-report boilerplate (hrs, nb, sb); extend
// the list if other filler shows up in the term chart.
// ============================================================

function ccsTokenize($text){
	if($text===null) return array();
	$text = strtolower($text);
	$text = preg_replace('/[^a-z0-9\s]/',' ',$text);
	$tokens = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
	static $stop = array('the'=>1,'a'=>1,'an'=>1,'and'=>1,'or'=>1,'of'=>1,'to'=>1,'in'=>1,'on'=>1,'at'=>1,
		'is'=>1,'was'=>1,'were'=>1,'for'=>1,'with'=>1,'not'=>1,'failure'=>1,'train'=>1,'trains'=>1,'from'=>1,'by'=>1,'due'=>1,'that'=>1,'this'=>1,
		'as'=>1,'be'=>1,'been'=>1,'are'=>1,'it'=>1,'its'=>1,'has'=>1,'had'=>1,'have'=>1,'per'=>1,
		'am'=>1,'pm'=>1,'hrs'=>1,'nb'=>1,'sb'=>1);
	$out=array();
	foreach($tokens as $t){
		if(strlen($t) >= 3 && !isset($stop[$t]) && !ctype_digit($t)) $out[]=$t;
	}
	return $out;
}

// ============================================================
// Personnel name matching.
//
// The same person was entered inconsistently over the years — "A.DOMINGO",
// "Domingo, A." and "TD A. Domingo" are one individual, so a literal LIKE
// search finds only whichever spelling was typed and silently drops the rest
// of that person's record.
//
// ccsNameParts() reduces a raw value to two sorted sets: multi-letter name
// tokens (surnames / given names) and single-letter initials, after stripping
// punctuation and rank prefixes. Token ORDER is discarded, so "Domingo, A."
// and "A. Domingo" reduce identically.
//
// Rank/title prefixes are stripped; generational suffixes (JR, SR, III) are
// deliberately NOT stripped, since those usually distinguish two real people.
// ============================================================
function ccsNameParts($raw){
	$s = strtoupper($raw);
	$s = preg_replace('/\b(TD|ENGR|ENG|MR|MRS|MS|MISS|SIR|MAAM|SUPV|SUP|OIC|ATTY|DR)\b/', ' ', $s);
	$s = preg_replace('/[^A-Z\s]/', ' ', $s);   // punctuation and digits out
	$toks = preg_split('/\s+/', $s, -1, PREG_SPLIT_NO_EMPTY);

	$names=array(); $inits=array();
	foreach($toks as $t){
		if(strlen($t) === 1) $inits[$t]=true; else $names[$t]=true;
	}
	$names=array_keys($names); sort($names);
	$inits=array_keys($inits); sort($inits);
	return array($names,$inits);
}

function ccsNamesMatch($a,$b){
	list($an,$ai) = ccsNameParts($a);
	list($bn,$bi) = ccsNameParts($b);
	if(!count($an) || !count($bn)) return false;
	if($an !== $bn) return false;                  // name tokens must match exactly
	// If either side carries no initials ("Solomon" vs "SOLOMON, R"), accept —
	// otherwise they must share at least one.
	if(!count($ai) || !count($bi)) return true;
	return count(array_intersect($ai,$bi)) > 0;
}

?>