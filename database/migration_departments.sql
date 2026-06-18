-- Migration: Fix departments table to match the unified 12-department list
-- Run this if you already seeded the DB with the old schema.sql (8 depts)

-- 1. Fix naming: 'Business Admin' -> 'Business Administration'
UPDATE departments SET department_name = 'Business Administration' WHERE department_code = 'BUS';

-- 2. Add missing departments (safe to re-run)
INSERT IGNORE INTO departments (department_name, department_code) VALUES
('Artificial Intelligence', 'AI'),
('Arts', 'ART'),
('Computer Engineering', 'CE'),
('Cyber Security', 'CYS'),
('Data Science', 'DS'),
('Information Systems', 'IS'),
('Information Technology', 'IT'),
('Science', 'SCI'),
('Software Engineering', 'SWE');
