<?php
session_start();
include_once('../classes/model.php'); 
require_once('Connections/paymaster.php');
								mysqli_select_db($salary,$database_salary);
								$query_period = 'SELECT payperiods.description, payperiods.periodYear, payperiods.periodId FROM payperiods WHERE payrollRun = "1"';
								$period = mysqli_query($salary,$query_period) or (mysqli_error($salary));
								$row_period = mysqli_fetch_assoc($period);
								$totalRows_period = mysqli_num_rows($period);
									
									do {  
									?>
											<option value="<?php echo $row_period['periodId']?>"><?php echo $row_period['periodId']?></option>
										<?php
									} while ($row_period = mysqli_fetch_assoc($period));
									$rows = mysqli_num_rows($period);
									if($rows > 0) {
								    mysqli_data_seek($period, 0);
									$row_period = mysqli_fetch_assoc($period);
									}
							?>