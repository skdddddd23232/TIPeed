
document.addEventListener('DOMContentLoaded', () => {


    // Cancel

  // Sidebar toggles (defensive)
  const sidebar = document.getElementById('sidebar');
  const toggleSidebar = document.getElementById('toggleSidebar');
  const rightSidebar = document.getElementById('rightSidebar');
  const toggleRightSidebar = document.getElementById('toggleRightSidebar');

  toggleSidebar?.addEventListener('click', () => sidebar.classList.toggle('expanded'));
  toggleRightSidebar?.addEventListener('click', () => rightSidebar.classList.toggle('expanded'));

  // Slider (kept lightweight)
  const slides = document.querySelectorAll('.slide');
  const dotsContainer = document.querySelector('.dots');
  let index = 0;
  let interval;

  function showSlide(n) {
    index = (n + slides.length) % slides.length;
    document.querySelector('.slides').style.transform = `translateX(-${index * 100}%)`;
    document.querySelectorAll('.dot').forEach((dot, i) => dot.classList.toggle('active', i === index));
  }
  function nextSlide() { showSlide(index + 1); }
  function prevSlide() { showSlide(index - 1); }
  function startAutoSlide() { interval = setInterval(nextSlide, 4000); }
  function stopAutoSlide() { clearInterval(interval); }

  document.querySelector('.next')?.addEventListener('click', () => { nextSlide(); stopAutoSlide(); startAutoSlide(); });
  document.querySelector('.prev')?.addEventListener('click', () => { prevSlide(); stopAutoSlide(); startAutoSlide(); });

  slides.forEach((_, i) => {
    const dot = document.createElement('div');
    dot.classList.add('dot');
    if (i === 0) dot.classList.add('active');
    dot.addEventListener('click', () => { showSlide(i); stopAutoSlide(); startAutoSlide(); });
    dotsContainer.appendChild(dot);
    
  });

  if (slides.length) { showSlide(0); startAutoSlide(); }

  


 // Popup handling
  const popup = document.getElementById('popup');
  const newThreadBtn = document.getElementById('newThreadBtn');
  const cancelBtn = document.getElementById('cancelBtn'); // Cancel button

  // Open popup
  newThreadBtn?.addEventListener('click', () => {
      if (!popup) return;
      popup.style.display = 'flex';
      document.body.style.overflow = 'hidden';
});



// Close popup function
function closePopup() {
    if (!popup) return;
    popup.style.display = 'none';
    document.body.style.overflow = '';
    const imgInput = document.getElementById('imageInput');
    if (imgInput) imgInput.value = '';
}

// Cancel button closes popup
cancelBtn?.addEventListener('click', () => {
    closePopup(); // closes popup
    const form = popup.querySelector('form');
    if (form) form.reset(); // resets all inputs
});

// Click outside popup content closes popup
popup?.addEventListener('click', (e) => {
    if (e.target === popup) closePopup();
});

  // Event delegation for votes and comments
  const threadList = document.getElementById('threadList');
  if (threadList) {
    threadList.addEventListener('click', async (e) => {
      const upBtn = e.target.closest('.upvote');
      const downBtn = e.target.closest('.downvote');
      const commentBtn = e.target.closest('.comment-btn');

      // Voting
      if (upBtn || downBtn) {
        const isUp = !!upBtn;
        const btn = isUp ? upBtn : downBtn;
        const container = btn.closest('.thread-container');
        if (!container) return;
        const threadId = container.dataset.id;
        if (!threadId) return alert('Thread ID missing');

        const voteCountEl = container.querySelector('.vote-count');

        try {
           const resp = await fetch('vote.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                credentials: 'same-origin',
                body: `thread_id=${encodeURIComponent(threadId)}&vote=${encodeURIComponent(isUp?'up':'down')}`
            });

            const data = await resp.json();
            voteCountEl.textContent = data.total;

            if (upBtn) upBtn.classList.toggle('active', data.user_vote === 'up');
            if (downBtn) downBtn.classList.toggle('active', data.user_vote === 'down');

          } catch (err) {
              alert(err.message);
          }

          return;
      }

      // Comment button (store ID then redirect)
      if (commentBtn) {
        const container = commentBtn.closest('.thread-container');
        if (!container) return;
        localStorage.setItem('currentThreadId', String(container.dataset.id));
        window.location.href = 'comment.php';
      }
    });
  }

  

  //FILTER BUTTON
  const filters = document.querySelectorAll('.filter');
  let currentFilter = 'latest'; // default

  filters.forEach(filter => {
      filter.addEventListener('click', () => {
          const type = filter.dataset.filter;

          // Toggle logic: if clicked same filter, go back to latest
          if (type === currentFilter && type !== 'latest') {
              currentFilter = 'latest';
          } else {
              currentFilter = type;
          }

          sortThreads(currentFilter);
          updateActiveClass(currentFilter);
      });
  });

  function updateActiveClass(filterType) {
      filters.forEach(f => f.classList.remove('active'));
      const activeFilter = document.querySelector(`.filter[data-filter="${filterType}"]`);
      if (activeFilter) activeFilter.classList.add('active');
  }

  function sortThreads(type) {
      const list = document.getElementById('threadList');
      if (!list) return;

      const threads = Array.from(list.querySelectorAll('.thread-container'));

      threads.sort((a, b) => {
          switch(type) {
              case 'popular':
                  return (b.dataset.popularity || 0) - (a.dataset.popularity || 0);
              case 'votes':
                  return (b.querySelector('.vote-count')?.textContent || 0) - 
                        (a.querySelector('.vote-count')?.textContent || 0);
              case 'latest':
                  return new Date(b.dataset.time) - new Date(a.dataset.time);
              default:
                  return 0;
          }
      });

      list.innerHTML = '';
      threads.forEach(t => list.appendChild(t));
  }

  // Expose a safe addThreadToDOM that sets data-id (important)
  window.addThreadToDOM = function(thread, save = true) {
    const list = document.getElementById('threadList');
    if (!list) return;

    const container = document.createElement('div');
    container.className = 'thread-container';
    if (thread.id) container.dataset.id = thread.id;

    const avatarInitials = (thread.username || '').split(' ').map(n => n[0] || '').join('').toUpperCase();

    container.innerHTML = `
      <div class="thread-header">
        <div class="thread-avatar">${avatarInitials}</div>
        <div class="thread-meta">
          <div class="thread-author">${thread.username || 'Unknown'}</div>
          <div class="thread-time">${thread.time || ''}</div>
        </div>
      </div>

      <div class="thread-content">
        <h3 class="thread-title">${thread.title || ''}</h3>
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
          <button class="action-btn comment-btn"><i class="far fa-comment"></i> Comment</button>
          <button class="action-btn"><i class="fas fa-share"></i> Share</button>
          <button class="action-btn"><i class="far fa-bookmark"></i> Save</button>
        </div>
      </div>
    `;

    list.prepend(container);

    if (save) {
      const stored = JSON.parse(localStorage.getItem('threads') || '[]');
      stored.unshift(thread);
      localStorage.setItem('threads', JSON.stringify(stored));
    }
  };

  
  document.querySelectorAll('.trending-card').forEach(card => {
  card.addEventListener('click', () => {
    const threadId = card.dataset.threadId;
    if (!threadId) {
      console.error('Trending card missing data-thread-id');
      return;
    }
    localStorage.setItem('currentThreadId', threadId);
    window.location.href = 'comment.php';
  });
});

    // Add this inside your DOMContentLoaded event listener

    // 3-dot dropdown menu toggle
    document.addEventListener('click', function (e) {
      const button = e.target.closest('.menu-btn');
      const openDropdowns = document.querySelectorAll('.menu-dropdown.show');

      // If clicking outside menu, close all
      if (!button && !e.target.closest('.menu-dropdown')) {
        openDropdowns.forEach(dd => dd.classList.remove('show'));
        return;
      }

      // If clicking 3-dot button
      if (button) {
        e.stopPropagation();
        const dropdown = button.nextElementSibling;

        // Close others
        openDropdowns.forEach(dd => {
          if (dd !== dropdown) dd.classList.remove('show');
        });

        // Toggle current one
        dropdown.classList.toggle('show');
      }
    });

    // handle delete button clicks
    document.addEventListener('click', function(e) {
      const del = e.target.closest('.delete-btn');
      if (!del) return;

      if (!confirm('Delete this thread permanently?')) return;

      const threadId = del.dataset.id;
      
      fetch('delete_thread.php', {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'thread_id=' + encodeURIComponent(threadId),
        credentials: 'same-origin'
      })
      .then(r => r.json())
      .then(json => {
        if (json.status === 'success') {
          const container = del.closest('.thread-container');
          if (container) container.remove();
        } else {
          alert(json.message || 'Unable to delete thread.');
        }
      })
      .catch(err => {
        console.error(err);
        alert('Request failed.');
      });
    });

  // --- Report modal elements ---
const reportModal = document.getElementById('reportModal');
const closeReportModal = document.getElementById('closeReportModal');
const cancelReportBtn = document.getElementById('cancelReport');
const reportForm = document.getElementById('reportForm');

// Open modal on Report button click
document.addEventListener('click', function(e) {
    const reportBtn = e.target.closest('.report-btn');
    if (!reportBtn) return;

    // Prefill hidden fields
    reportForm.location_type.value = 'thread';
    reportForm.location_id.value = reportBtn.dataset.id;
    reportForm.reported_user_id.value = reportBtn.dataset.userId;
    reportForm.reported_user_name.value = reportBtn.dataset.username;

    // Optionally update modal title
    const modalTitle = reportModal.querySelector('.modal-title');
    if (modalTitle) modalTitle.textContent = `Report thread by ${reportBtn.dataset.username}`;

    // Show modal
    reportModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
});

// Close modal
function closeReport() {
    reportModal.style.display = 'none';
    document.body.style.overflow = '';
    reportForm.reset();
}

closeReportModal?.addEventListener('click', closeReport);
cancelReportBtn?.addEventListener('click', closeReport);
reportModal?.addEventListener('click', (e) => {
    if (e.target === reportModal) closeReport();
});

// Submit report via AJAX
reportForm?.addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(reportForm);

    try {
        const resp = await fetch('submit_report.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        const data = await resp.json();

        if (data.success) {
            alert(`Report submitted successfully (ID: ${data.reportId})`);
            closeReport();
        } else {
            alert(data.error || 'Failed to submit report.');
        }
    } catch (err) {
        console.error(err);
        alert('Request failed.');
    }
});



});
