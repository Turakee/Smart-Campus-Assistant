# CampusEase AI - Smart Campus Management System

<div align="center">

![CampusEase AI](https://img.shields.io/badge/CampusEase-AI-6366f1?style=for-the-badge&logo=graduation-cap)
![PHP](https://img.shields.io/badge/PHP-8.0+-777bb4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

**An intelligent campus management system with AI-powered insights for students and administrators.**

[Features](#features) • [Tech Stack](#tech-stack) • [Installation](#installation) • [Documentation](#documentation) • [API Reference](#api-reference)

</div>

---

## 🎯 Features

### For Students
- 📊 **AI-Powered Dashboard** - Personalized overview of academic activities
- 📅 **Course Schedule** - View and manage weekly class schedules
- ✅ **Attendance Tracking** - Track attendance with real-time statistics
- 🚪 **Resource Booking** - Book rooms, labs, and facilities
- 🤖 **AI Insights** - Intelligent attendance risk analysis and schedule optimization
- 🔔 **Smart Notifications** - Real-time alerts and announcements

### For Administrators
- 👥 **Student Management** - Complete student lifecycle management
- 📚 **Course Management** - Create and manage courses with enrollments
- 📆 **Schedule Management** - Organize class schedules efficiently
- 🚪 **Booking Approval** - Review and approve resource bookings
- 📢 **Notifications** - Send events and announcements to students
- 📈 **AI Analytics** - Monitor student risk levels and system usage

---

## 🛠 Tech Stack

| Component | Technology |
|------------|------------|
| Backend | PHP 8.0+ |
| Database | MySQL 8.0 |
| Frontend | HTML5, CSS3, JavaScript |
| UI Framework | Custom CSS with modern design |
| Icons | Font Awesome 6.4 |
| Fonts | Inter (Google Fonts) |
| Authentication | Session-based with bcrypt |

---

## 📁 Project Structure

```
smart_campus/
├── api/                        # REST API endpoints
│   ├── admin/                  # Admin-specific APIs
│   │   ├── analytics.php
│   │   ├── create-announcement.php
│   │   ├── create-event.php
│   │   └── stats.php
│   ├── ai/                     # AI prediction APIs
│   │   ├── optimize-schedule.php
│   │   └── predict-attendance.php
│   ├── attendance/
│   │   └── get.php
│   ├── auth/                   # Authentication APIs
│   │   ├── login.php
│   │   ├── logout.php
│   │   └── register.php
│   ├── booking/               # Resource booking APIs
│   │   ├── history.php
│   │   ├── pending.php
│   │   ├── submit.php
│   │   └── update.php
│   ├── course/                # Course management APIs
│   │   ├── create.php
│   │   ├── delete.php
│   │   ├── enroll.php
│   │   ├── enrollments.php
│   │   ├── list.php
│   │   ├──unenroll.php
│   │   └── update.php
│   ├── notifications/
│   │   ├── get.php
│   │   └── mark-read.php
│   ├── schedule/              # Schedule management APIs
│   │   ├── create.php
│   │   ├── delete.php
│   │   ├── get.php
│   │   └── update.php
│   ├── stats/
│   │   └── get.php
│   └── student/               # Student management APIs
│       ├── delete.php
│       ├── list.php
│       └── update.php
├── config/                     # Configuration files
│   ├── config.php
│   └── database.php
├── database/
│   └── schema.sql              # Database schema
├── public/                     # Web root (serve from here)
│   ├── assets/
│   │   ├── css/
│   │   │   ├── auth.css       # Authentication styles
│   │   │   └── dashboard.css  # Dashboard styles
│   │   └── js/
│   │       ├── admin.js       # Admin dashboard logic
│   │       ├── auth.js        # Authentication logic
│   │       └── student.js     # Student dashboard logic
│   ├── admin/                 # Admin pages
│   │   ├── analytics.php
│   │   ├── bookings.php
│   │   ├── courses.php
│   │   ├── dashboard.php
│   │   ├── manage-courses.php
│   │   ├── schedule.php
│   │   └── students.php
│   ├── student/               # Student pages
│   │   ├── ai-insights.php
│   │   ├── attendance.php
│   │   ├── booking.php
│   │   ├── dashboard.php
│   │   └── schedule.php
│   └── index.php              # Login/Register page
└── README.md                   # This file
```

---

## 🚀 Installation

### Prerequisites
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Web server (Apache/Nginx) or XAMPP/WAMP

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd smart_campus
   ```

2. **Configure Database**
   - Create a MySQL database named `smart_db`
   - Import the schema:
   ```bash
   mysql -u root -p smart_db < database/schema.sql
   ```

3. **Update Configuration**
   
   Edit `config/database.php` with your credentials:
   ```php
   private $host = 'localhost';
   private $db_name = 'smart_db';
   private $username = 'root';
   private $password = 'your_password';
   ```

4. **Configure Web Server**
   
   For Apache (create `.htaccess`):
   ```apache
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^(.*)$ index.php?path=$1 [QSA,L]
   ```

5. **Access the Application**
   
   Open in browser: `http://localhost/smart_campus/public/`

### Default Admin Login
```
Username: sysadmin
Password: admin123
```

---

## 📖 Documentation

### User Roles

| Role | Access Level |
|------|--------------|
| `student` | Dashboard, Schedule, Attendance, Bookings, AI Insights |
| `administrator` | Full admin panel access |
| `system_admin` | Full access including system settings |

### Database Schema

**Core Tables:**
- `users` - User accounts with role-based access
- `students` - Student profiles
- `administrators` - Admin profiles
- `courses` - Course information
- `schedules` - Class schedules
- `student_courses` - Course enrollments
- `attendance` - Attendance records
- `resources` - Bookable facilities
- `bookings` - Resource booking requests
- `notifications` - User notifications
- `ai_analytics_log` - AI prediction history

---

## 🔌 API Reference

### Authentication

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/auth/login.php` | POST | User login |
| `/api/auth/logout.php` | GET | User logout |
| `/api/auth/register.php` | POST | New user registration |

### Student APIs

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/student/list.php` | GET | List all students |
| `/api/student/update.php` | POST | Update student |
| `/api/attendance/get.php` | GET | Get attendance records |
| `/api/schedule/get.php` | GET | Get student schedule |

### Course APIs

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/course/list.php` | GET | List all courses |
| `/api/course/create.php` | POST | Create new course |
| `/api/course/update.php` | POST | Update course |
| `/api/course/delete.php` | POST | Delete course |
| `/api/course/enroll.php` | POST | Enroll student |

### Booking APIs

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/booking/submit.php` | POST | Submit booking request |
| `/api/booking/pending.php` | GET | Get pending bookings |
| `/api/booking/history.php` | GET | Get booking history |
| `/api/booking/update.php` | POST | Approve/reject booking |

### AI APIs

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/ai/predict-attendance.php` | GET | Attendance risk prediction |
| `/api/ai/optimize-schedule.php` | POST | Schedule optimization |

---

## 🎨 UI Features

### Modern Design System
- Gradient backgrounds and cards
- Smooth animations and transitions
- Responsive layout (mobile-friendly)
- Dark sidebar navigation
- Toast notifications
- Modal dialogs

### Color Palette
| Color | Hex | Usage |
|-------|-----|-------|
| Primary | `#6366f1` | Buttons, links, accents |
| Success | `#10b981` | Positive indicators |
| Warning | `#f59e0b` | Caution states |
| Danger | `#ef4444` | Error states, alerts |
| Info | `#3b82f6` | Information badges |

---

## 🔒 Security Features

- Password hashing with bcrypt
- SQL injection prevention (prepared statements)
- Session-based authentication
- Role-based access control
- XSS protection (output escaping)
- CSRF protection ready

---

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 👨‍💻 Author

**CampusEase Development Team**

Built with ❤️ for modern campus management.

---

<div align="center">

⭐ Star this repo if you find it helpful!

</div>
