// CampusEase - Admin Dashboard JavaScript
// Consolidated & Enhanced

let allAnalytics = [];
let enrollmentCache = [];
let deleteTarget = null;

document.addEventListener('DOMContentLoaded', () => {
    if (typeof loadAdminStats === 'function') loadAdminStats();
    if (typeof loadPendingBookings === 'function') loadPendingBookings();
    if (typeof loadAnalytics === 'function') loadAnalytics();
});

// API Helper
async function apiCall(endpoint, options = {}) {
    try {
        const res = await fetch(endpoint, {
            headers: { 'Content-Type': 'application/json', ...options.headers },
            ...options
        });
        return await res.json();
    } catch (err) {
        console.error('API Error:', err);
        return { success: false, message: 'Network error' };
    }
}

// Utilities
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function formatTime(time) {
    if (!time) return '-';
    const [hours, minutes] = time.split(':');
    const h = parseInt(hours);
    const ampm = h >= 12 ? 'PM' : 'AM';
    const hour12 = h % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
}

function showToast(message, type = 'success') {
    const container = document.querySelector('.toast-container') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${escapeHtml(message)}`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
    return container;
}

// Admin Stats
async function loadAdminStats() {
    const statsEl = document.getElementById('statStudents');
    if (!statsEl) return;
    try {
        const data = await apiCall('../../api/admin/stats.php');
        if (data.success) {
            document.getElementById('statStudents').textContent = data.data.totalStudents || 0;
            document.getElementById('statPendingBookings').textContent = data.data.pendingBookings || 0;
            document.getElementById('statAtRisk').textContent = data.data.atRiskStudents || 0;
            document.getElementById('statAvgAttendance').textContent = (data.data.avgAttendance || 0) + '%';
        }
    } catch (err) { console.error('Stats error:', err); }
}

// Bookings
async function loadPendingBookings() {
    const tbody = document.getElementById('bookingTableBody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="5" class="empty-state"><div class="loading-spinner"></div></td></tr>';
    try {
        const data = await apiCall('../../api/booking/pending.php');
        if (data.success && data.data && data.data.length > 0) {
            tbody.innerHTML = data.data.map(b => `
                <tr>
                    <td data-label="Student">${escapeHtml(b.student_name)}</td>
                    <td data-label="Resource">${escapeHtml(b.resource_name)}</td>
                    <td data-label="Date">${b.booking_date}</td>
                    <td data-label="Status"><span class="status-badge pending">Pending</span></td>
                    <td data-label="Action">
                        <button class="btn btn-success btn-sm" onclick="approveBooking(${b.booking_id})"><i class="fas fa-check"></i></button>
                        <button class="btn btn-danger btn-sm" onclick="rejectBooking(${b.booking_id})"><i class="fas fa-times"></i></button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="5" class="empty-state"><i class="fas fa-check-circle" style="color: var(--success);"></i><h4>All caught up!</h4><p>No pending bookings</p></td></tr>';
        }
    } catch (err) {
        tbody.innerHTML = '<tr><td colspan="5" class="empty-state text-danger"><i class="fas fa-exclamation-triangle"></i> Failed</td></tr>';
    }
}

async function approveBooking(id) {
    if (!confirm('Approve this booking?')) return;
    await updateBookingStatus(id, 'approved');
}

async function rejectBooking(id) {
    if (!confirm('Reject this booking?')) return;
    await updateBookingStatus(id, 'rejected');
}

async function updateBookingStatus(id, status) {
    const data = await apiCall('../../api/booking/update.php', {
        method: 'POST',
        body: JSON.stringify({ booking_id: id, status: status })
    });
    if (data.success) {
        showToast(`Booking ${status}!`, 'success');
        loadPendingBookings();
        loadAdminStats();
    } else {
        showToast(data.message || 'Failed to update booking status', 'error');
    }
}

