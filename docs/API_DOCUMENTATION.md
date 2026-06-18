# CampusEase AI - REST API Documentation

**Version:** 1.0.0  
**Base URL:** `http://localhost/smart_campus/api`  
**Authentication:** Bearer Token / Session-based  
**Response Format:** JSON

---

## Table of Contents

1. [Authentication Endpoints](#authentication-endpoints)
2. [Student Endpoints](#student-endpoints)
3. [Course Endpoints](#course-endpoints)
4. [Schedule Endpoints](#schedule-endpoints)
5. [Attendance Endpoints](#attendance-endpoints)
6. [Resource & Booking Endpoints](#resource--booking-endpoints)
7. [Notification Endpoints](#notification-endpoints)
8. [Admin Endpoints](#admin-endpoints)
9. [AI Endpoints](#ai-endpoints)
10. [Error Handling](#error-handling)

---

## Authentication Endpoints

### POST `/auth/login`
Authenticate user and create session.

**Request Body:**
```json
{
  "username": "student001",
  "password": "password123"
}
```

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Login successful",
  "user": {
    "user_id": 1,
    "username": "student001",
    "email": "student@example.com",
    "role": "student"
  }
}
```

**Error Response (401):**
```json
{
  "status": "error",
  "message": "Invalid credentials"
}
```

---

### POST `/auth/register`
Register new student account.

**Request Body:**
```json
{
  "username": "newstudent",
  "email": "student@example.com",
  "password": "securepass123",
  "full_name": "John Doe",
  "department": "Computer Science",
  "level": 100
}
```

**Response (201 Created):**
```json
{
  "status": "success",
  "message": "User registered successfully",
  "user_id": 10
}
```

---

### POST `/auth/logout`
Terminate user session.

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Logged out successfully"
}
```

---

## Student Endpoints

### GET `/student/profile`
Get current student's profile information.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "student_id": 1,
    "user_id": 1,
    "full_name": "John Doe",
    "department": "Computer Science",
    "level": 100,
    "enrollment_year": 2024
  }
}
```

---

### PUT `/student/profile`
Update student's profile information.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "full_name": "John Doe",
  "department": "Computer Science",
  "level": 200
}
```

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Profile updated successfully"
}
```

---

### GET `/student/list`
Get list of all students (Admin only).

**Response (200 OK):**
```json
{
  "status": "success",
  "data": [
    {
      "student_id": 1,
      "full_name": "John Doe",
      "department": "Computer Science",
      "email": "student@example.com"
    }
  ],
  "total": 50
}
```

---

### DELETE `/student/delete/{id}`
Delete student account (Admin only).

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Student deleted successfully"
}
```

---

## Course Endpoints

### GET `/course/list`
Get all available courses.

**Query Parameters:**
- `department` (optional): Filter by department
- `page` (optional): Pagination page number

**Response (200 OK):**
```json
{
  "status": "success",
  "data": [
    {
      "course_id": 1,
      "course_name": "Introduction to Programming",
      "course_code": "CS101",
      "credit_hours": 3,
      "lecturer_name": "Dr. Smith"
    }
  ],
  "total": 25
}
```

---

### GET `/course/{id}`
Get specific course details.

**Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "course_id": 1,
    "course_name": "Introduction to Programming",
    "course_code": "CS101",
    "credit_hours": 3,
    "lecturer_name": "Dr. Smith"
  }
}
```

---

### POST `/course/create`
Create new course (Admin only).

**Request Body:**
```json
{
  "course_name": "Web Development",
  "course_code": "CS202",
  "credit_hours": 3,
  "lecturer_name": "Dr. Johnson"
}
```

**Response (201 Created):**
```json
{
  "status": "success",
  "message": "Course created successfully",
  "course_id": 26
}
```

---

### PUT `/course/update/{id}`
Update course information (Admin only).

**Request Body:**
```json
{
  "course_name": "Advanced Web Development",
  "credit_hours": 4
}
```

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Course updated successfully"
}
```

---

### DELETE `/course/delete/{id}`
Delete course (Admin only).

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Course deleted successfully"
}
```

---

### POST `/course/enroll`
Enroll student in course.

**Request Body:**
```json
{
  "course_id": 1
}
```

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Enrollment successful"
}
```

---

### POST `/course/unenroll`
Unenroll student from course.

**Request Body:**
```json
{
  "course_id": 1
}
```

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Unenrollment successful"
}
```

---

### GET `/course/my-courses`
Get all enrolled courses for current student.

**Response (200 OK):**
```json
{
  "status": "success",
  "data": [
    {
      "course_id": 1,
      "course_name": "Introduction to Programming",
      "course_code": "CS101",
      "credit_hours": 3
    }
  ],
  "total": 5
}
```

---

### GET `/course/enrollments/{id}`
Get all students enrolled in a course (Admin only).

**Response (200 OK):**
```json
{
  "status": "success",
  "data": [
    {
      "student_id": 1,
      "full_name": "John Doe",
      "email": "student@example.com"
    }
  ],
  "total": 35
}
```

---

## Schedule Endpoints

### GET `/schedule/get`
Get user's schedule.

**Query Parameters:**
- `course_id` (optional): Get schedule for specific course

**Response (200 OK):**
```json
{
  "status": "success",
  "data": [
    {
      "schedule_id": 1,
      "course_id": 1,
      "course_name": "Introduction to Programming",
      "day_of_week": "Monday",
      "start_time": "09:00:00",
      "end_time": "10:30:00",
      "room_number": "A101"
    }
  ]
}
```

---

### POST `/schedule/create`
Create schedule entry (Admin only).

**Request Body:**
```json
{
  "course_id": 1,
  "day_of_week": "Monday",
  "start_time": "09:00:00",
  "end_time": "10:30:00",
  "room_number": "A101"
}
```

**Response (201 Created):**
```json
{
  "status": "success",
  "message": "Schedule created successfully",
  "schedule_id": 50
}
```

---

### PUT `/schedule/update/{id}`
Update schedule (Admin only).

**Request Body:**
```json
{
  "day_of_week": "Tuesday",
  "start_time": "10:00:00"
}
```

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Schedule updated successfully"
}
```

---

### DELETE `/schedule/delete/{id}`
Delete schedule entry (Admin only).

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Schedule deleted successfully"
}
```

---

## Attendance Endpoints

### GET `/attendance/get`
Get student's attendance records.

**Query Parameters:**
- `course_id` (optional): Filter by course
- `start_date` (optional): Filter from date (YYYY-MM-DD)
- `end_date` (optional): Filter to date (YYYY-MM-DD)

**Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "course_id": 1,
    "course_name": "Introduction to Programming",
    "total_classes": 15,
    "present": 12,
    "absent": 3,
    "percentage": 80,
    "records": [
      {
        "date": "2024-05-01",
        "status": "present"
      }
    ]
  }
}
```

---

### POST `/attendance/mark`
Mark attendance for student (Admin only).

**Request Body:**
```json
{
  "student_id": 1,
  "course_id": 1,
  "date": "2024-05-07",
  "status": "present"
}
```

**Response (201 Created):**
```json
{
  "status": "success",
  "message": "Attendance marked successfully"
}
```

---

### PUT `/attendance/update/{id}`
Update attendance record (Admin only).

**Request Body:**
```json
{
  "status": "absent"
}
```

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Attendance updated successfully"
}
```

---

### DELETE `/attendance/delete/{id}`
Delete attendance record (Admin only).

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Attendance deleted successfully"
}
```

