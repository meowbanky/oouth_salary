<?php
require_once('Connections/paymaster.php');

function backup($hostname_salary, $username_salary, $password_salary, $database_salary)
{
    // Connect to MySQL server
    $conn = new mysqli($hostname_salary, $username_salary, $password_salary, $database_salary);

    // $backupFile = "backup_" . date("Y-m-d_H-i-s") . ".sql.gz";

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // File path and name for the dump file
    $dumpFilePath = 'backup/';
    $dumpFileName = 'backup_' . date('Y-m-d_H-i-s') . '.sql.gz';

    // Open gzip file for writing
    $fp = gzopen($dumpFilePath . $dumpFileName, 'w9'); // Adjust compression level (0-9) as needed

    if (!$fp) {
        die("Failed to create backup file");
    }

    // Function to escape database content for SQL statements
    function escape_sql_content($content, $conn)
    {
        return $conn->real_escape_string($content);
    }

    // Get list of all tables
    $result = $conn->query('SHOW TABLES');
    if (!$result) {
        die("Error fetching tables: " . $conn->error);
    }

    // Iterate over tables and write SQL to gzip file
    while ($row = $result->fetch_row()) {
        $table = $row[0];
        gzwrite($fp, "DROP TABLE IF EXISTS `$table`;\n");

        // Fetch table creation statement
        $res = $conn->query("SHOW CREATE TABLE `$table`");
        $createTable = $res->fetch_row();
        gzwrite($fp, $createTable[1] . ";\n\n");

        // Fetch table data
        $dataResult = $conn->query("SELECT * FROM `$table`");
        while ($dataRow = $dataResult->fetch_assoc()) {
            $vals = array_map(function ($val) use ($conn) {
                return isset($val) ? "'" . escape_sql_content($val, $conn) . "'" : 'NULL';
            }, array_values($dataRow));

            gzwrite($fp, "INSERT INTO `$table` VALUES (" . implode(', ', $vals) . ");\n");
        }
        gzwrite($fp, "\n\n");
    }

    // Close the gzip file and MySQL connection
    gzclose($fp);
    $conn->close();

    // Download section
    if (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($dumpFilePath . $dumpFileName) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($dumpFilePath . $dumpFileName));

    flush();
    readfile($dumpFilePath . $dumpFileName);


    // Optional: Delete the file after download
    unlink($dumpFilePath . $dumpFileName);

    exit;
}
