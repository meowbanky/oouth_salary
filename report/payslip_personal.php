<?php
ini_set('max_execution_time', '0');
session_start();

include_once('../classes/model.php');
require_once('../Connections/paymaster.php');
require_once('tcpdf.php');
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
    header("location: ../index.php");
    exit();
}


if (isset($_GET['period'])) {

    $period = $_GET['period'];
} else {
    $period = $_SESSION['currentactiveperiod'];
}
?>
<!DOCTYPE html>
<?php include('../header_payslip.php'); ?>

<body data-color="grey" class="flat">
    <div class="modal fade hidden-print" id="myModal"></div>
    <div id="wrapper">
        <div id="header" class="hidden-print">
            <h1><a href="../index.php"><img src="img/tasce_logo.png" class="hidden-print header-log" id="header-logo" alt=""></a></h1>
            <a id="menu-trigger" href="#"><i class="fa fa-bars fa fa-2x"></i></a>
            <div class="clear"></div>
        </div>




        <div id="user-nav" class="hidden-print hidden-xs">
            <ul class="btn-group ">
                <li class="btn  hidden-xs"><a title="" href="switch_user" data-toggle="modal" data-target="#myModal"><i class="icon fa fa-user fa-2x"></i> <span class="text"> Welcome <b> <?php echo $_SESSION['SESS_FIRST_NAME']; ?> </b></span></a></li>
                <li class="btn  hidden-xs disabled">
                    <a title="" href="/" onclick="return false;"><i class="icon fa fa-clock-o fa-2x"></i> <span class="text">
                            <?php
                            $Today = date('y:m:d', time());
                            $new = date('l, F d, Y', strtotime($Today));
                            echo $new;
                            ?> </span></a>
                </li>
                <li class="btn "><a href="#"><i class="icon fa fa-cog"></i><span class="text">Settings</span></a></li>
                <li class="btn  ">
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
                <a href="../home.php"><i class="fa fa-home"></i> Dashboard</a><a href="index.php">Reports</a><a class="current" href="payslip_personal.php">Report Input: Detailed Payslip individual</a>
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
                            <div class="clear"></div>

                        </div>
                        <div class="row">
                            <?php
                            global $conn;
                            $deptName = '';
                            $dept = '';
                            if (!isset($_POST['Dept'])) {
                                $dept = -1;
                            } else {
                                $dept = $_POST['Dept'];
                            }
                            try {
                                $query = $conn->prepare('SELECT tbl_dept.dept_id, tbl_dept.dept FROM tbl_dept WHERE dept_id = ?');
                                $res = $query->execute(array($dept));
                                $out = $query->fetchAll(PDO::FETCH_ASSOC);

                                while ($row = array_shift($out)) {
                                    $deptName = $row['dept'];
                                }
                            } catch (PDOException $e) {
                                $e->getMessage();
                            }

                            ?>


                            <div class="col-md-12 pull-left">
                                <img src="../img/tasce_logo.png" width="10%" height="10%" class="header-logo hidden-print" id="header-logo" alt="">
                                <h2 class="page-title pull-right hidden-print">
                                    <p align="center"> <?php echo $_SESSION['BUSINESSNAME']; ?>, <?php echo $_SESSION['town']; ?> <br><?php echo $deptName; ?> Payslip Report
                                    <p align="center">
                                        for the Month of:
                                        <?php
                                        global $conn;

                                        try {
                                            $query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear, payperiods.periodId FROM payperiods WHERE periodId = ?');
                                            $res = $query->execute(array($period));
                                            $out = $query->fetchAll(PDO::FETCH_ASSOC);

                                            while ($row = array_shift($out)) {
                                                $fullPeriod =  $row['description'] . '-' . $row['periodYear'];
                                                echo ($fullPeriod);
                                            }
                                        } catch (PDOException $e) {
                                            $e->getMessage();
                                        }

                                        ?>
                                </h2>
                            </div>
                            <div class="col-md-12 hidden-print">
                                <form class="form-horizontal form-horizontal-mobiles" method="POST" action="payslip_dept.php">
                                    <div class="form-group">
                                        <label for="range" class="col-sm-3 col-md-3 col-lg-2 control-label hidden-print">Pay Period :</label>
                                        <div class="col-sm-9 col-md-9 col-lg-10">&nbsp;
                                            <div class="input-group">
                                                <span class="input-group-addon"><i class="fa fa-location-arrow hidden-print"></i></span>
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

                                    </div>
                                </form>
                                <div class="form-group">
                                    <label for="range" class="col-sm-3 col-md-3 col-lg-2 control-label hidden-print">Name :</label>
                                    <div class="col-sm-9 col-md-9 col-lg-10">&nbsp;
                                        <form action="payslip_personal.php" method="post" accept-charset="utf-8" id="add_item_form" autocomplete="off">
                                            <span role="status" aria-live="polite" class="ui-helper-hidden-accessible"></span>
                                            <input type="text" name="item" value="" id="item" class="ui-autocomplete-input" accesskey="i" placeholder="Enter Staff Name or Staff No" />
                                            <span id="ajax-loader"><img src="img/ajax-loader.gif" alt="" /></span>
                                            <input type="hidden" name="staff_id" id="staff_id" value="">
                                        </form>
                                    </div>

                                    <div class="form-actions">
                                        <button name="generate_report" type="submit" id="generate_report" class="btn btn-primary submit_button btn-large hidden-print">Submit</button>
                                    </div>

                                </div>
                            </div>



                            <?php
                            if (isset($_GET['item'])) {
                                $item = $_GET['item'];
                            } else {
                                $item = -1;
                            }
                            $query = $conn->prepare('SELECT staff_id FROM master_staff WHERE staff_id=? and period = ?');
                            $query->execute(array($item, $period));
                            $ftres = $query->fetchAll(PDO::FETCH_COLUMN);
                            $count = $query->rowCount();
                            $counter = 1;
                            //print($count . "<br />");
                            //print_r($ftres);
                            $counter = 0;
                            if ($_SESSION['emptrack'] >= $count) {
                                $_SESSION['emptrack'] = 0;
                            }
                            // $currentemp = $ftres[''.$_SESSION['emptrack'].''];
                            ?>

                            <div class="col-md-12">
                                <!-- BEGIN EXAMPLE TABLE PORTLET-->
                                <div class="portlet light bordered">

                                    <div class="portlet-body">
                                        <div class="table-toolbar hidden-print">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <button class="btn btn-sm btn-primary" type="button">
                                                        Payroll Period <span class="badge"><?php if (isset($fullPeriod)) {
                                                                                                print $fullPeriod;
                                                                                            } ?></span>
                                                    </button>
                                                    <button class="btn btn-sm purple" type="button">
                                                        Number of Employees <span class="badge"><?php print $count ?></span>
                                                    </button>
                                                    <button class="btn btn-sm red" id="btnPrint">Print <i class="fa fa-print" aria-hidden="true"></i></button>

                                                    <form id="form_payprocess" method="post">

                                                        <button class="btn btn-sm purple" type="button" id="sendmail">
                                                            Send email
                                                        </button>
                                                    </form>

                                                    <div id="loading-indicator" style="display:none;"><img src="img/ajax-loader.gif" alt="">Sending mail...</div>
                                                    <div class="form-group">



                                                        <div id="sample_1" style="display: block;">

                                                            <div id="progress" style="border:1px solid #ccc; border-radius: 5px;"></div>
                                                            <div id="information" style="width:500px"></div>
                                                            <div id="message" style="width:500px">
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="btn-group pull-right">

                                                        <!--<button class="btn blue  btn-outline dropdown-toggle" data-toggle="dropdown">Tools
                                                            <i class="fa fa-angle-down"></i>
                                                        </button>
                                                        <ul class="dropdown-menu pull-right">
                                                            <li>
                                                                <a href="javascript:;">
                                                                    <i class="fa fa-print"></i> Print </a>
                                                            </li>
                                                            <li>
                                                                <a href="javascript:;">
                                                                    <i class="fa fa-file-pdf-o"></i> Save as PDF </a>
                                                            </li>
                                                            <li>
                                                                <a href="javascript:;">
                                                                    <i class="fa fa-file-excel-o"></i> Export to Excel </a>
                                                            </li>
                                                        </ul>-->
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="btn-group pull-right">

                                                    </div>
                                                </div>
                                            </div>

                                            <!--Printer-->
                                            <script type='text/javascript'>
                                                //<![CDATA[
                                                window.onload = function() {
                                                    jQuery.fn.extend({
                                                        printElem: function() {
                                                            var cloned = this.clone();
                                                            var printSection = $('#printSection');
                                                            if (printSection.length == 0) {
                                                                printSection = $('<div id="printSection"></div>')
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

                                                    $(document).ready(function() {
                                                        $(document).on('click', '#btnPrint', function() {
                                                            $('.printMe').printElem();
                                                        });
                                                    });
                                                } //]]> 
                                            </script>
                                            <!--Printer-->
                                            <!--Printer-->
                                            <table border="1" class="wrap_trs">
                                                <tr>
                                                    <?php
                                                    while ($counter < $count) {
                                                        echo '<td>';
                                                        //Print employee payslips
                                                        $thisemployee = $ftres['' . $counter . ''];
                                                        //print_r($thisemployee);
                                                    ?>

                                                        <!-- START ROLL-->
                                                        <?php
                                                        global $conn;

                                                        try {
                                                            $query = $conn->prepare('SELECT tbl_bank.BNAME, tbl_dept.dept, master_staff.STEP, master_staff.GRADE, master_staff.staff_id, master_staff.`NAME`, master_staff.ACCTNO FROM master_staff INNER JOIN tbl_dept ON tbl_dept.dept_id = master_staff.DEPTCD INNER JOIN tbl_bank ON tbl_bank.BCODE = master_staff.BCODE WHERE staff_id = ? and period = ?');
                                                            $res = $query->execute(array($thisemployee, $period));
                                                            $out = $query->fetch();
                                                        ?>
                                                            <div class="row bottom-spacer-40">
                                                                <div class="col-md-3"></div>

                                                                <div class="col-md-6 payslip_background">

                                                                    <div id="printThis" class="printMe payslip-wrapper">
                                                                        <div class=" payslip-header">
                                                                            <div class="row header-label">
                                                                                <div class="col-md-12 txt-ctr text-uppercase"><b>
                                                                                        <?php echo $_SESSION['BUSINESSNAME']; ?>, <?php echo $_SESSION['town']; ?>
                                                                                    </b></div>
                                                                                <div class="col-md-12 txt-ctr text-uppercase">
                                                                                    <b> PAYSLIP FOR <b> <?php echo $fullPeriod; ?> </b></b>
                                                                                </div>

                                                                            </div>
                                                                            <div class="row header-label">
                                                                                <div class="col-md-6 col-xs-6">
                                                                                    <span class="pay-header-item" style="white-space:nowrap;">Name:
                                                                                        <?php
                                                                                        echo $out['NAME'];
                                                                                        ?>

                                                                                    </span>
                                                                                </div>
                                                                                <div class="col-md-6 col-xs-6 txt-left" style="white-space:nowrap;"><?php
                                                                                                                                                    //  echo $out['NAME'];
                                                                                                                                                    ?></div>
                                                                            </div>
                                                                            <div class="row header-label">
                                                                                <div class="col-md-6 col-xs-6" style="white-space:nowrap;">Staff No.: <?php print_r($thisemployee); ?>
                                                                                    <input type="hidden" name="staff_no" id="staff_no" value="<?php echo $thisemployee ?>">
                                                                                    <input type="hidden" name="period" id="period" value="<?php echo $period ?>">
                                                                                </div>

                                                                            </div>
                                                                            <div class="row header-label">

                                                                                <div class="col-md-6 col-xs-6" style="white-space:nowrap;">
                                                                                    Dept:
                                                                                    <?php
                                                                                    echo $out['dept'];
                                                                                    ?>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row header-label">
                                                                                <div class="col-md-6 col-xs-6" style="white-space:nowrap;">Bank:
                                                                                    <?php
                                                                                    echo $out['BNAME'];
                                                                                    ?>
                                                                                </div>

                                                                            </div>
                                                                            <div class="row header-label">
                                                                                <div class="col-md-6 col-xs-6" style="white-space:nowrap;">Acct No.:
                                                                                    <?php
                                                                                    echo $out['ACCTNO'];
                                                                                    ?>
                                                                                </div>

                                                                            </div>
                                                                            <div class="row header-label">

                                                                                <div class="col-md-6 col-xs-6" style="white-space:nowrap;">BASIC:
                                                                                    <?php
                                                                                    echo $out['GRADE'] . '/' . $out['STEP'];
                                                                                    ?>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    <?php
                                                                } catch (PDOException $e) {
                                                                    echo $e->getMessage();
                                                                }

                                                                    ?>

                                                                    <div class="payslip-body">
                                                                        <div class="row header-label">
                                                                            <div class="col-md-12 col-xs-12"><b>BASIC SALARY</b></div>
                                                                        </div>

                                                                        <div class="row header-label">
                                                                            <div class="col-md-6 col-xs-6" style="white-space:nowrap;">BASIC SALARY: </div>
                                                                            <div class="col-md-6 col-xs-6 txt-right">
                                                                                <?php
                                                                                $consolidated = 0;
                                                                                try {
                                                                                    $query = $conn->prepare('SELECT tbl_master.staff_id,tbl_master.allow FROM tbl_master WHERE allow_id = ? and staff_id = ? and period = ?');
                                                                                    $fin = $query->execute(array('1', $thisemployee, $period));
                                                                                    //$res = $query->fetchAll(PDO::FETCH_ASSOC);
                                                                                    $res = $query->fetch();
                                                                                    if ($query->rowCount() > 0) {
                                                                                        $consolidated = $res['allow'];
                                                                                    } else {
                                                                                        $consolidated = 0;
                                                                                    }


                                                                                    echo number_format($consolidated);
                                                                                } catch (PDOException $e) {
                                                                                    echo $e->getMessage();
                                                                                }
                                                                                ?>



                                                                            </div>

                                                                        </div>
                                                                        <div class="row header-label">
                                                                            <div class="col-md-12 col-xs-12"><b><u>ALLOWANCES</u></b></div>
                                                                        </div>
                                                                        <div class="row payslip-data">
                                                                            <?php
                                                                            $totalAllow = 0;
                                                                            try {
                                                                                $query = $conn->prepare('SELECT tbl_master.staff_id, tbl_master.allow, tbl_earning_deduction.ed FROM tbl_master INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id WHERE allow_id <> ? and staff_id = ? and period = ? and tbl_earning_deduction.type = ?');
                                                                                $fin = $query->execute(array('1', $thisemployee, $period, '1'));
                                                                                $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                                                                //print_r($res);

                                                                                foreach ($res as $row => $link) {

                                                                                    $totalAllow = $totalAllow + floatval($link['allow']);

                                                                                    echo '<div class="col-md-8 col-xs-8" style="white-space:nowrap;">' . $link['ed'];

                                                                                    echo '</div><div class="col-md-4 col-xs-4 payslip-amount">' . number_format($link['allow']) . '</div>';
                                                                                }
                                                                            } catch (PDOException $e) {
                                                                                echo $e->getMessage();
                                                                            }
                                                                            ?>
                                                                        </div>

                                                                        <div class="row payslip-total">

                                                                            <div class="col-md-8 col-xs-8"><b>Gross Salary</b></div>
                                                                            <div class="col-md-4 col-xs-4 payslip-amount"><b>
                                                                                    <?php
                                                                                    echo number_format(floatval($totalAllow) + floatval($consolidated));
                                                                                    ?>
                                                                                </b></div>
                                                                        </div>
                                                                    </div>



                                                                    <div class="payslip-body">
                                                                        <div class="row header-label">
                                                                            <div class="col-md-12 col-xs-12"><b><u>Deductions</u></b></div>
                                                                        </div>
                                                                        <div class="row payslip-data">
                                                                            <?php
                                                                            $totalDeduction = 0;
                                                                            try {
                                                                                $query = $conn->prepare('SELECT tbl_master.staff_id, tbl_master.deduc, tbl_earning_deduction.ed FROM tbl_master INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id WHERE staff_id = ? and period = ? and tbl_earning_deduction.type = ?');
                                                                                $fin = $query->execute(array($thisemployee, $period, '2'));
                                                                                $res = $query->fetchAll(PDO::FETCH_ASSOC);


                                                                                foreach ($res as $row => $link) {

                                                                                    //Get ED description
                                                                                    $totalDeduction = $totalDeduction + floatval($link['deduc']);


                                                                                    echo '<div class="col-md-8 col-xs-8" style="white-space:nowrap;">' . $link['ed'];

                                                                                    echo '</div><div class="col-md-4 col-xs-4 payslip-amount">' . number_format($link['deduc']) . '</div>';
                                                                                }
                                                                            } catch (PDOException $e) {
                                                                                echo $e->getMessage();
                                                                            }
                                                                            ?>


                                                                        </div>



                                                                        <div class="row payslip-total">
                                                                            <div class="col-md-8 col-xs-8"><b>Total Deductions</b></div>
                                                                            <div class="col-md-4 col-xs-4 payslip-amount"><b>
                                                                                    <?php
                                                                                    echo number_format($totalDeduction);
                                                                                    ?>
                                                                                </b></div>
                                                                        </div>
                                                                    </div>


                                                                    <div class="payslip-body">


                                                                        <div class="row payslip-total">
                                                                            <div class="col-md-8 col-xs-8"><b>Net Pay</b></div>
                                                                            <div class="col-md-4 col-xs-4 payslip-amount"><b>
                                                                                    <?php
                                                                                    echo number_format((floatval($totalAllow) + floatval($consolidated)) - floatval($totalDeduction));
                                                                                    ?>
                                                                                </b></div>
                                                                        </div>
                                                                    </div>

                                                                    </div>

                                                                </div>

                                                                <div class="col-md-3"></div>
                                                            </div>
                                                            <!-- END ROLL-->

                                                        <?php
                                                        $counter++;
                                                        //end employee payslips
                                                    }
                                                    echo '</td>';
                                                    echo '<p style = "page-break-after:always;"></p>';
                                                        ?>
                                                </tr>
                                            </table>
                                        </div>

                                    </div>

                                    <!-- END EXAMPLE TABLE PORTLET-->
                                </div>
                            </div>

                        </div>
                    </div>
                    <div id="register_container" class="receiving"></div>
                </div>

            </div>

            <div id="footer" class="col-md-12 hidden-print">
                Please visit our
                <a href="https://tasce.edu.ng/site/" target="_blank">
                    website </a>
                to learn the latest information about the project.
                <span class="text-info"> <span class="label label-info"> 14.1</span></span>
            </div>

        </div><!--end #content-->
        <!--end #wrapper-->


        <script type="text/javascript" language="javascript">
            $(document).ready(function() {

                $("#item").autocomplete({
                    source: '../searchStaff.php',
                    type: 'POST',
                    delay: 10,
                    autoFocus: false,
                    minLength: 1,
                    select: function(event, ui) {
                        event.preventDefault();
                        $("#item").val(ui.item.value);
                        $item = $("#item").val();
                        $period = $("#period").val();
                        $('#add_item_form').ajaxSubmit({
                            beforeSubmit: salesBeforeSubmit,
                            success: itemScannedSuccess
                        });
                        $("#staff_id").val($item);
                        $('#add_item_form').ajaxSubmit({
                            type: "POST",
                            url: "payslip_personal.php",
                            success: function(data) {
                                window.location.href = "payslip_personal.php?item=" + $item + "&period=" + $period;
                            }


                        });
                    }
                });

                $('#item').focus();
                var last_focused_id = null;
                var submitting = false;

                function salesBeforeSubmit(formData, jqForm, options) {
                    if (submitting) {
                        return false;
                    }
                    submitting = true;
                    $("#ajax-loader").show();

                }

                function itemScannedSuccess(responseText, statusText, xhr, $form) {

                    if (($('#code').val()) == 1) {
                        gritter("Error", 'Item not Found', 'gritter-item-error', false, true);

                    } else {
                        gritter("Success", "Staff No Found Successfully", 'gritter-item-success', false, true);
                        //	window.location.reload(true);
                        $("#ajax-loader").hide();

                    }
                    setTimeout(function() {
                        $('#item').focus();
                    }, 10);

                    setTimeout(function() {

                        $.gritter.removeAll();
                        return false;

                    }, 1000);

                }



                $('#item').click(function() {
                    $(this).attr('placeholder', '');
                });

                $('#sendmail').click(function() {
                    event.preventDefault();
                    const staff_no = $('#staff_no').val();
                    const period = $('#period').val();
                    const All = 0;
                    $('#sample_1').css("display", "block")
                    $('#sendmail').attr('disabled', true);

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
                                // console.log(e.target.responseText);
                            }
                        },
                        success: function(response, message) {
                            if (message == 'success') {

                                $('#sendmail').attr('disabled', false);
                                alert("Mail succesfully Processed");

                                gritter("Success", message, 'gritter-item-success', false, false);


                            } else {
                                gritter("Error", message, 'gritter-item-error', false, false);

                            }

                            $('#sendmail').attr('disabled', false);
                            $('#sample_1').css("display", "block")

                        }
                    })
                });


                //Ajax submit current location
                $("#employee_current_location_id").change(function() {
                    $("#form_set_employee_current_location_id").ajaxSubmit(function() {
                        window.location.reload(true);
                    });
                });

                document.getElementById('item').focus();

            });
        </script>
        <script src="js/tableExport.js"></script>
        <script src="js/main.js"></script>
</body>

</html>