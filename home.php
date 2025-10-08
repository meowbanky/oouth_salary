<?php
session_start();
require_once 'Connections/paymaster.php';
require_once 'libs/App.php';
require_once 'libs/middleware.php';

$app = new App();
$app->checkAuthentication();
checkPermission();

// Fetch active payroll period
try {
    $query = $conn->prepare('SELECT periodId, description, periodYear FROM payperiods WHERE active = ? ORDER BY periodId ASC LIMIT 1');
    $query->execute([1]);
    $row = $query->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $_SESSION['currentactiveperiod'] = $row['periodId'];
        $_SESSION['activeperiodDescription'] = $row['description'] . ' ' . $row['periodYear'];
    } else {
        $_SESSION['currentactiveperiod'] = null;
        $_SESSION['activeperiodDescription'] = 'No Active Period';
    }
} catch (PDOException $e) {
    error_log("Error fetching payroll period: " . $e->getMessage());
    $_SESSION['currentactiveperiod'] = null;
    $_SESSION['activeperiodDescription'] = 'Error';
}

// Fetch dashboard analytics data
$dashboardData = [
    'total_employees' => 0,
    'total_payroll' => 0,
    'active_periods' => 0,
    'departments' => [],
    'recent_transactions' => [],
    'payroll_trend' => []
];

