<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireAuth();
requireRole(['administrator', 'system_admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([], false, 'Method not allowed', 405);
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['username']) || empty($data['email']) || empty($data['password']) || empty($data['full_name'])) {
    jsonResponse([], false, 'Full name, username, email, and password are required', 400);
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $db->beginTransaction();

    $check = $db->prepare("SELECT user_id FROM users WHERE username = :username OR email = :email");
    $check->execute([':username' => $data['username'], ':email' => $data['email']]);
    if ($check->rowCount() > 0) {
        throw new Exception('Username or email already exists');
    }

    $password_hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, role, is_active) VALUES (:username, :email, :password_hash, 'student', :active)");
    $stmt->execute([
        ':username' => sanitizeInput($data['username']),
        ':email' => sanitizeInput($data['email']),
        ':password_hash' => $password_hash,
        ':active' => isset($data['is_active']) ? (int)(bool)$data['is_active'] : 1
    ]);

    $user_id = $db->lastInsertId();

    $stmt = $db->prepare("INSERT INTO students (user_id, full_name, department, level, enrollment_year) VALUES (:uid, :name, :dept, :level, :year)");
    $stmt->execute([
        ':uid' => $user_id,
        ':name' => sanitizeInput($data['full_name']),
        ':dept' => !empty($data['department']) ? sanitizeInput($data['department']) : 'General',
        ':level' => !empty($data['level']) ? (int)$data['level'] : null,
        ':year' => !empty($data['enrollment_year']) ? (int)$data['enrollment_year'] : (int)date('Y')
    ]);

    $db->commit();

    jsonResponse(['student_id' => $db->lastInsertId(), 'user_id' => $user_id], true, 'Student created successfully', 201);
} catch(Exception $e) {
    $db->rollBack();
    jsonResponse([], false, $e->getMessage(), 400);
}
