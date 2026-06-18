<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireAuth();
requireRole(['administrator', 'system_admin']);

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([], false, 'Method not allowed', 405);
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['course_name']) || empty($data['course_code'])) {
    jsonResponse([], false, 'Course name and code are required', 400);
}

try {
    $code = strtoupper(trim($data['course_code']));
    $name = trim($data['course_name']);

    $stmt = $db->prepare("SELECT course_id FROM courses WHERE course_code = :code");
    $stmt->execute([':code' => $code]);
    if ($stmt->fetch()) {
        jsonResponse([], false, "Course code \"{$code}\" already exists", 409);
    }

    $stmt = $db->prepare("SELECT course_id FROM courses WHERE course_name = :name");
    $stmt->execute([':name' => $name]);
    if ($stmt->fetch()) {
        jsonResponse([], false, "Course name \"{$name}\" already exists", 409);
    }

    $deptId = isset($data['department_id']) ? (int)$data['department_id'] : null;
    if ($deptId === null && !empty($data['department_name'])) {
        $deptStmt = $db->prepare("SELECT department_id FROM departments WHERE department_name = :name");
        $deptStmt->execute([':name' => $data['department_name']]);
        $deptRow = $deptStmt->fetch();
        $deptId = $deptRow ? (int)$deptRow['department_id'] : null;
    }

    $stmt = $db->prepare("INSERT INTO courses (course_name, course_code, credit_hours, lecturer_name, department_id) VALUES (:name, :code, :credits, :lecturer, :dept_id)");
    $stmt->execute([
        ':name' => sanitizeInput($name),
        ':code' => sanitizeInput($code),
        ':credits' => isset($data['credit_hours']) ? (int)$data['credit_hours'] : null,
        ':lecturer' => isset($data['lecturer_name']) ? sanitizeInput($data['lecturer_name']) : null,
        ':dept_id' => $deptId
    ]);

    jsonResponse(['course_id' => $db->lastInsertId()], true, 'Course created successfully', 201);
} catch(Exception $e) {
    jsonResponse([], false, 'Server error: ' . $e->getMessage(), 500);
}
