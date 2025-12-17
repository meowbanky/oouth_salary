<?php
session_start();
require_once 'classes/controller.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_permissions'])) {
    try {
        // Add pages to the pages table
        $pages = [
            ['abeokuta_variance_tracking.php', 'Abeokuta Variance Tracking'],
            ['abeokuta_variance_export.php', 'Abeokuta Variance Export']
        ];
        
        foreach ($pages as $page) {
            $query = "INSERT IGNORE INTO pages (url, name) VALUES (?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->execute($page);
        }
        
        // Get all roles
        $query = "SELECT role_id FROM roles";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Add permissions for all roles
        foreach ($roles as $role_id) {
            foreach ($pages as $page) {
                $query = "INSERT IGNORE INTO permissions (role_id, page) VALUES (?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->execute([$role_id, $page[0]]);
            }
        }
        
        $message = "Permissions set up successfully! The Abeokuta Variance Tracking system is now accessible.";
        
    } catch(PDOException $e) {
        $error = "Error setting up permissions: " . $e->getMessage();
    }
}

// Check current permissions
$currentPermissions = [];
try {
    $query = "SELECT p.page, r.role_name 
              FROM permissions p 
              JOIN roles r ON p.role_id = r.role_id 
              WHERE p.page IN ('abeokuta_variance_tracking.php', 'abeokuta_variance_export.php')
              ORDER BY r.role_name, p.page";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $currentPermissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error checking permissions: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Abeokuta Permissions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="css/dark-mode.css" rel="stylesheet">
    <script src="js/theme-manager.js"></script>
</head>

<body class="bg-gray-100 min-h-screen">
    <?php include('header.php'); ?>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                    <i class="fas fa-cog mr-2"></i>Setup Abeokuta Permissions
                </h1>
                <p class="text-gray-600">Configure permissions for the Abeokuta Variance Tracking system</p>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-check-circle mr-2"></i><?php echo $message; ?>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
            </div>
            <?php endif; ?>

            <!-- Setup Form -->
            <div class="bg-white rounded-xl shadow p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Setup Permissions</h2>
                <p class="text-gray-600 mb-4">
                    This will add the Abeokuta Variance Tracking pages to the system and grant access to all existing
                    roles.
                </p>

                <form method="POST">
                    <button type="submit" name="setup_permissions"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold shadow transition">
                        <i class="fas fa-cog mr-2"></i>Setup Permissions
                    </button>
                </form>
            </div>

            <!-- Current Permissions -->
            <div class="bg-white rounded-xl shadow p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Current Permissions</h2>

                <?php if (empty($currentPermissions)): ?>
                <p class="text-gray-600">No permissions found for Abeokuta Variance Tracking pages.</p>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-2 px-4 text-left">Role</th>
                                <th class="py-2 px-4 text-left">Page</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($currentPermissions as $permission): ?>
                            <tr class="border-b">
                                <td class="py-2 px-4"><?php echo htmlspecialchars($permission['role_name']); ?></td>
                                <td class="py-2 px-4 font-mono"><?php echo htmlspecialchars($permission['page']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- Manual Setup Instructions -->
            <div class="bg-yellow-50 rounded-xl shadow p-6 mt-6">
                <h2 class="text-lg font-semibold text-yellow-800 mb-4">
                    <i class="fas fa-info-circle mr-2"></i>Manual Setup Instructions
                </h2>
                <p class="text-yellow-700 mb-4">
                    If the automatic setup doesn't work, you can manually run the SQL script:
                </p>
                <ol class="list-decimal list-inside text-yellow-700 space-y-2">
                    <li>Open your database management tool (phpMyAdmin, MySQL Workbench, etc.)</li>
                    <li>Run the SQL script: <code
                            class="bg-yellow-200 px-2 py-1 rounded">abeokuta_variance_permissions.sql</code></li>
                    <li>Or execute these SQL commands manually:</li>
                </ol>
                <div class="bg-gray-800 text-green-400 p-4 rounded mt-4 font-mono text-sm overflow-x-auto">
                    <pre>INSERT IGNORE INTO pages (url, name) VALUES ('abeokuta_variance_tracking.php', 'Abeokuta Variance Tracking');
INSERT IGNORE INTO pages (url, name) VALUES ('abeokuta_variance_export.php', 'Abeokuta Variance Export');

-- Grant permissions to all roles
INSERT IGNORE INTO permissions (role_id, page) 
SELECT r.role_id, 'abeokuta_variance_tracking.php'
FROM roles r;

INSERT IGNORE INTO permissions (role_id, page) 
SELECT r.role_id, 'abeokuta_variance_export.php'
FROM roles r;</pre>
                </div>
            </div>
        </div>
    </div>
</body>

</html>