<?php
ini_set('max_execution_time', '0');
require_once('../Connections/paymaster.php');
include_once('../classes/model.php');
require_once('../libs/App.php');
$App = new App();
$App->checkAuthentication();
require_once('../libs/middleware.php');
checkPermission();

// Initialize variables
$monthFrom = '';
$monthTo = '';

if (!function_exists("GetSQLValueString")) {
    function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "")
    {
        global $con;
        $theValue = function_exists("mysql_real_escape_string") ? mysqli_real_escape_string($con, $theValue) : mysqli_escape_string($con, $theValue);
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
    <title>Payroll Variance Report - OOUTH Salary Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                            <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Variance Report</span>
                        </div>
                    </li>
                </ol>
            </nav>



            <div class="w-full max-w-7xl mx-auto flex-1 flex flex-col">
                <!-- Header Section -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-blue-800 flex items-center gap-2">
                            <i class="fas fa-chart-line"></i> Payroll Variance Report
                        </h1>
                        <p class="text-sm text-blue-700/70 mt-1">Compare payroll data between different periods to
                            identify variances.</p>
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
                        <form method="GET" action="variance.php" class="space-y-6">
                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <label for="periodFrom" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calendar-check mr-2 text-green-600"></i>Current Month
                                    </label>
                                    <select name="periodFrom" id="periodFrom"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm"
                                        required>
                                        <option value="">Select Pay Period</option>
                                        <?php
                                        $periodFrom = isset($_GET['periodFrom']) ? $_GET['periodFrom'] : -1;
                                        try {
                                            $query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear, payperiods.periodId FROM payperiods WHERE payrollRun = ? order by periodId desc');
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

                                <div>
                                    <label for="periodTo" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calendar-minus mr-2 text-red-600"></i>Previous Month
                                    </label>
                                    <select name="periodTo" id="periodTo"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm"
                                        required>
                                        <option value="">Select Pay Period</option>
                                        <?php
                                        $periodTo = isset($_GET['periodTo']) ? $_GET['periodTo'] : -1;
                                        try {
                                            $query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear, payperiods.periodId FROM payperiods WHERE payrollRun = ? order by periodId desc');
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
                                <button type="button" onclick="exportAll('excel')"
                                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-file-excel"></i> Export XLS
                                </button>
                                <button type="button" onclick="exportAll('pdf')"
                                    class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-file-pdf"></i> Export PDF
                                </button>
                                <button type="button" onclick="window.print()"
                                    class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-print"></i> Print
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php
                // Get period descriptions for display
                $monthTo = '';
                $monthFrom = '';
                $periodTo = isset($_GET['periodTo']) ? $_GET['periodTo'] : -1;
                $periodFrom = isset($_GET['periodFrom']) ? $_GET['periodFrom'] : -1;
                
                if ($periodFrom != -1) {
                    try {
                        $query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear FROM payperiods WHERE periodId = ?');
                        $query->execute([$periodFrom]);
                        $row = $query->fetch(PDO::FETCH_ASSOC);
                        $monthFrom = $row ? $row['description'] . '-' . $row['periodYear'] : '';
                    } catch (PDOException $e) {
                        $monthFrom = 'Error loading period';
                    }
                }

                if ($periodTo != -1) {
                    try {
                        $query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear FROM payperiods WHERE periodId = ?');
                        $query->execute([$periodTo]);
                        $row = $query->fetch(PDO::FETCH_ASSOC);
                        $monthTo = $row ? $row['description'] . '-' . $row['periodYear'] : '';
                    } catch (PDOException $e) {
                        $monthTo = 'Error loading period';
                    }
                }
                ?>

                <?php if ($monthFrom != '' && $monthTo != '') { ?>
                <!-- Report Header -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
                    <div class="bg-blue-50 px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold text-blue-800 text-center">
                            OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL
                        </h2>
                        <p class="text-center text-blue-700 font-medium mt-2">
                            Payroll Variance Between the Month of <?php echo htmlspecialchars($monthFrom); ?> AND
                            <?php echo htmlspecialchars($monthTo); ?>
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
                                        S/N</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        Staff No</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        Name</th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        <?php echo htmlspecialchars($monthFrom); ?></th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        <?php echo htmlspecialchars($monthTo); ?></th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        Variance</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php
                                    if ($periodFrom != -1 && $periodTo != -1) {
                                        try {
                                            $query = $conn->prepare('SELECT staff_id,ANY_VALUE(master_staff.`NAME`) AS `NAME` FROM master_staff WHERE period = ? UNION SELECT staff_id,ANY_VALUE(master_staff.`NAME`) AS `NAME` FROM master_staff WHERE period = ? ORDER BY staff_id;');
                                            $query->execute([$periodTo, $periodFrom]);
                                            $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            $numberofstaff = count($res);
                                            $i = 1;
                                            $sumCurrent = 0;
                                            $sumPrevious = 0;

                                            if ($numberofstaff > 0) {
                                                foreach ($res as $link) {
                                                    echo '<tr class="hover:bg-gray-50 transition-colors duration-150">';
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . $i . '</td>';
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">' . htmlspecialchars($link['staff_id']) . '</td>';
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($link['NAME']) . '</td>';

                                                    // Get variance data using the variance() function
                                                    $currentAmount = variance($periodFrom, $link['staff_id']);
                                                    $previousAmount = variance($periodTo, $link['staff_id']);
                                                    $varianceAmount = $currentAmount - $previousAmount;

                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">₦' . number_format($currentAmount) . '</td>';
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">₦' . number_format($previousAmount) . '</td>';
                                                    
                                                    // Color code the variance
                                                    $varianceClass = $varianceAmount >= 0 ? 'text-green-600' : 'text-red-600';
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right ' . $varianceClass . '">₦' . number_format($varianceAmount) . '</td>';
                                                    echo '</tr>';

                                                    $sumCurrent += $currentAmount;
                                                    $sumPrevious += $previousAmount;
                                                    $i++;
                                                }

                                                // Total row
                                                $totalVariance = $sumCurrent - $sumPrevious;
                                                $totalVarianceClass = $totalVariance >= 0 ? 'text-green-600' : 'text-red-600';
                                                
                                                echo '<tr class="bg-blue-50 border-t-2 border-blue-200">';
                                                echo '<td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">TOTAL</td>';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">₦' . number_format($sumCurrent) . '</td>';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">₦' . number_format($sumPrevious) . '</td>';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-right ' . $totalVarianceClass . '">₦' . number_format($totalVariance) . '</td>';
                                                echo '</tr>';
                                            } else {
                                                echo '<tr>';
                                                echo '<td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No variance data found for the selected periods.</td>';
                                                echo '</tr>';
                                            }
                                        } catch (PDOException $e) {
                                            echo '<tr>';
                                            echo '<td colspan="6" class="px-6 py-4 text-center text-sm text-red-500">Error: ' . htmlspecialchars($e->getMessage()) . '</td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr>';
                                        echo '<td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">Please select both current and previous months to generate the variance report.</td>';
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
                                <p><strong>Current Month:</strong> <?php echo htmlspecialchars($monthFrom); ?></p>
                                <p><strong>Previous Month:</strong> <?php echo htmlspecialchars($monthTo); ?></p>
                                <?php if (isset($totalVariance)): ?>
                                <p><strong>Overall Variance:</strong> <span
                                        class="<?php echo $totalVariance >= 0 ? 'text-green-600' : 'text-red-600'; ?>">₦<?php echo number_format($totalVariance); ?></span>
                                </p>
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
            if (!$('#periodFrom').val() || !$('#periodTo').val()) {
                e.preventDefault();
                alert(
                    'Please select both Current Month and Previous Month before generating the report.'
                );
            }
        });

        window.exportAll = function(type) {
            const periodFrom = $('#periodFrom').val();
            const periodTo = $('#periodTo').val();
            if (!periodFrom || !periodTo) {
                alert('Please select both Current Month and Previous Month before exporting.');
                return;
            }

            const fromText = $('#periodFrom option:selected').text();
            const toText = $('#periodTo option:selected').text();
            const reportTitle = `variance btw ${toText} AND ${fromText}`;

            if (type === 'excel') {
                $.ajax({
                    url: 'variance_export_excel.php',
                    type: 'POST',
                    data: {
                        periodFrom: periodFrom,
                        periodTo: periodTo,
                        title: reportTitle
                    },
                    success: function(base64) {
                        try {
                            const link = document.createElement('a');
                            link.href =
                                'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' +
                                base64;
                            link.download = `Variance_Report_${fromText}_vs_${toText}.xlsx`;
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                        } catch (e) {
                            alert('Failed to download Excel.');
                        }
                    },
                    error: function() {
                        alert('Failed to generate Excel.');
                    }
                });
            } else if (type === 'pdf') {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'variance_export_pdf.php';
                form.target = '_blank';
                const f1 = document.createElement('input');
                f1.type = 'hidden';
                f1.name = 'periodFrom';
                f1.value = periodFrom;
                form.appendChild(f1);
                const f2 = document.createElement('input');
                f2.type = 'hidden';
                f2.name = 'periodTo';
                f2.value = periodTo;
                form.appendChild(f2);
                const f3 = document.createElement('input');
                f3.type = 'hidden';
                f3.name = 'title';
                f3.value = reportTitle;
                form.appendChild(f3);
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            }
        }
    });
    </script>
</body>

</html>