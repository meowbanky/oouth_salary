<?php
session_start();
if (!isset($_SESSION['SESS_MEMBER_ID']) || $_SESSION['role'] != 'Admin') {
    header("location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Backup - Debug Mode</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-4xl w-full bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <i class="fas fa-bug text-4xl text-red-600 mb-4"></i>
                <h1 class="text-2xl font-bold text-gray-800">Database Backup - Debug Mode</h1>
                <p class="text-gray-600 mt-2">Debug version with detailed logging</p>
            </div>

            <div class="space-y-4">
                <button id="create-backup-btn" class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Create New Backup (Debug)
                </button>
                
                <a href="backup_interface.php" class="block w-full px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-center">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Normal Interface
                </a>
            </div>

            <div id="backup-progress" class="mt-6 hidden">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mr-3"></div>
                        <span class="text-blue-800">Creating backup... Please wait.</span>
                    </div>
                    <div id="backup-log" class="mt-2 text-sm text-blue-700 max-h-32 overflow-y-auto"></div>
                </div>
            </div>

            <!-- Debug Information -->
            <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                <h3 class="font-semibold text-gray-800 mb-2">Debug Information:</h3>
                <div id="debug-info" class="text-sm text-gray-600 space-y-1">
                    <div>Session ID: <?php echo $_SESSION['SESS_MEMBER_ID'] ?? 'Not set'; ?></div>
                    <div>User Role: <?php echo $_SESSION['role'] ?? 'Not set'; ?></div>
                    <div>PHP Version: <?php echo PHP_VERSION; ?></div>
                    <div>Memory Limit: <?php echo ini_get('memory_limit'); ?></div>
                    <div>Max Execution Time: <?php echo ini_get('max_execution_time'); ?> seconds</div>
                </div>
            </div>

            <!-- AJAX Response Debug -->
            <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h3 class="font-semibold text-yellow-800 mb-2">AJAX Response Debug:</h3>
                <div id="ajax-debug" class="text-sm text-yellow-700 max-h-32 overflow-y-auto">
                    <div class="text-gray-500">No AJAX requests yet...</div>
                </div>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Create backup button
        $('#create-backup-btn').click(function() {
            const btn = $(this);
            const originalText = btn.html();
            
            Swal.fire({
                title: 'Create Database Backup?',
                text: 'This will create a new backup of the entire database. This process may take several minutes.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, create backup!'
            }).then((result) => {
                if (result.isConfirmed) {
                    createBackup();
                }
            });
        });
    });

    function createBackup() {
        const btn = $('#create-backup-btn');
        const originalText = btn.html();
        
        // Disable button and show progress
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Creating Backup...');
        $('#backup-progress').removeClass('hidden');
        $('#backup-log').html('');
        $('#ajax-debug').html('<div class="text-blue-600">Starting AJAX request...</div>');
        
        // Make AJAX request
        $.ajax({
            url: 'backup.php',
            type: 'POST',
            data: { action: 'create_backup' },
            dataType: 'json',
            success: function(response) {
                console.log('AJAX Success Response:', response);
                $('#ajax-debug').html(`
                    <div class="text-green-600 font-semibold">✅ AJAX Success</div>
                    <div class="mt-2"><strong>Response Type:</strong> ${typeof response}</div>
                    <div><strong>Success:</strong> ${response.success}</div>
                    <div><strong>Message:</strong> ${response.message}</div>
                    <div><strong>Filename:</strong> ${response.filename || 'N/A'}</div>
                    <div class="mt-2"><strong>Raw Response:</strong></div>
                    <pre class="text-xs bg-white p-2 rounded border overflow-x-auto">${JSON.stringify(response, null, 2)}</pre>
                `);
                
                // Show log output in progress area
                if (response.log) {
                    $('#backup-log').html(response.log.replace(/\n/g, '<br>'));
                }
                
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Backup Created!',
                        text: response.message + '\n\nFile: ' + response.filename,
                        timer: 3000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'call_backup.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Backup Failed',
                        text: response.message
                    });
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', {xhr, status, error});
                console.log('Response Text:', xhr.responseText);
                
                $('#ajax-debug').html(`
                    <div class="text-red-600 font-semibold">❌ AJAX Error</div>
                    <div class="mt-2"><strong>Status:</strong> ${status}</div>
                    <div><strong>Error:</strong> ${error}</div>
                    <div><strong>HTTP Status:</strong> ${xhr.status}</div>
                    <div class="mt-2"><strong>Response Text:</strong></div>
                    <pre class="text-xs bg-white p-2 rounded border overflow-x-auto">${xhr.responseText}</pre>
                `);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Backup Failed',
                    text: 'An error occurred while creating the backup. Check the debug information below.'
                });
            },
            complete: function() {
                // Re-enable button
                btn.prop('disabled', false).html(originalText);
                $('#backup-progress').addClass('hidden');
            }
        });
    }
    </script>
</body>
</html> 