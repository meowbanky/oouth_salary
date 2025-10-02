<?php
require_once('../Connections/paymaster.php');
include_once('../classes/model.php');
require_once '../libs/App.php';
$App = new App();
$App->checkAuthentication();
require_once '../libs/middleware.php';
checkPermission();

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) == '') {
    header("location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reports | OOUTH Salary Manager</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    html,
    body {
        overflow-x: hidden;
    }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <?php include('../header.php'); ?>
    <div class="flex min-h-screen">
        <?php include('report_sidebar_modern.php'); ?>
        <main class="flex-1 px-2 md:px-8 py-4 flex flex-col">
            <div class="w-full max-w-7xl mx-auto flex-1 flex flex-col">
                <!-- Header Section -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-blue-800 flex items-center gap-2">
                            <i class="fas fa-chart-bar"></i> Reports Dashboard
                        </h1>
                        <p class="text-sm text-blue-700/70 mt-1">Generate and view various payroll reports and
                            analytics.</p>
                    </div>
                </div>

                <!-- Reports Grid -->
                <div class="grid lg:grid-cols-2 gap-6">
                    <!-- Main Reports List -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="bg-blue-50 px-6 py-4 border-b">
                            <h2 class="text-lg font-semibold text-blue-800 flex items-center gap-2">
                                <i class="fas fa-list"></i> Report Categories
                            </h2>
                        </div>
                        <div class="p-6">
                            <div class="grid gap-3">
                                <a href="#"
                                    class="report-category flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200"
                                    id="sales">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-calculator text-blue-600"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-800">Payroll Reports</h3>
                                        <p class="text-sm text-gray-600">Salary summaries and payroll analytics</p>
                                    </div>
                                </a>

                                <a href="#"
                                    class="report-category flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200"
                                    id="tax_export">
                                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-receipt text-green-600"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-800">Tax Computation</h3>
                                        <p class="text-sm text-gray-600">Tax reports and annual returns</p>
                                    </div>
                                </a>

                                <a href="#"
                                    class="report-category flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200"
                                    id="employee">
                                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-users text-purple-600"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-800">Employee Reports</h3>
                                        <p class="text-sm text-gray-600">Staff information and analytics</p>
                                    </div>
                                </a>

                                <a href="#"
                                    class="report-category flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200"
                                    id="transfer">
                                    <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-university text-yellow-600"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-800">Bank Summary</h3>
                                        <p class="text-sm text-gray-600">Banking and payment summaries</p>
                                    </div>
                                </a>

                                <a href="#"
                                    class="report-category flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200"
                                    id="inventory">
                                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-minus-circle text-red-600"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-800">Deduction Reports</h3>
                                        <p class="text-sm text-gray-600">Salary deductions and lists</p>
                                    </div>
                                </a>

                                <a href="#"
                                    class="report-category flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200"
                                    id="gross">
                                    <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-chart-line text-indigo-600"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-800">Gross Amount</h3>
                                        <p class="text-sm text-gray-600">Gross salary calculations</p>
                                    </div>
                                </a>

                                <a href="#"
                                    class="report-category flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200"
                                    id="expiry-report">
                                    <div class="w-10 h-10 bg-teal-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-search text-teal-600"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-800">Net to Bank</h3>
                                        <p class="text-sm text-gray-600">Net salary bank transfers</p>
                                    </div>
                                </a>

                                <a href="#"
                                    class="report-category flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200"
                                    id="customers">
                                    <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-file-invoice text-orange-600"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-800">Payslip Reports</h3>
                                        <p class="text-sm text-gray-600">Individual and department payslips</p>
                                    </div>
                                </a>

                                <a href="#"
                                    class="report-category flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200"
                                    id="deleted-sales">
                                    <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-piggy-bank text-pink-600"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-800">Pension Fund Admin</h3>
                                        <p class="text-sm text-gray-600">PFA reports and summaries</p>
                                    </div>
                                </a>

                                <a href="#"
                                    class="report-category flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200"
                                    id="discounts">
                                    <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-chart-pie text-gray-600"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-800">Variance Analysis</h3>
                                        <p class="text-sm text-gray-600">Salary variance reports</p>
                                    </div>
                                </a>

                                <a href="#"
                                    class="report-category flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200"
                                    id="log">
                                    <div class="w-10 h-10 bg-cyan-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-history text-cyan-600"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-800">Audit Log</h3>
                                        <p class="text-sm text-gray-600">System activity and changes</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                    <!-- Report Details Panel -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden" id="report_selection">
                        <div class="bg-blue-50 px-6 py-4 border-b">
                            <h2 class="text-lg font-semibold text-blue-800 flex items-center gap-2" id="report_title">
                                <i class="fas fa-arrow-right"></i> Select a Report Category
                            </h2>
                        </div>
                        <div class="p-6">
                            <div class="text-center py-12 text-gray-500" id="default_message">
                                <i class="fas fa-mouse-pointer text-4xl mb-4"></i>
                                <p class="text-lg font-medium">Choose a report category from the left</p>
                                <p class="text-sm">Click on any category to view available reports</p>
                            </div>

                            <!-- Report Options (Hidden by default) -->
                            <div class="hidden" id="report_options">
                                <!-- Net to Bank Reports -->
                                <div class="report-section hidden" id="expiry-report">
                                    <div class="space-y-3">
                                        <a href="net2bank.php"
                                            class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200">
                                            <div
                                                class="w-8 h-8 bg-teal-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-search text-teal-600 text-sm"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-medium text-gray-800">Detailed Amount to Bank</h4>
                                                <p class="text-sm text-gray-600">Complete net salary bank transfer
                                                    details</p>
                                            </div>
                                        </a>
                                    </div>
                                </div>

                                <!-- Payslip Reports -->
                                <div class="report-section hidden" id="customers">
                                    <div class="space-y-3">
                                        <a href="payslip_all.php"
                                            class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200">
                                            <div
                                                class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-file-invoice text-orange-600 text-sm"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-medium text-gray-800">Payslip All</h4>
                                                <p class="text-sm text-gray-600">Generate payslips for all employees</p>
                                            </div>
                                        </a>
                                        <a href="payslip_dept.php"
                                            class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200">
                                            <div
                                                class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-building text-orange-600 text-sm"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-medium text-gray-800">Department Payslips</h4>
                                                <p class="text-sm text-gray-600">Generate payslips by department</p>
                                            </div>
                                        </a>
                                        <a href="payslip_personal.php"
                                            class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200">
                                            <div
                                                class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-user text-orange-600 text-sm"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-medium text-gray-800">Individual Payslip</h4>
                                                <p class="text-sm text-gray-600">Generate payslip for specific employee
                                                </p>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                                <!-- Payroll Reports -->
                                <div class="report-section hidden" id="sales">
                                    <div class="space-y-3">
                                        <a href="payrollsummary_all.php"
                                            class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200">
                                            <div
                                                class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-building text-blue-600 text-sm"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-medium text-gray-800">Payroll Summary All</h4>
                                                <p class="text-sm text-gray-600">Complete payroll summary for all
                                                    employees</p>
                                            </div>
                                        </a>
                                        <a href="payrollDept.php"
                                            class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200">
                                            <div
                                                class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-calendar text-blue-600 text-sm"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-medium text-gray-800">Payroll Summary by Department</h4>
                                                <p class="text-sm text-gray-600">Department-wise payroll summaries</p>
                                            </div>
                                        </a>
                                        <a href="payrollexcel_all.php"
                                            class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200">
                                            <div
                                                class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-file-excel text-blue-600 text-sm"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-medium text-gray-800">Payroll Excel Export</h4>
                                                <p class="text-sm text-gray-600">Export payroll data to Excel format</p>
                                            </div>
                                        </a>
                                        <a href="payrollTable.php"
                                            class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200">
                                            <div
                                                class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-table text-blue-600 text-sm"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-medium text-gray-800">Payroll Table</h4>
                                                <p class="text-sm text-gray-600">Detailed payroll table view</p>
                                            </div>
                                        </a>
                                        <a href="payrollTablebyDept.php"
                                            class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200">
                                            <div
                                                class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-building text-blue-600 text-sm"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-medium text-gray-800">Payroll Table by Department</h4>
                                                <p class="text-sm text-gray-600">Department-wise payroll tables</p>
                                            </div>
                                        </a>
                                    </div>
                                </div>

                                <!-- Tax Reports -->
                                <div class="report-section hidden" id="tax_export">
                                    <div class="space-y-3">
                                        <a href="taxexport.php"
                                            class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200">
                                            <div
                                                class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-receipt text-green-600 text-sm"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-medium text-gray-800">Export for Tax Computation</h4>
                                                <p class="text-sm text-gray-600">Export data for tax computation
                                                    purposes</p>
                                            </div>
                                        </a>
                                        <a href="tax_returns.php"
                                            class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200">
                                            <div
                                                class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-file-invoice text-green-600 text-sm"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-medium text-gray-800">Annual Tax Return</h4>
                                                <p class="text-sm text-gray-600">Generate annual tax return reports</p>
                                            </div>
                                        </a>
                                    </div>
                                </div>

                                <!-- Employee Reports -->
                                <div class="report-section hidden" id="employee">
                                    <div class="space-y-3">
                                        <a href="employee_report.php"
                                            class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200">
                                            <div
                                                class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-users text-purple-600 text-sm"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-medium text-gray-800">Employee Report</h4>
                                                <p class="text-sm text-gray-600">Comprehensive employee information
                                                    report</p>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                                <!-- Bank Summary Reports -->
                                <div class="report-section hidden" id="transfer">
                                    <div class="space-y-3">
                                        <a href="banksummary.php"
                                            class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200">
                                            <div
                                                class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-university text-yellow-600 text-sm"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-medium text-gray-800">Bank Summary</h4>
                                                <p class="text-sm text-gray-600">Banking and payment summaries</p>
                                            </div>
                                        </a>
                                    </div>
                                </div>

                                <!-- Deduction Reports -->
                                <div class="report-section hidden" id="inventory">
                                    <div class="space-y-3">
                                        <a href="deductionlist.php"
                                            class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200">
                                            <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-minus-circle text-red-600 text-sm"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-medium text-gray-800">Deduction List</h4>
                                                <p class="text-sm text-gray-600">Complete list of salary deductions</p>
                                            </div>
                                        </a>
                                    </div>
                                </div>

                                <!-- Gross Amount Reports -->
                                <div class="report-section hidden" id="gross">
                                    <div class="space-y-3">
                                        <a href="gross.php"
                                            class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200">
                                            <div
                                                class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-chart-line text-indigo-600 text-sm"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-medium text-gray-800">Gross Amount List</h4>
                                                <p class="text-sm text-gray-600">Gross salary calculations and reports
                                                </p>
                                            </div>
                                        </a>
                                    </div>
                                </div>

                                <!-- PFA Reports -->
                                <div class="report-section hidden" id="deleted-sales">
                                    <div class="space-y-3">
                                        <a href="pfalist.php"
                                            class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200">
                                            <div
                                                class="w-8 h-8 bg-pink-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-piggy-bank text-pink-600 text-sm"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-medium text-gray-800">PFA Report</h4>
                                                <p class="text-sm text-gray-600">Pension Fund Administrator reports</p>
                                            </div>
                                        </a>
                                        <a href="pfasummary.php"
                                            class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200">
                                            <div
                                                class="w-8 h-8 bg-pink-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-chart-pie text-pink-600 text-sm"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-medium text-gray-800">PFA Summary</h4>
                                                <p class="text-sm text-gray-600">PFA summary reports and analytics</p>
                                            </div>
                                        </a>
                                    </div>
                                </div>

                                <!-- Variance Reports -->
                                <div class="report-section hidden" id="discounts">
                                    <div class="space-y-3">
                                        <a href="variance.php"
                                            class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200">
                                            <div
                                                class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-chart-pie text-gray-600 text-sm"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-medium text-gray-800">Variance Analysis</h4>
                                                <p class="text-sm text-gray-600">Salary variance reports and analysis
                                                </p>
                                            </div>
                                        </a>
                                    </div>
                                </div>

                                <!-- Audit Log Reports -->
                                <div class="report-section hidden" id="log">
                                    <div class="space-y-3">
                                        <a href="log.php"
                                            class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all duration-200">
                                            <div
                                                class="w-8 h-8 bg-cyan-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-history text-cyan-600 text-sm"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-medium text-gray-800">Audit Log</h4>
                                                <p class="text-sm text-gray-600">System activity and change logs</p>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <script type="text/javascript">
            $(document).ready(function() {
                // Handle report category clicks
                $('.report-category').click(function(e) {
                    e.preventDefault();

                    // Remove active state from all categories
                    $('.report-category').removeClass('bg-blue-100 border-blue-400').addClass(
                        'border-gray-200');

                    // Add active state to clicked category
                    $(this).removeClass('border-gray-200').addClass('bg-blue-100 border-blue-400');

                    // Get the category ID
                    var categoryId = $(this).attr('id');
                    var categoryName = $(this).find('h3').text();

                    // Update the report title
                    $('#report_title').html('<i class="fas fa-arrow-right"></i> ' + categoryName);

                    // Hide default message
                    $('#default_message').addClass('hidden');

                    // Show report options
                    $('#report_options').removeClass('hidden');

                    // Hide all report sections
                    $('.report-section').addClass('hidden');

                    // Show the selected report section
                    $('#' + categoryId + '_section, .report-section[id="' + categoryId + '"]')
                        .removeClass('hidden');

                    // Smooth scroll to report selection
                    $('html, body').animate({
                        scrollTop: $("#report_selection").offset().top - 100
                    }, 500);
                });
            });
            </script>

        </main>
    </div>

</body>

</html>