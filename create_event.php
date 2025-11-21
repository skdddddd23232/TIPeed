<?php
session_start();
include "db_connect.php"; // your mysqli connection

if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$title = isset($data['title']) ? trim($data['title']) : '';
$description = isset($data['description']) ? trim($data['description']) : '';
$date = isset($data['date']) ? $data['date'] : null;
$admin_id = $_SESSION['userid'];

if (!$title || !$date) {
    http_response_code(400);
    echo json_encode(['error' => 'Title and date are required']);
    exit;
}

// Optional: Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date format']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO calendar_events (title, description, event_date, created_by) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => $conn->error]);
    exit;
}

$stmt->bind_param("sssi", $title, $description, $date, $admin_id);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => $stmt->error]);
    exit;
}

// Return inserted event
echo json_encode([
    'event_id' => $stmt->insert_id,
    'title' => $title,
    'description' => $description,
    'date' => $date
]);
?>
