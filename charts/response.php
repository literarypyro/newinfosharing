<?php

$con=mysqli_connect('localhost','root','','transport');
if (mysqli_connect_errno())
  {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  }
$query = 'select * from `equipment`';
$result = mysqli_query($con,$query);
while($row = mysqli_fetch_array($result)){
    $EquipmentS[$row['id']] = $row['equipment_name'];
}
$EquipmentS[''] = 'Uncategorized';
$EquipmentS['others'] = 'Others';
$query = 'select count(equipt) as `TotalEquipmentCount`,`equipt` from `incident_report`  group by `equipt`';
$result = mysqli_query($con,$query);
while($row = mysqli_fetch_array($result)){
    if($row['equipt']!=""){
        $JSON[] =   array(
                                'Equipment'=>$EquipmentS[$row['equipt']],
                                'Count'=>$row['TotalEquipmentCount']
                            );
    }
}
mysqli_close($con);
/*
echo '<pre>';
print_r($JSON);
echo '</pre>';
*/
echo json_encode($JSON);
?>
