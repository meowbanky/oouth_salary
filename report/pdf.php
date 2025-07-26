<?php
set_time_limit(300);
require_once('../../config.php');
//require_once 'tcpdf/tcpdf.php';
require __DIR__.'/../vendor/tecnickcom/tcpdf/tcpdf.php';
require __DIR__.'/../vendor/autoload.php';

if (!defined('K_PATH_IMAGES')) {
    define('K_PATH_IMAGES', '/report/');
}

try {
    $conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}

function getPayPeriod($conn, $period)
{
    $query = $conn->prepare('SELECT description, periodYear FROM payperiods WHERE periodId = ?');
    $query->execute(array($period));
    $out = $query->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($out)) {
        $row = array_shift($out); // Assuming there's only one row per periodId
        $fullPeriod = $row['description'] . '-' . $row['periodYear'];
        return $fullPeriod;
    }

    return ''; // Return an empty string or handle as needed if the period is not found
}


//$employeeId = 1140; // Example employee ID
//$period = 18; // Example period


function generateAndSendPayslip($employeeId, $period)
{
    $conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $employeeDetails = fetchEmployeeDetails($conn, $employeeId, $period);
    $payslipDetails = fetchPayslipDetails($conn, $employeeId, $period);
    $fullPeriod = getPayPeriod($conn, $period);
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    configurePDF($pdf);
    $htmlContent = generatePayslipHtml($employeeDetails, $payslipDetails, $fullPeriod);
    $pdf->writeHTMLCell(0, 0, '', '', $htmlContent, 0, 1, 0, true, '', true);

    $pdfOutput = $pdf->Output('', 'S');

    $emailResult = sendPayslipEmail($employeeDetails, $pdfOutput, $period, $fullPeriod);

    return $emailResult;
}

function configurePDF($pdf)
{


    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('SALARY UNIT');
    $pdf->SetTitle('OOUTH PAYSLIP');
    $pdf->SetSubject('Pay Slip');
    $pdf->SetKeywords('OOUTH, payslip, Sagamu');
    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 009', PDF_HEADER_STRING);

    $pdf->setHeaderData('oouth_logo.png', 10, 'Olabisi Onabanjo University Teaching Hospital', 'Generated on ' . date('d-m-Y:H:s'), array(0, 64, 255), array(0, 64, 128));
    $pdf->setFooterData(array(0, 64, 0), array(0, 64, 128));
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

    $pdf->setFooterData(array(0, 64, 0), array(0, 64, 128));
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->AddPage();
}

