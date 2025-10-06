<?php
session_start();
ini_set('max_execution_time', 300);
require_once 'Connections/paymaster.php';
require_once 'classes/model.php';
require_once 'libs/App.php';
require_once 'libs/middleware.php';

$App = new App();
$App->checkAuthentication();
checkPermission();

session_start();

if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) === '' || ($_SESSION['role'] ?? '') !== 'Admin') {
    header("Location: index.php");
    exit;
}

// Get parameters
$periodId = $_GET['period_id'] ?? null;
$submissionId = $_GET['submission_id'] ?? null;

if (!$periodId || !$submissionId) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Export Error</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 50px; }
            .error { background: #fee; border: 1px solid #fcc; padding: 20px; border-radius: 5px; }
        </style>
        <link href="css/dark-mode.css" rel="stylesheet">
    <script src="js/theme-manager.js"></script>
</head>
    <body>
        <div class="error">
            <h2>Export Error</h2>
            <p>Invalid parameters. Please go back to the variance tracking page and try again.</p>
            <p><a href="abeokuta_variance_tracking.php">← Back to Variance Tracking</a></p>
        </div>
    </body>
    </html>';
    exit;
}

// Get submission details
try {
    $query = "SELECT * FROM abeokuta_submissions WHERE id = ? AND period_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$submissionId, $periodId]);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$submission) {
        throw new Exception("Submission not found");
    }
} catch(Exception $e) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Export Error</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 50px; }
            .error { background: #fee; border: 1px solid #fcc; padding: 20px; border-radius: 5px; }
        </style>
        <link href="css/dark-mode.css" rel="stylesheet">
    <script src="js/theme-manager.js"></script>
</head>
    <body>
        <div class="error">
            <h2>Export Error</h2>
            <p>Submission not found. Please go back to the variance tracking page and try again.</p>
            <p><a href="abeokuta_variance_tracking.php">← Back to Variance Tracking</a></p>
        </div>
    </body>
    </html>';
    exit;
}

// Calculate variance data
$varianceData = calculateVarianceData($periodId, [$submission]);

// Set headers for CSV download
$filename = 'abeokuta_variance_' . $periodId . '_' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write CSV content
fputcsv($output, ['ABEOKUTA VARIANCE TRACKING REPORT']);
fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
fputcsv($output, ['Period ID: ' . $periodId]);
fputcsv($output, ['Submission Date: ' . $submission['submission_date']]);
fputcsv($output, ['Analysis Date: ' . date('Y-m-d')]);
fputcsv($output, ['']);

// Variance Summary
fputcsv($output, ['VARIANCE SUMMARY']);
fputcsv($output, ['Submitted Gross', '₦' . number_format($varianceData['submitted_gross'])]);
fputcsv($output, ['Actual Gross', '₦' . number_format($varianceData['actual_gross'])]);
fputcsv($output, ['Variance', '₦' . number_format($varianceData['variance'])]);
fputcsv($output, ['']);

// New Employees
if (!empty($varianceData['changes']['new_employees'])) {
    fputcsv($output, ['NEW EMPLOYEES']);
    fputcsv($output, ['Staff ID', 'Name', 'Department', 'Grade/Step', 'Status']);
    foreach ($varianceData['changes']['new_employees'] as $emp) {
        fputcsv($output, [
            $emp['staff_id'],
            $emp['NAME'],
            $emp['DEPTCD'] ?? '',
            $emp['GRADE'] . '/' . $emp['STEP'],
            $emp['STATUSCD']
        ]);
    }
    fputcsv($output, ['']);
}

// Departed Employees
if (!empty($varianceData['changes']['departed_employees'])) {
    fputcsv($output, ['DEPARTED EMPLOYEES']);
    fputcsv($output, ['Staff ID', 'Name', 'Department', 'Last Grade/Step', 'Last Status']);
    foreach ($varianceData['changes']['departed_employees'] as $emp) {
        fputcsv($output, [
            $emp['staff_id'],
            $emp['NAME'],
            $emp['DEPTCD'] ?? '',
            $emp['GRADE'] . '/' . $emp['STEP'],
            $emp['STATUSCD']
        ]);
    }
    fputcsv($output, ['']);
}

// Status Changes
if (!empty($varianceData['changes']['status_changes'])) {
    fputcsv($output, ['STATUS CHANGES']);
    fputcsv($output, ['Staff ID', 'Name', 'Old Status', 'New Status']);
    foreach ($varianceData['changes']['status_changes'] as $change) {
        fputcsv($output, [
            $change['staff_id'],
            $change['name'],
            $change['old_status'],
            $change['new_status']
        ]);
    }
    fputcsv($output, ['']);
}

// Promotions
if (!empty($varianceData['changes']['promotions'])) {
    fputcsv($output, ['PROMOTIONS/DEMOTIONS']);
    fputcsv($output, ['Staff ID', 'Name', 'Grade Change', 'Step Change']);
    foreach ($varianceData['changes']['promotions'] as $promotion) {
        fputcsv($output, [
            $promotion['staff_id'],
            $promotion['name'],
            $promotion['old_grade'] . ' → ' . $promotion['new_grade'],
            $promotion['old_step'] . ' → ' . $promotion['new_step']
        ]);
    }
    fputcsv($output, ['']);
}

