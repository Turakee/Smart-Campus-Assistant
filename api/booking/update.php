<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireAuth();
requireRole(['administrator', 'system_admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([], false, 'Method not allowed', 405);
}

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['booking_id']) || empty($data['status'])) {
    jsonResponse([], false, 'Booking ID and status required', 400);
}

$valid_statuses = ['approved', 'rejected'];
if (!in_array($data['status'], $valid_statuses)) {
    jsonResponse([], false, 'Invalid status', 400);
}

try {
    // Get booking details for notification
    $stmt = $db->prepare("SELECT b.student_id, b.booking_id, s.user_id, r.resource_name 
                          FROM bookings b 
                          JOIN students s ON b.student_id = s.student_id 
                          JOIN resources r ON b.resource_id = r.resource_id
                          WHERE b.booking_id = :id");
    $stmt->execute([':id' => $data['booking_id']]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        jsonResponse([], false, 'Booking not found', 404);
    }
    
    // Update booking status
    $query = "UPDATE bookings SET status = :status WHERE booking_id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':status' => $data['status'],
        ':id' => $data['booking_id']
    ]);
    
    // Create notification for student
    $statusText = $data['status'] === 'approved' ? 'approved' : 'rejected';
    $message = "Your booking for {$booking['resource_name']} has been {$statusText}!";
    $notifType = $data['status'] === 'approved' ? 'success' : 'danger';

    // Insert notification directly using user_id
    $notifQuery = "INSERT INTO notifications (user_id, message, type, is_read, created_at)
                   VALUES (:uid, :message, :type, 0, NOW())";
    $notifStmt = $db->prepare($notifQuery);
    $notifStmt->execute([
        ':uid' => $booking['user_id'],
        ':message' => $message,
        ':type' => $notifType
    ]);
    
    jsonResponse([
        'booking_id' => $data['booking_id'],
        'status' => $data['status'],
        'notification_sent' => true
    ], true, 'Booking status updated successfully');
    
} catch(Exception $e) {
    jsonResponse([], false, $e->getMessage(), 500);
}
?>