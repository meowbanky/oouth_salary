<?php
session_start();

require_once('../Connections/paymaster.php');
include_once('../classes/model.php');
require_once('../libs/App.php');
$App = new App();
$App->checkAuthentication();
require_once('../libs/middleware.php');
checkPermission();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Returns Report - OOUTH Salary Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                            <i class="fas fa-file-invoice-dollar"></i> Tax Returns Report
                        </h1>
                        <p class="text-sm text-blue-700/70 mt-1">Generate comprehensive tax returns reports for payroll compliance and documentation.</p>
                    </div>
                </div>

                <!-- Report Form -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
                    <div class="bg-blue-50 px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold text-blue-800 flex items-center gap-2">
                            <i class="fas fa-filter"></i> Report Parameters
                        </h2>
                    </div>
                    <div class="p-6">
                        <!-- Organization Header -->
                        <div class="text-center mb-8">
                            <img src="img/oouth_logo.gif" alt="OOUTH Logo" class="h-16 mx-auto mb-4">
                            <h3 class="text-lg font-bold text-blue-800 uppercase">OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL</h3>
                            <p class="text-blue-600 font-medium">Tax Returns Report Generator</p>
                        </div>

                        <form method="POST" action="generate_excel.php" id="downloadForm" class="space-y-6">
                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <label for="period" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calendar-alt mr-2 text-blue-600"></i>Pay Period
                                    </label>
                                    <select id="period" name="period" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm" required>
                                        <option value="">Loading periods...</option>
                                    </select>
                                    <div id="periodError" class="text-red-500 text-sm mt-1 hidden">
                                        Please select a pay period
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-3 pt-4">
                                <button name="generate_report" type="submit" id="generate_report" class="bg-blue-700 hover:bg-blue-900 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-file-excel"></i> Download Excel
                                </button>
                                <button type="button" onclick="window.history.back()" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-arrow-left"></i> Back
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Instructions Section -->
                <div class="bg-blue-50 rounded-xl p-6 mb-6">
                    <h3 class="text-lg font-semibold text-blue-800 mb-3 flex items-center gap-2">
                        <i class="fas fa-info-circle"></i> Instructions
                    </h3>
                    <div class="text-sm text-blue-700 space-y-2">
                        <p><strong>1. Select Pay Period:</strong> Choose the pay period for which you want to generate the tax returns report.</p>
                        <p><strong>2. Download Excel:</strong> Click "Download Excel" to generate and download the comprehensive tax returns report.</p>
                        <p><strong>3. Report Content:</strong> The report includes all tax-related information for the selected period.</p>
                        <p><strong>Note:</strong> Large reports may take a few minutes to process. Please be patient.</p>
                    </div>
                </div>

                <!-- Features Section -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-blue-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-list-check"></i> Report Features
                    </h3>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-check-circle text-green-600"></i>
                                <span class="text-sm text-gray-700">Comprehensive tax calculations</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <i class="fas fa-check-circle text-green-600"></i>
                                <span class="text-sm text-gray-700">Employee tax information</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <i class="fas fa-check-circle text-green-600"></i>
                                <span class="text-sm text-gray-700">Pay period summary</span>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-check-circle text-green-600"></i>
                                <span class="text-sm text-gray-700">Excel format for easy analysis</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <i class="fas fa-check-circle text-green-600"></i>
                                <span class="text-sm text-gray-700">Compliance-ready documentation</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <i class="fas fa-check-circle text-green-600"></i>
                                <span class="text-sm text-gray-700">Professional formatting</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading Overlay -->
                <div id="loading-overlay" class="fixed inset-0 bg-gray-800 bg-opacity-75 items-center justify-center z-50 hidden">
                    <div class="bg-white rounded-lg p-6 flex flex-col items-center">
                        <i class="fas fa-spinner fa-spin text-blue-600 text-3xl mb-4"></i>
                        <p class="text-gray-700 font-medium">Generating tax returns report...</p>
                        <p class="text-sm text-gray-500 mt-2">Please wait while we process your request</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        $(document).ready(function() {
            // Fetch periods from the server and populate the dropdown
            $.ajax({
                url: 'get_periods.php',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    var options = '<option value="">Select Pay Period</option>';
                    if (data && data.length > 0) {
                        data.forEach(function(period) {
                            options += `<option value="${period.periodId}">${period.periodText}</option>`;
                        });
                    } else {
                        options = '<option value="">No periods available</option>';
                    }
                    $('#period').html(options);
                },
                error: function(xhr, status, error) {
                    console.error('Error loading periods:', error);
                    $('#period').html('<option value="">Error loading periods</option>');
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error Loading Periods',
                        text: 'Unable to load pay periods. Please refresh the page and try again.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#1E40AF'
                    });
                }
            });

            // Handle form submission
            $('#downloadForm').on('submit', function(e) {
                e.preventDefault();
                
                const periodId = $('#period').val();
                
                // Validate period selection
                if (!periodId) {
                    $('#periodError').removeClass('hidden');
                    $('#period').focus();
                    
                    Swal.fire({
                        icon: 'warning',
                        title: 'Period Required',
                        text: 'Please select a pay period before generating the report.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#1E40AF'
                    });
                    return;
                }
                
                // Hide any previous error
                $('#periodError').addClass('hidden');
                
                // Show loading overlay
                $('#loading-overlay').removeClass('hidden');
                $('#loading-overlay').addClass('flex');
                $('#generate_report').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Generating...');

                // First, trigger the report generation
                $.ajax({
                    url: 'generate_excel.php',
                    method: 'POST',
                    data: {
                        periodId: periodId
                    },
                    timeout: 300000, // 5 minutes timeout
                    success: function(response) {
                        // Hide loading overlay
                        $('#loading-overlay').addClass('hidden');
                        $('#loading-overlay').removeClass('flex');
                        $('#generate_report').prop('disabled', false).html('<i class="fas fa-file-excel"></i> Download Excel');
                        
                        // Redirect to download the file
                        window.location.href = 'generate_excel.php?periodId=' + periodId;
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Report Generated!',
                            text: 'Your tax returns report has been generated successfully and is downloading.',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#1E40AF'
                        });
                    },
                    error: function(xhr, status, error) {
                        // Hide loading overlay
                        $('#loading-overlay').addClass('hidden');
                        $('#loading-overlay').removeClass('flex');
                        $('#generate_report').prop('disabled', false).html('<i class="fas fa-file-excel"></i> Download Excel');
                        
                        console.error('Error generating report:', error);
                        
                        let errorMessage = 'Error generating the tax returns report. Please try again.';
                        if (status === 'timeout') {
                            errorMessage = 'Request timed out. The report may be too large. Please try again or contact administrator.';
                        } else if (xhr.status === 500) {
                            errorMessage = 'Server error occurred. Please try again or contact administrator.';
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Generation Failed',
                            text: errorMessage,
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#1E40AF'
                        });
                    }
                });
            });

            // Clear error when period is selected
            $('#period').on('change', function() {
                if ($(this).val()) {
                    $('#periodError').addClass('hidden');
                }
            });

            // Auto-focus on period dropdown when page loads
            setTimeout(function() {
                $('#period').focus();
            }, 1000);
        });
    </script>
</body>
</html>