<?php
session_start();

require __DIR__.'/../vendor/autoload.php';
$tcpdf_path = __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';
if (!file_exists($tcpdf_path)) {
    error_log('TCPDF file not found at: ' . $tcpdf_path);
    header('HTTP/1.1 500 Internal Server Error');
    exit('TCPDF library not found');
}
// Manually include TCPDF since autoloader might not be working
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
    $period_text = $out[0]['description'] . '-' . $out[0]['periodYear'];
} catch (PDOException $e) {
    echo $e->getMessage();
    exit;
}

// Create new CustomTCPDF instance
$pdf = new CustomTCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor(isset($_SESSION['SESS_FIRST_NAME']) ? $_SESSION['SESS_FIRST_NAME'] : 'Unknown User');
$pdf->SetTitle('Payroll Summary - ' . $period_text);
$pdf->SetSubject('Payroll Summary Report');

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
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 5, 'PAYROLL SUMMARY', 0, 1, 'C');
$pdf->Cell(0, 5, 'Period: ' . $period_text, 0, 1, 'C');
$pdf->Ln(5);

// Table header
$pdf->SetFillColor(200, 200, 200);
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(30, 8, 'Code', 1, 0, 'C', 1);
$pdf->Cell(90, 8, 'Description', 1, 0, 'L', 1);
$pdf->Cell(40, 8, 'Amount', 1, 1, 'R', 1);

// Earnings section
$sumAll = 0;
$sumDeduct = 0;

$pdf->SetFont('helvetica', 'B', 8);
if ($pdf->GetY() + 6 > $pdf->getPageHeight() - 15) {
    $pdf->AddPage();
}
$pdf->Cell(160, 6, 'Earnings', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 8);

try {
    $query = $conn->prepare('SELECT sum(tbl_master.allow) as allow, allow_id, tbl_earning_deduction.ed FROM tbl_master INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id WHERE tbl_master.type = ? and period = ? GROUP BY tbl_master.allow_id');
    $fin = $query->execute(array('1', $period));
    $res = $query->fetchAll(PDO::FETCH_ASSOC);

    if ($res) {
        foreach ($res as $link) {
            if ($pdf->GetY() + 6 > $pdf->getPageHeight() - 15) {
                $pdf->AddPage();
                $pdf->SetFillColor(200, 200, 200);
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(30, 8, 'Code', 1, 0, 'C', 1);
                $pdf->Cell(90, 8, 'Description', 1, 0, 'L', 1);
                $pdf->Cell(40, 8, 'Amount', 1, 1, 'R', 1);
                $pdf->SetFont('helvetica', '', 10);
            }
            $descLines = $pdf->getNumLines($link['ed'] ?? '', 90);
            $rowHeight = max(6, 6 * $descLines);
            $pdf->MultiCell(30, $rowHeight, $link['allow_id'], 1, 'C', false, 0);
            $pdf->MultiCell(90, $rowHeight, $link['ed'] ?? '', 1, 'L', false, 0);
            $pdf->Cell(40, $rowHeight, number_format($link['allow'], 2), 1, 1, 'R');
            $sumAll += floatval($link['allow']);
        }
    } else {
        $pdf->Cell(160, 6, 'No earnings for this period.', 1, 1, 'L');
    }

    // Total earnings
    $pdf->SetFont('helvetica', 'B', 8);
    if ($pdf->GetY() + 6 > $pdf->getPageHeight() - 15) {
        $pdf->AddPage();
    }
    $pdf->Cell(30, 6, '', 1);
    $pdf->Cell(90, 6, 'Total Earnings', 1, 0, 'L');
    $pdf->Cell(40, 6, number_format($sumAll, 2), 1, 1, 'R');

    // Spacer
    $pdf->Ln(5);

    // Deductions section
    $pdf->SetFont('helvetica', 'B', 8);
    if ($pdf->GetY() + 6 > $pdf->getPageHeight() - 15) {
        $pdf->AddPage();
    }
    $pdf->Cell(160, 6, 'Deductions', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 8);

    $query = $conn->prepare('SELECT sum(tbl_master.deduc) as deduct, allow_id, tbl_earning_deduction.ed FROM tbl_master INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id WHERE tbl_master.type = ? and period = ? GROUP BY tbl_master.allow_id');
    $fin = $query->execute(array('2', $period));
    $res = $query->fetchAll(PDO::FETCH_ASSOC);

    if ($res) {
        foreach ($res as $link) {
            if ($pdf->GetY() + 6 > $pdf->getPageHeight() - 15) {
                $pdf->AddPage();
                $pdf->SetFillColor(200, 200, 200);
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->Cell(30, 8, 'Code', 1, 0, 'C', 1);
                $pdf->Cell(90, 8, 'Description', 1, 0, 'L', 1);
                $pdf->Cell(40, 8, 'Amount', 1, 1, 'R', 1);
                $pdf->SetFont('helvetica', '', 8);
            }
            $descLines = $pdf->getNumLines($link['ed'] ?? '', 90);
            $rowHeight = max(6, 6 * $descLines);
            $pdf->MultiCell(30, $rowHeight, $link['allow_id'], 1, 'C', false, 0);
            $pdf->MultiCell(90, $rowHeight, $link['ed'] ?? '', 1, 'L', false, 0);
            $pdf->Cell(40, $rowHeight, number_format($link['deduct'], 2), 1, 1, 'R');
            $sumDeduct += floatval($link['deduct']);
        }
    } else {
        $pdf->Cell(160, 6, 'No deductions for this period.', 1, 1, 'L');
    }

    // Total deductions
    $pdf->SetFont('helvetica', 'B', 8);
    if ($pdf->GetY() + 6 > $pdf->getPageHeight() - 15) {
        $pdf->AddPage();
    }
    $pdf->Cell(30, 6, '', 1);
    $pdf->Cell(90, 6, 'Total Deductions', 1, 0, 'L');
    $pdf->Cell(40, 6, number_format($sumDeduct, 2), 1, 1, 'R');

    // Net pay
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 10);
    if ($pdf->GetY() + 8 > $pdf->getPageHeight() - 15) {
        $pdf->AddPage();
    }
    $pdf->Cell(30, 8, '', 1);
    $pdf->Cell(90, 8, 'Net Pay', 1, 0, 'L');
    $pdf->Cell(40, 8, number_format(floatval($sumAll) - floatval($sumDeduct), 2), 1, 1, 'R');
} catch (PDOException $e) {
    echo $e->getMessage();
    exit;
}

// Output the PDF
$filename = 'Payroll_Summary_' . str_replace(' ', '_', $period_text) . '.pdf';
$pdf->Output($filename, 'D');
exit;
?>