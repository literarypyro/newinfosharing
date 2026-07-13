<?php 
require("Tmenu.php");
ini_set("date.timezone","Asia/Kuala_Lumpur");
?>
<?php
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_user_transport");
?>
<link href="css/modal_only.css" rel="stylesheet" />
<!-- <link href="css/style.min.css" rel="stylesheet" /> -->

<link rel="stylesheet" href="jquery-ui-themes-1.11.1/themes/smoothness/jquery-ui.css" />
<script src="jquery-ui-1.11.1/external/jquery/jquery.js"></script>
<script src="jquery-ui-1.11.1/jquery-ui.js"></script>
	
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

textarea:focus {
	background-color:rgb(158,27,32);
	color:white;
	font-weight:bold;
}

.date {
	text-style:bold;
	font-size:20px;
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

select { border: 1px solid rgb(185, 201, 254); color: black; background-color: #FFFACD; } 

/* --- mjun */
a.two:visited {color:black;}
a.two:hover, a.two:active {font-size:120%; color:orange;}

a.two2:visited {color:#ca0000;}
a.two2:hover, a.two:active {font-size:105%; color:orange;}
h2 { font-size:20px; font-weight:bold; }
a.LDel:visited {color:red;}
</style>

<style type='text/css'>
/* =========================================================================
   SIGNATORIES LIST -- Operations Console Theme
   Uniform with incident_summary.php / train_availability.php / car_history.php.
   Scoped under .ta-grid.ta-console (+ #addModal / #editModal) so nothing
   here bleeds into other pages. The legacy block above is kept intact; the
   rules below win where they overlap (source order / ID specificity).
   ========================================================================= */
:root {
	--cf-blue:    #00529B;
	--cf-gold:    #FDB813;
	--cf-dark:    #16243B;
	--cf-mid:     #41506A;
	--cf-muted:   #8A95A6;
	--cf-border:  #D2DDEA;
	--cf-row-odd: #EEF4FB;
	--cf-bg:      #F7F9FC;
	--cf-white:   #ffffff;
	--cf-sans:    "Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif;
}

table { border-collapse: collapse; }
body { font-family: var(--cf-sans); color: var(--cf-dark); }

/* -- Layout wrapper: keeps the toolbar and table aligned, and holds a sane
   width for a small (3-column) table. Remove max-width if you'd rather it
   run full-bleed like the wider console pages. -- */
.ta-grid.ta-console .cf-eq-wrap { width: 96%;  max-width: 1400px; margin-top:20px; }

/* -- Toolbar band: blue with the gold underline, same tokens as the other
   pages. Title + count on the left, primary action on the right. -- */
.ta-grid.ta-console .cf-eq-toolbar {
	width: 100%;
	background: var(--cf-blue);
	border-bottom: 3px solid var(--cf-gold);
	border-collapse: collapse;
	border-radius: 8px 8px 0 0;
	overflow: hidden;
}
.ta-grid.ta-console .cf-eq-toolbar td { border: none; padding: 10px 16px; vertical-align: middle; }
.ta-grid.ta-console .cf-eq-title { color: #fff; font-weight: 700; font-size: 14px; letter-spacing: .02em; }
.ta-grid.ta-console .cf-eq-count { color: rgba(255,255,255,.6); font-weight: 400; font-size: 11px; margin-left: 10px; }
.ta-grid.ta-console .cf-eq-actions { text-align: right; white-space: nowrap; }

/* -- Toolbar buttons: outlined / gold-filled pills, same treatment as the
   +Add / Generate buttons on the other pages. -- */
.ta-grid.ta-console .cf-tbtn {
	display: inline-block; font-size: 11px; font-weight: 500; color: #fff;
	text-decoration: none; padding: 5px 12px; border: 1px solid rgba(255,255,255,.35);
	border-radius: 3px; cursor: pointer;
}
.ta-grid.ta-console .cf-tbtn:hover { background: rgba(255,255,255,.12); }
.ta-grid.ta-console .cf-tbtn:focus-visible { outline: 2px solid var(--cf-gold); outline-offset: 2px; }
.ta-grid.ta-console .cf-tbtn--primary {
	font-weight: 600; color: #3A2D00; background: var(--cf-gold); border-color: var(--cf-gold);
}
.ta-grid.ta-console .cf-tbtn--primary:hover { background: #E5A50F; }

/* -- Data table -- */
.ta-grid.ta-console table.train_ava { width: 100%; border-collapse: collapse; font-size: 12px; }
.ta-grid.ta-console table.train_ava td,
.ta-grid.ta-console table.train_ava th { border: 1px solid var(--cf-border); padding: 8px 10px; text-align: left; }
.ta-grid.ta-console table.train_ava th {
	background: var(--cf-blue); color: #fff; font-weight: 600; font-size: 11px;
	text-align: center; border-color: #0A639E; text-transform: uppercase; letter-spacing: .03em;
}
.ta-grid.ta-console table.train_ava tr.rowHeading th { background: var(--cf-blue); }
.ta-grid.ta-console table.train_ava tr.cf-row--even td { background: var(--cf-white); }
.ta-grid.ta-console table.train_ava tr.cf-row--odd td  { background: var(--cf-row-odd); }
.ta-grid.ta-console table.train_ava tbody tr:hover td { background: #E3EEFA; }
.ta-grid.ta-console table.train_ava td.cf-actions { text-align: center; white-space: nowrap; width: 120px; }
.ta-grid.ta-console .cf-empty { text-align: center; color: var(--cf-muted); font-style: italic; padding: 18px; background: var(--cf-white); }

/* -- Inline Edit / Delete affordances. Unlike incident_summary (8 editable
   fields per row, so its Edit pills hide until cell-hover), this page has a
   single Edit + Delete per row in a dedicated Actions column, so both stay
   visible at rest for discoverability -- same pill/palette language, just
   not hover-gated. -- */
.ta-grid.ta-console a.LEdit {
	display: inline-flex; align-items: center; font-size: 10px; font-weight: 600;
	text-decoration: none; padding: 2px 10px; border-radius: 999px;
	border: 1px solid var(--cf-border); background: var(--cf-white); color: var(--cf-mid);
	transition: background .12s, border-color .12s, color .12s;
}
.ta-grid.ta-console a.LEdit:hover,
.ta-grid.ta-console a.LEdit:focus-visible { background: var(--cf-blue); border-color: var(--cf-blue); color: #fff; outline: none; }
.ta-grid.ta-console a.LDel {
	display: inline-block; font-size: 13px; font-weight: 700; line-height: 1;
	text-decoration: none; color: #B23A33; opacity: .75; margin-left: 8px;
	padding: 2px 7px; border-radius: 3px; border: 1px solid transparent; background: transparent;
	transition: opacity .12s, background .12s, border-color .12s;
}
.ta-grid.ta-console a.LDel:hover,
.ta-grid.ta-console a.LDel:focus-visible { opacity: 1; background: #FDEDEC; border-color: #F1C3C0; outline: none; }

/* -- Modal shell (Add + Edit) -- console theme, matches the other pages -- */
.modal { z-index: 99999; }
/* Guarantee the modal stays out of layout until it is opened. modal_only.css
   hides it ONLY via the .hide class (there is no display:none on .modal itself),
   and the modal is position:fixed / top:-25% / 560px wide -- so any time .hide
   stops winning, its lower edge drops over the top of the page and eats the menu's
   hover. This id-level rule removes that dependency; Bootstrap sets an inline
   display:block when it opens the modal, so opening still works. */
#addModal { display: none; }
#addModal, #editModal {
	border-radius: 8px; overflow: hidden; border: none;
	box-shadow: 0 8px 32px rgba(0,30,80,.18), 0 2px 8px rgba(0,30,80,.10);
	font-family: var(--cf-sans); min-width: 380px;
}
#addModal .modal-header, #editModal .modal-header {
	background: var(--cf-blue); border-bottom: 3px solid var(--cf-gold); padding: 10px 16px;
}
#addModal .modal-header h3, #editModal .modal-header h3 { color: #fff; font-size: 13px; font-weight: 600; margin: 0; }
#addModal .modal-header .close, #editModal .modal-header .close { color: rgba(255,255,255,.7); text-shadow: none; opacity: 1; font-size: 18px; }
#addModal .modal-header .close:hover, #editModal .modal-header .close:hover { color: var(--cf-gold); }
#addModal .modal-body, #editModal .modal-body { background: var(--cf-bg); padding: 16px 18px; }
#addModal .modal-footer, #editModal .modal-footer {
	background: #fff; border-top: 1px solid var(--cf-border); padding: 10px 16px;
	display: flex; justify-content: flex-end; gap: 8px;
}
#addModal .modal-footer .btn, #editModal .modal-footer .btn {
	font-size: 12px; font-weight: 500; padding: 5px 16px; border-radius: 4px;
	border: 1px solid var(--cf-border); background: #fff; color: var(--cf-mid); text-decoration: none;
}
#addModal .modal-footer .btn:hover, #editModal .modal-footer .btn:hover { background: var(--cf-row-odd); border-color: var(--cf-blue); color: var(--cf-blue); }
#addModal .modal-footer .btn-primary, #editModal .modal-footer .btn-primary { background: var(--cf-blue); border-color: var(--cf-blue); color: #fff; }
#addModal .modal-footer .btn-primary:hover, #editModal .modal-footer .btn-primary:hover { background: #013E76; border-color: #013E76; }

/* -- Form tables inside the modals -- */
#add_form, #edit_form { width: 100%; border-collapse: collapse; font-size: 12px; }
#add_form td:first-child, #edit_form td:first-child {
	background: var(--cf-row-odd); color: var(--cf-dark); font-weight: 600; font-size: 11px;
	padding: 8px 10px; white-space: nowrap; width: 140px; border-bottom: 1px solid var(--cf-border); vertical-align: middle;
}
#add_form td:nth-child(2), #edit_form td:nth-child(2) {
	background: #fff; padding: 7px 10px; border-bottom: 1px solid var(--cf-border); vertical-align: middle;
}

/* -- Form controls -- scoped to the modals so the data table is unaffected -- */
#addModal input[type="text"], #addModal select,
#editModal input[type="text"], #editModal select {
	height: 28px; box-sizing: border-box; font-size: 12px; font-family: var(--cf-sans);
	border: 1px solid var(--cf-border); background: #fff; color: var(--cf-dark);
	border-radius: 4px; padding: 0 8px;
}
#addModal input[type="text"]:focus, #addModal select:focus,
#editModal input[type="text"]:focus, #editModal select:focus {
	border-color: var(--cf-blue); outline: none; box-shadow: 0 0 0 2px rgba(0,82,155,.12);
}
</style>

