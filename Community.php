<?php
// --- AUTO CLEAR BROWSER CACHE ---
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache");
header("Expires: 0");

session_start();
require 'db_connect.php';

// Logged in user (to check membership)
$user_id = $_SESSION['userid'] ?? 0;

// Get only approved communities
// but pending communities only for their creator or admins
$sql = "SELECT 
            c.communities_id, c.name, c.description, c.category, c.privacy, c.status, c.created_at, 
            CONCAT(u.first_name, ' ', u.last_name) AS creator_name,
            (SELECT COUNT(*) FROM community_members cm WHERE cm.community_id = c.communities_id) AS member_count,
            EXISTS(
                SELECT 1 FROM community_members cm2 
                WHERE cm2.community_id = c.communities_id AND cm2.user_id = ?
            ) AS is_member
        FROM communities c
        JOIN users u ON c.created_by = u.userid
        WHERE 
            c.status = 'approved'
            OR (
                c.status = 'pending' 
                AND (
                    c.created_by = ? 
                    OR EXISTS (SELECT 1 FROM users WHERE userid = ? AND role = 'admin')
                )
            )
        ORDER BY c.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$communities = [];
while ($row = $result->fetch_assoc()) {
    $communities[] = $row;
}



$studentName   = $_SESSION['first_name'] . " " . $_SESSION['last_name'];
$studentCourse = isset($_SESSION['course']) ? $_SESSION['course'] : "No course assigned"; 

$currentUserRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';

