<?php
session_start();
include "db_connect.php";

if (!isset($_SESSION['userid'])) {
    http_response_code(401);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$announcement_id = $data['announcement_id'] ?? 0;

$stmt = $conn->prepare("DELETE FROM announcements WHERE announcement_id = ?");
$stmt->bind_param("i", $announcement_id);
$stmt->execute();

echo json_encode(['success' => true]);
?>
