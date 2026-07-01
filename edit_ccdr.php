<?php
require("Tmenu.php");
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
	$db2=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_external");
global $Mup;
?>
<!---- Modified: Jun
 Date: July 11, 2014
 Modify: Updating of fields
 Marks: Mjun@
 ----->
 
<!-- php -->

<?php
function getOrdinal($number){
$ends = array('th','st','nd','rd','th','th','th','th','th','th');
if (($number %100) >= 11 && ($number%100) <= 13)
   $abbreviation = $number. 'th';
else
   $abbreviation = $number. $ends[$number % 10];

   
 return $abbreviation;  

}
?>

<!--  http://faulknercs.github.io/Knockstrap/#alert sample 

<script src="dist/js/knockout-bootstrap.min.js"></script>
<link href="dist/css/bootstrap.css" rel="stylesheet">
<div data-bind="alerts">
    <div data-bind="alert: $data"></div>
</div>

<script src="jquery-1.11.1.js"></script>
<script src="Freeow/jquery.freeow.js"></script>
<script src="Freeow/jquery.freeow.min.js"></script>
<link href="Freeow/style/freeow/freeow.css" rel="stylesheet"/>

-->
<link href="css/modal_only.css" rel="stylesheet" />
<script src="jquery-1.11.1.js"></script>
<script src="Freeow/jquery.freeow.js"></script>
<script src="Freeow/jquery.freeow.min.js"></script>
<link href="Freeow/style/freeow/freeow.css" rel="stylesheet"/>

<script language='javascript' src='ajax.js'></script>
<script language='javascript'>
function openLink(){
	window.open("link_incident.php","_blank");


}
function addCoordinate(){
	var coordinate=document.getElementById('dotc_coordinated').value;
	var remarksValue=document.getElementById('dotc').value;
	var additional="";
	
	if(coordinate=="c_with"){
		additional="Coordinated with "+document.getElementById('coordinated_to').value+".";
		
		
	}
	else if(coordinate=="c_to"){
		
		additional="Coordinated to "+document.getElementById('coordinated_to').value+".";
		
		
	}

	else if(coordinate=="reinitialize"){
		
		additional="Re-initialized, ok.";
		
		
	}
	else if(coordinate=="recorded"){
		
		additional="Recorded.";
		
		
	}
	
	document.getElementById('dotc').value=remarksValue+" "+additional;	

}

