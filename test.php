<?php ini_set('max_execution_time','300');
require_once('Connections/paymaster.php'); 
include_once('classes/model.php'); ?>
<?php

	//Start session
	session_start();
	mysqli_select_db($salary,'salary');
	$query = "SELECT tbl_pfa.PFACODE, tbl_pfa.PFANAME FROM tbl_pfa";
	$result = mysqli_query($salary,$query) or (mysqli_error($salary));
		
	
	$id = "'{";
	while ($row = mysqli_fetch_array($result)){
		$id .= "\"". $row['PFACODE']."\":"."\"".$row['PFANAME']."\",";
		//$PFA .= ;
		
	}
	$id = rtrim($id,',');
	$id .= "}'";
	//echo $PFA;
	
	echo $id;
	?>