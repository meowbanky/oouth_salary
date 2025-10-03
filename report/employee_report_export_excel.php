<?php
// Wrapper to keep legacy endpoint used by employee_report.php
// Delegates to existing exporter

require_once('../Connections/paymaster.php');
if (session_status() === PHP_SESSION_NONE) session_start();

// Ensure expected POST parameters are present
$period = isset($_POST['period']) ? $_POST['period'] : null;
$periodText = isset($_POST['period_text']) ? $_POST['period_text'] : null;

if (!$period) {
    http_response_code(400);
    echo 'Missing required parameter: period';
    exit;
}

// Directly execute the actual exporter (returns base64)
require __DIR__ . '/employee_export_excel.php';
exit;
?>


