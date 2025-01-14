<?php
ini_set('max_execution_time', '0');
require_once('../Connections/paymaster.php');
require_once('../classes/fn_runUpdateGrade.php');
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
    $value = $data[$i][2]; // Assuming Value is in the second column
    $splited = explode('/', $value);
    mysqli_select_db($salary, $database_salary);
    $sqlStaff_id = "select staff_id from employee where staff_id ='" . $id . "'";
    $Staff_id = mysqli_query($salary, $sqlStaff_id) or die(mysqli_error($salary));
    $row_Staff_id = mysqli_fetch_assoc($Staff_id);
    $total_Staff_id = mysqli_num_rows($Staff_id);
    echo $splited[0] . ' - ' . $splited[1] . '<br>';
    if ($total_Staff_id > 0) {
        $Staff_id = $row_Staff_id['staff_id'];
        $sql = "UPDATE employee SET GRADE = '{$splited[0]}',STEP = '{$splited[1]}',editTime = '{$recordtime}', userID = '{$_SESSION['SESS_MEMBER_ID']}' where staff_id = '" .  $Staff_id . "'";
        //we are using mysql_query function. it returns a resource on true else False on error

        mysqli_select_db($salary, $database_salary);
        $result = mysqli_query($salary, $sql) or die(mysqli_error($salary));
        //update allow_deduction
        runGrade_Step($splited[1], $splited[0], $Staff_id);
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
