<?php
session_start();

// Check if logged in and is admin
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth.php");
    exit();
}

$admin_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
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
  <title>AdminCreate</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/NS.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; text-decoration: none;}
    html, body { background: #f9fafb; color: #222; text-decoration: none;  overflow: hidden;}

    

    /* Chat Layout */
    .reg-container { flex: 1; display: flex; background: #f5f6f7; }
    .group-list { width: 280px; border-right: 1px solid #ddd; background: #fff; overflow-y: auto; }
    .group-list h3 { padding: 15px; border-bottom: 1px solid #eee; font-size: 16px; }
    .group { display: flex; align-items: center; padding: 12px 15px; cursor: pointer; transition: 0.2s; }
    .group:hover { background: #f6f7f8; }
    .group img { width: 40px; height: 40px; border-radius: 50%; margin-right: 10px; }
    .group .name { font-weight: 500; }

    .reg-area { flex: 1; display: flex; flex-direction: column; }
    .chat-header { display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid #ddd; background: #fff; }
    .chat-header h2 { font-size: 16px; }
    .chat-header .icons i { margin-left: 15px; font-size: 18px; cursor: pointer; color: #555; }

    /* Course Chat Header */
    .course-chat-header {
      background: linear-gradient(135deg, #f5b301, #e0a500);
      color: white;
      padding: 10px 10px;
      border-radius: 10px 10px 0 0;
      margin: 20px 20px 0 ;
      box-shadow: 0 4px 15px rgba(245, 179, 1, 0.3);
    }

    .course-chat-header h1 {
      font-size: 20px;
      font-weight: bold;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .course-chat-header p {
      font-size: 16px;
      opacity: 0.9;
      margin: 0;
    }

    /* Course Chat Details Styles */
    .course-details-container {
      flex: 1;
      padding: 20px;
      background: #fff;
      border-radius: 10px;
      margin: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .course-header {
      display: flex;
      align-items: flex-start;
      gap: 20px;
      padding-bottom: 20px;
      border-bottom: 2px solid #f0f0f0;
    }

    .course-info {
      flex: 1;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      font-weight: 500;
      color: #333;
      margin-bottom: 5px;
      font-size: 14px;
    }

    .form-input {
      width: 100%;
      padding: 10px 12px;
      border: 2px solid #e9ecef;
      border-radius: 6px;
      font-size: 14px;
      transition: border-color 0.3s ease;
    }

    .form-input:focus {
      outline: none;
      border-color: #f5b301;
    }

    .form-select {
      width: 100%;
      padding: 10px 12px;
      border: 2px solid #e9ecef;
      border-radius: 6px;
      font-size: 14px;
      background: white;
      cursor: pointer;
      transition: border-color 0.3s ease;
    }

    .form-select:focus {
      outline: none;
      border-color: #f5b301;
    }

    .course-settings {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 8px;
      border: 1px solid #e9ecef;
    }

    .settings-title {
      font-size: 18px;
      font-weight: 600;
      color: #333;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .settings-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-bottom: 20px;
    }

    .setting-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px;
      background: white;
      border-radius: 6px;
      border: 1px solid #dee2e6;
    }

    .setting-item label {
      font-weight: 500;
      color: #495057;
      cursor: pointer;
    }

    .setting-item input[type="checkbox"] {
      width: 18px;
      height: 18px;
      accent-color: #f5b301;
    }

    .save-btn {
      padding: 12px 30px;
      background: grey;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 500;
      font-size: 16px;
      transition: background 0.3s;
      margin-top: 20px;
    }

    .save-btn:hover {
      background: #e0a500;
    }

    .name-row {
      display: flex;
      gap: 15px;
    }

    .name-row .form-group {
      flex: 1;
    }

    .email-row {
      display: flex;
      gap: 15px;
    }

    .email-row .form-group {
      flex: 1;
    }

    /* Selected Courses Display */
    .selected-courses-display {
      margin-top: 10px;
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }

    .course-tag {
      display: flex;
      align-items: center;
      padding: 8px 12px;
      background: #f8f9fa;
      border-radius: 6px;
      border-left: 4px solid #f5b301;
      font-size: 14px;
      color: #495057;
    }

    .course-tag .remove-course {
      margin-left: 8px;
      color: #dc3545;
      cursor: pointer;
      font-size: 14px;
      background: none;
      border: none;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 18px;
      height: 18px;
      border-radius: 50%;
    }

    .course-tag .remove-course:hover {
      background: #dc3545;
      color: white;
    }

    .no-courses-message {
      color: #6c757d;
      font-style: italic;
      font-size: 14px;
    }

    /* Success Popup */
    .success-popup {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.3);
      text-align: center;
      z-index: 1000;
      min-width: 350px;
      display: none;
    }

    .success-popup.show {
      display: block;
      animation: popIn 0.3s ease-out;
    }

    .success-icon {
      font-size: 48px;
      color: #28a745;
      margin-bottom: 15px;
    }

    .success-popup h2 {
      color: #333;
      margin-bottom: 10px;
      font-size: 24px;
    }

    .success-popup p {
      color: #666;
      margin-bottom: 20px;
      font-size: 16px;
    }

    .success-popup .close-btn {
      padding: 10px 25px;
      background: #f5b301;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 500;
      transition: background 0.3s;
    }

    .success-popup .close-btn:hover {
      background: #e0a500;
    }

    .overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 999;
      display: none;
    }

    .overlay.show {
      display: block;
    }

    /* Confetti container */
    .confetti-container {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      z-index: 1001;
    }

    .confetti {
      position: absolute;
      width: 10px;
      height: 10px;
      background: #f5b301;
      opacity: 0;
    }

    .confetti:nth-child(2n) {
      background: #ff6b6b;
    }

    .confetti:nth-child(3n) {
      background: #4ecdc4;
    }

    .confetti:nth-child(4n) {
      background: #45b7d1;
    }

    .confetti:nth-child(5n) {
      background: #96ceb4;
    }

    @keyframes popIn {
      0% {
        opacity: 0;
        transform: translate(-50%, -50%) scale(0.8);
      }
      100% {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1);
      }
    }

    @keyframes confetti-fall {
      0% {
        opacity: 1;
        transform: translateY(-100px) rotate(0deg);
      }
      100% {
        opacity: 0;
        transform: translateY(1000px) rotate(360deg);
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

    <!-- REG Container -->
    <div class="reg-container">
      <!-- Chat Area -->
      <div class="reg-area">
        <!-- Create Course Chat Header -->
        <div class="course-chat-header">
          <h1>
            <div class="header-icon">
            </div>
            Admin
          </h1>
        </div>
        <!-- Course Chat Details Section -->
        <div class="course-details-container">
          <!-- First Section: Course Header with Inputs -->
          <div class="course-header">
            <div class="course-info">
              <div class="name-row">
                <div class="form-group">
                  <label for="firstName">First Name</label>
                  <input type="text" id="firstName" class="form-input" placeholder="Enter first name">
                </div>
                <div class="form-group">
                  <label for="lastName">Last Name</label>
                  <input type="text" id="lastName" class="form-input" placeholder="Enter last name">
                </div>
              </div>
              
              <div class="email-row">
                <div class="form-group">
                  <label for="email">Email</label>
                  <input type="email" id="email" class="form-input" placeholder="Enter email">
                </div>
                <div class="form-group">
                  <label for="secondEmail">Second Email (Optional)</label>
                  <input type="email" id="secondEmail" class="form-input" placeholder="Enter second email">
                </div>
              </div>
              
              <div class="form-group">
                <label for="courseCode">Course Code - Title</label>
                <select id="courseCode" class="form-select">
                  <option value="">Loading CCS Courses...</option>
                </select>

                <div class="selected-courses-display" id="selectedCoursesDisplay">
                  <div class="no-courses-message">No courses selected yet</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Second Section: Course Settings -->
          <div class="course-settings">
            <div class="settings-title">
              <i class="fas fa-cog"></i>
              Group Management Settings
            </div>
            
            <div class="settings-grid">
              <div class="setting-item">
                <input type="checkbox" id="coAdmin" checked>
                <label for="coAdmin">Co-Admin</label>
              </div>
            </div>

            <button class="save-btn" id="saveBtn">
              <i class="fa fa-plus"></i> Create 
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Success Popup -->
  <div class="overlay" id="overlay"></div>
  <div class="success-popup" id="successPopup">
    <div class="success-icon">
      <i class="fas fa-check-circle"></i>
    </div>
    <h2>Awesome!</h2>
    <p id="successMessage">Your First name, Last NAME accounty patootie is set up!</p>
    <button class="close-btn" id="closePopup">Continue</button>
  </div>

  <!-- Confetti Container -->
  <div class="confetti-container" id="confettiContainer"></div>

  <script>
    // Left sidebar toggle
    const sidebar = document.getElementById('sidebar');
    const toggleSidebar = document.getElementById('toggleSidebar');
    toggleSidebar.addEventListener('click', () => sidebar.classList.toggle('expanded'));

    // Course selection functionality
    const courseCodeSelect = document.getElementById('courseCode');
    const selectedCoursesDisplay = document.getElementById('selectedCoursesDisplay');
    let selectedCourses = []; // array of {id: number, name: string}

    courseCodeSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const courseId = parseInt(selectedOption.value); // numeric ID
        const courseName = selectedOption.textContent;

        if (!isNaN(courseId) && !selectedCourses.some(c => c.id === courseId)) {
            selectedCourses.push({id: courseId, name: courseName});
            updateSelectedCoursesDisplay();
            this.selectedIndex = 0;
        }
    });


      function updateSelectedCoursesDisplay() {
        selectedCoursesDisplay.innerHTML = '';

        if (selectedCourses.length === 0) {
            selectedCoursesDisplay.innerHTML = '<div class="no-courses-message">No courses selected yet</div>';
            return;
        }

        selectedCourses.forEach((course, index) => {
            const courseTag = document.createElement('div');
            courseTag.className = 'course-tag';
            courseTag.innerHTML = `
                ${course.name}
                <button class="remove-course" data-index="${index}">
                    <i class="fas fa-times"></i>
                </button>
            `;
            selectedCoursesDisplay.appendChild(courseTag);
        });

        document.querySelectorAll('.remove-course').forEach(button => {
            button.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'));
                selectedCourses.splice(index, 1);
                updateSelectedCoursesDisplay();
            });
        });
    }



    // Confetti function
    function createConfetti() {
      const confettiContainer = document.getElementById('confettiContainer');
      confettiContainer.innerHTML = '';
      
      for (let i = 0; i < 150; i++) {
        const confetti = document.createElement('div');
        confetti.className = 'confetti';
        
        // Random position and styling
        const left = Math.random() * 100;
        const animationDelay = Math.random() * 3;
        const animationDuration = 2 + Math.random() * 3;
        
        confetti.style.left = `${left}%`;
        confetti.style.animation = `confetti-fall ${animationDuration}s ease-in ${animationDelay}s forwards`;
        
        confettiContainer.appendChild(confetti);
      }
      
      // Remove confetti after animation
      setTimeout(() => {
        confettiContainer.innerHTML = '';
      }, 6000);
    }

    // Save button functionality
    const saveBtn = document.getElementById('saveBtn');
    const successPopup = document.getElementById('successPopup');
    const overlay = document.getElementById('overlay');
    const closePopup = document.getElementById('closePopup');
    const successMessage = document.getElementById('successMessage');

    saveBtn.addEventListener('click', () => {
      const firstName = document.getElementById('firstName').value.trim();
      const lastName = document.getElementById('lastName').value.trim();
      const email = document.getElementById('email').value.trim();
      const secondEmail = document.getElementById('secondEmail').value.trim();
      const isCoAdmin = document.getElementById('coAdmin').checked ? 1 : 0;

      if (!firstName || !lastName || !email || selectedCourses.length === 0) {
        alert('Please fill in all required fields: First Name, Last Name, Email, and select at least one course.');
        return;
      }

      // Send data to backend
      fetch('create_faculty.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({
              firstName,
              lastName,
              email,
              courses: JSON.stringify(selectedCourses.map(c => c.id)), // send only IDs
              isCoAdmin: isCoAdmin
          })
      })
        .then(res => res.json())
        .then(data => {
          if (data.status === 'success' || data.status === 'warning') {
            createConfetti();
            successMessage.textContent = data.message || `Account for ${firstName} ${lastName} created successfully!`;
            successPopup.classList.add('show');
            overlay.classList.add('show');
          } else {
            alert(data.message || 'Error creating faculty account.');
          }
        })
        .catch(err => {
          console.error(err);
          alert('Server error — please try again later.');
        });
    });

    // Close popup functionality
    closePopup.addEventListener('click', () => {
      successPopup.classList.remove('show');
      overlay.classList.remove('show');
    });

    // Close popup when clicking overlay
    overlay.addEventListener('click', () => {
      successPopup.classList.remove('show');
      overlay.classList.remove('show');
    });


    const courseSelect = document.getElementById("courseCode");
    courseSelect.innerHTML = '<option value="">Loading CCS Courses...</option>';

    fetch("get_course.php")
      .then(response => response.json())
      .then(data => {
        courseSelect.innerHTML = '<option value="">Select a CCS Course...</option>';
        data.forEach(course => {
          const option = document.createElement("option");
          option.value = course.course_id; // <-- use numeric ID
          option.textContent = `${course.course_code} - ${course.course_name}`;
          courseSelect.appendChild(option);
        });
      })
      .catch(error => {
        console.error("Error loading CCS courses:", error);
        courseSelect.innerHTML = '<option value="">Failed to load courses</option>';
      });
  </script>
</body>
</html>