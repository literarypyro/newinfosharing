$(function() {	

	var availableTags=["kiss","scorpions","def leppard"];
	$('#maintenance').typeahead({ 
		//source: availableTags,

		source: function (query, process) {
			return $.getJSON(
				"processing.php?search_preencoded=Y",
				{ query: query },
				function (data) {
					//data=availableTags;
					return process(data);
				});
		}
/*
		minLength:4
*/

	} );
});
