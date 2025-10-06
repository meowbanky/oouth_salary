<?php
ini_set('max_execution_time', '0');
require_once('../Connections/paymaster.php');
include_once('../classes/model.php');
require_once('../libs/App.php');
$App = new App();
$App->checkAuthentication();
require_once('../libs/middleware.php');
checkPermission();

if (isset($_GET['period'])) {
    $period = $_GET['period'];
} else {
    $period = $_SESSION['currentactiveperiod'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Payslip - OOUTH Salary Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/dark-mode.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/ui-lightness/jquery-ui.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery-form@4.3.0/dist/jquery.form.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/theme-manager.js"></script>
    <style>
    /* Printable area styling with watermark */
    .printMe {
        position: relative;
    }

    .watermark {
        position: absolute;
        inset: 0;
        background: url('img/oouth_logo.png') center center no-repeat;
        background-size: 60%;
        opacity: 0.05;
        /* very faint */
        pointer-events: none;
    }

    @media print {
        .watermark {
            opacity: 0.07;
        }

        .printMe {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Balanced compact layout for single page */
        .printMe .p-4 {
            padding: 0.5rem !important;
        }

        .printMe .p-6 {
            padding: 0.5rem !important;
        }

        .printMe .p-2 {
            padding: 0.375rem !important;
        }

        .printMe .mb-4 {
            margin-bottom: 0.25rem !important;
        }

        .printMe .mb-6 {
            margin-bottom: 0.25rem !important;
        }

        .printMe .mb-2 {
            margin-bottom: 0.25rem !important;
        }

        .printMe .mb-1 {
            margin-bottom: 0.125rem !important;
        }

        .printMe .py-1 {
            padding-top: 0.125rem !important;
            padding-bottom: 0.125rem !important;
        }

        .printMe .py-2 {
            padding-top: 0.125rem !important;
            padding-bottom: 0.125rem !important;
        }

        .printMe .py-4 {
            padding-top: 0.25rem !important;
            padding-bottom: 0.25rem !important;
        }

        .printMe .py-0 {
            padding-top: 0.0625rem !important;
            padding-bottom: 0.0625rem !important;
        }

        .printMe .text-base {
            font-size: 0.875rem !important;
        }

        .printMe .text-lg {
            font-size: 0.875rem !important;
        }

        .printMe .text-xl {
            font-size: 1rem !important;
        }

        .printMe .text-2xl {
            font-size: 1rem !important;
        }

        .printMe .text-sm {
            font-size: 0.75rem !important;
        }

        .printMe .text-xs {
            font-size: 0.75rem !important;
        }

        .printMe .grid-cols-2 {
            display: block !important;
        }

        .printMe .grid-cols-2>div {
            margin-bottom: 0.125rem !important;
        }

        .printMe .gap-2 {
            gap: 0.125rem !important;
        }

        .printMe .gap-4 {
            gap: 0.125rem !important;
        }

        .printMe .gap-1 {
            gap: 0.125rem !important;
        }

        .printMe .mt-2 {
            margin-top: 0.125rem !important;
        }

        .printMe .mt-4 {
            margin-top: 0.125rem !important;
        }

        .printMe .mt-1 {
            margin-top: 0.125rem !important;
        }

        .printMe .rounded-lg {
            border-radius: 0.25rem !important;
        }

        .printMe .rounded {
            border-radius: 0.25rem !important;
        }

        /* Force single page with balanced constraints */
        .printMe {
            page-break-inside: avoid;
            max-height: 95vh;
            overflow: hidden;
        }

        /* Balanced line height for readability */
        .printMe * {
            line-height: 1.2 !important;
        }
    }
    </style>
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
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Personal Payslip</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                <!-- Header Section -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-blue-800 flex items-center gap-2">
                            <i class="fas fa-user"></i> Personal Payslip
                        </h1>
                        <p class="text-sm text-blue-700/70 mt-1">Generate individual employee payslips with detailed
                            salary breakdown.</p>
                    </div>
                </div>

                <!-- Report Form -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
                    <div class="bg-blue-50 px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold text-blue-800 flex items-center gap-2">
                            <i class="fas fa-filter"></i> Search Parameters
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label for="period" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-calendar-alt mr-2 text-blue-600"></i>Pay Period
                                </label>
                                <select name="period" id="period"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm">
                                    <option value="">Select Pay Period</option>
                                    <?php
                                    try {
                                        $query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear, payperiods.periodId FROM payperiods WHERE payrollRun = ? order by periodId desc');
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
                                <label for="item" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-search mr-2 text-green-600"></i>Staff Search
                                </label>
                                <div class="relative">
                                    <input type="text" name="item" id="item"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm"
                                        placeholder="Enter Staff Name or Staff No" />
                                    <div id="ajax-loader" class="absolute right-3 top-3 hidden">
                                        <i class="fas fa-spinner fa-spin text-blue-600"></i>
                                    </div>
                                </div>
                                <input type="hidden" name="staff_id" id="staff_id" value="">
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3 mt-6">
                            <button type="button" onclick="generatePayslip()"
                                class="bg-blue-700 hover:bg-blue-900 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                <i class="fas fa-search"></i> Generate Payslip
                            </button>
                            <button type="button" id="btnPrint"
                                class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <button type="button" id="sendmail"
                                class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                <i class="fas fa-envelope"></i> Send Email
                            </button>
                        </div>
                    </div>
                </div>

                <?php
                // Get period description for display
                $fullPeriod = '';
                if ($period != -1) {
                    try {
                        $query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear FROM payperiods WHERE periodId = ?');
                        $query->execute([$period]);
                        $row = $query->fetch(PDO::FETCH_ASSOC);
                        $fullPeriod = $row ? $row['description'] . '-' . $row['periodYear'] : '';
                    } catch (PDOException $e) {
                        $fullPeriod = 'Error loading period';
                    }
                }
                ?>

                <!-- Payslip Display Section -->
                <?php if (isset($_GET['item']) && $_GET['item'] != '') { ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-blue-50 px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold text-blue-800 text-center">
                            OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL
                        </h2>
                        <p class="text-center text-blue-700 font-medium mt-2">
                            Payslip for <?php echo htmlspecialchars($fullPeriod); ?>
                        </p>
                    </div>

                    <div class="p-6">
                        <?php
                            $item = $_GET['item'];
                            try {
                                $query = $conn->prepare('SELECT staff_id FROM master_staff WHERE staff_id=? and period = ?');
                                $query->execute([$item, $period]);
                                $ftres = $query->fetchAll(PDO::FETCH_COLUMN);
                                $count = $query->rowCount();
                                
                                if ($count > 0) {
                                    $thisemployee = $ftres[0];
                                    
                                    // Get employee details
                                    $query = $conn->prepare('SELECT tbl_bank.BNAME, tbl_dept.dept, master_staff.STEP, master_staff.GRADE, master_staff.staff_id, master_staff.NAME, master_staff.ACCTNO FROM master_staff INNER JOIN tbl_dept ON tbl_dept.dept_id = master_staff.DEPTCD INNER JOIN tbl_bank ON tbl_bank.BCODE = master_staff.BCODE WHERE staff_id = ? and period = ?');
                                    $query->execute([$thisemployee, $period]);
                                    $out = $query->fetch();
                                    
                                    if ($out) {
                                        // Employee Information Card
                                        echo '<div class="bg-gray-50 rounded-lg p-6 mb-6">';
                                        echo '<h3 class="text-lg font-semibold text-gray-800 mb-4">Employee Information</h3>';
                                        echo '<div class="grid md:grid-cols-2 gap-4">';
                                        echo '<div><strong>Name:</strong> ' . htmlspecialchars($out['NAME']) . '</div>';
                                        echo '<div><strong>Staff No:</strong> ' . htmlspecialchars($out['staff_id']) . '</div>';
                                        echo '<div><strong>Department:</strong> ' . htmlspecialchars($out['dept']) . '</div>';
                                        echo '<div><strong>Bank:</strong> ' . htmlspecialchars($out['BNAME']) . '</div>';
                                        echo '<div><strong>Account No:</strong> ' . htmlspecialchars($out['ACCTNO']) . '</div>';
                                        echo '<div><strong>Grade/Step:</strong> ' . htmlspecialchars($out['GRADE'] . '/' . $out['STEP']) . '</div>';
                                        echo '</div>';
                                        echo '</div>';
                                        
                                        // Payslip Details (printable area)
                                        echo '<div id="printThis" class="printMe">';
                                        // Watermark layer
                                        echo '<div class="watermark"></div>';
                                        // Include employee information inside printable area too (balanced)
                                        echo '<div class="bg-gray-50 rounded p-3 mb-3 relative">';
                                        echo '<h3 class="text-sm font-semibold text-gray-800 mb-2">Employee Information</h3>';
                                        echo '<div class="grid md:grid-cols-2 gap-2 text-sm">';
                                        echo '<div><strong>Name:</strong> ' . htmlspecialchars($out['NAME']) . '</div>';
                                        echo '<div><strong>Staff No:</strong> ' . htmlspecialchars($out['staff_id']) . '</div>';
                                        echo '<div><strong>Department:</strong> ' . htmlspecialchars($out['dept']) . '</div>';
                                        echo '<div><strong>Bank:</strong> ' . htmlspecialchars($out['BNAME']) . '</div>';
                                        echo '<div><strong>Account No:</strong> ' . htmlspecialchars($out['ACCTNO']) . '</div>';
                                        echo '<div><strong>Grade/Step:</strong> ' . htmlspecialchars($out['GRADE'] . '/' . $out['STEP']) . '</div>';
                                        echo '</div>';
                                        echo '</div>';
                                        
                                        // Consolidated Salary
                                        $consolidated = 0;
                                        try {
                                            $query = $conn->prepare('SELECT tbl_master.staff_id,tbl_master.allow FROM tbl_master WHERE allow_id = ? and staff_id = ? and period = ?');
                                            $query->execute(['1', $thisemployee, $period]);
                                            $res = $query->fetch();
                                            if ($query->rowCount() > 0 && $res) {
                                                $consolidated = $res['allow'];
                                            }
                                        } catch (PDOException $e) {
                                            $consolidated = 0;
                                        }
                                        
                                        echo '<div class="bg-white border rounded p-3 mb-3">';
                                        echo '<h4 class="text-sm font-semibold text-blue-800 mb-2">CONSOLIDATED SALARY</h4>';
                                        echo '<div class="flex justify-between items-center py-1 border-b">';
                                        echo '<span class="text-sm">Consolidated Salary:</span>';
                                        echo '<span class="font-semibold text-sm">₦' . number_format($consolidated) . '</span>';
                                        echo '</div>';
                                        echo '</div>';
                                        
                                        // Allowances
                                        $totalAllow = 0;
                                        try {
                                            $query = $conn->prepare('SELECT tbl_master.staff_id, tbl_master.allow, tbl_earning_deduction.ed FROM tbl_master INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id WHERE allow_id <> ? and staff_id = ? and period = ? and type = ?');
                                            $query->execute(['1', $thisemployee, $period, '1']);
                                            $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            if (count($res) > 0) {
                                                echo '<div class="bg-white border rounded p-3 mb-3">';
                                                echo '<h4 class="text-sm font-semibold text-green-800 mb-2">ALLOWANCES</h4>';
                                                
                                                foreach ($res as $link) {
                                                    $totalAllow += floatval($link['allow']);
                                                    echo '<div class="flex justify-between items-center py-1 border-b">';
                                                    echo '<span class="text-sm">' . htmlspecialchars($link['ed']) . ':</span>';
                                                    echo '<span class="font-semibold text-sm">₦' . number_format($link['allow']) . '</span>';
                                                    echo '</div>';
                                                }
                                                echo '</div>';
                                            }
                                        } catch (PDOException $e) {
                                            // Handle error silently
                                        }
                                        
                                        // Gross Salary
                                        $grossSalary = $totalAllow + $consolidated;
                                        echo '<div class="bg-green-50 border border-green-200 rounded p-3 mb-3">';
                                        echo '<div class="flex justify-between items-center">';
                                        echo '<span class="text-sm font-semibold text-green-800">Gross Salary:</span>';
                                        echo '<span class="text-sm font-bold text-green-800">₦' . number_format($grossSalary) . '</span>';
                                        echo '</div>';
                                        echo '</div>';
                                        
                                        // Deductions
                                        $totalDeduction = 0;
                                        try {
                                            $query = $conn->prepare('SELECT tbl_master.staff_id, tbl_master.deduc, tbl_earning_deduction.ed FROM tbl_master INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id WHERE staff_id = ? and period = ? and type = ?');
                                            $query->execute([$thisemployee, $period, '2']);
                                            $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            if (count($res) > 0) {
                                                echo '<div class="bg-white border rounded p-3 mb-3">';
                                                echo '<h4 class="text-sm font-semibold text-red-800 mb-2">DEDUCTIONS</h4>';
                                                
                                                foreach ($res as $link) {
                                                    $totalDeduction += floatval($link['deduc']);
                                                    echo '<div class="flex justify-between items-center py-1 border-b">';
                                                    echo '<span class="text-sm">' . htmlspecialchars($link['ed']) . ':</span>';
                                                    echo '<span class="font-semibold text-sm">₦' . number_format($link['deduc']) . '</span>';
                                                    echo '</div>';
                                                }
                                                
                                                echo '<div class="flex justify-between items-center py-1 mt-2 bg-red-50 rounded">';
                                                echo '<span class="font-semibold text-red-800 text-sm">Total Deductions:</span>';
                                                echo '<span class="font-bold text-red-800 text-sm">₦' . number_format($totalDeduction) . '</span>';
                                                echo '</div>';
                                                echo '</div>';
                                            }
                                        } catch (PDOException $e) {
                                            // Handle error silently
                                        }
                                        
                                        // Net Pay
                                        $netPay = $grossSalary - $totalDeduction;
                                        echo '<div class="bg-blue-50 border border-blue-200 rounded p-3">';
                                        echo '<div class="flex justify-between items-center">';
                                        echo '<span class="text-sm font-bold text-blue-800">Net Pay:</span>';
                                        echo '<span class="text-base font-bold text-blue-800">₦' . number_format($netPay) . '</span>';
                                        echo '</div>';
                                        echo '</div>';
                                        
                                        echo '</div>'; // End printMe div
                                        
                                        // Hidden form for email sending
                                        echo '<form id="form_payprocess" method="post" class="hidden">';
                                        echo '<input type="hidden" name="staff_no" id="staff_no" value="' . htmlspecialchars($thisemployee) . '">';
                                        echo '<input type="hidden" name="period" id="period_hidden" value="' . htmlspecialchars($period) . '">';
                                        echo '</form>';
                                    }
                                } else {
                                    echo '<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">';
                                    echo '<i class="fas fa-exclamation-triangle text-yellow-600 text-3xl mb-4"></i>';
                                    echo '<h3 class="text-lg font-semibold text-yellow-800 mb-2">Staff Not Found</h3>';
                                    echo '<p class="text-yellow-700">No payslip data found for the selected staff member and period.</p>';
                                    echo '</div>';
                                }
                            } catch (PDOException $e) {
                                echo '<div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">';
                                echo '<i class="fas fa-exclamation-circle text-red-600 text-3xl mb-4"></i>';
                                echo '<h3 class="text-lg font-semibold text-red-800 mb-2">Error</h3>';
                                echo '<p class="text-red-700">Error loading payslip data: ' . htmlspecialchars($e->getMessage()) . '</p>';
                                echo '</div>';
                            }
                            ?>
                    </div>
                </div>
                <?php } ?>
            </div>
        </main>
    </div>

    <script type="text/javascript" language="javascript">
    $(document).ready(function() {
        // Autocomplete for staff search
        $("#item").autocomplete({
            source: '../searchStaff.php',
            type: 'POST',
            delay: 10,
            autoFocus: false,
            minLength: 1,
            select: function(event, ui) {
                event.preventDefault();
                $("#item").val(ui.item.value);
                $("#staff_id").val(ui.item.value);
            }
        });

        $('#item').focus();

        // Email sending functionality
        $('#sendmail').click(function() {
            event.preventDefault();

            const staff_no = $('#staff_no').val();
            const period = $('#period_hidden').val();

            if (!staff_no || !period) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Information',
                    text: 'Please generate a payslip first before sending email.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#1E40AF'
                });
                return;
            }

            Swal.fire({
                title: 'Send Email?',
                text: 'This will send the payslip to the employee\'s email address.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1E40AF',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Send Email',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    sendEmail();
                }
            });
        });

        function sendEmail() {
            const staff_no = $('#staff_no').val();
            const period = $('#period_hidden').val();
            const All = 0;

            // Show loading state
            Swal.fire({
                title: 'Sending Email...',
                text: 'Please wait while we send the payslip.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Create form data
            const formData = new FormData();
            formData.append('staff_no', staff_no);
            formData.append('period', period);
            formData.append('All', All);

            $.ajax({
                url: 'callPdf.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response, textStatus, xhr) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Email Sent Successfully!',
                        text: 'The payslip has been sent to the employee\'s email address.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#1E40AF'
                    });
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error Sending Email',
                        text: 'There was an error sending the email. Please try again.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#1E40AF'
                    });
                }
            });
        }
    });

    function generatePayslip() {
        const period = $('#period').val();
        const staffId = $('#staff_id').val();

        if (!period) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Information',
                text: 'Please select a pay period.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#1E40AF'
            });
            return;
        }

        if (!staffId) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Information',
                text: 'Please search and select a staff member.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#1E40AF'
            });
            return;
        }

        // Show loading state
        Swal.fire({
            title: 'Generating Payslip...',
            text: 'Please wait while we generate the payslip.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        window.location.href = "payslip_personal.php?item=" + staffId + "&period=" + period;
    }

    // Print functionality
    $(document).ready(function() {
        $('#btnPrint').click(function() {
            // Ensure the printable content includes the watermark and personal details
            $('.printMe').printElem();
        });
    });

    // Print element extension
    jQuery.fn.extend({
        printElem: function() {
            var cloned = this.clone();
            var printSection = $('#printSection');
            if (printSection.length == 0) {
                printSection = $('<div id="printSection"></div>');
                $('body').append(printSection);
            }
            printSection.append(cloned);
            var toggleBody = $('body *:visible');
            toggleBody.hide();
            $('#printSection, #printSection *').show();
            window.print();
            printSection.remove();
            toggleBody.show();
        }
    });
    </script>
</body>

</html>