<script language='javascript'>
function addSignatory(){
	$('#addModal').modal('show');
}

$(function() {
    $("#as_of_date").datepicker({
      changeMonth: true,
      changeYear: true,
      showAnim: "clip"
    });    
});

</script>

<script language='javascript' src='ajax.js'></script>
<?php
if(isset($_POST['as_of_date'])){
	$sql="select * from signatories where signatory_date like '".date("Y-m-d",strtotime($_POST['as_of_date']))."%%'";	
	$rs=$db->query($sql);
	$nm=$rs->num_rows;
	
	//$sig_date=$_POST['as_of_date'];
	$sig_date=date("Y-m-d",strtotime($_POST['as_of_date']));
	$gm=$_POST['general_manager'];
	$gm_office=$_POST['gm_office'];
	$director=$_POST['director'];
	$maintenance=$_POST['maintenance'];
	$chief_transport=$_POST['chief_transport'];
		
	if($nm>0){
		$row=$rs->fetch_assoc();
		$update="update signatories set general_manager='".$gm."',";
		$update.="gm_office='".$gm_office."',director_ops='".$director."',";
		$update.="maintenance_provider='".$maintenance."',chief_transport='".$chief_transport."'";
		$update.=" where id='".$row['id']."'";
		
		$updateRS=$db->query($update);
	
	
	}	
	else {
		$update="insert into signatories";
		$update.="(signatory_date,general_manager,gm_office,director_ops,maintenance_provider,chief_transport)";
		$update.=" values ";
		$update.="('".$sig_date."','".$gm."','".$gm_office."','".$director."','".$maintenance."','".$chief_transport."')";
		$updateRS=$db->query($update);
	}

}
?>
<?php

