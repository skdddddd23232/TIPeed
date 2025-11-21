<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['userid'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$currentUserId = $_SESSION['userid'];
$currentUserRole = $_SESSION['role'];

error_log("User ID: $currentUserId, Role: $currentUserRole"); // Debug log

// Build SQL query based on user role
if ($currentUserRole === 'admin') {
    // Admin can see all tickets
    $sql = "
        SELECT t.*, u.first_name, u.last_name
        FROM tickets t
        LEFT JOIN users u ON t.user_id = u.userid   
        ORDER BY t.created_at DESC
    ";
    error_log("Admin query executed"); // Debug log
} else {
    // Regular users (student/teacher/faculty) can only see their own tickets
    $sql = "
        SELECT t.*, u.first_name, u.last_name
        FROM tickets t
        LEFT JOIN users u ON t.user_id = u.userid   
        WHERE t.user_id = '$currentUserId'
        ORDER BY t.created_at DESC
    ";
    error_log("User query executed for user ID: $currentUserId"); // Debug log
}

$result = $conn->query($sql);

if (!$result) {
    error_log("SQL Error: " . $conn->error); // Debug log
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}

$tickets = [];
$ticketCount = 0;

while ($row = $result->fetch_assoc()) {
    $ticketCount++;
    // Fetch ticket messages
    $msgRes = $conn->query("SELECT tm.*, u.userid as sender_id, u.role as sender_role 
                           FROM ticket_messages tm 
                           LEFT JOIN users u ON tm.user_id = u.userid 
                           WHERE tm.ticket_id = {$row['id']} 
                           ORDER BY tm.created_at ASC");
    $messages = [];
    while ($msg = $msgRes->fetch_assoc()) {
        $messages[] = [
            'id' => $msg['id'],
            'sender' => $msg['sender'],
            'sender_id' => $msg['sender_id'],
            'sender_role' => $msg['sender_role'],
            'message' => $msg['message'],
            'timestamp' => $msg['created_at']
        ];
    }

    $tickets[] = [
        'id' => $row['id'],
        'ticket_id' => $row['id'],
        'subject' => $row['subject'],
        'description' => $row['description'],
        'status' => $row['status'],
        'priority' => $row['priority'],
        'category' => $row['category'],
        'date' => $row['created_at'],
        'userName' => trim($row['first_name'] . ' ' . $row['last_name']),
        'user_id' => $row['user_id'],
        'messages' => $messages
    ];
}

error_log("Found $ticketCount tickets for user $currentUserId"); // Debug log
echo json_encode($tickets);
?>