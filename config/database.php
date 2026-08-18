<?php
// ============================================================
// Database Configuration
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'toilet_monitor');
define('DB_USER', 'root');       // Change to your MySQL username
define('DB_PASS', '1234');           // Change to your MySQL password
define('DB_CHARSET', 'utf8mb4');
define('DB_PORT', '3307');

/**
 * Get PDO database connection (singleton)
 */
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET . ";port=" . DB_PORT;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Show friendly error; do not expose credentials
            die('<div style="font-family:sans-serif;padding:2rem;background:#1a1a2e;color:#ef4444;text-align:center;">
                <h2>⚠️ Database Connection Failed</h2>
                <p>Please check your database settings in <code>config/database.php</code>.</p>
                <p><small>' . htmlspecialchars($e->getMessage()) . '</small></p>
            </div>');
        }
    }
    return $pdo;
}
