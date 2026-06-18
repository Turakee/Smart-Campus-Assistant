<?php
require_once '../../config/config.php';

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'administrator') {
    header('Location: ../index.php');
    exit;
}

$user_name = $_SESSION['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Smart Campus Assistant</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo-icon"><i class="fas fa-user-shield"></i></div>
            <h3>Smart Campus Assistant</h3>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item active"><i class="fas fa-home"></i> Dashboard</a>
            <a href="students.php" class="nav-item"><i class="fas fa-users"></i> Students</a>
            <a href="courses.php" class="nav-item"><i class="fas fa-book"></i> Courses</a>
            <a href="manage-courses.php" class="nav-item"><i class="fas fa-user-graduate"></i> Enrollments</a>
            <a href="attendance.php" class="nav-item"><i class="fas fa-clipboard-check"></i> Attendance</a>
            <a href="schedule.php" class="nav-item"><i class="fas fa-calendar-alt"></i> Schedules</a>
            <a href="bookings.php" class="nav-item"><i class="fas fa-door-open"></i> Approve Bookings</a>
            <a href="resources.php" class="nav-item"><i class="fas fa-building"></i> Resources</a>
            <a href="analytics.php" class="nav-item"><i class="fas fa-chart-line"></i> AI Analytics</a>
            <a href="profile.php" class="nav-item"><i class="fas fa-user-circle"></i> Profile</a>
            <a href="#" onclick="logout()" class="nav-item"><i class="fas fa-right-from-bracket"></i> Logout</a>
        </nav>
    </aside>

    <div class="sidebar-backdrop" onclick="toggleSidebar()"></div>

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button class="mobile-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <h2><i class="fas fa-user-shield"></i> Admin Dashboard</h2>
            </div>
            <div class="user-profile">
                <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($user_name); ?></span>
            </div>
        </header>

        <div class="dashboard-container">
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-users"></i></div>
                    <div class="stat-details">
                        <h3 id="statStudents">0</h3>
                        <p>Total Students</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
                    <div class="stat-details">
                        <h3 id="statPendingBookings">0</h3>
                        <p>Pending Bookings</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="stat-details">
                        <h3 id="statAtRisk">0</h3>
                        <p>At-Risk Students</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-check-double"></i></div>
                    <div class="stat-details">
                        <h3 id="statAvgAttendance">0%</h3>
                        <p>Avg Attendance</p>
                    </div>
                </div>
            </div>

            <!-- Pending Bookings -->
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-tasks"></i> Pending Booking Approvals</h3>
                    <button class="btn btn-primary" onclick="loadPendingBookings()">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Resource</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="bookingTableBody">
                        <tr><td colspan="5" class="empty-state"><div class="loading-spinner"></div></td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Notifications Forms -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 28px;">
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="fas fa-calendar-plus"></i> Create Event</h3>
                    </div>
                    <form id="eventForm">
                        <div class="form-group">
                            <label>Event Title</label>
                            <input type="text" id="eventTitle" required placeholder="Event title">
                        </div>
                        <div class="form-group">
                            <label>Event Date</label>
                            <input type="date" id="eventDate" required>
                        </div>
                        <div class="form-group">
                            <label>Details</label>
                            <textarea id="eventMessage" rows="3" required placeholder="Event details..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-paper-plane"></i> Send to All Students
                        </button>
                    </form>
                </div>

                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="fas fa-megaphone"></i> Create Announcement</h3>
                    </div>
                    <form id="announcementForm">
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" id="announcementTitle" required placeholder="Announcement title">
                        </div>
                        <div class="form-group">
                            <label>Message</label>
                            <textarea id="announcementMessage" rows="5" required placeholder="Announcement message..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-bullhorn"></i> Send Announcement
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/admin.js?v=<?php echo filemtime('../assets/js/admin.js'); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            loadAdminStats();
            loadPendingBookings();
            initNotificationForms();
        });
    </script>
</body>
</html>
