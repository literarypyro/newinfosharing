<?php
require_once("db_config.php"); /* centralized credentials -- see db_config.php */
//require("Tmenu.php");
?>



<link href="css/style.min.css" rel="stylesheet" /> 
<link href="css/bootstrap.min.css" rel="stylesheet" /> 


<link rel="stylesheet" href="jquery-ui-themes-1.11.1/themes/smoothness/jquery-ui.css" />
<script src="jquery-ui-1.11.1/external/jquery/jquery.js"></script>
<script src="jquery-ui-1.11.1/jquery-ui.js"></script>
	<!--
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
-->
<style type='text/css'>
table{
	border-collapse:collapse;
}
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
    
</style>
<script language='javascript' src='ajax.js'></script>
<script language='javascript'>
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

	else if(element=="summary_analysis"){
	
		elementHTML+="<tr>";
		elementHTML+="<td>Enter Summary and Analysis</td>";
		elementHTML+="<td><textarea name='summary_analysis' id='summary_analysis'>";
		elementHTML+="</textarea>";
		elementHTML+="</td>";
		elementHTML+="</tr>";
	
	
	
	}

	else if(element=="recommended"){
	
		elementHTML+="<tr>";
		elementHTML+="<td>Enter Recommended Measures</td>";
		elementHTML+="<td><textarea name='recommended' id='recommended'>";
		elementHTML+="</textarea>";
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
	elementHTML+="<input type=hidden name='weekly_id' id='weekly_id' value='"+clearance_id+"' />";
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
</script>

<body>
<br>
<br>
<br>
<?php





?>

<?php
if(isset($_GET['ccdr'])){
//$month=$_POST['month'];
//$day=$_POST['day'];
//$year=$_POST['year'];

//$_SESSION['month']=$month;
//$_SESSION['day']=$day;
//$_SESSION['year']=$year;

$availability_date=date("Y-m-d",strtotime($_GET['ccdr']));
$datenow=date("m/d/Y",strtotime($_GET['ccdr']));


if(isset($_GET['ccdr2'])){
	$availability_date2=date("Y-m-d",strtotime($_GET['ccdr2']));
//	$datenow=$datenow.=" - ".date("m/d/Y",strtotime($_POST['search_date']));
	$_SESSION['search_date2']=$_GET['ccdr2'];
	
	
}
else {
	$_SESSION['search_date2']="";
	$availability_date2="";
}



$_SESSION['search_date']=$_GET['ccdr'];

}
else {
if(isset($_SESSION['search_date'])){

$availability_date=date("Y-m-d",strtotime($_SESSION['search_date']));
$datenow=date("m/d/Y",strtotime($_SESSION['search_date']));

if(isset($_SESSION['search_date2'])){
	$availability_date2=date("Y-m-d",strtotime($_SESSION['search_date2']));
//	$datenow=$datenow.=" - ".date("m/d/Y",strtotime($_SESSION['search_date']));
	//$_SESSION['search_date2']=$_POST['search_date'];
	
	
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
}
else {
	$displayDate.=" - ".date("F d, Y",strtotime($availability_date2));
	
	$ccdr_add=" and to_date like '".$availability_date2."%%'";
	
}

//$timetable=date("Y-m-d",strtotime($_POST['search_date']));
echo "<h2>".$displayDate."</h2>";

?>
<?php

	$db=iss_db('transport');
$sql="select * from weekly_report where from_date like '".$availability_date."%%' ".$ccdr_add;
$rs=$db->query($sql);
$nm=$rs->num_rows;

if($nm>0){
	$row=$rs->fetch_assoc();
	$weekly_id=$row['id'];
	
	$summary=$row['summary_analysis'];
	$measures=$row['recommended'];
	
}
else {
	
	$sql2="insert into weekly_report(from_date,to_date) values ('".$availability_date."','".$availability_date2."')";
	$rs2=$db->query($sql2);
	$weekly_id=$db->insert_id;
}


?>
<?php
if(isset($_POST['weekly_id'])){
	$db=iss_db('transport');
	
	
	$update="update weekly_report ";

	$update.=" set ".$_POST['formElement']."='".$_POST[$_POST['formElement']]."' ";
	
	

	$update.=" where id='".$_POST['weekly_id']."'";

	$updateRS=$db->query($update);		
	
}
?>
<a href='#' class="two pull-right"  onclick='window.open("generate_nis2.php?ccdr=<?php echo $availability_date; ?>&ccdr2=<?php echo $availability_date2; ?>");'><b>Generate Weekly Printout</b></a>
<br>
Analysis: <?php echo $summary; ?> <a href='#' onclick="fillEdit('summary_analysis','<?php echo $weekly_id; ?>')">Edit</a>


<br>
Summary and Recommended Measures: <?php echo $measures; ?> <a href='#' onclick="fillEdit('recommended','<?php echo $weekly_id; ?>')">Edit</a>

<!-- header -->
<table width=95% class='train_ava'>
<tr class='rowHeading'>
<th rowspan=2>Incident No.</th>
<th rowspan=2>Time<br> (H)</th>
<th rowspan=2>Incident<br> Duration</th>
<th rowspan=2>Description</th>
<th colspan=2>Action Taken</th>
<th rowspan=2>Level<br> Status</th>
<th rowspan=2>Additional<br> Defects</th>
</tr>
<tr class='rowHeading'>
<th>DOTC</th>
<th>Maintenance Provider</th>
</tr>

<?php
	$db2=iss_db('external');

//$ccdr_date=date("Y-m-d",strtotime($year."-".$month."-".$day));
$ccdr_date=$availability_date;
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
$sql="select * from incident_report inner join incident_description on incident_report.id=incident_id where incident_date like '".$ccdr_date."%%'".$clause;

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
	$hourStamp=date("Hi",strtotime($row['incident_date']));
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
<tr <?php if($i%2>0){ echo "class='rowClass'"; } ?>>
<td align=center><?php echo $row['incident_no']; ?></td>
<td align=center><?php echo $hourStamp; ?></td>
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
</tr>
<?php
}
?>
</table>
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


<br>
<br>

		<div class="modal hide fade" id="addModal">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">×</button>
				<h3>Edit</h3>
			</div>
			<form action='weekly_printout.php?ccdr=<?php echo $availability_date; ?>&ccdr2=<?php echo $availability_date2; ?>' method='post'>

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

		<script src="js/jquery-1.10.2.min.js"></script>
	<script src="js/jquery-migrate-1.2.1.min.js"></script>	
		<script src="js/jquery-ui-1.10.3.custom.min.js"></script>	
		<script src="js/jquery.ui.touch-punch.js"></script>	
		<script src="js/modernizr.js"></script>	
		<script src="js/bootstrap.min.js"></script>	


<script src="js/date.js"></script>