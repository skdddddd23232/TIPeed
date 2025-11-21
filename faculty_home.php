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
  <title>Faculty Home - TiPeed Forum</title>
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

    /* Main Content */
    .main-content {
      flex: 1;
      padding: 20px;
      overflow-y: auto;
      background-color: #f9fafb;
      display: flex;
      flex-direction: column;
    }

    /* Top Section - Welcome and Notifications Side by Side */
    .top-section {
      display: grid;
      grid-template-columns: 4fr 1fr;
      gap: 20px;
      margin-bottom: 20px;
    }

    /* Welcome Section */
    .welcome-section {
        background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), 
          url('../assets/home2.jpg');
    background-size: cover;
    background-position: center;
      border-radius: 12px;
      padding: 30px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      text-align: center;
      color: white;
      position: relative;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      min-height: 200px;
    }

    .welcome-section h1 {
      margin: 0 0 15px 0;
      color: white;
      font-size: 2.2rem;
      font-weight: 700;
      text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
    }

    .welcome-section p {
      margin: 0;
      color: rgba(255, 255, 255, 0.95);
      font-size: 1.1rem;
      text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
      max-width: 500px;
    }

    /* Notifications Panel */
    .notifications-panel {
      background: white;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      display: flex;
      flex-direction: column;
    }

    .notification-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
      padding-bottom: 10px;
      border-bottom: 2px solid #f0f0f0;
    }

    .section-title {
      font-size: 18px;
      font-weight: 600;
      color: #333;
    }

    .notification-count {
      background: #f5b301;
      color: white;
      border-radius: 50%;
      width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      font-weight: bold;
    }

    .notification-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
      flex: 1;
      max-height: 150px;
      overflow-y: auto;
    }

    .notification-card {
      display: flex;
      align-items: flex-start;
      padding: 12px;
      border-radius: 8px;
      background: #f8f9fa;
      transition: all 0.2s;
      cursor: pointer;
    }

    .notification-card:hover {
      background: #e9ecef;
      transform: translateY(-2px);
    }

    .notification-card.unread {
      background: #fff3cd;
      border-left: 3px solid #f5b301;
    }

    .notification-card.highlight {
      background: #fff3cd;
      border-left: 3px solid #f5b301;
    }

    .notification-card-icon {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 12px;
      flex-shrink: 0;
    }

    .notification-card.assignment .notification-card-icon {
      background: #007bff;
      color: white;
    }

    .notification-card.announcement .notification-card-icon {
      background: #28a745;
      color: white;
    }

    .notification-card.chat .notification-card-icon {
      background: #6f42c1;
      color: white;
    }

    .notification-card.reminder .notification-card-icon {
      background: #fd7e14;
      color: white;
    }

    .notification-card-content {
      flex: 1;
    }

    .notification-card-title {
      font-weight: 500;
      margin-bottom: 4px;
      font-size: 14px;
    }

    .notification-card-message {
      font-size: 12px;
      color: #666;
      margin-bottom: 4px;
    }

    .notification-card-time {
      font-size: 11px;
      color: #999;
    }

    .view-all-notifications {
      text-align: center;
      margin-top: 15px;
      padding-top: 15px;
      border-top: 1px solid #eee;
    }

    .view-all-link {
      color: #f5b301;
      font-weight: 500;
      cursor: pointer;
      font-size: 14px;
      text-decoration: none;
      display: inline-block;
      padding: 8px 16px;
      border-radius: 6px;
      transition: all 0.3s;
    }

    .view-all-link:hover {
      background: #f5b301;
      color: white;
    }

    /* Content Grid */
    .content-grid {
      display: grid;
      grid-template-columns: 1fr 350px;
      gap: 20px;
      flex: 1;
    }

    .right-panel {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    /* Announcements Section */
    .announcements-section {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .announcements-content {
      flex: 1;
      overflow-y: auto;
      max-height: 500px;
      padding-right: 5px;
    }

    /* Custom scrollbar for announcements */
    .announcements-content::-webkit-scrollbar {
      width: 6px;
    }

    .announcements-content::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }

    .announcements-content::-webkit-scrollbar-thumb {
      background: #c1c1c1;
      border-radius: 10px;
    }

    .announcements-content::-webkit-scrollbar-thumb:hover {
      background: #a8a8a8;
    }

    .notification-item {
      display: flex;
      align-items: center;
      padding: 12px 0;
      border-bottom: 1px solid #f0f0f0;
    }

    .notification-item:last-child {
      border-bottom: none;
    }

    .notification-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background-color: #f5b301;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      margin-right: 15px;
      flex-shrink: 0;
    }

    .notification-content {
      flex: 1;
    }

    .notification-title {
      font-weight: 500;
      margin-bottom: 4px;
    }

    .notification-subtitle {
      font-size: 14px;
      color: #666;
    }

    .notification-time {
      font-size: 12px;
      color: #999;
    }

    .notification-action {
      color: #f5b301;
      font-weight: 500;
      cursor: pointer;
    }

    .delete-announcement {
      background: none;
      border: none;
      color: #dc3545;
      cursor: pointer;
      padding: 5px;
      border-radius: 4px;
      transition: background 0.3s;
    }

    .delete-announcement:hover {
      background: #f8d7da;
    }

    .no-announcements {
      text-align: center;
      color: #666;
      padding: 20px;
      font-style: italic;
    }

    /* Calendar Section */
    .calendar-section {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .calendar-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }

    .calendar-nav {
      display: flex;
      gap: 10px;
    }

    .calendar-nav button {
      background: none;
      border: 1px solid #ddd;
      border-radius: 4px;
      padding: 5px 10px;
      cursor: pointer;
      transition: background 0.3s;
    }

    .calendar-nav button:hover {
      background: #f0f0f0;
    }

    .calendar-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 5px;
    }

    .calendar-day-header {
      text-align: center;
      font-weight: 500;
      padding: 8px 0;
      color: #666;
      font-size: 0.85rem;
    }

    .calendar-day {
      text-align: center;
      padding: 10px 0;
      border-radius: 4px;
      cursor: pointer;
      font-size: 0.9rem;
      transition: all 0.2s;
      position: relative;
    }

    .calendar-day:hover {
      background-color: #f0f0f0;
    }

    .calendar-day.current {
      background-color: #f5b301;
      color: white;
    }

    .calendar-day.selected {
      background-color: #007bff;
      color: white;
    }

    .calendar-day.has-event::after {
      content: '';
      position: absolute;
      bottom: 3px;
      left: 50%;
      transform: translateX(-50%);
      width: 5px;
      height: 5px;
      border-radius: 50%;
      background-color: #f5b301;
    }

    .calendar-day.other-month {
      color: #ccc;
    }

    /* Events Section */
    .events-section {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .events-content {
      flex: 1;
      overflow-y: auto;
      max-height: 150px;
      padding-right: 5px;
    }

    /* Custom scrollbar for events */
    .events-content::-webkit-scrollbar {
      width: 6px;
    }

    .events-content::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }

    .events-content::-webkit-scrollbar-thumb {
      background: #c1c1c1;
      border-radius: 10px;
    }

    .events-content::-webkit-scrollbar-thumb:hover {
      background: #a8a8a8;
    }

    .event-item {
      display: flex;
      align-items: center;
      padding: 12px 0;
      border-bottom: 1px solid #f0f0f0;
      cursor: pointer;
      transition: background 0.2s;
    }

    .event-item:hover {
      background-color: #f9f9f9;
    }

    .event-item:last-child {
      border-bottom: none;
    }

    .event-date {
      width: 50px;
      height: 50px;
      border-radius: 8px;
      background-color: #f0f0f0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      margin-right: 15px;
      flex-shrink: 0;
    }

    .event-day {
      font-size: 18px;
      font-weight: 600;
    }

    .event-month {
      font-size: 12px;
      color: #666;
    }

    .event-content {
      flex: 1;
    }

    .event-title {
      font-weight: 500;
      margin-bottom: 4px;
    }

    .event-description {
      font-size: 14px;
      color: #666;
    }

    .no-events {
      text-align: center;
      color: #666;
      padding: 20px;
      font-style: italic;
    }

    /* Event Modal */
    .event-modal {
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

    .event-modal-content {
      background: white;
      border-radius: 8px;
      padding: 20px;
      width: 90%;
      max-width: 500px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }

    .event-modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
      padding-bottom: 10px;
      border-bottom: 1px solid #eee;
    }

    .event-modal-title {
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

    .event-form {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
    }

    .form-label {
      font-weight: 500;
      margin-bottom: 5px;
      color: #333;
    }

    .form-input {
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

    .form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 10px;
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

    .btn-danger {
      background: #dc3545;
      color: white;
    }

    .btn-danger:hover {
      background: #c82333;
    }

    @media (max-width: 1024px) {
      .top-section {
        grid-template-columns: 1fr;
      }
      
      .content-grid {
        grid-template-columns: 1fr;
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
      <!-- Top Section - Welcome and Notifications Side by Side -->
      <div class="top-section">
        <!-- Welcome Section -->
        <div class="welcome-section">
          <h1>Welcome back, <?php echo $studentName; ?>!</h1>
          <p>Here's what's happening in your community today.</p>
        </div>

        <!-- Notifications Panel -->
        <div class="notifications-panel">
          <div class="notification-header">
            <div class="section-title">Notifications</div>
            <div class="notification-count" id="notificationCount">0</div>
          </div>
          <div class="notification-list" id="notificationList">
            <!-- Notifications will be loaded dynamically -->
          </div>
          <div class="view-all-notifications">
            <a href="notifications.php" class="view-all-link">View All Notifications</a>
          </div>
        </div>
      </div>

      <!-- Content Grid -->
      <div class="content-grid">
        <div class="announcements-section">
          <div class="section-title">Public Announcement</div>
          <div class="announcements-content" id="announcementsList">
            <!-- Announcements will be loaded dynamically -->
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
            <div class="events-content" id="eventsList">
              <!-- Events will be displayed here -->
            </div>
          </div>
        </div>
      </div>
    </div>

   

  <!-- Event Modal -->
  <div class="event-modal" id="eventModal">
    <div class="event-modal-content">
      <div class="event-modal-header">
        <div class="event-modal-title" id="modalTitle">Add New Event</div>
        <button class="close-modal" id="closeModal">&times;</button>
      </div>
      <form class="event-form" id="eventForm">
        <input type="hidden" id="eventId">
        <div class="form-group">
          <label class="form-label" for="eventTitle">Event Title</label>
          <input type="text" id="eventTitle" class="form-input" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="eventDate">Date</label>
          <input type="text" id="eventDate" class="form-input" readonly>
        </div>
        <div class="form-group">
          <label class="form-label" for="eventDescription">Description</label>
          <textarea id="eventDescription" class="form-textarea"></textarea>
        </div>
        <div class="form-actions">
          <button type="button" class="btn btn-danger" id="deleteEvent" style="display: none;">Delete</button>
          <button type="button" class="btn btn-secondary" id="cancelEvent">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Event</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Calendar functionality
    let currentDate = new Date();
    let events = [];
    let selectedDate = null;
    let announcements = [];
    let previousNotifications = [];

    // DOM Elements
    const calendarTitle = document.getElementById('calendarTitle');
    const calendarGrid = document.getElementById('calendarGrid');
    const prevMonthBtn = document.getElementById('prevMonth');
    const nextMonthBtn = document.getElementById('nextMonth');
    const todayBtn = document.getElementById('todayBtn');
    const eventsList = document.getElementById('eventsList');
    const eventModal = document.getElementById('eventModal');
    const modalTitle = document.getElementById('modalTitle');
    const eventForm = document.getElementById('eventForm');
    const eventTitle = document.getElementById('eventTitle');
    const eventDate = document.getElementById('eventDate');
    const eventDescription = document.getElementById('eventDescription');
    const eventId = document.getElementById('eventId');
    const closeModal = document.getElementById('closeModal');
    const cancelEvent = document.getElementById('cancelEvent');
    const deleteEvent = document.getElementById('deleteEvent');
    const announcementsList = document.getElementById('announcementsList');
    const notificationList = document.getElementById('notificationList');
    const notificationCount = document.getElementById('notificationCount');

    // Initialize calendar
    async function initCalendar() {
      await fetchEvents();
      await fetchAnnouncements();
      renderCalendar();
      renderEvents();
      renderAnnouncements();
      
      // Event listeners
      prevMonthBtn.addEventListener('click', goToPreviousMonth);
      nextMonthBtn.addEventListener('click', goToNextMonth);
      todayBtn.addEventListener('click', goToToday);
      closeModal.addEventListener('click', closeEventModal);
      cancelEvent.addEventListener('click', closeEventModal);
      eventForm.addEventListener('submit', saveEvent);
      deleteEvent.addEventListener('click', deleteSelectedEvent);
      
      // Close modal when clicking outside
      eventModal.addEventListener('click', (e) => {
        if (e.target === eventModal) {
          closeEventModal();
        }
      });

      // Start fetching notifications
      fetchNotifications();
      setInterval(fetchNotifications, 3000);
    }

    // Fetch events from server
    async function fetchEvents() {
      try {
        const res = await fetch('get_events.php');
        events = await res.json();
        renderCalendar();
        renderEvents();
      } catch (err) {
        console.error('Failed to fetch events:', err);
      }
    }

    // Fetch announcements from database
    async function fetchAnnouncements() {
      try {
        const res = await fetch('get_announcements.php');
        announcements = await res.json();
        renderAnnouncements();
      } catch (err) {
        console.error('Failed to fetch announcements:', err);
      }
    }

    // Fetch notifications
    async function fetchNotifications() {
      try {
        const res = await fetch('get_notifications.php');
        const notifications = await res.json();

        // Clear the list first
        notificationList.innerHTML = '';

        if (!notifications.length) {
          notificationList.innerHTML = '<div class="no-notifications">No notifications</div>';
          previousNotifications = [];
          notificationCount.textContent = '0';
          return;
        }

        // Update notification count
        notificationCount.textContent = notifications.length;

        notifications.forEach(noti => {
          let iconClass = 'fas fa-bullhorn';
          if (noti.category === 'report') iconClass = 'fas fa-exclamation-circle';
          if (noti.category === 'message') iconClass = 'fas fa-comments';
          
          const notiElement = document.createElement('div');
          notiElement.className = 'notification-card ' + (noti.category === 'announcement' ? 'announcement' : '');

          // Persist highlight if new
          if (!previousNotifications.find(n => n.id === noti.id)) {
            notiElement.classList.add('highlight');
          }

          notiElement.innerHTML = `
            <div class="notification-card-icon">
              <i class="${iconClass}"></i>
            </div>
            <div class="notification-card-content">
              <div class="notification-card-title">${noti.title}</div>
              <div class="notification-card-message">${noti.message}</div>
              <div class="notification-card-time">${noti.time}</div>
            </div>
          `;

          // Remove highlight when user interacts
          notiElement.addEventListener('mouseenter', () => notiElement.classList.remove('highlight'));
          notiElement.addEventListener('click', () => notiElement.classList.remove('highlight'));
          notiElement.addEventListener('scroll', () => notiElement.classList.remove('highlight'));

          notificationList.appendChild(notiElement);
        });

        // Save current notifications for next fetch
        previousNotifications = notifications;

      } catch (err) {
        console.error('Failed to fetch notifications:', err);
      }
    }

    // Render announcements in the UI
    function renderAnnouncements() {
      announcementsList.innerHTML = '';
      if (announcements.length === 0) {
        announcementsList.innerHTML = '<div class="no-announcements">No announcements yet.</div>';
        return;
      }

      announcements.forEach(a => {
        const announcementElement = document.createElement('div');
        announcementElement.className = 'notification-item';
        
        let iconClass = 'fas fa-bullhorn';
        if (a.type === 'important') iconClass = 'fas fa-exclamation-circle';
        if (a.type === 'urgent') iconClass = 'fas fa-exclamation-triangle';

        announcementElement.innerHTML = `
          <div class="notification-icon">
            <i class="${iconClass}"></i>
          </div>
          <div class="notification-content">
            <div class="notification-title">${a.title}</div>
            <div class="notification-subtitle">${a.content}</div>
            <div class="notification-time">${a.date} • ${a.author}</div>
          </div>
        `;
        
        announcementsList.appendChild(announcementElement);
      });
    }

    // Render calendar
    function renderCalendar() {
      const year = currentDate.getFullYear();
      const month = currentDate.getMonth();
      
      // Update calendar title
      calendarTitle.textContent = `${currentDate.toLocaleString('default', { month: 'long' })} ${year}`;
      
      // Clear calendar grid
      calendarGrid.innerHTML = '';
      
      // Add day headers
      const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
      days.forEach(day => {
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day-header';
        dayElement.textContent = day;
        calendarGrid.appendChild(dayElement);
      });
      
      // Get first day of month and number of days
      const firstDay = new Date(year, month, 1);
      const lastDay = new Date(year, month + 1, 0);
      const daysInMonth = lastDay.getDate();
      const startingDay = firstDay.getDay();
      
      // Add days from previous month
      const prevMonthLastDay = new Date(year, month, 0).getDate();
      for (let i = startingDay - 1; i >= 0; i--) {
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day other-month';
        dayElement.textContent = prevMonthLastDay - i;
        calendarGrid.appendChild(dayElement);
      }
      
      // Add days of current month
      const today = new Date();
      for (let i = 1; i <= daysInMonth; i++) {
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day';
        dayElement.textContent = i;
        
        // Check if this is today
        if (year === today.getFullYear() && month === today.getMonth() && i === today.getDate()) {
          dayElement.classList.add('current');
        }
        
        // Check if this date has events
        const dateStr = formatDate(new Date(year, month, i));
        if (events.some(event => formatDate(new Date(event.date)) === dateStr)) {
          dayElement.classList.add('has-event');
        }
        
        // Add click event
        dayElement.addEventListener('click', () => selectDate(new Date(year, month, i)));
        calendarGrid.appendChild(dayElement);
      }
      
      // Add days from next month to fill the grid
      const totalCells = 42; // 6 rows * 7 days
      const remainingCells = totalCells - (startingDay + daysInMonth);
      for (let i = 1; i <= remainingCells; i++) {
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day other-month';
        dayElement.textContent = i;
        calendarGrid.appendChild(dayElement);
      }
    }

    // Navigate to previous month
    function goToPreviousMonth() {
      currentDate.setMonth(currentDate.getMonth() - 1);
      renderCalendar();
    }

    // Navigate to next month
    function goToNextMonth() {
      currentDate.setMonth(currentDate.getMonth() + 1);
      renderCalendar();
    }

    // Navigate to today
    function goToToday() {
      currentDate = new Date();
      renderCalendar();
    }

    // Select a date
    function selectDate(date) {
      selectedDate = date;
      eventDate.value = formatDate(date);
      eventTitle.value = '';
      eventDescription.value = '';
      eventId.value = '';
      modalTitle.textContent = 'Add New Event';
      deleteEvent.style.display = 'none';
      
      // Check if there's already an event on this date
      const existingEvent = events.find(event => formatDate(new Date(event.date)) === formatDate(date));
      if (existingEvent) {
        eventTitle.value = existingEvent.title;
        eventDescription.value = existingEvent.description;
        eventId.value = existingEvent.event_id;
        modalTitle.textContent = 'Edit Event';
        deleteEvent.style.display = 'block';
      }
      
      eventModal.style.display = 'flex';
    }

    // Close event modal
    function closeEventModal() {
      eventModal.style.display = 'none';
    }

    // Save event
    async function saveEvent(e) {
      e.preventDefault();
      
      const title = eventTitle.value.trim();
      const date = eventDate.value;
      const description = eventDescription.value.trim();
      const id = eventId.value || null;
      
      if (!title) {
        alert('Please enter an event title');
        return;
      }
      
      try {
        if (id) {
          // Update existing event
          await fetch('update_event.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ event_id: id, title, description, date })
          });
        } else {
          // Create new event
          await fetch('create_event.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ title, description, date })
          });
        }
        
        // Refresh events
        await fetchEvents();
        closeEventModal();
      } catch (err) {
        console.error('Failed to save event:', err);
        alert('Failed to save event. Please try again.');
      }
    }

    // Delete selected event
    async function deleteSelectedEvent() {
      if (confirm('Are you sure you want to delete this event?')) {
        try {
          await fetch('delete_event.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ event_id: eventId.value })
          });
          
          // Refresh events
          await fetchEvents();
          closeEventModal();
        } catch (err) {
          console.error('Failed to delete event:', err);
          alert('Failed to delete event. Please try again.');
        }
      }
    }

    // Render events list
    function renderEvents() {
      // Sort events by date
      const sortedEvents = [...events].sort((a, b) => new Date(a.date) - new Date(b.date));
      
      // Filter events for the next 30 days
      const today = new Date();
      const nextMonth = new Date();
      nextMonth.setDate(today.getDate() + 30);
      
      const upcomingEvents = sortedEvents.filter(event => {
        const eventDate = new Date(event.date);
        return eventDate >= today && eventDate <= nextMonth;
      });
      
      // Clear events list
      eventsList.innerHTML = '';
      
      if (upcomingEvents.length === 0) {
        eventsList.innerHTML = '<div class="no-events">No upcoming events</div>';
        return;
      }
      
      // Add events to list
      upcomingEvents.forEach(event => {
        const eventDate = new Date(event.date);
        const eventElement = document.createElement('div');
        eventElement.className = 'event-item';
        eventElement.addEventListener('click', () => {
          selectedDate = eventDate;
          eventTitle.value = event.title;
          eventDescription.value = event.description;
          eventId.value = event.event_id;
          eventDate.value = formatDate(eventDate);
          modalTitle.textContent = 'Edit Event';
          deleteEvent.style.display = 'block';
          eventModal.style.display = 'flex';
        });
        
        eventElement.innerHTML = `
          <div class="event-date">
            <div class="event-day">${eventDate.getDate()}</div>
            <div class="event-month">${eventDate.toLocaleString('default', { month: 'short' }).toUpperCase()}</div>
          </div>
          <div class="event-content">
            <div class="event-title">${event.title}</div>
            <div class="event-description">${event.description || 'No description'}</div>
          </div>
        `;
        
        eventsList.appendChild(eventElement);
      });
    }

    // Format date as YYYY-MM-DD
    function formatDate(date) {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    }

    // Left sidebar toggle
    const sidebar = document.getElementById('sidebar');
    const toggleSidebar = document.getElementById('toggleSidebar');
    toggleSidebar.addEventListener('click', () => sidebar.classList.toggle('expanded'));

    

    // Initialize the calendar when page loads
    document.addEventListener('DOMContentLoaded', initCalendar);
  </script>
</body>
</html>