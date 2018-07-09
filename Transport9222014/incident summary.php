<!--- Modified by Jun
//--- Date: 7/29/2014
//--- Modify: modify screen layout
//--- Marker: @mjun
//--------------------------------------------------->
<?php
require("Tmenu.php");
?>
<!--
	<link href="css/style.min.css" rel="stylesheet" /> -->
	
<link rel="stylesheet" type="text/css" href="../../information_sharing/transport/jquery-easyui-1.4/themes/gray/easyui.css" />
<link rel="stylesheet" type="text/css" href="../../information_sharing/transport/jquery-easyui-1.4/themes/icon.css" />
<link rel="stylesheet" type="text/css" href="../../information_sharing/transport/jquery-easyui-1.4/demo/demo.css" />
<script type="text/javascript" src="../../information_sharing/transport/jquery-easyui-1.4/jquery.min.js"></script>
<script type="text/javascript" src="../../information_sharing/transport/jquery-easyui-1.4/jquery.easyui.min.js"></script>	
	
	
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

$datenow=date("m/d/Y");
$availability_date=date("Y-m-d");

if(isset($_SESSION['search_date'])){
//$month=$_SESSION['month'];
//$day=$_SESSION['day'];
//$year=$_SESSION['year'];

$availability_date=$_SESSION['search_date'];

$datenow=date("m/d/Y",strtotime($availability_date));
}
?>
<form  action='incident summary.php' method='post'>
<!--
<input type='text' name='search_date' id='search_date' class='datepicker' value='<?php echo $datenow; ?>'/> -->

<input name='search_date' id='search_date' type="text" class="easyui-datebox" value='<?php echo $datenow; ?>'/> 

<input type=submit value='Access Monitoring' />
</form>

<!-- Sort form -->

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

<?php
if(isset($_POST['search_date'])){
//$month=$_POST['month'];
//$day=$_POST['day'];
//$year=$_POST['year'];

//$_SESSION['month']=$month;
//$_SESSION['day']=$day;
//$_SESSION['year']=$year;

$availability_date=date("Y-m-d",strtotime($_POST['search_date']));
$datenow=date("m/d/Y",strtotime($_POST['search_date']));

$_SESSION['search_date']=$_POST['search_date'];

}
else {
if(isset($_SESSION['search_date'])){

$availability_date=date("Y-m-d",strtotime($_SESSION['search_date']));
$datenow=date("m/d/Y",strtotime($_SESSION['search_date']));


}
else {

$availability_date=date("Y-m-d");
$datenow=date("m/d/Y");

}

}
//$timetable=date("Y-m-d",strtotime($_POST['search_date']));

echo "<h2>".date("F d, Y",strtotime($availability_date))."</h2>";
?>
<a href='#' class="two pull-right"  onclick='window.open("generate_ccdr.php?ccdr=<?php echo $availability_date; ?>");'><b>Generate Printout</b></a>

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
$db2=new mysqli("localhost","root","","external");

//$ccdr_date=date("Y-m-d",strtotime($year."-".$month."-".$day));
$ccdr_date=$availability_date;
$db=new mysqli("localhost","root","","transport");

$clause=" order by substring(incident_no,1,position('' in incident_no))*1 ";

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
<td align=center><a href='#' class="two2" onclick='window.open("edit_ccdr.php?ir=<?php echo $row['incident_id']; ?>")'><?php echo $row['incident_no']; ?></a></td>
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
<td valign=center align=center><a href='#' class="LDel" onclick='deleteIncident("<?php echo $row['incident_id']; ?>")'>X</a></td>
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
