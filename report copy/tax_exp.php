<?php

require_once('../Connections/paymaster.php');

$period = 19;
mysqli_select_db($salary, $database_salary);
$queryemployee = "SELECT staff_id FROM master_staff WHERE period = $period ORDER BY staff_id ASC";
$result_employee = mysqli_query($salary, $queryemployee);
$row_employee = mysqli_fetch_assoc($result_employee);
$total_employee = mysqli_num_rows($result_employee);
while ($row_employee = mysqli_fetch_assoc($result_employee)) {
    $thisemployee = $row_employee['staff_id'];
    try {
        $query = $conn->prepare('SELECT tbl_master.allow, tbl_master.allow_id,edDesc FROM tbl_master INNER JOIN
	    tbl_earning_deduction ON  tbl_master.allow_id = tbl_earning_deduction.ed_id WHERE  allow_id IN (1,5,23,19,39,25,3) AND staff_id = ? AND period = ?');
        $fin = $query->execute(array($thisemployee, $period));
        $res = $query->fetchAll(PDO::FETCH_ASSOC);
        //print_r($res);

        foreach ($res as $row => $link) {



            if (isset($link['allow_id'])) {
                if ($link['allow_id'] == 1) {
                    echo 'Consolidated ' . $link['allow'];
                    $totalAllow = $totalAllow + $link['allow'];
                } else {
                }
                if ($link['allow_id'] == 5) {
                    echo 'Hazard ' . $link['allow'];
                    $totalAllow = $totalAllow + $link['allow'];
                } else {
                }
                if ($link['allow_id'] == 23) {
                    echo 'Teaching ' . $link['allow'];
                    $totalAllow = $totalAllow + $link['allow'];
                } else {
                }
                if ($link['allow_id'] == 19) {
                    echo 'Clinical ' . $link['allow'];
                    $totalAllow = $totalAllow + $link['allow'];
                    $Data['Clinical'] = '';
                }
                if ($link['allow_id'] == 39) {
                    echo 'Specialist ' . $link['allow'];
                    $totalAllow = $totalAllow + $link['allow'];
                } else {
                }
                if ($link['allow_id'] == 25) {
                    echo 'Other Allowance2 ' . $link['allow'];
                    $totalAllow = $totalAllow + $link['allow'];
                } else {
                }
                if ($link['allow_id'] == 3) {
                    echo 'EmArrearspno ' . $link['allow'];
                    $totalAllow = $totalAllow + $link['allow'];
                } else {
                }
                echo '<br>';
                // $Data['Total'] = $totalAllow;
            }
        }
    } catch (PDOException $e) {
        echo $e->getMessage();
    }
}
