<!--- Modified by Jun
//--- Date: 7/30/2014
//--- Modify: Create new menu layout to comply to the added options AFC 
//--  Change monitoring menu with this one 
//--- Marker: @jun
//--------------------------------------------------->
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {session_start();}
$ULev=$_SESSION['Ulevel'];
?>

<!-- Start --> 
<style type='text/css'>

.box h2 {
text-align:left;
line-height:80px;
}

.box {
width:100%;
height:150px;
background:#f5f5f5;
border: 1px solid #e3e3e3;
border-radius: 4px;

/* margin:60px 20px; 
margin:2px 5px; */
}

.effect1 {
-webkit-box-shadow:0 10px 6px -6px #777;
-moz-box-shadow:0 10px 6px -6px #777;
box-shadow:0 10px 6px -6px #777;
}

.one_heading {
	border-bottom: 1px solid #FBCC2A; 

}

.exception {
	border-bottom: 1px solid #FBCC2A; 
}

h2.except {
	color: #444; 
	font: 20px Verdana; 
	-webkit-font-smoothing: antialiased; 
	text-shadow: 0px 1px black; 
	margin: 10px 0 2px 0; 
	padding: 5px 0 6px 0; 
}

/*
.exception td {
	border:1px solid black; 
	
a:link { color: green; text-decoration: none }
a:active { color: yellow; text-decoration: none }
a:visited { color: green; text-decoration: none }
a:hover { color: orange; text-decoration: underline; font-weight: bold}
*/

/* a.ex1:hover,a.ex1:active {color:red;} */

a.ex1:link {
     color: black;
    -webkit-transition: 0.5s;
     -moz-transition: 0.5s;
     -o-transition: 0.5s;
     -ms-transition: 0.5s;
     transition: 0.5s;
     font-weight: bold
}
 
a.ex1:hover {
     color: #fff;
    text-shadow: -1px 1px 5px #03c, 1px -1px 5px black;
}

h0 {color: #f5f5f5; text-shadow: black 0.1em 0.1em 0.2em; font: 55px 'Buxton Sketch'}

/*
h1 {
	text-shadow: -1px -1px 1px #fff, 1px 1px 1px #000;
	color: #f5f5f5;
	opacity: 0.6;
	font: 60px 'Museo700';
}
*/			
</style>



<div class="box effect1">
<table class='exception' width=100%>
<tr>
<td width=5%>
<img src='mrt-logo.png' align="center" valign="center" width="100%" height="100" />
</td><td valign="middle" width=55%><h0>Control Center Operation</h0></td>

<!-- </td><td valign="center" width=55%><font face="Century" size="5"><h1><b>Control Center Operation</b></h1></font> 
</td> -->
<!--
<td width=40% align=right valign=center>
<font style='font-size:25px;'><b><?php echo strtoupper($row['department_name']); ?></b></font>
</td> 
-->
</tr>
</table>	

<table width=100%>
	<tr>	
		<td>
			<a class="ex1 grow" href='../index.php'><font face="Century" size="4">Log Out</font></a> 
			<!-- <a class="float" href='../index.php'><font face="Century" size="4">Log Out</font></a> -->
		</td>
		
		<td style='' align="right" width=50%>	
<font style='font-size:20px;'><b>
	<?php echo "Hello, ".$_SESSION['name']."!"; ?>
	</b></font></td>
	</tr>
	
</table>
</div>


