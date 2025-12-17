<?php
// Quick backup system access link
// Include this in your admin dashboard for easy access

if (isset($_SESSION['SESS_MEMBER_ID']) && $_SESSION['role'] == 'Admin') {
    echo '<div class="bg-white p-4 rounded-lg shadow-md mb-4">';
    echo '<h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">';
    echo '<i class="fas fa-database mr-2 text-blue-600"></i>Database Backup';
    echo '</h3>';
    echo '<div class="flex space-x-2">';
    echo '<a href="backup_interface.php" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors text-sm">';
    echo '<i class="fas fa-plus mr-1"></i>Create Backup';
    echo '</a>';
    echo '<a href="call_backup.php" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors text-sm">';
    echo '<i class="fas fa-list mr-1"></i>Manage Backups';
    echo '</a>';
    echo '<a href="test_backup.php" class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700 transition-colors text-sm">';
    echo '<i class="fas fa-check mr-1"></i>Test System';
    echo '</a>';
    echo '</div>';
    echo '</div>';
}
?> 