$sql="select * from signatories order by signatory_date desc";
$rs=$db->query($sql);
$nm=$rs->num_rows;

?>


<div class="ta-grid ta-console" align="center">
<div class="cf-eq-wrap">

<table cellspacing="0" cellpadding="0" class="cf-eq-toolbar">
<tr>
	<td class="cf-eq-title">
		Signatories List
		<span class="cf-eq-count"><?php echo $nm; ?> item<?php echo ($nm==1?'':'s'); ?></span>
	</td>
	<td class="cf-eq-actions">
		<a href="#" class="cf-tbtn cf-tbtn--primary" onclick="addSignatory(); return false;">Add/Edit Signatories</a>
	</td>
</tr>
</table>


<table class='train_ava'>
	<tr class='rowHeading'>
		<th>As of (Date)</th>
		<th>General Manager/OIC</th>
		<th>Director of Operations</th>
		<th>Chief, Transport</th>
		<th>Maintenance Provider</th>
	</tr>
<?php
for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
	$sigdate=date("F d, Y",strtotime($row['signatory_date']));
	$gm=$row['general_manager'];
	$gm_office=$row['gm_office'];
	$director=$row['director_ops'];
	$maintenance=$row['maintenance_provider'];
	$transport=$row['chief_transport'];
?>
	<tr class="<?php echo ($i%2>0)?'cf-row--odd':'cf-row--even'; ?>">	
		<td><?php echo $sigdate; ?></td>
		<td><?php echo $gm.", ".$gm_office; ?></td>
		<td><?php echo $director; ?></td>
		<td><?php echo $transport; ?></td>
		<td><?php echo $maintenance; ?></td>
	</tr>
