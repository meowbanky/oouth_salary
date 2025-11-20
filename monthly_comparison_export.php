<?php
require_once('Connections/paymaster.php');
include_once('classes/model.php');
require_once 'libs/App.php';
$App = new App();
$App->checkAuthentication();
require_once 'libs/middleware.php';
checkPermission();

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) == '' || $_SESSION['role'] != 'Admin') {
    header("location: index.php");
    exit();
}

$currentPeriod = isset($_GET['current_period']) ? intval($_GET['current_period']) : null;
$previousPeriod = isset($_GET['previous_period']) ? intval($_GET['previous_period']) : null;

if (!$currentPeriod || !$previousPeriod || $currentPeriod <= 0 || $previousPeriod <= 0) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Export Error</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 50px; }
            .error { background: #fee; border: 1px solid #fcc; padding: 20px; border-radius: 5px; }
        </style>
        <link href="css/dark-mode.css" rel="stylesheet">
    <script src="js/theme-manager.js"></script>
</head>
    <body>
        <div class="error">
            <h2>Export Error</h2>
            <p>Invalid periods selected. Please go back to the report and select valid periods.</p>
            <p><a href="monthly_comparison_report.php">← Back to Report</a></p>
        </div>
    </body>
    </html>';
    exit;
}

// Fetch comparison data (reuse the same functions from main report)
function fetchComparisonData($currentPeriod, $previousPeriod) {
    global $conn;
    
    $data = [
        'current_period' => $currentPeriod,
        'previous_period' => $previousPeriod,
        'employee_changes' => [],
        'promotions' => [],
        'allowance_changes' => [],
        'deduction_changes' => [],
        'summary' => []
    ];
    
    // Get current and previous period employees
    $currentEmployees = getPeriodEmployees($currentPeriod);
    $previousEmployees = getPeriodEmployees($previousPeriod);
    
    // Employee changes
    $data['employee_changes']['new'] = array_diff_key($currentEmployees, $previousEmployees);
    $data['employee_changes']['departed'] = array_diff_key($previousEmployees, $currentEmployees);
    $data['employee_changes']['status_changed'] = getStatusChanges($currentEmployees, $previousEmployees);
    
    // Promotions/Demotions
    $data['promotions'] = getPromotionChanges($currentEmployees, $previousEmployees);
    
    // Allowance changes only
    $data['allowance_changes'] = getAllowanceChanges($currentPeriod, $previousPeriod);
    
    // Calculate financial totals for new and departed employees
    if (!empty($data['employee_changes']['new'])) {
        $newStaffIds = array_keys($data['employee_changes']['new']);
        $data['employee_changes']['new_totals'] = getEmployeeFinancialTotals($newStaffIds, $currentPeriod);
    } else {
        $data['employee_changes']['new_totals'] = ['total_allowances' => 0];
    }
    
    if (!empty($data['employee_changes']['departed'])) {
        $departedStaffIds = array_keys($data['employee_changes']['departed']);
        $data['employee_changes']['departed_totals'] = getEmployeeFinancialTotals($departedStaffIds, $previousPeriod);
    } else {
        $data['employee_changes']['departed_totals'] = ['total_allowances' => 0];
    }
    
    // Summary statistics
    $data['summary'] = generateSummary($data);
    
    return $data;
}

function getPeriodEmployees($periodId) {
    global $conn;
    
    $query = "SELECT DISTINCT ms.staff_id, ms.NAME, ms.GRADE, ms.STEP, ms.STATUSCD, ms.DEPTCD, 
                     td.dept, ms.BCODE, ms.ACCTNO, ms.PFACODE, ms.PFAACCTNO
              FROM master_staff ms
              LEFT JOIN tbl_dept td ON td.dept_id = ms.DEPTCD
              WHERE ms.period = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$periodId]);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [];
    foreach ($employees as $emp) {
        $result[$emp['staff_id']] = $emp;
    }
    
    return $result;
}