---

## Resource & Booking Endpoints

### GET `/resource/list`
Get all available resources.

**Query Parameters:**
- `type` (optional): Filter by resource type (classroom, lab, auditorium)

**Response (200 OK):**
```json
{
  "status": "success",
  "data": [
    {
      "resource_id": 1,
      "resource_name": "Lab A1",
      "resource_type": "lab",
      "capacity": 30
    }
  ],
  "total": 15
}
```

---

### GET `/resource/{id}`
Get specific resource details.

**Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "resource_id": 1,
    "resource_name": "Lab A1",
    "resource_type": "lab",
    "capacity": 30
  }
}
```

---

### POST `/booking/submit`
Submit resource booking request.

**Request Body:**
```json
{
  "resource_id": 1,
  "booking_date": "2024-05-15",
  "start_time": "14:00:00",
  "end_time": "16:00:00",
  "purpose": "Project Development"
}
```

**Response (201 Created):**
```json
{
  "status": "success",
  "message": "Booking submitted successfully",
  "booking_id": 45
}
```

---

### GET `/booking/history`
Get booking history for current student.

**Response (200 OK):**
```json
{
  "status": "success",
  "data": [
    {
      "booking_id": 45,
      "resource_name": "Lab A1",
      "booking_date": "2024-05-15",
      "start_time": "14:00:00",
      "end_time": "16:00:00",
      "status": "approved"
    }
  ],
  "total": 10
}
```

---

### GET `/booking/pending`
Get pending booking requests (Admin only).

**Response (200 OK):**
```json
{
  "status": "success",
  "data": [
    {
      "booking_id": 45,
      "student_name": "John Doe",
      "resource_name": "Lab A1",
      "booking_date": "2024-05-15",
      "start_time": "14:00:00",
      "status": "pending"
    }
  ],
  "total": 8
}
```

---

### PUT `/booking/update/{id}`
Approve or reject booking (Admin only).

**Request Body:**
```json
{
  "status": "approved"
}
```

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Booking updated successfully"
}
```

