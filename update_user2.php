<?php
$id = intval($_REQUEST['id']);
$lastname = strtoupper($_REQUEST['lastName']);
$firstname = strtoupper($_REQUEST['firstName']);





$position = strtoupper($_REQUEST['position']);
$levelid = $_REQUEST['levelid'];
$username = $_REQUEST['username'];
$password = $_REQUEST['password'];
$division = $_REQUEST['division'];

$Ffind = strpos($lastname,',');
$FValue = substr($lastname,(intval($Ffind)+2));
$LValue = substr($lastname,0,intval($Ffind));


include 'connuser.php';


//$sql = "update users set division='$division',firstName='$firstname',lastName='$lastname',position='$position',levelid='$levelid',username='$username',password='$password' where id=$id";
$sql = "update users set firstName='$FValue',lastName='$LValue',division='$division',levelid='$levelid',username='$username',password='$password' where id=$id";

$result = @mysql_query($sql);
if ($result){
	echo json_encode(array('success'=>true));
} else {
	echo json_encode(array('msg'=>'Some errors occured.'));
//	echo json_encode(array('msg'=>$sql));

	}
?>