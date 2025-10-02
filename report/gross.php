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
    <title>Gross Report - OOUTH Salary Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                            <i class="fas fa-chart-line"></i> Gross Report
                        </h1>
                        <p class="text-sm text-blue-700/70 mt-1">Generate and view gross salary reports for employees.</p>
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
                        <?php
                        global $conn;
                        $bankName = '';
                        if (!isset($_POST['bank'])) {
                            $bank = -1;
                        } else {
                            $bank = $_POST['bank'];
                        }
                        try {
                            $query = $conn->prepare('SELECT tbl_bank.BNAME FROM tbl_bank WHERE BCODE = ?');
                            $res = $query->execute(array($bank));
                            $out = $query->fetchAll(PDO::FETCH_ASSOC);
                            while ($row = array_shift($out)) {
                                $bankName = $row['BNAME'];
                            }
                        } catch (PDOException $e) {
                            $e->getMessage();
                        }
                        ?>
                        
                        <form method="POST" action="gross.php" class="space-y-6">
                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <label for="period" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calendar-alt mr-2 text-blue-600"></i>Pay Period
                                    </label>
                                    <select name="period" id="period" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm" required>
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
                                <button name="generate_report" type="submit" id="generate_report" class="bg-blue-700 hover:bg-blue-900 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-search"></i> Generate Report
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

                <?php if (isset($_POST['generate_report']) && $period != -1) { ?>
                    <!-- Report Header -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
                        <div class="bg-blue-50 px-6 py-4 border-b">
                            <h2 class="text-lg font-semibold text-blue-800 text-center">
                                OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL
                            </h2>
                            <p class="text-center text-blue-700 font-medium mt-2">
                                Gross Report for the Month of: <?php
                                $month = '';
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
                <?php if (isset($_POST['generate_report']) && $period != -1) { ?>
                    <!-- Report Table -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200" id="sample_1">
                                <thead class="bg-blue-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">S/No</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">Staff No.</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">Department</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">Grade</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">Step</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">Account No.</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">Bank</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-blue-700 uppercase tracking-wider">Gross Pay</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            try {
                                $sql = 'SELECT
                                            any_value(tbl_master.staff_id) AS staff_id,
                                            any_value(Sum(tbl_master.allow)) AS allow,
                                            any_value(Sum(tbl_master.deduc)) AS deduc,
                                            any_value((Sum(tbl_master.allow) - Sum(tbl_master.deduc))) AS net,
                                            any_value(master_staff.`NAME`) AS `NAME`,
                                            any_value(tbl_bank.BNAME) AS BNAME,
                                            ANY_VALUE(master_staff.BCODE) AS BCODE,
                                            ANY_VALUE(master_staff.ACCTNO) AS ACCTNO,
                                            any_value(master_staff.GRADE) AS GRADE,
                                            any_value(master_staff.STEP) AS STEP,
                                            any_value(tbl_dept.dept) AS dept 
                                        FROM
                                            tbl_master
                                            INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id
                                            INNER JOIN tbl_bank ON tbl_bank.BCODE = master_staff.BCODE
                                            INNER JOIN tbl_dept ON master_staff.DEPTCD = tbl_dept.dept_id 
                                        WHERE tbl_master.period = ? AND master_staff.period = ? GROUP BY tbl_master.staff_id';
                                $query = $conn->prepare($sql);
                                $fin = $query->execute(array($period, $period));
                                $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                $numberofstaff = count($res);
                                $counter = 1;
                                $sumAll = 0;
                                $sumDeduct = 0;
                                $sumTotal = 0;
                                $i = 1;
                                foreach ($res as $row => $link) {
                                    echo '<tr class="hover:bg-gray-50 transition-colors duration-150">';
                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . $i . '</td>';
                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">' . $link['staff_id'] . '</td>';
                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . $link['NAME'] . '</td>';
                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . $link['dept'] . '</td>';
                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . $link['GRADE'] . '</td>';
                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . $link['STEP'] . '</td>';
                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . $link['ACCTNO'] . '</td>';
                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . $link['BNAME'] . '</td>';
                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">₦' . number_format($link['allow']) . '</td>';
                                    $sumTotal = $sumTotal + floatval($link['allow']);
                                    $counter++;
                                    echo '</tr>';
                                    ++$i;
                                }
                                echo '<tr class="bg-blue-50 border-t-2 border-blue-200">';
                                echo '<td colspan="8" class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">TOTAL</td>';
                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">₦' . number_format($sumTotal) . '</td>';
                                echo '</tr>';
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
                                    <p><strong>Total Employees:</strong> <?php echo $numberofstaff; ?></p>
                                    <p><strong>Total Gross Pay:</strong> ₦<?php echo number_format($sumTotal); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </main>
    </div>

    <script type="text/javascript">
        $(document).ready(function() {
            // Handle PDF export
            $('#export-pdf-button').click(function() {
                downloadPDF();
            });

            // Handle Excel export
            $('#download-excel-button').click(function() {
                downloadExcel();
            });
        });

        function downloadPDF() {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'gross_export_pdf.php';
            form.style.display = 'none';

            var fields = {
                period: $('#period').val(),
                period_text: '<?php echo isset($month) ? $month : ''; ?>'
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
        }

        function downloadExcel() {
            $.ajax({
                type: "POST",
                url: 'gross_export_excel.php',
                data: {
                    period: $('#period').val(),
                    period_text: '<?php echo isset($month) ? $month : ''; ?>'
                },
                timeout: 300000,
                success: function(response) {
                    try {
                        var downloadLink = document.createElement('a');
                        downloadLink.href = 'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' + response;
                        downloadLink.download = 'Gross_Report_' + '<?php echo isset($month) ? $month : ''; ?>' + '.xlsx';
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                    } catch (e) {
                        console.error('Error processing Excel response:', e);
                        alert('Error generating Excel file. Please try again.');
                    }
                },
                error: function(xhr, status, error) {
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