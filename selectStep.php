<?php  
 //load_data.php  
 $connect = mysqli_connect("localhost", "root", "oluwaseyi", "salary");  
 $output = '';  
 if(isset($_POST["payType_id"]) && isset($_POST["grade_id"]))  
 {  
       if($_POST["payType_id"] ==1){
           
       
      
           $sql = "SELECT tbl_conhessmaxstep.max_step,tbl_conhessmaxstep.grade FROM tbl_conhessmaxstep WHERE grade = '".$_POST["grade_id"]."'";  
        }elseif($_POST["payType_id"] ==2){
           
           $sql = "SELECT tbl_conmessmaxstep.max_step,tbl_conmessmaxstep.grade FROM tbl_conmessmaxstep WHERE grade = '".$_POST["grade_id"]."'"; 
           
       }
       
      $result = mysqli_query($connect, $sql);
       $row = mysqli_fetch_assoc($result);
        $output .= '<select class="form-inps" id="gradestep" name="gradestep" required="required">';
            $output.= '<option value = "">Select Step</option>';
     $i=1;
      while($i <= $row['max_step']) 
         
      {    
           $output .= "<option value='". $i."'>".$i."</option>";
           $i=$i+1;
      }  
     $output .= '</select>';
      echo $output;  
 }  
 ?>  

