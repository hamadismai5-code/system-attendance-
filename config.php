<?php
// Enhanced config.php with better security
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security headers
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'attendance_db');

// Define paths for CSS and JS
define('CSS_PATH', 'css/');
define('JS_PATH', 'js/');

// Define the base URL of the application
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . '/attendance_project');

// Error reporting - only show errors in development
if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_ADDR'] === '127.0.0.1') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

// Set default timezone
date_default_timezone_set('Africa/Dar_es_Salaam');

// Database connection with improved error handling
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . htmlspecialchars($conn->connect_error));
    }
    
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Database Error: " . $e->getMessage());
    
    // User-friendly error message
    if (ini_get('display_errors')) {
        die("Database connection error: " . $e->getMessage());
    } else {
        die("System temporarily unavailable. Please try again later.");
    }
}

// CHECK IF FUNCTION EXISTS BEFORE DECLARING
if (!function_exists('generateCsrfToken')) {
    function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('validateCsrfToken')) {
    function validateCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('sanitizeInput')) {
    function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map('sanitizeInput', $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('validateEmail')) {
    function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}

if (!function_exists('validateDate')) {
    function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
}

// Password strength validation
if (!function_exists('validatePassword')) {
    function validatePassword($password) {
        // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password);
    }
}

// Session validation function
if (!function_exists('validateSession')) {
    function validateSession() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header("Location: login.php?error=not_logged_in");
            exit();
        }
        
        // Initialize session security on first load
        if (!isset($_SESSION['user_agent'])) {
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['login_time'] = time();
        }
        
        // Check session hijacking
        if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            session_destroy();
            header("Location: login.php?error=session_invalid");
            exit();
        }
        
        // Check session timeout (30 minutes)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            session_destroy();
            header("Location: login.php?timeout=1");
            exit();
        }
        
        // Regenerate session ID periodically to prevent fixation
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = time();
    }
}

// Admin check function
if (!function_exists('isAdminUser')) {
    function isAdminUser() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
    }
}
?>