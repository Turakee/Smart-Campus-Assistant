<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireAuth();

$database = new Database();
$db = $database->getConnection();

try {
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    $stats = [
        'courses' => 0, 
        'attendance' => 0, 
        'bookings_pending' => 0,
        'bookings_approved' => 0,
        'bookings_rejected' => 0,
        'alerts' => 0, 
        'enrolled_courses' => []
    ];
    
    if ($role === 'student') {
        // Get Student ID
        $stmt = $db->prepare("SELECT student_id FROM students WHERE user_id = :id");
        $stmt->execute([':id' => $user_id]);
        $student = $stmt->fetch();
        
        if ($student) {
            $sid = $student['student_id'];
            
            // Count and get ENROLLED Courses
            $stmt = $db->prepare("SELECT c.course_id, c.course_code, c.course_name, c.lecturer_name 
                                   FROM student_courses sc 
                                   JOIN courses c ON c.course_id = sc.course_id 
                                   WHERE sc.student_id = :sid 
                                   ORDER BY c.course_name");
            $stmt->execute([':sid' => $sid]);
            $enrolledCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stats['courses'] = count($enrolledCourses);
            $stats['enrolled_courses'] = $enrolledCourses;
            
            // Calc Attendance %
            $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present, SUM(CASE WHEN status='excused' THEN 1 ELSE 0 END) as excused FROM attendance WHERE student_id = :sid");
            $stmt->execute([':sid' => $sid]);
            $att = $stmt->fetch();
            $stats['attendance'] = $att['total'] > 0 ? round((($att['present'] + $att['excused']) / $att['total']) * 100) : 0;
            
            // Booking Stats
            $stmt = $db->prepare("SELECT status, COUNT(*) as count FROM bookings WHERE student_id = :sid GROUP BY status");
            $stmt->execute([':sid' => $sid]);
            $bookingStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($bookingStats as $bs) {
                if ($bs['status'] === 'pending') $stats['bookings_pending'] = (int)$bs['count'];
                if ($bs['status'] === 'approved') $stats['bookings_approved'] = (int)$bs['count'];
                if ($bs['status'] === 'rejected') $stats['bookings_rejected'] = (int)$bs['count'];
            }
            $stats['bookings_pending'] = (int)$stats['bookings_pending'];
            
            // AI Alerts (from log)
            $stmt = $db->prepare("SELECT COUNT(*) FROM ai_analytics_log WHERE student_id = :sid AND risk_level = 'high'");
            $stmt->execute([':sid' => $sid]);
            $stats['alerts'] = $stmt->fetchColumn();
        }
    }
    
    jsonResponse($stats, true, 'Stats retrieved');
    
} catch(Exception $e) {
    jsonResponse([], false, 'Server error', 500);
}
?>