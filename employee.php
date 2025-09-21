<?php
require_once('Connections/paymaster.php');
include_once('classes/model.php');
require_once 'libs/App.php';
$App = new App();
$App->checkAuthentication();
require_once 'libs/middleware.php';
checkPermission();

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) == '' || $_SESSION['role'] != 'Admin') {
    header("location: index.php");
    exit();
}

$results_per_page = 200;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = '';
$params = [];
if ($search) {
    $where = 'WHERE employee.NAME LIKE :search OR employee.staff_id LIKE :search';
    $params[':search'] = "%$search%";
}
$countSql = "SELECT COUNT(*) as total FROM employee $where";
$stmt = $conn->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetch()['total'] ?? 0;
$total_pages = ceil($total / $results_per_page);

$sql = "SELECT 
            employee.*, tbl_dept.dept, tbl_pfa.PFANAME, tbl_bank.BNAME 
        FROM employee 
        LEFT JOIN tbl_pfa ON tbl_pfa.PFACODE = employee.PFACODE 
        LEFT JOIN tbl_bank ON tbl_bank.BCODE = employee.BCODE 
        LEFT JOIN tbl_dept ON tbl_dept.dept_id = employee.DEPTCD 
        $where 
        ORDER BY statuscd, staff_id ASC 
        LIMIT :start, :limit";
