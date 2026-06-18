<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse([], false, 'Invalid request method', 405);
}

$database = new Database();
$db = $database->getConnection();

try {
    $query = "SELECT * FROM resources ORDER BY resource_type, resource_name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $resources = $stmt->fetchAll();
    
    jsonResponse($resources, true, 'Resources retrieved');
    
} catch(Exception $e) {
    jsonResponse([], false, 'Server error: ' . $e->getMessage(), 500);
}
?>
