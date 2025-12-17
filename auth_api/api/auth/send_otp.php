<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../config/EmailConfig.php';



require_once __DIR__ .'/../../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$database = new Database();
try {
    $db = $database->getConnection();
} catch (\Exception $e) {

}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['email']) || !isset($data['otp'])) {
        throw new Exception('Email and OTP are required');
    }

    $email = $data['email'];
    $otp = $data['otp'];

    // Store OTP in database
    $query = "INSERT INTO password_reset_tokens (email, otp, expires_at) 
              VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))";
    $stmt = $db->prepare($query);
    $stmt->execute([$email, $otp]);



    $query = "SELECT alternate_email FROM employee WHERE email = :email";
    $stmt1 = $db->prepare($query);
    $stmt1->bindParam(':email', $email);
    $stmt1->execute();
    
    $user = $stmt1->fetch();
    $cc_email = $user['alternate_email'] ?? null;

    // Send email
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = EmailConfig::SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = EmailConfig::SMTP_USERNAME;
    $mail->Password = EmailConfig::SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = EmailConfig::SMTP_PORT;

    $mail->setFrom(EmailConfig::SMTP_FROM, 'OOUTH Password Reset');
    $mail->addAddress($email);
    if ($cc_email) {
        $mail->addCC($cc_email);
    }
    $mail->isHTML(true);
    $mail->Subject = 'Password Reset OTP';
    $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2>OOUTH Password Reset</h2>
            <p>Your OTP for password reset is: <strong style='font-size: 24px;'>$otp</strong></p>
            <p>This code will expire in 10 minutes.</p>
            <p>If you didn't request this reset, please ignore this email.</p>
        </div>";

    $mail->send();

    echo json_encode([
        'success' => true,
        'message' => 'OTP sent successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}