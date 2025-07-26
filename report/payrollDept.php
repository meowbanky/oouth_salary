<?php
session_start();

include_once('../classes/model.php');
require_once('Connections/paymaster.php');
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
    header("location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<?php include('../header1.php'); ?>
<style>
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }
    th, td {
        border: 1px solid #ccc;
        padding: 5px;
        padding: 3px;
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
    .custom-button:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(242, 140, 56, 0.3);
    }
    .form-actions {
        display: flex;
        gap: 10px;
        align-items: center;
    }
</style>

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
            <li class="btn hidden-xs"><a title="" href="switch_user" data-toggle="modal" data-target="#myModal"><i class="icon fa fa-user fa-2x"></i> <span class="text"> Welcome <b> <?php echo $_SESSION['SESS_FIRST_NAME']; ?> </b></span></a></li>
            <li class="btn hidden-xs disabled">
                <a title="" href="/" onclick="return false;"><i class="icon fa fa-clock-o fa-2x"></i> <span class="text">
                        <?php
                        $Today = date('y:m:d', time());
                        $new = date('l, F d, Y', strtotime($Today));
                        echo $new;
                        ?>
                    </span></a>
            </li>
            <li class="btn"><a href="#"><i class="icon fa fa-cog"></i><span class="text">Settings</span></a></li>
            <li class="btn">
                <a href="index.php"><i class="fa fa-power-off"></i><span class="text">Logout</span></a>
            </li>
        </ul>
    </div>
    <?php include("report_sidebar.php"); ?>

    <div id="content" class="clearfix sales_content_minibar">
        <div id="content-header" class="hidden-print">
            <h1><i class="fa fa-beaker"></i> Report Input</h1> <span id="ajax-loader"><img src="img/ajax-loader.gif" alt="" /></span>
        </div>

        <div id="breadcrumb" class="hidden-print">
            <a href="../home.php"><i class="fa fa-home"></i> Dashboard</a><a href="index.php">Reports</a><a class="current" href="payrollDept.php">Report Input: Detailed Payroll by Dept Report</a>
        </div>
        <div class="clear"></div>
        <div class="row">
            <div class="col-md-12">
                <div class="widget-box">
                    <div class="widget-title">
                            <span class="icon">
                                <i class="fa fa-align-justify"></i>
                            </span>
                        <h5 align="center"></h5>
                        <div class="clear"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 pull-left">
                            <div class="row">
                                <div class="col-6">
                                    <h3 class="page-title">
                                        <p align="center"> <img src="img/oouth_logo.gif" width="10%" height="10%" class="header-log" id="header-logo" alt="">
                                            OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL<br> DEPARTMENTAL PAYROLL SUMMARY FOR THE MONTH OF
                                        </p>
                                        <p align="center">
                                            <?php
                                            $month = '';
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
                                    </h3>
                                </div>
                            </div>
                            <div class="col-md-12 hidden-print">
                                <form class="form-horizontal form-horizontal-mobiles" method="POST" action="payrollDept.php">
                                    <div class="form-group">
                                        <label for="range" class="col-sm-3 col-md-3 col-lg-2 control-label hidden-print">Pay Period :</label>
                                        <div class="col-sm-9 col-md-9 col-lg-10">
                                            <div class="input-group">
                                                <span class="input-group-addon"><i class="fa fa-location-arrow"></i></span>
                                                <select name="period" id="period" class="form-control hidden-print">
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
                                    </div>
                                    <div class="form-actions">
                                        <button name="generate_report" type="submit" id="generate_report" class="btn btn-primary submit_button btn-large hidden-print">Submit</button>
                                        <button type="button" id="export-pdf-button" class="custom-button pdf-button">
                                            <i class="fas fa-file-pdf"></i> Export PDF
                                        </button>
                                        <button type="button" id="download-excel-button" class="custom-button excel-button">
                                            <i class="fas fa-download"></i> Download Excel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php if ($month != '') { ?>
                            <div class="top-panel pull-right hidden-print">
                                <!-- Dropdown removed -->
                            </div>
                        <?php } ?>
                        <div class="widget-content nopadding">
                            <div class="table-responsive">
                                <table id="sample_1" class="table_without">
                                    <thead>
                                    <tr>
                                        <th> Department Name </th>
                                        <th> No. of Employee </th>
                                        <th> Total Allowance </th>
                                        <th> Total Deduction </th>
                                        <th> Department Net Pay </th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    if (!isset($_POST['period'])) {
                                        $period = -1;
                                    } else {
                                        $period = $_POST['period'];
                                    }
                                    try {
                                        $query = $conn->prepare('SELECT master_staff.DEPTCD, ANY_VALUE(tbl_dept.dept) AS dept, ANY_VALUE(Sum(tbl_master.allow)) as "allow", ANY_VALUE(count(tbl_master.staff_id)) as "numb", ANY_VALUE(Sum(tbl_master.deduc)) as "deduct", ANY_VALUE(Sum(tbl_master.allow) - Sum(tbl_master.deduc)) as "net", ANY_VALUE(tbl_dept.dept) AS dept FROM tbl_master INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id INNER JOIN tbl_dept ON tbl_dept.dept_id = master_staff.DEPTCD WHERE master_staff.period = ? and tbl_master.period = ? GROUP BY master_staff.DEPTCD order by dept asc');
                                        $fin = $query->execute(array($period, $period));
                                        $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                        $numberofstaff = count($res);
                                        $counter = 1;
                                        $sumAll = 0;
                                        $sumDeduct = 0;
                                        $sumTotal = 0;
                                        $countStaff = 0;
                                        echo '<tr class="odd gradeX">';
                                        foreach ($res as $row => $link) {
                                            $query2 = $conn->prepare('SELECT Count(master_staff.DEPTCD) as "numb" FROM master_staff WHERE STATUSCD = ? and DEPTCD = ? AND master_staff.period = ? GROUP BY DEPTCD');
                                            $fin2 = $query2->execute(array('A', $link['DEPTCD'], $period));
                                            $res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
                                            foreach ($res2 as $row2 => $link2) {
                                                $numb = $link2['numb'];
                                                $countStaff = $countStaff + $numb;
                                            }
                                            echo '<td class="stylecaps">' . $link['dept'] . '</td><td align="right">' . $numb . '</td>'
                                                . '</td><td align="right">' . number_format($link['allow']) . '</td>';
                                            echo '<td align="right">' . number_format($link['deduct']) . '</td>';
                                            echo '<td align="right">' . number_format($link['net']) . '</td>';
                                            $sumAll = $sumAll + floatval($link['allow']);
                                            $sumDeduct = $sumDeduct + floatval($link['deduct']);
                                            $sumTotal = $sumTotal + floatval($link['net']);
                                            $counter++;
                                            echo '</tr>';
                                        }
                                        echo '<tr class="odd gradeX">';
                                        echo '<td class="stylecaps">TOTAL</td><td align="right"><strong>' . number_format($countStaff) . '</strong></td><td align="right"><strong>' . number_format($sumAll) . '</strong></td>';
                                        echo '<td align="right"><strong>' . number_format($sumDeduct) . '</strong></td>';
                                        echo '<td align="right"><strong>' . number_format($sumTotal) . '</strong></td>';
                                        echo '</tr>';
                                    } catch (PDOException $e) {
                                        echo $e->getMessage();
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
            Please visit our
            <a href="http://www.oouth.com/" target="_blank">
                website </a>
            to learn the latest information about the project.
            <span class="text-info"> <span class="label label-info"> 14.1</span></span>
        </div>
    </div><!--end #content-->
</div><!--end #wrapper-->

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
        form.action = 'payrollDept_export_pdf.php';
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
            url: 'payrollDept_export_excel.php',
            data: {
                period: $('#period').val(),
                period_text: '<?php echo $month; ?>'
            },
            timeout: 300000,
            success: function(response) {
                $('#ajax-loader').hide();
                try {
                    var downloadLink = document.createElement('a');
                    downloadLink.href = 'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' + response;
                    downloadLink.download = 'Payroll_Dept_' + '<?php echo $month; ?>' + '.xlsx';
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
<script src="js/tableExport.js"></script>
<script src="js/main.js"></script>
</body>
</html>