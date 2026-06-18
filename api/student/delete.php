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

    $db->beginTransaction();

    $delStmt = $db->prepare("DELETE FROM ai_analytics_log WHERE student_id = :sid");
    $delStmt->execute([':sid' => $student_id]);
    $delStmt = $db->prepare("DELETE FROM attendance WHERE student_id = :sid");
    $delStmt->execute([':sid' => $student_id]);
    $delStmt = $db->prepare("DELETE FROM student_courses WHERE student_id = :sid");
    $delStmt->execute([':sid' => $student_id]);
    $delStmt = $db->prepare("DELETE FROM bookings WHERE student_id = :sid");
    $delStmt->execute([':sid' => $student_id]);
    $delStmt = $db->prepare("DELETE FROM notifications WHERE user_id = :uid");
    $delStmt->execute([':uid' => $user_id]);
    $delStmt = $db->prepare("DELETE FROM students WHERE student_id = :sid");
    $delStmt->execute([':sid' => $student_id]);

    $stmtDelete = $db->prepare('DELETE FROM users WHERE user_id = :user_id');
    $stmtDelete->execute([':user_id' => $user_id]);

    if ($stmtDelete->rowCount() === 0) {
        $db->rollBack();
        jsonResponse([], false, 'Failed to delete student account', 500);
    }

    $db->commit();
    jsonResponse([], true, 'Student account deleted');
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse([], false, 'Server error: ' . $e->getMessage(), 500);
}
