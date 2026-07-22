<?php
require("Tmenu.php");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">    
    <link rel="stylesheet" type="text/css" href="jquery-easyui-1.4/themes/gray/easyui.css">
    <link rel="stylesheet" type="text/css" href="jquery-easyui-1.4/themes/icon.css">
    <link rel="stylesheet" type="text/css" href="jquery-easyui-1.4/themes/color.css">
    <link rel="stylesheet" type="text/css" href="jquery-easyui-1.4/demo/demo.css"> 
    <script type="text/javascript" src="media/js/jquery-1.11.1.min.js"></script>
    <script type="text/javascript" src="jquery-easyui-1.4/jquery.easyui.min.js"></script>

<style type="text/css">
/* =============================================================================
   TRANSPORT EMPLOYEES (indexAdd.php) -- Operations Console Theme
   Uniform with the rest of the redesigned system, integrating the three
   established lineages:
     - history_theme.php / car_history.php ... canonical --cf-* token set,
       blue/gold page-header band (.ccs-header treatment)
     - train_operations.php / train_availability.php ... console toolbar
       language: outlined pill buttons, gold-filled primary action
     - ccdr_summary.php / incident_summary.php ... rounded card panel with
       its own blue header bar, subtle shadow
   This page runs on jQuery EasyUI (datagrid + dialog + searchbox), so the
   EasyUI widgets are kept 100% functionally intact and only RE-SKINNED here:
   the datagrid panel doubles as the page's ccs-panel card, its title bar
   as the ccs-header band. PHP and JS below are completely unchanged.

   !important is used deliberately throughout: EasyUI's gray theme +
   color.css + demo.css all load BEFORE this block and set backgrounds via
   gradients and generic element rules with enough reach that plain
   same-specificity overrides don't reliably win (same situation as the
   bootstrap/style.min.css fights documented in clearance_form.php).
   ============================================================================= */
:root {
	--cf-blue:      #00529B;
	--cf-blue-dark: #013E76;
	--cf-gold:      #FDB813;
	--cf-gold-dk:   #E5A50F;
	--cf-gold-ink:  #3A2D00;
	--cf-dark:      #16243B;
	--cf-mid:       #41506A;
	--cf-muted:     #8A95A6;
	--cf-border:    #D2DDEA;
	--cf-row-odd:   #EEF4FB;
	--cf-row-hov:   #E3EEFA;
	--cf-row-sel:   #FFEFC2;
	--cf-bg:        #F7F9FC;
	--cf-white:     #ffffff;
	--cf-red:       #A32D2D;
	--cf-red-bg:    #FCEBEB;
	--cf-sans:      "Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif;
}

body.DBody {
	background: var(--cf-bg) !important;
	font-family: var(--cf-sans) !important;
	color: var(--cf-dark) !important;
}

/* Page content container (below the shared menu) -- replaces the old
   <br><br> spacers with real margins, same rhythm as .ccs-page. */
.cf-page { margin: 20px 26px; }
.cf-page * { box-sizing: border-box; }

/* -- Back link: outlined console pill (was a bare a.two + <font Century>) -- */
.cf-page .cf-back {
	display: inline-block;
	font-family: var(--cf-sans);
	font-size: 12px;
	font-weight: 600;
	color: var(--cf-blue);
	text-decoration: none;
	background: var(--cf-white);
	border: 1px solid var(--cf-border);
	border-radius: 4px;
	padding: 5px 12px;
	margin-bottom: 14px;
}
.cf-page .cf-back:hover {
	color: var(--cf-blue-dark);
	border-color: var(--cf-blue);
	background: var(--cf-row-odd);
	text-decoration: none;
}

/* =============================================================================
   EasyUI re-skin
   ============================================================================= */

/* -- Panel card (datagrid AND dialog both render as .panel) --
   ccs-panel / ccdr_summary card treatment: rounded, clipped, soft shadow. */
.cf-page .panel {
	border-radius: 8px;
	overflow: hidden;
	box-shadow: 0 2px 6px rgba(22,36,59,.10);
}
.cf-page .panel-body {
	border-color: var(--cf-border) !important;
	background: var(--cf-white);
	font-family: var(--cf-sans);
}

/* -- Panel/dialog title bar = the ccs-header band: blue, gold underline -- */
.cf-page .panel-header {
	background: var(--cf-blue) !important;
	border: 1px solid var(--cf-blue) !important;
	border-bottom: 3px solid var(--cf-gold) !important;
	padding: 9px 14px !important;
}
.cf-page .panel-title {
	color: #fff !important;
	font-family: var(--cf-sans) !important;
	font-size: 14px !important;
	font-weight: 700 !important;
	letter-spacing: .3px;
	height: auto !important;
	background: none !important;
}
/* dialog close (X) tool: dark sprite -> light so it reads on the blue band */
.cf-page .panel-tool a { filter: invert(1) brightness(1.8); opacity: .85; }
.cf-page .panel-tool a:hover { opacity: 1; background: transparent !important; }

