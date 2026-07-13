<?php
require("Tmenu.php");
?>
<!--Modify: mjun
 Modified date: Aug 5, 2014
 Modified: Change screen layout
-->
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
<?php require("slide_panel.php"); ?>
<style type='text/css'>

.rowClass {background-color: #F3F3F3;}

/* color header */
.rowHeading {background-color: #cccccc}

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

/* --- mjun -- generate */
a.two:visited, a.two.link {color:black;}
a.two:hover, a.two:active {font-size:120%; color:orange;}

}


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

<script language='javascript'>
$(function() {
    $( "#search_date" ).datepicker({
      changeMonth: true,
      changeYear: true,
      showAnim: "clip"
    });    
});

</script>


<body>
<div class="ta-grid ta-console">
<form action='onboard equipment.php' method='post' class="stat-toolbar">
<!--
<input type='text' name='search_date' id='search_date' class='datepicker' />

<input name='search_date' id='search_date' type="text" class="easyui-datebox" />
-->

<input type="text" name='search_date' id='search_date' />

<input type=submit value='Access Monitoring' />
</form>
<?php
if(isset($_POST['search_date'])){

?>
<?php

//$month=$_POST['month'];
//$day=$_POST['day'];
//$year=$_POST['year'];

$ccdr_date=date("Y-m-d",strtotime($_POST['search_date']));
$ccdr_label=date("F d, Y",strtotime($ccdr_date));

	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");


$sqlCCDR="select * from train_availability inner join train_compo on train_availability.id=tar_id where train_availability.date like '".$ccdr_date."%%' and status='active' and type='revenue' group by car_no";

//$sqlCCDR="select * from train_availability where date like '".$ccdr_date."%%' and type='revenue' and status='active'";

$sqlRS=$db->query($sqlCCDR);
$sqlCCDRNM=$sqlRS->num_rows;

$cars=$sqlCCDRNM;

$sqlEquipt="select * from equipment where category='OB' order by equipment_name";
$rs=$db->query($sqlEquipt);
$nm=$rs->num_rows;
for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
	$equipment["Equipment_".$row['id']]['name']=$row['equipment_name'];
	$equipment["Equipment_".$row['id']]['id']=$row['id'];


	$sqlCount="select *, equipt from incident_description inner join incident_cars on incident_description.incident_id=incident_cars.incident_id where incident_description.incident_id in (select incident_id from train_union where trainDate like '".$ccdr_date."%%') and equipt='".$row['id']."' group by incident_cars.car_no";
	$countrs=$db->query($sqlCount);
	$countnm=$countrs->num_rows;

	$equipment["Equipment_".$row['id']]["nw_count"]=$countnm;

}

//$sql="SELECT count(*) as equipt_count,equipt FROM incident_report inner join where incident_date like '".$ccdr_date."%%' group by equipt";
//$sql="select count(*) as equipt_count, equipt from incident_description where incident_id in (select incident_id from train_union where trainDate like '".$ccdr_date."%%') and equipt in (select id from equipment where category='OB') group by car_no,equipt";


/*
$rs=$db->query($sql);
$nm=$rs->num_rows;
for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
	$equipment["Equipment_".$row['equipt']]["nw_count"]=$row['equipt_count'];


}
*/
?>
<a href='#' class="two pull-right" onclick='window.open("generate_onboard.php?onboard_date=<?php echo $ccdr_date; ?>");'><b>Generate Printout</b></a>

<table width=100%  class='train_ava'>
<tr class='rowHeading'>
<th rowspan=2><?php echo $ccdr_label; ?></th>
<th colspan=2>No. of Cars</th>
<th rowspan=2>Remarks</th>
</tr>
<tr class='rowHeading'>
<th>Operational</th>
<th>With Defect</th>
</tr>
<?php
$rs=$db->query($sqlEquipt);
$nm=$rs->num_rows;
for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
	$count=$cars;
?>
<tr>
	<td><?php echo $equipment["Equipment_".$row['id']]['name']; ?></td>
	<td align=center>
	<?php 
	$provided=$cars-($equipment["Equipment_".$row['id']]['nw_count']*1); 
	
	if($provided<0){ $provided=0; }
	echo $provided;
	?>
	</td>
	<td align=center><?php echo $equipment["Equipment_".$row['id']]['nw_count']*1; ?></td>
	<td>
	<?php
	$nw_count=$equipment["Equipment_".$row['id']]['nw_count'];
	if($nw_count>0){



//		$sql2="SELECT * FROM incident_report where incident_date like '".$ccdr_date."%%' and equipt='".$equipment["Equipment_".$row['id']]['id']."'";
		$sql2="select * from incident_description inner join incident_report on incident_id=incident_report.id where incident_id in (select incident_id from train_union where trainDate like '".$ccdr_date."%%') and incident_description.equipt='".$equipment["Equipment_".$row['id']]['id']."'";
//		echo $sql2;
		$rs2=$db->query($sql2);
		$nm2=$rs2->num_rows;
		for($n=0;$n<$nm2;$n++){
			$row2=$rs2->fetch_assoc();
			if($n==0){



			echo "<a href='#' onclick='openSlidePanel(\"edit_ccdr.php?ir=".$row2['incident_id']."&embed=1\",\"Incident - ".htmlspecialchars($row2['car_no'])."\")')'>Car # ".$row2['car_no']." - See IN ".$row2['incident_no']."</a>"; 
			
			}
			else {
				echo ", <a href='#' onclick='openSlidePanel(\"edit_ccdr.php?ir=".$row2['incident_id']."&embed=1\",\"Incident - ".htmlspecialchars($row2['car_no'])."\")'>Car # ".$row2['car_no']." - See IN ".$row2['incident_no']."</a>"; 
			}
		?>
			<br>	
		<?php
		}
	}
	else {
		echo "&nbsp;";
	}
	?>
	</td>	
</tr>
<?php
}
?>
</table>
<br>
<br>
<table class='train_ava' width=100%>
<tr class='rowHeading'>
<th>Category</th>
<th>Count</th>
</tr>
<?php