// Notification Forms
function initNotificationForms() {
    const eventForm = document.getElementById('eventForm');
    const announcementForm = document.getElementById('announcementForm');
    if (eventForm) {
        eventForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = eventForm.querySelector('button');
            btn.disabled = true;
            const data = await apiCall('../../api/admin/create-event.php', {
                method: 'POST',
                body: JSON.stringify({
                    title: document.getElementById('eventTitle').value,
                    event_date: document.getElementById('eventDate').value,
                    message: document.getElementById('eventMessage').value
                })
            });
            btn.disabled = false;
            if (data.success) {
                showToast('Event notification sent!', 'success');
                eventForm.reset();
            } else {
                showToast(data.message || 'Failed to send event notification', 'error');
            }
        });
    }
    if (announcementForm) {
        announcementForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = announcementForm.querySelector('button');
            btn.disabled = true;
            const data = await apiCall('../../api/admin/create-announcement.php', {
                method: 'POST',
                body: JSON.stringify({
                    title: document.getElementById('announcementTitle').value,
                    message: document.getElementById('announcementMessage').value
                })
            });
            btn.disabled = false;
            if (data.success) {
                showToast('Announcement sent!', 'success');
                announcementForm.reset();
            } else {
                showToast(data.message || 'Failed to send announcement', 'error');
            }
        });
    }
}

// Student Management
async function fetchStudents() {
    const loading = document.getElementById('loadingMessage');
    const studentSelect = document.getElementById('enrollStudentId');
    const totalStudentsEl = document.getElementById('totalStudents');
    
    if(loading) {
        loading.textContent = 'Loading students...';
        loading.style.display = 'block';
    }
    
    try {
        const result = await apiCall('../../api/student/list.php');
        
        if (!result.success) {
            if(loading) {
                loading.textContent = 'Error: ' + (result.message || 'Unknown error');
                loading.style.color = 'var(--danger)';
            }
            if(studentSelect) {
                studentSelect.innerHTML = '<option value="">Error loading students</option>';
            }
            return;
        }
        
        students = result.data || [];
        studentsById = {};
        students.forEach((s) => { studentsById[s.student_id] = s; });
        
        if(totalStudentsEl) {
            totalStudentsEl.textContent = students.length;
        }
        
        if(studentSelect) {
            studentSelect.innerHTML = '<option value="">Select a student</option>';
            if (students.length === 0) {
                studentSelect.innerHTML = '<option value="">No students available</option>';
            } else {
                students.forEach(student => {
                    if(student.is_active == 1) {
                        const opt = document.createElement('option');
                        opt.value = student.student_id;
                        opt.textContent = `${student.full_name} (${student.username || 'N/A'})`;
                        studentSelect.appendChild(opt);
                    }
                });
            }
        }
        
        renderStudentTable();
        
        if(loading) loading.style.display = 'none';
    } catch (err) {
        console.error('Error fetching students:', err);
        if(loading) {
            loading.textContent = 'Network Error: ' + err.message;
            loading.style.color = 'var(--danger)';
        }
        if(studentSelect) {
            studentSelect.innerHTML = '<option value="">Failed to load</option>';
        }
    }
}

function renderStudentTable() {
    const body = document.getElementById('studentsBody');
    if(!body) return;
    const filter = document.getElementById('studentSearch') ? document.getElementById('studentSearch').value.trim().toLowerCase() : '';
    const filtered = students.filter(s => {
        const combined = (s.full_name + ' ' + s.username + ' ' + s.email + ' ' + (s.department || '')).toLowerCase();
        return combined.includes(filter);
    });
    body.innerHTML = filtered.length ? filtered.map(student => {
        const activeBadge = student.is_active == 1 ? '<span class="status-badge present">Active</span>' : '<span class="status-badge absent">Inactive</span>';
        return `<tr>
            <td>${student.student_id}</td>
            <td>${escapeHtml(student.full_name)}</td>
            <td>${escapeHtml(student.username)}</td>
            <td>${escapeHtml(student.email)}</td>
            <td>${escapeHtml(student.department || '')}</td>
            <td>${student.level || ''}</td>
            <td>${student.enrollment_year || ''}</td>
            <td>${activeBadge}</td>
            <td>
                <button class="btn btn-primary btn-sm" style="margin-right:4px;" onclick="openStudentModal(${student.student_id})">Edit</button>
                <button class="btn btn-sm" style="background:#f59e0b;color:white; margin-right:4px;" onclick="toggleActive(${student.student_id}, ${student.is_active == 1 ? 0 : 1})">${student.is_active == 1 ? 'Deactivate' : 'Activate'}</button>
                <button class="btn btn-danger btn-sm" onclick="deleteStudent(${student.student_id})">Delete</button>
            </td>
        </tr>`;
    }).join('') : '<tr><td colspan="9" style="text-align:center; color: var(--gray);">No students found.</td></tr>';
}

