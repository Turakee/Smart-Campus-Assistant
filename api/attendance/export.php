<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../lib/PdfGenerator.php';

requireAuth();

$db = (new Database())->getConnection();

$role = $_SESSION['role'] ?? '';
$params = [];
$where = [];

if ($role === 'student') {
    $stmt = $db->prepare("SELECT student_id FROM students WHERE user_id = :uid");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $sid = $stmt->fetchColumn();
    if (!$sid) { http_response_code(403); echo 'Access denied'; exit; }
    $where[] = 'a.student_id = :sid';
    $params[':sid'] = $sid;
} elseif (in_array($role, ['administrator', 'system_admin'])) {
    if (!empty($_GET['course_id'])) { $where[] = 'a.course_id = :cid'; $params[':cid'] = (int)$_GET['course_id']; }
    if (!empty($_GET['department'])) { $where[] = 's.department = :dept'; $params[':dept'] = $_GET['department']; }
    if (!empty($_GET['date_from'])) { $where[] = 'a.date >= :df'; $params[':df'] = $_GET['date_from']; }
    if (!empty($_GET['date_to'])) { $where[] = 'a.date <= :dt'; $params[':dt'] = $_GET['date_to']; }
} else {
    http_response_code(403); echo 'Access denied'; exit;
}

$sql = "SELECT a.date, a.status, s.full_name, u.username, c.course_name, c.course_code, s.department as department_name
        FROM attendance a
        JOIN students s ON a.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        JOIN courses c ON a.course_id = c.course_id";
if (!empty($where)) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY a.date DESC, s.full_name ASC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

while (ob_get_level()) ob_end_clean();

$pdf = new PdfGenerator('Attendance Report');

$total = count($rows);
$pdf->addSubtitle('Total Records: ' . $total . ' | Generated: ' . date('Y-m-d H:i'));

$widths = [65, 130, 85, 120, 55, 40];
$pdf->addTableHeader(['Date', 'Student', 'Username', 'Course', 'Code', 'Status'], $widths);

if ($total === 0) {
    $pdf->addRow(['No records found', '', '', '', '', ''], $widths);
}

foreach ($rows as $row) {
    $pdf->addRow([
        $row['date'],
        $row['full_name'],
        $row['username'],
        $row['course_name'],
        $row['course_code'],
        $row['status'],
    ], $widths);
}

$pdf->output('attendance_report_' . date('Y-m-d') . '.pdf');
