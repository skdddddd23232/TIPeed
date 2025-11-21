<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get the data from POST
$communityId = isset($_POST['communityId']) ? intval($_POST['communityId']) : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : '';

// Validate input
if (!$communityId || empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Missing community ID or action']);
    exit;
}

// Determine the new status based on action
if ($action === 'approve') {
    $newStatus = 'approved';
} elseif ($action === 'reject') {
    $newStatus = 'rejected';
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action. Use "approve" or "reject"']);
    exit;
}

// Update the community status using your actual table structure
$stmt = $conn->prepare("UPDATE communities SET status = ?, approved_by = ?, approved_at = NOW() WHERE communities_id = ?");
$stmt->bind_param("sii", $newStatus, $_SESSION['userid'], $communityId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'status' => $newStatus]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update community: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>