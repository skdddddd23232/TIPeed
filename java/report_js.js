
    // Left sidebar toggle
    const sidebar = document.getElementById('sidebar');
    const toggleSidebar = document.getElementById('toggleSidebar');
    toggleSidebar.addEventListener('click', () => sidebar.classList.toggle('expanded'));

    // Filter functionality
    const filterBtns = document.querySelectorAll('.filter-btn');

    filterBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        filterBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const filter = btn.textContent.toLowerCase();

        reportItems.forEach(item => {
          let show = true;

          switch(filter) {
            case 'all reports':
              show = true;
              break;
            case 'unread':
              show = item.classList.contains('unread');
              break;
            case 'pending':
            case 'resolved':
              show = item.dataset.status === filter;
              break;
            default:
              // filter by report type
              show = item.dataset.type === filter;
              break;
          }

          item.style.display = show ? 'flex' : 'none';
        });
      });
    });


    // View Details functionality: attach to view-details buttons and resolve buttons
    document.querySelectorAll('.view-details').forEach(btn => {
      btn.addEventListener('click', function() {
        const reportModal = document.getElementById('reportModal');
        // populate modal fields from data attributes
        document.getElementById('modalReportId').textContent = this.dataset.id || '';
        document.getElementById('modalType').textContent = this.dataset.type || '';
        const statusText = this.dataset.status || '';
        const statusEl = document.getElementById('modalStatus');
        statusEl.textContent = statusText.charAt(0).toUpperCase() + statusText.slice(1);
        statusEl.className = 'status-badge ' + (statusText === 'pending' ? 'status-pending' : (statusText === 'resolved' ? 'status-resolved' : 'status-investigating'));
        document.getElementById('modalReporter').textContent = this.dataset.reporter || '';
        document.getElementById('modalReportedUser').textContent = this.dataset.reportedUser || '';
        document.getElementById('modalDateReported').textContent = this.dataset.date || '';
        document.getElementById('modalDescription').textContent = this.dataset.description || '';
        document.getElementById('modalReportedContent').textContent = this.dataset.reportedContent || '';
        document.getElementById('modalPriority').textContent = this.dataset.priority || '';

        reportModal.style.display = 'flex';
      });
    });

    // Resolve button behaviour (server-side)
    document.querySelectorAll('.action-btn.resolve').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.stopPropagation();
        const reportItem = this.closest('.report-item');
        const reportId = reportItem.dataset.reportId;
        const isResolved = reportItem.classList.contains('resolved');
        const action = isResolved ? 'reopen' : 'resolve';

        fetch('resolve_report.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `report_id=${reportId}&action=${action}`
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            // ✅ Update data-status attribute first
            if (data.status === 'resolved') {
              reportItem.classList.remove('unread');
              reportItem.classList.add('resolved');
              reportItem.dataset.status = 'resolved'; // <- your snippet here
            } else {
              reportItem.classList.remove('resolved');
              reportItem.dataset.status = 'pending'; // <- your snippet here
            }

            // Update status badge
            const statusBadge = reportItem.querySelector('.status-badge');
            if (statusBadge) {
              statusBadge.textContent = data.status === 'resolved' ? 'Resolved' : 'Pending';
              statusBadge.className = 'status-badge ' + (data.status === 'resolved' ? 'status-resolved' : 'status-pending');
            }

            // Update button text
            this.textContent = data.status === 'resolved' ? 'Reopen' : 'Resolve';
            this.classList.toggle('resolve', data.status !== 'resolved');

            updateReportStats();
          } else {
            alert(data.error || 'Failed to update report status');
          }
        })
        .catch(err => {
          console.error(err);
          alert('Failed to update report status');
        });
      });
    });

    // Open Thread button
    document.querySelectorAll('.open-thread-btn').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.stopPropagation();
        const threadId = this.dataset.threadId;
        if (!threadId) return;
        // open in a new tab so admin can return to reports
        window.open(`comment.php?thread_id=${threadId}`, '_blank');
      });
    });

    // Open Comment button
    document.querySelectorAll('.open-comment-btn').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.stopPropagation();
        const threadId = this.dataset.threadId;
        const commentId = this.dataset.commentId;
        
        if (!threadId) {
          alert('Thread not found');
          return;
        }

        // Use URL parameters instead of localStorage
        window.open(`comment.php?thread_id=${threadId}&comment_id=${commentId}`, '_blank');
      });
    });

    // Close modal
    const closeModal = document.getElementById('closeModal');
    const reportModal = document.getElementById('reportModal');
    closeModal.addEventListener('click', () => {
      reportModal.style.display = 'none';
    });

    // Close modal when clicking outside
    reportModal.addEventListener('click', (e) => {
      if (e.target === reportModal) {
        reportModal.style.display = 'none';
      }
    });

    // Delete report button in modal - mark report as resolved and tag deleted (server-side)
    const deleteReportBtn = document.querySelector('.action-btn.delete');
    if (deleteReportBtn) {
      deleteReportBtn.addEventListener('click', function() {
        const reportId = document.getElementById('modalReportId').textContent.trim();
        if (!reportId) return alert('Report ID not found');

        if (!confirm('Mark this report as deleted (it will be kept but marked resolved)?')) return;

        fetch('delete_report.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `report_id=${encodeURIComponent(reportId)}`
        }).then(res => res.json()).then(data => {
          if (data.success) {
            // find report item in DOM and update
            const item = document.querySelector(`.report-item[data-report-id="${reportId}"]`);
            if (item) {
              item.classList.remove('unread');
              item.classList.add('resolved');
              const badge = item.querySelector('.status-badge');
              if (badge) { badge.textContent = 'Resolved'; badge.className = 'status-badge status-resolved'; }
              const msg = item.querySelector('.report-message');
              if (msg) msg.textContent = '[DELETED REPORT] ' + (msg.textContent || '');
            }
            reportModal.style.display = 'none';
            updateReportStats();
          } else {
            alert(data.error || 'Failed to update report');
          }
        }).catch(err => {
          console.error(err);
          alert('Request failed');
        });
      });
    }

    // Modal buttons
      const assignBtn = document.querySelector('.report-actions-modal .assign');
      const resolveModalBtn = document.querySelector('.report-actions-modal .resolve');
      const deleteBtn = document.querySelector('.report-actions-modal .delete');

      assignBtn.addEventListener('click', function() {
        const assignedField = reportModal.querySelector('.detail-row:last-child .detail-value');
        assignedField.textContent = 'You';
        alert('Report assigned to you');
      });

      resolveModalBtn.addEventListener('click', function() {
        const reportId = document.getElementById('modalReportId').textContent.trim();
        if (!reportId) return alert('Report ID not found');

        fetch('resolve_report.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `report_id=${encodeURIComponent(reportId)}&action=resolve`
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            const statusBadge = reportModal.querySelector('.status-badge');
            statusBadge.textContent = 'Resolved';
            statusBadge.className = 'status-badge status-resolved';

            // Update report item in list
            const reportItem = document.querySelector(`.report-item[data-report-id="${reportId}"]`);
            if (reportItem) {
              reportItem.classList.remove('unread');
              reportItem.classList.add('resolved');
              reportItem.dataset.status = 'resolved';
              const badge = reportItem.querySelector('.status-badge');
              if (badge) { 
                badge.textContent = 'Resolved'; 
                badge.className = 'status-badge status-resolved'; 
              }
              const resolveBtn = reportItem.querySelector('.action-btn.resolve');
              if (resolveBtn) {
                resolveBtn.textContent = 'Reopen';
                resolveBtn.classList.remove('resolve');
              }
            }

            updateReportStats();
            alert('Report marked as resolved');
          } else {
            alert(data.error || 'Failed to mark report as resolved');
          }
        })
        .catch(err => console.error(err));
      });


    

    // Update report stats
    function updateReportStats() {
      const reportItemsArray = Array.from(document.querySelectorAll('.report-item'));
      const totalReports = reportItemsArray.length;
      const unreadReports = reportItemsArray.filter(item => item.classList.contains('unread')).length;
      const pendingReports = reportItemsArray.filter(item => item.dataset.status === 'pending').length;
      const resolvedReports = reportItemsArray.filter(item => item.dataset.status === 'resolved').length;

      document.getElementById('totalReports').textContent = totalReports;
      document.getElementById('unreadReports').textContent = unreadReports;
      document.getElementById('pendingReports').textContent = pendingReports;
      document.getElementById('resolvedReports').textContent = resolvedReports;
    }

    // Load more functionality
    const loadMoreBtn = document.querySelector('.load-more-btn');
    loadMoreBtn.addEventListener('click', function() {
      // Simulate loading more reports
      this.textContent = 'Loading...';
      this.disabled = true;
      
      setTimeout(() => {
        // In a real app, you would fetch more reports from the server
        alert('Loading more reports...');
        this.textContent = 'Load More Reports';
        this.disabled = false;
      }, 1000);
    });

    // Mark as read functionality
    const reportItems = document.querySelectorAll('.report-item');
    reportItems.forEach(item => {
      item.addEventListener('click', function(e) {
        // Don't mark as read if clicking action buttons
        if (!e.target.classList.contains('action-btn')) {
          this.classList.remove('unread');
          updateReportStats();
        }
      });
    });
