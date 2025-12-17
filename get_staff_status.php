<?php
require_once('Connections/paymaster.php');
header('Content-Type: application/json');
$stmt = $conn->prepare('SELECT STATUSCD, STATUS FROM staff_status ORDER BY STATUS');
$stmt->execute();
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
exit;
