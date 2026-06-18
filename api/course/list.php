<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireAuth();

$database = new Database();
$db = $database->getConnection();

try {
    $sql = "SELECT c.*, d.department_name, d.department_code 
            FROM courses c 
            LEFT JOIN departments d ON c.department_id = d.department_id";
    $params = [];

    if (!empty($_GET['department_id'])) {
        $sql .= " WHERE c.department_id = :dept_id";
        $params[':dept_id'] = (int)$_GET['department_id'];
    }

    $sql .= " ORDER BY c.course_code";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $courses = $stmt->fetchAll();
    jsonResponse($courses, true, 'Courses retrieved');
} catch(Exception $e) {
    jsonResponse([], false, 'Server error', 500);
}
?>