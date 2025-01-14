<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/Database.php';

$database = new Database();
try {
    $db = $database->getConnection();
} catch (\Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error'
    ]);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['staff_id']) || !isset($data['otp']) || !isset($data['new_password'])) {
        throw new Exception('Required fields are missing');
    }

    $staffId = $data['staff_id'];
    $newPassword = $data['new_password'];

    // Verify OTP exists and is valid
    $query = "SELECT * FROM password_reset_tokens 
              WHERE email = (SELECT EMAIL FROM employee WHERE staff_id = ?) 
              AND otp = ? 
              AND used = 0 
              AND expires_at > NOW() 
              ORDER BY created_at DESC 
              LIMIT 1";

    $stmt = $db->prepare($query);
    $stmt->execute([$staffId, $data['otp']]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Invalid or expired OTP');
    }

    // Update password in tbl_users
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    $updateQuery = "UPDATE tbl_users 
                   SET password_hash = ?,
                       plain_password = ?
                   WHERE staff_id = ?";

    $updateStmt = $db->prepare($updateQuery);
    $success = $updateStmt->execute([
        $passwordHash,
        $newPassword,
        $staffId
    ]);

    if (!$success) {
        throw new Exception('Failed to update password');
    }

    // Mark the OTP as used
    $markUsedQuery = "UPDATE password_reset_tokens 
                     SET used = 1 
                     WHERE email = (SELECT EMAIL FROM employee WHERE staff_id = ?) 
                     AND otp = ?";

    $markUsedStmt = $db->prepare($markUsedQuery);
    $markUsedStmt->execute([$staffId, $data['otp']]);

    echo json_encode([
        'success' => true,
        'message' => 'Password reset successful'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>