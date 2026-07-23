<?php
$IR_EMBED = isset($_GET['embed']);
if($IR_EMBED){ ob_start(); }
require("Tmenu.php");
if($IR_EMBED){ ob_end_clean(); }
require_once("db_config.php"); /* centralized credentials -- see db_config.php */
	
	$db=iss_db('transport');

	$db2=iss_db('external');
	
	
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

/* --------------------------------------------------------------------
   Edit-field modal: shown/hidden directly, without Bootstrap's plugin.

   This used to be $('#addModal').modal('show'), which depends on two
   things that are not reliable inside the embed=1 slide-panel iframe:
   bootstrap.min.js having registered $.fn.modal, and a transitionend
   firing on the injected backdrop before the plugin calls .show() on
   the modal (see the modal shim note in the stylesheet below). When
   either link in that chain breaks, fillEdit() still runs to completion
   -- the fields are built into #edit_table -- but nothing appears, so
   the Edit click looks dead.

   The equipment and link editors on this page never had that problem
   because they just toggle a class. These helpers do the same for
   #addModal: no plugin, no transition timing. Closing is handled here
   too, since the Close controls relied on data-dismiss, which is also
   plugin-driven and would otherwise stop working.
   -------------------------------------------------------------------- */
function ccEditBackdrop(show){
	var b=document.getElementById('cc-edit-backdrop');
	if(show){
		if(!b){
			b=document.createElement('div');
			b.id='cc-edit-backdrop';
			b.className='modal-backdrop fade in';
			b.onclick=function(){ ccEditModalHide(); };
			document.body.appendChild(b);
		}
		b.style.display='block';
	}
	else if(b){ b.style.display='none'; }
}

function ccEditModalShow(){
	var m=document.getElementById('addModal');
	if(!m){ return; }
	ccEditBackdrop(true);
	m.classList.remove('hide');   /* .hide { display:none } from the shim */
	m.classList.add('in');        /* .fade.in -> opacity 1, #addModal.in -> top */
	m.style.display='block';      /* inline, beats #addModal { display:none } */
}

function ccEditModalHide(){
	var m=document.getElementById('addModal');
	if(m){
		m.classList.remove('in');
		m.style.display='none';
	}
	ccEditBackdrop(false);
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
	else if(elementName=="resolution_date"){
		elementContents="<tr class='rowHeading'><td>&nbsp;</td><td>Edit CCDR Details</td></tr>";
	
		var dateHTML=setDate();
//		var dateHTML=document.getElementById('dateStamp').innerHTML;
		elementContents+="<tr><th width=20%>Date/Time</th><td>"+dateHTML+"</td></tr>";
		document.getElementById('fieldType').value='resolution_date';
		
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
		elementContents+="<tr><th width=20%>Index Number</th>";

		elementContents+="<td>";
		elementContents+="<input type='text' name='index_id' id='index_id' size=5 />  ";
		elementContents+="</td></tr>";


		elementContents+="<tr><th width=20%>Car Numbers.</th>";

		elementContents+="<td>";

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
	
	
	ccEditModalShow();
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
	ccEditModalShow();

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
	$incident_report=$_POST['inc_report'];

	/* -- Multi-equipment / multi-linked-incident writes -------------------
	   These no longer depend on a specific fieldType value -- equipment_ids
	   and incident_links now travel with edit_form itself (attached via
	   form="edit_form" on their hidden inputs, since the equipment/link
	   editors are no longer separate <form>s). That means they run
	   whenever their hidden field is present, REGARDLESS of what (if
	   anything) fieldType says -- including when fieldType is empty, which
	   is what the standalone "Save Equipment & Linked Incidents" button
	   submits when the user hasn't touched any other field.

	   IMPORTANT: because these hidden fields are now present on every
	   edit_form submission (not just when their own editor was opened),
	   they are seeded from the existing DB rows unconditionally on page
	   load now (ccEqSeedExisting()/ccLinkSeedExisting() are called
	   immediately, not just on first modal-open -- see the bottom of the
	   script block below). Without that, editing an unrelated field like
	   Level would submit an EMPTY equipment_ids/incident_links and wipe
	   out equipment/links the user never touched this session. ---------- */
	if(isset($_POST['equipment_ids'])){
		$db->query("delete from incident_equipment where incident_id='".$incident_report."'");
		if(!empty($_POST['equipment_ids'])){
			$pairs=array_filter(is_array($_POST['equipment_ids'])
				? $_POST['equipment_ids']
				: explode(',',$_POST['equipment_ids']));
			foreach($pairs as $pair){
				$pair=trim($pair);
				if($pair==='') continue;
				$parts=explode(':',$pair);
				$equipt_id =(int)trim($parts[0]);
				$subitem_id=isset($parts[1]) ? (int)trim($parts[1]) : 0;
				if($equipt_id<=0) continue;
				$db->query("insert ignore into incident_equipment(incident_id,equipt_id,subitem_id) values ('".$incident_report."','".$equipt_id."','".$subitem_id."')");
			}
		}
		$Mup=1;
	}
	if(isset($_POST['incident_links'])){
		$db->query("delete from incident_linked_reports where incident_id='".$incident_report."'");
		$firstLink=true;
		if(!empty($_POST['incident_links'])){
			$links=array_filter(is_array($_POST['incident_links'])
				? $_POST['incident_links']
				: explode(',',$_POST['incident_links']));
			foreach($links as $linked_id){
				$linked_id=(int)trim($linked_id);
				if($linked_id<=0) continue;
				$db->query("insert ignore into incident_linked_reports(incident_id,linked_to) values ('".$incident_report."','".$linked_id."')");
				if($firstLink){
					$db->query("update incident_report set linked_to='".$linked_id."' where id='".$incident_report."'");
					$firstLink=false;
				}
			}
		}
		if($firstLink){
			$db->query("update incident_report set linked_to='' where id='".$incident_report."'");
		}
		$Mup=1;
	}

	/* -- Generic single-field update (unchanged switch/cases) -- only when
	   a specific field edit was actually requested via one of the "Edit"
	   links elsewhere on the page. The standalone equipment/links save
	   leaves fieldType empty, so this whole block correctly does nothing
	   extra in that case -- the two writes above already covered it. ---- */
	if ($fieldT<>"") {			
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
			
			
		case "resolution_date":
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
			
			$resolution_date=$year."-".$month."-".$day." ".$hour.":".$minute;
			//date("Y-m-d H:i",strtotime($year."-".$month."-".$day." ".$hour.":".$minute));
	//		echo $incident_date;
			$sql.="set resolution_date='".$resolution_date."' ";
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
		$resolution_date=date("Y-m-d",strtotime($incidentRow['resolution_date']));

		
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
		/* item #1 fix: was `new mysqli("localhost","root","","user_transport")` -- root,
		   blank password, and the pre-migration database name. Confirmed (2026-07):
		   is_user_transport is the current name; this almost certainly meant incident
		   renumbering never reached the live table. Centralized via db_config.php. */
		require_once("db_config.php");
		$db2=iss_db('user_transport');
		$rs=$db2->query($update);
	}
		
	
	
	// echo "<script typ=javascript> sampleFreeow();</script>"; 
	
	// echo "<script type='text/javascript'>Samplefreeow();</script>";	
	
	// echo '<script type="text/javascript">window.onload = function () { alert("Data Update!"); }</script>';
	
	} /* end if($fieldT<>"") - generic single-field update path */
	
	//Mjun@ initialize
	$incident_report="";
	
}
?>



