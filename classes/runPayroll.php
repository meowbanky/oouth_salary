<?php
ini_set('max_execution_time', '0');
//$connect = mysqli_connect("localhost", "root", "Oluwaseyi", "salary");
require_once('../Connections/paymaster.php');
mysqli_select_db($salary, $database_salary);
include_once('functions.php');
include_once('fn_runUpdateGrade.php');
// session_start();

$j = 0;
$percent;

// echo '<div id="progress" style="width:500px;border:1px solid #ccc;"></div> ';
// echo '<div id="information" style="width" ><p align="center"></p> </div> ';
?>

<div id="progress" style="border:1px solid #ccc; border-radius: 5px;"></div>
<div id="information" style="width:100%"></div>

<?php

$period = $_SESSION['currentactiveperiod'];

deletecurrentperiod($period);
// $query = $conn->prepare('SELECT * FROM payperiods WHERE payrollRun = ? and periodId = ?');
// $fin = $query->execute(array(0, $_SESSION['currentactiveperiod']));
// $existtrans = $query->fetch();

// if ($existtrans) {


mysqli_select_db($salary, $database_salary);
$query_masterTransaction = 'SELECT * FROM employee WHERE STATUSCD = "A"';
$masterTransaction = mysqli_query($salary, $query_masterTransaction) or die(mysqli_error($salary));
$row_masterTransaction = mysqli_fetch_assoc($masterTransaction);
$totalRows_masterTransaction = mysqli_num_rows($masterTransaction);
$total = $totalRows_masterTransaction;

