<?php 
require("Tmenu.php");
ini_set("date.timezone","Asia/Kuala_Lumpur");
?>
<?php
	/* Centralized credentials -- see db_config.php. This page used to carry a
	   hardcoded new mysqli(...) line, which put the DB password in the page
	   source; iss_db() is the same accessor the rest of the console uses. */
	require_once("db_config.php");
	$db=iss_db('external');
?>
<!--
<link href="css/modal_only.css" rel="stylesheet" />
-->

<!-- The font-awesome / bootstrap / style / retina / dataTables stylesheet
     links that used to sit here are emitted by history_theme.php (included
     below), byte-identical. Keeping a second copy here just fetched every
     one of them twice, so they have been dropped and the include is now the
     single source -- same arrangement as the other console pages. -->

<style type='text/css'>

/* color background */
.rowClass {
	background-color: #F3F3F3;
}

/* color header */
.rowHeading {
	background-color: #cccccc; 
	 /* color:rgb(0,51,153); */
}

/* outline  color result */
.train_ava td{
	border: 1px solid #A9A9A9;
	/* color: rgb(0,51,153); */
	cellpadding: 5px; 
}

/* outline header */
 .train_ava th {
	border: 1px solid #A9A9A9;
	cellpadding: 5px;	
}

/*
body { 
	margin-left:30px;
	margin-right:30px;
	font-size: 3px;
}
*/

input[type="text"]{ 
	height:25px; 
	font-weight:bold; 
	font-size:15px; 
	font-family:courier; 
	border: 1px solid #C6C6C6; 
	background-color: rgb(185, 201, 254);  
	color: rgb(0,51,153);
	border-radius: 3px;
}

