<?php
ini_set('max_execution_time', '300');
require_once('Connections/paymaster.php');
include_once('classes/model.php');

// Start session
session_start();

// Check whether the session variable SESS_MEMBER_ID is present or not
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Set content type to JSON
header('Content-Type: application/json');

 try {
     // Debug: Log the received data
     error_log('PFA Action received POST data: ' . print_r($_POST, true));
     
     // Check if this is a POST request
     if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
         throw new Exception('Invalid request method');
     }

    // Get the action from POST data
    $action = $_POST['action'] ?? '';
    
         if ($action === 'edit') {
         // Handle edit action
         $staff_id = $_POST['staff_id'] ?? '';
         $pfapin = $_POST['pfapin'] ?? '';
         
                  // Debug: Log the values
         error_log('Processing staff_id: "' . $staff_id . '"');
         error_log('Processing pfapin: "' . $pfapin . '"');
         
         // Clean staff_id if it contains corrupted data
         $original_staff_id = $staff_id;
         
         // Remove URL encoding
         $staff_id = urldecode($staff_id);
         
         // Remove carriage returns and line feeds
         $staff_id = str_replace(["\r", "\n", "\r\n"], '', $staff_id);
         
         // Remove the + pattern
         $staff_id = preg_replace('/\++/', '', $staff_id);
         
         // Remove any non-numeric characters (assuming staff_id should be numeric)
         $staff_id = preg_replace('/[^0-9]/', '', $staff_id);
         
         error_log('Original staff_id: "' . $original_staff_id . '"');
         error_log('Cleaned staff_id to: "' . $staff_id . '"');
         
         if (empty($staff_id)) {
             throw new Exception('Staff ID is required');
         }
        
        // Validate PFA PIN (allow alphanumeric and some special characters)
        if (!empty($pfapin) && !preg_match('/^[a-zA-Z0-9\-_\.]+$/', $pfapin)) {
            throw new Exception('PFA PIN contains invalid characters');
        }
        
        // Update the PFA PIN in the database
        $sql = "UPDATE employee SET PFAACCTNO = ? WHERE staff_id = ? AND STATUSCD = 'A'";
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([$pfapin, $staff_id]);
        
        if ($result) {
            // Check if any rows were affected
            if ($stmt->rowCount() > 0) {
                                 echo json_encode([
                     'action' => 'edit',
                     'staff_id' => $staff_id,
                     'success' => true,
                     'message' => 'PFA PIN updated successfully'
                 ]);
            } else {
                throw new Exception('No employee found with the specified Staff ID');
            }
        } else {
            throw new Exception('Failed to update PFA PIN');
        }
        
    } else {
        throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    // Log the error
    error_log('PFA Action Error: ' . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
?>