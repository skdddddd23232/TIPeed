<?php
// --- AUTO CLEAR BROWSER CACHE ---
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache");
header("Expires: 0");
session_start();
include 'db_connect.php';

// check if logged in
if (!isset($_SESSION['userid'])) {
    header("Location: auth.php");
    exit;
}

$studentName   = $_SESSION['first_name'] . " " . $_SESSION['last_name'];

$studentCourse = isset($_SESSION['course']) ? $_SESSION['course'] : "No course assigned"; 

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
  <title>Help Center - TiPeed Forum</title>
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

    /* Help Center Styles */
    .help-header {
      background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                  url('https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80');
      background-size: cover;
      background-position: center;
      border-radius: 12px;
      padding: 40px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      text-align: center;
      color: white;
      margin-bottom: 30px;
      position: relative;
    }

    .help-header h1 {
      font-size: 2.5rem;
      margin-bottom: 15px;
      font-weight: 700;
    }

    .help-header p {
      font-size: 1.2rem;
      max-width: 600px;
      margin: 0 auto;
      opacity: 0.9;
    }

    .submit-ticket-btn {
      position: absolute;
      top: 30px;
      right: 30px;
      background: #f5b301;
      color: white;
      border: none;
      border-radius: 8px;
      padding: 12px 24px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .submit-ticket-btn:hover {
      background: #e0a500;
      transform: translateY(-2px);
    }

    .help-search {
      max-width: 600px;
      margin: 20px auto 0;
      position: relative;
    }

    .help-search input {
      width: 100%;
      padding: 15px 20px;
      border-radius: 30px;
      border: none;
      font-size: 1rem;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    .help-search button {
      position: absolute;
      right: 5px;
      top: 5px;
      background: #f5b301;
      color: white;
      border: none;
      border-radius: 30px;
      padding: 10px 20px;
      cursor: pointer;
      font-weight: 600;
      transition: background 0.3s;
    }

    .help-search button:hover {
      background: #e0a500;
    }

    .help-categories {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .help-category {
      background: white;
      border-radius: 10px;
      padding: 25px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.05);
      transition: transform 0.3s, box-shadow 0.3s;
      cursor: pointer;
    }

    .help-category:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }

    .category-icon {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: #f5b301;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 15px;
      color: white;
      font-size: 1.5rem;
    }

    .help-category h3 {
      margin-bottom: 10px;
      color: #333;
    }

    .help-category p {
      color: #666;
      margin-bottom: 15px;
    }

    .category-link {
      color: #f5b301;
      font-weight: 600;
      display: flex;
      align-items: center;
    }

    .category-link i {
      margin-left: 5px;
      transition: transform 0.3s;
    }

    .help-category:hover .category-link i {
      transform: translateX(5px);
    }

    .faq-section {
      background: white;
      border-radius: 10px;
      padding: 30px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.05);
      margin-bottom: 30px;
    }

    .faq-section h2 {
      margin-bottom: 20px;
      color: #333;
      font-size: 1.8rem;
    }

    .faq-item {
      border-bottom: 1px solid #eee;
      padding: 15px 0;
    }

    .faq-question {
      display: flex;
      justify-content: space-between;
      align-items: center;
      cursor: pointer;
      font-weight: 600;
      color: #333;
    }

    .faq-question i {
      color: #f5b301;
      transition: transform 0.3s;
    }

    .faq-answer {
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease;
      color: #666;
      line-height: 1.6;
      padding-top: 0;
    }

    .faq-item.active .faq-answer {
      max-height: 500px;
      padding-top: 10px;
    }

    .faq-item.active .faq-question i {
      transform: rotate(180deg);
    }

    .contact-section {
      background: white;
      border-radius: 10px;
      padding: 30px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }

    .contact-section h2 {
      margin-bottom: 20px;
      color: #333;
      font-size: 1.8rem;
    }

    .contact-methods {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
    }

    .contact-method {
      display: flex;
      align-items: flex-start;
      padding: 20px;
      border-radius: 8px;
      background: #f8f9fa;
      transition: background 0.3s;
      cursor: pointer;
    }

    .contact-method:hover {
      background: #e9ecef;
    }

    .contact-icon {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background: #f5b301;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 15px;
      color: white;
      font-size: 1.2rem;
      flex-shrink: 0;
    }

    .contact-details h3 {
      margin-bottom: 5px;
      color: #333;
    }

    .contact-details p {
      color: #666;
      margin-bottom: 5px;
    }

    .contact-link {
      color: #f5b301;
      font-weight: 500;
      display: inline-block;
      margin-top: 5px;
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 1000;
      align-items: center;
      justify-content: center;
    }

    .modal-content {
      background: white;
      border-radius: 12px;
      width: 90%;
      max-width: 600px;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .modal-header {
      background: #f5b301;
      color: white;
      padding: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-radius: 12px 12px 0 0;
    }

    .modal-title {
      margin: 0;
      font-size: 1.5rem;
    }

    .close-modal {
      background: none;
      border: none;
      color: white;
      font-size: 1.5rem;
      cursor: pointer;
    }

    .modal-body {
      padding: 30px;
    }

    /* Ticket Modal Specific Styles */
    .ticket-form {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
    }

    .form-label {
      font-weight: 600;
      margin-bottom: 8px;
      color: #333;
    }

    .form-input, .form-select, .form-textarea {
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 16px;
      transition: border-color 0.3s;
    }

    .form-input:focus, .form-select:focus, .form-textarea:focus {
      outline: none;
      border-color: #f5b301;
    }

    .form-textarea {
      min-height: 120px;
      resize: vertical;
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
      font-weight: 600;
      transition: all 0.3s;
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

    /* Chat Modal Specific Styles */
    .chat-modal-content {
      background: white;
      border-radius: 12px;
      width: 90%;
      max-width: 500px;
      height: 70vh;
      max-height: 600px;
      display: flex;
      flex-direction: column;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      overflow: hidden;
    }

    .chat-messages {
      flex: 1;
      padding: 20px;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .message {
      max-width: 80%;
      padding: 12px 16px;
      border-radius: 18px;
      position: relative;
    }

    .message.user {
      align-self: flex-end;
      background: #007bff;
      color: white;
      border-bottom-right-radius: 4px;
    }

    .message.admin {
      align-self: flex-start;
      background: #f1f1f1;
      color: #333;
      border-bottom-left-radius: 4px;
    }

    .message-time {
      font-size: 11px;
      margin-top: 5px;
      opacity: 0.7;
    }

    .chat-input-container {
      display: flex;
      gap: 10px;
      padding: 15px;
      border-top: 1px solid #eee;
      background: #f8f9fa;
    }

    .chat-input {
      flex: 1;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 24px;
      resize: none;
      height: 50px;
      outline: none;
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

    /* Success Message */
    .success-message {
      position: fixed;
      top: 20px;
      right: 20px;
      background: #28a745;
      color: white;
      padding: 15px 25px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      z-index: 1001;
      display: none;
      align-items: center;
      gap: 10px;
      animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
      from {
        transform: translateX(100%);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }

    @media (max-width: 768px) {
      .help-categories {
        grid-template-columns: 1fr;
      }
      
      .contact-methods {
        grid-template-columns: 1fr;
      }
      
      .help-header {
        padding: 30px 20px;
      }
      
      .help-header h1 {
        font-size: 2rem;
      }
      
      .submit-ticket-btn {
        position: static;
        margin-top: 15px;
        width: 100%;
        justify-content: center;
      }
      
      .chat-modal-content {
        width: 95%;
        height: 80vh;
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
      <!-- Help Header -->
      <div class="help-header">
        <h1>How can we help you?</h1>
        <p>Find answers to common questions or get in touch with our support team</p>
        <button class="submit-ticket-btn" id="submitTicketBtn">
          <i class="fas fa-ticket-alt"></i>
          Submit a Ticket
        </button>
      </div>

      <!-- Help Categories -->
      <div class="help-categories">
        <div class="help-category">
          <div class="category-icon">
            <i class="fas fa-user-graduate"></i>
          </div>
          <h3>Getting Started</h3>
          <p>Learn the basics of using TiPeed Forum as a student or faculty member</p>
          <a href="#" class="category-link">Browse guides <i class="fas fa-arrow-right"></i></a>
        </div>

        <div class="help-category">
          <div class="category-icon">
            <i class="fas fa-comments"></i>
          </div>
          <h3>Forum & Discussions</h3>
          <p>Learn how to create threads, participate in discussions, and manage your posts</p>
          <a href="#" class="category-link">Learn more <i class="fas fa-arrow-right"></i></a>
        </div>

        <div class="help-category">
          <div class="category-icon">
            <i class="fas fa-calendar-alt"></i>
          </div>
          <h3>Calendar & Events</h3>
          <p>How to use the calendar, create events, and set reminders</p>
          <a href="#" class="category-link">Explore features <i class="fas fa-arrow-right"></i></a>
        </div>

        <div class="help-category">
          <div class="category-icon">
            <i class="fas fa-cog"></i>
          </div>
          <h3>Account & Settings</h3>
          <p>Manage your profile, privacy settings, and notification preferences</p>
          <a href="#" class="category-link">View options <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>

      <!-- FAQ Section -->
      <div class="faq-section">
        <h2>Frequently Asked Questions</h2>
        
        <div class="faq-item">
          <div class="faq-question">
            How do I reset my password?
            <i class="fas fa-chevron-down"></i>
          </div>
          <div class="faq-answer">
            To reset your password, go to the login page and click on "Forgot Password". Enter your email address and we'll send you a link to reset your password. Make sure to check your spam folder if you don't see the email in your inbox.
          </div>
        </div>
        
        <div class="faq-item">
          <div class="faq-question">
            How can I join a course community?
            <i class="fas fa-chevron-down"></i>
          </div>
          <div class="faq-answer">
            Course communities are automatically created for registered courses. If you don't see your course community, please contact your instructor to ensure you're properly enrolled in the course through the university system.
          </div>
        </div>
        
        <div class="faq-item">
          <div class="faq-question">
            Can I customize my notification settings?
            <i class="fas fa-chevron-down"></i>
          </div>
          <div class="faq-answer">
            Yes, you can customize your notification preferences in the Settings section. You can choose to receive notifications for new threads, replies, announcements, and events via email or in-app notifications.
          </div>
        </div>
        
        <div class="faq-item">
          <div class="faq-question">
            How do I create a new discussion thread?
            <i class="fas fa-chevron-down"></i>
          </div>
          <div class="faq-answer">
            To create a new thread, navigate to your course community and click the "New Thread" button. Give your thread a descriptive title, write your content, and select the appropriate category before posting.
          </div>
        </div>
        
        <div class="faq-item">
          <div class="faq-question">
            Is there a mobile app for TiPeed?
            <i class="fas fa-chevron-down"></i>
          </div>
          <div class="faq-answer">
            Currently, TiPeed is optimized for mobile browsers but we don't have a dedicated mobile app. You can access all features through your mobile browser. We're working on a mobile app which will be released in the future.
          </div>
        </div>
      </div>

      <!-- Contact Section -->
      <div class="contact-section">
        <h2>Still need help? Contact us</h2>
        <div class="contact-methods">
          <div class="contact-method">
            <div class="contact-icon">
              <i class="fas fa-envelope"></i>
            </div>
            <div class="contact-details">
              <h3>Email Support</h3>
              <p>Get help via email</p>
              <a href="mailto:support@tiped.edu" class="contact-link">support@tiped.edu</a>
            </div>
          </div>
          
          <div class="contact-method">
            <div class="contact-icon">
              <i class="fas fa-phone"></i>
            </div>
            <div class="contact-details">
              <h3>Phone Support</h3>
              <p>Mon-Fri, 9AM-5PM</p>
              <a href="tel:+18005551234" class="contact-link">+1 (800) 555-1234</a>
            </div>
          </div>
          
          <div class="contact-method" id="startLiveChat">
            <div class="contact-icon">
              <i class="fas fa-comment-dots"></i>
            </div>
            <div class="contact-details">
              <h3>Live Chat</h3>
              <p>Available during business hours</p>
              <a href="Tickets.php" class="contact-link">Start Chat</a>
            </div>
          </div>
          
          <div class="contact-method">
            <div class="contact-icon">
              <i class="fas fa-map-marker-alt"></i>
            </div>
            <div class="contact-details">
              <h3>IT Help Desk</h3>
              <p>Library Building, Room 205</p>
              <a href="#" class="contact-link">View Campus Map</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Ticket Modal -->
  <div class="modal" id="ticketModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">Submit a Support Ticket</h2>
        <button class="close-modal" id="closeTicketModal">&times;</button>
      </div>
      <div class="modal-body">
        <form class="ticket-form" id="ticketForm">
          <div class="form-group">
            <label class="form-label" for="ticketSubject">Subject</label>
            <input type="text" id="ticketSubject" class="form-input" placeholder="Brief description of your issue" required>
          </div>
          
          <div class="form-group">
            <label class="form-label" for="ticketCategory">Category</label>
            <select id="ticketCategory" class="form-select" required>
              <option value="">Select a category</option>
              <option value="technical">Technical Issue</option>
              <option value="account">Account Problem</option>
              <option value="content">Content Issue</option>
              <option value="feature">Feature Request</option>
              <option value="other">Other</option>
            </select>
          </div>
          
          <div class="form-group">
            <label class="form-label" for="ticketPriority">Priority</label>
            <select id="ticketPriority" class="form-select" required>
              <option value="low">Low</option>
              <option value="medium" selected>Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>
          
          <div class="form-group">
            <label class="form-label" for="ticketDescription">Description</label>
            <textarea id="ticketDescription" class="form-textarea" placeholder="Please provide detailed information about your issue..." required></textarea>
          </div>
          
          <div class="form-actions">
            <button type="button" class="btn btn-secondary" id="cancelTicket">Cancel</button>
            <button type="submit" class="btn btn-primary">Submit Ticket</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Chat Modal -->


  <!-- Success Message -->
  <div class="success-message" id="successMessage">
    <i class="fas fa-check-circle"></i>
    <span id="successText">Ticket submitted successfully!</span>
  </div>

  <script>
    // DOM Elements
    const submitTicketBtn = document.getElementById('submitTicketBtn');
    const ticketModal = document.getElementById('ticketModal');
    const closeTicketModal = document.getElementById('closeTicketModal');
    const cancelTicket = document.getElementById('cancelTicket');
    const ticketForm = document.getElementById('ticketForm');
    const startLiveChat = document.getElementById('startLiveChat');
    const chatModal = document.getElementById('chatModal');
    const closeChat = document.getElementById('closeChat');
    const chatInput = document.getElementById('chatInput');
    const sendMessage = document.getElementById('sendMessage');
    const chatMessages = document.getElementById('chatMessages');
    const successMessage = document.getElementById('successMessage');
    const successText = document.getElementById('successText');

    // Initialize page
    function initPage() {
      // Event listeners
      submitTicketBtn.addEventListener('click', () => {
        ticketModal.style.display = 'flex';
      });

      closeTicketModal.addEventListener('click', closeTicketModalFunc);
      cancelTicket.addEventListener('click', closeTicketModalFunc);

      ticketForm.addEventListener('submit', submitTicket);

      startLiveChat.addEventListener('click', () => {
        chatModal.style.display = 'flex';
        chatMessages.scrollTop = chatMessages.scrollHeight;
      });

      closeChat.addEventListener('click', () => {
        chatModal.style.display = 'none';
        chatInput.value = '';
      });

      sendMessage.addEventListener('click', sendChatMessage);
      chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          sendChatMessage();
        }
      });

      // FAQ functionality
      const faqItems = document.querySelectorAll('.faq-item');
      
      faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        
        question.addEventListener('click', () => {
          // Close all other items
          faqItems.forEach(otherItem => {
            if (otherItem !== item) {
              otherItem.classList.remove('active');
            }
          });
          
          // Toggle current item
          item.classList.toggle('active');
        });
      });

      // Search functionality
      const searchInput = document.querySelector('.help-search input');
      const searchButton = document.querySelector('.help-search button');
      
      searchButton.addEventListener('click', performSearch);
      searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
          performSearch();
        }
      });

      // Close modals when clicking outside
      ticketModal.addEventListener('click', (e) => {
        if (e.target === ticketModal) {
          closeTicketModalFunc();
        }
      });

      chatModal.addEventListener('click', (e) => {
        if (e.target === chatModal) {
          chatModal.style.display = 'none';
          chatInput.value = '';
        }
      });
    }

    // Close ticket modal
    function closeTicketModalFunc() {
      ticketModal.style.display = 'none';
      ticketForm.reset();
    }

