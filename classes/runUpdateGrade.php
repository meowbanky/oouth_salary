<?php
ini_set('max_execution_time', '0');
require_once('../Connections/paymaster.php');
mysqli_select_db($salary, $database_salary);
include_once('functions.php');
session_start();

$j = 0;
$percent;
$step = trim($_POST['new_step']);
$grade = trim($_POST['new_grade']);

if (intval($grade) < 10) {

	if (strlen(($grade)) < 2) {
		$grade = '0' . $grade;
	}
}

if (intval($step) < 10) {

	if (strlen(($step)) < 2) {
		$step = '0' . $step;
	}
}


global $conn;

$step = $step;
$grade = $grade;
$staff_id = $_POST['curremployee'];

$query = $conn->prepare('UPDATE employee SET STEP = ?, GRADE = ? WHERE staff_id = ?');
$res = $query->execute(array($step, $grade, $staff_id));



//To get total percentage
mysqli_select_db($salary, $database_salary);
$query_masterTransaction = "SELECT * FROM employee WHERE staff_id = '{$staff_id}'";
$masterTransaction = mysqli_query($salary, $query_masterTransaction) or die(mysqli_error($salary));
$row_masterTransaction = mysqli_fetch_assoc($masterTransaction);
$totalRows_masterTransaction = mysqli_num_rows($masterTransaction);
$total = $totalRows_masterTransaction;

