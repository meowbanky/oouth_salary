<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', '0');
ini_set('memory_limit', '512M');

require_once('../classes/model.php');
require_once('Connections/paymaster.php');
require_once 'payrollExporter.php';

if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
    header("location: ../index.php");
    exit();
}

try {
    // Ensure we have a database connection
    global $conn;  // Get the connection from paymaster.php

    if (!$conn) {
        throw new Exception("Database connection not available");
    }

    $exporter = new PayrollExporter($conn);

    // Generate and handle report based on settings
    $exporter->generateAndSendReport();

} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => "Database error occurred. Please try again later."
    ]);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => "An error occurred: " . $e->getMessage()
    ]);
}
?>