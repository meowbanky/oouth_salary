<?php
// Database credentials
function backup()
{
    require_once('Connections/paymaster.php');

    $hostname_salary = "localhost";
    $username_salary = "root";
    $database_salary = "emmaggic_coop";
    $password_salary = "Oluwaseyi";

    // File path and name for the dump file
    $dumpFilePath = 'backup/';
    $dumpFileName = 'backup_' . date('Y-m-d_H-i-s') . '.sql.gz';

    // MySQL dump command
    $command = "mysqldump --host={$hostname_salary} --user={$username_salary} --password={$password_salary} {$database_salary} | gzip > {$dumpFilePath}{$dumpFileName}";

    // Execute the command
    exec($command);

    // Download the compressed dump
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $dumpFileName . '"');
    readfile($dumpFilePath . $dumpFileName);

    // Delete the local dump file
    unlink($dumpFilePath . $dumpFileName);
}
backup();
