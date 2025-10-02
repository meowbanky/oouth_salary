<?php include_once('../classes/model.php');
ini_set('max_execution_time', '0');
session_start();

//include_once('../classes/model.php');
require_once('../Connections/paymaster.php');
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
    header("location: ../index.php");
    exit();
}


?>
<!DOCTYPE html>


<body data-color="grey" class="flat">

    <div id="wrapper">

        <div id="content" class="clearfix ">

            <div class="clear"></div>
            <div class="row">
                <div class="col-md-12">
                    <div class="widget-box">
                        <div class="widget-title hidden-print">
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
                            $deductionName = '';
                            if (!isset($_POST['deduction'])) {
                                $deduction = -1;
                            } else {
                                $deduction = $_POST['deduction'];
                            }
                            try {
                                $query = $conn->prepare('SELECT tbl_earning_deduction.ed_id, tbl_earning_deduction.ed FROM tbl_earning_deduction WHERE ed_id = ?');
                                $res = $query->execute(array($deduction));
                                $out = $query->fetchAll(PDO::FETCH_ASSOC);

                                while ($row = array_shift($out)) {
                                    echo $deductionName = $row['ed'];
                                }
                            } catch (PDOException $e) {
                                $e->getMessage();
                            }

                            ?>


                            <div class="col-md-12 pull-left">
                                <p align="center"> OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL <br><?php echo $deductionName ?> Payslip Report
                                <p align="center">
                                    for the Month of:
                                    <?php
                                    global $conn;

                                    if (!isset($_GET['period'])) {
                                        $period = -1;
                                    } else {
                                        $period = $_GET['period'];
                                    }
                                    try {
                                        $query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear, payperiods.periodId FROM payperiods WHERE periodId = ?');
                                        $res = $query->execute(array($period));
                                        $out = $query->fetchAll(PDO::FETCH_ASSOC);

                                        while ($row = array_shift($out)) {
                                            echo ($row['description'] . '-' . $row['periodYear']);
                                        }
                                    } catch (PDOException $e) {
                                        $e->getMessage();
                                    }

                                    ?>
                                    </h2>
                            </div>
                            <div class="col-md-12 hidden-print">
                                <form class="form-horizontal form-horizontal-mobiles" method="GET" action="payslip_all.php">
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

                                    <div class="form-actions">
                                        <button name="generate_report" type="submit" id="generate_report" class="btn btn-primary submit_button btn-large hidden-print">Submit</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php if ($deductionName != '') { ?><div class="top-panel pull-right hidden-print">
                                <div class="btn-group">

                                    <button type="button" class="btn btn-warning btn-large dropdown-toggle" data-toggle="dropdown">Export to <span class="caret"></span></button>
                                    <ul class="dropdown-menu" role="menu">
                                        <li><a onclick="window.print();">Print</a></li>
                                        <li><a onclick="exportAll('xls','<?php echo $deductionName; ?>');" href="javascript://">XLS</a></li>
                                        <li><a onclick="exportAll('csv','<?php echo $deductionName; ?>');" href="javascript://">CSV</a></li>
                                        <li><a onclick="exportAll('txt','<?php echo $deductionName; ?>');" href="javascript://">TXT</a></li>

                                    </ul>
                                </div>
                            </div><?php } ?>


                        <?php
                        try {
                            $query = $conn->prepare('SELECT concat(payperiods.description," ",payperiods.periodYear) as period FROM payperiods WHERE periodId = ?');
                            $res = $query->execute(array($period));
                            $out = $query->fetchAll(PDO::FETCH_ASSOC);

                            while ($row = array_shift($out)) {
                                $fullPeriod = $row['period'];
                            }
                            if ($period == -1) {
                                $fullPeriod = '';
                            }
                        } catch (PDOException $e) {
                            echo $e->getMessage();
                        }

                        $results_per_page = 100;
                        if (isset($_GET['page'])) {
                            $page = $_GET['page'];
                        } else {
                            $page = 1;
                        }
                        $start_from = ($page - 1) * $results_per_page;
                        $query = $conn->prepare('SELECT staff_id FROM master_staff WHERE statuscd = ? and period = ? ORDER BY DEPTCD ASC LIMIT ' . $start_from . ',' . $results_per_page);
                        $query->execute(array('A', $period));
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
                                            <div class="col-md-3">
                                                <button class="btn btn-sm btn-primary" type="button">
                                                    Payroll Period <span class="badge"><?php print $fullPeriod ?></span>
                                                </button>
                                            </div>
                                            <div class="col-md-3">
                                                <button class="btn btn-sm purple" type="button">
                                                    Number of Employees <span class="badge"><?php print $count ?></span>
                                                </button>
                                            </div>
                                            <div class="col-md-3">
                                                <button class="btn btn-sm purple" type="button">
                                                    Page <span class="badge"><?php print $page ?></span>
                                                </button>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="btn-group pull-right">
                                                    <button class="btn btn-sm red" id="btnPrint">Print <i class="fa fa-print" aria-hidden="true"></i></button>
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

                                        </div>
                                        <div class="row">
                                            <div class="col">
                                                <nav aria-label="page navigation example" class="hidden-print">
                                                    <ul class="pagination">

                                                        <?php
                                                        $sql = 'SELECT count(staff_id) as "Total" FROM master_staff WHERE statuscd = "A"';
                                                        $result = $conn->query($sql);
                                                        $row = $result->fetch();
                                                        $total_pages = ceil($row['Total'] / $results_per_page);
                                                        for ($i = 1; $i <= $total_pages; $i++) {
                                                            //echo "<a href='payslip_all.php?page=".$i."'";
                                                            //	if($i ==$page){echo " class='curPage'";}
                                                            //	echo "> ".$i." </a>";
                                                            echo '<li class="page-item ';
                                                            if ($i == $page) {
                                                                echo ' active"';
                                                            };
                                                            echo '"><a class="page-link" href="payslip_all.php?page=' . $i . '&period=' . $period . '">' . $i . '</a></li>';
                                                        }
                                                        ?>
                                                    </ul>
                                                </nav>
                                            </div>
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
                                            <div class="row margin-right-5 bottom-spacer-15">
                                                <!-- <div class="col-md-3"></div> -->

                                                <div class="col-md-6">

                                                    <div id="printThis" class="printMe payslip-wrapper">
                                                        <div class="payslip-header">
                                                            <div class="row header-label">
                                                                <div class="col-md-12 txt-ctr text-uppercase"><b>
                                                                        OOUTH, SAGAMU
                                                                    </b>
                                                                </div>
                                                                <div class="col-md-12 txt-ctr text-uppercase">
                                                                    <b> PAYSLIP FOR <b> <?php print $fullPeriod ?> </b></b>
                                                                </div>

                                                            </div>

                                                            <?php
                                                            global $conn;

                                                            try {
                                                                $query = $conn->prepare('SELECT tbl_bank.BNAME, tbl_dept.dept, master_staff.STEP, master_staff.GRADE, master_staff.staff_id, master_staff.`NAME`, master_staff.ACCTNO FROM master_staff INNER JOIN tbl_dept ON tbl_dept.dept_id = master_staff.DEPTCD INNER JOIN tbl_bank ON tbl_bank.BCODE = master_staff.BCODE WHERE staff_id = ? and period = ?');
                                                                $res = $query->execute(array($thisemployee, $period));
                                                                $out = $query->fetch();

                                                            ?>
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
                                                                    <div class="col-md-6 col-xs-6" style="white-space:nowrap;">Staff No.: <?php print_r($thisemployee); ?> </div>

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

                                                                    <div class="col-md-6 col-xs-6" style="white-space:nowrap;">CONSOLIDATED:
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
                                                            <div class="col-md-12 col-xs-12"><b>CONSOLIDATED SALARY</b></div>
                                                        </div>

                                                        <div class="row header-label">
                                                            <div class="col-md-6 col-xs-6" style="white-space:nowrap;">CONSOLIDATED SALARY: </div>
                                                            <div class="col-md-6 col-xs-6 txt-right">
                                                                <?php
                                                                $consolidated = 0;
                                                                try {
                                                                    $query = $conn->prepare('SELECT tbl_master.staff_id,tbl_master.allow FROM tbl_master WHERE allow_id = ? and staff_id = ? and period = ?');
                                                                    $fin = $query->execute(array('1', $thisemployee, $period));
                                                                    //$res = $query->fetchAll(PDO::FETCH_ASSOC);
                                                                    $res = $query->fetch();
                                                                    //print_r($res);
                                                                    $consolidated = $res['allow'];



                                                                    echo number_format($res['allow']);
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
                                                                $query = $conn->prepare('SELECT tbl_master.staff_id, tbl_master.allow, tbl_earning_deduction.ed FROM tbl_master INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id WHERE allow_id <> ? and staff_id = ? and period = ? and type = ?');
                                                                $fin = $query->execute(array('1', $thisemployee, $period, '1'));
                                                                $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                                                //print_r($res);

                                                                foreach ($res as $row => $link) {

                                                                    $totalAllow = $totalAllow + floatval($link['allow']);

                                                                    echo '<div class="col-md-8 col-xs-8">' . $link['ed'];

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
                                                                $query = $conn->prepare('SELECT tbl_master.staff_id, tbl_master.deduc, tbl_earning_deduction.ed FROM tbl_master INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id WHERE staff_id = ? and period = ? and type = ?');
                                                                $fin = $query->execute(array($thisemployee, $period, '2'));
                                                                $res = $query->fetchAll(PDO::FETCH_ASSOC);


                                                                foreach ($res as $row => $link) {

                                                                    //Get ED description
                                                                    $totalDeduction = $totalDeduction + floatval($link['deduc']);


                                                                    echo '<div class="col-md-8 col-xs-8">' . $link['ed'];

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
                        <nav aria-label="page navigation example" class="hidden-print">
                            <ul class="pagination">

                                <?php
                                $sql = 'SELECT count(staff_id) as "Total" FROM master_staff WHERE statuscd = "A"';
                                $result = $conn->query($sql);
                                $row = $result->fetch();
                                $total_pages = ceil($row['Total'] / $results_per_page);
                                for ($i = 1; $i <= $total_pages; $i++) {
                                    //echo "<a href='payslip_all.php?page=".$i."'";
                                    //	if($i ==$page){echo " class='curPage'";}
                                    //	echo "> ".$i." </a>";
                                    echo '<li class="page-item ';
                                    if ($i == $page) {
                                        echo ' active"';
                                    };
                                    echo '"><a class="page-link" href="payslip_all.php?page=' . $i . '&period=' . $period . '">' . $i . '</a></li>';
                                }
                                ?>
                            </ul>
                        </nav>
                    </div>
                </div>

            </div>
        </div>
        <div id="register_container" class="receiving"> </div>
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
    <!--end #wrapper-->


    <script type="text/javascript" language="javascript">
        $(document).ready(function() {
            //'sales_report.php');
            $('table.wrap_trs tr').unwrap();
            var cells = $('table.wrap_trs tr td');
            for (var i = 0; i < cells.length; i += 3) {
                cells.slice(i, i + 3).wrapAll('<tr></tr>');
            }

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
            //	$("#finish_sale_button").hide();
        }
    </script>
    <script src="js/tableExport.js"></script>
    <script src="js/main.js"></script>
</body>

</html>