<?php
ini_set('max_execution_time', 300);
require_once 'Connections/paymaster.php';
include_once('classes/model.php');
require_once 'libs/App.php';
$App = new App();
$App->checkAuthentication();
require_once 'libs/middleware.php';
checkPermission();

session_start();

// Restrict to logged-in users
if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) === '') {
    header("Location: index.php");
    exit;
}

$results_per_page = 100;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$start_from = ($page - 1) * $results_per_page;

// Count total records
try {
    $sql = "SELECT COUNT(*) AS Total FROM employee WHERE STATUSCD = 'A'";
    if (isset($_GET['item']) && is_numeric($_GET['item'])) {
        $sql = "SELECT COUNT(*) AS Total FROM employee WHERE STATUSCD = 'A' AND staff_id = :staff_id";
    }
    $stmt = $conn->prepare($sql);
    if (isset($_GET['item']) && is_numeric($_GET['item'])) {
        $stmt->bindParam(':staff_id', $_GET['item'], PDO::PARAM_INT);
    }
    $stmt->execute();
    $total_records = $stmt->fetchColumn();
    $total_pages = ceil($total_records / $results_per_page);
} catch (PDOException $e) {
    error_log("Count error: " . $e->getMessage());
    $total_pages = 1;
}

// Fetch employee data
try {
    $sql = "SELECT employee.`NAME`, employee.EMAIL, employee.staff_id, tbl_dept.dept 
            FROM employee 
            INNER JOIN tbl_dept ON employee.DEPTCD = tbl_dept.dept_id 
            WHERE employee.STATUSCD = 'A'";
    if (isset($_GET['item']) && is_numeric($_GET['item'])) {
        $sql .= " AND employee.staff_id = :staff_id";
    }
    $sql .= " ORDER BY employee.`NAME` ASC LIMIT :start_from, :results_per_page";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':start_from', $start_from, PDO::PARAM_INT);
    $stmt->bindParam(':results_per_page', $results_per_page, PDO::PARAM_INT);
    if (isset($_GET['item']) && is_numeric($_GET['item'])) {
        $stmt->bindParam(':staff_id', $_GET['item'], PDO::PARAM_INT);
    }
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Query error: " . $e->getMessage());
    $employees = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Email - Salary Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.tailwindcss.min.css" rel="stylesheet">
    <link href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <style>
        /* Fallback CSS for DataTables if CDN fails */
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
                    <span>Edit Email</span>
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
                    <i class="fas fa-table mr-2"></i> Edit Email
                </h1>

                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <form id="add_item_form" action="edit_email.php" method="get" class="flex items-center space-x-4">
                        <input type="text" name="item" id="item" placeholder="Enter Staff Name or Staff No" class="w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-600">
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"><i class="fas fa-search"></i> Search</button>
                    </form>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold text-gray-800">Salary Table <span class="text-blue-600 text-sm">Total Employees: <?php echo $total_records; ?></span></h2>
                        <button onclick="window.print()" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700"><i class="fas fa-print"></i> Print</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table id="employeeTable" class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Staff No</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($employees as $employee): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($employee['staff_id']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap uppercase"><?php echo htmlspecialchars($employee['NAME']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($employee['dept']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap email-cell" data-staff-id="<?php echo htmlspecialchars($employee['staff_id']); ?>">
                                            <?php echo htmlspecialchars($employee['EMAIL'] ?: ''); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <button class="edit-email text-blue-600 hover:text-blue-800" data-staff-id="<?php echo htmlspecialchars($employee['staff_id']); ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <nav class="mt-4" aria-label="Page navigation">
                        <ul class="flex justify-center space-x-2">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li>
                                    <a href="edit_email.php?page=<?php echo $i; ?><?php echo isset($_GET['item']) ? '&item=' . urlencode($_GET['item']) : ''; ?>" 
                                       class="px-3 py-2 rounded-md <?php echo $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Check if jQuery UI Autocomplete is available
            if (!$.fn.autocomplete) {
                console.error('jQuery UI Autocomplete is not loaded. Falling back to basic search.');
                $('#item').on('keypress', function(e) {
                    if (e.which === 13) { // Enter key
                        $('#add_item_form').submit();
                    }
                });
            } else {
                // Initialize Autocomplete
                $('#item').autocomplete({
                    source: 'searchStaff.php',
                    minLength: 1,
                    delay: 100,
                    select: function(event, ui) {
                        event.preventDefault();
                        $('#item').val(ui.item.value);
                        $('#add_item_form').submit();
                    }
                }).autocomplete('instance')._renderItem = function(ul, item) {
                    return $('<li>').append('<div>').text(item.label).appendTo(ul);
                };
            }

            // Initialize DataTable
            try {
                $('#employeeTable').DataTable({
                    responsive: false,
                    pageLength: 100,
                    searching: false, // Disable DataTables search to use custom form
                    ordering: true,
                    columnDefs: [
                        { orderable: false, targets: 4 } // Disable sorting on Action column
                    ]
                });
            } catch (e) {
                console.error('DataTable initialization failed:', e);
            }

            // Focus on search input
            $('#item').focus();

            // Handle edit email
            $('.edit-email').on('click', function() {
                const staffId = $(this).data('staff-id');
                const emailCell = $(this).closest('tr').find('.email-cell');
                const currentEmail = emailCell.text().trim();
                
                Swal.fire({
                    title: 'Edit Email',
                    input: 'email',
                    inputValue: currentEmail,
                    showCancelButton: true,
                    confirmButtonText: 'Save',
                    cancelButtonText: 'Cancel',
                    inputValidator: function(value) {
                        if (!value) return 'Email is required!';
                        if (!/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,})+$/.test(value)) {
                            return 'Please enter a valid email!';
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'emailTable_edit.php',
                            method: 'POST',
                            data: { id: staffId, email: result.value,action: 'edit' },
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    emailCell.text(result.value);
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: 'Email updated successfully.',
                                        timer: 1500,
                                        showConfirmButton: false
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: response.message || 'Failed to update email.'
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'An error occurred while updating the email.'
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