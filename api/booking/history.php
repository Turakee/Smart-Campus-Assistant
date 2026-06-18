<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse([], false, 'Invalid request method', 405);
}

if ($_SESSION['role'] !== 'student') {
    jsonResponse([], false, 'Only students can view booking history', 403);
}

$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("SELECT student_id FROM students WHERE user_id = :uid");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $student = $stmt->fetch();

    if (!$student) {
        jsonResponse([], false, 'Student profile not found', 404);
    }

    $query = "SELECT b.*, r.resource_name, r.resource_type
              FROM bookings b
              JOIN resources r ON b.resource_id = r.resource_id
              WHERE b.student_id = :sid
              ORDER BY b.booking_date DESC, b.created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute([':sid' => $student['student_id']]);

    jsonResponse($stmt->fetchAll(), true, 'Booking history retrieved');

} catch(Exception $e) {
    jsonResponse([], false, 'Server error', 500);
}
?>