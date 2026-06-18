<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([], false, 'Method not allowed', 405);
}

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data) && !empty($_POST)) {
    $data = $_POST;
}

if (!is_array($data)) {
    $data = [];
}

try {
    $action = $data['action'] ?? 'mark_read';
    $user_id = $_SESSION['user_id'];
    
    if ($action === 'clear_all') {
        // Delete all notifications for user
        $stmt = $db->prepare("DELETE FROM notifications WHERE user_id = :uid");
        $stmt->execute([':uid' => $user_id]);
        jsonResponse([], true, 'All notifications cleared');
        
    } elseif ($action === 'mark_all_read') {
        // Mark all as read
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :uid");
        $stmt->execute([':uid' => $user_id]);
        jsonResponse([], true, 'All notifications marked as read');
        
    } elseif (isset($data['notification_id']) && !empty($data['notification_id'])) {
        // Mark single notification as read
        $notificationId = intval($data['notification_id']);
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 
                  WHERE notification_id = :nid AND user_id = :uid");
        $stmt->execute([
            ':nid' => $notificationId,
            ':uid' => $user_id
        ]);
        jsonResponse([], true, 'Notification marked as read');
        
    } else {
        jsonResponse([], false, 'Invalid action', 400);
    }
    
} catch(Exception $e) {
    jsonResponse([], false, 'Server error', 500);
}
?>
