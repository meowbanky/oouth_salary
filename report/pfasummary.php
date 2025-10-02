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
    <title>PFA Summary Report - OOUTH Salary Management</title>
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
                            <i class="fas fa-piggy-bank"></i> PFA Summary Report
                        </h1>
                        <p class="text-sm text-blue-700/70 mt-1">Generate pension fund administrator summary reports with contribution totals.</p>
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
                        <form method="POST" action="pfasummary.php" class="space-y-6">
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
                            </div>
                            
                            <div class="flex flex-wrap gap-3">
                                <button name="generate_report" type="submit" id="generate_report" class="bg-blue-700 hover:bg-blue-900 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-search"></i> Generate Report
                                </button>
                                <button type="button" onclick="exportAll('xls','PFA <?php echo $month; ?>')" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-file-excel"></i> Export XLS
                                </button>
                                <button type="button" onclick="exportAll('csv','PFA <?php echo $month; ?>')" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-file-csv"></i> Export CSV
                                </button>
                                <button type="button" onclick="window.print()" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-print"></i> Print
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php 
                $month = '';
                if (isset($_POST['period']) && $_POST['period'] != '') {
                    try {
                        $query = $conn->prepare('SELECT description, periodYear FROM payperiods WHERE periodId = ?');
                        $query->execute([$_POST['period']]);
                        $row = $query->fetch(PDO::FETCH_ASSOC);
                        $month = $row ? $row['description'] . '-' . $row['periodYear'] : '';
                    } catch (PDOException $e) {
                        $month = 'Error loading period';
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
                                PFA Summary for the Month of: <?php echo htmlspecialchars($month); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Report Table -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200" id="sample_1">
                                <thead class="bg-blue-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">S/N</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">PFA Name</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-blue-700 uppercase tracking-wider">No. of Employee</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-blue-700 uppercase tracking-wider">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php
                                    if (isset($_POST['period']) && $_POST['period'] != '') {
                                        try {
                                            $query = $conn->prepare('SELECT COUNT(staff_id) AS no, ANY_VALUE(tbl_pfa.PFANAME) AS PFANAME, master_staff.PFACODE FROM master_staff INNER JOIN tbl_pfa ON master_staff.PFACODE = tbl_pfa.PFACODE WHERE master_staff.period = ? GROUP BY master_staff.PFACODE');
                                            $query->execute([$_POST['period']]);
                                            $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            $numberofstaff = count($res);
                                            $counter = 1;
                                            $sumTotal = 0;
                                            $countStaff = 0;

                                            if ($numberofstaff > 0) {
                                                foreach ($res as $link) {
                                                    // Get pension contribution amount for this PFA
                                                    $query2 = $conn->prepare('SELECT SUM(tbl_master.deduc) as amount FROM master_staff INNER JOIN tbl_pfa ON master_staff.PFACODE = tbl_pfa.PFACODE INNER JOIN tbl_master ON tbl_master.staff_id = master_staff.staff_id WHERE allow_id = 50 AND tbl_master.period = ? AND master_staff.period = ? AND tbl_pfa.PFACODE = ?');
                                                    $query2->execute([$_POST['period'], $_POST['period'], $link['PFACODE']]);
                                                    $ftres = $query2->fetchAll(PDO::FETCH_ASSOC);
                                                    
                                                    $amount = 0;
                                                    foreach ($ftres as $row2) {
                                                        if (isset($row2['amount'])) {
                                                            $amount = $row2['amount'];
                                                        }
                                                    }

                                                    echo '<tr class="hover:bg-gray-50 transition-colors duration-150">';
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . $counter . '</td>';
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">' . htmlspecialchars($link['PFANAME']) . '</td>';
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">' . number_format($link['no']) . '</td>';
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">₦' . number_format($amount) . '</td>';
                                                    echo '</tr>';

                                                    $sumTotal += floatval($amount);
                                                    $countStaff += intval($link['no']);
                                                    $counter++;
                                                }

                                                // Total row
                                                echo '<tr class="bg-blue-50 border-t-2 border-blue-200">';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">TOTAL</td>';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900"></td>';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">' . number_format($countStaff) . '</td>';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">₦' . number_format($sumTotal) . '</td>';
                                                echo '</tr>';
                                            } else {
                                                echo '<tr>';
                                                echo '<td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No PFA data found for the selected period.</td>';
                                                echo '</tr>';
                                            }
                                        } catch (PDOException $e) {
                                            echo '<tr>';
                                            echo '<td colspan="4" class="px-6 py-4 text-center text-sm text-red-500">Error: ' . htmlspecialchars($e->getMessage()) . '</td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr>';
                                        echo '<td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">Please select a pay period to generate the PFA summary report.</td>';
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
                                    <p><strong>Period:</strong> <?php echo htmlspecialchars($month); ?></p>
                                    <?php if (isset($countStaff) && $countStaff > 0): ?>
                                        <p><strong>Total Employees:</strong> <?php echo number_format($countStaff); ?></p>
                                        <p><strong>Total Contributions:</strong> ₦<?php echo number_format($sumTotal); ?></p>
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
            if (!$('#period').val()) {
                e.preventDefault();
                alert('Please select a Pay Period before generating the report.');
            }
        });
    });
    </script>
</body>
</html>