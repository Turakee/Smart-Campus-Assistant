<?php
/**
 * Change Password API Endpoint
 * Allows authenticated users to change their password
 */

include_once '../../config/config.php';
include_once '../../config/database.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([], false, 'Method not allowed', 405);
}

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['current_password']) || empty($data['new_password'])) {
    jsonResponse([], false, 'Current password and new password are required', 400);
}

if (strlen($data['new_password']) < 6) {
    jsonResponse([], false, 'New password must be at least 6 characters', 400);
}

try {
    // Verify current password
    $stmt = $db->prepare("SELECT password_hash FROM users WHERE user_id = :uid");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse([], false, 'User not found', 404);
    }

    if (!password_verify($data['current_password'], $user['password_hash'])) {
        jsonResponse([], false, 'Current password is incorrect', 401);
    }

    // Hash and update new password
    $newHash = password_hash($data['new_password'], PASSWORD_BCRYPT, ['cost' => 12]);

    $update = $db->prepare("UPDATE users SET password_hash = :hash WHERE user_id = :uid");
    $update->execute([
        ':hash' => $newHash,
        ':uid' => $_SESSION['user_id']
    ]);

    jsonResponse([], true, 'Password changed successfully');

} catch (Exception $e) {
    jsonResponse([], false, 'Server error: ' . $e->getMessage(), 500);
}
?>
