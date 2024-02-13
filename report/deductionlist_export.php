<?php
ob_start();

session_start();

include_once('../classes/model.php');
require_once('../Connections/paymaster.php');
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) === '')) {
    header("location: ../index.php");
    exit;
}
require 'office_vendor/autoload.php';


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

require 'vendor/autoload.php';


$period = filter_input(INPUT_POST, 'period', FILTER_VALIDATE_INT) ?: -1;
$deduction = filter_input(INPUT_POST, 'deduction', FILTER_VALIDATE_INT) ?: -1;
$deduction_text = filter_input(INPUT_POST, 'deduction_text', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
$period_text = filter_input(INPUT_POST, 'period_text', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
$code = filter_input(INPUT_POST, 'code', FILTER_VALIDATE_INT) ?: -1;


function prepareSqlStatement($code, $deduction, $period) {
    // Example SQL statement logic based on the provided code
    if ($code == 1) {
        $sql = 'SELECT master_staff.staff_id,master_staff.`NAME`,tbl_master.allow as deduc 
                FROM tbl_master 
                INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id 
                WHERE tbl_master.allow_id = :deduction AND tbl_master.period = :period AND master_staff.period = :period 
                ORDER BY master_staff.staff_id ASC';
    } else {
        $sql = 'SELECT master_staff.staff_id,master_staff.`NAME`,tbl_master.deduc as deduc 
                FROM tbl_master 
                INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id
                WHERE tbl_master.allow_id = :deduction AND tbl_master.period = :period AND master_staff.period = :period 
                ORDER BY master_staff.staff_id ASC';
    }

    return $sql;
}

function fetchDataFromDatabase($deduction, $period, $code, $conn) {
    $sql = prepareSqlStatement($code, $deduction, $period);
    $stmt = $conn->prepare($sql);
    // Bind parameters as an associative array
    $stmt->execute([
        ':deduction' => $deduction, 
        ':period' => $period
    ]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $results;
}



function generateExcelFile($data, $deductionText, $periodText) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Define headers
    $headers = ['S/No.', 'Staff No.', 'Name', 'Amount'];

    // Add headers to the first row
    $sheet->fromArray([$headers], null, 'A1');

    // Initialize total sum
    $totalSum = 0;

    // Add serial numbers to each row of data and calculate total sum
    $formattedData = [];
    foreach ($data as $key => $row) {
        $formattedRow = [
            $key + 1, // S/No.
            $row['staff_id'], // Staff No.
            $row['NAME'], // Name
            $row['deduc'] // Amount
        ];
        $formattedData[] = $formattedRow;
        $totalSum += $row['deduc']; // Accumulate the sum of the Amount column
    }

    // Add data with serial numbers and amounts starting from the second row
    $sheet->fromArray($formattedData, null, 'A2');

    // Add the total sum as a footer after the data
    $footerRowIndex = count($data) + 2; // Add 2 because data starts from row 2
    $sheet->setCellValue("A{$footerRowIndex}", 'Total');
    $sheet->mergeCells("A{$footerRowIndex}:C{$footerRowIndex}"); // Merge the cells for the 'Total' label
    $sheet->setCellValue("D{$footerRowIndex}", $totalSum); // Set the total sum in the Amount column

    // Style the footer row if needed
    $footerStyle = [
        'font' => ['bold' => true],
        'borders' => [
            'top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
        ],
    ];
    $sheet->getStyle("A{$footerRowIndex}:D{$footerRowIndex}")->applyFromArray($footerStyle);
    
    $fileName = "{$deductionText} - {$periodText}.xlsx";
    $tempFilePath = sys_get_temp_dir() . '/' . $fileName;
    $writer = new Xlsx($spreadsheet);
    $writer->save($tempFilePath);
    
    return $tempFilePath;
}




// Database operations encapsulated in a function
$responseData = fetchDataFromDatabase($deduction, $period,$code,$conn);

// Generate Excel file

$filePath = generateExcelFile($responseData, $deduction_text, $period_text);



function getRecipientEmail($conn, $deduction) {
    // Prepare the SQL statement
    $query = $conn->prepare('SELECT * FROM email_deductionlist WHERE allow_id = ?');
    // Execute the query with the provided deduction id
    $query->execute([$deduction]);
    // Fetch the result
    $existTrans = $query->fetch(PDO::FETCH_ASSOC);

    // Return the email address if it exists, otherwise return a default or an empty string
    return [
        'email' => $existTrans ? $existTrans['email'] : '',
        'email_cc' => $existTrans && isset($existTrans['bcc']) ? $existTrans['bcc'] : ''
    ];
}

$recipientEmail = getRecipientEmail($conn, $deduction);

function sendEmail($filePath, $deductionText, $periodText, $recipientEmail, $ccEmail = '') {
    $mail = new PHPMailer(true); // Passing `true` enables exceptions
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'mail.oouthsalary.com.ng';
        $mail->SMTPAuth = true;
        $mail->Username = 'salary@oouthsalary.com.ng';
        $mail->Password = 'b07NwW3_5WNr';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        
        // Recipients
        $mail->setFrom('no-reply@oouth.com', 'OOUTHSALARY');
        $mail->addAddress($recipientEmail); // Add a recipient
        $mail->addReplyTo("no-reply@oouth.com", "OOUTHSALARY");
        if (!empty($ccEmail)) {
        $mail->addCC($ccEmail); // Add CC recipient if provided
            }
        // Attachments
        $mail->addAttachment($filePath); // Add attachments
        
        // Content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = "OOUTH  {$deductionText}  List";
        $mail->Body    = "Here is the report for {$deductionText} for the period of {$periodText}.";
        
        $mail->send();
        echo 'Message has been sent';
    } catch (Exception $e) {
        echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
    } finally {
        if (file_exists($filePath)) {
            unlink($filePath); // Clean up the temporary file
        }
    }
}


function downloadExcelFile($filePath, $fileName) {
    if (file_exists($filePath)) {
        if (headers_sent($filename, $linenum)) {
            echo "Headers already sent in $filename on line $linenum";
            exit;
        }

        // Now turn off error reporting to prevent corrupting file content
        error_reporting(0);
        ini_set('display_errors', 0);

        // Clear the output buffer
        ob_end_clean();

        // Send download headers
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));

        // Clear the output buffer again and flush all output buffers
        ob_clean();
        flush();

        // Read the file and send it to the output buffer
        readfile($filePath);

        // Delete the file after download
        unlink($filePath);

        // Terminate the script to prevent further output
        exit;
    } else {
        echo "The file does not exist.";
    }
}



if ($recipientEmail) {
    // Proceed with sending the email
sendEmail($filePath, $deduction_text, $period_text, $recipientEmail['email'], $recipientEmail['email_cc']);

} else {
   $fileName = basename($filePath);
    downloadExcelFile($filePath, $fileName);
}


//     $Data['S/No.']  = '';
//     $Data['StaffNo']  = '';
//     $Data['Name'] = 'TOTAL';
//     $Data['Amount']  = ($sumTotal);
//     if ($deduction == 87 || $deduction == 85) {
//         $loan = retrieveLoanStatus($link['staff_id'], $deduction);
//         $repayment = retrieveLoanBalanceStatus($link['staff_id'], $deduction, $period);
//         $Data['Balance'] = '';
//     }
//     array_push($response['data'], $Data);
// } catch (PDOException $e) {
//     echo $e->getMessage();
// }

?>