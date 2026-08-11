<?php


$ISS_DB_HOST = "localhost";
$ISS_DB_USER = "psssilva";
$ISS_DB_PASS = "!D40nkC2azXg$";

$db=new mysqli($ISS_DB_HOST,$ISS_DB_USER,$ISS_DB_PASS,"is_transport");


$sql="select * from equipment where id in ('114','102','110','11','113','104','108','109','103','124','67','111','112','105','81','118','119','64','115','89','120','123','121','116','2','122','117','105','81','118','119','64','115','89','120','123','121','116','2','122','117') order by equipment_name";
$rs=$db->query($sql);
$nm=$rs->num_rows;
	$stamp="";

for($i=0;$i<$nm;$i++){
	$row=$rs->fetch_assoc();
	if($i==0){
		$stamp.=$row['equipment_name'];
		
	}
	else {
		$stamp.=",".$row['equipment_name'];
		
	}
	
}
echo $stamp;

?>