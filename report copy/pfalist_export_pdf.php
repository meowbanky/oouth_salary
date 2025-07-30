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

if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) == '') {
    header("HTTP/1.1 401 Unauthorized");
    exit('Unauthorized access');
}

// Retrieve POST parameters
$period = filter_input(INPUT_POST, 'period', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: -1;
$pfa = filter_input(INPUT_POST, 'pfa', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: -1;
$period_text = filter_input(INPUT_POST, 'period_text', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 'Unknown Period';

// Fetch PFA name for the title
$pfaName = '';
try {
    $query = $conn->prepare('SELECT PFANAME FROM tbl_pfa WHERE PFACODE = ?');
    $query->execute([$pfa]);
    $out = $query->fetch(PDO::FETCH_ASSOC);
    $pfaName = $out['PFANAME'] ?? 'All PFA';
} catch (PDOException $e) {
    die($e->getMessage());
}

// Initialize TCPDF
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor($_SESSION['SESS_FIRST_NAME']);
$pdf->SetTitle('Pension Funds Report');
$pdf->SetSubject('Detailed Pension Funds Report for ' . $pfaName);

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 10);

// Define logo dimensions and positions
$logoLeft = __DIR__ . '/../img/ogun_logo.png';
$logoRight = __DIR__ . '/../img/oouth_logo.png';
$logoWidth = 25;
$logoHeight = 25;
$topMargin = 10;
$leftLogoX = 15;  // Left logo position
$rightLogoX = $pdf->getPageWidth() - $leftLogoX - $logoWidth;  // Right logo position

// Add logos using $pdf->Image()
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

// Add header text centered between the logos
$headerText1 = 'OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL';
$headerText2 = $pfaName . ' Pension Report for the Month of: ' . $period_text;

// Calculate the width available for the text (between logos)
$textStartX = $leftLogoX + $logoWidth + 5; // Start after left logo with a small gap
$textWidth = $rightLogoX - $textStartX - 5; // End before right logo with a small gap

// Position the cursor for the text
$pdf->SetY($topMargin + 20); // Align text vertically with the logos (slightly below top edge of logos)
$pdf->SetX($textStartX);

// Write the first line of header text (bold)
$pdf->SetFont('helvetica', 'B', 10);
$pdf->MultiCell($textWidth, 0, $headerText1, 0, 'C', false, 1, '', '', true, 0, false, true, 0);

// Write the second line of header text (normal)
$pdf->SetX($textStartX);
$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell($textWidth, 0, $headerText2, 0, 'C', false, 1, '', '', true, 0, false, true, 0);

// Write the second line of header text (normal)
$pdf->SetX($textStartX);
$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell($textWidth, 0, '', 0, 'C', false, 1, '', '', true, 0, false, true, 0);


// Reset font for the table
$pdf->SetFont('helvetica', '', 10);

// Add table headers with spacing below the header
$html = <<<EOD
<table border="1" cellpadding="4" style="margin-top: 15mm;">
    <thead>
        <tr style="background-color:#d3d3d3;">
            <th width="5%"><b>S/No.</b></th>
            <th width="10%"><b>Staff No.</b></th>
            <th width="30%"><b>Name</b></th>
            <th width="20%"><b>PFA</b></th>
            <th width="20%"><b>PIN</b></th>
            <th width="15%" align="right"><b>Amount</b></th>
        </tr>
    </thead>
    <tbody>
EOD;

// Fetch data
try {
    $sql = $pfa != -1
        ? 'SELECT tbl_master.deduc, master_staff.staff_id, master_staff.`NAME`, master_staff.PFAACCTNO, tbl_pfa.PFANAME 
           FROM tbl_master 
           INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id 
           INNER JOIN tbl_pfa ON master_staff.PFACODE = tbl_pfa.PFACODE 
           WHERE tbl_master.allow_id = ? AND master_staff.period = ? AND master_staff.PFACODE = ? AND tbl_master.period = ? 
           ORDER BY tbl_master.staff_id ASC'
        : 'SELECT tbl_master.deduc, master_staff.staff_id, master_staff.`NAME`, master_staff.PFAACCTNO, tbl_pfa.PFANAME 
           FROM tbl_master 
           INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id 
           INNER JOIN tbl_pfa ON master_staff.PFACODE = tbl_pfa.PFACODE 
           WHERE tbl_master.allow_id = ? AND master_staff.period = ? AND tbl_master.period = ? 
           ORDER BY tbl_master.staff_id ASC';

    $query = $conn->prepare($sql);
    $params = $pfa != -1 ? ['50', $period, $pfa, $period] : ['50', $period, $period];
    $query->execute($params);

    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $sumTotal = 0;
    $counter = 1;

    foreach ($res as $row) {
        $amount = number_format($row['deduc'], 2);
        $html .= <<<EOD
        <tr>
            <td width="5%">$counter</td>
            <td width="10%">{$row['staff_id']}</td>
            <td width="30%">{$row['NAME']}</td>
            <td width="20%">{$row['PFANAME']}</td>
            <td width="20%">{$row['PFAACCTNO']}</td>
            <td width="15%" align="right">{$amount}</td>
        </tr>
EOD;
        $sumTotal += floatval($row['deduc']);
        $counter++;
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

// Add footer with printed date
$printedDate = date('l, F d, Y'); // Current date, e.g., "Saturday, May 10, 2025"
$footer = <<<EOD
<table width="100%" border="0" style="margin-top: 10mm;">
    <tr>
        <td align="left">Report Generated by: {$_SESSION['SESS_FIRST_NAME']} | Printed on: {$printedDate}</td>
    </tr>
</table>
EOD;
$pdf->writeHTML($footer, true, false, true, false, '');

// Output PDF
$pdf->Output('Pension_Funds_Report_' . $period_text . '.pdf', 'D');
?>