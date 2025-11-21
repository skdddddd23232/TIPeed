<?php
session_start();

// Check if logged in and is admin
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About Us - TiPeed Forum</title>
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
      overflow-x: hidden;
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
      z-index: 100;
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


    /* Main Content */
    .main-content {
      flex: 1;
      padding: 0;
      overflow-y: auto;
      background-color: #f9fafb;
      display: flex;
      flex-direction: column;
    }

    /* About Us Content */
    .about-container {
      width: 100%;
      margin: 0;
      padding: 0;
    }

    /* Full Screen Sections */
    .full-screen-section {
      min-height: 100vh;
      display: flex;
      align-items: center;
      padding: 0;
    }

    .section-content {
      flex: 1;
      padding: 80px;
      max-width: 50%;
    }

    .section-image {
      flex: 1;
      height: 100vh;
      background-size: cover;
      background-position: center;
      opacity: 0;
      transform: translateX(50px);
      transition: all 0.8s ease 0.4s;
    }

    .section.reverse .section-content {
      order: 2;
    }

    .section.reverse .section-image {
      order: 1;
    }

    /* Header Section */
    .about-header {
      min-height: 100vh;
      display: flex;
      align-items: center;
      padding: 0 80px;
      background: linear-gradient(135deg, #f9fafb 0%, #eef2f6 100%);
      position: relative;
    }

    .header-content {
      flex: 1;
      max-width: 50%;
    }

    .header-image {
      flex: 1;
      height: 70vh;
      background-image: url('https://images.unsplash.com/photo-1521737711867-e3b97375f902?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1770&q=80');
      background-size: cover;
      background-position: center;
      border-radius: 15px;
      margin-left: 40px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.1);
      opacity: 0;
      transform: translateX(50px);
      transition: all 0.8s ease 0.4s;
    }

    .about-header h1 {
      font-size: 5rem;
      color: #333;
      margin-bottom: 30px;
      font-weight: 700;
      letter-spacing: -2px;
      opacity: 0;
      transform: translateY(30px);
      transition: all 0.8s ease;
    }

    .about-header p {
      font-size: 1.4rem;
      color: #666;
      max-width: 600px;
      line-height: 1.6;
      opacity: 0;
      transform: translateY(30px);
      transition: all 0.8s ease 0.2s;
    }

    .fade-in {
      opacity: 1 !important;
      transform: translateY(0) !important;
    }

    /* Section Titles & Content */
    .section-title {
      font-size: 3rem;
      color: #333;
      margin-bottom: 30px;
      font-weight: 700;
      opacity: 0;
      transform: translateX(-50px);
      transition: all 0.8s ease;
    }

    .section-text {
      font-size: 1.2rem;
      line-height: 1.7;
      color: #555;
      opacity: 0;
      transform: translateX(-50px);
      transition: all 0.8s ease 0.2s;
    }

    .section-text p {
      margin-bottom: 25px;
    }

    .slide-in {
      opacity: 1 !important;
      transform: translateX(0) !important;
    }

    /* Meaning Section */
    .meaning-section {
      background: #000;
      color: white;
      min-height: 70vh;
      display: flex;
      align-items: center;
      padding: 0 80px;
    }

    .meaning-content {
      max-width: 800px;
    }

    .meaning-title {
      font-size: 2.8rem;
      margin-bottom: 30px;
      font-weight: 700;
      opacity: 0;
      transform: translateX(-50px);
      transition: all 0.8s ease;
    }

    .meaning-text {
      font-size: 1.3rem;
      line-height: 1.7;
      opacity: 0;
      transform: translateX(-50px);
      transition: all 0.8s ease 0.2s;
    }

    /* Stats Section */
    .stats-section {
      background: #f5b301;
      color: white;
      min-height: 50vh;
      display: flex;
      align-items: center;
      padding: 80px;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 40px;
      width: 100%;
    }

    .stat-item {
      opacity: 0;
      transform: translateY(30px);
      transition: all 0.8s ease;
    }

    .stat-number {
      font-size: 3.5rem;
      font-weight: 700;
      margin-bottom: 10px;
    }

    .stat-label {
      font-size: 1.2rem;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    /* Team Section */
    .team-section {
      min-height: 100vh;
      padding: 80px;
      background: linear-gradient(135deg, #f9fafb 0%, #eef2f6 100%);
    }

    .team-title {
      font-size: 3rem;
      margin-bottom: 60px;
      font-weight: 700;
      opacity: 0;
      transform: translateY(30px);
      transition: all 0.8s ease;
    }

    .team-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 40px;
    }

    .team-member {
      background: white;
      padding: 40px 30px;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      transition: transform 0.3s ease;
      opacity: 0;
      transform: translateY(30px);
      transition: all 0.8s ease;
    }

    .team-member:hover {
      transform: translateY(-10px);
    }

    .member-photo {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      object-fit: cover;
      margin-bottom: 25px;
      border: 3px solid #f5b301;
    }

    .member-name {
      font-size: 1.5rem;
      color: #333;
      margin-bottom: 10px;
      font-weight: 600;
    }

    .member-role {
      font-size: 1.1rem;
      color: #f5b301;
      margin-bottom: 20px;
      font-weight: 600;
    }

    .member-bio {
      font-size: 1rem;
      color: #666;
      line-height: 1.6;
    }

    /* Footer */
    .footer {
      background: #000;
      color: white;
      padding: 60px 80px;
    }

    .social-links {
      display: flex;
      gap: 30px;
      margin-bottom: 40px;
    }

    .social-link {
      color: white;
      font-size: 1.5rem;
      transition: color 0.3s;
    }
    

    .social-link:hover {
      color: #f5b301;
    }

    .social-app {
      display: flex;
      gap: 30px;
      margin-bottom: 40px;
    }

    .social-app {
      color: #f5b301;
      font-size: 1.5rem;
      transition: color 0.3s;
    }

    .social-app:hover {
      color: #353535ff;
    }

    .footer-text {
      font-size: 1rem;
      color: #aaa;
    }

    /* Help Chat Widget */
    .help-widget {
      position: fixed;
      bottom: 30px;
      right: 30px;
      z-index: 1000;
    }

    .help-button {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: #f5b301;
      color: white;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.2);
      transition: all 0.3s ease;
    }

    .help-button:hover {
      transform: scale(1.1);
      background: #e0a500;
    }

    .chat-container {
      position: absolute;
      bottom: 70px;
      right: 0;
      width: 350px;
      height: 500px;
      background: white;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      display: none;
      flex-direction: column;
      overflow: hidden;
    }

    .chat-container.active {
      display: flex;
      animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .chat-header {
      background: #000;
      color: white;
      padding: 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .chat-title {
      font-weight: 600;
      font-size: 1.1rem;
    }

    .close-chat {
      background: none;
      border: none;
      color: white;
      cursor: pointer;
      font-size: 18px;
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
      line-height: 1.4;
    }

    .bot-message {
      background: #f0f0f0;
      align-self: flex-start;
      border-bottom-left-radius: 5px;
    }

    .user-message {
      background: #f5b301;
      color: white;
      align-self: flex-end;
      border-bottom-right-radius: 5px;
    }

    .chat-input {
      padding: 20px;
      border-top: 1px solid #eee;
      display: flex;
      gap: 10px;
    }

    .chat-input input {
      flex: 1;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 25px;
      outline: none;
    }

    .chat-input button {
      background: #f5b301;
      color: white;
      border: none;
      border-radius: 50%;
      width: 45px;
      height: 45px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
      .full-screen-section {
        flex-direction: column;
        min-height: auto;
      }
      
      .section-content, .section-image {
        max-width: 100%;
        width: 100%;
      }
      
      .section-image {
        height: 50vh;
        min-height: 400px;
      }
      
      .about-header {
        flex-direction: column;
        padding: 60px 40px;
      }
      
      .header-content {
        max-width: 100%;
        margin-bottom: 40px;
      }
      
      .header-image {
        margin-left: 0;
        height: 50vh;
      }
      
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .team-grid {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .meaning-section, .section-content {
        padding: 60px 40px;
      }
      
      .chat-container {
        width: 300px;
        height: 450px;
      }
    }

    @media (max-width: 768px) {
      .about-header h1 {
        font-size: 3.5rem;
      }
      
      .team-grid {
        grid-template-columns: 1fr;
      }
      
      .stats-grid {
        grid-template-columns: 1fr;
      }
      
      .navbar {
        padding: 15px 20px;
      }
      
      .search-bar {
        width: 40%;
      }
      
      .about-header, .meaning-section, .section-content, .team-section, .stats-section {
        padding: 60px 20px;
      }
      
      .help-widget {
        bottom: 20px;
        right: 20px;
      }
      
      .chat-container {
        width: 280px;
        height: 400px;
      }
    }

    @media (max-width: 480px) {
      .about-header h1 {
        font-size: 2.8rem;
      }
      
      .search-bar {
        display: none;
      }
      
      .section-title {
        font-size: 2.2rem;
      }
      
      .chat-container {
        width: 260px;
        right: -10px;
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

        <div class="profile-info">
          
  
        </div>
      </div>

      <div class="menu-section">

      </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
      <div class="about-container">
        <!-- Header Section with Team Photo -->
        <div class="about-header">
          <div class="header-content">
            <h1 id="main-title">ABOUT US</h1>
            <p id="main-subtitle">A Platform-Based Web Forum for Collaborative Academic Discussions Among CCS Students</p>
          </div>
          <div class="header-image" id="header-image"></div>
        </div>

        <!-- Meaning Section -->
        <div class="meaning-section">
          <div class="meaning-content">
            <h2 class="meaning-title" id="meaning-title">This is what TiPeed Means:</h2>
            <p class="meaning-text" id="meaning-text">TiPeed stands for <strong>TI</strong> (Technological Institute of the Philippines) <strong>Pee</strong> (Peer-to-Peer) <strong>d</strong> (Discussions). It represents our mission to facilitate peer-to-peer academic discussions within the TIP community.</p>
          </div>
        </div>

        <!-- Purpose Section - Left Text, Right Image -->
        <div class="full-screen-section section">
          <div class="section-content">
            <h2 class="section-title" id="purpose-title">Purpose</h2>
            <div class="section-text" id="purpose-text">
              <p>TiPeed is envisioned as a robust web-based forum platform tailored for the unique academic needs of CCS students at TIP. It will serve as a central hub for all course-related discussions, moving away from fragmented conversations on informal messaging apps.</p>
              <p>The platform will feature a clear, intuitive interface, allowing students to easily navigate between different courses and subjects. Each course will have its own dedicated section, where students can initiate new discussion threads, ask questions, share relevant resources, and engage in peer-to-peer learning.</p>
              <p>Special attention will be given to supporting technical and programming-related discussions, including functionalities for code sharing and troubleshooting, which are crucial for computer studies students.</p>
            </div>
          </div>
          <div class="section-image" style="background-image: url('https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1771&q=80')"></div>
        </div>

        <!-- Vision Section - Right Text, Left Image -->
        <div class="full-screen-section section reverse">
          <div class="section-content">
            <h2 class="section-title" id="vision-title">Our Vision</h2>
            <div class="section-text" id="vision-text">
              <p>Our work does make sense only if it is a faithful witness of its time. We believe in creating platforms that not only serve immediate needs but also adapt and evolve with the changing educational landscape.</p>
              <p>TiPeed aims to become the primary academic collaboration hub for CCS students, fostering a culture of knowledge sharing and peer-to-peer learning that extends beyond the classroom.</p>
              <p>Through continuous innovation and user-centered design, we strive to create an ecosystem where students can thrive academically while building meaningful connections with their peers.</p>
            </div>
          </div>
          <div class="section-image" style="background-image: url('https://images.unsplash.com/photo-1523240795612-9a054b0db644?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1770&q=80')"></div>
        </div>

        <!-- Stats Section -->
        <div class="stats-section">
          <div class="stats-grid">
            <div class="stat-item" id="stat1">
              <div class="stat-number">600+</div>
              <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-item" id="stat2">
              <div class="stat-number">700+</div>
              <div class="stat-label">Discussion Threads</div>
            </div>
            <div class="stat-item" id="stat3">
              <div class="stat-number">1.2k</div>
              <div class="stat-label">Resources Shared</div>
            </div>
            <div class="stat-item" id="stat4">
              <div class="stat-number">110+</div>
              <div class="stat-label">Courses Supported</div>
            </div>
          </div>
        </div>

        <!-- Team Section -->
        <div class="team-section">
          <h2 class="team-title" id="team-title">THE TEAM</h2>
          <div class="team-grid">
            <div class="team-member" id="member1">
              <a href="https://www.instagram.com/johangallardo_/"><img src="../assets/Johan.jpg" alt="Johan" class="member-photo"></a>
              <h3 class="member-name">Johan Gallardo</h3>
              <p class="member-bio">Documentation </p><br>
              <a href="https://www.instagram.com/johangallardo_/" class="social-app"><i class="fab fa-instagram"></i></a>
            </div>
            
            <div class="team-member" id="member2">
              <a href="https://www.instagram.com/ji07734/"><img src="../assets/Jhanmatt.jpg" alt="Gappi" class="member-photo"></a>
              <h3 class="member-name">Jhanmatt Gappi</h3>
              <p class="member-bio">Back End Developer</p><br>
              
              <a href="https://www.instagram.com/ji07734/" class="social-app"><i class="fab fa-instagram"></i></a>

            </div>
            
            <div class="team-member" id="member3">
              <a href="https://www.instagram.com/da.pnry__/"><img src="../assets/Darren.jpg" alt="Gappi" class="member-photo"></a>
              <h3 class="member-name">Darren Penarroyo</h3>
              <p class="member-bio">Front End Developer</p><br>
              <a href="https://www.instagram.com/da.pnry__/" class="social-app"><i class="fab fa-instagram"></i></a>

            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="footer">
          <div class="social-links">
            <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
            <a href="#" class="social-link"><i class="fab fa-facebook"></i></a>
            <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
          </div>
          <p class="footer-text">© 2023 TiPeed Forum. All rights reserved.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Help Chat Widget -->
  <div class="help-widget">
    <button class="help-button" id="helpButton">
      <i class="fas fa-comments"></i>
    </button>
    <div class="chat-container" id="chatContainer">
      <div class="chat-header">
        <div class="chat-title">TiPeed Support</div>
        <button class="close-chat" id="closeChat">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="chat-messages" id="chatMessages">
        <div class="message bot-message">
          Hi! I'm here to help. What can I assist you with today?
        </div>
      </div>
      <div class="chat-input">
        <input type="text" id="messageInput" placeholder="Type your message...">
        <button id="sendMessage">
          <i class="fas fa-paper-plane"></i>
        </button>
      </div>
    </div>
  </div>

  <script>
    // Left sidebar toggle
    const sidebar = document.getElementById('sidebar');
    const toggleSidebar = document.getElementById('toggleSidebar');
    toggleSidebar.addEventListener('click', () => sidebar.classList.toggle('expanded'));

    // Scroll animation function
    function checkScroll() {
      const elements = document.querySelectorAll('.about-header h1, .about-header p, .header-image, .meaning-title, .meaning-text, .section-title, .section-text, .section-image, .stat-item, .team-title, .team-member');
      
      elements.forEach(element => {
        const elementTop = element.getBoundingClientRect().top;
        const elementVisible = 150;
        
        if (elementTop < window.innerHeight - elementVisible) {
          element.classList.add('fade-in');
          element.classList.add('slide-in');
        }
      });
    }

    // Help Chat functionality
    const helpButton = document.getElementById('helpButton');
    const chatContainer = document.getElementById('chatContainer');
    const closeChat = document.getElementById('closeChat');
    const messageInput = document.getElementById('messageInput');
    const sendMessage = document.getElementById('sendMessage');
    const chatMessages = document.getElementById('chatMessages');

    helpButton.addEventListener('click', () => {
      chatContainer.classList.toggle('active');
    });

    closeChat.addEventListener('click', () => {
      chatContainer.classList.remove('active');
    });

    function addMessage(text, isUser = false) {
      const messageDiv = document.createElement('div');
      messageDiv.className = `message ${isUser ? 'user-message' : 'bot-message'}`;
      messageDiv.textContent = text;
      chatMessages.appendChild(messageDiv);
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    sendMessage.addEventListener('click', () => {
      const message = messageInput.value.trim();
      if (message) {
        addMessage(message, true);
        messageInput.value = '';
        
        // Simulate bot response after a short delay
        setTimeout(() => {
          const responses = [
            "I understand. How can I help you with that?",
            "Thanks for your question! Let me find the right information for you.",
            "I'd be happy to help with that. Could you provide more details?",
            "That's a great question! Let me check our resources for you.",
            "I'm here to assist you. What specific information are you looking for?"
          ];
          const randomResponse = responses[Math.floor(Math.random() * responses.length)];
          addMessage(randomResponse);
        }, 1000);
      }
    });

    messageInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        sendMessage.click();
      }
    });

    // Initial check on page load
    window.addEventListener('load', checkScroll);
    
    // Check on scroll
    window.addEventListener('scroll', checkScroll);
  </script>
</body>
</html>