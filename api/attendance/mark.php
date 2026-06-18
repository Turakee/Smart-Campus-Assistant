<?php
/**
 * Attendance Mark API Endpoint
 * POST /api/attendance/mark - Mark attendance for students
 */

include_once '../../config/config.php';
include_once '../../config/database.php';
include_once '../../config/Validator.php';
include_once '../../config/Security.php';

requireAuth();
requireRole(['administrator', 'system_admin']);

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([], false, 'Method not allowed', 405);
}

try {
    $input = json_decode(file_get_contents("php://input"), true);

    // Validate input
    Validator::reset();
    Validator::required($input['student_id'] ?? '', 'student_id');
    Validator::required($input['course_id'] ?? '', 'course_id');
    Validator::required($input['date'] ?? '', 'date');
    Validator::required($input['status'] ?? '', 'status');

    Validator::integer($input['student_id'] ?? '', 'student_id');
    Validator::integer($input['course_id'] ?? '', 'course_id');
    Validator::date($input['date'] ?? '', 'date');
    Validator::attendanceStatus($input['status'] ?? '', 'status');

    if (Validator::hasErrors()) {
        jsonResponse(Validator::getErrors(), false, 'Validation failed', 400);
    }

    // Check if student exists
    $studentQuery = "SELECT student_id FROM students WHERE student_id = :student_id";
    $stmt = $db->prepare($studentQuery);
    $stmt->bindParam(':student_id', $input['student_id']);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        jsonResponse([], false, 'Student not found', 404);
    }

    // Check if course exists
    $courseQuery = "SELECT course_id FROM courses WHERE course_id = :course_id";
    $stmt = $db->prepare($courseQuery);
    $stmt->bindParam(':course_id', $input['course_id']);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        jsonResponse([], false, 'Course not found', 404);
    }

    // Check if attendance record already exists
    $existingQuery = "SELECT attendance_id FROM attendance 
                     WHERE student_id = :student_id 
                     AND course_id = :course_id 
                     AND date = :date
                     LIMIT 1";

    $stmt = $db->prepare($existingQuery);
    $stmt->bindParam(':student_id', $input['student_id']);
    $stmt->bindParam(':course_id', $input['course_id']);
    $stmt->bindParam(':date', $input['date']);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        jsonResponse([], false, 'Attendance record already exists for this date', 409);
    }

    // Insert attendance record
    $insertQuery = "INSERT INTO attendance (student_id, course_id, date, status)
                    VALUES (:student_id, :course_id, :date, :status)";

    $stmt = $db->prepare($insertQuery);
    $stmt->bindParam(':student_id', $input['student_id']);
    $stmt->bindParam(':course_id', $input['course_id']);
    $stmt->bindParam(':date', $input['date']);
    $stmt->bindParam(':status', $input['status']);

    if ($stmt->execute()) {
        // Log the activity
        Security::logSecurityEvent('Attendance marked', 'INFO',
            "Student: {$input['student_id']}, Course: {$input['course_id']}, Date: {$input['date']}");

        jsonResponse([], true, 'Attendance marked successfully', 201);
    } else {
        jsonResponse([], false, 'Failed to mark attendance', 500);
    }

} catch (\PDOException $e) {
    jsonResponse([], false, 'Database error: ' . $e->getMessage(), 500);
}
