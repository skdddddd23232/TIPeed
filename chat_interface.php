<?php
session_start();
include "db_connect.php";

if (!isset($_SESSION['userid'])) {
    header("Location: auth.php");
    exit;
}

$chatId = $_GET['chat_id'] ?? 0;
$userId = $_SESSION['userid'];
$userName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$currentUserRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// Set home page based on role
if ($currentUserRole === 'admin') {
    $homePage = 'admin_home.php';
} else if ($currentUserRole === 'faculty') {
    $homePage = 'faculty_home.php';
} else {
    $homePage = 'student_home.php';
}

$studentName   = $_SESSION['first_name'] . " " . $_SESSION['last_name'];
$yearLevel = isset($_SESSION['year_level']) ? $_SESSION['year_level'] : null;
$role      = isset($_SESSION['role']) ? $_SESSION['role'] : "student";
function ordinal($number) {
    $ends = ['th','st','nd','rd','th','th','th','th','th','th'];
    if (($number % 100) >= 11 && ($number % 100) <= 13) {
        return $number . 'th';
    }
    return $number . $ends[$number % 10];
}
if ($role === 'student') {
    if ($yearLevel && is_numeric($yearLevel)) {
        $studentIDT = ordinal($yearLevel) . " Year"; // 1 -> 1st Year
    } else {
        $studentIDT = "No year assigned";
    }
} elseif ($role === 'faculty') {
    $studentIDT = "Faculty";
} elseif ($role === 'admin') {
    $studentIDT = "Administrator";
} else {
  $studentIDT = ucfirst(htmlspecialchars($role));
}

// Handle join by link functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_by_link'])) {
    $inviteCode = trim($_POST['invite_code']);
    
    if (!empty($inviteCode)) {
        // Find the chat with this invite code
        $inviteSql = "SELECT chat_id, group_name, course_id, faculty_id 
                      FROM course_chats 
                      WHERE invite_code = ?";
        $inviteStmt = $conn->prepare($inviteSql);
        $inviteStmt->bind_param("s", $inviteCode);
        $inviteStmt->execute();
        $inviteResult = $inviteStmt->get_result();
        $chatData = $inviteResult->fetch_assoc();
        
        if ($chatData) {
            $targetChatId = $chatData['chat_id'];
            
            // Check if user is already a member
            $checkMemberSql = "SELECT * FROM course_chat_members WHERE chat_id = ? AND user_id = ?";
            $checkStmt = $conn->prepare($checkMemberSql);
            $checkStmt->bind_param("ii", $targetChatId, $userId);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows === 0) {
                // Add user as a member with 'student' role
                $joinSql = "INSERT INTO course_chat_members (chat_id, user_id, role, joined_at) VALUES (?, ?, 'student', NOW())";
                $joinStmt = $conn->prepare($joinSql);
                $joinStmt->bind_param("ii", $targetChatId, $userId);
                
                if ($joinStmt->execute()) {
                    // Redirect to the joined chat
                    header("Location: chat_interface.php?chat_id=" . $targetChatId . "&joined=1");
                    exit;
                } else {
                    $joinError = "Failed to join the chat. Please try again.";
                }
            } else {
                // User is already a member, redirect to that chat
                header("Location: chat_interface.php?chat_id=" . $targetChatId);
                exit;
            }
        } else {
            $joinError = "Invalid invite code. Please check the code and try again.";
        }
    } else {
        $joinError = "Please enter an invite code.";
    }
}

// Get all course chats available for this user based on role
if ($currentUserRole === 'admin') {
    // Admin can see ALL course chats
    $userChatsSql = "SELECT cc.chat_id, cc.group_name, c.course_name, c.course_code,
                            cc.faculty_id, u.first_name as faculty_first, u.last_name as faculty_last
                     FROM course_chats cc 
                     JOIN courses c ON cc.course_id = c.course_id 
                     JOIN users u ON cc.faculty_id = u.userid
                     ORDER BY cc.group_name";
    $userChatsStmt = $conn->prepare($userChatsSql);
} else if ($currentUserRole === 'faculty') {
    // Faculty can see chats they created OR are members of
    $userChatsSql = "SELECT cc.chat_id, cc.group_name, c.course_name, c.course_code,
                            cc.faculty_id, u.first_name as faculty_first, u.last_name as faculty_last
                     FROM course_chats cc 
                     JOIN courses c ON cc.course_id = c.course_id 
                     JOIN users u ON cc.faculty_id = u.userid
                     WHERE cc.faculty_id = ? 
                     UNION
                     SELECT cc.chat_id, cc.group_name, c.course_name, c.course_code,
                            cc.faculty_id, u.first_name as faculty_first, u.last_name as faculty_last
                     FROM course_chat_members cm 
                     JOIN course_chats cc ON cm.chat_id = cc.chat_id 
                     JOIN courses c ON cc.course_id = c.course_id 
                     JOIN users u ON cc.faculty_id = u.userid
                     WHERE cm.user_id = ? 
                     ORDER BY group_name";
    $userChatsStmt = $conn->prepare($userChatsSql);
    $userChatsStmt->bind_param("ii", $userId, $userId);
} else {
    // Students can only see chats they are members of
    $userChatsSql = "SELECT cc.chat_id, cc.group_name, c.course_name, c.course_code,
                            cc.faculty_id, u.first_name as faculty_first, u.last_name as faculty_last
                     FROM course_chat_members cm 
                     JOIN course_chats cc ON cm.chat_id = cc.chat_id 
                     JOIN courses c ON cc.course_id = c.course_id 
                     JOIN users u ON cc.faculty_id = u.userid
                     WHERE cm.user_id = ? 
                     ORDER BY cc.group_name";
    $userChatsStmt = $conn->prepare($userChatsSql);
    $userChatsStmt->bind_param("i", $userId);
}

