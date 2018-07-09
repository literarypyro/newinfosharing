<?php
session_start();
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
<script language='javascript' src='ajax.js'></script>
<script language='javascript'>
function deleteIncident(index){
	var check=confirm("Remove Record?");
	if(check){
	makeajax("processing.php?removeIncident="+index,"reloadPage");	
	}
}

function reloadPage(ajaxHTML){
	self.location="incident summary.php";
	//self.location.reload();

}

</script>
<body>
<?php
require("monitoring menu.php");

?>
<br>
<br>
<br>
<?php
$mm=date("m");
$yy=date("Y");
$dd=date("d");

$hh=date("h");

$min=date("i");
$aa=date("a");


if(isset($_SESSION['month'])){
$month=$_SESSION['month'];
$day=$_SESSION['day'];
$year=$_SESSION['year'];

}
?>
<form action='incident summary.php' method='post'>
<select name='month'>

<?php


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
<br>
<form action='incident summary.php' method='post'>
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

<br>
<?php
if(isset($_POST['month'])){
$month=$_POST['month'];
$day=$_POST['day'];
$year=$_POST['year'];

$_SESSION['month']=$month;
$_SESSION['day']=$day;
$_SESSION['year']=$year;


}
else {
if(isset($_SESSION['month'])){
$month=$_SESSION['month'];
$day=$_SESSION['day'];
$year=$_SESSION['year'];

}
else {
$month=date("m");
$day=date("d");
$year=date("Y");
}

}
	$timetable=date("Y-m-d",strtotime($year."-".$month."-".$day));

echo "<h2>".date("F d, Y",strtotime($timetable))."</h2><br>";

?>
<table width=100% class='train_ava'>
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

$ccdr_date=date("Y-m-d",strtotime($year."-".$month."-".$day));
$db=new mysqli("localhost","root","","transport");

$clause=" order by incident_report.id";

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
			
		}
	$incident_type=$row['incident_type'];
		
	$description="";	
	$hourStamp=date("Hi",strtotime($row['incident_date']));
	$location=$row['location'];
	$reported_by=$row['reported_by'];

		if($incident_type=="rolling"){
			if($carClause==""){ } else { $carClause=" Car(s) ".$carClause.", "; }
			
			$direction=$row['direction'];
			if(($direction=="S")||($direction=="SB")||($direction=="NB")) { $location="Stn. ".$location; }
			else if($direction=="D"){ $direction="Depot"; }
			else if($direction=="ML"){ $direction="Mainline"; }
			$description="Index #".$row['index_no'].",".$carClause.$location."  ".$direction.", ".$row['description'].", Reported By ".$reported_by.", ";
		
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
<td align=center><a href='#' onclick='window.open("edit_ccdr.php?ir=<?php echo $row['incident_id']; ?>")'><?php echo $row['incident_no']; ?></a></td>
<td align=center><?php echo $hourStamp; ?></td>
<td><?php echo $row['duration']; ?></td>
<td><?php echo $description; ?></td>
<td><?php echo $row['action_dotc']; ?></td>
<td><?php echo $row['action_maintenance']; ?></td>
<td align=center><?php echo $row['level']; ?></td>
<td>
<?php
$db2=new mysqli("localhost","root","","external");
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
<td valign=center align=center><a href='#' onclick='deleteIncident("<?php echo $row['incident_id']; ?>")'>X</a></td>
</tr>
<?php
}
?>
</table>
<?php

?>
<br>
<a href='#' onclick='window.open("generate_ccdr.php?ccdr=<?php echo $ccdr_date; ?>");'>Generate Printout</a>



</body>