function fetchEmployeeDetails($conn, $employeeId, $period)
{
    $sql = "SELECT
	tbl_bank.BNAME as bank, 
	tbl_dept.dept as department, 
	ifnull(master_staff.STEP,'') as STEP, 
	ifnull(master_staff.GRADE,'') as grade_level, 
	master_staff.staff_id, 
	master_staff.`NAME` as employee_name, 
	master_staff.ACCTNO as account_number, 
	employee.EMAIL
FROM
	master_staff
	INNER JOIN
	tbl_dept
	ON 
		tbl_dept.dept_id = master_staff.DEPTCD
	INNER JOIN
	tbl_bank
	ON 
		tbl_bank.BCODE = master_staff.BCODE
	INNER JOIN
	employee
	ON 
		master_staff.staff_id = employee.staff_id WHERE master_staff.staff_id = :employeeId AND period = :period";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['employeeId' => $employeeId, 'period' => $period]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


function fetchPayslipDetails($conn, $employeeId, $period)
{
    // Initialize an array to hold payslip details
    $payslipDetails = [
        'consolidated' => 0,
        'allowances' => [],
        'deductions' => [],
        'netPay' => 0
    ];

    // Fetch Consolidated Salary
    $sqlConsolidated = "SELECT allow FROM tbl_master WHERE staff_id = :employeeId AND period = :period AND allow_id = '1'";
    // Fetch Allowances
    $sqlAllowances = "SELECT tbl_master.staff_id, tbl_master.allow, tbl_earning_deduction.ed FROM tbl_master INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id WHERE staff_id = :employeeId AND period = :period AND allow_id <> '1' AND type = '1'";
    // Fetch Deductions
    $sqlDeductions = "SELECT tbl_master.staff_id, tbl_master.deduc, tbl_earning_deduction.ed FROM tbl_master INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id WHERE staff_id = :employeeId AND period = :period AND type = '2'";

    try {
        // Consolidated Salary
        $stmt = $conn->prepare($sqlConsolidated);
        $stmt->execute(['employeeId' => $employeeId, 'period' => $period]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $payslipDetails['consolidated'] = $result ? $result['allow'] : 0;

        // Allowances
        $stmt = $conn->prepare($sqlAllowances);
        $stmt->execute(['employeeId' => $employeeId, 'period' => $period]);
        $payslipDetails['allowances'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Deductions
        $stmt = $conn->prepare($sqlDeductions);
        $stmt->execute(['employeeId' => $employeeId, 'period' => $period]);
        $payslipDetails['deductions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate Net Pay
        $totalAllowances = array_sum(array_column($payslipDetails['allowances'], 'allow'));
        $totalDeductions = array_sum(array_column($payslipDetails['deductions'], 'deduc'));
        $payslipDetails['netPay'] = $payslipDetails['consolidated'] + $totalAllowances - $totalDeductions;
        //Calculate Gross
        $payslipDetails['grosspay'] = $totalAllowances + $payslipDetails['consolidated'];
        $payslipDetails['grossDeduction'] = $totalDeductions;

        return $payslipDetails;
    } catch (PDOException $e) {
        throw new Exception("Database error while fetching payslip details: " . $e->getMessage());
    }
}


function generatePayslipHtml($employeeDetails, $payslipDetails, $fullPeriod)
{
    // Start buffering the output
    ob_start();
?>

    <style>
        .header {
            background-color: #D9EAD3;
            text-align: center;
            font-weight: bold;
        }

        .section-header {
            background-color: #4F6228;
            color: #FFFFFF;
            font-weight: bold;
        }

        .totals-row {
            font-weight: bold;
        }

        .details-table,
        .totals-table {
            border-collapse: collapse;
            width: 50%;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .details-table td,
        .totals-table td {
            border: 1px solid #000000;
            padding: 6px;
        }

        .details-table th,
        .totals-table th {
            border: 1px solid #000000;
            padding: 6px;
            background-color: #D9EAD3;
        }

        .right {
            text-align: right;
            margin-right: 1em;
        }

        .left {
            text-align: left;
            margin-left: 1em;
        }
    </style>
    <table class="details-table">
        <tr class="header">
            <th colspan="2">OOUTH, SAGAMU PAYSLIP FOR <?php echo $fullPeriod; ?></th>
        </tr>
        <tr>
            <td>Name:</td>
            <td><?php echo htmlspecialchars($employeeDetails['employee_name']); ?></td>
        </tr>
        <tr>
            <td>Staff No.:</td>
            <td><?php echo htmlspecialchars($employeeDetails['staff_id']); ?></td>
        </tr>
        <tr>
            <td>Dept:</td>
            <td><?php echo htmlspecialchars($employeeDetails['department']); ?></td>
        </tr>
        <tr>
            <td>Bank:</td>
            <td><?php echo htmlspecialchars($employeeDetails['bank']); ?></td>
        </tr>
        <tr>
            <td>Acct No.:</td>
            <td><?php echo htmlspecialchars($employeeDetails['account_number']); ?></td>
        </tr>
        <tr>
           <?php if (preg_match('/[a-zA-Z]/', $employeeDetails['grade_level'])) {
            $salaryType = "CONMESS";
            } else {
               $salaryType = "CONHESS";
            }
            ?>
            <td><?php echo $salaryType; ?>:</td>
            <td><?php echo htmlspecialchars($employeeDetails['grade_level']); ?>/<?php echo htmlspecialchars($employeeDetails['STEP']); ?></td>
        </tr>
    </table>

    <table class="totals-table">
        <tr class="section-header">
            <th colspan="2">CONSOLIDATED SALARY</th>
        </tr>
        <tr>
            <td>CONSOLIDATED SALARY:</td>
            <td class="right"><?php echo number_format($payslipDetails['consolidated'], 2); ?></td>
        </tr>
        <tr class="section-header">
            <th colspan="2">ALLOWANCES</th>
        </tr>
        <!-- Repeat for each allowance -->
        <?php
        if (!empty($payslipDetails['allowances'])) {
            foreach ($payslipDetails['allowances'] as $allowance) {
        ?>
                <tr>
                    <td><?php echo htmlspecialchars($allowance['ed']); ?></td>
                    <td class="right"><?php echo number_format($allowance['allow'], 2); ?></td>
                </tr>
        <?php }
        }
        ?>




        <tr class="totals-row">
            <td>Gross Salary</td>
            <td class="right"><?php echo number_format($payslipDetails['grosspay'], 2); ?></td>
        </tr>
    </table>

    <table class="totals-table">
        <tr class="section-header">
            <th colspan="2">Deductions</th>
        </tr>
        <!-- Repeat for each deduction -->
        <?php if (!empty($payslipDetails['deductions'])) {
            foreach ($payslipDetails['deductions'] as $deduction) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($deduction['ed']); ?></td>
                    <td class="right"><?php echo number_format($deduction['deduc'], 2); ?></td>
                </tr>
        <?php }
        } ?>
        <tr class="totals-row">
            <td>Total Deductions</td>
            <td class="right"><?php echo number_format($payslipDetails['grossDeduction'], 2); ?></td>
        </tr>


        <tr class="totals-row">
            <td>Net Pay</td>
            <td class="right"><?php echo number_format($payslipDetails['netPay'], 2); ?></td>
        </tr>
    </table>
<?php
    // Return the output buffer
    $html = ob_get_clean();
    return $html;
}


function sendPayslipEmail($employeeDetails, $pdfOutput, $period, $fullPeriod)
{

    $mail = new PHPMailer\PHPMailer\PHPMailer();
    //$mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port = SMTP_PORT;
//    $mail->SMTPDebug = SMT_SMTPDebug;
    $mail->SMTPDebug = 3;

    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->addAddress($employeeDetails['EMAIL'], $employeeDetails['employee_name']);
    $mail->addReplyTo(SMTP_REPLYTO_EMAIL, SMTP_REPLYTO_NAME);

    $mail->isHTML(true);
    $mail->Subject = $employeeDetails['employee_name'] . ' ' . $fullPeriod . ' Pay Slip';
    $mail->Body = '<html><body>';
    $mail->Body .= '<div style="padding-left:0;padding-bottom:15px;padding-right:0;padding-top:0;font-weight:normal;font-size:14px;line-height:18px;color:#808080"><img src="https://oouth.com/images/logo.png">';
    $mail->Body .= '<table width="100%" cellspacing="0" cellpadding="0" border="0" align="left" style="font-weight:normal;font-family:Arial,Helvetica,sans-serif;margin-top:0;margin-right:0;margin-bottom:0;margin-left:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;background-color:#ffffff" bgcolor="#ffffff">
                    <tbody><tr><td style="padding-left:0;padding-bottom:10px;padding-right:0;padding-top:35px;border-top-color:#eeeeee;border-top-width:1px;border-top-style:solid;font-size:18px;line-height:25px;color:#356ae9">Payslip</td></tr>
                    <tr><td style="padding-left:0;padding-bottom:15px;padding-right:0;padding-top:0;font-weight:normal;font-size:14px;line-height:18px;color:#808080">
                    <p>Hi <strong>' . htmlspecialchars($employeeDetails['employee_name']) . '</strong>,<p>  Please find attached your payslip for the month of <strong>' . $fullPeriod . '.</strong> We hope that you find the information in the payslip accurate and helpful.</p>
                    Please review your payslip and let us know if you have any questions or concerns. If you believe there is an error, please contact the Finance & Account department immediately so we can resolve the issue.
                    <p>Thank you for your hard work and dedication.</p>
                    </td></tr></tbody></table>';
    $mail->Body .= '</div></body></html>';
    $mail->AltBody = 'Attached is your payslip for the month of ' . $period;
    $filename = 'payslip-' . $fullPeriod . '.pdf'; // Construct a filename for the PDF attachment
    $mail->addStringAttachment($pdfOutput, $filename, 'base64', 'application/pdf');

    try {
        $mail->send();
        return 'Payslip email sent successfully to ' . $employeeDetails['EMAIL'];
    } catch (Exception $e) {
        return "Mailer Error: " . $mail->ErrorInfo;
    }
}


// Example usage

//echo generateAndSendPayslip($employeeId, $period);

//$employeeDetails = fetchEmployeeDetails($conn, $employeeId, $period);

//var_dump($employeeDetails);
// echo $logoPath = K_PATH_IMAGES . 'oouth_logo.png';
?>