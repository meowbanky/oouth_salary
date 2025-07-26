<?php
require_once 'Connections/paymaster.php';

header('Content-Type: application/json');

try {
    // Sanitize the search term
    $searchTerm = isset($_GET['term']) ? filter_var($_GET['term'], FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '';
    if (empty($searchTerm)) {
        echo json_encode([]);
        exit;
    }

    // Prepare the SQL query with parameter binding to prevent SQL injection
    $query = "SELECT staff_id, CONCAT(staff_id, ' - ', NAME) AS details, EMAIL, POST 
              FROM employee 
              WHERE staff_id LIKE :searchTerm OR NAME LIKE :searchTerm 
              ORDER BY staff_id ASC LIMIT 20"; // Limit results for performance

    $stmt = $conn->prepare($query);
    $stmt->execute(['searchTerm' => "%$searchTerm%"]);

    $results = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $post = $row['POST'] ? ' - ' . $row['POST'] : '';
        $results[] = [
            'id' => $row['staff_id'],
            'label' => $row['details'] . $post,
            'value' => $row['staff_id'],
            'EMAIL' => $row['EMAIL']
        ];
    }

    echo json_encode($results);
} catch (PDOException $e) {
    error_log("Database Error in searchStaff.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while fetching staff data.']);
} catch (Exception $e) {
    error_log("General Error in searchStaff.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An unexpected error occurred.']);
}
exit;