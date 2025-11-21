<?php
session_start();
include "db_connect.php";

if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$event_id = $data['event_id'];

$stmt = $conn->prepare("DELETE FROM calendar_events WHERE event_id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();

echo json_encode(['success' => true]);
?>
