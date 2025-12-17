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



//use TCPDF;

// Check for session
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
    header("HTTP/1.1 401 Unauthorized");
    exit('Unauthorized access');
}

// Retrieve POST parameters
$period = filter_input(INPUT_POST, 'period', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: -1;
$bank = filter_input(INPUT_POST, 'bank', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: -1;
$period_text = filter_input(INPUT_POST, 'period_text', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 'Unknown Period';

// Define date variable for footer
$Today = date('y:m:d', time());
$new = date('l, F d, Y', strtotime($Today));

// Fetch bank name for the title
$bankName = '';
try {
    $query = $conn->prepare('SELECT tbl_bank.BNAME FROM tbl_bank WHERE BCODE = ?');
    $query->execute(array($bank));
    $out = $query->fetchAll(PDO::FETCH_ASSOC);
    while ($row = array_shift($out)) {
        $bankName = $row['BNAME'];
    }
} catch (PDOException $e) {
    die($e->getMessage());
}

// Initialize TCPDF
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor($_SESSION['SESS_FIRST_NAME']);
$pdf->SetTitle('Netpay to Bank Report');
$pdf->SetSubject('Detailed Netpay to Bank Report for ' . $bankName);

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 10);

// Add custom header
$header = <<<EOD
<table width="100%" border="0">
    <tr>
        <td align="center">
            <img src="../img/oouth_logo.gif" width="50" height="50" />
        </td>
    </tr>
    <tr>
        <td align="center"><b>OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL</b></td>
    </tr>
    <tr>
        <td align="center">Bank Report for {$bankName} for the Month of: {$period_text}</td>
    </tr>
</table>
EOD;
$pdf->writeHTML($header, true, false, true, false, '');

// Add table headers
$html = <<<EOD
<table border="1" cellpadding="4">
    <thead>
        <tr style="background-color:#d3d3d3;">
            <th width="5%"><b>S/No</b></th>
            <th width="10%"><b>Staff No.</b></th>
            <th width="30%"><b>Name</b></th>
            <th width="15%"><b>Acct No.</b></th>
            <th width="25%"><b>Bank</b></th>
            <th width="15%" align="right"><b>Net Pay</b></th>
        </tr>
    </thead>
    <tbody>
EOD;

// Fetch data
try {
    if ($bank != 'All') {
        $sql = 'SELECT any_value(tbl_master.staff_id) as staff_id, any_value(Sum(tbl_master.allow)) as allow, any_value(Sum(tbl_master.deduc)) as deduc, any_value((Sum(tbl_master.allow)- Sum(tbl_master.deduc))) AS net, any_value(master_staff.`NAME`) as `NAME`, any_value(tbl_bank.BNAME) as BNAME,
                ANY_VALUE(master_staff.BCODE) AS BCODE, ANY_VALUE(master_staff.ACCTNO) AS ACCTNO 
                FROM tbl_master 
                INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id 
                INNER JOIN tbl_bank ON tbl_bank.BCODE = master_staff.BCODE 
                WHERE tbl_master.period = ? and master_staff.period = ? and master_staff.BCODE = ? 
                GROUP BY tbl_master.staff_id';
        $query = $conn->prepare($sql);
        $query->execute(array($period, $period, $bank));
    } else {
        $sql = 'SELECT any_value(tbl_master.staff_id) as staff_id, any_value(Sum(tbl_master.allow)) as allow, any_value(Sum(tbl_master.deduc)) as deduc, any_value((Sum(tbl_master.allow)- Sum(tbl_master.deduc))) AS net, any_value(master_staff.`NAME`) as `NAME`, any_value(tbl_bank.BNAME) as BNAME,
                ANY_VALUE(master_staff.BCODE) AS BCODE, ANY_VALUE(master_staff.ACCTNO) AS ACCTNO 
                FROM tbl_master 
                INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id 
                INNER JOIN tbl_bank ON tbl_bank.BCODE = master_staff.BCODE 
                WHERE tbl_master.period = ? and master_staff.period = ?  
                GROUP BY tbl_master.staff_id 
                ORDER BY BCODE';
        $query = $conn->prepare($sql);
        $query->execute(array($period, $period));
    }

    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $sumTotal = 0;
    $i = 1;

    foreach ($res as $row) {
        $netPay = number_format($row['net'], 2);
        $html .= <<<EOD
        <tr>
            <td width="5%">$i</td>
            <td width="10%">{$row['staff_id']}</td>
            <td width="30%">{$row['NAME']}</td>
            <td width="15%">{$row['ACCTNO']}</td>
            <td width="25%">{$row['BNAME']}</td>
            <td width="15%" align="right">{$netPay}</td>
        </tr>
EOD;
        $sumTotal += floatval($row['net']);
        $i++;
    }

    // Add total row
    $sumTotalFormatted = number_format($sumTotal, 2);
    $html .= <<<EOD
    <tr>
        <td width="85%" colspan="5"><b>TOTAL</b></td>
        <td width="15%" align="right"><b>{$sumTotalFormatted}</b></td>
    </tr>
EOD;

} catch (PDOException $e) {
    die($e->getMessage());
}

$html .= '</tbody></table>';

// Write the table to PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Add footer
$footer = <<<EOD
<table width="100%" border="0">
    <tr>
        <td align="left">Report Generated by: {$_SESSION['SESS_FIRST_NAME']} on {$new}</td>
    </tr>
</table>
EOD;
$pdf->writeHTML($footer, true, false, true, false, '');

// Output PDF
$pdf->Output('Netpay_to_Bank_Report_' . $period_text . '.pdf', 'D');
?>