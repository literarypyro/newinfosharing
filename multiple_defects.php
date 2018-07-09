<?php
$db=new mysqli("localhost","root","","transport");
$db2=new mysqli("localhost","root","","external");

if(isset($_POST['equipment'])){
	$equipt=$_POST['equipment'];
	$sub_item=$_POST['subitem'];

	$update="insert into temp_multiple(equipt_id,sub_item_id) values ('".$equipt."','".$sub_item."')";
	$updateRS=$db2->query($update);
	echo "<font color=red>Data added.</font><br>";
}
?>

<script language='javascript' src='ajax.js'></script>
<script language='javascript'>
function subItemScroll(){
	var problemType=document.getElementById('equipment').value;

	makeajax("processing.php?scrollSubItem="+problemType,"subItem");	

}

function subItem(ajaxHTML){
	var subHTML="";
	
	if(ajaxHTML=="No data available"){
	
	
	}
	else {
		var subItemTerms=ajaxHTML.split("==>");
		var count=(subItemTerms.length)*1-1;
		subHTML="<select id='subitem' name='subitem'>";
		subHTML+="<option></option>";
		for(var n=0;n<count;n++){
			var parts=subItemTerms[n].split(";");
			subHTML+="<option value='"+parts[0]+"'>";
			subHTML+=parts[1];
			subHTML+="</option>";
		
		}
		subHTML+="</select>";
	
	}
	document.getElementById('sub_item_space').innerHTML=subHTML;

}
function addEquipment(){

//	window.opener.location.reload();
	window.opener.retrieveDefects();
	
	self.close();

}
function deleteRow(index){
	var check=confirm("Remove Equipment?");
	if(check){
		makeajax("processing.php?removeEquipt="+index,"reloadPage");	
	}
}
function reloadPage(ajaxHTML){
	self.location="multiple_defects.php";


}
</script>
<style type='text/css'>
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

#list tr:nth-child(2) td{
	background-color: rgb(185, 201, 254);
	color: rgb(0,51,153);


}


#list tr:nth-child(n+2) td{
	background-color: #dfe7f2;
	color: rgb(0,51,153);

}

#list tr:last-child td{
	background-color: rgb(185, 201, 254);
	color: rgb(0,51,153);

}







#dataentry tr td:first-child {
	background-color: rgb(185, 201, 254);
	color: rgb(0,51,153);

}
#dataentry tr td:last-child {
	background-color: #dfe7f2;
	color: #fff;

}


td {
	border: 1px solid rgb(185, 201, 254);

}

</style>
<table id='list' width=70%>
<tr>
<th colspan=3>
Enter Equipment Defects
</th>
</tr>
<tr>
<td align=center>Equipment</td>
<td align=center>Sub-Item</td>
<td>&nbsp;</td>
</tr>
<?php
$db2=new mysqli("localhost","root","","external");

$sql="select * from temp_multiple";
$rs=$db2->query($sql);
$nm=$rs->num_rows;
for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
	
	$equipment_name="";
	$sub_item="";

	$equiptSQL="select * from equipment where id='".$row['equipt_id']."'";
	$equiptRS=$db->query($equiptSQL);
	
	$equiptRow=$equiptRS->fetch_assoc();
	$equipment_name=$equiptRow['equipment_name'];
	
	
	if($row['sub_item_id']==""){
	}
	else {
		$subitemSQL="select * from sub_item where id='".$row['sub_item_id']."'";
		$subitemRS=$db->query($subitemSQL);
		$subitemNM=$subitemRS->num_rows;
		
		if($subitemNM>0){
			$subitemRow=$subitemRS->fetch_assoc();
			$sub_item=$subitemRow['sub_item'];
		}
	}
	
?>	
	<tr>
	<td><?php echo $equipment_name; ?></td>
	<td><?php echo $sub_item; ?></td>
	<td><a href='#' onclick="deleteRow('<?php echo $row['id']; ?>')">X</a></td>
	</tr>
<?php	
}
?>
<tr>
<td colspan=3 align=center><input type=button value='Submit' onclick='addEquipment()' /></td>
</tr>
</table>
<br>
<form action='multiple_defects.php?problemType=<?php echo $_GET['problemType']; ?>' method='post'>
<table id='dataentry'>
<tr>
<td>Equipment</td>
<td>
<select name='equipment' id='equipment' onchange='subItemScroll()'>
<?php
$type=$_GET['problemType'];

$sql="select * from equipment where type='".$type."'";
$rs=$db->query($sql);
$nm=$rs->num_rows;
for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
	?>
	<option value='<?php echo $row['id']; ?>'>
	<?php echo $row['equipment_name']; ?>
	</option>
	<?php
}
?>
</select>
</td>
</tr>
<tr>
<td>Sub-Item</td><td><span name='sub_item_space' id='sub_item_space'></span></td>
</tr>
<tr>
<td align=center colspan=2><input type='submit' value='Add Item' /></td>
</tr>
</table>
</form>
<br>





