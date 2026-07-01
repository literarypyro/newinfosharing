<?php
require("trans menu_2.php"); //added mjun@
?> 
<style type='text/css'>

/*--- Menu --- */
table {
	border-collapse:collapse;
}
ul#navMenu {
	display: flex;
	flex-wrap: wrap;
	gap: 4px;
	padding: 6px 16px;
	margin: 0;
	width: auto;
	list-style: none;
	position: static;
	background: #013E76;
	border-top: 1px solid rgba(255,255,255,0.15);
}

ul#navMenu ul {
	position: absolute;
	left: 0;
	top: 100%;
	display: none;
	padding: 4px 0;
	margin: 0;
	background: #fff;
	border: 1px solid #D2DDEA;
	border-radius: 4px;
	box-shadow: 0 4px 12px rgba(0,30,80,.15);
	min-width: 200px;
	z-index: 10;
}

ul#navMenu li {
	display: block;
	float: none;
	position: relative;
}

ul#navMenu > li > a {
	text-decoration: none;
	padding: 6px 12px;
	width: auto;
	background: transparent;
	color: #fff;
	border: none;
	float: none;
	display: inline-block;
	text-align: left;
	font-family: "Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif;
	font-size: 13px;
	border-radius: 4px
}

ul#navMenu > li > a:hover {
	background: rgba(255,255,255,0.14);
	color: #fff
}

ul#navMenu li:hover ul {
	display:block;
}

/* -- dropdown submenu items: fill the dropdown card, dark text on white -- */

ul#navMenu ul a {
	width: auto;
	background: transparent;
	color: #16243B;
	border: none;
	float: none;
	display: block;
	text-align: left;
	padding: 7px 14px;
	border-radius: 0;
}

ul#navMenu ul a:hover {
	background: #EEF4FB;
	color: #00529B;
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
  
/**  try out*/ 
/* loading spinner 
body { height: 100%; overflow: hidden; }

.no-js #loading-screen, 
.no-js #pulsing-circle-container,
.no-js #pulsing-circle { display: none;  }

#loading-fallback { display: none; }

.no-cssanimations #pulsing-circle { background-color: none; }
.no-cssanimations #loading-fallback { display: block; }


#loading-screen {
  background: #fff;
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  height: 100%;
  width: 100%;
  z-index: 999;
}

#pulsing-circle-container {
  display: block; 
  position: absolute; 
  margin: 0 auto; 
  top: 0; 
  left: 0;
  bottom: 0;
  right: 0; 
  width: 100px;
  height: 100px;
}

#pulsing-circle {
  width: 100%;
  height: 100%;
  margin: 100px auto;
  background-color: gray;
  border-radius: 100%;  
  -webkit-animation: scaleout 1.0s infinite ease-in-out;
  animation: scaleout 1.0s infinite ease-in-out;
}

@-webkit-keyframes scaleout {
  0% { -webkit-transform: scale(0.0) }
  100% {
    -webkit-transform: scale(1.0);
    opacity: 0;
  }
}

@keyframes scaleout {
  0% { 
    transform: scale(0.0);
    -webkit-transform: scale(0.0);
  } 100% {
    transform: scale(1.0);
    -webkit-transform: scale(1.0);
    opacity: 0;
  }
}
*/

/* Replaced the old images/image_715235.gif (read as a nuclear/radiation
   alert icon) with a plain CSS ring spinner in the console's own blue/
   gold palette -- no external image dependency, and since this lives in
   the one shared Tmenu_2.php, every page picks up the same neutral
   spinner automatically. */
#dvLoading {
	background: none;
	height: 46px;
	width: 46px;
	position: fixed;
	left: 50%;
	top: 50%;
	margin: -23px 0 0 -23px;
	z-index: 1000;
	border: 4px solid rgba(0,82,155,0.15);
	border-top-color: #00529B;
	border-radius: 50%;
	animation: ta-spin 0.8s linear infinite;
}
@keyframes ta-spin {
	to { transform: rotate(360deg); }
}

</style>

<!--
 <script src="nprogress-master/nprogress.js"></script>
 -->

<script type="text/javascript" src="js/jquery-1.10.2.min.js"</script>

<script language='javascript' src='ajax.js'></script>
<script language='javascript'>


  $(window).load(function() {
/*     $("#loading-spinner").fadeOut(); 
    $('#loading-screen').delay(350).fadeOut('slow'); 
     $('body').delay(350).css({'overflow':'visible'});  */
     
     $('#dvLoading').delay(350).fadeOut('slow'); 
  });
  
  /*
  $(window).bind("load", function() {
    $('#dvLoading').fadeOut(1500);
 });
  */
  
        
