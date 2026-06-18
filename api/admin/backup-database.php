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

try {
    // Create backups directory if it doesn't exist
    $backupDir = '../../backups/';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }

    // Create backup filename with timestamp
    $backupFile = $backupDir . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    
    // Try to use mysqldump if available
    $mysqldumpPath = null;
    $possiblePaths = ['/usr/bin/mysqldump', '/usr/local/bin/mysqldump', 'C:\\xampp\\mysql\\bin\\mysqldump.exe'];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $mysqldumpPath = $path;
            break;
        }
    }
    
    if ($mysqldumpPath) {
        // Use mysqldump for efficient backup
        $db_host = DB_HOST;
        $db_user = DB_USER;
        $db_pass = DB_PASSWORD;
        $db_name = DB_NAME;
        
        // Build mysqldump command
        $cmd = $mysqldumpPath . 
               ' -h ' . escapeshellarg($db_host) . 
               ' -u ' . escapeshellarg($db_user);
        
        // Use environment variable for password instead of command line for security
        if (!empty($db_pass)) {
            putenv('MYSQL_PWD=' . $db_pass);
        }
        
        $cmd .= ' ' . escapeshellarg($db_name) . ' > ' . escapeshellarg($backupFile);
        
        exec($cmd, $output, $returnCode);
        
        // Clear password from environment
        if (!empty($db_pass)) {
            putenv('MYSQL_PWD');
        }
        
        if ($returnCode !== 0) {
            throw new Exception('mysqldump failed with return code: ' . $returnCode);
        }
    } else {
        // Fallback: Use PHP method
        $backupContent = "-- Smart Campus Database Backup\n";
        $backupContent .= "-- Created: " . date('Y-m-d H:i:s') . "\n";
        $backupContent .= "-- Database: " . DB_NAME . "\n\n";
        $backupContent .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        // Get all tables
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $backupContent .= "-- Table: $table\n";
            $backupContent .= "DROP TABLE IF EXISTS `$table`;\n";

            // Get create table statement
            $createTable = $db->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
            $backupContent .= $createTable[1] . ";\n\n";

            // Get data (skip if table is empty)
            try {
                $data = $db->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($data)) {
                    $backupContent .= "INSERT INTO `$table` VALUES\n";
                    $values = [];
                    foreach ($data as $row) {
                        $rowValues = [];
                        foreach ($row as $value) {
                            $rowValues[] = is_null($value) ? 'NULL' : $db->quote($value);
                        }
                        $values[] = "(" . implode(", ", $rowValues) . ")";
                    }
                    $backupContent .= implode(",\n", $values) . ";\n\n";
                }
            } catch (Exception $e) {
                // Skip tables that can't be selected (e.g., temporary tables)
                error_log("Warning: Could not backup data from table $table: " . $e->getMessage());
            }
        }
        
        $backupContent .= "SET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents($backupFile, $backupContent);
    }

    if (!file_exists($backupFile)) {
        throw new Exception('Backup file was not created');
    }
    
    $fileSize = filesize($backupFile);
    
    // Log the action
    $logger->log($_SESSION['user_id'], 
                 'Created database backup: ' . basename($backupFile) . ' (' . round($fileSize / 1024 / 1024, 2) . ' MB)', 
                 'backup_created', 
                 ['backup_file' => basename($backupFile), 'file_size' => $fileSize]);

    jsonResponse([
        'backup_file' => basename($backupFile),
        'size' => $fileSize,
        'size_formatted' => round($fileSize / 1024 / 1024, 2) . ' MB'
    ], true, 'Database backup created successfully');

} catch (Exception $e) {
    jsonResponse([], false, 'Backup error: ' . $e->getMessage(), 500);
}
?>