try { //echo $period ;

	global $conn;
	$query = $conn->prepare('SELECT * FROM employee WHERE STATUSCD = ?');
	$res = $query->execute(array('A'));
	$out = $query->fetchAll(PDO::FETCH_ASSOC);
	//get employee info                                          
	while ($row = array_shift($out)) {
		//$percent = '';

		//ob_end_clean();
		$percent = intval($j / $total * 100) . "%";
		$queryMaster = $conn->prepare('INSERT INTO master_staff (staff_id,NAME,DEPTCD,BCODE,ACCTNO,GRADE,STEP,period,PFACODE,PFAACCTNO) VALUES (?,?,?,?,?,?,?,?,?,?)');
		$master = $queryMaster->execute(array($row['staff_id'], $row['NAME'], $row['DEPTCD'], $row['BCODE'], $row['ACCTNO'], $row['GRADE'], $row['STEP'], $period, $row['PFACODE'], $row['PFAACCTNO']));

		//echo 'staff id'.' '.$row['staff_id'].'<br>';
		$query_allow = $conn->prepare('SELECT allow_deduc.temp_id, allow_deduc.staff_id, allow_deduc.allow_id, allow_deduc.`value`, allow_deduc.transcode, allow_deduc.counter,  allow_deduc.running_counter, allow_deduc.inserted_by, allow_deduc.date_insert,tbl_earning_deduction.edDesc FROM allow_deduc
 																				INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = allow_deduc.allow_id WHERE staff_id = ? and transcode = ? order by allow_deduc.allow_id asc');
		$res_allow = $query_allow->execute(array($row['staff_id'], '1'));
		$out_allow = $query_allow->fetchAll(PDO::FETCH_ASSOC);
		while ($row_allow = array_shift($out_allow)) {


			if ($row_allow['allow_id'] == '21') {

				$query_value = $conn->prepare('SELECT allowancetable.`value` FROM allowancetable WHERE allowancetable.grade = ? AND allowancetable.step = ? AND allowcode = ? AND category = ?');
				$rerun_value = $query_value->execute(array($row['GRADE'], $row['STEP'], $row_allow['allow_id'], $row['CALLTYPE']));
			} elseif ($row_allow['allow_id'] == '5') {

				$query_value = $conn->prepare('SELECT allowancetable.`value` FROM allowancetable WHERE allowancetable.grade = ? AND allowancetable.step = ? AND allowcode = ? AND category = ?');
				$rerun_value = $query_value->execute(array($row['GRADE'], $row['STEP'], $row_allow['allow_id'], $row['HARZAD_TYPE']));
			} else {

				$query_value = $conn->prepare('SELECT allowancetable.`value` FROM allowancetable WHERE allowancetable.grade = ? AND allowancetable.step = ? AND allowcode = ?');
				$rerun_value = $query_value->execute(array($row['GRADE'], $row['STEP'], $row_allow['allow_id']));
			}


			if ($row_value = $query_value->fetch()) {
				$output = $row_value['value'];
			} else {

				$output = number_format($row_allow['value'], 0, '.', '');
			}


			// echo $row_allow['allow_id'].' '.$row_allow['edDesc'].' '.number_format($output).'<br>';
			try {
				$recordtime = date('Y-m-d H:i:s');
				$query = 'INSERT INTO tbl_master (staff_id, allow_id, allow, type, period,editTime,userID) VALUES (?,?,?,?,?,?,?)';
				$conn->prepare($query)->execute(array($row['staff_id'], $row_allow['allow_id'], $output, '1',  $period, $recordtime, $_SESSION['SESS_MEMBER_ID']));

				$queryUdate = 'UPDATE allow_deduc SET value = ? WHERE allow_id = ? AND staff_id = ? ';
				$conn->prepare($queryUdate)->execute(array($output, $row_allow['allow_id'], $row['staff_id']));
			} catch (PDOException $e) {
				echo $e->getMessage();
			}
			if (intval($row_allow['counter']) > 0) {
				//echo 'allowance deduction counter check';
				$running_counter = intval($row_allow['running_counter']);
				$running_counter = $running_counter + 1;
				if (($running_counter) == intval($row_allow['counter'])) {

					$query = 'INSERT INTO completedloan (staff_id,allow_id,period,value,type)VALUES (?,?,?,?,?)';
					$conn->prepare($query)->execute(array($row['staff_id'], $row_allow['allow_id'], $period, $output, '1'));

					//delete allow once cycle is complete
					$sqlDelete = "DELETE FROM allow_deduc WHERE temp_id = '" . $row_allow['temp_id'] . "'";
					$conn->exec($sqlDelete);
				} else {
					$sqlUpdate = "update allow_deduc set running_counter = '" . $running_counter . "' WHERE temp_id = '" . $row_allow['temp_id'] . "'";
					$conn->exec($sqlUpdate);
				}
			}
		}


		// deduction process


		$total_rows = '';

		$query_deduct = $conn->prepare('SELECT allow_deduc.temp_id, allow_deduc.staff_id, allow_deduc.allow_id, allow_deduc.`value`, allow_deduc.transcode, allow_deduc.counter,  allow_deduc.running_counter, allow_deduc.inserted_by, allow_deduc.date_insert,tbl_earning_deduction.edDesc,tbl_earning_deduction.edType FROM allow_deduc
																			 INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = allow_deduc.allow_id WHERE staff_id = ? and transcode = ? order by allow_deduc.allow_id asc');
		$res_deduct = $query_deduct->execute(array($row['staff_id'], '2'));
		$out_deduct = $query_deduct->fetchAll(PDO::FETCH_ASSOC);
		while ($row_deduct = array_shift($out_deduct)) {
			$output = 0;
			//Process Normal deduction
			if (intval($row_deduct['edType']) == '2') {

				if (intval($row_deduct['allow_id']) == 50) { //process pension
					$sql_consolidated = "SELECT allowancetable.`value` FROM allowancetable WHERE allowancetable.allowcode = 1 and grade = '" . $row['GRADE'] . "' and step = '" . $row['STEP'] . "'";
					$result_consolidated = mysqli_query($salary, $sql_consolidated);
					$row_consolidated = mysqli_fetch_assoc($result_consolidated);
					$total_rowsConsolidated = mysqli_num_rows($result_consolidated);

					if ($total_rowsConsolidated > 0) {
						$sql_pensionRate = "SELECT (pension.PENSON/100) as rate FROM pension WHERE grade = '" . $row['GRADE'] . "' and step = '" . $row['STEP'] . "'";
						$result_pensionRate = mysqli_query($salary, $sql_pensionRate);
						$row_pensionRate = mysqli_fetch_assoc($result_pensionRate);
						$total_pensionRate = mysqli_num_rows($result_pensionRate);

						$output = number_format(($row_consolidated['value'] * $row_pensionRate['rate']), 0, '.', '');
					} else {
						$output = number_format($row_deduct['value'], 0, '.', '');
					}
					//echo $output;	

				} else {
					$output = number_format($row_deduct['value'], 0, '.', '');
				}
				//Save into db
				//echo $row_allow['allow_id'].' '.$row_allow['edDesc'].' '.number_format($output).'<br>';
				try {
					$recordtime = date('Y-m-d H:i:s');
					$query = 'INSERT INTO tbl_master (staff_id, allow_id, deduc, type, period,editTime,userID) VALUES (?,?,?,?,?,?,?)';
					$conn->prepare($query)->execute(array($row['staff_id'], $row_deduct['allow_id'], $output, '2',  $period, $recordtime, $_SESSION['SESS_MEMBER_ID']));

					$queryUdate = 'UPDATE allow_deduc SET value = ? WHERE allow_id = ? AND staff_id = ? ';
					$conn->prepare($queryUdate)->execute(array($output, $row_deduct['allow_id'], $row['staff_id']));
					//delete temp deduction
					if (intval($row_deduct['counter']) > 0) {
						//echo 'Normal deduction counter check';
						$running_counter = intval($row_deduct['running_counter']);
						$running_counter = intval($row_deduct['running_counter']) + 1;
						if (($running_counter) == intval($row_deduct['counter'])) {
							//	echo 'normal deduction counter check';
							$query = 'INSERT INTO completedloan (staff_id,allow_id,period,value,type)VALUES (?,?,?,?,?)';
							$conn->prepare($query)->execute(array($row['staff_id'], $row_deduct['allow_id'], $period, $output, '2'));
							//delete allow once cycle is complete
							$sqlDelete = "DELETE FROM allow_deduc WHERE temp_id = '" . $row_deduct['temp_id'] . "'";
							$conn->exec($sqlDelete);
						} else {
							$sqlUpdate = "update allow_deduc set running_counter = '" . $running_counter . "' WHERE temp_id = '" . $row_deduct['temp_id'] . "'";
							$conn->exec($sqlUpdate);
						}
					}
				} catch (PDOException $e) {
					echo $e->getMessage();
				}
			} else if (intval($row_deduct['edType']) == '3') {
				//Process Union deduction
				$sql_numberOfRows = "SELECT deductiontable.ded_id, deductiontable.allowcode, deductiontable.grade, deductiontable.step, deductiontable.`value`, deductiontable.category, deductiontable.ratetype, deductiontable.percentage FROM deductiontable WHERE allowcode = '" . $row_deduct['allow_id'] . "'";
				$result_numberOfRows = mysqli_query($salary, $sql_numberOfRows);
				$row_numberOfRows = mysqli_fetch_assoc($result_numberOfRows);
				$total_rows = mysqli_num_rows($result_numberOfRows);
				if ($total_rows == 1) {
					if ($row_numberOfRows['ratetype'] == 1) {
						$output = number_format($row_numberOfRows['value'], 0, '.', '');
					} else {
						$sql_consolidated = "SELECT allowancetable.allow_id, allowancetable.allowcode, allowancetable.grade, allowancetable.step, allowancetable.`value`, allowancetable.category, allowancetable.ratetype, allowancetable.percentage FROM allowancetable WHERE allowancetable.allowcode = 1 and grade = '" . $row['GRADE'] . "' and step = '" . $row['STEP'] . "'";
						$result_consolidated = mysqli_query($salary, $sql_consolidated);
						$row_consolidated = mysqli_fetch_assoc($result_consolidated);
						$total_rowsConsolidated = mysqli_num_rows($result_consolidated);
						$output = (($row_numberOfRows['percentage'] * $row_consolidated['value']) / 100);
						if (isset($ouput)) {
							$ouput = number_format($ouput, 0, '.', '');
						} else {
							$ouput = 0;
							// echo $row_numberOfRows['percentage'] . ' - ' . $row_consolidated['value'] . ' - ' . $row_deduct['allow_id'] . ' - ' . $row['GRADE'] . ' - ' . $row['STEP'] . '<br>';
						}
					}
					// if deduction is found in the table
				} else if ($total_rows > 1) {
					$sql_mulitple = "SELECT deductiontable.ded_id, deductiontable.allowcode, deductiontable.grade, deductiontable.step, deductiontable.`value`, deductiontable.category, deductiontable.ratetype, deductiontable.percentage FROM deductiontable WHERE allowcode = '" . $row_deduct['allow_id'] . "' and grade = '" . $row['GRADE'] . "'";
					$result_mulitple = mysqli_query($salary, $sql_mulitple);
					$row_mulitple = mysqli_fetch_assoc($result_mulitple);
					$total_mulitple = mysqli_num_rows($result_mulitple);
					if ($total_mulitple > 0) {
						if ($row_mulitple['ratetype'] == 1) {
							$output = number_format($row_mulitple['value'], 0, '.', '');
							//echo $sql_mulitple ; 
						} else {
							$sql_consolidated = "SELECT allowancetable.allow_id, allowancetable.allowcode, allowancetable.grade, allowancetable.step, allowancetable.`value`, allowancetable.category, allowancetable.ratetype, allowancetable.percentage FROM allowancetable WHERE allowancetable.allowcode = 1 and grade = '" . $row['GRADE'] . "' and step = '" . $row['STEP'] . "'";
							$result_consolidated = mysqli_query($salary, $sql_consolidated);
							$row_consolidated = mysqli_fetch_assoc($result_consolidated);
							$total_rowsConsolidated = mysqli_num_rows($result_consolidated);
							if ($total_rowsConsolidated > 0) {
								$output = number_format((($row_mulitple['percentage'] * $row_consolidated['value']) / 100), 0, '.', '');
							} else {
								$output = number_format($row_deduct['value'], 0, '.', '');
							}
						}
					} else {
						$output = number_format($row_deduct['value'], 0, '.', '');
					}
				} else {
					$output = number_format($row_deduct['value'], 0, '.', '');
				}
				//echo $row_allow['allow_id'].' '.$row_allow['edDesc'].' '.number_format($output).'<br>';		
				try {
					$recordtime = date('Y-m-d H:i:s');
					$query = 'INSERT INTO tbl_master (staff_id, allow_id, deduc, type, period,editTime,userID) VALUES (?,?,?,?,?,?,?)';
					$conn->prepare($query)->execute(array($row['staff_id'], $row_deduct['allow_id'], $output, '2',  $period, $recordtime, $_SESSION['SESS_MEMBER_ID']));

					$queryUdate = 'UPDATE allow_deduc SET value = ? WHERE allow_id = ? AND staff_id = ? ';
					$conn->prepare($queryUdate)->execute(array($output, $row_deduct['allow_id'], $row['staff_id']));


					//process temp allow id = ?

					if (intval($row_deduct['counter']) > 0) {
						//echo 'union deduction counter check';
						$running_counter = intval($row_deduct['running_counter']);
						$running_counter = intval($row_deduct['running_counter']) + 1;
						if (($running_counter) == intval($row_deduct['counter'])) {
							//delete allow once cycle is complete
							$query = 'INSERT INTO completedloan (staff_id,allow_id,period,value,type)VALUES (?,?,?,?,?)';
							$conn->prepare($query)->execute(array($row['staff_id'], $row_deduct['allow_id'], $period, $output, '2'));

							$sqlDelete = "DELETE FROM allow_deduc WHERE temp_id = '" . $row_deduct['temp_id'] . "'";
							$conn->exec($sqlDelete);
						} else {
							$sqlUpdate = "update allow_deduc set running_counter = '" . $running_counter . "' WHERE temp_id = '" . $row_deduct['temp_id'] . "'";
							$conn->exec($sqlUpdate);
						}
					}
				} catch (PDOException $e) {
					echo $e->getMessage();
				}
				//process loan deduction
			} else if (intval($row_deduct['edType']) == '4') {
				$sql_loancheck = "SELECT tbl_earning_deduction_type.edType FROM tbl_earning_deduction_type INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.edType = tbl_earning_deduction_type.edType WHERE tbl_earning_deduction.ed_id = '" . $row_deduct['allow_id'] . "' and tbl_earning_deduction_type.edType = 4";
				$result_loancheck = mysqli_query($salary, $sql_loancheck);
				$row_loan = mysqli_fetch_assoc($result_loancheck);
				$total_loancheck = mysqli_num_rows($result_loancheck);
				//echo 'sql check ='. $sql_loancheck. '<br>';
				//echo 'loan check ='. $total_loancheck. '<br>';
				if ($total_loancheck > 0) {

					$sql_loan = "SELECT tbl_debt.staff_id,tbl_debt.allow_id, SUM(ifnull(tbl_debt.principal,0))+SUM(ifnull(tbl_debt.interest,0)) as loan FROM tbl_debt WHERE staff_id = '" . $row['staff_id'] . "' AND allow_id = '" . $row_deduct['allow_id'] . "' GROUP BY staff_id";
					$result_loan = mysqli_query($salary, $sql_loan);
					$row_loan = mysqli_fetch_assoc($result_loan);
					$total_loan = mysqli_num_rows($result_loan);

					$sql_repayment = "SELECT tbl_repayment.staff_id, tbl_repayment.allow_id, SUM(ifnull(tbl_repayment.value,0)) as repayment FROM tbl_repayment WHERE staff_id = '" . $row['staff_id'] . "' and allow_id = '" . $row_deduct['allow_id'] . "' GROUP BY staff_id";
					$result_repayment = mysqli_query($salary, $sql_repayment);
					$row_repayment = mysqli_fetch_assoc($result_repayment);
					$total_repayment = mysqli_num_rows($result_repayment);

					if ($total_repayment == 0) {
						$row_repayment['repayment'] = 0;
					}

					$balance = $row_loan['loan'] - $row_repayment['repayment'];
					//print number_format($balance);
					//echo $sql_repayment ;
					if (floatval($balance) > floatval($row_deduct['value'])) {
						$output = number_format(floatval($row_deduct['value']), 0, '.', '');
						//add payment
						try {
							$recordtime = date('Y-m-d H:i:s');
							$query_repayment = 'INSERT INTO tbl_repayment (staff_id, allow_id, value,  period,userID,editTime) VALUES (?,?,?,?,?,?)';
							$conn->prepare($query_repayment)->execute(array($row['staff_id'], $row_deduct['allow_id'], $output, $period, $period, $recordtime));
						} catch (PDOException $e) {
							echo $e->getMessage();
						}
					} else if (floatval($balance) <= floatval($row_deduct['value'])) {
						$output = number_format(floatval($balance), 0, '.', '');
						try {
							//	echo 'loan deduction counter check';
							$recordtime = date('Y-m-d H:i:s');

							$query = 'INSERT INTO completedloan (staff_id,allow_id,period,value,type)VALUES (?,?,?,?,?)';
							$conn->prepare($query)->execute(array($row['staff_id'], $row_deduct['allow_id'], $period, $output, '2'));

							$query_repayment = 'INSERT INTO tbl_repayment (staff_id, allow_id, value,  period,userID,editTime) VALUES (?,?,?,?,?,?)';
							$conn->prepare($query_repayment)->execute(array($row['staff_id'], $row_deduct['allow_id'], $output, $period, $period, $recordtime));
							//delete loan id


							$query = 'DELETE FROM allow_deduc where allow_id = ? and staff_id = ?';
							$conn->prepare($query)->execute(array($row_deduct['allow_id'], $row['staff_id']));
						} catch (PDOException $e) {
							echo $e->getMessage();
						}
					}
				}
				//echo $row_deduct['allow_id'].' '.$row_deduct['edDesc'].' '.number_format($output).'<br>';
				try {




					$recordtime = date('Y-m-d H:i:s');
					$query = 'INSERT INTO tbl_master (staff_id, allow_id, deduc, type, period,editTime,userID) VALUES (?,?,?,?,?,?,?)';
					$conn->prepare($query)->execute(array($row['staff_id'], $row_deduct['allow_id'], $output, '2',  $period, $recordtime, $_SESSION['SESS_MEMBER_ID']));

					$queryUdate = 'UPDATE allow_deduc SET value = ? WHERE allow_id = ? AND staff_id = ? ';
					$conn->prepare($queryUdate)->execute(array($output, $row_deduct['allow_id'], $row['staff_id']));
				} catch (PDOException $e) {
					echo $e->getMessage();
				}
			}
		}

		$j++;
		echo str_repeat(' ', 1024 * 64);
		echo '<script>
					    parent.document.getElementById("progress").innerHTML="<div style=\"width:' . $percent . ';background:linear-gradient(to bottom, rgba(125,126,125,1) 0%,rgba(14,14,14,1) 100%); text-align:center;color:white;height:35px;display:block;\">' . $percent . '</div>";
					    parent.document.getElementById("information").innerHTML="<div style=\"text-align:center; font-weight:bold\">Processing ' . $row['staff_id'] . ' ' . $percent . ' is processed.</div>";</script>';

		ob_flush();
		flush();
	}
} catch (PDOException $e) {
	echo $e->getMessage();
}

//set openview status
$statuschange = $conn->prepare('UPDATE payperiods SET payrollRun = ? WHERE periodId = ?');
$perres = $statuschange->execute(array('1', $period));
echo '<script>parent.document.getElementById("information").innerHTML="<div style=\"text-align:center; display:block; font-weight:bold\">Process completed</div>";
        			parent.document.getElementById("payprocessbtn").disabled = false;
        			</script>';
// } else {
// 	echo '<script>parent.document.getElementById("information").innerHTML="<div style=\"text-align:center; font-weight:bold\">Payroll Already Processed for the Month</div>";
//         			 parent.document.getElementById("payprocessbtn").disabled = false;
//         			 </script>';
// }