function getStatusChanges($current, $previous) {
    $changes = [];
    
    foreach ($current as $staffId => $currentEmp) {
        if (isset($previous[$staffId])) {
            $prevEmp = $previous[$staffId];
            if ($currentEmp['STATUSCD'] !== $prevEmp['STATUSCD']) {
                $changes[$staffId] = [
                    'current' => $currentEmp,
                    'previous' => $prevEmp,
                    'change' => $prevEmp['STATUSCD'] . ' → ' . $currentEmp['STATUSCD']
                ];
            }
        }
    }
    
    return $changes;
}

function getPromotionChanges($current, $previous) {
    $changes = [];
    
    foreach ($current as $staffId => $currentEmp) {
        if (isset($previous[$staffId])) {
            $prevEmp = $previous[$staffId];
            $gradeChanged = $currentEmp['GRADE'] !== $prevEmp['GRADE'];
            $stepChanged = $currentEmp['STEP'] !== $prevEmp['STEP'];
            
            if ($gradeChanged || $stepChanged) {
                $changes[$staffId] = [
                    'current' => $currentEmp,
                    'previous' => $prevEmp,
                    'grade_change' => $gradeChanged ? $prevEmp['GRADE'] . ' → ' . $currentEmp['GRADE'] : null,
                    'step_change' => $stepChanged ? $prevEmp['STEP'] . ' → ' . $currentEmp['STEP'] : null
                ];
            }
        }
    }
    
    return $changes;
}

function getEmployeeFinancialTotals($staffIds, $periodId) {
    global $conn;
    
    if (empty($staffIds)) {
        return ['total_allowances' => 0];
    }
    
    $placeholders = str_repeat('?,', count($staffIds) - 1) . '?';
    
    $query = "SELECT 
                SUM(tm.allow) as total_allowances
              FROM tbl_master tm
              WHERE tm.staff_id IN ($placeholders) AND tm.period = ?";
    
    $params = array_merge($staffIds, [$periodId]);
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $totalAllowances = $result['total_allowances'] ?? 0;
    
    return [
        'total_allowances' => $totalAllowances
    ];
}

function getEmployeeGrossAllowance($staffId, $periodId) {
    global $conn;
    
    $query = "SELECT SUM(allow) as total_allowance 
              FROM tbl_master 
              WHERE staff_id = ? AND period = ? AND allow > 0";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$staffId, $periodId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['total_allowance'] ?? 0;
}

function getAllowanceChanges($currentPeriod, $previousPeriod) {
    global $conn;
    
    $query = "SELECT 
                tm.staff_id,
                ms.NAME,
                SUM(tm.allow) as current_total,
                SUM(prev.allow) as previous_total,
                SUM(tm.allow) - SUM(prev.allow) as total_difference
              FROM tbl_master tm
              JOIN master_staff ms ON ms.staff_id = tm.staff_id AND ms.period = tm.period
              INNER JOIN (
                  SELECT staff_id, allow_id, allow
                  FROM tbl_master 
                  WHERE period = ? AND allow > 0
              ) prev ON prev.staff_id = tm.staff_id AND prev.allow_id = tm.allow_id
              WHERE tm.period = ? AND tm.allow > 0
              GROUP BY tm.staff_id, ms.NAME
              HAVING SUM(tm.allow) != SUM(prev.allow)
              ORDER BY total_difference DESC, ms.NAME";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$previousPeriod, $currentPeriod]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDeductionChanges($currentPeriod, $previousPeriod) {
    global $conn;
    
    $query = "SELECT 
                tm.staff_id,
                ms.NAME,
                tm.allow_id,
                ac.ADJDESC,
                ac.TRANSCD,
                tm.deduc as current_amount,
                prev.deduc as previous_amount,
                (tm.deduc - COALESCE(prev.deduc, 0)) as difference
              FROM tbl_master tm
              JOIN master_staff ms ON ms.staff_id = tm.staff_id AND ms.period = tm.period
              JOIN allocode ac ON ac.ADJCD = tm.allow_id
              LEFT JOIN (
                  SELECT staff_id, allow_id, deduc
                  FROM tbl_master 
                  WHERE period = ? AND deduc > 0
              ) prev ON prev.staff_id = tm.staff_id AND prev.allow_id = tm.allow_id
              WHERE tm.period = ? AND tm.deduc > 0
              AND (prev.deduc IS NULL OR tm.deduc != prev.deduc)
              ORDER BY tm.staff_id, ac.ADJDESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$previousPeriod, $currentPeriod]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function generateSummary($data) {
    // Calculate allowance differences only
    $totalAllowanceIncrease = 0;
    $totalAllowanceDecrease = 0;
    
    foreach ($data['allowance_changes'] as $change) {
        if ($change['total_difference'] > 0) {
            $totalAllowanceIncrease += $change['total_difference'];
        } else {
            $totalAllowanceDecrease += abs($change['total_difference']);
        }
    }
    
    $netAllowanceChange = $totalAllowanceIncrease - $totalAllowanceDecrease;
    
    return [
        'new_employees' => count($data['employee_changes']['new']),
        'departed_employees' => count($data['employee_changes']['departed']),
        'status_changes' => count($data['employee_changes']['status_changed']),
        'promotions' => count($data['promotions']),
        'allowance_changes' => count($data['allowance_changes']),
        'total_allowance_increase' => $totalAllowanceIncrease,
        'total_allowance_decrease' => $totalAllowanceDecrease,
        'net_allowance_change' => $netAllowanceChange,
        'gross_difference' => $netAllowanceChange
    ];
}

