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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/ui-lightness/jquery-ui.css">
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
                            <i class="fas fa-user"></i> Personal Payslip
                        </h1>
                        <p class="text-sm text-blue-700/70 mt-1">Generate individual employee payslips with detailed salary breakdown.</p>
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
                                <select name="period" id="period" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm">
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
                                    <input type="text" name="item" id="item" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm" placeholder="Enter Staff Name or Staff No" />
                                    <div id="ajax-loader" class="absolute right-3 top-3 hidden">
                                        <i class="fas fa-spinner fa-spin text-blue-600"></i>
                                    </div>
                                </div>
                                <input type="hidden" name="staff_id" id="staff_id" value="">
                            </div>
                        </div>
                        
                        <div class="flex flex-wrap gap-3 mt-6">
                            <button type="button" onclick="generatePayslip()" class="bg-blue-700 hover:bg-blue-900 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                <i class="fas fa-search"></i> Generate Payslip
                            </button>
                            <button type="button" id="btnPrint" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <button type="button" id="sendmail" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold shadow transition flex items-center gap-2">
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
                                        
                                        // Payslip Details
                                        echo '<div id="printThis" class="printMe">';
                                        
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
                                        
                                        echo '<div class="bg-white border rounded-lg p-6 mb-6">';
                                        echo '<h4 class="text-lg font-semibold text-blue-800 mb-4">CONSOLIDATED SALARY</h4>';
                                        echo '<div class="flex justify-between items-center py-2 border-b">';
                                        echo '<span>Consolidated Salary:</span>';
                                        echo '<span class="font-semibold">₦' . number_format($consolidated) . '</span>';
                                        echo '</div>';
                                        echo '</div>';
                                        
                                        // Allowances
                                        $totalAllow = 0;
                                        try {
                                            $query = $conn->prepare('SELECT tbl_master.staff_id, tbl_master.allow, tbl_earning_deduction.ed FROM tbl_master INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id WHERE allow_id <> ? and staff_id = ? and period = ? and type = ?');
                                            $query->execute(['1', $thisemployee, $period, '1']);
                                            $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            if (count($res) > 0) {
                                                echo '<div class="bg-white border rounded-lg p-6 mb-6">';
                                                echo '<h4 class="text-lg font-semibold text-green-800 mb-4">ALLOWANCES</h4>';
                                                
                                                foreach ($res as $link) {
                                                    $totalAllow += floatval($link['allow']);
                                                    echo '<div class="flex justify-between items-center py-2 border-b">';
                                                    echo '<span>' . htmlspecialchars($link['ed']) . ':</span>';
                                                    echo '<span class="font-semibold">₦' . number_format($link['allow']) . '</span>';
                                                    echo '</div>';
                                                }
                                                echo '</div>';
                                            }
                                        } catch (PDOException $e) {
                                            // Handle error silently
                                        }
                                        
                                        // Gross Salary
                                        $grossSalary = $totalAllow + $consolidated;
                                        echo '<div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">';
                                        echo '<div class="flex justify-between items-center">';
                                        echo '<span class="text-lg font-semibold text-green-800">Gross Salary:</span>';
                                        echo '<span class="text-lg font-bold text-green-800">₦' . number_format($grossSalary) . '</span>';
                                        echo '</div>';
                                        echo '</div>';
                                        
                                        // Deductions
                                        $totalDeduction = 0;
                                        try {
                                            $query = $conn->prepare('SELECT tbl_master.staff_id, tbl_master.deduc, tbl_earning_deduction.ed FROM tbl_master INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id WHERE staff_id = ? and period = ? and type = ?');
                                            $query->execute([$thisemployee, $period, '2']);
                                            $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            if (count($res) > 0) {
                                                echo '<div class="bg-white border rounded-lg p-6 mb-6">';
                                                echo '<h4 class="text-lg font-semibold text-red-800 mb-4">DEDUCTIONS</h4>';
                                                
                                                foreach ($res as $link) {
                                                    $totalDeduction += floatval($link['deduc']);
                                                    echo '<div class="flex justify-between items-center py-2 border-b">';
                                                    echo '<span>' . htmlspecialchars($link['ed']) . ':</span>';
                                                    echo '<span class="font-semibold">₦' . number_format($link['deduc']) . '</span>';
                                                    echo '</div>';
                                                }
                                                
                                                echo '<div class="flex justify-between items-center py-2 mt-4 bg-red-50 rounded">';
                                                echo '<span class="font-semibold text-red-800">Total Deductions:</span>';
                                                echo '<span class="font-bold text-red-800">₦' . number_format($totalDeduction) . '</span>';
                                                echo '</div>';
                                                echo '</div>';
                                            }
                                        } catch (PDOException $e) {
                                            // Handle error silently
                                        }
                                        
                                        // Net Pay
                                        $netPay = $grossSalary - $totalDeduction;
                                        echo '<div class="bg-blue-50 border border-blue-200 rounded-lg p-6">';
                                        echo '<div class="flex justify-between items-center">';
                                        echo '<span class="text-xl font-bold text-blue-800">Net Pay:</span>';
                                        echo '<span class="text-2xl font-bold text-blue-800">₦' . number_format($netPay) . '</span>';
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
            const All = 0;
            
            $('#ajax-loader').show();
            $('#sendmail').prop('disabled', true);

            $('#form_payprocess').ajaxSubmit({
                data: {
                    staff_no: staff_no,
                    period: period,
                    All: All
                },
                url: 'callPdf.php',
                xhrFields: {
                    onprogress: function(e) {
                        $('#sample_1').html(e.target.responseText);
                    }
                },
                success: function(response, message) {
                    $('#ajax-loader').hide();
                    $('#sendmail').prop('disabled', false);
                    
                    if (message == 'success') {
                        alert("Email sent successfully!");
                    } else {
                        alert("Error sending email. Please try again.");
                    }
                },
                error: function() {
                    $('#ajax-loader').hide();
                    $('#sendmail').prop('disabled', false);
                    alert("Error sending email. Please try again.");
                }
            });
        });
    });

    function generatePayslip() {
        const period = $('#period').val();
        const staffId = $('#staff_id').val();
        
        if (!period) {
            alert('Please select a pay period.');
            return;
        }
        
        if (!staffId) {
            alert('Please search and select a staff member.');
            return;
        }
        
        window.location.href = "payslip_personal.php?item=" + staffId + "&period=" + period;
    }

    // Print functionality
    $(document).ready(function() {
        $('#btnPrint').click(function() {
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