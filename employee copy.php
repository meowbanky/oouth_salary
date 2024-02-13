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


$currentPage = $_SERVER["PHP_SELF"];




$today = '';
$today = date('Y-m-d');
?>
<!DOCTYPE html>
<!-- saved from url=(0055)http://www.optimumlinkup.com.ng/pos/index.php/customers -->
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
                    Employee</h1>


            </div>


            <div id="breadcrumb" class="hidden-print">
                <a href="home.php"><i class="fa fa-home"></i> Dashboard</a><a class="current" href="employee.php">Employees</a>
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
                        <div class="btn-group  ">
                            <div id="buttons">
                                <a class="btn red btn-sm" data-toggle="modal" data-target="#new-employee"><i class="fa fa-user-plus"></i> New Employee </a>

                                <button type="button" onclick="show_modal_page('createemployee_modal.php')" class="btn btn-info btn-flat"><i class="fa fa-plus-square" aria-hidden="true"></i>
                                    Add Employee
                                </button>

                                <button type="button" class="btn btn-warning btn-large dropdown-toggle" data-toggle="dropdown">Export to <span class="caret"></span></button>
                                <ul class="dropdown-menu" role="menu">
                                    <li><a onclick="window.print();">Print</a></li>
                                    <li><a onclick="exportAll('xls','<?php echo 'employee'; ?>');" href="javascript://">XLS</a></li>
                                    <li><a onclick="exportAll('csv','<?php echo 'employee'; ?>');" href="javascript://">CSV</a></li>
                                    <li><a onclick="exportAll('txt','<?php echo 'employee'; ?>');" href="javascript://">TXT</a></li>

                                </ul>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="row ">
                <form action="employee.php" method="post" accept-charset="utf-8" id="add_item_form" autocomplete="off">
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
                            <h5>List of Employees</h5>
                            <span title="" class="label label-info tip-left" data-original-title="total Employee">Total Employee<?php echo '100' ?></span>

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
                                                    $sql = 'SELECT count(staff_id) as "Total" FROM employee';
                                                } else {
                                                    $sql = 'SELECT count(staff_id) as "Total" FROM employee where staff_id = "' . $_GET['item'] . '"';
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
                                                    echo '"><a class="page-link" href="employee.php?page=' . $i . '">' . $i . '</a></li>';
                                                }
                                                ?>
                                            </ul>
                                        </nav>
                                    </div>

                                    <table class="table table-striped table-bordered table-hover table-checkable order-column tblbtn" id="sample_1">
                                        <thead>
                                            <tr>
                                                <th width="10"> </th>
                                                <th> Staff No# </th>
                                                <th> Names </th>
                                                <th> Date of Employment </th>
                                                <th> Status </th>
                                                <th> Department </th>
                                                <th> Grade/Step </th>
                                                <th> PFA </th>
                                                <th> Bank details - NO. </th>
                                                <th>Call Duty </th>
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
                                                    $sql = 'SELECT tbl_dept.dept, employee.STATUSCD,tbl_pfa.PFANAME, employee.PFAACCTNO, tbl_bank.BNAME, employee.staff_id, employee.`NAME`, employee.EMPDATE, employee.GRADE,  employee.STEP, employee.ACCTNO, employee.CALLTYPE FROM employee LEFT JOIN tbl_pfa ON tbl_pfa.PFACODE = employee.PFACODE INNER JOIN tbl_bank ON tbl_bank.BCODE = employee.BCODE INNER JOIN tbl_dept ON tbl_dept.dept_id = employee.DEPTCD ORDER BY statuscd,staff_id ASC LIMIT ' . $start_from . ',' . $results_per_page;
                                                } else {
                                                    $sql = 'SELECT tbl_dept.dept, employee.STATUSCD,tbl_pfa.PFANAME, employee.PFAACCTNO, tbl_bank.BNAME, employee.staff_id, employee.`NAME`, employee.EMPDATE, employee.GRADE,  employee.STEP, employee.ACCTNO, employee.CALLTYPE FROM employee LEFT JOIN tbl_pfa ON tbl_pfa.PFACODE = employee.PFACODE INNER JOIN tbl_bank ON tbl_bank.BCODE = employee.BCODE INNER JOIN tbl_dept ON tbl_dept.dept_id = employee.DEPTCD WHERE staff_id = ' . $_GET['item'] . ' ORDER BY statuscd,staff_id ASC LIMIT ' . $start_from . ',' . $results_per_page;
                                                }
                                                $query = $conn->prepare($sql);
                                                $fin = $query->execute();
                                                $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                                //sdsd

                                                foreach ($res as $row => $link) {
                                            ?><tr class="odd gradeX">
                                                        <?php
                                                        $thisemployeealterid = $link['staff_id'];
                                                        $thisemployeeNum = $link['staff_id'];
                                                        echo '<td><input type="checkbox"></td><td>' . $link['staff_id'] .  '</td><td class="stylecaps">' . $link['NAME'] . '</td><td>';
                                                        echo   $link['EMPDATE'];
                                                        echo '</td><td>';
                                                        if ($link['STATUSCD'] == 'A') {
                                                            echo 'Active';
                                                        } elseif ($link['STATUSCD'] == 'D') {
                                                            echo 'DISMISSED';
                                                        } elseif ($link['STATUSCD'] == 'T') {
                                                            echo 'TERMINATION';
                                                        } elseif ($link['STATUSCD'] == 'R') {
                                                            echo 'RESIGNATION';
                                                        } elseif ($link['STATUSCD'] == 'S') {
                                                            echo 'SUSPENSION';
                                                        }
                                                        //echo  $link['status'] ;   
                                                        echo '</td><td>';
                                                        echo $link['dept'];
                                                        echo '</td><td>';
                                                        echo $link['GRADE'] . '/' . $link['STEP'];
                                                        echo '</td><td>';
                                                        echo $link['PFANAME'];
                                                        echo '</td><td>';
                                                        echo $link['BNAME'] . '-' . $link['ACCTNO'];
                                                        echo '</td><td>';
                                                        if ($link['CALLTYPE'] == 0) {
                                                            echo 'NONE';
                                                        } elseif ($link['CALLTYPE'] == 1) {
                                                            echo 'DOCTOR';
                                                        } elseif ($link['CALLTYPE'] == 3) {
                                                            echo 'NURSE';
                                                        } elseif ($link['CALLTYPE'] == 2) {
                                                            echo 'OTHERS';
                                                        }
                                                        echo '</td>';

                                                        echo '<td> 

                                                      
                                                                             		
                                                                                <button type="button" onclick="show_modal_page("edit_employee_modal.php?staff_id=' . $thisemployeealterid . '")" data-target=#viewemp' . $thisemployeealterid . ' class="btn btn-xs blue" data-toggle="modal" data-placement="top" title="View employee details"><span class="glyphicon glyphicon-zoom-in" aria-hidden="true"></span></button> 
                                                                                <!--<a href="" class="btn btn-xs green" data-toggle="tooltip" data-placement="top" title="Edit employee details"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span></a>--> 
                                                                                <!--<button type="button" data-target="#suspend' . $thisemployeealterid . '" class="btn btn-xs yellow" data-toggle="modal" data-placement="top" title="Suspend Employee"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span></button>--> 
 																																								<button type="button" data-target="#deactivate' . $thisemployeealterid . '" class="btn btn-xs red" data-toggle="modal" data-placement="top" title="Terminate employee"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></button>
                                                                                <a href="classes/controller.php?act=vtrans&td=' . $link['staff_id'] . '" class="btn btn-xs purple" data-placement="top" title="View Transactions"><span class="glyphicon glyphicon-list-alt" aria-hidden="true"></span></a>
                                                                                <!--<a href="" class="btn btn-xs yellow" data-toggle="modal" data-placement="left" title="Go to employee Earnings / Deductions"><span class="glyphicon glyphicon-usd" aria-hidden="true"></span></a></td></tr>-->';
                                                        ?>


                                                        <div id="suspend<?php echo $thisemployeealterid; ?>" class="modal fade" tabindex="-1" data-width="560">
                                                            <div class="modal-dialog" role="document">
                                                                <div class="modal-content">
                                                                    <div class="modal-header modal-title" style="background: #6e7dc7;">
                                                                        <button type=" button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                                                                        <h4 class="modal-title">Suspend Employee <?php echo $thisemployeeNum; ?> </h4>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <form class="horizontal-form" method="post" action="assets/classes/controller.php?act=suspendEmployee">
                                                                            <div class="row">
                                                                                <div class="col-md-12">
                                                                                    <div class="form-body">

                                                                                        <div class="row">
                                                                                            <div class="col-md-12">
                                                                                                <input type="hidden" value="<?php echo $thisemployeealterid; ?>" name="empalterid">
                                                                                                <input type="hidden" value="<?php echo $thisemployeeNum; ?>" name="empalternumber">

                                                                                                <label>Please confirm you would like to suspend this employee?
                                                                                                    <b><?php
                                                                                                        //echo $thisemployeealterid;
                                                                                                        retrieveDescSingleFilter('employee', 'NAME', 'staff_id', $thisemployeealterid);
                                                                                                        echo " ";

                                                                                                        echo " - " . $thisemployeeNum;
                                                                                                        ?></b>
                                                                                                </label>
                                                                                            </div>
                                                                                        </div>

                                                                                        <p></p>


                                                                                        <div class="row">
                                                                                            <div class="col-md-6">
                                                                                                <div class="form-group">
                                                                                                    <label>Start Date</label>

                                                                                                    <div class="input-group date" data-provide="datepicker">
                                                                                                        <input type="date" required name="startsuspension" class="form-control">
                                                                                                        <div class="input-group-addon">
                                                                                                            <span class="glyphicon glyphicon-th"></span>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                            <div class="col-md-6">
                                                                                                <div class="form-group">
                                                                                                    <label>End Date</label>

                                                                                                    <div class="input-group date" data-provide="datepicker">
                                                                                                        <input type="date" required name="endsuspension" class="form-control">
                                                                                                        <div class="input-group-addon">
                                                                                                            <span class="glyphicon glyphicon-th"></span>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                        <div class="row">
                                                                                            <div class="col-md-12">
                                                                                                <div class="form-group">
                                                                                                    <label>Suspension Details</label>
                                                                                                    <textarea class="form-control" rows="3" required name="suspendreason" placeholder="Enter reason for exit"></textarea>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>


                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" data-dismiss="modal" class="btn btn-outline dark">Cancel</button>
                                                                        <button type="submit" class="btn red">Deactivate Employee</button>
                                                                    </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <!-- Deactiv Modal -->
                                                        <div id="deactivate<?php echo $thisemployeealterid; ?>" class="modal fade" tabindex="-1" data-width="560">
                                                            <div class="modal-dialog" role="document">
                                                                <div class="modal-content">
                                                                    <div class="modal-header modal-title" style="background: #6e7dc7;">
                                                                        <button type=" button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                                                                        <h4 class="modal-title">Deactivate Employee <?php echo $thisemployeeNum; ?> </h4>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <form class="horizontal-form" method="post" action="classes/controller.php?act=deactivateEmployee">
                                                                            <div class="row">
                                                                                <div class="col-md-12">
                                                                                    <div class="form-body">

                                                                                        <div class="row">
                                                                                            <div class="col-md-12">
                                                                                                <input type="hidden" value="<?php echo $thisemployeealterid; ?>" name="empalterid">
                                                                                                <input type="hidden" value="<?php echo $thisemployeeNum; ?>" name="empalternumber">

                                                                                                <label>Please confirm you would like to deactivate this employee?
                                                                                                    <b><?php
                                                                                                        //echo $thisemployeealterid;
                                                                                                        retrieveDescSingleFilter('employee', 'NAME', 'staff_id', $thisemployeealterid);
                                                                                                        echo " - " . $thisemployeeNum;
                                                                                                        ?></b>
                                                                                                </label>
                                                                                            </div>
                                                                                        </div>

                                                                                        <p></p>


                                                                                        <div class="row">
                                                                                            <div class="col-md-6">
                                                                                                <div class="form-group">
                                                                                                    <label for="dept" class="required  control-label ">Deative/Activate:</label>
                                                                                                    <select name="deactivate" class="form-inps" required>
                                                                                                        <option>Select Deactivate</option>
                                                                                                        <?php

                                                                                                        $query = $conn->prepare('SELECT * FROM staff_status');
                                                                                                        $res = $query->execute();
                                                                                                        $out = $query->fetchAll(PDO::FETCH_ASSOC);

                                                                                                        while ($row = array_shift($out)) {
                                                                                                            echo '<option value="' . $row['STATUSCD'] . '"';
                                                                                                            if ($link['STATUSCD'] == $row['STATUSCD']) {
                                                                                                                echo 'SELECTED ';
                                                                                                            }
                                                                                                            echo '>' .  $row['STATUS'] . '</option>';
                                                                                                        }

                                                                                                        ?>
                                                                                                    </select>
                                                                                                </div>
                                                                                            </div>


                                                                                        </div>


                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                    </div>

                                                                    <div class="modal-footer">
                                                                        <button type="button" data-dismiss="modal" class="btn btn-outline dark">Cancel</button>
                                                                        <button type="submit" class="btn red">Deactivate Employee</button>
                                                                    </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <!-- Deactiv Modal -->


                                                        <!-- Deactiv Modal -->

                                                        <!-- Deactiv Modal -->


                                                        <!-- View Emp Modal -->
                                                        <div id="viewemp<?php echo $thisemployeealterid; ?>" class="modal fade" tabindex="-1" data-width="650">
                                                            <div class="modal-dialog" role="document">
                                                                <div class="modal-content" style="width:800px">
                                                                    <div class="modal-header modal-title" style="background: #6e7dc7;">
                                                                        <h4 class=" modal-title" style="text-transform: uppercase;">Employee Details</h4>
                                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                            <span aria-hidden="true">&times;</span>
                                                                        </button>
                                                                    </div>

                                                                    <?php
                                                                    $query = $conn->prepare('SELECT * FROM employee WHERE staff_id = ?');
                                                                    $fin = $query->execute(array($thisemployeealterid));
                                                                    $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                                                    //sdsd

                                                                    foreach ($res as $row => $empd) {




                                                                    ?>

                                                                </div>

                                                            <?php
                                                                    }
                                                            ?>



                                                            </div>
                                                            <!-- View Emp Modal -->

                                                    <?php
                                                }
                                            } catch (PDOException $e) {
                                                echo $e->getMessage();
                                            }
                                                    ?>
                                                    <!--End Data Table-->





                                        </tbody>
                                    </table>

                                    <!-- modal -->
                                    <!-- modal -->
                                    <div id="new-employee" class="modal fade" tabindex="-1" aria-hidden="true" data-width="800">
                                        <!-- <div class="modal-dialog" role="document">
                                            <div class="modal-content" style="width:800px">
                                                <div class="modal-header modal-title" style="background: #6e7dc7;">
                                                    <button type=" button" class="close" data-dismiss="modal" aria-hidden="true"><span aria-hidden="true">&times;</span></button>
                                                    <h4 class="modal-title">create new employee </h4>
                                                </div>
                                                <div class="modal-body">
                                                    <form method="post" action="classes/controller.php?act=addNewEmp" class="horizontal-form" id="employee_form">
                                                        <div class="row">
                                                            <div class="col-md-12">

                                                                <!-- BEGIN New Employee Popup-->
                                                                <?php
                                                                mysqli_select_db($salary, $database_salary);
                                                                $query_bank = 'SELECT tbl_bank.BNAME, tbl_bank.BCODE FROM tbl_bank order by BCODE';
                                                                $bank = mysqli_query($salary, $query_bank) or die(mysqli_error($salary));
                                                                $row_bank = mysqli_fetch_assoc($bank);

                                                                mysqli_select_db($salary, $database_salary);
                                                                $query_dept = 'SELECT tbl_dept.dept_id, tbl_dept.dept FROM tbl_dept';
                                                                $dept = mysqli_query($salary, $query_dept) or die(mysqli_error($salary));
                                                                $row_dept = mysqli_fetch_assoc($dept);

                                                                mysqli_select_db($salary, $database_salary);
                                                                $query_pfa = 'SELECT tbl_pfa.PFACODE, tbl_pfa.PFANAME FROM tbl_pfa';
                                                                $pfa = mysqli_query($salary, $query_pfa) or die(mysqli_error($salary));
                                                                $row_pfa = mysqli_fetch_assoc($pfa);
                                                                ?>
                                                                <div class="form-body">

                                                                    <h4 class="form-section"><b>Personal Details</b></h4>

                                                                    <div class="row">
                                                                        <div class="co-md">
                                                                            <div class="form-group">
                                                                                <label>Name</label>
                                                                                <input type="text" autocomplete="off" name="namee" class="form-control" required placeholder="Name">
                                                                            </div>
                                                                        </div>

                                                                    </div>





                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label>Bank</label>
                                                                                <select name="bank" class="form-inps" id="bank" required>
                                                                                    <option value=''>Select Bank</option>
                                                                                    <?php while ($row = mysqli_fetch_array($bank)) {
                                                                                        echo "<option value='" . $row['BCODE'] . "' ";

                                                                                        echo ">" . $row['BNAME'] . "</option>";
                                                                                    } ?>
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label>Account No.</label>
                                                                                <input type="number" name="acct_no" required class="form-control" placeholder="Account No" pattern="\d{10}" maxlength="10">
                                                                            </div>

                                                                        </div>
                                                                    </div>

                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label>Pension FA</label>
                                                                                <select name="pfa" class="form-inps" id="pfa" required>
                                                                                    <option>Select PFA</option>
                                                                                    <?php while ($row = mysqli_fetch_array($pfa)) {
                                                                                        echo "<option value='" . $row['PFACODE'] . "' ";

                                                                                        echo ">" . $row['PFANAME'] . "</option>";
                                                                                    } ?>
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label>RSA PIN.</label>
                                                                                <input type="text" name="rsa_pin" required class="form-control" placeholder="PFA PIN">
                                                                            </div>

                                                                        </div>
                                                                    </div>



                                                                    <h4 class="form-section"><b>Employment Details</b></h4>

                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label>Employee No</label>
                                                                                <?php
                                                                                $payp = $conn->prepare('SELECT Max(employee.staff_id) as "nextNo" FROM employee');
                                                                                $myperiod = $payp->execute();
                                                                                $final = $payp->fetch();
                                                                                ?>
                                                                                <input type="text" readonly name="emp_no" class="form-control" required placeholder="Staff No" value="<?php echo intval($final['nextNo']) + 1 ?>">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label>Date of Employment</label>
                                                                                <div class="input-group date" data-provide="datepicker">
                                                                                    <input type="date" required name="doe" class="form-control">
                                                                                    <div class="input-group-addon">
                                                                                        <span class="glyphicon glyphicon-th"></span>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                    </div>



                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label>Department</label>
                                                                                <select name="dept" class="form-control">
                                                                                    <option value="">- - - Select Department - - -</option>
                                                                                    <?php

                                                                                    $query = $conn->prepare('SELECT * FROM tbl_dept');
                                                                                    $res = $query->execute();
                                                                                    $out = $query->fetchAll(PDO::FETCH_ASSOC);

                                                                                    while ($row = array_shift($out)) {
                                                                                        echo ('<option value="' . $row['dept_id'] . '">' .  $row['dept'] . '</option>');
                                                                                    }

                                                                                    ?>
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label>Designation</label>
                                                                                <input type="text" name="designation" required class="form-control" placeholder="Post">
                                                                            </div>
                                                                        </div>

                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <div class="form-group"><br>
                                                                                <label for="callType" class="required  control-label ">Call Duty Type:</label>
                                                                                <label><br>
                                                                                    <input name="callType" type="radio" class="radio-inline" id="payType_01" value="0" checked>
                                                                                    None</label><br>
                                                                                <label>
                                                                                    <input name="callType" type="radio" class="radio-inline" id="payType_0" value="1" <?php if ($empd['CALLTYPE'] == 1) {
                                                                                                                                                                            echo 'checked';
                                                                                                                                                                        } ?>>
                                                                                    Doctors</label><br>
                                                                                <label>
                                                                                    <input type="radio" name="callType" value="2" id="payType_1" class="radio-inline" <?php if ($empd['CALLTYPE'] == 2) {
                                                                                                                                                                            echo 'checked';
                                                                                                                                                                        } ?>>
                                                                                    Others</label><br>
                                                                                <label>
                                                                                    <input type="radio" name="callType" value="3" id="payType_2" class="radio-inline" <?php if ($empd['CALLTYPE'] == 3) {
                                                                                                                                                                            echo 'checked';
                                                                                                                                                                        } ?>>
                                                                                    Nurse</label>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">

                                                                        </div>

                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="grade" class="required  control-label ">Grade:</label>
                                                                                <input type="text" name="grade" value="" class="form-inps focus" id="grade" required maxlength="3">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="grade" class="required  control-label ">Step:</label>
                                                                                <input type="text" name="gradestep" value="" class="form-inps focus" id="gradestep" required maxlength="2">
                                                                            </div>
                                                                        </div>

                                                                    </div>


                                                                </div>
                                                                <!-- END New Employee Popup-->


                                                            </div>
                                                        </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" data-dismiss="modal" class="btn btn-outline dark">Cancel</button>
                                                    <button type="submit" name="addemp" class="btn red">Add Employee</button>
                                                </div>

                                                </form>
                                            </div>
                                        </div> -->
                                    </div>
                                </div>





                            </div>
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
                                            $sql = 'SELECT count(staff_id) as "Total" FROM employee';
                                        } else {
                                            $sql = 'SELECT count(staff_id) as "Total" FROM employee where staff_id = "' . $_GET['item'] . '"';
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
                                            echo '"><a class="page-link" href="employee.php?page=' . $i . '">' . $i . '</a></li>';
                                        }
                                        ?>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="page_model_view_data">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content" style="width:800px">
                            <div class="modal-body" style="height:800px; overflow:auto;">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Button trigger modal -->


                <!-- Modal -->






            </div>
        </div>
        <div id="footer" class="col-md-12 hidden-print">
            Please visit our
            <a href="#" target="_blank">
                website </a>
            to learn the latest information about the project.
            <span class="text-info"> <span class="label label-info"> 14.1</span></span>
        </div>



        <script type="text/javascript">
            function show_modal_page(url) {
                // SHOWING AJAX PRELOADER IMAGE
                jQuery('#page_model_view_data .modal-body').html('<div style="text-align:center;margin-top:200px;"><img src="img/loader-1.gif" style="height:25px;" /></div>');
                // LOADING THE AJAX MODAL
                jQuery('#page_model_view_data').modal('show', {
                    backdrop: 'true'
                });

                // SHOW AJAX RESPONSE ON REQUEST SUCCESS
                $.ajax({
                    url: url,
                    success: function(response) {
                        //alert(response);
                        jQuery('#page_model_view_data .modal-body').html(response);
                    }
                });
            }


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
                            url: "employee.php",
                            success: function(data) {
                                window.location.href = "employee.php?item=" + $item;
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
            });
        </script>
        <script src="js/tableExport.js"></script>
        <script src="js/main.js"></script>
    </div><!--end #content-->
    </div><!--end #wrapper-->

    <ul class="ui-autocomplete ui-front ui-menu ui-widget ui-widget-content ui-corner-all" id="ui-id-1" tabindex="0" style="display: none;"></ul>

</body>

</html>
<?php
//mysqli_free_result($employee);
?>