<?php 
session_start();
?>
<?php
require("Tmenu.php");
require_once("db_config.php"); /* centralized credentials -- see db_config.php */
?>

<!--- Modified by Jun
//--- Date: 7/29/2014
//--- Modify: modify screen layout
//--- Marker: @mjun
//--------------------------------------------------->

<!--
	<link href="css/style.min.css" rel="stylesheet" /> -->
<!--	
<link rel="stylesheet" type="text/css" href="../../information_sharing/transport/jquery-easyui-1.4/themes/gray/easyui.css" />
<link rel="stylesheet" type="text/css" href="../../information_sharing/transport/jquery-easyui-1.4/themes/icon.css" />
<link rel="stylesheet" type="text/css" href="../../information_sharing/transport/jquery-easyui-1.4/demo/demo.css" />
<script type="text/javascript" src="../../information_sharing/transport/jquery-easyui-1.4/jquery.min.js"></script>
<script type="text/javascript" src="../../information_sharing/transport/jquery-easyui-1.4/jquery.easyui.min.js"></script> 
-->	

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

.stat-toolbar {
	background:#00529B; border-bottom:3px solid #FDB813;
	border-radius:6px 6px 0 0; padding:10px 16px; margin-bottom:0;
}
.stat-toolbar table { border-collapse:collapse; }
.stat-toolbar th, .stat-toolbar td { border:none !important; padding:4px 8px; color:#FFFFFF; font-weight:600; font-size:13px; text-align:left; }
.stat-toolbar select, .stat-toolbar input[type=text] {
	height:26px; border:1px solid rgba(255,255,255,.5); border-radius:4px;
	background:#FFFFFF; color:#1A2238; padding:0 8px; font-size:12px;
}
.stat-toolbar input[type=submit] {
	height:28px; border:none; border-radius:4px; background:#FDB813;
	color:#3A2D00; font-weight:700; font-size:12px; padding:0 14px; cursor:pointer;
}
.stat-toolbar input[type=submit]:hover { background:#E5A50F; }

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
   CLEARANCE FORM -- Operations Console Theme
   Uniform with train_availability_console.php / incident_report_console.php
   Scoped under .ta-grid.ta-console so it doesn't bleed into other pages.
   PHP/JS: completely unchanged below -- only CSS and a wrapper div added,
   plus class-based row striping in place of the old inline echo "class=".
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

/* -- Page chrome (outside .ta-grid, kept minimal) -- */
body { font-family: var(--cf-sans); color: var(--cf-dark); }

/* #navMenu's layout (previously a float-collapsed list needing a local
   clearfix workaround here) is now fixed directly in Tmenu_2.php as a
   proper flex row (Option B: menu below the header band) -- no local
   workaround needed on this page anymore. */

/* The header-wrapper spacing and #navMenu layout (Option B: menu as its
   own row below the header band) are now fixed at the source, in
   trans_menu_2.php and Tmenu_2.php respectively -- both shared across
   clearance_form.php, edit_ccdr.php, incident_report.php, and
   train_availability.php, so the fix applies to all four instead of
   four separate local copies that can drift out of sync (exactly what
   produced the left:40px vs left:0 / width:55% vs width:100%
   inconsistencies found between the existing Tmenu/Tmenu_2 and trans
   menu/trans menu_2 file pairs). No local override needed here. */

/* -- Toolbar: single row matching train_availability.php's layout --
   date display (left) / search+Go (center) / action buttons (right),
   all in one table row instead of two stacked blocks. Colors reuse the
   same --cf-blue/--cf-gold tokens train_availability.php hardcodes as
   #00529B/#FDB813, so the two pages stay visually identical. */
.ta-grid.ta-console .cf-toolbar-table {
	width: 100% !important;
	background: var(--cf-blue) !important;
	border-bottom: 3px solid var(--cf-gold) !important;
	border-collapse: collapse !important;
	border-radius: 8px 8px 0 0 !important;
	overflow: hidden;
}
.ta-grid.ta-console .cf-toolbar-table td {
	padding: 8px 14px !important;
	vertical-align: middle !important;
	border: none !important;
	white-space: nowrap !important;
}
.ta-grid.ta-console .cf-td-date { width: 1%; }
.ta-grid.ta-console .cf-date-label { font-size: 15px; font-weight: 700; color: #fff; }
.ta-grid.ta-console .cf-date-day   { font-size: 11px; color: rgba(255,255,255,.6); margin-left: 8px; }
.ta-grid.ta-console .cf-td-search  { text-align: center; }
.ta-grid.ta-console .cf-td-actions {
	text-align: right;
	white-space: normal !important; /* override the global nowrap on toolbar
		cells -- degrade to two lines instead of clipping "+ Add New Entry"
		off past the edge when the row runs short on horizontal space */
}
.ta-grid.ta-console .cf-tbtn { margin-top: 3px !important; }
.ta-grid.ta-console .cf-toolbar-table input[type="text"] {
	height: 26px !important;
	font-size: 12px !important;
	font-weight: 400 !important;
	font-family: var(--cf-sans) !important;
	background: var(--cf-white) !important;
	color: var(--cf-dark) !important;
	border: 1px solid rgba(255,255,255,.5) !important;
	border-radius: 4px !important;
	padding: 0 7px !important;
	width: 120px !important;
	vertical-align: middle !important;
}
.ta-grid.ta-console .cf-toolbar-table input[type="submit"] {
	height: 26px !important;
	font-size: 11px !important;
	font-weight: 700 !important;
	font-family: var(--cf-sans) !important;
	background: var(--cf-gold) !important;
	color: #3A2D00 !important;
	border: none !important;
	border-radius: 4px !important;
	padding: 0 12px !important;
	cursor: pointer !important;
	vertical-align: middle !important;
	margin-left: 4px !important;
}
.ta-grid.ta-console .cf-toolbar-table input[type="submit"]:hover { background: #E5A50F !important; }

/* Action buttons: outlined (Add New Entry) / gold-filled (Generate
   Printout) pills, same treatment as train_availability's +Add Train /
   Generate Printout buttons. $SRemove / $SRemove2 (unchanged PHP) still
   control enabled vs disabled -- a.disabled below overrides these via
   !important when they apply, so the existing permission logic keeps
   working exactly as it did before. */
.ta-grid.ta-console .cf-tbtn {
	display: inline-block !important;
	font-size: 11px !important;
	font-weight: 500 !important;
	color: #fff !important;
	text-decoration: none !important;
	padding: 4px 10px !important;
	border: 1px solid rgba(255,255,255,.35) !important;
	border-radius: 3px !important;
	margin-left: 6px !important;
}
.ta-grid.ta-console .cf-tbtn:hover { background: rgba(255,255,255,.12) !important; }
.ta-grid.ta-console .cf-tbtn--primary {
	font-weight: 600 !important;
	color: #3A2D00 !important;
	background: var(--cf-gold) !important;
	border-color: var(--cf-gold) !important;
}
.ta-grid.ta-console .cf-tbtn--primary:hover { background: #E5A50F !important; }
/* -- Data table -- */
.ta-grid.ta-console table.train_ava {
	width: 100%;
	border-collapse: collapse;
	font-size: 12px;
}
.ta-grid.ta-console table.train_ava td,
.ta-grid.ta-console table.train_ava th {
	border: 1px solid var(--cf-border);
	padding: 7px 9px;
	text-align: left;
}
.ta-grid.ta-console table.train_ava th {
	background: var(--cf-blue);
	color: #fff;
	font-weight: 600;
	font-size: 11px;
	text-align: center;
	border-color: #0A639E;
}
.ta-grid.ta-console table.train_ava tr.rowHeading th { background: var(--cf-blue); }
.ta-grid.ta-console table.train_ava tr.cf-row--even td { background: var(--cf-white); }
.ta-grid.ta-console table.train_ava tr.cf-row--odd td  { background: var(--cf-row-odd); }
.ta-grid.ta-console table.train_ava tbody tr:hover td { background: #E3EEFA; }
.ta-grid.ta-console table.train_ava td:first-child { text-align: center; font-weight: 600; color: var(--cf-blue); width: 36px; }

/* -- Inline Edit / Delete links inside cells -- */
/* Inline per-cell Edit links: there are up to 8 of these in a single row
   (one per editable field), so a permanently-visible bordered button per
   link reads as noisy and cluttered next to the actual data. a.LEdit is
   fully invisible (opacity 0) until the row is hovered, at which point it
   appears as a small pill -- a light outline at rest, filling solid blue
   only when the pill itself is hovered/targeted. a.Llink (Add New Entry /
   Generate Printout, outside the table) keeps the older plain-text
   treatment since it isn't a per-row repeated affordance.

   !important is used throughout this block deliberately: this page loads
   css/style.min.css and css/bootstrap.min.css BEFORE this stylesheet, and
   neither file's contents are visible from here. Bootstrap in particular
   typically sets a uniform default <a> color/text-decoration with enough
   reach that a same-specificity rule loaded later doesn't reliably win
   against it in practice. The uniform solid-blue underlined links seen in
   testing -- identical across every row regardless of hover state -- are
   the signature of that kind of generic rule winning, not a row-specific
   bug. !important forces these specific rules to apply regardless of
   what either external stylesheet contains. */
.ta-grid.ta-console a.Llink {
	display: inline-block !important;
	font-size: 10px !important;
	font-weight: 600 !important;
	text-decoration: none !important;
	margin-left: 6px !important;
	padding: 1px 6px !important;
	border-radius: 3px !important;
	border: 1px solid transparent !important;
	background: transparent !important;
	color: var(--cf-muted) !important;
	opacity: .55 !important;
	transition: opacity .12s, background .12s, border-color .12s, color .12s;
}
.ta-grid.ta-console a.Llink:hover {
	opacity: 1 !important;
	color: var(--cf-blue) !important;
	background: var(--cf-row-odd) !important;
	border-color: var(--cf-border) !important;
}

/* -- a.LEdit: pill-shaped, hidden until the specific cell is hovered --
   scoped to td:hover (not tr:hover) so hovering one field -- e.g.
   Location -- doesn't pop all 8 Edit pills in the row at once; only
   that field's own pill appears. */
.ta-grid.ta-console a.LEdit {
	display: inline-flex !important;
	align-items: center !important;
	font-size: 10px !important;
	font-weight: 600 !important;
	text-decoration: none !important;
	margin-left: 6px !important;
	padding: 2px 9px !important;
	border-radius: 999px !important;
	border: 1px solid var(--cf-border) !important;
	background: var(--cf-white) !important;
	color: var(--cf-muted) !important;
	opacity: 0 !important;
	transform: translateY(1px);
	transition: opacity .12s, background .12s, border-color .12s, color .12s, transform .12s;
}
.ta-grid.ta-console table.train_ava td:hover a.LEdit {
	opacity: 1 !important;
	transform: translateY(0);
}
.ta-grid.ta-console a.LEdit:hover {
	background: var(--cf-blue) !important;
	border-color: var(--cf-blue) !important;
	color: #fff !important;
}
/* The row-level delete (X) link stays a touch more visible at rest than
   the per-field edit links, since it's a single destructive action per
   row rather than one of eight repeated affordances, and warrants being
   a little easier to locate without hovering first. */
.ta-grid.ta-console a.LDel {
	display: inline-block !important;
	font-size: 11px !important;
	font-weight: 700 !important;
	text-decoration: none !important;
	color: #B23A33 !important;
	opacity: .7 !important;
	padding: 1px 6px !important;
	border-radius: 3px !important;
	border: 1px solid transparent !important;
	background: transparent !important;
	transition: opacity .12s, background .12s, border-color .12s;
}
.ta-grid.ta-console a.LDel:hover {
	opacity: 1 !important;
	background: #FDEDEC !important;
	border-color: #F1C3C0 !important;
}
.ta-grid.ta-console a.two {
	font-weight: 600 !important; color: var(--cf-blue) !important; text-decoration: none !important;
}
.ta-grid.ta-console a.two:hover { text-decoration: underline !important; }
/* Disabled links: originally scoped only to .alink (the Add New Entry /
   Generate Printout action bar), so a.disabled inside the data table
   (per-row Edit/Delete, when $ULev < 2) fell through with zero styling
   -- rendering as a plain default blue link, fully visible and, worse,
   still clickable, since the JS href-stripping below is also scoped to
   .alink only. Widening the CSS selector here fixes the *look* (muted,
   non-interactive) for every disabled link in the console, table
   included. It does not by itself stop the click -- that's a JS-side
   href removal this pass intentionally leaves untouched per the
   CSS/structure-only scope of this work; flagging it separately. */
.ta-grid.ta-console a.disabled {
	color: var(--cf-muted) !important;
	text-decoration: none !important;
	cursor: default !important;
	opacity: .4 !important;
	pointer-events: none !important;
}
.ta-grid.ta-console table.train_ava a.disabled {
	display: inline-flex !important;
	align-items: center !important;
	font-size: 10px !important;
	margin-left: 6px !important;
	padding: 2px 9px !important;
	border-radius: 999px !important;
	border: 1px solid var(--cf-border) !important;
	background: var(--cf-bg) !important;
	opacity: 0 !important;
	transform: translateY(1px);
	transition: opacity .12s, transform .12s;
}
.ta-grid.ta-console table.train_ava tr:hover a.disabled {
	opacity: .4 !important;
	transform: translateY(0);
}

/* -- Modal shell -- console theme, matches the other two pages -- */
.modal { z-index: 99999; }
#addModal {
	border-radius: 8px;
	overflow: hidden;
	border: none;
	box-shadow: 0 8px 32px rgba(0,30,80,.18), 0 2px 8px rgba(0,30,80,.10);
	font-family: var(--cf-sans);
	min-width: 380px;
}
#addModal .modal-header {
	background: var(--cf-blue);
	border-bottom: 3px solid var(--cf-gold);
	padding: 10px 16px;
}
#addModal .modal-header h3 { color: #fff; font-size: 13px; font-weight: 600; margin: 0; }
#addModal .modal-header .close { color: rgba(255,255,255,.7); text-shadow: none; opacity: 1; font-size: 18px; }
#addModal .modal-header .close:hover { color: var(--cf-gold); }
#addModal .modal-body { background: var(--cf-bg); padding: 16px 18px; }
#addModal .modal-footer {
	background: #fff;
	border-top: 1px solid var(--cf-border);
	padding: 10px 16px;
	display: flex;
	justify-content: flex-end;
	gap: 8px;
}
#addModal .modal-footer .btn {
	font-size: 12px; font-weight: 500; padding: 5px 16px; border-radius: 4px;
	border: 1px solid var(--cf-border); background: #fff; color: var(--cf-mid); text-decoration: none;
}
#addModal .modal-footer .btn:hover { background: var(--cf-row-odd); border-color: var(--cf-blue); color: var(--cf-blue); }
#addModal .modal-footer .btn-primary { background: var(--cf-blue); border-color: var(--cf-blue); color: #fff; }
#addModal .modal-footer .btn-primary:hover { background: #013E76; border-color: #013E76; }

/* -- #add_form (the dynamically-built edit form inside the modal) -- */
#add_form { width: 100%; border-collapse: collapse; font-size: 12px; }
#add_form td:first-child {
	background: var(--cf-row-odd); color: var(--cf-dark); font-weight: 600;
	font-size: 11px; padding: 7px 10px; white-space: nowrap; width: 140px;
	border-bottom: 1px solid var(--cf-border); vertical-align: middle;
}
#add_form td:nth-child(2) {
	background: #fff; padding: 6px 10px; border-bottom: 1px solid var(--cf-border); vertical-align: middle;
}
#add_form td[colspan] { background: var(--cf-bg); text-align: center; padding: 10px; border-bottom: none; }

