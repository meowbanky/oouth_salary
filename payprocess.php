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

if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) === '') {
    header("Location: index.php");
    exit;
}

// Check for processing errors
$processingerrors = false;
$missing = [];
$setbasic = 0;
$missingbasic = 0;

try {
    // Get active employees
    $query = $conn->prepare('SELECT staff_id FROM employee WHERE STATUSCD = ? ORDER BY staff_id ASC');
    $query->execute(['A']);
    $employees = $query->fetchAll(PDO::FETCH_ASSOC);
    $employeecount = count($employees);

    // Check each employee's payroll data
    foreach ($employees as $employee) {
        $staff_id = $employee['staff_id'];
        
        // Get allowances
        $allowance_query = $conn->prepare('SELECT ANY_VALUE(Sum(allow_deduc.`value`)) AS allowance, ANY_VALUE(allow_deduc.allow_id) AS allow_id, ANY_VALUE(tbl_earning_deduction.ed) AS ed FROM allow_deduc INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = allow_deduc.allow_id WHERE staff_id = ? and transcode = ? GROUP BY staff_id');
        $allowance_query->execute([$staff_id, '01']);
        $allowances = $allowance_query->fetchAll(PDO::FETCH_ASSOC);
        
        $allowance = 0;
        if ($allowances) {
            foreach ($allowances as $allow) {
                $allowance = $allow['allowance'];
            }
        }

        // Get deductions
        $deduction_query = $conn->prepare('SELECT any_value(Sum(allow_deduc.`value`)) as deductions, any_value(allow_deduc.allow_id) as allow_id, any_value(tbl_earning_deduction.ed) as ed FROM allow_deduc INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = allow_deduc.allow_id WHERE staff_id = ? and transcode = ? GROUP BY staff_id');
        $deduction_query->execute([$staff_id, '02']);
        $deductions = $deduction_query->fetchAll(PDO::FETCH_ASSOC);
        
        $deduction = 0;
        if ($deductions) {
            foreach ($deductions as $ded) {
                $deduction = $ded['deductions'];
            }
        }

        $net = $allowance - $deduction;
        if ($net >= 0) {
            $setbasic++;
        } else {
            $missingbasic++;
            $missing[] = $staff_id . ' => ' . number_format($net, 2);
        }
    }

    if ($missingbasic > 0) {
        $processingerrors = true;
        $_SESSION['msg'] = $missingbasic . ' employees have negative net pay. Please correct this to be able to run payroll.';
        $_SESSION['alertcolor'] = 'danger';
    }

} catch (PDOException $e) {
    error_log("Database error in payprocess.php: " . $e->getMessage());
    $processingerrors = true;
    $_SESSION['msg'] = 'Database error occurred while checking payroll data.';
    $_SESSION['alertcolor'] = 'danger';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Processing - Salary Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/dark-mode.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/theme-manager.js"></script>
    
    <style>
        .progress-bar {
            background: linear-gradient(90deg, #3B82F6 0%, #1D4ED8 100%);
            height: 20px;
            border-radius: 10px;
            transition: width 0.3s ease;
        }
        
        .processing-status {
            min-height: 200px;
            border: 1px solid #E5E7EB;
            border-radius: 0.5rem;
            background: #F9FAFB;
        }
        
        .status-item {
            padding: 0.75rem;
            border-bottom: 1px solid #E5E7EB;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .status-item:last-child {
            border-bottom: none;
        }
        
        .status-success {
            color: #059669;
        }
        
        .status-error {
            color: #DC2626;
        }
        
        .status-warning {
            color: #D97706;
        }
        
        .status-info {
            color: #2563EB;
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
                <span>Payroll Processing</span>
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
                <i class="fas fa-calculator mr-2"></i> Payroll Processing
                <small class="text-base text-gray-600 ml-2">Run final payroll processing sequence</small>
            </h1>
            
            <div class="max-w-6xl mx-auto">
                <!-- Pre-requisites Check -->
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-clipboard-check text-blue-600 mr-2"></i>
                        <h2 class="text-xl font-semibold text-gray-800">Pre-requisites Check</h2>
                    </div>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <p class="text-blue-800 font-medium">
                            <i class="fas fa-info-circle mr-2"></i>
                            Before running the final payroll sequence, please ensure all pre-requisites regarding employee earnings and deductions have been fulfilled.
                        </p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Total Employees</span>
                                <span class="text-2xl font-bold text-gray-800"><?php echo $employeecount; ?></span>
                            </div>
                        </div>
                        
                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="flex items-center justify-between">
                                <span class="text-green-600">Valid Payroll</span>
                                <span class="text-2xl font-bold text-green-800"><?php echo $setbasic; ?></span>
                            </div>
                        </div>
                        
                        <div class="bg-red-50 p-4 rounded-lg">
                            <div class="flex items-center justify-between">
                                <span class="text-red-600">Issues Found</span>
                                <span class="text-2xl font-bold text-red-800"><?php echo $missingbasic; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($missingbasic > 0): ?>
                        <div class="mt-6 bg-red-50 border border-red-200 rounded-lg p-4">
                            <h3 class="text-red-800 font-semibold mb-2">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                Payroll Issues Detected
                            </h3>
                            <p class="text-red-700 mb-3">The following employees have negative net pay:</p>
                            <div class="bg-white border border-red-200 rounded p-3 max-h-40 overflow-y-auto">
                                <?php foreach ($missing as $issue): ?>
                                    <div class="text-sm text-red-600 py-1"><?php echo htmlspecialchars($issue); ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Payroll Processing Form -->
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <div class="flex items-center mb-6">
                        <i class="fas fa-cog text-indigo-600 mr-2"></i>
                        <h2 class="text-xl font-semibold text-gray-800">Payroll Processing</h2>
                    </div>
                    
                    <form id="payrollForm" method="post" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Current Active Payroll Period
                                </label>
                                <input type="text" id="activeperiod" name="activeperiod" 
                                       value="<?php echo htmlspecialchars($_SESSION['activeperiodDescription'] ?? ''); ?>" 
                                       class="w-full p-3 border border-gray-300 rounded-md bg-gray-50" readonly>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Processing Status
                                </label>
                                <div class="flex items-center space-x-2">
                                    <div class="flex-1 bg-gray-200 rounded-full h-2">
                                        <div id="progressBar" class="progress-bar h-2 rounded-full" style="width: 0%"></div>
                                    </div>
                                    <span id="progressText" class="text-sm text-gray-600">0%</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Processing Status Display -->
                        <div class="processing-status p-4">
                            <h3 class="font-semibold text-gray-800 mb-3">Processing Status</h3>
                            <div id="processingStatus" class="space-y-2">
                                <div class="text-gray-500 text-center py-8">
                                    <i class="fas fa-clock text-2xl mb-2"></i>
                                    <p>Ready to process payroll</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-center space-x-4">
                            <?php if (isset($_SESSION['periodstatuschange']) && $_SESSION['periodstatuschange'] == '1'): ?>
                                <button type="button" disabled 
                                        class="px-6 py-3 bg-yellow-500 text-white rounded-lg opacity-50 cursor-not-allowed flex items-center">
                                    <i class="fas fa-lock mr-2"></i>
                                    Viewing Closed Period
                                </button>
                            <?php elseif ($processingerrors): ?>
                                <button type="button" disabled 
                                        class="px-6 py-3 bg-red-500 text-white rounded-lg opacity-50 cursor-not-allowed flex items-center">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    Fix Errors First
                                </button>
                            <?php else: ?>
                                <button type="submit" id="payprocessbtn" 
                                        class="px-8 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:ring-4 focus:ring-red-200 transition-all duration-200 flex items-center">
                                    <i class="fas fa-cog mr-2"></i>
                                    Process Payroll
                                </button>
                            <?php endif; ?>
                            
                            <button type="button" id="refreshBtn" 
                                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 transition-all duration-200 flex items-center">
                                <i class="fas fa-sync-alt mr-2"></i>
                                Refresh Status
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    let isProcessing = false;
    
    $('#payrollForm').submit(function(e) {
        e.preventDefault();
        
        if (isProcessing) {
            return false;
        }
        
        Swal.fire({
            title: 'Confirm Payroll Processing',
            text: 'Are you sure you want to run payroll for ' + $('#activeperiod').val() + '?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#DC2626',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Yes, Process Payroll',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                startPayrollProcessing();
            }
        });
    });
    
    $('#refreshBtn').click(function() {
        location.reload();
    });
    
    function startPayrollProcessing() {
        isProcessing = true;
        
        // Update button state
        $('#payprocessbtn').prop('disabled', true);
        $('#payprocessbtn').html('<i class="fas fa-spinner fa-spin mr-2"></i>Processing...');
        
        // Clear previous status
        $('#processingStatus').empty();
        $('#progressBar').css('width', '0%');
        $('#progressText').text('0%');
        
        // Add initial status
        addStatusItem('Starting payroll processing...', 'info');
        
        $.ajax({
            type: 'GET',
            url: 'classes/runPayroll.php',
            xhrFields: {
                onprogress: function(e) {
                    try {
                        const response = e.target.responseText;
                        updateProgress(response);
                    } catch (error) {
                        console.log('Progress update error:', error);
                    }
                }
            },
            success: function(response, status) {
                isProcessing = false;
                $('#payprocessbtn').prop('disabled', false);
                $('#payprocessbtn').html('<i class="fas fa-cog mr-2"></i>Process Payroll');
                
                if (status === 'success') {
                    addStatusItem('Payroll processing completed successfully!', 'success');
                    $('#progressBar').css('width', '100%');
                    $('#progressText').text('100%');
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Payroll Processed Successfully!',
                        text: 'The payroll for ' + $('#activeperiod').val() + ' has been processed successfully.',
                        timer: 3000,
                        showConfirmButton: false
                    });
                } else {
                    addStatusItem('Payroll processing failed', 'error');
                    Swal.fire({
                        icon: 'error',
                        title: 'Processing Failed',
                        text: 'An error occurred during payroll processing.'
                    });
                }
            },
            error: function(xhr, status, error) {
                isProcessing = false;
                $('#payprocessbtn').prop('disabled', false);
                $('#payprocessbtn').html('<i class="fas fa-cog mr-2"></i>Process Payroll');
                
                addStatusItem('Payroll processing failed: ' + error, 'error');
                
                Swal.fire({
                    icon: 'error',
                    title: 'Processing Failed',
                    text: 'An error occurred during payroll processing: ' + error
                });
            }
        });
    }
    
    function updateProgress(response) {
        try {
            // Parse the response to extract progress information
            const lines = response.split('\n');
            let progress = 0;
            let currentStep = '';
            
            lines.forEach(line => {
                if (line.includes('Progress:')) {
                    const match = line.match(/(\d+)%/);
                    if (match) {
                        progress = parseInt(match[1]);
                    }
                }
                if (line.includes('Processing:') || line.includes('Step:')) {
                    currentStep = line.replace(/.*Processing:\s*|.*Step:\s*/, '').trim();
                }
            });
            
            // Update progress bar
            $('#progressBar').css('width', progress + '%');
            $('#progressText').text(progress + '%');
            
            // Add status item if there's a new step
            if (currentStep && !$('#processingStatus').text().includes(currentStep)) {
                addStatusItem(currentStep, 'info');
            }
            
        } catch (error) {
            console.log('Progress parsing error:', error);
        }
    }
    
    function addStatusItem(message, type) {
        const statusClass = {
            'success': 'status-success',
            'error': 'status-error',
            'warning': 'status-warning',
            'info': 'status-info'
        };
        
        const iconClass = {
            'success': 'fas fa-check-circle',
            'error': 'fas fa-times-circle',
            'warning': 'fas fa-exclamation-triangle',
            'info': 'fas fa-info-circle'
        };
        
        const statusItem = `
            <div class="status-item">
                <div class="flex items-center">
                    <i class="${iconClass[type]} ${statusClass[type]} mr-2"></i>
                    <span class="text-sm">${message}</span>
                </div>
                <span class="text-xs text-gray-500">${new Date().toLocaleTimeString()}</span>
            </div>
        `;
        
        $('#processingStatus').append(statusItem);
        
        // Scroll to bottom
        const statusContainer = document.getElementById('processingStatus');
        statusContainer.scrollTop = statusContainer.scrollHeight;
    }
});
</script>

<?php include 'footer.php'; ?>
</body>
</html>