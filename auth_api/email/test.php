<?php
// test.php
require_once 'email_manager.php';

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Manager Test</title>
    <style>
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
<h1>Email Manager Test</h1>

<?php
try {
    $manager = new EmailAccountManager('oouthco', 'Banky@123');

    echo "<h2>List Email Accounts</h2>";
    $result = $manager->listEmailAccounts();

    echo "<pre>";
    print_r($result);
    echo "</pre>";

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>Error Occurred</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
</body>
</html>