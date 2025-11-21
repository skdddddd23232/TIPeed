<?php
require 'db_connect.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['userid'];

// Get current user data
$user_query = "SELECT * FROM users WHERE userid = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

if (!$user) {
    header('Location: login.php');
    exit();
}

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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update profile information
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];
        $year_level = $_POST['year_level'];
        
        $update_query = "UPDATE users SET first_name = ?, last_name = ?, email = ?, year_level = ? WHERE userid = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("sssii", $first_name, $last_name, $email, $year_level, $user_id);
        
        if ($update_stmt->execute()) {
            $success = "Profile updated successfully!";
            // Refresh user data
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            $user = $user_result->fetch_assoc();
        } else {
            $error = "Error updating profile: " . $conn->error;
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 6) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $password_query = "UPDATE users SET password = ?, is_temp_password = 0 WHERE userid = ?";
                    $password_stmt = $conn->prepare($password_query);
                    $password_stmt->bind_param("si", $hashed_password, $user_id);
                    
                    if ($password_stmt->execute()) {
                        $success = "Password changed successfully!";
                    } else {
                        $error = "Error changing password: " . $conn->error;
                    }
                } else {
                    $error = "Password must be at least 6 characters long!";
                }
            } else {
                $error = "New passwords do not match!";
            }
        } else {
            $error = "Current password is incorrect!";
        }
    }
}

// Get user statistics
// Thread count
$thread_count_query = "SELECT COUNT(*) FROM threads WHERE user_id = ?";
$thread_count_stmt = $conn->prepare($thread_count_query);
$thread_count_stmt->bind_param("i", $user_id);
$thread_count_stmt->execute();
$thread_count_result = $thread_count_stmt->get_result();
$thread_count = $thread_count_result->fetch_row()[0];

// Likes count
$likes_count_query = "SELECT COUNT(*) FROM thread_vote WHERE user_id = ? AND vote = 'up'";
$likes_count_stmt = $conn->prepare($likes_count_query);
$likes_count_stmt->bind_param("i", $user_id);
$likes_count_stmt->execute();
$likes_count_result = $likes_count_stmt->get_result();
$likes_count = $likes_count_result->fetch_row()[0];

// Get user's liked threads
$liked_threads_query = "
    SELECT t.*, tv.created_at as liked_date 
    FROM threads t 
    INNER JOIN thread_vote tv ON t.thread_id = tv.thread_id 
    WHERE tv.user_id = ? AND tv.vote = 'up' 
    ORDER BY tv.created_at DESC 
    LIMIT 5
";
$liked_threads_stmt = $conn->prepare($liked_threads_query);
$liked_threads_stmt->bind_param("i", $user_id);
$liked_threads_stmt->execute();
$liked_threads_result = $liked_threads_stmt->get_result();
$liked_threads = $liked_threads_result->fetch_all(MYSQLI_ASSOC);

$communities_count_query = "SELECT COUNT(*) FROM community_members WHERE user_id = ?";
$communities_count_stmt = $conn->prepare($communities_count_query);
$communities_count_stmt->bind_param("i", $user_id);
$communities_count_stmt->execute();
$communities_count_result = $communities_count_stmt->get_result();
$communities_count = $communities_count_result->fetch_row()[0];

