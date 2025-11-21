<?php
session_start();
include "db_connect.php";

$user_id = $_SESSION['userid'] ?? null;
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

// Fetch notifications assigned to this user
$sql = "SELECT n.notification_id, n.title, n.message, n.category, n.created_at, nu.is_read
        FROM notifications n
        INNER JOIN notification_user nu ON n.notification_id = nu.notification_id
        WHERE nu.user_id = ?
        ORDER BY n.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'id'       => $row['notification_id'],
        'title'    => $row['title'],
        'message'  => $row['message'],
        'category' => strtolower($row['category']), // <-- normalize here
        'time'     => $row['created_at'],
        'is_read'  => (int)$row['is_read']
    ];
}

echo json_encode($notifications);
?>
