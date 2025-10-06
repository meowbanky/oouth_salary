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
} catch (PDOException $e) {
    error_log("Query error: " . $e->getMessage());
    $deductions = [];
    $totalDeductions = 0;
    $total_pages = 1;
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <script src="js/theme-manager.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>
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
        .dataTable th, .dataTable td {
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
            @apply px-3 py-2 mx-1 rounded-md text-white bg-blue-600 hover:bg-blue-700 text-sm;
        }
        .dataTables_paginate .paginate_button.current {
            @apply bg-green-500 text-white;
        }
        .dataTables_paginate .paginate_button.disabled {
            @apply text-gray-400 bg-gray-100 cursor-not-allowed hover:bg-gray-100 hover:text-gray-400;
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
            min-height: calc(100vh - 60px); /* Adjust for header height */
        }
        @media (max-width: 640px) {
            .dataTable th, .dataTable td {
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
                padding-top: 60px; /* Adjust for header */
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
                padding: 1.5rem; /* p-6 */
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
        <button class="hamburger fixed top-4 left-4 z-[1200] md:hidden p-2 bg-blue-600 text-white rounded-md" aria-label="Toggle sidebar">
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
                    <div class="bg-<?php echo $_SESSION['alertcolor']; ?>-100 text-<?php echo $_SESSION['alertcolor']; ?>-800 p-4 rounded-md mb-6 flex justify-between items-center">
                        <span><?php echo htmlspecialchars($_SESSION['msg']); ?></span>
                        <button onclick="this.parentElement.remove()" class="text-<?php echo $_SESSION['alertcolor']; ?>-600 hover:text-<?php echo $_SESSION['alertcolor']; ?>-700">
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
                            <input type="text" name="item" id="item" class="w-full border border-gray-300 rounded-md p-2 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-600" placeholder="Enter Deduction" value="<?php echo htmlspecialchars($searchItem); ?>">
                            <span id="ajax-loader" class="absolute right-3 top-3 hidden">
                                <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                        </div>
                        <button type="submit" class="ml-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </form>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold text-gray-800">Deduction Table</h2>
                        <span class="inline-block px-3 py-1 text-sm font-semibold text-blue-800 bg-blue-100 rounded">
                            Total Deductions: <?php echo $totalDeductions; ?>
                        </span>
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
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deductions as $row): ?>
                                    <tr data-id="<?php echo htmlspecialchars($row['ded_id']); ?>">
                                        <td class="hidden" data-id="<?php echo htmlspecialchars($row['allowcode']); ?>"><?php echo htmlspecialchars($row['allowcode']); ?></td>
                                        <td class="uppercase"><?php echo htmlspecialchars($row['edDesc']); ?></td>
                                        <td><?php echo htmlspecialchars($row['ratetype']); ?></td>
                                        <td class="editable" data-value="<?php echo htmlspecialchars($row['percentage']); ?>"><?php echo htmlspecialchars($row['percentage']); ?></td>
                                        <td><?php echo htmlspecialchars($row['grade_step']); ?></td>
                                        <td class="editable" data-value="<?php echo htmlspecialchars($row['value']); ?>"><?php echo htmlspecialchars($row['value']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <nav class="mt-4" aria-label="page navigation">
                        <ul class="flex justify-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li>
                                    <a href="edit_deduction_table.php?page=<?php echo $i; ?><?php echo $searchItem ? '&item=' . urlencode($searchItem) : ''; ?>" class="px-3 py-2 mx-1 rounded-md <?php echo $i == $page ? 'bg-green-500 text-white' : 'bg-blue-600 text-white hover:bg-blue-700'; ?>">
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
            // Sidebar toggle for mobile
            $('.hamburger').on('click', function(e) {
                e.stopPropagation();
                $('#sidebar').toggleClass('sidebar-show');
                console.log('Hamburger clicked, sidebar class:', $('#sidebar').attr('class'));
            });

            // Close sidebar when clicking outside on mobile
            $(document).on('click', function(e) {
                if ($(window).width() <= 640 && $('#sidebar').hasClass('sidebar-show') && !$(e.target).closest('#sidebar').length && !$(e.target).hasClass('hamburger')) {
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
                order: [[1, 'asc']],
                columns: [
                    { data: 'allowcode', visible: false },
                    { data: 'edDesc' },
                    { data: 'ratetype' },
                    { data: 'percentage', className: 'editable' },
                    { data: 'grade_step' },
                    { data: 'value', className: 'editable' }
                ],
                data: <?php echo json_encode($deductions); ?>,
                columnDefs: [
                    { targets: 0, visible: false },
                    { targets: [1, 2, 4], orderable: false }
                ],
                buttons: [
                    { extend: 'print', title: 'Deduction_Table_Export' },
                    { extend: 'csv', title: 'Deduction_Table_Export' },
                    { extend: 'excel', title: 'Deduction_Table_Export' }
                ],
                drawCallback: function() {
                    $('#deductionTable tbody td:nth-child(4), #deductionTable tbody td:nth-child(6)').addClass('editable').css('cursor', 'pointer');
                    console.log('Editable cells after draw:', $('#deductionTable tbody td.editable').length);
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
                const id = $row.data('id') || $row.find('td.hidden').data('id');
                const originalValue = $cell.data('value') || $cell.text();
                const columnIndex = $cell.index();
                const field = columnIndex === 3 ? 'percentage' : 'value';

                if (!id) {
                    console.error('No allowcode found');
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Unable to edit: ID not found.' });
                    return;
                }

                $cell.html(`<input type="number" class="editable-input" value="${originalValue}" />`);
                const $input = $cell.find('input');
                $input.focus();

                $input.on('blur keypress', function(e) {
                    if (e.type === 'blur' || e.which === 13) {
                        const newValue = $input.val().trim();
                        if (newValue === '' || isNaN(newValue) || parseFloat(newValue) < 0) {
                            Swal.fire({ icon: 'error', title: 'Invalid Input', text: 'Please enter a valid positive number.' });
                            $cell.html(originalValue).data('value', originalValue);
                            return;
                        }
                        if (newValue !== originalValue.toString()) {
                            $.ajax({
                                url: 'ajax_edit.php',
                                type: 'POST',
                                contentType: 'application/json',
                                data: JSON.stringify({ data: { [id]: { [field]: newValue } } }),
                                dataType: 'json',
                                success: function(response) {
                                    if (response.status === true) {
                                        $cell.html(newValue).data('value', newValue);
                                        table.cell($cell).data(newValue).draw();
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Success',
                                            text: response.message || 'Value updated successfully.',
                                            timer: 1500,
                                            showConfirmButton: false
                                        });
                                    } else {
                                        $cell.html(originalValue).data('value', originalValue);
                                        Swal.fire({ icon: 'error', title: 'Error', text: response.message || 'Failed to update value.' });
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error('AJAX error:', xhr.status, error);
                                    $cell.html(originalValue).data('value', originalValue);
                                    Swal.fire({ icon: 'error', title: 'Error', text: 'An error occurred: ' + xhr.status + ' ' + error });
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
                    $.ajax({
                        url: 'searchAllowDed.php',
                        type: 'POST',
                        data: { term: request.term },
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