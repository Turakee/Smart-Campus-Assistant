<?php
/**
 * Attendance Update API Endpoint
 * PUT /api/attendance/{id} - Update attendance record
 * DELETE /api/attendance/{id} - Delete attendance record
 */

include_once '../../config/config.php';
include_once '../../config/database.php';
include_once '../../config/Validator.php';
include_once '../../config/Security.php';

requireAuth();
requireRole(['administrator', 'system_admin']);

$database = new Database();
$db = $database->getConnection();

// Get attendance ID from URL
$attendanceId = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$attendanceId) {
    jsonResponse([], false, 'Attendance ID is required', 400);
}

// Check if attendance record exists
$checkQuery = "SELECT attendance_id, student_id, course_id FROM attendance 
               WHERE attendance_id = :id
               LIMIT 1";

$stmt = $db->prepare($checkQuery);
$stmt->bindParam(':id', $attendanceId);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    jsonResponse([], false, 'Attendance record not found', 404);
}

$record = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Update attendance record
    try {
        $input = json_decode(file_get_contents("php://input"), true);

        // Validate status if provided
        if (isset($input['status'])) {
            Validator::reset();
            Validator::attendanceStatus($input['status'], 'status');

            if (Validator::hasErrors()) {
                jsonResponse(Validator::getErrors(), false, 'Validation failed', 400);
            }
        }

        // Validate date if provided
        if (isset($input['date'])) {
            Validator::reset();
            Validator::date($input['date'], 'date');

            if (Validator::hasErrors()) {
                jsonResponse(Validator::getErrors(), false, 'Validation failed', 400);
            }
        }

        // Validate course_id if provided
        if (isset($input['course_id'])) {
            Validator::reset();
            Validator::integer($input['course_id'], 'course_id');

            if (Validator::hasErrors()) {
                jsonResponse(Validator::getErrors(), false, 'Validation failed', 400);
            }

            // Check if course exists
            $courseQuery = "SELECT course_id FROM courses WHERE course_id = :course_id";
            $stmt = $db->prepare($courseQuery);
            $stmt->bindParam(':course_id', $input['course_id']);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                jsonResponse([], false, 'Course not found', 404);
            }
        }

        // Build update query dynamically
        $updateFields = [];
        $params = [];

        if (isset($input['status'])) {
            $updateFields[] = "status = :status";
            $params[':status'] = $input['status'];
        }

        if (isset($input['date'])) {
            $updateFields[] = "date = :date";
            $params[':date'] = $input['date'];
        }

        if (isset($input['course_id'])) {
            $updateFields[] = "course_id = :course_id";
            $params[':course_id'] = $input['course_id'];
        }

        if (empty($updateFields)) {
            jsonResponse([], false, 'No fields to update', 400);
        }

        $updateQuery = "UPDATE attendance 
                       SET " . implode(", ", $updateFields) . "
                       WHERE attendance_id = :id";
        $params[':id'] = $attendanceId;

        $stmt = $db->prepare($updateQuery);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        if ($stmt->execute()) {
            $updateLog = "Attendance ID: {$attendanceId}";
            if (isset($input['status'])) $updateLog .= ", Status: {$input['status']}";
            if (isset($input['date'])) $updateLog .= ", Date: {$input['date']}";
            if (isset($input['course_id'])) $updateLog .= ", Course: {$input['course_id']}";

            Security::logSecurityEvent('Attendance updated', 'INFO', $updateLog);

            jsonResponse([], true, 'Attendance updated successfully');
        } else {
            jsonResponse([], false, 'Failed to update attendance', 500);
        }

    } catch (\PDOException $e) {
        jsonResponse([], false, 'Database error: ' . $e->getMessage(), 500);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Delete attendance record
    try {
        $deleteQuery = "DELETE FROM attendance WHERE attendance_id = :id";

        $stmt = $db->prepare($deleteQuery);
        $stmt->bindParam(':id', $attendanceId);

        if ($stmt->execute()) {
            Security::logSecurityEvent('Attendance deleted', 'INFO',
                "Attendance ID: {$attendanceId}, Student: {$record['student_id']}, Course: {$record['course_id']}");

            jsonResponse([], true, 'Attendance deleted successfully');
        } else {
            jsonResponse([], false, 'Failed to delete attendance', 500);
        }

    } catch (\PDOException $e) {
        jsonResponse([], false, 'Database error: ' . $e->getMessage(), 500);
    }

} else {
    jsonResponse([], false, 'Method not allowed', 405);
}
