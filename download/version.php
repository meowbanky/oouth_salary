<?php
// download/version.php
// API endpoint to serve version information

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Max-Age: 3600');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Try to read version.json from download directory
$versionFile = __DIR__ . '/version.json';

if (file_exists($versionFile)) {
    $versionData = json_decode(file_get_contents($versionFile), true);
    
    if ($versionData) {
        http_response_code(200);
        echo json_encode($versionData);
        exit();
    }
}

// Fallback: return default version info
http_response_code(200);
echo json_encode([
    'version' => '1.0.0',
    'build_number' => '1',
    'changelog' => '- Initial release',
    'force_update' => false,
    'download_url' => 'https://oouthsalary.com.ng/download.html',
    'release_date' => date('Y-m-d')
]);

