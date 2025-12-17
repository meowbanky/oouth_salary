<?php
session_start();
ini_set('max_execution_time', '0');
ini_set('memory_limit', '512M'); // Increase memory limit if needed

require_once('../classes/model.php');
require_once('Connections/paymaster.php');
require 'office_vendor/autoload.php';
require 'vendor/autoload.php';
require_once '../../config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class PayrollExporter {
    private $conn;
    private $periodFrom;
    private $periodTo;
    private $period_text;
    private $departments = [];
    private $edTypes = [];

    public function __construct($conn) {
        $this->conn = $conn;
        $this->loadPayrollPeriod();
        $this->cacheDepartments();
        $this->cacheEDTypes();
    }

    private function loadPayrollPeriod() {
        $query = $this->conn->prepare("
            SELECT periodId, CONCAT(description,' - ', periodYear) as description
            FROM payperiods 
            WHERE periodid = (
                SELECT MAX(periodid)
                FROM payperiods 
                WHERE openview = 0
            )
        ");
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);

        $this->periodFrom = $this->periodTo = $result['periodId'];
        $this->period_text = $result['description'];
    }

    private function cacheDepartments() {
        $query = $this->conn->prepare("SELECT dept_id, dept FROM tbl_dept");
        $query->execute();
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $this->departments[$row['dept_id']] = $row['dept'];
        }
    }

    private function cacheEDTypes() {
        $query = $this->conn->prepare("SELECT ed_id, ed, edType FROM tbl_earning_deduction");
        $query->execute();
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $this->edTypes[$row['ed_id']] = [
                'name' => $row['ed'],
                'type' => $row['edType']
            ];
        }
    }

    public function generateReport() {
        // Fetch all staff data with a single query
        $staffQuery = $this->conn->prepare("
            SELECT 
                ms.staff_id,
                ms.NAME,
                ms.DEPTCD,
                p.amount,
                p.ed_id
            FROM master_staff ms
            LEFT JOIN payroll p ON ms.staff_id = p.staff_id 
                AND p.period BETWEEN :periodFrom AND :periodTo
            WHERE ms.period BETWEEN :periodFrom AND :periodTo
            GROUP BY ms.staff_id, p.ed_id
        ");

        $staffQuery->execute([
            ':periodFrom' => $this->periodFrom,
            ':periodTo' => $this->periodTo
        ]);

        $reportData = [];
        while ($row = $staffQuery->fetch(PDO::FETCH_ASSOC)) {
            $staffId = $row['staff_id'];

            if (!isset($reportData[$staffId])) {
                $reportData[$staffId] = [
                    'staff_id' => $staffId,
                    'name' => $row['NAME'],
                    'dept' => $this->departments[$row['DEPTCD']] ?? 'Unknown Department',
                    'earnings' => array_fill_keys(array_column($this->edTypes, 'name'), 0),
                    'deductions' => array_fill_keys(array_column($this->edTypes, 'name'), 0),
                    'total_allow' => 0,
                    'total_deduc' => 0
                ];
            }

            if ($row['ed_id'] && isset($this->edTypes[$row['ed_id']])) {
                $edInfo = $this->edTypes[$row['ed_id']];
                $amount = floatval($row['amount']);

                if ($edInfo['type'] == 1) {
                    $reportData[$staffId]['earnings'][$edInfo['name']] = $amount;
                    $reportData[$staffId]['total_allow'] += $amount;
                } else {
                    $reportData[$staffId]['deductions'][$edInfo['name']] = $amount;
                    $reportData[$staffId]['total_deduc'] += $amount;
                }
            }
        }

        // Calculate net pay
        foreach ($reportData as &$staff) {
            $staff['net_pay'] = $staff['total_allow'] - $staff['total_deduc'];
        }

        return array_values($reportData);
    }

    public function exportToExcel($reportData, $recipientEmail) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Prepare headers
        $headers = ['STAFF NO', 'NAME', 'PAY PERIOD', 'DEPT'];
        foreach ($this->edTypes as $edType) {
            $headers[] = $edType['name'];
        }
        $headers = array_merge($headers, ['TOTAL ALLOW', 'TOTAL DEDUC', 'NET PAY']);

        // Set headers
        $sheet->fromArray([$headers], NULL, 'A1');

        // Prepare data rows
        $rowData = [];
        foreach ($reportData as $staff) {
            $row = [
                $staff['staff_id'],
                $staff['name'],
                $this->period_text,
                $staff['dept']
            ];

            foreach ($this->edTypes as $edType) {
                $value = $staff['earnings'][$edType['name']] ??
                    $staff['deductions'][$edType['name']] ?? 0;
                $row[] = $value;
            }

            $row = array_merge($row, [
                $staff['total_allow'],
                $staff['total_deduc'],
                $staff['net_pay']
            ]);

            $rowData[] = $row;
        }

        // Write all data at once
        $sheet->fromArray($rowData, NULL, 'A2');

        // Export and email
        $this->sendEmail($spreadsheet, $recipientEmail);
    }

    private function sendEmail($spreadsheet, $recipientEmail) {
        $filename = "payroll_summary_{$this->period_text}.xlsx";
        $tempFile = tempnam(sys_get_temp_dir(), 'payroll_');

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = SMTP_PORT;
            $mail->SMTPDebug = SMT_SMTPDebug;

            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addReplyTo(SMTP_REPLYTO_EMAIL, SMTP_REPLYTO_NAME);
            $mail->addAddress($recipientEmail);
            $mail->addBcc('bankole.adesoji@gmail.com');
            $mail->isHTML(true);

            $mail->Subject = "Payroll Summary Report for {$this->period_text}";
            $mail->Body = "Please find attached the payroll summary report for {$this->period_text}";
            $mail->addAttachment($tempFile, $filename);

            $mail->send();
            echo 'Report has been generated and sent successfully';
        } catch (Exception $e) {
            echo "Email could not be sent. Error: {$mail->ErrorInfo}";
        }

        unlink($tempFile);
    }
}

// Usage
try {
    $exporter = new PayrollExporter($conn);

    if (isset($_GET['export']) && $_GET['export'] === 'email') {
        $reportData = $exporter->generateReport();
        $exporter->exportToExcel($reportData, 'bankole.adesoji@gmail.com');
    } else {
        // Include template for HTML display
        $reportData = $exporter->generateReport();
        include 'report_template.php';
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    die("Database error occurred. Please try again later.");
}