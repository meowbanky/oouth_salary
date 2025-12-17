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
            $dept_name = trim($_POST['dept_name'] ?? '');
            
            if (empty($dept_name)) {
                throw new Exception('Department name is required');
            }
            
            // Check if department already exists
            $check_query = $conn->prepare('SELECT dept_id FROM tbl_dept WHERE dept = ?');
            $check_query->execute([$dept_name]);
            
            if ($check_query->fetch()) {
                throw new Exception('Department already exists');
            }
            
            // Get next department ID
            $next_id_query = $conn->prepare('SELECT MAX(dept_id) + 1 as next_id FROM tbl_dept');
            $next_id_query->execute();
            $next_id_result = $next_id_query->fetch();
            $next_id = $next_id_result['next_id'] ?? 1;
            
            // Insert new department
            $insert_query = $conn->prepare('INSERT INTO tbl_dept (dept_id, dept) VALUES (?, ?)');
            $insert_query->execute([$next_id, $dept_name]);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Department created successfully'
            ]);
            break;
            
        case 'update':
            $dept_id = $_POST['dept_id'] ?? '';
            $dept_name = trim($_POST['dept_name'] ?? '');
            
            if (empty($dept_id) || empty($dept_name)) {
                throw new Exception('Department ID and name are required');
            }
            
            // Check if department exists
            $check_query = $conn->prepare('SELECT dept_id FROM tbl_dept WHERE dept_id = ?');
            $check_query->execute([$dept_id]);
            
            if (!$check_query->fetch()) {
                throw new Exception('Department not found');
            }
            
            // Check if new name already exists (excluding current department)
            $check_name_query = $conn->prepare('SELECT dept_id FROM tbl_dept WHERE dept = ? AND dept_id != ?');
            $check_name_query->execute([$dept_name, $dept_id]);
            
            if ($check_name_query->fetch()) {
                throw new Exception('Department name already exists');
            }
            
            // Update department
            $update_query = $conn->prepare('UPDATE tbl_dept SET dept = ? WHERE dept_id = ?');
            $update_query->execute([$dept_name, $dept_id]);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Department updated successfully'
            ]);
            break;
            
        case 'delete':
            $dept_id = $_POST['dept_id'] ?? '';
            
            if (empty($dept_id)) {
                throw new Exception('Department ID is required');
            }
            
            // Check if department exists
            $check_query = $conn->prepare('SELECT dept_id FROM tbl_dept WHERE dept_id = ?');
            $check_query->execute([$dept_id]);
            
            if (!$check_query->fetch()) {
                throw new Exception('Department not found');
            }
            
            // Check if department is being used by employees
            $check_usage_query = $conn->prepare('SELECT COUNT(*) as count FROM employee WHERE dept_id = ?');
            $check_usage_query->execute([$dept_id]);
            $usage_result = $check_usage_query->fetch();
            
            if ($usage_result['count'] > 0) {
                throw new Exception('Cannot delete department: It is assigned to employees');
            }
            
            // Delete department
            $delete_query = $conn->prepare('DELETE FROM tbl_dept WHERE dept_id = ?');
            $delete_query->execute([$dept_id]);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Department deleted successfully'
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (PDOException $e) {
    error_log("Database error in manage_department.php: " . $e->getMessage());
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