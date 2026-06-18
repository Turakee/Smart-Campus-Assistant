<?php
include_once '../../config/config.php';
include_once '../../config/database.php';

requireAuth();

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $db->prepare("
            SELECT u.user_id, u.username, u.email, u.role, u.created_at, u.last_login,
                   COALESCE(a.full_name, s.full_name, 'User') AS name,
                   a.position
            FROM users u
            LEFT JOIN administrators a ON u.user_id = a.user_id
            LEFT JOIN students s ON u.user_id = s.user_id
            WHERE u.user_id = :user_id
            LIMIT 1
        ");
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            jsonResponse([], false, 'User not found', 404);
        }

        jsonResponse($stmt->fetch(), true, 'Profile retrieved');
    } catch (\PDOException $e) {
        jsonResponse([], false, 'Database error', 500);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        $input = json_decode(file_get_contents("php://input"), true);
        $name = trim($input['name'] ?? '');
        if (strlen($name) < 2) {
            jsonResponse([], false, 'Name is required (min 2 characters)', 400);
        }

        $stmt = $db->prepare("UPDATE administrators SET full_name = :name WHERE user_id = :uid");
        $stmt->execute([':name' => $name, ':uid' => $_SESSION['user_id']]);
        if ($stmt->rowCount() === 0) {
            $stmt = $db->prepare("UPDATE students SET full_name = :name WHERE user_id = :uid");
            $stmt->execute([':name' => $name, ':uid' => $_SESSION['user_id']]);
        }

        $_SESSION['name'] = $name;
        jsonResponse([], true, 'Profile updated');
    } catch (\PDOException $e) {
        jsonResponse([], false, 'Database error', 500);
    }

} else {
    jsonResponse([], false, 'Method not allowed', 405);
}
