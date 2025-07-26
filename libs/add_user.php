<?php

require_once 'App.php';
require_once '../vendor/autoload.php';
require_once '../../config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');
$app = new App();
$response = ['status' => 'error', 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get POST data
        $action = trim($_POST['action'] ?? 'create');
        $staff_id = trim($_POST['staff_id'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $role_id = trim($_POST['roles_id'] ?? '');
        $status_id = trim($_POST['status_id'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $employee_name = trim($_POST['employee_name'] ?? '');

        // Validate input
        if (!in_array($action, ['create', 'update'])) {
            throw new Exception('Invalid action specified.');
        }
        if (empty($username)) {
            throw new Exception('Username is required.');
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address.');
        }
        if (empty($role_id) || !is_numeric($role_id)) {
            throw new Exception('Please select a valid role.');
        }
        if (!in_array($status_id, ['0', '1'])) {
            throw new Exception('Please select a valid status.');
        }
        if ($action === 'create' && empty($employee_name)) {
            throw new Exception('Employee name is required for new users.');
        }

        // Handle create or update
        if ($action === 'create') {
            // Use username as staff_id for new users
            $effective_staff_id = $username;

            // Generate and hash password
            $password = $app->generateStrongPassword();
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Create user
            $result = $app->create_user($effective_staff_id, $username, $hashed_password, $role_id, $status_id);

            if ($result) {
                if ($status_id == '0') {
                    $emailSent = sendPasswordEmail($email, $username, $password, $employee_name);
                    $response = [
                        'status' => 'success',
                        'message' => $emailSent ? 'User created and password sent to email.' : 'User created, but email sending failed.'
                    ];
                } else {
                    $response = ['status' => 'success', 'message' => 'User created, but email not sent (inactive user).'];
                }
            } else {
                throw new Exception('Error creating user.');
            }
        } else {
            // Update user
            $result = $app->create_user($staff_id, $username, null, $role_id,  $status_id);

            if ($result) {
                $response = ['status' => 'success', 'message' => 'User updated successfully.'];
            } else {
                throw new Exception('Error updating user.');
            }
        }
    } catch (Exception $e) {
        $response = ['status' => 'error', 'message' => $e->getMessage()];
    }
}

echo json_encode($response);

function sendPasswordEmail($to, $username, $password, $employee_name) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->SMTPDebug = 0;
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer Debug [$level]: $str");
        };
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Oouth Salary System Account Details';
        $mail->Body = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Account Details</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <tr>
            <td style="padding: 20px 0; text-align: center; background-color: #1E3A8A; border-radius: 8px 8px 0 0;">
                <img src="https://oouthsalary.com.ng/img/oouth_logo.png" alt="Oouth Logo" style="max-width: 200px; height: auto;">
            </td>
        </tr>
        <tr>
            <td style="padding: 40px 30px;">
                <h1 style="color: #1E3A8A; font-size: 24px; margin: 0 0 20px;">Welcome to Oouth Salary System</h1>
                <p style="color: #333333; font-size: 16px; line-height: 1.5; margin: 0 0 20px;">
                    Hello ' . htmlspecialchars($employee_name) . ',<br><br>
                    Your account has been successfully created. Below are your login details:
                </p>
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="width: 100%; background-color: #f9fafb; padding: 20px; border-radius: 6px; margin-bottom: 20px;">
                    <tr>
                        <td style="font-size: 16px; color: #333333; padding: 10px 0;">
                            <strong>Username:</strong> ' . htmlspecialchars($username) . '
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size: 16px; color: #333333; padding: 10px 0;">
                            <strong>Password:</strong> ' . htmlspecialchars($password) . '
                        </td>
                    </tr>
                </table>
                <p style="color: #333333; font-size: 16px; line-height: 1.5; margin: 0 0 20px;">
                    Please log in and change your password as soon as possible for security purposes.
                </p>
                <a href="https://oouthsalary.com.ng/" style="display: inline-block; padding: 12px 24px; background-color: #10B981; color: #ffffff; text-decoration: none; font-size: 16px; border-radius: 6px; font-weight: bold;">Log In Now</a>
            </td>
        </tr>
        <tr>
            <td style="padding: 20px; background-color: #f4f4f4; text-align: center; color: #666666; font-size: 14px; border-radius: 0 0 8px 8px;">
                <p style="margin: 0 0 10px;">Oouth Salary System</p>
                <p style="margin: 0;">For support, contact us at <a href="mailto:support@oouthsalary.com.ng" style="color: #1E3A8A; text-decoration: none;">support@oouthsalary.com.ng</a></p>
                <p style="margin: 0;">&copy; ' . date('Y') . ' Oouth Salary System. All rights reserved.</p>
            </td>
        </tr>
    </table>
</body>
</html>';
        $mail->AltBody = "Hello " . $employee_name . ",\n\nYour account has been created in the Oouth Salary System.\n\nLogin Details:\nUsername: $username\nPassword: $password\n\nPlease log in at https://oouthsalary.com.ng/login and change your password as soon as possible.\n\nFor support, contact support@oouthsalary.com.ng.\n\nOouth Salary System";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>