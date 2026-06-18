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

$studentId = (int)$data['student_id'];
$courseId = (int)$data['course_id'];

try {
    $stmt = $db->prepare("DELETE FROM student_courses 
                          WHERE student_id = :sid AND course_id = :cid");
    $stmt->execute([
        ':sid' => $studentId,
        ':cid' => $courseId
    ]);

    if ($stmt->rowCount() === 0) {
        jsonResponse([], false, 'Enrollment not found', 404);
    }

    jsonResponse([], true, 'Unenrolled successfully');
} catch (Exception $e) {
    jsonResponse([], false, 'Server error: ' . $e->getMessage(), 500);
}
?>