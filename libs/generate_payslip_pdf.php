<?php
session_start();
require_once '../config/config.php';
require_once '../report/pdf.php';

// Check if user is logged in
// if (!isset($_SESSION['user_id'])) {
//     http_response_code(401);
//     echo json_encode(['error' => 'Unauthorized access']);
//     exit;
// }

// Get parameters
$staff_id = $_POST['staff_id'] ?? $_GET['staff_id'] ?? '';
$period = $_POST['period'] ?? $_GET['period'] ?? '';

if (empty($staff_id) || empty($period)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

try {
    // Use the existing working functions from pdf.php
    $employeeDetails = fetchEmployeeDetails($conn, $staff_id, $period);
    $payslipDetails = fetchPayslipDetails($conn, $staff_id, $period);
    $fullPeriod = getPayPeriod($conn, $period);
    
    // Create PDF using existing logic
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    configurePDF($pdf);
    $htmlContent = generatePayslipHtml($employeeDetails, $payslipDetails, $fullPeriod);
    $pdf->writeHTMLCell(0, 0, '', '', $htmlContent, 0, 1, 0, true, '', true);

    // Set filename
    $filename = 'Payslip_' . $staff_id . '_' . $period . '_' . date('Y-m-d') . '.pdf';

    // Output PDF for download
    $pdf->Output($filename, 'D'); // 'D' for download

} catch (Exception $e) {
    // Fallback to HTML print version
    header('Location: get_payslip_content.php?staff_id=' . urlencode($staff_id) . '&period=' . urlencode($period) . '&print=1');
    exit;
}
?>