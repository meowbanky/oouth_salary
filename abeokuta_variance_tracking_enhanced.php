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

// Get current period
$currentPeriod = $_GET['period'] ?? null;

// Get all periods for dropdown
$periods = [];
try {
    $query = "SELECT periodId, description, periodYear FROM payperiods ORDER BY periodId DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $periods = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error fetching periods: " . $e->getMessage();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'submit_gross':
                $submissionDate = $_POST['submission_date'];
                $periodId = $_POST['period_id'];
                
                // Calculate gross as of submission date
                $grossAmount = calculateGrossAsOfDate($periodId, $submissionDate);
                
                if ($grossAmount > 0) {
                    // Store submission with snapshot
                    try {
                        $conn->beginTransaction();
                        
                        // Insert submission
                        $query = "INSERT INTO abeokuta_submissions (period_id, submission_date, submitted_gross, submitted_by, created_at) 
                                VALUES (?, ?, ?, ?, NOW())";
                        $stmt = $conn->prepare($query);
                        $stmt->execute([$periodId, $submissionDate, $grossAmount, $_SESSION['SESS_MEMBER_ID']]);
                        $submissionId = $conn->lastInsertId();
                        
                        // Create snapshot of current employee data
                        createEmployeeSnapshot($submissionId, $periodId, $submissionDate);
                        
                        $conn->commit();
                        
                        $success = "Gross amount of ₦" . number_format($grossAmount) . " submitted to Abeokuta with snapshot created successfully!";
                    } catch(PDOException $e) {
                        $conn->rollBack();
                        $error = "Error storing submission: " . $e->getMessage();
                    }
                } else {
                    $error = "No payroll data found for the selected date.";
                }
                break;
                
            case 'analyze_changes':
                $submissionId = $_POST['submission_id'];
                analyzeChangesForSubmission($submissionId);
                $success = "Change analysis completed and logged successfully!";
                break;
        }
    }
}

// Get submissions for current period
$submissions = [];
if ($currentPeriod) {
    try {
        $query = "SELECT s.*, 
                         (SELECT COUNT(*) FROM abeokuta_snapshots WHERE submission_id = s.id) as snapshot_count,
                         (SELECT COUNT(*) FROM abeokuta_change_log WHERE submission_id = s.id) as change_count
                  FROM abeokuta_submissions s 
                  WHERE s.period_id = ? 
                  ORDER BY s.submission_date DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$currentPeriod]);
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error = "Error fetching submissions: " . $e->getMessage();
    }
}

// Get variance data if we have submissions
$varianceData = null;
$changeHistory = [];
if ($currentPeriod && !empty($submissions)) {
    $varianceData = calculateVarianceDataWithHistory($currentPeriod, $submissions);
    $changeHistory = getChangeHistory($submissions[0]['id']);
}

// Function to create employee snapshot
function createEmployeeSnapshot($submissionId, $periodId, $submissionDate) {
    global $conn;
    
    try {
        $query = "SELECT DISTINCT 
                    tm.staff_id,
                    ms.NAME,
                    ms.DEPTCD,
                    ms.GRADE,
                    ms.STEP,
                    ms.STATUSCD,
                    SUM(tm.allow) as gross_allowance
                  FROM tbl_master tm
                  JOIN master_staff ms ON ms.staff_id = tm.staff_id AND ms.period = tm.period
                  WHERE tm.period = ? AND tm.allow > 0
                  GROUP BY tm.staff_id, ms.NAME, ms.DEPTCD, ms.GRADE, ms.STEP, ms.STATUSCD";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$periodId]);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Insert snapshot records
        $insertQuery = "INSERT INTO abeokuta_snapshots 
                       (submission_id, staff_id, name, dept, grade, step, status, gross_allowance, snapshot_date) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        
        foreach ($employees as $emp) {
            $insertStmt->execute([
                $submissionId,
                $emp['staff_id'],
                $emp['NAME'],
                $emp['DEPTCD'] ?? '',
                $emp['GRADE'],
                $emp['STEP'],
                $emp['STATUSCD'],
                $emp['gross_allowance'],
                $submissionDate
            ]);
        }
        
        return true;
    } catch(PDOException $e) {
        throw $e;
    }
}

