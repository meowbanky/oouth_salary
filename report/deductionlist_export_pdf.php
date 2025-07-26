<?php
session_start();

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

// Clear any existing output buffers
while (ob_get_level()) {
    ob_end_clean();
}

$period = filter_input(INPUT_POST, 'period', FILTER_VALIDATE_INT) ?: -1;
$deduction = filter_input(INPUT_POST, 'deduction', FILTER_VALIDATE_INT) ?: -1;
$deduction_text = filter_input(INPUT_POST, 'deduction_text', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
$period_text = filter_input(INPUT_POST, 'period_text', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
$code = filter_input(INPUT_POST, 'code', FILTER_VALIDATE_INT) ?: -1;

// Sanitize filename
$filename = 'Deduction_Report_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $deduction_text) . '_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $period_text) . '.pdf';

$response['data'] = array();

try {
    if ($code == 1) {
        $sql = 'SELECT tbl_master.allow as deduc, master_staff.staff_id, master_staff.`NAME` FROM tbl_master INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id WHERE tbl_master.allow_id = ? and tbl_master.period = ? and master_staff.period = ? order by master_staff.staff_id asc';
    } else {
        $sql = 'SELECT tbl_master.deduc as deduc, master_staff.staff_id, master_staff.`NAME` FROM tbl_master INNER JOIN master_staff ON master_staff.staff_id = tbl_master.staff_id WHERE tbl_master.allow_id = ? and tbl_master.period = ? and master_staff.period = ? order by master_staff.staff_id asc';
    }

    $query = $conn->prepare($sql);
    $fin = $query->execute(array($deduction, $period, $period));
    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $sumTotal = 0;
    $i = 1;

    foreach ($res as $row => $link) {
        $Data['S/No.'] = $i;
        $Data['StaffNo'] = $link['staff_id'];
        $Data['Name'] = $link['NAME'];
        $Data['Amount'] = number_format($link['deduc'], 2);
        if ($deduction == 87 || $deduction == 85) {
            $loan = retrieveLoanStatus($link['staff_id'], $deduction);
            $repayment = retrieveLoanBalanceStatus($link['staff_id'], $deduction, $period);
            $Data['Balance'] = number_format($loan - $repayment, 2);
        }
        $sumTotal += floatval($link['deduc']);
        array_push($response['data'], $Data);
        ++$i;
    }

    $Data['S/No.'] = '';
    $Data['StaffNo'] = '';
    $Data['Name'] = 'TOTAL';
    $Data['Amount'] = number_format($sumTotal, 2);
    if ($deduction == 87 || $deduction == 85) {
        $Data['Balance'] = '';
    }
    array_push($response['data'], $Data);
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    exit('Database error occurred');
}

// Create PDF
class MYPDF extends TCPDF {
    private $deduction_text;
    private $period_text;

    public function __construct($deduction_text, $period_text) {
        parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->deduction_text = $deduction_text;
        $this->period_text = $period_text;
    }

    public function Header() {
        if ($this->getPage() == 1) {
            // Logo paths
            $logoLeft = __DIR__ . '/../img/ogun_logo.png';
            $logoRight = __DIR__ . '/../img/oouth_logo.png';

            // Add logos
            if (file_exists($logoLeft)) {
                $this->Image($logoLeft, 15, 10, 25, 25, 'PNG', '', 'T', false, 300, '', false, false, 0);
            } else {
                error_log('Left logo file missing: ' . $logoLeft);
            }
            if (file_exists($logoRight)) {
                $this->Image($logoRight, 165, 10, 25, 25, 'PNG', '', 'T', false, 300, '', false, false, 0);
            } else {
                error_log('Right logo file missing: ' . $logoRight);
            }

            // Set font for institution name
            $this->SetFont('helvetica', 'B', 12);
            $this->SetY(20);
            $this->Cell(0, 10, 'OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL', 0, 1, 'C', 0, '', 0, false, 'T', 'M');

            // Set font for report name
            $this->SetFont('helvetica', '', 10);
            $this->Cell(0, 10, $this->deduction_text, 0, 1, 'C', 0, '', 0, false, 'T', 'M');

            $this->SetFont('helvetica', '', 10);
            $this->Cell(0, 10, 'Report for the Month of ' . $this->period_text, 0, 1, 'C', 0, '', 0, false, 'T', 'M');
        }
    }

    public function AddPage($orientation = '', $format = '', $keepmargins = false, $tocpage = false) {
        parent::AddPage($orientation, $format, $keepmargins, $tocpage);
        if ($this->getPage() > 1) {
            $this->SetMargins(PDF_MARGIN_LEFT, 20, PDF_MARGIN_RIGHT);
        }
    }

    public function Footer() {
        $this->SetY(-10);
        $this->SetFont('helvetica', 'I', 8);
        $printedBy = isset($_SESSION['SESS_FIRST_NAME']) ? $_SESSION['SESS_FIRST_NAME'] : 'Unknown User';
        $this->Cell(0, 10, 'Printed By: ' . $printedBy . '  Date Printed: ' . date('Y-m-d H:i:s'), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }

    public function printTableHeader() {
        $this->SetFont('helvetica', 'B', 8);
        $this->SetFillColor(242, 242, 242); // Light gray background
        $this->Cell(20, 6, 'S/No.', 1, 0, 'C', 1);
        $this->Cell(40, 6, 'Staff No.', 1, 0, 'C', 1);
        $this->Cell(80, 6, 'Name', 1, 0, 'L', 1);
        $this->Cell(50, 6, 'Amount', 1, 1, 'R', 1);
        $this->SetFont('helvetica', '', 8);
    }
}

$pdf = new MYPDF($deduction_text, $period_text);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Olabisi Onabanjo University Teaching Hospital');
$pdf->SetTitle($deduction_text . ' Report');
$pdf->SetSubject('Deduction Report');

$pdf->setPrintHeader(true);
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(PDF_MARGIN_LEFT, 50, PDF_MARGIN_RIGHT); // Initial margin for first page
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(false); // Disable auto page break to control it manually
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
$pdf->AddPage();

// Print table header on the first page
$pdf->printTableHeader();

// Render table data
foreach ($response['data'] as $row) {
    // Calculate the height of the row (mainly due to the Name field wrapping)
    $nameLines = $pdf->getNumLines($row['Name'], 80); // Width of Name column
    $rowHeight = max(6, 6 * $nameLines); // Minimum height of 6, adjust for wrapped text

    // Check if adding this row will exceed the page height
    if ($pdf->GetY() + $rowHeight > $pdf->getPageHeight() - $pdf->GetFooterMargin()) {
        $pdf->AddPage();
        $pdf->printTableHeader();
    }

    // Print the row
    $pdf->Cell(20, $rowHeight, $row['S/No.'], 1, 0, 'C');
    $pdf->Cell(40, $rowHeight, $row['StaffNo'], 1, 0, 'C');
    $pdf->MultiCell(80, $rowHeight, $row['Name'], 1, 'L', false, 0);
    $pdf->Cell(50, $rowHeight, $row['Amount'], 1, 1, 'R');
}

$pdf->lastPage();

// Log PDF generation completion
error_log('PDF generation completed for filename: ' . $filename);

// Set headers for direct PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Output PDF directly
try {
    $pdf->Output($filename, 'D');
} catch (Exception $e) {
    error_log('PDF output error: ' . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    exit('Error generating PDF');
}
exit;
?>