<?php
require_once '../libs/App.php';
$App = new App();
$App->checkAuthentication();
$users = $App->getUsersDetails();

?>

<div class="bg-white p-6 rounded-lg shadow-md">
    <div class="container mx-auto py-8">
        <div class="flex justify-between">
            <h1 class="text-2xl font-bold mb-6">User Lists</h1>
            <div>
                <button id="reload-button" class="ml-2 mb-2 px-4 py-2 bg-green-600 text-white rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <i class="fas fa-sync-alt"></i>Reload
                </button>
                <button id="download-excel-button" class="ml-2 mb-2 px-4 py-2 bg-green-600 text-white rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <i class="fas fa-download"></i> Download Excel
                </button>
                <button id="add-employee-button" class="ml-2 mb-2 px-4 py-2 bg-green-600 text-white rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <i class="fas fa-plus"></i> Add New
                </button>
            </div>
        </div>
        <div class="overflow-x-auto font-xs">
            <table id="table-search" class="min-w-full bg-white border border-gray-200">
                <thead>
                <tr class="w-full bg-gray-800 text-white">
                    <th class="sortable py-2 px-4 border border-gray-300">Staff ID</th>
                    <th class="sortable py-2 px-4 border border-gray-300">Name</th>
                    <th class="sortable py-2 px-4 border border-gray-300">User Type</th>
                    <th class="sortable py-2 px-4 border border-gray-300">Status</th>
                    <th class="sortable py-2 px-4 border border-gray-300">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php
                if (isset($users['staff_id'])) {
                    $users = [$users];
                }
                foreach ($users as $user) {
                    $statusClass = $user['deleted'] == '0' ? 'bg-green-500' : 'bg-red-500';
                    $status = $user['deleted'] == '1' ? 'In-Active' : 'Active';
                    echo "
                        <tr class='bg-gray-50'>
                            <td class='py-2 px-4 border border-gray-300'>{$user['staff_id']}</td>
                            <td class='py-2 px-4 border border-gray-300'>{$user['NAME']}</td>
                            <td class='py-2 px-4 border border-gray-300'>{$user['role_name']}</td>
                            <td class='py-2 px-4 border border-gray-300 $statusClass text-center'>{$status}</td>
                            <td class='py-2 px-4 border border-gray-300 text-center'>
                                <div class='flex justify-center'>
                                   <button class='edit-button text-blue-500 hover:text-blue-700 mx-1' data-staff_id='{$user['staff_id']}'  data-name='{$user['NAME']}' data-role='{$user['role_id']}' data-status='{$user['deleted']}' data-email='{$user['EMAIL']}'  ><i class='fas fa-edit'></i></button>
                                  
                                     </div>
                            </td>
                        </tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="editModal" class="fixed inset-0 flex items-center justify-center bg-gray-500 bg-opacity-50 hidden">
    <div class="scrollable-content bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:max-w-lg w-full p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Edit Users</h2>
            <button id="closeModalButton" class="closeModalButton text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <form id="createuserForm" method="POST">
        <div class="mb-4">
            <label for="search" class="block text-sm font-medium text-gray-700">Search Employee</label>
            <input type="text" id="search" name="search" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            <input type="hidden" name="staff_id" id="staff_id">
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" type="text" name="email" id="email">
            <label for="employee_name" class="block text-sm font-medium text-gray-700">Name</label>
            <input type="text" readonly name="employee_name" id="employee_name" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            <label for="roles_id" class="block text-sm font-medium text-gray-700">Roles</label>
            <select id="roles_id" name="roles_id" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <option value="">Select Role</option>
                <option value="1">Admin</option>
                <option value="2">Editor</option>
                <option value="3">Viewer</option>
            </select>
            <label for="status_id" class="block text-sm font-medium text-gray-700">Status</label>
            <select id="status_id" name="status_id" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <option value="">Select Status</option>
                <option value="0">Active</option>
                <option value="1">In-Active</option>
            </select>
        </div>
        <div class="mb-4">
            <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
            <input type="text" readonly id="username" name="username" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 gap-1">
            <button type="submit" id="saveButton" class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">Save</button>
            <button type="button" class="closeModalButton mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto closebutton">Cancel</button>
        </div>
    </form>
</div>
</div>

<script>
    $(document).ready(function() {
       $("#search").focus();
        $("#search").select();
        $("#search").autocomplete({
            source: 'libs/searchstaff.php',
            type: 'POST',
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

        $('#createuserForm').submit(function(event) {
            event.preventDefault();

            var formData = $(this).serialize();

            $.ajax({
                type: 'POST',
                url: 'libs/add_user.php', // PHP script to handle form submission
                dataType: 'json',
                data: formData,
                success: function(response) {
                    if(response.status == 'success') {
                        $('#editModal').addClass('hidden');
                        // displayAlert(response.message,'center', 'success');
                        displayAlert(response.message, 'success', 5000, 'center');
                        // showSuccess(response.message);
                        $('#loadContent', window.parent.document).load('view/view_users.php');
                    }else{
                        displayAlert(response.message,'center', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    // Handle error response
                    console.log(error);
                }
            });
        });

        $('.edit-button').click(function() {
            var staff_id = $(this).data('staff_id');
            var name = $(this).data('name');
            var deleted = $(this).data('status');
            var role_id = $(this).data('role');
            var email = $(this).data('email');

            $('#staff_id').val(staff_id);
            $('#employee_name').val(name);
            $('#status_id').val(deleted);
            $('#roles_id').val(role_id);
            $('#username').val(staff_id);
            $('#email').val(email);

            $('#editModal').removeClass('hidden');
        });

        // Close Modal
        $('.closeModalButton').click(function() {
            $('#editModal').addClass('hidden');
        });

        // Close Modal
        $('#add-employee-button').click(function() {
            $('#editModal').removeClass('hidden');
        });

    })
</script>