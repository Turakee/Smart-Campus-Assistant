# Smart Campus AI - Comprehensive Testing Guide

## 1. Pre-Testing Setup

### 1.1 Environment Verification
```bash
# Verify PHP version (8.0+)
php -v

# Verify MySQL is running
mysql -u root -p -e "SELECT 1"

# Check all required files exist
ls -la config/Validator.php config/Utilities.php config/Security.php
```

### 1.2 Database Setup
```bash
# Import the database schema
mysql -u root < database/schema.sql

# Create default test users (if not already in schema)
mysql -u root -e "
USE smart_campus;
INSERT INTO users VALUES 
(1, 'sysadmin', 'System Administrator', 'sysadmin@campus.edu', '\$2y\$12\$...hash...', 'system_admin', '2024-01-01 00:00:00', '2024-01-01 00:00:00', 1),
(2, 'admin', 'Administrator', 'admin@campus.edu', '\$2y\$12\$...hash...', 'administrator', '2024-01-01 00:00:00', '2024-01-01 00:00:00', 1),
(3, 'student1', 'John Doe', 'student1@campus.edu', '\$2y\$12\$...hash...', 'student', '2024-01-01 00:00:00', '2024-01-01 00:00:00', 1);
"
```

### 1.3 Verify All Config Files Loaded
Create and run `verify-setup.php`:
```php
<?php
include 'config/config.php';
include 'config/database.php';
include 'config/Validator.php';
include 'config/Utilities.php';
include 'config/Security.php';

echo "✓ All config files loaded successfully\n";
echo "✓ Database connection: " . (isset($conn) ? "OK" : "FAILED") . "\n";
echo "✓ Validator class: " . (class_exists('Validator') ? "OK" : "FAILED") . "\n";
echo "✓ Utilities class: " . (class_exists('Utilities') ? "OK" : "FAILED") . "\n";
echo "✓ Security class: " . (class_exists('Security') ? "OK" : "FAILED") . "\n";
?>
```

---

## 2. API Endpoint Testing

### 2.1 Authentication Endpoints (api/auth/)

#### Test: User Login
```bash
curl -X POST http://localhost/smart_campus/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{
    "username": "student1",
    "password": "password123"
  }'

# Expected Response: 200 OK
# {
#   "success": true,
#   "message": "Login successful",
#   "data": {
#     "user_id": 3,
#     "username": "student1",
#     "role": "student",
#     "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
#   },
#   "timestamp": "2024-01-15 10:30:45"
# }
```

#### Test: Invalid Credentials
```bash
curl -X POST http://localhost/smart_campus/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{
    "username": "student1",
    "password": "wrongpassword"
  }'

# Expected Response: 401 Unauthorized
```

#### Test: User Registration
```bash
curl -X POST http://localhost/smart_campus/api/auth/register.php \
  -H "Content-Type: application/json" \
  -d '{
    "full_name": "Jane Smith",
    "username": "jane.smith",
    "email": "jane@campus.edu",
    "password": "SecurePass123!",
    "confirm_password": "SecurePass123!"
  }'

# Expected Response: 201 Created
```

### 2.2 Student Profile Endpoints (api/student/)

#### Test: Get Student Profile
```bash
curl -X GET http://localhost/smart_campus/api/student/profile.php \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# Expected Response: 200 OK
# {
#   "success": true,
#   "message": "Profile retrieved successfully",
#   "data": {
#     "user_id": 3,
#     "username": "student1",
#     "full_name": "John Doe",
#     "email": "student1@campus.edu",
#     "department": "Computer Science",
#     "academic_level": "Sophomore",
#     "created_at": "2024-01-01 00:00:00"
#   }
# }
```

#### Test: Update Student Profile (Invalid Name Length)
```bash
curl -X PUT http://localhost/smart_campus/api/student/profile.php \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "full_name": "J",
    "department": "Mathematics"
  }'

# Expected Response: 400 Bad Request
# Validation error about name length (min 3 chars)
```

#### Test: Update Student Profile (Valid Data)
```bash
curl -X PUT http://localhost/smart_campus/api/student/profile.php \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "full_name": "John Michael Doe",
    "department": "Computer Science",
    "academic_level": "Junior"
  }'

# Expected Response: 200 OK
# {
#   "success": true,
#   "message": "Profile updated successfully",
#   "data": {
#     "user_id": 3,
#     "full_name": "John Michael Doe",
#     "department": "Computer Science",
#     "academic_level": "Junior"
#   }
# }
```

