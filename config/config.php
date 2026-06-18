<?php
/**
 * CampusEase Core Configuration
 * Section 5.3.7: Security Implementation
 */

// Error Reporting (Disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

/**
 * Load .env file if it exists
 */
function loadEnv() {
    $envFile = __DIR__ . '/../.env';
    if (!file_exists($envFile)) return;
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) continue;
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        putenv("$key=$value");
    }
}
loadEnv();

// Database Constants (from env or defaults)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'smart_Campus_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');

// Security Headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");

// CORS Headers for Mobile App
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed_origins = [
    'http://localhost:8080',
    'http://192.168.18.1:8080',
    'http://localhost',
    'http://127.0.0.1',
    'http://10.0.2.2:8080'
];
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
}
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// Handle preflight requests (guard for CLI as well)
$method = $_SERVER['REQUEST_METHOD'] ?? '';
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);
ini_set('session.cookie_path', '/smart_campus/');
ini_set('session.cookie_samesite', 'Lax');

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// System Constants
define('SITE_NAME', getenv('SITE_NAME') ?: 'Smart Campus Assistant');
define('BASE_URL', getenv('BASE_URL') ?: 'http://localhost:8080/smart_campus/');
define('API_URL', BASE_URL . 'api/');
define('TIMEZONE', getenv('TIMEZONE') ?: 'UTC');
date_default_timezone_set(TIMEZONE);

// Security Keys (Change in production)
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'your_super_secret_jwt_key_change_this');
define('ENCRYPTION_KEY', getenv('ENCRYPTION_KEY') ?: 'your_encryption_key_change_this');

// Response Helper Functions
function jsonResponse($data, $success = true, $message = 'OK', $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

function requireAuth() {
    // Support both session (web) and header-based (mobile) authentication
    if (isset($_SESSION['user_id'])) {
        return; // Session auth works for web
    }
    
    // Mobile app sends user_id in header
    $userId = $_SERVER['HTTP_X_USER_ID'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    
    // Remove 'Bearer ' prefix if present
    if (strpos($userId, 'Bearer ') === 0) {
        $userId = substr($userId, 7);
    }
    
    if (empty($userId)) {
        jsonResponse([], false, 'Unauthorized access', 401);
    }
    
    // Set session variables from user_id
    $_SESSION['user_id'] = (int)$userId;
    
    // Load user role from database
    require_once __DIR__ . '/database.php';
    $database = new Database();
    $db = $database->getConnection();
    try {
        $stmt = $db->prepare("SELECT u.role, 
            (SELECT full_name FROM students WHERE user_id = u.user_id LIMIT 1) as student_name,
            (SELECT full_name FROM administrators WHERE user_id = u.user_id LIMIT 1) as admin_name,
            u.is_system_admin
            FROM users u WHERE u.user_id = :uid AND u.is_active = 1");
        $stmt->execute([':uid' => $_SESSION['user_id']]);
        $user = $stmt->fetch();
    } catch (PDOException $e) {
        // Fallback in case the is_system_admin column does not exist
        $stmt = $db->prepare("SELECT u.role, 
            (SELECT full_name FROM students WHERE user_id = u.user_id LIMIT 1) as student_name,
            (SELECT full_name FROM administrators WHERE user_id = u.user_id LIMIT 1) as admin_name
            FROM users u WHERE u.user_id = :uid AND u.is_active = 1");
        $stmt->execute([':uid' => $_SESSION['user_id']]);
        $user = $stmt->fetch();
        $_SESSION['is_system_admin'] = false;
    }
    
    if (!$user) {
        jsonResponse([], false, 'Unauthorized access', 401);
    }
    // Store role and name for downstream access
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['student_name'] ?? $user['admin_name'] ?? 'User';
    // System Admin flag (if the column exists in users table)
    // This enables bypassing per-endpoint RBAC checks when true
    $_SESSION['is_system_admin'] = !empty($user['is_system_admin']) ? (bool)$user['is_system_admin'] : false;
}

function requireRole($allowedRoles) {
    requireAuth();
    // If user is System Admin, bypass per-endpoint role checks but keep auditing
    if (!empty($_SESSION['is_system_admin'])) {
        return;
    }
    $userRole = strtolower(trim($_SESSION['role'] ?? ''));
    $allowed = array_map('strtolower', $allowedRoles);
    if (!in_array($userRole, $allowed, true)) {
        jsonResponse([], false, 'Access denied: Insufficient permissions', 403);
    }
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Create a notification for a user in the database
 */
function createNotification($db, $userId, $message, $type = 'info') {
    $stmt = $db->prepare("INSERT INTO notifications (user_id, message, type, is_read, created_at) VALUES (:uid, :msg, :type, 0, NOW())");
    $stmt->execute([':uid' => $userId, ':msg' => $message, ':type' => $type]);
}

/**
 * Create notifications for all students enrolled in a course
 */
function notifyEnrolledStudents($db, $courseId, $message, $type = 'warning') {
    $stmt = $db->prepare("SELECT DISTINCT u.user_id FROM student_courses sc JOIN students s ON sc.student_id = s.student_id JOIN users u ON s.user_id = u.user_id WHERE sc.course_id = :cid AND u.is_active = 1");
    $stmt->execute([':cid' => $courseId]);
    while ($row = $stmt->fetch()) {
        createNotification($db, $row['user_id'], $message, $type);
    }
}

// Enrollment operations should be controlled by role-based checks.
?>
