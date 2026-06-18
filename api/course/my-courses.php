<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireAuth();

if ($_SESSION['role'] !== 'student') {
    jsonResponse([], false, 'Only for students', 403);
}

$database = new Database();
$db = $database->getConnection();

try {
    $user_id = $_SESSION['user_id'];
    
    // Get Student ID
    $stmt = $db->prepare("SELECT student_id FROM students WHERE user_id = :id");
    $stmt->execute([':id' => $user_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        jsonResponse([], false, 'Student record not found', 404);
    }
    
    $sid = $student['student_id'];
    
    // Get enrolled courses with schedule info
    $stmt = $db->prepare("SELECT c.course_id, c.course_code, c.course_name, c.lecturer_name, c.credit_hours,
                                  c.department_id, d.department_name,
                                  sc.enrolled_at
                           FROM student_courses sc 
                           JOIN courses c ON c.course_id = sc.course_id 
                           LEFT JOIN departments d ON c.department_id = d.department_id
                           WHERE sc.student_id = :sid 
                           ORDER BY c.course_name");
    $stmt->execute([':sid' => $sid]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get schedules for enrolled courses
    if (!empty($courses)) {
        $courseIds = array_column($courses, 'course_id');
        $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
        
        $stmt2 = $db->prepare("SELECT schedule_id, course_id, day_of_week, start_time, end_time, room_number 
                                FROM schedules 
                                WHERE course_id IN ($placeholders)
                                ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), start_time");
        $stmt2->execute($courseIds);
        $schedules = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        // Group schedules by course
        $schedulesByCourse = [];
        foreach ($schedules as $schedule) {
            $schedulesByCourse[$schedule['course_id']][] = $schedule;
        }
        
        // Attach schedules to courses
        foreach ($courses as &$course) {
            $course['schedules'] = $schedulesByCourse[$course['course_id']] ?? [];
        }
    }
    
    jsonResponse($courses, true, 'Enrolled courses retrieved');
    
} catch(Exception $e) {
    jsonResponse([], false, 'Server error: ' . $e->getMessage(), 500);
}
?>
