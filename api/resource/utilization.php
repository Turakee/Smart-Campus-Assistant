<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

requireAuth();
requireRole(['administrator', 'system_admin']);

$database = new Database();
$db = $database->getConnection();

try {
    // Total resources by type
    $stmt = $db->query("
        SELECT resource_type, COUNT(*) as count
        FROM resources
        GROUP BY resource_type
        ORDER BY count DESC
    ");
    $byType = $stmt->fetchAll();

    // Booking counts per resource (approved)
    $stmt = $db->query("
        SELECT r.resource_id, r.resource_name, r.resource_type, r.capacity,
               COUNT(b.booking_id) as total_bookings,
               SUM(CASE WHEN b.status = 'approved' THEN 1 ELSE 0 END) as approved_bookings,
               SUM(CASE WHEN b.status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
               SUM(CASE WHEN b.status = 'rejected' THEN 1 ELSE 0 END) as rejected_bookings,
               MAX(b.created_at) as last_booked
        FROM resources r
        LEFT JOIN bookings b ON r.resource_id = b.resource_id
        GROUP BY r.resource_id
        ORDER BY total_bookings DESC
    ");
    $resources = $stmt->fetchAll();

    // Overall stats
    $stmt = $db->query("
        SELECT
            COUNT(DISTINCT resource_id) as total_resources,
            COUNT(*) as total_bookings,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
        FROM bookings
    ");
    $overall = $stmt->fetch();

    // Most booked resources (top 5)
    $stmt = $db->query("
        SELECT r.resource_name, r.resource_type, COUNT(b.booking_id) as usage_count
        FROM resources r
        JOIN bookings b ON r.resource_id = b.resource_id AND b.status = 'approved'
        GROUP BY r.resource_id
        ORDER BY usage_count DESC
        LIMIT 5
    ");
    $topResources = $stmt->fetchAll();

    // Bookings by day of week
    $stmt = $db->query("
        SELECT DAYNAME(booking_date) as day_name, COUNT(*) as count
        FROM bookings
        WHERE status = 'approved'
        GROUP BY DAYNAME(booking_date)
        ORDER BY FIELD(DAYNAME(booking_date), 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')
    ");
    $byDay = $stmt->fetchAll();

    $utilizationRate = $overall['total_bookings'] > 0
        ? round(($overall['approved'] / $overall['total_bookings']) * 100, 1)
        : 0;

    jsonResponse([
        'by_type' => $byType,
        'resources' => $resources,
        'overall' => $overall,
        'top_resources' => $topResources,
        'by_day' => $byDay,
        'utilization_rate' => $utilizationRate,
    ], true, 'Resource utilization data retrieved');
} catch (Exception $e) {
    jsonResponse([], false, 'Error: ' . $e->getMessage(), 500);
}
