<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse([], false, 'Invalid request method', 405);
}

$database = new Database();
$db = $database->getConnection();

try {
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    // If student, get only their enrolled schedule
    if ($role === 'student') {
        $stmt1 = $db->prepare("SELECT student_id FROM students WHERE user_id = :uid");
        $stmt1->execute([':uid' => $user_id]);
        $student = $stmt1->fetch();
        
        if (!$student) {
            jsonResponse([], true, 'Student not found', 200);
        }
        
        $student_id = $student['student_id'];
        
        $query = "SELECT sc.schedule_id, sc.course_id, sc.day_of_week, sc.start_time, sc.end_time, sc.room_number,
                         c.course_name, c.course_code, c.department_id,
                         d.department_name, d.department_code
                  FROM schedules sc
                  INNER JOIN courses c ON sc.course_id = c.course_id 
                  LEFT JOIN departments d ON c.department_id = d.department_id
                  INNER JOIN student_courses en ON en.course_id = sc.course_id AND en.student_id = :sid
                  ORDER BY FIELD(sc.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), sc.start_time";
        
        $stmt = $db->prepare($query);
        $stmt->execute([':sid' => $student_id]);
    } else {
        $query = "SELECT s.schedule_id, s.course_id, s.day_of_week, s.start_time, s.end_time, s.room_number,
                         c.course_name, c.course_code, c.department_id,
                         d.department_name, d.department_code
                  FROM schedules s 
                  JOIN courses c ON s.course_id = c.course_id 
                  LEFT JOIN departments d ON c.department_id = d.department_id";

        $conditions = [];
        $params = [];

        // Optional department_id filter
        $deptFilter = $_GET['department_id'] ?? $_GET['dept'] ?? null;
        if ($deptFilter) {
            $conditions[] = "c.department_id = :dept";
            $params[':dept'] = (int)$deptFilter;
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(' AND ', $conditions);
        }

        $query .= " ORDER BY FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), s.start_time";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
    }

    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonResponse($schedules, true, 'Schedule retrieved');
    
} catch(Exception $e) {
    jsonResponse([], false, 'Server error: ' . $e->getMessage(), 500);
}
?>