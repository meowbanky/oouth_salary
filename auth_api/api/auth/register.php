<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

require_once '../../config/Database.php';
require_once '../../models/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $data = json_decode(file_get_contents("php://input"));

    if (!$data || !isset($data->name) || !isset($data->email) || !isset($data->password)) {
        throw new Exception('Missing required fields');
    }

    $database = new Database();
    $db = $database->getConnection();

    $user = new User($db);
    $result = $user->register($data->name, $data->email, $data->password);

    if ($result['success']) {
        http_response_code(201);
        echo json_encode($result);
    } else {
        throw new Exception($result['message']);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}