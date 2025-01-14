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

$spreadsheet = new Spreadsheet();
$activeWorksheet = $spreadsheet->getActiveSheet();
$employeesPerPage = 4;

$styleArray = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT],
    'borders' => ['top' => ['borderStyle' => PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
    'fill' => [
        'fillType' => PhpOffice\PhpSpreadsheet\Style\Fill::FILL_GRADIENT_LINEAR,
        'rotation' => 90,
        'startColor' => ['argb' => 'FFA0A0A0'],
        'endColor' => ['argb' => 'FFFFFFFF'],
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

$period = isset($_POST['period']) ? $_POST['period'] : -1;

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

mysqli_select_db($salary, $database_salary);

// Fetch all employees and required details in one query
$queryemployee = "
    SELECT master_staff.staff_id, master_staff.NAME, master_staff.ACCTNO, tbl_bank.BNAME, tbl_dept.dept, master_staff.STEP, master_staff.GRADE, employee.EMAIL 
    FROM master_staff 
    JOIN tbl_dept ON tbl_dept.dept_id = master_staff.DEPTCD 
    JOIN tbl_bank ON tbl_bank.BCODE = master_staff.BCODE 
    JOIN employee ON master_staff.staff_id = employee.staff_id 
    WHERE period = $period AND (master_staff.DEPTCD != 40 AND master_staff.DEPTCD != 43)
    ORDER BY staff_id ASC
";

$result_employee = mysqli_query($salary, $queryemployee);
$employees = mysqli_fetch_all($result_employee, MYSQLI_ASSOC);

// Fetch all allowances in one batch
$employee_ids = array_column($employees, 'staff_id');
$employee_ids_list = implode(',', $employee_ids);

$queryAllowances = "
    SELECT staff_id, tbl_master.allow, tbl_master.deduc, tbl_master.allow_id 
    FROM tbl_master 
    INNER JOIN tbl_earning_deduction ON tbl_master.allow_id = tbl_earning_deduction.ed_id 
    WHERE allow_id IN (1,5,23,19,39,25,3,50) AND staff_id IN ($employee_ids_list) AND period = $period
";
$resultAllowances = mysqli_query($salary, $queryAllowances);
$allowances = mysqli_fetch_all($resultAllowances, MYSQLI_ASSOC);

$counter = 1;
$activeWorksheet->fromArray(
    ['S/NO', 'Empno', 'Name', 'Consolidated', 'Hazard', 'Teaching', 'Clinical', 'Specialist', 'Other Allowance2', 'Arrears', 'PENSION', 'Total'],
    null,
    'A1'
);

// Loop through employees and calculate allowances for each
foreach ($employees as $employee) {
    $Data = [
        's_n' => $counter,
        'Empno' => $employee['staff_id'],
        'Name' => $employee['NAME'],
        'consolidated' => '',
        'Hazard' => '',
        'Teaching' => '',
        'Clinical' => '',
        'Specialist' => '',
        'Other Allowance2' => '',
        'EmArrearspno' => '',
        'PENSION' => '',
        'Total' => 0
    ];

    // Initialize total allowances
    $totalAllow = 0;

    // Find allowances for this employee
    foreach ($allowances as $allowance) {
        if ($allowance['staff_id'] == $employee['staff_id']) {
            switch ($allowance['allow_id']) {
                case '1':
                    $Data['consolidated'] = $allowance['allow'];
                    break;
                case '5':
                    $Data['Hazard'] = $allowance['allow'];
                    break;
                case '23':
                    $Data['Teaching'] = $allowance['allow'];
                    break;
                case '19':
                    $Data['Clinical'] = $allowance['allow'];
                    break;
                case '39':
                    $Data['Specialist'] = $allowance['allow'];
                    break;
                case '25':
                    $Data['Other Allowance2'] = $allowance['allow'];
                    break;
                case '3':
                    $Data['EmArrearspno'] = $allowance['allow'];
                    break;
                case '50':
                    $Data['PENSION'] = $allowance['deduc'];
                    break;
            }
            $totalAllow += $allowance['allow'];
        }
    }

    $Data['Total'] = $totalAllow;

    // Write the data directly to the spreadsheet row
    $activeWorksheet->fromArray($Data, null, 'A' . ($counter + 1));
    $counter++;
}

// Set headers to force download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fullPeriod . ' taxExport.xlsx"');
header('Cache 
Control: max-age=0');

// Output the Excel file to the browser
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

// Terminate the script to prevent further output
exit();
