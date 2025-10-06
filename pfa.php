<?php
session_start();
require_once 'Connections/paymaster.php';
require_once 'header.php';

// Handle form submission for updating PFA details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_pfa') {
    $staff_id = trim($_POST['staff_id']);
    $pfa_code = trim($_POST['pfa_code']);
    $pfa_pin = trim($_POST['pfa_pin']);
    
    try {
        // Update employee PFA details
        $updateQuery = $conn->prepare('UPDATE employee SET PFACODE = ?, PFAACCTNO = ? WHERE staff_id = ?');
        $result = $updateQuery->execute([$pfa_code, $pfa_pin, $staff_id]);
        
        if ($result) {
            $_SESSION['msg'] = 'PFA details updated successfully!';
            $_SESSION['alertcolor'] = 'success';
        } else {
            $_SESSION['msg'] = 'Error updating PFA details.';
            $_SESSION['alertcolor'] = 'danger';
        }
    } catch (PDOException $e) {
        $_SESSION['msg'] = 'Database error: ' . $e->getMessage();
        $_SESSION['alertcolor'] = 'danger';
    }
    
    // Redirect to prevent form resubmission
    header('Location: pfa.php' . (isset($_GET['page']) ? '?page=' . $_GET['page'] : ''));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PFA Management - OOUTH Salary System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/dark-mode.css" rel="stylesheet">
    <script src="js/theme-manager.js"></script>
    <link href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 0;
        border: none;
        border-radius: 8px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .close:hover {
        color: #000;
    }
    </style>
</head>