function openStudentModal(studentId = null) {
    const modal = document.getElementById('studentModal');
    const form = document.getElementById('modalForm');
    const title = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('modalSubmitBtn');
    const passwordField = document.getElementById('modal_password');
    if(!modal) return;
    form.reset();
    if (studentId && studentsById[studentId]) {
        const student = studentsById[studentId];
        title.textContent = 'Edit Student';
        submitBtn.textContent = 'Update Student';
        passwordField.required = false;
        passwordField.style.display = 'none';
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
        submitBtn.textContent = 'Add Student';
        passwordField.required = true;
        passwordField.style.display = 'block';
        document.getElementById('modal_student_id').value = '';
        document.getElementById('modal_is_active').checked = true;
    }
    modal.style.display = 'flex';
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
            const payload = { ...basePayload, password: password, role: 'student' };
            response = await fetch('../../api/auth/register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
        }
        result = await response.json();
        if (!result.success) throw new Error(result.message);
        showToast(isEdit ? 'Student updated successfully' : 'Student added successfully');
        closeStudentModal();
        await fetchStudents();
    } catch (err) {
        showToast('Error saving student: ' + err.message, 'error');
    }
}

async function toggleActive(student_id, targetState) {
    if (!confirm('Change active status for student #' + student_id + '?')) return;
    try {
        const response = await fetch('../../api/student/update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ student_id, is_active: targetState })
        });
        const result = await response.json();
        if (!result.success) throw new Error(result.message);
        showToast('Status updated successfully');
        await fetchStudents();
    } catch (err) {
        showToast('Error updating status: ' + err.message, 'error');
    }
}

async function deleteStudent(student_id) {
    if (!confirm('Are you sure you want to delete student #' + student_id + '? This is irreversible.')) return;
    try {
        const response = await fetch('../../api/student/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ student_id })
        });
        const result = await response.json();
        if (!result.success) throw new Error(result.message);
        showToast('Student deleted successfully');
        await fetchStudents();
    } catch (err) {
        showToast('Error deleting student: ' + err.message, 'error');
    }
}

// Analytics
async function loadAnalytics() {
    const tbody = document.getElementById('analyticsBody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="6" class="empty-state"><div class="loading-spinner"></div></td></tr>';
    const data = await apiCall('../../api/admin/analytics.php');
    if (data.success) {
        const stats = data.data.stats || {};
        const statTotal = document.getElementById('statTotal');
        const statHighRisk = document.getElementById('statHighRisk');
        const statScheduleOpt = document.getElementById('statScheduleOpt');
        const statAttendance = document.getElementById('statAttendance');
        if(statTotal) statTotal.textContent = stats.total || 0;
        if(statHighRisk) statHighRisk.textContent = stats.high_risk || 0;
        if(statScheduleOpt) statScheduleOpt.textContent = stats.schedule_optimizations || 0;
        if(statAttendance) statAttendance.textContent = stats.attendance_analyses || 0;
        
        allAnalytics = data.data.logs || [];
        renderAnalyticsTable(allAnalytics);
        renderRiskStudents(data.data.high_risk_students || []);
    } else {
        tbody.innerHTML = '<tr><td colspan="6" class="empty-state text-danger"><i class="fas fa-exclamation-triangle"></i> Failed to load</td></tr>';
    }
}

function renderAnalyticsTable(data) {
    const tbody = document.getElementById('analyticsBody');
    if (!tbody) return;
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="empty-state"><i class="fas fa-robot"></i><h4>No AI Analytics Yet</h4><p>Analytics will appear here as students use the AI features</p></td></tr>';
        return;
    }
    tbody.innerHTML = data.map(log => {
        const riskClass = log.risk_level === 'high' ? 'danger' : log.risk_level === 'medium' ? 'warning' : 'success';
        const typeClass = log.prediction_type === 'schedule_optimization' ? 'info' : log.prediction_type === 'performance' ? 'success' : 'warning';
        const typeLabel = log.prediction_type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
        let details = '';
        if (typeof log.prediction_result === 'object') {
            const r = log.prediction_result;
            if (log.prediction_type === 'schedule_optimization') {
                details = `Score: ${r.optimization_score || '-'}% | Conflicts: ${r.conflicts_resolved || 0}`;
            } else if (log.prediction_type === 'performance') {
                details = `Predicted Grade: ${r.predicted_grade || '-'} | Score: ${r.predicted_score || '-'}%`;
            } else {
                details = `Attendance: ${r.percentage || '-'}%`;
            }
        }
        return `
            <tr>
                <td><strong>#${log.log_id}</strong></td>
                <td><strong>${escapeHtml(log.student_name)}</strong></td>
                <td><span class="status-badge ${typeClass}">${typeLabel}</span></td>
                <td><span class="status-badge ${riskClass}">${log.risk_level.toUpperCase()}</span></td>
                <td style="max-width: 200px; font-size: 13px;">${escapeHtml(details)}</td>
                <td style="font-size: 12px; color: var(--gray);">${new Date(log.generated_at).toLocaleString()}</td>
            </tr>
        `;
    }).join('');
}

