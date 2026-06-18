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

if (empty($data['student_id']) || empty($data['course_id'])) {
    jsonResponse([], false, 'Student ID and Course ID are required', 400);
}

$student_id = (int)$data['student_id'];
$course_id = (int)$data['course_id'];

try {
    $check = $db->prepare("SELECT student_course_id FROM student_courses WHERE student_id = :sid AND course_id = :cid");
    $check->execute([':sid' => $student_id, ':cid' => $course_id]);
    if ($check->fetch()) {
        jsonResponse([], false, 'Student is already enrolled in this course', 400);
    }
    
    $studentCheck = $db->prepare("SELECT student_id FROM students WHERE student_id = :sid");
    $studentCheck->execute([':sid' => $student_id]);
    if (!$studentCheck->fetch()) {
        jsonResponse([], false, 'Student not found', 404);
    }
    
    $courseCheck = $db->prepare("SELECT course_id FROM courses WHERE course_id = :cid");
    $courseCheck->execute([':cid' => $course_id]);
    if (!$courseCheck->fetch()) {
        jsonResponse([], false, 'Course not found', 404);
    }
    
    $stmt = $db->prepare("INSERT INTO student_courses (student_id, course_id, enrolled_at) VALUES (:sid, :cid, NOW())");
    $stmt->execute([
        ':sid' => $student_id,
        ':cid' => $course_id
    ]);

    jsonResponse(['enrollment_id' => $db->lastInsertId()], true, 'Student enrolled successfully', 201);
} catch(Exception $e) {
    jsonResponse([], false, 'Server error: ' . $e->getMessage(), 500);
}
?>
