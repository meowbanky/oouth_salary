<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'Unauthorized access';
    exit;
}

// Get parameters
$staff_id = $_GET['staff_id'] ?? '';
$period = $_GET['period'] ?? '';

if (empty($staff_id) || empty($period)) {
    echo 'Missing required parameters';
    exit;
}

try {
    // Fetch employee data
    $stmt = $pdo->prepare("
        SELECT ms.*, d.dept_name as dept, b.BNAME, b.ACCTNO 
        FROM master_staff ms 
        LEFT JOIN tbl_dept d ON ms.dept_id = d.dept_id 
        LEFT JOIN tbl_bank b ON ms.bank_id = b.bank_id 
        WHERE ms.staff_id = ?
    ");
    $stmt->execute([$staff_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        echo 'Employee not found';
        exit;
    }

    // Fetch period data
    $stmt = $pdo->prepare("SELECT * FROM payperiods WHERE period_id = ?");
    $stmt->execute([$period]);
    $periodData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$periodData) {
        echo 'Period not found';
        exit;
    }

    // Fetch consolidated salary
    $stmt = $pdo->prepare("SELECT allow FROM tbl_master WHERE staff_id = ? AND allow_id = 1 AND period_id = ?");
    $stmt->execute([$staff_id, $period]);
    $consolidatedResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $consolidated = $consolidatedResult ? $consolidatedResult['allow'] : 0;

    // Fetch allowances and deductions
    $stmt = $pdo->prepare("
        SELECT ed, allow, deduc, type 
        FROM tbl_master 
        WHERE staff_id = ? AND period_id = ? AND ((type = 1 AND allow_id != 1 AND allow > 0) OR (type = 2 AND deduc > 0))
    ");
    $stmt->execute([$staff_id, $period]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $allowances = array_filter($items, function($item) { return $item['type'] == 1; });
    $deductions = array_filter($items, function($item) { return $item['type'] == 2; });

    $totalAllowances = array_sum(array_column($allowances, 'allow'));
    $totalDeductions = array_sum(array_column($deductions, 'deduc'));
    $grossSalary = $consolidated + $totalAllowances;
    $netPay = $grossSalary - $totalDeductions;

    $fullPeriod = $periodData['month'] . ' ' . $periodData['year'];

} catch (Exception $e) {
    echo 'Error loading payslip data: ' . $e->getMessage();
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payslip - <?php echo htmlspecialchars($staff_id); ?></title>
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
    </div>

    <div class="payslip-container">
        <!-- Payslip Header -->
        <div class="payslip-header">
            <div class="company-title">OOUTH, SAGAMU</div>
            <div class="company-subtitle">Salary Management System</div>
            <div class="period-info">
                <div>
                    <strong>Period:</strong><br>
                    <?php echo htmlspecialchars($fullPeriod); ?>
                </div>
                <div>
                    <strong>Date:</strong><br>
                    <?php echo date('M j, Y'); ?>
                </div>
                <div>
                    <strong>No:</strong><br>
                    <?php echo 'PS-' . $staff_id . '-' . date('Ymd'); ?>
                </div>
            </div>
        </div>

        <!-- Employee Information -->
        <div class="employee-info">
            <div class="info-box">
                <div class="info-title">
                    <i class="fas fa-user mr-2" style="color: #3b82f6;"></i>Employee Information
                </div>
                <div class="info-item">
                    <span class="label">Staff ID:</span>
                    <span class="value"><?php echo htmlspecialchars($employee['staff_id']); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Name:</span>
                    <span class="value"><?php echo htmlspecialchars($employee['NAME']); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Department:</span>
                    <span class="value"><?php echo htmlspecialchars($employee['dept']); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Grade/Step:</span>
                    <span class="value"><?php echo htmlspecialchars($employee['GRADE']); ?>/<?php echo htmlspecialchars($employee['STEP']); ?></span>
                </div>
            </div>

            <div class="info-box">
                <div class="info-title">
                    <i class="fas fa-university mr-2" style="color: #10b981;"></i>Banking Information
                </div>
                <div class="info-item">
                    <span class="label">Bank:</span>
                    <span class="value"><?php echo htmlspecialchars($employee['BNAME']); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Account No:</span>
                    <span class="value"><?php echo htmlspecialchars($employee['ACCTNO']); ?></span>
                </div>
            </div>
        </div>

        <!-- Consolidated Salary -->
        <div class="consolidated-salary">
            <div class="info-title">
                <i class="fas fa-money-bill-wave mr-2" style="color: #3b82f6;"></i>Consolidated Salary
            </div>
            <div style="font-size: 24px; font-weight: bold; color: #1e40af;">
                ₦<?php echo number_format($consolidated, 2); ?>
            </div>
        </div>

        <!-- Earnings and Deductions -->
        <div class="earnings-deductions">
            <!-- Allowances -->
            <div class="earnings-box">
                <div class="box-title">
                    <i class="fas fa-plus-circle mr-2" style="color: #16a34a;"></i>Allowances
                </div>
                <?php if (empty($allowances)): ?>
                    <div style="color: #6b7280; font-style: italic;">No allowances recorded</div>
                <?php else: ?>
                    <?php foreach ($allowances as $allowance): ?>
                        <div class="item-row">
                            <span><?php echo htmlspecialchars($allowance['ed']); ?></span>
                            <span style="color: #16a34a; font-weight: bold;">₦<?php echo number_format($allowance['allow'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div class="total-row">
                    <span>Total Allowances</span>
                    <span style="color: #16a34a;">₦<?php echo number_format($totalAllowances, 2); ?></span>
                </div>
            </div>

            <!-- Deductions -->
            <div class="deductions-box">
                <div class="box-title">
                    <i class="fas fa-minus-circle mr-2" style="color: #dc2626;"></i>Deductions
                </div>
                <?php if (empty($deductions)): ?>
                    <div style="color: #6b7280; font-style: italic;">No deductions recorded</div>
                <?php else: ?>
                    <?php foreach ($deductions as $deduction): ?>
                        <div class="item-row">
                            <span><?php echo htmlspecialchars($deduction['ed']); ?></span>
                            <span style="color: #dc2626; font-weight: bold;">₦<?php echo number_format($deduction['deduc'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div class="total-row">
                    <span>Total Deductions</span>
                    <span style="color: #dc2626;">₦<?php echo number_format($totalDeductions, 2); ?></span>
                </div>
            </div>
        </div>

        <!-- Pay Summary -->
        <div class="summary-section">
            <div class="info-title" style="text-align: center; margin-bottom: 20px;">
                <i class="fas fa-calculator mr-2" style="color: #3b82f6;"></i>Pay Summary
            </div>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-amount" style="color: #16a34a;">₦<?php echo number_format($grossSalary, 2); ?></div>
                    <div class="summary-label">Gross Salary</div>
                </div>
                <div class="summary-item">
                    <div class="summary-amount" style="color: #dc2626;">₦<?php echo number_format($totalDeductions, 2); ?></div>
                    <div class="summary-label">Total Deductions</div>
                </div>
                <div class="summary-item">
                    <div class="summary-amount" style="color: #1e40af;">₦<?php echo number_format($netPay, 2); ?></div>
                    <div class="summary-label">Net Pay</div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This is a computer-generated payslip and does not require a signature.</p>
            <p>For any queries regarding this payslip, please contact the HR department.</p>
            <p>Generated on: <?php echo date('F j, Y \a\t g:i A'); ?></p>
        </div>
    </div>
</body>
</html> 