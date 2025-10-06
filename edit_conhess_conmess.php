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

// Restrict to authenticated users
if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) === '') {
    header("Location: index.php");
    exit;
}

// Sanitize and decode search input
$searchItem = isset($_GET['item']) ? urldecode(trim($_GET['item'])) : '';

// Fetch allowances
try {
    $results_per_page = 100;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $start_from = ($page - 1) * $results_per_page;

    $sql = 'SELECT allow_id, allocode.ADJDESC, CONCAT(allowancetable.grade, "/", allowancetable.step) AS grade_step, allowancetable.`value`
            FROM allowancetable
            INNER JOIN allocode ON allowancetable.allowcode = allocode.ADJCD';
    $params = [];
    if ($searchItem !== '') {
        $sql .= ' WHERE allocode.ADJDESC = :adjdesc';
        $params[':adjdesc'] = $searchItem;
    }
    $sql .= ' ORDER BY allow_id ASC, grade ASC, step ASC LIMIT :start, :limit';
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':start', $start_from, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $results_per_page, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $allowances = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug: Log first allowance row
    error_log('First allowance row: ' . print_r($allowances[0] ?? [], true));

    // Count total for export label and pagination
    $countSql = 'SELECT COUNT(*) as Total FROM allowancetable
            INNER JOIN allocode ON allowancetable.allowcode = allocode.ADJCD' . ($searchItem !== '' ? ' WHERE allocode.ADJDESC = :adjdesc' : '');
    $countStmt = $conn->prepare($countSql);
    if ($searchItem !== '') {
        $countStmt->bindParam(':adjdesc', $searchItem);
    }
    $countStmt->execute();
    $totalAllowances = $countStmt->fetchColumn();
    $total_pages = ceil($totalAllowances / $results_per_page);
} catch (PDOException $e) {
    error_log("Query error: " . $e->getMessage());
    $allowances = [];
    $totalAllowances = 0;
    $total_pages = 1;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Salary Table - Salary Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/dark-mode.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"
        integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.tailwindcss.min.css" rel="stylesheet">
    <link href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css" rel="stylesheet">
    <script src="js/theme-manager.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"
        integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <style>
    .dataTable {
        width: 100% !important;
        border-collapse: collapse;
    }

    .dataTable th,
    .dataTable td {
        padding: 0.75rem 1.5rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .dataTable thead {
        background-color: #1E3A8A;
        /* Navy blue */
        color: #fff;
    }

    .dataTable tbody tr:hover {
        background-color: #f3f4f6;
    }

    .dataTables_paginate .paginate_button {
        @apply px-3 py-2 mx-1 rounded-md text-white bg-blue-600 hover: bg-blue-700;
    }

    .dataTables_paginate .paginate_button.current {
        @apply bg-green-500 text-white;
        /* Green */
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
                    <span>Edit Salary Table</span>
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
                    <i class="fas fa-table mr-2"></i> Salary Table
                    <small class="text-base text-gray-600 ml-2">Edit allowances and deductions</small>
                </h1>

                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <form id="add_item_form" action="edit_conhess_conmess.php" method="get" class="flex items-center">
                        <div class="relative w-full max-w-md">
                            <input type="text" name="item" id="item"
                                class="w-full border border-gray-300 rounded-md p-2 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-600"
                                placeholder="Enter Allowance & Deduction"
                                value="<?php echo htmlspecialchars($searchItem); ?>">
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
                        <h2 class="text-xl font-semibold text-gray-800">Salary Table</h2>
                        <span class="inline-block px-3 py-1 text-sm font-semibold text-blue-800 bg-blue-100 rounded">
                            Total Allowance/Deduction: <?php echo $totalAllowances; ?>
                        </span>
                    </div>
                    <div class="overflow-x-auto">
                        <table id="salaryTable" class="w-full dataTable">
                            <thead>
                                <tr>
                                    <th class="hidden">ID</th>
                                    <th>Allowance</th>
                                    <th>Grade/Step</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allowances as $row): ?>
                                <tr data-id="<?php echo htmlspecialchars($row['allow_id']); ?>">
                                    <td class="hidden" data-id="<?php echo htmlspecialchars($row['allow_id']); ?>">
                                        <?php echo htmlspecialchars($row['allow_id']); ?></td>
                                    <td class="uppercase"><?php echo htmlspecialchars($row['ADJDESC']); ?></td>
                                    <td><?php echo htmlspecialchars($row['grade_step']); ?></td>
                                    <td class="editable" data-value="<?php echo htmlspecialchars($row['value']); ?>">
                                        <?php echo htmlspecialchars($row['value']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <nav class="mt-4" aria-label="page navigation">
                        <ul class="flex justify-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li>
                                <a href="edit_conhess_conmess.php?page=<?php echo $i; ?><?php echo $searchItem ? '&item=' . urlencode($searchItem) : ''; ?>"
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

    <script>
    $(document).ready(function() {
        // Log initial table HTML and data
        console.log('Initial table HTML:', $('#salaryTable').html());
        console.log('jQuery version:', $.fn.jquery);
        console.log('DataTables version:', $.fn.DataTable ? $.fn.DataTable.version : 'Not loaded');
        console.log('PHP allowances data:', <?php echo json_encode($allowances); ?>);

        // Initialize DataTable
        const table = $('#salaryTable').DataTable({
            dom: 'Bfrtip',
            pageLength: 100,
            searching: false,
            ordering: true,
            order: [
                [1, 'asc']
            ],
            columns: [{
                    data: 'allow_id',
                    visible: false
                },
                {
                    data: 'ADJDESC'
                },
                {
                    data: 'grade_step'
                },
                {
                    data: 'value',
                    className: 'editable'
                }
            ],
            data: <?php echo json_encode($allowances); ?>,
            columnDefs: [{
                    targets: 0,
                    visible: false
                },
                {
                    targets: [1, 2],
                    orderable: false
                }
            ],
            buttons: [{
                    extend: 'print',
                    title: 'Salary_Table_Export'
                },
                {
                    extend: 'csv',
                    title: 'Salary_Table_Export'
                },
                {
                    extend: 'excel',
                    title: 'Salary_Table_Export'
                }
            ],
            drawCallback: function() {
                $('#salaryTable tbody td:nth-child(4)').addClass('editable').css('cursor',
                    'pointer');
                console.log('Editable cells after draw:', $('#salaryTable tbody td.editable')
                    .length);
                console.log('First row DOM:', $('#salaryTable tbody tr:first').html());
            },
            initComplete: function() {
                console.log('DataTable columns:', this.api().columns().count());
                console.log('Editable cells:', $('#salaryTable tbody td.editable').length);
                console.log('First row data:', this.api().row(0).data());
            }
        });

        // Debug column rendering
        console.log('Table columns after init:', $('#salaryTable thead th').length);
        $('#salaryTable thead th').each(function(i) {
            console.log(`Column ${i}:`, $(this).text(), 'Visible:', $(this).is(':visible'));
        });

        // Inline editing with event delegation
        $(document).on('dblclick touchstart', '#salaryTable tbody td.editable', function(e) {
            e.preventDefault();
            if (e.type === 'touchstart') {
                e.preventDefault(); // Prevent zoom on double-tap
                if (e.originalEvent.touches.length > 1) return; // Ignore multi-touch
            }
            const $cell = $(this);
            console.log('Double-click detected on cell:', $cell.text(), 'Index:', $cell.index());

            // Get row and ID
            const $row = $cell.closest('tr');
            const rowData = table.row($row).data();
            let id = rowData ? rowData.allow_id : null;

            // Fallbacks to DOM data-id
            if (!id) {
                id = $row.data('id') || $row.find('td.hidden').data('id');
                console.log('Fallback ID from DOM:', id);
            }

            const originalValue = $cell.data('value') || $cell.text();
            console.log('Row data:', rowData);
            console.log('allow_id:', id);
            console.log('Original value:', originalValue);

            if (!id) {
                console.error('No allow_id found for row:', rowData, 'DOM ID:', $row.data('id'));
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Unable to edit: ID not found.'
                });
                return;
            }

            // Replace cell content with input
            $cell.html(`<input type="number" class="editable-input" value="${originalValue}" />`);
            const $input = $cell.find('input');
            $input.focus();

            // Save on blur or Enter
            $input.on('blur keypress', function(e) {
                if (e.type === 'blur' || e.which === 13) {
                    const newValue = $input.val().trim();
                    console.log('Saving value:', newValue, 'for ID:', id);

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
                            url: 'ajax_edit.php',
                            type: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify({
                                data: {
                                    [id]: {
                                        value: newValue
                                    }
                                }
                            }),
                            dataType: 'json',
                            success: function(response) {
                                console.log('AJAX response:', response);
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
                                console.error('AJAX error:', xhr.status, error, xhr
                                    .responseText);
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

        // Autocomplete with space handling
        $('#item').autocomplete({
            source: function(request, response) {
                console.log('Autocomplete search term:', request.term);
                $.ajax({
                    url: 'searchAllowDed.php',
                    type: 'POST',
                    data: {
                        term: request.term
                    },
                    dataType: 'json',
                    success: function(data) {
                        console.log('Autocomplete response:', data);
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
                console.log('Autocomplete selected:', ui.item.value);
                $('#item').val(ui.item.value);
                $('#add_item_form').submit();
            },
            search: function() {
                console.log('Autocomplete search started');
                $('#ajax-loader').removeClass('hidden');
            },
            response: function() {
                console.log('Autocomplete response received');
                $('#ajax-loader').addClass('hidden');
            }
        }).focus();

        // Clear placeholder on click
        $('#item').on('click', function() {
            $(this).attr('placeholder', '');
        });

        // Debug event binding
        console.log('Double-click event bound to:', $('#salaryTable tbody td.editable').length, 'cells');
    });
    </script>
</body>

</html>