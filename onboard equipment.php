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
<br>
<br>
<br>
<form action='onboard equipment.php' method='post'>
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

$db=new mysqli("localhost","root","","transport");


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
	<td><font color="black"><?php echo $equipment["Equipment_".$row['id']]['name']; ?></font></td>
	<td align=center>
	<font color="black"><?php 
	$provided=$cars-($equipment["Equipment_".$row['id']]['nw_count']*1); 
	
	if($provided<0){ $provided=0; }
	echo $provided;
	?>
	</font></td>
	<td align=center><font color="black"><?php echo $equipment["Equipment_".$row['id']]['nw_count']*1; ?></font></td>
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

			echo "<a href='#' onclick='window.open(\"incident details.php?ir=".$row2['incident_id']."\")'>Car # ".$row2['car_no']." - See IN ".$row2['incident_no']."</a>"; 
			
			}
			else {
				echo ", <a href='#' onclick='window.open(\"incident details.php?ir=".$row2['incident_id']."\")'>Car # ".$row2['car_no']." - See IN ".$row2['incident_no']."</a>"; 
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
<table class='train_ava'>
<tr>
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
<td><font color="black">Number of LRV Used:</font>
</td>
<td align="center"><?php echo $lrv; ?></td>
</tr>
<tr>
<td><font color="black">Finance Train</font></td>
<td align="center"><?php echo $finance; ?></td>
</tr>
<tr>
<td><font color="black">Test Train</font></td>
<td align="center"><?php echo $test; ?></td>
</tr>
<tr>
<td><font color="black">UNIMOG</font></td>
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
</body>
		<script src="js/jquery-1.10.2.min.js"></script>
	<script src="js/jquery-migrate-1.2.1.min.js"></script>	
		<script src="js/jquery-ui-1.10.3.custom.min.js"></script>	
		<script src="js/jquery.ui.touch-punch.js"></script>	
		<script src="js/modernizr.js"></script>	


<script src="js/date.js"></script>	

