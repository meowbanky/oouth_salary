<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

require_once '../../config/Database.php';
require_once '../../models/User.php';
require_once '../../utils/JWTHandler.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Get all headers
    $headers = getallheaders();

    // Check if Authorization header exists
    if (!isset($headers['Authorization']) && !isset($headers['authorization'])) {
        throw new Exception('No authorization token provided');
    }

    // Get token from header (handle both uppercase and lowercase header names)
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : $headers['authorization'];
    $token = str_replace('Bearer ', '', $authHeader);

    if (empty($token)) {
        throw new Exception('Invalid token format');
    }

    // Validate token
    $jwtHandler = new JWTHandler();
    $isValid = $jwtHandler->validateToken($token);

    if (!$isValid) {
        throw new Exception('Invalid or expired token');
    }

    // Get user data from token
    $tokenParts = explode('.', $token);
    $payload = json_decode(base64_decode($tokenParts[1]), true);

    // Get user details from database
    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT id, name, email FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $payload['user_id']);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        throw new Exception('User not found');
    }

    $user = $stmt->fetch();

    // Return success response with user data
    echo json_encode([
        'success' => true,
        'message' => 'Token is valid',
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}