function renderRiskStudents(data) {
    const container = document.getElementById('riskStudentsBody');
    if (!container) return;
    if (!data || data.length === 0) {
        container.innerHTML = '<div class="empty-state"><i class="fas fa-check-circle" style="color: var(--success);"></i><p>No students at high risk</p></div>';
        return;
    }
    container.innerHTML = data.map(student => `
        <div style="display: flex; align-items: center; gap: 16px; padding: 16px; background: #fef2f2; border-radius: var(--radius-sm); margin-bottom: 12px;">
            <div style="width: 48px; height: 48px; background: var(--danger); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <div style="flex: 1;">
                <strong>${escapeHtml(student.student_name)}</strong>
                <div style="font-size: 12px; color: var(--gray);">Attendance: ${student.attendance_percentage || 0}%</div>
            </div>
            <button class="btn btn-primary" style="padding: 8px 16px;" onclick="contactStudent(${student.student_id}, '${escapeHtml(student.student_name)}')"><i class="fas fa-envelope"></i> Contact</button>
        </div>
    `).join('');
}

function filterByType(type) {
    document.querySelectorAll('.filter-tab').forEach(tab => tab.classList.remove('active'));
    event.target.classList.add('active');
    if (type === 'all') {
        renderAnalyticsTable(allAnalytics);
    } else {
        renderAnalyticsTable(allAnalytics.filter(d => d.prediction_type === type));
    }
}

// Course Management (Table)
async function fetchCourses() {
    const tbody = document.getElementById('coursesTableBody');
    if (!tbody) return;
    const totalEl = document.getElementById('totalCourses');
        tbody.innerHTML = '<tr><td colspan="6" class="empty-state"><div class="loading-spinner"></div></td></tr>';
    const data = await apiCall('../../api/course/list.php');
    if (data.success && data.data && data.data.length > 0) {
        if (totalEl) totalEl.textContent = data.data.length;
        tbody.innerHTML = '';
        data.data.forEach(course => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><span class="status-badge info">${course.course_id}</span></td>
                <td><strong>${course.course_code}</strong></td>
                <td>${course.course_name}</td>
                <td><span class="status-badge success">${course.credit_hours} credits</span></td>
                <td>${course.lecturer_name || '-'}</td>
                <td>
                    <button class="btn btn-primary btn-sm" onclick="editCourse(${course.course_id}, '${String(course.course_code).replace(/'/g, "\\'")}', '${String(course.course_name).replace(/'/g, "\\'")}', ${course.credit_hours}, '${String(course.lecturer_name || '').replace(/'/g, "\\'")}')" style="padding: 6px 12px; margin-right: 4px;"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-danger btn-sm" onclick="confirmDelete(${course.course_id}, '${String(course.course_code).replace(/'/g, "\\'")}')" style="padding: 6px 12px;"><i class="fas fa-trash"></i></button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    } else {
        tbody.innerHTML = '<tr><td colspan="6" class="empty-state"><i class="fas fa-book"></i><p>No courses yet</p></td></tr>';
    }
}

// Course options for enrollment dropdown
async function fetchCourseSelect() {
    const courseSelect = document.getElementById('enrollCourseId');
    if (!courseSelect) return;
    courseSelect.innerHTML = '<option value="">Loading...</option>';
    try {
        const data = await apiCall('../../api/course/list.php');
        if (data.success && data.data) {
            courseSelect.innerHTML = '<option value="">Select a course</option>';
            data.data.forEach(course => {
                const opt = document.createElement('option');
                opt.value = course.course_id;
                opt.textContent = `${course.course_code} - ${course.course_name}`;
                courseSelect.appendChild(opt);
            });
        } else {
            courseSelect.innerHTML = '<option value="">No courses available</option>';
        }
    } catch (e) {
        courseSelect.innerHTML = '<option value="">Failed to load courses</option>';
    }
}

