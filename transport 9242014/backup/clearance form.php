<?php
require("Tmenu.php");
global $varR;
?>
<link href="css/style.min.css" rel="stylesheet" />
	<link href="css/bootstrap.min.css" rel="stylesheet" />
<!--Modify: mjun
 Modified date: Aug 5, 2014
 Modified: Change screen layout
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
	$db=new mysqli("localhost","root","","transport");
	
	
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
			driverHTML+=parts[1].replace("_ENYE_","Ñ");
			driverHTML+="</option>";
		
		}
		
	}
	document.getElementById('received_by').innerHTML=driverHTML;

}

</script>
<body>
<br>
<br>
<br>
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

<input type='text' name='search_date' id='search_date' class='datepicker' value='<?php echo $datenow; ?>' />


<input type=submit value='Retrieve Date' />
</form>
<div class='pull-left'>
<a href='#' class="Llink" onclick='window.open("clearance entry.php");'><b>Add New Entry</b></a>
|
<a href='#' class="two" onclick='window.open("generate_clearance_form.php?clearance_date=<?php echo $clearance_date; ?>");'><b>Generate Printout</b></a>

</div>

<table class='train_ava' width=100%>
<tr class='rowHeading'>
	<th>Clearance No.</th>
	<th>Location</th>
	<th>Activity</th>
	<th>Person</th>
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

	$db=new mysqli("localhost","root","","transport");	
	
	$sql="select * from clearance where date like '".$clearance_date."%%' order by clearance_no";
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
			<tr <?php if($i%2>0){ echo "class='rowClass'"; } ?>>
				<td align=center><?php echo $clearance_no; ?></td>	
				<td><?php echo $location; ?> <a href='#' class="LEdit" onclick="fillEdit('location','<?php echo $clearance_no; ?>')">Edit</a></td>

				<td><?php echo $activity; ?> <a href='#' class="LEdit" onclick="fillEdit('activity','<?php echo $clearance_no; ?>')">Edit</a></td>
				<td><?php echo $person; ?> <a href='#' class="LEdit" onclick="fillEdit('person','<?php echo $clearance_no; ?>')">Edit</a></td>
				<td><?php echo $position." / ".$company; ?> <a href='#' class="LEdit" onclick="fillEdit('position','<?php echo $clearance_no; ?>')">Edit</a></td>
				<td><?php echo $received_by; ?> <a href='#' class="LEdit" onclick="fillEdit('received_by','<?php echo $clearance_no; ?>')">Edit</a></td>
				<td><?php echo $login; ?> <a href='#' class="LEdit" onclick="fillEdit('login','<?php echo $clearance_no; ?>')">Edit</a></td>
				<td><?php echo $logout; ?>  <a href='#' class="LEdit" onclick="fillEdit('logout','<?php echo $clearance_no; ?>')">Edit</a></td>
				<td><?php echo $control_no; ?>  <a href='#' class="LEdit" onclick="fillEdit('control_no','<?php echo $clearance_no; ?>')">Edit</a></td>
				<td><a href='#' class="LDel" onclick="deleteRow('<?php echo $clearance_no; ?>','<?php echo $clearance_date; ?>')">X</a></td>
			</tr>	
			
<?php		
		}	
	}
	

}


?>
<!--
</table>
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
				<button type="button" class="close" data-dismiss="modal">×</button>
				<h3>Edit</h3>
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
