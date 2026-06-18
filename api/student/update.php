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

if (empty($data['student_id'])) {
    jsonResponse([], false, 'Student ID is required', 400);
}

$student_id = (int)$data['student_id'];

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("SELECT user_id, full_name, department, level, enrollment_year FROM students WHERE student_id = :id");
    $stmt->execute([':id' => $student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        jsonResponse([], false, 'Student not found', 404);
    }

    $user_id = (int)$student['user_id'];

    $updatedStudent = [
        'full_name' => isset($data['full_name']) ? sanitizeInput($data['full_name']) : $student['full_name'],
        'department' => isset($data['department']) ? sanitizeInput($data['department']) : $student['department'],
        'level' => isset($data['level']) ? (int)$data['level'] : $student['level'],
        'enrollment_year' => isset($data['enrollment_year']) ? (int)$data['enrollment_year'] : $student['enrollment_year'],
    ];

    $stmtUpdateStudent = $db->prepare("UPDATE students SET full_name = :full_name, department = :department, level = :level, enrollment_year = :enrollment_year WHERE student_id = :student_id");

    $stmtUpdateStudent->execute([
        ':full_name' => $updatedStudent['full_name'],
        ':department' => $updatedStudent['department'],
        ':level' => $updatedStudent['level'],
        ':enrollment_year' => $updatedStudent['enrollment_year'],
        ':student_id' => $student_id
    ]);

    $userPatch = [];
    $userPatch['username'] = isset($data['username']) ? sanitizeInput($data['username']) : null;
    $userPatch['email'] = isset($data['email']) ? sanitizeInput($data['email']) : null;
    $userPatch['is_active'] = isset($data['is_active']) ? (int)(bool)$data['is_active'] : null;

    $updateUserParts = [];
    $updateUserData = [':user_id' => $user_id];

    if ($userPatch['username'] !== null) {
        $updateUserParts[] = 'username = :username';
        $updateUserData[':username'] = $userPatch['username'];
    }
    if ($userPatch['email'] !== null) {
        $updateUserParts[] = 'email = :email';
        $updateUserData[':email'] = $userPatch['email'];
    }
    if ($userPatch['is_active'] !== null) {
        $updateUserParts[] = 'is_active = :is_active';
        $updateUserData[':is_active'] = $userPatch['is_active'];
    }

    if (count($updateUserParts) > 0) {
        $stmtUpdateUser = $db->prepare('UPDATE users SET ' . implode(', ', $updateUserParts) . ' WHERE user_id = :user_id');
        $stmtUpdateUser->execute($updateUserData);
    }

    jsonResponse([], true, 'Student updated successfully');
} catch (Exception $e) {
    jsonResponse([], false, 'Server error: ' . $e->getMessage(), 500);
}
