<?php
require("Tmenu.php");
?>
<!--Modify: mjun
 Modified date: Aug 5, 2014
 Modified: Change screen layout
-->
<?php
function is_decimal( $val )
{
    return is_numeric( $val ) && floor( $val ) != $val;
}
?>
<?php
if(isset($_POST['reserve'])){
	if($_SESSION['search_date']==""){
	}
	else {
//		$month=$_SESSION['month'];
//		$day=$_SESSION['day'];
//		$year=$_SESSION['year'];
		
//		$train_date=$year."-".$month."-".$day;
		$train_date=date("Y-m-d",strtotime($_SESSION['search_date']));
		
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
		
		$reserve_hour=str_replace("30","",$_POST['reserve_hour']);
		$reserve=$_POST['reserve'];
		$nm=0;
		
		$table="";
		
		if($reserve_hour<=12){
			$sql="select * from reserve_1 where date='".$train_date."'";
			$rs=$db->query($sql);
			$nm=$rs->num_rows;
			$table="reserve_1";
		}
		else {
			$sql="select * from reserve_2 where date='".$train_date."'";
			$rs=$db->query($sql);
			$nm=$rs->num_rows;
			$table="reserve_2";
		}
		
		if($nm>0){
		
			$row=$rs->fetch_assoc();
			$update="update ".$table." set h_".$reserve_hour."='".$reserve."' where id='".$row['id']."'";

			$updateRS=$db->query($update);
		}
		else {
			$update="insert into ".$table."(h_".$reserve_hour.",date) values ('".$reserve."','".$train_date."')";

			$updateRS=$db->query($update);
		
		
		}
	}
}

if(isset($_POST['remarks'])){
	if($_SESSION['search_date']==""){
	}
	else {
//		$month=$_SESSION['month'];
//		$day=$_SESSION['day'];
//		$year=$_SESSION['year'];
		$train_date=date("Y-m-d",strtotime($_SESSION['search_date']));
		
//		$train_date=$year."-".$month."-".$day;
		
		$db=new mysqli("localhost","root","","transport");
		
		
		$remarks_hour=$_POST['remarks_hour'];
		//$remarks_hour=str_replace("30","",$_POST['remarks_hour']);
		$remarks=$_POST['remarks'];

		$sql="select * from train_hourly_remarks where hourly_date='".$train_date."' and hour='".$remarks_hour."'";
		$rs=$db->query($sql);
		$nm=$rs->num_rows;		
		
		if($nm>0){
		
			$row=$rs->fetch_assoc();
			$update="update train_hourly_remarks set remarks=\"".$remarks."\" where id='".$row['id']."'";
			$updateRS=$db->query($update);
		}
		else {
			$update="insert into train_hourly_remarks(hourly_date,hour,remarks) values ('".$train_date."','".$remarks_hour."',\"".$remarks."\")";
			$updateRS=$db->query($update);
			
		
		}		
	}
}	

?>
<?php
if(isset($_POST['cars_provided'])){
	if($_SESSION['search_date']==""){
	}
	else {
//		$month=$_SESSION['month'];
//		$day=$_SESSION['day'];
//		$year=$_SESSION['year'];
		
//		$train_date=$year."-".$month."-".$day;
		
		
		$train_date=date("Y-m-d",strtotime($_SESSION['search_date']));
		
		$db=new mysqli("localhost","root","","transport");
		
		$provided_hour=str_replace("30","",$_POST['provided_hour']);
		$cars_provided=$_POST['cars_provided'];
		$nm=0;
		
		$table="";
		
		if($provided_hour<=12){
			$sql="select * from cars_provided_1 where date='".$train_date."'";
			$rs=$db->query($sql);
			$nm=$rs->num_rows;
			$table="cars_provided_1";
		}
		else {
			$sql="select * from cars_provided_2 where date='".$train_date."'";
			$rs=$db->query($sql);
			$nm=$rs->num_rows;
			$table="cars_provided_2";
		}
		
		if($nm>0){
		
			$row=$rs->fetch_assoc();
			$update="update ".$table." set h_".$provided_hour."='".$cars_provided."' where id='".$row['id']."'";
			
			$updateRS=$db->query($update);
		}
		else {
			$update="insert into ".$table."(h_".$provided_hour.",date) values ('".$cars_provided."','".$train_date."')";
			
			$updateRS=$db->query($update);
			
		
		}
	}
}
?>
<!--
<link href="css/style.min.css" rel="stylesheet" />


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

