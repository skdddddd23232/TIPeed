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
    * {margin:0;padding:0;box-sizing:border-box;font-family:'Arial',sans-serif;}

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
    nav .logo {font-size:22px;font-weight:bold;color:#fff;}
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
  </style>
</head>
<body>
  <!-- Background slideshow -->
  <div class="slideshow">
    <img src="home1.jpg" alt="">
    <img src="home2.jpg" alt="">
    <img src="home3.jpg" alt="">
  </div>
  <div class="overlay"></div>

  <!-- Navbar -->
  <nav>
    <div class="logo">TIPeed</div>
    <ul>
      <li><a href="#">About Us</a></li>
      <li><a href="#">Contact</a></li>
      <li><a href="#" id="navLogin">Login</a></li>
    </ul>
  </nav>

  <!-- Change Password Card -->
  <div class="login-container">
    <div class="login-header"></div>
    <div class="login-box">

        <!-- Change Password Form -->
        <div id="changePasswordForm" class="form-section active">
          <h2>Change Password</h2>
          <form action="faculty_change_password.php" method="POST">
            <div class="password-container">
              <input type="password" id="newPassword" name="new_password" placeholder="New Password" required>
              <i class="fa-solid fa-eye toggle-password" onclick="togglePassword('newPassword', this)"></i>
            </div>
            <div class="password-container">
              <input type="password" id="confirmPassword" name="confirm_password" placeholder="Confirm Password" required>
              <i class="fa-solid fa-eye toggle-password" onclick="togglePassword('confirmPassword', this)"></i>
            </div>
            <button type="submit">Change Password</button>
          </form>
        </div>

    </div>
  </div>

  <!-- Lower left tagline -->
  <div class="tagline">
    <span>LIFELONG LEARNERS</span>
    <span>PROBLEM SOLVERS</span>
    <span>INNOVATORS</span>
  </div>

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
  </script>
</body>
</html>