function fillEdit(elementName){
	var elementContents="";
	
	if(elementName=='dotc'){
		elementContents="<tr class='rowHeading'><td>&nbsp;</td><td>Edit Action Taken</td></tr>";
		elementContents+="<tr><th width=20%>DOTC</th><td><span name='remarks_space' id='remarks_space'><textarea rows=5 cols=50 name='dotc' id='dotc'></textarea></span>";
//		elementContents+="<input type=checkbox name='remarks_check' id='remarks_check' onclick='setPreset(this)' /><font color=blue>Preset Values</font>";

		elementContents+="<br>";
		elementContents+="<select name='dotc_coordinated' id='dotc_coordinated'>";
		elementContents+="<option value='c_with'>Coordinated with</option>";
		elementContents+="<option value='c_to'>Coordinated to</option>";
		elementContents+="<option value='reinitialize'>Re-initialized</option>";
		elementContents+="<option value='recorded'>Recorded</option>";

		elementContents+="</select>";




		elementContents+="<input style='border: 1px solid gray' type=text name='coordinated_to' id='coordinated_to' /><input type=button value='Add' onclick='addCoordinate()' />";
		
		
		elementContents+="</td></tr>";
			
		
		document.getElementById('fieldType').value='dotc';
	}
	else if(elementName=="maintenance"){
		elementContents="<tr class='rowHeading'><td>&nbsp;</td><td>Edit Action Taken</td></tr>";

		elementContents+="<tr><th width=20%>Maintenance Provider</th><td><textarea rows=5 cols=50 name='maintenance_provider'></textarea></td></tr>";
		document.getElementById('fieldType').value='maintenance';
	
	}
	else if(elementName=="level"){
		elementContents="<tr class='rowHeading'><td>&nbsp;</td><td>Edit CCDR Details</td></tr>";
		elementContents+="<tr><th width=20%>Level</th>";
		elementContents+="<td>";
		elementContents+="<select name='level' onchange='getLevel(this)'>";
//		elementContents+="<select name='level' onchange='enterOrder(this.value)'>";
		elementContents+="<option value='1'>1</option>";
		elementContents+="<option value='2'>2</option>";
		elementContents+="<option value='3'>3</option>";
		elementContents+="<option value='4'>4</option>";
		elementContents+="</select>";
		elementContents+="<span name='condition_html' id='condition_html'></span>";

		//		elementContents+="<span name='order_space' id='order_space'></span>";
		elementContents+="</td></tr>";
		document.getElementById('fieldType').value='level';

	}
	else if(elementName=="description"){
		elementContents="<tr class='rowHeading'><td>&nbsp;</td><td>Edit CCDR Details</td></tr>";
		elementContents+="<tr><th width=20%>Description</th><td><textarea rows=5 cols=50 name='description'></textarea></td></tr>";
		document.getElementById('fieldType').value='description';

	}
	else if(elementName=="onboard_equipt"){
	
/*		elementContents="<tr class='rowHeading'><td>&nbsp;</td><td>Edit CCDR Details</td></tr>";
		var equipmentHTML=document.getElementById('equipment_copy').innerHTML;
		elementContents+="<tr><th width=20%>On-Board Equipment/Accessories</th><td><select name='onboard_equipt'>"+equipmentHTML+"</select></td></tr>";
		document.getElementById('fieldType').value='onboard_equipt';
*/		
	}
	else if(elementName=="duration"){
		elementContents="<tr class='rowHeading'><td>&nbsp;</td><td>Edit CCDR Details</td></tr>";
		elementContents+="<tr><th width=20%>Incident Duration</th><td><input type=text name='duration' /></td></tr>";
		document.getElementById('fieldType').value='duration';


	}
	/*
	else if(elementName=="incident_no"){
		elementContents="<tr class='rowHeading'><td>&nbsp;</td><td>Edit CCDR Details</td></tr>";
//		elementContents+="<tr><th width=20%>Incident Number</th><td><input type=text name='incident_number' /></td></tr>";
		document.getElementById('fieldType').value='incident_no';


	}
	*/
	else if(elementName=="link_incident"){
		elementContents="<tr class='rowHeading'><td>&nbsp;</td><td>Edit CCDR Details</td></tr>";
		elementContents+="<tr><th width=20%>Linked Incident Number</th>";
		elementContents+="<td>";
		elementContents+="<input type='text' name='incident_no_link' id='incident_no_link' />";
		elementContents+="<input type='hidden' name='incident_link' id='incident_link' />";
		elementContents+="<input type=button value='Link Incident' onclick='openLink()' />";
		elementContents+="</td></tr>";
		document.getElementById('fieldType').value='linked_to';
	
	}	
	else if(elementName=="date"){
		elementContents="<tr class='rowHeading'><td>&nbsp;</td><td>Edit CCDR Details</td></tr>";
	
		var dateHTML=setDate();
//		var dateHTML=document.getElementById('dateStamp').innerHTML;
		elementContents+="<tr><th width=20%>Date/Time</th><td>"+dateHTML+"</td></tr>";
		document.getElementById('fieldType').value='date';
		
	}
	else if(elementName=="problem"){
		elementContents="<tr class='rowHeading'><td>&nbsp;</td><td>Edit CCDR Details</td></tr>";
		elementContents+="<tr><th width=20%>Type of Problem</th>";
		
		elementContents+="<td>";
		elementContents+="<select name='type' id='type' onchange='getCategory(this.value)'>";
		elementContents+="<option value='cc_equipt'>CC Equipment</option>";
		elementContents+="<option value='communication'>Communication</option>";
		elementContents+="<option value='depot_equipt'>Depot Equipment</option>";
		elementContents+="<option value='power'>Power</option>";
		elementContents+="<option value='rolling'>Rolling Stock</option>";
		elementContents+="<option value='signaling'>Signaling</option>";
		elementContents+="<option value='tracks'>Tracks</option>";
		elementContents+="<option value='gradual'>Gradual Removal</option>";
		elementContents+="<option value='c_loops'>Cancelled Loops; Acc. Delay/Failure</option>";
		elementContents+="<option value='unload'>Unloading of Passengers</option>";
		elementContents+="<option value='nload'>Not Loading</option>";


		//		elementContents+="<option value='ser_int'>Service Interruption</option>";
		elementContents+="<option value='others'>Others</option>";

		elementContents+="</select>";

//		elementContents+="<span id='rolling_category' name='rolling_category'>";
		
//		elementContents+="</span>";
		
		elementContents+="</td></tr>";
		
		document.getElementById('fieldType').value='problem';
	}
	else if(elementName=="index"){
		elementContents="<tr class='rowHeading'><td>&nbsp;</td><td>Edit CCDR Details</td></tr>";
		elementContents+="<tr><th width=20%>Index/Car No.</th>";

		elementContents+="<td>";
		elementContents+="<input type='text' name='index_id' id='index_id' size=5 /> / ";

		elementContents+="<span id='car_space' name='car_space'></span>";	

		
		elementContents+="</td></tr>";

		document.getElementById('fieldType').value='index';

		
	}
	else if(elementName=="location"){
		
		elementContents="<tr class='rowHeading'><td>&nbsp;</td><td>Edit CCDR Details</td></tr>";
		elementContents+="<tr><th width=20%>Location/Direction</th>";

		elementContents+="<td>";

		elementContents+="<select name='direction' id='direction' onchange='setDirection(this.value)'>";
		elementContents+="<option></option>";
		elementContents+="<option value='S'>Station</option>";
		elementContents+="<option value='D'>Depot</option>";
		elementContents+="<option value='ML'>Mainline</option>";

		elementContents+="<option value='CC'>Control Center</option>";

		elementContents+="<option value='NB'>Northbound</option>";
		elementContents+="<option value='SB'>Southbound</option>";
		elementContents+="<option value='NTB'>North Turnback</option>";
		elementContents+="<option value='IR'>Insertion/Removal Area</option>";
		elementContents+="<option value='SPT'>Shaw Pocket Track</option>";
		elementContents+="<option value='TPT'>Taft Pocket Track</option>";


		elementContents+="</select>";
		
		elementContents+=" ";
		elementContents+="<input type='text' size=5 name='location' id='location' />";
		
		elementContents+="</td></tr>";
		
		document.getElementById('fieldType').value='location';
		
		
	}
	else if(elementName=="recommend_approval"){
		
		elementContents="<tr class='rowHeading'><td>&nbsp;</td><td>Edit Reporting Details</td></tr>";
		elementContents+="<tr><th width=20%>Recommend Approval</th>";
		elementContents+="<td>";
		elementContents+="<input type=text name='recommend_approval' id='recommend_approval' />";

		elementContents+="</td></tr>";

		
		document.getElementById('fieldType').value='recommend_approval';
		
		
	}
	else if(elementName=="approving_officer"){
		
		elementContents="<tr class='rowHeading'><td>&nbsp;</td><td>Edit Reporting Details</td></tr>";
		elementContents+="<tr><th width=20%>Approving Officer</th>";
		elementContents+="<td>";
		elementContents+="<input type=text name='approving_officer' id='approving_officer' />";

		elementContents+="</td></tr>";

		
		document.getElementById('fieldType').value='approving_officer';
		
		
	}


	else if(elementName=="reported_by"){

		elementContents="<tr class='rowHeading'><td>&nbsp;</td><td>Edit Reporting Details</td></tr>";
		elementContents+="<tr><th width=20%>Reported By</th>";
		elementContents+="<td>";
		elementContents+="<input type=text name='reported_by' id='reported_by' />";

		elementContents+="</td></tr>";

		document.getElementById('fieldType').value='reported_by';
	
	}
	else if(elementName=="received_by"){
		elementContents="<tr class='rowHeading'><td>&nbsp;</td><td>Edit Reporting Details</td></tr>";
		elementContents+="<tr><th width=20%>Reported By</th>";
		elementContents+="<td>";
		elementContents+="<span name='receive_space' id='receive_space'> </span>";

		elementContents+="</td></tr>";

		document.getElementById('fieldType').value='received_by';
	
	}
	else if(elementName=="cancel"){
		elementContents="<tr class='rowHeading'><td>&nbsp;</td><td>Edit Incident Details</td></tr>";
		elementContents+="<tr><th width=20%>Cancelled Loops</th>";
		elementContents+="<td>";
		
		
		elementContents+="<select name='cancel' id='cancel' onchange='getMore(this.value)'>";
		elementContents+="<option value='none'>0</option>";
		elementContents+="<option value='whole'>1</option>";
		elementContents+="<option value='half'>1/2</option>";
		elementContents+="<option value='more'> more than 1</option>";
		elementContents+="</select>";
		elementContents+="<input type=text name='cancel_more' id='cancel_more' size=5 style='border:1px solid gray' disabled />";		
		
		elementContents+="</td></tr>";

		document.getElementById('fieldType').value='cancelled';
	
	
	
	}
	else if(elementName=="incident_no"){
		elementContents="<tr class='rowHeading'><td>&nbsp;</td><td>Edit Incident Number</td></tr>";
		elementContents+="<tr><th width=20%>Incident Number</th>";
		elementContents+="<td>";
		elementContents+="<input type='text' name='incident_digit'/>";
		
		elementContents+="<select name='incident_suffix'>";
		elementContents+="<option value=''></option>";
		elementContents+="<option value='RS'>RS</option>";
		elementContents+="<option value='SEQ'>SEQ</option>";
		elementContents+="<option value='SIG'>SIG</option>";
		elementContents+="<option value='PWR'>PWR</option>";
		elementContents+="<option value='AFC'>AFC</option>";
		elementContents+="<option value='DEQ'>DEQ</option>";
		elementContents+="<option value='COM'>COM</option>";
		elementContents+="<option value='TRK'>TRK</option>";
		elementContents+="<option value='CEQ'>CEQ</option>";
		elementContents+="<option value='OTR'>OTR</option>";
		elementContents+="</select>";
		
		elementContents+="</td></tr>";

		document.getElementById('fieldType').value='incident_no';
	
		
		
	}
	else if(elementName=="additional_defects"){
		var multipleTable="<table name='multi_list' id='multi_list' width=80%>";
		
		
		
		multipleTable+="</table>";
		var multipleTable2="<a href='#' onclick=\"window.open('multiple_defects.php?problemType=RS')\">Update</a>";	
		


		elementContents="<tr><td colspan=2>"+multipleTable+"</td></tr>";
		elementContents+="<tr><td colspan=2>"+multipleTable2+"</td></tr>";

		document.getElementById('fieldType').value='additional_defects';
		
	}
	
	
	document.getElementById('edit_table').innerHTML=elementContents;
	
	
	if(elementName=="index"){

		var incident_id=document.getElementById('incident_report').value;

		makeajax("processing.php?getCars="+incident_id,"fillCars");	

		
		
	}
	else if(elementName=="received_by"){
	
		makeajax("processing.php?supervisor=Y","fillSuper");	
	
	}
	else if(elementName=="additional_defects"){
		var incident_id=document.getElementById('incident_report').value;
		makeajax("processing.php?debugDefects="+incident_id,"okayDefects");	
	
	}
	
	
	$('#addModal').modal('show');
}

function okayDefects(ajaxHTML){
	
	retrieveDefects();


}

function setPreset(check){
	var remarksHTML="";
	
	if(check.checked){
		remarksHTML="<select name='dotc_coordinated' id='dotc_coordinated'>";
		remarksHTML+="<option>Coordinated with</option>";
		remarksHTML+="<option>Coordinated to</option>";
		remarksHTML+="</select>";
		remarksHTML+="<input style='border: 1px solid gray' type=text name='coordinated_to' id='coordinated_to' />";
	}
	else {
		remarksHTML="<textarea rows=5 cols=50 name='dotc'></textarea>";
	
	}

	document.getElementById('remarks_space').innerHTML=remarksHTML;
}

function getMore(cancel){
	if(cancel=="more"){
		document.getElementById('cancel_more').disabled=false;
	}
	else {
		document.getElementById('cancel_more').disabled=true;
	}

}