$userChatsStmt->execute();
$userChatsResult = $userChatsStmt->get_result();
$userChats = $userChatsResult->fetch_all(MYSQLI_ASSOC);

// If no chat_id specified and user has chats, redirect to first one
if (!$chatId && !empty($userChats)) {
    header("Location: chat_interface.php?chat_id=" . $userChats[0]['chat_id']);
    exit;
}

// Verify user has access to this chat
if ($chatId) {
    if ($currentUserRole === 'admin') {
        // Admin can access any chat
        $accessSql = "SELECT cm.role, cc.group_name, c.course_name, c.course_code,
                             cc.chat_id, cc.description, cc.created_at, cc.faculty_id,
                             u.first_name as faculty_first, u.last_name as faculty_last
                      FROM course_chats cc 
                      JOIN courses c ON cc.course_id = c.course_id 
                      JOIN users u ON cc.faculty_id = u.userid
                      LEFT JOIN course_chat_members cm ON cc.chat_id = cm.chat_id AND cm.user_id = ?
                      WHERE cc.chat_id = ?";
        $accessStmt = $conn->prepare($accessSql);
        $accessStmt->bind_param("ii", $userId, $chatId);
    } else if ($currentUserRole === 'faculty') {
        // Faculty can access chats they created OR are members of
        $accessSql = "SELECT cm.role, cc.group_name, c.course_name, c.course_code,
                             cc.chat_id, cc.description, cc.created_at, cc.faculty_id,
                             u.first_name as faculty_first, u.last_name as faculty_last
                      FROM course_chats cc 
                      JOIN courses c ON cc.course_id = c.course_id 
                      JOIN users u ON cc.faculty_id = u.userid
                      LEFT JOIN course_chat_members cm ON cc.chat_id = cm.chat_id AND cm.user_id = ?
                      WHERE cc.chat_id = ? AND (cc.faculty_id = ? OR cm.user_id = ?)";
        $accessStmt = $conn->prepare($accessSql);
        $accessStmt->bind_param("iiii", $userId, $chatId, $userId, $userId);
    } else {
        // Students must be members
        $accessSql = "SELECT cm.role, cc.group_name, c.course_name, c.course_code,
                             cc.chat_id, cc.description, cc.created_at, cc.faculty_id,
                             u.first_name as faculty_first, u.last_name as faculty_last
                      FROM course_chat_members cm 
                      JOIN course_chats cc ON cm.chat_id = cc.chat_id 
                      JOIN courses c ON cc.course_id = c.course_id 
                      JOIN users u ON cc.faculty_id = u.userid
                      WHERE cm.chat_id = ? AND cm.user_id = ?";
        $accessStmt = $conn->prepare($accessSql);
        $accessStmt->bind_param("ii", $chatId, $userId);
    }
    
    $accessStmt->execute();
    $accessResult = $accessStmt->get_result();
    $access = $accessResult->fetch_assoc();

    if (!$access) {
        // If user doesn't have access to specified chat, redirect to available chats
        if (!empty($userChats)) {
            header("Location: chat_interface.php?chat_id=" . $userChats[0]['chat_id']);
        } else {
            header("Location: " . $homePage);
        }
        exit;
    }
}

// Get total member count for this chat
$memberCountSql = "SELECT COUNT(*) as total_members 
                   FROM course_chat_members 
                   WHERE chat_id = ?";
$memberCountStmt = $conn->prepare($memberCountSql);
$memberCountStmt->bind_param("i", $chatId);
$memberCountStmt->execute();
$memberCountResult = $memberCountStmt->get_result();
$memberCount = $memberCountResult->fetch_assoc()['total_members'];

// Get last activity time
$lastActivitySql = "SELECT MAX(created_at) as last_activity 
                    FROM chat_messages 
                    WHERE chat_id = ?";
$lastActivityStmt = $conn->prepare($lastActivitySql);
$lastActivityStmt->bind_param("i", $chatId);
$lastActivityStmt->execute();
$lastActivityResult = $lastActivityStmt->get_result();
$lastActivity = $lastActivityResult->fetch_assoc()['last_activity'];

// Get chat messages
$messagesSql = "SELECT m.*, u.first_name, u.last_name, u.userid
                FROM chat_messages m 
                JOIN users u ON m.user_id = u.userid 
                WHERE m.chat_id = ? 
                ORDER BY m.created_at ASC";
$messagesStmt = $conn->prepare($messagesSql);
$messagesStmt->bind_param("i", $chatId);
$messagesStmt->execute();
$messagesResult = $messagesStmt->get_result();
$messages = $messagesResult->fetch_all(MYSQLI_ASSOC);

// Get last message ID for real-time updates
$lastMessageId = 0;
if (!empty($messages)) {
    $lastMessage = end($messages);
    $lastMessageId = $lastMessage['message_id'];
}

