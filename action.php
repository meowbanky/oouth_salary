<?php ini_set('max_execution_time','300');
require_once('Connections/paymaster.php'); 
include_once('classes/model.php');

if ($_POST['action']== 'edit'){
	$data = array(
	':pfa' => $_POST['pfa'],
	':pfapin' => $_POST['pfapin'],
	':id' => $_POST['id']
	);
	
	$query = "
	UPDATE employee
	SET PFACODE = :pfa,
	PFAACCTNO = :pfapin 
	WHERE staff_id = :id
	";
	
	$statement = $conn->prepare($query);
	$statement-> execute($data);
	echo json_encode($_POST);
	
}


?>