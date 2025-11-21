<?php
session_start();
include 'db_connect.php';

// Check if logged in
if (!isset($_SESSION['userid'])) {
    header("Location: auth.php");
    exit;
}

// Safely get user info from session
$firstName = isset($_SESSION['first_name']) ? $_SESSION['first_name'] : "User";
$lastName  = isset($_SESSION['last_name']) ? $_SESSION['last_name'] : "";
$role      = isset($_SESSION['role']) ? $_SESSION['role'] : "student";
$yearLevel = isset($_SESSION['year_level']) ? $_SESSION['year_level'] : null;
$currentUserId = $_SESSION['userid'];

$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$studentName = trim($firstName . " " . $lastName);

function ordinal($number) {
    $ends = ['th','st','nd','rd','th','th','th','th','th','th'];
    if (($number % 100) >= 11 && ($number % 100) <= 13) {
        return $number . 'th';
    }
    return $number . $ends[$number % 10];
}

$studentName = trim($firstName . " " . $lastName);

if ($role === 'student') {
    if ($yearLevel && is_numeric($yearLevel)) {
        $studentIDT = ordinal($yearLevel) . " Year";
    } else {
        $studentIDT = "No year assigned";
    }
} else {
  $studentIDT = ucfirst(htmlspecialchars($role));
}

$currentUserRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';

