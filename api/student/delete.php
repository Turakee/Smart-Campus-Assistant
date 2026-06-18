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

    $stmt = $db->prepare('SELECT user_id FROM students WHERE student_id = :id');
    $stmt->execute([':id' => $student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        jsonResponse([], false, 'Student not found', 404);
    }

    $user_id = (int)$student['user_id'];

    $stmtDelete = $db->prepare('DELETE FROM users WHERE user_id = :user_id');
    $stmtDelete->execute([':user_id' => $user_id]);

    if ($stmtDelete->rowCount() === 0) {
        jsonResponse([], false, 'Failed to delete student account', 500);
    }

    jsonResponse([], true, 'Student account deleted');
} catch (Exception $e) {
    jsonResponse([], false, 'Server error: ' . $e->getMessage(), 500);
}
