
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

#dvLoading {
background:url(images/image_715235.gif) no-repeat center center;
height: 100px;
width: 100px;
position: fixed;
left: 50%;
top: 50%;
margin: -25px 0 0 -25px;
z-index: 1000;
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


<?php
require("trans menu.php"); //added mjun@
?> 




<ul id="navMenu" >
  <li><a href="#" class="bubble-float-bottom">Control Center Report</a>
  
  	<ul>
		<li><a href='incident report.php' id='dr'>Daily Report</a></li>		
		<li><a href='edit_ccdr.php' id='dr1' >Edit CCDR</a></li>
		<li><a href='ccdr_summary.php' id='dr2'>CCDR Summary</a></li>
		<li><a href='incident summary.php' id='dr3'>View Daily Incident Summary</a></li>		
		
	</ul>
	</li>
		<li><a class="bubble-float-bottom" href='#'>Train</a>	
	<ul>
		<li><a style='text-decoration:none;' href='train_availability.php'>Train Availability</a></li>  
		<li><a style='text-decoration:none;' href='train hourly.php'>Train Hourly Monitoring Report</a></li>
		<li><a style='text-decoration:none;' href='onboard equipment.php'>Onboard Equipment and Accessories</a></li>
	</ul>
	</li>	
		<li><a href='clearance form.php' id='dr'>Clearance Form</a></li>
		<li><a class="bubble-float-bottom" href='#' >Statistics Report</a>
		<ul>
			<li><a href='#' onclick="window.open('statistics_report_modified.php')">Train Equipment</a></li>
			<li><a href='#' onclick="window.open('car_statistics_report.php')">Rolling Stock (Cars)</a></li>
			<li><a href='#' onclick="window.open('td_history.php')">Personnel</a></li>
			<li><a href='#' onclick="window.open('other_history.php')">Other Incidents</a></li>
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
	<li><a class="bubble-float-bottom" href='#'>Transport</a>	
	<ul>
		<li><a style='text-decoration:none;' href='indexAdd.php'>Transport Employees</a></li>  
		<li><a style='text-decoration:none;' href='UserAdd.php'>Transport Users</a></li>	
		<li><a style='text-decoration:none;' href='signatories_list.php'>Signatories</a></li>	
	</ul>	
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