<?php
session_start();
require_once('../Connections/paymaster.php');
require_once('../vendor/autoload.php'); // Include PhpSpreadsheet via Composer

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Check for session
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Retrieve POST parameters
$period = filter_input(INPUT_POST, 'period', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: -1;
$bank = filter_input(INPUT_POST, 'bank', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: -1;
$period_text = filter_input(INPUT_POST, 'period_text', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 'Unknown Period';

// Fetch bank name for the title
$bankName = '';
try {
    $query = $conn->prepare('SELECT tbl_bank.BNAME FROM tbl_bank WHERE BCODE = ?');
    $query->execute(array($bank));
    $out = $query->fetchAll(PDO::FETCH_ASSOC);
    while ($row = array_shift($out)) {
        $bankName = $row['BNAME'];
    }
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit();
}

// Initialize PhpSpreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set document properties
$spreadsheet->getProperties()
    ->setCreator($_SESSION['SESS_FIRST_NAME'])
    ->setTitle('Netpay to Bank Report')
    ->setDescription('Detailed Netpay to Bank Report for ' . $bankName);

// Add title
$sheet->setCellValue('A1', 'OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL');
$sheet->setCellValue('A2', 'Bank Report for ' . $bankName . ' for the Month of: ' . $period_text);
$sheet->mergeCells('A1:F1');
$sheet->mergeCells('A2:F2');
$sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Set column headers
$headers = ['Destination Bank Code', 'Destination Bank Name', 'Account Number','Account Name', 'Amount',  'Narration','Reference Number'];
$sheet->fromArray($headers, null, 'A4');
$sheet->getStyle('A4:G4')->getFont()->setBold(true);
$sheet->getStyle('A4:G4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD3D3D3');
$sheet->getStyle('A4:G4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

// Fetch data
try {
    if ($bank != 'All') {
        $sql = 'SELECT any_value(tbl_master.staff_id) as staff_id, any_value(Sum(tbl_master.allow)) as allow, any_value(Sum(tbl_master.deduc)) as deduc, any_value((Sum(tbl_master.allow)- Sum(tbl_master.deduc))) AS net, any_value(master_staff.`NAME`) as `NAME`, any_value(tbl_bank.BNAME) as BNAME,
                ANY_VALUE(master_staff.BCODE) AS BCODE,ANY_VALUE(tbl_bank.CBN_CODE) AS CBN_CODE, ANY_VALUE(master_staff.ACCTNO) AS ACCTNO 
                FROM tbl_master 
                INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id 
                INNER JOIN tbl_bank ON tbl_bank.BCODE = master_staff.BCODE 
                WHERE tbl_master.period = ? and master_staff.period = ? and master_staff.BCODE = ?
                GROUP BY tbl_master.staff_id';
        $query = $conn->prepare($sql);
        $query->execute(array($period, $period, $bank));
    } else {
        $sql = 'SELECT any_value(tbl_master.staff_id) as staff_id, any_value(Sum(tbl_master.allow)) as allow, any_value(Sum(tbl_master.deduc)) as deduc, any_value((Sum(tbl_master.allow)- Sum(tbl_master.deduc))) AS net, any_value(master_staff.`NAME`) as `NAME`, any_value(tbl_bank.BNAME) as BNAME,
                ANY_VALUE(master_staff.BCODE) AS BCODE, ANY_VALUE(tbl_bank.CBN_CODE) AS CBN_CODE,ANY_VALUE(master_staff.ACCTNO) AS ACCTNO 
                FROM tbl_master 
                INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id 
                INNER JOIN tbl_bank ON tbl_bank.BCODE = master_staff.BCODE 
                WHERE tbl_master.period = ? and master_staff.period = ? and tbl_bank.CBN_CODE <> "" 
                GROUP BY tbl_master.staff_id 
                ORDER BY BCODE';
        $query = $conn->prepare($sql);
        $query->execute(array($period, $period));
    }

    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $rowNum = 5;
    $sumTotal = 0;
    $i = 1;

    foreach ($res as $row) {
        $data = [
            $row['CBN_CODE'],
            $row['BNAME'],
            $row['ACCTNO'],
            $row['NAME'],
            number_format($row['net'], 2),
            $period_text.' SALARY',
            11
        ];
        $sheet->fromArray($data, null, 'A' . $rowNum);
        // Uppercase S/No and Staff No.
//        $sheet->getStyle('A' . $rowNum . ':A' . $rowNum)->getFont()->setBold(true);
        $sheet->getStyle('F' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sumTotal += floatval($row['net']);
        $rowNum++;
        $i++;
    }

    // Add total row
    $sheet->setCellValue('A' . $rowNum, 'TOTAL');
    $sheet->mergeCells('A' . $rowNum . ':C' . $rowNum);
    $sheet->setCellValue('D' . $rowNum, number_format($sumTotal, 2));
    $sheet->getStyle('A' . $rowNum . ':F' . $rowNum)->getFont()->setBold(true);
    $sheet->getStyle('D' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    // Apply borders
    $sheet->getStyle('A4:G' . $rowNum)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

    // Adjust column widths
    foreach (range('A', 'G') as $col) {
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