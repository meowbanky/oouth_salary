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
            $newearningcode = trim($_POST['newearningcode'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $cc_email = trim($_POST['cc_email'] ?? '');
            
            if (empty($newearningcode)) {
                throw new Exception('Please select an earning/deduction.');
            }
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Please enter a valid email address.');
            }
            if (empty($cc_email) || !filter_var($cc_email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Please enter a valid CC email address.');
            }
            
            // Check if email deduction already exists for this earning/deduction
            $checkQuery = $conn->prepare('SELECT COUNT(*) FROM email_deductionlist WHERE allow_id = ?');
            $checkQuery->execute([$newearningcode]);
            if ($checkQuery->fetchColumn() > 0) {
                throw new Exception('Email deduction already exists for this earning/deduction.');
            }
            
            // Insert new email deduction
            $insertQuery = $conn->prepare('INSERT INTO email_deductionlist (allow_id, email, bcc) VALUES (?, ?, ?)');
            $result = $insertQuery->execute([$newearningcode, $email, $cc_email]);
            
            if ($result) {
                $response = ['status' => 'success', 'message' => 'Email deduction created successfully.'];
            } else {
                throw new Exception('Error creating email deduction.');
            }
            
        } elseif ($action === 'update') {
            $allow_id = trim($_POST['allow_id'] ?? '');
            $newearningcode = trim($_POST['newearningcode'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $cc_email = trim($_POST['cc_email'] ?? '');
            
            if (empty($allow_id)) {
                throw new Exception('Allow ID is required for update.');
            }
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Please enter a valid email address.');
            }
            if (empty($cc_email) || !filter_var($cc_email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Please enter a valid CC email address.');
            }
            
            // Check if new earning/deduction already has an email deduction (excluding current record)
            if ($newearningcode !== $allow_id) {
                $checkQuery = $conn->prepare('SELECT COUNT(*) FROM email_deductionlist WHERE allow_id = ? AND allow_id != ?');
                $checkQuery->execute([$newearningcode, $allow_id]);
                if ($checkQuery->fetchColumn() > 0) {
                    throw new Exception('Email deduction already exists for this earning/deduction.');
                }
            }
            
            // Update email deduction
            $updateQuery = $conn->prepare('UPDATE email_deductionlist SET allow_id = ?, email = ?, bcc = ? WHERE allow_id = ?');
            $result = $updateQuery->execute([$newearningcode, $email, $cc_email, $allow_id]);
            
            if ($result) {
                $response = ['status' => 'success', 'message' => 'Email deduction updated successfully.'];
            } else {
                throw new Exception('Error updating email deduction.');
            }
            
        } elseif ($action === 'delete') {
            $allow_id = trim($_POST['allow_id'] ?? '');
            
            if (empty($allow_id)) {
                throw new Exception('Allow ID is required for deletion.');
            }
            
            // Delete email deduction
            $deleteQuery = $conn->prepare('DELETE FROM email_deductionlist WHERE allow_id = ?');
            $result = $deleteQuery->execute([$allow_id]);
            
            if ($result) {
                $response = ['status' => 'success', 'message' => 'Email deduction deleted successfully.'];
            } else {
                throw new Exception('Error deleting email deduction.');
            }
        }
        
    } catch (Exception $e) {
        $response = ['status' => 'error', 'message' => $e->getMessage()];
    }
}

echo json_encode($response); 