// Handle AJAX message send
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_message'])) {
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $filePath = null;
    
    // Handle file upload
    if (isset($_FILES['file_attachment']) && $_FILES['file_attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/chat_files/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = time() . '_' . basename($_FILES['file_attachment']['name']);
        $filePath = $uploadDir . $fileName;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['file_attachment']['tmp_name'], $filePath)) {
            // File uploaded successfully
        } else {
            echo "file_upload_error";
            exit;
        }
    }
    
    if (!empty(trim($message)) || $filePath) {
        $insertSql = "INSERT INTO chat_messages (chat_id, user_id, message, file_path) VALUES (?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("iiss", $chatId, $userId, $message, $filePath);
        if ($insertStmt->execute()) {
            echo "success";
        } else {
            echo "error";
        }
    } else {
        echo "empty";
    }
    exit;
}

// Handle AJAX message fetch
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax_get_messages']) && isset($_GET['last_id'])) {
    $lastId = $_GET['last_id'];
    $newMessagesSql = "SELECT m.*, u.first_name, u.last_name, u.userid
                      FROM chat_messages m 
                      JOIN users u ON m.user_id = u.userid 
                      WHERE m.chat_id = ? AND m.message_id > ?
                      ORDER BY m.created_at ASC";
    $newMessagesStmt = $conn->prepare($newMessagesSql);
    $newMessagesStmt->bind_param("ii", $chatId, $lastId);
    $newMessagesStmt->execute();
    $newMessagesResult = $newMessagesStmt->get_result();
    $newMessages = $newMessagesResult->fetch_all(MYSQLI_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($newMessages);
    exit;
}

