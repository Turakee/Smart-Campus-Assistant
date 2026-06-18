<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireAuth();
requireRole(['system_admin']);

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->query("SELECT u.user_id, u.username, u.email, u.role, u.is_active, u.created_at,
                               COALESCE(a.full_name, s.full_name, u.username) as full_name
                        FROM users u
                        LEFT JOIN administrators a ON u.user_id = a.user_id
                        LEFT JOIN students s ON u.user_id = s.user_id
                        ORDER BY u.user_id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as &$user) {
        $user['is_active'] = (bool)$user['is_active'];
    }

    jsonResponse($users, true, 'Users retrieved');

} catch (Exception $e) {
    jsonResponse([], false, 'Database error: ' . $e->getMessage(), 500);
}
?>