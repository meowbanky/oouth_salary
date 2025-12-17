<?php
header('Content-Type: application/json');
require_once '../vendor/autoload.php';
require_once '../config/config.php';
require_once '../libs/App.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

$App = new App();
//var_dump($_POST);
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_FILES)) {
    $inserted_by  = $_SESSION['SESS_MEMBER_ID'];
    $file = $_FILES['file'];
    $fileTmpPath = $file['tmp_name'];
     $counter = trim($_POST['counter']);
    $counter = $counter === '' ? 0 : $counter;
    $allow_id = $_POST['allow_id'];
     $add_remove = $_POST['add_remove'];
    $verificationColumn = $_POST['verification'];

    if(ctype_digit($counter) || !empty($allow_id)  || ($allow_id != "")) {

        if($_POST['allow_id'] == ""){
           $response = ["status" => "error", "message" => "Invalid request."];
            echo json_encode($response);
            exit();
        }

        if ($file['error'] == UPLOAD_ERR_OK) {
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedfileExtensions = ['xlsx'];

            if (in_array($fileExtension, $allowedfileExtensions)) {
                $reader = new Xlsx();
                $spreadsheet = $reader->load($fileTmpPath);
                $sheet = $spreadsheet->getActiveSheet();
                $columns = $sheet->toArray();

                // Assume first row contains column names, and start processing from the second row
                $processedRows = 0;
                foreach ($columns as $index => $column) {
                    if ($index == 0) {
                        continue; // Skip header row
                    }


                        $verificationValue = $column[0];
                        $value = $column[1];

                    // Validation
                    if (empty($verificationColumn) || (!is_numeric($value))) {
                        continue; // Skip invalid rows
                    }

                    // Verify if the staff_id exists
                    $checkStaffQuery = "SELECT COUNT(*),staff_id FROM employee WHERE {$verificationColumn} = :verificationColumn GROUP BY staff_id";
                    $staffParams = [':verificationColumn' => $verificationValue];
                    $staffExists = $App->selectOne($checkStaffQuery, $staffParams);

                    if ($staffExists == 0) {
                        continue; // Skip if staff_id does not exist
                    }

                     $staff_id = $staffExists['staff_id'];
                    // Update database with the allowance and deduction
                    if($add_remove == 1) {

                        $query = "INSERT INTO allow_deduc (staff_id, allow_id, value, transcode, counter, inserted_by, date_insert) 
            VALUES (:staff_id, :allow_id, :value, 
                   (SELECT code FROM tbl_earning_deduction WHERE ed_id = :allow_id), 
                   :counter, :inserted_by, NOW())
            ON DUPLICATE KEY UPDATE 
            value = VALUES(value),
            transcode = (SELECT code FROM tbl_earning_deduction WHERE ed_id = :allow_id),
            counter = VALUES(counter),
            inserted_by = VALUES(inserted_by),
            date_insert = NOW()";

                        $params = [
                            ':staff_id' => $staff_id,
                            ':allow_id' => $allow_id,
                            ':value' => $value,
                            ':counter' => $counter,
                            ':inserted_by' => $inserted_by
                        ];
                }else{
                    $query = "Delete from allow_deduc WHERE allow_id = :allow_id AND staff_id = :staff_id";
                        $params = [
                            ':staff_id' => $staff_id,
                            ':allow_id' => $allow_id
                            ];
                    }
                    $result = $App->executeNonSelect($query, $params);
                    if ($result) {
                        $processedRows++;
                    }
                }
                $response = ["status" => "success", "message" => "Processed $processedRows rows successfully."];
            } else {
                $response = ["status" => "error", "message" => "Invalid file type. Please upload an Excel file."];
            }
        } else {
            $response = ["status" => "error", "message" => "Error uploading file."];
        }
    }else {
        $response = ["status" => "error", "message" => "Counter should be a digit."];
    }
} else {
    $response = ["status" => "error", "message" => "Invalid request."];
}
echo json_encode($response);
?>
