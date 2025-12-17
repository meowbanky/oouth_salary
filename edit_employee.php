<?php
require_once('Connections/paymaster.php');
if (session_status() === PHP_SESSION_NONE) session_start();

// Ensure authentication and permissions if you require (optional)
// Example: Only allow if logged in and has permission
if (!isset($_SESSION['SESS_MEMBER_ID']) || $_SESSION['role'] != 'Admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// Validate and sanitize input
if (!isset($_GET['id']) || !preg_match('/^\d+$/', $_GET['id'])) {
    echo json_encode(['error' => 'Invalid employee ID']);
    exit;
}

$staff_id = $_GET['id'];

try {
    $sql = "SELECT 
                employee.*, 
                tbl_dept.dept, 
                tbl_pfa.PFANAME, 
                tbl_bank.BNAME 
            FROM employee
            LEFT JOIN tbl_dept ON tbl_dept.dept_id = employee.DEPTCD
            LEFT JOIN tbl_pfa ON tbl_pfa.PFACODE = employee.PFACODE
            LEFT JOIN tbl_bank ON tbl_bank.BCODE = employee.BCODE
            WHERE employee.staff_id = :id
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $staff_id, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        echo json_encode(['error' => 'Employee not found']);
        exit;
    }

    // Optionally, hide sensitive fields before returning
    // unset($data['password'], $data['userID'], ...);

    echo json_encode($data);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: '.$e->getMessage()]);
    exit;
}
