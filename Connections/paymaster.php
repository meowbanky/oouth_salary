<?php
# FileName="Connection_php_mysql.htm"
# Type="MYSQL"
# HTTP="true"
$hostname_salary = "localhost";
$database_salary = "oouthsal_salary3";
$username_salary = "oouthsal_root";
$password_salary = "Oluwaseyi@7980";
$salary = mysqli_connect($hostname_salary, $username_salary, $password_salary);
if (!$salary) {
    trigger_error(mysqli_connect_error(), E_USER_ERROR);
}

try {
	// Removed persistent connection to prevent "too many connections" errors
	$conn = new PDO("mysql:host=$hostname_salary;dbname=$database_salary", $username_salary, $password_salary);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	// Set connection timeout to prevent hanging connections
	$conn->setAttribute(PDO::ATTR_TIMEOUT, 5);
} catch (PDOException $e) {
	error_log("Database Connection Error: " . $e->getMessage());
	echo "Failed Connection: " . $e->getMessage();
}