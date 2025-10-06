<?php
ini_set('max_execution_time', 300);
require_once 'Connections/paymaster.php';
require_once 'classes/model.php';
require_once 'libs/App.php';
require_once 'libs/middleware.php';

$App = new App();
$App->checkAuthentication();
checkPermission();

session_start();

// Restrict to admins
if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) === '' || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

$currentYear = date('Y');
$currentMonth = date('n');
$months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

// Fetch pay periods
try {
    $stmt = $conn->prepare('SELECT * FROM payperiods ORDER BY periodId DESC');
    $stmt->execute();
    $periods = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Query error: " . $e->getMessage());
    $periods = [];
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Periods - Salary Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/dark-mode.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.tailwindcss.min.css" rel="stylesheet">
    <link href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <script src="js/theme-manager.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <style>
        .dataTable {
            width: 100% !important;
            border-collapse: collapse;
        }
        .dataTable th, .dataTable td {
            padding: 0.75rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .dataTable thead {
            background-color: #f9fafb;
        }
        .dataTable tbody tr:hover {
            background-color: #f3f4f6;
        }
        /* Custom pagination styling */
        .dataTables_paginate .paginate_button {
            @apply px-3 py-2 mx-1 rounded-md text-gray-700 bg-gray-200 hover:bg-blue-600 hover:text-white;
        }
        .dataTables_paginate .paginate_button.current {
            @apply bg-blue-600 text-white;
        }
        .dataTables_paginate .paginate_button.disabled {
            @apply text-gray-400 bg-gray-100 cursor-not-allowed hover:bg-gray-100 hover:text-gray-400;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <?php include 'header.php'; ?>
    <div class="flex min-h-screen">
        <?php include 'sidebar.php'; ?>
        <div class="flex-1 p-6">
            <div class="container mx-auto">
                <nav class="mb-6">
                    <a href="home.php" class="text-blue-600 hover:underline"><i class="fas fa-home"></i> Dashboard</a>
                    <span class="mx-2">/</span>
                    <span>Pay Periods</span>
                </nav>

                <?php if (isset($_SESSION['msg'])): ?>
                    <div class="bg-<?php echo $_SESSION['alertcolor']; ?>-100 text-<?php echo $_SESSION['alertcolor']; ?>-800 p-4 rounded-md mb-6 flex justify-between items-center">
                        <span><?php echo htmlspecialchars($_SESSION['msg']); ?></span>
                        <button onclick="this.parentElement.remove()" class="text-<?php echo $_SESSION['alertcolor']; ?>-600 hover:text-<?php echo $_SESSION['alertcolor']; ?>-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <?php unset($_SESSION['msg'], $_SESSION['alertcolor']); ?>
                <?php endif; ?>

                <h1 class="text-3xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-calendar mr-2"></i> Pay Periods
                    <small class="text-base text-gray-600 ml-2">Create & manage payroll periods (close current period before moving to next)</small>
                </h1>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold text-gray-800">Pay Periods</h2>
                        <button id="addNewPeriod" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            <i class="fas fa-plus-square"></i> Add New Period
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table id="periodsTable" class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Period</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($periods as $period): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($period['periodId']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($period['description'] . ' ' . $period['periodYear']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            if ($period['active'] == 0) {
                                                echo '<span class="inline-block px-2 py-1 text-sm font-semibold text-yellow-800 bg-yellow-100 rounded">Open</span>';
                                            } elseif ($period['active'] == 1) {
                                                echo '<span class="inline-block px-2 py-1 text-sm font-semibold text-blue-800 bg-blue-100 rounded">Current Active</span>';
                                            } elseif ($period['active'] == 2) {
                                                echo '<span class="inline-block px-2 py-1 text-sm font-semibold text-red-800 bg-red-100 rounded">Closed</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($period['active'] == 1): ?>
                                                <button class="close-period text-red-600 hover:text-red-800" data-period-id="<?php echo htmlspecialchars($period['periodId']); ?>">
                                                    <i class="fas fa-times-circle"></i> Close Period
                                                </button>
                                            <?php elseif ($period['active'] == 2): ?>
                                                <button class="reactivate-period text-yellow-600 hover:text-yellow-800" data-period-id="<?php echo htmlspecialchars($period['periodId']); ?>">
                                                    <i class="fas fa-eye"></i> View/Re-activate
                                                </button>
                                            <?php else: ?>
                                                <button class="edit-period text-gray-600 hover:text-gray-800" disabled>
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            try {
                $('#periodsTable').DataTable({
                    responsive: false,
                    pageLength: 50,
                    searching: false,
                    ordering: true,
                    order: [[0, 'desc']], // Sort Payment Period (column 0) in descending order
                    columnDefs: [
                        { orderable: false, targets: 2 } // Disable sorting on Actions
                    ]
                });
            } catch (e) {
                console.error('DataTable initialization failed:', e);
            }

            // Add New Period
            $('#addNewPeriod').on('click', function() {
                Swal.fire({
                    title: 'Add New Payment Period',
                    html: `
                        <form id="addPeriodForm">
                            <div class="mb-4 text-left">
                                <label class="block text-sm font-medium text-gray-700">Description</label>
                                <select name="perioddesc" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                                    <?php for ($monthNumber = $currentMonth; $monthNumber <= 12; $monthNumber++): 
                                        $currentMonthIndex = $monthNumber - 1;
                                        $nextMonthIndex = ($currentMonthIndex + 1) % 12;
                                        ?>
                                        <option value="<?php echo $months[$currentMonthIndex]; ?>"><?php echo $months[$currentMonthIndex]; ?></option>
                                        <option value="<?php echo $months[$nextMonthIndex]; ?>"><?php echo $months[$nextMonthIndex]; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="mb-4 text-left">
                                <label class="block text-sm font-medium text-gray-700">Year</label>
                                <select name="periodyear" class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                                    <option value="<?php echo $currentYear; ?>"><?php echo $currentYear; ?></option>
                                    <option value="<?php echo $currentYear + 1; ?>"><?php echo $currentYear + 1; ?></option>
                                </select>
                            </div>
                        </form>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Create Period',
                    cancelButtonText: 'Cancel',
                    preConfirm: () => {
                        const formData = new FormData(document.getElementById('addPeriodForm'));
                        return $.ajax({
                            url: 'classes/controller.php?act=addperiod',
                            method: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            dataType: 'json'
                        }).then(response => {
                            if (response.status !== 'success') {
                                throw new Error(response.message || 'Failed to create period.');
                            }
                            return response;
                        }).catch(error => {
                            Swal.showValidationMessage(`Error: ${error.message}`);
                        });
                    }
                }).then(result => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Pay period created successfully.',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    }
                });
            });

            // Close Period
            $('.close-period').on('click', function() {
                const periodId = $(this).data('period-id');
                Swal.fire({
                    title: 'Close Current Period',
                    html: `
                        <p class="text-gray-600">Please confirm you would like to close the period below. Ensure all transactional changes and processing are complete. <b>This process is irreversible.</b></p>
                        <p class="mt-2"><b>Period:</b> <?php echo retrieveDescSingleFilter('payperiods', 'description', 'periodId', $_SESSION['currentactiveperiod']); ?> <?php echo retrieveDescSingleFilter('payperiods', 'periodYear', 'periodId', $_SESSION['currentactiveperiod']); ?></p>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Close Period',
                    cancelButtonText: 'Cancel'
                }).then(result => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'classes/controller.php?act=closeActivePeriod',
                            method: 'POST',
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: 'Period closed successfully.',
                                        timer: 1500,
                                        showConfirmButton: false
                                    }).then(() => location.reload());
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: response.message || 'Failed to close period.'
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'An error occurred while closing the period.'
                                });
                            }
                        });
                    }
                });
            });

            // Reactivate Period
            $('.reactivate-period').on('click', function() {
                const periodId = $(this).data('period-id');
                Swal.fire({
                    title: 'Re-activate Period',
                    html: `
                        <p class="text-gray-600">Please confirm you would like to reactivate this <b>CLOSED</b> period to <b>VIEW</b> data. <b>You cannot transact in this period.</b></p>
                        <p class="mt-2"><b>Period:</b> ${$(this).closest('tr').find('td:first').text()}</p>
                        <input type="hidden" name="reactivateperiodid" value="${periodId}">
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Reactivate Period',
                    cancelButtonText: 'Cancel'
                }).then(result => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'classes/controller.php?act=activateclosedperiod',
                            method: 'POST',
                            data: { reactivateperiodid: periodId },
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: 'Period reactivated successfully.',
                                        timer: 1500,
                                        showConfirmButton: false
                                    }).then(() => location.reload());
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: response.message || 'Failed to reactivate period.'
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'An error occurred while reactivating the period.'
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>