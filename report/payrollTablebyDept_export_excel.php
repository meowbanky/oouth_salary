<?php
require_once('../Connections/paymaster.php');
include_once('../classes/model.php');
require_once('../libs/App.php');
$App = new App();
$App->checkAuthentication();
require_once('../libs/middleware.php');
checkPermission();

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) == '') {
    header("location: ../index.php");
    exit();
}

$periodFrom = isset($_POST['periodFrom']) ? $_POST['periodFrom'] : '';
$periodTo = isset($_POST['periodTo']) ? $_POST['periodTo'] : '';
$dept = isset($_POST['dept']) ? $_POST['dept'] : '';
$periodFrom_text = isset($_POST['periodFrom_text']) ? $_POST['periodFrom_text'] : '';
$periodTo_text = isset($_POST['periodTo_text']) ? $_POST['periodTo_text'] : '';
$dept_text = isset($_POST['dept_text']) ? $_POST['dept_text'] : '';

if (!$periodFrom || !$periodTo || !$dept) {
    die("Period and Department are required.");
}

// Include PhpSpreadsheet
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Set content type for AJAX response
header('Content-Type: text/plain; charset=utf-8');

try {
    // Create new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator('OOUTH Salary Manager')
        ->setLastModifiedBy($_SESSION['SESS_MEMBER_ID'])
        ->setTitle('Departmental Payroll Table')
        ->setSubject('Departmental Payroll Report')
        ->setDescription('Departmental payroll table report from OOUTH Salary Management System')
        ->setKeywords('payroll, department, table, oouth, salary')
        ->setCategory('Payroll Report');
    
    // Set headers starting from column A
    $col = 'A';
    $headers = [
        'Staff No',
        'Name',
        'Pay Period',
        'Department'
    ];
    
    // Add allowance headers
    $query = $conn->prepare('SELECT tbl_earning_deduction.ed_id, tbl_earning_deduction.ed FROM tbl_earning_deduction WHERE tbl_earning_deduction.edType = ?');
    $query->execute([1]);
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        $headers[] = $row['ed'];
    }
    $headers[] = 'Total Allow';
    
    // Add deduction headers
    $query = $conn->prepare('SELECT tbl_earning_deduction.ed_id, tbl_earning_deduction.ed FROM tbl_earning_deduction WHERE tbl_earning_deduction.edType > ?');
    $query->execute([1]);
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        $headers[] = $row['ed'];
    }
    $headers[] = 'Total Deduct';
    $headers[] = 'Net Pay';
    
    // Set header values
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $col++;
    }
    
    // Style the header row
    $headerRange = 'A1:' . chr(ord('A') + count($headers) - 1) . '1';
    $sheet->getStyle($headerRange)->applyFromArray([
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF']
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '1E40AF'] // Blue-800
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ]);
    
    // Set column widths
    $sheet->getColumnDimension('A')->setWidth(12); // Staff No
    $sheet->getColumnDimension('B')->setWidth(25); // Name
    $sheet->getColumnDimension('C')->setWidth(20); // Pay Period
    $sheet->getColumnDimension('D')->setWidth(20); // Department
    
    // Set width for other columns
    for ($i = 5; $i <= count($headers); $i++) {
        $sheet->getColumnDimension(chr(ord('A') + $i - 1))->setWidth(15);
    }
    
    // Get employee data
    $query = $conn->prepare('SELECT
        master_staff.staff_id, DEPTCD, ANY_VALUE(master_staff.`NAME`) AS `NAME`, 
        ANY_VALUE(tbl_dept.dept) AS dept, ANY_VALUE(CONCAT(payperiods.description," ",payperiods.periodYear)) as period 
        FROM master_staff 
        INNER JOIN tbl_dept ON tbl_dept.dept_id = master_staff.DEPTCD 
        INNER JOIN payperiods ON payperiods.periodId = master_staff.period 
        WHERE master_staff.period BETWEEN ? AND ? AND DEPTCD = ? 
        GROUP BY master_staff.staff_id 
        ORDER BY DEPTCD, staff_id');
    $query->execute([$periodFrom, $periodTo, $dept]);
    $employees = $query->fetchAll(PDO::FETCH_ASSOC);
    
    // Add data rows
    $row = 2;
    $totalEmployees = 0;
    
    foreach ($employees as $link) {
        $col = 'A';
        
        // Basic employee info
        $sheet->setCellValue($col++, $link['staff_id']);
        $sheet->setCellValue($col++, $link['NAME']);
        $sheet->setCellValue($col++, 'From ' . $periodFrom_text . ' To ' . $periodTo_text);
        $sheet->setCellValue($col++, $link['dept']);
        
        $allow = 0;
        $dedu = 0;
        
        // Allowance columns
        $query2 = $conn->prepare('SELECT tbl_earning_deduction.ed_id, tbl_earning_deduction.ed FROM tbl_earning_deduction WHERE tbl_earning_deduction.edType = ?');
        $query2->execute([1]);
        while ($row2 = $query2->fetch(PDO::FETCH_ASSOC)) {
            $j = retrievePayroll($periodFrom, $periodTo, $link['staff_id'], $row2['ed_id']);
            $sheet->setCellValue($col++, $j);
            $allow += $j;
        }
        $sheet->setCellValue($col++, $allow);
        
        // Deduction columns
        $query3 = $conn->prepare('SELECT tbl_earning_deduction.ed_id, tbl_earning_deduction.ed FROM tbl_earning_deduction WHERE tbl_earning_deduction.edType > ?');
        $query3->execute([1]);
        while ($row3 = $query3->fetch(PDO::FETCH_ASSOC)) {
            $j = retrievePayroll($periodFrom, $periodTo, $link['staff_id'], $row3['ed_id']);
            $sheet->setCellValue($col++, $j);
            $dedu += $j;
        }
        
        $sheet->setCellValue($col++, $dedu);
        $sheet->setCellValue($col, $allow - $dedu); // Net Pay
        
        $totalEmployees++;
        $row++;
    }
    
    // Style data rows
    if ($row > 2) {
        $dataRange = 'A2:' . chr(ord('A') + count($headers) - 1) . ($row - 1);
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC']
                ]
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
        
        // Right align numeric columns (from column E onwards)
        $numericRange = 'E2:' . chr(ord('A') + count($headers) - 1) . ($row - 1);
        $sheet->getStyle($numericRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // Alternate row colors
        for ($i = 2; $i < $row; $i++) {
            if ($i % 2 == 0) {
                $rowRange = 'A' . $i . ':' . chr(ord('A') + count($headers) - 1) . $i;
                $sheet->getStyle($rowRange)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F8FAFC'] // Gray-50
                    ]
                ]);
            }
        }
    }
    
    // Add report information
    $infoRow = $row + 2;
    $sheet->setCellValue('A' . $infoRow, 'Report Information:');
    $sheet->setCellValue('A' . ($infoRow + 1), 'Generated by: ' . $_SESSION['SESS_FIRST_NAME']);
    $sheet->setCellValue('A' . ($infoRow + 2), 'Date: ' . date('Y-m-d H:i:s'));
    $sheet->setCellValue('A' . ($infoRow + 3), 'Department: ' . $dept_text);
    $sheet->setCellValue('A' . ($infoRow + 4), 'Period: ' . $periodFrom_text . ' to ' . $periodTo_text);
    $sheet->setCellValue('A' . ($infoRow + 5), 'Total Employees: ' . $totalEmployees);
    
    // Create writer and save to output buffer
    ob_start();
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    $excelData = ob_get_contents();
    ob_end_clean();
    
    // Return as base64 for AJAX download
    echo base64_encode($excelData);
    
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error generating Excel file. Please try again.']);
}
?>