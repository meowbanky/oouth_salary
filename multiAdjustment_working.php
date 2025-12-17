<?php
session_start();
require_once 'Connections/paymaster.php';
require_once 'classes/model.php';

const SESSION_INVOICE_KEY = 'SESS_INVOICE';
const INVOICE_PREFIX = 'SIV-';

function generateRandomInvoice(): string {
    $chars = "003232303232023232023456789";
    $length = 8;
    return INVOICE_PREFIX . substr(str_shuffle($chars), 0, $length);
}

if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) === '') {
    header("Location: index.php");
    exit;
}

if (!isset($_SESSION[SESSION_INVOICE_KEY]) || $_SESSION[SESSION_INVOICE_KEY] === '') {
    $_SESSION[SESSION_INVOICE_KEY] = generateRandomInvoice();
}

try {
    $conn->beginTransaction();

    // Handle cancellation of the current session
    if (isset($_POST['cancel']) && $_POST['cancel'] === 'cancel') {
        $stmt = $conn->prepare("DELETE FROM tbl_workingfile WHERE session_id = :session_id");
        $stmt->execute(['session_id' => $_SESSION[SESSION_INVOICE_KEY]]);
        unset($_SESSION[SESSION_INVOICE_KEY]);
        $_SESSION[SESSION_INVOICE_KEY] = generateRandomInvoice();
    }

    // Save the form data to the database
    if (isset($_POST['saveForm'])) {
        $stmt = $conn->prepare("SELECT * FROM tbl_workingfile WHERE session_id = :session_id");
        $stmt->execute(['session_id' => $_SESSION[SESSION_INVOICE_KEY]]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            if ($row['stop_allow'] == 1) {
                $deleteStmt = $conn->prepare("DELETE FROM allow_deduc WHERE staff_id = :staff_id AND allow_id = :allow_id");
                $deleteStmt->execute(['staff_id' => $row['staff_id'], 'allow_id' => $row['allow_id']]);
                $deleteStmt = $conn->prepare("DELETE FROM tbl_workingfile WHERE temp_id = :temp_id");
                $deleteStmt->execute(['temp_id' => $row['temp_id']]);
            } else {
                $checkStmt = $conn->prepare("SELECT * FROM allow_deduc WHERE allow_id = :allow_id AND staff_id = :staff_id");
                $checkStmt->execute(['allow_id' => $row['allow_id'], 'staff_id' => $row['staff_id']]);
                if ($checkStmt->rowCount() > 0) {
                    $updateStmt = $conn->prepare("UPDATE allow_deduc SET `value` = :value, transcode = :transcode, counter = :counter, inserted_by = :inserted_by, date_insert = NOW() WHERE staff_id = :staff_id AND allow_id = :allow_id");
                    $updateStmt->execute([
                        'value' => $row['value'],
                        'transcode' => $row['type'],
                        'counter' => $row['counter'],
                        'inserted_by' => $row['inserted_by'],
                        'staff_id' => $row['staff_id'],
                        'allow_id' => $row['allow_id']
                    ]);
                } else {
                    $insertStmt = $conn->prepare("INSERT INTO allow_deduc (staff_id, allow_id, `value`, transcode, counter, inserted_by, date_insert) VALUES (:staff_id, :allow_id, :value, :transcode, :counter, :inserted_by, NOW())");
                    $insertStmt->execute([
                        'staff_id' => $row['staff_id'],
                        'allow_id' => $row['allow_id'],
                        'value' => $row['value'],
                        'transcode' => $row['type'],
                        'counter' => $row['counter'],
                        'inserted_by' => $row['inserted_by']
                    ]);
                }
                $deleteStmt = $conn->prepare("DELETE FROM tbl_workingfile WHERE temp_id = :temp_id");
                $deleteStmt->execute(['temp_id' => $row['temp_id']]);
            }
        }
        unset($_SESSION[SESSION_INVOICE_KEY]);
        $_SESSION[SESSION_INVOICE_KEY] = generateRandomInvoice();
    }

    // Update amount in tbl_workingfile
    if (isset($_POST['amount']) && $_POST['amount'] !== "" && $_POST['amount'] > 0) {
        $stmt = $conn->prepare("UPDATE tbl_workingfile SET `value` = :value WHERE temp_id = :temp_id");
        $stmt->execute(['value' => (float)$_POST['amount'], 'temp_id' => (int)$_POST['temp_id']]);
    }

    // Update stop_allow field
    if (isset($_POST['stop_allow'])) {
        $stmt = $conn->prepare("UPDATE tbl_workingfile SET stop_allow = :stop_allow WHERE temp_id = :temp_id");
        $stmt->execute(['stop_allow' => (int)$_POST['stop_allow'], 'temp_id' => (int)$_POST['temp_id']]);
    }

    // Update deduction code and type
    if (isset($_POST['newdeductioncode']) && $_POST['newdeductioncode'] != 0) {
        $stmt = $conn->prepare("SELECT operator, ed_id FROM tbl_earning_deduction WHERE ed_id = :ed_id");
        $stmt->execute(['ed_id' => (int)$_POST['newdeductioncode']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $operator = $row['operator'] === '+' ? '1' : '2';
        $stmt = $conn->prepare("UPDATE tbl_workingfile SET allow_id = :allow_id, type = :type WHERE temp_id = :temp_id");
        $stmt->execute(['allow_id' => (int)$_POST['newdeductioncode'], 'type' => $operator, 'temp_id' => (int)$_POST['temp_id']]);
    }

    // Update running period
    if (isset($_POST['runningPeriod']) && $_POST['runningPeriod'] !== "" && $_POST['runningPeriod'] >= 0) {
        $stmt = $conn->prepare("UPDATE tbl_workingfile SET counter = :counter WHERE temp_id = :temp_id");
        $stmt->execute(['counter' => (int)$_POST['runningPeriod'], 'temp_id' => (int)$_POST['temp_id']]);
    }

    // Delete a specific entry
    if (isset($_GET['deleteid'])) {
        $stmt = $conn->prepare("DELETE FROM tbl_workingfile WHERE temp_id = :temp_id");
        $stmt->execute(['temp_id' => (int)$_GET['deleteid']]);
    }

    // Add new staff entry
    if (isset($_POST['item'])) {
        $deductionCode = $_SESSION['deductoncode'] ?? -1;
        $stmt = $conn->prepare("SELECT operator, ed_id FROM tbl_earning_deduction WHERE ed_id = :ed_id");
        $stmt->execute(['ed_id' => (int)$deductionCode]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $operator = $row && $row['operator'] === '+' ? '1' : '2';
        $stmt = $conn->prepare("INSERT INTO tbl_workingfile (session_id, staff_id, allow_id, inserted_by, type, date_insert) VALUES (:session_id, :staff_id, :allow_id, :inserted_by, :type, NOW())");
        $stmt->execute([
            'session_id' => $_SESSION[SESSION_INVOICE_KEY],
            'staff_id' => filter_var($_POST['item'], FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'allow_id' => (int)$deductionCode,
            'inserted_by' => $_SESSION['SESS_MEMBER_ID'],
            'type' => $operator
        ]);
    }

    // Fetch current session details
    $stmt = $conn->prepare("SELECT CONCAT(employee.staff_id, ' - ', employee.NAME) AS details, employee.staff_id, employee.NAME, tbl_workingfile.allow_id, tbl_workingfile.counter, tbl_workingfile.value, temp_id, tbl_workingfile.stop_allow 
                            FROM tbl_workingfile 
                            LEFT JOIN employee ON employee.staff_id = tbl_workingfile.staff_id 
                            WHERE session_id = :session_id 
                            ORDER BY temp_id DESC");
    $stmt->execute(['session_id' => $_SESSION[SESSION_INVOICE_KEY]]);
    $saveDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $conn->commit();
} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Database Error in sales.php: " . $e->getMessage());
    $errorMessage = '<div class="alert alert-danger">An error occurred while processing your request. Please try again.</div>';
} catch (Exception $e) {
    $conn->rollBack();
    error_log("General Error in sales.php: " . $e->getMessage());
    $errorMessage = '<div class="alert alert-danger">An unexpected error occurred. Please try again.</div>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Management</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        #wrapper { min-height: 100vh; display: flex; flex-direction: column; }
        #header { background: #333; color: #fff; padding: 10px; }
        #header-logo { height: 40px; }
        #user-nav { background: #f8f8f8; padding: 10px; }
        #content { flex: 1; padding: 20px; }
        #footer { background: #333; color: #fff; text-align: center; padding: 10px; position: relative; bottom: 0; width: 100%; }
        .hidden-print { display: block; }
        .modal { display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; }
        .close:hover, .close:focus { color: black; text-decoration: none; cursor: pointer; }
        .forms-area { margin-bottom: 15px; }
        .input-append { display: flex; align-items: center; }
        .table-responsive { overflow-x: auto; }
        .sale_register_leftbox, .sale_register_rightbox { padding: 10px; }
        .spacing { height: 10px; }
        @media print { .hidden-print { display: none; } }
    </style>
</head>
<body data-color="grey" class="flat">
<div class="modal fade hidden-print" id="myModal"></div>
<div id="wrapper" class="minibar">
    <div id="header" class="hidden-print">
        <h1><a href="index.php"><img src="img/header_logo.png" class="hidden-print header-log" id="header-logo" alt="Logo"></a></h1>
        <a id="menu-trigger" href="#"><i class="fa fa-bars fa-2x"></i></a>
        <div class="clear"></div>
    </div>

    <div id="user-nav" class="hidden-print hidden-xs">
        <ul class="btn-group">
            <li class="btn hidden-xs"><a href="switch_user" data-toggle="modal" data-target="#myModal"><i class="fa fa-user fa-2x"></i> Welcome <b><?php echo htmlspecialchars($_SESSION['SESS_FIRST_NAME']); ?></b></a></li>
            <li class="btn hidden-xs disabled"><a href="/" onclick="return false;"><i class="fa fa-clock-o fa-2x"></i> <span><?php echo htmlspecialchars(date('l, F d, Y', strtotime(date('Y-m-d')))); ?></span></a></li>
            <li class="btn"><a href="#"><i class="fa fa-cog"></i> Settings</a></li>
            <li class="btn"><a href="index.php"><i class="fa fa-power-off"></i> Logout</a></li>
        </ul>
    </div>

    <div id="sidebar" class="hidden-print">
        <ul>
            <li><a href="sales.php">Sales</a></li>
            <li><a href="reports.php">Reports</a></li>
        </ul>
    </div>
    <style>
        #sidebar { width: 250px; background: #f0f0f0; padding: 10px; }
        #sidebar ul { list-style: none; padding: 0; }
        #sidebar li { margin: 10px 0; }
        #sidebar a { color: #333; text-decoration: none; }
        #sidebar a:hover { color: #007bff; }
    </style>

    <div id="content" class="clearfix sales_content_minibar">
        <div class="clear"></div>
        <div id="sale-grid-big-wrapper" class="clearfix">
            <div id="category_item_selection_wrapper" class="clearfix"></div>
        </div>
        <div id="register_container" class="sales clearfix">
            <div id="content-header" class="hidden-print sales_header_container">
                <h1 class="headigs"><i class="fa fa-shopping-cart"></i> Periodic Data <?php echo htmlspecialchars($_SESSION[SESSION_INVOICE_KEY]); ?><span id="ajax-loader"><img src="img/ajax-loader.gif" alt="" /></span></h1>
            </div>
            <div class="clear"></div>

            <div class="row">
                <div class="sale_register_leftbox col-md-9">
                    <div class="row forms-area">
                        <div class="col-md-8 no-padd">
                            <div class="input-append">
                                <form action="sales.php" method="post" id="add_item_form" class="form-inline" autocomplete="off">
                                    <input type="text" name="item" id="item" class="input-xlarge" placeholder="Enter Staff Name or Staff No" list="staffSuggestions" />
                                    <input type="hidden" name="code" id="code" value="-1" />
                                </form>
                            </div>
                        </div>
                        <div class="col-md-12">To stop Deduction/Allowance input 1 in <strong>Stop Field</strong></div>
                    </div>
                    <div class="row">
                        <div class="table-responsive">
                            <table id="register" class="table table-bordered">
                                <thead>
                                <tr>
                                    <th></th>
                                    <th class="staff_name_heading">Staff No.</th>
                                    <th class="sales_item sales_items_number">Name</th>
                                    <th class="sales_stock">Allowance/Deduction</th>
                                    <th class="sales_price">Amount</th>
                                    <th class="sales_quality">Running Period</th>
                                    <th class="sales_quality">Stop</th>
                                </tr>
                                </thead>
                                <tbody id="cart_contents" class="sa">
                                <?php foreach ($saveDetails as $row): ?>
                                    <tr id="reg_item_top" bgcolor="#eeeeee">
                                        <td><a href="sales.php?deleteid=<?php echo htmlspecialchars($row['temp_id']); ?>" class="delete_item"><i class="fa fa-trash-o fa-2x text-error"></i></a></td>
                                        <td class="text text-success"><?php echo htmlspecialchars($row['staff_id']); ?></td>
                                        <td class="text text-info sales_item" id="reg_item_number"><?php echo htmlspecialchars($row['details']); ?></td>
                                        <td class="text text-warning sales_stock" id="reg_item_stock">
                                            <form action="sales.php" method="post" class="line_item_form" autocomplete="off">
                                                <input type="hidden" name="temp_id" value="<?php echo htmlspecialchars($row['temp_id']); ?>" />
                                                <select required name="newdeductioncode" id="newdeductioncode" class="form-control">
                                                    <option>- - Select Deduction/Allowance - -</option>
                                                    <?php
                                                    $stmt = $conn->prepare("SELECT ed_id, edDesc FROM tbl_earning_deduction");
                                                    $stmt->execute();
                                                    $deductions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                    foreach ($deductions as $deduction) {
                                                        $selected = $deduction['ed_id'] == $row['allow_id'] ? 'selected' : '';
                                                        echo "<option value='{$deduction['ed_id']}' $selected>" . htmlspecialchars($deduction['edDesc']) . " - {$deduction['ed_id']}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <form action="sales.php" method="post" class="line_item_form" autocomplete="off">
                                                <input type="number" required min="0" name="amount" class="form-control" value="<?php echo htmlspecialchars($row['value'] ?? 0); ?>" />
                                                <input type="hidden" name="temp_id" value="<?php echo htmlspecialchars($row['temp_id']); ?>" />
                                            </form>
                                        </td>
                                        <td id="reg_item_qty">
                                            <form action="sales.php" method="post" class="line_item_form" autocomplete="off">
                                                <input type="number" name="runningPeriod" class="form-control" value="<?php echo htmlspecialchars($row['counter'] ?? 0); ?>" />
                                                <input type="hidden" name="temp_id" value="<?php echo htmlspecialchars($row['temp_id']); ?>" />
                                            </form>
                                        </td>
                                        <td id="reg_item_qty">
                                            <form action="sales.php" method="post" class="line_item_form" autocomplete="off">
                                                <input type="number" name="stop_allow" class="form-control" value="<?php echo htmlspecialchars($row['stop_allow'] ?? 0); ?>" />
                                                <input type="hidden" name="temp_id" value="<?php echo htmlspecialchars($row['temp_id']); ?>" />
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 sale_register_rightbox">
                    <ul class="list-group">
                        <li class="list-group-item nopadding">
                            <form action="sales.php" method="post" id="cancel_sale_form" autocomplete="off">
                                <input type="button" class="btn btn-danger button_dangers" id="cancel_sale_button" value="Cancel Entry" />
                                <input type="hidden" name="cancel" value="cancel" />
                            </form>
                        </li>
                        <li class="list-group-item spacing"></li>
                        <li class="list-group-item nopadding"><div id='sale_details'></div></li>
                        <li class="list-group-item spacing"></li>
                        <li class="list-group-item nopadding"><div id="Payment_Types"></div></li>
                        <li class="list-group-item">
                            <form action="sales.php" method="post" id="finish_sale_form" autocomplete="off">
                                <?php if (count($saveDetails) > 0): ?>
                                    <input type="button" class="btn btn-success btn-large btn-block" id="finish_sale_button" value="Finish" />
                                    <input type="hidden" name="saveForm" value="save" />
                                <?php endif; ?>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Trigger/Open The Modal (Placeholder for future implementation) -->
            <button id="openModal" style="display:none;">Open Modal</button>

            <?php if (isset($errorMessage)): echo $errorMessage; endif; ?>
        </div>
    </div>

    <div id="footer" class="col-md-12 hidden-print">
        Please visit our <a href="#" target="_blank">website</a> to learn the latest information about the project.
        <span class="text-info"><span class="label label-info">14.1</span></span>
    </div>
</div>

<script type="module">
    import { showLoader, hideLoader, confirmAction, gritter } from './js/utils.js';

    let submitting = false;

    document.addEventListener('DOMContentLoaded', () => {
        const loader = document.getElementById('ajax-loader');
        const forms = document.querySelectorAll('#add_item_form, .line_item_form, #finish_sale_form, #cancel_sale_form');

        // Handle form submissions with fetch
        forms.forEach(form => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (submitting) return;
                submitting = true;
                showLoader(loader);

                const formData = new FormData(form);
                const url = form.action;

                try {
                    const response = await fetch(url, { method: 'POST', body: formData });
                    const text = await response.text();
                    document.getElementById('register_container').innerHTML = text;
                    if (form.id === 'add_item_form') itemScannedSuccess(text);
                } catch (error) {
                    console.error('Fetch Error:', error);
                    gritter('Error', 'Failed to process request.', 'gritter-item-error', false, true);
                } finally {
                    hideLoader(loader);
                    submitting = false;
                }
            });
        });

        // Handle input changes for immediate updates
        document.querySelectorAll('#cart_contents input, .form-control').forEach(input => {
            input.addEventListener('change', (e) => e.target.closest('form').dispatchEvent(new Event('submit')));
        });

        // Autocomplete for staff search
        document.getElementById('item').addEventListener('input', async (e) => {
            const searchTerm = e.target.value;
            if (searchTerm.length < 1) {
                document.getElementById('staffSuggestions')?.remove();
                return;
            }

            try {
                const response = await fetch(`searchStaff.php?term=${encodeURIComponent(searchTerm)}`);
                const suggestions = await response.json();

                if (suggestions.error) {
                    gritter('Error', suggestions.error, 'gritter-item-error', false, true);
                    return;
                }

                let datalist = document.getElementById('staffSuggestions');
                if (!datalist) {
                    datalist = document.createElement('datalist');
                    datalist.id = 'staffSuggestions';
                    document.body.appendChild(datalist);
                    document.getElementById('item').setAttribute('list', 'staffSuggestions');
                }

                datalist.innerHTML = suggestions.map(suggestion =>
                    `<option value="${suggestion.value}" data-label="${suggestion.label}">${suggestion.label}</option>`
                ).join('');

                document.getElementById('item').addEventListener('change', (e) => {
                    const selectedOption = suggestions.find(s => s.value === e.target.value);
                    if (selectedOption) {
                        e.target.value = selectedOption.value;
                        document.getElementById('add_item_form').dispatchEvent(new Event('submit'));
                    }
                }, { once: true });
            } catch (error) {
                console.error('Autocomplete Error:', error);
                gritter('Error', 'Failed to fetch staff suggestions.', 'gritter-item-error', false, true);
            }
        });

        // Update deduction code session with feedback
        document.getElementById('newdeductioncode')?.addEventListener('change', async () => {
            try {
                const response = await fetch('setdeductionsession.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `deductoncode=${encodeURIComponent(document.getElementById('newdeductioncode').value)}`
                });
                const result = await response.json();

                if (result.status === 'success') {
                    gritter('Success', result.message, 'gritter-item-success', false, true);
                } else {
                    gritter('Error', result.message, 'gritter-item-error', false, true);
                }
            } catch (error) {
                console.error('Error updating deduction code:', error);
                gritter('Error', 'Failed to update deduction code.', 'gritter-item-error', false, true);
            }
        });

        // Finish sale confirmation
        document.getElementById('finish_sale_button')?.addEventListener('click', () => {
            if (confirmAction('Are you sure you want to Save All Adjustments?')) {
                document.getElementById('finish_sale_form').dispatchEvent(new Event('submit'));
            }
        });

        // Suspend sale
        document.getElementById('suspend_sale_button')?.addEventListener('click', () => {
            if (confirmAction('Are you sure you want to suspend this sale?')) {
                fetch('sales.php?suspend=suspend').then(res => res.text()).then(text => {
                    document.getElementById('register_container').innerHTML = text;
                });
            }
        });

        // Cancel sale
        document.getElementById('cancel_sale_button')?.addEventListener('click', () => {
            if (confirmAction('Are you sure you want to clear this sale? All items will be cleared.')) {
                document.getElementById('cancel_sale_form').dispatchEvent(new Event('submit'));
            }
        });

        // Delete items
        document.querySelectorAll('.delete_item').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                fetch(link.href).then(res => res.text()).then(text => {
                    document.getElementById('register_container').innerHTML = text;
                });
            });
        });

        // Select text on focus
        document.querySelectorAll('input[type="text"]').forEach(input => input.addEventListener('click', () => input.select()));

        // Toggle sidebar
        document.getElementById('menu-trigger')?.addEventListener('click', () => {
            document.getElementById('sidebar')?.classList.toggle('open');
        });
    });

    function itemScannedSuccess(response) {
        const code = document.getElementById('code').value;
        gritter('Success', code === '1' ? 'Item not Found' : 'Item Added Successfully', code === '1' ? 'gritter-item-error' : 'gritter-item-success', false, true);
        setTimeout(() => document.getElementById('item').focus(), 10);
        setTimeout(() => gritter.removeAll(), 1000);
    }
</script>
</body>
</html>