<?php
/**
 * Registration API Endpoint
 * Section 5.2.3: Users & Students Tables
 */

include_once '../../config/config.php';
include_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([], false, 'Method not allowed', 405);
}

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"), true);

// Default role to student for mobile app
$role = !empty($data['role']) ? $data['role'] : 'student';

// Validation
if (empty($data['username']) || empty($data['email']) || empty($data['password']) || empty($data['full_name'])) {
    jsonResponse([], false, 'All fields are required', 400);
}

$allowedRoles = ['student', 'administrator'];
if (!in_array($role, $allowedRoles)) {
    jsonResponse([], false, 'Invalid role selected', 400);
}

try {
    $db->beginTransaction();
    
    // Check Existing User
    $check = $db->prepare("SELECT user_id FROM users WHERE username = :username OR email = :email");
    $check->execute([':username' => $data['username'], ':email' => $data['email']]);
    
    if ($check->rowCount() > 0) {
        throw new Exception('Username or email already exists');
    }
    
    // Hash Password (Security Section 4.8.2)
    $password_hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    
    // Insert User
    $query = "INSERT INTO users (username, email, password_hash, role) 
              VALUES (:username, :email, :password_hash, :role)";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':username' => sanitizeInput($data['username']),
        ':email' => sanitizeInput($data['email']),
        ':password_hash' => $password_hash,
        ':role' => $role
    ]);
    
    $user_id = $db->lastInsertId();
    
    // Insert Student Profile if role is student
    $fullName = !empty($data['full_name']) ? $data['full_name'] : $data['username'];
    $department = !empty($data['department']) ? $data['department'] : 'General';
    $level = !empty($data['level']) ? (int)$data['level'] : 1;
    
    if ($role === 'student') {
        $studentQuery = "INSERT INTO students (user_id, full_name, department, level, enrollment_year) 
                         VALUES (:user_id, :name, :dept, :level, :year)";
        $stmt = $db->prepare($studentQuery);
        $stmt->execute([
            ':user_id' => $user_id,
            ':name' => sanitizeInput($fullName),
            ':dept' => sanitizeInput($department),
            ':level' => $level,
            ':year' => date('Y')
        ]);
    } elseif ($role === 'administrator') {
        $adminQuery = "INSERT INTO administrators (user_id, full_name) 
                       VALUES (:user_id, :name)";
        $stmt = $db->prepare($adminQuery);
        $stmt->execute([
            ':user_id' => $user_id,
            ':name' => sanitizeInput($fullName)
        ]);
    }
    
    $db->commit();
    
    // Set session for auto-login after registration
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $data['username'];
    $_SESSION['role'] = $role;
    $_SESSION['name'] = $fullName;
    
    // Return data in format the mobile app expects
    jsonResponse([
        'token' => (string)$user_id,
        'user' => [
            'user_id' => $user_id,
            'username' => $data['username'],
            'email' => $data['email'],
            'role' => $role,
            'name' => $fullName,
            'department' => $department,
            'level' => $level,
        ]
    ], true, 'Registration successful', 201);
    
} catch(Exception $e) {
    $db->rollBack();
    jsonResponse([], false, $e->getMessage(), 400);
}
?>