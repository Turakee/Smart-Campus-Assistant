<?php
/**
 * Audit Logger Helper Class
 * Logs all admin actions for compliance and security
 */
class AuditLogger {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Log an admin action
     * @param int $admin_id Admin user ID
     * @param string $action Description of action
     * @param string $action_type Type of action (user_delete, user_role_change, backup, etc)
     * @param array $details Additional details as JSON
     */
    public function log($admin_id, $action, $action_type, $details = []) {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO admin_audit_log (admin_id, action, action_type, details, ip_address, created_at) 
                 VALUES (:admin_id, :action, :action_type, :details, :ip, NOW())"
            );
            
            $stmt->execute([
                ':admin_id' => $admin_id,
                ':action' => $action,
                ':action_type' => $action_type,
                ':details' => json_encode($details),
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Audit logging failed: " . $e->getMessage());
            return false;
        }
    }
    
}
?>
