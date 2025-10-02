<?php
// Start output buffering at the earliest point
ob_start();

// Set Content-Type header immediately
header('Content-Type: application/json');

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable display of errors
ini_set('max_execution_time', '0');
ini_set('memory_limit', '512M');

require_once('../classes/model.php');
require_once('Connections/paymaster.php');
require_once('payrollExporter.php');

if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Session expired. Please log in.']);
    exit();
}

try {
    // Ensure we have a database connection
    global $conn;

    if (!$conn) {
        throw new Exception("Database connection not available");
    }

    $exporter = new PayrollExporter($conn);
    $exporter->generateAndSendReport();

} catch (PDOException $e) {
    error_log("PDOException: " . $e->getMessage());
    error_log("Captured output before JSON: " . ob_get_contents());
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => "Database error occurred. Please try again later."
    ]);
    exit();
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    error_log("Captured output before JSON: " . ob_get_contents());
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => "An error occurred: " . $e->getMessage()
    ]);
    exit();
}

// Log output for debugging
error_log("Captured output at end: " . ob_get_contents());
ob_end_clean();
exit();
?>