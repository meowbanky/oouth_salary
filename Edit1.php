<!DOCTYPE html>
<!-- saved from url=(0055)http://www.optimumlinkup.com.ng/pos/index.php/customers -->
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>OOUTH Salary Manager</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <!--<base href="http://www.optimumlinkup.com.ng/pos/">-->
        <script type="text/javascript" src="http://gc.kis.v2.scr.kaspersky-labs.com/FD126C42-EBFA-4E12-B309-BB3FDD723AC1/main.js?attr=swErBLd082Ry1ZJQ6lj8007b32QAL5dHTUDGKkHx_iHBnhrVWVH-o46SAd5GgYH9tMVLZD5lYPb20EbVczhISw" charset="UTF-8"></script>
        <link rel="stylesheet" crossorigin="anonymous" href="http://gc.kis.v2.scr.kaspersky-labs.com/E3E8934C-235A-4B0E-825A-35A08381A191/abn/main.css?attr=aHR0cDovL2xvY2FsaG9zdDo4MS9QYXltYXN0ZXIvcGF5cGVyaW9kcy5waHA"/>
        <base href=".">
        <link rel="icon" href="favicon.ico" type="image/x-icon">
        <link href="css/bootstrap.min.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
        <link href="css/jquery.gritter.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
        <link href="css/jquery-ui.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
        <link href="css/unicorn.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
        <link href="css/datepicker.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
        <link href="css/bootstrap-select.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
        <link href="css/select2.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
        <link href="css/font-awesome.min.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
        <link href="css/jquery.loadmask.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
        <link href="css/token-input-facebook.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
        <link href="css/dataTables.tableTools.min.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
        <link href="css/components-md.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
        <link href="css/dataTables.tableTools.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
        <script type="text/javascript">
            var SITE_URL = "index.php";
        </script>
        <script src="js/all.js" type="text/javascript" language="javascript" charset="UTF-8"></script>
        <script src="js/select2.js" type="text/javascript" language="javascript" charset="UTF-8"></script>
        <script src="js/jquery.dataTables.min.js" type="text/javascript" language="javascript" charset="UTF-8"></script>
        <link rel="stylesheet" type="text/css" href="datatable/datatables.min.css"/>
        <script type="text/javascript" src="datatable/pdfmake.min.js"></script>
        <script type="text/javascript" src="datatable/pdfmake-0.1.36/vfs_fonts.js"></script>
        <script type="text/javascript" src="datatable/datatables.min.js"></script>
        <link href="css/custom.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
        <script type="text/javascript">
            COMMON_SUCCESS = "Success";
            COMMON_ERROR = "Error";
            $.ajaxSetup({
                cache: false,
                headers: {
                    "cache-control": "no-cache"
                }
            });

            $(document).ready(function() {
                //Ajax submit current location
                $("#employee_current_location_id").change(function() {
                    $("#form_set_employee_current_location_id").ajaxSubmit(function() {
                        window.location.reload(true);
                    });
                });
            });
        </script>
        <script>

            var isNS4 = (navigator.appName == "Netscape") ? 1 : 0;

            function auto_logout(iSessionTimeout, iSessTimeOut, sessiontimeout)
            {

                window.setTimeout('', iSessionTimeout);

                window.setTimeout('winClose()', iSessTimeOut);

            }

            function winClose() {

                //alert("Your Application session is expired.");

                if (!isNS4)
                {

                    window.navigate("index.php");

                }
                else
                {

                    window.location = "index.php";

                }

            }

            auto_logout(1440000, 1500000, 1500)
        </script>
        <style>
            @font-face {
                font-family: uc-nexus-iconfont;
                src: url(chrome-extension://pogijhnlcfmcppgimcaccdkmbedjkmhi/res/font_1471832554_080215.woff) format('woff'),url(chrome-extension://pogijhnlcfmcppgimcaccdkmbedjkmhi/res/font_1471832554_080215.ttf) format('truetype')
            }

            .modal .modal-title {
                font-weight: 400;
                color: #FFF;
                text-transform: uppercase;
                text-align: center;
                font-size: 100%;
            }

            .modal .modal-header {
                background: #6e7dc7;
            }
        </style>
    </head>
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
                            <span class="text">
                                Welcome 
                  <b>Abiodun                  </b>
                            </span>
                        </a>
                    </li>
                    <li class="btn  hidden-xs disabled">
                        <a title="" href="pos/" onclick="return false;">
                            <i class="icon fa fa-clock-o fa-2x"></i>
                            <span class="text">Sunday, September 20, 2020                  </span>
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
            <div id="sidebar" class="hidden-print minibar sales_minibar">
                <ul style="display: block;">
                    <li>
                        <a href="home.php">
                            <i class="icon fa fa-dashboard"></i>
                            <span class="hidden-minibar">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="customer.php">
                            <i class="fa fa-group"></i>
                            <span class="hidden-minibar">Customers</span>
                        </a>
                    </li>
                    <li>
                        <a href="item.php">
                            <i class="fa fa-table"></i>
                            <span class="hidden-minibar">Items</span>
                        </a>
                    </li>
                    <li>
                        <a href="supplier.php">
                            <i class="fa fa-download"></i>
                            <span class="hidden-minibar">Suppliers</span>
                        </a>
                    </li>
                    <li>
                        <a href="receiving.php">
                            <i class="fa fa-cloud-download"></i>
                            <span class="hidden-minibar">Purchase</span>
                        </a>
                    </li>
                    <li>
                        <a href="price_adjustment.php">
                            <i class="fa fa-money"></i>
                            <span class="hidden-minibar">Price Adjustment</span>
                        </a>
                    </li>
                    <li>
                        <a href="employee.php">
                            <i class="fa fa-user"></i>
                            <span class="hidden-minibar">Employees</span>
                        </a>
                    </li>
                    <li>
                        <a href="multiAdjustment.php">
                            <i class="fa fa-shopping-cart"></i>
                            <span class="hidden-minibar">Allow./Deduction Adjustment</span>
                        </a>
                    </li>
                    <li>
                        <a href="requisit.php">
                            <i class="fa fa-exchange"></i>
                            <span class="hidden-minibar">Requisit</span>
                        </a>
                    </li>
                    <li>
                        <a href="reports.php">
                            <i class="fa fa-bar-chart-o"></i>
                            <span class="hidden-minibar">Reports</span>
                        </a>
                    </li>
                    <li>
                        <a href="sales_receipt_list.php">
                            <i class="fa fa-print"></i>
                            <span class="hidden-minibar">Reprint Receipt</span>
                        </a>
                    </li>
                    <li>
                        <a href="locations.php">
                            <i class="fa fa-home"></i>
                            <span class="hidden-minibar">Locations</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php">
                            <i class="fa fa-power-off"></i>
                            <span class="hidden-minibar">Logout</span>
                        </a>
                    </li>
                </ul>
            </div>
            <div id="content" class="clearfix sales_content_minibar">
                <div id="content-header" class="hidden-print">
                    <h1>
                        <i class="icon fa fa-user"></i>
                        Company Departments
               
                    </h1>
                </div>
                <div id="breadcrumb" class="hidden-print">
                    <a href="home.php">
                        <i class="fa fa-home"></i>
                        Dashboard
               
                    </a>
                    <a class="current" href="departments.php">Company Departments</a>
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
                <!-- BEGIN PAGE TITLE-->
                <h1 class="page-title">
                    Organization - 
                            <small>Create &Manage organization's payroll periods ( Close current period before moving to next period )</small>
                </h1>
                <!-- END PAGE TITLE-->
                <!-- END PAGE HEADER-->
                <!--Begin Page Content-->
                <div class="row">
                    <div class="col-md-12">
                        <!-- BEGIN EXAMPLE TABLE PORTLET-->
                        <div class="portlet light bordered">
                            <div class="portlet-body">
                                <div class="table-toolbar">
                                    <div class="row">
                                        <div class="col-md-6"></div>
                                        <div class="col-md-6">
                                            <div class="btn-group pull-right">
                                                <a class="btn green" data-toggle="modal" data-target="#newperiod">
                                                    Add New Period <i class="fa fa-plus-square"></i>
                                                </a>
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
                                                                                <option value="January">January</option>
                                                                                <option value="February">February</option>
                                                                                <option value="March">March</option>
                                                                                <option value="April">April</option>
                                                                                <option value="May">May</option>
                                                                                <option value="June">June</option>
                                                                                <option value="July">July</option>
                                                                                <option value="August">August</option>
                                                                                <option value="September">September</option>
                                                                                <option value="October">October</option>
                                                                                <option value="November">November</option>
                                                                                <option value="December">December</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label class="col-md-4 control-label">Year</label>
                                                                        <div class="col-md-7">
                                                                            <select class="form-control" name="periodyear">
                                                                                <option value="2020">2020</option>
                                                                                <option value="2021">2021</option>
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
</form></div></div></div>
<!--End Modal-->
</div>
<table class="table table-striped table-bordered table-hover table-checkable order-column" id="sample_1">
    <thead>
        <tr>
            <th></th>
            <th>Payment Period </th>
            <th>Status </th>
            <th>Actions </th>
        </tr>
    </thead>
    <tbody>
        <!--Begin Data Table-->
        <tr class="odd gradeX">
            <td></td>
            <td>September 2020</td>
            <td>
                <span class="label label-inverse label-sm label-warning">Open </span>
            </td>
            <td>
                <button class="btn btn-zs yellow">
                    <span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
                </button>
                <!--<button disabled class="btn btn-xs red"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></button>-->
            </td>
        </tr>
        <!--View Closed Period-->
        <div id="viewperiod6" class="modal fade" tabindex="-1" data-width="560">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title">
                    <b>Re-activate Period To View Data</b>
                </h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" method="post" action="assets/classes/controller.php?act=activateclosedperiod">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-body">
                                <div class="form-group">
                                    <label class="col-md-12 txt-ctr">
                                        Please confirm you would like to reactivate this <b>CLOSED</b>
                                        period to <b>VIEW</b>
                                        data. <b>Please note you cannot transact in this period.</b>
                                        <p></p>
                                    </label>
                                </div>
                                <input type="hidden" value="6" name="reactivateperiodid">
                                <div class="form-group">
                                    <label class="col-md-4 control-label txt-right">
                                        <b>Period</b>
                                    </label>
                                    <div class="col-md-7">
                                        <input type="text" disabled class="form-control" value="September 2020">
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
</form></div>
<!--View Closed Period-->
<!--Close Period-->
<div id="closeperiod" class="modal fade" tabindex="-1" data-width="560">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
        <h4 class="modal-title">Close Current Period</h4>
    </div>
    <div class="modal-body">
        <form class="form-horizontal" method="post" action="assets/classes/controller.php?act=closeActivePeriod">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-body">
                        <div class="form-group">
                            <label class="col-md-12 txt-ctr">
                                Please confirm you would like to close the period below. Ensure you have completed all transactional changes and processing for the current month. <b>This process is irreversible.</b>
                                <p></p>
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label txt-right">
                                <b>Period</b>
                            </label>
                            <div class="col-md-7">
                                <input type="text" disabled class="form-control" value="January 2020">
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
</form></div>
<!--Close Period-->
<tr class="odd gradeX">
    <td></td>
    <td>August 2020</td>
    <td>
        <span class="label label-inverse label-sm label-warning">Open </span>
    </td>
    <td>
        <button class="btn btn-zs yellow">
            <span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
        </button>
        <!--<button disabled class="btn btn-xs red"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></button>-->
    </td>
</tr>
<!--View Closed Period-->
<div id="viewperiod5" class="modal fade" tabindex="-1" data-width="560">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
        <h4 class="modal-title">
            <b>Re-activate Period To View Data</b>
        </h4>
    </div>
    <div class="modal-body">
        <form class="form-horizontal" method="post" action="assets/classes/controller.php?act=activateclosedperiod">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-body">
                        <div class="form-group">
                            <label class="col-md-12 txt-ctr">
                                Please confirm you would like to reactivate this <b>CLOSED</b>
                                period to <b>VIEW</b>
                                data. <b>Please note you cannot transact in this period.</b>
                                <p></p>
                            </label>
                        </div>
                        <input type="hidden" value="5" name="reactivateperiodid">
                        <div class="form-group">
                            <label class="col-md-4 control-label txt-right">
                                <b>Period</b>
                            </label>
                            <div class="col-md-7">
                                <input type="text" disabled class="form-control" value="August 2020">
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
</form></div>
<!--View Closed Period-->
<!--Close Period-->
<div id="closeperiod" class="modal fade" tabindex="-1" data-width="560">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
        <h4 class="modal-title">Close Current Period</h4>
    </div>
    <div class="modal-body">
        <form class="form-horizontal" method="post" action="assets/classes/controller.php?act=closeActivePeriod">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-body">
                        <div class="form-group">
                            <label class="col-md-12 txt-ctr">
                                Please confirm you would like to close the period below. Ensure you have completed all transactional changes and processing for the current month. <b>This process is irreversible.</b>
                                <p></p>
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label txt-right">
                                <b>Period</b>
                            </label>
                            <div class="col-md-7">
                                <input type="text" disabled class="form-control" value="January 2020">
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
</form></div>
<!--Close Period-->
<tr class="odd gradeX">
    <td></td>
    <td>January 2020</td>
    <td>
        <span class="label label-inverse label-sm label-primary">Current Active </span>
    </td>
    <td>
        <!--<a href="" class="btn btn-xs yellow"><span class="glyphicon glyphicon-edit" aria-hidden="true"></span></a> <a href="" class="btn btn-xs red"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></a>-->
        <a data-toggle="modal" href="#closeperiod" class="btn btn-xs red">
            <span class="glyphicon glyphicon-ok-circle" aria-hidden="true"></span>
            Close Active Period 
        </a>
    </td>
</tr>
<!--View Closed Period-->
<div id="viewperiod4" class="modal fade" tabindex="-1" data-width="560">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
        <h4 class="modal-title">
            <b>Re-activate Period To View Data</b>
        </h4>
    </div>
    <div class="modal-body">
        <form class="form-horizontal" method="post" action="assets/classes/controller.php?act=activateclosedperiod">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-body">
                        <div class="form-group">
                            <label class="col-md-12 txt-ctr">
                                Please confirm you would like to reactivate this <b>CLOSED</b>
                                period to <b>VIEW</b>
                                data. <b>Please note you cannot transact in this period.</b>
                                <p></p>
                            </label>
                        </div>
                        <input type="hidden" value="4" name="reactivateperiodid">
                        <div class="form-group">
                            <label class="col-md-4 control-label txt-right">
                                <b>Period</b>
                            </label>
                            <div class="col-md-7">
                                <input type="text" disabled class="form-control" value="January 2020">
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
</form></div>
<!--View Closed Period-->
<!--Close Period-->
<div id="closeperiod" class="modal fade" tabindex="-1" data-width="560">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
        <h4 class="modal-title">Close Current Period</h4>
    </div>
    <div class="modal-body">
        <form class="form-horizontal" method="post" action="assets/classes/controller.php?act=closeActivePeriod">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-body">
                        <div class="form-group">
                            <label class="col-md-12 txt-ctr">
                                Please confirm you would like to close the period below. Ensure you have completed all transactional changes and processing for the current month. <b>This process is irreversible.</b>
                                <p></p>
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label txt-right">
                                <b>Period</b>
                            </label>
                            <div class="col-md-7">
                                <input type="text" disabled class="form-control" value="January 2020">
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
</form></div>
<!--Close Period-->
<tr class="odd gradeX">
    <td></td>
    <td>June 2017</td>
    <td>
        <span class="label label-inverse label-sm label-warning">Open </span>
    </td>
    <td>
        <button class="btn btn-zs yellow">
            <span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
        </button>
        <!--<button disabled class="btn btn-xs red"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></button>-->
    </td>
</tr>
<!--View Closed Period-->
<div id="viewperiod3" class="modal fade" tabindex="-1" data-width="560">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
        <h4 class="modal-title">
            <b>Re-activate Period To View Data</b>
        </h4>
    </div>
    <div class="modal-body">
        <form class="form-horizontal" method="post" action="assets/classes/controller.php?act=activateclosedperiod">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-body">
                        <div class="form-group">
                            <label class="col-md-12 txt-ctr">
                                Please confirm you would like to reactivate this <b>CLOSED</b>
                                period to <b>VIEW</b>
                                data. <b>Please note you cannot transact in this period.</b>
                                <p></p>
                            </label>
                        </div>
                        <input type="hidden" value="3" name="reactivateperiodid">
                        <div class="form-group">
                            <label class="col-md-4 control-label txt-right">
                                <b>Period</b>
                            </label>
                            <div class="col-md-7">
                                <input type="text" disabled class="form-control" value="June 2017">
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
</form></div>
<!--View Closed Period-->
<!--Close Period-->
<div id="closeperiod" class="modal fade" tabindex="-1" data-width="560">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
        <h4 class="modal-title">Close Current Period</h4>
    </div>
    <div class="modal-body">
        <form class="form-horizontal" method="post" action="assets/classes/controller.php?act=closeActivePeriod">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-body">
                        <div class="form-group">
                            <label class="col-md-12 txt-ctr">
                                Please confirm you would like to close the period below. Ensure you have completed all transactional changes and processing for the current month. <b>This process is irreversible.</b>
                                <p></p>
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label txt-right">
                                <b>Period</b>
                            </label>
                            <div class="col-md-7">
                                <input type="text" disabled class="form-control" value="January 2020">
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
</form></div>
<!--Close Period-->
<tr class="odd gradeX">
    <td></td>
    <td>May 2017</td>
    <td>
        <span class="label label-inverse label-sm label-warning">Open </span>
    </td>
    <td>
        <button class="btn btn-zs yellow">
            <span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
        </button>
        <!--<button disabled class="btn btn-xs red"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></button>-->
    </td>
</tr>
<!--View Closed Period-->
<div id="viewperiod2" class="modal fade" tabindex="-1" data-width="560">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
        <h4 class="modal-title">
            <b>Re-activate Period To View Data</b>
        </h4>
    </div>
    <div class="modal-body">
        <form class="form-horizontal" method="post" action="assets/classes/controller.php?act=activateclosedperiod">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-body">
                        <div class="form-group">
                            <label class="col-md-12 txt-ctr">
                                Please confirm you would like to reactivate this <b>CLOSED</b>
                                period to <b>VIEW</b>
                                data. <b>Please note you cannot transact in this period.</b>
                                <p></p>
                            </label>
                        </div>
                        <input type="hidden" value="2" name="reactivateperiodid">
                        <div class="form-group">
                            <label class="col-md-4 control-label txt-right">
                                <b>Period</b>
                            </label>
                            <div class="col-md-7">
                                <input type="text" disabled class="form-control" value="May 2017">
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
</form></div>
<!--View Closed Period-->
<!--Close Period-->
<div id="closeperiod" class="modal fade" tabindex="-1" data-width="560">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
        <h4 class="modal-title">Close Current Period</h4>
    </div>
    <div class="modal-body">
        <form class="form-horizontal" method="post" action="assets/classes/controller.php?act=closeActivePeriod">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-body">
                        <div class="form-group">
                            <label class="col-md-12 txt-ctr">
                                Please confirm you would like to close the period below. Ensure you have completed all transactional changes and processing for the current month. <b>This process is irreversible.</b>
                                <p></p>
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label txt-right">
                                <b>Period</b>
                            </label>
                            <div class="col-md-7">
                                <input type="text" disabled class="form-control" value="January 2020">
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
</form></div>
<!--Close Period-->
<tr class="odd gradeX">
    <td></td>
    <td>April 2017</td>
    <td>
        <span class="label label-inverse label-sm label-danger">Closed </span>
    </td>
    <td>
        <a data-toggle="modal" href="#viewperiod1" class="btn btn-xs yellow">
            <span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span>
            View Closed Period
        </a>
    </td>
</tr>
<!--View Closed Period-->
<div id="viewperiod1" class="modal fade" tabindex="-1" data-width="560">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
        <h4 class="modal-title">
            <b>Re-activate Period To View Data</b>
        </h4>
    </div>
    <div class="modal-body">
        <form class="form-horizontal" method="post" action="assets/classes/controller.php?act=activateclosedperiod">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-body">
                        <div class="form-group">
                            <label class="col-md-12 txt-ctr">
                                Please confirm you would like to reactivate this <b>CLOSED</b>
                                period to <b>VIEW</b>
                                data. <b>Please note you cannot transact in this period.</b>
                                <p></p>
                            </label>
                        </div>
                        <input type="hidden" value="1" name="reactivateperiodid">
                        <div class="form-group">
                            <label class="col-md-4 control-label txt-right">
                                <b>Period</b>
                            </label>
                            <div class="col-md-7">
                                <input type="text" disabled class="form-control" value="April 2017">
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
</form></div>
<!--View Closed Period-->
<!--Close Period-->
<div id="closeperiod" class="modal fade" tabindex="-1" data-width="560">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
        <h4 class="modal-title">Close Current Period</h4>
    </div>
    <div class="modal-body">
        <form class="form-horizontal" method="post" action="assets/classes/controller.php?act=closeActivePeriod">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-body">
                        <div class="form-group">
                            <label class="col-md-12 txt-ctr">
                                Please confirm you would like to close the period below. Ensure you have completed all transactional changes and processing for the current month. <b>This process is irreversible.</b>
                                <p></p>
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label txt-right">
                                <b>Period</b>
                            </label>
                            <div class="col-md-7">
                                <input type="text" disabled class="form-control" value="January 2020">
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
</form></div>
<!--Close Period-->
<!--End Data Table-->
</tbody></table></div></div>
<!-- END EXAMPLE TABLE PORTLET-->
</div></div><div class="clearfix"></div>
<!-- END DASHBOARD STATS 1-->
</div>
<!-- END CONTENT BODY -->
</div>
<!-- END CONTENT -->
</div>
<!-- END CONTENT -->
<div id="footer" class="col-md-12 hidden-print">
    Please visit our 
         <a href="#" target="_blank">website		</a>
    to learn the latest information about the project.
         
    <span class="text-info">
        <span class="label label-info">14.1</span>
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
</body></html>
