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
  <title>TiPeed Forum</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/NS.css">
  

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
    <!-- Sidebar -->
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
        <a href="profile.php"class="menu-item active"><div class="menu-icon"><i class="fas fa-user"></i></div><div class="menu-text">Profile</div></a>
        <a href="<?= $homePage ?>"  class="menu-item"><div class="menu-icon"><i class="fas fa-home"></i></div><div class="menu-text">Home</div></a>
        <a href="chat_interface.php" class="menu-item"><div class="menu-icon"><i class="fas fa-comment-dots"></i></div><div class="menu-text">Course Chat</div></a>
        <a href="CourseChat.php" class="menu-item"><div class="menu-icon"><i class="fas fa-comments"></i></div><div class="menu-text">Communities Chat</div></a>
        <a href="Community.php" class="menu-item"><div class="menu-icon"><i class="fas fa-users"></i></div><div class="menu-text">Community</div></a>
        <?php if ($currentUserRole === 'admin'): ?>
        <a href="admin_reg.php" class="menu-item"><div class="menu-icon"><i class="fas fa-user-plus"></i></div><div class="menu-text">Register</div></a>
        <?php endif; ?>
        <a href="calendar.php" class="menu-item"><div class="menu-icon"><i class="fas fa-calendar-alt"></i></div><div class="menu-text">Calendar</div></a>
        <div class="menu-item"><div class="menu-icon"><i class="fas fa-cog"></i></div><div class="menu-text">Settings</div></div>
        <a href="Help.php" class="menu-item"><div class="menu-icon"><i class="fas fa-question-circle"></i></div><div class="menu-text">Help</div></a>
        <a href="logout.php" class="menu-item"><div class="menu-icon"><i class="fas fa-sign-out-alt"></i></div><div class="menu-text">Log Out</div></a>
      </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
      <!-- Header -->
      <div class="header">
        <div class="slider">
          <div class="slides">
            <div class="slide active" style="background-image: url('../assets/home2.jpg');"></div>
            <div class="slide" style="background-image: url('../assets/2.jpg');"></div>
            <div class="slide" style="background-image: url('../assets/home1.jpg');"></div>
            <div class="slide" style="background-image: url('../assets/1.jpg');"></div>
            <div class="slide" style="background-image: url('../assets/home3.jpg');"></div>
            <div class="slide" style="background-image: url('../assets/3.jpg');"></div>
          </div>

          <!-- Controls -->
          <div class="controls">
            <span class="prev">&#10094;</span>
            <span class="next">&#10095;</span>
          </div>

          <!-- Dots -->
          <div class="dots"></div>
        </div>

        <div class="header-content">
          <h1>Welcome to TiPeed</h1>
          <p>Clean and simple community discussion</p>
        </div>
      </div>

      <!-- Trending Section -->
      <div class="trending">
        <h2>Trending Today</h2>
        <div class="cards">
         <?php
          // Fetch top threads today by positive votes only
          $sqlTrending = "
            SELECT t.thread_id, t.title, t.image_path,
                  u.first_name, u.last_name,
                  COALESCE(SUM(CASE WHEN v.vote='up' THEN 1
                                    WHEN v.vote='down' THEN -1
                                    ELSE 0 END), 0) AS total_votes
            FROM threads t
            JOIN users u ON t.user_id = u.userid
            LEFT JOIN thread_vote v ON t.thread_id = v.thread_id
            WHERE DATE(t.created_at) = CURDATE()
            GROUP BY t.thread_id
            HAVING total_votes > 0
            ORDER BY total_votes DESC
            LIMIT 4
          ";

          $resultTrending = mysqli_query($conn, $sqlTrending);

          if ($resultTrending && mysqli_num_rows($resultTrending) > 0) {
              while ($row = mysqli_fetch_assoc($resultTrending)) {
                  $author = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
                  $title  = htmlspecialchars($row['title']);
                  $img = !empty($row['image_path']) ? htmlspecialchars($row['image_path']) : 'Suspended.jpg';

                  echo <<<HTML
                  <div class="card trending-card" data-thread-id="{$row['thread_id']}">
                    <img src="{$img}" alt="Thread image">
                    <div class="card-content">
                      <h3>{$title}</h3>
                      <p>Posted by {$author}</p>
                      <p>Votes: {$row['total_votes']}</p>
                    </div>
                  </div>
                  HTML;

              }
          } else {
              // No threads with positive votes
              echo "<div class='no-trending' style='padding:20px; text-align:center; color:#666; font-size:16px;'>
                      No trending threads today.
                    </div>";
          }
        ?>

        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
          <div class="filters">
            <div class="filter green" data-filter="popular"><i class="fa fa-star"></i> Most Popular</div>
            <div class="filter blue" data-filter="votes"><i class="fa fa-arrow-up"></i> Highest Votes</div>
            <div class="filter orange" data-filter="latest"><i class="fa fa-file-alt"></i> Latest Thread</div>

          </div>
          <button class="new-thread" id="newThreadBtn" onclick="openPopup()"><i class="fa fa-pen"></i> Write New Thread</button>
        </div>
      </div>

      <!-- Threads & Discussions Section -->
      <div class="threads-section">
        <h2>Threads & Discussion</h2>
        <div id="threadList">
          <!-- Threads will be dynamically added here -->
    
            <?php
            // ensure DB connection is available (only include once in the file)
            if (!isset($conn)) {
                include 'db_connect.php';
            }

            // fetch threads and show them inside the threads-section
            $sql = "SELECT t.thread_id, t.title, t.content, t.image_path, t.created_at, t.user_id,
                        u.first_name, u.last_name,
                        COALESCE(SUM(
                            CASE WHEN v.vote = 'up' THEN 1 
                                  WHEN v.vote = 'down' THEN -1 
                                  ELSE 0 END
                        ),0) AS total_votes,
                        (SELECT COUNT(*) FROM comments c WHERE c.thread_id = t.thread_id) AS total_comments
                  FROM threads t
                  JOIN users u ON t.user_id = u.userid
                  LEFT JOIN thread_vote v ON t.thread_id = v.thread_id
                  GROUP BY t.thread_id
                  ORDER BY t.created_at DESC";


            $result = mysqli_query($conn, $sql);

            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $author = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
                    $avatar = strtoupper(substr($author, 0, 2));
                    $title  = htmlspecialchars($row['title']);
                    $content = nl2br(htmlspecialchars($row['content']));
                    $time = htmlspecialchars($row['created_at']);
                    $imgHtml = '';
                    if (!empty($row['image_path'])) {
                        $img = htmlspecialchars($row['image_path']);
                        $imgHtml = "<img src=\"{$img}\" class=\"thread-image\" alt=\"Thread image\">";
                    }

                    $commentHtml = '';
                    if ($row['total_comments'] > 0) {
                        $count = $row['total_comments'];
                        $plural = $count > 1 ? 's' : '';
                        $commentHtml = "<span style='margin-left:auto; font-size:12px; color:#666;'>$count comment{$plural}</span>";
                    }

                    // build menu dropdown only once
                      $menuDropdown = "
                      <div class='menu-wrapper'>
                          <button class='menu-btn'><i class='fas fa-ellipsis-v'></i></button>
                          <div class='menu-dropdown'>
                              <button class='menu-item report-btn' 
                                      data-id='{$row['thread_id']}' 
                                      data-user-id='{$row['user_id']}' 
                                      data-username='{$author}'>
                                  <i class='fas fa-flag'></i> Report
                              </button>
                              <button class='menu-item share-btn' data-id='{$row['thread_id']}'>
                                  <i class='fas fa-share'></i> Share
                              </button>";
                      // Allow both admins and thread authors to delete
                      if ($isAdmin || (int)$_SESSION['userid'] === (int)$row['user_id']) {
                          $menuDropdown .= "
                              <button class='menu-item delete-btn' data-id='{$row['thread_id']}'>
                                  <i class='fas fa-trash'></i> Delete
                              </button>";
                      }
                      $menuDropdown .= "
                          </div>
                      </div>";


                    echo <<<HTML
                      <div class="thread-container" 
                        data-id="{$row['thread_id']}" 
                        data-time="{$time}" 
                        data-votes="{$row['total_votes']}"  
                        data-popularity="{$row['total_votes']}">
                        <div class="thread-header">
                          <div class="thread-avatar">{$avatar}</div>
                          <div class="thread-meta">
                              <div class="thread-author">{$author}</div>
                              <div class="thread-time">{$time}</div>
                          </div>
                          {$menuDropdown}
                      </div>

                        <div class="thread-content">
                          <h3 class="thread-title">{$title}</h3>
                          <div class="thread-body">{$content}</div>
                          {$imgHtml}
                        </div>

                        <div class="thread-footer">
                          <div class="vote-buttons">
                            <button class="vote-btn upvote">▲</button>
                            <span class="vote-count">{$row['total_votes']}</span>
                            <button class="vote-btn downvote">▼</button>
                          </div>
                          <div class="thread-actions">
                            <button class="action-btn comment-btn"><i class="far fa-comment"></i> Comment</button>
                            <button class="action-btn"><i class="fas fa-share"></i> Share</button>
                            <button class="action-btn"><i class="far fa-bookmark"></i> Save</button>
                          </div>
                          {$commentHtml}
                        </div>
                      </div>
            HTML;
                }
            } else {
                echo "<p style=\"padding:12px 16px; color:#666\">No threads yet. Be the first to post!</p>";
            }
            ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Right Sidebar -->
  

  <!-- Popup for new thread -->
  <div id="popup">
    <div id="popupContent">
      <h3>Create a New Thread</h3>
      <form action="create_thread.php" method="POST" enctype="multipart/form-data">
        <input type="text" name="title" id="threadTitle" placeholder="Title" required>
        <textarea name="content" id="threadInput" placeholder="What's on your mind?" required></textarea>
        <input type="file" name="image" id="imageInput" accept="image/*">
        <br>
        <small style="color:gray;">(Optional: attach an image)</small><br><br>
        <div class="popup-buttons">
          <button type="button" id="cancelBtn" class="btn btn-secondary">Cancel</button>
          <button type="submit" class="btn btn-primary">Post</button>
        </div>
      </form>
    </div>
