<?php
include_once 'Connections/paymaster.php';

try {

    // Fetch periods from the payperiods table
    $sql = "
        SELECT 
            periodId, 
            CONCAT(description, ' ', periodYear) AS periodText 
        FROM 
            payperiods 
        WHERE 
            enabled = 1 
        ORDER BY 
            periodId DESC, description ASC
    ";

    // Prepare and execute the query
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    // Fetch all results as an associative array
    $periods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return data as JSON
    header('Content-Type: application/json');
    echo json_encode($periods);
} catch (PDOException $e) {
    // Handle database connection or query errors
    die("Database error: " . $e->getMessage());
} finally {
    // Close the connection
    $conn = null;
}
?>