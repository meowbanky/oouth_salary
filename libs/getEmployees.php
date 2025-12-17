<?php
include '../services/session.php';

if(!isset($_SESSION['logged_in']) AND ($_SESSION['logged_in'] != 1)){
    header('Location:../index.php');
}
require 'App.php';

$App = new App;

$noOfDays = $App->getDaysOfMonth($_SESSION['activeperiodDescription']);

$selectDrops = $App->selectDrop();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!empty($_POST['delete'])) {
        $deletions = $_POST['delete'];
        $deleteSuccessfully = '';
        // Process each deletion
        foreach ($deletions as $delete) {
            list($temp_id, $staff_id) = explode('/', $delete);
            $array_deletions = [
                ':temp_id' => $temp_id
            ];
            $deleteSuccessfully = $App->selectOne("DELETE FROM allow_deduc WHERE temp_id = :temp_id",$array_deletions);

        }
        echo "Selected allowances have been deleted.";
    }
}

if(isset($_POST['search'])) {
    $staff_id = $_POST['search'];
    $_SESSION['staff'] = $staff_id;
}else {
    $staff_id = $_SESSION['staff'];
}

$employeeDetails = $App->getEmployeeDetails($staff_id);

$employeePayslip = $App->getEmployeeDetailsPayslip($staff_id,$_SESSION['currentactiveperiod']);

$empAllowances = $App->getEmployeesEarnings($staff_id,1);
$empDeductions = $App->getEmployeesEarnings($staff_id,2);

$payslipStatus = $App->isPayslipAvailable($staff_id,$_SESSION['currentactiveperiod']);

$paySlips = $App->getPaySlip($staff_id,$_SESSION['currentactiveperiod']);

?>