<?php
require_once('../Connections/paymaster.php');
include_once('../classes/model.php');
require_once('../libs/App.php');
$App = new App();
$App->checkAuthentication();
require_once('../libs/middleware.php');
checkPermission();

// Initialize variables
$staffId = $_POST['staff_id'] ?? '';
$periodFrom = $_POST['period_from'] ?? -1;
$periodTo = $_POST['period_to'] ?? -1;
$recipientEmail = $_POST['recipient_email'] ?? '';
$staffName = '';
$staffEmail = '';
$pfaName = '';
$pfaPin = '';
$reportData = [];

// Fetch staff details if staff ID is provided
if ($staffId) {
    try {
        $query = $conn->prepare('SELECT staff_id, NAME, EMAIL, PFACODE, PFAACCTNO FROM employee WHERE staff_id = ?');
        $query->execute([$staffId]);
        $staff = $query->fetch(PDO::FETCH_ASSOC);
        
        if ($staff) {
            $staffName = $staff['NAME'];
            $staffEmail = $staff['EMAIL'] ?? '';
            $pfaPin = $staff['PFAACCTNO'] ?? '';
            
            // Get PFA name
            if ($staff['PFACODE']) {
                ob_start();
                retrieveDescSingleFilter('tbl_pfa', 'PFANAME', 'PFACODE', $staff['PFACODE']);
                $pfaName = ob_get_clean() ?: '';
            }
        }
    } catch (PDOException $e) {
        error_log("Error fetching staff: " . $e->getMessage());
    }
}

