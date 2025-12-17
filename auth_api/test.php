<?php
// test.php
header('Content-Type: application/json');

require_once 'config/Database.php';
require_once 'models/User.php';

try {
    // Test database connection
    $database = new Database();
    $db = $database->getConnection();

    // Test query
    $query = "SELECT COUNT(*) as count FROM users";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Database connection successful',
        'users_count' => $row['count']
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>