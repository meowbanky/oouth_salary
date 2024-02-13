<?php  
 //load_data.php  
 $connect = mysqli_connect("localhost", "root", "oluwaseyi", "salary");  
 $output = '';  
 if(isset($_POST["payType_id"]) && isset($_POST["grade_id"])&&isset($_POST["step_id"]) )  
 {  
       if($_POST["payType_id"] ==1){
           
       
      
           $sql = "SELECT conhess.id, conhess.`value` FROM conhess WHERE conhess.id = 1 and grade_level = ".$_POST["grade_id"]." and step = ".$_POST["step_id"]."";  
        }elseif($_POST["payType_id"] ==2){
           
          // $sql = "SELECT tbl_conmessmaxstep.max_step,tbl_conmessmaxstep.grade FROM tbl_conmessmaxstep WHERE grade = '".$_POST["grade_id"]."'"; 
           
       }
       
      $result = mysqli_query($connect, $sql);
      $row = mysqli_fetch_assoc($result);
     
       	$output .='<div class="form-group">';
     
    $output .='<label for="consolidated" class="col-sm-3 col-md-3 col-lg-2 control-label">';
     $output .='Consolidated:</label>';
     $output .='<label for="consolidated" class="col-sm-3 col-md-3 col-lg-2 control-label">';
     $output .='Pls Tick to Give Allowance:</label>';
     $output .='<div class="col-sm-9 col-md-9 col-lg-10">';
     $output .='<input type="checkbox" id="consolidated_id" class="form-inps" name="consolidated_id"'; 
     $output .='value="'.$row['id'].'> ';
     $output .='</div>';
     $output .='<div class="col-sm-9 col-md-9 col-lg-10">';
    $output .='<input type="text" name="consolidated_value" value="'.$row['value'].'"'; 
     $output .='id="consolidated_value" class="form-inps" autocomplete="off">';	
     
     $output .='</div>';
    $output .='</div>';
     
       
      echo $output;  
     
 }  
 ?>  