// Generate avatar initials
$avatar_initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile - TiPeed Forum</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
      padding: 20px;
      overflow-y: auto;
      background-color: #f9fafb;
      display: flex;
      flex-direction: column;
    }

    /* Profile Content */
    .profile-content {
      display: flex;
      gap: 20px;
      flex: 1;
    }

    .profile-left {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .profile-right {
      width: 300px;
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    /* Profile Card */
    .profile-card {
      background: white;
      border-radius: 12px;
      padding: 30px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      text-align: center;
    }

    .profile-header {
      margin-bottom: 20px;
    }

    .profile-avatar-large {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      background: #f5b301;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 40px;
      font-weight: bold;
      margin: 0 auto 15px;
    }

    

    .profile-n {
      font-size: 24px;
      font-weight: 600;
      margin-bottom: 5px;
    }

    .profile-username {
      color: #666;
      font-size: 16px;
      margin-bottom: 15px;
    }

    .profile-stats {
      display: flex;
      justify-content: space-around;
      margin: 20px 0;
      padding: 15px 0;
      border-top: 1px solid #eee;
      border-bottom: 1px solid #eee;
    }

    .stat {
      text-align: center;
    }

    .stat-value {
      font-size: 20px;
      font-weight: 600;
      color: #f5b301;
    }

    .stat-label {
      font-size: 14px;
      color: #666;
    }

    /* Profile Sections */
    .profile-section-card {
      background: white;
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 2px solid #f0f0f0;
    }

    .section-title {
      font-size: 18px;
      font-weight: 600;
      color: #333;
    }

    .edit-btn {
      background: none;
      border: none;
      color: #f5b301;
      cursor: pointer;
      font-size: 14px;
      font-weight: 500;
    }

    .edit-btn:hover {
      text-decoration: underline;
    }

    /* Form Styles */
    .form-group {
      margin-bottom: 15px;
    }

    .form-label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
      color: #333;
    }

    .form-input {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 14px;
    }

    .form-input:focus {
      outline: none;
      border-color: #f5b301;
    }

    .form-textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 14px;
      resize: vertical;
      min-height: 80px;
      font-family: inherit;
    }

    .form-textarea:focus {
      outline: none;
      border-color: #f5b301;
    }

    .form-checkbox {
      margin-right: 8px;
    }

    .form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 20px;
    }

    .btn {
      padding: 8px 16px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-weight: 500;
      transition: background 0.3s;
    }

    .btn-primary {
      background: #f5b301;
      color: white;
    }

    .btn-primary:hover {
      background: #e0a500;
    }

    .btn-secondary {
      background: #6c757d;
      color: white;
    }

    .btn-secondary:hover {
      background: #5a6268;
    }

    /* Thread List */
    .thread-list {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .thread-item {
      display: flex;
      padding: 15px;
      border-radius: 8px;
      background: #f8f9fa;
      transition: all 0.2s;
      cursor: pointer;
    }

    .thread-item:hover {
      background: #e9ecef;
      transform: translateY(-2px);
    }

    .thread-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: #f5b301;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      margin-right: 15px;
      flex-shrink: 0;
    }

    .thread-content {
      flex: 1;
    }

    .thread-title {
      font-weight: 500;
      margin-bottom: 4px;
    }

    .thread-meta {
      font-size: 12px;
      color: #666;
    }

    /* Tabs */
    .tabs {
      display: flex;
      border-bottom: 1px solid #ddd;
      margin-bottom: 20px;
    }

    .tab {
      padding: 10px 20px;
      cursor: pointer;
      font-weight: 500;
      color: #666;
      border-bottom: 2px solid transparent;
    }

    .tab.active {
      color: #f5b301;
      border-bottom: 2px solid #f5b301;
    }

    .tab-content {
      display: none;
    }

    .tab-content.active {
      display: block;
    }

    /* Modal */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
      z-index: 1000;
      align-items: center;
      justify-content: center;
    }

    .modal-content {
      background: white;
      border-radius: 8px;
      padding: 20px;
      width: 90%;
      max-width: 500px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
      padding-bottom: 10px;
      border-bottom: 1px solid #eee;
    }

    .modal-title {
      font-size: 18px;
      font-weight: 600;
      color: #333;
    }

    .close-modal {
      background: none;
      border: none;
      font-size: 20px;
      cursor: pointer;
      color: #666;
    }

    /* Messages */
    .alert {
      padding: 12px 15px;
      border-radius: 4px;
      margin-bottom: 20px;
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

    /* Responsive */
    @media (max-width: 1024px) {
      .profile-content {
        flex-direction: column;
      }
      
      .profile-right {
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <!-- Navbar -->
  <div class="navbar">
    <div class="logo">TIPeed</div>
    <div class="nav-links">
      <a href="admin_home.php" >Home</a>
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
    <!-- Left Sidebar -->
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

    <!-- Main Content -->
    <div class="main-content">
      <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
      <?php endif; ?>
      
      <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
      <?php endif; ?>
      
      <div class="profile-content">
        <!-- Left Column -->
        <div class="profile-left">
          <!-- Profile Card -->
          <div class="profile-card">
            <div class="profile-header">
              <div class="profile-avatar-large"><?php echo $avatar_initials; ?></div>
              <div class="profile-n"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
              <div class="profile-username">@<?php echo htmlspecialchars($user['student_id'] ?? 'user' . $user['userid']); ?></div>
            </div>
            
            <div class="profile-stats">
              <div class="stat">
                <div class="stat-value"><?php echo $thread_count; ?></div>
                <div class="stat-label">Threads</div>
              </div>
              <div class="stat">
                <div class="stat-value"><?php echo $likes_count; ?></div>
                <div class="stat-label">Likes</div>
              </div>
                <div class="stat">
                    <div class="stat-value"><?php echo $communities_count; ?></div>
                    <div class="stat-label">Communities</div>
                </div>
            </div>
            
            <button class="btn btn-primary" id="editProfileBtn">Edit Profile</button>
          </div>
          
          <!-- Liked Threads Section -->
          <div class="profile-section-card">
            <div class="section-header">
              <div class="section-title">Liked Threads</div>
            </div>
            <div class="thread-list" id="likedThreads">
              <?php if (empty($liked_threads)): ?>
                <p>No liked threads yet</p>
              <?php else: ?>
                <?php foreach ($liked_threads as $thread): ?>
                  <div class="thread-item">
                    <div class="thread-icon">
                      <i class="fas fa-thumbs-up"></i>
                    </div>
                    <div class="thread-content">
                      <div class="thread-title"><?php echo htmlspecialchars($thread['title']); ?></div>
                      <div class="thread-meta">Liked on <?php echo date('M j, Y', strtotime($thread['liked_date'])); ?></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
        
        <!-- Right Column -->
        <div class="profile-right">
          <!-- Account Information -->
          <div class="profile-section-card">
            <div class="section-header">
              <div class="section-title">Account Information</div>
              <button class="edit-btn" data-section="account">Edit</button>
            </div>
            <div class="section-content">
              <div class="form-group">
                <div class="form-label">Full Name</div>
                <div><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
              </div>
              <div class="form-group">
                <div class="form-label">Student ID</div>
                <div><?php echo htmlspecialchars($user['student_id'] ?? 'Not set'); ?></div>
              </div>
              <div class="form-group">
                <div class="form-label">Email</div>
                <div><?php echo htmlspecialchars($user['email']); ?></div>
              </div>
              <div class="form-group">
                <div class="form-label">Year Level</div>
                <div>
                  <?php 
                  $year_levels = ['', '1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year'];
                  echo isset($year_levels[$user['year_level']]) ? $year_levels[$user['year_level']] : 'Not set';
                  ?>
                </div>
              </div>
              <div class="form-group">
                <div class="form-label">Role</div>
                <div><?php echo ucfirst($user['role']); ?></div>
              </div>
            </div>
          </div>
          
          <!-- Security -->
          <div class="profile-section-card">
            <div class="section-header">
              <div class="section-title">Security</div>
            </div>
            <div class="section-content">
              <button class="btn btn-primary" id="changePasswordBtn">Change Password</button>
              <?php if ($user['is_temp_password']): ?>
                <div style="margin-top: 10px; color: #d9534f; font-size: 14px;">
                  <i class="fas fa-exclamation-triangle"></i> Please change your temporary password
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Profile Modal -->
  <div class="modal" id="editProfileModal">
    <div class="modal-content">
      <div class="modal-header">
        <div class="modal-title">Edit Profile</div>
        <button class="close-modal" id="closeProfileModal">&times;</button>
      </div>
      <form method="POST" id="profileForm">
        <input type="hidden" name="update_profile" value="1">
        
        <div class="form-group">
          <label class="form-label" for="first_name">First Name</label>
          <input type="text" id="first_name" name="first_name" class="form-input" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
        </div>
        
        <div class="form-group">
          <label class="form-label" for="last_name">Last Name</label>
          <input type="text" id="last_name" name="last_name" class="form-input" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
        </div>
        
        <div class="form-group">
          <label class="form-label" for="email">Email</label>
          <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>
        
        <div class="form-group">
          <label class="form-label" for="year_level">Year Level</label>
          <select id="year_level" name="year_level" class="form-input" required>
            <option value="1" <?php echo $user['year_level'] == 1 ? 'selected' : ''; ?>>1st Year</option>
            <option value="2" <?php echo $user['year_level'] == 2 ? 'selected' : ''; ?>>2nd Year</option>
            <option value="3" <?php echo $user['year_level'] == 3 ? 'selected' : ''; ?>>3rd Year</option>
            <option value="4" <?php echo $user['year_level'] == 4 ? 'selected' : ''; ?>>4th Year</option>
            <option value="5" <?php echo $user['year_level'] == 5 ? 'selected' : ''; ?>>5th Year</option>
          </select>
        </div>
        
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" id="cancelProfileEdit">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Change Password Modal -->
  <div class="modal" id="changePasswordModal">
    <div class="modal-content">
      <div class="modal-header">
        <div class="modal-title">Change Password</div>
        <button class="close-modal" id="closePasswordModal">&times;</button>
      </div>
      <form method="POST" id="passwordForm">
        <input type="hidden" name="change_password" value="1">
        
        <div class="form-group">
          <label class="form-label" for="currentPassword">Current Password</label>
          <input type="password" id="currentPassword" name="current_password" class="form-input" required>
        </div>
        
        <div class="form-group">
          <label class="form-label" for="newPassword">New Password</label>
          <input type="password" id="newPassword" name="new_password" class="form-input" required>
        </div>
        
        <div class="form-group">
          <label class="form-label" for="confirmPassword">Confirm New Password</label>
          <input type="password" id="confirmPassword" name="confirm_password" class="form-input" required>
        </div>
        
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" id="cancelPasswordChange">Cancel</button>
          <button type="submit" class="btn btn-primary">Change Password</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // DOM Elements
    const sidebar = document.getElementById('sidebar');
    const toggleSidebar = document.getElementById('toggleSidebar');
    const editProfileBtn = document.getElementById('editProfileBtn');
    const editProfileModal = document.getElementById('editProfileModal');
    const closeProfileModal = document.getElementById('closeProfileModal');
    const cancelProfileEdit = document.getElementById('cancelProfileEdit');
    const changePasswordBtn = document.getElementById('changePasswordBtn');
    const changePasswordModal = document.getElementById('changePasswordModal');
    const closePasswordModal = document.getElementById('closePasswordModal');
    const cancelPasswordChange = document.getElementById('cancelPasswordChange');
    const editButtons = document.querySelectorAll('.edit-btn');

    // Initialize profile page
    function initProfilePage() {
      // Event listeners
      toggleSidebar.addEventListener('click', () => sidebar.classList.toggle('expanded'));
      editProfileBtn.addEventListener('click', openEditProfileModal);
      closeProfileModal.addEventListener('click', closeEditProfileModal);
      cancelProfileEdit.addEventListener('click', closeEditProfileModal);
      changePasswordBtn.addEventListener('click', openChangePasswordModal);
      closePasswordModal.addEventListener('click', closeChangePasswordModal);
      cancelPasswordChange.addEventListener('click', closeChangePasswordModal);
      
      // Edit buttons for sections
      editButtons.forEach(button => {
        button.addEventListener('click', () => {
          const section = button.getAttribute('data-section');
          if (section === 'account') {
            openEditProfileModal();
          }
        });
      });
      
      // Close modals when clicking outside
      editProfileModal.addEventListener('click', (e) => {
        if (e.target === editProfileModal) {
          closeEditProfileModal();
        }
      });
      
      changePasswordModal.addEventListener('click', (e) => {
        if (e.target === changePasswordModal) {
          closeChangePasswordModal();
        }
      });
    }

    // Open edit profile modal
    function openEditProfileModal() {
      editProfileModal.style.display = 'flex';
    }

    // Close edit profile modal
    function closeEditProfileModal() {
      editProfileModal.style.display = 'none';
    }

    // Open change password modal
    function openChangePasswordModal() {
      changePasswordModal.style.display = 'flex';
    }

    // Close change password modal
    function closeChangePasswordModal() {
      changePasswordModal.style.display = 'none';
      document.getElementById('passwordForm').reset();
    }

    // Initialize the profile page when DOM is loaded
    document.addEventListener('DOMContentLoaded', initProfilePage);
  </script>
</body>
</html>