<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <?php include('sidebar.php'); ?>

        <div class="flex-1 p-6">
            <div class="max-w-7xl mx-auto">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-900">
                        <i class="fas fa-pension"></i> PFA Management
                    </h1>
                </div>

                <?php if (isset($_SESSION['msg'])): ?>
                <div
                    class="mb-4 p-4 rounded-md <?php echo $_SESSION['alertcolor'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php 
                    echo $_SESSION['msg'];
                    unset($_SESSION['msg']);
                    unset($_SESSION['alertcolor']);
                    ?>
                </div>
                <?php endif; ?>

                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-2">
                            <h2 class="text-xl font-semibold text-gray-800">Employee Pension List</h2>
                            <div>
                                <button id="reload-button"
                                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                    <i class="fas fa-sync-alt"></i> Reload
                                </button>
                                <button id="export-excel-button"
                                    class="ml-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                                    <i class="fas fa-file-excel"></i> Export Excel
                                </button>
                                <button onclick="window.print()"
                                    class="ml-2 px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                                    <i class="fas fa-print"></i> Print
                                </button>
                            </div>
                        </div>
                        <div class="text-sm text-gray-600 mb-4">
                            <i class="fas fa-info-circle"></i> Click the edit button to modify PFA details
                        </div>
                    </div>

                    <!-- Search Form -->
                    <div class="mb-6">
                        <form action="pfa.php" method="get" class="flex gap-4 items-end">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Search Employee</label>
                                <input type="text" name="item" id="item"
                                    value="<?php echo htmlspecialchars($_GET['item'] ?? ''); ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter Staff Name or Staff No">
                            </div>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <?php if (isset($_GET['item'])): ?>
                            <a href="pfa.php" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                                <i class="fas fa-times"></i> Clear
                            </a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table id="pfaTable" class="min-w-full bg-white border border-gray-200">
                            <thead>
                                <tr class="bg-gray-800 text-white">
                                    <th class="py-3 px-4 text-left">Staff No</th>
                                    <th class="py-3 px-4 text-left">Names</th>
                                    <th class="py-3 px-4 text-left">Department</th>
                                    <th class="py-3 px-4 text-left">Grade/Step</th>
                                    <th class="py-3 px-4 text-left">PFA</th>
                                    <th class="py-3 px-4 text-left">PFA PIN</th>
                                    <th class="py-3 px-4 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                            $results_per_page = 100;
                            if (isset($_GET['page'])) {
                                $page = $_GET['page'];
                            } else {
                                $page = 1;
                            }

                            try {
                                $start_from = ($page - 1) * $results_per_page;
                                if (!isset($_GET['item'])) {
                                    $sql = 'SELECT tbl_dept.dept, employee.STATUSCD, tbl_pfa.PFANAME, employee.PFAACCTNO, employee.PFACODE, tbl_bank.BNAME, employee.staff_id, employee.`NAME`, employee.EMPDATE, employee.GRADE, employee.STEP, employee.ACCTNO, employee.CALLTYPE FROM employee LEFT JOIN tbl_pfa ON tbl_pfa.PFACODE = employee.PFACODE INNER JOIN tbl_bank ON tbl_bank.BCODE = employee.BCODE INNER JOIN tbl_dept ON tbl_dept.dept_id = employee.DEPTCD WHERE STATUSCD = "A" ORDER BY statuscd,staff_id ASC LIMIT ' . $start_from . ',' . $results_per_page;
                                } else {
                                    $sql = 'SELECT tbl_dept.dept, employee.STATUSCD, tbl_pfa.PFANAME, employee.PFAACCTNO, employee.PFACODE, tbl_bank.BNAME, employee.staff_id, employee.`NAME`, employee.EMPDATE, employee.GRADE, employee.STEP, employee.ACCTNO, employee.CALLTYPE FROM employee LEFT JOIN tbl_pfa ON tbl_pfa.PFACODE = employee.PFACODE INNER JOIN tbl_bank ON tbl_bank.BCODE = employee.BCODE INNER JOIN tbl_dept ON tbl_dept.dept_id = employee.DEPTCD WHERE staff_id = ' . $_GET['item'] . ' AND STATUSCD = "A" ORDER BY statuscd,staff_id ASC LIMIT ' . $start_from . ',' . $results_per_page;
                                }
                                $query = $conn->prepare($sql);
                                $fin = $query->execute();
                                $res = $query->fetchAll(PDO::FETCH_ASSOC);

                                foreach ($res as $row => $link) {
                                    $thisemployeealterid = $link['staff_id'];
                                    $thisemployeeNum = $link['staff_id'];
                            ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-3 px-4 font-medium">
                                        <?php echo htmlspecialchars(trim($link['staff_id'])); ?></td>
                                    <td class="py-3 px-4"><?php echo htmlspecialchars($link['NAME']); ?></td>
                                    <td class="py-3 px-4"><?php echo htmlspecialchars($link['dept']); ?></td>
                                    <td class="py-3 px-4">
                                        <?php echo htmlspecialchars($link['GRADE'] . '/' . $link['STEP']); ?></td>
                                    <td class="py-3 px-4">
                                        <?php echo htmlspecialchars($link['PFANAME'] ?? 'Not Assigned'); ?></td>
                                    <td class="py-3 px-4"><?php echo htmlspecialchars($link['PFAACCTNO'] ?? ''); ?></td>
                                    <td class="py-3 px-4">
                                        <button
                                            onclick="editPFA('<?php echo htmlspecialchars($link['staff_id']); ?>', '<?php echo htmlspecialchars($link['NAME']); ?>', '<?php echo htmlspecialchars($link['PFACODE'] ?? ''); ?>', '<?php echo htmlspecialchars($link['PFAACCTNO'] ?? ''); ?>')"
                                            class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                    </td>
                                </tr>
                                <?php
                                }
                            } catch (PDOException $e) {
                                echo '<tr><td colspan="7" class="py-3 px-4 text-red-600">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php
                if (!isset($_GET['item'])) {
                    $sql = 'SELECT count(staff_id) as "Total" FROM employee WHERE STATUSCD = "A"';
                } else {
                    $sql = 'SELECT count(staff_id) as "Total" FROM employee where staff_id = "' . $_GET['item'] . '" AND STATUSCD = "A"';
                }
                $result = $conn->query($sql);
                $row = $result->fetch();
                $total_pages = ceil($row['Total'] / $results_per_page);
                
                if ($total_pages > 1):
                ?>
                    <div class="mt-6 flex justify-center">
                        <nav class="flex items-center space-x-2">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="pfa.php?page=<?php echo $i; ?><?php echo isset($_GET['item']) ? '&item=' . htmlspecialchars($_GET['item']) : ''; ?>"
                                class="px-3 py-2 border rounded-md <?php echo $i == $page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'; ?>">
                                <?php echo $i; ?>
                            </a>
                            <?php endfor; ?>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Edit PFA Modal -->
        <div id="editPFAModal" class="modal">
            <div class="modal-content">
                <div class="bg-blue-600 text-white px-6 py-4 rounded-t-lg">
                    <span class="close text-white hover:text-gray-200">&times;</span>
                    <h2 class="text-xl font-semibold">Edit PFA Details</h2>
                </div>
                <form id="editPFAForm" method="POST" class="p-6">
                    <input type="hidden" name="action" value="update_pfa">
                    <input type="hidden" name="staff_id" id="edit_staff_id">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Employee Name</label>
                        <input type="text" id="edit_employee_name"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100" readonly>
                    </div>

                    <div class="mb-4">
                        <label for="edit_pfa_code" class="block text-sm font-medium text-gray-700 mb-2">PFA *</label>
                        <select name="pfa_code" id="edit_pfa_code"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            required>
                            <option value="">Select PFA</option>
                            <?php
                        try {
                            $pfaQuery = $conn->prepare('SELECT PFACODE, PFANAME FROM tbl_pfa ORDER BY PFANAME');
                            $pfaQuery->execute();
                            while ($pfaRow = $pfaQuery->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='{$pfaRow['PFACODE']}'>{$pfaRow['PFANAME']}</option>";
                            }
                        } catch (PDOException $e) {
                            echo '<option value="">Error loading PFA list</option>';
                        }
                        ?>
                        </select>
                    </div>

                    <div class="mb-6">
                        <label for="edit_pfa_pin" class="block text-sm font-medium text-gray-700 mb-2">PFA PIN</label>
                        <input type="text" name="pfa_pin" id="edit_pfa_pin"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Enter PFA PIN">
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button"
                            class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 close-modal">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            <i class="fas fa-save"></i> Update PFA
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    $(function() {
        // Initialize DataTable
        var pfaTable = $('#pfaTable').DataTable({
            pageLength: 25,
            ordering: true,
            responsive: true,
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ]
        });

        // Reload button
        $('#reload-button').click(function() {
            location.reload();
        });

        // Export Excel button
        $('#export-excel-button').click(function() {
            pfaTable.button('excel').trigger();
        });

        // Autocomplete for staff search
        $("#item").autocomplete({
            source: 'searchStaff.php',
            type: 'POST',
            delay: 10,
            autoFocus: false,
            minLength: 1,
            select: function(event, ui) {
                event.preventDefault();
                $("#item").val(ui.item.value);
                $(this).closest('form').submit();
            }
        });

        // Focus on search field
        $('#item').focus();

        // Modal functionality
        $('.close, .close-modal').click(function() {
            $('#editPFAModal').hide();
        });

        $(window).click(function(event) {
            if (event.target == document.getElementById('editPFAModal')) {
                $('#editPFAModal').hide();
            }
        });

        // Form submission
        $('#editPFAForm').submit(function(e) {
            e.preventDefault();

            var formData = $(this).serialize();

            $.ajax({
                url: 'pfa.php',
                type: 'POST',
                data: formData,
                success: function(response) {
                    $('#editPFAModal').hide();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'PFA details updated successfully!',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(function() {
                        location.reload();
                    });
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Failed to update PFA details. Please try again.'
                    });
                }
            });
        });
    });

    function editPFA(staffId, employeeName, currentPFA, currentPIN) {
        $('#edit_staff_id').val(staffId);
        $('#edit_employee_name').val(employeeName);
        $('#edit_pfa_code').val(currentPFA);
        $('#edit_pfa_pin').val(currentPIN);
        $('#editPFAModal').show();
    }
    </script>

    <?php include('footer.php'); ?>
</body>

</html>