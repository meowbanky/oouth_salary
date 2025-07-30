<?php
session_start();

include_once('../classes/model.php');
require_once('../Connections/paymaster.php');

if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) == '') {
    header("location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<?php include('../header1.php'); ?>

<body data-color="grey" class="flat">
<div class="modal fade hidden-print" id="myModal"></div>
<div id="wrapper">
    <div id="header" class="hidden-print">
        <h1><a href="../index.php"><img src="img/header_logo.png" class="hidden-print header-log" id="header-logo" alt=""></a></h1>
        <a id="menu-trigger" href="#"><i class="fa fa-bars fa fa-2x"></i></a>
        <div class="clear"></div>
    </div>

    <div id="user-nav" class="hidden-print hidden-xs">
        <ul class="btn-group">
            <li class="btn hidden-xs"><a title="" href="switch_user" data-toggle="modal" data-target="#myModal"><i class="icon fa fa-user fa-2x"></i> <span class="text"> Welcome <b><?php echo $_SESSION['SESS_FIRST_NAME']; ?></b></span></a></li>
            <li class="btn hidden-xs disabled">
                <a title="" href="/" onclick="return false;"><i class="icon fa fa-clock-o fa-2x"></i> <span class="text">
                            <?php
                            $today = date('y:m:d', time());
                            $formattedDate = date('l, F d, Y', strtotime($today));
                            echo $formattedDate;
                            ?>
                        </span></a>
            </li>
            <li class="btn"><a href="#"><i class="icon fa fa-cog"></i><span class="text">Settings</span></a></li>
            <li class="btn"><a href="index.php"><i class="fa fa-power-off"></i><span class="text">Logout</span></a></li>
        </ul>
    </div>
    <?php include("report_sidebar.php"); ?>

    <div id="content" class="clearfix sales_content_minibar">
        <div id="content-header" class="hidden-print">
            <h1><i class="fa fa-beaker"></i> Report Input</h1> <span id="ajax-loader"><img src="img/ajax-loader.gif" alt="" /></span>
        </div>

        <div id="breadcrumb" class="hidden-print">
            <a href="../home.php"><i class="fa fa-home"></i> Dashboard</a>
            <a href="index.php">Reports</a>
            <a class="current" href="employee_report.php">Report Input: Detailed Employee Report</a>
        </div>
        <div class="clear"></div>
        <div class="row">
            <div class="col-md-12">
                <div class="widget-box">
                    <div class="widget-title">
                        <span class="icon"><i class="fa fa-align-justify"></i></span>
                        <h5 align="center"></h5>
                        <div class="clear"></div>
                    </div>
                    <div class="row align-items-center">
                        <div class="col-md-2 pull-left">
                            <img src="img/oouth_logo.gif" width="50" height="50" class="header-log" id="header-logo" alt="">
                        </div>
                        <div class="col-md-8 text-center">
                            <h3 style="text-transform: uppercase; margin: 0;">
                                OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL<br>
                                Employee Report for the Month of:
                                <?php
                                $month = '';
                                $period = isset($_POST['period']) ? $_POST['period'] : -1;
                                try {
                                    $query = $conn->prepare('SELECT description, periodYear FROM payperiods WHERE periodId = ?');
                                    $query->execute([$period]);
                                    $row = $query->fetch(PDO::FETCH_ASSOC);
                                    $month = $row ? $row['description'] . '-' . $row['periodYear'] : 'Not Selected';
                                    echo htmlspecialchars($month);
                                } catch (PDOException $e) {
                                    echo 'Error: ' . htmlspecialchars($e->getMessage());
                                }
                                ?>
                            </h3>
                        </div>
                        <div class="col-md-2 pull-right">
                            <img src="img/oouth_logo.gif" width="50" height="50" class="header-log" id="header-logo" alt="">
                        </div>
                    </div>
                    <div class="col-md-12 hidden-print">
                        <form class="form-horizontal form-horizontal-mobiles" method="POST" action="employee_report.php">
                            <div class="form-group">
                                <label for="range" class="col-sm-3 col-md-3 col-lg-2 control-label hidden-print">Pay Period :</label>
                                <div class="col-sm-9 col-md-9 col-lg-10">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-location-arrow hidden-print"></i></span>
                                        <select name="period" id="period" class="form-control hidden-print" required>
                                            <option value="">Select Pay Period</option>
                                            <?php
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
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="generate_report" id="generate_report" class="btn btn-primary submit_button btn-large hidden-print">Submit</button>
                                <div class="btn-group">
                                    <button type="button" class="custom-button excel-button" onclick="downloadExcel()">Download Excel</button>
                                    <button type="button" class="custom-button pdf-button" onclick="exportPDF()">Download PDF</button>
                                    <button type="button" class="custom-button print-button" onclick="window.print()">Print</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="widget-content">
                        <div class="table-container">
                            <table class="table_without" id="sample_1">
                                <thead>
                                <tr>
                                    <th>S/No</th>
                                    <th>Staff No.</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Dept</th>
                                    <th>Emp Date</th>
                                    <th>Post</th>
                                    <th>Grade</th>
                                    <th>Step</th>
                                    <th>Bank</th>
                                    <th>Acct. No.</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                try {
                                    $sql = 'SELECT master_staff.staff_id, master_staff.`NAME`, tbl_dept.dept, master_staff.GRADE, master_staff.STEP, 
                        tbl_bank.BNAME, master_staff.ACCTNO, employee.EMPDATE, employee.EMAIL, employee.POST
                        FROM master_staff
                        INNER JOIN tbl_dept ON master_staff.DEPTCD = tbl_dept.dept_id
                        INNER JOIN tbl_bank ON master_staff.BCODE = tbl_bank.BCODE
                        INNER JOIN employee ON master_staff.staff_id = employee.staff_id 
                        WHERE master_staff.period = ?';
                                    $query = $conn->prepare($sql);
                                    $query->execute([$period]);
                                    $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                    $counter = 1;

                                    if (empty($res)) {
                                        echo '<tr><td colspan="11" style="text-align: center;">No data available for the selected period.</td></tr>';
                                    } else {
                                        foreach ($res as $link) {
                                            echo '<tr class="odd gradeX">';
                                            echo '<td class="stylecaps">' . $counter . '</td>';
                                            echo '<td class="stylecaps">' . htmlspecialchars($link['staff_id'] ?? '') . '</td>';
                                            echo '<td>' . htmlspecialchars($link['NAME'] ?? '') . '</td>';
                                            echo '<td>' . htmlspecialchars($link['EMAIL'] ?? '') . '</td>';
                                            echo '<td>' . htmlspecialchars($link['dept'] ?? '') . '</td>';
                                            echo '<td>' . htmlspecialchars($link['EMPDATE'] ?? '') . '</td>';
                                            echo '<td>' . htmlspecialchars($link['POST'] ?? '') . '</td>';
                                            echo '<td>' . htmlspecialchars($link['GRADE'] ?? '') . '</td>';
                                            echo '<td>' . htmlspecialchars($link['STEP'] ?? '') . '</td>';
                                            echo '<td>' . htmlspecialchars($link['BNAME'] ?? '') . '</td>';
                                            echo '<td>' . htmlspecialchars($link['ACCTNO'] ?? '') . '</td>';
                                            echo '</tr>';
                                            $counter++;
                                        }
                                    }
                                } catch (PDOException $e) {
                                    echo '<tr><td colspan="11" style="text-align: center;">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div id="register_container" class="receiving"></div>
        </div>
    </div>

    <div id="footer" class="col-md-12 hidden-print">
        Please visit our <a href="http://www.oouth.com/" target="_blank">website</a> to learn the latest information about the project.
        <span class="text-info"><span class="label label-info">14.1</span></span>
    </div>
</div>

<style>
    body {
        font-size: 16px;
    }
    #content {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        overflow-x: visible; /* Ensure content doesn't overflow horizontally */
    }
    .widget-box {
        width: 100%;
        box-sizing: border-box;
    }
    .widget-content {
        width: 100%;
        padding: 15px; /* Remove nopadding class and add padding */
        box-sizing: border-box;
    }
    .table-container {
        width: 100%;
        overflow-x: auto; /* Allow horizontal scrolling for the table */
        -webkit-overflow-scrolling: touch; /* Smooth scrolling on mobile */
    }
    .table_without {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
        display: table; /* Ensure table renders as a table */
        visibility: visible; /* Ensure table is visible */
    }
    .table_without th,
    .table_without td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
        word-wrap: break-word;
        min-width: 80px; /* Prevent columns from collapsing too small */
    }
    .table_without th {
        background-color: #f2f2f2;
        font-weight: bold;
        position: sticky;
        top: 0;
        z-index: 1;
    }
    .table_without td.stylecaps {
        text-transform: uppercase;
    }
    .table_without tr.odd.gradeX td {
        background-color: #f9f9f9;
    }
    .custom-button {
        display: inline-flex;
        align-items: center;
        padding: 8px 16px;
        border: none;
        border-radius: 5px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: background-color 0.3s;
        margin-right: 5px;
    }
    .custom-button i {
        margin-right: 8px;
        font-size: 16px;
    }
    .pdf-button {
        background-color: #f28c38;
        color: white;
    }
    .pdf-button:hover {
        background-color: #d87a32;
    }
    .excel-button {
        background-color: #2e7d32;
        color: white;
    }
    .excel-button:hover {
        background-color: #1b5e20;
    }
    .print-button {
        background-color: #2196F3;
        color: white;
    }
    .print-button:hover {
        background-color: #1976D2;
    }
</style>

<script type="text/javascript" language="javascript">
    $(document).ready(function() {
        $("#start_month, #start_day, #start_year, #end_month, #end_day, #end_year").change(function() {
            $("#complex_radio").prop('checked', true);
        });

        $("#report_date_range_simple").change(function() {
            $("#simple_radio").prop('checked', true);
        });

        // Ensure the table is visible on page load
        $('.table_without').css({
            'display': 'table',
            'visibility': 'visible'
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

    function exportPDF() {
        $('#ajax-loader').show();
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'employee_export_pdf.php';
        form.style.display = 'none';

        var fields = {
            period: $('#period').val(),
            month: '<?php echo addslashes($month); ?>'
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
            url: 'employee_export_excel.php',
            data: {
                period: $('#period').val(),
                bank: '', // No bank field in this form; set to empty string
                period_text: '<?php echo addslashes($month); ?>'
            },
            timeout: 300000,
            success: function(response) {
                $('#ajax-loader').hide();
                try {
                    var downloadLink = document.createElement('a');
                    downloadLink.href = 'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' + response;
                    downloadLink.download = 'Employee_Report_' + '<?php echo addslashes($month); ?>' + '.xlsx';
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
<script src="js/main.js"></script>
</body>
</html>