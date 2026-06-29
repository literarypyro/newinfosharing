<?php 
require("Tmenu.php");
ini_set("date.timezone","Asia/Kuala_Lumpur");
?>
<?php
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_user_transport");
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


function changeForm(form_type,form_id,form_extra){
	var htmlCode="";
	form_extra="equipment_list";
	if(form_type=="insertion"){
		htmlCode="<table>";
		htmlCode+="<tr>";
		htmlCode+="<th colspan=2>Add Insertion</th>";
		htmlCode+="</tr>";

		htmlCode+="<tr>";
		htmlCode+="<td>Insertion Time</td>";
		htmlCode+="<td id='cell' name='cell'>";
		htmlCode+="</td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td>Train Driver</td>";
		if(form_extra=="unimog"){
	//		htmlCode+="<td><input type=text name='unimog_train_driver' />";
			htmlCode+="<td id='ph_trams_tag' name='ph_trams_tag' >";
			
			htmlCode+="</td>";
			

		}
		else if(form_extra="equipment_list"){
					htmlCode="<table>";
		htmlCode+="<tr>";
		htmlCode+="<th colspan=2>Add Removal</th>";
		htmlCode+="</tr>";

		htmlCode+="<tr>";
		htmlCode+="<td>Removal Time</td>";
		htmlCode+="<td id='cell' name='cell'>";
	//	htmlCode+=document.getElementById('cell').innerHTML;
		htmlCode+="</td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		}
		else if(form_extra=="test"){
//			htmlCode+="<td><input type=text name='unimog_train_driver' />";

			htmlCode+="<td id='ph_trams_tag' name='ph_trams_tag' >";
			
			htmlCode+="</td>";
			

		}
		else if(form_extra=="schooling"){
//			htmlCode+="<td><input type=text name='unimog_train_driver' />";

			htmlCode+="<td id='school_tag' name='school_tag' >";
			
			htmlCode+="</td>";
			

		}


		else if(form_extra=="reserve"){
			htmlCode+="<td><input type=text name='unimog_train_driver' />";
			
			htmlCode+="</td>";

		}

		else {
			htmlCode+="<td id='td' name='td'>";

			htmlCode+="</td>";

			setHTML();	

		}

//		else {
//			htmlCode+="<td id='td' name='td'>";
//			htmlCode+=document.getElementById('td').innerHTML;
//			htmlCode+="</td>";
//			setHTML();	
		
//		}		
		
		
		
		htmlCode+="</tr>";

		htmlCode+="<tr>";
		htmlCode+="<td>Inserted To</td>";	
		htmlCode+="<td>";	
		
		htmlCode+="<select name='inserted_to' id='inserted_to'>";
		htmlCode+="<option value='north'>North Ave.</option>";
		htmlCode+="<option value='quezon'>Quezon Ave.</option>";
		
		
		
		
		htmlCode+="</select>";
		htmlCode+="</td>";	

		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td colspan=2 class='submit' align=center>";
		htmlCode+="<input type=hidden name='insertion_id' id='insertion_id' value='"+form_id+"' />";
		htmlCode+="<input type=submit value='Submit' />";
		htmlCode+="</td>";
		htmlCode+="</tr>";
		htmlCode+="</table>";
	
	}
	else if(form_type=="removal"){
		htmlCode="<table>";
		htmlCode+="<tr>";
		htmlCode+="<th colspan=2>Add Removal</th>";
		htmlCode+="</tr>";

		htmlCode+="<tr>";
		htmlCode+="<td>Removal Time</td>";
		htmlCode+="<td id='cell' name='cell'>";
	//	htmlCode+=document.getElementById('cell').innerHTML;
		htmlCode+="</td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td>Train Driver</td>";
		
		if(form_extra=="unimog"){
//			htmlCode+="<td><input type=text name='unimog_train_driver' />";
			htmlCode+="<td id='ph_trams_tag' name='ph_trams_tag' >";
			
			htmlCode+="</td>";

		}
		else if(form_extra=="test"){
//			htmlCode+="<td ><input type=text name='unimog_train_driver' />";
			htmlCode+="<td id='ph_trams_tag' name='ph_trams_tag' >";
			
			htmlCode+="</td>";

		}

		else if(form_extra=="reserve"){
			htmlCode+="<td><input type=text name='unimog_train_driver' />";
			
			htmlCode+="</td>";

		}
		else if(form_extra=="schooling"){
//			htmlCode+="<td><input type=text name='unimog_train_driver' />";

			htmlCode+="<td id='school_tag' name='school_tag' >";
			
			htmlCode+="</td>";
			

		}
		
		
		
		else {
			htmlCode+="<td id='td' name='td'>";
			htmlCode+="</td>";
		
		}


		htmlCode+="</tr>";
		if(form_extra=="test"){
			/*
			htmlCode+="<tr>";
			htmlCode+="<td>MSD</td>";
			htmlCode+="<td><input type=text name='test_msd' /></td>";
			
			htmlCode+="</tr>";
			htmlCode+="<tr>";
			htmlCode+="<td>SSU</td>";
			htmlCode+="<td><input type=text name='test_ssu' /></td>";
			
			
			htmlCode+="</tr>";
			htmlCode+="<tr>";
			htmlCode+="<td>PH Trams</td>";
			htmlCode+="<td><input type=text name='test_maintenance' /></td>";

			
			
			htmlCode+="</tr>";
			*/

			htmlCode+="<tr>";
			
			htmlCode+="<td>Remarks/Cause of <br>Failure/Removal</td>";
			htmlCode+="<td><span name='remarks_space' id='remarks_space'>";
			htmlCode+="<textarea name='remarks' cols=50></textarea>";
			htmlCode+="</span><input type=checkbox name='remarks_check' id='remarks_check' onclick='setPreset(this)' />Preset Values</td>";	
			
			htmlCode+="</tr>";			
			
			
			
		}
		else {
		
			htmlCode+="<tr>";
			
			htmlCode+="<td>Remarks/Cause of <br>Failure/Removal</td>";
			htmlCode+="<td><span name='remarks_space' id='remarks_space'>";
			htmlCode+="<textarea name='remarks' cols=50></textarea>";
			htmlCode+="</span><input type=checkbox name='remarks_check' id='remarks_check' onclick='setPreset(this)' />Preset Values</td>";	
			
			htmlCode+="</tr>";
		}	
		htmlCode+="<tr>";
		htmlCode+="<td>Removed From</td>";	
		htmlCode+="<td>";	
		
		htmlCode+="<select name='removed_from' id='removed_from'>";
		htmlCode+="<option value='north'>North Ave.</option>";
		htmlCode+="<option value='quezon'>Quezon Ave.</option>";
		
		
		
		
		htmlCode+="</select>";
		htmlCode+="</td>";	

		htmlCode+="</tr>";

		htmlCode+="<tr>";
		htmlCode+="<td>";
		htmlCode+="Add Incident?";
		htmlCode+="</td>";	

		htmlCode+="<td>";
		htmlCode+="<input type='checkbox' name='cancel_loop' id='cancel_loop' />";
		htmlCode+="Open Incident Report</td>";	
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td colspan=2 class='submit' align=center>";
		htmlCode+="<input type=hidden name='remove_id' id='remove_id' value='"+form_id+"' />";
		htmlCode+="<input type=submit value='Submit' />";
		htmlCode+="</td>";
		htmlCode+="</tr>";
		htmlCode+="</table>";
	}
else if(form_type=="index_switch"){
		htmlCode="<table>";
		htmlCode+="<tr>";
		htmlCode+="<th colspan=2>Switch Index No.</th>";
		htmlCode+="</tr>";

		htmlCode+="<tr>";
		htmlCode+="<td>New Index No.</td>";
		htmlCode+="<td><input type=text id='new_index_input' /></td>";
		htmlCode+="</tr>";
		
		htmlCode+="<tr>";
		htmlCode+="<td>Time of Switch</td>";
		htmlCode+="<td id='cell' name='cell'></td>";
		htmlCode+="</tr>";
				htmlCode+="<tr>";
		htmlCode+="<td>Train Driver</td>";
			htmlCode+="<td id='td' name='td'>";

			htmlCode+="</td>";

			setHTML();	


//		else {
//			htmlCode+="<td id='td' name='td'>";
//			htmlCode+=document.getElementById('td').innerHTML;
//			htmlCode+="</td>";
//			setHTML();	
		
//		}		
		
		
		
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td colspan=2 align=center>";
		htmlCode+="<button type='button' onclick='submitSwitch("+form_id+")'>Submit</button>";
		htmlCode+="</td>";
		htmlCode+="</tr>";
		htmlCode+="</table>";
	
	}
	else if(form_type=="editIndex"){
		htmlCode="<table>";
		htmlCode+="<tr>";
		htmlCode+="<th colspan=2>Edit Index No.</th>";
		htmlCode+="</tr>";

		htmlCode+="<tr>";
		htmlCode+="<td>New Index No.</td>";
		htmlCode+="<td><input type=text name='edit_index' /></td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td colspan=2>";
		htmlCode+="<input type='submit' class='submit' value='Submit' />";
		htmlCode+="</td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<input type=hidden name='edit_id' id='edit_id' value='"+form_id+"' />";
		htmlCode+="</tr>";
		htmlCode+="</table>";	
	
	}
	else if(form_type=="editCar"){
		htmlCode="<table>";
		htmlCode+="<tr>";
		htmlCode+="<th colspan=2>Edit Car</th>";
		htmlCode+="</tr>";

		htmlCode+="<tr>";
		htmlCode+="<td>Car 1</td>";
		htmlCode+="<td><input type=text name='car_1' id='car_1' autocomplete='off' onblur='fillCar(\"first\",this.value)'  /></td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td>Car 2</td>";
		htmlCode+="<td><input type=text name='car_2' id='car_2' autocomplete='off'  onblur='fillCar(\"mid\",this.value)'  /></td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td>Car 3</td>";
		htmlCode+="<td><input type=text name='car_3' id='car_3' autocomplete='off'  onblur='fillCar(\"last\",this.value)'  /></td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td colspan=2>";
		htmlCode+="<input type='submit' class='submit' value='Submit' />";
		htmlCode+="</td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		
		htmlCode+="<input type=hidden name='edit_car' id='edit_car' value='"+form_id+"' />";
		htmlCode+="</tr>";
		htmlCode+="</table>";	
	
	}
	
	
	else if(form_type=="add_train"){
		htmlCode="<table>";
		htmlCode+="<tr>";
		htmlCode+="<th colspan=2>Add/Prep Train</th>";
		htmlCode+="</tr>";
		htmlCode+="<tr><td>Type</td>";
		htmlCode+="<td>";
		htmlCode+="<select name='type' id='type' onchange='setTrain(this.value)'>";
		htmlCode+="<option value='revenue'>Revenue Train</option>";
		htmlCode+="<option value='reserve'>Reserve Train</option>";
		htmlCode+="<option value='schooling'>Schooling Train</option>";
		htmlCode+="<option value='finance'>Finance Train</option>";
		htmlCode+="<option value='test'>Test Train</option>";
		htmlCode+="</select>";
		htmlCode+="</td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td>Index No.</td>";
		htmlCode+="<td  id='index_tag' name='index_tag'><input type=text name='index_no'  autocomplete='off'  /></td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td>Car 1</td>";
		htmlCode+="<td><input type=text name='car_1' id='car_1' autocomplete='off' onblur='fillCar(\"first\",this.value)'  /></td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td>Car 2</td>";
		htmlCode+="<td><input type=text name='car_2' id='car_2' autocomplete='off'  onblur='fillCar(\"mid\",this.value)'  /></td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td>Car 3</td>";
		htmlCode+="<td><input type=text name='car_3' id='car_3' autocomplete='off'  onblur='fillCar(\"last\",this.value)'  /></td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";


		htmlCode+="<tr>";
		htmlCode+="<td>Car 4</td>";
		htmlCode+="<td><input type=text name='car_4' id='car_4' autocomplete='off'  onblur='fillCar(\"last2\",this.value)'  /></td>";
		htmlCode+="</tr>";



		htmlCode+="<td>LPAM No.</td>";
		htmlCode+="<td><input type=text name='lpam_id'  autocomplete='off'  /></td>";		
		htmlCode+="</tr>";


		htmlCode+="<tr>";
		htmlCode+="<th colspan=2>";
		htmlCode+="<input type=checkbox name='cancel_departure' id='cancel_departure' />Cancelled";
		htmlCode+="</th>";
		htmlCode+="</tr>";


		
		htmlCode+="<tr>";
		
		htmlCode+="<td>I336 Time</td>";
		htmlCode+="<td id='cell' name='cell'>";
		htmlCode+="</td>";
		htmlCode+="</tr>";

		htmlCode+="<tr><td align=center class='submit' colspan=2>";

		htmlCode+="<input type='submit' value='Add' />";
		htmlCode+="</td>";
		htmlCode+="</table>";
	}
	else if(form_type=="unimog"){
		htmlCode="<table>";
		htmlCode+="<tr>";
		htmlCode+="<th colspan=2>Add/Prep Unimog Train</th>";
		htmlCode+="</tr>";
		htmlCode+="<tr><td>Type of Train</td>";
		htmlCode+="<td>";	
		htmlCode+="<select name='train_type'>";
		htmlCode+="<option value='unimog'>UNIMOG</option>";	
		htmlCode+="</select>";

		htmlCode+="</td>";	

		htmlCode+="</tr>";

		htmlCode+="<tr>";
		htmlCode+="<td>Index No.</td>";
		htmlCode+="<td>";
		htmlCode+="<select name='other_index_no'>";
		for(var n=80;n<=89;n++){

			htmlCode+="<option value='"+n+"'>"+n+"</option>";
		}
		htmlCode+="</select>";
		
		htmlCode+="</td>";
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<th colspan=2>";
		htmlCode+="<input type=checkbox name='cancel_departure' id='cancel_departure' />Cancelled";
		htmlCode+="</th>";
		htmlCode+="</tr>";
		
		htmlCode+="<tr>";
		
		htmlCode+="<td>I336 Time</td>";
		htmlCode+="<td id='cell' name='cell'>";
		htmlCode+="</td>";
		htmlCode+="</tr>";

		htmlCode+="<tr><td align=center colspan=2>";

		htmlCode+="<input type='submit' class='submit' value='Add' />";
		htmlCode+="</td>";
		htmlCode+="</table>";
	}
	else if(form_type=="remarks"){
		htmlCode="<table>";
		htmlCode+="<tr>";
		htmlCode+="<th colspan=2>Add/Edit Remarks</th>";
		htmlCode+="</tr>";
/*
		if(form_extra=="test"){
		htmlCode+="<tr>";
		htmlCode+="<td>MSD</td>";
		htmlCode+="<td><input type=text name='test_msd' /></td>";
		
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td>SSU</td>";
		htmlCode+="<td><input type=text name='test_ssu' /></td>";
		
		
		htmlCode+="</tr>";
		htmlCode+="<tr>";
		htmlCode+="<td>PH Trams</td>";
		htmlCode+="<td><input type=text name='test_maintenance' /></td>";
		
		
		htmlCode+="</tr>";
		}
		else {
*/		
		htmlCode+="<tr>";
		
		htmlCode+="<td>Remarks/Cause of <br>Failure/Removal</td>";
		htmlCode+="<td><span name='remarks_space' id='remarks_space'>";
		htmlCode+="<textarea name='remarks' cols=50>";
		htmlCode+=form_extra;
		htmlCode+="</textarea>";
		htmlCode+="</span><input type=checkbox name='remarks_check' id='remarks_check' onclick='setPreset(this)' />Preset Values</td>";	
		
		htmlCode+="</tr>";
//		}	

		htmlCode+="<tr>";
		htmlCode+="<td colspan=2 class='submit' align=center>";
		htmlCode+="<input type=hidden name='remarks_id' id='remarks_id' value='"+form_id+"' />";
		htmlCode+="<input type=submit value='Submit' />";
		htmlCode+="</td>";
		htmlCode+="</tr>";
		htmlCode+="</table>";		
	
	}
	
	
	document.getElementById('add_form').innerHTML=htmlCode;
	$('#addModal').modal('show');

	setDate();
	
	if((form_type=="removal")||(form_type=="insertion")){
		if((form_extra=="test")||(form_extra=="unimog")){
			setPH();
		}
		else if(form_extra=="schooling"){
			setSchool();	
		}
		else {
			setHTML();	
		}
	}
		

}


</script>

<script language='javascript' src='ajax.js'></script>
<?php
?>
<?php


	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
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
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
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
			<div class="modal hide fade" id="editModal">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">×</button>
				<h3>Edit</h3>
			</div>

				<div class="modal-body">	
						<form  name='add_form' id='add_form' action='train_availability.php' method='post'>

				</form>

				</div>
						
			<div class="modal-footer">
				<a href="#" class="btn" data-dismiss="modal">Close</a>
				<button type='submit' class="btn btn-primary" id='Suc' value='Submit'>Edit </button>
			</div>
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