<?php
require_once 'App.php';
$App = new App;

$response = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role_id = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);

    if ($role_id) {
        try {
            $stmt = $App->link->prepare("SELECT page FROM permissions WHERE role_id = :role_id");
            $stmt->execute([':role_id' => $role_id]);
            $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $response['status'] = 'success';
            $response['permissions'] = $permissions;
        } catch (PDOException $e) {
            $response['status'] = 'error';
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Invalid role ID';
    }
} else {
    $response['status'] = 'error';
    $response['message'] = 'Invalid request method';
}

header('Content-Type: application/json');
echo json_encode($response);
?>