### 2.3 Attendance Endpoints (api/attendance/)

#### Test: Mark Attendance (Admin Only)
```bash
curl -X POST http://localhost/smart_campus/api/attendance/mark.php \
  -H "Authorization: Bearer ADMIN_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "student_id": 3,
    "course_id": 1,
    "date": "2024-01-15",
    "status": "present"
  }'

# Expected Response: 201 Created
# {
#   "success": true,
#   "message": "Attendance marked successfully",
#   "data": {
#     "attendance_id": 45,
#     "student_id": 3,
#     "course_id": 1,
#     "date": "2024-01-15",
#     "status": "present",
#     "marked_by": 2
#   }
# }
```

#### Test: Mark Attendance (Student Attempt - Should Fail)
```bash
curl -X POST http://localhost/smart_campus/api/attendance/mark.php \
  -H "Authorization: Bearer STUDENT_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "student_id": 3,
    "course_id": 1,
    "date": "2024-01-15",
    "status": "present"
  }'

# Expected Response: 403 Forbidden
# {
#   "success": false,
#   "message": "Insufficient permissions for this action",
#   "code": 403
# }
```

#### Test: Mark Attendance with Invalid Status
```bash
curl -X POST http://localhost/smart_campus/api/attendance/mark.php \
  -H "Authorization: Bearer ADMIN_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "student_id": 3,
    "course_id": 1,
    "date": "2024-01-15",
    "status": "maybe"
  }'

# Expected Response: 400 Bad Request
# Validation error: status must be one of (present/absent/late/excused)
```

#### Test: Update Attendance Record
```bash
curl -X PUT http://localhost/smart_campus/api/attendance/42.php \
  -H "Authorization: Bearer ADMIN_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "late"
  }'

# Expected Response: 200 OK
```

#### Test: Delete Attendance Record
```bash
curl -X DELETE http://localhost/smart_campus/api/attendance/42.php \
  -H "Authorization: Bearer ADMIN_JWT_TOKEN"

# Expected Response: 200 OK
# {
#   "success": true,
#   "message": "Attendance deleted successfully",
#   "data": { "deleted_id": 42 }
# }
```

### 2.4 Resource Management Endpoints (api/resource/)

#### Test: Get All Resources (Paginated)
```bash
curl -X GET "http://localhost/smart_campus/api/resource/index.php?page=1&limit=10" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# Expected Response: 200 OK
# {
#   "success": true,
#   "message": "Resources retrieved successfully",
#   "data": {
#     "items": [
#       {
#         "resource_id": 1,
#         "name": "Lab A101",
#         "type": "lab",
#         "capacity": 30,
#         "location": "Building A",
#         "status": "available"
#       }
#     ],
#     "pagination": {
#       "current_page": 1,
#       "total_pages": 5,
#       "total_items": 48,
#       "items_per_page": 10
#     }
#   }
# }
```

#### Test: Filter Resources by Type
```bash
curl -X GET "http://localhost/smart_campus/api/resource/index.php?type=classroom&limit=5" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# Expected Response: 200 OK (only classrooms)
```

#### Test: Get Single Resource
```bash
curl -X GET http://localhost/smart_campus/api/resource/index.php \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{"action": "get", "id": 1}'

# Expected Response: 200 OK
# {
#   "success": true,
#   "message": "Resource retrieved successfully",
#   "data": {
#     "resource_id": 1,
#     "name": "Lab A101",
#     "type": "lab",
#     "capacity": 30,
#     "location": "Building A",
#     "status": "available",
#     "description": "Equipment lab with computers",
#     "bookings_count": 5
#   }
# }
```

#### Test: Create Resource (Admin Only)
```bash
curl -X POST http://localhost/smart_campus/api/resource/index.php \
  -H "Authorization: Bearer ADMIN_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Auditorium C",
    "type": "auditorium",
    "capacity": 250,
    "location": "Building C",
    "description": "Main auditorium for events"
  }'

# Expected Response: 201 Created
# {
#   "success": true,
#   "message": "Resource created successfully",
#   "data": {
#     "resource_id": 12,
#     "name": "Auditorium C",
#     "type": "auditorium",
#     "capacity": 250
#   }
# }
```

