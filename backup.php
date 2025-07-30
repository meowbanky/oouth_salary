<?php
// Security: Check if user is authenticated
session_start();
if (!isset($_SESSION['SESS_MEMBER_ID']) || $_SESSION['role'] != 'Admin') {
    die('Unauthorized access');
}

// Include database configuration
require_once('Connections/paymaster.php');

// Configuration
define('BACKUP_DIR', 'backup/');
define('MAX_BACKUP_AGE', 30); // days
define('MAX_MEMORY_USAGE', '256M');
define('CHUNK_SIZE', 1000); // rows per chunk

// Set memory limit
ini_set('memory_limit', MAX_MEMORY_USAGE);
set_time_limit(300); // 5 minutes

class DatabaseBackup {
    private $conn;
    private $backupPath;
    private $logFile;
    private $database;
    private $errors = [];
    
    public function __construct($hostname, $username, $password, $database) {
        $this->backupPath = BACKUP_DIR;
        $this->logFile = $this->backupPath . 'backup_log.txt';
        
        // Create backup directory if it doesn't exist
        if (!is_dir($this->backupPath)) {
            if (!mkdir($this->backupPath, 0755, true)) {
                throw new Exception("Failed to create backup directory: " . $this->backupPath);
            }
        }
        
        // Check if directory is writable
        if (!is_writable($this->backupPath)) {
            throw new Exception("Backup directory is not writable: " . $this->backupPath);
        }
        
        // Connect to database
        $this->conn = new mysqli($hostname, $username, $password, $database);
        if ($this->conn->connect_error) {
            throw new Exception("Connection failed: " . $this->conn->connect_error);
        }
        
        // Set charset
        $this->conn->set_charset("utf8");
        
        // Store database name
        $this->database = $database;
    }
    
    public function createBackup() {
        try {
            $this->log("Starting database backup...");
            
            $backupFile = $this->backupPath . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $compressedFile = $backupFile . '.gz';
            
            // Clean old backups
            $this->cleanOldBackups();
            
            // Create backup file
            $this->generateBackupFile($backupFile);
            
            // Compress backup file
            $this->compressFile($backupFile, $compressedFile);
            
            // Verify backup
            if (!$this->verifyBackup($compressedFile)) {
                throw new Exception("Backup verification failed");
            }
            
            // Delete uncompressed file
            unlink($backupFile);
            
            $this->log("Backup completed successfully: " . basename($compressedFile));
            
            return basename($compressedFile);
            
        } catch (Exception $e) {
            $this->log("Backup failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function generateBackupFile($backupFile) {
        $fp = fopen($backupFile, 'w');
        if (!$fp) {
            throw new Exception("Failed to create backup file");
        }
        
        // Write header
        fwrite($fp, "-- Database Backup\n");
        fwrite($fp, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
        fwrite($fp, "-- Database: " . $this->database . "\n\n");
        
        // Get all tables
        $tables = $this->getTables();
        
        foreach ($tables as $table) {
            $this->log("Processing table: $table");
            
            // Write table structure
            $this->writeTableStructure($fp, $table);
            
            // Write table data
            $this->writeTableData($fp, $table);
            
            // Check memory usage
            if (memory_get_usage() > 50 * 1024 * 1024) { // 50MB
                gc_collect_cycles();
            }
        }
        
        fclose($fp);
    }
    
    public function getTables() {
        $tables = [];
        $result = $this->conn->query('SHOW TABLES');
        
        if (!$result) {
            throw new Exception("Error fetching tables: " . $this->conn->error);
        }
        
        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
        }
        
        return $tables;
    }
    
    private function writeTableStructure($fp, $table) {
        fwrite($fp, "-- Table structure for table `$table`\n");
        fwrite($fp, "DROP TABLE IF EXISTS `$table`;\n");
        
        $result = $this->conn->query("SHOW CREATE TABLE `$table`");
        if (!$result) {
            throw new Exception("Error getting structure for table $table: " . $this->conn->error);
        }
        
        $row = $result->fetch_row();
        fwrite($fp, $row[1] . ";\n\n");
    }
    
    private function writeTableData($fp, $table) {
        fwrite($fp, "-- Data for table `$table`\n");
        
        // Get total rows
        $countResult = $this->conn->query("SELECT COUNT(*) as count FROM `$table`");
        $totalRows = $countResult->fetch_assoc()['count'];
        
        if ($totalRows == 0) {
            fwrite($fp, "-- Table is empty\n\n");
            return;
        }
        
        // Process in chunks
        $offset = 0;
        $firstChunk = true;
        
        while ($offset < $totalRows) {
            $query = "SELECT * FROM `$table` LIMIT " . CHUNK_SIZE . " OFFSET $offset";
            $result = $this->conn->query($query);
            
            if (!$result) {
                throw new Exception("Error fetching data from table $table: " . $this->conn->error);
            }
            
            while ($row = $result->fetch_assoc()) {
                if ($firstChunk) {
                    fwrite($fp, "INSERT INTO `$table` (`" . implode('`, `', array_keys($row)) . "`) VALUES\n");
                    $firstChunk = false;
                }
                
                $values = array_map(function($value) {
                    if ($value === null) {
                        return 'NULL';
                    }
                    return "'" . $this->conn->real_escape_string($value) . "'";
                }, array_values($row));
                
                fwrite($fp, "(" . implode(', ', $values) . ")");
                
                // Add comma or semicolon
                if ($offset + $result->num_rows < $totalRows) {
                    fwrite($fp, ",\n");
                } else {
                    fwrite($fp, ";\n\n");
                }
            }
            
            $offset += CHUNK_SIZE;
        }
    }
    
    private function compressFile($source, $destination) {
        $this->log("Compressing backup file...");
        
        $sourceFile = fopen($source, 'rb');
        $destFile = gzopen($destination, 'wb9');
        
        if (!$sourceFile || !$destFile) {
            throw new Exception("Failed to open files for compression");
        }
        
        while (!feof($sourceFile)) {
            gzwrite($destFile, fread($sourceFile, 4096));
        }
        
        fclose($sourceFile);
        gzclose($destFile);
    }
    
    private function verifyBackup($backupFile) {
        $this->log("Verifying backup file...");
        
        if (!file_exists($backupFile)) {
            return false;
        }
        
        $fileSize = filesize($backupFile);
        if ($fileSize < 1024) { // Less than 1KB
            return false;
        }
        
        return true;
    }
    
    private function cleanOldBackups() {
        $this->log("Cleaning old backups...");
        
        $files = glob($this->backupPath . 'backup_*.sql.gz');
        $cutoff = time() - (MAX_BACKUP_AGE * 24 * 60 * 60);
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                $this->log("Deleted old backup: " . basename($file));
            }
        }
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        
        // Write to log file (with error handling)
        if ($this->logFile && is_writable(dirname($this->logFile))) {
            file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        }
        
        // Also output for immediate feedback
        echo $logMessage;
        flush();
    }
    
