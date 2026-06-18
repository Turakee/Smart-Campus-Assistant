<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse([], false, 'Invalid request method', 405);
}

$database = new Database();
$db = $database->getConnection();

try {
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    if ($role === 'student') {
        $stmt = $db->prepare("SELECT student_id FROM students WHERE user_id = :id");
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
        $student = $stmt->fetch();
        
        if ($student) {
            // Only get attendance for ENROLLED courses
            $query = "SELECT a.*, c.course_code, c.course_name 
                      FROM attendance a 
                      JOIN courses c ON a.course_id = c.course_id 
                      JOIN student_courses sc ON sc.course_id = a.course_id AND sc.student_id = a.student_id
                      WHERE a.student_id = :sid 
                      ORDER BY a.date DESC LIMIT 50";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':sid', $student['student_id']);
            $stmt->execute();
            $records = $stmt->fetchAll();
            
            $stats = ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0, 'total' => count($records)];
            foreach ($records as $r) {
                if (isset($stats[$r['status']])) $stats[$r['status']]++;
            }
            $stats['percentage'] = $stats['total'] > 0 ? round((($stats['present'] + $stats['excused']) / $stats['total']) * 100) : 0;
            
            jsonResponse([
                'records' => $records,
                'stats' => $stats
            ], true, 'Attendance retrieved');
        } else {
            jsonResponse([], false, 'Student profile not found', 404);
        }
    } else {
        // Admin sees all attendance
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : null;
        $date = isset($_GET['date']) ? $_GET['date'] : null;
        $studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;
        
        $baseQuery = "SELECT a.*, c.course_code, c.course_name, s.full_name as student_name, s.department as student_department
                      FROM attendance a 
                      JOIN courses c ON a.course_id = c.course_id 
                      JOIN students s ON a.student_id = s.student_id 
                      WHERE 1=1";
        
        $params = [];
        
        if ($search) {
            $baseQuery .= " AND (s.full_name LIKE :search1 
                             OR s.department LIKE :search4
                             OR c.course_code LIKE :search2 
                             OR c.course_name LIKE :search3)";
            $params[':search1'] = '%' . $search . '%';
            $params[':search4'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
            $params[':search3'] = '%' . $search . '%';
        }
        
        if ($courseId) {
            $baseQuery .= " AND a.course_id = :course_id";
            $params[':course_id'] = $courseId;
        }
        
        if ($studentId) {
            $baseQuery .= " AND a.student_id = :student_id";
            $params[':student_id'] = $studentId;
        }
        
        if ($date) {
            $baseQuery .= " AND a.date = :date";
            $params[':date'] = $date;
        }
        
        $baseQuery .= " ORDER BY a.date DESC LIMIT 100";
        
        $stmt = $db->prepare($baseQuery);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $records = $stmt->fetchAll();
        
        $stats = ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0, 'total' => count($records)];
        foreach ($records as $r) {
            if (isset($stats[$r['status']])) $stats[$r['status']]++;
        }
        $stats['percentage'] = $stats['total'] > 0 ? round((($stats['present'] + $stats['excused']) / $stats['total']) * 100) : 0;
        
        jsonResponse([
            'records' => $records,
            'stats' => $stats
        ], true, 'Attendance retrieved');
    }
    
} catch(Exception $e) {
    error_log('Attendance API Error: ' . $e->getMessage());
    jsonResponse([], false, 'Server error: ' . $e->getMessage(), 500);
}
?>