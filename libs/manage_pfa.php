<?php
require_once 'App.php';
require_once '../Connections/paymaster.php';

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = trim($_POST['action'] ?? '');
        
        if (!in_array($action, ['create', 'update', 'delete'])) {
            throw new Exception('Invalid action specified.');
        }
        
        if ($action === 'create') {
            $pfa_code = trim($_POST['pfa_code'] ?? '');
            $pfa_name = trim($_POST['pfa_name'] ?? '');
            
            if (empty($pfa_code)) {
                throw new Exception('PFA code is required.');
            }
            if (empty($pfa_name)) {
                throw new Exception('PFA name is required.');
            }
            
            // Check if PFA code already exists
            $checkQuery = $conn->prepare('SELECT COUNT(*) FROM tbl_pfa WHERE PFACODE = ?');
            $checkQuery->execute([$pfa_code]);
            if ($checkQuery->fetchColumn() > 0) {
                throw new Exception('PFA code already exists.');
            }
            
            // Insert new PFA
            $insertQuery = $conn->prepare('INSERT INTO tbl_pfa (PFACODE, PFANAME) VALUES (?, ?)');
            $result = $insertQuery->execute([$pfa_code, $pfa_name]);
            
            if ($result) {
                $response = ['status' => 'success', 'message' => 'PFA created successfully.'];
            } else {
                throw new Exception('Error creating PFA.');
            }
            
        } elseif ($action === 'update') {
            $pfacode = trim($_POST['pfacode'] ?? '');
            $pfa_code = trim($_POST['pfa_code'] ?? '');
            $pfa_name = trim($_POST['pfa_name'] ?? '');
            
            if (empty($pfacode)) {
                throw new Exception('PFA code is required for update.');
            }
            if (empty($pfa_name)) {
                throw new Exception('PFA name is required.');
            }
            
            // Check if new PFA code already exists (excluding current record)
            if ($pfa_code !== $pfacode) {
                $checkQuery = $conn->prepare('SELECT COUNT(*) FROM tbl_pfa WHERE PFACODE = ? AND PFACODE != ?');
                $checkQuery->execute([$pfa_code, $pfacode]);
                if ($checkQuery->fetchColumn() > 0) {
                    throw new Exception('PFA code already exists.');
                }
            }
            
            // Update PFA
            $updateQuery = $conn->prepare('UPDATE tbl_pfa SET PFACODE = ?, PFANAME = ? WHERE PFACODE = ?');
            $result = $updateQuery->execute([$pfa_code, $pfa_name, $pfacode]);
            
            if ($result) {
                $response = ['status' => 'success', 'message' => 'PFA updated successfully.'];
            } else {
                throw new Exception('Error updating PFA.');
            }
            
        } elseif ($action === 'delete') {
            $pfacode = trim($_POST['pfacode'] ?? '');
            
            if (empty($pfacode)) {
                throw new Exception('PFA code is required for deletion.');
            }
            
            // Check if PFA is being used in other tables
            // You may want to add checks here for foreign key constraints
            
            // Delete PFA
            $deleteQuery = $conn->prepare('DELETE FROM tbl_pfa WHERE PFACODE = ?');
            $result = $deleteQuery->execute([$pfacode]);
            
            if ($result) {
                $response = ['status' => 'success', 'message' => 'PFA deleted successfully.'];
            } else {
                throw new Exception('Error deleting PFA.');
            }
        }
        
    } catch (Exception $e) {
        $response = ['status' => 'error', 'message' => $e->getMessage()];
    }
}

echo json_encode($response); 