---

### GET `/booking/{id}`
Get specific booking details.

**Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "booking_id": 45,
    "student_name": "John Doe",
    "resource_name": "Lab A1",
    "booking_date": "2024-05-15",
    "start_time": "14:00:00",
    "end_time": "16:00:00",
    "purpose": "Project Development",
    "status": "approved"
  }
}
```

---

## Notification Endpoints

### GET `/notifications/get`
Get user's notifications.

**Query Parameters:**
- `limit` (optional): Max results (default: 20)
- `unread_only` (optional): Show only unread (true/false)

**Response (200 OK):**
```json
{
  "status": "success",
  "data": [
    {
      "notification_id": 1,
      "message": "Your booking has been approved",
      "type": "success",
      "is_read": false,
      "created_at": "2024-05-07 10:30:00"
    }
  ],
  "total": 15,
  "unread_count": 3
}
```

---

### PUT `/notifications/mark-read/{id}`
Mark notification as read.

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Notification marked as read"
}
```

---

### POST `/notifications/read-all`
Mark all notifications as read.

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "All notifications marked as read"
}
```

---

### DELETE `/notifications/delete/{id}`
Delete notification.

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Notification deleted successfully"
}
```

---

## Admin Endpoints

### GET `/admin/stats`
Get system statistics (Admin only).

**Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "total_students": 150,
    "total_courses": 25,
    "total_bookings": 180,
    "pending_bookings": 8,
    "high_risk_students": 5
  }
}
```

---

### POST `/admin/create-announcement`
Create announcement (Admin only).

**Request Body:**
```json
{
  "title": "System Maintenance",
  "content": "System will be under maintenance on May 15",
  "recipients": ["all"]
}
```

**Response (201 Created):**
```json
{
  "status": "success",
  "message": "Announcement created successfully"
}
```

---

### POST `/admin/create-event`
Create calendar event (Admin only).

**Request Body:**
```json
{
  "event_name": "Graduation Ceremony",
  "event_date": "2024-06-15",
  "description": "Annual graduation ceremony"
}
```

**Response (201 Created):**
```json
{
  "status": "success",
  "message": "Event created successfully"
}
```

---

### POST `/admin/send-message`
Send message to users (Admin only).

**Request Body:**
```json
{
  "recipient_id": 1,
  "message": "Your attendance is below threshold"
}
```

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Message sent successfully"
}
```

---

### GET `/admin/analytics`
Get system analytics (Admin only).

**Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "attendance_statistics": {
      "average_attendance": 85,
      "at_risk_percentage": 12
    },
    "course_enrollment": {
      "most_enrolled": "CS101",
      "least_enrolled": "CS301"
    },
    "booking_trends": {
      "peak_hours": ["14:00", "15:00"],
      "most_booked_resource": "Lab A1"
    }
  }
}
```

---

### GET `/admin/system-stats`
Get system status and backup information (System Admin only).

**Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "total_users": 200,
    "total_admins": 5,
    "system_status": "online",
    "last_backup": "2024-05-07 02:00:00"
  }
}
```