/* -- Datagrid toolbar strip (New/Edit/Remove/Refresh + search) -- */
.cf-page .datagrid-toolbar {
	background: var(--cf-bg) !important;
	border-bottom: 1px solid var(--cf-border) !important;
	padding: 6px 10px !important;
}
#toolbar { overflow: hidden; }
#toolbar .searchbox { float: right; margin: 2px 2px 0 0; }

/* -- Linkbuttons: console pill treatment (train_operations lineage) -- */
.cf-page .l-btn {
	background: var(--cf-white) !important;
	border: 1px solid var(--cf-border) !important;
	border-radius: 4px !important;
}
.cf-page .l-btn:hover {
	background: var(--cf-row-odd) !important;
	border-color: var(--cf-blue) !important;
}
.cf-page .l-btn-text {
	color: var(--cf-blue) !important;
	font-family: var(--cf-sans) !important;
	font-size: 12px !important;
	font-weight: 600 !important;
}
.cf-page .l-btn-disabled,
.cf-page .l-btn-disabled:hover {
	background: var(--cf-white) !important;
	border-color: var(--cf-border) !important;
	opacity: .45 !important;
}
/* primary action (Save, class c2): gold fill, same as .cf-tbtn--primary /
   Generate Printout treatment on the console pages */
.cf-page .l-btn.c2 {
	background: var(--cf-gold) !important;
	border-color: var(--cf-gold) !important;
}
.cf-page .l-btn.c2:hover {
	background: var(--cf-gold-dk) !important;
	border-color: var(--cf-gold-dk) !important;
}
.cf-page .l-btn.c2 .l-btn-text { color: var(--cf-gold-ink) !important; }

/* pager arrows stay quiet -- relax the pill treatment inside the pagination
   bar so it doesn't read as a wall of buttons */
.cf-page .pagination .l-btn {
	background: transparent !important;
	border: 1px solid transparent !important;
}
.cf-page .pagination .l-btn:hover {
	background: var(--cf-row-odd) !important;
	border-color: var(--cf-border) !important;
}

/* -- Column headers: blue band, white 11px labels (train_ava th treatment) -- */
.cf-page .datagrid-header,
.cf-page .datagrid-header td {
	background: var(--cf-blue) !important;
	border-color: #0A639E !important;
}
.cf-page .datagrid-header .datagrid-cell {
	color: #fff !important;
	font-family: var(--cf-sans) !important;
	font-size: 11px !important;
	font-weight: 600 !important;
	text-align: center !important;
}
/* sort arrows are a dark sprite -> invert so they show on blue */
.cf-page .datagrid-sort-icon { filter: invert(1); }

/* -- Data rows: striped / hover / gold-tinted selection -- */
.cf-page .datagrid-body td { border-color: var(--cf-border) !important; }
.cf-page .datagrid-cell {
	font-family: var(--cf-sans);
	font-size: 12px;
	color: var(--cf-dark);
}
.cf-page .datagrid-row-alt { background: var(--cf-row-odd) !important; }
.cf-page .datagrid-row-over { background: var(--cf-row-hov) !important; cursor: pointer; }
.cf-page .datagrid-row-selected { background: var(--cf-row-sel) !important; }
.cf-page td.datagrid-td-rownumber { background: var(--cf-bg) !important; }
.cf-page .datagrid-cell-rownumber { color: var(--cf-muted) !important; }

/* -- Pagination bar -- */
.cf-page .datagrid-pager,
.cf-page .pagination {
	background: var(--cf-bg) !important;
	border-top: 1px solid var(--cf-border) !important;
}
.cf-page .datagrid-pager { border-radius: 0 0 8px 8px; }
.cf-page .pagination-info,
.cf-page .pagination span {
	font-family: var(--cf-sans);
	color: var(--cf-mid);
}
.cf-page .pagination .pagination-num {
	border: 1px solid var(--cf-border);
	border-radius: 3px;
	font-family: var(--cf-sans);
}

/* -- Search box -- */
.cf-page .searchbox {
	background: var(--cf-white) !important;
	border: 1px solid var(--cf-border) !important;
	border-radius: 4px !important;
}
.cf-page .searchbox .searchbox-text {
	font-family: var(--cf-sans) !important;
	font-size: 12px !important;
	color: var(--cf-dark) !important;
}

