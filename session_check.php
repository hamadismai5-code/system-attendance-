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