// Get period descriptions
$periodQuery = "SELECT * FROM payperiods WHERE periodId IN (?, ?)";
$periodStmt = $conn->prepare($periodQuery);
$periodStmt->execute([$currentPeriod, $previousPeriod]);
$periods = $periodStmt->fetchAll(PDO::FETCH_ASSOC);

$currentPeriodDesc = '';
$previousPeriodDesc = '';
foreach ($periods as $period) {
    if ($period['periodId'] == $currentPeriod) {
        $currentPeriodDesc = $period['description'] . ' ' . $period['periodYear'];
    }
    if ($period['periodId'] == $previousPeriod) {
        $previousPeriodDesc = $period['description'] . ' ' . $period['periodYear'];
    }
}

$reportData = fetchComparisonData($currentPeriod, $previousPeriod);

// Check if we have any data
if (empty($reportData['employee_changes']['new']) && 
    empty($reportData['employee_changes']['departed']) && 
    empty($reportData['employee_changes']['status_changed']) && 
    empty($reportData['promotions']) && 
    empty($reportData['allowance_changes']) && 
    empty($reportData['deduction_changes'])) {
    
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Export Error</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 50px; }
            .error { background: #fee; border: 1px solid #fcc; padding: 20px; border-radius: 5px; }
        </style>
        <link href="css/dark-mode.css" rel="stylesheet">
    <script src="js/theme-manager.js"></script>
</head>
    <body>
        <div class="error">
            <h2>Export Error</h2>
            <p>No data found for the selected periods. Please check that both periods have payroll data.</p>
            <p><a href="monthly_comparison_report.php">← Back to Report</a></p>
        </div>
    </body>
    </html>';
    exit;
}

// Set headers for Excel download
$filename = "Monthly_Comparison_Report_{$currentPeriodDesc}_vs_{$previousPeriodDesc}_" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');

// Create file handle
$output = fopen('php://output', 'w');

// Write BOM for UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write report header
fputcsv($output, ['OOUTH SALARY MANAGEMENT SYSTEM'], ',', '"', '\\');
fputcsv($output, ['MONTHLY COMPARISON REPORT'], ',', '"', '\\');
fputcsv($output, [''], ',', '"', '\\');
fputcsv($output, ['Current Period:', $currentPeriodDesc], ',', '"', '\\');
fputcsv($output, ['Previous Period:', $previousPeriodDesc], ',', '"', '\\');
fputcsv($output, ['Generated On:', date('Y-m-d H:i:s')], ',', '"', '\\');
fputcsv($output, ['Generated By:', $_SESSION['SESS_MEMBER_ID'] ?? ''], ',', '"', '\\');
fputcsv($output, [''], ',', '"', '\\');

