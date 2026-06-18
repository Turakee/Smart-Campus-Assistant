<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/AuditLogger.php';

requireAuth();
requireRole(['administrator', 'system_admin']);

$database = new Database();
$db = $database->getConnection();
$logger = new AuditLogger($db);

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['student_id']) || empty(trim($data['message'] ?? ''))) {
    jsonResponse([], false, 'Student ID and message are required', 400);
}

$student_id = (int)$data['student_id'];
$message = trim($data['message']);

// Validate message length
if (strlen($message) < 1 || strlen($message) > 5000) {
    jsonResponse([], false, 'Message must be 1-5000 characters', 400);
}

try {
    // Check if student exists and get user_id
    $checkStmt = $db->prepare("SELECT student_id, user_id, full_name FROM students WHERE student_id = :sid LIMIT 1");
    $checkStmt->execute([':sid' => $student_id]);
    $student = $checkStmt->fetch();
    
    if (!$student) {
        jsonResponse([], false, 'Student not found', 404);
    }
    
    // Get admin name
    $adminStmt = $db->prepare("SELECT username FROM users WHERE user_id = :uid LIMIT 1");
    $adminStmt->execute([':uid' => $_SESSION['user_id']]);
    $admin = $adminStmt->fetch();
    $adminName = $admin['username'] ?? 'Administrator';
    
    // Insert notification using the student's user_id
    $fullMessage = "Message from " . $adminName . ": " . $message;
    
    $insertStmt = $db->prepare("INSERT INTO notifications (user_id, message, type, is_read, created_at) 
                                VALUES (:uid, :message, 'warning', 0, NOW())");
    
    $insertStmt->execute([
        ':uid' => $student['user_id'],
        ':message' => $fullMessage
    ]);
    
    // Log the action
    $logger->log($_SESSION['user_id'], 
                'Sent message to student: ' . $student['full_name'] . ' (ID: ' . $student_id . ')', 
                'message_sent', 
                ['recipient_student_id' => $student_id, 'recipient_name' => $student['full_name'], 'message_length' => strlen($message)]);
    
    jsonResponse([
        'notification_id' => $db->lastInsertId(),
        'student_name' => $student['full_name']
    ], true, 'Message sent successfully to ' . $student['full_name']);
    
} catch(Exception $e) {
    jsonResponse([], false, 'Failed to send message: ' . $e->getMessage(), 500);
}
?>
