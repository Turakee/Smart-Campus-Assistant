<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireAuth();
requireRole(['administrator', 'system_admin']);

$database = new Database();
$db = $database->getConnection();

try {
    // Get recent activity from audit log
    $query = "SELECT 
                aal.log_id,
                aal.action,
                aal.action_type,
                aal.created_at,
                u.username as admin_name,
                TIMESTAMPDIFF(MINUTE, aal.created_at, NOW()) as minutes_ago
              FROM admin_audit_log aal
              LEFT JOIN users u ON aal.admin_id = u.user_id
              ORDER BY aal.created_at DESC
              LIMIT 20";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $activities = $stmt->fetchAll();
    
    // Format activities for display
    $formatted = [];
    foreach ($activities as $activity) {
        $timeText = 'just now';
        if ($activity['minutes_ago'] > 60) {
            $hoursAgo = floor($activity['minutes_ago'] / 60);
            $timeText = $hoursAgo . ' hour' . ($hoursAgo > 1 ? 's' : '') . ' ago';
        } elseif ($activity['minutes_ago'] > 1) {
            $timeText = $activity['minutes_ago'] . ' minutes ago';
        }
        
        $formatted[] = [
            'id' => $activity['log_id'],
            'action' => $activity['action'],
            'action_type' => $activity['action_type'],
            'admin' => $activity['admin_name'],
            'timestamp' => $activity['created_at'],
            'time_text' => $timeText,
            'icon' => getActivityIcon($activity['action_type'])
        ];
    }
    
    jsonResponse(['activities' => $formatted], true, 'Activities retrieved');
    
} catch(Exception $e) {
    jsonResponse([], false, 'Error retrieving activities: ' . $e->getMessage(), 500);
}

function getActivityIcon($actionType) {
    $icons = [
        'user_delete' => 'fas fa-user-slash',
        'user_role_change' => 'fas fa-user-tie',
        'backup_created' => 'fas fa-database',
        'backup_restore' => 'fas fa-redo',
        'announcement_created' => 'fas fa-bullhorn',
        'event_created' => 'fas fa-calendar-check',
        'message_sent' => 'fas fa-envelope',
        'settings_updated' => 'fas fa-cog'
    ];
    return $icons[$actionType] ?? 'fas fa-info-circle';
}
?>
