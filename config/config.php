<?php
/**
 * MedCare System - General Configuration
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

// Base URL of the system, worked out automatically from where the folder sits
// inside htdocs (so renaming the folder or using another port still works).
// To force a value, replace this block with e.g.
//   define('BASE_URL', 'http://localhost/Fanugon_System');
if (!defined('BASE_URL')) {
    $base_url = 'http://localhost/Fanugon_System'; // fallback (command line, unusual setups)
    $doc_root = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $app_root = realpath(dirname(__DIR__));
    if (!empty($_SERVER['HTTP_HOST']) && $doc_root && $app_root) {
        $doc_root = rtrim(str_replace('\\', '/', $doc_root), '/');
        $app_root = rtrim(str_replace('\\', '/', $app_root), '/');
        // Windows paths are case-insensitive (C:\xampp vs c:\xampp)
        if (strncasecmp($app_root, $doc_root, strlen($doc_root)) === 0) {
            $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            $base_url = ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']
                . substr($app_root, strlen($doc_root));
        }
    }
    define('BASE_URL', rtrim($base_url, '/'));
    unset($base_url, $doc_root, $app_root, $https);
}

// Define paths
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('PAGES_PATH', ROOT_PATH . '/pages');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'medcare_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Security settings
define('PASSWORD_MIN_LENGTH', 6);
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds

// Pagination
define('RECORDS_PER_PAGE', 10);

// Inventory settings
define('DEFAULT_MIN_STOCK_LEVEL', 10);
define('EXPIRY_WARNING_DAYS', 30);

// Timezone
date_default_timezone_set('Asia/Manila');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once CONFIG_PATH . '/database.php';
?>
