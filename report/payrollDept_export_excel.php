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

$period = isset($_POST['period']) ? $_POST['period'] : '';
$period_text = isset($_POST['period_text']) ? $_POST['period_text'] : '';

if (!$period) {
    die("Period is required.");
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
    // Get departmental payroll data
    $query = $conn->prepare('SELECT master_staff.DEPTCD, ANY_VALUE(tbl_dept.dept) AS dept, ANY_VALUE(Sum(tbl_master.allow)) as "allow", ANY_VALUE(count(tbl_master.staff_id)) as "numb", ANY_VALUE(Sum(tbl_master.deduc)) as "deduct", ANY_VALUE(Sum(tbl_master.allow) - Sum(tbl_master.deduc)) as "net", ANY_VALUE(tbl_dept.dept) AS dept FROM tbl_master INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id INNER JOIN tbl_dept ON tbl_dept.dept_id = master_staff.DEPTCD WHERE master_staff.period = ? and tbl_master.period = ? GROUP BY master_staff.DEPTCD order by dept asc');
    $query->execute([$period, $period]);
    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    
    $countStaff = 0;
    $sumAll = 0;
    $sumDeduct = 0;
    $sumTotal = 0;
    
    // Create new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator('OOUTH Salary Manager')
        ->setLastModifiedBy($_SESSION['SESS_MEMBER_ID'])
        ->setTitle('Departmental Payroll Summary')
        ->setSubject('Departmental Payroll Report')
        ->setDescription('Departmental payroll summary report from OOUTH Salary Management System')
        ->setKeywords('payroll, department, summary, oouth, salary')
        ->setCategory('Payroll Report');
    
    // Set headers
    $headers = [
        'A' => 'Department Name',
        'B' => 'No. of Employee',
        'C' => 'Total Allowance',
        'D' => 'Total Deduction',
        'E' => 'Department Net Pay'
    ];
    
    // Set header values and style
    foreach ($headers as $column => $header) {
        $sheet->setCellValue($column . '1', $header);
    }
    
    // Style the header row
    $headerRange = 'A1:E1';
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
    $columnWidths = [
        'A' => 30, // Department Name
        'B' => 15, // No. of Employee
        'C' => 18, // Total Allowance
        'D' => 18, // Total Deduction
        'E' => 20  // Department Net Pay
    ];
    
    foreach ($columnWidths as $column => $width) {
        $sheet->getColumnDimension($column)->setWidth($width);
    }
    
    // Add data rows
    $row = 2;
    if (count($res) > 0) {
        foreach ($res as $link) {
            // Get active employee count for this department
            $query2 = $conn->prepare('SELECT Count(master_staff.DEPTCD) as "numb" FROM master_staff WHERE STATUSCD = ? and DEPTCD = ? AND master_staff.period = ? GROUP BY DEPTCD');
            $query2->execute(['A', $link['DEPTCD'], $period]);
            $res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
            
            $numb = 0;
            foreach ($res2 as $link2) {
                $numb = $link2['numb'];
                $countStaff += $numb;
            }
            
            $sheet->setCellValue('A' . $row, $link['dept']);
            $sheet->setCellValue('B' . $row, $numb);
            $sheet->setCellValue('C' . $row, $link['allow']);
            $sheet->setCellValue('D' . $row, $link['deduct']);
            $sheet->setCellValue('E' . $row, $link['net']);
            
            $sumAll += floatval($link['allow']);
            $sumDeduct += floatval($link['deduct']);
            $sumTotal += floatval($link['net']);
            
            $row++;
        }
        
        // Add total row
        $totalRow = $row;
        $sheet->setCellValue('A' . $totalRow, 'TOTAL');
        $sheet->setCellValue('B' . $totalRow, $countStaff);
        $sheet->setCellValue('C' . $totalRow, $sumAll);
        $sheet->setCellValue('D' . $totalRow, $sumDeduct);
        $sheet->setCellValue('E' . $totalRow, $sumTotal);
        
        // Style the total row
        $sheet->getStyle('A' . $totalRow . ':E' . $totalRow)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '1E40AF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EFF6FF'] // Blue-50
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '1E40AF']
                ]
            ]
        ]);
        
        $row++; // Move to next row after total
    }
    
    // Style data rows
    if ($row > 2) {
        $dataRange = 'A2:E' . ($row - 1);
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
        
        // Right align numeric columns
        $sheet->getStyle('B2:E' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // Alternate row colors
        for ($i = 2; $i < $row - 1; $i++) {
            if ($i % 2 == 0) {
                $sheet->getStyle('A' . $i . ':E' . $i)->applyFromArray([
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
    $sheet->setCellValue('A' . ($infoRow + 3), 'Period: ' . $period_text);
    $sheet->setCellValue('A' . ($infoRow + 4), 'Total Departments: ' . count($res));
    $sheet->setCellValue('A' . ($infoRow + 5), 'Total Employees: ' . $countStaff);
    $sheet->setCellValue('A' . ($infoRow + 6), 'Total Allowances: ₦' . number_format($sumAll));
    $sheet->setCellValue('A' . ($infoRow + 7), 'Total Deductions: ₦' . number_format($sumDeduct));
    $sheet->setCellValue('A' . ($infoRow + 8), 'Total Net Pay: ₦' . number_format($sumTotal));
    
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