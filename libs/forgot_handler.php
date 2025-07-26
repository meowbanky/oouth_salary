<?php
require_once '../Connections/paymaster.php'; // $conn (PDO)
require_once 'App.php';
require_once '../vendor/autoload.php';
require_once '../../config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = trim($_POST['user_or_email'] ?? '');
    if (empty($input)) {
        $response['message'] = "Please provide your username or email address.";
        echo json_encode($response); exit;
    }

    // Search user by username or email
    $stmt = $conn->prepare(
        "SELECT e.EMAIL, u.username, e.NAME
         FROM employee e
         LEFT JOIN username u ON u.staff_id = e.staff_id
         WHERE u.username = :input OR e.EMAIL = :input OR e.alternate_email = :input
         LIMIT 1"
    );
    $stmt->execute([':input' => $input]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || empty($user['EMAIL'])) {
        $response['message'] = "No matching user or valid email found.";
        echo json_encode($response); exit;
    }

    // Generate 6-digit OTP
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    // Store OTP in password_reset_tokens
    $ins = $conn->prepare("INSERT INTO password_reset_tokens (email, otp, expires_at) VALUES (?, ?, ?)");
    $ins->execute([$user['EMAIL'], $otp, $expires_at]);

    // Send OTP to email
    $sent = sendResetOTPEmail($user['EMAIL'], $user['username'], $otp, $user['NAME']);

    if ($sent) {
        $response = [
            'success' => true,
            'message' => "A password reset code has been sent to your registered email address.",
            'email' => $user['EMAIL']
        ];
    } else {
        $response['message'] = "Failed to send email. Please contact support.";
    }
}
echo json_encode($response);


// Email sending function
function sendResetOTPEmail($to, $username, $otp, $name) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = 'OOUTH Salary Manager Password Reset Code';
        $mail->Body = '
            <div style="font-family:sans-serif;max-width:520px;margin:auto;border-radius:8px;border:1px solid #dbeafe;overflow:hidden;">
                <div style="background:#1E3A8A;padding:20px 0;text-align:center;">
                    <img src="https://oouthsalary.com.ng/img/oouth_logo.png" style="max-width:150px">
                </div>
                <div style="padding:30px;">
                    <h2 style="color:#1E3A8A;">Password Reset Request</h2>
                    <p>Hi <b>' . htmlspecialchars($name ?: $username) . '</b>,</p>
                    <p>Your OOUTH Salary Manager password reset code is:</p>
                    <div style="background:#f0f6ff;padding:24px 0;margin:18px 0;border-radius:6px;text-align:center;font-size:2rem;letter-spacing:7px;color:#1E3A8A;font-weight:bold;">'
                        . htmlspecialchars($otp) .
                    '</div>
                    <p>This code is valid for 15 minutes. If you did not request a password reset, please ignore this email.</p>
                    <br>
                    <a href="https://oouthsalary.com.ng/reset_password.php" style="background:#1E3A8A;color:#fff;text-decoration:none;padding:12px 30px;border-radius:5px;font-weight:bold;display:inline-block;margin-top:10px;">Reset Password</a>
                </div>
                <div style="background:#f1f5f9;text-align:center;padding:14px;color:#4b5563;font-size:13px;">
                    &copy; ' . date('Y') . ' OOUTH Salary Manager
                </div>
            </div>
        ';
        $mail->AltBody = "Hi " . $name . ",\nYour password reset code is: $otp\nThis code expires in 15 minutes.\n\nIf you did not request a password reset, please ignore this message.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Reset email error: " . $mail->ErrorInfo);
        return false;
    }
}
?>
