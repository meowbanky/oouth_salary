<?php
require_once '../libs/App.php';
$App = new App();
$App->checkAuthentication();

$permissions = $App->getUsersPermission();
$pages = $App->getPages();
?>

<div class="bg-white p-6 rounded-lg shadow-md">
    <div class="container mx-auto py-8">
        <div class="flex justify-between">
            <h1 class="text-2xl font-bold mb-6">Permissions Lists</h1>
            <div>
                <button id="add-employee-button" class="ml-2 mb-2 px-4 py-2 bg-green-600 text-white rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <i class="fas fa-plus"></i> Edit
                </button>
            </div>
        </div>
        <div class="overflow-x-auto font-xs">
            <table id="table-search" class="min-w-full bg-white border border-gray-200">
                <thead>
                <tr class="w-full bg-gray-800 text-white">
                    <th class="sortable py-2 px-4 border border-gray-300">Role</th>
                    <th class="sortable py-2 px-4 border border-gray-300">Page</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($permissions as $permission) {
                    echo "
                            <tr class='bg-gray-50'>
                                <td class='py-2 px-4 border border-gray-300'>{$permission['role_name']}</td>
                                <td class='py-2 px-4 border border-gray-300'>{$permission['page']}</td>
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
            <h2 class="text-xl font-bold">Edit Permissions</h2>
            <button id="closeModalButton" class="closeModalButton text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="createuserForm" method="POST">
            <div class="mb-4">
                <label for="roles_id" class="block text-sm font-medium text-gray-700">Roles</label>
                <select id="roles_id" name="roles_id" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">Select Role</option>
                    <option value="1">Admin</option>
                    <option value="2">Editor</option>
                    <option value="3">Viewer</option>
                </select>
            </div>
            <div class="mb-4">
                <h6 class="text-sm mb-2">Pages</h6>
                <div id="pages-container" class="grid grid-cols-2 gap-4">
                    <?php if ($pages) {
                        foreach ($pages as $page) {
                            ?>
                            <div class="flex items-center">
                                <input class="form-switch text-primary" role="switch" type="checkbox" name="pages[]" value="<?php echo $page['url']; ?>" id="page_<?php echo $page['url']; ?>">
                                <label for="page_<?php echo $page['url']; ?>" class="ms-1.5 ml-2 text-sm text-gray-700"><?php echo $page['name']; ?></label>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
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

        // Handle role change event
        $('#roles_id').change(function() {
            var roleId = $(this).val();
            if (roleId) {
                $.ajax({
                    type: 'POST',
                    url: 'libs/get_role_permissions.php', // PHP script to get role permissions
                    dataType: 'json',
                    data: { role_id: roleId },
                    success: function(response) {
                        if(response.status === 'success') {
                            updatePermissions(response.permissions);
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log(error);
                    }
                });
            } else {
                clearPermissions();
            }
        });

        // Update permissions checkboxes
        function updatePermissions(permissions) {
            $('#pages-container input[type="checkbox"]').each(function() {
                var pageId = $(this).val();
                if (permissions.includes(pageId)) {
                    $(this).prop('checked', true);
                } else {
                    $(this).prop('checked', false);
                }
            });
        }

        // Clear permissions checkboxes
        function clearPermissions() {
            $('#pages-container input[type="checkbox"]').prop('checked', false);
        }



        // Handle form submission
        $('#createuserForm').submit(function(event) {
            event.preventDefault();

            var formData = $(this).serialize();

            $.ajax({
                type: 'POST',
                url: 'libs/getpermission.php', // PHP script to handle form submission
                dataType: 'json',
                data: formData,
                success: function(response) {
                    if(response.status == 'success') {
                        $('#editModal').addClass('hidden');
                        displayAlert(response.message,'center', 'success');
                        $('#loadContent', window.parent.document).load('view/view_permissions.php');
                    } else {
                        displayAlert(response.message,'center', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    // Handle error response
                    console.log(error);
                }
            });
        });


        // Close Modal
        $('.closeModalButton').click(function() {
            $('#editModal').addClass('hidden');
        });

        // Open modal for adding new permission
        $('#add-employee-button').click(function() {
            $('#editModal').removeClass('hidden');
        });

    });
</script>
