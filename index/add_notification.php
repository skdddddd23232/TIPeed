<?php
session_start();
include "db_connect.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$title    = $_POST['title'] ?? '';
$message  = $_POST['message'] ?? '';
$category = $_POST['category'] ?? 'system'; // announcement, message, thread, community, system
$targets  = $_POST['targets'] ?? []; // array of user_ids

if (!$title || !$message) {
    echo json_encode(['error' => 'Missing title or message']);
    exit;
}

// Insert into notifications table
$sql = "INSERT INTO notifications (title, message, category, created_at) VALUES (?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $title, $message, $category);
$stmt->execute();
$notification_id = $stmt->insert_id;
$stmt->close();

// Assign to users
foreach ($targets as $user_id) {
    $sql2 = "INSERT INTO notification_user (notification_id, user_id, is_read) VALUES (?, ?, 0)";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("ii", $notification_id, $user_id);
    $stmt2->execute();
    $stmt2->close();
}

echo json_encode(['success' => true, 'notification_id' => $notification_id]);
?>
