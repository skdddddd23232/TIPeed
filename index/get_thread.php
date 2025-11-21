<?php
session_start();
header('Content-Type: application/json');
require 'db_connect.php';

$threadId = $_GET['thread_id'] ?? null;
$commentId = $_GET['comment_id'] ?? null;

// If we have a comment_id but no thread_id, look up the thread_id
if (!$threadId && $commentId) {
    $stmt = $conn->prepare("SELECT thread_id FROM comments WHERE comment_id = ?");
    $stmt->bind_param("i", $commentId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $threadId = $row['thread_id'];
    }
    $stmt->close();
}

if (!$threadId) {
    echo json_encode(["error" => "Missing thread_id"]);
    exit;
}

$userId = $_SESSION['userid'] ?? 0;
$userRole = $_SESSION['role'] ?? '';

try {
    // Fetch thread
    $stmt = $conn->prepare("
        SELECT 
            t.thread_id, 
            t.user_id, 
            t.title, 
            t.content AS body, 
            t.image_path AS image, 
            t.created_at AS time,
            CONCAT(u.first_name, ' ', u.last_name) AS username,
            (
                SELECT COALESCE(SUM(
                    CASE 
                        WHEN v.vote = 'up' THEN 1 
                        WHEN v.vote = 'down' THEN -1 
                        ELSE 0 END),0)
                FROM thread_vote v 
                WHERE v.thread_id = t.thread_id
            ) AS votes
        FROM threads t
        JOIN users u ON u.userid = t.user_id
        WHERE t.thread_id = ?
    ");
    $stmt->bind_param("i", $threadId);
    $stmt->execute();
    $result = $stmt->get_result();
    $thread = $result->fetch_assoc();
    $stmt->close();

    if (!$thread) {
        echo json_encode(["error" => "Thread not found"]);
        exit;
    }

    // ✅ Compute canDelete for the thread
    $thread['canDelete'] = ($thread['user_id'] == $userId) || ($userRole === 'admin');

    // Fetch comments
    $stmt = $conn->prepare("
        SELECT 
            c.comment_id, 
            c.user_id,
            c.thread_id,
            c.content, 
            c.created_at,
            CONCAT(u.first_name, ' ', u.last_name) AS username,
            (SELECT COUNT(*) FROM comment_likes cl WHERE cl.comment_id = c.comment_id) AS likes,
            c.parent_id,
            COALESCE(EXISTS(
                SELECT 1 FROM comment_likes 
                WHERE comment_id = c.comment_id AND user_id = ?
            ), 0) AS isLiked
        FROM comments c
        JOIN users u ON u.userid = c.user_id
        WHERE c.thread_id = ?
        ORDER BY 
            COALESCE(c.parent_id, c.comment_id), 
            c.created_at ASC
    ");
    $stmt->bind_param("ii", $userId, $threadId);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Build nested comments
    $comments = [];
    $lookup = [];
    foreach ($rows as $row) {
        // ✅ Compute canDelete for each comment
        $row['canDelete'] = ($row['user_id'] == $userId) || ($userRole === 'admin');

        $row['isLiked'] = false;
        $row['replies'] = [];
        $lookup[$row['comment_id']] = $row;
    }
    foreach ($lookup as $id => &$c) {
        if (!empty($c['parent_id']) && isset($lookup[$c['parent_id']])) {
            $lookup[$c['parent_id']]['replies'][] = &$c;
        } else {
            $comments[] = &$c;
        }
    }

    $thread['comments'] = $comments;

    echo json_encode(["thread" => $thread]);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
