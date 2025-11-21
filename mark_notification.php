<?php
session_start();
include "db_connect.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$user_id = $_SESSION['userid'] ?? null;
$notification_id = $_POST['notification_id'] ?? null;
$mark_read = $_POST['mark_read'] ?? 1;

if (!$user_id || !$notification_id) {
    echo json_encode(['error' => 'Missing data']);
    exit;
}

$sql = "UPDATE notification_user SET is_read = ? WHERE notification_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $mark_read, $notification_id, $user_id);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true]);
?>
