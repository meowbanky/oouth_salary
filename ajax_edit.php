<?php
require_once 'Connections/paymaster.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $response = ['status' => false, 'message' => ''];

    if (!isset($input['data']) || empty($input['data'])) {
        throw new Exception('No data provided');
    }

    foreach ($input['data'] as $id => $data) {
        $value = isset($data['value']) ? floatval($data['value']) : null;
        if ($value === null || $value < 0) {
            throw new Exception('Invalid value');
        }

        $sql = 'UPDATE allowancetable SET `value` = :value WHERE allow_id = :id';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':value', $value, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $response['status'] = true;
            $response['message'] = 'Value updated successfully';
        } else {
            throw new Exception('No rows updated');
        }
    }

    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}
?>