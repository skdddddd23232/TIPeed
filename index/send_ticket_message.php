<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputData = json_decode(file_get_contents('php://input'), true);
    $ticketId = $inputData['ticket_id'];
    $message = $inputData['message'];

    // Validate ticket_id and message
    if (empty($ticketId) || empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'Ticket ID or message cannot be empty']);
        exit;
    }

    // Get current user info
    $userId = $_SESSION['userid'];
    $userRole = $_SESSION['role'];
    
    // Sanitize the message to prevent SQL injection
    $message = mysqli_real_escape_string($conn, $message);
    
    // Insert message into the database
    $query = "INSERT INTO ticket_messages (ticket_id, user_id, sender, message, created_at) 
              VALUES ('$ticketId', '$userId', '$userRole', '$message', NOW())";
    
    if (mysqli_query($conn, $query)) {
        // Return the new message after saving to the DB
        $messageId = mysqli_insert_id($conn);
        
        // Get the created message with timestamp
        $messageQuery = "SELECT tm.*, u.userid as sender_id, u.role as sender_role 
                        FROM ticket_messages tm 
                        LEFT JOIN users u ON tm.user_id = u.userid 
                        WHERE tm.id = $messageId";
        $messageResult = mysqli_query($conn, $messageQuery);
        $newMessage = mysqli_fetch_assoc($messageResult);
        
        echo json_encode([
            'status' => 'success', 
            'message' => [
                'id' => $newMessage['id'],
                'message' => $newMessage['message'],
                'sender' => $newMessage['sender'],
                'sender_id' => $newMessage['sender_id'],
                'sender_role' => $newMessage['sender_role'],
                'timestamp' => $newMessage['created_at']
            ]
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to send message: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>