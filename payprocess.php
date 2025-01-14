<?php include_once('classes/model.php');
ini_set('max_execution_time', '0');
session_start();

include_once('classes/model.php');
require_once('Connections/paymaster.php');
require_once 'libs/App.php';
$App = new App();
$App->checkAuthentication();
require_once 'libs/middleware.php';
checkPermission();

if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
    header("location: index.php");
    exit();
}



?>
<!DOCTYPE html>
<?php include('header1.php'); ?>

<body data-color="grey" class="flat">
    <div class="modal fade hidden-print" id="myModal"></div>
    <div id="wrapper">
        <div id="header" class="hidden-print">
            <h1><a href="index.php"><img src="img/header_logo.png" class="hidden-print header-log" id="header-logo" alt=""></a></h1>
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

        <?php include('sidebar.php') ?>


    </div>



    <div id="content" class="clearfix sales_content_minibar">

        <div id="content-header" class="hidden-print">
            <h1><i class="fa fa-beaker"></i> Report Input</h1> <span id="ajax-loader"><img src="img/ajax-loader.gif" alt="" /></span>
        </div>

        <div id="breadcrumb" class="hidden-print">
            <a href="home.php"><i class="fa fa-home"></i> Dashboard</a><a a class="current" href="payprocess.php">Run Payroll Process</a>
        </div>
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

                    <!-- BEGIN PAGE TITLE-->
                    <h1 class="page-title"> Payroll Processing </h1>
                    <!-- END PAGE TITLE-->
                    <!-- END PAGE HEADER-->


                    <!--Begin Page Content-->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="portlet box blue">
                                <div class="portlet-title">
                                    <div class="caption">
                                        <i class="fa fa-gift"></i>Run Final Payroll Processing Sequence
                                    </div>

                                    <div class="tools">
                                        <!--<a href="javascript:;" class="reload"> </a>
                                                <a href="javascript:;" class="collapse"> </a>
                                                <a href="#portlet-config" data-toggle="modal" class="config"> </a>
                                                <a href="javascript:;" class="remove"> </a>-->
                                    </div>
                                </div>
                                <div class="portlet-body form">

                                    <div>
                                        <div class="portlet light bordered">
                                            <!--<div class="portlet-title">
                                                        <div class="caption">
                                                            <i class="icon-social-dribbble font-purple"></i>
                                                            <span class="caption-subject font-purple bold uppercase">Please Note</span>
                                                        </div>
                                                        
                                                    </div>-->
                                            <div class="portlet-body">
                                                <div class="well">
                                                    <b>Before running the final payroll sequence, please ensure all pre requisites regarding employee earnings and deductions have been fulfilled.</b>
                                                </div>
                                            </div>
                                        </div>
                                    </div>


                                    <!-- BEGIN FORM-->
                                    <form method="post" id="form_payprocess" class="form-horizontal">

                                        <div class="form-body">
                                            <div class="row">


                                                <div class="col-md-12">
                                                    <?php
                                                    $query = $conn->prepare('SELECT staff_id FROM employee WHERE STATUSCD = ?  ORDER BY staff_id ASC');
                                                    $query->execute(array('A'));
                                                    $ftres = $query->fetchAll(PDO::FETCH_ASSOC);;
                                                    $employeecount = $query->rowCount();
                                                    // print($employeecount . "<br />");
                                                    // print_r($ftres);


                                                    $counter = 0;
                                                    $missingbasic = 0;
                                                    $setbasic = 0;
                                                    $missing = array();


                                                    foreach ($ftres as $row => $link) {

                                                        // echo $ftres[$counter] . ", ";
                                                        $payrollquery = $conn->prepare('SELECT ANY_VALUE(Sum(allow_deduc.`value`)) AS  "allowance", ANY_VALUE(allow_deduc.allow_id) AS allow_id, ANY_VALUE(tbl_earning_deduction.ed) AS ed FROM allow_deduc INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = allow_deduc.allow_id  WHERE staff_id = ? and transcode = ? GROUP BY staff_id');
                                                        $payrollquery->execute(array($link['staff_id'], '01'));
                                                        $allow = $payrollquery->fetchAll(PDO::FETCH_ASSOC);
                                                        if (!$allow) {
                                                            $allowance = 0;
                                                        } else {
                                                            foreach ($allow as $row => $link1) {
                                                                $allowance = $link1['allowance'];
                                                            }
                                                        }

                                                        $payrollquery2 = $conn->prepare('SELECT any_value(Sum(allow_deduc.`value`)) as "deductions", any_value(allow_deduc.allow_id) as allow_id, any_value(tbl_earning_deduction.ed) as ed FROM allow_deduc INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = allow_deduc.allow_id  WHERE staff_id = ? and transcode = ? GROUP BY staff_id');
                                                        $payrollquery2->execute(array($link['staff_id'], '02'));
                                                        $deduc = $payrollquery2->fetchAll(PDO::FETCH_ASSOC);

                                                        if (!$deduc) {
                                                            $deduction = 0;
                                                        } else {
                                                            foreach ($deduc as $row => $link2) {

                                                                $deduction =  $link2['deductions'];
                                                            }
                                                        }

                                                        $net = $allowance - $deduction;
                                                        if ($net >= 0) {
                                                            $setbasic = $setbasic + 1;
                                                        } else {
                                                            $missingbasic = $missingbasic + 1;
                                                            $together = $link['staff_id'] . '=>' . $net;
                                                            array_push($missing, $together);
                                                        }

                                                        $counter++;
                                                    }

                                                    //print("<br />Set basic: " . $setbasic . "<br />" . "Missing Basic: " . $missingbasic);

                                                    if ($missingbasic > 0) {
                                                        print_r(implode(',', $missing));
                                                        $_SESSION['msg'] = $missingbasic . ' employees have negative net pay. Please correct this to be able to run payroll.';
                                                        $_SESSION['alertcolor'] = 'danger';
                                                        $processingerrors = true;
                                                    } else {
                                                        $processingerrors = false;
                                                    }

                                                    if (isset($_SESSION['msg'])) {
                                                        echo '<div class="alert alert-' . $_SESSION['alertcolor'] . ' alert-dismissable role="alert"> <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' . $_SESSION['msg'] . '</div>';
                                                        unset($_SESSION['msg']);
                                                        unset($_SESSION['alertcolor']);
                                                    }
                                                    ?>
                                                </div>



                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label class="col-md-4 control-label"><b>Current Active Payroll Period</b></label>
                                                        <div class="col-md-4">



                                                            <?php
                                                            /*$query = $conn->prepare('SELECT description FROM payperiods WHERE companyId = ? AND active =?');
                                                                            $query->execute([$_SESSION['companyid'], '1']);
                                                                            $ftres = $query->fetchAll(PDO::FETCH_COLUMN);
                                                                            //print_r($ftres);
                                                                            $closingperiodname = $ftres[0];*/
                                                            ?>

                                                            <input type="text" required class="form-control" id="activeperiod" name="activeperiod" value="<?php echo $_SESSION['activeperiodDescription']; ?>" disabled>

                                                        </div>
                                                    </div>

                                                    <div class="form-group">
                                                        <label class="col-md-4 control-label"><b>Progress</b></label>
                                                        <div class="col-md-4">
                                                            <div id="sample_1">
                                                                <div id="progress" style="border:1px solid #ccc; border-radius: 5px;"></div>
                                                                <div id="information" style="width:500px">
                                                                </div>
                                                            </div>

                                                        </div>
                                                    </div>


                                                </div>

                                            </div>
                                        </div>

                                        <div class="form-actions">
                                            <div class="row">
                                                <div class="col-md-12 txt-ctr">
                                                    <?php
                                                    $processingerrors = false;
                                                    if (isset($_SESSION['periodstatuschange']) && $_SESSION['periodstatuschange'] == '1') {
                                                    ?><button disabled class="btn btn-lg yellow" data-toggle="modal" data-placement="top" title="You are in a closed period. Unable to process data.">Viewing Closed Period <i class="fa fa-cog"></i></button><?php
                                                                                                                                                                                                                                                            } else {
                                                                                                                                                                                                                                                                if ($processingerrors) {
                                                                                                                                                                                                                                                                ?><button disabled class="btn btn-lg yellow" data-toggle="modal" data-placement="top" title="Processing disabled. Fix errors to be able to run full payroll.">Process Payroll <i class="fa fa-cog"></i></button><?php
                                                                                                                                                                                                                                                                                                                                                                                                                                                                            } else {
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                ?><button type="submit" id="payprocessbtn" class="btn btn-lg red">Process Payroll <i class="fa fa-cog"></i></button><?php
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                }
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            }

                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    ?>

                                                </div>
                                                <iframe id="loadarea" style="display:none"></iframe><br />
                                            </div>
                                        </div>
                                    </form>
                                    <!-- END FORM-->


                                </div>
                            </div>
                        </div>
                    </div>

                    <!--End Page Content-->

                    <div class="clearfix"></div>

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

    <!-- <script>
        $("#payprocessbtn").click(function() {
            document.getElementById('loadarea').src = 'classes/runPayroll.php';
        });
    </script> -->

    <script type="text/javascript" language="javascript">
        $(document).ready(function() {

            $('#payprocessbtn').click(function() {
                event.preventDefault();

                if (confirm('Are you sure you want to run ' + $('#activeperiod').val() + ' Payroll?')) {

                    $('#payprocessbtn').attr('disabled', true);
                    $('#payprocessbtn').html("Transaction is processing");
                    //$('#payprocessbtn').prop("disable",true);
                    $('#payprocessbtn').attr('val', 'Please wait while your transaction is Processing');
                    submitting = false;
                    $.ajax({
                        type: "GET",
                        url: 'classes/runPayroll.php',
                        xhrFields: {
                            onprogress: function(e) {
                                $('#sample_1').html(e.target.responseText);
                                console.log(e.target.responseText);
                            }
                        },
                        success: function(response, message) {
                            if (message == 'success') {
                                //if(response == 1){
                                //$('#payprocessbtn').attr('disabled',false);
                                //	alert("Payroll for the month already Processed");
                                //	gritter("Error",message,'gritter-item-error',false,false);
                                //}else{
                                $('#payprocessbtn').attr('disabled', false);
                                alert("Payroll for the month succesfully Processed");
                                //location.reload(true);
                                gritter("Success", message, 'gritter-item-success', false, false);
                                //}

                            } else {
                                gritter("Error", message, 'gritter-item-error', false, false);

                            }

                            $('#payprocessbtn').attr('disabled', false);
                            $('#payprocessbtn').html("Payroll Process");


                        }
                    })
                }



            })



            function receivingsBeforeSubmit(formData, jqForm, options) {
                var submitting = false;
                if (submitting) {
                    return false;
                }
                submitting = true;

                $("#ajax-loader").show();
                //	$("#finish_sale_button").hide();
            }

        });
    </script>
    <script src="js/tableExport.js"></script>
    <script src="js/main.js"></script>
</body>

</html>