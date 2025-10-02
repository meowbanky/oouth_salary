<?php
require_once('Connections/paymaster.php');
include_once('classes/model.php');
require_once 'libs/App.php';
$App = new App();
$App->checkAuthentication();
require_once 'libs/middleware.php';
checkPermission();

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) == '' || $_SESSION['role'] != 'Admin') {
    header("location: index.php");
    exit();
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';

// Build WHERE clause
$where = '';
$params = [];
$conditions = [];

if ($search) {
    $conditions[] = '(employee.NAME LIKE :search OR employee.staff_id LIKE :search)';
    $params[':search'] = "%$search%";
}

if ($status_filter) {
    $conditions[] = 'employee.STATUSCD = :status';
    $params[':status'] = $status_filter;
}

if (!empty($conditions)) {
    $where = 'WHERE ' . implode(' AND ', $conditions);
}

// Get all employees (no pagination for export)
$sql = "SELECT 
            employee.*, 
            tbl_dept.dept, 
            tbl_pfa.PFANAME, 
            tbl_bank.BNAME,
            staff_status.STATUS as status_name
        FROM employee 
        LEFT JOIN tbl_pfa ON tbl_pfa.PFACODE = employee.PFACODE 
        LEFT JOIN tbl_bank ON tbl_bank.BCODE = employee.BCODE 
        LEFT JOIN tbl_dept ON tbl_dept.dept_id = employee.DEPTCD 
        LEFT JOIN staff_status ON staff_status.STATUSCD = employee.STATUSCD
        $where 
        ORDER BY employee.statuscd, employee.staff_id ASC";

$stmt = $conn->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include PhpSpreadsheet
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Create new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set document properties
$spreadsheet->getProperties()
    ->setCreator('OOUTH Salary Manager')
    ->setLastModifiedBy($_SESSION['SESS_MEMBER_ID'])
    ->setTitle('Employee Records Export')
    ->setSubject('Employee Data Export')
    ->setDescription('Export of employee records from OOUTH Salary Management System')
    ->setKeywords('employee, export, oouth, salary')
    ->setCategory('Employee Data');

// Set headers
$headers = [
    'A' => 'Staff ID',
    'B' => 'Full Name',
    'C' => 'Email',
    'D' => 'Employment Date',
    'E' => 'Status',
    'F' => 'Department',
    'G' => 'Designation',
    'H' => 'Grade',
    'I' => 'Step',
    'J' => 'PFA',
    'K' => 'RSA PIN',
    'L' => 'Bank',
    'M' => 'Account Number',
    'N' => 'Call Duty Type',
    'O' => 'Hazard Type'
];

// Set header values and style
$col = 1;
foreach ($headers as $column => $header) {
    $sheet->setCellValueByColumnAndRow($col, 1, $header);
    $col++;
}

// Style the header row
$headerRange = 'A1:O1';
$sheet->getStyle($headerRange)->applyFromArray([
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF']
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '1E40AF'] // Blue-800
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
]);

// Set column widths
$columnWidths = [
    'A' => 12, // Staff ID
    'B' => 25, // Full Name
    'C' => 30, // Email
    'D' => 15, // Employment Date
    'E' => 12, // Status
    'F' => 20, // Department
    'G' => 20, // Designation
    'H' => 8,  // Grade
    'I' => 8,  // Step
    'J' => 20, // PFA
    'K' => 15, // RSA PIN
    'L' => 20, // Bank
    'M' => 15, // Account Number
    'N' => 15, // Call Duty Type
    'O' => 15  // Hazard Type
];

foreach ($columnWidths as $column => $width) {
    $sheet->getColumnDimension($column)->setWidth($width);
}

// Status mapping
$statusMap = [
    'A' => 'Active',
    'D' => 'Dismissed',
    'T' => 'Termination',
    'R' => 'Resignation',
    'S' => 'Suspension',
    'DE' => 'Death'
];

// Call duty mapping
$callMap = [
    0 => 'None',
    1 => 'Doctor',
    2 => 'Others',
    3 => 'Nurse'
];

// Hazard type mapping
$hazardMap = [
    1 => 'Clinical',
    2 => 'Non-clinical'
];

// Add data rows
$row = 2;
foreach ($employees as $emp) {
    $sheet->setCellValue('A' . $row, $emp['staff_id']);
    $sheet->setCellValue('B' . $row, $emp['NAME']);
    $sheet->setCellValue('C' . $row, $emp['EMAIL'] ?? '');
    $sheet->setCellValue('D' . $row, $emp['EMPDATE'] ?? '');
    $sheet->setCellValue('E' . $row, $emp['status_name'] ?? ($statusMap[$emp['STATUSCD']] ?? 'Unknown'));
    $sheet->setCellValue('F' . $row, $emp['dept'] ?? '');
    $sheet->setCellValue('G' . $row, $emp['POST'] ?? '');
    $sheet->setCellValue('H' . $row, $emp['GRADE'] ?? '');
    $sheet->setCellValue('I' . $row, $emp['STEP'] ?? '');
    $sheet->setCellValue('J' . $row, $emp['PFANAME'] ?? '');
    $sheet->setCellValue('K' . $row, $emp['PFAACCTNO'] ?? '');
    $sheet->setCellValue('L' . $row, $emp['BNAME'] ?? '');
    $sheet->setCellValue('M' . $row, $emp['ACCTNO'] ?? '');
    $sheet->setCellValue('N' . $row, $callMap[$emp['CALLTYPE']] ?? '—');
    $sheet->setCellValue('O' . $row, $hazardMap[$emp['HARZAD_TYPE']] ?? '—');
    
    $row++;
}

// Style data rows
if ($row > 2) {
    $dataRange = 'A2:O' . ($row - 1);
    $sheet->getStyle($dataRange)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'CCCCCC']
            ]
        ],
        'alignment' => [
            'vertical' => Alignment::VERTICAL_CENTER
        ]
    ]);
    
    // Alternate row colors
    for ($i = 2; $i < $row; $i++) {
        if ($i % 2 == 0) {
            $sheet->getStyle('A' . $i . ':O' . $i)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F8FAFC'] // Gray-50
                ]
            ]);
        }
    }
}

// Add filter info as a note
$filterInfo = "Export Date: " . date('Y-m-d H:i:s') . "\n";
$filterInfo .= "Total Records: " . count($employees) . "\n";
if ($search) {
    $filterInfo .= "Search Term: " . $search . "\n";
}
if ($status_filter) {
    $statusName = $emp['status_name'] ?? ($statusMap[$status_filter] ?? $status_filter);
    $filterInfo .= "Status Filter: " . $statusName . "\n";
}

$sheet->setCellValue('A' . ($row + 1), 'Export Information:');
$sheet->setCellValue('A' . ($row + 2), $filterInfo);

// Generate filename
$filename = 'employees_export_' . date('Y-m-d_H-i-s');
if ($search) {
    $filename .= '_search_' . preg_replace('/[^a-zA-Z0-9]/', '', $search);
}
if ($status_filter) {
    $filename .= '_status_' . $status_filter;
}
$filename .= '.xlsx';

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Create writer and save
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

exit();
?>
