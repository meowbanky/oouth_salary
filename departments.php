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

// Load departments list
try {
    $query = $conn->prepare('SELECT * FROM tbl_dept ORDER BY dept_id ASC');
    $query->execute();
    $departments = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $departments = [];
    error_log("Error loading departments: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments Manager - Salary Management System</title>
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
        #departmentModal {
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
                <span>Departments Manager</span>
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
                <i class="fas fa-building mr-2"></i> Departments Manager
                <small class="text-base text-gray-600 ml-2">Create & manage company departments</small>
            </h1>
            
            <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                <div class="mb-4 flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-800">Department List</h2>
                    <div>
                        <button id="reload-button" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            <i class="fas fa-sync-alt"></i> Reload
                        </button>
                        <button id="add-department-button" class="ml-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                            <i class="fas fa-plus"></i> Add New
                        </button>
                        <button id="download-excel-button" class="ml-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table id="departmentTable" class="min-w-full bg-white border border-gray-200">
                        <thead>
                            <tr class="bg-gray-800 text-white">
                                <th class="py-2 px-4">ID</th>
                                <th class="py-2 px-4">Department Name</th>
                                <th class="py-2 px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departments as $dept): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($dept['dept_id']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($dept['dept']); ?></td>
                                    <td class="py-2 px-4">
                                        <button class="edit-department-btn text-blue-600 hover:text-blue-900 mr-2"
                                            data-dept_id="<?php echo htmlspecialchars($dept['dept_id']); ?>"
                                            data-dept_name="<?php echo htmlspecialchars($dept['dept']); ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="delete-department-btn text-red-600 hover:text-red-900"
                                            data-dept_id="<?php echo htmlspecialchars($dept['dept_id']); ?>"
                                            data-dept_name="<?php echo htmlspecialchars($dept['dept']); ?>">
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

<!-- Edit/Add Modal -->
<div id="departmentModal" class="fixed inset-0 bg-gray-500 bg-opacity-50 hidden z-50 items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-8">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold" id="modalTitle">Add/Edit Department</h2>
            <button type="button" id="closeModalButton" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="departmentForm" method="POST" autocomplete="off">
            <input type="hidden" name="action" id="action" value="create">
            <input type="hidden" name="dept_id" id="dept_id">
            
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Department Name</label>
                <input type="text" id="dept_name" name="dept_name"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" 
                       required>
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
    $('#departmentTable').DataTable({ 
        pageLength: 25, 
        ordering: true,
        columnDefs: [
            { orderable: false, targets: 2 } // Disable sorting on Actions column
        ]
    });

    $('#reload-button').click(function() { 
        location.reload(); 
    });
    
    $('#download-excel-button').click(function() { 
        window.location.href = 'libs/export_departments_excel.php'; 
    });

    $('#add-department-button').click(function() {
        $('#modalTitle').text('Add New Department');
        $('#departmentForm')[0].reset();
        $('#action').val('create');
        $('#dept_id').val('');
        $('#departmentModal').removeClass('hidden').addClass('flex');
    });

    $(document).on('click', '.edit-department-btn', function() {
        $('#modalTitle').text('Edit Department');
        $('#action').val('update');
        $('#dept_id').val($(this).data('dept_id'));
        $('#dept_name').val($(this).data('dept_name'));
        $('#departmentModal').removeClass('hidden').addClass('flex');
    });

    $(document).on('click', '.delete-department-btn', function() {
        const deptId = $(this).data('dept_id');
        const deptName = $(this).data('dept_name');
        
        Swal.fire({
            title: 'Are you sure?',
            text: `Do you want to delete department "${deptName}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'libs/manage_department.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'delete',
                        dept_id: deptId
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
                            setTimeout(function(){ location.reload(); }, 1500);
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'An error occurred while deleting the department', 'error');
                    }
                });
            }
        });
    });

    $('#closeModalButton, #cancelBtn').click(function() {
        $('#departmentModal').addClass('hidden').removeClass('flex');
    });

    $('#departmentForm').submit(function(event) {
        event.preventDefault();
        var formData = $(this).serialize();
        
        $.ajax({
            url: 'libs/manage_department.php',
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
            },
            error: function() {
                Swal.fire('Error', 'An error occurred while saving the department', 'error');
            }
        });
    });
});
</script>

<?php include 'footer.php'; ?>
</body>
</html>