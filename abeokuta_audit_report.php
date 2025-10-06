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
$submissionId = $_GET['submission_id'] ?? null;
$periodId = $_GET['period_id'] ?? null;

// Get submission details
$submission = null;
if ($submissionId) {
    try {
        $query = "SELECT s.*, p.description, p.periodYear 
                  FROM abeokuta_submissions s
                  JOIN payperiods p ON s.period_id = p.periodId
                  WHERE s.id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$submissionId]);
        $submission = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error = "Error fetching submission: " . $e->getMessage();
    }
}

// Get snapshot data
$snapshots = [];
if ($submission) {
    try {
        $query = "SELECT * FROM abeokuta_snapshots WHERE submission_id = ? ORDER BY name";
        $stmt = $conn->prepare($query);
        $stmt->execute([$submissionId]);
        $snapshots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error = "Error fetching snapshots: " . $e->getMessage();
    }
}

// Get change log
$changeLog = [];
if ($submission) {
    try {
        $query = "SELECT * FROM abeokuta_change_log 
                  WHERE submission_id = ? 
                  ORDER BY detected_date DESC, created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$submissionId]);
        $changeLog = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error = "Error fetching change log: " . $e->getMessage();
    }
}

// Get variance history
$varianceHistory = [];
if ($submission) {
    try {
        $query = "SELECT * FROM abeokuta_variance_history 
                  WHERE submission_id = ? 
                  ORDER BY analysis_date DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$submissionId]);
        $varianceHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error = "Error fetching variance history: " . $e->getMessage();
    }
}

// Get all submissions for dropdown
$allSubmissions = [];
try {
    $query = "SELECT s.id, s.submission_date, s.submitted_gross, p.description, p.periodYear
              FROM abeokuta_submissions s
              JOIN payperiods p ON s.period_id = p.periodId
              ORDER BY s.submission_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $allSubmissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error fetching submissions: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abeokuta Audit Report</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="css/dark-mode.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/theme-manager.js"></script>
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
                <i class="fas fa-clipboard-list mr-2"></i>Abeokuta Audit Report
            </h1>
            <p class="text-gray-600">Detailed audit trail and compliance reporting</p>
        </div>

        <!-- Submission Selection -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Select Submission</h2>
            <form method="GET" class="flex gap-4 items-end">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Submission</label>
                    <select name="submission_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select Submission</option>
                        <?php foreach ($allSubmissions as $sub): ?>
                        <option value="<?php echo $sub['id']; ?>"
                            <?php echo $submissionId == $sub['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sub['description'] . ' ' . $sub['periodYear'] . ' - ' . date('M d, Y', strtotime($sub['submission_date'])) . ' (₦' . number_format($sub['submitted_gross']) . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold shadow transition">
                    <i class="fas fa-search mr-2"></i>Generate Report
                </button>
            </form>
        </div>

        <?php if ($submission): ?>
        <!-- Submission Summary -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Submission Summary</h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="text-sm text-blue-600 mb-1">Period</div>
                    <div class="text-lg font-bold text-blue-800">
                        <?php echo htmlspecialchars($submission['description'] . ' ' . $submission['periodYear']); ?>
                    </div>
                </div>
                <div class="bg-green-50 rounded-lg p-4">
                    <div class="text-sm text-green-600 mb-1">Submission Date</div>
                    <div class="text-lg font-bold text-green-800">
                        <?php echo date('M d, Y', strtotime($submission['submission_date'])); ?>
                    </div>
                </div>
                <div class="bg-purple-50 rounded-lg p-4">
                    <div class="text-sm text-purple-600 mb-1">Submitted Gross</div>
                    <div class="text-lg font-bold text-purple-800">
                        ₦<?php echo number_format($submission['submitted_gross']); ?>
                    </div>
                </div>
                <div class="bg-orange-50 rounded-lg p-4">
                    <div class="text-sm text-orange-600 mb-1">Snapshot Count</div>
                    <div class="text-lg font-bold text-orange-800">
                        <?php echo count($snapshots); ?> employees
                    </div>
                </div>
            </div>
        </div>

        <!-- Snapshot Data -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Employee Snapshot at Submission</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-2 px-4 text-left">Staff ID</th>
                            <th class="py-2 px-4 text-left">Name</th>
                            <th class="py-2 px-4 text-left">Department</th>
                            <th class="py-2 px-4 text-left">Grade/Step</th>
                            <th class="py-2 px-4 text-left">Status</th>
                            <th class="py-2 px-4 text-left">Gross Allowance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($snapshots as $snapshot): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-2 px-4 font-mono"><?php echo htmlspecialchars($snapshot['staff_id']); ?></td>
                            <td class="py-2 px-4"><?php echo htmlspecialchars($snapshot['name']); ?></td>
                            <td class="py-2 px-4"><?php echo htmlspecialchars($snapshot['dept'] ?? ''); ?></td>
                            <td class="py-2 px-4">
                                <?php echo htmlspecialchars($snapshot['grade'] . '/' . $snapshot['step']); ?></td>
                            <td class="py-2 px-4">
                                <span class="px-2 py-1 rounded text-xs font-bold bg-blue-100 text-blue-800">
                                    <?php echo htmlspecialchars($snapshot['status']); ?>
                                </span>
                            </td>
                            <td class="py-2 px-4 font-semibold text-green-700">
                                ₦<?php echo number_format($snapshot['gross_allowance']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-gray-100">
                        <tr class="font-semibold">
                            <td colspan="5" class="py-3 px-4 text-right">Total Snapshot Gross:</td>
                            <td class="py-3 px-4">
                                <div class="text-lg font-bold text-green-700">
                                    ₦<?php echo number_format(array_sum(array_column($snapshots, 'gross_allowance'))); ?>
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Change Log -->
        <?php if (!empty($changeLog)): ?>
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Change Log</h2>
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
                        <?php foreach ($changeLog as $change): ?>
                        <tr class="border-b">
                            <td class="py-2 px-4"><?php echo date('M d, Y H:i', strtotime($change['created_at'])); ?>
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

        <!-- Variance History -->
        <?php if (!empty($varianceHistory)): ?>
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Variance History</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-2 px-4 text-left">Analysis Date</th>
                            <th class="py-2 px-4 text-left">Current Gross</th>
                            <th class="py-2 px-4 text-left">Variance Amount</th>
                            <th class="py-2 px-4 text-left">Variance %</th>
                            <th class="py-2 px-4 text-left">New Employees</th>
                            <th class="py-2 px-4 text-left">Departed</th>
                            <th class="py-2 px-4 text-left">Status Changes</th>
                            <th class="py-2 px-4 text-left">Promotions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($varianceHistory as $history): ?>
                        <tr class="border-b">
                            <td class="py-2 px-4"><?php echo date('M d, Y', strtotime($history['analysis_date'])); ?>
                            </td>
                            <td class="py-2 px-4 font-semibold">₦<?php echo number_format($history['current_gross']); ?>
                            </td>
                            <td class="py-2 px-4">
                                <span
                                    class="px-2 py-1 rounded text-xs font-bold <?php echo $history['variance_amount'] >= 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    ₦<?php echo number_format($history['variance_amount']); ?>
                                </span>
                            </td>
                            <td class="py-2 px-4">
                                <span
                                    class="px-2 py-1 rounded text-xs font-bold <?php echo $history['variance_percentage'] >= 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo number_format($history['variance_percentage'], 2); ?>%
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
        <?php endif; ?>

        <!-- Alerts -->
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