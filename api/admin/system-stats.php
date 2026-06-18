<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireAuth();
requireRole(['system_admin']);

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get total users
    $stmt = $db->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch()['total'];

    // Get total administrators
    $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role IN ('administrator', 'system_admin')");
    $totalAdmins = $stmt->fetch()['total'];

    // Get total students (active only)
    $stmt = $db->query("SELECT COUNT(*) as total FROM students s JOIN users u ON s.user_id = u.user_id WHERE u.is_active = 1");
    $totalStudents = $stmt->fetch()['total'];

    // Get total courses
    $stmt = $db->query("SELECT COUNT(*) as total FROM courses");
    $totalCourses = $stmt->fetch()['total'];

    // Get total schedules
    $stmt = $db->query("SELECT COUNT(*) as total FROM schedules");
    $totalSchedules = $stmt->fetch()['total'];

    // System status - dynamic check
    try {
        $db->query("SELECT 1");
        $systemStatus = 'Online';
    } catch (Exception $e) {
        $systemStatus = 'Degraded';
    }

    // Last backup - determine from backup directory
    $backupDir = realpath(__DIR__ . '/../../backups');
    $lastBackup = 'Never';
    if ($backupDir && is_dir($backupDir)) {
        $files = glob($backupDir . DIRECTORY_SEPARATOR . 'backup_*.sql');
        if (!empty($files)) {
            usort($files, function ($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            $lastBackup = date('Y-m-d H:i:s', filemtime($files[0]));
        }
    }

    jsonResponse([
        'totalUsers' => $totalUsers,
        'totalAdmins' => $totalAdmins,
        'totalStudents' => $totalStudents,
        'totalCourses' => $totalCourses,
        'totalSchedules' => $totalSchedules,
        'systemStatus' => $systemStatus,
        'lastBackup' => $lastBackup
    ], true, 'System stats retrieved');

} catch (Exception $e) {
    jsonResponse([], false, 'Database error: ' . $e->getMessage(), 500);
}
?>