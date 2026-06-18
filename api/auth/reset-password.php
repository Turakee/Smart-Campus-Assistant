<?php

include_once '../../config/config.php';
include_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([], false, 'Method not allowed', 405);
}

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['email']) || empty($data['new_password'])) {
    jsonResponse([], false, 'Email and new password are required', 400);
}

if (strlen($data['new_password']) < 6) {
    jsonResponse([], false, 'Password must be at least 6 characters', 400);
}

try {
    $stmt = $db->prepare("SELECT user_id FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $data['email']]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse([], false, 'No account found with that email', 404);
    }

    $newHash = password_hash($data['new_password'], PASSWORD_BCRYPT, ['cost' => 12]);
    $update = $db->prepare("UPDATE users SET password_hash = :hash WHERE user_id = :uid");
    $update->execute([
        ':hash' => $newHash,
        ':uid' => $user['user_id']
    ]);

    jsonResponse([], true, 'Password has been reset successfully');
} catch (Exception $e) {
    jsonResponse([], false, 'Server error: ' . $e->getMessage(), 500);
}
?>
