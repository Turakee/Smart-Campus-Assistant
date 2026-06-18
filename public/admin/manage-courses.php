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
    <title>Enrollment Management - Smart Campus Assistant</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .enroll-row { display: flex; flex-wrap: wrap; gap: 16px; align-items: end; }
        .enroll-row .form-group { flex: 1; min-width: 200px; }
        .enroll-table { border-collapse: separate; border-spacing: 0; width: 100%; }
        .enroll-table th { background: var(--light); padding: 10px 12px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--gray); border-bottom: 2px solid var(--border); }
        .enroll-table td { padding: 10px 12px; border-bottom: 1px solid var(--light-2); vertical-align: middle; }
        .enroll-table tr:last-child td { border-bottom: none; }
        .bulk-result-box { padding: 12px 16px; border-radius: 8px; margin-top: 12px; font-size: 14px; display: none; }
        .bulk-result-box.success { display: block; background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .bulk-result-box.error { display: block; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .bulk-result-box .num { font-weight: 700; font-size: 18px; }
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
            <a href="manage-courses.php" class="nav-item active"><i class="fas fa-user-graduate"></i> Enrollments</a>
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
                <h2><i class="fas fa-user-graduate"></i> Enrollment Management</h2>
            </div>
            <div class="user-profile">
                <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($user_name); ?></span>
            </div>
        </header>

        <div class="dashboard-container">
            <!-- Stats -->
            <div class="mini-stats">
                <div class="mini-stat" style="border-left-color: #10b981;">
                    <i class="fas fa-graduation-cap" style="color: #10b981;"></i>
                    <h2 id="totalEnrollments">0</h2>
                    <p>Total Enrollments</p>
                </div>
                <div class="mini-stat" style="border-left-color: #6366f1;">
                    <i class="fas fa-users" style="color: #6366f1;"></i>
                    <h2 id="totalStudents">0</h2>
                    <p>Total Students</p>
                </div>
            </div>

            <!-- Single + Bulk Enroll side by side -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <!-- Enroll Student -->
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="fas fa-user-plus"></i> Enroll Student</h3>
                    </div>
                    <form id="enrollForm">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Student</label>
                            <select id="enrollStudentId" class="form-control" required>
                                <option value="">Loading...</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-book"></i> Course</label>
                            <select id="enrollCourseId" class="form-control" required>
                                <option value="">Loading...</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%;">
                            <i class="fas fa-user-plus"></i> Enroll
                        </button>
                    </form>
                </div>

                <!-- Bulk Enroll -->
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="fas fa-users-gear"></i> Bulk Enroll by Department</h3>
                    </div>
                    <p style="color: var(--gray); font-size: 14px; margin-bottom: 16px; line-height: 1.5;">
                        Enroll all students in a department to every course assigned to that department in one click.
                    </p>
                    <div class="enroll-row">
                        <div class="form-group">
                            <label><i class="fas fa-building"></i> Department</label>
                            <select id="bulkDepartment" class="form-control">
                                <option value="">Select department...</option>
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-primary" onclick="bulkEnrollByDepartment()" style="width:100%;">
                        <i class="fas fa-rocket"></i> Enroll All
                    </button>
                    <div id="bulkResult" class="bulk-result-box"></div>
                </div>
            </div>

            <!-- Current Enrollments -->
            <div class="section-card" style="margin-top: 24px;">
                <div class="section-header">
                    <h3><i class="fas fa-list-check"></i> Current Enrollments</h3>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <select id="enrollmentDeptFilter" onchange="filterEnrollments()" style="padding: 8px 10px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px;">
                            <option value="">All Departments</option>
                        </select>
                        <input type="text" id="enrollmentFilter" placeholder="Search enrollments..." style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; width: 220px; font-size: 13px;" oninput="filterEnrollments()">
                    </div>
                </div>
                <div style="overflow-x: auto;">
                    <table class="enroll-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Code</th>
                                <th>Course</th>
                                <th>Enrolled</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="enrollmentsTableBody">
                            <tr><td colspan="5" class="empty-state"><div class="loading-spinner"></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/admin.js?v=<?php echo filemtime('../assets/js/admin.js'); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof fetchStudents === 'function') fetchStudents();
            if (typeof fetchCourseSelect === 'function') fetchCourseSelect();
            if (typeof fetchEnrollments === 'function') fetchEnrollments();
            const ef = document.getElementById('enrollForm');
            if (ef && typeof enrollStudent === 'function') ef.addEventListener('submit', enrollStudent);
            loadDepartments();
        });

        async function loadDepartments() {
            const sel = document.getElementById('bulkDepartment');
            if (!sel) return;
            try {
                const res = await fetch('../../api/student/list.php');
                const data = await res.json();
                if (data.success && data.data) {
                    const depts = [...new Set(data.data.map(s => s.department).filter(Boolean))];
                    sel.innerHTML = '<option value="">Select department...</option>' +
                        depts.map(d => `<option value="${escapeHtml(d)}">${escapeHtml(d)}</option>`).join('');
                }
            } catch (e) {
                sel.innerHTML = '<option value="">Failed to load</option>';
            }
        }

        async function bulkEnrollByDepartment() {
            const dept = document.getElementById('bulkDepartment').value;
            if (!dept) { showToast('Please select a department', 'error'); return; }
            if (!confirm('Enroll all students in "' + dept + '" to all "' + dept + '" courses?')) return;

            const resultDiv = document.getElementById('bulkResult');
            resultDiv.className = 'bulk-result-box';
            resultDiv.innerHTML = '<div class="loading-spinner"></div><p style="margin-top:8px;">Processing...</p>';

            try {
                const res = await fetch('../../api/course/bulk-enroll-by-department.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ department: dept })
                });
                const data = await res.json();
                if (data.success) {
                    resultDiv.className = 'bulk-result-box success';
                    resultDiv.innerHTML = '<i class="fas fa-check-circle"></i> <strong>Done!</strong> Created <span class="num">' + data.data.enrollments_created + '</span> enrollment(s) <span style="color:#6b7280;font-size:13px;">(' + data.data.students_count + ' students &times; ' + data.data.courses_count + ' courses, ' + data.data.enrollments_skipped + ' skipped)</span>';
                    fetchEnrollments();
                } else {
                    resultDiv.className = 'bulk-result-box error';
                    resultDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + (data.message || 'Bulk enrollment failed');
                }
            } catch (e) {
                resultDiv.className = 'bulk-result-box error';
                resultDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Network error: ' + escapeHtml(e.message);
            }
        }

        function logout() {
            if (confirm('Logout?')) {
                fetch('../../api/auth/logout.php').then(() => window.location.href = '../index.php');
            }
        }

    </script>
</body>
</html>
