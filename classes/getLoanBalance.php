<?php
//load_data.php  
require_once('../Connections/paymaster.php');
mysqli_select_db($salary, $database_salary);
$output = '';
$staff_id = $_POST['curremployee'];
$allow_id = $_POST['newdeductioncodeloan'];


$sql_loan = "SELECT SUM(ifnull(tbl_debt.principal,0))+SUM(ifnull(tbl_debt.interest,0)) as loan FROM tbl_debt WHERE staff_id = '" . $staff_id . "' AND allow_id = '" . $allow_id . "'
 GROUP BY staff_id";
$result_loan = mysqli_query($salary, $sql_loan);
$row_loan = mysqli_fetch_assoc($result_loan);
$total_loan = mysqli_num_rows($result_loan);
if ($total_loan == 0) {
    $row_loan['loan'] = 0;
}

$sql_repayment = "SELECT SUM(ifnull(tbl_repayment.value,0)) as repayment FROM tbl_repayment WHERE staff_id = '" . $staff_id . "' and allow_id = '" . $allow_id . "'";
$result_repayment = mysqli_query($salary, $sql_repayment);
$row_repayment = mysqli_fetch_assoc($result_repayment);
$total_repayment = mysqli_num_rows($result_repayment);
if ($total_repayment == 0) {
    $row_repayment['repayment'] = 0;
}


$balance = $row_loan['loan'] - $row_repayment['repayment'];
//print number_format($balance);
echo $balance;
