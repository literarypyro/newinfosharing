<?php
require("Tmenu_2.php");
global $varR;
?>

<!--Modify: mjun
 Modified date: Aug 5, 2014
 Modified: Change screen layout
-->

<link href="css/style.min.css" rel="stylesheet" /> 
<link href="css/bootstrap.min.css" rel="stylesheet" /> 


<link rel="stylesheet" href="jquery-ui-themes-1.11.1/themes/smoothness/jquery-ui.css" />
<script src="jquery-ui-1.11.1/external/jquery/jquery.js"></script>
<script src="jquery-ui-1.11.1/jquery-ui.js"></script>

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

/* Workaround for Tmenu_2.php's #navMenu: every <li> and <a> inside it is
   float:left with nothing clearing the float afterward, so the <ul> itself
   collapses to zero height. The page's original three manual <br> tags
   were almost certainly hand-compensating for that collapse rather than
   fixing it. Since Tmenu_2.php is shared across other pages and shouldn't
   be edited here, this clearfix is scoped to #navMenu specifically and
   only restores the height the float collapse was removing -- it changes
   nothing for any float that's already cleared elsewhere, so it's a safe,
   page-local fix rather than a shared-file edit. */
#navMenu::after {
	content: "";
	display: table;
	clear: both;
}

/* Second, separate header block, now confirmed from reading the actual
   trans_menu_2.php (required by Tmenu_2.php at its very top, before
   #navMenu is even output). This block renders BEFORE the nav menu:
   a .header-wrapper div containing a full-width <table class='exception'>
   with a 100px-tall logo image, followed by a second full-width <table>
   for the "Log Out" link and "Hello, {name}!" greeting. Neither table has
   compact sizing or margin-collapse control, and this is a wholly
   different source of vertical space than #navMenu's float-collapse --
   the earlier clearfix never touched it because it's a separate element
   entirely. This tightens that block's own spacing without altering
   trans_menu_2.php or Tmenu_2.php, both of which are shared elsewhere. */
.header-wrapper table.exception {
	margin-bottom: 0 !important;
}
.header-wrapper table.exception td {
	padding: 4px 8px !important;
}
.header-wrapper table.exception img {
	height: 56px !important; /* the source markup hardcodes height="100" on the <img> itself,
	                              which HTML attribute height takes priority over CSS in some
	                              browsers for replaced elements; this override still reduces
	                              the visual footprint where the browser does respect it */
}
.header-wrapper table:not(.exception) {
	margin: 0 !important;
}
.header-wrapper table:not(.exception) td {
	padding: 2px 8px !important;
}
.header-wrapper h0 {
	font-size: 22px !important; /* the source sets a large custom h0 size via
	                                 inline style attribute in trans_menu_2.php's
	                                 own <style>; this brings it back in line so
	                                 the header band doesn't need as much height
	                                 to contain it */
	line-height: 1.3 !important;
}

