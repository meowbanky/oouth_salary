<?php
/**
 * AJAX endpoint for deleting deductions from deductiontable
 */

require_once 'Connections/paymaster.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $response = ['status' => false, 'message' => ''];

    if (!isset($input['ded_id']) || empty($input['ded_id'])) {
        throw new Exception('ded_id is required');
    }

    $dedId = intval($input['ded_id']);
    
    if ($dedId <= 0) {
        throw new Exception('Invalid ded_id');
    }

    // Check if deduction exists
    $checkStmt = $conn->prepare('SELECT ded_id, allowcode FROM deductiontable WHERE ded_id = ? LIMIT 1');
    $checkStmt->execute([$dedId]);
    $deduction = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$deduction) {
        throw new Exception('Deduction not found');
    }

    // Optional: Check if deduction is being used in other tables
    // For example, check if it's referenced in tbl_master or allow_deduc
    $checkUsageStmt = $conn->prepare('
        SELECT COUNT(*) as count 
        FROM tbl_master 
        WHERE allow_id = ? AND type = 2
    ');
    $checkUsageStmt->execute([$deduction['allowcode']]);
    $usageResult = $checkUsageStmt->fetch(PDO::FETCH_ASSOC);
    
    // Note: This checks if the allowcode is used, not the specific ded_id
    // You may want to adjust this check based on your business logic
    
    // Delete the deduction
    $deleteStmt = $conn->prepare('DELETE FROM deductiontable WHERE ded_id = ?');
    $deleteStmt->execute([$dedId]);

    if ($deleteStmt->rowCount() > 0) {
        $response['status'] = true;
        $response['message'] = 'Deduction deleted successfully';
    } else {
        throw new Exception('No rows deleted. Deduction may not exist.');
    }

    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}
?>