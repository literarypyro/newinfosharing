<?php
require("Tmenu.php");
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
:root {
		--ta-sans: "Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif;
	--ta-mono: ui-monospace, "Cascadia Mono", "Consolas", "Liberation Mono", monospace;
	--rail:      #00529B;
	--rail-dark: #013E76;
	--rail-wash: #EEF4FB;
	--gold:      #FDB813;
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
table{
	border-collapse:collapse;
}
.rowClass {background-color: #F3F3F3;}

/* color header */
.rowHeading {background-color: #cccccc}

/*
body {
	margin-left:30px;
	margin-right:30px;
}
*/

/* input color */
input[type="text"]{ 
	height:25px; 
	font-weight:bold; 
	font-size:15px; 
	font-family:courier; 
	border: 1px solid #FFD700;
	background-color: #FFFACD;  
	border-radius: 3px
}

/*
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
*/

input[type="text"]:focus {
	background-color:#FFFFF0;
}

textarea:focus {
	background-color:#FFFFF0;
	font-weight:bold;
}

.date {
	text-style:bold;
	font-size:20px;
}

textarea{ 
	border: 1px solid #FFD700;
	background-color: #FFFACD;
	border-radius: 3px;
}

/* header */
#add_form th{
background-color: #cccccc;
}

#add_form td:nth-child(odd) {
background-color: #DCDCDC; 
color:black;
font-weight:bold;
padding:5px;
}

#add_form td:last-child{
background-color:white;
}

#add_form td:nth-child(even) {
background-color: #f5f5f5;
border:1px solid #cccccc;
}