// Write summary
fputcsv($output, ['SUMMARY'], ',', '"', '\\');
fputcsv($output, ['New Employees', $reportData['summary']['new_employees'] ?? 0], ',', '"', '\\');
fputcsv($output, ['Departed Employees', $reportData['summary']['departed_employees'] ?? 0], ',', '"', '\\');
fputcsv($output, ['Status Changes', $reportData['summary']['status_changes'] ?? 0], ',', '"', '\\');
fputcsv($output, ['Promotions/Demotions', $reportData['summary']['promotions'] ?? 0], ',', '"', '\\');
fputcsv($output, ['Allowance Changes', $reportData['summary']['allowance_changes'] ?? 0], ',', '"', '\\');
fputcsv($output, [''], ',', '"', '\\');

// Write financial impact
fputcsv($output, ['ALLOWANCE IMPACT SUMMARY'], ',', '"', '\\');
fputcsv($output, ['Allowance Increases', '₦' . number_format($reportData['summary']['total_allowance_increase'] ?? 0)], ',', '"', '\\');
fputcsv($output, ['Allowance Decreases', '₦' . number_format($reportData['summary']['total_allowance_decrease'] ?? 0)], ',', '"', '\\');
fputcsv($output, ['Net Allowance Change', '₦' . number_format($reportData['summary']['net_allowance_change'] ?? 0)], ',', '"', '\\');
fputcsv($output, ['GROSS DIFFERENCE', '₦' . number_format($reportData['summary']['gross_difference'] ?? 0)], ',', '"', '\\');
fputcsv($output, [''], ',', '"', '\\');

// New Employees
if (!empty($reportData['employee_changes']['new'])) {
    fputcsv($output, ['NEW EMPLOYEES'], ',', '"', '\\');
    fputcsv($output, ['Staff ID', 'Name', 'Department', 'Grade/Step', 'Status', 'Gross Allowance'], ',', '"', '\\');
    foreach ($reportData['employee_changes']['new'] as $emp) {
        $empGross = getEmployeeGrossAllowance($emp['staff_id'], $reportData['current_period']);
        fputcsv($output, [
            $emp['staff_id'] ?? '',
            $emp['NAME'] ?? '',
            $emp['dept'] ?? '',
            ($emp['GRADE'] ?? '') . '/' . ($emp['STEP'] ?? ''),
            $emp['STATUSCD'] ?? '',
            '₦' . number_format($empGross ?? 0)
        ], ',', '"', '\\');
    }
    
    // Add new employees financial totals
    if (!empty($reportData['employee_changes']['new_totals']) && isset($reportData['employee_changes']['new_totals']['total_allowances'])) {
        fputcsv($output, [''], ',', '"', '\\');
        fputcsv($output, ['NEW EMPLOYEES ALLOWANCE TOTAL'], ',', '"', '\\');
        fputcsv($output, ['Total Allowances', '₦' . number_format($reportData['employee_changes']['new_totals']['total_allowances'])], ',', '"', '\\');
        fputcsv($output, [''], ',', '"', '\\');
    }
}

// Departed Employees
if (!empty($reportData['employee_changes']['departed'])) {
    fputcsv($output, ['DEPARTED EMPLOYEES'], ',', '"', '\\');
    fputcsv($output, ['Staff ID', 'Name', 'Department', 'Last Grade/Step', 'Last Status', 'Gross Allowance'], ',', '"', '\\');
    foreach ($reportData['employee_changes']['departed'] as $emp) {
        $empGross = getEmployeeGrossAllowance($emp['staff_id'], $reportData['previous_period']);
        fputcsv($output, [
            $emp['staff_id'] ?? '',
            $emp['NAME'] ?? '',
            $emp['dept'] ?? '',
            ($emp['GRADE'] ?? '') . '/' . ($emp['STEP'] ?? ''),
            $emp['STATUSCD'] ?? '',
            '₦' . number_format($empGross ?? 0)
        ], ',', '"', '\\');
    }
    
    // Add departed employees financial totals
    if (!empty($reportData['employee_changes']['departed_totals']['total_allowances'])) {
        fputcsv($output, [''], ',', '"', '\\');
        fputcsv($output, ['DEPARTED EMPLOYEES ALLOWANCE TOTAL'], ',', '"', '\\');
        fputcsv($output, ['Total Allowances', '₦' . number_format($reportData['employee_changes']['departed_totals']['total_allowances'])], ',', '"', '\\');
        fputcsv($output, [''], ',', '"', '\\');
    }
}

