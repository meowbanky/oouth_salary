// test_token.php
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/Database.php';
require_once '../utils/JWTHandler.php';

try {
    // Get token from header
    $headers = apache_request_headers();
    $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

    if (!$auth_header || !preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        throw new Exception('No token provided');
    }

    $token = $matches[1];

    // Log the received token
    error_log("Received token: " . $token);

    $jwt = new JWTHandler();
    if (!$jwt->validateToken($token)) {
        throw new Exception('Invalid token');
    }

    // Get payload for debugging
    $payload = $jwt->getPayload($token);
    error_log("Token payload: " . json_encode($payload));

    echo json_encode([
        'success' => true,
        'message' => 'Token is valid',
        'payload' => $payload
    ]);

} catch (Exception $e) {
    error_log("Token error: " . $e->getMessage());
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}