/* -- Form controls -- scoped to #addModal so the data table is unaffected -- */
#addModal input[type="text"],
#addModal select {
	height: 28px; font-size: 12px; font-family: var(--cf-sans);
	border: 1px solid var(--cf-border); background: #fff; color: var(--cf-dark);
	border-radius: 4px; padding: 0 8px; box-sizing: border-box;
}
#addModal input[type="text"]:focus,
#addModal select:focus { border-color: var(--cf-blue); outline: none; box-shadow: 0 0 0 2px rgba(0,82,155,.12); }
#addModal textarea {
	font-size: 12px; font-family: var(--cf-sans); border: 1px solid var(--cf-border);
	background: #fff; color: var(--cf-dark); border-radius: 4px; padding: 6px 8px;
	width: 100%; box-sizing: border-box; resize: vertical;
}
#addModal textarea:focus { border-color: var(--cf-blue); outline: none; }
#addModal input[type="submit"] {
	height: 30px; font-size: 12px; font-weight: 600; font-family: var(--cf-sans);
	background: var(--cf-blue); color: #fff; border: 1px solid var(--cf-blue);
	border-radius: 4px; padding: 0 18px; cursor: pointer;
}
#addModal input[type="submit"]:hover { background: #013E76; }
/* time selects in the login/logout form stay compact and inline */
#addModal select[name$="_hour"],
#addModal select[name$="_minute"],
#addModal select[name$="_second"],
#addModal select[name$="_amorpm"] { width: auto; display: inline-block; margin-right: 3px; }

