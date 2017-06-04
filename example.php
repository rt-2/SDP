<?php
	require_once('SDP/SDP.php');
	
	$sdp_sqlInfos = array(
		'hostname' => '127.0.0.1',
		'username' => 'user',
		'password' => 'pass',
		'dbname' => 'database'
	);

?>
<html>
	<head>
		<title></title>
	</head>
	<body>
		<h1>SDP Example</h1>
		<br />
		<br />
		<center>
			<table border="true" width="80%">
				<?php
					//setup
					$tablename = 'table_name';
					$order = 'ORDER BY `id` DESC';
					$where = 'WHERE `date` > NOW()';
					$db_access['id'] = 'read';
					$db_access['field1'] = 'read, new'; // Will not work
					$db_access['field1'] = 'read,new'; // Will work
					$db_access['field2'] = 'read,new';
					$db_access['field3'] = 'read,write,new';
					$db_access['date'] = 'read,new,date';
					$db_access['comment'] = 'read,write,new,text';
					//function
					echo SpawnSaarpDatabasePanel($sdp_sqlInfos, $tablename, $db_access, $where, $order);
				?>
			</table>
			<br><br><br>
			<br><br><br>
			<br><br><br>
		</center>
	</body>
</html>
