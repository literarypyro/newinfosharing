<?php
$id = intval($_REQUEST['id']);
$lastname = strtoupper($_REQUEST['lastName']);
$firstname = strtoupper($_REQUEST['firstName']);
$position = strtoupper($_REQUEST['position']);
$levelid = $_REQUEST['levelid'];
$username = $_REQUEST['username'];
$password = $_REQUEST['password'];

include 'connuser.php';

$sql = "update users set firstName='$firstname',lastName='$lastname',position='$position',levelid='$levelid',username='$username',password='$password' where id=$id";
$result = @mysql_query($sql);
if ($result){
	echo json_encode(array('success'=>true));
} else {
	echo json_encode(array('msg'=>'Some errors occured.'));
}
?>