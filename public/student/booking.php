<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}
$database = new Database();
$db = $database->getConnection();
$stmt = $db->prepare("SELECT * FROM resources WHERE capacity > 0 ORDER BY resource_name");
$stmt->execute();
$resources = $stmt->fetchAll();

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resource Booking - Smart Campus Assistant</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo-icon"><i class="fas fa-graduation-cap"></i></div>
        <h3>Smart Campus Assistant</h3>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
        <a href="schedule.php" class="nav-item"><i class="fas fa-calendar-alt"></i> Schedule</a>
        <a href="attendance.php" class="nav-item"><i class="fas fa-clipboard-check"></i> Attendance</a>
        <a href="courses.php" class="nav-item"><i class="fas fa-book"></i> Courses</a>
        <a href="booking.php" class="nav-item active"><i class="fas fa-door-open"></i> Bookings</a>
        <a href="ai-insights.php" class="nav-item"><i class="fas fa-robot"></i> AI Insights</a>
        <a href="profile.php" class="nav-item"><i class="fas fa-user-circle"></i> Profile</a>
        <a href="#" onclick="logout()" class="nav-item"><i class="fas fa-right-from-bracket"></i> Logout</a>
    </nav>
</aside>
<div class="sidebar-backdrop" onclick="toggleSidebar()"></div>
<main class="main-content">
    <header class="top-header">
        <div class="header-left">
            <button class="mobile-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            <h2><i class="fas fa-door-open"></i> Resource Booking</h2>
        </div>
        <div class="user-profile">
            <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?></div>
            <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
        </div>
    </header>
    <div class="dashboard-container">
        <div class="section-card">
            <div class="section-header"><h3><i class="fas fa-building"></i> Available Resources</h3></div>
            <div class="resource-grid" id="resourceGrid">
                <?php foreach ($resources as $resource):
                $icon = $resource['resource_type'] === 'classroom' ? 'chalkboard-user' : ($resource['resource_type'] === 'lab' ? 'flask' : ($resource['resource_type'] === 'auditorium' ? 'microphone' : 'door-open'));
                $color = $resource['resource_type'] === 'classroom' ? 'var(--primary)' : ($resource['resource_type'] === 'lab' ? '#06b6d4' : 'var(--warning)');
                ?>
                <div class="resource-card" onclick="selectResource(<?php echo $resource['resource_id']; ?>)">
                    <div class="resource-icon" style="background: linear-gradient(135deg, <?php echo $color; ?>, <?php echo $color; ?>88);">
                        <i class="fas fa-<?php echo $icon; ?>"></i>
                    </div>
                    <h4><?php echo htmlspecialchars($resource['resource_name']); ?></h4>
                    <p style="color: var(--gray); font-size: 14px; margin: 8px 0;"><i class="fas fa-users"></i> Capacity: <?php echo $resource['capacity']; ?></p>
                    <p style="color: var(--gray); font-size: 14px;"><i class="fas fa-tag"></i> <?php echo ucfirst($resource['resource_type']); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="section-card">
            <div class="section-header"><h3><i class="fas fa-calendar-plus"></i> New Booking Request</h3></div>
            <form id="bookingForm" style="max-width: 600px;">
                <input type="hidden" id="csrfToken" value="<?php echo $csrfToken; ?>">
                <input type="hidden" id="resourceId">
                <div class="form-group">
                    <label><i class="fas fa-building"></i> Selected Resource</label>
                    <input type="text" id="resourceName" readonly placeholder="Click a resource above to select" class="form-control" style="background: var(--light); cursor: not-allowed;">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Booking Date</label>
                        <input type="date" id="bookingDate" required min="<?php echo date('Y-m-d'); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Time Slot</label>
                        <select id="timeSlot" required class="form-control">
                            <option value="">Select time</option>
                            <option value="08:00-10:00">08:00 AM - 10:00 AM</option>
                            <option value="10:00-12:00">10:00 AM - 12:00 PM</option>
                            <option value="12:00-14:00">12:00 PM - 02:00 PM</option>
                            <option value="14:00-16:00">02:00 PM - 04:00 PM</option>
                            <option value="16:00-18:00">04:00 PM - 06:00 PM</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-pen"></i> Purpose</label>
                    <textarea id="purpose" rows="3" required placeholder="Describe the purpose of your booking..." class="form-control"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px;">
                    <i class="fas fa-paper-plane"></i> Submit Booking Request
                </button>
            </form>
        </div>
        <div class="section-card">
            <div class="section-header"><h3><i class="fas fa-clock-rotate-left"></i> My Booking History</h3></div>
            <table class="data-table">
                <thead>
                    <tr><th>Resource</th><th>Date</th><th>Time</th><th>Purpose</th><th>Status</th></tr>
                </thead>
                <tbody id="bookingHistoryBody">
                    <tr><td colspan="5" class="empty-state"><div class="loading-spinner"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</main>
<script src="../assets/js/student.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        loadBookingHistory();
        document.getElementById('bookingForm').addEventListener('submit', submitBooking);
        
        // Auto-refresh booking history every 30 seconds
        setInterval(() => {
            if (document.visibilityState === 'visible') {
                loadBookingHistory();
            }
        }, 30000);
    });
    </script>
</body>
</html>