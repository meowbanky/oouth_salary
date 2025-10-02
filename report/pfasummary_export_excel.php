<?php
require_once('../Connections/paymaster.php');
include_once('../classes/model.php');
require_once('../libs/App.php');
$App = new App();
$App->checkAuthentication();
require_once('../libs/middleware.php');
checkPermission();

// Get parameters
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

try {
    // Get PFA summary data
    $query = $conn->prepare('SELECT COUNT(staff_id) AS no, ANY_VALUE(tbl_pfa.PFANAME) AS PFANAME, master_staff.PFACODE FROM master_staff INNER JOIN tbl_pfa ON master_staff.PFACODE = tbl_pfa.PFACODE WHERE master_staff.period = ? GROUP BY master_staff.PFACODE');
    $query->execute([$period]);
    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    
    // Create new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator('OOUTH Salary Manager')
        ->setLastModifiedBy($_SESSION['SESS_MEMBER_ID'])
        ->setTitle('PFA Summary Report')
        ->setSubject('PFA Summary Data Export')
        ->setDescription('Export of PFA summary data from OOUTH Salary Management System')
        ->setKeywords('pfa, summary, export, oouth, salary')
        ->setCategory('PFA Data');
    
    // Set headers
    $headers = [
        'A' => 'S/N',
        'B' => 'PFA Name',
        'C' => 'No. of Employee',
        'D' => 'Amount'
    ];
    
    // Set header values and style
    foreach ($headers as $column => $header) {
        $sheet->setCellValue($column . '1', $header);
    }
    
    // Style the header row
    $headerRange = 'A1:D1';
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
        'A' => 8,   // S/N
        'B' => 30,  // PFA Name
        'C' => 15,  // No. of Employee
        'D' => 20   // Amount
    ];
    
    foreach ($columnWidths as $column => $width) {
        $sheet->getColumnDimension($column)->setWidth($width);
    }
    
    // Add data rows
    $row = 2;
    $sumTotal = 0;
    $countStaff = 0;
    $counter = 1;
    
    foreach ($res as $link) {
        // Get pension contribution amount for this PFA
        $query2 = $conn->prepare('SELECT SUM(tbl_master.deduc) as amount FROM master_staff INNER JOIN tbl_pfa ON master_staff.PFACODE = tbl_pfa.PFACODE INNER JOIN tbl_master ON tbl_master.staff_id = master_staff.staff_id WHERE allow_id = 50 AND tbl_master.period = ? AND master_staff.period = ? AND tbl_pfa.PFACODE = ?');
        $query2->execute([$period, $period, $link['PFACODE']]);
        $ftres = $query2->fetchAll(PDO::FETCH_ASSOC);
        
        $amount = 0;
        foreach ($ftres as $row2) {
            if (isset($row2['amount'])) {
                $amount = $row2['amount'];
            }
        }
        
        $sheet->setCellValue('A' . $row, $counter);
        $sheet->setCellValue('B' . $row, $link['PFANAME']);
        $sheet->setCellValue('C' . $row, $link['no']);
        $sheet->setCellValue('D' . $row, $amount);
        
        $sumTotal += floatval($amount);
        $countStaff += intval($link['no']);
        $counter++;
        $row++;
    }
    
    // Add total row
    if ($row > 2) {
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->setCellValue('B' . $row, '');
        $sheet->setCellValue('C' . $row, $countStaff);
        $sheet->setCellValue('D' . $row, $sumTotal);
        
        // Style the total row
        $totalRange = 'A' . $row . ':D' . $row;
        $sheet->getStyle($totalRange)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E5E7EB'] // Gray-200
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);
    }
    
    // Style data rows
    if ($row > 2) {
        $dataRange = 'A2:D' . ($row - 1);
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
        
        // Alternate row colors
        for ($i = 2; $i < $row; $i++) {
            if ($i % 2 == 0) {
                $sheet->getStyle('A' . $i . ':D' . $i)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F8FAFC'] // Gray-50
                    ]
                ]);
            }
        }
    }
    
    // Add report info
    $infoRow = $row + 2;
    $sheet->setCellValue('A' . $infoRow, 'Report Information:');
    $sheet->setCellValue('A' . ($infoRow + 1), 'Generated by: ' . $_SESSION['SESS_FIRST_NAME']);
    $sheet->setCellValue('A' . ($infoRow + 2), 'Date: ' . date('Y-m-d H:i:s'));
    $sheet->setCellValue('A' . ($infoRow + 3), 'Period: ' . $period_text);
    $sheet->setCellValue('A' . ($infoRow + 4), 'Total Employees: ' . $countStaff);
    $sheet->setCellValue('A' . ($infoRow + 5), 'Total Contributions: ₦' . number_format($sumTotal));
    
    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="PFA_Summary_' . $period_text . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    // Create writer and save
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    
} catch (Exception $e) {
    error_log($e->getMessage());
    die('Error generating Excel file. Please try again.');
}

exit();
?>