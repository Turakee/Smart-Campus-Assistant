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
    <title>Dashboard - Smart Campus Assistant</title>
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
            <a href="dashboard.php" class="nav-item active"><i class="fas fa-home"></i> Dashboard</a>
            <a href="schedule.php" class="nav-item"><i class="fas fa-calendar-alt"></i> Schedule</a>
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
                <h2><i class="fas fa-home"></i> Student Dashboard</h2>
            </div>
            <div class="user-profile">
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
            </div>
        </header>

        <div class="dashboard-container">
            <!-- Welcome Banner -->
            <div class="welcome-section">
                <div class="welcome-text">
                    <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>! <span class="wave">👋</span></h1>
                    <p><span class="greeting-dot"></span> Here's your learning overview for today</p>
                </div>
                <div class="welcome-time">
                    <div class="time" id="currentTime">--:--</div>
                    <div class="date"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-book"></i></div>
                    <div class="stat-details">
                        <h3 id="statCourses">0</h3>
                        <p>Active Courses</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-details">
                        <h3 id="statAttendance">0%</h3>
                        <p>Attendance Rate</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
                    <div class="stat-details">
                        <h3 id="statBookingsPending">0</h3>
                        <p>Pending Bookings</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon teal"><i class="fas fa-door-open"></i></div>
                    <div class="stat-details">
                        <h3 id="statBookingsApproved">0</h3>
                        <p>Approved Bookings</p>
                    </div>
                </div>
            </div>

            <!-- My Bookings Section -->
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-calendar-check"></i> My Approved Bookings</h3>
                    <a href="booking.php" class="btn btn-secondary"><i class="fas fa-external-link-alt"></i> View All</a>
                </div>
                <div id="approvedBookingsList" class="booking-list">
                    <div class="empty-state"><div class="loading-spinner"></div></div>
                </div>
            </div>

            <!-- My Enrolled Courses -->
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-graduation-cap"></i> My Enrolled Courses</h3>
                </div>
                <div id="enrolledCoursesList" class="course-list">
                    <div class="empty-state"><div class="loading-spinner"></div></div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 28px;">
                <!-- Today's Schedule Section -->
                <div class="section-card" style="border-left: 4px solid var(--primary);">
                    <div class="section-header">
                        <h3><i class="fas fa-sun" style="color: var(--primary);"></i> Today's Classes</h3>
                        <a href="schedule.php" class="btn btn-secondary"><i class="fas fa-calendar-alt"></i> Full Schedule</a>
                    </div>
                    <div id="todayScheduleList" style="display: grid; gap: 10px;">
                        <div class="empty-state"><div class="loading-spinner"></div></div>
                    </div>
                </div>

                <!-- Notifications Section -->
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="fas fa-bell"></i> Notifications</h3>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <button class="btn" onclick="toggleNotifMenu()" style="padding: 6px 10px;">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <span id="notificationBadge" class="badge" style="display:none;">0</span>
                            <div id="notifDropdown" style="display:none; position:absolute; right:32px; top:180px; background:white; border-radius: var(--radius); box-shadow: var(--shadow-lg); z-index:100; min-width:180px;">
                                <button onclick="markAllAsRead()" style="width:100%; padding:12px 16px; border:none; background:none; text-align:left; cursor:pointer; display:flex; align-items:center; gap:10px;">
                                    <i class="fas fa-check-double" style="color:var(--success);"></i> Mark all read
                                </button>
                                <button onclick="clearAllNotifications()" style="width:100%; padding:12px 16px; border:none; background:none; text-align:left; cursor:pointer; display:flex; align-items:center; gap:10px; border-top:1px solid var(--border);">
                                    <i class="fas fa-trash" style="color:var(--danger);"></i> Clear all
                                </button>
                            </div>
                        </div>
                    </div>
                    <div id="notificationsList" class="notification-list"></div>
                </div>
            </div>

            <!-- Attendance Section -->
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-clipboard-list"></i> Recent Attendance</h3>
                    <a href="attendance.php" class="btn btn-secondary"><i class="fas fa-up-right-and-down-left-from-center"></i> View All</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceTableBody">
                        <tr><td colspan="3" class="empty-state"><div class="loading-spinner"></div></td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Quick Actions -->
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                </div>
                <div class="quick-actions">
                    <a href="booking.php" class="quick-action-btn" style="background: linear-gradient(135deg, rgba(99,102,241,0.1), rgba(99,102,241,0.2)); border-color: var(--primary);">
                        <i class="fas fa-door-open" style="color: var(--primary);"></i>
                        Book Room
                    </a>
                    <a href="ai-insights.php" class="quick-action-btn" style="background: linear-gradient(135deg, rgba(16,185,129,0.1), rgba(16,185,129,0.2)); border-color: var(--success);">
                        <i class="fas fa-robot" style="color: var(--success);"></i>
                        AI Insights
                    </a>
                    <a href="schedule.php" class="quick-action-btn" style="background: linear-gradient(135deg, rgba(100,116,139,0.1), rgba(100,116,139,0.2)); border-color: var(--secondary);">
                        <i class="fas fa-calendar-alt" style="color: var(--secondary);"></i>
                        Full Schedule
                    </a>
                    <a href="attendance.php" class="quick-action-btn" style="background: linear-gradient(135deg, rgba(245,158,11,0.1), rgba(245,158,11,0.2)); border-color: var(--warning);">
                        <i class="fas fa-clipboard-check" style="color: var(--warning);"></i>
                        Attendance
                    </a>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/student.js?v=<?php echo filemtime('../assets/js/student.js'); ?>"></script>
    <script>
        function updateClock() {
            const now = new Date();
            document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', {
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
            });
            document.getElementById('currentTime').textContent = now.toLocaleTimeString('en-US', {
                hour: '2-digit', minute: '2-digit'
            });
        }
        updateClock();
        setInterval(updateClock, 30000);
        document.addEventListener('DOMContentLoaded', loadDashboard);
    </script>
</body>
</html>
