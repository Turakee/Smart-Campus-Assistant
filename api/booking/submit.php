<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireAuth();

if ($_SESSION['role'] !== 'student') {
    jsonResponse([], false, 'Only students can submit bookings', 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([], false, 'Method not allowed', 405);
}

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"), true);

// Validate input
if (empty($data['resource_id']) || empty($data['booking_date']) || 
    empty($data['start_time']) || empty($data['end_time'])) {
    jsonResponse([], false, 'All booking fields are required', 400);
}

try {
    $db->beginTransaction();
    
    // Get student_id
    $stmt = $db->prepare("SELECT student_id FROM students WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $student = $stmt->fetch();
    
    if (!$student) {
        throw new Exception('Student profile not found');
    }
    
    // Check if student already has a future approved booking for this resource
    $approvedCheck = "SELECT COUNT(*) as count FROM bookings 
                       WHERE student_id = :student_id 
                       AND resource_id = :resource_id 
                       AND status = 'approved'
                       AND booking_date >= CURDATE()";
    
    $approvedStmt = $db->prepare($approvedCheck);
    $approvedStmt->execute([
        ':student_id' => $student['student_id'],
        ':resource_id' => $data['resource_id']
    ]);
    
    if ($approvedStmt->fetch()['count'] > 0) {
        throw new Exception('You already have an upcoming approved booking for this resource.');
    }
    
    // Check for double booking
    $checkQuery = "SELECT COUNT(*) as count FROM bookings 
                   WHERE resource_id = :resource_id 
                   AND booking_date = :booking_date 
                   AND status != 'rejected'
                   AND start_time < :end_time 
                   AND end_time > :start_time";
    
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([
        ':resource_id' => $data['resource_id'],
        ':booking_date' => $data['booking_date'],
        ':start_time' => $data['start_time'],
        ':end_time' => $data['end_time']
    ]);
    
    if ($checkStmt->fetch()['count'] > 0) {
        throw new Exception('Resource already booked for this time slot');
    }
    
    // Insert booking
    $query = "INSERT INTO bookings 
              (student_id, resource_id, booking_date, start_time, end_time, purpose, status, created_at) 
              VALUES (:student_id, :resource_id, :booking_date, :start_time, :end_time, :purpose, 'pending', NOW())";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':student_id' => $student['student_id'],
        ':resource_id' => $data['resource_id'],
        ':booking_date' => $data['booking_date'],
        ':start_time' => $data['start_time'],
        ':end_time' => $data['end_time'],
        ':purpose' => isset($data['purpose']) ? sanitizeInput($data['purpose']) : null
    ]);
    
    $booking_id = $db->lastInsertId();
    
    // Create notification for admin
    $notifStmt = $db->prepare("SELECT user_id FROM administrators LIMIT 1");
    $notifStmt->execute();
    $admin = $notifStmt->fetch();
    
    if ($admin) {
        $insertNotif = $db->prepare("INSERT INTO notifications (user_id, message, type, created_at) VALUES (:uid, :msg, 'info', NOW())");
        $insertNotif->execute([
            ':uid' => $admin['user_id'],
            ':msg' => 'New booking request submitted (ID: ' . $booking_id . ')'
        ]);
    }
    
    $db->commit();
    
    jsonResponse(['booking_id' => $booking_id], true, 'Booking request submitted successfully', 201);
    
} catch(Exception $e) {
    $db->rollBack();
    jsonResponse([], false, $e->getMessage(), 400);
}
?>