</div>

<div class="report-modal" id="reportModal" style="display: none;">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title">Submit a Report</h2>
          <button class="close-modal" id="closeReportModal">&times;</button>
        </div>
        <div class="modal-body">
          <form class="report-form" id="reportForm">
            <input type="hidden" id="reportedUserId" name="reported_user_id">
            <input type="hidden" id="locationType" name="location_type">
            <input type="hidden" id="locationId" name="location_id">
            <input type="hidden" id="reportedUserName" name="reported_user_name">

            <label>Type</label>
            <select id="reportCategory" name="report_type" required>
              <option value="">Select type</option>
              <option value="inappropriate">Inappropriate Content</option>
              <option value="spam">Spam</option>
              <option value="harassment">Harassment</option>
              <option value="other">Other</option>
            </select>

            <label>Priority</label>
            <select id="reportPriority" name="priority" required>
              <option value="low">Low</option>
              <option value="medium" selected>Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>

            <label>Description</label>
            <textarea id="reportDescription" name="description" placeholder="Describe the issue..." required></textarea>

            <div class="form-actions">
              <button type="button" class="btn btn-secondary" id="cancelReport">Cancel</button>
              <button type="submit" class="btn btn-primary">Submit Report</button>
            </div>
          </form>
        </div>
      </div>
    </div>


<script src="../java/script.js" defer></script>
</body>
</html>