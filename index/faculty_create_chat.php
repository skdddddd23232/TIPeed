<?php
session_start();
include "db_connect.php";

if (!isset($_SESSION['userid'])) {
    header("Location: auth.php");
    exit;
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
  <title>Create Course Chat - TiPeed</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/NS.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; text-decoration: none;}
    body { background: #f9fafb; color: #222; text-decoration: none;}

    

    /* Right Sidebar */
    .friends-sidebar {
      width: 70px; 
      background: #fff; 
      border-left: 1px solid #eee; 
      transition: all 0.3s ease; 
      overflow: hidden; 
      height: 100%;
    }
    
    .friend-header {
      display: flex;
      align-items: center;
      margin-bottom: 15px;
      padding: 16px 20px;
      border-bottom: 1px solid #eee;
      cursor: pointer;
    }
    
    .friend-header i {
      color: #f5b301;
      margin-right: 10px;
    }
    
    .friends-sidebar.expanded { 
      width: 200px; 
      padding: 0px; 
    }
    
    .friend { 
      display: flex; 
      align-items: center; 
      margin-bottom: 12px; 
      cursor: pointer; 
      justify-content: center; 
    }
    
    .friend img { 
      width: 40px; 
      height: 40px; 
      border-radius: 50%; 
      margin-right: 12px; 
    }
    
    .friend span { 
      font-size: 14px; 
      font-weight: 500; 
    }
    
    .friends-sidebar:not(.expanded) h3,
    .friends-sidebar:not(.expanded) .friend span { 
      display: none; 
    }

    /* Main Content Area */
    .main-content {
      flex: 1;
      display: flex;
      flex-direction: column;
      background: #f5f6f7;
    }

    /* Course Chat Header */
    .course-chat-header {
      background: linear-gradient(135deg, #f5b301, #e0a500);
      color: white;
      padding: 20px;
      margin: 20px 20px 0;
      border-radius: 10px 10px 0 0;
      box-shadow: 0 4px 15px rgba(245, 179, 1, 0.3);
    }

    .course-chat-header h1 {
      font-size: 24px;
      font-weight: bold;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    /* Course Details Container */
    .course-details-container {
      flex: 1;
      padding: 20px;
      background: #fff;
      border-radius: 0 0 10px 10px;
      margin: 0 20px 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .course-header {
      display: flex;
      align-items: flex-start;
      gap: 20px;
      padding-bottom: 20px;
      border-bottom: 2px solid #f0f0f0;
    }

    .course-avatar {
      width: 200px;
      height: 200px;
      border-radius: 12px;
      background: #f5b301;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 24px;
      font-weight: bold;
      flex-shrink: 0;
      cursor: pointer;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .course-avatar:hover {
      background: #e0a500;
      transform: scale(1.05);
    }

    .course-avatar-input {
      display: none;
    }

    .course-info {
      flex: 1;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      font-weight: 600;
      color: #333;
      margin-bottom: 8px;
      font-size: 14px;
    }

    .form-input {
      width: 100%;
      padding: 12px;
      border: 2px solid #e9ecef;
      border-radius: 8px;
      font-size: 14px;
      transition: border-color 0.3s ease;
    }

    .form-input:focus {
      outline: none;
      border-color: #f5b301;
    }

    .form-select {
      width: 100%;
      padding: 12px;
      border: 2px solid #e9ecef;
      border-radius: 8px;
      font-size: 14px;
      background: white;
      cursor: pointer;
      transition: border-color 0.3s ease;
    }

    .form-select:focus {
      outline: none;
      border-color: #f5b301;
    }

    .form-textarea {
      width: 100%;
      padding: 12px;
      border: 2px solid #e9ecef;
      border-radius: 8px;
      font-size: 14px;
      resize: vertical;
      min-height: 100px;
      font-family: inherit;
      transition: border-color 0.3s ease;
    }

    .form-textarea:focus {
      outline: none;
      border-color: #f5b301;
    }

    /* Course Settings */
    .course-settings {
      background: #f8f9fa;
      padding: 25px;
      border-radius: 8px;
      border: 1px solid #e9ecef;
      margin-top: 20px;
    }

    .settings-title {
      font-size: 18px;
      font-weight: 600;
      color: #333;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .settings-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-bottom: 25px;
    }

    .setting-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 15px;
      background: white;
      border-radius: 8px;
      border: 1px solid #dee2e6;
      transition: all 0.3s ease;
    }

    .setting-item:hover {
      border-color: #f5b301;
    }

    .setting-item label {
      font-weight: 500;
      color: #495057;
      cursor: pointer;
    }

    .setting-item input[type="checkbox"] {
      width: 20px;
      height: 20px;
      accent-color: #f5b301;
    }

    /* Invite Section */
    .invite-section {
      display: flex;
      gap: 10px;
      align-items: center;
      margin-bottom: 25px;
    }

    .invite-link {
      flex: 1;
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 8px;
      background: #f8f9fa;
      font-family: monospace;
      font-size: 14px;
      color: #495057;
    }

    .generate-btn {
      padding: 12px 20px;
      background: #f5b301;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 500;
      transition: background 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .generate-btn:hover {
      background: #e0a500;
    }

    /* Co-Admin Section */
    .co-admin-section {
      margin-top: 25px;
      padding-top: 25px;
      border-top: 2px solid #e9ecef;
    }

    .co-admin-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .co-admin-form {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
    }

    .co-admin-input {
      flex: 1;
      padding: 12px;
      border: 2px solid #e9ecef;
      border-radius: 8px;
      font-size: 14px;
    }

    .co-admin-input:focus {
      outline: none;
      border-color: #f5b301;
    }

    .add-co-admin-btn {
      padding: 12px 20px;
      background: #17a2b8;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 500;
      transition: background 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .add-co-admin-btn:hover {
      background: #138496;
    }

    .co-admin-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
      max-height: 200px;
      overflow-y: auto;
    }

    .co-admin-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 15px;
      background: white;
      border-radius: 8px;
      border: 1px solid #dee2e6;
      transition: all 0.3s ease;
    }

    .co-admin-item:hover {
      border-color: #17a2b8;
    }

    .co-admin-info {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .co-admin-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: #17a2b8;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: bold;
      font-size: 14px;
      flex-shrink: 0;
    }

    .co-admin-details {
      display: flex;
      flex-direction: column;
    }

    .co-admin-name {
      font-weight: 600;
      color: #333;
      margin-bottom: 2px;
    }

    .co-admin-email {
      color: #666;
      font-size: 12px;
    }

    .remove-co-admin {
      background: #dc3545;
      color: white;
      border: none;
      border-radius: 6px;
      padding: 8px 12px;
      cursor: pointer;
      font-size: 12px;
      transition: background 0.3s;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .remove-co-admin:hover {
      background: #c82333;
    }

    /* Add Student Section */
    .add-student-section {
      margin-top: 25px;
      padding-top: 25px;
      border-top: 2px solid #e9ecef;
    }

    .add-student-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .add-student-form {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
    }

    .student-input {
      flex: 1;
      padding: 12px;
      border: 2px solid #e9ecef;
      border-radius: 8px;
      font-size: 14px;
    }

    .student-input:focus {
      outline: none;
      border-color: #f5b301;
    }

    .add-student-btn {
      padding: 12px 20px;
      background: #007bff;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 500;
      transition: background 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .add-student-btn:hover {
      background: #0056b3;
    }

    .students-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
      max-height: 250px;
      overflow-y: auto;
    }

    .student-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 15px;
      background: white;
      border-radius: 8px;
      border: 1px solid #dee2e6;
      transition: all 0.3s ease;
    }

    .student-item:hover {
      border-color: #007bff;
    }

    .student-info {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .student-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: #6c757d;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: bold;
      font-size: 14px;
      flex-shrink: 0;
    }

    .student-details {
      display: flex;
      flex-direction: column;
    }

    .student-name {
      font-weight: 600;
      color: #333;
      margin-bottom: 2px;
    }

    .student-email {
      color: #666;
      font-size: 12px;
    }

    .remove-student {
      background: #dc3545;
      color: white;
      border: none;
      border-radius: 6px;
      padding: 8px 12px;
      cursor: pointer;
      font-size: 12px;
      transition: background 0.3s;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .remove-student:hover {
      background: #c82333;
    }

    /* Save Button */
    .save-btn {
      padding: 15px 40px;
      background: #f5b301;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      font-size: 16px;
      transition: all 0.3s;
      margin-top: 30px;
      display: flex;
      align-items: center;
      gap: 10px;
      justify-content: center;
      width: 100%;
    }

    .save-btn:hover {
      background: #e0a500;
      transform: translateY(-2px);
    }

    .save-btn:disabled {
      background: #6c757d;
      cursor: not-allowed;
      transform: none;
    }

    /* Utility Classes */
    .avatar-preview {
      font-size: 14px;
      color: rgba(255, 255, 255, 0.9);
      margin-top: 8px;
      text-align: center;
    }

    .no-users {
      text-align: center;
      color: #6c757d;
      font-style: italic;
      padding: 20px;
      background: #f8f9fa;
      border-radius: 8px;
      border: 2px dashed #dee2e6;
    }

    .course-description-preview {
      background: #f8f9fa;
      padding: 12px;
      border-radius: 6px;
      border-left: 4px solid #f5b301;
      margin-top: 8px;
      font-size: 14px;
      color: #495057;
      line-height: 1.4;
    }

    .loading {
      color: #6c757d;
      font-style: italic;
      padding: 10px;
      text-align: center;
    }

    /* Scrollbar Styling */
    .co-admin-list::-webkit-scrollbar,
    .students-list::-webkit-scrollbar {
      width: 6px;
    }

    .co-admin-list::-webkit-scrollbar-track,
    .students-list::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }

    .co-admin-list::-webkit-scrollbar-thumb,
    .students-list::-webkit-scrollbar-thumb {
      background: #c1c1c1;
      border-radius: 10px;
    }

    .co-admin-list::-webkit-scrollbar-thumb:hover,
    .students-list::-webkit-scrollbar-thumb:hover {
      background: #a8a8a8;
    }

    /* Success Popup */
    .success-popup {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 1000;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
    }

    .success-popup.show {
      opacity: 1;
      visibility: visible;
    }

    .success-content {
      background: white;
      padding: 40px;
      border-radius: 12px;
      text-align: center;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
      max-width: 450px;
      width: 90%;
      transform: translateY(20px);
      transition: transform 0.3s ease;
    }

    .success-popup.show .success-content {
      transform: translateY(0);
    }

    .success-icon {
      font-size: 60px;
      color: #28a745;
      margin-bottom: 20px;
    }

    .success-title {
      font-size: 24px;
      font-weight: 600;
      color: #333;
      margin-bottom: 15px;
    }

    .success-message {
      color: #666;
      margin-bottom: 25px;
      line-height: 1.5;
    }

    .invite-link-display {
      background: #f8f9fa;
      padding: 12px;
      border-radius: 8px;
      border: 1px solid #dee2e6;
      margin-bottom: 25px;
      font-family: monospace;
      font-size: 14px;
      word-break: break-all;
    }

    .success-buttons {
      display: flex;
      gap: 15px;
      justify-content: center;
    }

    .copy-btn {
      padding: 12px 25px;
      background: #17a2b8;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 500;
      transition: background 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .copy-btn:hover {
      background: #138496;
    }

    .continue-btn {
      padding: 12px 25px;
      background: #f5b301;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 500;
      transition: background 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .continue-btn:hover {
      background: #e0a500;
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
      <!-- Create Course Chat Header -->
      <div class="course-chat-header">
        <h1>
          <i class="fas fa-plus-circle"></i>
          Create New Course Chat
        </h1>
      </div>

      <!-- Course Chat Details Section -->
      <div class="course-details-container">
        <!-- First Section: Course Header with Inputs -->
        <div class="course-header">
          <div class="course-avatar" id="avatarUpload">
            <input type="file" id="avatarInput" class="course-avatar-input" accept="image/*">
            <i class="fas fa-camera"></i>
            <div class="avatar-preview">Click to upload group photo</div>
          </div>
          <div class="course-info">
            <div class="form-group">
              <label for="groupName">Group Name *</label>
              <input type="text" id="groupName" class="form-input" placeholder="Enter group name (e.g., CS101 Study Group)" required>
            </div>
            <div class="form-group">
              <label for="courseCode">Course *</label>
              <select id="courseCode" class="form-select" required>
                <option value="">Loading your courses...</option>
              </select>
              <div class="course-info-display" id="courseInfoDisplay"></div>
            </div>
            <div class="form-group">
              <label for="classSection">Class Section</label>
              <input type="text" id="classSection" class="form-input" placeholder="e.g., Section A, Morning Batch">
            </div>
            <div class="form-group">
              <label for="description">Group Description</label>
              <textarea id="description" class="form-textarea" placeholder="Describe the purpose of this group..."></textarea>
            </div>
          </div>
        </div>

        <!-- Second Section: Course Settings -->
        <div class="course-settings">
          <div class="settings-title">
            <i class="fas fa-cog"></i>
            Group Management Settings
          </div>
          
          <div class="settings-grid">
            <div class="setting-item">
              <input type="checkbox" id="coAdmin" checked>
              <label for="coAdmin">Allow Co-Admins</label>
            </div>
            <div class="setting-item">
              <input type="checkbox" id="allowApproval" checked>
              <label for="allowApproval">Require Member Approval</label>
            </div>
            <div class="setting-item">
              <input type="checkbox" id="addStudent" checked>
              <label for="addStudent">Allow Student Addition</label>
            </div>
          </div>

          <div class="invite-section">
            <input type="text" class="invite-link" id="inviteLink" value="https://tipeed.com/join/" readonly>
            <button class="generate-btn" id="generateBtn">
              <i class="fas fa-link"></i> Generate Link
            </button>
          </div>

          <!-- Co-Admin Section -->
          <div class="co-admin-section">
            <div class="co-admin-header">
              <h3 class="settings-title">
                <i class="fas fa-user-shield"></i>
                Co-Admins
              </h3>
            </div>
            
            <div class="co-admin-form">
              <input type="text" class="co-admin-input" id="coAdminName" placeholder="Enter co-admin name">
              <input type="email" class="co-admin-input" id="coAdminEmail" placeholder="Enter co-admin email">
              <button class="add-co-admin-btn" id="addCoAdminBtn">
                <i class="fas fa-plus"></i> Add
              </button>
            </div>

            <div class="co-admin-list" id="coAdminList">
              <div class="no-users">No co-admins added yet</div>
            </div>
          </div>

          <!-- Add Student Section -->
          <div class="add-student-section">
            <div class="add-student-header">
              <h3 class="settings-title">
                <i class="fas fa-user-plus"></i>
                Add Students
              </h3>
            </div>
            
            <div class="add-student-form">
              <input type="text" class="student-input" id="studentName" placeholder="Enter student name">
              <input type="email" class="student-input" id="studentEmail" placeholder="Enter student email">
              <button class="add-student-btn" id="addStudentBtn">
                <i class="fas fa-plus"></i> Add
              </button>
            </div>

            <div class="students-list" id="studentsList">
              <div class="no-users">No students added yet</div>
            </div>
          </div>

          <button class="save-btn" id="saveBtn">
            <i class="fas fa-plus-circle"></i> Create Course Chat
          </button>
        </div>
      </div>
    </div>

    <!-- Success Popup -->
    <div class="success-popup" id="successPopup">
      <div class="success-content">
        <div class="success-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <h2 class="success-title">Course Chat Created Successfully!</h2>
        <p class="success-message">
          Your course chat "<span id="successGroupName"></span>" has been created successfully.
          You can now invite students and co-admins to join.
        </p>
        <div class="invite-link-display" id="successInviteLink"></div>
        <div class="success-buttons">
          <button class="copy-btn" id="copyInviteBtn">
            <i class="fas fa-copy"></i> Copy Link
          </button>
          <button class="continue-btn" id="continueBtn">
            <i class="fas fa-arrow-right"></i> Continue
          </button>
        </div>
      </div>
    </div>
    
  </div>

  <script>
    // Initialize the page
    document.addEventListener('DOMContentLoaded', function() {
      initializePage();
    });

    async function initializePage() {
      // Initialize sidebar toggles
      initializeSidebars();
      
      // Initialize avatar upload
      initializeAvatarUpload();
      
      // Load faculty courses
      await loadFacultyCourses();
      
      // Initialize form interactions
      initializeFormInteractions();
      
      // Initialize buttons
      initializeButtons();
      
      // Initialize success popup
      initializeSuccessPopup();
    }

    function initializeSidebars() {
      // Left sidebar toggle
      const sidebar = document.getElementById('sidebar');
      const toggleSidebar = document.getElementById('toggleSidebar');
      if (toggleSidebar) {
        toggleSidebar.addEventListener('click', () => sidebar.classList.toggle('expanded'));
      }

      // Right sidebar toggle
      const rightSidebar = document.getElementById('rightSidebar');
      const toggleRightSidebar = document.getElementById('toggleRightSidebar');
      if (toggleRightSidebar) {
        toggleRightSidebar.addEventListener('click', () => rightSidebar.classList.toggle('expanded'));
      }
    }

    function initializeAvatarUpload() {
      const avatarUpload = document.getElementById('avatarUpload');
      const avatarInput = document.getElementById('avatarInput');
      const avatarPreview = document.querySelector('.avatar-preview');

      avatarUpload.addEventListener('click', () => {
        avatarInput.click();
      });

      avatarInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
          const reader = new FileReader();
          reader.onload = function(e) {
            avatarUpload.style.backgroundImage = `url(${e.target.result})`;
            avatarUpload.style.backgroundSize = 'cover';
            avatarUpload.style.backgroundPosition = 'center';
            avatarUpload.innerHTML = '';
            avatarPreview.textContent = 'Photo uploaded';
            avatarPreview.style.color = 'rgba(255, 255, 255, 0.9)';
          }
          reader.readAsDataURL(file);
        }
      });
    }

    async function loadFacultyCourses() {
      const courseSelect = document.getElementById('courseCode');
      
      try {
        const response = await fetch('get_faculty_courses.php');
        const result = await response.json();
        
        if (result.status === 'success') {
          courseSelect.innerHTML = '<option value="">Select a course...</option>';
          
          result.data.forEach(course => {
            const option = document.createElement('option');
            option.value = course.course_id;
            option.textContent = `${course.course_code} - ${course.course_name}`;
            option.setAttribute('data-description', course.course_description || 'No description available');
            courseSelect.appendChild(option);
          });
          
          if (result.data.length === 0) {
            courseSelect.innerHTML = '<option value="">No courses assigned to you</option>';
          }
        } else {
          throw new Error(result.message);
        }
      } catch (error) {
        console.error('Error loading courses:', error);
        courseSelect.innerHTML = '<option value="">Error loading courses</option>';
      }
    }

    function initializeFormInteractions() {
      const courseSelect = document.getElementById('courseCode');
      const courseInfoDisplay = document.getElementById('courseInfoDisplay');
      const descriptionTextarea = document.getElementById('description');
      const groupNameInput = document.getElementById('groupName');

      // Course selection handler
      courseSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const courseDescription = selectedOption.getAttribute('data-description');
        
        if (courseDescription && this.value) {
          courseInfoDisplay.innerHTML = `
            <div class="course-description-preview">${courseDescription}</div>
          `;
          
          // Auto-fill description if empty
          if (!descriptionTextarea.value.trim()) {
            descriptionTextarea.value = `Course group for ${selectedOption.textContent}. ${courseDescription}`;
          }
          
          // Auto-fill group name if empty
          if (!groupNameInput.value.trim()) {
            const courseName = selectedOption.textContent.split(' - ')[1] || selectedOption.textContent;
            groupNameInput.value = `${courseName} Study Group`;
          }
        } else {
          courseInfoDisplay.innerHTML = '';
        }
      });

      // Enter key handlers for forms
      setupEnterKeyHandlers();
    }

    function setupEnterKeyHandlers() {
      // Co-admin form enter key
      document.getElementById('coAdminName').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          document.getElementById('coAdminEmail').focus();
        }
      });

      document.getElementById('coAdminEmail').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          addCoAdmin();
        }
      });

      // Student form enter key
      document.getElementById('studentName').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          document.getElementById('studentEmail').focus();
        }
      });

      document.getElementById('studentEmail').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          addStudent();
        }
      });
    }

    function initializeButtons() {
      // Generate invite link
      document.getElementById('generateBtn').addEventListener('click', generateInviteLink);
      
      // Add co-admin
      document.getElementById('addCoAdminBtn').addEventListener('click', addCoAdmin);
      
      // Add student
      document.getElementById('addStudentBtn').addEventListener('click', addStudent);
      
      // Save course chat
      document.getElementById('saveBtn').addEventListener('click', saveCourseChat);
    }

    function initializeSuccessPopup() {
      // Copy invite link button
      document.getElementById('copyInviteBtn').addEventListener('click', function() {
        const inviteLink = document.getElementById('successInviteLink').textContent;
        navigator.clipboard.writeText(inviteLink).then(() => {
          const copyBtn = document.getElementById('copyInviteBtn');
          const originalText = copyBtn.innerHTML;
          copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
          setTimeout(() => {
            copyBtn.innerHTML = originalText;
          }, 2000);
        });
      });

      // Continue button
      document.getElementById('continueBtn').addEventListener('click', function() {
        window.location.href = 'faculty_chats.php';
      });
    }

    function generateInviteLink() {
      const groupName = document.getElementById('groupName').value.trim() || 'study-group';
      const courseCode = document.getElementById('courseCode').value.trim() || 'course';
      
      const urlFriendlyName = groupName.toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)+/g, '');
      
      const randomId = Math.random().toString(36).substr(2, 8);
      const inviteLink = `https://tipeed.com/join/${urlFriendlyName}-${randomId}`;
      
      document.getElementById('inviteLink').value = inviteLink;
      
      // Copy to clipboard and show feedback
      const inviteInput = document.getElementById('inviteLink');
      inviteInput.select();
      document.execCommand('copy');
      
      const generateBtn = document.getElementById('generateBtn');
      const originalText = generateBtn.innerHTML;
      generateBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
      setTimeout(() => {
        generateBtn.innerHTML = originalText;
      }, 2000);
    }

    function addCoAdmin() {
      const nameInput = document.getElementById('coAdminName');
      const emailInput = document.getElementById('coAdminEmail');
      const name = nameInput.value.trim();
      const email = emailInput.value.trim();

      if (!name || !email) {
        alert('Please enter both co-admin name and email');
        return;
      }

      if (!isValidEmail(email)) {
        alert('Please enter a valid email address');
        return;
      }

      const coAdminList = document.getElementById('coAdminList');
      
      // Remove "no users" message if it exists
      const noUsersMsg = coAdminList.querySelector('.no-users');
      if (noUsersMsg) {
        noUsersMsg.remove();
      }

      // Create co-admin item
      const coAdminItem = document.createElement('div');
      coAdminItem.className = 'co-admin-item';
      
      const initials = name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
      
      coAdminItem.innerHTML = `
        <div class="co-admin-info">
          <div class="co-admin-avatar">${initials}</div>
          <div class="co-admin-details">
            <div class="co-admin-name">${name}</div>
            <div class="co-admin-email">${email}</div>
          </div>
        </div>
        <button class="remove-co-admin" onclick="removeCoAdmin(this)">
          <i class="fas fa-times"></i> Remove
        </button>
      `;

      coAdminList.appendChild(coAdminItem);

      // Clear inputs
      nameInput.value = '';
      emailInput.value = '';
      nameInput.focus();
    }

    function addStudent() {
      const nameInput = document.getElementById('studentName');
      const emailInput = document.getElementById('studentEmail');
      const name = nameInput.value.trim();
      const email = emailInput.value.trim();

      if (!name || !email) {
        alert('Please enter both student name and email');
        return;
      }

      if (!isValidEmail(email)) {
        alert('Please enter a valid email address');
        return;
      }

      const studentsList = document.getElementById('studentsList');
      
      // Remove "no users" message if it exists
      const noUsersMsg = studentsList.querySelector('.no-users');
      if (noUsersMsg) {
        noUsersMsg.remove();
      }

      // Create student item
      const studentItem = document.createElement('div');
      studentItem.className = 'student-item';
      
      const initials = name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
      
      studentItem.innerHTML = `
        <div class="student-info">
          <div class="student-avatar">${initials}</div>
          <div class="student-details">
            <div class="student-name">${name}</div>
            <div class="student-email">${email}</div>
          </div>
        </div>
        <button class="remove-student" onclick="removeStudent(this)">
          <i class="fas fa-times"></i> Remove
        </button>
      `;

      studentsList.appendChild(studentItem);

      // Clear inputs
      nameInput.value = '';
      emailInput.value = '';
      nameInput.focus();
    }

    function removeCoAdmin(button) {
      const coAdminItem = button.closest('.co-admin-item');
      coAdminItem.remove();
      
      const coAdminList = document.getElementById('coAdminList');
      if (coAdminList.children.length === 0) {
        coAdminList.innerHTML = '<div class="no-users">No co-admins added yet</div>';
      }
    }

    function removeStudent(button) {
      const studentItem = button.closest('.student-item');
      studentItem.remove();
      
      const studentsList = document.getElementById('studentsList');
      if (studentsList.children.length === 0) {
        studentsList.innerHTML = '<div class="no-users">No students added yet</div>';
      }
    }

    function isValidEmail(email) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return emailRegex.test(email);
    }

    async function saveCourseChat() {
      const groupName = document.getElementById('groupName').value.trim();
      const courseId = document.getElementById('courseCode').value;
      const classSection = document.getElementById('classSection').value.trim();
      const description = document.getElementById('description').value.trim();
      
      // Get settings
      const coAdminAllowed = document.getElementById('coAdmin').checked;
      const approvalAllowed = document.getElementById('allowApproval').checked;
      const studentAdditionAllowed = document.getElementById('addStudent').checked;
      
      // Get co-admins and students
      const coAdmins = [];
      const students = [];
      
      document.querySelectorAll('#coAdminList .co-admin-item').forEach(item => {
        const name = item.querySelector('.co-admin-name').textContent.trim();
        const email = item.querySelector('.co-admin-email').textContent.trim();
        coAdmins.push({ name, email });
      });
      
      document.querySelectorAll('#studentsList .student-item').forEach(item => {
        const name = item.querySelector('.student-name').textContent.trim();
        const email = item.querySelector('.student-email').textContent.trim();
        students.push({ name, email });
      });
      
      // Validation
      if (!groupName) {
        alert('Please enter a Group Name');
        document.getElementById('groupName').focus();
        return;
      }
      
      if (!courseId) {
        alert('Please select a Course');
        document.getElementById('courseCode').focus();
        return;
      }
      
      const saveBtn = document.getElementById('saveBtn');
      const originalText = saveBtn.innerHTML;
      
      // Show loading state
      saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Course Chat...';
      saveBtn.disabled = true;
      
      try {
        const response = await fetch('create_course_chat.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            groupName,
            courseId,
            classSection,
            description,
            coAdmin: coAdminAllowed,
            allowApproval: approvalAllowed,
            addStudent: studentAdditionAllowed,
            coAdmins,
            students
          })
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
          // Show success popup
          showSuccessPopup(groupName, result.inviteLink || document.getElementById('inviteLink').value);
          
          // Reset button
          saveBtn.innerHTML = originalText;
          saveBtn.disabled = false;
          
        } else {
          throw new Error(result.message);
        }
      } catch (error) {
        console.error('Error:', error);
        alert('Error creating course chat: ' + error.message);
        
        // Reset button
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
      }
    }

    function showSuccessPopup(groupName, inviteLink) {
      // Set the success popup content
      document.getElementById('successGroupName').textContent = groupName;
      document.getElementById('successInviteLink').textContent = inviteLink;
      
      // Show the popup
      const popup = document.getElementById('successPopup');
      popup.classList.add('show');
      
      // Update the main invite link field
      document.getElementById('inviteLink').value = inviteLink;
    }

    // Make functions globally available for onclick handlers
    window.removeCoAdmin = removeCoAdmin;
    window.removeStudent = removeStudent;
  </script>
</body>
</html>