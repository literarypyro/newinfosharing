<?php

$id = intval($_REQUEST['id']);
$firstname = strtoupper($_REQUEST['firstName']);
$lastname = strtoupper($_REQUEST['lastName']);
$midname = strtoupper($_REQUEST['midName']);
$empnum = $_REQUEST['empNum'];
$pos = $_REQUEST['position'];

include 'conn.php';

$sql = "update train_driver set firstName='$firstname',lastName='$lastname',midName='$midname',empNum='$empnum',position='$pos' where id=$id";
$result = @mysql_query($sql);
if ($result){
	echo json_encode(array('success'=>true));
} else {
	echo json_encode(array('msg'=>'Some errors occured.'));
}
?>