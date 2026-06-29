<?php
	$page = isset($_POST['page']) ? intval($_POST['page']) : 1;
	$rows = isset($_POST['rows']) ? intval($_POST['rows']) : 10;
	$sort = isset($_POST['sort']) ? strval($_POST['sort']) : 'lastName';
	$order = isset($_POST['order']) ? strval($_POST['order']) : 'asc';		
	$SFind = isset($_POST['SFind']) ? mysql_real_escape_string($_POST['SFind']) : '';
	
	$offset = ($page-1)*$rows;
	$result = array();

	include 'connuser.php';	
	
	$where = "lastName like '$SFind%' or firstName like '$SFind%'";
$rs = $db->query("select count(*) from users where " . $where);
$row = $rs->fetch_row();
$result["total"] = $row[0];
 
$rs = $db->query("select * from users where " . $where . " order by $sort $order limit $offset,$rows");
		
	$items = array();
	while($row = $rs->fetch_assoc()){
		array_push($items, $row);
	}
	$result["rows"] = $items;

	echo json_encode($result);

?>