

// Extracted JS from admin_home.php
// Calendar functionality and announcement/event handling
let currentDate = new Date();
let events = [];
let selectedDate = null;

// DOM Elements
const calendarTitle = document.getElementById('calendarTitle');
const calendarGrid = document.getElementById('calendarGrid');
const prevMonthBtn = document.getElementById('prevMonth');
const nextMonthBtn = document.getElementById('nextMonth');
const todayBtn = document.getElementById('todayBtn');
const eventsList = document.getElementById('eventsList');
const createAnnouncementBtn = document.getElementById('createAnnouncementBtn');
const announcementModal = document.getElementById('announcementModal');
const closeAnnouncementModal = document.getElementById('closeAnnouncementModal');
const cancelAnnouncement = document.getElementById('cancelAnnouncement');
const announcementForm = document.getElementById('announcementForm');
const announcementsList = document.getElementById('announcementsList');
const successMessage = document.getElementById('successMessage');
const successText = document.getElementById('successText');
const calendarPage = document.getElementById('calendarPage');
const backToDashboard = document.getElementById('backToDashboard');
const calendarPageTitle = document.getElementById('calendarPageTitle');
const selectedDateTitle = document.getElementById('selectedDateTitle');
const calendarPageEventsList = document.getElementById('calendarPageEventsList');
const calendarPageUpcomingEvents = document.getElementById('calendarPageUpcomingEvents');
const calendarEventForm = document.getElementById('calendarEventForm');

// Fetch events from server



// Initialize calendar
async function initCalendar() {
  await fetchEvents();
  renderCalendar();
  renderEvents();
  fetchAnnouncements();
  
  // Event listeners
  prevMonthBtn.addEventListener('click', goToPreviousMonth);
  nextMonthBtn.addEventListener('click', goToNextMonth);
  todayBtn.addEventListener('click', goToToday);
  backToDashboard.addEventListener('click', closeCalendarPage);
  calendarEventForm.addEventListener('submit', saveCalendarEvent);
  
  // Close modal when clicking outside
  announcementModal.addEventListener('click', (e) => {
    if (e.target === announcementModal) {
      closeAnnouncementModalFunc();
    }
  });
}

async function fetchEvents() {
  try {
    const res = await fetch('get_events.php');
    events = await res.json();
    renderCalendar();
    renderEvents();
  } catch (err) {
    console.error('Failed to fetch events:', err);
  }
}

// Render calendar
function renderCalendar() {
  const year = currentDate.getFullYear();
  const month = currentDate.getMonth();
  
  // Update calendar title
  calendarTitle.textContent = `${currentDate.toLocaleString('default', { month: 'long' })} ${year}`;
  
  // Clear calendar grid
  calendarGrid.innerHTML = '';
  
  // Add day headers
  const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
  days.forEach(day => {
    const dayElement = document.createElement('div');
    dayElement.className = 'calendar-day-header';
    dayElement.textContent = day;
    calendarGrid.appendChild(dayElement);
  });
  
  // Get first day of month and number of days
  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);
  const daysInMonth = lastDay.getDate();
  const startingDay = firstDay.getDay();
  
  // Add days from previous month
  const prevMonthLastDay = new Date(year, month, 0).getDate();
  for (let i = startingDay - 1; i >= 0; i--) {
    const dayElement = document.createElement('div');
    dayElement.className = 'calendar-day other-month';
    dayElement.textContent = prevMonthLastDay - i;
    calendarGrid.appendChild(dayElement);
  }
  
  // Add days of current month
  const today = new Date();
  for (let i = 1; i <= daysInMonth; i++) {
    const dayElement = document.createElement('div');
    dayElement.className = 'calendar-day';
    dayElement.textContent = i;

    // Check if this is today
    if (year === today.getFullYear() && month === today.getMonth() && i === today.getDate()) {
      dayElement.classList.add('current');
    }

    // Highlight if this date has events
    const dateStr = formatDate(new Date(year, month, i));
    if (events.some(event => formatDate(new Date(event.date)) === dateStr)) {
      dayElement.classList.add('has-event');
    }

    dayElement.addEventListener('click', () => openCalendarPage(new Date(year, month, i)));
    calendarGrid.appendChild(dayElement);
  }
  
  // Add days from next month to fill the grid
  const totalCells = 42; // 6 rows * 7 days
  const remainingCells = totalCells - (startingDay + daysInMonth);
  for (let i = 1; i <= remainingCells; i++) {
    const dayElement = document.createElement('div');
    dayElement.className = 'calendar-day other-month';
    dayElement.textContent = i;
    calendarGrid.appendChild(dayElement);
  }
}

