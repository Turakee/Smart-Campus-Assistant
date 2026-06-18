<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireAuth();
requireRole(['administrator', 'system_admin']);

$database = new Database();
$db = $database->getConnection();

try {
    // Total students (active only)
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM students s JOIN users u ON s.user_id = u.user_id WHERE u.is_active = 1");
    $stmt->execute();
    $totalStudents = (int) $stmt->fetchColumn();

    // Total courses
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM courses");
    $stmt->execute();
    $totalCourses = (int) $stmt->fetchColumn();

    // Total resources
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM resources");
    $stmt->execute();
    $totalResources = (int) $stmt->fetchColumn();

    // Pending bookings
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'");
    $stmt->execute();
    $pendingBookings = (int) $stmt->fetchColumn();

    // At-risk students (based on AI analytics logs)
    $stmt = $db->prepare("SELECT COUNT(DISTINCT student_id) as count FROM ai_analytics_log WHERE risk_level = 'high'");
    $stmt->execute();
    $atRiskStudents = (int) $stmt->fetchColumn();

    // Average attendance across all students
    $stmt = $db->prepare("SELECT
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
        COUNT(*) as total
        FROM attendance");
    $stmt->execute();
    $attendanceRow = $stmt->fetch();

    $avgAttendance = 0;
    if ($attendanceRow && $attendanceRow['total'] > 0) {
        $avgAttendance = round(($attendanceRow['present'] / $attendanceRow['total']) * 100);
    }

    jsonResponse([
        'totalStudents' => $totalStudents,
        'totalCourses' => $totalCourses,
        'totalResources' => $totalResources,
        'pendingBookings' => $pendingBookings,
        'atRiskStudents' => $atRiskStudents,
        'avgAttendance' => $avgAttendance
    ], true, 'Admin stats retrieved');

} catch(Exception $e) {
    jsonResponse([], false, 'Server error', 500);
}
?>