try {
    // Debug: Check if we have an active period
    $currentPeriod = $_SESSION['currentactiveperiod'] ?? 0;
    
    // Total employees - use tbl_master table (the main payroll table)
    $query = $conn->prepare('SELECT COUNT(DISTINCT staff_id) as total FROM tbl_master WHERE period = ? AND type = "1"');
    $query->execute([$currentPeriod]);
    $dashboardData['total_employees'] = $query->fetchColumn() ?: 0;
    
    // If no data for current period, try latest period
    if ($dashboardData['total_employees'] == 0) {
        $query = $conn->prepare('SELECT COUNT(DISTINCT staff_id) as total FROM tbl_master WHERE type = "1" ORDER BY period DESC LIMIT 1');
        $query->execute();
        $dashboardData['total_employees'] = $query->fetchColumn() ?: 0;
    }

    // Total payroll amount - calculate from tbl_master (allowances - deductions)
    $query = $conn->prepare('
        SELECT 
            SUM(CASE WHEN type = "1" THEN allow ELSE 0 END) - 
            SUM(CASE WHEN type = "2" THEN deduc ELSE 0 END) as total_payroll
        FROM tbl_master WHERE period = ?
    ');
    $query->execute([$currentPeriod]);
    $dashboardData['total_payroll'] = $query->fetchColumn() ?: 0;
    
    // If no data for current period, try latest period
    if ($dashboardData['total_payroll'] == 0) {
        $query = $conn->prepare('
            SELECT 
                SUM(CASE WHEN type = "1" THEN allow ELSE 0 END) - 
                SUM(CASE WHEN type = "2" THEN deduc ELSE 0 END) as total_payroll
            FROM tbl_master 
            WHERE period = (SELECT MAX(period) FROM tbl_master)
        ');
        $query->execute();
        $dashboardData['total_payroll'] = $query->fetchColumn() ?: 0;
    }

    // Active periods count
    $query = $conn->prepare('SELECT COUNT(*) as total FROM payperiods WHERE active = ?');
    $query->execute([1]);
    $dashboardData['active_periods'] = $query->fetchColumn() ?: 0;

    // Department breakdown - use tbl_master and employee tables
    $query = $conn->prepare('
        SELECT 
            d.dept, 
            COUNT(DISTINCT tm.staff_id) as employee_count,
            SUM(CASE WHEN tm.type = "1" THEN tm.allow ELSE 0 END) - 
            SUM(CASE WHEN tm.type = "2" THEN tm.deduc ELSE 0 END) as total_payroll
        FROM tbl_master tm
        INNER JOIN employee e ON e.staff_id = tm.staff_id
        INNER JOIN tbl_dept d ON d.dept_id = e.DEPTCD
        WHERE tm.period = ?
        GROUP BY d.dept_id, d.dept
        ORDER BY employee_count DESC
        LIMIT 10
    ');
    $query->execute([$currentPeriod]);
    $dashboardData['departments'] = $query->fetchAll(PDO::FETCH_ASSOC);
    
    // If no data for current period, try latest period
    if (empty($dashboardData['departments'])) {
        $query = $conn->prepare('
            SELECT 
                d.dept, 
                COUNT(DISTINCT tm.staff_id) as employee_count,
                SUM(CASE WHEN tm.type = "1" THEN tm.allow ELSE 0 END) - 
                SUM(CASE WHEN tm.type = "2" THEN tm.deduc ELSE 0 END) as total_payroll
            FROM tbl_master tm
            INNER JOIN employee e ON e.staff_id = tm.staff_id
            INNER JOIN tbl_dept d ON d.dept_id = e.DEPTCD
            WHERE tm.period = (SELECT MAX(period) FROM tbl_master)
            GROUP BY d.dept_id, d.dept
            ORDER BY employee_count DESC
            LIMIT 10
        ');
        $query->execute();
        $dashboardData['departments'] = $query->fetchAll(PDO::FETCH_ASSOC);
    }

            // Recent payroll periods for trend (Gross vs Net)
            $query = $conn->prepare('
                SELECT 
                    p.description, 
                    p.periodYear,
                    SUM(CASE WHEN tm.type = "1" THEN tm.allow ELSE 0 END) as gross_pay,
                    SUM(CASE WHEN tm.type = "2" THEN tm.deduc ELSE 0 END) as total_deductions,
                    SUM(CASE WHEN tm.type = "1" THEN tm.allow ELSE 0 END) - 
                    SUM(CASE WHEN tm.type = "2" THEN tm.deduc ELSE 0 END) as net_pay
                FROM payperiods p
                LEFT JOIN tbl_master tm ON tm.period = p.periodId
                WHERE p.payrollRun = 1
                GROUP BY p.periodId, p.description, p.periodYear
                ORDER BY p.periodId DESC
                LIMIT 6
            ');
            $query->execute();
            $dashboardData['payroll_trend'] = array_reverse($query->fetchAll(PDO::FETCH_ASSOC));

} catch (PDOException $e) {
    error_log("Error fetching dashboard data: " . $e->getMessage());
    
    // Fallback data if database queries fail
    $dashboardData = [
        'total_employees' => 0,
        'total_payroll' => 0,
        'active_periods' => 0,
        'departments' => [],
        'recent_transactions' => [],
        'payroll_trend' => []
    ];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Salary Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/dark-mode.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/theme-manager.js"></script>
    <style>
    .overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
    }

    .overlay-content {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
    }

    .quick-action {
        transition: transform 0.2s;
    }

    .quick-action:hover {
        transform: scale(1.05);
    }
    </style>
</head>

<body class="bg-gray-100 font-sans">
    <!-- Backup Overlay -->
    <div id="backupOverlay" class="overlay">
        <div class="overlay-content">
            <i class="fa fa-spinner fa-spin fa-3x fa-fw text-blue-600"></i>
            <p class="mt-4 text-lg">Backing up the database, please wait...</p>
        </div>
    </div>

    <!-- Header -->
    <?php include 'header.php'; ?>

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 p-6">
            <div class="container mx-auto">
                <!-- Breadcrumb -->
                <nav class="mb-6">
                    <a href="home.php" class="text-blue-600 hover:underline"><i class="fas fa-home"></i> Dashboard</a>
                </nav>

                <!-- Alerts -->
                <?php if (isset($_SESSION['msg'])): ?>
                <div
                    class="bg-<?php echo $_SESSION['alertcolor'] ?? 'blue'; ?>-100 text-<?php echo $_SESSION['alertcolor'] ?? 'blue'; ?>-800 p-4 rounded-md mb-6 flex justify-between items-center">
                    <span><?php echo htmlspecialchars($_SESSION['msg']); ?></span>
                    <button onclick="this.parentElement.remove()"
                        class="text-<?php echo $_SESSION['alertcolor'] ?? 'blue'; ?>-600 hover:text-<?php echo $_SESSION['alertcolor'] ?? 'blue'; ?>-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php unset($_SESSION['msg'], $_SESSION['alertcolor']); ?>
                <?php endif; ?>

                <!-- Dashboard Header -->
                <h1 class="text-3xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-dashboard mr-2"></i> Dashboard
                </h1>
                <h3 class="text-xl font-semibold text-blue-600 mb-8 text-center">Welcome to Salary Management System
                </h3>

                <!-- Debug Info (remove in production) -->
                <?php if (isset($_GET['debug'])): ?>
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                    <strong>Debug Info:</strong><br>
                    Current Period: <?php echo $currentPeriod; ?><br>
                    Active Period Description: <?php echo htmlspecialchars($_SESSION['activeperiodDescription']); ?><br>
                    Total Employees: <?php echo $dashboardData['total_employees']; ?><br>
                    Total Payroll: <?php echo $dashboardData['total_payroll']; ?><br>
                    Departments Count: <?php echo count($dashboardData['departments']); ?><br>
                    Payroll Trend Count: <?php echo count($dashboardData['payroll_trend']); ?><br>
                    Sample Trend Data: <?php echo json_encode(array_slice($dashboardData['payroll_trend'], 0, 2)); ?>
                </div>
                <?php endif; ?>

                <!-- KPI Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Employees -->
                    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Employees</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    <?php echo number_format($dashboardData['total_employees']); ?></p>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <i class="fas fa-users text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Total Payroll -->
                    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Payroll</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    ₦<?php echo number_format($dashboardData['total_payroll']); ?></p>
                            </div>
                            <div class="bg-green-100 p-3 rounded-full">
                                <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Active Periods -->
                    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Active Periods</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    <?php echo $dashboardData['active_periods']; ?></p>
                            </div>
                            <div class="bg-yellow-100 p-3 rounded-full">
                                <i class="fas fa-calendar-alt text-yellow-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Current Period -->
                    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Current Period</p>
                                <p class="text-sm font-bold text-gray-900 truncate">
                                    <?php echo htmlspecialchars($_SESSION['activeperiodDescription']); ?></p>
                            </div>
                            <div class="bg-purple-100 p-3 rounded-full">
                                <i class="fas fa-clock text-purple-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Payroll Trend Chart -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-chart-line mr-2 text-blue-600"></i>
                            Payroll Trend: Gross vs Net (Last 6 Periods)
                        </h3>
                        <div class="relative" style="height: 300px;">
                            <canvas id="payrollTrendChart"></canvas>
                        </div>
                    </div>

                    <!-- Department Distribution -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-chart-pie mr-2 text-green-600"></i>
                            Department Distribution
                        </h3>
                        <div class="relative" style="height: 300px;">
                            <canvas id="departmentChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Department Stats Table -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-building mr-2 text-purple-600"></i>
                        Department Statistics
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Department</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Employees</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Total Payroll</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Avg. Salary</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($dashboardData['departments'] as $dept): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($dept['dept']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo number_format($dept['employee_count']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        ₦<?php echo number_format($dept['total_payroll']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        ₦<?php echo number_format($dept['employee_count'] > 0 ? $dept['total_payroll'] / $dept['employee_count'] : 0); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <?php
                    $actions = [
                        ['href' => 'multiAdjustment.php', 'icon' => 'fa-shopping-cart', 'label' => 'Periodic Data'],
                        ['href' => 'report/index.php', 'icon' => 'fa-file-lines', 'label' => 'Reports'],
                        ['href' => 'tax.php', 'icon' => 'fa-upload', 'label' => 'Update Tax'],
                        ['href' => 'upload_grade_step.php', 'icon' => 'fa-upload', 'label' => 'Bulk Grade/Step'],
                        ['href' => 'edit_email.php', 'icon' => 'fa-envelope', 'label' => 'Update Email'],
                        ['href' => 'payperiods.php', 'icon' => 'fa-calendar', 'label' => 'Pay Periods'],
                        ['href' => 'empearnings.php', 'icon' => 'fa-credit-card', 'label' => 'Emp Earnings/Deductions'],
                        ['href' => 'edit_conhess_conmess.php', 'icon' => 'fa-table', 'label' => 'Salary Table'],
                        ['href' => 'edit_deduction_table.php', 'icon' => 'fa-table', 'label' => 'Deduction Table'],
                        ['href' => 'call_backup.php', 'icon' => 'fa-database', 'label' => 'Backup', 'id' => 'backupButton'],
                        ['href' => 'pfa.php', 'icon' => 'fa-download', 'label' => 'Pension Fund Update'],
                        ['href' => 'earningsdeductions.php', 'icon' => 'fa-money-bill', 'label' => 'Create New Deduction/Allowance'],
                        ['href' => 'users.php', 'icon' => 'fa-users', 'label' => 'Users'],
                        ['href' => 'employee.php', 'icon' => 'fa-user', 'label' => 'Employees'],
                        ['href' => 'email_deduction.php', 'icon' => 'fa-envelope', 'label' => 'Email Deduction List'],
                        ['href' => 'payprocess.php', 'icon' => 'fa-cog', 'label' => 'Process Payroll', 'accesskey' => '2'],
                    ];
                    if ($_SESSION['role'] === 'Admin') {
                        $actions[] = ['href' => '#', 'icon' => 'fa-cloud-download', 'label' => 'Delete Transaction', 'id' => 'link_deletetransaction'];
                    }
                    foreach ($actions as $action):
                    ?>
                    <a href="<?php echo $action['href']; ?>"
                        <?php echo isset($action['id']) ? 'id="' . $action['id'] . '"' : ''; ?>
                        <?php echo isset($action['accesskey']) ? 'accesskey="' . $action['accesskey'] . '"' : ''; ?>
                        class="quick-action bg-white p-6 rounded-lg shadow-md hover:bg-blue-50 text-center">
                        <i class="fas <?php echo $action['icon']; ?> text-blue-600 text-3xl mb-4"></i>
                        <p class="text-gray-700 font-medium"><?php echo $action['label']; ?></p>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Transaction Modal -->
    <div id="deleteTransactionModal"
        class="fixed inset-0 bg-gray-500 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <form id="deleteTransactionForm" method="POST" action="classes/controller.php?act=deletecurrentperiod">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800">Delete Current Payroll Transaction</h2>
                    <button type="button" onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Active Payroll Period</label>
                    <input type="text" class="w-full border border-gray-300 rounded-md p-2 bg-gray-100"
                        value="<?php echo htmlspecialchars($_SESSION['activeperiodDescription']); ?>" disabled>
                    <input type="hidden" name="activeperiodID" id="activeperiodID"
                        value="<?php echo $_SESSION['currentactiveperiod']; ?>">
                    <input type="hidden" name="activeperiodName" id="activeperiodName"
                        value="<?php echo htmlspecialchars($_SESSION['activeperiodDescription']); ?>">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeModal()"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Cancel</button>
                    <button type="submit" id="deleteTransactionButton"
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function closeModal() {
        document.getElementById('deleteTransactionModal').classList.add('hidden');
    }


    document.getElementById('link_deletetransaction')?.addEventListener('click', (e) => {
        e.preventDefault();
        document.getElementById('deleteTransactionModal').classList.remove('hidden');
    });

    document.getElementById('deleteTransactionForm')?.addEventListener('submit', (e) => {
        e.preventDefault();
        const periodName = document.getElementById('activeperiodName').value;
        Swal.fire({
            title: 'Are you sure?',
            text: `You are about to delete transactions for ${periodName}.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData(e.target);
                fetch(e.target.action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        closeModal();
                        Swal.fire({
                            icon: data.status === 'success' ? 'success' : 'error',
                            title: data.status === 'success' ? 'Success' : 'Error',
                            text: data.message || (data.status === 'success' ?
                                'Payroll transactions deleted.' :
                                'No payroll data found.'),
                        }).then(() => {
                            if (data.status === 'success' || data.status === 'error') {
                                location.reload();
                            }
                        });
                    })
                    .catch(() => {
                        closeModal();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to delete transactions.'
                        });
                    });
            }
        });
    });

    // Dashboard Charts Initialization
    document.addEventListener('DOMContentLoaded', function() {
        // Chart.js configuration for dark mode
        const isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark';
        const textColor = isDarkMode ? '#e5e7eb' : '#374151';
        const gridColor = isDarkMode ? '#4b5563' : '#e5e7eb';

        // Payroll Trend Chart (Gross vs Net)
        const payrollTrendCtx = document.getElementById('payrollTrendChart').getContext('2d');
        const payrollTrendData = <?php echo json_encode($dashboardData['payroll_trend']); ?>;

        new Chart(payrollTrendCtx, {
            type: 'line',
            data: {
                labels: payrollTrendData.map(item => item.description + ' ' + item.periodYear),
                datasets: [{
                    label: 'Gross Pay (₦)',
                    data: payrollTrendData.map(item => parseFloat(item.gross_pay) || 0),
                    borderColor: '#22c55e', // Green for gross
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    borderWidth: 3,
                    fill: false,
                    tension: 0.4
                }, {
                    label: 'Net Pay (₦)',
                    data: payrollTrendData.map(item => parseFloat(item.net_pay) || 0),
                    borderColor: '#3b82f6', // Blue for net
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 3,
                    fill: false,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                aspectRatio: 2,
                plugins: {
                    legend: {
                        labels: {
                            color: textColor
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = '₦' + context.parsed.y.toLocaleString();
                                return label + ': ' + value;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: textColor,
                            callback: function(value) {
                                return '₦' + value.toLocaleString();
                            }
                        },
                        grid: {
                            color: gridColor
                        }
                    },
                    x: {
                        ticks: {
                            color: textColor,
                            maxRotation: 45
                        },
                        grid: {
                            color: gridColor
                        }
                    }
                }
            }
        });

        // Department Distribution Chart
        const departmentCtx = document.getElementById('departmentChart').getContext('2d');
        const departmentData = <?php echo json_encode($dashboardData['departments']); ?>;

        new Chart(departmentCtx, {
            type: 'doughnut',
            data: {
                labels: departmentData.map(item => item.dept),
                datasets: [{
                    data: departmentData.map(item => item.employee_count),
                    backgroundColor: [
                        '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
                        '#06b6d4', '#84cc16', '#f97316', '#ec4899', '#6366f1'
                    ],
                    borderWidth: 2,
                    borderColor: isDarkMode ? '#1f2937' : '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                aspectRatio: 1,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: textColor,
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + context.parsed + ' employees (' +
                                    percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    });
    </script>
</body>

</html>