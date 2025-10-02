<?php
session_start();
ini_set('max_execution_time', '0');

require_once('../Connections/paymaster.php');
include_once('../classes/model.php');
require_once('../libs/App.php');
$App = new App();
$App->checkAuthentication();
require_once('../libs/middleware.php');
checkPermission();

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
$periodFrom = isset($_GET['periodFrom']) ? $_GET['periodFrom'] : ($defaultPeriod['periodId'] ?? '');
$periodTo = isset($_GET['periodTo']) ? $_GET['periodTo'] : ($defaultPeriod['periodId'] ?? '');

// Get period description for display
function getPeriodDescription($conn, $periodId) {
    if (empty($periodId)) return '';
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
    <title>Payroll Report Generator - OOUTH Salary Management</title>
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
                            <i class="fas fa-file-excel"></i> Payroll Report Generator
                        </h1>
                        <p class="text-sm text-blue-700/70 mt-1">Generate comprehensive payroll reports with email delivery options.</p>
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
                            <h3 class="text-lg font-bold text-blue-800">OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL</h3>
                            <p class="text-blue-600 font-medium">Payroll Report Generator</p>
                        </div>

                        <form method="GET" action="payroll_report.php" id="payrollForm" class="space-y-6">
                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <label for="periodFrom" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calendar-alt mr-2 text-blue-600"></i>Period From
                                    </label>
                                    <select name="periodFrom" id="periodFrom" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm" required>
                                        <option value="">Select Starting Period</option>
                                        <?php
                                        try {
                                            $query = $conn->prepare('
                                                SELECT payperiods.description, payperiods.periodYear, payperiods.periodId 
                                                FROM payperiods 
                                                WHERE payrollRun = ? 
                                                ORDER BY periodId DESC
                                            ');
                                            $query->execute(['1']);
                                            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                                                $selected = ($row['periodId'] == $periodFrom) ? 'selected="selected"' : '';
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
                                    <div id="periodFromError" class="text-red-500 text-sm mt-1 hidden">
                                        Please select a starting period
                                    </div>
                                </div>

                                <div>
                                    <label for="periodTo" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calendar-alt mr-2 text-blue-600"></i>Period To
                                    </label>
                                    <select name="periodTo" id="periodTo" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm" required>
                                        <option value="">Select Ending Period</option>
                                        <?php
                                        try {
                                            $query = $conn->prepare('
                                                SELECT payperiods.description, payperiods.periodYear, payperiods.periodId 
                                                FROM payperiods 
                                                WHERE payrollRun = ? 
                                                ORDER BY periodId DESC
                                            ');
                                            $query->execute(['1']);
                                            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                                                $selected = ($row['periodId'] == $periodTo) ? 'selected="selected"' : '';
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
                                    <div id="periodToError" class="text-red-500 text-sm mt-1 hidden">
                                        Please select an ending period
                                    </div>
                                </div>
                            </div>

                            <!-- Email Section -->
                            <div class="border-t pt-6">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="flex items-center">
                                        <input type="checkbox" name="send_email" id="send_email" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2" onchange="toggleEmailField()">
                                        <label for="send_email" class="ml-2 text-sm font-medium text-gray-700">
                                            <i class="fas fa-envelope mr-2 text-blue-600"></i>Send Excel file via email
                                        </label>
                                    </div>
                                </div>
                                
                                <div id="email_field" class="hidden">
                                    <label for="email_address" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-at mr-2 text-blue-600"></i>Email Address
                                    </label>
                                    <input type="email" name="email_address" id="email_address" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm" placeholder="Enter email address">
                                    <div id="emailError" class="text-red-500 text-sm mt-1 hidden">
                                        Please enter a valid email address
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-3 pt-4">
                                <button type="submit" class="bg-blue-700 hover:bg-blue-900 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2" id="submitButton">
                                    <i class="fas fa-file-excel"></i> Generate Report
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
                        <p><strong>1. Select Period Range:</strong> Choose the starting and ending pay periods for your report.</p>
                        <p><strong>2. Email Option:</strong> Check the email option to receive the report via email instead of downloading directly.</p>
                        <p><strong>3. Generate Report:</strong> Click "Generate Report" to create your payroll report.</p>
                        <p><strong>Note:</strong> Large reports may take a few minutes to process. Please be patient.</p>
                    </div>
                </div>

                <!-- Loading Overlay -->
                <div id="loading-overlay" class="fixed inset-0 bg-gray-800 bg-opacity-75 items-center justify-center z-50 hidden">
                    <div class="bg-white rounded-lg p-6 flex flex-col items-center">
                        <i class="fas fa-spinner fa-spin text-blue-600 text-3xl mb-4"></i>
                        <p class="text-gray-700 font-medium">Generating report...</p>
                        <p class="text-sm text-gray-500 mt-2">Please wait while we process your request</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script type="text/javascript">
        function toggleEmailField() {
            const emailField = document.getElementById('email_field');
            const emailCheckbox = document.getElementById('send_email');
            
            if (emailCheckbox.checked) {
                emailField.classList.remove('hidden');
                emailField.classList.add('block');
            } else {
                emailField.classList.add('hidden');
                emailField.classList.remove('block');
            }
        }

        function showError(elementId, message) {
            const errorElement = document.getElementById(elementId);
            errorElement.textContent = message;
            errorElement.classList.remove('hidden');
        }

        function hideError(elementId) {
            const errorElement = document.getElementById(elementId);
            errorElement.classList.add('hidden');
        }

        function hideAllErrors() {
            hideError('periodFromError');
            hideError('periodToError');
            hideError('emailError');
        }

        function validateEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        document.getElementById('payrollForm').addEventListener('submit', function(e) {
            e.preventDefault();

            let isValid = true;
            hideAllErrors();

            // Validate period selections
            const periodFrom = document.getElementById('periodFrom').value;
            const periodTo = document.getElementById('periodTo').value;

            if (!periodFrom) {
                showError('periodFromError', 'Please select a starting period');
                isValid = false;
            }

            if (!periodTo) {
                showError('periodToError', 'Please select an ending period');
                isValid = false;
            }

            // Validate email if checkbox is checked
            const emailCheckbox = document.getElementById('send_email');
            const emailInput = document.getElementById('email_address');

            if (emailCheckbox.checked) {
                if (!emailInput.value) {
                    showError('emailError', 'Please enter an email address');
                    isValid = false;
                } else if (!validateEmail(emailInput.value)) {
                    showError('emailError', 'Please enter a valid email address');
                    isValid = false;
                }
            }

            if (!isValid) {
                // Scroll to first error
                const firstError = document.querySelector('.text-red-500:not(.hidden)');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return false;
            }

            // Show loading overlay
            document.getElementById('loading-overlay').classList.remove('hidden');
            document.getElementById('loading-overlay').classList.add('flex');
            document.getElementById('submitButton').disabled = true;

            // If send_email is checked, use AJAX for JSON response
            if (emailCheckbox.checked) {
                const form = this;
                const formData = new FormData(form);
                const url = form.action;
                const queryString = new URLSearchParams(formData).toString();

                fetch(url + '?' + queryString, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.text())
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        document.getElementById('loading-overlay').classList.add('hidden');
                        document.getElementById('loading-overlay').classList.remove('flex');
                        document.getElementById('submitButton').disabled = false;

                        Swal.fire({
                            icon: 'success',
                            title: 'Report Sent!',
                            text: data.message,
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#1E40AF'
                        });
                    } catch (e) {
                        document.getElementById('loading-overlay').classList.add('hidden');
                        document.getElementById('loading-overlay').classList.remove('flex');
                        document.getElementById('submitButton').disabled = false;
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Report Sent!',
                            text: 'Report has been sent successfully to ' + emailInput.value,
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#1E40AF'
                        });
                    }
                })
                .catch(error => {
                    document.getElementById('loading-overlay').classList.add('hidden');
                    document.getElementById('submitButton').disabled = false;
                    
                    Swal.fire({
                        icon: 'warning',
                        title: 'Report May Have Been Sent',
                        text: 'An error occurred, but the report may have been sent. Please check your email.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#1E40AF'
                    });
                });
            } else {
                // For download, submit the form traditionally
                document.getElementById('loading-overlay').classList.add('hidden');
                document.getElementById('submitButton').disabled = false;
                this.submit();
            }
        });

        // Auto-focus first empty field
        document.addEventListener('DOMContentLoaded', function() {
            const periodFrom = document.getElementById('periodFrom');
            const periodTo = document.getElementById('periodTo');
            
            if (!periodFrom.value) {
                periodFrom.focus();
            } else if (!periodTo.value) {
                periodTo.focus();
            }
        });
    </script>
</body>
</html>