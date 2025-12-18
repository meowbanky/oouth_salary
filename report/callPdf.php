<?php
include_once('../classes/model.php');
require_once('../Connections/paymaster.php');
require_once('pdf.php');

//$item = $_POST['staff_no'];
$period = $_POST['period'];
$All = $_POST['All'];
if (isset($_POST['dept'])) {
	$dept = $_POST['dept'];
} else {
	$dept = -1;
}



if (isset($_POST['staff_no'])) {
	$item = $_POST['staff_no'];
} else {
	$item = -1;
}

// Get custom email if provided
$customEmail = isset($_POST['custom_email']) ? trim($_POST['custom_email']) : '';
?>

<div id="progress" style="border:1px solid #ccc; border-radius: 5px;"></div>
<div id="information" style="width:100%"></div>
<div id="message" style="width:100%"></div>
<?php


try {
	mysqli_select_db($salary, $database_salary);
	if ($All == 0) {
		$query_masterTransaction = "SELECT * FROM employee WHERE ISNULL(EMAIL) = FALSE AND staff_id = {$item}";
	} elseif ($All == 1) {
		$query_masterTransaction = 'SELECT * FROM employee WHERE ISNULL(EMAIL) = FALSE AND STATUSCD = "A"';
	} elseif ($All == 2) {
		$query_masterTransaction = "SELECT * FROM employee WHERE DEPTCD = {$dept} AND ISNULL(EMAIL) = FALSE AND STATUSCD = 'A'";
	}
	$masterTransaction = mysqli_query($salary, $query_masterTransaction) or die(mysqli_error($salary));
	$row_masterTransaction = mysqli_fetch_assoc($masterTransaction);
	$totalRows_masterTransaction = mysqli_num_rows($masterTransaction);
	$total = $totalRows_masterTransaction;

	$j = 1;
	$percent = 0;


	//$message = '';
	do {
		$percent = $total > 0 ? intval($j / $total * 100) . "%" : "0%";

		// Pass custom email if provided (only for single staff, not bulk)
		$emailToUse = ($All == 0 && !empty($customEmail)) ? $customEmail : null;
		echo $message = generateAndSendPayslip($row_masterTransaction['staff_id'], $period, $emailToUse);

		//generatePdf($row_masterTransaction['staff_id'], $period);
		//$message = generatePdf(1140, $period);

		echo str_repeat(' ', 1024 * 64);
		echo '<script>
	parent.document.getElementById("progress").innerHTML="<div style=\"width:' . $percent . ';background:linear-gradient(to bottom, rgba(125,126,125,1) 0%,rgba(14,14,14,1) 100%); text-align:center;color:white;height:35px;display:block;\">' . $percent .
			'</div>";
	parent.document.getElementById("information").innerHTML="<div style=\"text-align:center; font-weight:bold\">Processing ' . $row_masterTransaction['staff_id'] . ' ' . $percent . ' is processed.</div>";
	parent.document.getElementById("message").innerHTML = "<div style=\"text-align:center; font-weight:bold\"> ' . $message . '</div>";
	</script>';

		ob_flush();
		flush();
		$j++;
	} while ($row_masterTransaction = mysqli_fetch_assoc($masterTransaction));
} catch (PDOException $e) {
	$e->getMessage();
}

//generatePdf($item, $period);