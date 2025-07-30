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
            $bank_code = trim($_POST['bank_code'] ?? '');
            $bank_name = trim($_POST['bank_name'] ?? '');
            
            if (empty($bank_code)) {
                throw new Exception('Bank code is required.');
            }
            if (empty($bank_name)) {
                throw new Exception('Bank name is required.');
            }
            
            // Check if bank code already exists
            $checkQuery = $conn->prepare('SELECT COUNT(*) FROM tbl_bank WHERE BCODE = ?');
            $checkQuery->execute([$bank_code]);
            if ($checkQuery->fetchColumn() > 0) {
                throw new Exception('Bank code already exists.');
            }
            
            // Insert new bank
            $insertQuery = $conn->prepare('INSERT INTO tbl_bank (BCODE, BNAME) VALUES (?, ?)');
            $result = $insertQuery->execute([$bank_code, $bank_name]);
            
            if ($result) {
                $response = ['status' => 'success', 'message' => 'Bank created successfully.'];
            } else {
                throw new Exception('Error creating bank.');
            }
            
        } elseif ($action === 'update') {
            $bcode = trim($_POST['bcode'] ?? '');
            $bank_code = trim($_POST['bank_code'] ?? '');
            $bank_name = trim($_POST['bank_name'] ?? '');
            
            if (empty($bcode)) {
                throw new Exception('Bank code is required for update.');
            }
            if (empty($bank_name)) {
                throw new Exception('Bank name is required.');
            }
            
            // Check if new bank code already exists (excluding current record)
            if ($bank_code !== $bcode) {
                $checkQuery = $conn->prepare('SELECT COUNT(*) FROM tbl_bank WHERE BCODE = ? AND BCODE != ?');
                $checkQuery->execute([$bank_code, $bcode]);
                if ($checkQuery->fetchColumn() > 0) {
                    throw new Exception('Bank code already exists.');
                }
            }
            
            // Update bank
            $updateQuery = $conn->prepare('UPDATE tbl_bank SET BCODE = ?, BNAME = ? WHERE BCODE = ?');
            $result = $updateQuery->execute([$bank_code, $bank_name, $bcode]);
            
            if ($result) {
                $response = ['status' => 'success', 'message' => 'Bank updated successfully.'];
            } else {
                throw new Exception('Error updating bank.');
            }
            
        } elseif ($action === 'delete') {
            $bcode = trim($_POST['bcode'] ?? '');
            
            if (empty($bcode)) {
                throw new Exception('Bank code is required for deletion.');
            }
            
            // Check if bank is being used in other tables
            // You may want to add checks here for foreign key constraints
            
            // Delete bank
            $deleteQuery = $conn->prepare('DELETE FROM tbl_bank WHERE BCODE = ?');
            $result = $deleteQuery->execute([$bcode]);
            
            if ($result) {
                $response = ['status' => 'success', 'message' => 'Bank deleted successfully.'];
            } else {
                throw new Exception('Error deleting bank.');
            }
        }
        
    } catch (Exception $e) {
        $response = ['status' => 'error', 'message' => $e->getMessage()];
    }
}

echo json_encode($response); 