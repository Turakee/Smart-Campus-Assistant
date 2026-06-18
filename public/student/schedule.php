<?php
require_once '../../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule - Smart Campus Assistant</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo-icon"><i class="fas fa-graduation-cap"></i></div>
            <h3>Smart Campus Assistant</h3>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
            <a href="schedule.php" class="nav-item active"><i class="fas fa-calendar-alt"></i> Schedule</a>
            <a href="attendance.php" class="nav-item"><i class="fas fa-clipboard-check"></i> Attendance</a>
            <a href="courses.php" class="nav-item"><i class="fas fa-book"></i> Courses</a>
            <a href="booking.php" class="nav-item"><i class="fas fa-door-open"></i> Bookings</a>
            <a href="ai-insights.php" class="nav-item"><i class="fas fa-robot"></i> AI Insights</a>
            <a href="profile.php" class="nav-item"><i class="fas fa-user-circle"></i> Profile</a>
            <a href="#" onclick="logout()" class="nav-item"><i class="fas fa-right-from-bracket"></i> Logout</a>
        </nav>
    </aside>

    <div class="sidebar-backdrop" onclick="toggleSidebar()"></div>

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button class="mobile-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <h2><i class="fas fa-calendar-alt"></i> Weekly Schedule</h2>
            </div>
            <div class="user-profile">
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
            </div>
        </header>

        <div class="dashboard-container">
            <!-- Today's Classes -->
            <div class="section-card" style="border-left: 4px solid var(--primary);">
                <div class="section-header">
                    <h3><i class="fas fa-sun" style="color: var(--primary);"></i> Today's Classes</h3>
                    <span id="todayDate" style="color: var(--gray); font-size: 14px;"></span>
                </div>
                <div id="todayClasses" style="display: grid; gap: 12px;">
                    <div class="empty-state"><div class="loading-spinner"></div></div>
                </div>
            </div>

            <!-- Weekly Schedule -->
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-calendar-week"></i> Full Weekly Schedule</h3>
                    <button class="btn btn-secondary" onclick="loadFullSchedule()" style="padding: 6px 12px;">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                </div>
                <div id="scheduleByDay"></div>
            </div>
        </div>
    </main>

    <script src="../assets/js/student.js"></script>
    <script>
        const DAY_SHORT = { 'Monday':'mon','Tuesday':'tue','Wednesday':'wed','Thursday':'thu','Friday':'fri','Saturday':'sat','Sunday':'sun' };
        const DAY_EMOJI = { 'Monday':'🌤','Tuesday':'🌥','Wednesday':'☀️','Thursday':'🌦','Friday':'🌅','Saturday':'🌙','Sunday':'🌟' };
        
        async function loadTodayClasses() {
            const container = document.getElementById('todayClasses');
            const todayDate = document.getElementById('todayDate');
            if (!container) return;
            
            const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const today = days[new Date().getDay()];
            const todayFormatted = new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
            if (todayDate) todayDate.textContent = todayFormatted;
            
            container.innerHTML = '<div class="empty-state"><div class="loading-spinner"></div></div>';
            
            try {
                const response = await fetch('../../api/schedule/get.php');
                const data = await response.json();
                
                if (!data.success || !data.data || data.data.length === 0) {
                    container.innerHTML = '<div class="empty-state"><i class="fas fa-calendar-xmark"></i><p>No classes scheduled yet</p><small>Contact admin to enroll you in courses and set up schedules</small></div>';
                    return;
                }
                
                const todayClasses = data.data.filter(s => s.day_of_week === today);
                
                if (todayClasses.length === 0) {
                    container.innerHTML = '<div class="empty-state"><i class="fas fa-calendar-xmark"></i><p>No classes today</p><small>Enjoy your free day!</small></div>';
                    return;
                }
                
                container.innerHTML = todayClasses.map(cls => `
                    <div class="sched-today-card">
                        <div class="sched-today-left">
                            <div class="sched-today-icon"><i class="fas fa-book"></i></div>
                            <div class="sched-today-info">
                                <h4>${escapeHtml(cls.course_name)}</h4>
                                <small>${escapeHtml(cls.course_code)} • Room ${escapeHtml(cls.room_number || 'TBA')}</small>
                            </div>
                        </div>
                        <div class="sched-today-time">${formatTime(cls.start_time)} - ${formatTime(cls.end_time)}</div>
                    </div>
                `).join('');
            } catch (err) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Failed to load</p></div>';
            }
        }
        
        async function loadFullSchedule() {
            const container = document.getElementById('scheduleByDay');
            if (!container) return;
            
            container.innerHTML = '<div class="empty-state"><div class="loading-spinner"></div></div>';
            
            try {
                const response = await fetch('../../api/schedule/get.php');
                const data = await response.json();
                
                if (!data.success || !data.data || data.data.length === 0) {
                    container.innerHTML = '<div class="empty-state"><i class="fas fa-calendar-xmark"></i><p>No schedule available</p><small>Contact admin to enroll you in courses and set up schedules</small></div>';
                    return;
                }
                
                const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                const dayOrder = { 'Monday': 1, 'Tuesday': 2, 'Wednesday': 3, 'Thursday': 4, 'Friday': 5, 'Saturday': 6, 'Sunday': 7 };
                const today = days[new Date().getDay()];
                
                const sorted = [...data.data].sort((a, b) => 
                    (dayOrder[a.day_of_week] || 0) - (dayOrder[b.day_of_week] || 0)
                );
                
                const grouped = {};
                days.forEach(day => grouped[day] = []);
                sorted.forEach(cls => {
                    if (grouped[cls.day_of_week]) grouped[cls.day_of_week].push(cls);
                });
                
                let html = '';
                days.forEach(day => {
                    const isToday = day === today;
                    const clsArr = grouped[day];
                    if (clsArr.length === 0) return;
                    
                    html += `
                        <div style="margin-bottom: 24px;">
                            <div class="sched-day-header ${isToday ? 'today' : 'other'}">
                                <span class="day-emoji">${DAY_EMOJI[day] || '📅'}</span>
                                ${day} ${isToday ? '(Today)' : ''}
                                <span class="day-count">${clsArr.length} class${clsArr.length > 1 ? 'es' : ''}</span>
                            </div>
                            ${clsArr.map(cls => `
                                <div class="sched-card ${DAY_SHORT[day] || ''}">
                                    <div class="sched-card-left">
                                        <div class="sched-card-icon ${DAY_SHORT[day] || ''}"><i class="fas fa-book"></i></div>
                                        <div class="sched-card-info">
                                            <h4>${escapeHtml(cls.course_name)}</h4>
                                            <div class="meta">
                                                <span class="code-badge">${escapeHtml(cls.course_code)}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="sched-card-time">
                                        <div class="time"><i class="fas fa-clock"></i>${formatTime(cls.start_time)} - ${formatTime(cls.end_time)}</div>
                                        <div class="room"><i class="fas fa-door-open"></i> ${escapeHtml(cls.room_number || 'TBA')}</div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    `;
                });
                
                if (!html) {
                    container.innerHTML = '<div class="empty-state"><i class="fas fa-calendar-xmark"></i><p>No schedule found</p></div>';
                } else {
                    container.innerHTML = html;
                }
            } catch (err) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Failed to load schedule</p></div>';
            }
        }
        
        document.addEventListener('DOMContentLoaded', () => {
            loadTodayClasses();
            loadFullSchedule();
            // Auto-poll every 30s for schedule updates
            setInterval(() => { loadTodayClasses(); loadFullSchedule(); }, 30000);
        });
    </script>
</body>
</html>
