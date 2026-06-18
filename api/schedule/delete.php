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

if (empty($data['schedule_id'])) {
    jsonResponse([], false, 'Schedule ID is required', 400);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Fetch schedule details before deleting (for notification)
    $stmt = $db->prepare("SELECT s.course_id, s.day_of_week, s.start_time, s.end_time, s.room_number, c.course_code FROM schedules s JOIN courses c ON s.course_id = c.course_id WHERE s.schedule_id = :sid");
    $stmt->execute([':sid' => (int)$data['schedule_id']]);
    $schedule = $stmt->fetch();

    if (!$schedule) {
        jsonResponse([], false, 'Schedule not found', 404);
    }

    $stmt = $db->prepare('DELETE FROM schedules WHERE schedule_id = :schedule_id');
    $stmt->execute([':schedule_id' => (int)$data['schedule_id']]);

    notifyEnrolledStudents($db, $schedule['course_id'], "Schedule removed: {$schedule['day_of_week']} {$schedule['start_time']}-{$schedule['end_time']} for {$schedule['course_code']}", 'danger');

    jsonResponse([], true, 'Schedule deleted');
} catch (Exception $e) {
    jsonResponse([], false, 'Server error: ' . $e->getMessage(), 500);
}
