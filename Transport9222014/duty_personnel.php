<?php
session_start();
?>
<?php
ini_set("date.timezone","Asia/Kuala_Lumpur");
?>
<!--- Modified by Jun
//--- Date: 7/11/2014
//--- Modify: Incident No. no longer system generate no. input manually
//--- Marker: @jun
//--------------------------------------------------->
<?php
if(isset($_POST['personnel_date'])){
	$db=new mysqli("localhost","root","","user_transport");
	$sql="select * from duty_personnel where personnel_date like '".date("Y-m-d",strtotime($_POST['personnel_date']))."%%' and shift='".$_POST['shift']."'";
	$rs=$db->query($sql);
	$nm=$rs->num_rows;
	
	$input_date=date("Y-m-d",strtotime($_POST['personnel_date']));
	if($nm>0){
		$row=$rs->fetch_assoc();
		$update="update duty_personnel set st='".$_POST['tcad']."',sr='".$_POST['sr']."',man900='".$_POST['man900']."',reliever='".$_POST['reliever']."',recording='".$_POST['recording']."',communications='".$_POST['communications']."',duty_manager='".$_POST['duty_manager']."',clerk='".$_POST['clerk']."' where id='".$row['id']."'";

		$updateRS=$db->query($update);
		
	}
	else {
		$update="insert into duty_personnel(personnel_date,shift,recording,man900,reliever,communications,duty_manager,clerk,st,sr) values ";
		$update.="('".$input_date."','".$_POST['shift']."','".$_POST['recording']."','".$_POST['man900']."','".$_POST['reliever']."','".$_POST['communications']."','".$_POST['duty_manager']."','".$_POST['clerk']."','".$_POST['tcad']."','".$_POST['sr']."')";

		$updateRS=$db->query($update);
	
	}
	
	$_SESSION['personnel_date']=$input_date;
	$_SESSION['personnel_shift']=$_POST['shift'];
	
	
	header("Location: main_page.php");
}
?>
<style type='text/css'>
.dropdown-menu{
position:absolute;
top:100%;
left:0;
z-index:1000;
display:none;
float:left;
min-width:160px;
padding:5px 0;
margin:2px 0 0;
list-style:none;
background-color:#fff;
border:1px solid #ccc;
border:1px solid rgba(0,0,0,0.2);
*border-right-width:2px;
*border-bottom-width:2px;
-webkit-border-radius:6px;
-moz-border-radius:6px;
border-radius:6px;
-webkit-box-shadow:0 5px 10px rgba(0,0,0,0.2);
-moz-box-shadow:0 5px 10px rgba(0,0,0,0.2);box-shadow:0 5px 10px rgba(0,0,0,0.2);-webkit-background-clip:padding-box;-moz-background-clip:padding;background-clip:padding-box}.dropdown-menu.pull-right{right:0;left:auto}.dropdown-menu .divider{*width:100%;height:1px;margin:9px 1px;*margin:-5px 0 5px;overflow:hidden;background-color:#e5e5e5;border-bottom:1px solid #fff}.dropdown-menu>li>a{display:block;padding:3px 20px;clear:both;font-weight:normal;line-height:20px;color:#333;white-space:nowrap}.dropdown-menu>li>a:hover,.dropdown-menu>li>a:focus,.dropdown-submenu:hover>a,.dropdown-submenu:focus>a{color:#fff;text-decoration:none;background-color:#0081c2;background-image:-moz-linear-gradient(top,#08c,#0077b3);background-image:-webkit-gradient(linear,0 0,0 100%,from(#08c),to(#0077b3));background-image:-webkit-linear-gradient(top,#08c,#0077b3);background-image:-o-linear-gradient(top,#08c,#0077b3);background-image:linear-gradient(to bottom,#08c,#0077b3);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff0088cc',endColorstr='#ff0077b3',GradientType=0)}.dropdown-menu>.active>a,.dropdown-menu>.active>a:hover,.dropdown-menu>.active>a:focus{color:#fff;text-decoration:none;background-color:#0081c2;background-image:-moz-linear-gradient(top,#08c,#0077b3);background-image:-webkit-gradient(linear,0 0,0 100%,from(#08c),to(#0077b3));background-image:-webkit-linear-gradient(top,#08c,#0077b3);background-image:-o-linear-gradient(top,#08c,#0077b3);background-image:linear-gradient(to bottom,#08c,#0077b3);background-repeat:repeat-x;outline:0;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff0088cc',endColorstr='#ff0077b3',GradientType=0)}.dropdown-menu>.disabled>a,.dropdown-menu>.disabled>a:hover,.dropdown-menu>.disabled>a:focus{color:#999}.dropdown-menu>.disabled>a:hover,.dropdown-menu>.disabled>a:focus{text-decoration:none;cursor:default;background-color:transparent;background-image:none;filter:progid:DXImageTransform.Microsoft.gradient(enabled=false)}.open{*z-index:1000}.open>.dropdown-menu{display:block}.dropdown-backdrop{position:fixed;top:0;right:0;bottom:0;left:0;z-index:990}.pull-right>.dropdown-menu{right:0;left:auto}.dropup .caret,.navbar-fixed-bottom .dropdown .caret{border-top:0;border-bottom:4px solid #000;content:""}.dropup .dropdown-menu,.navbar-fixed-bottom .dropdown .dropdown-menu{top:auto;bottom:100%;margin-bottom:1px}.dropdown-submenu{position:relative}.dropdown-submenu>.dropdown-menu{top:0;left:100%;margin-top:-6px;margin-left:-1px;-webkit-border-radius:0 6px 6px 6px;-moz-border-radius:0 6px 6px 6px;border-radius:0 6px 6px 6px}.dropdown-submenu:hover>.dropdown-menu{display:block}.dropup .dropdown-submenu>.dropdown-menu{top:auto;bottom:0;margin-top:0;margin-bottom:-2px;-webkit-border-radius:5px 5px 5px 0;-moz-border-radius:5px 5px 5px 0;border-radius:5px 5px 5px 0}.dropdown-submenu>a:after{display:block;float:right;width:0;height:0;margin-top:5px;margin-right:-10px;border-color:transparent;border-left-color:#ccc;border-style:solid;border-width:5px 0 5px 5px;content:" "}.dropdown-submenu:hover>a:after{border-left-color:#fff}.dropdown-submenu.pull-left{float:none}.dropdown-submenu.pull-left>.dropdown-menu{left:-100%;margin-left:10px;-webkit-border-radius:6px 0 6px 6px;-moz-border-radius:6px 0 6px 6px;border-radius:6px 0 6px 6px}.dropdown .dropdown-menu .nav-header{padding-right:20px;padding-left:20px}.typeahead{z-index:1051;margin-top:2px;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px}.well{min-height:20px;padding:19px;margin-bottom:20px;background-color:#f5f5f5;border:1px solid #e3e3e3;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.05);-moz-box-shadow:inset 0 1px 1px rgba(0,0,0,0.05);box-shadow:inset 0 1px 1px rgba(0,0,0,0.05)}.well blockquote{border-color:#ddd;border-color:rgba(0,0,0,0.15)}
</style>
<script language='javascript' src='js/jquery-1.10.2.min.js'></script>
	<link href="css/style.min.css" rel="stylesheet" />


<script language='javascript' src='ajax.js'></script>
<script language='javascript'>
function checkShift(element){
	var shift=element.value;
	if(shift=='3'){
		$('#clerk_entry').show();
		$('.shift_1_entry').hide();
	}
	else {
		if(shift==1){
			$('.shift_1_entry').show();
		}
		else {
			$('.shift_1_entry').hide();
		}
		$('#clerk_entry').hide();
	}

}

</script>

<style type='text/css'>
/*
body {
	/*background-color: gray; 
	color: #fff;		
	margin-left:30px;
	margin-right:30px;		*/
/*	
	background-color: #dfe7f2;
	color: #000000;
} 
 */
 
.content {
	width: 80%;
	margin: 20px auto 40px auto; 
	background-color: #ffa;
	color: #333;
	border: 2px solid #1a3c2d; 
	padding: .75em;
	spacing: .5px; 
}


/* table border */
 .ir table {
margin: .75em auto auto auto; 
	color: #000;
	border: 1px solid rgb(185, 201, 254); 	
} 


/* Title header */
 .ir th {
	background-color: #cccccc; 
	text-align: center;
	border: 1px solid black;
	color:  black; 
	font-family: "Comic Sans MS"; 
	font-size: 17px	
}


/* left-side color txt background */
  .ir tr td:first-child {
 	background-color: #F3F3F3;
/* 	width: 45%;  */
 	padding: 5px
 	
/*	 background-color: rgb(185, 201, 254);
	color: rgb(0,51,153); */
} 

/* gray background right-side */
 .ir tr td:last-child {
 	background-color: rgb(240,240,240);
 	padding: 5px
 		
/*	 background-color: #dfe7f2;
	color: #fff; */
} 

/* border line blue outline */ 
  .ir td {
	/* border: 1px solid rgb(185, 201, 254); */
	border: 1px solid #808080	
}

input[type="text"]{ 
	height:25px; 	
	border: 1px solid #FFD700;
	background-color: #FFFACD;
	border-radius: 3px;	
	
/*	font-weight:bold; 
	font-size:15px; 
	font-family:ariel; 	
	border: 1px solid #dfe7f2;
    background-color: #dfe7f2;
	color: rgb(0,51,153);
	border-radius: 3px; */
}


textarea{ 
	border: 1px solid #FFD700;
	background-color: #FFFACD;
	border-radius: 3px;	
	
/*	border: 1px solid #dfe7f2;
	background-color: #dfe7f2;
	color: rgb(0,51,153);
	border-radius: 3px; */
}

input[type="text"]:focus {
	background-color:#FFFFF0;
	
/*	background-color:rgb(158,27,32);
	color:white; */

}

textarea:focus {
	background-color:#FFFFF0;
	
/*	background-color:rgb(158,27,32);
	color:white;
	font-weight:bold; */
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

/*-- disable the color of drop down
 select { border: 1px solid #dfe7f2; color: rgb(0,51,153); background-color:  #dfe7f2;  } */

ul.nav li a{
	text-decoration: none;
}

.removal {
	color: rgb(0,51,153);
}

.removalnone {
	color:rgb(223,231,242);

}

.ir #multi_list tr th {
	background-color: #33aa55;
	color: #fff;
	border: 1px solid rgb(185, 201, 254);

}

.ir #multi_list tr:nth-child(2) td {
	background-color: rgb(185, 201, 254);
	color: rgb(0,51,153);


}
.ir #multi_list tr:nth-child(n+2) td{
	background-color: #dfe7f2;
	color: rgb(0,51,153);

}