<style type='text/css'>
/* =========================================================================
   EDIT CCDR ? Operations Console Theme
   Uniform with train_availability_console.php / incident_report_console.php
   / clearance_form_console.php. Scoped under .ta-grid.ta-console.
   PHP/JS: completely unchanged below ? including every fillEdit() and
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
	
		margin: 24px auto 0 auto;

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

/* -- Modal shell ? console theme, uniform with the other pages -- */
.modal { z-index: 99999; }
/* -- Embed-safe Bootstrap-2 modal shims --------------------------------
   Standalone, Tmenu.php's <head> prints the full bootstrap.css -- which is
   where .hide, the generic .fade transition, .modal.fade's top slide and
   the .modal-backdrop rules actually live; modal_only.css only carries the
   .modal shell. With embed=1 (train_operations slide-panel iframe) the
   Tmenu require is wrapped in ob_start/ob_end_clean, so ALL of that CSS is
   discarded -- and bootstrap-modal.js, seeing a .fade modal, appends the
   backdrop and waits for a transitionend on it before it ever calls
   $element.show() on the modal. With no .fade transition rule in the
   document that event never fires, so $('#addModal').modal('show') runs
   but the modal never appears: the Edit click looks dead inside the panel,
   while standalone (Tmenu CSS present) works fine. Duplicating the exact
   BS2 rules here is a no-op standalone and makes the modal self-sufficient
   when embedded. Two deliberate deltas: the id-level display guard (same
   fix as equipment_list/signatories_list, so the hidden modal can never
   sit invisibly over the page intercepting clicks), and backdrop z-index
   raised to sit just under this page's .modal { z-index: 99999 }. -- */
