<?php
session_start();

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

$period = filter_input(INPUT_POST, 'period', FILTER_VALIDATE_INT) ?: -1;
$period_text = filter_input(INPUT_POST, 'period_text', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';

try {
    $query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear FROM payperiods WHERE periodId = ?');
    $res = $query->execute(array($period));
    $out = $query->fetchAll(PDO::FETCH_ASSOC);
    $period_text = $out[0]['description'] . '-' . $out[0]['periodYear'];
} catch (PDOException $e) {
    $e->getMessage();
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
$activeWorksheet->setCellValue('A2', 'PAYROLL SUMMARY');
$activeWorksheet->getStyle('A2')->getFont()->setBold(true);
$activeWorksheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Set period
$activeWorksheet->mergeCells('A3:C3');
$activeWorksheet->setCellValue('A3', 'Period: ' . $period_text);
$activeWorksheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Set headers starting from row 4
$headerRow = 4;
$activeWorksheet->setCellValue('A' . $headerRow, 'Code');
$activeWorksheet->setCellValue('B' . $headerRow, 'Description');
$activeWorksheet->setCellValue('C' . $headerRow, 'Amount');

// Apply header styling
$headerRange = 'A' . $headerRow . ':C' . $headerRow;
$activeWorksheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('d3d3d3');
$activeWorksheet->getStyle($headerRange)->getFont()->setBold(true);

// Earnings data
$row = $headerRow + 1;
$activeWorksheet->setCellValue('A' . $row, '');
$activeWorksheet->setCellValue('B' . $row, 'Earnings');
$activeWorksheet->mergeCells('B' . $row . ':C' . $row);
$row++;

try {
    $query = $conn->prepare('SELECT sum(tbl_master.allow) as allow, allow_id, tbl_earning_deduction.ed FROM tbl_master INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id WHERE tbl_master.type = ? and period = ? GROUP BY tbl_master.allow_id');
    $fin = $query->execute(array('1', $period));
    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $sumAll = 0;

    foreach ($res as $link) {
        $activeWorksheet->setCellValue('A' . $row, $link['allow_id']);
        $activeWorksheet->setCellValue('B' . $row, $link['ed']);
        $activeWorksheet->setCellValue('C' . $row, floatval($link['allow']));
        $sumAll += floatval($link['allow']);
        $row++;
    }

    $activeWorksheet->setCellValue('A' . $row, '');
    $activeWorksheet->setCellValue('B' . $row, 'TOTAL earnings');
    $activeWorksheet->setCellValue('C' . $row, $sumAll);
    $activeWorksheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
    $row++;
} catch (PDOException $e) {
    echo $e->getMessage();
    exit;
}

$activeWorksheet->setCellValue('A' . $row, '');
$activeWorksheet->setCellValue('B' . $row, '');
$activeWorksheet->setCellValue('C' . $row, '');
$row++;

$activeWorksheet->setCellValue('A' . $row, '');
$activeWorksheet->setCellValue('B' . $row, 'DEDUCTIONS');
$activeWorksheet->mergeCells('B' . $row . ':C' . $row);
$row++;

try {
    $query = $conn->prepare('SELECT sum(tbl_master.deduc) as deduct, allow_id, tbl_earning_deduction.ed FROM tbl_master INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id WHERE tbl_master.type = ? and period = ? GROUP BY tbl_master.allow_id');
    $fin = $query->execute(array('2', $period));
    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $sumDeduct = 0;

    foreach ($res as $link) {
        $activeWorksheet->setCellValue('A' . $row, $link['allow_id']);
        $activeWorksheet->setCellValue('B' . $row, $link['ed']);
        $activeWorksheet->setCellValue('C' . $row, floatval($link['deduct']));
        $sumDeduct += floatval($link['deduct']);
        $row++;
    }

    $activeWorksheet->setCellValue('A' . $row, '');
    $activeWorksheet->setCellValue('B' . $row, 'TOTAL DEDUCTIONS');
    $activeWorksheet->setCellValue('C' . $row, $sumDeduct);
    $activeWorksheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
    $row++;

    $activeWorksheet->setCellValue('A' . $row, '');
    $activeWorksheet->setCellValue('B' . $row, 'NET PAY');
    $activeWorksheet->setCellValue('C' . $row, floatval($sumAll) - floatval($sumDeduct));
    $activeWorksheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
} catch (PDOException $e) {
    echo $e->getMessage();
    exit;
}

// Apply borders
$dataRange = 'A' . ($headerRow) . ':C' . $row;
$activeWorksheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// Align text
$activeWorksheet->getStyle($dataRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$activeWorksheet->getStyle('B' . ($headerRow + 1) . ':B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

// Adjust column widths
$activeWorksheet->getColumnDimension('A')->setWidth(10);
$activeWorksheet->getColumnDimension('B')->setWidth(30);
$activeWorksheet->getColumnDimension('C')->setWidth(15);

$tempfilepath = 'Payroll_Summary_' . $period_text . '.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($tempfilepath);

ob_start();
readfile($tempfilepath);
$excel_output = base64_encode(ob_get_clean());

if (file_exists($tempfilepath)) {
    unlink($tempfilepath);
}

header('Content-Type: application/json');
echo json_encode($excel_output);
?>