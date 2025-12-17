<?php
/**
 * AJAX endpoint for editing deductions in deductiontable
 * Handles both value and percentage updates
 */

require_once 'Connections/paymaster.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $response = ['status' => false, 'message' => ''];

    if (!isset($input['data']) || empty($input['data'])) {
        throw new Exception('No data provided');
    }

    foreach ($input['data'] as $dedId => $data) {
        // Validate ded_id
        $dedId = intval($dedId);
        if ($dedId <= 0) {
            throw new Exception('Invalid ded_id');
        }

        // Check if deduction exists
        $checkStmt = $conn->prepare('SELECT ded_id FROM deductiontable WHERE ded_id = ? LIMIT 1');
        $checkStmt->execute([$dedId]);
        if (!$checkStmt->fetch()) {
            throw new Exception('Deduction not found');
        }

        // Build update query based on provided fields
        $updateFields = [];
        $updateParams = [':ded_id' => $dedId];

        // Handle value field
        if (isset($data['value'])) {
            $value = floatval($data['value']);
            if ($value < 0) {
                throw new Exception('Invalid value: must be non-negative');
            }
            $updateFields[] = '`value` = :value';
            $updateParams[':value'] = $value;
        }

        // Handle percentage field
        if (isset($data['percentage'])) {
            $percentage = floatval($data['percentage']);
            if ($percentage < 0 || $percentage > 100) {
                throw new Exception('Invalid percentage: must be between 0 and 100');
            }
            $updateFields[] = 'percentage = :percentage';
            $updateParams[':percentage'] = $percentage;
        }

        if (empty($updateFields)) {
            throw new Exception('No valid fields to update');
        }

        // Update deduction
        $sql = 'UPDATE deductiontable SET ' . implode(', ', $updateFields) . ' WHERE ded_id = :ded_id';
        $stmt = $conn->prepare($sql);

        foreach ($updateParams as $key => $value) {
            $stmt->bindValue($key, $value, is_float($value) ? PDO::PARAM_STR : PDO::PARAM_INT);
        }

        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $response['status'] = true;
            $response['message'] = 'Deduction updated successfully';
        } else {
            throw new Exception('No rows updated');
        }
    }

    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}
?>