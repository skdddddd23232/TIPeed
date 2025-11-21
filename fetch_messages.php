<?php
session_start();
include 'db_connect.php';

if (!isset($_GET['community_id'])) {
    echo json_encode([]);
    exit;
}

$community_id = intval($_GET['community_id']);
$last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;

$stmt = $conn->prepare("
    SELECT m.cmessages_id, m.message, m.created_at, u.first_name, u.last_name 
    FROM community_messages m
    JOIN users u ON m.user_id = u.userid
    WHERE m.community_id = ? AND m.cmessages_id > ?
    ORDER BY m.created_at ASC
");
$stmt->bind_param("ii", $community_id, $last_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

$messages = [];
while ($msg = $result->fetch_assoc()) {
    $messages[] = $msg;
}

header("Content-Type: application/json");
echo json_encode($messages);
