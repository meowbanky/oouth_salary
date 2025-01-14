<?php require_once('Connections/paymaster.php');
include_once('classes/model.php');
require_once 'libs/App.php';
$App = new App();
$App->checkAuthentication();
require_once 'libs/middleware.php';
checkPermission();
?>
<?php
//Start session
session_start();

//Check whether the session variable SESS_MEMBER_ID is present or not
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '') || $_SESSION['role'] != 'Admin') {
    header("location: index.php");
    exit();
}


$currentYear = date('Y');
$currentMonth = date('n'); // Month without leading zeros

// Months array
$months = array(
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
);

?>
<!DOCTYPE html>

<html>
<?php include('header1.php'); ?>

<body data-color="grey" class="flat" style="zoom: 1;">
<div class="modal fade hidden-print" id="myModal"></div>
<div id="wrapper">
    <div id="header" class="hidden-print">
        <h1>
            <a href="index.php">
                <img src="img/header_logo.png" class="hidden-print header-log" id="header-logo" alt="">
            </a>
        </h1>
        <a id="menu-trigger" href="#">
            <i class="fa fa-bars fa fa-2x"></i>
        </a>
        <div class="clear"></div>
    </div>
    <div id="user-nav" class="hidden-print hidden-xs">
        <ul class="btn-group ">
            <li class="btn  hidden-xs">
                <a title="" href="switch_user" data-toggle="modal" data-target="#myModal">
                    <i class="icon fa fa-user fa-2x"></i>
                    <span class="text"> Welcome
                            <b>
                                <?php echo $_SESSION['SESS_FIRST_NAME']; ?>
                            </b>
                        </span>
                </a>
            </li>
            <li class="btn  hidden-xs disabled">
                <a title="" href="pos/" onclick="return false;">
                    <i class="icon fa fa-clock-o fa-2x"></i>
                    <span class="text">
                            <?php
                            $Today = date('y:m:d', time());
                            $new = date('l, F d, Y', strtotime($Today));
                            echo $new;
                            ?>
                        </span>
                </a>
            </li>
            <li class="btn ">
                <a href="#">
                    <i class="icon fa fa-cog"></i>
                    <span class="text">Settings</span>
                </a>
            </li>
            <li class="btn  ">
                <a href="index.php">
                    <i class="fa fa-power-off"></i>
                    <span class="text">Logout</span>
                </a>
            </li>
        </ul>
    </div>
    <?php include('sidebar.php'); ?>
    <div id="content" class="clearfix sales_content_minibar">

        <div id="content-header" class="hidden-print">
            <h1>
                <i class="icon fa fa-calendar"></i>
                Pay Period
            </h1>
        </div>
        <div id="breadcrumb" class="hidden-print">
            <a href="home.php">
                <i class="fa fa-home"></i> Dashboard
            </a>
            <a class="current" href="payperiods.php">Pay Period</a>
        </div>
        <div class="clear"></div>
        <div id="datatable_wrapper"></div>
        <div class=" pull-right">
            <div class="row">
                <div id="datatable_wrapper"></div>
                <div class="col-md-12 center" style="text-align: center;">
                    <div class="btn-group  "></div>
                </div>
            </div>
        </div>
        <div class="row"></div>
        <?php
        if (isset($_SESSION['msg'])) {
            echo '<div class="alert alert-' . $_SESSION['alertcolor'] . ' alert-dismissable role="alert"> <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' . $_SESSION['msg'] . '</div>';
            unset($_SESSION['msg']);
            unset($_SESSION['alertcolor']);
        }
        ?>



        <!--Begin Page Content-->

        <div class="row">

            <div class="col-md-12">
                <h1 class="page-title"> Organization -
                    <small>Create & Manage organization's payroll periods ( Close current period before moving to next period )</small>
                </h1>
                <!-- BEGIN EXAMPLE TABLE PORTLET-->
                <div class="portlet light bordered">

                    <div class="portlet-body">
                        <div class="table-toolbar">
                            <div class="row">

                                <div class="col-md-6"></div>

                                <div class="col-md-6">
                                    <div class="btn-group pull-right">

                                        <button type="button" class="btn green" data-toggle="modal" data-target="#newperiod"> Add New Period <i class="fa fa-plus-square"></i></button>
                                    </div>
                                </div>

                            </div>


                            <!-- Start Modal -->

                            <div id="newperiod" class="modal fade" tabindex="-1" data-width="560">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header" style="background: #6e7dc7;">
                                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                                            <h4 class="modal-title">Add New Payment Period</h4>
                                        </div>
                                        <div class="modal-body">
                                            <form class="form-horizontal" method="post" action="classes/controller.php?act=addperiod">
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="form-body">
                                                            <div class="form-group">
                                                                <label class="col-md-4 control-label">Description</label>
                                                                <div class="col-md-7">
                                                                    <select class="form-control" name="perioddesc">
                                                                        <?php for ($monthNumber = $currentMonth; $monthNumber <= 12; $monthNumber++) {
                                                                            $currentMonthIndex = $monthNumber - 1; // Adjusting to zero-based index
                                                                            $nextMonthIndex = ($currentMonthIndex + 1) % 12; // Calculate index of next month, wrapping around
                                                                            $monthName = $months[$currentMonthIndex]; // Current month name
                                                                            $nextMonthName = $months[$nextMonthIndex]; // Next month name

                                                                            ?>
                                                                            <option value="<?php echo $monthName ?>"><?php echo $monthName ?></option>
                                                                            <option value="<?php echo $nextMonthName ?>"><?php echo $nextMonthName ?></option>
                                                                        <?php }
                                                                        ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="col-md-4 control-label">Year</label>
                                                                <div class="col-md-7">
                                                                    <select class="form-control" name="periodyear">
                                                                        <option value="<?php echo date('Y') ?>"><?php echo date('Y') ?></option>
                                                                        <option value="<?php echo date('Y') + 1 ?>"><?php echo date('Y') + 1 ?></option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" data-dismiss="modal" class="btn btn-outline dark">Cancel</button>
                                            <button type="submit" class="btn red">Create Period</button>
                                        </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!--End Modal-->



                        </div>
                        <table class="table table-striped table-bordered table-hover table-checkable order-column" id="sample_1">
                            <thead>
                            <tr>
                                <th> </th>
                                <th> Payment Period </th>
                                <th> Status </th>
                                <th> Actions </th>
                            </tr>
                            </thead>
                            <tbody>

                            <!--Begin Data Table-->
                            <?php
                            try {
                            $query = $conn->prepare('SELECT * FROM payperiods ORDER BY periodId DESC');
                            $fin = $query->execute();
                            $res = $query->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($res as $row => $link) {
                            $thisperiod = $link['periodId'];
                            ?><tr class="odd gradeX">
                                <td></td><?php echo '<td>' . $link['description'] . " " . $link['periodYear'] . '</td>';

                                if ($link['active'] == 0) {
                                    echo '<td> <span class="label label-inverse label-sm label-warning">Open </span> </td>';
                                } elseif ($link['active'] == 1) {
                                    echo '<td> <span class="label label-inverse label-sm label-primary"> Current Active </span> </td>';
                                } elseif ($link['active'] == 2) {
                                    echo '<td> <span class="label label-inverse label-sm label-danger"> Closed </span> </td>';
                                }

                                echo '<td>';
                                if ($link['active'] == 1) {
                                    echo '<!--<a href="" class="btn btn-xs yellow"><span class="glyphicon glyphicon-edit" aria-hidden="true"></span></a> <a href="" class="btn btn-xs red"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></a>--> 
                                                                            <a data-toggle="modal" href="#viewperiod' . $thisperiod . '" class="btn btn-xs red"><span class="glyphicon glyphicon-ok-circle" aria-hidden="true"></span> Close Active Period </a>';
                                } else {
                                    if ($link['active'] == 2) {
                                        echo '<a data-toggle="modal" href="#viewperiod' . $thisperiod . '" class="btn btn-xs yellow"><span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span> View Closed Period</a> ';
                                    } else {
                                        echo '<button class="btn btn-zs yellow"><span class="glyphicon glyphicon-edit" aria-hidden="true"></span></button> <!--<button disabled class="btn btn-xs red"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></button>-->';
                                    }
                                }
                                echo '</td></tr>';
                                ?>

                                <!--View Closed Period-->
                                <div id="viewperiod<?php echo $thisperiod; ?>" class="modal fade" tabindex="-1" data-width="560">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                                        <h4 class="modal-title"><b>Re-activate Period To View Data</b></h4>
                                    </div>
                                    <div class="modal-body">
                                        <form class="form-horizontal" method="post" action="classes/controller.php?act=activateclosedperiod">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="form-body">
                                                        <div class="form-group">
                                                            <label class="col-md-12 txt-ctr">Please confirm you would like to reactivate this <b>CLOSED</b> period to <b>VIEW</b> data. <b>Please note you cannot transact in this period.</b>
                                                                <p></p>
                                                            </label>
                                                        </div>
                                                        <input type="hidden" value="<?php echo $thisperiod; ?>" name="reactivateperiodid">
                                                        <div class="form-group">
                                                            <label class="col-md-4 control-label txt-right"><b>Period</b></label>
                                                            <div class="col-md-7">
                                                                <input type="text" disabled class="form-control" value="<?php
                                                                retrieveDescSingleFilter('payperiods', 'description', 'periodId', $thisperiod);
                                                                echo " ";
                                                                retrieveDescSingleFilter('payperiods', 'periodYear', 'periodId', $thisperiod);
                                                                ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" data-dismiss="modal" class="btn btn-outline dark">Cancel</button>
                                        <button type="submit" class="btn red">Reactivate Period</button>
                                    </div>
                                    </form>
                                </div>
                                <!--View Closed Period-->



                                <!--Close Period-->
                                <div id="closeperiod" class="modal fade" tabindex="-1" data-width="560">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                                        <h4 class="modal-title">Close Current Period</h4>
                                    </div>
                                    <div class="modal-body">
                                        <form class="form-horizontal" method="post" action="classes/controller.php?act=closeActivePeriod">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="form-body">
                                                        <div class="form-group">
                                                            <label class="col-md-12 txt-ctr">Please confirm you would like to close the period below. Ensure you have completed all transactional changes and processing for the current month. <b>This process is irreversible.</b>
                                                                <p></p>
                                                            </label>
                                                        </div>
                                                        <div class="form-group">
                                                            <label class="col-md-4 control-label txt-right"><b>Period</b></label>
                                                            <div class="col-md-7">
                                                                <input type="text" disabled class="form-control" value="<?php
                                                                retrieveDescSingleFilter('payperiods', 'description', 'periodId', $_SESSION['currentactiveperiod']);
                                                                echo " ";
                                                                retrieveDescSingleFilter('payperiods', 'periodYear', 'periodId', $_SESSION['currentactiveperiod']);
                                                                ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" data-dismiss="modal" class="btn btn-outline dark">Cancel</button>
                                        <button type="submit" class="btn red">Close Period</button>
                                    </div>
                                    </form>
                                </div>
                                <!--Close Period-->

                                <?php
                                }
                                } catch (PDOException $e) {
                                    echo $e->getMessage();
                                }
                                ?>
                                <!--End Data Table-->

                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- END EXAMPLE TABLE PORTLET-->
            </div>
        </div>

        <div class="clearfix"></div>
        <!-- END DASHBOARD STATS 1-->



    </div>
    <!-- END CONTENT BODY -->