/* -- Dialog / messager windows -- */
.cf-page .window {
	background: var(--cf-white) !important;
	border: 1px solid var(--cf-border) !important;
	border-radius: 8px !important;
	box-shadow: 0 14px 38px rgba(22,36,59,.22) !important;
	overflow: hidden !important;
	padding: 0 !important;
}
.cf-page .window-shadow { display: none !important; }
.cf-page .window-mask { background: var(--cf-dark) !important; opacity: .35 !important; }
.cf-page .dialog-content { background: var(--cf-white) !important; }
.cf-page .dialog-button,
.cf-page .messager-button {
	background: var(--cf-bg) !important;
	border-top: 1px solid var(--cf-border) !important;
	padding: 8px 12px !important;
}

/* -- Form fields (dialog textboxes / combobox) -- */
.cf-page .textbox {
	border: 1px solid var(--cf-border) !important;
	border-radius: 4px !important;
}
.cf-page .textbox:hover,
.cf-page .textbox.textbox-focused {
	border-color: var(--cf-blue) !important;
	box-shadow: none !important;
}
.cf-page .textbox .textbox-text {
	font-family: var(--cf-sans) !important;
	font-size: 12px !important;
	color: var(--cf-dark) !important;
}
.cf-page .validatebox-invalid {
	border-color: var(--cf-red) !important;
	background: var(--cf-red-bg) !important;
}
.combo-panel { border-color: var(--cf-border) !important; }
.combo-panel .combobox-item {
	font-family: var(--cf-sans);
	font-size: 12px;
	padding: 5px 8px;
}
.combo-panel .combobox-item-hover {
	background: var(--cf-row-hov) !important;
	color: var(--cf-dark) !important;
}
.combo-panel .combobox-item-selected {
	background: var(--cf-blue) !important;
	color: #fff !important;
}

/* -- Dialog form layout (restyled from the old bottom-of-page block) -- */
#fm{
	margin:0;
	padding:14px 18px;
}
.ftitle{
	font-family: var(--cf-sans);
	font-size:11px;
	font-weight:700;
	letter-spacing:.5px;
	text-transform:uppercase;
	color: var(--cf-muted);
	padding:10px 18px 6px;
	margin:0;
	border-bottom:1px solid var(--cf-border);
}
.fitem{
	margin-bottom:8px;
}
.fitem label{
	display:inline-block;
	width:100px;
	font-family: var(--cf-sans);
	font-size:12px;
	font-weight:600;
	color: var(--cf-mid);
}
.fitem input{
	width:160px;
}
</style>
</head>	

<body class="DBody">      
<div class="cf-page">
<!--
    <a class="cf-back" href="train_operations.php">&#8592; Back to Main Menu</a>
