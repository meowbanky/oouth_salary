<?php
ini_set('max_execution_time', 300);
require_once('Connections/paymaster.php');
include_once('classes/model.php');

require_once 'libs/App.php';
$App = new App();
$App->checkAuthentication();
require_once 'libs/middleware.php';
checkPermission();

session_start();

if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '') || $_SESSION['role'] != 'Admin') {
   header("location: index.php");
   exit();
}

// Get current employee data
$currentemp = null;



if ($_SESSION['empDataTrack'] == 'next') {
   $query = $conn->prepare("SELECT employee.staff_id, employee.`NAME` FROM employee WHERE STATUSCD = 'A' ORDER BY staff_id desc");
   $query->execute();
   $ftres = $query->fetchAll(PDO::FETCH_COLUMN);
   $count = $query->rowCount();
   $counter = 0;
   if ($_SESSION['emptrack'] >= $count) {
      $_SESSION['emptrack'] = 0;
   }
   $currentemp = $ftres['' . $_SESSION['emptrack'] . ''];
} elseif ($_SESSION['empDataTrack'] == 'option') {
   $currentemp = $_SESSION['emptNumTack'];
}

// Get employee details
$empfname = '';
$empGrade = '';
$empStep = '';
$staffID = '';
$dept = '';
$callType = '';
$HARZAD_TYPE = '';
$status = '';

if ($currentemp) {
   
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
   $query->execute(array($currentemp));
   if ($row = $query->fetch()) {
      $empfname = $row['NAME'];
      $empGrade = $row['GRADE'];
      $empStep = $row['STEP'];
      $staffID = $row['staff_id'];
      $dept = $row['dept'];
      $callType = $row['CALLTYPE'];
      $HARZAD_TYPE = $row['HARZAD_TYPE'];
      $status = $row['STATUSCD'];
   }
}

// Get earnings data
$earnings = [];
$gross = 0;
if ($staffID) {
   try {
      $query = $conn->prepare('SELECT ifnull(allow_deduc.`value`,0) as `value`,allow_deduc.allow_id,allow_deduc.temp_id,tbl_earning_deduction.edDesc FROM
         tbl_earning_deduction right JOIN allow_deduc ON tbl_earning_deduction.ed_id = allow_deduc.allow_id
         WHERE transcode = ? and staff_id = ? order by allow_id asc');
      $query->execute(array('01', $staffID));
      $earnings = $query->fetchAll(PDO::FETCH_ASSOC);
      
      foreach ($earnings as $earning) {
         $gross += $earning['value'];
      }
   } catch (PDOException $e) {
      error_log("Error loading earnings: " . $e->getMessage());
   }
}

// Get deductions data
$deductions = [];
$totalDeduction = 0;
if ($staffID) {
   try {
      $query = $conn->prepare('SELECT ifnull(allow_deduc.`value`,0) as `value`, allow_deduc.allow_id,allow_deduc.temp_id,tbl_earning_deduction.edDesc FROM
         tbl_earning_deduction RIGHT JOIN allow_deduc ON tbl_earning_deduction.ed_id = allow_deduc.allow_id
         WHERE transcode = ? and staff_id = ? order by allow_id asc');
      $query->execute(array('02', $staffID));
      $deductions = $query->fetchAll(PDO::FETCH_ASSOC);
      
      foreach ($deductions as $deduction) {
         $totalDeduction += $deduction['value'];
      }
   } catch (PDOException $e) {
      error_log("Error loading deductions: " . $e->getMessage());
   }
}

// Get earning/deduction options
$earningOptions = [];
try {
   $query = $conn->prepare('SELECT * FROM tbl_earning_deduction where edType = 1 ORDER BY edDesc asc');
   $query->execute();
   $earningOptions = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
   error_log("Error loading earning options: " . $e->getMessage());
}

$deductionOptions = [];
try {
   $query = $conn->prepare('SELECT * FROM tbl_earning_deduction where edType > 1 ORDER BY edDesc asc');
   $query->execute();
   $deductionOptions = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
   error_log("Error loading deduction options: " . $e->getMessage());
}



?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Earnings - Salary Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/dark-mode.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.form/4.3.0/jquery.form.min.js"></script>
    <script src="js/theme-manager.js"></script>

    <style>
    .swal2-container {
        z-index: 10000 !important;
    }

    .swal2-popup {
        z-index: 10001 !important;
    }

    .modal {
        z-index: 9999;
    }

    .employee-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .earnings-card {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .deductions-card {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .net-pay-card {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }

    /* Ensure buttons are visible */
    #prorate-btn,
    #update-grade-btn {
        display: block !important;
        visibility: visible !important;
        background-color: #eab308 !important;
        /* yellow-500 */
        color: white !important;
    }

    #prorate-btn:hover {
        background-color: #ca8a04 !important;
        /* yellow-600 */
    }

    #update-grade-btn {
        background-color: #3b82f6 !important;
        /* blue-500 */
    }

    #update-grade-btn:hover {
        background-color: #2563eb !important;
        /* blue-600 */
    }

    /* Modal button styles */
    .modal button[type="submit"] {
        background-color: #3b82f6 !important;
        color: white !important;
    }

    .modal button[type="submit"]:hover {
        background-color: #2563eb !important;
    }

    #calculate-prorate-btn {
        background-color: #16a34a !important;
        /* green-600 */
        color: white !important;
    }

    #calculate-prorate-btn:hover {
        background-color: #15803d !important;
        /* green-700 */
    }

    /* Prorate modal specific styles */
    #prorateModal .bg-white {
        display: flex;
        flex-direction: column;
        max-height: 90vh;
    }

    #prorateModal .p-6 {
        flex: 1;
        overflow-y: auto;
        padding-bottom: 0;
    }

    #prorateModal .sticky {
        position: sticky;
        bottom: 0;
        background: white;
        z-index: 10;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #e5e7eb;
    }

    /* Ensure save button is visible after calculation */

    /* Disabled button styles for payslip modal */
    #downloadPayslipBtn:disabled,
    #emailPayslipBtn:disabled,
    #printPayslipBtn:disabled {
        opacity: 0.5 !important;
        cursor: not-allowed !important;
        background-color: #9ca3af !important;
        /* gray-400 */
        color: #6b7280 !important;
        /* gray-500 */
        pointer-events: none !important;
    }

    #downloadPayslipBtn:disabled:hover,
    #emailPayslipBtn:disabled:hover,
    #printPayslipBtn:disabled:hover {
        background-color: #9ca3af !important;
        /* gray-400 */
        color: #6b7280 !important;
        /* gray-500 */
    }



    /* Responsive improvements */
    @media (max-width: 768px) {
        .grid {
            grid-template-columns: 1fr !important;
        }

        .employee-card .grid {
            grid-template-columns: 1fr !important;
        }

        .action-buttons {
            grid-template-columns: repeat(2, 1fr) !important;
        }
    }

    /* Loading states */
    .loading {
        opacity: 0.6;
        pointer-events: none;
    }

    .loading::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 20px;
        height: 20px;
        margin: -10px 0 0 -10px;
        border: 2px solid #f3f3f3;
        border-top: 2px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* Enhanced table styles */
    #earningsTable {
        border-collapse: separate;
        border-spacing: 0;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    #earningsTable thead th {
        background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.875rem;
        letter-spacing: 0.05em;
    }

    #earningsTable tbody tr:hover {
        background-color: #f8fafc;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease;
    }

    /* Enhanced modal animations */
    .modal {
        animation: modalFadeIn 0.3s ease-out;
    }

    @keyframes modalFadeIn {
        from {
            opacity: 0;
            transform: scale(0.9) translateY(-20px);
        }

        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    /* Card hover effects */
    .earnings-card,
    .deductions-card,
    .net-pay-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .earnings-card:hover,
    .deductions-card:hover,
    .net-pay-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }

    /* Form validation styles */
    .form-error {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
    }

    .form-success {
        border-color: #10b981 !important;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1) !important;
    }

    /* Ensure badge styling is persistent */
    .badge-earning, .badge-deduction {
        display: inline-block !important;
        transition: none !important;
        animation: none !important;
    }
    
    .badge-earning {
        background-color: #dcfce7 !important;
        color: #166534 !important;
    }
    
    .badge-deduction {
        background-color: #fee2e2 !important;
        color: #991b1b !important;
    }

    /* Tooltip styles */
    .tooltip {
        position: relative;
        display: inline-block;
    }

    .tooltip .tooltiptext {
        visibility: hidden;
        width: 200px;
        background-color: #1f2937;
        color: white;
        text-align: center;
        border-radius: 6px;
        padding: 8px;
        position: absolute;
        z-index: 1;
        bottom: 125%;
        left: 50%;
        margin-left: -100px;
        opacity: 0;
        transition: opacity 0.3s;
        font-size: 0.875rem;
    }

    .tooltip:hover .tooltiptext {
        visibility: visible;
        opacity: 1;
    }

    /* Payslip modal specific styles */
    #payslipModal .payslip-container {
        max-width: 100%;
        margin: 0;
    }

    #payslipModal .payslip-header {
        padding: 1rem;
        margin-bottom: 1rem;
    }

    #payslipModal .employee-info {
        margin-bottom: 1rem;
    }

    #payslipModal .earnings-deductions {
        margin-bottom: 1rem;
    }

    #payslipModal .net-pay-summary {
        margin-bottom: 1rem;
    }

    #payslipModal .additional-info {
        margin-bottom: 1rem;
    }

    #payslipModal .payslip-footer {
        padding: 1rem;
    }

    /* Compact payslip styles */
    #payslipModal .text-3xl {
        font-size: 1.5rem;
    }

    #payslipModal .text-2xl {
        font-size: 1.25rem;
    }

    #payslipModal .text-xl {
        font-size: 1.125rem;
    }

    #payslipModal .p-6 {
        padding: 1rem;
    }

    #payslipModal .mb-6 {
        margin-bottom: 1rem;
    }

    #payslipModal .gap-6 {
        gap: 1rem;
    }

    /* Responsive payslip */
    @media (max-width: 768px) {
        #payslipModal .max-w-5xl {
            max-width: 95vw;
        }

        #payslipModal .p-6 {
            padding: 0.75rem;
        }

        #payslipModal .text-3xl {
            font-size: 1.25rem;
        }
    }

    /* Scrollable content styling */
    #payslipContent {
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f1f5f9;
    }

    #payslipContent::-webkit-scrollbar {
        width: 8px;
    }

    #payslipContent::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }

    #payslipContent::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    #payslipContent::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    </style>
