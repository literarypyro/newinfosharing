<?php
/* =============================================================================
   HISTORY REPORT THEME -- shared Line 3 console styling for the incident
   history report pages: td_history.php, problem_history.php, other_history.php,
   car_history.php.

   This is the same visual language as ccdr_summary.php / edit_ccdr.php /
   train_operations.php (same --cf-* variable names and values), just scoped
   for a "single table report" layout instead of ccdr_summary's multi-panel
   dashboard. Centralizing it here means all four report pages stay visually
   in sync automatically -- update this one file instead of four copies.

   Include with:  <?php include("history_theme.php"); ?>
   right where the old per-page <style> block used to sit. No PHP logic in
   any of the four pages needs to change -- this only affects presentation.
   ============================================================================= */
?>
<link href="css/font-awesome.min.css" rel="stylesheet">
<link href="css/bootstrap.min.css" rel="stylesheet" />
<link href="css/bootstrap-responsive.min.css" rel="stylesheet" />
<link href="css/style.min.css" rel="stylesheet" />
<link href="css/style-responsive.min.css" rel="stylesheet" />
<link href="css/retina.css" rel="stylesheet" />
<link rel="stylesheet" type="text/css" href="css/dataTables.tableTools.css">

<style type="text/css">
:root {
	--cf-blue:      #00529B;
	--cf-blue-dark: #013E76;
	--cf-gold:      #FDB813;
	--cf-gold-ink:  #3A2D00;
	--cf-dark:      #16243B;
	--cf-mid:       #41506A;
	--cf-muted:     #8A95A6;
	--cf-border:    #D2DDEA;
	--cf-row-odd:   #EEF4FB;
	--cf-bg:        #F7F9FC;
	--cf-white:     #ffffff;
	--cf-red:       #A32D2D;
	--cf-red-bg:    #FCEBEB;
	--cf-sans:      "Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif;
}

body { margin:0; background:var(--cf-bg); color:var(--cf-dark); font-family:var(--cf-sans); }

.ccs-page { padding:24px 30px; font-family:var(--cf-sans); color:var(--cf-dark); }
.ccs-page * { box-sizing:border-box; }

/* ── Page header ── */
.ccs-header      { background:var(--cf-blue); border-bottom:3px solid var(--cf-gold); border-radius:6px 6px 0 0; padding:12px 16px; }
.ccs-header h1   { margin:0; font-size:16px; font-weight:700; color:#fff; letter-spacing:.3px; }
.ccs-header .sub { font-size:10px; color:rgba(255,255,255,.6); letter-spacing:.5px; text-transform:uppercase; margin-top:2px; }

/* ── Toolbar (search boxes, date filters, retrieve/submit buttons) ── */
.stat-toolbar { background:var(--cf-blue); padding:10px 16px; margin-bottom:0; width:100%; border-collapse:collapse; table-layout:auto; }
.stat-toolbar table { border-collapse:collapse; width:100%; }
.stat-toolbar th, .stat-toolbar td { border:none !important; padding:4px 8px; color:#fff; font-weight:600; font-size:13px; text-align:left; }
.stat-toolbar label { color:rgba(255,255,255,.85); font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.3px; margin-right:6px; }
.stat-toolbar select, .stat-toolbar input[type=text] {
	height:28px; border:1px solid rgba(255,255,255,.5); border-radius:4px;
	background:#fff !important; color:var(--cf-dark) !important; padding:0 8px; font-size:12px; font-family:var(--cf-sans);
}
.stat-toolbar input[type=text]:focus { color:var(--cf-dark) !important; background:#fff !important; }
.stat-toolbar input[type=submit] {
	height:30px; border:none; border-radius:4px; background:var(--cf-gold);
	color:var(--cf-gold-ink); font-weight:700; font-size:12px; padding:0 16px; cursor:pointer;
}
.stat-toolbar input[type=submit]:hover { background:#E5A50F; }

/* ── Report panel wrapping the table ── */
.ccs-panel { background:var(--cf-white); border:1px solid var(--cf-border); border-radius:0 0 6px 6px;
	box-shadow:0 1px 3px rgba(0,30,80,.08); overflow:hidden; margin-bottom:24px; }
.ccs-panel-head { background:#F1F5FB; border-bottom:1px solid var(--cf-border); padding:9px 14px; }
.ccs-panel-head h3 { margin:0; font-size:12px; font-weight:700; color:var(--cf-blue); letter-spacing:.4px; text-transform:uppercase; }
.ccs-panel-body { padding:14px; }

/* ── DataTables chrome (search/length/info/pagination controls) ── */
#add_form_wrapper .dataTables_length,
#add_form_wrapper .dataTables_filter { color:var(--cf-dark); }
#add_form_wrapper .dataTables_info    { color:var(--cf-muted); }
#add_form_wrapper .dataTables_length select,
#add_form_wrapper .dataTables_filter input {
	border:1px solid var(--cf-border); background:#fff; color:var(--cf-dark);
	border-radius:5px; padding:4px 8px;
}
#add_form_wrapper .dataTables_paginate .paginate_button {
	border:1px solid var(--cf-border) !important; background:#fff !important;
	color:var(--cf-dark) !important; border-radius:0;
}
#add_form_wrapper .dataTables_paginate .paginate_button.current,
#add_form_wrapper .dataTables_paginate .paginate_button.current:hover {
	background:var(--cf-gold) !important; color:var(--cf-gold-ink) !important; border-color:var(--cf-gold) !important;
}
#add_form_wrapper .dataTables_paginate .paginate_button.disabled,
#add_form_wrapper .dataTables_paginate .paginate_button.disabled:hover {
	color:var(--cf-muted) !important; background:#fff !important;
}

/* ── The report table itself (#add_form) ── */
#add_form { background:var(--cf-white); width:100% !important; }
#add_form th, #add_form td { border:1px solid var(--cf-border) !important; }

#add_form thead tr:first-child th {
	background:var(--cf-blue) !important; color:#fff !important;
	border:1px solid var(--cf-blue) !important;
	border-bottom:3px solid var(--cf-gold) !important;
	text-align:center; padding:10px; font-weight:600;
	position:sticky; top:0; z-index:2;
}
#add_form thead tr:nth-child(2) th {
	background:#F1EEE3 !important; color:var(--cf-dark) !important;
	border-bottom:2px solid var(--cf-blue) !important;
	text-align:left; padding:8px 10px; font-weight:600;
	position:sticky; top:40px; z-index:1;
}

#add_form tbody td                    { background:#fff !important; padding:8px 10px; vertical-align:top; font-size:12.5px; }
#add_form tbody tr:nth-child(even) td { background:var(--cf-row-odd) !important; }

#add_form a       { color:var(--cf-blue); font-weight:600; text-decoration:none; }
#add_form a:hover { color:var(--cf-blue-dark); text-decoration:underline; }

@media (max-width:900px){ .ccs-page { padding:16px; } }
</style>