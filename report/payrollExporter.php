<?php
ini_set('max_execution_time', '0');
ini_set('memory_limit', '512M');

require 'office_vendor/autoload.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPMailer\PHPMailer\PHPMailer;
require_once '../../config.php';
use PHPMailer\PHPMailer\SMTP;

class PayrollExporter {
    private $conn;
    private $periodFrom;
    private $periodTo;
    private $period_text;
    private $period;
    private $departments = [];
    private $edTypes = [];
    private $headers = [];
    private $earnings = [];
    private $deductions = [];
    private $chunk_size = 500;
    private $sendEmail = false;
    private $recipientEmail = null;

    public function __construct($conn) {
        if (!$conn) {
            throw new Exception("Valid database connection is required");
        }
        $this->conn = $conn;
        $this->loadPayrollPeriod();
        $this->loadEmailSettings();
        $this->cacheDepartments();
        $this->generateHeaders();
    }

    private function loadEmailSettings() {
        $this->sendEmail = isset($_GET['send_email']) && $_GET['send_email'] === 'on';
        if ($this->sendEmail && isset($_GET['email_address']) && !empty($_GET['email_address'])) {
            $this->recipientEmail = filter_var($_GET['email_address'], FILTER_SANITIZE_EMAIL);
        }
    }

    private function loadPayrollPeriod() {
        try {
            $periodFrom = isset($_GET['periodFrom']) ? $_GET['periodFrom'] : null;
            $periodTo = isset($_GET['periodTo']) ? $_GET['periodTo'] : null;

            if ($periodFrom && $periodTo) {
                $query = $this->conn->prepare("
                    SELECT periodId, CONCAT(description,' - ', periodYear) as description
                    FROM payperiods 
                    WHERE periodId = ?
                ");
                $query->execute([$periodFrom]);
                $result = $query->fetch(PDO::FETCH_ASSOC);

                if ($result) {
                    $this->periodFrom = $periodFrom;
                    $this->periodTo = $periodTo;
                    $this->period = $periodFrom;
                    $this->period_text = $result['description'];
                } else {
                    $this->loadDefaultPeriod();
                }
            } else {
                $this->loadDefaultPeriod();
            }
        } catch (PDOException $e) {
            error_log("Error loading payroll period: " . $e->getMessage());
            $this->loadDefaultPeriod();
        }
    }

    private function loadDefaultPeriod() {
        $query = $this->conn->prepare("
            SELECT periodId, CONCAT(description,' - ', periodYear) as description
            FROM payperiods 
            WHERE periodid = (
                SELECT MAX(periodid)
                FROM payperiods 
                WHERE active = 0
            )
        ");
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $this->periodFrom = $this->periodTo = $result['periodId'];
            $this->period = $result['periodId'];
            $this->period_text = $result['description'];
        } else {
            throw new Exception("Could not find default payroll period");
        }
    }

    private function cacheDepartments() {
        $query = $this->conn->prepare("SELECT dept_id, dept FROM tbl_dept");
        $query->execute();
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $this->departments[$row['dept_id']] = $row['dept'];
        }
    }