select { border: 1px solid rgb(185, 201, 254); color: black; background-color: #FFFACD; }

/* --- mjun -- generate */
a.two:visited {color:black;}
a.two:hover, a.two:active {font-size:120%; color:orange;}

/* unvisited link */
a.Llink:link { color: #FF0000;}
a.Llink:visited {color: black;}
a.Llink:hover { color: Orange;}
a.Llink:active {color: #0000FF;}

a.LEdit:visited {color:blue;}
a.LDel:visited {color:red;}

.alink a.disabled {
        color: #666;
        text-decoration: none;
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
.ops-act--gold    { background:var(--gold); border-color:var(--gold); color:var(--gold-ink); font-weight:600; }
.ops-act          { display:inline-block; font-size:11px; font-weight:500; color:#fff; text-decoration:none; padding:5px 11px; border:1px solid rgba(255,255,255,.35); border-radius:4px; float:none !important; width:auto !important; cursor:pointer; }



   table.train_ava   { margin-top:20px;width:100%; border-collapse:separate; border-spacing:0; min-width:920px; }
   
table.train_ava th{ background:var(--rail); color:#fff; padding:9px 10px; font-family:var(--ta-sans); font-weight:600; font-size:11px; letter-spacing:.4px; text-transform:uppercase; text-align:center; border-right:1px solid rgba(255,255,255,.18); border-bottom:3px solid var(--gold); }
table.train_ava td{ padding:8px 10px; vertical-align:top; border-right:1px solid #E6EDF5; border-bottom:1px solid #E6EDF5; font-family:var(--ta-sans); font-size:12.5px; }
table.train_ava tr.row-first td { border-top:2px solid var(--line); }




/* =========================================================================
   DEPOT INSERTION -- console softening pass, matched to ccdr_summary.php.
   Appended so later rules override the stronger legacy colors above without
   disturbing anything the slide panel also reads. Also defines --gold-ink
   and --line, which .ops-act--gold and tr.row-first referenced but which
   were never defined here (same undefined-token bug ccdr_summary's rebuild
   notes fixed on that page) -- the Add New Entry button was rendering
   white-on-gold because of it.
   ========================================================================= */
:root { --cf-blue-dark:#013E76; --cf-gold-ink:#3A2D00; --gold-ink:#3A2D00; --line:#D2DDEA; }

/* Inputs: lemon/gold -> console white (these generic selectors also feed
   the slide-panel entry form, which is the point) */
input[type="text"] {
	height:26px; font-weight:normal; font-size:12.5px; font-family:var(--cf-sans);
	border:1px solid var(--cf-border); background:var(--cf-white); color:var(--cf-dark);
	border-radius:4px; padding:0 8px; margin-bottom:0; vertical-align:middle;
}
textarea {
	font-size:12.5px; font-family:var(--cf-sans); font-weight:normal;
	border:1px solid var(--cf-border); background:var(--cf-white); color:var(--cf-dark);
	border-radius:4px; padding:6px 8px; margin-bottom:0;
}
input[type="text"]:focus, textarea:focus {
	background:var(--cf-white); font-weight:normal;
	border-color:var(--cf-blue); outline:none; box-shadow:0 0 0 2px rgba(0,82,155,.12);
}
select {
	border:1px solid var(--cf-border); background:var(--cf-white); color:var(--cf-dark);
	border-radius:4px; height:26px; line-height:normal; font-size:12.5px;
	font-family:var(--cf-sans); margin-bottom:0; vertical-align:middle;
}

/* Row striping: legacy gray -> console wash */
.rowClass { background-color: var(--cf-row-odd); }

/* Slide-panel form palette: flat grays -> console */
#add_form th { background:var(--cf-blue); color:#fff; }
#add_form td:nth-child(odd)  { background:var(--cf-row-odd); color:var(--cf-dark); font-weight:600; padding:7px 10px; border:1px solid var(--cf-border); }
#add_form td:nth-child(even) { background:var(--cf-white); border:1px solid var(--cf-border); padding:6px 8px; }
#add_form input[type=submit] { height:28px; border:none; border-radius:4px; background:var(--cf-gold); color:var(--cf-gold-ink); font-weight:700; font-size:12px; padding:0 16px; cursor:pointer; }
#add_form input[type=submit]:hover { background:#E5A50F; }

/* Page header band above the toolbar -- ccdr composition: the header
   carries the radius + gold rule, the toolbar below is a plain blue band */
.dip-header { background:var(--cf-blue); border-bottom:3px solid var(--cf-gold); border-radius:6px 6px 0 0; padding:12px 16px; font-family:var(--cf-sans); }
.dip-header h1 { margin:0; font-size:16px; font-weight:700; color:#fff; letter-spacing:.3px; }
.dip-header .sub { font-size:10px; color:rgba(255,255,255,.6); letter-spacing:.5px; text-transform:uppercase; margin-top:2px; }
.stat-toolbar { border-bottom:none; border-radius:0; }
.stat-toolbar form { margin:0; display:inline; }  /* bootstrap.min.css gives form 20px bottom margin -> bloated toolbar */
.stat-toolbar select, .stat-toolbar input[type=text] { height:28px; margin-bottom:0; vertical-align:middle; }
.stat-toolbar input[type=submit] { height:30px; padding:0 16px; margin-bottom:0; vertical-align:middle; }
/* Force the date field's text to render. The value was always being set
   (console-verified: the property held the picked date while the box
   looked empty) -- the text was painting invisibly. Most likely cause:
   style.min.css / bootstrap.min.css (loaded on THIS page only among the
   console pages) make inputs inherit color, and these toolbar cells are
   color:#FFFFFF -- white text on a white field. This rule pins every
   text-visibility vector (color, webkit text-fill, indent, opacity) so
   no stylesheet can blank it again. */
.stat-toolbar input[type="text"], .stat-toolbar input[type="text"]:focus {
	color:#16243B !important;
	-webkit-text-fill-color:#16243B !important;
	opacity:1 !important;
	text-indent:0 !important;
}

/* Panel card around the log table (ccdr's treatment) */
.dip-wrap { padding:16px; font-family:var(--cf-sans); }
.dip-panel { background:var(--cf-white); border:1px solid var(--cf-border); border-radius:6px; box-shadow:0 1px 3px rgba(0,30,80,.08); overflow:hidden; }
/* (flattened) the blue .dip-panel-head band made a THIRD stacked blue/gold
   layer under the header + toolbar; the date chip lives in the toolbar's
   left cell instead -- same date-left / search-center / action-right
   anatomy as train_hourly and train_availability. */
.stat-toolbar .cf-td-date { width:1%; white-space:nowrap; }
.stat-toolbar .cf-date-label { font-size:15px; font-weight:700; color:#fff; }
.stat-toolbar .cf-date-day { font-size:11px; color:rgba(255,255,255,.6); margin-left:8px; }
/* search sits beside the date chip; centering (copied from train_hourly's
   two-cell toolbar) floated it mid-bar here, where a third action cell
   exists on the right. */
.stat-toolbar .cf-td-search { text-align:left; width:1%; }
.stat-toolbar .cf-td-search input[type=text] { width:120px; }
.dip-panel-body { padding:14px; overflow-x:auto; }

/* Table breathing room inside the panel */
table.train_ava { margin-top:0; }
table.train_ava th { border-bottom:1px solid #0A639E; line-height:1.35; }  /* the page header carries the gold rule */
table.train_ava td { text-align:center; }
table.train_ava td:last-child { text-align:left; }
tr.cf-empty-row td { text-align:center; color:var(--cf-muted); font-style:italic; padding:18px; background:var(--cf-white); }

/* Modal: forced resting state (same guard as the other console pages; the
   modal is otherwise hidden only by Bootstrap's .hide class. Bootstrap sets
   inline display:block on open, so opening is unaffected) */
#addModal { display:none; }
</style>

<?php
function setTime($hour,$minute,$amorpm){


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
	$timestring=$hour.":".$minute;
	
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
		$amorpm=$_POST[$_POST['formElement']."_amorpm"];
		
		$clearance_timestamp=$clearance_date." ".setTime($hour,$minute,$amorpm);	

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
		
		
		
		
		elementHTML+="<td>";		
		elementHTML+="<select name='"+prefix+"_hour'>";
		elementHTML+="<option></option>";
		
		
		for(var i=1;i<=12;i++){
			elementHTML+="<option value='"+i+"' ";
			if(hour==i){
				elementHTML+="selected";
			}
			elementHTML+=">"+i+"</option>";
		}
		elementHTML+="</select>";

		
		elementHTML+="<select name='"+prefix+"_minute'>";
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

		
		elementHTML+="<select name='"+prefix+"_amorpm'>";	
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
	
	elementHTML+="<td colspan=2 align=center>";
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
			driverHTML+=parts[1].replace("_ENYE_","\u00D1")  /* ASCII-safe escape: the raw N-tilde byte here got mangled to '?' in a save/transfer once already */;
			driverHTML+="</option>";
		
		}
		
	}
	document.getElementById('received_by').innerHTML=driverHTML;

}

function processSlidePanel(type){
		
		var htmlCode="";
		
		
		if(type=='addEntry'){
			
			htmlCode="<table><tr><th colspan=2>Add Removal</th></tr>";
			htmlCode+="<tr><td>Removal Time</td><td id='cell' name='cell'></td></tr>";
		htmlCode+="<tr><td>Remarks/Cause of <br>Failure/Removal</td>";
		htmlCode+="<td><span name='remarks_space' id='remarks_space'><textarea name='remarks' cols=50></textarea></span>";
		htmlCode+="<input type=checkbox name='remarks_check' id='remarks_check' onclick='setPreset(this)' />Preset Values</td></tr>";
		htmlCode+="<tr><td>Removed From</td><td><select name='removed_from' id='removed_from'>";
		htmlCode+="<option value='north'>North Ave.</option><option value='quezon'>Quezon Ave.</option>";
		htmlCode+="</select></td></tr>";


		htmlCode+="<tr><th colspan=2>";

		htmlCode+="Departure at Stabling Area</th></tr>";


		
		htmlCode+="<tr><td>Remarks</td><td><input type=text name='remarks' /></td></tr>";
		
		
		htmlCode+="<tr><td colspan=2 class='submit' align=center>";
		htmlCode+="<input type=hidden name='remove_id' id='remove_id' value='"+form_id+"' />";
		htmlCode+="<input type=submit value='Submit' /></td></tr></table>";


			openSlidePanel('','Depot Insertion','');  /* stray trailing quote removed: it was a SyntaxError that prevented this ENTIRE
			   script block from executing -- datepicker init, deleteRow, fillEdit and the
			   Add New Entry wiring included */




			
			
		}
	
	
}

/**

	else if(form_type=="removal"){
		htmlCode="<table><tr><th colspan=2>Add Removal</th></tr>";
		htmlCode+="<tr><td>Removal Time</td><td id='cell' name='cell'></td></tr>";
		htmlCode+="<tr><td>Train Driver</td>";
		if(form_extra=="unimog") htmlCode+="<td id='ph_trams_tag' name='ph_trams_tag'></td>";
		else if(form_extra=="test") htmlCode+="<td id='ph_trams_tag' name='ph_trams_tag'></td>";
		else if(form_extra=="reserve") htmlCode+="<td><input type=text name='unimog_train_driver' /></td>";
		else if(form_extra=="schooling") htmlCode+="<td id='school_tag' name='school_tag'></td>";
		else htmlCode+="<td id='td' name='td'></td>";
		htmlCode+="</tr>";
		htmlCode+="<tr><td>Remarks/Cause of <br>Failure/Removal</td>";
		htmlCode+="<td><span name='remarks_space' id='remarks_space'><textarea name='remarks' cols=50></textarea></span>";
		htmlCode+="<input type=checkbox name='remarks_check' id='remarks_check' onclick='setPreset(this)' />Preset Values</td></tr>";
		htmlCode+="<tr><td>Removed From</td><td><select name='removed_from' id='removed_from'>";
		htmlCode+="<option value='north'>North Ave.</option><option value='quezon'>Quezon Ave.</option>";
		htmlCode+="</select></td></tr>";
		htmlCode+="<tr><td>Add Incident?</td><td>";
		htmlCode+="<input type='checkbox' name='cancel_loop' id='cancel_loop' />Open Incident Report</td></tr>";
		htmlCode+="<tr><td colspan=2 class='submit' align=center>";
		htmlCode+="<input type=hidden name='remove_id' id='remove_id' value='"+form_id+"' />";
		htmlCode+="<input type=submit value='Submit' /></td></tr></table>";
	}



*/

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

<div class="dip-header">
	<h1>Depot Insertion Program</h1>
	<div class="sub">Train Insertion &amp; Stabling Departures &mdash; Line 3</div>
</div>







<table cellspacing="0" cellpadding="0" class='stat-toolbar'>
<tr>
	<td class="cf-td-date" style="padding:8px 14px;vertical-align:middle;border:none">
<?php
	/* active-date chip: same POST -> SESSION precedence the table query uses */
	if((isset($_POST['search_date']))||(isset($_SESSION['search_date']))){
		$dip_shown=isset($_POST['search_date'])?$_POST['search_date']:$_SESSION['search_date'];
		echo "<span class='cf-date-label'>".date("F d, Y",strtotime($dip_shown))."</span><span class='cf-date-day'>".date("l",strtotime($dip_shown))."</span>";
	}
	else {
		echo "<span class='cf-date-day'>No date selected</span>";
	}
?>
	</td>
	<td class="cf-td-search" style="padding:8px 14px;vertical-align:middle;white-space:nowrap;border:none">

<form action='depot_insertion.php' method='post'>
<?php
$mm=date("m");
$yy=date("Y");
$dd=date("d");

$hh=date("h");

$min=date("i");
$aa=date("a");

$datenow=date("m/d/Y");
$insertion_date=date("Y-m-d");
if(isset($_POST['search_date'])){
	//$yy=$_POST['year'];
	//$mm=$_POST['month'];
	//$dd=$_POST['day'];
	
	$_SESSION['search_date']=$_POST['search_date'];
//	$_SESSION['day']=$_POST['day'];
//	$_SESSION['month']=$_POST['month'];
//	$_SESSION['year']=$_POST['year'];
	
	$datenow=date("m/d/Y",strtotime($_POST['search_date']));
	$insertion_date=date("Y-m-d",strtotime($_POST['search_date']));
	
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



<!--
<input type='text' name='search_date' id='search_date' class='datepicker' value='<?php echo $datenow; ?>' />
-->

<input type="text" name='search_date' id='search_date' placeholder='mm/dd/yyyy' value='<?php echo isset($dip_shown)?date("m/d/Y",strtotime($dip_shown)):""; ?>' />

<input type=submit value='Retrieve Date' />
</form>
	</td>


	<td style="padding:8px 14px;vertical-align:middle;text-align:right;white-space:nowrap;border:none">
<a href='#' class="ops-act ops-act--gold" onclick="processSlidePanel('addEntry')"

<?php 
/**
<!--
onclick='window.open("insertion_entry.php");'

-->
*/
?>

><b>Add New Entry</b></a>






	</td>
</tr>
</table>






<div class="dip-wrap">
<div class="dip-panel">
<div class="dip-panel-body">
<table class='train_ava' width=100%>
<tr >
	<th rowspan=2>Index</th>
	<th rowspan=2>Time of Train Availability at Stabling Area</th>
	<th rowspan=2>Actual time of completion of train preparation (Ready for insertion)</th>
	<th colspan=2>Departure at Stabling Area for 1st loop</th>
	<th colspan=2>Departure at Stabling Area for 2nd loop</th>
	<th colspan=2>Departure at Stabling Area for 3rd loop</th>
	<th rowspan=2>Remarks</th>

</tr>
<tr >
	<th>Planned</th>
	<th>Actual</th>
	<th>Planned</th>
	<th>Actual</th>
	<th>Planned</th>
	<th>Actual</th>
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
	$insertion_date=$ava_date;
//	$clearance_date=date("Y-m-d",strtotime($year."-".$month."-".$day));

	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
	
	$sql="select * from depot_insertion where insertion_date like '".$insertion_date."%%' order by id";
	$rs=$db->query($sql);
	$nm=$rs->num_rows;	
	
	if($nm>0){
		$varR=1;
		for($i=0;$i<$nm;$i++){
			$row=$rs->fetch_assoc();

			$index_no=$row['index_no'];
			$tar_time=$row['tar_time'];
			$actual_completion=$row['actual_completion'];
			$remarks=$row['remarks'];

		
			$sql2="select * from stabling_departure where depot_insertion_id='".$row['id']."'";
			$rs2=$db->query($sql2);
			$nm2=$rs2->num_rows;
			
			if($nm2>0){


				
			
				for($k=0;$k<$nm2;$k++){
					$row2=$rs2->fetch_assoc();
				
				
					$planned[$row2['loop_no']]=$row2['planned'];	
					$actual[$row2['loop_no']]=$row2['actual'];	
				}
				
				
				
			
			}

		
?>			
			<tr <?php if($i%2>0){ echo "class='rowClass'"; } ?>>
			
				<td><?php echo $index_no; ?></td>
				<td><?php echo $tar_time; ?></td>
				<td><?php echo $actual_completion; ?></td>
				
				<td><?php echo $planned[1]; ?></td>
				<td><?php echo $actual[1]; ?></td>
				<td><?php echo $planned[2]; ?></td>
				<td><?php echo $actual[2]; ?></td>
				<td><?php echo $planned[3]; ?></td>
				<td><?php echo $actual[3]; ?></td>

				<td><?php echo $remarks; ?></td>

			</tr>	
			
<?php		
		}	
	}
	else {
		/* empty state: date searched, no rows recorded */
		echo "<tr class='cf-empty-row'><td colspan=10>No insertion records for ".date("F d, Y",strtotime($insertion_date)).".</td></tr>";
	}
	

}
else {
	/* empty state: no date chosen yet */
	echo "<tr class='cf-empty-row'><td colspan=10>Select a date and click Retrieve Date to view the insertion program.</td></tr>";
}

?>
</table>
</div><!-- /.dip-panel-body (absorbs a previously stray closing div here) -->
</div><!-- /.dip-panel -->
</div><!-- /.dip-wrap -->

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
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h3>Edit Entry</h3>
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


<?php require("slide_panel2.php"); ?>
</body>
	<script src="js/jquery-migrate-1.2.1.min.js"></script>	
		<script src="js/jquery-ui-1.10.3.custom.min.js"></script>	
		<script src="js/jquery.ui.touch-punch.js"></script>	
		<script src="js/modernizr.js"></script>	
		<script src="js/bootstrap.min.js"></script>	
		

<script src="js/date.js"></script>	
<script src='js/form.js'></script>