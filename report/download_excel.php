<?php
session_start();
require_once('../Connections/paymaster.php');
require 'vendor/autoload.php'; // You'll need PHPSpreadsheet library

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$period = $_GET['period'];
$bankCode = $_GET['bank'];

$period_sql = "SELECT CONCAT(LEFT(description, 3), '-', periodYear) as period FROM payperiods WHERE periodId = ?";
$params_period = [$period];
$query_sql = $conn->prepare($period_sql);
$query_sql->execute($params_period);
$result = $query_sql->fetch(PDO::FETCH_ASSOC);
$period_value = $result['period'];
if($bankCode != 'All') {
    $bank_sql = "SELECT tbl_bank.BNAME, tbl_bank.BCODE FROM tbl_bank WHERE BCODE = ?";
    $params_bank = [$bankCode];
    $query_bank = $conn->prepare($bank_sql);
    $query_bank->execute($params_bank);
    $result_banky = $query_bank->fetch(PDO::FETCH_ASSOC);
    $bank_value = $result_banky['BNAME'];
}else{
    $bank_value = 'All Banks';
}
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Headers
$headers = ['Account Number','Destination Bank Code', 'Destination Bank Name',  'Amount','Account Name', 'Narration', 'Reference Number'];
foreach($headers as $i => $header) {
    $sheet->setCellValue(chr(65 + $i).'1', $header);
}

// Data
$row = 2;
try {
    if ($bankCode != 'All') {
        $sql = "SELECT any_value(tbl_master.staff_id) as staff_id, any_value((Sum(tbl_master.allow)- Sum(tbl_master.deduc))) AS net, 
                any_value(master_staff.`NAME`) as `NAME`, any_value(tbl_bank.BNAME) as BNAME,
                ANY_VALUE(master_staff.ACCTNO) AS ACCTNO,  tbl_bank.CBN_CODE
                FROM tbl_master 
                INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id 
                INNER JOIN tbl_bank ON tbl_bank.BCODE = master_staff.BCODE 
                WHERE tbl_master.period = ? and master_staff.period = ? and master_staff.BCODE = ? AND master_staff.BCODE != '00'
                GROUP BY tbl_master.staff_id ORDER BY tbl_bank.BCODE";
        $params = [$period, $period, $bankCode];
    } else {
        $sql = "SELECT any_value(tbl_master.staff_id) as staff_id, any_value((Sum(tbl_master.allow)- Sum(tbl_master.deduc))) AS net, 
                any_value(master_staff.`NAME`) as `NAME`, any_value(tbl_bank.BNAME) as BNAME,
                ANY_VALUE(master_staff.ACCTNO) AS ACCTNO,  tbl_bank.CBN_CODE
                FROM tbl_master 
                INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id 
                INNER JOIN tbl_bank ON tbl_bank.BCODE = master_staff.BCODE 
                WHERE tbl_master.period = ? and master_staff.period = ? AND master_staff.BCODE != '00'
                GROUP BY tbl_master.staff_id ORDER BY tbl_bank.BCODE";
        $params = [$period, $period];
    }

    $query = $conn->prepare($sql);
    $query->execute($params);
    $results = $query->fetchAll(PDO::FETCH_ASSOC);

    foreach($results as $i => $data) {
        $sheet->setCellValue('A'.$row, $data['ACCTNO']);
        $sheet->setCellValue('B'.$row, $data['CBN_CODE']);
        $sheet->setCellValue('C'.$row, $data['BNAME']);
        $sheet->setCellValue('D'.$row, number_format($data['net'], 2));
        $sheet->setCellValue('E'.$row, $data['NAME']);
        $sheet->setCellValue('F'.$row, $period_value.' SALARY');
        $sheet->setCellValue('G'.$row, 11);
        $row++;
    }
} catch(PDOException $e) {
    die($e->getMessage());
}

// Set column widths
$sheet->getColumnDimension('A')->setWidth(8);
$sheet->getColumnDimension('B')->setWidth(12);
$sheet->getColumnDimension('C')->setWidth(30);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(20);
$sheet->getColumnDimension('F')->setWidth(15);
$sheet->getColumnDimension('G')->setWidth(15);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$period_value.'_Bank_Report_'.$bank_value.'.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;