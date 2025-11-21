<?php
// --- AUTO CLEAR BROWSER CACHE ---
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TIPeed</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css'); ?>">
  <script src="assets/js/app.js?v=<?= filemtime('assets/js/app.js'); ?>"></script>
  <style>
    * { 
      margin: 0; 
      padding: 0; 
      box-sizing: border-box; 
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
      text-decoration: none;
    }

    body {
      height: 100vh;
      display: flex;
      justify-content: flex-end;
      align-items: center;
      overflow: hidden;
    }

    /* Background slideshow */
    .slideshow {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      z-index: -2;
      overflow: hidden;
    }

    .slideshow img {
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      object-fit: cover;
      opacity: 0;
      animation: fade 18s infinite;
    }

    .slideshow img:nth-child(1) {animation-delay: 0s;}
    .slideshow img:nth-child(2) {animation-delay: 6s;}
    .slideshow img:nth-child(3) {animation-delay: 12s;}

    @keyframes fade {
      0% {opacity: 0;}
      10% {opacity: 1;}
      30% {opacity: 1;}
      40% {opacity: 0;}
      100% {opacity: 0;}
    }

    /* Gradient overlay */
    .overlay {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: linear-gradient(to right, rgba(0,0,0,0.6), rgba(255, 223, 0, 0.6), white);
      z-index: -1;
    }

    /* Navbar */
    nav {
      position: fixed;
      top: 0; left: 0;
      width: 100%;
      display: flex; justify-content: space-between; align-items: center;
      padding: 20px 50px;
      background: transparent;
      color: white; font-size: 16px;
      z-index: 1000;
    }
    nav .logo {font-size:50px;font-weight:bold;color:#f5b301;}
    nav ul {list-style:none;display:flex;gap:100px;}
    nav ul li a {color: #000;;text-decoration:none;font-weight:500;transition:0.3s;}
    nav ul li a:hover {color:#ffdf00;}

    /* Card */
    .login-container {
      width: 380px; background:#fff; border-radius:15px;
      box-shadow:0 4px 20px rgba(0,0,0,0.2);
      overflow:hidden; margin-right:80px; margin-top:80px;
    }
    .login-header {
      background: linear-gradient(135deg, #ffdf00 40%, #fff176 100%);
      height: 120px;
      border-bottom-left-radius: 50% 40px;
      border-bottom-right-radius: 50% 40px;
    }
    .login-box {padding:20px;}
    .login-box h2 {text-align:center;margin-bottom:20px;color:#ff9800;}
    .login-box input, .login-box select {
      width:100%; padding:10px; margin:10px 0;
      border:1px solid #ccc; border-radius:8px; font-size:14px;
    }

    /* Password + toggle eye */
    .password-container {position:relative;}
    .password-container input {padding-right:40px;}
    .toggle-password {
      position:absolute; right:10px; top:50%;
      transform:translateY(-50%); cursor:pointer; font-size:16px; color:#888;
    }
    .toggle-password:hover {color:#ff9800;}

    /* Checkbox align */
    .agree-container {
      display:flex; align-items:center;
      margin:15px 0; font-size:14px; gap:8px;
      padding-left:10px;
    }
    .agree-container input[type="checkbox"] {
      width:16px; height:16px; accent-color:#ff9800; cursor:pointer;
    }

    /* Buttons */
    .login-box button {
      width:100%; background:#ff9800; color:white;
      border:none; padding:12px; font-size:16px;
      border-radius:8px; cursor:pointer; transition:0.3s;
    }
    .login-box button:hover {background:#e68900;}

    .switch-form {
      text-align:center; margin-top:15px; font-size:14px;
    }
    .switch-form a {color:#ff9800;text-decoration:none;cursor:pointer;}
    .switch-form a:hover {text-decoration:underline;}

    /* Hide forms by default */
    .form-section {display:none;}
    .form-section.active {display:block;}

    /* Lower left text */
    .tagline {
    position: fixed;
    bottom: 30px;
    left: 40px;
    font-size: 34px;
    font-weight: 900;
    line-height: 1.3;
    letter-spacing: 2px;
    text-transform: uppercase;
    z-index: 2000; /* keep above backgrounds */
    }
    .tagline span {
      display: block;
      color: #fff;
      text-shadow: 
        -2px -2px 0 #000,  
        2px -2px 0 #000,
        -2px  2px 0 #000,
        2px  2px 0 #000, /* Black outline */
        4px  4px 0 #ff9800; /* Orange accent shadow */
    }

    /* Notification Popup */
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 10000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: translateX(400px);
        opacity: 0;
        transition: all 0.3s ease;
        max-width: 350px;
    }

    .notification.show {
        transform: translateX(0);
        opacity: 1;
    }

    .notification.error {
        background: #f44336;
        border-left: 4px solid #d32f2f;
    }

    .notification.success {
        background: #4CAF50;
        border-left: 4px solid #388E3C;
    }

    .notification.warning {
        background: #ff9800;
        border-left: 4px solid #f57c00;
    }

    .close-notification {
        background: none;
        border: none;
        color: white;
        font-size: 18px;
        cursor: pointer;
        margin-left: 15px;
        float: right;
        line-height: 1;
    }
  </style>
</head>
<body>
  <!-- Background slideshow -->
  <div class="slideshow">
    <img src="../assets/home1.jpg" alt="">
    <img src="../assets/home2.jpg" alt="">
    <img src="../assets/home3.jpg" alt="">
  </div>
  <div class="overlay"></div>

  <!-- Navbar -->
  <nav>
    <div class="logo">TIPeed</div>
    <ul>
      <li><a href="authus.php">About Us</a></li>
      <li><a href="auth.php" id="navLogin">Login</a></li>
    </ul>
  </nav>

  <!-- Registration / Login Card -->
  <div class="login-container">
    <div class="login-header"></div>
    <div class="login-box">

      <!-- Registration Form -->
        <div id="registerForm" class="form-section active">
          <h2>Create Account</h2>
          <!-- CHANGE: Add action + method -->
          <form action="register.php" method="POST">  
            <!-- CHANGE: Add name="" for PHP -->
            <input type="text" name="first_name" placeholder="First Name" required>
            <input type="text" name="last_name" placeholder="Last Name" required>
            <input type="text" name="student_id" placeholder="Student ID" required>

            <select name="year_level" required>
              <option value="" disabled selected>Year</option>
              <option value="1">1st Year</option>
              <option value="2">2nd Year</option>
              <option value="3">3rd Year</option>
              <option value="4">4th Year</option>
            </select>

            <input type="email" name="email" placeholder="Email" required>

            <div class="password-container">
              <input type="password" id="regPassword" name="password" placeholder="Password" required>
              <i class="fa-solid fa-eye toggle-password" onclick="togglePassword('regPassword', this)"></i>
            </div>

            <div class="password-container">
              <input type="password" id="regConfirmPassword" name="confirm_password" placeholder="Confirm Password" required>
              <i class="fa-solid fa-eye toggle-password" onclick="togglePassword('regConfirmPassword', this)"></i>
            </div>

            <div class="agree-container">
              <input type="checkbox" id="agree" required>
              <label for="agree">I agree to the <a href="#">Terms & Conditions</a></label>
            </div>

            <button type="submit">Create Account</button>
          </form>
          <div class="switch-form">
            Already have an account? <a onclick="showLogin()">Login</a>
          </div>
        </div>

        <!-- Login Form -->
       <!-- Login Form -->
       <!-- Login Form -->
      <div id="loginForm" class="form-section">
          <h2>Login</h2>
          <form action="login.php" method="POST" id="loginFormElement">
              <input type="email" name="email" placeholder="Email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
              <div class="password-container">
                  <input type="password" id="loginPassword" name="password" placeholder="Password" required>
                  <i class="fa-solid fa-eye toggle-password" onclick="togglePassword('loginPassword', this)"></i>
              </div>
              <button type="submit">Sign In</button>
          </form>

          <div class="switch-form">
              Don't have an account? <a onclick="showRegister()">Register</a>
          </div>
      </div>


    </div>
  </div>

  <!-- Lower left tagline -->
  <div class="tagline">
    <span>LIFELONG LEARNERS</span>
    <span>PROBLEM SOLVERS</span>
    <span>INNOVATORS</span>
  </div>

  <!-- Notification Container -->
<div id="notificationContainer"></div>

  <script>
    function togglePassword(id, icon) {
      const field = document.getElementById(id);
      if (field.type === "password") {
        field.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
      } else {
        field.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
      }
    }

    function showLogin() {
      document.getElementById("registerForm").classList.remove("active");
      document.getElementById("loginForm").classList.add("active");
    }

    function showRegister() {
      document.getElementById("loginForm").classList.remove("active");
      document.getElementById("registerForm").classList.add("active");
    }

    // Navbar Login click
    document.getElementById("navLogin").addEventListener("click", (e) => {
      e.preventDefault();
      showLogin();
    });

    // Notification system
// Notification system
function showNotification(message, type = 'error') {
    const container = document.getElementById('notificationContainer');
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        ${message}
        <button class="close-notification" onclick="this.parentElement.remove()">&times;</button>
    `;
    
    container.appendChild(notification);
    
    // Show notification
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        hideNotification(notification);
    }, 5000);
}

function hideNotification(notification) {
    notification.classList.remove('show');
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 300);
}

// Check URL parameters for errors and show login form
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    
    if (error === 'invalid_password') {
        showNotification('❌ Invalid password.', 'error');
        // Show login form and remove error from URL
        showLogin();
        window.history.replaceState({}, document.title, window.location.pathname);
    } else if (error === 'no_account') {
        showNotification('❌ No account found with that email.', 'error');
        // Show login form and remove error from URL
        showLogin();
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

// Function to show login form and ensure it's visible
function showLogin() {
    document.getElementById("registerForm").classList.remove("active");
    document.getElementById("loginForm").classList.add("active");
}

function showRegister() {
    document.getElementById("loginForm").classList.remove("active");
    document.getElementById("registerForm").classList.add("active");
    // Clear any URL parameters when switching to register
    window.history.replaceState({}, document.title, window.location.pathname);
}

// Rest of your existing functions...
function togglePassword(id, icon) {
    const field = document.getElementById(id);
    if (field.type === "password") {
        field.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        field.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}

// Navbar Login click
document.getElementById("navLogin").addEventListener("click", (e) => {
    e.preventDefault();
    showLogin();
});
// Check for PHP errors on page load
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($login_error)): ?>
        showNotification('<?php echo $login_error; ?>', 'error');
    <?php endif; ?>
});

// AJAX login form submission
// AJAX login form submission - FIXED VERSION
document.getElementById('loginFormElement').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitButton = this.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    
    // Show loading state
    submitButton.textContent = 'Signing In...';
    submitButton.disabled = true;
    
    fetch('login.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.redirected) {
            // If PHP redirects, follow the redirect
            window.location.href = response.url;
            return;
        }
        return response.text();
    })
    .then(data => {
        if (!data) return;
        
        // Check if response contains error messages
        if (data.includes('❌')) {
            if (data.includes('Invalid password')) {
                showNotification('❌ Invalid password.', 'error');
            } else if (data.includes('No account found')) {
                showNotification('❌ No account found with that email.', 'error');
            }
        } else if (data.includes('Location:')) {
            // Handle redirect responses
            const redirectMatch = data.match(/Location:\s*([^\s]+)/);
            if (redirectMatch) {
                window.location.href = redirectMatch[1];
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('❌ An error occurred. Please try again.', 'error');
    })
    .finally(() => {
        // Reset button state
        submitButton.textContent = originalText;
        submitButton.disabled = false;
    });
});
  </script>
</body>
</html>