// Handle regular form submission (fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $filePath = null;
    
    // Handle file upload
    if (isset($_FILES['file_attachment']) && $_FILES['file_attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/chat_files/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = time() . '_' . basename($_FILES['file_attachment']['name']);
        $filePath = $uploadDir . $fileName;
        
        // Move uploaded file
        move_uploaded_file($_FILES['file_attachment']['tmp_name'], $filePath);
    }
    
    if (!empty(trim($message)) || $filePath) {
        $insertSql = "INSERT INTO chat_messages (chat_id, user_id, message, file_path) VALUES (?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("iiss", $chatId, $userId, $message, $filePath);
        $insertStmt->execute();
    }
    
    header("Location: chat_interface.php?chat_id=" . $chatId);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - <?php echo isset($access) ? htmlspecialchars($access['group_name']) : 'TiPeed'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="../css/NS.css">
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            text-decoration: none;
        }

        body { 
            background: #f9fafb; 
            color: #222; 
            text-decoration: none;
            height: 100vh;
            overflow: hidden;
        }

    
        /* Chat Layout */
        .chat-container {
            flex: 1;
            display: flex;
            background: #fff;
        }

        /* Chat Sidebar - Course Chats List */
        .chat-sidebar {
            width: 300px;
            background: white;
            border-right: 1px solid #e9ecef;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            background: white;
        }

        .chat-title {
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .chat-course {
            color: #f5b301;
            font-weight: 600;
            font-size: 14px;
        }

        .available-chats {
            padding: 20px;
            flex: 1;
            overflow-y: auto;
        }

        .section-title {
            font-size: 14px;
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .chat-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .chat-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 8px;
            transition: all 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .chat-item:hover {
            background: #f8f9fa;
        }

        .chat-item.active {
            background: #f5f5f5;
            border-color: #f5b301;
        }

        .chat-avatar {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: linear-gradient(135deg, #f5b301, #e0a500);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
            flex-shrink: 0;
        }

        .chat-info {
            flex: 1;
            min-width: 0;
        }

        .chat-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .chat-details {
            font-size: 12px;
            color: #6c757d;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .faculty-name {
            font-size: 11px;
            color: #999;
            font-style: italic;
        }

        .admin-badge {
            background: #dc3545;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 5px;
        }

        /* Join Chat Section */
        .join-chat-section {
            padding: 20px;
            border-top: 1px solid #e9ecef;
            background: #f8f9fa;
        }

        .join-chat-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .join-chat-input {
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .join-chat-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .join-chat-btn:hover {
            background: #218838;
        }

        .join-error {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            padding: 8px;
            background: #f8d7da;
            border-radius: 4px;
            border: 1px solid #f5c6cb;
        }

        .join-success {
            color: #155724;
            font-size: 12px;
            margin-top: 5px;
            padding: 8px;
            background: #d4edda;
            border-radius: 4px;
            border: 1px solid #c3e6cb;
        }

        /* Main Chat Area */
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .messages-header {
            padding: 15px 25px;
            background: white;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-info-main h2 {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 4px;
        }

        .chat-meta {
            font-size: 14px;
            color: #6c757d;
        }

        .chat-actions {
            display: flex;
            gap: 10px;
        }

        .action-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            color: #6c757d;
        }

        .action-icon:hover {
            background: #e9ecef;
            color: #f5b301;
        }

        /* Messages Area */
        .messages-area {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .message {
            display: flex;
            gap: 12px;
            max-width: 70%;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message.own {
            align-self: flex-end;
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f5b301, #e0a500);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
            flex-shrink: 0;
        }

        .message-content {
            background: white;
            padding: 12px 16px;
            border-radius: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: relative;
            max-width: 100%;
        }

        .message.own .message-content {
            background: linear-gradient(135deg, #f5b301, #e0a500);
            color: white;
        }

        .message-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
        }

        .message-sender {
            font-weight: 600;
            font-size: 14px;
            color: #f5b301;
        }

        .message.own .message-sender {
            color: rgba(255,255,255,0.9);
        }

        .message-time {
            font-size: 12px;
            color: #6c757d;
        }

        .message.own .message-time {
            color: rgba(255,255,255,0.7);
        }

        .message-text {
            line-height: 1.4;
            word-wrap: break-word;
        }

        /* Link Styles */
        .message-text a {
            color: #007bff;
            text-decoration: none;
            word-break: break-all;
        }

        .message-text a:hover {
            text-decoration: underline;
        }

        .message.own .message-text a {
            color: rgba(255,255,255,0.9);
            text-decoration: underline;
        }

        .message.own .message-text a:hover {
            color: white;
        }

        /* Link Preview Styles */
        .link-preview {
            margin-top: 8px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            overflow: hidden;
            max-width: 400px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .message.own .link-preview {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.2);
        }

        .link-preview-image {
            width: 100%;
            height: 200px;
            background: #f8f9fa;
            overflow: hidden;
        }

        .link-preview-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .link-preview-content {
            padding: 12px;
        }

        .link-preview-title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
            color: #2c3e50;
        }

        .message.own .link-preview-title {
            color: white;
        }

        .link-preview-description {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .message.own .link-preview-description {
            color: rgba(255,255,255,0.8);
        }

        .link-preview-url {
            font-size: 11px;
            color: #6c757d;
            word-break: break-all;
        }

        .message.own .link-preview-url {
            color: rgba(255,255,255,0.7);
        }

        /* File Attachment Styles */
        .file-attachment {
            margin-top: 8px;
            padding: 8px 12px;
            background: rgba(0,0,0,0.05);
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            max-width: 300px;
        }

        .message.own .file-attachment {
            background: rgba(255,255,255,0.2);
        }

        .file-icon {
            font-size: 18px;
            color: #6c757d;
            flex-shrink: 0;
        }

        .message.own .file-icon {
            color: rgba(255,255,255,0.8);
        }

        .file-info {
            flex: 1;
            min-width: 0;
        }

        .file-name {
            font-weight: 500;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .file-size {
            font-size: 12px;
            color: #6c757d;
        }

        .message.own .file-size {
            color: rgba(255,255,255,0.7);
        }

        .file-download {
            color: #f5b301;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.2s;
        }

        .message.own .file-download {
            color: rgba(255,255,255,0.9);
        }

        .file-download:hover {
            color: #e0a500;
        }

        .message.own .file-download:hover {
            color: white;
        }

        /* Image Attachment Styles */
        .image-attachment {
            margin-top: 8px;
            max-width: 300px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .image-attachment img {
            width: 100%;
            height: auto;
            display: block;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .image-attachment img:hover {
            transform: scale(1.02);
        }

        /* Image Modal */
        .image-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .image-modal.active {
            display: flex;
        }

        .modal-content {
            max-width: 90%;
            max-height: 90%;
        }

        .modal-content img {
            max-width: 100%;
            max-height: 100%;
            border-radius: 8px;
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 30px;
            cursor: pointer;
            background: none;
            border: none;
        }

        /* Message Input */
        .message-input-container {
            padding: 20px;
            background: white;
            border-top: 1px solid #e9ecef;
        }

        .input-group {
            display: flex;
            gap: 12px;
            align-items: end;
        }

        .message-input-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .message-input {
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            font-size: 14px;
            resize: none;
            max-height: 120px;
            outline: none;
            transition: all 0.3s;
            width: 100%;
        }

        .message-input:focus {
            border-color: #f5b301;
            box-shadow: 0 0 0 3px rgba(245, 179, 1, 0.1);
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
        }

        .file-input {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-btn {
            background: #f8f9fa;
            color: #6c757d;
            border: 2px solid #e9ecef;
            width: 46px;
            height: 46px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .file-input-btn:hover {
            background: #e9ecef;
            color: #f5b301;
            border-color: #f5b301;
        }

        .attachment-preview {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .attachment-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            background: #f8f9fa;
            border-radius: 16px;
            font-size: 12px;
            max-width: 200px;
        }

        .attachment-name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
        }

        .attachment-remove {
            color: #dc3545;
            cursor: pointer;
            font-size: 14px;
            background: none;
            border: none;
        }

        .send-btn {
            background: linear-gradient(135deg, #f5b301, #e0a500);
            color: white;
            border: none;
            width: 46px;
            height: 46px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(245, 179, 1, 0.3);
        }

        .send-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 179, 1, 0.4);
        }

        .send-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .chat-sidebar {
                display: none;
            }
            
            .message {
                max-width: 85%;
            }
            
            .navbar {
                padding: 12px 20px;
            }
            
            .link-preview {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="logo">TIPeed</div>
        <div class="nav-links">
        <a href="<?= $homePage ?>">Home</a>
        <?php if ($currentUserRole === 'admin' || $currentUserRole === 'faculty'): ?>
        <a href="student_home.php">Thread</a>
        <a href="faculty_chats.php">Faculty</a>
        <?php endif; ?>
        <a href="Community.php">Community</a>
        <a href="aboutus.php">About Us</a>
        </div>
        <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search Topics">
        </div>
    </div>

    <!-- Layout -->
    <div class="layout">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="profile-section" id="toggleSidebar">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($studentName, 0, 2)); ?>
                </div>
                <div class="profile-info">
                <div class="profile-name"><?php echo $studentName; ?></div>
                <div class="profile-course"><?php echo $studentIDT; ?></div>
                </div>
            </div>

                <div class="menu-section">
                    <a href="profile.php"class="menu-item active"><div class="menu-icon"><i class="fas fa-user"></i></div><div class="menu-text">Profile</div></a>
                    <a href="<?= $homePage ?>"  class="menu-item"><div class="menu-icon"><i class="fas fa-home"></i></div><div class="menu-text">Home</div></a>
                    <a href="chat_interface.php" class="menu-item"><div class="menu-icon"><i class="fas fa-comment-dots"></i></div><div class="menu-text">Course Chat</div></a>
                    <a href="CourseChat.php" class="menu-item"><div class="menu-icon"><i class="fas fa-comments"></i></div><div class="menu-text">Communities Chat</div></a>
                    <a href="Community.php" class="menu-item"><div class="menu-icon"><i class="fas fa-users"></i></div><div class="menu-text">Community</div></a>
                    <?php if ($currentUserRole === 'admin'): ?>
                    <a href="admin_reg.php" class="menu-item"><div class="menu-icon"><i class="fas fa-user-plus"></i></div><div class="menu-text">Register</div></a>
                    <?php endif; ?>
                    <a href="calendar.php" class="menu-item"><div class="menu-icon"><i class="fas fa-calendar-alt"></i></div><div class="menu-text">Calendar</div></a>
                    <div class="menu-item"><div class="menu-icon"><i class="fas fa-cog"></i></div><div class="menu-text">Settings</div></div>
                    <a href="Help.php" class="menu-item"><div class="menu-icon"><i class="fas fa-question-circle"></i></div><div class="menu-text">Help</div></a>
                    <a href="logout.php" class="menu-item"><div class="menu-icon"><i class="fas fa-sign-out-alt"></i></div><div class="menu-text">Log Out</div></a>
                </div>
        </div>

        <!-- Chat Container -->
        <div class="chat-container">
            <!-- Sidebar - Course Chats List -->
            <div class="chat-sidebar">
                <div class="chat-header">
                    <div class="chat-title">
                        <?php 
                        if ($currentUserRole === 'admin') {
                            echo 'All Course Chats';
                        } else if ($currentUserRole === 'faculty') {
                            echo 'My Course Chats';
                        } else {
                            echo 'My Joined Chats';
                        }
                        ?>
                        <?php if ($currentUserRole === 'admin'): ?>
                            <span class="admin-badge">ADMIN</span>
                        <?php endif; ?>
                    </div>
                    <div class="chat-course">
                        <?php 
                        if ($currentUserRole === 'admin') {
                            echo 'All Available Groups';
                        } else if ($currentUserRole === 'faculty') {
                            echo 'Created & Joined Groups';
                        } else {
                            echo 'Joined Groups';
                        }
                        ?>
                    </div>
                </div>

                <div class="available-chats">
                    <div class="section-title">
                        <?php 
                        if ($currentUserRole === 'admin') {
                            echo 'All Chats';
                        } else if ($currentUserRole === 'faculty') {
                            echo 'Your Chats';
                        } else {
                            echo 'Your Joined Chats';
                        }
                        ?> 
                        (<?php echo count($userChats); ?>)
                    </div>
                    <div class="chat-list">
                        <?php if (!empty($userChats)): ?>
                            <?php foreach ($userChats as $chat): ?>
                                <a href="chat_interface.php?chat_id=<?php echo $chat['chat_id']; ?>" 
                                   class="chat-item <?php echo $chat['chat_id'] == $chatId ? 'active' : ''; ?>">
                                    <div class="chat-avatar">
                                        <?php echo strtoupper(substr($chat['group_name'], 0, 2)); ?>
                                    </div>
                                    <div class="chat-info">
                                        <div class="chat-name"><?php echo htmlspecialchars($chat['group_name']); ?></div>
                                        <div class="chat-details"><?php echo htmlspecialchars($chat['course_code'] . ' - ' . $chat['course_name']); ?></div>
                                        <?php if ($currentUserRole === 'admin' || $currentUserRole === 'student'): ?>
                                            <div class="faculty-name">By: <?php echo htmlspecialchars($chat['faculty_first'] . ' ' . $chat['faculty_last']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-comments"></i>
                                <h3>No Course Chats</h3>
                                <p>
                                    <?php if ($currentUserRole === 'admin'): ?>
                                        No course chats have been created yet.
                                    <?php else: ?>
                                        You are not a member of any course chats yet.
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Join Chat by Link Section - Only show for students -->
                <?php if ($currentUserRole === 'student'): ?>
                <div class="join-chat-section">
                    <h4 style="margin-bottom: 15px; font-size: 16px; color: #333;">
                        <i class="fas fa-key"></i> Join New Chat
                    </h4>
                    <form method="POST" class="join-chat-form" id="joinChatForm">
                        <input type="text" 
                               name="invite_code" 
                               class="join-chat-input" 
                               placeholder="Enter invite code" 
                               required
                               value="<?php echo isset($_POST['invite_code']) ? htmlspecialchars($_POST['invite_code']) : ''; ?>">
                        <button type="submit" name="join_by_link" class="join-chat-btn">
                            <i class="fas fa-sign-in-alt"></i> Join Chat
                        </button>
                        <?php if (isset($joinError)): ?>
                            <div class="join-error">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $joinError; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($_GET['joined'])): ?>
                            <div class="join-success">
                                <i class="fas fa-check-circle"></i> Successfully joined the chat!
                            </div>
                        <?php endif; ?>
                    </form>
                    <p style="font-size: 12px; color: #666; margin-top: 10px;">
                        Get an invite code from your instructor to join a course chat.
                    </p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Main Chat Area -->
            <?php if ($chatId && isset($access)): ?>
            <div class="chat-main">
                <div class="messages-header">
                    <div class="chat-info-main">
                        <h2><?php echo htmlspecialchars($access['group_name']); ?></h2>
                        <div class="chat-meta">
                            <span id="memberCount"><?php echo $memberCount; ?></span> members • 
                            Last activity: <span id="lastActivity"><?php echo $lastActivity ? date('M j, g:i A', strtotime($lastActivity)) : 'No activity yet'; ?></span>
                            <?php if ($currentUserRole === 'admin' || $currentUserRole === 'student'): ?>
                                • Created by: <?php echo htmlspecialchars($access['faculty_first'] . ' ' . $access['faculty_last']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="chat-actions">
                        <div class="action-icon" title="Search messages">
                            <i class="fas fa-search"></i>
                        </div>
                        <div class="action-icon" title="Chat info">
                            <i class="fas fa-info-circle"></i>
                        </div>
                    </div>
                </div>

                <div class="messages-area" id="messagesArea">
                    <?php if (!empty($messages)): ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="message <?php echo $message['userid'] == $userId ? 'own' : ''; ?>" data-message-id="<?php echo $message['message_id']; ?>">
                                <div class="message-avatar">
                                    <?php echo strtoupper(substr($message['first_name'], 0, 1) . substr($message['last_name'], 0, 1)); ?>
                                </div>
                                <div class="message-content">
                                    <div class="message-header">
                                        <span class="message-sender">
                                            <?php echo $message['userid'] == $userId ? 'You' : htmlspecialchars($message['first_name'] . ' ' . $message['last_name']); ?>
                                        </span>
                                        <span class="message-time">
                                            <?php echo date('M j, g:i A', strtotime($message['created_at'])); ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($message['message'])): ?>
                                        <div class="message-text">
                                            <?php echo autoLinkUrls($message['message']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($message['file_path'])): ?>
                                        <?php
                                        $fileExtension = strtolower(pathinfo($message['file_path'], PATHINFO_EXTENSION));
                                        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
                                        $isImage = in_array($fileExtension, $imageExtensions);
                                        $fileName = basename($message['file_path']);
                                        $fileSize = file_exists($message['file_path']) ? filesize($message['file_path']) : 0;
                                        $formattedSize = formatFileSize($fileSize);
                                        ?>
                                        <?php if ($isImage): ?>
                                            <div class="image-attachment">
                                                <img src="<?php echo $message['file_path']; ?>" 
                                                     alt="Image attachment" 
                                                     onclick="openImageModal('<?php echo $message['file_path']; ?>')">
                                            </div>
                                        <?php else: ?>
                                            <div class="file-attachment">
                                                <div class="file-icon">
                                                    <i class="fas fa-file"></i>
                                                </div>
                                                <div class="file-info">
                                                    <div class="file-name"><?php echo htmlspecialchars($fileName); ?></div>
                                                    <div class="file-size"><?php echo $formattedSize; ?></div>
                                                </div>
                                                <a href="<?php echo $message['file_path']; ?>" 
                                                   class="file-download" 
                                                   download="<?php echo htmlspecialchars($fileName); ?>">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-comments"></i>
                            <h3>No Messages Yet</h3>
                            <p>Start the conversation by sending the first message!</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Message Input -->
                <div class="message-input-container">
                    <div class="input-group">
                        <div class="message-input-wrapper">
                            <textarea class="message-input" id="messageInput" name="message" placeholder="Type your message..." rows="1"></textarea>
                            <div class="attachment-preview" id="attachmentPreview"></div>
                        </div>
                        <div class="file-input-wrapper">
                            <input type="file" id="fileInput" class="file-input" accept="image/*,.pdf,.doc,.docx,.txt,.zip,.rar,.xls,.xlsx,.ppt,.pptx,.js,.html,.css,.php,.py,.java,.cpp,.c,.cs">
                            <div class="file-input-btn" title="Attach file">
                                <i class="fas fa-paperclip"></i>
                            </div>
                        </div>
                        <button type="button" class="send-btn" id="sendBtn">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php else: ?>
                <div class="empty-state" style="flex: 1; display: flex; flex-direction: column; justify-content: center;">
                    <i class="fas fa-comments fa-3x"></i>
                    <h3>Select a Course Chat</h3>
                    <p>Choose a course chat from the sidebar to start messaging</p>
                    <?php if ($currentUserRole === 'student' && empty($userChats)): ?>
                        <p style="margin-top: 10px; font-size: 14px;">
                            Don't have any chats? Ask your instructor for an invite code to join a course chat.
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="image-modal" id="imageModal">
        <button class="modal-close" onclick="closeImageModal()">&times;</button>
        <div class="modal-content">
            <img id="modalImage" src="" alt="Full size image">
        </div>
    </div>

    <script>
        const messagesArea = document.getElementById('messagesArea');
        const messageInput = document.getElementById('messageInput');
        const fileInput = document.getElementById('fileInput');
        const attachmentPreview = document.getElementById('attachmentPreview');
        const sendBtn = document.getElementById('sendBtn');
        const joinChatForm = document.getElementById('joinChatForm');
        const imageModal = document.getElementById('imageModal');
        const modalImage = document.getElementById('modalImage');
        let lastMessageId = <?php echo $lastMessageId; ?>;
        let isSending = false;
        let refreshInterval;
        let currentAttachment = null;

        // Sidebar toggle functionality
        const sidebar = document.getElementById('sidebar');
        const toggleSidebar = document.getElementById('toggleSidebar');
        
        if (toggleSidebar && sidebar) {
            toggleSidebar.addEventListener('click', () => sidebar.classList.toggle('expanded'));
        }

        // Auto-resize textarea
        if (messageInput) {
            messageInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });

            // Handle Enter key for message input
            messageInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
        }

        // File input handling
        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                if (this.files.length > 0) {
                    const file = this.files[0];
                    currentAttachment = file;
                    showAttachmentPreview(file);
                }
            });
        }

        // Show attachment preview
        function showAttachmentPreview(file) {
            attachmentPreview.innerHTML = '';
            
            const attachmentItem = document.createElement('div');
            attachmentItem.className = 'attachment-item';
            
            const fileIcon = document.createElement('i');
            fileIcon.className = getFileIconClass(file.name);
            
            const fileName = document.createElement('span');
            fileName.className = 'attachment-name';
            fileName.textContent = file.name;
            
            const removeBtn = document.createElement('button');
            removeBtn.className = 'attachment-remove';
            removeBtn.innerHTML = '<i class="fas fa-times"></i>';
            removeBtn.onclick = function() {
                currentAttachment = null;
                attachmentPreview.innerHTML = '';
                fileInput.value = '';
            };
            
            attachmentItem.appendChild(fileIcon);
            attachmentItem.appendChild(fileName);
            attachmentItem.appendChild(removeBtn);
            attachmentPreview.appendChild(attachmentItem);
        }

        // Get appropriate file icon based on file extension
        function getFileIconClass(fileName) {
            const ext = fileName.split('.').pop().toLowerCase();
            const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
            const codeExts = ['js', 'html', 'css', 'php', 'py', 'java', 'cpp', 'c', 'cs'];
            const docExts = ['doc', 'docx'];
            const sheetExts = ['xls', 'xlsx'];
            const pptExts = ['ppt', 'pptx'];
            
            if (imageExts.includes(ext)) return 'fas fa-file-image';
            if (codeExts.includes(ext)) return 'fas fa-file-code';
            if (docExts.includes(ext)) return 'fas fa-file-word';
            if (sheetExts.includes(ext)) return 'fas fa-file-excel';
            if (pptExts.includes(ext)) return 'fas fa-file-powerpoint';
            if (ext === 'pdf') return 'fas fa-file-pdf';
            if (ext === 'txt') return 'fas fa-file-alt';
            if (ext === 'zip' || ext === 'rar') return 'fas fa-file-archive';
            
            return 'fas fa-file';
        }

        // Send message function
        function sendMessage() {
            if (isSending) return;
            
            const message = messageInput.value.trim();
            
            if (message || currentAttachment) {
                isSending = true;
                sendBtn.disabled = true;
                sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                
                // Create FormData for AJAX request
                const formData = new FormData();
                formData.append('ajax_message', 'true');
                formData.append('message', message);
                
                if (currentAttachment) {
                    formData.append('file_attachment', currentAttachment);
                }
                
                fetch('chat_interface.php?chat_id=<?php echo $chatId; ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(result => {
                    if (result === 'success') {
                        messageInput.value = '';
                        messageInput.style.height = 'auto';
                        currentAttachment = null;
                        attachmentPreview.innerHTML = '';
                        fileInput.value = '';
                        // Immediately fetch new messages
                        fetchNewMessages();
                    } else if (result === 'file_upload_error') {
                        alert('Failed to upload file. Please try again.');
                    } else if (result === 'empty') {
                        alert('Please enter a message or attach a file.');
                    } else {
                        alert('Failed to send message. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to send message. Please try again.');
                })
                .finally(() => {
                    isSending = false;
                    sendBtn.disabled = false;
                    sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
                    messageInput.focus();
                });
            }
        }

        // Fetch new messages
        function fetchNewMessages() {
            fetch(`chat_interface.php?chat_id=<?php echo $chatId; ?>&ajax_get_messages=true&last_id=${lastMessageId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(messages => {
                    if (messages.length > 0) {
                        messages.forEach(message => {
                            addMessageToChat(message);
                            lastMessageId = Math.max(lastMessageId, message.message_id);
                        });
                        scrollToBottom();
                        updateLastActivity();
                    }
                })
                .catch(error => console.error('Error fetching messages:', error));
        }

        // Add message to chat
        function addMessageToChat(message) {
            const isOwnMessage = message.userid == <?php echo $userId; ?>;
            const messageElement = document.createElement('div');
            messageElement.className = `message ${isOwnMessage ? 'own' : ''}`;
            messageElement.setAttribute('data-message-id', message.message_id);
            
            const avatarText = message.first_name.charAt(0).toUpperCase() + message.last_name.charAt(0).toUpperCase();
            const senderName = isOwnMessage ? 'You' : `${message.first_name} ${message.last_name}`;
            const messageTime = new Date(message.created_at).toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                hour: 'numeric',
                minute: 'numeric',
                hour12: true
            });
            
            let fileHtml = '';
            if (message.file_path) {
                const fileName = message.file_path.split('/').pop();
                const fileExtension = fileName.split('.').pop().toLowerCase();
                const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
                const isImage = imageExtensions.includes(fileExtension);
                
                if (isImage) {
                    fileHtml = `
                        <div class="image-attachment">
                            <img src="${message.file_path}" 
                                 alt="Image attachment" 
                                 onclick="openImageModal('${message.file_path}')">
                        </div>
                    `;
                } else {
                    fileHtml = `
                        <div class="file-attachment">
                            <div class="file-icon">
                                <i class="fas fa-file"></i>
                            </div>
                            <div class="file-info">
                                <div class="file-name">${fileName}</div>
                                <div class="file-size">${formatFileSizeForDisplay(0)}</div>
                            </div>
                            <a href="${message.file_path}" 
                               class="file-download" 
                               download="${fileName}">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                    `;
                }
            }
            
            // Process message text for links
            let messageHtml = '';
            if (message.message) {
                messageHtml = autoLinkUrls(message.message);
            }
            
            messageElement.innerHTML = `
                <div class="message-avatar">${avatarText}</div>
                <div class="message-content">
                    <div class="message-header">
                        <span class="message-sender">${senderName}</span>
                        <span class="message-time">${messageTime}</span>
                    </div>
                    ${messageHtml ? `<div class="message-text">${messageHtml}</div>` : ''}
                    ${fileHtml}
                </div>
            `;
            
            // Remove empty state if it exists
            const emptyState = messagesArea.querySelector('.empty-state');
            if (emptyState) {
                emptyState.remove();
            }
            
            messagesArea.appendChild(messageElement);
            
            // Add link previews for any URLs in the message
            if (message.message) {
                addLinkPreviews(messageElement, message.message);
            }
        }

        // Auto-link URLs in text
        function autoLinkUrls(text) {
            // URL pattern matching
            const urlPattern = /(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig;
            
            return text.replace(urlPattern, function(url) {
                return '<a href="' + url + '" target="_blank" rel="noopener noreferrer">' + url + '</a>';
            });
        }

        // Add link previews
        function addLinkPreviews(messageElement, text) {
            const urlPattern = /(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig;
            const urls = text.match(urlPattern);
            
            if (urls) {
                urls.forEach(url => {
                    // For demonstration, we'll create a simple preview
                    // In a real application, you'd fetch meta data from the URL
                    createLinkPreview(messageElement, url);
                });
            }
        }

        // Create link preview
        function createLinkPreview(messageElement, url) {
            const messageContent = messageElement.querySelector('.message-content');
            const linkPreview = document.createElement('div');
            linkPreview.className = 'link-preview';
            
            // Extract domain for display
            const domain = new URL(url).hostname;
            
            linkPreview.innerHTML = `
                <div class="link-preview-content">
                    <div class="link-preview-title">Link Preview</div>
                    <div class="link-preview-description">Click to visit this link</div>
                    <div class="link-preview-url">${domain}</div>
                </div>
            `;
            
            // Make the entire preview clickable
            linkPreview.style.cursor = 'pointer';
            linkPreview.addEventListener('click', function() {
                window.open(url, '_blank', 'noopener,noreferrer');
            });
            
            messageContent.appendChild(linkPreview);
        }

        // Scroll to bottom of messages
        function scrollToBottom() {
            if (messagesArea) {
                messagesArea.scrollTop = messagesArea.scrollHeight;
            }
        }

        // Update last activity time
        function updateLastActivity() {
            const lastActivityElement = document.getElementById('lastActivity');
            if (lastActivityElement) {
                const now = new Date();
                lastActivityElement.textContent = now.toLocaleString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    hour: 'numeric',
                    minute: 'numeric',
                    hour12: true
                });
            }
        }

        // Format file size for display
        function formatFileSizeForDisplay(bytes) {
            if (bytes === 0) return '0 Bytes';
            
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Open image modal
        function openImageModal(src) {
            modalImage.src = src;
            imageModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        // Close image modal
        function closeImageModal() {
            imageModal.classList.remove('active');
            document.body.style.overflow = '';
        }

        // Close modal when clicking outside the image
        imageModal.addEventListener('click', function(e) {
            if (e.target === imageModal) {
                closeImageModal();
            }
        });

        // Handle Enter key for join chat input (prevent form submission on Enter)
        if (joinChatForm) {
            const inviteCodeInput = joinChatForm.querySelector('input[name="invite_code"]');
            if (inviteCodeInput) {
                inviteCodeInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                    }
                });
            }
        }

        // Start auto-refresh
        function startAutoRefresh() {
            refreshInterval = setInterval(fetchNewMessages, 2000); // Check every 2 seconds
        }

        // Stop auto-refresh
        function stopAutoRefresh() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        }

        // Initialize chat
        function initChat() {
            scrollToBottom();
            startAutoRefresh();
            if (messageInput) {
                messageInput.focus();
            }
        }

        // Handle send button click
        if (sendBtn) {
            sendBtn.addEventListener('click', sendMessage);
        }

        // Simple search functionality for chat list
        document.querySelector('.search-bar input').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const chatItems = document.querySelectorAll('.chat-item');
            
            chatItems.forEach(item => {
                const chatName = item.querySelector('.chat-name').textContent.toLowerCase();
                const chatDetails = item.querySelector('.chat-details').textContent.toLowerCase();
                
                if (chatName.includes(searchTerm) || chatDetails.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // Auto-focus invite code input if there's an error
        <?php if (isset($joinError)): ?>
            if (document.querySelector('input[name="invite_code"]')) {
                document.querySelector('input[name="invite_code"]').focus();
            }
        <?php endif; ?>

        // Initialize chat when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initChat();
        });

        // Clean up when leaving page
        window.addEventListener('beforeunload', function() {
            stopAutoRefresh();
        });
    </script>
</body>
</html>

<?php
// Helper function to format file size
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 Bytes';
    
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    
    return number_format($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

// Helper function to automatically convert URLs to links
function autoLinkUrls($text) {
    // URL pattern matching
    $urlPattern = '/(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/i';
    
    return preg_replace($urlPattern, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>', $text);
}
?>