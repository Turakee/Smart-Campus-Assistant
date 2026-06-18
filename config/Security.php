<?php

class Security
{
    public static function logSecurityEvent($event, $severity = 'INFO', $details = '')
    {
        $logFile = __DIR__ . '/../logs/security.log';

        if (!is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }

        $message = sprintf(
            "[%s] %s - %s - IP: %s - User: %s - %s\n",
            date('Y-m-d H:i:s'),
            $severity,
            $event,
            Utilities::getClientIP(),
            $_SESSION['user_id'] ?? 'anonymous',
            $details
        );

        file_put_contents($logFile, $message, FILE_APPEND);
    }
}
