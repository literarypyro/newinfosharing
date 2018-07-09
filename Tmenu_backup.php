
<?php
require("trans menu.php"); //added mjun@
?>

<style type='text/css'>


/*--- Menu --- */

ul#navMenu {
left: 200px;
padding:0px; 
/* width:1020px; */
width:100%;
list-style:none;
position:relative
}

ul#navMenu ul {
position:absolute;
left:0; 
top:100%;
display:none;
padding:0px;
margin:0px
}

ul#navMenu li {
display:inline;
float:left;
position:relative
}

ul#navMenu a {
text-decoration:none;
padding:10px 0px; 
width:200px;
background:#f5f5f5;
color:black;
border:1px solid #FBCC2A;
float:left;
text-align:center;
font-family: Verdana, sans-serif;
border-radius: 4px
}

ul#navMenu a:hover {
background:#cccccc;
color:#333333
}

ul#navMenu li:hover ul {
display:block;
}

*/
/* -- width box match navmenu a */

ul#navMenu ul a {
width:200px;
}

ul#navMenu ul li {
display:block;
margin:0px
}

ul#navMenu ul ul {
top:0;left:100%;
}

ul#navMenu li:hover ul ul {
display:none;
}

ul#navMenu ul li:hover ul {
display:block;
}


 /* Menu end */
 /* a.ex1:hover,a.ex1:active {color:red;} */
/*
a.ex2:active {
    display: inline-block;
    color: #03c;
    -webkit-transition: 0.5s;
    -moz-transition: 0.5s;
    -o-transition: 0.5s;
    -ms-transition: 0.5s;
    transition: 0.5s;
}
 
a.ex2:hover {
    -webkit-transform: rotate(10deg);
    -moz-transform: rotate(10deg);
    -o-transform: rotate(10deg);
    -ms-transform: rotate(10deg);
    transform: rotate(10deg);
}
 */
 


</style>

<script type="text/javascript" src="js/jquery-1.10.2.min.js"</script>

<script language='javascript' src='ajax.js'></script>



<link href="dist/css/hover.css" rel="stylesheet" media="all">
<link href="dist/hover-min.css" rel="stylesheet" media="all">

<ul id="navMenu" >
  <li><a href="#" class="bubble-float-bottom">Control Center Report</a>
  	<ul>
		<li><a href='incident report.php'>Daily Report</a></li>
		<li><a href='edit_ccdr.php'>Edit CCDR</a></li>
		<li><a href='ccdr_summary.php'>CCDR Summary</a></li>
		<li><a href='incident summary.php'>View Daily Incident Summary</a></li>		
		
	</ul>
	</li>
		<li><a class="bubble-float-bottom" href='#'>Train</a>	
	<ul>
		<li><a style='text-decoration:none;' href='train_availability.php'>Train Availability</a></li>  
		<li><a style='text-decoration:none;' href='train hourly.php'>Train Hourly Monitoring Report</a></li>
		<li><a style='text-decoration:none;' href='onboard equipment.php'>Onboard Equipment and Accessories</a></li>
	</ul>
	</li>	
		<li><a href='clearance form.php'>Clearance Form</a></li>
		<li><a class="bubble-float-bottom" href='#' >Statistics Report</a>
		<ul>
			<li><a href='#' onclick="window.open('statistics_report_modified.php')">Train Equipment</a></li>
			<li><a href='#' onclick="window.open('car_statistics_report.php')">Rolling Stock (Cars)</a></li>
			<li><a href='#' onclick="window.open('td_history.php')">Train Driver History</a></li>

			<li><a class="bubble-float-right" href='#' >Stats Report(AFC)</a>
			<ul>			
        		<?php 
				$db=new mysqli("localhost","root","","transport");
			
				$sql="select * from station";
				$rs=$db->query($sql);
				$nm=$rs->num_rows;
			
				for($i=0;$i<$nm;$i++){
					$row=$rs->fetch_assoc();
				?>
				<li ><a href='#'  onclick="window.open('statistics_report_afc.php?station=<?php echo $row['id']; ?>&station_name=<?php echo $row['station_name']; ?>')"><?php echo $row['station_name']; ?></a> </li>
				<?php
				}
				?>
	<li><a href='#' onclick="window.open('statistics_report_afc.php?station=D&station_name=Depot')">Depot</a></li>
			</ul>
			</li>		
		</li>		
</ul>
	<li><a href='indexAdd.php'>Transport Employees</a></li>
</ul>