function fillSuper(ajaxHTML){

	if(ajaxHTML=="None available"){
	}
	else {

		driverHTML="<select name='received_by' id='received_by'>";

		var driverTerms=ajaxHTML.split("==>");
		var count=(driverTerms.length)*1-1;
		
		for(var n=0;n<count;n++){
			var parts=driverTerms[n].split(";");
			driverHTML+="<option value='"+parts[0]+"'>";
			driverHTML+=parts[1];
			driverHTML+="</option>";
		
		}
		driverHTML+="</select>";
		
	}
	document.getElementById('receive_space').innerHTML=driverHTML;
	
}

function fillCars(ajaxHTML){
	var subHTML="";
	
	if(ajaxHTML=="No data available"){
	
	
	}
	else {
		var subItemTerms=ajaxHTML.split(";");
		var count=(subItemTerms.length)*1-1;


		var optionHTML="";
		for(var n=0;n<count;n++){
			optionHTML+="<option value='"+subItemTerms[n]+"'>";
			optionHTML+=subItemTerms[n];
			optionHTML+="</option>";
		
		}


		subHTML="<select id='car' name='car'>";
		subHTML+="<option></option>";
		subHTML+=optionHTML;
		subHTML+="</select>, ";


		subHTML+="<select id='car_2' name='car_2'>";
		subHTML+="<option></option>";
		subHTML+=optionHTML;
		subHTML+="</select>, ";

		subHTML+="<select id='car_3' name='car_3'>";
		subHTML+="<option></option>";
		subHTML+=optionHTML;
		subHTML+="</select>";
		
	}
	document.getElementById('car_space').innerHTML=subHTML;
	
}


function enterOrder(level){
	var orderHTML="";
	if((level==2)||(level==3)){
		orderHTML+="<input type='text' name='order' id='order' size=5 /> Order of Removal";
		
	}
	else {
		orderHTML="";
	
	}

	document.getElementById('order_space').innerHTML=orderHTML;
}


function getLevel(element){
	var level=element.value;
	var conditionHTML="";
	/*	if((level=="1")){
		document.getElementById('remove').className='removalnone';
		document.getElementById('order').disabled=true;
	}	
	else {
		document.getElementById('remove').className='removal';
		document.getElementById('order').disabled=false;
	}
*/
	if(level==3){
		conditionHTML+="<select name='condition'>";
		conditionHTML+="<option value='1'>Train is removed without replacement</option>";
		conditionHTML+="<option value='2'>Cancellation of loops and insertion</option>";
		conditionHTML+="</select>";
	}
	else if(level==4){
		conditionHTML+="<select name='condition'>";
		conditionHTML+="<option value='3'>Service interruption</option>";
		conditionHTML+="<option value='4'>Cancellation of loops. Ticket refunds.</option>";
		conditionHTML+="</select>";
	
	}
	document.getElementById('condition_html').innerHTML=conditionHTML;
}




function fillEquipt(problemType,equiptId){
	var elementName=problemType;
	
	if(elementName=="onboard_equipt"){
		
		
		
		elementContents="<tr class='rowHeading'><td>&nbsp;</td><td>Edit CCDR Details</td></tr>";
		elementContents+="<tr><th width=20%>On-Board Equipment/Accessories</th><td><span id='rolling_category' name='rolling_category'></span><span id='equipment_space' name='equipment_space'> <select name='equipment' id='equipment' onchange='fillSubItem(this.value)'></select></span> <span id='sub_item_space' name='sub_item_space' ></span></td></tr>";

		//var equipmentHTML=document.getElementById('equipment_copy').innerHTML;
		//elementContents+="<tr><th width=20%>On-Board Equipment/Accessories</th><td><select name='onboard_equipt'>"+equipmentHTML+"</select></td></tr>";

		document.getElementById('edit_table').innerHTML=elementContents;

		document.getElementById('fieldType').value='onboard_equipt';

//		if((equiptId=="rolling")||(equiptId=="power")){
		if(equiptId=="power"){

			getCategory(equiptId);
		}
		else if(equiptId=="rolling"){
			makeajax("processing.php?scrollRolling="+equiptId,"fillOnboard");		
		
		
		}		
		else if((equiptId=="cc_equipt")||(equiptId=="depot_equipt")){
			
			document.getElementById('equipment_space').innerHTML="<input type='text' name='equipment' id='equipment' />";
		}
		else if((equiptId=="gradual")||(equiptId=="ser_int")){
			document.getElementById('equipment_space').innerHTML="";
		}
		else if(equiptId=="others"){
			makeajax("processing.php?scrollOthers="+problemType,"fillOnboard");		
		}
	
		else {
			makeajax("processing.php?scrollRolling="+problemType,"fillOnboard");		
		}
	}
	$('#addModal').modal('show');

}

function fillItem(equiptId,categoryId){
	makeajax("processing.php?scrollRolling="+equiptId+"&category="+categoryId,"fillOnboard");	
	

}

function fillSubItem(equiptId){
	makeajax("processing.php?scrollSubItem="+equiptId,"subItem");	
	

}


function setDate(){
	var d=new Date();
	
	var year=d.getFullYear();
	var mmonth=d.getMonth()*1+1;
	var day=d.getDate();
	
	var tentativehour=d.getHours();
	var minute=d.getMinutes();
	var hour=0;
	
	var amorpm="AM";
	
	if(tentativehour==0){
		hour=12;
		
		amorpm="AM";
	
	}
	else {
		if(tentativehour>12){
			hour=tentativehour-12;
			amorpm="PM";
		}
		else {
			hour=tentativehour;
			amorpm="AM";
		}
	
	}
	
	
	
	dateHTML="<select name='month' id='month'>";

	for(var i=1;i<=12;i++){
		d=new Date(year+"-"+i+"-1");	
		var month="";

		switch(i){
			case 1: month='January'; break;
			case 2: month='February'; break;
			case 3: month='March'; break;
			case 4: month='April'; break;
			case 5: month='May'; break;
			case 6: month='June'; break;
			case 7: month='July'; break;
			case 8: month='August'; break;
			case 9: month='September'; break;
			case 10: month='October'; break;
			case 11: month='November'; break;
			case 12: month='December'; break;
		
		}
		
		dateHTML+="<option value='"+i+"' "; 
		
		if(mmonth==i){
		dateHTML+="selected";
		}
		dateHTML+=">";
		dateHTML+=month;
		dateHTML+="</option>";
		
	}
	dateHTML+="</select>";

	
	dateHTML+="<select name='day' id='day'>";
	for(var i=1;i<=31;i++){
		dateHTML+="<option value='"+i+"' ";
		if(day==i){
		dateHTML+="selected";
		}
		dateHTML+=">"+i+"</option>";
	}
	
	dateHTML+="</select>";

	yearLimit=year*1+16;
	dateHTML+="<select name='year' id='year'>";
	for(var i=1999;i<=yearLimit;i++){
		dateHTML+="<option value='"+i+"' ";
		if(year==i){
		dateHTML+="selected";
		}
		dateHTML+=">"+i+"</option>";
	}
	
	dateHTML+="</select>";
//	dateHTML+="<br>";
	dateHTML+="<select name='hour'>";
	
	for(var i=1;i<=12;i++){
		dateHTML+="<option value='"+i+"' ";
		if(hour==i){
		dateHTML+="selected";
		}
		dateHTML+=">"+i+"</option>";
	}
	
	
	
	dateHTML+="</select>";

	dateHTML+="<select name='minute'>";
	
	var label="";
	for(var i=0;i<=59;i++){
		
		if(i<10){
			label="0"+i;			
		}
		else {
			label=i;
		}
		
		dateHTML+="<option value='"+i+"' ";
		if(minute==i){
		dateHTML+="selected";
		}
		dateHTML+=">"+label+"</option>";

	}
	
	
	
	dateHTML+="</select>";
	dateHTML+="<select name='amorpm'>";
	dateHTML+="<option value='am' ";
	if(amorpm=="AM"){
		dateHTML+="selected";
	}
	dateHTML+=">AM</option>";

	dateHTML+="<option value='pm' ";
	if(amorpm=="PM"){
		dateHTML+="selected";
	}
	dateHTML+=">PM</option>";

	dateHTML+="</select>";
	
    return dateHTML;
	



	
}

