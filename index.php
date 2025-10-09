<?php
// index.php - Main entry point for the application

// Start the session to check login status.
// Using session_status() check to avoid errors if it's already started.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is already logged in by looking for a session variable.
if (isset($_SESSION['user_id'])) {
    // If logged in, redirect to the main dashboard router.
    // dashboard.php will handle routing to the correct user/admin page.
    header('Location: dashboard.php');
} else {
    // If not logged in, redirect to the login page.
    header('Location: login.php');
}
exit;
