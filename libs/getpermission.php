<?php
require_once 'App.php';
$App = new App;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $role_id = filter_input(INPUT_POST, 'roles_id', FILTER_VALIDATE_INT);
    $pages = filter_input(INPUT_POST, 'pages', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);

    if ($role_id && $pages && count($pages) > 0) {
        try {
            // Begin transaction
            $App->link->beginTransaction();

            // Delete existing permissions for the role
            $delete_sql = "DELETE FROM permissions WHERE role_id = :role_id";
            $delete_stmt = $App->link->prepare($delete_sql);
            $delete_stmt->execute([':role_id' => $role_id]);

            // Prepare the SQL statement for inserting permissions
            $insert_sql = "INSERT INTO permissions (role_id, page) VALUES (:role_id, :page_id)";
            $insert_stmt = $App->link->prepare($insert_sql);

            // Insert each selected page for the role
            foreach ($pages as $page_id) {
                $insert_stmt->execute([':role_id' => $role_id, ':page_id' => $page_id]);
            }

            // Commit transaction
            $App->link->commit();

            $response['status'] = 'success';
            $response['message'] = 'Permissions saved successfully';
        } catch (PDOException $e) {
            // Rollback transaction on error
            $App->link->rollBack();
            $response['status'] = 'error';
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Invalid input';
    }
} else {
    $response['status'] = 'error';
    $response['message'] = 'Invalid request method';
}

header('Content-Type: application/json');
echo json_encode($response);

?>