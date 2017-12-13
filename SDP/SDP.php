<?php
	
	define('SDP_LOGLEVEL_NONE', 0);
	//define('SDP_LOGLEVEL_COMPACT', 1);
	define('SDP_LOGLEVEL_COMPLETE', 2);
	
	$SDP_logLevel = SDP_LOGLEVEL_NONE;
	
	//getRelativePath - found here <https://stackoverflow.com/questions/2637945/getting-relative-path-from-absolute-path-in-php>
	function getRelativePath($from, $to)
	{
		// some compatibility fixes for Windows paths
		$from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
		$to   = is_dir($to)   ? rtrim($to, '\/') . '/'   : $to;
		$from = str_replace('\\', '/', $from);
		$to   = str_replace('\\', '/', $to);

		$from     = explode('/', $from);
		$to       = explode('/', $to);
		$relPath  = $to;

		foreach($from as $depth => $dir) {
			// find first non-matching dir
			if($dir === $to[$depth]) {
				// ignore this directory
				array_shift($relPath);
			} else {
				// get number of remaining dirs to $from
				$remaining = count($from) - $depth;
				if($remaining > 1) {
					// add traversals up to first matching dir
					$padLength = (count($relPath) + $remaining - 1) * -1;
					$relPath = array_pad($relPath, $padLength, '..');
					break;
				} else {
					$relPath[0] = './' . $relPath[0];
				}
			}
		}
		return implode('/', $relPath);
	}
	
	function SpawnSaarpDatabasePanel(array $sdp_sqlInfos, $tablename, array $access, $where, $order)
	{
                // Mysql Connection
                $hostname = $sdp_sqlInfos['hostname'];
                $username = $sdp_sqlInfos['username'];
                $password = $sdp_sqlInfos['password'];
                $dbname = $sdp_sqlInfos['dbname'];
		try
		{
			$con = new PDO("mysql:host=$hostname;dbname=$dbname", $username, $password);
			$con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$con->exec("SET CHARACTER SET utf8");
		}
		catch(PDOException $e)
		{
			echo $e->getCode() .': '. $e->getMessage();
		}
                // Basic Vars
		$panel_uid = str_replace('.', '', uniqid("", true));
		$ajax_url = getRelativePath($_SERVER["SCRIPT_FILENAME"], realpath(__DIR__)).'SDP_ajax.php';
		
		// (new) 
		
		
		
		// Table Name Verification
		$sql = $con->prepare("SHOW INDEX FROM $tablename;");
		$sql->execute();
		$result = $sql->fetchAll(PDO::FETCH_ASSOC);
		if(count($result) > 0)
		{
			$tableindex = $result[0]['Column_name'];
			$sql_tableindex = '`'.$tableindex.'`,';
		}
		else
		{
			ob_get_contents();
			ob_end_clean();
			return "no index on table `$tablename`";
		}
		
		// Session Vars
		session_start();
		$_SESSION['SDP']['SDP_'.$panel_uid]['sqlInfos'] = $sdp_sqlInfos;
		$_SESSION['SDP']['SDP_'.$panel_uid]['tablename'] = $tablename;
		$_SESSION['SDP']['SDP_'.$panel_uid]['index'] = $tableindex;
		$_SESSION['SDP']['SDP_'.$panel_uid]['access'] = $access;
		$_SESSION['SDP']['SDP_'.$panel_uid]['loglevel'] = $GLOBALS['SDP_logLevel'];
		$_SESSION['SDP']['SDP_'.$panel_uid]['tablenamestring'] = $GLOBALS['SDP_tableNameString'];
		$_SESSION['SDP']['SDP_'.$panel_uid]['othervars'] = $GLOBALS['SDP_otherVars'];
		
                // BEGIN of Result Code
		ob_start();
		?>
			<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
			<script>
				// Tipr 3.0
				// Copyright (c) 2017 Tipue
				// Tipr is released under the MIT License
				// http://www.tipue.com/tipr
				$(document).ready(function() {
					 $('.tip').tipr();
				});
				(function($){$.fn.tipr=function(options){var set=$.extend({'speed':200,'mode':'below','space':70},options);return this.each(function(){var mouseY=-1;$(document).on('mousemove',function(event)
				{mouseY=event.clientY;});var viewY=$(window).height();$(this).hover(function()
				{var d_m=set.mode;$(window).on('resize',function()
				{viewY=$(window).height();});if(viewY-mouseY<set.space)
				{d_m='above';}
				else
				{d_m=set.mode;if($(this).attr('data-mode'))
				{d_m=$(this).attr('data-mode')}}
				tipr_cont='.tipr_container_'+d_m;var out='<div class="tipr_container_'+d_m+'"><div class="tipr_point_'+d_m+'"><div class="tipr_content">'+$(this).attr('data-tip')+'</div></div></div>';$(this).append(out);var w_t=$(tipr_cont).outerWidth();var w_e=$(this).width();var m_l=(w_e / 2)-(w_t / 2);$(tipr_cont).css('margin-left',m_l+'px');$(this).removeAttr('title alt');$(tipr_cont).fadeIn(set.speed);},function()
				{$(tipr_cont).remove();});});};})(jQuery);
			</script>
			<style>
				// Tipr 3.0
				// Copyright (c) 2017 Tipue
				// Tipr is released under the MIT License
				// http://www.tipue.com/tipr
				.tipr_content
				{
					 font: 13px/1.7 'Helvetica Neue', Helvetica, Arial, sans-serif;
					 color: #333; 
					 background-color: #fff;
					 padding: 9px 17px;
					 max-width: 800px !important;
				}
				.tipr_container_below
				{
					 display: none;
					 position: absolute;
					 margin-top: 13px;
					 box-shadow: 2px 2px 5px #f9f9f9;
					 z-index: 1000;
				}
				.tipr_container_above
				{
					 display: none;
					 position: absolute;
					 margin-top: -77px;
					 box-shadow: 2px 2px 5px #f9f9f9;
					 z-index: 1000;
				}
				.tipr_point_above, .tipr_point_below 
				{
					 position: relative;
					background: #fff;
					border: 1px solid #dcdcdc;
				}
				.tipr_point_above:after, .tipr_point_above:before
				{
					position: absolute;
					pointer-events: none;
					border: solid transparent;
					top: 100%;
					content: "";
					height: 0;
					width: 0;
				}
				.tipr_point_above:after
				{
					border-top-color: #fff;
					border-width: 8px;
					left: 50%;
					margin-left: -8px;
				}
				.tipr_point_above:before 
				{
					border-top-color: #dcdcdc;
					border-width: 9px;
					left: 50%;
					margin-left: -9px;
				}
				.tipr_point_below:after, .tipr_point_below:before
				{
					position: absolute;
					pointer-events: none;
					border: solid transparent;
					bottom: 100%;
					content: "";
					height: 0;
					width: 0;
				}
				.tipr_point_below:after
				{
					border-bottom-color: #fff;
					border-width: 8px;
					left: 50%;
					margin-left: -8px;
				}
				.tipr_point_below:before 
				{
					border-bottom-color: #dcdcdc;
					border-width: 9px;
					left: 50%;
					margin-left: -9px;
				}

			</style>
			<script>
				//	jQuery Masked Input Plugin
				//	Copyright (c) 2007 - 2015 Josh Bush (digitalbush.com)
				//	Licensed under the MIT license (http://digitalbush.com/projects/masked-input-plugin/#license)
				//	Version: 1.4.1
				!function(a){"function"==typeof define&&define.amd?define(["jquery"],a):a("object"==typeof exports?require("jquery"):jQuery)}(function(a){var b,c=navigator.userAgent,d=/iphone/i.test(c),e=/chrome/i.test(c),f=/android/i.test(c);a.mask={definitions:{9:"[0-9]",a:"[A-Za-z]","*":"[A-Za-z0-9]"},autoclear:!0,dataName:"rawMaskFn",placeholder:"_"},a.fn.extend({caret:function(a,b){var c;if(0!==this.length&&!this.is(":hidden"))return"number"==typeof a?(b="number"==typeof b?b:a,this.each(function(){this.setSelectionRange?this.setSelectionRange(a,b):this.createTextRange&&(c=this.createTextRange(),c.collapse(!0),c.moveEnd("character",b),c.moveStart("character",a),c.select())})):(this[0].setSelectionRange?(a=this[0].selectionStart,b=this[0].selectionEnd):document.selection&&document.selection.createRange&&(c=document.selection.createRange(),a=0-c.duplicate().moveStart("character",-1e5),b=a+c.text.length),{begin:a,end:b})},unmask:function(){return this.trigger("unmask")},mask:function(c,g){var h,i,j,k,l,m,n,o;if(!c&&this.length>0){h=a(this[0]);var p=h.data(a.mask.dataName);return p?p():void 0}return g=a.extend({autoclear:a.mask.autoclear,placeholder:a.mask.placeholder,completed:null},g),i=a.mask.definitions,j=[],k=n=c.length,l=null,a.each(c.split(""),function(a,b){"?"==b?(n--,k=a):i[b]?(j.push(new RegExp(i[b])),null===l&&(l=j.length-1),k>a&&(m=j.length-1)):j.push(null)}),this.trigger("unmask").each(function(){function h(){if(g.completed){for(var a=l;m>=a;a++)if(j[a]&&C[a]===p(a))return;g.completed.call(B)}}function p(a){return g.placeholder.charAt(a<g.placeholder.length?a:0)}function q(a){for(;++a<n&&!j[a];);return a}function r(a){for(;--a>=0&&!j[a];);return a}function s(a,b){var c,d;if(!(0>a)){for(c=a,d=q(b);n>c;c++)if(j[c]){if(!(n>d&&j[c].test(C[d])))break;C[c]=C[d],C[d]=p(d),d=q(d)}z(),B.caret(Math.max(l,a))}}function t(a){var b,c,d,e;for(b=a,c=p(a);n>b;b++)if(j[b]){if(d=q(b),e=C[b],C[b]=c,!(n>d&&j[d].test(e)))break;c=e}}function u(){var a=B.val(),b=B.caret();if(o&&o.length&&o.length>a.length){for(A(!0);b.begin>0&&!j[b.begin-1];)b.begin--;if(0===b.begin)for(;b.begin<l&&!j[b.begin];)b.begin++;B.caret(b.begin,b.begin)}else{for(A(!0);b.begin<n&&!j[b.begin];)b.begin++;B.caret(b.begin,b.begin)}h()}function v(){A(),B.val()!=E&&B.change()}function w(a){if(!B.prop("readonly")){var b,c,e,f=a.which||a.keyCode;o=B.val(),8===f||46===f||d&&127===f?(b=B.caret(),c=b.begin,e=b.end,e-c===0&&(c=46!==f?r(c):e=q(c-1),e=46===f?q(e):e),y(c,e),s(c,e-1),a.preventDefault()):13===f?v.call(this,a):27===f&&(B.val(E),B.caret(0,A()),a.preventDefault())}}function x(b){if(!B.prop("readonly")){var c,d,e,g=b.which||b.keyCode,i=B.caret();if(!(b.ctrlKey||b.altKey||b.metaKey||32>g)&&g&&13!==g){if(i.end-i.begin!==0&&(y(i.begin,i.end),s(i.begin,i.end-1)),c=q(i.begin-1),n>c&&(d=String.fromCharCode(g),j[c].test(d))){if(t(c),C[c]=d,z(),e=q(c),f){var k=function(){a.proxy(a.fn.caret,B,e)()};setTimeout(k,0)}else B.caret(e);i.begin<=m&&h()}b.preventDefault()}}}function y(a,b){var c;for(c=a;b>c&&n>c;c++)j[c]&&(C[c]=p(c))}function z(){B.val(C.join(""))}function A(a){var b,c,d,e=B.val(),f=-1;for(b=0,d=0;n>b;b++)if(j[b]){for(C[b]=p(b);d++<e.length;)if(c=e.charAt(d-1),j[b].test(c)){C[b]=c,f=b;break}if(d>e.length){y(b+1,n);break}}else C[b]===e.charAt(d)&&d++,k>b&&(f=b);return a?z():k>f+1?g.autoclear||C.join("")===D?(B.val()&&B.val(""),y(0,n)):z():(z(),B.val(B.val().substring(0,f+1))),k?b:l}var B=a(this),C=a.map(c.split(""),function(a,b){return"?"!=a?i[a]?p(b):a:void 0}),D=C.join(""),E=B.val();B.data(a.mask.dataName,function(){return a.map(C,function(a,b){return j[b]&&a!=p(b)?a:null}).join("")}),B.one("unmask",function(){B.off(".mask").removeData(a.mask.dataName)}).on("focus.mask",function(){if(!B.prop("readonly")){clearTimeout(b);var a;E=B.val(),a=A(),b=setTimeout(function(){B.get(0)===document.activeElement&&(z(),a==c.replace("?","").length?B.caret(0,a):B.caret(a))},10)}}).on("blur.mask",v).on("keydown.mask",w).on("keypress.mask",x).on("input.mask paste.mask",function(){B.prop("readonly")||setTimeout(function(){var a=A(!0);B.caret(a),h()},0)}),e&&f&&B.off("input.mask").on("input.mask",u),A()})}})});
			</script>
			<script>
				function toUni(input) {
					function pad_four(input) {
						var l = input.length;
						if (l == 0) return '0000';
						if (l == 1) return '000' + input;
						if (l == 2) return '00' + input;
						if (l == 3) return '0' + input;
						return input;
					}
					var output = '';
					for (var i = 0, l = input.length; i < l; i++)
						output += '\\u' + pad_four(input.charCodeAt(i).toString(/*16*/));
					return output;
				}
				$(document).ready(function() {
					
					
					var Player_Current_Clipboard = '';
					var panel_uid = '<?php echo $panel_uid; ?>';
					var ajax_url = '<?php echo $ajax_url; ?>';
					
					function SDP_<?php echo $panel_uid; ?>_SendFieldEdit(indexid, field_uid, field_name, new_value) {
						
						//alert('the value0: '+new_value);
						//alert('the value: '+toHex(new_value));
						
						var fd = new FormData();
						fd.append('panel_uid', panel_uid);
						fd.append('indexid', indexid);
						fd.append('field', field_name);
						fd.append('value', new_value);
						
						
						$.ajax({
							url : ajax_url,
							type: "POST",
							data: fd,
							processData: false,
							contentType: false,
							success: function(data, textStatus, jqXHR)
							{
								//data - response from server
								alert(data);
							},
							error: function (jqXHR, textStatus, errorThrown)
							{
						 
							}
						});
					}
					
					$(".SDP_<?php echo $panel_uid; ?>_editable").click(function() {
						// vars
						var view_element = $(this);
						var field_uid = view_element.attr('data-fielduid');
						var editor_element = $("#SDP_write_"+field_uid);
						var data_valAttr = editor_element.attr('data-valAttr');
						// action
						if(!$(this).hasClass('SDP_blob'))
						{
							Player_Current_Clipboard = editor_element.val();
							
							view_element.hide();
							editor_element.show();
							editor_element.focus();
						}
					});
					$(".SDP_<?php echo $panel_uid; ?>_editor").focusout(function() {
						if(!$(this).hasClass('SDP_blob'))
						{
							// Vars
							var editor_element = $(this);
							var indexid = editor_element.attr('data-indexid');
							var field_uid = editor_element.attr('data-fielduid');
							var field_name = editor_element.attr('data-fieldname');
							var view_element = $("#SDP_view_"+field_uid);
							var data_valAttr = editor_element.attr('data-valAttr');
							// Action
							var new_value;
							new_value = editor_element.val();
							if(new_value != Player_Current_Clipboard)
							{
								//change been made
								view_element.html(new_value);
								view_element.attr('data-tip', new_value.replace('"', "''"));
								
								SDP_<?php echo $panel_uid; ?>_SendFieldEdit(indexid, field_uid, field_name, new_value)
							}
							editor_element.hide();
							view_element.show();
						}
					});
					$(".SDP_<?php echo $panel_uid; ?>_editor .SDP_blob").change(function() {
						/*if($(this).hasClass('SDP_blob'))
						{
							
							
							// Vars
							var editor_element = $(this);
							var indexid = editor_element.attr('data-indexid');
							var field_uid = editor_element.attr('data-fielduid');
							var field_name = editor_element.attr('data-fieldname');
							var view_element = $("#SDP_view_"+field_uid+" img").first();
							var data_valAttr = editor_element.attr('data-valAttr');
							
							// Action
							
							//console.log(editor_element);
							//console.log(editor_element[0].files);
							//console.log(editor_element[0].files[0]);
							
							if (editor_element[0].files && editor_element[0].files[0]) {
								
								var file_value = editor_element[0].files[0];
									
								//if(new_value != Player_Current_Clipboard)
								//{
									var img_FRObject = new FileReader();
									var txt_FRObject = new FileReader();
									img_FRObject.readAsDataURL(file_value, 'UTF-8');
									//txt_FRObject.readAsText(file_value, 'UTF-8');
									txt_FRObject.readAsDataURL(file_value, 'UTF-8');

									
									img_FRObject.onload = function (oFREvent) {
										
										var img_value = oFREvent.target.result;
										
										view_element.attr('src', img_value);
										
										
										
									};
									txt_FRObject.onload = function (oFREvent) {
										
										var new_value = oFREvent.target.result;
										SDP_<?php echo $panel_uid; ?>_SendFieldEdit(indexid, field_uid, field_name, new_value);
										//$('#SDP_<?php echo $panel_uid; ?>_damntest').html(toUni(new_value));
										$('#SDP_<?php echo $panel_uid; ?>_damntest').html(new_value.substring(23, 53)+'<br>'+toUni(new_value).substring(23, 53));
									};
									//SDP_<?php echo $panel_uid; ?>_SendFieldEdit(indexid, field_uid, field_name, new_value);
									
								//}
							}
						}*/
					});
					$(".SDP_<?php echo $panel_uid; ?>_editor").change(function() {
						if($(this).hasClass('SDP_blob'))
						{
							
							
							// Vars
							var editor_element = $(this);
							var indexid = editor_element.attr('data-indexid');
							var field_uid = editor_element.attr('data-fielduid');
							var field_name = editor_element.attr('data-fieldname');
							var view_element = $("#SDP_view_"+field_uid+" img").first();
							var data_valAttr = editor_element.attr('data-valAttr');
							
							// Action
							
							//console.log(editor_element);
							//console.log(editor_element[0].files);
							//console.log(editor_element[0].files[0]);
							
							if (editor_element[0].files && editor_element[0].files[0]) {
								
								var file_value = editor_element[0].files[0];
									
								//if(new_value != Player_Current_Clipboard)
								//{
									var img_FRObject = new FileReader();
									var txt_FRObject = new FileReader();
									img_FRObject.readAsDataURL(file_value, 'UTF-8');
									//txt_FRObject.readAsText(file_value, 'UTF-8');
									txt_FRObject.readAsDataURL(file_value, 'UTF-8');

									
									img_FRObject.onload = function (oFREvent) {
										
										var img_value = oFREvent.target.result;
										
										view_element.attr('src', img_value);
										
										
										
									};
									txt_FRObject.onload = function (oFREvent) {
										
										var new_value = oFREvent.target.result;
										SDP_<?php echo $panel_uid; ?>_SendFieldEdit(indexid, field_uid, field_name, new_value);
										//$('#SDP_<?php echo $panel_uid; ?>_damntest').html(toUni(new_value));
										$('#SDP_<?php echo $panel_uid; ?>_damntest').html(new_value.substring(23, 53)+'<br>'+toUni(new_value).substring(23, 53));
									};
									//SDP_<?php echo $panel_uid; ?>_SendFieldEdit(indexid, field_uid, field_name, new_value);
									
								//}
							}
						}
					});
					$(".SDP_<?php echo $panel_uid; ?>_newButton").click(function() {
						// Vars
						var indexid = 0;
						var new_values_arr = new Array();
						var new_values = '';
						var first_value = true;
						new_values += '{ ';
						$(".SDP_<?php echo $panel_uid; ?>_newField").each(function(index) {
							editor_element = $(this);
							if(first_value)
							{
								first_value = false;
							}
							else
							{
								new_values += ', ';
								
							}
							new_values += '"'+editor_element.attr('data-fieldname')+'"';
							new_values += ':';
							data_valAttr = editor_element.attr('data-valAttr');
							
							
							//if (editor_element[0].files && editor_element[0].files[0]) {
							//	
							//	new_values += '"'+editor_element[0].files[0]+'"';
							//	$('#SDP_0_damntest').html(editor_element[0].files[0].substring(0, 30)+'<br>'+toUni(editor_element[0].files[0]).substring(0, 30));
							//}
							//else{
								new_values += '"'+editor_element.val()+'"';
							//}
							
							
							
						});
						new_values += ' }';
						
						
						var fd = new FormData();
						fd.append('panel_uid', panel_uid);
						fd.append('indexid', indexid);
						fd.append('values', new_values);
						
						// Actions
						$.ajax({
							url : ajax_url,
							type: "POST",
							processData: false,
							contentType: false,
							data : fd,
							success: function(data, textStatus, jqXHR)
							{
								//data - response from server
								alert(data);
								if(data == 'Row added.'){
									location.reload();
								}
							},
							error: function (jqXHR, textStatus, errorThrown)
							{
						 
							}
						});
					});
					$(".SDP_dateMasked").mask("9999-99-99 99:99:99",{placeholder:"YYYY-MM-DD HH:MM:SS"});
				});
			</script>
		<?php
                // Table Creation Vars
		$sql_hasAddPerm = false;
		$sql_fields = '';
                // Verify Permitions and Check if has any 'adding' power.
		$sql_fields_first = true;
		foreach($access as $field=>$permissions)
		{
			$permissions = preg_replace('/\s+/', '', $permissions);
			$permissions_array = explode(',', $permissions);
			$field_permissions[$field] = $permissions_array;
			
			if(in_array('read', $permissions_array))
			{
				if($sql_fields_first)
				{
					$sql_fields_first = false;
				}
				else
				{
					$sql_fields .= ',';
				}
				$sql_fields .= '`'.$field.'`';
			}
			if(in_array('new', $permissions_array))
			{
				$sql_hasAddPerm = true;
			}
		}
		
		// Get columns
		$columns = [];
		$sql = $con->prepare("
			DESCRIBE `$tablename`
		");
		$sql->execute();
		foreach($sql->fetchAll(PDO::FETCH_ASSOC) as $this_column) {
			
			$fieldname = $this_column['Field'];
			
			if(in_array('read', $field_permissions[$fieldname])) {
				$columns[$fieldname] = $this_column;
			}
		}
		
                // Fetch All Needed Data From The Server
		$sql = $con->prepare("
			SELECT $sql_tableindex $sql_fields
			FROM `$tablename`
			$where
			$order
		");
		$sql->execute();
		$result = $sql->fetchAll(PDO::FETCH_ASSOC);
		$columnsNb = count($columns);
                // 'Add' Table
		if($sql_hasAddPerm)
		{
		    echo '<tr>';
			//var_dump($result);
			foreach($columns as $field=>$this_column)
			{
				$field_addable = (in_array('new', $field_permissions[$field]))? true:false;
				$field_isDate = (in_array('date', $field_permissions[$field]))? true:false;
				$field_isText = (in_array('text', $field_permissions[$field]))? true:false;
				$field_isBlob = (in_array('blob', $field_permissions[$field]))? true:false;
				
				echo '<td>';
				echo $field.':';
				if($field_addable)
				{
					if($field_isText) {
						echo '<textarea ';
					} elseif($field_isBlob) {
						echo '<input type="file" ';
					} else {
						echo '<input type="text" ';
					}
					echo 'class="SDP_'.$panel_uid.'_newField ';
					if($field_isDate) echo 'SDP_dateMasked ';
					if($field_isBlob) echo 'SDP_blob ';
					echo'" ';
					echo'data-fieldname="'.$field.'" ';
					if($field_isText) {
						echo '>';
						echo $value_forQuotes;
						echo '</textarea>';
					} elseif($field_isBlob) {
						echo '>';
					} else {
						echo'value="'.$value_forQuotes.'" ';
						echo '<';
					}
				}
				echo '</td>';
			}
			
			
			echo '</tr>';
			echo '<tr>';
			echo '<td colspan="'.$columnsNb.'">';
			echo '<input type="button" class="SDP_'.$panel_uid.'_newButton" ';
			echo 'data-paneluid="'.$panel_uid.'" ';
			echo 'value="Add New Entry" ';
			echo '>';
			echo '</td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td colspan="'.$columnsNb.'">';
			echo '&nbsp;';
			echo '</td>';
			echo '</tr>';
		}
                // Table Field Names
		echo '<tr>';
		foreach($columns as $field=>$this_column)
		{
			echo '<td>';
			echo $field;
			echo '</td>';
		}
                // Table Content
		echo '</tr>';
		foreach($result as $row)
		{
			echo '<tr>';
			$rowindexid = $row[$tableindex];
			foreach($row as $field=>$value)
			{
				$field_uid = str_replace('.', '', uniqid("", true));
				$field_editable = (in_array('write',$field_permissions[$field]))? true:false;
				$field_isDate = (in_array('date',$field_permissions[$field]))? true:false;
				$field_isText = (in_array('text',$field_permissions[$field]))? true:false;
				$field_isBlob = (in_array('blob',$field_permissions[$field]))? true:false;
				$field_hasMax = (in_array('max',$field_permissions[$field]))? true:false;
				$field_isMaxed = ($field_hasMax && strlen($value) > 25);
				$value_forQuotes = str_replace(array("\n", '"'), array("<br>", "''"), $value);
				$value = str_replace(array("<br>", "''"), array("\n", '"'), $value);
				$value_complete = $value;
				$div_class = '';
				echo '<td>';
				echo '<div ';
				echo'id="SDP_view_'.$field_uid.'" ';
				if($field_isBlob) $div_class .= 'SDP_blob ';
				if($field_isMaxed)
				{
					$div_class .= 'tip ';
					echo'data-tip="'.$value_forQuotes.'" ';
					$value = substr($value, 0, 24).'...';
				}
				echo'data-fielduid="'.$field_uid.'" ';
				if($field_editable) $div_class .= 'SDP_'.$panel_uid.'_editable ';
				echo 'class="'.$div_class.'" ';
				echo '>';
				
				if($field_isBlob)
				{
					?>
					<script>
						$(document).ready(function() {
							/*
								var tstring = '<?php echo base64_encode($value); ?>';
							
								$('#SDP_<?php echo $panel_uid; ?>_damntest').html(tstring.substring(0, 30)+'<br>'+toUni(tstring).substring(0, 30));
								//$('#SDP_<?php echo $panel_uid; ?>_damntest').html(toUni('Hello world'));
							*/
						});
					</script>
					<?php
					
				echo '<div id="SDP_'.$panel_uid.'_damntest" >';
					//echo base64_encode($value);
				echo '</div>';
					echo '<img src="data:image/png;base64,'.base64_encode( $value ).'"/>';
				}
				else{
					
					echo (strlen($value) > 0)? $value:'&nbsp;';
				}
				
				
				
				echo '</div>';
				if($field_editable)
				{
					if($field_isText)
					{
						echo '<textarea ';
					} elseif($field_isBlob) {
						echo '<input type="file" ';
					} else {
						echo '<input type="text" ';
					}
					echo'id="SDP_write_'.$field_uid.'" ';
					echo 'class="SDP_'.$panel_uid.'_editor ';
					if($field_isDate) echo 'SDP_dateMasked ';
					if($field_isBlob) echo 'SDP_blob ';
					echo'" ';
					echo'data-fielduid="'.$field_uid.'" ';
					echo'data-fieldname="'.$field.'" ';
					echo'data-indexid="'.$rowindexid.'" ';
					if(!$field_isBlob) echo'style="display:none" ';
					
					if($field_isText)
					{
						echo '>';
						echo $value_complete;
						echo '</textarea>';
					} elseif($field_isBlob) {
						echo '>';
					} else {
						echo'value="'.$value_forQuotes.'" ';
						echo '>';
					}
				}
				echo '</td>';
			}
			echo '</tr>';
		}
                // END of Result Code
		$return_str = ob_get_contents();
		ob_end_clean();
                // Return Code
		return $return_str;
	}
	
	function SetSaarpDatabaseLogLevel($tablenamestring, $loglevel/*, $other_vars*/)
	{
		$GLOBALS['SDP_logLevel'] = $loglevel;
		$GLOBALS['SDP_tableNameString'] = $tablenamestring;
		//$GLOBAL['SDP_otherVars'] = $other_vars;
	}
	
?>
