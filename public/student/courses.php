<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get student_id and department
$stmt = $db->prepare("SELECT student_id, department FROM students WHERE user_id = :uid");
$stmt->execute([':uid' => $_SESSION['user_id']]);
$student = $stmt->fetch();
$studentDeptId = null;
if ($student && !empty($student['department'])) {
    $deptStmt = $db->prepare("SELECT department_id FROM departments WHERE department_name = :name");
    $deptStmt->execute([':name' => $student['department']]);
    $deptRow = $deptStmt->fetch();
    $studentDeptId = $deptRow ? $deptRow['department_id'] : null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - Smart Campus Assistant</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" crossorigin="anonymous">
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
            <a href="attendance.php" class="nav-item"><i class="fas fa-clipboard-check"></i> Attendance</a>
            <a href="courses.php" class="nav-item active"><i class="fas fa-book"></i> Courses</a>
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
                <h2><i class="fas fa-book"></i> My Courses</h2>
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
                    <i class="fas fa-book-open" style="color: var(--primary);"></i>
                    <h2 id="totalCourses">0</h2>
                    <p>Enrolled Courses</p>
                </div>
                <div class="mini-stat" style="border-left: 4px solid var(--success);">
                    <i class="fas fa-clock" style="color: var(--success);"></i>
                    <h2 id="totalCredits">0</h2>
                    <p>Total Credits</p>
                </div>
                <div class="mini-stat" style="border-left: 4px solid var(--info);">
                    <i class="fas fa-chalkboard-user" style="color: var(--info);"></i>
                    <h2 id="totalLecturers">0</h2>
                    <p>Lecturers</p>
                </div>
            </div>

            <!-- Enrolled Courses -->
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-graduation-cap"></i> Enrolled Courses</h3>
                </div>
                <div id="enrolledCoursesContainer">
                    <div class="empty-state"><div class="loading-spinner"></div></div>
                </div>
            </div>

            <!-- Browse Available Courses -->
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-search"></i> Browse Available Courses <span style="font-size:12px;font-weight:400;color:var(--gray);margin-left:8px;">(<?php echo htmlspecialchars($student['department'] ?? 'General'); ?> department)</span></h3>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="text" id="courseSearch" placeholder="Search courses..." style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; width: 200px;" oninput="loadAvailableCourses()">
                        <button class="btn btn-primary" onclick="loadAvailableCourses()"><i class="fas fa-sync"></i> Refresh</button>
                    </div>
                </div>
                <div id="availableCoursesContainer">
                    <div class="empty-state"><div class="loading-spinner"></div></div>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/student.js"></script>
    <script>
        let enrolledCourseIds = new Set();
        const studentDeptId = <?php echo json_encode($studentDeptId); ?>;

        document.addEventListener('DOMContentLoaded', () => {
            loadEnrolledCourses();
            loadAvailableCourses();
        });

        async function loadEnrolledCourses() {
            const container = document.getElementById('enrolledCoursesContainer');
            container.innerHTML = '<div class="empty-state"><div class="loading-spinner"></div></div>';

            try {
                const response = await fetch('../../api/course/my-courses.php');
                const data = await response.json();

                if (data.success && data.data && data.data.length > 0) {
                    const courses = data.data;
                    enrolledCourseIds = new Set(courses.map(c => c.course_id));
                    let totalCredits = 0;
                    let lecturers = new Set();

                    container.innerHTML = courses.map(c => {
                        totalCredits += parseInt(c.credit_hours) || 0;
                        if (c.lecturer_name) lecturers.add(c.lecturer_name);
                        const schedHtml = (c.schedules && c.schedules.length > 0)
                            ? `<div class="course-card-schedules">${c.schedules.map(s => `<span class="sched-tag"><i class="fas fa-calendar-day"></i> ${escapeHtml(s.day_of_week)} ${formatTime(s.start_time)}-${formatTime(s.end_time)} <span class="room"><i class="fas fa-door-open"></i> ${escapeHtml(s.room_number || 'TBA')}</span></span>`).join('')}</div>`
                            : '<div class="no-schedule"><i class="fas fa-clock"></i> No schedule set</div>';
                        return `
                            <div class="course-card">
                                <div class="course-card-left">
                                    <div class="course-card-icon"><i class="fas fa-book"></i></div>
                                    <div class="course-card-info">
                                        <h4>${escapeHtml(c.course_name)}</h4>
                                        <div class="meta">
                                            <span class="code-badge">${escapeHtml(c.course_code)}</span>
                                            ${c.credit_hours ? '<span>&bull;</span><span>' + c.credit_hours + ' Credits</span>' : ''}
                                        </div>
                                    </div>
                                </div>
                                <div class="course-card-right">
                                    <div class="lecturer"><i class="fas fa-user-tie"></i> ${c.lecturer_name ? escapeHtml(c.lecturer_name) : 'TBA'}</div>
                                </div>
                                ${schedHtml}
                            </div>
                        `;
                    }).join('');

                    document.getElementById('totalCourses').textContent = courses.length;
                    document.getElementById('totalCredits').textContent = totalCredits;
                    document.getElementById('totalLecturers').textContent = lecturers.size;
                } else {
                    container.innerHTML = '<div class="empty-state"><i class="fas fa-graduation-cap"></i><p>You are not enrolled in any courses yet</p><small>Browse available courses below to enroll</small></div>';
                    document.getElementById('totalCourses').textContent = '0';
                    document.getElementById('totalCredits').textContent = '0';
                    document.getElementById('totalLecturers').textContent = '0';
                }
            } catch (err) {
                console.error('Error loading enrolled courses:', err);
                container.innerHTML = '<div class="empty-state text-danger"><i class="fas fa-exclamation-triangle"></i><p>Failed to load courses</p></div>';
            }
        }

        async function loadAvailableCourses() {
            const container = document.getElementById('availableCoursesContainer');
            const search = document.getElementById('courseSearch').value.trim();

            container.innerHTML = '<div class="empty-state"><div class="loading-spinner"></div></div>';

            try {
                let params = new URLSearchParams();
                if (studentDeptId) params.set('department_id', studentDeptId);
                if (search) params.set('search', search);
                const qs = params.toString();
                const url = qs ? `../../api/course/list.php?${qs}` : '../../api/course/list.php';
                const response = await fetch(url);
                const data = await response.json();

                if (data.success && data.data && data.data.length > 0) {
                    const available = data.data.filter(c => !enrolledCourseIds.has(c.course_id));

                    if (available.length === 0) {
                        container.innerHTML = '<div class="empty-state"><i class="fas fa-check-circle" style="color: var(--success);"></i><p>You are enrolled in all available courses</p></div>';
                        return;
                    }

                    container.innerHTML = available.map(c => `
                        <div class="course-card available">
                            <div class="course-card-left">
                                <div class="course-card-icon"><i class="fas fa-book"></i></div>
                                <div class="course-card-info">
                                    <h4>${escapeHtml(c.course_name)}</h4>
                                    <div class="meta">
                                        <span class="code-badge">${escapeHtml(c.course_code)}</span>
                                        ${c.credit_hours ? '<span>&bull;</span><span>' + c.credit_hours + ' Credits</span>' : ''}
                                    </div>
                                </div>
                            </div>
                            <div class="course-card-right">
                                <div class="lecturer"><i class="fas fa-user-tie"></i> ${c.lecturer_name ? escapeHtml(c.lecturer_name) : 'TBA'}</div>
                            </div>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = '<div class="empty-state"><i class="fas fa-search"></i><p>No courses found</p></div>';
                }
            } catch (err) {
                console.error('Error loading available courses:', err);
                container.innerHTML = '<div class="empty-state text-danger"><i class="fas fa-exclamation-triangle"></i><p>Failed to load courses</p></div>';
            }
        }

    </script>
</body>
</html>
