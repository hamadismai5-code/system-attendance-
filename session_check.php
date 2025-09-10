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
    
    // Optional: IP validation (can be problematic with mobile networks)
    // if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
    //     session_destroy();
    //     header("Location: login.php?error=ip_changed");
    //     exit();
    // }
    
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

function isAdminUser() {
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        header("Location: attendance.php?error=access_denied");
        exit();
    }
    return true;
}

// Force HTTPS in production
function forceHTTPS() {
    if ($_SERVER['SERVER_NAME'] !== 'localhost' && 
        $_SERVER['SERVER_ADDR'] !== '127.0.0.1' &&
        (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off')) {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        exit();
    }
}

// Call this function on sensitive pages
// forceHTTPS();
?>