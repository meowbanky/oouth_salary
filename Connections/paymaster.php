<?php
# FileName="Connection_php_mysql.htm"
# Type="MYSQL"
# HTTP="true"
$hostname_salary = "localhost";
$database_salary = "oouthsal_salary";
$username_salary = "oouthsal_root";
$password_salary = "Oluwaseyi@7980";
$salary = mysqli_connect($hostname_salary, $username_salary, $password_salary) or trigger_error(mysqli_error($salary), E_USER_ERROR);


// $db_server = "localhost";
// $db_user = 	"oouthsal_root";
// $db_passwd = "Oluwaseyi@7980";

try {
	$conn = new PDO("mysql:host=$hostname_salary;dbname=$database_salary", $username_salary, $password_salary, array(PDO::ATTR_PERSISTENT => true));
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
	echo "Failed Connection: " . $e->getMessage();
}
