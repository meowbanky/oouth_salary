<?php
ini_set('max_execution_time', '300');
require_once('Connections/paymaster.php');
include_once('classes/model.php');

require_once 'libs/App.php';
$App = new App();
$App->checkAuthentication();
require_once 'libs/middleware.php';
checkPermission();

// Start session
session_start();

// Check whether the session variable SESS_MEMBER_ID is present or not
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '') || $_SESSION['role'] != 'Admin') {
    header("location: index.php");
    exit();
}

$currentPage = $_SERVER["PHP_SELF"];

$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html>
<?php include('header1.php'); ?>

<body data-color="grey" class="flat" style="zoom: 1;">
    <div class="modal fade hidden-print" id="myModal"></div>
    <div id="wrapper">
        <div id="header" class="hidden-print">
            <h1><a href="index.php"><img src="img/header_logo.png" class="hidden-print header-log" id="header-logo"
                        alt=""></a></h1>
            <a id="menu-trigger" href="#"><i class="fa fa-bars fa fa-2x"></i></a>
            <div class="clear"></div>
        </div>
        <?php include('header.php'); ?>
        <?php include('sidebar.php'); ?>
        <div id="content" class="clearfix sales_content_minibar">
            <div id="content-header" class="hidden-print">
                <h1> <i class="icon fa fa-user"></i> </h1>
            </div>
            <div id="breadcrumb" class="hidden-print">
                <a href="home.php"><i class="fa fa-home"></i> Dashboard</a><a class="current"
                    href="employee.php">Employees</a>
            </div>
            <div class="clear"></div>
            <div id="datatable_wrapper"></div>
            <div class="pull-right">
                <div class="row">
                    <div id="datatable_wrapper"></div>
                    <div class="col-md-12 center" style="text-align: center;">
                        <?php
                        if (isset($_SESSION['msg'])) {
                            echo '<div class="alert alert-' . $_SESSION['alertcolor'] . ' alert-dismissable role="alert"> <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>' . $_SESSION['msg'] . '</div>';
                            unset($_SESSION['msg']);
                            unset($_SESSION['alertcolor']);
                        }
                        ?>
                        <div class="btn-group">
                            <div id="buttons">
                                <a class="btn green btn-sm" href="exportemployee.php"><i class="fa fa-user-plus"></i>
                                    Download Employee </a>
                                <a class="btn red btn-sm" data-toggle="modal" data-target="#new-employee"><i
                                        class="fa fa-user-plus"></i> New Employee </a>
                                <button type="button" class="btn btn-warning btn-large dropdown-toggle"
                                    data-toggle="dropdown">Export to <span class="caret"></span></button>
                                <ul class="dropdown-menu" role="menu">
                                    <li><a onclick="window.print();">Print</a></li>
                                    <li><a onclick="exportAll('xls','<?php echo 'employee'; ?>');"
                                            href="javascript://">XLS</a></li>
                                    <li><a onclick="exportAll('csv','<?php echo 'employee'; ?>');"
                                            href="javascript://">CSV</a></li>
                                    <li><a onclick="exportAll('txt','<?php echo 'employee'; ?>');"
                                            href="javascript://">TXT</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <form action="employee.php" method="post" accept-charset="utf-8" id="add_item_form" autocomplete="off">
                    <span role="status" aria-live="polite" class="ui-helper-hidden-accessible"></span>
                    <input type="text" name="item" value="" id="item" class="ui-autocomplete-input" accesskey="i"
                        placeholder="Enter Staff Name or Staff No" />
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
                            <span title="" class="label label-info tip-left" data-original-title="total Employee">Total
                                Employee<?php echo '100' ?></span>
                        </div>
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
                                                if (!isset($_GET['item'])) {
                                                    $sql = 'SELECT count(staff_id) as "Total" FROM employee';
                                                } else {
                                                    $sql = 'SELECT count(staff_id) as "Total" FROM employee where staff_id = "' . $_GET['item'] . '"';
                                                }
                                                $result = $conn->query($sql);
                                                $row = $result->fetch();
                                                $total_pages = ceil($row['Total'] / $results_per_page);
                                                for ($i = 1; $i <= $total_pages; $i++) {
                                                    echo '<li class="page-item ';
                                                    if ($i == $page) {
                                                        echo ' active"';
                                                    }
                                                    echo '"><a class="page-link" href="employee.php?page=' . $i . '">' . $i . '</a></li>';
                                                }
                                                ?>
                                            </ul>
                                        </nav>
                                    </div>
                                    <table
                                        class="table table-striped table-bordered table-hover table-checkable order-column tblbtn"
                                        id="sample_1">
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
                                                <th> Call Duty </th>
                                                <th> Actions </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                        $results_per_page = 20;
                                        if (isset($_GET['page'])) {
                                            $page = $_GET['page'];
                                        } else {
                                            $page = 1;
                                        }
                                        try {
                                        $start_from = ($page - 1) * $results_per_page;
                                        if (!isset($_GET['item'])) {
                                            $sql = 'SELECT tbl_dept.dept, employee.STATUSCD, tbl_pfa.PFANAME, employee.PFAACCTNO, tbl_bank.BNAME, employee.staff_id, employee.`NAME`, employee.EMPDATE, employee.GRADE, employee.STEP, employee.ACCTNO, employee.CALLTYPE FROM employee LEFT JOIN tbl_pfa ON tbl_pfa.PFACODE = employee.PFACODE LEFT JOIN tbl_bank ON tbl_bank.BCODE = employee.BCODE LEFT JOIN tbl_dept ON tbl_dept.dept_id = employee.DEPTCD ORDER BY statuscd,staff_id ASC LIMIT ' . $start_from . ',' . $results_per_page;
                                        } else {
                                            $sql = "SELECT tbl_dept.dept, employee.STATUSCD, COALESCE(tbl_pfa.PFANAME,'') PFANAME, employee.PFAACCTNO, COALESCE(tbl_bank.BNAME,'') BNAME, employee.staff_id, employee.`NAME`, employee.EMPDATE, employee.GRADE, employee.STEP, employee.ACCTNO, employee.CALLTYPE FROM employee LEFT JOIN tbl_pfa ON tbl_pfa.PFACODE = employee.PFACODE LEFT JOIN tbl_bank ON tbl_bank.BCODE = employee.BCODE LEFT JOIN tbl_dept ON tbl_dept.dept_id = employee.DEPTCD WHERE staff_id = " . $_GET['item'] . " ORDER BY statuscd,staff_id ASC LIMIT " . $start_from . ',' . $results_per_page;
                                        }
                                        $query = $conn->prepare($sql);
                                        $fin = $query->execute();
                                        $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($res as $row => $link) {
                                        $thisemployeealterid = $link['staff_id'];
                                        $thisemployeeNum = $link['staff_id'];
                                        ?>
                                            <tr class="odd gradeX">
                                                <td><input type="checkbox"></td>
                                                <td><?php echo $link['staff_id']; ?></td>
                                                <td class="stylecaps"><?php echo $link['NAME']; ?></td>
                                                <td><?php echo $link['EMPDATE']; ?></td>
                                                <td><?php
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
                                                ?></td>
                                                <td><?php echo $link['dept']; ?></td>
                                                <td><?php echo $link['GRADE'] . '/' . $link['STEP']; ?></td>
                                                <td><?php echo $link['PFANAME']; ?></td>
                                                <td><?php echo $link['BNAME'] . '-' . $link['ACCTNO']; ?></td>
                                                <td><?php
                                                if ($link['CALLTYPE'] == 0) {
                                                    echo 'NONE';
                                                } elseif ($link['CALLTYPE'] == 1) {
                                                    echo 'DOCTOR';
                                                } elseif ($link['CALLTYPE'] == 3) {
                                                    echo 'NURSE';
                                                } elseif ($link['CALLTYPE'] == 2) {
                                                    echo 'OTHERS';
                                                }
                                                ?></td>
                                                <td>
                                                    <button type="button"
                                                        data-target="#viewemp<?php echo $thisemployeealterid; ?>"
                                                        class="btn btn-xs blue" data-toggle="modal" data-placement="top"
                                                        title="View employee details"><span
                                                            class="glyphicon glyphicon-zoom-in"
                                                            aria-hidden="true"></span></button>
                                                    <button type="button"
                                                        data-target="#deactivate<?php echo $thisemployeealterid; ?>"
                                                        class="btn btn-xs red" data-toggle="modal" data-placement="top"
                                                        title="Terminate employee"><span
                                                            class="glyphicon glyphicon-remove"
                                                            aria-hidden="true"></span></button>
                                                    <a href="classes/controller.php?act=vtrans&td=<?php echo $link['staff_id']; ?>"
                                                        class="btn btn-xs purple" data-placement="top"
                                                        title="View Transactions"><span
                                                            class="glyphicon glyphicon-list-alt"
                                                            aria-hidden="true"></span></a>
                                                </td>
                                            </tr>
                                            <div id="suspend<?php echo $thisemployeealterid; ?>" class="modal fade"
                                                tabindex="-1" data-width="560">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header modal-title"
                                                            style="background: #6e7dc7;">
                                                            <button type="button" class="close" data-dismiss="modal"
                                                                aria-hidden="true"></button>
                                                            <h4 class="modal-title">Suspend Employee
                                                                <?php echo $thisemployeeNum; ?> </h4>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form class="horizontal-form" method="post"
                                                                action="assets/classes/controller.php?act=suspendEmployee">
                                                                <div class="row">
                                                                    <div class="col-md-12">
                                                                        <div class="form-body">
                                                                            <div class="row">
                                                                                <div class="col-md-12">
                                                                                    <input type="hidden"
                                                                                        value="<?php echo $thisemployeealterid; ?>"
                                                                                        name="empalterid">
                                                                                    <input type="hidden"
                                                                                        value="<?php echo $thisemployeeNum; ?>"
                                                                                        name="empalternumber">
                                                                                    <label>Please confirm you would like
                                                                                        to suspend this employee? <b><?php
                                                                                        retrieveDescSingleFilter('employee', 'NAME', 'staff_id', $thisemployeealterid);
                                                                                        echo " - " . $thisemployeeNum;
                                                                                        ?></b></label>
                                                                                </div>
                                                                            </div>
                                                                            <p></p>
                                                                            <div class="row">
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label>Start Date</label>
                                                                                        <div class="input-group date"
                                                                                            data-provide="datepicker">
                                                                                            <input type="date" required
                                                                                                name="startsuspension"
                                                                                                class="form-control">
                                                                                            <div
                                                                                                class="input-group-addon">
                                                                                                <span
                                                                                                    class="glyphicon glyphicon-th"></span>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label>End Date</label>
                                                                                        <div class="input-group date"
                                                                                            data-provide="datepicker">
                                                                                            <input type="date" required
                                                                                                name="endsuspension"
                                                                                                class="form-control">
                                                                                            <div
                                                                                                class="input-group-addon">
                                                                                                <span
                                                                                                    class="glyphicon glyphicon-th"></span>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row">
                                                                                <div class="col-md-12">
                                                                                    <div class="form-group">
                                                                                        <label>Suspension
                                                                                            Details</label>
                                                                                        <textarea class="form-control"
                                                                                            rows="3" required
                                                                                            name="suspendreason"
                                                                                            placeholder="Enter reason for exit"></textarea>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" data-dismiss="modal"
                                                                        class="btn btn-outline dark">Cancel</button>
                                                                    <button type="submit" class="btn red">Deactivate
                                                                        Employee</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="deactivate<?php echo $thisemployeealterid; ?>" class="modal fade"
                                                tabindex="-1 passata" data-width="560">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header modal-title"
                                                            style="background: #6e7dc7;">
                                                            <button type="button" class="close" data-dismiss="modal"
                                                                aria-hidden="true"></button>
                                                            <h4 class="modal-title">Deactivate Employee
                                                                <?php echo $thisemployeeNum; ?> </h4>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form class="horizontal-form" method="post"
                                                                action="classes/controller.php?act=deactivateEmployee">
                                                                <div class="row">
                                                                    <div class="col-md-12">
                                                                        <div class="form-body">
                                                                            <div class="row">
                                                                                <div class="col-md-12">
                                                                                    <input type="hidden"
                                                                                        value="<?php echo $thisemployeealterid; ?>"
                                                                                        name="empalterid">
                                                                                    <input type="hidden"
                                                                                        value="<?php echo $thisemployeeNum; ?>"
                                                                                        name="empalternumber">
                                                                                    <label>Please confirm you would like
                                                                                        to deactivate this employee? <b><?php
                                                                                        retrieveDescSingleFilter('employee', 'NAME', 'staff_id', $thisemployeealterid);
                                                                                        echo " - " . $thisemployeeNum;
                                                                                        ?></b></label>
                                                                                </div>
                                                                            </div>
                                                                            <p></p>
                                                                            <div class="row">
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label for="dept"
                                                                                            class="required control-label">Deactive/Activate:</label>
                                                                                        <select name="deactivate"
                                                                                            class="form-inps" required>
                                                                                            <option>Select Deactivate
                                                                                            </option>
                                                                                            <?php
                                                                                        $query = $conn->prepare('SELECT * FROM staff_status');
                                                                                        $res = $query->execute();
                                                                                        $out = $query->fetchAll(PDO::FETCH_ASSOC);
                                                                                        while ($row = array_shift($out)) {
                                                                                            echo '<option value="' . $row['STATUSCD'] . '"';
                                                                                            if ($link['STATUSCD'] == $row['STATUSCD']) {
                                                                                                echo 'SELECTED ';
                                                                                            }
                                                                                            echo '>' . $row['STATUS'] . '</option>';
                                                                                        }
                                                                                        ?>
                                                                                        </select>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" data-dismiss="modal"
                                                                        class="btn btn-outline dark">Cancel</button>
                                                                    <button type="submit" class="btn red">Deactivate
                                                                        Employee</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="viewemp<?php echo $thisemployeealterid; ?>" class="modal fade"
                                                tabindex="-1" data-width="650">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content" style="width:800px">
                                                        <div class="modal-header modal-title"
                                                            style="background: #6e7dc7;">
                                                            <h4 class="modal-title" style="text-transform: uppercase;">
                                                                Employee Details</h4>
                                                            <button type="button" class="close" data-dismiss="modal"
                                                                aria-label="Close">
                                                                <span aria-hidden="true">×</span>
                                                            </button>
                                                        </div>
                                                        <?php
                                                    $query = $conn->prepare('SELECT * FROM employee WHERE staff_id = ?');
                                                    $fin = $query->execute(array($thisemployeealterid));
                                                    $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                                    foreach ($res as $row => $empd) {
                                                    ?>
                                                        <div class="modal-body">
                                                            <form method="post"
                                                                action="classes/controller.php?act=updateEmp"
                                                                class="horizontal-form">
                                                                <div class="row">
                                                                    <div class="col-md-12">
                                                                        <div class="form-body">
                                                                            <h4 class="form-section"><b>Personal
                                                                                    Details</b></h4>
                                                                            <div class="row">
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label>Employee No:</label>
                                                                                        <input type="text" name="emp_no"
                                                                                            value="<?php echo $empd['staff_id'] ?>"
                                                                                            class="form-inps focus"
                                                                                            readonly>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label>Employee Name:</label>
                                                                                        <input name="namee" type="text"
                                                                                            class="form-control"
                                                                                            value="<?php echo $empd['NAME']; ?>">
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row">
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label for="dept"
                                                                                            class="required control-label">Department:</label>
                                                                                        <select name="dept"
                                                                                            class="form-inps" required>
                                                                                            <option>Select Department
                                                                                            </option>
                                                                                            <?php
                                                                                        $query = $conn->prepare('SELECT * FROM tbl_dept');
                                                                                        $res = $query->execute();
                                                                                        $out = $query->fetchAll(PDO::FETCH_ASSOC);
                                                                                        while ($row = array_shift($out)) {
                                                                                            echo ('<option value="' . $row['dept_id'] . '"');
                                                                                            if ($row['dept_id'] == $empd['DEPTCD']) {
                                                                                                echo 'SELECTED';
                                                                                            }
                                                                                            echo ('>' . $row['dept'] . '</option>');
                                                                                        }
                                                                                        ?>
                                                                                        </select>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label
                                                                                            for="designation">Designation:</label>
                                                                                        <input name="post" type="text"
                                                                                            class="form-inps"
                                                                                            value="<?php echo $empd['POST'] ?>">
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row">
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label for="payType"
                                                                                            class="required control-label">Call
                                                                                            Duty Type:</label><br>
                                                                                        <label><input name="callType"
                                                                                                type="radio"
                                                                                                class="radio-inline"
                                                                                                value="0" checked>
                                                                                            None</label><br>
                                                                                        <label><input name="callType"
                                                                                                type="radio"
                                                                                                class="radio-inline"
                                                                                                value="1"
                                                                                                <?php if ($empd['CALLTYPE'] == 1) echo 'checked'; ?>>
                                                                                            Doctors</label><br>
                                                                                        <label><input type="radio"
                                                                                                name="callType"
                                                                                                value="2"
                                                                                                class="radio-inline"
                                                                                                <?php if ($empd['CALLTYPE'] == 2) echo 'checked'; ?>>
                                                                                            Others</label><br>
                                                                                        <label><input type="radio"
                                                                                                name="callType"
                                                                                                value="3"
                                                                                                class="radio-inline"
                                                                                                <?php if ($empd['CALLTYPE'] == 3) echo 'checked'; ?>>
                                                                                            Nurse</label>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label for="hazardType"
                                                                                            class="required control-label">Hazard
                                                                                            Type:</label><br>
                                                                                        <label><input name="hazardType"
                                                                                                type="radio"
                                                                                                class="radio-inline"
                                                                                                value="1"
                                                                                                <?php if ($empd['HARZAD_TYPE'] == 1) echo 'checked'; ?>>
                                                                                            Clinical</label><br>
                                                                                        <label><input type="radio"
                                                                                                name="hazardType"
                                                                                                value="2"
                                                                                                class="radio-inline"
                                                                                                <?php if ($empd['HARZAD_TYPE'] == 2) echo 'checked'; ?>>
                                                                                            Non-clinical</label><br>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row">
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label for="grade"
                                                                                            class="required control-label">Grade:</label>
                                                                                        <input type="text" name="grade"
                                                                                            value="<?php echo $empd['GRADE']; ?>"
                                                                                            class="form-inps focus"
                                                                                            required maxlength="3">
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label for="grade"
                                                                                            class="required control-label">Step:</label>
                                                                                        <input type="text"
                                                                                            name="gradestep"
                                                                                            value="<?php echo $empd['STEP'] ?>"
                                                                                            class="form-inps focus"
                                                                                            required maxlength="2">
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row">
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label for="doe"
                                                                                            class="required control-label">Date
                                                                                            of Employment:</label>
                                                                                        <input name="doe" type="date"
                                                                                            required
                                                                                            value="<?php echo $empd['EMPDATE']; ?>"
                                                                                            class="form-inps"
                                                                                            max="<?php echo $today; ?>">
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label for="dob"
                                                                                            class="required control-label">Date
                                                                                            of Birth:</label>
                                                                                        <input name="dob" type="date"
                                                                                            class="form-inps"
                                                                                            max="<?php echo $today; ?>">
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row">
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label for="bank"
                                                                                            class="required control-label">Bank:</label>
                                                                                        <select name="bank"
                                                                                            class="form-inps">
                                                                                            <option>Select Bank</option>
                                                                                            <?php
                                                                                        $query = $conn->prepare('SELECT * FROM tbl_bank');
                                                                                        $res = $query->execute();
                                                                                        $out = $query->fetchAll(PDO::FETCH_ASSOC);
                                                                                        while ($row = array_shift($out)) {
                                                                                            echo ('<option value="' . $row['BCODE'] . '"');
                                                                                            if ($row['BCODE'] == $empd['BCODE']) {
                                                                                                echo 'SELECTED';
                                                                                            }
                                                                                            echo ('>' . $row['BNAME'] . '</option>');
                                                                                        }
                                                                                        ?>
                                                                                        </select>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label for="acct_no"
                                                                                            class="required control-label">Account
                                                                                            No:</label>
                                                                                        <input name="acct_no"
                                                                                            type="text"
                                                                                            class="form-inps"
                                                                                            autocomplete="off"
                                                                                            value="<?php echo $empd['ACCTNO'] ?>">
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row">
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label for="pfa"
                                                                                            class="required control-label">PFA:</label>
                                                                                        <select name="pfa"
                                                                                            class="form-inps">
                                                                                            <option value="">Select PFA
                                                                                            </option>
                                                                                            <?php
                                                                                        $query = $conn->prepare('SELECT * FROM tbl_pfa');
                                                                                        $res = $query->execute();
                                                                                        $out = $query->fetchAll(PDO::FETCH_ASSOC);
                                                                                        while ($row = array_shift($out)) {
                                                                                            echo ('<option value="' . $row['PFACODE'] . '"');
                                                                                            if ($row['PFACODE'] == $empd['PFACODE']) {
                                                                                                echo 'SELECTED';
                                                                                            }
                                                                                            echo ('>' . $row['PFANAME'] . '</option>');
                                                                                        }
                                                                                        ?>
                                                                                        </select>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label for="rsa_pin"
                                                                                            class="required control-label">PFA
                                                                                            PIN:</label>
                                                                                        <input name="rsa_pin"
                                                                                            type="text"
                                                                                            class="form-inps"
                                                                                            autocomplete="off"
                                                                                            value="<?php echo $empd['PFAACCTNO'] ?>">
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="submit" class="btn red">Save
                                                                        Details</button>
                                                                    <button type="button" data-dismiss="modal"
                                                                        class="btn btn-primary dark">Close</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                    <?php
                                                }
                                                ?>
                                                </div>
                                            </div>
                                </div>
                                <?php
                                }
                                } catch (PDOException $e) {
                                    echo $e->getMessage();
                                }
                                ?>
                                </tbody>
                                </table>
                                <div id="new-employee" class="modal fade" tabindex="-1" aria-hidden="true"
                                    data-width="800">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content" style="width:800px">
                                            <div class="modal-header modal-title" style="background: #6e7dc7;">
                                                <button type="button" class="close" data-dismiss="modal"
                                                    aria-hidden="true"><span aria-hidden="true">×</span></button>
                                                <h4 class="modal-title">Create New Employee</h4>
                                            </div>
                                            <div class="modal-body">
                                                <form method="post" action="classes/controller.php?act=addNewEmp"
                                                    class="horizontal-form" id="employee_form">
                                                    <div class="row">
                                                        <div class="col-md-12">
                                                            <div class="form-body">
                                                                <h4 class="form-section"><b>Personal Details</b></h4>
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label>Name</label>
                                                                            <input type="text" autocomplete="off"
                                                                                name="namee" id="namee"
                                                                                class="form-control" required
                                                                                placeholder="Enter Full Name (e.g., John Doe)">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label>Oouth Email</label>
                                                                            <input type="email" autocomplete="off"
                                                                                name="email" id="email"
                                                                                class="form-control" required
                                                                                placeholder="surname.firstname@oouth.com"
                                                                                pattern="[a-zA-Z0-9]+\.[a-zA-Z0-9]+@oouth\.com$">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label>Bank</label>
                                                                            <select name="bank" class="form-inps"
                                                                                id="bank" required>
                                                                                <option value="">Select Bank</option>
                                                                                <?php
                                                                                $query = $conn->prepare('SELECT * FROM tbl_bank');
                                                                                $res = $query->execute();
                                                                                $out = $query->fetchAll(PDO::FETCH_ASSOC);
                                                                                while ($row = array_shift($out)) {
                                                                                    echo ('<option value="' . $row['BCODE'] . '">' . $row['BNAME'] . '</option>');
                                                                                }
                                                                                ?>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label>Account No.</label>
                                                                            <input type="number" name="acct_no" required
                                                                                class="form-control"
                                                                                placeholder="Account No"
                                                                                pattern="\d{10}" maxlength="10">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label>Pension FA</label>
                                                                            <select name="pfa" class="form-inps"
                                                                                id="pfa">
                                                                                <option value="">Select PFA</option>
                                                                                <?php
                                                                                $query = $conn->prepare('SELECT * FROM tbl_pfa');
                                                                                $res = $query->execute();
                                                                                $out = $query->fetchAll(PDO::FETCH_ASSOC);
                                                                                while ($row = array_shift($out)) {
                                                                                    echo ('<option value="' . $row['PFACODE'] . '">' . $row['PFANAME'] . '</option>');
                                                                                }
                                                                                ?>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label>RSA PIN.</label>
                                                                            <input type="text" name="rsa_pin"
                                                                                class="form-control"
                                                                                placeholder="PFA PIN">
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
                                                                            <input type="text" readonly name="emp_no"
                                                                                class="form-control" required
                                                                                placeholder="Staff No"
                                                                                value="<?php echo intval($final['nextNo']) + 1 ?>">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label>Date of Employment</label>
                                                                            <div class="input-group date"
                                                                                data-provide="datepicker">
                                                                                <input type="date" required name="doe"
                                                                                    class="form-control">
                                                                                <div class="input-group-addon">
                                                                                    <span
                                                                                        class="glyphicon glyphicon-th"></span>
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
                                                                                <option value="">- - - Select Department
                                                                                    - - -</option>
                                                                                <?php
                                                                                $query = $conn->prepare('SELECT * FROM tbl_dept');
                                                                                $res = $query->execute();
                                                                                $out = $query->fetchAll(PDO::FETCH_ASSOC);
                                                                                while ($row = array_shift($out)) {
                                                                                    echo ('<option value="' . $row['dept_id'] . '">' . $row['dept'] . '</option>');
                                                                                }
                                                                                ?>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label>Designation</label>
                                                                            <input type="text" name="designation"
                                                                                required class="form-control"
                                                                                placeholder="Post">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="callType"
                                                                                class="required control-label">Call Duty
                                                                                Type:</label><br>
                                                                            <label><input name="callType" type="radio"
                                                                                    class="radio-inline" id="payType_01"
                                                                                    value="0" checked> None</label><br>
                                                                            <label><input name="callType" type="radio"
                                                                                    class="radio-inline" id="payType_0"
                                                                                    value="1"> Doctors</label><br>
                                                                            <label><input type="radio" name="callType"
                                                                                    value="2" id="payType_1"
                                                                                    class="radio-inline">
                                                                                Others</label><br>
                                                                            <label><input type="radio" name="callType"
                                                                                    value="3" id="payType_2"
                                                                                    class="radio-inline"> Nurse</label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="hazardType"
                                                                                class="required control-label">Hazard
                                                                                Type:</label><br>
                                                                            <label><input name="hazardType" type="radio"
                                                                                    class="radio-inline"
                                                                                    id="hazardType_0" value="1">
                                                                                Clinical</label><br>
                                                                            <label><input type="radio" name="hazardType"
                                                                                    value="2" id="hazardType_1"
                                                                                    class="radio-inline">
                                                                                Non-clinical</label><br>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="grade"
                                                                                class="required control-label">Grade:</label>
                                                                            <input type="text" name="grade" value=""
                                                                                class="form-inps focus" id="grade"
                                                                                required maxlength="3">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="form-group">
                                                                            <label for="grade"
                                                                                class="required control-label">Step:</label>
                                                                            <input type="text" name="gradestep" value=""
                                                                                class="form-inps focus" id="gradestep"
                                                                                required maxlength="2">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" data-dismiss="modal"
                                                            class="btn btn-outline dark">Cancel</button>
                                                        <button type="submit" name="addemp" class="btn red">Add
                                                            Employee</button>
                                                    </div>
                                                </form>
                                            </div>
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
                                            if (!isset($_GET['item'])) {
                                                $sql = 'SELECT count(staff_id) as "Total" FROM employee';
                                            } else {
                                                $sql = 'SELECT count(staff_id) as "Total" FROM employee where staff_id = "' . $_GET['item'] . '"';
                                            }
                                            $result = $conn->query($sql);
                                            $row = $result->fetch();
                                            $total_pages = ceil($row['Total'] / $results_per_page);
                                            for ($i = 1; $i <= $total_pages; $i++) {
                                                echo '<li class="page-item ';
                                                if ($i == $page) {
                                                    echo ' active"';
                                                }
                                                echo '"><a class="page-link" href="employee.php?page=' . $i . '">' . $i . '</a></li>';
                                            }
                                            ?>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="footer" class="col-md-12 hidden-print">
            Please visit our
            <a href="#" target="_blank">website</a>
            to learn the latest information about the project.
            <span class="text-info"> <span class="label label-info"> 14.1</span></span>
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
            $("#namee").on("blur", function() {
                var namee = $(this).val();
                if (namee.trim() !== '') {
                    $("#email").load("split_name.php", {
                        namee: namee
                    }, function(response, status, xhr) {
                        if (status === "success" && response.trim() !== '') {
                            $("#email").val(response);
                        } else {
                            $("#email").val('');
                            gritter("Error", "Failed to generate email. Please enter manually.",
                                'gritter-item-error', false, true);
                        }
                    });
                }
            });
            $("#email").on("input", function() {
                var email = $(this).val();
                email = email.replace(/@tasce\.com$|\.tasce\.com$/, '');
                email = email.trim();
                if (!email.endsWith('@oouth.com')) {
                    email = email.split('@')[0] + '@oouth.com';
                }
                $(this).val(email);
            });
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
            $("#employee_current_location_id").change(function() {
                $("#form_set_employee_current_location_id").ajaxSubmit(function() {
                    window.location.reload(true);
                });
            });
            $('#employee_form').validate({
                rules: {
                    namee: "required",
                    dept: "required",
                    email: {
                        required: true,
                        pattern: /^[a-zA-Z0-9]+\.[a-zA-Z0-9]+@oouth\.com$/
                    },
                    acct_no: {
                        required: {
                            depends: function(element) {
                                return ($("#bank option:selected").text() !== 'UNPAID');
                            }
                        },
                        minlength: 10,
                        maxlength: 10,
                        number: true
                    }
                },
                messages: {
                    namee: "The name is a required field.",
                    email: "Please enter a valid email in the format surname.firstname@oouth.com",
                    dept: "Please select a department.",
                    acct_no: "Please enter a valid 10-digit account number."
                },
                errorClass: "text-danger",
                errorElement: "span",
                highlight: function(element, errorClass, validClass) {
                    $(element).parents('.form-group').removeClass('has-success').addClass(
                        'has-error');
                },
                unhighlight: function(element, errorClass, validClass) {
                    $(element).parents('.form-group').removeClass('has-error').addClass(
                        'has-success');
                },
                submitHandler: function(form) {
                    var email = $("#email").val();
                    email = email.replace(/@tasce\.com$|\.tasce\.com$/, '').trim();
                    if (!email.endsWith('@oouth.com')) {
                        email = email.split('@')[0] + '@oouth.com';
                    }
                    $("#email").val(email);
                    doEmployeeSubmit(form);
                }
            });
            document.getElementById('item').focus();
        });
        </script>
        <script src="js/tableExport.js"></script>
        <script src="js/main.js"></script>
    </div>
    </div>
    <ul class="ui-autocomplete ui-front ui-menu ui-widget ui-widget-content ui-corner-all" id="ui-id-1" tabindex="0"
        style="display: none;"></ul>
</body>

</html>
<?php
//mysqli_free_result($employee);
?>