function retrieveDefects(){
	makeajax("processing.php?retrieveAdditional=Y","getAdditional");	

}
function getAdditional(ajaxHTML){
	var subHTML="";
	
	if(ajaxHTML=="No data available"){
	
	
	}
	else {
		var subItemTerms=ajaxHTML.split(";");
		var count=(subItemTerms.length)*1-1;
		subHTML="<tr><th>Equipment</th><th>Sub-item</th></tr>";
		for(var n=0;n<count;n++){
			var parts=subItemTerms[n].split(",");
			subHTML+="<tr>";
			subHTML+="<td>"+parts[0]+"</td><td>";
			subHTML+=parts[1];
			subHTML+="</td>";
			subHTML+="</tr>";
		}
		//subHTML+="</select>";
	
	}
	document.getElementById('multi_list').innerHTML=subHTML;



}


function fillOnboard(ajaxHTML){
	var rollingHTML="<option></option>";

	if(ajaxHTML=="No data available"){
	
		
	}
	else {
		var equipmentTerms=ajaxHTML.split("==>");
		var count=(equipmentTerms.length)*1-1;
		
		for(var n=0;n<count;n++){
			var parts=equipmentTerms[n].split(";");
			rollingHTML+="<option value='"+parts[0]+"'>";
			rollingHTML+=parts[1];
			rollingHTML+="</option>";
		
		}

	
	}




	
	document.getElementById('equipment').innerHTML=rollingHTML;
	
	document.getElementById('sub_item_space').innerHTML="";		

}


function subItem(ajaxHTML){
	var subHTML="";
	
	if(ajaxHTML=="No data available"){
	
	
	}
	else {
		var subItemTerms=ajaxHTML.split("==>");
		var count=(subItemTerms.length)*1-1;
		subHTML="<select id='subitem' name='subitem'>";
		//subHTML+="<option></option>";
		for(var n=0;n<count;n++){
			var parts=subItemTerms[n].split(";");
			subHTML+="<option value='"+parts[0]+"'>";
			subHTML+=parts[1];
			subHTML+="</option>";
		
		}
		subHTML+="</select>";
	
	}
	document.getElementById('sub_item_space').innerHTML=subHTML;

}



function getCategory(problemType){
	var rollingHTML="";
	if(problemType=="rolling"){
		
		rollingHTML+="<select name='category' id='category' onchange='fillItem(\""+problemType+"\",this.value)' >";
		rollingHTML+="<option></option>";
		rollingHTML+="<option value='EXT'>Exterior</option>";
		rollingHTML+="<option value='UFE'>Underfloor Equipment</option>";
		rollingHTML+="<option value='OB'>Onboard Equipment and Accessories</option>";
		rollingHTML+="<option value='OTH'>Others</option>";


		rollingHTML+="</select>";	
		document.getElementById('rolling_category').innerHTML=rollingHTML;
	
	}
	
	else {
		if(problemType=="power"){
			rollingHTML+="<select id='category' name='category' onchange='fillItem(\""+problemType+"\",this.value)'>";
			rollingHTML+="<option></option>";
			rollingHTML+="<option value='OCS'>Overhead Catenary System</option>";
			rollingHTML+="<option value='SS'>Station Substation</option>";
			rollingHTML+="<option value='TPSS'>Traction Power Substation Equipment</option>";


			rollingHTML+="</select>";	
			
			document.getElementById('rolling_category').innerHTML=rollingHTML;
		
		
		}
		else {
			document.getElementById('rolling_category').innerHTML=rollingHTML;

		}
		
	}
}

function sampleFreeow(){	
$("#freeow").freeow("Success!", "Data Update..", {
    classes: ["gray", "append"],
    autoHide: true
});
	
}

</script>

<?php
if(isset($_POST['fieldType'])){			
//$incident_report=$_POST['incident_report'];
	//Mjun@
	$fieldT=$_POST['fieldType'];
	if ($fieldT<>"") {			
	$incident_report=$_POST['inc_report'];	
	$sql="update incident_report ";		
	switch($_POST['fieldType']){
		case "onboard_equipt":
			$sql.="set equipt='".$_POST['equipment']."' ";
			break;
		case "dotc":
			if(isset($_POST['dotc'])){
				$dotc_taken=$_POST['dotc'];

			}
			else if(isset($_POST['dotc_coordinated'])){
				$dotc_taken=$_POST['dotc_coordinated']." ".$_POST['coordinated_to'];
			}		
		
			$sql.="set action_dotc='".$dotc_taken."' ";
			break;
		case "maintenance":
			$sql.="set action_maintenance='".$_POST['maintenance_provider']."' ";
			break;
		case "level":
			$level_condition=$_POST['condition'];

			$sql.="set level='".$_POST['level']."',level_condition='".$level_condition."' ";
			break;
		case "description":
			$sql.="set description='".$_POST['description']."' ";
			break;

		case "duration":
			$sql.="set duration='".$_POST['duration']."' ";
			break;
			
			
		case "linked_to":
			$sql.="set linked_to='".$_POST['incident_link']."' ";
			break;
			
		case "recommend_approval":
			$sql.="set recommending_approval='".$_POST['recommend_approval']."' ";
			break;
			
		case "approving_officer":
			$sql.="set approving_person='".$_POST['approving_officer']."' ";
			break;			
			
		case "incident_no":
			
			$incidentSQL="select * from incident_report where id='".$incident_report."'";
			$incidentRS=$db->query($incidentSQL);
			$incidentRow=$incidentRS->fetch_assoc();
			
			$suffixSQL="select * from equipment_type where equipment_code='".$incidentRow['incident_type']."'";
			$suffixRS=$db->query($suffixSQL);
			
			$suffixRow=$suffixRS->fetch_assoc();
			$suffix=$suffixRow['incident_code'];
		
		
		
			$sql.="set incident_no='".$_POST['incident_number']." ".$suffix."' ";
			//$_POST['incident_report']=$_POST['incident_number'];
			break;
		case "problem":
			$sql.="set incident_type='".$_POST['type']."',equipt='',";

			$incidentSQL="select * from incident_report where id='".$incident_report."'";
			$incidentRS=$db->query($incidentSQL);
			$incidentRow=$incidentRS->fetch_assoc();
			
			$suffixSQL="select * from equipment_type where equipment_code='".$_POST['type']."'";
			$suffixRS=$db->query($suffixSQL);
			$suffixRow=$suffixRS->fetch_assoc();
			$suffix=$suffixRow['incident_code'];
				
		
			$sql.="incident_no='".$incidentRow['id']." ".$suffix."' ";

			
			if($_POST['type']=="ser_int"){
				echo "<script language='javascript'>";
				echo "window.open('service interruption.php?incident=".$incident_report."');";
				echo "</script>";
			}
						
			break;
		case "cancelled":
		
			$cancelTerm=$_POST['cancel'];
			if($cancelTerm=="whole"){
				$cancel=1;
			}
			else if($cancelTerm=="half"){
				$cancel=.5;
			}
			else if($cancelTerm=="more"){
				$cancel=$_POST['cancel_more'];
			}
			$sql.="set cancel='".$cancel."' ";
			break;	
		case "date":
			$year=$_POST['year'];
			$month=$_POST['month'];
			$day=$_POST['day'];
			
			$hour=$_POST['hour'];
//			echo $hour;
			$minute=$_POST['minute'];
//			echo $minute;
			$amorpm=$_POST['amorpm'];
//			echo $amorpm;
			$equipment=$_POST['equipment'];
			if($amorpm=="pm"){
				if($hour<12){
					$hour+=12;
				}
				else {
				}
			}
			else {
				if($hour=="12"){
					$hour=0;
				}
			}
			
			$incident_date=$year."-".$month."-".$day." ".$hour.":".$minute;
			//date("Y-m-d H:i",strtotime($year."-".$month."-".$day." ".$hour.":".$minute));
	//		echo $incident_date;
			$sql.="set incident_date='".$incident_date."' ";
			break;
		}
	$sql.=" where id='".$incident_report."'";
	
	$rs=$db->query($sql);
	$Mup = 1;
	
	if($_POST['fieldType']=='onboard_equipt'){
		$update="update incident_description set equipt='".$_POST['equipment']."', subitem='".$_POST['subitem']."' where incident_id='".$incident_report."'";

		$rs=$db->query($update);
	}
	if($_POST['fieldType']=='problem'){
		$update="update incident_description set equipt='', subitem='' where incident_id='".$incident_report."'";
		$rs=$db->query($update);
	}
	else if($_POST['fieldType']=="additional_defects"){
		$update="delete from incident_defects where incident_id='".$incident_report."'";
		$rs=$db2->query($update);
		
		$update="insert into incident_defects(incident_id,equipt_id,sub_item_id) (select '".$incident_report."',equipt_id,sub_item_id from temp_multiple)";
		$rs=$db2->query($update);
		
		$update="delete from temp_multiple";
		$rs=$db2->query($update);
			
	
	}
	

	else if($_POST['fieldType']=="level"){
		$levelSQL="select * from level where incident_id='".$incident_report."'";
		$levelRS=$db->query($levelSQL);


		
		$levelNM=$levelRS->num_rows;

		if($_POST['level']=="2"){
			//$update="update incident_report set l2='".$_POST['order']."',l3='',l4='' where id='".$incident_report."'";
			//$rs=$db->query($update);
			
			
			if($levelNM>0){
//				$update="update level set level='2',order='".$_POST['order']."' where id='".$incident_report."'";
//				$rs=$db->query($update);
			}
			else {
//				$update="insert into level(level,order,incident_id
			
			}

		}
		else if($_POST['level']=="3"){
			//$update="update incident_report set l3='".$_POST['order']."',l2='',l4='' where id='".$incident_report."'";
		//	$rs=$db->query($update);
		
			if($levelNM>0){
			
			
			}
			else {
			
			
			}

		}
		else if($_POST['level']=="4"){
		//	$update="update incident_report set l4='".$_POST['order']."',l2='',l3='' where id='".$incident_report."'";
			//$rs=$db->query($update);
			
			if($levelNM>0){
			
			
			}
			else {
			
			}

		}

		$incidentSQL="select * from incident_report where id='".$incident_report."'";
		$incidentRS=$db->query($incidentSQL);
		$incidentRow=$incidentRS->fetch_assoc();

		$incident_date=date("Y-m-d",strtotime($incidentRow['incident_date']));

		
		$updateSQL="delete from level where incident_id='".$incident_report."'";
		$updateRS=$db->query($updateSQL);
		
		$updateSQL="insert into level(date,incident_id,level) values ";
		$updateSQL.="('".$incident_date."','".$incident_report."','".$_POST['level']."')";
		$updateRS=$db->query($updateSQL);
		
		
		
	}
	else if($_POST['fieldType']=="index"){
		$update="update incident_description set index_no='".$_POST['index_id']."', car_no='".$_POST['car']."' where incident_id='".$incident_report."'";
		$rs=$db->query($update);
		
		$update="delete from incident_cars where incident_id='".$incident_report."'";
		$rs=$db->query($update);

		if($_POST['car']==""){
		}
		else {
			$update="insert into incident_cars(incident_id,car_no) values ('".$incident_report."','".$_POST['car']."')";
			$rs=$db->query($update);

		}
		
		if($_POST['car_2']==""){
		}
		else {
			$update="insert into incident_cars(incident_id,car_no) values ('".$incident_report."','".$_POST['car_2']."')";
			$rs=$db->query($update);
		
		}
		
		if($_POST['car_3']==""){
		}
		else {
			$update="insert into incident_cars(incident_id,car_no) values ('".$incident_report."','".$_POST['car_3']."')";
			$rs=$db->query($update);
		
		}
		
		
		
	}
	
	else if($_POST['fieldType']=="location"){
		$update="update incident_description set location='".$_POST['location']."',direction='".$_POST['direction']."' where incident_id='".$incident_report."'";

		$rs=$db->query($update);

	}

	else if($_POST['fieldType']=="reported_by"){
		$update="update incident_description set reported_by='".$_POST['reported_by']."' where incident_id='".$incident_report."'";

		$rs=$db->query($update);

	}

	else if($_POST['fieldType']=="received_by"){
		$update="update incident_description set received_by='".$_POST['received_by']."' where incident_id='".$incident_report."'";

		$rs=$db->query($update);

	}
	else if($_POST['fieldType']=="incident_no"){
		$update="update incident_report set incident_no='".$_POST['incident_digit']." ".$_POST['incident_suffix']."' where id='".$incident_report."'";
		$rs=$db->query($update);
		
		
		$update="update incident_no set incident_number='".$_POST['incident_digit']."', suffix='".$_POST['incident_suffix']."' where incident_id='".$incident_report."'";
		$db2=new mysqli("localhost","root","","user_transport");
		$rs=$db2->query($update);
	}
		
	
	
	// echo "<script typ=javascript> sampleFreeow();</script>"; 
	
	// echo "<script type='text/javascript'>Samplefreeow();</script>";	
	
	// echo '<script type="text/javascript">window.onload = function () { alert("Data Update!"); }</script>';
	
	
	//Mjun@ initialize
	$incident_report="";
	
	}
}
?>



