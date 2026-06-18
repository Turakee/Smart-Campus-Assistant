<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireAuth();
requireRole(['administrator', 'system_admin']);

$database = new Database();
$db = $database->getConnection();

try {
    $query = "SELECT b.*, s.full_name as student_name, r.resource_name, r.resource_type
              FROM bookings b
              JOIN students s ON b.student_id = s.student_id
              JOIN resources r ON b.resource_id = r.resource_id
              WHERE b.status = 'pending'
              ORDER BY b.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    jsonResponse($stmt->fetchAll(), true, 'Pending bookings retrieved');
    
} catch(Exception $e) {
    jsonResponse([], false, 'Server error', 500);
}
?>