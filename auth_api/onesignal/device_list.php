<?php
// test_devices.php

// Load environment variables from .env file
if (file_exists(__DIR__ . '/../../.env')) {
    $lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        $value = trim($value, '"\'');
        $_ENV[$name] = $value;
        putenv($name . '=' . $value);
    }
}

// Alternative: Use DotEnv library if available
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    try {
        require_once __DIR__ . '/../../vendor/autoload.php';
        if (class_exists('Dotenv\Dotenv')) {
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
            $dotenv->load();
        }
    } catch (Exception $e) {
        error_log("DotEnv loading failed: " . $e->getMessage());
    }
}

require_once __DIR__ . '/../config/Database.php';
$database = new Database();
$db = $database->getConnection();

$appId = getenv('ONESIGNAL_APP_ID') ?: $_ENV['ONESIGNAL_APP_ID'] ?? "c04a0f15-e70b-4d40-a3c6-284b1898b5b6";
$apiKey = getenv('ONESIGNAL_REST_API_KEY') ?: $_ENV['ONESIGNAL_REST_API_KEY'] ?? '';

if (empty($apiKey)) {
    die("ERROR: OneSignal REST API Key not configured! Set ONESIGNAL_REST_API_KEY environment variable in .env file.");
}

// Get all registered devices from OneSignal
function getRegisteredDevices() {
    global $appId, $apiKey;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/players?app_id=$appId&limit=300");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Basic " . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        $data = json_decode($response, true);
        return $data['players'] ?? [];
    }
    return [];
}

// Display registered devices
$devices = getRegisteredDevices();
echo "<h2>Registered Devices (" . count($devices) . ")</h2>";
echo "<pre>";
foreach ($devices as $device) {
    echo "Device ID: " . $device['id'] . "\n";
    echo "Device Type: " . $device['device_type'] . "\n";
    echo "Device Model: " . ($device['device_model'] ?? 'Unknown') . "\n";
    echo "Last Active: " . date('Y-m-d H:i:s', $device['last_active']) . "\n";
    echo "Invalid Identifier: " . ($device['invalid_identifier'] ? 'Yes' : 'No') . "\n";
    echo "------------------------\n";
}
echo "</pre>";
?>