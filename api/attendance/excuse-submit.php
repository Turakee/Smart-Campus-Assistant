<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireAuth();

if ($_SESSION['role'] !== 'student') {
    jsonResponse([], false, 'Only students can submit excuses', 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([], false, 'Method not allowed', 405);
}

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['attendance_id']) || empty($data['reason'])) {
    jsonResponse([], false, 'Attendance ID and reason are required', 400);
}

$reason = trim($data['reason']);
if (strlen($reason) < 10) {
    jsonResponse([], false, 'Please provide a detailed reason (at least 10 characters)', 400);
}

try {
    // Verify the attendance record belongs to this student
    $stmt = $db->prepare("SELECT a.attendance_id, a.student_id, a.status, a.date, c.course_name
                          FROM attendance a
                          JOIN courses c ON a.course_id = c.course_id
                          JOIN students s ON a.student_id = s.student_id
                          WHERE a.attendance_id = :aid AND s.user_id = :uid");
    $stmt->execute([
        ':aid' => (int)$data['attendance_id'],
        ':uid' => $_SESSION['user_id']
    ]);
    $record = $stmt->fetch();

    if (!$record) {
        jsonResponse([], false, 'Attendance record not found', 404);
    }

    if ($record['status'] === 'present') {
        jsonResponse([], false, 'Cannot submit excuse for a present record', 400);
    }

    if ($record['status'] === 'excused') {
        jsonResponse([], false, 'This record is already excused', 409);
    }

    // Directly update the attendance record to excused
    $update = $db->prepare("UPDATE attendance SET status = 'excused' WHERE attendance_id = :aid");
    $update->execute([':aid' => (int)$data['attendance_id']]);

    // Notify admin
    $adminStmt = $db->prepare("SELECT user_id FROM administrators LIMIT 1");
    $adminStmt->execute();
    $admin = $adminStmt->fetch();

    if ($admin) {
        $notif = $db->prepare("INSERT INTO notifications (user_id, message, type)
                               VALUES (:uid, :msg, 'warning')");
        $notif->execute([
            ':uid' => $admin['user_id'],
            ':msg' => 'Attendance excused for ' . $record['course_name'] . ' on ' . $record['date'] . ': ' . $reason
        ]);
    }

    jsonResponse([], true, 'Excuse submitted successfully. Attendance updated to excused.');

} catch (Exception $e) {
    jsonResponse([], false, 'Server error: ' . $e->getMessage(), 500);
}
