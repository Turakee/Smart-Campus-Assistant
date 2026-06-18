<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireAuth();
requireRole(['administrator', 'system_admin']);

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([], false, 'Method not allowed', 405);
}

$data = json_decode(file_get_contents('php://input'), true);
if (empty($data['course_id'])) {
    jsonResponse([], false, 'Course ID is required', 400);
}

try {
    $stmt = $db->prepare("DELETE FROM courses WHERE course_id = :id");
    $stmt->execute([':id' => (int)$data['course_id']]);

    if ($stmt->rowCount() === 0) {
        jsonResponse([], false, 'Course not found', 404);
    }

    jsonResponse([], true, 'Course deleted');
} catch (Exception $e) {
    jsonResponse([], false, 'Server error: ' . $e->getMessage(), 500);
}
?>