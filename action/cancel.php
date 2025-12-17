<?php
require_once('../Connections/paymaster.php');

session_start();


// Check if there's an active session invoice number
if (isset($_SESSION['SESS_INVOICE']) && $_SESSION['SESS_INVOICE'] !== '') {
    $invoiceNumber = $_SESSION['SESS_INVOICE'];

    try {
        // Prepare the SQL statement to delete records associated with the session invoice
        $stmt = $conn->prepare("DELETE FROM tbl_workingfile WHERE session_id = :invoiceNumber");
        $stmt->execute([':invoiceNumber' => $invoiceNumber]);

        // Reset the session invoice to a new value or clear it
        $_SESSION['SESS_INVOICE'] = 'SIV-' . createRandomPassword(); // Assuming createRandomPassword() is available and generates a new string

        // Redirect or perform other logic after successful cancellation
        // For example, redirect back to a main page or display a success message
        header("Location: multiAdjustment.php"); // Adjust the redirect location as necessary
        exit();

    } catch (PDOException $e) {
        // Handle any errors, such as by logging them and showing an error message
        error_log("Database error during cancellation: " . $e->getMessage());
        echo "An error occurred. Please try again or contact support.";
        exit();
    }
} else {
    // Handle cases where there is no active session invoice or it's already cleared
    echo "No active session to cancel or it has already been processed.";
    // Optionally, redirect the user to a different page
}