#### Test: Create Resource with Invalid Data
```bash
curl -X POST http://localhost/smart_campus/api/resource/index.php \
  -H "Authorization: Bearer ADMIN_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Rm",
    "type": "invalid_type",
    "capacity": 0,
    "location": ""
  }'

# Expected Response: 400 Bad Request
# Multiple validation errors: name too short, invalid type, invalid capacity, location required
```

#### Test: Update Resource
```bash
curl -X PUT http://localhost/smart_campus/api/resource/index.php \
  -H "Authorization: Bearer ADMIN_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "id": 12,
    "capacity": 300,
    "status": "maintenance"
  }'

# Expected Response: 200 OK
```

#### Test: Delete Resource (Admin Only)
```bash
curl -X DELETE http://localhost/smart_campus/api/resource/index.php \
  -H "Authorization: Bearer ADMIN_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"id": 12}'

# Expected Response: 200 OK
# {
#   "success": true,
#   "message": "Resource deleted successfully",
#   "data": { "deleted_id": 12 }
# }
```

### 2.5 Course Details Endpoints (api/course/)

#### Test: Get Course Details
```bash
curl -X GET "http://localhost/smart_campus/api/course/details.php?course_id=1" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# Expected Response: 200 OK
# {
#   "success": true,
#   "message": "Course details retrieved successfully",
#   "data": {
#     "course_id": 1,
#     "name": "Introduction to Programming",
#     "code": "CS101",
#     "credits": 3,
#     "semester": 1,
#     "enrolled_students": 45,
#     "max_capacity": 50,
#     "schedule": [
#       {
#         "day": "Monday",
#         "start_time": "09:00:00",
#         "end_time": "10:30:00",
#         "location": "Lab A101"
#       }
#     ]
#   }
# }
```

#### Test: Get Course Details (Course Not Found)
```bash
curl -X GET "http://localhost/smart_campus/api/course/details.php?course_id=99999" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# Expected Response: 404 Not Found
```

### 2.6 Booking Details Endpoints (api/booking/)

#### Test: Get Booking Details (Student Owns This Booking)
```bash
curl -X GET "http://localhost/smart_campus/api/booking/details.php?booking_id=5" \
  -H "Authorization: Bearer STUDENT_JWT_TOKEN"

# Expected Response: 200 OK
# {
#   "success": true,
#   "message": "Booking details retrieved successfully",
#   "data": {
#     "booking_id": 5,
#     "student_name": "John Doe",
#     "resource_name": "Lab A101",
#     "start_time": "2024-01-15 14:00:00",
#     "end_time": "2024-01-15 16:00:00",
#     "status": "approved",
#     "purpose": "Project work"
#   }
# }
```

#### Test: Get Booking Details (Student Does Not Own - Should Fail)
```bash
curl -X GET "http://localhost/smart_campus/api/booking/details.php?booking_id=10" \
  -H "Authorization: Bearer STUDENT_JWT_TOKEN"

# Expected Response: 403 Forbidden (if booking belongs to another student)
# OR 200 OK with data if student_id matches
```

#### Test: Get Booking Details (Admin Can Access Any)
```bash
curl -X GET "http://localhost/smart_campus/api/booking/details.php?booking_id=10" \
  -H "Authorization: Bearer ADMIN_JWT_TOKEN"

# Expected Response: 200 OK (admins can view any booking)
```

### 2.7 Notification Management (api/notifications/)

#### Test: Delete Single Notification
```bash
curl -X DELETE "http://localhost/smart_campus/api/notifications/manage.php?id=15" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# Expected Response: 200 OK
# {
#   "success": true,
#   "message": "Notification deleted successfully",
#   "data": { "deleted_id": 15 }
# }
```

#### Test: Delete Notification Not Owned
```bash
curl -X DELETE "http://localhost/smart_campus/api/notifications/manage.php?id=25" \
  -H "Authorization: Bearer STUDENT_JWT_TOKEN"

# Expected Response: 403 Forbidden
# (if notification belongs to another user)
```

