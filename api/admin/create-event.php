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
if (empty($data['title']) || empty($data['message']) || empty($data['event_date'])) {
    jsonResponse([], false, 'Title, message, and event date are required', 400);
}

// Sanitize inputs
$title = trim($data['title']);
$message = trim($data['message']);
$eventDate = trim($data['event_date']);

// Validate date format (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
    jsonResponse([], false, 'Invalid date format. Use YYYY-MM-DD', 400);
}

// Validate date is not in the past
$eventDateTime = strtotime($eventDate);
if ($eventDateTime === false) {
    jsonResponse([], false, 'Invalid date', 400);
}

if ($eventDateTime < strtotime('today')) {
    jsonResponse([], false, 'Event date cannot be in the past', 400);
}

// Validate lengths
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

    // Create event notification for all students
    $notifQuery = "INSERT INTO notifications (user_id, message, type, created_at)
                   SELECT u.user_id, :message, 'info', NOW()
                   FROM users u
                   JOIN students s ON u.user_id = s.user_id
                   WHERE u.role = 'student'";

    $fullMessage = "📅 " . $title . " - " . $message . " (" . $eventDate . ")";

    $notifStmt = $db->prepare($notifQuery);
    $notifStmt->execute([':message' => $fullMessage]);
    
    $recipientCount = $notifStmt->rowCount();

    $db->commit();
    
    // Log the action
    $logger->log($_SESSION['user_id'], 
                 'Created event: "' . $title . '" on ' . $eventDate . ' - Sent to ' . $recipientCount . ' students', 
                 'event_created', 
                 ['title' => $title, 'message' => $message, 'event_date' => $eventDate, 'recipient_count' => $recipientCount]);

    jsonResponse(['recipient_count' => $recipientCount], true, 'Event notification sent to ' . $recipientCount . ' students');

} catch(Exception $e) {
    $db->rollBack();
    jsonResponse([], false, 'Error creating event: ' . $e->getMessage(), 500);
}
?>