<?php
/**
 * Student Profile API Endpoint
 * GET /api/student/profile - Get current student's profile
 * PUT /api/student/profile - Update student's profile
 */

include_once '../../config/config.php';
include_once '../../config/database.php';
include_once '../../config/Validator.php';

requireAuth();

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // GET current student's profile
    try {
        $query = "SELECT s.student_id, s.full_name, s.department, s.level, s.enrollment_year,
                         u.username, u.email, u.user_id, u.last_login, u.created_at
                  FROM students s
                  JOIN users u ON s.user_id = u.user_id
                  WHERE u.user_id = :user_id
                  LIMIT 1";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            jsonResponse([], false, 'Profile not found', 404);
        }

        $profile = $stmt->fetch();
        jsonResponse($profile, true, 'Profile retrieved successfully');

    } catch (\PDOException $e) {
        jsonResponse([], false, 'Database error: ' . $e->getMessage(), 500);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // PUT update student's profile
    try {
        $input = json_decode(file_get_contents("php://input"), true);

        // Validate input
        Validator::reset();
        Validator::required($input['full_name'] ?? '', 'full_name');
        Validator::minLength($input['full_name'] ?? '', 3, 'full_name');
        Validator::maxLength($input['full_name'] ?? '', 100, 'full_name');

        if (isset($input['department'])) {
            Validator::maxLength($input['department'], 100, 'department');
        }

        if (isset($input['level'])) {
            Validator::academicLevel($input['level'], 'level');
        }

        if (Validator::hasErrors()) {
            jsonResponse(Validator::getErrors(), false, 'Validation failed', 400);
        }

        // Update profile
        $query = "UPDATE students 
                  SET full_name = :full_name, 
                      department = :department, 
                      level = :level
                  WHERE user_id = :user_id";

        $department = $input['department'] ?? null;
        $level = $input['level'] ?? null;

        $stmt = $db->prepare($query);
        $stmt->bindParam(':full_name', $input['full_name']);
        $stmt->bindParam(':department', $department);
        $stmt->bindParam(':level', $level);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);

        if ($stmt->execute()) {
            $_SESSION['name'] = $input['full_name'];
            jsonResponse([], true, 'Profile updated successfully');
        } else {
            jsonResponse([], false, 'Failed to update profile', 500);
        }

    } catch (\PDOException $e) {
        jsonResponse([], false, 'Database error: ' . $e->getMessage(), 500);
    }

} else {
    jsonResponse([], false, 'Method not allowed', 405);
}
