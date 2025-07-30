<?php

include_once 'Connections/paymaster.php'; // Ensure this file contains the PDO connection setup
require 'vendor/autoload.php'; // Ensure Composer's autoload is included for PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

try {
    // Get the selected periodId from the request
    $periodId = $_GET['periodId'];

    // Fetch the period description and year
    $period_sql = "SELECT CONCAT(LEFT(description, 3), '-', periodYear) as period FROM payperiods WHERE periodId = ?";
    $query_sql = $conn->prepare($period_sql);
    $query_sql->execute([$periodId]);
    $result = $query_sql->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        die("Invalid period ID.");
    }

    $period_value = $result['period'];

    // Fetch data from the database with the filter
    $sql = "
        SELECT 
            ms.NAME, 
            ms.staff_id,
            ms.TIN,
            e.EMAIL, 
            SUM(m.allow) AS total_gross,
            MAX(CASE WHEN m.allow_id = 41 THEN m.deduc ELSE 0 END) AS tax_payable,
            m.period
        FROM 
            tbl_master m
        JOIN 
            master_staff ms ON m.staff_id = ms.staff_id
        JOIN 
            tbl_earning_deduction ed ON m.allow_id = ed.ed_id
        LEFT JOIN employee e ON ms.staff_id = e.staff_id
        WHERE 
            m.period = :periodId 
            AND ms.period = :periodId2
        GROUP BY 
            ms.NAME,
            ms.TIN,
            m.period,
            ms.staff_id,
            e.EMAIL 
        ORDER BY 
            ms.staff_id;
    ";

    // Prepare the SQL statement
    $stmt = $conn->prepare($sql);

    // Bind the parameters
    $stmt->bindParam(':periodId', $periodId, PDO::PARAM_INT);
    $stmt->bindParam(':periodId2', $periodId, PDO::PARAM_INT);

    // Execute the query
    $stmt->execute();

    // Fetch all results as an associative array
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($results) > 0) {
        // Create a new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $headers = ['EMP NO', 'TIN NO', 'NAME', 'EMAIL', 'MONTHLY GROSS', 'TAX PAYABLE'];
        foreach ($headers as $i => $header) {
            $sheet->setCellValue(chr(65 + $i) . '1', $header);
            $sheet->getColumnDimension(chr(65 + $i))->setAutoSize(true);
            $sheet->getStyle(chr(65 + $i) . '1')->getFont()->setBold(true);
        }

        // Populate the spreadsheet with data
        $row = 2;
        foreach ($results as $row_data) {
            $sheet->setCellValue('A' . $row, $row_data['staff_id']);
            $sheet->setCellValue('B' . $row, $row_data['TIN']);
            $sheet->setCellValue('C' . $row, $row_data['NAME']);
            $sheet->setCellValue('D' . $row, $row_data['EMAIL']);
            $sheet->setCellValue('E' . $row, $row_data['total_gross']);
            $sheet->setCellValue('F' . $row, $row_data['tax_payable']);
            $row++;
        }

        // Set column widths (optional, as auto-size is already enabled)
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(15);

        // Set headers for file download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $period_value . '_Tax_Returns.xlsx"');
        header('Cache-Control: max-age=0');

        // Write the spreadsheet to the output
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    } else {
        echo "No data found for the selected period.";
    }
} catch (PDOException $e) {
    // Handle database connection or query errors
    die("Database error: " . $e->getMessage());
} finally {
    // Close the connection
    $conn = null;
}
?>