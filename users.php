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
                <div class="bg-<?php echo $_SESSION['alertcolor'] ?? 'blue'; ?>-100 text-<?php echo $_SESSION['alertcolor'] ?? 'blue'; ?>-800 p-4 rounded-md mb-6 flex justify-between items-center">
                    <span><?php echo htmlspecialchars($_SESSION['msg']); ?></span>
                    <button onclick="this.parentElement.remove()" class="text-<?php echo $_SESSION['alertcolor'] ?? 'blue'; ?>-600 hover:text-<?php echo $_SESSION['alertcolor'] ?? 'blue'; ?>-700">
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
                        <button id="reload-button" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            <i class="fas fa-sync-alt"></i> Reload
                        </button>
                        <button id="add-user-button" class="ml-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                            <i class="fas fa-plus"></i> Add New
                        </button>
                        <button id="download-excel-button" class="ml-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
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
                                <td class="py-2 px-4">
                                    <span class="inline-block rounded px-3 py-1 <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                                </td>
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
<div id="userModal" class="fixed inset-0 bg-gray-500 bg-opacity-50 hidden z-50 items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-8">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold" id="modalTitle">Add/Edit User</h2>
            <button type="button" id="closeModalButton" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="userForm" method="POST" autocomplete="off">
            <input type="hidden" name="action" id="action" value="create">
            <input type="hidden" name="staff_id" id="staff_id">
            <div class="mb-3">
            <input type="text" id="search" name="search" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" id="employee_name" name="employee_name"
                       class="input input-bordered w-full" required>
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email"
                       class="input input-bordered w-full" required>
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input type="text" id="username" name="username"
                       class="input input-bordered w-full" required>
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select id="roles_id" name="roles_id"
                        class="input input-bordered w-full" required>
                    <option value="">Select Role</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo htmlspecialchars($role['role_id']); ?>">
                            <?php echo htmlspecialchars($role['role_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status_id" name="status_id"
                        class="input input-bordered w-full" required>
                    <option value="0">Active</option>
                    <option value="1">Inactive</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 mt-5">
                <button type="button" id="cancelBtn" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Cancel</button>
                <button type="submit" id="saveBtn" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save</button>
            </div>
        </form>
    </div>
</div>
<script>
$(function() {
    $('#userTable').DataTable({ pageLength: 25, ordering: true });

    $('#reload-button').click(function() { location.reload(); });
    $('#download-excel-button').click(function() { window.location.href = 'libs/export_users_excel.php'; });

    $('#add-user-button').click(function() {
        $('#modalTitle').text('Add New User');
        $('#userForm')[0].reset();
        $('#action').val('create');
        $('#userModal').removeClass('hidden flex').addClass('flex');
    });

    $('.edit-user-btn').click(function() {
        $('#modalTitle').text('Edit User');
        $('#action').val('update');
        $('#staff_id').val($(this).data('staff_id'));
        $('#employee_name').val($(this).data('name'));
        $('#email').val($(this).data('email'));
        $('#username').val($(this).data('staff_id'));
        $('#roles_id').val($(this).data('role'));
        $('#status_id').val($(this).data('status'));
        $('#userModal').removeClass('hidden').addClass('flex');
    });

    $('#closeModalButton, #cancelBtn').click(function() {
        $('#userModal').addClass('hidden').removeClass('flex');
    });

    $("#search").autocomplete({
            source: '../searchStaff.php',
            type: 'GET',
            delay: 10,
            autoFocus: false,
            minLength: 3,
            select: function (event, ui) {
                event.preventDefault();
                $("#staff_id").val(ui.item.value);
                $('#employee_name').val(ui.item.label);
                $('#email').val(ui.item.EMAIL);
                $("#username").val(ui.item.value);
            }
    });

    $('#userForm').submit(function(event) {
        event.preventDefault();
        var formData = $(this).serialize();
        $.ajax({
            url: 'libs/add_user.php',
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
                    setTimeout(function(){ location.reload(); }, 1500);
                }
            }
        });
    });
});
</script>
<?php include 'footer.php'; ?>
