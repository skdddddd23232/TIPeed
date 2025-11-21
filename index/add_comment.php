<?php
session_start();
include "db_connect.php";

header('Content-Type: application/json');

// Ensure user is logged in
$user_id = $_SESSION['userid'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

// Validate inputs
$thread_id = isset($_POST['thread_id']) ? (int)$_POST['thread_id'] : 0;
$content   = trim($_POST['content'] ?? '');
$parent_id = $_POST['parent_id'] ?? null;

// Normalize parent_id (empty string => NULL)
if ($parent_id === '' || $parent_id === null) {
    $parent_id = null;
} else {
    $parent_id = (int)$parent_id;
}

if ($thread_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Thread ID missing']);
    exit;
}
if ($content === '') {
    echo json_encode(['success' => false, 'error' => 'Comment content empty']);
    exit;
}

// Insert comment
$stmt = $conn->prepare("
    INSERT INTO comments (thread_id, user_id, content, parent_id, created_at)
    VALUES (?, ?, ?, ?, NOW())
");
$stmt->bind_param("iisi", $thread_id, $user_id, $content, $parent_id);
$ok = $stmt->execute();

if ($ok && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Comment added']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to insert comment']);
}

$stmt->close();
$conn->close();
