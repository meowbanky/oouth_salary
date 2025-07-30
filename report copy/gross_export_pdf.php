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
        $this->SetFont('helvetica', 'I', 10); // Increased font size to 10
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

// Create new CustomTCPDF instance with landscape orientation
$pdf = new CustomTCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor(isset($_SESSION['SESS_FIRST_NAME']) ? $_SESSION['SESS_FIRST_NAME'] : 'Unknown User');
$pdf->SetTitle('Gross Report - ' . $period_text);
$pdf->SetSubject('Gross Report');

// Enable footer, disable header
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(true);

// Set margins
$pdf->SetMargins(15, 20, 15);
$pdf->SetAutoPageBreak(TRUE, 15);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 10); // Increased font size to 10

// Logos
$logoLeft = __DIR__ . '/../img/ogun_logo.png';
$logoRight = __DIR__ . '/../img/oouth_logo.png';

if (file_exists($logoLeft)) {
    $pdf->Image($logoLeft, 15, 10, 25, 25, 'PNG', '', 'T', false, 300, '', false, false, 0);
} else {
    error_log('Left logo file missing: ' . $logoLeft);
}
if (file_exists($logoRight)) {
    $pdf->Image($logoRight, 265, 10, 25, 25, 'PNG', '', 'T', false, 300, '', false, false, 0);
} else {
    error_log('Right logo file missing: ' . $logoRight);
}

// Institution name and title
$pdf->SetY(10);
$pdf->SetFont('helvetica', 'B', 10); // Increased font size to 10
$businessName = "Olabisi Onabanjo University Teaching Hospital,\nSagamu";
$pdf->MultiCell(260, 15, $businessName, 0, 'C', false, 1, 35, null, true, 0, false, true, 15, 'M');
$pdf->SetFont('helvetica', '', 10); // Increased font size to 10
$pdf->Cell(0, 5, 'GROSS REPORT', 0, 1, 'C');
$pdf->Cell(0, 5, 'Period: ' . $period_text, 0, 1, 'C');
$pdf->Ln(5);

// Table header
$pdf->SetFillColor(200, 200, 200);
$pdf->SetFont('helvetica', 'B', 10); // Increased font size to 10
// Adjusted column widths to fill 267mm (previously 219mm, scaling by ~1.22 factor)
$pdf->Cell(15, 7, 'S/No', 1, 0, 'C', 1); // 12 * 1.22 ≈ 15
$pdf->Cell(27, 7, 'Staff No.', 1, 0, 'L', 1); // 22 * 1.22 ≈ 27
$pdf->Cell(43, 7, 'Name', 1, 0, 'L', 1); // 35 * 1.22 ≈ 43
$pdf->Cell(49, 7, 'Dept', 1, 0, 'L', 1); // 40 * 1.22 ≈ 49
$pdf->Cell(22, 7, 'Grade', 1, 0, 'L', 1); // 18 * 1.22 ≈ 22
$pdf->Cell(15, 7, 'Step', 1, 0, 'C', 1); // 12 * 1.22 ≈ 15
$pdf->Cell(30, 7, 'Acct No.', 1, 0, 'L', 1); // 25 * 1.22 ≈ 30
$pdf->Cell(37, 7, 'Bank', 1, 0, 'L', 1); // 30 * 1.22 ≈ 37
$pdf->Cell(30, 7, 'Gross Pay', 1, 1, 'R', 1); // 25 * 1.22 ≈ 30

// Data section
$sumTotal = 0;

