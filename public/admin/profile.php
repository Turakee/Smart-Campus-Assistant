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
    <title>Admin Profile - Smart Campus Assistant</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .profile-section { background: white; border-radius: 12px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.08); }
        .profile-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; }
        .profile-avatar { width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); color: white; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 700; flex-shrink: 0; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 600px) { .form-row { grid-template-columns: 1fr; } }
        .msg { padding: 10px 14px; border-radius: 8px; font-weight: 600; font-size: 13px; display: none; margin-bottom: 16px; }
        .msg.success { display: block; background: #dcfce7; color: #166534; }
        .msg.error { display: block; background: #fee2e2; color: #991b1b; }
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
            <a href="analytics.php" class="nav-item"><i class="fas fa-chart-line"></i> AI Analytics</a>
            <a href="profile.php" class="nav-item active"><i class="fas fa-user-circle"></i> Profile</a>
            <a href="#" onclick="logout()" class="nav-item"><i class="fas fa-right-from-bracket"></i> Logout</a>
        </nav>
    </aside>

    <div class="sidebar-backdrop" onclick="toggleSidebar()"></div>

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button class="mobile-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <h2><i class="fas fa-user-circle"></i> My Profile</h2>
            </div>
            <div class="user-profile">
                <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($user_name); ?></span>
            </div>
        </header>

        <div class="dashboard-container">
            <div class="profile-section">
                <div class="profile-header">
                    <div class="profile-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                    <div>
                        <h3 id="displayName" style="margin:0;font-size:20px;font-weight:700;"><?php echo htmlspecialchars($user_name); ?></h3>
                        <p id="displayRole" style="margin:4px 0 0;color:var(--gray);font-size:14px;">Loading...</p>
                    </div>
                </div>

                <div id="profileMsg" class="msg"></div>

                <form id="profileForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" id="fullName" required>
                        </div>
                        <div class="form-group">
                            <label>Position</label>
                            <input type="text" id="position" placeholder="e.g. IT Director">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" id="username" disabled style="background:#f1f5f9;cursor:not-allowed;">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" id="email" disabled style="background:#f1f5f9;cursor:not-allowed;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <input type="text" id="role" disabled style="background:#f1f5f9;cursor:not-allowed;max-width:300px;">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-floppy-disk"></i> Save Profile
                    </button>
                </form>
            </div>

            <div class="profile-section">
                <h3 style="margin:0 0 16px;font-size:16px;font-weight:700;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-lock" style="color:#667eea;"></i> Change Password
                </h3>

                <div id="passwordMsg" class="msg"></div>

                <form id="passwordForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" id="currentPassword" required>
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" id="newPassword" required minlength="6">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script>
        async function loadProfile() {
            try {
                const res = await fetch('../../api/admin/profile.php');
                const result = await res.json();
                if (!result.success) throw new Error(result.message);
                const p = result.data;
                document.getElementById('displayName').textContent = p.full_name;
                document.getElementById('displayRole').textContent = p.role === 'system_admin' ? 'System Administrator' : 'Administrator';
                document.getElementById('fullName').value = p.full_name || '';
                document.getElementById('position').value = p.position || '';
                document.getElementById('username').value = p.username || '';
                document.getElementById('email').value = p.email || '';
                document.getElementById('role').value = p.role || '';
            } catch (err) {
                showMsg('profileMsg', 'Failed to load profile: ' + err.message, 'error');
            }
        }

        function showMsg(id, text, type) {
            const el = document.getElementById(id);
            el.textContent = text;
            el.className = 'msg ' + type;
            setTimeout(() => { el.className = 'msg'; }, 4000);
        }

        document.getElementById('profileForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const payload = {
                full_name: document.getElementById('fullName').value.trim(),
                position: document.getElementById('position').value.trim()
            };
            if (!payload.full_name) {
                showMsg('profileMsg', 'Full name is required', 'error');
                return;
            }
            try {
                const res = await fetch('../../api/admin/profile.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await res.json();
                if (!result.success) throw new Error(result.message);
                showMsg('profileMsg', 'Profile updated successfully', 'success');
                document.getElementById('displayName').textContent = payload.full_name;
                document.querySelector('.user-avatar').textContent = payload.full_name.charAt(0).toUpperCase();
                document.querySelector('.user-profile span').textContent = payload.full_name;
            } catch (err) {
                showMsg('profileMsg', 'Error: ' + err.message, 'error');
            }
        });

        document.getElementById('passwordForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const current = document.getElementById('currentPassword').value;
            const newPass = document.getElementById('newPassword').value;
            if (!current || !newPass) {
                showMsg('passwordMsg', 'Both fields are required', 'error');
                return;
            }
            if (newPass.length < 6) {
                showMsg('passwordMsg', 'New password must be at least 6 characters', 'error');
                return;
            }
            try {
                const res = await fetch('../../api/auth/change-password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ current_password: current, new_password: newPass })
                });
                const result = await res.json();
                if (!result.success) throw new Error(result.message);
                showMsg('passwordMsg', 'Password changed successfully', 'success');
                document.getElementById('currentPassword').value = '';
                document.getElementById('newPassword').value = '';
            } catch (err) {
                showMsg('passwordMsg', 'Error: ' + err.message, 'error');
            }
        });

        function logout() {
            if (confirm('Logout?')) {
                fetch('../../api/auth/logout.php').then(() => window.location.href = '../index.php');
            }
        }

        loadProfile();

        function toggleSidebar() { document.querySelector('.sidebar').classList.toggle('active'); }
    </script>
</body>
</html>
