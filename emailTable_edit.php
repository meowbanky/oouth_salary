<?php
ini_set('max_execution_time', 300);
require_once 'Connections/paymaster.php';
require_once 'classes/model.php';
// require_once 'classes/create_email.php'; // Uncomment if needed

header('Content-Type: application/json');

session_start();

// Validate session
if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) === '') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Please log in.']);
    exit;
}

// Validate POST data
$staff_id = isset($_POST['id']) ? trim($_POST['id']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if (empty($staff_id) || empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Staff ID and email are required.']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
    exit;
}

try {
    // Verify staff_id exists and is active
    $stmt = $conn->prepare("SELECT staff_id FROM employee WHERE staff_id = :staff_id AND STATUSCD = 'A'");
    $stmt->bindParam(':staff_id', $staff_id, PDO::PARAM_STR);
    $stmt->execute();
    if (!$stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'No active employee found with this Staff ID.']);
        exit;
    }

    // Update email
    $stmt = $conn->prepare("UPDATE employee SET EMAIL = :email WHERE staff_id = :staff_id");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':staff_id', $staff_id, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        // Uncomment if createEmail is implemented
        // try {
        //     createEmail($email);
        // } catch (Exception $e) {
        //     error_log("createEmail error: " . $e->getMessage());
        // }
        echo json_encode(['status' => 'success', 'message' => 'Email updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No changes made to email.']);
    }
} catch (PDOException $e) {
    error_log("Update error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Failed to update email: ' . $e->getMessage()]);
}
?>