.rowClass {background-color: #F3F3F3;}

/* color header */
.rowHeading {background-color: #cccccc;}

.train_ava td{
	border: 1px solid #FBCC2A;
	color: rgb(0,51,153);
	cellpadding: 5px;

}
 .train_ava th {
	border: 1px solid #FBCC2A;;
	cellpadding: 5px;	
}

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
/*
#add_form th{background-color: #cccccc;}

#add_form td:nth-child(odd) {
background-color: #DCDCDC;  
color:black;
font-weight:bold;
padding:5px;
}
#add_form td:last-child{background-color:white;}

#add_form td:nth-child(even) { background-color: #f5f5f5; border:1px solid #cccccc; }
*/
select { border: 1px solid rgb(185, 201, 254); color: black; background-color: #FFFACD; }

/* --- mjun -- generate */
a.two:visited {color:black;}
a.two:hover, a.two:active {font-size:120%; color:orange;}

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
</style>
<style type='text/css'>
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
	
	
	--cf-blue-dark: #013E76;
	--cf-gold:      #FDB813;
	--cf-gold-ink:  #3A2D00;
	--cf-red:       #A32D2D;
	--cf-red-bg:    #FCEBEB;
	
}
body { font-family: var(--cf-sans); color: var(--cf-dark); }

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
</style>
<style type='text/css'>
.ccs-page { font-family:var(--cf-sans); color:var(--cf-dark); }
.ccs-page * { box-sizing:border-box; }

/* ── Page header ── */
.ccs-header      { background:var(--cf-blue); border-bottom:3px solid var(--cf-gold); border-radius:6px 6px 0 0; padding:12px 16px; }
.ccs-header h1   { margin:0; font-size:16px; font-weight:700; color:#fff; letter-spacing:.3px; }
.ccs-header .sub { font-size:10px; color:rgba(255,255,255,.6); letter-spacing:.5px; text-transform:uppercase; margin-top:2px; }
/* ── Panel grid ── */
.ccs-grid { display:flex; gap:16px; align-items:flex-start; flex-wrap:wrap; padding:16px; }
.ccs-panel { background:var(--cf-white); border:1px solid var(--cf-border); border-radius:6px;
	box-shadow:0 1px 3px rgba(0,30,80,.08); flex:1 1 300px; min-width:280px; overflow:hidden; }
.ccs-panel-head { background:var(--cf-blue); border-bottom:3px solid var(--cf-gold); padding:9px 14px; }
.ccs-panel-head h3 { margin:0; font-size:12px; font-weight:700; color:#fff; letter-spacing:.4px; text-transform:uppercase; }
.ccs-panel-body { padding:14px; }

/* ── Data table (faults per discipline) ── */
table.ccdr { width:100%; border-collapse:collapse; font-size:12.5px; }
table.ccdr th { background:var(--cf-blue); color:#fff; font-weight:600; font-size:11px; text-transform:uppercase;
	letter-spacing:.3px; padding:7px 8px; border:1px solid var(--cf-blue-dark); text-align:center; }
table.ccdr td { padding:7px 8px; border:1px solid var(--cf-border); text-align:center; }
table.ccdr tr:nth-child(odd) td { background:var(--cf-row-odd); }
table.ccdr th.ccs-discipline { background:var(--cf-white); color:var(--cf-dark); font-weight:600; text-transform:none;
	letter-spacing:normal; border:1px solid var(--cf-border); text-align:left; }

table.ccdr tr td input[type=submit] {
	height:28px; border:none; border-radius:4px; background:#FDB813;
	color:#3A2D00; font-weight:700; font-size:12px; padding:0 14px; cursor:pointer;
}
table.ccdr tr td input[type=submit]:hover { background:#E5A50F; }


/* ── Level badges (identical palette to edit_ccdr.php's .cc-lvl-0..4) ── */
.cf-lvl { display:inline-block; font-size:10px; font-weight:700; border-radius:3px; padding:2px 7px; }
.cf-lvl-1 { background:#E8F5EE; color:#0F6E4E; } .cf-lvl-2 { background:#EAF2FB; color:#0C447C; }
.cf-lvl-3 { background:#FAEEDA; color:#854F0B; } .cf-lvl-4 { background:var(--cf-red-bg); color:var(--cf-red); }

/* ── Legend panel ── */
.ccs-legend-row { display:flex; align-items:flex-start; gap:10px; padding:8px 0; border-bottom:1px solid var(--cf-border); }
.ccs-legend-row:last-child { border-bottom:none; }
.ccs-legend-row .cf-lvl { flex:none; margin-top:1px; min-width:34px; text-align:center; }
.ccs-legend-row span.desc { font-size:12.5px; color:var(--cf-mid); line-height:1.4; }

/* ── Stat panel (AM/PM cancellations, loops, LRV) ── */
.ccs-stat-group { display:flex; gap:10px; margin-bottom:14px; }
.ccs-stat-group:last-child { margin-bottom:0; }
.ccs-stat { flex:1; background:var(--cf-bg); border:1px solid var(--cf-border); border-radius:5px; padding:9px 10px; text-align:center; }
.ccs-stat .lbl { display:block; font-size:9.5px; font-weight:600; color:var(--cf-muted); text-transform:uppercase;
	letter-spacing:.4px; margin-bottom:5px; line-height:1.3; }
.ccs-stat .val { display:block; font-size:19px; font-weight:700; color:var(--cf-dark); font-family:ui-monospace,Consolas,monospace; }
.ccs-stat.ccs-stat--danger .val { color:var(--cf-red); }
.ccs-stat.ccs-stat--gold .val { color:#B9840A; }
.ccs-stat-section-label { font-size:10px; font-weight:700; color:var(--cf-muted); text-transform:uppercase;
	letter-spacing:.5px; margin:0 0 6px; }

/* toolbar search form sits inline inside its cf-toolbar-table cell */
.ta-grid.ta-console .cf-td-search form { margin:0; display:inline-block; }

</style>
<script language='javascript'>

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



<?php
$datenow=date("m/d/Y");
if((isset($_POST['search_date']))||(isset($_SESSION['search_date']))){
	if(isset($_POST['search_date'])){
		
		$datenow=date("m/d/Y",strtotime($_POST['search_date']));
//		$year=$_POST['year'];
//		$month=$_POST['month'];
//		$day=$_POST['day'];
	}
	
	else if(isset($_SESSION['search_date'])){
		$datenow=date("m/d/Y",strtotime($_SESSION['search_date']));

		//$year=$_SESSION['year'];
		//$month=$_SESSION['month'];
		//$day=$_SESSION['day'];
	
	
	}
}
?>

<body>

<div class="ta-grid ta-console">

<!-- Toolbar rebuilt onto the cf-toolbar-table layout this page's CSS
     already defined (date left / search center); the date that used to
     render as a bare <h2> below now lives in the cf-td-date cell. -->
<table cellspacing="0" cellpadding="0" class="cf-toolbar-table">
<tr>
	<td class="cf-td-date">
<?php if((isset($_POST['search_date']))||(isset($_SESSION['search_date']))){ ?>
		<span class="cf-date-label"><?php echo date("F d, Y",strtotime($datenow)); ?></span>
		<span class="cf-date-day"><?php echo date("l",strtotime($datenow)); ?></span>
<?php } else { ?>
		<span class="cf-date-day">No date selected</span>
<?php } ?>
	</td>
	<td class="cf-td-search">
<form action='train hourly.php' method='post'>
<!--
<input type='text' name='search_date' id='search_date' class="easyui-datebox" value='<?php // echo $datenow; ?>' />
-->
<input type="text" name='search_date' id='search_date' />
<input type=submit value='Access Monitoring' />
</form>
	</td>
</tr>
</table>
<?php
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");

$timetable_code="";

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
	$timetable=$ava_date;
	
	$sql="select * from timetable_day where train_date like '".$timetable."%%'";
	$rs=$db->query($sql);
	
	$nm=$rs->num_rows;

	if($nm>0){
		$row=$rs->fetch_assoc();
		$timetable_code=$row['timetable_code'];
	
	}
	
/* date heading moved into the toolbar's cf-td-date cell above */
	
}



?>
<?php
if ($ULev>=2){
	$SRemove = ""; 
	$SRemove2 = "two";	
} else {
	$SRemove = "disabled";
	$SRemove2 = "disabled";	
}
?>
<div class="alink"></div>

<table class='train_ava' width=100%>
<tr class='rowHeading'>
<th>Time</th>
<th>Headway</th>
<th>No. of Cars Req'd.</th>
<th>No. of Cars Provided</th>
<th>Reserve Req'd.</th>
<th>Reserve Provided</th>
<th>Cancelled Departure</th>
<th>Cancelled Loop</th>
<th>Incident No.</th>
<th>Remarks</th>
</tr>
<?php
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");

if($timetable_code==""){
}
else {

	$headway="select * from headway_1 inner join headway_2 on headway_1.timetable_code=headway_2.timetable_code where headway_1.timetable_code='".$timetable_code."'";
	
	$hRS=$db->query($headway);
	
	$hRow=$hRS->fetch_assoc();
	
	$reserve="select * from reserve_1 where date='".$timetable."'";
	$rRS=$db->query($reserve);
	
	$rRow=$rRS->fetch_assoc();


	$reserve="select * from reserve_2 where date='".$timetable."'";
	$rRS=$db->query($reserve);
	
	$rRow2=$rRS->fetch_assoc();
	



	$provided="select * from cars_provided_1 where date='".date("Y-m-d",strtotime($timetable))."'";
	$pRS=$db->query($provided);
	
	$pRow=$pRS->fetch_assoc();


	$provided="select * from cars_provided_2 where date='".date("Y-m-d",strtotime($timetable))."'";
	$pRS=$db->query($provided);
	
	$pRow2=$pRS->fetch_assoc();



	
	
	$sql="select * from train_availability_required inner join timetable_hour on train_availability_required.time=timetable_hour.time where timetable_code='".$timetable_code."'";
	
	$rs=$db->query($sql);
	
	$nm=$rs->num_rows;
	
	for($i=0;$i<$nm;$i++){
		$row=$rs->fetch_assoc();
		$hour=$row['time'];
		$nexthour=$hour+1;
		$loop_cancelled=0;		

?>		
	<tr <?php if($i%2>0){ echo "class='rowClass'"; } ?>>
		<td><?php echo $row['label']; ?></td>
		<td>
		<?php 
		if($hour==201){
		echo $hRow['h_20']; 
		
		}
		else if($hour==203){
		echo $hRow['h_21']; 
		
		}
		else if($hour=="501"){
			echo "6.00-4.00 mins.";
			
		}
		else {
		echo $hRow['h_'.str_replace("30","",$hour)]; 
		}
		
		?></td>
		<td align=center><?php echo $row['cars_required']; ?></td>
		<?php
		
		if($hour=="201"){
			$timestamp="between '".date("Y-m-d H:i:s",strtotime($timetable." 20:01:00"))."' and '".date("Y-m-d H:i:s",strtotime($timetable." 20:30:00"))."'";

		}
		else if($hour=="203"){
			$timestamp="between '".date("Y-m-d H:i:s",strtotime($timetable." 20:31:00"))."' and '".date("Y-m-d H:i:s",strtotime($timetable." 21:00:00"))."'";

		}
		else if($hour=="501"){
			$timestamp="between '".date("Y-m-d H:i:s",strtotime($timetable." 05:01:00"))."' and '".date("Y-m-d H:i:s",strtotime($timetable." 05:30:00"))."'";
		
		}
		else {
//			$hour=str_replace("30",":30",$hour);
			
			if($timetable_code=="7"){ $nexthour+=100; }
			$nexthour=str_replace("31",":31",$nexthour);

			$timestamp="between '".date("Y-m-d H:i:s",strtotime($timetable." ".str_replace("30",":31",$hour).":00"))."' and '".date("Y-m-d H:i:s",strtotime($timetable." ".$nexthour.":00"))."'";		
		}
		

		$car_sql="select * from train_availability inner join train_ava_time on train_availability.id=train_ava_time.train_ava_id where date ".$timestamp."  and status='active' and type='revenue' and insert_time is not null";
		$car_rs=$db->query($car_sql);
		$car_nm=$car_rs->num_rows;
		
		$cars_provided=$car_nm*3;

		
		
		
		for($n=0;$n<$car_nm;$n++){
			$car_row=$car_rs->fetch_assoc();
			if($car_row['cancel_loop']=="SB"){
//				$loop_cancelled+=.5;
			}
			else if($car_row['cancel_loop']=="NB"){
//				$loop_cancelled++;
			
			}
			
		}
		
// 		$car_sql3="select sum(cancel) as cancel from train_incident_view where train_ava_id in (select id from train_availability where date like '".$timetable."%%') and incident_date ".$timestamp."";

		
  		$car_sql3="select sum(cancel) as cancel from incident_report where incident_date ".$timestamp." and level in ('3','4')";
//  	$car_sql3="select sum(cancel) as cancel from train_incident_view where incident_date ".$timestamp."";
//		$car_sql3="select sum(cancel) as cancel from train_incident_view where train_ava_id in (select id from train_availability where date like '".$timetable."%%' and status='active') and incident_date ".$timestamp."";
		$car_rs3=$db->query($car_sql3);
		$car_nm3=$car_rs3->num_rows;
		if($car_nm3>0){
			$car_row3=$car_rs3->fetch_assoc();
			$loop_cancelled=$car_row3['cancel']*1;
		
		}
		
		

		$car_sql2="select * from train_availability where date ".$timestamp." and type='revenue' and status='cancelled'";
		$car_rs2=$db->query($car_sql2);
		$car_nm2=$car_rs2->num_rows;
		
//		$cars_cancelled=$car_nm2;
		if(is_decimal($loop_cancelled)){
			$loop_cancelled=floor($loop_cancelled);
			if($loop_cancelled==0){ $loop_cancelled="1/2"; }
			else { $loop_cancelled.=" 1/2"; }
		}		
		$cars_cancelled=0;	
	//	$loop_cancelled+=$cars_cancelled;
		
		
		
  		$car_sql3="select sum(cancel) as cancel from incident_report where incident_date ".$timestamp." and incident_type in ('gradual','c_loops')";
		
		$car_rs3=$db->query($car_sql3);
		$car_nm3=$car_rs3->num_rows;
		if($car_nm3>0){
			$car_row3=$car_rs3->fetch_assoc();
			$cars_cancelled+=$car_row3['cancel']*1;
		
		}
				
		
  		$car_sql3="select sum(cancel) as cancel from incident_report inner join incident_description on incident_report.id=incident_description.incident_id where incident_date ".$timestamp." and level in ('3','4') and cancel>=1 and incident_type in ('rolling')";



		$car_rs3=$db->query($car_sql3);
		$car_nm3=$car_rs3->num_rows;
		if($car_nm3>0){
			$car_row3=$car_rs3->fetch_assoc();
			$cars_cancelled+=$car_row3['cancel']*1;
			
		}
		
		
		?>
		
		
		<td align=center>
		<?php 
		if($hour==201){
			$hourLabel="20";
		
		}
		else if($hour=="203"){
			$hourLabel="21";
		}
		else {
			$hourLabel=str_replace(":","",str_replace("30","",$hour));
		
		}
		if($hourLabel<=12){
				
			echo $pRow['h_'.str_replace("30","",$hourLabel)]; 
		}
		else {
			echo $pRow2['h_'.str_replace("30","",$hourLabel)]; 
		
		}
		?>
		
		<td align=center><?php if($row['reserve_required']==""){ echo "3"; } else { echo $row['reserve_required']; } ?></td>
		
		<td align=center>
		<?php 
		if($hour==201){
			$hourLabel="20";
		
		}
		else if($hour=="203"){
			$hourLabel="21";
		}
		else {
			$hourLabel=str_replace(":","",str_replace("30","",$hour));
		
		}
		
		if($hourLabel<=12){
			echo $rRow['h_'.str_replace("30","",$hourLabel)]; 
		}
		else {
			echo $rRow2['h_'.str_replace("30","",$hourLabel)]; 
		
		}
		
		?>
		
		</td>
		<td align=center><?php echo $cars_cancelled; ?></td>
		<td align=center><?php echo $loop_cancelled; ?></td>
		<td>
		<?php
		
//		$incident_sql="select * from train_incident_view inner join incident_description on train_incident_view.incident_id=incident_description.incident_id where incident_date like '".$timestamp."%%'";
//		$incident_sql="select * from train_incident_view inner join incident_description on train_incident_view.incident_id=incident_description.incident_id where train_ava_id in (select id from train_availability where date ".$timestamp." and type='revenue') and level='3'";
//		$incident_sql="select * from train_incident_view inner join incident_description on train_incident_view.incident_id=incident_description.incident_id where incident_date ".$timestamp." and level='3'";
		$incident_sql="select * from incident_report inner join incident_description on incident_report.id=incident_id where incident_date ".$timestamp." and ((incident_type in ('rolling') and level in ('3','4')) or (incident_type in ('gradual','c_loops','r_trains','unload','nload')))";
		$incident_rs=$db->query($incident_sql);
		
		$incident_nm=$incident_rs->num_rows;
		if($incident_nm>0){
			for($m=0;$m<$incident_nm;$m++){
				$incident_row=$incident_rs->fetch_assoc();
	
				if($m==0){
					echo "<a href='#' class='$SRemove' onclick='window.open(\"edit_ccdr.php?ir=".$incident_row['incident_id']."\")'>IN ".$incident_row['incident_no'];
					if(($incident_row['incident_type']=="rolling")||($incident_row['incident_type']=="unload")||($incident_row['incident_type']=="nload")){
					echo "(".$incident_row['index_no'].")";

					}
					echo "</a>";
				}
				else {
					echo ", <a href='#' class='$SRemove' onclick='window.open(\"edit_ccdr.php?ir=".$incident_row['incident_id']."\")'>IN ".$incident_row['incident_no'];
					if(($incident_row['incident_type']=="rolling")||($incident_row['incident_type']=="unload")||($incident_row['incident_type']=="nload")){
					echo "(".$incident_row['index_no'].")";

					}
					echo "</a>";

				}
			}
		}
		else {
			echo "&nbsp;";			
		
		}

		?>
		</td>
		<td>
		<?php
			$remarks_sql="select * from train_hourly_remarks where hourly_date='".$timetable."' and hour='".$hour."' limit 1";
			$remarks_rs=$db->query($remarks_sql);
			$remarks_nm=$remarks_rs->num_rows;
			
			if($remarks_nm>0){
				$remarks_row=$remarks_rs->fetch_assoc();
				echo $remarks_row['remarks'];
			}
			else {
				echo "&nbsp;";
			
			}
		
		?>
		</td>

	</tr>

<?php	
	
	}
}



?>
</table>
<!-- mjun -->
<?php
if ($timetable_code<>'') { ?>
	
<a href='#' class="<?php echo $SRemove2; ?>" onclick='window.open("generate_star.php?star_id=<?php echo $timetable_code; ?>&timetable=<?php echo $timetable; ?>");'><b>Generate Printout</b></a>

<br>

<a href='#' class="<?php echo $SRemove2; ?>"  onclick='window.open("generate_ccip.php?ccip_id=<?php echo $timetable_code; ?>&ccip_date=<?php echo $timetable; ?>");'><b>Generate Insertion Form</b></a>

<?php }
?>
</div>
</div>



<div class="ccs-page">

<div class="ccs-grid">
<div class="ccs-panel" style="flex-basis:340px;">
<div class="ccs-panel-head"><h3>Enter Cars Provided
</h3></div>

<div class="ccs-panel-body">

<form action='train hourly.php' method='post'>
<table class='ccdr' id='add_form' name='add_form'>
<tr>
<th class="ccs-discipline">Enter Cars Provided</th>
<td><input type='text' name='cars_provided' id='cars_provided' /></td>
</tr>
<tr>
<th class="ccs-discipline">Enter Hour</th>
<td>
<select name='provided_hour' id='provided_hour'>
<?php
//$db=new mysqli("localhost","root","","transport");
$sql="select * from timetable_hour order by time*1"; 
$rs=$db->query($sql);
$nm=$rs->num_rows;

for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
?>
<option value='<?php if($row['time']=="201"){ echo "20"; } else if($row['time']=="203"){ echo "21"; } else { echo $row['time']; } ?>'><?php echo " ".$row['label']." "; ?></option>
<?php
}
?>
</select>
</td>
</tr>
<tr>
<td colspan=2 align=center>

<?php
if ($ULev>=2){
?>
<input type=submit value='Submit' />
<?php
} else {
?>
<input type=submit value='Submit' disabled />
<?php
}
?>
</td>
</tr>
</table>
</form>
</div>
</div>
<div class="ccs-panel" style="flex-basis:280px;">
<div class="ccs-panel-head"><h3>Reserve Provided
</h3></div>
<div class="ccs-panel-body" >



<form action='train hourly.php' method='post'>
<table class='ccdr' id='add_form' name='add_form'>

<tr>
<th class="ccs-discipline" >Enter Reserve</th>
<td><input type='text' name='reserve' id='reserve' /></td>
</tr>
<tr>
<th class="ccs-discipline" >Enter Hour</th>
<td>
<select name='reserve_hour' id='reserve_hour'>
<?php
//$db=new mysqli("localhost","root","","transport");
$sql="select * from timetable_hour order by time*1"; 
$rs=$db->query($sql);
$nm=$rs->num_rows;

for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
?>
<option value='<?php if($row['time']=="201"){ echo "20"; } else if($row['time']=="203"){ echo "21"; } else { echo $row['time']; } ?>'><?php echo " ".$row['label']." "; ?></option>
<?php
}
?>
</select>
</td>
</tr>

<tr>
<td class="ccs-discipline" colspan=2 align=center>

<?php
if ($ULev>=2){
?>
<input type=submit value='Submit' />
<?php
} else {
?>
<input type=submit value='Submit' disabled />
<?php
}
?>
</td>
</tr>
</table>
</form>
</div>
</div>


<div class="ccs-panel" style="flex-basis:320px;">
<div class="ccs-panel-head"><h3>Train Hourly Remarks

</h3></div>


<div class="ccs-panel-body"  >

<form action='train hourly.php' method='post'>
<table class='ccdr' id='add_form' name='add_form'>
<tr>
<th  class="ccs-discipline">Enter Remarks</th>
<td><textarea name='remarks' cols=50></textarea></td>
</tr>
<tr>
<th  class="ccs-discipline">Enter Hour</th>
<td>
<select name='remarks_hour' id='remarks_hour'>
<?php
//$db=new mysqli("localhost","root","","transport");
$sql="select * from timetable_hour order by time*1"; 
$rs=$db->query($sql);
$nm=$rs->num_rows;

for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
?>
<option value='<?php if($row['time']=="201"){ echo "20"; } else if($row['time']=="203"){ echo "21"; } else { echo $row['time']; } ?>'><?php echo " ".$row['label']." "; ?></option>
<?php
}
?>
</select>
</td>
</tr>
<tr>
<td colspan=2 align=center  class="ccs-discipline">

<?php
if ($ULev>=2){
?>
<input type=submit value='Submit' />
<?php
} else {
?>
<input type=submit value='Submit' disabled />
<?php
}
?>
</td>
</tr>
</table>
</form>
</div>
</div>
</div>
</div>
</body>
		<script src="js/jquery-1.10.2.min.js"></script>
	<script src="js/jquery-migrate-1.2.1.min.js"></script>	
		<script src="js/jquery-ui-1.10.3.custom.min.js"></script>	
		<script src="js/jquery.ui.touch-punch.js"></script>	
		<script src="js/modernizr.js"></script>	
		<script src="js/bootstrap.min.js"></script>	

<!--
<script src="js/date.js"></script>	
-->