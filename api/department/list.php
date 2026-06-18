<?php
header('Content-Type: application/json');
require_once '../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse([], false, 'Invalid request method', 405);
}

$departments = [
    ['department_id' => 1, 'department_name' => 'Artificial Intelligence', 'department_code' => 'AI'],
    ['department_id' => 2, 'department_name' => 'Arts', 'department_code' => 'ART'],
    ['department_id' => 3, 'department_name' => 'Business Administration', 'department_code' => 'BUS'],
    ['department_id' => 4, 'department_name' => 'Computer Engineering', 'department_code' => 'CE'],
    ['department_id' => 5, 'department_name' => 'Computer Science', 'department_code' => 'CS'],
    ['department_id' => 6, 'department_name' => 'Cyber Security', 'department_code' => 'CYS'],
    ['department_id' => 7, 'department_name' => 'Data Science', 'department_code' => 'DS'],
    ['department_id' => 8, 'department_name' => 'Engineering', 'department_code' => 'ENG'],
    ['department_id' => 9, 'department_name' => 'Information Systems', 'department_code' => 'IS'],
    ['department_id' => 10, 'department_name' => 'Information Technology', 'department_code' => 'IT'],
    ['department_id' => 11, 'department_name' => 'Science', 'department_code' => 'SCI'],
    ['department_id' => 12, 'department_name' => 'Software Engineering', 'department_code' => 'SWE'],
];

jsonResponse($departments, true, 'Departments retrieved');
