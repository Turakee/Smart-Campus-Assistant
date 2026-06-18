<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Fetch student profile
$stmt = $db->prepare("SELECT s.student_id, s.full_name, s.department, s.level, s.enrollment_year, u.username, u.email, u.created_at, u.last_login FROM students s JOIN users u ON s.user_id = u.user_id WHERE u.user_id = :uid LIMIT 1");
$stmt->execute([':uid' => $_SESSION['user_id']]);
$profile = $stmt->fetch();

if (!$profile) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Smart Campus Assistant</title>
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
            <a href="courses.php" class="nav-item"><i class="fas fa-book"></i> Courses</a>
            <a href="booking.php" class="nav-item"><i class="fas fa-door-open"></i> Bookings</a>
            <a href="ai-insights.php" class="nav-item"><i class="fas fa-robot"></i> AI Insights</a>
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
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
            </div>
        </header>

        <div class="dashboard-container">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 28px;">
                <!-- Profile Details Card -->
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="fas fa-id-card"></i> Profile Details</h3>
                        <button class="btn btn-primary" onclick="toggleEdit()" id="editBtn"><i class="fas fa-pen"></i> Edit</button>
                    </div>
                    <div id="profileView">
                        <div style="text-align: center; margin-bottom: 24px;">
                            <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--primary), var(--primary-light)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-size: 32px; color: white; font-weight: 700;">
                                <?php echo strtoupper(substr($profile['full_name'], 0, 1)); ?>
                            </div>
                            <h2 style="margin: 0;"><?php echo htmlspecialchars($profile['full_name']); ?></h2>
                            <p style="color: var(--gray);"><?php echo htmlspecialchars($profile['email']); ?></p>
                        </div>
                        <div style="display: grid; gap: 16px;">
                            <div style="display: flex; justify-content: space-between; padding: 12px 16px; background: var(--light); border-radius: var(--radius);">
                                <span style="color: var(--gray);"><i class="fas fa-user"></i> Username</span>
                                <span style="font-weight: 600;"><?php echo htmlspecialchars($profile['username']); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 12px 16px; background: var(--light); border-radius: var(--radius);">
                                <span style="color: var(--gray);"><i class="fas fa-building"></i> Department</span>
                                <span style="font-weight: 600;" id="displayDepartment"><?php echo htmlspecialchars($profile['department'] ?? 'Not set'); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 12px 16px; background: var(--light); border-radius: var(--radius);">
                                <span style="color: var(--gray);"><i class="fas fa-layer-group"></i> Level</span>
                                <span style="font-weight: 600;" id="displayLevel"><?php echo htmlspecialchars($profile['level'] ?? 'Not set'); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 12px 16px; background: var(--light); border-radius: var(--radius);">
                                <span style="color: var(--gray);"><i class="fas fa-calendar"></i> Enrollment Year</span>
                                <span style="font-weight: 600;"><?php echo htmlspecialchars($profile['enrollment_year'] ?? 'Not set'); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 12px 16px; background: var(--light); border-radius: var(--radius);">
                                <span style="color: var(--gray);"><i class="fas fa-clock"></i> Member Since</span>
                                <span style="font-weight: 600;"><?php echo date('M d, Y', strtotime($profile['created_at'])); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 12px 16px; background: var(--light); border-radius: var(--radius);">
                                <span style="color: var(--gray);"><i class="fas fa-sign-in-alt"></i> Last Login</span>
                                <span style="font-weight: 600;"><?php echo $profile['last_login'] ? date('M d, Y h:i A', strtotime($profile['last_login'])) : 'First login'; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Form -->
                    <div id="profileEdit" style="display: none;">
                        <form id="profileForm" onsubmit="event.preventDefault(); saveProfile();">
                            <div style="display: grid; gap: 16px;">
                                <div class="form-group">
                                    <label><i class="fas fa-user"></i> Full Name</label>
                                    <input type="text" id="editFullName" class="form-control" value="<?php echo htmlspecialchars($profile['full_name']); ?>" required minlength="3" maxlength="100">
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-envelope"></i> Email</label>
                                    <input type="email" id="editEmail" class="form-control" value="<?php echo htmlspecialchars($profile['email']); ?>" readonly style="background: var(--light); cursor: not-allowed;">
                                    <small style="color: var(--gray);">Email cannot be changed here</small>
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-building"></i> Department</label>
                                    <select id="editDepartment" class="form-control">
                                        <option value="">Select Department</option>
                                        <option value="Artificial Intelligence" <?php echo $profile['department'] === 'Artificial Intelligence' ? 'selected' : ''; ?>>Artificial Intelligence</option>
                                        <option value="Arts" <?php echo $profile['department'] === 'Arts' ? 'selected' : ''; ?>>Arts</option>
                                        <option value="Business Administration" <?php echo $profile['department'] === 'Business Administration' ? 'selected' : ''; ?>>Business Administration</option>
                                        <option value="Computer Engineering" <?php echo $profile['department'] === 'Computer Engineering' ? 'selected' : ''; ?>>Computer Engineering</option>
                                        <option value="Computer Science" <?php echo $profile['department'] === 'Computer Science' ? 'selected' : ''; ?>>Computer Science</option>
                                        <option value="Cyber Security" <?php echo $profile['department'] === 'Cyber Security' ? 'selected' : ''; ?>>Cyber Security</option>
                                        <option value="Data Science" <?php echo $profile['department'] === 'Data Science' ? 'selected' : ''; ?>>Data Science</option>
                                        <option value="Engineering" <?php echo $profile['department'] === 'Engineering' ? 'selected' : ''; ?>>Engineering</option>
                                        <option value="Information Systems" <?php echo $profile['department'] === 'Information Systems' ? 'selected' : ''; ?>>Information Systems</option>
                                        <option value="Information Technology" <?php echo $profile['department'] === 'Information Technology' ? 'selected' : ''; ?>>Information Technology</option>
                                        <option value="Science" <?php echo $profile['department'] === 'Science' ? 'selected' : ''; ?>>Science</option>
                                        <option value="Software Engineering" <?php echo $profile['department'] === 'Software Engineering' ? 'selected' : ''; ?>>Software Engineering</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-layer-group"></i> Level</label>
                                    <select id="editLevel" class="form-control">
                                        <option value="">Select Level</option>
                                        <option value="1" <?php echo $profile['level'] == 1 ? 'selected' : ''; ?>>Level 1</option>
                                        <option value="2" <?php echo $profile['level'] == 2 ? 'selected' : ''; ?>>Level 2</option>
                                        <option value="3" <?php echo $profile['level'] == 3 ? 'selected' : ''; ?>>Level 3</option>
                                        <option value="4" <?php echo $profile['level'] == 4 ? 'selected' : ''; ?>>Level 4</option>
                                        <option value="5" <?php echo $profile['level'] == 5 ? 'selected' : ''; ?>>Level 5</option>
                                    </select>
                                </div>
                            </div>
                            <div style="display: flex; gap: 12px; margin-top: 24px; justify-content: flex-end;">
                                <button type="button" class="btn" style="background: #e2e8f0;" onclick="toggleEdit()">Cancel</button>
                                <button type="submit" class="btn btn-primary" id="saveBtn"><i class="fas fa-save"></i> Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Account Settings Card -->
                <div style="display: flex; flex-direction: column; gap: 28px;">
                    <div class="section-card">
                        <div class="section-header">
                            <h3><i class="fas fa-lock"></i> Change Password</h3>
                        </div>
                        <form id="passwordForm" onsubmit="event.preventDefault(); changePassword();">
                            <div class="form-group">
                                <label><i class="fas fa-lock"></i> Current Password</label>
                                <input type="password" id="currentPassword" class="form-control" required minlength="6">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-key"></i> New Password</label>
                                <input type="password" id="newPassword" class="form-control" required minlength="6">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-key"></i> Confirm New Password</label>
                                <input type="password" id="confirmPassword" class="form-control" required minlength="6">
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-save"></i> Update Password</button>
                        </form>
                    </div>

                    <div class="section-card">
                        <div class="section-header">
                            <h3><i class="fas fa-shield-alt"></i> Account Security</h3>
                        </div>
                        <div style="display: grid; gap: 12px;">
                            <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--light); border-radius: var(--radius);">
                                <i class="fas fa-check-circle" style="color: var(--success); font-size: 20px;"></i>
                                <div>
                                    <div style="font-weight: 600;">Role</div>
                                    <small style="color: var(--gray);">Student Account</small>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--light); border-radius: var(--radius);">
                                <i class="fas fa-check-circle" style="color: var(--success); font-size: 20px;"></i>
                                <div>
                                    <div style="font-weight: 600;">Status</div>
                                    <small style="color: var(--gray);">Active Account</small>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--light); border-radius: var(--radius);">
                                <i class="fas fa-info-circle" style="color: var(--primary); font-size: 20px;"></i>
                                <div>
                                    <div style="font-weight: 600;">Student ID</div>
                                    <small style="color: var(--gray);"><?php echo $profile['student_id']; ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/student.js"></script>
    <script>
        let editing = false;

        function toggleEdit() {
            editing = !editing;
            document.getElementById('profileView').style.display = editing ? 'none' : 'block';
            document.getElementById('profileEdit').style.display = editing ? 'block' : 'none';
            document.getElementById('editBtn').innerHTML = editing ? '<i class="fas fa-times"></i> Cancel' : '<i class="fas fa-pen"></i> Edit';
        }

        async function saveProfile() {
            const data = {
                full_name: document.getElementById('editFullName').value.trim(),
                department: document.getElementById('editDepartment').value,
                level: document.getElementById('editLevel').value ? parseInt(document.getElementById('editLevel').value) : null
            };

            if (!data.full_name || data.full_name.length < 3) {
                showToast('Name must be at least 3 characters', 'error');
                return;
            }

            const btn = document.getElementById('saveBtn');
            btn.disabled = true;
            btn.innerHTML = '<div class="loading-spinner" style="width:20px;height:20px;border-width:2px;margin:0;"></div>';

            try {
                const response = await fetch('../../api/student/profile.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.success) {
                    showToast('Profile updated successfully!', 'success');
                    document.getElementById('displayDepartment').textContent = data.department || 'Not set';
                    document.getElementById('displayLevel').textContent = data.level || 'Not set';
                    toggleEdit();
                } else {
                    showToast(result.message || 'Failed to update profile', 'error');
                }
            } catch (err) {
                showToast('Network error. Please try again.', 'error');
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
        }

        async function changePassword() {
            const current = document.getElementById('currentPassword').value;
            const newPass = document.getElementById('newPassword').value;
            const confirm = document.getElementById('confirmPassword').value;

            if (newPass !== confirm) {
                showToast('New passwords do not match', 'error');
                return;
            }
            if (newPass.length < 6) {
                showToast('Password must be at least 6 characters', 'error');
                return;
            }

            try {
                const response = await fetch('../../api/auth/change-password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ current_password: current, new_password: newPass })
                });
                const result = await response.json();

                if (result.success) {
                    showToast('Password changed successfully!', 'success');
                    document.getElementById('passwordForm').reset();
                } else {
                    showToast(result.message || 'Failed to change password', 'error');
                }
            } catch (err) {
                showToast('Network error. Please try again.', 'error');
            }
        }
    </script>
</body>
</html>
