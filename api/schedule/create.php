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

$required = ['course_id', 'day_of_week', 'start_time', 'end_time'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        jsonResponse([], false, ucfirst(str_replace('_', ' ', $field)) . ' is required', 400);
    }
}

try {
    $database = new Database();
    $db = $database->getConnection();
    require_once '../../config/Utilities.php';

    $day = sanitizeInput($data['day_of_week']);
    $start = sanitizeInput($data['start_time']);
    $end = sanitizeInput($data['end_time']);
    $courseId = (int)$data['course_id'];
    $room = isset($data['room_number']) ? sanitizeInput($data['room_number']) : null;

    // Check for exact duplicate schedule
    $sql = "SELECT schedule_id FROM schedules WHERE course_id = :cid AND day_of_week = :day AND start_time = :start AND end_time = :end";
    $params = [':cid' => $courseId, ':day' => $day, ':start' => $start, ':end' => $end];
    if ($room) {
        $sql .= " AND room_number = :room";
        $params[':room'] = $room;
    } else {
        $sql .= " AND room_number IS NULL";
    }
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    if ($stmt->fetch()) {
        jsonResponse([], false, 'An identical schedule already exists for this course', 409);
    }

    // Check for time conflicts: same course + same day + overlapping time
    $stmt = $db->prepare("SELECT s.schedule_id, s.start_time, s.end_time, c.course_name, c.course_code FROM schedules s JOIN courses c ON s.course_id = c.course_id WHERE s.course_id = :cid AND s.day_of_week = :day");
    $stmt->execute([':cid' => $courseId, ':day' => $day]);
    while ($row = $stmt->fetch()) {
        if (Utilities::timeSlotOverlaps($start, $end, $row['start_time'], $row['end_time'])) {
            jsonResponse([], false, "Time conflict: \"{$row['course_code']} {$row['course_name']}\" already has a schedule {$row['start_time']}-{$row['end_time']} on {$day}", 409);
        }
    }

    // Check for room conflicts: same room + same day + overlapping time
    if ($room) {
        $stmt = $db->prepare("SELECT s.schedule_id, s.start_time, s.end_time, c.course_name, c.course_code FROM schedules s JOIN courses c ON s.course_id = c.course_id WHERE s.room_number = :room AND s.day_of_week = :day AND s.room_number IS NOT NULL");
        $stmt->execute([':room' => $room, ':day' => $day]);
        while ($row = $stmt->fetch()) {
            if (Utilities::timeSlotOverlaps($start, $end, $row['start_time'], $row['end_time'])) {
                jsonResponse([], false, "Room conflict: \"{$row['course_code']} {$row['course_name']}\" already booked this room {$row['start_time']}-{$row['end_time']} on {$day}", 409);
            }
        }
    }

    // Check for lecturer time conflict: same lecturer + same day + overlapping time across different courses
    $stmt = $db->prepare("SELECT lecturer_name FROM courses WHERE course_id = :cid");
    $stmt->execute([':cid' => $courseId]);
    $course = $stmt->fetch();
    if ($course && !empty($course['lecturer_name'])) {
        $lecturer = $course['lecturer_name'];
        $stmt = $db->prepare("SELECT s.schedule_id, s.start_time, s.end_time, c.course_name, c.course_code FROM schedules s JOIN courses c ON s.course_id = c.course_id WHERE c.lecturer_name = :lecturer AND s.day_of_week = :day AND s.course_id != :cid");
        $stmt->execute([':lecturer' => $lecturer, ':day' => $day, ':cid' => $courseId]);
        while ($row = $stmt->fetch()) {
            if (Utilities::timeSlotOverlaps($start, $end, $row['start_time'], $row['end_time'])) {
                jsonResponse([], false, "Lecturer conflict: {$lecturer} is already teaching \"{$row['course_code']} {$row['course_name']}\" {$row['start_time']}-{$row['end_time']} on {$day}", 409);
            }
        }
    }

    $stmt = $db->prepare("INSERT INTO schedules (course_id, day_of_week, start_time, end_time, room_number) VALUES (:course_id, :day_of_week, :start_time, :end_time, :room_number)");
    $stmt->execute([
        ':course_id' => $courseId,
        ':day_of_week' => $day,
        ':start_time' => $start,
        ':end_time' => $end,
        ':room_number' => $room,
    ]);

    $scheduleId = $db->lastInsertId();
    notifyEnrolledStudents($db, $courseId, "Schedule added: {$day} {$start}-{$end}" . ($room ? " in {$room}" : ''), 'info');

    jsonResponse(['schedule_id' => $scheduleId], true, 'Schedule created', 201);
} catch (Exception $e) {
    jsonResponse([], false, 'Server error: ' . $e->getMessage(), 500);
}