// Status Changes
if (!empty($reportData['employee_changes']['status_changed'])) {
    fputcsv($output, ['STATUS CHANGES'], ',', '"', '\\');
    fputcsv($output, ['Staff ID', 'Name', 'Department', 'Status Change'], ',', '"', '\\');
    foreach ($reportData['employee_changes']['status_changed'] as $change) {
        fputcsv($output, [
            $change['current']['staff_id'] ?? '',
            $change['current']['NAME'] ?? '',
            $change['current']['dept'] ?? '',
            $change['change'] ?? ''
        ], ',', '"', '\\');
    }
    fputcsv($output, [''], ',', '"', '\\');
}

// Promotions
if (!empty($reportData['promotions'])) {
    fputcsv($output, ['PROMOTIONS/DEMOTIONS'], ',', '"', '\\');
    fputcsv($output, ['Staff ID', 'Name', 'Department', 'Grade Change', 'Step Change'], ',', '"', '\\');
    foreach ($reportData['promotions'] as $promotion) {
        fputcsv($output, [
            $promotion['current']['staff_id'] ?? '',
            $promotion['current']['NAME'] ?? '',
            $promotion['current']['dept'] ?? '',
            $promotion['grade_change'] ?? '',
            $promotion['step_change'] ?? ''
        ], ',', '"', '\\');
    }
    fputcsv($output, [''], ',', '"', '\\');
}

// Allowance Changes - Grouped by Increases and Decreases
if (!empty($reportData['allowance_changes'])) {
    // Separate increases and decreases
    $allowanceIncreases = [];
    $allowanceDecreases = [];
    $totalIncrease = 0;
    $totalDecrease = 0;
    
    foreach ($reportData['allowance_changes'] as $change) {
        if ($change['total_difference'] > 0) {
            $allowanceIncreases[] = $change;
            $totalIncrease += $change['total_difference'];
        } else {
            $allowanceDecreases[] = $change;
            $totalDecrease += abs($change['total_difference']);
        }
    }
    
    // Allowance Increases
    if (!empty($allowanceIncreases)) {
        fputcsv($output, ['ALLOWANCE INCREASES'], ',', '"', '\\');
        fputcsv($output, ['Staff ID', 'Name', 'Previous Total', 'Current Total', 'Total Increase'], ',', '"', '\\');
        foreach ($allowanceIncreases as $change) {
            fputcsv($output, [
                $change['staff_id'] ?? '',
                $change['NAME'] ?? '',
                number_format($change['previous_total'] ?? 0),
                number_format($change['current_total'] ?? 0),
                number_format($change['total_difference'] ?? 0)
            ], ',', '"', '\\');
        }
        fputcsv($output, [''], ',', '"', '\\');
        fputcsv($output, ['TOTAL INCREASES', '₦' . number_format($totalIncrease ?? 0)], ',', '"', '\\');
        fputcsv($output, [''], ',', '"', '\\');
    }
    
    // Allowance Decreases
    if (!empty($allowanceDecreases)) {
        fputcsv($output, ['ALLOWANCE DECREASES'], ',', '"', '\\');
        fputcsv($output, ['Staff ID', 'Name', 'Previous Total', 'Current Total', 'Total Decrease'], ',', '"', '\\');
        foreach ($allowanceDecreases as $change) {
            fputcsv($output, [
                $change['staff_id'] ?? '',
                $change['NAME'] ?? '',
                number_format($change['previous_total'] ?? 0),
                number_format($change['current_total'] ?? 0),
                number_format(abs($change['total_difference'] ?? 0))
            ], ',', '"', '\\');
        }
        fputcsv($output, [''], ',', '"', '\\');
        fputcsv($output, ['TOTAL DECREASES', '₦' . number_format($totalDecrease ?? 0)], ',', '"', '\\');
        fputcsv($output, [''], ',', '"', '\\');
    }
}



fclose($output);
exit;
?>