</style>
<script language='javascript' src='ajax.js'></script>
<script language='javascript'>
function deleteIncident(index){
	var check=confirm("Remove Record?");
	if(check){
	makeajax("processing.php?removeIncident="+index,"reloadPage");	
	}
}

function reloadPage(ajaxHTML){
	self.location="incident summary.php";
	//self.location.reload();

}


$(function() {
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
<body>
<?php
$mm=date("m");
$yy=date("Y");
$dd=date("d");

$hh=date("h");

$min=date("i");
$aa=date("a");

$datenow=date("m/d/Y");
$availability_date=date("Y-m-d");

if(isset($_SESSION['search_date'])){
//$month=$_SESSION['month'];
//$day=$_SESSION['day'];
//$year=$_SESSION['year'];

$availability_date=$_SESSION['search_date'];

$datenow=date("m/d/Y",strtotime($availability_date));
}
?>
<div class="ta-grid ta-console">

<table cellspacing="0" cellpadding="0" class='stat-toolbar'>
<tr>
	<td style="padding:8px 14px;vertical-align:middle;white-space:nowrap;width:1%;border:none">
		<form action='incident summary.php' method='post' >
<div width="50%" align=left>
<table>

<th>From</th>
<td> <input type="text" name='search_date2' id='search_date2'> 
</td>
<th>To</th>
<td> <input type="text" name='search_date' id='search_date'>
</td>


<th><input type=submit value='Submit' /></th>
</tr>
</table>
</form>
	</td>
		<td style="padding:8px 14px;vertical-align:middle;text-align:center;border:none">


	<?php
if(isset($_POST['search_date2'])){
//$month=$_POST['month'];
//$day=$_POST['day'];
//$year=$_POST['year'];

//$_SESSION['month']=$month;
//$_SESSION['day']=$day;
//$_SESSION['year']=$year;

$availability_date=date("Y-m-d",strtotime($_POST['search_date2']));
$datenow=date("m/d/Y",strtotime($_POST['search_date2']));


if(isset($_POST['search_date'])){
	$availability_date2=date("Y-m-d",strtotime($_POST['search_date']));
//	$datenow=$datenow.=" - ".date("m/d/Y",strtotime($_POST['search_date']));
	$_SESSION['search_date']=$_POST['search_date'];
	
	
}
else {
	$_SESSION['search_date']="";
	$availability_date2="";
}



$_SESSION['search_date2']=$_POST['search_date2'];

}
else {
if(isset($_SESSION['search_date2'])){

$availability_date=date("Y-m-d",strtotime($_SESSION['search_date2']));
$datenow=date("m/d/Y",strtotime($_SESSION['search_date2']));

if(isset($_SESSION['search_date'])){
	$availability_date2=date("Y-m-d",strtotime($_SESSION['search_date']));
//	$datenow=$datenow.=" - ".date("m/d/Y",strtotime($_SESSION['search_date']));
	$_SESSION['search_date']=$_POST['search_date'];
	
	
}




}
else {

$availability_date=date("Y-m-d");
$datenow=date("m/d/Y");

}

}
//$timetable=date("Y-m-d",strtotime($_POST['search_date']));

$displayDate=date("F d, Y",strtotime($availability_date));

if($availability_date2==""){
	
	$ccdr_date="like '".$availability_date."%%' "; 
}
else {
	$ccdr_date="between '".$availability_date." 00:00:00' and '".$availability_date2." 23:59:59' ";
	$displayDate.=" - ".date("F d, Y",strtotime($availability_date2));
}



//$timetable=date("Y-m-d",strtotime($_POST['search_date']));
echo "<h2>".$displayDate."</h2>";
?>
		</td>

	<td style="padding:8px 14px;vertical-align:middle;text-align:right;white-space:nowrap;border:none">
<form action='incident summary.php' method='post'>
Sort By:
<select name='sort_by' id='sort_by'>
<option></option>
<option value='level ascending'>Level Ascending</option>
<option value='1'>All Level 1</option>
<option value='2'>All Level 2</option>
<option value='3'>All Level 3</option>
<option value='4'>All Level 4</option>
</select>
<input type='submit' value='Sort' />
</form>
	</td>
</tr>
</table>


<a href='#' class="two pull-right"  onclick='window.open("generate_ccdr.php?ccdr=<?php echo $availability_date; ?>&ccdr2=<?php echo $availability_date2; ?>");'><b>Generate Printout</b></a>
 | 
<a href='#' class="two pull-right"  onclick='window.open("generate_nis.php?ccdr=<?php echo $availability_date; ?>&ccdr2=<?php echo $availability_date2; ?>");'><b>Generate (New Format) Printout</b></a>
|
<a href='#' class="two pull-right"  onclick='window.open("weekly_printout.php?ccdr=<?php echo $availability_date; ?>&ccdr2=<?php echo $availability_date2; ?>");'><b>Generate Weekly Printout</b></a>

<!-- header -->
<table width=95% class='train_ava'>
<tr class='rowHeading'>
<th rowspan=2>Incident No.</th>
<th rowspan=2>Incident Date/Time</th>
<th rowspan=2>Time Resolved</th>

<th rowspan=2>Incident<br> Duration</th>
<th rowspan=2>Description</th>
<th colspan=2>Action Taken</th>
<th rowspan=2>Level<br> Status</th>
<th rowspan=2>Additional<br> Defects</th>
</tr>
<tr class='rowHeading'>
<th>DOTC</th>
<th>Maintenance Provider (TESP/Other)</th>
</tr>

<?php
	$db2=iss_db('external');

//$ccdr_date=date("Y-m-d",strtotime($year."-".$month."-".$day));
//$ccdr_date=$availability_date;
	$db=iss_db('transport');

/* item #3 fix: position('' IN incident_no) always returns 1, so this sorted by just
   the FIRST CHARACTER of the incident number -- verified against a live MySQL
   instance. Commented out rather than removed, per your request:
$clause=" order by substring(incident_no,1,position('' in incident_no))*1 ";
*/
$clause=" order by substring(incident_no,1,position(' ' in incident_no)-1)*1 ";

if(isset($_POST['sort_by'])){
	if($_POST['sort_by']==""){
	
	}
	else {
		if($_POST['sort_by']=="level ascending"){
			$clause=" order by level asc";
		
		}
		else if($_POST['sort_by']=="1"){
			$clause=" and level='1'".$clause;
		}
		else if($_POST['sort_by']=="2"){
			$clause=" and level='2'".$clause;
		}
		else if($_POST['sort_by']=="3"){
			$clause=" and level='3'".$clause;
		}
		else if($_POST['sort_by']=="4"){
			$clause=" and level='4'".$clause;
		}
	
	}


}



//$sql="select * from incident_report where incident_date like '".$ccdr_date."%%' order by incident_date";
$sql="select * from incident_report inner join incident_description on incident_report.id=incident_id where incident_date ".$ccdr_date.$clause;

$rs=$db->query($sql);

$nm=$rs->num_rows;
for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
	
		$car[0]="";
		$car[1]="";
		$car[2]="";
		$car[3]=""; /* item #2 fix: fourth car was never read here */

		$carClause="";
		$carSQL="select * from incident_cars where incident_id='".$row['incident_id']."'";
		$carRS=$db->query($carSQL);
		$carNM=$carRS->num_rows;
		
		if($carNM>0){
			for($b=0;$b<$carNM;$b++){
				$carRow=$carRS->fetch_assoc();
				$car[$b]=$carRow['car_no'];
			}			
			
			$carClause=$car[0];
			if($car[1]==""){
			}
			else {
				$carClause.=", ".$car[1];
			}
			
			if($car[2]==""){
			}
			else {
				$carClause.=", ".$car[2];
			}
			
			if($car[3]==""){
			}
			else {
				$carClause.=", ".$car[3];
			}
			
		}
	$incident_type=$row['incident_type'];
		
	$description="";	
	$hourStamp=date("Y-m-d Hia",strtotime($row['incident_date']));
	$location=$row['location'];
	$reported_by=$row['reported_by'];

		if($incident_type=="rolling"){
			if($carClause==""){ } else { $carClause=" Car(s) ".$carClause.", "; }
			
			$direction=$row['direction'];
			/* item #7 fix: SB/NB never got spelled out the way D/ML do below, so the
			   description used to end with a raw code ("...  SB," / "...  NB,"). S means
			   "station" (not a direction), so it is blanked rather than spelled out --
			   matching how edit_ccdr.php already treats S on its own display. Confirmed
			   2026-07: S=Station, SB=Southbound, NB=Northbound, D=Depot, ML=Mainline. */
			if($direction=="S"){ $location="Stn. ".$location; $direction=""; }
			else if($direction=="SB"){ $location="Stn. ".$location; $direction="Southbound"; }
			else if($direction=="NB"){ $location="Stn. ".$location; $direction="Northbound"; }
			else if($direction=="D"){ $direction="Depot"; }
			else if($direction=="ML"){ $direction="Mainline"; }
			/* item #7 fix: omit the "  " separator when $direction was blanked (S), so the
			   description reads "Stn. Ayala, ..." instead of "Stn. Ayala  , ...". */
			$description="Index #".$row['index_no'].",".$carClause.$location.($direction!=""?"  ".$direction:"").", ".$row['description'].", Reported By ".$reported_by.", ";
		
		}
		else if(($incident_type=="unload")||($incident_type=='nload')){
			if($carClause==""){ } else { $carClause=" Car(s) ".$carClause.", "; }
			
			$description="Index #".$row['index_no'].",".$carClause.", ".$row['description'].", Reported By ".$reported_by.", ";



		}
		else {
			$description.=$row['description'].", Reported By ".$reported_by;
		}
	
?>
			<tr class="<?php echo ($i%2>0)?'cf-row--odd':'cf-row--even'; ?>">
						
			
<td align=center>
<?php 

/**<a href='#' class="two2" onclick='window.open("edit_ccdr.php?ir=<?php echo $row['incident_id']; ?>")'><?php echo $row['incident_no']; ?></a></td>
*/

$no=$row['incident_no'];
$id=$row['incident_id'];

?>

<a href='#' class="two2" onclick='openSlidePanel("edit_ccdr.php?ir=<?php echo $id; ?>&embed=1","Incident - <?php echo htmlspecialchars($no); ?>")'><?php echo $row['incident_no']; ?></a></td><td align=center><?php echo $hourStamp; ?></td>
<td align=center>&nbsp;</td>

<td><?php echo $row['duration']; ?></td>
<td><?php echo $description; ?></td>
<td><?php echo $row['action_dotc']; ?></td>
<td><?php echo $row['action_maintenance']; ?></td>
<td align=center><?php echo $row['level']; ?></td>
<td>
<?php
$defectsSQL="select * from incident_defects where incident_id='".$row['incident_id']."'";

$defectsRS=$db2->query($defectsSQL);
$defectsNM=$defectsRS->num_rows;
if($defectsNM>0){
	for($n=0;$n<$defectsNM;$n++){
		$defectsRow=$defectsRS->fetch_assoc();

		$equiptSQL="select * from equipment where id='".$defectsRow['equipt_id']."' limit 1";
		$equiptRS=$db->query($equiptSQL);
		$equiptRow=$equiptRS->fetch_assoc();
		
		$eq_name=$equiptRow['equipment_name'];
		
		
		
		if($n==0){
			echo $eq_name;
		}
		else {
			echo ", ".$eq_name;
		
		}
	}
}
?>
</td>
<td valign=center align=center><a href='#' class="LDel" onclick='deleteIncident("<?php echo $row['incident_id']; ?>")'>X</a></td>
</tr>
<?php
}
?>
</table>
</div>
<!--
<?php
if ($nm<>0) {
?>
<br>
<a href='#' class="two" onclick='window.open("generate_ccdr.php?ccdr=<?php echo $ccdr_date; ?>");'><b>Generate Printout</b></a>
<?php
}
?>
-->
<?php require("slide_panel.php"); ?>
</body>

<!--
		<script src="js/jquery-1.10.2.min.js"></script>
	<script src="js/jquery-migrate-1.2.1.min.js"></script>	
		<script src="js/jquery-ui-1.10.3.custom.min.js"></script>	
		<script src="js/jquery.ui.touch-punch.js"></script>	
		<script src="js/modernizr.js"></script>	
		<script src="js/bootstrap.min.js"></script>	


<script src="js/date.js"></script>	
-->