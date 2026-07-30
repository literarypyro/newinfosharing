<?php
function genericized_function(){


if(isset($_POST['sort_by'])){
	if($_POST['sort_by']==""){
	
	}
	else {
		if($_POST['sort_by']=="level ascending"){
			$clause=" order by level asc";
		
		}
		else if($_POST['sort_by']=="1"){
			$clause=" and level='1'".$clause;
		}
		else if($_POST['sort_by']=="2"){
			$clause=" and level='2'".$clause;
		}
		else if($_POST['sort_by']=="3"){
			$clause=" and level='3'".$clause;
		}
		else if($_POST['sort_by']=="4"){
			$clause=" and level='4'".$clause;
		}
	
	}


}


}
?>