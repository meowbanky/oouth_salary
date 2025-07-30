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

// Load email deduction list
try {
    $query = $conn->prepare(
        'SELECT tbl_earning_deduction.edDesc, email_deductionlist.allow_id, email_deductionlist.email, email_deductionlist.bcc 
         FROM email_deductionlist 
         INNER JOIN tbl_earning_deduction ON email_deductionlist.allow_id = tbl_earning_deduction.ed_id'
    );
    $query->execute();
    $emailDeductions = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $emailDeductions = [];
    error_log("Database error: " . $e->getMessage());
}

// Load earning/deduction options for dropdown
try {
    $earningQuery = $conn->prepare('SELECT * FROM tbl_earning_deduction');
    $earningQuery->execute();
    $earnings = $earningQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $earnings = [];
    error_log("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Deduction Manager - Salary Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    
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
        #emailDeductionModal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 9999;
        }
        
        /* Ensure SweetAlert2 appears above modal */
        .swal2-container {
            z-index: 10000 !important;
        }
        
        .swal2-popup {
            z-index: 10001 !important;
        }
        
        /* DataTable styling improvements */
        .dataTables_wrapper {
            margin-bottom: 1rem;
        }
        
        /* Ensure proper spacing */
        .container {
            flex: 1;
        }
        
        /* Prevent body scroll when modal is open */
        body.overflow-hidden {
            overflow: hidden;
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
                    <span>Email Deduction Manager</span>
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
                    <i class="fas fa-envelope mr-2"></i> Email Deduction Manager
                    <small class="text-base text-gray-600 ml-2">Manage email notifications for deductions</small>
                </h1>
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <div class="mb-4 flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-800">Email Deduction List</h2>
                        <div>
                            <button id="reload-button"
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                <i class="fas fa-sync-alt"></i> Reload
                            </button>
                            <button id="add-email-deduction-button"
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
                        <table id="emailDeductionTable" class="min-w-full bg-white border border-gray-200">
                            <thead>
                                <tr class="bg-gray-800 text-white">
                                    <th class="py-2 px-4">ID</th>
                                    <th class="py-2 px-4">List Name</th>
                                    <th class="py-2 px-4">Email</th>
                                    <th class="py-2 px-4">CC Email</th>
                                    <th class="py-2 px-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($emailDeductions as $item): ?>
                                <tr>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($item['allow_id']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($item['edDesc']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($item['email']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($item['bcc']); ?></td>
                                    <td class="py-2 px-4">
                                        <button class="edit-email-deduction-btn text-blue-600 hover:text-blue-900 mr-2"
                                            data-allow_id="<?php echo htmlspecialchars($item['allow_id']); ?>"
                                            data-eddesc="<?php echo htmlspecialchars($item['edDesc']); ?>"
                                            data-email="<?php echo htmlspecialchars($item['email']); ?>"
                                            data-bcc="<?php echo htmlspecialchars($item['bcc']); ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="delete-email-deduction-btn text-red-600 hover:text-red-900"
                                            data-allow_id="<?php echo htmlspecialchars($item['allow_id']); ?>"
                                            data-eddesc="<?php echo htmlspecialchars($item['edDesc']); ?>">
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
    <div id="emailDeductionModal" class="fixed inset-0 bg-gray-500 bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4 p-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold" id="modalTitle">Add/Edit Email Deduction</h2>
                <button type="button" id="closeModalButton" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="emailDeductionForm" method="POST" autocomplete="off">
                <input type="hidden" name="action" id="action" value="create">
                <input type="hidden" name="allow_id" id="allow_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <select id="newearningcode" name="newearningcode" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                        <option value="">- - Select Earning - -</option>
                        <?php foreach ($earnings as $earning): ?>
                        <option value="<?php echo htmlspecialchars($earning['ed_id']); ?>" 
                                data-code="<?php echo htmlspecialchars($earning['edType']); ?>">
                            <?php echo htmlspecialchars($earning['ed']); ?> - <?php echo htmlspecialchars($earning['ed_id']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter email address..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">CC Email</label>
                    <input type="email" id="cc_email" name="cc_email" placeholder="Enter CC email address..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
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
        var emailDeductionTable = $('#emailDeductionTable').DataTable({
            pageLength: 25,
            ordering: true,
            columnDefs: [
                { orderable: false, targets: 4 } // Disable sorting on Actions column
            ]
        });

        // Reload button
        $('#reload-button').click(function() {
            location.reload();
        });
        
        // Download Excel button
        $('#download-excel-button').click(function() {
            window.location.href = 'libs/export_email_deductions_excel.php';
        });

        // Add email deduction button
        $('#add-email-deduction-button').click(function() {
            $('#modalTitle').text('Add New Email Deduction');
            $('#emailDeductionForm')[0].reset();
            $('#action').val('create');
            $('#allow_id').val('');
            showModal();
        });

        // Edit email deduction button with event delegation
        $(document).on('click', '.edit-email-deduction-btn', function() {
            $('#modalTitle').text('Edit Email Deduction');
            $('#action').val('update');
            $('#allow_id').val($(this).data('allow_id'));
            $('#newearningcode').val($(this).data('allow_id'));
            $('#email').val($(this).data('email'));
            $('#cc_email').val($(this).data('bcc'));
            showModal();
        });

        // Delete email deduction button with event delegation
        $(document).on('click', '.delete-email-deduction-btn', function() {
            const allowId = $(this).data('allow_id');
            const edDesc = $(this).data('eddesc');
            
            Swal.fire({
                title: 'Are you sure?',
                text: `Do you want to delete email deduction for "${edDesc}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Deleting...',
                        text: 'Please wait while we delete the email deduction.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    $.ajax({
                        url: 'libs/manage_email_deduction.php',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'delete',
                            allow_id: allowId
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: response.status === 'success' ? 'success' : 'error',
                                title: response.status === 'success' ? 'Deleted!' : 'Error',
                                text: response.message,
                                timer: response.status === 'success' ? 2000 : 0,
                                showConfirmButton: response.status !== 'success'
                            }).then((result) => {
                                if (response.status === 'success') {
                                    location.reload();
                                }
                            });
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occurred while deleting the email deduction',
                                showConfirmButton: true
                            });
                        }
                    });
                }
            });
        });

        // Close modal buttons
        $('#closeModalButton, #cancelBtn').click(function() {
            hideModal();
        });

        // Close modal when clicking outside
        $('#emailDeductionModal').click(function(e) {
            if (e.target === this) {
                hideModal();
            }
        });

        // Form submission
        $('#emailDeductionForm').submit(function(event) {
            event.preventDefault();
            var formData = $(this).serialize();
            
            // Show loading state
            $('#saveBtn').prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: 'libs/manage_email_deduction.php',
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
                            text: 'An error occurred while saving the email deduction',
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
            $('#emailDeductionModal').removeClass('hidden').addClass('flex');
            $('body').addClass('overflow-hidden');
        }

        function hideModal() {
            $('#emailDeductionModal').addClass('hidden').removeClass('flex');
            $('body').removeClass('overflow-hidden');
        }
    });
    </script>
    <?php include 'footer.php'; ?>
</body>

</html>