try { //echo $period ;


	$query = $conn->prepare('SELECT * FROM employee WHERE staff_id = ?');
	$res = $query->execute(array($staff_id));
	$out = $query->fetchAll(PDO::FETCH_ASSOC);
	//get employee info                                          
	while ($row = array_shift($out)) {	//$percent = '';

		//ob_end_clean();
		$percent = intval($j / $total * 100) . "%";

		//echo 'staff id'.' '.$row['staff_id'].'<br>';
		$query_allow = $conn->prepare('SELECT allow_deduc.temp_id, allow_deduc.staff_id, allow_deduc.allow_id, allow_deduc.`value`, allow_deduc.transcode, allow_deduc.counter,  allow_deduc.running_counter, allow_deduc.inserted_by, allow_deduc.date_insert,tbl_earning_deduction.edDesc FROM allow_deduc
 																				INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = allow_deduc.allow_id WHERE staff_id = ? and transcode = ? order by allow_deduc.allow_id asc');
		$res_allow = $query_allow->execute(array($row['staff_id'], '1'));
		$out_allow = $query_allow->fetchAll(PDO::FETCH_ASSOC);
		while ($row_allow = array_shift($out_allow)) {


			if ($row_allow['allow_id'] == '21') {

				$query_value = $conn->prepare('SELECT allowancetable.`value` FROM allowancetable WHERE allowancetable.grade = ? AND allowancetable.step = ? AND allowcode = ? AND category = ?');
				$rerun_value = $query_value->execute(array($row['GRADE'], $row['STEP'], $row_allow['allow_id'], $row['CALLTYPE']));
			} else if ($row_allow['allow_id'] == '5') {
				$query_value = $conn->prepare('SELECT allowancetable.`value` FROM allowancetable WHERE allowancetable.grade = ? AND allowancetable.step = ? AND allowcode = ? AND category = ?');
				$rerun_value = $query_value->execute(array($row['GRADE'], $row['STEP'], $row_allow['allow_id'], $row['HARZAD_TYPE']));
			} else {

				$query_value = $conn->prepare('SELECT allowancetable.`value` FROM allowancetable WHERE allowancetable.grade = ? AND allowancetable.step = ? AND allowcode = ?');
				$rerun_value = $query_value->execute(array($row['GRADE'], $row['STEP'], $row_allow['allow_id']));
			}


			if ($row_value = $query_value->fetch()) {
				$output = $row_value['value'];
			} else {

				$output = $row_allow['value'];
			}


			// echo $row_allow['allow_id'].' '.$row_allow['edDesc'].' '.number_format($output).'<br>';
			try {
				$recordtime = date('Y-m-d H:i:s');
				//$query = 'INSERT INTO tbl_master (staff_id, allow_id, allow, type, period,editTime,userID) VALUES (?,?,?,?,?,?,?)';
				//$conn->prepare($query)->execute(array($row['staff_id'], $row_allow['allow_id'], $output, '1',  $period,$recordtime,$_SESSION['SESS_MEMBER_ID']));

				$queryUdate = 'UPDATE allow_deduc SET value = ? WHERE allow_id = ? AND staff_id = ? ';
				$conn->prepare($queryUdate)->execute(array($output, $row_allow['allow_id'], $row['staff_id']));
			} catch (PDOException $e) {
				echo $e->getMessage();
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

					$sql_pensionRate = "SELECT (pension.PENSON/100) as rate FROM pension WHERE grade = '" . $row['GRADE'] . "' and step = '" . $row['STEP'] . "'";
					$result_pensionRate = mysqli_query($salary, $sql_pensionRate);
					$row_pensionRate = mysqli_fetch_assoc($result_pensionRate);
					$total_pensionRate = mysqli_num_rows($result_pensionRate);

					$output = ceil($row_consolidated['value'] * $row_pensionRate['rate']);
					//echo $output;	

				} else {
					$output = $row_deduct['value'];
				}
				//Save into db
				//echo $row_allow['allow_id'].' '.$row_allow['edDesc'].' '.number_format($output).'<br>';
				try {
					$recordtime = date('Y-m-d H:i:s');
					//$query = 'INSERT INTO tbl_master (staff_id, allow_id, deduc, type, period,editTime,userID) VALUES (?,?,?,?,?,?,?)';
					//$conn->prepare($query)->execute(array($row['staff_id'], $row_deduct['allow_id'], $output, '2',  $period,$recordtime,$_SESSION['SESS_MEMBER_ID']));

					$queryUdate = 'UPDATE allow_deduc SET value = ? WHERE allow_id = ? AND staff_id = ? ';
					$conn->prepare($queryUdate)->execute(array($output, $row_deduct['allow_id'], $row['staff_id']));

					//delete temp deduction


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
						$output = $row_numberOfRows['value'];
					} else {
						$sql_consolidated = "SELECT allowancetable.allow_id, allowancetable.allowcode, allowancetable.grade, allowancetable.step, allowancetable.`value`, allowancetable.category, allowancetable.ratetype, allowancetable.percentage FROM allowancetable WHERE allowancetable.allowcode = 1 and grade = '" . $row['GRADE'] . "' and step = '" . $row['STEP'] . "'";
						$result_consolidated = mysqli_query($salary, $sql_consolidated);
						$row_consolidated = mysqli_fetch_assoc($result_consolidated);
						$total_rowsConsolidated = mysqli_num_rows($result_consolidated);
						$output = ($row_numberOfRows['percentage'] * $row_consolidated['value']) / 100;
					}
					// if deduction is found in the table
				} else if ($total_rows > 1) {
					$sql_mulitple = "SELECT deductiontable.ded_id, deductiontable.allowcode, deductiontable.grade, deductiontable.step, deductiontable.`value`, deductiontable.category, deductiontable.ratetype, deductiontable.percentage FROM deductiontable WHERE allowcode = '" . $row_deduct['allow_id'] . "' and grade = '" . $row['GRADE'] . "'";
					$result_mulitple = mysqli_query($salary, $sql_mulitple);
					$row_mulitple = mysqli_fetch_assoc($result_mulitple);
					$total_mulitple = mysqli_num_rows($result_mulitple);
					if ($total_mulitple > 0) {
						if ($row_mulitple['ratetype'] == 1) {
							$output = $row_mulitple['value'];
							//echo $sql_mulitple ; 
						} else {
							$sql_consolidated = "SELECT allowancetable.allow_id, allowancetable.allowcode, allowancetable.grade, allowancetable.step, allowancetable.`value`, allowancetable.category, allowancetable.ratetype, allowancetable.percentage FROM allowancetable WHERE allowancetable.allowcode = 1 and grade = '" . $row['GRADE'] . "' and step = '" . $row['STEP'] . "'";
							$result_consolidated = mysqli_query($salary, $sql_consolidated);
							$row_consolidated = mysqli_fetch_assoc($result_consolidated);
							$total_rowsConsolidated = mysqli_num_rows($result_consolidated);
							$output = ceil(($row_mulitple['percentage'] * $row_consolidated['value']) / 100);
						}
					} else {
						$output = $row_deduct['value'];
					}
				} else {
					$output = $row_deduct['value'];
				}
				//echo $row_allow['allow_id'].' '.$row_allow['edDesc'].' '.number_format($output).'<br>';		
				try {
					$recordtime = date('Y-m-d H:i:s');

					$queryUdate = 'UPDATE allow_deduc SET value = ? WHERE allow_id = ? AND staff_id = ? ';
					$conn->prepare($queryUdate)->execute(array($output, $row_deduct['allow_id'], $row['staff_id']));

					//process temp allow id


				} catch (PDOException $e) {
					echo $e->getMessage();
				}
				//process loan deduction
			}
		}

		$j++;
		echo '<script>
					    parent.document.getElementById("progress").innerHTML="<div style=\"width:' . $percent . ';background:linear-gradient(to bottom, rgba(125,126,125,1) 0%,rgba(14,14,14,1) 100%); ;height:35px;display:block;\">&nbsp;</div>";
					    parent.document.getElementById("information").innerHTML="<div style=\"text-align:center; font-weight:bold\">Processing ' . $row['staff_id'] . ' ' . $percent . ' is processed.</div>";</script>';

		ob_flush();
		flush();
	}
} catch (PDOException $e) {
	echo $e->getMessage();
}

//set openview status

echo '<script>parent.document.getElementById("information").innerHTML="<div style=\"text-align:center; display:block; font-weight:bold\">Process completed</div>";
        			parent.document.getElementById("payprocessbtn").disabled = false;
        			</script>';
