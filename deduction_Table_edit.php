<?php ini_set('max_execution_time', '300');
require_once('Connections/paymaster.php');
include_once('classes/model.php');

if ($_POST['action'] == 'edit') {
	$data = array(
		':value' => $_POST['value'],
		':id' => $_POST['id'],
		':percentage' => $_POST['percentage']
	);

	$query = "
	UPDATE deductiontable
	SET value = :value,
	percentage = :percentage
	WHERE allowcode = :id";

	$statement = $conn->prepare($query);
	$statement->execute($data);
	echo json_encode($_POST);
}
