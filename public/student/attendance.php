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
    <title>Attendance - Smart Campus Assistant</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
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
            <a href="schedule.php" class="nav-item"><i class="fas fa-calendar-alt"></i> Schedule</a>
            <a href="attendance.php" class="nav-item active"><i class="fas fa-clipboard-check"></i> Attendance</a>
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
                <h2><i class="fas fa-clipboard-check"></i> Attendance Records</h2>
            </div>
            <div class="user-profile">
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
            </div>
        </header>

        <div class="dashboard-container">
            <!-- Stats -->
            <div class="mini-stats">
                <div class="mini-stat" style="border-left: 4px solid var(--primary);">
                    <i class="fas fa-chart-pie" style="color: var(--primary);"></i>
                    <h2 id="statPercentage">0%</h2>
                    <p>Attendance Rate</p>
                </div>
                <div class="mini-stat" style="border-left: 4px solid var(--success);">
                    <i class="fas fa-check-circle" style="color: var(--success);"></i>
                    <h2 id="statPresent">0</h2>
                    <p>Present</p>
                </div>
                <div class="mini-stat" style="border-left: 4px solid var(--danger);">
                    <i class="fas fa-times-circle" style="color: var(--danger);"></i>
                    <h2 id="statAbsent">0</h2>
                    <p>Absent</p>
                </div>
                <div class="mini-stat" style="border-left: 4px solid var(--warning);">
                    <i class="fas fa-clock" style="color: var(--warning);"></i>
                    <h2 id="statLate">0</h2>
                    <p>Late</p>
                </div>
                <div class="mini-stat" style="border-left: 4px solid #6366f1;">
                    <i class="fas fa-file-pen" style="color: #6366f1;"></i>
                    <h2 id="statExcused">0</h2>
                    <p>Excused</p>
                </div>
            </div>

            <!-- Attendance Table -->
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-list"></i> Attendance History</h3>
                    <a href="../../api/attendance/export.php" class="btn btn-danger" style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;" download><i class="fas fa-file-pdf"></i> Export PDF</a>
                </div>
                
                <div class="filter-tabs">
                    <button class="filter-tab active" onclick="filterAttendance('all')">All</button>
                    <button class="filter-tab" onclick="filterAttendance('present')">Present</button>
                    <button class="filter-tab" onclick="filterAttendance('absent')">Absent</button>
                    <button class="filter-tab" onclick="filterAttendance('late')">Late</button>
                    <button class="filter-tab" onclick="filterAttendance('excused')">Excused</button>
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
        </div>
    </main>

    <script src="../assets/js/student.js?v=<?php echo filemtime('../assets/js/student.js'); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', loadAttendance);
    </script>
</body>
</html>
