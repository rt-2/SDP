<?php
	
	define('SDP_LOGLEVEL_NONE', 0);
	//define('SDP_LOGLEVEL_COMPACT', 1);
	define('SDP_LOGLEVEL_COMPLETE', 2);
	
    // Functions
	function json_decode_nice($json, $assoc = TRUE){
		$json = str_replace(array("\n","\r"),"\\n", $json);
		$json = preg_replace('/([{,]+)(\s*)([^"]+?)\s*:/','$1"$3":',$json);
		$json = preg_replace('/(,)\s*}$/','}',$json);
		return json_decode($json,$assoc);
	}
	function fromUni($string)
	{
		$return_string = '';
		//echo "\n\n string: "; var_dump($string);
		$string_array = explode('\\u', $string);
		//echo "\n\n string_array("+count($string_array)+"): "; var_dump($string_array);
		for($c = 1; $c < count($string_array); $c++)
		{
			
			$char = (int) $string_array[$c];
			//$char = base_convert($char, 16, 10);
			$char = chr($char);
			
			$return_string = substr_replace($return_string, $char, $c, 1);
		}
		//echo "\n\n return_string: "; var_dump($return_string);
		return $return_string;
	}
	function tohex($ascii) {
		$hex = '';
		for ($i = 0; $i < strlen($ascii); $i++) {
			$byte = strtoupper(dechex(ord($ascii{$i})));
			$byte = str_repeat('0', 2 - strlen($byte)).$byte;
			$hex.=$byte." ";
		}
		return $hex;
	}
        // Functions
	function ExplodeSaarpFieldPermissionString($fieldPermissionString)
	{
		$return_array = array();
		$return_string = $fieldPermissionString;
		$return_string = preg_replace('/\s+/', '', $return_string);
		$return_array = explode(',', $return_string);
		return $return_array;
	}
	function TransformBlobUrlForDatabase($blob)
	{
		$return = $blob;
		$return = preg_replace('/data:([A-Za-z]+)\/([A-Za-z]+);base64,/', '', $return);
		$return = base64_decode($return);
		$return = tohex($return);
		$return = '0x'.strtolower(preg_replace('/\s+/', '', $return));
		return $return;
	}
        // Basic Vars
	//fetch post vars
	$panel_uid = $_POST['panel_uid'];
	$indexid = $_POST['indexid'];
	$field_name = $_POST['field'];
	$field_value = $_POST['value'];
	$values = $_POST['values'];
	
	/*
	echo "\n\n".substr($field_value, 0, 30);
	
	//$field_value = base64_decode($field_value);
	//$field_value = tohex($field_value);
	
	echo "\n\n".substr($field_value, 0, 30);
	
	//$field_value = '0x'.preg_replace('/\s+/', '', $field_value);
	
	echo "\n\n".substr($field_value, 0, 30);
	
	
	//$field_value = utf8_encode($field_value);
	
	echo "\n\n";
	
	echo "\n\n".substr($field_value, 0, 30);*/
        //fetch session vars
	session_start();
	$tablename = $_SESSION['SDP']['SDP_'.$panel_uid]['tablename'];
	$table_index = $_SESSION['SDP']['SDP_'.$panel_uid]['index'];
	$fields_permissions = $_SESSION['SDP']['SDP_'.$panel_uid]['access'];
	$sdp_sqlInfos = $_SESSION['SDP']['SDP_'.$panel_uid]['sqlInfos'];
	$SDP_logLevel = $_SESSION['SDP']['SDP_'.$panel_uid]['loglevel'];
	$SDP_tableNameString = $_SESSION['SDP']['SDP_'.$panel_uid]['tablenamestring'];
	$SDP_logTableName = str_replace('$tablename', $tablename, $SDP_tableNameString);
	$SDP_otherVars = $_SESSION['SDP']['SDP_'.$panel_uid]['othervars'];
	
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
		echo $e->getMessage();
	}
	
	// Fields transormations
	
	//echo "\n\n".substr($field_value, 0, 30);
	if($field_name)
	{
		
		$field_permissions = $fields_permissions[$field_name];
		if(
			in_array(
				'blob',
				ExplodeSaarpFieldPermissionString(
					$field_permissions
				),
				false
			)
		)
		{
			
			// Is blob
			$field_value = TransformBlobUrlForDatabase($field_value);
		}
		else{
			
			// Normal
			$field_value = strtr($field_value, array("\r\n" => '<br>', "\r" => '<br>', "\n" => '<br>'));
			
		}
		
	}
	//echo "\n\n".substr($field_value, 0, 30);
	
	//prepare stats if necessary
	
	if($SDP_logLevel != SDP_LOGLEVEL_NONE) {
	
		$sql = $con->prepare("
			CREATE TABLE IF NOT EXISTS `$SDP_logTableName` (
				`id` int NOT NULL AUTO_INCREMENT,
				`session_id` tinytext NOT NULL,
				`action_type` tinytext NOT NULL,
				`fields` longtext NOT NULL,
				`values` longtext NOT NULL,
				`action_time` timestamp DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			);
		");
		try {
			//execute query
			$sql->execute();
		} catch (PDOException $e) {
			//display error
			echo 'Error: ' . $e->getMessage();
			//end script execution
			exit(0);
		}
	
	}
	
	// If Is Adding a New Row
	if($indexid == 0)
	{
		//$values = str_replace(',', '__sdpc__', $values);
		//$values = str_replace("\n\r", '__sdpn__', $values);
		//vars
		//$values = str_replace('"', '__sdpq__', $values);
		//$values = preg_replace('/(?<!:|: )"(?=[^"]*?"(( [^:])|([,}])))/', '\\"', $values);
		$values = str_replace(array("\n\r", "\n", "\r"), '__sdpn__', $values);
		//echo "\n\n\n";
		//var_dump($values);
		//echo "\n\n\n";
		$values_arr = json_decode($values, true);
		
		//echo "\n\n\n";
		//var_dump($values_arr);
		//echo "\n\n\n";
        //prepare query
		foreach($values_arr as $field=>$value)
		{
			$field_editable = (in_array('new',ExplodeSaarpFieldPermissionString($fields_permissions[$field])))? true:false;
			if(!$field_editable) die('Permission denied'.$field );
		}
		$sql_fields = '';
		$sql_values = '';
		$sql_fields_first = true;
		$sql_fields .= '(';
		$sql_values .= '(';
		foreach($values_arr as $field=>$value)
		{
			
			$field_isBlob = (in_array('blob',ExplodeSaarpFieldPermissionString($fields_permissions[$field])))? true:false;
	
			if($sql_fields_first)
			{
				$sql_fields_first = false;
			}
			else
			{
				$sql_fields .= ',';
				$sql_values .= ',';
			}
			$sql_fields .= '`'.$field.'`';
			if($field_isBlob) {
				$sql_values .= ''.TransformBlobUrlForDatabase($value).'1111ffff';
			} else{
				$sql_values .= ':'.$field.'';
			}
		}
		$sql_fields .= ')';
		$sql_values .= ')';
		$sql = $con->prepare("
			INSERT INTO `$tablename`
			$sql_fields
			VALUES
			$sql_values
			;
		");
		//echo "
		//	INSERT INTO `$tablename`
		//	$sql_fields
		//	VALUES
		//	$sql_values
		//	;
		//";
		//$i = 1;
		foreach($values_arr as $field=>$value)
		{
			//$field_isBlob = (in_array('new',ExplodeSaarpFieldPermissionString($fields_permissions[$field])))? true:false;
			$field_isBlob = (in_array('blob',ExplodeSaarpFieldPermissionString($fields_permissions[$field])))? true:false;
			
			$value = str_replace('__sdpn__',"\n\r", $value);
			//$value = str_replace('__sdpq__','"', $value);
			if(!$field_isBlob) $sql->bindValue(':'.$field, $value);
			//$i++;
		}
		try {
			//execute query
			$sql->execute();
		} catch (PDOException $e) {
			//display error
			echo 'Error: ' . $e->getMessage();
			//end script execution
			exit(0);
		}
		if($sql->errorInfo()[0]!= '00000')
		{
			//display error
			echo 'Error: '.$sql->errorInfo()[2];
			
			//end script execution
			exit(0);
		}
		
		// Add statistics
		if($SDP_logLevel == SDP_LOGLEVEL_COMPLETE) {
			
			$sql = $con->prepare("
				INSERT INTO `$SDP_logTableName`
				(`session_id`, `action_type`, `fields`, `values`)
				VALUES
				(:session_id, 'new', :fields, :values)
				;
			");
			
			$sql->bindParam(':session_id', session_id());
			$sql->bindParam(':fields', $sql_fields);
			
			$final_values_str = $sql_values;
			foreach($values_arr as $field=>$value)
			{
				$str_tmp = ':'.$field;
				$final_values_str = str_replace(':'.$field, $value, $final_values_str);
			}
			
			
			$sql->bindParam(':values', $final_values_str);
			
			try {
				//execute query
				$sql->execute();
			} catch (PDOException $e) {
				//display error
				echo 'Error: ' . $e->getMessage();
				//end script execution
				exit(0);
			}
			
		}
		
		
	        //send confiration message
		echo 'Row added.';
                //end script execution
		exit(0);
	}
	// If Is Updating a row (or would have exited)
        //check permitions for 'write'
	if(!(
		in_array(
			'write',
			ExplodeSaarpFieldPermissionString(
				$fields_permissions[$field_name]
			),
			false
		)
	))
	{
		die('Permition denied');
	}
	
	$field_isBlob = (in_array('blob',ExplodeSaarpFieldPermissionString($fields_permissions[$field_name])))? true:false;
	
	// Action
        //test for protection against sql injection for $field_name
	if(!array_key_exists($field_name,$fields_permissions))
	{
		die('Permition denied');
	}
        //prepare query
	if($field_isBlob)
	{
		//echo 'phase 1';
		$sql = $con->prepare("
			UPDATE `$tablename`
			SET `$field_name` = $field_value
			WHERE `$table_index` = :indexid;
		");
		
		//echo "\n\n"."
		//	UPDATE `$tablename`
		//	SET `$field_name` = '$field_value'
		//	WHERE `$table_index` = :indexid;
		//";
	}
	else
	{
		//echo 'phase 2';
		$sql = $con->prepare("
			UPDATE `$tablename`
			SET `$field_name` = :value
			WHERE `$table_index` = :indexid;
		");
		$sql->bindParam(':value', $field_value);
		//echo "\n\n"."
		//	UPDATE `$tablename`
		//	SET `$field_name` = '$field_value'
		//	WHERE table_index` = :indexid;
		//";
		
	}
	$sql->bindParam(':indexid', $indexid);
	try {
		//execute query
		$sql->execute();
	} catch (PDOException $e) {
		//display error
		echo 'Error: ' . $e->getMessage();
		//end script execution
		exit(0);
	}
	if($sql->errorInfo()[0]!= '00000')
	{
		//display error
		echo 'Error: '.$sql->errorInfo()[2];
		//end script execution
		exit(0);
	}
	
	// Add statistics
	
	//echo "\n\n".$SDP_logLevel;
	
	if($SDP_logLevel == SDP_LOGLEVEL_COMPLETE) {
		
		$sql = $con->prepare("
			INSERT INTO `$SDP_logTableName`
			(`session_id`, `action_type`, `fields`, `values`)
			VALUES
			(:session_id, 'edit', :fields, :values)
			;
		");
		
		$sql->bindParam(':session_id', session_id());
		
		$sql->bindParam(':fields', $field_name);
		$sql->bindParam(':values', $field_value);
		
		
		try {
			//execute query
			$sql->execute();
		} catch (PDOException $e) {
			//display error
			echo 'Error: ' . $e->getMessage();
			//end script execution
			exit(0);
		}
		
	}
	
	
	
	
	//echo "\n\n".substr($field_value, 0, 30);
	
	//echo "\n\n";
	//echo "\n\n";
	
	
	//send confiration message
	echo 'Information updated.';

?>
