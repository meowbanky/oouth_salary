<?php
session_start();
ini_set('max_execution_time', '0');

require_once('../Connections/paymaster.php');
include_once('../classes/model.php');
require_once('../libs/App.php');
$App = new App();
$App->checkAuthentication();
require_once('../libs/middleware.php');
checkPermission();

// Get parameters from URL
$periodFrom = isset($_GET['periodFrom']) ? $_GET['periodFrom'] : -1;
$periodTo = isset($_GET['periodTo']) ? $_GET['periodTo'] : -1;
$dept = isset($_GET['dept']) ? $_GET['dept'] : -1;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$results_per_page = 100;

// Get period descriptions for display
function getPeriodDescription($conn, $periodId) {
    if ($periodId == -1) return '';
    $query = $conn->prepare('SELECT CONCAT(description, "-", periodYear) as description FROM payperiods WHERE periodId = ?');
    $query->execute([$periodId]);
    $result = $query->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['description'] : '';
}

$monthFrom = getPeriodDescription($conn, $periodFrom);
$monthTo = getPeriodDescription($conn, $periodTo);

// Get department name
$deptName = '';
if ($dept != -1) {
    try {
        $query = $conn->prepare('SELECT dept FROM tbl_dept WHERE dept_id = ?');
        $query->execute([$dept]);
        $result = $query->fetch(PDO::FETCH_ASSOC);
        $deptName = $result ? $result['dept'] : '';
    } catch (PDOException $e) {
        $deptName = 'Unknown Department';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departmental Payroll Table - OOUTH Salary Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-100 min-h-screen">
    <?php include('../header.php'); ?>
    <div class="flex min-h-screen">
        <?php include('report_sidebar_modern.php'); ?>
        <main class="flex-1 px-2 md:px-8 py-4 flex flex-col">
            <div class="w-full max-w-7xl mx-auto flex-1 flex flex-col">
                <!-- Header Section -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-blue-800 flex items-center gap-2">
                            <i class="fas fa-table"></i> Departmental Payroll Table
                        </h1>
                        <p class="text-sm text-blue-700/70 mt-1">Detailed payroll breakdown by department with
                            comprehensive employee data.</p>
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
                        <!-- Organization Header -->
                        <div class="text-center mb-8">
                            <img src="img/oouth_logo.gif" alt="OOUTH Logo" class="h-16 mx-auto mb-4">
                            <h3 class="text-lg font-bold text-blue-800">OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL
                            </h3>
                            <p class="text-blue-600 font-medium">Departmental Payroll Summary</p>
                            <?php if ($monthFrom && $monthTo): ?>
                            <p class="text-sm text-gray-600 mt-2">Period: <?php echo htmlspecialchars($monthFrom); ?> to
                                <?php echo htmlspecialchars($monthTo); ?></p>
                            <?php endif; ?>
                            <?php if ($deptName): ?>
                            <p class="text-sm text-gray-600">Department: <?php echo htmlspecialchars($deptName); ?></p>
                            <?php endif; ?>
                        </div>

                        <form method="GET" action="payrollTablebyDept.php" class="space-y-6">
                            <div class="grid md:grid-cols-3 gap-6">
                                <div>
                                    <label for="periodFrom" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calendar-alt mr-2 text-blue-600"></i>Period From
                                    </label>
                                    <select name="periodFrom" id="periodFrom"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm"
                                        required>
                                        <option value="">Select Starting Period</option>
                                        <?php
                                        try {
                                            $query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear, payperiods.periodId FROM payperiods WHERE payrollRun = ? ORDER BY periodId DESC');
                                            $query->execute(['1']);
                                            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                                                $selected = ($row['periodId'] == $periodFrom) ? 'selected="selected"' : '';
                                                echo sprintf(
                                                    '<option value="%s" %s>%s - %s</option>',
                                                    htmlspecialchars($row['periodId']),
                                                    $selected,
                                                    htmlspecialchars($row['description']),
                                                    htmlspecialchars($row['periodYear'])
                                                );
                                            }
                                        } catch (PDOException $e) {
                                            echo "<option value=''>Error loading periods</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div>
                                    <label for="periodTo" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calendar-alt mr-2 text-blue-600"></i>Period To
                                    </label>
                                    <select name="periodTo" id="periodTo"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm"
                                        required>
                                        <option value="">Select Ending Period</option>
                                        <?php
                                        try {
                                            $query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear, payperiods.periodId FROM payperiods WHERE payrollRun = ? ORDER BY periodId DESC');
                                            $query->execute(['1']);
                                            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                                                $selected = ($row['periodId'] == $periodTo) ? 'selected="selected"' : '';
                                                echo sprintf(
                                                    '<option value="%s" %s>%s - %s</option>',
                                                    htmlspecialchars($row['periodId']),
                                                    $selected,
                                                    htmlspecialchars($row['description']),
                                                    htmlspecialchars($row['periodYear'])
                                                );
                                            }
                                        } catch (PDOException $e) {
                                            echo "<option value=''>Error loading periods</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div>
                                    <label for="dept" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-building mr-2 text-blue-600"></i>Department
                                    </label>
                                    <select name="dept" id="dept"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm"
                                        required>
                                        <option value="">Select Department</option>
                                        <?php retrieveSelectwithoutWhere('tbl_dept', '*', 'dept', 'dept_id', 'dept'); ?>
                                    </select>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-3 pt-4">
                                <button name="generate_report" type="submit"
                                    class="bg-blue-700 hover:bg-blue-900 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-search"></i> Generate Report
                                </button>
                                <?php if ($periodFrom != -1 && $periodTo != -1 && $dept != -1): ?>
                                <button type="button" onclick="window.print()"
                                    class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-print"></i> Print
                                </button>
                                <button type="button" id="export-excel-button"
                                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-file-excel"></i> Export Excel
                                </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($periodFrom != -1 && $periodTo != -1 && $dept != -1): ?>
                <!-- Pagination Controls -->
                <?php
                    try {
                        $sql = 'SELECT COUNT(staff_id) as "Total" FROM master_staff WHERE period BETWEEN ? AND ? AND master_staff.DEPTCD = ?';
                        $query = $conn->prepare($sql);
                        $query->execute([$periodFrom, $periodTo, $dept]);
                        $row = $query->fetch(PDO::FETCH_ASSOC);
                        $total_pages = ceil($row['Total'] / $results_per_page);
                    } catch (PDOException $e) {
                        $total_pages = 1;
                    }
                    ?>

                <?php if ($total_pages > 1): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
                    <div class="bg-blue-50 px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-blue-800 flex items-center gap-2">
                            <i class="fas fa-list"></i> Pagination Controls
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="flex flex-wrap gap-2 justify-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php
                                        $queryParams = [
                                            'page' => $i,
                                            'periodFrom' => $periodFrom,
                                            'periodTo' => $periodTo,
                                            'dept' => $dept
                                        ];
                                        ?>
                                        <?php $pageClasses = $page == $i ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-blue-800 border-gray-300 hover:bg-blue-100'; ?>
                                        <a href="?<?php echo http_build_query($queryParams); ?>"
                                           class="px-3 py-2 border rounded text-sm font-semibold transition <?php echo $pageClasses; ?>">
                                <?php echo $i; ?>
                            </a>
                            <?php endfor; ?>
                        </div>
                        <p class="text-center text-sm text-gray-600 mt-4">
                            Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                            (<?php echo number_format($row['Total'] ?? 0); ?> total employees)
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Report Table -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-blue-50 px-6 py-4 border-b flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-blue-800 flex items-center gap-2">
                            <i class="fas fa-table"></i> Employee Payroll Details
                        </h2>
                        <img src="img/oouth_logo.gif" alt="OOUTH Logo" class="h-10">
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="sample_1">
                            <thead class="bg-blue-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        Staff No</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        Name</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        Pay Period</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        Department</th>
                                    <?php
                                        // Get allowance columns
                                        try {
                                            $query = $conn->prepare('SELECT tbl_earning_deduction.ed_id, tbl_earning_deduction.ed FROM tbl_earning_deduction WHERE tbl_earning_deduction.edType = ?');
                                            $query->execute([1]);
                                            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                                                echo '<th class="px-6 py-3 text-right text-xs font-medium text-blue-700 uppercase tracking-wider">' . htmlspecialchars($row['ed']) . '</th>';
                                            }
                                            echo '<th class="px-6 py-3 text-right text-xs font-medium text-blue-700 uppercase tracking-wider">Total Allow</th>';

                                            // Get deduction columns
                                            $query = $conn->prepare('SELECT tbl_earning_deduction.ed_id, tbl_earning_deduction.ed FROM tbl_earning_deduction WHERE tbl_earning_deduction.edType > ?');
                                            $query->execute([1]);
                                            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                                                echo '<th class="px-6 py-3 text-right text-xs font-medium text-blue-700 uppercase tracking-wider">' . htmlspecialchars($row['ed']) . '</th>';
                                            }
                                            echo '<th class="px-6 py-3 text-right text-xs font-medium text-blue-700 uppercase tracking-wider">Total Deduct</th>';
                                            echo '<th class="px-6 py-3 text-right text-xs font-medium text-blue-700 uppercase tracking-wider">Net Pay</th>';
                                        } catch (PDOException $e) {
                                            echo '<th class="px-6 py-3 text-center text-xs font-medium text-red-700 uppercase tracking-wider">Error loading columns</th>';
                                        }
                                        ?>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php
                                    try {
                                        $start_from = ($page - 1) * $results_per_page;
                                        $query = $conn->prepare('SELECT
                                            master_staff.staff_id, DEPTCD, ANY_VALUE(master_staff.`NAME`) AS `NAME`, 
                                            ANY_VALUE(tbl_dept.dept) AS dept, ANY_VALUE(CONCAT(payperiods.description," ",payperiods.periodYear)) as period 
                                            FROM master_staff 
                                            INNER JOIN tbl_dept ON tbl_dept.dept_id = master_staff.DEPTCD 
                                            INNER JOIN payperiods ON payperiods.periodId = master_staff.period 
                                            WHERE master_staff.period BETWEEN ? AND ? AND DEPTCD = ? 
                                            GROUP BY master_staff.staff_id 
                                            ORDER BY DEPTCD, staff_id 
                                            LIMIT ?, ?');
                                        $query->execute([$periodFrom, $periodTo, $dept, $start_from, $results_per_page]);
                                        $res = $query->fetchAll(PDO::FETCH_ASSOC);

                                        if (count($res) > 0) {
                                            foreach ($res as $link) {
                                                echo '<tr class="hover:bg-gray-50 transition-colors duration-150">';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">' . htmlspecialchars($link['staff_id']) . '</td>';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($link['NAME']) . '</td>';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">From ' . htmlspecialchars($monthFrom) . ' To ' . htmlspecialchars($monthTo) . '</td>';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($link['dept']) . '</td>';

                                                $allow = 0;
                                                $dedu = 0;

                                                // Allowance columns
                                                $query2 = $conn->prepare('SELECT tbl_earning_deduction.ed_id, tbl_earning_deduction.ed FROM tbl_earning_deduction WHERE tbl_earning_deduction.edType = ?');
                                                $query2->execute([1]);
                                                while ($row = $query2->fetch(PDO::FETCH_ASSOC)) {
                                                    $j = retrievePayroll($periodFrom, $periodTo, $link['staff_id'], $row['ed_id']);
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">₦' . number_format($j) . '</td>';
                                                    $allow += $j;
                                                }
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-right">₦' . number_format($allow) . '</td>';

                                                // Deduction columns
                                                $query3 = $conn->prepare('SELECT tbl_earning_deduction.ed_id, tbl_earning_deduction.ed FROM tbl_earning_deduction WHERE tbl_earning_deduction.edType > ?');
                                                $query3->execute([1]);
                                                while ($row = $query3->fetch(PDO::FETCH_ASSOC)) {
                                                    $j = retrievePayroll($periodFrom, $periodTo, $link['staff_id'], $row['ed_id']);
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">₦' . number_format($j) . '</td>';
                                                    $dedu += $j;
                                                }

                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-right">₦' . number_format($dedu) . '</td>';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-blue-900 text-right">₦' . number_format(floatval($allow) - floatval($dedu)) . '</td>';
                                                echo '</tr>';
                                            }
                                        } else {
                                            echo '<tr>';
                                            echo '<td colspan="100%" class="px-6 py-4 text-center text-sm text-gray-500">No payroll data found for the selected criteria.</td>';
                                            echo '</tr>';
                                        }
                                    } catch (PDOException $e) {
                                        echo '<tr>';
                                        echo '<td colspan="100%" class="px-6 py-4 text-center text-sm text-red-500">Error: ' . htmlspecialchars($e->getMessage()) . '</td>';
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
                                <p><strong>Date:</strong> <?php echo date('l, F d, Y'); ?></p>
                            </div>
                            <div class="text-sm text-gray-600">
                                <p><strong>Department:</strong> <?php echo htmlspecialchars($deptName); ?></p>
                                <p><strong>Period:</strong> <?php echo htmlspecialchars($monthFrom); ?> to
                                    <?php echo htmlspecialchars($monthTo); ?></p>
                                <p><strong>Page:</strong> <?php echo $page; ?> of <?php echo $total_pages; ?></p>
                                <p><strong>Total Employees:</strong> <?php echo number_format($row['Total'] ?? 0); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="bg-white rounded-xl shadow-lg p-6 text-center text-gray-600">
                    <i class="fas fa-info-circle text-4xl text-blue-500 mb-4"></i>
                    <p class="text-lg font-semibold">Please select Period From, Period To, and Department to generate
                        the payroll table.</p>
                    <p class="text-sm mt-2">Use the form above to view detailed payroll information by department.</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script type="text/javascript">
    $(document).ready(function() {
        // Form validation
        $('#generate_report').click(function(e) {
            const periodFrom = $('#periodFrom').val();
            const periodTo = $('#periodTo').val();
            const dept = $('#dept').val();

            if (!periodFrom || !periodTo || !dept) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Information',
                    text: 'Please select Period From, Period To, and Department before generating the report.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#1E40AF'
                });
            }
        });

        // Export Excel functionality
        $('#export-excel-button').click(function() {
            downloadExcel();
        });

        function downloadExcel() {
            // Show loading indicator
            $('#export-excel-button').prop('disabled', true).html(
                '<i class="fas fa-spinner fa-spin"></i> Exporting...');

            // Get current parameters
            const urlParams = new URLSearchParams(window.location.search);
            const periodFrom = urlParams.get('periodFrom');
            const periodTo = urlParams.get('periodTo');
            const dept = urlParams.get('dept');

            $.ajax({
                type: "POST",
                url: 'payrollTablebyDept_export_excel.php',
                data: {
                    periodFrom: periodFrom,
                    periodTo: periodTo,
                    dept: dept,
                    periodFrom_text: '<?php echo $monthFrom; ?>',
                    periodTo_text: '<?php echo $monthTo; ?>',
                    dept_text: '<?php echo $deptName; ?>'
                },
                timeout: 300000,
                success: function(response) {
                    $('#export-excel-button').prop('disabled', false).html(
                        '<i class="fas fa-file-excel"></i> Export Excel');

                    try {
                        // Check if response is an error JSON
                        if (typeof response === 'string' && response.includes('{"error":')) {
                            var errorData = JSON.parse(response);
                            Swal.fire({
                                icon: 'error',
                                title: 'Export Error',
                                text: errorData.error,
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#1E40AF'
                            });
                            return;
                        }

                        // Check if response is HTML error page
                        if (typeof response === 'string' && response.includes('<!DOCTYPE html>')) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Server Error',
                                text: 'Server error occurred. Please try again or contact administrator.',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#1E40AF'
                            });
                            return;
                        }

                        // Check if response is empty or invalid
                        if (!response || response.length === 0) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'No Data',
                                text: 'No data received from server. Please try again.',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#1E40AF'
                            });
                            return;
                        }

                        var downloadLink = document.createElement('a');
                        downloadLink.href =
                            'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' +
                            response;
                        downloadLink.download =
                            'Payroll_Table_<?php echo $deptName; ?>_<?php echo $monthFrom; ?>_to_<?php echo $monthTo; ?>.xlsx';
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);

                        Swal.fire({
                            icon: 'success',
                            title: 'Export Successful',
                            text: 'Excel file has been downloaded successfully!',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#1E40AF'
                        });
                    } catch (e) {
                        console.error('Error processing Excel response:', e);
                        Swal.fire({
                            icon: 'error',
                            title: 'Export Error',
                            text: 'Error generating Excel file. Please try again.',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#1E40AF'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    $('#export-excel-button').prop('disabled', false).html(
                        '<i class="fas fa-file-excel"></i> Export Excel');
                    console.error('AJAX Error:', status, error);

                    let errorMessage = 'Error downloading Excel file. Please try again.';
                    if (status === 'timeout') {
                        errorMessage =
                            'Request timed out. Please try again or contact administrator.';
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Export Failed',
                        text: errorMessage,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#1E40AF'
                    });
                }
            });
        }
    });
    </script>
</body>

</html>