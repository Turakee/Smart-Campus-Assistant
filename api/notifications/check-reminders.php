<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireAuth();

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$today = date('l');
$notificationsCreated = 0;

try {
    if ($role === 'student') {
        $stmt = $db->prepare("SELECT student_id FROM students WHERE user_id = :uid");
        $stmt->execute([':uid' => $user_id]);
        $sid = $stmt->fetchColumn();
        if (!$sid) { jsonResponse(['count' => 0], true, 'No student profile'); exit; }

        // Check today's upcoming schedules within the next 2 hours
        $now = date('H:i');
        $twoHoursLater = date('H:i', strtotime('+2 hours'));
        $stmt = $db->prepare("
            SELECT c.course_name, c.course_code, s.start_time, s.end_time, s.room_number
            FROM schedules s
            JOIN courses c ON s.course_id = c.course_id
            JOIN student_courses sc ON sc.course_id = s.course_id AND sc.student_id = :sid
            WHERE s.day_of_week = :today
              AND s.start_time >= :now
              AND s.start_time <= :later
              AND NOT EXISTS (
                SELECT 1 FROM notifications n
                WHERE n.user_id = :uid2
                  AND n.type = 'info'
                  AND n.message LIKE CONCAT('%', c.course_code, '%')
                  AND n.created_at >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
              )
            ORDER BY s.start_time
        ");
        $stmt->execute([':sid' => $sid, ':today' => $today, ':now' => $now, ':later' => $twoHoursLater, ':uid2' => $user_id]);

        while ($row = $stmt->fetch()) {
            $timeUntil = '';
            $startTs = strtotime($row['start_time']);
            $nowTs = strtotime($now);
            $diffMin = round(($startTs - $nowTs) / 60);
            if ($diffMin <= 5) $timeUntil = 'starting now';
            elseif ($diffMin <= 15) $timeUntil = 'in 15 minutes';
            elseif ($diffMin <= 30) $timeUntil = 'in 30 minutes';
            elseif ($diffMin <= 60) $timeUntil = 'in 1 hour';
            else $timeUntil = "in {$diffMin} minutes";

            $msg = "📚 {$row['course_code']} {$row['course_name']} {$timeUntil}";
            if ($row['room_number']) $msg .= " — {$row['room_number']}";

            $stmtN = $db->prepare("INSERT INTO notifications (user_id, message, type, is_read) VALUES (:uid, :msg, 'info', 0)");
            $stmtN->execute([':uid' => $user_id, ':msg' => $msg]);
            $notificationsCreated++;
        }
    }

    jsonResponse(['count' => $notificationsCreated], true, $notificationsCreated > 0 ? "{$notificationsCreated} reminder(s) created" : 'No reminders needed');
} catch (Exception $e) {
    jsonResponse(['count' => 0], false, 'Error: ' . $e->getMessage(), 500);
}
