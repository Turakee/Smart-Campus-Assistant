<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireRole(['administrator', 'system_admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([], false, 'Method not allowed', 405);
}

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['resource_id'])) {
    jsonResponse([], false, 'Resource ID is required', 400);
}

try {
    // Check if resource has any bookings
    $checkQuery = "SELECT COUNT(*) as count FROM bookings WHERE resource_id = :id AND status = 'approved'";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([':id' => (int)$data['resource_id']]);
    $count = $checkStmt->fetch()['count'];
    
    if ($count > 0) {
        jsonResponse([], false, 'Cannot delete resource with active bookings', 400);
    }
    
    $query = "DELETE FROM resources WHERE resource_id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => (int)$data['resource_id']]);
    
    jsonResponse([], true, 'Resource deleted successfully');
    
} catch(Exception $e) {
    jsonResponse([], false, 'Error: ' . $e->getMessage(), 500);
}
?>
