<?php

include('Connections/paymaster.php');

// Set some progress variables
$progress = 0; // Initial progress
$totalSteps = 10; // Total progress steps


if (!isset($_FILES['file']) || $_FILES['file']['error'] != UPLOAD_ERR_OK) {
    echo $_FILES['file']['error'];
    die('No file uploaded or there was an upload error');
}

$dumpFile = $_FILES["file"]["tmp_name"];
// Command to decompress the SQL dump file (gzip) and import it into the database
//$command = "gunzip < $dumpFile | mysql -h $hostname_salary -u $username_salary -p$password_salary $database_salary";

$command = "gunzip < $dumpFile | mysql -h $hostname_salary -u $username_salary -p$password_salary $database_salary 2>&1";

// Execute the command
exec($command, $output, $returnVar);

if ($returnVar === 0) {
    echo 'Restore completed successfully';
} else {
    echo 'Error Restoring Database. Debug output:' . PHP_EOL;
    echo implode(PHP_EOL, $output);
}

// echo json_encode(array('progress' => $progress, 'totalSteps' => $totalSteps));
