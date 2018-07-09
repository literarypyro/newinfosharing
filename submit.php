<?php
session_start();
?>
<?php
    require('db_page.php');
	if($_SESSION['page']=="submit.php"){
		require("functions/user functions.php");
		if(isset($_POST['username'])) {
			$db=retrieveUsersDb(); 
			if(isset($_POST['username'])){
				$sql="select * from users where username='".$_POST['username']."' and password='".$_POST['password']."'";
				$rs=$db->query($sql);
				$nm=$rs->num_rows;
			
				if($nm>0){
					if(checkLog($_POST['username'],$db)>0){
						echo "Security error. User has already logged in.";	
						echo '<meta http-equiv="refresh" content="3;url=login.php" />';
					}	
					else {
						$row=$rs->fetch_assoc();	
						$_SESSION['name']=$row['lastName'].", ".$row['firstName'];
						$_SESSION['username']=$row['username'];
						$_SESSION['department']=$row['deptCode'];
				
						header("Location: transport/index.php");						

					}									
				}
				else {
					echo "Invalid login credentials.  Please check your password or create new account.";	
					echo '<meta http-equiv="refresh" content="3;url=index.php" />';					
				}
			}
			
			
		}
		
		
	
	}
		
	
?>
<!--
Program: CCDR
-->
