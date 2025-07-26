<?php
require_once('Connections/paymaster.php');
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No ID']);
    exit;
}
$id = $_GET['id'];
$stmt = $conn->prepare('SELECT employee.*, tbl_dept.dept, tbl_pfa.PFANAME, tbl_bank.BNAME
    FROM employee 
    LEFT JOIN tbl_pfa ON tbl_pfa.PFACODE = employee.PFACODE 
    LEFT JOIN tbl_bank ON tbl_bank.BCODE = employee.BCODE 
    LEFT JOIN tbl_dept ON tbl_dept.dept_id = employee.DEPTCD
    WHERE staff_id = ?');
$stmt->execute([$id]);
$emp = $stmt->fetch(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
if ($emp) echo json_encode($emp);
else echo json_encode(['error' => 'Not found']);
