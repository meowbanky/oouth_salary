<?php
// auth/notifications.php

// Clear any existing output and enable error reporting
if (ob_get_level()) ob_end_clean();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Include CORS handler
require_once __DIR__ . '/../../config/CorsHandler.php';
CorsHandler::handleCors();

// Set JSON content type
header('Content-Type: application/json; charset=UTF-8');

try {
    // Include dependencies
    require_once __DIR__ . '/../../config/Database.php';
    require_once __DIR__ . '/../../models/User.php';
    require_once __DIR__ . '/../../utils/JWTHandler.php';

    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();

    // Get the authorization header
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        throw new Exception('Authorization token required', 401);
    }

    $token = $matches[1];
    $jwt = new JWTHandler();
    $decodedToken = $jwt->validateToken($token);

    if (!$decodedToken) {
        throw new Exception('Invalid token', 401);
    }

    // Handle different request methods
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_GET['unread-count'])&& isset($_GET['count'])) {
                getUnreadCount($db);
            } else {
                getNotifications($db);
            }
            break;

        case 'PUT':
            if (preg_match('/\/notifications\.php\/(\d+)\/read$/', $_SERVER['REQUEST_URI'], $matches)) {
                markAsRead($db, $matches[1]); // Pass the extracted notification ID
            } else {
                throw new Exception('Invalid endpoint', 404);
            }

            break;

        default:
            throw new Exception('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log("Notification error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    http_response_code($e->getCode() ?: 400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function getNotifications($db) {
    if (!isset($_GET['staff_id'])) {
        throw new Exception('Staff ID is required');
    }
    error_log('Notification getine');
    $staff_id = intval($_GET['staff_id']);



    try {
        $query = "SELECT id, staff_id, title, message, status, created_at, updated_at 
              FROM notifications 
              WHERE staff_id = :staff_id 
              ORDER BY created_at DESC";

        $stmt = $db->prepare($query);

        // Replace this with the actual staff_id value you're querying for
//        $staff_id = 123; // Example value
        $stmt->bindParam(':staff_id', $staff_id, PDO::PARAM_INT);

        // Execute the statement
        $stmt->execute();

        $notifications = [];

        // Fetch results if there are rows
        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $notifications[] = $row;
            }

            // Respond with the notifications
            echo json_encode([
                'success' => true,
                'data' => $notifications
            ]);
        } else {
            // No notifications found
            echo json_encode([
                'success' => false,
                'message' => 'No notifications found for the given staff_id'
            ]);
        }
    } catch (Exception $e) {
        // Handle exceptions and errors
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }

}

function getUnreadCount($db) {
    try {
        if (!isset($_GET['staff_id'])) {
            throw new Exception('Staff ID is required');
        }

        // Safely retrieve and validate the staff_id
        $staff_id = intval($_GET['staff_id']);

        // Query to count unread notifications
        $query = "SELECT COUNT(*) as count 
                  FROM notifications 
                  WHERE staff_id = :staff_id AND status != 'read'";

        $stmt = $db->prepare($query);

        // Bind the staff_id parameter
        $stmt->bindParam(':staff_id', $staff_id, PDO::PARAM_INT);

        // Execute the statement
        $stmt->execute();

        // Fetch the result
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Return the count in a JSON response
        echo json_encode([
            'success' => true,
            'count' => intval($result['count'])
        ]);
    } catch (Exception $e) {
        // Handle errors and return JSON response
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}


function markAsRead($db, $notification_id) {
    try {
        // Decode JSON input from the request body
        $input = json_decode(file_get_contents('php://input'));

        // Validate input
//        if (!isset($input->staff_id)) {
//            throw new Exception('Staff ID is required');
//        }

        // Safely retrieve and validate the inputs
//        $staff_id = intval($input->staff_id);
        $notification_id = intval($notification_id);

        // Query to update notification status
        $query = "UPDATE notifications 
                  SET status = 'read', updated_at = CURRENT_TIMESTAMP 
                  WHERE id = :notification_id";

        $stmt = $db->prepare($query);

        // Bind parameters to prevent SQL injection
        $stmt->bindParam(':notification_id', $notification_id, PDO::PARAM_INT);

        // Execute the statement
        $stmt->execute();

        // Check if the update affected any rows
        if ($stmt->rowCount() > 0) {
            // Successful update
            echo json_encode([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);
        } else {
            // Notification not found or unauthorized
            throw new Exception('Notification not found or unauthorized');
        }
    } catch (Exception $e) {
        // Handle errors and return JSON response
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
