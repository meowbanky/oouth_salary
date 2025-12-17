<?php
session_start();
require_once('../Connections/paymaster.php');
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) == '') {
    http_response_code(401);
    exit('Unauthorized');
}

$periodFrom = filter_input(INPUT_POST, 'periodFrom', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: -1;
$periodTo = filter_input(INPUT_POST, 'periodTo', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: -1;
$title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 'Variance Report';

if ($periodFrom == -1 || $periodTo == -1) {
    http_response_code(400);
    exit('Missing parameters');
}

function getPeriodText(PDO $conn, $periodId) {
    try {
        $q = $conn->prepare('SELECT description, periodYear FROM payperiods WHERE periodId = ?');
        $q->execute([$periodId]);
        $r = $q->fetch(PDO::FETCH_ASSOC);
        return $r ? ($r['description'] . '-' . $r['periodYear']) : '';
    } catch (PDOException $e) {
        return '';
    }
}

$fromText = getPeriodText($conn, $periodFrom);
$toText = getPeriodText($conn, $periodTo);

// Build dataset of staff across both periods
try {
    $q = $conn->prepare('SELECT staff_id, ANY_VALUE(master_staff.`NAME`) AS `NAME` FROM master_staff WHERE period = ? UNION SELECT staff_id, ANY_VALUE(master_staff.`NAME`) AS `NAME` FROM master_staff WHERE period = ? ORDER BY staff_id');
    $q->execute([$periodTo, $periodFrom]);
    $rows = $q->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    exit('DB error');
}

// Helper variance getter (copy of logic from classes/model.php)
function varianceAmount(PDO $conn, $periodId, $staffId) {
    try {
        $query = $conn->prepare('SELECT tbl_master.period,
       SUM(tbl_master.allow) as amount
FROM tbl_master
INNER JOIN employee ON employee.staff_id = tbl_master.staff_id
RIGHT JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id
INNER JOIN tbl_dept ON tbl_dept.dept_id = employee.DEPTCD
INNER JOIN payperiods ON payperiods.periodId = tbl_master.period
WHERE tbl_master.period = ? 
AND employee.staff_id = ?
GROUP BY employee.staff_id');
        $res = $query->execute(array($periodId, $staffId));
        if ($row = $query->fetch()) {
            return (float)$row['amount'];
        } else {
            return 0.0;
        }
    } catch (PDOException $e) {
        return 0.0;
    }
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setCellValue('A1', 'OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL');
$sheet->mergeCells('A1:F1');
$sheet->setCellValue('A2', 'Payroll Variance Between the Month of ' . $fromText . ' AND ' . $toText);
$sheet->mergeCells('A2:F2');

$sheet->fromArray(['S/N', 'Staff No', 'Name', $fromText, $toText, 'Variance'], null, 'A4');

$row = 5; $sn = 1; $sumCurrent = 0; $sumPrevious = 0;
foreach ($rows as $r) {
    $current = varianceAmount($conn, $periodFrom, $r['staff_id']);
    $previous = varianceAmount($conn, $periodTo, $r['staff_id']);
    $variance = $current - $previous;

    $sheet->fromArray([
        $sn,
        $r['staff_id'] ?? '',
        $r['NAME'] ?? '',
        $current ?: 0,
        $previous ?: 0,
        $variance ?: 0,
    ], null, 'A' . $row);

    $sumCurrent += $current;
    $sumPrevious += $previous;
    $sn++; $row++;
}

$totalVariance = $sumCurrent - $sumPrevious;
$sheet->fromArray(['TOTAL', '', '', $sumCurrent ?: 0, $sumPrevious ?: 0, $totalVariance ?: 0], null, 'A' . $row);

foreach (range('A','F') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

ob_start();
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
$excelOutput = ob_get_clean();
echo base64_encode($excelOutput);
exit;
?>