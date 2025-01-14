<?php
session_start();

include_once('../classes/model.php');
require_once('../Connections/paymaster.php');
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) === '')) {
    header("location: ../index.php");
    exit;
}
require 'office_vendor/autoload.php';


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

require 'vendor/autoload.php';





if (!function_exists("GetSQLValueString")) {
    function GetSQLValueString($con, $theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "")
    {

        $theValue = function_exists("mysqli_real_escape_string") ? mysqli_real_escape_string($con, $theValue) : mysqli_escape_string($con, $theValue);
        switch ($theType) {
            case "text":
                $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
                break;
            case "long":
            case "int":
                $theValue = ($theValue != "") ? intval($theValue) : "NULL";
                break;
            case "double":
                $theValue = ($theValue != "") ? doubleval($theValue) : "NULL";
                break;
            case "date":
                $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
                break;
            case "defined":
                $theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue;
                break;
        }
        return $theValue;
    }
}

// if (!isset($_POST['period']) or !isset($_POST['deduction'])) {
//     $period = -1;
//     $deduction = -1;
// } else {
//     $period = $_POST['period'];
//     $deduction = $_POST['deduction'];
// }

// if (isset($_POST['deduction_text'])) {
//     $deduction_text = $_POST['deduction_text'];
// } else {
//     $deduction_text = '';
// }

// if (isset($_POST['period_text'])) {
//     $period_text =    $_POST['period_text'];
// } else {
//     $period_text = '';
// }


// if (isset($_POST['code'])) {
//     $code =    $_POST['code'];
// } else {
//     $code = -1;
// }

