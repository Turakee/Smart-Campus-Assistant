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
    <title>Schedule Management - Smart Campus Assistant</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .sch-page { max-width: 1400px; margin: 0 auto; }
        .layout-grid { display: grid; grid-template-columns: 380px 1fr; gap: 24px; align-items: start; }
        @media (max-width: 960px) { .layout-grid { grid-template-columns: 1fr; } }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .toolbar { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .toolbar .search-box { position: relative; flex: 1; min-width: 160px; }
        .toolbar .search-box i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: var(--gray-light); font-size: 14px; }
        .toolbar .search-box input { width: 100%; padding: 9px 12px 9px 36px; border: 1.5px solid var(--border); border-radius: var(--radius); font-size: 13px; background: white; transition: var(--transition); }
        .toolbar .search-box input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79,70,229,0.08); outline: none; }
        .toolbar select { padding: 9px 12px; border: 1.5px solid var(--border); border-radius: var(--radius); font-size: 13px; background: white; cursor: pointer; }
        .dept-group { border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 20px; overflow: hidden; transition: var(--transition); }
        .dept-group:hover { box-shadow: var(--shadow-sm); }
        .dept-group-header { display: flex; align-items: center; gap: 12px; padding: 14px 20px; background: linear-gradient(135deg, var(--light), #f8fafc); font-weight: 600; font-size: 14px; border-bottom: 1px solid var(--border); color: var(--dark); }
        .dept-group-header .count { margin-left: auto; font-size: 12px; font-weight: 500; color: var(--gray); background: white; padding: 3px 12px; border-radius: 20px; border: 1px solid var(--border); }
        .schedule-form label { display: block; font-size: 12px; font-weight: 600; color: var(--gray); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
        .schedule-form label i { width: 14px; margin-right: 4px; color: var(--primary); }
        .action-btns { display: flex; gap: 6px; }
        .action-btns .btn-icon { width: 32px; height: 32px; border-radius: 8px; border: none; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; cursor: pointer; transition: var(--transition); }
        .btn-icon.edit { background: #eef2ff; color: var(--primary); }
        .btn-icon.edit:hover { background: var(--primary); color: white; transform: translateY(-1px); }
        .btn-icon.delete { background: #fef2f2; color: var(--danger); }
        .btn-icon.delete:hover { background: var(--danger); color: white; transform: translateY(-1px); }
        .time-cell { font-family: 'Inter', monospace; font-weight: 600; font-size: 13px; color: var(--dark); white-space: nowrap; letter-spacing: 0.3px; }
        .skel { background: linear-gradient(90deg, var(--light) 25%, #e8ecf1 50%, var(--light) 75%); background-size: 200% 100%; animation: shimmer 1.4s infinite; border-radius: 8px; }
        @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        .skel-row { display: flex; gap: 16px; padding: 16px 20px; align-items: center; border-bottom: 1px solid var(--light); }
        .skel-row .skel { height: 16px; }
        .skel-row .skel:nth-child(1) { width: 40px; }
        .skel-row .skel:nth-child(2) { width: 160px; }
        .skel-row .skel:nth-child(3) { width: 80px; }
        .skel-row .skel:nth-child(4) { width: 100px; }
        .skel-row .skel:nth-child(5) { width: 60px; }
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
            <a href="schedule.php" class="nav-item active"><i class="fas fa-calendar-alt"></i> Schedules</a>
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
                <h2><i class="fas fa-calendar-alt" style="color:var(--primary);"></i> Schedule Management</h2>
            </div>
            <div class="user-profile">
                <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($user_name); ?></span>
            </div>
        </header>

        <div class="dashboard-container sch-page">
            <!-- Stats -->
            <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-calendar-week"></i></div>
                    <div class="stat-details">
                        <h3 id="totalSchedules">0</h3>
                        <p>Total Schedules</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon teal"><i class="fas fa-building"></i></div>
                    <div class="stat-details">
                        <h3 id="totalDeptSche">0</h3>
                        <p>Departments</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="fas fa-chalkboard"></i></div>
                    <div class="stat-details">
                        <h3 id="totalCourses">0</h3>
                        <p>Courses Scheduled</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-clock"></i></div>
                    <div class="stat-details">
                        <h3 id="totalHours">0</h3>
                        <p>Total Hours/Week</p>
                    </div>
                </div>
            </div>

            <!-- Two-column layout -->
            <div class="layout-grid">
                <!-- Form Panel -->
                <div class="section-card">
                    <div class="section-header" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark)); margin: -24px -28px 20px; padding: 18px 28px; border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
                        <h3 style="color: white; border: none;"><i class="fas fa-plus-circle" style="color: rgba(255,255,255,0.8);"></i> <span id="formTitle">Add Schedule</span></h3>
                    </div>
                    <form id="scheduleForm" class="schedule-form">
                        <input type="hidden" id="scheduleId">
                        <div class="form-group">
                            <label><i class="fas fa-building"></i> Department</label>
                            <select id="formDept" class="form-control" onchange="filterCoursesByDept()" required>
                                <option value="">Select Department</option>
                                <?php foreach ($depts as $d): ?>
                                <option value="<?php echo $d['department_id']; ?>"><?php echo htmlspecialchars($d['department_name']); ?> (<?php echo htmlspecialchars($d['department_code']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-book"></i> Course</label>
                            <select id="formCourse" class="form-control" required>
                                <option value="">Select Department First</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar-day"></i> Day of Week</label>
                            <select id="formDay" class="form-control" required>
                                <option value="">Select Day</option>
                                <option>Monday</option>
                                <option>Tuesday</option>
                                <option>Wednesday</option>
                                <option>Thursday</option>
                                <option>Friday</option>
                                <option>Saturday</option>
                                <option>Sunday</option>
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-play"></i> Start</label>
                                <input type="time" id="formStart" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-stop"></i> End</label>
                                <input type="time" id="formEnd" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-door-open"></i> Room</label>
                            <input type="text" id="formRoom" class="form-control" placeholder="e.g. LH-101, Lab-3">
                        </div>
                        <div style="display: flex; gap: 10px; margin-top: 6px;">
                            <button type="submit" class="btn btn-primary" id="submitBtn" style="flex: 1;"><i class="fas fa-plus"></i> Create Schedule</button>
                            <button type="button" class="btn btn-secondary" id="cancelBtn" style="display:none;" onclick="clearForm()"><i class="fas fa-times"></i> Cancel</button>
                        </div>
                    </form>
                </div>

                <!-- Schedule List Panel -->
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="fas fa-list"></i> All Schedules</h3>
                    </div>
                    <div class="toolbar" style="margin-bottom: 18px;">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="scheduleSearch" placeholder="Search by course, day, room..." oninput="renderSchedules()">
                        </div>
                        <select id="deptFilter" onchange="filterByDept()">
                            <option value="">All Departments</option>
                            <?php foreach ($depts as $d): ?>
                            <option value="<?php echo $d['department_id']; ?>"><?php echo htmlspecialchars($d['department_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="scheduleContainer"></div>
                </div>
            </div>
        </div>
    </main>

    <!-- Delete Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal">
            <div style="width:56px;height:56px;border-radius:16px;background:#fef2f2;display:flex;align-items:center;justify-content:center;margin-bottom:16px;">
                <i class="fas fa-trash-alt" style="font-size:22px;color:var(--danger);"></i>
            </div>
            <h3 style="font-size: 18px; font-weight: 700; margin: 0 0 8px;">Delete Schedule</h3>
            <p id="deleteMessage" style="color: var(--gray); font-size: 14px; margin: 0 0 24px;">Are you sure you want to delete this schedule? This action cannot be undone.</p>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn btn-danger" onclick="confirmDelete()"><i class="fas fa-trash"></i> Delete</button>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin.js?v=<?php echo filemtime('../assets/js/admin.js'); ?>"></script>
    <script>
        let schedulesData = [];
        let allCourses = [];
        deleteTarget = null;

        const DAY_COLORS = {
            Monday:   { bg: '#eef2ff', text: '#4338ca', icon: '🌤' },
            Tuesday:  { bg: '#d1fae5', text: '#065f46', icon: '🌥' },
            Wednesday:{ bg: '#fef3c7', text: '#92400e', icon: '☀' },
            Thursday: { bg: '#fee2e2', text: '#991b1b', icon: '🌦' },
            Friday:   { bg: '#ede9fe', text: '#6d28d9', icon: '🌅' }
        };

        document.addEventListener('DOMContentLoaded', () => {
            loadCourses();
            fetchSchedules();
            document.getElementById('scheduleForm').addEventListener('submit', saveSchedule);
            // Auto-poll every 30s for schedule updates
            setInterval(fetchSchedules, 30000);
        });

        async function loadCourses() {
            try {
                const res = await fetch('../../api/course/list.php');
                const data = await res.json();
                if (data.success) allCourses = data.data;
            } catch (e) { console.error('Failed to load courses', e); }
        }

        function filterCoursesByDept() {
            const deptId = document.getElementById('formDept').value;
            const select = document.getElementById('formCourse');
            if (!deptId) {
                select.innerHTML = '<option value="">Select Department First</option>';
                return;
            }
            const filtered = allCourses.filter(c => c.department_id == deptId);
            select.innerHTML = '<option value="">Select Course</option>' +
                filtered.map(c => `<option value="${c.course_id}">${escapeHtml(c.course_name)} (${escapeHtml(c.course_code)})</option>`).join('');
        }

        function showSkeleton() {
            const container = document.getElementById('scheduleContainer');
            let html = '';
            for (let g = 0; g < 2; g++) {
                html += '<div style="margin-bottom:16px;"><div class="skel" style="height:20px;width:200px;margin-bottom:10px;"></div>';
                for (let r = 0; r < 3; r++) {
                    html += '<div class="skel-row"><div class="skel"></div><div class="skel"></div><div class="skel"></div><div class="skel"></div><div class="skel"></div></div>';
                }
                html += '</div>';
            }
            container.innerHTML = html;
        }

        let schedulePollTimer = null;

        async function fetchSchedules() {
            showSkeleton();
            try {
                const dept = document.getElementById('deptFilter').value;
                const url = dept ? `../../api/schedule/get.php?department_id=${dept}` : '../../api/schedule/get.php';
                const res = await fetch(url);
                const data = await res.json();
                if (data.success) {
                    schedulesData = data.data;
                    updateStats();
                    renderSchedules();
                } else {
                    document.getElementById('scheduleContainer').innerHTML =
                        '<div class="empty-state"><i class="fas fa-exclamation-triangle" style="color:var(--danger);"></i><p>Failed to Load</p><small>' + escapeHtml(data.message) + '</small></div>';
                }
            } catch (e) {
                document.getElementById('scheduleContainer').innerHTML =
                    '<div class="empty-state"><i class="fas fa-wifi-slash" style="color:var(--danger);"></i><p>Network Error</p><small>Could not reach the server. Please try again.</small></div>';
            }
        }

        function updateStats() {
            document.getElementById('totalSchedules').textContent = schedulesData.length;
            const deptSet = new Set(schedulesData.map(s => s.department_id).filter(Boolean));
            document.getElementById('totalDeptSche').textContent = deptSet.size;
            const courseSet = new Set(schedulesData.map(s => s.course_id));
            document.getElementById('totalCourses').textContent = courseSet.size;
            const totalHours = schedulesData.reduce((sum, s) => {
                const [sh, sm] = (s.start_time || '0:0').split(':').map(Number);
                const [eh, em] = (s.end_time || '0:0').split(':').map(Number);
                return sum + Math.max(0, (eh * 60 + em) - (sh * 60 + sm));
            }, 0);
            document.getElementById('totalHours').textContent = (totalHours / 60).toFixed(1);
        }

        function formatTime12(t) {
            if (!t) return '—';
            const [h, m] = t.split(':').map(Number);
            const ampm = h >= 12 ? 'PM' : 'AM';
            const h12 = h % 12 || 12;
            return `${h12}:${String(m).padStart(2, '0')} ${ampm}`;
        }

        function renderSchedules() {
            const container = document.getElementById('scheduleContainer');
            const query = document.getElementById('scheduleSearch').value.toLowerCase().trim();

            let filtered = schedulesData;
            if (query) {
                filtered = filtered.filter(s =>
                    (s.course_name + ' ' + s.course_code + ' ' + s.day_of_week + ' ' + s.start_time + ' ' + s.end_time + ' ' + (s.room_number || '') + ' ' + (s.department_name || '')).toLowerCase().includes(query)
                );
            }

            if (!filtered.length) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-calendar-xmark"></i>
                        <p>No Schedules Found</p>
                        <small>${query ? 'Try a different search term.' : 'Start by adding a new schedule using the form.'}</small>
                    </div>`;
                return;
            }

            const grouped = {};
            filtered.forEach(s => {
                const key = s.department_id || '0';
                if (!grouped[key]) grouped[key] = { name: s.department_name || 'No Department', items: [] };
                grouped[key].items.push(s);
            });

            const deptOrder = Object.keys(grouped).sort((a, b) => (grouped[a].name || '').localeCompare(grouped[b].name || ''));
            let html = '<div class="fade-in">';

            deptOrder.forEach(key => {
                const group = grouped[key];
                const items = group.items.sort((a, b) => {
                    const days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
                    return (days.indexOf(a.day_of_week) - days.indexOf(b.day_of_week)) || (a.start_time || '').localeCompare(b.start_time || '');
                });

                html += `
                    <div class="dept-group">
                        <div class="dept-group-header">
                            <i class="fas fa-building" style="color:var(--primary);"></i>
                            ${escapeHtml(group.name)}
                            <span class="count">${group.items.length} slot${group.items.length > 1 ? 's' : ''}</span>
                        </div>
                        <div class="dept-group-body" style="overflow-x: auto;">
                            <table class="data-table">
                                <thead><tr>
                                    <th>Course</th>
                                    <th>Day</th>
                                    <th>Time</th>
                                    <th>Room</th>
                                    <th>Actions</th>
                                </tr></thead>
                                <tbody>
                                    ${items.map((s, i) => {
                                        const dc = DAY_COLORS[s.day_of_week] || { bg: '#f1f5f9', text: '#64748b' };
                                        return `<tr>
                                            <td>
                                                <strong>${escapeHtml(s.course_name)}</strong>
                                                <br><small style="color:var(--gray-light);font-size:11px;">${escapeHtml(s.course_code)}</small>
                                            </td>
                                            <td><span class="day-pill" style="background:${dc.bg};color:${dc.text};">${escapeHtml(s.day_of_week)}</span></td>
                                            <td><span class="time-cell">${formatTime12(s.start_time)} — ${formatTime12(s.end_time)}</span></td>
                                            <td><i class="fas fa-door-open" style="color:var(--gray-light);font-size:12px;margin-right:4px;"></i> ${escapeHtml(s.room_number || '—')}</td>
                                            <td>
                                                <div class="action-btns">
                                                    <button class="btn-icon edit" onclick="editSchedule(${s.schedule_id})" title="Edit"><i class="fas fa-pen"></i></button>
                                                    <button class="btn-icon delete" onclick="openDeleteModal(${s.schedule_id}, '${escapeHtml(s.course_code)}')" title="Delete"><i class="fas fa-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>`;
                                    }).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>`;
            });

            html += '</div>';
            container.innerHTML = html;
        }

        function filterByDept() { fetchSchedules(); }

        function editSchedule(id) {
            const s = schedulesData.find(x => x.schedule_id == id);
            if (!s) return;
            document.getElementById('scheduleId').value = s.schedule_id;
            document.getElementById('formDept').value = s.department_id || '';
            filterCoursesByDept();
            document.getElementById('formCourse').value = s.course_id;
            document.getElementById('formDay').value = s.day_of_week;
            document.getElementById('formStart').value = s.start_time;
            document.getElementById('formEnd').value = s.end_time;
            document.getElementById('formRoom').value = s.room_number || '';
            document.getElementById('formTitle').textContent = 'Edit Schedule';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Update Schedule';
            document.getElementById('cancelBtn').style.display = 'inline-flex';
            document.querySelector('.layout-grid').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function clearForm() {
            document.getElementById('scheduleForm').reset();
            document.getElementById('scheduleId').value = '';
            document.getElementById('formCourse').innerHTML = '<option value="">Select Department First</option>';
            document.getElementById('formTitle').textContent = 'Add Schedule';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-plus"></i> Create Schedule';
            document.getElementById('cancelBtn').style.display = 'none';
        }

        async function saveSchedule(e) {
            e.preventDefault();
            const scheduleId = document.getElementById('scheduleId').value;
            const endpoint = scheduleId ? '../../api/schedule/update.php' : '../../api/schedule/create.php';
            const payload = {
                course_id: Number(document.getElementById('formCourse').value),
                day_of_week: document.getElementById('formDay').value,
                start_time: document.getElementById('formStart').value,
                end_time: document.getElementById('formEnd').value,
                room_number: document.getElementById('formRoom').value.trim()
            };
            if (!payload.course_id || !payload.day_of_week || !payload.start_time || !payload.end_time) {
                showToast('Please fill all required fields', 'error');
                return;
            }
            if (scheduleId) payload.schedule_id = Number(scheduleId);

            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            const data = await apiCall(endpoint, { method: 'POST', body: JSON.stringify(payload) });
            btn.disabled = false;
            if (data.success) {
                showToast(scheduleId ? 'Schedule updated successfully' : 'Schedule created successfully', 'success');
                clearForm();
                fetchSchedules();
            } else {
                showToast(data.message || 'Failed to save schedule', 'error');
                btn.innerHTML = scheduleId ? '<i class="fas fa-save"></i> Update Schedule' : '<i class="fas fa-plus"></i> Create Schedule';
            }
        }

        function openDeleteModal(id, code) {
            deleteTarget = id;
            document.getElementById('deleteMessage').textContent = `Remove schedule for ${code}? All associated data will be lost.`;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
            deleteTarget = null;
        }

        async function confirmDelete() {
            console.log('confirmDelete called, deleteTarget =', deleteTarget);
            if (!deleteTarget) {
                console.warn('deleteTarget is null/undefined, returning early');
                closeDeleteModal();
                return;
            }
            try {
                const data = await apiCall('../../api/schedule/delete.php', { method: 'POST', body: JSON.stringify({ schedule_id: deleteTarget }) });
                console.log('delete API response:', data);
                closeDeleteModal();
                if (data.success) {
                    showToast('Schedule deleted', 'success');
                    fetchSchedules();
                } else {
                    showToast(data.message || 'Failed to delete schedule', 'error');
                }
            } catch (err) {
                console.error('Delete error:', err);
                closeDeleteModal();
                showToast('Delete failed: ' + err.message, 'error');
            }
        }

        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.sidebar-backdrop').classList.toggle('active');
        }
    </script>
</body>
</html>
