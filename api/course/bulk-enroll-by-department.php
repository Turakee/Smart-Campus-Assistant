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

if (empty($data['department'])) {
    jsonResponse([], false, 'Department is required', 400);
}

$department = trim($data['department']);
$database = new Database();
$db = $database->getConnection();

try {
    // Get all active students in this department
    $stmt = $db->prepare("SELECT s.student_id FROM students s JOIN users u ON s.user_id = u.user_id WHERE s.department = :dept AND u.is_active = 1");
    $stmt->execute([':dept' => $department]);
    $students = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($students)) {
        jsonResponse([], false, "No students found in department: $department", 404);
    }

    // Get courses in the same department (match by department name)
    $stmt = $db->prepare(
        "SELECT c.course_id FROM courses c 
         JOIN departments d ON c.department_id = d.department_id 
         WHERE d.department_name = :dept"
    );
    $stmt->execute([':dept' => $department]);
    $courses = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($courses)) {
        jsonResponse([], false, "No courses found for department: $department", 404);
    }

    // For each student-course pair, insert only if not already enrolled
    $checkStmt = $db->prepare("SELECT 1 FROM student_courses WHERE student_id = :sid AND course_id = :cid");
    $insertStmt = $db->prepare("INSERT INTO student_courses (student_id, course_id, enrolled_at) VALUES (:sid, :cid, NOW())");
    $created = 0;
    $skipped = 0;

    foreach ($students as $sid) {
        foreach ($courses as $cid) {
            $checkStmt->execute([':sid' => $sid, ':cid' => $cid]);
            if ($checkStmt->fetch()) {
                $skipped++;
            } else {
                $insertStmt->execute([':sid' => $sid, ':cid' => $cid]);
                $created++;
            }
        }
    }

    jsonResponse([
        'department' => $department,
        'students_count' => count($students),
        'courses_count' => count($courses),
        'enrollments_created' => $created,
        'enrollments_skipped' => $skipped
    ], true, "Enrolled $created students in $department courses");
} catch(Exception $e) {
    jsonResponse([], false, 'Server error: ' . $e->getMessage(), 500);
}
