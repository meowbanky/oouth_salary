<?php
require_once('Connection/paymaster.php');
//connect with the database
$return_arr = array();
//get search term
$searchTerm = $_GET['term'];
//get matched data from skills table
$query = $salary->query("SELECT employee.staff_id,concat(employee.staff_id,' - ', employee.NAME) as details FROM employee
WHERE (statuscd = 'A') and (staff_id like '%" . $searchTerm . "%' or NAME like '%" . $searchTerm . "%') ORDER BY staff_id ASC");
while ($row = $query->fetch_assoc()) {
	$data['id'] = $row['staff_id'];
	$data['label'] = $row['details'];
	$data['value'] = $row['staff_id'];
	array_push($return_arr, $data);
}
//return json data
echo json_encode($return_arr);
