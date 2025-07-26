<?php
ini_set('max_execution_time', 0);
require_once '../Connections/paymaster.php';
require_once '../classes/fn_runUpdateGrade.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

session_start();

// Validate session
if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) === '') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Please log in.']);
    exit;
}

// Validate file upload
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'File upload failed. Please try again.']);
    exit;
}

// Validate file type
$allowedExtensions = ['csv', 'xlsx', 'xls'];
$extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
if (!in_array($extension, $allowedExtensions)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid file format. Please upload a CSV, XLS, or XLSX file.']);
    exit;
}

$recordtime = date('Y-m-d H:i:s');

try {
    // Load spreadsheet
    $excelFile = $_FILES['file']['tmp_name'];
    $spreadsheet = IOFactory::load($excelFile);
    $worksheet = $spreadsheet->getActiveSheet();
    $data = $worksheet->toArray();

    // Determine starting row based on headers
    $startRow = (isset($_POST['hasHeaders']) && $_POST['hasHeaders'] == 1) ? 1 : 0;

    if (count($data) <= $startRow) {
        throw new Exception('File is empty or contains only headers.');
    }

    $conn->beginTransaction();
    $successCount = 0;

    for ($i = $startRow; $i < count($data); $i++) {
        $staff_id = trim($data[$i][0] ?? null);
        $gradeStep = trim($data[$i][2] ?? null);

        // Validate data
        if (empty($staff_id) || empty($gradeStep)) {
            continue; // Skip if staff_id or empty is gradeStep
        }

        // Validate format of grade/step (e.g., 5/3)
        $splited = explode('/', $gradeStep);
        if (count($splited) !== 2 || !is_numeric($splited[0]) || !is_numeric($splited[1]) || $splited[0] < 1 || $splited[1] < 1) {
            continue; // Skip invalid format grade/step
        }
        $grade = $splited[0];
        $step = $splited[1];

        // Check if staff_id exists
        $stmt = $conn->prepare("SELECT staff_id FROM employee WHERE staff_id = ?");
        $stmt->execute([$staff_id]);
        if (!$stmt->fetch()) {
            continue; // Skip invalid staff_id
        }

        // Update employee grade and step
        $stmt = $conn->prepare("UPDATE employee SET GRADE = ?, STEP = ?, editTime = ?, userID = ? WHERE staff_id = ?");
        $stmt->execute([$grade, $step, $recordtime, $_SESSION['SESS_MEMBER_ID'], $staff_id]);

        // Update related deductions
        runGrade_Step($step, $grade, $staff_id);

        $successCount++;
    }

    $conn->commit();

    if ($successCount === 0) {
        echo json_encode(['status' => 'error', 'message' => 'No valid records were processed. Check staff IDs and grade/step format (e.g., 5/3).']);
    } else {
        echo json_encode(['status' => 'success', 'message' => "$successCount record(s) successfully imported."]);
    }
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Import error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Failed to process file: ' . $e->getMessage()]);
}
?>