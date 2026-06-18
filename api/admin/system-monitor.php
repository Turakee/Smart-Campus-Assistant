<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireAuth();
requireRole(['system_admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse([], false, 'Method not allowed', 405);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Server status — try a simple query
    $serverOnline = false;
    $dbConnections = 0;
    $responseTime = 0;
    $activeSessions = 0;
    $dbSize = '0 MB';
    $phpVersion = phpversion();
    $mysqlVersion = '';

    if ($db) {
        $start = microtime(true);
        
        $stmt = $db->query("SELECT 1");
        $stmt->fetch();
        $serverOnline = true;

        $responseTime = round((microtime(true) - $start) * 1000, 0);

        // DB connections
        $stmt = $db->query("SHOW STATUS LIKE 'Threads_connected'");
        $row = $stmt->fetch();
        $dbConnections = (int)$row['Value'];

        // Active processes
        $stmt = $db->query("SHOW STATUS LIKE 'Threads_running'");
        $row = $stmt->fetch();
        $activeSessions = (int)$row['Value'];

        // MySQL version
        $stmt = $db->query("SELECT VERSION() as v");
        $row = $stmt->fetch();
        $mysqlVersion = $row['v'];

        // DB size
        $stmt = $db->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) as size_mb FROM information_schema.tables WHERE table_schema = DATABASE()");
        $row = $stmt->fetch();
        $dbSize = ($row['size_mb'] ?: 0) . ' MB';
    }

    // Recent logs (from PHP error_log, last few lines)
    $logLines = [];
    $logFile = ini_get('error_log');
    if ($logFile && file_exists($logFile)) {
        $lines = file($logFile);
        $recent = array_slice($lines, -10);
        foreach ($recent as $line) {
            $logLines[] = trim($line);
        }
    }

    // Uptime
    $uptime = 'N/A';
    if ($db) {
        $stmt = $db->query("SHOW STATUS LIKE 'Uptime'");
        $row = $stmt->fetch();
        $uptimeSec = (int)$row['Value'];
        $days = floor($uptimeSec / 86400);
        $hours = floor(($uptimeSec % 86400) / 3600);
        $mins = floor(($uptimeSec % 3600) / 60);
        $uptime = "{$days}d {$hours}h {$mins}m";
    }

    // CPU Usage
    $cpuUsage = 0;
    if (function_exists('sys_getloadavg')) {
        $load = sys_getloadavg();
        $cpuUsage = round($load[0] * 100, 0);
    } elseif (PHP_OS_FAMILY === 'Windows') {
        $cpuOutput = shell_exec('powershell -Command "Get-CimInstance Win32_Processor | Measure-Object -Property LoadPercentage -Average | Select-Object -ExpandProperty Average" 2>&1');
        if ($cpuOutput !== null && is_numeric(trim($cpuOutput))) {
            $cpuUsage = (int)trim($cpuOutput);
        }
    }

    // Memory Usage
    $memoryUsage = 0;
    $memoryTotal = 'N/A';
    $memoryUsed = 'N/A';
    if (PHP_OS_FAMILY === 'Windows') {
        $memOutput = shell_exec('powershell -Command "Get-CimInstance Win32_OperatingSystem | Select-Object TotalVisibleMemorySize,FreePhysicalMemory | Format-List" 2>&1');
        if ($memOutput) {
            preg_match('/TotalVisibleMemorySize\s*:\s*(\d+)/', $memOutput, $totalMatch);
            preg_match('/FreePhysicalMemory\s*:\s*(\d+)/', $memOutput, $freeMatch);
            if (!empty($totalMatch[1]) && !empty($freeMatch[1]) && $totalMatch[1] > 0) {
                $totalMemKB = (int)$totalMatch[1];
                $freeMemKB = (int)$freeMatch[1];
                $usedMemKB = $totalMemKB - $freeMemKB;
                $memoryUsage = round(($usedMemKB / $totalMemKB) * 100, 0);
                $memoryTotal = round($totalMemKB / 1024 / 1024, 1) . ' GB';
                $memoryUsed = round($usedMemKB / 1024 / 1024, 1) . ' GB';
            }
        }
    } else {
        $memOutput = @file_get_contents('/proc/meminfo');
        if ($memOutput) {
            preg_match('/MemTotal:\s+(\d+)/', $memOutput, $totalMatch);
            preg_match('/MemAvailable:\s+(\d+)/', $memOutput, $availMatch);
            if (!empty($totalMatch[1]) && !empty($availMatch[1]) && $totalMatch[1] > 0) {
                $totalMemKB = (int)$totalMatch[1];
                $availMemKB = (int)$availMatch[1];
                $usedMemKB = $totalMemKB - $availMemKB;
                $memoryUsage = round(($usedMemKB / $totalMemKB) * 100, 0);
                $memoryTotal = round($totalMemKB / 1024 / 1024, 1) . ' GB';
                $memoryUsed = round($usedMemKB / 1024 / 1024, 1) . ' GB';
            }
        }
    }

    // Disk Usage
    $diskUsage = 0;
    $diskTotal = 'N/A';
    $diskUsed = 'N/A';
    $diskPath = __DIR__ . '/../../';
    $totalSpace = @disk_total_space($diskPath);
    $freeSpace = @disk_free_space($diskPath);
    if ($totalSpace && $totalSpace > 0) {
        $usedSpace = $totalSpace - $freeSpace;
        $diskUsage = round(($usedSpace / $totalSpace) * 100, 0);
        $diskTotal = round($totalSpace / 1024 / 1024 / 1024, 1) . ' GB';
        $diskUsed = round($usedSpace / 1024 / 1024 / 1024, 1) . ' GB';
    }

    jsonResponse([
        'server_status' => $serverOnline ? 'Online' : 'Offline',
        'server_online' => $serverOnline,
        'php_version' => $phpVersion,
        'mysql_version' => $mysqlVersion,
        'db_connections' => $dbConnections,
        'active_sessions' => $activeSessions,
        'response_time' => $responseTime . 'ms',
        'db_size' => $dbSize,
        'uptime' => $uptime,
        'cpu_usage' => $cpuUsage,
        'memory_usage' => $memoryUsage,
        'memory_total' => $memoryTotal,
        'memory_used' => $memoryUsed,
        'disk_usage' => $diskUsage,
        'disk_total' => $diskTotal,
        'disk_used' => $diskUsed,
        'logs' => $logLines,
        'last_checked' => date('H:i:s')
    ], true, 'System monitoring data retrieved');
} catch (Exception $e) {
    jsonResponse([
        'server_status' => 'Degraded',
        'server_online' => false,
        'error' => $e->getMessage(),
        'last_checked' => date('H:i:s')
    ], true, 'Partial monitoring data');
}
