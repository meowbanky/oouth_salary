<?php
//load_data.php  
require_once('../Connections/paymaster.php');
mysqli_select_db($salary, $database_salary); 
 $output = ''; 
 
if(isset($_POST['grade_level']))
 { 
  			  $sql_consolidated = "SELECT allow_deduc.staff_id, allow_deduc.allow_id,sum(allow_deduc.`value`) as tax
															FROM allow_deduc INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = allow_deduc.allow_id
															INNER JOIN employee ON employee.staff_id = allow_deduc.staff_id
															WHERE taxable = 1 AND transcode = 1 and allow_deduc.staff_id = '" .$_POST['curremployee']."' and DEPTCD = '40'";
      		$result_consolidated = mysqli_query($salary, $sql_consolidated);
		      $row_consolidated = mysqli_fetch_assoc($result_consolidated);
		      $total_rowsConsolidated = mysqli_num_rows($result_consolidated);
		      
		      
		      
		      $output = number_format($row_consolidated['tax']*0.05,0,'','');
		      
		      echo $output;

			
 	
 }
