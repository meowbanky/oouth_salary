<?php
require_once('../Connections/hms.php');
if(isset($_POST["Import"])){
 
 
		echo $filename=$_FILES["file"]["tmp_name"];
 
 
			 
 
		 if($_FILES["file"]["size"] > 0)
		 {
				// $emptySql = "delete from tbl_contributions";
				  //mysql_select_db($database_hms, $hms);
				  //$result2 = mysql_query( $emptySql, $hms ) or die(mysql_error());
				  
				  
		  	$file = fopen($filename, "r");
	         while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE)
	         {
 				$emapData[0] = str_replace(',','',$emapData[0]);
				//$emapData[1] = str_replace(',','',$emapData[1]);
				$emapData[3] = str_replace(',','',$emapData[3]);
				//$emapData[4] = str_replace(',','',$emapData[4]);
				$emapData[5] = str_replace(',','',$emapData[5]);
				//$emapData[6] = str_replace(',','',$emapData[6]);
				//$emapData[7] = str_replace(',','',$emapData[7]);

								
	          //It wiil insert a row to our subject table from our csv file`
	           $sql = "UPDATE tbl_personalinfo SET MobilePhone = '".$emapData[4]."' WHERE Id_no = '".$emapData[1]."'";
	         //we are using mysql_query function. it returns a resource on true else False on error
	          mysql_select_db($database_hms, $hms);
			  $result = mysql_query( $sql, $hms ) or die(mysql_error());
				if(!$result )
				{
					echo "<script type=\"text/javascript\">
							alert(\"Invalid File:Please Upload CSV File.\");
							window.location = \"index.php\"
						</script>";
 
				}
 
	         }
	         fclose($file);
	         //throws a message if data successfully imported to mysql database from excel file
	         echo "<script type=\"text/javascript\">
						alert(\"CSV File has been successfully Imported.\");
						window.location = \"index_number.php\"
					</script>";
 
 
 
			 //close of connection
			mysql_close($hms); 
 
 
 
		 }
	}	 
?>		