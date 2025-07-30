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

// Load user list and roles
$users = $App->getUsersDetails();
$roles = $App->getRoles();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Manager - Salary Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    
    <style>
        /* Ensure footer is always visible */
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .flex-1 {
            flex: 1;
        }
        
        /* Modal positioning fixes */
        #userModal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 9999;
        }
        
        /* Status badge styling */
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
            text-align: center;
            white-space: nowrap;
        }
        
        /* Ensure status badges are always visible */
        #userTable td:nth-child(4) {
            min-width: 80px;
        }
        
        #userTable td:nth-child(4) .status-badge {
            display: inline-block !important;
            visibility: visible !important;
        }
        
        /* DataTable styling improvements */
        .dataTables_wrapper {
            margin-bottom: 1rem;
        }
        
        /* Ensure proper spacing */
        .container {
            flex: 1;
        }
        
        /* Modal backdrop */
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 9998;
        }
        
        /* Prevent body scroll when modal is open */
        body.overflow-hidden {
            overflow: hidden;
        }
        
        /* Ensure SweetAlert2 appears above modal */
        .swal2-container {
            z-index: 10000 !important;
        }
        
        .swal2-popup {
            z-index: 10001 !important;
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
                    <span>Users Manager</span>
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
                    <i class="fas fa-users mr-2"></i> Users Manager
                    <small class="text-base text-gray-600 ml-2">Create & manage system users</small>
                </h1>
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <div class="mb-4 flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-800">User List</h2>
                        <div>
                            <button id="reload-button"
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                <i class="fas fa-sync-alt"></i> Reload
                            </button>
                            <button id="add-user-button"
                                class="ml-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                                <i class="fas fa-plus"></i> Add New
                            </button>
                            <button id="download-excel-button"
                                class="ml-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                <i class="fas fa-file-excel"></i> Export Excel
                            </button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table id="userTable" class="min-w-full bg-white border border-gray-200">
                            <thead>
                                <tr class="bg-gray-800 text-white">
                                    <th class="py-2 px-4">Staff ID</th>
                                    <th class="py-2 px-4">Name</th>
                                    <th class="py-2 px-4">User Type</th>
                                    <th class="py-2 px-4">Status</th>
                                    <th class="py-2 px-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                        if (isset($users['staff_id'])) $users = [$users];
                        foreach ($users as $user):
                            $status = $user['deleted'] == '1' ? 'Inactive' : 'Active';
                            $statusClass = $user['deleted'] == '1' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700';
                        ?>
                                <tr>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($user['staff_id']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($user['NAME']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($user['role_name']); ?></td>
                                    <td class="py-2 px-4" data-status="<?php echo $status; ?>"><?php echo $status; ?></td>
                                    <td class="py-2 px-4">
                                        <button class="edit-user-btn text-blue-600 hover:text-blue-900"
                                            data-staff_id="<?php echo htmlspecialchars($user['staff_id']); ?>"
                                            data-name="<?php echo htmlspecialchars($user['NAME']); ?>"
                                            data-role="<?php echo htmlspecialchars($user['role_id']); ?>"
                                            data-status="<?php echo htmlspecialchars($user['deleted']); ?>"
                                            data-email="<?php echo htmlspecialchars($user['EMAIL']); ?>">
                                            <i class="fas fa-edit"></i> Edit
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
    <!-- Edit/Add Modal -->
    <div id="userModal" class="fixed inset-0 bg-gray-500 bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4 p-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold" id="modalTitle">Add/Edit User</h2>
                <button type="button" id="closeModalButton" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="userForm" method="POST" autocomplete="off">
                <input type="hidden" name="action" id="action" value="create">
                <input type="hidden" name="staff_id" id="staff_id">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search Staff</label>
                    <input type="text" id="search" name="search" placeholder="Search for staff..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                    <input type="text" id="employee_name" name="employee_name"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" id="email" name="email"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                    <input type="text" id="username" name="username"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                    <select id="roles_id" name="roles_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        required>
                        <option value="">Select Role</option>
                        <?php foreach ($roles as $role): ?>
                        <option value="<?php echo htmlspecialchars($role['role_id']); ?>">
                            <?php echo htmlspecialchars($role['role_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="status_id" name="status_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        required>
                        <option value="0">Active</option>
                        <option value="1">Inactive</option>
                    </select>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" id="cancelBtn"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Cancel</button>
                    <button type="submit" id="saveBtn"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    $(function() {
        // Initialize DataTable with proper configuration
        var userTable = $('#userTable').DataTable({
            pageLength: 25,
            ordering: true,
            columnDefs: [
                { orderable: false, targets: 4 }, // Disable sorting on Actions column
                { 
                    targets: 3, // Status column
                    render: function(data, type, row) {
                        if (type === 'display') {
                            var statusValue = data.trim();
                            var statusClass = statusValue === 'Active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
                            return '<span class="status-badge ' + statusClass + '">' + statusValue + '</span>';
                        }
                        return data;
                    }
                }
            ],
            drawCallback: function() {
                // Ensure status badges are properly rendered after each draw
                $('#userTable tbody tr').each(function() {
                    var $row = $(this);
                    var $statusCell = $row.find('td:eq(3)'); // Status column
                    var statusValue = $statusCell.text().trim();
                    
                    if (statusValue === 'Active' || statusValue === 'Inactive') {
                        var statusClass = statusValue === 'Active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
                        $statusCell.html('<span class="status-badge ' + statusClass + '">' + statusValue + '</span>');
                    }
                });
            },
            initComplete: function() {
                // Ensure status badges are rendered after initial load
                setTimeout(function() {
                    $('#userTable tbody tr').each(function() {
                        var $row = $(this);
                        var $statusCell = $row.find('td:eq(3)'); // Status column
                        var statusValue = $statusCell.text().trim();
                        
                        if (statusValue === 'Active' || statusValue === 'Inactive') {
                            var statusClass = statusValue === 'Active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
                            $statusCell.html('<span class="status-badge ' + statusClass + '">' + statusValue + '</span>');
                        }
                    });
                }, 100);
            }
        });

        // Reload button
        $('#reload-button').click(function() {
            location.reload();
        });
        
        // Download Excel button
        $('#download-excel-button').click(function() {
            window.location.href = 'libs/export_users_excel.php';
        });

        // Add user button
        $('#add-user-button').click(function() {
            $('#modalTitle').text('Add New User');
            $('#userForm')[0].reset();
            $('#action').val('create');
            $('#staff_id').val('');
            showModal();
        });

        // Edit user button with event delegation
        $(document).on('click', '.edit-user-btn', function() {
            $('#modalTitle').text('Edit User');
            $('#action').val('update');
            $('#staff_id').val($(this).data('staff_id'));
            $('#employee_name').val($(this).data('name'));
            $('#email').val($(this).data('email'));
            $('#username').val($(this).data('staff_id'));
            $('#roles_id').val($(this).data('role'));
            $('#status_id').val($(this).data('status'));
            showModal();
        });

        // Close modal buttons
        $('#closeModalButton, #cancelBtn').click(function() {
            hideModal();
        });

        // Close modal when clicking outside
        $('#userModal').click(function(e) {
            if (e.target === this) {
                hideModal();
            }
        });

        // Autocomplete for staff search
        $("#search").autocomplete({
            source: '../searchStaff.php',
            type: 'GET',
            delay: 10,
            autoFocus: false,
            minLength: 3,
            select: function(event, ui) {
                event.preventDefault();
                $("#staff_id").val(ui.item.value);
                $('#employee_name').val(ui.item.label);
                $('#email').val(ui.item.EMAIL);
                $("#username").val(ui.item.value);
            }
        });

        // Form submission
        $('#userForm').submit(function(event) {
            event.preventDefault();
            var formData = $(this).serialize();
            
            // Show loading state
            $('#saveBtn').prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: 'libs/add_user.php',
                type: 'POST',
                dataType: 'json',
                data: formData,
                success: function(response) {
                    // Hide modal first
                    hideModal();
                    
                    // Show alert after modal is hidden
                    setTimeout(function() {
                        Swal.fire({
                            icon: response.status === 'success' ? 'success' : 'error',
                            title: response.status === 'success' ? 'Success' : 'Error',
                            text: response.message,
                            timer: response.status === 'success' ? 2000 : 0,
                            showConfirmButton: response.status !== 'success'
                        }).then((result) => {
                            if (response.status === 'success') {
                                location.reload();
                            }
                        });
                    }, 100);
                },
                error: function() {
                    // Hide modal first
                    hideModal();
                    
                    // Show error alert
                    setTimeout(function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while saving the user',
                            showConfirmButton: true
                        });
                    }, 100);
                },
                complete: function() {
                    // Reset button state
                    $('#saveBtn').prop('disabled', false).text('Save');
                }
            });
        });

        // Modal functions
        function showModal() {
            $('#userModal').removeClass('hidden').addClass('flex');
            $('body').addClass('overflow-hidden');
        }

        function hideModal() {
            $('#userModal').addClass('hidden').removeClass('flex');
            $('body').removeClass('overflow-hidden');
        }
    });
    </script>
    <?php include 'footer.php'; ?>