#cellHeading {
	background-image: -o-linear-gradient(bottom, rgb(185, 201, 254) 38%, #4ad 62%);
	background-image: -moz-linear-gradient(bottom, rgb(185, 201, 254) 38%,#4ad 62%);
	background-image: -moz-linear-gradient(bottom, rgb(185, 201, 254) 38%, #4ad 62%);
	background-image: -webkit-gradient(linear, left bottom, left top, color-stop(0.38, rgb(185, 201, 254)), color-stop(0.62, #4ad));
	background-image: -webkit-linear-gradient(bottom, rgb(185, 201, 254) 38%,#4ad 62%);
	background-image: -ms-linear-gradient(bottom, rgb(185, 201, 254) 38%, #4ad 62%);
	background-image: linear-gradient(bottom, rgb(185, 201, 254) 38%, #4ad 62%);

	background-color: rgb(185, 201, 254);  

	color: rgb(0,51,153);
	padding:5px;
	-moz-border-radius: 5px;
	border-radius: 5px;
}

input[type="text"]:focus {
	background-color:rgb(158,27,32);
	color:white;

}

/* ---------------------------------------------------------------------------
   The six rules below are commented out rather than deleted, per the usual
   practice on these pages.

   They are the only legacy rules on this page that still match live elements,
   and they outrank the shared theme: #add_form td:nth-child(odd) carries an
   id + a pseudo-class, so it beats history_theme.php's plainer #add_form
   selectors and repainted the modal's label cells bright green, the field
   cell periwinkle, and the heading cyan -- with the textarea itself blue,
   turning dark red on focus. Everything else in this block (.rowClass,
   .rowHeading, .train_ava, #cellHeading, input[type=text], select, a.two,
   a.two2, a.LDel, .date, h2) matches nothing on this page and is left exactly
   as it was. Replacement chrome, in --cf-* tokens, is in the page-specific
   block after the theme include.

textarea:focus {
	background-color:rgb(158,27,32);
	color:white;
	font-weight:bold;
}

textarea{ 
	border: 1px solid rgb(185, 201, 254);
	background-color: rgb(185, 201, 254);  
	color: rgb(0,51,153);
	border-radius: 3px;
}

#add_form th{
background-color: #4ad;  
}

#add_form td:nth-child(odd) {
background-color: #33aa55; 
color:white;
font-weight:bold;
padding:5px;

}
#add_form td:last-child{
background-color:white;
}

#add_form td:nth-child(even) {
background-color: rgb(185, 201, 254);  
border:1px solid #4ad;
}
   --------------------------------------------------------------------------- */

.date {
	text-style:bold;
	font-size:20px;
}

select { border: 1px solid rgb(185, 201, 254); color: black; background-color: #FFFACD; } 

/* --- mjun */
a.two:visited {color:black;}
a.two:hover, a.two:active {font-size:120%; color:orange;}

a.two2:visited {color:#ca0000;}
a.two2:hover, a.two:active {font-size:105%; color:orange;}
h2 { font-size:20px; font-weight:bold; }
a.LDel:visited {color:red;}
</style>
<?php include("history_theme.php"); ?>

<style type="text/css">
/* =============================================================================
   PREENCODED DETAILS (details_list.php) -- page-specific chrome
   Sits after history_theme.php so it layers on top of the shared --cf-*
   tokens, matching how equipment_list.php / signatories_list.php are built.
   ============================================================================= */

/* -- Shared modal visibility guard ------------------------------------------
   modal_only.css / bootstrap hold .modal out of the page with the .hide class
   alone -- nothing sets display:none on the modal itself. Whenever .hide isn't
   holding, the modal is still a fixed, centered slab sitting invisibly over
   the page, swallowing hover and clicks on the nav bar above it. This is the
   same id-level guard already applied to equipment_list, signatories_list and
   edit_ccdr; Bootstrap's inline display:block on open still overrides it, so
   opening the modal is unaffected. -- */
#addModal { display: none; }
#addModal.in { display: block; }

/* -- Panel head: title + primary action, same pill-button language used
   for primary actions across the console (gold fill on the blue bar, as
   in train_operations.php's .ops-act--gold). ccs-panel-head itself is
   defined by history_theme.php; this just adds the flex layout and the
   button skin for the "Add Details" action. -- */
.ccs-panel-head { display: flex; align-items: center; justify-content: space-between; }
.ccs-panel-head .cf-btn-add {
	display: inline-flex;
	align-items: center;
	gap: 5px;
	font-family: var(--cf-sans);
	font-size: 11px;
	font-weight: 700;
	letter-spacing: .2px;
	text-decoration: none;
	color: var(--cf-gold-ink);
	background: var(--cf-gold);
	border: 1px solid var(--cf-gold);
	border-radius: 4px;
	padding: 5px 12px;
	cursor: pointer;
}
.ccs-panel-head .cf-btn-add:hover { background: #E5A50F; border-color: #E5A50F; color: var(--cf-gold-ink); text-decoration: none; }

/* -- Add-details modal form -- */
#addModal .modal-header { background: var(--cf-blue); border-bottom: 3px solid var(--cf-gold); border-radius: 6px 6px 0 0; }
#addModal .modal-header h3 { color: #fff; font-size: 13px; font-weight: 600; margin: 0; }
#addModal .modal-header .close { color: rgba(255,255,255,.7); text-shadow: none; opacity: 1; font-size: 18px; }
#addModal .modal-header .close:hover { color: var(--cf-gold); }
#addModal .modal-body { background: var(--cf-bg); }

#add_form table { width: 100%; border-collapse: collapse; }
#add_form th {
	background: var(--cf-blue);
	color: #fff;
	font-size: 11px;
	font-weight: 600;
	letter-spacing: .3px;
	text-align: left;
	padding: 7px 10px;
}
#add_form td {
	padding: 8px 10px;
	border-bottom: 1px solid var(--cf-border);
	font-size: 12px;
	vertical-align: top;
}
#add_form td:first-child {
	background: var(--cf-row-odd);
	color: var(--cf-mid);
	font-weight: 600;
	width: 90px;
	white-space: nowrap;
}
#add_form textarea {
	width: 100%;
	border: 1px solid var(--cf-border);
	border-radius: 3px;
	background: var(--cf-white);
	color: var(--cf-dark);
	font-family: var(--cf-sans);
	font-size: 12px;
	padding: 6px 8px;
}
#add_form textarea:focus {
	outline: none;
	border-color: var(--cf-blue);
	background: var(--cf-white);
	color: var(--cf-dark);
	box-shadow: 0 0 0 2px rgba(0,82,155,.12);
}

/* -- Details column reads as prose, not as a data cell -- */
.ccs-panel .detail-text { white-space: pre-wrap; color: var(--cf-dark); }
.ccs-panel td.detail-id { width: 60px; color: var(--cf-muted); text-align: center; }
</style>

<script language='javascript'>
/* Named addSignatory() until now -- a copy-paste carryover from
   signatories_list.php, which this page was cloned from. */
function addDetails(){
	$('#addModal').modal('show');
}

/* The datepicker below bound #as_of_date, which does not exist on this page
   (another signatories_list carryover). jQuery treated it as an empty set and
   did nothing; kept here commented in case a date filter is added later.

$(function() {
    $( "#as_of_date" ).datepicker({
      changeMonth: true,
      changeYear: true,
      showAnim: "bounce"
    });    
});
*/

</script>

<script language='javascript' src='ajax.js'></script>
<?php
if(isset($_POST['content'])){
	/* Escaped before interpolation -- this insert previously concatenated
	   $_POST['content'] straight into the statement. */
	$content=trim($_POST['content']);
	if($content!==""){
		$content=$db->real_escape_string($content);
		$update="insert into preencoded";
		//$update.="(code,content)";
		$update.="(content)";
		$update.=" values ";
		$update.="(\"".$content."\")";
		$updateRS=$db->query($update);
	}

}
?>
<?php

$sql="select * from preencoded order by id desc";
$rs=$db->query($sql);
$nm=$rs->num_rows;

?>

<div class="ccs-page">

<div class="ccs-header">
	<h1>Preencoded Details</h1>
	<div class="sub">Transport &middot; canned description text offered by the incident forms</div>
</div>

<div class="ccs-panel">
<div class="ccs-panel-head">
<h3 style="margin:0;font-size:12px;font-weight:700;color:#fff;letter-spacing:.4px;text-transform:uppercase;">All Details</h3>
<a href='#' class="cf-btn-add" onclick='addDetails()'>+ Add Details</a>
</div>
<div class="ccs-panel-body">


<table width='100%' class='table table-striped table-bordered bootstrap-datatable datatable2'>
	<thead>
	<tr>
		<th>ID</th>
		<!--
		<td>Code</td>
		-->
		<th>Details</th>
	</tr>
	</thead>
	<tbody>
<?php
for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
	//$code=$row['code'];
	$details=$row['content'];
	
?>
	<tr>	
		<td class="detail-id"><?php echo $row['id']; ?></td>
		<td class="detail-text"><?php echo htmlspecialchars($details, ENT_QUOTES, 'UTF-8'); ?></td>
	</tr>
<?php

}

?>
</tbody>
</table>
</div>
</div>
</div>


		<div class="modal hide fade" id="addModal">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
				<h3>Add Preencoded Detail</h3>
				</div>
				<form  action='details_list.php' method='post'>

							<div class="modal-body">	
							<div name='add_form' id='add_form'>
				<table>
				<tr><th colspan=2>Add Details</th></tr>
				<!--
				<tr>
				<td>
				Code
				</td>
				<td>
				<input type='text' name='code' />

				</td>
				</tr>
				-->
				<tr>
				<td>
				Content
				</td>
				<td>
				<textarea rows=5 cols=50 name='content' id='content'></textarea>
				</td>
				</tr>
				
				</table>

				</div>
			</div>
						
			<div class="modal-footer">
				<a href="#" class="btn" data-dismiss="modal">Close</a>
				<button type='submit' class="btn btn-primary" id='Suc' value='Submit'>Save</button>
			</div>
			  </form>
		</div>
		<!--	
		<script src="js/jquery-1.10.2.min.js"></script>
		-->
		<!--
		<script src="js/jquery-migrate-1.2.1.min.js"></script>	
		<script src="js/jquery-ui-1.10.3.custom.min.js"></script>	
		<script src="js/jquery.ui.touch-punch.js"></script>
		<script src="js/modernizr.js"></script>	

		<script src="js/bootstrap.min.js"></script>	
		
		<script src='js/jquery.dataTables.min.js'></script>
		<script src="js/core.min.js"></script>	
		<script src="js/custom.min.js"></script>
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
		
		
		<script src="js/date.js"></script>	
		
		<script src='js/form3.js'></script>