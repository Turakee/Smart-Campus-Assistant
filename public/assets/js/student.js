let currentFilter = 'all';
let allRecords = [];

function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatTime(time) {
    if (!time) return '-';
    const [hours, minutes] = time.split(':');
    const h = parseInt(hours);
    const ampm = h >= 12 ? 'PM' : 'AM';
    const hour12 = h % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
}

async function apiCall(endpoint, options = {}) {
    const config = {
        headers: { 'Content-Type': 'application/json' },
        ...options
    };
    const response = await fetch(endpoint, config);
    return await response.json();
}

function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
}

async function logout() {
    if (!confirm('Logout?')) return;
    try {
        await apiCall('../../api/auth/logout.php', { method: 'POST' });
    } catch (e) {}
    window.location.href = '../index.php';
}

function showToast(message, type) {
    const existing = document.querySelector('.toast-notification');
    if (existing) existing.remove();
    const toast = document.createElement('div');
    toast.className = 'toast-notification';
    const bg = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#6366f1';
    toast.style.cssText = `position:fixed;bottom:24px;right:24px;background:${bg};color:white;padding:14px 24px;border-radius:12px;font-weight:500;z-index:9999;box-shadow:0 8px 32px rgba(0,0,0,0.2);animation:slideUp 0.3s ease;max-width:400px;`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.3s'; setTimeout(() => toast.remove(), 300); }, 3500);
}

// ===== DASHBOARD =====

async function loadDashboard() {
    try {
        const data = await apiCall('../../api/stats/get.php');
        if (data.success) {
            document.getElementById('statCourses').textContent = data.data.courses || 0;
            document.getElementById('statAttendance').textContent = (data.data.attendance || 0) + '%';
            document.getElementById('statBookingsPending').textContent = data.data.bookings_pending || 0;
            document.getElementById('statBookingsApproved').textContent = data.data.bookings_approved || 0;
        }
    } catch (e) { console.error('Dashboard stats error:', e); }
    loadApprovedBookings();
    loadEnrolledCoursesList();
    loadRecentSchedule();
    loadNotifications();
    loadRecentAttendance();
}

async function loadApprovedBookings() {
    const container = document.getElementById('approvedBookingsList');
    if (!container) return;
    try {
        const data = await apiCall('../../api/booking/history.php');
        if (data.success && data.data && data.data.length > 0) {
            const approved = data.data.filter(b => b.status === 'approved');
            if (approved.length === 0) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-calendar-check"></i><p>No approved bookings</p></div>';
                return;
            }
            container.innerHTML = approved.slice(0, 3).map(b => `
                <div class="booking-item" style="display:flex;align-items:center;gap:14px;padding:12px 16px;background:var(--light);border-radius:10px;margin-bottom:8px;">
                    <div style="width:40px;height:40px;border-radius:10px;background:rgba(16,185,129,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-door-open" style="color:#10b981;"></i>
                    </div>
                    <div style="flex:1;">
                        <strong style="font-size:14px;">${escapeHtml(b.resource_name)}</strong>
                        <div style="font-size:12px;color:var(--gray);">${b.booking_date} | ${b.start_time?.substring(0,5)}-${b.end_time?.substring(0,5)}</div>
                    </div>
                    <span class="status-badge success">Approved</span>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-calendar-check"></i><p>No approved bookings</p></div>';
        }
    } catch (e) {
        container.innerHTML = '<div class="empty-state text-danger"><i class="fas fa-exclamation-triangle"></i><p>Failed to load</p></div>';
    }
}

async function loadEnrolledCoursesList() {
    const container = document.getElementById('enrolledCoursesList');
    if (!container) return;
    try {
        const data = await apiCall('../../api/course/my-courses.php');
        if (data.success && data.data && data.data.length > 0) {
            container.innerHTML = data.data.slice(0, 4).map(c => `
                <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;background:var(--light);border-radius:10px;margin-bottom:6px;">
                    <div style="width:36px;height:36px;border-radius:8px;background:rgba(99,102,241,0.12);display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-book" style="color:var(--primary);font-size:14px;"></i>
                    </div>
                    <div style="flex:1;">
                        <strong style="font-size:13px;">${escapeHtml(c.course_name)}</strong>
                        <span style="font-size:11px;color:var(--gray);margin-left:6px;">${escapeHtml(c.course_code)}</span>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-graduation-cap"></i><p>No enrolled courses</p></div>';
        }
    } catch (e) {
        container.innerHTML = '<div class="empty-state text-danger"><i class="fas fa-exclamation-triangle"></i><p>Failed to load</p></div>';
    }
}

