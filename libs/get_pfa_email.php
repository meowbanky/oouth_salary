<?php
require_once '../Connections/paymaster.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pfa_code = trim($_POST['pfa_code'] ?? '');
        
        if (empty($pfa_code)) {
            echo json_encode(['status' => 'error', 'message' => 'PFA code is required']);
            exit;
        }
        
        $query = $conn->prepare('SELECT EMAIL, PFANAME FROM tbl_pfa WHERE PFACODE = ?');
        $query->execute([$pfa_code]);
        $pfa = $query->fetch(PDO::FETCH_ASSOC);
        
        if ($pfa) {
            echo json_encode([
                'status' => 'success',
                'email' => $pfa['EMAIL'] ?? '',
                'pfa_name' => $pfa['PFANAME'] ?? ''
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'PFA not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>