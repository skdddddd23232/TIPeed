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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Full Calendar - TiPeed Forum</title>
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
      padding: 30px;
      background-color: #f9fafb;
      margin-left: 250px; /* Account for sidebar width */
      transition: margin-left 0.3s ease;
    }

    body.sidebar-collapsed .main-content {
      margin-left: 70px;
    }

    /* Page Header */
    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      padding-bottom: 15px;
      border-bottom: 2px solid #eee;
    }

    .page-title {
      font-size: 28px;
      font-weight: 700;
      color: #333;
    }

    .calendar-controls {
      display: flex;
      gap: 15px;
      align-items: center;
    }

    .calendar-nav {
      display: flex;
      gap: 10px;
    }

    .calendar-nav button {
      background: none;
      border: 1px solid #ddd;
      border-radius: 4px;
      padding: 8px 12px;
      cursor: pointer;
      transition: background 0.3s;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .calendar-nav button:hover {
      background: #f0f0f0;
    }

    .today-btn {
      background: #f5b301;
      color: white;
      border: none;
      border-radius: 6px;
      padding: 8px 16px;
      cursor: pointer;
      font-weight: 500;
      transition: background 0.3s;
    }

    .today-btn:hover {
      background: #e0a500;
    }

    /* Big Calendar Container */
    .big-calendar-container {
      display: grid;
      grid-template-columns: 1fr 350px;
      gap: 30px;
    }

    /* Big Calendar */
    .big-calendar {
      background: white;
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .big-calendar-header {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 10px;
      margin-bottom: 15px;
    }

    .big-calendar-day-header {
      text-align: center;
      font-weight: 600;
      padding: 15px 0;
      color: #333;
      font-size: 16px;
      border-bottom: 2px solid #f0f0f0;
    }

    .big-calendar-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      grid-template-rows: repeat(6, 1fr);
      gap: 10px;
      height: 500px;
    }

    .big-calendar-day {
      border: 1px solid #f0f0f0;
      border-radius: 8px;
      padding: 10px;
      cursor: pointer;
      transition: all 0.2s;
      position: relative;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .big-calendar-day:hover {
      background-color: #f8f9fa;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .big-calendar-day.current {
      background-color: #f5b301;
      color: white;
    }

    .big-calendar-day.selected {
      background-color: #007bff;
      color: white;
    }

    .big-calendar-day.other-month {
      color: #ccc;
      background-color: #f9f9f9;
    }

    .big-calendar-date {
      font-size: 18px;
      font-weight: 600;
      margin-bottom: 5px;
    }

    .big-calendar-events {
      flex: 1;
      overflow-y: auto;
      max-height: 80px;
    }

    .big-calendar-event {
      background: #e9ecef;
      border-radius: 4px;
      padding: 4px 8px;
      margin-bottom: 4px;
      font-size: 12px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .big-calendar-event.important {
      background: #fff3cd;
      border-left: 3px solid #ffc107;
    }

    .big-calendar-event.urgent {
      background: #f8d7da;
      border-left: 3px solid #dc3545;
    }

    .big-calendar-day.has-events::after {
      content: '';
      position: absolute;
      top: 8px;
      right: 8px;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background-color: #f5b301;
    }

    /* Calendar Sidebar */
    .calendar-sidebar {
      display: flex;
      flex-direction: column;
      gap: 20px;
      max-height: calc(100vh - 140px);
      overflow-y: auto;
      position: sticky;
      top: 20px;
    }

    /* Custom scrollbar for calendar sidebar */
    .calendar-sidebar::-webkit-scrollbar {
      width: 6px;
    }

    .calendar-sidebar::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }

    .calendar-sidebar::-webkit-scrollbar-thumb {
      background: #c1c1c1;
      border-radius: 10px;
    }

    .calendar-sidebar::-webkit-scrollbar-thumb:hover {
      background: #a8a8a8;
    }

    /* Ensure the sections inside don't overflow */
    .calendar-sidebar-section {
      background: white;
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      flex-shrink: 0; /* Prevent sections from shrinking */
    }

    .section-title {
      font-size: 20px;
      font-weight: 600;
      color: #333;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 2px solid #f0f0f0;
    }

    /* Selected Date Info */
    .selected-date-info {
      text-align: center;
    }

    .selected-date {
      font-size: 24px;
      font-weight: 700;
      color: #f5b301;
      margin-bottom: 10px;
    }

    .selected-day {
      font-size: 18px;
      color: #666;
      margin-bottom: 20px;
    }

    .event-count {
      display: inline-block;
      background: #f5b301;
      color: white;
      border-radius: 20px;
      padding: 5px 15px;
      font-weight: 500;
    }

    /* Add Event Form */
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
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 14px;
    }

    .form-input:focus {
      outline: none;
      border-color: #f5b301;
    }

    .form-textarea {
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 14px;
      resize: vertical;
      min-height: 100px;
      font-family: inherit;
    }

    .form-textarea:focus {
      outline: none;
      border-color: #f5b301;
    }

    .form-select {
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 14px;
      background: white;
    }

    .form-select:focus {
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
      padding: 10px 20px;
      border: none;
      border-radius: 6px;
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

    /* Upcoming Events */
    .upcoming-events-list {
      display: flex;
      flex-direction: column;
      gap: 15px;
      max-height: 300px;
      overflow-y: auto;
    }

    .upcoming-event {
      display: flex;
      align-items: center;
      padding: 15px;
      border-radius: 8px;
      background: #f8f9fa;
      transition: all 0.2s;
      cursor: pointer;
    }

    .upcoming-event:hover {
      background: #e9ecef;
      transform: translateY(-2px);
    }

    .upcoming-event-date {
      width: 60px;
      height: 60px;
      border-radius: 8px;
      background: #f5b301;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      margin-right: 15px;
      flex-shrink: 0;
      color: white;
    }

    .upcoming-event-day {
      font-size: 20px;
      font-weight: 700;
    }

    .upcoming-event-month {
      font-size: 12px;
    }

    .upcoming-event-content {
      flex: 1;
    }

    .upcoming-event-title {
      font-weight: 600;
      margin-bottom: 5px;
    }

    .upcoming-event-description {
      font-size: 14px;
      color: #666;
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
      border-radius: 12px;
      padding: 25px;
      width: 90%;
      max-width: 500px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }

    .event-modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 2px solid #f0f0f0;
    }

    .event-modal-title {
      font-size: 22px;
      font-weight: 600;
      color: #333;
    }

    .close-modal {
      background: none;
      border: none;
      font-size: 24px;
      cursor: pointer;
      color: #666;
    }

    /* Success Message */
    .success-message {
      position: fixed;
      top: 20px;
      right: 20px;
      background: #28a745;
      color: white;
      padding: 12px 20px;
      border-radius: 6px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      z-index: 1001;
      display: none;
      align-items: center;
      gap: 10px;
    }

    /* Loading Spinner */
    .loading-spinner {
      display: inline-block;
      width: 20px;
      height: 20px;
      border: 3px solid #f3f3f3;
      border-top: 3px solid #f5b301;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 30px 20px;
      color: #666;
    }

    .empty-state i {
      font-size: 48px;
      color: #ddd;
      margin-bottom: 20px;
    }

    .empty-state h3 {
      font-size: 18px;
      margin-bottom: 10px;
      color: #999;
    }

    /* Responsive Design */
    @media (max-width: 1200px) {
      .big-calendar-container {
        grid-template-columns: 1fr;
      }
      
      .big-calendar-grid {
        height: 400px;
      }
      
      .main-content {
        margin-left: 0;
        padding: 20px;
      }
      
      body.sidebar-collapsed .main-content {
        margin-left: 0;
      }
    }

    @media (max-width: 768px) {
      .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }
      
      .calendar-controls {
        width: 100%;
        justify-content: space-between;
      }
      
      .big-calendar-grid {
        height: 350px;
      }
      
      .big-calendar-day {
        padding: 5px;
      }
      
      .big-calendar-date {
        font-size: 14px;
      }
      
      .main-content {
        padding: 15px;
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
      <!-- Page Header -->
      <div class="page-header">
        <h1 class="page-title">Calendar</h1>
        <div class="calendar-controls">
          <div class="calendar-nav">
            <button id="prevYear">
              <i class="fas fa-chevron-double-left"></i>
            </button>
            <button id="prevMonth">
              <i class="fas fa-chevron-left"></i>
            </button>
            <button id="nextMonth">
              <i class="fas fa-chevron-right"></i>
            </button>
            <button id="nextYear">
              <i class="fas fa-chevron-double-right"></i>
            </button>
          </div>
          <button class="today-btn" id="todayBtn">Today</button>
        </div>
      </div>

      <!-- Big Calendar Container -->
      <div class="big-calendar-container">
        <!-- Big Calendar -->
        <div class="big-calendar">
          <div class="big-calendar-header">
            <div class="big-calendar-day-header">Sunday</div>
            <div class="big-calendar-day-header">Monday</div>
            <div class="big-calendar-day-header">Tuesday</div>
            <div class="big-calendar-day-header">Wednesday</div>
            <div class="big-calendar-day-header">Thursday</div>
            <div class="big-calendar-day-header">Friday</div>
            <div class="big-calendar-day-header">Saturday</div>
          </div>
          <div class="big-calendar-grid" id="bigCalendarGrid">
            <!-- Calendar will be generated dynamically -->
          </div>
        </div>

        <!-- Calendar Sidebar -->
        <div class="calendar-sidebar">
          <!-- Selected Date Info -->
          <div class="calendar-sidebar-section selected-date-info">
            <div class="section-title">Selected Date</div>
            <div class="selected-date" id="selectedDate">January 1, 2020</div>
            <div class="selected-day" id="selectedDay">Wednesday</div>
            <div class="event-count" id="eventCount">0 Events</div>
          </div>

          <!-- Add Event Form -->
          <div class="calendar-sidebar-section">
            <div class="section-title">Add New Event</div>
            <form class="event-form" id="eventForm">
              <div class="form-group">
                <label class="form-label" for="eventTitle">Event Title</label>
                <input type="text" id="eventTitle" class="form-input" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="eventDescription">Description</label>
                <textarea id="eventDescription" class="form-textarea"></textarea>
              </div>
              <div class="form-group">
                <label class="form-label" for="eventType">Event Type</label>
                <select id="eventType" class="form-select">
                  <option value="general">General</option>
                  <option value="important">Important</option>
                  <option value="urgent">Urgent</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label" for="eventDate">Date</label>
                <input type="date" id="eventDate" class="form-input" required>
              </div>
              <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="submitBtn">
                  <span id="submitText">Add Event</span>
                  <div class="loading-spinner" id="loadingSpinner" style="display: none;"></div>
                </button>
              </div>
            </form>
          </div>

          <!-- Upcoming Events -->
          <div class="calendar-sidebar-section">
            <div class="section-title">Upcoming Events</div>
            <div class="upcoming-events-list" id="upcomingEventsList">
              <!-- Upcoming events will be displayed here -->
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Event Modal -->
  <div class="event-modal" id="eventModal">
    <div class="event-modal-content">
      <div class="event-modal-header">
        <div class="event-modal-title" id="modalTitle">Event Details</div>
        <button class="close-modal" id="closeModal">&times;</button>
      </div>
      <div id="eventModalContent">
        <!-- Event details will be displayed here -->
      </div>
    </div>
  </div>

  <!-- Success Message -->
  <div class="success-message" id="successMessage">
    <i class="fas fa-check-circle"></i>
    <span id="successText">Event added successfully!</span>
  </div>

  <script>
    // Calendar functionality
    let currentDate = new Date();
    let events = [];
    let selectedDate = new Date();

    // DOM Elements
    const bigCalendarGrid = document.getElementById('bigCalendarGrid');
    const prevYearBtn = document.getElementById('prevYear');
    const prevMonthBtn = document.getElementById('prevMonth');
    const nextMonthBtn = document.getElementById('nextMonth');
    const nextYearBtn = document.getElementById('nextYear');
    const todayBtn = document.getElementById('todayBtn');
    const selectedDateElement = document.getElementById('selectedDate');
    const selectedDayElement = document.getElementById('selectedDay');
    const eventCountElement = document.getElementById('eventCount');
    const eventForm = document.getElementById('eventForm');
    const eventTitle = document.getElementById('eventTitle');
    const eventDescription = document.getElementById('eventDescription');
    const eventType = document.getElementById('eventType');
    const eventDateInput = document.getElementById('eventDate');
    const upcomingEventsList = document.getElementById('upcomingEventsList');
    const eventModal = document.getElementById('eventModal');
    const modalTitle = document.getElementById('modalTitle');
    const eventModalContent = document.getElementById('eventModalContent');
    const closeModal = document.getElementById('closeModal');
    const successMessage = document.getElementById('successMessage');
    const successText = document.getElementById('successText');
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitText');
    const loadingSpinner = document.getElementById('loadingSpinner');

    // Initialize calendar
    async function initCalendar() {
      await fetchEvents();
      renderBigCalendar();
      updateSelectedDateInfo();
      renderUpcomingEvents();
      
      // Set today's date in the form
      eventDateInput.value = formatDateForInput(selectedDate);
      
      // Event listeners
      prevYearBtn.addEventListener('click', goToPreviousYear);
      prevMonthBtn.addEventListener('click', goToPreviousMonth);
      nextMonthBtn.addEventListener('click', goToNextMonth);
      nextYearBtn.addEventListener('click', goToNextYear);
      todayBtn.addEventListener('click', goToToday);
      eventForm.addEventListener('submit', saveEvent);
      closeModal.addEventListener('click', closeEventModal);
      
      // Close modal when clicking outside
      eventModal.addEventListener('click', (e) => {
        if (e.target === eventModal) {
          closeEventModal();
        }
      });
    }

    // Fetch events from PHP backend
    async function fetchEvents() {
      try {
        const response = await fetch('get_events.php'); // Your PHP file path
        if (!response.ok) {
          throw new Error('Failed to fetch events');
        }
        const data = await response.json();
        
        // Transform the data to match our expected format
        events = data.map(event => ({
          id: event.event_id.toString(),
          title: event.title,
          description: event.description,
          type: 'general', // You might want to add event_type to your database
          date: event.date.split(' ')[0], // Remove time part if exists
          author: event.author
        }));
        
      } catch (error) {
        console.error('Error fetching events:', error);
        events = [];
      }
    }

    // Render big calendar
    function renderBigCalendar() {
      const year = currentDate.getFullYear();
      const month = currentDate.getMonth();
      
      // Clear calendar grid
      bigCalendarGrid.innerHTML = '';
      
      // Get first day of month and number of days
      const firstDay = new Date(year, month, 1);
      const lastDay = new Date(year, month + 1, 0);
      const daysInMonth = lastDay.getDate();
      const startingDay = firstDay.getDay();
      
      // Add days from previous month
      const prevMonthLastDay = new Date(year, month, 0).getDate();
      for (let i = startingDay - 1; i >= 0; i--) {
        const dayElement = createDayElement(prevMonthLastDay - i, true);
        bigCalendarGrid.appendChild(dayElement);
      }
      
      // Add days of current month
      const today = new Date();
      for (let i = 1; i <= daysInMonth; i++) {
        const dayElement = createDayElement(i, false);
        
        // Check if this is today
        if (year === today.getFullYear() && month === today.getMonth() && i === today.getDate()) {
          dayElement.classList.add('current');
        }
        
        // Check if this is selected date
        if (year === selectedDate.getFullYear() && month === selectedDate.getMonth() && i === selectedDate.getDate()) {
          dayElement.classList.add('selected');
        }
        
        bigCalendarGrid.appendChild(dayElement);
      }
      
      // Add days from next month to fill the grid
      const totalCells = 42; // 6 rows * 7 days
      const remainingCells = totalCells - (startingDay + daysInMonth);
      for (let i = 1; i <= remainingCells; i++) {
        const dayElement = createDayElement(i, true);
        bigCalendarGrid.appendChild(dayElement);
      }
    }

    // Create day element for big calendar
    function createDayElement(day, isOtherMonth) {
      const dayElement = document.createElement('div');
      dayElement.className = 'big-calendar-day';
      
      if (isOtherMonth) {
        dayElement.classList.add('other-month');
      }
      
      const date = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
      const dateStr = formatDate(date);
      const dayEvents = events.filter(event => event.date === dateStr);
      
      dayElement.innerHTML = `
        <div class="big-calendar-date">${day}</div>
        <div class="big-calendar-events">
          ${dayEvents.map(event => `
            <div class="big-calendar-event ${event.type || 'general'}" data-id="${event.id}">
              ${event.title}
            </div>
          `).join('')}
        </div>
      `;
      
      if (dayEvents.length > 0) {
        dayElement.classList.add('has-events');
      }
      
      // Add click event
      dayElement.addEventListener('click', () => selectDate(date));
      
      // Add event listeners to event elements
      dayElement.querySelectorAll('.big-calendar-event').forEach(eventElement => {
        eventElement.addEventListener('click', (e) => {
          e.stopPropagation();
          const eventId = eventElement.getAttribute('data-id');
          showEventDetails(eventId);
        });
      });
      
      return dayElement;
    }

    // Select a date
    function selectDate(date) {
      selectedDate = date;
      eventDateInput.value = formatDateForInput(date);
      updateSelectedDateInfo();
      renderBigCalendar();
    }

    // Update selected date info
    function updateSelectedDateInfo() {
      selectedDateElement.textContent = selectedDate.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
      });
      
      selectedDayElement.textContent = selectedDate.toLocaleDateString('en-US', { 
        weekday: 'long' 
      });
      
      const dateStr = formatDate(selectedDate);
      const dayEvents = events.filter(event => event.date === dateStr);
      eventCountElement.textContent = `${dayEvents.length} Event${dayEvents.length !== 1 ? 's' : ''}`;
    }

    // Navigate to previous year
    function goToPreviousYear() {
      currentDate.setFullYear(currentDate.getFullYear() - 1);
      renderBigCalendar();
    }

    // Navigate to previous month
    function goToPreviousMonth() {
      currentDate.setMonth(currentDate.getMonth() - 1);
      renderBigCalendar();
    }

    // Navigate to next month
    function goToNextMonth() {
      currentDate.setMonth(currentDate.getMonth() + 1);
      renderBigCalendar();
    }

    // Navigate to next year
    function goToNextYear() {
      currentDate.setFullYear(currentDate.getFullYear() + 1);
      renderBigCalendar();
    }

    // Navigate to today
    function goToToday() {
      currentDate = new Date();
      selectedDate = new Date();
      eventDateInput.value = formatDateForInput(selectedDate);
      renderBigCalendar();
      updateSelectedDateInfo();
    }

    // Save event
    async function saveEvent(e) {
      e.preventDefault();
      
      const title = eventTitle.value.trim();
      const description = eventDescription.value.trim();
      const type = eventType.value;
      const date = eventDateInput.value;
      
      if (!title) {
        alert('Please enter an event title');
        return;
      }
      
      // Show loading state
      submitText.textContent = 'Adding...';
      loadingSpinner.style.display = 'inline-block';
      submitBtn.disabled = true;
      
      try {
        // Send data to PHP backend
        const formData = new FormData();
        formData.append('title', title);
        formData.append('description', description);
        formData.append('event_date', date);
        formData.append('event_type', type);
        
        const response = await fetch('save_event.php', {
          method: 'POST',
          body: formData
        });
        
        if (!response.ok) {
          throw new Error('Failed to save event');
        }
        
        const result = await response.json();
        
        if (result.success) {
          // Refresh events from server
          await fetchEvents();
          renderBigCalendar();
          updateSelectedDateInfo();
          renderUpcomingEvents();
          
          // Reset form
          eventForm.reset();
          eventDateInput.value = formatDateForInput(selectedDate);
          
          // Show success message
          showSuccessMessage('Event added successfully!');
        } else {
          throw new Error(result.message || 'Failed to save event');
        }
        
      } catch (error) {
        console.error('Error saving event:', error);
        alert('Error saving event: ' + error.message);
      } finally {
        // Reset button state
        submitText.textContent = 'Add Event';
        loadingSpinner.style.display = 'none';
        submitBtn.disabled = false;
      }
    }

    // Show event details
    function showEventDetails(eventId) {
      const event = events.find(e => e.id === eventId);
      
      if (!event) return;
      
      modalTitle.textContent = 'Event Details';
      eventModalContent.innerHTML = `
        <div class="form-group">
          <label class="form-label">Title</label>
          <div class="form-input" style="background: #f8f9fa;">${event.title}</div>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <div class="form-textarea" style="background: #f8f9fa; min-height: 100px;">${event.description || 'No description'}</div>
        </div>
        <div class="form-group">
          <label class="form-label">Type</label>
          <div class="form-input" style="background: #f8f9fa;">${event.type || 'General'}</div>
        </div>
        <div class="form-group">
          <label class="form-label">Date</label>
          <div class="form-input" style="background: #f8f9fa;">${new Date(event.date).toLocaleDateString('en-US', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
          })}</div>
        </div>
        <div class="form-group">
          <label class="form-label">Author</label>
          <div class="form-input" style="background: #f8f9fa;">${event.author || 'Unknown'}</div>
        </div>
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" id="closeDetailsBtn">Close</button>
          <button type="button" class="btn btn-danger" id="deleteEventBtn">Delete Event</button>
        </div>
      `;
      
      eventModal.style.display = 'flex';
      
      // Add event listeners
      document.getElementById('closeDetailsBtn').addEventListener('click', closeEventModal);
      document.getElementById('deleteEventBtn').addEventListener('click', () => deleteEvent(eventId));
    }

    // Delete event
    async function deleteEvent(eventId) {
      if (confirm('Are you sure you want to delete this event?')) {
        try {
          const response = await fetch('delete_event.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({ event_id: eventId })
          });
          
          if (!response.ok) {
            throw new Error('Failed to delete event');
          }
          
          const result = await response.json();
          
          if (result.success) {
            // Refresh events from server
            await fetchEvents();
            renderBigCalendar();
            updateSelectedDateInfo();
            renderUpcomingEvents();
            closeEventModal();
            showSuccessMessage('Event deleted successfully!');
          } else {
            throw new Error(result.message || 'Failed to delete event');
          }
          
        } catch (error) {
          console.error('Error deleting event:', error);
          alert('Error deleting event: ' + error.message);
        }
      }
    }

    // Close event modal
    function closeEventModal() {
      eventModal.style.display = 'none';
    }

    // Render upcoming events
    function renderUpcomingEvents() {
      // Sort events by date
      const sortedEvents = [...events].sort((a, b) => new Date(a.date) - new Date(b.date));
      
      // Filter events for the next 30 days
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const nextMonth = new Date();
      nextMonth.setDate(today.getDate() + 30);
      
      const upcomingEvents = sortedEvents.filter(event => {
        const eventDate = new Date(event.date);
        return eventDate >= today;
      }).slice(0, 5); // Show only next 5 events
      
      // Clear upcoming events list
      upcomingEventsList.innerHTML = '';
      
      if (upcomingEvents.length === 0) {
        upcomingEventsList.innerHTML = `
          <div class="empty-state">
            <i class="fas fa-calendar-plus"></i>
            <h3>No upcoming events</h3>
            <p>Add events to see them here</p>
          </div>
        `;
        return;
      }
      
      // Add upcoming events to list
      upcomingEvents.forEach(event => {
        const eventDate = new Date(event.date);
        const eventElement = document.createElement('div');
        eventElement.className = 'upcoming-event';
        eventElement.addEventListener('click', () => {
          selectDate(eventDate);
          showEventDetails(event.id);
        });
        
        eventElement.innerHTML = `
          <div class="upcoming-event-date">
            <div class="upcoming-event-day">${eventDate.getDate()}</div>
            <div class="upcoming-event-month">${eventDate.toLocaleString('default', { month: 'short' }).toUpperCase()}</div>
          </div>
          <div class="upcoming-event-content">
            <div class="upcoming-event-title">${event.title}</div>
            <div class="upcoming-event-description">${event.description || 'No description'}</div>
          </div>
        `;
        
        upcomingEventsList.appendChild(eventElement);
      });
    }

    // Format date as YYYY-MM-DD
    function formatDate(date) {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    }

    // Format date for input field (YYYY-MM-DD)
    function formatDateForInput(date) {
      return formatDate(date);
    }

    // Show success message
    function showSuccessMessage(message) {
      successText.textContent = message;
      successMessage.style.display = 'flex';
      
      setTimeout(() => {
        successMessage.style.display = 'none';
      }, 3000);
    }

    // Left sidebar toggle
    const sidebar = document.getElementById('sidebar');
    const toggleSidebar = document.getElementById('toggleSidebar');
    toggleSidebar.addEventListener('click', () => {
      sidebar.classList.toggle('expanded');
      document.body.classList.toggle('sidebar-collapsed');
    });

    // Initialize the calendar when page loads
    document.addEventListener('DOMContentLoaded', initCalendar);
  </script>
</body>
</html>