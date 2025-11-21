<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status'=>'error', 'message'=>'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$approval_id = intval($data['approval_id']);
$type = $data['type']; // 'thread' or 'community'
$status = $data['status']; // 'approved' or 'rejected'
$reviewed_by = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$review_date = date('Y-m-d H:i:s');

$table = ($type === 'thread') ? 'thread_approvals' : 'community_approvals';

$stmt = $conn->prepare("UPDATE $table SET status=?, reviewed_by=?, review_date=? WHERE approval_id=?");
$stmt->bind_param("sssi", $status, $reviewed_by, $review_date, $approval_id);

if ($stmt->execute()) {
    echo json_encode(['status'=>'success']);
} else {
    echo json_encode(['status'=>'error', 'message'=>$conn->error]);
}

$stmt->close();
$conn->close();
?>
