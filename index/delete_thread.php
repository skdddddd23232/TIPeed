<?php
include 'db_connect.php';
session_start();

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Access denied'];

if (!isset($_SESSION['userid'])) {
    echo json_encode($response);
    exit;
}

$threadId = $_POST['thread_id'] ?? null;
$userId = $_SESSION['userid'];
$role = $_SESSION['role'] ?? 'student';

if (!$threadId) {
    $response['message'] = 'Missing thread ID';
    echo json_encode($response);
    exit;
}

// Fetch thread owner
$stmt = $conn->prepare("SELECT user_id FROM threads WHERE thread_id = ?");
$stmt->bind_param("i", $threadId);
$stmt->execute();
$stmt->bind_result($threadOwner);
$stmt->fetch();
$stmt->close();

if (!$threadOwner) {
    $response['message'] = 'Thread not found';
    echo json_encode($response);
    exit;
}

// Check permission
if ($role === 'admin' || $userId == $threadOwner) {
    // First, update related reports to preserve them
    $upd = $conn->prepare("UPDATE reports SET status = 'resolved', reported_content = CONCAT('[DELETED THREAD] ', IFNULL(reported_content, '')), description = CONCAT(IFNULL(description,''), '\n\n[Thread was deleted]'), thread_id = NULL WHERE thread_id = ?");
    if ($upd) {
        $upd->bind_param("i", $threadId);
        $upd->execute();
        $upd->close();
    }

    // Now delete the thread (reports no longer reference it so won't be cascaded)
    $stmt = $conn->prepare("DELETE FROM threads WHERE thread_id = ?");
    $stmt->bind_param("i", $threadId);
    $stmt->execute();
    $stmt->close();

    $response = ['status' => 'success'];
} else {
    $response['message'] = 'Access denied';
}

echo json_encode($response);
?>
