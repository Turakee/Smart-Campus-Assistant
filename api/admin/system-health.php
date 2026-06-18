<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireAuth();
requireRole(['administrator', 'system_admin']);

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $health = [];
    
    // 1. MySQL Database
    try {
        $db->query("SELECT 1");
        $health['mysql_database'] = ['status' => 'healthy', 'message' => 'Database connection OK'];
    } catch (Exception $e) {
        $health['mysql_database'] = ['status' => 'critical', 'message' => 'Database connection failed'];
    }
    
    // 2. Web Server
    if (function_exists('gethostname')) {
        $health['web_server'] = ['status' => 'healthy', 'message' => 'Web server running'];
    }
    
    // 3. Mail Service (check if mail function is available)
    $health['mail_service'] = [
        'status' => function_exists('mail') ? 'healthy' : 'degraded',
        'message' => function_exists('mail') ? 'Mail service available' : 'Mail service not configured'
    ];
    
    // 4. AI Engine (check if AI log table has recent entries)
    try {
        $recentAI = $db->query("SELECT COUNT(*) as count FROM ai_analytics_log WHERE generated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch();
        $aiStatus = $recentAI['count'] > 0 ? 'healthy' : 'idle';
        $health['ai_engine'] = ['status' => $aiStatus, 'message' => $recentAI['count'] . ' AI predictions in last 24h'];
    } catch (Exception $e) {
        $health['ai_engine'] = ['status' => 'unavailable', 'message' => 'AI log table not accessible'];
    }
    
    // 5. Backup Service (check recent backups)
    $backupDir = '../../backups/';
    $backups = [];
    if (is_dir($backupDir)) {
        $files = scandir($backupDir, SCANDIR_SORT_DESCENDING);
        foreach ($files as $file) {
            if (strpos($file, 'backup_') === 0 && strpos($file, '.sql') !== false) {
                $backups[] = filemtime($backupDir . $file);
            }
        }
    }
    
    if (!empty($backups)) {
        $lastBackup = $backups[0];
        $hoursSinceBackup = round((time() - $lastBackup) / 3600);
        $status = $hoursSinceBackup <= 24 ? 'healthy' : ($hoursSinceBackup <= 72 ? 'warning' : 'critical');
        $health['backup_service'] = [
            'status' => $status,
            'message' => $hoursSinceBackup . ' hours since last backup'
        ];
    } else {
        $health['backup_service'] = ['status' => 'warning', 'message' => 'No backups found'];
    }
    
    // 6. SSL Certificate (check if HTTPS is enabled)
    $health['ssl_certificate'] = [
        'status' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'healthy' : 'warning',
        'message' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'SSL enabled' : 'Running on HTTP'
    ];
    
    // Count healthy/warning/critical
    $statusCounts = ['healthy' => 0, 'warning' => 0, 'critical' => 0, 'degraded' => 0, 'idle' => 0, 'unavailable' => 0];
    foreach ($health as $service) {
        $status = $service['status'];
        if (isset($statusCounts[$status])) {
            $statusCounts[$status]++;
        }
    }
    
    jsonResponse([
        'services' => $health,
        'summary' => $statusCounts,
        'overall_status' => $statusCounts['critical'] > 0 ? 'critical' : ($statusCounts['warning'] > 0 ? 'warning' : 'healthy')
    ], true, 'System health checked');
    
} catch(Exception $e) {
    jsonResponse([], false, 'Error checking system health: ' . $e->getMessage(), 500);
}
?>
