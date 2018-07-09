$(function() {	
	//===== Dynamic data table =====//
$(".datatable2").dataTable({
	"sDom": "<'row-fluid'<'span6'l><'span6' \"H\"Tf>r>t<'row-fluid'<'span12'\"F\"i><'span12 center'p>>",
//	sDom:"<'row-fluid'<'span6'l><'span6'f>r>t<'row-fluid'<'span12'i><'span12 center'p>>",
	sPaginationType:"bootstrap", 

	"aaSorting": [[ 1, "desc" ]],

		"tableTools": {
			"aButtons": [
				{
					"sExtends": "copy",
					"sButtonText": "Copy to clipboard"
				},
				{
					"sExtends": "csv",
					"sButtonText": "Save to CSV",
					"sFileName": "stats_report_"+$.now()+".csv" 
				},
				{
					"sExtends": "xls",
					"oSelectorOpts": {
						page: 'current'
					},
					"sFileName": "stats_report_"+$.now()+".xls" 
				},
				"print"
			],
			"sSwfPath": "swf/copy_csv_xls_pdf.swf" 

		},
	oLanguage:{sLengthMenu:"_MENU_ records per page"}});
});	