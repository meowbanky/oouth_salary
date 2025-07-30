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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Salary Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    </script>
</body>

</html>