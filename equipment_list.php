<?php 
require("Tmenu.php");
ini_set("date.timezone","Asia/Kuala_Lumpur");
?>
<?php
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_user_transport");
?>

<!--- Operations Console redesign + Edit/Delete scaffolding
//--- Uniform with incident_summary.php / train_availability.php / car_history.php
//--- Visual layer only below (--cf-* tokens); the Add form's PHP query is
//--- left byte-for-byte, the Edit form mirrors it. Marker: @console
//--------------------------------------------------------------------------->

<?php
/* =========================================================================
   SERVER-SIDE TO-DO  (front-end is wired below -- these two handlers are
   the parts left for you to finish)

   1) EDIT  -- the Edit modal posts back to THIS page (equipment_list.php).
      Detect it with isset($_POST['edit_id']).  Fields sent:
          $_POST['edit_id']         equipment.id being edited
          $_POST['equipt_type']     equipment_type.incident_code (the FK)
          $_POST['equipment_name']  the new name
      Use a prepared statement (do NOT concatenate -- same class of bug we
      flagged in incident_report.php):
          // $stmt = $db->prepare(
          //     "update equipment set equipment_name=?, type=? where id=?");
          // $stmt->bind_param("ssi",
          //     $_POST['equipment_name'], $_POST['equipt_type'], $_POST['edit_id']);
          // $stmt->execute();

   2) DELETE -- deleteEquipment() calls, via makeajax():
          processing.php?removeEquipment=<id>
      Add a matching handler in processing.php (prepared statement again):
          // if (isset($_GET['removeEquipment'])) {
          //     $stmt = $db->prepare("delete from equipment where id=?");
          //     $stmt->bind_param("i", $_GET['removeEquipment']);
          //     $stmt->execute();
          // }
   ========================================================================= */
   if(isset($_POST['edit_id'])){
	   
           /* FIX: the equipment table lives in is_transport -- the DB the list
              SELECT and the type dropdowns below all connect to -- NOT the
              is_user_transport connection $db was opened with at the top of
              this file. Point $db at the correct database before the UPDATE. */
           $db = new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");

           $stmt = $db->prepare(
               "update equipment set equipment_name=?, type=? where id=?");
           $stmt->bind_param("ssi",
               $_POST['equipment_name'], $_POST['equipt_type'], $_POST['edit_id']);
			   
           $stmt->execute();
	   
   }
   
   
   
   
   
   
   
   
?>

<link href="css/modal_only.css" rel="stylesheet" />
<!-- <link href="css/style.min.css" rel="stylesheet" /> -->

<link rel="stylesheet" href="jquery-ui-themes-1.11.1/themes/smoothness/jquery-ui.css" />
<script src="jquery-ui-1.11.1/external/jquery/jquery.js"></script>
<script src="jquery-ui-1.11.1/jquery-ui.js"></script>
	
<style type='text/css'>

/* color background
.rowClass {
	background-color: #F3F3F3;
}
*/

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

/* outline header
 .train_ava th {
	border: 1px solid #A9A9A9;
	cellpadding: 5px;	
}
*/

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
   EQUIPMENT LIST -- Operations Console Theme
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
.ta-grid.ta-console .cf-eq-wrap { width: 96%;  max-width: 900px; }

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

/* Edit: populate the edit modal from the row's data-* attributes, then open
   it. The form posts back to equipment_list.php (see server-side TO-DO up top). */
function editEquipment(link){
	$('#edit_id').val(link.getAttribute('data-id'));
	$('#edit_equipment_name').val(link.getAttribute('data-name'));
	$('#edit_equipt_type').val(link.getAttribute('data-type'));
	$('#editModal').modal('show');
}

/* Delete: AJAX to processing.php, then reload -- same pattern as incident
   summary's deleteIncident(). Implement removeEquipment server-side. */
function deleteEquipment(id){
	if(confirm("Remove this equipment?")){
		makeajax("processing.php?removeEquipment="+encodeURIComponent(id),"reloadPage");
	}
}

function reloadPage(ajaxHTML){
	self.location="equipment_list.php";
}
</script>

<script language='javascript' src='ajax.js'></script>
<?php
?>
<?php


	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
/* Added equipment.id (for edit/delete) and equipment.type as type_code (so the
   Edit modal can pre-select the current type). Join clause left unchanged. */
$sql="select equipment.id as id, equipment.equipment_name as equipt, equipment.type as type_code, equipment_type.equipment_name as type from equipment inner join equipment_type on type=incident_code order by equipment.equipment_name";
$rs=$db->query($sql);
$nm=$rs->num_rows;


?>
<br>
<div class="ta-grid ta-console" align="center">
<div class="cf-eq-wrap">

