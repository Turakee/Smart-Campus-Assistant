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

if (empty($data['resource_name']) || empty($data['resource_type'])) {
    jsonResponse([], false, 'Resource name and type are required', 400);
}

$allowedTypes = ['classroom', 'lab', 'auditorium', 'other'];
if (!in_array($data['resource_type'], $allowedTypes)) {
    jsonResponse([], false, 'Invalid resource type', 400);
}

try {
    $query = "INSERT INTO resources (resource_name, resource_type, capacity) 
              VALUES (:name, :type, :capacity)";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':name' => sanitizeInput($data['resource_name']),
        ':type' => $data['resource_type'],
        ':capacity' => isset($data['capacity']) ? (int)$data['capacity'] : null
    ]);
    
    $resource_id = $db->lastInsertId();
    
    // Create notification for all students
    $studentsQuery = "SELECT user_id FROM students";
    $studentsStmt = $db->query($studentsQuery);
    $students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($students as $student) {
        $notifQuery = "INSERT INTO notifications (user_id, message, type) 
                       VALUES (:uid, :msg, 'info')";
        $notifStmt = $db->prepare($notifQuery);
        $notifStmt->execute([
            ':uid' => $student['user_id'],
            ':msg' => 'New resource available: ' . sanitizeInput($data['resource_name'])
        ]);
    }
    
    jsonResponse(['resource_id' => $resource_id], true, 'Resource created successfully', 201);
    
} catch(Exception $e) {
    jsonResponse([], false, 'Error: ' . $e->getMessage(), 500);
}
?>
