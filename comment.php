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

// Get thread_id and comment_id from URL parameters
$threadId = isset($_GET['thread_id']) ? $_GET['thread_id'] : null;
$commentId = isset($_GET['comment_id']) ? $_GET['comment_id'] : null;

// Store them in localStorage via JavaScript
$storeParams = "";
if ($threadId) {
    $storeParams .= "localStorage.setItem('currentThreadId', '$threadId');";
}
if ($commentId) {
    $storeParams .= "localStorage.setItem('currentCommentId', '$commentId');";
}



$studentName   = $_SESSION['first_name'] . " " . $_SESSION['last_name'];
$studentCourse = isset($_SESSION['course']) ? $_SESSION['course'] : "No course assigned"; 
// Pass user info and parameters to JS
$currentUserId = $_SESSION['userid'];
$currentUserRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';

if ($currentUserRole === 'admin') {
    $homePage = 'admin_home.php';
} else if ($currentUserRole === 'teacher') {
    $homePage = 'faculty_home.php';
} else {
    $homePage = 'student_home.php';
}

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
  <title>Thread Comments - TiPeed Forum</title>
  <script>
    <?php echo $storeParams; ?>
  </script>
  <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css'); ?>">
  <script src="assets/js/app.js?v=<?= filemtime('assets/js/app.js'); ?>"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/comment_css.css">
  <link rel="stylesheet" href="../css/comment_css_2.css">
  <link rel="stylesheet" href="../css/NS.css">

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
      <!-- Back Button -->
      <div class="back-btn" id="backBtn">
        <i class="fas fa-arrow-left"></i>
        <span>Back to Threads</span>
      </div>

      <!-- Thread Container -->
      <div class="thread-container" id="threadContainer">
        <!-- Thread content will be loaded here -->


      </div>

      <!-- Comments Section -->
      <div class="comments-section">
        <div class="comments-header">
          <h2 class="comments-title">Comments</h2>
          <span class="comments-count" id="commentsCount">0 comments</span>
        </div>

        <!-- Comment Form -->
        <div class="comment-form">
          <textarea class="comment-input" id="commentInput" placeholder="Add a comment..."></textarea>
          <button class="comment-submit" id="commentSubmit">Post Comment</button>
        </div>

        <!-- Comments List -->
        <div class="comments-list" id="commentsList">
          <!-- Comments will be loaded here -->
        </div>
      </div>
    </div>
  </div>

    <!-- Report Modal -->
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

<!-- JavaScript -->