// Navigate to previous month
function goToPreviousMonth() {
  currentDate.setMonth(currentDate.getMonth() - 1);
  renderCalendar();
}

// Navigate to next month
function goToNextMonth() {
  currentDate.setMonth(currentDate.getMonth() + 1);
  renderCalendar();
}

// Navigate to today
function goToToday() {
  currentDate = new Date();
  renderCalendar();
}

// Open calendar page
function openCalendarPage(date) {
  selectedDate = date;
  
  // Update calendar page title
  calendarPageTitle.textContent = `Calendar - ${date.toLocaleString('default', { month: 'long' })} ${date.getFullYear()}`;
  selectedDateTitle.textContent = date.toLocaleDateString('en-US', { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  });
  
  // Render events for selected date
  renderCalendarPageEvents();
  
  // Render upcoming events
  renderCalendarPageUpcomingEvents();
  
  // Show calendar page
  calendarPage.style.display = 'block';
}

// Close calendar page
function closeCalendarPage() {
  calendarPage.style.display = 'none';
  renderCalendar();
  renderEvents();
}

// Render events for calendar page
function renderCalendarPageEvents() {
  const dateStr = formatDate(selectedDate);
  const dateEvents = events.filter(event => event.date === dateStr);
  
  calendarPageEventsList.innerHTML = '';
  
  if (dateEvents.length === 0) {
    calendarPageEventsList.innerHTML = '<p>No events scheduled for this date.</p>';
    return;
  }
  
  dateEvents.forEach(event => {
    const eventElement = document.createElement('div');
    eventElement.className = 'event-item';
    eventElement.innerHTML = `
      <div class="event-content">
        <div class="event-title">${event.title}</div>
        <div class="event-description">${event.description || 'No description'}</div>
      </div>
      <button class="delete-announcement" data-id="${event.event_id}">
        <i class="fas fa-trash"></i>
      </button>
    `;

    calendarPageEventsList.appendChild(eventElement);
  });

  // Add delete listeners
  document.querySelectorAll('#calendarPageEventsList .delete-announcement').forEach(button => {
    button.addEventListener('click', function() {
      const eventId = this.getAttribute('data-id');
      deleteCalendarEvent(eventId);
    });
  });
}

// Render upcoming events for calendar page
function renderCalendarPageUpcomingEvents() {
  // Sort events by date
  const sortedEvents = [...events].sort((a, b) => new Date(a.date) - new Date(b.date));
  
  // Filter events for the next 30 days
  const today = new Date();
  const nextMonth = new Date();
  nextMonth.setDate(today.getDate() + 30);
  
  const upcomingEvents = sortedEvents.filter(event => {
    const eventDate = new Date(event.date);
    return eventDate >= today && eventDate <= nextMonth;
  });
  
  // Clear upcoming events list
  calendarPageUpcomingEvents.innerHTML = '';
  
  if (upcomingEvents.length === 0) {
    calendarPageUpcomingEvents.innerHTML = '<p>No upcoming events.</p>';
    return;
  }
  
  // Add upcoming events to list
  upcomingEvents.forEach(event => {
    const eventDate = new Date(event.date);
    const eventElement = document.createElement('div');
    eventElement.className = 'event-item';
    eventElement.innerHTML = `
      <div class="event-date">
        <div class="event-day">${eventDate.getDate()}</div>
        <div class="event-month">${eventDate.toLocaleString('default', { month: 'short' }).toUpperCase()}</div>
      </div>
      <div class="event-content">
        <div class="event-title">${event.title}</div>
        <div class="event-description">${event.description || 'No description'}</div>
      </div>
    `;
    
    calendarPageUpcomingEvents.appendChild(eventElement);
  });
}


// Save calendar event
    async function saveCalendarEvent(e) {
      e.preventDefault();

      // Use selectedDate or default to today
      const eventDate = selectedDate ? formatDate(selectedDate) : formatDate(new Date());

      const title = document.getElementById('calendarEventTitle').value.trim();
      const description = document.getElementById('calendarEventDescription').value.trim();

      if (!title) return alert('Please enter an event title');

      try {
        const res = await fetch('create_event.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ title, description, date: eventDate })
        });

        const result = await res.json();
        console.log('PHP response:', result);

        if (result.error) {
          alert('Error creating event: ' + result.error);
          return;
        }

        // Success
        calendarEventForm.reset();
        fetchEvents(); // refresh calendar
        showSuccessMessage('Event added successfully!');
      } catch (err) {
        console.error('Fetch failed:', err);
        alert('Failed to create event. Check console for details.');
      }
    }



