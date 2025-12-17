<?php
// setup_test_user.php
header('Content-Type: application/json');

require_once 'config/Database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Test credentials
    $email = 'test@example.com';
    $password = 'password@123';
    $name = 'Test User';

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Check if user exists
    $checkQuery = "SELECT id FROM users WHERE email = :email";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':email', $email);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        // Update existing user
        $updateQuery = "UPDATE users SET password = :password, name = :name WHERE email = :email";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':password', $hashedPassword);
        $updateStmt->bindParam(':name', $name);
        $updateStmt->bindParam(':email', $email);
        $updateStmt->execute();

        echo json_encode([
            'success' => true,
            'message' => 'Test user updated successfully',
            'credentials' => [
                'email' => $email,
                'password' => $password,
                'hashed_password' => $hashedPassword
            ]
        ]);
    } else {
        // Create new user
        $insertQuery = "INSERT INTO users (name, email, password) VALUES (:name, :email, :password)";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->bindParam(':name', $name);
        $insertStmt->bindParam(':email', $email);
        $insertStmt->bindParam(':password', $hashedPassword);
        $insertStmt->execute();

        echo json_encode([
            'success' => true,
            'message' => 'Test user created successfully',
            'credentials' => [
                'email' => $email,
                'password' => $password,
                'hashed_password' => $hashedPassword
            ]
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}