<?php
// ============================================================
// Application Configuration
// ============================================================

// App identity
define('APP_NAME', 'ToiletTrack');
define('APP_SUBTITLE', 'Cleanliness Monitoring System');
define('COLLEGE_NAME', 'College Cleanliness Monitoring');

// Base URL — update this if your subfolder or port is different
define('BASE_URL', 'http://localhost:8080/toilet');

// Upload directory (relative to project root)
define('UPLOAD_DIR', __DIR__ . '/../uploads/sessions/');
define('UPLOAD_URL', BASE_URL . '/uploads/sessions/');

// Allowed image types for uploads
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10 MB per photo

// Session name
define('SESSION_NAME', 'toilet_monitor_sess');

// Role constants
define('ROLE_ADMIN', 'admin');
define('ROLE_USER',  'user');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}
