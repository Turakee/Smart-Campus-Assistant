<?php
/**
 * AI Chatbot API
 * AI-Powered Smart Campus Assistant
 * 
 * Provides chatbot functionality for student queries
 */

header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/AIEngine.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    jsonResponse([], false, 'Method not allowed. Use POST request.', 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    jsonResponse([], false, 'Invalid JSON input', 400);
}

if (!isset($input['query']) || empty(trim($input['query']))) {
    jsonResponse([], false, 'Query text is required', 400);
}

$query = trim($input['query']);
$queryLower = strtolower($query);

// Local intent handlers should take precedence for attendance, schedule, course, and booking queries.
// The AI engine is used only as a fallback for unknown queries.

// Helper functions
function getStudentId($db) {
    $stmt = $db->prepare("SELECT student_id FROM students WHERE user_id = :uid");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function handleScheduleQuery($query, $queryLower, $db) {
    $student = getStudentId($db);
    
    if (!$student) {
        return [
            'intent' => 'schedule_query',
            'response' => 'I couldn\'t find your schedule information. Please contact admin.',
            'suggestions' => []
        ];
    }
    
    $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $today = $days[date('w')];
    $currentTime = date('H:i:s');
    
    // Check if user wants the full weekly schedule
    $isFullSchedule = preg_match('/\b(weekly|full|all|complete|week|entire)\b/i', $query) || 
                      preg_match('/\bshow all\b/i', $query) ||
                      preg_match('/\bthis week\b/i', $query);
    
    if ($isFullSchedule) {
        $stmt = $db->prepare("
            SELECT s.*, c.course_name, c.course_code
            FROM schedules s
            JOIN courses c ON s.course_id = c.course_id
            JOIN student_courses sc ON sc.course_id = s.course_id
            WHERE sc.student_id = :sid
            ORDER BY FIELD(s.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), s.start_time ASC
        ");
        $stmt->execute([':sid' => $student['student_id']]);
        $allClasses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($allClasses)) {
            return [
                'intent' => 'schedule_query',
                'response' => 'You have no classes scheduled. Please contact admin to set up your schedule.',
                'suggestions' => ['Check attendance', 'View my courses']
            ];
        }
        
        $grouped = [];
        foreach ($allClasses as $class) {
            $day = $class['day_of_week'];
            if (!isset($grouped[$day])) $grouped[$day] = [];
            $grouped[$day][] = $class;
        }
        
        $dayOrder = ['Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6, 'Sunday' => 7];
        uksort($grouped, function($a, $b) use ($dayOrder) {
            return ($dayOrder[$a] ?? 99) - ($dayOrder[$b] ?? 99);
        });
        
        $lines = [];
        $details = [];
        foreach ($grouped as $day => $classes) {
            $dayLine = "$day: ";
            $classParts = [];
            foreach ($classes as $cls) {
                $time = date('g:i A', strtotime($cls['start_time'])) . ' - ' . date('g:i A', strtotime($cls['end_time']));
                $classParts[] = $cls['course_code'] . ' at ' . $time . ' (' . ($cls['room_number'] ?? 'TBA') . ')';
                $details[] = $day . ' - ' . $cls['course_name'] . ' (' . $cls['course_code'] . ') ' . date('g:i A', strtotime($cls['start_time'])) . ' - ' . ($cls['room_number'] ?? 'TBA');
            }
            $lines[] = $dayLine . implode(' | ', $classParts);
        }
        
        return [
            'intent' => 'schedule_query',
            'response' => "Here is your full weekly schedule:\n" . implode("\n", $lines),
            'details' => $details,
            'suggestions' => ['What is my next class?', 'Check attendance', 'View my courses']
        ];
    }
    
    $stmt = $db->prepare("
        SELECT s.*, c.course_name, c.course_code
        FROM schedules s
        JOIN courses c ON s.course_id = c.course_id
        JOIN student_courses sc ON sc.course_id = s.course_id
        WHERE sc.student_id = :sid AND s.day_of_week = :day
        ORDER BY s.start_time ASC
    ");
    $stmt->execute([':sid' => $student['student_id'], ':day' => $today]);
    $todayClasses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $nextClass = null;
    foreach ($todayClasses as $class) {
        $startTime = substr($class['start_time'], 0, 5);
        if ($startTime > $currentTime) {
            $nextClass = $class;
            break;
        }
    }
    
    if ($nextClass) {
        return [
            'intent' => 'schedule_query',
            'response' => sprintf(
                'Your next class today is %s (%s) at %s in %s.',
                $nextClass['course_name'],
                $nextClass['course_code'],
                date('g:i A', strtotime($nextClass['start_time'])),
                $nextClass['room_number'] ?? 'TBA'
            ),
            'suggestions' => ['Show full schedule', 'Check attendance']
        ];
    } else {
        $tomorrowIndex = (date('w') + 1) % 7;
        $tomorrow = $days[$tomorrowIndex];
        
        $stmt->execute([':sid' => $student['student_id'], ':day' => $tomorrow]);
        $tomorrowClasses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($tomorrowClasses)) {
            $firstClass = $tomorrowClasses[0];
            return [
                'intent' => 'schedule_query',
                'response' => sprintf(
                    'No more classes today. Your first class tomorrow (%s) is %s (%s) at %s.',
                    $tomorrow,
                    $firstClass['course_name'],
                    $firstClass['course_code'],
                    date('g:i A', strtotime($firstClass['start_time']))
                ),
                'suggestions' => ['Show full schedule', 'View timetable']
            ];
        } else {
            return [
                'intent' => 'schedule_query',
                'response' => 'You have no more classes today or tomorrow. Enjoy your free time!',
                'suggestions' => ['Show weekly schedule', 'Check attendance']
            ];
        }
    }
}

function handleAttendanceQuery($query, $db) {
    $student = getStudentId($db);
    
    if (!$student) {
        return [
            'intent' => 'attendance_query',
            'response' => 'I couldn\'t find your attendance information.',
            'suggestions' => []
        ];
    }
    
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
            SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
            SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused
        FROM attendance 
        WHERE student_id = :sid
    ");
    $stmt->execute([':sid' => $student['student_id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $total = (int)($stats['total'] ?: 0);
    $present = (int)($stats['present'] ?: 0);
    $excused = (int)($stats['excused'] ?: 0);
    $good = $present + $excused;
    $percentage = $total > 0 ? round(($good / $total) * 100, 1) : 0;
    
    if ($total === 0) {
        return [
            'intent' => 'attendance_query',
            'response' => 'No attendance records found yet. Once your classes begin, attendance will be recorded here.',
            'details' => ['Present: 0', 'Absent: 0', 'Late: 0', 'Excused: 0'],
            'suggestions' => ['View my schedule', 'Check my courses']
        ];
    }
    
    $message = 'Your attendance is looking great!';
    
    if ($percentage < 75) {
        $message = 'Warning: Your attendance is below the 75% threshold!';
    } elseif ($percentage < 85) {
        $message = 'Your attendance needs attention. Try to be more consistent.';
    }
    
    return [
        'intent' => 'attendance_query',
        'response' => sprintf(
            '%s You have %d present and %d excused out of %d classes (%.1f%%).',
            $message,
            $present,
            $excused,
            $total,
            $percentage
        ),
        'details' => [
            'Present: ' . $present,
            'Absent: ' . (int)($stats['absent'] ?: 0),
            'Late: ' . (int)($stats['late'] ?: 0),
            'Excused: ' . (int)($stats['excused'] ?: 0)
        ],
        'suggestions' => ['View detailed attendance', 'Check risk analysis']
    ];
}

function handleBookingQuery($db) {
    $student = getStudentId($db);

    if (!$student) {
        return [
            'intent' => 'booking_query',
            'response' => 'I couldn\'t find your booking information.',
            'suggestions' => []
        ];
    }

    $stmt = $db->prepare("
        SELECT b.*, r.resource_name, r.resource_type
        FROM bookings b
        JOIN resources r ON b.resource_id = r.resource_id
        WHERE b.student_id = :sid
        ORDER BY b.booking_date DESC, b.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([':sid' => $student['student_id']]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($bookings)) {
        return [
            'intent' => 'booking_query',
            'response' => 'You have no bookings yet. You can book a room, lab, or other facility from the Bookings section.',
            'suggestions' => ['Book a room', 'View available rooms', 'Help']
        ];
    }

    $bookingList = array_map(function($b) {
        $icon = $b['status'] === 'approved' ? '✅' : ($b['status'] === 'rejected' ? '❌' : '⏳');
        return $icon . ' ' . $b['resource_name'] . ' on ' . $b['booking_date'] . ' (' . substr($b['start_time'], 0, 5) . '-' . substr($b['end_time'], 0, 5) . ') - ' . ucfirst($b['status']);
    }, $bookings);

    $pending = 0;
    $approved = 0;
    foreach ($bookings as $b) {
        if ($b['status'] === 'pending') $pending++;
        if ($b['status'] === 'approved') $approved++;
    }

    $summary = sprintf('You have %d booking%s (%d approved, %d pending).', count($bookings), count($bookings) > 1 ? 's' : '', $approved, $pending);

    return [
        'intent' => 'booking_query',
        'response' => 'Here are your recent bookings:',
        'details' => $bookingList,
        'suggestions' => ['Book a new room', 'View all bookings', 'Cancel a booking', 'Help']
    ];
}

function handleRiskAnalysisQuery($db) {
    $student = getStudentId($db);
    
    if (!$student) {
        return [
            'intent' => 'risk_analysis',
            'response' => 'I couldn\'t find your student information.',
            'suggestions' => []
        ];
    }
    
        $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            COALESCE(SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END), 0) as present,
            COALESCE(SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END), 0) as absent,
            COALESCE(SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END), 0) as late,
            COALESCE(SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END), 0) as excused
        FROM attendance 
        WHERE student_id = :sid
    ");
    $stmt->execute([':sid' => $student['student_id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $total = (int)($stats['total'] ?: 0);
    $present = (int)($stats['present'] ?: 0);
    $absent = (int)($stats['absent'] ?: 0);
    $excused = (int)($stats['excused'] ?: 0);
    $good = $present + $excused;
    $percentage = $total > 0 ? round(($good / $total) * 100, 1) : 0;
    
    // Check consecutive absences
    $stmt = $db->prepare("
        SELECT status FROM attendance 
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
    
    // Determine risk level
    if ($consecutiveAbsences >= 3) {
        $riskLevel = 'HIGH';
        $riskMessage = 'Critical: You have ' . $consecutiveAbsences . ' consecutive absences!';
    } elseif ($percentage < 75) {
        $riskLevel = 'HIGH';
        $riskMessage = 'Your attendance is below the 75% threshold.';
    } elseif ($percentage < 85) {
        $riskLevel = 'MEDIUM';
        $riskMessage = 'Your attendance needs attention.';
    } else {
        $riskLevel = 'LOW';
        $riskMessage = 'Your attendance is looking good!';
    }
    
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
    if ($consecutiveAbsences >= 3) {
        $recommendations[] = 'Warning: ' . $consecutiveAbsences . ' consecutive absences detected';
    }
    
    return [
        'intent' => 'risk_analysis',
        'response' => sprintf(
            "Risk Analysis Report:\nRisk Level: %s - %s\nAttendance: %d out of %d classes (%.1f%%)\nConsecutive Absences: %d",
            $riskLevel,
            $riskMessage,
            $present,
            $total,
            $percentage,
            $consecutiveAbsences
        ),
        'details' => [
            'Risk Level: ' . $riskLevel,
            'Attendance: ' . $percentage . '%',
            'Present: ' . $present,
            'Absent: ' . $absent,
            'Late: ' . (int)($stats['late'] ?: 0),
            'Consecutive Absences: ' . $consecutiveAbsences,
            'Total Classes: ' . $total
        ],
        'suggestions' => ['View detailed attendance', 'Show my schedule', 'Get recommendations']
    ];
}

function handleRecommendationsQuery($db) {
    $student = getStudentId($db);

    if (!$student) {
        return [
            'intent' => 'recommendations',
            'response' => 'I couldn\'t find your student information.',
            'suggestions' => []
        ];
    }

    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            COALESCE(SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END), 0) as present,
            COALESCE(SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END), 0) as absent,
            COALESCE(SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END), 0) as excused
        FROM attendance 
        WHERE student_id = :sid
    ");
    $stmt->execute([':sid' => $student['student_id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $total = (int)($stats['total'] ?: 0);
    $present = (int)($stats['present'] ?: 0);
    $excused = (int)($stats['excused'] ?: 0);
    $good = $present + $excused;
    $percentage = $total > 0 ? round(($good / $total) * 100, 1) : 0;

    $stmt = $db->prepare("
        SELECT status FROM attendance 
        WHERE student_id = :sid 
        ORDER BY date DESC LIMIT 5
    ");
    $stmt->execute([':sid' => $student['student_id']]);
    $recent = $stmt->fetchAll();

    $consecutiveAbsences = 0;
    foreach ($recent as $r) {
        if ($r['status'] === 'absent') $consecutiveAbsences++;
        else break;
    }

    $recommendations = [];

    if ($consecutiveAbsences >= 3) {
        $recommendations[] = 'You have ' . $consecutiveAbsences . ' consecutive absences — please contact your instructor';
        $recommendations[] = 'Attend all upcoming classes to avoid academic penalties';
    }

    if ($percentage < 75) {
        $recommendations[] = 'Your attendance (' . $percentage . '%) is below the required 75% threshold';
        $recommendations[] = 'Consider attending extra sessions or meeting with your instructor';
        $recommendations[] = 'Review missed coursework and catch up on assignments';
    } elseif ($percentage < 85) {
        $recommendations[] = 'Your attendance (' . $percentage . '%) is approaching the risk zone';
        $recommendations[] = 'Try to maintain consistent attendance in all classes';
    } else {
        $recommendations[] = 'Great attendance (' . $percentage . '%)! Keep it up';
        $recommendations[] = 'Continue attending regularly to stay on track';
    }

    if ($total === 0) {
        $recommendations = ['No attendance records yet. Start attending classes to get recommendations.'];
    }

    return [
        'intent' => 'recommendations',
        'response' => "Here are your personalized recommendations:\n\n" . implode("\n", array_map(function($r) {
            return '• ' . $r;
        }, $recommendations)),
        'details' => $recommendations,
        'suggestions' => ['Check risk analysis', 'View my schedule', 'Check attendance']
    ];
}

function handleCourseQuery($db) {
    $student = getStudentId($db);
    
    if (!$student) {
        return [
            'intent' => 'course_query',
            'response' => 'I couldn\'t find your course information.',
            'suggestions' => []
        ];
    }
    
    $stmt = $db->prepare("
        SELECT c.course_name, c.course_code, c.credit_hours
        FROM courses c
        JOIN student_courses sc ON sc.course_id = c.course_id
        WHERE sc.student_id = :sid
    ");
    $stmt->execute([':sid' => $student['student_id']]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($courses)) {
        return [
            'intent' => 'course_query',
            'response' => 'You are not enrolled in any courses yet. Contact admin to get enrolled.',
            'suggestions' => ['Contact administrator']
        ];
    }
    
    $courseNames = array_map(function($c) {
        return $c['course_code'] . ' - ' . $c['course_name'];
    }, $courses);
    
    return [
        'intent' => 'course_query',
        'response' => sprintf(
            'You are enrolled in %d courses: %s.',
            count($courses),
            implode(', ', $courseNames)
        ),
        'details' => array_map(function($c) {
            return $c['course_name'] . ' (' . ($c['credit_hours'] ?? 3) . ' credits)';
        }, $courses),
        'suggestions' => ['View full course list', 'Check schedule']
    ];
}

// Main intent classification
$responses = [];

if (preg_match('/\b(hello|hi|hey|greetings)\b/i', $query)) {
    $responses = [
        'intent' => 'greeting',
        'response' => 'Hello! I\'m your AI Campus Assistant. How can I help you today?',
        'suggestions' => ['View my schedule', 'Check attendance', 'Book a room']
    ];
} elseif (preg_match('/\b(attendance|present|absent|miss(ed|ing)?|attended)\b/i', $query)) {
    $database = new Database();
    $db = $database->getConnection();
    $responses = handleAttendanceQuery($query, $db);
} elseif (preg_match('/\b(courses?|subjects?|lectures?|enrolled|credits?)\b/i', $query)) {
    $database = new Database();
    $db = $database->getConnection();
    $responses = handleCourseQuery($db);
} elseif (preg_match('/\b(schedules?|class(es)?|timetable|when|time|days?)\b/i', $query) || preg_match('/\bnext class\b/i', $query)) {
    $database = new Database();
    $db = $database->getConnection();
    $responses = handleScheduleQuery($query, $queryLower, $db);
} elseif (preg_match('/\b(book(s|ings?)?|reserv(e|ations?|ing|ed)|room|lab|facilit(y|ies))\b/i', $query)) {
    $database = new Database();
    $db = $database->getConnection();
    $responses = handleBookingQuery($db);
} elseif (preg_match('/\b(risk|analysis|predict|health score)\b/i', $query) && preg_match('/\b(attendance|risk|analysis|check|my)\b/i', $query)) {
    $database = new Database();
    $db = $database->getConnection();
    $responses = handleRiskAnalysisQuery($db);
} elseif (preg_match('/\b(recommend(ations?)?|suggest(ions?)?|advice|tips?|what should)\b/i', $query)) {
    $database = new Database();
    $db = $database->getConnection();
    $responses = handleRecommendationsQuery($db);
} elseif (preg_match('/\b(remind(er|s)?|notify|alert|upcoming|today\'s|what\'?s (next|today|on))\b/i', $query) && preg_match('/\b(class|schedule|lecture|exam|test|deadline)\b/i', $query)) {
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare("SELECT student_id FROM students WHERE user_id = :uid");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $sid = $stmt->fetchColumn();
    if ($sid) {
        $today = date('l');
        $stmt = $db->prepare("SELECT c.course_name, c.course_code, s.start_time, s.end_time, s.room_number FROM schedules s JOIN courses c ON s.course_id = c.course_id JOIN student_courses sc ON sc.course_id = s.course_id AND sc.student_id = :sid WHERE s.day_of_week = :today ORDER BY s.start_time");
        $stmt->execute([':sid' => $sid, ':today' => $today]);
        $classes = $stmt->fetchAll();
        if (count($classes) > 0) {
            $list = array_map(function($c) {
                return "{$c['course_code']} {$c['course_name']} {$c['start_time']}-{$c['end_time']}" . ($c['room_number'] ? " ({$c['room_number']})" : '');
            }, $classes);
            $responses = [
                'intent' => 'reminder',
                'response' => "Here are today's classes:\n" . implode("\n", $list),
                'details' => $list,
                'suggestions' => ['Add to my calendar', 'Check attendance for these courses', 'Show full schedule']
            ];
        } else {
            $responses = [
                'intent' => 'reminder',
                'response' => 'You have no classes scheduled for today. Enjoy your day off!',
                'suggestions' => ['View full weekly schedule', 'Check attendance', 'Book a room']
            ];
        }
    } else {
        $responses = ['intent' => 'reminder', 'response' => 'Unable to retrieve your student profile.', 'suggestions' => ['Help']];
    }
} elseif (preg_match('/\b(help|assist|support|how|what can)\b/i', $query)) {
    $responses = [
        'intent' => 'help',
        'response' => 'I\'m your AI Campus Assistant! Here\'s what I can help with:',
        'details' => [
            'Schedule queries - "When is my next class?"',
            'Attendance - "What is my attendance percentage?"',
            'Courses - "Show my enrolled courses"',
            'Bookings - "Book a room"'
        ],
        'suggestions' => ['When is my next class?', 'Check my attendance', 'Show my courses']
    ];
} elseif (preg_match('/\b(bye|goodbye|thanks|thank you)\b/i', $query)) {
    $responses = [
        'intent' => 'goodbye',
        'response' => 'Goodbye! Have a great day! Feel free to return if you need any assistance.',
        'suggestions' => []
    ];
} else {
    $responses = [
        'intent' => 'unknown',
        'response' => 'I\'m not sure I understand that query. Try asking about schedules, attendance, courses, or room booking.',
        'suggestions' => ['Help', 'Show my schedule', 'Check attendance']
    ];
}

if ($responses['intent'] === 'unknown') {
    $aiResult = callAIEngine(['task' => 'chatbot', 'query' => $query], 'chatbot');
    if ($aiResult !== null && isset($aiResult['success']) && $aiResult['success']) {
        $responses = [
            'intent' => $aiResult['intent'] ?? 'chatbot',
            'response' => $aiResult['response'] ?? 'I\'m not sure I understand that query.',
            'suggestions' => $aiResult['suggestions'] ?? [],
            'details' => $aiResult['details'] ?? []
        ];
    }
}

jsonResponse($responses, true, 'Response generated');
?>