<?php

}

?>
</table>
</div><!-- /.cf-eq-wrap -->
</div><!-- /.ta-grid.ta-console -->


		<div class="modal hide fade" id="addModal">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h3>Add / Edit Signatories</h3>
				</div>
				<form  action='signatories_list.php' method='post'>

							<div class="modal-body">	
							<div name='add_form' id='add_form'>
				<table>
				<!-- <thead>
				<tr><th colspan=2>Edit Signatories</th></tr>
				</thead> -->
				<tbody>
				<tr>
				<td>
				As of (Date):
				</td>
				<td>				
				<!-- <input type='text' name='as_of_date' id='as_of_date' class='datepicker' />	-->
				<input type="text" name='as_of_date' id='as_of_date'>			
				</td>
				</tr>
				<tr>
				<td>
				General Manager/OIC
				</td>
				<td>
				<input type='text' name='general_manager' />

				</td>
				</tr>
				<tr>
				<td>
				GM/OIC Position
				</td>
				<td>
				<input type=text name='gm_office'/>
				</td>
				</tr>
				<tr>
				<td>
				Director of Operations
				</td>
				<td>
				<input type=text name='director' />
				</td>
				</tr>
				<tr>
				<td>
				Chief of Transport Division
				</td>
				<td>
				<input type=text name='chief_transport' />
				</td>
				</tr>
				<tr>
				<td>
				Maintenance Provider
				</td>
				<td>

				<input type=text name='maintenance' id='maintenance'/>
				
				</td>
				</tr>
				</tbody>
				</table>

				</div>
			</div>
						
			<div class="modal-footer">
				<a href="#" class="btn" data-dismiss="modal">Close</a>
				<button type='submit' class="btn btn-primary" id='Suc' value='Submit'>Save Signatories</button>
			</div>
			  </form>
		</div>
		
		<script src="js/jquery-migrate-1.2.1.min.js"></script>	
		<script src="js/jquery-ui-1.10.3.custom.min.js"></script>	
		<script src="js/jquery.ui.touch-punch.js"></script>
		<script src="js/modernizr.js"></script>	
		<script src="js/bootstrap.min.js"></script>	
		<script src="js/date.js"></script>	
		<!--
		<script src='js/form2.js'></script>
		-->