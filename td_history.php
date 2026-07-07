<?php
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
$car_id=$_GET['car_id'];
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
<div class="ccs-panel-head"><h3>Reported by: <?php echo htmlspecialchars($_POST['reported_by'] ?? ''); ?></h3></div>
<div class="ccs-panel-body">
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
if($_POST['reported_by']==""){ $rep="XXXX"; }
else { $rep=$_POST['reported_by']; }

$sql="select * from incident_description inner join incident_report on incident_report.id=incident_description.incident_id where reported_by like '%".$rep."%%' order by incident_date desc";
$rs=$db->query($sql);
$nm=$rs->num_rows;

for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
?>	
	<tr>
		<td><?php echo $row['index_no']; ?></td>
		<td><?php echo "<span>".date("Y-m-d",strtotime($row['incident_date']))."</span>"; ?></td>
		<td><?php echo  getProblemType($db,$row['incident_type']); ?></td>

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