<?php
include 'db_connect.php';
session_start();

header('Content-Type: application/json');

// Check if logged in and is admin
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

$reportId = $_POST['report_id'] ?? null;
$action = $_POST['action'] ?? 'resolve'; // 'resolve' or 'reopen'

if (!$reportId) {
    echo json_encode(['success' => false, 'error' => 'Report ID required']);
    exit;
}

$status = $action === 'resolve' ? 'resolved' : 'pending';

$stmt = $conn->prepare("UPDATE reports SET status = ? WHERE reportForm_id = ?");
$stmt->bind_param("si", $status, $reportId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'status' => $status]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update report: ' . $conn->error]);
}

$stmt->close();
?>