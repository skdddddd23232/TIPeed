<?php
session_start();
include "db_connect.php";

$stmt = $conn->prepare("
    SELECT a.announcement_id, a.title, a.content, a.type, a.date, u.first_name, u.last_name
    FROM announcements a
    JOIN users u ON a.created_by = u.userid
    ORDER BY a.date DESC
");
$stmt->execute();
$result = $stmt->get_result();

$announcements = [];
while ($row = $result->fetch_assoc()) {
    $announcements[] = [
        'announcement_id' => $row['announcement_id'],
        'title' => $row['title'],
        'content' => $row['content'],
        'type' => $row['type'],
        'date' => $row['date'],
        'author' => $row['first_name'] . ' ' . $row['last_name']
    ];
}

echo json_encode($announcements);
?>
