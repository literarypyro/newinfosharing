<?php
session_start();
?>
<?php
//header("Location: duty_personnel.php");
if($_SESSION['username']=="demo2"){
header("Location: train_operations_parallel.php");

}
else {
header("Location: train_operations.php");
}
?>
