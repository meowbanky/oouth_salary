<?php ini_set('max_execution_time','300');
require_once('Connections/paymaster.php'); 
include_once('classes/model.php');

if(!isset($_POST['action'])){
	echo json_encode(array('status' => 'error', 'message' => 'No action specified.'));
	exit;
}

if(!isset($_POST['pfa']) || empty($_POST['pfa'])){
	echo json_encode(array('status' => 'error', 'message' => 'No PFA specified.'));
	exit;
}

if($_POST['pfa'] == 21){
	$_POST['pfapin'] = -1;
}
if(!isset($_POST['pfapin']) || empty($_POST['pfapin'])){
	echo json_encode(array('status' => 'error', 'message' => 'No PFA PIN specified.'));
	exit;
}

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
	echo json_encode(array('status' => 'success', 'message' => 'PFA details updated successfully.'));
	
	
}


?>