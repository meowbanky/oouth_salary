<?php
require_once('Connections/paymaster.php');
//connect with the database
$return_arr = array();

//get search term
$searchTerm = $_GET['term'];
mysqli_select_db($salary, $database_salary);
$query = $salary->query("SELECT employee.staff_id,concat(employee.staff_id,' - ', employee.NAME) as details,employee.EMAIL , IFNULL(employee.POST,'') AS POST FROM employee
WHERE  (staff_id like '%" . $searchTerm . "%' or NAME like '%" . $searchTerm . "%') ORDER BY staff_id ASC");
while ($row = $query->fetch_assoc()) {
	$data['id'] = $row['staff_id'];
	if ($row['POST'] == '') {
		$row['POST'] = '';
	} else {
		$row['POST'] = ' - ' . $row['POST'];
	}
	$data['label'] = $row['details'] . $row['POST'];
	$data['value'] = $row['staff_id'];
	$data['EMAIL'] = $row['EMAIL'];
	array_push($return_arr, $data);
}
//return json data
echo json_encode($return_arr);
