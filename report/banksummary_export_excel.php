<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__.'/../vendor/autoload.php';
include_once('../classes/model.php');
require_once('../Connections/paymaster.php');

if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) === '')) {
    header("location: ../index.php");
    exit;
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Debug: Log start of script
file_put_contents(__DIR__ . '/debug_excel.log', 'Script started at ' . date('Y-m-d H:i:s') . PHP_EOL, FILE_APPEND);

$period = filter_input(INPUT_POST, 'period', FILTER_VALIDATE_INT) ?: -1;
$period_text = filter_input(INPUT_POST, 'period_text', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';

try {
    $query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear FROM payperiods WHERE periodId = ?');
    $res = $query->execute(array($period));
    $out = $query->fetchAll(PDO::FETCH_ASSOC);
    if (empty($out)) {
        throw new Exception('No period data found for periodId: ' . $period);
    }
    $period_text = $out[0]['description'] . '-' . $out[0]['periodYear'];
    file_put_contents(__DIR__ . '/debug_excel.log', 'Period text set to: ' . $period_text . PHP_EOL, FILE_APPEND);
} catch (PDOException $e) {
    file_put_contents(__DIR__ . '/debug_excel.log', 'PDOException: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/debug_excel.log', 'Exception: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

$spreadsheet = new Spreadsheet();
$activeWorksheet = $spreadsheet->getActiveSheet();

// Set title
$activeWorksheet->mergeCells('A1:C1');
$activeWorksheet->setCellValue('A1', 'Olabisi Onabanjo University Teaching Hospital, Sagamu');
$activeWorksheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2e7d32');
$activeWorksheet->getStyle('A1')->getFont()->setBold(true);
$activeWorksheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Set payroll summary
$activeWorksheet->mergeCells('A2:C2');
$activeWorksheet->setCellValue('A2', 'BANK SUMMARY');
$activeWorksheet->getStyle('A2')->getFont()->setBold(true);
$activeWorksheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Set period
$activeWorksheet->mergeCells('A3:C3');
$activeWorksheet->setCellValue('A3', 'Period: ' . $period_text);
$activeWorksheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Set headers starting from row 4
$headerRow = 4;
$activeWorksheet->setCellValue('A' . $headerRow, 'Bank Name');
$activeWorksheet->setCellValue('B' . $headerRow, 'No. of Employee');
$activeWorksheet->setCellValue('C' . $headerRow, 'Total Netpay');

// Apply header styling
$headerRange = 'A' . $headerRow . ':C' . $headerRow;
$activeWorksheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('d3d3d3');
$activeWorksheet->getStyle($headerRange)->getFont()->setBold(true);

// Align headers
$activeWorksheet->getStyle('A' . $headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // Bank Name
$activeWorksheet->getStyle('B' . $headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT); // No. of Employee
$activeWorksheet->getStyle('C' . $headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT); // Total Netpay

// Debug: Log before data section
file_put_contents(__DIR__ . '/debug_excel.log', 'Starting data section' . PHP_EOL, FILE_APPEND);

// Data section
$row = $headerRow + 1;
$sumTotal = 0;
$countStaff = 0;

try {
    $query = $conn->prepare('SELECT any_value(BNAME) as BNAME, any_value(Sum(tbl_master.allow) - Sum(tbl_master.deduc)) AS net, any_value(tbl_bank.BNAME) as BNAME, any_value(tbl_bank.BCODE) as BCODE FROM tbl_master INNER JOIN employee ON tbl_master.staff_id = employee.staff_id INNER JOIN tbl_bank ON employee.BCODE = tbl_bank.BCODE WHERE period = ? GROUP BY employee.BCODE order by any_value(tbl_bank.BNAME) ASC');
    $fin = $query->execute(array($period));
    $res = $query->fetchAll(PDO::FETCH_ASSOC);

    foreach ($res as $link) {
        $query2 = $conn->prepare('SELECT Count(employee.staff_id) as "numb" FROM employee WHERE BCODE = ? AND STATUSCD = ? GROUP BY BCODE');
        $fin2 = $query2->execute(array($link['BCODE'], 'A'));
        $res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
        $numb = 0;
        foreach ($res2 as $link2) {
            $numb = $link2['numb'];
            $countStaff += $numb;
        }

        $activeWorksheet->setCellValue('A' . $row, $link['BNAME']);
        $activeWorksheet->setCellValue('B' . $row, $numb);
        $activeWorksheet->setCellValue('C' . $row, floatval($link['net']));
        $sumTotal += floatval($link['net']);
        $row++;
    }

    // Totals row
    $activeWorksheet->setCellValue('A' . $row, 'TOTAL');
    $activeWorksheet->setCellValue('B' . $row, $countStaff);
    $activeWorksheet->setCellValue('C' . $row, $sumTotal);
    $activeWorksheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
} catch (PDOException $e) {
    file_put_contents(__DIR__ . '/debug_excel.log', 'Data section PDOException: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

// Apply borders
$dataRange = 'A' . $headerRow . ':C' . $row;
$activeWorksheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// Align data columns
$activeWorksheet->getStyle('A' . ($headerRow + 1) . ':A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // Bank Name
$activeWorksheet->getStyle('B' . ($headerRow + 1) . ':B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT); // No. of Employee
$activeWorksheet->getStyle('C' . ($headerRow + 1) . ':C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT); // Total Netpay

// Adjust column widths
$activeWorksheet->getColumnDimension('A')->setWidth(30);
$activeWorksheet->getColumnDimension('B')->setWidth(15);
$activeWorksheet->getColumnDimension('C')->setWidth(15);

// Debug: Log before file save
file_put_contents(__DIR__ . '/debug_excel.log', 'Saving Excel file' . PHP_EOL, FILE_APPEND);

$tempfilepath = 'Bank_Summary_' . $period_text . '.xlsx';
$writer = new Xlsx($spreadsheet);

// Check if the directory is writable
$tempDir = dirname($tempfilepath);
if (!is_writable($tempDir)) {
    file_put_contents(__DIR__ . '/debug_excel.log', 'Directory not writable: ' . $tempDir . PHP_EOL, FILE_APPEND);
    echo json_encode(['error' => 'Directory not writable: ' . $tempDir]);
    exit;
}

try {
    $writer->save($tempfilepath);
    file_put_contents(__DIR__ . '/debug_excel.log', 'Excel file saved to: ' . $tempfilepath . PHP_EOL, FILE_APPEND);
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/debug_excel.log', 'Error saving Excel file: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
    echo json_encode(['error' => 'Error saving Excel file: ' . $e->getMessage()]);
    exit;
}

// Debug: Log before reading file
file_put_contents(__DIR__ . '/debug_excel.log', 'Reading Excel file for base64 encoding' . PHP_EOL, FILE_APPEND);

ob_start();
readfile($tempfilepath);
$excel_output = base64_encode(ob_get_clean());

if (file_exists($tempfilepath)) {
    unlink($tempfilepath);
    file_put_contents(__DIR__ . '/debug_excel.log', 'Temporary file deleted' . PHP_EOL, FILE_APPEND);
}

// Debug: Log before sending response
file_put_contents(__DIR__ . '/debug_excel.log', 'Sending base64 response' . PHP_EOL, FILE_APPEND);

// Ensure no extra output before this
header('Content-Type: application/json');
echo json_encode($excel_output);
?>