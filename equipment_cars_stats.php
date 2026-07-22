<!--- Modified by Jun
//--- Date: 8/6/2014
//--- Modify: screen layout
//--- Marker: @mjun
//--------------------------------------------------->
<style type='text/css'>

.rowClass {background-color: #F3F3F3;}

/* color header */
.rowHeading {background-color: #cccccc;
			color:black
}
.train_ava td{
	border: 1px solid #FBCC2A;
	color: black;
	cellpadding: 5px
}

.train_ava th {
	border: 1px solid #FBCC2A;;
	cellpadding: 5px;	
	color: black
}

select { border: 1px solid rgb(185, 201, 254); color: black; background-color: #FFFACD; }

/* --- mjun -- generate */
a.two:visited {color:black;}
a.two:hover, a.two:active {font-size:120%; color:orange;}

</style>
<link rel="stylesheet" href="jquery-ui-themes-1.11.1/themes/smoothness/jquery-ui.css" />
<script src="jquery-ui-1.11.1/external/jquery/jquery.js"></script>
<script src="jquery-ui-1.11.1/jquery-ui.js"></script>
<script language='javascript' src='ajax.js'></script>

<!--
<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
-->

<script language='javascript'>

$(function() {
	/*
    $( "#search_date2" ).daterangepicker(
	{
        ranges: {
           'Today': [moment(), moment()],
           'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
           'Last 7 Days': [moment().subtract(6, 'days'), moment()],
           'Last 30 Days': [moment().subtract(29, 'days'), moment()],
           'This Month': [moment().startOf('month'), moment().endOf('month')],
           'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
           'This Year': [moment().startOf('year'), moment().endOf('year')],
           'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')]
		   
        }
    });    

	*/
    $( "#search_date" ).datepicker({
      changeMonth: true,
      changeYear: true,
      showAnim: "clip"
    });    
    $( "#search_date2" ).datepicker({
      changeMonth: true,
      changeYear: true,
      showAnim: "clip"
    });    
	
	
});
</script>
<?php
$equipt_id=$_GET['eq'];
$level=$_GET['level'];
//$start_date=$_GET['sd'];
//$end_date=$_GET['ed'];
$range=$_GET['range'];

if(isset($_GET['sd'])){
$year=date("Y",strtotime($_GET['sd']));

$start_date=date("Y-m-d",strtotime($_GET['sd']));

$init_start=$start_date;
$end_date=date("Y-m-d",strtotime($_GET['ed']));


if($_GET['range']=="daily"){
$end_date=$start_date;	
}
else if($_GET['range']=="weekly"){
$end_date=date("Y-m-d",strtotime($_GET['sd']."+1 week"));
	
}

else if($_GET['range']=="monthly"){
$end_date=date("Y-m-d",strtotime($_GET['sd']."+1 month"));
	
}
else if($_GET['range']=="yearly"){
$end_date=date("Y-m-d",strtotime($_GET['sd']."+365 days"));
	
}
else if($_GET['range']=="custom"){
$end_date=date("Y-m-d",strtotime($_GET['ed']));
	
}
}
else {
$start_date=date("Y")."-01-01";
$end_date=date("Y")."-12-31";
$year=date("Y");
// $init_start was only ever set inside the isset($_GET['sd']) branch above,
// but the car-list queries below use it unconditionally — on a default load
// it was empty, so the BETWEEN clause had no lower bound.
$init_start=$start_date;
}

?>


<?php
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");

$sql="select * from equipment where id='$equipt_id'";
$rs=$db->query($sql);
$row=$rs->fetch_assoc();
$ename=$row['equipment_name'];


// ---- Canonical car list -------------------------------------------------
// Built ONCE, grouped by car_no only. Previously this was built by a query
// grouped by (incident_date, car_no) — one row per car per date — and then
// overwritten by a second query whose date variables were never assigned.
// The render loop further down then read labels from a THIRD query while
// reading figures from this array by position, so the numbers landed against
// the wrong car. One list, one order, used for both.
//
// $range_start / $range_end are kept separate from $start_date / $end_date
// because the monthly aggregation loop reassigns those on every iteration.
$range_start = $init_start;
$range_end   = $end_date;

$car = array();
$car_count = array();

// Distinct incidents behind those car-level failures. Stated alongside the
// pair total so the relationship between this page and the incident logs is
// visible rather than inferred: one incident affecting three cars shows as
// 1 incident / 3 car-level failures.
$distinctIncidents = 0;
$dq = $db->query("select count(distinct incident_cars.incident_id) as c
                  from incident_cars
                  inner join incident_report on incident_cars.incident_id=incident_report.id
                  where incident_report.equipt='".$equipt_id."' and level='".$level."'
                    and incident_date between '".$range_start." 00:00:00' and '".$range_end." 23:59:59'");
if($dq && ($dr = $dq->fetch_assoc())) $distinctIncidents = (int)$dr['c'];

$sql = "select incident_cars.car_no as car_no
        from incident_cars
        inner join incident_report on incident_cars.incident_id=incident_report.id
        where incident_report.equipt='".$equipt_id."' and level='".$level."'
          and incident_date between '".$range_start." 00:00:00' and '".$range_end." 23:59:59'
        group by incident_cars.car_no
        order by incident_cars.car_no";
$rs = $db->query($sql);

if($rs){
	$i = 0;
	while($row = $rs->fetch_assoc()){
		$car[$i]['id']  = $row['car_no'];
		$car[$i]['car'] = $row['car_no'];
		for($k=1;$k<=12;$k++){
			$car_count["Car_".$row['car_no']]["Month_".$k] = 0;
		}
		$car_count["Car_".$row['car_no']]["total"] = 0;
		$i++;
	}
}

/*

if(isset($_POST['level'])){
//	$year=$_POST['year'];
	$level=$_POST['level'];
}
else {
//	$year=date("Y");
	$level="2";
}
*/
?>

<body>
<div class="ccs-page">
<div class="ccs-panel">

<div class='ccs-header'>
<h1>
<?php echo $ename." - Failures By Car"; ?>
</h1>
<div class='sub'></div>

</div>

<div class='ccs-panel-head'><h2><?php echo "Year ".$year; echo " / ";echo " Level ".$level; ?></h2></div>

</div>

<div class='ccs-panel-body'>
<?php
// The KPI values below are only known once the monthly aggregation loop has
// run, which happens while the table is being emitted. Buffer the table so
// the summary can still be printed ABOVE it.
ob_start();
?>


<table class="table table-striped table-bordered bootstrap-datatable datatable2" border=1px style='border-collapse:collapse;' width=100%>
<tr >
<th>Car Number</th>
<?php
if(isset($_GET['sd'])){
	$start=date("m",strtotime($start_date));
	$end=date("m",strtotime($end_date));
	
	if($_GET['range']=="yearly"){
		$end=12;
	}
	
	
}
else {
	$start=1;
	$end=12;
}

for($k=$start;$k<=$end;$k++){
?>	
	<th>
	<?php echo date("F",strtotime(date("Y")."-".$k."-01")); ?>
	</th>
	
<?php
}
?>
<th>Total</th>
<?php
//$sql="select *,count(1) as equipt_count from incident_report where level='".$level."' and incident_date between '".$start_date." 00:00:00' and '".$end_date." 23:59:59' and equipt in ('114','102','110','11','113','104','108','109','103','124','67','111','112','105','81','118','119','64','115','89','120','123','121','116','2','122','117','105','81','118','119','64','115','89','120','123','121','116','2','122','117') group by equipt";
//$rs=$db->query($sql);

//$nm=$rs->num_rows;


?>



</tr>
<?php

if(isset($_GET['sd'])){
	// Was date("m",$start_date) — no strtotime, so PHP read the date string as
	// a timestamp and this range diverged from the header's. Column headers and
	// row cells then iterated different months and the table silently shifted.
	$start=date("m",strtotime($start_date));
	$end=date("m",strtotime($end_date));
	
	if($_GET['range']=="yearly"){
		$end=12;
	}
	
	
}
else {
	$start=1;
	$end=12;
}



for($i=$start;$i<=$end;$i++){
	$month_heading=date("F",strtotime($year."-".$i."-01"));
	
	
	// Loop-local: reassigning $start_date/$end_date here used to leave them
	// pointing at the LAST month once the loop finished, and the car-list
	// query below then ran over the wrong range.
	$date_limit=date("t",strtotime($year."-".$i."-01"));
	$m_start=date("Y-m-d",strtotime($year."-".$i."-01"));
	$m_end=date("Y-m-d",strtotime($year."-".$i."-".$date_limit));

	
	// Counts incident-car pairs, matching statistics_report_modified.php.
	$sql="select incident_cars.car_no as car_no, count(1) as car_count
	       from incident_report
	       inner join incident_cars on incident_report.id=incident_cars.incident_id
	       where level='".$level."' and incident_date between '".$m_start." 00:00:00' and '".$m_end." 23:59:59'
	         and incident_report.equipt='".$equipt_id."'
	       group by incident_cars.car_no";
	if($i==1){
//		echo $sql;
//		echo "<br>";
	}
	$rs=$db->query($sql);
	if($rs){
		while($row=$rs->fetch_assoc()){
			if(!isset($car_count["Car_".$row['car_no']])) continue;   // outside the car list
			$car_count["Car_".$row['car_no']]["Month_".$i]+=$row['car_count'];
			$car_count["Car_".$row['car_no']]["total"]+=$row['car_count'];
		}
	}


	// Was joining is_external.incident_defects but filtering on
	// external.incident_defects — two different schema names in one statement,
	// so the query failed, $rs came back false and external defects silently
	// contributed nothing. Also needs the incident_cars join to produce a
	// car_no at all, and to count per car like the internal query does.
	$sql="select incident_cars.car_no as car_no, count(1) as car_count
	       from incident_union
	       inner join is_external.incident_defects on incident_union.id=is_external.incident_defects.incident_id
	       inner join incident_cars on incident_union.id=incident_cars.incident_id
	       where level='".$level."' and incident_date between '".$m_start." 00:00:00' and '".$m_end." 23:59:59'
	         and is_external.incident_defects.equipt_id='".$equipt_id."'
	       group by incident_cars.car_no"; 
	$rs=$db->query($sql);
	if($rs){
		while($row=$rs->fetch_assoc()){
			if(!isset($car_count["Car_".$row['car_no']])) continue;
			$car_count["Car_".$row['car_no']]["Month_".$i]+=$row['car_count'];
			$car_count["Car_".$row['car_no']]["total"]+=$row['car_count'];
		}
	}



/*	
	$sql="select *,count(1) as equipt_count from incident_report where level='".$level."' and incident_date between '".$start_date." 00:00:00' and '".$end_date." 23:59:59' and equipt in ('105','81','118','119','64','115','89','120','123','121','116','2','122','117','105','81','118','119','64','115','89','120','123','121','116','2','122','117') group by level";
	$rs=$db->query($sql);
	$nm=$rs->num_rows;

	if($nm>0){
		$row=$rs->fetch_assoc();
		$equipt_count["Equipt_Others"]["Month_".$i]=$row['equipt_count'];
	}

	$sql="select *,count(1) as equipt_count from incident_report inner join external.incident_defects on incident_report.id=external.incident_defects.incident_id where level='".$level."' and incident_date between '".$start_date." 00:00:00' and '".$end_date." 23:59:59' and external.incident_defects.equipt_id in ('105','81','118','119','64','115','89','120','123','121','116','2','122','117','105','81','118','119','64','115','89','120','123','121','116','2','122','117') group by level"; 
	$rs=$db->query($sql);
	$nm=$rs->num_rows;
	
	for($k=0;$k<$nm;$k++){
		$row=$rs->fetch_assoc();
		$equipt_count["Month_".$i]["Equipt_".$row['equipt_id']]+=$row['equipt_count'];
	}
*/

	
}	
?>	
<?php
// ---- Totals, peak car, and the highlight threshold -----------------------
// The old pairwise sortCar() walk mis-indexed on lists shorter than two and
// left $highestCar without a 'total'. A straight max is clearer and safe.
$grandTotal = 0;
$peakTotal  = 0;
$peakCar    = '';
foreach($car as $idx => $c){
	$t = $car_count["Car_".$c['id']]["total"];
	$car[$idx]['total'] = $t;
	$grandTotal += $t;
	if($t > $peakTotal){ $peakTotal = $t; $peakCar = $c['id']; }
}
$carsWithFailures = 0;
foreach($car as $c){ if($c['total'] > 0) $carsWithFailures++; }
$avgPerCar = count($car) ? round($grandTotal / count($car), 1) : 0;

// Rows at or above 60% of the worst car are flagged. The rule was previously
// applied with no explanation on screen — it now has a stated threshold and
// a legend beneath the table.
$flagThreshold = $peakTotal * 0.60;
function ehIsFlagged($total,$threshold){ return $threshold > 0 && $total >= $threshold; }

?>
<?php
// ---- Rows ---------------------------------------------------------------
// Iterates the canonical $car list, so the label and the figures on a row
// always come from the same record. Previously the label came from a fresh
// query while the figures were read from $car by position — different
// orderings, so the numbers sat against the wrong car.
foreach($car as $i => $c){
	$carNo   = $c['id'];
	$rowTot  = $car_count["Car_".$carNo]["total"];
	$flagged = ehIsFlagged($rowTot, $flagThreshold);
?>
<tr <?php echo $flagged ? "style='background-color:#F9D6D6; color:#7A1F1F;'" : ($i%2>0 ? "class='rowClass'" : ""); ?>>
	<th><?php echo htmlspecialchars($carNo); ?><?php if($flagged){ echo " <span title='At or above 60% of the worst car' style='font-size:11px;'>&#9679;</span>"; } ?></th>
<?php
	for($k=$start;$k<=$end;$k++){
		$v = $car_count["Car_".$carNo]["Month_".$k];
?>
	<td<?php echo $flagged ? "" : " class='stat_hover'"; ?> align=center><?php echo ($v>0) ? $v : "<span style='opacity:.35'>0</span>"; ?></td>
<?php
	}
?>
	<td<?php echo $flagged ? "" : " class='stat_hover'"; ?> align=center><b><?php echo $rowTot; ?></b></td>
</tr>
<?php
}

if(!count($car)){
?>
<tr><td colspan="<?php echo ($end-$start+2); ?>" align=center style="padding:18px;opacity:.6;">No car failures recorded for this equipment in the selected range.</td></tr>
<?php
}
?>
<tr style="background:#F1EFE8;font-weight:bold;">
	<th>All cars</th>
<?php
	for($k=$start;$k<=$end;$k++){
		$colTot=0;
		foreach($car as $c){ $colTot += $car_count["Car_".$c['id']]["Month_".$k]; }
?>
	<td align=center><?php echo $colTot; ?></td>
<?php
	}
?>
	<td align=center><?php echo $grandTotal; ?></td>
</tr>
</table>
<?php
$tableHtml = ob_get_clean();

$monthTotals = array();
for($k=$start;$k<=$end;$k++){
	$t=0;
	foreach($car as $c){ $t += $car_count["Car_".$c['id']]["Month_".$k]; }
	$monthTotals[] = array(date("M",strtotime(date("Y")."-".$k."-01")), $t);
}
$carTotals = array();
foreach($car as $c){ $carTotals[] = array($c['id'], (int)$car_count["Car_".$c['id']]["total"]); }
usort($carTotals, function($a,$b){ return $b[1]-$a[1]; });
?>

<div class="ecs-kpis" style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
	<div style="flex:1;min-width:130px;border:1px solid #D8D2C2;border-radius:6px;padding:10px 12px;background:#FBFAF6;">
		<div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.06em;">Car-level failures</div>
		<div style="font-size:22px;font-weight:600;color:#1f4e79;"><?php echo $grandTotal; ?></div>
		<div style="font-size:11px;color:#6b7280;">from <?php echo $distinctIncidents; ?> incident<?php echo $distinctIncidents==1?'':'s'; ?></div>
	</div>
	<div style="flex:1;min-width:130px;border:1px solid #D8D2C2;border-radius:6px;padding:10px 12px;background:#FBFAF6;">
		<div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.06em;">Cars affected</div>
		<div style="font-size:22px;font-weight:600;color:#1f4e79;"><?php echo $carsWithFailures; ?></div>
		<div style="font-size:11px;color:#6b7280;">of <?php echo count($car); ?> listed</div>
	</div>
	<div style="flex:1;min-width:130px;border:1px solid #D8D2C2;border-radius:6px;padding:10px 12px;background:#FBFAF6;">
		<div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.06em;">Worst car</div>
		<div style="font-size:22px;font-weight:600;color:#7A1F1F;"><?php echo $peakCar!=='' ? htmlspecialchars($peakCar) : '&mdash;'; ?></div>
		<div style="font-size:11px;color:#6b7280;"><?php echo $peakTotal; ?> failures</div>
	</div>
	<div style="flex:1;min-width:130px;border:1px solid #D8D2C2;border-radius:6px;padding:10px 12px;background:#FBFAF6;">
		<div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.06em;">Average per car</div>
		<div style="font-size:22px;font-weight:600;color:#1f4e79;"><?php echo $avgPerCar; ?></div>
		<div style="font-size:11px;color:#6b7280;">across listed cars</div>
	</div>
</div>

<div style="margin-bottom:14px;">
	<button type="button" onclick="ecsPrintReport()" style="padding:6px 14px;border:1px solid #1f4e79;background:#1f4e79;color:#fff;border-radius:4px;cursor:pointer;font-size:13px;">Print report</button>
</div>

<div style="display:flex;flex-wrap:wrap;gap:16px;margin-bottom:16px;">
	<div><canvas id="ecsByCar" width="340" height="220"></canvas></div>
	<div><canvas id="ecsByMonth" width="340" height="200"></canvas></div>
</div>

<?php echo $tableHtml; ?>

<div style="font-size:12px;color:#5b5749;margin-top:8px;">
	<span style="display:inline-block;width:11px;height:11px;background:#F9D6D6;border:1px solid #7A1F1F;vertical-align:-1px;"></span>
	Shaded rows are cars at or above 60% of the worst car's total (<?php echo round($flagThreshold,1); ?> failures) &mdash; the review threshold.
	Figures count <b>car-level failures</b>: an incident affecting three cars counts once against each car, so <?php echo $distinctIncidents; ?> incident<?php echo $distinctIncidents==1?'':'s'; ?> here produce <?php echo $grandTotal; ?> car-level failure<?php echo $grandTotal==1?'':'s'; ?>. This is the same basis the equipment summary report uses, so the two reconcile; the incident history log counts one row per incident and will show the smaller figure.
</div>

<script>
var ecsCarTotals   = <?php echo json_encode($carTotals); ?>;
var ecsMonthTotals = <?php echo json_encode($monthTotals); ?>;
var ecsEquipment   = <?php echo json_encode($ename); ?>;
var ecsYear        = <?php echo json_encode($year); ?>;
var ecsLevel       = <?php echo json_encode($level); ?>;
var ecsGrandTotal  = <?php echo (int)$grandTotal; ?>;
var ecsPeakCar     = <?php echo json_encode($peakCar); ?>;
var ecsCarsAffected= <?php echo (int)$carsWithFailures; ?>;
var ecsIncidents   = <?php echo (int)$distinctIncidents; ?>;
</script>
<br>
<br>

</div>
</div>


<style type='text/css'>
.stat_hover:hover {
	background-color:#fbcc2a;
	text-decoration:underline;
	font-weight:bold;
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
(function(){
	var ink='#1a1a1a', muted='#5b5749', grid='rgba(137,135,129,0.20)';
	var TOP_CARS = 8;

	function valueLabels(){
		return { id:'ecsLabels', afterDatasetsDraw:function(chart){
			var ctx=chart.ctx, meta=chart.getDatasetMeta(0);
			ctx.save(); ctx.font='11px Arial, sans-serif'; ctx.fillStyle=ink;
			ctx.textBaseline='middle'; ctx.textAlign='left';
			meta.data.forEach(function(bar,i){ ctx.fillText(chart.data.datasets[0].data[i], bar.x+6, bar.y); });
			ctx.restore();
		}};
	}

	var top = ecsCarTotals.slice(0, TOP_CARS);
	var tail = ecsCarTotals.slice(TOP_CARS);
	var tailTotal = tail.reduce(function(s,r){ return s+r[1]; }, 0);

	new Chart(document.getElementById('ecsByCar'), {
		type:'bar',
		data:{ labels: top.map(function(r){ return 'Car '+r[0]; }),
		       datasets:[{ data: top.map(function(r){ return r[1]; }),
		                   backgroundColor: top.map(function(r){ return r[0]===ecsPeakCar ? '#c0392b' : '#2a78d6'; }),
		                   borderRadius:3, categoryPercentage:0.62, barPercentage:0.9 }] },
		options:{ indexAxis:'y', responsive:false, animation:false,
			layout:{ padding:{ right:22, bottom: tail.length ? 18 : 4 } },
			plugins:{
				title:{ display:true, text:'Failures by car'+(tail.length?' (top '+TOP_CARS+')':''), color:ink, font:{size:11,weight:'normal'}, padding:{bottom:8} },
				legend:{ display:false },
				tooltip:{ callbacks:{ label:function(c){ return c.parsed.x+' failures'; } } }
			},
			scales:{ x:{ ticks:{ color:muted, precision:0, font:{size:10} }, grid:{ color:grid } },
			         y:{ ticks:{ color:ink, font:{size:11} }, grid:{ display:false } } }
		},
		plugins:[ valueLabels(), { id:'ecsTail', afterDraw:function(chart){
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

	new Chart(document.getElementById('ecsByMonth'), {
		type:'bar',
		data:{ labels: ecsMonthTotals.map(function(r){ return r[0]; }),
		       datasets:[{ data: ecsMonthTotals.map(function(r){ return r[1]; }), backgroundColor:'#2a78d6', borderRadius:3 }] },
		options:{ responsive:false, animation:false,
			plugins:{ title:{ display:true, text:'Failures by month, all cars', color:ink, font:{size:11,weight:'normal'}, padding:{bottom:6} }, legend:{ display:false } },
			scales:{ x:{ ticks:{ color:muted, font:{size:10} }, grid:{ display:false } },
			         y:{ ticks:{ color:muted, precision:0, font:{size:10} }, grid:{ color:grid } } }
		}
	});

	window.ecsPrintReport = function(){
		var imgCar   = document.getElementById('ecsByCar').toDataURL('image/png');
		var imgMonth = document.getElementById('ecsByMonth').toDataURL('image/png');
		var tbl      = document.querySelector('.ccs-panel-body table');
		var tableHtml = tbl ? tbl.outerHTML : '';
		function esc(x){ return String(x).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
		var name = esc(ecsEquipment);

		var win = window.open('', '_blank');
		win.document.write(
			'<html><head><title>'+name+' \u2014 Failures by Car</title>' +
			'<style>' +
				'@page{ size:A4 portrait; margin:14mm 12mm 15mm; }' +
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
					' border-bottom:1px solid #d1d5db; padding-bottom:4px; margin:20px 0 10px; font-weight:600; }' +
				'.charts{ margin-bottom:4px; }' +
				'.chart{ display:inline-block; vertical-align:top; width:48%; margin:0 2% 10px 0; page-break-inside:avoid; }' +
				'.chart img{ display:block; width:100%; height:auto; border:1px solid #e5e7eb; }' +
				'.chart .cap{ font-size:9px; color:#6b7280; margin-top:3px; }' +
				'.note{ font-size:9px; color:#6b7280; font-style:italic; margin:2px 0 0; }' +
				'table{ width:100%; border-collapse:collapse; font-size:9px; }' +
				'thead{ display:table-header-group; }' +
				'th{ background:#1f4e79; color:#fff; text-align:left; padding:5px 5px; font-size:8.5px; font-weight:600;' +
					' text-transform:uppercase; letter-spacing:.04em; border:1px solid #1f4e79; }' +
				'td{ padding:4px 5px; border:1px solid #e5e7eb; text-align:center; }' +
				'tr{ page-break-inside:avoid; }' +
				'.rpt-foot{ margin-top:14px; border-top:1px solid #d1d5db; padding-top:6px; font-size:8.5px; color:#6b7280; }' +
			'</style></head><body>' +
			'<div class="rpt-head">' +
				'<div class="rpt-org">DOTr &middot; MRT-3 Line 3 &middot; Operations Control</div>' +
				'<h1 class="rpt-title">Equipment Failures by Car</h1>' +
				'<p class="rpt-subject">'+name+'</p>' +
			'</div>' +
			'<div class="rpt-meta">' +
				'<span><b>Year:</b> '+esc(ecsYear)+'</span>' +
				'<span><b>Level:</b> '+esc(ecsLevel)+'</span>' +
				'<span><b>Car-level failures:</b> '+ecsGrandTotal+'</span>' +
				'<span><b>From incidents:</b> '+ecsIncidents+'</span>' +
				'<span><b>Cars affected:</b> '+ecsCarsAffected+'</span>' +
				'<span><b>Generated:</b> <?php echo date("d M Y, H:i"); ?></span>' +
			'</div>' +
			'<h2 class="sec">Summary</h2>' +
			'<div class="charts">' +
				'<div class="chart"><img src="'+imgCar+'"><div class="cap">Figure 1 &mdash; Failures by car</div></div>' +
				'<div class="chart"><img src="'+imgMonth+'"><div class="cap">Figure 2 &mdash; Failures by month, all cars</div></div>' +
				'<p class="note">Figures count car-level failures: an incident affecting several cars counts once against each car, so '+ecsIncidents+' incidents produce '+ecsGrandTotal+' car-level failures. This matches the basis used by the equipment summary report; the incident history log counts one row per incident and shows the smaller figure. Shaded rows are cars at or above 60% of the worst car total.</p>' +
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

<?php include("history_theme.php"); ?>
</body>