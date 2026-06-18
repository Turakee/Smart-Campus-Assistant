<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
requireAuth();
requireRole(['administrator']);

$user_name = $_SESSION['name'];

$database = new Database();
$db = $database->getConnection();
$depts = $db->query("SELECT * FROM departments ORDER BY department_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - Smart Campus Assistant</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .course-grid { display: grid; grid-template-columns: 380px 1fr; gap: 24px; align-items: start; }
        @media (max-width: 900px) { .course-grid { grid-template-columns: 1fr; } }
        .form-card { background: white; border-radius: var(--radius); box-shadow: var(--shadow); padding: 24px; }
        .form-card h3 { font-size: 16px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 500; color: var(--gray); margin-bottom: 6px; }
        .form-group label i { width: 16px; margin-right: 4px; }
        .form-control { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; transition: border-color 0.2s; }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-secondary { background: var(--light); color: var(--gray); }
        .btn-secondary:hover { background: #e5e7eb; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-danger:hover { background: #dc2626; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .action-bar { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .table-wrap { overflow-x: auto; }
        .dept-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; background: #eef2ff; color: #4338ca; }
        .empty-state { text-align: center; padding: 40px 20px; color: var(--gray); }
        .empty-state i { font-size: 40px; margin-bottom: 12px; opacity: 0.4; }
        .search-input { padding: 8px 12px 8px 36px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; width: 240px; }
        .search-wrap { position: relative; }
        .search-wrap i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--gray); font-size: 14px; }
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
            <a href="courses.php" class="nav-item active"><i class="fas fa-book"></i> Courses</a>
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
                <h2><i class="fas fa-book"></i> Manage Courses</h2>
            </div>
            <div class="user-profile">
                <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($user_name); ?></span>
            </div>
        </header>

        <div class="dashboard-container">
            <!-- Stats -->
            <div class="mini-stats">
                <div class="mini-stat" style="border-left: 4px solid var(--primary);">
                    <i class="fas fa-book" style="color: var(--primary);"></i>
                    <h2 id="totalCourses">0</h2>
                    <p>Total Courses</p>
                </div>
                <div class="mini-stat" style="border-left: 4px solid #10b981;">
                    <i class="fas fa-building" style="color: #10b981;"></i>
                    <h2 id="totalDepartments">0</h2>
                    <p>Departments</p>
                </div>
            </div>

            <!-- Two-column layout -->
            <div class="course-grid">
                <!-- Form -->
                <div class="form-card">
                    <h3><i class="fas fa-<?php echo isset($_GET['edit']) ? 'edit' : 'plus-circle'; ?>"></i> <span id="formTitle">Create Course</span></h3>
                    <form id="courseForm">
                        <input type="hidden" id="courseId">
                        <div class="form-group">
                            <label><i class="fas fa-code"></i> Course Code</label>
                            <input type="text" id="courseCode" class="form-control" required placeholder="e.g. CSC101">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-book"></i> Course Name</label>
                            <input type="text" id="courseName" class="form-control" required placeholder="e.g. Introduction to Programming">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-clock"></i> Credits</label>
                                <select id="creditHours" class="form-control" required>
                                    <option value="">Select</option>
                                    <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $i === 3 ? 'selected' : ''; ?>><?php echo $i; ?> Credit<?php echo $i > 1 ? 's' : ''; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-building"></i> Department</label>
                                <select id="courseDepartment" class="form-control" required>
                                    <option value="">Select Department</option>
                                    <?php foreach ($depts as $d): ?>
                                    <option value="<?php echo $d['department_id']; ?>"><?php echo htmlspecialchars($d['department_name']); ?> (<?php echo htmlspecialchars($d['department_code']); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-chalkboard-teacher"></i> Lecturer</label>
                            <input type="text" id="courseLecturer" class="form-control" required placeholder="Dr. Name">
                        </div>
                        <div style="display: flex; gap: 10px; margin-top: 4px;">
                            <button type="submit" class="btn btn-primary" id="submitBtn" style="flex:1;"><i class="fas fa-plus"></i> Create Course</button>
                            <button type="button" class="btn btn-secondary" id="cancelBtn" style="display:none;" onclick="clearForm()"><i class="fas fa-times"></i> Cancel</button>
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <div class="form-card">
                    <h3><i class="fas fa-list"></i> All Courses</h3>
                    <div class="action-bar" style="margin-bottom: 16px;">
                        <div class="search-wrap" style="flex:1;">
                            <i class="fas fa-search"></i>
                            <input type="text" id="courseSearch" class="search-input" style="width:100%;" placeholder="Search courses..." oninput="filterCourses()">
                        </div>
                        <select id="deptFilter" onchange="filterCourses()" style="padding:8px 10px;border:1px solid var(--border);border-radius:8px;font-size:13px;">
                            <option value="">All Departments</option>
                            <?php foreach ($depts as $d): ?>
                            <option value="<?php echo $d['department_id']; ?>"><?php echo htmlspecialchars($d['department_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="table-wrap">
                        <table class="data-table" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Course Name</th>
                                    <th>Credits</th>
                                    <th>Department</th>
                                    <th>Lecturer</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="coursesTableBody">
                                <tr><td colspan="6" class="empty-state"><div class="loading-spinner"></div></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Delete Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal">
            <h3><i class="fas fa-exclamation-triangle" style="color: var(--danger);"></i> Confirm Delete</h3>
            <p id="deleteMessage">Delete this course?</p>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn btn-danger" onclick="deleteCourse()"><i class="fas fa-trash"></i> Delete</button>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin.js?v=<?php echo filemtime('../assets/js/admin.js'); ?>"></script>
    <script>
        let coursesData = [];

        document.addEventListener('DOMContentLoaded', () => {
            fetchCourses();
            document.getElementById('courseForm').addEventListener('submit', submitCourse);
        });

        async function fetchCourses() {
            const tbody = document.getElementById('coursesTableBody');
            if (!tbody) return;
            const totalEl = document.getElementById('totalCourses');
            const deptEl = document.getElementById('totalDepartments');
            tbody.innerHTML = '<tr><td colspan="6" class="empty-state"><div class="loading-spinner"></div></td></tr>';

            const data = await apiCall('../../api/course/list.php');
            if (data.success && data.data) {
                coursesData = data.data;
                if (totalEl) totalEl.textContent = data.data.length;
                if (deptEl) {
                    const depts = [...new Set(data.data.map(c => c.department_id).filter(Boolean))];
                    deptEl.textContent = depts.length;
                }
                renderCourses(coursesData);
            } else {
                tbody.innerHTML = '<tr><td colspan="6" class="empty-state"><i class="fas fa-book"></i><p>No courses yet</p></td></tr>';
            }
        }

        function renderCourses(courses) {
            const tbody = document.getElementById('coursesTableBody');
            if (!tbody) return;
            if (!courses.length) {
                tbody.innerHTML = '<tr><td colspan="6" class="empty-state"><i class="fas fa-book"></i><p>No courses found</p></td></tr>';
                return;
            }
            tbody.innerHTML = courses.map(c => `
                <tr>
                    <td><span class="status-badge info">${escapeHtml(c.course_code)}</span></td>
                    <td><strong>${escapeHtml(c.course_name)}</strong></td>
                    <td><span class="status-badge success" style="background:#d1fae5;color:#065f46;">${c.credit_hours} cr</span></td>
                    <td>${c.department_name ? '<span class="dept-badge"><i class="fas fa-building" style="font-size:10px;margin-right:3px;"></i>' + escapeHtml(c.department_name) + '</span>' : '<span style="color:var(--gray);font-size:12px;">—</span>'}</td>
                    <td>${escapeHtml(c.lecturer_name || '—')}</td>
                    <td>
                        <button class="btn btn-primary btn-sm" onclick="editCourse(${c.course_id})" style="margin-right:4px;"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-danger btn-sm" onclick="confirmDelete(${c.course_id}, '${escapeHtml(c.course_code).replace(/'/g, "\\'")}')"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `).join('');
        }

        function filterCourses() {
            const query = document.getElementById('courseSearch').value.toLowerCase().trim();
            const deptId = document.getElementById('deptFilter').value;
            let filtered = coursesData;
            if (deptId) filtered = filtered.filter(c => c.department_id == deptId);
            if (query) filtered = filtered.filter(c =>
                (c.course_code + ' ' + c.course_name + ' ' + (c.department_name || '') + ' ' + (c.lecturer_name || '')).toLowerCase().includes(query)
            );
            renderCourses(filtered);
        }

        function editCourse(id) {
            const course = coursesData.find(c => c.course_id == id);
            if (!course) return;
            document.getElementById('courseId').value = course.course_id;
            document.getElementById('courseCode').value = course.course_code;
            document.getElementById('courseName').value = course.course_name;
            document.getElementById('creditHours').value = course.credit_hours;
            document.getElementById('courseDepartment').value = course.department_id || '';
            document.getElementById('courseLecturer').value = course.lecturer_name || '';
            document.getElementById('formTitle').textContent = 'Edit Course';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Update Course';
            document.getElementById('cancelBtn').style.display = 'inline-flex';
            document.querySelector('.course-grid').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function clearForm() {
            document.getElementById('courseForm').reset();
            document.getElementById('courseId').value = '';
            document.getElementById('formTitle').textContent = 'Create Course';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-plus"></i> Create Course';
            document.getElementById('cancelBtn').style.display = 'none';
        }

        async function submitCourse(e) {
            e.preventDefault();
            const courseId = document.getElementById('courseId').value;
            const endpoint = courseId ? '../../api/course/update.php' : '../../api/course/create.php';
            const payload = {
                course_code: document.getElementById('courseCode').value.trim(),
                course_name: document.getElementById('courseName').value.trim(),
                credit_hours: Number(document.getElementById('creditHours').value),
                department_id: Number(document.getElementById('courseDepartment').value) || null,
                lecturer_name: document.getElementById('courseLecturer').value.trim()
            };
            if (courseId) payload.course_id = Number(courseId);

            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            const data = await apiCall(endpoint, { method: 'POST', body: JSON.stringify(payload) });
            btn.disabled = false;
            if (data.success) {
                showToast(courseId ? 'Course updated!' : 'Course created!', 'success');
                clearForm();
                fetchCourses();
            } else {
                showToast(data.message || 'Failed to save course', 'error');
                btn.innerHTML = courseId ? '<i class="fas fa-save"></i> Update Course' : '<i class="fas fa-plus"></i> Create Course';
            }
        }

        function confirmDelete(id, code) {
            deleteTarget = id;
            document.getElementById('deleteMessage').textContent = 'Delete course "' + code + '"? This will remove all associated schedules.';
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
            deleteTarget = null;
        }

        async function deleteCourse() {
            if (!deleteTarget) return;
            const data = await apiCall('../../api/course/delete.php', { method: 'POST', body: JSON.stringify({ course_id: deleteTarget }) });
            closeDeleteModal();
            if (data.success) {
                showToast('Course deleted!', 'success');
                fetchCourses();
            } else {
                showToast(data.message || 'Failed to delete course', 'error');
            }
        }

        function logout() {
            if (confirm('Logout?')) {
                fetch('../../api/auth/logout.php').then(() => window.location.href = '../index.php');
            }
        }

        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        }
    </script>
</body>
</html>
