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
    <title>Employee Report - OOUTH Salary Management</title>
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
                                <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Employee Report</span>
                            </div>
                        </li>
                    </ol>
                </nav>

            <div class="w-full max-w-7xl mx-auto flex-1 flex flex-col">
                <!-- Header Section -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-blue-800 flex items-center gap-2">
                            <i class="fas fa-users"></i> Employee Report
                        </h1>
                        <p class="text-sm text-blue-700/70 mt-1">Generate comprehensive employee information reports.
                        </p>
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
                        <form method="POST" action="employee_report.php" class="space-y-6">
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
                                        $period = isset($_POST['period']) ? $_POST['period'] : -1;
                                        try {
                                            $query = $conn->prepare('SELECT description, periodYear, periodId FROM payperiods WHERE payrollRun = ? ORDER BY periodId DESC');
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
                                <button name="generate_report" type="submit" id="generate_report"
                                    class="bg-blue-700 hover:bg-blue-900 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-search"></i> Generate Report
                                </button>
                                <button type="button" onclick="downloadExcel()"
                                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-file-excel"></i> Download Excel
                                </button>
                                <button type="button" onclick="exportPDF()"
                                    class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                    <i class="fas fa-file-pdf"></i> Download PDF
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
                $month = '';
                if (isset($_POST['period']) && $_POST['period'] != '') {
                    try {
                        $query = $conn->prepare('SELECT description, periodYear FROM payperiods WHERE periodId = ?');
                        $query->execute([$period]);
                        $row = $query->fetch(PDO::FETCH_ASSOC);
                        $month = $row ? $row['description'] . '-' . $row['periodYear'] : 'Not Selected';
                    } catch (PDOException $e) {
                        $month = 'Error loading period';
                    }
                }
                ?>

                <?php if ($month != '' && $month != 'Not Selected') { ?>
                <!-- Report Header -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
                    <div class="bg-blue-50 px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold text-blue-800 text-center">
                            OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL
                        </h2>
                        <p class="text-center text-blue-700 font-medium mt-2">
                            Employee Report for the Month of: <?php echo htmlspecialchars($month); ?>
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
                                        class="px-3 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        S/No</th>
                                    <th
                                        class="px-3 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        Staff No.</th>
                                    <th
                                        class="px-3 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        Name</th>
                                    <th
                                        class="px-3 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        Email</th>
                                    <th
                                        class="px-3 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        Dept</th>
                                    <th
                                        class="px-3 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        Emp Date</th>
                                    <th
                                        class="px-3 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        Post</th>
                                    <th
                                        class="px-3 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        Grade</th>
                                    <th
                                        class="px-3 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        Step</th>
                                    <th
                                        class="px-3 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        Bank</th>
                                    <th
                                        class="px-3 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                                        Acct. No.</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php
                                    if (isset($_POST['period']) && $_POST['period'] != '') {
                                        try {
                                            $sql = 'SELECT master_staff.staff_id, master_staff.`NAME`, tbl_dept.dept, master_staff.GRADE, master_staff.STEP, 
                                                        tbl_bank.BNAME, master_staff.ACCTNO, employee.EMPDATE, employee.EMAIL, employee.POST
                                                    FROM master_staff
                                                    INNER JOIN tbl_dept ON master_staff.DEPTCD = tbl_dept.dept_id
                                                    INNER JOIN tbl_bank ON master_staff.BCODE = tbl_bank.BCODE
                                                    INNER JOIN employee ON master_staff.staff_id = employee.staff_id
                                                    WHERE master_staff.period = ? AND employee.STATUSCD = ?
                                                    ORDER BY master_staff.staff_id ASC';
                                            
                                            $query = $conn->prepare($sql);
                                            $query->execute([$period, 'A']);
                                            $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            $counter = 1;
                                            $totalEmployees = count($res);

                                            if ($totalEmployees > 0) {
                                                foreach ($res as $row) {
                                                    echo '<tr class="hover:bg-gray-50 transition-colors duration-150">';
                                                    echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900">' . $counter . '</td>';
                                                    echo '<td class="px-3 py-2 whitespace-nowrap text-xs font-medium text-gray-900">' . htmlspecialchars($row['staff_id'] ?? '') . '</td>';
                                                    echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900">' . htmlspecialchars($row['NAME'] ?? '') . '</td>';
                                                    echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900">' . htmlspecialchars($row['EMAIL'] ?? '') . '</td>';
                                                    echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900">' . htmlspecialchars($row['dept'] ?? '') . '</td>';
                                                    echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900">' . htmlspecialchars($row['EMPDATE'] ?? '') . '</td>';
                                                    echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900">' . htmlspecialchars($row['POST'] ?? '') . '</td>';
                                                    echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900">' . htmlspecialchars($row['GRADE'] ?? '') . '</td>';
                                                    echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900">' . htmlspecialchars($row['STEP'] ?? '') . '</td>';
                                                    echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900">' . htmlspecialchars($row['BNAME'] ?? '') . '</td>';
                                                    echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-gray-900">' . htmlspecialchars($row['ACCTNO'] ?? '') . '</td>';
                                                    echo '</tr>';
                                                    $counter++;
                                                }

                                                // Summary row
                                                echo '<tr class="bg-blue-50 border-t-2 border-blue-200">';
                                                echo '<td colspan="11" class="px-3 py-3 text-center text-xs font-bold text-blue-900">';
                                                echo 'Total Active Employees: ' . $totalEmployees;
                                                echo '</td>';
                                                echo '</tr>';
                                            } else {
                                                echo '<tr>';
                                                echo '<td colspan="11" class="px-6 py-4 text-center text-sm text-gray-500">No active employees found for the selected period.</td>';
                                                echo '</tr>';
                                            }
                                        } catch (PDOException $e) {
                                            echo '<tr>';
                                            echo '<td colspan="11" class="px-6 py-4 text-center text-sm text-red-500">Error: ' . htmlspecialchars($e->getMessage()) . '</td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr>';
                                        echo '<td colspan="11" class="px-6 py-4 text-center text-sm text-gray-500">Please select a pay period to generate the employee report.</td>';
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
                                <p><strong>Period:</strong> <?php echo htmlspecialchars($month); ?></p>
                                <?php if (isset($totalEmployees) && $totalEmployees > 0): ?>
                                <p><strong>Total Employees:</strong> <?php echo $totalEmployees; ?></p>
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

    function downloadExcel() {
        if (!$('#period').val()) {
            alert('Please select a Pay Period before downloading Excel.');
            return;
        }

        $('#ajax-loader').show();
        $.ajax({
            type: "POST",
            url: 'employee_report_export_excel.php',
            data: {
                period: $('#period').val(),
                period_text: '<?php echo $month; ?>'
            },
            timeout: 300000,
            success: function(response) {
                $('#ajax-loader').hide();
                try {
                    if (typeof response === 'string' && response.includes('<!DOCTYPE html>')) {
                        console.error('Received HTML error page instead of data');
                        alert('Server error occurred. Please try again or contact administrator.');
                        return;
                    }

                    var downloadLink = document.createElement('a');
                    downloadLink.href =
                        'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' +
                        response;
                    downloadLink.download = 'Employee_Report_' + '<?php echo $month; ?>' + '.xlsx';
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

    function exportPDF() {
        if (!$('#period').val()) {
            alert('Please select a Pay Period before downloading PDF.');
            return;
        }

        $('#ajax-loader').show();
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'employee_report_export_pdf.php';
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
    </script>
</body>

</html>