// Delete calendar event
  async function deleteCalendarEvent(id) {
    if (!confirm('Are you sure you want to delete this event?')) return;

    try {
      await fetch('delete_event.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ event_id: id })
      });
      fetchEvents(); // refresh calendar
      showSuccessMessage('Event deleted successfully!');
    } catch (err) {
      console.error(err);
    }
  }


// Render events list
function renderEvents() {
  // Sort events by date
  const sortedEvents = [...events].sort((a, b) => new Date(a.date) - new Date(b.date));
  
  // Filter events for the next 30 days
  const today = new Date();
  const nextMonth = new Date();
  nextMonth.setDate(today.getDate() + 30);
  
  const upcomingEvents = sortedEvents.filter(event => {
    const eventDate = new Date(event.date);
    return eventDate >= today && eventDate <= nextMonth;
  });
  
  // Clear events list
  eventsList.innerHTML = '';
  
  if (upcomingEvents.length === 0) {
    eventsList.innerHTML = '<div class="no-events">No upcoming events</div>';
    return;
  }
  
  // Add events to list
  upcomingEvents.forEach(event => {
    const eventDate = new Date(event.date);
    const eventElement = document.createElement('div');
    eventElement.className = 'event-item';
    eventElement.addEventListener('click', () => openCalendarPage(eventDate));
    
    eventElement.innerHTML = `
      <div class="event-date">
        <div class="event-day">${eventDate.getDate()}</div>
        <div class="event-month">${eventDate.toLocaleString('default', { month: 'short' }).toUpperCase()}</div>
      </div>
      <div class="event-content">
        <div class="event-title">${event.title}</div>
        <div class="event-description">${event.description || 'No description'}</div>
      </div>
    `;
    
    eventsList.appendChild(eventElement);
  });
}

// Format date as YYYY-MM-DD
function formatDate(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

// Announcement functionality
let announcements = [];

// Open/Close Modal
createAnnouncementBtn.addEventListener('click', () => {
  announcementModal.style.display = 'flex';
  announcementForm.reset();
});
closeAnnouncementModal.addEventListener('click', () => announcementModal.style.display = 'none');
cancelAnnouncement.addEventListener('click', () => announcementModal.style.display = 'none');
announcementModal.addEventListener('click', e => {
  if (e.target === announcementModal) announcementModal.style.display = 'none';
});

// Fetch announcements from database
async function fetchAnnouncements() {
  try {
    const res = await fetch('get_announcements.php');
    announcements = await res.json();
    renderAnnouncements();
  } catch (err) {
    console.error('Failed to fetch announcements:', err);
  }
}

// Render announcements in the UI
function renderAnnouncements() {
  announcementsList.innerHTML = '';
  if (announcements.length === 0) {
    announcementsList.innerHTML = '<div class="no-announcements">No announcements yet. Create one!</div>';
    return;
  }

  announcements.forEach(a => {
    const announcementElement = document.createElement('div');
    announcementElement.className = 'notification-item';
    
    let iconClass = 'fas fa-bullhorn';
    if (a.type === 'important') iconClass = 'fas fa-exclamation-circle';
    if (a.type === 'urgent') iconClass = 'fas fa-exclamation-triangle';

    announcementElement.innerHTML = `
      <div class="notification-icon">
        <i class="${iconClass}"></i>
      </div>
      <div class="notification-content">
        <div class="notification-title">${a.title}</div>
        <div class="notification-subtitle">${a.content}</div>
        <div class="notification-time">${a.date} • ${a.author}</div>
      </div>
      <button class="delete-announcement" data-id="${a.announcement_id}">
        <i class="fas fa-trash"></i>
      </button>
    `;
    
    announcementsList.appendChild(announcementElement);
  });

  // Delete button listeners
  document.querySelectorAll('.delete-announcement').forEach(btn => {
    btn.addEventListener('click', async function () {
      const announcement_id = this.dataset.id;
      if (!confirm('Are you sure you want to delete this announcement?')) return;

      try {
        await fetch('delete_announcement.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ announcement_id })
        });
        fetchAnnouncements(); // Refresh in real-time
        showSuccessMessage('Announcement deleted successfully!');
      } catch (err) {
        console.error('Delete failed:', err);
      }
    });
  });
}

