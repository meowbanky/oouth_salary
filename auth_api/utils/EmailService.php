<?php
class EmailService {
private $mailer;

public function __construct() {
// Initialize your email service (PHPMailer, Laravel Mail, etc.)
// This is just a placeholder - implement according to your email setup
}

public function sendApprovalNotification($staffEmail, $staffName, $changes) {
$subject = 'Profile Changes Approved';
$message = "Dear $staffName,\n\n";
$message .= "Your profile changes have been approved.\n\n";
$message .= "Changes:\n";
foreach ($changes as $field => $value) {
$message .= "- $field: $value\n";
}

// Send email using your preferred method
// This is just a placeholder
mail($staffEmail, $subject, $message);
}

public function sendRejectionNotification($staffEmail, $staffName, $reason) {
$subject = 'Profile Changes Rejected';
$message = "Dear $staffName,\n\n";
$message .= "Your profile changes have been rejected.\n\n";
$message .= "Reason: $reason\n";

// Send email using your preferred method
mail($staffEmail, $subject, $message);
}
}