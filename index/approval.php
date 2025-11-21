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
  <title>Community Approvals - TiPeed Forum</title>
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

    .approval-stats {
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

    /* Approval Filters */
    .approval-filters {
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

    /* Approvals Container */
    .approvals-container {
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      overflow: hidden;
    }

    /* Approval List */
    .approval-list {
      display: flex;
      flex-direction: column;
    }

    .approval-item {
      display: flex;
      align-items: flex-start;
      padding: 20px;
      border-bottom: 1px solid #f0f0f0;
      transition: all 0.3s;
      cursor: pointer;
    }

    .approval-item:hover {
      background: #f8f9fa;
    }

    .approval-item.unread {
      background: #fff3cd;
      border-left: 4px solid #f5b301;
    }

    .approval-item.approved {
      background: #f8f9fa;
      opacity: 0.8;
    }

    .approval-item.rejected {
      background: #f8f9fa;
      opacity: 0.8;
    }

    .approval-item:last-child {
      border-bottom: none;
    }

    .approval-icon-large {
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

    .approval-item.community .approval-icon-large {
      background: #6f42c1;
      color: white;
    }

    .approval-content {
      flex: 1;
    }

    .approval-title {
      font-weight: 600;
      margin-bottom: 8px;
      font-size: 16px;
      color: #333;
    }

    .approval-message {
      font-size: 14px;
      color: #666;
      margin-bottom: 10px;
      line-height: 1.5;
    }

    .approval-meta {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .approval-info {
      display: flex;
      gap: 15px;
    }

    .approval-user {
      font-size: 14px;
      color: #007bff;
      font-weight: 500;
    }

    .approval-time {
      font-size: 12px;
      color: #999;
    }

    .approval-type {
      font-size: 12px;
      padding: 4px 8px;
      border-radius: 4px;
      background: #e9ecef;
      color: #495057;
    }

    .approval-actions {
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

    .action-btn.approve {
      background: #28a745;
      color: white;
      border-color: #28a745;
    }

    .action-btn.approve:hover {
      background: #218838;
    }

    .action-btn.reject {
      background: #dc3545;
      color: white;
      border-color: #dc3545;
    }

    .action-btn.reject:hover {
      background: #c82333;
    }

    /* Community Preview */
    .community-preview {
      background: #f8f9fa;
      border-radius: 8px;
      padding: 15px;
      margin: 10px 0;
      border-left: 3px solid #6f42c1;
    }

    .community-preview-title {
      font-weight: 600;
      margin-bottom: 8px;
      font-size: 16px;
      color: #333;
    }

    .community-preview-content {
      font-size: 14px;
      color: #666;
      line-height: 1.5;
      margin-bottom: 10px;
    }

    /* Approval Details Modal */
    .approval-modal {
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

    .approval-modal-content {
      background: white;
      border-radius: 12px;
      padding: 25px;
      width: 90%;
      max-width: 700px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.15);
      max-height: 80vh;
      overflow-y: auto;
    }

    .approval-modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 2px solid #f0f0f0;
    }

    .approval-modal-title {
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

    .approval-details {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .detail-row {
      display: flex;
      gap: 15px;
    }

    .detail-label {
      font-weight: 600;
      color: #333;
      min-width: 120px;
    }

    .detail-value {
      flex: 1;
      color: #666;
    }

    .approval-content-box {
      background: #f8f9fa;
      border-radius: 8px;
      padding: 15px;
      margin: 10px 0;
    }

    .approval-actions-modal {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 20px;
      padding-top: 15px;
      border-top: 1px solid #eee;
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

    /* Status Badges */
    .status-badge {
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: 500;
    }

    .status-pending {
      background: #fff3cd;
      color: #856404;
    }

    .status-approved {
      background: #d4edda;
      color: #155724;
    }

    .status-rejected {
      background: #f8d7da;
      color: #721c24;
    }

    /* User Info */
    .user-info {
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 10px 0;
    }

    .user-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: #f5b301;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: bold;
    }

    .user-details {
      flex: 1;
    }

    .user-name {
      font-weight: 600;
      color: #333;
    }

    .user-email {
      font-size: 12px;
      color: #666;
    }
  </style>
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
      <button class="back-btn" onclick="window.location.href='Admin_home.php'">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
      </button>

      <!-- Page Header -->
      <div class="page-header">
        <h1 class="page-title">Community Approvals</h1>
        <div class="approval-stats">
          <div class="stat-item">
            <div class="stat-number" id="totalCount">0</div>
            <div class="stat-label">Total</div>
          </div>
          <div class="stat-item">
            <div class="stat-number" id="pendingCount">0</div>
            <div class="stat-label">Pending</div>
          </div>
          <div class="stat-item">
            <div class="stat-number" id="approvedCount">0</div>
            <div class="stat-label">Approved</div>
          </div>
          <div class="stat-item">
            <div class="stat-number" id="rejectedCount">0</div>
            <div class="stat-label">Rejected</div>
          </div>
        </div>
      </div>

      <!-- Approval Filters -->
      <div class="approval-filters">
        <button class="filter-btn active" data-filter="all">All Communities</button>
        <button class="filter-btn" data-filter="pending">Pending</button>
        <button class="filter-btn" data-filter="approved">Approved</button>
        <button class="filter-btn" data-filter="rejected">Rejected</button>
      </div>

      <!-- Approvals Container -->
      <div class="approvals-container">
        <div class="approval-list" id="approvalList">
          <!-- Community approvals will be dynamically added here -->
        </div>

        <!-- Load More Button -->
        <div class="load-more">
          <button class="load-more-btn">Load More Approvals</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Approval Details Modal -->
  <div class="approval-modal" id="approvalModal">
    <div class="approval-modal-content">
      <div class="approval-modal-header">
        <div class="approval-modal-title">Community Approval Details</div>
        <button class="close-modal" id="closeModal">&times;</button>
      </div>
      <div class="approval-details" id="modalContent">
        <!-- Content will be dynamically added here -->
      </div>
      <div class="approval-actions-modal">
        <button class="action-btn reject" id="modalRejectBtn">Reject Community</button>
        <button class="action-btn approve" id="modalApproveBtn">Approve Community</button>
      </div>
    </div>
  </div>

  <script>
    // Community approval data structure
    let communityApprovals = JSON.parse(localStorage.getItem('communityApprovals')) || [];
    let currentFilter = 'all';
    let currentModalApprovalId = null;

    // DOM Elements
    const approvalList = document.getElementById('approvalList');
    const approvalModal = document.getElementById('approvalModal');
    const modalContent = document.getElementById('modalContent');
    const closeModal = document.getElementById('closeModal');
    const modalApproveBtn = document.getElementById('modalApproveBtn');
    const modalRejectBtn = document.getElementById('modalRejectBtn');
    const filterBtns = document.querySelectorAll('.filter-btn');
    const totalCount = document.getElementById('totalCount');
    const pendingCount = document.getElementById('pendingCount');
    const approvedCount = document.getElementById('approvedCount');
    const rejectedCount = document.getElementById('rejectedCount');

    // Initialize the page
    function initApprovals() {
      loadPendingCommunities();
      renderApprovals();
      updateStats();
      
      // Event listeners
      closeModal.addEventListener('click', closeApprovalModal);
      modalApproveBtn.addEventListener('click', approveCommunityFromModal);
      modalRejectBtn.addEventListener('click', rejectCommunityFromModal);
      
      // Filter functionality
      filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
          filterBtns.forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
          currentFilter = btn.getAttribute('data-filter');
          renderApprovals();
        });
      });
      
      // Close modal when clicking outside
      approvalModal.addEventListener('click', (e) => {
        if (e.target === approvalModal) {
          closeApprovalModal();
        }
      });
    }

    // Load pending communities from the community system
    async function loadPendingCommunities() {
    const res = await fetch('fetch_pending_communities.php'); // new PHP endpoint
    const data = await res.json();
    if (data.success) {
      communityApprovals = data.communities;
      renderApprovals();
      updateStats();
    }
  }

    // Render approvals based on current filter
    function renderApprovals() {
      approvalList.innerHTML = '';
      
      const filteredApprovals = communityApprovals.filter(approval => {
        if (currentFilter === 'all') return true;
        return approval.status === currentFilter;
      });
      
      if (filteredApprovals.length === 0) {
        approvalList.innerHTML = `
          <div class="empty-state">
            <i class="fas fa-check-circle"></i>
            <h3>No ${currentFilter === 'all' ? '' : currentFilter} community approvals</h3>
            <p>${currentFilter === 'pending' ? 'All communities have been reviewed!' : 'No communities match your filter.'}</p>
          </div>
        `;
        return;
      }
      
      filteredApprovals.forEach(approval => {
        const approvalItem = document.createElement('div');
        approvalItem.className = `approval-item community ${approval.status === 'pending' ? 'unread' : approval.status}`;
        approvalItem.setAttribute('data-id', approval.id);
        
        // Generate avatar initials
        const avatarInitials = approval.username.split(' ').map(n => n[0]).join('').toUpperCase();
        
        approvalItem.innerHTML = `
          <div class="approval-icon-large">
            <i class="fas fa-users"></i>
          </div>
          <div class="approval-content">
            <div class="approval-title">New Community: "${approval.name}"</div>
            <div class="approval-message">
              User has requested to create a new community. Please review the details before approval.
            </div>
            <div class="user-info">
              <div class="user-avatar">${avatarInitials}</div>
              <div class="user-details">
                <div class="user-name">${approval.username}</div>
                <div class="user-email">${approval.email}</div>
              </div>
            </div>
            <div class="community-preview">
              <div class="community-preview-title">${approval.name}</div>
              <div class="community-preview-content">${approval.description || 'No description provided'}</div>
              <div class="community-meta">
                <span><i class="fas fa-tag"></i> ${approval.category}</span>
                <span><i class="fas fa-lock"></i> ${approval.privacy}</span>
              </div>
            </div>
            <div class="approval-meta">
              <div class="approval-info">
                <div class="approval-time">${approval.timestamp}</div>
                <div class="approval-type">Community Request</div>
                <div class="status-badge status-${approval.status}">${approval.status.charAt(0).toUpperCase() + approval.status.slice(1)}</div>
              </div>
              <div class="approval-actions">
                ${approval.status === 'pending' ? `
                  <button class="action-btn approve">Approve</button>
                  <button class="action-btn reject">Reject</button>
                ` : ''}
                <button class="action-btn">View Details</button>
              </div>
            </div>
          </div>
        `;
        
        approvalList.appendChild(approvalItem);
        
        // Add event listeners to action buttons
        const viewDetailsBtn = approvalItem.querySelector('.action-btn:last-child');
        const approveBtn = approvalItem.querySelector('.action-btn.approve');
        const rejectBtn = approvalItem.querySelector('.action-btn.reject');
        
        viewDetailsBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          showApprovalDetails(approval.id);
        });
        
        if (approveBtn) {
          approveBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            approveCommunity(approval.id);
          });
        }
        
        if (rejectBtn) {
          rejectBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            rejectCommunity(approval.id);
          });
        }
        
        // Clicking the entire item shows details
        approvalItem.addEventListener('click', () => {
          showApprovalDetails(approval.id);
        });
      });
    }

    // Show approval details in modal
    function showApprovalDetails(approvalId) {
      const approval = communityApprovals.find(a => a.id === approvalId);
      if (!approval) return;
      
      currentModalApprovalId = approvalId;
      
      // Generate avatar initials
      const avatarInitials = approval.username.split(' ').map(n => n[0]).join('').toUpperCase();
      
      modalContent.innerHTML = `
        <div class="detail-row">
          <div class="detail-label">Community ID:</div>
          <div class="detail-value">#COM-${approval.communityId}</div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Status:</div>
          <div class="detail-value"><span class="status-badge status-${approval.status}">${approval.status.charAt(0).toUpperCase() + approval.status.slice(1)}</span></div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Submitted By:</div>
          <div class="detail-value">${approval.username} (${approval.email})</div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Date Submitted:</div>
          <div class="detail-value">${approval.timestamp}</div>
        </div>
        <div class="user-info">
          <div class="user-avatar">${avatarInitials}</div>
          <div class="user-details">
            <div class="user-name">${approval.username}</div>
            <div class="user-email">${approval.email}</div>
          </div>
        </div>
        <div class="approval-content-box">
          <strong>Community Name:</strong><br>
          ${approval.name}
        </div>
        <div class="approval-content-box">
          <strong>Community Description:</strong><br>
          ${approval.description || 'No description provided'}
        </div>
        <div class="detail-row">
          <div class="detail-label">Category:</div>
          <div class="detail-value">${approval.category}</div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Privacy:</div>
          <div class="detail-value">${approval.privacy}</div>
        </div>
        ${approval.reviewedBy ? `
          <div class="detail-row">
            <div class="detail-label">Reviewed By:</div>
            <div class="detail-value">${approval.reviewedBy}</div>
          </div>
          <div class="detail-row">
            <div class="detail-label">Review Date:</div>
            <div class="detail-value">${approval.reviewDate}</div>
          </div>
        ` : ''}
      `;
      
      // Update modal buttons based on status
      if (approval.status === 'pending') {
        modalApproveBtn.style.display = 'block';
        modalRejectBtn.style.display = 'block';
      } else {
        modalApproveBtn.style.display = 'none';
        modalRejectBtn.style.display = 'none';
      }
      
      approvalModal.style.display = 'flex';
    }

    // Close approval modal
    function closeApprovalModal() {
      approvalModal.style.display = 'none';
      currentModalApprovalId = null;
    }

    // Approve community from list
  function approveCommunity(id) {
    fetch('update_community_status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        communityId: id,
        action: 'approve'
      })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const community = communityApprovals.find(a => a.id === id);
        if (community) community.status = data.status;
        renderApprovals();
        updateStats();
        alert('✅ Community approved successfully!');
      } else {
        alert('❌ Error: ' + data.message);
      }
    })
    .catch(err => console.error(err));
  }

  function rejectCommunity(id) {
    fetch('update_community_status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        communityId: id,
        action: 'reject'
      })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const community = communityApprovals.find(a => a.id === id);
        if (community) community.status = data.status;
        renderApprovals();
        updateStats();
        alert('🚫 Community rejected successfully!');
      } else {
        alert('❌ Error: ' + data.message);
      }
    })
    .catch(err => console.error(err));
  }

    // Approve community from modal
    function approveCommunityFromModal() {
      if (!currentModalApprovalId) return;
      approveCommunity(currentModalApprovalId);
      closeApprovalModal();
    }

    function rejectCommunityFromModal() {
      if (!currentModalApprovalId) return;
      rejectCommunity(currentModalApprovalId);
      closeApprovalModal();
    }


    // Update statistics
    function updateStats() {
      const total = communityApprovals.length;
      const pending = communityApprovals.filter(a => a.status === 'pending').length;
      const approved = communityApprovals.filter(a => a.status === 'approved').length;
      const rejected = communityApprovals.filter(a => a.status === 'rejected').length;
      
      totalCount.textContent = total;
      pendingCount.textContent = pending;
      approvedCount.textContent = approved;
      rejectedCount.textContent = rejected;
    }

    // Show success message
    function showSuccessMessage(message) {
      // Create a temporary success message
      const successDiv = document.createElement('div');
      successDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #28a745;
        color: white;
        padding: 12px 20px;
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 1001;
        display: flex;
        align-items: center;
        gap: 10px;
      `;
      successDiv.innerHTML = `
        <i class="fas fa-check-circle"></i>
        <span>${message}</span>
      `;
      
      document.body.appendChild(successDiv);
      
      setTimeout(() => {
        document.body.removeChild(successDiv);
      }, 3000);
    }

    // Left sidebar toggle
    const sidebar = document.getElementById('sidebar');
    const toggleSidebar = document.getElementById('toggleSidebar');
    toggleSidebar.addEventListener('click', () => sidebar.classList.toggle('expanded'));

    // Initialize the page when loaded
    document.addEventListener('DOMContentLoaded', initApprovals);

    function updateCommunityStatus(communityId, status) {
      if (!confirm(`Are you sure you want to ${status} this community?`)) return;

      // Create a form data object
          const formData = new FormData();
          formData.append('communityId', communityId);
          formData.append('status', status);

          // Send it to your PHP file
          fetch('update_community_status.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              alert(`✅ Community ${status} successfully!`);
              loadPendingCommunities(); // reload the list
            } else {
              alert('❌ Error: ' + data.message);
            }
          })
          .catch(error => {
            console.error('Fetch error:', error);
          });
        }

  </script>
</body>
</html>