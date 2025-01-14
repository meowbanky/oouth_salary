<?php
require_once 'App.php';

$app = new App;
$return_arr = array();

// Get search term
$searchTerm = '%' . $_GET['term'] . '%';

// Prepare the query with placeholders
$query = $app->link->prepare("SELECT employee.staff_id, 
    CONCAT(employee.staff_id, ' - ', employee.NAME) AS details, 
    employee.EMAIL, IFNULL(employee.PPNO,'') AS OGNO,
    IFNULL(employee.POST, '') AS POST 
    FROM employee
    WHERE staff_id LIKE :searchTerm OR NAME LIKE :searchTerm OR PPNO LIKE :searchTerm
    ORDER BY staff_id ASC");

// Bind the parameter
$query->bindParam(':searchTerm', $searchTerm, PDO::PARAM_STR);

// Execute the query
$query->execute();

// Fetch the results
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $data['id'] = $row['staff_id'];
    $postDetails = $row['POST'] == '' ? '' : ' - ' . $row['POST'];
    $data['label'] = $row['details'] . $postDetails .' - '.$row['OGNO'];
    $data['value'] = $row['staff_id'];
    $data['EMAIL'] = $row['EMAIL'];
    $data['OGNO'] = $row['OGNO'];
    array_push($return_arr, $data);
}

// Return JSON data
echo json_encode($return_arr);
?>
