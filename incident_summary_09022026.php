<?php 
session_start();

/* ==========================================================================
   @dashlink -- A date in the URL is an instruction, not a suggestion.

   This page had no $_GET handling at all: the displayed window came only from
   its own From/To form and from $_SESSION['search_date2'] / ['search_date'].
   dashboard.php links here with dash_link('incidents', $view_date), which
   carries the operating date the user was looking at -- and that date was
   discarded on arrival.

   It LOOKED like it worked. On a fresh session the fall-through default is
   date("Y-m-d"), which matches the dashboard's own default view date, so the
   two agreed by coincidence. Choose a range once and the session holds it, and
   every later arrival from the dashboard renders that range instead of the day
   that was clicked. Viewing any non-today date on the dashboard and clicking
   Incidents never worked either.

   Arriving with an explicit date CLEARS a previously chosen range. You clicked
   one day on one dashboard; silently showing three weeks instead is the
   surprise this fixes, and a range that survives navigation is the mechanism
   that caused it.

   Writing THROUGH to the session rather than bypassing it matters: the sort
   form, the printout bar and the From/To boxes all read the session, so a
   parallel "GET wins" variable would leave them disagreeing with the table.
   It also means refreshing after arriving from the dashboard shows the same
   thing, and the URL can be shared.

   Parameter names match dashboard.php's own convention -- it reads $_GET['d']
   for its view date at the top of that file. 'to' is accepted so a future
   caller can hand over a real range; nothing sends it today.
   ======================================================================== */
$dlFrom = (isset($_GET['d'])  && trim((string)$_GET['d'])  !== '') ? strtotime(trim((string)$_GET['d']))  : false;
$dlTo   = (isset($_GET['to']) && trim((string)$_GET['to']) !== '') ? strtotime(trim((string)$_GET['to'])) : false;

if($dlFrom !== false){
	/* Stored in m/d/Y because that is what the From/To inputs post and what
	   every strtotime() below already expects. A "to" earlier than "from" is
	   treated as no range rather than silently inverted -- an inverted BETWEEN
	   returns nothing, which reads as "no incidents" instead of as a bad link. */
	$_SESSION['search_date2'] = date("m/d/Y", $dlFrom);
	/* UNSET, not "". isset("") is TRUE, so an empty string still satisfies the
	   isset() guards further down; strtotime("") is false and date() coerces
	   that to 0, giving a To date of 1970-01-01 and a BETWEEN that matches
	   nothing. Removing the key is the only way to say "no To date" that those
	   guards actually hear. */
	if($dlTo !== false && $dlTo >= $dlFrom){ $_SESSION['search_date'] = date("m/d/Y", $dlTo); }
	else                                   { unset($_SESSION['search_date']); }
}
?>
<?php
require("Tmenu.php");
if (file_exists(dirname(__FILE__)."/type_scale.php")) require_once(dirname(__FILE__)."/type_scale.php");
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
<?php
/* @filterui -- the silent file_exists guard is right for a rollout, but it is
   also indistinguishable from "the file loaded and did nothing". The else
   branch prints the directory PHP actually searched, so View Source answers
   the question outright instead of by inference. Harmless to leave in. */
if(file_exists(dirname(__FILE__)."/datepicker_theme.php")){
	include(dirname(__FILE__)."/datepicker_theme.php");
} else {
	echo "<!-- ccs-datepicker-theme MISSING: looked in ".dirname(__FILE__)." -->\n";
}
?>
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

/* @printbar -- was #FFFACD (lemon chiffon) on a page whose palette is navy and
   gold; a leftover from the old theme. Neutral, matching the other reports. */