// Create a new announcement
announcementForm.addEventListener('submit', async function (e) {
  e.preventDefault();

  const title = document.getElementById('announcementTitle').value.trim();
  const content = document.getElementById('announcementContent').value.trim();
  const type = document.getElementById('announcementType').value;

  if (!title || !content) {
    alert('Please fill in all required fields');
    return;
  }

  try {
    const res = await fetch('create_announcement.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ title, content, type })
    });
    const newAnnouncement = await res.json();

    announcementModal.style.display = 'none';
    fetchAnnouncements(); // Real-time update
    showSuccessMessage('Announcement created successfully!');
  } catch (err) {
    console.error('Create failed:', err);
  }
});

// Success message
function showSuccessMessage(message) {
  successText.textContent = message;
  successMessage.style.display = 'flex';
  setTimeout(() => successMessage.style.display = 'none', 3000);
}

// Left sidebar toggle
const sidebar = document.getElementById('sidebar');
const toggleSidebar = document.getElementById('toggleSidebar');
if(toggleSidebar && sidebar) {
  toggleSidebar.addEventListener('click', () => sidebar.classList.toggle('expanded'));
}

// Fetch and render logs
 async function fetchLogs() {
      try {
        const res = await fetch('get_logs.php');
        const logs = await res.json();
        logsList.innerHTML = '';
        if (logs.length === 0) {
          logsList.innerHTML = '<p>No logs yet.</p>';
          return;
        }

        logs.forEach(log => {
          const logItem = document.createElement('div');
          logItem.className = 'log-item';
          
          // Split details by newline for clean formatting
          const detailsLines = log.details.split('\n').map(line => `<div>${line}</div>`).join('');

          logItem.innerHTML = `
            <div class="log-top">
              <div class="log-action">${log.action}</div>
              <div class="log-time">${log.time}</div>
            </div>
            <div class="log-details">${detailsLines}</div>
          `;
          
          logsList.appendChild(logItem);
        });
      } catch (err) {
        console.error('Failed to fetch logs:', err);
      }
 }
    let logsList;

const notificationList = document.querySelector('.notification-list');
let previousNotifications = [];

async function fetchNotifications() {
    try {
        const res = await fetch('get_notifications.php');
        const notifications = await res.json();

        // Clear the list first
        notificationList.innerHTML = '';

        if (!notifications.length) {
            notificationList.innerHTML = '<div class="no-notifications">No notifications</div>';
            previousNotifications = [];
            return;
        }

        notifications.forEach(noti => {
            let iconClass = 'fas fa-bullhorn';
            if (noti.category === 'report') iconClass = 'fas fa-exclamation-circle';
            if (noti.category === 'message') iconClass = 'fas fa-comments';
            
            const notiElement = document.createElement('div');
            notiElement.className = 'notification-card ' + (noti.category === 'announcement' ? 'announcement' : '');

            // Persist highlight if new
            if (!previousNotifications.find(n => n.id === noti.id)) {
                notiElement.classList.add('highlight');
            }

            notiElement.innerHTML = `
                <div class="notification-card-icon">
                    <i class="${iconClass}"></i>
                </div>
                <div class="notification-card-content">
                    <div class="notification-card-title">${noti.title}</div>
                    <div class="notification-card-message">${noti.message}</div>
                    <div class="notification-card-time">${noti.time}</div>
                </div>
            `;

            // Remove highlight when user interacts
            notiElement.addEventListener('mouseenter', () => notiElement.classList.remove('highlight'));
            notiElement.addEventListener('click', () => notiElement.classList.remove('highlight'));
            notiElement.addEventListener('scroll', () => notiElement.classList.remove('highlight'));

            notificationList.appendChild(notiElement);
        });

        // Save current notifications for next fetch
        previousNotifications = notifications;

    } catch (err) {
        console.error('Failed to fetch notifications:', err);
    }
}

// Initial fetch
fetchNotifications();
setInterval(fetchNotifications, 3000);




document.addEventListener('DOMContentLoaded', () => {
  logsList = document.querySelector('.logs-list'); // global
  initCalendar();   // now async
  fetchLogs();
});


