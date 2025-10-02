<?php
session_start();
ini_set('max_execution_time', '0');
include_once('../classes/model.php');
require_once('Connections/paymaster.php');

// Check session
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
    header("location: ../index.php");
    exit();
}

// Get SQL Value String function
if (!function_exists("GetSQLValueString")) {
    function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "")
    {
        global $con;
        $theValue = function_exists("mysql_real_escape_string") ?
            mysqli_real_escape_string($con, $theValue) : mysqli_escape_string($con, $theValue);

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

// Function to get default period
function getDefaultPeriod($conn) {
    $query = $conn->prepare("
        SELECT periodId, CONCAT(description,' - ', periodYear) as description
        FROM payperiods 
        WHERE periodid = (
            SELECT MAX(periodid)
            FROM payperiods 
            WHERE active = 0
        )
    ");
    $query->execute();
    return $query->fetch(PDO::FETCH_ASSOC);
}

// Get default period
$defaultPeriod = getDefaultPeriod($conn);
$periodFrom = isset($_GET['periodFrom']) ? $_GET['periodFrom'] : $defaultPeriod['periodId'];
$periodTo = isset($_GET['periodTo']) ? $_GET['periodTo'] : $defaultPeriod['periodId'];

// Get period description for display
function getPeriodDescription($conn, $periodId) {
    $query = $conn->prepare("
        SELECT CONCAT(description,' - ', periodYear) as description
        FROM payperiods 
        WHERE periodId = ?
    ");
    $query->execute([$periodId]);
    $result = $query->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['description'] : '';
}

$periodFromDesc = getPeriodDescription($conn, $periodFrom);
$periodToDesc = getPeriodDescription($conn, $periodTo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Report Generator - OOUTH Salary Manager</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    
    <!-- Custom Styles -->
    <style>
        .form-card {
            transition: all 0.3s ease;
        }
        .form-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-success {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            transition: all 0.3s ease;
        }
        .btn-success:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(67, 233, 123, 0.4);
        }
        .btn-warning {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            transition: all 0.3s ease;
        }
        .btn-warning:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(250, 112, 154, 0.4);
        }
        .loading {
            display: none;
        }
        .loading.show {
            display: inline-block;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen flex flex-col">
    <?php include('../header.php'); ?>

    <div class="flex-1 p-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-table text-blue-600 mr-3"></i>
                        Payroll Report Generator
                    </h1>
                    <p class="text-gray-600">Generate comprehensive payroll reports for selected periods</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500">Current Period</p>
                    <p class="font-semibold text-gray-700"><?php echo htmlspecialchars($_SESSION['activeperiodDescription'] ?? 'No Active Period'); ?></p>
                </div>
            </div>
        </div>

        <!-- Breadcrumb -->
        <nav class="flex mb-8" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="../home.php" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                        <i class="fas fa-home mr-2"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                        <a href="index.php" class="text-sm font-medium text-gray-700 hover:text-blue-600">Reports</a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                        <span class="text-sm font-medium text-gray-500">Payroll Report</span>
                    </div>
                </li>
            </ol>
        </nav>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Form Section -->
            <div class="lg:col-span-1">
                <div class="form-card bg-white rounded-xl p-6 shadow-sm">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-cog text-blue-600"></i>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-900">Report Settings</h2>
                    </div>

                    <form id="payrollForm" method="GET" action="">
                        <!-- Period Selection -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-calendar mr-2"></i>
                                Period From
                            </label>
                            <select name="periodFrom" id="periodFrom" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <?php
                                $query = $conn->prepare("SELECT periodId, CONCAT(description,' - ', periodYear) as description FROM payperiods ORDER BY periodId DESC");
                                $query->execute();
                                while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                                    $selected = ($row['periodId'] == $periodFrom) ? 'selected' : '';
                                    echo "<option value='{$row['periodId']}' {$selected}>{$row['description']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-calendar mr-2"></i>
                                Period To
                            </label>
                            <select name="periodTo" id="periodTo" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <?php
                                $query->execute();
                                while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                                    $selected = ($row['periodId'] == $periodTo) ? 'selected' : '';
                                    echo "<option value='{$row['periodId']}' {$selected}>{$row['description']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Action Buttons -->
                        <div class="space-y-3">
                            <button type="submit" class="btn-primary w-full px-6 py-3 text-white font-semibold rounded-lg flex items-center justify-center">
                                <i class="fas fa-search mr-2"></i>
                                Generate Report
                                <i class="fas fa-spinner fa-spin ml-2 loading"></i>
                            </button>

                            <button type="button" onclick="exportToExcel()" class="btn-success w-full px-6 py-3 text-white font-semibold rounded-lg flex items-center justify-center">
                                <i class="fas fa-file-excel mr-2"></i>
                                Export to Excel
                                <i class="fas fa-spinner fa-spin ml-2 loading"></i>
                            </button>

                            <button type="button" onclick="exportToPDF()" class="btn-warning w-full px-6 py-3 text-white font-semibold rounded-lg flex items-center justify-center">
                                <i class="fas fa-file-pdf mr-2"></i>
                                Export to PDF
                                <i class="fas fa-spinner fa-spin ml-2 loading"></i>
                            </button>
                        </div>
                    </form>

                    <!-- Email Section -->
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-envelope mr-2"></i>
                            Email Report
                        </h3>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                            <input type="email" id="emailAddress" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter email address">
                        </div>

                        <button type="button" onclick="sendEmail()" class="w-full px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg flex items-center justify-center transition-colors">
                            <i class="fas fa-paper-plane mr-2"></i>
                            Send Report
                            <i class="fas fa-spinner fa-spin ml-2 loading"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Results Section -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-semibold text-gray-900">
                                <i class="fas fa-chart-bar mr-2"></i>
                                Payroll Report Results
                            </h2>
                            <div class="text-sm text-gray-500">
                                <?php if ($periodFromDesc && $periodToDesc): ?>
                                    <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full">
                                        <?php echo htmlspecialchars($periodFromDesc); ?> to <?php echo htmlspecialchars($periodToDesc); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        <div id="resultsContainer">
                            <!-- Results will be loaded here -->
                            <div class="text-center py-12">
                                <i class="fas fa-chart-line text-gray-400 text-6xl mb-4"></i>
                                <p class="text-gray-500 text-lg">Select periods and generate report to view results</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include('../footer.php'); ?>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            let dataTable = null;

            // Form submission
            $('#payrollForm').on('submit', function(e) {
                e.preventDefault();
                generateReport();
            });

            // Generate report function
            function generateReport() {
                const periodFrom = $('#periodFrom').val();
                const periodTo = $('#periodTo').val();

                if (!periodFrom || !periodTo) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Missing Information',
                        text: 'Please select both periods before generating the report.',
                    });
                    return;
                }

                // Show loading
                $('.loading').addClass('show');
                $('#resultsContainer').html(`
                    <div class="text-center py-12">
                        <i class="fas fa-spinner fa-spin text-blue-600 text-4xl mb-4"></i>
                        <p class="text-gray-600">Generating report...</p>
                    </div>
                `);

                // AJAX request
                $.ajax({
                    url: 'payrollTable.php',
                    method: 'GET',
                    data: {
                        periodFrom: periodFrom,
                        periodTo: periodTo,
                        action: 'generate'
                    },
                    success: function(response) {
                        $('.loading').removeClass('show');
                        
                        // Parse response and display results
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                displayResults(data.data);
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.message || 'Failed to generate report',
                                });
                            }
                        } catch (e) {
                            // If response is HTML, display it directly
                            $('#resultsContainer').html(response);
                        }
                    },
                    error: function() {
                        $('.loading').removeClass('show');
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to generate report. Please try again.',
                        });
                    }
                });
            }

            // Display results function
            function displayResults(data) {
                if (data && data.length > 0) {
                    let tableHtml = `
                        <div class="overflow-x-auto">
                            <table id="payrollTable" class="w-full table-auto">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Staff ID</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Basic Salary</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Allowances</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deductions</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net Pay</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                    `;

                    data.forEach(row => {
                        tableHtml += `
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-900">${row.staff_id || ''}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">${row.name || ''}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">${row.department || ''}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">₦${parseFloat(row.basic_salary || 0).toLocaleString()}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">₦${parseFloat(row.allowances || 0).toLocaleString()}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">₦${parseFloat(row.deductions || 0).toLocaleString()}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-green-600">₦${parseFloat(row.net_pay || 0).toLocaleString()}</td>
                            </tr>
                        `;
                    });

                    tableHtml += `
                                </tbody>
                            </table>
                        </div>
                    `;

                    $('#resultsContainer').html(tableHtml);

                    // Initialize DataTable
                    if (dataTable) {
                        dataTable.destroy();
                    }
                    dataTable = $('#payrollTable').DataTable({
                        responsive: true,
                        pageLength: 25,
                        order: [[0, 'asc']],
                        language: {
                            search: "Search:",
                            lengthMenu: "Show _MENU_ entries per page",
                            info: "Showing _START_ to _END_ of _TOTAL_ entries",
                            paginate: {
                                first: "First",
                                last: "Last",
                                next: "Next",
                                previous: "Previous"
                            }
                        }
                    });
                } else {
                    $('#resultsContainer').html(`
                        <div class="text-center py-12">
                            <i class="fas fa-exclamation-triangle text-yellow-500 text-4xl mb-4"></i>
                            <p class="text-gray-600">No data found for the selected periods.</p>
                        </div>
                    `);
                }
            }
        });

        // Export functions
        function exportToExcel() {
            const periodFrom = $('#periodFrom').val();
            const periodTo = $('#periodTo').val();
            
            if (!periodFrom || !periodTo) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Information',
                    text: 'Please select both periods before exporting.',
                });
                return;
            }

            $('.loading').addClass('show');
            window.location.href = `payrollExporter.php?periodFrom=${periodFrom}&periodTo=${periodTo}&format=excel`;
            setTimeout(() => $('.loading').removeClass('show'), 2000);
        }

        function exportToPDF() {
            const periodFrom = $('#periodFrom').val();
            const periodTo = $('#periodTo').val();
            
            if (!periodFrom || !periodTo) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Information',
                    text: 'Please select both periods before exporting.',
                });
                return;
            }

            $('.loading').addClass('show');
            window.location.href = `payrollExporter.php?periodFrom=${periodFrom}&periodTo=${periodTo}&format=pdf`;
            setTimeout(() => $('.loading').removeClass('show'), 2000);
        }

        function sendEmail() {
            const email = $('#emailAddress').val();
            const periodFrom = $('#periodFrom').val();
            const periodTo = $('#periodTo').val();
            
            if (!email) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Email',
                    text: 'Please enter an email address.',
                });
                return;
            }

            if (!periodFrom || !periodTo) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Information',
                    text: 'Please select both periods before sending.',
                });
                return;
            }

            $('.loading').addClass('show');

            $.ajax({
                url: 'payrollExporter.php',
                method: 'POST',
                data: {
                    email: email,
                    periodFrom: periodFrom,
                    periodTo: periodTo,
                    action: 'email'
                },
                success: function(response) {
                    $('.loading').removeClass('show');
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Email Sent',
                                text: 'Report has been sent to your email address.',
                            });
                            $('#emailAddress').val('');
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Failed to send email',
                            });
                        }
                    } catch (e) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to send email. Please try again.',
                        });
                    }
                },
                error: function() {
                    $('.loading').removeClass('show');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to send email. Please try again.',
                    });
                }
            });
        }
    </script>
</body>
</html>