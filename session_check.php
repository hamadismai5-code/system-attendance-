<?php
// Enhanced session_check.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
}

// Include config for database functions - USE require_once
require_once 'config.php';

// CHECK IF FUNCTION EXISTS BEFORE DECLARING
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

if (!function_exists('isAdminUser')) {
    function isAdminUser() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
    }
}

// Force HTTPS in production
if (!function_exists('forceHTTPS')) {
    function forceHTTPS() {
        if ($_SERVER['SERVER_NAME'] !== 'localhost' && 
            $_SERVER['SERVER_ADDR'] !== '127.0.0.1' &&
            (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off')) {
            header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            exit();
        }
    }
}

// Call this function on sensitive pages
// forceHTTPS();
?>