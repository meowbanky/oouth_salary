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

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) == '') {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

// Retrieve POST parameters
$period = filter_input(INPUT_POST, 'period', FILTER_VALIDATE_INT) ?: -1;
$pfa = filter_input(INPUT_POST, 'pfa', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: -1;
$period_text = filter_input(INPUT_POST, 'period_text', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 'Unknown Period';
$pfa_text = filter_input(INPUT_POST, 'pfa_text', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 'All PFA';
$recipientEmail = filter_input(INPUT_POST, 'recipient_email', FILTER_SANITIZE_EMAIL) ?: '';
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 'download';

// Fetch PFA name for the title using output buffering
ob_start();

retrieveDescSingleFilter('tbl_pfa', 'PFANAME', 'PFACODE', $pfa);
$pfaName = ob_get_clean() ?: 'All PFA';

// Initialize PhpSpreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set document properties
$spreadsheet->getProperties()
    ->setCreator($_SESSION['SESS_FIRST_NAME'])
    ->setTitle('Pension Funds Report')
    ->setDescription('Detailed Pension Funds Report for ' . $pfaName);

// Add title
$sheet->setCellValue('A1', 'OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL');
$sheet->setCellValue('A2', $pfaName . ' Pension Report for the Month of: ' . $period_text);
$sheet->mergeCells('A1:F1');
$sheet->mergeCells('A2:F2');
$sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Set column headers
$headers = ['S/No.', 'Staff No.', 'Name', 'PFA', 'PIN', 'Amount'];
$sheet->fromArray($headers, null, 'A4');
$sheet->getStyle('A4:F4')->getFont()->setBold(true);
$sheet->getStyle('A4:F4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD3D3D3');
$sheet->getStyle('A4:F4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

// Fetch data
try {
    $sql = $pfa != -1
        ? 'SELECT tbl_master.deduc, master_staff.staff_id, master_staff.`NAME`, master_staff.PFAACCTNO, tbl_pfa.PFANAME 
           FROM tbl_master 
           INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id 
           INNER JOIN tbl_pfa ON master_staff.PFACODE = tbl_pfa.PFACODE 
           WHERE tbl_master.allow_id = ? AND master_staff.period = ? AND master_staff.PFACODE = ? AND tbl_master.period = ? 
           ORDER BY tbl_master.staff_id ASC'
        : 'SELECT tbl_master.deduc, master_staff.staff_id, master_staff.`NAME`, master_staff.PFAACCTNO, tbl_pfa.PFANAME 
           FROM tbl_master 
           INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id 
           INNER JOIN tbl_pfa ON master_staff.PFACODE = tbl_pfa.PFACODE 
           WHERE tbl_master.allow_id = ? AND master_staff.period = ? AND tbl_master.period = ? 
           ORDER BY tbl_master.staff_id ASC';

    $query = $conn->prepare($sql);
    $params = $pfa != -1 ? ['50', $period, $pfa, $period] : ['50', $period, $period];
    $query->execute($params);

    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $rowNum = 5;
    $sumTotal = 0;
    $counter = 1;

    foreach ($res as $row) {
        $data = [
            $counter,
            $row['staff_id'],
            $row['NAME'],
            $row['PFANAME'],
            $row['PFAACCTNO'],
            number_format($row['deduc'], 2)
        ];
        $sheet->fromArray($data, null, 'A' . $rowNum);
        $sheet->getStyle('A' . $rowNum . ':B' . $rowNum)->getFont()->setBold(true);
        $sheet->getStyle('F' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sumTotal += floatval($row['deduc']);
        $rowNum++;
        $counter++;
    }

    // Add total row
    $sheet->setCellValue('A' . $rowNum, 'TOTAL');
    $sheet->mergeCells('A' . $rowNum . ':E' . $rowNum);
    $sheet->setCellValue('F' . $rowNum, number_format($sumTotal, 2));
    $sheet->getStyle('A' . $rowNum . ':F' . $rowNum)->getFont()->setBold(true);
    $sheet->getStyle('F' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    // Apply borders
    $sheet->getStyle('A4:F' . $rowNum)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

    // Adjust column widths
    foreach (range('A', 'F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
} catch (PDOException $e) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Error fetching data: ' . $e->getMessage()]);
    exit();
}

// Handle email or download
if ($action === 'email' && !empty($recipientEmail)) {
    // Validate email
    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Invalid email address']);
        exit();
    }
    
    // Save to temporary file
    $tempFile = tempnam(sys_get_temp_dir(), 'pfa_report_') . '.xlsx';
    $writer = new Xlsx($spreadsheet);
    $writer->save($tempFile);
    
    // Send email using PHPMailer
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        
        // Handle SMTP_SECURE from config.php
        if (defined('SMTP_SECURE')) {
            $secureValue = SMTP_SECURE;
            if (is_string($secureValue)) {
                if (strpos($secureValue, 'ENCRYPTION_STARTTLS') !== false) {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                } elseif (strpos($secureValue, 'ENCRYPTION_SMTPS') !== false || strpos($secureValue, 'ssl') !== false) {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                } elseif (SMTP_PORT == 587) {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                } else {
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
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }
        
        $mail->Port = SMTP_PORT;
        $mail->SMTPDebug = 0; // Disable debug output
        
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addReplyTo(SMTP_REPLYTO_EMAIL, SMTP_REPLYTO_NAME);
        $mail->addAddress($recipientEmail);
        $mail->isHTML(true);
        
        $mail->Subject = "PFA Pension Report - " . $pfa_text . " (" . $period_text . ")";
        $mail->Body = "
            <html>
            <body>
                <h2>PFA Pension Report</h2>
                <p>Dear Recipient,</p>
                <p>Please find attached the pension fund report for:</p>
                <ul>
                    <li><strong>PFA:</strong> {$pfa_text}</li>
                    <li><strong>Period:</strong> {$period_text}</li>
                    <li><strong>Total Amount:</strong> ₦" . number_format($sumTotal ?? 0, 2) . "</li>
                </ul>
                <p>Report generated by: " . ($_SESSION['SESS_FIRST_NAME'] ?? 'System') . "</p>
                <p>Date: " . date('l, F d, Y') . "</p>
                <p>This is an automated email from OOUTH Salary Management System.</p>
            </body>
            </html>
        ";
        $mail->AltBody = "PFA Pension Report for {$pfa_text}. Period: {$period_text}. Total Amount: ₦" . number_format($sumTotal ?? 0, 2) . ".";
        
        $filename = "PFA_Report_{$pfa_text}_{$period_text}_" . date('Y-m-d') . '.xlsx';
        $mail->addAttachment($tempFile, $filename);
        
        $mail->send();
        error_log("PFA Report email sent successfully to: $recipientEmail");
        
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
            'status' => 'success',
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
            'status' => 'error',
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
        echo json_encode(['status' => 'error', 'message' => 'Error generating Excel: ' . $e->getMessage()]);
        exit();
    }
}
?>