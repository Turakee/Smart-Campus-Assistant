<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse([], false, 'Invalid request method', 405);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->query("SELECT department_id, department_name, department_code FROM departments ORDER BY department_name");
    $departments = $stmt->fetchAll();

    jsonResponse($departments, true, 'Departments retrieved');
} catch (Exception $e) {
    error_log('Department list error: ' . $e->getMessage());
    jsonResponse([], false, 'Failed to load departments', 500);
}
