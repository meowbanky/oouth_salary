<?php
session_start();
require_once '../Connections/paymaster.php';
require_once '../classes/model.php';
require_once 'App.php';
require_once 'middleware.php';

$App = new App();
$App->checkAuthentication();
checkPermission();

// Check if user is admin
if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) === '' || ($_SESSION['role'] ?? '') !== 'Admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit;
}

header('Content-Type: application/json');

try {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $description = trim($_POST['description'] ?? '');
            $ed_type = $_POST['ed_type'] ?? '';
            
            if (empty($description)) {
                throw new Exception('Description is required');
            }
            
            if (empty($ed_type) || !in_array($ed_type, ['1', '2', '3', '4'])) {
                throw new Exception('Invalid item type');
            }
            
            // Check if item already exists
            $check_query = $conn->prepare('SELECT ed_id FROM tbl_earning_deduction WHERE edDesc = ? AND status = ?');
            $check_query->execute([$description, 'Active']);
            
            if ($check_query->fetch()) {
                throw new Exception('Item with this description already exists');
            }
            
            // Get next ID
            $next_id_query = $conn->prepare('SELECT MAX(ed_id) + 1 as next_id FROM tbl_earning_deduction');
            $next_id_query->execute();
            $next_id_result = $next_id_query->fetch();
            $next_id = $next_id_result['next_id'] ?? 1;
            
            // Generate code based on type
            $code = '';
            switch ($ed_type) {
                case '1': // Earning
                    $code = '01';
                    break;
                case '2': // Deduction
                    $code = '02';
                    break;
                case '3': // Union Deduction
                    $code = '03';
                    break;
                case '4': // Loan
                    $code = '04';
                    break;
            }
            
            // Insert new item
            $insert_query = $conn->prepare('INSERT INTO tbl_earning_deduction (ed_id, edDesc, ed,edType, code, status) VALUES (?, ?,?, ?, ?, ?)');
            $insert_query->execute([$next_id, $description, $description, $ed_type, $code, 'Active']);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Item created successfully'
            ]);
            break;
            
        case 'update':
            $ed_id = $_POST['ed_id'] ?? '';
            $description = trim($_POST['description'] ?? '');
            
            if (empty($ed_id) || empty($description)) {
                throw new Exception('ID and description are required');
            }
            
            // Check if item exists
            $check_query = $conn->prepare('SELECT ed_id FROM tbl_earning_deduction WHERE ed_id = ? AND status = ?');
            $check_query->execute([$ed_id, 'Active']);
            
            if (!$check_query->fetch()) {
                throw new Exception('Item not found');
            }
            
            // Check if new description already exists (excluding current item)
            $check_name_query = $conn->prepare('SELECT ed_id FROM tbl_earning_deduction WHERE edDesc = ? AND ed_id != ? AND status = ?');
            $check_name_query->execute([$description, $ed_id, 'Active']);
            
            if ($check_name_query->fetch()) {
                throw new Exception('Item with this description already exists');
            }
            
            // Update item
            $update_query = $conn->prepare('UPDATE tbl_earning_deduction SET edDesc = ?, ed = ? WHERE ed_id = ?');
            $update_query->execute([$description, $description, $ed_id]);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Item updated successfully'
            ]);
            break;
            
        case 'delete':
            $ed_id = $_POST['ed_id'] ?? '';
            
            if (empty($ed_id)) {
                throw new Exception('Item ID is required');
            }
            
            // Check if item exists
            $check_query = $conn->prepare('SELECT ed_id FROM tbl_earning_deduction WHERE ed_id = ? AND status = ?');
            $check_query->execute([$ed_id, 'Active']);
            
            if (!$check_query->fetch()) {
                throw new Exception('Item not found');
            }
            
            // Check if item is being used by employees
            $check_usage_query = $conn->prepare('SELECT COUNT(*) as count FROM allow_deduc WHERE allow_id = ?');
            $check_usage_query->execute([$ed_id]);
            $usage_result = $check_usage_query->fetch();
            
            if ($usage_result['count'] > 0) {
                throw new Exception('Cannot delete item: It is assigned to employees');
            }
            
            // Soft delete (set status to inactive)
            $delete_query = $conn->prepare('UPDATE tbl_earning_deduction SET status = ? WHERE ed_id = ?');
            $delete_query->execute(['Inactive', $ed_id]);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Item deleted successfully'
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (PDOException $e) {
    error_log("Database error in manage_earnings_deductions.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>