if ($currentUserRole === 'admin') {
    $homePage = 'admin_home.php';
} else if ($currentUserRole === 'teacher') {
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
  <title>TiPeed Forum</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css'); ?>">
  <script src="assets/js/app.js?v=<?= filemtime('assets/js/app.js'); ?>"></script>
  <link rel="stylesheet" href="../css/NS.css">
  <style>
    * { text-decoration: none; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;}
    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f9fafb;
      color: #222;
    }

    /* Navbar */
    .navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px 40px;
      border-bottom: 1px solid #eee;
      background: #000000;
      position: sticky;
      top: 0;
      z-index: 10;
    }

    .logo {
      font-size: 26px;
      font-weight: bold;
      color: #f5b301;
    }

    .nav-links {
      display: flex;
      gap: 25px;
    }

    .nav-links a {
      text-decoration: none;
      color: #ddd;
      font-weight: 500;
      transition: color 0.3s;
    }

    .nav-links a:hover {
      color: #fff;
    }

    .nav-links a.active {
      border-bottom: 2px solid #fff;
      padding-bottom: 4px;
      color: #fff;
    }

    /* Glassy Search Bar */
    .search-bar {
      display: flex;
      background: rgba(255, 255, 255, 0.15);
      border-radius: 20px;
      padding: 5px 15px;
      align-items: center;
      transition: all 0.3s ease;
      width: 50%;
    }

    .search-bar:focus-within {
      background: rgba(255, 255, 255, 0.25);
      box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.5);
    }

    .search-bar i {
      margin-right: 10px;
      color: rgba(255, 255, 255, 0.8);
    }

    .search-bar input {
      background: transparent;
      border: none;
      color: white;
      padding: 8px 0;
      width: 100%;
      outline: none;
    }

    .search-bar input::placeholder {
      color: rgba(255, 255, 255, 0.7);
    }

    /* Layout */
    .layout {
      display: flex;
      height: calc(100vh - 70px);
    }

    /* Sidebar */
        .sidebar {
      text-decoration: none;
      width: 70px;
      background: #fff;
      border-right: 1px solid #eee;
      transition: all 0.3s ease;
      overflow: hidden;
    }

    .sidebar.expanded {
      width: 260px;
    }

    .profile-section {
      display: flex;
      align-items: center;
      padding: 8px 10px;
      border-bottom: 1px solid #eee;
      cursor: pointer;
      transition: padding 0.3s ease;
    }

    .profile-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: #f5b301;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-weight: bold;
      margin-right: 12px;
      flex-shrink: 0;
      user-select: none;
    }

    .profile-info {
      flex: 1;
      transition: opacity 0.3s ease;
      overflow: hidden;
      white-space: nowrap;
      opacity: 1;
      transition: opacity 0.3s ease;
      padding-left: 8px;
      user-select: none;
    }

    

    .menu-section {
      padding: 2px 0;
      text-decoration: none;
    }

    .menu-item {
      text-decoration: none;
      display: flex;
      align-items: center;
      padding: 12px 20px;
      color: #1c1c1c;
      cursor: pointer;
      transition: all 0.2s;
      white-space: nowrap;
    }

    .menu-text {
      text-decoration: none;
      transition: opacity 0.3s ease;
      user-select: none;
    }

    .menu-item:hover {
      background-color: #4d4d4d;
    }

     .menu-icon {
      width: 20px;
      margin-right: 12px;
      text-align: center;
      color: #878a8c;
      font-size: 18px;
    }

    /* Menu Link*/
        /*.menu-section a.menu-item {
        display: flex;
        align-items: center;
        padding: 12px 20px;
        color: #1c1c1c;
        text-decoration: none; 
        transition: all 0.2s;
        }

        .menu-section a.menu-item:hover {
        background-color: #f6f7f8;
        }

        .menu-section a.menu-item .menu-icon {
        width: 20px;
        margin-right: 12px;
        text-align: center;
        color: #878a8c;
        font-size: 18px;
        }

        .menu-section a.menu-item .menu-text {
        opacity: 1;
        transition: opacity 0.3s ease;
        }
        */

    /*.sidebar:not(.expanded)*/
        .sidebar:not(.expanded) .profile-info {
        opacity: 0;
        width: 0;
        padding: 0;
        }

        .sidebar:not(.expanded) .profile-section {
        padding: 4px 6px;       /* reduce overall padding */
        justify-content: flex-start;
        }

        /*.sidebar:not(.expanded) .profile-avatar {
        width: 30px;            
        height: 30px;
        margin-right: 0;        
        }*/

        .sidebar:not(.expanded) .profile-info {
        opacity: 0; 
        }

        .sidebar:not(.expanded) .menu-text {
        opacity: 0;
        }

    /* Main Content */
    .main-content {
      flex: 1;
      padding: 20px;
      overflow-y: auto;
    }

    .right-sidebar {
      position: fixed;
      top: 83px;
      right: 0;
      width: 70px;
      height: calc(100vh - 60px);
      background: #fff;
      border-left: 1px solid #eee;
      transition: all 0.3s ease;
      overflow: hidden;
      z-index: 999;
    }

    .right-sidebar.expanded {
      width: 250px;
    }

    .friend-header {
      display: flex;
      align-items: center;
      padding: 16px 20px;
      border-bottom: 1px solid #eee;
      cursor: pointer;
      white-space: nowrap;
    }

    .friend-header i {
      font-size: 20px;
      color: #f5b301;
      margin-right: 10px;
    }

    .friend-title {
      transition: opacity 0.3s ease;
      overflow: hidden;
    }

    .right-sidebar:not(.expanded) .friend-title {
      opacity: 0;
      width: 0;
    }

    .friend-list {
      padding: 15px 20px;
    }

    .friend {
      display: flex;
      align-items: center;
      margin-bottom: 12px;
      gap: 10px;
    }

    .friend img {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
    }

    .friend span {
      font-size: 14px;
      color: #444;
      white-space: nowrap;
    }

    .right-sidebar:not(.expanded) .friend span {
      display: none;
    }

    /* Trending */
    .trending {
      padding: 50px 20px;
      max-width: 1400px;
      margin: 0 auto;
    }

    .trending h2 {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      font-size: 26px;
      margin-bottom: 35px;
      font-weight: 700;
      color: #333;
      justify-content: center;
    }

    .cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 20px;
    }

    .card {
      position: relative;
      border-radius: 18px;
      overflow: hidden;
      cursor: pointer;
      background: #fff;
      box-shadow: 0 4px 14px rgba(0,0,0,0.1);
      transition: transform 0.35s ease, box-shadow 0.35s ease;
    }

    .card:hover {
      transform: translateY(-8px) scale(1.02);
      box-shadow: 0 10px 22px rgba(0,0,0,0.18);
    }

    .card img {
      width: 100%;
      height: 200px;
      object-fit: cover;
      display: block;
      transition: transform 0.5s ease;
    }

    .card:hover img {
      transform: scale(1.08);
    }

    .card-content {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      padding: 18px;
      background: linear-gradient(to top, rgba(0,0,0,0.75), transparent);
      color: #fff;
      text-align: left;
    }

    .card-content h3 {
      margin: 0 0 8px;
      font-size: 17px;
      font-weight: 600;
      line-height: 1.4;
    }

    .card-content p {
      font-size: 13px;
      margin: 0;
      opacity: 0.9;
    }

    /* Create Community Button */
    .create-community-btn {
      position: fixed;
      bottom: 30px;
      right: 30px;
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: #f5b301;
      color: white;
      border: none;
      font-size: 24px;
      cursor: pointer;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      transition: all 0.3s ease;
      z-index: 1000;
    }

    .create-community-btn:hover {
      transform: scale(1.1);
      box-shadow: 0 6px 16px rgba(0,0,0,0.2);
    }

    /* Create Community Popup */
    .popup {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 2000;
      justify-content: center;
      align-items: center;
    }

    .popup-content {
      background: white;
      border-radius: 12px;
      width: 90%;
      max-width: 500px;
      padding: 25px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .popup-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .popup-header h2 {
      margin: 0;
      color: #333;
    }

    .close-btn {
      background: none;
      border: none;
      font-size: 24px;
      cursor: pointer;
      color: #777;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
      color: #444;
    }

    .form-group input, .form-group textarea, .form-group select {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-family: inherit;
      font-size: 14px;
    }

    .form-group textarea {
      min-height: 100px;
      resize: vertical;
    }

    .form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 20px;
    }

    .btn {
      padding: 10px 20px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 500;
      transition: all 0.2s;
    }

    .btn-primary {
      background: #f5b301;
      color: white;
    }

    .btn-primary:hover {
      background: #e0a300;
    }

    .btn-secondary {
      background: #eee;
      color: #333;
    }

    .btn-secondary:hover {
      background: #ddd;
    }

    /* Community Cards */
    .communities-section {
      margin-top: 40px;
    }

    .communities-section h2 {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 26px;
      margin-bottom: 20px;
      font-weight: 700;
      color: #333;
    }

    .community-cards {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 20px;
    }

    .community-card {
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .community-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 16px rgba(0,0,0,0.12);
    }

    .community-banner {
      height: 120px;
      background: linear-gradient(135deg, #f5b301, #ffcc33);
      position: relative;
    }

    .community-avatar {
      width: 70px;
      height: 70px;
      border-radius: 50%;
      background: white;
      position: absolute;
      bottom: -35px;
      left: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
      color: #f5b301;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .community-info {
      padding: 50px 20px 20px;
    }

    .community-info h3 {
      margin: 0 0 10px;
      font-size: 18px;
      color: #333;
    }

    .community-info p {
      margin: 0 0 15px;
      font-size: 14px;
      color: #666;
      line-height: 1.4;
    }

    .community-meta {
      display: flex;
      justify-content: space-between;
      font-size: 12px;
      color: #888;
    }

    /* Admin three-dot menu */
    .community-options {
      position: absolute;
      top: 10px;
      right: 10px;
    }

    .options-icon {
      font-size: 18px;
      color: white;
      cursor: pointer;
      padding: 5px;
    }

    .options-menu {
      display: none;
      position: absolute;
      right: 0;
      top: 25px;
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      padding: 5px 0;
      z-index: 10;
    }

    .options-menu form {
      margin: 0;
    }

    .options-menu .delete-btn {
      background: none;
      border: none;
      color: #e63946;
      font-size: 14px;
      padding: 8px 15px;
      width: 100%;
      text-align: left;
      cursor: pointer;
    }

    .options-menu .delete-btn:hover {
      background: #f8d7da;
    }

    /* Fix Community Action Buttons */
    .community-action {
      margin-top: 10px;
    }

    .pending-status {
      position: absolute;
      top: 10px;
      left: 10px;
      background-color: #fff;
      color: #a05913ff;
      padding: 5px 10px;
      font-size: 14px;
      font-weight: bold;
      border-radius: 5px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    .btn-chat {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      margin-top: 10px;
      background: #fbc321;
      color: #3c3c3cff;
      border-radius: 6px;
      padding: 10px 8px;
      font-weight: 500;
      text-decoration: none;
      transition: all 0.3s ease;
      border: none;
    }

    .btn-chat:hover {
      background: #747474ff;
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(0,0,0,0.15);
      color: #fbc321;
    }

    .btn-primary i, .btn-chat i {
      font-size: 14px;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .navbar { flex-wrap: wrap; padding: 15px 20px; }
      .search-bar { order: 3; margin: 10px 0; width: 100%; }
      .nav-links { order: 2; flex: 1; justify-content: center; }
      .right { order: 1; }
      .trending { padding: 30px 15px; }
      .cards { gap: 20px; }
      .trending h2 { font-size: 22px; }
      .create-community-btn { bottom: 20px; right: 20px; }
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

    <!-- Main Content -->
    <div class="main-content">


      <!-- Communities Section -->
      <div class="communities-section">
        <h2><i class="fas fa-users"></i> Communities</h2>
        <div class="community-cards" id="communityList">
          <!-- Communities will be added here dynamically -->

          <?php foreach($communities as $community): ?>
            <div class="community-card">
               <div class="community-banner">
                  <?php if ($community['status'] == 'pending'): ?>
                    <div class="pending-status">Pending</div> <!-- This is the text indicator -->
                  <?php endif; ?>
                <div class="community-avatar"><i class="fas fa-users"></i></div>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                  <div class="community-options">
                    <i class="fas fa-ellipsis-v options-icon"></i>
                    <div class="options-menu">
                      <form method="POST" action="delete_community.php" onsubmit="return confirm('Delete this community?')">
                        <input type="hidden" name="community_id" value="<?= $community['communities_id'] ?>">
                        <button type="submit" class="delete-btn"><i class="fas fa-trash"></i> Delete</button>
                      </form>
                    </div>
                  </div>
                <?php endif; ?>

              </div>
              <div class="community-info">
                <h3><?= htmlspecialchars($community['name']) ?></h3>
                <p><?= htmlspecialchars($community['description']) ?></p>
                <div class="community-meta">
                  <span><i class="fas fa-tag"></i> <?= htmlspecialchars($community['category']) ?></span>
                  <span><i class="fas fa-users"></i> <?= $community['member_count'] ?> members</span>
                </div>
                <div class="community-meta">
                  <span><i class="fas fa-user"></i> Created by <?= htmlspecialchars($community['creator_name']) ?></span>
                  <span><i class="fas fa-calendar"></i> <?= date("M d, Y", strtotime($community['created_at'])) ?></span>
                </div>
                <?php if (!$community['is_member']): ?>
                  <?php if ($community['privacy'] === 'Public'): ?>
                    <form method="POST" action="join_community.php" class="community-action">
                      <input type="hidden" name="community_id" value="<?= $community['communities_id'] ?>">
                      <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Join
                      </button>
                    </form>
                    <?php else: ?>
                    <button class="btn btn-primary join-private" data-id="<?= $community['communities_id'] ?>">
                      <i class="fas fa-lock"></i> Join Private
                    </button>
                    <?php endif; ?>
                  <?php else: ?>
                  <a href="coursechat.php?community_id=<?= $community['communities_id'] ?>" class="btn btn-chat">
                    <i class="fas fa-comments"></i> Enter Chat
                  </a>
                <?php endif; ?>

              </div>
            </div>
          <?php endforeach; ?>

        </div>
      </div>
    </div>
  </div>

  <!-- Create Community Button -->
  <button class="create-community-btn" id="createCommunityBtn">
    <i class="fas fa-plus"></i>
  </button>

  <!-- Create Community Popup -->
  <div class="popup" id="createCommunityPopup">
    <div class="popup-content">
      <div class="popup-header">
        <h2>Create a Community</h2>
        <button class="close-btn" id="closePopup">&times;</button>
      </div>
      <form id="communityForm">
        <div class="form-group">
          <label for="communityName">Community Name</label>
          <input type="text" id="communityName" name="name" required>
        </div>
        <div class="form-group">
          <label for="communityDescription">Description</label>
          <textarea id="communityDescription" name="description" required></textarea>
        </div>
        <div class="form-group">
          <label for="communityCategory">Category</label>
          <select id="communityCategory" name="category" required>
            <option value="">Select a category</option>
            <option value="Education">Education</option>
            <option value="Technology">Technology</option>
            <option value="Gaming">Gaming</option>
            <option value="Sports">Sports</option>
            <option value="Entertainment">Entertainment</option>
            <option value="Health">Health</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div class="form-group">
          <label for="communityPrivacy">Privacy</label>
          <select id="communityPrivacy" name="privacy" required>
            <option value="Public">Public</option>
            <option value="Private">Private</option>
          </select>
        </div>
        <div class="form-group" id="accessCodeGroup" style="display:none;">
          <label for="communityAccessCode">Access Code (for Private)</label>
          <input type="text" id="communityAccessCode" name="access_code">
        </div>
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Community</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Private Community Access Popup -->
    <div class="popup" id="privateAccessPopup">
      <div class="popup-content">
        <div class="popup-header">
          <h2>Enter Access Code</h2>
          <button class="close-btn" id="closePrivatePopup">&times;</button>
        </div>

        <form id="privateAccessForm">
          <input type="hidden" id="privateCommunityId" name="community_id">
          <div class="form-group">
            <label for="privateAccessCode">Access Code</label>
            <input type="text" id="privateAccessCode" name="access_code" required>
          </div>

          <!-- Notification message -->
          <div id="privateAccessMessage" style="color:red; margin-bottom:10px; display:none;"></div>

          <div class="form-actions">
            <button type="button" class="btn btn-secondary" id="cancelPrivateBtn">Cancel</button>
            <button type="submit" class="btn btn-primary">Join</button>
          </div>
        </form>
      </div>
    </div>
    
    
    
    <script>
      
      document.querySelectorAll('.options-icon').forEach(icon => {
        icon.addEventListener('click', (e) => {
          const menu = e.target.closest('.community-options').querySelector('.options-menu');
          menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
        });
      });
      
      window.addEventListener('click', (e) => {
        if (!e.target.closest('.community-options')) {
          document.querySelectorAll('.options-menu').forEach(menu => menu.style.display = 'none');
        }
      });
      // WHICH PART SHOULD I DELETE TELL ME WHERE LINE OF CODE
      // Sidebar toggle functionality
      const sidebar = document.getElementById('sidebar');
      const toggleSidebar = document.getElementById('toggleSidebar');
      if(toggleSidebar && sidebar) {
        toggleSidebar.addEventListener('click', () => sidebar.classList.toggle('expanded'));
      }


      
      // Create Community functionality
      const createCommunityBtn = document.getElementById('createCommunityBtn');
      const createCommunityPopup = document.getElementById('createCommunityPopup');
      const closePopupBtn = document.getElementById('closePopup');
      const cancelBtn = document.getElementById('cancelBtn');
      const communityForm = document.getElementById('communityForm');
      const communityList = document.getElementById('communityList');
      
      // Open popup
      createCommunityBtn.addEventListener('click', () => {
        createCommunityPopup.style.display = 'flex';
      });
      
      document.querySelectorAll('.join-private').forEach(btn => {
        btn.addEventListener('click', () => {
          privateCommunityIdInput.value = btn.dataset.id;
          privateAccessCodeInput.value = '';
          privateAccessPopup.style.display = 'flex';
        });
      });
      // Close popup
      function closePopup() {
        createCommunityPopup.style.display = 'none';
        communityForm.reset();
      }
      
      closePopupBtn.addEventListener('click', closePopup);
      cancelBtn.addEventListener('click', closePopup);
      
    communityForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(communityForm);
      
      fetch('community_handler.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert("Community created successfully!");
          window.location.reload(); // reload to see from DB
        } else {
          alert(data.message || "Error creating community");
        }
      })
      .catch(err => {
        console.error(err);
        alert("Something went wrong.");
      });
    });
    
    const privateAccessPopup = document.getElementById('privateAccessPopup');
    const closePrivatePopupBtn = document.getElementById('closePrivatePopup');
    const cancelPrivateBtn = document.getElementById('cancelPrivateBtn');
    const privateAccessForm = document.getElementById('privateAccessForm');
    const privateCommunityIdInput = document.getElementById('privateCommunityId');
    const privateAccessCodeInput = document.getElementById('privateAccessCode');
    
    // Open popup when clicking join private
    
    // Close popup
    function closePrivatePopup() {
      privateAccessPopup.style.display = 'none';
    }
    closePrivatePopupBtn.addEventListener('click', closePrivatePopup);
    cancelPrivateBtn.addEventListener('click', closePrivatePopup);
    
    const communityPrivacy = document.getElementById('communityPrivacy');
    const accessCodeGroup = document.getElementById('accessCodeGroup');
    
    communityPrivacy.addEventListener('change', () => {
      if (communityPrivacy.value === 'Private') {
        accessCodeGroup.style.display = 'block';
        document.getElementById('communityAccessCode').required = true;
      } else {
        accessCodeGroup.style.display = 'none';
        document.getElementById('communityAccessCode').required = false;
      }
    });

    const privateAccessMessage = document.getElementById('privateAccessMessage');

    privateAccessForm.addEventListener('submit', function(e) {
      e.preventDefault();

      privateAccessMessage.style.display = 'none'; // reset

      const formData = new FormData(privateAccessForm);

      fetch('join_private_community.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          privateAccessMessage.style.color = 'green';
          privateAccessMessage.textContent = 'Successfully joined the community!';
          privateAccessMessage.style.display = 'block';

          setTimeout(() => {
            privateAccessPopup.style.display = 'none';
            window.location.reload(); // reload to show joined community
          }, 1500);
        } else {
          privateAccessMessage.style.color = 'red';
          privateAccessMessage.textContent = data.message || 'Incorrect access code';
          privateAccessMessage.style.display = 'block';
        }
      })
      .catch(err => {
        privateAccessMessage.style.color = 'red';
        privateAccessMessage.textContent = 'Something went wrong.';
        privateAccessMessage.style.display = 'block';
        console.error(err);
      });
    });





  </script>
</body>
</html>