#### Test: Mark All as Read
```bash
curl -X POST http://localhost/smart_campus/api/notifications/manage.php \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"action": "read-all"}'

# Expected Response: 200 OK
# {
#   "success": true,
#   "message": "All notifications marked as read",
#   "data": {
#     "updated_count": 3,
#     "total_unread": 0
#   }
# }
```

---

## 3. Security Testing

### 3.1 CSRF Protection Testing

#### Test: Missing CSRF Token
```bash
curl -X POST http://localhost/smart_campus/api/student/profile.php \
  -H "Content-Type: application/json" \
  -d '{"full_name": "Test User"}'

# Expected Response: 403 Forbidden
# "CSRF token validation failed"
```

#### Test: Invalid CSRF Token
```bash
curl -X POST http://localhost/smart_campus/api/student/profile.php \
  -H "Content-Type: application/json" \
  -H "X-CSRF-Token: invalid_token_123" \
  -d '{"full_name": "Test User"}'

# Expected Response: 403 Forbidden
```

### 3.2 Rate Limiting Testing

#### Test: Multiple Requests Within Rate Limit
```bash
# Run 5 requests in quick succession (limit is 10 per minute)
for i in {1..5}; do
  curl -X GET http://localhost/smart_campus/api/course/details.php?course_id=1 \
    -H "Authorization: Bearer YOUR_JWT_TOKEN"
done

# Expected: All 5 return 200 OK
```

#### Test: Exceeding Rate Limit
```bash
# Run 15 requests in quick succession (limit is 10 per minute)
for i in {1..15}; do
  curl -X GET http://localhost/smart_campus/api/course/details.php?course_id=1 \
    -H "Authorization: Bearer YOUR_JWT_TOKEN"
done

# Expected: First 10 return 200 OK, remaining return 429 Too Many Requests
```

### 3.3 SQL Injection Testing

#### Test: SQL Injection in Username Field
```bash
curl -X POST http://localhost/smart_campus/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin'\'' OR 1=1--",
    "password": "anything"
  }'

# Expected Response: 401 Unauthorized
# (Prepared statements prevent SQL injection)
```

#### Test: SQL Injection in Search Parameter
```bash
curl -X GET "http://localhost/smart_campus/api/resource/index.php?search=test'); DROP TABLE resources; --" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# Expected: Safe handling, no table deletion
```

### 3.4 Authorization Testing

#### Test: Student Accessing Admin-Only Endpoint
```bash
curl -X POST http://localhost/smart_campus/api/attendance/mark.php \
  -H "Authorization: Bearer STUDENT_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "student_id": 3,
    "course_id": 1,
    "date": "2024-01-15",
    "status": "present"
  }'

# Expected Response: 403 Forbidden
# "Insufficient permissions for this action"
```

#### Test: Invalid JWT Token
```bash
curl -X GET http://localhost/smart_campus/api/student/profile.php \
  -H "Authorization: Bearer invalid.jwt.token"

# Expected Response: 401 Unauthorized
# "Invalid or expired authentication token"
```

#### Test: No Authorization Header
```bash
curl -X GET http://localhost/smart_campus/api/student/profile.php

# Expected Response: 401 Unauthorized
# "Authentication required"
```

---

## 4. Data Validation Testing

### 4.1 Email Validation

#### Test: Invalid Email Format
```bash
curl -X POST http://localhost/smart_campus/api/auth/register.php \
  -H "Content-Type: application/json" \
  -d '{
    "full_name": "John Doe",
    "username": "johndoe",
    "email": "invalid-email",
    "password": "SecurePass123!",
    "confirm_password": "SecurePass123!"
  }'

# Expected Response: 400 Bad Request
# "email must be a valid email address"
```

#### Test: Valid Email Formats
```bash
# Test multiple valid formats
emails=("user@example.com" "john.doe+tag@domain.co.uk" "test.123@subdomain.example.org")

for email in "${emails[@]}"; do
  curl -X POST http://localhost/smart_campus/api/auth/register.php \
    -H "Content-Type: application/json" \
    -d "{...}" | grep -q "success"
done
```

### 4.2 Password Validation

#### Test: Weak Password
```bash
curl -X POST http://localhost/smart_campus/api/auth/register.php \
  -H "Content-Type: application/json" \
  -d '{
    "full_name": "John Doe",
    "username": "johndoe",
    "email": "john@example.com",
    "password": "weak",
    "confirm_password": "weak"
  }'

# Expected Response: 400 Bad Request
# "password must be at least 8 characters long"
```

