<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'administrator') {
    header('Location: ../index.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

$courses = $db->query("SELECT course_id, course_name, course_code FROM courses ORDER BY course_name")->fetchAll();
$students = $db->query("SELECT s.student_id, s.full_name, s.department FROM students s JOIN users u ON s.user_id = u.user_id WHERE u.is_active = 1 ORDER BY s.full_name")->fetchAll();
$depts = $db->query("SELECT DISTINCT department FROM students WHERE department IS NOT NULL AND department != '' ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);

$user_name = $_SESSION['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management - Smart Campus Assistant</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .att-actions { display: flex; gap: 4px; }
        .att-actions .btn-icon { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; transition: all 0.15s ease; }
        .att-actions .btn-icon:hover { transform: translateY(-1px); box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
        .att-actions .btn-icon:disabled { opacity: 0.4; cursor: not-allowed; transform: none; box-shadow: none; }
        .btn-icon.present { background: #10b981; color: white; }
        .btn-icon.absent { background: #ef4444; color: white; }
        .btn-icon.late { background: #f59e0b; color: white; }
        .btn-icon.excused { background: #6366f1; color: white; }
        .batch-toolbar { display: flex; gap: 6px; flex-wrap: wrap; }
        .batch-toolbar .btn-batch { padding: 6px 14px; border: none; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; transition: all 0.15s ease; display: inline-flex; align-items: center; gap: 6px; }
        .batch-toolbar .btn-batch:hover { transform: translateY(-1px); box-shadow: 0 2px 8px rgba(0,0,0,0.12); }
        .batch-toolbar .btn-batch.present { background: #d1fae5; color: #065f46; }
        .batch-toolbar .btn-batch.absent { background: #fee2e2; color: #991b1b; }
        .batch-toolbar .btn-batch.late { background: #fef3c7; color: #92400e; }
        .batch-toolbar .btn-batch.excused { background: #e0e7ff; color: #3730a3; }
        .student-row { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: var(--light); border-radius: var(--radius); margin-bottom: 8px; border-left: 4px solid var(--gray); transition: all 0.15s ease; }
        .student-row:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .student-row.marked { border-left-color: var(--success); opacity: 0.75; }
        .student-avatar { width: 36px; height: 36px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 14px; flex-shrink: 0; }
        .date-header { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; border-radius: var(--radius) var(--radius) 0 0; }
        .date-header td { padding: 8px 12px; font-weight: 600; }
        .records-table { border-collapse: separate; border-spacing: 0; width: 100%; }
        .records-table th { background: var(--light); padding: 10px 12px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--gray); border-bottom: 2px solid var(--border); }
        .records-table td { padding: 10px 12px; border-bottom: 1px solid var(--light-2); vertical-align: middle; }
        .records-table tr:last-child td { border-bottom: none; }
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
            <a href="attendance.php" class="nav-item active"><i class="fas fa-clipboard-check"></i> Attendance</a>
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
                <h2><i class="fas fa-clipboard-check"></i> Attendance Management</h2>
            </div>
            <div class="user-profile">
                <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($user_name); ?></span>
            </div>
        </header>

        <div class="dashboard-container">
            <div class="content-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <!-- Mark Attendance -->
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="fas fa-pen"></i> Mark Attendance</h3>
                    </div>
                    <form id="attendanceForm" onsubmit="event.preventDefault(); markAttendance();">
                        <div class="form-group">
                            <label><i class="fas fa-building"></i> Department</label>
                            <select id="markDept" class="form-control" onchange="filterStudentsByDept('markStudent', this.value)">
                                <option value="">All Departments</option>
                                <?php foreach ($depts as $d): ?>
                                <option value="<?php echo htmlspecialchars($d); ?>"><?php echo htmlspecialchars($d); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-book"></i> Course</label>
                            <select id="markCourse" class="form-control" required>
                                <option value="">Select Course</option>
                                <?php foreach ($courses as $c): ?>
                                <option value="<?php echo $c['course_id']; ?>"><?php echo htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="form-group">
                                <label><i class="fas fa-user"></i> Student</label>
                                <select id="markStudent" class="form-control" required>
                                    <option value="">Select Student</option>
                                    <?php foreach ($students as $s): ?>
                                    <option value="<?php echo $s['student_id']; ?>" data-dept="<?php echo htmlspecialchars($s['department'] ?? ''); ?>"><?php echo htmlspecialchars($s['full_name'] . ($s['department'] ? ' (' . $s['department'] . ')' : '')); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-calendar"></i> Date</label>
                                <input type="date" id="markDate" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr auto; gap: 12px; align-items: end;">
                            <div class="form-group" style="margin: 0;">
                                <label><i class="fas fa-flag"></i> Status</label>
                                <select id="markStatus" class="form-control" required>
                                    <option value="present">Present</option>
                                    <option value="absent">Absent</option>
                                    <option value="late">Late</option>
                                    <option value="excused">Excused</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary" style="height: 44px;" id="markBtn">
                                <i class="fas fa-check"></i> Mark Attendance
                            </button>
                        </div>
                        <div id="markResult" style="display: none; margin-top: 12px;"></div>
                    </form>
                </div>

                <!-- Batch Mark -->
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="fas fa-users"></i> Batch Mark by Course</h3>
                    </div>
                    <form id="batchForm" onsubmit="event.preventDefault(); loadBatchStudents();">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="form-group">
                                <label><i class="fas fa-building"></i> Department</label>
                                <select id="batchDept" class="form-control" onchange="filterBatchByDept()">
                                    <option value="">All Departments</option>
                                    <?php foreach ($depts as $d): ?>
                                    <option value="<?php echo htmlspecialchars($d); ?>"><?php echo htmlspecialchars($d); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-book"></i> Course</label>
                                <select id="batchCourse" class="form-control" required>
                                    <option value="">Select Course</option>
                                    <?php foreach ($courses as $c): ?>
                                    <option value="<?php echo $c['course_id']; ?>"><?php echo htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-calendar"></i> Date</label>
                                <input type="date" id="batchDate" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <div style="margin-top: 12px;">
                            <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fas fa-search"></i> Load Students</button>
                        </div>
                    </form>
                    <div id="batchContainer" style="margin-top: 16px; display: none;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                            <h4 style="margin: 0; font-size: 14px; color: var(--gray);"><i class="fas fa-list"></i> Enrolled Students</h4>
                            <div class="batch-toolbar">
                                <button class="btn-batch present" onclick="markAllBatch('present')"><i class="fas fa-check"></i> All Present</button>
                                <button class="btn-batch absent" onclick="markAllBatch('absent')"><i class="fas fa-times"></i> All Absent</button>
                                <button class="btn-batch late" onclick="markAllBatch('late')"><i class="fas fa-clock"></i> All Late</button>
                                <button class="btn-batch excused" onclick="markAllBatch('excused')"><i class="fas fa-file-pen"></i> All Excused</button>
                            </div>
                        </div>
                        <div id="batchStudentsList"></div>
                    </div>
                </div>
            </div>

            <!-- Attendance Records -->
            <div class="section-card" style="margin-top: 24px;">
                <div class="section-header">
                    <h3><i class="fas fa-history"></i> Attendance Records</h3>
                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <input type="text" id="attendanceSearch" placeholder="Search name, course, or department..." style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; width: 200px; font-size: 13px;" oninput="loadAttendanceRecords()">
                        <button class="btn btn-primary" onclick="loadAttendanceRecords()"><i class="fas fa-sync"></i> Refresh</button>
                        <a href="../../api/attendance/export.php" class="btn btn-danger" style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;" download><i class="fas fa-file-pdf"></i> Export PDF</a>
                    </div>
                </div>
                <div style="overflow-x: auto;">
                    <table class="records-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Department</th>
                                <th>Course</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="attendanceTableBody">
                            <tr><td colspan="6" class="empty-state"><div class="loading-spinner"></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/admin.js?v=<?php echo filemtime('../assets/js/admin.js'); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', loadAttendanceRecords);

        function filterStudentsByDept(selectId, dept) {
            const sel = document.getElementById(selectId);
            if (!sel) return;
            for (let opt of sel.options) {
                if (!opt.value) continue;
                const optDept = opt.getAttribute('data-dept') || '';
                opt.style.display = (!dept || optDept === dept) ? '' : 'none';
            }
            if (sel.selectedIndex > 0 && sel.options[sel.selectedIndex].style.display === 'none') {
                sel.value = '';
            }
        }

        function filterBatchByDept() {
            const dept = document.getElementById('batchDept').value;
            document.querySelectorAll('#batchStudentsList .student-row').forEach(row => {
                const rowDept = row.getAttribute('data-dept') || '';
                row.style.display = (!dept || rowDept === dept) ? '' : 'none';
            });
        }

        const markInputs = ['markStudent', 'markCourse', 'markDate'];
        markInputs.forEach(id => document.getElementById(id)?.addEventListener('change', checkExistingAttendance));

        async function checkExistingAttendance() {
            const result = document.getElementById('markResult');
            const btn = document.getElementById('markBtn');
            const studentId = document.getElementById('markStudent').value;
            const courseId = document.getElementById('markCourse').value;
            const date = document.getElementById('markDate').value;
            if (!studentId || !courseId || !date) { result.style.display = 'none'; btn.disabled = false; btn.innerHTML = '<i class="fas fa-check"></i> Mark Attendance'; return; }
            try {
                const res = await fetch('../../api/attendance/get.php?' + new URLSearchParams({ student_id: studentId, course_id: courseId, date: date }));
                const data = await res.json();
                const records = data.success && data.data ? (data.data.records || data.data) : [];
                if (records.length > 0) {
                    const existing = records[0];
                    const cls = existing.status === 'present' ? 'success' : existing.status === 'late' ? 'warning' : existing.status === 'excused' ? 'info' : 'danger';
                    result.style.display = 'block';
                    result.innerHTML = '<div style="background:#fef3c7;color:#92400e;padding:12px;border-radius:8px;"><i class="fas fa-info-circle"></i> Already marked as <strong>' + capitalize(existing.status) + '</strong> — use the dropdown below to override</div>';
                    document.getElementById('markStatus').value = existing.status;
                } else {
                    result.style.display = 'none';
                }
            } catch (e) {}
        }

        async function markAttendance() {
            const btn = document.getElementById('markBtn');
            const result = document.getElementById('markResult');
            const studentId = parseInt(document.getElementById('markStudent').value);
            const courseId = parseInt(document.getElementById('markCourse').value);
            const date = document.getElementById('markDate').value;
            const status = document.getElementById('markStatus').value;
            btn.disabled = true;
            btn.innerHTML = '<div class="loading-spinner" style="width:20px;height:20px;border-width:2px;margin:0;"></div>';

            try {
                // Check if updating existing record
                const checkRes = await fetch('../../api/attendance/get.php?' + new URLSearchParams({ student_id: studentId, course_id: courseId, date: date }));
                const checkData = await checkRes.json();
                const existing = checkData.success && checkData.data ? (checkData.data.records || checkData.data) : [];
                let response;
                if (existing.length > 0) {
                    response = await fetch('../../api/attendance/update.php?id=' + existing[0].attendance_id, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ status: status })
                    });
                } else {
                    response = await fetch('../../api/attendance/mark.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ student_id: studentId, course_id: courseId, date: date, status: status })
                    });
                }
                const data = await response.json();
                result.style.display = 'block';
                if (data.success) {
                    const verb = existing.length > 0 ? 'updated' : 'marked';
                    result.innerHTML = '<div style="background: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px;"><i class="fas fa-check-circle"></i> Attendance ' + verb + ' as ' + capitalize(status) + '</div>';
                    checkExistingAttendance();
                    loadAttendanceRecords();
                } else {
                    result.innerHTML = '<div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px;"><i class="fas fa-exclamation-circle"></i> ' + (data.message || 'Failed to mark attendance') + '</div>';
                }
            } catch (err) {
                result.style.display = 'block';
                result.innerHTML = '<div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px;"><i class="fas fa-exclamation-triangle"></i> Network error</div>';
            }
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> Mark Attendance';
        }

        async function loadBatchStudents() {
            const courseId = document.getElementById('batchCourse').value;
            const date = document.getElementById('batchDate').value;
            const container = document.getElementById('batchContainer');
            if (!courseId) { showToast('Please select a course', 'error'); return; }
            container.style.display = 'block';
            document.getElementById('batchStudentsList').innerHTML = '<div class="empty-state"><div class="loading-spinner"></div></div>';

            try {
                const response = await fetch('../../api/course/enrollments.php');
                const data = await response.json();
                if (data.success && data.data) {
                    const enrolled = data.data.filter(e => e.course_id == courseId);
                    const existingResp = await fetch('../../api/attendance/get.php?' + new URLSearchParams({ course_id: courseId, date: date }));
                    const existingData = await existingResp.json();
                    const existingRecords = existingData.success && existingData.data ? (existingData.data.records || existingData.data) : [];
                    const existingMap = {};
                    existingRecords.forEach(r => { if (r.student_id) existingMap[r.student_id] = r.status; });

                    if (enrolled.length === 0) {
                        document.getElementById('batchStudentsList').innerHTML = '<div class="empty-state"><i class="fas fa-users"></i><p>No students enrolled in this course</p></div>';
                        return;
                    }

                    document.getElementById('batchStudentsList').innerHTML = enrolled.map(e => {
                        const existing = existingMap[e.student_id];
                        const borderColor = existing ? (existing === 'present' ? '#10b981' : existing === 'late' ? '#f59e0b' : existing === 'excused' ? '#6366f1' : '#ef4444') : '#d1d5db';
                        const deptAttr = e.student_department || e.department || '';
                        return `<div class="student-row" data-dept="${escapeHtml(deptAttr)}" style="border-left-color: ${borderColor}; ${existing ? 'opacity: 0.75;' : ''}">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div class="student-avatar">${(e.student_name || 'S')[0].toUpperCase()}</div>
                                <div>
                                    <strong>${escapeHtml(e.student_name)}</strong>
                                    <small style="color: var(--gray); display: block;">${escapeHtml(deptAttr)}</small>
                                </div>
                            </div>
                            <div class="att-actions">
                                ${existing
                                    ? `<span class="status-badge ${existing === 'present' ? 'success' : existing === 'late' ? 'warning' : existing === 'excused' ? 'info' : 'danger'}">${capitalize(existing)}</span>`
                                    : `<button class="btn-icon present" onclick="markSingle(${e.student_id},${courseId},'${date}','present',this)" title="Present"><i class="fas fa-check"></i></button>
                                       <button class="btn-icon absent" onclick="markSingle(${e.student_id},${courseId},'${date}','absent',this)" title="Absent"><i class="fas fa-times"></i></button>
                                       <button class="btn-icon late" onclick="markSingle(${e.student_id},${courseId},'${date}','late',this)" title="Late"><i class="fas fa-clock"></i></button>
                                       <button class="btn-icon excused" onclick="markSingle(${e.student_id},${courseId},'${date}','excused',this)" title="Excused"><i class="fas fa-file-pen"></i></button>`
                                }
                            </div>
                        </div>`;
                    }).join('');
                }
            } catch (err) {
                document.getElementById('batchStudentsList').innerHTML = '<div class="empty-state text-danger"><i class="fas fa-exclamation-triangle"></i><p>Failed to load enrolled students</p></div>';
            }
        }

        async function markSingle(studentId, courseId, date, status, btn) {
            btn.disabled = true;
            btn.innerHTML = '<div class="loading-spinner" style="width:14px;height:14px;border-width:2px;"></div>';
            try {
                const checkRes = await fetch('../../api/attendance/get.php?' + new URLSearchParams({ student_id: studentId, course_id: courseId, date: date }));
                const checkData = await checkRes.json();
                const existing = checkData.success && checkData.data ? (checkData.data.records || checkData.data) : [];
                let response;
                if (existing.length > 0) {
                    response = await fetch('../../api/attendance/update.php?id=' + existing[0].attendance_id, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ status: status })
                    });
                } else {
                    response = await fetch('../../api/attendance/mark.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ student_id: studentId, course_id: courseId, date: date, status: status })
                    });
                }
                const data = await response.json();
                if (data.success) {
                    const container = btn.parentElement;
                    const statusClass = status === 'present' ? 'success' : status === 'late' ? 'warning' : status === 'excused' ? 'info' : 'danger';
                    container.innerHTML = `<span class="status-badge ${statusClass}">${capitalize(status)}</span>`;
                    container.closest('.student-row').style.opacity = '0.75';
                    showToast('Marked as ' + capitalize(status), 'success');
                } else {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-' + (status === 'present' ? 'check' : status === 'absent' ? 'times' : status === 'late' ? 'clock' : 'file-pen') + '"></i>';
                    showToast(data.message || 'Failed to mark attendance', 'error');
                }
            } catch (err) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-' + (status === 'present' ? 'check' : status === 'absent' ? 'times' : status === 'late' ? 'clock' : 'file-pen') + '"></i>';
                showToast('Error: ' + err.message, 'error');
            }
        }

        async function markAllBatch(status) {
            const courseId = document.getElementById('batchCourse').value;
            const date = document.getElementById('batchDate').value;
            if (!courseId || !date) { showToast('Please select course and date', 'error'); return; }

            const unmarkedBtns = document.querySelectorAll('#batchStudentsList .btn-icon');
            if (unmarkedBtns.length === 0) { showToast('All students already marked for this date', 'info'); return; }
            if (!confirm('Mark ' + unmarkedBtns.length + ' students as ' + capitalize(status) + '?')) return;

            unmarkedBtns.forEach(b => b.disabled = true);
            try {
                const response = await fetch('../../api/course/enrollments.php');
                const enrollData = await response.json();
                if (!enrollData.success || !enrollData.data) {
                    showToast('Failed to load enrolled students', 'error');
                    unmarkedBtns.forEach(b => b.disabled = false);
                    return;
                }
                const enrolled = enrollData.data.filter(e => e.course_id == courseId);
                let marked = 0, failed = 0;
                for (const student of enrolled) {
                    const studentBtn = Array.from(unmarkedBtns).find(btn =>
                        btn.getAttribute('onclick') && btn.getAttribute('onclick').includes(student.student_id)
                    );
                    if (!studentBtn) continue;
                    try {
                        const checkRes = await fetch('../../api/attendance/get.php?' + new URLSearchParams({ student_id: student.student_id, course_id: courseId, date: date }));
                        const checkData = await checkRes.json();
                        const existing = checkData.success && checkData.data ? (checkData.data.records || checkData.data) : [];
                        let res;
                        if (existing.length > 0) {
                            res = await fetch('../../api/attendance/update.php?id=' + existing[0].attendance_id, {
                                method: 'PUT',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ status: status })
                            });
                        } else {
                            res = await fetch('../../api/attendance/mark.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ student_id: student.student_id, course_id: courseId, date: date, status: status })
                            });
                        }
                        const result = await res.json();
                        if (result.success) {
                            marked++;
                            const statusClass = status === 'present' ? 'success' : status === 'late' ? 'warning' : status === 'excused' ? 'info' : 'danger';
                            studentBtn.parentElement.innerHTML = `<span class="status-badge ${statusClass}">${capitalize(status)}</span>`;
                            studentBtn.closest('.student-row').style.opacity = '0.75';
                        } else { failed++; }
                    } catch (err) { failed++; }
                }
                const msg = 'Marked ' + marked + ' students as ' + capitalize(status) + (failed > 0 ? ' (' + failed + ' failed)' : '');
                showToast(msg, failed === 0 ? 'success' : 'warning');
            } catch (err) {
                showToast('Batch error: ' + err.message, 'error');
                unmarkedBtns.forEach(b => b.disabled = false);
            }
        }

        async function loadAttendanceRecords() {
            const tbody = document.getElementById('attendanceTableBody');
            tbody.innerHTML = '<tr><td colspan="6" class="empty-state"><div class="loading-spinner"></div></td></tr>';
            try {
                const search = document.getElementById('attendanceSearch').value.trim();
                let url = '../../api/attendance/get.php?all=true';
                if (search) url += '&search=' + encodeURIComponent(search);
                const response = await fetch(url);
                const data = await response.json();

                if (data.success) {
                    const records = data.data && data.data.records ? data.data.records : (Array.isArray(data.data) ? data.data : []);
                    if (records.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" class="empty-state"><i class="fas fa-clipboard"></i><p>No attendance records found</p></td></tr>';
                        return;
                    }
                    const grouped = {};
                    records.forEach(r => { const k = r.date; if (!grouped[k]) grouped[k] = []; grouped[k].push(r); });
                    const sortedDates = Object.keys(grouped).sort((a, b) => b.localeCompare(a));
                    let html = '';
                    sortedDates.forEach(dateKey => {
                        const displayDate = new Date(dateKey).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                        const present = grouped[dateKey].filter(r => r.status === 'present').length;
                        const absent = grouped[dateKey].filter(r => r.status === 'absent').length;
                        const late = grouped[dateKey].filter(r => r.status === 'late').length;
                        const excused = grouped[dateKey].filter(r => r.status === 'excused').length;
                        const total = grouped[dateKey].length;
                            html += `<tr class="date-header"><td colspan="6"><i class="fas fa-calendar-day"></i> ${displayDate} &mdash; <span style="font-weight:400;opacity:0.85;">${total} records</span></td></tr>`;
                        grouped[dateKey].forEach(r => {
                            const statusClass = r.status === 'present' ? 'success' : r.status === 'late' ? 'warning' : r.status === 'excused' ? 'info' : 'danger';
                            html += `<tr>
                                <td><strong>${escapeHtml(r.student_name || r.course_code || '')}</strong></td>
                                <td style="font-size: 12px;">${escapeHtml(r.student_department || '—')}</td>
                                <td>${escapeHtml(r.course_name || r.course_code || '')}</td>
                                <td style="color: var(--gray); font-size: 13px;">${new Date(r.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}</td>
                                <td><span class="status-badge ${statusClass}">${capitalize(r.status)}</span></td>
                                <td>
                                    <select onchange="updateAttendance(${r.attendance_id}, this.value)" style="padding: 4px 8px; border: 1px solid var(--border); border-radius: 6px; font-size: 12px; background: white;">
                                        <option value="present" ${r.status === 'present' ? 'selected' : ''}>Present</option>
                                        <option value="absent" ${r.status === 'absent' ? 'selected' : ''}>Absent</option>
                                        <option value="late" ${r.status === 'late' ? 'selected' : ''}>Late</option>
                                        <option value="excused" ${r.status === 'excused' ? 'selected' : ''}>Excused</option>
                                    </select>
                                </td>
                            </tr>`;
                        });
                    });
                    tbody.innerHTML = html;
                } else {
                    tbody.innerHTML = '<tr><td colspan="6" class="empty-state text-danger"><i class="fas fa-exclamation-triangle"></i><p>' + escapeHtml(data.message || 'Failed to load records') + '</p></td></tr>';
                }
            } catch (err) {
                tbody.innerHTML = '<tr><td colspan="6" class="empty-state text-danger"><i class="fas fa-exclamation-triangle"></i><p>Network error</p></td></tr>';
            }
        }

        async function updateAttendance(id, status) {
            try {
                const response = await fetch('../../api/attendance/update.php?id=' + id, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ status: status })
                });
                const data = await response.json();
                if (data.success) {
                    showToast('Updated to ' + capitalize(status), 'success');
                    loadAttendanceRecords();
                } else {
                    showToast(data.message || 'Failed to update attendance', 'error');
                }
            } catch (err) {
                showToast('Network error: ' + err.message, 'error');
            }
        }

    </script>
</body>
</html>