$trainSQL="select * from train_availability inner join train_compo on train_availability.id=tar_id where train_availability.date like '".$ccdr_date."%%' and status='active' and type='revenue' group by car_no";
//$trainSQL="select * from train_availability inner join train_ava_time on train_availability.id=train_ava_id where date like '".$ccdr_date."%%' and status='active' and type='revenue' and insert_time is not null";
$trainRS=$db->query($trainSQL);
$trainNM=$trainRS->num_rows;
$trainNM*=1;
$lrv=$trainNM;


$trainSQL="select * from train_availability inner join train_ava_time on train_availability.id=train_ava_id where date like '".$ccdr_date."%%' and status='active' and type='unimog' and insert_time is not null";
$trainRS=$db->query($trainSQL);
$trainNM=$trainRS->num_rows;
$trainNM*=1;
$unimog=$trainNM;

$trainSQL="select * from train_availability inner join train_ava_time on train_availability.id=train_ava_id where date like '".$ccdr_date."%%' and status='active' and type='finance' and insert_time is not null";
$trainRS=$db->query($trainSQL);
$trainNM=$trainRS->num_rows;
$trainNM*=1;
$finance=$trainNM;

$trainSQL="select * from train_availability inner join train_ava_time on train_availability.id=train_ava_id where date like '".$ccdr_date."%%' and status='active' and type='test' and insert_time is not null";
$trainRS=$db->query($trainSQL);
$trainNM=$trainRS->num_rows;
$trainNM*=1;
$test=$trainNM;

?>
<tr>
<td>Number of LRV Used:</td>
<td align="center"><?php echo $lrv; ?></td>
</tr>
<tr>
<td>Finance Train</td>
<td align="center"><?php echo $finance; ?></td>
</tr>
<tr>
<td>Test Train</td>
<td align="center"><?php echo $test; ?></td>
</tr>
<tr>
<td>UNIMOG</td>
<td align="center"><?php echo $unimog; ?></td>
</tr>

</table>
<!--
<br>
<a href='#' class="two" onclick='window.open("generate_onboard.php?onboard_date=<?php echo $ccdr_date; ?>");'><b>Generate Printout</b></a>
-->
<?php
}
?>
</div>
</body>
		<script src="js/jquery-1.10.2.min.js"></script>
	<script src="js/jquery-migrate-1.2.1.min.js"></script>	
		<script src="js/jquery-ui-1.10.3.custom.min.js"></script>	
		<script src="js/jquery.ui.touch-punch.js"></script>	
		<script src="js/modernizr.js"></script>	


<script src="js/date.js"></script>