<?php 
include_once('../classes/model.php');
ini_set('max_execution_time', '0');
session_start();

require_once('../Connections/paymaster.php');
require_once('../libs/App.php');
$App = new App();
$App->checkAuthentication();
require_once('../libs/middleware.php');
checkPermission();

// Initialize variables
$period = isset($_GET['period']) ? $_GET['period'] : $_SESSION['currentactiveperiod'];
$fullPeriod = '';

// Get period information
if ($period != -1) {
    try {
        $query = $conn->prepare('SELECT concat(payperiods.description," ",payperiods.periodYear) as period FROM payperiods WHERE periodId = ?');
        $query->execute([$period]);
        $result = $query->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $fullPeriod = $result['period'];
        }
    } catch (PDOException $e) {
        $fullPeriod = '';
    }
}

// Pagination setup
$results_per_page = 100;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start_from = ($page - 1) * $results_per_page;

// Get employee data
$query = $conn->prepare('SELECT staff_id FROM master_staff WHERE statuscd = ? AND period = ? ORDER BY DEPTCD ASC LIMIT ' . $start_from . ',' . $results_per_page);
$query->execute(['A', $period]);
$ftres = $query->fetchAll(PDO::FETCH_COLUMN);
$count = $query->rowCount();

// Get total employee count for pagination
$total_query = $conn->query('SELECT count(staff_id) as "Total" FROM employee WHERE statuscd = "A"');
$total_row = $total_query->fetch();
$total_pages = ceil($total_row['Total'] / $results_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip All Report - OOUTH Salary Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-form@4.3.0/dist/jquery.form.min.js"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            .payslip-wrapper { page-break-after: always; }
            .payslip-wrapper:last-child { page-break-after: auto; }
        }
        
        .payslip-wrapper {
            width: 300px;
            border: 1px solid #ddd;
            margin: 10px;
            padding: 15px;
            display: inline-block;
            vertical-align: top;
            font-family: Arial, sans-serif;
            font-size: 12px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .payslip-header {
            text-align: center;
            border-bottom: 2px solid #1E40AF;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .payslip-row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
            padding: 2px 0;
        }
        
        .payslip-total {
            border-top: 1px solid #ddd;
            padding-top: 8px;
            margin-top: 8px;
            font-weight: bold;
        }
        
        .payslip-section {
            margin: 10px 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
        }
        
        .payslip-section:last-child {
            border-bottom: none;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background-color: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-fill {
            height: 100%;
            background-color: #1E40AF;
            transition: width 0.3s ease;
        }
        
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <?php include('../header.php'); ?>
    <div class="flex min-h-screen">
        <?php include('../sidebar.php'); ?>
        <main class="flex-1 px-2 md:px-8 py-4 flex flex-col">
            <div class="w-full max-w-7xl mx-auto flex-1 flex flex-col">
                <!-- Header Section -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-blue-800 flex items-center gap-2">
                            <i class="fas fa-file-invoice"></i> Payslip All Report
                        </h1>
                        <p class="text-sm text-blue-700/70 mt-1">Generate individual payslips for all employees in a selected pay period.</p>
                    </div>
                </div>

                <!-- Report Form -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6 no-print">
                    <div class="bg-blue-50 px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold text-blue-800 flex items-center gap-2">
                            <i class="fa fa-filter"></i> Report Parameters
                        </h2>
                    </div>
                    <div class="p-6">
                        <!-- Organization Header -->
                        <div class="text-center mb-6">
                            <img src="img/oouth_logo.gif" alt="OOUTH Logo" class="h-16 mx-auto mb-4">
                            <h3 class="text-lg font-bold text-blue-800 uppercase">OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL</h3>
                            <p class="text-blue-600 font-medium">Payslip Report</p>
                            <?php if ($fullPeriod): ?>
                                <p class="text-sm text-gray-600">For the Month of: <?php echo htmlspecialchars($fullPeriod); ?></p>
                            <?php endif; ?>
                        </div>

                        <form method="GET" action="payslip_all.php" class="space-y-6">
                            <div class="max-w-md">
                                <label for="period" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-calendar-alt mr-2 text-blue-600"></i>Pay Period
                                </label>
                                <select name="period" id="period" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm" required>
                                    <option value="">Select Pay Period</option>
                                    <?php
                                    try {
                                        $query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear, payperiods.periodId FROM payperiods WHERE payrollRun = ? ORDER BY periodId DESC');
                                        $query->execute(['1']);
                                        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                                            $selected = ($row['periodId'] == $period) ? 'selected="selected"' : '';
                                            echo sprintf(
                                                '<option value="%s" %s>%s - %s</option>',
                                                htmlspecialchars($row['periodId']),
                                                $selected,
                                                htmlspecialchars($row['description']),
                                                htmlspecialchars($row['periodYear'])
                                            );
                                        }
                                    } catch (PDOException $e) {
                                        echo "<option value=''>Error loading periods</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <button name="generate_report" type="submit" id="generate_report" class="bg-blue-700 hover:bg-blue-900 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-search"></i> Generate Report
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($period != -1 && $fullPeriod): ?>
                    <!-- Report Controls -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6 no-print">
                        <div class="bg-blue-50 px-6 py-4 border-b">
                            <h2 class="text-lg font-semibold text-blue-800 flex items-center gap-2">
                                <i class="fas fa-tools"></i> Report Controls
                            </h2>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                                <div class="bg-blue-100 p-4 rounded-lg text-center">
                                    <div class="text-2xl font-bold text-blue-800"><?php echo $fullPeriod; ?></div>
                                    <div class="text-sm text-blue-600">Payroll Period</div>
                                </div>
                                <div class="bg-green-100 p-4 rounded-lg text-center">
                                    <div class="text-2xl font-bold text-green-800"><?php echo $count; ?></div>
                                    <div class="text-sm text-green-600">Employees (This Page)</div>
                                </div>
                                <div class="bg-purple-100 p-4 rounded-lg text-center">
                                    <div class="text-2xl font-bold text-purple-800"><?php echo $total_row['Total']; ?></div>
                                    <div class="text-sm text-purple-600">Total Employees</div>
                                </div>
                                <div class="bg-orange-100 p-4 rounded-lg text-center">
                                    <div class="text-2xl font-bold text-orange-800"><?php echo $page; ?> / <?php echo $total_pages; ?></div>
                                    <div class="text-sm text-orange-600">Page</div>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-3 mb-4">
                                <button type="button" id="btnPrint" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-print"></i> Print All Payslips
                                </button>
                                <button type="button" id="sendmail" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-envelope"></i> Send Email to All
                                </button>
                            </div>

                            <!-- Email Progress -->
                            <div id="email-progress" class="hidden">
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-gray-700">Sending emails...</span>
                                        <span id="progress-text" class="text-sm text-gray-500">0%</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div id="progress-fill" class="progress-fill" style="width: 0%"></div>
                                    </div>
                                    <div id="progress-message" class="text-sm text-gray-600 mt-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6 no-print">
                        <div class="p-6">
                            <nav class="flex items-center justify-center">
                                <div class="flex items-center space-x-2">
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <?php $pageClasses = $page == $i ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-blue-800 border-gray-300 hover:bg-blue-100'; ?>
                                        <a href="?page=<?php echo $i; ?>&period=<?php echo $period; ?>" 
                                           class="px-3 py-2 border rounded text-sm font-semibold transition <?php echo $pageClasses; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                </div>
                            </nav>
                        </div>
                    </div>

                    <!-- Payslips Grid -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="bg-blue-50 px-6 py-4 border-b">
                            <h2 class="text-lg font-semibold text-blue-800 flex items-center gap-2">
                                <i class="fas fa-file-invoice"></i> Employee Payslips
                            </h2>
                        </div>
                        <div class="p-6">
                            <div class="grid-container">
                                <?php
                                $counter = 0;
                                while ($counter < $count) {
                                    $thisemployee = $ftres[$counter];
                                    
                                    // Get employee information
                                    try {
                                        $query = $conn->prepare('SELECT
                                                tbl_bank.BNAME,
                                                tbl_dept.dept,
                                                master_staff.STEP,
                                                master_staff.GRADE,
                                                master_staff.staff_id,
                                                master_staff.NAME,
                                                master_staff.ACCTNO,
                                                employee.EMAIL
                                                FROM master_staff
                                                INNER JOIN tbl_dept ON tbl_dept.dept_id = master_staff.DEPTCD
                                                INNER JOIN tbl_bank ON tbl_bank.BCODE = master_staff.BCODE
                                                INNER JOIN employee ON master_staff.staff_id = employee.staff_id 
                                                WHERE master_staff.staff_id = ? AND period = ?');
                                        $query->execute([$thisemployee, $period]);
                                        $row_staff = $query->fetch();
                                    } catch (PDOException $e) {
                                        $counter++;
                                        continue;
                                    }

                                    if (!$row_staff) {
                                        $counter++;
                                        continue;
                                    }

                                    // Get consolidated salary
                                    $consolidated = 0;
                                    try {
                                        $query = $conn->prepare('SELECT tbl_master.staff_id, tbl_master.allow FROM tbl_master WHERE allow_id = ? AND staff_id = ? AND period = ?');
                                        $query->execute(['1', $thisemployee, $period]);
                                        $res_consolidated = $query->fetch();
                                        $consolidated = $res_consolidated ? $res_consolidated['allow'] : 0;
                                    } catch (PDOException $e) {
                                        $consolidated = 0;
                                    }

                                    // Get allowances
                                    $totalAllow = 0;
                                    $allowances = [];
                                    try {
                                        $query = $conn->prepare('SELECT tbl_master.staff_id, tbl_master.allow, tbl_earning_deduction.ed FROM tbl_master INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id WHERE allow_id <> ? AND staff_id = ? AND period = ? AND type = ?');
                                        $query->execute(['1', $thisemployee, $period, '1']);
                                        $allowances = $query->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($allowances as $allowance) {
                                            $totalAllow += floatval($allowance['allow']);
                                        }
                                    } catch (PDOException $e) {
                                        $allowances = [];
                                    }

                                    // Get deductions
                                    $totalDeduction = 0;
                                    $deductions = [];
                                    try {
                                        $query = $conn->prepare('SELECT tbl_master.staff_id, tbl_master.deduc, tbl_earning_deduction.ed FROM tbl_master INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id WHERE staff_id = ? AND period = ? AND type = ?');
                                        $query->execute([$thisemployee, $period, '2']);
                                        $deductions = $query->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($deductions as $deduction_item) {
                                            $totalDeduction += floatval($deduction_item['deduc']);
                                        }
                                    } catch (PDOException $e) {
                                        $deductions = [];
                                    }

                                    $grossSalary = $totalAllow + $consolidated;
                                    $netPay = $grossSalary - $totalDeduction;
                                ?>
                                <div class="payslip-wrapper">
                                    <!-- Payslip Header -->
                                    <div class="payslip-header">
                                        <div class="font-bold text-lg mb-2">OOUTH, SAGAMU</div>
                                        <div class="font-bold">PAYSLIP FOR <?php echo strtoupper($fullPeriod); ?></div>
                                    </div>

                                    <!-- Employee Information -->
                                    <div class="payslip-section">
                                        <div class="payslip-row">
                                            <span><strong>Name:</strong></span>
                                            <span><?php echo htmlspecialchars($row_staff['NAME']); ?></span>
                                        </div>
                                        <div class="payslip-row">
                                            <span><strong>Staff No:</strong></span>
                                            <span><?php echo htmlspecialchars($row_staff['staff_id']); ?></span>
                                        </div>
                                        <div class="payslip-row">
                                            <span><strong>Dept:</strong></span>
                                            <span><?php echo htmlspecialchars($row_staff['dept']); ?></span>
                                        </div>
                                        <div class="payslip-row">
                                            <span><strong>Bank:</strong></span>
                                            <span><?php echo htmlspecialchars($row_staff['BNAME']); ?></span>
                                        </div>
                                        <div class="payslip-row">
                                            <span><strong>Acct No:</strong></span>
                                            <span><?php echo htmlspecialchars($row_staff['ACCTNO']); ?></span>
                                        </div>
                                        <div class="payslip-row">
                                            <span><strong>Grade/Step:</strong></span>
                                            <span><?php echo htmlspecialchars($row_staff['GRADE'] . '/' . $row_staff['STEP']); ?></span>
                                        </div>
                                    </div>

                                    <!-- Consolidated Salary -->
                                    <div class="payslip-section">
                                        <div class="font-bold mb-2">CONSOLIDATED SALARY</div>
                                        <div class="payslip-row">
                                            <span>CONSOLIDATED SALARY:</span>
                                            <span>₦<?php echo number_format($consolidated); ?></span>
                                        </div>
                                    </div>

                                    <!-- Allowances -->
                                    <div class="payslip-section">
                                        <div class="font-bold mb-2 underline">ALLOWANCES</div>
                                        <?php foreach ($allowances as $allowance): ?>
                                        <div class="payslip-row">
                                            <span><?php echo htmlspecialchars($allowance['ed']); ?></span>
                                            <span>₦<?php echo number_format($allowance['allow']); ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                        
                                        <div class="payslip-total">
                                            <span><strong>Gross Salary</strong></span>
                                            <span><strong>₦<?php echo number_format($grossSalary); ?></strong></span>
                                        </div>
                                    </div>

                                    <!-- Deductions -->
                                    <div class="payslip-section">
                                        <div class="font-bold mb-2 underline">DEDUCTIONS</div>
                                        <?php foreach ($deductions as $deduction_item): ?>
                                        <div class="payslip-row">
                                            <span><?php echo htmlspecialchars($deduction_item['ed']); ?></span>
                                            <span>₦<?php echo number_format($deduction_item['deduc']); ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                        
                                        <div class="payslip-total">
                                            <span><strong>Total Deductions</strong></span>
                                            <span><strong>₦<?php echo number_format($totalDeduction); ?></strong></span>
                                        </div>
                                    </div>

                                    <!-- Net Pay -->
                                    <div class="payslip-section">
                                        <div class="payslip-row payslip-total">
                                            <span><strong>Net Pay</strong></span>
                                            <span><strong>₦<?php echo number_format($netPay); ?></strong></span>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                    $counter++;
                                }
                                ?>
                            </div>
                        </div>

                        <!-- Report Footer -->
                        <div class="bg-gray-50 px-6 py-4 border-t no-print">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <div class="text-sm text-gray-600">
                                    <p><strong>Report Generated by:</strong> <?php echo $_SESSION['SESS_FIRST_NAME']; ?></p>
                                    <p><strong>Date:</strong> <?php echo date('l, F d, Y'); ?></p>
                                </div>
                                <div class="text-sm text-gray-600">
                                    <p><strong>Period:</strong> <?php echo htmlspecialchars($fullPeriod); ?></p>
                                    <p><strong>Employees on this page:</strong> <?php echo $count; ?></p>
                                    <p><strong>Page:</strong> <?php echo $page; ?> of <?php echo $total_pages; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Pagination -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden mt-6 no-print">
                        <div class="p-6">
                            <nav class="flex items-center justify-center">
                                <div class="flex items-center space-x-2">
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <?php $pageClasses = $page == $i ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-blue-800 border-gray-300 hover:bg-blue-100'; ?>
                                        <a href="?page=<?php echo $i; ?>&period=<?php echo $period; ?>" 
                                           class="px-3 py-2 border rounded text-sm font-semibold transition <?php echo $pageClasses; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                </div>
                            </nav>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-xl shadow-lg p-6 text-center text-gray-600">
                        <i class="fas fa-info-circle text-4xl text-blue-500 mb-4"></i>
                        <p class="text-lg font-semibold">Please select a Pay Period to generate payslips.</p>
                        <p class="text-sm mt-2">Use the form above to view individual employee payslips.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script type="text/javascript">
        $(document).ready(function() {
            // Print functionality
            $('#btnPrint').click(function() {
                window.print();
            });

            // Send email functionality
            $('#sendmail').click(function(e) {
                e.preventDefault();
                
                if (!$('#period').val()) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Missing Information',
                        text: 'Please select a Pay Period before sending emails.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#1E40AF'
                    });
                    return;
                }

                Swal.fire({
                    title: 'Send Emails to All Employees?',
                    text: 'This will send payslips to all employees in the selected period. This may take a few minutes.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#1E40AF',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, Send Emails',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        sendEmails();
                    }
                });
            });

            function sendEmails() {
                $('#email-progress').removeClass('hidden');
                $('#sendmail').prop('disabled', true);
                
                $('#form_payprocess').ajaxSubmit({
                    data: {
                        staff_no: '',
                        period: $('#period').val(),
                        All: 1
                    },
                    url: 'callPdf.php',
                    xhrFields: {
                        onprogress: function(e) {
                            // Parse progress from response
                            const response = e.target.responseText;
                            const progressMatch = response.match(/(\d+)%/);
                            if (progressMatch) {
                                const progress = progressMatch[1];
                                $('#progress-fill').css('width', progress + '%');
                                $('#progress-text').text(progress + '%');
                            }
                            
                            const messageMatch = response.match(/<div[^>]*>([^<]+)<\/div>/);
                            if (messageMatch) {
                                $('#progress-message').text(messageMatch[1]);
                            }
                        }
                    },
                    success: function(response, message) {
                        $('#email-progress').addClass('hidden');
                        $('#sendmail').prop('disabled', false);
                        
                        if (message == 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Emails Sent Successfully!',
                                text: 'Payslips have been sent to all employees.',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#1E40AF'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error Sending Emails',
                                text: 'There was an error sending the emails. Please try again.',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#1E40AF'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#email-progress').addClass('hidden');
                        $('#sendmail').prop('disabled', false);
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error Sending Emails',
                            text: 'There was an error sending the emails. Please try again.',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#1E40AF'
                        });
                    }
                });
            }

            // Initialize form for AJAX submission
            $('#form_payprocess').ajaxForm();
        });
    </script>
</body>
</html>