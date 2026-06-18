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

$settingsFile = __DIR__ . '/../../config/settings.json';

$defaults = [
    'system_name' => 'Smart Campus Assistant',
    'max_login_attempts' => 5,
    'session_timeout' => 60,
    'enable_ai' => true,
    'backup_interval_hours' => 24
];

function loadSettings($file, $defaults) {
    if (!file_exists($file)) {
        return $defaults;
    }
    $saved = json_decode(file_get_contents($file), true);
    if (!is_array($saved)) {
        return $defaults;
    }
    return array_merge($defaults, $saved);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $settings = loadSettings($settingsFile, $defaults);
    jsonResponse($settings, true, 'Settings retrieved successfully');

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);

    $current = loadSettings($settingsFile, $defaults);
    $changedFields = [];

    if (isset($input['system_name'])) {
        $val = trim($input['system_name']);
        // Sanitize system name - remove special characters that could cause issues
        $val = preg_replace('/[^a-zA-Z0-9\s\-._]/', '', $val);
        if (strlen($val) < 2 || strlen($val) > 100) {
            jsonResponse([], false, 'System name must be 2-100 characters', 400);
        }
        if ($current['system_name'] !== $val) {
            $changedFields['system_name'] = ['from' => $current['system_name'], 'to' => $val];
            $current['system_name'] = $val;
        }
    }

    if (isset($input['max_login_attempts'])) {
        $val = (int)$input['max_login_attempts'];
        if ($val < 1 || $val > 20) {
            jsonResponse([], false, 'Max login attempts must be 1-20', 400);
        }
        if ($current['max_login_attempts'] !== $val) {
            $changedFields['max_login_attempts'] = ['from' => $current['max_login_attempts'], 'to' => $val];
            $current['max_login_attempts'] = $val;
        }
    }

    if (isset($input['session_timeout'])) {
        $val = (int)$input['session_timeout'];
        if ($val < 5 || $val > 1440) {
            jsonResponse([], false, 'Session timeout must be 5-1440 minutes', 400);
        }
        if ($current['session_timeout'] !== $val) {
            $changedFields['session_timeout'] = ['from' => $current['session_timeout'], 'to' => $val];
            $current['session_timeout'] = $val;
        }
    }

    if (isset($input['enable_ai'])) {
        $val = (bool)$input['enable_ai'];
        if ($current['enable_ai'] !== $val) {
            $changedFields['enable_ai'] = ['from' => $current['enable_ai'], 'to' => $val];
            $current['enable_ai'] = $val;
        }
    }

    if (isset($input['backup_interval_hours'])) {
        $val = (int)$input['backup_interval_hours'];
        if ($val < 1 || $val > 168) {
            jsonResponse([], false, 'Backup interval must be 1-168 hours', 400);
        }
        if ($current['backup_interval_hours'] !== $val) {
            $changedFields['backup_interval_hours'] = ['from' => $current['backup_interval_hours'], 'to' => $val];
            $current['backup_interval_hours'] = $val;
        }
    }

    $dir = dirname($settingsFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    if (file_put_contents($settingsFile, json_encode($current, JSON_PRETTY_PRINT))) {
        // Log only if changes were made
        if (!empty($changedFields)) {
            $logger->log($_SESSION['user_id'], 
                        'Updated system settings - ' . count($changedFields) . ' field(s) changed', 
                        'settings_updated', 
                        $changedFields);
        }
        jsonResponse($current, true, 'Settings saved successfully');
    } else {
        jsonResponse([], false, 'Failed to save settings', 500);
    }

} else {
    jsonResponse([], false, 'Method not allowed', 405);
}