if ($currentUserRole === 'admin') {
    $homePage = 'admin_home.php';
} else if ($currentUserRole === 'teacher') {
    $homePage = 'teacher_home.php';
} else {
    $homePage = 'student_home.php';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Tickets - Admin - TiPeed Forum</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/NS.css">
  <style>
    * { 
      margin: 0; 
      padding: 0; 
      box-sizing: border-box; 
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    }
    
    body { 
      background: #f9fafb; 
      color: #222; 
    }

    /* Layout Fixes */
    .layout { 
      display: flex; 
      height: calc(100vh - 70px); 
    }

    /* Fixed Sidebar */
    .sidebar {
      width: 70px;
      background: #fff;
      border-right: 1px solid #eee;
      transition: all 0.3s ease;
      overflow: hidden;
      height: 100%;
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

    .profile-name {
      font-weight: 600;
      font-size: 14px;
      color: #333;
    }

    .profile-course {
      font-size: 12px;
      color: #666;
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
      font-size: 14px;
    }

    .menu-item:hover {
      background-color: #f6f7f8;
    }

    .menu-icon {
      width: 20px;
      margin-right: 12px;
      text-align: center;
      color: #878a8c;
      font-size: 18px;
    }

    .sidebar:not(.expanded) .profile-info {
      opacity: 0;
      width: 0;
      padding: 0;
    }

    .sidebar:not(.expanded) .profile-section {
      padding: 4px 6px;
      justify-content: flex-start;
    }

    .sidebar:not(.expanded) .menu-text {
      opacity: 0;
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

    /* Page Header */
    .page-header {
      margin-bottom: 30px;
    }

    .page-header h1 {
      font-size: 2.2rem;
      color: #333;
      margin-bottom: 10px;
    }

    .page-header p {
      color: #666;
      font-size: 1.1rem;
    }

    /* Tickets Container */
    .tickets-container {
      display: grid;
      grid-template-columns: 400px 1fr;
      gap: 20px;
      height: calc(100vh - 200px);
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      overflow: hidden;
    }

    /* Tickets List Sidebar */
    /* Tickets List Sidebar - Scrollable */
    .tickets-sidebar {
        border-right: 1px solid #eee;
        display: flex;
        flex-direction: column;
        height: 100%; /* Ensure it takes full height */
    }

    .tickets-list {
        flex: 1;
        overflow-y: auto;
        padding: 10px;
        max-height: calc(100vh - 300px); /* Limit maximum height */
        min-height: 200px; /* Minimum height */
    }

    /* Custom scrollbar for tickets list */
    .tickets-list::-webkit-scrollbar {
        width: 6px;
    }

    .tickets-list::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    .tickets-list::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }

    .tickets-list::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    /* For Firefox */
    .tickets-list {
        scrollbar-width: thin;
        scrollbar-color: #c1c1c1 #f1f1f1;
    }
        .tickets-header {
          padding: 20px;
          border-bottom: 1px solid #eee;
          background: #f8f9fa;
        }

        .tickets-header h2 {
          color: #333;
          margin-bottom: 15px;
        }

        .ticket-filters {
          display: flex;
          gap: 10px;
        }

        .filter-select {
          flex: 1;
          padding: 8px 12px;
          border: 1px solid #ddd;
          border-radius: 6px;
          background: white;
          font-size: 14px;
        }



        .ticket-item {
          padding: 15px;
          border-radius: 8px;
          background: #f8f9fa;
          margin-bottom: 10px;
          cursor: pointer;
          transition: all 0.3s;
          border-left: 4px solid #f5b301;
        }

        .ticket-item:hover {
          background: #e9ecef;
          transform: translateX(5px);
        }

        .ticket-item.active {
          background: #fff3cd;
          border-left-color: #007bff;
        }

        .ticket-item.unread {
          border-left-color: #dc3545;
        }

        .ticket-header {
          display: flex;
          justify-content: space-between;
          align-items: flex-start;
          margin-bottom: 8px;
        }

        .ticket-subject {
          font-weight: 600;
          color: #333;
          margin-bottom: 5px;
          font-size: 14px;
        }

        .ticket-meta {
          display: flex;
          gap: 10px;
          font-size: 12px;
          color: #666;
          flex-wrap: wrap;
        }

        .ticket-preview {
          font-size: 13px;
          color: #666;
          margin-top: 8px;
          display: -webkit-box;
          -webkit-line-clamp: 2;
          -webkit-box-orient: vertical;
          overflow: hidden;
        }

        /* Chat Section */
        .chat-messages {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 10px;
        background: #f8f9fa;
        max-height: calc(100vh - 400px); /* Added max-height to ensure scrolling */
        min-height: 300px; /* Minimum height */
    }

    /* Chat Section - Make sure it has proper height constraints */
    .chat-section {
        display: flex;
        flex-direction: column;
        background: white;
        height: 100%;
        min-height: 500px; /* Ensure minimum height */
    }

        .chat-header {
          padding: 20px;
          border-bottom: 1px solid #eee;
          background: #f8f9fa;
        }

        .chat-user-info {
          display: flex;
          align-items: center;
          gap: 15px;
        }

        .user-avatar {
          width: 50px;
          height: 50px;
          border-radius: 50%;
          background: #f5b301;
          display: flex;
          align-items: center;
          justify-content: center;
          color: white;
          font-weight: bold;
          font-size: 18px;
        }

        .user-details h3 {
          color: #333;
          margin-bottom: 5px;
        }

        .user-details .ticket-meta {
          font-size: 13px;
        }

        .chat-actions {
          display: flex;
          gap: 10px;
          margin-top: 15px;
        }

        .action-btn {
          padding: 8px 16px;
          border: none;
          border-radius: 6px;
          cursor: pointer;
          font-weight: 500;
          font-size: 13px;
          transition: all 0.3s;
        }

        .btn-resolve {
          background: #28a745;
          color: white;
        }

        .btn-resolve:hover {
          background: #218838;
        }

        .btn-close {
          background: #6c757d;
          color: white;
        }

        .btn-close:hover {
          background: #5a6268;
        }

        .btn-delete {
          background: #dc3545;
          color: white;
        }

        .btn-delete:hover {
          background: #c82333;
        }

        /* Messenger-style Chat Messages */
        .chat-messages {
          flex: 1;
          padding: 20px;
          overflow-y: auto;
          display: flex;
          flex-direction: column;
          gap: 10px;
          background: #f8f9fa;
        }

        /* Message container */
        .message {
          display: flex;
          margin-bottom: 15px;
          max-width: 70%;
        }

        /* Received messages (on the left) */
        .message.received {
          align-self: flex-start;
        }

        /* Sent messages (on the right) */
        .message.sent {
          align-self: flex-end;
        }

        /* Message bubble styling */
        .message-bubble {
          padding: 12px 16px;
          border-radius: 18px;
          position: relative;
          word-wrap: break-word;
        }

        /* Received message bubble (light background) */
        .message.received .message-bubble {
          background: #ffffff;
          border: 1px solid #e5e5ea;
          color: #333;
          border-bottom-left-radius: 4px;
        }

        /* Sent message bubble (blue background) */
        .message.sent .message-bubble {
          background: #007bff;
          color: white;
          border-bottom-right-radius: 4px;
        }

        /* Message time */
        .message-time {
          font-size: 11px;
          color: #999;
          margin-top: 5px;
          text-align: right;
        }

        .message.received .message-time {
          text-align: left;
        }

        .chat-input-container {
          display: flex;
          gap: 10px;
          padding: 20px;
          border-top: 1px solid #eee;
          background: white;
        }

        .chat-input {
          flex: 1;
          padding: 12px 15px;
          border: 1px solid #ddd;
          border-radius: 24px;
          resize: none;
          height: 50px;
          outline: none;
          font-size: 14px;
        }

        .send-btn {
          background: #f5b301;
          color: white;
          border: none;
          border-radius: 50%;
          width: 50px;
          height: 50px;
          display: flex;
          align-items: center;
          justify-content: center;
          cursor: pointer;
          transition: background 0.3s;
        }

        .send-btn:hover {
          background: #e0a500;
        }

        .no-ticket-selected {
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          height: 100%;
          color: #666;
          text-align: center;
          padding: 40px;
        }

        .no-ticket-selected i {
          font-size: 64px;
          margin-bottom: 20px;
          color: #ccc;
        }

        .no-ticket-selected h3 {
          margin-bottom: 10px;
          color: #333;
        }

        /* Status Badges */
        .status-badge {
          padding: 3px 8px;
          border-radius: 12px;
          font-size: 11px;
          font-weight: 600;
        }

        .badge-open {
          background: #d4edda;
          color: #155724;
        }

        .badge-pending {
          background: #fff3cd;
          color: #856404;
        }

        .badge-resolved {
          background: #d1ecf1;
          color: #0c5460;
        }

        .badge-closed {
          background: #f8d7da;
          color: #721c24;
        }

        /* Priority Badges */
        .priority-badge {
          padding: 3px 8px;
          border-radius: 12px;
          font-size: 11px;
          font-weight: 600;
        }

        .priority-low {
          background: #d4edda;
          color: #155724;
        }

        .priority-medium {
          background: #fff3cd;
          color: #856404;
        }

        .priority-high {
          background: #f8d7da;
          color: #721c24;
        }

        .priority-urgent {
          background: #dc3545;
          color: white;
        }

        /* Notification */
        .notification {
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

        @media (max-width: 1024px) {
          .tickets-container {
            grid-template-columns: 1fr;
          }
          
          .tickets-sidebar {
            display: none;
          }
          
          .friends-sidebar {
            display: none;
          }
          
          .message {
            max-width: 85%;
          }
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
        <a href="calendar.php" class="menu-item"><div class="menu-icon"><i class="fas fa-calendar-alt"></i></div><div class="menu-text">Calendar</div></a>
        <a href="settings.php" class="menu-item"><div class="menu-icon"><i class="fas fa-cog"></i></div><div class="menu-text">Settings</div></a>
        <a href="Help.php" class="menu-item"><div class="menu-icon"><i class="fas fa-question-circle"></i></div><div class="menu-text">Help</div></a>
        <a href="logout.php" class="menu-item"><div class="menu-icon"><i class="fas fa-sign-out-alt"></i></div><div class="menu-text">Log Out</div></a>
      </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
      <div class="page-header">
        <h1>User Support Tickets</h1>
        <p>Manage and respond to user support requests and chat with users</p>
      </div>

      <div class="tickets-container">
        <!-- Tickets List Sidebar -->
        <div class="tickets-sidebar">
          <div class="tickets-header">
            <h2>Support Tickets</h2>
            <div class="ticket-filters">
              <select class="filter-select" id="statusFilter">
                <option value="all">All Status</option>
                <option value="open">Open</option>
                <option value="pending">Pending</option>
                <option value="resolved">Resolved</option>
                <option value="closed">Closed</option>
              </select>
              <select class="filter-select" id="priorityFilter">
                <option value="all">All Priority</option>
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>
          </div>
          
          <div class="tickets-list" id="ticketsList">
            <!-- Tickets will be loaded here -->
          </div>
        </div>

        <!-- Chat Section -->
        <div class="chat-section">
          <div id="chatContainer">
            <div class="no-ticket-selected">
              <i class="fas fa-comments"></i>
              <h3>Select a ticket to start chatting</h3>
              <p>Choose a ticket from the list to view the conversation and respond to the user</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Notification -->
  <div class="notification" id="notification">
    <i class="fas fa-check-circle"></i>
    <span id="notificationText">Action completed successfully!</span>
  </div>

  <script>
    // Tickets data - loaded from localStorage
    let allTickets = [];
    let selectedTicket = null;

    // DOM Elements
    const ticketsList = document.getElementById('ticketsList');
    const chatContainer = document.getElementById('chatContainer');
    const statusFilter = document.getElementById('statusFilter');
    const priorityFilter = document.getElementById('priorityFilter');
    const notification = document.getElementById('notification');
    const notificationText = document.getElementById('notificationText');

    // Current user info from PHP
    const currentUserID = '<?= $currentUserId ?>';
    const currentUserRole = '<?= $role ?>';

    // Initialize page
    function initPage() {
      renderTicketsList();
      startAutoRefresh();
      
      // Event listeners
      statusFilter.addEventListener('change', renderTicketsList);
      priorityFilter.addEventListener('change', renderTicketsList);
      
      // Left sidebar toggle
      const sidebar = document.getElementById('sidebar');
      const toggleSidebar = document.getElementById('toggleSidebar');
      toggleSidebar.addEventListener('click', () => sidebar.classList.toggle('expanded'));
    }

    function fetchTickets(refreshChat = false) {
      fetch('get_tickets.php')
        .then(res => res.json())
        .then(data => {
          allTickets = data.map(ticket => ({
            ...ticket,
            messages: ticket.messages || []
          }));
          renderTicketsList();

          if (refreshChat && selectedTicket) {
            // Update chat for selected ticket
            const updatedTicket = allTickets.find(t => t.id === selectedTicket.id);
            if (updatedTicket) {
              selectedTicket = updatedTicket;
              renderChat();
            }
          }
        })
        .catch(err => console.error('Failed to fetch tickets:', err));
    }

    // Render tickets list
    function renderTicketsList() {
      const statusFilterValue = statusFilter.value;
      const priorityFilterValue = priorityFilter.value;
      
      let filteredTickets = allTickets;
      
      if (statusFilterValue !== 'all') {
        filteredTickets = filteredTickets.filter(ticket => ticket.status === statusFilterValue);
      }
      
      if (priorityFilterValue !== 'all') {
        filteredTickets = filteredTickets.filter(ticket => ticket.priority === priorityFilterValue);
      }
      
      if (filteredTickets.length === 0) {
        ticketsList.innerHTML = `
          <div class="no-ticket-selected">
            <i class="fas fa-ticket-alt"></i>
            <h3>No tickets found</h3>
            <p>No tickets match your current filters</p>
          </div>
        `;
        return;
      }
      
      ticketsList.innerHTML = '';
      
      filteredTickets.forEach(ticket => {
        const ticketElement = document.createElement('div');
        ticketElement.className = `ticket-item ${selectedTicket && selectedTicket.id === ticket.id ? 'active' : ''}`;
        ticketElement.addEventListener('click', () => selectTicket(ticket));
        
        const lastMessage = ticket.messages.length > 0 
          ? ticket.messages[ticket.messages.length - 1].message 
          : ticket.description;
        
        ticketElement.innerHTML = `
          <div class="ticket-header">
            <div class="ticket-subject">${ticket.subject}</div>
            <div class="status-badge badge-${ticket.status}">${ticket.status}</div>
          </div>
          <div class="ticket-meta">
            <span>By: ${ticket.userName || 'User'}</span>
            <span class="priority-badge priority-${ticket.priority}">${ticket.priority}</span>
          </div>
          <div class="ticket-preview">${lastMessage}</div>
          <div class="ticket-meta">
            <span>${formatDate(ticket.date)}</span>
            <span>${ticket.messages.length} messages</span>
          </div>
        `;
        
        ticketsList.appendChild(ticketElement);
      });
    }

    // Select a ticket
    function selectTicket(ticket) {
      if (!ticket || !ticket.id) {
        console.error("Invalid ticket selected");
        return;
      }
      console.log("Ticket selected:", ticket);
      selectedTicket = ticket;
      renderTicketsList();
      renderChat();
    }

    // Render chat
    function renderChat() {
      if (!selectedTicket) return;

      let actionsHtml = '';
      if (currentUserRole === 'admin') {
        actionsHtml = `
          <div class="chat-actions">
            <button class="action-btn btn-resolve" onclick="updateTicketStatus('resolved')">
              <i class="fas fa-check"></i> Resolve
            </button>
            <button class="action-btn btn-close" onclick="updateTicketStatus('closed')">
              <i class="fas fa-times"></i> Close
            </button>
            <button class="action-btn btn-delete" onclick="deleteTicket()">
              <i class="fas fa-trash"></i> Delete
            </button>
          </div>
        `;
      }

      chatContainer.innerHTML = `
        <div class="chat-header">
          <div class="chat-user-info">
            <div class="user-avatar">${(selectedTicket.userName || 'U').charAt(0).toUpperCase()}</div>
            <div class="user-details">
              <h3>${selectedTicket.userName || 'User'}</h3>
              <div class="ticket-meta">
                <span>Ticket: ${selectedTicket.subject}</span>
                <span class="status-badge badge-${selectedTicket.status}">${selectedTicket.status}</span>
                <span class="priority-badge priority-${selectedTicket.priority}">${selectedTicket.priority}</span>
              </div>
            </div>
          </div>
          ${actionsHtml}
        </div>
        <div class="chat-messages" id="chatMessages">
          ${selectedTicket.messages.map(msg => {
            // Determine if the message was sent by the current user
            const isCurrentUser = msg.sender_id == currentUserID;
            const messageClass = isCurrentUser ? 'sent' : 'received';
            
            return `
              <div class="message ${messageClass}">
                <div class="message-bubble">
                  ${msg.message}
                  <div class="message-time">${formatTime(msg.timestamp)}</div>
                </div>
              </div>
            `;
          }).join('')}
        </div>
        <div class="chat-input-container">
          <textarea class="chat-input" id="chatInput" placeholder="Type your response to ${selectedTicket.userName || 'the user'}..."></textarea>
          <button class="send-btn" onclick="sendMessage()">
            <i class="fas fa-paper-plane"></i>
          </button>
        </div>
      `;

      // Scroll to bottom of chat
      const chatMessages = document.getElementById('chatMessages');
      chatMessages.scrollTop = chatMessages.scrollHeight;

      // Add event listener to send button
      const chatInput = document.getElementById('chatInput');
      chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          sendMessage();
        }
      });
    }

    // Send message
    function sendMessage() {
      const chatInput = document.getElementById('chatInput');
      const message = chatInput.value.trim();
      if (!message || !selectedTicket) return;

      console.log("Sending message for ticket ID:", selectedTicket.id);

      fetch('send_ticket_message.php', {
        method: 'POST',
        body: JSON.stringify({ ticket_id: selectedTicket.id, message }),
        headers: { 'Content-Type': 'application/json' }
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          selectedTicket.messages.push(data.message);
          renderChat();
          chatInput.value = '';
          showNotification('Message sent');
        } else {
          showNotification(data.message || 'Failed to send message');
        }
      })
      .catch(err => {
        console.error(err);
        showNotification('Error sending message');
      });
    }

    // Update ticket status
    function updateTicketStatus(status) {
      if (!selectedTicket) return;

      fetch('update_ticket_status.php', {
        method: 'POST',
        body: JSON.stringify({ ticket_id: selectedTicket.id, status }),
        headers: { 'Content-Type': 'application/json' }
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) fetchTickets();
        showNotification(`Ticket ${status}`);
      });
    }

    // Delete ticket
    function deleteTicket() {
      if (!selectedTicket) return;
      
      if (confirm('Are you sure you want to delete this ticket? This action cannot be undone.')) {
        allTickets = allTickets.filter(ticket => ticket.id !== selectedTicket.id);
        selectedTicket = null;
        
        // Save to localStorage
        localStorage.setItem('allTickets', JSON.stringify(allTickets));
        
        // Update UI
        renderTicketsList();
        chatContainer.innerHTML = `
          <div class="no-ticket-selected">
            <i class="fas fa-comments"></i>
            <h3>Select a ticket to start chatting</h3>
            <p>Choose a ticket from the list to view the conversation and respond to the user</p>
          </div>
        `;
        
        // Show notification
        showNotification('Ticket deleted successfully');
      }
    }

    // Auto-refresh tickets
    function startAutoRefresh() {
        setInterval(() => {
            const chatInput = document.getElementById('chatInput');
            const isTyping = chatInput && chatInput.value.length > 0;
            
            if (!isTyping) {
                fetchTickets(true);
            }
        }, 5000); // Refresh every 5 seconds when not typing
    }
    // Show notification
    function showNotification(message) {
      notificationText.textContent = message;
      notification.style.display = 'flex';
      
      setTimeout(() => {
        notification.style.display = 'none';
      }, 3000);
    }

    // Format date for display
    function formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    // Format time for display
    function formatTime(timestamp) {
      const date = new Date(timestamp);
      return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    // Initialize the page when loaded
    document.addEventListener('DOMContentLoaded', () => {
      initPage();
      fetchTickets();
    });
  </script>
</body>
</html>