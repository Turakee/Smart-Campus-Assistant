# CampusEase AI — Complete Project Documentation

**Version:** 1.0.0 (Production Ready)  
**Last Updated:** May 26, 2026  
**Status:** Live

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [Installation & Setup](#installation--setup)
3. [System Architecture](#system-architecture)
4. [Database Setup](#database-setup)
5. [API Integration](#api-integration)
6. [Security Best Practices](#security-best-practices)
7. [Deployment Guide](#deployment-guide)
8. [Troubleshooting](#troubleshooting)
9. [Contributing](#contributing)

---

## Project Overview

CampusEase AI is an intelligent campus management system that brings together a PHP-based web dashboard, a Flutter mobile app, a C++ AI engine for predictive analytics, and a RESTful API layer — all backed by MySQL.

### What It Does

**For Students:**
- Personal dashboard with a complete academic overview
- Class schedules with built-in conflict detection
- Attendance tracking with risk predictions (spot trouble before it happens)
- Resource booking — classrooms, labs, and auditoriums
- AI-powered academic insights and recommendations
- Voice-enabled interactions and real-time notifications

**For Administrators:**
- Full control over students, courses, and schedules
- Attendance monitoring and analytics dashboards
- Resource booking approval workflow
- System-wide announcements and event creation

**For System Administrators:**
- User and role management with granular permissions
- System health monitoring and database backup
- Security configuration and audit logging
- API key management

### Technology Stack

| Component | Technology |
|---|---|
| Backend Framework | PHP 8.2+ |
| Frontend Web | HTML5, CSS3, JavaScript, Bootstrap 5 |
| Mobile App | Flutter (Dart) with Material Design 3 |
| AI Engine | C++ (predictive analytics module) |
| Database | MySQL 8.0+ (InnoDB) |
| Web Server | Apache 2.4+ / Nginx |
| Authentication | bcrypt (cost 12) + JWT tokens |
| API Style | RESTful with JSON responses |

---

## Installation & Setup

### What You'll Need

**Server:**
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache 2.4+ or Nginx
- OpenSSL (for HTTPS)
- At least 500 MB free disk space

**Development:**
- VS Code or any code editor
- MySQL Workbench (handy but optional)
- Postman or Insomnia for API testing
- Flutter SDK 3.0+ (for mobile app development)
- C++ compiler — g++ or Visual Studio (for AI engine)

### Step 1: Get the Code

```bash
cd C:\xampp\htdocs
git clone https://github.com/Turakee/smart_campus.git
cd smart_campus
```

Or simply extract the project archive into your web server's document root.

### Step 2: Set Up the Database

The database schema is ready to go. You'll find it at `database/schema.sql`.

**Option A — Command Line:**

```bash
mysql -u root -p < database/schema.sql
```

**Option B — phpMyAdmin:**

1. Open `http://localhost:8080/phpmyadmin`
2. Click the **Import** tab
3. Choose the `database/schema.sql` file
4. Click **Go**

**Verify everything is in place:**

```bash
mysql -u root -p smart_campus_db -e "SHOW TABLES;"
```

You should see 12 tables: users, students, administrators, courses, schedules, student_courses, attendance, resources, bookings, notifications, ai_analytics_log, and admin_audit_log.

### Step 3: Configure the Application

The system now uses a `.env` file for configuration. Create one at the project root:

```bash
# Copy the template
cp .env.example .env
```

Edit `.env` with your settings:

```env
DB_HOST=localhost
DB_NAME=smart_Campus_db
DB_USER=root
DB_PASSWORD=
JWT_SECRET=your_super_secret_jwt_key_change_this
ENCRYPTION_KEY=your_encryption_key_change_this
BASE_URL=http://localhost:8080/smart_campus/
SITE_NAME=CampusEase AI
TIMEZONE=UTC
```

The application reads these values automatically. No need to touch `config/config.php` or `config/database.php` manually.

> **Important:** Never commit `.env` to version control. It's already in `.gitignore`.

### Step 4: Set File Permissions

```bash
# Linux / macOS
chmod -R 755 public/ api/ config/
chmod 600 .env

# Windows (XAMPP)
# Permissions are usually handled automatically. If you run into write issues,
# make sure the Apache user has write access to logs/ and backups/ directories.
```

### Step 5: Verify Everything Works

1. **Open the app:** Navigate to `http://localhost:8080/smart_campus/`

2. **Test the database connection:**
   ```
   http://localhost:8080/smart_campus/test_api.php
   ```

3. **Test the login API:**
   ```bash
   curl -X POST http://localhost:8080/smart_campus/api/auth/login \
     -H "Content-Type: application/json" \
     -d '{"username":"sysadmin","password":"admin123"}'
   ```

   You should get a response like this:
   ```json
   {
     "success": true,
     "message": "Login successful",
     "data": {
       "user_id": 1,
       "username": "sysadmin",
       "role": "system_admin"
     }
   }
   ```

### Step 6: Log In

| Role | Username | Password |
|---|---|---|
| System Administrator | `sysadmin` | `admin123` |

Open the admin dashboard at `http://localhost:8080/smart_campus/public/admin/dashboard.php` and log in. From there, you can create additional users, courses, and populate the system with data.

---

## System Architecture

### How Everything Fits Together

The system follows a classic three-tier architecture:

```
┌──────────────────────────────────────────┐
│          Presentation Layer              │
│  (Flutter Mobile + Web Dashboard HTML)   │
└──────────────────┬───────────────────────┘
                   │
                   │  JSON over HTTP(S)
                   ▼
┌──────────────────────────────────────────┐
│     Business Logic Layer                 │
│  (PHP Backend — RESTful API Endpoints)   │
└──────────────────┬───────────────────────┘
                   │
         ┌─────────┴──────────┐
         ▼                    ▼
┌──────────────────┐  ┌──────────────────┐
│  AI Engine       │  │  Data Layer      │
│  (C++ Module)    │  │  (MySQL)         │
└──────────────────┘  └──────────────────┘
```

The web frontend communicates with the PHP backend through RESTful JSON endpoints. The Flutter mobile app does the same. The AI engine runs as a separate C++ process, called on demand by the backend when predictions are needed.

### Directory Layout

```
smart_campus/
├── api/                          # API endpoints organized by domain
│   ├── admin/                    # Admin operations (backup, settings, stats)
│   ├── ai/                       # AI predictions (chatbot, attendance, performance)
│   ├── attendance/               # Attendance records
│   ├── auth/                     # Login, logout, registration
│   ├── booking/                  # Resource booking CRUD
│   ├── course/                   # Course management
│   ├── notifications/            # Push notifications
│   ├── resource/                 # Campus resources
│   ├── schedule/                 # Class schedules
│   └── student/                  # Student data
├── config/                       # Core configuration
│   ├── config.php               # App config, auth helpers, CORS
│   ├── database.php             # PDO database connection
│   ├── AIEngine.php             # Shared AI engine wrapper
│   ├── Validator.php            # Input validation rules
│   ├── Utilities.php            # Helpers (JWT, sanitization)
│   └── Security.php             # Encryption, audit logging
├── database/
│   └── schema.sql               # Complete database schema
├── public/                       # Web frontend
│   ├── admin/                   # Admin dashboard pages
│   ├── student/                 # Student portal pages
│   ├── assets/                  # CSS, JavaScript, images
│   └── index.php                # Entry point
├── mobile/                       # Flutter application
│   ├── lib/                     # Dart source code
│   └── pubspec.yaml             # Dependencies
├── ai-engine/                    # C++ prediction engine
│   ├── main.cpp
│   ├── include/
│   └── bin/
├── docs/                         # Documentation
│   ├── COMPLETE_DOCUMENTATION.md
│   ├── API_DOCUMENTATION.md
│   ├── TESTING_GUIDE.md
│   └── ...
├── backups/                      # Database backup files
└── .env                          # Environment variables (not in git)
```

### API Response Format

Every endpoint returns a consistent JSON structure:

```json
{
  "success": true,
  "message": "Human-readable message",
  "data": { ... },
  "timestamp": "2026-05-26 14:30:00"
}
```

- `success` — `true` or `false`
- `message` — What happened, in plain language
- `data` — The actual payload (object, array, or empty `[]`)
- `timestamp` — When the response was generated

---

## Database Setup

### Tables at a Glance

| Table | What It Stores | Key Fields |
|---|---|---|
| **users** | Login credentials and roles | user_id, username, email, role, password_hash |
| **students** | Student profiles | student_id, user_id, full_name, department, level |
| **administrators** | Admin profiles | admin_id, user_id, full_name, position |
| **courses** | Course catalog | course_id, course_name, course_code, credit_hours |
| **schedules** | Class timetables | schedule_id, course_id, day_of_week, start_time, end_time |
| **student_courses** | Enrollment records | student_course_id, student_id, course_id |
| **attendance** | Daily attendance logs | attendance_id, student_id, course_id, date, status |
| **resources** | Campus facilities | resource_id, resource_name, resource_type, capacity |
| **bookings** | Resource reservations | booking_id, student_id, resource_id, date, status |
| **notifications** | In-app messages | notification_id, user_id, message, type, is_read |
| **ai_analytics_log** | AI prediction history | log_id, student_id, prediction_type, risk_level |
| **admin_audit_log** | Admin activity trail | log_id, admin_id, action, action_type, details |

### Creating Users Through the API

Once the system administrator account is set up, you can create additional users via the registration API:

```bash
curl -X POST http://localhost:8080/smart_campus/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "username": "student001",
    "email": "student@example.com",
    "password": "SecurePass123",
    "full_name": "John Doe",
    "department": "Computer Science",
    "level": 100
  }'
```

---

## API Integration

### Authentication

Protected endpoints need authentication. The system supports two methods:

**Session-based (used by the web dashboard):**
The session cookie is set automatically when you log in through the browser.

```
Cookie: PHPSESSID=your_session_id
```

**Bearer token (used by the mobile app):**
After login, include the token in the `Authorization` header.

```
Authorization: Bearer your_jwt_token_here
```

### Common API Calls

**Login:**

```bash
POST /api/auth/login
{
  "username": "student001",
  "password": "password123"
}
```

```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user_id": 5,
    "role": "student",
    "token": "jwt_token_here"
  }
}
```

**Get a student's schedule:**

```bash
GET /api/schedule/get
Authorization: Bearer your_token
```

```json
{
  "success": true,
  "message": "Schedule retrieved",
  "data": [
    {
      "schedule_id": 1,
      "course_name": "Introduction to Programming",
      "course_code": "CS101",
      "day_of_week": "Monday",
      "start_time": "09:00:00",
      "end_time": "10:30:00",
      "room_number": "A101"
    }
  ]
}
```

**Check attendance:**

```bash
GET /api/attendance/get?course_id=1
Authorization: Bearer your_token
```

### Error Handling

When something goes wrong, the API always responds with a consistent structure:

```json
{
  "success": false,
  "message": "What went wrong, explained clearly",
  "data": []
}
```

HTTP status codes are used appropriately:
- **200** — Success
- **400** — Bad request (missing or invalid parameters)
- **401** — Unauthenticated (login required)
- **403** — Forbidden (wrong role)
- **404** — Resource not found
- **405** — Wrong HTTP method
- **500** — Server error

---

## Security Best Practices

### What's Already in Place

**Authentication:**
- Passwords are hashed with bcrypt at cost 12
- Both session-based and JWT authentication supported
- Failed login attempts are tracked; accounts lock after 5 failures

**Authorization:**
- Role-Based Access Control with three tiers: student, administrator, system_admin
- Every protected endpoint verifies permissions before executing

**Data Protection:**
- All database queries use prepared statements (no SQL injection)
- Input is sanitized to prevent XSS attacks
- CSRF tokens are validated on state-changing operations
- Security headers are set on every response

**Monitoring:**
- All admin actions are logged to the audit trail
- Failed access attempts are recorded
- Suspicious activity can be detected through log analysis

### Configuration You Should Change

**1. Environment variables** (already set up — just update the values):

```env
JWT_SECRET=a_long_random_string_at_least_32_characters
ENCRYPTION_KEY=another_long_random_string
```

**2. Force HTTPS in production:**

In `config/config.php`, uncomment or add:

```php
if (empty($_SERVER['HTTPS']) && $_SERVER['HTTP_HOST'] !== 'localhost') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}
```

**3. Restrict CORS origins to your actual domains:**

```php
$allowed_origins = [
    'https://yourdomain.com',
    'https://app.yourdomain.com'
];
```

### Production Security Checklist

Before going live, run through this list:

- [ ] Change the default `sysadmin` password
- [ ] Generate strong JWT and encryption keys
- [ ] Install a valid SSL certificate and enable HTTPS
- [ ] Restrict CORS to your actual domains
- [ ] Turn off `display_errors` in PHP (`display_errors = Off`)
- [ ] Enable PHP error logging to a file
- [ ] Set up automated database backups
- [ ] Configure firewall rules
- [ ] Set up rate limiting (especially on login)
- [ ] Test every authentication and authorization path

---

## Deployment Guide

### Local Development (XAMPP)

```bash
# Start Apache and MySQL from the XAMPP Control Panel
# Then open:
#   http://localhost:8080/smart_campus/

# Quick sanity checks:
php test_api.php
php test_login.php
```

### Staging Server

```bash
# 1. Copy the project to your staging server
scp -r . user@staging-server:/var/www/smart_campus

# 2. Set up the environment
cd /var/www/smart_campus
cp .env.example .env
# Edit .env with your staging credentials

# 3. Import the database
mysql -u root -p < database/schema.sql

# 4. Verify the API responds
curl http://staging-server/smart_campus/api/auth/login
```

### Production Server

**Apache configuration:**

```apache
<VirtualHost *:80>
    ServerName campus.yourdomain.com
    DocumentRoot /var/www/smart_campus/public
    
    <Directory /var/www/smart_campus/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    Redirect permanent / https://campus.yourdomain.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName campus.yourdomain.com
    DocumentRoot /var/www/smart_campus/public
    
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/campus.yourdomain.com.crt
    SSLCertificateKeyFile /etc/ssl/private/campus.yourdomain.com.key
    
    <Directory /var/www/smart_campus/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Permissions:**

```bash
chmod -R 755 /var/www/smart_campus
chmod -R 775 /var/www/smart_campus/logs /var/www/smart_campus/backups
chmod 600 /var/www/smart_campus/.env
```

**PHP settings for production (`php.ini`):**

```ini
display_errors = Off
log_errors = On
error_log = /var/log/php-errors.log
upload_max_filesize = 50M
post_max_size = 50M
session.cookie_secure = On
session.cookie_httponly = On
session.cookie_samesite = Strict
```

**Running with Docker:**

```dockerfile
FROM php:8.0-apache

RUN docker-php-ext-install pdo_mysql
RUN a2enmod rewrite

COPY . /var/www/html
RUN chmod -R 755 /var/www/html

EXPOSE 80 443
```

```bash
docker build -t smart_campus .
docker run -d \
  -p 80:80 \
  -p 443:443 \
  -e DB_HOST=db \
  -e DB_USER=root \
  -e DB_PASSWORD=your_password \
  -e DB_NAME=smart_campus_db \
  smart_campus
```

---

## Troubleshooting

### "Database connection failed"

**Most likely:** MySQL isn't running, or the credentials are wrong.

```
Check:
- Is MySQL running? (Look in XAMPP or system services)
- Are DB_HOST, DB_USER, DB_PASSWORD in .env correct?
- Does the database 'smart_campus_db' exist?
- Quick test: mysql -u root -p -e "SELECT 1"
```

### "Unauthorized — Invalid token"

**Most likely:** Your session expired, or the JWT key changed.

```
Fix:
- Log out and log back in
- If the JWT_SECRET in .env was changed, all existing tokens are invalid
- Check that the token hasn't expired (default: 24 hours)
```

### "CORS error when accessing from mobile"

**Most likely:** The mobile app's origin isn't whitelisted.

```
Fix:
- Add the mobile app's URL to $allowed_origins in config/config.php
- If testing from an emulator, add http://10.0.2.2:8080
```

### "AI predictions not working"

**Most likely:** The C++ engine binary is missing or the tables are empty.

```
Check:
- Does the AI engine binary exist at ai-engine/bin/ai_engine.exe?
- Are the input/output directories writable?
- Are there records in the ai_analytics_log table?
- Was the C++ code compiled successfully?
```

### "Attendance percentage is wrong"

**Most likely:** The attendance data or the calculation needs review.

```
Check:
- Are attendance records being created correctly?
- Are the status values correct? (present, absent, late, excused)
- The calculateAttendancePercentage() function in Utilities.php handles the math
```

### Enabling Debug Mode

When you need to dig deeper:

```php
// In config/config.php
define('DEBUG_MODE', true);

// Then check logs/debug.log for detailed output
```

---

## Contributing

### Code Style

- **PHP:** Follow PSR-12 coding standards
- **Naming:** camelCase for methods and variables, snake_case for database columns
- **Documentation:** Add PHPDoc blocks to every public method
- **Testing:** Write tests for new features

### Making a Pull Request

1. Create a feature branch: `git checkout -b feature/your-feature`
2. Make your changes following the code standards
3. Add tests and update documentation
4. Submit a pull request with a clear description of what changed and why
5. Address any review feedback

### Code Review Checklist

- [ ] Follows PSR-12 coding standards
- [ ] No security vulnerabilities introduced
- [ ] Database schema changes are included
- [ ] API documentation is updated
- [ ] Tests pass
- [ ] All inputs are validated and sanitized

---

## Support

**Email:** support@campus.edu  
**Issue Tracker:** https://github.com/yourorg/smart_campus/issues  
**Security:** security@campus.edu
