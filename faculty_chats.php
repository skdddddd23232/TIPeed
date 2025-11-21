<?php
session_start();
include "db_connect.php";

if (!isset($_SESSION['userid']) || ($_SESSION['role'] !== 'faculty' && $_SESSION['role'] !== 'admin')) {
    header("Location: auth.php");
    exit;
}

$userId = $_SESSION['userid'];
$userName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$currentUserRole = $_SESSION['role'];

// Set home page based on role
if ($currentUserRole === 'admin') {
    $homePage = 'admin_home.php';
} else if ($currentUserRole === 'faculty') {
    $homePage = 'teacher_home.php';
} else {
    $homePage = 'student_home.php';
}

// Get all course chats based on role - UPDATED TO INCLUDE invite_code
if ($currentUserRole === 'admin') {
    // Admin can see all course chats
    $sql = "SELECT cc.*, c.course_code, c.course_name, 
                COUNT(DISTINCT cm.user_id) as member_count,
                cc.invite_code
            FROM course_chats cc 
            JOIN courses c ON cc.course_id = c.course_id 
            LEFT JOIN course_chat_members cm ON cc.chat_id = cm.chat_id
            GROUP BY cc.chat_id
            ORDER BY cc.created_at DESC";
    $stmt = $conn->prepare($sql);
} else {
    // Faculty can only see their own course chats
    $sql = "SELECT cc.*, c.course_code, c.course_name, 
                COUNT(DISTINCT cm.user_id) as member_count,
                cc.invite_code
            FROM course_chats cc 
            JOIN courses c ON cc.course_id = c.course_id 
            LEFT JOIN course_chat_members cm ON cc.chat_id = cm.chat_id
            WHERE cc.faculty_id = ? 
            GROUP BY cc.chat_id
            ORDER BY cc.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
}

$stmt->execute();
$result = $stmt->get_result();
$chats = $result->fetch_all(MYSQLI_ASSOC);

// Handle invite code generation - UPDATED SECTION
if (isset($_GET['generate_invite']) && isset($_GET['chat_id'])) {
    $chatId = $_GET['chat_id'];
    
    // Generate a simple 6-character invite code (easier to share)
    $inviteCode = substr(strtoupper(bin2hex(random_bytes(3))), 0, 6);
    
    // Store only the invite code in database (no link needed)
    $updateSql = "UPDATE course_chats SET invite_code = ? WHERE chat_id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("si", $inviteCode, $chatId);
    
    if ($updateStmt->execute()) {
        header("Location: faculty_chats.php?success=invite_generated&code=" . $inviteCode);
        exit;
    } else {
        header("Location: faculty_chats.php?error=invite_failed");
        exit;
    }
}

// Handle invite code deletion - UPDATED SECTION
if (isset($_GET['delete_invite']) && isset($_GET['chat_id'])) {
    $chatId = $_GET['chat_id'];
    
    $updateSql = "UPDATE course_chats SET invite_code = NULL WHERE chat_id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("i", $chatId);
    
    if ($updateStmt->execute()) {
        header("Location: faculty_chats.php?success=invite_deleted");
        exit;
    } else {
        header("Location: faculty_chats.php?error=delete_failed");
        exit;
    }
}

$currentUserRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// Set home page based on role
if ($currentUserRole === 'admin') {
    $homePage = 'admin_home.php';
} else if ($currentUserRole === 'faculty') {
    $homePage = 'teacher_home.php';
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Course Chats - TiPeed</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="../css/NS.css">
    <style>
        /* Your existing CSS styles remain the same */
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
        }

        
        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #333;
        }

        .create-chat-btn {
            background: #f5b301;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .create-chat-btn:hover {
            background: #e0a500;
            transform: translateY(-2px);
        }

        .chats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .chat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }

        .chat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .chat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .chat-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .chat-course {
            font-size: 14px;
            color: #f5b301;
            font-weight: 500;
        }

        .chat-section {
            font-size: 13px;
            color: #666;
        }

        .chat-description {
            color: #666;
            font-size: 14px;
            line-height: 1.4;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .chat-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }

        .stat {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
            color: #666;
        }

        .chat-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .view-btn { background: #007bff; color: white; }
        .view-btn:hover { background: #0056b3; }
        
        .edit-btn { background: #17a2b8; color: white; }
        .edit-btn:hover { background: #138496; }
        
        .manage-btn { background: #6c757d; color: white; }
        .manage-btn:hover { background: #545b62; }

        .invite-btn { background: #28a745; color: white; }
        .invite-btn:hover { background: #218838; }

        .delete-invite-btn { background: #dc3545; color: white; }
        .delete-invite-btn:hover { background: #c82333; }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .empty-icon {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-title {
            font-size: 24px;
            color: #666;
            margin-bottom: 10px;
        }

        .empty-description {
            color: #888;
            margin-bottom: 30px;
        }

        .invite-section {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 12px;
            margin-top: 40px;
        }

        .invite-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }

        .invite-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .invite-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .invite-code {
            font-family: monospace;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            letter-spacing: 2px;
            border: 2px dashed #28a745;
        }

        .copy-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            margin-right: 8px;
        }

        .copy-btn:hover { background: #218838; }

        .generate-invite-btn {
            background: #f5b301;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            margin-right: 8px;
        }

        .generate-invite-btn:hover { background: #e0a500; }

        /* Success/Error Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .admin-badge {
            background: #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
        }

        .invite-instructions {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .invite-instructions h4 {
            color: #007bff;
            margin-bottom: 8px;
        }

        /* Fix for the status badge */
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-badge.active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.inactive {
            background: #f8d7da;
            color: #721c24;
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
                <a href="profile.php" class="menu-item"><div class="menu-icon"><i class="fas fa-user"></i></div><div class="menu-text">Profile</div></a>
                <a href="<?= $homePage ?>" class="menu-item"><div class="menu-icon"><i class="fas fa-home"></i></div><div class="menu-text">Home</div></a>
                <a href="chat_interface.php" class="menu-item"><div class="menu-icon"><i class="fas fa-comment-dots"></i></div><div class="menu-text">Course Chat</div></a>
                <a href="CourseChat.php" class="menu-item"><div class="menu-icon"><i class="fas fa-comments"></i></div><div class="menu-text">Communities Chat</div></a>
                <a href="Community.php" class="menu-item"><div class="menu-icon"><i class="fas fa-users"></i></div><div class="menu-text">Community</div></a>
                <?php if ($currentUserRole === 'admin'): ?>
                <a href="admin_reg.php" class="menu-item"><div class="menu-icon"><i class="fas fa-user-plus"></i></div><div class="menu-text">Register</div></a>
                <?php endif; ?>
                <a href="calendar.php" class="menu-item active"><div class="menu-icon"><i class="fas fa-calendar-alt"></i></div><div class="menu-text">Calendar</div></a>
                <div class="menu-item"><div class="menu-icon"><i class="fas fa-cog"></i></div><div class="menu-text">Settings</div></div>
                <a href="Help.php" class="menu-item"><div class="menu-icon"><i class="fas fa-question-circle"></i></div><div class="menu-text">Help</div></a>
                <a href="logout.php" class="menu-item"><div class="menu-icon"><i class="fas fa-sign-out-alt"></i></div><div class="menu-text">Log Out</div></a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Success/Error Messages -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php 
                    if ($_GET['success'] === 'invite_generated'): 
                        echo 'Invite code generated successfully! Code: ' . htmlspecialchars($_GET['code']);
                    elseif ($_GET['success'] === 'invite_deleted'): 
                        echo 'Invite code deleted successfully!';
                    endif;
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php 
                    if ($_GET['error'] === 'invite_failed') echo 'Failed to generate invite code.';
                    if ($_GET['error'] === 'delete_failed') echo 'Failed to delete invite code.';
                    ?>
                </div>
            <?php endif; ?>

            <div class="page-header">
                <h1 class="page-title">
                    <?php echo $currentUserRole === 'admin' ? 'All Course Chats' : 'My Course Chats'; ?>
                    <?php if ($currentUserRole === 'admin'): ?>
                        <span class="admin-badge">ADMIN VIEW</span>
                    <?php endif; ?>
                </h1>
                <?php if ($currentUserRole === 'faculty'): ?>
                    <a href="faculty_create_chat.php" class="create-chat-btn">
                        <i class="fas fa-plus"></i> Create New Chat
                    </a>
                <?php endif; ?>
            </div>

            <?php if (empty($chats)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h2 class="empty-title">No Course Chats Yet</h2>
                    <p class="empty-description">
                        <?php if ($currentUserRole === 'admin'): ?>
                            There are no course chats created yet.
                        <?php else: ?>
                            Create your first course chat to start communicating with students and co-admins.
                        <?php endif; ?>
                    </p>
                    <?php if ($currentUserRole === 'faculty'): ?>
                        <a href="faculty_create_chat.php" class="create-chat-btn">
                            <i class="fas fa-plus"></i> Create Your First Chat
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="chats-grid">
                    <?php foreach ($chats as $chat): ?>
                        <div class="chat-card">
                            <div class="chat-header">
                                <div>
                                    <h3 class="chat-title"><?php echo htmlspecialchars($chat['group_name']); ?></h3>
                                    <div class="chat-course"><?php echo htmlspecialchars($chat['course_code'] . ' - ' . $chat['course_name']); ?></div>
                                    <?php if ($chat['class_section']): ?>
                                        <div class="chat-section"><?php echo htmlspecialchars($chat['class_section']); ?></div>
                                    <?php endif; ?>
                                    <?php if ($currentUserRole === 'admin'): ?>
                                        <div class="chat-section" style="color: #dc3545; font-weight: 600;">
                                            Created by: <?php 
                                                // Get faculty name
                                                $facultySql = "SELECT first_name, last_name FROM users WHERE userid = ?";
                                                $facultyStmt = $conn->prepare($facultySql);
                                                $facultyStmt->bind_param("i", $chat['faculty_id']);
                                                $facultyStmt->execute();
                                                $facultyResult = $facultyStmt->get_result();
                                                $faculty = $facultyResult->fetch_assoc();
                                                echo htmlspecialchars($faculty['first_name'] . ' ' . $faculty['last_name']);
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="chat-status">
                                    <span class="status-badge <?php echo $chat['is_coadmin_allowed'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $chat['is_coadmin_allowed'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if ($chat['description']): ?>
                                <p class="chat-description"><?php echo htmlspecialchars($chat['description']); ?></p>
                            <?php endif; ?>
                            
                            <div class="chat-stats">
                                <div class="stat">
                                    <i class="fas fa-users"></i>
                                    <span><?php echo $chat['member_count']; ?> members</span>
                                </div>
                                <div class="stat">
                                    <i class="fas fa-calendar"></i>
                                    <span><?php echo date('M j, Y', strtotime($chat['created_at'])); ?></span>
                                </div>
                            </div>
                            
                            <div class="chat-actions">
                                <a href="chat_interface.php?chat_id=<?php echo $chat['chat_id']; ?>" class="action-btn view-btn">
                                    <i class="fas fa-comments"></i> Enter Chat
                                </a>
                                <?php if ($currentUserRole === 'admin' || $chat['faculty_id'] == $userId): ?>
                                    <a href="edit_chat.php?chat_id=<?php echo $chat['chat_id']; ?>" class="action-btn edit-btn">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="manage_members.php?chat_id=<?php echo $chat['chat_id']; ?>" class="action-btn manage-btn">
                                        <i class="fas fa-users-cog"></i> Manage
                                    </a>
                                    <?php if (empty($chat['invite_code'])): ?>
                                        <a href="faculty_chats.php?generate_invite=1&chat_id=<?php echo $chat['chat_id']; ?>" class="action-btn invite-btn">
                                            <i class="fas fa-key"></i> Generate Invite Code
                                        </a>
                                    <?php else: ?>
                                        <span class="action-btn" style="background: #6c757d; cursor: default;">
                                            <i class="fas fa-key"></i> Code: <?php echo $chat['invite_code']; ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Active Invite Codes Section -->
                <div class="invite-section">
                    <h3 class="invite-title">Active Invite Codes</h3>
                    
                    <!-- Instructions for using invite codes -->
                    <div class="invite-instructions">
                        <h4><i class="fas fa-info-circle"></i> How to use invite codes:</h4>
                        <p>1. Generate an invite code for a course chat<br>
                        2. Share the code with students<br>
                        3. Students enter the code in the "Join New Chat" section of the chat interface<br>
                        4. Students will be automatically added to the chat</p>
                    </div>
                    
                    <div class="invite-grid">
                        <?php 
                        $hasActiveInvites = false;
                        foreach ($chats as $chat): 
                            $inviteCode = isset($chat['invite_code']) ? $chat['invite_code'] : '';
                            $hasInviteCode = !empty($inviteCode);
                            
                            if ($hasInviteCode && ($currentUserRole === 'admin' || $chat['faculty_id'] == $userId)): 
                                $hasActiveInvites = true;
                        ?>
                                <div class="invite-card">
                                    <strong><?php echo htmlspecialchars($chat['group_name']); ?></strong>
                                    <div class="invite-code">
                                        <?php echo htmlspecialchars($inviteCode); ?>
                                    </div>
                                    <div>
                                        <button class="copy-btn" onclick="copyToClipboard('<?php echo $inviteCode; ?>')">
                                            <i class="fas fa-copy"></i> Copy Code
                                        </button>
                                        <a href="faculty_chats.php?delete_invite=1&chat_id=<?php echo $chat['chat_id']; ?>" class="action-btn delete-invite-btn" onclick="return confirm('Are you sure you want to delete this invite code?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <?php if (!$hasActiveInvites): ?>
                            <div class="empty-state" style="background: transparent; box-shadow: none;">
                                <i class="fas fa-key" style="font-size: 48px;"></i>
                                <h3>No Active Invite Codes</h3>
                                <p>Generate invite codes to allow students to join your course chats.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('Invite code copied to clipboard! Share this code with students.');
            }, function(err) {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('Invite code copied to clipboard! Share this code with students.');
            });
        }

        // Simple search functionality
        document.querySelector('.search-bar input').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const chatCards = document.querySelectorAll('.chat-card');
            
            chatCards.forEach(card => {
                const title = card.querySelector('.chat-title').textContent.toLowerCase();
                const course = card.querySelector('.chat-course').textContent.toLowerCase();
                const description = card.querySelector('.chat-description')?.textContent.toLowerCase() || '';
                
                if (title.includes(searchTerm) || course.includes(searchTerm) || description.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Sidebar toggle functionality
        const sidebar = document.getElementById('sidebar');
        const toggleSidebar = document.getElementById('toggleSidebar');
        if(toggleSidebar && sidebar) {
            toggleSidebar.addEventListener('click', () => sidebar.classList.toggle('expanded'));
        }
    </script>
</body>
</html>