</div>
<!-- END CONTENT -->

</div>
<!-- END CONTENT -->



<div id="footer" class="col-md-12 hidden-print">
    Please visit our
    <a href="#" target="_blank">
        website </a>
    to learn the latest information about the project.
    <span class="text-info">
            <span class="label label-info"> 14.1</span>
        </span>
</div>

<script src="js/tableExport.js"></script>
<script src="js/main.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        //$("#ajax-loader").show();
        //$("#pickEmployee").select2();
        //$("#newdeductioncodeunion").select2();
        //$("Input[type=Select]").select2();
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
                window.location.reload(true);
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

        $("#item").autocomplete({
            source: 'searchStaff.php',
            type: 'POST',
            delay: 10,
            autoFocus: false,
            minLength: 1,
            select: function(event, ui) {
                event.preventDefault();
                $("#item").val(ui.item.value);
                $('#add_item_form').ajaxSubmit({
                    beforeSubmit: salesBeforeSubmit,
                    success: itemScannedSuccess
                });

            }
        });

        $('#item').click(function() {
            $(this).attr('placeholder', '');
        });

        $("#no_times_repayment").blur(function() {
            // alert(parseFloat($("#principal").val().trim()));
            var monthlyPayment = ((parseFloat($("#Principal").val()) + parseFloat($("#interest").val())) / parseFloat($("#no_times_repayment").val()));

            $("#monthlyRepayment").val(monthlyPayment);
        });

        $("#monthlyRepayment").blur(function() {
            // alert(parseFloat($("#principal").val().trim()));
            var monthlyPayment = ((parseFloat($("#Principal").val()) + parseFloat($("#interest").val())) / parseFloat($(this).val()));

            $("#no_times_repayment").val(monthlyPayment);
        });


        //Ajax submit current location

        $("#addearningsButton").click(function() {

            $("#form_newearningcode").ajaxSubmit({
                url: 'classes/controller.php?act=addemployeeearning',
                success: function(response, message) {

                    $("#form_newearningcode").unmask();
                    submitting = false;

                    if (message == 'success') {
                        $("#reloadtable").load(location.href + " #reloadtable");

                    } else {
                        gritter("Error", message, 'gritter-item-error', false, false);

                    }


                }
            });

        })


        $("#addDeductionButtonUnion").click(function() {

            $("#form_newedeductioncodeunion").ajaxSubmit({
                url: 'classes/controller.php?act=addemployeedeductionunion',
                success: function(response, message) {

                    $("#form_newedeductioncode").unmask();
                    submitting = false;

                    if (message == 'success') {

                        $("#reloadtable").load(location.href + " #reloadtable");


                    } else {
                        gritter("Error", message, 'gritter-item-error', false, false);

                    }


                }
            });

        })

        $("#addDeductionButton").click(function() {

            $("#form_newedeductioncode").ajaxSubmit({
                url: 'classes/controller.php?act=addemployeededuction',
                success: function(response, message) {

                    $("#form_newedeductioncode").unmask();
                    submitting = false;

                    if (message == 'success') {

                        $("#reloadtable").load(location.href + " #reloadtable");


                    } else {
                        gritter("Error", message, 'gritter-item-error', false, false);

                    }


                }
            });

        })

        $("#addLoanButton").click(function() {

            $("#form_newloanemployeededuction").ajaxSubmit({
                url: 'classes/controller.php?act=loan_corporate',
                success: function(response, message) {

                    $("#form_newedeductioncode").unmask();
                    submitting = false;

                    if (message == 'success') {
                        $("#reloadtable").load(location.href + " #reloadtable");


                    } else {
                        gritter("Error", message, 'gritter-item-error', false, false);

                    }


                }
            });

        })

        $(".btn btn-outline dark").click(function() {

            alert('ok');
            location.reload(true);


        });

        $("#newdeductioncode").change(function() {
            var $option = $(this).find('option:selected');
            var $value = $option.val();

            if ($value == 41) {

                $("#form_newedeductioncode").ajaxSubmit({
                    url: 'classes/getPensionValue.php',
                    success: function(response, message) {

                        $("#form").unmask();
                        submitting = false;

                        if (message == 'success') {
                            if ($.trim(response) == 'manual') {

                                $("#deductionamount").val('');
                                $("#deductionamount").attr('readonly', false);

                            } else {
                                $("#deductionamount").val(response);
                                $("#deductionamount").attr('readonly', true);
                            }
                        } else {
                            gritter("Error", message, 'gritter-item-error', false, false);

                        }


                    }
                });
            } else {
                $("#deductionamount").val('');
                $("#deductionamount").attr('readonly', false);
            }
        });

        $("#newdeductioncodeloan").change(function() {
            $("#form_newloanemployeededuction").ajaxSubmit({
                url: 'classes/getLoanBalance.php',
                success: function(response, message) {

                    $("#form").unmask();
                    submitting = false;

                    if (message == 'success') {
                        if (response > 0) {
                            $("#addLoanButton").attr('disabled', true);
                            $("#Balance").val(response);
                        } else {
                            $("#addLoanButton").attr('disabled', false);
                            $("#Balance").val(response);
                        }
                    } else {
                        gritter("Error", message, 'gritter-item-error', false, false);

                    }


                }
            });

        });

        $("#newearningcode").change(function() {

            $("#form_newearningcode").ajaxSubmit({
                url: 'classes/getSalaryValue.php',
                success: function(response, message) {

                    $("#form").unmask();
                    submitting = false;

                    if (message == 'success') {
                        if ($.trim(response) == 'manual') {

                            $("#earningamount").val('');
                            $("#earningamount").attr('readonly', false);

                        } else {
                            $("#earningamount").val(response);
                            $("#earningamount").attr('readonly', true);
                        }
                    } else {
                        gritter("Error", message, 'gritter-item-error', false, false);

                    }


                }
            });
        });

        $("#newdeductioncodeunion").change(function() {

            $("#form_newedeductioncodeunion").ajaxSubmit({
                url: 'classes/getUnionValue.php',
                success: function(response, message) {

                    $("#form").unmask();
                    submitting = false;

                    if (message == 'success') {
                        if ($.trim(response) == 'manual') {
                            $("#deductionamountunion").val('');
                            $("#deductionamountunion").attr('readonly', false);

                        } else {

                            $("#deductionamountunion").val(response);
                            $("#deductionamountunion").attr('readonly', true);

                        }
                    } else {
                        gritter("Error", message, 'gritter-item-error', false, false);

                    }


                }
            });



        });

    });
</script>
</div>
<!--end #content-->
</div>
<!--end #wrapper-->
<ul class="ui-autocomplete ui-front ui-menu ui-widget ui-widget-content ui-corner-all" id="ui-id-1" tabindex="0" style="display: none;"></ul>
</body>

</html>