<?php
ini_set('max_execution_time', 0);
require_once '../Connections/paymaster.php';
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
    $startRow = isset($_POST['hasHeaders']) && $_POST['hasHeaders'] == 1 ? 1 : 0;

    if (count($data) <= $startRow) {
        throw new Exception('File is empty or contains only headers.');
    }

    $conn->beginTransaction();
    $successCount = 0;

    for ($i = $startRow; $i < count($data); $i++) {
        $staff_id = trim($data[$i][0] ?? '');
        $value = trim($data[$i][1] ?? '');

        // Validate data
        if (empty($staff_id) || !is_numeric($value) || $value < 0) {
            continue; // Skip invalid rows
        }

        // Check if staff_id exists
        $stmt = $conn->prepare("SELECT staff_id FROM employee WHERE staff_id = ?");
        $stmt->execute([$staff_id]);
        if (!$stmt->fetch()) {
            continue; // Skip invalid staff_id
        }

        // Check if tax record exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM allow_deduc WHERE allow_id = 41 AND staff_id = ?");
        $stmt->execute([$staff_id]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            // Update existing record
            $stmt = $conn->prepare("UPDATE allow_deduc SET value = ?, date_insert = ?, inserted_by = ? WHERE allow_id = 41 AND staff_id = ?");
            $stmt->execute([$value, $recordtime, $_SESSION['SESS_MEMBER_ID'], $staff_id]);
        } else {
            // Insert new record
            $stmt = $conn->prepare("INSERT INTO allow_deduc (staff_id, allow_id, value, transcode, date_insert, inserted_by) VALUES (?, 41, ?, 2, ?, ?)");
            $stmt->execute([$staff_id, $value, $recordtime, $_SESSION['SESS_MEMBER_ID']]);
        }

        $successCount++;
    }

    $conn->commit();

    if ($successCount === 0) {
        echo json_encode(['status' => 'error', 'message' => 'No valid records were processed. Check staff IDs and values.']);
    } else {
        echo json_encode(['status' => 'success', 'message' => "$successCount record(s) successfully imported."]);
    }
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Import error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Failed to process file: ' . $e->getMessage()]);
}
?>