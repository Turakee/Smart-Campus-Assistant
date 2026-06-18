<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireAuth();
requireRole(['administrator', 'system_admin']);

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        jsonResponse([], false, 'Database connection failed', 500);
    }
    
    $stmt = $db->prepare("SELECT s.student_id, s.full_name, s.department, s.level, s.enrollment_year, u.user_id, u.username, u.email, u.is_active
        FROM students s
        LEFT JOIN users u ON u.user_id = s.user_id
        ORDER BY s.full_name");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($result === false) {
        jsonResponse([], false, 'Query failed', 500);
    }
    
    jsonResponse($result, true, 'Students retrieved');
} catch(Exception $e) {
    jsonResponse([], false, 'Server error: ' . $e->getMessage(), 500);
}
?>