</script>


 
<!--
<div id="loading-screen">
	<div id="pulsing-circle-container">
        <div id="pulsing-circle"><img scr="images/sprites.gif" id="loading-fallback">            
        </div>        
        <div id="dvLoading"></div>
	</div>
</div>

-->
<div id="dvLoading"></div>

<link href="dist/css/hover.css" rel="stylesheet" media="all">
<link href="dist/hover-min.css" rel="stylesheet" media="all">

<!-- The Stylesheets -->
    <!--    <link href="nprogress-master/nprogress.css" rel="stylesheet" /> -->


<ul id="navMenu" >
  <li><a href="#">Control Center Report</a>
  
  	<ul>
		<li><a href='incident report.php' id='dr'>Daily Report</a></li>		
		<li><a href='edit_ccdr.php' id='dr1' >Edit CCDR</a></li>
		<li><a href='ccdr_summary.php' id='dr2'>CCDR Summary</a></li>
		<li><a href='incident summary.php' id='dr3'>View Daily Incident Summary</a></li>		
		
	</ul>
	</li>
		<li><a href='#'>Train</a>	
	<ul>
		<li><a style='text-decoration:none;' href='train_availability.php'>Train Availability</a></li>  
		<li><a style='text-decoration:none;' href='train hourly.php'>Train Hourly Monitoring Report</a></li>
		<li><a style='text-decoration:none;' href='onboard equipment.php'>Onboard Equipment and Accessories</a></li>
		<li><a style='text-decoration:none;' href='depot_insertion.php'>Depot Insertion Program</a></li>

	</ul>
	</li>	
		<li><a href='clearance form.php' id='dr'>Clearance Form</a></li>
		<li><a href='#'>Statistics Report</a>
		<ul>
			<li><a href='#' onclick="window.open('td_history.php')">Personnel</a></li>
			<li><a href='#'>Problem Type</a>
			<ul>			
        		<?php 
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
			
				$sql="select * from equipment_type where equipment_name not in ('OTHERS') order by equipment_name";
				$rs=$db->query($sql);
				$nm=$rs->num_rows;
			
				for($i=0;$i<$nm;$i++){
					$row=$rs->fetch_assoc();
				?>
				<li ><a href='#'  onclick="window.open('problem_history.php?problem=<?php echo $row['equipment_code']; ?>')"><?php echo $row['equipment_name']; ?></a> </li>
				<?php
				}

				$sql="select * from equipment_type where equipment_name in ('OTHERS') order by equipment_name";
				$rs=$db->query($sql);
				$nm=$rs->num_rows;
			
				for($i=0;$i<$nm;$i++){
					$row=$rs->fetch_assoc();
				?>
				<li ><a href='#'  onclick="window.open('problem_history.php?problem=<?php echo $row['equipment_code']; ?>')"><?php echo $row['equipment_name']; ?></a> </li>
				<?php
				}
				?>

			
			</ul>
			</li>

			<li><a href='#' onclick="window.open('car_statistics_report.php')">Rolling Stock (Cars)</a></li>

			
			<li><a href='#'>Stats Report(AFC)</a>
			<ul>			
        		<?php 
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
			
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
			<li><a href='#' onclick="window.open('statistics_report_modified.php')">Train Equipment</a></li>

			<li><a href='#' onclick="window.open('other_history.php')">Other Incidents</a></li>

			</li>		
</ul>
	<li><a href='#'>Transport</a>	
	<ul>
		<li><a style='text-decoration:none;' href='indexAdd.php'>Transport Employees</a></li>  
		<li><a style='text-decoration:none;' href='UserAdd.php'>Transport Users</a></li>	
		<li><a style='text-decoration:none;' href='signatories_list.php'>Signatories</a></li>	
		<li><a style='text-decoration:none;' href='equipment_list.php'>Equipment</a></li>	
		<li><a style='text-decoration:none;' href='details_list.php'>Preencoded Details</a></li>	

	</ul>	
	</li>
</ul>

<!--
<script language='javascript' src='ajax.js'></script>
<script language='javascript'>

	// Quick Load

	$(function(){	
	
	$("#dr").click(function() { NProgress.start();  });   
	$("#dr1").click(function() { NProgress.start(); });
	$("#dr2").click(function() { NProgress.start(); });
	$("#dr3").click(function() { NProgress.start();
	setTimeout(function() { NProgress.done(); $('.fade').removeClass('out'); }, 1000); });  
	});
	
</script>
-->