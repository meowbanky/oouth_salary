<?php
require_once 'cron_function.php';

$recipientEmails = getRecipientEmail($conn);

foreach ($recipientEmails as $recipientEmail) {
    $deduction = $recipientEmail['allow_id'];
    $deduction_text = $recipientEmail['ed'];
    
    sendEmail($period_text, $period, $code, $conn, $deduction_text, $period_text, $recipientEmail['email'], $deduction, $deduction_text, $recipientEmail['bcc']);
}
?>