<style type='text/css'>
/* =========================================================================
   EDIT CCDR — Operations Console Theme
   Uniform with train_availability_console.php / incident_report_console.php
   / clearance_form_console.php. Scoped under .ta-grid.ta-console.
   PHP/JS: completely unchanged below — including every fillEdit() and
   fillEquipt() string that injects <tr class='rowHeading'> into the modal;
   that class is restyled here to match, not altered in the JS itself.
   ========================================================================= */
:root {
	--cc-blue:    #00529B;
	--cc-gold:    #FDB813;
	--cc-dark:    #16243B;
	--cc-mid:     #41506A;
	--cc-muted:   #8A95A6;
	--cc-border:  #D2DDEA;
	--cc-row-odd: #EEF4FB;
	--cc-bg:      #F7F9FC;
	--cc-white:   #ffffff;
	--cc-sans:    "Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif;
}

body { font-family: var(--cc-sans); color: var(--cc-dark); }

/* -- .ccdr property-sheet tables (Control / Reporting / Action Taken) -- */
.ta-grid.ta-console .ccdr {
	border-collapse: collapse;
	border: 1px solid var(--cc-border);
	border-radius: 8px;
	overflow: hidden;
	font-size: 12px;
}
.ta-grid.ta-console .ccdr td,
.ta-grid.ta-console .ccdr th {
	border: 1px solid var(--cc-border);
	padding: 8px 12px;
	vertical-align: middle;
}
.ta-grid.ta-console .ccdr tr:nth-child(odd) td { background: var(--cc-row-odd); }
.ta-grid.ta-console .ccdr tr:nth-child(even) td { background: var(--cc-white); }
.ta-grid.ta-console .ccdr tr th:first-child {
	color: var(--cc-dark);
	font-weight: 600;
	font-size: 11px;
	text-align: left;
	white-space: nowrap;
	background: transparent;
}
.ta-grid.ta-console .ccdr #ccdr_heading,
.ta-grid.ta-console .ccdr tr#ccdr_heading {
	background: var(--cc-blue);
}
.ta-grid.ta-console .ccdr tr#ccdr_heading th {
	background: var(--cc-blue);
	color: #fff;
	font-family: var(--cc-sans);
	font-size: 13px;
	font-weight: 600;
	letter-spacing: .3px;
	border-bottom: 3px solid var(--cc-gold);
	border-color: #0A639E;
	text-align: center;
}

