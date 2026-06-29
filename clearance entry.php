<?php
ini_set("date.timezone","Asia/Kuala_Lumpur");
?>
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
if(isset($_POST['year'])){
	$activity=$_POST['activity'];
	$person=$_POST['person'];
	$position=$_POST['position'];
	$company=$_POST['company'];
	$control_no=$_POST['control_no'];
	$location=$_POST['location'];
	$received_by=$_POST['received_by'];
	
	$year=$_POST['year'];
	$month=$_POST['month'];
	$day=$_POST['day'];
	
	$timeStamp=setTime($_POST['hour'],$_POST['minute'],$_POST['amorpm']);
	$logout_time="";

	$login_time="";
	
	$clearance_date=date("Y-m-d",strtotime($year."-".$month."-".$day));


	if($_POST['login_amorpm']==""){
	
	}
	else {
		$loginStamp=setTime($_POST['login_hour'],$_POST['login_minute'],$_POST['login_amorpm']);
	
		$login_time=date("Y-m-d H:i",strtotime($clearance_date." ".$loginStamp));	

	}
	
	
	if($_POST['logout_amorpm']==""){
	
	
	}
	else {
		$logoutStamp=setTime($_POST['logout_hour'],$_POST['logout_minute'],$_POST['logout_amorpm']);
		$logout_time=date("Y-m-d H:i",strtotime($clearance_date." ".$logoutStamp));	

	}

	$clearance_time=date("Y-m-d H:i",strtotime($clearance_date." ".$timeStamp));	
	
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
	$sql="insert into clearance(date,location,activity,person,position,company,login,logout,control_no,received_by)";
	$sql.=" values ";
	
	$sql.="('".$clearance_date."','".$location."',\"".$activity."\",\"".$person."\",";
	$sql.="\"".$position."\",\"".$company."\",'".$login_time."','".$logout_time."','".$control_no."','".$received_by."')";
	$rs=$db->query($sql);
	
	echo "<script language='javascript'>";
	echo "window.opener.location='clearance form.php';";
	echo "</script>";
	
	echo "Data added.";
}	
?>	
<script language='javascript' src='ajax.js'></script>
<style type='text/css'>
body {
	background-color: #gray;
	color:  rgb(0,51,153);
	margin-left:30px;
	margin-right:30px;	
	
	
<!--	margin: 0;
	padding: 0;
-->	
}
.content {
	width: 80%;
	margin: 20px auto 40px auto;
	background-color: #ffa;
	color: #333;
	border: 2px solid #1a3c2d;
	padding: .75em;
	spacing: .5px;
}

table {
	//margin: .75em auto auto auto;
	color: #000;
	border: 1px solid rgb(185, 201, 254);
}

th {
	background-color: #33aa55;
	color: #fff;
	border: 1px solid rgb(185, 201, 254);
	


}

tr td:first-child {
	background-color: rgb(185, 201, 254);
	color: rgb(0,51,153);

}
tr td:last-child {
	background-color: #dfe7f2;
	color: #fff;

}

td {
	border: 1px solid rgb(185, 201, 254);

}

