<?php
session_start();
require_once 'Connections/paymaster.php';
require_once 'libs/App.php';
require_once 'libs/middleware.php';

$app = new App();
$app->checkAuthentication();
checkPermission();



// Generate unique invoice ID
if (!isset($_SESSION['SESS_INVOICE']) || $_SESSION['SESS_INVOICE'] === '') {
    $_SESSION['SESS_INVOICE'] = 'SIV-' . bin2hex(random_bytes(4));
}

// Handle form submissions
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		error_log('POST data: ' . print_r($_POST, true));
        if (isset($_POST['cancel']) && $_POST['cancel'] === 'cancel') {
            // Clear tbl_workingfile
            $stmt = $conn->prepare("DELETE FROM tbl_workingfile WHERE session_id = ?");
            $stmt->execute([$_SESSION['SESS_INVOICE']]);
            unset($_SESSION['SESS_INVOICE']);
            $_SESSION['SESS_INVOICE'] = 'SIV-' . bin2hex(random_bytes(4));
        } elseif (isset($_POST['saveForm']) && $_POST['saveForm'] === 'save') {
            // Process tbl_workingfile entries
            $stmt = $conn->prepare("SELECT * FROM tbl_workingfile WHERE session_id = ?");
            $stmt->execute([$_SESSION['SESS_INVOICE']]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                if ($row['stop_allow'] == 1) {
                    // Delete allowance/deduction
                    $deleteStmt = $conn->prepare("DELETE FROM allow_deduc WHERE staff_id = ? AND allow_id = ?");
                    $deleteStmt->execute([$row['staff_id'], $row['allow_id']]);
                } else {
                    // Check if allowance/deduction exists
                    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM allow_deduc WHERE allow_id = ? AND staff_id = ?");
                    $checkStmt->execute([$row['allow_id'], $row['staff_id']]);
                    $exists = $checkStmt->fetchColumn();

                    if ($exists) {
                        // Update allowance/deduction
                        $updateStmt = $conn->prepare("UPDATE allow_deduc SET `value` = ?, transcode = ?, counter = ?, inserted_by = ?, date_insert = NOW() WHERE staff_id = ? AND allow_id = ?");
                        $updateStmt->execute([$row['value'], $row['type'], $row['counter'], $row['inserted_by'], $row['staff_id'], $row['allow_id']]);
                    } else {
                        // Insert new allowance/deduction
                        $insertStmt = $conn->prepare("INSERT INTO allow_deduc (staff_id, allow_id, `value`, transcode, counter, inserted_by, date_insert) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                        $insertStmt->execute([$row['staff_id'], $row['allow_id'], $row['value'], $row['type'], $row['counter'], $row['inserted_by']]);
                    }
                }
                // Delete from tbl_workingfile
                $deleteStmt = $conn->prepare("DELETE FROM tbl_workingfile WHERE temp_id = ?");
                $deleteStmt->execute([$row['temp_id']]);
            }
            unset($_SESSION['SESS_INVOICE']);
            $_SESSION['SESS_INVOICE'] = 'SIV-' . bin2hex(random_bytes(4));
            $_SESSION['msg'] = 'Adjustments saved successfully.';
            $_SESSION['alertcolor'] = 'green';
        } elseif (isset($_POST['amount']) && $_POST['amount'] > 0) {
            // Update amount
            $stmt = $conn->prepare("UPDATE tbl_workingfile SET `value` = ? WHERE temp_id = ?");
            $stmt->execute([$_POST['amount'], $_POST['temp_id']]);
        } elseif (isset($_POST['stop_allow'])) {
            // Update stop_allow
            $stmt = $conn->prepare("UPDATE tbl_workingfile SET stop_allow = ? WHERE temp_id = ?");
            $stmt->execute([$_POST['stop_allow'], $_POST['temp_id']]);
        } elseif (isset($_POST['newdeductioncode']) && $_POST['newdeductioncode'] != 0) {
            // Update deduction code
            $stmt = $conn->prepare("SELECT operator FROM tbl_earning_deduction WHERE ed_id = ?");
            $stmt->execute([$_POST['newdeductioncode']]);
            $operator = $stmt->fetchColumn() === '+' ? '1' : '2';
            $updateStmt = $conn->prepare("UPDATE tbl_workingfile SET allow_id = ?, type = ? WHERE temp_id = ?");
            $updateStmt->execute([$_POST['newdeductioncode'], $operator, $_POST['temp_id']]);
        } elseif (isset($_POST['runningPeriod']) && $_POST['runningPeriod'] >= 0) {
            // Update running period
            $stmt = $conn->prepare("UPDATE tbl_workingfile SET counter = ? WHERE temp_id = ?");
            $stmt->execute([$_POST['runningPeriod'], $_POST['temp_id']]);
        } elseif (isset($_POST['item'])) {
            // Add new staff adjustment
            $deductionCode = $_SESSION['deductoncode'] ?? -1;
            $stmt = $conn->prepare("SELECT operator FROM tbl_earning_deduction WHERE ed_id = ?");
            $stmt->execute([$deductionCode]);
            $operator = $stmt->fetchColumn();
            $operator = $operator === '+' ? '1' : ($operator === '-' ? '2' : '-1');
            error_log("Operator: " . $_SESSION['deductoncode']);
			if ($operator) {
                $insertStmt = $conn->prepare("INSERT INTO tbl_workingfile (session_id, staff_id, allow_id, inserted_by, type, date_insert) VALUES (?, ?, ?, ?, ?, NOW())");
                $insertStmt->execute([$_SESSION['SESS_INVOICE'], $_POST['item'], $deductionCode, $_SESSION['SESS_MEMBER_ID'], $operator]);
            }
        }
    }

    if (isset($_GET['deleteid'])) {
        // Delete adjustment
        $stmt = $conn->prepare("DELETE FROM tbl_workingfile WHERE temp_id = ?");
        $stmt->execute([$_GET['deleteid']]);
    }

    // Fetch adjustment details
    $stmt = $conn->prepare("SELECT CONCAT(employee.staff_id, ' - ', employee.NAME) AS details, employee.staff_id, employee.NAME, tbl_workingfile.allow_id, tbl_workingfile.counter, tbl_workingfile.`value`, tbl_workingfile.temp_id, tbl_workingfile.stop_allow FROM tbl_workingfile LEFT JOIN employee ON employee.staff_id = tbl_workingfile.staff_id WHERE session_id = ? ORDER BY temp_id DESC");
    $stmt->execute([$_SESSION['SESS_INVOICE']]);
    $adjustments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch deduction codes
    $stmt = $conn->prepare("SELECT ed_id, edDesc FROM tbl_earning_deduction");
    $stmt->execute();
    $deductionCodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['msg'] = 'An error occurred. Please try again.';
    $_SESSION['alertcolor'] = 'red';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Periodic Data - Salary Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/dark-mode.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/theme-manager.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <link href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css" rel="stylesheet">
    <style>
        .table-responsive { overflow-x: auto; }
        .autocomplete-suggestions { background: #fff; border: 1px solid #ddd; max-height: 200px; overflow-y: auto; }
        .autocomplete-suggestion { padding: 8px; cursor: pointer; }
        .autocomplete-suggestion:hover { background: #f0f0f0; }
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
                    <span>Periodic Data</span>
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
                    <i class="fas fa-shopping-cart mr-2"></i> Periodic Data <span class="ml-2 text-gray-600"><?php echo htmlspecialchars($_SESSION['SESS_INVOICE']); ?></span>
                </h1>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Section: Form and Table -->
                    <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
                        <form id="add_item_form" action="multiAdjustment.php" method="POST" class="mb-6">
                            <div class="flex flex-col sm:flex-row gap-4">
                                <input type="text" name="item" id="item" class="flex-1 border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-600" placeholder="Enter Staff Name or Staff No" required>
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Add Staff</button>
                            </div>
                        </form>
                        <p class="text-sm text-gray-600 mb-4">To stop Deduction/Allowance, input <strong>1</strong> in Stop field.</p>
                        <div class="table-responsive">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-gray-200">
                                        <th class="p-3 text-left">Action</th>
                                        <th class="p-3 text-left">Staff No.</th>
                                        <th class="p-3 text-left">Name</th>
                                        <th class="p-3 text-left">Allowance/Deduction</th>
                                        <th class="p-3 text-left">Amount</th>
                                        <th class="p-3 text-left">Running Period</th>
                                        <th class="p-3 text-left">Stop</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($adjustments as $adjustment): ?>
                                        <tr class="border-t">
                                            <td class="p-3">
                                                <a href="multiAdjustment.php?deleteid=<?php echo $adjustment['temp_id']; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Are you sure you want to delete this adjustment?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                            <td class="p-3"><?php echo htmlspecialchars($adjustment['staff_id']); ?></td>
                                            <td class="p-3"><?php echo htmlspecialchars($adjustment['details']); ?></td>
                                            <td class="p-3">
                                                <form action="multiAdjustment.php" method="POST" class="inline-flex">
                                                    <input type="hidden" name="temp_id" value="<?php echo $adjustment['temp_id']; ?>">
                                                    <select name="newdeductioncode" class="border border-gray-300 rounded-md p-2 w-full" onchange="this.form.submit()">
                                                        <option value="">Select Deduction</option>
                                                        <?php foreach ($deductionCodes as $code): ?>
                                                            <option value="<?php echo $code['ed_id']; ?>" <?php echo $code['ed_id'] == $adjustment['allow_id'] ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($code['edDesc'] . ' - ' . $code['ed_id']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </form>
                                            </td>
                                            <td class="p-3">
                                                <form action="multiAdjustment.php" method="POST" class="inline-flex">
                                                    <input type="hidden" name="temp_id" value="<?php echo $adjustment['temp_id']; ?>">
                                                    <input type="number" name="amount" value="<?php echo $adjustment['value']; ?>" min="0" class="border border-gray-300 rounded-md p-2 w-full" required onchange="this.form.submit()">
                                                </form>
                                            </td>
                                            <td class="p-3">
                                                <form action="multiAdjustment.php" method="POST" class="inline-flex">
                                                    <input type="hidden" name="temp_id" value="<?php echo $adjustment['temp_id']; ?>">
                                                    <input type="number" name="runningPeriod" value="<?php echo $adjustment['counter']; ?>" min="0" class="border border-gray-300 rounded-md p-2 w-full" onchange="this.form.submit()">
                                                </form>
                                            </td>
                                            <td class="p-3">
                                                <form action="multiAdjustment.php" method="POST" class="inline-flex">
                                                    <input type="hidden" name="temp_id" value="<?php echo $adjustment['temp_id']; ?>">
                                                    <input type="number" name="stop_allow" value="<?php echo $adjustment['stop_allow']; ?>" min="0" max="1" class="border border-gray-300 rounded-md p-2 w-full" onchange="this.form.submit()">
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($adjustments)): ?>
                                        <tr>
                                            <td colspan="7" class="p-3 text-center text-gray-500">No adjustments found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Right Section: Actions -->
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <form id="cancel_sale_form" action="multiAdjustment.php" method="POST" class="mb-4">
                            <input type="hidden" name="cancel" value="cancel">
                            <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700" onclick="return confirm('Are you sure you want to clear all adjustments?')">Cancel Entry</button>
                        </form>
                        <?php if (!empty($adjustments)): ?>
                            <form id="finish_sale_form" action="multiAdjustment.php" method="POST">
                                <input type="hidden" name="saveForm" value="save">
                                <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700" onclick="return confirm('Are you sure you want to save all adjustments?')">Finish</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#item').autocomplete({
                source: 'searchStaff.php',
                minLength: 1,
                select: function(event, ui) {
                    event.preventDefault();
                    $('#item').val(ui.item.value);
                    $('#add_item_form').submit();
                }
            });

            $('#add_item_form, #cancel_sale_form, #finish_sale_form').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        window.location.reload();
                    },
                    error: function() {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'An error occurred. Please try again.' });
                    }
                });
            });
        });
    </script>
</body>
</html>