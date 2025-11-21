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

// Verify ownership and get chat details - modified for admin access
if ($currentUserRole === 'admin') {
    // Admin can access any chat
    $chatSql = "SELECT cc.*, c.course_code, c.course_name 
                FROM course_chats cc 
                JOIN courses c ON cc.course_id = c.course_id 
                WHERE cc.chat_id = ?";
    $chatStmt = $conn->prepare($chatSql);
    $chatStmt->bind_param("i", $chatId);
} else {
    // Faculty can only access their own chats
    $chatSql = "SELECT cc.*, c.course_code, c.course_name 
                FROM course_chats cc 
                JOIN courses c ON cc.course_id = c.course_id 
                WHERE cc.chat_id = ? AND cc.faculty_id = ?";
    $chatStmt = $conn->prepare($chatSql);
    $chatStmt->bind_param("ii", $chatId, $userId);
}

$chatStmt->execute();
$chatResult = $chatStmt->get_result();
$chat = $chatResult->fetch_assoc();

if (!$chat) {
    if ($currentUserRole === 'admin') {
        header("Location: faculty_chats.php");
    } else {
        header("Location: faculty_chats.php");
    }
    exit;
}

// Get current members
$membersSql = "SELECT cm.*, u.first_name, u.last_name, u.email 
               FROM course_chat_members cm 
               JOIN users u ON cm.user_id = u.userid 
               WHERE cm.chat_id = ? 
               ORDER BY 
                 CASE cm.role 
                   WHEN 'faculty' THEN 1 
                   WHEN 'co-admin' THEN 2 
                   ELSE 3 
                 END, u.first_name";
$membersStmt = $conn->prepare($membersSql);
$membersStmt->bind_param("i", $chatId);
$membersStmt->execute();
$membersResult = $membersStmt->get_result();
$members = $membersResult->fetch_all(MYSQLI_ASSOC);

// Handle member actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user has permission to manage members
    $canManage = false;
    if ($currentUserRole === 'admin') {
        $canManage = true;
    } else {
        // Check if current user is the faculty owner of this chat
        $ownerCheck = $conn->prepare("SELECT faculty_id FROM course_chats WHERE chat_id = ? AND faculty_id = ?");
        $ownerCheck->bind_param("ii", $chatId, $userId);
        $ownerCheck->execute();
        $ownerResult = $ownerCheck->get_result();
        $canManage = ($ownerResult->num_rows > 0);
    }
    
    if ($canManage) {
        if (isset($_POST['remove_member'])) {
            $memberId = $_POST['member_id'];
            $removeSql = "DELETE FROM course_chat_members WHERE member_id = ? AND chat_id = ?";
            $removeStmt = $conn->prepare($removeSql);
            $removeStmt->bind_param("ii", $memberId, $chatId);
            $removeStmt->execute();
            
            // Refresh members list
            header("Location: manage_members.php?chat_id=" . $chatId);
            exit;
        }
        
        if (isset($_POST['change_role'])) {
            $memberId = $_POST['member_id'];
            $newRole = $_POST['new_role'];
            $roleSql = "UPDATE course_chat_members SET role = ? WHERE member_id = ? AND chat_id = ?";
            $roleStmt = $conn->prepare($roleSql);
            $roleStmt->bind_param("sii", $newRole, $memberId, $chatId);
            $roleStmt->execute();
            
            header("Location: manage_members.php?chat_id=" . $chatId);
            exit;
        }
        
        if (isset($_POST['add_member'])) {
            $email = mysqli_real_escape_string($conn, $_POST['email']);
            $role = mysqli_real_escape_string($conn, $_POST['role']);
            
            // Find user by email
            $userSql = "SELECT userid FROM users WHERE email = ?";
            $userStmt = $conn->prepare($userSql);
            $userStmt->bind_param("s", $email);
            $userStmt->execute();
            $userResult = $userStmt->get_result();
            
            if ($userResult->num_rows > 0) {
                $user = $userResult->fetch_assoc();
                
                // Check if user is already a member
                $existingSql = "SELECT member_id FROM course_chat_members WHERE chat_id = ? AND user_id = ?";
                $existingStmt = $conn->prepare($existingSql);
                $existingStmt->bind_param("ii", $chatId, $user['userid']);
                $existingStmt->execute();
                $existingResult = $existingStmt->get_result();
                
                if ($existingResult->num_rows === 0) {
                    $addSql = "INSERT INTO course_chat_members (chat_id, user_id, role) VALUES (?, ?, ?)";
                    $addStmt = $conn->prepare($addSql);
                    $addStmt->bind_param("iis", $chatId, $user['userid'], $role);
                    $addStmt->execute();
                } else {
                    $error = "User is already a member of this chat.";
                }
                
                header("Location: manage_members.php?chat_id=" . $chatId);
                exit;
            } else {
                $error = "User with email '$email' not found.";
            }
        }
    } else {
        $error = "You don't have permission to manage members of this chat.";
    }
}

