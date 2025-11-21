<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['userid'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Login required']);
    exit;
}

$user_id = $_SESSION['userid'];
$comment_id = intval($_POST['comment_id']);

// Check existing like
$check = $conn->prepare("SELECT * FROM comment_likes WHERE comment_id=? AND user_id=?");
$check->bind_param("ii", $comment_id, $user_id);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    // Unlike
    $del = $conn->prepare("DELETE FROM comment_likes WHERE comment_id=? AND user_id=?");
    $del->bind_param("ii", $comment_id, $user_id);
    $del->execute();
    $liked = false;
} else {
    // Like
    $ins = $conn->prepare("INSERT INTO comment_likes (comment_id, user_id) VALUES (?, ?)");
    $ins->bind_param("ii", $comment_id, $user_id);
    $ins->execute();
    $liked = true;
}

// Get total likes
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM comment_likes WHERE comment_id=?");
$stmt->bind_param("i", $comment_id);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

echo json_encode([
    'liked' => $liked,
    'total' => intval($total)
]);
?>
