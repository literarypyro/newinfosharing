<?php

$position = $_REQUEST['position'];
$levelid = $_REQUEST['levelid'];
$username = $_REQUEST['username'];
$password = $_REQUEST['password'];
$division = $_REQUEST['division'];


//$firstname = strtoupper($_REQUEST['firstName']);
$lastname = strtoupper($_REQUEST['lastName']);

$Ffind = strpos($lastname,',');
$FValue = substr($lastname,(intval($Ffind)+2));
$LValue = substr($lastname,0,intval($Ffind));

include 'connuser.php';

//$sql = "insert into users(username,password,firstName,lastName,position,levelid,division) values ('$username','$password','$FValue','$LValue','$position','$levelid','$division')";
//$sql = "insert into users(username,password,firstName,lastName,position,levelid) values ('$username','$password','$FValue','$LValue','$position','$levelid')";
$sql = "insert into users(username,password,firstName,lastName,division,levelid) values ('$username','$password','$FValue','$LValue','$division','$levelid')";


$result = @mysql_query($sql);
if ($result){
	echo json_encode(array('success'=>true));
} else {
//	echo json_encode(array('msg'=>'Some errors occured.'));
	echo json_encode(array('msg'=>$result));

}
?>


