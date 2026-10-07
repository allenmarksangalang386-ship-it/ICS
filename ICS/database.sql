-- 1. Lumikha ng Database
CREATE DATABASE IF NOT EXISTS org_attendance 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE org_attendance;

-- ==========================================
-- 2. TABLE: Users (Para sa Authentication & User Management)
-- ==========================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('Admin', 'Staff') DEFAULT 'Staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 3. TABLE: Members (Information Management & QR Code Base)
-- ==========================================
CREATE TABLE IF NOT EXISTS members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_code VARCHAR(50) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    department VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 4. TABLE: Attendance (Transaction Records)
-- ==========================================
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('Present', 'Late', 'Absent') DEFAULT 'Present',
    time_in TIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    UNIQUE KEY unique_daily_attendance (member_id, attendance_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 5. TABLE: Activity Logs (Audit Trail Implementation)
-- ==========================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 6. DEFAULT DATA / SEEDER
-- ==========================================
-- Default Admin User Account
-- Username: admin
-- Password: admin123
INSERT INTO users (username, password, full_name, role) 
VALUES ('admin', '$2y$10$20AEI.Auu3osnL5oC0SAoO21Sj5nxZSc5i8h.1Wpe0EIwbnogr.OO', 'System Administrator', 'Admin')
ON DUPLICATE KEY UPDATE id=id;

-- Sample Members (Manga-generate agad ng QR sa system)
INSERT INTO members (member_code, first_name, last_name, department) VALUES
('STU-2026-001', 'Juan', 'Dela Cruz', 'BS Information Technology'),
('STU-2026-002', 'Maria', 'Clara', 'BS Computer Science')
ON DUPLICATE KEY UPDATE id=id;