async function loadRecentSchedule() {
    const container = document.getElementById('todayScheduleList');
    if (!container) return;
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const today = days[new Date().getDay()];
    try {
        const data = await apiCall('../../api/schedule/get.php');
        if (data.success && data.data && data.data.length > 0) {
            const todayClasses = data.data.filter(s => s.day_of_week === today);
            if (todayClasses.length === 0) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-sun"></i><p>No classes today</p></div>';
                return;
            }
            container.innerHTML = todayClasses.map(cls => `
                <div style="display:flex;align-items:center;gap:12px;padding:12px 14px;background:var(--light);border-radius:10px;border-left:3px solid var(--primary);">
                    <div style="flex:1;">
                        <strong style="font-size:13px;">${escapeHtml(cls.course_name)}</strong>
                        <div style="font-size:11px;color:var(--gray);">${escapeHtml(cls.course_code)} | ${cls.room_number || 'TBA'}</div>
                    </div>
                    <div style="font-size:12px;font-weight:600;color:var(--primary);white-space:nowrap;">${formatTime(cls.start_time)}</div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-calendar-xmark"></i><p>No schedule available</p></div>';
        }
    } catch (e) {
        container.innerHTML = '<div class="empty-state text-danger"><i class="fas fa-exclamation-triangle"></i><p>Failed to load</p></div>';
    }
}

async function loadNotifications() {
    const container = document.getElementById('notificationsList');
    const badge = document.getElementById('notificationBadge');
    if (!container) return;
    try {
        const data = await apiCall('../../api/notifications/get.php');
        if (data.success && data.data) {
            const notifs = data.data.notifications || [];
            const unread = data.data.unread_count || 0;
            if (badge) {
                badge.textContent = unread;
                badge.style.display = unread > 0 ? 'inline' : 'none';
            }
            if (notifs.length === 0) {
                container.innerHTML = '<div class="empty-state" style="padding:20px;"><i class="fas fa-bell-slash"></i><p>No notifications</p></div>';
                return;
            }
            container.innerHTML = notifs.slice(0, 5).map(n => {
                const icon = n.type === 'danger' ? 'fa-exclamation-circle' : n.type === 'success' ? 'fa-check-circle' : 'fa-info-circle';
                const color = n.type === 'danger' ? '#ef4444' : n.type === 'success' ? '#10b981' : 'var(--primary)';
                return `
                    <div style="display:flex;align-items:flex-start;gap:12px;padding:12px 14px;border-bottom:1px solid var(--border);${!n.is_read ? 'background:rgba(99,102,241,0.04);' : ''}">
                        <i class="fas ${icon}" style="color:${color};margin-top:2px;"></i>
                        <div style="flex:1;font-size:13px;">${escapeHtml(n.message)}</div>
                        <small style="color:var(--gray);font-size:11px;white-space:nowrap;">${n.created_at ? new Date(n.created_at).toLocaleDateString() : ''}</small>
                    </div>
                `;
            }).join('');
        }
    } catch (e) {
        container.innerHTML = '<div class="empty-state text-danger"><i class="fas fa-exclamation-triangle"></i><p>Failed</p></div>';
    }
}

async function loadRecentAttendance() {
    const tbody = document.getElementById('attendanceTableBody');
    if (!tbody) return;
    try {
        const data = await apiCall('../../api/attendance/get.php');
        if (data.success) {
            const records = data.data.records || data.data || [];
            if (records.length > 0) {
                tbody.innerHTML = records.slice(0, 5).map(item => {
                    const statusClass = item.status === 'present' ? 'success' : item.status === 'late' ? 'warning' : item.status === 'excused' ? 'info' : 'danger';
                    return `
                        <tr>
                            <td data-label="Course">${item.course_code || '-'}</td>
                            <td data-label="Date">${new Date(item.date).toLocaleDateString()}</td>
                            <td data-label="Status"><span class="status-badge ${statusClass}">${capitalize(item.status)}</span></td>
                        </tr>
                    `;
                }).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="3" class="empty-state"><i class="fas fa-clipboard"></i><p>No attendance records</p></td></tr>';
            }
        }
    } catch (err) {
        tbody.innerHTML = '<tr><td colspan="3" class="empty-state text-danger"><i class="fas fa-exclamation-triangle"></i> Failed</td></tr>';
    }
}

function toggleNotifMenu() {
    const dropdown = document.getElementById('notifDropdown');
    if (dropdown) dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}

async function markAllAsRead() {
    try {
        await apiCall('../../api/notifications/mark-read.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'mark_all_read' })
        });
        toggleNotifMenu();
        loadNotifications();
    } catch (e) { console.error(e); }
}

async function clearAllNotifications() {
    try {
        await apiCall('../../api/notifications/mark-read.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'clear_all' })
        });
        toggleNotifMenu();
        loadNotifications();
    } catch (e) { console.error(e); }
}

// ===== ATTENDANCE =====

async function loadAttendance() {
    const tbody = document.getElementById('attendanceTableBody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="3" class="empty-state"><div class="loading-spinner"></div></td></tr>';
    try {
        const data = await apiCall('../../api/attendance/get.php');
        if (data.success) {
            const records = data.data.records || data.data || [];
            const stats = data.data.stats || {};
            const pct = document.getElementById('statPercentage');
            const pr = document.getElementById('statPresent');
            const ab = document.getElementById('statAbsent');
            const lt = document.getElementById('statLate');
            const ex = document.getElementById('statExcused');
            if (pct) pct.textContent = (stats.percentage || 0) + '%';
            if (pr) pr.textContent = stats.present || 0;
            if (ab) ab.textContent = stats.absent || 0;
            if (lt) lt.textContent = stats.late || 0;
            if (ex) ex.textContent = stats.excused || 0;
            allRecords = records;
            renderAttendance(allRecords);
        }
    } catch (err) {
        tbody.innerHTML = '<tr><td colspan="3" class="empty-state text-danger"><i class="fas fa-exclamation-triangle"></i> Failed</td></tr>';
    }
}

function renderAttendance(records) {
    const tbody = document.getElementById('attendanceTableBody');
    if (!tbody) return;
    let filtered = records;
    if (currentFilter !== 'all') filtered = records.filter(r => r.status === currentFilter);
    if (filtered.length === 0) {
        tbody.innerHTML = `<tr><td colspan="3" class="empty-state"><i class="fas fa-clipboard"></i><p>No ${currentFilter === 'all' ? '' : currentFilter} records</p></td></tr>`;
        return;
    }
    tbody.innerHTML = filtered.map(item => {
        const statusClass = item.status === 'present' ? 'success' : item.status === 'late' ? 'warning' : item.status === 'excused' ? 'info' : 'danger';
        const date = new Date(item.date);
        const formatted = date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
        return `
            <tr>
                <td data-label="Course"><strong>${item.course_code || '-'}</strong><div style="font-size: 12px; color: var(--gray);">${item.course_name || ''}</div></td>
                <td data-label="Date">${formatted}</td>
                <td data-label="Status"><span class="status-badge ${statusClass}">${capitalize(item.status)}</span></td>
            </tr>
        `;
    }).join('');
}

function filterAttendance(filter) {
    currentFilter = filter;
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.filter-tab').forEach(t => {
        if (t.textContent.toLowerCase().trim() === filter || (filter === 'all' && t.textContent.trim() === 'All')) {
            t.classList.add('active');
        }
    });
    renderAttendance(allRecords);
}

// ===== BOOKING =====

let selectedResourceId = null;

function selectResource(resourceId) {
    selectedResourceId = resourceId;
    const card = document.querySelector(`.resource-card[onclick*="selectResource(${resourceId})"]`);
    document.querySelectorAll('.resource-card').forEach(c => c.style.borderColor = 'transparent');
    if (card) card.style.borderColor = 'var(--primary)';

    fetch('../../api/resource/list.php?id=' + resourceId)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.data) {
                document.getElementById('resourceName').value = data.data.resource_name || 'Selected';
                document.getElementById('resourceId').value = resourceId;
            } else {
                document.getElementById('resourceName').value = 'Resource selected';
                document.getElementById('resourceId').value = resourceId;
            }
        })
        .catch(() => {
            document.getElementById('resourceName').value = 'Resource selected';
            document.getElementById('resourceId').value = resourceId;
        });
}

async function loadBookingHistory() {
    const tbody = document.getElementById('bookingHistoryBody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="5" class="empty-state"><div class="loading-spinner"></div></td></tr>';
    try {
        const data = await apiCall('../../api/booking/history.php');
        if (data.success && data.data && data.data.length > 0) {
            const statusColors = { pending: 'warning', approved: 'success', rejected: 'danger', cancelled: 'secondary' };
            tbody.innerHTML = data.data.map(b => `
                <tr>
                    <td data-label="Resource">${escapeHtml(b.resource_name)}</td>
                    <td data-label="Date">${b.booking_date}</td>
                    <td data-label="Time">${b.start_time?.substring(0,5)}-${b.end_time?.substring(0,5)}</td>
                    <td data-label="Purpose">${escapeHtml(b.purpose || '-')}</td>
                    <td data-label="Status"><span class="status-badge ${statusColors[b.status] || 'secondary'}">${capitalize(b.status)}</span></td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="5" class="empty-state"><i class="fas fa-clock-rotate-left"></i><p>No booking history</p></td></tr>';
        }
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="5" class="empty-state text-danger"><i class="fas fa-exclamation-triangle"></i> Failed</td></tr>';
    }
}

