<?php
require_once('../Connections/paymaster.php');
include_once('../classes/model.php');
require_once('../libs/App.php');
$App = new App();
$App->checkAuthentication();
require_once('../libs/middleware.php');
checkPermission();

// Initialize variables
$period = $_POST['period'] ?? -1;
$pfa = $_POST['pfa'] ?? -1;
$month = '';
// Capture the output of retrieveDescSingleFilter using output buffering
ob_start();
retrieveDescSingleFilter('tbl_pfa', 'PFANAME', 'PFACODE', $pfa);
$pfaName = ob_get_clean() ?: 'All PFA'; // Default to 'All PFA' if no output

// Fetch period description
if ($period != -1) {
    try {
        $query = $conn->prepare('SELECT description, periodYear FROM payperiods WHERE periodId = ?');
        $query->execute([$period]);
        $row = $query->fetch(PDO::FETCH_ASSOC);
        $month = $row ? $row['description'] . '-' . $row['periodYear'] : '';
    } catch (PDOException $e) {
        $month = '';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PFA List Report - OOUTH Salary Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../css/dark-mode.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../js/theme-manager.js"></script>
</head>

<body class="bg-gray-100 min-h-screen">
    <?php include('../header.php'); ?>
    <div class="flex min-h-screen">
        <?php include('report_sidebar_modern.php'); ?>
        <main class="flex-1 px-2 md:px-8 py-4 flex flex-col">
            <!-- Breadcrumb Navigation -->
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="../home.php"
                            class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                            <i class="fas fa-home w-4 h-4 mr-2"></i>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 mx-1"></i>
                            <a href="index.php"
                                class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2">Reports</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 mx-1"></i>
                            <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">PFA List</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <div class="w-full max-w-7xl mx-auto flex-1 flex flex-col">
                <!-- Header Section -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-blue-800 flex items-center gap-2">
                            <i class="fas fa-piggy-bank"></i> PFA List Report
                        </h1>
                        <p class="text-sm text-blue-700/70 mt-1">Generate pension fund administrator reports with
                            employee contributions.</p>
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
                        <form method="POST" action="pfalist.php" class="space-y-6">
                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <label for="period" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calendar-alt mr-2 text-blue-600"></i>Pay Period
                                    </label>
                                    <select name="period" id="period"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm"
                                        required>
                                        <option value="">Select Pay Period</option>
                                        <?php
                                        try {
                                            $query = $conn->prepare('SELECT description, periodYear, periodId FROM payperiods WHERE payrollRun = ? ORDER BY periodId DESC');
                                            $query->execute(['1']);
                                            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                                                $selected = $row['periodId'] == $period ? 'selected' : '';
                                                echo "<option value='{$row['periodId']}' $selected>{$row['description']} - {$row['periodYear']}</option>";
                                            }
                                        } catch (PDOException $e) {
                                            echo "<option value=''>Error loading periods</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div>
                                    <label for="pfa" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-university mr-2 text-green-600"></i>Pension Fund Administrator
                                    </label>
                                    <select name="pfa" id="pfa"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm"
                                        required>
                                        <option value="">Select PFA</option>
                                        <option value="-1" <?php echo $pfa == -1 ? 'selected' : ''; ?>>All PFA</option>
                                        <?php
                                        try {
                                            $query = $conn->prepare('SELECT PFACODE, PFANAME FROM tbl_pfa WHERE PFANAME <> "" ORDER BY PFANAME');
                                            $query->execute();
                                            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                                                $selected = $row['PFACODE'] == $pfa ? 'selected' : '';
                                                echo "<option value='{$row['PFACODE']}' $selected>{$row['PFANAME']}</option>";
                                            }
                                        } catch (PDOException $e) {
                                            echo "<option value=''>Error loading PFAs</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Email Recipient Field -->
                            <div>
                                <label for="recipient_email" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-envelope mr-2 text-purple-600"></i>Recipient Email
                                </label>
                                <input type="email" id="recipient_email" name="recipient_email"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm"
                                    placeholder="Enter email address to send report">
                                <p class="mt-1 text-xs text-gray-500">Email will be auto-filled when a specific PFA is
                                    selected</p>
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <button name="generate_report" type="submit" id="generate_report"
                                    class="bg-blue-700 hover:bg-blue-900 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-search"></i> Generate Report
                                </button>
                                <button type="button" id="download-excel-button"
                                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-file-excel"></i> Download Excel
                                </button>
                                <button type="button" id="send-email-button"
                                    class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i class="fas fa-paper-plane"></i> Send via Email
                                </button>
                                <button type="button" id="download-pdf-button"
                                    class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-file-pdf"></i> Download PDF
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($month != '' && $pfa != '') { ?>
                <!-- Report Header -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
                    <div class="bg-blue-50 px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold text-blue-800 text-center">
                            OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL
                        </h2>
                        <p class="text-center text-blue-700 font-medium mt-2">
                            <?php echo htmlspecialchars($pfaName); ?> Pension Report for the Month of:
                            <?php echo htmlspecialchars($month); ?>
                        </p>
                    </div>
                </div>

                <!-- Report Table -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="sample_1">
                            <thead class="bg-blue-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        S/No.</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        Staff No.</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        Name</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        PFA</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        PIN</th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        Amount</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php
                                    if ($period != -1 && $pfa != '') {
                                        try {
                                            $sql = $pfa != -1
                                                ? 'SELECT tbl_master.deduc, master_staff.staff_id, master_staff.`NAME`, master_staff.PFAACCTNO, tbl_pfa.PFANAME 
                                                           FROM tbl_master 
                                                           INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id 
                                                           INNER JOIN tbl_pfa ON master_staff.PFACODE = tbl_pfa.PFACODE 
                                                           WHERE tbl_master.allow_id = ? AND master_staff.period = ? AND master_staff.PFACODE = ? AND tbl_master.period = ? 
                                                           ORDER BY tbl_master.staff_id ASC'
                                                : 'SELECT tbl_master.deduc, master_staff.staff_id, master_staff.`NAME`, master_staff.PFAACCTNO, tbl_pfa.PFANAME 
                                                           FROM tbl_master 
                                                           INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id 
                                                           INNER JOIN tbl_pfa ON master_staff.PFACODE = tbl_pfa.PFACODE 
                                                           WHERE tbl_master.allow_id = ? AND master_staff.period = ? AND tbl_master.period = ? 
                                                           ORDER BY tbl_master.staff_id ASC';

                                            $query = $conn->prepare($sql);
                                            $params = $pfa != -1 ? ['50', $period, $pfa, $period] : ['50', $period, $period];
                                            $query->execute($params);

                                            $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                            $counter = 1;
                                            $sumTotal = 0;

                                            if (count($res) > 0) {
                                                foreach ($res as $row) {
                                                    echo '<tr class="hover:bg-gray-50 transition-colors duration-150">';
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . $counter . '</td>';
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">' . htmlspecialchars($row['staff_id'] ?? '') . '</td>';
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['NAME'] ?? '') . '</td>';
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['PFANAME'] ?? '') . '</td>';
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['PFAACCTNO'] ?? '') . '</td>';
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">₦' . number_format($row['deduc'], 2) . '</td>';
                                                    $sumTotal += floatval($row['deduc']);
                                                    $counter++;
                                                    echo '</tr>';
                                                }

                                                // Total row
                                                echo '<tr class="bg-blue-50 border-t-2 border-blue-200">';
                                                echo '<td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">TOTAL</td>';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">₦' . number_format($sumTotal, 2) . '</td>';
                                                echo '</tr>';
                                            } else {
                                                echo '<tr>';
                                                echo '<td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No pension data found for the selected criteria.</td>';
                                                echo '</tr>';
                                            }
                                        } catch (PDOException $e) {
                                            echo '<tr>';
                                            echo '<td colspan="6" class="px-6 py-4 text-center text-sm text-red-500">Error: ' . htmlspecialchars($e->getMessage()) . '</td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr>';
                                        echo '<td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">Please select a pay period and PFA to generate the report.</td>';
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
                                <p><strong>Date:</strong> <?php 
                                        $today = date('y:m:d');
                                        $formattedDate = date('l, F d, Y', strtotime($today));
                                        echo $formattedDate;
                                    ?></p>
                            </div>
                            <div class="text-sm text-gray-600">
                                <p><strong>PFA:</strong> <?php echo htmlspecialchars($pfaName); ?></p>
                                <p><strong>Period:</strong> <?php echo htmlspecialchars($month); ?></p>
                                <?php if (isset($sumTotal) && $sumTotal > 0): ?>
                                <p><strong>Total Contribution:</strong> ₦<?php echo number_format($sumTotal, 2); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
        </main>
    </div>

    <script type="text/javascript" language="javascript">
    $(document).ready(function() {
        // Fetch PFA email when PFA is selected
        $('#pfa').on('change', function() {
            const pfaCode = $(this).val();
            if (pfaCode && pfaCode !== '-1' && pfaCode !== '') {
                // Fetch PFA details including email
                $.ajax({
                    url: '../libs/get_pfa_email.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        pfa_code: pfaCode
                    },
                    success: function(response) {
                        if (response.status === 'success' && response.email) {
                            $('#recipient_email').val(response.email);
                        } else {
                            // Clear email if PFA doesn't have one
                            $('#recipient_email').val('');
                        }
                        updateButtonStates();
                    },
                    error: function() {
                        // Silently fail - user can still enter email manually
                        console.log('Could not fetch PFA email');
                        updateButtonStates();
                    }
                });
            } else {
                // Clear email field when "All PFA" or empty selection
                $('#recipient_email').val('');
            }
        });

        // Update button states based on form inputs
        function updateButtonStates() {
            const hasData = $('#period').val() && $('#pfa').val();
            const hasEmail = $('#recipient_email').val().trim();

            $('#download-excel-button').prop('disabled', !hasData);
            $('#download-pdf-button').prop('disabled', !hasData);
            $('#send-email-button').prop('disabled', !hasData || !hasEmail);
        }

        // Update button states on input changes
        $('#period, #pfa, #recipient_email').on('change input', function() {
            updateButtonStates();
        });

        // Initial button state check
        updateButtonStates();

        // If a PFA is already selected on page load, fetch its email
        <?php if ($pfa != -1 && $pfa != ''): ?>
        $(document).ready(function() {
            const pfaCode = '<?php echo htmlspecialchars($pfa); ?>';
            if (pfaCode && pfaCode !== '-1') {
                $.ajax({
                    url: '../libs/get_pfa_email.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        pfa_code: pfaCode
                    },
                    success: function(response) {
                        if (response.status === 'success' && response.email) {
                            $('#recipient_email').val(response.email);
                        }
                        updateButtonStates();
                    },
                    error: function() {
                        // Silently fail
                        updateButtonStates();
                    }
                });
            }
        });
        <?php endif; ?>

        // Form submission handling
        $('#generate_report').click(function(e) {
            if (!$('#period').val() || !$('#pfa').val()) {
                e.preventDefault();
                alert('Please select both Pay Period and PFA before generating the report.');
            }
        });

        // Export functionality
        $('#download-excel-button').click(function() {
            if (!$('#period').val() || !$('#pfa').val()) {
                alert('Please select both Pay Period and PFA before downloading Excel.');
                return;
            }
            downloadExcel();
        });

        // Send Email functionality
        $('#send-email-button').click(function() {
            const email = $('#recipient_email').val().trim();

            if (!$('#period').val() || !$('#pfa').val()) {
                alert('Please select both Pay Period and PFA before sending email.');
                return;
            }

            if (!email) {
                alert('Please enter a recipient email address.');
                $('#recipient_email').focus();
                return;
            }

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                alert('Please enter a valid email address.');
                $('#recipient_email').focus();
                return;
            }

            sendEmail(email);
        });

        $('#download-pdf-button').click(function() {
            if (!$('#period').val() || !$('#pfa').val()) {
                alert('Please select both Pay Period and PFA before downloading PDF.');
                return;
            }
            downloadPDF();
        });

        function downloadExcel() {
            $('#ajax-loader').show();
            $.ajax({
                type: "POST",
                url: 'pfalist_export_excel.php',
                data: {
                    period: $('#period').val(),
                    pfa: $('#pfa').val(),
                    period_text: '<?php echo $month; ?>',
                    pfa_text: '<?php echo htmlspecialchars($pfaName); ?>'
                },
                timeout: 300000,
                success: function(response) {
                    $('#ajax-loader').hide();
                    try {
                        if (typeof response === 'string' && response.includes('<!DOCTYPE html>')) {
                            console.error('Received HTML error page instead of data');
                            alert(
                                'Server error occurred. Please try again or contact administrator.'
                            );
                            return;
                        }

                        var downloadLink = document.createElement('a');
                        downloadLink.href =
                            'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' +
                            response;
                        downloadLink.download = 'PFA_Report_' + '<?php echo $month; ?>' + '.xlsx';
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                    } catch (e) {
                        console.error('Error processing Excel response:', e);
                        alert('Error generating Excel file. Please try again.');
                    }
                },
                error: function(xhr, status, error) {
                    $('#ajax-loader').hide();
                    console.error('AJAX Error:', status, error);
                    if (status === 'timeout') {
                        alert(
                            'Request timed out. The report may be too large. Please try with fewer records or contact administrator.'
                        );
                    } else {
                        alert('Error downloading Excel file. Please try again.');
                    }
                }
            });
        }

        function sendEmail(email) {
            // Show loading alert
            Swal.fire({
                title: 'Sending Email',
                text: 'Please wait while we generate and send the report...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                type: "POST",
                url: 'pfalist_export_excel.php',
                data: {
                    period: $('#period').val(),
                    pfa: $('#pfa').val(),
                    period_text: '<?php echo $month; ?>',
                    pfa_text: '<?php echo htmlspecialchars($pfaName); ?>',
                    recipient_email: email,
                    action: 'email'
                },
                timeout: 300000,
                dataType: 'json',
                success: function(response) {
                    Swal.close();

                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Email Sent!',
                            text: response.message ||
                                'Report has been successfully sent to ' + email,
                            timer: 3000,
                            showConfirmButton: true
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message ||
                                'Failed to send email. Please try again.',
                            showConfirmButton: true
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close();
                    console.error('AJAX Error:', status, error, xhr.responseText);

                    let errorMsg = 'Error sending email. Please try again.';
                    try {
                        if (xhr.responseText) {
                            const response = JSON.parse(xhr.responseText);
                            if (response.message || response.error) {
                                errorMsg = response.message || response.error;
                            }
                        }
                    } catch (e) {
                        if (xhr.responseText && xhr.responseText.length < 200) {
                            errorMsg = xhr.responseText;
                        }
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMsg,
                        showConfirmButton: true
                    });
                }
            });
        }

        function downloadPDF() {
            $('#ajax-loader').show();
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'pfalist_export_pdf.php';
            form.style.display = 'none';

            var fields = {
                period: $('#period').val(),
                pfa: $('#pfa').val(),
                period_text: '<?php echo $month; ?>',
                pfa_text: '<?php echo htmlspecialchars($pfaName); ?>'
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
            $('#ajax-loader').hide();
        }
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>

</html>