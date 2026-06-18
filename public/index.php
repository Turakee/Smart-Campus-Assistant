<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smart Campus Assistant</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="assets/css/auth.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h1>Smart Campus Assistant</h1>
                <p>Smart Campus Management System</p>
            </div>

            <div class="auth-tabs">
                <button class="tab-btn active" onclick="switchTab('login')">Sign In</button>
                <button class="tab-btn" onclick="switchTab('register')">Create Account</button>
            </div>

            <!-- Login Form -->
            <form id="loginForm" class="auth-form active">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="loginUsername" required placeholder="Enter your username" autocomplete="username">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="loginPassword" required placeholder="Enter your password" autocomplete="current-password">
                </div>
                <button type="submit" class="btn-primary">
                    <span class="btn-text"><i class="fas fa-right-to-bracket"></i> Sign In</span>
                    <span class="btn-loader" style="display:none;"><i class="fas fa-spinner fa-spin"></i></span>
                </button>
                <div id="loginMessage" class="message-box"></div>
            </form>

            <!-- Register Form -->
            <form id="registerForm" class="auth-form">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" id="regName" required placeholder="Enter your full name">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Username</label>
                    <input type="text" id="regUsername" required placeholder="Choose a username" autocomplete="username">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="regEmail" required placeholder="Enter your email" autocomplete="email">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="regPassword" required placeholder="Create a strong password" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-users"></i> Role</label>
                    <select id="regRole" required onchange="toggleStudentFields()">
                        <option value="student">Student</option>
                        <option value="administrator">Administrator</option>
                    </select>
                </div>
                    <div class="form-group" id="deptGroup">
                        <label><i class="fas fa-building"></i> Department</label>
                        <select id="regDept">
                            <option value="">Select Department</option>
                            <option value="Artificial Intelligence">Artificial Intelligence</option>
                            <option value="Arts">Arts</option>
                            <option value="Business Administration">Business Administration</option>
                            <option value="Computer Engineering">Computer Engineering</option>
                            <option value="Computer Science">Computer Science</option>
                            <option value="Cyber Security">Cyber Security</option>
                            <option value="Data Science">Data Science</option>
                            <option value="Engineering">Engineering</option>
                            <option value="Information Systems">Information Systems</option>
                            <option value="Information Technology">Information Technology</option>
                            <option value="Science">Science</option>
                            <option value="Software Engineering">Software Engineering</option>
                        </select>
                    </div>
                    <div class="form-group" id="levelGroup">
                        <label><i class="fas fa-graduation-cap"></i> Level</label>
                        <select id="regLevel">
                            <option value="100">Level 1</option>
                            <option value="200">Level 2</option>
                            <option value="300">Level 3</option>
                            <option value="400">Level 4</option>
                            <option value="500">Level 5</option>
                        </select>
                    </div>
                <button type="submit" class="btn-primary">
                    <span class="btn-text"><i class="fas fa-user-plus"></i> Create Account</span>
                    <span class="btn-loader" style="display:none;"><i class="fas fa-spinner fa-spin"></i></span>
                </button>
                <div id="regMessage" class="message-box"></div>
            </form>

            <p class="footer-text">
                Powered by <strong>Smart Campus Assistant</strong>
            </p>
        </div>
    </div>

    <script>window.API_BASE = '<?php
        $p = $_SERVER['PHP_SELF'] ?? $_SERVER['SCRIPT_NAME'] ?? '/';
        $d = dirname(dirname($p));
        if (DIRECTORY_SEPARATOR === '\\') $d = str_replace('\\', '/', $d);
        echo rtrim($d, '/') . '/';
    ?>';</script>
    <script src="assets/js/auth.js?v=<?php echo filemtime('assets/js/auth.js'); ?>"></script>
</body>
</html>
