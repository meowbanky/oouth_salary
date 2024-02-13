<?php

	$db_server = "localhost";
	$db_user = "root";
	$db_passwd = "oluwaseyi";

	try {
			$conn = new PDO("mysql:host=$db_server;dbname=payroll", $db_user, $db_passwd, array(PDO::ATTR_PERSISTENT=>true));
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
	catch(PDOException $e)
		{
			echo "Failed Connection: " . $e->getMessage();
		}

?>
