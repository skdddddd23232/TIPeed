<?php
session_start();
include "db_connect.php";

if (!isset($_SESSION['userid']) || ($_SESSION['role'] !== 'faculty' && $_SESSION['role'] !== 'admin')) {
    header("Location: auth.php");
    exit;
}

$chatId = $_GET['chat_id'] ?? 0;
$userId = $_SESSION['userid'];
$userRole = $_SESSION['role'];
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

// Verify the chat belongs to this faculty OR user is admin
if ($userRole === 'admin') {
    // Admin can edit any chat
    $sql = "SELECT cc.*, c.course_code, c.course_name 
            FROM course_chats cc 
            JOIN courses c ON cc.course_id = c.course_id 
            WHERE cc.chat_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $chatId);
} else {
    // Faculty can only edit their own chats
    $sql = "SELECT cc.*, c.course_code, c.course_name 
            FROM course_chats cc 
            JOIN courses c ON cc.course_id = c.course_id 
            WHERE cc.chat_id = ? AND cc.faculty_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $chatId, $userId);
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

$stmt->execute();
$result = $stmt->get_result();
$chat = $result->fetch_assoc();

if (!$chat) {
    if ($userRole === 'admin') {
        header("Location: faculty_chats.php");
    } else {
        header("Location: faculty_chats.php");
    }
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $groupName = mysqli_real_escape_string($conn, $_POST['group_name']);
    $classSection = mysqli_real_escape_string($conn, $_POST['class_section']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $isCoAdminAllowed = isset($_POST['coadmin_allowed']) ? 1 : 0;
    $isApprovalAllowed = isset($_POST['approval_allowed']) ? 1 : 0;
    $isStudentAdditionAllowed = isset($_POST['student_addition_allowed']) ? 1 : 0;
    
    $updateSql = "UPDATE course_chats SET 
                  group_name = ?, 
                  class_section = ?, 
                  description = ?,
                  is_coadmin_allowed = ?,
                  is_approval_allowed = ?,
                  is_student_addition_allowed = ?
                  WHERE chat_id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("sssiiii", $groupName, $classSection, $description, 
                           $isCoAdminAllowed, $isApprovalAllowed, $isStudentAdditionAllowed, $chatId);
    
    if ($updateStmt->execute()) {
        $success = "Chat updated successfully!";
        // Refresh chat data
        $stmt->execute();
        $result = $stmt->get_result();
        $chat = $result->fetch_assoc();
    } else {
        $error = "Error updating chat: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Chat - <?php echo htmlspecialchars($chat['group_name']); ?> - TiPeed</title>
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

        /* Edit Form */
        .edit-form-container {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            border: 1px solid #eef2f7;
        }

        .form-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f5b301;
        }

        .course-avatar {
            width: 120px;
            height: 120px;
            border-radius: 12px;
            background: linear-gradient(135deg, #f5b301, #e0a500);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .course-avatar:hover {
            transform: scale(1.05);
        }

        .course-avatar i {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            background: #fafbfc;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #f5b301;
            background: white;
            box-shadow: 0 0 0 3px rgba(245, 179, 1, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
        }

        /* Settings Grid */
        .settings-section {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 12px;
            margin: 30px 0;
            border-left: 4px solid #f5b301;
        }

        .settings-title {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .setting-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: white;
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
            cursor: pointer;
        }

        .setting-item:hover {
            border-color: #f5b301;
            transform: translateY(-2px);
        }

        .setting-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: #f5b301;
        }

        .setting-item label {
            font-weight: 600;
            color: #495057;
            cursor: pointer;
            margin: 0;
        }

        .setting-description {
            font-size: 13px;
            color: #6c757d;
            margin-top: 4px;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #e9ecef;
        }

        .cancel-btn {
            background: #6c757d;
            color: white;
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .cancel-btn:hover {
            background: #545b62;
            transform: translateY(-2px);
        }

        .save-btn {
            background: linear-gradient(135deg, #f5b301, #e0a500);
            color: white;
            padding: 14px 32px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(245, 179, 1, 0.3);
        }

        .save-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(245, 179, 1, 0.4);
        }

        /* Success Message */
        .success-message {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .error-message {
            background: linear-gradient(135deg, #dc3545, #e83e8c);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

        /* Admin badge */
        .admin-badge {
            background: #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 8px;
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
            
            .form-header {
                flex-direction: column;
                text-align: center;
            }
            
            .settings-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
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
                <h1 class="page-title">
                    Edit Course Chat
                    <?php if ($userRole === 'admin'): ?>
                        <span style="font-size: 16px; color: #6c757d; margin-left: 10px;">(Admin Mode)</span>
                    <?php endif; ?>
                </h1>
                <a href="<?php echo $userRole === 'admin' ? 'faculty_chats.php' : 'faculty_chats.php'; ?>" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Chats
                </a>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($success)): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle fa-2x"></i>
                    <div>
                        <strong><?php echo $success; ?></strong>
                        <div>Your changes have been saved successfully.</div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                    <div>
                        <strong><?php echo $error; ?></strong>
                        <div>Please try again.</div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="edit-form-container">
                <div class="form-header">
                    <div class="course-avatar" id="avatarUpload">
                        <i class="fas fa-comments"></i>
                        <span><?php echo strtoupper(substr($chat['group_name'], 0, 2)); ?></span>
                    </div>
                    <div style="flex: 1;">
                        <h2 style="color: #2c3e50; margin-bottom: 8px;"><?php echo htmlspecialchars($chat['group_name']); ?></h2>
                        <p style="color: #f5b301; font-weight: 600; margin-bottom: 4px;"><?php echo htmlspecialchars($chat['course_code'] . ' - ' . $chat['course_name']); ?></p>
                        <?php if ($chat['class_section']): ?>
                            <p style="color: #6c757d;"><?php echo htmlspecialchars($chat['class_section']); ?></p>
                        <?php endif; ?>
                        <?php if ($userRole === 'admin'): ?>
                            <p style="color: #dc3545; font-size: 14px; margin-top: 8px;">
                                <i class="fas fa-shield-alt"></i> You are editing this chat as an administrator
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <form method="POST" id="editChatForm">
                    <div class="form-group">
                        <label for="group_name">Group Name *</label>
                        <input type="text" id="group_name" name="group_name" class="form-input" 
                               value="<?php echo htmlspecialchars($chat['group_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="class_section">Class Section</label>
                        <input type="text" id="class_section" name="class_section" class="form-input" 
                               value="<?php echo htmlspecialchars($chat['class_section'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="description">Group Description</label>
                        <textarea id="description" name="description" class="form-textarea"><?php echo htmlspecialchars($chat['description'] ?? ''); ?></textarea>
                    </div>

                    <!-- Settings Section -->
                    <div class="settings-section">
                        <h3 class="settings-title">
                            <i class="fas fa-cog"></i>
                            Chat Settings
                        </h3>
                        <div class="settings-grid">
                            <div class="setting-item">
                                <input type="checkbox" id="coadmin_allowed" name="coadmin_allowed" 
                                       <?php echo $chat['is_coadmin_allowed'] ? 'checked' : ''; ?>>
                                <div>
                                    <label for="coadmin_allowed">Allow Co-Admins</label>
                                    <div class="setting-description">Enable co-admins to manage members</div>
                                </div>
                            </div>
                            <div class="setting-item">
                                <input type="checkbox" id="approval_allowed" name="approval_allowed"
                                       <?php echo $chat['is_approval_allowed'] ? 'checked' : ''; ?>>
                                <div>
                                    <label for="approval_allowed">Require Approval</label>
                                    <div class="setting-description">Approve new members before joining</div>
                                </div>
                            </div>
                            <div class="setting-item">
                                <input type="checkbox" id="student_addition_allowed" name="student_addition_allowed"
                                       <?php echo $chat['is_student_addition_allowed'] ? 'checked' : ''; ?>>
                                <div>
                                    <label for="student_addition_allowed">Allow Student Addition</label>
                                    <div class="setting-description">Students can add other students</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="<?php echo $userRole === 'admin' ? 'faculty_chats.php' : 'faculty_chats.php'; ?>" class="cancel-btn">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="save-btn">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
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
            // Implement search functionality as needed
        });

        // Auto-hide success message after 5 seconds
        const successMessage = document.querySelector('.success-message');
        if (successMessage) {
            setTimeout(() => {
                successMessage.style.display = 'none';
            }, 5000);
        }

        // Auto-hide error message after 5 seconds
        const errorMessage = document.querySelector('.error-message');
        if (errorMessage) {
            setTimeout(() => {
                errorMessage.style.display = 'none';
            }, 5000);
        }

        // Avatar upload functionality (optional enhancement)
        document.getElementById('avatarUpload').addEventListener('click', function() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            input.onchange = function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const avatar = document.getElementById('avatarUpload');
                        avatar.style.backgroundImage = `url(${e.target.result})`;
                        avatar.style.backgroundSize = 'cover';
                        avatar.style.backgroundPosition = 'center';
                        avatar.innerHTML = '';
                    };
                    reader.readAsDataURL(file);
                }
            };
            input.click();
        });
    </script>
</body>
</html>