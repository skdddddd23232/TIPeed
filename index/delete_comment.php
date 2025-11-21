<?php
include 'db_connect.php';
session_start();

header('Content-Type: application/json');

$response = ['success' => false, 'error' => 'Access denied'];

if (!isset($_SESSION['userid'])) {
    echo json_encode($response);
    exit;
}

$commentId = $_POST['comment_id'] ?? null;
$userId = $_SESSION['userid'];
$role = $_SESSION['role'] ?? 'student';

if (!$commentId) {
    $response['error'] = 'Missing comment ID';
    echo json_encode($response);
    exit;
}

// Fetch comment owner
$stmt = $conn->prepare("SELECT user_id FROM comments WHERE comment_id = ?");
$stmt->bind_param("i", $commentId);
$stmt->execute();
$stmt->bind_result($commentOwner);
$stmt->fetch();
$stmt->close();

if (!$commentOwner) {
    $response['error'] = 'Comment not found';
    echo json_encode($response);
    exit;
}

// Check permission
if ($role === 'admin' || $userId == $commentOwner) {
    // First, update related reports to preserve them
    $upd = $conn->prepare("UPDATE reports SET status = 'resolved', reported_content = CONCAT('[DELETED COMMENT] ', IFNULL(reported_content, '')), description = CONCAT(IFNULL(description,''), '\n\n[Comment was deleted]'), comment_id = NULL WHERE comment_id = ?");
    if ($upd) {
        $upd->bind_param("i", $commentId);
        $upd->execute();
        $upd->close();
    }

    // Now delete the comment (reports no longer reference it so won't be cascaded)
    $stmt = $conn->prepare("DELETE FROM comments WHERE comment_id = ?");
    $stmt->bind_param("i", $commentId);
    $stmt->execute();
    $stmt->close();

    $response = ['success' => true];
} else {
    $response['error'] = 'Access denied';
}

echo json_encode($response);
?>