    private function generateHeaders() {
        // Get earnings (code = 1)
        $earningsQuery = $this->conn->prepare("
            SELECT DISTINCT ed_id, ed, code 
            FROM tbl_earning_deduction 
            WHERE code = 1 
            AND status = 'Active'
            ORDER BY ed_id
        ");
        $earningsQuery->execute();
        while ($row = $earningsQuery->fetch(PDO::FETCH_ASSOC)) {
            $this->earnings[] = $row['ed'];
            $this->edTypes[$row['ed_id']] = [
                'name' => $row['ed'],
                'type' => $row['code']
            ];
        }

        // Get deductions (code = 2)
        $deductionsQuery = $this->conn->prepare("
            SELECT DISTINCT ed_id, ed, code 
            FROM tbl_earning_deduction 
            WHERE code = 2 
            AND status = 'Active'
            ORDER BY ed_id
        ");
        $deductionsQuery->execute();
        while ($row = $deductionsQuery->fetch(PDO::FETCH_ASSOC)) {
            $this->deductions[] = $row['ed'];
            $this->edTypes[$row['ed_id']] = [
                'name' => $row['ed'],
                'type' => $row['code']
            ];
        }
    }

    public function generateReport() {
        $reportData = [];

        $countQuery = $this->conn->prepare("
            SELECT COUNT(DISTINCT staff_id) as total 
            FROM master_staff 
            WHERE period = :period
        ");
        $countQuery->execute([':period' => $this->periodFrom]);
        $totalStaff = $countQuery->fetch(PDO::FETCH_ASSOC)['total'];

        for ($offset = 0; $offset < $totalStaff; $offset += $this->chunk_size) {
            $staffQuery = $this->conn->prepare("
                WITH StaffList AS (
                    SELECT DISTINCT staff_id, NAME, DEPTCD
                    FROM master_staff
                    WHERE period = :period
                    LIMIT :limit OFFSET :offset
                )
                SELECT 
                    s.staff_id,
                    s.NAME,
                    s.DEPTCD,
                    m.allow_id,
                    ed.ed as allowance_name,
                    ed.code as type,
                    CASE 
                        WHEN ed.code = 1 THEN COALESCE(m.allow, 0)
                        WHEN ed.code = 2 THEN COALESCE(m.deduc, 0)
                    END as amount
                FROM StaffList s
                LEFT JOIN tbl_master m ON s.staff_id = m.staff_id 
                    AND m.period = :period
                LEFT JOIN tbl_earning_deduction ed ON m.allow_id = ed.ed_id AND ed.status = 'Active'
            ");

            $staffQuery->bindValue(':period', $this->periodFrom, PDO::PARAM_INT);
            $staffQuery->bindValue(':limit', $this->chunk_size, PDO::PARAM_INT);
            $staffQuery->bindValue(':offset', $offset, PDO::PARAM_INT);
            $staffQuery->execute();

            while ($row = $staffQuery->fetch(PDO::FETCH_ASSOC)) {
                $staffId = $row['staff_id'];

                if (!isset($reportData[$staffId])) {
                    $reportData[$staffId] = [
                        'staff_id' => $staffId,
                        'name' => $row['NAME'],
                        'dept' => $this->departments[$row['DEPTCD']] ?? 'Unknown Department',
                        'earnings' => array_fill_keys($this->earnings, 0),
                        'deductions' => array_fill_keys($this->deductions, 0),
                        'total_allow' => 0,
                        'total_deduc' => 0
                    ];
                }

                if ($row['allowance_name'] && $row['amount'] > 0) {
                    if ($row['type'] == 1) {
                        $reportData[$staffId]['earnings'][$row['allowance_name']] = floatval($row['amount']);
                        $reportData[$staffId]['total_allow'] += floatval($row['amount']);
                    } else {
                        $reportData[$staffId]['deductions'][$row['allowance_name']] = floatval($row['amount']);
                        $reportData[$staffId]['total_deduc'] += floatval($row['amount']);
                    }
                }
            }

            $staffQuery->closeCursor();
            gc_collect_cycles();
        }

        foreach ($reportData as &$staff) {
            $staff['net_pay'] = $staff['total_allow'] - $staff['total_deduc'];
        }

        return array_values($reportData);
    }

    public function generateAndSendReport() {
        $reportData = $this->generateReport();

        if ($this->sendEmail && $this->recipientEmail) {
            $this->exportToExcel($reportData, $this->recipientEmail);
        } else {
            $period_text = $this->period_text;
            $exporter = $this;
            include 'report_template.php';
        }
    }

    public function getPeriod() {
        return $this->period;
    }

    public function getPeriodText() {
        return $this->period_text;
    }

    public function getEarnings() {
        return $this->earnings;
    }

    public function getDeductions() {
        return $this->deductions;
    }

    private function exportToExcel($reportData, $recipientEmail) {
        ini_set('memory_limit', '512M');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = [
            'STAFF NO', 'NAME', 'PAY PERIOD', 'DEPT'
        ];

        // Add earnings headers
        foreach ($this->earnings as $earning) {
            $headers[] = $earning;
        }

        // Add total allowance header
        $headers[] = 'TOTAL ALLOW';

        // Add deductions headers
        foreach ($this->deductions as $deduction) {
            $headers[] = $deduction;
        }

        // Add final totals headers
        $headers[] = 'TOTAL DEDUC';
        $headers[] = 'NET PAY';

        $sheet->fromArray([$headers], NULL, 'A1');

        // Format data rows
        $rowCount = 2;
        foreach ($reportData as $staff) {
            $row = [
                $staff['staff_id'],
                $staff['name'],
                $this->period_text,
                $staff['dept']
            ];

            foreach ($this->earnings as $earning) {
                $row[] = $staff['earnings'][$earning] ?? 0;
            }

            $row[] = $staff['total_allow'];

            foreach ($this->deductions as $deduction) {
                $row[] = $staff['deductions'][$deduction] ?? 0;
            }

            $row[] = $staff['total_deduc'];
            $row[] = $staff['net_pay'];

            $sheet->fromArray([$row], NULL, 'A' . $rowCount);
            $rowCount++;
        }

        $lastColumn = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();

        foreach (range('A', $lastColumn) as $col) {
            if ($col === 'B') {
                $width = 30;
            } elseif ($col === 'D') {
                $width = 25;
            } else {
                $width = 15;
            }
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // Format numbers
        $sheet->getStyle('E2:' . $lastColumn . $lastRow)->getNumberFormat()
            ->setFormatCode('#,##0.00');

        // Add borders and alignment
        $sheet->getStyle('A1:' . $lastColumn . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                ]
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT
            ]
        ]);

        // Left align text columns
        $sheet->getStyle('A1:D' . $lastRow)->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

        // Format headers
        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            ]
        ]);

        $sheet->freezePane('A2');
        $this->sendEmail($spreadsheet, $recipientEmail);
    }

    private function sendEmail($spreadsheet, $recipientEmail) {
        if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address provided");
        }

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
            echo json_encode([
                'success' => true,
                'message' => 'Report has been generated and sent successfully to ' . $recipientEmail
            ]);
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => "Email could not be sent. Please check the error logs."
            ]);
        }

        unlink($tempFile);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        gc_collect_cycles();
    }
}