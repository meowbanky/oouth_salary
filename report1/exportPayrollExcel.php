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

// Initialize Spreadsheet
$spreadsheet = new Spreadsheet();
$activeWorksheet = $spreadsheet->getActiveSheet();
$employeesPerPage = 4;

// Common Styles
$styleArray = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT],
    'borders' => ['top' => ['borderStyle' => PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
    'fill' => [
        'fillType' => PhpOffice\PhpSpreadsheet\Style\Fill::FILL_GRADIENT_LINEAR,
        'rotation' => 90,
        'startColor' => ['argb' => 'FFA0A0A0'],
        'endColor' => ['argb' => 'FFFFFFFF']
    ],
];

// Helper function for SQL value sanitation
if (!function_exists("GetSQLValueString")) {
    function GetSQLValueString($con, $theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "")
    {
        $theValue = function_exists("mysqli_real_escape_string") ? mysqli_real_escape_string($con, $theValue) : mysqli_escape_string($con, $theValue);
        switch ($theType) {
            case "text": $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL"; break;
            case "long":
            case "int": $theValue = ($theValue != "") ? intval($theValue) : "NULL"; break;
            case "double": $theValue = ($theValue != "") ? doubleval($theValue) : "NULL"; break;
            case "date": $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL"; break;
            case "defined": $theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue; break;
        }
        return $theValue;
    }
}

// Get period input
$period = isset($_POST['period']) ? $_POST['period'] : -1;

// Query to fetch all employees at once
mysqli_select_db($salary, $database_salary);
$queryemployee = "SELECT staff_id FROM master_staff WHERE period = $period ORDER BY staff_id ASC";
$result_employee = mysqli_query($salary, $queryemployee);
$total_employee = mysqli_num_rows($result_employee);

$response['data'] = [];
$spreadsheetData = [];  // Collect all data here for bulk insert

$counter = 0;
$staffIds = [];
while ($row_employee = mysqli_fetch_assoc($result_employee)) {
    $staffIds[] = $row_employee['staff_id'];
}

// Fetch all required data in bulk instead of per staff member
try {
    // Fetch pay periods
    $query = $conn->prepare('SELECT concat(payperiods.description," ",payperiods.periodYear) as period FROM payperiods WHERE periodId = ?');
    $query->execute(array($period));
    $out = $query->fetchAll(PDO::FETCH_ASSOC);
    $fullPeriod = $period != -1 ? $out[0]['period'] : '';

    // Fetch staff details in bulk
    $placeholders = implode(',', array_fill(0, count($staffIds), '?'));
    $query = $conn->prepare("SELECT 
                                tbl_bank.BNAME, tbl_dept.dept, master_staff.STEP, master_staff.GRADE,
                                master_staff.staff_id, master_staff.`NAME`, master_staff.ACCTNO, employee.EMAIL
                              FROM master_staff
                              INNER JOIN tbl_dept ON tbl_dept.dept_id = master_staff.DEPTCD
                              INNER JOIN tbl_bank ON tbl_bank.BCODE = master_staff.BCODE
                              INNER JOIN employee ON master_staff.staff_id = employee.staff_id
                              WHERE master_staff.staff_id IN ($placeholders) AND period = ?");
    $query->execute(array_merge($staffIds, [$period]));
    $staffData = $query->fetchAll(PDO::FETCH_ASSOC);

    // Fetch allowances and deductions in bulk
    $query = $conn->prepare("SELECT 
                                tbl_master.staff_id, IFNULL(tbl_master.allow, 0) AS allow, tbl_earning_deduction.ed, tbl_master.deduc, tbl_master.allow_id 
                              FROM tbl_master
                              LEFT JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id
                              WHERE tbl_master.staff_id IN ($placeholders) AND tbl_master.period = ?");
    $query->execute(array_merge($staffIds, [$period]));
    $financialData = $query->fetchAll(PDO::FETCH_ASSOC);

    $financialMap = [];
    foreach ($financialData as $row) {
        $financialMap[$row['staff_id']][] = $row;
    }

    // Process and collect data for each employee
    foreach ($staffData as $staff) {
        $Data = [];
        $thisemployee = $staff['staff_id'];

        // Add employee basic info
        $Data[] = ['NAME' => 'NAME', 'Value' => $staff['NAME']];
        $Data[] = ['NAME' => 'Staff No', 'Value' => $staff['staff_id']];
        $Data[] = ['NAME' => 'Dept', 'Value' => $staff['dept']];
        $Data[] = ['NAME' => 'Account No', 'Value' => $staff['ACCTNO']];
        $Data[] = ['NAME' => 'Grade/Level', 'Value' => $staff['GRADE'] . '/' . $staff['STEP']];
        $Data[] = ['NAME' => 'PAY PERIOD', 'Value' => $fullPeriod];

        // Add financial data (consolidated, allowances, and deductions)
        $totalAllow = 0;
        $totalDeduction = 0;
        foreach ($financialMap[$thisemployee] as $finance) {
            if ($finance['allow_id'] == 1) {
                $Data[] = ['NAME' => 'CONSOLIDATED', 'Value' => $finance['allow']];
            } elseif ($finance['deduc']) {
                $totalDeduction += floatval($finance['deduc']);
                $Data[] = ['NAME' => $finance['ed'], 'Value' => $finance['deduc']];
            } else {
                $totalAllow += floatval($finance['allow']);
                $Data[] = ['NAME' => $finance['ed'], 'Value' => $finance['allow']];
            }
        }

        // Add gross and net pay
        $grossPay = $totalAllow + $finance['allow'];  // Consolidated is already in totalAllow
        $netPay = $grossPay - $totalDeduction;
        $Data[] = ['NAME' => 'GROSS PAY', 'Value' => $grossPay];
        $Data[] = ['NAME' => 'NET PAY', 'Value' => $netPay];

        // Add this employee's data to the spreadsheetData
        foreach ($Data as $row) {
            array_push($spreadsheetData, $row);
        }

        $counter++;
        if ($counter > 0 && ($counter % $employeesPerPage) == 0) {
            $activeWorksheet->setBreak('A' . ($activeWorksheet->getHighestRow() + 1), PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::BREAK_ROW);
        }
    }
} catch (PDOException $e) {
    echo $e->getMessage();
}

// Write all data to the spreadsheet in bulk
$activeWorksheet->fromArray($spreadsheetData, null, 'A1');

// Save file
$tempfilepath = 'employee.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($tempfilepath);

// Set headers and output Excel file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $tempfilepath . '"');
header('Cache-Control: max-age=0');
header('Content-Length: ' . filesize($tempfilepath));
readfile($tempfilepath);

// Delete the temporary file
if (file_exists($tempfilepath)) {
    unlink($tempfilepath);
}
?>