</style>
<body>
<br>
<br>
<div>
<br>
<br>
<!--<a href='monitoring menu.php'>Go Back to Monitoring Menu</a>-->
<form action='duty_personnel.php' method='post'>




<table class='ir' align=center style='border-collapse:collapse'>
<tr>
<th colspan=2>Enter Duty Personnel</th>
</tr>
<tr>
<td>
Enter Date
</td>
<td>
<input type='text' name='personnel_date' id='personnel_date' class='datepicker' value='<?php echo date("m/d/Y"); ?>'/>
</td>
</tr>

<tr>
<td>Enter Shift</td> 
<td>
<select name='shift' id='shift' onchange='checkShift(this)'>
	<option value='1'>1</option>
	<option value='2'>2</option>
	<option value='3'>3</option>

</select>



</td>

</tr>
<tr><th colspan=2>CCS</th></tr>




<tr>
<td>Recording</td>
<td>
<select name='recording' id='recording' class='stdo'>
<option></option>
<?php
$db=new mysqli("localhost","root","","transport");
$sql="select * from train_driver where position in ('STDO') order by lastName";
$rs=$db->query($sql);
$nm=$rs->num_rows;
for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
?>	
	<option value='<?php echo $row['id']; ?>'><?php echo $row['lastName'].", ".$row['firstName']; ?></option>


