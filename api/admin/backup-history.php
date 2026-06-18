<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireAuth();
requireRole(['system_admin']);

try {
    $backupDir = realpath(__DIR__ . '/../../backups');
    if (!$backupDir || !is_dir($backupDir)) {
        jsonResponse([], true, 'No backups directory');
    }

    $files = glob($backupDir . DIRECTORY_SEPARATOR . 'backup_*.sql');
    if (!$files) {
        jsonResponse([], true, 'No backups found');
    }

    usort($files, function ($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    $history = [];
    foreach ($files as $file) {
        $history[] = [
            'fileName' => basename($file),
            'createdAt' => date('Y-m-d H:i:s', filemtime($file)),
            'size' => filesize($file)
        ];
    }

    jsonResponse($history, true, 'Backup history retrieved');

} catch (Exception $e) {
    jsonResponse([], false, 'Backup history error: ' . $e->getMessage(), 500);
}
?>