// Function to analyze changes for a submission
function analyzeChangesForSubmission($submissionId) {
    global $conn;
    
    try {
        // Get submission details
        $query = "SELECT * FROM abeokuta_submissions WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$submissionId]);
        $submission = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$submission) {
            throw new Exception("Submission not found");
        }
        
        // Get snapshot data
        $query = "SELECT * FROM abeokuta_snapshots WHERE submission_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$submissionId]);
        $snapshotEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get current employee data
        $currentEmployees = getCurrentEmployeeData($submission['period_id']);
        
        // Create lookup arrays
        $snapshotLookup = [];
        foreach ($snapshotEmployees as $emp) {
            $snapshotLookup[$emp['staff_id']] = $emp;
        }
        
        $currentLookup = [];
        foreach ($currentEmployees as $emp) {
            $currentLookup[$emp['staff_id']] = $emp;
        }
        
        // Clear existing change log for this submission
        $query = "DELETE FROM abeokuta_change_log WHERE submission_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$submissionId]);
        
        // Find new employees
        foreach ($currentLookup as $staffId => $currentEmp) {
            if (!isset($snapshotLookup[$staffId])) {
                $query = "INSERT INTO abeokuta_change_log 
                         (submission_id, staff_id, change_type, new_value, change_description, detected_date) 
                         VALUES (?, ?, 'new_employee', ?, ?, CURDATE())";
                $stmt = $conn->prepare($query);
                $stmt->execute([
                    $submissionId,
                    $staffId,
                    json_encode($currentEmp),
                    "New employee added: {$currentEmp['NAME']} (Grade: {$currentEmp['GRADE']}/{$currentEmp['STEP']})"
                ]);
            }
        }
        
        // Find departed employees
        foreach ($snapshotLookup as $staffId => $snapshotEmp) {
            if (!isset($currentLookup[$staffId])) {
                $query = "INSERT INTO abeokuta_change_log 
                         (submission_id, staff_id, change_type, old_value, change_description, detected_date) 
                         VALUES (?, ?, 'departed_employee', ?, ?, CURDATE())";
                $stmt = $conn->prepare($query);
                $stmt->execute([
                    $submissionId,
                    $staffId,
                    json_encode($snapshotEmp),
                    "Employee departed: {$snapshotEmp['name']} (Grade: {$snapshotEmp['grade']}/{$snapshotEmp['step']})"
                ]);
            }
        }
        
        // Find status changes and promotions
        foreach ($currentLookup as $staffId => $currentEmp) {
            if (isset($snapshotLookup[$staffId])) {
                $snapshotEmp = $snapshotLookup[$staffId];
                
                // Check status changes
                if ($currentEmp['STATUSCD'] !== $snapshotEmp['status']) {
                    $query = "INSERT INTO abeokuta_change_log 
                             (submission_id, staff_id, change_type, old_value, new_value, change_description, detected_date) 
                             VALUES (?, ?, 'status_change', ?, ?, ?, CURDATE())";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([
                        $submissionId,
                        $staffId,
                        $snapshotEmp['status'],
                        $currentEmp['STATUSCD'],
                        "Status changed: {$snapshotEmp['status']} → {$currentEmp['STATUSCD']}"
                    ]);
                }
                
                // Check promotions
                if ($currentEmp['GRADE'] !== $snapshotEmp['grade'] || $currentEmp['STEP'] !== $snapshotEmp['step']) {
                    $query = "INSERT INTO abeokuta_change_log 
                             (submission_id, staff_id, change_type, old_value, new_value, change_description, detected_date) 
                             VALUES (?, ?, 'promotion', ?, ?, ?, CURDATE())";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([
                        $submissionId,
                        $staffId,
                        "{$snapshotEmp['grade']}/{$snapshotEmp['step']}",
                        "{$currentEmp['GRADE']}/{$currentEmp['STEP']}",
                        "Promotion: {$snapshotEmp['grade']}/{$snapshotEmp['step']} → {$currentEmp['GRADE']}/{$currentEmp['STEP']}"
                    ]);
                }
            }
        }
        
        // Record variance analysis
        recordVarianceAnalysis($submissionId, $submission);
        
        return true;
    } catch(Exception $e) {
        throw $e;
    }
}

