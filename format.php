<?php 

$connect = mysqli_connect("localhost", "root", "oluwaseyi", "salary"); 
 //include_once('functions.php'); 
 
 $row['STEP'] = '05';
 $row['GRADE'] = '017';
 
 $sql_consolidated = "SELECT allowancetable.`value` FROM allowancetable WHERE allowancetable.allowcode = 1 and 
 grade = '". $row['GRADE'] ."' and step = '". $row['STEP'] ."'";
									$result_consolidated = mysqli_query($connect, $sql_consolidated);
									$row_consolidated = mysqli_fetch_assoc($result_consolidated);
									$total_rowsConsolidated = mysqli_num_rows($result_consolidated);
 
 									
									
									if($total_rowsConsolidated > 0){
										echo $row_consolidated['value'];
									}else{
										echo 'zerio';
										
									}

?>