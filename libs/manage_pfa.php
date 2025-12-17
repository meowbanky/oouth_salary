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
            $pfa_email = trim($_POST['pfa_email'] ?? '');
            $pfa_website = trim($_POST['pfa_website'] ?? '');
            
            if (empty($pfa_code)) {
                throw new Exception('PFA code is required.');
            }
            if (empty($pfa_name)) {
                throw new Exception('PFA name is required.');
            }
            
            // Validate email if provided
            if (!empty($pfa_email) && !filter_var($pfa_email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address format.');
            }
            
            // Validate website URL if provided
            if (!empty($pfa_website) && !filter_var($pfa_website, FILTER_VALIDATE_URL)) {
                // Try adding http:// if no protocol
                if (!preg_match('#^https?://#i', $pfa_website)) {
                    $pfa_website = 'http://' . $pfa_website;
                }
                if (!filter_var($pfa_website, FILTER_VALIDATE_URL)) {
                    throw new Exception('Invalid website URL format.');
                }
            }
            
            // Check if PFA code already exists
            $checkQuery = $conn->prepare('SELECT COUNT(*) FROM tbl_pfa WHERE PFACODE = ?');
            $checkQuery->execute([$pfa_code]);
            if ($checkQuery->fetchColumn() > 0) {
                throw new Exception('PFA code already exists.');
            }
            
            // Insert new PFA
            $insertQuery = $conn->prepare('INSERT INTO tbl_pfa (PFACODE, PFANAME, EMAIL, WEBSITE) VALUES (?, ?, ?, ?)');
            $result = $insertQuery->execute([$pfa_code, $pfa_name, $pfa_email ?: null, $pfa_website ?: null]);
            
            if ($result) {
                $response = ['status' => 'success', 'message' => 'PFA created successfully.'];
            } else {
                throw new Exception('Error creating PFA.');
            }
            
        } elseif ($action === 'update') {
            $pfacode = trim($_POST['pfacode'] ?? '');
            $pfa_code = trim($_POST['pfa_code'] ?? '');
            $pfa_name = trim($_POST['pfa_name'] ?? '');
            $pfa_email = trim($_POST['pfa_email'] ?? '');
            $pfa_website = trim($_POST['pfa_website'] ?? '');
            
            if (empty($pfacode)) {
                throw new Exception('PFA code is required for update.');
            }
            if (empty($pfa_name)) {
                throw new Exception('PFA name is required.');
            }
            
            // Validate email if provided
            if (!empty($pfa_email) && !filter_var($pfa_email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address format.');
            }
            
            // Validate website URL if provided
            if (!empty($pfa_website) && !filter_var($pfa_website, FILTER_VALIDATE_URL)) {
                // Try adding http:// if no protocol
                if (!preg_match('#^https?://#i', $pfa_website)) {
                    $pfa_website = 'http://' . $pfa_website;
                }
                if (!filter_var($pfa_website, FILTER_VALIDATE_URL)) {
                    throw new Exception('Invalid website URL format.');
                }
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
            $updateQuery = $conn->prepare('UPDATE tbl_pfa SET PFACODE = ?, PFANAME = ?, EMAIL = ?, WEBSITE = ? WHERE PFACODE = ?');
            $result = $updateQuery->execute([$pfa_code, $pfa_name, $pfa_email ?: null, $pfa_website ?: null, $pfacode]);
            
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