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
    require_once '../../config/Utilities.php';

    $scheduleId = (int)$data['schedule_id'];
    $fields = [];
    $params = [':schedule_id' => $scheduleId];

    $updatable = ['course_id', 'day_of_week', 'start_time', 'end_time', 'room_number'];
    foreach ($updatable as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = :$field";
            $params[":$field"] = $field === 'course_id' ? (int)$data[$field] : sanitizeInput($data[$field]);
        }
    }

    if (count($fields) === 0) {
        jsonResponse([], false, 'No fields to update', 400);
    }

    $newCourseId = isset($data['course_id']) ? (int)$data['course_id'] : null;
    $newDay = isset($data['day_of_week']) ? sanitizeInput($data['day_of_week']) : null;
    $newStart = isset($data['start_time']) ? sanitizeInput($data['start_time']) : null;
    $newEnd = isset($data['end_time']) ? sanitizeInput($data['end_time']) : null;
    $newRoom = isset($data['room_number']) ? sanitizeInput($data['room_number']) : null;

    if ($newCourseId && $newDay && $newStart && $newEnd) {
        // Check for exact duplicate (excluding self)
        $sql = "SELECT schedule_id FROM schedules WHERE course_id = :cid AND day_of_week = :day AND start_time = :start AND end_time = :end AND schedule_id != :sid";
        $dupParams = [':cid' => $newCourseId, ':day' => $newDay, ':start' => $newStart, ':end' => $newEnd, ':sid' => $scheduleId];
        if ($newRoom) {
            $sql .= " AND room_number = :room";
            $dupParams[':room'] = $newRoom;
        } else {
            $sql .= " AND room_number IS NULL";
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($dupParams);
        if ($stmt->fetch()) {
            jsonResponse([], false, 'An identical schedule already exists for this course', 409);
        }

        // Time conflict: same course + same day + overlapping time
        $stmt = $db->prepare("SELECT s.schedule_id, s.start_time, s.end_time, c.course_name, c.course_code FROM schedules s JOIN courses c ON s.course_id = c.course_id WHERE s.course_id = :cid AND s.day_of_week = :day AND s.schedule_id != :sid");
        $stmt->execute([':cid' => $newCourseId, ':day' => $newDay, ':sid' => $scheduleId]);
        while ($row = $stmt->fetch()) {
            if (Utilities::timeSlotOverlaps($newStart, $newEnd, $row['start_time'], $row['end_time'])) {
                jsonResponse([], false, "Time conflict: \"{$row['course_code']} {$row['course_name']}\" already has a schedule {$row['start_time']}-{$row['end_time']} on {$newDay}", 409);
            }
        }

        // Room conflict: same room + same day + overlapping time
        if ($newRoom) {
            $stmt = $db->prepare("SELECT s.schedule_id, s.start_time, s.end_time, c.course_name, c.course_code FROM schedules s JOIN courses c ON s.course_id = c.course_id WHERE s.room_number = :room AND s.day_of_week = :day AND s.schedule_id != :sid AND s.room_number IS NOT NULL");
            $stmt->execute([':room' => $newRoom, ':day' => $newDay, ':sid' => $scheduleId]);
            while ($row = $stmt->fetch()) {
                if (Utilities::timeSlotOverlaps($newStart, $newEnd, $row['start_time'], $row['end_time'])) {
                    jsonResponse([], false, "Room conflict: \"{$row['course_code']} {$row['course_name']}\" already booked this room {$row['start_time']}-{$row['end_time']} on {$newDay}", 409);
                }
            }
        }

        // Lecturer conflict: same lecturer + same day + overlapping time across different courses
        $stmt = $db->prepare("SELECT lecturer_name FROM courses WHERE course_id = :cid");
        $stmt->execute([':cid' => $newCourseId]);
        $course = $stmt->fetch();
        if ($course && !empty($course['lecturer_name'])) {
            $lecturer = $course['lecturer_name'];
            $stmt = $db->prepare("SELECT s.schedule_id, s.start_time, s.end_time, c.course_name, c.course_code FROM schedules s JOIN courses c ON s.course_id = c.course_id WHERE c.lecturer_name = :lecturer AND s.day_of_week = :day AND s.course_id != :cid");
            $stmt->execute([':lecturer' => $lecturer, ':day' => $newDay, ':cid' => $newCourseId]);
            while ($row = $stmt->fetch()) {
                if (Utilities::timeSlotOverlaps($newStart, $newEnd, $row['start_time'], $row['end_time'])) {
                    jsonResponse([], false, "Lecturer conflict: {$lecturer} is already teaching \"{$row['course_code']} {$row['course_name']}\" {$row['start_time']}-{$row['end_time']} on {$newDay}", 409);
                }
            }
        }
    }

    $stmt = $db->prepare('UPDATE schedules SET ' . implode(', ', $fields) . ' WHERE schedule_id = :schedule_id');
    $stmt->execute($params);

    if ($stmt->rowCount() === 0) {
        jsonResponse([], false, 'No changes or schedule not found', 404);
    }

    $cid = $newCourseId;
    if (!$cid) {
        $stmtC = $db->prepare("SELECT course_id FROM schedules WHERE schedule_id = :sid");
        $stmtC->execute([':sid' => $scheduleId]);
        $cid = $stmtC->fetchColumn();
    }
    if ($cid) {
        notifyEnrolledStudents($db, $cid, "Schedule updated for {$newDay} {$newStart}-{$newEnd}", 'warning');
    }

    jsonResponse([], true, 'Schedule updated successfully');
} catch (Exception $e) {
    jsonResponse([], false, 'Server error: ' . $e->getMessage(), 500);
}
