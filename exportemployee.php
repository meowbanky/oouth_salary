<?php
session_start();

include_once('classes/model.php');
require_once('Connections/paymaster.php');
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
    header("location: ../index.php");
    exit();
}
require 'report/office_vendor/autoload.php';


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

require 'report/vendor/autoload.php';





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




$response['data'] = array();
$response['code'] = 1;
$Data['S/No.'] = ' S/No.';
$Data['Staff No.'] = ' Staff No';
$Data['Name'] = 'Name';
$Data['email'] = 'email';
$Data['Dept'] = 'Dept';
$Data['EMP DATE'] = 'EMP DATE';
$Data['POST'] = 'POST';
$Data['Grade_Level'] = 'Grade/Level';
$Data['Bank'] = 'Bank';
$Data['Acct No'] = 'Acct. No.';
$Data['PFA'] = 'PFA';
$Data['PFA No'] = 'PFA ACCT';

array_push($response['data'], $Data);

try {
    $sql = 'SELECT
	tbl_bank.BNAME, 
	tbl_dept.dept, 
	employee.staff_id, 
	employee.PPNO, 
	employee.EMAIL, 
	employee.`NAME`, 
	employee.EMPDATE, 
	employee.POST, 
	employee.GRADE, 
	employee.STEP, 
	employee.ACCTNO, 
	employee.PFAACCTNO, 
	tbl_pfa.PFANAME
FROM
	employee
	LEFT JOIN
	tbl_bank
	ON 
		employee.BCODE = tbl_bank.BCODE
	INNER JOIN
	tbl_dept
	ON 
		employee.DEPTCD = tbl_dept.dept_id
	LEFT JOIN
	tbl_pfa
	ON 
		employee.PFACODE = tbl_pfa.PFACODE
WHERE
	STATUSCD = ? ORDER BY staff_id';

    $query = $conn->prepare($sql);
    $fin = $query->execute(array('A'));
    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $numberofstaff = count($res);
    $counter = 1;

    $i = 1;

    foreach ($res as $row => $link) {

        $Data['S/No.'] = $i;
        $Data['Staff No.'] = $link['staff_id'];
        $Data['Name'] = $link['NAME'];
        $Data['email'] = $link['EMAIL'];
        $Data['Dept'] = $link['dept'];
        $Data['EMP DATE'] = $link['EMPDATE'];
        $Data['POST'] = $link['POST'];
        $Data['Grade_Level'] = $link['GRADE'] . '-' . $link['STEP'];
        $Data['Bank'] = $link['BNAME'];
        $Data['Acct No'] = $link['ACCTNO'];
        $Data['PFA'] = $link['PFANAME'];
        $Data['PFA No'] = $link['PFAACCTNO'];
        $counter++;
        ++$i;

        array_push($response['data'], $Data);
    }
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
$tempfilepath = 'employee.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($tempfilepath);

// Set headers to force download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $tempfilepath . '"');
header('Cache-Control: max-age=0');
header('Content-Length: ' . filesize($tempfilepath));

// Output the Excel file to the browser
readfile($tempfilepath);

// Delete the temporary file

if (file_exists($tempfilepath)) {
    unlink($tempfilepath);
}