function editCourse(id, code, name, credits, lecturer) {
    document.getElementById('courseId').value = id;
    document.getElementById('courseCode').value = code;
    document.getElementById('courseName').value = name;
    document.getElementById('creditHours').value = credits;
    document.getElementById('courseLecturer').value = lecturer;
    document.getElementById('formTitle').textContent = 'Edit Course';
    document.getElementById('submitBtn').innerHTML = ' Update Course';
    document.getElementById('cancelBtn').style.display = 'inline-flex';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function clearForm() {
    document.getElementById('courseForm').reset();
    document.getElementById('courseId').value = '';
    document.getElementById('formTitle').textContent = 'Create New Course';
    document.getElementById('submitBtn').innerHTML = ' Create Course';
    document.getElementById('cancelBtn').style.display = 'none';
}

async function submitCourse(e) {
    e.preventDefault();
    const courseId = document.getElementById('courseId').value;
    const endpoint = courseId ? '../../api/course/update.php' : '../../api/course/create.php';
    const body = {
        course_code: document.getElementById('courseCode').value,
        course_name: document.getElementById('courseName').value,
        credit_hours: Number(document.getElementById('creditHours').value),
        lecturer_name: document.getElementById('courseLecturer').value
    };
    if (courseId) body.course_id = Number(courseId);
    const data = await apiCall(endpoint, { method: 'POST', body: JSON.stringify(body) });
    if (data.success) {
        showToast(courseId ? 'Course updated!' : 'Course created!', 'success');
        clearForm();
        fetchCourses();
    } else {
        showToast(data.message || 'Failed to save course', 'error');
    }
}

function confirmDelete(id, code) {
    deleteTarget = id;
    document.getElementById('deleteMessage').textContent = `Delete course "${code}"? This will remove all schedules.`;
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
        fetchEnrollments();
    } else {
        showToast(data.message || 'Failed to delete course', 'error');
    }
}

async function fetchEnrollments() {
    const tbody = document.getElementById('enrollmentsTableBody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="5" class="empty-state"><div class="loading-spinner"></div></td></tr>';
    }
    try {
        const data = await apiCall('../../api/course/enrollments.php');
        if (data.success) {
            const totalEl = document.getElementById('totalEnrollments');
            if (totalEl) totalEl.textContent = data.data.length;
            enrollmentCache = data.data;
            populateEnrollmentDeptFilter(data.data);
            renderEnrollmentTable(enrollmentCache);
        } else {
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="5" class="empty-state"><i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i><p>Failed to load enrollments</p></td></tr>';
            }
        }
    } catch (e) {
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="5" class="empty-state"><i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i><p>Network error loading enrollments</p></td></tr>';
        }
    }
}

function populateEnrollmentDeptFilter(enrollments) {
    const sel = document.getElementById('enrollmentDeptFilter');
    if (!sel) return;
    const depts = [...new Set(enrollments.map(e => e.course_department).filter(Boolean))].sort();
    sel.innerHTML = '<option value="">All Departments</option>' + depts.map(d => `<option value="${escapeHtml(d)}">${escapeHtml(d)}</option>`).join('');
}

function renderEnrollmentTable(items) {
    const tbody = document.getElementById('enrollmentsTableBody');
    if (!tbody) return;
    if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="empty-state"><i class="fas fa-users"></i><p>No enrollments</p></td></tr>';
        return;
    }
    tbody.innerHTML = items.map(item => `
        <tr>
            <td><strong>${escapeHtml(item.student_name)}</strong><div style="font-size: 11px; color: var(--gray);">${escapeHtml(item.student_department || '')}</div></td>
            <td><span class="status-badge info">${item.course_code}</span></td>
            <td>${item.course_name}<div style="font-size: 11px; color: var(--gray);">${escapeHtml(item.course_department || '')}</div></td>
            <td>${new Date(item.enrolled_at).toLocaleDateString()}</td>
            <td>
                <button class="btn btn-sm" style="padding: 4px 10px; font-size: 12px; background: #fee2e2; color: #991b1b; border: none; border-radius: 6px; cursor: pointer;" onclick="unenroll(${item.student_id}, ${item.course_id}, '${escapeHtml(item.student_name)}')"><i class="fas fa-user-xmark"></i> Unenroll</button>
            </td>
        </tr>
    `).join('');
}