-->
    <table id="dg" title="Transport Employees" class="easyui-datagrid" style="width:100%;height:350px"
            url="get_users.php" 
            toolbar="#toolbar" pagination="true"
            rownumbers="true" fitColumns="true" singleSelect="true">
       <thead>
            <tr>
            	<th field="empNum" width="50" sortable="true">Employee No.</th>                                                              
				<th field="lastName" width="50" sortable="true">Last Name</th>
                <th field="firstName" width="50" sortable="true">First Name</th>
                <th field="midName" width="50" sortable="true">Middle Name</th>                
                <th field="position" width="50">Position</th>
            </tr>
        </thead>                     
    </table>
    
    
    <div id="toolbar" >
    	<?php 
		$ULev=3;
    	if ($ULev>2) {	?>				
        <a href="javascript:void(0)" class="easyui-linkbutton" data-options="iconCls:'icon-add'" onclick="newUser()">New Employee</a>
        <a href="javascript:void(0)" class="easyui-linkbutton" data-options="iconCls:'icon-edit'" onclick="editUser()">Edit User</a>
        <a href="javascript:void(0)" class="easyui-linkbutton" data-options="iconCls:'icon-remove'" onclick="destroyUser()">Remove User</a>        		
   <?php  }
        else { ?>					
        <a href="javascript:void(0)" class="easyui-linkbutton" data-options="iconCls:'icon-add',disabled:true" onclick="newUser()">New Employee</a>
        <a href="javascript:void(0)" class="easyui-linkbutton" data-options="iconCls:'icon-edit',disabled:true" onclick="editUser()">Edit User</a>
        <a href="javascript:void(0)" class="easyui-linkbutton" data-options="iconCls:'icon-remove',disabled:true" onclick="destroyUser()">Remove User</a>
        <?php } 
        ?>
        
        <input type="hidden" id="Rvalue">
        <a href="#" class="easyui-linkbutton" iconCls="icon-reload" onclick="doFresh()">Refresh</a>          
   <!--
     <span>Please Input Value</span>
    <input id="SFind" style="line-height:26px;border:1px solid #ccc">
    <a href="#" class="easyui-linkbutton" iconCls="icon-search" onclick="doSearch()">Search</a>                 
   -->
   <input id="SFind" class="easyui-searchbox" data-options="prompt:'Who are you looking for?',searcher:doSearch" style="width:250px"></input>
	<script>
		function doSearch(){			
			$('#dg').datagrid( 'load',{SFind: $('#SFind').val()});       			
		}
	</script>   
    </div>    
    
    
    <div id="dlg" class="easyui-dialog" style="width:400px;height:300px;padding:5px 5px"
            closed="true" buttons="#dlg-buttons">
        <div class="ftitle">Employee's Information</div>
        <form id="fm" method="post" novalidate>                	        
            <div class="fitem">
                <label>First Name</label>
                <input name="firstName" class="easyui-textbox" required="true">           
            </div>
            <div class="fitem">
                <label>Last Name</label>
                <input name="lastName" class="easyui-textbox" required="true" >
            </div>
            <div class="fitem">
                <label>Middle Name</label>
                <input name="midName" class="easyui-textbox" >
            </div>            
            <div class="fitem">
                <label>Employee No.</label>                
                <input name="empNum" class="easyui-textbox">                
            </div>
            <div class="fitem">
            <label>Position</label>
            <input id="position" class="easyui-combobox" name="position"
    		data-options="valueField:'value',textField:'text',url:'AddPos.json',panelHeight:'auto',required:true,prompt:'Select Type',value:''">            </div>     		        
            <!--
                <label>Position</label>  
                <select id="position" class="easyui-combobox" name="position" style="width:200px;">
    				<option value="TD" >Train Driver</option>
					<option value="CCRE">CCRE</option>
					<option value="STDO">Senior TDO</option>
					<option value="SVTDO">Supervising TDO</option>
					<option value="CLERK III">CLERK III</option>
					<option value="CLERK IV">CLERK IV</option>
					<option value="SUP">From Support</option>
					<option value="CHIEF TDO">CHIEF TDO</option>
				</select>					
				-->
            
        </form>
    </div>
    <div id="dlg-buttons">
        <a href="javascript:void(0)" class="easyui-linkbutton c2" iconCls="icon-ok" onclick="saveUser()" style="width:90px">Save</a>
        <a href="javascript:void(0)" class="easyui-linkbutton" iconCls="icon-cancel" onclick="javascript:$('#dlg').dialog('close')" style="width:90px">Cancel</a>
    </div>
    <script type="text/javascript">
        var url;
        function newUser(){
            $('#dlg').dialog('open').dialog('setTitle','New Employee');
            $('#fm').form('clear');
            url = 'save_user.php';
        }
        function editUser(){
            var row = $('#dg').datagrid('getSelected');            
            if (row){
                $('#dlg').dialog('open').dialog('setTitle','Edit');
                $('#fm').form('load',row);                
                url = 'update_user.php?id='+row.id;
            }
        }
        function saveUser(){
            $('#fm').form('submit',{
                url: url,
                onSubmit: function(){
                    return $(this).form('validate');
                },
                success: function(result){
                    var result = eval('('+result+')');
                    if (result.errorMsg){
                        $.messager.show({
                            title: 'Error',
                            msg: result.errorMsg
                        });
                    } else {
                        $('#dlg').dialog('close');        // close the dialog
                        $('#dg').datagrid('reload');    // reload the user data
                    }
                }
            });
        }
        function destroyUser(){
            var row = $('#dg').datagrid('getSelected');            
            if (row){
                $.messager.confirm('Confirm','Are you sure you want to delete this employee?',function(r){
                    if (r){
                    	
                        $.post('remove_user.php',{id:row.id},function(result){
                            if (result.success){
                                $('#dg').datagrid('reload');    // reload the user data
                            } else {
                                $.messager.show({    // show error message
                                    title: 'Error',
                                    msg: result.errorMsg
                                });
                            }
                        },'json');
                    }
                });
            }
        }

     
        
    
    // function doSearch(){
    //$('#dg').datagrid( 'load',{SFind: $('#SFind').val()});       
    //}
        
   
    
    function doFresh(){    	
    $('#dg').datagrid( 'load',{Rvalue: $('#Rvalue').val()});
    $('#SFind').searchbox('clear'); 
    
}
	
    </script>
</div>
</body>
</html>