/* -- Edit links inside each row's third cell -- */
.ta-grid.ta-console .alink a,
.ta-grid.ta-console .ccdr a {
	font-size: 11px;
	font-weight: 600;
	text-decoration: none;
	color: var(--cc-blue);
	padding: 2px 9px;
	border-radius: 3px;
	border: 1px solid var(--cc-border);
	background: var(--cc-bg);
}
.ta-grid.ta-console .alink a:hover,
.ta-grid.ta-console .ccdr a:hover { background: var(--cc-blue); color: #fff; border-color: var(--cc-blue); }
.ta-grid.ta-console .alink a.disabled { color: var(--cc-muted); background: transparent; border-color: transparent; cursor: default; }
/* the "See <incident>" link and "[Report]" link read as plain text links, not buttons */
.ta-grid.ta-console .ccdr td a[onclick*="edit_ccdr"],
.ta-grid.ta-console .ccdr td a[onclick*="service interruption"] {
	padding: 0; border: none; background: none; font-weight: 500;
}
.ta-grid.ta-console .ccdr td a[onclick*="edit_ccdr"]:hover,
.ta-grid.ta-console .ccdr td a[onclick*="service interruption"]:hover { background: none; color: var(--cc-blue); text-decoration: underline; }

/* -- Search bar at top of page -- */
.ta-grid.ta-console .cc-search-bar {
	background: var(--cc-blue);
	border-bottom: 3px solid var(--cc-gold);
	border-radius: 8px 8px 0 0;
	padding: 10px 16px;
	display: flex;
	align-items: center;
	gap: 10px;
	flex-wrap: wrap;
	margin-bottom: 16px;
}
.ta-grid.ta-console .cc-search-bar b,
.ta-grid.ta-console .cc-search-bar font { color: #fff !important; font-size: 12px !important; font-weight: 600 !important; font-family: var(--cc-sans) !important; }
.ta-grid.ta-console .cc-search-bar input.text_input,
.ta-grid.ta-console .cc-search-bar select.text_input {
	height: 28px; font-size: 12px; font-family: var(--cc-sans);
	border: 1px solid var(--cc-border); background: #fff; color: var(--cc-dark);
	border-radius: 4px; padding: 0 8px;
}
.ta-grid.ta-console .cc-search-bar input[type="submit"] {
	height: 28px; font-size: 11px; font-weight: 600; font-family: var(--cc-sans);
	background: var(--cc-gold); color: #3A2D00; border: none; border-radius: 4px;
	padding: 0 14px; cursor: pointer;
}
.ta-grid.ta-console .cc-search-bar input[type="submit"]:hover { background: #E5A50F; }
.ta-grid.ta-console .cc-search-bar form { display: flex; align-items: center; gap: 8px; margin: 0; }

/* -- Section spacing -- */
.ta-grid.ta-console .ccdr { margin-bottom: 18px; }

/* -- rowHeading rows injected by fillEdit()/fillEquipt() JS into #edit_table -- */
#addModal .rowHeading td {
	background: var(--cc-blue);
	color: #fff;
	font-weight: 600;
	font-size: 12px;
	padding: 8px 12px;
	border-bottom: 3px solid var(--cc-gold);
}

/* -- Multiple-defects sub-tables (#multi_list / #multi_list2) -- */
#multi_list tr th, #multi_list2 tr th {
	background: var(--cc-blue);
	color: #fff;
	border: 1px solid var(--cc-border);
	text-align: center;
	font-size: 11px;
	font-weight: 600;
	padding: 6px 10px;
}
#multi_list tr:nth-child(n+2) td, #multi_list2 tr:nth-child(n+2) td {
	background: var(--cc-row-odd);
	color: var(--cc-dark);
	border: 1px solid var(--cc-border);
	padding: 6px 10px;
	font-size: 12px;
}

/* -- Modal shell — console theme, uniform with the other pages -- */
.modal { z-index: 99999; }
#addModal {
	border-radius: 8px;
	overflow: hidden;
	border: none;
	box-shadow: 0 8px 32px rgba(0,30,80,.18), 0 2px 8px rgba(0,30,80,.10);
	font-family: var(--cc-sans);
	min-width: 420px;
}
#addModal .modal-header {
	background: var(--cc-blue);
	border-bottom: 3px solid var(--cc-gold);
	padding: 10px 16px;
}
#addModal .modal-header h3 { color: #fff; font-size: 13px; font-weight: 600; margin: 0; }
#addModal .modal-header .close { color: rgba(255,255,255,.7); text-shadow: none; opacity: 1; font-size: 18px; }
#addModal .modal-header .close:hover { color: var(--cc-gold); }
#addModal .modal-body { background: var(--cc-bg); padding: 16px 18px; }
#addModal .modal-footer {
	background: #fff;
	border-top: 1px solid var(--cc-border);
	padding: 10px 16px;
	display: flex;
	justify-content: flex-end;
	gap: 8px;
}
#addModal .modal-footer .btn {
	font-size: 12px; font-weight: 500; padding: 5px 16px; border-radius: 4px;
	border: 1px solid var(--cc-border); background: #fff; color: var(--cc-mid); text-decoration: none;
}
#addModal .modal-footer .btn:hover { background: var(--cc-row-odd); border-color: var(--cc-blue); color: var(--cc-blue); }
#addModal .modal-footer .btn-primary { background: var(--cc-blue); border-color: var(--cc-blue); color: #fff; }
#addModal .modal-footer .btn-primary:hover { background: #013E76; border-color: #013E76; }

/* -- #edit_table (built by fillEdit/fillEquipt JS) and #edit_form th -- */
#edit_table { width: 100%; border-collapse: collapse; font-size: 12px; }
#edit_table th {
	background: var(--cc-row-odd); color: var(--cc-dark); font-weight: 600;
	font-size: 11px; padding: 8px 10px; text-align: left; white-space: nowrap;
	border-bottom: 1px solid var(--cc-border);
}
#edit_table td { padding: 7px 10px; border-bottom: 1px solid var(--cc-border); }
#edit_form th { background: var(--cc-row-odd); color: var(--cc-dark); padding: 7px 10px; font-size: 11px; font-weight: 600; }

/* -- Form controls — scoped to #addModal so nothing outside the modal changes -- */
#addModal input[type="text"],
#addModal select {
	height: 28px; font-size: 12px; font-family: var(--cc-sans); font-weight: 400;
	border: 1px solid var(--cc-border); background: #fff; color: var(--cc-dark);
	border-radius: 4px; padding: 0 8px; box-sizing: border-box;
}
#addModal input[type="text"]:focus,
#addModal select:focus { border-color: var(--cc-blue); outline: none; box-shadow: 0 0 0 2px rgba(0,82,155,.12); }
#addModal textarea {
	font-size: 12px; font-family: var(--cc-sans); border: 1px solid var(--cc-border);
	background: #fff; color: var(--cc-dark); border-radius: 4px; padding: 7px 9px;
	width: 100%; box-sizing: border-box; resize: vertical; min-height: 80px;
}
#addModal textarea:focus { border-color: var(--cc-blue); outline: none; box-shadow: 0 0 0 2px rgba(0,82,155,.12); }
#addModal input[type="button"],
#addModal button[type="button"]:not(.close) {
	height: 28px; font-size: 11px; font-weight: 500; font-family: var(--cc-sans);
	background: #fff; color: var(--cc-blue); border: 1px solid var(--cc-border);
	border-radius: 4px; padding: 0 12px; cursor: pointer;
}
#addModal input[type="button"]:hover { background: var(--cc-row-odd); border-color: var(--cc-blue); }
#addModal input[type="checkbox"] { margin-right: 5px; vertical-align: middle; }
#addModal input[disabled] { background: var(--cc-bg); color: var(--cc-muted); }
/* The original markup includes invisible white-on-white spacer text
   (<font color=white>| | | ...) used as layout padding before the hidden
   inputs. It renders as nothing either way; hidden here for cleanliness
   without touching the markup itself. */
#addModal font[color="white"] { display: none; }

</style>



<!-- orig javascrip    -->

<body>
<br>
<br>
<br>
<br>

<div id="freeow" class="freeow freeow-bottom-right"></div>
<div class="ta-grid ta-console">
<div class="cc-search-bar">
<form action='edit_ccdr.php' method='post'><b>Search Incident Number</b> <input class='text_input' type=text name='search_incident_number'/><input type=submit value='Search' /></form>




