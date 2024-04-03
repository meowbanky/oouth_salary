<?php
session_start();
ini_set('max_execution_time', '0');
//$connect = mysqli_connect("localhost", "root", "Oluwaseyi", "salary");
include_once('functions.php');
include_once('../Connections/paymaster.php');
include_once('../passwordHash.php');
include_once('create_email.php');
include_once('../backup.php');
$act = strip_tags(addslashes($_GET['act']));
$act = filter_var($_GET['act']);
$source = $_SERVER['HTTP_REFERER'];
//$hostname = gethostbyname($_SERVER['REMOTE_ADDR');
//session variables
$comp = '1';

global $salary;

switch ($act) {

	case 'login':
		$errmsg_arr = array();
		$errflag = false;

		$errors         = array();      // array to hold validation errors
		$data           = array();      // array to pass back data

		$uname = filter_var((filter_var($_POST['username'])));
		$pass = filter_var($_POST['password']);

		if ($uname == '') {
			$errmsg_arr[] = 'Username missing';
			$errflag = true;
		}
		if ($pass == '') {
			$errmsg_arr[] = 'Password missing';
			$errflag = true;
		}
		// if ($errflag) {
		// 	$_SESSION['ERRMSG_ARR'] = $errmsg_arr;
		// 	session_write_close();
		// 	header("location: ../index.php");
		// 	exit();
		// }


		$upass = password_hash(filter_var($pass), PASSWORD_BCRYPT);

		try {
			$query = $conn->prepare('SELECT employee.name, employee.EMAIL,username.username, username.`password`, username.role, username.staff_id FROM username
			INNER JOIN employee ON employee.staff_id = username.staff_id WHERE username = ? AND deleted = ?');
			$fin = $query->execute(array($uname, '0'));


			if (isset($_SESSION['periodstatuschange'])) {
				unset($_SESSION['periodstatuschange']);
			}
			// password_verify( 
			if (($row = $query->fetch()) and (password_verify($pass, $row['password']))) {

				$_SESSION['logged_in'] = '1';
				$_SESSION['user'] = $row['username'];
				$_SESSION['SESS_MEMBER_ID'] = $row['staff_id'];
				$_SESSION['email'] = $row['EMAIL'];
				$_SESSION['SESS_FIRST_NAME'] = $row['name'];
				$_SESSION['SESS_LAST_NAME'] = $row['name'];
				//$_SESSION['companyid'] = $row['companyId'];
				$_SESSION['role'] = $row['role'];
				$_SESSION['emptrack'] = 0;
				$_SESSION['empDataTrack'] = 'next';


				$data['success'] = 'true';
				$data['message'] = 'Successfully Login';


				//Get current active period for the organization
				$payp = $conn->prepare('SELECT periodId, description, periodYear FROM payperiods WHERE active = ? ORDER BY periodId ASC LIMIT 1');
				$myperiod = $payp->execute(array(1));
				$final = $payp->fetch();
				$_SESSION['currentactiveperiod'] = $final['periodId'];
				$_SESSION['activeperiodDescription'] = $final['description'] . " " . $final['periodYear'];
				//exit($_SESSION['currentactiveperiod');

				//If temp period change, reset session
				if (isset($_SESSION['periodstatuschange'])) {
					unset($_SESSION['periodstatuschange']);
				}

				//$page = "../../dashboard.php";
				$_SESSION['msg'] = $msg = "Welcome " . $_SESSION['SESS_FIRST_NAME'];
				$_SESSION['alertcolor'] = $type = "success";
				//header('Location: ../../dashboard.php');
				//redirect($msg, $type, $page);

			} else {

				$_SESSION['msg'] = $msg = "Invalid login. Please try again.";
				$_SESSION['alertcolor'] = 'danger';
				$data['success'] = 'false';
				$data['message'] = 'Invalid Username and Password';
				// $data['message'] = $upass;
				// header('Location: ' . $source);
			}
		} catch (PDOException $e) {
			echo $e->getMessage();
		}
		echo json_encode($data);
		break;


	case 'adduser':
		//
		$staff_id = filter_var($_POST['staff_id'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		//$ulname = filter_var($_POST['ulname'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$uemail = filter_var((filter_var($_POST['uemail'], FILTER_SANITIZE_EMAIL)), FILTER_VALIDATE_EMAIL);
		$upass1 = filter_var($_POST['upass'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$upass2 = filter_var($_POST['upass1'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);

		if ($upass1 == $upass2) {
			try {

				$query = $conn->prepare('SELECT * FROM username WHERE staff_id = ? ');
				$res = $query->execute(array($staff_id));
				$existtrans = $query->fetch();

				if ($existtrans) {
					//user exists
					$_SESSION['msg'] = "A user account associated with the supplied Staff ID exists.";
					$_SESSION['alertcolor'] = "danger";
					$source = $_SERVER['HTTP_REFERER'];
					$upass = password_hash($upass1, PASSWORD_DEFAULT);
					$query = 'UPDATE username SET  password = ?, position=?, role=?, deleted=? WHERE staff_id = ?';
					$conn->prepare($query)->execute(array($upass, 'Admin', 'Admin', '0', $staff_id));

					header('Location: ' . $source);
				} else {
					$upass = password_hash($upass1, PASSWORD_DEFAULT);

					$query = 'INSERT INTO username (staff_id, username, password, position, role, deleted) VALUES (?,?,?,?,?,?)';
					$conn->prepare($query)->execute(array($staff_id, $staff_id, $upass, 'Admin', 'Admin', '0'));

					$_SESSION['msg'] = $msg = 'User Successfully Created';
					$_SESSION['alertcolor'] = $type = 'success';
					$source = $_SERVER['HTTP_REFERER'];
					header('Location: ' . $source);
				}
			} catch (PDOException $e) {
				echo $e->getMessage();
			}
		} else {

			$_SESSION['msg'] = $msg = 'Entered passwords are not matching.';
			$_SESSION['alertcolor'] = $type = 'danger';
			header('Location: ' . $source);
		}

		break;

	case 'createcompanyaccount':
		//create new company
		$title = "New Payroll Account";
		$companyname = filter_var($_POST['fullname'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$contactemail = filter_var((filter_var($_POST['email'], FILTER_SANITIZE_EMAIL)), FILTER_VALIDATE_EMAIL);
		$contactphone = filter_var($_POST['phone'], FILTER_VALIDATE_INT);
		$companyaddress = filter_var($_POST['address'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$compcity = filter_var($_POST['city'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);

		$useremail = filter_var((filter_var($_POST['username'], FILTER_SANITIZE_EMAIL)), FILTER_VALIDATE_EMAIL);
		$userfname = filter_var($_POST['ufname'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$userlname = filter_var($_POST['ulname'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$userpass1 = filter_var($_POST['password'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$userpass = password_hash($userpass1, PASSWORD_DEFAULT);

		try {
			$query = $conn->prepare('SELECT * FROM users WHERE emailAddress = ? AND active = ? ');
			$res = $query->execute(array($useremail, '1'));
			$existtrans = $query->fetch();

			if ($existtrans) {
				//same transaction for current employee, current period posted
				$_SESSION['msg'] = "A user account associated with the supplied email exists.";
				$_SESSION['alertcolor'] = "danger";
				$source = $_SERVER['HTTP_REFERER'];
				header('Location: ' . $source);
			} else {

				$query = 'INSERT INTO company (companyName, city, companyAddress, companyEmail, contactTelephone) VALUES (?,?,?,?,?)';
				$conn->prepare($query)->execute(array($companyname, $compcity, $companyaddress, $contactemail, $contactphone));
				$last_id = $conn->lastInsertId();

				$query = 'INSERT INTO users (emailAddress, password, userTypeId, firstName, lastName, companyId, active) VALUES (?,?,?,?,?,?,?)';
				$conn->prepare($query)->execute(array($useremail, $userpass, '1', $userfname, $userlname, $last_id, '0'));
				$latestuserinsert = $conn->lastInsertId();

				//user account becomes active after validating emailed link
				//Send email validation
				//Generate update token
				$reset_token = bin2hex(openssl_random_pseudo_bytes(32));

				//write token to token table and assign validity state, creation timestamp
				$tokenrecordtime = date('Y-m-d H:i:s');


				//check for any previous tokens and invalidate
				$tokquery = $conn->prepare('SELECT * FROM reset_token WHERE userEmail = ? AND valid = ? AND type = ?');
				$fin = $tokquery->execute(array($useremail, '1', '2'));

				if ($row = $tokquery->fetch()) {
					$upquery = 'UPDATE reset_token SET valid = ? WHERE userEmail = ? AND valid = ?';
					$conn->prepare($upquery)->execute(array('0', $useremail, '1'));
				}

				$tokenquery = 'INSERT INTO reset_token (userEmail, token, creationTime, valid, type) VALUES (?,?,?,?,?)';
				$conn->prepare($tokenquery)->execute(array($useremail, $reset_token, $tokenrecordtime, '1', '2'));

				//exit($resetemail . " " . $reset_token);

				$sendmessage = "You've recently created a new Red Payroll account linked to the email address: " . $useremail . "<br /><br />To activate your account, click the link below:<br /><br /> " . $sysurl . 'validate.php?act=auth&jam=' . $latestuserinsert . '&queue=' . $last_id . '&token=' . $reset_token;
				//generate reset cdde and append to email submitted

				require 'phpmailer/PHPMailerAutoload.php';

				$mail = new PHPMailer;

				$mail->SMTPDebug = 3;                               // Enable verbose debug output

				$mail->isSMTP();                                      // Set mailer to use SMTP
				$mail->Host = 'smtp.zoho.com';  // Specify main and backup SMTP servers
				$mail->SMTPAuth = true;                               // Enable SMTP authentication
				$mail->Username = 'noreply@redsphere.co.ke';                 // SMTP username
				$mail->Password = 'redsphere_2017***';                           // SMTP password
				$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
				$mail->Port = 587;                                    // TCP port to connect to

				$mail->setFrom('noreply@redsphere.co.ke', 'Red Payroll');
				$mail->addAddress($useremail, 'Redsphere Payroll');     // Add a recipient
				//$mail->addAddress('ellen@example.com');               // Name is optional
				$mail->addReplyTo('noreply@redsphere.co.ke', 'Red Payroll');
				//$mail->addCC('fgesora@gmail.com');
				$mail->addBCC('fgesora@gmail.com');

				//$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
				//$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
				$mail->isHTML(true);                                  // Set email format to HTML

				$mail->Subject = $title;
				$mail->Body    = $sendmessage;
				$mail->AltBody = $sendmessage;

				if (!$mail->send()) {
					//exit($mail->ErrorInfo);
					echo 'Mailer Error: ' . $mail->ErrorInfo;
					//  $_SESSION('msg') = "Failed. Error sending email.";
					$_SESSION['alertcolor'] = "danger";
					header("Location: " . $source);
				} else {
					$status = "Success";
					$_SESSION['msg'] = "An activation link has been sent to the provided email address. Please activate your account in order to log in.";
					$_SESSION['alertcolor'] = "success";
					header("Location: " . $source);
				}
			}

			/*
					********
					********
					Check if user account exists
					********
					********
					*/
		} catch (PDOException $e) {
			echo $e->getMessage();
		}

		//exit($companyname . ', ' . $contactemail . ', ' . $last_id);
		break;


	case 'addcostcenter':
		$ccname = filter_var($_POST['cctrname'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);

		try {
			$query = 'INSERT INTO company_costcenters (companyId, costCenterName, active) VALUES (?,?,?)';
			$conn->prepare($query)->execute(array($_SESSION['companyid'], $ccname, '1'));
			$_SESSION['msg'] = $msg = 'Cost Center successfully Created';
			$_SESSION['alertcolor'] = $type = 'success';
			$source = $_SERVER['HTTP_REFERER'];
			header('Location: ' . $source);
		} catch (PDOException $e) {
			echo $e->getMessage();
		}

		break;

	case 'earningchange':
		$ccname = filter_var($_POST['cctrname'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);

		try {
			$query = 'INSERT INTO company_costcenters (companyId, costCenterName, active) VALUES (?,?,?)';
			$conn->prepare($query)->execute(array($_SESSION['companyid'], $ccname, '1'));
			$_SESSION['msg'] = $msg = 'Cost Center successfully Created';
			$_SESSION['alertcolor'] = $type = 'success';
			$source = $_SERVER['HTTP_REFERER'];
			header('Location: ' . $source);
		} catch (PDOException $e) {
			echo $e->getMessage();
		}

		break;


	case 'adddepartment':
		$dept = filter_var($_POST['deptname']);

		try {

			$query1 = $conn->prepare('SELECT MAX(dept_id)+1 as nextNo FROM tbl_dept');
			$res1 = $query1->execute();
			if ($row1 = $query1->fetch()) {
				$nextNo =  ($row1['nextNo']);
			}

			$query = $conn->prepare('SELECT * FROM tbl_dept WHERE dept = ? ');
			$res = $query->execute(array($dept));
			$existtrans = $query->fetch();

			if ($existtrans) {
				//same transaction for current employee, current period posted
				$_SESSION['msg'] = "Department already existing";
				$_SESSION['alertcolor'] = "danger";
				$source = $_SERVER['HTTP_REFERER'];
				header('Location: ' . $source);
			} else {

				$query = 'INSERT INTO tbl_dept (dept,dept_id) VALUES (?,?)';
				$conn->prepare($query)->execute(array($dept, $nextNo));
				$_SESSION['msg'] = $msg = 'Department successfully Created';
				$_SESSION['alertcolor'] = $type = 'success';
				$source = $_SERVER['HTTP_REFERER'];
				header('Location: ' . $source);
			}
		} catch (PDOException $e) {
			echo $e->getMessage();
		}

		break;

	case 'pfa':
		$pfa = filter_var($_POST['pfaname']);

		try {

			$query1 = $conn->prepare('SELECT MAX(PFACODE)+1 as nextNo FROM tbl_pfa');
			$res1 = $query1->execute();
			if ($row1 = $query1->fetch()) {
				$nextNo =  ($row1['nextNo']);
			}

			$query = $conn->prepare('SELECT * FROM tbl_pfa WHERE PFANAME = ?');
			$res = $query->execute(array($pfa));
			$existtrans = $query->fetch();

			if ($existtrans) {
				//same transaction for current employee, current period posted
				$_SESSION['msg'] = "PFA already existing";
				$_SESSION['alertcolor'] = "danger";
				$source = $_SERVER['HTTP_REFERER'];
				header('Location: ' . $source);
			} else {

				$query = 'INSERT INTO tbl_pfa (PFANAME,PFACODE) VALUES (?,?)';
				$conn->prepare($query)->execute(array($pfa, $nextNo));
				$_SESSION['msg'] = $msg = 'PFA successfully Created';
				$_SESSION['alertcolor'] = $type = 'success';
				$source = $_SERVER['HTTP_REFERER'];
				header('Location: ' . $source);
			}
		} catch (PDOException $e) {
			echo $e->getMessage();
		}

		break;

	case 'bank':
		$bank = filter_var($_POST['bankname']);

		try {

			$query1 = $conn->prepare('SELECT MAX(BCODE)+1 as nextNo FROM tbl_bank');
			$res1 = $query1->execute();
			if ($row1 = $query1->fetch()) {
				$nextNo =  ($row1['nextNo']);
			}

			$query = $conn->prepare('SELECT * FROM tbl_bank WHERE BNAME = ? ');
			$res = $query->execute(array($bank));
			$existtrans = $query->fetch();

			if ($existtrans) {
				//same transaction for current employee, current period posted
				$_SESSION['msg'] = "Bank already existing";
				$_SESSION['alertcolor'] = "danger";
				$source = $_SERVER['HTTP_REFERER'];
				header('Location: ' . $source);
			} else {

				$query = 'INSERT INTO tbl_bank (BNAME,BCODE) VALUES (?,?)';
				$conn->prepare($query)->execute(array($bank, $nextNo));
				$_SESSION['msg'] = $msg = 'Bank successfully Created';
				$_SESSION['alertcolor'] = $type = 'success';
				$source = $_SERVER['HTTP_REFERER'];
				header('Location: ' . $source);
			}
		} catch (PDOException $e) {
			echo $e->getMessage();
		}

		break;

	case 'pfa':
		$pfa = filter_var($_POST['pfaname']);

		try {

			$query1 = $conn->prepare('SELECT MAX(PFACODE)+1 as nextNo FROM tbl_pfa');
			$res1 = $query1->execute();
			if ($row1 = $query1->fetch()) {
				$nextNo =  ($row1['nextNo']);
			}

			$query = $conn->prepare('SELECT * FROM tbl_pfa WHERE PFANAME = ? ');
			$res = $query->execute(array($pfa));
			$existtrans = $query->fetch();

			if ($existtrans) {
				//same transaction for current employee, current period posted
				$_SESSION['msg'] = "PFA already existing";
				$_SESSION['alertcolor'] = "danger";
				$source = $_SERVER['HTTP_REFERER'];
				header('Location: ' . $source);
			} else {

				$query = 'INSERT INTO tbl_pfa (PFANAME,PFACODE) VALUES (?,?)';
				$conn->prepare($query)->execute(array($pfa, $nextNo));
				$_SESSION['msg'] = $msg = 'PFA successfully Created';
				$_SESSION['alertcolor'] = $type = 'success';
				$source = $_SERVER['HTTP_REFERER'];
				header('Location: ' . $source);
			}
		} catch (PDOException $e) {
			echo $e->getMessage();
		}

		break;


	case 'email_deductionlist':

		$deduction_list = filter_var($_POST['newearningcode']);
		$email = filter_var($_POST['email']);
		$cc_email = filter_var($_POST['cc_email']);
		try {

			$query = $conn->prepare('SELECT * FROM email_deductionlist WHERE allow_id = ? ');
			$res = $query->execute(array($deduction_list));
			$existtrans = $query->fetch();

			if ($existtrans) {
				//same transaction for current employee, current period posted
				$_SESSION['msg'] = "PFA already existing";
				$_SESSION['alertcolor'] = "danger";
				$source = $_SERVER['HTTP_REFERER'];
				header('Location: ' . $source);
			} else {

				$query = 'INSERT INTO email_deductionlist (allow_id,email,bcc) VALUES (?,?,?)';
				$conn->prepare($query)->execute(array($deduction_list, $email, $cc_email));
				$_SESSION['msg'] = $msg = 'Email successfully Created';
				$_SESSION['alertcolor'] = $type = 'success';
				$source = $_SERVER['HTTP_REFERER'];
				header('Location: ' . $source);
			}
		} catch (PDOException $e) {
			echo $e->getMessage();
		}

		break;


	case 'amount':
		$amount = filter_var($_POST['amount'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$temp_id = filter_var($_POST['temp_id'], FILTER_VALIDATE_INT);
		if (isset($amount)) {
			if ($amount != "") {
				mysqli_select_db($salary, $database_salary);
				$updateSQL = sprintf(
					"update  tbl_workingFile SET `value` = %s where temp_id = %s",
					filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT),
					filter_var($_POST['temp_id'], FILTER_VALIDATE_INT)
				);

				try { 	 // code to try 		   
					$Result1 = mysqli_query($salary, $updateSQL);
					$source = $_SERVER['HTTP_REFERER'];
					header('Location: ' . $source);
				} catch (PDOException $e) {	  // error handling }	
					// error handling }	
				}
			}
		}
		break;


	case 'addearning':
		$newearning = filter_var($_POST['eddescription'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$recordtime = date('Y-m-d H:i:s');
		//	$recurrent = filter_var($_POST['recurrent'], FILTER_VALIDATE_INT);

		try {
			$getlast = $conn->prepare('SELECT edDesc FROM tbl_earning_deduction WHERE edDesc = ?');
			$res = $getlast->execute(array($newearning));

			if ($row = $getlast->fetch()) {
				$_SESSION['alertcolor'] = $type = "danger";
				$msg = "Duplicate Earning not allowed";
				$source = $_SERVER['HTTP_REFERER'];
				redirect($msg, $type, $source);
			} else {


				$query = 'INSERT INTO tbl_earning_deduction (ed,edDesc, edType, status, operator, edCreatedBy,edCreatedDate) VALUES (?,?,?,?,?,?,?)';
				$conn->prepare($query)->execute(array($newearning, $newearning, '1', 'Active', '+', $_SESSION['SESS_MEMBER_ID'], $recordtime));

				$_SESSION['msg'] = $msg = 'New earning Created';
				$_SESSION['alertcolor'] = $type = 'success';
				$source = $_SERVER['HTTP_REFERER'];
				header('Location: ' . $source);
			}
		} catch (PDOException $e) {
			echo $e->getMessage();
		}

		break;

	case 'editearning':
		$eddescription = filter_var($_POST['eddescription'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$ed_id = filter_var($_POST['ed_id'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);

		$recordtime = date('Y-m-d H:i:s');
		//	$recurrent = filter_var($_POST['recurrent'], FILTER_VALIDATE_INT);

		try {


			$query = 'UPDATE tbl_earning_deduction SET ed = ?,edDesc = ?,edCreatedBy = ?,edCreatedDate = ? where ed_id = ?';
			$conn->prepare($query)->execute(array($eddescription, $eddescription, $_SESSION['SESS_MEMBER_ID'], $recordtime, $ed_id));

			$_SESSION['msg'] = $msg = 'Edited Successfully';
			$_SESSION['alertcolor'] = $type = 'success';
			$source = $_SERVER['HTTP_REFERER'];
			header('Location: ' . $source);
		} catch (PDOException $e) {
			echo $e->getMessage();
		}
		break;


	case 'adddeduction':
		$newearning = filter_var($_POST['eddescription'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		//$recurrent = filter_var($_POST['recurrent'], FILTER_VALIDATE_INT);

		try {
			$getlast = $conn->prepare('SELECT edDesc FROM tbl_earning_deduction WHERE edDesc = ?');
			$res = $getlast->execute(array($newearning));

			if ($row = $getlast->fetch()) {
				$_SESSION['alertcolor'] = $type = "danger";
				$msg = "Duplicate Earning not allowed";
				$source = $_SERVER['HTTP_REFERER'];
				redirect($msg, $type, $source);
			} else {


				$query = 'INSERT INTO tbl_earning_deduction (ed,edDesc, edType, status, operator, edCreatedBy,edCreatedDate) VALUES (?,?,?,?,?,?,?)';
				$conn->prepare($query)->execute(array($newearning, $newearning, '2', 'Active', '-', $_SESSION['SESS_MEMBER_ID'], $recordtime));

				$_SESSION['msg'] = $msg = 'New earning Created';
				$_SESSION['alertcolor'] = $type = 'success';
				$source = $_SERVER['HTTP_REFERER'];
				header('Location: ' . $source);
			}
		} catch (PDOException $e) {
			echo $e->getMessage();
		}

		break;

	case 'addloanparameter':
		$newearning = filter_var($_POST['newloandesc'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		//$recurrent = filter_var($_POST['recurrent'], FILTER_VALIDATE_INT);

		try {
			$getlast = $conn->prepare('SELECT edDesc FROM tbl_earning_deduction WHERE edDesc = ?');
			$res = $getlast->execute(array($newearning));

			if ($row = $getlast->fetch()) {
				$_SESSION['alertcolor'] = $type = "danger";
				$msg = "Duplicate Earning not allowed";
				$source = $_SERVER['HTTP_REFERER'];
				redirect($msg, $type, $source);
			} else {


				$query = 'INSERT INTO tbl_earning_deduction (ed,edDesc, edType, status, operator, edCreatedBy,edCreatedDate) VALUES (?,?,?,?,?,?,?)';
				$conn->prepare($query)->execute(array($newearning, $newearning, '4', 'Active', '-', $_SESSION['SESS_MEMBER_ID'], $recordtime));

				$_SESSION['msg'] = $msg = 'New earning Created';
				$_SESSION['alertcolor'] = $type = 'success';
				$source = $_SERVER['HTTP_REFERER'];
				header('Location: ' . $source);
			}
		} catch (PDOException $e) {
			echo $e->getMessage();
		}

		break;

	case 'addunion':
		$newearning = filter_var($_POST['newunion'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		//$recurrent = filter_var($_POST['recurrent'], FILTER_VALIDATE_INT);

		try {
			$getlast = $conn->prepare('SELECT edDesc FROM tbl_earning_deduction WHERE edDesc = ?');
			$res = $getlast->execute(array($newearning));

			if ($row = $getlast->fetch()) {
				$_SESSION['alertcolor'] = $type = "danger";
				$msg = "Duplicate Earning not allowed";
				$source = $_SERVER['HTTP_REFERER'];
				redirect($msg, $type, $source);
			} else {


				$query = 'INSERT INTO tbl_earning_deduction (ed,edDesc, edType, status, operator, edCreatedBy,edCreatedDate) VALUES (?,?,?,?,?,?,?)';
				$conn->prepare($query)->execute(array($newearning, $newearning, '3', 'Active', '-', $_SESSION['SESS_MEMBER_ID'], $recordtime));

				$_SESSION['msg'] = $msg = 'New earning Created';
				$_SESSION['alertcolor'] = $type = 'success';
				$source = $_SERVER['HTTP_REFERER'];
				header('Location: ' . $source);
			}
		} catch (PDOException $e) {
			echo $e->getMessage();
		}

		break;


	case 'addloan':
		$newloandesc = filter_var($_POST['newloandesc'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		//define 900** as the loan ED Code
		try {
			$getlast = $conn->prepare('SELECT edCode FROM earnings_deductions WHERE edType = ? AND companyId = ? AND active = ? ORDER BY id DESC');
			$res = $getlast->execute(array('Loan', $_SESSION['companyid'], '1'));

			if ($row = $getlast->fetch()) {
				$latestcode = intval($row['edCode']);
				$principleinsertcode = $latestcode + 1;
				$repaymentinsertcode = $latestcode + 2;
			}
			$principleinsertdesc = $newloandesc . 'Loan Principle';
			$repaymentinsertdesc = $newloandesc . 'Loan Repayment';
			exit($principleinsertcode . ',' . $repaymentinsertcode);

			$query = 'INSERT INTO earnings_deductions (edCode, edDesc, edType, companyId, active, recurrentEd) VALUES (?,?,?,?,?,?)';
			$conn->prepare($query)->execute(array($principleinsertcode, $principleinsertdesc, 'Loan', $_SESSION['companyid'], '1', '0'));

			$query = 'INSERT INTO earnings_deductions (edCode, edDesc, edType, companyId, active, recurrentEd) VALUES (?,?,?,?,?,?)';
			$conn->prepare($query)->execute(array($repaymentinsertcode, $repaymentinsertdesc, 'Deduction', $_SESSION['companyid'], '1', '1'));
		} catch (PDOException $e) {
			echo $e->getMessage();
		}

		exit($newloandesc);
		break;



	case 'loan_corporate':
		$currentempl = $_POST['curremployee'];
		$edcode = $_POST['newdeductioncodeloan'];
		$earningamount = trim($_POST['monthlyRepayment']);
		$principal = trim($_POST['Principal']);
		$interest = trim($_POST['interest']);
		$recordtime = date('Y-m-d H:i:s');

		try {

			$query = $conn->prepare('DELETE FROM tbl_debt WHERE staff_id = ?  AND allow_id = ? ');
			$res = $query->execute(array($currentempl, $edcode));

			$query = $conn->prepare('DELETE FROM tbl_repayment WHERE staff_id = ?  AND allow_id = ? ');
			$res = $query->execute(array($currentempl, $edcode));


			$query = $conn->prepare('SELECT * FROM allow_deduc WHERE staff_id = ?  AND allow_id = ? ');
			$res = $query->execute(array($currentempl, $edcode));
			$existtrans = $query->fetch();

			if ($existtrans) {
				//same transaction for current employee, current period posted
				$query2 = 'UPDATE allow_deduc SET value = ?,transcode = ?, date_insert = ?, inserted_by = ? WHERE staff_id = ? AND allow_id = ?';
				$conn->prepare($query2)->execute(array($earningamount, 2, $recordtime, $_SESSION['SESS_MEMBER_ID'], $currentempl, $edcode));


				$query2 = 'INSERT INTO tbl_debt (staff_id, allow_id,date_insert, inserted_by,principal,interest) VALUES (?,?,?,?,?,?)';
				$conn->prepare($query2)->execute(array($currentempl, $edcode, $recordtime, $_SESSION['SESS_MEMBER_ID'], $principal, $interest));

				$_SESSION['alertcolor'] = $type = "success";
				$msg = "Corporate/Loan Updated";
				$source = $_SERVER['HTTP_REFERER'];
				redirect($msg, $type, $source);
			} else {
				if ($earningamount > 0) {

					$query2 = 'INSERT INTO tbl_debt (staff_id, allow_id,date_insert, inserted_by,principal,interest) VALUES (?,?,?,?,?,?)';
					$conn->prepare($query2)->execute(array($currentempl, $edcode, $recordtime, $_SESSION['SESS_MEMBER_ID'], $principal, $interest));


					$query = 'INSERT INTO allow_deduc (staff_id, allow_id, value, transcode, date_insert, inserted_by) VALUES (?,?,?,?,?,?)';
					$conn->prepare($query)->execute(array($currentempl, $edcode, $earningamount, '2', $recordtime, $_SESSION['SESS_MEMBER_ID']));


					$_SESSION['msg'] = $msg = "Corporate/Loan Successfully Saved";
					$_SESSION['alertcolor'] = $type = "success";
					$source = $_SERVER['HTTP_REFERER'];
					//redirect($msg, $type, $source);					
					header('Location: ' . $source);
				} else {



					$_SESSION['msg'] = $msg = "Employee not Entitiled to the Allowance";
					$_SESSION['alertcolor'] = $type = "danger";
					$source = $_SERVER['HTTP_REFERER'];
					//redirect($msg, $type, $source);					
					header('Location: ' . $source);
				}
			}
		} catch (PDOException $e) {
			echo $e->getMessage();
		}
		break;


	case 'addemployeeearning':
		$currentempl = $_POST['curremployee'];
		$edcode = $_POST['newearningcode'];
		$earningamount = str_replace(',', '', trim($_POST['earningamount']));
		$recordtime = date('Y-m-d H:i:s');

		try {
			$query = $conn->prepare('SELECT * FROM allow_deduc WHERE staff_id = ?  AND allow_id = ? ');
			$res = $query->execute(array($currentempl, $edcode));
			$existtrans = $query->fetch();

			if ($existtrans) {
				//same transaction for current employee, current period posted
				$query = 'UPDATE allow_deduc SET value = ?, date_insert = ?, inserted_by = ? WHERE staff_id = ?  AND allow_id = ?';
				$conn->prepare($query)->execute(array($earningamount, $recordtime, $_SESSION['SESS_MEMBER_ID'], $currentempl, $edcode));

				$_SESSION['alertcolor'] = $type = "danger";
				$msg = "Duplicate Earning not allowed";
				$source = $_SERVER['HTTP_REFERER'];
				redirect($msg, $type, $source);
				header('Location: ' . $source);
			} else {
				if ($earningamount > -1) {

					$query = $conn->prepare('SELECT code FROM tbl_earning_deduction WHERE ed_id = ?');
					$res = $query->execute(array($edcode));
					$existtrans = $query->fetch();
					$code = $existtrans['code'];

					$query = 'INSERT INTO allow_deduc (transcode,staff_id, allow_id, value, date_insert, inserted_by) VALUES (?,?,?,?,?,?)';
					$conn->prepare($query)->execute(array($code, $currentempl, $edcode, $earningamount, $recordtime, $_SESSION['SESS_MEMBER_ID']));
					$_SESSION['msg'] = $msg = "Earning successfully saved";
					$_SESSION['alertcolor'] = $type = "success";
					$source = $_SERVER['HTTP_REFERER'];
					//redirect($msg, $type, $source);					
					header('Location: ' . $source);
				} else {
					$_SESSION['msg'] = $msg = "Employee not Entitiled to the Allowance";
					$_SESSION['alertcolor'] = $type = "danger";
					$source = $_SERVER['HTTP_REFERER'];
					//redirect($msg, $type, $source);					
					header('Location: ' . $source);
				}
			}
		} catch (PDOException $e) {
			echo $e->getMessage();
		}
		break;

	case 'tempearnings':
		$currentempl = $_POST['curremployee'];
		$edcode = $_POST['newdeductioncodetemp'];
		$no_times = $_POST['no_times'];
		$earningamount = str_replace(',', '', trim($_POST['deductionamount']));
		$recordtime = date('Y-m-d H:i:s');

		try {
			$query = $conn->prepare('SELECT * FROM allow_deduc WHERE staff_id = ?  AND allow_id = ? ');
			$res = $query->execute(array($currentempl, $edcode));
			$existtrans = $query->fetch();

			if ($existtrans) {
				//same transaction for current employee, current period posted
				$query = 'UPDATE allow_deduc SET counter = ?,value = ?, date_insert = ?, inserted_by = ? WHERE staff_id = ?  AND allow_id = ?';
				$conn->prepare($query)->execute(array($no_times, $earningamount, $recordtime, $_SESSION['SESS_MEMBER_ID'], $currentempl, $edcode));

				$_SESSION['alertcolor'] = $type = "danger";
				$msg = "Duplicate Earning not allowed";
				$source = $_SERVER['HTTP_REFERER'];
				redirect($msg, $type, $source);
				header('Location: ' . $source);
			} else {
				if ($earningamount > -1) {

					$query = $conn->prepare('SELECT code FROM tbl_earning_deduction WHERE ed_id = ?');
					$res = $query->execute(array($edcode));
					$existtrans = $query->fetch();
					$code = $existtrans['code'];

					$query = 'INSERT INTO allow_deduc (counter,transcode,staff_id, allow_id, value, date_insert, inserted_by) VALUES (?,?,?,?,?,?,?)';
					$conn->prepare($query)->execute(array($no_times, $code, $currentempl, $edcode, $earningamount, $recordtime, $_SESSION['SESS_MEMBER_ID']));
					$_SESSION['msg'] = $msg = "Earning successfully saved";
					$_SESSION['alertcolor'] = $type = "success";
					$source = $_SERVER['HTTP_REFERER'];
					//redirect($msg, $type, $source);					
					header('Location: ' . $source);
				} else {
					$_SESSION['msg'] = $msg = "Employee not Entitiled to the Allowance";
					$_SESSION['alertcolor'] = $type = "danger";
					$source = $_SERVER['HTTP_REFERER'];
					//redirect($msg, $type, $source);					
					header('Location: ' . $source);
				}
			}
		} catch (PDOException $e) {
			echo $e->getMessage();
		}
		break;


	case 'addemployeededuction':
		$currentempl = $_POST['curremployee'];
		$edcode = $_POST['newdeductioncode'];
		$deductionamount = trim($_POST['deductionamount']);
		$recordtime = date('Y-m-d H:i:s');


		try {
			$query = $conn->prepare('SELECT * FROM allow_deduc WHERE staff_id = ?  AND allow_id = ? ');
			$res = $query->execute(array($currentempl, $edcode));
			$existtrans = $query->fetch();

			if ($existtrans) {
				//same transaction for current employee, current period posted
				$query = 'UPDATE allow_deduc SET value = ?, date_insert = ?, inserted_by = ? WHERE staff_id = ?  AND allow_id = ? ';
				$conn->prepare($query)->execute(array($deductionamount, $recordtime, $_SESSION['SESS_MEMBER_ID'], $currentempl, $edcode));
				$_SESSION['msg'] = $msg = "Deduction UPdated successfully saved";
				$_SESSION['alertcolor'] = $type = "success";
				$source = $_SERVER['HTTP_REFERER'];
				//redirect($msg, $type, $source);					
				header('Location: ' . $source);
			} else {
				if ($deductionamount > 0) {
					$query = 'INSERT INTO allow_deduc (staff_id, allow_id, value, date_insert, inserted_by,transcode) VALUES (?,?,?,?,?,?)';
					$conn->prepare($query)->execute(array($currentempl, $edcode, $deductionamount, $recordtime, $_SESSION['SESS_MEMBER_ID'], '02'));
					$_SESSION['msg'] = $msg = "Deduction successfully saved";
					$_SESSION['alertcolor'] = $type = "success";
					$source = $_SERVER['HTTP_REFERER'];
					//redirect($msg, $type, $source);					
					header('Location: ' . $source);
				} else {
					$_SESSION['msg'] = $msg = "Employee not Entitiled to the Deduction";
					$_SESSION['alertcolor'] = $type = "danger";
					$source = $_SERVER['HTTP_REFERER'];
					//redirect($msg, $type, $source);					
					header('Location: ' . $source);
				}
			}
		} catch (PDOException $e) {
			echo $e->getMessage();
		}
		break;


	case 'addemployeedeductionunion':

		$currentempl = $_POST['curremployee'];
		$edcode = $_POST['newdeductioncodeunion'];
		$deductionamount = trim($_POST['deductionamountunion']);
		$recordtime = date('Y-m-d H:i:s');


		try {
			$query = $conn->prepare('SELECT * FROM allow_deduc WHERE staff_id = ?  AND allow_id = ? ');
			$res = $query->execute(array($currentempl, $edcode));
			$existtrans = $query->fetch();

			if ($existtrans) {
				//same transaction for current employee, current period posted
				$query = 'UPDATE allow_deduc SET value = ?, date_insert = ?, inserted_by = ? WHERE staff_id = ?  AND allow_id = ? ';
				$conn->prepare($query)->execute(array($deductionamount, $recordtime, $_SESSION['SESS_MEMBER_ID'], $currentempl, $edcode));
				$_SESSION['msg'] = $msg = "Deduction UPdated successfully saved";
				$_SESSION['alertcolor'] = $type = "success";
				$source = $_SERVER['HTTP_REFERER'];
				//redirect($msg, $type, $source);					
				header('Location: ' . $source);
			} else {
				if ($deductionamount > 0) {
					$query = 'INSERT INTO allow_deduc (staff_id, allow_id, value, date_insert, inserted_by,transcode) VALUES (?,?,?,?,?,?)';
					$conn->prepare($query)->execute(array($currentempl, $edcode, $deductionamount, $recordtime, $_SESSION['SESS_MEMBER_ID'], '02'));
					$_SESSION['msg'] = $msg = "Deduction successfully saved";
					$_SESSION['alertcolor'] = $type = "success";
					$source = $_SERVER['HTTP_REFERER'];
					//redirect($msg, $type, $source);					
					header('Location: ' . $source);
				} else {
					$_SESSION['msg'] = $msg = "Employee not Entitiled to the Deduction";
					$_SESSION['alertcolor'] = $type = "danger";
					$source = $_SERVER['HTTP_REFERER'];
					//redirect($msg, $type, $source);					
					header('Location: ' . $source);
				}
			}
		} catch (PDOException $e) {
			echo $e->getMessage();
		}
		break;

	case 'newtempemployeededuction':
		$currentempl = $_POST['curremployee'];
		$edtype = $_POST['payType_id'];
		$edcode = $_POST['newdeductioncode'];
		$earningamount = trim($_POST['deductionamount']);
		$counter = $_POST['no_times'];
		$recordtime = date('Y-m-d H:i:s');

		$query = $conn->prepare('SELECT * FROM tbl_earning_deduction WHERE ed_id = ?');
		$res = $query->execute(array($edcode));
		if ($row = $query->fetch()) {
			echo ($row['operator']);
		}

		try {
			$query = $conn->prepare('SELECT * FROM tbl_temp WHERE staff_id = ?  AND allow_id = ? ');
			$res = $query->execute(array($currentempl, $edcode));
			$existtrans = $query->fetch();

			if ($existtrans) {
				//same transaction for current employee, current period posted
				$query = 'UPDATE tbl_temp SET value = ?, date_insert = ?, inserted_by = ?,counter = ? WHERE staff_id = ?  AND allow_id = ? ';
				$conn->prepare($query)->execute(array($earningamount, $recordtime, $_SESSION['SESS_MEMBER_ID'], $counter, $currentempl, $edcode));
				$_SESSION['msg'] = $msg = "Temp Deduction / Allowance successfully saved";
				$_SESSION['alertcolor'] = $type = "success";
				$source = $_SERVER['HTTP_REFERER'];
				//redirect($msg, $type, $source);					
				header('Location: ' . $source);
			} else {
				if ($earningamount > 0) {
					$query = 'INSERT INTO tbl_temp (staff_id, allow_id, value, date_insert, inserted_by,type,counter) VALUES (?,?,?,?,?,?,?)';
					$conn->prepare($query)->execute(array($currentempl, $edcode, $earningamount, $recordtime, $_SESSION['SESS_MEMBER_ID'], $row['operator'], $counter));
					$_SESSION['msg'] = $msg = "Temp Deduction/Earning successfully saved";
					$_SESSION['alertcolor'] = $type = "success";
					$source = $_SERVER['HTTP_REFERER'];
					//redirect($msg, $type, $source);					
					header('Location: ' . $source);
				} else {
					$_SESSION['msg'] = $msg = "Employee not Entitiled to the Deduction";
					$_SESSION['alertcolor'] = $type = "danger";
					$source = $_SERVER['HTTP_REFERER'];
					//redirect($msg, $type, $source);					
					header('Location: ' . $source);
				}
			}
		} catch (PDOException $e) {
			echo $e->getMessage();
		}
		break;

	case 'addallowance_deduction':
		$deductionname = filter_var($_POST['deductionname'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$newdeductionCode = trim($_POST['newdeductionCode']);
		$recordtime = date('Y-m-d H:i:s');
		if ($newdeductionCode < 2) {
			$operator = '+';
		} else {

			$operator = '-';
		}

		try {
			$query = $conn->prepare('SELECT * FROM tbl_earning_deduction WHERE ed = ? or edDesc = ?');
			$res = $query->execute(array($deductionname, $deductionname));
			$existtrans = $query->fetch();

			if ($existtrans) {
				//same transaction for current employee, current period posted
				$_SESSION['msg'] = $msg = "Deduction Already Existing";
				$_SESSION['alertcolor'] = $type = "danger";
				$source = $_SERVER['HTTP_REFERER'];
				//redirect($msg, $type, $source);					
				header('Location: ' . $source);
			} else {

				$query = 'INSERT INTO tbl_earning_deduction (ed, edType, edDesc, edCreatedDate,edCreatedBy,operator ) VALUES (?,?,?,?,?,?)';
				$conn->prepare($query)->execute(array($deductionname, $newdeductionCode, $deductionname, $recordtime, $_SESSION['SESS_MEMBER_ID'], $operator));
				$_SESSION['msg'] = $msg = "Earning successfully saved";
				$_SESSION['alertcolor'] = $type = "success";
				$source = $_SERVER['HTTP_REFERER'];
				//redirect($msg, $type, $source);					
				header('Location: ' . $source);
			}
		} catch (PDOException $e) {
			echo $e->getMessage();
		}
		break;

	case 'editorganization':
		$compname = filter_var($_POST['compname'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$city = filter_var($_POST['city'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$county = filter_var($_POST['county'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$compemail = filter_var((filter_var($_POST['compemail'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS)), FILTER_VALIDATE_EMAIL);
		$compphone = filter_var($_POST['compphone'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$companypin = filter_var($_POST['companypin'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$nssfnumber = filter_var($_POST['nssfnumber'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$nhifnumber = filter_var($_POST['nhifnumber'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);

		$startyear = date('Y-m-d', strtotime(filter_var($_POST['startyear'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS)));
		$endyear = date('Y-m-d', strtotime(filter_var($_POST['endyear'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS)));

		try {
			$query = 'INSERT INTO company (companyName, city, county, companyEmail, contactTelephone, companyPin, companyNssf, companyNhif, companyId, yearStart, yearEnd) VALUES (?,?,?,?,?,?,?,?,?,?,?)';
			$conn->prepare($query)->execute(array($compname, $city, $county, $compemail, $compphone, $companypin, $nssfnumber, $nhifnumber, $_SESSION['companyid'], $startyear, $endyear));
			$msg = "Company Details successfully saved";
			$type = "success";
			$source = $_SERVER['HTTP_REFERER'];
			redirect($msg, $type, $source);
		} catch (PDOException $e) {
			echo $e->getMessage();
		}

		break;


	case 'addperiod':
		$periodname = filter_var($_POST['perioddesc']);
		$periodyear = filter_var($_POST['periodyear']);
		$periodDescription = $periodname . " " . $periodyear;
		//exit(var_dump(is_int($_SESSION['currentactiveperiod')));
		try {

			//backup($hostname_salary, $username_salary, $password_salary, $database_salary);

			//check for replication and create period
			$query = $conn->prepare('SELECT * FROM payperiods WHERE description = ? AND periodYear = ? ');
			$fin = $query->execute(array($periodname, $periodyear));

			if ($row = $query->fetch()) {
				$_SESSION['msg'] = "Selected period values already exist.";
				$_SESSION['alertcolor'] = "danger";
				header('Location: ' . $source);
			} else {
				//Get last id in table
				$payp = $conn->prepare('SELECT periodId, description FROM payperiods  ORDER BY id DESC LIMIT 1');
				$myperiod = $payp->execute();
				$final = $payp->fetch();

				$workperiod = intval($final['periodId']);
				$insertPayId = $workperiod + 1;

				$query = 'INSERT INTO payperiods (periodId, description, periodYear, active, payrollRun) VALUES (?,?,?,?,?)';
				$conn->prepare($query)->execute(array($insertPayId, $periodname, $periodyear, '1', '0'));


				$query2 = 'UPDATE payperiods SET active = ? WHERE periodId = ?';
				$conn->prepare($query2)->execute(array(0, $workperiod));

				$query3 = 'DELETE FROM allow_deduc WHERE allow_id = ?';
				$conn->prepare($query3)->execute(array(3));

				$_SESSION['msg'] = "New Period Succesfully Created";
				$_SESSION['alertcolor'] = "success";
				header('Location: ' . $source);
			}
		} catch (PDOException $e) {
			echo $e->getMessage();
		}
		break;


	case 'closeActivePeriod':
		try {

			//reset period id
			//reset assigned active period id

			exit('closeActivePeriod');
		} catch (PDOEXception $e) {
			echo $e->getMessage();
		}
		break;

	case 'deletecurrentperiod':

		$period = filter_var($_GET['activeperiodID']);

		$payrollquery2 = $conn->prepare('SELECT * FROM payperiods WHERE periodId = ? and payrollRun = ?');
		$payrollquery2->execute(array($period, 1));
		//$deduc = $payrollquery2->fetchAll(PDO::FETCH_ASSOC);

		if ($row = $payrollquery2->fetch()) {
			try {

				$query = 'DELETE FROM tbl_master where period = ?';
				$conn->prepare($query)->execute(array($period));

				$query = 'DELETE FROM master_staff where period = ?';
				$conn->prepare($query)->execute(array($period));


				$query = 'DELETE FROM tbl_repayment where period = ?';
				$conn->prepare($query)->execute(array($period));

				$payrollquery2 = $conn->prepare('SELECT completedloan.id, completedloan.type,completedloan.staff_id, completedloan.allow_id, completedloan.period, completedloan.`value` FROM completedloan WHERE period = ?');
				$payrollquery2->execute(array($period));
				$deduc = $payrollquery2->fetchAll(PDO::FETCH_ASSOC);
				foreach ($deduc as $row => $link2) {
					$query = 'INSERT INTO allow_deduc (staff_id,allow_id,`value`,transcode) VALUES (?,?,?,?)';
					$conn->prepare($query)->execute(array($link2['staff_id'], $link2['allow_id'], $link2['value'], $link2['type']));
				}
				$query = 'DELETE FROM completedloan where period = ?';
				$conn->prepare($query)->execute(array($period));

				$query = 'UPDATE payperiods SET payrollRun = ? where periodId = ?';
				$conn->prepare($query)->execute(array(0, $period));



				$_SESSION['msg'] = $msg = $_SESSION['activeperiodDescription'] . " Succesfully Deleted.";
				$_SESSION['alertcolor'] = 'success';
				header('Location: ' . $source);
			} catch (PDOException $e) {
				echo $e->getMessage();
			}
		} else {
			echo '0';
		}
		break;

	case 'deletecurrentstaffPayslip':

		$period = filter_var($_SESSION['currentactiveperiod'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$staff_id = filter_var($_POST['thisemployee'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);

		$payrollquery2 = $conn->prepare('SELECT * FROM payperiods WHERE periodId = ? and payrollRun = ?');
		$payrollquery2->execute(array($period, 1));
		//$deduc = $payrollquery2->fetchAll(PDO::FETCH_ASSOC);

		if ($row = $payrollquery2->fetch()) {
			try {

				$query = 'DELETE FROM tbl_master where period = ? and staff_id = ?';
				$conn->prepare($query)->execute(array($period, $staff_id));

				$query = 'DELETE FROM master_staff where period = ? and staff_id = ?';
				$conn->prepare($query)->execute(array($period, $staff_id));


				$query = 'DELETE FROM tbl_repayment where period = ? and staff_id = ?';
				$conn->prepare($query)->execute(array($period, $staff_id));

				$payrollquery2 = $conn->prepare('SELECT completedloan.id, completedloan.type,completedloan.staff_id, completedloan.allow_id, completedloan.period, completedloan.`value` FROM completedloan WHERE period = ? and staff_id = ?');
				$payrollquery2->execute(array($period, $staff_id));
				$deduc = $payrollquery2->fetchAll(PDO::FETCH_ASSOC);
				foreach ($deduc as $row => $link2) {
					$query = 'INSERT INTO allow_deduc (staff_id,allow_id,`value`,transcode) VALUES (?,?,?,?)';
					$conn->prepare($query)->execute(array($link2['staff_id'], $link2['allow_id'], $link2['value'], $link2['type']));
				}
				$query = 'DELETE FROM completedloan where period = ? AND staff_id = ?';
				$conn->prepare($query)->execute(array($period, $staff_id));

				//$query = 'UPDATE payperiods SET payrollRun = ? where periodId = ?';
				//$conn->prepare($query)->execute(array(0,$period));



				$_SESSION['msg'] = $msg = $staff_id . " Succesfully Deleted.";
				$_SESSION['alertcolor'] = 'success';
				header('Location: ' . $source);
			} catch (PDOException $e) {
				echo $e->getMessage();
			}
		} else {
			$_SESSION['msg'] = $msg = "Payroll for {$staff_id} has not been Run.";
			$_SESSION['alertcolor'] = 'warning';
			header('Location: ' . $source);
		}
		break;

	case 'activateclosedperiod':
		try {
			$reactivateperiodid = filter_var($_POST['reactivateperiodid'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			//exit('activateclosedperiod ' . $reactivateperiodid);

			//Change period session variables
			$_SESSION['currentactiveperiod'] = $reactivateperiodid;

			$periodquery = $conn->prepare('SELECT description, periodYear FROM payperiods WHERE periodId = ?');
			$perres = $periodquery->execute(array($_SESSION['currentactiveperiod']));
			if ($rowp = $periodquery->fetch()) {
				$reactivatedperioddesc = $rowp['description'];
				$reactivatedperiodyear = $rowp['periodYear'];
			}

			$_SESSION['activeperiodDescription'] = $reactivatedperioddesc . ' ' . $reactivatedperiodyear;

			//Ensure all openview status are reset before activating particular one
			$statuschange = $conn->prepare('UPDATE payperiods SET openview = ? ');
			$perres = $statuschange->execute(array('0'));

			//set openview status
			$statuschange = $conn->prepare('UPDATE payperiods SET openview = ? WHERE periodId = ?');
			$perres = $statuschange->execute(array('1', $_SESSION['currentactiveperiod']));
			$_SESSION['periodstatuschange'] = '1';

			$_SESSION['msg'] = "You are now viewing data from a closed period. Transactions are not allowed.";
			$_SESSION['alertcolor'] = "success";
			header('Location: ' . $source);
		} catch (PDOException $e) {
			echo $e->getMessage();
		}
		break;


	case 'addNewEmp':
		//check for existing same employee number


		$emp_no = filter_var($_POST['emp_no']);
		$namee = ucwords(strtolower(strip_tags(addslashes($_POST['namee']))));
		$dept = filter_var($_POST['dept']);
		$designation = ucwords(strtolower(strip_tags(addslashes($_POST['designation']))));
		$callType = filter_var($_POST['callType']);
		$hazardType = filter_var($_POST['hazardType']);
		$grade = filter_var($_POST['grade']);
		$gradestep = filter_var($_POST['gradestep']);
		$doe = date('Y-m-d', strtotime(filter_var($_POST['doe'])));
		//$dob = date('Y-m-d', strtotime(filter_var($_POST['dob'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS)));
		$bank = filter_var($_POST['bank']);
		$acct_no = filter_var($_POST['acct_no']);
		$email = filter_var($_POST['email']);
		$position = strpos($email, '@oouth.com');
		
		if($position == true){
		    
		$insertemail = $email;
		}else{
		    	$insertemail = $email . '@oouth.com';
		}
	
		$pfa = filter_var($_POST['pfa']);
		$rsa_pin = filter_var($_POST['rsa_pin']);
		$recordtime	= $recordtime = date('Y-m-d H:i:s');
		//validate for empty mandatory fields

		try {
			//check for replication and create period


			$query = 'INSERT INTO employee (EMAIL,HARZAD_TYPE,employee.staff_id,employee.`NAME`, employee.EMPDATE, employee.DEPTCD, employee.POST, employee.GRADE, employee.STEP, employee.ACCTNO, employee.BCODE, employee.CALLTYPE, employee.STATUSCD, employee.PFACODE, employee.PFAACCTNO,userID,editTime) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';

			$conn->prepare($query)->execute(array($insertemail, $hazardType, $emp_no, $namee, $doe, $dept, $designation, $grade, $gradestep, $acct_no, $bank, $callType, 'A', $pfa, $rsa_pin, $_SESSION['SESS_MEMBER_ID'], $recordtime,));
			//createEmail
			createEmail($email);

			$_SESSION['msg'] = $msg = "Employee Successfully added.";
			$_SESSION['alertcolor'] = 'success';
			header('Location: ' . $source);
			//redirect($msg,$type,$source);



		} catch (PDOException $e) {
			echo $e->getMessage();
		}

		break;


	case 'updateEmp':
		//check for existing same employee number


		$emp_no = filter_var($_POST['emp_no'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$namee = ucwords(strtolower(strip_tags(addslashes($_POST['namee']))));
		$dept = filter_var($_POST['dept'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$post = ucwords(strtolower(strip_tags(addslashes($_POST['post']))));
		$callType = filter_var($_POST['callType'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$hazardType = filter_var($_POST['hazardType']);
		$grade = filter_var($_POST['grade'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$gradestep = filter_var($_POST['gradestep'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$doe = date('Y-m-d', strtotime(filter_var($_POST['doe'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS)));
		$dob = date('Y-m-d', strtotime(filter_var($_POST['dob'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS)));
		$bank = filter_var($_POST['bank'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$acct_no = filter_var($_POST['acct_no'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$pfa = filter_var($_POST['pfa'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$rsa_pin = filter_var($_POST['rsa_pin'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);

		//					$citizenship = filter_var($_POST['citizenship'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);	
		//
		//					$emppin = filter_var($_POST['emppin'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		//					$empnssf = filter_var($_POST['empnssf'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		//					$empnhif = filter_var($_POST['empnhif'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		//					$empbank = ucwords(strtolower(strip_tags(addslashes($_POST['empbank']))));
		//					$empbankbranch = ucwords(strtolower(strip_tags(addslashes($_POST['empbankbranch']))));
		//
		//					$empacctnum = ucwords(strtolower(strip_tags(addslashes($_POST['empacctnum']))));
		//					$empdept = ucwords(strtolower(strip_tags(addslashes($_POST['empdept']))));
		//					$empcompbranch = ucwords(strtolower(strip_tags(addslashes($_POST['empcompbranch']))));
		//					$emptype = ucwords(strtolower(strip_tags(addslashes($_POST['emptype']))));
		//					$empnumber = filter_var($_POST['empnumber'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		//					$employdate = date('Y-m-d', strtotime(filter_var($_POST['employdate'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS)));
		//					$empposition = ucwords(strtolower(strip_tags(addslashes($_POST['empposition']))));

		//validate for empty mandatory fields

		try {
			//check for replication and create period



			$query = 'UPDATE employee SET HARZAD_TYPE = ?,employee.`NAME` = ? ,employee.POST = ?, employee.DEPTCD = ?, employee.CALLTYPE = ?, employee.GRADE = ?, employee.STEP = ?, employee.EMPDATE = ?, employee.BCODE = ?, employee.ACCTNO = ?, employee.PFACODE = ?, employee.PFAACCTNO = ? WHERE staff_id = ?';
			//$query = 'INSERT INTO employees (empNumber, fName, lName, gender, idNumber, companyId, companyDept, companyBranch, empType, dob, citizenship, empTaxPin, empNssf, empNhif, empEmplDate, empPosition, active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';

			$conn->prepare($query)->execute(array($hazardType, $namee, $post, $dept,  $callType, $grade, $gradestep, $doe, $bank, $acct_no, $pfa, $rsa_pin, $emp_no));


			$_SESSION['msg'] = $msg = "Employee Successfully updated.";
			$_SESSION['alertcolor'] = 'success';
			header('Location: ' . $source);
			//redirect($msg,$type,$source);



		} catch (PDOException $e) {
			echo $e->getMessage();
		}

		break;

	case 'getPreviousEmployee':

		$_SESSION['emptrack'] = $_SESSION['emptrack'] - 1;
		header('Location: ' . $source);
		break;


	case 'getNextEmployee':

		$_SESSION['emptrack'] = $_SESSION['emptrack'] + 1;
		$_SESSION['empDataTrack'] = 'next';
		header('Location: ' . $source);

		break;


	case 'retrieveLeaveData':

		$leavestate = filter_var($_GET['state'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$_SESSION['leavestate'] = $leavestate;

		header('Location: ' . $source);


		break;

	case 'retrieveSingleEmployeeData':

		if (isset($_POST['item'])) {

			$_POST['item'] = $_POST['item'];
		} else {

			$_POST['item'] = -1;
		}
		$empnumber = filter_var($_POST['item'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$_SESSION['empDataTrack'] = 'option';
		$_SESSION['emptNumTack'] = $empnumber;

		header('Location: ' . $source);

		break;

	case 'retrieveSingleEmployee1':

		$empnumber = filter_var($_POST['item'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$_SESSION['empDataTrack'] = 'option';
		$_SESSION['emptNumTack'] = $empnumber;
		//$source = 'employee.php?staff_id=$empnumber';
		//echo $source;
		header('Location: ' . $source);

		break;

	case 'vtrans':
		$empRecordId = filter_var($_GET['td'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		//exit($empRecordId);
		$_SESSION['empDataTrack'] = 'option';
		$_SESSION['emptNumTack'] = $empRecordId;

		header('Location: ../empearnings.php');
		break;


	case 'runCurrentEmployeePayroll':

		define('TAX_RELIEF', '1280');

		$thisemployee = filter_var($_POST['thisemployee'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);

		//check if employee has basic salary, if not return error & exit
		$query = $conn->prepare('SELECT earningDeductionCode FROM employee_earnings_deductions WHERE employeeId = ? AND companyId = ? AND earningDeductionCode = ? AND payPeriod = ? AND active = ? ');
		$rerun = $query->execute(array($thisemployee, $_SESSION['companyid'], '200', $_SESSION['currentactiveperiod'], '1'));

		if (!$row = $query->fetch()) {
			$_SESSION['msg'] = $msg = "This employee has no basic salary. Please assign basic salary in order to process employee's earnings.";
			$_SESSION['alertcolor'] = 'danger';
			header('Location: ' . $source);
		} else {

			//check if employee rerun
			try {
				$query = $conn->prepare('SELECT * FROM employee_earnings_deductions WHERE employeeId = ? AND companyId = ? AND earningDeductionCode = ? AND payPeriod = ? AND active = ? ');
				$rerun = $query->execute(array($thisemployee, $_SESSION['companyid'], '601', $_SESSION['currentactiveperiod'], '1'));

				if ($row = $query->fetch()) {

					$query = $conn->prepare('SELECT * FROM employee_earnings_deductions WHERE employeeId = ? AND companyId = ? AND transactionType = ? AND payPeriod = ? AND active = ? ');
					$fin = $query->execute(array($thisemployee, $_SESSION['companyid'], 'Earning', $_SESSION['currentactiveperiod'], '1'));
					$res = $query->fetchAll(PDO::FETCH_ASSOC);
					$thisemployeeearnings = 0;

					foreach ($res as $row => $link) {
						$thisemployeeearnings = $thisemployeeearnings + $link['amount'];
					}

					$recordtime = date('Y-m-d H:i:s');
					//Run with an update query
					$grossquery = 'UPDATE employee_earnings_deductions SET amount = ?, editTime = ?, userId = ? WHERE employeeId = ? AND companyId = ? AND earningDeductionCode = ? AND payPeriod = ? AND active = ?';
					$conn->prepare($grossquery)->execute(array($thisemployeeearnings, $recordtime, $_SESSION['user'], $thisemployee, $_SESSION['companyid'], '601',  $_SESSION['currentactiveperiod'], '1'));

					//NHIF Bands
					if ($thisemployeeearnings > 0 && $thisemployeeearnings < 5999) {
						$thisEmpNhif = 150;
					} elseif ($thisemployeeearnings > 5999 && $thisemployeeearnings <= 7999) {
						$thisEmpNhif = 300;
					} elseif ($thisemployeeearnings > 7999 && $thisemployeeearnings <= 11999) {
						$thisEmpNhif = 400;
					} elseif ($thisemployeeearnings > 11999 && $thisemployeeearnings <= 14999) {
						$thisEmpNhif = 500;
					} elseif ($thisemployeeearnings > 14999 && $thisemployeeearnings <= 19999) {
						$thisEmpNhif = 600;
					} elseif ($thisemployeeearnings > 19999 && $thisemployeeearnings <= 24999) {
						$thisEmpNhif = 750;
					} elseif ($thisemployeeearnings > 24999 && $thisemployeeearnings <= 29999) {
						$thisEmpNhif = 850;
					} elseif ($thisemployeeearnings > 29999 && $thisemployeeearnings <= 34999) {
						$thisEmpNhif = 900;
					} elseif ($thisemployeeearnings > 34999 && $thisemployeeearnings <= 39999) {
						$thisEmpNhif = 950;
					} elseif ($thisemployeeearnings > 39999 && $thisemployeeearnings <= 44999) {
						$thisEmpNhif = 1000;
					} elseif ($thisemployeeearnings > 44999 && $thisemployeeearnings <= 49999) {
						$thisEmpNhif = 1100;
					} elseif ($thisemployeeearnings > 49999 && $thisemployeeearnings <= 59999) {
						$thisEmpNhif = 1200;
					} elseif ($thisemployeeearnings > 59999 && $thisemployeeearnings <= 69999) {
						$thisEmpNhif = 1300;
					} elseif ($thisemployeeearnings > 69999 && $thisemployeeearnings <= 79999) {
						$thisEmpNhif = 1400;
					} elseif ($thisemployeeearnings > 79999 && $thisemployeeearnings <= 89999) {
						$thisEmpNhif = 1500;
					} elseif ($thisemployeeearnings > 89999 && $thisemployeeearnings <= 99999) {
						$thisEmpNhif = 1600;
					} elseif ($thisemployeeearnings > 99999) {
						$thisEmpNhif = 1700;
					}

					$nhifquery = 'UPDATE employee_earnings_deductions SET amount = ?, editTime = ?, userId = ? WHERE employeeId = ? AND companyId = ? AND earningDeductionCode = ? AND payPeriod = ? AND active = ?';
					$conn->prepare($nhifquery)->execute(array($thisEmpNhif, $recordtime, $_SESSION['user'], $thisemployee, $_SESSION['companyid'], '481',  $_SESSION['currentactiveperiod'], '1'));

					//NSSF is standard. No recalculation
					$thisemployeeNssfBand1 = 200;
					//Compute Taxable Income
					$thisEmpTaxablePay = $thisemployeeearnings - $thisemployeeNssfBand1;
					$taxpayquery = 'UPDATE employee_earnings_deductions SET amount = ?, editTime = ?, userId = ? WHERE employeeId = ? AND companyId = ? AND earningDeductionCode = ? AND payPeriod = ? AND active = ?';
					$conn->prepare($taxpayquery)->execute(array($thisEmpTaxablePay, $recordtime, $_SESSION['user'], $thisemployee, $_SESSION['companyid'], '400',  $_SESSION['currentactiveperiod'], '1'));

					//Compute PAYE
					$employeepayee = 0;
					$taxpay = $thisEmpTaxablePay;
					if ($taxpay > 0 && $taxpay <= 11180) {
						$employeepayee = $taxpay * 0.1;
					} elseif ($taxpay > 11180 && $taxpay <= 21714) {
						$employeepayee = (11180 * 0.1) + (($taxpay - 11180) * 0.15);
					} elseif ($taxpay > 21714 && $taxpay <= 32248) {
						$employeepayee = (11180 * 0.1) + (10534 * 0.15) + (($taxpay - 11181 - 10533) * 0.2);
					} elseif ($taxpay > 32248 && $taxpay <= 42782) {
						$employeepayee = (11180 * 0.1) + (10534 * 0.15) + (10534 * 0.2) + (($taxpay - 11181 - 10533 - 10534) * 0.25);
					} elseif ($taxpay > 42782) {
						$employeepayee = (11180 * 0.1) + (10534 * 0.15) + (10534 * 0.2) + (10534 * 0.25) + (($taxpay - 11181 - 10533 - 10534 - 10534) * 0.3);
					}

					$taxcharged = $employeepayee;
					$taxchargequery = 'UPDATE employee_earnings_deductions SET amount = ?, editTime = ?, userId = ? WHERE employeeId = ? AND companyId = ? AND earningDeductionCode = ? AND payPeriod = ? AND active = ?';
					$conn->prepare($taxchargequery)->execute(array($taxcharged, $recordtime, $_SESSION['user'], $thisemployee, $_SESSION['companyid'], '399',  $_SESSION['currentactiveperiod'], '1'));


					$finalEmployeePayee = $employeepayee - TAX_RELIEF;

					if ($finalEmployeePayee  <= 0) {
						$finalEmployeePayee = 0;
					}

					$taxpayequery = 'UPDATE employee_earnings_deductions SET amount = ?, editTime = ?, userId = ? WHERE employeeId = ? AND companyId = ? AND earningDeductionCode = ? AND payPeriod = ? AND active = ?';
					$conn->prepare($taxpayequery)->execute(array($finalEmployeePayee, $recordtime, $_SESSION['user'], $thisemployee, $_SESSION['companyid'], '550',  $_SESSION['currentactiveperiod'], '1'));


					//Fetch and populate all deductions and write total
					$query = $conn->prepare('SELECT * FROM employee_earnings_deductions WHERE employeeId = ? AND companyId = ? AND transactionType = ? AND payPeriod = ? AND active = ? ');
					$fin = $query->execute(array($thisemployee, $_SESSION['companyid'], 'Deduction', $_SESSION['currentactiveperiod'], '1'));
					$res = $query->fetchAll(PDO::FETCH_ASSOC);
					$thisemployeeearnings = 0;

					foreach ($res as $row => $link) {
						$thisemployeedeductions = $thisemployeedeductions + $link['amount'];
					}

					$recordtime = date('Y-m-d H:i:s');
					$deductionsquery = 'UPDATE employee_earnings_deductions SET amount = ?, editTime = ?, userId = ? WHERE employeeId = ? AND companyId = ? AND earningDeductionCode = ? AND payPeriod = ? AND active = ?';
					$conn->prepare($deductionsquery)->execute(array($thisemployeedeductions, $recordtime, $_SESSION['user'], $thisemployee, $_SESSION['companyid'], '603',  $_SESSION['currentactiveperiod'], '1'));

					//Calculate Net Salary
					$thisemployeeNet = $thisEmpTaxablePay - $thisemployeedeductions;

					$netquery = 'UPDATE employee_earnings_deductions SET amount = ?, editTime = ?, userId = ? WHERE employeeId = ? AND companyId = ? AND earningDeductionCode = ? AND payPeriod = ? AND active = ?';
					$conn->prepare($netquery)->execute(array($thisemployeeNet, $recordtime, $_SESSION['user'], $thisemployee, $_SESSION['companyid'], '600',  $_SESSION['currentactiveperiod'], '1'));


					$_SESSION['msg'] = 'Employee payroll re-run successful';
					$_SESSION['alertcolor'] = 'success';
					//echo $thisemployeeearnings;
					//exit("Re run");
					header('Location: ' . $source);
				} else {
					//new; insert records
					//Fetch and populate all taxable earnings and write total
					$query = $conn->prepare('SELECT * FROM employee_earnings_deductions WHERE employeeId = ? AND companyId = ? AND transactionType = ? AND payPeriod = ? AND active = ? ');
					$fin = $query->execute(array($thisemployee, $_SESSION['companyid'], 'Earning', $_SESSION['currentactiveperiod'], '1'));
					$res = $query->fetchAll(PDO::FETCH_ASSOC);
					$thisemployeeearnings = 0;

					foreach ($res as $row => $link) {
						$thisemployeeearnings = $thisemployeeearnings + $link['amount'];
					}

					$recordtime = date('Y-m-d H:i:s');
					$grossquery = 'INSERT INTO employee_earnings_deductions (employeeId, companyId, transactionType, earningDeductionCode, amount, payPeriod, standardRecurrent, active, editTime, userId) VALUES (?,?,?,?,?,?,?,?,?,?)';
					$conn->prepare($grossquery)->execute(array($thisemployee, $_SESSION['companyid'], 'Calc', '601', $thisemployeeearnings, $_SESSION['currentactiveperiod'], '0', '1', $recordtime, $_SESSION['user']));

					//Get initial statutories - NHIF, NSSF, Tax relief
					//NHIF Bands
					if ($thisemployeeearnings > 0 && $thisemployeeearnings < 5999) {
						$thisEmpNhif = 150;
					} elseif ($thisemployeeearnings > 5999 && $thisemployeeearnings <= 7999) {
						$thisEmpNhif = 300;
					} elseif ($thisemployeeearnings > 7999 && $thisemployeeearnings <= 11999) {
						$thisEmpNhif = 400;
					} elseif ($thisemployeeearnings > 11999 && $thisemployeeearnings <= 14999) {
						$thisEmpNhif = 500;
					} elseif ($thisemployeeearnings > 14999 && $thisemployeeearnings <= 19999) {
						$thisEmpNhif = 600;
					} elseif ($thisemployeeearnings > 19999 && $thisemployeeearnings <= 24999) {
						$thisEmpNhif = 750;
					} elseif ($thisemployeeearnings > 24999 && $thisemployeeearnings <= 29999) {
						$thisEmpNhif = 850;
					} elseif ($thisemployeeearnings > 29999 && $thisemployeeearnings <= 34999) {
						$thisEmpNhif = 900;
					} elseif ($thisemployeeearnings > 34999 && $thisemployeeearnings <= 39999) {
						$thisEmpNhif = 950;
					} elseif ($thisemployeeearnings > 39999 && $thisemployeeearnings <= 44999) {
						$thisEmpNhif = 1000;
					} elseif ($thisemployeeearnings > 44999 && $thisemployeeearnings <= 49999) {
						$thisEmpNhif = 1100;
					} elseif ($thisemployeeearnings > 49999 && $thisemployeeearnings <= 59999) {
						$thisEmpNhif = 1200;
					} elseif ($thisemployeeearnings > 59999 && $thisemployeeearnings <= 69999) {
						$thisEmpNhif = 1300;
					} elseif ($thisemployeeearnings > 69999 && $thisemployeeearnings <= 79999) {
						$thisEmpNhif = 1400;
					} elseif ($thisemployeeearnings > 79999 && $thisemployeeearnings <= 89999) {
						$thisEmpNhif = 1500;
					} elseif ($thisemployeeearnings > 89999 && $thisemployeeearnings <= 99999) {
						$thisEmpNhif = 1600;
					} elseif ($thisemployeeearnings > 99999) {
						$thisEmpNhif = 1700;
					}

					$nhifquery = 'INSERT INTO employee_earnings_deductions (employeeId, companyId, transactionType, earningDeductionCode, amount, payPeriod, standardRecurrent, active, editTime, userId) VALUES (?,?,?,?,?,?,?,?,?,?)';
					$conn->prepare($nhifquery)->execute(array($thisemployee, $_SESSION['companyid'], 'Deduction', '481', $thisEmpNhif, $_SESSION['currentactiveperiod'], '0', '1', $recordtime, $_SESSION['user']));

					//NSSF Band Calculation
					$thisemployeeNssfBand1 = 200;

					/*$thisemployeeNssfBand1 = $thisemployeeearnings * 0.06;
							if ($thisemployeeNssfBand1 > 360) {
								$thisemployeeNssfBand1 = 360;
							}*/
					$nssfquery = 'INSERT INTO employee_earnings_deductions (employeeId, companyId, transactionType, earningDeductionCode, amount, payPeriod, standardRecurrent, active, editTime, userId) VALUES (?,?,?,?,?,?,?,?,?,?)';
					$conn->prepare($nssfquery)->execute(array($thisemployee, $_SESSION['companyid'], 'Deduction', '482', $thisemployeeNssfBand1, $_SESSION['currentactiveperiod'], '0', '1', $recordtime, $_SESSION['user']));

					//Compute Taxable Income
					$thisEmpTaxablePay = $thisemployeeearnings - $thisemployeeNssfBand1;
					$taxpayquery = 'INSERT INTO employee_earnings_deductions (employeeId, companyId, transactionType, earningDeductionCode, amount, payPeriod, standardRecurrent, active, editTime, userId) VALUES (?,?,?,?,?,?,?,?,?,?)';
					$conn->prepare($taxpayquery)->execute(array($thisemployee, $_SESSION['companyid'], 'Calc', '400', $thisEmpTaxablePay, $_SESSION['currentactiveperiod'], '0', '1', $recordtime, $_SESSION['user']));


					//Compute PAYE
					$employeepayee = 0;
					$taxpay = $thisEmpTaxablePay;
					if ($taxpay > 0 && $taxpay <= 11180) {
						$employeepayee = $taxpay * 0.1;
					} elseif ($taxpay > 11180 && $taxpay <= 21714) {
						$employeepayee = (11180 * 0.1) + (($taxpay - 11180) * 0.15);
					} elseif ($taxpay > 21714 && $taxpay <= 32248) {
						$employeepayee = (11180 * 0.1) + (10534 * 0.15) + (($taxpay - 11181 - 10533) * 0.2);
					} elseif ($taxpay > 32248 && $taxpay <= 42782) {
						$employeepayee = (11180 * 0.1) + (10534 * 0.15) + (10534 * 0.2) + (($taxpay - 11181 - 10533 - 10534) * 0.25);
					} elseif ($taxpay > 42782) {
						$employeepayee = (11180 * 0.1) + (10534 * 0.15) + (10534 * 0.2) + (10534 * 0.25) + (($taxpay - 11181 - 10533 - 10534 - 10534) * 0.3);
					}

					$taxcharged = $employeepayee;
					$taxchargequery = 'INSERT INTO employee_earnings_deductions (employeeId, companyId, transactionType, earningDeductionCode, amount, payPeriod, standardRecurrent, active, editTime, userId) VALUES (?,?,?,?,?,?,?,?,?,?)';
					$conn->prepare($taxchargequery)->execute(array($thisemployee, $_SESSION['companyid'], 'Calc', '399', $taxcharged, $_SESSION['currentactiveperiod'], '0', '1', $recordtime, $_SESSION['user']));

					$finalEmployeePayee = $employeepayee - TAX_RELIEF;

					if ($finalEmployeePayee  <= 0) {
						$finalEmployeePayee = 0;
					}

					$taxpayequery = 'INSERT INTO employee_earnings_deductions (employeeId, companyId, transactionType, earningDeductionCode, amount, payPeriod, standardRecurrent, active, editTime, userId) VALUES (?,?,?,?,?,?,?,?,?,?)';
					$conn->prepare($taxpayequery)->execute(array($thisemployee, $_SESSION['companyid'], 'Deduction', '550', $finalEmployeePayee, $_SESSION['currentactiveperiod'], '0', '1', $recordtime, $_SESSION['user']));


					//Fetch and populate all deductions and write total
					$query = $conn->prepare('SELECT * FROM employee_earnings_deductions WHERE employeeId = ? AND companyId = ? AND transactionType = ? AND payPeriod = ? AND active = ? ');
					$fin = $query->execute(array($thisemployee, $_SESSION['companyid'], 'Deduction', $_SESSION['currentactiveperiod'], '1'));
					$res = $query->fetchAll(PDO::FETCH_ASSOC);
					$thisemployeedeductions = 0;

					foreach ($res as $row => $link) {
						$thisemployeedeductions = $thisemployeedeductions + $link['amount'];
					}

					$recordtime = date('Y-m-d H:i:s');
					$deductionsquery = 'INSERT INTO employee_earnings_deductions (employeeId, companyId, transactionType, earningDeductionCode, amount, payPeriod, standardRecurrent, active, editTime, userId) VALUES (?,?,?,?,?,?,?,?,?,?)';
					$conn->prepare($deductionsquery)->execute(array($thisemployee, $_SESSION['companyid'], 'Calc', '603', $thisemployeedeductions, $_SESSION['currentactiveperiod'], '0', '1', $recordtime, $_SESSION['user']));

					//Calculate Net Salary
					$thisemployeeNet = $thisEmpTaxablePay - $thisemployeedeductions;

					$netquery = 'INSERT INTO employee_earnings_deductions (employeeId, companyId, transactionType, earningDeductionCode, amount, payPeriod, standardRecurrent, active, editTime, userId) VALUES (?,?,?,?,?,?,?,?,?,?)';
					$conn->prepare($netquery)->execute(array($thisemployee, $_SESSION['companyid'], 'Calc', '600', $thisemployeeNet, $_SESSION['currentactiveperiod'], '0', '1', $recordtime, $_SESSION['user']));

					$_SESSION['msg'] = 'Employee payroll run successful';
					$_SESSION['alertcolor'] = 'success';
					header('Location: ' . $source);
				}
			} catch (PDOException $e) {
				echo $e->getMessage();
			}
		}



		break;


	case 'runGlobalPayroll':
		ini_set('max_execution_time', '0');
		//$connect = mysqli_connect("localhost", "root", "oluwaseyi", "salary");
		//include_once('functions.php'); 
		//session_start();


		$period = $_SESSION['currentactiveperiod'];



		$query = $conn->prepare('SELECT * FROM payperiods WHERE payrollRun = ? and periodId = ?');
		$fin = $query->execute(array(0, $_SESSION['currentactiveperiod']));
		$existtrans = $query->fetch();

		if ($existtrans) {

			try { //echo $period ;
				global $conn;
				$query = $conn->prepare('SELECT * FROM employee WHERE STATUSCD = ?');
				$res = $query->execute(array('A'));
				$out = $query->fetchAll(PDO::FETCH_ASSOC);
				//get employee info                                          
				while ($row = array_shift($out)) {
					$queryMaster = $conn->prepare('INSERT INTO master_staff (staff_id,NAME,DEPTCD,BCODE,ACCTNO,GRADE,STEP,period,PFACODE,PFAACCTNO) VALUES (?,?,?,?,?,?,?,?,?,?)');
					$master = $queryMaster->execute(array($row['staff_id'], $row['NAME'], $row['DEPTCD'], $row['BCODE'], $row['ACCTNO'], $row['GRADE'], $row['STEP'], $period, $row['PFACODE'], $row['PFAACCTNO']));

					echo 'staff id' . ' ' . $row['staff_id'] . '<br>';
					$query_allow = $conn->prepare('SELECT allow_deduc.temp_id, allow_deduc.staff_id, allow_deduc.allow_id, allow_deduc.`value`, allow_deduc.transcode, allow_deduc.counter,  allow_deduc.running_counter, allow_deduc.inserted_by, allow_deduc.date_insert,tbl_earning_deduction.edDesc FROM allow_deduc
 																				INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = allow_deduc.allow_id WHERE staff_id = ? and transcode = ? order by allow_deduc.allow_id asc');
					$res_allow = $query_allow->execute(array($row['staff_id'], '1'));
					$out_allow = $query_allow->fetchAll(PDO::FETCH_ASSOC);
					while ($row_allow = array_shift($out_allow)) {


						if ($row_allow['allow_id'] == '21') {

							$query_value = $conn->prepare('SELECT allowancetable.`value` FROM allowancetable WHERE allowancetable.grade = ? AND allowancetable.step = ? AND allowcode = ? AND category = ?');
							$rerun_value = $query_value->execute(array($row['GRADE'], $row['STEP'], $row_allow['allow_id'], $row['CALLTYPE']));
						} else {

							$query_value = $conn->prepare('SELECT allowancetable.`value` FROM allowancetable WHERE allowancetable.grade = ? AND allowancetable.step = ? AND allowcode = ?');
							$rerun_value = $query_value->execute(array($row['GRADE'], $row['STEP'], $row_allow['allow_id']));
						}


						if ($row_value = $query_value->fetch()) {
							$output = $row_value['value'];
						} else {

							$output = $row_allow['value'];
						}


						echo $row_allow['allow_id'] . ' ' . $row_allow['edDesc'] . ' ' . number_format($output) . '<br>';
						try {
							$recordtime = date('Y-m-d H:i:s');
							$query = 'INSERT INTO tbl_master (staff_id, allow_id, allow, type, period,editTime,userID) VALUES (?,?,?,?,?,?,?)';
							$conn->prepare($query)->execute(array($row['staff_id'], $row_allow['allow_id'], $output, '1',  $period, $recordtime, $_SESSION['SESS_MEMBER_ID']));
						} catch (PDOException $e) {
							echo $e->getMessage();
						}
						if (intval($row_allow['counter']) > 0) {
							echo 'allowance deduction counter check';
							$running_counter = intval($row_allow['running_counter']);
							$running_counter = $running_counter + 1;
							if (($running_counter) == intval($row_allow['counter'])) {

								$query = 'INSERT INTO completedLoan (staff_id,allow_id,period,value,type)VALUES (?,?,?,?,?)';
								$conn->prepare($query)->execute(array($row['staff_id'], $row_allow['allow_id'], $period, $output, '1'));

								//delete allow once cycle is complete
								$sqlDelete = "DELETE FROM allow_deduc WHERE temp_id = '" . $row_allow['temp_id'] . "'";
								$conn->exec($sqlDelete);
							} else {
								$sqlUpdate = "update allow_deduc set running_counter = '" . $running_counter . "' WHERE temp_id = '" . $row_allow['temp_id'] . "'";
								$conn->exec($sqlUpdate);
							}
						}
					}


					// deduction process


					$total_rows = '';

					$query_deduct = $conn->prepare('SELECT allow_deduc.temp_id, allow_deduc.staff_id, allow_deduc.allow_id, allow_deduc.`value`, allow_deduc.transcode, allow_deduc.counter,  allow_deduc.running_counter, allow_deduc.inserted_by, allow_deduc.date_insert,tbl_earning_deduction.edDesc,tbl_earning_deduction.edType FROM allow_deduc
																			 INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = allow_deduc.allow_id WHERE staff_id = ? and transcode = ? order by allow_deduc.allow_id asc');
					$res_deduct = $query_deduct->execute(array($row['staff_id'], '2'));
					$out_deduct = $query_deduct->fetchAll(PDO::FETCH_ASSOC);
					while ($row_deduct = array_shift($out_deduct)) {
						$output = 0;
						//Process Normal deduction
						if (intval($row_deduct['edType']) == '2') {

							if (intval($row_deduct['allow_id']) == 50) { //process pension
								$sql_consolidated = "SELECT allowancetable.`value` FROM allowancetable WHERE allowancetable.allowcode = 1 and grade = '" . $row['GRADE'] . "' and step = '" . $row['STEP'] . "'";
								$result_consolidated = mysqli_query($salary, $sql_consolidated);
								$row_consolidated = mysqli_fetch_assoc($result_consolidated);
								$total_rowsConsolidated = mysqli_num_rows($result_consolidated);

								$sql_pensionRate = "SELECT (pension.PENSON/100) as rate FROM pension WHERE grade = '" . $row['GRADE'] . "' and step = '" . $row['STEP'] . "'";
								$result_pensionRate = mysqli_query($salary, $sql_pensionRate);
								$row_pensionRate = mysqli_fetch_assoc($result_pensionRate);
								$total_pensionRate = mysqli_num_rows($result_pensionRate);

								$output = ceil($row_consolidated['value'] * $row_pensionRate['rate']);
								//echo $output;	

							} else {
								$output = $row_deduct['value'];
							}
							//Save into db
							//echo $row_allow['allow_id'].' '.$row_allow['edDesc'].' '.number_format($output).'<br>';
							try {
								$recordtime = date('Y-m-d H:i:s');
								$query = 'INSERT INTO tbl_master (staff_id, allow_id, deduc, type, period,editTime,userID) VALUES (?,?,?,?,?,?,?)';
								$conn->prepare($query)->execute(array($row['staff_id'], $row_deduct['allow_id'], $output, '2',  $period, $recordtime, $_SESSION['SESS_MEMBER_ID']));
								//delete temp deduction
								if (intval($row_deduct['counter']) > 0) {
									echo 'Normal deduction counter check';
									$running_counter = intval($row_deduct['running_counter']);
									$running_counter = intval($row_deduct['running_counter']) + 1;
									if (($running_counter) == intval($row_deduct['counter'])) {
										echo 'normal deduction counter check';
										$query = 'INSERT INTO completedLoan (staff_id,allow_id,period,value,type)VALUES (?,?,?,?,?)';
										$conn->prepare($query)->execute(array($row['staff_id'], $row_deduct['allow_id'], $period, $output, '2'));
										//delete allow once cycle is complete
										$sqlDelete = "DELETE FROM allow_deduc WHERE temp_id = '" . $row_deduct['temp_id'] . "'";
										$conn->exec($sqlDelete);
									} else {
										$sqlUpdate = "update allow_deduc set running_counter = '" . $running_counter . "' WHERE temp_id = '" . $row_deduct['temp_id'] . "'";
										$conn->exec($sqlUpdate);
									}
								}
							} catch (PDOException $e) {
								echo $e->getMessage();
							}
						} else if (intval($row_deduct['edType']) == '3') {
							//Process Union deduction
							$sql_numberOfRows = "SELECT deductiontable.ded_id, deductiontable.allowcode, deductiontable.grade, deductiontable.step, deductiontable.`value`, deductiontable.category, deductiontable.ratetype, deductiontable.percentage FROM deductiontable WHERE allowcode = '" . $row_deduct['allow_id'] . "'";
							$result_numberOfRows = mysqli_query($salary, $sql_numberOfRows);
							$row_numberOfRows = mysqli_fetch_assoc($result_numberOfRows);
							$total_rows = mysqli_num_rows($result_numberOfRows);
							if ($total_rows == 1) {
								if ($row_numberOfRows['ratetype'] == 1) {
									$output = $row_numberOfRows['value'];
								} else {
									$sql_consolidated = "SELECT allowancetable.allow_id, allowancetable.allowcode, allowancetable.grade, allowancetable.step, allowancetable.`value`, allowancetable.category, allowancetable.ratetype, allowancetable.percentage FROM allowancetable WHERE allowancetable.allowcode = 1 and grade = '" . $row['GRADE'] . "' and step = '" . $row['STEP'] . "'";
									$result_consolidated = mysqli_query($salary, $sql_consolidated);
									$row_consolidated = mysqli_fetch_assoc($result_consolidated);
									$total_rowsConsolidated = mysqli_num_rows($result_consolidated);
									$output = ($row_numberOfRows['percentage'] * $row_consolidated['value']) / 100;
								}
								// if deduction is found in the table
							} else if ($total_rows > 1) {
								$sql_mulitple = "SELECT deductiontable.ded_id, deductiontable.allowcode, deductiontable.grade, deductiontable.step, deductiontable.`value`, deductiontable.category, deductiontable.ratetype, deductiontable.percentage FROM deductiontable WHERE allowcode = '" . $row_deduct['allow_id'] . "' and grade = '" . $row['GRADE'] . "'";
								$result_mulitple = mysqli_query($salary, $sql_mulitple);
								$row_mulitple = mysqli_fetch_assoc($result_mulitple);
								$total_mulitple = mysqli_num_rows($result_mulitple);
								if ($total_mulitple > 0) {
									if ($row_mulitple['ratetype'] == 1) {
										$output = $row_mulitple['value'];
										//echo $sql_mulitple ; 
									} else {
										$sql_consolidated = "SELECT allowancetable.allow_id, allowancetable.allowcode, allowancetable.grade, allowancetable.step, allowancetable.`value`, allowancetable.category, allowancetable.ratetype, allowancetable.percentage FROM allowancetable WHERE allowancetable.allowcode = 1 and grade = '" . $row['GRADE'] . "' and step = '" . $row['STEP'] . "'";
										$result_consolidated = mysqli_query($salary, $sql_consolidated);
										$row_consolidated = mysqli_fetch_assoc($result_consolidated);
										$total_rowsConsolidated = mysqli_num_rows($result_consolidated);
										$output = ceil(($row_mulitple['percentage'] * $row_consolidated['value']) / 100);
									}
								} else {
									$output = $row_deduct['value'];
								}
							} else {
								$output = $row_deduct['value'];
							}
							echo $row_allow['allow_id'] . ' ' . $row_allow['edDesc'] . ' ' . number_format($output) . '<br>';
							try {
								$recordtime = date('Y-m-d H:i:s');
								$query = 'INSERT INTO tbl_master (staff_id, allow_id, deduc, type, period,editTime,userID) VALUES (?,?,?,?,?,?,?)';
								$conn->prepare($query)->execute(array($row['staff_id'], $row_deduct['allow_id'], $output, '2',  $period, $recordtime, $_SESSION['SESS_MEMBER_ID']));
								//process temp allow id

								if (intval($row_deduct['counter']) > 0) {
									echo 'union deduction counter check';
									$running_counter = intval($row_deduct['running_counter']);
									$running_counter = intval($row_deduct['running_counter']) + 1;
									if (($running_counter) == intval($row_deduct['counter'])) {
										//delete allow once cycle is complete
										$query = 'INSERT INTO completedLoan (staff_id,allow_id,period,value,type)VALUES (?,?,?,?,?)';
										$conn->prepare($query)->execute(array($row['staff_id'], $row_deduct['allow_id'], $period, $output, '2'));

										$sqlDelete = "DELETE FROM allow_deduc WHERE temp_id = '" . $row_deduct['temp_id'] . "'";
										$conn->exec($sqlDelete);
									} else {
										$sqlUpdate = "update allow_deduc set running_counter = '" . $running_counter . "' WHERE temp_id = '" . $row_deduct['temp_id'] . "'";
										$conn->exec($sqlUpdate);
									}
								}
							} catch (PDOException $e) {
								echo $e->getMessage();
							}
							//process loan deduction
						} else if (intval($row_deduct['edType']) == '4') {
							$sql_loancheck = "SELECT tbl_earning_deduction_type.edType FROM tbl_earning_deduction_type INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.edType = tbl_earning_deduction_type.edType WHERE tbl_earning_deduction.ed_id = '" . $row_deduct['allow_id'] . "' and tbl_earning_deduction_type.edType = 4";
							$result_loancheck = mysqli_query($salary, $sql_loancheck);
							$row_loan = mysqli_fetch_assoc($result_loancheck);
							$total_loancheck = mysqli_num_rows($result_loancheck);
							//echo 'sql check ='. $sql_loancheck. '<br>';
							//echo 'loan check ='. $total_loancheck. '<br>';
							if ($total_loancheck > 0) {

								$sql_loan = "SELECT tbl_debt.loan_id, tbl_debt.staff_id,tbl_debt.allow_id, SUM(ifnull(tbl_debt.principal,0))+SUM(ifnull(tbl_debt.interest,0)) as loan FROM tbl_debt WHERE staff_id = '" . $row['staff_id'] . "' AND allow_id = '" . $row_deduct['allow_id'] . "'";
								$result_loan = mysqli_query($salary, $sql_loan);
								$row_loan = mysqli_fetch_assoc($result_loan);
								$total_loan = mysqli_num_rows($result_loan);

								$sql_repayment = "SELECT tbl_repayment.staff_id, tbl_repayment.allow_id, SUM(ifnull(tbl_repayment.value,0)) as repayment FROM tbl_repayment WHERE staff_id = '" . $row['staff_id'] . "' and allow_id = '" . $row_deduct['allow_id'] . "'";
								$result_repayment = mysqli_query($salary, $sql_repayment);
								$row_repayment = mysqli_fetch_assoc($result_repayment);
								$total_repayment = mysqli_num_rows($result_repayment);

								$balance = $row_loan['loan'] - $row_repayment['repayment'];
								//print number_format($balance);
								//echo $sql_repayment ;
								if (floatval($balance) > floatval($row_deduct['value'])) {
									$output = floatval($row_deduct['value']);
									//add payment
									try {
										$recordtime = date('Y-m-d H:i:s');
										$query_repayment = 'INSERT INTO tbl_repayment (staff_id, allow_id, value,  period,userID,editTime) VALUES (?,?,?,?,?,?)';
										$conn->prepare($query_repayment)->execute(array($row['staff_id'], $row_deduct['allow_id'], $output, $period, $period, $recordtime));
									} catch (PDOException $e) {
										echo $e->getMessage();
									}
								} else if (floatval($balance) <= floatval($row_deduct['value'])) {
									$output = floatval($balance);
									try {
										echo 'loan deduction counter check';
										$recordtime = date('Y-m-d H:i:s');

										$query = 'INSERT INTO completedLoan (staff_id,allow_id,period,value,type)VALUES (?,?,?,?,?)';
										$conn->prepare($query)->execute(array($row['staff_id'], $row_deduct['allow_id'], $period, $output, '2'));

										$query_repayment = 'INSERT INTO tbl_repayment (staff_id, allow_id, value,  period,userID,editTime) VALUES (?,?,?,?,?,?)';
										$conn->prepare($query_repayment)->execute(array($row['staff_id'], $row_deduct['allow_id'], $output, $period, $period, $recordtime));
										//delete loan id
										$query = 'DELETE FROM allow_deduc where allow_id = ? and staff_id = ?';
										$conn->prepare($query)->execute(array($row_deduct['allow_id'], $row['staff_id']));
									} catch (PDOException $e) {
										echo $e->getMessage();
									}
								}
							}
							echo $row_deduct['allow_id'] . ' ' . $row_deduct['edDesc'] . ' ' . number_format($output) . '<br>';
							try {




								$recordtime = date('Y-m-d H:i:s');
								$query = 'INSERT INTO tbl_master (staff_id, allow_id, deduc, type, period,editTime,userID) VALUES (?,?,?,?,?,?,?)';
								$conn->prepare($query)->execute(array($row['staff_id'], $row_deduct['allow_id'], $output, '2',  $period, $recordtime, $_SESSION['SESS_MEMBER_ID']));
							} catch (PDOException $e) {
								echo $e->getMessage();
							}
						}
					}
				}
			} catch (PDOException $e) {
				echo $e->getMessage();
			}

			//set openview status
			$statuschange = $conn->prepare('UPDATE payperiods SET payrollRun = ? WHERE periodId = ?');
			$perres = $statuschange->execute(array('1', $period));

			//exit ($_SESSION['companyid'] . 'Entire Employee Run');
			echo 0;
		}
		break;


	case 'addNewLeave':
		//check for existing same employee number

		$empnumber = filter_var($_POST['empnumber'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$leavetype = filter_var($_POST['leavetype'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$startleave = date('Y-m-d', strtotime(filter_var($_POST['startleave'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS)));
		$day1 = strtotime(filter_var($_POST['startleave'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS));
		$endleave = date('Y-m-d', strtotime(filter_var($_POST['endleave'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS)));
		$day2 = strtotime(filter_var($_POST['endleave'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS));

		$days_diff = $day2 - $day1;
		$numofdays = date('d', $days_diff);

		$currdate = date('Y-m-d');
		//validate for empty mandatory fields

		try {
			//check for same leave request for same staffer
			$leavequery = $conn->prepare('SELECT * FROM hr_leave_requests WHERE employeeNumber = ? AND leaveType = ? AND status = ? OR status = ? AND active = ?');
			$res = $leavequery->execute(array($empnumber, $leavetype, '1', '2', '1'));

			if ($row = $leavequery->fetch()) {
				$_SESSION['msg'] = $msg = "Active / Pending similar leave type for this employee. Please review all approved or pending leave requests.";
				$_SESSION['alertcolor'] = 'danger';
				header('Location: ' . $source);
			} else {
				$query = 'INSERT INTO hr_leave_requests (employeeNumber, leaveType, fromDate, toDate, applicationDate, numberOfDays, status) VALUES (?,?,?,?,?,?,?)';

				$conn->prepare($query)->execute(array($empnumber, $leavetype, $startleave, $endleave, $currdate, $numofdays, '2'));

				$_SESSION['msg'] = $msg = "New Leave Successfully added.";
				$_SESSION['alertcolor'] = 'success';
				header('Location: ' . $source);
			}
		} catch (PDOException $e) {
			echo $e->getMessage();
		}

		break;


	case 'manageLeave':

		$empalternumber = filter_var($_POST['empalternumber'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$empalterid = filter_var($_POST['empalterid'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$leaveaction = filter_var($_POST['leaveaction'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		//exit($empalternumber . ",". $empalterid. "," .$leaveaction);

		try {

			$query = ('UPDATE hr_leave_requests SET status = ? WHERE id = ? AND employeeNumber = ?');
			$conn->prepare($query)->execute(array($leaveaction, $empalterid, $empalternumber));

			$_SESSION['msg'] = $msg = "Leave status successfully amended";
			$_SESSION['alertcolor'] = 'success';
			header('Location: ' . $source);
		} catch (PDOException $e) {
			echo $e->getMessage();
		}

		break;


	case 'deactivateEmployee':
		$empalterid = filter_var($_POST['empalterid'], FILTER_VALIDATE_INT);
		$empalternumber = filter_var($_POST['empalternumber'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		//$exitdate = date('Y-m-d', strtotime(filter_var($_POST['exitdate'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS)));
		$deactivate = filter_var($_POST['deactivate'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$editDate = date('Y-m-d H:i:s');

		//exit($empalternumber . ", " . $empalterid . ", " . $exitdate . ", " . $exitreason);
		$query = 'UPDATE employee SET STATUSCD = ? WHERE staff_id = ?';
		$conn->prepare($query)->execute(array($deactivate, $empalterid));

		//	$deactivatequery = 'INSERT INTO hr_exited_employees (employeeId, exitDate, exitReason, editTime, userEditorId) VALUES (?,?,?,?,?)';
		//	$conn->prepare($deactivatequery)->execute (array($empalternumber, $exitdate, $exitreason, $editDate, $_SESSION['user']));

		$_SESSION['msg'] = $msg = "Employee successfully deactivated.";
		$_SESSION['alertcolor'] = 'success';
		header('Location: ' . $source);
		break;


	case 'suspendEmployee':
		$empalterid = filter_var($_POST['empalterid'], FILTER_VALIDATE_INT);
		$empalternumber = filter_var($_POST['empalternumber'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$startsuspension = date('Y-m-d', strtotime(filter_var($_POST['startsuspension'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS)));
		$endsuspension = date('Y-m-d', strtotime(filter_var($_POST['endsuspension'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS)));
		$suspendreason = filter_var($_POST['suspendreason'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$editDate = date('Y-m-d H:i:s');

		try {

			$susquery = $conn->prepare('SELECT * FROM employees WHERE empNumber = ? AND companyId = ? AND active = ? AND suspended = ?');
			$fin = $susquery->execute(array($empalternumber, $_SESSION['companyid'], '1', '1'));

			if ($row = $susquery->fetch()) {
				$_SESSION['msg'] = "Selected employee currently on suspension.";
				$_SESSION['alertcolor'] = "danger";
				header('Location: ' . $source);
			} else {
				//exit($empalternumber . ", " . $empalterid . ", " . $exitdate . ", " . $exitreason);
				$query = 'UPDATE employees SET suspended = ? WHERE empNumber = ? AND companyId = ? AND active = ? AND suspended = ?';
				$conn->prepare($query)->execute(array('1', $empalternumber, $_SESSION['companyid'], '1', '0'));

				$deactivatequery = 'INSERT INTO employee_suspensions (employeeId, suspendStartDate, suspendEndDate, suspenReason, editTime, userEditorId) VALUES (?,?,?,?,?,?)';
				$conn->prepare($deactivatequery)->execute(array($empalternumber, $startsuspension, $endsuspension, $suspendreason, $editDate, $_SESSION['user']));

				$_SESSION['msg'] = $msg = "Employee successfully suspended.";
				$_SESSION['alertcolor'] = 'success';
				header('Location: ' . $source);
			}
		} catch (PDOException $e) {
			echo $e->getMessage();
		}

		break;


	case 'editemployeeearning':
		//exit('Edit Employee Earning');
		$empedit = filter_var($_POST['empeditnum'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$edited = filter_var($_POST['edited'], FILTER_VALIDATE_INT);
		$editname = filter_var($_POST['editname'], FILTER_VALIDATE_INT);
		$editvalue = filter_var($_POST['editvalue'], FILTER_VALIDATE_INT);
		$grossquery = 'UPDATE employee_earnings_deductions SET amount = ? WHERE employeeId = ? AND companyId = ? AND earningDeductionCode = ? AND payPeriod = ? AND active = ?';
		$conn->prepare($grossquery)->execute(array($editvalue, $empedit, $_SESSION['companyid'], $edited,  $_SESSION['currentactiveperiod'], '1'));

		$_SESSION['msg'] = 'Successfully Edited Earning / Deduction';
		$_SESSION['alertcolor'] = 'success';
		header('Location: ' . $source);
		break;


	case 'deactivateEd':
		$empeditnum = filter_var($_POST['empeditnum'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$edited = filter_var($_POST['edited'], FILTER_VALIDATE_INT);
		//exit($empeditnum . " " . $edited . " " . $_SESSION['currentactiveperiod');
		try {

			$query = $conn->prepare('SELECT * FROM allow_deduc WHERE temp_id = ?');
			$res = $query->execute(array($empeditnum));
			$existtrans = $query->fetch();

			if ($existtrans) {

				$query = 'DELETE FROM allow_deduc where temp_id = ?';
			}
			$conn->prepare($query)->execute(array($empeditnum));

			$_SESSION['msg'] = $msg = "E/D successfully deactivated.";
			$_SESSION['alertcolor'] = 'success';
			header('Location: ' . $source);
		} catch (PDOException $e) {
			echo $e->getMessage();
		}

		break;


	case 'batchprocess':
		exit('Batch Process');
		break;


	case 'resetpass':
		//exit('reset');

		$title = "Password Reset";
		$resetemail = filter_var((filter_var($_POST['email'], FILTER_SANITIZE_EMAIL)), FILTER_VALIDATE_EMAIL);

		//check if account exists with emailaddress
		$query = $conn->prepare('SELECT emailAddress FROM users WHERE emailAddress = ? AND active = ?');
		$fin = $query->execute(array($resetemail, '1'));

		if ($row = $query->fetch()) {

			//Generate update token
			$reset_token = bin2hex(openssl_random_pseudo_bytes(32));

			//write token to token table and assign validity state, creation timestamp
			$tokenrecordtime = date('Y-m-d H:i:s');

			//check for any previous tokens and invalidate
			$tokquery = $conn->prepare('SELECT * FROM reset_token WHERE userEmail = ? AND valid = ? AND type = ?');
			$fin = $tokquery->execute(array($resetemail, '1', '1'));

			if ($row = $tokquery->fetch()) {
				$upquery = 'UPDATE reset_token SET valid = ? WHERE userEmail = ? AND valid = ?';
				$conn->prepare($upquery)->execute(array('0', $resetemail, '1'));
			}

			$tokenquery = 'INSERT INTO reset_token (userEmail, token, creationTime, valid, type) VALUES (?,?,?,?,?)';
			$conn->prepare($tokenquery)->execute(array($resetemail, $reset_token, $tokenrecordtime, '1', '1'));

			//exit($resetemail . " " . $reset_token);

			$sendmessage = "You've recently asked to reset the password for this Redsphere Payroll account: " . $resetemail . "<br /><br />To update your password, click the link below:<br /><br /> " . $sysurl . 'password_reset.php?token=' . $reset_token;
			//generate reset cdde and append to email submitted

			require 'phpmailer/PHPMailerAutoload.php';

			$mail = new PHPMailer;

			$mail->SMTPDebug = 3;                               // Enable verbose debug output

			$mail->isSMTP();                                      // Set mailer to use SMTP
			$mail->Host = 'smtp.zoho.com';  // Specify main and backup SMTP servers
			$mail->SMTPAuth = true;                               // Enable SMTP authentication
			$mail->Username = 'noreply@redsphere.co.ke';                 // SMTP username
			$mail->Password = 'redsphere_2017***';                           // SMTP password
			$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
			$mail->Port = 587;                                    // TCP port to connect to

			$mail->setFrom('noreply@redsphere.co.ke', 'Redsphere Payroll');
			$mail->addAddress($resetemail, 'Redsphere Payroll');     // Add a recipient
			//$mail->addAddress('ellen@example.com');               // Name is optional
			$mail->addReplyTo('info@example.com', 'Information');
			$mail->addCC('fgesora@gmail.com');
			//$mail->addBCC('bcc@example.com');

			//$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
			//$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
			$mail->isHTML(true);                                  // Set email format to HTML

			$mail->Subject = $title;
			$mail->Body    = $sendmessage;
			$mail->AltBody = $sendmessage;

			if (!$mail->send()) {
				//exit($mail->ErrorInfo);
				echo 'Mailer Error: ' . $mail->ErrorInfo;
				$_SESSION['msg'] = "Failed. Error sending email.";
				$_SESSION['alertcolor'] = "danger";
				header("Location: " . $source);
			} else {
				$status = "Success";
				$_SESSION['msg'] = "If there is an account associated with this email address, an email has been sent to reset your password.";
				$_SESSION['alertcolor'] = "success";
				header("Location: " . $source);
			}
		} else {

			$_SESSION['msg'] = "If there is an account associated with this email address, an email has been sent to reset your password.";
			$_SESSION['alertcolor'] = "success";
			header("Location: " . $source);
		}

		break;



	case 'deactivateuser':
		$thisuser = filter_var($_POST['thisuser'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$useremail = filter_var($_POST['useremail'],  FILTER_SANITIZE_FULL_SPECIAL_CHARS);

		try {
			$query = 'UPDATE username SET deleted = ? WHERE staff_id = ?';
			$conn->prepare($query)->execute(array('1', $thisuser));

			$_SESSION['msg'] = $msg = "User successfully deactivated.";
			$_SESSION['alertcolor'] = 'success';
			header('Location: ' . $source);
		} catch (PDOException $e) {
			echo $e->getMessage();
		}

		break;



	case 'logout':
		$_SESSION['logged_in'] = '0';
		unset($_SESSION['user']);
		unset($_SESSION['email']);
		unset($_SESSION['first_name']);
		unset($_SESSION['last_name']);
		unset($_SESSION['companyid']);
		unset($_SESSION['emptrack']);
		unset($_SESSION['currentactiveperiod']);
		unset($_SESSION['activeperiodDescription']);
		unset($_SESSION['msg']);
		unset($_SESSION['alertcolor']);
		unset($_SESSION['empDataTrack']);
		unset($_SESSION['emptNumTack']);

		if (isset($_SESSION['leavestate'])) {
			unset($_SESSION['leavestate']);
		}

		if (isset($_SESSION['periodstatuschange'])) {
			unset($_SESSION['periodstatuschange']);
		}

		//reset global openview status
		$statuschange = $conn->prepare('UPDATE payperiods SET openview = ? ');
		$perres = $statuschange->execute(array('0'));

		$_SESSION['msg'] = $msg = "Successfully logged out";
		$_SESSION['alertcolor'] = $type = "success";
		$page = "../../index.php";
		header('Location: ' . $page);
		break;


	default:
		exit('Unexpected route. Please contact administrator.');
		break;
}
