<?php
ini_set('max_execution_time', 300);
require_once('../Connections/paymaster.php');
include_once('../classes/model.php');

// Check if this is a print request
$print = isset($_GET['print']) && $_GET['print'] == '1';

// Check if required parameters are provided
if ((!isset($_POST['staff_id']) && !isset($_GET['staff_id'])) || (!isset($_POST['period']) && !isset($_GET['period']))) {
    echo '<div class="text-center py-8">
            <div class="text-red-500 text-6xl mb-4">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Missing Parameters</h3>
            <p class="text-gray-600">Staff ID and period are required to generate payslip.</p>
          </div>';
    exit();
}

$staff_id = $_POST['staff_id'] ?? $_GET['staff_id'] ?? '';
$period = $_POST['period'] ?? $_GET['period'] ?? '';

// If this is a print request, output full HTML page
if ($print) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Payslip - ' . htmlspecialchars($staff_id) . '</title>
        <meta charset="UTF-8">
        <style>
            @import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css");
            * { box-sizing: border-box; }
            body { 
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; 
                margin: 0; 
                padding: 20px; 
                background: white;
                color: #333;
            }
            .payslip-container {
                background: white;
                max-width: 800px;
                margin: 0 auto;
                padding: 30px;
                border: 1px solid #ddd;
            }
            .payslip-header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 3px solid #2563eb;
                padding-bottom: 20px;
            }
            .company-title {
                font-size: 24px;
                font-weight: bold;
                color: #1e40af;
                margin-bottom: 5px;
            }
            .company-subtitle {
                font-size: 14px;
                color: #6b7280;
            }
            .period-info {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 20px;
                margin-top: 20px;
                font-size: 12px;
            }
            .employee-info {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
                margin-bottom: 30px;
            }
            .info-box {
                background: #f8fafc;
                padding: 20px;
                border-radius: 8px;
            }
            .info-title {
                font-size: 16px;
                font-weight: bold;
                color: #1f2937;
                margin-bottom: 15px;
                display: flex;
                align-items: center;
            }
            .info-item {
                display: flex;
                justify-content: space-between;
                margin-bottom: 8px;
                font-size: 14px;
            }
            .consolidated-salary {
                background: #eff6ff;
                padding: 20px;
                border-radius: 8px;
                margin-bottom: 30px;
                text-align: center;
            }
            .earnings-deductions {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
                margin-bottom: 30px;
            }
            .earnings-box, .deductions-box {
                padding: 20px;
                border-radius: 8px;
            }
            .earnings-box {
                background: #f0fdf4;
                border-left: 4px solid #16a34a;
            }
            .deductions-box {
                background: #fef2f2;
                border-left: 4px solid #dc2626;
            }
            .box-title {
                font-size: 18px;
                font-weight: bold;
                margin-bottom: 15px;
                display: flex;
                align-items: center;
            }
            .item-row {
                display: flex;
                justify-content: space-between;
                margin-bottom: 8px;
                font-size: 14px;
            }
            .total-row {
                border-top: 2px solid #d1d5db;
                padding-top: 10px;
                margin-top: 10px;
                font-weight: bold;
                font-size: 16px;
            }
            .summary-section {
                background: #eff6ff;
                padding: 30px;
                border-radius: 8px;
                margin-bottom: 30px;
            }
            .summary-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 20px;
                text-align: center;
            }
            .summary-item {
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .summary-amount {
                font-size: 24px;
                font-weight: bold;
                margin-bottom: 5px;
            }
            .summary-label {
                font-size: 12px;
                color: #6b7280;
            }
            .footer {
                text-align: center;
                font-size: 12px;
                color: #6b7280;
                border-top: 2px solid #e5e7eb;
                padding-top: 20px;
            }
            @media print {
                body { margin: 0; padding: 0; }
                .payslip-container { 
                    max-width: none; 
                    padding: 20px; 
                    border: none; 
                }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="no-print" style="text-align: center; margin-bottom: 20px;">
            <button onclick="window.print()" style="padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer;">
                <i class="fas fa-print"></i> Print Payslip
            </button>
            <button onclick="window.close()" style="padding: 10px 20px; background: #6b7280; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
                <i class="fas fa-times"></i> Close
            </button>
        </div>';
}

try {
    // Get period details from payperiods table
    $query = $conn->prepare('SELECT description, periodYear, periodId FROM payperiods WHERE periodId = ?');
    $query->execute(array($period));
    $periodData = $query->fetch(PDO::FETCH_ASSOC);
    
    if (!$periodData) {
        echo '<div class="text-center py-8">
                <div class="text-red-500 text-6xl mb-4">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Period Not Found</h3>
                <p class="text-gray-600">The specified payroll period was not found.</p>
              </div>';
        exit();
    }
    
    $fullPeriod = $periodData['description'] . '-' . $periodData['periodYear'];

    // Get employee details from master_staff table
    $query = $conn->prepare('SELECT 
        master_staff.staff_id,
        master_staff.`NAME`,
        master_staff.GRADE,
        master_staff.STEP,
        master_staff.ACCTNO,
        tbl_dept.dept,
        tbl_bank.BNAME
        FROM master_staff 
        LEFT JOIN tbl_dept ON tbl_dept.dept_id = master_staff.DEPTCD 
        LEFT JOIN tbl_bank ON tbl_bank.BCODE = master_staff.BCODE 
        WHERE staff_id = ? AND period = ?');
    $query->execute(array($staff_id, $period));
    $employee = $query->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        echo '<div class="text-center py-8">
                <div class="text-red-500 text-6xl mb-4">
                    <i class="fas fa-user-slash"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Employee Not Found</h3>
                <p class="text-gray-600">No employee found with the provided Staff ID for this period.</p>
              </div>';
        exit();
    }

    // Get consolidated salary (allow_id = 1)
    $consolidated = 0;
    $query = $conn->prepare('SELECT allow FROM tbl_master WHERE allow_id = ? AND staff_id = ? AND period = ?');
    $query->execute(array('1', $staff_id, $period));
    $consolidatedData = $query->fetch(PDO::FETCH_ASSOC);
    if ($consolidatedData) {
        $consolidated = $consolidatedData['allow'];
    }

    // Get allowances (type = 1, exclude allow_id = 1 which is consolidated)
    $query = $conn->prepare('SELECT 
        tbl_master.allow,
        tbl_master.allow_id,
        tbl_earning_deduction.ed,
        tbl_earning_deduction.edDesc
        FROM tbl_master 
        INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id 
        WHERE allow_id <> ? AND staff_id = ? AND period = ? AND type = ? 
        ORDER BY allow_id ASC');
    $query->execute(array('1', $staff_id, $period, '1'));
    $allowances = $query->fetchAll(PDO::FETCH_ASSOC);

    // Get deductions (type = 2)
    $query = $conn->prepare('SELECT 
        tbl_master.deduc,
        tbl_master.allow_id,
        tbl_earning_deduction.ed,
        tbl_earning_deduction.edDesc
        FROM tbl_master 
        INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id 
        WHERE staff_id = ? AND period = ? AND type = ? 
        ORDER BY allow_id ASC');
    $query->execute(array($staff_id, $period, '2'));
    $deductions = $query->fetchAll(PDO::FETCH_ASSOC);

    // Calculate totals
    $totalAllowances = 0;
    foreach ($allowances as $allowance) {
        $totalAllowances += floatval($allowance['allow']);
    }
    
    $totalDeductions = 0;
    foreach ($deductions as $deduction) {
        $totalDeductions += floatval($deduction['deduc']);
    }
    
    $grossSalary = $consolidated + $totalAllowances;
    $netPay = $grossSalary - $totalDeductions;

    // Generate payslip HTML
    ?>
    <div class="payslip-container bg-white rounded-lg shadow-lg p-3 max-w-full mx-auto">
        <!-- Payslip Header -->
        <div class="payslip-header text-center border-b border-gray-300 pb-2 mb-3">
            <div class="flex items-center justify-center mb-2">
                <div class="bg-blue-600 text-white p-1 rounded-full mr-2">
                    <i class="fas fa-building text-lg"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-800">OOUTH, SAGAMU</h1>
                    <p class="text-gray-600 text-xs">Salary Management System</p>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-2 text-xs">
                <div>
                    <span class="font-semibold">Period:</span><br>
                    <?php echo htmlspecialchars($fullPeriod); ?>
                </div>
                <div>
                    <span class="font-semibold">Date:</span><br>
                    <?php echo date('M j, Y'); ?>
                </div>
                <div>
                    <span class="font-semibold">No:</span><br>
                    <?php echo 'PS-' . $staff_id . '-' . date('Ymd'); ?>
                </div>
            </div>
        </div>

        <!-- Employee Information -->
        <div class="employee-info grid grid-cols-2 gap-3 mb-3">
            <div class="bg-gray-50 p-2 rounded">
                <h3 class="text-sm font-semibold text-gray-800 mb-1 flex items-center">
                    <i class="fas fa-user mr-1 text-blue-600"></i>Employee
                </h3>
                <div class="space-y-0.5 text-xs">
                    <div class="flex justify-between">
                        <span class="font-medium">ID:</span>
                        <span><?php echo htmlspecialchars($employee['staff_id']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium">Name:</span>
                        <span><?php echo htmlspecialchars($employee['NAME']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium">Dept:</span>
                        <span><?php echo htmlspecialchars($employee['dept']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium">Grade:</span>
                        <span><?php echo htmlspecialchars($employee['GRADE']); ?>/<?php echo htmlspecialchars($employee['STEP']); ?></span>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 p-2 rounded">
                <h3 class="text-sm font-semibold text-gray-800 mb-1 flex items-center">
                    <i class="fas fa-university mr-1 text-green-600"></i>Banking
                </h3>
                <div class="space-y-0.5 text-xs">
                    <div class="flex justify-between">
                        <span class="font-medium">Bank:</span>
                        <span><?php echo htmlspecialchars($employee['BNAME']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium">Account:</span>
                        <span><?php echo htmlspecialchars($employee['ACCTNO']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Consolidated Salary Section -->
        <div class="bg-blue-50 p-2 rounded mb-3">
            <h3 class="text-sm font-semibold text-blue-800 mb-1 flex items-center">
                <i class="fas fa-money-bill-wave mr-1"></i>Consolidated Salary
            </h3>
            <div class="flex justify-between items-center">
                <span class="text-xs">Consolidated Salary:</span>
                <span class="font-bold text-blue-700 text-sm">₦<?php echo number_format($consolidated, 2); ?></span>
            </div>
        </div>

        <!-- Earnings and Deductions -->
        <div class="earnings-deductions grid grid-cols-2 gap-3 mb-3">
            <!-- Allowances Section -->
            <div class="bg-green-50 p-2 rounded">
                <h3 class="text-sm font-semibold text-green-800 mb-1 flex items-center">
                    <i class="fas fa-plus-circle mr-1"></i>Allowances
                </h3>
                <div class="space-y-1">
                    <?php if (empty($allowances)): ?>
                        <p class="text-gray-500 text-xs italic">No allowances</p>
                    <?php else: ?>
                        <?php foreach ($allowances as $allowance): ?>
                            <div class="flex justify-between items-center py-0.5 border-b border-green-200">
                                <span class="text-xs"><?php echo htmlspecialchars($allowance['ed']); ?></span>
                                <span class="font-semibold text-green-700 text-xs">₦<?php echo number_format($allowance['allow'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <div class="flex justify-between items-center pt-1 border-t border-green-300 font-bold">
                        <span class="text-xs">Total</span>
                        <span class="text-green-800 text-xs">₦<?php echo number_format($totalAllowances, 2); ?></span>
                    </div>
                </div>
            </div>

            <!-- Deductions Section -->
            <div class="bg-red-50 p-2 rounded">
                <h3 class="text-sm font-semibold text-red-800 mb-1 flex items-center">
                    <i class="fas fa-minus-circle mr-1"></i>Deductions
                </h3>
                <div class="space-y-1">
                    <?php if (empty($deductions)): ?>
                        <p class="text-gray-500 text-xs italic">No deductions</p>
                    <?php else: ?>
                        <?php foreach ($deductions as $deduction): ?>
                            <div class="flex justify-between items-center py-0.5 border-b border-red-200">
                                <span class="text-xs"><?php echo htmlspecialchars($deduction['ed']); ?></span>
                                <span class="font-semibold text-red-700 text-xs">₦<?php echo number_format($deduction['deduc'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <div class="flex justify-between items-center pt-1 border-t border-red-300 font-bold">
                        <span class="text-xs">Total</span>
                        <span class="text-red-800 text-xs">₦<?php echo number_format($totalDeductions, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Net Pay Summary -->
        <div class="net-pay-summary bg-blue-50 p-3 rounded mb-3">
            <h3 class="text-sm font-bold text-blue-800 mb-2 text-center">Pay Summary</h3>
            <div class="grid grid-cols-3 gap-2 text-center">
                <div class="bg-white p-2 rounded shadow-sm">
                    <div class="text-lg font-bold text-green-600">₦<?php echo number_format($grossSalary, 2); ?></div>
                    <div class="text-xs text-gray-600">Gross</div>
                </div>
                <div class="bg-white p-2 rounded shadow-sm">
                    <div class="text-lg font-bold text-red-600">₦<?php echo number_format($totalDeductions, 2); ?></div>
                    <div class="text-xs text-gray-600">Deductions</div>
                </div>
                <div class="bg-white p-2 rounded shadow-sm">
                    <div class="text-lg font-bold text-blue-600">₦<?php echo number_format($netPay, 2); ?></div>
                    <div class="text-xs text-gray-600">Net Pay</div>
                </div>
            </div>
        </div>

        <!-- Additional Information -->
        <div class="additional-info bg-gray-50 p-2 rounded mb-3">
            <h3 class="text-sm font-semibold text-gray-800 mb-1 flex items-center">
                <i class="fas fa-info-circle mr-1 text-blue-600"></i>Additional Info
            </h3>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div>
                    <span class="font-medium">Payment:</span> Bank Transfer
                </div>
                <div>
                    <span class="font-medium">Date:</span> <?php echo date('M j, Y', strtotime('last day of this month')); ?>
                </div>
                <div>
                    <span class="font-medium">Currency:</span> NGN (₦)
                </div>
                <div>
                    <span class="font-medium">Generated:</span> <?php echo date('M j, g:i A'); ?>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="payslip-footer text-center text-xs text-gray-600 border-t border-gray-300 pt-2">
            <p class="mb-1">Computer-generated payslip - no signature required.</p>
            <p>Contact HR/Finance for queries.</p>
            <div class="mt-2 flex justify-center space-x-2">
                <span class="px-2 py-0.5 bg-gray-200 rounded text-xs">HR</span>
                <span class="px-2 py-0.5 bg-gray-200 rounded text-xs">Finance</span>
            </div>
        </div>
    </div>

    <style>
        @media print {
            .payslip-container {
                box-shadow: none;
                border: 1px solid #ccc;
            }
            
            .bg-gray-50, .bg-green-50, .bg-red-50, .bg-blue-50 {
                background-color: #f9fafb !important;
            }
            
            .text-green-600, .text-green-700, .text-green-800 {
                color: #059669 !important;
            }
            
            .text-red-600, .text-red-700, .text-red-800 {
                color: #dc2626 !important;
            }
            
            .text-blue-600, .text-blue-700, .text-blue-800 {
                color: #2563eb !important;
            }
        }
    </style>
    <?php

} catch (PDOException $e) {
    echo '<div class="text-center py-8">
            <div class="text-red-500 text-6xl mb-4">
                <i class="fas fa-database"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Database Error</h3>
            <p class="text-gray-600">Unable to retrieve payslip data. Please try again later.</p>
            <p class="text-xs text-gray-500 mt-2">Error: ' . htmlspecialchars($e->getMessage()) . '</p>
          </div>';
} catch (Exception $e) {
    echo '<div class="text-center py-8">
            <div class="text-red-500 text-6xl mb-4">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">System Error</h3>
            <p class="text-gray-600">An unexpected error occurred while generating the payslip.</p>
          </div>';
}
?> 