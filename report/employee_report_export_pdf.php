<?php
// Wrapper to keep legacy endpoint used by employee_report.php
// Delegates to existing PDF exporter

require_once('../Connections/paymaster.php');
if (session_status() === PHP_SESSION_NONE) session_start();

// Map expected POST params
$_POST['month'] = isset($_POST['period_text']) ? $_POST['period_text'] : ($_POST['month'] ?? '');

// Delegate to existing PDF generator (outputs PDF directly)
require __DIR__ . '/employee_export_pdf.php';
exit;
?>