#### Test: Password Mismatch
```bash
curl -X POST http://localhost/smart_campus/api/auth/register.php \
  -H "Content-Type: application/json" \
  -d '{
    "full_name": "John Doe",
    "username": "johndoe",
    "email": "john@example.com",
    "password": "SecurePass123!",
    "confirm_password": "DifferentPass123!"
  }'

# Expected Response: 400 Bad Request
# "passwords do not match"
```

### 4.3 Date/Time Validation

#### Test: Past Date
```bash
curl -X POST http://localhost/smart_campus/api/booking/submit.php \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "resource_id": 1,
    "start_time": "2020-01-15 10:00:00",
    "end_time": "2020-01-15 12:00:00",
    "purpose": "Meeting"
  }'

# Expected Response: 400 Bad Request
# "start_time must be in the future"
```

#### Test: End Time Before Start Time
```bash
curl -X POST http://localhost/smart_campus/api/booking/submit.php \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "resource_id": 1,
    "start_time": "2024-01-20 14:00:00",
    "end_time": "2024-01-20 10:00:00",
    "purpose": "Meeting"
  }'

# Expected Response: 400 Bad Request
# "end_time must be after start_time"
```

---

## 5. Integration Testing

### 5.1 Complete User Journey

#### Step 1: Register New User
```bash
curl -X POST http://localhost/smart_campus/api/auth/register.php \
  -H "Content-Type: application/json" \
  -d '{
    "full_name": "Integration Test User",
    "username": "inttest123",
    "email": "inttest@campus.edu",
    "password": "IntegrationTest123!",
    "confirm_password": "IntegrationTest123!"
  }'

# Save returned user_id: USER_ID=4
```

#### Step 2: Login with New User
```bash
curl -X POST http://localhost/smart_campus/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{
    "username": "inttest123",
    "password": "IntegrationTest123!"
  }'

# Save returned token: TOKEN="eyJ0eXAi..."
```

#### Step 3: View Profile
```bash
curl -X GET http://localhost/smart_campus/api/student/profile.php \
  -H "Authorization: Bearer $TOKEN"

# Verify: Returns user with username "inttest123"
```

#### Step 4: Update Profile
```bash
curl -X PUT http://localhost/smart_campus/api/student/profile.php \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "full_name": "Integration Test Updated",
    "department": "Computer Science"
  }'

# Verify: Confirms update with new name
```

#### Step 5: Get Available Resources
```bash
curl -X GET "http://localhost/smart_campus/api/resource/index.php?type=classroom" \
  -H "Authorization: Bearer $TOKEN"

# Save resource_id: RESOURCE_ID=2
```

#### Step 6: Submit Booking
```bash
curl -X POST http://localhost/smart_campus/api/booking/submit.php \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "resource_id": '$RESOURCE_ID',
    "start_time": "2024-01-20 14:00:00",
    "end_time": "2024-01-20 15:30:00",
    "purpose": "Integration test study session"
  }'

# Save booking_id: BOOKING_ID=5
```

#### Step 7: View Booking Details
```bash
curl -X GET "http://localhost/smart_campus/api/booking/details.php?booking_id=$BOOKING_ID" \
  -H "Authorization: Bearer $TOKEN"

# Verify: Returns booking details for our user
```

### 5.2 Admin Complete Workflow

#### Step 1: Admin Login
```bash
curl -X POST http://localhost/smart_campus/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "admin123"
  }'

# Save admin token: ADMIN_TOKEN="..."
```

#### Step 2: View All Resources
```bash
curl -X GET "http://localhost/smart_campus/api/resource/index.php?limit=20" \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

#### Step 3: Create New Resource
```bash
curl -X POST http://localhost/smart_campus/api/resource/index.php \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test Lab",
    "type": "lab",
    "capacity": 25,
    "location": "Building D"
  }'

# Save resource_id: NEW_RESOURCE_ID=15
```

#### Step 4: Mark Student Attendance
```bash
curl -X POST http://localhost/smart_campus/api/attendance/mark.php \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "student_id": 3,
    "course_id": 1,
    "date": "2024-01-15",
    "status": "present"
  }'

