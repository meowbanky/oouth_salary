<?php
session_start();
require_once('../Connections/paymaster.php');
require __DIR__.'/../vendor/autoload.php';
$tcpdf_path = __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';

if (!file_exists($tcpdf_path)) {
    error_log('TCPDF file not found at: ' . $tcpdf_path);
    header('HTTP/1.1 500 Internal Server Error');
    exit('TCPDF library not found');
}
require_once $tcpdf_path;

if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) == '') {
    header("HTTP/1.1 401 Unauthorized");
    exit('Unauthorized access');
}

// Retrieve POST parameters
$period = filter_input(INPUT_POST, 'period', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: -1;
$month = filter_input(INPUT_POST, 'month', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 'Unknown Period';

// Initialize TCPDF
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor($_SESSION['SESS_FIRST_NAME']);
$pdf->SetTitle('Employee Report');
$pdf->SetSubject('Detailed Employee Report for ' . $month);

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 10);

// Add logos and header
$logoLeft = __DIR__ . '/../img/ogun_logo.png';
$logoRight = __DIR__ . '/../img/oouth_logo.png';
$logoWidth = 25;
$logoHeight = 25;
$topMargin = 10;
$leftLogoX = 15;
$rightLogoX = $pdf->getPageWidth() - $leftLogoX - $logoWidth;

if (file_exists($logoLeft)) {
    $pdf->Image($logoLeft, $leftLogoX, $topMargin, $logoWidth, $logoHeight, 'PNG', '', 'T', false, 300, '', false, false, 0);
} else {
    error_log('Left logo file missing: ' . $logoLeft);
}
if (file_exists($logoRight)) {
    $pdf->Image($logoRight, $rightLogoX, $topMargin, $logoWidth, $logoHeight, 'PNG', '', 'T', false, 300, '', false, false, 0);
} else {
    error_log('Right logo file missing: ' . $logoRight);
}

$headerText1 = 'OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL';
$headerText2 = 'Employee Report for the Month of: ' . $month;
$textStartX = $leftLogoX + $logoWidth + 5;
$textWidth = $rightLogoX - $textStartX - 5;

$pdf->SetY($topMargin + 20);
$pdf->SetX($textStartX);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->MultiCell($textWidth, 0, $headerText1, 0, 'C', false, 1, '', '', true, 0, false, true, 0);
$pdf->SetX($textStartX);
$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell($textWidth, 0, $headerText2, 0, 'C', false, 1, '', '', true, 0, false, true, 0);

// Add table headers
// Add table headers
$html = <<<EOD
<table border="1" cellpadding="4" style="margin-top: 15mm; width: 277mm;">
    <thead>
        <tr style="background-color:#d3d3d3;">
            <th width="10mm"><b>S/No</b></th>
            <th width="14mm"><b>Staff No.</b></th>
            <th width="55mm"><b>Name</b></th>
            <th width="42mm"><b>Email</b></th>
            <th width="28mm"><b>Dept</b></th>
            <th width="20mm"><b>Emp Date</b></th>
            <th width="28mm"><b>Post</b></th>
            <th width="14mm"><b>Grade</b></th>
            <th width="14mm"><b>Step</b></th>
            <th width="28mm"><b>Bank</b></th>
            <th width="28mm"><b>Acct. No.</b></th>
        </tr>
    </thead>
    <tbody>
EOD;

// Fetch data
try {
    $sql = 'SELECT master_staff.staff_id, master_staff.`NAME`, tbl_dept.dept, master_staff.GRADE, master_staff.STEP, 
            tbl_bank.BNAME, master_staff.ACCTNO, employee.EMPDATE, employee.EMAIL, employee.POST
            FROM master_staff
            INNER JOIN tbl_dept ON master_staff.DEPTCD = tbl_dept.dept_id
            INNER JOIN tbl_bank ON master_staff.BCODE = tbl_bank.BCODE
            INNER JOIN employee ON master_staff.staff_id = employee.staff_id 
            WHERE master_staff.period = ?';
    $query = $conn->prepare($sql);
    $query->execute([$period]);
    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $counter = 1;

    foreach ($res as $link) {
        // Sanitize all fields to handle null values
        $staff_id = htmlspecialchars($link['staff_id'] ?? '');
        $name = htmlspecialchars($link['NAME'] ?? '');
        $email = htmlspecialchars($link['EMAIL'] ?? '');
        $dept = htmlspecialchars($link['dept'] ?? '');
        $emp_date = htmlspecialchars($link['EMPDATE'] ?? '');
        $post = htmlspecialchars($link['POST'] ?? '');
        $grade = htmlspecialchars($link['GRADE'] ?? '');
        $step = htmlspecialchars($link['STEP'] ?? '');
        $bank = htmlspecialchars($link['BNAME'] ?? '');
        $acct_no = htmlspecialchars($link['ACCTNO'] ?? '');

        $html .= <<<EOD
        <tr>
            <td width="10mm">$counter</td>
            <td width="14mm">$staff_id</td>
            <td width="55mm" style="word-wrap: break-word;">$name</td>
            <td width="42mm" style="word-wrap: break-word;">$email</td>
            <td width="28mm">$dept</td>
            <td width="20mm">$emp_date</td>
            <td width="28mm">$post</td>
            <td width="14mm">$grade</td>
            <td width="14mm">$step</td>
            <td width="28mm">$bank</td>
            <td width="28mm" style="word-wrap: break-word;">$acct_no</td>
        </tr>
EOD;
        $counter++;
    }
} catch (PDOException $e) {
    $html .= '<tr><td colspan="11">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
}

$html .= '</tbody></table>';

// Write the table to PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Add footer with printed date
$printedDate = date('l, F d, Y'); // Current date, e.g., "Saturday, May 10, 2025"
$footer = <<<EOD
<table width="100%" border="0" style="margin-top: 10mm;">
    <tr>
        <td align="left">Report Generated by: {$_SESSION['SESS_FIRST_NAME']} | Printed on: $printedDate</td>
    </tr>
</table>
EOD;
$pdf->writeHTML($footer, true, false, true, false, '');

// Output PDF
$pdf->Output('Employee_Report_' . $month . '.pdf', 'D');
?>