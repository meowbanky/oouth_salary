<?php
session_start();
ini_set('max_execution_time', '0');
// if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
//     header("location: ../index.php");
//     exit();
// }

require_once('../classes/model.php'); // Assuming this file contains your database connection logic
require_once('Connections/paymaster.php');
require 'office_vendor/autoload.php';  // Include PhpSpreadsheet for Excel export
require 'vendor/autoload.php';  // Include PhpSpreadsheet for Excel export
require_once '../../config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;


$periods = getPayRollPeriodDetails($conn);


$period = $periods['periodid'];
$period_text =  $periods['description'];


// Form handling and validation
$periodFrom = isset($period) ? (int)$period : null;
$periodTo = isset($period) ? (int)$period : null;

// Input validation
if (!$periodFrom || !$periodTo) {
    die("Please select both pay periods.");
}



function getPayRollPeriodDetails($conn)
{
    // Prepare the SQL statement
    $query = $conn->prepare(
        "SELECT
    periodId,
    CONCAT(description,' - ', periodYear) as description
    FROM payperiods WHERE
    periodid = (
        SELECT
            MAX(periodid)
        FROM
            payperiods WHERE openview = 0)"
    );
    // Execute the query with the provided deduction id
    $query->execute();
    // Fetch the result
    $existTrans = $query->fetch(PDO::FETCH_ASSOC);

    //Return the email address if it exists, otherwise return a default or an empty string
    return [
        'periodid' => $existTrans ? $existTrans['periodId'] : '',
        'description' => $existTrans && isset($existTrans['description']) ? $existTrans['description'] : ''
    ];
}



function exportToExcel($reportData, $edData, $recipientEmail, $period_text)
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set headers (efficiently)
    $headerRow = ['STAFF NO', 'NAME', 'PAY PERIOD', 'DEPT'];
    foreach ($edData as $ed) {
        $headerRow[] = $ed['ed'];
    }
    $headerRow = array_merge($headerRow, ['TOTAL ALLOW', 'TOTAL DEDUC', 'NET PAY']);
    $sheet->fromArray($headerRow, NULL, 'A1');

    // Populate data (efficiently)
    $row = 2;
    foreach ($reportData as $staff) {
        $rowData = [
            $staff['staff_id'],
            $staff['name'],
            $period_text,
            $staff['dept']
        ];

        foreach ($edData as $ed) {
            $value = $staff['earnings'][$ed['ed']] ?? $staff['deductions'][$ed['ed']] ?? 0;
            $rowData[] = $value;
        }

        $rowData = array_merge($rowData, [
            $staff['total_allow'],
            $staff['total_deduc'],
            $staff['net_pay']
        ]);

        $sheet->fromArray($rowData, NULL, 'A' . $row);
        $row++;
    }

    // Styling (optional)
    // ... your styling code ...
    $filename = "payroll_summary_" . $period_text . ".xlsx";
    // Set appropriate headers for Excel download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    // Write to output
    // $writer = new Xlsx($spreadsheet);
    // $writer->save('php://output');


    // Save the spreadsheet to a temporary file
    $tempFile = tempnam(sys_get_temp_dir(), $filename); // Create a temporary file
    $writer = new Xlsx($spreadsheet);
    $writer->save($tempFile);

    // Send email with attachment
    $mail = new PHPMailer(true); // Enable exceptions

    try {
        //Server settings

        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; //$mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        $mail->SMTPDebug = SMT_SMTPDebug;

        $mail->setFrom(
            SMTP_FROM_EMAIL,
            SMTP_FROM_NAME
        );
        $mail->addReplyTo(SMTP_REPLYTO_EMAIL, SMTP_REPLYTO_NAME);
        $mail->addAddress($recipientEmail);
        $mail->addBcc('bankole.adesoji@gmail.com');
        $mail->isHTML(true);


        $mail->Subject = 'Payroll Summary Report for the Month of ' . $period_text;
        $mail->Body    =
            'Please find the attached payroll summary report for the Month of ' . $period_text;
        $mail->addAttachment($tempFile, $filename); // Add the Excel file as an attachment

        $mail->send();
        echo 'Message has been sent';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }

    // Delete the temporary file
    unlink($tempFile);
    exit;
}


function retrieveDept($deptCode, $conn)
{
    // Query to fetch the department name based on the department code
    $query = $conn->prepare("SELECT dept FROM tbl_dept WHERE dept_id = ?");
    $query->execute([$deptCode]);

    // Fetch the result
    $dept = $query->fetch(PDO::FETCH_ASSOC);

    // Return the department name if found, otherwise return a default value (e.g., 'Unknown')
    return $dept ? $dept['dept'] : 'Unknown Department';
}

try {
    // SQL query to fetch staff data and earning/deduction types
    $sql = "SELECT staff_id, ANY_VALUE(NAME) AS NAME, ANY_VALUE(DEPTCD) AS DEPTCD, ANY_VALUE(period) AS period,ANY_VALUE(dept) AS dept
            FROM master_staff LEFT JOIN tbl_dept ON master_staff.DEPTCD = tbl_dept.dept_id
            WHERE period BETWEEN ? AND ? 
            GROUP BY staff_id 
            ORDER BY staff_id,DEPTCD
            LIMIT 0,1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$periodFrom, $periodTo]);
    $staffData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $edQuery = "SELECT ed_id, ed, edType FROM tbl_earning_deduction";
    $stmt = $conn->prepare($edQuery);
    $stmt->execute();
    $edData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare data for the template
    $reportData = [];
    foreach ($staffData as $staff) {
        $staffReport = [
            'staff_id' => $staff['staff_id'],
            'name' => $staff['NAME'],
            'period' => $staff['period'],
            'dept' => $staff['dept'],
            'earnings' => [],
            'deductions' => [],
            'total_allow' => 0,
            'total_deduc' => 0,
            'net_pay' => 0
        ];

        foreach ($edData as $ed) {
            $value = retrievePayroll($periodFrom, $periodTo, $staff['staff_id'], $ed['ed_id'], $conn);
            if ($ed['edType'] == 1) {
                $staffReport['earnings'][$ed['ed']] = $value;
                $staffReport['total_allow'] += $value;
            } else {
                $staffReport['deductions'][$ed['ed']] = $value;
                $staffReport['total_deduc'] += $value;
            }
        }
        $staffReport['net_pay'] = $staffReport['total_allow'] - $staffReport['total_deduc'];
        $reportData[] = $staffReport;
    }

    // Check if Excel export is requested
    if (isset($_GET['export']) && $_GET['export'] === 'email') {
        $recipientEmail = 'bankole.adesoji@gmail.com'; // Get the recipient's email address
        exportToExcel($reportData, $edData, $recipientEmail, $period_text);
    } else {
        // Include the template to render the HTML report
        include 'report_template.php';
    }
} catch (PDOException $e) {
    echo ($e->getMessage());
    die("Database error. Please try again.");
}
