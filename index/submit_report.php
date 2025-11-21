<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

// --- Check if user is authenticated ---
if (!isset($_SESSION['userid'])) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // --- Get and validate form data ---
        $reportedUserId = isset($_POST['reported_user_id']) ? intval($_POST['reported_user_id']) : null;
        $reportedUserName = trim($_POST['reported_user_name'] ?? '');
        $locationType = trim($_POST['location_type'] ?? '');
        $locationId = isset($_POST['location_id']) ? intval($_POST['location_id']) : null;
        $reportType = trim($_POST['report_type'] ?? '');
        $priority = isset($_POST['priority']) ? intval($_POST['priority']) : 0;
        $description = trim($_POST['description'] ?? '');

        if (!$reportedUserId || !$reportedUserName || !$locationType || !$locationId || !$reportType) {
            echo json_encode(['success' => false, 'error' => 'Missing or invalid required fields']);
            exit;
        }

        if (!in_array($locationType, ['thread', 'comment'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid location type']);
            exit;
        }

        $reporterId = $_SESSION['userid'];
        $reporterName = trim($_SESSION['first_name'] . ' ' . $_SESSION['last_name']);
        
        // Get reporter's email from users table
        $emailStmt = $conn->prepare("SELECT email FROM users WHERE userid = ?");
        $emailStmt->bind_param("s", $reporterId);
        $emailStmt->execute();
        $emailResult = $emailStmt->get_result();
        $reporterEmail = $emailResult->fetch_assoc()['email'] ?? 'No email provided';
        $emailStmt->close();

        // --- Generate report ID ---
        $year = date('Y');
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM reports WHERE YEAR(created_at) = ?");
        $stmt->bind_param("i", $year);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = ($result->fetch_assoc()['count'] ?? 0) + 1;
        $reportId = sprintf("RPT-%d-%04d", $year, $count);
        $stmt->close();

        // --- Fetch reported content ---
        $reportedContent = '';
        if ($locationType === 'thread') {
            $stmt = $conn->prepare("SELECT title, content FROM threads WHERE thread_id = ?");
            $stmt->bind_param("i", $locationId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($thread = $result->fetch_assoc()) {
                $reportedContent = "Title: " . $thread['title'] . "\nContent: " . $thread['content'];
            } else {
                echo json_encode(['success' => false, 'error' => 'Thread not found']);
                exit;
            }
            $stmt->close();
        } else if ($locationType === 'comment') {
            $stmt = $conn->prepare("SELECT content FROM comments WHERE comment_id = ?");
            $stmt->bind_param("i", $locationId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($comment = $result->fetch_assoc()) {
                $reportedContent = $comment['content'];
            } else {
                echo json_encode(['success' => false, 'error' => 'Comment not found']);
                exit;
            }
            $stmt->close();
        }

        // --- Insert report ---
        $stmt = $conn->prepare("INSERT INTO reports (
            report_id, 
            reporter_id, 
            reporter_name,
            reporter_email, 
            reported_user_id, 
            reported_user_name, 
            location_type, 
            thread_id,
            comment_id, 
            report_type, 
            priority, 
            description, 
            reported_content, 
            status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");

        $threadIdParam = $locationType === 'thread' ? $locationId : null;
        $commentIdParam = $locationType === 'comment' ? $locationId : null;

        $stmt->bind_param(
            "sssssssiissss",
            $reportId,
            $reporterId,
            $reporterName,
            $reporterEmail,
            $reportedUserId,
            $reportedUserName,
            $locationType,
            $threadIdParam,
            $commentIdParam,
            $reportType,
            $priority,
            $description,
            $reportedContent
        );

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'reportId' => $reportId]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }

        $stmt->close();
        $conn->close();

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>
