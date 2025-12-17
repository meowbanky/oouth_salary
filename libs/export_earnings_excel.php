<?php
session_start();
require_once '../Connections/paymaster.php';
require_once '../classes/model.php';
require_once 'App.php';
require_once 'middleware.php';

$App = new App();
$App->checkAuthentication();
checkPermission();

// Check if user is admin
if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) === '' || ($_SESSION['role'] ?? '') !== 'Admin') {
    header("Location: ../index.php");
    exit;
}

// Get staff_id from URL parameter
$staff_id = $_GET['staff_id'] ?? '';

if (empty($staff_id)) {
    echo "Error: Staff ID is required";
    exit;
}

try {
    // Get employee details
    $query = $conn->prepare('SELECT
        employee.staff_id,
        employee.`NAME`,
        employee.EMPDATE,
        tbl_dept.dept,
        employee.POST,
        employee.GRADE,
        employee.STEP,
        employee.ACCTNO,
        tbl_bank.BNAME,
        tbl_pfa.PFANAME,
        employee.PFAACCTNO,
        employee.TAXPD,
        IFNULL(employee.HARZAD_TYPE,-1) AS HARZAD_TYPE,
        employee.PFACODE,
        employee.CALLTYPE,
        employee.STATUSCD
        FROM employee
        LEFT JOIN tbl_dept ON tbl_dept.dept_id = employee.DEPTCD
        LEFT JOIN tbl_bank ON tbl_bank.BCODE = employee.BCODE
        LEFT JOIN tbl_pfa ON tbl_pfa.PFACODE = employee.PFACODE 
        WHERE staff_id = ?');
    $query->execute(array($staff_id));
    $employee = $query->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        echo "Error: Employee not found";
        exit;
    }
    
    // Get earnings data
    $query = $conn->prepare('SELECT ifnull(allow_deduc.`value`,0) as `value`,allow_deduc.allow_id,allow_deduc.temp_id,tbl_earning_deduction.edDesc FROM
        tbl_earning_deduction right JOIN allow_deduc ON tbl_earning_deduction.ed_id = allow_deduc.allow_id
        WHERE transcode = ? and staff_id = ? order by allow_id asc');
    $query->execute(array('01', $staff_id));
    $earnings = $query->fetchAll(PDO::FETCH_ASSOC);
    
    // Get deductions data
    $query = $conn->prepare('SELECT ifnull(allow_deduc.`value`,0) as `value`, allow_deduc.allow_id,allow_deduc.temp_id,tbl_earning_deduction.edDesc FROM
        tbl_earning_deduction RIGHT JOIN allow_deduc ON tbl_earning_deduction.ed_id = allow_deduc.allow_id
        WHERE transcode = ? and staff_id = ? order by allow_id asc');
    $query->execute(array('02', $staff_id));
    $deductions = $query->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $gross = 0;
    foreach ($earnings as $earning) {
        $gross += $earning['value'];
    }
    
    $totalDeduction = 0;
    foreach ($deductions as $deduction) {
        $totalDeduction += $deduction['value'];
    }
    
    $netPay = $gross - $totalDeduction;
    
    // Set headers for Excel download
    $filename = 'employee_earnings_' . $staff_id . '_' . date('Y-m-d_H-i-s') . '.xls';
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // Create Excel content
    echo '<table border="1">';
    
    // Employee Information
    echo '<tr style="background-color: #4F81BD; color: white; font-weight: bold;">';
    echo '<th colspan="4">Employee Information</th>';
    echo '</tr>';
    echo '<tr>';
    echo '<td><strong>Staff ID:</strong></td>';
    echo '<td>' . htmlspecialchars($employee['staff_id']) . '</td>';
    echo '<td><strong>Name:</strong></td>';
    echo '<td>' . htmlspecialchars($employee['NAME']) . '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td><strong>Grade/Step:</strong></td>';
    echo '<td>' . htmlspecialchars($employee['GRADE']) . '/' . htmlspecialchars($employee['STEP']) . '</td>';
    echo '<td><strong>Department:</strong></td>';
    echo '<td>' . htmlspecialchars($employee['dept']) . '</td>';
    echo '</tr>';
    
    // Earnings Section
    echo '<tr style="background-color: #4F81BD; color: white; font-weight: bold;">';
    echo '<th colspan="4">Earnings</th>';
    echo '</tr>';
    echo '<tr style="background-color: #E6E6E6; font-weight: bold;">';
    echo '<th>Code</th>';
    echo '<th>Description</th>';
    echo '<th>Amount</th>';
    echo '<th>Type</th>';
    echo '</tr>';
    
    foreach ($earnings as $earning) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($earning['allow_id']) . '</td>';
        echo '<td>' . htmlspecialchars($earning['edDesc']) . '</td>';
        echo '<td>' . number_format($earning['value'], 2) . '</td>';
        echo '<td>Earning</td>';
        echo '</tr>';
    }
    
    // Deductions Section
    echo '<tr style="background-color: #4F81BD; color: white; font-weight: bold;">';
    echo '<th colspan="4">Deductions</th>';
    echo '</tr>';
    echo '<tr style="background-color: #E6E6E6; font-weight: bold;">';
    echo '<th>Code</th>';
    echo '<th>Description</th>';
    echo '<th>Amount</th>';
    echo '<th>Type</th>';
    echo '</tr>';
    
    foreach ($deductions as $deduction) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($deduction['allow_id']) . '</td>';
        echo '<td>' . htmlspecialchars($deduction['edDesc']) . '</td>';
        echo '<td>' . number_format($deduction['value'], 2) . '</td>';
        echo '<td>Deduction</td>';
        echo '</tr>';
    }
    
    // Summary Section
    echo '<tr style="background-color: #4F81BD; color: white; font-weight: bold;">';
    echo '<th colspan="4">Summary</th>';
    echo '</tr>';
    echo '<tr>';
    echo '<td><strong>Gross Salary:</strong></td>';
    echo '<td colspan="3">' . number_format($gross, 2) . '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td><strong>Total Deductions:</strong></td>';
    echo '<td colspan="3">' . number_format($totalDeduction, 2) . '</td>';
    echo '</tr>';
    echo '<tr style="background-color: #FFFF99; font-weight: bold;">';
    echo '<td><strong>Net Pay:</strong></td>';
    echo '<td colspan="3">' . number_format($netPay, 2) . '</td>';
    echo '</tr>';
    
    echo '</table>';
    
} catch (PDOException $e) {
    error_log("Database error in export_earnings_excel.php: " . $e->getMessage());
    echo "Error exporting data: " . $e->getMessage();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 