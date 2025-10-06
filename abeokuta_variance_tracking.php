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
                    // Store submission
                    try {
                        $query = "INSERT INTO abeokuta_submissions (period_id, submission_date, submitted_gross, submitted_by, created_at) 
                                VALUES (?, ?, ?, ?, NOW())";
                        $stmt = $conn->prepare($query);
                        $stmt->execute([$periodId, $submissionDate, $grossAmount, $_SESSION['SESS_MEMBER_ID']]);
                        
                        $success = "Gross amount of ₦" . number_format($grossAmount) . " submitted to Abeokuta successfully!";
                    } catch(PDOException $e) {
                        $error = "Error storing submission: " . $e->getMessage();
                    }
                } else {
                    $error = "No payroll data found for the selected date.";
                }
                break;
        }
    }
}

// Get submissions for current period
$submissions = [];
if ($currentPeriod) {
    try {
        $query = "SELECT * FROM abeokuta_submissions WHERE period_id = ? ORDER BY submission_date DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$currentPeriod]);
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error = "Error fetching submissions: " . $e->getMessage();
    }
}

// Get variance data if we have submissions
$varianceData = null;
if ($currentPeriod && !empty($submissions)) {
    $varianceData = calculateVarianceData($currentPeriod, $submissions);
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

// Function to calculate variance data
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

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abeokuta Variance Tracking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="css/dark-mode.css" rel="stylesheet">
    <script src="js/theme-manager.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-100 min-h-screen">
    <?php include('header.php'); ?>

    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-chart-line mr-2"></i>Abeokuta Variance Tracking
            </h1>
            <p class="text-gray-600">Track gross salary submissions to Abeokuta and monitor changes</p>
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
                            <i class="fas fa-paper-plane mr-2"></i>Submit Gross
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
                            <th class="py-2 px-4 text-left">Submitted By</th>
                            <th class="py-2 px-4 text-left">Created At</th>
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
                            <td class="py-2 px-4"><?php echo htmlspecialchars($submission['submitted_by']); ?></td>
                            <td class="py-2 px-4">
                                <?php echo date('M d, Y H:i', strtotime($submission['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Variance Analysis -->
        <?php if ($varianceData): ?>
        <div class="bg-white rounded-xl shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Variance Analysis</h2>
                <button onclick="exportVarianceReport()"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold shadow transition">
                    <i class="fas fa-download mr-2"></i>Export Report
                </button>
            </div>

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

            <div class="text-sm text-gray-600 mb-6">
                <p><strong>Submission Date:</strong>
                    <?php echo date('M d, Y', strtotime($varianceData['submission_date'])); ?></p>
                <p><strong>Analysis Date:</strong> <?php echo date('M d, Y'); ?></p>
            </div>

            <!-- Detailed Changes -->
            <?php if (!empty($varianceData['changes'])): ?>
            <div class="space-y-6">
                <!-- New Employees -->
                <?php if (!empty($varianceData['changes']['new_employees'])): ?>
                <div class="bg-green-50 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-green-800 mb-3">
                        <i class="fas fa-user-plus mr-2"></i>New Employees
                        (<?php echo count($varianceData['changes']['new_employees']); ?>)
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-green-100">
                                <tr>
                                    <th class="py-2 px-4 text-left">Staff ID</th>
                                    <th class="py-2 px-4 text-left">Name</th>
                                    <th class="py-2 px-4 text-left">Department</th>
                                    <th class="py-2 px-4 text-left">Grade/Step</th>
                                    <th class="py-2 px-4 text-left">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($varianceData['changes']['new_employees'] as $emp): ?>
                                <tr class="border-b">
                                    <td class="py-2 px-4 font-mono"><?php echo htmlspecialchars($emp['staff_id']); ?>
                                    </td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($emp['NAME']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($emp['DEPTCD'] ?? ''); ?></td>
                                    <td class="py-2 px-4">
                                        <?php echo htmlspecialchars($emp['GRADE'] . '/' . $emp['STEP']); ?></td>
                                    <td class="py-2 px-4">
                                        <span class="px-2 py-1 rounded text-xs font-bold bg-green-100 text-green-800">
                                            <?php echo htmlspecialchars($emp['STATUSCD']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Departed Employees -->
                <?php if (!empty($varianceData['changes']['departed_employees'])): ?>
                <div class="bg-red-50 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-red-800 mb-3">
                        <i class="fas fa-user-minus mr-2"></i>Departed Employees
                        (<?php echo count($varianceData['changes']['departed_employees']); ?>)
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-red-100">
                                <tr>
                                    <th class="py-2 px-4 text-left">Staff ID</th>
                                    <th class="py-2 px-4 text-left">Name</th>
                                    <th class="py-2 px-4 text-left">Department</th>
                                    <th class="py-2 px-4 text-left">Last Grade/Step</th>
                                    <th class="py-2 px-4 text-left">Last Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($varianceData['changes']['departed_employees'] as $emp): ?>
                                <tr class="border-b">
                                    <td class="py-2 px-4 font-mono"><?php echo htmlspecialchars($emp['staff_id']); ?>
                                    </td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($emp['NAME']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($emp['DEPTCD'] ?? ''); ?></td>
                                    <td class="py-2 px-4">
                                        <?php echo htmlspecialchars($emp['GRADE'] . '/' . $emp['STEP']); ?></td>
                                    <td class="py-2 px-4">
                                        <span class="px-2 py-1 rounded text-xs font-bold bg-red-100 text-red-800">
                                            <?php echo htmlspecialchars($emp['STATUSCD']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Status Changes -->
                <?php if (!empty($varianceData['changes']['status_changes'])): ?>
                <div class="bg-yellow-50 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-yellow-800 mb-3">
                        <i class="fas fa-exchange-alt mr-2"></i>Status Changes
                        (<?php echo count($varianceData['changes']['status_changes']); ?>)
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-yellow-100">
                                <tr>
                                    <th class="py-2 px-4 text-left">Staff ID</th>
                                    <th class="py-2 px-4 text-left">Name</th>
                                    <th class="py-2 px-4 text-left">Old Status</th>
                                    <th class="py-2 px-4 text-left">New Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($varianceData['changes']['status_changes'] as $change): ?>
                                <tr class="border-b">
                                    <td class="py-2 px-4 font-mono"><?php echo htmlspecialchars($change['staff_id']); ?>
                                    </td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($change['name']); ?></td>
                                    <td class="py-2 px-4">
                                        <span class="px-2 py-1 rounded text-xs font-bold bg-gray-100 text-gray-800">
                                            <?php echo htmlspecialchars($change['old_status']); ?>
                                        </span>
                                    </td>
                                    <td class="py-2 px-4">
                                        <span class="px-2 py-1 rounded text-xs font-bold bg-yellow-100 text-yellow-800">
                                            <?php echo htmlspecialchars($change['new_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Promotions -->
                <?php if (!empty($varianceData['changes']['promotions'])): ?>
                <div class="bg-blue-50 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-blue-800 mb-3">
                        <i class="fas fa-arrow-up mr-2"></i>Promotions/Demotions
                        (<?php echo count($varianceData['changes']['promotions']); ?>)
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-blue-100">
                                <tr>
                                    <th class="py-2 px-4 text-left">Staff ID</th>
                                    <th class="py-2 px-4 text-left">Name</th>
                                    <th class="py-2 px-4 text-left">Grade Change</th>
                                    <th class="py-2 px-4 text-left">Step Change</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($varianceData['changes']['promotions'] as $promotion): ?>
                                <tr class="border-b">
                                    <td class="py-2 px-4 font-mono">
                                        <?php echo htmlspecialchars($promotion['staff_id']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($promotion['name']); ?></td>
                                    <td class="py-2 px-4">
                                        <?php if ($promotion['old_grade'] !== $promotion['new_grade']): ?>
                                        <span class="px-2 py-1 rounded text-xs font-bold bg-blue-100 text-blue-800">
                                            <?php echo htmlspecialchars($promotion['old_grade'] . ' → ' . $promotion['new_grade']); ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="text-gray-400">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-2 px-4">
                                        <?php if ($promotion['old_step'] !== $promotion['new_step']): ?>
                                        <span class="px-2 py-1 rounded text-xs font-bold bg-green-100 text-green-800">
                                            <?php echo htmlspecialchars($promotion['old_step'] . ' → ' . $promotion['new_step']); ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="text-gray-400">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
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

    <script>
    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.fixed.top-4.right-4');
        alerts.forEach(alert => alert.remove());
    }, 5000);

    function exportVarianceReport() {
        const currentPeriod = <?php echo $currentPeriod ?: 'null'; ?>;
        const latestSubmission = <?php echo !empty($submissions) ? $submissions[0]['id'] : 'null'; ?>;

        if (!currentPeriod || !latestSubmission) {
            Swal.fire({
                icon: 'error',
                title: 'Export Error',
                text: 'No submission data available for export.'
            });
            return;
        }

        // Show loading message
        Swal.fire({
            title: 'Exporting...',
            text: 'Please wait while we prepare your variance report.',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });

        // Create export URL
        const exportUrl = `abeokuta_variance_export.php?period_id=${currentPeriod}&submission_id=${latestSubmission}`;

        // Open export in new window
        const exportWindow = window.open(exportUrl, '_blank');

        // Check if window opened successfully
        if (exportWindow) {
            setTimeout(() => {
                Swal.close();
            }, 1000);
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Export Failed',
                text: 'Unable to open export window. Please check your popup blocker settings.'
            });
        }
    }
    </script>
</body>

</html>