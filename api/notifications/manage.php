<?php
/**
 * Notification Management API Endpoints
 * DELETE /api/notifications/{id} - Delete notification
 * POST /api/notifications/read-all - Mark all as read
 */

include_once '../../config/config.php';
include_once '../../config/database.php';
include_once '../../config/Utilities.php';
include_once '../../config/Security.php';

requireAuth();

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$notificationId = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    if ($method === 'DELETE') {
        if ($notificationId) {
            // Delete single notification
            $checkQuery = "SELECT notification_id FROM notifications 
                          WHERE notification_id = :id AND user_id = :user_id
                          LIMIT 1";

            $stmt = $db->prepare($checkQuery);
            $stmt->bindParam(':id', $notificationId);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                jsonResponse([], false, 'Notification not found', 404);
            }

            $deleteQuery = "DELETE FROM notifications WHERE notification_id = :id";
            $stmt = $db->prepare($deleteQuery);
            $stmt->bindParam(':id', $notificationId);

            if ($stmt->execute()) {
                Security::logSecurityEvent('Notification deleted', 'INFO',
                    "User: {$_SESSION['user_id']}, Notification ID: {$notificationId}");
                jsonResponse([], true, 'Notification deleted successfully');
            } else {
                jsonResponse([], false, 'Failed to delete notification', 500);
            }
        } else {
            // Clear all notifications for user
            $deleteQuery = "DELETE FROM notifications WHERE user_id = :user_id";
            $stmt = $db->prepare($deleteQuery);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);

            if ($stmt->execute()) {
                $affectedRows = $stmt->rowCount();
                Security::logSecurityEvent('All notifications cleared', 'INFO',
                    "User: {$_SESSION['user_id']}, Count: {$affectedRows}");
                jsonResponse(['deleted_count' => $affectedRows], true, 'All notifications cleared');
            } else {
                jsonResponse([], false, 'Failed to clear notifications', 500);
            }
        }

    } elseif ($method === 'POST') {
        // Mark all notifications as read for current user
        $updateQuery = "UPDATE notifications 
                       SET is_read = 1
                       WHERE user_id = :user_id AND is_read = 0";

        $stmt = $db->prepare($updateQuery);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);

        if ($stmt->execute()) {
            $affectedRows = $stmt->rowCount();

            Security::logSecurityEvent('All notifications marked as read', 'INFO',
                "User: {$_SESSION['user_id']}, Count: {$affectedRows}");

            jsonResponse(['marked_as_read' => $affectedRows], true, 'All notifications marked as read');
        } else {
            jsonResponse([], false, 'Failed to update notifications', 500);
        }

    } else {
        jsonResponse([], false, 'Method not allowed', 405);
    }

} catch (\PDOException $e) {
    jsonResponse([], false, 'Database error: ' . $e->getMessage(), 500);
}
