<?php require_once('Connections/paymaster.php');
include_once('classes/model.php'); ?>
<?php
//Start session
session_start();

//Check whether the session variable SESS_MEMBER_ID is present or not
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '') || $_SESSION['role'] != 'Admin') {
    header("location: index.php");
    exit();
}





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
                    <i class="icon fa fa-upload"></i>
                    Retore Database
                </h1>
            </div>
            <div id="breadcrumb" class="hidden-print">
                <a href="home.php">
                    <i class="fa fa-home"></i> Dashboard
                </a>
                <a class="current" href="retore.php">Restore</a>
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


            <!-- BEGIN PAGE TITLE-->
            <div class="container">
                <h1 class="page-title"> Restore Database

                </h1>
            </div>

            <!-- END PAGE TITLE-->
            <!-- END PAGE HEADER-->


            <!--Begin Page Content-->

            <div class="row">
                <div class="col-md-12">
                    <!-- BEGIN EXAMPLE TABLE PORTLET-->
                    <div class="portlet light bordered">

                        <div class="portlet-body">
                            <form class="form-horizontal" method="post" action="restore_logic.php" id="restore_database" name="restore_database" enctype="multipart/form-data">

                                <div class="table-toolbar">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-body">
                                                <div class="form-group">
                                                    <label class="col-md-4 control-label">Restore Database</label>
                                                    <div class="col-md-7">
                                                        <input type="file" name="file" id="file" class="input-large">
                                                    </div>
                                                </div>

                                            </div>
                                        </div>


                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn red" id="restore">Restore</button>
                                </div>
                                <input type="hidden" value="import">
                            </form>


                        </div>

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
            $("#restore").click(function(event) {
                event.preventDefault();

                // Create a FormData object from the form element
                var formData = new FormData($("#restore_database")[0]);

                // Disable the button while the request is in progress
                $('#restore').attr('disabled', true);

                // Make an AJAX request with the FormData
                $.ajax({
                    url: 'restore_logic.php',
                    type: 'POST',
                    data: formData,
                    processData: false, // Important to prevent jQuery from processing data
                    contentType: false, // Important for FormData
                    success: function(response, message) {
                        if (message == 'success') {
                            // Handle success
                            $('#restore').attr('disabled', false);
                            gritter("Success", response, 'gritter-item-success', false, false);
                            $("#restore_database")[0].reset();
                            // Reset the form or perform other actions as needed
                        } else {
                            // Handle error
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