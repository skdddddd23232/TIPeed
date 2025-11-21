<?php
session_start();
header('Content-Type: application/json');
require 'db_connect.php'; // your mysqli connection

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in.']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$subject = trim($data['subject'] ?? '');
$category = $data['category'] ?? '';
$priority = $data['priority'] ?? '';
$description = trim($data['description'] ?? '');

if (!$subject || !$category || !$priority || !$description) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

$user_id = $_SESSION['userid'];

// Generate unique ticket ID
$ticket_id = 'TKT-' . rand(100000, 999999);

// Insert ticket into database
$stmt = $conn->prepare("INSERT INTO tickets (ticket_id, user_id, subject, category, priority, description) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sissss", $ticket_id, $user_id, $subject, $category, $priority, $description);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => "Ticket submitted successfully! Ticket ID: $ticket_id"]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit ticket. Please try again.']);
}

$stmt->close();
$conn->close();
?>
