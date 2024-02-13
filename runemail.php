<?php
include_once('classes/model.php');
require_once('Connections/paymaster.php');
require_once('classes/create_email.php');

mysqli_select_db($salary, $database_salary);

$query_masterTransaction = "SELECT staff_id,NAME FROM employee WHERE ISNULL(EMAIL) = TRUE AND STATUSCD = 'A'";

$masterTransaction = mysqli_query($salary, $query_masterTransaction) or die(mysqli_error($salary));
$row_masterTransaction = mysqli_fetch_assoc($masterTransaction);
$totalRows_masterTransaction = mysqli_num_rows($masterTransaction);
$total = $totalRows_masterTransaction;

do {
    $originalName = strtolower($row_masterTransaction['NAME']);
    $originalName = explode(" ", $originalName);
    $originalName = $originalName[0] . '.' . $originalName[1] . '@oouth.com';
    createEmail($originalName);
    echo $originalName;
    echo '<br>';
$staff_id = $row_masterTransaction['staff_id'];
    $updateQuery = "UPDATE employee SET EMAIL = '{$originalName}' WHERE staff_id = {$staff_id}";
    $runQuery = mysqli_query($salary, $updateQuery);
    
} while ($row_masterTransaction = mysqli_fetch_assoc($masterTransaction));