try {
    $sql = 'SELECT
        any_value(tbl_master.staff_id) AS staff_id,
        any_value(Sum(tbl_master.allow)) AS allow,
        any_value(Sum(tbl_master.deduc)) AS deduc,
        any_value((Sum(tbl_master.allow) - Sum(tbl_master.deduc))) AS net,
        any_value(master_staff.`NAME`) AS `NAME`,
        any_value(tbl_bank.BNAME) AS BNAME,
        ANY_VALUE(master_staff.BCODE) AS BCODE,
        ANY_VALUE(master_staff.ACCTNO) AS ACCTNO,
        any_value(master_staff.GRADE) AS GRADE,
        any_value(master_staff.STEP) AS STEP,
        any_value(tbl_dept.dept) AS dept 
    FROM
        tbl_master
        INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id
        INNER JOIN tbl_bank ON tbl_bank.BCODE = master_staff.BCODE
        INNER JOIN tbl_dept ON master_staff.DEPTCD = tbl_dept.dept_id 
    WHERE tbl_master.period = ? AND master_staff.period = ? GROUP BY tbl_master.staff_id';
    $query = $conn->prepare($sql);
    $fin = $query->execute(array($period, $period));
    $res = $query->fetchAll(PDO::FETCH_ASSOC);

    if ($res) {
        $i = 1;
        foreach ($res as $link) {
            // Check for page break
            if ($pdf->GetY() + 7 > $pdf->getPageHeight() - 15) { // Adjusted for larger font
                $pdf->AddPage();
                $pdf->SetFillColor(200, 200, 200);
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(15, 7, 'S/No', 1, 0, 'C', 1);
                $pdf->Cell(27, 7, 'Staff No.', 1, 0, 'L', 1);
                $pdf->Cell(43, 7, 'Name', 1, 0, 'L', 1);
                $pdf->Cell(49, 7, 'Dept', 1, 0, 'L', 1);
                $pdf->Cell(22, 7, 'Grade', 1, 0, 'L', 1);
                $pdf->Cell(15, 7, 'Step', 1, 0, 'C', 1);
                $pdf->Cell(30, 7, 'Acct No.', 1, 0, 'L', 1);
                $pdf->Cell(37, 7, 'Bank', 1, 0, 'L', 1);
                $pdf->Cell(30, 7, 'Gross Pay', 1, 1, 'R', 1);
            }

            $pdf->SetFont('helvetica', '', 10); // Increased font size to 10

            // Calculate the number of lines for wrapping columns
            $nameLines = $pdf->getNumLines($link['NAME'] ?? '', 43); // Updated width
            $deptLines = $pdf->getNumLines($link['dept'] ?? '', 49); // Updated width
            $maxLines = max($nameLines, $deptLines, 1);
            $rowHeight = 6 * $maxLines; // Increased base height for larger font (was 5mm)

            // Start a new row
            $startY = $pdf->GetY();

            // S/No
            $pdf->Cell(15, $rowHeight, $i, 1, 0, 'C');

            // Staff No.
            $pdf->Cell(27, $rowHeight, $link['staff_id'], 1, 0, 'L');

            // Name (with wrapping)
            $pdf->MultiCell(43, $rowHeight, $link['NAME'], 1, 'L', false, 0);

            // Dept (with wrapping)
            $pdf->MultiCell(49, $rowHeight, $link['dept'], 1, 'L', false, 0);

            // Grade
            $pdf->Cell(22, $rowHeight, $link['GRADE'], 1, 0, 'L');

            // Step
            $pdf->Cell(15, $rowHeight, $link['STEP'], 1, 0, 'C');

            // Acct No.
            $pdf->Cell(30, $rowHeight, $link['ACCTNO'], 1, 0, 'L');

            // Bank
            $pdf->Cell(37, $rowHeight, $link['BNAME'], 1, 0, 'L');

            // Gross Pay
            $pdf->Cell(30, $rowHeight, number_format($link['allow'], 2), 1, 1, 'R');

            // Update Y position to ensure alignment
            $pdf->SetY($startY + $rowHeight);

            $sumTotal += floatval($link['allow']);
            $i++;
        }

        // Totals row
        $pdf->SetFont('helvetica', 'B', 10); // Increased font size to 10
        if ($pdf->GetY() + 6 > $pdf->getPageHeight() - 15) {
            $pdf->AddPage();
        }
        $pdf->Cell(136, 6, 'TOTAL', 1, 0, 'L'); // Adjusted width for new column sizes
        $pdf->Cell(30, 6, '', 1, 0, 'R');
        $pdf->Cell(30, 6, number_format($sumTotal, 2), 1, 1, 'R');

        // Extra rows and signature
        $pdf->Ln(8);
        $pdf->Cell(90, 6, '', 0, 0);
        $pdf->Cell(45, 6, '', 0, 0);
        $pdf->Cell(30, 6, '', 0, 1);
        $pdf->Cell(90, 6, '', 0, 0);
        $pdf->Cell(45, 6, '', 0, 0);
        $pdf->Cell(30, 6, '', 0, 1);
        $pdf->Cell(90, 6, '', 0, 0);
        $pdf->Cell(45, 6, '', 0, 0);
        $pdf->Cell(30, 6, '', 0, 1);
        $pdf->Cell(90, 6, 'ADEKANMBI \'MUYIWA', 0, 0, 'L');
        $pdf->Cell(45, 6, 'SIGNATURE', 0, 0, 'C');
        $pdf->Cell(30, 6, 'DATE', 0, 1, 'C');
    } else {
        $pdf->SetFont('helvetica', '', 10); // Increased font size to 10
        $pdf->Cell(237, 6, 'No data for this period.', 1, 1, 'L'); // Adjusted width for new total
    }
} catch (PDOException $e) {
    echo $e->getMessage();
    exit;
}

// Output the PDF
$filename = 'Gross_Report_' . str_replace(' ', '_', $period_text) . '.pdf';
$pdf->Output($filename, 'D');
exit;
?>