<?php
require_once('../Connections/paymaster.php');
include_once('../classes/model.php'); 
session_start();

// Set content type to JSON
header('Content-Type: application/json');

$recordtime = date('Y-m-d H:i:s');

$staffID = $_POST['curremployee'];
$DaysToCal = $_POST['daysToCal'];
$no_days = $_POST['no_days'];

$response = array('success' => false, 'message' => '', 'data' => null);

try {
    // Get current allowances
    $query = $conn->prepare('SELECT allow_deduc.`value`,allow_deduc.allow_id,allow_deduc.temp_id,tbl_earning_deduction.edDesc FROM
                            tbl_earning_deduction INNER JOIN allow_deduc ON tbl_earning_deduction.ed_id = allow_deduc.allow_id
                            WHERE transcode = ? and staff_id = ? order by allow_id asc');
    $fin = $query->execute(array('01', $staffID));
    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate prorated values and save to prorate_allow_deduc
    foreach ($res as $row => $link2) {
        $vals = ($DaysToCal * $link2['value'])/$no_days;
        
        // Delete existing prorate record
        $query = 'DELETE FROM prorate_allow_deduc where staff_id = ? and allow_id = ?';
        $conn->prepare($query)->execute(array($staffID, $link2['allow_id']));
        
        // Insert new prorate record
        $insertQry = 'INSERT INTO prorate_allow_deduc (staff_id,allow_id,value,transcode,inserted_by,date_insert,counter) values(?,?,?,?,?,?,1)';
        $conn->prepare($insertQry)->execute(array($staffID, $link2['allow_id'], $vals, 1, $_SESSION['SESS_MEMBER_ID'], $recordtime));
    }
    
    // Get saved prorate data
    $query = $conn->prepare('SELECT prorate_allow_deduc.`value`,prorate_allow_deduc.allow_id,prorate_allow_deduc.temp_id,tbl_earning_deduction.edDesc FROM
                            tbl_earning_deduction INNER JOIN prorate_allow_deduc ON tbl_earning_deduction.ed_id = prorate_allow_deduc.allow_id
                            WHERE transcode = ? and staff_id = ? order by allow_id asc');
    $fin = $query->execute(array('01', $staffID));
    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    
    // Delete original allowances
    foreach ($res as $row => $link2) {
        $query = 'DELETE FROM allow_deduc where staff_id = ? and allow_id = ?';
        $conn->prepare($query)->execute(array($staffID, $link2['allow_id']));
    }
    
    // Insert prorated allowances back to allow_deduc
    $query = $conn->prepare('INSERT INTO allow_deduc (staff_id,allow_id,value,transcode,inserted_by,date_insert,counter) 
                            SELECT prorate_allow_deduc.staff_id, prorate_allow_deduc.allow_id,prorate_allow_deduc.`value`, 
                            prorate_allow_deduc.transcode,inserted_by,date_insert,counter 
                            FROM prorate_allow_deduc WHERE transcode = ? and staff_id = ? order by allow_id asc');
    $fin = $query->execute(array('01', $staffID));
    
    // Update employee step
    $query = $conn->prepare("UPDATE employee SET STEP = CONCAT(STEP,'P') WHERE staff_id = ?");
    $fin = $query->execute(array($staffID));
    
    // Delete deductions
    $query = 'DELETE FROM allow_deduc where staff_id = ? and transcode = ?';
    $conn->prepare($query)->execute(array($staffID, 2));
    
    $response['success'] = true;
    $response['message'] = 'Prorate allowances saved successfully';
    $response['data'] = $res;
    
} catch(PDOException $e) {
    $response['success'] = false;
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch(Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?>