<!-- toolbar -->
<table cellspacing="0" cellpadding="0" class="cf-eq-toolbar">
<tr>
	<td class="cf-eq-title">
		Equipment List
		<span class="cf-eq-count"><?php echo $nm; ?> item<?php echo ($nm==1?'':'s'); ?></span>
	</td>
	<td class="cf-eq-actions">
		<a href="#" class="cf-tbtn cf-tbtn--primary" onclick="addSignatory(); return false;">+ Add Equipment</a>
	</td>
</tr>
</table>

<!-- data table -->
<table class='train_ava'>
	<tr class='rowHeading'>
		<th>Equipment Name</th>
		<th>Equipment Type</th>
		<th>Actions</th>
	</tr>
<?php
if($nm==0){
?>
	<tr class="cf-row--even">
		<td class="cf-empty" colspan="3">No equipment recorded yet. Use &ldquo;+ Add Equipment&rdquo; to create the first entry.</td>
	</tr>
<?php
}
for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
?>
	<tr class="<?php echo ($i%2>0)?'cf-row--odd':'cf-row--even'; ?>">	
		<td><?php echo htmlspecialchars($row['equipt']); ?></td>
		<td><?php echo htmlspecialchars($row['type']); ?></td>
		<td class="cf-actions">
			<a href="#" class="LEdit"
			   data-id="<?php echo htmlspecialchars($row['id'], ENT_QUOTES); ?>"
			   data-name="<?php echo htmlspecialchars($row['equipt'], ENT_QUOTES); ?>"
			   data-type="<?php echo htmlspecialchars($row['type_code'], ENT_QUOTES); ?>"
			   onclick="editEquipment(this); return false;">Edit</a>
			<a href="#" class="LDel"
			   data-id="<?php echo htmlspecialchars($row['id'], ENT_QUOTES); ?>"
			   onclick="deleteEquipment(this.getAttribute('data-id')); return false;">&times;</a>
		</td>
	</tr>
<?php

}

?>
</table>

</div><!-- /.cf-eq-wrap -->
</div><!-- /.ta-grid.ta-console -->


		<!-- ============================ ADD MODAL ============================ -->
		<div class="modal hide fade" id="addModal">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h3>Add Equipment</h3>
				</div>
				<form  action='equipment_list.php' method='post'>

							<div class="modal-body">	
							<div name='add_form' id='add_form'>
				<table>
				<!-- <thead><tr><th colspan=2>Add Equipment</th></tr></thead> -->
				<tbody>
				<tr>
				<td>
				Equipment Type
				</td>
				<td>				
				
				<select name='equipt_type'>
				<?php
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
				$sql="select * from equipment_type";
				
				$rs=$db->query($sql);
				$nm=$rs->num_rows;
				
				for($l=0;$l<$nm;$l++){
					$row=$rs->fetch_assoc();
				?>	
					<option value='<?php echo $row['incident_code']; ?>' >
					<?php echo $row['equipment_name']; ?>
					
					</option>
				<?php	
				}
				
				
				
				
				?>
				
				</select>

				</td>
				</tr>
				<tr>
				<td>
				Equipment Name
				</td>
				<td>
				<input type='text' name='equipment_name' />

				</td>
				</tr>
				</tbody>
				</table>

				</div>
			</div>
						
			<div class="modal-footer">
				<a href="#" class="btn" data-dismiss="modal">Close</a>
				<button type='submit' class="btn btn-primary" id='Suc' value='Submit'>Add Equipment</button>
			</div>
			  </form>
		</div>

		<!-- ============================ EDIT MODAL ===========================
		   Mirrors the Add modal. The hidden edit_id field is what the
		   server uses to tell an edit apart from an add
		   (isset($_POST['edit_id'])). editEquipment() fills these in and
		   pre-selects the current type before showing the modal.
		   ================================================================== -->
		<div class="modal hide fade" id="editModal">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h3>Edit Equipment</h3>
				</div>
				<form action='equipment_list.php' method='post'>
				<input type='hidden' name='edit_id' id='edit_id' value='' />

							<div class="modal-body">	
							<div name='edit_form' id='edit_form'>
				<table>
				<tbody>
				<tr>
				<td>
				Equipment Type
				</td>
				<td>

				<select name='equipt_type' id='edit_equipt_type'>
				<?php
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
				$sql="select * from equipment_type";

				$rs=$db->query($sql);
				$nm=$rs->num_rows;

				for($l=0;$l<$nm;$l++){
					$row=$rs->fetch_assoc();
				?>
					<option value='<?php echo $row['incident_code']; ?>' >
					<?php echo $row['equipment_name']; ?>

					</option>
				<?php
				}
				?>

				</select>

				</td>
				</tr>
				<tr>
				<td>
				Equipment Name
				</td>
				<td>
				<input type='text' name='equipment_name' id='edit_equipment_name' />

				</td>
				</tr>
				</tbody>
				</table>

				</div>
			</div>

			<div class="modal-footer">
				<a href="#" class="btn" data-dismiss="modal">Close</a>
				<button type='submit' class="btn btn-primary" value='Update'>Save Changes</button>
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