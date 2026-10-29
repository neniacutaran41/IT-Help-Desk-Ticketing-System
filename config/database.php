<?php
/**
 * Database Configuration & PDO Connection
 * DeskHero IT Help Desk System
 */

// Allow overriding credentials via database.local.php (recommended for production/Hostinger to avoid committing secrets)
if (file_exists(__DIR__ . '/database.local.php')) {
    require_once __DIR__ . '/database.local.php';
}

// Database credentials fallback (default for local XAMPP)
if (!defined('DB_HOST'))    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_NAME'))    define('DB_NAME', getenv('DB_NAME') ?: 'helpdesk_db');
if (!defined('DB_USER'))    define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS'))    define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a shared PDO instance
 *
 * @return PDO
 * @throws PDOException
 */
function get_db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Friendly error message if running through web
            if (php_sapi_name() !== 'cli') {
                http_response_code(500);
                echo '<div style="font-family: sans-serif; max-width: 600px; margin: 50px auto; padding: 25px; border: 1px solid #f5c2c7; background: #f8d7da; border-radius: 8px; color: #842029;">';
                echo '<h3 style="margin-top:0;">Database Connection Error</h3>';
                echo '<p>Could not connect to database <code>' . htmlspecialchars(DB_NAME) . '</code> on <code>' . htmlspecialchars(DB_HOST) . '</code>.</p>';
                echo '<p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '<p><a href="setup_check.php" style="color: #0d6efd; font-weight: bold;">Run System Health & Setup Diagnostic Tool</a></p>';
                echo '</div>';
                exit;
            }
            throw $e;
        }
    }

    return $pdo;
}
