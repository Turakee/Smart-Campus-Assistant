<?php
include_once '../../config/config.php';
include_once '../../config/database.php';

requireAuth();

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $query = "SELECT a.admin_id, a.full_name, a.position,
                         u.username, u.email, u.user_id, u.role, u.last_login
                  FROM administrators a
                  JOIN users u ON a.user_id = u.user_id
                  WHERE u.user_id = :user_id
                  LIMIT 1";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            jsonResponse([], false, 'Profile not found', 404);
        }

        $profile = $stmt->fetch();
        jsonResponse($profile, true, 'Profile retrieved successfully');

    } catch (\PDOException $e) {
        jsonResponse([], false, 'Database error: ' . $e->getMessage(), 500);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['full_name']) || strlen(trim($input['full_name'])) < 2) {
            jsonResponse([], false, 'Full name is required (min 2 characters)', 400);
        }

        $query = "UPDATE administrators 
                  SET full_name = :full_name, 
                      position = :position
                  WHERE user_id = :user_id";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':full_name', $input['full_name']);
        $stmt->bindParam(':position', $input['position'] ?? null);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);

        if ($stmt->execute()) {
            $_SESSION['name'] = $input['full_name'];
            jsonResponse([], true, 'Profile updated successfully');
        } else {
            jsonResponse([], false, 'Failed to update profile', 500);
        }

    } catch (\PDOException $e) {
        jsonResponse([], false, 'Database error: ' . $e->getMessage(), 500);
    }

} else {
    jsonResponse([], false, 'Method not allowed', 405);
}
