<?php
/**
 * AI Performance Prediction
 * AI-Powered Smart Campus Assistant
 * 
 * Predicts student academic performance based on attendance and engagement
 */

header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/AIEngine.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([], false, 'Method not allowed', 405);
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
    
    $studentId = $student['student_id'];
    
    // Get attendance stats
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_classes,
            COALESCE(SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END), 0) as present_count,
            COALESCE(SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END), 0) as absent_count,
            COALESCE(SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END), 0) as late_count,
            COALESCE(SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END), 0) as excused_count
        FROM attendance 
        WHERE student_id = :sid
    ");
    $stmt->execute([':sid' => $studentId]);
    $attendance = $stmt->fetch();
    
    $totalClasses = $attendance['total_classes'];
    $presentCount = $attendance['present_count'];
    $excusedCount = $attendance['excused_count'];
    $absentCount = $attendance['absent_count'];
    $lateCount = $attendance['late_count'];
    $goodCount = $presentCount + $excusedCount;
    
    // Calculate attendance percentage (excused counts as attended)
    $attendancePercentage = $totalClasses > 0 ? round(($goodCount / $totalClasses) * 100, 1) : 0;
    
    // Get enrolled courses count
    $stmt = $db->prepare("SELECT COUNT(*) as course_count FROM student_courses WHERE student_id = :sid");
    $stmt->execute([':sid' => $studentId]);
    $enrolledCourses = $stmt->fetchColumn();
    
    // Calculate predicted score based on attendance and engagement factors
    $baseScore = $attendancePercentage;
    $latePenalty = ($lateCount / max($totalClasses, 1)) * 5;
    $predictedScore = max(0, min(100, round($baseScore - $latePenalty, 1)));
    
    // Determine predicted grade
    $predictedGrade = 'F';
    $gradePoints = 0;
    if ($predictedScore >= 90) {
        $predictedGrade = 'A';
        $gradePoints = 4.0;
    } elseif ($predictedScore >= 80) {
        $predictedGrade = 'B';
        $gradePoints = 3.0;
    } elseif ($predictedScore >= 70) {
        $predictedGrade = 'C';
        $gradePoints = 2.0;
    } elseif ($predictedScore >= 60) {
        $predictedGrade = 'D';
        $gradePoints = 1.0;
    }
    
    // Determine risk level based on predicted score
    $riskLevel = 'low';
    if ($predictedScore < 60) {
        $riskLevel = 'high';
    } elseif ($predictedScore < 75) {
        $riskLevel = 'medium';
    }
    
    // Generate recommendations
    $recommendations = [];
    if ($predictedScore >= 90) {
        $recommendations[] = 'Excellent performance! Keep up the great work.';
        $recommendations[] = 'Consider tutoring or mentoring other students.';
    } elseif ($predictedScore >= 80) {
        $recommendations[] = 'Good performance. Aim for excellence by increasing attendance.';
        $recommendations[] = 'Review course materials regularly to maintain high scores.';
    } elseif ($predictedScore >= 70) {
        $recommendations[] = 'Average performance. Focus on improving attendance.';
        $recommendations[] = 'Seek help from instructors for challenging topics.';
    } elseif ($predictedScore >= 60) {
        $recommendations[] = 'Below average. Critical attention needed.';
        $recommendations[] = 'Attend all classes and complete assignments on time.';
        $recommendations[] = 'Consider meeting with academic advisor.';
    } else {
        $recommendations[] = 'At risk of failing. Immediate action required.';
        $recommendations[] = 'Attend all remaining classes without exception.';
        $recommendations[] = 'Contact instructors and academic advisor immediately.';
    }
    
    if ($lateCount > 0) {
        $recommendations[] = 'Reduce late arrivals to improve your standing.';
    }
    
    $aiInput = [
        'task' => 'analyze-performance',
        'total_classes' => (int)$totalClasses,
        'present_count' => (int)$presentCount,
        'absent_count' => (int)$absentCount,
        'late_count' => (int)$lateCount,
        'courses_enrolled' => (int)$enrolledCourses
    ];

    $aiResult = callAIEngine($aiInput, 'performance');
    $aiEngineUsed = false;
    if ($aiResult !== null && isset($aiResult['predicted_score'])) {
        $aiEngineUsed = true;
        $predictedScore = floatval($aiResult['predicted_score']);
        $predictedGrade = $aiResult['predicted_grade'] ?? $predictedGrade;
        $gradePoints = isset($aiResult['grade_points']) ? floatval($aiResult['grade_points']) : $gradePoints;
        $riskLevel = $aiResult['risk_level'] ?? $riskLevel;
        $recommendations = $aiResult['recommendations'] ?? $recommendations;
        $attendancePercentage = isset($aiResult['attendance_percentage']) ? floatval($aiResult['attendance_percentage']) : $attendancePercentage;
    }

    // Log prediction
    $logQuery = "INSERT INTO ai_analytics_log 
                 (student_id, prediction_type, prediction_result, risk_level, generated_at) 
                 VALUES (:sid, 'performance', :result, :risk, NOW())";
    $logStmt = $db->prepare($logQuery);
    $logStmt->execute([
        ':sid' => $studentId,
        ':result' => json_encode([
            'predicted_score' => $predictedScore,
            'predicted_grade' => $predictedGrade,
            'grade_points' => $gradePoints,
            'attendance_percentage' => $attendancePercentage,
            'total_classes' => $totalClasses,
            'present' => $presentCount,
            'excused' => $excusedCount,
            'absent' => $absentCount,
            'late' => $lateCount,
            'courses_enrolled' => $enrolledCourses,
            'source' => $aiEngineUsed ? 'cpp_engine' : 'php_prediction'
        ]),
        ':risk' => $riskLevel
    ]);
    
    // Create notification for high risk
    if ($riskLevel === 'high') {
        $message = 'Performance Alert: Your predicted grade is below passing. Immediate action required!';
        
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
        'predicted_score' => $predictedScore,
        'predicted_grade' => $predictedGrade,
        'grade_points' => $gradePoints,
        'attendance_percentage' => $attendancePercentage,
        'total_classes' => $totalClasses,
        'present' => $presentCount,
        'excused' => $excusedCount,
        'absent' => $absentCount,
        'late' => $lateCount,
        'courses_enrolled' => $enrolledCourses,
        'risk_level' => $riskLevel,
        'recommendations' => $recommendations,
        'ai_engine_used' => $aiEngineUsed
    ], true, 'Performance prediction complete');
    
} catch(Exception $e) {
    jsonResponse([], false, 'Prediction failed: ' . $e->getMessage(), 500);
}
?>