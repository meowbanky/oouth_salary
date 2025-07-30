<?php
session_start();
if (!isset($_SESSION['SESS_MEMBER_ID']) || $_SESSION['role'] != 'Admin') {
    header("location: index.php");
    exit();
}

// Simple backup interface
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Backup - Simple Interface</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <i class="fas fa-database text-4xl text-blue-600 mb-4"></i>
                <h1 class="text-2xl font-bold text-gray-800">Database Backup</h1>
                <p class="text-gray-600 mt-2">Create and manage database backups</p>
            </div>

            <div class="space-y-4">
                <button id="create-backup-btn" class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Create New Backup
                </button>
                
                <button id="view-backups-btn" class="w-full px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-list mr-2"></i>View All Backups
                </button>
                
                <a href="home.php" class="block w-full px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors text-center">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
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

        // View backups button
        $('#view-backups-btn').click(function() {
            window.location.href = 'call_backup.php';
        });
    });

    function createBackup() {
        const btn = $('#create-backup-btn');
        const originalText = btn.html();
        
        // Disable button and show progress
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Creating Backup...');
        $('#backup-progress').removeClass('hidden');
        $('#backup-log').html('');
        
        // Make AJAX request
        $.ajax({
            url: 'backup.php',
            type: 'POST',
            data: { action: 'create_backup' },
            dataType: 'json',
            success: function(response) {
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
                console.log('AJAX Error:', xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Backup Failed',
                    text: 'An error occurred while creating the backup. Please check the console for details.'
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