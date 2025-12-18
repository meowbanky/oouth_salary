<?php
include_once('../classes/model.php');
require_once('../Connections/paymaster.php');
require_once('pdf.php');
require __DIR__.'/../vendor/autoload.php';

// Get parameters
$staff_no = isset($_POST['staff_no']) ? $_POST['staff_no'] : null;
$periodsJson = isset($_POST['periods']) ? $_POST['periods'] : '[]';
$customEmail = isset($_POST['custom_email']) ? trim($_POST['custom_email']) : '';

if (!$staff_no) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Staff number is required']);
    exit;
}

$periods = json_decode($periodsJson, true);
if (empty($periods) || !is_array($periods)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No periods selected']);
    exit;
}

try {
    if (!$conn) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    // Get employee details
    $query = $conn->prepare('SELECT employee.staff_id, employee.NAME, employee.EMAIL FROM employee WHERE staff_id = ?');
    $query->execute([$staff_no]);
    $employee = $query->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit;
    }
    
    // Use custom email if provided, otherwise use employee's default email
    $emailToUse = (!empty($customEmail) && filter_var($customEmail, FILTER_VALIDATE_EMAIL)) 
        ? $customEmail 
        : ($employee['EMAIL'] ?? '');
    
    if (empty($emailToUse)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No email address available for this employee']);
        exit;
    }
    
    // Generate PDFs for all selected periods
    $pdfFiles = [];
    $totalPeriods = count($periods);
    $processed = 0;
    
    foreach ($periods as $periodId) {
        $processed++;
        $percent = intval(($processed / $totalPeriods) * 100);
        
        try {
            // Generate PDF for this period
            $conn_pdf = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
            $conn_pdf->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $employeeDetails = fetchEmployeeDetails($conn_pdf, $staff_no, $periodId);
            $payslipDetails = fetchPayslipDetails($conn_pdf, $staff_no, $periodId);
            $fullPeriod = getPayPeriod($conn_pdf, $periodId);
            
            if ($employeeDetails && $payslipDetails) {
                $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                configurePDF($pdf);
                $htmlContent = generatePayslipHtml($employeeDetails, $payslipDetails, $fullPeriod, $pdf);
                $pdf->writeHTMLCell(0, 0, '', '', $htmlContent, 0, 1, 0, true, '', true);
                
                // Save PDF to temporary file
                $tempFile = tempnam(sys_get_temp_dir(), 'payslip_');
                $pdf->Output($tempFile, 'F');
                
                // Sanitize period name for filename
                $periodFileName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fullPeriod);
                $pdfFiles[] = [
                    'file' => $tempFile,
                    'filename' => 'OOUTH_Payslip_' . $periodFileName . '.pdf',
                    'period' => $fullPeriod
                ];
            }
            
            // Send progress update
            echo str_repeat(' ', 1024);
            echo '<script>
            if (parent && parent.document) {
                const progressBar = parent.document.querySelector(".progress-bar");
                if (progressBar) {
                    progressBar.style.width = "' . $percent . '%";
                }
            }
            </script>';
            ob_flush();
            flush();
            
        } catch (Exception $e) {
            error_log("Error generating PDF for period $periodId: " . $e->getMessage());
            // Continue with other periods even if one fails
        }
    }
    
    if (empty($pdfFiles)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No payslips could be generated']);
        exit;
    }
    
    // Send email with all PDFs attached
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port = SMTP_PORT;
    $mail->SMTPDebug = 0;
    
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->addAddress($emailToUse, $employee['NAME']);
    $mail->addReplyTo(SMTP_REPLYTO_EMAIL, SMTP_REPLYTO_NAME);
    
    $mail->isHTML(true);
    $periodsList = implode(', ', array_column($pdfFiles, 'period'));
    $mail->Subject = $employee['NAME'] . ' - Payslips for Multiple Periods';
    
    $mail->Body = '<html><body>';
    $mail->Body .= '<div style="padding:20px;font-family:Arial,sans-serif;">';
    $mail->Body .= '<img src="https://oouth.com/images/logo.png" alt="OOUTH Logo" style="margin-bottom:20px;">';
    $mail->Body .= '<h2 style="color:#356ae9;margin-bottom:15px;">Payslips</h2>';
    $mail->Body .= '<p>Hi <strong>' . htmlspecialchars($employee['NAME']) . '</strong>,</p>';
    $mail->Body .= '<p>Please find attached your payslips for the following periods:</p>';
    $mail->Body .= '<ul>';
    foreach ($pdfFiles as $pdfInfo) {
        $mail->Body .= '<li><strong>' . htmlspecialchars($pdfInfo['period']) . '</strong></li>';
    }
    $mail->Body .= '</ul>';
    $mail->Body .= '<p>We hope that you find the information in the payslips accurate and helpful.</p>';
    $mail->Body .= '<p>Please review your payslips and let us know if you have any questions or concerns. If you believe there is an error, please contact the Finance & Account department immediately so we can resolve the issue.</p>';
    $mail->Body .= '<p>Thank you for your hard work and dedication.</p>';
    $mail->Body .= '</div></body></html>';
    
    $mail->AltBody = 'Please find attached your payslips for: ' . $periodsList;
    
    // Attach all PDF files
    foreach ($pdfFiles as $pdfInfo) {
        $mail->addAttachment($pdfInfo['file'], $pdfInfo['filename'], 'base64', 'application/pdf');
    }
    
    try {
        $mail->send();
        
        // Clean up temporary files
        foreach ($pdfFiles as $pdfInfo) {
            if (file_exists($pdfInfo['file'])) {
                @unlink($pdfInfo['file']);
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => count($pdfFiles) . ' payslip(s) sent successfully to ' . $emailToUse
        ]);
        
    } catch (Exception $e) {
        // Clean up temporary files on error
        foreach ($pdfFiles as $pdfInfo) {
            if (file_exists($pdfInfo['file'])) {
                @unlink($pdfInfo['file']);
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error sending email: ' . $mail->ErrorInfo
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error in callPdfMultiple.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>