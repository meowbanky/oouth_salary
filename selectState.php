<?php  
 //load_data.php  
 $connect = mysqli_connect("localhost", "root", "oluwaseyi", "salary");  
 $output = '';  
 if(isset($_POST["state_id"]))  
 {  
       
      
           $sql = "SELECT `lg-state`.lg, `lg-state`.lg_id FROM `lg-state` where state_id = '".$_POST["state_id"]."'";  
        
       
      $result = mysqli_query($connect, $sql);
            $output .= '<select class="form-inps" id="lg" required="required">';
            $output.= '<option value = "">Select Local Govt</option>';
      while($row = mysqli_fetch_array($result)) 
          
      {    
           $output .= "<option value='". $row['lg']."'>".$row['lg']."</option>";;  
      }  
     $output .= '</select>';
      echo $output;  
 }  
 ?>  

