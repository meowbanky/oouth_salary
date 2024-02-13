<?php
include_once('../classes/model.php');
require_once('../Connections/paymaster.php');
require_once('pdf.php');

//$item = $_POST['staff_no'];
$period = $_POST['period'];
$All = $_POST['All'];

if (isset($_POST['staff_no'])) {
	$item = $_POST['staff_no'];
} else {
	$item = -1;
}
?>

<div id="progress" style="border:1px solid #ccc; border-radius: 5px;"></div>
<div id="information" style="width:100%"></div>
<div id="message" style="width:100%"></div>
<?php
// try {
//     if ($All == 0) {
//         $query = $conn->prepare('SELECT
// 	employee.staff_id, 
// 	employee.`NAME`
// FROM
// 	employee
// 	WHERE ISNULL(EMAIL) = FALSE AND STATUSCD = "A" AND staff_id = ?');
//         $res = $query->execute(array($item));
//     } else {
//         $query = $conn->prepare('SELECT
// 	employee.staff_id, 
// 	employee.`NAME`
// FROM
// 	employee
// 	WHERE ISNULL(EMAIL) = FALSE AND STATUSCD = "A"');
//         $res = $query->execute();
//     }
//     $out = $query->fetchAll(PDO::FETCH_ASSOC);

//     while ($row = array_shift($out)) {
//         generatePdf($row['staff_id'], $period);
//     }
// } catch (PDOException $e) {
//     $e->getMessage();
// }

try {
	mysqli_select_db($salary, $database_salary);
	if ($All == 0) {
		$query_masterTransaction = "SELECT * FROM employee WHERE ISNULL(EMAIL) = FALSE AND STATUSCD = 'A' AND staff_id = {$item}";
	} else {
		$query_masterTransaction = 'SELECT * FROM employee WHERE ISNULL(EMAIL) = FALSE AND STATUSCD = "A"';
	}
	$masterTransaction = mysqli_query($salary, $query_masterTransaction) or die(mysqli_error($salary));
	$row_masterTransaction = mysqli_fetch_assoc($masterTransaction);
	$totalRows_masterTransaction = mysqli_num_rows($masterTransaction);
	$total = $totalRows_masterTransaction;

	$j = 1;
	$percent;

	//$message = '';
	do {
		$percent = intval($j / $total * 100) . "%";

		$message = generatePdf($row_masterTransaction['staff_id'], $period);
		//$message = generatePdf(1140, $period);

		echo str_repeat(' ', 1024 * 64);
		echo '<script>
	parent.document.getElementById("progress").innerHTML="<div style=\"width:' . $percent . ';background:linear-gradient(to bottom, rgba(125,126,125,1) 0%,rgba(14,14,14,1) 100%); text-align:center;color:white;height:35px;display:block;\">' . $percent .
			'</div>";
	parent.document.getElementById("information").innerHTML="<div style=\"text-align:center; font-weight:bold\">Processing ' . $row_masterTransaction['staff_id'] . ' ' . $percent . ' is processed.</div>";
	parent.document.getElementById("message").innerHTML="<div style=\"text-align:center; font-weight:bold\"> ' . $message . '</div>";
	</script>';

		ob_flush();
		flush();
		$j++;
	} while ($row_masterTransaction = mysqli_fetch_assoc($masterTransaction));
} catch (PDOException $e) {
	$e->getMessage();
}

//generatePdf($item, $period);