/* -- Toolbar (search date / retrieve / add / printout) -- */
.ta-grid.ta-console .cf-toolbar {
	background: var(--cf-blue) !important;
	border-bottom: 3px solid var(--cf-gold) !important;
	padding: 10px 16px !important;
	border-radius: 8px 8px 0 0 !important;
	display: flex !important;
	align-items: center !important;
	gap: 10px;
	flex-wrap: wrap;
}
.ta-grid.ta-console .cf-toolbar input[type="text"] {
	height: 28px !important;
	font-size: 12px !important;
	font-weight: 400 !important;
	font-family: var(--cf-sans) !important;
	border: 1px solid var(--cf-border) !important;
	background: var(--cf-white) !important;
	color: var(--cf-dark) !important;
	border-radius: 4px !important;
	padding: 0 8px !important;
}
.ta-grid.ta-console .cf-toolbar input[type="submit"] {
	height: 28px !important;
	font-size: 11px !important;
	font-weight: 600 !important;
	font-family: var(--cf-sans) !important;
	background: var(--cf-gold) !important;
	color: #3A2D00 !important;
	border: none !important;
	border-radius: 4px !important;
	padding: 0 14px !important;
	cursor: pointer !important;
}
.ta-grid.ta-console .cf-toolbar input[type="submit"]:hover { background: #E5A50F !important; }

/* -- Action bar (Add New Entry / Generate Printout) -- */
.ta-grid.ta-console .cf-action-bar {
	background: var(--cf-bg) !important;
	padding: 9px 16px !important;
	border-bottom: 1px solid var(--cf-border);
	display: flex;
	align-items: center;
	gap: 12px;
}
.ta-grid.ta-console .cf-action-bar a {
	font-size: 12px;
	font-weight: 600;
	color: var(--cf-blue);
	text-decoration: none;
}
.ta-grid.ta-console .cf-action-bar a:hover { text-decoration: underline; }
.ta-grid.ta-console .cf-action-bar .cf-sep { color: var(--cf-border); }

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
	text-align: left;
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
   link reads as noisy and cluttered next to the actual data. These now
   sit quietly as a small muted icon-like affordance and only become a
   clear "click me" button on hover -- of the link itself, or anywhere in
   its containing row, so the affordance is discoverable without needing
   pixel-precise mouse aim.

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
.ta-grid.ta-console a.Llink,
.ta-grid.ta-console a.LEdit {
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
.ta-grid.ta-console table.train_ava tr:hover a.Llink,
.ta-grid.ta-console table.train_ava tr:hover a.LEdit,
.ta-grid.ta-console a.Llink:hover,
.ta-grid.ta-console a.LEdit:hover {
	opacity: 1 !important;
	color: var(--cf-blue) !important;
}
.ta-grid.ta-console a.Llink:hover,
.ta-grid.ta-console a.LEdit:hover {
	background: var(--cf-row-odd) !important;
	border-color: var(--cf-border) !important;
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
.ta-grid.ta-console .alink a.disabled { color: var(--cf-muted) !important; text-decoration: none !important; cursor: default !important; opacity: .4 !important; }

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


<?php
function setTime($hour,$minute,$second,$amorpm){


	if($amorpm=="pm"){
		if($hour<12){
			$hour+=12;
			
		}
		else {
		}
	
	}
	else {
		if($hour=="12"){
			$hour=0;
			
		}
	
	}
	$timestring=$hour.":".$minute.":".$second;
	
	return $timestring;
}

?>
<?php
if(isset($_POST['clearanceId'])){
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
	
	
	/**
	$day=$_SESSION['day'];
	$month=$_SESSION['month'];
	$year=$_SESSION['year'];	
	*/
	
//	$clearance_date=$year."-".$month."-".$day;
	$clearance_date=date("Y-m-d",strtotime($_SESSION['search_date']));
	$update="update clearance ";

	if(($_POST['formElement']=="logout")||($_POST['formElement']=="login")){

		$hour=$_POST[$_POST['formElement']."_hour"];
		$minute=$_POST[$_POST['formElement']."_minute"];
		$second=$_POST[$_POST['formElement']."_second"];

		$amorpm=$_POST[$_POST['formElement']."_amorpm"];
		
		$clearance_timestamp=$clearance_date." ".setTime($hour,$minute,$second,$amorpm);	

		$update.=" set ".$_POST['formElement']."='".$clearance_timestamp."' ";
		
		
		
		
		
	}
	else if($_POST['formElement']=="position") {
		$position=$_POST['position'];
		$company=$_POST['company'];

		$update.=" set ".$_POST['formElement']."='".$_POST[$_POST['formElement']]."', company='".$_POST['company']."' ";
	
	
	}
	else {
		$update.=" set ".$_POST['formElement']."='".$_POST[$_POST['formElement']]."' ";
	
	
	
	}
	$update.=" where clearance_no='".$_POST['clearanceId']."' and date='".$clearance_date."'";
	$updateRS=$db->query($update);
	

}
?>

<script src="js/jquery-1.10.2.min.js"></script>
<script language='javascript' src='ajax.js'></script>
<script language='javascript'>
function deleteRow(index,index_date){
	var check=confirm("Remove Record?");
	if(check){
	makeajax("processing.php?removeClearance="+index+"&removeDate="+index_date,"reloadPage");	
	}
}
function reloadPage(ajaxHTML){
	self.location="clearance form.php";


}
function fillEdit(element,clearance_id){
	var elementHTML="";

	elementHTML+="<table name='add_form' id='add_form' >";
	
	
	if((element=="login")||(element=="logout")){
		
		elementHTML+="<tr>";
		elementHTML+="<td>Enter "+element.toUpperCase()+"</td>";
		
		
		var prefix=element;
		
		var d=new Date();
		
		var year=d.getFullYear();
		var mmonth=d.getMonth()*1+1;
		var day=d.getDate();

		
		var tentativehour=d.getHours();
		var minute=d.getMinutes();
		var second=d.getSeconds();

		var hour=0;

		var amorpm="AM";
	
		if(tentativehour==0){
			hour=12;
			
			amorpm="AM";
		
		}
		else {
			if(tentativehour>12){
				hour=tentativehour-12;
				amorpm="PM";
			}
			else {
				hour=tentativehour;
				amorpm="AM";
			}
		
		}	
		
		
		
		
		elementHTML+="<td width=40%>";		
		elementHTML+="<select name='"+prefix+"_hour' width='20%'>";
		elementHTML+="<option></option>";
		
		
		for(var i=1;i<=12;i++){
			elementHTML+="<option value='"+i+"' ";
			if(hour==i){
				elementHTML+="selected";
			}
			elementHTML+=">"+i+"</option>";
		}
		elementHTML+="</select>";

		
		elementHTML+="<select name='"+prefix+"_minute' width='20%'>";
		elementHTML+="<option></option>";		
		var label="";
		for(var i=0;i<=59;i++){
			
			if(i<10){
				label="0"+i;			
			}
			else {
				label=i;
			}
			
			elementHTML+="<option value='"+i+"' ";
			if(minute==i){
			elementHTML+="selected";
			}
			elementHTML+=">"+label+"</option>";

		}
		elementHTML+="</select>";

		elementHTML+="<select name='"+prefix+"_second' width='20%'>";
		var label="";
		for(var i=0;i<=59;i++){
			
			if(i<10){
				label="0"+i;			
			}
			else {
				label=i;
			}
			
			elementHTML+="<option value='"+i+"' ";
			if(second==i){
			elementHTML+="selected";
			}
			elementHTML+=">"+label+"</option>";

		}
		elementHTML+="</select>";
	
		
		elementHTML+="<select name='"+prefix+"_amorpm' width='40%'>";	
		elementHTML+="<option></option>";
		elementHTML+="<option value='am' ";
		if(amorpm=="AM"){
			elementHTML+="selected";
		}
		elementHTML+=">AM</option>";

		elementHTML+="<option value='pm' ";
		if(amorpm=="PM"){
			elementHTML+="selected";
		}
		elementHTML+=">PM</option>";			
		
		elementHTML+="</select>";
		
		elementHTML+="</td>";
		elementHTML+="</tr>";	
		
	}
	else if((element=="activity")||(element=="location")){
		elementHTML+="<tr>";
		elementHTML+="<td>Enter "+element.toUpperCase()+"</td>";

		elementHTML+="<td><textarea rows=5 cols=50 name='"+element+"'></textarea></td>";

		elementHTML+="</tr>";	

	}
	else if(element=="position"){
		elementHTML+="<tr>";
		elementHTML+="<td>Enter POSITION</td>";
		elementHTML+="<td><input type=text name='position' /></td>";
		elementHTML+="</tr>";
	
		elementHTML+="<tr>";
		elementHTML+="<td>Enter COMPANY</td>";
		elementHTML+="<td><input type=text name='company' /></td>";
		elementHTML+="</tr>";
	
	}
	else if(element=="received_by"){
	
		elementHTML+="<tr>";
		elementHTML+="<td>Enter RECEIVED BY</td>";
		elementHTML+="<td><select name='received_by' id='received_by'>";
		elementHTML+="</select>";
		elementHTML+="</td>";
		elementHTML+="</tr>";
	
	
	
	}
	
	else {
		elementHTML+="<tr>";
		elementHTML+="<td>Enter "+element.toUpperCase()+"</td>";
		elementHTML+="<td><input type=text name='"+element+"' /></td>";
		elementHTML+="</tr>";
	
	}
	
	elementHTML+="<tr>";
	
	elementHTML+="<td colspan=3 align=center>";
	elementHTML+="<input type=hidden name='clearanceId' id='clearanceId' value='"+clearance_id+"' />";
	elementHTML+="<input type=hidden name='formElement' id='formElement' value='"+element+"' />";

	elementHTML+="<input type=submit value='Edit' />";	
	elementHTML+="</td>";
	elementHTML+="</tr>";
	elementHTML+="</table>";
	
	document.getElementById('clearance_edit').innerHTML=elementHTML;	

	if(element=="received_by"){
	makeajax("processing.php?received_by=Y","fillReceived");			
	
	}
	$('#addModal').modal('show');
	
}
function fillReceived(ajaxHTML){
	if(ajaxHTML=="None available"){
	}
	else {

		var driverHTML="";
		var driverTerms=ajaxHTML.split("==>");
		var count=(driverTerms.length)*1-1;
		
		for(var n=0;n<count;n++){
			var parts=driverTerms[n].split(";");
			driverHTML+="<option value='"+parts[0]+"'>";
			driverHTML+=parts[1].replace("_ENYE_","?");
			driverHTML+="</option>";
		
		}
		
	}
	document.getElementById('received_by').innerHTML=driverHTML;

}


$(function() {
    $( "#search_date" ).datepicker({
      changeMonth: true,
      changeYear: true,
      showAnim: "clip"
    });    
});

$(document).ready(function(){
    $(".alink a").each(function(){
        if($(this).hasClass("disabled")){
            $(this).removeAttr("href");
        }
    });
});

</script>
<body>
<form action='clearance form.php' method='post'>
<?php
$mm=date("m");
$yy=date("Y");
$dd=date("d");

$hh=date("h");

$min=date("i");
$aa=date("a");

$datenow=date("m/d/Y");
$clearance_date=date("Y-m-d");
if(isset($_POST['search_date'])){
	//$yy=$_POST['year'];
	//$mm=$_POST['month'];
	//$dd=$_POST['day'];
	
	$_SESSION['search_date']=$_POST['search_date'];
//	$_SESSION['day']=$_POST['day'];
//	$_SESSION['month']=$_POST['month'];
//	$_SESSION['year']=$_POST['year'];
	
	$datenow=date("m/d/Y",strtotime($_POST['search_date']));
	$clearance_date=date("Y-m-d",strtotime($_POST['search_date']));
	
}	



?>

<?php
if ($ULev>=2){
	$SRemove = "Llink"; 
	$SRemove2 = "two";
	$SRemove3 = "liR grow";
	$SRemove4 = "LEdit";
	$SRemove5 = "LDel";
} else {
	$SRemove = "disabled";
	$SRemove2 = "disabled";
	$SRemove3 = "disabled";
	$SRemove4 = "disabled";
	$SRemove5 = "disabled";
}
?>



<div class="ta-grid ta-console">
<form class="cf-toolbar" action='clearance form.php' method='post'>
<b style="color:#fff;font-size:12px;font-weight:600;margin-right:4px;">Date</b>
<input type="text" name='search_date' id='search_date' value='<?php echo $datenow; ?>' />

<input type=submit value='Retrieve Date' />
</form>
<div class="alink cf-action-bar">
<a href='#' class="<?php echo $SRemove; ?>" onclick='window.open("clearance entry.php");'>+ Add New Entry</a>
<span class="cf-sep">|</span>
<a href='#' class="<?php echo $SRemove2; ?>" onclick='window.open("generate_clearance_form.php?clearance_date=<?php echo $clearance_date; ?>");'>Generate Printout</a>
</div>

<table class='train_ava' width=100%>
<tr class='rowHeading'>
	<th>Clearance No.</th>
	<th>Location</th>
	<th>Activity</th>
	<th>Requesting Person</th>
	<th>Position/Company</th>
	<th>Received By</th>
	<th>Login</th>
	<th>Logout</th>
	<th>Work Permit/Control No.</th>
</tr>
<?php
//if((isset($_POST['day']))||(isset($_SESSION['day']))){

if((isset($_POST['search_date']))||(isset($_SESSION['search_date']))){
	if(isset($_POST['search_date'])){
		
		$ava_date=date("Y-m-d",strtotime($_POST['search_date']));
//		$year=$_POST['year'];
//		$month=$_POST['month'];
//		$day=$_POST['day'];
	}
	
	else if(isset($_SESSION['search_date'])){
		$ava_date=date("Y-m-d",strtotime($_SESSION['search_date']));

		//$year=$_SESSION['year'];
		//$month=$_SESSION['month'];
		//$day=$_SESSION['day'];
	
	
	}
	$clearance_date=$ava_date;
//	$clearance_date=date("Y-m-d",strtotime($year."-".$month."-".$day));

	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
	
	$sql="select * from clearance where date like '".$clearance_date."%%' order by login asc";
	$rs=$db->query($sql);
	$nm=$rs->num_rows;	
	
	if($nm>0){
		$varR=1;
		for($i=0;$i<$nm;$i++){
			$row=$rs->fetch_assoc();
			$clearance_no=$row['clearance_no'];
			$location=$row['location'];
			$activity=$row['activity'];
			$person=$row['person'];
			$position=$row['position'];
			$company=$row['company'];
			$received_by=$row['received_by'];
			$login=$row['login'];
			$logout=$row['logout'];
		
			$sql2="select * from train_driver where id='".$received_by."'";
			$rs2=$db->query($sql2);
			$nm2=$rs2->num_rows;
			
			if($nm2>0){
				$row2=$rs2->fetch_assoc();
				$received_by=$row2['position']." ".substr($row2['firstName'],0,1).". ".$row2['lastName'];
			
			}

		
			if($login=="0000-00-00 00:00:00"){
				$login="";
			}
			else {
				$login=date("H:i",strtotime($row['login']));
			}
			
			if($logout=="0000-00-00 00:00:00"){
				$logout="";
			}
			else {
				$logout=date("H:i",strtotime($row['logout']));
			}
			$control_no=$row['control_no'];
			
?>			
			<tr class="<?php echo ($i%2>0)?'cf-row--odd':'cf-row--even'; ?>">
				<td align=center><?php echo $i*1+1; ?></td>	
				<td><?php echo $location; ?> <a href='#' class="<?php echo $SRemove4; ?>" onclick="fillEdit('location','<?php echo $clearance_no; ?>')">Edit</a></td>

				<td><?php echo $activity; ?> <a href='#' class="<?php echo $SRemove4; ?>" onclick="fillEdit('activity','<?php echo $clearance_no; ?>')">Edit</a></td>
				<td><?php echo $person; ?> <a href='#' class="<?php echo $SRemove4; ?>" onclick="fillEdit('person','<?php echo $clearance_no; ?>')">Edit</a></td>
				<td><?php echo $position." / ".$company; ?> <a href='#' class="<?php echo $SRemove4; ?>" onclick="fillEdit('position','<?php echo $clearance_no; ?>')">Edit</a></td>
				<td><?php echo $received_by; ?> <a href='#' class="<?php echo $SRemove4; ?>" onclick="fillEdit('received_by','<?php echo $clearance_no; ?>')">Edit</a></td>
				<td><?php echo $login; ?> <a href='#' class="<?php echo $SRemove4; ?>" onclick="fillEdit('login','<?php echo $clearance_no; ?>')">Edit</a></td>
				<td><?php echo $logout; ?>  <a href='#' class="<?php echo $SRemove4; ?>" onclick="fillEdit('logout','<?php echo $clearance_no; ?>')">Edit</a></td>
				<td><?php echo $control_no; ?>  <a href='#' class="<?php echo $SRemove4; ?>" onclick="fillEdit('control_no','<?php echo $clearance_no; ?>')">Edit</a></td>
				<td><a href='#' class="<?php echo $SRemove5; ?>" onclick="deleteRow('<?php echo $clearance_no; ?>','<?php echo $clearance_date; ?>')">X</a></td>
			</tr>	
			
<?php		
		}	
	}
	

}


?>
</table>
</div>
</div><!-- /.ta-grid -->

<!--
<a href='#' class="Llink" onclick='window.open("clearance entry.php");'><b>Add New Entry</b></a>
<br>

<?php
if ($varR<>0) { ?>
<a href='#' class="two" onclick='window.open("generate_clearance_form.php?clearance_date=<?php echo $clearance_date; ?>");'><b>Generate Printout</b></a>
<?php 
$varR=0;
} 
?>
-->
<br>
<br>
		<div class="modal hide fade" id="addModal">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
				<h3>Edit Clearance Entry</h3>
			</div>
			<form action='clearance form.php' method='post'>

			<div class="modal-body">	
				<div id='clearance_edit' name='clearance_edit'>




				</div>


				
			</div>
						
			<div class="modal-footer">
				<a href="#" class="btn" data-dismiss="modal">Close</a>
				<button type='submit' class="btn btn-primary" value='Submit'>Submit </button>
			</div>
			  </form>
		</div>



</body>
	<script src="js/jquery-migrate-1.2.1.min.js"></script>	
		<script src="js/jquery-ui-1.10.3.custom.min.js"></script>	
		<script src="js/jquery.ui.touch-punch.js"></script>	
		<script src="js/modernizr.js"></script>	
		<script src="js/bootstrap.min.js"></script>	
		

<script src="js/date.js"></script>	
<script src='js/form.js'></script>