<?php
	$page = isset($_POST['page']) ? intval($_POST['page']) : 1;
	$rows = isset($_POST['rows']) ? intval($_POST['rows']) : 10;
	$sort = isset($_POST['sort']) ? strval($_POST['sort']) : 'lastName';
	$order = isset($_POST['order']) ? strval($_POST['order']) : 'asc';		
	$SFind = isset($_POST['SFind']) ? mysql_real_escape_string($_POST['SFind']) : '';
	
	$offset = ($page-1)*$rows;
	$result = array();

	include 'conn.php';	
	
	$where = "lastName like '$SFind%' or firstName like '$SFind%' or empNum like '$SFind%'";
$rs = mysql_query("select count(*) from train_driver where " . $where);
$row = mysql_fetch_row($rs);
$result["total"] = $row[0];
 
$rs = mysql_query("select * from train_driver where " . $where . " order by $sort $order limit $offset,$rows");
		
	$items = array();
	while($row = mysql_fetch_object($rs)){
		array_push($items, $row);
	}
	$result["rows"] = $items;

	echo json_encode($result);

?>