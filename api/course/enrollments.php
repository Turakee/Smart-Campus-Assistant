<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireAuth();
requireRole(['administrator', 'system_admin']);

$database = new Database();
$db = $database->getConnection();

try {
    $query = "SELECT sc.student_id, sc.course_id, sc.enrolled_at,
                     s.full_name AS student_name, s.department AS student_department,
                     c.course_name, c.course_code, c.department_id,
                     d.department_name AS course_department
              FROM student_courses sc
              JOIN students s ON sc.student_id = s.student_id
              JOIN courses c ON sc.course_id = c.course_id
              LEFT JOIN departments d ON c.department_id = d.department_id
              ORDER BY sc.enrolled_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format enrolled_at dates
    foreach ($enrollments as &$enrollment) {
        if (isset($enrollment['enrolled_at'])) {
            $enrollment['enrolled_at'] = date('Y-m-d H:i:s', strtotime($enrollment['enrolled_at']));
        }
    }

    jsonResponse($enrollments, true, 'Enrollments retrieved');
} catch (Exception $e) {
    jsonResponse([], false, 'Server error: ' . $e->getMessage(), 500);
}
?>