// Determine user display title
if ($currentUserRole === 'admin') {
    $userTitle = 'Administrator';
} else if ($currentUserRole === 'faculty') {
    $userTitle = 'Faculty';
} else {
    $userTitle = 'Student';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Members - TiPeed</title>
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
        }

       
        /* Main Content */
        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            background: #f8f9fa;
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

        .back-btn {
            background: #6c757d;
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

        .back-btn:hover {
            background: #545b62;
            transform: translateY(-2px);
        }

        /* Chat Info */
        .chat-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.3);
        }

        .chat-info h2 {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .chat-meta {
            display: flex;
            gap: 20px;
            font-size: 14px;
            opacity: 0.9;
        }

        /* Members Container */
        .members-container {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            border: 1px solid #eef2f7;
        }

        .section-title {
            font-size: 22px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Add Member Form */
        .add-member-form {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            border-left: 4px solid #f5b301;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            background: white;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #f5b301;
            box-shadow: 0 0 0 3px rgba(245, 179, 1, 0.1);
        }

        .add-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .add-btn:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        /* Members List */
        .members-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-height: 500px;
            overflow-y: auto;
        }

        .member-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            background: #fafbfc;
            border-radius: 12px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
        }

        .member-item:hover {
            border-color: #f5b301;
            transform: translateY(-2px);
        }

        .member-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }

        .member-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f5b301, #e0a500);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 16px;
            flex-shrink: 0;
        }

        .member-details {
            flex: 1;
        }

        .member-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 4px;
        }

        .member-email {
            color: #6c757d;
            font-size: 14px;
        }

        .member-role {
            background: #e9ecef;
            color: #495057;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .role-faculty { background: #d4edda; color: #155724; }
        .role-co-admin { background: #cce7ff; color: #004085; }
        .role-student { background: #fff3cd; color: #856404; }
        .role-admin { background: #e2e3e5; color: #383d41; }

        .member-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .role-select {
            padding: 8px 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 13px;
            background: white;
            cursor: pointer;
        }

        .remove-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
        }

        .remove-btn:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        /* Admin Badge */
        .admin-badge {
            background: #6f42c1;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 8px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
                flex-wrap: wrap;
                gap: 15px;
            }
            
            .nav-links {
                gap: 15px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .member-item {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .member-info {
                flex-direction: column;
                text-align: center;
            }
            
            .member-actions {
                width: 100%;
                justify-content: center;
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
            <div class="page-header">
                <h1 class="page-title">Manage Members</h1>
                <a href="<?php echo $currentUserRole === 'admin' ? 'faculty_chats.php' : 'faculty_chats.php'; ?>" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Chats
                </a>
            </div>

            <!-- Chat Info -->
            <div class="chat-info">
                <h2><?php echo htmlspecialchars($chat['group_name']); ?></h2>
                <div class="chat-meta">
                    <span><i class="fas fa-book"></i> <?php echo htmlspecialchars($chat['course_code'] . ' - ' . $chat['course_name']); ?></span>
                    <span><i class="fas fa-users"></i> <?php echo count($members); ?> Members</span>
                    <span><i class="fas fa-calendar"></i> Created <?php echo date('M j, Y', strtotime($chat['created_at'])); ?></span>
                    <?php if ($currentUserRole === 'admin'): ?>
                        <span class="admin-badge">Admin View</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="members-container">
                <!-- Add Member Form -->
                <div class="add-member-form">
                    <h3 class="section-title">
                        <i class="fas fa-user-plus"></i>
                        Add New Member
                    </h3>
                    <form method="POST" class="form-row">
                        <div class="form-group">
                            <label for="memberEmail">Email Address</label>
                            <input type="email" id="memberEmail" name="email" class="form-input" placeholder="Enter email address" required>
                        </div>
                        <div class="form-group">
                            <label for="memberRole">Role</label>
                            <select id="memberRole" name="role" class="form-select" required>
                                <option value="student">Student</option>
                                <option value="co-admin">Co-Admin</option>
                                <?php if ($currentUserRole === 'admin'): ?>
                                    <option value="faculty">Faculty</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <button type="submit" name="add_member" class="add-btn">
                            <i class="fas fa-plus"></i> Add Member
                        </button>
                    </form>
                    <?php if (isset($error)): ?>
                        <div style="color: #dc3545; margin-top: 10px; font-size: 14px;">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Members List -->
                <h3 class="section-title">
                    <i class="fas fa-users"></i>
                    Current Members (<?php echo count($members); ?>)
                </h3>
                
                <div class="members-list">
                    <?php if (empty($members)): ?>
                        <div class="empty-state">
                            <i class="fas fa-users-slash"></i>
                            <h3>No Members Yet</h3>
                            <p>Add members to this chat using the form above.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($members as $member): ?>
                            <div class="member-item">
                                <div class="member-info">
                                    <div class="member-avatar">
                                        <?php echo strtoupper(substr($member['first_name'], 0, 1) . substr($member['last_name'], 0, 1)); ?>
                                    </div>
                                    <div class="member-details">
                                        <div class="member-name">
                                            <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>
                                            <?php if ($member['user_id'] == $userId): ?>
                                                <span style="color: #f5b301; font-weight: 600;"> (You)</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="member-email"><?php echo htmlspecialchars($member['email']); ?></div>
                                    </div>
                                    <span class="member-role role-<?php echo str_replace('-', '', $member['role']); ?>">
                                        <?php echo ucfirst($member['role']); ?>
                                    </span>
                                </div>
                                <div class="member-actions">
                                    <?php 
                                    // Check if current user can modify this member
                                    $canModify = false;
                                    if ($currentUserRole === 'admin') {
                                        $canModify = true;
                                    } else if ($member['role'] !== 'faculty') {
                                        // Faculty can modify non-faculty members
                                        $canModify = true;
                                    }
                                    ?>
                                    
                                    <?php if ($canModify && $member['user_id'] != $userId): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="member_id" value="<?php echo $member['member_id']; ?>">
                                            <select name="new_role" class="role-select" onchange="this.form.submit()">
                                                <option value="student" <?php echo $member['role'] === 'student' ? 'selected' : ''; ?>>Student</option>
                                                <option value="co-admin" <?php echo $member['role'] === 'co-admin' ? 'selected' : ''; ?>>Co-Admin</option>
                                                <?php if ($currentUserRole === 'admin'): ?>
                                                    <option value="faculty" <?php echo $member['role'] === 'faculty' ? 'selected' : ''; ?>>Faculty</option>
                                                <?php endif; ?>
                                            </select>
                                            <input type="hidden" name="change_role" value="1">
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="member_id" value="<?php echo $member['member_id']; ?>">
                                            <button type="submit" name="remove_member" class="remove-btn" onclick="return confirm('Are you sure you want to remove this member?')">
                                                <i class="fas fa-times"></i> Remove
                                            </button>
                                        </form>
                                    <?php elseif ($member['user_id'] == $userId): ?>
                                        <span style="color: #6c757d; font-size: 13px;">Current User</span>
                                    <?php else: ?>
                                        <span style="color: #6c757d; font-size: 13px;"><?php echo $member['role'] === 'faculty' ? 'Chat Owner' : 'Protected'; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sidebar toggle functionality
        const sidebar = document.getElementById('sidebar');
        const toggleSidebar = document.getElementById('toggleSidebar');
        
        if (toggleSidebar && sidebar) {
            toggleSidebar.addEventListener('click', () => sidebar.classList.toggle('expanded'));
        }

        // Simple search functionality
        document.querySelector('.search-bar input').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const memberItems = document.querySelectorAll('.member-item');
            
            memberItems.forEach(item => {
                const name = item.querySelector('.member-name').textContent.toLowerCase();
                const email = item.querySelector('.member-email').textContent.toLowerCase();
                
                if (name.includes(searchTerm) || email.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>