<?php
if(isset($_POST['timetable_code'])){
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
	
	$year=$_POST['year'];
	$month=$_POST['month'];
	$day=$_POST['day'];
	
	$availability_date=date("Y-m-d",strtotime($year."-".$month."-".$day));
	$reset=$_POST['timetable_id'];
	
	
	if($_POST['action']=="edit"){
		$update="update	timetable_day set timetable_code='".$_POST['timetable_code']."' where id='".$reset."'";
		$rs=$db->query($update);

	}
	else if($_POST['action']=="new"){
		$update="insert into timetable_day(timetable_code,train_date) values ('".$_POST['timetable_code']."','".$availability_date."')"; 
		$rs=$db->query($update);
	

	
	}

	echo "<script language='javascript'>";
//	echo "window.opener.location.reload();";
	echo "window.opener.location='train_availability.php';";
//	echo "self.close();";
	echo "</script>";

}

if(isset($_GET['reset'])){
	$reset_value=$_GET['reset'];
	$action="edit";

}
if(isset($_GET['set'])){
	$action="new";

}
?>
<style type='text/css'>
.rowClass {
	background-color:#eaf2d3;
}
.rowHeading {
	background-color:#a7c942;

	color:rgb(0,51,153);
	color:white;	
	
}
.train_ava td{
	border: 1px solid #a7c942;
	color: rgb(0,51,153);
	cellpadding: 5px;

}
 .train_ava th {
	border: 1px solid #a7c942;
	cellpadding: 5px;
	
}
body {
	margin-left:30px;
	margin-right:30px;

}
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

select { border: 1px solid rgb(185, 201, 254); color: rgb(0,51,153); background-color: rgb(185, 201, 254);  }
</style>
<script language='javascript'>
function create_timetable(action){
	if(action=="edit"){
		var timetable_id=document.getElementById('timetable_code').value;
		
		window.open("create_timetable.php?timetable_id="+timetable_id,"_blank");
		
	}
	else {
		window.open("create_timetable.php","_blank");
	}
	self.close();
}

</script>
<form name='timetable_form' id='timetable_form' action='timetable_set.php' method='post'>
<table class='train_ava' >
<tr class='rowHeading'>
<th colspan=2>Set Timetable Code</th>
</tr>
<tr class='rowClass'>
<td>Enter Date</td>
<td>
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

</td>
</tr>
<tr class='rowClass'>
<td>Enter Timetable Code</td>
<td>
<select name='timetable_code' id='timetable_code'>
<?php
//	$db=new mysqli("localhost","root","","transport");
	$db2=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_timetable");

	$sql="select * from timetable";
	$rs=$db2->query($sql);
	$nm=$rs->num_rows;

	for($m=0;$m<$nm;$m++){
		$row=$rs->fetch_assoc();
?>
	<option value='<?php echo $row['id']; ?>'><?php echo $row['code']; ?></option>
<?php	
	}
?>
</select>
</td>
</tr>

</table>
<div align=center>
<input type=submit value='Submit' />
<input type=button value='Edit' onclick="create_timetable('edit')" />
<input type=button value='Create New' onclick="create_timetable('new')" />

</div>
<input type=hidden name='action' id='action' value='<?php echo $action; ?>'/><input type=hidden name='timetable_id' id='timetable_id' value='<?php echo $reset_value; ?>' />
</form>