$(function() {	
	$('#reported_by').keyup(function(){
		this.value = this.value.toUpperCase();
	});
	
	$('.datepicker').datepicker();
	
	
});