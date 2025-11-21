<?php
session_start();

// Check if logged in and is admin
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth.php");
    exit();
}

$admin_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

?>

<?php
// connect to database and fetch reports
include 'db_connect.php';

$reports = [];
$total = $unread = $pending = $resolved = 0;

$sql = "SELECT r.*, t.thread_id AS thread_id 
         FROM reports r 
         LEFT JOIN threads t ON r.thread_id = t.thread_id 
         ORDER BY r.created_at DESC LIMIT 200";
$result = $conn->query($sql);
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
    $total++;
    $status = isset($row['status']) ? strtolower($row['status']) : 'pending';
    if ($status === 'pending') $pending++;
    if ($status === 'resolved') $resolved++;

    if (isset($row['is_read'])) {
      if (!$row['is_read']) $unread++;
    } else {
      // fallback: consider non-resolved reports as unread
      if ($status !== 'resolved') $unread++;
    }
  }
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
  <title>Reports - TiPeed Forum</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/report_css.css">
  <link rel="stylesheet" href="../css/NS.css">
</head>
<body>
  <!-- Navbar -->
  <div class="navbar">
    <div class="logo">TIPeed</div>
    <div class="nav-links">
      <a href="<?= $homePage ?>">Home</a>
      <?php if ($currentUserRole === 'admin'): ?>
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
      <!-- Back Button -->
      <button class="back-btn" onclick="window.location.href='admin_home.php'">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
      </button>

      <!-- Page Header -->
      <div class="page-header">
        <h1 class="page-title">User Reports</h1>
        <div class="report-stats">
          <div class="stat-item">
              <div class="stat-number" id="totalReports"><?php echo $total; ?></div>
            <div class="stat-label">Total</div>
          </div>
          <div class="stat-item">
              <div class="stat-number" id="unreadReports"><?php echo $unread; ?></div>
            <div class="stat-label">Unread</div>
          </div>
          <div class="stat-item">
              <div class="stat-number" id="pendingReports"><?php echo $pending; ?></div>
            <div class="stat-label">Pending</div>
          </div>
          <div class="stat-item">
              <div class="stat-number" id="resolvedReports"><?php echo $resolved; ?></div>
            <div class="stat-label">Resolved</div>
          </div>
        </div>
      </div>

      <!-- Report Filters -->
      <div class="report-filters">
        <button class="filter-btn active">All Reports</button>
        <button class="filter-btn">Unread</button>
        <button class="filter-btn">Pending</button>
        <button class="filter-btn">Resolved</button>
        <button class="filter-btn">Inappropriate</button>
        <button class="filter-btn">Harassment</button>
        <button class="filter-btn">Spam</button>
      </div>

      <!-- Reports Container -->
      <div class="reports-container">
        <div class="report-list">
          <?php if (empty($reports)): ?>
            <div class="empty-state">
              <i class="fas fa-inbox"></i>
              <h3>No reports found</h3>
              <p>There are currently no reports in the system.</p>
            </div>
          <?php else: ?>
            <?php foreach ($reports as $r):
                $r_status = isset($r['status']) ? strtolower($r['status']) : 'pending';
                $r_type = isset($r['report_type']) ? strtolower($r['report_type']) : 'other';
                $class_status = $r_status === 'resolved' ? 'resolved' : 
                              (isset($r['is_read']) && $r['is_read'] ? '' : 'unread');
                $type_class = preg_replace('/[^a-z0-9\-]/', '-', $r_type);

                // choose icon
                switch ($r_type) {
                    case 'inappropriate': $icon = 'fa-exclamation-triangle'; break;
                    case 'spam': $icon = 'fa-ban'; break;
                    case 'technical': $icon = 'fa-bug'; break;
                    default: $icon = 'fa-exclamation-circle';
                }

                $title = htmlspecialchars((!empty($r['report_type']) ? $r['report_type'] : 'Report') . ' - ' . $r['report_id']);
                $message = htmlspecialchars(!empty($r['description']) ? $r['description'] : (substr($r['reported_content'],0,200) ?: 'No description'));
                $reporter = htmlspecialchars($r['reporter_name'] ?? 'Unknown');
                $reported_user = htmlspecialchars($r['reported_user_name'] ?? ($r['reported_user_id'] ?? 'Unknown'));
                $created_at = !empty($r['created_at']) ? date('M j, Y \- H:i', strtotime($r['created_at'])) : '';
            ?>
            <div class="report-item <?php echo $type_class; ?> <?php echo $class_status; ?>" 
                data-report-id="<?php echo htmlspecialchars($r['reportForm_id']); ?>"
                data-status="<?php echo $r_status; ?>"
                data-type="<?php echo $r_type; ?>">
              <div class="report-icon-large">
                <i class="fas <?php echo $icon; ?>"></i>
              </div>
              <div class="report-content">
                <div class="report-title"><?php echo $title; ?></div>
                <div class="report-message"><?php echo $message; ?></div>
                <div class="report-meta">
                  <div class="report-info">
                    <div class="report-user">Reported by: <?php echo $reporter; ?></div>
                    <div class="report-time"><?php echo $created_at; ?></div>
                    <div class="report-type"><?php echo htmlspecialchars($r['report_type'] ?? 'Other'); ?></div>
                    <div class="status-badge <?php echo $r_status === 'pending' ? 'status-pending' : ($r_status === 'resolved' ? 'status-resolved' : 'status-investigating'); ?>"><?php echo ucfirst($r_status); ?></div>
                  </div>
                  <div class="report-actions">
                    <?php if (!empty($r['thread_id'])): ?>
                      <button class="action-btn open-thread-btn" data-thread-id="<?php echo intval($r['thread_id']); ?>">Open Thread</button>
                    <?php endif; ?>
                    <?php if (!empty($r['comment_id'])): ?>
                      <button class="action-btn open-comment-btn" data-thread-id="<?php echo intval($r['thread_id'] ?? 0); ?>" data-comment-id="<?php echo intval($r['comment_id']); ?>">Open Comment</button>
                    <?php endif; ?>
                    <?php if ($r_status !== 'resolved'): ?>
                      <button class="action-btn resolve">Resolve</button>
                    <?php endif; ?>
                    <button class="action-btn view-details" 
                      data-id="<?php echo htmlspecialchars($r['report_id']); ?>"
                      data-type="<?php echo htmlspecialchars($r['report_type']); ?>"
                      data-status="<?php echo htmlspecialchars($r_status); ?>"
                      data-reporter="<?php echo $reporter; ?>"
                      data-reported-user="<?php echo $reported_user; ?>"
                      data-date="<?php echo $created_at; ?>"
                      data-description="<?php echo htmlspecialchars($r['description'] ?? ''); ?>"
                      data-reported-content="<?php echo htmlspecialchars($r['reported_content'] ?? ''); ?>"
                      data-priority="<?php echo htmlspecialchars($r['priority'] ?? ''); ?>"
                    >View Details</button>
                  </div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>

          <!-- Load More Button -->
          <div class="load-more">
            <button class="load-more-btn">Load More Reports</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Report Details Modal -->
  <div class="report-modal" id="reportModal">
    <div class="report-modal-content">
      <div class="report-modal-header">
        <div class="report-modal-title">Report Details</div>
        <button class="close-modal" id="closeModal">&times;</button>
      </div>
      <div class="report-details">
        <div class="detail-row">
          <div class="detail-label">Report ID:</div>
          <div class="detail-value" id="modalReportId"></div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Type:</div>
          <div class="detail-value" id="modalType"></div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Status:</div>
          <div class="detail-value"><span class="status-badge" id="modalStatus"></span></div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Reported By:</div>
          <div class="detail-value" id="modalReporter"></div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Reported User:</div>
          <div class="detail-value" id="modalReportedUser"></div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Date Reported:</div>
          <div class="detail-value" id="modalDateReported"></div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Description:</div>
          <div class="detail-value" id="modalDescription"></div>
        </div>
        <div class="report-content-box">
          <strong>Reported Content:</strong><br>
          <div id="modalReportedContent"></div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Priority:</div>
          <div class="detail-value" id="modalPriority"></div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Assigned To:</div>
          <div class="detail-value" id="modalAssignedTo">Not assigned</div>
        </div>
      </div>
      <div class="report-actions-modal">
        <button class="action-btn delete">Delete Report</button>
        <button class="action-btn assign">Assign to Me</button>
        <button class="action-btn resolve">Mark as Resolved</button>
      </div>
    </div>
  </div>

  <script src="../java/report_js.js"></script>
</body>
</html>