    public function downloadBackup($filename) {
        $filepath = $this->backupPath . $filename;
        
        if (!file_exists($filepath)) {
            throw new Exception("Backup file not found");
        }
        
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set headers for download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        
        // Read and output file
        $handle = fopen($filepath, 'rb');
        while (!feof($handle)) {
            echo fread($handle, 8192);
            flush();
        }
        fclose($handle);
        
        exit;
    }
    
    public function getBackupList() {
        $files = glob($this->backupPath . 'backup_*.sql.gz');
        $backups = [];
        
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => $this->formatBytes(filesize($file)),
                'date' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }
        
        // Sort by date (newest first)
        usort($backups, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        return $backups;
    }
    
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

// Handle backup creation
if (isset($_POST['action']) && $_POST['action'] === 'create_backup') {
    // Start output buffering to capture log messages
    ob_start();
    
    try {
        $backup = new DatabaseBackup($hostname_salary, $username_salary, $password_salary, $database_salary);
        $filename = $backup->createBackup();
        
        // Get the log output
        $logOutput = ob_get_clean();
        
        echo json_encode([
            'success' => true,
            'message' => 'Backup created successfully',
            'filename' => $filename,
            'log' => $logOutput
        ]);
    } catch (Exception $e) {
        // Get any log output before the error
        $logOutput = ob_get_clean();
        
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'log' => $logOutput
        ]);
    }
    exit;
}

// Handle backup download
if (isset($_GET['action']) && $_GET['action'] === 'download' && isset($_GET['file'])) {
    try {
        $backup = new DatabaseBackup($hostname_salary, $username_salary, $password_salary, $database_salary);
        $backup->downloadBackup($_GET['file']);
    } catch (Exception $e) {
        die('Download failed: ' . $e->getMessage());
    }
}
?>