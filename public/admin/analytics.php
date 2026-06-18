<?php
require_once '../../config/config.php';

// Security: Check Admin Role
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
    <title>AI Analytics - Smart Campus Assistant</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: var(--shadow);
        }
        .stat-box h2 {
            font-size: 32px;
            margin: 0 0 5px;
        }
        .stat-box p {
            margin: 0;
            color: var(--gray);
            font-size: 14px;
        }
        .stat-box.blue { border-left: 4px solid #667eea; }
        .stat-box.blue h2 { color: #667eea; }
        .stat-box.red { border-left: 4px solid #ef4444; }
        .stat-box.red h2 { color: #ef4444; }
        .stat-box.green { border-left: 4px solid #10b981; }
        .stat-box.green h2 { color: #10b981; }
        .stat-box.orange { border-left: 4px solid #f59e0b; }
        .stat-box.orange h2 { color: #f59e0b; }
        
        .type-badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .type-badge.schedule { background: #e0e7ff; color: #3730a3; }
        .type-badge.attendance { background: #fef3c7; color: #92400e; }
        .type-badge.performance { background: #d1fae5; color: #065f46; }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--gray);
        }
        .empty-state i {
            font-size: 48px;
            margin-bottom: 12px;
            color: #d1d5db;
        }
        
        .filter-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
        }
        .filter-tab {
            padding: 8px 16px;
            border: 1px solid var(--border);
            background: white;
            border-radius: 20px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
        }
        .filter-tab:hover, .filter-tab.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo-icon"><i class="fas fa-user-shield"></i></div>
            <h3>Smart Campus Assistant</h3>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
            <a href="students.php" class="nav-item"><i class="fas fa-users"></i> Students</a>
            <a href="courses.php" class="nav-item"><i class="fas fa-book"></i> Courses</a>
            <a href="manage-courses.php" class="nav-item"><i class="fas fa-user-graduate"></i> Enrollments</a>
            <a href="attendance.php" class="nav-item"><i class="fas fa-clipboard-check"></i> Attendance</a>
            <a href="schedule.php" class="nav-item"><i class="fas fa-calendar-alt"></i> Schedules</a>
            <a href="bookings.php" class="nav-item"><i class="fas fa-door-open"></i> Approve Bookings</a>
            <a href="resources.php" class="nav-item"><i class="fas fa-building"></i> Resources</a>
            <a href="analytics.php" class="nav-item active"><i class="fas fa-chart-line"></i> AI Analytics</a>
            <a href="profile.php" class="nav-item"><i class="fas fa-user-circle"></i> Profile</a>
            <a href="#" onclick="logout()" class="nav-item"><i class="fas fa-right-from-bracket"></i> Logout</a>
        </nav>
    </aside>

    <div class="sidebar-backdrop" onclick="toggleSidebar()"></div>

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button class="mobile-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <h2><i class="fas fa-robot"></i> AI Analytics Dashboard</h2>
            </div>
            <div class="user-profile">
                <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($user_name); ?></span>
            </div>
        </header>

        <div class="dashboard-container">
            <!-- Stats Row -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                <div class="stat-box blue">
                    <h2 id="statTotal">0</h2>
                    <p><i class="fas fa-brain"></i> Total Predictions</p>
                </div>
                <div class="stat-box red">
                    <h2 id="statHighRisk">0</h2>
                    <p><i class="fas fa-exclamation-triangle"></i> High Risk Students</p>
                </div>
                <div class="stat-box green">
                    <h2 id="statScheduleOpt">0</h2>
                    <p><i class="fas fa-calendar-check"></i> Schedule Optimizations</p>
                </div>
                <div class="stat-box orange">
                    <h2 id="statAttendance">0</h2>
                    <p><i class="fas fa-clipboard-check"></i> Attendance Analyses</p>
                </div>
            </div>

            <!-- Analytics Table -->
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-list"></i> AI Analysis History</h3>
                    <button class="btn btn-primary" onclick="loadAnalytics()">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                </div>

                <div class="filter-tabs">
                    <button class="filter-tab active" onclick="filterByType('all')">All</button>
                    <button class="filter-tab" onclick="filterByType('attendance_risk')">Attendance Risk</button>
                    <button class="filter-tab" onclick="filterByType('schedule_optimization')">Schedule Optimization</button>
                    <button class="filter-tab" onclick="filterByType('performance')">Performance</button>
                </div>

                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Student</th>
                                <th>Type</th>
                                <th>Risk Level</th>
                                <th>Details</th>
                                <th>Generated</th>
                            </tr>
                        </thead>
                        <tbody id="analyticsBody">
                            <tr><td colspan="6" class="empty-state"><i class="fas fa-spinner fa-spin"></i> Loading analytics...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Student Risk Summary -->
            <div class="section-card" style="margin-top: 20px;">
                <div class="section-header">
                    <h3><i class="fas fa-exclamation-circle" style="color: var(--danger); font-size: 20px;"></i> Students at Risk</h3>
                </div>
                <div id="riskStudentsBody">
                    <div class="empty-state"><i class="fas fa-check-circle" style="color: #10b981;"></i><p>No students at high risk</p></div>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/admin.js?v=<?php echo filemtime('../assets/js/admin.js'); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', loadAnalytics);

    </script>
</body>
</html>
