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
$activeWorksheet->mergeCells('A1:I1');
$activeWorksheet->setCellValue('A1', 'Olabisi Onabanjo University Teaching Hospital, Sagamu');
$activeWorksheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2e7d32');
$activeWorksheet->getStyle('A1')->getFont()->setBold(true);
$activeWorksheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Set report title
$activeWorksheet->mergeCells('A2:I2');
$activeWorksheet->setCellValue('A2', 'GROSS REPORT');
$activeWorksheet->getStyle('A2')->getFont()->setBold(true);
$activeWorksheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Set period
$activeWorksheet->mergeCells('A3:I3');
$activeWorksheet->setCellValue('A3', 'Period: ' . $period_text);
$activeWorksheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Set headers starting from row 4
$headerRow = 4;
$activeWorksheet->setCellValue('A' . $headerRow, 'S/No');
$activeWorksheet->setCellValue('B' . $headerRow, 'Staff No.');
$activeWorksheet->setCellValue('C' . $headerRow, 'Name');
$activeWorksheet->setCellValue('D' . $headerRow, 'Dept');
$activeWorksheet->setCellValue('E' . $headerRow, 'Grade');
$activeWorksheet->setCellValue('F' . $headerRow, 'Step');
$activeWorksheet->setCellValue('G' . $headerRow, 'Acct No.');
$activeWorksheet->setCellValue('H' . $headerRow, 'Bank');
$activeWorksheet->setCellValue('I' . $headerRow, 'Gross Pay');

// Apply header styling
$headerRange = 'A' . $headerRow . ':I' . $headerRow;
$activeWorksheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('d3d3d3');
$activeWorksheet->getStyle($headerRange)->getFont()->setBold(true);

// Align headers
$activeWorksheet->getStyle('A' . $headerRow . ':H' . $headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // Text columns
$activeWorksheet->getStyle('I' . $headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT); // Gross Pay

// Debug: Log before data section
file_put_contents(__DIR__ . '/debug_excel.log', 'Starting data section' . PHP_EOL, FILE_APPEND);

// Data section
$row = $headerRow + 1;
$sumTotal = 0;

try {
    $sql = 'SELECT
        any_value(tbl_master.staff_id) AS staff_id,
        any_value(Sum(tbl_master.allow)) AS allow,
        any_value(Sum(tbl_master.deduc)) AS deduc,
        any_value((Sum(tbl_master.allow) - Sum(tbl_master.deduc))) AS net,
        any_value(master_staff.`NAME`) AS `NAME`,
        any_value(tbl_bank.BNAME) AS BNAME,
        ANY_VALUE(master_staff.BCODE) AS BCODE,
        ANY_VALUE(master_staff.ACCTNO) AS ACCTNO,
        any_value(master_staff.GRADE) AS GRADE,
        any_value(master_staff.STEP) AS STEP,
        any_value(tbl_dept.dept) AS dept 
    FROM
        tbl_master
        INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id
        INNER JOIN tbl_bank ON tbl_bank.BCODE = master_staff.BCODE
        INNER JOIN tbl_dept ON master_staff.DEPTCD = tbl_dept.dept_id 
    WHERE tbl_master.period = ? AND master_staff.period = ? GROUP BY tbl_master.staff_id';
    $query = $conn->prepare($sql);
    $fin = $query->execute(array($period, $period));
    $res = $query->fetchAll(PDO::FETCH_ASSOC);

    foreach ($res as $link) {
        $activeWorksheet->setCellValue('A' . $row, $row - $headerRow);
        $activeWorksheet->setCellValue('B' . $row, $link['staff_id']);
        $activeWorksheet->setCellValue('C' . $row, $link['NAME']);
        $activeWorksheet->setCellValue('D' . $row, $link['dept']);
        $activeWorksheet->setCellValue('E' . $row, $link['GRADE']);
        $activeWorksheet->setCellValue('F' . $row, $link['STEP']);
        $activeWorksheet->setCellValue('G' . $row, $link['ACCTNO']);
        $activeWorksheet->setCellValue('H' . $row, $link['BNAME']);
        $activeWorksheet->setCellValue('I' . $row, floatval($link['allow']));
        $sumTotal += floatval($link['allow']);
        $row++;
    }

    // Totals row
    $activeWorksheet->setCellValue('A' . $row, '');
    $activeWorksheet->setCellValue('B' . $row, '');
    $activeWorksheet->setCellValue('C' . $row, '');
    $activeWorksheet->setCellValue('D' . $row, 'TOTAL');
    $activeWorksheet->setCellValue('E' . $row, '');
    $activeWorksheet->setCellValue('F' . $row, '');
    $activeWorksheet->setCellValue('G' . $row, '');
    $activeWorksheet->setCellValue('H' . $row, '');
    $activeWorksheet->setCellValue('I' . $row, $sumTotal);
    $activeWorksheet->getStyle('D' . $row . ':I' . $row)->getFont()->setBold(true);
} catch (PDOException $e) {
    file_put_contents(__DIR__ . '/debug_excel.log', 'Data section PDOException: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

// Apply borders
$dataRange = 'A' . $headerRow . ':I' . $row;
$activeWorksheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// Align data columns
$activeWorksheet->getStyle('A' . ($headerRow + 1) . ':H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // Text columns
$activeWorksheet->getStyle('I' . ($headerRow + 1) . ':I' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT); // Gross Pay

// Adjust column widths
$activeWorksheet->getColumnDimension('A')->setWidth(8);
$activeWorksheet->getColumnDimension('B')->setWidth(15);
$activeWorksheet->getColumnDimension('C')->setWidth(25);
$activeWorksheet->getColumnDimension('D')->setWidth(20);
$activeWorksheet->getColumnDimension('E')->setWidth(12);
$activeWorksheet->getColumnDimension('F')->setWidth(8);
$activeWorksheet->getColumnDimension('G')->setWidth(15);
$activeWorksheet->getColumnDimension('H')->setWidth(20);
$activeWorksheet->getColumnDimension('I')->setWidth(15);

// Debug: Log before file save
file_put_contents(__DIR__ . '/debug_excel.log', 'Saving Excel file' . PHP_EOL, FILE_APPEND);

$tempfilepath = 'Gross_Report_' . $period_text . '.xlsx';
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