input[type="text"]{ 
	height:25px; 
	font-weight:bold; 
	font-size:15px; 
	font-family:courier; 
	border: 1px solid #dfe7f2;
	background-color: #dfe7f2;
	color: rgb(0,51,153);
	border-radius: 3px;
}
textarea{ 
	border: 1px solid #dfe7f2;
	background-color: #dfe7f2;
	color: rgb(0,51,153);
	border-radius: 3px;
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
ul.nav li {
	list-style-type:none;
	display: inline;
	padding-left: 0;
	margin-left: 0;

	
	padding: 5px;
	spacing: 1.75px;
	color: black;
	
	
	min-width: 8em;
	margin-right: 0.5em;
	
	-webkit-border-radius: 3px;
	-moz-border-radius: 3px;
	border-radius: 3px;
	-webkit-box-shadow: 3px 3px 3px 3px rgba(43, 43, 77, 0.5);
	-moz-box-shadow: 3px 3px 3px 3px rgba(43, 43, 77, 0.5);
	box-shadow: 3px 3px 3px 3px rgba(43, 43, 77, 0.5);
}
select { border: 1px solid #dfe7f2; color: rgb(0,51,153); background-color:  #dfe7f2;  }

ul.nav li a{
	text-decoration: none;

}


.removal {

	color: rgb(0,51,153);
}

.removalnone {
	color:rgb(223,231,242);

}




</style>
<form action='clearance entry.php' method='post'>
<table class='ir'>
<tr>
<th colspan=2>Clearance Entry</th>
</tr>
<tr>
<td>Date:</td><td>
<select name='month'>
<?php
$mm=date("m");
$yy=date("Y");
$dd=date("d");

$hh=date("h");

$min=date("i");
$aa=date("a");

for($i=1;$i<13;$i++){
?>
	<option value='<?php echo $i; ?>' 
	<?php
	if($i==$mm){
		echo "selected";
	}
	?>
	>
	<?php
	echo date("F",strtotime(date("Y")."-".$i."-01"));
	?>
	</option>
<?php
}
?>
</select>
<select name='day'>
<?php
for($i=1;$i<=31;$i++){
?>
	<option value='<?php echo $i; ?>' 
	<?php
	if($i==$dd){
		echo "selected";
	}
	?>		
	>
	<?php
	
	echo $i;
	?>
	</option>
<?php
}
?>
</select>
<select name='year'>
<?php
$dateRecent=date("Y")*1+16;
for($i=1999;$i<=$dateRecent;$i++){
?>
	<option value='<?php echo $i; ?>' 
	<?php
	if($i==$yy){
		echo "selected";
	}
	?>		
	>
	<?php
	echo $i;
	?>
	</option>
<?php
}
?>
</select>
</td></tr>
<tr>
<td valign=top>Location:</td><td> <textarea rows=5 cols=50 name='location'></textarea></td></tr>
<tr>
<td valign=top>Activity:</td><td> <textarea rows=5 cols=50 name='activity'></textarea></td></tr>
<tr>
<td>Person</td>
<td><input type=text name='person' /></td>
</tr>
<tr>
<td>Position</td>
<td><input type=text name='position' /></td>
</tr>
<tr>
<td>Company</td>
<td><input type=text name='company' /></td>
</tr>
<tr>
<td>Received By:</td>
<td>
<select name='received_by'>
<?php 
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
	
	$sql="select * from train_driver where position in ('STDO','CCRE')";
$rs=$db->query($sql);
$nm=$rs->num_rows;

for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
	?>
	<option value='<?php echo $row['id']; ?>'>	
	<?php echo $row['lastName'].", ".$row['firstName'].", ".$row['position']; ?>
	</option>
<?php
}
?>
</select>
</td>
</tr>

<tr><td>Log-in </td><td>
<select name='login_hour'>
	<option></option>
<?php
for($i=1;$i<=12;$i++){
?>
	<option value='<?php echo $i; ?>' 
	<?php
	if($i*1==$hh*1){
	//	echo "selected";
	}
	?>		
	>
	<?php
	echo $i;
	?>
	</option>
<?php
}
?>
</select>
<select name='login_minute'>
	<option></option>
<?php
for($i=0;$i<=59;$i++){
?>
	<option value='<?php echo $i; ?>' 
	<?php
	if($i*1==$min*1){
		//echo "selected";
	}
	?>		
	>
	<?php
	if($i<10){
	echo "0".$i;
	}
	else {
	echo $i;
	}
	?>	
	</option>
<?php
}
?>
</select>
<select name='login_amorpm'>
	<option></option>
<option value='am' <?php //if($aa=="am"){ echo "selected"; } ?>>AM</option>
<option value='pm' <?php //if($aa=="pm"){ echo "selected"; } ?>>PM</option>
</select>
</td>
</tr>
<tr><td>Log-out </td><td>
<select name='logout_hour'>
	<option></option>
<?php
for($i=1;$i<=12;$i++){
?>
	<option value='<?php echo $i; ?>' 
	<?php
	if($i*1==$hh*1){
		//echo "selected";
	}
	?>		
	>
	<?php
	echo $i;
	?>
	</option>
<?php
}
?>
</select>
<select name='logout_minute'>
	<option></option>
<?php
for($i=0;$i<=59;$i++){
?>
	<option value='<?php echo $i; ?>' 
	<?php
	if($i*1==$min*1){
		//echo "selected";
	}
	?>		
	>
	<?php
	if($i<10){
	echo "0".$i;
	}
	else {
	echo $i;
	}
	?>	
	</option>
<?php
}
?>
</select>
<select name='logout_amorpm'>
	<option></option>
<option value='am' <?php 
if($aa=="am")
{ 
//echo "selected"; 
} ?>>AM</option>
<option value='pm' <?php 
if($aa=="pm"){ 
//echo "selected"; 
} ?>>PM</option>
</select>
</td>
</tr>

<tr>
<td>Work Permit/Control No.</td>
<td><input type=text name='control_no' /></td>
</tr>

<tr>
<th colspan=2><input type=submit value='Submit' /></th>
</tr>
</table>
</form>