<?php
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
?>
<?php
if(isset($_POST['search_incident_number'])){
	$sql="select * from incident_report where incident_no like '".$_POST['search_incident_number']."%%' order by incident_no";

	$rs=$db->query($sql);
	$nm=$rs->num_rows;
	if($nm>0){
	?>	
<form action='edit_ccdr.php' method=post><b>Retrieve Incident Report</b> <select  class='text_input' name='incident_report'>
	<?php
		for($i=0;$i<$nm;$i++){
		$row=$rs->fetch_assoc();
	?>	
			<option value="<?php echo $row['id']; ?>"><?php echo $row['incident_no']; ?></option>
	<?php
		}
	?>
	</select>
	<input type=submit value='Retrieve' />
</form>
	<?php	
	}

}
?>
</div><!-- /.cc-search-bar -->
<?php
?>
<?php
	if((isset($_POST['incident_report']))||(isset($_GET['ir']))){
		
		if(isset($_GET['ir'])){
			$incident_report=$_GET['ir'];
		}
		else {
			$incident_report=$_POST['incident_report'];
		
		}
		
//		$sql="select * from train_incident_view inner join level on train_incident_view.incident_id=level.incident_id where id='".$incident_report."'";
		$sql="select * from incident_report inner join level on incident_report.id=level.incident_id where incident_report.id='".$incident_report."'";
		
		$rs=$db->query($sql);
		$row=$rs->fetch_assoc();
		

		
		$level_condition=$row['level_condition'];
		
		$conditionSQL="select * from level_condition where id='".$level_condition."'";
		$conditionRS=$db->query($conditionSQL);
		
		$conditionRow=$conditionRS->fetch_assoc();
		
		$condition=$conditionRow['description'];
		
		$link_no="";
		$linked_to=$row['linked_to'];
		
		$linkSQL="select * from incident_report where id='".$linked_to."'";
		$linkRS=$db->query($linkSQL);
		
		$linkNM=$linkRS->num_rows;
		
		if($linkNM>0){
			$linkRow=$linkRS->fetch_assoc();
		
			$link_no=$linkRow['incident_no'];
		
		
		}
			
		$incident_no=$row['incident_no'];
		$problem_type2=$row['incident_type'];
		$equipSQL="select * from equipment_type where equipment_code='".$problem_type2."'";
		$equipRS=$db->query($equipSQL);
		$row2=$equipRS->fetch_assoc();
		$problem_type=$row2['equipment_name'];


		$level=$row['level'];
		
		$levelClause=="";
		if($level==2){
			$levelClause.=" (".getOrdinal($row['order']).")";
		
		}
		else if($level==3){
			$levelClause.=" (".getOrdinal($row['order']).")";
		
		}
		else if($level==4){
			$levelClause.=" (".getOrdinal($row['order']).")";
		
		}		
		$cancel=$row['cancel'];
		
		
		$date=date("Y-m-d",strtotime($row['incident_date']));
		$time=date("H:ia",strtotime($row['incident_date']));
		
		$$incident_time="";
		
		$duration=$row['duration'];
		$equipt=$row['equipt'];
		
		$onboard_equipt="";
		if($problem_type2=="others"){
			$equipSQL="select * from other_problem where id='".$equipt."'";

			$equipRS=$db->query($equipSQL);
			$row2=$equipRS->fetch_assoc();
			$onboard_equipt=$row2['problem'];		
		}
		else {
			$equipSQL="select * from equipment where id='".$equipt."'";

			$equipRS=$db->query($equipSQL);
			$row2=$equipRS->fetch_assoc();
			$onboard_equipt=$row2['equipment_name'];
		}
		
		$description=$row['description'];
		$dotc_action=$row['action_dotc'];
		$maintenance_action=$row['action_maintenance'];
		$recommend_approval=$row['recommending_approval'];
		$approving_officer=$row['approving_person'];
		
		$category=$row['category'];

		$categoryName="";

		if($category==""){
			$categorySQL="select * from category where category_code='".$category."'";
			$categoryRS=$db->query($categorySQL);
			
			$categoryRow=$categoryRS->fetch_assoc();
			
			$categoryName=$categoryRow['category'];

		
		}
		

		$irSQL="select * from incident_description where incident_id='".$incident_report."'";
		$irRS=$db->query($irSQL);
		
			
		$irRow=$irRS->fetch_assoc();
		
		
		$indexNo=$irRow['index_no'];
		$carNo=$irRow['car_no'];
		
		$car[0]="";
		$car[1]="";
		$car[2]="";

		
		$carSQL="select * from incident_cars where incident_id='".$incident_report."'";
		$carRS=$db->query($carSQL);
		$carNM=$carRS->num_rows;
		
		if($carNM>0){
			for($b=0;$b<$carNM;$b++){
				$carRow=$carRS->fetch_assoc();
				$car[$b]=$carRow['car_no'];
			}			
			
			$carClause=$car[0];
			if($car[1]==""){
			}
			else {
				$carClause.=", ".$car[1];
			}
			
			if($car[2]==""){
			}
			else {
				$carClause.=", ".$car[2];
			}
			
		}
		
		
		
		
		
		
		
		$location=$irRow['location'];
		$direction=$irRow['direction'];
		
		
		
		$reported_by=$irRow['reported_by'];
		$received_by="";
		
		$tdSQL="select * from train_driver where id='".$irRow['received_by']."'";
		$tdRS=$db->query($tdSQL);
		$tdNM=$tdRS->num_rows;
		if($tdNM>0){
			$tdRow=$tdRS->fetch_assoc();
			$received_by=$tdRow['lastName'].", ".$tdRow['firstName'];
			
		}
		
		
		
		
		if($direction=="S"){
			$direction="";
		}
		
		
		$subClause="";
		
		$subItemSQL="select * from sub_item where id='".$irRow['subitem']."'";
		$subItemRS=$db->query($subItemSQL);
		$subItemNM=$subItemRS->num_rows;
		
		if($subItemNM>0){
			$subItemRow=$subItemRS->fetch_assoc();

			$subClause=" / ".$subItemRow['sub_item'];			
		
		}
		
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
		
		$serviceSQL="select * from service_interruption where incident_id='".$incident_report."'";
		$serviceRS=$db->query($serviceSQL);
		$serviceNM=$serviceRS->fetch_assoc();
		if(isset($_POST['incident_report'])){
			if($level_condition=='3'){
				echo "<script language='javascript'>";
				echo "window.open('service interruption.php?incident=".$incident_report."');";
				echo "</script>";
		//		header("Location: service interruption.php?incident=".$incident_code);
			}		
		}	
		
		
	$db2=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_external");
		$defectsSQL="select * from incident_defects where incident_id='".$incident_report."'";
		
		$defectsRS=$db2->query($defectsSQL);
		$defectsNM=$defectsRS->num_rows;
		
		
		$additional_defects="";
		if($defectsNM>0){
			for($n=0;$n<$defectsNM;$n++){
				$defectsRow=$defectsRS->fetch_assoc();

				$equiptSQL="select * from equipment where id='".$defectsRow['equipt_id']."' limit 1";
				$equiptRS=$db->query($equiptSQL);
				$equiptRow=$equiptRS->fetch_assoc();
				
				$eq_name=$equiptRow['equipment_name'];
				

				if($defectsRow['sub_item_id']==""){
				}
				else {
					$subitemSQL="select * from sub_item where id='".$defectsRow['sub_item_id']."'";
					$subitemRS=$db->query($subitemSQL);
					$subitemNM=$subitemRS->num_rows;
					
					if($subitemNM>0){
						$subitemRow=$subitemRS->fetch_assoc();
						$sub_item=$subitemRow['sub_item'];
					}
				}
					
				$additional_defects.="<tr><td>".$eq_name."</td><td>".$sub_item."</td></tr>";
			}
		}

		
		
		
	}



?>

<!-- table for Control -->
<script type="text/javascript">
$(document).ready(function(){
    $(".alink a").each(function(){
        if($(this).hasClass("disabled")){
            $(this).removeAttr("href");
        }
    });
});
</script>


<?php
if ($ULev>=2){
	$SRemove = "";
} else {
	$SRemove = "disabled";
}
?>

<!-- table for Control -->

<div class="alink">

<table width=70% class='ccdr'>
<tr id='ccdr_heading'><th colspan=3 style=text-align:center>Control Center Daily Report</th></tr>
<tr><th width=20%>Incident Number</th><td width=50%><?php echo $incident_no; ?></td><td align=center><a href='#edit_form'  class="<?php echo $SRemove; ?>" onclick='fillEdit("incident_no")'>Edit</a></td>


<!--

<a href='#edit_form' onclick='fillEdit("incident_no")'>Edit</a>
-->
&nbsp;
</tr>
<tr><th>Problem Category</th>
<td>
<?php echo $problem_type; ?>
<?php
if($categoryName==""){
}
else {
	echo " / ".$categoryName;

}
?>
<?php
if($level_condition=="3"){	

//	if($serviceNM>0){
		echo " [<a href='#' onclick='window.open(\"service interruption.php?incident=".$incident_report."\")'>Report</a>]";
	
//	}

}

?>
</td>

<td align="center"><a href='#edit_form' onclick='fillEdit("problem")'  class="<?php echo $SRemove; ?>">Edit</a></td></tr>

<tr><th>On-board Equipt/Accessories</th>


<td><?php echo $onboard_equipt; ?>
<?php
echo $subClause; 
?>

<span style="visibility:hidden">
<select name='equipment_copy' id='equipment_copy' hidden>
<option></option>
<?php 
	$db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
