-- ============================================================
-- Toilet Cleanliness Monitoring App
-- Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS toilet_monitor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE toilet_monitor;

-- ============================================================
-- USERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    must_change_password TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TOILETS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS toilets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(200),
    description TEXT,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- USER_TOILETS (Many-to-Many Pivot)
-- ============================================================
CREATE TABLE IF NOT EXISTS user_toilets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    toilet_id INT NOT NULL,
    assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_toilet (user_id, toilet_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (toilet_id) REFERENCES toilets(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TOILET SESSIONS (Check-In / Check-Out Records)
-- ============================================================
CREATE TABLE IF NOT EXISTS toilet_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    toilet_id INT NOT NULL,
    checkin_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    checkin_comment TEXT,
    checkout_at DATETIME,
    checkout_comment TEXT,
    status ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (toilet_id) REFERENCES toilets(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SESSION PHOTOS
-- ============================================================
CREATE TABLE IF NOT EXISTS session_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    photo_type ENUM('checkin', 'checkout') NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    original_name VARCHAR(255),
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES toilet_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA: Default Admin Account
-- Username: admin
-- Password: Admin@123  (change after first login)
-- ============================================================
INSERT IGNORE INTO users (username, full_name, email, password_hash, role, must_change_password)
VALUES (
    'admin',
    'System Administrator',
    'admin@college.edu.my',
    '$2y$12$VYfa17ztoeX2rJ2VANpbVO65iN3fEDZWSLpOdEFXJp7IP5kT7FYcS', -- Admin@123
    'admin',
    0
);

-- ============================================================
-- INDEXES FOR PERFORMANCE
-- ============================================================
CREATE INDEX idx_sessions_toilet ON toilet_sessions(toilet_id);
CREATE INDEX idx_sessions_user ON toilet_sessions(user_id);
CREATE INDEX idx_sessions_status ON toilet_sessions(status);
CREATE INDEX idx_photos_session ON session_photos(session_id);
