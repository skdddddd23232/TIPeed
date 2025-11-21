<?php
session_start();

// Check if logged in and is admin
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth.php");
    exit();
}

$admin_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$currentUserRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Home - TiPeed</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/admin_home.css">
</head>
<body>
  <!-- Navbar -->
  <div class="navbar">
    <div class="logo">TIPeed</div>
    <div class="nav-links">
      <a href="admin_home.php" >Home</a>
      <?php if ($currentUserRole === 'admin'): ?>
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
             <?php echo strtoupper(substr($admin_name, 0, 2)); ?>
        </div>
        <div class="profile-info">
          <div class="profile-name"><?php echo $admin_name; ?></div>
          <div class="profile-course">Administrator</div>
        </div>
      </div>

      <div class="menu-section">
        <a href="profile.php" class="menu-item active"><div class="menu-icon"><i class="fas fa-user"></i></div><div class="menu-text">Profile</div></a>
        <a href="admin_home.php" class="menu-item"><div class="menu-icon"><i class="fas fa-home"></i></div><div class="menu-text">Home</div></a>
        <a href="chat_interface.php" class="menu-item"><div class="menu-icon"><i class="fas fa-comment-dots"></i></div><div class="menu-text">Course Chat</div></a>
        <a href="CourseChat.php" class="menu-item"><div class="menu-icon"><i class="fas fa-comments"></i></div><div class="menu-text">Communities Chat</div></a>
        <a href="Community.php" class="menu-item"><div class="menu-icon"><i class="fas fa-users"></i></div><div class="menu-text">Community</div></a>
        <a href="admin_reg.php" class="menu-item"><div class="menu-icon"><i class="fas fa-user-plus"></i></div><div class="menu-text">Register</div></a>
        <a href="calendar.php" class="menu-item"><div class="menu-icon"><i class="fas fa-calendar-alt"></i></div><div class="menu-text">Calendar</div></a>
        <div class="menu-item"><div class="menu-icon"><i class="fas fa-cog"></i></div><div class="menu-text">Settings</div></div>
        <a href="Help.php" class="menu-item"><div class="menu-icon"><i class="fas fa-question-circle"></i></div><div class="menu-text">Help</div></a>
        <a href="logout.php" class="menu-item"><div class="menu-icon"><i class="fas fa-sign-out-alt"></i></div><div class="menu-text">Log Out</div></a>
      </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
      <!-- Top Section - Welcome, Admin Controls and Notifications Side by Side -->
      <div class="top-section">
        <!-- Welcome Section -->
        <div class="welcome-section">
          <h1>Welcome back, <?php echo $admin_name; ?></h1>
          <p>Here's what's happening in your community today.</p>
        </div>

        <!-- Admin Controls -->
        <div class="admin-controls">
          <button class="admin-btn" onclick="window.location.href='Tickets.php'">
            <i class="fas fa-hand-paper"></i>
            <span>UserTckets</span>
          </button>
          <button class="admin-btn" onclick="window.location.href='approval.php'">
            <i class="fas fa-check-circle"></i>
            <span>Approval</span>
          </button>
          <button class="admin-btn" onclick="window.location.href='report.php'">
            <i class="fas fa-chart-bar"></i>
            <span>Report</span>
          </button>
        </div>

        <!-- Notifications Panel -->
       <div class="notifications-panel">
          <div class="notification-header">
            <div class="section-title">Notifications</div>
            <div class="notification-count">3</div>
          </div>
          <div class="notification-list">
            <div class="notification-card assignment unread">
              <div class="notification-card-icon">
                <i class="fas fa-file-alt"></i>
              </div>
              <div class="notification-card-content">
                <div class="notification-card-title">New Assignment</div>
                <div class="notification-card-message">CIT 201 - Assignment 3 posted</div>
                <div class="notification-card-time">2 hours ago</div>
              </div>
            </div>
            
            <div class="notification-card announcement">
              <div class="notification-card-icon">
                <i class="fas fa-bullhorn"></i>
              </div>
              <div class="notification-card-content">
                <div class="notification-card-title">Course Announcement</div>
                <div class="notification-card-message">IT Briefing 2 - Class canceled</div>
                <div class="notification-card-time">5 hours ago</div>
              </div>
            </div>
            
            <div class="notification-card chat unread">
              <div class="notification-card-icon">
                <i class="fas fa-comments"></i>
              </div>
              <div class="notification-card-content">
                <div class="notification-card-title">New Message</div>
                <div class="notification-card-message">Jane sent you a message</div>
                <div class="notification-card-time">Yesterday</div>
              </div>
            </div>
          </div>
          <div class="view-all-notifications">
            <a href="notifications.php" class="view-all-link">View All Notifications</a>
          </div>
        </div>
      </div>

      <!-- Content Grid -->
      <div class="content-grid">
        <div class="announcements-logs-container">
          <!-- Announcements Section -->
          <div class="announcements-section">
            <div class="section-header">
              <div class="section-title">Public Announcement</div>
              <button class="create-announcement-btn" id="createAnnouncementBtn">
                <i class="fas fa-plus"></i>
                Create Announcement
              </button>
            </div>
            
            <div id="announcementsList">
              <!-- Announcements will be displayed here -->
            </div>
          </div>

          <!-- Logs Section -->
          <div class="logs-section">
            <div class="section-header">
              <div class="section-title">Logs</div>
            </div> 
            
            <div class="logs-list">
              <!-- Logs will be displayed here -->
            </div>
          </div>
        </div>

        <div class="right-panel">
          <!-- Calendar Section -->
          <div class="calendar-section">
            <div class="calendar-header">
              <div class="section-title" id="calendarTitle">January 2020</div>
              <div class="calendar-nav">
                <button id="prevMonth"><i class="fas fa-chevron-left"></i></button>
                <button id="todayBtn">Today</button>
                <button id="nextMonth"><i class="fas fa-chevron-right"></i></button>
              </div>
            </div>
            
            <div class="calendar-grid" id="calendarGrid">
              <!-- Calendar will be generated dynamically -->
            </div>
          </div>
          
          <!-- Events Section -->
          <div class="events-section">
              <div class="section-title">UPCOMING EVENTS</div>
            <div id="eventsList">
              <!-- Events will be displayed here -->
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Announcement Modal -->
  <div class="announcement-modal" id="announcementModal">
    <div class="announcement-modal-content">
      <div class="announcement-modal-header">
        <div class="announcement-modal-title" id="modalTitle">Create New Announcement</div>
        <button class="close-modal" id="closeAnnouncementModal">&times;</button>
      </div>
      <form class="announcement-form" id="announcementForm">
        <div class="form-group">
          <label class="form-label" for="announcementTitle">Title</label>
          <input type="text" id="announcementTitle" class="form-input" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="announcementContent">Content</label>
          <textarea id="announcementContent" class="form-textarea" required></textarea>
        </div>
        <div class="form-group">
          <label class="form-label" for="announcementType">Type</label>
          <select id="announcementType" class="form-input">
            <option value="general">General</option>
            <option value="important">Important</option>
            <option value="urgent">Urgent</option>
          </select>
        </div>
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" id="cancelAnnouncement">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Announcement</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Calendar Page -->
  <div class="calendar-page" id="calendarPage">
    <div class="calendar-page-header">
      <button class="back-btn" id="backToDashboard">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
      </button>
      <h1 class="calendar-page-title" id="calendarPageTitle">Calendar - January 2020</h1>
    </div>
    
    <div class="calendar-page-content">
      <div class="calendar-page-events">
        <h2>Events for <span id="selectedDateTitle">January 1, 2020</span></h2>
        <div id="calendarPageEventsList">
          <!-- Events for selected date will be displayed here -->
        </div>
      </div>
      
      <div class="calendar-page-sidebar">
        <div class="calendar-page-sidebar-section">
          <h3>Add New Event</h3>
          <form id="calendarEventForm">
            <div class="form-group">
              <label class="form-label" for="calendarEventTitle">Event Title</label>
              <input type="text" id="calendarEventTitle" class="form-input" required>
            </div>
            <div class="form-group">
              <label class="form-label" for="calendarEventDescription">Description</label>
              <textarea id="calendarEventDescription" class="form-textarea"></textarea>
            </div>
            <div class="form-actions">
              <button type="submit" class="btn btn-primary">Add Event</button>
            </div>
          </form>
        </div>
        
        <div class="calendar-page-sidebar-section">
          <h3>Upcoming Events</h3>
          <div id="calendarPageUpcomingEvents">
            <!-- Upcoming events will be displayed here -->
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Success Message -->
  <div class="success-message" id="successMessage">
    <i class="fas fa-check-circle"></i>
    <span id="successText">Announcement created successfully!</span>
  </div>

  <script src="../java/admin_home.js"></script>
</body>
</html>