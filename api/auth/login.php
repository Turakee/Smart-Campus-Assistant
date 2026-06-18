<?php
/**
 * Login API Endpoint
 * Section 5.3.3: Authentication Implementation
 */

include_once '../../config/config.php';
include_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([], false, 'Method not allowed', 405);
}

$database = new Database();
$db = $database->getConnection();

$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Support both JSON and traditional form posts
if (!is_array($data)) {
    $data = $_POST;
}

if (empty($data['username']) || empty($data['password'])) {
    jsonResponse([], false, 'Username and password required', 400);
}

try {
    // Secure Query with Prepared Statements
    $query = "SELECT u.user_id, u.username, u.email, u.password_hash, u.role, 
                     s.full_name as student_name, s.department, s.level,
                     a.full_name as admin_name
              FROM users u
              LEFT JOIN students s ON u.user_id = s.user_id
              LEFT JOIN administrators a ON u.user_id = a.user_id
              WHERE u.username = :username AND u.is_active = 1
              LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $data['username']);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        jsonResponse([], false, 'Invalid credentials', 401);
    }
    
    $user = $stmt->fetch();
    
    // Verify Password Hash
    if (!password_verify($data['password'], $user['password_hash'])) {
        jsonResponse([], false, 'Invalid credentials', 401);
    }
    
    // Update Last Login
    $update = $db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = :id");
    $update->execute([':id' => $user['user_id']]);
    
    // Normalize role values and set session
    $role = strtolower(trim($user['role']));
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $role;
    $_SESSION['name'] = $user['student_name'] ?? $user['admin_name'] ?? 'User';
    
    // Set system admin flag
    $_SESSION['is_system_admin'] = ($role === 'system_admin') || !empty($user['is_system_admin']);
    
  
    // Determine Dashboard Redirect (absolute path from document root)
    $basePath = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
    $redirect = $basePath . '/public/student/dashboard.php';
    if ($role === 'system_admin') {
        $redirect = $basePath . '/public/admin/system-admin.php';
    } elseif ($role === 'administrator') {
        $redirect = $basePath . '/public/admin/dashboard.php';
    }
    
    $name = $_SESSION['name'];
    $department = $user['department'] ?? null;
    $level = $user['level'] ?? null;

    jsonResponse([
        'token' => (string)$user['user_id'],
        'redirect' => $redirect,
        'user' => [
            'user_id' => (int)$user['user_id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $role,
            'name' => $name,
            'department' => $department,
            'level' => $level ? (int)$level : null,
        ]
    ], true, 'Login successful');
    
} catch(Exception $e) {
    jsonResponse([], false, 'Server error', 500);
}
?>