function filterEnrollments() {
    const query = document.getElementById('enrollmentFilter').value.toLowerCase();
    const dept = document.getElementById('enrollmentDeptFilter')?.value || '';
    let filtered = enrollmentCache;
    if (dept) {
        filtered = filtered.filter(item =>
            (item.student_department || '').toLowerCase() === dept.toLowerCase() ||
            (item.course_department || '').toLowerCase() === dept.toLowerCase()
        );
    }
    if (query) {
        filtered = filtered.filter(item =>
            item.student_name.toLowerCase().includes(query) ||
            item.course_code.toLowerCase().includes(query) ||
            item.course_name.toLowerCase().includes(query)
        );
    }
    renderEnrollmentTable(filtered);
}

async function enrollStudent(e) {
    e.preventDefault();
    const studentId = document.getElementById('enrollStudentId').value;
    const courseId = document.getElementById('enrollCourseId').value;
    
    if (!studentId || !courseId) {
        showToast('Please select both student and course', 'error');
        return;
    }
    
    const btn = e.target.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enrolling...';
    
    try {
        const data = await apiCall('../../api/course/enroll.php', {
            method: 'POST',
            body: JSON.stringify({
                student_id: Number(studentId),
                course_id: Number(courseId)
            })
        });
        if (data.success) {
            showToast('Student enrolled successfully!', 'success');
            e.target.reset();
            fetchEnrollments();
        } else {
            showToast(data.message || 'Failed to enroll student', 'error');
        }
    } catch (err) {
        showToast('Error: ' + err.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

async function unenroll(studentId, courseId, name) {
    if (!confirm(`Unenroll ${name}?`)) return;
    const data = await apiCall('../../api/course/unenroll.php', {
        method: 'POST',
        body: JSON.stringify({ student_id: studentId, course_id: courseId })
    });
    if (data.success) {
        showToast('Student unenrolled!', 'success');
        fetchEnrollments();
    } else {
        showToast(data.message || 'Failed to unenroll student', 'error');
    }
}

// Schedule Management (Admin)
async function fetchSchedules() {
    const msg = document.getElementById('loadingMessage');
    if(msg) {
        msg.textContent = 'Loading schedules...';
        msg.style.display = 'block';
    }
    try {
        const response = await fetch('../../api/schedule/get.php');
        const result = await response.json();
        if (!result.success) throw new Error(result.message);
        schedules = result.data;
        scheduleById = {};
        schedules.forEach(s => { scheduleById[s.schedule_id] = s; });
        renderScheduleTable();
        if(msg) msg.style.display = 'none';
    } catch (err) {
        if(msg) msg.textContent = 'Failed to load schedules: ' + err.message;
    }
}

function renderScheduleTable() {
    const body = document.getElementById('scheduleBody');
    if(!body) return;
    const filter = document.getElementById('scheduleSearch') ? document.getElementById('scheduleSearch').value.trim().toLowerCase() : '';
    const filtered = schedules.filter(s => {
        const combined = `${s.course_name} ${s.course_code} ${s.day_of_week} ${s.start_time} ${s.end_time} ${s.room_number || ''}`.toLowerCase();
        return combined.includes(filter);
    });
    body.innerHTML = filtered.length ? filtered.map(s => `
        <tr>
            <td>${s.schedule_id}</td>
            <td>${escapeHtml(s.course_name)}</td>
            <td>${escapeHtml(s.course_code)}</td>
            <td>${escapeHtml(s.day_of_week)}</td>
            <td>${escapeHtml(s.start_time)}</td>
            <td>${escapeHtml(s.end_time)}</td>
            <td>${escapeHtml(s.room_number || '')}</td>
            <td>
                <button class="btn btn-primary btn-sm" style="margin-right:4px;" onclick="openScheduleModal(${s.schedule_id})">Edit</button>
                <button class="btn btn-danger btn-sm" onclick="deleteSchedule(${s.schedule_id})">Delete</button>
            </td>
        </tr>`).join('')
        : '<tr><td colspan="8" style="text-align:center; color: var(--gray);">No schedules found.</td></tr>';
}

// Logout & Sidebar
async function logout() {
    if (confirm('Logout?')) {
        await apiCall('../../api/auth/logout.php');
        window.location.href = '../index.php';
    }
}

function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
}
