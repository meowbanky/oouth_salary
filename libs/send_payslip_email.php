<?php
session_start();
require_once '../config/config.php';
require_once '../report/pdf.php';

// Check if user is logged in
// if (!isset($_SESSION['user_id'])) {
//     http_response_code(401);
//     echo json_encode(['error' => 'Unauthorized access']);
//     exit;
// }

// Get parameters
$staff_id = $_POST['staff_id'] ?? '';
$period = $_POST['period'] ?? '';
$email = $_POST['email'] ?? '';

if (empty($staff_id) || empty($period)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

try {
    // Use the existing working function from pdf.php
    $result = generateAndSendPayslip($staff_id, $period);
    
    // Check if email was sent successfully
    if (strpos($result, 'successfully') !== false) {
        echo json_encode([
            'success' => true,
            'message' => $result
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'error' => $result
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'An error occurred while sending the email: ' . $e->getMessage()
    ]);
}
?>