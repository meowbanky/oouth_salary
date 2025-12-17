<?php
// auth/login.php

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
    // Verify request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed', 405);
    }

    // Get and validate input
    $input = file_get_contents('php://input');
    error_log("Received input: " . $input);

    $data = json_decode($input);
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }

    if (!isset($data->email) || !isset($data->password)) {
        throw new Exception('Email and password are required');
    }

    // Include dependencies
    require_once __DIR__ . '/../../config/Database.php';
    require_once __DIR__ . '/../../models/User.php';
    require_once __DIR__ . '/../../utils/JWTHandler.php';

    // Initialize services
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);

    // Attempt login
    $result = $user->login($data->email, $data->password);

    if ($result['success']) {
        $jwt = new JWTHandler();
        $token = $jwt->generateToken($result['user']['id']);

        if (isset($data->device_id)) {
            $user->saveDeviceId($result['user']['id'], $data->device_id);
            error_log('Saving...'.$result['user']['id'].'-'.$data->device_id);
        }

        $response = [
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'user' => $result['user']
        ];
        http_response_code(200);
    } else {
        $response = [
            'success' => false,
            'message' => 'Invalid credentials'
        ];
        http_response_code(401);
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    http_response_code($e->getCode() ?: 400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}