# Verify: Returns attendance_id
```

#### Step 5: View Admin Analytics
```bash
curl -X GET http://localhost/smart_campus/api/admin/stats.php \
  -H "Authorization: Bearer $ADMIN_TOKEN"

# Verify: Returns system statistics
```

---

## 6. Performance Testing

### 6.1 Load Testing with ApacheBench
```bash
# Test single endpoint with 100 requests, 10 concurrent
ab -n 100 -c 10 -H "Authorization: Bearer $TOKEN" \
   http://localhost/smart_campus/api/resource/index.php

# Expected: Response time should be < 100ms on average
```

### 6.2 Database Query Performance
```bash
# Check slow query log
mysql -u root -e "SHOW VARIABLES LIKE 'long_query_time';"

# Run complex query
EXPLAIN SELECT * FROM courses c 
JOIN schedules s ON c.course_id = s.course_id 
JOIN resources r ON s.resource_id = r.resource_id 
WHERE c.course_id = 1;

# Verify: Uses indexes appropriately
```

---

## 7. File Syntax Validation

### 7.1 Validate All PHP Files
```bash
# Check all API endpoints
find api/ -name "*.php" -exec php -l {} \;

# Expected: No syntax errors reported

# Specific test
php -l config/Validator.php
php -l config/Utilities.php
php -l config/Security.php
php -l api/student/profile.php
php -l api/attendance/mark.php
php -l api/resource/index.php
```

---

## 8. Test Results Summary Template

Create a file `TEST_RESULTS.md` with results:

```markdown
# Test Results - Smart Campus AI
**Date**: 2024-01-15
**Tester**: [Your Name]

## Authentication Tests
- [ ] Login with valid credentials: ✅ PASS
- [ ] Login with invalid password: ✅ PASS
- [ ] Registration with valid data: ✅ PASS
- [ ] Registration with duplicate username: ✅ PASS

## Student Profile Tests
- [ ] Get profile: ✅ PASS
- [ ] Update valid profile: ✅ PASS
- [ ] Update with invalid data: ✅ PASS

## Attendance Tests
- [ ] Mark attendance (admin): ✅ PASS
- [ ] Mark attendance (student - should fail): ✅ PASS
- [ ] Update attendance: ✅ PASS
- [ ] Delete attendance: ✅ PASS

## Resource Management Tests
- [ ] List resources: ✅ PASS
- [ ] Create resource (admin): ✅ PASS
- [ ] Update resource: ✅ PASS
- [ ] Delete resource: ✅ PASS

## Security Tests
- [ ] CSRF protection: ✅ PASS
- [ ] Rate limiting: ✅ PASS
- [ ] SQL injection prevention: ✅ PASS
- [ ] Authorization checks: ✅ PASS

## Integration Tests
- [ ] Complete user journey: ✅ PASS
- [ ] Admin workflow: ✅ PASS

## Overall Result: ✅ ALL TESTS PASSED
```

---

## 9. Troubleshooting

### Common Issues

#### "CORS error" in mobile app
- Verify CORS headers in config.php
- Check Origin is in allowed_origins list
- Test with curl: `curl -i http://localhost/smart_campus/api/auth/login.php`

#### "Database connection failed"
- Verify MySQL is running: `mysql -u root -p -e "SELECT 1"`
- Check database.php credentials match your setup
- Verify database exists: `mysql -u root -e "USE smart_campus;"`

#### "Class not found" errors
- Verify includes are in correct order in each endpoint
- Check file paths are correct (use absolute paths)
- Ensure config.php is included first

#### "Token expired" errors
- Check JWT_SECRET in config.php is consistent
- Verify token expiry time matches your requirements (default: 24 hours)

---

## 10. Continuous Testing

### Pre-Deployment Checklist
- [ ] All endpoints tested individually
- [ ] Full integration test completed
- [ ] Security tests all passed
- [ ] Rate limiting verified
- [ ] CSRF protection tested
- [ ] Database backups created
- [ ] Error logs reviewed
- [ ] Performance benchmarks acceptable

### Regular Monitoring
- Daily: Check error logs in `/logs/`
- Weekly: Run full test suite
- Monthly: Performance analysis
- Before deploy: Run all tests

