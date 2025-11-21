<?php
session_start();
include "db_connect.php";

if (!isset($_SESSION['userid'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$title = $data['title'] ?? '';
$content = $data['content'] ?? '';
$type = $data['type'] ?? 'general';

if (!$title || !$content) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing title or content']);
    exit;
}


$stmt = $conn->prepare("INSERT INTO announcements (title, content, type, created_by) VALUES (?, ?, ?, ?)");
$stmt->bind_param("sssi", $title, $content, $type, $_SESSION['userid']);
$stmt->execute();

$stmt2 = $conn->prepare("INSERT INTO notifications (title, message, category, created_at) VALUES (?, ?, ?, NOW())");
$stmt2->bind_param("sss", $title, $content, $type);
$stmt2->execute();
$notification_id = $stmt2->insert_id;
$stmt2->close();

// 3️⃣ Assign notification to faculty & admin users
$result = $conn->query("SELECT userid FROM users WHERE role IN ('faculty','admin')");
while ($row = $result->fetch_assoc()) {
    $user_id = $row['userid'];
    $conn->query("INSERT INTO notification_user (notification_id, user_id, is_read) VALUES ($notification_id, $user_id, 0)");
}

echo json_encode([
    'announcement_id' => $stmt->insert_id,
    'title' => $title,
    'content' => $content,
    'type' => $type,
    'date' => date('Y-m-d H:i:s'),
    'author' => $_SESSION['first_name'] . ' ' . $_SESSION['last_name']
]);
?>
