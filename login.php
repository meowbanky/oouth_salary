<?php
//Start session
session_start();
require_once('Connections/paymaster.php');

global $conn;
global $salary;
//Array to store validation errors
$errmsg_arr = array();

//Validation error flag
$errflag = false;

$errors         = array();      // array to hold validation errors
$data           = array();      // array to pass back data

//Connect to mysql server

//echo "am here";
//Select database
$db = mysqli_select_db($salary, $database_salary);
if (!$db) {
	die("Unable to select database");
}

function clean($str)
{
	global $salary;
	$str = @trim($str);

	return mysqli_real_escape_string($salary, $str);
}

//Sanitize the POST values
$login = clean($_POST['username']);
$password = clean($_POST['password']);


//Input Validations
if ($login == '') {
	$errmsg_arr[] = 'Username missing';
	$errflag = true;
}
if ($password == '') {
	$errmsg_arr[] = 'Password missing';
	$errflag = true;
}

//If there are input validations, redirect back to the login form
if ($errflag) {
	$_SESSION['ERRMSG_ARR'] = $errmsg_arr;
	session_write_close();
	header("location: index.php");
	exit();
}



//Create query
mysqli_select_db($salary, $database_salary);
$qry = "SELECT employee.name, username.username, username.`password`, username.role, username.staff_id FROM username
INNER JOIN employee ON employee.staff_id = username.staff_id WHERE username = '$login' AND password = '$password' AND username.deleted = 0";
$result = mysqli_query($salary, $qry) or die(mysqli_error($salary));
$row_qry = mysqli_fetch_assoc($result);
$totalRows_result = mysqli_num_rows($result);





//Check whether the query was successful or not
if ($result) {

	if (mysqli_num_rows($result) > 0) {


		session_regenerate_id();
		//$member = mysql_fetch_assoc($result);


		$_SESSION['SESS_MEMBER_ID'] = $row_qry['staff_id'];
		$_SESSION['SESS_FIRST_NAME'] = $row_qry['name'];
		$_SESSION['SESS_LAST_NAME'] = $row_qry['name'];
		$_SESSION['role'] = $row_qry['role'];
		$_SESSION['emptrack'] = 0;
		$_SESSION['empDataTrack'] = 'next';

		$data['success'] = 'true';
		$data['message'] = 'Successfully Login';
	} else {

		$data['success'] = 'false';
		$data['message'] = 'Invalid Username and Password';
	}
} else {
	die("Query failed");
}
echo json_encode($data);
//echo "completed";
