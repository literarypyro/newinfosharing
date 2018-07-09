<!--- Author by Jun
//--- Date: 9/3/2014
//--- Subject: Create a page for creating a transport user log-in
//--- Marker: @jun
//--------------------------------------------------->
<?php
require("trans menu.php");
global $PCmb;
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
</head>	

<body class="DBody">      
    <br>
    <br>
    <a class='two' href='Tmenu.php'><font face="Century" size=""><b>Back to Main Menu</b></font></a>  
    <br>
    <br>    
    <table id="dg" title="Transport Users" class="easyui-datagrid" style="width:100%;height:350px"
            url="get_Tusers.php" 
            toolbar="#toolbar" pagination="true"
            rownumbers="true" fitColumns="true" singleSelect="true">
       <thead>
            <tr>            	
            	<th field="username" width="50">User Name</th>            	
            	<th field="lastName" width="50" sortable="true">Last Name</th>
                <th field="firstName" width="50" sortable="true">First Name</th>                                                				<th field="levelid" width="50">User Level</th>
            </tr>
        </thead>                     
    </table>`
    
    
            </td>
    <div id="toolbar" >
    	<?php 
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

 <input id="SFind" class="easyui-searchbox" data-options="prompt:'Who are you looking for?',searcher:doSearch" style="width:250px"></input>
	<script>
		function doSearch(){			
			$('#dg').datagrid( 'load',{SFind: $('#SFind').val()});       			
		}
	</script>                
    </div>    
    
    <div id="dlg" class="easyui-dialog" style="width:400px;height:320px;padding:5px 5px"
            closed="true" buttons="#dlg-buttons">
        <div class="ftitle">User's Information</div>
        <form id="fm" method="post" novalidate>        
        	<div class="fitem">
                <label>Name</label>    
				<!--                              
                <input name="firstName" class="easyui-textbox" required="true">
                -->         
                
                <select id='lastName' name='lastName' class="easyui-combobox" style="width:160px;" required="true">
				<?php											
				$db=new mysqli("localhost","root","","transport");
				$sql="select id, firstName, lastName from train_driver where position not in ('TD','CCRE') order by lastName";
				$rs=$db->query($sql);
				$nm=$rs->num_rows;
				for($i=0;$i<$nm;$i++){
					$row=$rs->fetch_assoc();
					?>	
					<option value='<?php echo $row['lastName'].", ".$row['firstName']; ?>'><?php echo $row['lastName'].", ".$row['firstName']; ?>					
					</option>										
					<?php
					}
					?>
				</select>
				<script type="text/javascript">
					$(document).ready(function() {
  					$("#lastName").change(function() {
    				$("#changeme").val($(this).val());
  						});
					});
					</script>				
            </div>            
        	<!--
            <div class="fitem">
                <label>Last Name</label>
                <input  id="lastName" name="lastName" value="<?php echo $PCmb;?>" >
            </div>
            -->
            <div class="fitem">
                <input type="hidden" id="firstName" name="firstName"  value="value">
            </div>
             
            
            <div class="fitem">
                <label>Position</label>  
                <input id="position" class="easyui-combobox" name="position"
    	data-options="valueField: 'value',textField: 'text', valueField: 'level', url:'AddUser2.json',panelHeight:'auto',required:true,prompt:'Select Type',value:'',
    		onSelect: function(rec){         		   		
            var url = rec.level;              
            var vri = rec.value;            
            $('#position').combobox({value:vri});
            $('#cc2').textbox({value:url})}">
            <!--$('#cc2').textbox({value:url+' '+rec.text})}">  -->         
            </div>
            
            <div class="fitem">
            	<label>Level ID</label>  
            	<input id="cc2" class="easyui-textbox" style="width:25px;" name="levelid" readonly>
    			                 
                <!--
                <select id="position" name="position" style="width:200px;" onchange="changeFunc()"> 
                -->
                
        	
        	<!--
                <select id="position"  class="easyui-combobox" style="width:200px;" data-options="required:true,prompt:'Select Type',value:''" name="position">    					
                    <option value="STDO">Senior TDO</option>
					<option value="SVTDO">Supervising TDO</option>
					<option value="CLERK III">CLERK III</option>
					<option value="CLERK IV">CLERK IV</option>															
					<option value="CHIEF TDO">CHIEF TDO</option>
				</select>
			
				<script>
				$('#position').combobox('setValue', 'SVTDO');
				
				</script>
				<!--
				<script>
		function changeFunc() {
		//var selectedValue = position.options[position.selectedIndex].value;
		//$PCmb = selectedValue;
		
		 alert($("#position").val());
		//alert($PCmb);
		}
	</script> 		
	-->	
            </div>            
        	<div class="fitem">
                <label>Username</label>                
                <input name="username" class="easyui-textbox" required="true" data-options="prompt:'Username',iconCls:'icon-man',iconWidth:38">
                <!-- <input name="username" class="easyui-textbox" required="true" > -->                
            </div>                    
            <div class="fitem">
                <label>Password</label>
                <input class="easyui-textbox" type="password" required="true" name="password" data-options="prompt:'Password',iconCls:'icon-lock',iconWidth:38">
                <!-- <input name="midName" class="easyui-textbox" > -->
            </div>                                                  
        </form>
    </div>
    <div id="dlg-buttons">
        <a href="javascript:void(0)" class="easyui-linkbutton c2" iconCls="icon-ok" onclick="saveUser()" style="width:90px">Save</a>
        <a href="javascript:void(0)" class="easyui-linkbutton" iconCls="icon-cancel" onclick="javascript:$('#dlg').dialog('close')" style="width:90px">Cancel</a>
    </div>
    <script type="text/javascript">
        var url;
        function newUser(){
            $('#dlg').dialog('open').dialog('setTitle','New User');
            $('#fm').form('clear');
            url = 'save_user2.php';
        }
        function editUser(){
           var row = $('#dg').datagrid('getSelected');            
            if (row){
                $('#dlg').dialog('open').dialog('setTitle','Edit User');
                $('#fm').form('load',row);                
                url = 'update_user2.php?id='+row.id;
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
                $.messager.confirm('Confirm','Are you sure you want to delete this user?',function(r){
                    if (r){
                    	
                        $.post('remove_user2.php',{id:row.id},function(result){
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

     
        
    
     //function doSearch(){
    //$('#dg').datagrid( 'load',{SFind: $('#SFind').val()});       
    //}
    
     
    
    function doFresh(){    	
    $('#dg').datagrid( 'load',{Rvalue: $('#Rvalue').val()});
    $('#SFind').searchbox('clear'); 
}
	
    </script>
        
    <style type="text/css">
        #fm{
            margin:0;
            padding:10px 30px;
        }
        .ftitle{
            font-size:14px;
            font-weight:bold;
            padding:5px 0;
            margin-bottom:10px;
            border-bottom:1px solid #ccc;
        }
        .fitem{
            margin-bottom:5px;
        }
        .fitem label{
            display:inline-block;
            width:100px;
        }
        .fitem input{
            width:160px;
        }
        
        
        /* --- mjun */
		a.two:visited {color:black;}
		a.two:hover, a.two:active {font-size:120%; color:orange;}
		
    </style>
</body>
</html>