<?php
session_start();
require 'db_connect.php';

// Only allow logged-in admins
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$ticket_id = intval($data['ticket_id']);
$status = $conn->real_escape_string($data['status']);

// Update ticket status
$sql = "UPDATE tickets SET status='$status' WHERE id=$ticket_id";

if ($conn->query($sql)) {
    // optional: add system message
    $msgSql = "INSERT INTO ticket_messages (ticket_id, sender, message) 
               VALUES ($ticket_id, 'system', 'Ticket $status by admin')";
    $conn->query($msgSql);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
?>
