<?php
session_start();

include_once('../classes/model.php');
require_once('../Connections/paymaster.php');
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
    header("location: ../index.php");
    exit();
}
require '../report/office_vendor/autoload.php';


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


//require 'report/vendor/autoload.php';
$spreadsheet = new Spreadsheet();
$activeWorksheet = $spreadsheet->getActiveSheet();
$employeesPerPage = 4;

$styleArray = [
    'font' => [
        'bold' => true,
    ],
    'alignment' => [
        'horizontal' => PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
    ],
    'borders' => [
        'top' => [
            'borderStyle' => PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
        ],
    ],
    'fill' => [
        'fillType' => PhpOffice\PhpSpreadsheet\Style\Fill::FILL_GRADIENT_LINEAR,
        'rotation' => 90,
        'startColor' => [
            'argb' => 'FFA0A0A0',
        ],
        'endColor' => [
            'argb' => 'FFFFFFFF',
        ],
    ],
];

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


if (isset($_POST['period'])) {
    $period = $_POST['period'];
} else {
    $period = -1;
}

mysqli_select_db($salary, $database_salary);
$queryemployee = "SELECT staff_id FROM master_staff WHERE period = $period AND (DEPTCD != 40 AND DEPTCD != 43) ORDER BY staff_id ASC";
$result_employee = mysqli_query($salary, $queryemployee);
$row_employee = mysqli_fetch_assoc($result_employee);
$total_employee = mysqli_num_rows($result_employee);

$counter = 1;
$response['data'] = array();

$Data['s_n'] = 'S/NO';
$Data['Empno'] = 'Empno';
$Data['Name'] = 'Name';
$Data['consolidated'] = ' consolidated ';
$Data['Hazard'] = ' Hazard ';
$Data['Teaching'] = 'Teaching ';
$Data['Clinical'] = 'Clinical';
$Data['Specialist'] = 'Specialist';
$Data['Other Allowance2'] = 'Other Allowance2';
$Data['EmArrearspno'] = 'Arrears';
$Data['PENSION'] = 'PENSION';
$Data['Total'] = ' Total';

$totalAllow = 0;
array_push($response['data'], $Data);


try {
    $query = $conn->prepare('SELECT concat(payperiods.description," ",payperiods.periodYear) as period FROM payperiods WHERE periodId = ?');
    $res = $query->execute(array($period));
    $out = $query->fetchAll(PDO::FETCH_ASSOC);

    while ($row = array_shift($out)) {
        $fullPeriod = $row['period'];
    }
    if ($period == -1) {
        $fullPeriod = '';
    }
} catch (PDOException $e) {
    echo $e->getMessage();
}




while ($row_employee = mysqli_fetch_assoc($result_employee)) {

    $totalAllow = 0;

    $thisemployee = $row_employee['staff_id'];




    try {
        $query = $conn->prepare('SELECT
                                                            tbl_bank.BNAME,
                                                            tbl_dept.dept,
                                                            master_staff.STEP,
                                                            master_staff.GRADE,
                                                            master_staff.staff_id,
                                                            master_staff.`NAME`,
                                                            master_staff.ACCTNO,
                                                            employee.EMAIL
                                                            FROM
                                                            master_staff
                                                            INNER JOIN
                                                            tbl_dept
                                                            ON
                                                            tbl_dept.dept_id = master_staff.DEPTCD
                                                            INNER JOIN
                                                            tbl_bank
                                                            ON
                                                            tbl_bank.BCODE = master_staff.BCODE
                                                            INNER JOIN
                                                            employee
                                                            ON
                                                            master_staff.staff_id = employee.staff_id WHERE master_staff.staff_id = ? and period = ?');
        $res = $query->execute(array($thisemployee, $period));
        $row_staff = $query->fetch();

        $Data['s_n'] = $counter;
        $Data['Empno'] = $row_staff['staff_id'];
        $Data['Name'] = $row_staff['NAME'];
    } catch (PDOException $e) {
        echo "Error: " . $e->getmessage();
    }


    try {
        $query = $conn->prepare('SELECT tbl_master.allow, tbl_master.deduc,tbl_master.allow_id,edDesc FROM tbl_master INNER JOIN
	    tbl_earning_deduction ON  tbl_master.allow_id = tbl_earning_deduction.ed_id WHERE  allow_id IN (1,5,23,19,39,25,3,50) AND staff_id = ? AND period = ?');
        $fin = $query->execute(array($thisemployee, $period));
        $res = $query->fetchAll(PDO::FETCH_ASSOC);
        //print_r($res);
        $Data['consolidated'] = '';
        $Data['Hazard'] = '';
        $Data['Teaching'] = '';
        $Data['Clinical'] = '';
        $Data['Specialist'] = '';
        $Data['Other Allowance2'] = '';
        $Data['EmArrearspno'] = '';
        $Data['PENSION'] = '';
        $Data['Total'] = ' Total';
        foreach ($res as $row => $link) {


            if ($link['allow_id'] == '1') {
                $Data['consolidated'] = $link['allow'];
                $totalAllow = $totalAllow + $link['allow'];
            } elseif ($link['allow_id'] == '5') {
                $Data['Hazard'] = $link['allow'];
                $totalAllow = $totalAllow + $link['allow'];
            } elseif ($link['allow_id'] == '23') {
                $Data['Teaching'] = $link['allow'];
                $totalAllow = $totalAllow + $link['allow'];
            } elseif ($link['allow_id'] == '19') {
                $Data['Clinical'] = $link['allow'];
                $totalAllow = $totalAllow + $link['allow'];
            } elseif ($link['allow_id'] == 39) {
                $Data['Specialist'] = $link['allow'];
                $totalAllow = $totalAllow + $link['allow'];
            } elseif ($link['allow_id'] == '25') {
                $Data['Other Allowance2'] = $link['allow'];
                $totalAllow = $totalAllow + $link['allow'];
            } elseif ($link['allow_id'] == '3') {
                $Data['EmArrearspno'] = $link['allow'];
                $totalAllow = $totalAllow + $link['allow'];
            } elseif ($link['allow_id'] == '50') {
                $Data['PENSION'] = $link['deduc'];
                $totalAllow = $totalAllow + $link['deduc'];
            }
        }
    } catch (PDOException $e) {
        echo $e->getMessage();
    }
    $Data['Total'] = $totalAllow;
    array_push($response['data'], $Data);
    $counter++;
}

$activeWorksheet->fromArray(
    $response['data'],
    null,
    'A1'
);
//$activeWorksheet->setBreak('A' . ($row->getRowIndex() + 1), PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::BREAK_ROW);

$tempfilepath = $fullPeriod . ' taxExport.xlsx';
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
