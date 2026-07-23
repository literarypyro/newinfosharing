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
.ta-grid.ta-console .cf-td-search  { text-align: left; padding:4px; !important }
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

#addModal select[name$="year"],
#addModal select[name$="month"],
#addModal select[name$="day"],
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



<?php
$mm = date("m");
$yy = date("Y");
$dd = date("d");

$driverOptions="";


$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
	
	$sql="select * from train_driver where position in ('STDO','CCRE')";
$rs=$db->query($sql);
$nm=$rs->num_rows;

for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
	$driverOptions.="<option value='".$row['id']."'>".$row['lastName'].", ".$row['firstName'].", ".$row['position']."</option>";
}


$monthOptions = "";
for ($i = 1; $i < 13; $i++) {
	$monthOptions .= "<option value='".$i."'".($i == $mm ? " selected" : "").">"
		.date("F", strtotime(date("Y")."-".$i."-01"))."</option>";
}

$dayOptions = "";
for ($i = 1; $i <= 31; $i++) {
	$dayOptions .= "<option value='".$i."'".($i == $dd ? " selected" : "").">".$i."</option>";
}

$yearOptions = "";
$dateRecent = date("Y") * 1 + 16;
for ($i = 1999; $i <= $dateRecent; $i++) {
	$yearOptions .= "<option value='".$i."'".($i == $yy ? " selected" : "").">".$i."</option>";
}



$hourOptions = "";

for($i=1;$i<=12;$i++){

	$hourOptions.="<option value='".$i."'>".$i."</option>"; 
	
}

$minuteOptions = "";

for($i=0;$i<=12;$i++){

	$minuteOptions.="<option value='".$i."'>";
	
	if($i<10){
	$minuteOptions.="0".$i;
	}
	else {
	$minuteOptions.=$i;
	}
	
	$minuteOptions.="</option>"; 
	
}

$secondOptions = "";

for($i=0;$i<=59;$i++){

	$secondOptions.="<option value='".$i."'>".$i."</option>"; 
	
}

	
?>




<script language='javascript'>
function fillDate(){
	var dateCode = "<select name='month'><?php echo $monthOptions; ?></select>";
	dateCode += "<select name='day'><?php echo $dayOptions; ?></select>";
	dateCode += "<select name='year'><?php echo $yearOptions; ?></select>";
	return dateCode;
}

function fillTD(){
	var driver="<select name='received_by'><?php echo $driverOptions; ?></select>";
	return driver;
}

function fillTime(prefix){
	var timeCode="<select name='"+prefix+"_hour'><?php echo $hourOptions; ?></select>";
	timeCode+="<select name='"+prefix+"_minute'><?php echo $minuteOptions; ?></select>";
	timeCode+="<select name='"+prefix+"_second'><?php echo $secondOptions; ?></select>";
	timeCode+="<select name='"+prefix+"_amorpm'><option value='am'>AM</option><option value='pm'>PM</option></select>";
	return timeCode;


}

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
	else if(element=="create_entry"){

		elementHTML+="<tr>";
		elementHTML+="<td>Date</td>";
		elementHTML+="<td>"+fillDate()+"</td>";
		elementHTML+="</tr>";

		elementHTML+="<tr>";
		elementHTML+="<td>Location</td>";
		elementHTML+="<td><textarea rows=5 cols=50 name='location'></textarea>";
		elementHTML+="</td>";
		elementHTML+="</tr>";

		elementHTML+="<tr>";
		elementHTML+="<td>Activity</td>";
		elementHTML+="<td><textarea rows=5 cols=50 name='activity'></textarea>";
		elementHTML+="</td>";
		elementHTML+="</tr>";


		elementHTML+="<tr>";
		elementHTML+="<td>Person</td>";
		elementHTML+="<td><input type=text name='person' />";
		elementHTML+="</td>";
		elementHTML+="</tr>";


		elementHTML+="<tr>";
		elementHTML+="<td>Position</td>";
		elementHTML+="<td><input type=text name='position' />";
		elementHTML+="</td>";
		elementHTML+="</tr>";
		

		elementHTML+="<tr>";
		elementHTML+="<td>Company</td>";
		elementHTML+="<td><input type=text name='company' />";
		elementHTML+="</td>";
		elementHTML+="</tr>";

		elementHTML+="<tr>";
		elementHTML+="<td>Received By</td>";
		elementHTML+="<td>"+fillTD();
		elementHTML+="</td>";
		elementHTML+="</tr>";

		elementHTML+="<tr>";
		elementHTML+="<td>Log In Time</td>";
		elementHTML+="<td>";
		elementHTML+=fillTime("login");
		elementHTML+="</td>";
		elementHTML+="</tr>";

		elementHTML+="<tr>";
		elementHTML+="<td>Log Out Time</td>";
		elementHTML+="<td>";
		elementHTML+=fillTime("logout");
		elementHTML+="</td>";
		elementHTML+="</tr>";
	
		elementHTML+="<tr>";
		elementHTML+="<td>Work Permit/Control No.</td>";
		elementHTML+="<td><input type=text name='control_no' />";
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
			/* processing.php now sends the name pre-converted to proper
			   UTF-8 (see received_by handler), so this is a defensive
			   no-op for any older/other endpoint that still uses the
			   _ENYE_ placeholder scheme -- and correctly restores "ñ"
			   rather than the "?" it was replaced with previously. */
			driverHTML+=parts[1].replace(/_ENYE_/g,"ñ");
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
if(isset($_SESSION['search_date'])){
	$datenow      = date("m/d/Y", strtotime($_SESSION['search_date']));
	$clearance_date = date("Y-m-d",  strtotime($_SESSION['search_date']));
}





?>

<?php
if ($ULev>=2){
	$SRemove = "Llink"; 
	$SRemove2 = "two";
	$SRemove3 = "liR grow";
	$SRemove5 = "LDel";
} else {
	$SRemove = "disabled";
	$SRemove2 = "disabled";
	$SRemove3 = "disabled";
	$SRemove5 = "disabled";
}
/* Per-cell Edit is intentionally not gated by $ULev -- enabled for
   everyone, unlike Add New Entry / Generate Printout / Delete above. */
$SRemove4 = "LEdit";
?>



<div class="ta-grid ta-console">

<!-- ── TOOLBAR (single row: date / search / actions -- matches train_availability.php) ── -->
<table class="cf-toolbar-table" cellspacing="0" cellpadding="0">
<tr>
	<td class="cf-td-date">
		<span class="cf-date-label"><?php echo date("F d, Y", strtotime($datenow)); ?></span>
		<span class="cf-date-day"><?php echo date("l", strtotime($datenow)); ?></span>
	</td>
	<td class="cf-td-search">
		<form action='clearance form.php' method='post' style="margin:0;padding:0;display:inline">
			<input type="text" name='search_date' id='search_date' value='<?php echo $datenow; ?>' />
			<input type="submit" value="Go" />
		</form>
	</td>
	<td class="cf-td-actions alink">
		<a href='#' class="cf-tbtn <?php echo $SRemove; ?>" onclick="fillEdit('create_entry','<?php echo $clearance_no; ?>')">+ Add New Entry</a>
		<a href='#' class="cf-tbtn cf-tbtn--primary <?php echo $SRemove2; ?>" onclick='window.open("generate_clearance_form.php?clearance_date=<?php echo $clearance_date; ?>");'>Generate Printout</a>
	</td>
</tr>
</table>
<!-- end toolbar -->

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

if((isset($_POST['search_date']))||(isset($_SESSION['search_date']))||(isset($datenow))){
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
		else if(isset($datenow)){
		$ava_date=date("Y-m-d",strtotime($datenow));

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