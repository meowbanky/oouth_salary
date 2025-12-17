<?php
session_start();

require_once('../Connections/paymaster.php');
include_once('../classes/model.php');
require_once('../libs/App.php');
$App = new App();
$App->checkAuthentication();
require_once('../libs/middleware.php');
checkPermission();

// Initialize variables
$deductionName = '';
$month = '';
$period = isset($_POST['period']) ? $_POST['period'] : -1;
$deduction = isset($_POST['deduction']) ? $_POST['deduction'] : -1;

// Get deduction name
if ($deduction != -1) {
    try {
        $query = $conn->prepare('SELECT tbl_earning_deduction.ed_id, tbl_earning_deduction.ed FROM tbl_earning_deduction WHERE ed_id = ?');
        $query->execute([$deduction]);
        $result = $query->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $deductionName = $result['ed'];
        }
    } catch (PDOException $e) {
        $deductionName = 'Unknown Deduction';
    }
}

// Get period description
if ($period != -1) {
    try {
        $query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear, payperiods.periodId FROM payperiods WHERE periodId = ?');
        $query->execute([$period]);
        $result = $query->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $month = $result['description'] . '-' . $result['periodYear'];
        }
    } catch (PDOException $e) {
        $month = 'Unknown Period';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deduction List Report - OOUTH Salary Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../css/dark-mode.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/theme-manager.js"></script>
</head>

<body class="bg-gray-100 min-h-screen">
    <?php include('../header.php'); ?>
    <div class="flex min-h-screen">
        <?php include('report_sidebar_modern.php'); ?>
        <main class="flex-1 px-2 md:px-8 py-4 flex flex-col">
            <div class="w-full max-w-7xl mx-auto flex-1 flex flex-col">
                <!-- Breadcrumb Navigation -->
                <nav class="flex mb-4" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="../home.php" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                                <i class="fas fa-home w-4 h-4 mr-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-1"></i>
                                <a href="index.php" class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2">Reports</a>
                            </div>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-1"></i>
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Deduction List</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                <!-- Header Section -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-blue-800 flex items-center gap-2">
                            <i class="fas fa-list-alt"></i> Deduction List Report
                        </h1>
                        <p class="text-sm text-blue-700/70 mt-1">Generate detailed deduction reports for specific pay periods and deduction types.</p>
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
                            <?php if ($deductionName): ?>
                                <p class="text-blue-600 font-medium"><?php echo htmlspecialchars($deductionName); ?> Report</p>
                            <?php endif; ?>
                            <?php if ($month): ?>
                                <p class="text-sm text-gray-600">For the Month of: <?php echo htmlspecialchars($month); ?></p>
                            <?php endif; ?>
                        </div>

                        <form id="deduction_form" method="POST" action="deductionlist.php" class="space-y-6">
                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
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

                                <div>
                                    <label for="deduction" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-minus-circle mr-2 text-blue-600"></i>Deduction Type
                                    </label>
                                    <select name="deduction" id="deduction" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm" required>
                                        <option value="">Select Deduction</option>
                                        <?php
                                        try {
                                            $query = $conn->prepare('SELECT tbl_earning_deduction.ed_id, tbl_earning_deduction.ed, tbl_earning_deduction.code FROM tbl_earning_deduction WHERE edType > ? AND status = ? ORDER BY ed_id ASC');
                                            $query->execute(['0', 'Active']);
                                            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                                                $selected = ($row['ed_id'] == $deduction) ? 'selected="selected"' : '';
                                                echo sprintf(
                                                    '<option value="%s" data-code="%s" %s>%s - %s</option>',
                                                    htmlspecialchars($row['ed_id']),
                                                    htmlspecialchars($row['code']),
                                                    $selected,
                                                    htmlspecialchars($row['ed']),
                                                    htmlspecialchars($row['ed_id'])
                                                );
                                            }
                                        } catch (PDOException $e) {
                                            echo "<option value=''>Error loading deductions</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-3 pt-4">
                                <button name="generate_report" type="submit" id="generate_report" class="bg-blue-700 hover:bg-blue-900 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-search"></i> Generate Report
                                </button>
                                <button name="send_mail" id="send_mail" type="button" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-envelope"></i> Send Mail
                                </button>
                                <button type="button" id="export-pdf-button" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-file-pdf"></i> Export PDF
                                </button>
                                <button type="button" id="download-excel-button" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-file-excel"></i> Download Excel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($period != -1 && $deduction != -1): ?>
                    <!-- Report Table -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="bg-blue-50 px-6 py-4 border-b flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-blue-800 flex items-center gap-2">
                                <i class="fas fa-table"></i> Deduction Details
                            </h2>
                            <img src="img/oouth_logo.gif" alt="OOUTH Logo" class="h-10">
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200" id="sample_1">
                                <thead class="bg-blue-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">S/No.</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">Staff No.</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-blue-700 uppercase tracking-wider">Amount</th>
                                        <?php if ($deduction == 87 || $deduction == 85): ?>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-blue-700 uppercase tracking-wider">Balance</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php
                                    $type = -1;
                                    try {
                                        // Get deduction type
                                        $query = $conn->prepare('SELECT tbl_earning_deduction.code FROM tbl_earning_deduction WHERE ed_id = ?');
                                        $query->execute([$deduction]);
                                        $result = $query->fetch(PDO::FETCH_ASSOC);
                                        if ($result) {
                                            $type = $result['code'];
                                        }
                                    } catch (PDOException $e) {
                                        $type = -1;
                                    }

                                    try {
                                        // Get deduction data
                                        if ($type == 1) {
                                            $query = $conn->prepare('SELECT tbl_master.allow as deduc, master_staff.staff_id, master_staff.NAME FROM tbl_master INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id WHERE tbl_master.allow_id = ? AND tbl_master.period = ? AND master_staff.period = ? ORDER BY master_staff.staff_id ASC');
                                        } else {
                                            $query = $conn->prepare('SELECT tbl_master.deduc as deduc, master_staff.staff_id, master_staff.NAME FROM tbl_master INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id WHERE tbl_master.allow_id = ? AND tbl_master.period = ? AND master_staff.period = ? ORDER BY master_staff.staff_id ASC');
                                        }
                                        
                                        $query->execute([$deduction, $period, $period]);
                                        $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                        $numberofstaff = count($res);
                                        $sumTotal = 0;

                                        if ($numberofstaff > 0) {
                                            foreach ($res as $index => $link) {
                                                echo '<tr class="hover:bg-gray-50 transition-colors duration-150">';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . ($index + 1) . '</td>';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">' . htmlspecialchars($link['staff_id']) . '</td>';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($link['NAME']) . '</td>';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">₦' . number_format($link['deduc']) . '</td>';
                                                
                                                if ($deduction == 87 || $deduction == 85) {
                                                    $loan = retrieveLoanStatus($link['staff_id'], $deduction);
                                                    $repayment = retrieveLoanBalanceStatus($link['staff_id'], $deduction, $period);
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">₦' . number_format($loan - $repayment) . '</td>';
                                                }
                                                
                                                $sumTotal += floatval($link['deduc']);
                                                echo '</tr>';
                                            }

                                            // Total row
                                            echo '<tr class="bg-blue-50 border-t-2 border-blue-200">';
                                            echo '<td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">TOTAL</td>';
                                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">₦' . number_format($sumTotal) . '</td>';
                                            if ($deduction == 87 || $deduction == 85) {
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"></td>';
                                            }
                                            echo '</tr>';
                                        } else {
                                            echo '<tr>';
                                            echo '<td colspan="' . (($deduction == 87 || $deduction == 85) ? '5' : '4') . '" class="px-6 py-4 text-center text-sm text-gray-500">No deduction data found for the selected criteria.</td>';
                                            echo '</tr>';
                                        }
                                    } catch (PDOException $e) {
                                        echo '<tr>';
                                        echo '<td colspan="' . (($deduction == 87 || $deduction == 85) ? '5' : '4') . '" class="px-6 py-4 text-center text-sm text-red-500">Error: ' . htmlspecialchars($e->getMessage()) . '</td>';
                                        echo '</tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Report Footer -->
                        <div class="bg-gray-50 px-6 py-4 border-t">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <div class="text-sm text-gray-600">
                                    <p><strong>Report Generated by:</strong> <?php echo $_SESSION['SESS_FIRST_NAME']; ?></p>
                                    <p><strong>Date:</strong> <?php echo date('l, F d, Y'); ?></p>
                                </div>
                                <div class="text-sm text-gray-600">
                                    <p><strong>Deduction:</strong> <?php echo htmlspecialchars($deductionName); ?></p>
                                    <p><strong>Period:</strong> <?php echo htmlspecialchars($month); ?></p>
                                    <p><strong>Total Records:</strong> <?php echo $numberofstaff ?? 0; ?></p>
                                    <p><strong>Total Amount:</strong> ₦<?php echo number_format($sumTotal ?? 0); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-xl shadow-lg p-6 text-center text-gray-600">
                        <i class="fas fa-info-circle text-4xl text-blue-500 mb-4"></i>
                        <p class="text-lg font-semibold">Please select a Pay Period and Deduction Type to generate the report.</p>
                        <p class="text-sm mt-2">Use the form above to view detailed deduction information.</p>
                    </div>
                <?php endif; ?>

                <!-- Loading Overlay -->
                <div id="export-loading" class="fixed inset-0 bg-gray-800 bg-opacity-75 items-center justify-center z-50 hidden">
                    <div class="bg-white rounded-lg p-6 flex flex-col items-center">
                        <i class="fas fa-spinner fa-spin text-blue-600 text-3xl mb-4"></i>
                        <p class="text-gray-700 font-medium">Generating file...</p>
                        <p class="text-sm text-gray-500 mt-2">Please wait while we process your request</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script type="text/javascript">
        $(document).ready(function() {
            // Auto-submit form when deduction changes
            $('#deduction').change(function(e) {
                $('#deduction_form').submit();
            });

            // Send Mail functionality
            $('#send_mail').click(function(e) {
                e.preventDefault();
                
                if (!$('#period').val() || !$('#deduction').val()) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Missing Information',
                        text: 'Please select both Pay Period and Deduction Type before sending mail.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#1E40AF'
                    });
                    return;
                }

                $('#export-loading').removeClass('hidden');
                $('#export-loading').addClass('flex');
                $('#send_mail').prop('disabled', true);

                $.ajax({
                    type: "POST",
                    url: "deductionlist_export2.php",
                    data: {
                        period: $('#period').val(),
                        deduction: $('#deduction').val(),
                        deduction_text: $('#deduction option:selected').text(),
                        period_text: $('#period option:selected').text(),
                        code: $('#deduction').find(':selected').data('code')
                    },
                    timeout: 300000,
                    success: function(response) {
                        $('#export-loading').addClass('hidden');
                        $('#export-loading').removeClass('flex');
                        $('#send_mail').prop('disabled', false);

                        try {
                            if (typeof response === 'string' && response.includes('<!DOCTYPE html>')) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Server Error',
                                    text: 'Server error occurred. Please try again or contact administrator.',
                                    confirmButtonText: 'OK',
                                    confirmButtonColor: '#1E40AF'
                                });
                                return;
                            }

                            var downloadLink = document.createElement('a');
                            downloadLink.href = 'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' + response;
                            downloadLink.download = 'deduction_report.xlsx';
                            document.body.appendChild(downloadLink);
                            downloadLink.click();
                            document.body.removeChild(downloadLink);
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Email Sent!',
                                text: 'Deduction report has been sent successfully.',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#1E40AF'
                            });
                        } catch (e) {
                            console.error('Error processing response:', e);
                            Swal.fire({
                                icon: 'error',
                                title: 'Export Error',
                                text: 'Error generating Excel file. Please try again.',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#1E40AF'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#export-loading').addClass('hidden');
                        $('#export-loading').removeClass('flex');
                        $('#send_mail').prop('disabled', false);
                        
                        console.error('AJAX Error:', status, error);
                        
                        let errorMessage = 'Error downloading Excel file. Please try again.';
                        if (status === 'timeout') {
                            errorMessage = 'Request timed out. The report may be too large. Please try with fewer records or contact administrator.';
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Export Failed',
                            text: errorMessage,
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#1E40AF'
                        });
                    }
                });
            });

            // Export PDF functionality
            $('#export-pdf-button').click(function() {
                if (!$('#period').val() || !$('#deduction').val()) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Missing Information',
                        text: 'Please select both Pay Period and Deduction Type before exporting PDF.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#1E40AF'
                    });
                    return;
                }

                $('#export-loading').removeClass('hidden');
                $('#export-loading').addClass('flex');
                
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = 'deductionlist_export_pdf.php';
                form.target = '_blank';
                form.style.display = 'none';

                var fields = {
                    period: $('#period').val(),
                    deduction: $('#deduction').val(),
                    deduction_text: $('#deduction option:selected').text(),
                    period_text: $('#period option:selected').text(),
                    code: $('#deduction').find(':selected').data('code')
                };

                for (var key in fields) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = fields[key];
                    form.appendChild(input);
                }

                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
                
                setTimeout(function() {
                    $('#export-loading').addClass('hidden');
                    $('#export-loading').removeClass('flex');
                }, 2000);
            });

            // Download Excel functionality
            $('#download-excel-button').click(function() {
                if (!$('#period').val() || !$('#deduction').val()) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Missing Information',
                        text: 'Please select both Pay Period and Deduction Type before downloading Excel.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#1E40AF'
                    });
                    return;
                }

                $('#export-loading').removeClass('hidden');
                $('#export-loading').addClass('flex');
                
                $.ajax({
                    type: "POST",
                    url: "deductionlist_export_excel.php",
                    data: {
                        period: $('#period').val(),
                        deduction: $('#deduction').val(),
                        deduction_text: $('#deduction option:selected').text(),
                        period_text: $('#period option:selected').text(),
                        code: $('#deduction').find(':selected').data('code')
                    },
                    timeout: 300000,
                    success: function(response) {
                        $('#export-loading').addClass('hidden');
                        $('#export-loading').removeClass('flex');
                        
                        try {
                            var downloadLink = document.createElement('a');
                            downloadLink.href = 'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' + response;
                            downloadLink.download = 'Deduction_Report_' + $('#deduction option:selected').text() + '_' + $('#period option:selected').text() + '.xlsx';
                            document.body.appendChild(downloadLink);
                            downloadLink.click();
                            document.body.removeChild(downloadLink);
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Download Complete!',
                                text: 'Excel file has been downloaded successfully.',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#1E40AF'
                            });
                        } catch (e) {
                            console.error('Error processing Excel response:', e);
                            Swal.fire({
                                icon: 'error',
                                title: 'Export Error',
                                text: 'Error generating Excel file. Please try again.',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#1E40AF'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#export-loading').addClass('hidden');
                        $('#export-loading').removeClass('flex');
                        
                        console.error('AJAX Error:', status, error);
                        
                        let errorMessage = 'Error downloading Excel file. Please try again.';
                        if (status === 'timeout') {
                            errorMessage = 'Request timed out. Please try again or contact administrator.';
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Export Failed',
                            text: errorMessage,
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#1E40AF'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>