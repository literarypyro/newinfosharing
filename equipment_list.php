<?php 
require("Tmenu.php");
ini_set("date.timezone","Asia/Kuala_Lumpur");
?>
<?php
$db=new mysqli("localhost","root","","user_transport");
?>
<link href="css/modal_only.css" rel="stylesheet" />
<!-- <link href="css/style.min.css" rel="stylesheet" /> -->

<link rel="stylesheet" href="jquery-ui-themes-1.11.1/themes/smoothness/jquery-ui.css" />
<script src="jquery-ui-1.11.1/external/jquery/jquery.js"></script>
<script src="jquery-ui-1.11.1/jquery-ui.js"></script>
	
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
<script language='javascript'>
function addSignatory(){
	$('#addModal').modal('show');
}


</script>

<script language='javascript' src='ajax.js'></script>
<?php
?>
<?php


$db=new mysqli("localhost","root","","transport");
$sql="select equipment.equipment_name as equipt, equipment_type.equipment_name as type from equipment inner join equipment_type on type=incident_code order by equipment.equipment_name";
$rs=$db->query($sql);
$nm=$rs->num_rows;


?>
<br>
<br>
<br>
<br>
<br>
<a href='#' onclick='addSignatory()'>Add Equipment</a>
<table width='80%' class='train_ava'>
	<tr  class='rowHeading'>
		<td>Equipment Name</td>
		<td>Equipment Type</td>
	</tr>
<?php
for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
?>
	<tr <?php if($i%2>0){ echo "class='rowClass'"; } ?>>	
		<td><?php echo $row['equipt']; ?></td>
		<td><?php echo $row['type']; ?></td>
	</tr>
<?php

}

?>
</table>


		<div class="modal hide fade" id="addModal">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">×</button>
				<h3>Edit</h3>
				</div>
				<form  action='equipment_list.php' method='post'>

							<div class="modal-body">	
							<div name='add_form' id='add_form'>
				<table>
				<thead>
				<tr><th colspan=2>Add Equipment</th></tr>
				</thead>
				<tbody>
				<tr>
				<td>
				Equipment Type
				</td>
				<td>				
				
				<select name='equipt_type'>
				<?php
				$db=new mysqli("localhost","root","","transport");
				$sql="select * from equipment_type";
				
				$rs=$db->query($sql);
				$nm=$rs->num_rows;
				
				for($l=0;$l<$nm;$l++){
					$row=$rs->fetch_assoc();
				?>	
					<option value='<?php echo $row['incident_code']; ?>' >
					<?php echo $row['equipment_name']; ?>
					
					</option>
				<?php	
				}
				
				
				
				
				?>
				
				</select>

				</td>
				</tr>
				<tr>
				<td>
				Equipment Name
				</td>
				<td>
				<input type='text' name='equipment_name' />

				</td>
				</tr>
				</tbody>
				</table>

				</div>
			</div>
						
			<div class="modal-footer">
				<a href="#" class="btn" data-dismiss="modal">Close</a>
				<button type='submit' class="btn btn-primary" id='Suc' value='Submit'>Edit </button>
			</div>
			  </form>
		</div>
		
		
		<script src="js/jquery-migrate-1.2.1.min.js"></script>	
		<script src="js/jquery-ui-1.10.3.custom.min.js"></script>	
		<script src="js/jquery.ui.touch-punch.js"></script>
		<script src="js/modernizr.js"></script>	
		<script src="js/bootstrap.min.js"></script>	
		<script src="js/date.js"></script>	
		<!--
		<script src='js/form2.js'></script>
		-->