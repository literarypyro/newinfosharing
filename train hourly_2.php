<?php
session_start();
?>
<?php
function is_decimal( $val )
{
    return is_numeric( $val ) && floor( $val ) != $val;
}
?>
<?php
if(isset($_POST['reserve'])){
	if($_SESSION['month']==""){
	}
	else {
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
	$db2=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_timetable");
		$month=$_SESSION['month'];
		$day=$_SESSION['day'];
		$year=$_SESSION['year'];
		
		$train_date=date("Y-m-d",strtotime($year."-".$month."-".$day));

		$sql="select * from timetable_day where train_date like '".$train_date."%%'";
		$rs=$db->query($sql);
		
		$nm=$rs->num_rows;

		if($nm>0){
			$row=$rs->fetch_assoc();
			$timetable_code=$row['timetable_code'];
		
		}
		
		
		$reserve_hour=$_POST['reserve_hour'];
		$reserve=$_POST['reserve'];
		
		$nm=0;
		
		$table="";
		
		$sql="select * from timetable_provided where timetable_date='".$train_date."' and timetable_id='".$timetable_code."' and hour_id='".$reserve_hour."'";
		
		$rs=$db2->query($sql);
		$nm=$rs->num_rows;	
		
		if($nm>0){
		
			$row=$rs->fetch_assoc();
			$update="update timetable_provided set reserve_provided='".$reserve."' where id='".$row['id']."'";

			$updateRS=$db2->query($update);
			
		}
		else {
			$update="insert into timetable_provided(timetable_date,timetable_id,hour_id,reserve_provided) values ('".$train_date."','".$timetable_code."','".$reserve_hour."','".$reserve."')";
			
			$updateRS=$db2->query($update);
		
		
		}
	}
}

if(isset($_POST['remarks'])){
	if($_SESSION['month']==""){
	}
	else {
		$month=$_SESSION['month'];
		$day=$_SESSION['day'];
		$year=$_SESSION['year'];
		
		$train_date=date("Y-m-d",strtotime($year."-".$month."-".$day));
		
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
	$db2=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_timetable");
		
		
		$remarks_hour=$_POST['remarks_hour'];
		$remarks=$_POST['remarks'];

		$sql="select * from timetable_day where train_date like '".$train_date."%%'";
		$rs=$db->query($sql);
		
		$nm=$rs->num_rows;

		if($nm>0){
			$row=$rs->fetch_assoc();
			$timetable_code=$row['timetable_code'];
		
		}
		
		$sql="select * from timetable_remarks where timetable_date='".$train_date."' and hour_id='".$remarks_hour."' and timetable_id='".$timetable_code."'";
		$rs=$db->query($sql);
		$nm=$rs->num_rows;		
		
		if($nm>0){
		
			$row=$rs->fetch_assoc();
			$update="update timetable_remarks set timetable_remarks=\"".$remarks."\" where id='".$row['id']."'";
			$updateRS=$db2->query($update);
		}
		else {
			$update="insert into timetable_remarks(timetable_date,timetable_id,hour_id,timetable_remarks) values ('".$train_date."','".$timetable_code."','".$remarks_hour."',\"".$remarks."\")";
			$updateRS=$db2->query($update);
		}		
	}
}	

?>
<?php
if(isset($_POST['cars_provided'])){
	if($_SESSION['month']==""){
	}
	else {
		$month=$_SESSION['month'];
		$day=$_SESSION['day'];
		$year=$_SESSION['year'];
		
		$train_date=date("Y-m-d",strtotime($year."-".$month."-".$day));
		
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
	$db2=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_timetable");
		
		$provided_hour=$_POST['provided_hour'];
		$cars_provided=$_POST['cars_provided'];
		$nm=0;

		$sql="select * from timetable_day where train_date like '".$train_date."%%'";
		$rs=$db->query($sql);
		
		$nm=$rs->num_rows;

		if($nm>0){
			$row=$rs->fetch_assoc();
			$timetable_code=$row['timetable_code'];
		
		}


		
		$table="";

		$sql="select * from timetable_provided where timetable_date='".$train_date."' and timetable_id='".$timetable_code."' and hour_id='".$provided_hour."'";
		$rs=$db2->query($sql);
		$nm=$rs->num_rows;	

		if($nm>0){
		
			$row=$rs->fetch_assoc();
			$update="update timetable_provided set cars_provided='".$cars_provided."' where id='".$row['id']."'";
			$updateRS=$db2->query($update);
			
		}
		else {
			$update="insert into timetable_provided(timetable_date,timetable_id,hour_id,cars_provided) values ('".$train_date."','".$timetable_code."','".$provided_hour."','".$cars_provided."')";
			
			$updateRS=$db2->query($update);
			
		
		}
	}
}
?>