// Fetch report data if all parameters are provided
if ($staffId && $periodFrom != -1 && $periodTo != -1) {
    try {
        // Ensure periodFrom <= periodTo
        if ($periodFrom > $periodTo) {
            $temp = $periodFrom;
            $periodFrom = $periodTo;
            $periodTo = $temp;
        }
        
        $query = $conn->prepare('
            SELECT 
                tbl_master.deduc,
                tbl_master.period,
                payperiods.description,
                payperiods.periodYear
            FROM tbl_master 
            INNER JOIN payperiods ON tbl_master.period = payperiods.periodId
            WHERE tbl_master.staff_id = ? 
                AND tbl_master.allow_id = 50
                AND tbl_master.period >= ? 
                AND tbl_master.period <= ?
            ORDER BY tbl_master.period ASC
        ');
        $query->execute([$staffId, $periodFrom, $periodTo]);
        $reportData = $query->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching report data: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Individual Pension Fund Report - OOUTH Salary Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../css/dark-mode.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="../js/theme-manager.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                            <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Individual Pension
                                Report</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <div class="w-full max-w-7xl mx-auto flex-1 flex flex-col">
                <!-- Header Section -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-blue-800 flex items-center gap-2">
                            <i class="fas fa-user-circle"></i> Individual Staff Pension Fund Report
                        </h1>
                        <p class="text-sm text-blue-700/70 mt-1">Generate pension fund report for individual staff
                            across selected periods.</p>
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
                        <form method="POST" action="pfa_individual.php" id="reportForm" class="space-y-6">
                            <div class="grid md:grid-cols-2 gap-6">
                                <!-- Staff Selection -->
                                <div>
                                    <label for="staff_search" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-user mr-2 text-blue-600"></i>Search Staff
                                    </label>
                                    <input type="text" id="staff_search" name="staff_search"
                                        value="<?php echo htmlspecialchars($staffId); ?>"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm"
                                        placeholder="Enter Staff ID or Name" autocomplete="off">
                                    <input type="hidden" id="staff_id" name="staff_id"
                                        value="<?php echo htmlspecialchars($staffId); ?>">
                                    <div id="staff_info" class="mt-2 text-sm text-gray-600">
                                        <?php if ($staffName): ?>
                                        <div class="p-3 bg-green-50 rounded-lg border border-green-200">
                                            <strong>Selected:</strong> <?php echo htmlspecialchars($staffName); ?>
                                            <?php if ($pfaName): ?>
                                            <br><strong>PFA:</strong> <?php echo htmlspecialchars($pfaName); ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Email Address -->
                                <div>
                                    <label for="recipient_email" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-envelope mr-2 text-green-600"></i>Recipient Email
                                    </label>
                                    <input type="email" id="recipient_email" name="recipient_email"
                                        value="<?php echo htmlspecialchars($recipientEmail ?: $staffEmail); ?>"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm"
                                        placeholder="Enter email address to send report">
                                    <p class="mt-1 text-xs text-gray-500">Leave empty to download instead of emailing
                                    </p>
                                </div>

                                <!-- Period From -->
                                <div>
                                    <label for="period_from" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calendar-alt mr-2 text-purple-600"></i>Period From
                                    </label>
                                    <select name="period_from" id="period_from"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm"
                                        required>
                                        <option value="">Select Period From</option>
                                        <?php
                                        try {
                                            $query = $conn->prepare('SELECT description, periodYear, periodId FROM payperiods WHERE payrollRun = ? ORDER BY periodId ASC');
                                            $query->execute(['1']);
                                            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                                                $selected = $row['periodId'] == $periodFrom ? 'selected' : '';
                                                echo "<option value='{$row['periodId']}' $selected>{$row['description']} - {$row['periodYear']}</option>";
                                            }
                                        } catch (PDOException $e) {
                                            echo "<option value=''>Error loading periods</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <!-- Period To -->
                                <div>
                                    <label for="period_to" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calendar-check mr-2 text-orange-600"></i>Period To
                                    </label>
                                    <select name="period_to" id="period_to"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm"
                                        required>
                                        <option value="">Select Period To</option>
                                        <?php
                                        try {
                                            $query = $conn->prepare('SELECT description, periodYear, periodId FROM payperiods WHERE payrollRun = ? ORDER BY periodId ASC');
                                            $query->execute(['1']);
                                            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                                                $selected = $row['periodId'] == $periodTo ? 'selected' : '';
                                                echo "<option value='{$row['periodId']}' $selected>{$row['description']} - {$row['periodYear']}</option>";
                                            }
                                        } catch (PDOException $e) {
                                            echo "<option value=''>Error loading periods</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <button name="generate_report" type="submit" id="generate_report"
                                    class="bg-blue-700 hover:bg-blue-900 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-search"></i> Generate Report
                                </button>
                                <button type="button" id="download-excel-button"
                                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2"
                                    <?php echo (empty($reportData) || empty($staffId)) ? 'disabled' : ''; ?>>
                                    <i class="fas fa-file-excel"></i> Download Excel
                                </button>
                                <button type="button" id="send-email-button"
                                    class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                    <?php echo (empty($reportData) || empty($staffId)) ? 'disabled' : ''; ?>>
                                    <i class="fas fa-paper-plane"></i> Send via Email
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if (!empty($reportData) && $staffId): ?>
                <!-- Report Header -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
                    <div class="bg-blue-50 px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold text-blue-800 text-center">
                            OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL
                        </h2>
                        <p class="text-center text-blue-700 font-medium mt-2">
                            Individual Pension Fund Report for: <?php echo htmlspecialchars($staffName); ?>
                        </p>
                        <p class="text-center text-blue-600 text-sm mt-1">
                            Staff ID: <?php echo htmlspecialchars($staffId); ?>
                            <?php if ($pfaName): ?>
                            | PFA: <?php echo htmlspecialchars($pfaName); ?>
                            <?php endif; ?>
                            <?php if ($pfaPin): ?>
                            | PIN: <?php echo htmlspecialchars($pfaPin); ?>
                            <?php endif; ?>
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
                                        Period</th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        Pension Contribution (₦)</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php
                                $counter = 1;
                                $sumTotal = 0;
                                
                                foreach ($reportData as $row) {
                                    $periodText = $row['description'] . ' ' . $row['periodYear'];
                                    $amount = floatval($row['deduc']);
                                    $sumTotal += $amount;
                                    
                                    echo '<tr class="hover:bg-gray-50 transition-colors duration-150">';
                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . $counter . '</td>';
                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($periodText) . '</td>';
                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">₦' . number_format($amount, 2) . '</td>';
                                    echo '</tr>';
                                    $counter++;
                                }
                                
                                // Total row
                                if (count($reportData) > 0) {
                                    echo '<tr class="bg-blue-50 border-t-2 border-blue-200">';
                                    echo '<td colspan="2" class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">TOTAL CONTRIBUTIONS</td>';
                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">₦' . number_format($sumTotal, 2) . '</td>';
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
                                    echo date('l, F d, Y');
                                ?></p>
                            </div>
                            <div class="text-sm text-gray-600">
                                <p><strong>Staff:</strong> <?php echo htmlspecialchars($staffName); ?></p>
                                <p><strong>Period Range:</strong>
                                    <?php 
                                    if ($periodFrom != -1 && $periodTo != -1) {
                                        try {
                                            $q1 = $conn->prepare('SELECT description, periodYear FROM payperiods WHERE periodId = ?');
                                            $q1->execute([$periodFrom]);
                                            $from = $q1->fetch(PDO::FETCH_ASSOC);
                                            
                                            $q2 = $conn->prepare('SELECT description, periodYear FROM payperiods WHERE periodId = ?');
                                            $q2->execute([$periodTo]);
                                            $to = $q2->fetch(PDO::FETCH_ASSOC);
                                            
                                            if ($from && $to) {
                                                echo htmlspecialchars($from['description'] . ' ' . $from['periodYear'] . ' to ' . $to['description'] . ' ' . $to['periodYear']);
                                            }
                                        } catch (PDOException $e) {
                                            echo 'N/A';
                                        }
                                    }
                                    ?>
                                </p>
                                <?php if ($sumTotal > 0): ?>
                                <p><strong>Total Contributions:</strong> ₦<?php echo number_format($sumTotal, 2); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script type="text/javascript" language="javascript">
    $(document).ready(function() {
        // Staff autocomplete
        $('#staff_search').autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: '../searchStaff.php',
                    dataType: 'json',
                    data: {
                        term: request.term
                    },
                    success: function(data) {
                        response($.map(data, function(item) {
                            return {
                                label: item.label,
                                value: item.value,
                                staff_id: item.id,
                                email: item.EMAIL
                            };
                        }));
                    }
                });
            },
            minLength: 2,
            select: function(event, ui) {
                $('#staff_id').val(ui.item.staff_id);
                $('#staff_search').val(ui.item.label);

                // Update email field if empty
                if (!$('#recipient_email').val() && ui.item.email) {
                    $('#recipient_email').val(ui.item.email);
                }

                // Show staff info
                $('#staff_info').html(
                    '<div class="p-3 bg-green-50 rounded-lg border border-green-200">' +
                    '<strong>Selected:</strong> ' + ui.item.label.split(' - ')[1] +
                    '</div>'
                );

                return false;
            }
        });

        // Form validation
        $('#reportForm').on('submit', function(e) {
            if (!$('#staff_id').val()) {
                e.preventDefault();
                Swal.fire('Error', 'Please select a staff member', 'error');
                return false;
            }

            if (!$('#period_from').val() || !$('#period_to').val()) {
                e.preventDefault();
                Swal.fire('Error', 'Please select both Period From and Period To', 'error');
                return false;
            }
        });

        // Download Excel
        $('#download-excel-button').click(function() {
            if (!$('#staff_id').val() || !$('#period_from').val() || !$('#period_to').val()) {
                Swal.fire('Error', 'Please generate the report first', 'error');
                return;
            }

            downloadExcel();
        });

        // Send Email - use document.on for better event handling
        $(document).on('click', '#send-email-button:not(:disabled)', function(e) {
            e.preventDefault();
            e.stopPropagation();

            console.log('Send email button clicked');

            const email = $('#recipient_email').val().trim();

            if (!email) {
                Swal.fire('Error', 'Please enter a recipient email address', 'error');
                $('#recipient_email').focus();
                return false;
            }

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                Swal.fire('Error', 'Please enter a valid email address', 'error');
                $('#recipient_email').focus();
                return false;
            }

            if (!$('#staff_id').val() || !$('#period_from').val() || !$('#period_to').val()) {
                Swal.fire('Error', 'Please generate the report first', 'error');
                return false;
            }

            console.log('Calling sendEmail with:', email);
            sendEmail(email);
            return false;
        });

        // Also handle disabled button clicks with helpful message
        $(document).on('click', '#send-email-button:disabled', function(e) {
            e.preventDefault();
            Swal.fire('Info', 'Please enter a recipient email address to enable email sending', 'info');
            $('#recipient_email').focus();
            return false;
        });

        // Debug: Check button state after DOM is ready
        setTimeout(function() {
            console.log('Send email button check:', {
                exists: $('#send-email-button').length > 0,
                disabled: $('#send-email-button').prop('disabled'),
                visible: $('#send-email-button').is(':visible'),
                hasClickHandler: $('#send-email-button').data('events') !== undefined
            });
        }, 1000);

        function downloadExcel() {
            Swal.fire({
                title: 'Generating Excel',
                text: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                type: "POST",
                url: 'pfa_individual_export.php',
                data: {
                    staff_id: $('#staff_id').val(),
                    staff_name: '<?php echo htmlspecialchars($staffName); ?>',
                    period_from: $('#period_from').val(),
                    period_to: $('#period_to').val(),
                    pfa_name: '<?php echo htmlspecialchars($pfaName); ?>',
                    pfa_pin: '<?php echo htmlspecialchars($pfaPin); ?>',
                    action: 'download'
                },
                timeout: 300000,
                dataType: 'text', // Expect text/base64 response
                success: function(response) {
                    Swal.close();
                    try {
                        // Check if response is JSON (error case)
                        let jsonResponse = null;
                        try {
                            jsonResponse = typeof response === 'string' ? JSON.parse(response) :
                                response;
                        } catch (e) {
                            // Not JSON, continue as base64
                        }

                        if (jsonResponse && (jsonResponse.error || jsonResponse.message)) {
                            Swal.fire('Error', jsonResponse.error || jsonResponse.message ||
                                'Server error occurred', 'error');
                            return;
                        }

                        if (typeof response === 'string' && (response.includes('<!DOCTYPE html>') ||
                                response.includes('<html'))) {
                            Swal.fire('Error', 'Server error occurred. Please try again.', 'error');
                            return;
                        }

                        // Response should be base64 string
                        const base64Data = typeof response === 'string' ? response.trim() :
                            response;

                        if (!base64Data || base64Data.length < 100) {
                            Swal.fire('Error', 'Invalid response from server', 'error');
                            return;
                        }

                        var downloadLink = document.createElement('a');
                        downloadLink.href =
                            'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' +
                            base64Data;
                        downloadLink.download =
                            'Pension_Report_<?php echo htmlspecialchars($staffId); ?>_<?php echo date('Y-m-d'); ?>.xlsx';
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);

                        Swal.fire('Success', 'Excel file downloaded successfully', 'success');
                    } catch (e) {
                        console.error('Error processing Excel response:', e, response);
                        Swal.fire('Error', 'Error generating Excel file. Please try again.',
                            'error');
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close();
                    console.error('AJAX Error:', status, error, xhr.responseText);

                    let errorMsg = 'Error downloading Excel file. Please try again.';
                    try {
                        if (xhr.responseText) {
                            const response = JSON.parse(xhr.responseText);
                            if (response.error || response.message) {
                                errorMsg = response.error || response.message;
                            }
                        }
                    } catch (e) {
                        // Use default error message
                    }

                    Swal.fire('Error', errorMsg, 'error');
                }
            });
        }

        function sendEmail(email) {
            console.log('sendEmail function called with email:', email);

            Swal.fire({
                title: 'Sending Email',
                text: 'Please wait while we generate and send the report...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const requestData = {
                staff_id: $('#staff_id').val(),
                staff_name: '<?php echo htmlspecialchars($staffName); ?>',
                period_from: $('#period_from').val(),
                period_to: $('#period_to').val(),
                pfa_name: '<?php echo htmlspecialchars($pfaName); ?>',
                pfa_pin: '<?php echo htmlspecialchars($pfaPin); ?>',
                recipient_email: email,
                action: 'email'
            };

            console.log('Sending AJAX request with data:', requestData);

            $.ajax({
                type: "POST",
                url: 'pfa_individual_export.php',
                data: requestData,
                timeout: 300000,
                dataType: 'json',
                beforeSend: function() {
                    console.log('AJAX request started');
                },
                success: function(response) {
                    console.log('AJAX success response:', response);
                    Swal.close();

                    // Handle string response that might be JSON
                    let jsonResponse = response;
                    if (typeof response === 'string') {
                        try {
                            jsonResponse = JSON.parse(response);
                        } catch (e) {
                            console.error('Failed to parse JSON response:', e, response);
                            Swal.fire('Error', 'Invalid response from server', 'error');
                            return;
                        }
                    }

                    if (jsonResponse && jsonResponse.success) {
                        Swal.fire('Success', jsonResponse.message || 'Email sent successfully',
                            'success');
                    } else {
                        Swal.fire('Error', jsonResponse.message || 'Failed to send email', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error Details:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        statusCode: xhr.status,
                        readyState: xhr.readyState
                    });

                    Swal.close();

                    let errorMsg = 'Error sending email. Please try again.';
                    try {
                        if (xhr.responseText) {
                            // Try to parse as JSON
                            const response = JSON.parse(xhr.responseText);
                            if (response.message || response.error) {
                                errorMsg = response.message || response.error;
                            }
                        } else if (xhr.status === 0) {
                            errorMsg = 'Network error. Please check your connection.';
                        } else if (xhr.status === 404) {
                            errorMsg = 'Server endpoint not found. Please contact administrator.';
                        } else if (xhr.status === 500) {
                            errorMsg = 'Server error. Please try again or contact administrator.';
                        }
                    } catch (e) {
                        // Use default error message or response text if short
                        if (xhr.responseText && xhr.responseText.length < 200) {
                            errorMsg = xhr.responseText;
                        }
                    }

                    Swal.fire('Error', errorMsg, 'error');
                }
            });
        }

        // Enable/disable buttons based on form state
        function updateButtonStates() {
            const hasData = $('#staff_id').val() && $('#period_from').val() && $('#period_to').val();
            $('#download-excel-button').prop('disabled', !hasData);

            const emailVal = $('#recipient_email').val().trim();
            const hasEmail = hasData && emailVal;
            $('#send-email-button').prop('disabled', !hasEmail);

            console.log('Button states updated:', {
                hasData: hasData,
                emailValue: emailVal,
                hasEmail: hasEmail,
                emailButtonDisabled: $('#send-email-button').prop('disabled'),
                emailButtonExists: $('#send-email-button').length > 0
            });
        }

        // Update on form field changes
        $('#staff_id, #period_from, #period_to, #recipient_email').on('change input keyup paste', function() {
            updateButtonStates();
        });

        // Update button states on page load
        setTimeout(function() {
            updateButtonStates();
        }, 100);

        // Also update after a short delay to ensure all fields are populated (especially after form submission)
        setTimeout(function() {
            updateButtonStates();
        }, 500);

        // Force enable send email button if all conditions are met (even if initially disabled)
        if ($('#staff_id').val() && $('#period_from').val() && $('#period_to').val()) {
            $('#send-email-button').prop('disabled', false);
            setTimeout(function() {
                const emailVal = $('#recipient_email').val().trim();
                if (!emailVal) {
                    $('#send-email-button').prop('disabled', true);
                }
            }, 200);
        }
    });
    </script>
</body>

</html>