$sql="select * from equipment order by equipment_name";
$rs=$db->query($sql);
$nm=$rs->num_rows;
for($i=0;$i<$nm;$i++){
$row=$rs->fetch_assoc();
?>
<option value='<?php echo $row['id']; ?>'><?php echo $row['equipment_name']; ?></option>
<?php
}
?>
<option value='others'>OTHERS</option>
</select>
</span>
</td>
<td align="center"><a href='#edit_form' class="<?php echo $SRemove; ?>" onclick='fillEquipt("onboard_equipt","<?php echo $problem_type2; ?>")'>Edit</a></td>
</tr>
<tr>
<th>Additional Defects
</th>
<td>
		<?php echo "<table name='multi_list2' id='multi_list2' width=80%>";
		echo "<tr><th>Equipment</th><th>Sub-item</th></tr>";
		?>
		<?php	
		echo $additional_defects;
		echo "</table>";
	
		?>

</td>
<td align="center"><a href='#edit_form'  class="<?php echo $SRemove; ?>" onclick='fillEdit("additional_defects")'>Edit</a></td>
</tr>


<tr>
<th>Linked Incident (?)</th>
<td>
<?php
if($link_no==""){
}
else {
?>
See <a href='#'  class="<?php echo $SRemove; ?>" onclick='window.open("edit_ccdr.php?ir=<?php echo $linked_to; ?>","_blank")'><?php echo $link_no; ?></a>

<?php
}
?>
</td>
<td align="center"><a href='#edit_form'  class="<?php echo $SRemove; ?>" onclick='fillEdit("link_incident")'>Edit</a></td>
</tr>
<tr>
<th>Index Number</th>


<td>
<?php
	echo $indexNo;

?>


</td><td align="center"><a href='#edit_form'  class="<?php echo $SRemove; ?>" onclick='fillEdit("index")'>Edit</a></td></tr>

<tr>

<tr>
<th>Car Numbers</th>


<td>
<?php
if($carNo==""){
}
else {
	echo $carClause;

}

?>


</td><td align="center"><a href='#edit_form'  class="<?php echo $SRemove; ?>" onclick='fillEdit("index")'>Edit</a></td></tr>

<tr>

<th>Cancelled Loops</th>
<td>
<?php echo $cancel; ?>

</td>
<td align="center">
<a href='#edit_form'  class="<?php echo $SRemove; ?>" onclick='fillEdit("cancel")'>Edit</a>
</td>
</tr>

</tr>
<tr><th>Level</th><td><?php echo $level; echo $levelClause; echo ". ".$condition; ?></td><td align="center"><a href='#edit_form'  class="<?php echo $SRemove; ?>" onclick='fillEdit("level")'>Edit</a></td></tr>



<tr><th>Date</th><td><?php echo $date; ?></td><td align="center"><a href='#edit_form'  class="<?php echo $SRemove; ?>" onclick='fillEdit("date")'>Edit</a></td></tr>
<tr><th>Time</th><td><?php echo $time; ?></td><td align="center"><a href='#edit_form'  class="<?php echo $SRemove; ?>" onclick='fillEdit("date")'>Edit</a></td></tr>
<tr><th>Incident Duration</th><td><?php echo $duration; ?></td><td align="center"><a href='#edit_form'  class="<?php echo $SRemove; ?>" onclick='fillEdit("duration")'>Edit</a></td></tr>

<tr><th>Location/Direction</th><td><?php echo str_replace("D","Depot",$direction); echo " ".$location; ?></td><td align="center"><a href='#edit_form' class="<?php echo $SRemove; ?>" onclick='fillEdit("location")'>Edit</a></td></tr>




<tr><th>Description</th><td><?php echo $description; ?></td><td align="center"><a href='#edit_form'  class="<?php echo $SRemove; ?>" onclick='fillEdit("description")'>Edit</a></td></tr>
		
		

</table>
<br>

<!-- table for Report -->

<table  class='ccdr' width=70% border=1>
<tr id='ccdr_heading'><th colspan=3 style=text-align:center>Reporting</th></tr>
<tr><th width=20%>Reported By</th><td width=50%><?php echo $reported_by; ?></td><td width=5% align="center"><a href='#edit_form'  class="<?php echo $SRemove; ?>" onclick='fillEdit("reported_by")'>Edit</a></td></tr>
<tr><th>Received By</th><td><?php echo $received_by; ?></td><td align="center"><a href='#edit_form'  class="<?php echo $SRemove; ?>" onclick='fillEdit("received_by")'>Edit</a></td></tr>
<tr><th width=20%>Recommending Approval</th><td width=50%><?php echo $recommend_approval; ?></td><td align=center> <a href='#edit_form'  class="<?php echo $SRemove; ?>" onclick='fillEdit("recommend_approval")'>Edit</a></td></tr>
<tr><th>Approving Officer</th><td><?php echo $approving_officer; ?></td><td align=center> <a href='#edit_form'  class="<?php echo $SRemove; ?>" onclick='fillEdit("approving_officer")'>Edit</a></td></tr>

</table>
<br>

<!-- table for maintenance -->

<table  class='ccdr' width=70% border=1>
<tr id='ccdr_heading'><th colspan=3 style=text-align:center>Action Taken</th></tr>
<tr><th width=20%>DOTR</th><td width=50%><?php echo $dotc_action; ?></td><td width=5% align="center"><a href='#edit_form'  class="<?php echo $SRemove; ?>" onclick='fillEdit("dotc")'>Edit</a></td></tr>
<tr><th>Maintenance Provider</th><td><?php echo $maintenance_action; ?></td><td align="center">&nbsp;</td></tr>

</table>
<br>
<br>
</div><!-- /.alink -->
</div><!-- /.ta-grid -->

<!--<form id='edit_form' name='edit_form' action='edit_ccdr.php'  method='post'>
	<table id='edit_table' name='edit_table' width=80%>	
	</table>
	<table width=80%>
	<tr><th width=20%>Incident ID</th><td><input type=hidden id='incident_report' name='incident_report' value='<?php echo $incident_report; ?>' /><input type='text' name='incident_report1' value='<?php echo $incident_no; ?>' /></td></tr>
	</table>
	<br>
	<div align=left><font color=white>| | | | | | | | | | | | | | | | | | | |</font><input type=hidden name='fieldType' id='fieldType' /><input type=submit value='Edit' /></div>
	
	
	------------
	<div align=left><font color=white>| | | | | | | | | | | | | | | | | | | |</font><input type="hidden" name='fieldType' id='fieldType' /> <input type="hidden" name="inc_report" value='<?php echo $incident_report?>' /> <input type=button id=submit onclick="sampleFreeow()" value='Edit' /></div>
	</form>
	
</form> -->

<!-- Mjun@ -->
		<div class="modal hide fade" id="addModal">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
				<h3>Edit CCDR Field</h3>
			</div>
<form id='edit_form' name='edit_form' action='edit_ccdr.php?ir=<?php echo $incident_report; ?>'  method='post'>

			<div class="modal-body">	
				<table id='edit_table' name='edit_table' width=80%>	
				</table>
				<table width=80%>
				<tr><th width=20%>Incident ID</th><td><input type="Text" id='incident_report' name='incident_report' value='<?php echo $incident_report; ?>' disabled/></td></tr> 
				</table>
				<br>
				<div align=left><font color=white>| | | | | | | | | | | | | | | | | | | |</font><input type="hidden" name='fieldType' id='fieldType' /> <input type="hidden" name="inc_report" value='<?php echo $incident_report?>' /> </div>


				
			</div>
						
			<div class="modal-footer">
				<a href="#" class="btn" data-dismiss="modal">Close</a>
				<button type='submit' class="btn btn-primary" id='Suc' value='Submit'>Edit </button>
			</div>
			  </form>
		</div>



<?php	
if(isset($_POST['submit'])){	
	if ($Mup==1) {		
	echo "<script typ=javascript> sampleFreeow();</script>"; 
	$Mup=0;
	}
}
	?>
</body>
	<script src="js/jquery-migrate-1.2.1.min.js"></script>	
		<script src="js/jquery-ui-1.10.3.custom.min.js"></script>	
		<script src="js/jquery.ui.touch-punch.js"></script>	
		<script src="js/modernizr.js"></script>	
		<script src="js/bootstrap.min.js"></script>	
		

<script src="js/date.js"></script>	
<script src='js/form.js'></script>