<?php ini_set('max_execution_time', '300');
require_once('Connections/paymaster.php');
include_once('classes/model.php');
include_once('classes/create_email.php');

if (($_POST['action'] == 'edit') & ($_POST['value'] != '')) {
	$email = $_POST['value'];
	$data = array(
		':value' => $_POST['value'],
		':id' => $_POST['id']
	);

	$query = "
	UPDATE employee
	SET EMAIL = :value
	WHERE staff_id = :id";

	$statement = $conn->prepare($query);
	$statement->execute($data);
	echo json_encode($_POST);

	createEmail($email);
}
