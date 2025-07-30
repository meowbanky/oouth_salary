<?php
session_start();
require_once '../Connections/paymaster.php';
require_once '../classes/model.php';
require_once 'App.php';
require_once 'middleware.php';

$App = new App();
$App->checkAuthentication();
checkPermission();

// Check if user is admin
if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) === '' || ($_SESSION['role'] ?? '') !== 'Admin') {
    header("Location: ../index.php");
    exit;
}

try {
    // Get departments data
    $query = $conn->prepare('SELECT dept_id, dept FROM tbl_dept ORDER BY dept_id ASC');
    $query->execute();
    $departments = $query->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers for Excel download
    $filename = 'departments_' . date('Y-m-d_H-i-s') . '.xls';
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // Create Excel content
    echo '<table border="1">';
    echo '<tr style="background-color: #4F81BD; color: white; font-weight: bold;">';
    echo '<th>ID</th>';
    echo '<th>Department Name</th>';
    echo '</tr>';
    
    foreach ($departments as $dept) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($dept['dept_id']) . '</td>';
        echo '<td>' . htmlspecialchars($dept['dept']) . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    
} catch (PDOException $e) {
    error_log("Database error in export_departments_excel.php: " . $e->getMessage());
    echo "Error exporting data: " . $e->getMessage();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>