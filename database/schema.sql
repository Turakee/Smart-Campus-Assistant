-- AI-Powered Smart Campus Assistant Database Schema
-- Created based on Project Documentation Section 5.2

CREATE DATABASE IF NOT EXISTS smart_campus_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smart_campus_db;

-- 1. Users Table (Central Authentication)
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'administrator', 'system_admin') NOT NULL,
    is_system_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_role (role),
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- 2. Students Table (Profile Details)
CREATE TABLE IF NOT EXISTS students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    level INT,
    enrollment_year YEAR,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_department (department)
) ENGINE=InnoDB;

-- 3. Administrators Table (Staff Details)
CREATE TABLE IF NOT EXISTS administrators (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    position VARCHAR(100),
    gender ENUM('female', 'other') DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. Departments Table
CREATE TABLE IF NOT EXISTS departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL,
    department_code VARCHAR(20) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO departments (department_name, department_code) VALUES
('Computer Science', 'CSC'),
('Mathematics', 'MTH'),
('General Studies', 'GST'),
('Physics', 'PHY'),
('Chemistry', 'CHM'),
('Biology', 'BIO'),
('Engineering', 'ENG'),
('Business Admin', 'BUS');

-- 5. Courses Table
CREATE TABLE IF NOT EXISTS courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(100) NOT NULL,
    course_code VARCHAR(20) UNIQUE NOT NULL,
    credit_hours INT,
    lecturer_name VARCHAR(100),
    department_id INT DEFAULT NULL,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL,
    INDEX idx_code (course_code),
    INDEX idx_department (department_id)
) ENGINE=InnoDB;

-- 5. Schedules Table
CREATE TABLE IF NOT EXISTS schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    room_number VARCHAR(50),
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    INDEX idx_day (day_of_week)
) ENGINE=InnoDB;

-- 5.1 Student Courses (Enrollment)
CREATE TABLE IF NOT EXISTS student_courses (
    student_course_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, course_id)
) ENGINE=InnoDB;

-- 6. Attendance Table
CREATE TABLE IF NOT EXISTS attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('present', 'absent', 'late', 'excused') DEFAULT 'absent',
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (student_id, course_id, date),
    INDEX idx_date (date)
) ENGINE=InnoDB;

-- 7. Resources Table (Rooms/Labs)
CREATE TABLE IF NOT EXISTS resources (
    resource_id INT AUTO_INCREMENT PRIMARY KEY,
    resource_name VARCHAR(100) NOT NULL,
    resource_type ENUM('classroom', 'lab', 'auditorium', 'other') DEFAULT 'classroom',
    capacity INT,
    INDEX idx_type (resource_type)
) ENGINE=InnoDB;

-- 8. Bookings Table
CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    resource_id INT NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    purpose TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (resource_id) REFERENCES resources(resource_id) ON DELETE CASCADE,
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- 9. Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'danger') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read)
) ENGINE=InnoDB;

-- 10. AI Analytics Log (For C++ Engine Results)
CREATE TABLE IF NOT EXISTS ai_analytics_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    prediction_type ENUM('schedule_optimization', 'attendance_risk', 'performance') NOT NULL,
    prediction_result TEXT,
    risk_level ENUM('low', 'medium', 'high') DEFAULT 'low',
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    INDEX idx_type (prediction_type)
) ENGINE=InnoDB;

-- 11. Admin Audit Log (Compliance & Security Tracking)
CREATE TABLE IF NOT EXISTS admin_audit_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    details JSON,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_action_type (action_type),
    INDEX idx_admin_id (admin_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Insert Default System Admin (Password: admin123)
INSERT INTO users (username, email, password_hash, role) VALUES 
('sysadmin', 'admin@campus.edu', '$2y$10$nHJ.6X9.knJclIFKFL3P7uYgZAx1laaVd8XrGC6vrfWJ/w572Q6l6', 'system_admin');

INSERT INTO administrators (user_id, full_name, position) VALUES 
(1, 'System Administrator', 'IT Director');