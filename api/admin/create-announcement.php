<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/AuditLogger.php';

requireAuth();
requireRole(['administrator', 'system_admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([], false, 'Method not allowed', 405);
}

$database = new Database();
$db = $database->getConnection();
$logger = new AuditLogger($db);
$data = json_decode(file_get_contents("php://input"), true);

// Input validation
if (empty($data['title']) || empty($data['message'])) {
    jsonResponse([], false, 'Title and message are required', 400);
}

// Sanitize inputs
$title = trim($data['title']);
$message = trim($data['message']);

// Validate length
if (strlen($title) > 255) {
    jsonResponse([], false, 'Title must be 255 characters or less', 400);
}

if (strlen($message) > 5000) {
    jsonResponse([], false, 'Message must be 5000 characters or less', 400);
}

if (strlen($title) < 3) {
    jsonResponse([], false, 'Title must be at least 3 characters', 400);
}

try {
    $db->beginTransaction();

    // Create announcement notification for all students
    $notifQuery = "INSERT INTO notifications (user_id, message, type, created_at)
                   SELECT u.user_id, :message, 'warning', NOW()
                   FROM users u
                   JOIN students s ON u.user_id = s.user_id
                   WHERE u.role = 'student'";

    $fullMessage = "📢 " . $title . ": " . $message;

    $notifStmt = $db->prepare($notifQuery);
    $notifStmt->execute([':message' => $fullMessage]);
    
    $recipientCount = $notifStmt->rowCount();

    $db->commit();
    
    // Log the action
    $logger->log($_SESSION['user_id'], 
                 'Created announcement: "' . $title . '" - Sent to ' . $recipientCount . ' students', 
                 'announcement_created', 
                 ['title' => $title, 'message' => $message, 'recipient_count' => $recipientCount]);

    jsonResponse(['recipient_count' => $recipientCount], true, 'Announcement sent to ' . $recipientCount . ' students');

} catch(Exception $e) {
    $db->rollBack();
    jsonResponse([], false, 'Error creating announcement: ' . $e->getMessage(), 500);
}
?>