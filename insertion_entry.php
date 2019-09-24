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
	$planned_1=$_POST['planned_1'];
	$actual_1=$_POST['actual_1'];

	$planned_2=$_POST['planned_2'];
	$actual_2=$_POST['actual_2'];

	$planned_3=$_POST['planned_3'];
	$actual_3=$_POST['actual_3'];
	
	
	
	$remarks=$_POST['remarks'];
	$actual_completion=$_POST['actual_completion'];
	
	
	$tar_time=$_POST['tar_time'];
	$index_no=$_POST['index_no'];
	
	$year=$_POST['year'];
	$month=$_POST['month'];
	$day=$_POST['day'];
	
	
	$insertion_date=date("Y-m-d",strtotime($year."-".$month."-".$day));


	
	$db=new mysqli("localhost","root","","transport");
	$sql="insert into depot_insertion(index_no,insertion_date,tar_time,actual_completion,remarks)";
	$sql.=" values ";
	
	$sql.="('".$index_no."','".$insertion_date."','".$tar_time."','".$actual_completion."',\"".$remarks."\")";
	$rs=$db->query($sql);
	
	$insertion_id=$db->insert_id;
	
	$sql="insert into stabling_departure(depot_insertion_id,planned,actual,loop_no)";
	$sql.=" values ";
	
	$sql.="('".$insertion_id."','".$planned_1."','".$actual_1."','1'),";
	$sql.="('".$insertion_id."','".$planned_2."','".$actual_2."','2'),";
	$sql.="('".$insertion_id."','".$planned_3."','".$actual_3."','3')";
	$rs=$db->query($sql);
	
	
	echo "<script language='javascript'>";
	echo "window.opener.location='depot_insertion.php';";
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
		
		
<!--
	margin: 0;
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
	color: rgb(223,231,242);
}




</style>
<form action='insertion_entry.php' method='post'>
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
<td>Index No.</td>
<td><input type=text name='index_no' /></td>
</tr>
<tr>
<td>Train Availability Time at Stabling</td>
<td><input type=text name='tar_time' /></td>
</tr>
<tr>
<td>Actual time of Completion</td>
<td><input type=text name='actual_completion' /></td>
</tr>

<tr>
<th colspan=2>Departure at Stabling Area</tr>
</tr>
<tr>
<th colspan=2>1st Loop</tr>
</tr>
<tr>
<td>Planned</td>
<td><input type=text name='planned_1' /></td>
</tr>
<tr>
<td>Actual</td>
<td><input type=text name='actual_1' /></td>
</tr>

<tr>
<th colspan=2>2nd Loop</tr>
</tr>
<tr>
<td>Planned</td>
<td><input type=text name='planned_2' /></td>
</tr>
<tr>
<td>Actual</td>
<td><input type=text name='actual_2' /></td>
</tr>

<tr>
<th colspan=2>3rd Loop</tr>
</tr>
<tr>
<td>Planned</td>
<td><input type=text name='planned_3' /></td>
</tr>
<tr>
<td>Actual</td>
<td><input type=text name='actual_3' /></td>
</tr>
<tr>
<td>Remarks</td>
<td><input type=text name='remarks' /></td>
</tr>


<tr>
<th colspan=2><input type=submit value='Submit' /></th>
</tr>
</table>
</form>
