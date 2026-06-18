<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/AuditLogger.php';

requireAuth();
requireRole(['system_admin']);

$database = new Database();
$db = $database->getConnection();
$logger = new AuditLogger($db);

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['user_id'])) {
    jsonResponse([], false, 'Missing user_id', 400);
}

$userId = (int)$data['user_id'];

// Prevent self-deletion
if ($userId === (int)$_SESSION['user_id']) {
    jsonResponse([], false, 'You cannot delete your own account', 403);
}

// Validate user exists
try {
    $checkStmt = $db->prepare("SELECT user_id, username FROM users WHERE user_id = :user_id LIMIT 1");
    $checkStmt->execute([':user_id' => $userId]);
    $user = $checkStmt->fetch();
    
    if (!$user) {
        jsonResponse([], false, 'User not found', 404);
    }
    
    // Start transaction
    $db->beginTransaction();

    // Delete from related tables first (in correct order)
    $delStmt = $db->prepare("DELETE FROM ai_analytics_log WHERE student_id IN (SELECT student_id FROM students WHERE user_id = :user_id)");
    $delStmt->execute([':user_id' => $userId]);

    $delStmt = $db->prepare("DELETE FROM attendance WHERE student_id IN (SELECT student_id FROM students WHERE user_id = :user_id)");
    $delStmt->execute([':user_id' => $userId]);

    $delStmt = $db->prepare("DELETE FROM student_courses WHERE student_id IN (SELECT student_id FROM students WHERE user_id = :user_id)");
    $delStmt->execute([':user_id' => $userId]);

    $delStmt = $db->prepare("DELETE FROM bookings WHERE student_id IN (SELECT student_id FROM students WHERE user_id = :user_id)");
    $delStmt->execute([':user_id' => $userId]);

    $delStmt = $db->prepare("DELETE FROM notifications WHERE user_id = :user_id");
    $delStmt->execute([':user_id' => $userId]);

    $delStmt = $db->prepare("DELETE FROM students WHERE user_id = :user_id");
    $delStmt->execute([':user_id' => $userId]);

    $delStmt = $db->prepare("DELETE FROM administrators WHERE user_id = :user_id");
    $delStmt->execute([':user_id' => $userId]);

    $delStmt = $db->prepare("DELETE FROM users WHERE user_id = :user_id");
    $delStmt->execute([':user_id' => $userId]);

    $db->commit();
    
    // Log the action
    $logger->log($_SESSION['user_id'], 
                 'Deleted user: ' . $user['username'] . ' (ID: ' . $userId . ')', 
                 'user_delete', 
                 ['deleted_user_id' => $userId, 'deleted_username' => $user['username']]);

    jsonResponse([], true, 'User deleted successfully');

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse([], false, 'Error deleting user: ' . $e->getMessage(), 500);
}
?>