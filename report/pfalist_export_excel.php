<?php
session_start();
require_once('../Connections/paymaster.php');
include_once('../classes/model.php');
require_once('../vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) == '') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Retrieve POST parameters
$period = filter_input(INPUT_POST, 'period', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: -1;
$pfa = filter_input(INPUT_POST, 'pfa', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: -1;
$period_text = filter_input(INPUT_POST, 'period_text', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 'Unknown Period';

// Fetch PFA name for the title using output buffering
ob_start();

retrieveDescSingleFilter('tbl_pfa', 'PFANAME', 'PFACODE', $pfa);
$pfaName = ob_get_clean() ?: 'All PFA';

// Initialize PhpSpreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set document properties
$spreadsheet->getProperties()
    ->setCreator($_SESSION['SESS_FIRST_NAME'])
    ->setTitle('Pension Funds Report')
    ->setDescription('Detailed Pension Funds Report for ' . $pfaName);

// Add title
$sheet->setCellValue('A1', 'OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL');
$sheet->setCellValue('A2', $pfaName . ' Pension Report for the Month of: ' . $period_text);
$sheet->mergeCells('A1:F1');
$sheet->mergeCells('A2:F2');
$sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Set column headers
$headers = ['S/No.', 'Staff No.', 'Name', 'PFA', 'PIN', 'Amount'];
$sheet->fromArray($headers, null, 'A4');
$sheet->getStyle('A4:F4')->getFont()->setBold(true);
$sheet->getStyle('A4:F4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD3D3D3');
$sheet->getStyle('A4:F4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

// Fetch data
try {
    $sql = $pfa != -1
        ? 'SELECT tbl_master.deduc, master_staff.staff_id, master_staff.`NAME`, master_staff.PFAACCTNO, tbl_pfa.PFANAME 
           FROM tbl_master 
           INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id 
           INNER JOIN tbl_pfa ON master_staff.PFACODE = tbl_pfa.PFACODE 
           WHERE tbl_master.allow_id = ? AND master_staff.period = ? AND master_staff.PFACODE = ? AND tbl_master.period = ? 
           ORDER BY tbl_master.staff_id ASC'
        : 'SELECT tbl_master.deduc, master_staff.staff_id, master_staff.`NAME`, master_staff.PFAACCTNO, tbl_pfa.PFANAME 
           FROM tbl_master 
           INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id 
           INNER JOIN tbl_pfa ON master_staff.PFACODE = tbl_pfa.PFACODE 
           WHERE tbl_master.allow_id = ? AND master_staff.period = ? AND tbl_master.period = ? 
           ORDER BY tbl_master.staff_id ASC';

    $query = $conn->prepare($sql);
    $params = $pfa != -1 ? ['50', $period, $pfa, $period] : ['50', $period, $period];
    $query->execute($params);

    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $rowNum = 5;
    $sumTotal = 0;
    $counter = 1;

    foreach ($res as $row) {
        $data = [
            $counter,
            $row['staff_id'],
            $row['NAME'],
            $row['PFANAME'],
            $row['PFAACCTNO'],
            number_format($row['deduc'], 2)
        ];
        $sheet->fromArray($data, null, 'A' . $rowNum);
        $sheet->getStyle('A' . $rowNum . ':B' . $rowNum)->getFont()->setBold(true);
        $sheet->getStyle('F' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sumTotal += floatval($row['deduc']);
        $rowNum++;
        $counter++;
    }

    // Add total row
    $sheet->setCellValue('A' . $rowNum, 'TOTAL');
    $sheet->mergeCells('A' . $rowNum . ':E' . $rowNum);
    $sheet->setCellValue('F' . $rowNum, number_format($sumTotal, 2));
    $sheet->getStyle('A' . $rowNum . ':F' . $rowNum)->getFont()->setBold(true);
    $sheet->getStyle('F' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    // Apply borders
    $sheet->getStyle('A4:F' . $rowNum)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

    // Adjust column widths
    foreach (range('A', 'F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit();
}

// Generate Excel file and output as base64
ob_start();
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
$excelData = ob_get_clean();
$base64 = base64_encode($excelData);
echo $base64;
?>