<?php
require_once('../Connections/paymaster.php');
include_once('../classes/model.php');
require_once('../libs/App.php');
$App = new App();
$App->checkAuthentication();
require_once('../libs/middleware.php');
checkPermission();

$bankId = $_POST['bank'] ?? -1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Net to Bank Report - OOUTH Salary Management</title>
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
                            <i class="fas fa-university"></i> Net to Bank Report
                        </h1>
                        <p class="text-sm text-blue-700/70 mt-1">Generate net pay reports by bank for salary processing.</p>
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
                        <form method="POST" action="net2bank.php" class="space-y-6">
                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <label for="period" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calendar-alt mr-2 text-blue-600"></i>Pay Period
                                    </label>
                                    <select name="period" id="period" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm" required>
                                        <option value="">Select Pay Period</option>
                                        <?php
                                        $period = isset($_POST['period']) ? $_POST['period'] : -1;
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
                                    <label for="bank" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-university mr-2 text-green-600"></i>Bank
                                    </label>
                                    <select name="bank" id="bank" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm" required>
                                        <option value="">Select Bank</option>
                                        <option <?php if ($bankId == 'All') { echo 'selected'; } ?> value="All">All Banks</option>
                                        <?php
                                        try {
                                            $query = $conn->prepare('SELECT tbl_bank.BCODE, tbl_bank.BNAME FROM tbl_bank');
                                            $query->execute();
                                            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                                                $selected = $row['BCODE'] == $bankId ? 'selected' : '';
                                                echo "<option value='{$row['BCODE']}' $selected>{$row['BNAME']}</option>";
                                            }
                                        } catch (PDOException $e) {
                                            echo "<option value=''>Error loading banks</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="flex flex-wrap gap-3">
                                <button name="generate_report" type="submit" id="generate_report" class="bg-blue-700 hover:bg-blue-900 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-search"></i> Generate Report
                                </button>
                                <button type="button" id="download-excel-button" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-file-excel"></i> Download Excel
                                </button>
                                <button type="button" id="download-pdf-button" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-file-pdf"></i> Download PDF
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php
                // Get bank name and period info for display
                $bankName = '';
                $bankCode = '';
                $month = '';
                
                if (isset($_POST['bank']) && $_POST['bank'] != '') {
                    $bankCode = $_POST['bank'];
                    if ($bankCode != 'All') {
                        try {
                            $query = $conn->prepare('SELECT tbl_bank.BNAME FROM tbl_bank WHERE BCODE = ?');
                            $query->execute([$bankCode]);
                            $row = $query->fetch(PDO::FETCH_ASSOC);
                            $bankName = $row ? $row['BNAME'] : '';
                        } catch (PDOException $e) {
                            $bankName = 'Unknown Bank';
                        }
                    } else {
                        $bankName = 'All Banks';
                    }
                }

                if (isset($_POST['period']) && $_POST['period'] != '') {
                    try {
                        $query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear FROM payperiods WHERE periodId = ?');
                        $query->execute([$_POST['period']]);
                        $row = $query->fetch(PDO::FETCH_ASSOC);
                        $month = $row ? $row['description'] . '-' . $row['periodYear'] : '';
                    } catch (PDOException $e) {
                        $month = 'Unknown Period';
                    }
                }
                ?>

                <?php if ($month != '' && $bankCode != '') { ?>
                    <!-- Report Header -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
                        <div class="bg-blue-50 px-6 py-4 border-b">
                            <h2 class="text-lg font-semibold text-blue-800 text-center">
                                OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL
                            </h2>
                            <p class="text-center text-blue-700 font-medium mt-2">
                                Bank Report for <?php echo htmlspecialchars($bankName); ?> for the Month of: <?php echo htmlspecialchars($month); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Report Table -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200" id="sample_1">
                                <thead class="bg-blue-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">S/No</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">Staff No.</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">Acct No.</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">Bank</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-blue-700 uppercase tracking-wider">Net Pay</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php
                                    if (isset($_POST['period']) && isset($_POST['bank'])) {
                                        try {
                                            if ($bankCode != 'All') {
                                                $sql = 'SELECT any_value(tbl_master.staff_id) as staff_id, any_value(Sum(tbl_master.allow)) as allow, any_value(Sum(tbl_master.deduc)) as deduc, any_value((Sum(tbl_master.allow)- Sum(tbl_master.deduc))) AS net, any_value(master_staff.`NAME`) as `NAME`, any_value(tbl_bank.BNAME) as BNAME, ANY_VALUE(master_staff.BCODE) AS BCODE, ANY_VALUE(master_staff.ACCTNO) AS ACCTNO FROM tbl_master INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id INNER JOIN tbl_bank ON tbl_bank.BCODE = master_staff.BCODE WHERE tbl_master.period = ? and master_staff.period = ? and master_staff.BCODE = ? GROUP BY tbl_master.staff_id';
                                                $query = $conn->prepare($sql);
                                                $query->execute([$_POST['period'], $_POST['period'], $bankCode]);
                                            } else {
                                                $sql = 'SELECT any_value(tbl_master.staff_id) as staff_id, any_value(Sum(tbl_master.allow)) as allow, any_value(Sum(tbl_master.deduc)) as deduc, any_value((Sum(tbl_master.allow)- Sum(tbl_master.deduc))) AS net, any_value(master_staff.`NAME`) as `NAME`, any_value(tbl_bank.BNAME) as BNAME, ANY_VALUE(master_staff.BCODE) AS BCODE, ANY_VALUE(master_staff.ACCTNO) AS ACCTNO FROM tbl_master INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id INNER JOIN tbl_bank ON tbl_bank.BCODE = master_staff.BCODE WHERE tbl_master.period = ? and master_staff.period = ? GROUP BY tbl_master.staff_id ORDER BY BCODE';
                                                $query = $conn->prepare($sql);
                                                $query->execute([$_POST['period'], $_POST['period']]);
                                            }

                                            $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                            $numberofstaff = count($res);
                                            $sumTotal = 0;
                                            $i = 1;

                                            if ($numberofstaff > 0) {
                                                foreach ($res as $link) {
                                                    echo '<tr class="hover:bg-gray-50 transition-colors duration-150">';
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . $i . '</td>';
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">' . htmlspecialchars($link['staff_id']) . '</td>';
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($link['NAME']) . '</td>';
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($link['ACCTNO']) . '</td>';
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($link['BNAME']) . '</td>';
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">₦' . number_format($link['net']) . '</td>';
                                                    $sumTotal += floatval($link['net']);
                                                    $i++;
                                                    echo '</tr>';
                                                }

                                                // Total row
                                                echo '<tr class="bg-blue-50 border-t-2 border-blue-200">';
                                                echo '<td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">TOTAL</td>';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">₦' . number_format($sumTotal) . '</td>';
                                                echo '</tr>';
                                            } else {
                                                echo '<tr>';
                                                echo '<td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No net pay data found for the selected criteria.</td>';
                                                echo '</tr>';
                                            }
                                        } catch (PDOException $e) {
                                            echo '<tr>';
                                            echo '<td colspan="6" class="px-6 py-4 text-center text-sm text-red-500">Error: ' . htmlspecialchars($e->getMessage()) . '</td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr>';
                                        echo '<td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">Please select a pay period and bank to generate the report.</td>';
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
                                        $Today = date('y:m:d', time());
                                        $new = date('l, F d, Y', strtotime($Today));
                                        echo $new;
                                    ?></p>
                                </div>
                                <div class="text-sm text-gray-600">
                                    <p><strong>Bank:</strong> <?php echo htmlspecialchars($bankName); ?></p>
                                    <p><strong>Period:</strong> <?php echo htmlspecialchars($month); ?></p>
                                    <?php if (isset($sumTotal) && $sumTotal > 0): ?>
                                        <p><strong>Total Net Pay:</strong> ₦<?php echo number_format($sumTotal); ?></p>
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
        // Form validation
        $('#generate_report').click(function(e) {
            if (!$('#period').val() || !$('#bank').val()) {
                e.preventDefault();
                alert('Please select both Pay Period and Bank before generating the report.');
            }
        });

        // Export functionality
        $('#download-excel-button').click(function() {
            if (!$('#period').val() || !$('#bank').val()) {
                alert('Please select both Pay Period and Bank before downloading Excel.');
                return;
            }
            downloadExcel();
        });

        $('#download-pdf-button').click(function() {
            if (!$('#period').val() || !$('#bank').val()) {
                alert('Please select both Pay Period and Bank before downloading PDF.');
                return;
            }
            downloadPDF();
        });

        function downloadExcel() {
            $('#ajax-loader').show();
            $.ajax({
                type: "POST",
                url: 'net2bank_export_excel.php',
                data: {
                    period: $('#period').val(),
                    bank: $('#bank').val(),
                    period_text: '<?php echo addslashes($month); ?>'
                },
                timeout: 300000,
                success: function(response) {
                    $('#ajax-loader').hide();
                    try {
                        var downloadLink = document.createElement('a');
                        downloadLink.href = 'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' + response;
                        downloadLink.download = 'Netpay_to_Bank_Report_' + '<?php echo addslashes($month); ?>' + '.xlsx';
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

        function downloadPDF() {
            $('#ajax-loader').show();
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'net2bank_export_pdf.php';
            form.style.display = 'none';

            var fields = {
                period: $('#period').val(),
                bank: $('#bank').val(),
                period_text: '<?php echo addslashes($month); ?>'
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
</body>
</html>