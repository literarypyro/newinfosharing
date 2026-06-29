<?php

$id = intval($_REQUEST['id']);

include 'conn.php';

$sql = "delete from train_driver where id=$id";
$result = $db->query($sql);
if ($result){
	echo json_encode(array('success'=>true));
} else {
	echo json_encode(array('msg'=>'Some errors occured.'));
}
?>