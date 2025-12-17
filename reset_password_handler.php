<?php
require_once 'Connections/paymaster.php'; // $conn (PDO)
header('Content-Type: application/json');

$email = trim($_POST['email'] ?? '');
$otp = trim($_POST['otp'] ?? '');
$password = trim($_POST['password'] ?? '');

if (!$email || !$otp || !$password) {
    echo json_encode(['success' => false, 'message' => 'Missing required field.']); exit;
}

// Check if OTP is valid and not expired or used
$stmt = $conn->prepare("SELECT * FROM password_reset_tokens WHERE email = ? AND otp = ? AND used = 0 AND expires_at >= NOW() ORDER BY id DESC LIMIT 1");
$stmt->execute([$email, $otp]);
$token = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$token) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired reset code.']); exit;
}

// Get staff_id for this email
$stmt = $conn->prepare("SELECT staff_id FROM employee WHERE EMAIL = ? OR alternate_email = ? LIMIT 1");
$stmt->execute([$email, $email]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Email not found in staff records.']); exit;
}
$staff_id = $row['staff_id'];

// Update password in username table
$new_hash = password_hash($password, PASSWORD_BCRYPT);
$update = $conn->prepare("UPDATE username SET password = ? WHERE staff_id = ?");
if ($update->execute([$new_hash, $staff_id])) {
    // Mark token as used
    $conn->prepare("UPDATE password_reset_tokens SET used = 1 WHERE id = ?")->execute([$token['id']]);
    echo json_encode(['success' => true, 'message' => 'Password reset successful! You can now login.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Could not update password.']);
}
?>