fclose($output);

// Function to calculate variance data (copied from main file)
function calculateVarianceData($periodId, $submissions) {
    global $conn;
    
    // Get actual gross for the period
    $actualGross = calculateGrossAsOfDate($periodId, date('Y-m-d'));
    
    // Get the latest submission
    $latestSubmission = $submissions[0];
    $submittedGross = $latestSubmission['submitted_gross'];
    
    // Calculate variance
    $variance = $actualGross - $submittedGross;
    
    // Get changes that occurred after submission
    $changes = getChangesAfterSubmission($periodId, $latestSubmission['submission_date']);
    
    return [
        'submitted_gross' => $submittedGross,
        'actual_gross' => $actualGross,
        'variance' => $variance,
        'submission_date' => $latestSubmission['submission_date'],
        'changes' => $changes
    ];
}

// Function to calculate gross as of a specific date
function calculateGrossAsOfDate($periodId, $date) {
    global $conn;
    
    try {
        $query = "SELECT SUM(allow) as total_gross 
                  FROM tbl_master tm
                  JOIN master_staff ms ON ms.staff_id = tm.staff_id AND ms.period = tm.period
                  WHERE tm.period = ? AND tm.allow > 0";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$periodId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['total_gross'] ?? 0;
    } catch(PDOException $e) {
        return 0;
    }
}

// Function to get changes after submission
function getChangesAfterSubmission($periodId, $submissionDate) {
    global $conn;
    
    $changes = [];
    
    try {
        // Get employees who were active as of submission date
        $submissionQuery = "SELECT DISTINCT tm.staff_id, ms.NAME, ms.DEPTCD, ms.GRADE, ms.STEP, ms.STATUSCD
                           FROM tbl_master tm
                           JOIN master_staff ms ON ms.staff_id = tm.staff_id AND ms.period = tm.period
                           WHERE tm.period = ? AND tm.allow > 0";
        
        $stmt = $conn->prepare($submissionQuery);
        $stmt->execute([$periodId]);
        $submissionEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get current employees
        $currentQuery = "SELECT DISTINCT tm.staff_id, ms.NAME, ms.DEPTCD, ms.GRADE, ms.STEP, ms.STATUSCD
                        FROM tbl_master tm
                        JOIN master_staff ms ON ms.staff_id = tm.staff_id AND ms.period = tm.period
                        WHERE tm.period = ? AND tm.allow > 0";
        
        $stmt = $conn->prepare($currentQuery);
        $stmt->execute([$periodId]);
        $currentEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Create lookup arrays
        $submissionLookup = [];
        foreach ($submissionEmployees as $emp) {
            $submissionLookup[$emp['staff_id']] = $emp;
        }
        
        $currentLookup = [];
        foreach ($currentEmployees as $emp) {
            $currentLookup[$emp['staff_id']] = $emp;
        }
        
        // Find new employees (in current but not in submission)
        foreach ($currentLookup as $staffId => $currentEmp) {
            if (!isset($submissionLookup[$staffId])) {
                $changes['new_employees'][] = $currentEmp;
            }
        }
        
        // Find departed employees (in submission but not in current)
        foreach ($submissionLookup as $staffId => $submissionEmp) {
            if (!isset($currentLookup[$staffId])) {
                $changes['departed_employees'][] = $submissionEmp;
            }
        }
        
        // Find status changes
        foreach ($currentLookup as $staffId => $currentEmp) {
            if (isset($submissionLookup[$staffId])) {
                $submissionEmp = $submissionLookup[$staffId];
                
                if ($currentEmp['STATUSCD'] !== $submissionEmp['STATUSCD']) {
                    $changes['status_changes'][] = [
                        'staff_id' => $staffId,
                        'name' => $currentEmp['NAME'],
                        'old_status' => $submissionEmp['STATUSCD'],
                        'new_status' => $currentEmp['STATUSCD']
                    ];
                }
                
                if ($currentEmp['GRADE'] !== $submissionEmp['GRADE'] || $currentEmp['STEP'] !== $submissionEmp['STEP']) {
                    $changes['promotions'][] = [
                        'staff_id' => $staffId,
                        'name' => $currentEmp['NAME'],
                        'old_grade' => $submissionEmp['GRADE'],
                        'new_grade' => $currentEmp['GRADE'],
                        'old_step' => $submissionEmp['STEP'],
                        'new_step' => $currentEmp['STEP']
                    ];
                }
            }
        }
        
        // Initialize empty arrays if no changes found
        if (!isset($changes['new_employees'])) $changes['new_employees'] = [];
        if (!isset($changes['departed_employees'])) $changes['departed_employees'] = [];
        if (!isset($changes['status_changes'])) $changes['status_changes'] = [];
        if (!isset($changes['promotions'])) $changes['promotions'] = [];
        
    } catch(PDOException $e) {
        error_log("Error getting changes after submission: " . $e->getMessage());
    }
    
    return $changes;
}
?>