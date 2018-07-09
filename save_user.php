<?php

$firstname = strtoupper($_REQUEST['firstName']);
$lastname = strtoupper($_REQUEST['lastName']);
$midname = strtoupper($_REQUEST['midName']);
$empnum = $_REQUEST['empNum'];
$pos = $_REQUEST['position'];

include 'conn.php';

$sql = "insert into train_driver(firstName,lastName,midName,empNum,position) values('$firstname','$lastname','$midname','$empnum','$pos')";
$result = @mysql_query($sql);
if ($result){
	echo json_encode(array('success'=>true));
} else {
	echo json_encode(array('msg'=>'Some errors occured.'));
}
?>