#addModal { display: none; }
#addModal.in { display: block; }
.hide { display: none; }
.fade { opacity: 0; -webkit-transition: opacity .15s linear; -moz-transition: opacity .15s linear; -o-transition: opacity .15s linear; transition: opacity .15s linear; }
.fade.in { opacity: 1; }
.modal.fade { top: -25%; -webkit-transition: opacity .3s linear, top .3s ease-out; -moz-transition: opacity .3s linear, top .3s ease-out; -o-transition: opacity .3s linear, top .3s ease-out; transition: opacity .3s linear, top .3s ease-out; }
.modal.fade.in, #addModal.in { top: 10%; }
.modal-backdrop { position: fixed; top: 0; right: 0; bottom: 0; left: 0; z-index: 99998; background-color: #000000; }
.modal-backdrop.fade { opacity: 0; }
.modal-backdrop, .modal-backdrop.fade.in { opacity: 0.8; }
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

/* -- Form controls ? scoped to #addModal so nothing outside the modal changes -- */
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

/* --- Per-field Edit pill (hidden until row hover, matches clearance_form) --- */
.ta-grid.ta-console .ccdr a.cc-edit-pill {
	display: inline-flex; align-items: center;
	font-size: 10px; font-weight: 600; text-decoration: none;
	padding: 2px 9px; border-radius: 999px;
	border: 1px solid var(--cc-border); background: var(--cc-white);
	color: var(--cc-muted);
	opacity: 0; transform: translateY(1px);
	transition: opacity .12s, background .12s, border-color .12s, color .12s, transform .12s;
}
.ta-grid.ta-console .ccdr tr:hover a.cc-edit-pill { opacity: 1; transform: translateY(0); }
.ta-grid.ta-console .ccdr a.cc-edit-pill:hover {
	background: var(--cc-blue); border-color: var(--cc-blue); color: #fff;
}

/* Read-only listings in the property sheet */
.cc-none-note { font-size: 11px; color: var(--cc-muted); font-style: italic; }
.ta-grid.ta-console .cc-eq-list { border-collapse: collapse; font-size: 12px; }
.ta-grid.ta-console .cc-eq-list th {
	background: var(--cc-row-odd); color: var(--cc-mid); font-size: 10px;
	font-weight: 600; text-align: left; padding: 4px 10px; border: 1px solid var(--cc-border);
}
.ta-grid.ta-console .cc-eq-list td { padding: 5px 10px; border: 1px solid var(--cc-border); }
.cc-linked-item { font-size: 12px; margin: 2px 0; }
.cc-linked-item a { color: var(--cc-blue); }

/* --- Ported multi-equipment picker (option-C ir-eq-*, renamed cc-eq-*) --- */
.cc-eq-panel{border:1px solid var(--cc-border);border-radius:6px;overflow:hidden;margin-top:8px;}
.cc-eq-panel-head{background:var(--cc-row-odd);padding:7px 11px;font-size:11px;font-weight:600;color:var(--cc-mid);border-bottom:1px solid var(--cc-border);display:flex;align-items:center;gap:7px;}
.cc-eq-panel-body{max-height:200px;overflow-y:auto;}
.cc-eq-panel-foot{padding:7px 11px;border-top:1px solid var(--cc-border);background:var(--cc-bg);display:flex;justify-content:flex-end;gap:7px;}
.cc-eq-cb-row{display:flex;align-items:center;gap:8px;padding:6px 10px;cursor:pointer;border-bottom:1px solid var(--cc-border);}
.cc-eq-cb-row:last-child{border-bottom:none;}
.cc-eq-cb-row:hover{background:var(--cc-row-odd);}
.cc-eq-cb-row input[type=checkbox]{accent-color:var(--cc-blue);width:14px;height:14px;cursor:pointer;flex-shrink:0;}
.cc-eq-cb-row .cc-eq-name{font-size:12px;color:var(--cc-dark);flex:1;}
.cc-eq-cb-row .cc-eq-cat{font-size:10px;color:var(--cc-muted);white-space:nowrap;}
.cc-eq-chips{display:flex;flex-direction:column;gap:8px;min-height:32px;padding:8px;border:1px solid var(--cc-border);border-radius:4px;background:var(--cc-bg);margin-top:8px;}
.cc-eq-card{background:var(--cc-white);border:1px solid var(--cc-border);border-radius:6px;overflow:hidden;}
.cc-eq-card-head{display:flex;align-items:center;justify-content:space-between;padding:6px 10px;background:var(--cc-row-odd);border-bottom:1px solid var(--cc-border);}
.cc-eq-card-name{font-size:12px;font-weight:600;color:var(--cc-blue);}
.cc-eq-card-head button{background:none;border:none;cursor:pointer;color:var(--cc-muted);padding:0;line-height:1;font-size:15px;display:flex;align-items:center;}
.cc-eq-card-head button:hover{color:#E24B4A;}
.cc-eq-card-sub{padding:8px 10px;}
.cc-eq-subselect{height:28px;font-size:12px;font-family:var(--cc-sans);border:1px solid var(--cc-border);background:var(--cc-white);color:var(--cc-dark);border-radius:4px;padding:0 6px;width:100%;box-sizing:border-box;}
.cc-eq-subselect:focus{border-color:var(--cc-blue);outline:none;}
.cc-eq-loading{font-size:11px;color:var(--cc-muted);font-style:italic;}
.cc-eq-no-sub{font-size:11px;color:var(--cc-muted);font-style:italic;}
.cc-eq-empty{font-size:11px;color:var(--cc-muted);padding:4px 2px;}

/* --- Ported multi-link chips + modal (option-C ir-link-*/ir-modal-*, renamed cc-*) --- */
.cc-link-chips{display:flex;flex-wrap:wrap;gap:6px;min-height:32px;padding:6px 8px;border:1px solid var(--cc-border);border-radius:4px;background:var(--cc-bg);margin-top:8px;}
.cc-link-chip{display:inline-flex;align-items:center;gap:5px;background:var(--cc-row-odd);border:1px solid var(--cc-border);border-radius:12px;padding:2px 6px 2px 9px;font-size:11px;font-weight:500;color:var(--cc-blue);}
.cc-link-chip button{background:none;border:none;cursor:pointer;color:var(--cc-muted);padding:0;line-height:1;font-size:14px;display:flex;align-items:center;}
.cc-link-chip button:hover{color:#E24B4A;}
.cc-link-empty{font-size:11px;color:var(--cc-muted);padding:4px 2px;}
.cc-link-label{font-size:11px;font-weight:600;color:var(--cc-mid);margin-bottom:5px;margin-top:8px;}
.cc-link-results{border-collapse:collapse;width:100%;font-size:11px;}
.cc-link-results th{background:var(--cc-blue);color:#fff;font-weight:500;padding:5px 8px;text-align:left;border-bottom:2px solid var(--cc-gold);}
.cc-link-results td{padding:6px 8px;border-bottom:1px solid var(--cc-border);vertical-align:middle;}
.cc-link-results tbody tr:hover td{background:var(--cc-row-odd);}
.cc-link-no{font-family:var(--cc-sans);font-weight:600;color:var(--cc-blue);}
.cc-link-muted{color:var(--cc-muted);}
.cc-lvl{display:inline-block;font-size:10px;font-weight:700;border-radius:3px;padding:1px 5px;}
.cc-lvl-0{background:#F3F4F6;color:#6B7280;} .cc-lvl-1{background:#E8F5EE;color:#0F6E4E;}
.cc-lvl-2{background:#EAF2FB;color:#0C447C;} .cc-lvl-3{background:#FAEEDA;color:#854F0B;}
.cc-lvl-4{background:#FCEBEB;color:#A32D2D;}
.cc-modal-backdrop{position:fixed;inset:0;background:rgba(16,24,40,.38);z-index:100000;display:none;align-items:center;justify-content:center;}
.cc-modal-backdrop.open{display:flex;}
.cc-modal-box{background:var(--cc-white);border-radius:10px;overflow:hidden;width:600px;max-width:96vw;max-height:82vh;display:flex;flex-direction:column;box-shadow:0 8px 40px rgba(0,30,80,.20);}
.cc-modal-head{background:var(--cc-blue);border-bottom:3px solid var(--cc-gold);padding:11px 16px;display:flex;align-items:center;justify-content:space-between;flex:none;}
.cc-modal-head h4{font-size:13px;font-weight:600;color:#fff;margin:0;}
.cc-modal-close{background:none;border:none;color:rgba(255,255,255,.7);cursor:pointer;font-size:20px;line-height:1;padding:0;}
.cc-modal-close:hover{color:var(--cc-gold);}
.cc-modal-body{padding:14px 16px;flex:1;overflow-y:auto;}
.cc-modal-foot{padding:11px 16px;border-top:1px solid var(--cc-border);background:var(--cc-bg);display:flex;align-items:center;justify-content:space-between;flex:none;}
.cc-modal-sel-count{font-size:11px;color:var(--cc-mid);font-weight:600;}
.cc-filter-tabs{display:flex;gap:4px;margin-bottom:10px;flex-wrap:wrap;}
.cc-filter-tab{font-size:11px;font-weight:500;padding:3px 9px;border-radius:4px;border:1px solid var(--cc-border);background:var(--cc-white);color:var(--cc-mid);cursor:pointer;}
.cc-filter-tab.active{background:var(--cc-blue);color:#fff;border-color:var(--cc-blue);}
.cc-result-scroll{max-height:240px;overflow-y:auto;border:1px solid var(--cc-border);border-radius:4px;}
.cc-input{height:28px;font-size:12px;font-family:var(--cc-sans);border:1px solid var(--cc-border);background:#fff;color:var(--cc-dark);border-radius:4px;padding:0 8px;}
.cc-editor-note{font-size:10px;color:var(--cc-muted);font-style:italic;margin-top:6px;}
.cc-picker-badge{display:inline-block;font-size:11px;font-weight:600;border-radius:10px;padding:2px 9px;margin-left:6px;background:var(--cc-row-odd);color:var(--cc-blue);}
.cc-picker-badge-empty{background:var(--cc-bg);color:var(--cc-muted);}

</style>



<!-- orig javascrip    -->

<body>


<div id="freeow" class="freeow freeow-bottom-right"></div>
<div class="ta-grid ta-console">
<?php
if($IR_EMBED){ ob_start(); }
?>
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



if($IR_EMBED){ ob_end_clean(); }
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
		
		$incident_time=date("Y-m-d H:ia",strtotime($row['incident_date']));
		if($row['resolution_date']==""){
			$resolution_time="";
		}
		else {
			$resolution_time=date("Y-m-d H:ia",strtotime($row['resolution_date']));
		}

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

		/* -- Read-back: multi-equipment (incident_equipment junction) --------
		   Mirrors the write side in incident_report.php. Each row is one
		   equipment item plus its own sub-item. Built into two parallel
		   structures: $equipment_rows_display for the read-only property-sheet
		   listing, and $existing_eq_pairs (equipt_id:subitem_id, comma-joined)
		   to seed the editor's chips as pre-existing selections so the modal
		   opens showing the current set rather than empty. ------------------- */
		$equipment_rows_display="";
		$existing_eq_pairs="";
		$eqJoinSQL="select ie.equipt_id, ie.subitem_id, e.equipment_name, s.sub_item
		            from incident_equipment ie
		            left join equipment e on e.id=ie.equipt_id
		            left join sub_item s on s.id=ie.subitem_id
		            where ie.incident_id='".$incident_report."'
		            order by e.equipment_name";
		$eqJoinRS=$db->query($eqJoinSQL);
		if($eqJoinRS){
			$eqPairsArr=array();
			while($eqRow=$eqJoinRS->fetch_assoc()){
				$eqName=($eqRow['equipment_name']!==null && $eqRow['equipment_name']!=='')
					? $eqRow['equipment_name'] : "(unnamed - id ".$eqRow['equipt_id'].")";
				$subName=($eqRow['subitem_id']*1>0 && $eqRow['sub_item']!==null && $eqRow['sub_item']!=='')
					? $eqRow['sub_item'] : "<span style='color:#8A95A6;font-style:italic'>none</span>";
				$equipment_rows_display.="<tr><td>".$eqName."</td><td>".$subName."</td></tr>";
				$eqPairsArr[]=$eqRow['equipt_id'].":".($eqRow['subitem_id']*1>0 ? $eqRow['subitem_id'] : "");
			}
			$existing_eq_pairs=implode(",",$eqPairsArr);
		}

		/* -- Read-back: multi-link (incident_linked_reports junction) --------
		   $linked_rows_display lists each linked incident (each still a
		   click-through to its own edit_ccdr). $existing_link_ids seeds the
		   link editor's chips. Falls back to nothing extra here; the legacy
		   single linked_to is still read separately above as $link_no. ------ */
		$linked_rows_display="";
		$existing_link_ids="";
		$existing_link_labels="";
		$linkJoinSQL="select ilr.linked_to, ir.incident_no
		              from incident_linked_reports ilr
		              left join incident_report ir on ir.id=ilr.linked_to
		              where ilr.incident_id='".$incident_report."'
		              order by ir.incident_no";
		$linkJoinRS=$db->query($linkJoinSQL);
		if($linkJoinRS){
			$linkIdsArr=array();
			$linkLabelsArr=array();
			while($lnkRow=$linkJoinRS->fetch_assoc()){
				$lnkNo=($lnkRow['incident_no']!==null && $lnkRow['incident_no']!=='')
					? $lnkRow['incident_no'] : ("ID ".$lnkRow['linked_to']);
				$linked_rows_display.="<div class='cc-linked-item'>See <a href='#' onclick='window.open(\"edit_ccdr.php?ir=".$lnkRow['linked_to']."\",\"_blank\")'>".$lnkNo."</a></div>";
				$linkIdsArr[]=$lnkRow['linked_to'];
				$linkLabelsArr[]=$lnkNo;
			}
			$existing_link_ids=implode(",",$linkIdsArr);
			/* JS-safe label list for seeding chips (pipe-delimited, matches ids order) */
			$existing_link_labels=implode("|",$linkLabelsArr);
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
/* Embed diagnostic (temporary): shows what $ULev the iframe request actually
   carries. Open the panel, right-click inside it -> View frame source, and
   look for this comment just above the details table. Remove once confirmed. */
if($IR_EMBED){ echo "<!-- embed diag: ULev=" . (isset($ULev) ? var_export($ULev,true) : "(unset)") . " -->"; }
if ($ULev>=2){
	$SRemove = "";
} else {
	$SRemove = "disabled";
}
/* Per-field Edit links use the pill treatment and, matching the decision
   made for clearance_form.php, are always available regardless of $ULev.
   The permission-gated $SRemove is still used elsewhere; $SRemove4 is the
   always-on pill class for the property-sheet Edit links. */
$SRemove4 = "cc-edit-pill";
?>

<!-- table for Control -->

<div class="alink">

<table width=70% class='ccdr'>
<tr id='ccdr_heading'><th colspan=3 style=text-align:center>Incident Details</th></tr>
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

<tr><th>Equipment Involved</th>
<td>
<?php
/* Multi-equipment read-back listing (incident_equipment). Replaces the
   former single On-board Equipt display + legacy Additional Defects table.
   Each row: equipment name + its sub-item. */
if($equipment_rows_display!==""){
	echo "<table class='cc-eq-list' width=90%>";
	echo "<tr><th>Equipment</th><th>Sub-item</th></tr>";
	echo $equipment_rows_display;
	echo "</table>";
} else {
	echo "<span class='cc-none-note'>No equipment recorded</span>";
}
?>
</td>
<td align="center"><a href='#edit_form' class="<?php echo $SRemove4; ?>" onclick='ccEqOpenEditor()'>Edit</a>
	<span id="cc-eq-badge" class="cc-picker-badge cc-picker-badge-empty">none yet</span></td>
</tr>


<tr>
<th>Linked Incident(s)</th>
<td>
<?php
/* Multi-link read-back listing (incident_linked_reports). Replaces the
   former single "Linked Incident (?)" row. */
if($linked_rows_display!==""){
	echo $linked_rows_display;
} else {
	echo "<span class='cc-none-note'>No linked incidents</span>";
}
?>
</td>
<td align="center"><a href='#edit_form' class="<?php echo $SRemove4; ?>" onclick='ccLinkOpenEditor()'>Edit</a>
	<span id="cc-link-badge" class="cc-picker-badge cc-picker-badge-empty">none yet</span></td>
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

<tr><th>Incident Date/Time</th><td><?php echo $incident_time; ?></td><td align="center"><a href='#edit_form'  class="<?php echo $SRemove; ?>" onclick='fillEdit("date")'>Edit</a></td></tr>
<tr><th>Time Resolved</th><td><?php echo $resolution_time; ?></td><td align="center"><a href='#edit_form'  class="<?php echo $SRemove; ?>" onclick='fillEdit("resolution_date")'>Edit</a></td></tr>
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
<tr><th>Maintenance Provider (TESP/Other)</th><td><?php echo $maintenance_action; ?></td><td align="center">&nbsp;</td></tr>

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
				<button type="button" class="close" data-dismiss="modal" onclick="ccEditModalHide();" aria-label="Close">&times;</button>
				<h3>Edit CCDR Field</h3>
			</div>
<form id='edit_form' name='edit_form' action='edit_ccdr.php?ir=<?php echo $incident_report; if($IR_EMBED){ echo "&embed=1"; } ?>'  method='post'>

			<div class="modal-body">	
				<table id='edit_table' name='edit_table' width=80%>	
				</table>
				<table width=80%>
				<!-- Incident reference: shown as static read-only text (the user
				     never edits it), with the actual id carried in ONE hidden
				     input. Replaces the previous disabled text input (which the
				     browser never submits) plus a parallel hidden inc_report --
				     the save handler reads inc_report, so that single hidden
				     field below is the one authoritative source now.

				     id='incident_report' is kept on this hidden input (even
				     though the visible display above no longer uses that id)
				     because fillEdit()'s "index" branch -- used by both Index
				     Number and Car Numbers -- and its "additional_defects"
				     branch call document.getElementById('incident_report').value
				     to fetch the id before an AJAX call. Without the id here,
				     that lookup returns null and throws, which silently aborts
				     fillEdit() before it ever reaches $('#addModal').modal('show'),
				     so the modal never opens -- exactly the "Index/Car edit
				     doesn't work" symptom. Keeping the id on this element (now
				     the sole authoritative source) fixes it with no JS changes. -->
					 <?PHP
					 /**
					 
					 
				<tr><th width=20%>Incident</th><td>
					<span style="font-weight:600;color:var(--cc-blue);"><?php echo $incident_no; ?></span>
					<span style="color:var(--cc-muted);font-size:11px;margin-left:6px;">(ID <?php echo $incident_report; ?>)</span>
				</td></tr>

*/ ?>				
				</table>
				<br>
				<input type="hidden" name='fieldType' id='fieldType' />
				<input type="hidden" name="inc_report" id="incident_report" value='<?php echo $incident_report; ?>' />


				
			</div>
						
			<div class="modal-footer">
				<a href="#" class="btn" data-dismiss="modal" onclick="ccEditModalHide();return false;">Close</a>
				<button type='submit' class="btn btn-primary" id='Suc' value='Submit'>Edit </button>
			</div>
			  </form>
		</div>

<!-- ----------- Multi-equipment editor (ported from incident_report option-C) ----------- -->
<div class="cc-modal-backdrop" id="cc-eq-modal">
	<div class="cc-modal-box">
		<div class="cc-modal-head">
			<h4>Edit Equipment Involved</h4>
			<button class="cc-modal-close" type="button" onclick="ccEqCloseEditor()">&times;</button>
		</div>
		<div class="cc-modal-body">
			<div style="display:flex;gap:7px;margin-bottom:6px;">
				<input type='text' class="cc-input" id='cc-eq-search-input' style="flex:1"
					placeholder="Search equipment..." oninput='ccEqFilterInput(this.value)' autocomplete="off" />
				<input type='button' value='Browse' onclick='ccEqTogglePanel()' />
			</div>
			<div class="cc-eq-panel" id="cc-eq-panel" style="display:none;">
				<div class="cc-eq-panel-head">Tick equipment to add, then click Add Selected</div>
				<div class="cc-eq-panel-body" id="cc-eq-list"></div>
				<div class="cc-eq-panel-foot">
					<input type='button' value='Cancel' onclick='document.getElementById("cc-eq-panel").style.display="none"' />
					<input type='button' value='Add selected ✓' onclick='ccEqAddSelected()'
						style="background:var(--cc-blue);color:#fff;border-color:var(--cc-blue);" />
				</div>
			</div>
			<div class="cc-link-label">Selected equipment (each with its own sub-item)</div>
			<div class="cc-eq-chips" id="cc-eq-chips">
				<span class="cc-eq-empty">No equipment selected</span>
			</div>
			<!-- No longer its own <form>: this incident's equipment set now
			     rides along with whatever edit_form submission happens next
			     (a single-field edit elsewhere, or the standalone "Save
			     Equipment & Linked Incidents" button), via form="edit_form".
			     fieldType/inc_report hidden fields removed -- no longer
			     needed, the server now keys off equipment_ids being present
			     rather than a specific fieldType value.

			     equipment_ids travels as a REAL PHP array now, not one
			     joined string: ccEqSyncHidden() (re)populates this
			     container with one equipment_ids[] hidden input per
			     equipment item (each "id:subitem"), all form="edit_form".
			     The server's is_array($_POST['equipment_ids']) branch
			     already expected exactly this shape -- it just never
			     received it before. -->
			<div id="cc-eq-ids-container"></div>
			<div class="cc-editor-note">Selections here are saved to the form as you make them -- Submit persists them, same as editing any other field.</div>
		</div>
		<div class="cc-modal-foot">
			<span class="cc-modal-sel-count" id="cc-eq-sel-note">&nbsp;</span>
			<div style="display:flex;gap:8px;">
				<input type='button' value='Close' onclick='ccEqCloseEditor()' />
				<button type='submit' form='edit_form' class="btn btn-primary"
					onclick="document.getElementById('fieldType').value='';"
					style="background:var(--cc-blue);color:#fff;border:1px solid var(--cc-blue);border-radius:4px;padding:4px 14px;cursor:pointer;">Submit</button>
			</div>
		</div>
	</div>
</div>

<!-- ----------- Multi-link editor (ported from incident_report option-C) ----------- -->
<div class="cc-modal-backdrop" id="cc-link-modal">
	<div class="cc-modal-box">
		<div class="cc-modal-head">
			<h4>Edit Linked Incident Report(s)</h4>
			<button class="cc-modal-close" type="button" onclick="ccLinkCloseEditor()">&times;</button>
		</div>
		<div class="cc-modal-body">
			<div style="display:flex;gap:7px;margin-bottom:8px;">
				<input type='text' id='cc-link-search-input' class="cc-input" style="flex:1"
					placeholder="Search by incident no., type..." oninput='ccLinkFilterSearch(this.value)' autocomplete="off" />
				<input type='button' value='Clear' onclick='document.getElementById("cc-link-search-input").value="";ccLinkFilterSearch("")' />
			</div>
			<div class="cc-filter-tabs" id="cc-link-tabs">
				<button class="cc-filter-tab active" type="button" onclick="ccLinkSetTab(this,'today')">Today</button>
				<button class="cc-filter-tab" type="button" onclick="ccLinkSetTab(this,'all')">All (date desc)</button>
				<button class="cc-filter-tab" type="button" onclick="ccLinkSetTab(this,'rolling')">Rolling Stock</button>
				<button class="cc-filter-tab" type="button" onclick="ccLinkSetTab(this,'power')">Power</button>
				<button class="cc-filter-tab" type="button" onclick="ccLinkSetTab(this,'l3')">Level 3+</button>
			</div>
			<div class="cc-result-scroll">
				<table class="cc-link-results">
					<thead><tr>
						<th style="width:28px"></th><th>Incident No.</th><th>Type</th><th>Lvl</th><th>Date</th><th>Index</th>
					</tr></thead>
					<tbody id="cc-link-tbody"></tbody>
				</table>
			</div>
			<div class="cc-link-label">Currently linked</div>
			<div class="cc-link-chips" id="cc-link-chips">
				<span class="cc-link-empty">No incidents linked yet</span>
			</div>
			<!-- No longer its own <form>: see the equivalent note in the
			     equipment editor above -- same reasoning, same mechanism.
			     incident_links travels as a real PHP array too now. -->
			<div id="cc-link-ids-container"></div>
			<div class="cc-editor-note">Selections here are saved to the form as you make them -- Submit persists them, same as editing any other field.</div>
		</div>
		<div class="cc-modal-foot">
			<span class="cc-modal-sel-count" id="cc-link-sel-count">0 selected</span>
			<div style="display:flex;gap:8px;">
				<input type='button' value='Close' onclick='ccLinkCloseEditor()' />
				<button type='submit' form='edit_form' class="btn btn-primary"
					onclick="document.getElementById('fieldType').value='';"
					style="background:var(--cc-blue);color:#fff;border:1px solid var(--cc-blue);border-radius:4px;padding:4px 14px;cursor:pointer;">Submit</button>
			</div>
		</div>
	</div>
</div>



<?php	
if(isset($_POST['submit'])){	
	if ($Mup==1) {		
	if($IR_EMBED){ echo "<script>parent.postMessage('sp:saved','*');</script>"; }
	else { echo "<script typ=javascript> sampleFreeow();</script>"; }
	$Mup=0;
	}
}
	?>

<!-- --- Multi-equipment / multi-link editor bootstrap (seed data + logic) --- -->
<script type="text/javascript">
/* PHP-emitted seed data: the incident's existing junction-table rows, so the
   editors open pre-populated with the current set rather than empty. */
var ccSelfIncidentId = '<?php echo (int)$incident_report; ?>';
var ccProblemType = '<?php echo isset($problem_type2) ? $problem_type2 : ""; ?>';
var ccExistingEqPairs = '<?php echo isset($existing_eq_pairs) ? $existing_eq_pairs : ""; ?>';
var ccExistingLinks = '<?php echo isset($existing_link_ids) ? $existing_link_ids : ""; ?>';
var ccExistingLinkLabels = <?php echo isset($existing_link_labels) ? json_encode($existing_link_labels) : "''"; ?>;
/* Equipment display names keyed by id, so seeded chips show real names.
   Built from the same incident_equipment read-back. */
var ccExistingEqNames = {};
<?php
if(isset($existing_eq_pairs) && $existing_eq_pairs!==""){
    $seedDb = new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
    foreach(explode(",",$existing_eq_pairs) as $seedPair){
        $seedParts = explode(":",$seedPair);
        $seedEqId = (int)$seedParts[0];
        if($seedEqId<=0) continue;
        $seedRs = $seedDb->query("select equipment_name from equipment where id='".$seedEqId."' limit 1");
        $seedRow = $seedRs ? $seedRs->fetch_assoc() : null;
        $seedName = ($seedRow && $seedRow['equipment_name']!=="") ? $seedRow['equipment_name'] : ("Equipment ".$seedEqId);
        echo "ccExistingEqNames['".$seedEqId."'] = ".json_encode($seedName).";
";
    }
}
?>
</script>
<script type="text/javascript">
/* -----------------------------------------------------------------------
   edit_ccdr - multi-equipment & multi-link EDITORS
   Ported from incident_report.php (option C), with two edit-side additions
   the create side never needed:
     1. cc-prefixed identifiers, so nothing collides with the many existing
        functions/ids already in edit_ccdr.php (fillEdit, fillEquipt, etc).
     2. Openers that PRE-SEED chips from the incident's existing junction-
        table rows (emitted by PHP into ccExistingEqPairs / ccExistingLinks
        below), so each editor opens showing the current set, not empty.
   The picker/search/subitem mechanics themselves are unchanged from the
   verified option-C source, including the serialized sub-item queue and
   the well-formedness diagnostics.
   ----------------------------------------------------------------------- */

/* -- MULTI-EQUIPMENT --------------------------------------------------- */
var ccEqSelected={};
var ccEqLinked={};          /* id ? equipment name */
var ccEqSubItemChoice={};   /* id ? chosen subitem_id ('' if none yet) */
var ccEqPendingQueue=[];    /* FIFO of equipt_ids whose scrollSubItem fetch is in flight */
var ccEqSearchResults=[];
var ccEqSearchCallback=null;
var ccEqSeeded=false;       /* seed existing rows only once per page load */

function ccEqOpenEditor(){
	document.getElementById('cc-eq-modal').classList.add('open');
	if(!ccEqSeeded){
		ccEqSeedExisting();
		ccEqSeeded=true;
	}
}
function ccEqCloseEditor(){
	document.getElementById('cc-eq-modal').classList.remove('open');
}

/* Seed chips from PHP-emitted existing pairs "equipt_id:subitem_id,...".
   Each seeded chip fetches its sub-item list (so the dropdown is populated)
   and then its previously-saved sub-item is re-selected once that list
   arrives (handled in ccEqRenderSubItemSelect via ccEqSubItemChoice). */
function ccEqSeedExisting(){
	if(typeof ccExistingEqPairs==='undefined' || !ccExistingEqPairs) return;
	var pairs=ccExistingEqPairs.split(',');
	var ids=[];
	pairs.forEach(function(pair){
		pair=pair.trim(); if(pair==='') return;
		var parts=pair.split(':');
		var eqId=String(parseInt(parts[0],10));
		if(!eqId || eqId==='NaN') return;
		var subId=(parts.length>1)?parts[1]:'';
		var label=(typeof ccExistingEqNames!=='undefined' && ccExistingEqNames[eqId])
			? ccExistingEqNames[eqId] : ('Equipment '+eqId);
		ccEqLinked[eqId]=label;
		ccEqSubItemChoice[eqId]=subId; /* remember prior choice; applied on render */
		ccEqRenderChip(eqId,label);
		ids.push(eqId);
	});
	ccEqSyncHidden();
	ccEqPendingQueue=ids.slice();
	ccEqFetchNextInQueue();
}

function ccEqSearch(q,cb){
	ccEqSearchCallback=cb;
	makeajax("processing.php?probname="+encodeURIComponent(ccProblemType)+"&searchEquipment="+encodeURIComponent(q),"ccEqSearchResponse");
}

function ccEqSearchResponse(ajaxHTML){
	var results=[];
	var looksWellFormed = (ajaxHTML==="No data available") || (ajaxHTML==="") ||
		(/^(\d+;[^;]*;[^;]*==>)+$/.test(ajaxHTML));
	if(!looksWellFormed){
		console.error('[ccEqSearchResponse] Unexpected response from processing.php?searchEquipment=. Raw:');
		console.error(ajaxHTML);
		var escaped=String(ajaxHTML).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
		document.getElementById('cc-eq-list').innerHTML=
			'<div style="padding:9px 11px;font-size:11px;color:#A32D2D;line-height:1.5;">'
			+'<strong>Equipment search did not return usable data.</strong><br>'
			+'<pre style="background:#FDF2F2;border:1px solid #DDB5B3;border-radius:4px;padding:8px;'
			+'margin-top:6px;white-space:pre-wrap;word-break:break-word;font-family:monospace;'
			+'font-size:11px;color:#7A1F1F;max-height:160px;overflow-y:auto;">'
			+(escaped===''?'(empty response)':escaped)+'</pre></div>';
		ccEqSearchResults=[];
		return;
	}
	if(ajaxHTML!=="No data available" && ajaxHTML!==""){
		var rows=ajaxHTML.split("==>");
		var count=rows.length-1;
		for(var n=0;n<count;n++){
			var parts=rows[n].split(";");
			if(!parts[0] || isNaN(parseInt(parts[0],10))){
				console.warn('[ccEqSearchResponse] Skipping row with non-numeric id:',rows[n]);
				continue;
			}
			results.push({id:parts[0],name:parts[1]||'',category:parts[2]||""});
		}
	}
	ccEqSearchResults=results;
	if(ccEqSearchCallback) ccEqSearchCallback(results);
}

function ccEqTogglePanel(){
	var p=document.getElementById('cc-eq-panel');
	var open=p.style.display!=='none';
	p.style.display=open?'none':'block';
	if(!open){ ccEqSearch('',ccEqRenderList); }
}
function ccEqFilterInput(q){
	ccEqSearch(q,ccEqRenderList);
	document.getElementById('cc-eq-panel').style.display='block';
}
function ccEqRenderList(data){
	var html='';
	data.forEach(function(r){
		var chk=ccEqSelected[String(r.id)]?'checked':'';
		var displayName=r.name && r.name.trim()!==''?r.name:'(unnamed - id '+r.id+')';
		var displayNameEsc=displayName.replace(/'/g,"\\'");
		html+='<label class="cc-eq-cb-row">'
			+'<input type="checkbox" value="'+r.id+'" '+chk
			+' onchange="ccEqToggle(\''+r.id+'\',\''+displayNameEsc+'\',this.checked)">'
			+'<span class="cc-eq-name"'+(displayName.indexOf('unnamed')>=0?' style="color:var(--cc-muted);font-style:italic;"':'')+'>'+displayName+'</span>'
			+'<span class="cc-eq-cat">'+r.category+'</span>'
			+'</label>';
	});
	document.getElementById('cc-eq-list').innerHTML=html||
		'<div style="padding:9px 11px;font-size:11px;color:var(--cc-muted)">No matches</div>';
}
function ccEqToggle(id,name,checked){
	id=String(id);
	if(checked) ccEqSelected[id]=name; else delete ccEqSelected[id];
}
function ccEqAddSelected(){
	var ids=Object.keys(ccEqSelected);
	ids.forEach(function(id){ ccEqAddChip(id,ccEqSelected[id]); });
	document.getElementById('cc-eq-panel').style.display='none';
	ccEqSelected={};
	ccEqPendingQueue=ccEqPendingQueue.concat(ids);
	if(ccEqPendingQueue.length===ids.length) ccEqFetchNextInQueue();
}
function ccEqFetchNextInQueue(){
	if(ccEqPendingQueue.length===0) return;
	var id=ccEqPendingQueue[0]; /* peek; response shifts */
	makeajax("processing.php?scrollSubItem="+id,"ccEqSubItemResponse");
}
function ccEqAddChip(id,label){
	id=String(id);
	if(ccEqLinked[id]) return;
	ccEqLinked[id]=label;
	if(ccEqSubItemChoice[id]===undefined) ccEqSubItemChoice[id]='';
	ccEqRenderChip(id,label);
	ccEqSyncHidden();
}
/* DOM-only chip render, shared by seed + add so both look identical */
function ccEqRenderChip(id,label){
	var chips=document.getElementById('cc-eq-chips');
	var empty=chips.querySelector('.cc-eq-empty');
	if(empty) chips.removeChild(empty);
	if(document.getElementById('cc-eq-card-'+id)) return;
	var card=document.createElement('div');
	card.className='cc-eq-card'; card.id='cc-eq-card-'+id;
	card.innerHTML=
		'<div class="cc-eq-card-head">'
			+'<span class="cc-eq-card-name">'+label+'</span>'
			+'<button type="button" onclick="ccEqRemoveChip(\''+id+'\')" title="Remove">&times;</button>'
		+'</div>'
		+'<div class="cc-eq-card-sub" id="cc-eq-sub-'+id+'">'
			+'<span class="cc-eq-loading">Loading sub-items...</span>'
		+'</div>';
	chips.appendChild(card);
}
function ccEqSubItemResponse(ajaxHTML){
	var id=ccEqPendingQueue.shift();
	if(id===undefined) return;
	ccEqRenderSubItemSelect(id,ajaxHTML);
	ccEqFetchNextInQueue();
}
function ccEqRenderSubItemSelect(id,ajaxHTML){
	var target=document.getElementById('cc-eq-sub-'+id);
	if(!target) return;
	var looksWellFormed = (ajaxHTML==="No data available") || (ajaxHTML==="") ||
		(/^(\d+;[^;]*==>)+$/.test(ajaxHTML));
	var html;
	if(!looksWellFormed){
		console.error('[ccEqRenderSubItemSelect] Unexpected scrollSubItem response for '+id+'. Raw:');
		console.error(ajaxHTML);
		var escapedSub=String(ajaxHTML).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
		html='<div style="color:#A32D2D;font-size:11px;line-height:1.5;">Could not load sub-items:'
			+'<pre style="background:#FDF2F2;border:1px solid #DDB5B3;border-radius:4px;padding:6px;'
			+'margin-top:4px;white-space:pre-wrap;word-break:break-word;font-family:monospace;'
			+'font-size:10px;color:#7A1F1F;max-height:120px;overflow-y:auto;">'
			+(escapedSub===''?'(empty response)':escapedSub)+'</pre></div>';
	} else if(ajaxHTML==="No data available" || ajaxHTML===""){
		html='<span class="cc-eq-no-sub">No sub-items for this equipment</span>';
	} else {
		var rows=ajaxHTML.split("==>");
		var count=rows.length-1;
		var prior=String(ccEqSubItemChoice[String(id)]||'');
		html='<select class="cc-eq-subselect" onchange="ccEqSetSubItem(\''+id+'\',this.value)">'
			+'<option value="">Select sub-item...</option>';
		for(var n=0;n<count;n++){
			var parts=rows[n].split(";");
			var sel=(String(parts[0])===prior && prior!=='')?' selected':'';
			html+='<option value="'+parts[0]+'"'+sel+'>'+(parts[1]||'(unnamed)')+'</option>';
		}
		html+='</select>';
	}
	target.innerHTML=html;
}
function ccEqSetSubItem(id,subitemId){
	ccEqSubItemChoice[String(id)]=subitemId;
	ccEqSyncHidden();
}
function ccEqRemoveChip(id){
	id=String(id);
	var el=document.getElementById('cc-eq-card-'+id);
	if(el) el.remove();
	delete ccEqLinked[id];
	delete ccEqSubItemChoice[id];
	var chips=document.getElementById('cc-eq-chips');
	if(!chips.querySelector('.cc-eq-card'))
		chips.innerHTML='<span class="cc-eq-empty">No equipment selected</span>';
	ccEqSyncHidden();
}
function ccEqSyncHidden(){
	/* One equipment_ids[] hidden input per item ("id:subitem" each), not one
	   joined string -- $_POST['equipment_ids'] arrives as a real PHP array,
	   engaging the is_array() branch the server already has.

	   If ccEqLinked is empty (user removed everything), a plain marker
	   input (no [] -- just name="equipment_ids", empty value) is emitted
	   instead of nothing at all. Without it, isset($_POST['equipment_ids'])
	   would be false when the array is empty (PHP simply never receives
	   that key if zero equipment_ids[] inputs exist), and the server would
	   skip its delete-then-reinsert entirely -- silently failing to clear
	   equipment the user deliberately emptied out. The marker keeps
	   isset() true; the server's existing explode(',','') / empty() path
	   already handles an empty string correctly. */
	var container=document.getElementById('cc-eq-ids-container');
	container.innerHTML='';
	var ids=Object.keys(ccEqLinked);
	if(ids.length===0){
		var marker=document.createElement('input');
		marker.type='hidden'; marker.name='equipment_ids'; marker.value='';
		marker.setAttribute('form','edit_form');
		container.appendChild(marker);
	} else {
		ids.forEach(function(id){
			var input=document.createElement('input');
			input.type='hidden';
			input.name='equipment_ids[]';
			input.value=id+":"+(ccEqSubItemChoice[id]||'');
			input.setAttribute('form','edit_form');
			container.appendChild(input);
		});
	}
	ccEqUpdateCount();
}
function ccEqUpdateCount(){
	var n=Object.keys(ccEqLinked).length;
	document.getElementById('cc-eq-sel-note').textContent = n ? n+' selected' : '\u00A0';
	var badge=document.getElementById('cc-eq-badge');
	badge.textContent = n ? n+' selected' : 'none yet';
	badge.className = n ? 'cc-picker-badge' : 'cc-picker-badge cc-picker-badge-empty';
}

/* -- MULTI-LINK -------------------------------------------------------- */
var ccLinkSelected={};
var ccLinked={};            /* id ? incident_no label */
var ccLinkTabFilter='today';
var ccLinkSearchCallback=null;
var ccLinkSeeded=false;

function ccLinkOpenEditor(){
	document.getElementById('cc-link-modal').classList.add('open');
	ccLinkTabFilter='today';
	document.querySelectorAll('#cc-link-modal .cc-filter-tab').forEach(function(t){t.classList.remove('active');});
	document.querySelector('#cc-link-modal .cc-filter-tab').classList.add('active');
	document.getElementById('cc-link-search-input').value='';
	if(!ccLinkSeeded){
		ccLinkSeedExisting();
		ccLinkSeeded=true;
	}
	ccLinkFilterSearch('');
}
function ccLinkCloseEditor(){
	document.getElementById('cc-link-modal').classList.remove('open');
}
/* Seed chips from PHP-emitted existing ids + pipe-delimited labels */
function ccLinkSeedExisting(){
	if(typeof ccExistingLinks==='undefined' || !ccExistingLinks) return;
	var ids=ccExistingLinks.split(',');
	var labels=(typeof ccExistingLinkLabels!=='undefined' && ccExistingLinkLabels)
		? ccExistingLinkLabels.split('|') : [];
	ids.forEach(function(id,i){
		id=String(parseInt(id,10)); if(!id || id==='NaN') return;
		var label=labels[i]||('ID '+id);
		ccLinkAddChip(id,label);
	});
}
function ccLinkSearchIncidents(q,cb){
	ccLinkSearchCallback=cb;
	var scope=(ccLinkTabFilter==='all')?'all':'today';
	makeajax("processing.php?searchIncidents="+encodeURIComponent(q)+"&scope="+scope,"ccLinkSearchResponse");
}
function ccLinkSearchResponse(ajaxHTML){
	var results=[];
	if(ajaxHTML!=="No data available" && ajaxHTML!==""){
		var rows=ajaxHTML.split("==>");
		var count=rows.length-1;
		for(var n=0;n<count;n++){
			var parts=rows[n].split(";");
			results.push({id:parts[0],no:parts[1],type:parts[2],
				level:parseInt(parts[3],10)||0,date:parts[4],index_no:parts[5]||"",description:""});
		}
	}
	if(ccLinkSearchCallback) ccLinkSearchCallback(results);
}
function ccLinkSetTab(btn,key){
	document.querySelectorAll('#cc-link-modal .cc-filter-tab').forEach(function(t){t.classList.remove('active');});
	btn.classList.add('active');
	ccLinkTabFilter=key;
	ccLinkFilterSearch(document.getElementById('cc-link-search-input').value);
}
function ccLinkFilterSearch(q){
	ccLinkSearchIncidents(q,function(data){
		var filtered=data.filter(function(r){
			if(ccLinkTabFilter==='today'||ccLinkTabFilter==='all') return true;
			if(ccLinkTabFilter==='l3') return r.level>=3;
			return r.type.toLowerCase().indexOf(ccLinkTabFilter)>=0;
		});
		ccLinkRenderResults(filtered);
	});
}
function ccLinkLvlBadge(l){ return '<span class="cc-lvl cc-lvl-'+l+'">L'+l+'</span>'; }
function ccLinkRenderResults(data){
	var html='';
	var selfId=(typeof ccSelfIncidentId!=='undefined')?String(ccSelfIncidentId):'';
	data.forEach(function(r){
		if(selfId!=='' && String(r.id)===selfId) return; /* never offer to link an incident to itself */
		var chk=(ccLinked[String(r.id)]||ccLinkSelected[String(r.id)])?'checked':'';
		html+='<tr>'
			+'<td style="width:28px"><input type="checkbox" value="'+r.id+'" '+chk
			+" onchange=\"ccLinkToggle('"+r.id+"','"+r.no+"',this.checked)\""
			+' style="accent-color:var(--cc-blue)"></td>'
			+'<td class="cc-link-no">'+r.no+'</td>'
			+'<td>'+r.type+'</td>'
			+'<td>'+ccLinkLvlBadge(r.level)+'</td>'
			+'<td class="cc-link-muted" style="white-space:nowrap">'+r.date+'</td>'
			+'<td class="cc-link-no" style="font-size:10px">'+(r.index_no||'-')+'</td>'
			+'</tr>';
	});
	document.getElementById('cc-link-tbody').innerHTML=html||
		'<tr><td colspan="6" style="padding:12px;text-align:center;color:var(--cc-muted)">No matches</td></tr>';
}
function ccLinkToggle(id,no,checked){
	id=String(id);
	if(checked){
		ccLinkSelected[id]=no;
		ccLinkAddChip(id,no);      /* reflect into chips + hidden field immediately */
	} else {
		delete ccLinkSelected[id];
		ccLinkRemoveChip(id);
	}
	ccLinkUpdateCount();
}
function ccLinkUpdateCount(){
	var n=Object.keys(ccLinked).length; /* chips are the source of truth */
	document.getElementById('cc-link-sel-count').textContent=n+' linked';
	var badge=document.getElementById('cc-link-badge');
	badge.textContent = n ? n+' linked' : 'none yet';
	badge.className = n ? 'cc-picker-badge' : 'cc-picker-badge cc-picker-badge-empty';
}
function ccLinkAddChip(id,label){
	id=String(id);
	if(ccLinked[id]) return;
	ccLinked[id]=label;
	var chips=document.getElementById('cc-link-chips');
	var empty=chips.querySelector('.cc-link-empty');
	if(empty) chips.removeChild(empty);
	var chip=document.createElement('span');
	chip.className='cc-link-chip'; chip.id='cc-link-chip-'+id;
	chip.innerHTML=label+"<button type=\"button\" onclick=\"ccLinkRemoveChip('"+id+"')\" title=\"Remove\">&times;</button>";
	chips.appendChild(chip);
	ccLinkSyncHidden();
}
function ccLinkRemoveChip(id){
	id=String(id);
	var el=document.getElementById('cc-link-chip-'+id);
	if(el) el.remove();
	delete ccLinked[id];
	delete ccLinkSelected[id];
	/* untick the matching checkbox if it's currently rendered in the results */
	var cb=document.querySelector('#cc-link-tbody input[type=checkbox][value="'+id+'"]');
	if(cb) cb.checked=false;
	var chips=document.getElementById('cc-link-chips');
	if(!chips.querySelector('.cc-link-chip'))
		chips.innerHTML='<span class="cc-link-empty">No incidents linked yet</span>';
	ccLinkSyncHidden();
	ccLinkUpdateCount();
}
function ccLinkSyncHidden(){
	/* Same shape change and same empty-set marker fix as ccEqSyncHidden()
	   above -- see that comment for why the marker is necessary. */
	var container=document.getElementById('cc-link-ids-container');
	container.innerHTML='';
	var ids=Object.keys(ccLinked);
	if(ids.length===0){
		var marker=document.createElement('input');
		marker.type='hidden'; marker.name='incident_links'; marker.value='';
		marker.setAttribute('form','edit_form');
		container.appendChild(marker);
	} else {
		ids.forEach(function(id){
			var input=document.createElement('input');
			input.type='hidden';
			input.name='incident_links[]';
			input.value=id;
			input.setAttribute('form','edit_form');
			container.appendChild(input);
		});
	}
	ccLinkUpdateCount();
}

/* Backdrop-click + Esc close for both editors */
document.getElementById('cc-eq-modal').addEventListener('click',function(e){ if(e.target===this) ccEqCloseEditor(); });
document.getElementById('cc-link-modal').addEventListener('click',function(e){ if(e.target===this) ccLinkCloseEditor(); });
document.addEventListener('keydown',function(e){ if(e.key==='Escape'){ ccEqCloseEditor(); ccLinkCloseEditor(); } });

/* -- Seed both editors' hidden fields immediately, not just on first open --
   equipment_ids / incident_links now travel with EVERY edit_form submission
   (attached via form="edit_form"), including submissions that have nothing
   to do with either editor -- a plain Level edit, for instance. If those
   hidden fields were only ever populated the first time their own modal was
   opened, a user who edits an unrelated field without ever opening the
   equipment/link editors would submit them empty, and the server-side
   delete-then-reinsert would silently wipe out equipment/links they never
   touched this session. Seeding here, unconditionally, closes that gap. -- */
ccEqSeedExisting();
ccEqSeeded=true;
ccLinkSeedExisting();
ccLinkSeeded=true;

</script>

</body>
	<script src="js/jquery-migrate-1.2.1.min.js"></script>	
		<script src="js/jquery-ui-1.10.3.custom.min.js"></script>	
		<script src="js/jquery.ui.touch-punch.js"></script>	
		<script src="js/modernizr.js"></script>	
		<script src="js/bootstrap.min.js"></script>	
		

<script src="js/date.js"></script>	
<script src='js/form.js'></script>