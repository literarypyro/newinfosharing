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