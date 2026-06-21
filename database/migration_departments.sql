-- Migration: Fix departments table to match the unified 12-department list
-- Run this if you already have the old schema (8 depts: CSC, MTH, GST, PHY, CHM, BIO, ENG, BUS)

-- 1. Fix naming: 'Business Admin' -> 'Business Administration'
UPDATE departments SET department_name = 'Business Administration' WHERE department_code = 'BUS';

-- 2. Add missing departments (safe to re-run via INSERT IGNORE)
INSERT IGNORE INTO departments (department_name, department_code) VALUES
('Artificial Intelligence', 'AI'),
('Arts', 'Arts'),
('Computer Engineering', 'CompEng'),
('Cyber Security', 'CyberSec'),
('Data Science', 'DataSci'),
('Information Systems', 'InfoSys'),
('Information Technology', 'IT'),
('Science', 'Science'),
('Software Engineering', 'SWEng');

-- Note: old departments (Mathematics, Physics, Chemistry, Biology)
-- remain for backward compatibility with existing course references.
-- General Studies (GST) is also exposed in UI dropdowns for general/elective courses.
-- Note: 13 departments total in the standardized UI list.
