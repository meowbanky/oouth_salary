<?php
require_once '../libs/App.php';
$App = new App();
$App->checkAuthentication();
require_once '../vendor/autoload.php';
require_once '../config/config.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$respon = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(!isset($_POST['staff_id'])){
        $staff_id = -1;
    }else{
        $staff_id = $_POST['staff_id'];
    }
}
    $staff_id = trim($_POST['staff_id']);
    $username = trim($_POST['username']);
    $role_id = trim($_POST['roles_id']);
    $status_id = trim($_POST['status_id']);
    $email = trim($_POST['email']);
    $employee_name = trim($_POST['email']);

    // Validate input
    if (empty($staff_id)){
        $response = ["status" => "error", "message" => "Staff ID is empty."];
        echo json_encode($response);
    }
    if(empty($username))  {
         $response = ["status" => "error", "message" => "Username is required."];
        echo json_encode($response);
        exit();
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response = ["status" => "error", "message" => "Invalid email address."];
        echo json_encode($response);
        exit();
    }
    if(empty($role_id)){
        $response = ["status" => "error", "message" => "Role is required."];
        echo json_encode($response);
        exit();
    }

    // Generate strong password
    $password = $App->generateStrongPassword();
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);


    $result = $App->create_user($staff_id, $username, $hashed_password, $role_id, $status_id);

    if ($result) {
        if(!empty($email) && $status_id != 1) {
            sendPasswordEmail($email, $username, $password,$employee_name);
        }
        $response = ["status" => "success", "message" => "User created/updated and password sent to email."];
    } else {
        $response = ["status" => "error", "message" => "Error creating/updating user."];
    }
}
echo json_encode($response);
function sendPasswordEmail($to, $username, $password,$employee_name) {
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host = HOST_MAIL; // Set the SMTP server to send through
        $mail->SMTPAuth = true;
        $mail->Username = USERNAME; // SMTP username
        $mail->Password = PASSWORD; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        //Recipients
        $mail->setFrom('no-reply@tascesalary.com.ng', $_SESSION['businessname']);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your New Account Details';
        $mail->Body    = "Hello ".$employee_name.",<br><br>Your account has been created. Here are your login details:<br>Username: $username<br>Password: $password<br><br>Please change your password after your first login.";
        $mail->AltBody = "Hello,\n\nYour account has been created. Here are your login details:\nUsername: $username\nPassword: $password\n\nPlease change your password after your first login.";

        $mail->send();
    } catch (Exception $e) {
        // Handle the error
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}
?>
