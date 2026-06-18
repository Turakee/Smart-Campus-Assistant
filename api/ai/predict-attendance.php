<?php
/**
 * AI Attendance Risk Prediction
 * AI-Powered Smart Campus Assistant
 * 
 * Integrates with C++ AI Engine for attendance risk prediction
 */

header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/AIEngine.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([], false, 'Invalid request method', 405);
}

$database = new Database();
$db = $database->getConnection();

try {
    // Get student_id
    $stmt = $db->prepare("SELECT student_id FROM students WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $student = $stmt->fetch();
    
    if (!$student) {
        jsonResponse([], false, 'Student profile not found', 404);
    }
    
    // Get attendance records
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_classes,
            COALESCE(SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END), 0) as present_count,
            COALESCE(SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END), 0) as absent_count,
            COALESCE(SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END), 0) as excused_count
        FROM attendance 
        WHERE student_id = :sid
    ");
    $stmt->execute([':sid' => $student['student_id']]);
    $attendance = $stmt->fetch();
    
    $total = $attendance['total_classes'];
    $present = $attendance['present_count'];
    $excused = $attendance['excused_count'];
    $percentage = $total > 0 ? round((($present + $excused) / $total) * 100, 2) : 0;
    
    // Prepare AI input
    $aiInput = [
        'task' => 'predict-attendance',
        'total_classes' => (int)$total,
        'present_count' => (int)$present,
        'absent_count' => (int)$attendance['absent_count'],
        'excused_count' => (int)$excused
    ];
    
    // Try to use C++ AI Engine
    $aiResult = callAIEngine($aiInput, 'attendance');
    
    if ($aiResult !== null) {
        $riskLevel = $aiResult['risk_level'] ?? 'low';
        $riskScore = $aiResult['risk_score'] ?? (100 - $percentage);
        $recommendations = $aiResult['recommendations'] ?? generateDefaultRecommendations($percentage);
    } else {
        // Fallback to PHP-based prediction
        $riskLevel = calculateRiskLevel($percentage);
        $riskScore = 100 - $percentage;
        $recommendations = generateDefaultRecommendations($percentage);
    }
    
    // Check consecutive absences
    $stmt = $db->prepare("
        SELECT status, date FROM attendance 
        WHERE student_id = :sid 
        ORDER BY date DESC 
        LIMIT 5
    ");
    $stmt->execute([':sid' => $student['student_id']]);
    $recentAttendance = $stmt->fetchAll();
    
    $consecutiveAbsences = 0;
    foreach ($recentAttendance as $record) {
        if ($record['status'] === 'absent') {
            $consecutiveAbsences++;
        } else {
            break;
        }
    }
    
    if ($consecutiveAbsences >= 3) {
        $riskLevel = 'high';
        $riskScore = max($riskScore, 85);
        $recommendations[] = "Warning: {$consecutiveAbsences} consecutive absences detected";
        $recommendations[] = 'Please contact your academic advisor immediately';
    }
    
    // Log prediction
    $logQuery = "INSERT INTO ai_analytics_log 
                 (student_id, prediction_type, prediction_result, risk_level, generated_at) 
                 VALUES (:sid, 'attendance_risk', :result, :risk, NOW())";
    $logStmt = $db->prepare($logQuery);
    $logStmt->execute([
        ':sid' => $student['student_id'],
        ':result' => json_encode([
            'percentage' => $percentage,
            'score' => $riskScore,
            'source' => $aiResult !== null ? 'cpp_engine' : 'php_fallback'
        ]),
        ':risk' => $riskLevel
    ]);
    
    // Create notification for high risk
    if ($riskLevel === 'high') {
        $message = $consecutiveAbsences >= 3
            ? "Attendance Risk Alert: {$consecutiveAbsences} consecutive absences detected — immediate attention required"
            : 'Attendance Risk Alert: Your attendance is below the required threshold';
        
        $checkNotif = $db->prepare("SELECT COUNT(*) as count FROM notifications
                                   WHERE user_id = :uid
                                     AND type = 'danger'
                                     AND message = :msg
                                     AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $checkNotif->execute([
            ':uid' => $_SESSION['user_id'],
            ':msg' => $message
        ]);
        
        if ($checkNotif->fetchColumn() == 0) {
            $notifQuery = "INSERT INTO notifications (user_id, message, type, created_at)
                           VALUES (:uid, :msg, 'danger', NOW())";
            $notifStmt = $db->prepare($notifQuery);
            $notifStmt->execute([
                ':uid' => $_SESSION['user_id'],
                ':msg' => $message
            ]);
        }
    }
    
    jsonResponse([
        'attendance_percentage' => $percentage,
        'total_classes' => $total,
        'present' => $present,
        'absent' => $attendance['absent_count'],
        'risk_level' => $riskLevel,
        'risk_score' => round($riskScore, 2),
        'consecutive_absences' => $consecutiveAbsences,
        'recommendations' => $recommendations,
        'ai_engine_used' => $aiResult !== null
    ], true, 'Attendance risk analysis complete');
    
} catch(Exception $e) {
    jsonResponse([], false, 'Prediction failed: ' . $e->getMessage(), 500);
}

/**
 * Calculate risk level based on attendance percentage
 */
function calculateRiskLevel($percentage) {
    if ($percentage < 75) {
        return 'high';
    } elseif ($percentage < 85) {
        return 'medium';
    }
    return 'low';
}

/**
 * Generate default recommendations based on attendance
 */
function generateDefaultRecommendations($percentage) {
    $recommendations = [];
    
    if ($percentage < 75) {
        $recommendations[] = 'Your attendance is below the required 75% threshold';
        $recommendations[] = 'Consider attending extra sessions or meeting with your instructor';
    } elseif ($percentage < 85) {
        $recommendations[] = 'Your attendance is approaching the risk zone';
        $recommendations[] = 'Try to maintain consistent attendance';
    } else {
        $recommendations[] = 'Great! Maintain your excellent attendance';
    }
    
    return $recommendations;
}
?>
