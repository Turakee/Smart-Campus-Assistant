<?php
require_once '../../config/config.php';

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'system_admin') {
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
    <title>System Admin - Smart Campus Assistant</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        :root {
            --it-primary: #0f172a;
            --it-sidebar: #0b1120;
            --it-sidebar-hover: #1a2332;
            --it-accent: #4f46e5;
            --it-accent-light: #6366f1;
            --it-success: #10b981;
            --it-warning: #f59e0b;
            --it-danger: #ef4444;
            --it-info: #3b82f6;
            --it-teal: #14b8a6;
            --it-card: #ffffff;
            --it-gray: #64748b;
            --it-dark: #0f172a;
            --it-border: #e2e8f0;
            --it-radius: 12px;
            --it-shadow: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -2px rgba(0,0,0,0.05);
        }

        body.it-admin {
            background: #f1f5f9;
            font-family: 'Inter', -apple-system, sans-serif;
        }

        .it-admin .sidebar {
            width: 270px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: var(--it-sidebar);
            border-right: 1px solid rgba(255,255,255,0.05);
            display: flex;
            flex-direction: column;
            z-index: 100;
            overflow: hidden;
        }

        .it-admin .sidebar-header {
            padding: 20px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            background: linear-gradient(180deg, rgba(79,70,229,0.12), transparent);
        }

        .it-admin .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--it-accent), #3730a3);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 0 20px rgba(79,70,229,0.3);
        }

        .it-admin .sidebar-header h3 {
            font-size: 16px;
            font-weight: 700;
            color: #f1f5f9;
            letter-spacing: -0.3px;
        }

        .it-admin .sidebar-header small {
            display: block;
            font-size: 10px;
            color: var(--it-gray);
            font-weight: 500;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .it-admin .sidebar-nav {
            flex: 1;
            padding: 16px 12px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .it-admin .nav-section-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: rgba(255,255,255,0.25);
            padding: 20px 16px 6px;
        }

        .it-admin .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 16px;
            border-radius: 8px;
            color: rgba(255,255,255,0.55);
            font-weight: 500;
            font-size: 13px;
            transition: all 0.2s;
            position: relative;
        }

        .it-admin .nav-item:hover {
            background: rgba(255,255,255,0.06);
            color: #e2e8f0;
        }

        .it-admin .nav-item.active {
            background: rgba(79,70,229,0.15);
            color: #a5b4fc;
            font-weight: 600;
        }

        .it-admin .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            height: 24px;
            width: 3px;
            background: var(--it-accent);
            border-radius: 0 3px 3px 0;
        }

        .it-admin .nav-item i {
            width: 20px;
            text-align: center;
            font-size: 15px;
        }

        .it-admin .nav-item .badge-dot {
            margin-left: auto;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--it-success);
            box-shadow: 0 0 6px rgba(16,185,129,0.5);
        }

        .it-admin .nav-item .badge-dot.warning {
            background: var(--it-warning);
            box-shadow: 0 0 6px rgba(245,158,11,0.5);
        }

        .it-admin .nav-item .badge-dot.danger {
            background: var(--it-danger);
            box-shadow: 0 0 6px rgba(239,68,68,0.5);
        }

        .it-admin .nav-item .badge-count {
            margin-left: auto;
            background: rgba(239,68,68,0.2);
            color: #fca5a5;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 10px;
        }

        .it-admin .main-content {
            margin-left: 270px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .it-admin .top-header {
            height: 64px;
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--it-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .it-admin .header-left h2 {
            font-size: 18px;
            font-weight: 700;
            color: var(--it-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .it-admin .header-left h2 i {
            color: var(--it-accent);
        }

        .it-admin .header-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .it-admin .header-search {
            display: flex;
            align-items: center;
            background: #f1f5f9;
            border-radius: 8px;
            padding: 0 14px;
            gap: 8px;
            border: 1px solid transparent;
            transition: all 0.2s;
        }

        .it-admin .header-search:focus-within {
            border-color: var(--it-accent);
            background: white;
            box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
        }

        .it-admin .header-search input {
            border: none;
            background: transparent;
            padding: 10px 0;
            font-size: 13px;
            outline: none;
            color: var(--it-dark);
            width: 200px;
        }

        .it-admin .header-search i {
            color: var(--it-gray);
            font-size: 14px;
        }

        .it-admin .header-icon-btn {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--it-gray);
            background: #f1f5f9;
            transition: all 0.2s;
            position: relative;
            cursor: pointer;
            border: none;
        }

        .it-admin .header-icon-btn:hover {
            background: #e2e8f0;
            color: var(--it-dark);
        }

        .it-admin .header-icon-btn .notif-dot {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--it-danger);
            border: 2px solid white;
        }

        .it-admin .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 4px 14px 4px 4px;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid var(--it-border);
            background: white;
        }

        .it-admin .user-profile:hover {
            border-color: var(--it-accent);
            box-shadow: 0 2px 8px rgba(79,70,229,0.12);
        }

        .it-admin .user-avatar {
            width: 34px;
            height: 34px;
            background: linear-gradient(135deg, var(--it-accent), var(--it-accent-light));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
        }

        .it-admin .user-profile span {
            font-size: 13px;
            font-weight: 600;
            color: var(--it-dark);
        }

        .it-admin .dashboard-container {
            padding: 28px 0 0 32px;
            min-height: calc(100vh - 64px);
            animation: fadeIn 0.4s ease-out;
            display: flex;
            flex-direction: column;
        }

        .it-admin .dashboard-container > div:last-child {
            flex: 1;
            min-height: 0;
            margin-bottom: 0;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .it-admin .status-banner {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            border-radius: 16px;
            padding: 28px 32px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            position: relative;
            overflow: hidden;
        }

        .it-admin .status-banner::before {
            content: '';
            position: absolute;
            top: -40%;
            right: -5%;
            width: 280px;
            height: 280px;
            background: radial-gradient(circle, rgba(79,70,229,0.12), transparent 70%);
            border-radius: 50%;
        }

        .it-admin .status-banner-left {
            display: flex;
            align-items: center;
            gap: 20px;
            position: relative;
            z-index: 1;
        }

        .it-admin .status-banner-left .icon-wrap {
            width: 52px;
            height: 52px;
            background: rgba(79,70,229,0.2);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: #a5b4fc;
        }

        .it-admin .status-banner-left h1 {
            font-size: 20px;
            font-weight: 700;
            color: #f1f5f9;
            margin: 0;
        }

        .it-admin .status-banner-left p {
            font-size: 13px;
            color: rgba(255,255,255,0.5);
            margin: 4px 0 0;
        }

        .it-admin .status-banner-right {
            display: flex;
            align-items: center;
            gap: 20px;
            position: relative;
            z-index: 1;
            flex-wrap: wrap;
        }

        .it-admin .status-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: rgba(255,255,255,0.06);
            color: rgba(255,255,255,0.7);
        }

        .it-admin .status-pill .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--it-success);
            box-shadow: 0 0 8px rgba(16,185,129,0.6);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .it-admin .status-pill .dot.warning {
            background: var(--it-warning);
            box-shadow: 0 0 8px rgba(245,158,11,0.6);
        }

        .it-metrics-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }

        .it-metric-card {
            background: var(--it-card);
            border-radius: var(--it-radius);
            padding: 20px;
            border: 1px solid var(--it-border);
            box-shadow: var(--it-shadow);
            transition: all 0.25s;
            position: relative;
            overflow: hidden;
        }

        .it-metric-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            border-color: rgba(79,70,229,0.2);
        }

        .it-metric-card .metric-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .it-metric-card .metric-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: white;
        }

        .it-metric-card .metric-icon.blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .it-metric-card .metric-icon.green { background: linear-gradient(135deg, #10b981, #059669); }
        .it-metric-card .metric-icon.purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .it-metric-card .metric-icon.orange { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .it-metric-card .metric-icon.red { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .it-metric-card .metric-icon.teal { background: linear-gradient(135deg, #14b8a6, #0d9488); }
        .it-metric-card .metric-icon.indigo { background: linear-gradient(135deg, var(--it-accent), #3730a3); }

        .it-metric-card .metric-trend {
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 6px;
        }

        .it-metric-card .metric-trend.up {
            background: #d1fae5;
            color: #065f46;
        }

        .it-metric-card .metric-trend.down {
            background: #fee2e2;
            color: #991b1b;
        }

        .it-metric-card .metric-value {
            font-size: 26px;
            font-weight: 800;
            color: var(--it-dark);
            line-height: 1.2;
        }

        .it-metric-card .metric-label {
            font-size: 12px;
            color: var(--it-gray);
            font-weight: 500;
            margin-top: 4px;
        }

        .it-admin .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 28px;
        }

        .it-admin .content-grid-full {
            grid-column: 1 / -1;
        }

        .it-admin .it-panel {
            background: var(--it-card);
            border-radius: 16px;
            border: 1px solid var(--it-border);
            box-shadow: var(--it-shadow);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .it-admin .it-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 24px;
            border-bottom: 1px solid var(--it-border);
            gap: 12px;
        }

        .it-admin .it-panel-header h3 {
            font-size: 15px;
            font-weight: 700;
            color: var(--it-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .it-admin .it-panel-header h3 i {
            color: var(--it-accent);
        }

        .it-admin .it-panel-body {
            padding: 20px 24px;
            flex: 1;
        }

        .it-admin .service-list {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .it-admin .service-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .it-admin .service-item:last-child {
            border-bottom: none;
        }

        .it-admin .service-item-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .it-admin .service-item-left i {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: white;
            background: var(--it-gray);
        }

        .it-admin .service-item-left .service-name {
            font-weight: 600;
            font-size: 13px;
            color: var(--it-dark);
        }

        .it-admin .service-item-left .service-desc {
            font-size: 11px;
            color: var(--it-gray);
            margin-top: 2px;
        }

        .it-admin .service-status {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .it-admin .service-status .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
        }

        .it-admin .service-status .dot.online { background: var(--it-success); box-shadow: 0 0 6px rgba(16,185,129,0.5); }
        .it-admin .service-status .dot.warning { background: var(--it-warning); box-shadow: 0 0 6px rgba(245,158,11,0.5); }
        .it-admin .service-status .dot.offline { background: var(--it-danger); box-shadow: 0 0 6px rgba(239,68,68,0.5); }
        .it-admin .service-status .online { color: var(--it-success); }
        .it-admin .service-status .warning { color: var(--it-warning); }
        .it-admin .service-status .offline { color: var(--it-danger); }

        .it-admin .action-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .it-admin .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 18px 12px;
            border-radius: 12px;
            border: 1px solid var(--it-border);
            background: #fafafa;
            color: var(--it-dark);
            font-weight: 600;
            font-size: 12px;
            transition: all 0.2s;
            cursor: pointer;
            text-align: center;
        }

        .it-admin .action-btn:hover {
            transform: translateY(-2px);
            border-color: var(--it-accent);
            background: white;
            box-shadow: 0 4px 12px rgba(79,70,229,0.12);
        }

        .it-admin .action-btn i {
            font-size: 22px;
            color: var(--it-accent);
        }

        .it-admin .admin-links-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        .it-admin .admin-link-card {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px;
            border-radius: 12px;
            border: 1px solid var(--it-border);
            background: var(--it-card);
            color: var(--it-dark);
            font-weight: 600;
            font-size: 13px;
            transition: all 0.2s;
            text-decoration: none;
        }

        .it-admin .admin-link-card:hover {
            transform: translateY(-2px);
            border-color: var(--it-accent);
            box-shadow: 0 4px 12px rgba(79,70,229,0.1);
        }

        .it-admin .admin-link-card i {
            font-size: 18px;
            color: var(--it-accent);
            width: 24px;
            text-align: center;
        }

        .it-admin .activity-feed {
            display: flex;
            flex-direction: column;
            gap: 12px;
            flex: 1;
            overflow-y: auto;
        }

        .it-admin .activity-feed .activity-item:last-child {
            margin-bottom: 0;
        }

        .it-admin .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 14px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .it-admin .activity-item:last-child {
            border-bottom: none;
        }

        .it-admin .activity-item .activity-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: white;
            flex-shrink: 0;
        }

        .it-admin .activity-item .activity-icon.blue { background: #3b82f6; }
        .it-admin .activity-item .activity-icon.green { background: #10b981; }
        .it-admin .activity-item .activity-icon.orange { background: #f59e0b; }
        .it-admin .activity-item .activity-icon.red { background: #ef4444; }
        .it-admin .activity-item .activity-icon.purple { background: #8b5cf6; }

        .it-admin .activity-item .activity-text {
            font-size: 13px;
            color: var(--it-dark);
            line-height: 1.4;
        }

        .it-admin .activity-item .activity-text strong {
            font-weight: 600;
        }

        .it-admin .activity-item .activity-time {
            font-size: 11px;
            color: var(--it-gray);
            margin-top: 2px;
        }

        .it-admin .timeline-feed {
            position: relative;
            padding-left: 24px;
        }
        .it-admin .timeline-feed::before {
            content: '';
            position: absolute;
            left: 7px;
            top: 8px;
            bottom: 8px;
            width: 2px;
            background: var(--it-border);
            border-radius: 2px;
        }
        .it-admin .timeline-item {
            position: relative;
            padding: 14px 0 14px 20px;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s;
        }
        .it-admin .timeline-item:last-child {
            border-bottom: none;
        }
        .it-admin .timeline-item:hover {
            background: #f8fafc;
            margin: 0 -12px;
            padding: 14px 12px 14px 32px;
            border-radius: 8px;
        }
        .it-admin .timeline-item .tl-dot {
            position: absolute;
            left: -20px;
            top: 18px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 0 0 2px var(--it-border);
        }
        .it-admin .timeline-item .tl-dot.blue { background: #3b82f6; }
        .it-admin .timeline-item .tl-dot.green { background: #10b981; }
        .it-admin .timeline-item .tl-dot.orange { background: #f59e0b; }
        .it-admin .timeline-item .tl-dot.red { background: #ef4444; }
        .it-admin .timeline-item .tl-dot.purple { background: #8b5cf6; }
        .it-admin .timeline-item .tl-text {
            font-size: 13px;
            color: var(--it-dark);
            line-height: 1.4;
        }
        .it-admin .timeline-item .tl-time {
            font-size: 11px;
            color: var(--it-gray);
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .it-admin .compact-stat-card {
            background: #f8fafc;
            border-radius: 10px;
            padding: 16px;
            text-align: center;
            border: 1px solid var(--it-border);
            transition: all 0.25s;
            cursor: default;
        }
        .it-admin .compact-stat-card:hover {
            transform: translateY(-2px);
            border-color: rgba(79,70,229,0.2);
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }
        .it-admin .compact-stat-card .stat-num {
            font-size: 22px;
            font-weight: 800;
            color: var(--it-dark);
        }
        .it-admin .compact-stat-card .stat-label {
            font-size: 11px;
            color: var(--it-gray);
            font-weight: 500;
            margin-top: 4px;
        }

        .it-admin .resource-usage {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .it-admin .resource-bar-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .it-admin .resource-bar-item .bar-label {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            font-weight: 600;
        }

        .it-admin .resource-bar-item .bar-label span:first-child {
            color: var(--it-dark);
        }

        .it-admin .resource-bar-item .bar-label span:last-child {
            color: var(--it-gray);
        }

        .it-admin .resource-bar-item .bar-track {
            height: 8px;
            background: #f1f5f9;
            border-radius: 10px;
            overflow: hidden;
        }

        .it-admin .resource-bar-item .bar-fill {
            height: 100%;
            border-radius: 10px;
            transition: width 0.8s ease;
        }

        .it-admin .resource-bar-item .bar-fill.blue { background: linear-gradient(90deg, #3b82f6, #6366f1); }
        .it-admin .resource-bar-item .bar-fill.green { background: linear-gradient(90deg, #10b981, #34d399); }
        .it-admin .resource-bar-item .bar-fill.orange { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
        .it-admin .resource-bar-item .bar-fill.red { background: linear-gradient(90deg, #ef4444, #f87171); }

        @media (max-width: 1200px) {
            .it-metrics-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            .it-admin .admin-links-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 992px) {
            .it-admin .content-grid {
                grid-template-columns: 1fr;
            }
            .it-metrics-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .it-admin .sidebar {
                transform: translateX(-100%);
            }
            .it-admin .sidebar.active {
                transform: translateX(0);
                box-shadow: 0 0 40px rgba(0,0,0,0.3);
            }
            .it-admin .main-content {
                margin-left: 0;
            }
            .it-metrics-grid {
                grid-template-columns: 1fr 1fr;
            }
            .it-admin .dashboard-container {
                padding: 16px;
            }
            .it-admin .top-header {
                padding: 0 16px;
            }
            .it-admin .admin-links-grid {
                grid-template-columns: 1fr;
            }
            .it-admin .action-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 480px) {
            .it-metrics-grid {
                grid-template-columns: 1fr;
            }
        }

        .it-admin .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,0.6);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .it-admin .modal-overlay.active {
            display: flex;
        }

        .it-admin .modal-box {
            background: white;
            border-radius: 16px;
            padding: 28px;
            max-width: 560px;
            width: 92%;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            animation: modalIn 0.25s ease-out;
        }

        @keyframes modalIn {
            from { transform: scale(0.95) translateY(10px); opacity: 0; }
            to { transform: scale(1) translateY(0); opacity: 1; }
        }

        .it-admin .modal-box .modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--it-border);
        }

        .it-admin .modal-box .modal-head h3 {
            font-size: 17px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .it-admin .modal-box .modal-head h3 i {
            color: var(--it-accent);
        }

        .it-admin .modal-box .modal-close {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: none;
            background: #f1f5f9;
            color: var(--it-gray);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            transition: all 0.2s;
        }

        .it-admin .modal-box .modal-close:hover {
            background: #e2e8f0;
            color: var(--it-dark);
        }

        .it-admin table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .it-admin table.data-table th {
            text-align: left;
            padding: 12px 14px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--it-gray);
            background: #f8fafc;
            border-bottom: 2px solid var(--it-border);
        }

        .it-admin table.data-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #f1f5f9;
            color: var(--it-dark);
        }

        .it-admin table.data-table tr:hover td {
            background: #f8fafc;
        }

        .it-admin .form-group {
            margin-bottom: 16px;
        }

        .it-admin .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--it-dark);
            margin-bottom: 6px;
        }

        .it-admin .form-group input,
        .it-admin .form-group select,
        .it-admin .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--it-border);
            border-radius: 8px;
            font-size: 13px;
            outline: none;
            transition: all 0.2s;
        }

        .it-admin .form-group input:focus,
        .it-admin .form-group select:focus,
        .it-admin .form-group textarea:focus {
            border-color: var(--it-accent);
            box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
        }

        .it-admin .mb-4 { margin-bottom: 16px; }

        .it-admin .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }

        .it-admin .btn-primary {
            background: linear-gradient(135deg, var(--it-accent), #3730a3);
            color: white;
            box-shadow: 0 4px 6px rgba(79,70,229,0.2);
        }

        .it-admin .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 12px rgba(79,70,229,0.3);
        }

        .it-admin .btn-secondary {
            background: #f1f5f9;
            color: var(--it-dark);
            border: 1px solid var(--it-border);
        }

        .it-admin .btn-secondary:hover {
            background: #e2e8f0;
        }

        .it-admin .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .it-admin .text-success { color: var(--it-success); }
        .it-admin .text-danger { color: var(--it-danger); }
        .it-admin .text-warning { color: var(--it-warning); }

        .it-admin .monitor-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .it-admin .monitor-item {
            padding: 16px;
            background: #f8fafc;
            border-radius: 10px;
            border: 1px solid var(--it-border);
        }

        .it-admin .monitor-item h4 {
            font-size: 13px;
            font-weight: 600;
            color: var(--it-gray);
            margin-bottom: 8px;
        }

        .it-admin .monitor-item p {
            font-size: 22px;
            font-weight: 800;
            color: var(--it-dark);
        }

        .it-admin .logs-box {
            max-height: 200px;
            overflow-y: auto;
            background: #0f172a;
            color: #a5b4fc;
            padding: 16px;
            border-radius: 10px;
            font-family: 'SF Mono', 'Fira Code', monospace;
            font-size: 12px;
            line-height: 1.6;
        }

        .it-admin .logs-box .log-line {
            color: rgba(255,255,255,0.6);
        }

        .it-admin .logs-box .log-line .time {
            color: var(--it-gray);
        }

        .it-admin .logs-box .log-line .ok { color: var(--it-success); }
        .it-admin .logs-box .log-line .err { color: var(--it-danger); }
        .it-admin .logs-box .log-line .warn { color: var(--it-warning); }

        .it-admin .backup-section {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .it-admin .backup-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .it-admin .backup-list li {
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
            color: var(--it-dark);
        }

        .it-admin .backup-list li:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body class="it-admin">
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo-icon"><i class="fas fa-shield-halved"></i></div>
            <div>
                <h3>Smart Campus Assistant</h3>
                <small>System Administration</small>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section-label">Main</div>
            <a href="system-admin.php" class="nav-item active"><i class="fas fa-gauge-high"></i> Dashboard</a>
            <a href="#" class="nav-item" onclick="showUserManagement()"><i class="fas fa-users-gear"></i> User Management</a>
            <a href="#" class="nav-item" onclick="showSystemMonitoring()"><i class="fas fa-server"></i> System Monitoring</a>
            <a href="#" class="nav-item" onclick="showDatabaseBackup()"><i class="fas fa-database"></i> Database & Backup</a>
            <a href="#" class="nav-item" onclick="showSystemSettings()"><i class="fas fa-sliders"></i> System Settings</a>

            <div class="nav-section-label">Account</div>
            <a href="#" class="nav-item" onclick="showProfile()"><i class="fas fa-user-circle"></i> Profile</a>
            <a href="#" onclick="logout()" class="nav-item"><i class="fas fa-right-from-bracket"></i> Logout</a>
        </nav>
        <div style="padding: 16px; border-top: 1px solid rgba(255,255,255,0.06);">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:8px;height:8px;border-radius:50%;background:var(--it-success);box-shadow:0 0 8px rgba(16,185,129,0.6);animation:pulse 2s infinite;"></div>
                <span style="font-size:12px;color:rgba(255,255,255,0.4);">System Online</span>
            </div>
        </div>
    </aside>

    <div class="sidebar-backdrop" onclick="toggleSidebar()"></div>

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button class="mobile-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <h2><i class="fas fa-gauge-high"></i> IT Dashboard</h2>
            </div>
            <div class="header-right">
                <div class="header-search">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search controls..." id="globalSearch">
                </div>
                <div class="header-icon-btn">
                    <i class="fas fa-bell"></i>
                    <span class="notif-dot"></span>
                </div>
                <div class="user-profile">
                    <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                    <span><?php echo htmlspecialchars($user_name); ?></span>
                </div>
            </div>
        </header>

        <div class="dashboard-container">
            <!-- Status Banner -->
            <div class="status-banner">
                <div class="status-banner-left">
                    <div class="icon-wrap"><i class="fas fa-terminal"></i></div>
                    <div>
                        <h1>System Control Center</h1>
                        <p>Monitor, manage, and maintain your campus infrastructure from a single pane of glass.</p>
                    </div>
                </div>
                <div class="status-banner-right">
                    <div class="status-pill">
                        <span class="dot"></span>
                        All Systems Operational
                    </div>
                    <div class="status-pill">
                        <i class="fas fa-clock" style="color: rgba(255,255,255,0.4);"></i>
                        <span id="liveClock">--:--:--</span>
                    </div>
                </div>
            </div>

            <!-- IT Metrics Grid -->
            <div class="it-metrics-grid">
                <div class="it-metric-card">
                    <div class="metric-top">
                        <div class="metric-icon green"><i class="fas fa-arrow-trend-up"></i></div>
                        <span class="metric-trend up">+2.1%</span>
                    </div>
                    <div class="metric-value" id="totalUsers">0</div>
                    <div class="metric-label">Total Users</div>
                </div>
                <div class="it-metric-card">
                    <div class="metric-top">
                        <div class="metric-icon purple"><i class="fas fa-user-shield"></i></div>
                        <span class="metric-trend up">Stable</span>
                    </div>
                    <div class="metric-value" id="totalAdmins">0</div>
                    <div class="metric-label">Admin Accounts</div>
                </div>
                <div class="it-metric-card">
                    <div class="metric-top">
                        <div class="metric-icon blue"><i class="fas fa-wifi"></i></div>
                    </div>
                    <div class="metric-value" id="systemUptimeMetric">--</div>
                    <div class="metric-label">System Uptime</div>
                </div>
                <div class="it-metric-card">
                    <div class="metric-top">
                        <div class="metric-icon orange"><i class="fas fa-user-group"></i></div>
                    </div>
                    <div class="metric-value" id="activeSessionsMetric">--</div>
                    <div class="metric-label">Active Sessions</div>
                </div>
                <div class="it-metric-card">
                    <div class="metric-top">
                        <div class="metric-icon teal"><i class="fas fa-database"></i></div>
                    </div>
                    <div class="metric-value" id="dbSizeMetric">--</div>
                    <div class="metric-label">Database Size</div>
                </div>
                <div class="it-metric-card">
                    <div class="metric-top">
                        <div class="metric-icon indigo"><i class="fas fa-server"></i></div>
                    </div>
                    <div class="metric-value" id="systemStatus">--</div>
                    <div class="metric-label">System Status</div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- System Health -->
                <div class="it-panel">
                    <div class="it-panel-header">
                        <h3><i class="fas fa-heart-pulse"></i> System Health</h3>
                        <span style="font-size:12px;color:var(--it-success);font-weight:600;"><span class="dot" style="display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--it-success);margin-right:6px;"></span>All OK</span>
                    </div>
                    <div class="it-panel-body">
                        <div class="service-list">
                            <div class="service-item">
                                <div class="service-item-left">
                                    <i class="fas fa-database"></i>
                                    <div>
                                        <div class="service-name">MySQL Database</div>
                                        <div class="service-desc">Primary data store</div>
                                    </div>
                                </div>
                                <div class="service-status">
                                    <span class="dot online"></span>
                                    <span class="online">Connected</span>
                                </div>
                            </div>
                            <div class="service-item">
                                <div class="service-item-left">
                                    <i class="fas fa-gear"></i>
                                    <div>
                                        <div class="service-name">Web Server</div>
                                        <div class="service-desc">Apache / Nginx</div>
                                    </div>
                                </div>
                                <div class="service-status">
                                    <span class="dot online"></span>
                                    <span class="online">Running</span>
                                </div>
                            </div>
                            <div class="service-item">
                                <div class="service-item-left">
                                    <i class="fas fa-envelope"></i>
                                    <div>
                                        <div class="service-name">Mail Service</div>
                                        <div class="service-desc">SMTP / Notifications</div>
                                    </div>
                                </div>
                                <div class="service-status">
                                    <span class="dot online"></span>
                                    <span class="online">Operational</span>
                                </div>
                            </div>
                            <div class="service-item">
                                <div class="service-item-left">
                                    <i class="fas fa-brain"></i>
                                    <div>
                                        <div class="service-name">AI Engine</div>
                                        <div class="service-desc">ML Predictions</div>
                                    </div>
                                </div>
                                <div class="service-status">
                                    <span class="dot online"></span>
                                    <span class="online">Active</span>
                                </div>
                            </div>
                            <div class="service-item">
                                <div class="service-item-left">
                                    <i class="fas fa-rotate"></i>
                                    <div>
                                        <div class="service-name">Backup Service</div>
                                        <div class="service-desc">Scheduled backups</div>
                                    </div>
                                </div>
                                <div class="service-status">
                                    <span class="dot warning"></span>
                                    <span class="warning">Due Soon</span>
                                </div>
                            </div>
                            <div class="service-item">
                                <div class="service-item-left">
                                    <i class="fas fa-lock"></i>
                                    <div>
                                        <div class="service-name">SSL Certificate</div>
                                        <div class="service-desc">TLS 1.3</div>
                                    </div>
                                </div>
                                <div class="service-status">
                                    <span class="dot online"></span>
                                    <span class="online">Valid</span>
                                </div>
                            </div>
                        </div>
                        <div style="margin-top:16px;">
                            <div class="resource-usage">
                                <div class="resource-bar-item">
                                    <div class="bar-label">
                                        <span>CPU Usage</span>
                                        <span id="cpuUsageText">--</span>
                                    </div>
                                    <div class="bar-track">
                                        <div class="bar-fill blue" id="cpuUsageBar" style="width:0%"></div>
                                    </div>
                                </div>
                                <div class="resource-bar-item">
                                    <div class="bar-label">
                                        <span>Memory Usage</span>
                                        <span id="memoryUsageText">--</span>
                                    </div>
                                    <div class="bar-track">
                                        <div class="bar-fill green" id="memoryUsageBar" style="width:0%"></div>
                                    </div>
                                </div>
                                <div class="resource-bar-item">
                                    <div class="bar-label">
                                        <span>Disk Usage</span>
                                        <span id="diskUsageText">--</span>
                                    </div>
                                    <div class="bar-track">
                                        <div class="bar-fill orange" id="diskUsageBar" style="width:0%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Operations -->
                <div class="it-panel">
                    <div class="it-panel-header">
                        <h3><i class="fas fa-bolt"></i> Quick Operations</h3>
                    </div>
                    <div class="it-panel-body">
                        <div class="action-grid">
                            <button class="action-btn" onclick="showUserManagement()">
                                <i class="fas fa-users-gear"></i>
                                Manage Users
                            </button>
                            <button class="action-btn" onclick="showDatabaseBackup()">
                                <i class="fas fa-database"></i>
                                Run Backup
                            </button>
                            <button class="action-btn" onclick="showSystemMonitoring()">
                                <i class="fas fa-server"></i>
                                Monitor Health
                            </button>
                            <button class="action-btn" onclick="showSystemSettings()">
                                <i class="fas fa-sliders"></i>
                                Settings
                            </button>

                        </div>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="it-panel content-grid-full">
                    <div class="it-panel-header">
                        <h3><i class="fas fa-bullhorn"></i> Notifications & Communications</h3>
                        <span style="font-size:12px;color:var(--it-gray);">Send events and announcements to all students</span>
                    </div>
                    <div class="it-panel-body">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
                            <div style="background:#f8fafc;border-radius:12px;border:1px solid var(--it-border);padding:20px;">
                                <h4 style="font-size:14px;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px;color:var(--it-dark);">
                                    <span style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,var(--it-accent),#3730a3);display:flex;align-items:center;justify-content:center;color:white;font-size:14px;"><i class="fas fa-calendar-plus"></i></span>
                                    Create Event
                                </h4>
                                <form id="eventForm">
                                    <div class="form-group">
                                        <label>Event Title</label>
                                        <input type="text" id="eventTitle" required placeholder="e.g. Career Fair 2026">
                                    </div>
                                    <div class="form-group">
                                        <label>Event Date</label>
                                        <input type="date" id="eventDate" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Details</label>
                                        <textarea id="eventMessage" rows="3" required placeholder="Event details, location, time..."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary" style="width:100%;">
                                        <i class="fas fa-paper-plane"></i> Send to All Students
                                    </button>
                                </form>
                            </div>
                            <div style="background:#f8fafc;border-radius:12px;border:1px solid var(--it-border);padding:20px;">
                                <h4 style="font-size:14px;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px;color:var(--it-dark);">
                                    <span style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#f59e0b,#d97706);display:flex;align-items:center;justify-content:center;color:white;font-size:14px;"><i class="fas fa-megaphone"></i></span>
                                    Create Announcement
                                </h4>
                                <form id="announcementForm">
                                    <div class="form-group">
                                        <label>Title</label>
                                        <input type="text" id="announcementTitle" required placeholder="e.g. Campus Closure Notice">
                                    </div>
                                    <div class="form-group">
                                        <label>Message</label>
                                        <textarea id="announcementMessage" rows="5" required placeholder="Write your announcement here..."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary" style="width:100%;background:linear-gradient(135deg,#f59e0b,#d97706);">
                                        <i class="fas fa-bullhorn"></i> Send Announcement
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity & System Summary -->
                <div class="content-grid">
                    <div class="it-panel">
                        <div class="it-panel-header">
                            <h3><i class="fas fa-clock-rotate-left"></i> Recent Activity</h3>
                            <button class="btn btn-sm btn-secondary" onclick="loadRecentActivity()"><i class="fas fa-rotate"></i></button>
                        </div>
                        <div class="it-panel-body" style="padding: 12px 24px;">
                            <div class="timeline-feed" id="activityFeed">
                                <div style="text-align:center;padding:20px;color:var(--it-gray);"><i class="fas fa-spinner fa-spin"></i> Loading activities...</div>
                            </div>
                        </div>
                    </div>
                    <div class="it-panel">
                        <div class="it-panel-header">
                            <h3><i class="fas fa-circle-info"></i> System Summary</h3>
                            <span id="lastBackupLabel" style="font-size:12px;color:var(--it-gray);">Last backup: <strong id="lastBackup">Never</strong></span>
                        </div>
                        <div class="it-panel-body">
                            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px;">
                                <div class="compact-stat-card">
                                    <div class="stat-num" id="totalStudentsStat">0</div>
                                    <div class="stat-label"><i class="fas fa-user-graduate" style="margin-right:4px;color:var(--it-accent);"></i>Students</div>
                                </div>
                                <div class="compact-stat-card">
                                    <div class="stat-num" id="totalCoursesStat">0</div>
                                    <div class="stat-label"><i class="fas fa-book" style="margin-right:4px;color:var(--it-accent);"></i>Courses</div>
                                </div>
                                <div class="compact-stat-card">
                                    <div class="stat-num" id="totalSchedulesStat">0</div>
                                    <div class="stat-label"><i class="fas fa-calendar" style="margin-right:4px;color:var(--it-accent);"></i>Schedules</div>
                                </div>
                            </div>
                            <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--it-border);">
                                <div class="service-item" style="padding:8px 0;">
                                    <div class="service-item-left">
                                        <div>
                                            <div class="service-name">PHP Version</div>
                                        </div>
                                    </div>
                                    <span style="font-size:13px;color:var(--it-gray);font-weight:500;"><?php echo phpversion(); ?></span>
                                </div>
                                <div class="service-item" style="padding:8px 0;">
                                    <div class="service-item-left">
                                        <div>
                                            <div class="service-name">Server Software</div>
                                        </div>
                                    </div>
                                    <span style="font-size:13px;color:var(--it-gray);font-weight:500;"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span>
                                </div>
                                <div class="service-item" style="padding:8px 0;">
                                    <div class="service-item-left">
                                        <div>
                                            <div class="service-name">Platform</div>
                                        </div>
                                    </div>
                                    <span style="font-size:13px;color:var(--it-gray);font-weight:500;"><?php echo php_uname('s') . ' ' . php_uname('r'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- User Management Modal -->
    <div id="userManagementModalOverlay" class="modal-overlay">
        <div class="modal-box" style="max-width: 720px;">
            <div class="modal-head">
                <h3><i class="fas fa-users-gear"></i> User Management</h3>
                <div style="display:flex;gap:8px;align-items:center;">
                    <div style="position:relative;">
                        <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--it-gray);font-size:12px;"></i>
                        <input type="text" id="userSearchInput" placeholder="Search users..." oninput="loadUsers()" style="padding:6px 12px 6px 32px;border:1px solid var(--it-border);border-radius:8px;font-size:12px;width:160px;outline:none;">
                    </div>
                    <button class="btn btn-sm btn-primary" onclick="toggleAddUserForm()"><i class="fas fa-plus"></i> Add User</button>
                    <button class="modal-close" onclick="closeModal('userManagementModalOverlay')">&times;</button>
                </div>
            </div>
            <div id="addUserForm" style="display:none;margin-bottom:20px;padding:20px;background:linear-gradient(135deg,#f8fafc,#f1f5f9);border-radius:12px;border:1px solid var(--it-border);">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--it-border);">
                    <span style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,var(--it-accent),#3730a3);display:flex;align-items:center;justify-content:center;color:white;font-size:16px;"><i class="fas fa-user-plus"></i></span>
                    <div>
                        <div style="font-size:14px;font-weight:700;color:var(--it-dark);">New User</div>
                        <div style="font-size:11px;color:var(--it-gray);">Create a new user account</div>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" id="newUserFullName" placeholder="e.g. John Doe">
                    </div>
                    <div class="form-group">
                        <label>Username <span style="color:var(--it-danger);">*</span></label>
                        <input type="text" id="newUserUsername" required placeholder="jdoe">
                    </div>
                    <div class="form-group">
                        <label>Email <span style="color:var(--it-danger);">*</span></label>
                        <input type="email" id="newUserEmail" required placeholder="john@example.com">
                    </div>
                    <div class="form-group">
                        <label>Password <span style="color:var(--it-danger);">*</span></label>
                        <input type="password" id="newUserPassword" required placeholder="Min 8 characters">
                    </div>
                    <div class="form-group">
                        <label>Role <span style="color:var(--it-danger);">*</span></label>
                        <select id="newUserRole">
                            <option value="student">Student</option>
                            <option value="administrator">Administrator</option>
                        </select>
                    </div>
                    <div class="form-group" style="display:flex;align-items:flex-end;">
                        <button class="btn btn-primary" onclick="addUser()" style="width:100%;">
                            <i class="fas fa-user-check"></i> Create User
                        </button>
                    </div>
                </div>
                <div id="addUserMessage" style="font-size:13px;font-weight:600;margin-top:10px;"></div>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:30px;"></th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th style="width:110px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                    <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--it-gray);"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- System Monitoring Modal -->
    <div id="systemMonitoringModalOverlay" class="modal-overlay">
        <div class="modal-box" style="max-width: 700px;">
            <div class="modal-head">
                <h3><i class="fas fa-server"></i> System Monitoring</h3>
                <button class="modal-close" onclick="closeModal('systemMonitoringModalOverlay')">&times;</button>
            </div>
            <div class="monitor-grid">
                <div class="monitor-item">
                    <h4>Server Status</h4>
                    <p style="color:var(--it-success);font-weight:700;" id="serverStatus">Loading...</p>
                </div>
                <div class="monitor-item">
                    <h4>PHP Version</h4>
                    <p id="phpVersion">-</p>
                </div>
                <div class="monitor-item">
                    <h4>MySQL Version</h4>
                    <p id="mysqlVersion">-</p>
                </div>
                <div class="monitor-item">
                    <h4>Uptime</h4>
                    <p id="uptime">-</p>
                </div>
                <div class="monitor-item">
                    <h4>DB Connections</h4>
                    <p id="dbConnections">-</p>
                </div>
                <div class="monitor-item">
                    <h4>Active Queries</h4>
                    <p id="activeSessions">-</p>
                </div>
                <div class="monitor-item">
                    <h4>Response Time</h4>
                    <p id="responseTime">-</p>
                </div>
                <div class="monitor-item">
                    <h4>Database Size</h4>
                    <p id="dbSize">-</p>
                </div>
            </div>
            <div style="margin-top:8px;text-align:right;font-size:11px;color:var(--it-gray);" id="lastChecked"></div>
            <div style="margin-top:12px;">
                <h4 style="font-size:13px;font-weight:600;color:var(--it-gray);margin-bottom:8px;">Recent Logs</h4>
                <div class="logs-box" id="systemLogs" style="max-height:180px;overflow-y:auto;">
                    <div class="log-line" style="color:var(--it-gray);">Loading...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Database Backup Modal -->
    <div id="databaseBackupModalOverlay" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-head">
                <h3><i class="fas fa-database"></i> Database & Backup</h3>
                <button class="modal-close" onclick="closeModal('databaseBackupModalOverlay')">&times;</button>
            </div>
            <div class="backup-section">
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <button class="btn btn-primary" onclick="performBackup()">
                        <i class="fas fa-circle-play"></i> Create Full Backup
                    </button>
                    <span style="font-size:12px;color:var(--it-gray);">Creates a complete database snapshot</span>
                </div>
                <div id="backupStatus" style="font-size:13px;font-weight:600;"></div>
            </div>
            <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--it-border);">
                <h4 style="font-size:13px;font-weight:700;margin-bottom:12px;">Recent Backups</h4>
                <ul class="backup-list" id="backupList">
                    <li style="color:var(--it-gray);">No backups found.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- System Settings Modal -->
    <div id="systemSettingsModalOverlay" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-head">
                <h3><i class="fas fa-sliders"></i> System Settings</h3>
                <button class="modal-close" onclick="closeModal('systemSettingsModalOverlay')">&times;</button>
            </div>
            <form id="settingsForm">
                <div class="form-group">
                    <label>System Name</label>
                    <input type="text" id="systemName" value="Smart Campus Assistant">
                </div>
                <div class="form-group">
                    <label>Max Login Attempts</label>
                    <input type="number" id="maxLoginAttempts" value="5">
                </div>
                <div class="form-group">
                    <label>Session Timeout (minutes)</label>
                    <input type="number" id="sessionTimeout" value="60">
                </div>
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" id="enableAI" checked style="width:auto;">
                        Enable AI Features
                    </label>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">
                    <i class="fas fa-floppy-disk"></i> Save Settings
                </button>
            </form>
        </div>
    </div>

    <!-- Profile Modal -->
    <div id="profileModalOverlay" class="modal-overlay">
        <div class="modal-box" style="max-width: 480px;">
            <div class="modal-head">
                <h3><i class="fas fa-user-circle"></i> My Profile</h3>
                <button class="modal-close" onclick="closeModal('profileModalOverlay')">&times;</button>
            </div>
            <div id="profileView">
                <div style="text-align:center;padding:20px 0;">
                    <div style="width:72px;height:72px;background:linear-gradient(135deg,var(--it-accent),var(--it-accent-light));border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:28px;color:white;font-weight:700;" id="profileAvatar">U</div>
                    <h4 style="font-size:18px;font-weight:700;color:var(--it-dark);" id="profileName">User</h4>
                    <p style="font-size:13px;color:var(--it-gray);" id="profileRole">---</p>
                </div>
                <div style="border-top:1px solid var(--it-border);padding-top:16px;">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" id="profileUsername" disabled style="background:#f8fafc;">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="profileEmail" disabled style="background:#f8fafc;">
                    </div>
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" id="profileNameInput" placeholder="Your full name">
                    </div>
                    <button class="btn btn-primary" onclick="saveProfile()" style="width:100%;"><i class="fas fa-floppy-disk"></i> Update Profile</button>
                </div>
                <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--it-border);">
                    <h4 style="font-size:13px;font-weight:700;margin-bottom:12px;color:var(--it-dark);"><i class="fas fa-key"></i> Change Password</h4>
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" id="profileCurrentPassword" placeholder="Enter current password">
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" id="profileNewPassword" placeholder="Min 6 characters">
                    </div>
                    <button class="btn btn-primary" onclick="changeProfilePassword()" style="width:100%;"><i class="fas fa-key"></i> Change Password</button>
                    <div id="profileMessage" style="font-size:13px;font-weight:600;margin-top:8px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container"></div>
    <script src="../assets/js/admin.js?v=<?php echo filemtime('../assets/js/admin.js'); ?>"></script>
    <script>
        function escapeHtml(text) {
            if (typeof text !== 'string') return String(text || '');
            return text.replace(/[&<>"']/g, function(c) {
                return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
            });
        }

        function loadSystemStats() {
            fetch('../../api/admin/system-stats.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.success || !data.data) return;
                    const d = data.data;
                    document.getElementById('totalUsers').textContent = d.totalUsers || 0;
                    document.getElementById('totalAdmins').textContent = d.totalAdmins || 0;
                    document.getElementById('lastBackup').textContent = d.lastBackup || 'Never';
                    document.getElementById('systemStatus').textContent = d.systemStatus || 'Offline';
                    if (d.totalStudents) document.getElementById('totalStudentsStat').textContent = d.totalStudents;
                    if (d.totalCourses) document.getElementById('totalCoursesStat').textContent = d.totalCourses;
                    if (d.totalSchedules) document.getElementById('totalSchedulesStat').textContent = d.totalSchedules;
                })
                .catch(error => {
                    console.error('Error loading stats:', error);
                    document.getElementById('systemStatus').textContent = 'Offline';
                });
            
            // Load system monitor metrics for the dashboard
            fetch('../../api/admin/system-monitor.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.success || !data.data) return;
                    const d = data.data;
                    const uptimeEl = document.getElementById('systemUptimeMetric');
                    if (uptimeEl && d.uptime) uptimeEl.textContent = d.uptime;
                    const activeEl = document.getElementById('activeSessionsMetric');
                    if (activeEl && d.active_sessions !== undefined) activeEl.textContent = d.active_sessions || 0;
                    const dbSizeEl = document.getElementById('dbSizeMetric');
                    if (dbSizeEl && d.db_size) dbSizeEl.textContent = d.db_size;

                    // Resource bars
                    if (d.cpu_usage !== undefined) {
                        const cpuVal = Math.min(100, Math.max(0, d.cpu_usage));
                        const cpuText = document.getElementById('cpuUsageText');
                        const cpuBar = document.getElementById('cpuUsageBar');
                        if (cpuText) cpuText.textContent = cpuVal + '%';
                        if (cpuBar) cpuBar.style.width = cpuVal + '%';
                    }
                    if (d.memory_usage !== undefined) {
                        const memVal = Math.min(100, Math.max(0, d.memory_usage));
                        const memText = document.getElementById('memoryUsageText');
                        const memBar = document.getElementById('memoryUsageBar');
                        if (memText) memText.textContent = memVal + '%';
                        if (memBar) memBar.style.width = memVal + '%';
                    }
                    if (d.disk_usage !== undefined) {
                        const diskVal = Math.min(100, Math.max(0, d.disk_usage));
                        const diskText = document.getElementById('diskUsageText');
                        const diskBar = document.getElementById('diskUsageBar');
                        if (diskText) diskText.textContent = diskVal + '%';
                        if (diskBar) diskBar.style.width = diskVal + '%';
                    }
                })
                .catch(error => console.warn('Could not load monitor metrics:', error));
        }

        function showUserManagement() {
            document.getElementById('userManagementModalOverlay').classList.add('active');
            loadUsers();
        }

        function showSystemMonitoring() {
            document.getElementById('systemMonitoringModalOverlay').classList.add('active');
            loadSystemMonitor();
        }

        async function loadSystemMonitor() {
            try {
                const resp = await fetch('../../api/admin/system-monitor.php');
                const data = await resp.json();
                if (!data.success) return;
                const d = data.data;
                document.getElementById('serverStatus').textContent = d.server_status;
                document.getElementById('serverStatus').style.color = d.server_online ? 'var(--it-success)' : 'var(--it-danger)';
                document.getElementById('dbConnections').textContent = d.db_connections;
                document.getElementById('activeSessions').textContent = d.active_sessions;
                document.getElementById('responseTime').textContent = d.response_time;
                document.getElementById('phpVersion').textContent = d.php_version;
                document.getElementById('mysqlVersion').textContent = d.mysql_version;
                document.getElementById('dbSize').textContent = d.db_size;
                document.getElementById('uptime').textContent = d.uptime;
                document.getElementById('lastChecked').textContent = 'Last checked: ' + d.last_checked;

                const logsBox = document.getElementById('systemLogs');
                if (d.logs && d.logs.length > 0) {
                    logsBox.innerHTML = d.logs.map(line => {
                        const cls = line.includes('[error]') || line.includes('[ERROR]') ? 'err' :
                                    line.includes('[warn]') || line.includes('[WARN]') ? 'warn' : 'ok';
                        const label = cls === 'err' ? '[ERR]' : cls === 'warn' ? '[WARN]' : '[OK]';
                        const time = line.match(/\[(\d{2}-\w{3}-\d{4}\s+\d{2}:\d{2}:\d{2})/)?.[1] ||
                                     line.match(/(\d{2}:\d{2}:\d{2})/)?.[1] || '--:--:--';
                        return `<div class="log-line"><span class="time">[${escapeHtml(time)}]</span> <span class="${cls}">${label}</span> ${escapeHtml(line)}</div>`;
                    }).join('');
                } else {
                    logsBox.innerHTML = '<div class="log-line" style="color:var(--it-gray);">No recent logs found.</div>';
                }
            } catch (e) {
                console.error('Monitor load error:', e);
            }
        }

        function loadSystemHealth() {
            fetch('../../api/admin/system-health.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.success || !data.data || !data.data.services) {
                        console.error('Failed to load system health');
                        return;
                    }
                    const services = data.data.services;
                    const summary = data.data.summary;
                    const overallStatus = data.data.overall_status;
                    
                    // Update overall status indicator
                    const statusSpan = document.querySelector('.it-panel-header [style*="color:var(--it-success)"]') || 
                                      document.querySelector('.it-panel-header .dot');
                    if (statusSpan) {
                        const statusText = overallStatus === 'healthy' ? 'All OK' :
                                          overallStatus === 'warning' ? 'Warning' : 'Critical';
                        const statusColor = overallStatus === 'healthy' ? 'var(--it-success)' :
                                           overallStatus === 'warning' ? 'var(--it-warning)' : 'var(--it-danger)';
                        const statusDot = statusSpan.querySelector('.dot') || document.createElement('span');
                        statusDot.className = 'dot';
                        statusDot.style.display = 'inline-block';
                        statusDot.style.width = '7px';
                        statusDot.style.height = '7px';
                        statusDot.style.borderRadius = '50%';
                        statusDot.style.background = statusColor;
                        statusDot.style.marginRight = '6px';
                        if (!statusSpan.querySelector('.dot')) {
                            statusSpan.insertBefore(statusDot, statusSpan.firstChild);
                        }
                    }
                    
                    // Update service statuses
                    const serviceList = document.querySelector('.service-list');
                    if (serviceList && services) {
                        const serviceOrder = ['mysql_database', 'web_server', 'mail_service', 'ai_engine', 'backup_service', 'ssl_certificate'];
                        const serviceNames = {
                            'mysql_database': { name: 'MySQL Database', icon: 'fas fa-database', desc: 'Primary data store' },
                            'web_server': { name: 'Web Server', icon: 'fas fa-gear', desc: 'Apache / Nginx' },
                            'mail_service': { name: 'Mail Service', icon: 'fas fa-envelope', desc: 'SMTP / Notifications' },
                            'ai_engine': { name: 'AI Engine', icon: 'fas fa-brain', desc: 'ML Predictions' },
                            'backup_service': { name: 'Backup Service', icon: 'fas fa-rotate', desc: 'Scheduled backups' },
                            'ssl_certificate': { name: 'SSL Certificate', icon: 'fas fa-lock', desc: 'TLS 1.3' }
                        };
                        
                        serviceList.innerHTML = '';
                        serviceOrder.forEach(key => {
                            if (services[key]) {
                                const service = services[key];
                                const info = serviceNames[key];
                                const statusClass = service.status === 'healthy' ? 'online' :
                                                   service.status === 'warning' ? 'warning' :
                                                   service.status === 'critical' ? 'offline' : 'warning';
                                const displayStatus = service.status === 'healthy' ? 'Healthy' :
                                                     service.status === 'warning' ? 'Warning' :
                                                     service.status === 'critical' ? 'Critical' : 'Unknown';
                                
                                const html = `
                                    <div class="service-item">
                                        <div class="service-item-left">
                                            <i class="${info.icon}"></i>
                                            <div>
                                                <div class="service-name">${info.name}</div>
                                                <div class="service-desc">${escapeHtml(service.message)}</div>
                                            </div>
                                        </div>
                                        <div class="service-status">
                                            <span class="dot ${statusClass}"></span>
                                            <span class="${statusClass}">${displayStatus}</span>
                                        </div>
                                    </div>
                                `;
                                serviceList.innerHTML += html;
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading system health:', error);
                });
        }

        function showDatabaseBackup() {
            document.getElementById('databaseBackupModalOverlay').classList.add('active');
            loadBackupHistory();
        }

        function showSystemSettings() {
            document.getElementById('systemSettingsModalOverlay').classList.add('active');
            loadSettingsModal();
        }

        function loadSettingsModal() {
            // Load settings into the form when modal opens (prevents race condition)
            fetch('../../api/admin/settings.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        if (data.data.system_name) document.getElementById('systemName').value = data.data.system_name;
                        if (data.data.max_login_attempts) document.getElementById('maxLoginAttempts').value = data.data.max_login_attempts;
                        if (data.data.session_timeout) document.getElementById('sessionTimeout').value = data.data.session_timeout;
                        if (data.data.enable_ai !== undefined) document.getElementById('enableAI').checked = data.data.enable_ai;
                    }
                })
                .catch(err => {
                    console.error('Error loading settings:', err);
                    alert('Could not load settings. Please try again.');
                });
        }

        function closeModal(overlayId) {
            document.getElementById(overlayId).classList.remove('active');
        }

        function loadUsers() {
            const tbody = document.getElementById('userTableBody');
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:24px;color:var(--it-gray);"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>';
            const search = (document.getElementById('userSearchInput')?.value || '').trim().toLowerCase();
            fetch('../../api/admin/get-users.php')
                .then(response => response.json())
                .then(data => {
                    tbody.innerHTML = '';
                    let users = data.success ? data.data : data;
                    if (!Array.isArray(users)) users = [];
                    if (search) {
                        users = users.filter(u =>
                            (u.username || '').toLowerCase().includes(search) ||
                            (u.email || '').toLowerCase().includes(search) ||
                            (u.full_name || '').toLowerCase().includes(search) ||
                            (u.role || '').toLowerCase().includes(search)
                        );
                    }
                    if (users.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:24px;color:var(--it-gray);"><i class="fas fa-search" style="margin-right:6px;"></i> No users found.</td></tr>';
                        return;
                    }
                    users.forEach(user => {
                        const isSystemAdmin = user.role === 'system_admin';
                        const initial = (user.full_name || user.username || 'U')[0].toUpperCase();
                        const roleColors = { student: '#3b82f6', administrator: '#f59e0b', system_admin: '#ef4444' };
                        const roleColor = roleColors[user.role] || 'var(--it-gray)';
                        const roleLabels = { student: 'Student', administrator: 'Admin', system_admin: 'Super Admin' };
                        const roleLabel = roleLabels[user.role] || user.role;
                        const activeBadge = user.is_active
                            ? '<span class="status-badge present" style="font-size:11px;">Active</span>'
                            : '<span class="status-badge absent" style="font-size:11px;">Inactive</span>';
                        const roles = ['student', 'administrator', 'system_admin'];
                        const options = roles.map(r =>
                            `<option value="${r}"${r === user.role ? ' selected' : ''}>${roleLabels[r] || r}</option>`
                        ).join('');
                        tbody.innerHTML += `
                            <tr>
                                <td><div style="width:30px;height:30px;border-radius:50%;background:${roleColor}22;color:${roleColor};display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;">${initial}</div></td>
                                <td><strong>${escapeHtml(user.username)}</strong></td>
                                <td style="font-size:13px;">${escapeHtml(user.email || '—')}</td>
                                <td>
                                    <select onchange="changeRole(${user.user_id}, this)" style="padding:4px 8px;border-radius:6px;border:1px solid var(--it-border);font-size:12px;font-weight:600;background:white;color:var(--it-dark);cursor:pointer;">
                                        ${options}
                                    </select>
                                </td>
                                <td>${activeBadge}</td>
                                <td style="white-space:nowrap;">
                                    ${!isSystemAdmin ? `
                                    <button class="btn btn-sm" onclick="deleteUser(${user.user_id})" style="padding:5px 10px;background:#fee2e2;color:#dc2626;box-shadow:none;font-size:12px;" title="Delete User">
                                        <i class="fas fa-trash-can"></i>
                                    </button>
                                    ` : '<span style="font-size:11px;color:var(--it-gray);">Protected</span>'}
                                </td>
                            </tr>
                        `;
                    });
                })
                .catch(error => {
                    document.getElementById('userTableBody').innerHTML = '<tr><td colspan="6" style="text-align:center;padding:24px;color:var(--it-danger);">Error loading users.</td></tr>';
                    console.error('Error loading users:', error);
                });
        }

        function loadBackupHistory() {
            fetch('../../api/admin/backup-history.php')
                .then(response => response.json())
                .then(data => {
                    const list = document.getElementById('backupList');
                    list.innerHTML = '';
                    const backups = data.success ? data.data : data;
                    if (!Array.isArray(backups) || backups.length === 0) {
                        list.innerHTML = '<li style="color:var(--it-gray);">No backups found.</li>';
                        return;
                    }
                    backups.forEach(backup => {
                        list.innerHTML += `<li><i class="fas fa-circle-check" style="color:var(--it-success);margin-right:8px;"></i> ${backup.createdAt} &mdash; ${backup.fileName}</li>`;
                    });
                })
                .catch(() => {
                    document.getElementById('backupList').innerHTML = '<li style="color:var(--it-danger);">Error loading backup history.</li>';
                });
        }

        function performBackup() {
            const status = document.getElementById('backupStatus');
            status.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating backup...';
            status.style.color = 'var(--it-gray)';
            fetch('../../api/admin/backup-database.php', { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        status.innerHTML = '<i class="fas fa-circle-exclamation"></i> ' + data.error;
                        status.style.color = 'var(--it-danger)';
                        return;
                    }
                    status.innerHTML = '<i class="fas fa-circle-check"></i> ' + (data.message || 'Backup created successfully!');
                    status.style.color = 'var(--it-success)';
                    loadBackupHistory();
                    loadSystemStats();
                })
                .catch(() => {
                    document.getElementById('backupStatus').innerHTML = '<i class="fas fa-circle-exclamation"></i> Error creating backup';
                    document.getElementById('backupStatus').style.color = 'var(--it-danger)';
                });
        }

        function loadRecentActivity() {
            const feed = document.getElementById('activityFeed');
            feed.innerHTML = '<div style="text-align:center;padding:20px;color:var(--it-gray);"><i class="fas fa-spinner fa-spin"></i> Refreshing...</div>';
            fetch('../../api/admin/activity-feed.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.success || !data.data || !data.data.activities) {
                        feed.innerHTML = '<div style="color:var(--it-gray);padding:20px;text-align:center;">No activities found.</div>';
                        return;
                    }
                    const activities = data.data.activities;
                    if (activities.length === 0) {
                        feed.innerHTML = '<div style="color:var(--it-gray);padding:20px;text-align:center;">No activities recorded yet.</div>';
                        return;
                    }
                    feed.innerHTML = activities.map(activity => {
                        const dotClass = activity.action_type.includes('backup') ? 'green' : 
                                        activity.action_type.includes('user_delete') ? 'red' :
                                        activity.action_type.includes('announcement') ? 'blue' :
                                        activity.action_type.includes('settings') ? 'orange' : 'green';
                        return `<div class="timeline-item">
                            <div class="tl-dot ${dotClass}"></div>
                            <div class="tl-text">${escapeHtml(activity.action)}</div>
                            <div class="tl-time"><i class="far fa-clock"></i> ${escapeHtml(activity.time_text)}</div>
                        </div>`;
                    }).join('');
                })
                .catch(error => {
                    console.error('Error loading activities:', error);
                    feed.innerHTML = '<div style="color:var(--it-danger);padding:20px;text-align:center;">Error loading activities.</div>';
                });
        }

        function toggleAddUserForm() {
            const form = document.getElementById('addUserForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
            if (form.style.display === 'block') {
                document.getElementById('newUserUsername').focus();
            }
        }

        function addUser() {
            const username = document.getElementById('newUserUsername').value.trim();
            const email = document.getElementById('newUserEmail').value.trim();
            const password = document.getElementById('newUserPassword').value;
            const role = document.getElementById('newUserRole').value;
            const fullName = document.getElementById('newUserFullName').value.trim();
            const msg = document.getElementById('addUserMessage');

            if (!username || !email || !password) {
                msg.innerHTML = '<span style="color:var(--it-danger);">Please fill in all required fields.</span>';
                return;
            }
            if (password.length < 8) {
                msg.innerHTML = '<span style="color:var(--it-danger);">Password must be at least 8 characters.</span>';
                return;
            }

            msg.innerHTML = '<span style="color:var(--it-gray);"><i class="fas fa-spinner fa-spin"></i> Creating user...</span>';

            const payload = { username, email, password, role };
            if (fullName) payload.full_name = fullName;

            fetch('../../api/auth/register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    msg.innerHTML = '<span style="color:var(--it-success);"><i class="fas fa-circle-check"></i> User created successfully!</span>';
                    document.getElementById('newUserUsername').value = '';
                    document.getElementById('newUserEmail').value = '';
                    document.getElementById('newUserPassword').value = '';
                    document.getElementById('newUserFullName').value = '';
                    loadUsers();
                    loadSystemStats();
                    setTimeout(() => { document.getElementById('addUserForm').style.display = 'none'; msg.innerHTML = ''; }, 2000);
                } else {
                    msg.innerHTML = '<span style="color:var(--it-danger);"><i class="fas fa-circle-exclamation"></i> ' + (data.message || 'Failed to create user.') + '</span>';
                }
            })
            .catch(() => {
                msg.innerHTML = '<span style="color:var(--it-danger);"><i class="fas fa-circle-exclamation"></i> Network error. Please try again.</span>';
            });
        }

        function changeRole(userId, select) {
            const newRole = select.value;
            const validRoles = ['student', 'administrator', 'system_admin'];
            
            // Validate role
            if (!validRoles.includes(newRole)) {
                alert('Invalid role selected.');
                loadUsers(); // Reload to reset UI
                return;
            }
            
            // Confirmation before role change
            if (!confirm(`Change user role to "${newRole}"? This action cannot be undone.`)) {
                loadUsers(); // Reload to reset UI
                return;
            }
            
            fetch('../../api/admin/update-user-role.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, role: newRole })
            })
            .then(response => response.json())
            .then(data => {
                if (typeof showToast === 'function') {
                    showToast(data.message || (data.success ? 'Role updated' : 'Failed to update role'), data.success ? 'success' : 'error');
                } else {
                    alert(data.message || (data.success ? 'Role updated' : 'Failed to update role'));
                }
                loadUsers();
            })
            .catch(error => {
                console.error('Error updating role:', error);
                alert('Network error: Could not update role');
                loadUsers();
            });
        }

        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                fetch('../../api/admin/delete-user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (typeof showToast === 'function') {
                            showToast('User deleted successfully', 'success');
                        } else {
                            alert(data.message || 'User deleted successfully');
                        }
                        loadUsers();
                        loadSystemStats();
                    } else {
                        alert(data.message || 'Failed to delete user');
                    }
                })
                .catch(error => {
                    alert('Error deleting user: ' + error.message);
                    console.error('Delete error:', error);
                });
            }
        }

        function showProfile() {
            closeModal('userManagementModalOverlay');
            closeModal('systemMonitoringModalOverlay');
            closeModal('databaseBackupModalOverlay');
            closeModal('systemSettingsModalOverlay');
            document.getElementById('profileModalOverlay').classList.add('active');
            document.getElementById('profileMessage').innerHTML = '';

            fetch('../../api/admin/system-profile.php')
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.data) {
                        const d = res.data;
                        document.getElementById('profileAvatar').textContent = (d.name || 'U')[0].toUpperCase();
                        document.getElementById('profileName').textContent = d.name || 'User';
                        document.getElementById('profileRole').textContent = d.role || '---';
                        document.getElementById('profileUsername').value = d.username || '';
                        document.getElementById('profileEmail').value = d.email || '';
                        document.getElementById('profileNameInput').value = d.name || '';
                    }
                })
                .catch(() => {});
        }

        function saveProfile() {
            const name = document.getElementById('profileNameInput').value.trim();
            const msg = document.getElementById('profileMessage');
            if (!name || name.length < 2) {
                msg.innerHTML = '<span style="color:var(--it-danger);">Name must be at least 2 characters.</span>';
                return;
            }
            msg.innerHTML = '<span style="color:var(--it-gray);"><i class="fas fa-spinner fa-spin"></i> Saving...</span>';
            fetch('../../api/admin/system-profile.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    msg.innerHTML = '<span style="color:var(--it-success);"><i class="fas fa-circle-check"></i> Profile updated!</span>';
                    document.getElementById('profileName').textContent = name;
                    document.getElementById('profileAvatar').textContent = name[0].toUpperCase();
                    document.querySelector('.user-profile span').textContent = name;
                    setTimeout(() => closeModal('profileModalOverlay'), 1500);
                } else {
                    msg.innerHTML = '<span style="color:var(--it-danger);"><i class="fas fa-circle-exclamation"></i> ' + (res.message || 'Failed to update profile') + '</span>';
                }
            })
            .catch(() => {
                msg.innerHTML = '<span style="color:var(--it-danger);"><i class="fas fa-circle-exclamation"></i> Network error.</span>';
            });
        }

        function changeProfilePassword() {
            const current = document.getElementById('profileCurrentPassword').value;
            const newPass = document.getElementById('profileNewPassword').value;
            const msg = document.getElementById('profileMessage');
            if (!current || !newPass) {
                msg.innerHTML = '<span style="color:var(--it-danger);">Fill in both password fields.</span>';
                return;
            }
            if (newPass.length < 6) {
                msg.innerHTML = '<span style="color:var(--it-danger);">New password must be at least 6 characters.</span>';
                return;
            }
            msg.innerHTML = '<span style="color:var(--it-gray);"><i class="fas fa-spinner fa-spin"></i> Changing password...</span>';
            fetch('../../api/auth/change-password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ current_password: current, new_password: newPass })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    msg.innerHTML = '<span style="color:var(--it-success);"><i class="fas fa-circle-check"></i> Password changed!</span>';
                    document.getElementById('profileCurrentPassword').value = '';
                    document.getElementById('profileNewPassword').value = '';
                } else {
                    msg.innerHTML = '<span style="color:var(--it-danger);"><i class="fas fa-circle-exclamation"></i> ' + (res.message || 'Failed to change password') + '</span>';
                }
            })
            .catch(() => {
                msg.innerHTML = '<span style="color:var(--it-danger);"><i class="fas fa-circle-exclamation"></i> Network error.</span>';
            });
        }

        function logout() {
            if (!confirm('Are you sure you want to logout?')) return;
            fetch('../../api/auth/logout.php')
                .then(() => { window.location.href = '../index.php'; })
                .catch(error => console.error('Logout error:', error));
        }

        function updateClock() {
            const now = new Date();
            const time = now.toTimeString().split(' ')[0];
            document.getElementById('liveClock').textContent = time;
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadSystemStats();
            loadSystemHealth();
            loadRecentActivity();
            updateClock();
            setInterval(updateClock, 1000);
            setInterval(loadSystemHealth, 30000); // Refresh system health every 30 seconds
            setInterval(() => {
                // Refresh dashboard metrics periodically
                const statsBtn = document.querySelector('[onclick*="loadSystemStats"]');
                if (!statsBtn) loadSystemStats(); // Only refresh if not in a modal
            }, 60000); // Refresh stats every 60 seconds
            if (typeof initNotificationForms === 'function') initNotificationForms();

            document.getElementById('settingsForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = this.querySelector('button[type="submit"]');
                
                // Validate form values
                const systemName = document.getElementById('systemName').value.trim();
                const maxLoginAttempts = parseInt(document.getElementById('maxLoginAttempts').value);
                const sessionTimeout = parseInt(document.getElementById('sessionTimeout').value);
                
                if (!systemName) {
                    alert('System name is required.');
                    return;
                }
                if (maxLoginAttempts < 1 || maxLoginAttempts > 100) {
                    alert('Max login attempts must be between 1 and 100.');
                    return;
                }
                if (sessionTimeout < 1 || sessionTimeout > 1440) {
                    alert('Session timeout must be between 1 and 1440 minutes.');
                    return;
                }
                
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                btn.disabled = true;
                const payload = {
                    system_name: systemName,
                    max_login_attempts: maxLoginAttempts,
                    session_timeout: sessionTimeout,
                    enable_ai: document.getElementById('enableAI').checked
                };
                fetch('../../api/admin/settings.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (typeof showToast === 'function') {
                            showToast('Settings saved successfully!', 'success');
                        } else {
                            alert('Settings saved successfully!');
                        }
                        closeModal('systemSettingsModalOverlay');
                    } else {
                        if (typeof showToast === 'function') {
                            showToast(data.message || 'Failed to save settings', 'error');
                        } else {
                            alert(data.message || 'Failed to save settings');
                        }
                    }
                })
                .catch(err => {
                    console.error('Settings save error:', err);
                    alert('Network error saving settings');
                })
                .finally(() => {
                    btn.innerHTML = '<i class="fas fa-floppy-disk"></i> Save Settings';
                    btn.disabled = false;
                });
            });

            const searchInput = document.getElementById('globalSearch');
            if (searchInput) {
                const searchActions = {
                    'user': () => showUserManagement(),
                    'users': () => showUserManagement(),
                    'add user': () => { showUserManagement(); setTimeout(() => toggleAddUserForm(), 300); },
                    'monitor': () => showSystemMonitoring(),
                    'monitoring': () => showSystemMonitoring(),
                    'health': () => showSystemMonitoring(),
                    'backup': () => showDatabaseBackup(),
                    'database': () => showDatabaseBackup(),
                    'settings': () => showSystemSettings(),
                    'event': () => { document.querySelector('.it-panel .btn-primary')?.scrollIntoView({behavior:'smooth'}); },
                    'announcement': () => { document.querySelector('.it-panel .btn-primary')?.scrollIntoView({behavior:'smooth'}); },
                    'activity': () => document.getElementById('activityFeed')?.closest('.it-panel')?.scrollIntoView({behavior:'smooth'}),
                    'dashboard': () => window.location.href = 'system-admin.php',
                };
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        const q = this.value.trim().toLowerCase();
                        if (!q) return;
                        let matched = false;
                        for (const [key, fn] of Object.entries(searchActions)) {
                            if (q === key || key.includes(q)) {
                                fn();
                                matched = true;
                                break;
                            }
                        }
                        if (!matched) {
                            const allPanels = document.querySelectorAll('.it-panel-header h3');
                            for (const panel of allPanels) {
                                if (panel.textContent.toLowerCase().includes(q)) {
                                    panel.closest('.it-panel')?.scrollIntoView({behavior:'smooth', block:'center'});
                                    panel.closest('.it-panel')?.style.setProperty('border-color', 'var(--it-accent)', 'important');
                                    setTimeout(() => panel.closest('.it-panel')?.style.removeProperty('border-color'), 3000);
                                    matched = true;
                                    break;
                                }
                            }
                        }
                        if (!matched) {
                            this.style.borderColor = 'var(--it-danger)';
                            this.placeholder = 'No results for "' + this.value + '"';
                            setTimeout(() => { this.style.borderColor = ''; this.placeholder = 'Search controls...'; }, 2000);
                        } else {
                            this.value = '';
                        }
                    }
                });
                searchInput.addEventListener('input', function() {
                    this.style.borderColor = '';
                });
            }
        });

    </script>
</body>
</html>
