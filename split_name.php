<?php
header('Content-Type: text/plain');

// Start session to access database connection
session_start();
require_once('Connections/paymaster.php');

// Get the name from POST
$name = isset($_POST['namee']) ? trim($_POST['namee']) : '';

if (empty($name)) {
    echo '';
    exit;
}

// Split the name into parts
$nameParts = explode(' ', $name);
if (count($nameParts) < 2) {
    echo '';
    exit;
}

// Assume last part is surname, first part is firstname
$surname = strtolower(trim($nameParts[count($nameParts) - 1]));
$firstname = strtolower(trim($nameParts[0]));

// Format email as surname.firstname@oouth.com
$email = "{$surname}.{$firstname}@oouth.com";

// Clean any invalid suffixes (just in case)
$email = str_replace(['@tasce.com', '.tasce.com'], '', $email);
$email = trim($email, '.');

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strpos($email, '@oouth.com') === false) {
    echo '';
    exit;
}

// Check if email already exists in the employee table
try {
    $query = $conn->prepare('SELECT COUNT(*) AS count FROM employee WHERE EMAIL = ?');
    $query->execute([$email]);
    $result = $query->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] > 0) {
        // Append a number if email exists (e.g., doe.john2@oouth.com)
        $i = 2;
        while (true) {
            $newEmail = "{$surname}.{$firstname}{$i}@oouth.com";
            $query = $conn->prepare('SELECT COUNT(*) AS count FROM employee WHERE EMAIL = ?');
            $query->execute([$newEmail]);
            $result = $query->fetch(PDO::FETCH_ASSOC);
            if ($result['count'] == 0) {
                $email = $newEmail;
                break;
            }
            $i++;
        }
    }
} catch (PDOException $e) {
    echo '';
    exit;
}

// Output the formatted email
echo $email;
?>