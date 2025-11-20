<?php
// Start output buffering early and suppress any warnings/notices
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

ob_start();
session_start();
require_once('../Connections/paymaster.php');
include_once('../classes/model.php');
require_once('../vendor/autoload.php');
require_once('../config/config.php');

// All SMTP constants are now defined in config/config.php
// No need to define them here - they're already available after requiring config.php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) == '') {
    ob_clean();
    if (isset($_POST['action']) && $_POST['action'] === 'email') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized access']);
    }
    exit();
}

// Retrieve POST parameters
$staffId = filter_input(INPUT_POST, 'staff_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
$staffName = filter_input(INPUT_POST, 'staff_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
$periodFrom = filter_input(INPUT_POST, 'period_from', FILTER_VALIDATE_INT) ?: -1;
$periodTo = filter_input(INPUT_POST, 'period_to', FILTER_VALIDATE_INT) ?: -1;
$pfaName = filter_input(INPUT_POST, 'pfa_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
$pfaPin = filter_input(INPUT_POST, 'pfa_pin', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
$recipientEmail = filter_input(INPUT_POST, 'recipient_email', FILTER_SANITIZE_EMAIL) ?: '';
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 'download';

// Validate required parameters
if (empty($staffId) || $periodFrom == -1 || $periodTo == -1) {
    ob_clean();
    if ($action === 'email') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Missing required parameters']);
    }
    exit();
}

// Ensure periodFrom <= periodTo
if ($periodFrom > $periodTo) {
    $temp = $periodFrom;
    $periodFrom = $periodTo;
    $periodTo = $temp;
}

// Get period descriptions
$periodFromText = '';
$periodToText = '';
try {
    $query = $conn->prepare('SELECT description, periodYear FROM payperiods WHERE periodId = ?');
    $query->execute([$periodFrom]);
    $fromPeriod = $query->fetch(PDO::FETCH_ASSOC);
    if ($fromPeriod) {
        $periodFromText = $fromPeriod['description'] . ' ' . $fromPeriod['periodYear'];
    }
    
    $query = $conn->prepare('SELECT description, periodYear FROM payperiods WHERE periodId = ?');
    $query->execute([$periodTo]);
    $toPeriod = $query->fetch(PDO::FETCH_ASSOC);
    if ($toPeriod) {
        $periodToText = $toPeriod['description'] . ' ' . $toPeriod['periodYear'];
    }
} catch (PDOException $e) {
    error_log("Error fetching period descriptions: " . $e->getMessage());
}

// Initialize PhpSpreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set document properties
$spreadsheet->getProperties()
    ->setCreator($_SESSION['SESS_FIRST_NAME'] ?? 'System')
    ->setTitle('Individual Pension Fund Report')
    ->setDescription('Individual Pension Fund Report for ' . $staffName);

// Add title
$sheet->setCellValue('A1', 'OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL');
$sheet->setCellValue('A2', 'Individual Pension Fund Report');
$sheet->setCellValue('A3', 'Staff: ' . $staffName . ' (ID: ' . $staffId . ')');
if ($pfaName) {
    $sheet->setCellValue('A4', 'PFA: ' . $pfaName);
}
if ($pfaPin) {
    $sheet->setCellValue('A5', 'PIN: ' . $pfaPin);
}
$sheet->setCellValue('A6', 'Period: ' . $periodFromText . ' to ' . $periodToText);

// Merge cells for title
$sheet->mergeCells('A1:D1');
$sheet->mergeCells('A2:D2');
$sheet->mergeCells('A3:D3');
if ($pfaName) {
    $sheet->mergeCells('A4:D4');
}
if ($pfaPin) {
    $sheet->mergeCells('A5:D5');
}
$sheet->mergeCells('A6:D6');

// Style title rows
$titleRows = ['A1', 'A2', 'A3'];
if ($pfaName) $titleRows[] = 'A4';
if ($pfaPin) $titleRows[] = 'A5';
$titleRows[] = 'A6';

foreach ($titleRows as $cell) {
    $sheet->getStyle($cell)->getFont()->setBold(true);
    if ($cell === 'A1' || $cell === 'A2') {
        $sheet->getStyle($cell)->getFont()->setSize(14);
    }
}

$sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Start data rows after title
$startRow = $pfaPin ? 8 : ($pfaName ? 7 : 6);
$headerRow = $startRow;

// Set column headers
$headers = ['S/No.', 'Period', 'Pension Contribution (₦)'];
$sheet->fromArray($headers, null, 'A' . $headerRow);
$sheet->getStyle('A' . $headerRow . ':C' . $headerRow)->getFont()->setBold(true);
$sheet->getStyle('A' . $headerRow . ':C' . $headerRow)->getFill()
    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD3D3D3');
$sheet->getStyle('A' . $headerRow . ':C' . $headerRow)->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_LEFT);

// Fetch data
try {
    $query = $conn->prepare('
        SELECT 
            tbl_master.deduc,
            tbl_master.period,
            payperiods.description,
            payperiods.periodYear
        FROM tbl_master 
        INNER JOIN payperiods ON tbl_master.period = payperiods.periodId
        WHERE tbl_master.staff_id = ? 
            AND tbl_master.allow_id = 50
            AND tbl_master.period >= ? 
            AND tbl_master.period <= ?
        ORDER BY tbl_master.period ASC
    ');
    $query->execute([$staffId, $periodFrom, $periodTo]);
    
    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $rowNum = $headerRow + 1;
    $sumTotal = 0;
    $counter = 1;
    
    foreach ($res as $row) {
        $periodText = $row['description'] . ' ' . $row['periodYear'];
        $amount = floatval($row['deduc']);
        $sumTotal += $amount;
        
        $data = [
            $counter,
            $periodText,
            number_format($amount, 2)
        ];
        $sheet->fromArray($data, null, 'A' . $rowNum);
        $sheet->getStyle('C' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $rowNum++;
        $counter++;
    }
    
    // Add total row
    if (count($res) > 0) {
        $sheet->setCellValue('A' . $rowNum, 'TOTAL CONTRIBUTIONS');
        $sheet->mergeCells('A' . $rowNum . ':B' . $rowNum);
        $sheet->setCellValue('C' . $rowNum, number_format($sumTotal, 2));
        $sheet->getStyle('A' . $rowNum . ':C' . $rowNum)->getFont()->setBold(true);
        $sheet->getStyle('C' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $rowNum++;
    }
    
    // Apply borders
    $sheet->getStyle('A' . $headerRow . ':C' . ($rowNum - 1))->getBorders()
        ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    
    // Adjust column widths
    $sheet->getColumnDimension('A')->setWidth(10);
    $sheet->getColumnDimension('B')->setWidth(25);
    $sheet->getColumnDimension('C')->setWidth(20);
    
} catch (PDOException $e) {
    error_log("Error fetching report data: " . $e->getMessage());
    ob_clean();
    if ($action === 'email') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error fetching data: ' . $e->getMessage()]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Error fetching data: ' . $e->getMessage()]);
    }
    exit();
}

// Handle email or download
if ($action === 'email') {
    // Log email request
    error_log("Email action requested. Action: $action, Recipient: $recipientEmail, Staff: $staffId");
    
    // Email action requires recipient email
    if (empty($recipientEmail)) {
        // End all output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Recipient email is required']);
        exit();
    }
    
    // Validate email
    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        // End all output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit();
    }
    
    error_log("Email validated. Proceeding to generate and send report to: $recipientEmail");
    
    // Save to temporary file
    $tempFile = tempnam(sys_get_temp_dir(), 'pension_report_') . '.xlsx';
    $writer = new Xlsx($spreadsheet);
    $writer->save($tempFile);
    
    // Send email
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        
        // Handle SMTP_SECURE from config.php
        // SMTP_SECURE is stored as a string in config.php (e.g., 'PHPMailer::ENCRYPTION_STARTTLS')
        // We need to convert it to the actual PHPMailer constant
        if (defined('SMTP_SECURE')) {
            $secureValue = SMTP_SECURE;
            // Check if it's a string representation of the constant
            if (is_string($secureValue)) {
                if (strpos($secureValue, 'ENCRYPTION_STARTTLS') !== false) {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                } elseif (strpos($secureValue, 'ENCRYPTION_SMTPS') !== false || strpos($secureValue, 'ssl') !== false) {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                } elseif (SMTP_PORT == 587) {
                    // Default to STARTTLS for port 587
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                } else {
                    // Default to SMTPS for other ports (like 465)
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                }
            } elseif ($secureValue === PHPMailer::ENCRYPTION_STARTTLS) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($secureValue === PHPMailer::ENCRYPTION_SMTPS) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif (SMTP_PORT == 587) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }
        } elseif (SMTP_PORT == 587) {
            // Default to STARTTLS for port 587
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            // Default to SMTPS for other ports (like 465)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }
        
        $mail->Port = SMTP_PORT;
        $mail->SMTPDebug = SMT_SMTPDebug;
        
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addReplyTo(SMTP_REPLYTO_EMAIL, SMTP_REPLYTO_NAME);
        $mail->addAddress($recipientEmail);
        $mail->isHTML(true);
        
        $mail->Subject = "Individual Pension Fund Report - " . $staffName;
        $mail->Body = "
            <html>
            <body>
                <h2>Individual Pension Fund Report</h2>
                <p>Dear Recipient,</p>
                <p>Please find attached the individual pension fund report for:</p>
                <ul>
                    <li><strong>Staff:</strong> {$staffName} (ID: {$staffId})</li>
                    <li><strong>Period:</strong> {$periodFromText} to {$periodToText}</li>
                </ul>
                <p>Report generated by: " . ($_SESSION['SESS_FIRST_NAME'] ?? 'System') . "</p>
                <p>Date: " . date('l, F d, Y') . "</p>
                <p>This is an automated email from OOUTH Salary Management System.</p>
            </body>
            </html>
        ";
        $mail->AltBody = "Individual Pension Fund Report for {$staffName} (ID: {$staffId}). Period: {$periodFromText} to {$periodToText}.";
        
        $filename = "Pension_Report_{$staffId}_" . date('Y-m-d') . '.xlsx';
        $mail->addAttachment($tempFile, $filename);
        
        $mail->send();
        
        // Clean up
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
        
        // Clear all output before sending JSON
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => "Report has been successfully sent to {$recipientEmail}"
        ]);
        exit();
        
    } catch (Exception $e) {
        error_log("Email send error: " . $e->getMessage() . " | PHPMailer Error: " . (isset($mail) ? $mail->ErrorInfo : 'N/A'));
        
        // Clean up on error
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
        
        // Clear all output before sending JSON
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send email: ' . (isset($mail) ? $mail->ErrorInfo : $e->getMessage())
        ]);
        exit();
    }
    
} else {
    // Download Excel file - return as base64 for AJAX handling
    try {
        // End and clean all previous output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Generate Excel file in memory
        ob_start();
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        $excelData = ob_get_clean();
        
        // Clear any remaining output
        ob_clean();
        
        // Return base64 encoded data (no headers needed for AJAX - jQuery handles it)
        $base64 = base64_encode($excelData);
        echo $base64;
        exit();
    } catch (Exception $e) {
        // End all output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Error generating Excel: ' . $e->getMessage()]);
        exit();
    }
}
?>