<?php
}

?>
</select>
</td>
</tr>

<tr>
<td>MAN900</td>
<td>
<select name='man900' id='man900' class='stdo'>
<option></option>

<?php
$db=new mysqli("localhost","root","","transport");
$sql="select * from train_driver where position in ('STDO') order by lastName";
$rs=$db->query($sql);
$nm=$rs->num_rows;
for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
?>	
	<option value='<?php echo $row['id']; ?>'><?php echo $row['lastName'].", ".$row['firstName']; ?></option>


<?php
}

?>
</select>
</td>
</tr>


<tr>
<td>Communications</td>
<td>
<select name='communications' id='communications' class='stdo'>
<option></option>

<?php
$db=new mysqli("localhost","root","","transport");
$sql="select * from train_driver where position in ('STDO') order by lastName";
$rs=$db->query($sql);
$nm=$rs->num_rows;
for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
?>	
	<option value='<?php echo $row['id']; ?>'><?php echo $row['lastName'].", ".$row['firstName']; ?></option>


<?php
}

?>
</select>
</td>
</tr>


<tr>
<td>Reliever</td>
<td>
<select name='reliever' id='reliever' class='stdo'>
<option></option>

<?php
$db=new mysqli("localhost","root","","transport");
$sql="select * from train_driver where position in ('STDO') order by lastName";
$rs=$db->query($sql);
$nm=$rs->num_rows;
for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
?>	
	<option value='<?php echo $row['id']; ?>'><?php echo $row['lastName'].", ".$row['firstName']; ?></option>


