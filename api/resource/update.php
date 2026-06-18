<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireRole(['administrator', 'system_admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    jsonResponse([], false, 'Method not allowed', 405);
}

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['resource_id']) || empty($data['resource_name']) || empty($data['resource_type'])) {
    jsonResponse([], false, 'Resource ID, name and type are required', 400);
}

$allowedTypes = ['classroom', 'lab', 'auditorium', 'other'];
if (!in_array($data['resource_type'], $allowedTypes)) {
    jsonResponse([], false, 'Invalid resource type', 400);
}

try {
    $query = "UPDATE resources 
              SET resource_name = :name, resource_type = :type, capacity = :capacity 
              WHERE resource_id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':id' => (int)$data['resource_id'],
        ':name' => sanitizeInput($data['resource_name']),
        ':type' => $data['resource_type'],
        ':capacity' => isset($data['capacity']) ? (int)$data['capacity'] : null
    ]);
    
    jsonResponse([], true, 'Resource updated successfully');
    
} catch(Exception $e) {
    jsonResponse([], false, 'Error: ' . $e->getMessage(), 500);
}
?>
