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
    <title>Booking Approvals - Smart Campus Assistant</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
            <a href="bookings.php" class="nav-item active"><i class="fas fa-door-open"></i> Approve Bookings</a>
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
                <h2>Booking Approvals</h2>
            </div>
            <div class="user-profile">
                <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($user_name); ?></span>
            </div>
        </header>

        <div class="dashboard-container">
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-door-open"></i> Pending Bookings</h3>
                    <button id="refreshBtn" class="btn btn-primary"><i class="fas fa-sync"></i> Refresh</button>
                </div>

                <div id="alertBox" style="margin-bottom:12px; display:none; padding:10px; border-radius:8px;"></div>

                <div style="overflow-x:auto;">
                    <table class="data-table" id="bookingsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Student</th>
                                <th>Resource</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Purpose</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="bookingsBody"></tbody>
                    </table>
                </div>

                <div id="loadingMessage" style="padding:14px; color: var(--gray);">Loading bookings...</div>
            </div>
        </div>
    </main>

    <script>
        function showAlert(message, type = 'success') {
            const alertBox = document.getElementById('alertBox');
            alertBox.style.display = 'block';
            alertBox.style.background = type === 'success' ? '#dcfce7' : '#fee2e2';
            alertBox.style.color = type === 'success' ? '#166534' : '#991b1b';
            alertBox.textContent = message;
            setTimeout(() => { alertBox.style.display = 'none'; }, 3000);
        }

        async function fetchBookings() {
            const msg = document.getElementById('loadingMessage');
            msg.textContent = 'Loading bookings...';
            msg.style.display = 'block';
            try {
                const response = await fetch('../../api/booking/pending.php');
                const result = await response.json();
                if (!result.success) throw new Error(result.message);
                renderTable(result.data);
                msg.style.display = 'none';
            } catch (err) {
                msg.textContent = 'Failed to load bookings: ' + err.message;
            }
        }

        function renderTable(bookings) {
            const body = document.getElementById('bookingsBody');
            body.innerHTML = bookings.length ? bookings.map(b => {
                const date = new Date(b.booking_date);
                const formattedDate = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                return `
                <tr>
                    <td><strong>#${b.booking_id}</strong></td>
                    <td>
                        <strong>${escapeHtml(b.student_name)}</strong>
                        <div style="font-size: 11px; color: var(--gray);">${capitalize(b.resource_type)}</div>
                    </td>
                    <td><strong>${escapeHtml(b.resource_name)}</strong></td>
                    <td>${formattedDate}</td>
                    <td>${formatTime(b.start_time)} - ${formatTime(b.end_time)}</td>
                    <td style="max-width: 200px;">
                        <div style="font-size: 12px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${escapeHtml(b.purpose || '')}">
                            ${escapeHtml(b.purpose) || '<em style="color:var(--gray);">No purpose specified</em>'}
                        </div>
                    </td>
                    <td>
                        <button class="btn btn-primary" style="margin-right:4px;" onclick="updateStatus(${b.booking_id}, 'approved')">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button class="btn" style="background:#ef4444;color:white;" onclick="updateStatus(${b.booking_id}, 'rejected')">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </td>
                </tr>`;
            }).join('')
                : '<tr><td colspan="7" style="text-align:center;"><div class="empty-state"><i class="fas fa-check-circle" style="color: #10b981;"></i><p>No pending bookings!</p></div></td></tr>';
        }

        function formatTime(time) {
            if (!time) return '-';
            const [hours, minutes] = time.split(':');
            const h = parseInt(hours);
            const ampm = h >= 12 ? 'PM' : 'AM';
            const hour12 = h % 12 || 12;
            return `${hour12}:${minutes} ${ampm}`;
        }

        function capitalize(str) {
            if (!str) return '';
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        function escapeHtml(value) {
            if (typeof value !== 'string') return value;
            return value.replace(/[&<>\"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[char]));
        }

        async function updateStatus(bookingId, status) {
            if (!confirm(`Are you sure you want to ${status} this booking?`)) return;
            try {
                const response = await fetch('../../api/booking/update.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ booking_id: bookingId, status })
                });
                const result = await response.json();
                if (!result.success) throw new Error(result.message);
                showAlert(`Booking ${status}`, 'success');
                await fetchBookings();
            } catch (err) {
                showAlert('Error updating booking: ' + err.message, 'error');
            }
        }

        function logout() {
            if (confirm('Logout?')) {
                fetch('../../api/auth/logout.php').then(() => window.location.href = '../index.php');
            }
        }

        document.getElementById('refreshBtn').addEventListener('click', fetchBookings);
        fetchBookings();

        function toggleSidebar() { document.querySelector('.sidebar').classList.toggle('active'); }
    </script>
</body>
</html>
