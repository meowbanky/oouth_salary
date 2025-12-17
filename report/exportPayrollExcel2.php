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
$queryemployee = "SELECT staff_id FROM master_staff WHERE period = $period ORDER BY staff_id ASC";
$result_employee = mysqli_query($salary, $queryemployee);
$row_employee = mysqli_fetch_assoc($result_employee);
$total_employee = mysqli_num_rows($result_employee);

$counter = 0;
$response['data'] = array();

while ($row_employee = mysqli_fetch_assoc($result_employee)) {
    $thisemployee = $row_employee['staff_id'];


    $response['code'] = 1;
    $Data['NAME'] = '';
    $Data['value'] = '';

    array_push($response['data'], $Data);



    $Data['NAME'] = 'NAME';
    $Data['value'] = 'Staff No';

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

        $Data['NAME'] = 'PAY PERIOD';
        $Data['value'] = $fullPeriod;

        array_push($response['data'], $Data);
    } catch (PDOException $e) {
        echo $e->getMessage();
    }

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

        $Data['NAME'] = 'NAME';
        $Data['value'] = $row_staff['NAME'];
        array_push($response['data'], $Data);

        $Data['NAME'] = 'staff No';
        $Data['value'] = $row_staff['staff_id'];
        array_push($response['data'], $Data);

        $Data['NAME'] = 'Dept';
        $Data['value'] = $row_staff['dept'];
        array_push($response['data'], $Data);

        $Data['NAME'] = 'Account No';
        $Data['value'] = $row_staff['ACCTNO'];
        array_push($response['data'], $Data);

        $Data['NAME'] = 'Grade/Level';
        $Data['value'] = $row_staff['GRADE'] . '/' . $row_staff['STEP'];
        array_push($response['data'], $Data);
    } catch (PDOException $e) {
        echo "Error: " . $e->getmessage();
    }



    try {
        $query = $conn->prepare('SELECT tbl_master.staff_id, IFNULL(tbl_master.allow, 0) AS allow FROM tbl_master WHERE allow_id = ? AND staff_id = ? AND period = ?');
        $fin = $query->execute(array('1', $thisemployee, $period));

        if ($fin) {
            // Query executed successfully
            $row_count = $query->rowCount();

            if ($row_count > 0) {
                // Rows were returned
                $res_consolidated = $query->fetch();
                $consolidated = $res_consolidated['allow'];
                $conso =  $res_consolidated['allow'];
                // Process the data as needed
            } else {
                $conso = 0;
            }
        } else {
            // Error occurred while executing the query
            // Handle the error here
        }
    } catch (PDOException $e) {
        echo $e->getMessage();
    }

    $totalAllow = 0;

    $Data['NAME'] = 'CONSOLIDATED';
    $Data['value'] = '';
    array_push($response['data'], $Data);

    $Data['NAME'] = 'CONSOLIDATED SALARY';
    $Data['value'] = $conso;
    array_push($response['data'], $Data);

    $Data['NAME'] = 'ALLOWANCES';
    $Data['value'] = '';
    array_push($response['data'], $Data);

    try {

        $query = $conn->prepare('SELECT tbl_master.staff_id, tbl_master.allow, tbl_earning_deduction.ed FROM tbl_master INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id WHERE allow_id <> ? and staff_id = ? and period = ? and type = ?');
        $fin = $query->execute(array('1', $thisemployee, $period, '1'));
        $res = $query->fetchAll(PDO::FETCH_ASSOC);
        //print_r($res);

        foreach ($res as $row => $link) {
            $totalAllow = $totalAllow + floatval($link['allow']);
            $Data['NAME'] = $link['ed'];
            $Data['value'] = $link['allow'];
            array_push($response['data'], $Data);
        }
    } catch (PDOException $e) {
        echo $e->getMessage();
    }

    $Data['NAME'] = 'GROSS PAY';
    $Data['value'] = floatval($totalAllow) + floatval($consolidated);
    array_push($response['data'], $Data);

    $Data['NAME'] = '';
    $Data['value'] = '';
    array_push($response['data'], $Data);

    $Data['NAME'] = 'Deductions';
    $Data['value'] = '';
    array_push($response['data'], $Data);

    $totalDeduction = 0;

    try {
        $query = $conn->prepare('SELECT tbl_master.staff_id, tbl_master.deduc, tbl_earning_deduction.ed FROM tbl_master INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id WHERE staff_id = ? and period = ? and type = ?');
        $fin = $query->execute(array($thisemployee, $period, '2'));
        $res = $query->fetchAll(PDO::FETCH_ASSOC);


        foreach ($res as $row => $link) {

            //Get ED description
            $totalDeduction = $totalDeduction + floatval($link['deduc']);
            $Data['NAME'] = $link['ed'];
            $Data['value'] = $link['deduc'];
            array_push($response['data'], $Data);
        }
    } catch (PDOException $e) {
        echo $e->getMessage();
    }

    $Data['NAME'] = 'Total Deductions';
    $Data['value'] = $totalDeduction;
    array_push($response['data'], $Data);

    $Data['NAME'] = 'NET PAY';
    $Data['value'] = floatval($totalAllow) + floatval($consolidated) - floatval($totalDeduction);
    array_push($response['data'], $Data);

    $response['code'] = 1;
    $Data['NAME'] = '';
    $Data['value'] = '';

    array_push($response['data'], $Data);
    $counter++;
    //end employee payslips

    if ($counter > 0 && ($counter % $employeesPerPage) == 0) {
        $activeWorksheet->setBreak('A' . ($activeWorksheet->getHighestRow() + 1), PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::BREAK_ROW);
    }
}

$activeWorksheet->fromArray(
    $response['data'],
    null,
    'A1'
);
//$activeWorksheet->setBreak('A' . ($row->getRowIndex() + 1), PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::BREAK_ROW);

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
