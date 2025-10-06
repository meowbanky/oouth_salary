<?php
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
    <title>Bank Summary Report - OOUTH Salary Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../css/dark-mode.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script><script src="../js/theme-manager.js"></script>
</head>

<body class="bg-gray-100 min-h-screen">
    <?php include('../header.php'); ?>
    <div class="flex min-h-screen">
        <?php include('report_sidebar_modern.php'); ?>                <main class="flex-1 px-2 md:px-8 py-4 flex flex-col">
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
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Bank Summary</span>
                            </div>
                        </li>
                    </ol>
                </nav>

            <div class="w-full max-w-7xl mx-auto flex-1 flex flex-col">
                <!-- Header Section -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-blue-800 flex items-center gap-2">
                            <i class="fas fa-university"></i> Bank Summary Report
                        </h1>
                        <p class="text-sm text-blue-700/70 mt-1">Generate comprehensive bank-wise salary summary
                            reports.</p>
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
                        <form method="POST" action="banksummary.php" class="space-y-6">
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
                                        if (!isset($_POST['period'])) {
                                            $period = -1;
                                        } else {
                                            $period = $_POST['period'];
                                        }
                                        try {
                                            $query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear, payperiods.periodId FROM payperiods WHERE payrollRun = ? order by periodId desc');
                                            $res = $query->execute(array('1'));
                                            $out = $query->fetchAll(PDO::FETCH_ASSOC);
                                            while ($row = array_shift($out)) {
                                                echo '<option value="' . $row['periodId'] . '"';
                                                if ($row['periodId'] == $period) {
                                                    echo 'selected = "selected"';
                                                }
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

                <?php 
                $month = '';
                if (isset($_POST['period']) && $_POST['period'] != '') {
                    try {
                        $query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear, payperiods.periodId FROM payperiods WHERE periodId = ?');
                        $res = $query->execute(array($period));
                        $out = $query->fetchAll(PDO::FETCH_ASSOC);
                        while ($row = array_shift($out)) {
                            $month = $row['description'] . '-' . $row['periodYear'];
                        }
                    } catch (PDOException $e) {
                        $e->getMessage();
                    }
                }
                ?>

                <?php if ($month != '') { ?>
                <!-- Report Header -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
                    <div class="bg-blue-50 px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold text-blue-800 text-center">
                            OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL
                        </h2>
                        <p class="text-center text-blue-700 font-medium mt-2">
                            Bank Summary for the Month of: <?php echo htmlspecialchars($month); ?>
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
                                        Bank Name</th>
                                    <th
                                        class="px-6 py-3 text-center text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        No. of Employees</th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        Total Net Pay</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php
                                    if (!isset($_POST['period'])) {
                                        $period = -1;
                                    } else {
                                        $period = $_POST['period'];
                                    }
                                    try {
                                        $query = $conn->prepare('SELECT any_value(BNAME) as BNAME, any_value(Sum(tbl_master.allow) - Sum(tbl_master.deduc)) AS net, any_value(tbl_bank.BNAME) as BNAME, any_value(tbl_bank.BCODE) as BCODE FROM tbl_master INNER JOIN employee ON tbl_master.staff_id = employee.staff_id INNER JOIN tbl_bank ON employee.BCODE = tbl_bank.BCODE WHERE period = ? GROUP BY employee.BCODE order by any_value(tbl_bank.BNAME) ASC');
                                        $fin = $query->execute(array($period));
                                        $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                        $numberofstaff = count($res);
                                        $counter = 1;
                                        $sumAll = 0;
                                        $sumDeduct = 0;
                                        $sumTotal = 0;
                                        $countStaff = 0;
                                        
                                        if ($numberofstaff > 0) {
                                            foreach ($res as $row => $link) {
                                                $query2 = $conn->prepare('SELECT Count(employee.staff_id) as "numb" FROM employee WHERE BCODE = ? AND STATUSCD = ? GROUP BY BCODE');
                                                $fin2 = $query2->execute(array($link['BCODE'], 'A'));
                                                $res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
                                                $numb = 0;
                                                foreach ($res2 as $row2 => $link2) {
                                                    $numb = $link2['numb'];
                                                    $countStaff = $countStaff + $numb;
                                                }
                                                
                                                echo '<tr class="hover:bg-gray-50 transition-colors duration-150">';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">' . $link['BNAME'] . '</td>';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center">' . $numb . '</td>';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">₦' . number_format($link['net']) . '</td>';
                                                echo '</tr>';
                                                
                                                $sumTotal = $sumTotal + floatval($link['net']);
                                                $counter++;
                                            }
                                            
                                            // Total row
                                            echo '<tr class="bg-blue-50 border-t-2 border-blue-200">';
                                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">TOTAL</td>';
                                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-center">' . number_format($countStaff) . '</td>';
                                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">₦' . number_format($sumTotal) . '</td>';
                                            echo '</tr>';
                                        } else {
                                            echo '<tr>';
                                            echo '<td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">No data available for the selected period.</td>';
                                            echo '</tr>';
                                        }
                                    } catch (PDOException $e) {
                                        echo '<tr>';
                                        echo '<td colspan="3" class="px-6 py-4 text-center text-sm text-red-500">Error: ' . $e->getMessage() . '</td>';
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
                                <p><strong>Total Banks:</strong> <?php echo $numberofstaff; ?></p>
                                <p><strong>Total Net Pay:</strong>
                                    ₦<?php echo isset($sumTotal) ? number_format($sumTotal) : '0'; ?></p>
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
        form.action = 'banksummary_export_pdf.php';
        form.style.display = 'none';

        var fields = {
            period: $('#period').val(),
            period_text: '<?php echo $month; ?>'
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
            url: 'banksummary_export_excel.php',
            data: {
                period: $('#period').val(),
                period_text: '<?php echo $month; ?>'
            },
            timeout: 300000,
            success: function(response) {
                $('#ajax-loader').hide();
                try {
                    var downloadLink = document.createElement('a');
                    downloadLink.href =
                        'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' +
                        response;
                    downloadLink.download = 'Bank_Summary_' + '<?php echo $month; ?>' + '.xlsx';
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