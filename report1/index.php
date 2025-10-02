<?php
require_once('../Connections/paymaster.php');
session_start();

//Check whether the session variable SESS_MEMBER_ID is present or not
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
	header("location: ../index.php");
	exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports Dashboard - OOUTH Salary Manager</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom Styles -->
    <style>
        .report-card {
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }
        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-color: #3b82f6;
        }
        .report-icon {
            transition: all 0.3s ease;
        }
        .report-card:hover .report-icon {
            transform: scale(1.1);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .gradient-bg-2 {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .gradient-bg-3 {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .gradient-bg-4 {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        .gradient-bg-5 {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        .gradient-bg-6 {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        }
        .gradient-bg-7 {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
        }
        .gradient-bg-8 {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen flex flex-col">
    <?php include('../header.php'); ?>

    <div class="flex-1 flex">
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-lg hidden md:block">
            <div class="p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Reports Menu</h2>
                <nav class="space-y-2">
                    <a href="../home.php" class="flex items-center px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors">
                        <i class="fas fa-home mr-3"></i>
                        Dashboard
                    </a>
                    <a href="payrollTable.php" class="flex items-center px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors">
                        <i class="fas fa-table mr-3"></i>
                        Payroll Table
                    </a>
                    <a href="employee_report.php" class="flex items-center px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors">
                        <i class="fas fa-users mr-3"></i>
                        Employee Report
                    </a>
                    <a href="banksummary.php" class="flex items-center px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors">
                        <i class="fas fa-university mr-3"></i>
                        Bank Summary
                    </a>
                    <a href="deductionlist.php" class="flex items-center px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors">
                        <i class="fas fa-list mr-3"></i>
                        Deduction List
                    </a>
                    <a href="gross.php" class="flex items-center px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors">
                        <i class="fas fa-calculator mr-3"></i>
                        Gross Amount
                    </a>
                    <a href="net2bank.php" class="flex items-center px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors">
                        <i class="fas fa-exchange-alt mr-3"></i>
                        Net to Bank
                    </a>
                    <a href="payslip_personal.php" class="flex items-center px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors">
                        <i class="fas fa-file-invoice mr-3"></i>
                        Payslip
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8">
            <!-- Page Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">
                            <i class="fas fa-chart-bar text-blue-600 mr-3"></i>
                            Reports Dashboard
                        </h1>
                        <p class="text-gray-600">Generate and view comprehensive payroll reports</p>
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
                            <span class="text-sm font-medium text-gray-500">Reports</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <!-- Reports Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <!-- Payroll Report -->
                <div class="report-card bg-white rounded-xl p-6 cursor-pointer" onclick="window.location.href='payrollTable.php'">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 gradient-bg rounded-lg flex items-center justify-center">
                            <i class="fas fa-table text-white text-xl report-icon"></i>
                        </div>
                        <i class="fas fa-arrow-right text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Payroll Report</h3>
                    <p class="text-gray-600 text-sm mb-4">Comprehensive payroll data with detailed breakdowns</p>
                    <div class="flex items-center text-sm text-blue-600">
                        <span>View Report</span>
                        <i class="fas fa-chevron-right ml-2"></i>
                    </div>
                </div>

                <!-- Employee Report -->
                <div class="report-card bg-white rounded-xl p-6 cursor-pointer" onclick="window.location.href='employee_report.php'">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 gradient-bg-2 rounded-lg flex items-center justify-center">
                            <i class="fas fa-users text-white text-xl report-icon"></i>
                        </div>
                        <i class="fas fa-arrow-right text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Employee Report</h3>
                    <p class="text-gray-600 text-sm mb-4">Employee information and statistics</p>
                    <div class="flex items-center text-sm text-blue-600">
                        <span>View Report</span>
                        <i class="fas fa-chevron-right ml-2"></i>
                    </div>
                </div>

                <!-- Bank Summary -->
                <div class="report-card bg-white rounded-xl p-6 cursor-pointer" onclick="window.location.href='banksummary.php'">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 gradient-bg-3 rounded-lg flex items-center justify-center">
                            <i class="fas fa-university text-white text-xl report-icon"></i>
                        </div>
                        <i class="fas fa-arrow-right text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Bank Summary</h3>
                    <p class="text-gray-600 text-sm mb-4">Bank transfer summaries and details</p>
                    <div class="flex items-center text-sm text-blue-600">
                        <span>View Report</span>
                        <i class="fas fa-chevron-right ml-2"></i>
                    </div>
                </div>

                <!-- Deduction List -->
                <div class="report-card bg-white rounded-xl p-6 cursor-pointer" onclick="window.location.href='deductionlist.php'">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 gradient-bg-4 rounded-lg flex items-center justify-center">
                            <i class="fas fa-list text-white text-xl report-icon"></i>
                        </div>
                        <i class="fas fa-arrow-right text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Deduction List</h3>
                    <p class="text-gray-600 text-sm mb-4">Complete list of salary deductions</p>
                    <div class="flex items-center text-sm text-blue-600">
                        <span>View Report</span>
                        <i class="fas fa-chevron-right ml-2"></i>
                    </div>
                </div>

                <!-- Gross Amount -->
                <div class="report-card bg-white rounded-xl p-6 cursor-pointer" onclick="window.location.href='gross.php'">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 gradient-bg-5 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calculator text-white text-xl report-icon"></i>
                        </div>
                        <i class="fas fa-arrow-right text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Gross Amount</h3>
                    <p class="text-gray-600 text-sm mb-4">Gross salary calculations and reports</p>
                    <div class="flex items-center text-sm text-blue-600">
                        <span>View Report</span>
                        <i class="fas fa-chevron-right ml-2"></i>
                    </div>
                </div>

                <!-- Net to Bank -->
                <div class="report-card bg-white rounded-xl p-6 cursor-pointer" onclick="window.location.href='net2bank.php'">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 gradient-bg-6 rounded-lg flex items-center justify-center">
                            <i class="fas fa-exchange-alt text-white text-xl report-icon"></i>
                        </div>
                        <i class="fas fa-arrow-right text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Net to Bank</h3>
                    <p class="text-gray-600 text-sm mb-4">Net salary transfers to bank accounts</p>
                    <div class="flex items-center text-sm text-blue-600">
                        <span>View Report</span>
                        <i class="fas fa-chevron-right ml-2"></i>
                    </div>
                </div>

                <!-- Payslip -->
                <div class="report-card bg-white rounded-xl p-6 cursor-pointer" onclick="window.location.href='payslip_personal.php'">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 gradient-bg-7 rounded-lg flex items-center justify-center">
                            <i class="fas fa-file-invoice text-white text-xl report-icon"></i>
                        </div>
                        <i class="fas fa-arrow-right text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Payslip</h3>
                    <p class="text-gray-600 text-sm mb-4">Individual employee payslips</p>
                    <div class="flex items-center text-sm text-blue-600">
                        <span>View Report</span>
                        <i class="fas fa-chevron-right ml-2"></i>
                    </div>
                </div>

                <!-- Tax Export -->
                <div class="report-card bg-white rounded-xl p-6 cursor-pointer" onclick="window.location.href='tax_returns.php'">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 gradient-bg-8 rounded-lg flex items-center justify-center">
                            <i class="fas fa-file-export text-white text-xl report-icon"></i>
                        </div>
                        <i class="fas fa-arrow-right text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Tax Export</h3>
                    <p class="text-gray-600 text-sm mb-4">Tax computation and export reports</p>
                    <div class="flex items-center text-sm text-blue-600">
                        <span>View Report</span>
                        <i class="fas fa-chevron-right ml-2"></i>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="mt-12 grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white rounded-xl p-6 shadow-sm">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-users text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Employees</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo isset($_SESSION['total_employees']) ? $_SESSION['total_employees'] : 'N/A'; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-6 shadow-sm">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Active Period</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo isset($_SESSION['activeperiodDescription']) ? 'Active' : 'None'; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-6 shadow-sm">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-yellow-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Last Updated</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo date('H:i'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-6 shadow-sm">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-chart-line text-purple-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Reports Available</p>
                            <p class="text-2xl font-bold text-gray-900">8</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include('../footer.php'); ?>

    <script>
        // Add loading animation for report cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.report-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });

        // Add click feedback
        document.querySelectorAll('.report-card').forEach(card => {
            card.addEventListener('click', function() {
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 150);
            });
        });
    </script>
</body>
</html>