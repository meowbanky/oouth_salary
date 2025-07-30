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

if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) === '' || ($_SESSION['role'] ?? '') !== 'Admin') {
    header("Location: index.php");
    exit;
}

// Load earnings/deductions data
try {
    $query = $conn->prepare('SELECT * FROM tbl_earning_deduction WHERE status = ? ORDER BY edType, ed_id');
    $query->execute(['Active']);
    $earningsDeductions = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $earningsDeductions = [];
    error_log("Error loading earnings/deductions: " . $e->getMessage());
}

// Helper function for label colors
function getTypeColor($type) {
    switch ($type) {
        case 'Earning':
            return 'green';
        case 'Deduction':
            return 'red';
        case 'Union Deduction':
            return 'purple';
        case 'Loan':
            return 'blue';
        default:
            return 'gray';
    }
}

function getTypeIcon($type) {
    switch ($type) {
        case 'Earning':
            return 'fas fa-plus-circle';
        case 'Deduction':
            return 'fas fa-minus-circle';
        case 'Union Deduction':
            return 'fas fa-handshake';
        case 'Loan':
            return 'fas fa-money-bill-wave';
        default:
            return 'fas fa-circle';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings & Deductions Manager - Salary Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    
    <style>
        /* Ensure SweetAlert2 appears above modal */
        .swal2-container {
            z-index: 10000 !important;
        }
        
        .swal2-popup {
            z-index: 10001 !important;
        }
        
        /* Modal positioning fixes */
        #itemModal {
            z-index: 9999;
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
                    <span>Earnings & Deductions Manager</span>
                </nav>

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

                <h1 class="text-3xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-money-bill-wave mr-2"></i> Earnings & Deductions Manager
                    <small class="text-base text-gray-600 ml-2">Manage company allowances, deductions, loans &
                        unions</small>
                </h1>

                <!-- Current Period Info -->
                <div class="bg-white p-4 rounded-lg shadow-md mb-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <i class="fas fa-calendar-alt text-blue-600 mr-2"></i>
                            <span class="text-gray-700 font-medium">Current Payroll Period:</span>
                            <span
                                class="ml-2 text-gray-900"><?php echo htmlspecialchars($_SESSION['activeperiodDescription'] ?? ''); ?></span>
                        </div>
                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                            <i class="fas fa-check-circle mr-1"></i>Open
                        </span>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <div class="mb-6 flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-800">Earnings & Deductions List</h2>
                        <div class="flex space-x-2">
                            <button id="add-earning-btn"
                                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors">
                                <i class="fas fa-plus mr-2"></i>New Earning
                            </button>
                            <button id="add-deduction-btn"
                                class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors">
                                <i class="fas fa-minus mr-2"></i>New Deduction
                            </button>
                            <button id="add-loan-btn"
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                                <i class="fas fa-money-bill-wave mr-2"></i>New Loan
                            </button>
                            <button id="add-union-btn"
                                class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition-colors">
                                <i class="fas fa-handshake mr-2"></i>New Union
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table id="earningsTable" class="min-w-full bg-white border border-gray-200">
                            <thead>
                                <tr class="bg-gray-800 text-white">
                                    <th class="py-3 px-4 text-left">Code</th>
                                    <th class="py-3 px-4 text-left">Description</th>
                                    <th class="py-3 px-4 text-left">Type</th>
                                    <th class="py-3 px-4 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($earningsDeductions as $item): 
                                $type = '';
                                switch ($item['edType']) {
                                    case 1: $type = 'Earning'; break;
                                    case 2: $type = 'Deduction'; break;
                                    case 3: $type = 'Union Deduction'; break;
                                    case 4: $type = 'Loan'; break;
                                    default: $type = 'Unknown';
                                }
                                $color = getTypeColor($type);
                                $icon = getTypeIcon($type);
                            ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($item['ed_id']); ?>
                                    </td>
                                    <td class="py-3 px-4"><?php echo htmlspecialchars($item['edDesc']); ?></td>
                                    <td class="py-3 px-4">
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-<?php echo $color; ?>-100 text-<?php echo $color; ?>-800">
                                            <i class="<?php echo $icon; ?> mr-2"></i>
                                            <?php echo $type; ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <button class="edit-item-btn text-blue-600 hover:text-blue-900 mr-3"
                                            data-id="<?php echo htmlspecialchars($item['ed_id']); ?>"
                                            data-description="<?php echo htmlspecialchars($item['edDesc']); ?>"
                                            data-type="<?php echo htmlspecialchars($item['edType']); ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="delete-item-btn text-red-600 hover:text-red-900"
                                            data-id="<?php echo htmlspecialchars($item['ed_id']); ?>"
                                            data-description="<?php echo htmlspecialchars($item['edDesc']); ?>">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
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

    <!-- Add/Edit Modal -->
    <div id="itemModal" class="fixed inset-0 bg-gray-500 bg-opacity-50 hidden z-50 items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold" id="modalTitle">Add New Item</h2>
                <button type="button" id="closeModalButton" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="itemForm" method="POST" autocomplete="off">
                <input type="hidden" name="action" id="action" value="create">
                <input type="hidden" name="ed_id" id="ed_id">
                <input type="hidden" name="ed_type" id="ed_type" value="1">

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Item Type</label>
                    <select id="itemType" name="itemType"
                        class="w-full p-3 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                        required>
                        <option value="1">Earning</option>
                        <option value="2">Deduction</option>
                        <option value="3">Union Deduction</option>
                        <option value="4">Loan</option>
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <input type="text" id="description" name="description"
                        class="w-full p-3 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Enter description" required>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" id="cancelBtn"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" id="saveBtn"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    $(function() {
        $('#earningsTable').DataTable({
            pageLength: 25,
            ordering: true,
            columnDefs: [{
                    orderable: false,
                    targets: 3
                } // Disable sorting on Actions column
            ]
        });

        // Add buttons
        $('#add-earning-btn').click(function() {
            showModal('create', 'Add New Earning', 1);
        });

        $('#add-deduction-btn').click(function() {
            showModal('create', 'Add New Deduction', 2);
        });

        $('#add-loan-btn').click(function() {
            showModal('create', 'Add New Loan', 4);
        });

        $('#add-union-btn').click(function() {
            showModal('create', 'Add New Union Deduction', 3);
        });

            // Edit buttons - using event delegation
    $(document).on('click', '.edit-item-btn', function() {
        const id = $(this).data('id');
        const description = $(this).data('description');
        const type = $(this).data('type');
        
        showModal('update', 'Edit Item', type, id, description);
    });

    // Delete buttons - using event delegation
    $(document).on('click', '.delete-item-btn', function() {
        const id = $(this).data('id');
        const description = $(this).data('description');
        
        Swal.fire({
            title: 'Are you sure?',
            text: `Do you want to delete "${description}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                deleteItem(id);
            }
        });
    });

        // Modal controls
        $('#closeModalButton, #cancelBtn').click(function() {
            hideModal();
        });

        // Form submission
        $('#itemForm').submit(function(event) {
            event.preventDefault();

            const formData = $(this).serialize();
            const action = $('#action').val();

            $.ajax({
                url: 'libs/manage_earnings_deductions.php',
                type: 'POST',
                dataType: 'json',
                data: formData,
                success: function(response) {
                    Swal.fire({
                        icon: response.status === 'success' ? 'success' : 'error',
                        title: response.status === 'success' ? 'Success' : 'Error',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });

                    if (response.status === 'success') {
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    }
                },
                error: function() {
                    Swal.fire('Error', 'An error occurred while saving the item', 'error');
                }
            });
        });

        function showModal(action, title, type, id = null, description = null) {
            $('#modalTitle').text(title);
            $('#action').val(action);
            $('#ed_type').val(type);
            $('#itemType').val(type);

            if (action === 'update' && id) {
                $('#ed_id').val(id);
                $('#description').val(description);
            } else {
                $('#ed_id').val('');
                $('#description').val('');
            }

            $('#itemModal').removeClass('hidden').addClass('flex');
        }

        function hideModal() {
            $('#itemModal').addClass('hidden').removeClass('flex');
        }

        function deleteItem(id) {
            $.ajax({
                url: 'libs/manage_earnings_deductions.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'delete',
                    ed_id: id
                },
                success: function(response) {
                    Swal.fire({
                        icon: response.status === 'success' ? 'success' : 'error',
                        title: response.status === 'success' ? 'Deleted!' : 'Error',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });

                    if (response.status === 'success') {
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    }
                },
                error: function() {
                    Swal.fire('Error', 'An error occurred while deleting the item', 'error');
                }
            });
        }
    });
    </script>

    <?php include 'footer.php'; ?>
</body>

</html>