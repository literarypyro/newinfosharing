<?php
$db2=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_timetable");

if(isset($_POST['timetable_code'])){
	if($_POST['action']=="new"){
		$timetable_code=$_POST['timetable_code'];
		$planned_loops=$_POST['planned_loops'];
		$filename_report=$_POST['filename_report'];

		$update="insert into timetable(code,planned_loops,report_file) values ('".$timetable_code."','".$planned_loops."','".$filename_report."')";
		$updateRS=$db2->query($update);
		
		$timetable_id=$db2->insert_id;
	}
	else if($_POST['action']=="edit"){
		$timetable_id=$_POST['timetable_id'];
	
		$timetable_code=$_POST['timetable_code'];
		$planned_loops=$_POST['planned_loops'];
		$filename_report=$_POST['filename_report'];
		
		
		$update="update timetable set code='".$timetable_code."',planned_loops='".$planned_loops."',report_file='".$filename_report."' where id='".$timetable_id."'";
		$updateRS=$db2->query($update);
		
		
	}
	header("Location: new_timetable.php?timetable_id=".$timetable_id);

}
?>
<?php
if(isset($_GET['timetable_id'])){
	$sql="select * from timetable where id='".$_GET['timetable_id']."'";
	$rs=$db2->query($sql);
	
	$row=$rs->fetch_assoc();
	$timetable_code=$row['code'];
	$planned_loops=$row['planned_loops'];
	$filename_report=$row['report_file'];
	
	
	
}
?>
<style type="text/css">

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
</style>


<form action='create_timetable.php' method='post'>
<table class='train_ava' >
<tr>
	<td  style='background-color:#eaf2d3' >Timetable Code (e.g. WD30 v.1)
	</td>
	<td><input type=text name='timetable_code' value='<?php echo $timetable_code; ?>' />
	</td>
</tr>
<tr>
	<td  style='background-color:#eaf2d3'>Planned Loops
	</td>
	<td><input type=text name='planned_loops' value='<?php echo $planned_loops; ?>' />
	</td>
</tr>
<tr>
	<td  style='background-color:#eaf2d3'>Filename for Report (e.g. WD30v1)
	</td>
	<td><input type=text name='filename_report' value='<?php echo $filename_report; ?>' />
	</td>
</tr>
<tr>
<td colspan=2 align=center>
<?php
if(isset($_GET['timetable_id'])){ $action="edit"; } else { $action="new"; }
?>
<input type=hidden name='action' value='<?php echo $action; ?>'/>
<input type=hidden name='timetable_id' value='<?php echo $_GET['timetable_id']; ?>' />
<input type=submit value='Submit' />
</td>
</tr>
</table>
</form>