$stmt = $conn->prepare($sql);
if ($search) $stmt->bindValue(':search', "%$search%");
$stmt->bindValue(':start', ($page - 1) * $results_per_page, PDO::PARAM_INT);
$stmt->bindValue(':limit', $results_per_page, PDO::PARAM_INT);
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Employees | OOUTH Salary Manager</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    html,
    body {
        overflow-x: hidden;
    }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <?php include('header.php'); ?>
    <div class="flex min-h-screen">
        <?php include('sidebar.php'); ?>
        <main class="flex-1 px-2 md:px-8 py-4 flex flex-col">
            <div class="w-full max-w-5xl mx-auto flex-1 flex flex-col">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-blue-800 flex items-center gap-2">
                            <i class="fas fa-users"></i> Employees
                        </h1>
                        <p class="text-sm text-blue-700/70 mt-1">Manage, search and add employee records.</p>
                    </div>
                    <button type="button"
                        class="bg-blue-700 hover:bg-blue-900 text-white px-5 py-2 rounded-lg font-semibold shadow transition"
                        id="openAddEmpModal">
                        <i class="fas fa-user-plus mr-2"></i> Add Employee
                    </button>
                </div>
                <?php if (isset($_SESSION['msg'])): ?>
                <div class="mb-4">
                    <div
                        class="rounded px-4 py-3 shadow text-white 
                        <?php echo ($_SESSION['alertcolor'] ?? 'info') === 'success' ? 'bg-green-500' : 'bg-red-500'; ?>">
                        <?php echo htmlspecialchars($_SESSION['msg']); ?>
                    </div>
                </div>
                <?php unset($_SESSION['msg'], $_SESSION['alertcolor']); ?>
                <?php endif; ?>
                <form method="get" class="flex gap-2 mb-5">
                    <input type="text" name="search" placeholder="Search by Name or Staff No"
                        value="<?php echo htmlspecialchars($search); ?>"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded focus:outline-blue-500 bg-white shadow-sm" />
                    <button class="bg-blue-700 hover:bg-blue-900 text-white px-4 py-2 rounded shadow" type="submit">
                        <i class="fas fa-search"></i> Search
                    </button>
                </form>
                <!-- Responsive Table (Desktop) -->
                <div class="hidden md:block overflow-x-auto rounded-xl bg-white shadow">
                    <table class="min-w-full text-sm">
                        <thead class="bg-blue-50">
                            <tr>
                                <th class="py-2 px-2 text-left">Staff No</th>
                                <th class="py-2 px-2 text-left">Name</th>
                                <th class="py-2 px-2 text-left">Employment Date</th>
                                <th class="py-2 px-2 text-left">Status</th>
                                <th class="py-2 px-2 text-left">Department</th>
                                <th class="py-2 px-2 text-left">Grade/Step</th>
                                <th class="py-2 px-2 text-left">PFA</th>
                                <th class="py-2 px-2 text-left">Bank - No</th>
                                <th class="py-2 px-2 text-left">Call Duty</th>
                                <th class="py-2 px-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $emp): ?>
                            <tr class="border-b hover:bg-blue-50">
                                <td class="py-1 px-2 font-mono text-blue-900">
                                    <?php echo htmlspecialchars($emp['staff_id']); ?></td>
                                <td class="py-1 px-2"><?php echo htmlspecialchars($emp['NAME']); ?></td>
                                <td class="py-1 px-2"><?php echo htmlspecialchars($emp['EMPDATE'] ?? ''); ?></td>
                                <td class="py-1 px-2">
                                    <?php
                                $statusMap = [
                                    'A' => 'Active', 'D' => 'Dismissed', 'T' => 'Terminated',
                                    'R' => 'Resigned', 'S' => 'Suspended'
                                ];
                                echo '<span class="px-2 py-1 rounded text-xs font-bold ' .
                                    ($emp['STATUSCD'] === 'A' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') .
                                    '">' . ($statusMap[$emp['STATUSCD']] ?? 'Unknown') . '</span>';
                                ?>
                                </td>
                                <td class="py-1 px-2"><?php echo htmlspecialchars($emp['dept']); ?></td>
                                <td class="py-1 px-2"><?php echo htmlspecialchars($emp['GRADE'].'/'.$emp['STEP']); ?>
                                </td>
                                <td class="py-1 px-2"><?php echo htmlspecialchars($emp['PFANAME'] ?? ''); ?></td>
                                <td class="py-1 px-2"><?php echo htmlspecialchars($emp['BNAME'].'-'.$emp['ACCTNO']); ?>
                                </td>
                                <td class="py-1 px-2">
                                    <?php
                                $callMap = [0=>'None', 1=>'Doctor', 2=>'Others', 3=>'Nurse'];
                                echo '<span class="text-blue-700">'.$callMap[$emp['CALLTYPE']] ?? '—'.'</span>';
                                ?>
                                </td>
                                <td class="py-1 px-2">
                                    <div class="flex gap-2">
                                        <a href="javascript:void(0);"
                                            onclick="showEmployeeModal('<?php echo $emp['staff_id']; ?>')"
                                            class="text-blue-600 hover:text-blue-900" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="javascript:void(0);"
                                            onclick="showEditEmployeeModal('<?php echo $emp['staff_id']; ?>')"
                                            class="text-yellow-600 hover:text-yellow-900" title="Edit">
                                            <i class="fas fa-pen"></i>
                                        </a>

                                        <a href="javascript:void(0);"
                                            onclick="showDeactivateModal('<?php echo $emp['staff_id']; ?>','<?php echo addslashes($emp['NAME']); ?>','<?php echo $emp['STATUSCD']; ?>')"
                                            class="text-red-600 hover:text-red-900" title="Deactivate/Activate">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Card List (Mobile) -->
                <div class="md:hidden flex-1 min-h-0">
                    <div class="overflow-y-auto" style="max-height: calc(100dvh - 11rem);">
                        <div class="flex flex-col gap-3 px-1 py-2">
                            <?php foreach ($employees as $emp): ?>
                            <div class="bg-white rounded-xl shadow p-4 flex flex-col gap-2 w-full">
                                <div class="flex justify-between items-center">
                                    <div class="font-bold text-blue-900 text-base">
                                        <?php echo htmlspecialchars($emp['NAME']); ?></div>
                                    <span
                                        class="text-xs px-2 py-1 rounded font-bold
                            <?php echo ($emp['STATUSCD'] === 'A') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php
                            $statusMap = [
                              'A' => 'Active', 'D' => 'Dismissed', 'T' => 'Terminated',
                              'R' => 'Resigned', 'S' => 'Suspended'
                            ];
                            echo $statusMap[$emp['STATUSCD']] ?? 'Unknown';
                            ?>
                                    </span>
                                </div>
                                <div class="flex gap-2 text-sm">
                                    <span class="font-medium text-gray-600">Staff No:</span>
                                    <span
                                        class="font-mono text-blue-800"><?php echo htmlspecialchars($emp['staff_id']); ?></span>
                                </div>
                                <div class="grid grid-cols-2 gap-x-2 gap-y-1 text-sm">
                                    <div><span class="text-gray-600">Date:</span>
                                        <span><?php echo htmlspecialchars($emp['EMPDATE'] ?? ''); ?></span>
                                    </div>
                                    <div><span class="text-gray-600">Dept:</span>
                                        <span><?php echo htmlspecialchars($emp['dept'] ?? ''); ?></span>
                                    </div>
                                    <div><span class="text-gray-600">Grade/Step:</span>
                                        <span><?php echo htmlspecialchars($emp['GRADE'] ?? ''.'/'.$emp['STEP']); ?></span>
                                    </div>
                                    <div><span class="text-gray-600">PFA:</span>
                                        <span><?php echo htmlspecialchars($emp['PFANAME'] ?? ''); ?></span>
                                    </div>
                                    <div><span class="text-gray-600">Bank/No:</span>
                                        <span><?php echo htmlspecialchars($emp['BNAME'] ?? ''.'-'.$emp['ACCTNO'] ?? ''); ?></span>
                                    </div>
                                    <div><span class="text-gray-600">Call Duty:</span>
                                        <span class="text-blue-700">
                                            <?php
                            $callMap = [0=>'None', 1=>'Doctor', 2=>'Others', 3=>'Nurse'];
                            echo $callMap[$emp['CALLTYPE']] ?? '—';
                            ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex gap-4 mt-2">
                                    <a href="javascript:void(0);"
                                        onclick="showEmployeeModal('<?php echo $emp['staff_id']; ?>')"
                                        class="text-blue-600 hover:text-blue-900" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="javascript:void(0);"
                                        onclick="showEditEmployeeModal('<?php echo $emp['staff_id']; ?>')"
                                        class="text-yellow-600 hover:text-yellow-900" title="Edit">
                                        <i class="fas fa-pen"></i>
                                    </a>

                                    <a href="javascript:void(0);"
                                        onclick="showDeactivateModal('<?php echo $emp['staff_id']; ?>','<?php echo addslashes($emp['NAME']); ?>','<?php echo $emp['STATUSCD']; ?>')"
                                        class="text-red-600 hover:text-red-900" title="Deactivate/Activate">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="flex justify-center mt-4">
                    <nav class="inline-flex -space-x-px">
                        <?php for ($i=1; $i <= $total_pages; $i++): ?>
                        <a href="?<?php echo http_build_query(['page'=>$i] + ($search ? ['search'=>$search] : [])); ?>"
                            class="px-3 py-1 border <?php echo $page==$i ? 'bg-blue-600 text-white' : 'bg-white text-blue-800'; ?> rounded mx-0.5 text-xs font-semibold hover:bg-blue-100">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                    </nav>
                </div>
            </div>
        </main>
    </div>


    <!-- Edit Employee Modal -->
    <div id="editEmployeeModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 transition-all duration-150 ease-in-out hidden">
        <div
            class="bg-white rounded-xl shadow-xl w-full max-w-2xl mx-2 p-0 overflow-hidden relative max-h-[90dvh] overflow-y-auto">
            <div class="flex items-center justify-between px-5 py-4 bg-yellow-600 text-white">
                <div class="font-bold text-lg flex items-center gap-2">
                    <i class="fas fa-user-edit"></i> Edit Employee
                </div>
                <button onclick="$('#editEmployeeModal').addClass('hidden')"
                    class="text-white hover:text-yellow-300 text-2xl leading-3">&times;</button>
            </div>
            <form method="post" action="classes/controller.php?act=updateEmp" class="p-6 space-y-5"
                id="edit_employee_form" autocomplete="off">
                <div id="editEmployeeFormMsg" class="text-center text-sm pb-2"></div>
                <div id="editEmployeeFields"></div>
                <!-- The fields will be loaded dynamically via JS below -->
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="$('#editEmployeeModal').addClass('hidden')"
                        class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700">Save
                        Changes</button>
                </div>
            </form>
        </div>
    </div>


    <!-- Add Employee Modal -->
    <div id="addEmployeeModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 transition-all duration-150 ease-in-out hidden">
        <div
            class="bg-white rounded-xl shadow-xl w-full max-w-2xl mx-2 p-0 overflow-hidden relative max-h-[90dvh] overflow-y-auto">
            <div class="flex items-center justify-between px-5 py-4 bg-blue-700 text-white">
                <div class="font-bold text-lg flex items-center gap-2">
                    <i class="fas fa-user-plus"></i> Add New Employee
                </div>
                <button onclick="$('#addEmployeeModal').addClass('hidden')"
                    class="text-white hover:text-blue-300 text-2xl leading-3">&times;</button>
            </div>
            <form method="post" action="classes/controller.php?act=addNewEmp" class="p-6 space-y-5" id="employee_form"
                autocomplete="off">
                <div id="addEmployeeFormMsg" class="text-center text-sm pb-2"></div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-sm mb-1">Full Name</label>
                        <input type="text" name="namee" required class="w-full px-3 py-2 border rounded"
                            placeholder="e.g. John Doe">
                    </div>
                    <div>
                        <label class="block font-semibold text-sm mb-1">OOUTH Email</label>
                        <input type="email" name="email" required pattern="[a-zA-Z0-9]+\.[a-zA-Z0-9]+@oouth\.com$"
                            placeholder="surname.firstname@oouth.com" class="w-full px-3 py-2 border rounded">
                    </div>
                    <div>
                        <label class="block font-semibold text-sm mb-1">Bank</label>
                        <select name="bank" required class="w-full px-3 py-2 border rounded">
                            <option value="">Select Bank</option>
                            <?php
                        $query = $conn->prepare('SELECT * FROM tbl_bank');
                        $query->execute();
                        $banks = $query->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($banks as $bank) {
                            echo '<option value="'.htmlspecialchars($bank['BCODE']).'">'.htmlspecialchars($bank['BNAME']).'</option>';
                        }
                        ?>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-sm mb-1">Account No.</label>
                        <input type="number" name="acct_no" required pattern="\d{10}" maxlength="10"
                            class="w-full px-3 py-2 border rounded" placeholder="Account Number">
                    </div>
                    <div>
                        <label class="block font-semibold text-sm mb-1">Pension FA</label>
                        <select name="pfa" class="w-full px-3 py-2 border rounded">
                            <option value="">Select PFA</option>
                            <?php
                        $query = $conn->prepare('SELECT * FROM tbl_pfa');
                        $query->execute();
                        $pfas = $query->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($pfas as $pfa) {
                            echo '<option value="'.htmlspecialchars($pfa['PFACODE']).'">'.htmlspecialchars($pfa['PFANAME']).'</option>';
                        }
                        ?>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-sm mb-1">RSA PIN</label>
                        <input type="text" name="rsa_pin" class="w-full px-3 py-2 border rounded" placeholder="RSA PIN">
                    </div>
                    <div>
                        <label class="block font-semibold text-sm mb-1">Employee No</label>
                        <?php
                    $payp = $conn->prepare('SELECT Max(employee.staff_id) as "nextNo" FROM employee');
                    $payp->execute();
                    $final = $payp->fetch();
                    ?>
                        <input type="text" readonly name="emp_no" class="w-full px-3 py-2 border rounded bg-gray-100"
                            required value="<?php echo intval($final['nextNo']) + 1 ?>">
                    </div>
                    <div>
                        <label class="block font-semibold text-sm mb-1">Date of Employment</label>
                        <input type="date" name="doe" required class="w-full px-3 py-2 border rounded">
                    </div>
                    <div>
                        <label class="block font-semibold text-sm mb-1">Department</label>
                        <select name="dept" class="w-full px-3 py-2 border rounded">
                            <option value="">- - - Select Department - - -</option>
                            <?php
                        $query = $conn->prepare('SELECT * FROM tbl_dept');
                        $query->execute();
                        $depts = $query->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($depts as $dept) {
                            echo '<option value="'.htmlspecialchars($dept['dept_id']).'">'.htmlspecialchars($dept['dept']).'</option>';
                        }
                        ?>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-sm mb-1">Designation</label>
                        <input type="text" name="designation" required class="w-full px-3 py-2 border rounded"
                            placeholder="Post">
                    </div>
                    <div>
                        <label class="block font-semibold text-sm mb-1">Grade</label>
                        <input type="text" name="grade" maxlength="3" required class="w-full px-3 py-2 border rounded"
                            placeholder="Grade">
                    </div>
                    <div>
                        <label class="block font-semibold text-sm mb-1">Step</label>
                        <input type="text" name="gradestep" maxlength="2" required
                            class="w-full px-3 py-2 border rounded" placeholder="Step">
                    </div>
                </div>
                <!-- Radios -->
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-sm mb-1">Call Duty Type:</label>
                        <div class="flex gap-4 mt-1">
                            <label class="inline-flex items-center"><input type="radio" name="callType" value="0"
                                    checked class="mr-1"> None</label>
                            <label class="inline-flex items-center"><input type="radio" name="callType" value="1"
                                    class="mr-1"> Doctor</label>
                            <label class="inline-flex items-center"><input type="radio" name="callType" value="2"
                                    class="mr-1"> Others</label>
                            <label class="inline-flex items-center"><input type="radio" name="callType" value="3"
                                    class="mr-1"> Nurse</label>
                        </div>
                    </div>
                    <div>
                        <label class="block font-semibold text-sm mb-1">Hazard Type:</label>
                        <div class="flex gap-4 mt-1">
                            <label class="inline-flex items-center"><input type="radio" name="hazardType" value="1"
                                    class="mr-1"> Clinical</label>
                            <label class="inline-flex items-center"><input type="radio" name="hazardType" value="2"
                                    class="mr-1"> Non-clinical</label>
                        </div>
                    </div>
                </div>
                <!-- Actions -->
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="$('#addEmployeeModal').addClass('hidden')"
                        class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">Cancel</button>
                    <button type="submit" name="addemp"
                        class="bg-blue-700 text-white px-4 py-2 rounded hover:bg-blue-900">Add Employee</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Deactivate/Activate Modal -->
    <div id="deactivateModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 transition-all duration-150 ease-in-out hidden">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-2 p-0 overflow-hidden relative">
            <div class="flex items-center justify-between px-5 py-4 bg-blue-700 text-white">
                <div class="font-bold text-lg flex items-center gap-2">
                    <i class="fas fa-user-slash"></i> Change Employee Status
                </div>
                <button onclick="$('#deactivateModal').addClass('hidden')"
                    class="text-white hover:text-blue-300 text-2xl leading-3">&times;</button>
            </div>
            <form id="deactivateEmpForm" method="post" action="classes/controller.php?act=deactivateEmployee">
                <div class="p-5 space-y-4" id="deactivateModalBody"></div>
                <div class="flex justify-end gap-2 px-5 pb-4">
                    <button type="button" onclick="$('#deactivateModal').addClass('hidden')"
                        class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="bg-red-700 text-white px-4 py-2 rounded hover:bg-red-900">Update
                        Status</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Employee Details Modal -->
    <div id="employeeViewModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 transition-all duration-150 ease-in-out hidden">
        <div
            class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-2 p-0 overflow-hidden relative max-h-[90dvh] overflow-y-auto">
            <div class="flex items-center justify-between px-5 py-4 bg-blue-700 text-white">
                <div class="font-bold text-lg flex items-center gap-2">
                    <i class="fas fa-id-card"></i> Employee Details
                </div>
                <button onclick="$('#employeeViewModal').addClass('hidden')"
                    class="text-white hover:text-blue-300 text-2xl leading-3">&times;</button>
            </div>
            <div id="employeeViewModalBody" class="p-5">
                <div class="text-center text-gray-400 py-10">
                    <i class="fas fa-spinner fa-spin text-2xl"></i> Loading...
                </div>
            </div>
        </div>
    </div>

    <script>
    function showEditEmployeeModal(staff_id) {
        $('#editEmployeeModal').removeClass('hidden');
        $('#editEmployeeFormMsg').html('');
        $('#editEmployeeFields').html(
            '<div class="text-center text-gray-400 py-10"><i class="fas fa-spinner fa-spin text-2xl"></i> Loading...</div>'
        );
        // Fetch employee data via AJAX
        $.get('employee_view.php', {
            id: staff_id,
            edit: 1
        }, function(data) {
            if (data.error) {
                $('#editEmployeeFields').html('<div class="text-center text-red-500 p-10">' + data.error +
                    '</div>');
                return;
            }
            // Build fields
            $('#editEmployeeFields').html(`
            <input type="hidden" name="emp_no" value="${escapeHtml(data.staff_id)}">
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-sm mb-1">Full Name</label>
                    <input type="text" name="namee" value="${escapeHtml(data.NAME)}" required class="w-full px-3 py-2 border rounded">
                </div>
                <div>
                    <label class="block font-semibold text-sm mb-1">OOUTH Email</label>
                    <input type="email" name="email" value="${escapeHtml(data.EMAIL)}" required class="w-full px-3 py-2 border rounded">
                </div>
                <div>
                    <label class="block font-semibold text-sm mb-1">Bank</label>
                    <select name="bank" required class="w-full px-3 py-2 border rounded">
                        ${window.banksOptionsHtml.replace('value="'+escapeHtml(data.BCODE)+'"', 'value="'+escapeHtml(data.BCODE)+'" selected')}
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-sm mb-1">Account No.</label>
                    <input type="number" name="acct_no" value="${escapeHtml(data.ACCTNO)}" required class="w-full px-3 py-2 border rounded">
                </div>
                <div>
                    <label class="block font-semibold text-sm mb-1">Pension FA</label>
                    <select name="pfa" class="w-full px-3 py-2 border rounded">
                        ${window.pfasOptionsHtml.replace('value="'+escapeHtml(data.PFACODE)+'"', 'value="'+escapeHtml(data.PFACODE)+'" selected')}
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-sm mb-1">RSA PIN</label>
                    <input type="text" name="rsa_pin" value="${escapeHtml(data.PFAACCTNO)}" class="w-full px-3 py-2 border rounded">
                </div>
                <div>
                    <label class="block font-semibold text-sm mb-1">Date of Employment</label>
                    <input type="date" name="doe" value="${escapeHtml(data.EMPDATE)}" required class="w-full px-3 py-2 border rounded">
                </div>
                <div>
                    <label class="block font-semibold text-sm mb-1">Department</label>
                    <select name="dept" class="w-full px-3 py-2 border rounded">
                        ${window.deptsOptionsHtml.replace('value="'+escapeHtml(data.DEPTCD)+'"', 'value="'+escapeHtml(data.DEPTCD)+'" selected')}
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-sm mb-1">Designation</label>
                    <input type="text" name="designation" value="${escapeHtml(data.POST)}" required class="w-full px-3 py-2 border rounded">
                </div>
                <div>
                    <label class="block font-semibold text-sm mb-1">Grade</label>
                    <input type="text" name="grade" value="${escapeHtml(data.GRADE)}" maxlength="3" required class="w-full px-3 py-2 border rounded">
                </div>
                <div>
                    <label class="block font-semibold text-sm mb-1">Step</label>
                    <input type="text" name="gradestep" value="${escapeHtml(data.STEP)}" maxlength="2" required class="w-full px-3 py-2 border rounded">
                </div>
            </div>
            <!-- Radios -->
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-sm mb-1">Call Duty Type:</label>
                    <div class="flex gap-4 mt-1">
                        <label class="inline-flex items-center"><input type="radio" name="callType" value="0" ${data.CALLTYPE == 0 ? 'checked' : ''} class="mr-1"> None</label>
                        <label class="inline-flex items-center"><input type="radio" name="callType" value="1" ${data.CALLTYPE == 1 ? 'checked' : ''} class="mr-1"> Doctor</label>
                        <label class="inline-flex items-center"><input type="radio" name="callType" value="2" ${data.CALLTYPE == 2 ? 'checked' : ''} class="mr-1"> Others</label>
                        <label class="inline-flex items-center"><input type="radio" name="callType" value="3" ${data.CALLTYPE == 3 ? 'checked' : ''} class="mr-1"> Nurse</label>
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-sm mb-1">Hazard Type:</label>
                    <div class="flex gap-4 mt-1">
                        <label class="inline-flex items-center"><input type="radio" name="hazardType" value="1" ${data.HARZAD_TYPE == 1 ? 'checked' : ''} class="mr-1"> Clinical</label>
                        <label class="inline-flex items-center"><input type="radio" name="hazardType" value="2" ${data.HARZAD_TYPE == 2 ? 'checked' : ''} class="mr-1"> Non-clinical</label>
                    </div>
                </div>
            </div>
        `);
        }, 'json');
    }

    // Store the HTML options for select elements so we can reuse for edit modal
    window.banksOptionsHtml = $('select[name="bank"]').html();
    window.pfasOptionsHtml = $('select[name="pfa"]').html();
    window.deptsOptionsHtml = $('select[name="dept"]').html();

    // Edit Employee AJAX Submit
    $('#edit_employee_form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $msg = $('#editEmployeeFormMsg');
        $msg.removeClass().html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated!',
                        text: 'Employee updated successfully!',
                        timer: 1200,
                        showConfirmButton: false
                    }).then(function() {
                        $('#editEmployeeModal').addClass('hidden');
                        $msg.html('');
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Update Failed',
                        text: res.error || 'Unable to update employee'
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Server Error',
                    text: 'Server error. Please try again.'
                });
            }
        });
    });



    // Auto-generate OOUTH email when user leaves the 'Full Name' input
    $('input[name="namee"]').on('blur', function() {
        var name = $(this).val().trim();
        // Only generate if input is not empty and email is empty or not manually changed
        if (name && !$('input[name="email"]').data('manual')) {
            var parts = name.split(/\s+/);
            if (parts.length >= 2) {
                var first = parts[0].toLowerCase().replace(/[^a-z]/gi, '');
                var last = parts.slice(-1)[0].toLowerCase().replace(/[^a-z]/gi, '');
                var email = last + '.' + first + '@oouth.com';
                $('input[name="email"]').val(email);
            }
        }
    });

    // If user manually edits email, mark as manual to prevent overwriting
    $('input[name="email"]').on('input', function() {
        $(this).data('manual', true);
    });


    // Show Add Modal
    $('#openAddEmpModal').on('click', function() {
        $('#addEmployeeModal').removeClass('hidden');
    });

    // AJAX Add Employee Form
    $('#employee_form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $msg = $('#addEmployeeFormMsg');
        $msg.removeClass().html('<i class="fas fa-spinner fa-spin"></i> Adding...');
        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $msg.html(
                        '<span class="text-green-600"><i class="fas fa-check-circle"></i> Employee added successfully!</span>'
                    );
                    setTimeout(function() {
                        $('#addEmployeeModal').addClass('hidden');
                        $form[0].reset();
                        $msg.html('');
                        location.reload();
                    }, 1200);
                } else {
                    $msg.html(
                        '<span class="text-red-600"><i class="fas fa-exclamation-triangle"></i> ' +
                        (res.error || 'Unable to add employee') + '</span>');
                }
            },
            error: function(xhr) {
                $msg.html(
                    '<span class="text-red-600"><i class="fas fa-exclamation-triangle"></i> Server error. Please try again.</span>'
                );
            }
        });
    });
    // Deactivate/Activate Employee Modal
    function showDeactivateModal(staff_id, name, currentStatus) {
        $('#deactivateModal').removeClass('hidden');
        $('#deactivateModalBody').html(
            '<div class="py-6 text-center text-gray-500"><i class="fas fa-spinner fa-spin text-2xl"></i> Loading...</div>'
        );
        $.get('get_staff_status.php', function(statusList) {
            let options = `<option value="">Select Status</option>`;
            statusList.forEach(function(stat) {
                options +=
                    `<option value="${stat.STATUSCD}"${stat.STATUSCD===currentStatus?' selected':''}>${stat.STATUS}</option>`;
            });
            $('#deactivateModalBody').html(`
            <input type="hidden" name="empalterid" value="${staff_id}">
            <input type="hidden" name="empalternumber" value="${staff_id}">
            <div class="mb-2 text-base font-bold text-blue-900 text-center">
                Change status for <span class="text-blue-700">${name}</span> [<span class="font-mono">${staff_id}</span>]
            </div>
            <div class="mb-2 text-center text-gray-600">
                Please confirm you want to change the status of this employee.<br>
                <b>This action is logged and reversible.</b>
            </div>
            <div>
                <label class="block font-semibold mb-1 text-sm">Deactivate/Activate:</label>
                <select name="deactivate" required class="w-full px-3 py-2 border rounded">${options}</select>
            </div>
        `);
        }, 'json');
    }

    // View Employee Details Modal
    function showEmployeeModal(id) {
        $('#employeeViewModal').removeClass('hidden');
        $('#employeeViewModalBody').html(
            '<div class="text-center text-gray-400 py-10"><i class="fas fa-spinner fa-spin text-2xl"></i> Loading...</div>'
        );
        $.get('edit_employee.php', {
            id
        }, function(data) {
            if (data.error) {
                $('#employeeViewModalBody').html('<div class="text-center text-red-500 p-10">' + data.error +
                    '</div>');
                return;
            }
            $('#employeeViewModalBody').html(`
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <div>
                        <div class="font-bold text-xl text-blue-900 mb-1">${escapeHtml(data.NAME)}</div>
                        <div class="text-gray-500 text-sm">${escapeHtml(data.staff_id)}</div>
                    </div>
                    <span class="px-2 py-1 rounded text-xs font-bold
                        ${data.STATUSCD==='A'?'bg-green-100 text-green-800':'bg-red-100 text-red-800'}">
                        ${statusMap(data.STATUSCD)}
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <div><span class="text-gray-600">Department:</span> <span>${escapeHtml(data.dept||'')}</span></div>
                    <div><span class="text-gray-600">Designation:</span> <span>${escapeHtml(data.POST||'')}</span></div>
                    <div><span class="text-gray-600">Date Employed:</span> <span>${escapeHtml(data.EMPDATE||'')}</span></div>
                    <div><span class="text-gray-600">Grade/Step:</span> <span>${escapeHtml(data.GRADE+'/'+data.STEP)}</span></div>
                    <div><span class="text-gray-600">PFA:</span> <span>${escapeHtml(data.PFANAME||'')}</span></div>
                    <div><span class="text-gray-600">RSA PIN:</span> <span>${escapeHtml(data.PFAACCTNO||'')}</span></div>
                    <div><span class="text-gray-600">Bank:</span> <span>${escapeHtml(data.BNAME||'')}</span></div>
                    <div><span class="text-gray-600">Account No.:</span> <span>${escapeHtml(data.ACCTNO||'')}</span></div>
                    <div><span class="text-gray-600">Call Duty:</span> <span class="text-blue-700">${callMap(data.CALLTYPE)}</span></div>
                    <div><span class="text-gray-600">Hazard Type:</span> <span>${hazardMap(data.HARZAD_TYPE)}</span></div>
                </div>
            </div>
        `);
        }, 'json');
    }

    function escapeHtml(txt) {
        return String(txt || '').replace(/[<>"'&]/g, s => ({
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
            '&': '&amp;'
        })[s]);
    }

    function statusMap(s) {
        return {
            A: 'Active',
            D: 'Dismissed',
            T: 'Terminated',
            R: 'Resigned',
            S: 'Suspended'
        } [s] || 'Unknown';
    }

    function callMap(c) {
        return {
            0: 'None',
            1: 'Doctor',
            2: 'Others',
            3: 'Nurse'
        } [c] || '—';
    }

    function hazardMap(h) {
        return {
            1: 'Clinical',
            2: 'Non-clinical'
        } [h] || '—';
    }
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('#addEmployeeModal').addClass('hidden');
            $('#employeeViewModal').addClass('hidden');
            $('#deactivateModal').addClass('hidden');
        }
    });
    </script>
</body>

</html>