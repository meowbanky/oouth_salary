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
$deduction = filter_input(INPUT_POST, 'deduction', FILTER_VALIDATE_INT) ?: -1;
$deduction_text = filter_input(INPUT_POST, 'deduction_text', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
$period_text = filter_input(INPUT_POST, 'period_text', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
$code = filter_input(INPUT_POST, 'code', FILTER_VALIDATE_INT) ?: -1;

$response['data'] = array();
$Data['S/No.'] = 'S/No.';
$Data['StaffNo'] = 'Staff No.';
$Data['Name'] = 'Name';
$Data['Amount'] = 'Amount';

if ($deduction == 87 || $deduction == 85) {
    $Data['Balance'] = 'Balance';
}

array_push($response['data'], $Data);

try {
    if ($code == 1) {
        $sql = 'SELECT tbl_master.allow as deduc, master_staff.staff_id, master_staff.`NAME` FROM tbl_master INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id WHERE tbl_master.allow_id = ? and tbl_master.period = ? and master_staff.period = ? order by master_staff.staff_id asc';
    } else {
        $sql = 'SELECT tbl_master.deduc as deduc, master_staff.staff_id, master_staff.`NAME` FROM tbl_master INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id WHERE tbl_master.allow_id = ? and tbl_master.period = ? and master_staff.period = ? order by master_staff.staff_id asc';
    }

    $query = $conn->prepare($sql);
    $fin = $query->execute(array($deduction, $period, $period));
    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $sumTotal = 0;
    $i = 1;

    foreach ($res as $row => $link) {
        $Data['S/No.'] = $i;
        $Data['StaffNo'] = $link['staff_id'];
        $Data['Name'] = $link['NAME'];
        $Data['Amount'] = floatval($link['deduc']);
        if ($deduction == 87 || $deduction == 85) {
            $loan = retrieveLoanStatus($link['staff_id'], $deduction);
            $repayment = retrieveLoanBalanceStatus($link['staff_id'], $deduction, $period);
            $Data['Balance'] = floatval($loan - $repayment);
        }
        $sumTotal += floatval($link['deduc']);
        array_push($response['data'], $Data);
        ++$i;
    }

    $Data['S/No.'] = '';
    $Data['StaffNo'] = '';
    $Data['Name'] = 'TOTAL';
    $Data['Amount'] = $sumTotal;
    if ($deduction == 87 || $deduction == 85) {
        $Data['Balance'] = '';
    }
    array_push($response['data'], $Data);
} catch (PDOException $e) {
    echo $e->getMessage();
    exit;
}

$spreadsheet = new Spreadsheet();
$activeWorksheet = $spreadsheet->getActiveSheet();

// Set title
$activeWorksheet->mergeCells('A1:D1');
$activeWorksheet->setCellValue('A1', 'Olabisi Onabanjo University Teaching Hospital, Sagamu');
$activeWorksheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2e7d32'); // Green background
$activeWorksheet->getStyle('A1')->getFont()->setBold(true);
$activeWorksheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Set payroll summary
$activeWorksheet->mergeCells('A2:D2');
$activeWorksheet->setCellValue('A2', $deduction_text);
$activeWorksheet->getStyle('A2')->getFont()->setBold(true);
$activeWorksheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Set period
$activeWorksheet->mergeCells('A3:D3');
$activeWorksheet->setCellValue('A3', 'Period: ' . $period_text);
$activeWorksheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Set headers starting from row 4
$headerRow = 4;
$activeWorksheet->fromArray($response['data'], null, 'A' . $headerRow);

// Apply header styling
$headerRange = 'A' . $headerRow . ':' . ($deduction == 87 || $deduction == 85 ? 'D' . $headerRow : 'D' . $headerRow);
$activeWorksheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('d3d3d3'); // Gray background
$activeWorksheet->getStyle($headerRange)->getFont()->setBold(true);

// Apply borders to the table
$dataRange = 'A' . $headerRow . ':' . ($deduction == 87 || $deduction == 85 ? 'D' . (count($response['data']) + $headerRow - 1) : 'D' . (count($response['data']) + $headerRow - 1));
$activeWorksheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// Adjust column widths
$activeWorksheet->getColumnDimension('A')->setWidth(10);
$activeWorksheet->getColumnDimension('B')->setWidth(15);
$activeWorksheet->getColumnDimension('C')->setWidth(30);
if ($deduction == 87 || $deduction == 85) {
    $activeWorksheet->getColumnDimension('D')->setWidth(15);
}

// Align text
$activeWorksheet->getStyle($dataRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
// Override alignment for column C (Name) to left
$activeWorksheet->getStyle('C' . $headerRow . ':C' . (count($response['data']) + $headerRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

// Bold the TOTAL row
$totalRow = count($response['data']) + $headerRow - 1; // Last row of the data
$totalRange = 'A' . $totalRow . ':' . ($deduction == 87 || $deduction == 85 ? 'D' . $totalRow : 'D' . $totalRow);
$activeWorksheet->getStyle($totalRange)->getFont()->setBold(true);

$tempfilepath = 'Deduction_Report_' . $deduction_text . '_' . $period_text . '.xlsx';
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