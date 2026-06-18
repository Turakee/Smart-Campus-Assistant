<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse([], false, 'Invalid request method', 405);
}

$database = new Database();
$db = $database->getConnection();

try {
    $user_id = $_SESSION['user_id'];
    
    // Fetch all notifications for user, ordered by date
    $query = "SELECT * FROM notifications 
              WHERE user_id = :uid 
              ORDER BY created_at DESC 
              LIMIT 50";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':uid' => $user_id]);
    $notifications = $stmt->fetchAll();
    
    // Count unread
    $countQuery = "SELECT COUNT(*) as count FROM notifications 
                   WHERE user_id = :uid AND is_read = 0";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute([':uid' => $user_id]);
    $unreadCount = $countStmt->fetchColumn();
    
    jsonResponse([
        'notifications' => $notifications,
        'unread_count' => $unreadCount
    ], true, 'Notifications retrieved');
    
} catch(Exception $e) {
    jsonResponse([], false, 'Server error', 500);
}
?>