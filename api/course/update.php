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

if (empty($data['course_id']) || empty($data['course_code']) || empty($data['course_name'])) {
    jsonResponse([], false, 'Course ID, code and name are required', 400);
}

try {
    $courseId = (int)$data['course_id'];
    $code = strtoupper(trim($data['course_code']));
    $name = trim($data['course_name']);

    $stmt = $db->prepare("SELECT course_id FROM courses WHERE course_code = :code AND course_id != :id");
    $stmt->execute([':code' => $code, ':id' => $courseId]);
    if ($stmt->fetch()) {
        jsonResponse([], false, "Course code \"{$code}\" is already used by another course", 409);
    }

    $stmt = $db->prepare("SELECT course_id FROM courses WHERE course_name = :name AND course_id != :id");
    $stmt->execute([':name' => $name, ':id' => $courseId]);
    if ($stmt->fetch()) {
        jsonResponse([], false, "Course name \"{$name}\" is already used by another course", 409);
    }

    $deptId = isset($data['department_id']) ? (int)$data['department_id'] : null;
    if ($deptId === null && !empty($data['department_name'])) {
        $deptStmt = $db->prepare("SELECT department_id FROM departments WHERE department_name = :name");
        $deptStmt->execute([':name' => $data['department_name']]);
        $deptRow = $deptStmt->fetch();
        $deptId = $deptRow ? (int)$deptRow['department_id'] : null;
    }

    $stmt = $db->prepare("UPDATE courses SET course_code = :code, course_name = :name, credit_hours = :credits, lecturer_name = :lecturer, department_id = :dept_id WHERE course_id = :id");
    $stmt->execute([
        ':id' => $courseId,
        ':code' => $code,
        ':name' => $name,
        ':credits' => isset($data['credit_hours']) ? (int)$data['credit_hours'] : null,
        ':lecturer' => isset($data['lecturer_name']) ? trim($data['lecturer_name']) : null,
        ':dept_id' => $deptId,
    ]);

    if ($stmt->rowCount() === 0) {
        jsonResponse([], false, 'Course not found or no changes made', 404);
    }

    jsonResponse([], true, 'Course updated successfully');
} catch (Exception $e) {
    jsonResponse([], false, 'Server error: ' . $e->getMessage(), 500);
}
