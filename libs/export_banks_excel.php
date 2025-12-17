<?php
require_once '../vendor/autoload.php';
require_once '../Connections/paymaster.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Set headers for file download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="banks_export_' . date('Y-m-d_H-i-s') . '.xlsx"');
header('Cache-Control: max-age=0');

try {
    // Create new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator('Salary Management System')
        ->setLastModifiedBy('Salary Management System')
        ->setTitle('Banks Export')
        ->setSubject('Banks Data Export')
        ->setDescription('Export of bank information from Salary Management System')
        ->setKeywords('banks export salary management')
        ->setCategory('Bank Data');
    
    // Set headers
    $sheet->setCellValue('A1', 'Bank Code');
    $sheet->setCellValue('B1', 'Bank Name');
    
    // Style the header row
    $sheet->getStyle('A1:B1')->getFont()->setBold(true);
    $sheet->getStyle('A1:B1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
    $sheet->getStyle('A1:B1')->getFill()->getStartColor()->setRGB('4472C4');
    $sheet->getStyle('A1:B1')->getFont()->getColor()->setRGB('FFFFFF');
    
    // Fetch data from database
    $query = $conn->prepare('SELECT * FROM tbl_bank ORDER BY BCODE ASC');
    $query->execute();
    $banks = $query->fetchAll(PDO::FETCH_ASSOC);
    
    // Populate data
    $row = 2;
    foreach ($banks as $bank) {
        $sheet->setCellValue('A' . $row, $bank['BCODE']);
        $sheet->setCellValue('B' . $row, $bank['BNAME']);
        $row++;
    }
    
    // Auto-size columns
    $sheet->getColumnDimension('A')->setAutoSize(true);
    $sheet->getColumnDimension('B')->setAutoSize(true);
    
    // Add borders to all cells with data
    $lastRow = $row - 1;
    $sheet->getStyle('A1:B' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
    
    // Create the Excel file
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    
} catch (Exception $e) {
    // If there's an error, create a simple error file
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment;filename="error_log.txt"');
    echo "Error exporting banks: " . $e->getMessage();
} 