---

### POST `/admin/backup-database`
Perform manual database backup (System Admin only).

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Database backup completed successfully"
}
```

---

### GET `/admin/get-users`
Get all system users with roles (System Admin only).

**Response (200 OK):**
```json
{
  "status": "success",
  "data": [
    {
      "user_id": 1,
      "username": "student001",
      "email": "student@example.com",
      "role": "student"
    }
  ],
  "total": 200
}
```

---

### PUT `/admin/update-user-role/{id}`
Update user role (System Admin only).

**Request Body:**
```json
{
  "role": "administrator"
}
```

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "User role updated successfully"
}
```

---

### DELETE `/admin/delete-user/{id}`
Delete user account (System Admin only).

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "User deleted successfully"
}
```

---

## AI Endpoints

### POST `/ai/optimize-schedule`
Get AI-optimized schedule.

**Request Body:**
```json
{
  "courses": [1, 2, 3]
}
```

**Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "optimized_schedule": [
      {
        "course_id": 1,
        "day_of_week": "Monday",
        "start_time": "09:00:00",
        "end_time": "10:30:00"
      }
    ],
    "conflicts_resolved": 2
  }
}
```

---

### POST `/ai/predict-attendance`
Get attendance risk prediction.

**Request Body:**
```json
{
  "student_id": 1
}
```

**Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "overall_risk": "medium",
    "courses": [
      {
        "course_id": 1,
        "course_name": "CS101",
        "risk_level": "high",
        "percentage": 70,
        "recommendation": "Attend more classes"
      }
    ]
  }
}
```

---

### POST `/ai/predict-performance`
Get performance prediction.

**Request Body:**
```json
{
  "student_id": 1
}
```

**Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "predicted_gpa": 3.2,
    "risk_factors": [
      "Low attendance in CS201",
      "Missing assignments in CS202"
    ]
  }
}
```

---

### POST `/ai/chatbot`
AI chatbot query endpoint.

**Request Body:**
```json
{
  "query": "What is my next class?"
}
```

**Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "response": "Your next class is Introduction to Programming on Monday at 09:00 AM in Room A101"
  }
}
```

---

## Error Handling

### Standard Error Response Format

All errors follow this format:

```json
{
  "status": "error",
  "message": "Description of the error",
  "code": "ERROR_CODE"
}
```

### HTTP Status Codes

| Code | Meaning | Example |
|------|---------|---------|
| 200 | OK | Request successful |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Invalid input data |
| 401 | Unauthorized | Authentication failed |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource not found |
| 409 | Conflict | Resource already exists |
| 500 | Internal Server Error | Server error |

### Common Error Codes

| Code | Message |
|------|---------|
| INVALID_CREDENTIALS | Username or password is incorrect |
| UNAUTHORIZED | User not authenticated |
| FORBIDDEN | Insufficient permissions for this action |
| NOT_FOUND | Resource not found |
| VALIDATION_ERROR | Input validation failed |
| DUPLICATE_ENTRY | Resource already exists |
| SERVER_ERROR | Internal server error |

---

## Authentication

### Bearer Token
```
Authorization: Bearer {token}
```

### Session-based
```
Cookie: PHPSESSID={session_id}
```

---

## Rate Limiting

API calls are limited to:
- **200 requests per hour** per IP
- **50 requests per minute** for login endpoint

Rate limit headers in response:
```
X-RateLimit-Limit: 200
X-RateLimit-Remaining: 195
X-RateLimit-Reset: 1620000000
```

---

## Pagination

For endpoints returning lists, use pagination:

**Query Parameters:**
- `page` (default: 1)
- `limit` (default: 20, max: 100)

**Response includes:**
```json
{
  "data": [...],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 150,
    "last_page": 8
  }
}
```

---

## Last Updated
May 7, 2024

**For additional support, contact:** support@campusease.edu
