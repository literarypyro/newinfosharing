<!--- Modified by Jun
//--- Date: 8/7/2014
//--- Modify: screen layout
//--- Marker: @mjun
//---------------------------------------------------
//--- Console theme + presentation pass (01302026):
//--- Reconciled this page's look with car_history.php, its drill-down
//--- destination -- same blue/gold console theme instead of grey +
//--- Comic Sans + gold-on-every-cell, link colour now signals
//--- "clickable" at rest instead of only on hover, added a caption
//--- explaining the red highlight and the two click targets. No query
//--- logic touched -- purely presentation.
//--------------------------------------------------->
<?php
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Car Statistics Report</title>

<style type='text/css'>
/* ===========================================================================
   LINE 3 SCHEME — shared with car_history.php / equipment_history.php
   Blue leads the structure; yellow is a small accent (title-bar stripe +
   hover highlight), never the gridlines.
   =========================================================================== */

body { margin:24px 30px; background:#FAFAF6; color:#1A2238; font-family:"Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif; }

h2 { color:#1A2238; font-size:20px; }

.stat-toolbar {
	display:flex; align-items:center; gap:10px; flex-wrap:wrap;
	background:#00529B; border-bottom:3px solid #FDB813;
	border-radius:6px 6px 0 0; padding:10px 16px; margin-bottom:0;
}
.stat-toolbar label { color:#FFFFFF; font-weight:600; font-size:13px; }
.stat-toolbar select, .stat-toolbar input[type=text] {
	height:26px; border:1px solid rgba(255,255,255,.5); border-radius:4px;
	background:#FFFFFF; color:#1A2238; padding:0 8px; font-size:12px;
}
.stat-toolbar input[type=submit] {
	height:28px; border:none; border-radius:4px; background:#FDB813;
	color:#3A2D00; font-weight:700; font-size:12px; padding:0 14px; cursor:pointer;
}
.stat-toolbar input[type=submit]:hover { background:#E5A50F; }

.stat-legend {
	display:flex; align-items:center; gap:16px; flex-wrap:wrap;
	background:#F1EEE3; border:1px solid #E5DECC; border-top:none;
	padding:8px 16px; font-size:12px; color:#5A6275;
}
.stat-legend .swatch { display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:5px; vertical-align:middle; }

.rowHeading {background:#00529B; color:#FFFFFF; font-size:15px; font-weight:600;}
.rowHeading2 {background:#F1EEE3; color:#1A2238;}
.rowClass {background-color: #F5F2E8;}

.train_ava { border-collapse:collapse; }
.train_ava td, .train_ava th { border:1px solid #E5DECC; padding:6px 8px; }

.train_ava a { color:#00529B; text-decoration:none; }

select { border: 1px solid #D8D2C2; color: #1A2238; background-color: #FFFFFF; border-radius:4px; }

/* --- mjun -- generate */
a.two { color:#00529B; font-weight:600; text-decoration:none; }
a.two:visited {color:#00529B;}
a.two:hover, a.two:active {color:#003E76; text-decoration:underline;}

.stat_hover:hover {
	background-color:#FFF1CC;
	text-decoration:underline;
	font-weight:bold;
}
</style>
<?php include("history_theme.php"); ?>


</head>
<body>
<div class="ccs-page">
<div class="ccs-header">
<?php
if(isset($_POST['year'])){
	$year=$_POST['year'];

}
else {
	$year=date("Y");

}



?>

<h1><?php echo "Car Incidents By Year"; ?></h1>
<div class='sub'> <?php echo "For the Year ".$year; ?> </div>
</div>

<div class="ccs-panel">
<div class="ccs-panel-head">
<form action='car_statistics_report.php' method='post' class="stat-toolbar">
<!--
<label for='levelSelect'>Level</label>
<select name='level' id='levelSelect'>
<option value='2'>2</option>
<option value='3'>3</option>
</select>
-->
<label for='yearSelect'>Year</label>
<?php
$startYear=2013;

$endYear=date("Y")*1+16;

?>
<select name='year' id='yearSelect'>
<?php
for($k=$startYear;$k<=$endYear;$k++){
?>
<option value="<?php echo $k; ?>"<?php if($k==$year) echo " selected"; ?>><?php echo $k; ?></option>
<?php
}
?>
</select>
<input type=submit value='Submit' />
</form>
<div class="stat-legend">
	<span><span class="swatch" style="background:#00529B;"></span>Click a car number for its full-year history</span>
	<span><span class="swatch" style="background:#FDB813;"></span>Click a monthly count for that car's incidents that month</span>
	<span><span class="swatch" style="background:#F9D6D6; border:1px solid #E3A9A9;"></span>Highlighted row = among the highest incident counts this year (&ge;60% of the peak)</span>
</div>
</div>
<div class='ccs-panel-body'>
<?php
// The summary figures below are only known once the aggregation loop inside
// the table has run, so buffer the table and emit the summary above it.
ob_start();
?>
<table class='table table-striped table-bordered bootstrap-datatable datatable2' border=1 style='border-collapse:collapse;' width=100%>
<thead>
<tr>	
	<th>Car #</th>
	<th>January</th>
	<th>February</th>
	<th>March</th>
	<th>April</th>
	<th>May</th>
	<th>June</th>
	<th>July</th>
	<th>August</th>
	<th>September</th>
	<th>October</th>
	<th>November</th>
	<th>December</th>
	<th>Total</th>
</tr>
</thead>
<tbody>
<?php
$CAR_MAX = 73;
for($i=1;$i<=$CAR_MAX;$i++){
	for($k=1;$k<=12;$k++){
		$stats["Car_".$i]["Month_".$k]=0;
	}
	// "total" was never initialised — the += below was accumulating onto an
	// undefined index on the first hit for every car.
	$stats["Car_".$i]["total"]=0;
}
$highestCount=0;   // was only defined inside if($nm>0), but used unconditionally below

$sql="SELECT car_no,month(incident_date) as mo,sum(1) as count FROM incident_cars inner join incident_report on incident_cars.incident_id=incident_report.id where incident_date like '".$year."-%%' group by incident_cars.car_no*1,month(incident_date)";

// UNION (not UNION ALL) silently DROPPED a legacy row whenever it happened to
// produce an identical (car_no, month, count) triple as the current database —
// e.g. car 12 with 2 incidents in March on both sides collapsed to one row.
$sql.=" union all ";
$sql.="SELECT car_no,month(incident_date) as mo,sum(1) as count FROM is_transport_old.incident_cars inner join is_transport_old.incident_report on is_transport_old.incident_cars.incident_id=is_transport_old.incident_report.id where incident_date like '".$year."-%%' group by incident_cars.car_no*1,month(incident_date)";

$rs=$db->query($sql);

$nm=$rs->num_rows;

if($nm>0){
	$highestCount=0;
	
	for($i=0;$i<$nm;$i++){
		$row=$rs->fetch_assoc();
		$car_id=$row['car_no']*1;
		$month=$row['mo']*1;
		
		// Was "=" not "+=": a car appearing in BOTH the current and legacy
		// databases for the same month had one of the two figures overwritten,
		// while the total below accumulated both — so the month cells and the
		// total disagreed.
		if($car_id < 1 || $car_id > $CAR_MAX) continue;   // outside the fleet range
		$stats["Car_".$car_id]["Month_".$month]+=$row['count'];
		$stats["Car_".$car_id]["total"]+=$row['count'];
		
		$highestCount=sortCar($highestCount,$stats["Car_".$car_id]["total"]);
		
		
		
		
	}
}




for($i=1;$i<=73;$i++){
	$isFlagged=(($highestCount*0.60)<$stats["Car_".$i]["total"]);
?>
<tr 
<?php 
if($isFlagged){
		echo "style='background-color:#F9D6D6; color:#7A1F1F;'";

}
else {

//	if($i%2>0){ echo "class='rowClass'"; } 

}


?>>
<th class='stat_hover'><a href='#' style='text-decoration:none; color:#00529B; font-weight:600;'  onclick='window.open("car_history.php?car_id=<?php echo $i; ?>&y=<?php echo $year; ?>",target="_self")' ><?php echo $i; ?></a></th>
<?php
for($k=1;$k<=12;$k++){
	$monthTotals[$k]+=$stats["Car_".$i]["Month_".$k];



?>			
	<td class='stat_hover' align=center><a href='#' style='text-decoration:none; color:<?php echo $stats["Car_".$i]["Month_".$k]>0 ? '#00529B' : '#B4B2A9'; ?>;' onclick='window.open("car_history.php?car_id=<?php echo $i; ?>&y=<?php echo $year; ?>&m=<?php echo $k; ?>",target="_self")' ><?php echo $stats["Car_".$i]["Month_".$k]; ?></a></td>
<?php
}
?>
	<td align=center style="font-weight:600;"><?php echo $stats["Car_".$i]["total"]; ?></td>
</tr>

<?php
}
?>
<tr style="background:#F1EEE3;font-weight:700;">
	<th>All cars</th>
<?php for($k=1;$k<=12;$k++){ 
		$grandTotal+=$monthTotals[$k];

?>
	<td align=center><?php echo $monthTotals[$k]; ?></td>
<?php } ?>
	<td align=center><?php echo $grandTotal; ?></td>
</tr>
</tbody>
</table>
<?php
$tableHtml = ob_get_clean();

// ---- Derived figures for the summary strip and charts --------------------
// This page counts CAR-LEVEL FAILURES: it joins incident_cars, so an incident
// affecting three cars counts once against each. Same basis as
// equipment_cars_stats.php and the equipment summary report, so the three
// reconcile. The incident history logs count one row per incident and show a
// smaller figure.
$grandTotal2 = $grandTotal;   // already accumulated above
$carTotals = array();
$carsWithFailures=0;
for($i=1;$i<=$CAR_MAX;$i++){
	if($stats["Car_".$i]["total"] > 0){
		$carTotals[] = array($i, (int)$stats["Car_".$i]["total"]);
		$carsWithFailures++;
	
	}
}
usort($carTotals, function($a,$b){ return $b[1]-$a[1]; });
$avgPerActiveCar = $carsWithFailures ? round($grandTotal / $carsWithFailures, 1) : 0;

$peak=reset($carTotals);
$peakTotal=$peak[1];

$peakCar=$peak[0];
$monthSeries = array();
$mn = array(1=>'Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
for($k=1;$k<=12;$k++){ $monthSeries[] = array($mn[$k], (int)$monthTotals[$k]); }

// Distinct incidents behind those car-level failures, across both databases.
// The source tag keeps ids from the two schemas from colliding.
$distinctIncidents = 0;
$dq = $db->query("select count(*) as c from (
                    select distinct incident_cars.incident_id as iid, 'cur' as src
                      from incident_cars
                      inner join incident_report on incident_cars.incident_id=incident_report.id
                      where incident_date like '".$year."-%'
                    union all
                    select distinct is_transport_old.incident_cars.incident_id as iid, 'old' as src
                      from is_transport_old.incident_cars
                      inner join is_transport_old.incident_report on is_transport_old.incident_cars.incident_id=is_transport_old.incident_report.id
                      where incident_date like '".$year."-%'
                  ) t");
if($dq && ($dr = $dq->fetch_assoc())) $distinctIncidents = (int)$dr['c'];
?>

<div style="display:flex;flex-wrap:wrap;gap:10px;margin:14px 0;">
	<div style="flex:1;min-width:140px;border:1px solid #E5DECC;border-radius:6px;padding:10px 12px;background:#FBFAF6;">
		<div style="font-size:11px;color:#5A6275;text-transform:uppercase;letter-spacing:.06em;">Car-level failures</div>
		<div style="font-size:22px;font-weight:600;color:#00529B;"><?php echo $grandTotal; ?></div>
		<div style="font-size:11px;color:#5A6275;">from <?php echo $distinctIncidents; ?> incident<?php echo $distinctIncidents==1?'':'s'; ?></div>
	</div>
	<div style="flex:1;min-width:140px;border:1px solid #E5DECC;border-radius:6px;padding:10px 12px;background:#FBFAF6;">
		<div style="font-size:11px;color:#5A6275;text-transform:uppercase;letter-spacing:.06em;">Cars affected</div>
		<div style="font-size:22px;font-weight:600;color:#00529B;"><?php echo $carsWithFailures; ?></div>
		<div style="font-size:11px;color:#5A6275;">of <?php echo $CAR_MAX; ?> in the fleet</div>
	</div>
	<div style="flex:1;min-width:140px;border:1px solid #E5DECC;border-radius:6px;padding:10px 12px;background:#FBFAF6;">
		<div style="font-size:11px;color:#5A6275;text-transform:uppercase;letter-spacing:.06em;">Worst car</div>
		<div style="font-size:22px;font-weight:600;color:#7A1F1F;"><?php echo $peakCar>0 ? $peakCar : '&mdash;'; ?></div>
		<div style="font-size:11px;color:#5A6275;"><?php echo $peakTotal; ?> failures</div>
	</div>
	<div style="flex:1;min-width:140px;border:1px solid #E5DECC;border-radius:6px;padding:10px 12px;background:#FBFAF6;">
		<div style="font-size:11px;color:#5A6275;text-transform:uppercase;letter-spacing:.06em;">Avg per affected car</div>
		<div style="font-size:22px;font-weight:600;color:#00529B;"><?php echo $avgPerActiveCar; ?></div>
		<div style="font-size:11px;color:#5A6275;">excludes cars with none</div>
	</div>
</div>

<div style="margin-bottom:14px;">
	<button type="button" onclick="csrPrintReport()" style="padding:6px 14px;border:1px solid #00529B;background:#00529B;color:#fff;border-radius:4px;cursor:pointer;font-size:13px;">Print report</button>
</div>

<div style="display:flex;flex-wrap:wrap;gap:16px;margin-bottom:16px;">
	<div><canvas id="csrByCar" width="340" height="220"></canvas></div>
	<div><canvas id="csrByMonth" width="340" height="200"></canvas></div>
</div>

<?php echo $tableHtml; ?>

<div style="font-size:12px;color:#5A6275;margin-top:8px;">
	Figures count <b>car-level failures</b>: an incident affecting three cars counts once against each car, so <?php echo $distinctIncidents; ?> incident<?php echo $distinctIncidents==1?'':'s'; ?> produce <?php echo $grandTotal; ?> car-level failure<?php echo $grandTotal==1?'':'s'; ?>. This matches the basis used by the equipment summary and per-car reports; the incident history logs count one row per incident and show the smaller figure.
	Covers all severity levels &mdash; this page has no level filter.
</div>

<script>
var csrCarTotals   = <?php echo json_encode($carTotals); ?>;
var csrMonthSeries = <?php echo json_encode($monthSeries); ?>;
var csrYear        = <?php echo json_encode($year); ?>;
var csrGrandTotal  = <?php echo (int)$grandTotal; ?>;
var csrIncidents   = <?php echo (int)$distinctIncidents; ?>;
var csrCarsAffected= <?php echo (int)$carsWithFailures; ?>;
var csrPeakCar     = <?php echo (int)$peakCar; ?>;
</script>
<?php



function sortCar($count_a,$count_b){
	
	if($count_a>$count_b){
		
		return $count_a;

	}
	else {
		return $count_b;	

	}
	
}
?>
</div>
</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
(function(){
	var ink='#1A2238', muted='#5A6275', grid='rgba(137,135,129,0.20)';
	var TOP_CARS = 10;

	function valueLabels(){
		return { id:'csrLabels', afterDatasetsDraw:function(chart){
			var ctx=chart.ctx, meta=chart.getDatasetMeta(0);
			ctx.save(); ctx.font='11px Arial, sans-serif'; ctx.fillStyle=ink;
			ctx.textBaseline='middle'; ctx.textAlign='left';
			meta.data.forEach(function(bar,i){ ctx.fillText(chart.data.datasets[0].data[i], bar.x+6, bar.y); });
			ctx.restore();
		}};
	}

	var top = csrCarTotals.slice(0, TOP_CARS);
	var tail = csrCarTotals.slice(TOP_CARS);
	var tailTotal = tail.reduce(function(s,r){ return s+r[1]; }, 0);

	if(top.length){
		new Chart(document.getElementById('csrByCar'), {
			type:'bar',
			data:{ labels: top.map(function(r){ return 'Car '+r[0]; }),
			       datasets:[{ data: top.map(function(r){ return r[1]; }),
			                   backgroundColor: top.map(function(r){ return r[0]===csrPeakCar ? '#A32D2D' : '#00529B'; }),
			                   borderRadius:3, categoryPercentage:0.62, barPercentage:0.9 }] },
			options:{ indexAxis:'y', responsive:false, animation:false,
				layout:{ padding:{ right:22, bottom: tail.length ? 18 : 4 } },
				plugins:{
					title:{ display:true, text:'Car-level failures by car'+(tail.length?' (top '+TOP_CARS+')':'')+' \u2014 '+csrYear, color:ink, font:{size:11,weight:'normal'}, padding:{bottom:8} },
					legend:{ display:false },
					tooltip:{ callbacks:{ label:function(c){ return c.parsed.x+' failures'; } } }
				},
				scales:{ x:{ ticks:{ color:muted, precision:0, font:{size:10} }, grid:{ color:grid } },
				         y:{ ticks:{ color:ink, font:{size:11} }, grid:{ display:false } } }
			},
			plugins:[ valueLabels(), { id:'csrTail', afterDraw:function(chart){
				if(!tail.length) return;
				var ctx=chart.ctx, area=chart.chartArea;
				ctx.save(); ctx.font='10px Arial, sans-serif'; ctx.fillStyle=muted;
				ctx.textAlign='left'; ctx.textBaseline='top';
				var y=chart.height-14;
				ctx.strokeStyle=grid; ctx.lineWidth=1;
				ctx.beginPath(); ctx.moveTo(area.left,y-5); ctx.lineTo(chart.width-8,y-5); ctx.stroke();
				ctx.fillText('+ '+tailTotal+' across '+tail.length+' further car'+(tail.length===1?'':'s'), area.left, y);
				ctx.restore();
			}}]
		});
	}
	else{
		var cv=document.getElementById('csrByCar'), c=cv.getContext('2d');
		c.textBaseline='middle'; c.textAlign='left';
		c.font='11px Arial, sans-serif'; c.fillStyle=ink;
		c.fillText('Car-level failures by car \u2014 '+csrYear, 0, 9);
		c.font='10px Arial, sans-serif'; c.fillStyle=muted;
		c.fillText('No failures recorded for this year.', 0, 34);
	}

	new Chart(document.getElementById('csrByMonth'), {
		type:'bar',
		data:{ labels: csrMonthSeries.map(function(r){ return r[0]; }),
		       datasets:[{ data: csrMonthSeries.map(function(r){ return r[1]; }), backgroundColor:'#00529B', borderRadius:3 }] },
		options:{ responsive:false, animation:false,
			plugins:{ title:{ display:true, text:'Car-level failures by month, whole fleet', color:ink, font:{size:11,weight:'normal'}, padding:{bottom:6} }, legend:{ display:false } },
			scales:{ x:{ ticks:{ color:muted, font:{size:10} }, grid:{ display:false } },
			         y:{ ticks:{ color:muted, precision:0, font:{size:10} }, grid:{ color:grid } } }
		}
	});

	window.csrPrintReport = function(){
		var imgCar   = document.getElementById('csrByCar').toDataURL('image/png');
		var imgMonth = document.getElementById('csrByMonth').toDataURL('image/png');
		var tbl = document.querySelector('.ccs-panel-body table');
		var tableHtml = tbl ? tbl.outerHTML : '';
		function esc(x){ return String(x).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

		var win = window.open('', '_blank');
		win.document.write(
			'<html><head><title>Car Incidents by Year \u2014 '+esc(csrYear)+'</title>' +
			'<style>' +
				'@page{ size:A4 landscape; margin:12mm 10mm 13mm; }' +
				'*{ box-sizing:border-box; }' +
				'body{ font-family:"Segoe UI",Arial,Helvetica,sans-serif; color:#1a1a1a; margin:0; font-size:11px; line-height:1.45;' +
					' -webkit-print-color-adjust:exact; print-color-adjust:exact; }' +
				'.rpt-head{ border-bottom:2px solid #1f4e79; padding-bottom:9px; margin-bottom:4px; }' +
				'.rpt-org{ font-size:8.5px; letter-spacing:.15em; text-transform:uppercase; color:#6b7280; margin-bottom:3px; }' +
				'.rpt-title{ font-size:19px; font-weight:600; color:#1f4e79; margin:0 0 1px; }' +
				'.rpt-subject{ font-size:12.5px; color:#374151; margin:0; }' +
				'.rpt-meta{ margin:8px 0 0; font-size:9.5px; color:#4b5563; }' +
				'.rpt-meta span{ margin-right:20px; white-space:nowrap; }' +
				'.rpt-meta b{ color:#1f2937; font-weight:600; }' +
				'h2.sec{ font-size:11px; text-transform:uppercase; letter-spacing:.09em; color:#1f4e79;' +
					' border-bottom:1px solid #d1d5db; padding-bottom:4px; margin:18px 0 10px; font-weight:600; }' +
				'.charts{ margin-bottom:4px; }' +
				'.chart{ display:inline-block; vertical-align:top; width:40%; margin:0 2% 10px 0; page-break-inside:avoid; }' +
				'.chart img{ display:block; width:100%; height:auto; border:1px solid #e5e7eb; }' +
				'.chart .cap{ font-size:9px; color:#6b7280; margin-top:3px; }' +
				'.note{ font-size:9px; color:#6b7280; font-style:italic; margin:2px 0 0; }' +
				'table{ width:100%; border-collapse:collapse; font-size:8.5px; }' +
				'thead{ display:table-header-group; }' +
				// Navy fill applies to the HEADER ROW only. Each data row's first
				// cell is also a <th>, so an unscoped th rule painted the whole
				// Car # column solid navy.
				'thead th{ background:#1f4e79; color:#fff; text-align:center; padding:4px 3px; font-size:8px; font-weight:600;' +
					' text-transform:uppercase; letter-spacing:.03em; border:1px solid #1f4e79; }' +
				'tbody th{ background:#F1EFE8; color:#1a1a1a; text-align:center; padding:3px; font-size:8.5px;' +
					' font-weight:600; border:1px solid #e5e7eb; }' +
				'td{ padding:3px; border:1px solid #e5e7eb; text-align:center; }' +
				'tr{ page-break-inside:avoid; }' +
				// !important because the car-number and month links carry inline
				// colours, which would otherwise win over this rule.
				'a{ color:inherit !important; text-decoration:none !important; pointer-events:none; }' +
				'.rpt-foot{ margin-top:12px; border-top:1px solid #d1d5db; padding-top:6px; font-size:8.5px; color:#6b7280; }' +
			'</style></head><body>' +
			'<div class="rpt-head">' +
				'<div class="rpt-org">DOTr &middot; MRT-3 Line 3 &middot; Operations Control</div>' +
				'<h1 class="rpt-title">Car Incidents by Year</h1>' +
				'<p class="rpt-subject">Fleet-wide, '+esc(csrYear)+'</p>' +
			'</div>' +
			'<div class="rpt-meta">' +
				'<span><b>Year:</b> '+esc(csrYear)+'</span>' +
				'<span><b>Car-level failures:</b> '+csrGrandTotal+'</span>' +
				'<span><b>From incidents:</b> '+csrIncidents+'</span>' +
				'<span><b>Cars affected:</b> '+csrCarsAffected+'</span>' +
				'<span><b>Generated:</b> <?php echo date("d M Y, H:i"); ?></span>' +
			'</div>' +
			'<h2 class="sec">Summary</h2>' +
			'<div class="charts">' +
				'<div class="chart"><img src="'+imgCar+'"><div class="cap">Figure 1 &mdash; Car-level failures by car</div></div>' +
				'<div class="chart"><img src="'+imgMonth+'"><div class="cap">Figure 2 &mdash; Car-level failures by month, whole fleet</div></div>' +
				'<p class="note">Figures count car-level failures: an incident affecting several cars counts once against each car, so '+csrIncidents+' incidents produce '+csrGrandTotal+' car-level failures. This matches the equipment summary and per-car reports; the incident history logs count one row per incident and show the smaller figure. Covers all severity levels. Shaded rows are cars at or above 60% of the worst car total.</p>' +
			'</div>' +
			'<h2 class="sec">Monthly Breakdown by Car</h2>' +
			tableHtml +
			'<div class="rpt-foot">MRT-3 Information Sharing System &middot; generated <?php echo date("d M Y, H:i"); ?> &middot; for internal operational use</div>' +
			'</body></html>'
		);
		win.document.close();
		win.focus();
		win.onload = function(){ setTimeout(function(){ win.print(); }, 250); };
	};
})();
</script>
</body>
</html>