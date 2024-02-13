<?php ini_set('max_execution_time', '300');
require_once('Connections/paymaster.php');
include_once('classes/model.php'); ?>
<?php

//Start session
session_start();

//Check whether the session variable SESS_MEMBER_ID is present or not
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '') || $_SESSION['role'] != 'Admin') {
    header("location: index.php");
    exit();
}

if (!function_exists("GetSQLValueString")) {
    function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "")
    {

        global $con;
        $theValue = function_exists("mysql_real_escape_string") ? mysqli_real_escape_string($con, $theValue) : mysqli_escape_string($con, $theValue);

        switch ($theType) {
            case "text":
                $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
                break;
            case "long":
            case "int":
                $theValue = ($theValue != "") ? intval($theValue) : "NULL";
                break;
            case "double":
                $theValue = ($theValue != "") ? doubleval($theValue) : "NULL";
                break;
            case "date":
                $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
                break;
            case "defined":
                $theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue;
                break;
        }
        return $theValue;
    }
}

$currentPage = $_SERVER["PHP_SELF"];






$today = '';
$today = date('Y-m-d');
?>
<!DOCTYPE html>

<html>
<?php include('header1.php'); ?>

<body data-color="grey" class="flat" style="zoom: 1;">
    <div class="modal fade hidden-print" id="myModal"></div>
    <div id="wrapper">
        <div id="header" class="hidden-print">
            <h1><a href="index.php"><img src="img/header_logo.png" class="hidden-print header-log" id="header-logo" alt=""></a></h1>
            <a id="menu-trigger" href="#"><i class="fa fa-bars fa fa-2x"></i></a>
            <div class="clear"></div>
        </div>

        <?php include('header.php'); ?>


        <?php include('sidebar.php'); ?>



        <div id="content" class="clearfix sales_content_minibar">

            <script type="text/javascript">
                $(document).ready(function() {


                });
            </script>
            <div id="content-header" class="hidden-print">
                <h1> <i class="icon fa fa-user"></i>
                    Users</h1>


            </div>


            <div id="breadcrumb" class="hidden-print">
                <a href="home.php"><i class="fa fa-home"></i> Dashboard</a><a class="current" href="users.php">Users Manager</a>
            </div>
            <div class="clear"></div>
            <div id="datatable_wrapper"></div>
            <div class=" pull-right">
                <div class="row">
                    <div id="datatable_wrapper"></div>
                    <div class="col-md-12 center" style="text-align: center;">
                        <?php
                        if (isset($_SESSION['msg'])) {
                            echo '<div class="alert alert-' . $_SESSION['alertcolor'] . ' alert-dismissable role="alert"> <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' . $_SESSION['msg'] . '</div>';
                            unset($_SESSION['msg']);
                            unset($_SESSION['alertcolor']);
                        }
                        ?>
                        <?php
                        if (isset($_SESSION['msg'])) {
                            echo '<div class="alert alert-' . $_SESSION['alertcolor'] . ' alert-dismissable role="alert"> <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' . $_SESSION['msg'] . '</div>';
                            unset($_SESSION['msg']);
                            unset($_SESSION['alertcolor']);
                        }
                        ?>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="btn-group pull-right">
                                    <button type="button" data-target="#responsive" class="btn red" data-toggle="modal"> Add User <i class="fa fa-plus"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row ">
                <form action="#" method="post" accept-charset="utf-8" id="add_item_form" autocomplete="off">
                    <span role="status" aria-live="polite" class="ui-helper-hidden-accessible"></span>
                    <input type="text" name="item" value="" id="item" class="ui-autocomplete-input" accesskey="i" placeholder="Enter Staff Name or Staff No" />
                    <span id="ajax-loader"><img src="img/ajax-loader.gif" alt="" /></span>
                </form>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="widget-box">
                        <div class="widget-title">
                            <span class="icon">
                                <i class="fa fa-th"></i>
                            </span>
                            <h5>List of Users</h5>
                            <span title="" class="label label-info tip-left" data-original-title="total users">Total Users<?php echo '100' ?></span>

                        </div>
                        <!--endbegiing of employee details-->
                        <div id="datatable_wrapper">

                            <div class="row top-spacer-20">

                                <div class="col-md-12">

                                    <div class="container">
                                        <nav aria-label="page navigation example" class="hidden-print">
                                            <ul class="pagination">

                                                <?php
                                                $results_per_page = 100;
                                                if (isset($_GET['page'])) {
                                                    $page = $_GET['page'];
                                                } else {
                                                    $page = 1;
                                                }
                                                $results_per_page = 100;
                                                if (!isset($_GET['item'])) {
                                                    $sql = 'SELECT count(staff_id) as "Total" FROM username';
                                                } else {
                                                    $sql = 'SELECT count(staff_id) as "Total" FROM username where staff_id = "' . $_GET['item'] . '"';
                                                }

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
                                                    echo '"><a class="page-link" href="users.php?page=' . $i . '">' . $i . '</a></li>';
                                                }
                                                ?>
                                            </ul>
                                        </nav>
                                    </div>

                                    <table class="table table-striped table-bordered table-hover table-checkable order-column tblbtn" id="sample_1">
                                        <thead>
                                            <tr>
                                                <th> </th>
                                                <th> User Name </th>
                                                <th> Name </th>
                                                <th> User Type </th>
                                                <th> Actions </th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                            <!--Begin Data Table-->
                                            <?php
                                            //retrieveData('employment_types', 'id', '2', '1');
                                            $results_per_page = 100;
                                            if (isset($_GET['page'])) {
                                                $page = $_GET['page'];
                                            } else {
                                                $page = 1;
                                            }

                                            try {
                                                $start_from = ($page - 1) * $results_per_page;
                                                if (!isset($_GET['item'])) {
                                                    $sql = 'SELECT username.staff_id, username.username, username.`password`, username.position, username.role, username.deleted, employee.`NAME` FROM username INNER JOIN employee ON employee.staff_id = username.staff_id ORDER BY username.staff_id ASC LIMIT ' . $start_from . ',' . $results_per_page;
                                                } else {
                                                    $sql = 'SELECT username.staff_id, username.username, username.`password`, username.position, username.role, username.deleted, employee.`NAME` FROM username INNER JOIN employee ON employee.staff_id = username.staff_id WHERE username.staff_id = ' . $_GET['item'] . ' ORDER BY username.staff_id ASC LIMIT ' . $start_from . ',' . $results_per_page;
                                                }
                                                $query = $conn->prepare($sql);
                                                $fin = $query->execute();
                                                $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                                //sdsd

                                                foreach ($res as $row => $link) {
                                            ?><tr class="odd gradeX">
                                                        <?php
                                                        $thisemployeealterid = $link['staff_id'];
                                                        $thisuser = $link['staff_id'];
                                                        $thisemployeeNum = $link['staff_id'];
                                                        echo     '<td><input type="checkbox"></td>';
                                                        echo '<td>' . $link['staff_id'] .  '</td><td class="stylecaps">' . $link['NAME'] . '</td>';
                                                        echo '<td>';
                                                        echo $link['role'];
                                                        echo '</td>';;
                                                        echo '<td><button type="button" data-target="#edituser' . $thisuser . '" data-toggle="modal" class="btn btn-xs red"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></button></td>';
                                                        echo '</tr>';
                                                        ?>

                                                        <div id="edituser<?php echo $thisuser; ?>" class="modal fade" tabindex="-1" data-width="560">
                                                            <div class="modal-dialog" role="document">
                                                                <form class="form-horizontal" method="post" action="classes/controller.php?act=deactivateuser">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header modal-title" style="background: #6e7dc7;">
                                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span> </button>
                                                                            <h4 class="modal-title">Deactivate User</h4>
                                                                        </div>
                                                                        <div class="modal-body">

                                                                            <div class="row">
                                                                                <div class="col-md-12">
                                                                                    <div class="form-body">
                                                                                        <input type="hidden" name="thisuser" value="<?php echo $thisuser; ?>">

                                                                                        <label class="col-md-12 control-label">Please confirm account deactivation for:</label>


                                                                                        <div class="form-group">
                                                                                            <label class="col-md-4 control-label">Name</label>
                                                                                            <div class="col-md-7">
                                                                                                <input type="text" class="form-control" value="<?php echo $link['NAME'] ?>" readonly placeholder="Name">
                                                                                            </div>
                                                                                        </div>
                                                                                        <div class="form-group">
                                                                                            <label class="col-md-4 control-label">Username</label>
                                                                                            <div class="col-md-7">
                                                                                                <input type="text" required readonly value="<?php echo $link['staff_id']; ?>" class="form-control" name="username" placeholder="username">
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" data-dismiss="modal" class="btn btn-outline dark">Cancel</button>
                                                                            <button type="submit" class="btn red">Deactivate User</button>
                                                                        </div>
                                                                    </div>
                                                                </form>
                                                            </div>
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
                        </div>
                    </div>
                    <!-- Button trigger modal --> <?php

                                                    try {

                                                        if (isset($_GET['item'])) {
                                                            $sql = 'SELECT employee.staff_id, employee.`NAME` FROM employee WHERE staff_id = ' . $_GET['item'];
                                                            $query = $conn->prepare($sql);
                                                            $fin = $query->execute();
                                                            $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                                        }
                                                        //sdsd

                                                        foreach ($res as $row => $link) {
                                                    ?>
                            <div id="responsive" class="modal fade" tabindex="-1" data-width="560">
                                <div class="modal-dialog" role="document">
                                    <form class="form-horizontal" method="post" action="classes/controller.php?act=adduser">
                                        <div class="modal-content">
                                            <div class="modal-header modal-title" style="background: #6e7dc7;">
                                                <h4 class=" modal-title" style="text-transform: uppercase;">Create New Company User</h4>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>

                                            </div>
                                            <div class="modal-body">

                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="form-body">
                                                            <div class="form-group">
                                                                <label class="col-md-4 control-label">Name</label>
                                                                <div class="col-md-7">
                                                                    <input type="text" value="" required class="form-control" name="name" id="name" placeholder="Name">
                                                                </div>
                                                            </div>
                                                            <input type="hidden" name="staff_id" value="">
                                                            <div class="form-group">
                                                                <label class="col-md-4 control-label">Email Address</label>
                                                                <div class="col-md-7">
                                                                    <input type="email" required class="form-control" name="uemail" placeholder="Email Address">
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="col-md-4 control-label">Password</label>
                                                                <div class="col-md-7">
                                                                    <input type="password" required class="form-control" name="upass" placeholder="Password">
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="col-md-4 control-label">Repeat Password</label>
                                                                <div class="col-md-7">
                                                                    <input type="password" required class="form-control" name="upass1" placeholder="Repeat Password">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" data-dismiss="modal" class="btn btn-outline dark">Cancel</button>
                                                <button type="submit" class="btn red">Create User</button>
                                            </div>
                                    </form>
                                </div>
                            </div>

                    <?php
                                                        }
                                                    } catch (PDOException $e) {
                                                        echo $e->getMessage();
                                                    }
                    ?>





                </div>
            </div>




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

                    $("#item").autocomplete({
                        source: 'searchStaff.php',
                        type: 'POST',
                        delay: 10,
                        autoFocus: false,
                        minLength: 1,
                        select: function(event, ui) {
                            event.preventDefault();
                            $("#item").val(ui.item.value);
                            $item = $("#item").val();
                            //$('#add_item_form').ajaxSubmit({beforeSubmit: salesBeforeSubmit, success: itemScannedSuccess});
                            $('#add_item_form').ajaxSubmit({
                                beforeSubmit: salesBeforeSubmit,
                                type: "POST",
                                url: "users.php",
                                success: function(data) {
                                    window.location.href = "users.php?item=" + $item;
                                }


                            });
                        }
                    });

                    $("#name").autocomplete({
                        source: 'searchStaff.php',
                        type: 'POST',
                        delay: 10,
                        autoFocus: false,
                        minLength: 1,
                        select: function(event, ui) {
                            event.preventDefault();
                            $("#name").val(ui.item.value);
                            $item = $("#name").val();
                            //$('#add_item_form').ajaxSubmit({beforeSubmit: salesBeforeSubmit, success: itemScannedSuccess});
                            // $('#add_item_form').ajaxSubmit({
                            //     beforeSubmit: salesBeforeSubmit,
                            //     type: "POST",
                            //     url: "users.php",
                            //     success: function(data) {
                            //         window.location.href = "users.php?item=" + $item;
                            //     }


                            // });
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
                    //Ajax submit current location
                    $("#employee_current_location_id").change(function() {
                        $("#form_set_employee_current_location_id").ajaxSubmit(function() {
                            window.location.reload(true);
                        });
                    });


                    $('#employee_form').validate({

                        // Specify the validation rules
                        rules: {

                            namee: "required",
                            dept: "required",
                            acct_no: {
                                required: {
                                    depends: function(element) {
                                        if (($("#bank option:selected").text() != 'CHEQUE/CASH') || $("#bank option:selected").text() != 'CHEQUE/CASH') {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                //"required": false,
                                minlength: 10,
                                maxlength: 10,
                                number: true
                            },

                            rsa_pin: {
                                required: {
                                    depends: function(element) {
                                        if ($("#pfa option:selected").text() != 'OTHERS') {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                number: true
                            }


                        },

                        // Specify the validation error messages
                        messages: {
                            namee: "The name is a required field.",


                        },

                        errorClass: "text-danger",
                        errorElement: "span",
                        highlight: function(element, errorClass, validClass) {
                            $(element).parents('.form-group').removeClass('has-success').addClass('has-error');
                        },
                        unhighlight: function(element, errorClass, validClass) {
                            $(element).parents('.form-group').removeClass('has-error').addClass('has-success');
                        },

                        submitHandler: function(form) {

                            //form.submit();
                            doEmployeeSubmit(form);
                        }
                    });

                    document.getElementById('item').focus();

                    //						$('#sample_1').Tabledit({
                    //			      url:'action.php',
                    //			      columns:{
                    //			       identifier:[0, "StaffNo"],
                    //			       editable:[[5, 'PFAPIN']
                    //			      },
                    //			      restoreButton:false,
                    //			      onSuccess:function(data, textStatus, jqXHR)
                    //			      {
                    //			       if(data.action == 'delete')
                    //			       {
                    //			        $('#'+data.id).remove();
                    //			       }
                    //			      }
                    //			     });


                });
            </script>


            <script src="js/tableExport.js"></script>
            <script src="js/main.js"></script>
        </div><!--end #content-->
    </div><!--end #wrapper-->

    <ul class="ui-autocomplete ui-front ui-menu ui-widget ui-widget-content ui-corner-all" id="ui-id-1" tabindex="0" style="display: none;"></ul>
    <div id="footer" class="col-md-12 hidden-print">
        Please visit our
        <a href="#" target="_blank">
            website </a>
        to learn the latest information about the project.
        <span class="text-info"> <span class="label label-info"> 14.1</span></span>
    </div>
</body>

</html>
<?php
//mysqli_free_result($employee);
?>