<?php
ini_set('max_execution_time', 300);
require_once 'Connections/paymaster.php';
include_once('classes/model.php');
require_once 'libs/App.php';
$App = new App();
$App->checkAuthentication();
require_once 'libs/middleware.php';
checkPermission();

// Restrict to authenticated users
if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) === '') {
    header("Location: index.php");
    exit;
}

// Sanitize and decode search input
$searchItem = isset($_GET['item']) ? urldecode(trim($_GET['item'])) : '';

try {
    $results_per_page = 100;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $start_from = ($page - 1) * $results_per_page;

    $sql = 'SELECT ded_id,
        deductiontable.allowcode,
        tbl_earning_deduction.edDesc,
        deductiontable.ratetype,
        deductiontable.percentage,
        CONCAT(deductiontable.grade, "/", deductiontable.step) AS grade_step,
        deductiontable.`value`
    FROM
        deductiontable
        INNER JOIN tbl_earning_deduction ON deductiontable.allowcode = tbl_earning_deduction.ed_id';
    $params = [];
    if ($searchItem !== '') {
        $sql .= ' WHERE tbl_earning_deduction.edDesc = :edDesc';
        $params[':edDesc'] = $searchItem;
    }
    $sql .= ' ORDER BY edDesc, grade, step LIMIT :start, :limit';

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':start', $start_from, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $results_per_page, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $deductions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug: Log first deduction row
    error_log('First deduction row: ' . print_r($deductions[0] ?? [], true));

    // Count total for pagination and export
    $countSql = 'SELECT COUNT(*) as Total FROM deductiontable
        INNER JOIN tbl_earning_deduction ON deductiontable.allowcode = tbl_earning_deduction.ed_id' . ($searchItem !== '' ? ' WHERE tbl_earning_deduction.edDesc = :edDesc' : '');
    $countStmt = $conn->prepare($countSql);
    if ($searchItem !== '') {
        $countStmt->bindParam(':edDesc', $searchItem);
    }
    $countStmt->execute();
    $totalDeductions = $countStmt->fetchColumn();
    $total_pages = ceil($totalDeductions / $results_per_page);
    
    // Get deduction items for dropdown (edType > 1 means deductions)
    $deductionItemsStmt = $conn->prepare('SELECT ed_id, edDesc, ed FROM tbl_earning_deduction WHERE edType > 1 AND status = "Active" ORDER BY edDesc ASC');
    $deductionItemsStmt->execute();
    $deductionItems = $deductionItemsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Query error: " . $e->getMessage());
    $deductions = [];
    $totalDeductions = 0;
    $total_pages = 1;
    $deductionItems = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Deduction Table - Salary Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/dark-mode.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.tailwindcss.min.css" rel="stylesheet">
    <link href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"
        integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <script src="js/theme-manager.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"
        integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    .dataTable {
        width: 100% !important;
        border-collapse: collapse;
    }

    .dataTable th,
    .dataTable td {
        padding: 0.75rem 1.5rem;
        border-bottom: 1px solid #e5e7eb;
        font-size: 0.875rem;
    }

    .dataTable thead {
        background-color: #1E3A8A;
        color: #fff;
    }

    .dataTable tbody tr:hover {
        background-color: #f3f4f6;
    }

    .dataTables_paginate .paginate_button {
        @apply px-3 py-2 mx-1 rounded-md text-white bg-blue-600 hover: bg-blue-700 text-sm;
    }

    .dataTables_paginate .paginate_button.current {
        @apply bg-green-500 text-white;
    }

    .dataTables_paginate .paginate_button.disabled {
        @apply text-gray-400 bg-gray-100 cursor-not-allowed hover: bg-gray-100 hover:text-gray-400;
    }

    .ui-autocomplete {
        max-height: 200px;
        overflow-y: auto;
        overflow-x: hidden;
        z-index: 1000;
        border: 1px solid #e5e7eb;
        background: #fff;
        border-radius: 0.375rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .ui-autocomplete .ui-menu-item {
        padding: 0.5rem 1rem;
        cursor: pointer;
        font-size: 0.875rem;
    }

    .ui-autocomplete .ui-menu-item:hover {
        background: #f3f4f6;
    }

    .editable-input {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.375rem;
        outline: none;
        font-size: 0.875rem;
    }

    .editable-input:focus {
        border-color: #10B981;
        box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
    }

    .editable {
        cursor: pointer;
    }

    .delete-btn {
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .delete-btn:hover {
        transform: scale(1.1);
    }

    .delete-btn:active {
        transform: scale(0.95);
    }

    .sidebar {
        background-color: #fff;
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        width: 250px;
        position: absolute;
        height: 100%;
        z-index: 50;
    }

    .sidebar-show {
        display: block !important;
    }

    .main-content {
        flex: 1;
        transition: margin-left 0.3s ease;
    }

    .content-wrapper {
        display: flex;
        min-height: calc(100vh - 60px);
        /* Adjust for header height */
    }

    @media (max-width: 640px) {

        .dataTable th,
        .dataTable td {
            padding: 0.5rem;
            font-size: 0.75rem;
        }

        .dataTable-container {
            overflow-x: auto;
        }

        .dataTables_paginate {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }

        .dataTables_paginate .paginate_button {
            @apply px-2 py-1 mx-0.5 text-xs;
        }

        #add_item_form {
            flex-direction: column;
            align-items: stretch;
        }

        #add_item_form input {
            margin-bottom: 0.5rem;
        }

        #add_item_form button {
            width: 100%;
        }

        .container {
            padding: 0 1rem;
        }

        h1 {
            font-size: 1.5rem;
        }

        h2 {
            font-size: 1.25rem;
        }

        .sidebar {
            display: none;
        }

        .hamburger {
            display: block;
        }

        .main-content {
            margin-left: 0;
            padding-top: 60px;
            /* Adjust for header */
        }

        .content-wrapper {
            flex-direction: column;
        }
    }

    @media (min-width: 641px) {
        .hamburger {
            display: none;
        }

        .sidebar {
            display: block;
        }

        .main-content {
            margin-left: 250px;
            padding: 1.5rem;
            /* p-6 */
        }

        .content-wrapper {
            flex-direction: row;
        }
    }
    </style>
