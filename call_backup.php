<?php
session_start();
if (!isset($_SESSION['SESS_MEMBER_ID']) || $_SESSION['role'] != 'Admin') {
    header("location: index.php");
    exit();
}

include_once('backup.php');

// Initialize backup object
$backup = new DatabaseBackup($hostname_salary, $username_salary, $password_salary, $database_salary);

// Get list of existing backups
$backups = $backup->getBackupList();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Backup - Salary Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-100">
    <?php include 'header.php'; ?>

    <div class="flex min-h-screen">
        <?php include 'sidebar.php'; ?>

        <div class="flex-1 p-6">
            <div class="container mx-auto">
                <nav class="mb-6">
                    <a href="home.php" class="text-blue-600 hover:underline"><i class="fas fa-home"></i> Dashboard</a>
                    <span class="mx-2">/</span>
                    <span>Database Backup</span>
                </nav>

                <h1 class="text-3xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-database mr-2"></i> Database Backup
                    <small class="text-base text-gray-600 ml-2">Manage database backups</small>
                </h1>

                <!-- Backup Actions -->
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Backup Actions</h2>

                    <div class="flex space-x-4">
                        <button id="create-backup-btn"
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-plus mr-2"></i>Create New Backup
                        </button>

                        <button id="refresh-list-btn"
                            class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                            <i class="fas fa-refresh mr-2"></i>Refresh List
                        </button>
                    </div>

                    <div id="backup-progress" class="mt-4 hidden">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mr-3"></div>
                                <span class="text-blue-800">Creating backup... Please wait.</span>
                            </div>
                            <div id="backup-log" class="mt-2 text-sm text-blue-700 max-h-32 overflow-y-auto"></div>
                        </div>
                    </div>
                </div>

                <!-- Backup List -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Available Backups</h2>

                    <?php if (empty($backups)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-database text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-600">No backups found. Create your first backup to get started.</p>
                    </div>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-200">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="py-3 px-4 text-left font-semibold text-gray-700">Backup File</th>
                                    <th class="py-3 px-4 text-left font-semibold text-gray-700">Size</th>
                                    <th class="py-3 px-4 text-left font-semibold text-gray-700">Created</th>
                                    <th class="py-3 px-4 text-left font-semibold text-gray-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $backup): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-3 px-4">
                                        <div class="flex items-center">
                                            <i class="fas fa-file-archive text-blue-600 mr-2"></i>
                                            <span
                                                class="font-mono text-sm"><?php echo htmlspecialchars($backup['filename']); ?></span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-gray-600"><?php echo $backup['size']; ?></td>
                                    <td class="py-3 px-4 text-gray-600"><?php echo $backup['date']; ?></td>
                                    <td class="py-3 px-4">
                                        <div class="flex space-x-2">
                                            <a href="backup.php?action=download&file=<?php echo urlencode($backup['filename']); ?>"
                                                class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700 transition-colors">
                                                <i class="fas fa-download mr-1"></i>Download
                                            </a>
                                            <button
                                                onclick="deleteBackup('<?php echo htmlspecialchars($backup['filename']); ?>')"
                                                class="px-3 py-1 bg-red-600 text-white rounded text-sm hover:bg-red-700 transition-colors">
                                                <i class="fas fa-trash mr-1"></i>Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Backup Information -->
                <div class="bg-white p-6 rounded-lg shadow-md mt-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Backup Information</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-clock text-blue-600 mr-2"></i>
                                <div>
                                    <h3 class="font-semibold text-blue-800">Backup Retention</h3>
                                    <p class="text-blue-600"><?php echo MAX_BACKUP_AGE; ?> days</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-compress text-green-600 mr-2"></i>
                                <div>
                                    <h3 class="font-semibold text-green-800">Compression</h3>
                                    <p class="text-green-600">GZIP (Level 9)</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-purple-50 p-4 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-memory text-purple-600 mr-2"></i>
                                <div>
                                    <h3 class="font-semibold text-purple-800">Memory Limit</h3>
                                    <p class="text-purple-600"><?php echo MAX_MEMORY_USAGE; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
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

            // Show confirmation
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

        // Refresh list button
        $('#refresh-list-btn').click(function() {
            location.reload();
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
            data: {
                action: 'create_backup'
            },
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
                        location.reload();
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

    function deleteBackup(filename) {
        Swal.fire({
            title: 'Delete Backup?',
            text: `Are you sure you want to delete "${filename}"? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Add delete functionality here if needed
                Swal.fire({
                    icon: 'info',
                    title: 'Delete Functionality',
                    text: 'Delete functionality can be added here. For now, you can manually delete files from the backup directory.'
                });
            }
        });
    }
    </script>

    <?php include 'footer.php'; ?>
</body>

</html>