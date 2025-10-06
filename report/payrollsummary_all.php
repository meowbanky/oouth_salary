<?php
require_once('../Connections/paymaster.php');
include_once('../classes/model.php');
require_once('../libs/App.php');
$App = new App();
$App->checkAuthentication();
require_once('../libs/middleware.php');
checkPermission();

// Initialize variables
$month = '';
$period = isset($_POST['period']) ? $_POST['period'] : (isset($_GET['period']) ? $_GET['period'] : -1);

// Get period information
if ($period != -1) {
    try {
        $query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear FROM payperiods WHERE periodId = ?');
        $query->execute([$period]);
        $result = $query->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $month = $result['description'] . '-' . $result['periodYear'];
        }
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
    <title>Payroll Summary - OOUTH Salary Management</title>
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
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Payroll Summary</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                <!-- Header Section -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-blue-800 flex items-center gap-2">
                            <i class="fas fa-calculator"></i> Payroll Summary Report
                        </h1>
                        <p class="text-sm text-blue-700/70 mt-1">Generate comprehensive payroll summary reports for all
                            employees.</p>
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
                        <form method="POST" action="payrollsummary_all.php" class="space-y-6">
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
                                        global $conn;
                                        try {
                                            $query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear, payperiods.periodId FROM payperiods WHERE payrollRun = ? order by periodId desc');
                                            $res = $query->execute(array('1'));
                                            $out = $query->fetchAll(PDO::FETCH_ASSOC);

                                            while ($row = array_shift($out)) {
                                                echo '<option value="' . $row['periodId'] . '"';
                                                if ($row['periodId'] == $_SESSION['currentactiveperiod']) {
                                                    echo 'selected = "selected"';
                                                };
                                                echo ' >' . $row['description'] . ' - ' . $row['periodYear'] . '</option>';
                                            }
                                        } catch (PDOException $e) {
                                            echo $e->getMessage();
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
                                <button type="button" id="export-pdf-button"
                                    class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-file-pdf"></i> Export PDF
                                </button>
                                <button type="button" id="download-excel-button"
                                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-file-excel"></i> Download Excel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if (isset($_POST['generate_report']) && isset($_POST['period']) && $_POST['period'] != '') { ?>
                <!-- Report Header -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
                    <div class="bg-blue-50 px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold text-blue-800 text-center">
                            OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL
                        </h2>
                        <p class="text-center text-blue-700 font-medium mt-2">
                            PAYROLL SUMMARY FOR THE MONTH OF: <?php 
                                $month = '';
                                global $conn;
                                if (!isset($_POST['period'])) {
                                    $period = -1;
                                } else {
                                    $period = $_POST['period'];
                                }
                                try {
                                    $query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear, payperiods.periodId FROM payperiods WHERE periodId = ?');
                                    $res = $query->execute(array($period));
                                    $out = $query->fetchAll(PDO::FETCH_ASSOC);

                                    while ($row = array_shift($out)) {
                                        echo ($month = $row['description'] . '-' . $row['periodYear']);
                                    }
                                } catch (PDOException $e) {
                                    $e->getMessage();
                                }
                                ?>
                        </p>
                    </div>
                </div>
                <?php } ?>
                <?php if (isset($_POST['generate_report']) && isset($_POST['period']) && $_POST['period'] != '') { ?>
                <!-- Report Table -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="sample_1">
                            <thead class="bg-blue-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        Code</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        Description</th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        Amount</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <!-- Earnings Section Header -->
                                <tr class="bg-green-50">
                                    <td colspan="3"
                                        class="px-6 py-4 text-center text-sm font-bold text-green-800 uppercase">
                                        <i class="fas fa-plus-circle mr-2"></i>Earnings
                                    </td>
                                </tr>
                                <?php
                                //retrieveData('employment_types', 'id', '2', '1');
                                if (!isset($_POST['period'])) {
                                    $period = -1;
                                } else {
                                    $period = $_POST['period'];
                                }
                                try {
                                    $query = $conn->prepare('SELECT sum(tbl_master.allow) as allow,allow_id, tbl_earning_deduction.ed FROM tbl_master INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id WHERE tbl_master.type = ? and period = ? GROUP BY tbl_master.allow_id ');
                                    $fin = $query->execute(array('1', $period));
                                    $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                    $numberofstaff = count($res);
                                    $counter = 1;
                                    $sumAll = 0;
                                    $sumDeduct = 0;
                                    $sumTotal = 0;
                                    if ($numberofstaff > 0) {
                                        foreach ($res as $row => $link) {
                                            echo '<tr class="hover:bg-gray-50 transition-colors duration-150">';
                                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . $link['allow_id'] . '</td>';
                                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . $link['ed'] . '</td>';
                                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">₦' . number_format($link['allow']) . '</td>';
                                            $sumAll = $sumAll + floatval($link['allow']);
                                            $counter++;
                                            echo '</tr>';
                                        }
                                        echo '<tr class="bg-green-50 border-t-2 border-green-200">';
                                        echo '<td colspan="2" class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">TOTAL EARNINGS</td>';
                                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">₦' . number_format($sumAll) . '</td>';
                                        echo '</tr>';
                                    }
                                } catch (PDOException $e) {
                                    echo $e->getMessage();
                                }
                                ?>

                                <!-- Deductions Section Header -->
                                <tr class="bg-red-50">
                                    <td colspan="3"
                                        class="px-6 py-4 text-center text-sm font-bold text-red-800 uppercase">
                                        <i class="fas fa-minus-circle mr-2"></i>Deductions
                                    </td>
                                </tr>

                                <!-- Deduction summary -->
                                <?php
                                    try {
                                        $query = $conn->prepare('SELECT sum(tbl_master.deduc) as deduct, allow_id,tbl_earning_deduction.ed FROM tbl_master INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id WHERE tbl_master.type = ? and period = ? GROUP BY tbl_master.allow_id ');
                                        $fin = $query->execute(array('2', $period));
                                        $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                        $numberofstaff = count($res);
                                        $counter = 1;
                                        $sumDeduct = 0;
                                        $sumTotal = 0;
                                        if ($numberofstaff > 0) {
                                            foreach ($res as $row => $link) {
                                                echo '<tr class="hover:bg-gray-50 transition-colors duration-150">';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . $link['allow_id'] . '</td>';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . $link['ed'] . '</td>';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">₦' . number_format($link['deduct']) . '</td>';
                                                $sumDeduct = $sumDeduct + floatval($link['deduct']);
                                                $counter++;
                                                echo '</tr>';
                                            }
                                            echo '<tr class="bg-red-50 border-t-2 border-red-200">';
                                            echo '<td colspan="2" class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">TOTAL DEDUCTIONS</td>';
                                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">₦' . number_format($sumDeduct) . '</td>';
                                            echo '</tr>';
                                            
                                            // Net Pay Section
                                            echo '<tr class="bg-blue-50 border-t-4 border-blue-300">';
                                            echo '<td colspan="2" class="px-6 py-4 whitespace-nowrap text-lg font-bold text-blue-900">NET PAY</td>';
                                            echo '<td class="px-6 py-4 whitespace-nowrap text-lg font-bold text-blue-900 text-right">₦' . number_format(floatval($sumAll) - floatval($sumDeduct)) . '</td>';
                                            echo '</tr>';
                                        }
                                    } catch (PDOException $e) {
                                        echo $e->getMessage();
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
                                <p><strong>Total Earnings:</strong>
                                    ₦<?php echo isset($sumAll) ? number_format($sumAll) : '0'; ?></p>
                                <p><strong>Total Deductions:</strong>
                                    ₦<?php echo isset($sumDeduct) ? number_format($sumDeduct) : '0'; ?></p>
                                <p><strong>Net Pay:</strong>
                                    ₦<?php echo isset($sumAll) && isset($sumDeduct) ? number_format($sumAll - $sumDeduct) : '0'; ?>
                                </p>
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
        //'sales_report.php');

        $("#start_month, #start_day, #start_year, #end_month, #end_day, #end_year").change(function() {
            $("#complex_radio").prop('checked', true);
        });

        $("#report_date_range_simple").change(function() {
            $("#simple_radio").prop('checked', true);
        });
    });

    function receivingsBeforeSubmit(formData, jqForm, options) {
        var submitting = false;
        if (submitting) {
            return false;
        }
        submitting = true;

        $("#ajax-loader").show();
        // $("#finish_sale_button").hide();
    }

    $('#export-pdf-button').click(function() {
        downloadPDF();
    });

    $('#download-excel-button').click(function() {
        downloadExcel();
    });

    function downloadPDF() {
        $('#ajax-loader').show();
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'payrollsummary_export_pdf.php';
        form.style.display = 'none';

        var fields = {
            period: $('#period').val(),
            deduction_text: 'PAYROLL SUMMARY',
            period_text: '<?php echo $month; ?>',
            code: -1 // Placeholder, adjust if needed
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

    function downloadExcel() {
        $('#ajax-loader').show();
        $.ajax({
            type: "POST",
            url: 'payrollsummary_export_excel.php',
            data: {
                period: $('#period').val(),
                deduction_text: 'PAYROLL SUMMARY',
                period_text: '<?php echo $month; ?>',
                code: -1 // Placeholder, adjust if needed
            },
            timeout: 300000,
            success: function(response) {
                $('#ajax-loader').hide();
                try {
                    var downloadLink = document.createElement('a');
                    downloadLink.href =
                        'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' +
                        response;
                    downloadLink.download = 'Payroll_Summary_' + '<?php echo $month; ?>' + '.xlsx';
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
                    alert('Request timed out. Please try again or contact administrator.');
                } else {
                    alert('Error downloading Excel file. Please try again.');
                }
            }
        });
    }
    </script>
</body>

</html>