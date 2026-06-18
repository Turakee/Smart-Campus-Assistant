<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireAuth();
requireRole(['administrator', 'system_admin']);

$database = new Database();
$db = $database->getConnection();

try {
    // Get all analytics logs with student names
    $query = "SELECT al.log_id, al.student_id, al.prediction_type, al.prediction_result, 
                     al.risk_level, al.generated_at, s.full_name as student_name
              FROM ai_analytics_log al
              JOIN students s ON al.student_id = s.student_id
              ORDER BY al.generated_at DESC
              LIMIT 100";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $logs = $stmt->fetchAll();
    
    // Parse JSON prediction results
    foreach ($logs as &$log) {
        if (is_string($log['prediction_result'])) {
            $log['prediction_result'] = json_decode($log['prediction_result'], true);
        }
    }
    
    // Get stats
    $statsQuery = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN risk_level = 'high' THEN 1 ELSE 0 END) as high_risk,
        SUM(CASE WHEN risk_level = 'medium' THEN 1 ELSE 0 END) as medium_risk,
        SUM(CASE WHEN prediction_type = 'schedule_optimization' THEN 1 ELSE 0 END) as schedule_optimizations,
        SUM(CASE WHEN prediction_type = 'attendance_risk' THEN 1 ELSE 0 END) as attendance_analyses
    FROM ai_analytics_log";
    
    $stmt = $db->prepare($statsQuery);
    $stmt->execute();
    $stats = $stmt->fetch();
    
    // Get unique students at high risk from analytics log
    $riskQuery = "SELECT DISTINCT al.student_id, s.full_name as student_name
                  FROM ai_analytics_log al
                  JOIN students s ON al.student_id = s.student_id
                  WHERE al.risk_level = 'high'
                  AND al.generated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    
    $stmt = $db->prepare($riskQuery);
    $stmt->execute();
    $highRiskStudents = $stmt->fetchAll();
    
    // Also get students with low attendance (below 75%)
    $attQuery = "SELECT 
        st.student_id,
        st.full_name as student_name,
        COALESCE(att_stats.total, 0) as total_classes,
        COALESCE(att_stats.present, 0) as present_count
    FROM students st
    LEFT JOIN (
        SELECT 
            student_id,
            COUNT(*) as total,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present
        FROM attendance 
        GROUP BY student_id
    ) att_stats ON st.student_id = att_stats.student_id
    WHERE att_stats.total > 0
    AND (att_stats.present / att_stats.total) < 0.75
    ORDER BY (att_stats.present / att_stats.total) ASC
    LIMIT 20";
    
    $stmt = $db->prepare($attQuery);
    $stmt->execute();
    $lowAttendanceStudents = $stmt->fetchAll();
    
    // Merge and deduplicate students
    $allAtRiskStudents = [];
    $seenIds = [];
    
    foreach ($highRiskStudents as $student) {
        if (!in_array($student['student_id'], $seenIds)) {
            $seenIds[] = $student['student_id'];
            $allAtRiskStudents[] = $student;
        }
    }
    
    foreach ($lowAttendanceStudents as $student) {
        if (!in_array($student['student_id'], $seenIds)) {
            $seenIds[] = $student['student_id'];
            $allAtRiskStudents[] = $student;
        }
    }
    
    // Get attendance data for all at-risk students
    foreach ($allAtRiskStudents as &$student) {
        $attQuery = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present
            FROM attendance WHERE student_id = :sid";
        $stmt = $db->prepare($attQuery);
        $stmt->execute([':sid' => $student['student_id']]);
        $att = $stmt->fetch();
        
        $student['attendance_percentage'] = $att['total'] > 0 ? round(($att['present'] / $att['total']) * 100) : 0;
    }
    
    jsonResponse([
        'logs' => $logs,
        'stats' => $stats,
        'high_risk_students' => $allAtRiskStudents
    ], true, 'Analytics retrieved');
    
} catch(Exception $e) {
    jsonResponse([], false, 'Server error: ' . $e->getMessage(), 500);
}
?>