select { border: 1px solid #D8D2C2; color: #1A2238; background-color: #FFFFFF; border-radius: 4px; }

/* --- mjun */
a.two:visited {color:black;}
/* @printbar -- was font-size:120%, which reflows the row under the cursor
   every time it passes over a link. Underline instead: same affordance, no
   layout change. */
a.two:hover, a.two:active {text-decoration:underline; color:#003E76;}

a.two2:visited {color:#ca0000;}
a.two2:hover {text-decoration:underline; color:#8A1F1F;}
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

/* @printbar -- The action row under the table. Dark bar so the gold pill reads
   as the primary action, matching the toolbar above the table rather than
   floating loose on the page background. */
.ta-grid.ta-console .cf-printbar {
	display: flex; align-items: center; justify-content: space-between;
	flex-wrap: wrap; gap: 8px;
	background: var(--cf-blue); border-radius: 0 0 6px 6px;
	padding: 8px 14px; margin: 0 0 14px;
}
.ta-grid.ta-console .cf-printbar-label {
	color: rgba(255,255,255,.85); font-size: 11px; font-weight: 600;
	text-transform: uppercase; letter-spacing: .06em;
}
.ta-grid.ta-console .cf-printbar-range {
	display: inline-block; margin-left: 8px;
	color: #FFFFFF; font-weight: 400; text-transform: none; letter-spacing: 0;
	font-size: 12px;
}
.ta-grid.ta-console .cf-printbar-actions { display: flex; flex-wrap: wrap; gap: 0; }
/* margin-left on .cf-tbtn already spaces these; the first needs none so the
   group aligns flush when it wraps to its own line. */
.ta-grid.ta-console .cf-printbar-actions .cf-tbtn:first-child { margin-left: 0 !important; }
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
	/* @printbar -- the space was here too; same file, three references. */
	self.location="incident_summary.php";
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
/* @dashlink -- Only ever assigned inside conditional branches, then read
   unconditionally at the $availability_date2=="" test below. On any path that
   set neither, that read was an undefined-variable notice -- and with
   display_errors on, the warning HTML lands mid-page. */
$availability_date2="";

/* @dashlink -- Dead, and a trap. This assigns search_date -- the TO date --
   into $availability_date, which is the FROM date everywhere else. It is
   harmless today only because all three branches of the chain further down
   overwrite both variables before either is read. Left commented rather than
   deleted: if it is ever reinstated it must read search_date2.
if(isset($_SESSION['search_date'])){
//$month=$_SESSION['month'];
//$day=$_SESSION['day'];
//$year=$_SESSION['year'];

$availability_date=$_SESSION['search_date'];

$datenow=date("m/d/Y",strtotime($availability_date));
}
*/
?>
<div class="ta-grid ta-console">

<table cellspacing="0" cellpadding="0" class='stat-toolbar'>
<tr>
	<td style="padding:8px 14px;vertical-align:middle;white-space:nowrap;width:1%;border:none">
		<form action='incident summary.php?tt=2a7b85131d93ffbaacc73f7ff024b55a' method='post' >
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
	/* @dashlink -- was $_SESSION['search_date']="". Survivable on THIS request
	   because $availability_date2 is blanked on the next line, but the key
	   stays set for the next one, where isset() passes and the To date resolves
	   to 1970-01-01. Submitting a From with no To and then navigating away and
	   back was enough to trigger it. */
	unset($_SESSION['search_date']);
	$availability_date2="";
}



$_SESSION['search_date2']=$_POST['search_date2'];

}
else {
if(isset($_SESSION['search_date2'])){

$availability_date=date("Y-m-d",strtotime($_SESSION['search_date2']));
$datenow=date("m/d/Y",strtotime($_SESSION['search_date2']));

/* @dashlink -- was isset(). A blank or unparseable value passed that test and
   produced 1970-01-01, which is not "" and so opened the range branch below
   with a To date decades in the past -- a BETWEEN matching nothing, presented
   as "no incidents". Guarding on a value that actually parses means no future
   writer can reopen the hole by storing "". */
$ssTo = isset($_SESSION['search_date']) ? trim((string)$_SESSION['search_date']) : '';
if($ssTo !== '' && strtotime($ssTo) !== false){
	$availability_date2=date("Y-m-d",strtotime($ssTo));
//	$datenow=$datenow.=" - ".date("m/d/Y",strtotime($_SESSION['search_date']));
	/* @dashlink -- This branch is the NO-POST path, so $_POST['search_date']
	   does not exist here. The assignment raised an undefined-index notice and
	   wrote null into the session; isset(null) is false, so the To date erased
	   itself and the range silently decayed to a single open-ended day on the
	   next request. The line had no purpose even when it worked -- it assigned
	   the session value back to itself.
	$_SESSION['search_date']=$_POST['search_date'];
	*/
	
	
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
<form action='incident_summary.php?tt=2a7b85131d93ffbaacc73f7ff024b55a' method='post'>
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


<?php
/* @printbar -- Was three <a class="two pull-right"> with literal "|" characters
   between them. pull-right floats each one, so the anchors stacked right in
   REVERSE source order while the pipes stayed in the text flow -- two orphaned
   bars sitting wherever the flow left them.
   Now one flex row, source order preserved, with the pipes gone: the pill
   borders do the separating that the bars were standing in for.

   The three build different documents from the SAME date range, so the range
   is stated once above them rather than implied three times. */
$isPrintFrom = isset($availability_date)  ? $availability_date  : '';
$isPrintTo   = isset($availability_date2) ? $availability_date2 : '';
$isPrintQS   = "ccdr=".urlencode($isPrintFrom)."&ccdr2=".urlencode($isPrintTo);
?>
<div class="cf-printbar">
	<span class="cf-printbar-label">Control Center Daily Report
		<?php if($isPrintFrom !== ''){ ?><span class="cf-printbar-range"><?php
			echo htmlspecialchars(date("d M Y", strtotime($isPrintFrom)));
			if($isPrintTo !== '' && $isPrintTo !== $isPrintFrom){
				echo ' &ndash; '.htmlspecialchars(date("d M Y", strtotime($isPrintTo)));
			}
		?></span><?php } ?>
	</span>
	<span class="cf-printbar-actions">
		<?php /* Primary = the current format. The other two are named for what
		         they are rather than all three reading "Generate ... Printout",
		         which said nothing about which to pick. */ ?>
<?php
		/* @nisdirect -- ccadmin generates the NIS spreadsheet from here instead of
		   going to weekly_printout.php and clicking Generate NIS printout there.
		   That trip was only ever navigation: generate_nis2.php is self-contained
		   and takes the same ccdr/ccdr2 range this bar already knows, so nothing
		   on the weekly page contributes to the document.

		   The username is compared case-insensitively and trimmed -- session
		   values arrive however the login page stored them. Kept as one named
		   variable so the account list is a one-line change if it grows.

		   isset() matters: the previous form read $_SESSION['username'] directly,
		   which emits a warning for a logged-out or expired session and, with
		   display_errors on, prints it into the middle of the print bar. */
		$isNisUser = (isset($_SESSION['username'])
		              && strtolower(trim($_SESSION['username'])) === 'ccadmin');

		if($isNisUser && $isPrintFrom !== ''){ ?>
		<?php /* No range, no button: generate_nis2 with an empty ccdr produces a
		         spreadsheet for nothing, and the click gives no clue why. */ ?>
		<?php } ?>
	</span>
</div>
<?php if($isNisUser && $isPrintFrom !== ''){ ?>
<script>
/* @nisdirect -- same mechanism weekly_printout.php uses: a hidden iframe on
   generate_nis2.php with &dl=1 streams the .xls as a download, so no third
   window opens. Dates are read from the button's data-* attributes at click
   time rather than echoed here, matching wpGenerateNIS(). */
function isGenerateNIS(){
	var btn = document.getElementById('isNisBtn');
	if(!btn) return;
	var d1  = btn.getAttribute('data-ccdr')  || '';
	var d2  = btn.getAttribute('data-ccdr2') || '';
	var url = "generate_nis2.php?ccdr=" + encodeURIComponent(d1) +
	          "&ccdr2=" + encodeURIComponent(d2) + "&dl=1";
	var f = document.getElementById('isNisFrame');
	if(!f){
		f = document.createElement('iframe');
		f.id = 'isNisFrame';
		f.style.display = 'none';
		document.body.appendChild(f);
	}
	f.src = url;
}
</script>
<?php } ?>

<!-- header -->
<table width=95% class='train_ava'>
<tr class='rowHeading'>
<th rowspan=2>Incident No.</th>
<th rowspan=2>Incident Date/Time</th>
<th rowspan=2>Time Resolved</th>
<th rowspan=2>Incident<br> Duration</th>

<th rowspan=2>Index No</th>
<th rowspan=2>Car(s)</th>
<th rowspan=2>Location</th>
<th rowspan=2>Description</th>
<th rowspan=2>Reported By</th>

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
			$description=$row['description'].",  ";
		
		}
		else if(($incident_type=="unload")||($incident_type=='nload')){
			if($carClause==""){ } else { $carClause=" Car(s) ".$carClause.", "; }
			
			$description=$row['description'].",";



		}
		else {
			$description.=$row['description'];
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
<td align=center><?php if(isset($row['resolution_date'])){ echo date("Y-m-d H:iA",strtotime($row['resolution_date'])); } ?></td>
<td><?php echo $row['duration']; ?></td>

<td align=center>
			<?php echo $row['index_no']; ?>
</td>
<td align=center>
			<?php echo $carClause; ?>
</td>
<td align=center>

<?php
echo $location.($direction!=""?"  ".$direction:"")
?>
</td>

<td><?php echo $description; ?></td>

<td><?php echo $reported_by; ?></td>

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
$defectsSQL="select * from incident_equipt where incident_id='".$row['incident_id']."'";

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
<?PHP
if(!isset($_GET['tt'])){
	?>

<td valign=center align=center><a href='#' class="LDel" onclick='deleteIncident("<?php echo $row['incident_id']; ?>")'>X</a></td>
<?PHP
}
?>
</tr>
<?php
}
?>
</table>
</div>
<?php /* @printbar -- A second "Generate Printout" lived here, commented out.
         It called generate_ccdr.php with ccdr= only and no ccdr2=, so it printed
         a single date where the bar above prints a range -- two identically
         labelled links doing different things. Already dead; removed so it
         cannot be uncommented by someone who reads only the label. */ ?>
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