// Function to get current employee data
function getCurrentEmployeeData($periodId) {
    global $conn;
    
    $query = "SELECT DISTINCT 
                tm.staff_id,
                ms.NAME,
                ms.DEPTCD,
                ms.GRADE,
                ms.STEP,
                ms.STATUSCD,
                SUM(tm.allow) as gross_allowance
              FROM tbl_master tm
              JOIN master_staff ms ON ms.staff_id = tm.staff_id AND ms.period = tm.period
              WHERE tm.period = ? AND tm.allow > 0
              GROUP BY tm.staff_id, ms.NAME, ms.DEPTCD, ms.GRADE, ms.STEP, ms.STATUSCD";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$periodId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to record variance analysis
function recordVarianceAnalysis($submissionId, $submission) {
    global $conn;
    
    $currentGross = calculateGrossAsOfDate($submission['period_id'], date('Y-m-d'));
    $variance = $currentGross - $submission['submitted_gross'];
    $variancePercentage = $submission['submitted_gross'] > 0 ? ($variance / $submission['submitted_gross']) * 100 : 0;
    
    // Get change counts
    $query = "SELECT 
                SUM(CASE WHEN change_type = 'new_employee' THEN 1 ELSE 0 END) as new_employees,
                SUM(CASE WHEN change_type = 'departed_employee' THEN 1 ELSE 0 END) as departed_employees,
                SUM(CASE WHEN change_type = 'status_change' THEN 1 ELSE 0 END) as status_changes,
                SUM(CASE WHEN change_type = 'promotion' THEN 1 ELSE 0 END) as promotions
              FROM abeokuta_change_log 
              WHERE submission_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$submissionId]);
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Insert variance history
    $query = "INSERT INTO abeokuta_variance_history 
             (submission_id, analysis_date, submitted_gross, current_gross, variance_amount, 
              variance_percentage, new_employees_count, departed_employees_count, 
              status_changes_count, promotions_count) 
             VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        $submissionId,
        $submission['submitted_gross'],
        $currentGross,
        $variance,
        $variancePercentage,
        $counts['new_employees'] ?? 0,
        $counts['departed_employees'] ?? 0,
        $counts['status_changes'] ?? 0,
        $counts['promotions'] ?? 0
    ]);
}

