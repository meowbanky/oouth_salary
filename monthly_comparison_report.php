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

// Get current and previous periods
$currentPeriod = isset($_GET['current_period']) ? intval($_GET['current_period']) : null;
$previousPeriod = isset($_GET['previous_period']) ? intval($_GET['previous_period']) : null;

// Fetch available periods
$periodsQuery = "SELECT * FROM payperiods WHERE enabled = 1 ORDER BY periodId DESC";
$periodsStmt = $conn->prepare($periodsQuery);
$periodsStmt->execute();
$periods = $periodsStmt->fetchAll(PDO::FETCH_ASSOC);

// If no periods selected, use the two most recent
if (!$currentPeriod || !$previousPeriod) {
    if (count($periods) >= 2) {
        $currentPeriod = $periods[0]['periodId'];
        $previousPeriod = $periods[1]['periodId'];
    }
}

$reportData = null;
if ($currentPeriod && $previousPeriod) {
    // Fetch comparison data
    $reportData = fetchComparisonData($currentPeriod, $previousPeriod);
}

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
    
    // Calculate financial impact for new and departed employees
    $data['employee_changes']['new_totals'] = getEmployeeFinancialTotals(array_keys($data['employee_changes']['new']), $currentPeriod);
    $data['employee_changes']['departed_totals'] = getEmployeeFinancialTotals(array_keys($data['employee_changes']['departed']), $previousPeriod);
    
    // Promotions/Demotions
    $data['promotions'] = getPromotionChanges($currentEmployees, $previousEmployees);
    
    // Allowance changes only
    $data['allowance_changes'] = getAllowanceChanges($currentPeriod, $previousPeriod);
    
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Monthly Comparison Report | OOUTH Salary Manager</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-100 min-h-screen">
    <?php include('header.php'); ?>
    <div class="flex min-h-screen">
        <?php include('sidebar.php'); ?>
        <main class="flex-1 px-2 md:px-8 py-4">
            <div class="w-full max-w-7xl mx-auto">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-blue-800 flex items-center gap-2">
                            <i class="fas fa-chart-line"></i> Monthly Comparison Report
                        </h1>
                        <p class="text-sm text-blue-700/70 mt-1">Compare payroll changes between two periods</p>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="exportReport()"
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold shadow transition">
                            <i class="fas fa-download mr-2"></i> Export Report
                        </button>
                    </div>
                </div>

                <!-- Period Selection -->
                <div class="bg-white rounded-xl shadow p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Select Periods to Compare</h2>
                    <form method="get" class="grid md:grid-cols-3 gap-4">
                        <div>
                            <label class="block font-semibold text-sm mb-2">Current Period</label>
                            <select name="current_period" required class="w-full px-3 py-2 border rounded">
                                <option value="">Select Current Period</option>
                                <?php foreach ($periods as $period): ?>
                                <option value="<?php echo $period['periodId']; ?>"
                                    <?php echo $currentPeriod == $period['periodId'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($period['description'] . ' ' . $period['periodYear']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-sm mb-2">Previous Period</label>
                            <select name="previous_period" required class="w-full px-3 py-2 border rounded">
                                <option value="">Select Previous Period</option>
                                <?php foreach ($periods as $period): ?>
                                <option value="<?php echo $period['periodId']; ?>"
                                    <?php echo $previousPeriod == $period['periodId'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($period['description'] . ' ' . $period['periodYear']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold shadow transition w-full">
                                <i class="fas fa-search mr-2"></i> Generate Report
                            </button>
                        </div>
                    </form>
                </div>

                <?php if ($reportData): ?>
                <!-- Summary Cards -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
                    <div class="bg-white rounded-lg shadow p-4 text-center">
                        <div class="text-2xl font-bold text-green-600">
                            <?php echo $reportData['summary']['new_employees']; ?></div>
                        <div class="text-sm text-gray-600">New Employees</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4 text-center">
                        <div class="text-2xl font-bold text-red-600">
                            <?php echo $reportData['summary']['departed_employees']; ?></div>
                        <div class="text-sm text-gray-600">Departed Employees</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4 text-center">
                        <div class="text-2xl font-bold text-blue-600">
                            <?php echo $reportData['summary']['promotions']; ?></div>
                        <div class="text-sm text-gray-600">Promotions</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4 text-center">
                        <div class="text-2xl font-bold text-yellow-600">
                            <?php echo $reportData['summary']['status_changes']; ?></div>
                        <div class="text-sm text-gray-600">Status Changes</div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4 text-center">
                        <div class="text-2xl font-bold text-purple-600">
                            <?php echo $reportData['summary']['allowance_changes']; ?></div>
                        <div class="text-sm text-gray-600">Allowance Changes</div>
                    </div>
                </div>

                <!-- Financial Impact Summary -->
                <div class="bg-white rounded-xl shadow p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-calculator"></i> Allowance Impact Summary
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-green-50 rounded-lg p-4 text-center">
                            <div class="text-lg font-bold text-green-700">
                                ₦<?php echo number_format($reportData['summary']['total_allowance_increase']); ?></div>
                            <div class="text-sm text-green-600">Allowance Increases</div>
                        </div>
                        <div class="bg-red-50 rounded-lg p-4 text-center">
                            <div class="text-lg font-bold text-red-700">
                                ₦<?php echo number_format($reportData['summary']['total_allowance_decrease']); ?></div>
                            <div class="text-sm text-red-600">Allowance Decreases</div>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-purple-50 rounded-lg p-4 text-center">
                            <div
                                class="text-lg font-bold <?php echo $reportData['summary']['net_allowance_change'] >= 0 ? 'text-green-700' : 'text-red-700'; ?>">
                                ₦<?php echo number_format($reportData['summary']['net_allowance_change']); ?>
                            </div>
                            <div class="text-sm text-purple-600">Net Allowance Change</div>
                        </div>
                        <div
                            class="bg-indigo-50 rounded-lg p-4 text-center border-2 <?php echo $reportData['summary']['gross_difference'] >= 0 ? 'border-green-300' : 'border-red-300'; ?>">
                            <div
                                class="text-xl font-bold <?php echo $reportData['summary']['gross_difference'] >= 0 ? 'text-green-700' : 'text-red-700'; ?>">
                                ₦<?php echo number_format($reportData['summary']['gross_difference']); ?>
                            </div>
                            <div class="text-sm font-semibold text-indigo-600">GROSS DIFFERENCE</div>
                        </div>
                    </div>
                </div>

                <!-- Report Sections -->
                <div class="space-y-6">
                    <!-- New Employees -->
                    <?php if (!empty($reportData['employee_changes']['new'])): ?>
                    <div class="bg-white rounded-xl shadow">
                        <div class="px-6 py-4 border-b bg-green-50">
                            <h3 class="text-lg font-semibold text-green-800 flex items-center gap-2">
                                <i class="fas fa-user-plus"></i> New Employees
                                (<?php echo count($reportData['employee_changes']['new']); ?>)
                            </h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="py-2 px-4 text-left">Staff ID</th>
                                        <th class="py-2 px-4 text-left">Name</th>
                                        <th class="py-2 px-4 text-left">Department</th>
                                        <th class="py-2 px-4 text-left">Grade/Step</th>
                                        <th class="py-2 px-4 text-left">Status</th>
                                        <th class="py-2 px-4 text-left">Gross Allowance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reportData['employee_changes']['new'] as $emp): ?>
                                    <?php 
                                                // Get individual gross allowance for this employee
                                                $empGross = getEmployeeGrossAllowance($emp['staff_id'], $reportData['current_period']);
                                                ?>
                                    <tr class="border-b hover:bg-green-50">
                                        <td class="py-2 px-4 font-mono">
                                            <?php echo htmlspecialchars($emp['staff_id']); ?></td>
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($emp['NAME']); ?></td>
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($emp['dept'] ?? ''); ?></td>
                                        <td class="py-2 px-4">
                                            <?php echo htmlspecialchars($emp['GRADE'] . '/' . $emp['STEP']); ?></td>
                                        <td class="py-2 px-4">
                                            <span
                                                class="px-2 py-1 rounded text-xs font-bold bg-green-100 text-green-800">
                                                <?php echo htmlspecialchars($emp['STATUSCD']); ?>
                                            </span>
                                        </td>
                                        <td class="py-2 px-4 font-semibold text-green-700">
                                            ₦<?php echo number_format($empGross); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="bg-green-100">
                                    <tr class="font-semibold">
                                        <td colspan="4" class="py-3 px-4 text-right">New Employees Allowance Total:</td>
                                        <td class="py-3 px-4"></td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm font-bold text-green-700">
                                                ₦<?php echo number_format($reportData['employee_changes']['new_totals']['total_allowances']); ?>
                                            </div>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Departed Employees -->
                    <?php if (!empty($reportData['employee_changes']['departed'])): ?>
                    <div class="bg-white rounded-xl shadow">
                        <div class="px-6 py-4 border-b bg-red-50">
                            <h3 class="text-lg font-semibold text-red-800 flex items-center gap-2">
                                <i class="fas fa-user-minus"></i> Departed Employees
                                (<?php echo count($reportData['employee_changes']['departed']); ?>)
                            </h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="py-2 px-4 text-left">Staff ID</th>
                                        <th class="py-2 px-4 text-left">Name</th>
                                        <th class="py-2 px-4 text-left">Department</th>
                                        <th class="py-2 px-4 text-left">Last Grade/Step</th>
                                        <th class="py-2 px-4 text-left">Last Status</th>
                                        <th class="py-2 px-4 text-left">Gross Allowance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reportData['employee_changes']['departed'] as $emp): ?>
                                    <?php 
                                                // Get individual gross allowance for this employee
                                                $empGross = getEmployeeGrossAllowance($emp['staff_id'], $reportData['previous_period']);
                                                ?>
                                    <tr class="border-b hover:bg-red-50">
                                        <td class="py-2 px-4 font-mono">
                                            <?php echo htmlspecialchars($emp['staff_id']); ?></td>
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($emp['NAME']); ?></td>
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($emp['dept'] ?? ''); ?></td>
                                        <td class="py-2 px-4">
                                            <?php echo htmlspecialchars($emp['GRADE'] . '/' . $emp['STEP']); ?></td>
                                        <td class="py-2 px-4">
                                            <span class="px-2 py-1 rounded text-xs font-bold bg-red-100 text-red-800">
                                                <?php echo htmlspecialchars($emp['STATUSCD']); ?>
                                            </span>
                                        </td>
                                        <td class="py-2 px-4 font-semibold text-green-700">
                                            ₦<?php echo number_format($empGross); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="bg-red-100">
                                    <tr class="font-semibold">
                                        <td colspan="4" class="py-3 px-4 text-right">Departed Employees Allowance Total:
                                        </td>
                                        <td class="py-3 px-4"></td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm font-bold text-green-700">
                                                ₦<?php echo number_format($reportData['employee_changes']['departed_totals']['total_allowances']); ?>
                                            </div>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Promotions -->
                    <?php if (!empty($reportData['promotions'])): ?>
                    <div class="bg-white rounded-xl shadow">
                        <div class="px-6 py-4 border-b bg-blue-50">
                            <h3 class="text-lg font-semibold text-blue-800 flex items-center gap-2">
                                <i class="fas fa-arrow-up"></i> Promotions/Demotions
                                (<?php echo count($reportData['promotions']); ?>)
                            </h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="py-2 px-4 text-left">Staff ID</th>
                                        <th class="py-2 px-4 text-left">Name</th>
                                        <th class="py-2 px-4 text-left">Department</th>
                                        <th class="py-2 px-4 text-left">Grade Change</th>
                                        <th class="py-2 px-4 text-left">Step Change</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reportData['promotions'] as $promotion): ?>
                                    <tr class="border-b hover:bg-blue-50">
                                        <td class="py-2 px-4 font-mono">
                                            <?php echo htmlspecialchars($promotion['current']['staff_id']); ?></td>
                                        <td class="py-2 px-4">
                                            <?php echo htmlspecialchars($promotion['current']['NAME']); ?></td>
                                        <td class="py-2 px-4">
                                            <?php echo htmlspecialchars($promotion['current']['dept'] ?? ''); ?></td>
                                        <td class="py-2 px-4">
                                            <?php if ($promotion['grade_change']): ?>
                                            <span class="px-2 py-1 rounded text-xs font-bold bg-blue-100 text-blue-800">
                                                <?php echo htmlspecialchars($promotion['grade_change']); ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="text-gray-400">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-2 px-4">
                                            <?php if ($promotion['step_change']): ?>
                                            <span
                                                class="px-2 py-1 rounded text-xs font-bold bg-green-100 text-green-800">
                                                <?php echo htmlspecialchars($promotion['step_change']); ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="text-gray-400">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Allowance Changes -->
                    <?php if (!empty($reportData['allowance_changes'])): ?>
                    <?php 
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
                            ?>

                    <!-- Allowance Increases -->
                    <?php if (!empty($allowanceIncreases)): ?>
                    <div class="bg-white rounded-xl shadow">
                        <div class="px-6 py-4 border-b bg-green-50">
                            <h3 class="text-lg font-semibold text-green-800 flex items-center gap-2">
                                <i class="fas fa-arrow-up"></i> Allowance Increases
                                (<?php echo count($allowanceIncreases); ?>)
                            </h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="py-2 px-4 text-left">Staff ID</th>
                                        <th class="py-2 px-4 text-left">Name</th>
                                        <th class="py-2 px-4 text-left">Previous Total</th>
                                        <th class="py-2 px-4 text-left">Current Total</th>
                                        <th class="py-2 px-4 text-left">Total Increase</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allowanceIncreases as $change): ?>
                                    <tr class="border-b hover:bg-green-50">
                                        <td class="py-2 px-4 font-mono">
                                            <?php echo htmlspecialchars($change['staff_id']); ?></td>
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($change['NAME']); ?></td>
                                        <td class="py-2 px-4">₦<?php echo number_format($change['previous_total']); ?>
                                        </td>
                                        <td class="py-2 px-4">₦<?php echo number_format($change['current_total']); ?>
                                        </td>
                                        <td class="py-2 px-4">
                                            <span
                                                class="px-2 py-1 rounded text-xs font-bold bg-green-100 text-green-800">
                                                +₦<?php echo number_format($change['total_difference']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="bg-green-100">
                                    <tr class="font-semibold">
                                        <td colspan="4" class="py-3 px-4 text-right">Total Increases:</td>
                                        <td class="py-3 px-4">
                                            <span
                                                class="px-2 py-1 rounded text-sm font-bold bg-green-200 text-green-900">
                                                +₦<?php echo number_format($totalIncrease); ?>
                                            </span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Allowance Decreases -->
                    <?php if (!empty($allowanceDecreases)): ?>
                    <div class="bg-white rounded-xl shadow">
                        <div class="px-6 py-4 border-b bg-red-50">
                            <h3 class="text-lg font-semibold text-red-800 flex items-center gap-2">
                                <i class="fas fa-arrow-down"></i> Allowance Decreases
                                (<?php echo count($allowanceDecreases); ?>)
                            </h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="py-2 px-4 text-left">Staff ID</th>
                                        <th class="py-2 px-4 text-left">Name</th>
                                        <th class="py-2 px-4 text-left">Previous Total</th>
                                        <th class="py-2 px-4 text-left">Current Total</th>
                                        <th class="py-2 px-4 text-left">Total Decrease</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allowanceDecreases as $change): ?>
                                    <tr class="border-b hover:bg-red-50">
                                        <td class="py-2 px-4 font-mono">
                                            <?php echo htmlspecialchars($change['staff_id']); ?></td>
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($change['NAME']); ?></td>
                                        <td class="py-2 px-4">₦<?php echo number_format($change['previous_total']); ?>
                                        </td>
                                        <td class="py-2 px-4">₦<?php echo number_format($change['current_total']); ?>
                                        </td>
                                        <td class="py-2 px-4">
                                            <span class="px-2 py-1 rounded text-xs font-bold bg-red-100 text-red-800">
                                                -₦<?php echo number_format(abs($change['total_difference'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="bg-red-100">
                                    <tr class="font-semibold">
                                        <td colspan="4" class="py-3 px-4 text-right">Total Decreases:</td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-1 rounded text-sm font-bold bg-red-200 text-red-900">
                                                -₦<?php echo number_format($totalDecrease); ?>
                                            </span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>


                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
    function exportReport() {
        if (!<?php echo $reportData ? 'true' : 'false'; ?>) {
            Swal.fire({
                icon: 'warning',
                title: 'No Data',
                text: 'Please generate a report first before exporting.'
            });
            return;
        }

        // Get current period values from the form
        const currentPeriod = document.querySelector('select[name="current_period"]').value;
        const previousPeriod = document.querySelector('select[name="previous_period"]').value;

        if (!currentPeriod || !previousPeriod) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Periods',
                text: 'Please select both current and previous periods before exporting.'
            });
            return;
        }

        // Create export URL with period parameters
        const exportUrl =
            `monthly_comparison_export.php?current_period=${currentPeriod}&previous_period=${previousPeriod}`;

        // Show loading message
        Swal.fire({
            title: 'Exporting...',
            text: 'Please wait while we prepare your report.',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });

        // Open export in new window
        const exportWindow = window.open(exportUrl, '_blank');

        // Check if window opened successfully
        if (exportWindow) {
            setTimeout(() => {
                Swal.close();
            }, 1000);
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Export Failed',
                text: 'Unable to open export window. Please check your popup blocker settings.'
            });
        }
    }
    </script>
</body>

</html>