<style type='text/css'>
.rowClass {
	background-color:#eaf2d3;
}
.rowHeading {
	background-color:#a7c942;
	color:rgb(0,51,153);
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
<body>
<?php
require("monitoring menu.php");
?>
<br>
<br>
<form action='train hourly_2.php' method='post'>
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
<input type=submit value='Access Monitoring' />
</form>
<?php
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
$timetable_code="";
if((isset($_POST['month']))||(isset($_SESSION['month']))){
	if(isset($_POST['month'])){
		$month=$_POST['month'];
		$day=$_POST['day'];
		$year=$_POST['year'];
		
		$_SESSION['month']=$_POST['month'];
		$_SESSION['day']=$_POST['day'];
		$_SESSION['year']=$_POST['year'];
	
	}
	else if(isset($_SESSION['month'])){
		$month=$_SESSION['month'];
		$day=$_SESSION['day'];
		$year=$_SESSION['year'];
	
	
	}

	$timetable=date("Y-m-d",strtotime($year."-".$month."-".$day));
	
	$sql="select * from timetable_day where train_date like '".$timetable."%%'";
	$rs=$db->query($sql);
	
	$nm=$rs->num_rows;

	if($nm>0){
		$row=$rs->fetch_assoc();
		$timetable_code=$row['timetable_code'];
	
	}
	echo "<h2>".date("F d, Y",strtotime($timetable))."</h2><br>";
}
?>
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
	$db2=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_timetable");


if($timetable_code==""){
}
else {
	$sql="select * from timetable_hour where timetable_id='".$timetable_code."' order by time_from";
	
	$rs=$db2->query($sql);
	$nm=$rs->num_rows;
		
	if($nm>0){
		for($i=0;$i<$nm;$i++){
			$row=$rs->fetch_assoc();		
			
			$headway=$row['headway'];
			$time_label=$row['time_label'];
			
			$from=date("H:i",strtotime($row['time_from']));
			$to=date("H:i",strtotime($row['time_to']));
			
			$sql2="select * from timetable_required where timetable_id='".$timetable_code."' and hour_id='".$row['id']."'";
			$rs2=$db2->query($sql2);
			$nm2=$rs2->num_rows;

			$cars_required=0;
			$reserve_required=0;			
			if($nm2>0){
				$row2=$rs2->fetch_assoc();	
				$cars_required=$row2['cars_required'];
				$reserve_required=$row2['reserve_required'];	
			}

			
			$sql3="select * from timetable_provided where timetable_id='".$timetable_code."' and hour_id='".$row['id']."' and timetable_date like '".$timetable."%%'";
			$rs3=$db2->query($sql3);
			$nm3=$rs3->num_rows;

			$cars_provided=0;
			$reserve_provided=0;			
			
			if($nm3>0){
				$row3=$rs3->fetch_assoc();
				$cars_provided=$row3['cars_provided'];
				$reserve_provided=$row3['reserve_provided'];
			}
			
			$sql4="select * from timetable_remarks where timetable_id='".$timetable_code."' and hour_id='".$row['id']."' and timetable_date like '".$timetable."%%'";
			$rs4=$db2->query($sql4);
			$nm4=$rs4->num_rows;

			$timetable_remarks="";
			
			if($nm4>0){
				$row4=$rs4->fetch_assoc();
				$timetable_remarks=$row4['timetable_remarks'];
			}

			$timestamp="between '".date("Y-m-d H:i:s",strtotime($timetable." ".$from.":00"))."' and '".date("Y-m-d H:i:s",strtotime($timetable." ".$to.":00"))."'";		
			
			$car_sql3="select sum(cancel) as cancel from incident_report where incident_date ".$timestamp." and level in ('3','4')";
			$car_rs3=$db->query($car_sql3);
			$car_nm3=$car_rs3->num_rows;
			if($car_nm3>0){
				$car_row3=$car_rs3->fetch_assoc();
				$loop_cancelled=$car_row3['cancel']*1;
			}

			$car_sql2="select * from train_availability where date ".$timestamp." and type='revenue' and status='cancelled'";
			$car_rs2=$db->query($car_sql2);
			$car_nm2=$car_rs2->num_rows;
			
			if(is_decimal($loop_cancelled)){
				$loop_cancelled=floor($loop_cancelled);
				if($loop_cancelled==0){ $loop_cancelled="1/2"; }
				else { $loop_cancelled.=" 1/2"; }
			}		
			$cars_cancelled=0;	
		
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
			<tr>	
				<th><?php echo $time_label; ?></th>
				<th><?php echo $headway; ?></th>
				<th><?php echo $cars_required; ?></th>
				<th><?php echo $cars_provided; ?></th>
				<th><?php echo $reserve_required; ?></th>
				<th><?php echo $reserve_provided; ?></th>
				<th><?php echo $cars_cancelled; ?></th>
				<th><?php echo $loop_cancelled; ?></th>
				<th>
				<?php
					$incident_sql="select * from incident_report inner join incident_description on incident_report.id=incident_id where incident_date ".$timestamp." and ((incident_type in ('rolling') and level in ('3','4')) or (incident_type in ('gradual','c_loops','r_trains','unload','nload')))";
					$incident_rs=$db->query($incident_sql);
					
					$incident_nm=$incident_rs->num_rows;
					if($incident_nm>0){
						for($m=0;$m<$incident_nm;$m++){
							$incident_row=$incident_rs->fetch_assoc();
				
							if($m==0){
								echo "<a href='#' onclick='window.open(\"edit_ccdr.php?ir=".$incident_row['incident_id']."\")'>IN ".$incident_row['incident_no'];
								if(($incident_row['incident_type']=="rolling")||($incident_row['incident_type']=="unload")||($incident_row['incident_type']=="nload")){
									echo "(".$incident_row['index_no'].")";

								}
								echo "</a>";
							}
							else {
								echo ", <a href='#' onclick='window.open(\"edit_ccdr.php?ir=".$incident_row['incident_id']."\")'>IN ".$incident_row['incident_no'];
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
				</th>
				<th><?php echo $timetable_remarks; ?></th>
			</tr>
<?php	
		}
	}
}
?>
</table>
<a href='#' onclick='window.open("generate_star2.php?star_id=<?php echo $timetable_code; ?>&timetable=<?php echo $timetable; ?>&timetable_id=<?php echo $timetable_code; ?>");'>Generate Printout</a>
<br>

<a href='#' onclick='window.open("generate_ccip.php?ccip_id=<?php echo $timetable_code; ?>&ccip_date=<?php echo $timetable; ?>");'>Generate Insertion Form</a>
<br>
<br>
<?php
if($timetable_code==""){
}
else {
	$db2=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_timetable");
?>

<form action='train hourly_2.php' method='post'>
<table id='add_form' name='add_form'>
<tr>
<th colspan=2>
Enter Cars Provided
</th>
</tr>
<tr>
<td>Enter Cars Provided</td>
<td><input type='text' name='cars_provided' id='cars_provided' /></td>
</tr>
<tr>
<td>Enter Hour</td>
<td>
<select name='provided_hour' id='provided_hour'>
<?php
	$sql="select * from timetable_hour where timetable_id='".$timetable_code."' order by time_from"; 
	$rs=$db2->query($sql);
	$nm=$rs->num_rows;

	for($i=0;$i<$nm;$i++){
		$row=$rs->fetch_assoc();
?>
	<option value='<?php echo $row['id']; ?>'><?php echo $row['time_label']; ?></option>
<?php
	}
?>
</select>
</td>
</tr>
<tr>
<td colspan=2 align=center>
<input type=submit value='Submit' />
</td>
</tr>
</table>
</form>



<br>


<form action='train hourly_2.php' method='post'>
<table id='add_form' name='add_form'>
<tr>
<th colspan=2>
Reserve Provided
</th>
</tr>
<tr>
<td>Enter Reserve</td>
<td><input type='text' name='reserve' id='reserve' /></td>
</tr>
<tr>
<td>Enter Hour</td>
<td>
<select name='reserve_hour' id='reserve_hour'>
<?php
	$sql="select * from timetable_hour where timetable_id='".$timetable_code."' order by time_from"; 
	$rs=$db2->query($sql);
	$nm=$rs->num_rows;

	for($i=0;$i<$nm;$i++){
		$row=$rs->fetch_assoc();
?>
	<option value='<?php echo $row['id']; ?>'><?php echo $row['time_label']; ?></option>
<?php
	}
?>
</select>
</td>
</tr>

<tr>
<td colspan=2 align=center>
<input type=submit value='Submit' />
</td>
</tr>
</table>
</form>
<br>
<form action='train hourly_2.php' method='post'>
<table id='add_form' name='add_form'>
<tr>
<th colspan=2>
Train Hourly Remarks
</th>
</tr>
<tr>
<td>Enter Remarks</td>
<td><textarea name='remarks' cols=50></textarea></td>
</tr>
<tr>
<td>Enter Hour</td>
<td>
<select name='remarks_hour' id='remarks_hour'>
<?php
	$sql="select * from timetable_hour where timetable_id='".$timetable_code."' order by time_from"; 
	$rs=$db2->query($sql);
	$nm=$rs->num_rows;

	for($i=0;$i<$nm;$i++){
		$row=$rs->fetch_assoc();
?>
	<option value='<?php echo $row['id']; ?>'><?php echo $row['time_label']; ?></option>
<?php
	}
?>
</select>
</td>
</tr>
<tr>
<td colspan=2 align=center>
<input type=submit value='Submit' />
</td>
</tr>
</table>
</form>
<?php
}
?>
</body>