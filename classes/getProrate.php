<?php
 require_once('../Connections/paymaster.php');
 include_once('../classes/model.php'); 
 //load_data.php  
 session_start();
 //$connect = mysqli_connect("localhost", "root", "Oluwaseyi", "salary");  
 $recordtime = date('Y-m-d H:i:s');
 
 $staffID = $_POST['curremployee'];
 $DaysToCal = $_POST['daysToCal'];
 $no_days = $_POST['no_days'];
 
try{
$query = $conn->prepare('SELECT allow_deduc.`value`,allow_deduc.allow_id,allow_deduc.temp_id,tbl_earning_deduction.edDesc FROM
																						tbl_earning_deduction INNER JOIN allow_deduc ON tbl_earning_deduction.ed_id = allow_deduc.allow_id
																						WHERE transcode = ? and staff_id = ? order by allow_id asc' );
                                           $fin = $query->execute(array('01', $staffID ));;
                                           $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                           //print_r($res);
                                       
                                       
                                           foreach ($res as $row => $link2) {
                                       
                                           
                                       
                                       				$vals = ($DaysToCal * $link2['value'])/$no_days;
                                       				
                                             	$query = 'DELETE FROM prorate_allow_deduc where staff_id = ? and allow_id = ?';
			
																						  $conn->prepare($query)->execute (array($staffID,$link2['allow_id']));
																						  
																						  $insertQry = 'INSERT INTO prorate_allow_deduc (staff_id,allow_id,value,transcode,inserted_by,date_insert,counter) values(?,?,?,?,?,?,1)';
																							$conn->prepare($insertQry)->execute (array($staffID,$link2['allow_id'],$vals,1,$_SESSION['SESS_MEMBER_ID'],$recordtime));

                                    						
                                                 }
                                          			}
                                                catch(PDOException $e){
                                                echo $e->getMessage();
                                                }
                                                
                                                try{
                                                $query = $conn->prepare('SELECT prorate_allow_deduc.`value`,prorate_allow_deduc.allow_id,prorate_allow_deduc.temp_id,tbl_earning_deduction.edDesc FROM
                                                tbl_earning_deduction INNER JOIN prorate_allow_deduc ON tbl_earning_deduction.ed_id = prorate_allow_deduc.allow_id
                                                WHERE transcode = ? and staff_id = ? order by allow_id asc' );
                                              $fin = $query->execute(array('01', $staffID ));
                                              $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                           //print_r($res);
                                       
                                      echo ' <table class="table table-bordered table-hover">';
                                      echo ' <thead> ';
                                   echo ' <tr class="earnings-ded-header"> ';
                                    echo '   <th> Code </th> ';
                                     echo '  <th> Description </th> ';
                                     echo '  <th> Amount </th> ';
                                       
                                  echo '  </tr> ';
                                 echo '</thead> ';
                                 echo '<tbody> ';
                                 
                                           foreach ($res as $row => $link2) {
                                       echo '<tr class="odd gradeX">';
                                         echo '<td>' . $link2['allow_id']; 
                                          			echo '</td><td>' . 	$link2['edDesc'];
                                                echo '</td><td class="align-right">' . number_format($link2['value']) . '</td>';
                                                echo '</td>';  
                                       
                                    						
		                                            echo '  </tr> ';
		                                            
		                                            $query = 'DELETE FROM allow_deduc where staff_id = ? and allow_id = ?';
					
																								$conn->prepare($query)->execute (array($staffID,$link2['allow_id']));
																								
																								
																						
                                                 }
                                          			}
                                                catch(PDOException $e){
                                                echo $e->getMessage();
                                                }
//print number_format($balance);
			$query = $conn->prepare('INSERT INTO allow_deduc (staff_id,allow_id,value,transcode,inserted_by,date_insert,counter) SELECT prorate_allow_deduc.staff_id, prorate_allow_deduc.allow_id,prorate_allow_deduc.`value`, prorate_allow_deduc.transcode,inserted_by,date_insert,counter FROM
																						prorate_allow_deduc WHERE transcode = ? and staff_id = ? order by allow_id asc' );
                                           $fin = $query->execute(array('01', $staffID ));

			$query = $conn->prepare("UPDATE employee SET STEP = CONCAT(STEP,'P') WHERE staff_id = ?" );
      $fin = $query->execute(array($staffID ));
      
     $query = 'DELETE FROM allow_deduc where staff_id = ? and transcode = ?';
					
		 $conn->prepare($query)->execute (array($staffID,2));

?>