</head>

<body class="bg-gray-100 font-sans">
    <?php include 'header.php'; ?>
    <div class="content-wrapper">
        <button class="hamburger fixed top-4 left-4 z-[1200] md:hidden p-2 bg-blue-600 text-white rounded-md"
            aria-label="Toggle sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <nav class="sidebar" id="sidebar">
            <?php include 'sidebar.php'; ?>
        </nav>
        <div class="main-content">
            <div class="container">
                <nav class="mb-6">
                    <a href="home.php" class="text-blue-600 hover:underline"><i class="fas fa-home"></i> Dashboard</a>
                    <span class="mx-2">/</span>
                    <span>Edit Deduction Table</span>
                </nav>

                <?php if (isset($_SESSION['msg'])): ?>
                <div
                    class="bg-<?php echo $_SESSION['alertcolor']; ?>-100 text-<?php echo $_SESSION['alertcolor']; ?>-800 p-4 rounded-md mb-6 flex justify-between items-center">
                    <span><?php echo htmlspecialchars($_SESSION['msg']); ?></span>
                    <button onclick="this.parentElement.remove()"
                        class="text-<?php echo $_SESSION['alertcolor']; ?>-600 hover:text-<?php echo $_SESSION['alertcolor']; ?>-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php unset($_SESSION['msg'], $_SESSION['alertcolor']); ?>
                <?php endif; ?>

                <h1 class="text-3xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-table mr-2"></i> Deduction Table
                    <small class="text-base text-gray-600 ml-2">Edit deductions</small>
                </h1>

                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <form id="add_item_form" action="edit_deduction_table.php" method="get" class="flex items-center">
                        <div class="relative w-full max-w-md">
                            <input type="text" name="item" id="item"
                                class="w-full border border-gray-300 rounded-md p-2 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-600"
                                placeholder="Enter Deduction" value="<?php echo htmlspecialchars($searchItem); ?>">
                            <span id="ajax-loader" class="absolute right-3 top-3 hidden">
                                <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                            </span>
                        </div>
                        <button type="submit"
                            class="ml-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </form>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold text-gray-800">Deduction Table</h2>
                        <div class="flex items-center gap-4">
                            <span
                                class="inline-block px-3 py-1 text-sm font-semibold text-blue-800 bg-blue-100 rounded">
                                Total Deductions: <?php echo $totalDeductions; ?>
                            </span>
                            <button id="addDeductionBtn"
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                                <i class="fas fa-plus mr-2"></i> Add New Deduction
                            </button>
                        </div>
                    </div>
                    <div class="dataTable-container">
                        <table id="deductionTable" class="w-full dataTable">
                            <thead>
                                <tr>
                                    <th class="hidden">ID</th>
                                    <th>Allowance</th>
                                    <th>Rate Type</th>
                                    <th>Percentage</th>
                                    <th>Grade/Step</th>
                                    <th>Value</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deductions as $row): ?>
                                <tr data-id="<?php echo htmlspecialchars($row['ded_id'] ?? ''); ?>">
                                    <td class="hidden"
                                        data-id="<?php echo htmlspecialchars($row['allowcode'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($row['allowcode'] ?? ''); ?></td>
                                    <td class="uppercase"><?php echo htmlspecialchars($row['edDesc'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['ratetype'] ?? ''); ?></td>
                                    <td class="editable"
                                        data-value="<?php echo htmlspecialchars($row['percentage'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($row['percentage'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['grade_step'] ?? ''); ?></td>
                                    <td class="editable"
                                        data-value="<?php echo htmlspecialchars($row['value'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($row['value'] ?? ''); ?></td>
                                    <td></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <nav class="mt-4" aria-label="page navigation">
                        <ul class="flex justify-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li>
                                <a href="edit_deduction_table.php?page=<?php echo $i; ?><?php echo $searchItem ? '&item=' . urlencode($searchItem) : ''; ?>"
                                    class="px-3 py-2 mx-1 rounded-md <?php echo $i == $page ? 'bg-green-500 text-white' : 'bg-blue-600 text-white hover:bg-blue-700'; ?>">
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

    <!-- Add Deduction Modal -->
    <div id="addDeductionModal" class="fixed inset-0 bg-black bg-opacity-50 z-50" style="display: none;">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-2xl font-bold text-gray-800">Add New Deduction</h3>
                        <button id="closeModalBtn" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times text-2xl"></i>
                        </button>
                    </div>

                    <form id="addDeductionForm">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label for="deduction_item" class="block text-sm font-medium text-gray-700 mb-2">
                                    Deduction Item <span class="text-red-500">*</span>
                                </label>
                                <select id="deduction_item" name="allowcode" required
                                    class="w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-600">
                                    <option value="">-- Select Deduction Item --</option>
                                    <?php foreach ($deductionItems as $item): ?>
                                    <option value="<?php echo htmlspecialchars($item['ed_id']); ?>">
                                        <?php echo htmlspecialchars($item['edDesc']); ?>
                                        (<?php echo htmlspecialchars($item['ed']); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="grade" class="block text-sm font-medium text-gray-700 mb-2">
                                    Grade
                                </label>
                                <input type="text" id="grade" name="grade"
                                    class="w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-600"
                                    placeholder="e.g., 10">
                            </div>

                            <div>
                                <label for="step" class="block text-sm font-medium text-gray-700 mb-2">
                                    Step
                                </label>
                                <input type="text" id="step" name="step"
                                    class="w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-600"
                                    placeholder="e.g., 5">
                            </div>

                            <div>
                                <label for="ratetype" class="block text-sm font-medium text-gray-700 mb-2">
                                    Rate Type
                                </label>
                                <select id="ratetype" name="ratetype"
                                    class="w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-600">
                                    <option value="">-- Select Rate Type --</option>
                                    <option value="1">Fixed Amount</option>
                                    <option value="2">Percentage</option>
                                </select>
                            </div>

                            <div>
                                <label for="percentage" class="block text-sm font-medium text-gray-700 mb-2">
                                    Percentage (%)
                                </label>
                                <input type="number" id="percentage" name="percentage" min="0" max="100" step="0.01"
                                    class="w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-600"
                                    placeholder="0.00">
                            </div>

                            <div>
                                <label for="value" class="block text-sm font-medium text-gray-700 mb-2">
                                    Value
                                </label>
                                <input type="number" id="value" name="value" min="0" step="0.01"
                                    class="w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-600"
                                    placeholder="0.00">
                            </div>

                            <div class="md:col-span-2">
                                <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                                    Category
                                </label>
                                <input type="text" id="category" name="category"
                                    class="w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-600"
                                    placeholder="Optional category">
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-3">
                            <button type="button" id="cancelAddBtn"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                <i class="fas fa-save mr-2"></i> Save Deduction
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Sidebar toggle for mobile
        $('.hamburger').on('click', function(e) {
            e.stopPropagation();
            $('#sidebar').toggleClass('sidebar-show');
            console.log('Hamburger clicked, sidebar class:', $('#sidebar').attr('class'));
        });

        // Close sidebar when clicking outside on mobile
        $(document).on('click', function(e) {
            if ($(window).width() <= 640 && $('#sidebar').hasClass('sidebar-show') && !$(e.target)
                .closest('#sidebar').length && !$(e.target).hasClass('hamburger')) {
                $('#sidebar').removeClass('sidebar-show');
                console.log('Clicked outside, sidebar closed');
            }
        });

        // Log initial table data
        console.log('PHP deductions data:', <?php echo json_encode($deductions); ?>);

        // Initialize DataTable
        const table = $('#deductionTable').DataTable({
            dom: 'Bfrtip',
            pageLength: 100,
            searching: false,
            ordering: true,
            order: [
                [1, 'asc']
            ],
            columns: [{
                    data: 'allowcode',
                    visible: false
                },
                {
                    data: 'edDesc'
                },
                {
                    data: 'ratetype'
                },
                {
                    data: 'percentage',
                    className: 'editable'
                },
                {
                    data: 'grade_step'
                },
                {
                    data: 'value',
                    className: 'editable'
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return `
                            <button class="delete-btn text-red-600 hover:text-red-800 p-2 rounded transition-colors"
                                data-ded-id="${row.ded_id || ''}"
                                data-ded-name="${row.edDesc || ''}"
                                title="Delete deduction">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        `;
                    }
                }
            ],
            data: <?php echo json_encode($deductions); ?>,
            columnDefs: [{
                    targets: 0,
                    visible: false
                },
                {
                    targets: [1, 2, 4],
                    orderable: false
                }
            ],
            buttons: [{
                    extend: 'print',
                    title: 'Deduction_Table_Export'
                },
                {
                    extend: 'csv',
                    title: 'Deduction_Table_Export'
                },
                {
                    extend: 'excel',
                    title: 'Deduction_Table_Export'
                }
            ],
            drawCallback: function() {
                // Make percentage and value cells editable
                $('#deductionTable tbody td:nth-child(4), #deductionTable tbody td:nth-child(6)')
                    .addClass('editable').css('cursor', 'pointer');

                console.log('Editable cells after draw:', $('#deductionTable tbody td.editable')
                    .length);
            },
            initComplete: function() {
                console.log('DataTable columns:', this.api().columns().count());
                console.log('Editable cells:', $('#deductionTable tbody td.editable').length);
            }
        });

        // Inline editing with touch and double-click support
        $(document).on('dblclick touchstart', '#deductionTable tbody td.editable', function(e) {
            if (e.type === 'touchstart') {
                e.preventDefault();
                if (e.originalEvent.touches.length > 1) return;
            }
            const $cell = $(this);
            const $row = $cell.closest('tr');

            // Get row data from DataTables API (since DataTables recreates DOM)
            const rowData = table.row($row).data();
            if (!rowData) {
                console.error('No row data found');
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Unable to edit: Row data not found.'
                });
                return;
            }

            // Get allowcode from row data
            const allowcode = rowData.allowcode;
            const dedId = rowData.ded_id;

            // Determine which field we're editing based on column index
            // Columns: 0=allowcode(hidden), 1=edDesc, 2=ratetype, 3=percentage, 4=grade_step, 5=value
            const columnIndex = $cell.index();
            const field = columnIndex === 3 ? 'percentage' : 'value';

            // Get original value from cell text or row data
            const originalValue = $cell.data('value') || $cell.text().trim() || rowData[field];

            if (!allowcode) {
                console.error('No allowcode found', {
                    dedId: dedId,
                    rowData: rowData,
                    cellIndex: columnIndex,
                    field: field
                });
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Unable to edit: Allowcode not found in row data.'
                });
                return;
            }

            console.log('Editing deduction', {
                allowcode: allowcode,
                dedId: dedId,
                field: field,
                originalValue: originalValue,
                columnIndex: columnIndex
            });

            $cell.html(`<input type="number" class="editable-input" value="${originalValue}" />`);
            const $input = $cell.find('input');
            $input.focus();

            $input.on('blur keypress', function(e) {
                if (e.type === 'blur' || e.which === 13) {
                    const newValue = $input.val().trim();
                    if (newValue === '' || isNaN(newValue) || parseFloat(newValue) < 0) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Invalid Input',
                            text: 'Please enter a valid positive number.'
                        });
                        $cell.html(originalValue).data('value', originalValue);
                        return;
                    }
                    if (newValue !== originalValue.toString()) {
                        $.ajax({
                            url: 'ajax_edit_deduction.php',
                            type: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify({
                                data: {
                                    [dedId]: {
                                        [field]: newValue
                                    }
                                }
                            }),
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === true) {
                                    $cell.html(newValue).data('value', newValue);
                                    table.cell($cell).data(newValue).draw();
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: response.message ||
                                            'Value updated successfully.',
                                        timer: 1500,
                                        showConfirmButton: false
                                    });
                                } else {
                                    $cell.html(originalValue).data('value',
                                        originalValue);
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: response.message ||
                                            'Failed to update value.'
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('AJAX error:', xhr.status, error);
                                $cell.html(originalValue).data('value',
                                    originalValue);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'An error occurred: ' + xhr
                                        .status + ' ' + error
                                });
                            }
                        });
                    } else {
                        $cell.html(originalValue).data('value', originalValue);
                    }
                }
            });
        });

        // Delete deduction handler
        $(document).on('click', '.delete-btn', function(e) {
            e.stopPropagation();
            const $btn = $(this);
            const dedId = $btn.data('ded-id');
            const dedName = $btn.data('ded-name') || 'this deduction';

            if (!dedId) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Invalid deduction ID'
                });
                return;
            }

            // Confirm deletion
            Swal.fire({
                title: 'Are you sure?',
                text: `Do you want to delete "${dedName}"? This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Deleting...',
                        text: 'Please wait',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Send delete request
                    $.ajax({
                        url: 'ajax_delete_deduction.php',
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({
                            ded_id: dedId
                        }),
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === true) {
                                // Remove row from DataTable
                                const $row = $btn.closest('tr');
                                table.row($row).remove().draw();

                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: response.message ||
                                        'Deduction deleted successfully.',
                                    timer: 1500,
                                    showConfirmButton: false
                                });

                                // Optionally reload the page to refresh totals
                                // Or update the total count via AJAX
                                setTimeout(() => {
                                    location.reload();
                                }, 1500);
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message ||
                                        'Failed to delete deduction.'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Delete error:', xhr.status, error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occurred: ' + xhr.status +
                                    ' ' + error
                            });
                        }
                    });
                }
            });
        });

        // Add Deduction Modal handlers
        const modal = $('#addDeductionModal');
        const form = $('#addDeductionForm');

        // Open modal
        $('#addDeductionBtn').on('click', function() {
            modal.show();
            form[0].reset();
        });

        // Close modal
        $('#closeModalBtn, #cancelAddBtn').on('click', function() {
            modal.hide();
            form[0].reset();
        });

        // Close modal when clicking outside
        modal.on('click', function(e) {
            if ($(e.target).is(modal)) {
                modal.hide();
                form[0].reset();
            }
        });

        // Handle form submission
        form.on('submit', function(e) {
            e.preventDefault();

            const formData = {
                allowcode: $('#deduction_item').val(),
                grade: $('#grade').val(),
                step: $('#step').val(),
                ratetype: $('#ratetype').val(),
                percentage: $('#percentage').val(),
                value: $('#value').val(),
                category: $('#category').val()
            };

            // Validate required field
            if (!formData.allowcode) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please select a deduction item'
                });
                return;
            }

            // Show loading
            Swal.fire({
                title: 'Adding...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Submit form
            $.ajax({
                url: 'ajax_add_deduction.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                dataType: 'json',
                success: function(response) {
                    if (response.status === true) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message ||
                                'Deduction added successfully.',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        // Close modal and reset form
                        modal.hide();
                        form[0].reset();

                        // Reload page to show new deduction
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Failed to add deduction.'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Add deduction error:', xhr.status, error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred: ' + xhr.status + ' ' + error
                    });
                }
            });
        });

        // Autocomplete with space handling
        $('#item').autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: 'searchAllowDed.php',
                    type: 'POST',
                    data: {
                        term: request.term
                    },
                    dataType: 'json',
                    success: function(data) {
                        response(data);
                    },
                    error: function(xhr, status, error) {
                        console.error('Autocomplete error:', xhr.status, error);
                    }
                });
            },
            delay: 10,
            autoFocus: false,
            minLength: 1,
            select: function(event, ui) {
                event.preventDefault();
                $('#item').val(ui.item.value);
                $('#add_item_form').submit();
            },
            search: function() {
                $('#ajax-loader').removeClass('hidden');
            },
            response: function() {
                $('#ajax-loader').addClass('hidden');
            }
        }).focus();

        // Clear placeholder on click
        $('#item').on('click', function() {
            $(this).attr('placeholder', '');
        });
    });
    </script>
</body>

</html>