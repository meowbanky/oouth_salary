<?php
session_start();
if (!isset($_SESSION['SESS_MEMBER_ID']) || $_SESSION['role'] != 'Admin') {
    header("location: index.php");
    exit();
}

include_once('backup.php');

echo "<h1>Backup System Test</h1>";

try {
    // Test 1: Check if backup directory exists and is writable
    echo "<h2>Test 1: Directory Check</h2>";
    $backupDir = 'backup/';
    if (!is_dir($backupDir)) {
        echo "❌ Backup directory doesn't exist<br>";
        if (mkdir($backupDir, 0755, true)) {
            echo "✅ Created backup directory<br>";
        } else {
            echo "❌ Failed to create backup directory<br>";
        }
    } else {
        echo "✅ Backup directory exists<br>";
    }
    
    if (is_writable($backupDir)) {
        echo "✅ Backup directory is writable<br>";
    } else {
        echo "❌ Backup directory is not writable<br>";
    }
    
    // Test 2: Check database connection
    echo "<h2>Test 2: Database Connection</h2>";
    try {
        $backup = new DatabaseBackup($hostname_salary, $username_salary, $password_salary, $database_salary);
        echo "✅ Database connection successful<br>";
    } catch (Exception $e) {
        echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    }
    
    // Test 3: Check if tables exist
    echo "<h2>Test 3: Database Tables</h2>";
    if (isset($backup)) {
        try {
            $tables = $backup->getTables();
            echo "✅ Found " . count($tables) . " tables in database<br>";
            if (count($tables) > 0) {
                echo "Sample tables: " . implode(', ', array_slice($tables, 0, 5)) . "<br>";
            }
        } catch (Exception $e) {
            echo "❌ Failed to get tables: " . $e->getMessage() . "<br>";
        }
    }
    
    // Test 4: Check existing backups
    echo "<h2>Test 4: Existing Backups</h2>";
    if (isset($backup)) {
        try {
            $backups = $backup->getBackupList();
            echo "✅ Found " . count($backups) . " existing backups<br>";
            if (count($backups) > 0) {
                echo "Latest backup: " . $backups[0]['filename'] . " (" . $backups[0]['size'] . ")<br>";
            }
        } catch (Exception $e) {
            echo "❌ Failed to get backup list: " . $e->getMessage() . "<br>";
        }
    }
    
    // Test 5: Check PHP extensions
    echo "<h2>Test 5: PHP Extensions</h2>";
    if (extension_loaded('mysqli')) {
        echo "✅ MySQLi extension loaded<br>";
    } else {
        echo "❌ MySQLi extension not loaded<br>";
    }
    
    if (extension_loaded('zlib')) {
        echo "✅ Zlib extension loaded (for compression)<br>";
    } else {
        echo "❌ Zlib extension not loaded<br>";
    }
    
    // Test 6: Memory and time limits
    echo "<h2>Test 6: Server Limits</h2>";
    echo "Memory limit: " . ini_get('memory_limit') . "<br>";
    echo "Max execution time: " . ini_get('max_execution_time') . " seconds<br>";
    echo "Current memory usage: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB<br>";
    
    echo "<h2>Test Complete!</h2>";
    echo "<p>If all tests passed, your backup system should work correctly.</p>";
    echo "<p><a href='backup_interface.php'>Go to Backup Interface</a></p>";
    
} catch (Exception $e) {
    echo "<h2>Test Failed</h2>";
    echo "Error: " . $e->getMessage() . "<br>";
}
?> 