<script>
    // --------------------
    // Pass PHP data to JS
    // --------------------
    const currentUserId = <?php echo json_encode($currentUserId); ?>;
    const currentUserRole = <?php echo json_encode($currentUserRole); ?>;

    // --------------------
    // Sidebar toggle
    // --------------------
    const sidebar = document.getElementById('sidebar');
    const toggleSidebar = document.getElementById('toggleSidebar');
    toggleSidebar.addEventListener('click', () => {
        sidebar.classList.toggle('expanded');
    });

    document.getElementById('backBtn').addEventListener('click', () => {
        window.location.href = 'student_home.php';
    });

    // --------------------
    // Thread & comments loader
    // --------------------
    function getInitials(name) {
        if (!name || typeof name !== "string") return "?";
        const parts = name.trim().split(" ").filter(Boolean);
        const firstName = parts[0] || "";
        return firstName.substring(0, 2).toUpperCase();
    }

    function getCurrentThreadId() {
        // First try URL parameter
        const urlThreadId = new URLSearchParams(window.location.search).get('thread_id');
        if (urlThreadId && urlThreadId !== '0') {
            // Update localStorage to match URL
            localStorage.setItem('currentThreadId', urlThreadId);
            return urlThreadId;
        }
        // Fallback to localStorage if URL thread_id is 0 or not present
        const storedId = localStorage.getItem('currentThreadId');
        if (storedId) return storedId;
        // If no valid thread ID found, we'll let the server find it from comment_id
    }

    const commentId = <?php echo json_encode($commentId); ?>;

    function loadThreadAndComments() {
        const container = document.getElementById('threadContainer');
        const commentsList = document.getElementById('commentsList');
        const commentsCount = document.getElementById('commentsCount');
        
        const threadId = getCurrentThreadId();
        const commentId = new URLSearchParams(window.location.search).get('comment_id');

        // If we have a comment_id but no thread_id, let the server find the thread
        if (!threadId && !commentId) {
            container.innerHTML = '<p>No thread selected.</p>';
            commentsList.innerHTML = '';
            commentsCount.textContent = '0 comments';
            return;
        }

        fetch(`get_thread.php?thread_id=${threadId}`)
            .then(res => res.json())
            .then(data => {
                if (!data || data.error) {
                    container.innerHTML = '<p>Thread not found.</p>';
                    commentsList.innerHTML = '';
                    commentsCount.textContent = '0 comments';
                    return;
                }

                displayThread(data.thread);
                displayComments(data.thread.comments || []);
            })
            .catch(err => {
                console.error(err);
                container.innerHTML = '<p>Error loading thread.</p>';
                commentsList.innerHTML = '';
                commentsCount.textContent = '0 comments';
            });
    }

    // --------------------
    // Display thread
    // --------------------
    function displayThread(thread) {
        const container = document.getElementById('threadContainer');
        const avatarInitials = getInitials(thread.username);

        thread.canDelete = (thread.user_id == currentUserId) || (currentUserRole === 'admin');

        container.innerHTML = `
            <div class="thread" data-can-delete="${thread.canDelete}" data-user-id="${thread.user_id}">
                <div class="thread-header">
                    <div class="thread-avatar">${avatarInitials}</div>
                    <div class="thread-meta">
                        <div class="thread-author">${thread.username}</div>
                        <div class="thread-time">${thread.time}</div>
                    </div>
                    <div class="thread-options" data-can-delete="${thread.canDelete}">
                        <button class="options-btn"><i class="fas fa-ellipsis-v"></i></button>
                        <div class="options-menu">
                            <button class="report-thread"><i class="fas fa-flag"></i> Report</button>
                            <button class="share-thread"><i class="fas fa-share"></i> Share</button>
                            <button class="delete-thread" style="display: ${thread.canDelete ? 'flex' : 'none'};">
                                <i class="fas fa-trash"></i> Delete Thread
                            </button>
                        </div>
                    </div>
                </div>
                <div class="thread-content">
                    <h3 class="thread-title">${thread.title}</h3>
                    ${thread.body ? `<div class="thread-body">${thread.body}</div>` : ''}
                    ${thread.image ? `<img src="${thread.image}" class="thread-image">` : ''}
                </div>
                <div class="thread-footer">
                    <div class="vote-buttons">
                        <button class="vote-btn upvote">▲</button>
                        <span class="vote-count">${thread.votes || 0}</span>
                        <button class="vote-btn downvote">▼</button>
                    </div>
                    <div class="thread-actions">
                        <button class="action-btn"><i class="fas fa-share"></i> Share</button>
                        <button class="action-btn"><i class="far fa-bookmark"></i> Save</button>
                    </div>
                </div>
            </div>
        `;

        const upvoteBtn = container.querySelector('.upvote');
        const downvoteBtn = container.querySelector('.downvote');
        const voteCountEl = container.querySelector('.vote-count');

        upvoteBtn.addEventListener('click', () => voteThread(thread.thread_id, 'up', voteCountEl, upvoteBtn, downvoteBtn));
        downvoteBtn.addEventListener('click', () => voteThread(thread.thread_id, 'down', voteCountEl, upvoteBtn, downvoteBtn));

        setupOptionsMenu(container);
    }

    // --------------------
    // Display comments
    // --------------------
    function displayComments(comments) {
        const list = document.getElementById('commentsList');
        const counter = document.getElementById('commentsCount');

        const total = countComments(comments);
        counter.textContent = `${total} comment${total !== 1 ? 's' : ''}`;

        if (comments.length === 0) {
            list.innerHTML = '<div class="no-comments">No comments yet. Be the first to comment!</div>';
            return;
        }

      list.innerHTML = '';
      comments.forEach(c => list.appendChild(createCommentElement(c)));
      setupOptionsMenu(list);

      // If a comment id was provided in URL (from a report), scroll to and highlight it
      if (commentId) {
        const el = list.querySelector(`[data-comment-id="${commentId}"]`);
        if (el) {
          el.scrollIntoView({ behavior: 'smooth', block: 'center' });
          el.classList.add('highlighted-comment');
          // remove highlight after a short delay
          setTimeout(() => el.classList.remove('highlighted-comment'), 5000);
        }
      }
    }

    function countComments(comments) {
        let count = comments.length;
        comments.forEach(c => { if (c.replies) count += countComments(c.replies); });
        return count;
    }

    function createCommentElement(comment, isNested = false) {
        const div = document.createElement('div');
        div.className = isNested ? 'nested-comment' : 'comment';
        div.dataset.commentId = comment.comment_id;
        div.dataset.userId = comment.user_id;
        comment.canDelete = (comment.user_id == currentUserId) || (currentUserRole === 'admin');
        div.dataset.canDelete = comment.canDelete ? 'true' : 'false';
        const initials = getInitials(comment.username);

        div.innerHTML = `
            <div class="comment-header">
                <div class="comment-avatar">${initials}</div>
                <div class="comment-author">${comment.username}</div>
                <div class="comment-time">${comment.created_at}</div>
                <div class="comment-options">
                    <button class="options-btn"><i class="fas fa-ellipsis-v"></i></button>
                    <div class="options-menu">
                        <button class="report-comment"><i class="fas fa-flag"></i> Report</button>
                        <button class="delete-comment" style="display: ${comment.canDelete ? 'flex' : 'none'};">
                            <i class="fas fa-trash"></i> Delete Comment
                        </button>
                    </div>
                </div>
            </div>
            <div class="comment-body">${comment.content}</div>
            <div class="comment-actions">
                <button class="comment-action reply-btn"><i class="fas fa-reply"></i> Reply</button>
                <button class="comment-action like-btn"><i class="${comment.isLiked ? 'fas' : 'far'} fa-thumbs-up"></i> ${comment.likes} ${comment.isLiked ? 'Liked' : 'Like'}</button>
            </div>
        `;

        // Reply
        div.querySelector('.reply-btn').addEventListener('click', () => {
            if (div.querySelector('.nested-form')) return;
            const form = document.createElement('div');
            form.className = 'comment-form nested-form';
            form.innerHTML = `
                <textarea class="comment-input" placeholder="Write a reply..."></textarea>
                <div style="display:flex; gap:8px;">
                    <button class="comment-submit post-reply">Post Reply</button>
                    <button class="btn-secondary cancel-reply">Cancel</button>
                </div>
            `;
            div.appendChild(form);

            form.querySelector('.cancel-reply').addEventListener('click', () => form.remove());
            form.querySelector('.post-reply').addEventListener('click', () => {
                const text = form.querySelector('textarea').value.trim();
                if (!text) return;
                postComment(text, comment.comment_id);
            });
        });

        // Like
        const likeBtn = div.querySelector('.like-btn');
        likeBtn.addEventListener('click', () => {
            fetch('like_comment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `comment_id=${comment.comment_id}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.error) return alert(data.error);
                comment.likes = data.total;
                comment.isLiked = data.liked;
                likeBtn.innerHTML = `<i class="${comment.isLiked ? 'fas' : 'far'} fa-thumbs-up"></i> ${comment.likes} ${comment.isLiked ? 'Liked' : 'Like'}`;
                likeBtn.style.color = comment.isLiked ? '#f5b301' : '';
            });
        });

        // Nested replies
        if (comment.replies && comment.replies.length > 0) {
            const nest = document.createElement('div');
            nest.className = 'nested-comments';
            comment.replies.forEach(r => nest.appendChild(createCommentElement(r, true)));
            div.appendChild(nest);
        }

        return div;
    }

    // --------------------
    // Post comment
    // --------------------
    function postComment(content, parentId = null) {
        // Use URL param first, fallback to localStorage
        const threadId = (new URLSearchParams(window.location.search)).get('thread_id') || localStorage.getItem('currentThreadId');
        fetch('add_comment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `thread_id=${encodeURIComponent(threadId)}&content=${encodeURIComponent(content)}&parent_id=${encodeURIComponent(parentId || '')}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadThreadAndComments();
                document.getElementById('commentInput').value = '';
            } else {
                alert(data.error || 'Failed to post');
            }
        })
        .catch(err => console.error('Error:', err));
    }

    // --------------------
    // Vote thread
    // --------------------
    function voteThread(threadId, type, voteCountEl, upBtn, downBtn) {
        fetch('vote.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `thread_id=${encodeURIComponent(threadId)}&vote=${encodeURIComponent(type)}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.error) return alert(data.error);
            voteCountEl.textContent = data.total;
            upBtn.style.color = '';
            downBtn.style.color = '';
            if (data.user_vote === 'up') upBtn.style.color = '#ff4500';
            else if (data.user_vote === 'down') downBtn.style.color = '#7193ff';
        })
        .catch(err => console.error('Vote error:', err));
    }

    // --------------------
    // Options menu
    // --------------------
    function setupOptionsMenu(container) {
        // Close all menus when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.options-btn')) {
                container.querySelectorAll('.options-menu').forEach(menu => menu.classList.remove('show'));
            }
        });

        // Toggle menu
        container.querySelectorAll('.options-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const menu = btn.nextElementSibling;
                const visible = menu.classList.contains('show');
                container.querySelectorAll('.options-menu').forEach(m => m.classList.remove('show'));
                if (!visible) menu.classList.add('show');
            });
        });

        // Delete thread
        container.querySelectorAll('.delete-thread').forEach(btn => {
            btn.addEventListener('click', () => {
                if (!confirm('Are you sure you want to delete this thread?')) return;
                // Use URL param first, fallback to localStorage
                const threadId = (new URLSearchParams(window.location.search)).get('thread_id') || localStorage.getItem('currentThreadId');
                fetch('delete_thread.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `thread_id=${threadId}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') window.location.href = 'student_home.php';
                    else alert(data.message || 'Failed to delete thread');
                });
            });
        });

        // Delete comment
        container.querySelectorAll('.delete-comment').forEach(btn => {
            btn.addEventListener('click', () => {
                if (!confirm('Are you sure you want to delete this comment?')) return;
                const commentId = btn.closest('[data-comment-id]').dataset.commentId;
                fetch('delete_comment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `comment_id=${commentId}&user_id=${currentUserId}&role=${currentUserRole}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) loadThreadAndComments();
                    else alert(data.error || 'Failed to delete comment');
                });
            });
        });

        // Thread report
        container.querySelectorAll('.report-thread').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const threadEl = btn.closest('.thread');
                if (!threadEl) return;
                openReportModal({
                    locationType: 'thread',
                    locationId: (new URLSearchParams(window.location.search)).get('thread_id') || localStorage.getItem('currentThreadId'),
                    reportedUserId: threadEl.dataset.userId,
                    reportedUserName: threadEl.querySelector('.thread-author').textContent
                });
            });
        });

        // Comment report
        container.querySelectorAll('.report-comment').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const commentEl = btn.closest('.comment, .nested-comment');
                if (!commentEl) return;
                openReportModal({
                    locationType: 'comment',
                    locationId: commentEl.dataset.commentId,
                    reportedUserId: commentEl.dataset.userId,
                    reportedUserName: commentEl.querySelector('.comment-author').textContent
                });
            });
        });

        // Share thread
        container.querySelectorAll('.share-thread').forEach(btn => {
            btn.addEventListener('click', () => {
                const threadId = getCurrentThreadId();
                const threadUrl = `${window.location.origin}/comment.php?thread_id=${threadId}`;
                navigator.clipboard.writeText(threadUrl).then(() => alert('Thread link copied!'));
            });
        });
    }

    // --------------------
    // Report modal
    // --------------------
    const reportModal = document.getElementById('reportModal');
    const closeReportModalBtn = document.getElementById('closeReportModal');
    const cancelReportBtn = document.getElementById('cancelReport');
    const reportForm = document.getElementById('reportForm');

    function openReportModal({ locationType, locationId, reportedUserId, reportedUserName }) {
        reportModal.style.display = 'flex';
        document.getElementById('locationType').value = locationType;
        document.getElementById('locationId').value = locationId;
        document.getElementById('reportedUserId').value = reportedUserId;
        document.getElementById('reportedUserName').value = reportedUserName;
    }

    function closeReportModal() {
        reportModal.style.display = 'none';
        reportForm.reset();
    }

    closeReportModalBtn.addEventListener('click', closeReportModal);
    cancelReportBtn.addEventListener('click', closeReportModal);
    window.addEventListener('click', e => { if (e.target === reportModal) closeReportModal(); });

    reportForm.addEventListener('submit', e => {
        e.preventDefault();
        const formData = new FormData(reportForm);
        fetch('submit_report.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    alert(`Report submitted successfully!\nReport ID: ${data.reportId}\nStatus: Pending\nThank you for your report. Our moderators will review it shortly.`);
                    closeReportModal();
                } else {
                    alert(data.error || 'Failed to submit report. Please try again later.');
                }
            }).catch(err => {
                console.error(err);
                alert('An error occurred while submitting the report. Please try again later.');
            });
    });

    // --------------------
    // Init
    // --------------------
    window.addEventListener('DOMContentLoaded', () => {
        loadThreadAndComments();

        document.getElementById('commentSubmit').addEventListener('click', () => {
            const val = document.getElementById('commentInput').value.trim();
            if (val) postComment(val);
        });
    });
</script>


</body>
</html>