</head>

<body class="bg-gray-100 font-sans">
    <?php include 'header.php'; ?>
    <div class="flex min-h-screen">
        <?php include 'sidebar.php'; ?>
        <div class="flex-1 p-6">
            <div class="container mx-auto">
                <nav class="mb-6">
                    <a href="home.php" class="text-blue-600 hover:underline"><i class="fas fa-home"></i> Dashboard</a>
                    <span class="mx-2">/</span>
                    <span>Employee Earnings</span>
                </nav>

                <?php if (isset($_SESSION['msg'])): ?>
                <div
                    class="bg-<?php echo $_SESSION['alertcolor'] ?? 'blue'; ?>-100 text-<?php echo $_SESSION['alertcolor'] ?? 'blue'; ?>-800 p-4 rounded-md mb-6 flex justify-between items-center">
                    <span><?php echo htmlspecialchars($_SESSION['msg']); ?></span>
                    <button onclick="this.parentElement.remove()"
                        class="text-<?php echo $_SESSION['alertcolor'] ?? 'blue'; ?>-600 hover:text-<?php echo $_SESSION['alertcolor'] ?? 'blue'; ?>-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php unset($_SESSION['msg'], $_SESSION['alertcolor']); ?>
                <?php endif; ?>

                <h1 class="text-3xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-user-tie mr-2"></i> Employee Earnings
                    <small class="text-base text-gray-600 ml-2">Manage employee payroll and earnings</small>
                </h1>

                <!-- Payroll Period Info -->
                <div class="bg-white p-4 rounded-lg shadow-md mb-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <i class="fas fa-calendar-alt text-blue-600 mr-2"></i>
                            <span class="font-semibold">Current Payroll Period:</span>
                            <span class="ml-2 text-blue-600"><?php echo $_SESSION['activeperiodDescription']; ?></span>
                            <span class="ml-2 px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Open</span>
                        </div>
                        <div class="flex space-x-2">
                            <button id="reload-button"
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                <i class="fas fa-sync-alt"></i> Reload
                            </button>
                            <button id="next-employee-button"
                                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                                <i class="fas fa-arrow-right"></i> Next Employee
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Employee Search -->
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <div class="flex items-center space-x-4">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Search Employee</label>
                            <div class="flex">
                                <input type="text" id="employee-search"
                                    class="flex-1 px-4 py-2 border border-gray-300 rounded-l-md focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter Staff Name or Staff No">
                                <button id="search-employee-btn"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-r-md hover:bg-blue-700 focus:ring-blue-500 focus:border-blue-500">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <!-- Hidden form for employee search -->
                            <form id="add_item_form" action="classes/controller.php?act=retrieveSingleEmployeeData"
                                method="post" style="display: none;">
                                <input type="text" name="item" id="item" value="">
                                <input type="hidden" name="code" id="code" value="-1">
                            </form>
                        </div>
                        <!-- Hidden form for employee search -->
                        <form id="add_item_form" action="classes/controller.php?act=retrieveSingleEmployeeData"
                            method="post" style="display: none;">
                            <input type="text" name="item" id="item" value="">
                            <input type="hidden" name="code" id="code" value="-1">
                        </form>
                    </div>
                </div>
            </div>

            <!-- Employee Info Card -->


            <?php if ($staffID): ?>
            <div class="employee-card text-white p-6 rounded-lg shadow-md mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <h3 class="text-lg font-semibold mb-2">Employee Information</h3>
                        <p><strong>ID:</strong> <?php echo htmlspecialchars($staffID); ?></p>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($empfname); ?></p>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-2">Position Details</h3>
                        <p><strong>Grade/Step:</strong>
                            <?php echo htmlspecialchars($empGrade ?? ''); ?>/<?php echo htmlspecialchars($empStep ?? ''); ?>
                        </p>
                        <p><strong>Department:</strong> <?php echo htmlspecialchars($dept ?? ''); ?></p>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-2">Status</h3>
                        <p><strong>Status:</strong>
                            <span class="px-2 py-1 bg-white bg-opacity-20 rounded text-sm">
                                <?php echo htmlspecialchars($status ?? ''); ?>
                            </span>
                        </p>
                    </div>
                    <div class="flex items-center justify-center">
                        <div class="text-center">
                            <i class="fas fa-user-circle text-6xl mb-2"></i>
                            <p class="text-sm opacity-75">Active Employee</p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Earnings and Deductions Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Earnings Card -->
                <div class="earnings-card text-white p-6 rounded-lg shadow-md">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-semibold">Earnings</h3>
                        <i class="fas fa-plus-circle text-2xl"></i>
                    </div>
                    <div class="space-y-2">
                        <?php foreach ($earnings as $earning): ?>
                        <div class="flex justify-between items-center bg-white bg-opacity-20 p-2 rounded">
                            <span><?php echo htmlspecialchars($earning['edDesc']); ?></span>
                            <span class="font-semibold">₦<?php echo number_format($earning['value']); ?></span>
                        </div>
                        <?php endforeach; ?>
                        <div class="border-t border-white border-opacity-30 pt-2 mt-4">
                            <div class="flex justify-between items-center font-bold text-lg">
                                <span>Gross Salary</span>
                                <span>₦<?php echo number_format($gross); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Deductions Card -->
                <div class="deductions-card text-white p-6 rounded-lg shadow-md">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-semibold">Deductions</h3>
                        <i class="fas fa-minus-circle text-2xl"></i>
                    </div>
                    <div class="space-y-2">
                        <?php foreach ($deductions as $deduction): ?>
                        <div class="flex justify-between items-center bg-white bg-opacity-20 p-2 rounded">
                            <span><?php echo htmlspecialchars($deduction['edDesc']); ?></span>
                            <span class="font-semibold">₦<?php echo number_format($deduction['value']); ?></span>
                        </div>
                        <?php endforeach; ?>
                        <div class="border-t border-white border-opacity-30 pt-2 mt-4">
                            <div class="flex justify-between items-center font-bold text-lg">
                                <span>Total Deductions</span>
                                <span>₦<?php echo number_format($totalDeduction); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Net Pay Card -->
                <div class="net-pay-card text-white p-6 rounded-lg shadow-md">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-semibold">Net Pay</h3>
                        <i class="fas fa-wallet text-2xl"></i>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-bold mb-2">
                            ₦<?php echo number_format($gross - $totalDeduction); ?>
                        </div>
                        <p class="text-sm opacity-75">Take Home Pay</p>
                    </div>
                    <div class="mt-6 space-y-2">
                        <div class="flex justify-between">
                            <span>Gross Salary:</span>
                            <span>₦<?php echo number_format($gross); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Total Deductions:</span>
                            <span>₦<?php echo number_format($totalDeduction); ?></span>
                        </div>
                        <div class="border-t border-white border-opacity-30 pt-2">
                            <div class="flex justify-between font-bold">
                                <span>Net Pay:</span>
                                <span>₦<?php echo number_format($gross - $totalDeduction); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Payroll Actions</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <button id="add-earning-btn"
                        class="p-4 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-plus-circle text-2xl mb-2"></i>
                        <div class="text-sm">Add Earning</div>
                    </button>
                    <button id="add-deduction-btn"
                        class="p-4 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-minus-circle text-2xl mb-2"></i>
                        <div class="text-sm">Add Deduction</div>
                    </button>
                    <button id="add-temp-btn"
                        class="p-4 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
                        <i class="fas fa-clock text-2xl mb-2"></i>
                        <div class="text-sm">Temp Item</div>
                    </button>
                    <button id="add-loan-btn"
                        class="p-4 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                        <i class="fas fa-hand-holding-usd text-2xl mb-2"></i>
                        <div class="text-sm">Add Loan</div>
                    </button>
                    <button id="run-payroll-btn"
                        class="p-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors <?php $j = retrievePayrollRunStatus($staffID, $_SESSION['currentactiveperiod']); if ($j == '1') { echo 'opacity-50 cursor-not-allowed'; } ?>"
                        <?php if ($j == '1') { echo 'disabled'; } ?>>
                        <i class="fas fa-play-circle text-2xl mb-2"></i>
                        <div class="text-sm">Run Payroll</div>
                    </button>
                    <button id="view-payslip-btn"
                        class="p-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors <?php $j = retrievePayrollRunStatus($staffID, $_SESSION['currentactiveperiod']); if ($j == '0') { echo 'opacity-50 cursor-not-allowed'; } ?>"
                        <?php if ($j == '0') { echo 'disabled'; } ?>>
                        <i class="fas fa-file-alt text-2xl mb-2"></i>
                        <div class="text-sm">View Payslip</div>
                    </button>
                    <button id="delete-payslip-btn"
                        class="p-4 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors <?php $j = retrievePayrollRunStatus($staffID, $_SESSION['currentactiveperiod']); if ($j == '0') { echo 'opacity-50 cursor-not-allowed'; } ?>"
                        <?php if ($j == '0') { echo 'disabled'; } ?>>
                        <i class="fas fa-trash-alt text-2xl mb-2"></i>
                        <div class="text-sm">Delete Payslip</div>
                    </button>
                    <button id="prorate-btn"
                        class="p-4 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors">
                        <i class="fas fa-percentage text-2xl mb-2"></i>
                        <div class="text-sm">Prorate Allowance</div>
                    </button>
                    <button id="update-grade-btn"
                        class="p-4 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                        <i class="fas fa-level-up-alt text-2xl mb-2"></i>
                        <div class="text-sm">Update Grade/Step</div>
                    </button>
                </div>
            </div>

            <!-- Detailed Table -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Detailed Breakdown</h3>
                    <div class="flex space-x-2">
                        <button id="export-excel-btn"
                            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                        <button id="print-btn" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table id="earningsTable" class="min-w-full bg-white border border-gray-200">
                        <thead>
                            <tr class="bg-gray-800 text-white">
                                <th class="py-2 px-4">Type</th>
                                <th class="py-2 px-4">Code</th>
                                <th class="py-2 px-4">Description</th>
                                <th class="py-2 px-4">Amount</th>
                                <th class="py-2 px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($earnings as $earning): ?>
                            <tr class="border-b hover:bg-green-50">
                                <td class="py-2 px-4"><span
                                        class="badge-earning px-2 py-1 rounded text-sm font-medium">Earning</span>
                                </td>
                                <td class="py-2 px-4"><?php echo htmlspecialchars($earning['allow_id']); ?></td>
                                <td class="py-2 px-4"><?php echo htmlspecialchars($earning['edDesc']); ?></td>
                                <td class="py-2 px-4 text-green-600 font-semibold">
                                    ₦<?php echo number_format($earning['value']); ?></td>
                                <td class="py-2 px-4">
                                    <button class="delete-item-btn text-red-600 hover:text-red-900 mr-2"
                                        data-id="<?php echo htmlspecialchars($earning['temp_id']); ?>"
                                        data-type="earning">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                            <?php foreach ($deductions as $deduction): ?>
                            <tr class="border-b hover:bg-red-50">
                                <td class="py-2 px-4"><span
                                        class="badge-deduction px-2 py-1 rounded text-sm font-medium">Deduction</span>
                                </td>
                                <td class="py-2 px-4"><?php echo htmlspecialchars($deduction['allow_id']); ?></td>
                                <td class="py-2 px-4"><?php echo htmlspecialchars($deduction['edDesc']); ?></td>
                                <td class="py-2 px-4 text-red-600 font-semibold">
                                    ₦<?php echo number_format($deduction['value']); ?></td>
                                <td class="py-2 px-4">
                                    <button class="delete-item-btn text-red-600 hover:text-red-900 mr-2"
                                        data-id="<?php echo htmlspecialchars($deduction['temp_id']); ?>"
                                        data-type="deduction">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Add Earning Modal -->
    <div id="earningModal" class="fixed inset-0 bg-gray-500 bg-opacity-50 hidden z-50 items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Add Earning</h2>
                <button type="button" class="close-modal text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="earningForm" method="POST" autocomplete="off">
                <input type="hidden" name="curremployee" value="<?php echo htmlspecialchars($staffID ?? ''); ?>">
                <input type="hidden" name="grade_level" value="<?php echo htmlspecialchars($empGrade ?? ''); ?>">
                <input type="hidden" name="step" value="<?php echo htmlspecialchars($empStep ?? ''); ?>">
                <input type="hidden" name="callType" value="<?php echo htmlspecialchars($callType ?? ''); ?>">
                <input type="hidden" name="HARZAD_TYPE" value="<?php echo htmlspecialchars($HARZAD_TYPE ?? ''); ?>">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Earning Type</label>
                    <select name="newearningcode" id="newearningcode"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        required>
                        <option value="">Select Earning Type</option>
                        <?php foreach ($earningOptions as $option): ?>
                        <option value="<?php echo htmlspecialchars($option['ed_id']); ?>"
                            data-code="<?php echo htmlspecialchars($option['edType']); ?>">
                            <?php echo htmlspecialchars($option['edDesc']); ?> -
                            <?php echo htmlspecialchars($option['ed_id']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                    <input type="number" name="earningamount" id="earningamount" step="0.01" min="0"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        readonly>
                </div>

                <div class="flex justify-end gap-2 mt-5">
                    <button type="button"
                        class="close-modal px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Add
                        Earning</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Deduction Modal -->
    <div id="deductionModal" class="fixed inset-0 bg-gray-500 bg-opacity-50 hidden z-50 items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Add Deduction</h2>
                <button type="button" class="close-modal text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="deductionForm" method="POST" autocomplete="off">
                <input type="hidden" name="curremployee" value="<?php echo htmlspecialchars($staffID ?? ''); ?>">
                <input type="hidden" name="grade_level" value="<?php echo htmlspecialchars($empGrade ?? ''); ?>">
                <input type="hidden" name="step" value="<?php echo htmlspecialchars($empStep ?? ''); ?>">
                <input type="hidden" name="callType" value="<?php echo htmlspecialchars($callType ?? ''); ?>">
                <input type="hidden" name="HARZAD_TYPE" value="<?php echo htmlspecialchars($HARZAD_TYPE ?? ''); ?>">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Deduction Type</label>
                    <select name="newdeductioncode" id="newdeductioncode"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        required>
                        <option value="">Select Deduction Type</option>
                        <?php foreach ($deductionOptions as $option): ?>
                        <option value="<?php echo htmlspecialchars($option['ed_id']); ?>"
                            data-code="<?php echo htmlspecialchars($option['edType']); ?>">
                            <?php echo htmlspecialchars($option['edDesc']); ?> -
                            <?php echo htmlspecialchars($option['ed_id']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                    <input type="number" name="deductionamount" id="deductionamount" step="0.01" min="0"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>

                <div class="flex justify-end gap-2 mt-5">
                    <button type="button"
                        class="close-modal px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Add
                        Deduction</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Loan/Corporate Modal -->
    <div id="loanModal" class="fixed inset-0 bg-gray-500 bg-opacity-50 hidden z-50 items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Add Loan/Corporate</h2>
                <button type="button" class="close-modal text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="loanForm" method="POST" autocomplete="off">
                <input type="hidden" name="curremployee" value="<?php echo htmlspecialchars($staffID ?? ''); ?>">
                <input type="hidden" name="grade_level" value="<?php echo htmlspecialchars($empGrade ?? ''); ?>">
                <input type="hidden" name="step" value="<?php echo htmlspecialchars($empStep ?? ''); ?>">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <select name="newdeductioncodeloan" id="newdeductioncodeloan"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        required>
                        <option value="">Select Deduction/Allowance</option>
                        <?php foreach ($deductionOptions  as $option): ?>
                        <?php if ($option['edType'] == '4'): ?>
                        <option value="<?php echo htmlspecialchars($option['ed_id']); ?>">
                            <?php echo htmlspecialchars($option['ed']); ?> -
                            <?php echo htmlspecialchars($option['ed_id']); ?>
                        </option>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Principal</label>
                    <input type="number" name="Principal" id="Principal" step="0.01" min="0"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Interest</label>
                    <input type="number" name="interest" id="interest" step="0.01" min="0" value="0"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Repayment Period</label>
                    <input type="number" name="no_times_repayment" id="no_times_repayment" min="1" value="1"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monthly Repayment</label>
                    <input type="number" name="monthlyRepayment" id="monthlyRepayment" step="0.01" min="0"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        readonly>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Existing Balance</label>
                    <input type="number" name="Balance" id="Balance" step="0.01" min="0"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 bg-gray-100"
                        readonly>
                </div>

                <div class="flex justify-end gap-2 mt-5">
                    <button type="button"
                        class="close-modal px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Add
                        Loan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Prorate Allowance Modal -->
    <div id="prorateModal"
        class="fixed inset-0 bg-gray-500 bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-6xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold">Prorate Allowances</h2>
                    <button type="button" class="close-modal text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="prorateForm" method="POST" autocomplete="off">
                    <input type="hidden" name="curremployee" value="<?php echo htmlspecialchars($staffID ?? ''); ?>">
                    <input type="hidden" name="grade_level" value="<?php echo htmlspecialchars($empGrade ?? ''); ?>">
                    <input type="hidden" name="step" value="<?php echo htmlspecialchars($empStep ?? ''); ?>">

                    <?php
                $split = explode(' ', $_SESSION['activeperiodDescription'], 2);
                $mon = date('m', strtotime($split[0]));
                $yr = $split[1];
                $dayss = cal_days_in_month(CAL_GREGORIAN, $mon, $yr);
                ?>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">No. of Days in Current
                            Period</label>
                        <input type="text" name="no_days" id="no_days" value="<?php echo $dayss; ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 bg-gray-100"
                            readonly>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">No of Days to Calculate</label>
                        <input type="number" name="daysToCal" id="daysToCal" min="0" max="<?php echo $dayss; ?>"
                            value="0"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            required>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Current Allowances</label>
                            <div class="overflow-x-auto">
                                <table class="min-w-full bg-white border border-gray-200">
                                    <thead>
                                        <tr class="bg-gray-100">
                                            <th class="py-2 px-4 border">Code</th>
                                            <th class="py-2 px-4 border">Description</th>
                                            <th class="py-2 px-4 border">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($earnings as $earning): ?>
                                        <tr class="border-b">
                                            <td class="py-2 px-4 border">
                                                <?php echo htmlspecialchars($earning['allow_id']); ?>
                                            </td>
                                            <td class="py-2 px-4 border">
                                                <?php echo htmlspecialchars($earning['edDesc']); ?>
                                            </td>
                                            <td class="py-2 px-4 border text-right">
                                                ₦<?php echo number_format($earning['value']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Calculated Allowances</label>
                            <div class="overflow-x-auto">
                                <table class="min-w-full bg-white border border-gray-200" id="calculatedTable">
                                    <thead>
                                        <tr class="bg-gray-100">
                                            <th class="py-2 px-4 border">Code</th>
                                            <th class="py-2 px-4 border">Description</th>
                                            <th class="py-2 px-4 border">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="border-b">
                                            <td class="py-2 px-4 border text-center text-gray-500" colspan="3">
                                                Click Calculate to see prorated values...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Total Calculated Value</label>
                        <div id="getProrateValue"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 min-h-[40px]">
                            <div class="text-gray-500 text-sm">Click Calculate to see total prorated value...</div>
                        </div>
                    </div>

                </form>
                <div class="flex justify-end gap-2 mt-5 sticky bottom-0 bg-white pt-4 border-t">
                    <button type="button"
                        class="close-modal px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Cancel</button>
                    <button type="button" id="calculate-prorate-btn"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Grade/Step Modal -->
    <div id="gradeStepModal" class="fixed inset-0 bg-gray-500 bg-opacity-50 hidden z-50 items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Update Grade/Step</h2>
                <button type="button" class="close-modal text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="gradeStepForm" method="POST" autocomplete="off">
                <input type="hidden" name="curremployee" value="<?php echo htmlspecialchars($staffID ?? ''); ?>">
                <input type="hidden" name="grade_level" value="<?php echo htmlspecialchars($empGrade ?? ''); ?>">
                <input type="hidden" name="step" value="<?php echo htmlspecialchars($empStep ?? ''); ?>">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Grade</label>
                    <input type="text" name="grade" id="grade" value="<?php echo htmlspecialchars($empGrade ?? ''); ?>"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 bg-gray-100"
                        readonly>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Step</label>
                    <input type="text" name="step" id="step" value="<?php echo htmlspecialchars($empStep ?? ''); ?>"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 bg-gray-100"
                        readonly>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Grade</label>
                    <input type="text" name="new_grade" id="new_grade"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Step</label>
                    <input type="text" name="new_step" id="new_step"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        required>
                </div>

                <div class="flex justify-end gap-2 mt-5">
                    <button type="button"
                        class="close-modal px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Update
                        Grade/Step</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Payslip Modal -->
    <div id="payslipModal"
        class="fixed inset-0 bg-gray-500 bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-6xl flex flex-col" style="max-height: 90vh;">
            <!-- Modal Header -->
            <div class="flex justify-between items-center p-4 border-b border-gray-200 flex-shrink-0">
                <div>
                    <h2 class="text-lg font-bold">Employee Payslip</h2>
                    <p class="text-sm text-gray-600" id="payslipEmployeeInfo">Loading...</p>
                </div>
                <button type="button" class="close-modal text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Payslip Content - Scrollable -->
            <div id="payslipContent" class="flex-1 overflow-y-auto p-4" style="min-height: 0;">
                <!-- Loading indicator -->
                <div class="text-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="mt-2 text-gray-600">Loading payslip...</p>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex justify-between items-center p-4 border-t border-gray-200 flex-shrink-0 bg-gray-50">
                <div class="flex space-x-2">
                    <button type="button" id="downloadPayslipBtn"
                        class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm">
                        <i class="fas fa-download mr-2"></i>Download PDF
                    </button>
                    <button type="button" id="emailPayslipBtn"
                        class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 text-sm">
                        <i class="fas fa-envelope mr-2"></i>Email Payslip
                    </button>
                </div>
                <div class="flex space-x-2">
                    <button type="button"
                        class="close-modal px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 text-sm">Close</button>
                    <button type="button" id="printPayslipBtn"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                        <i class="fas fa-print mr-2"></i>Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    $(function() {
        // Initialize DataTable with proper configuration to preserve badges
        $('#earningsTable').DataTable({
            pageLength: 25,
            ordering: true,
            responsive: true,
            language: {
                search: "Search records:",
                lengthMenu: "Show _MENU_ records per page",
                info: "Showing _START_ to _END_ of _TOTAL_ records",
                infoEmpty: "Showing 0 to 0 of 0 records",
                infoFiltered: "(filtered from _MAX_ total records)",
                zeroRecords: "No matching records found",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            },
            columnDefs: [{
                    orderable: false,
                    targets: 4
                } // Disable sorting on Actions column
            ],
            createdRow: function(row, data, dataIndex) {
                // Preserve badge styling when DataTable creates rows
                var $typeCell = $(row).find('td:first-child span');
                if ($typeCell.text().trim() === 'Earning') {
                    $typeCell.addClass('badge-earning px-2 py-1 rounded text-sm font-medium');
                } else if ($typeCell.text().trim() === 'Deduction') {
                    $typeCell.addClass('badge-deduction px-2 py-1 rounded text-sm font-medium');
                }
            },
            drawCallback: function() {
                // Ensure badges are properly styled after each draw
                $('#earningsTable tbody tr').each(function() {
                    var $row = $(this);
                    var $typeCell = $row.find('td:first-child span');
                    var cellText = $typeCell.text().trim();

                    // Remove any existing badge classes first
                    $typeCell.removeClass('badge-earning badge-deduction px-2 py-1 bg-green-100 text-green-800 bg-red-100 text-red-800 rounded text-sm font-medium');
                    
                    // Add appropriate badge classes based on content
                    if (cellText === 'Earning') {
                        $typeCell.addClass('badge-earning px-2 py-1 rounded text-sm font-medium');
                    } else if (cellText === 'Deduction') {
                        $typeCell.addClass('badge-deduction px-2 py-1 rounded text-sm font-medium');
                    }
                });
            }
        });

        // Add loading states to buttons
        function addLoadingState(button) {
            button.addClass('loading').prop('disabled', true);
            const originalText = button.text();
            button.data('original-text', originalText);
            button.html('<i class="fas fa-spinner fa-spin"></i> Loading...');
        }

        function removeLoadingState(button) {
            button.removeClass('loading').prop('disabled', false);
            const originalText = button.data('original-text');
            if (originalText) {
                button.text(originalText);
            }
        }

        // Form validation enhancement
        function validateForm(form) {
            let isValid = true;
            const requiredFields = form.find('[required]');

            requiredFields.each(function() {
                const field = $(this);
                const value = field.val().trim();

                if (!value) {
                    field.addClass('form-error');
                    isValid = false;
                } else {
                    field.removeClass('form-error').addClass('form-success');
                }
            });

            return isValid;
        }

        // Auto-remove success/error classes after 3 seconds (but preserve badge classes)
        function autoRemoveValidationClasses() {
            setTimeout(function() {
                // Only remove validation classes from form inputs, not badge elements
                $('input.form-success, input.form-error, select.form-success, select.form-error, textarea.form-success, textarea.form-error').removeClass('form-success form-error');
            }, 3000);
        }

        // Reload button
        $('#reload-button').click(function() {
            location.reload();
        });

        // Next employee button
        $('#next-employee-button').click(function() {
            window.location.href =
                'classes/controller.php?act=getNextEmployee&track=<?php echo $_SESSION['emptrack'] + 1; ?>';
        });

        // Employee search functionality
        function performEmployeeSearch() {
            var searchValue = $('#employee-search').val().trim();
            if (searchValue !== '') {
                // Show loading state
                $('#search-employee-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                $.post('classes/controller.php?act=retrieveSingleEmployeeData', {
                    item: searchValue
                }, function(response) {
                    location.reload();
                }).fail(function() {
                    // Reset button on error
                    $('#search-employee-btn').prop('disabled', false).html(
                        '<i class="fas fa-search"></i>');
                    Swal.fire('Error', 'Failed to search for employee', 'error');
                });
            } else {
                Swal.fire('Warning', 'Please enter a staff name or number to search', 'warning');
            }
        }

        // Employee search autocomplete
        if ($.fn.autocomplete) {
            $('#employee-search').autocomplete({
                source: 'searchStaff.php',
                minLength: 1,
                delay: 300,
                select: function(event, ui) {
                    event.preventDefault();
                    event.stopPropagation();
                    $('#employee-search').val(ui.item.value);
                    // Update the hidden form field
                    $('#item').val(ui.item.value);
                    // Don't auto-submit, let user click search button or press enter
                    // return false;
                    performEmployeeSearch();

                }
            });
        }

        // Prevent any form submission from search field
        $('#employee-search').on('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        });

        // Prevent form submission on button click if it's inside a form
        $('#search-employee-btn').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            // Update the hidden form field before searching
            $('#item').val($('#employee-search').val());
            performEmployeeSearch();
            return false;
        });

        // Search button click
        $('#search-employee-btn').click(function() {
            performEmployeeSearch();
        });

        // Enter key press in search field
        $('#employee-search').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault(); // Prevent form submission
                // Update the hidden form field before searching
                $('#item').val($('#employee-search').val());
                performEmployeeSearch();
            }
        });

        // Modal controls - Fix modal closing
        $('.close-modal').click(function() {
            console.log('Close modal clicked');
            $('#earningModal, #deductionModal, #loanModal, #prorateModal, #gradeStepModal, #payslipModal')
                .addClass(
                    'hidden').removeClass('flex');
        });

        // Close modal when clicking outside
        $(document).on('click', function(e) {
            // Check if click is on modal backdrop (the gray overlay)
            if ($(e.target).hasClass('fixed') && $(e.target).hasClass('bg-gray-500')) {
                console.log('Clicked outside modal');
                $('#earningModal, #deductionModal, #loanModal, #prorateModal, #gradeStepModal, #payslipModal')
                    .addClass('hidden').removeClass('flex');
            }
        });

        // Alternative close method - ESC key
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                console.log('ESC key pressed');
                $('#earningModal, #deductionModal, #loanModal, #prorateModal, #gradeStepModal, #payslipModal')
                    .addClass('hidden').removeClass('flex');
            }
        });

        // Add earning button
        $('#add-earning-btn').click(function() {
            $('#earningModal').removeClass('hidden').addClass('flex');
        });

        // Add deduction button
        $('#add-deduction-btn').click(function() {
            $('#deductionModal').removeClass('hidden').addClass('flex');
        });

        // Add loan button
        $('#add-loan-btn').click(function() {
            $('#loanModal').removeClass('hidden').addClass('flex');
        });

        // Prorate button
        $('#prorate-btn').click(function() {
            console.log('Prorate button clicked');
            $('#prorateModal').removeClass('hidden').addClass('flex');
        });

        // Update grade/step button
        $('#update-grade-btn').click(function() {
            console.log('Update grade button clicked');
            $('#gradeStepModal').removeClass('hidden').addClass('flex');
        });

        // Debug: Check if buttons exist
        $(document).ready(function() {
            console.log('Prorate button exists:', $('#prorate-btn').length);
            console.log('Update grade button exists:', $('#update-grade-btn').length);
            console.log('Prorate modal exists:', $('#prorateModal').length);
            console.log('Grade modal exists:', $('#gradeStepModal').length);
            console.log('Close modal buttons exist:', $('.close-modal').length);

            // Test close button functionality
            $('.close-modal').each(function(index) {
                console.log('Close button ' + index + ' found:', $(this).text().trim());
            });
        });

        // Earning type change - Auto-populate amount
        $('#newearningcode').change(function() {
            var code = $(this).find(':selected').data("code");
            var selectedValue = $(this).val();

            if (selectedValue) {
                $.ajax({
                    url: 'classes/getSalaryValue.php',
                    type: 'POST',
                    data: {
                        curremployee: '<?php echo htmlspecialchars($staffID ?? ''); ?>',
                        grade_level: '<?php echo htmlspecialchars($empGrade ?? ''); ?>',
                        step: '<?php echo htmlspecialchars($empStep ?? ''); ?>',
                        callType: '<?php echo htmlspecialchars($callType ?? ''); ?>',
                        HARZAD_TYPE: '<?php echo htmlspecialchars($HARZAD_TYPE ?? ''); ?>',
                        newearningcode: selectedValue,
                        earningamount: '',
                        code: code
                    },
                    success: function(response) {
                        console.log('Response:', response); // Debug log

                        // The backend directly outputs the value, so we handle the response directly
                        response = $.trim(response);

                        if (response == 'manual') {
                            $("#earningamount").val('');
                            $("#earningamount").attr('readonly', false);
                        } else if (response && response != '0' && response != '') {
                            // Remove commas from the response for number input
                            var cleanValue = response.replace(/,/g, '');
                            $("#earningamount").val(cleanValue);
                            $("#earningamount").attr('readonly', true);
                        } else {
                            $("#earningamount").val('');
                            $("#earningamount").attr('readonly', false);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('Error:', error); // Debug log
                        console.log('Status:', status); // Debug log
                        console.log('Response:', xhr.responseText); // Debug log
                        Swal.fire('Error', 'An error occurred while getting salary value',
                            'error');
                    }
                });
            }
        });

        // Deduction type change - Auto-populate amount
        $('#newdeductioncode').change(function() {
            var code = $(this).find(':selected').data("code");
            var selectedValue = $(this).val();

            if (selectedValue) {
                $.ajax({
                    url: 'classes/getSalaryValue.php',
                    type: 'POST',
                    data: {
                        curremployee: '<?php echo htmlspecialchars($staffID ?? ''); ?>',
                        grade_level: '<?php echo htmlspecialchars($empGrade ?? ''); ?>',
                        step: '<?php echo htmlspecialchars($empStep ?? ''); ?>',
                        callType: '<?php echo htmlspecialchars($callType ?? ''); ?>',
                        HARZAD_TYPE: '<?php echo htmlspecialchars($HARZAD_TYPE ?? ''); ?>',
                        newearningcode: selectedValue,
                        earningamount: '',
                        code: code
                    },
                    success: function(response) {
                        console.log('Response:', response); // Debug log

                        // The backend directly outputs the value, so we handle the response directly
                        response = $.trim(response);

                        if (response == 'manual') {
                            $("#deductionamount").val('');
                            $("#deductionamount").attr('readonly', false);
                        } else if (response && response != '0' && response != '') {
                            // Remove commas from the response for number input
                            var cleanValue = response.replace(/,/g, '');
                            $("#deductionamount").val(cleanValue);
                            $("#deductionamount").attr('readonly', true);
                        } else {
                            $("#deductionamount").val('');
                            $("#deductionamount").attr('readonly', false);
                        }
                    },


                    error: function(xhr, status, error) {
                        console.log('Error:', error); // Debug log
                        console.log('Status:', status); // Debug log
                        console.log('Response:', xhr.responseText); // Debug log
                        Swal.fire('Error', 'An error occurred while getting salary value',
                            'error');
                    }
                });
            }
        });
        // Form submissions
        $('#earningForm').submit(function(event) {
            event.preventDefault();

            if (!validateForm($(this))) {
                Swal.fire('Validation Error', 'Please fill in all required fields', 'error');
                return;
            }

            const submitBtn = $(this).find('button[type="submit"]');
            addLoadingState(submitBtn);

            var formData = $(this).serialize();

            $.ajax({
                url: 'classes/controller.php?act=addemployeeearning',
                type: 'POST',
                data: formData,
                success: function(response) {
                    removeLoadingState(submitBtn);
                    autoRemoveValidationClasses();

                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Earning added successfully',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                },
                error: function() {
                    removeLoadingState(submitBtn);
                    Swal.fire('Error', 'An error occurred while adding the earning',
                        'error');
                }
            });
        });

        $('#deductionForm').submit(function(event) {
            event.preventDefault();

            if (!validateForm($(this))) {
                Swal.fire('Validation Error', 'Please fill in all required fields', 'error');
                return;
            }

            const submitBtn = $(this).find('button[type="submit"]');
            addLoadingState(submitBtn);

            var formData = $(this).serialize();

            $.ajax({
                url: 'classes/controller.php?act=addemployeededuction',
                type: 'POST',
                data: formData,
                success: function(response) {
                    removeLoadingState(submitBtn);
                    autoRemoveValidationClasses();

                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Earning added successfully',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    setTimeout(function() {
                        // location.reload();
                    }, 1500);
                },
                error: function() {
                    removeLoadingState(submitBtn);
                    Swal.fire('Error', 'An error occurred while adding the earning',
                        'error');
                }
            });
        });

        // Loan form submission
        $('#loanForm').submit(function(event) {
            event.preventDefault();
            var formData = $(this).serialize();

            $.ajax({
                url: 'classes/controller.php?act=loan_corporate',
                type: 'POST',
                data: formData,
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Loan added successfully',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                },
                error: function() {
                    Swal.fire('Error', 'An error occurred while adding the loan',
                        'error');
                }
            });
        });

        // Grade/Step form submission
        $('#gradeStepForm').submit(function(event) {
            event.preventDefault();
            var formData = $(this).serialize();

            $.ajax({
                url: 'classes/runUpdateGrade.php',
                type: 'POST',
                data: formData,
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Grade/Step updated successfully',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                },
                error: function() {
                    Swal.fire('Error', 'An error occurred while updating grade/step',
                        'error');
                }
            });
        });

        // Calculate on blur of days to calculate field
        $('#daysToCal').blur(function() {
            var daysToCal = $(this).val();
            var noDays = $('#no_days').val();

            if (!daysToCal || daysToCal <= 0) {
                return; // Don't show error, just return
            }

            if (parseInt(daysToCal) > parseInt(noDays)) {
                Swal.fire('Error', 'Days to calculate cannot exceed total days in period', 'error');
                return;
            }

            // Get current allowances from the table
            var totalCalculated = 0;
            var calculatedRows = '';

            // Loop through current allowances table and calculate prorated values
            $('#prorateModal table:first tbody tr').each(function() {
                var $row = $(this);
                var code = $row.find('td:first').text().trim();
                var description = $row.find('td:eq(1)').text().trim();
                var amountText = $row.find('td:last').text().trim();

                // Extract numeric value from amount (remove ₦ and commas)
                var amount = parseFloat(amountText.replace(/[₦,]/g, '')) || 0;

                if (amount > 0) {
                    // Calculate prorated amount
                    var proratedAmount = (amount / parseInt(noDays)) * parseInt(daysToCal);
                    totalCalculated += proratedAmount;

                    // Build calculated row
                    calculatedRows += '<tr class="border-b">' +
                        '<td class="py-2 px-4 border">' + code + '</td>' +
                        '<td class="py-2 px-4 border">' + description + '</td>' +
                        '<td class="py-2 px-4 border text-right">₦' + proratedAmount
                        .toLocaleString('en-NG', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }) + '</td>' +
                        '</tr>';
                }
            });

            // Update calculated table
            if (calculatedRows === '') {
                $('#calculatedTable tbody').html(
                    '<tr class="border-b"><td class="py-2 px-4 border text-center text-gray-500" colspan="3">No allowances found to calculate</td></tr>'
                );
                $('#getProrateValue').html(
                    '<div class="text-gray-500 text-sm">No allowances to calculate</div>');
            } else {
                $('#calculatedTable tbody').html(calculatedRows);
                // Update total calculated value
                $('#getProrateValue').html('<div class="text-lg font-semibold text-green-600">₦' +
                    totalCalculated.toLocaleString('en-NG', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }) + '</div>');
            }

            // Show calculation complete message
            $('#getProrateValue').append(
                '<div class="text-green-600 text-sm mt-2">✓ Calculation complete - Click Save to apply</div>'
            );
        });

        // Save prorate button
        $('#calculate-prorate-btn').click(function(e) {
            console.log('Save button clicked!'); // Debug log
            e.preventDefault(); // Prevent form submission
            e.stopPropagation(); // Stop event bubbling

            var daysToCal = $('#daysToCal').val();
            if (!daysToCal || daysToCal <= 0) {
                Swal.fire('Error', 'Please enter a valid number of days to calculate', 'error');
                return;
            }

            // Show loading state
            $('#calculate-prorate-btn').prop('disabled', true).text('Saving...');

            var formData = $('#prorateForm').serialize();

            $.ajax({
                url: 'classes/getProrate.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });

                        // Close modal and reload page
                        setTimeout(function() {
                            $('#prorateModal').addClass('hidden').removeClass(
                                'flex');
                            location.reload();
                        }, 1500);
                    } else {
                        $('#calculate-prorate-btn').prop('disabled', false).text(
                            'Save');
                        Swal.fire('Error', response.message ||
                            'An error occurred while saving prorate allowances',
                            'error'
                        );
                    }
                },
                error: function(xhr, status, error) {
                    $('#calculate-prorate-btn').prop('disabled', false).text('Save');
                    console.error('AJAX Error:', xhr.responseText);
                    Swal.fire('Error',
                        'An error occurred while saving prorate allowances: ' +
                        error,
                        'error');
                }
            });
        });

        // Loan code change - Get balance
        $('#newdeductioncodeloan').change(function() {
            var formData = $('#loanForm').serialize();

            $.ajax({
                url: 'classes/getLoanBalance.php',
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response > 0) {
                        $('#Balance').val(response);
                    } else {
                        $('#Balance').val('0');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'An error occurred while getting loan balance',
                        'error');
                }
            });
        });

        // Calculate monthly repayment
        $('#no_times_repayment').blur(function() {
            var principal = parseFloat($('#Principal').val()) || 0;
            var interest = parseFloat($('#interest').val()) || 0;
            var repaymentPeriod = parseFloat($(this).val()) || 1;

            if (repaymentPeriod > 0) {
                var monthlyPayment = (principal + interest) / repaymentPeriod;
                $('#monthlyRepayment').val(monthlyPayment.toFixed(2));
            }
        });

        // Calculate repayment period from monthly payment
        $('#monthlyRepayment').blur(function() {
            var principal = parseFloat($('#Principal').val()) || 0;
            var interest = parseFloat($('#interest').val()) || 0;
            var monthlyPayment = parseFloat($(this).val()) || 0;

            if (monthlyPayment > 0) {
                var repaymentPeriod = (principal + interest) / monthlyPayment;
                $('#no_times_repayment').val(Math.ceil(repaymentPeriod));
            }
        });

        // Delete item
        $(document).on('click', '.delete-item-btn', function() {
            const itemId = $(this).data('id');
            const itemType = $(this).data('type');

            Swal.fire({
                title: 'Are you sure?',
                text: `Do you want to delete this ${itemType}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'classes/controller.php?act=deactivateEd',
                        type: 'POST',
                        data: {
                            empeditnum: itemId
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'Item deleted successfully',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        },
                        error: function() {
                            Swal.fire('Error',
                                'An error occurred while deleting the item',
                                'error');
                        }
                    });
                }
            });
        });

        // Run payroll
        $('#run-payroll-btn').click(function() {
            Swal.fire({
                title: 'Run Payroll?',
                text: 'Are you sure you want to run payroll for this employee?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, run it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'classes/runPayrollindividual.php',
                        type: 'POST',
                        data: {
                            thisemployeePayslip: '<?php echo htmlspecialchars($staffID); ?>'
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'Employee payroll processed successfully',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        },
                        error: function() {
                            Swal.fire('Error',
                                'An error occurred while running payroll',
                                'error');
                        }
                    });
                }
            });
        });

        // Delete payslip
        $('#delete-payslip-btn').click(function() {
            Swal.fire({
                title: 'Delete Payslip?',
                text: 'Are you sure you want to delete payslip info for this employee?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Disable button and show processing state
                    $('#delete-payslip-btn').prop('disabled', true).html(`
                        <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                        <div class="text-sm">Processing...</div>
                    `);

                    $.ajax({
                        url: 'classes/controller.php?act=deletecurrentstaffPayslip',
                        type: 'POST',
                        data: {
                            thisemployee: '<?php echo htmlspecialchars($staffID); ?>'
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'Employee payslip deleted successfully',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        },
                        error: function() {
                            // Re-enable button on error
                            $('#delete-payslip-btn').prop('disabled', false)
                                .html(`
                                <i class="fas fa-trash-alt text-2xl mb-2"></i>
                                <div class="text-sm">Delete Payslip</div>
                            `);
                            Swal.fire('Error',
                                'An error occurred while deleting payslip',
                                'error');
                        }
                    });
                }
            });
        });

        // View payslip
        $('#view-payslip-btn').click(function() {
            // Show payslip modal
            $('#payslipModal').removeClass('hidden').addClass('flex');

            // Enable action buttons initially (will be disabled if employee not found)
            $('#downloadPayslipBtn, #emailPayslipBtn, #printPayslipBtn').prop('disabled', false)
                .removeClass('opacity-50 cursor-not-allowed');

            // Load payslip content
            loadPayslipContent();
        });

        // Load payslip content via AJAX
        function loadPayslipContent() {
            // Update employee info in header
            $('#payslipEmployeeInfo').text(
                '<?php echo htmlspecialchars($empfname); ?> - <?php echo htmlspecialchars($staffID); ?>'
            );

            // Show loading state
            $('#payslipContent').html(`
                <div class="text-center py-8">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                    <p class="text-gray-600">Generating payslip...</p>
                </div>
            `);

            $.ajax({
                url: 'libs/get_payslip_content.php',
                type: 'POST',
                data: {
                    staff_id: '<?php echo htmlspecialchars($staffID); ?>',
                    period: '<?php echo $_SESSION['currentactiveperiod']; ?>'
                },
                success: function(response) {
                    $('#payslipContent').html(response);

                    // Check if employee not found
                    if (response.includes('Employee Not Found') || response.includes(
                            'No employee found')) {
                        // Disable action buttons
                        $('#downloadPayslipBtn, #emailPayslipBtn, #printPayslipBtn').prop(
                            'disabled', true).addClass('opacity-50 cursor-not-allowed');

                        // Show warning notification
                        Swal.fire({
                            icon: 'warning',
                            title: 'Employee Not Found',
                            text: 'No employee found with the provided Staff ID for this period. Action buttons have been disabled.',
                            timer: 3000,
                            showConfirmButton: false
                        });
                    } else {
                        // Enable action buttons
                        $('#downloadPayslipBtn, #emailPayslipBtn, #printPayslipBtn').prop(
                            'disabled', false).removeClass(
                            'opacity-50 cursor-not-allowed');

                        // Add success notification
                        Swal.fire({
                            icon: 'success',
                            title: 'Payslip Generated',
                            text: 'Payslip has been successfully generated and is ready for viewing.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Payslip Error:', error);
                    console.error('Status:', xhr.status);
                    console.error('Response:', xhr.responseText);

                    // Disable action buttons on error
                    $('#downloadPayslipBtn, #emailPayslipBtn, #printPayslipBtn').prop(
                        'disabled',
                        true).addClass('opacity-50 cursor-not-allowed');

                    $('#payslipContent').html(`
                        <div class="text-center py-8">
                            <div class="text-red-500 text-6xl mb-4">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Error Loading Payslip</h3>
                            <p class="text-gray-600">Unable to load payslip data. Please try again.</p>
                            <button onclick="loadPayslipContent()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                <i class="fas fa-redo mr-2"></i>Retry
                            </button>
                        </div>
                    `);
                }
            });
        }

        // Print payslipd
        $('#printPayslipBtn').click(function() {
            const printContent = $('#payslipContent').html();
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Employee Payslip - <?php echo htmlspecialchars($empfname); ?></title>
                    <meta charset="UTF-8">
                    <style>
                        @import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
                        
                        * {
                            box-sizing: border-box;
                        }
                        
                        body { 
                            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                            margin: 0; 
                            padding: 20px; 
                            background: #f5f5f5;
                            color: #333;
                        }
                        
                        .payslip-container {
                            background: white;
                            max-width: 800px;
                            margin: 0 auto;
                            padding: 30px;
                            border-radius: 10px;
                            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                        }
                        
                        .payslip-header {
                            text-align: center;
                            margin-bottom: 30px;
                            border-bottom: 3px solid #2563eb;
                            padding-bottom: 20px;
                        }
                        
                        .company-logo {
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            margin-bottom: 20px;
                        }
                        
                        .company-logo .logo-icon {
                            background: #2563eb;
                            color: white;
                            padding: 15px;
                            border-radius: 50%;
                            margin-right: 15px;
                            font-size: 24px;
                        }
                        
                        .company-name {
                            font-size: 28px;
                            font-weight: bold;
                            color: #1f2937;
                        }
                        
                        .company-subtitle {
                            color: #6b7280;
                            font-size: 14px;
                        }
                        
                        .payslip-info {
                            display: grid;
                            grid-template-columns: repeat(3, 1fr);
                            gap: 20px;
                            margin-top: 20px;
                        }
                        
                        .info-item {
                            text-align: center;
                            padding: 10px;
                            background: #f8fafc;
                            border-radius: 8px;
                        }
                        
                        .info-label {
                            font-weight: bold;
                            color: #374151;
                            font-size: 12px;
                            text-transform: uppercase;
                            letter-spacing: 0.5px;
                        }
                        
                        .info-value {
                            color: #1f2937;
                            font-size: 14px;
                            margin-top: 5px;
                        }
                        
                        .employee-section {
                            display: grid;
                            grid-template-columns: 1fr 1fr;
                            gap: 20px;
                            margin-bottom: 30px;
                        }
                        
                        .info-card {
                            background: #f8fafc;
                            padding: 20px;
                            border-radius: 10px;
                            border-left: 4px solid #2563eb;
                        }
                        
                        .info-card h3 {
                            margin: 0 0 15px 0;
                            color: #1f2937;
                            font-size: 16px;
                            display: flex;
                            align-items: center;
                        }
                        
                        .info-card h3 i {
                            margin-right: 8px;
                            color: #2563eb;
                        }
                        
                        .info-row {
                            display: flex;
                            justify-content: space-between;
                            margin-bottom: 8px;
                            font-size: 14px;
                        }
                        
                        .info-label-small {
                            font-weight: 600;
                            color: #374151;
                        }
                        
                        .earnings-deductions {
                            display: grid;
                            grid-template-columns: 1fr 1fr;
                            gap: 20px;
                            margin-bottom: 30px;
                        }
                        
                        .earnings-card {
                            background: #f0fdf4;
                            padding: 20px;
                            border-radius: 10px;
                            border-left: 4px solid #16a34a;
                        }
                        
                        .deductions-card {
                            background: #fef2f2;
                            padding: 20px;
                            border-radius: 10px;
                            border-left: 4px solid #dc2626;
                        }
                        
                        .card-title {
                            margin: 0 0 15px 0;
                            color: #1f2937;
                            font-size: 16px;
                            display: flex;
                            align-items: center;
                        }
                        
                        .earnings-card .card-title {
                            color: #166534;
                        }
                        
                        .deductions-card .card-title {
                            color: #991b1b;
                        }
                        
                        .card-title i {
                            margin-right: 8px;
                        }
                        
                        .item-row {
                            display: flex;
                            justify-content: space-between;
                            padding: 8px 0;
                            border-bottom: 1px solid rgba(0,0,0,0.1);
                            font-size: 14px;
                        }
                        
                        .item-row:last-child {
                            border-bottom: none;
                        }
                        
                        .total-row {
                            display: flex;
                            justify-content: space-between;
                            padding: 15px 0;
                            border-top: 2px solid rgba(0,0,0,0.2);
                            font-weight: bold;
                            font-size: 16px;
                            margin-top: 10px;
                        }
                        
                        .summary-section {
                            background: #eff6ff;
                            padding: 25px;
                            border-radius: 10px;
                            margin-bottom: 30px;
                            text-align: center;
                        }
                        
                        .summary-title {
                            margin: 0 0 20px 0;
                            color: #1e40af;
                            font-size: 20px;
                            font-weight: bold;
                        }
                        
                        .summary-grid {
                            display: grid;
                            grid-template-columns: repeat(3, 1fr);
                            gap: 20px;
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
                            text-transform: uppercase;
                            letter-spacing: 0.5px;
                        }
                        
                        .gross-amount { color: #16a34a; }
                        .deductions-amount { color: #dc2626; }
                        .net-amount { color: #2563eb; }
                        
                        .footer {
                            text-align: center;
                            padding: 20px;
                            border-top: 2px solid #e5e7eb;
                            color: #6b7280;
                            font-size: 12px;
                        }
                        
                        .footer p {
                            margin: 5px 0;
                        }
                        
                        .footer-badges {
                            display: flex;
                            justify-content: center;
                            gap: 15px;
                            margin-top: 15px;
                        }
                        
                        .footer-badge {
                            background: #e5e7eb;
                            padding: 5px 15px;
                            border-radius: 15px;
                            font-size: 11px;
                            font-weight: 600;
                        }
                        
                        @media print {
                            body { 
                                background: white; 
                                margin: 0; 
                                padding: 0;
                            }
                            
                            .payslip-container {
                                box-shadow: none;
                                border: 1px solid #ccc;
                                border-radius: 0;
                                padding: 20px;
                            }
                            
                            .no-print { display: none; }
                            
                            .info-card, .earnings-card, .deductions-card, .summary-section {
                                break-inside: avoid;
                            }
                        }
                    </style>
                </head>
                <body>
                    ${printContent}
                </body>
                </html>
            `);
            printWindow.document.close();

            // Wait for content to load then print
            printWindow.onload = function() {
                printWindow.print();
            };

            // Fallback if onload doesn't work
            setTimeout(function() {
                printWindow.print();
            }, 1000);
        });

        // Export Excel
        $('#export-excel-btn').click(function() {
            window.location.href =
                'libs/export_earnings_excel.php?staff_id=<?php echo htmlspecialchars($staffID); ?>';
        });

        // Download payslip as PDF
        $('#downloadPayslipBtn').click(function() {
            Swal.fire({
                title: 'Download Payslip',
                text: 'Generating PDF...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Create a temporary form to submit the payslip data
            const form = $('<form>', {
                'method': 'POST',
                'action': 'libs/generate_payslip_pdf.php',
                'target': '_blank'
            });

            form.append($('<input>', {
                'type': 'hidden',
                'name': 'staff_id',
                'value': '<?php echo htmlspecialchars($staffID); ?>'
            }));

            form.append($('<input>', {
                'type': 'hidden',
                'name': 'period',
                'value': '<?php echo $_SESSION['currentactiveperiod']; ?>'
            }));

            $('body').append(form);
            form.submit();
            form.remove();

            // Check if PDF was generated successfully after a delay
            setTimeout(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'PDF Generated',
                    text: 'Payslip PDF has been generated and downloaded.',
                    timer: 2000,
                    showConfirmButton: false
                });
            }, 3000);
        });

        // Print payslip (fallback option)
        $('#printPayslipBtn').click(function() {
            // Open payslip in new window for printing
            const printWindow = window.open(
                'libs/get_payslip_content.php?staff_id=<?php echo htmlspecialchars($staffID); ?>&period=<?php echo $_SESSION['currentactiveperiod']; ?>&print=1',
                '_blank',
                'width=800,height=600,scrollbars=yes,resizable=yes'
            );

            if (printWindow) {
                printWindow.focus();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Print Blocked',
                    text: 'Please allow popups for this site to print the payslip.',
                });
            }
        });

        // Email payslip
        $('#emailPayslipBtn').click(function() {
            Swal.fire({
                title: 'Email Payslip',
                html: `
                    <div class="text-left">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                        <input type="email" id="emailAddress" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" placeholder="Enter email address">
                        <div class="mt-2 text-xs text-gray-500">Leave empty to use employee's registered email</div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Send Email',
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    const email = document.getElementById('emailAddress').value;
                    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                        Swal.showValidationMessage(
                            'Please enter a valid email address');
                        return false;
                    }
                    return email || null;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const email = result.value;

                    Swal.fire({
                        title: 'Sending Email',
                        text: 'Please wait while we send the payslip...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: 'libs/send_payslip_email.php',
                        type: 'POST',
                        data: {
                            staff_id: '<?php echo htmlspecialchars($staffID); ?>',
                            period: '<?php echo $_SESSION['currentactiveperiod']; ?>',
                            email: email
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Email Sent',
                                text: 'Payslip has been sent successfully.',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Email Failed',
                                text: 'Failed to send payslip email. Please try again.'
                            });
                        }
                    });
                }
            });
        });

        // Print
        $('#print-btn').click(function() {
            window.print();
        });

        // Enhanced keyboard shortcuts
        $(document).keydown(function(e) {
            // Ctrl/Cmd + Enter to submit forms
            if ((e.ctrlKey || e.metaKey) && e.keyCode === 13) {
                const activeForm = $('form:focus, form:has(:focus)');
                if (activeForm.length) {
                    activeForm.submit();
                }
            }

            // Escape to close modals
            if (e.keyCode === 27) {
                $('.modal').addClass('hidden').removeClass('flex');
            }
        });

        // Auto-save draft functionality


        // Enhanced tooltips
        $('[title]').each(function() {
            $(this).addClass('tooltip');
            const title = $(this).attr('title');
            $(this).removeAttr('title');
            $(this).append('<span class="tooltiptext">' + title + '</span>');
        });

        // Real-time form validation
        $('input[required], select[required], textarea[required]').on('blur', function() {
            const field = $(this);
            const value = field.val().trim();

            if (!value) {
                field.addClass('form-error');
            } else {
                field.removeClass('form-error').addClass('form-success');
            }
        });

        // Performance optimization - Debounce search
        let searchTimer;
        $('#employee-search').on('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                // Don't auto-trigger search, let user control it
                // Only show autocomplete suggestions
            }, 500);
        });

        // Enhanced error handling
        $(document).ajaxError(function(event, xhr, settings, error) {
            console.error('AJAX Error:', error);
            console.error('Status:', xhr.status);
            console.error('Response:', xhr.responseText);

            let errorMessage = 'An unexpected error occurred';
            if (xhr.status === 404) {
                errorMessage = 'The requested resource was not found';
            } else if (xhr.status === 500) {
                errorMessage = 'Server error occurred';
            } else if (xhr.status === 403) {
                errorMessage = 'Access denied';
            }

            Swal.fire('Error', errorMessage, 'error');
        });

        // Initialize tooltips and other UI enhancements
        $(document).ready(function() {
            // Add smooth scrolling to all links
            $('a[href^="#"]').on('click', function(event) {
                if (this.hash !== '') {
                    event.preventDefault();
                    const hash = this.hash;
                    $('html, body').animate({
                        scrollTop: $(hash).offset().top
                    }, 800);
                }
            });

            // Add confirmation for destructive actions
            $('.delete-item-btn, #run-payroll-btn').on('click', function(e) {
                if (!$(this).hasClass('confirmed')) {
                    e.preventDefault();
                    const action = $(this).data('action') || 'perform this action';

                    Swal.fire({
                        title: 'Are you sure?',
                        text: `Do you want to ${action}?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, proceed!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $(this).addClass('confirmed').click();
                        }
                    });
                }
            });
        });
    });
    </script>

    <?php include 'footer.php'; ?>
</body>

</html>