<?php
}

?>
</select>
</td>
</tr>
<tr>
<th colspan=2>Duty Manager</th>
</tr>
<tr>

<td colspan=2>
<select name='duty_manager' id='duty_manager'>
<?php
$db=new mysqli("localhost","root","","transport");
$sql="select * from train_driver where position in ('SVTDO') order by lastName";
$rs=$db->query($sql);
$nm=$rs->num_rows;
for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
?>	
	<option value='<?php echo $row['id']; ?>'><?php echo $row['lastName'].", ".$row['firstName']; ?></option>


<?php
}

?>
</select>
</td>
</tr>
<tr class='shift_1_entry'>
<td>Enter ST/TCAD</td>
<td>
<select name='tcad' id='tcad'>
<?php
$db=new mysqli("localhost","root","","transport");
$sql="select * from train_driver where position in ('SVTDO') order by lastName";
$rs=$db->query($sql);
$nm=$rs->num_rows;
for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
?>	
	<option value='<?php echo $row['id']; ?>'><?php echo $row['lastName'].", ".$row['firstName']; ?></option>


<?php
}

?>
</select>
</td>
</tr>
<tr class='shift_1_entry'>
<td>Enter SR/Mainline</td>
<td>
<select name='sr' id='sr'>
<?php
$db=new mysqli("localhost","root","","transport");
$sql="select * from train_driver where position in ('SVTDO') order by lastName";
$rs=$db->query($sql);
$nm=$rs->num_rows;
for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
?>	
	<option value='<?php echo $row['id']; ?>'><?php echo $row['lastName'].", ".$row['firstName']; ?></option>


<?php
}

?>
</select>
</td>
</tr>
<tr name='clerk_entry' id='clerk_entry' style='display:none'>
<td>Enter Clerk</td>
<td>
<select name='clerk' id='clerk'>
<?php
$db=new mysqli("localhost","root","","transport");
$sql="select * from train_driver where position in ('CLERK III') order by lastName";
$rs=$db->query($sql);
$nm=$rs->num_rows;
for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
?>	
	<option value='<?php echo $row['id']; ?>'><?php echo $row['lastName'].", ".$row['firstName']; ?></option>


<?php
}

?>
</select>
</td>
</tr>



<tr>
<th colspan=2><input type=submit value='Submit' /></th>
</tr>
</table>
</form>
</div>
<!--index number and car no.-->
</body>
		<script src="js/jquery-1.10.2.min.js"></script>
	<script src="js/jquery-migrate-1.2.1.min.js"></script>	
		<script src="js/jquery-ui-1.10.3.custom.min.js"></script>	
		<script src="js/jquery.ui.touch-punch.js"></script>	
		<script src="js/modernizr.js"></script>	
		<script src="js/bootstrap.min.js"></script>	

		<script src="js/additional2.js"></script>	
		
		<script src="js/date.js"></script>	
<script language='javascript'>
$(function(){		
		
var doNotShow = [];
$('.stdo').change( function() {
  var nextSelect = $(this).attr("var");

  doNotShow.push($(this).val());

  $.each(doNotShow, function( index, value ) {
//    $(".stdo option[value='" + value + "']").prop('disabled', true);
    $(".stdo option[value='" + value + "']").prop('hidden', true);

  });

 // $("#" + nextSelect).prop('disabled', false);
  $(this).prop('disabled', true);

});





});
</script>