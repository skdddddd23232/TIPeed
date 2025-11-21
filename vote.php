<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['userid'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Login required']);
    exit;
}

$user_id = $_SESSION['userid'];
$thread_id = intval($_POST['thread_id']);
$type = $_POST['vote']; // 'up' or 'down'

if (!in_array($type, ['up','down'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid vote type']);
    exit;
}

// Check previous vote
$check = $conn->prepare("SELECT vote FROM thread_vote WHERE user_id=? AND thread_id=?");
$check->bind_param("ii", $user_id, $thread_id);
$check->execute();
$res = $check->get_result();
$old_vote = $res->num_rows ? $res->fetch_assoc()['vote'] : null;

if ($old_vote === $type) {
    // same vote = remove vote (toggle off)
    $del = $conn->prepare("DELETE FROM thread_vote WHERE user_id=? AND thread_id=?");
    $del->bind_param("ii", $user_id, $thread_id);
    $del->execute();
    $user_vote = null;
} elseif ($old_vote && $old_vote !== $type) {
    // switch vote
    $update = $conn->prepare("UPDATE thread_vote SET vote=? WHERE user_id=? AND thread_id=?");
    $update->bind_param("sii", $type, $user_id, $thread_id);
    $update->execute();
    $user_vote = $type;
} else {
    // new vote
    $insert = $conn->prepare("INSERT INTO thread_vote (user_id, thread_id, vote) VALUES (?,?,?)");
    $insert->bind_param("iis", $user_id, $thread_id, $type);
    $insert->execute();
    $user_vote = $type;
}

// recalc total
$stmt = $conn->prepare("SELECT SUM(CASE WHEN vote='up' THEN 1 WHEN vote='down' THEN -1 ELSE 0 END) AS total FROM thread_vote WHERE thread_id=?");
$stmt->bind_param("i", $thread_id);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

echo json_encode([
    'total' => intval($total),
    'user_vote' => $user_vote
]);

?>
