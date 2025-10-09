<?php
// Set timezone for Philippines (Manila)
date_default_timezone_set('Asia/Manila');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'pointshift_pos');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site configuration
define('SITE_URL', 'http://localhost/point-shift_pos-system');
define('SITE_NAME', 'PointShift POS');

// Session configuration
session_start();

// Autoload classes
spl_autoload_register(function ($className) {
    $directories = [
        'classes/',
        'controllers/',
        'helpers/'
    ];
    
    foreach ($directories as $dir) {
        $file = __DIR__ . '/' . $dir . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Helper functions for backward compatibility
function isLoggedIn() {
    return User::isLoggedIn();
}

function isAdmin() {
    return User::isAdmin();
}

function requireLogin() {
    User::requireLogin();
}

function requireAdmin() {
    User::requireAdmin();
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function formatCurrency($amount) {
    return Layout::formatCurrency($amount);
}

// Create MySQLi connection for backward compatibility
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");
?>
