<?php
session_start();

require __DIR__.'/../vendor/autoload.php';
$tcpdf_path = __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';
if (!file_exists($tcpdf_path)) {
    error_log('TCPDF file not found at: ' . $tcpdf_path);
    header('HTTP/1.1 500 Internal Server Error');
    exit('TCPDF library not found');
}
require_once $tcpdf_path;

include_once('../classes/model.php');
require_once('../Connections/paymaster.php');

if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) === '')) {
    header("location: ../index.php");
    exit;
}

// Custom TCPDF class to override footer
class CustomTCPDF extends TCPDF {
    public function Footer() {
        $this->SetY(-10);
        $this->SetFont('helvetica', 'I', 8);
        $printedBy = isset($_SESSION['SESS_FIRST_NAME']) ? $_SESSION['SESS_FIRST_NAME'] : 'Unknown User';
        $this->Cell(0, 10, 'Printed By: ' . $printedBy . '  Date Printed: ' . date('Y-m-d H:i:s'), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

$period = filter_input(INPUT_POST, 'period', FILTER_VALIDATE_INT) ?: -1;
$period_text = filter_input(INPUT_POST, 'period_text', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';

try {
    $query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear FROM payperiods WHERE periodId = ?');
    $res = $query->execute(array($period));
    $out = $query->fetchAll(PDO::FETCH_ASSOC);
    if (empty($out)) {
        throw new Exception('No period data found for periodId: ' . $period);
    }
    $period_text = $out[0]['description'] . '-' . $out[0]['periodYear'];
} catch (PDOException $e) {
    echo $e->getMessage();
    exit;
} catch (Exception $e) {
    echo $e->getMessage();
    exit;
}

// Create new CustomTCPDF instance
$pdf = new CustomTCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor(isset($_SESSION['SESS_FIRST_NAME']) ? $_SESSION['SESS_FIRST_NAME'] : 'Unknown User');
$pdf->SetTitle('Bank Summary - ' . $period_text);
$pdf->SetSubject('Bank Summary Report');

// Enable footer, disable header
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(true);

// Set margins
$pdf->SetMargins(15, 20, 15);
$pdf->SetAutoPageBreak(TRUE, 15);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 10);

// Logos
$logoLeft = __DIR__ . '/../img/ogun_logo.png';
$logoRight = __DIR__ . '/../img/oouth_logo.png';

if (file_exists($logoLeft)) {
    $pdf->Image($logoLeft, 15, 10, 25, 25, 'PNG', '', 'T', false, 300, '', false, false, 0);
} else {
    error_log('Left logo file missing: ' . $logoLeft);
}
if (file_exists($logoRight)) {
    $pdf->Image($logoRight, 165, 10, 25, 25, 'PNG', '', 'T', false, 300, '', false, false, 0);
} else {
    error_log('Right logo file missing: ' . $logoRight);
}

// Institution name and title
$pdf->SetY(10);
$pdf->SetFont('helvetica', 'B', 10);
$businessName = "Olabisi Onabanjo University Teaching Hospital,\nSagamu";
$pdf->MultiCell(140, 15, $businessName, 0, 'C', false, 1, 35, null, true, 0, false, true, 15, 'M');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'BANK SUMMARY', 0, 1, 'C');
$pdf->Cell(0, 5, 'Period: ' . $period_text, 0, 1, 'C');
$pdf->Ln(5);

// Table header
$pdf->SetFillColor(200, 200, 200);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(50, 8, 'Bank Name', 1, 0, 'L', 1); // Left-aligned for text
$pdf->Cell(30, 8, 'No. of Employee', 1, 0, 'R', 1); // Right-aligned for numerical data
$pdf->Cell(30, 8, 'Total Netpay', 1, 1, 'R', 1); // Right-aligned as requested

// Data section
$sumTotal = 0;
$countStaff = 0;

try {
    $query = $conn->prepare('SELECT any_value(BNAME) as BNAME, any_value(Sum(tbl_master.allow) - Sum(tbl_master.deduc)) AS net, any_value(tbl_bank.BNAME) as BNAME, any_value(tbl_bank.BCODE) as BCODE FROM tbl_master INNER JOIN employee ON tbl_master.staff_id = employee.staff_id INNER JOIN tbl_bank ON employee.BCODE = tbl_bank.BCODE WHERE period = ? GROUP BY employee.BCODE order by any_value(tbl_bank.BNAME) ASC');
    $fin = $query->execute(array($period));
    $res = $query->fetchAll(PDO::FETCH_ASSOC);

    if ($res) {
        foreach ($res as $link) {
            $query2 = $conn->prepare('SELECT Count(employee.staff_id) as "numb" FROM employee WHERE BCODE = ? AND STATUSCD = ? GROUP BY BCODE');
            $fin2 = $query2->execute(array($link['BCODE'], 'A'));
            $res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
            $numb = 0;
            foreach ($res2 as $link2) {
                $numb = $link2['numb'];
                $countStaff += $numb;
            }

            if ($pdf->GetY() + 6 > $pdf->getPageHeight() - 15) {
                $pdf->AddPage();
                $pdf->SetFillColor(200, 200, 200);
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(50, 8, 'Bank Name', 1, 0, 'L', 1);
                $pdf->Cell(30, 8, 'No. of Employee', 1, 0, 'R', 1);
                $pdf->Cell(30, 8, 'Total Netpay', 1, 1, 'R', 1);
            }
            $pdf->SetFont('helvetica', '', 10);
            $descLines = $pdf->getNumLines($link['BNAME'] ?? '', 50);
            $rowHeight = max(6, 6 * $descLines);
            $pdf->MultiCell(50, $rowHeight, $link['BNAME'], 1, 'L', false, 0); // Left-aligned for bank name
            $pdf->Cell(30, $rowHeight, $numb, 1, 0, 'R'); // Right-aligned for numerical data
            $pdf->Cell(30, $rowHeight, number_format($link['net'], 2), 1, 1, 'R'); // Right-aligned as requested
            $sumTotal += floatval($link['net']);
        }

        // Totals row
        $pdf->SetFont('helvetica', 'B', 10);
        if ($pdf->GetY() + 6 > $pdf->getPageHeight() - 15) {
            $pdf->AddPage();
        }
        $pdf->Cell(50, 6, 'TOTAL', 1, 0, 'L'); // Left-aligned for text
        $pdf->Cell(30, 6, number_format($countStaff), 1, 0, 'R'); // Right-aligned for numerical data
        $pdf->Cell(30, 6, number_format($sumTotal, 2), 1, 1, 'R'); // Right-aligned as requested
    } else {
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(110, 6, 'No data for this period.', 1, 1, 'L');
    }
} catch (PDOException $e) {
    echo $e->getMessage();
    exit;
}

// Output the PDF
$filename = 'Bank_Summary_' . str_replace(' ', '_', $period_text) . '.pdf';
$pdf->Output($filename, 'D');
exit;
?>