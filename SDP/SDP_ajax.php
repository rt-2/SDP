<?php

	define('SDP_LOGLEVEL_NONE', 0);
	//define('SDP_LOGLEVEL_COMPACT', 1);
	define('SDP_LOGLEVEL_COMPLETE', 2);
	
        // Functions
	function ExplodeSaarpFieldPermissionString($fieldPermissionString)
	{
		$return_array = array();
		$return_string = $fieldPermissionString;
		$return_string = str_replace(' ', '', $return_string);
		$return_array = explode(',', $return_string);
		return $return_array;
	}
        // Basic Vars
	//fetch post vars
	$panel_uid = $_POST['panel_uid'];
	$indexid = $_POST['indexid'];
	$field_name = $_POST['field'];
	$field_value = $_POST['value'];
	$field_value = json_decode('"'.$field_value.'"'); // this acts as fromHex
	$field_value = strtr($field_value, array("\r\n" => '<br>', "\r" => '<br>', "\n" => '<br>'));
	$values = $_POST['values'];
	
	
	
	//echo "\n\n".substr($field_value, 0, 30);;
	
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
	
	//prepare stats if necessary
	
	if($SDP_logLevel != SDP_LOGLEVEL_NONE) {
	
		$sql = $con->prepare("
			CREATE TABLE IF NOT EXISTS `$SDP_logTableName` (
				`id` int NOT NULL AUTO_INCREMENT,
				`session_id` tinytext NOT NULL,
				`action_type` tinytext NOT NULL,
				`fields` text NOT NULL,
				`values` text NOT NULL,
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
			exit();
		}
	
	}
	
	// If Is Adding a New Row
	if($indexid == 0)
	{
		//vars
		$values_arr = json_decode($values);
        //prepare query
		foreach($values_arr as $field=>$value)
		{
			$permissions_array = explode(',', $fields_permissions[$field]);
			$field_permissions[$field] = $permissions_array;
			$field_editable = (in_array('new',$field_permissions[$field]))? true:false;
			if(!$field_editable) die('Permission denied'.$field );
		}
		$sql_fields = '';
		$sql_values = '';
		$sql_fields_first = true;
		$sql_fields .= '(';
		$sql_values .= '(';
		foreach($values_arr as $field=>$value)
		{
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
			$sql_values .= ':'.$field.'';
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
		//$i = 1;
		foreach($values_arr as $field=>$value)
		{
			$str_tmp = ':'.$field;
			$sql->bindValue(':'.$field, $value);
			//$i++;
		}
		try {
			//execute query
			$sql->execute();
		} catch (PDOException $e) {
			//display error
			echo 'Error: ' . $e->getMessage();
			//end script execution
			exit();
		}
		if($sql->errorInfo()[0]!= '00000')
		{
			//display error
			echo 'Error: '.$sql->errorInfo()[2];
			
			//end script execution
			exit();
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
				exit();
			}
			
		}
		
		
	        //send confiration message
		echo 'Row added.';
                //end script execution
		exit();
	}
	// If Is Updating a row (or would have exited)
        //check permitions for 'write'
	$field_permissions = $fields_permissions[$field_name];
	if(!(
		in_array(
			'write',
			ExplodeSaarpFieldPermissionString(
				$field_permissions
			),
			false
		)
	))
	{
		die('Permition denied');
	}
	// Action
        //test for protection against sql injection for $field_name
	if(!array_key_exists($field_name,$fields_permissions))
	{
		die('Permition denied');
	}
        //prepare query
	$sql = $con->prepare("
		UPDATE `$tablename`
		SET `$field_name` = :value
		WHERE `$table_index` = :indexid;
	");
	$sql->bindParam(':value', $field_value);
	$sql->bindParam(':indexid', $indexid);
	try {
		//execute query
		$sql->execute();
	} catch (PDOException $e) {
		//display error
		echo 'Error: ' . $e->getMessage();
		//end script execution
		exit();
	}
	if($sql->errorInfo()[0]!= '00000')
	{
		//display error
		echo 'Error: '.$sql->errorInfo()[2];
		//end script execution
		exit();
	}
	
        //prepare query
	$sql = $con->prepare("
		UPDATE `$tablename`
		SET `$field_name` = :value
		WHERE `$table_index` = :indexid;
	");
	$sql->bindParam(':value', $field_value);
	$sql->bindParam(':indexid', $indexid);
	try {
		//execute query
		$sql->execute();
	} catch (PDOException $e) {
		//display error
		echo 'Error: ' . $e->getMessage();
		//end script execution
		exit();
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
			exit();
		}
		
	}
	
	
	
	
	//echo "\n\n".substr($field_value, 0, 30);
	
	//echo "\n\n";
	
	
	//send confiration message
	echo 'Information updated.';

?>