// Function to get change history
function getChangeHistory($submissionId) {
    global $conn;
    
    $query = "SELECT * FROM abeokuta_change_log 
              WHERE submission_id = ? 
              ORDER BY detected_date DESC, created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute([$submissionId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

// Function to calculate variance data with history
function calculateVarianceDataWithHistory($periodId, $submissions) {
    global $conn;
    
    // Get actual gross for the period
    $actualGross = calculateGrossAsOfDate($periodId, date('Y-m-d'));
    
    // Get the latest submission
    $latestSubmission = $submissions[0];
    $submittedGross = $latestSubmission['submitted_gross'];
    
    // Calculate variance
    $variance = $actualGross - $submittedGross;
    
    // Get variance history
    $query = "SELECT * FROM abeokuta_variance_history 
              WHERE submission_id = ? 
              ORDER BY analysis_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute([$latestSubmission['id']]);
    $varianceHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'submitted_gross' => $submittedGross,
        'actual_gross' => $actualGross,
        'variance' => $variance,
        'submission_date' => $latestSubmission['submission_date'],
        'variance_history' => $varianceHistory
    ];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abeokuta Variance Tracking - Enhanced</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-100 font-sans">
    <?php include('header.php'); ?>

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <?php include('sidebar.php'); ?>

        <!-- Main Content -->
        <div class="flex-1 p-6">
            <div class="container mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-chart-line mr-2"></i>Abeokuta Variance Tracking - Enhanced
            </h1>
            <p class="text-gray-600">Track gross salary submissions with detailed snapshots and change history</p>
        </div>

        <!-- Period Selection -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Select Period</h2>
            <form method="GET" class="flex gap-4 items-end">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Period</label>
                    <select name="period"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select Period</option>
                        <?php foreach ($periods as $period): ?>
                        <option value="<?php echo $period['periodId']; ?>"
                            <?php echo $currentPeriod == $period['periodId'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($period['description'] . ' ' . $period['periodYear']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold shadow transition">
                    <i class="fas fa-search mr-2"></i>Load Period
                </button>
            </form>
        </div>

        <?php if ($currentPeriod): ?>
        <!-- Submission Form -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Submit Gross to Abeokuta</h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="submit_gross">
                <input type="hidden" name="period_id" value="<?php echo $currentPeriod; ?>">

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Submission Date</label>
                        <input type="date" name="submission_date"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="flex items-end">
                        <button type="submit"
                            class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-semibold shadow transition w-full">
                            <i class="fas fa-paper-plane mr-2"></i>Submit Gross with Snapshot
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Submissions History -->
        <?php if (!empty($submissions)): ?>
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Submission History</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-2 px-4 text-left">Submission Date</th>
                            <th class="py-2 px-4 text-left">Submitted Gross</th>
                            <th class="py-2 px-4 text-left">Snapshot Count</th>
                            <th class="py-2 px-4 text-left">Changes Detected</th>
                            <th class="py-2 px-4 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-2 px-4">
                                <?php echo date('M d, Y', strtotime($submission['submission_date'])); ?></td>
                            <td class="py-2 px-4 font-semibold text-green-700">
                                ₦<?php echo number_format($submission['submitted_gross']); ?>
                            </td>
                            <td class="py-2 px-4">
                                <span class="px-2 py-1 rounded text-xs font-bold bg-blue-100 text-blue-800">
                                    <?php echo $submission['snapshot_count']; ?> employees
                                </span>
                            </td>
                            <td class="py-2 px-4">
                                <span class="px-2 py-1 rounded text-xs font-bold bg-orange-100 text-orange-800">
                                    <?php echo $submission['change_count']; ?> changes
                                </span>
                            </td>
                            <td class="py-2 px-4">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="analyze_changes">
                                    <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                    <button type="submit"
                                        class="bg-orange-600 hover:bg-orange-700 text-white px-3 py-1 rounded text-xs font-semibold">
                                        <i class="fas fa-sync mr-1"></i>Analyze Changes
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Variance Analysis with History -->
        <?php if ($varianceData): ?>
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Variance Analysis with History</h2>

            <div class="grid md:grid-cols-3 gap-4 mb-6">
                <div class="bg-blue-50 rounded-lg p-4 text-center">
                    <div class="text-lg font-bold text-blue-700">
                        ₦<?php echo number_format($varianceData['submitted_gross']); ?>
                    </div>
                    <div class="text-sm text-blue-600">Submitted Gross</div>
                </div>
                <div class="bg-green-50 rounded-lg p-4 text-center">
                    <div class="text-lg font-bold text-green-700">
                        ₦<?php echo number_format($varianceData['actual_gross']); ?>
                    </div>
                    <div class="text-sm text-green-600">Actual Gross</div>
                </div>
                <div
                    class="bg-<?php echo $varianceData['variance'] >= 0 ? 'green' : 'red'; ?>-50 rounded-lg p-4 text-center">
                    <div
                        class="text-lg font-bold text-<?php echo $varianceData['variance'] >= 0 ? 'green' : 'red'; ?>-700">
                        ₦<?php echo number_format($varianceData['variance']); ?>
                    </div>
                    <div class="text-sm text-<?php echo $varianceData['variance'] >= 0 ? 'green' : 'red'; ?>-600">
                        Variance</div>
                </div>
            </div>

            <!-- Variance History Chart -->
            <?php if (!empty($varianceData['variance_history'])): ?>
            <div class="mt-6">
                <h3 class="text-md font-semibold text-gray-800 mb-3">Variance History</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-2 px-4 text-left">Analysis Date</th>
                                <th class="py-2 px-4 text-left">Current Gross</th>
                                <th class="py-2 px-4 text-left">Variance</th>
                                <th class="py-2 px-4 text-left">New Employees</th>
                                <th class="py-2 px-4 text-left">Departed</th>
                                <th class="py-2 px-4 text-left">Status Changes</th>
                                <th class="py-2 px-4 text-left">Promotions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($varianceData['variance_history'] as $history): ?>
                            <tr class="border-b">
                                <td class="py-2 px-4">
                                    <?php echo date('M d, Y', strtotime($history['analysis_date'])); ?></td>
                                <td class="py-2 px-4 font-semibold">
                                    ₦<?php echo number_format($history['current_gross']); ?></td>
                                <td class="py-2 px-4">
                                    <span
                                        class="px-2 py-1 rounded text-xs font-bold <?php echo $history['variance_amount'] >= 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        ₦<?php echo number_format($history['variance_amount']); ?>
                                    </span>
                                </td>
                                <td class="py-2 px-4"><?php echo $history['new_employees_count']; ?></td>
                                <td class="py-2 px-4"><?php echo $history['departed_employees_count']; ?></td>
                                <td class="py-2 px-4"><?php echo $history['status_changes_count']; ?></td>
                                <td class="py-2 px-4"><?php echo $history['promotions_count']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Change History -->
        <?php if (!empty($changeHistory)): ?>
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Detailed Change History</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-2 px-4 text-left">Date</th>
                            <th class="py-2 px-4 text-left">Staff ID</th>
                            <th class="py-2 px-4 text-left">Change Type</th>
                            <th class="py-2 px-4 text-left">Description</th>
                            <th class="py-2 px-4 text-left">Old Value</th>
                            <th class="py-2 px-4 text-left">New Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($changeHistory as $change): ?>
                        <tr class="border-b">
                            <td class="py-2 px-4"><?php echo date('M d, Y', strtotime($change['detected_date'])); ?>
                            </td>
                            <td class="py-2 px-4 font-mono"><?php echo htmlspecialchars($change['staff_id']); ?></td>
                            <td class="py-2 px-4">
                                <span class="px-2 py-1 rounded text-xs font-bold 
                                                <?php 
                                                $colors = [
                                                    'new_employee' => 'bg-green-100 text-green-800',
                                                    'departed_employee' => 'bg-red-100 text-red-800',
                                                    'status_change' => 'bg-yellow-100 text-yellow-800',
                                                    'promotion' => 'bg-blue-100 text-blue-800',
                                                    'allowance_change' => 'bg-purple-100 text-purple-800'
                                                ];
                                                echo $colors[$change['change_type']] ?? 'bg-gray-100 text-gray-800';
                                                ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $change['change_type'])); ?>
                                </span>
                            </td>
                            <td class="py-2 px-4"><?php echo htmlspecialchars($change['change_description']); ?></td>
                            <td class="py-2 px-4"><?php echo htmlspecialchars($change['old_value'] ?? '—'); ?></td>
                            <td class="py-2 px-4"><?php echo htmlspecialchars($change['new_value'] ?? '—'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <!-- Alerts -->
        <?php if (isset($success)): ?>
        <div
            class="fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded shadow-lg z-50">
            <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded shadow-lg z-50">
            <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
        </div>
        <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.fixed.top-4.right-4');
        alerts.forEach(alert => alert.remove());
    }, 5000);
    </script>
</body>

</html>