// Submit new ticket
function submitTicket(e) {
  e.preventDefault();

  const subject = document.getElementById('ticketSubject').value.trim();
  const category = document.getElementById('ticketCategory').value;
  const priority = document.getElementById('ticketPriority').value;
  const description = document.getElementById('ticketDescription').value.trim();

  if (!subject || !category || !priority || !description) {
    alert('Please fill in all required fields');
    return;
  }

  fetch('submit_ticket.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ subject, category, priority, description })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      showSuccessMessage(data.message);
      closeTicketModalFunc();
    } else {
      alert(data.message);
    }
  })
  .catch(err => {
    console.error(err);
    alert('An error occurred while submitting the ticket.');
  });
}



    // Send chat message
    function sendChatMessage() {
      const message = chatInput.value.trim();
      
      if (!message) return;
      
      // Add user message
      const userMessage = document.createElement('div');
      userMessage.className = 'message user';
      userMessage.innerHTML = `
        <div class="message-text">${message}</div>
        <div class="message-time">${getCurrentTime()}</div>
      `;
      chatMessages.appendChild(userMessage);
      
      // Clear input
      chatInput.value = '';
      
      // Scroll to bottom
      chatMessages.scrollTop = chatMessages.scrollHeight;
      
      // Simulate admin response after a delay
      setTimeout(() => {
        const adminMessage = document.createElement('div');
        adminMessage.className = 'message admin';
        adminMessage.innerHTML = `
          <div class="message-text">Thank you for your message. Our support team will respond to you shortly. In the meantime, is there anything else I can help you with?</div>
          <div class="message-time">${getCurrentTime()}</div>
        `;
        chatMessages.appendChild(adminMessage);
        
        // Scroll to bottom again
        chatMessages.scrollTop = chatMessages.scrollHeight;
      }, 2000);
    }

// Show success message
function showSuccessMessage(message) {
  successText.textContent = message;
  successMessage.style.display = 'flex';
  
  setTimeout(() => {
    successMessage.style.display = 'none';
  }, 5000); // Increased to 5 seconds to read the ticket ID
}

    // Get current time for chat
    function getCurrentTime() {
      const now = new Date();
      return now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    // Search functionality
    function performSearch() {
      const query = document.querySelector('.help-search input').value.trim();
      if (query) {
        alert(`Searching for: "${query}"\n\nThis would normally show search results for help articles related to your query.`);
      }
    }

    // Initialize the page when loaded
    document.addEventListener('DOMContentLoaded', initPage);
  </script>
</body>
</html>