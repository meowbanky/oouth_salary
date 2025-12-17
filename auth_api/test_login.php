<?php
// test_login.php
header('Content-Type: application/json');

try {
    require_once 'config/Database.php';
    require_once 'models/User.php';

    // Test database connection
    $database = new Database();
    $conn = $database->getConnection();

    // Check if test user exists
    $email = "test@example.com";
    $query = "SELECT * FROM users WHERE email = :email";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        // Create test user if doesn't exist
        $password = password_hash('password@123', PASSWORD_DEFAULT);
        $query = "INSERT INTO users (name, email, password) VALUES (:name, :email, :password)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':name', 'Test User');
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password);
        $stmt->execute();

        echo json_encode([
            'success' => true,
            'message' => 'Test user created successfully'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Test user already exists'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}