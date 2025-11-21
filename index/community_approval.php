<?php
session_start();
require 'db_connect.php';
require 'log_action.php';

if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$admin_id = $_SESSION['userid'];
$community_id = $_POST['community_id'] ?? null;
$action = $_POST['action'] ?? null; // approve or reject

if (!$community_id || !in_array($action, ['approve','reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

// Update community status
$status = $action === 'approve' ? 'approved' : 'rejected';
$approved_at = date('Y-m-d H:i:s');

$stmt = $conn->prepare("UPDATE communities SET approval_status = ?, approved_by = ?, approved_at = ? WHERE id = ?");
$stmt->bind_param("sisi", $status, $admin_id, $approved_at, $community_id);
if ($stmt->execute()) {
    $stmt->close();
    logAction("Community $status", "Community ID: $community_id by admin ID $admin_id");
    echo json_encode(['success' => true, 'message' => "Community $status successfully"]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
