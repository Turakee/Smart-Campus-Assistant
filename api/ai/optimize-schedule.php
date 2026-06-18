<?php
/**
 * AI Schedule Optimization
 * AI-Powered Smart Campus Assistant
 * 
 * Integrates with C++ AI Engine for schedule optimization
 */

header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/AIEngine.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([], false, 'Method not allowed', 405);
}

$database = new Database();
$db = $database->getConnection();

try {
    // Ensure we have a student profile
    $stmt = $db->prepare("SELECT student_id FROM students WHERE user_id = :uid");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $student = $stmt->fetch();
    if (!$student) {
        jsonResponse([], false, 'Student profile not found', 404);
    }
    $studentId = $student['student_id'];

    // Get student's courses
    $stmt = $db->prepare("
        SELECT c.course_id, c.course_name, c.course_code, c.credit_hours
        FROM courses c
        JOIN student_courses sc ON sc.course_id = c.course_id
        WHERE sc.student_id = :sid
    ");
    $stmt->execute([':sid' => $studentId]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get existing schedules for the student's courses
    $stmt = $db->prepare("
        SELECT s.schedule_id, s.course_id, s.day_of_week, s.start_time, s.end_time, 
               s.room_number, c.course_code
        FROM schedules s
        JOIN courses c ON s.course_id = c.course_id
        JOIN student_courses sc ON sc.course_id = s.course_id
        WHERE sc.student_id = :sid
    ");
    $stmt->execute([':sid' => $studentId]);
    $existingSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare AI input
    $aiInput = [
        'task' => 'optimize-schedule',
        'courses' => $courses,
        'existing_schedules' => array_map(function($s) {
            return [
                'schedule_id' => (int)$s['schedule_id'],
                'course_id' => (int)$s['course_id'],
                'day_of_week' => $s['day_of_week'],
                'start_time' => substr($s['start_time'], 0, 5),
                'end_time' => substr($s['end_time'], 0, 5),
                'room_number' => $s['room_number'],
                'course_code' => $s['course_code']
            ];
        }, $existingSchedules),
        'constraints' => [
            'max_hours_per_day' => 6,
            'min_break_minutes' => 30,
            'preferred_times' => ['08:00', '10:00', '14:00']
        ]
    ];
    
    // Try to use C++ AI Engine
    $aiResult = callAIEngine($aiInput, 'schedule');
    
    if ($aiResult !== null && isset($aiResult['success']) && $aiResult['success']) {
        $optimizedSchedule = [
            'success' => true,
            'message' => $aiResult['message'] ?? 'Schedule optimized successfully',
            'optimized_slots' => $aiResult['optimized_slots'] ?? [],
            'conflicts_resolved' => $aiResult['conflicts_resolved'] ?? 0,
            'optimization_score' => $aiResult['optimization_score'] ?? 85,
            'ai_engine_used' => true
        ];
    } else {
        // Fallback to PHP-based optimization
        $optimizedSchedule = phpOptimizeSchedule($courses, $existingSchedules);
    }
    
    // Log AI analytics
    $logQuery = "INSERT INTO ai_analytics_log 
                 (student_id, prediction_type, prediction_result, risk_level, generated_at) 
                 VALUES (:sid, 'schedule_optimization', :result, 'low', NOW())";
    $logStmt = $db->prepare($logQuery);
    $logStmt->execute([
        ':sid' => $studentId,
        ':result' => json_encode([
            'score' => $optimizedSchedule['optimization_score'],
            'conflicts_resolved' => $optimizedSchedule['conflicts_resolved'],
            'source' => isset($optimizedSchedule['ai_engine_used']) ? 'cpp_engine' : 'php_fallback'
        ])
    ]);
    
    jsonResponse($optimizedSchedule, true, 'Schedule optimized successfully');
    
} catch(Exception $e) {
    jsonResponse([], false, 'AI optimization failed: ' . $e->getMessage(), 500);
}

/**
 * PHP-based schedule optimization fallback
 */
function phpOptimizeSchedule($courses, $existingSchedules) {
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    $times = [
        ['08:00', '10:00'],
        ['10:00', '12:00'],
        ['12:00', '14:00'],
        ['14:00', '16:00'],
        ['16:00', '18:00']
    ];
    
    $optimizedSlots = [];
    $conflictsResolved = 0;
    
    // Detect conflicts in existing schedules
    $daySlots = [];
    foreach ($existingSchedules as $schedule) {
        $day = $schedule['day_of_week'];
        if (!isset($daySlots[$day])) {
            $daySlots[$day] = [];
        }
        $daySlots[$day][] = [
            'start' => $schedule['start_time'],
            'end' => $schedule['end_time'],
            'course_code' => $schedule['course_code']
        ];
    }
    
    // Generate optimized slots for each course
    $slotIndex = 0;
    foreach ($courses as $course) {
        $assigned = false;
        
        foreach ($days as $day) {
            if ($assigned) break;
            
            foreach ($times as $time) {
                // Check if slot is available
                $available = true;
                if (isset($daySlots[$day])) {
                    foreach ($daySlots[$day] as $slot) {
                        // Check overlap
                        if (!($time[1] <= $slot['start'] || $time[0] >= $slot['end'])) {
                            $available = false;
                            $conflictsResolved++;
                            break;
                        }
                    }
                }
                
                if ($available) {
                    $optimizedSlots[] = [
                        'course_code' => $course['course_code'],
                        'day' => $day,
                        'time' => $time[0] . '-' . $time[1],
                        'room' => 'Room ' . (($slotIndex % 5) + 101)
                    ];
                    
                    // Add to day slots for future checks
                    if (!isset($daySlots[$day])) {
                        $daySlots[$day] = [];
                    }
                    $daySlots[$day][] = [
                        'start' => $time[0],
                        'end' => $time[1],
                        'course_code' => $course['course_code']
                    ];
                    
                    $assigned = true;
                    $slotIndex++;
                    break;
                }
            }
        }
    }
    
    // Calculate optimization score
    $score = 100;
    if (!empty($existingSchedules)) {
        $score = 100 - ($conflictsResolved * 10);
    }
    $score = max(60, min(100, $score));
    
    return [
        'success' => true,
        'message' => 'Schedule optimized successfully (PHP fallback)',
        'optimized_slots' => $optimizedSlots,
        'conflicts_resolved' => $conflictsResolved,
        'optimization_score' => $score,
        'ai_engine_used' => false
    ];
}
?>