$period = filter_input(INPUT_POST, 'period', FILTER_VALIDATE_INT) ?: -1;
$deduction = filter_input(INPUT_POST, 'deduction', FILTER_VALIDATE_INT) ?: -1;
$deduction_text = filter_input(INPUT_POST, 'deduction_text', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
$period_text = filter_input(INPUT_POST, 'period_text', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
$code = filter_input(INPUT_POST, 'code', FILTER_VALIDATE_INT) ?: -1;

$response['data'] = array();
$response['code'] = 1;
$Data['S/No.'] = ' S/No.';
$Data['StaffNo'] = 'Staff No.';
$Data['Name'] = 'Name';
$Data['Amount'] = 'Amount';

if ($deduction == 87 || $deduction == 85) {
    $Data['Balance'] = 'Balance';
}

array_push($response['data'], $Data);

$query = $conn->prepare('SELECT * FROM email_deductionlist WHERE allow_id = ?');
$res = $query->execute(array($deduction));
$existtrans = $query->fetch();

try {
    if ($code == 1) {
        $sql = 'SELECT tbl_master.allow as deduc, master_staff.staff_id, master_staff.`NAME` FROM tbl_master INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id WHERE tbl_master.allow_id = ? and tbl_master.period = ? and master_staff.period = ? order by master_staff.staff_id asc';
    } else {
        $sql = 'SELECT tbl_master.deduc as deduc, master_staff.staff_id, master_staff.`NAME` FROM tbl_master INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id WHERE tbl_master.allow_id = ? and tbl_master.period = ? and master_staff.period = ? order by master_staff.staff_id asc';
    }

    $query = $conn->prepare($sql);
    $fin = $query->execute(array($deduction, $period, $period));
    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $numberofstaff = count($res);
    $counter = 1;
    //sdsd
    $sumAll = 0;
    $sumDeduct = 0;
    $sumTotal = 0;
    $i = 1;

    foreach ($res as $row => $link) {

        $Data['S/No.']  = $i;
        $Data['StaffNo']  = $link['staff_id'];
        $Data['Name'] = $link['NAME'];
        $Data['Amount']  = ($link['deduc']);
        if ($deduction == 87 || $deduction == 85) {
            $loan = retrieveLoanStatus($link['staff_id'], $deduction);
            $repayment = retrieveLoanBalanceStatus($link['staff_id'], $deduction, $period);
            $Data['Balance'] = ($loan - $repayment);
        }

        $sumTotal = $sumTotal + floatval($link['deduc']);
        $counter++;
        ++$i;

        array_push($response['data'], $Data);
    }

    $Data['S/No.']  = '';
    $Data['StaffNo']  = '';
    $Data['Name'] = 'TOTAL';
    $Data['Amount']  = ($sumTotal);
    if ($deduction == 87 || $deduction == 85) {
        $loan = retrieveLoanStatus($link['staff_id'], $deduction);
        $repayment = retrieveLoanBalanceStatus($link['staff_id'], $deduction, $period);
        $Data['Balance'] = '';
    }
    array_push($response['data'], $Data);
} catch (PDOException $e) {
    echo $e->getMessage();
}


$spreadsheet = new Spreadsheet();
$activeWorksheet = $spreadsheet->getActiveSheet();
$activeWorksheet->fromArray(
    $response['data'],
    null,
    'A1'
);
$tempfilepath = $deduction_text . ' - ' . $period_text . '.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($tempfilepath);


if ($existtrans) {
    $email_register = $existtrans['email'];


    //$email_register = 'bankole.adesoji@gmail.com';
    $first_name = $deduction_text;

    $sendmessage = "Find attached " . $deduction_text . " List  for the Month of " . $period_text;


    //Create a new PHPMailer instance
    $mail = new PHPMailer();

    //Tell PHPMailer to use SMTP
    $mail->isSMTP();

    //Enable SMTP debugging
    //SMTP::DEBUG_OFF = off (for production use)
    //SMTP::DEBUG_CLIENT = client messages
    //SMTP::DEBUG_SERVER = client and server messages
    $mail->SMTPDebug = SMTP::DEBUG_OFF;
    // $mail->SMTPDebug = SMTP::DEBUG_CLIENT;
    // $mail->SMTPDebug = SMTP::DEBUG_SERVER;

    //Set the hostname of the mail server
    $mail->Host = "mail.oouthsalary.com.ng";
    //Use `$mail->Host = gethostbyname('smtp.gmail.com');`
    //if your network does not support SMTP over IPv6,
    //though this may cause issues with TLS

    //Set the SMTP port number:
    // - 465 for SMTP with implicit TLS, a.k.a. RFC8314 SMTPS or
    // - 587 for SMTP+STARTTLS
    $mail->Port = 465;

    //Set the encryption mechanism to use:
    // - SMTPS (implicit TLS on port 465) or
    // - STARTTLS (explicit TLS on port 587)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;

    //Whether to use SMTP authentication
    $mail->SMTPAuth = true;

    //Username to use for SMTP authentication - use full email address for gmail
    $mail->Username = "salary@oouthsalary.com.ng";

    //Password to use for SMTP authentication
    $mail->Password = "b07NwW3_5WNr";

    //Set who the message is to be sent from
    //Note that with gmail you can only use your account address (same as `Username`)
    //or predefined aliases that you have configured within your account.
    //Do not use user-submitted addresses in here
    $mail->setFrom("no-reply@oouth.com", "OOUTHSALARY");

    //Set an alternative reply-to address
    //This is a good place to put user-submitted addresses
    $mail->addReplyTo("no-reply@oouth.com", "OOUTHSALARY");

    //Set who the message is to be sent to
    $mail->addAddress($email_register, $first_name);
    $mail->addBCC('bankole.adesoji@gmail.com');

    //Set the subject line
    $mail->Subject = "OOUTH " . $deduction_text . " List";

    //Read an HTML message body from an external file, convert referenced images to embedded,
    //convert HTML into a basic plain-text alternative body
    //$mail->msgHTML(file_get_contents('contents.html'), __DIR__);

    //Replace the plain text body with one created manually
    $mail->AltBody = "This is a plain-text message body";

    $mail->Body = $sendmessage;

    //Attach an image file
    $mail->addAttachment($tempfilepath);



    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $tempfilepath);
    header('Cache-Control: max-age=0');

    // Read the Excel file content
    readfile($tempfilepath);





    //send the message, check for errors
    if (!$mail->send()) {
        echo "Mailer Error: " . $mail->ErrorInfo;
    } else {

        if (file_exists($tempfilepath)) {
            $status  = unlink($tempfilepath);
        } else {
            // echo 'The file '.$tempfilepath.".xlsx".' doesnot exist';
        }
        echo "2";
    }
} else {
    // header('Content-Type: application/vnd.ms-excel');
    header('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $tempfilepath);
    header('Cache-Control: max-age=0');

    // Read the Excel file content
    readfile($tempfilepath);

    if (file_exists($tempfilepath)) {
        $status  = unlink($tempfilepath);
    } else {
        // echo 'The file '.$tempfilepath.".xlsx".' doesnot exist';
    }
    echo "2";
}
