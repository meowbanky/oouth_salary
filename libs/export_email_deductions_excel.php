<?php
require_once '../vendor/autoload.php';
require_once '../Connections/paymaster.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Set headers for file download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="email_deductions_export_' . date('Y-m-d_H-i-s') . '.xlsx"');
header('Cache-Control: max-age=0');

try {
    // Create new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator('Salary Management System')
        ->setLastModifiedBy('Salary Management System')
        ->setTitle('Email Deductions Export')
        ->setSubject('Email Deduction Data Export')
        ->setDescription('Export of email deduction information from Salary Management System')
        ->setKeywords('email deduction export salary management')
        ->setCategory('Email Deduction Data');
    
    // Set headers
    $sheet->setCellValue('A1', 'ID');
    $sheet->setCellValue('B1', 'List Name');
    $sheet->setCellValue('C1', 'Email');
    $sheet->setCellValue('D1', 'CC Email');
    
    // Style the header row
    $sheet->getStyle('A1:D1')->getFont()->setBold(true);
    $sheet->getStyle('A1:D1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
    $sheet->getStyle('A1:D1')->getFill()->getStartColor()->setRGB('4472C4');
    $sheet->getStyle('A1:D1')->getFont()->getColor()->setRGB('FFFFFF');
    
    // Fetch data from database
    $query = $conn->prepare(
        'SELECT tbl_earning_deduction.edDesc, email_deductionlist.allow_id, email_deductionlist.email, email_deductionlist.bcc 
         FROM email_deductionlist 
         INNER JOIN tbl_earning_deduction ON email_deductionlist.allow_id = tbl_earning_deduction.ed_id'
    );
    $query->execute();
    $emailDeductions = $query->fetchAll(PDO::FETCH_ASSOC);
    
    // Populate data
    $row = 2;
    foreach ($emailDeductions as $item) {
        $sheet->setCellValue('A' . $row, $item['allow_id']);
        $sheet->setCellValue('B' . $row, $item['edDesc']);
        $sheet->setCellValue('C' . $row, $item['email']);
        $sheet->setCellValue('D' . $row, $item['bcc']);
        $row++;
    }
    
    // Auto-size columns
    $sheet->getColumnDimension('A')->setAutoSize(true);
    $sheet->getColumnDimension('B')->setAutoSize(true);
    $sheet->getColumnDimension('C')->setAutoSize(true);
    $sheet->getColumnDimension('D')->setAutoSize(true);
    
    // Add borders to all cells with data
    $lastRow = $row - 1;
    $sheet->getStyle('A1:D' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
    
    // Create the Excel file
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    
} catch (Exception $e) {
    // If there's an error, create a simple error file
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment;filename="error_log.txt"');
    echo "Error exporting email deductions: " . $e->getMessage();
} 