<?php
ini_set('max_execution_time', '0');
require_once('../Connections/paymaster.php');
$recordtime = date('Y-m-d H:i:s');
session_start();
require 'vendor/autoload.php'; // Load Composer autoloader

use PhpOffice\PhpSpreadsheet\IOFactory;

$excelFile = $_FILES["file"]["tmp_name"];
if (isset($_POST['hasHeaders'])) {

    $i = $_POST['hasHeaders'] == 0 ? 0 : 1;
}

$spreadsheet = IOFactory::load($excelFile);
$worksheet = $spreadsheet->getActiveSheet();
$data = $worksheet->toArray();

// Assuming the Excel data starts from the second row (index 1)
for ($i; $i < count($data); $i++) {
    $id = $data[$i][0]; // Assuming ID is in the first column
    $value = $data[$i][1]; // Assuming Value is in the second column

    mysqli_select_db($salary, $database_salary);
    $sqlStaff_id = "select staff_id from employee where staff_id ='" . $id . "'";
    $Staff_id = mysqli_query($salary, $sqlStaff_id) or die(mysqli_error($salary));
    $row_Staff_id = mysqli_fetch_assoc($Staff_id);
    $total_Staff_id = mysqli_num_rows($Staff_id);
    if ($total_Staff_id > 0) {
        $Staff_id = $row_Staff_id['staff_id'];

        $qryCheck = "select * from allow_deduc where allow_id = '41' AND staff_id = '" . $id . "'";
        $Check = mysqli_query($salary, $qryCheck) or die(mysqli_error($salary));
        $row_qryCheck = mysqli_fetch_assoc($Check);
        $total_Check = mysqli_num_rows($Check);
        if ($total_Check > 0) {
            //It wiil insert a row to our subject table from our csv file`
            $sql = "UPDATE allow_deduc SET value =  {$value},date_insert = '{$recordtime}', inserted_by = '{$_SESSION['SESS_MEMBER_ID']}' where allow_id = '41' AND staff_id = '" . $id . "'";
            //we are using mysql_query function. it returns a resource on true else False on error
        } else {
            $sql = "INSERT INTO allow_deduc (staff_id, allow_id, value, transcode, date_insert, inserted_by) VALUES ({$id}, 41, {$value}, 2, '{$recordtime}', {$_SESSION['SESS_MEMBER_ID']})";
        }
        mysqli_select_db($salary, $database_salary);
        $result = mysqli_query($salary, $sql) or die(mysqli_error($salary));
        if (!$result) {
            echo "<script type=\"text/javascript\">
    							alert(\"Invalid File:Please Upload CSV File.\");
    							window.location = \"index.php\"
    						</script>";
        }
    }
}
echo "<script type=\"text/javascript\">
    						alert(\"CSV File has been successfully Imported.\");
    						window.location = \"index.php\"
    					</script>";

//close of connection
mysqli_close($salary);
