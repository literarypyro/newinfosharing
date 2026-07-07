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

for($i=0;$i<$nm;$i++){

	$row=$rs->fetch_assoc();
	?>
    <tr>
        <td><?php echo $row['index_no']; ?></td>
        <td><?php echo date("Y-m-d H:iA", strtotime($row['incident_date'])); ?></td>
        <td>&nbsp;</td>
        <td><?php echo $row['duration']; ?></td>
        <td><?php echo getProblemType($db,$row['incident_type']); ?></td>
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