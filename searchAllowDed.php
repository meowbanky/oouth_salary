<?php
require_once('Connections/paymaster.php');
//connect with the database
$return_arr = array();

//get search term
$searchTerm = isset($_GET['term']) ? filter_var($_GET['term'], FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '';
if (empty($searchTerm)) {
    echo json_encode([]);
    exit;
}
mysqli_select_db($salary, $database_salary);
$query = $salary->query("SELECT DISTINCT allocode.ADJDESC FROM allowancetable INNER JOIN allocode ON allowancetable.allowcode = allocode.ADJCD WHERE ADJDESC like '%" . $searchTerm . "%'");
while ($row = $query->fetch_assoc()) {
	$data['id'] = $row['ADJDESC'];
	$data['label'] = $row['ADJDESC'];
	$data['value'] = $row['ADJDESC'];
	array_push($return_arr, $data);
}
//return json data
echo json_encode($return_arr);