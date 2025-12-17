<?php
/**
 * AJAX endpoint for adding new deductions to deductiontable
 */

require_once 'Connections/paymaster.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $response = ['status' => false, 'message' => ''];

    // Validate required fields
    if (!isset($input['allowcode']) || empty($input['allowcode'])) {
        throw new Exception('Deduction item (allowcode) is required');
    }

    $allowcode = intval($input['allowcode']);
    
    if ($allowcode <= 0) {
        throw new Exception('Invalid deduction item');
    }

    // Check if deduction item exists
    $checkStmt = $conn->prepare('SELECT ed_id, edDesc FROM tbl_earning_deduction WHERE ed_id = ? AND edType > 1 AND status = "Active" LIMIT 1');
    $checkStmt->execute([$allowcode]);
    $deductionItem = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$deductionItem) {
        throw new Exception('Selected deduction item not found or inactive');
    }

    // Get and validate other fields
    $grade = isset($input['grade']) ? trim($input['grade']) : null;
    $step = isset($input['step']) ? trim($input['step']) : null;
    $ratetype = isset($input['ratetype']) && $input['ratetype'] !== '' ? intval($input['ratetype']) : null;
    $percentage = isset($input['percentage']) && $input['percentage'] !== '' ? floatval($input['percentage']) : null;
    $value = isset($input['value']) && $input['value'] !== '' ? floatval($input['value']) : null;
    $category = isset($input['category']) ? trim($input['category']) : null;

    // Validate percentage if provided
    if ($percentage !== null && ($percentage < 0 || $percentage > 100)) {
        throw new Exception('Percentage must be between 0 and 100');
    }

    // Validate value if provided
    if ($value !== null && $value < 0) {
        throw new Exception('Value must be non-negative');
    }

    // Insert new deduction
    $insertStmt = $conn->prepare('
        INSERT INTO deductiontable (allowcode, grade, step, ratetype, percentage, `value`, category)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');
    
    $insertStmt->execute([
        $allowcode,
        $grade,
        $step,
        $ratetype,
        $percentage,
        $value,
        $category
    ]);

    $newDedId = $conn->lastInsertId();

    if ($newDedId) {
        // Get the inserted record with joined data
        $getStmt = $conn->prepare('
            SELECT 
                dt.ded_id,
                dt.allowcode,
                ted.edDesc,
                dt.ratetype,
                dt.percentage,
                CONCAT(dt.grade, "/", dt.step) AS grade_step,
                dt.`value`
            FROM deductiontable dt
            INNER JOIN tbl_earning_deduction ted ON dt.allowcode = ted.ed_id
            WHERE dt.ded_id = ?
            LIMIT 1
        ');
        $getStmt->execute([$newDedId]);
        $newDeduction = $getStmt->fetch(PDO::FETCH_ASSOC);
        
        $response['status'] = true;
        $response['message'] = 'Deduction added successfully';
        $response['data'] = $newDeduction;
    } else {
        throw new Exception('Failed to insert deduction');
    }

    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}
?>