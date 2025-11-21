<?php
session_start();

// Check if logged in and is admin
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth.php");
    exit();
}

$admin_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>All Notifications - TiPeed Forum</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
      min-height: calc(100vh - 70px); 
    }

    /* Left Sidebar */
    .sidebar {
      height: 100%;
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
    }

    .profile-info {
      flex: 1;
      white-space: nowrap;
      opacity: 1;
      padding-left: 8px;
    }

    .menu-section {
      padding: 2px 0;
    }

    .menu-item {
      display: flex;
      align-items: center;
      padding: 12px 20px;
      color: #1c1c1c;
      cursor: pointer;
      transition: all 0.2s;
    }

    .menu-item:hover {
      background-color: #4d4d4d;
      color: white;
    }

    .menu-icon {
      width: 20px;
      margin-right: 12px;
      text-align: center;
      color: #878a8c;
      font-size: 18px;
    }

    .sidebar:not(.expanded) .profile-info,
    .sidebar:not(.expanded) .menu-text {
      opacity: 0;
      width: 0;
      padding: 0;
    }

    /* Main Content */
    .main-content {
      flex: 1;
      padding: 30px;
      background-color: #f9fafb;
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

    .notification-stats {
      display: flex;
      gap: 20px;
      align-items: center;
    }

    .stat-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 10px 20px;
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .stat-number {
      font-size: 24px;
      font-weight: 700;
      color: #f5b301;
    }

    .stat-label {
      font-size: 14px;
      color: #666;
      margin-top: 5px;
    }

    /* Notification Filters */
    .notification-filters {
      display: flex;
      gap: 15px;
      margin-bottom: 25px;
      padding: 15px;
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .filter-btn {
      padding: 8px 16px;
      border: 1px solid #ddd;
      border-radius: 20px;
      background: white;
      color: #666;
      cursor: pointer;
      transition: all 0.3s;
      font-size: 14px;
    }

    .filter-btn.active {
      background: #f5b301;
      color: white;
      border-color: #f5b301;
    }

    .filter-btn:hover {
      background: #f5b301;
      color: white;
      border-color: #f5b301;
    }

    /* Notifications Container */
    .notifications-container {
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      overflow: hidden;
    }

    /* Notification List */
    .notification-list-full {
      display: flex;
      flex-direction: column;
    }

    .notification-item-full {
      display: flex;
      align-items: flex-start;
      padding: 20px;
      border-bottom: 1px solid #f0f0f0;
      transition: all 0.3s;
      cursor: pointer;
    }

    .notification-item-full:hover {
      background: #f8f9fa;
    }

    .notification-item-full.unread {
      background: #fff3cd;
      border-left: 4px solid #f5b301;
    }

    .notification-item-full:last-child {
      border-bottom: none;
    }

    .notification-icon-large {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 20px;
      flex-shrink: 0;
      font-size: 20px;
    }

   
    .notification-item-full.assignment .notification-icon-large {
      background: #007bff;
      color: white;
    }

    .notification-item-full.announcement .notification-icon-large {
      background: #28a745;
      color: white;
    }

    .notification-item-full.chat .notification-icon-large {
      background: #6f42c1;
      color: white;
    }

    .notification-item-full.reminder .notification-icon-large {
      background: #fd7e14;
      color: white;
    }

    .notification-item-full.system .notification-icon-large {
      background: #6c757d;
      color: white;
    }

    .notification-item-full.grade .notification-icon-large {
      background: #e83e8c;
      color: white;
    }

    .notification-content-full {
      flex: 1;
    }

    .notification-title-full {
      font-weight: 600;
      margin-bottom: 8px;
      font-size: 16px;
      color: #333;
    }

    .notification-message-full {
      font-size: 14px;
      color: #666;
      margin-bottom: 10px;
      line-height: 1.5;
    }

    .notification-meta {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .notification-time {
      font-size: 12px;
      color: #999;
    }

    .notification-actions {
      display: flex;
      gap: 10px;
    }

    .action-btn {
      padding: 6px 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      background: white;
      color: #666;
      cursor: pointer;
      font-size: 12px;
      transition: all 0.3s;
    }

    .action-btn:hover {
      background: #f5b301;
      color: white;
      border-color: #f5b301;
    }

    .action-btn.primary {
      background: #f5b301;
      color: white;
      border-color: #f5b301;
    }

    .action-btn.primary:hover {
      background: #e0a500;
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: #666;
    }

    .empty-state i {
      font-size: 48px;
      color: #ddd;
      margin-bottom: 20px;
    }

    .empty-state h3 {
      font-size: 20px;
      margin-bottom: 10px;
      color: #999;
    }

    .empty-state p {
      font-size: 14px;
      color: #999;
    }

    /* Load More */
    .load-more {
      text-align: center;
      padding: 20px;
      border-top: 1px solid #eee;
    }

    .load-more-btn {
      padding: 10px 30px;
      background: #f5b301;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 500;
      transition: background 0.3s;
    }

    .load-more-btn:hover {
      background: #e0a500;
    }

    /* Back Button */
    .back-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 20px;
      background: #6c757d;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 500;
      transition: background 0.3s;
      margin-bottom: 20px;
    }

    .back-btn:hover {
      background: #5a6268;
    }
  </style>
</head>
<body>
  <!-- Navbar -->
  <div class="navbar">
    <div class="logo">TiPeed</div>
    <div class="nav-links">
      <a href=".html">Home</a>
      <a href="#">Thread</a>
      <a href="#">Community</a>
    </div>
    <div class="search-bar">
      <i class="fas fa-search"></i>
      <input type="text" placeholder="Search notifications...">
    </div>
  </div>

  <!-- Layout -->
  <div class="layout">
    <!-- Left Sidebar -->
    <div class="sidebar" id="sidebar">
      <div class="profile-section" id="toggleSidebar">
        <div class="profile-avatar">JD</div>
        <div class="profile-info">
          <div class="profile-name">John Doe</div>
          <div class="profile-course">BSIT</div>
        </div>
      </div>

      <div class="menu-section">
        <div class="menu-item"><div class="menu-icon"><i class="fas fa-user"></i></div><div class="menu-text">Profile</div></div>
        <div class="menu-item"><div class="menu-icon"><i class="fas fa-home"></i></div><div class="menu-text">Home</div></div>
        <div class="menu-item"><div class="menu-icon"><i class="fas fa-comments"></i></div><div class="menu-text">Course Chat</div></div>
        <div class="menu-item"><div class="menu-icon"><i class="fas fa-users"></i></div><div class="menu-text">Community</div></div>
        <div class="menu-item"><div class="menu-icon"><i class="fas fa-calendar-alt"></i></div><div class="menu-text">Calendar</div></div>
        <div class="menu-item"><div class="menu-icon"><i class="fas fa-cog"></i></div><div class="menu-text">Settings</div></div>
        <div class="menu-item"><div class="menu-icon"><i class="fas fa-question-circle"></i></div><div class="menu-text">Help</div></div>
        <div class="menu-item"><div class="menu-icon"><i class="fas fa-sign-out-alt"></i></div><div class="menu-text">Log Out</div></div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
      <!-- Back Button -->
      <button class="back-btn" onclick="window.location.href='Admin.html'">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
      </button>

      <!-- Page Header -->
      <div class="page-header">
        <h1 class="page-title">All Notifications</h1>
        <div class="notification-stats">
          <div class="stat-item">
            <div class="stat-number">12</div>
            <div class="stat-label">Total</div>
          </div>
          <div class="stat-item">
            <div class="stat-number">5</div>
            <div class="stat-label">Unread</div>
          </div>
          <div class="stat-item">
            <div class="stat-number">7</div>
            <div class="stat-label">Read</div>
          </div>
        </div>
      </div>

      <!-- Notification Filters -->
      <div class="notification-filters">
        <button class="filter-btn active">All</button>
        <button class="filter-btn">Unread</button>
        <button class="filter-btn">Announcements</button>
        <button class="filter-btn">Report</button>
        <button class="filter-btn">Messages</button>
        <button class="filter-btn">System</button>
      </div>

      <!-- Notifications Container -->
      <div class="notifications-container">
        <div class="notification-list-full" id="notificationList">
  
        </div>

        <!-- Load More Button -->
        <div class="load-more">
          <button class="load-more-btn">Load More Notifications</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Left sidebar toggle
    const sidebar = document.getElementById('sidebar');
    const toggleSidebar = document.getElementById('toggleSidebar');
    toggleSidebar.addEventListener('click', () => sidebar.classList.toggle('expanded'));

const notificationList = document.getElementById('notificationList');
const filterBtns = document.querySelectorAll('.filter-btn');

// Fetch notifications from server
async function loadNotifications() {
  try {
    const response = await fetch('get_notifications.php');
    const data = await response.json();

    // Clear existing notifications
    notificationList.innerHTML = '';

    if (data.length === 0) {
      notificationList.innerHTML = `
        <div class="empty-state">
          <i class="fas fa-bell-slash"></i>
          <h3>No notifications</h3>
          <p>You're all caught up!</p>
        </div>
      `;
      return;
    }

    // Render notifications
    data.forEach(notif => {
      const notifDiv = document.createElement('div');
      const category = notif.category.toLowerCase();
      notifDiv.classList.add('notification-item-full', notif.category);
      notifDiv.dataset.id = notif.id; // <-- Add this!
      if (notif.is_read === 0) notifDiv.classList.add('unread');
      notifDiv.innerHTML = `
        <div class="notification-icon-large">
          <i class="${getIconClass(notif.category)}" ></i>
        </div>
        <div class="notification-content-full">
          <div class="notification-title-full">${notif.title}</div>
          <div class="notification-message-full">${notif.message}</div>
          <div class="notification-meta">
            <div class="notification-time">${formatTime(notif.time)}</div>
            <div class="notification-actions">
              <button class="action-btn mark-read-btn">Mark Read</button>
              <button class="action-btn primary">View Details</button>
            </div>
          </div>
        </div>
      `;
      notificationList.appendChild(notifDiv);
    });

    setupMarkReadButtons();
    updateNotificationStats();
  } catch (error) {
    console.error('Error fetching notifications:', error);
  }
}

// Helper to get icon per category
function getIconClass(category) {
  switch(category) {
    case 'assignment': return 'fas fa-file-alt';
    case 'announcement': return 'fas fa-bullhorn';
    case 'chat': return 'fas fa-comments';
    case 'reminder': return 'fas fa-clock';
    case 'system': return 'fas fa-cogs';
    case 'grade': return 'fas fa-graduation-cap';
    default: return 'fas fa-bell';
  }
}

// Format time nicely
function formatTime(time) {
  const date = new Date(time);
  return date.toLocaleString();
}

// Filter notifications
filterBtns.forEach(btn => {
  btn.addEventListener('click', () => {
    filterBtns.forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    
    const filterType = btn.textContent.toLowerCase();
    const allNotifs = document.querySelectorAll('.notification-item-full');

    allNotifs.forEach(n => {
      if (filterType === 'all') {
        n.style.display = 'flex';
      } else if (n.classList.contains(filterType)) {
        n.style.display = 'flex';
      } else {
        n.style.display = 'none';
      }
    });
  });
});

// Mark Read/Unread functionality
  function setupMarkReadButtons() {
    const markBtns = document.querySelectorAll('.mark-read-btn');
    markBtns.forEach(btn => {
      btn.addEventListener('click', async function() {
        const notifItem = this.closest('.notification-item-full');
        const notifId = notifItem.dataset.id; // store notification ID
        const isCurrentlyUnread = notifItem.classList.contains('unread');

        // Call PHP to mark read/unread
        await fetch('mark_notification.php', {
          method: 'POST',
          body: new URLSearchParams({ 
            notification_id: notifId, 
            mark_read: isCurrentlyUnread ? 1 : 0 
          })
        });

        // Update UI
        notifItem.classList.toggle('unread');
        this.textContent = isCurrentlyUnread ? 'Mark Unread' : 'Mark Read';
        updateNotificationStats();
      });
    });
  }


// Update stats display
function updateNotificationStats() {
  const total = document.querySelectorAll('.notification-item-full').length;
  const unread = document.querySelectorAll('.notification-item-full.unread').length;
  const read = total - unread;

  document.querySelector('.notification-stats .stat-item:nth-child(1) .stat-number').textContent = total;
  document.querySelector('.notification-stats .stat-item:nth-child(2) .stat-number').textContent = unread;
  document.querySelector('.notification-stats .stat-item:nth-child(3) .stat-number').textContent = read;
}

// Initial load
loadNotifications();
  </script>
</body>
</html>