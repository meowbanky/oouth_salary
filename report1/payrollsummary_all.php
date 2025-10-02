<?php
session_start();

include_once('../classes/model.php');
require_once('../Connections/paymaster.php');
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
    header("location: ../index.php");
    exit();
}
if (!function_exists("GetSQLValueString")) {
    function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "")
    {
        global $salary;

        $theValue = function_exists("mysql_real_escape_string") ? mysqli_real_escape_string($salary, $theValue) : mysqli_escape_string($salary, $theValue);

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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Summary Report - Salary Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

    <style>
    /* Ensure footer is always visible */
    body {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    .flex-1 {
        flex: 1;
    }

    /* DataTable styling improvements */
    .dataTables_wrapper {
        margin-bottom: 1rem;
    }

    /* Ensure proper spacing */
    .container {
        flex: 1;
    }

    /* Print styles */
    @media print {
        .hidden-print {
            display: none !important;
        }

        .bg-gradient-to-r {
            background: #2563eb !important;
        }

        .text-white {
            color: #000 !important;
        }

        .shadow-lg {
            box-shadow: none !important;
        }
    }
    </style>

</head>

<body class="bg-gray-100 font-sans">
    <?php include '../header.php'; ?>
    <div class="flex min-h-screen">
        <?php include '../sidebar.php'; ?>
        <div class="flex-1 p-6">
            <div class="container mx-auto">
                <nav class="mb-6">
                    <a href="../home.php" class="text-blue-600 hover:underline"><i class="fas fa-home"></i>
                        Dashboard</a>
                    <span class="mx-2">/</span>
                    <a href="index.php" class="text-blue-600 hover:underline">Reports</a>
                    <span class="mx-2">/</span>
                    <span>Payroll Summary Report</span>
                </nav>

                <?php if (isset($_SESSION['msg'])): ?>
                <div
                    class="bg-<?php echo $_SESSION['alertcolor'] ?? 'blue'; ?>-100 text-<?php echo $_SESSION['alertcolor'] ?? 'blue'; ?>-800 p-4 rounded-md mb-6 flex justify-between items-center">
                    <span><?php echo htmlspecialchars($_SESSION['msg']); ?></span>
                    <button onclick="this.parentElement.remove()"
                        class="text-<?php echo $_SESSION['alertcolor'] ?? 'blue'; ?>-600 hover:text-<?php echo $_SESSION['alertcolor'] ?? 'blue'; ?>-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php unset($_SESSION['msg'], $_SESSION['alertcolor']); ?>
                <?php endif; ?>

                <h1 class="text-3xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-chart-bar mr-2"></i> Payroll Summary Report
                    <small class="text-base text-gray-600 ml-2">Generate comprehensive payroll summaries and export
                        data</small>
                </h1>

                <!-- Report Header Card -->
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-6 py-8 text-white rounded-lg">
                        <div class="flex items-center justify-center space-x-4">
                            <img src="img/oouth_logo.gif" width="60" height="60" class="rounded-lg bg-white p-2"
                                alt="OOUTH Logo">
                            <div class="text-center">
                                <h2 class="text-2xl font-bold uppercase tracking-wide">OLABISI ONABANJO UNIVERSITY
                                    TEACHING HOSPITAL</h2>
                                <p class="text-lg mt-2 opacity-90">PAYROLL SUMMARY FOR THE MONTH OF</p>
                                <p class="text-xl font-semibold mt-1">
                                    <?php $month = '';
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
                    </div>
                </div>

                <!-- Form and Controls Card -->
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <div class="mb-4 flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-800">Report Controls</h2>
                        <div class="flex space-x-3">
                            <button id="reload-button"
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                <i class="fas fa-sync-alt"></i> Reload
                            </button>
                            <button id="export-pdf-button"
                                class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                                <i class="fas fa-file-pdf"></i> Export PDF
                            </button>
                            <button id="download-excel-button"
                                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                                <i class="fas fa-file-excel"></i> Export Excel
                            </button>
                        </div>
                    </div>

                    <form class="form-horizontal" method="POST" action="payrollsummary_all.php">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                            <div>
                                <label for="period" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fa fa-calendar mr-2"></i>Pay Period
                                </label>
                                <div class="relative">
                                    <select name="period" id="period"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
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
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <i class="fa fa-chevron-down text-gray-400"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="flex space-x-3">
                                <button name="generate_report" type="submit" id="generate_report"
                                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg transition-colors duration-200 flex items-center justify-center">
                                    <i class="fa fa-search mr-2"></i>
                                    Generate Report
                                </button>
                            </div>

                            <div class="flex items-center">
                                <span id="ajax-loader" class="hidden">
                                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                                </span>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Results Table Card -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="mb-4">
                        <h2 class="text-xl font-semibold text-gray-800">Payroll Summary Results</h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table id="sample_1" class="min-w-full bg-white border border-gray-200">
                            <thead>
                                <tr class="bg-gray-800 text-white">
                                    <th class="py-2 px-4 text-left">Code</th>
                                    <th class="py-2 px-4 text-left">Description</th>
                                    <th class="py-2 px-4 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="bg-blue-50">
                                    <td colspan="3"
                                        class="py-3 px-4 text-lg font-semibold text-blue-800 uppercase tracking-wide">
                                        Earnings</td>
                                </tr>
                                <?php
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
                                            echo '<tr class="hover:bg-gray-50 transition-colors">';
                                            echo '<td class="py-2 px-4 text-sm font-medium text-gray-900">' . $link['allow_id'] . '</td>';
                                            echo '<td class="py-2 px-4 text-sm text-gray-700">' . $link['ed'] . '</td>';
                                            echo '<td class="py-2 px-4 text-sm text-gray-900 text-right font-medium">' . number_format($link['allow']) . '</td>';
                                            echo '</tr>';
                                            $sumAll = $sumAll + floatval($link['allow']);
                                            $counter++;
                                        }
                                        echo '<tr class="bg-green-50 border-t-2 border-green-200">';
                                        echo '<td colspan="2" class="py-3 px-4 text-lg font-bold text-green-800">TOTAL EARNINGS</td>';
                                        echo '<td class="py-3 px-4 text-lg font-bold text-green-800 text-right">' . number_format($sumAll) . '</td>';
                                        echo '</tr>';
                                    }
                                } catch (PDOException $e) {
                                    echo '<tr><td colspan="3" class="py-2 px-4 text-red-600">' . $e->getMessage() . '</td></tr>';
                                }

                                echo '<tr class="h-4"><td colspan="3"></td></tr>';

                                echo '<tr class="bg-red-50">';
                                echo '<td colspan="3" class="py-3 px-4 text-lg font-semibold text-red-800 uppercase tracking-wide">DEDUCTIONS</td>';
                                echo '</tr>';

                                //Deduction summary
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
                                            echo '<tr class="hover:bg-gray-50 transition-colors">';
                                            echo '<td class="py-2 px-4 text-sm font-medium text-gray-900">' . $link['allow_id'] . '</td>';
                                            echo '<td class="py-2 px-4 text-sm text-gray-700">' . $link['ed'] . '</td>';
                                            echo '<td class="py-2 px-4 text-sm text-gray-900 text-right font-medium">' . number_format($link['deduct']) . '</td>';
                                            echo '</tr>';
                                            $sumDeduct = $sumDeduct + floatval($link['deduct']);
                                            $counter++;
                                        }
                                        echo '<tr class="bg-red-50 border-t-2 border-red-200">';
                                        echo '<td colspan="2" class="py-3 px-4 text-lg font-bold text-red-800">TOTAL DEDUCTIONS</td>';
                                        echo '<td class="py-3 px-4 text-lg font-bold text-red-800 text-right">' . number_format($sumDeduct) . '</td>';
                                        echo '</tr>';
                                    }
                                } catch (PDOException $e) {
                                    echo '<tr><td colspan="3" class="py-2 px-4 text-red-600">' . $e->getMessage() . '</td></tr>';
                                }
                                ?>
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-900 text-white">
                                    <td colspan="2" class="py-4 px-4 text-xl font-bold">NET PAY</td>
                                    <td class="py-4 px-4 text-xl font-bold text-right">
                                        <?php echo number_format(floatval($sumAll) - floatval($sumDeduct)); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    $(function() {
        // Enhanced form interactions
        $("#start_month, #start_day, #start_year, #end_month, #end_day, #end_year").change(function() {
            $("#complex_radio").prop('checked', true);
        });

        $("#report_date_range_simple").change(function() {
            $("#simple_radio").prop('checked', true);
        });

        // Add loading states
        $('#generate_report').click(function() {
            $(this).prop('disabled', true);
            $(this).html('<i class="fa fa-spinner fa-spin mr-2"></i>Generating...');
            $('#ajax-loader').removeClass('hidden');
        });

        // Reload button
        $('#reload-button').click(function() {
            location.reload();
        });

        // Enhanced export buttons
        $('#export-pdf-button').click(function() {
            if (!$('#period').val()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Period Selected',
                    text: 'Please select a pay period before exporting PDF.',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }
            downloadPDF();
        });

        $('#download-excel-button').click(function() {
            if (!$('#period').val()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Period Selected',
                    text: 'Please select a pay period before downloading Excel.',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }
            downloadExcel();
        });
    });

    function receivingsBeforeSubmit(formData, jqForm, options) {
        var submitting = false;
        if (submitting) {
            return false;
        }
        submitting = true;

        $("#ajax-loader").removeClass('hidden');
    }

    function downloadPDF() {
        $('#ajax-loader').removeClass('hidden');
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'payrollsummary_export_pdf.php';
        form.style.display = 'none';

        var fields = {
            period: $('#period').val(),
            deduction_text: 'PAYROLL SUMMARY',
            period_text: '<?php echo $month; ?>',
            code: -1
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
            $('#ajax-loader').addClass('hidden');
        }, 2000);
    }

    function downloadExcel() {
        $('#ajax-loader').removeClass('hidden');
        $.ajax({
            type: "POST",
            url: 'payrollsummary_export_excel.php',
            data: {
                period: $('#period').val(),
                deduction_text: 'PAYROLL SUMMARY',
                period_text: '<?php echo $month; ?>',
                code: -1
            },
            timeout: 300000,
            success: function(response) {
                $('#ajax-loader').addClass('hidden');
                try {
                    var downloadLink = document.createElement('a');
                    downloadLink.href =
                        'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' +
                        response;
                    downloadLink.download = 'Payroll_Summary_' + '<?php echo $month; ?>' + '.xlsx';
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);

                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Excel Download Complete',
                        text: 'The payroll summary has been exported successfully.',
                        confirmButtonColor: '#3085d6'
                    });
                } catch (e) {
                    console.error('Error processing Excel response:', e);
                    Swal.fire({
                        icon: 'error',
                        title: 'Export Error',
                        text: 'Error generating Excel file. Please try again.',
                        confirmButtonColor: '#3085d6'
                    });
                }
            },
            error: function(xhr, status, error) {
                $('#ajax-loader').addClass('hidden');
                console.error('AJAX Error:', status, error);
                let errorMessage = 'Error downloading Excel file. Please try again.';
                if (status === 'timeout') {
                    errorMessage = 'Request timed out. Please try again or contact administrator.';
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Download Failed',
                    text: errorMessage,
                    confirmButtonColor: '#3085d6'
                });
            }
        });
    }
    </script>

    <?php include '../footer.php'; ?>
</body>

</html>