async function submitBooking(event) {
    event.preventDefault();
    const resourceId = document.getElementById('resourceId').value;
    const date = document.getElementById('bookingDate').value;
    const timeSlot = document.getElementById('timeSlot').value;
    const purpose = document.getElementById('purpose').value.trim();

    if (!resourceId) { showToast('Please select a resource', 'error'); return; }
    if (!date) { showToast('Please select a date', 'error'); return; }
    if (!timeSlot) { showToast('Please select a time slot', 'error'); return; }

    const [start, end] = timeSlot.split('-');
    const btn = event.target.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<div class="loading-spinner" style="width:20px;height:20px;border-width:2px;margin:0;"></div>';

    try {
        const data = await apiCall('../../api/booking/submit.php', {
            method: 'POST',
            body: JSON.stringify({
                resource_id: parseInt(resourceId),
                booking_date: date,
                start_time: start + ':00',
                end_time: end + ':00',
                purpose: purpose,
                csrf_token: document.getElementById('csrfToken')?.value || ''
            })
        });
        if (data.success) {
            showToast('Booking request submitted successfully!', 'success');
            document.getElementById('bookingForm').reset();
            document.getElementById('resourceName').value = '';
            selectedResourceId = null;
            document.querySelectorAll('.resource-card').forEach(c => c.style.borderColor = 'transparent');
            loadBookingHistory();
        } else {
            showToast(data.message || 'Failed to submit booking', 'error');
        }
    } catch (e) {
        showToast('Network error. Please try again.', 'error');
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Booking Request';
}

// ===== AI INSIGHTS =====

async function runAttendancePrediction() {
    const resultDiv = document.getElementById('predictionResult');
    if (!resultDiv) return;
    resultDiv.innerHTML = '<div class="empty-state"><div class="loading-spinner"></div></div>';
    try {
        const data = await apiCall('../../api/ai/predict-attendance.php', { method: 'POST' });
        if (data.success && data.data) {
            const d = data.data;
            const riskColors = { low: '#10b981', medium: '#f59e0b', high: '#ef4444' };
            const riskLabels = { low: 'Low Risk', medium: 'Medium Risk', high: 'High Risk' };
            resultDiv.innerHTML = `
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-top:12px;">
                    <div class="stat-card"><div class="stat-icon green"><i class="fas fa-chart-line"></i></div><div class="stat-details"><h3>${d.attendance_percentage}%</h3><p>Attendance Rate</p></div></div>
                    <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-calendar-check"></i></div><div class="stat-details"><h3>${d.total_classes || 0}</h3><p>Total Classes</p></div></div>
                    <div class="stat-card"><div class="stat-icon orange"><i class="fas fa-exclamation-triangle"></i></div><div class="stat-details"><h3 style="color:${riskColors[d.risk_level] || '#6366f1'}">${riskLabels[d.risk_level] || d.risk_level}</h3><p>Risk Level (${d.risk_score || 0}%)</p></div></div>
                </div>
                ${d.consecutive_absences > 0 ? `<div style="margin-top:12px;padding:12px;background:#fef2f2;border-radius:8px;color:#991b1b;font-size:13px;"><i class="fas fa-exclamation-circle"></i> ${d.consecutive_absences} consecutive absence(s) detected</div>` : ''}
                ${d.recommendations && d.recommendations.length > 0 ? `
                    <div style="margin-top:16px;"><h4 style="margin-bottom:8px;">Recommendations</h4>
                    <ul style="padding-left:20px;">${d.recommendations.map(r => `<li style="font-size:13px;margin-bottom:6px;">${escapeHtml(r)}</li>`).join('')}</ul></div>
                ` : ''}
                ${d.ai_engine_used ? '<small style="display:block;margin-top:8px;color:var(--gray);"><i class="fas fa-microchip"></i> Powered by AI Engine</small>' : ''}
            `;
            updateStatCards(d, riskColors, riskLabels);
            if (d.recommendations && d.recommendations.length > 0) {
                updateRecommendations(d.recommendations);
            }
        } else {
            resultDiv.innerHTML = '<div class="empty-state text-danger"><p>' + (data.message || 'Prediction failed') + '</p></div>';
        }
    } catch (e) {
        resultDiv.innerHTML = '<div class="empty-state text-danger"><p>Failed to run prediction</p></div>';
    }
}

function updateStatCards(d, riskColors, riskLabels) {
    const aiScoreEl = document.getElementById('aiScore');
    const attPctEl = document.getElementById('attendancePercent');
    const riskEl = document.getElementById('riskLevel');
    if (aiScoreEl) aiScoreEl.textContent = d.attendance_percentage + '%';
    if (attPctEl) attPctEl.textContent = d.attendance_percentage + '%';
    if (riskEl) {
        riskEl.textContent = riskLabels && riskLabels[d.risk_level] ? riskLabels[d.risk_level] : d.risk_level;
        riskEl.style.color = (riskColors && riskColors[d.risk_level]) ? riskColors[d.risk_level] : '#6366f1';
    }
}

function updateRecommendations(items) {
    const list = document.getElementById('recommendationList');
    if (!list) return;
    if (list.querySelector('li.placeholder')) {
        list.innerHTML = '';
    }
    items.forEach(r => {
        const li = document.createElement('li');
        li.innerHTML = '<i class="fas fa-lightbulb" style="color:var(--primary);"></i> ' + escapeHtml(r);
        list.appendChild(li);
    });
}

async function runScheduleOptimization() {
    const resultDiv = document.getElementById('optimizationResult');
    if (!resultDiv) return;
    resultDiv.innerHTML = '<div class="empty-state"><div class="loading-spinner"></div></div>';
    try {
        const data = await apiCall('../../api/ai/optimize-schedule.php', { method: 'POST' });
        if (data.success && data.data) {
            const d = data.data;
            let html = `
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-top:12px;">
                    <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-star"></i></div><div class="stat-details"><h3>${d.optimization_score || 0}/100</h3><p>Optimization Score</p></div></div>
                    <div class="stat-card"><div class="stat-icon orange"><i class="fas fa-exclamation-triangle"></i></div><div class="stat-details"><h3>${d.conflicts_resolved || 0}</h3><p>Conflicts Resolved</p></div></div>
                </div>
            `;
            if (d.optimized_slots && d.optimized_slots.length > 0) {
                html += `<div style="margin-top:16px;"><h4 style="margin-bottom:8px;">Optimized Schedule</h4>`;
                d.optimized_slots.forEach(slot => {
                    html += `<div style="display:flex;align-items:center;gap:12px;padding:10px 14px;background:var(--light);border-radius:8px;margin-bottom:6px;">
                        <i class="fas fa-calendar-day" style="color:var(--primary);"></i>
                        <div style="flex:1;font-size:13px;"><strong>${escapeHtml(slot.course_code || slot.course_name || '')}</strong> — ${slot.day || ''} ${slot.time || ''}</div>
                        <small style="color:var(--gray);">${slot.room || ''}</small>
                    </div>`;
                });
                html += `</div>`;
            } else {
                html += `<div style="margin-top:12px;padding:12px;background:#f0fdf4;border-radius:8px;color:#166534;font-size:13px;"><i class="fas fa-check-circle"></i> No conflicts found. Your schedule is already optimized.</div>`;
            }
            html += d.ai_engine_used ? '<small style="display:block;margin-top:8px;color:var(--gray);"><i class="fas fa-microchip"></i> Powered by AI Engine</small>' : '';
            resultDiv.innerHTML = html;
            const optScoreEl = document.getElementById('optimizationScore');
            if (optScoreEl) optScoreEl.textContent = (d.optimization_score || 0) + '/100';
        } else {
            resultDiv.innerHTML = '<div class="empty-state text-danger"><p>' + (data.message || 'Optimization failed') + '</p></div>';
        }
    } catch (e) {
        resultDiv.innerHTML = '<div class="empty-state text-danger"><p>Failed to run optimization</p></div>';
    }
}

async function runPerformancePrediction() {
    const resultDiv = document.getElementById('performanceResult');
    if (!resultDiv) return;
    resultDiv.innerHTML = '<div class="empty-state"><div class="loading-spinner"></div></div>';
    try {
        const data = await apiCall('../../api/ai/predict-performance.php', { method: 'POST' });
        if (data.success && data.data) {
            const d = data.data;
            const gradeColors = { A: '#10b981', B: '#3b82f6', C: '#f59e0b', D: '#f97316', F: '#ef4444' };
            const riskColors = { low: '#10b981', medium: '#f59e0b', high: '#ef4444' };
            const riskLabels = { low: 'Low Risk', medium: 'Medium Risk', high: 'High Risk' };
            resultDiv.innerHTML = `
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-top:12px;">
                    <div class="stat-card"><div class="stat-icon" style="background:rgba(16,185,129,0.12);"><i class="fas fa-graduation-cap" style="color:#10b981;"></i></div><div class="stat-details"><h3 style="color:${gradeColors[d.predicted_grade] || '#6366f1'}">${d.predicted_grade || 'N/A'}</h3><p>Predicted Grade</p></div></div>
                    <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-chart-line"></i></div><div class="stat-details"><h3>${d.predicted_score || 0}%</h3><p>Predicted Score</p></div></div>
                    <div class="stat-card"><div class="stat-icon orange"><i class="fas fa-exclamation-triangle"></i></div><div class="stat-details"><h3 style="color:${riskColors[d.risk_level] || '#6366f1'}">${riskLabels[d.risk_level] || d.risk_level}</h3><p>Risk Level</p></div></div>
                </div>
                <div style="margin-top:12px;display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:8px;">
                    <div style="padding:8px 12px;background:var(--light);border-radius:6px;text-align:center;"><strong>${d.total_classes || 0}</strong><br><small>Classes</small></div>
                    <div style="padding:8px 12px;background:var(--light);border-radius:6px;text-align:center;"><strong>${d.present || 0}</strong><br><small>Present</small></div>
                    <div style="padding:8px 12px;background:var(--light);border-radius:6px;text-align:center;"><strong>${d.excused || 0}</strong><br><small>Excused</small></div>
                    <div style="padding:8px 12px;background:var(--light);border-radius:6px;text-align:center;"><strong>${d.absent || 0}</strong><br><small>Absent</small></div>
                    <div style="padding:8px 12px;background:var(--light);border-radius:6px;text-align:center;"><strong>${d.late || 0}</strong><br><small>Late</small></div>
                </div>
                ${d.recommendations && d.recommendations.length > 0 ? `
                    <div style="margin-top:16px;"><h4 style="margin-bottom:8px;">Recommendations</h4>
                    <ul style="padding-left:20px;">${d.recommendations.map(r => `<li style="font-size:13px;margin-bottom:6px;">${escapeHtml(r)}</li>`).join('')}</ul></div>
                ` : ''}
                ${d.ai_engine_used ? '<small style="display:block;margin-top:8px;color:var(--gray);"><i class="fas fa-microchip"></i> Powered by AI Engine</small>' : ''}
            `;
            if (d.recommendations && d.recommendations.length > 0) {
                updateRecommendations(d.recommendations);
            }
        } else {
            resultDiv.innerHTML = '<div class="empty-state text-danger"><p>' + (data.message || 'Prediction failed') + '</p></div>';
        }
    } catch (e) {
        resultDiv.innerHTML = '<div class="empty-state text-danger"><p>Failed to run prediction</p></div>';
    }
}

function loadAIInsights() {
    const list = document.getElementById('recommendationList');
    if (list) {
        list.innerHTML = '<li class="placeholder" style="color:var(--gray);"><i class="fas fa-sync fa-spin"></i> Loading recommendations...</li>';
    }
    runAttendancePrediction();
    runScheduleOptimization();
    runPerformancePrediction();
}


