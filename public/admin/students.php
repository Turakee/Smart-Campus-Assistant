<?php
require_once '../../config/config.php';

$allowedRoles = ['administrator', 'system_admin'];
if (!isset($_SESSION['user_id']) || !in_array(strtolower($_SESSION['role']), $allowedRoles)) {
    header('Location: ../index.php');
    exit;
}

$user_name = $_SESSION['name'];
$is_system_admin = strtolower($_SESSION['role']) === 'system_admin';

require_once '../../config/database.php';
$database = new Database();
$db = $database->getConnection();
$depts = [];
if ($db) {
    try {
        $stmt = $db->prepare("SELECT DISTINCT department FROM students WHERE department IS NOT NULL AND department != '' ORDER BY department");
        $stmt->execute();
        $depts = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $depts = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - Smart Campus Assistant</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .app-modal {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: 32px;
            max-width: 640px;
            width: 90%;
            transform: scale(0.95);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: var(--shadow-lg);
            position: relative;
        }
        .modal-overlay.active .app-modal {
            transform: scale(1);
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
            <a href="<?php echo $is_system_admin ? 'system-admin.php' : 'dashboard.php'; ?>" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
            <a href="students.php" class="nav-item active"><i class="fas fa-users"></i> Students</a>
            <?php if (!$is_system_admin): ?>
            <a href="courses.php" class="nav-item"><i class="fas fa-book"></i> Courses</a>
            <a href="manage-courses.php" class="nav-item"><i class="fas fa-user-graduate"></i> Enrollments</a>
            <a href="attendance.php" class="nav-item"><i class="fas fa-clipboard-check"></i> Attendance</a>
            <a href="schedule.php" class="nav-item"><i class="fas fa-calendar-alt"></i> Schedules</a>
            <a href="bookings.php" class="nav-item"><i class="fas fa-door-open"></i> Approve Bookings</a>
            <a href="resources.php" class="nav-item"><i class="fas fa-building"></i> Resources</a>
            <a href="analytics.php" class="nav-item"><i class="fas fa-chart-line"></i> AI Analytics</a>
            <?php endif; ?>
            <a href="profile.php" class="nav-item"><i class="fas fa-user-circle"></i> Profile</a>
            <?php if ($is_system_admin): ?>
            <a href="dashboard.php" class="nav-item"><i class="fas fa-shield-halved"></i> Admin Panel</a>
            <?php endif; ?>
            <a href="#" onclick="logout()" class="nav-item"><i class="fas fa-right-from-bracket"></i> Logout</a>
        </nav>
    </aside>

    <div class="sidebar-backdrop" onclick="toggleSidebar()"></div>

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button class="mobile-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <h2><i class="fas fa-users-gear"></i> Student Management</h2>
            </div>
            <div class="user-profile">
                <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($user_name); ?></span>
            </div>
        </header>

        <div class="dashboard-container">
            <!-- Stats -->
            <div class="stats-grid" id="statsGrid" style="display: none;">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-users"></i></div>
                    <div class="stat-details">
                        <h3 id="statTotalStudents">0</h3>
                        <p>Total Students</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-user-check"></i></div>
                    <div class="stat-details">
                        <h3 id="statActiveStudents">0</h3>
                        <p>Active</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="fas fa-building"></i></div>
                    <div class="stat-details">
                        <h3 id="statDepartments">0</h3>
                        <p>Departments</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-graduation-cap"></i></div>
                    <div class="stat-details">
                        <h3 id="statAvgLevel">-</h3>
                        <p>Avg Level</p>
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-list"></i> All Students</h3>
                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <span id="studentCount" style="font-size: 13px; color: var(--gray);"></span>
                        <select id="deptFilter" onchange="renderTable()" class="form-control" style="width: auto; min-width: 160px; padding: 8px 12px;">
                            <option value="">All Departments</option>
                            <?php foreach ($depts as $d): ?>
                            <option value="<?php echo htmlspecialchars($d); ?>"><?php echo htmlspecialchars($d); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" id="studentSearch" placeholder="Search name, email, username..." class="form-control" style="width: auto; min-width: 200px; padding: 8px 12px;" oninput="renderTable()">
                        <button type="button" class="btn btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Add Student</button>
                    </div>
                </div>

                <div id="alertBox" style="margin-bottom: 12px; display: none; padding: 10px 16px; border-radius: var(--radius); font-weight: 500;"></div>

                <div style="overflow-x: auto;">
                    <table class="data-table" id="studentsTable">
                        <thead>
                            <tr>
                                <th style="width: 40px;"></th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Level</th>
                                <th>Year</th>
                                <th>Status</th>
                                <th style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="studentsBody">
                            <tr><td colspan="9" class="empty-state"><div class="loading-spinner"></div></td></tr>
                        </tbody>
                    </table>
                </div>

                <div id="loadingMessage" style="display: none;"></div>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal-overlay" id="studentModal">
            <div class="app-modal">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <h3 style="font-size: 20px; font-weight: 700; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-user-graduate" style="color: var(--primary);"></i>
                        <span id="modalTitle">Add Student</span>
                    </h3>
                    <button onclick="closeModal()" style="border: none; background: none; font-size: 24px; cursor: pointer; color: var(--gray);">&times;</button>
                </div>
                <form id="modalForm" onsubmit="event.preventDefault(); saveStudent();">
                    <input type="hidden" id="modal_student_id">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-user"></i> Full Name</label>
                            <input type="text" id="modal_full_name" placeholder="e.g. John Doe" required class="form-control">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-at"></i> Username</label>
                            <input type="text" id="modal_username" placeholder="e.g. johndoe" required class="form-control">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" id="modal_email" placeholder="e.g. john@example.com" required class="form-control">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-lock"></i> Password</label>
                            <input type="password" id="modal_password" placeholder="Leave blank to keep" class="form-control">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-building"></i> Department</label>
                            <select id="modal_department" class="form-control">
                                <option value="">Select Department</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Information Technology">Information Technology</option>
                                <option value="Software Engineering">Software Engineering</option>
                                <option value="Data Science">Data Science</option>
                                <option value="Cyber Security">Cyber Security</option>
                                <option value="Artificial Intelligence">Artificial Intelligence</option>
                                <option value="Computer Engineering">Computer Engineering</option>
                                <option value="Information Systems">Information Systems</option>
                                <option value="Business Administration">Business Administration</option>
                                <option value="Engineering">Engineering</option>
                                <option value="Arts">Arts</option>
                                <option value="Science">Science</option>
                                <option value="General Studies">General Studies</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-level-up-alt"></i> Level</label>
                            <select id="modal_level" class="form-control">
                                <option value="">Select Level</option>
                                <option value="1">Level 1</option>
                                <option value="2">Level 2</option>
                                <option value="3">Level 3</option>
                                <option value="4">Level 4</option>
                                <option value="5">Level 5</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-calendar"></i> Enrollment Year</label>
                            <select id="modal_enrollment_year" class="form-control">
                                <option value="">Select Year</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0; display: flex; align-items: flex-end; padding-bottom: 4px;">
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin-bottom: 0;">
                                <input type="checkbox" id="modal_is_active" checked style="width: 18px; height: 18px; cursor: pointer;">
                                <span style="font-weight: 600; font-size: 14px;">Active Account</span>
                            </label>
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="modalSubmitBtn"><i class="fas fa-save"></i> Add Student</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        let students = [];
        let studentsById = {};

        const currentYear = new Date().getFullYear();
        const yearSelect = document.getElementById('modal_enrollment_year');
        for (let year = currentYear; year >= currentYear - 10; year--) {
            const option = document.createElement('option');
            option.value = year;
            option.textContent = year;
            yearSelect.appendChild(option);
        }

        function showAlert(message, type = 'success') {
            const alertBox = document.getElementById('alertBox');
            alertBox.style.display = 'block';
            alertBox.style.background = type === 'success' ? 'var(--success-light)' : 'var(--danger-light)';
            alertBox.style.color = type === 'success' ? '#065f46' : '#991b1b';
            alertBox.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
            setTimeout(() => { alertBox.style.display = 'none'; }, 3000);
        }

        async function fetchStudents() {
            const tbody = document.getElementById('studentsBody');
            tbody.innerHTML = '<tr><td colspan="9" class="empty-state"><div class="loading-spinner"></div></td></tr>';
            try {
                const response = await fetch('../../api/student/list.php');
                const result = await response.json();
                if (!result.success) throw new Error(result.message);
                students = result.data;
                studentsById = {};
                students.forEach((s) => { studentsById[s.student_id] = s; });
                renderTable();
                renderStats();
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="9" class="empty-state text-danger"><i class="fas fa-exclamation-triangle"></i><p>${escapeHtml(err.message)}</p></td></tr>`;
            }
        }

        function renderStats() {
            const grid = document.getElementById('statsGrid');
            grid.style.display = 'grid';
            const total = students.length;
            const active = students.filter(s => s.is_active == 1).length;
            const depts = new Set(students.map(s => s.department).filter(Boolean));
            const avg = total > 0 ? (students.reduce((sum, s) => sum + (parseInt(s.level) || 0), 0) / total).toFixed(1) : '-';
            document.getElementById('statTotalStudents').textContent = total;
            document.getElementById('statActiveStudents').textContent = active;
            document.getElementById('statDepartments').textContent = depts.size;
            document.getElementById('statAvgLevel').textContent = avg;
        }

        function renderTable() {
            const body = document.getElementById('studentsBody');
            const search = document.getElementById('studentSearch').value.trim().toLowerCase();
            const dept = document.getElementById('deptFilter').value;
            const filtered = students.filter(s => {
                if (dept && (s.department || '') !== dept) return false;
                const combined = (s.full_name + ' ' + s.username + ' ' + s.email + ' ' + (s.department || '')).toLowerCase();
                return combined.includes(search);
            });

            document.getElementById('studentCount').textContent = `Showing ${filtered.length} of ${students.length} students`;

            if (!filtered.length) {
                body.innerHTML = '<tr><td colspan="9" class="empty-state"><i class="fas fa-search"></i><p>No students match your filters</p></td></tr>';
                return;
            }

            body.innerHTML = filtered.map(student => {
                const initial = (student.full_name || 'S')[0].toUpperCase();
                const activeBadge = student.is_active == 1
                    ? '<span class="status-badge present">Active</span>'
                    : '<span class="status-badge absent">Inactive</span>';
                return `<tr>
                    <td><div style="width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--primary-light)); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">${initial}</div></td>
                    <td><strong>${escapeHtml(student.full_name)}</strong></td>
                    <td>${escapeHtml(student.username)}</td>
                    <td style="font-size: 13px;">${escapeHtml(student.email)}</td>
                    <td>${student.department ? '<span class="status-badge info">' + escapeHtml(student.department) + '</span>' : '<span style="color: var(--gray-light);">—</span>'}</td>
                    <td><strong>Level ${student.level || '—'}</strong></td>
                    <td>${student.enrollment_year || '—'}</td>
                    <td>${activeBadge}</td>
                    <td>
                        <div style="display: flex; gap: 6px;">
                            <button class="btn btn-primary" style="padding: 6px 10px; font-size: 12px;" title="Edit" onclick="openModal(${student.student_id})"><i class="fas fa-pen"></i></button>
                            <button class="btn" style="padding: 6px 10px; font-size: 12px; background: ${student.is_active == 1 ? 'var(--warning)' : 'var(--success)'}; color: white;" title="${student.is_active == 1 ? 'Deactivate' : 'Activate'}" onclick="toggleActive(${student.student_id}, ${student.is_active == 1 ? 0 : 1})"><i class="fas fa-${student.is_active == 1 ? 'ban' : 'check'}"></i></button>
                            <button class="btn" style="padding: 6px 10px; font-size: 12px; background: var(--danger); color: white;" title="Delete" onclick="deleteStudent(${student.student_id})"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>`;
            }).join('');
        }

        function escapeHtml(value) {
            if (typeof value !== 'string') return value;
            return value.replace(/[&<>"]/g, function (char) {
                return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[char];
            });
        }

        function openModal(studentId = null) {
            const modal = document.getElementById('studentModal');
            const form = document.getElementById('modalForm');
            const title = document.getElementById('modalTitle');
            const submitBtn = document.getElementById('modalSubmitBtn');
            const passwordField = document.getElementById('modal_password');

            form.reset();

            if (studentId && studentsById[studentId]) {
                const student = studentsById[studentId];
                title.textContent = 'Edit Student';
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Update Student';
                passwordField.placeholder = 'Leave blank to keep';
                passwordField.required = false;
                passwordField.closest('.form-group').style.display = 'none';

                document.getElementById('modal_student_id').value = student.student_id;
                document.getElementById('modal_full_name').value = student.full_name;
                document.getElementById('modal_username').value = student.username;
                document.getElementById('modal_email').value = student.email;
                document.getElementById('modal_department').value = student.department || '';
                document.getElementById('modal_level').value = student.level || '';
                document.getElementById('modal_enrollment_year').value = student.enrollment_year || '';
                document.getElementById('modal_is_active').checked = student.is_active == 1;
            } else {
                title.textContent = 'Add Student';
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Add Student';
                passwordField.placeholder = 'Enter password';
                passwordField.required = true;
                passwordField.closest('.form-group').style.display = 'block';

                document.getElementById('modal_student_id').value = '';
                document.getElementById('modal_is_active').checked = true;
            }

            modal.classList.add('active');
        }

        function closeModal() {
            document.getElementById('studentModal').classList.remove('active');
        }

        async function saveStudent() {
            const student_id = document.getElementById('modal_student_id').value;
            const isEdit = student_id !== '';

            const basePayload = {
                full_name: document.getElementById('modal_full_name').value.trim(),
                username: document.getElementById('modal_username').value.trim(),
                email: document.getElementById('modal_email').value.trim(),
                department: document.getElementById('modal_department').value.trim(),
                level: document.getElementById('modal_level').value ? parseInt(document.getElementById('modal_level').value) : null,
                enrollment_year: document.getElementById('modal_enrollment_year').value ? parseInt(document.getElementById('modal_enrollment_year').value) : null,
                is_active: document.getElementById('modal_is_active').checked ? 1 : 0
            };

            try {
                let response, result;

                if (isEdit) {
                    const payload = { ...basePayload, student_id: parseInt(student_id) };
                    response = await fetch('../../api/student/update.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                } else {
                    const password = document.getElementById('modal_password').value;
                    if (!password) {
                        showAlert('Password is required for new students', 'error');
                        return;
                    }
                    const payload = { ...basePayload, password };
                    response = await fetch('../../api/student/create.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                }

                result = await response.json();
                if (!result.success) throw new Error(result.message);

                showAlert(isEdit ? 'Student updated successfully' : 'Student added successfully');
                closeModal();
                await fetchStudents();
            } catch (err) {
                showAlert('Error saving student: ' + err.message, 'error');
            }
        }

        async function toggleActive(student_id, targetState) {
            if (!confirm(`Change status for student #${student_id}?`)) return;
            try {
                const response = await fetch('../../api/student/update.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ student_id, is_active: targetState })
                });
                const result = await response.json();
                if (!result.success) throw new Error(result.message);
                showAlert('Status updated successfully');
                await fetchStudents();
            } catch (err) {
                showAlert('Error updating status: ' + err.message, 'error');
            }
        }

        async function deleteStudent(student_id) {
            if (!confirm('Delete student #' + student_id + '? This cannot be undone.')) return;
            try {
                const response = await fetch('../../api/student/delete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ student_id })
                });
                const result = await response.json();
                if (!result.success) throw new Error(result.message);
                showAlert('Student deleted successfully');
                await fetchStudents();
            } catch (err) {
                showAlert('Error deleting student: ' + err.message, 'error');
            }
        }

        function logout() {
            if (confirm('Logout?')) {
                fetch('../../api/auth/logout.php').then(() => window.location.href = '../index.php');
            }
        }

        fetchStudents();

    </script>
</body>
</html>
