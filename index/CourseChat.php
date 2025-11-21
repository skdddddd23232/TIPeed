<?php
// --- AUTO CLEAR BROWSER CACHE ---
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache");
header("Expires: 0");
session_start();
include 'db_connect.php';

// check if logged in
if (!isset($_SESSION['userid'])) {
    header("Location: auth.php");
    exit;
}

// Handle AJAX message send
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_message'])) {
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $community_id = $_POST['community_id'];
    $filePath = null;
    
    // Handle file upload
    if (isset($_FILES['file_attachment']) && $_FILES['file_attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/community_files/';
        
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
        $insertSql = "INSERT INTO community_messages (community_id, user_id, message, file_path) VALUES (?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("iiss", $community_id, $_SESSION['userid'], $message, $filePath);
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
    $community_id = $_GET['community_id'];
    
    $newMessagesSql = "SELECT m.*, u.first_name, u.last_name, u.userid
                      FROM community_messages m 
                      JOIN users u ON m.user_id = u.userid 
                      WHERE m.community_id = ? AND m.cmessages_id > ?
                      ORDER BY m.created_at ASC";
    $newMessagesStmt = $conn->prepare($newMessagesSql);
    $newMessagesStmt->bind_param("ii", $community_id, $lastId);
    $newMessagesStmt->execute();
    $newMessagesResult = $newMessagesStmt->get_result();
    $newMessages = $newMessagesResult->fetch_all(MYSQLI_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($newMessages);
    exit;
}

// Your existing user session and community data...
$studentName   = $_SESSION['first_name'] . " " . $_SESSION['last_name'];
$userName = $_SESSION['first_name'] . " " . $_SESSION['last_name'];
$currentUserRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$isAdmin = ($currentUserRole === 'admin');

// Set home page based on role
if ($currentUserRole === 'admin') {
    $homePage = 'admin_home.php';
} else if ($currentUserRole === 'faculty') {
    $homePage = 'teacher_home.php';
} else {
    $homePage = 'student_home.php';
}

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
        $studentIDT = ordinal($yearLevel) . " Year";
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

// get selected community
$community_id = $_GET['community_id'] ?? null;
$messages = [];
$community = null;

if ($community_id) {
    // fetch community info
    $stmt = $conn->prepare("SELECT * FROM communities WHERE communities_id = ?");
    $stmt->bind_param("i", $community_id);
    $stmt->execute();
    $community = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // For admin, skip the membership check - they can access all communities
    if (!$isAdmin) {
        $check = $conn->prepare("
            SELECT 1 FROM community_members 
            WHERE community_id = ? AND user_id = ?
        ");
        $check->bind_param("ii", $community_id, $_SESSION['userid']);
        $check->execute();
        $check->store_result();

        if ($check->num_rows === 0) {
            echo "<p>You must join this community to view messages.</p>";
            exit;
        }
    }

    // Get total member count for this community
    $memberCountSql = "SELECT COUNT(*) as total_members 
                      FROM community_members 
                      WHERE community_id = ?";
    $memberCountStmt = $conn->prepare($memberCountSql);
    $memberCountStmt->bind_param("i", $community_id);
    $memberCountStmt->execute();
    $memberCountResult = $memberCountStmt->get_result();
    $memberCount = $memberCountResult->fetch_assoc()['total_members'];

    // Get last activity time
    $lastActivitySql = "SELECT MAX(created_at) as last_activity 
                        FROM community_messages 
                        WHERE community_id = ?";
    $lastActivityStmt = $conn->prepare($lastActivitySql);
    $lastActivityStmt->bind_param("i", $community_id);
    $lastActivityStmt->execute();
    $lastActivityResult = $lastActivityStmt->get_result();
    $lastActivity = $lastActivityResult->fetch_assoc()['last_activity'];

    // Fetch messages for initial page load
    $stmt = $conn->prepare("
        SELECT m.cmessages_id, m.message, m.file_path, m.created_at, u.first_name, u.last_name, u.userid
        FROM community_messages m
        JOIN users u ON m.user_id = u.userid
        WHERE m.community_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->bind_param("i", $community_id);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Get last message ID for real-time updates
$lastMessageId = 0;
if (!empty($messages)) {
    $lastMessage = end($messages);
    $lastMessageId = $lastMessage['cmessages_id'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Community Chat - TiPeed</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css'); ?>">
  <script src="assets/js/app.js?v=<?= filemtime('assets/js/app.js'); ?>"></script>
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

    /* Chat Sidebar - Communities List */
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
        text-decoration: none;
        color: inherit;
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

    .admin-badge {
        background: #dc3545;
        color: white;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 10px;
        margin-left: 5px;
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
      <a href="<?= $homePage ?>" >Home</a>
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
      <!-- Sidebar - Communities List -->
      <div class="chat-sidebar">
        <div class="chat-header">
          <div class="chat-title">My Communities</div>
          <div class="chat-course"><?php echo $isAdmin ? 'All Communities (Admin View)' : 'Available Groups'; ?></div>
        </div>

        <div class="available-chats">
          <div class="section-title"><?php echo $isAdmin ? 'All Communities' : 'Your Communities'; ?></div>
          <div class="chat-list">
            <?php
            // Different query for admin vs regular users
            if ($isAdmin) {
                // Admin can see ALL communities
                $stmt = $conn->prepare("
                    SELECT c.* 
                    FROM communities c
                    ORDER BY c.created_at DESC
                ");
                $stmt->execute();
            } else {
                // Regular users only see communities they've joined
                $stmt = $conn->prepare("
                    SELECT c.* 
                    FROM communities c
                    JOIN community_members m ON c.communities_id = m.community_id
                    WHERE m.user_id = ?
                    ORDER BY c.created_at DESC
                ");
                $stmt->bind_param("i", $_SESSION['userid']);
                $stmt->execute();
            }
            
            $result = $stmt->get_result();
            $communities = $result->fetch_all(MYSQLI_ASSOC);
            ?>
            
            <?php if (!empty($communities)): ?>
                <?php foreach ($communities as $comm): ?>
                    <a href="coursechat.php?community_id=<?php echo $comm['communities_id']; ?>" 
                       class="chat-item <?php echo $comm['communities_id'] == $community_id ? 'active' : ''; ?>">
                        <div class="chat-avatar">
                            <?php echo strtoupper(substr($comm['name'], 0, 2)); ?>
                        </div>
                        <div class="chat-info">
                            <div class="chat-name">
                                <?php echo htmlspecialchars($comm['name']); ?>
                                <?php if ($isAdmin): ?>
                                    <span class="admin-badge">ALL</span>
                                <?php endif; ?>
                            </div>
                            <div class="chat-details"><?php echo htmlspecialchars($comm['description'] ?? 'Community Group'); ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No Communities</h3>
                    <p><?php echo $isAdmin ? 'No communities have been created yet.' : 'You haven\'t joined any communities yet.'; ?></p>
                </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Main Chat Area -->
      <div class="chat-main">
        <?php if ($community_id && $community): ?>
        <div class="messages-header">
          <div class="chat-info-main">
            <h2>
                <?php echo htmlspecialchars($community['name']); ?>
                <?php if ($isAdmin): ?>
                    <span class="admin-badge" style="font-size: 12px; padding: 3px 8px;">ADMIN VIEW</span>
                <?php endif; ?>
            </h2>
            <div class="chat-meta">
              <span id="memberCount"><?php echo $memberCount; ?></span> members • 
              Last activity: <span id="lastActivity"><?php echo $lastActivity ? date('M j, g:i A', strtotime($lastActivity)) : 'No activity yet'; ?></span>
            </div>
          </div>
          <div class="chat-actions">
            <div class="action-icon" title="Search messages">
              <i class="fas fa-search"></i>
            </div>
            <div class="action-icon" title="Community info">
              <i class="fas fa-info-circle"></i>
            </div>
          </div>
        </div>

        <div class="messages-area" id="messagesArea">
          <?php if (!empty($messages)): ?>
            <?php foreach ($messages as $msg): ?>
              <div class="message <?php echo $msg['userid'] == $_SESSION['userid'] ? 'own' : ''; ?>" data-message-id="<?php echo $msg['cmessages_id']; ?>">
                <div class="message-avatar">
                  <?php echo strtoupper(substr($msg['first_name'], 0, 1) . substr($msg['last_name'], 0, 1)); ?>
                </div>
                <div class="message-content">
                  <div class="message-header">
                    <span class="message-sender">
                      <?php echo $msg['userid'] == $_SESSION['userid'] ? 'You' : htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name']); ?>
                    </span>
                    <span class="message-time">
                      <?php echo date('M j, g:i A', strtotime($msg['created_at'])); ?>
                    </span>
                  </div>
                  <?php if (!empty($msg['message'])): ?>
                    <div class="message-text">
                      <?php echo autoLinkUrls($msg['message']); ?>
                    </div>
                  <?php endif; ?>
                  <?php if (!empty($msg['file_path'])): ?>
                    <?php
                    $fileExtension = strtolower(pathinfo($msg['file_path'], PATHINFO_EXTENSION));
                    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
                    $isImage = in_array($fileExtension, $imageExtensions);
                    $fileName = basename($msg['file_path']);
                    $fileSize = file_exists($msg['file_path']) ? filesize($msg['file_path']) : 0;
                    $formattedSize = formatFileSize($fileSize);
                    ?>
                    <?php if ($isImage): ?>
                      <div class="image-attachment">
                        <img src="<?php echo $msg['file_path']; ?>" 
                             alt="Image attachment" 
                             onclick="openImageModal('<?php echo $msg['file_path']; ?>')">
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
                        <a href="<?php echo $msg['file_path']; ?>" 
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
        <?php else: ?>
          <div class="empty-state" style="flex: 1; display: flex; flex-direction: column; justify-content: center;">
            <i class="fas fa-users fa-3x"></i>
            <h3>Select a Community</h3>
            <p>Choose a community from the sidebar to start chatting</p>
            <?php if ($isAdmin): ?>
              <p style="margin-top: 10px; font-size: 14px; color: #f5b301;">
                <i class="fas fa-shield-alt"></i> You have administrative access to all communities
              </p>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
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
    // Send message function
  function sendMessage() {
      if (isSending) return;
      
      const message = messageInput.value.trim();
      const communityId = <?php echo $community_id ? $community_id : 'null'; ?>;
      
      if ((message || currentAttachment) && communityId) {
          isSending = true;
          sendBtn.disabled = true;
          sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
          
          // Create FormData for AJAX request
          const formData = new FormData();
          formData.append('ajax_message', 'true');
          formData.append('message', message);
          formData.append('community_id', communityId);
          
          if (currentAttachment) {
              formData.append('file_attachment', currentAttachment);
          }
          
          fetch('coursechat.php', {
              method: 'POST',
              body: formData
          })
          .then(response => {
              if (!response.ok) {
                  throw new Error('Network response was not ok');
              }
              return response.text();
          })
          .then(result => {
              console.log('Send message result:', result); // Debug log
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
              alert('Failed to send message. Please check your connection and try again.');
          })
          .finally(() => {
              isSending = false;
              sendBtn.disabled = false;
              sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
              messageInput.focus();
          });
      } else if (!communityId) {
          alert('Please select a community first.');
      } else if (!message && !currentAttachment) {
          alert('Please enter a message or attach a file.');
      }
  }

      // Fetch new messages
      // Fetch new messages
function fetchNewMessages() {
    const communityId = <?php echo $community_id ? $community_id : 'null'; ?>;
    if (!communityId) return;
    
    fetch(`coursechat.php?ajax_get_messages=true&last_id=${lastMessageId}&community_id=${communityId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(messages => {
            if (messages && messages.length > 0) {
                messages.forEach(message => {
                    // Check if message already exists to avoid duplicates
                    const existingMessage = document.querySelector(`[data-message-id="${message.cmessages_id}"]`);
                    if (!existingMessage) {
                        addMessageToChat(message);
                        lastMessageId = Math.max(lastMessageId, message.cmessages_id);
                    }
                });
                scrollToBottom();
                updateLastActivity();
            }
        })
        .catch(error => console.error('Error fetching messages:', error));
}

    // Add message to chat
    function addMessageToChat(message) {
        const isOwnMessage = message.userid == <?php echo $_SESSION['userid']; ?>;
        const messageElement = document.createElement('div');
        messageElement.className = `message ${isOwnMessage ? 'own' : ''}`;
        messageElement.setAttribute('data-message-id', message.cmessages_id);
        
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

    // Simple search functionality for community list
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