<?php
include('Connections/paymaster.php');

// Set some progress variables
$progress = 0; // Initial progress
$totalSteps = 10; // Total progress steps

// Path to the compressed SQL dump file
$dumpFile = $_FILES["file"]["tmp_name"]; //'path_to_your_dump_file.sql.gz';

// Command to decompress the SQL dump file (gzip) and import it into the database
$command = "gunzip < $dumpFile | mysql -h $hostname_salary -u $username_salary -p$password_salary $database_salary";

// Execute the command
exec($command, $output, $returnVar);

if ($returnVar === 0) {
    echo  'Restore completed successfully';
} else {
    echo 'Error Restoring Database'; // Restore failed
}

// echo json_encode(array('progress' => $progress, 'totalSteps' => $totalSteps));
