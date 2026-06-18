<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/AuditLogger.php';

requireAuth();
requireRole(['system_admin']);

$database = new Database();
$db = $database->getConnection();
$logger = new AuditLogger($db);

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['user_id']) || empty($data['role'])) {
    jsonResponse([], false, 'Missing required fields', 400);
}

$userId = (int)$data['user_id'];
$role = trim($data['role']);

// Prevent self-demotion
if ($userId === (int)$_SESSION['user_id']) {
    jsonResponse([], false, 'You cannot change your own role', 403);
}

// Validate role
$validRoles = ['student', 'administrator', 'system_admin'];
if (!in_array($role, $validRoles)) {
    jsonResponse([], false, 'Invalid role. Must be: ' . implode(', ', $validRoles), 400);
}

try {
    // Get current user info
    $getUserStmt = $db->prepare("SELECT user_id, username, role FROM users WHERE user_id = :user_id LIMIT 1");
    $getUserStmt->execute([':user_id' => $userId]);
    $user = $getUserStmt->fetch();
    
    if (!$user) {
        jsonResponse([], false, 'User not found', 404);
    }
    
    $oldRole = $user['role'];
    
    // Update user role
    $updateStmt = $db->prepare("UPDATE users SET role = :role WHERE user_id = :user_id");
    $updateStmt->execute([
        ':role' => $role,
        ':user_id' => $userId
    ]);
    
    // Log the action
    $logger->log($_SESSION['user_id'], 
                 'Changed user ' . $user['username'] . ' role from ' . $oldRole . ' to ' . $role, 
                 'user_role_change', 
                 ['user_id' => $userId, 'username' => $user['username'], 'old_role' => $oldRole, 'new_role' => $role]);

    jsonResponse([], true, 'User role updated successfully from ' . $oldRole . ' to ' . $role);

} catch (Exception $e) {
    jsonResponse([], false, 'Error updating user role: ' . $e->getMessage(), 500);
}
?>