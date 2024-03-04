<?php
require_once 'Connections/paymaster.php'; // Adjust this path as needed
session_start();

// Check for necessary POST data
if (isset($_POST['amount'], $_POST['temp_id']) && is_numeric($_POST['amount'])) {
    $amount = $_POST['amount'];
    $tempId = $_POST['temp_id'];

    // Connect to the database
    $conn = $salary; // Assuming $salary is your PDO or mysqli connection from 'Connections/paymaster.php'

    // Use prepared statements to prevent SQL injection
    if ($conn instanceof mysqli) {
        // Prepare the SQL statement
        $stmt = $conn->prepare("UPDATE tbl_workingfile SET `value` = ? WHERE temp_id = ?");
        $stmt->bind_param("di", $amount, $tempId);

        // Execute the query
        if ($stmt->execute()) {
            // Check if any row was updated
            if ($stmt->affected_rows > 0) {
                echo "Amount updated successfully.";
            } else {
                echo "No changes were made. Please check your inputs.";
            }
        } else {
            // Handle errors during execution
            echo "Error updating record: " . $conn->error;
        }

        $stmt->close();
    } else {
        echo "Database connection error.";
    }
} else {
    // Handle invalid input
    echo "Invalid input or missing data.";
}

// Redirect back or display a message
// header("Location: somepage.php?message=AmountUpdated");
// exit();
