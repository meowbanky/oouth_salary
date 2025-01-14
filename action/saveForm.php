<?php
require_once 'Connections/paymaster.php'; // Adjust this path as needed
session_start();

// Ensure there's a session invoice to work with
if (!isset($_SESSION['SESS_INVOICE']) || empty($_SESSION['SESS_INVOICE'])) {
    header("Location: errorPage.php?error=MissingInvoice");
    exit();
}

$invoiceNumber = $_SESSION['SESS_INVOICE'];

$conn = $salary; // Assuming $salary is your database connection from paymaster.php

try {
    // Start transaction for database integrity
    $conn->begin_transaction();

    // Retrieve temporary data for processing
    $stmt = $conn->prepare("SELECT * FROM tbl_workingfile WHERE session_id = ?");
    $stmt->bind_param("s", $invoiceNumber);
    $stmt->execute();
    $tempData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($tempData as $data) {
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM allow_deduc WHERE staff_id = ? AND allow_id = ?");
            $checkStmt->bind_param("si", $data['staff_id'], $data['allow_id']);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $exists = $checkResult->fetch_row()[0] > 0;
    
        // Example: Handling stop allowance or deduction
        if ($data['stop_allow'] == 1) {
            // Delete corresponding record in allow_deduc
            $deleteStmt = $conn->prepare("DELETE FROM allow_deduc WHERE staff_id = ? AND allow_id = ?");
            $deleteStmt->bind_param("si", $data['staff_id'], $data['allow_id']);
            $deleteStmt->execute();
        } else {
            if ($exists) {
            // Update existing record
            $updateStmt = $conn->prepare("UPDATE allow_deduc SET value = ?, transcode = ?, counter = ?, inserted_by = ?, date_insert = NOW() WHERE staff_id = ? AND allow_id = ?");
            $updateStmt->bind_param("isissi", $data['value'], $data['type'], $data['counter'], $data['inserted_by'], $data['staff_id'], $data['allow_id']);
            $updateStmt->execute();
            } else {
            // Insert new record
            $insertStmt = $conn->prepare("INSERT INTO allow_deduc (staff_id, allow_id, value, transcode, counter, inserted_by, date_insert) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $insertStmt->bind_param("siisii", $data['staff_id'], $data['allow_id'], $data['value'], $data['type'], $data['counter'], $data['inserted_by']);
            $insertStmt->execute();
            }

        // Delete processed record from tbl_workingfile
        $deleteTempStmt = $conn->prepare("DELETE FROM tbl_workingfile WHERE temp_id = ?");
        $deleteTempStmt->bind_param("i", $data['temp_id']);
        $deleteTempStmt->execute();
    }

    // Commit transaction
    $conn->commit();

    // Reset session invoice for new operations
    $_SESSION['SESS_INVOICE'] = 'SIV-' . createRandomPassword(); // Assuming createRandomPassword() function exists

    // Redirect to a success page or back to the form with a success message
    header("Location: successPage.php");
    exit();
    }

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();

    // Log error or handle it as per your logging mechanism
    error_log("Error processing form save: " . $e->getMessage());

    // Redirect to an error page or back to the form with an error message
    header("Location: errorPage.php?error=ProcessingError");
    exit();
}
