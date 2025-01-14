<?php
// test_credentials.php
header('Content-Type: application/json');

require_once 'config/Database.php';
require_once 'models/User.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $email = "test@example.com";
    $password = "password@123";

    // Create hashed password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    echo json_encode([
        'success' => true,
        'test_credentials' => [
            'email' => $email,
            'password' => $password,
            'hashed_password' => $hashed_password
        ],
        'message' => 'Use these credentials for testing'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}