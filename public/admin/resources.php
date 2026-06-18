<?php
require_once '../../config/config.php';

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'administrator') {
    header('Location: ../index.php');
    exit;
}

$user_name = $_SESSION['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Resources - Smart Campus Assistant</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal.show { display: flex; align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 30px; border-radius: 12px; width: 90%; max-width: 500px; }
        .resource-card { display: flex; align-items: center; justify-content: space-between; padding: 16px; background: var(--light); border-radius: 12px; margin-bottom: 12px; }
        .resource-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; }
        .resource-info { flex: 1; margin-left: 16px; }
        .resource-info h4 { margin: 0; font-size: 16px; }
        .resource-info small { color: var(--gray); }
        .type-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .type-lab { background: #dbeafe; color: #1d4ed8; }
        .type-classroom { background: #dcfce7; color: #166534; }
        .type-auditorium { background: #fef3c7; color: #92400e; }
        .type-other { background: #f3e8ff; color: #7c3aed; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo-icon"><i class="fas fa-user-shield"></i></div>
            <h3>Smart Campus Assistant</h3>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
            <a href="students.php" class="nav-item"><i class="fas fa-users"></i> Students</a>
            <a href="courses.php" class="nav-item"><i class="fas fa-book"></i> Courses</a>
            <a href="manage-courses.php" class="nav-item"><i class="fas fa-user-graduate"></i> Enrollments</a>
            <a href="attendance.php" class="nav-item"><i class="fas fa-clipboard-check"></i> Attendance</a>
            <a href="schedule.php" class="nav-item"><i class="fas fa-calendar-alt"></i> Schedules</a>
            <a href="bookings.php" class="nav-item"><i class="fas fa-door-open"></i> Approve Bookings</a>
            <a href="resources.php" class="nav-item active"><i class="fas fa-building"></i> Resources</a>
            <a href="analytics.php" class="nav-item"><i class="fas fa-chart-line"></i> AI Analytics</a>
            <a href="profile.php" class="nav-item"><i class="fas fa-user-circle"></i> Profile</a>
            <a href="#" onclick="logout()" class="nav-item"><i class="fas fa-right-from-bracket"></i> Logout</a>
        </nav>
    </aside>

    <div class="sidebar-backdrop" onclick="toggleSidebar()"></div>

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button class="mobile-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <h2><i class="fas fa-building"></i> Manage Resources</h2>
            </div>
            <div class="user-profile">
                <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($user_name); ?></span>
            </div>
        </header>

        <div class="dashboard-container">
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-list"></i> All Resources</h3>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <button class="btn btn-info" onclick="showUtilization()" style="display:inline-flex;align-items:center;gap:6px;"><i class="fas fa-chart-bar"></i> Utilization</button>
                        <button class="btn btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Add New Resource</button>
                    </div>
                </div>

                <div id="alertBox" style="margin-bottom: 12px; display: none; padding: 12px; border-radius: 8px;"></div>

                <div id="resourcesContainer">
                    <div style="padding: 20px; text-align: center; color: var(--gray);">
                        <div class="loading-spinner"></div>
                        <p style="margin-top: 10px;">Loading resources...</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Add/Edit Modal -->
    <div id="resourceModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 id="modalTitle"><i class="fas fa-plus-circle"></i> Add New Resource</h3>
                <button onclick="closeModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="resourceForm">
                <input type="hidden" id="resourceId">
                <div class="form-group">
                    <label>Resource Name *</label>
                    <input type="text" id="resourceName" required placeholder="e.g., Lab 101, Room 205">
                </div>
                <div class="form-group">
                    <label>Resource Type *</label>
                    <select id="resourceType" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
                        <option value="classroom">Classroom</option>
                        <option value="lab">Lab</option>
                        <option value="auditorium">Auditorium</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Capacity (optional)</label>
                    <input type="number" id="resourceCapacity" placeholder="e.g., 30" min="1">
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        <i class="fas fa-save"></i> Save Resource
                    </button>
                    <button type="button" class="btn" style="background: #6b7280; color: white;" onclick="closeModal()">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Utilization Modal -->
    <div id="utilModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3><i class="fas fa-chart-bar"></i> Resource Utilization</h3>
                <button onclick="closeUtilModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="utilContent" style="text-align: center; padding: 20px;">
                <div class="loading-spinner"></div>
            </div>
        </div>
    </div>

    <script>
        const API_BASE = '../../api/resource/';

        function showAlert(message, type = 'success') {
            const alertBox = document.getElementById('alertBox');
            alertBox.style.display = 'block';
            alertBox.style.background = type === 'success' ? '#dcfce7' : '#fee2e2';
            alertBox.style.color = type === 'success' ? '#166534' : '#991b1b';
            alertBox.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
            setTimeout(() => { alertBox.style.display = 'none'; }, 4000);
        }

        function getTypeIcon(type) {
            const icons = {
                'lab': 'fa-desktop',
                'classroom': 'fa-chalkboard-teacher',
                'auditorium': 'fa-users-rectangle',
                'other': 'fa-building'
            };
            return icons[type] || 'fa-building';
        }

        function getTypeColor(type) {
            const colors = {
                'lab': '#3b82f6',
                'classroom': '#22c55e',
                'auditorium': '#f59e0b',
                'other': '#8b5cf6'
            };
            return colors[type] || '#6b7280';
        }

        function renderResources(resources) {
            const container = document.getElementById('resourcesContainer');
            
            if (!resources || resources.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: var(--gray);">
                        <i class="fas fa-building" style="font-size: 48px; opacity: 0.3;"></i>
                        <p style="margin-top: 15px;">No resources found. Click "Add New Resource" to create one.</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = resources.map(r => `
                <div class="resource-card">
                    <div class="resource-icon" style="background: ${getTypeColor(r.resource_type)};">
                        <i class="fas ${getTypeIcon(r.resource_type)}"></i>
                    </div>
                    <div class="resource-info">
                        <h4>${escapeHtml(r.resource_name)}</h4>
                        <small>${r.resource_type ? r.resource_type.charAt(0).toUpperCase() + r.resource_type.slice(1) : 'Other'} 
                        ${r.capacity ? '• Capacity: ' + r.capacity : ''}</small>
                    </div>
                    <div>
                        <span class="type-badge type-${r.resource_type}">${r.resource_type || 'other'}</span>
                    </div>
                    <div style="margin-left: 16px;">
                        <button class="btn btn-primary" style="padding: 8px 12px; margin-right: 4px;" onclick="editResource(${r.resource_id}, '${escapeHtml(r.resource_name)}', '${r.resource_type}', ${r.capacity || 0})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn" style="padding: 8px 12px; background: #ef4444; color: white;" onclick="deleteResource(${r.resource_id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `).join('');
        }

        function escapeHtml(text) {
            if (typeof text !== 'string') return text;
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        async function loadResources() {
            try {
                const response = await fetch(API_BASE + 'list.php', {
                    credentials: 'include'
                });
                const result = await response.json();
                if (result.success) {
                    renderResources(result.data);
                } else {
                    showAlert(result.message || 'Failed to load resources', 'error');
                }
            } catch (err) {
                showAlert('Error loading resources: ' + err.message, 'error');
            }
        }

        function openModal(resourceId = null) {
            document.getElementById('resourceModal').classList.add('show');
            document.getElementById('resourceForm').reset();
            document.getElementById('resourceId').value = '';
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle"></i> Add New Resource';
        }

        function editResource(id, name, type, capacity) {
            document.getElementById('resourceModal').classList.add('show');
            document.getElementById('resourceId').value = id;
            document.getElementById('resourceName').value = name;
            document.getElementById('resourceType').value = type;
            document.getElementById('resourceCapacity').value = capacity || '';
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Resource';
        }

        function closeModal() {
            document.getElementById('resourceModal').classList.remove('show');
        }

        document.getElementById('resourceForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const resourceId = document.getElementById('resourceId').value;
            const data = {
                resource_name: document.getElementById('resourceName').value,
                resource_type: document.getElementById('resourceType').value,
                capacity: document.getElementById('resourceCapacity').value || null
            };

            try {
                let response;
                if (resourceId) {
                    data.resource_id = resourceId;
                    response = await fetch(API_BASE + 'update.php', {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data),
                        credentials: 'include'
                    });
                } else {
                    response = await fetch(API_BASE + 'create.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data),
                        credentials: 'include'
                    });
                }

                const result = await response.json();
                if (result.success) {
                    showAlert(resourceId ? 'Resource updated successfully' : 'Resource created successfully');
                    closeModal();
                    loadResources();
                } else {
                    showAlert(result.message || 'Failed to save resource', 'error');
                }
            } catch (err) {
                showAlert('Error: ' + err.message, 'error');
            }
        });

        async function deleteResource(id) {
            if (!confirm('Are you sure you want to delete this resource? This cannot be undone.')) {
                return;
            }

            try {
                const response = await fetch(API_BASE + 'delete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ resource_id: id }),
                    credentials: 'include'
                });
                const result = await response.json();
                if (result.success) {
                    showAlert('Resource deleted successfully');
                    loadResources();
                } else {
                    showAlert(result.message || 'Failed to delete resource', 'error');
                }
            } catch (err) {
                showAlert('Error deleting resource: ' + err.message, 'error');
            }
        }

        function logout() {
            if (confirm('Logout?')) {
                fetch('../../api/auth/logout.php').then(() => window.location.href = '../index.php');
            }
        }

        async function showUtilization() {
            document.getElementById('utilModal').style.display = 'flex';
            document.getElementById('utilContent').innerHTML = '<div class="loading-spinner"></div>';
            try {
                const res = await fetch('../../api/resource/utilization.php', { credentials: 'include' });
                const data = await res.json();
                if (!data.success) { document.getElementById('utilContent').innerHTML = '<p style="color:var(--danger);">' + escapeHtml(data.message) + '</p>'; return; }
                const d = data.data;
                let html = '<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:20px;">';
                html += '<div style="background:var(--light);padding:16px;border-radius:10px;"><h3 style="margin:0 0 4px;font-size:24px;">' + (d.overall ? d.overall.total_resources : 0) + '</h3><p style="margin:0;font-size:12px;color:var(--gray);">Resources</p></div>';
                html += '<div style="background:var(--light);padding:16px;border-radius:10px;"><h3 style="margin:0 0 4px;font-size:24px;">' + (d.overall ? d.overall.total_bookings : 0) + '</h3><p style="margin:0;font-size:12px;color:var(--gray);">Total Bookings</p></div>';
                html += '<div style="background:var(--light);padding:16px;border-radius:10px;"><h3 style="margin:0 0 4px;font-size:24px;">' + d.utilization_rate + '%</h3><p style="margin:0;font-size:12px;color:var(--gray);">Approval Rate</p></div></div>';
                if (d.resources && d.resources.length > 0) {
                    html += '<table style="width:100%;border-collapse:collapse;font-size:13px;"><thead><tr style="background:var(--light);"><th style="padding:8px 10px;text-align:left;">Resource</th><th style="padding:8px 10px;text-align:left;">Type</th><th style="padding:8px 10px;text-align:center;">Approved</th><th style="padding:8px 10px;text-align:center;">Pending</th></tr></thead><tbody>';
                    d.resources.forEach(function(r) {
                        html += '<tr><td style="padding:8px 10px;border-bottom:1px solid var(--light);">' + escapeHtml(r.resource_name) + '</td><td style="padding:8px 10px;border-bottom:1px solid var(--light);"><span class="type-badge type-' + escapeHtml(r.resource_type) + '">' + escapeHtml(r.resource_type) + '</span></td><td style="padding:8px 10px;border-bottom:1px solid var(--light);text-align:center;">' + (r.approved_bookings || 0) + '</td><td style="padding:8px 10px;border-bottom:1px solid var(--light);text-align:center;">' + (r.pending_bookings || 0) + '</td></tr>';
                    });
                    html += '</tbody></table>';
                } else {
                    html += '<p style="color:var(--gray);">No booking data available yet.</p>';
                }
                document.getElementById('utilContent').innerHTML = html;
            } catch (e) {
                document.getElementById('utilContent').innerHTML = '<p style="color:var(--danger);">Failed to load utilization data.</p>';
            }
        }
        function closeUtilModal() { document.getElementById('utilModal').style.display = 'none'; }
        document.getElementById('utilModal').addEventListener('click', function(e) { if (e.target.id === 'utilModal') closeUtilModal(); });

        // Close modal on outside click
        document.getElementById('resourceModal').addEventListener('click', (e) => {
            if (e.target.id === 'resourceModal') {
                closeModal();
            }
        });

        // Load resources on page load
        loadResources();

        function toggleSidebar() { document.querySelector('.sidebar').classList.toggle('active'); }
    </script>
</body>
</html>
