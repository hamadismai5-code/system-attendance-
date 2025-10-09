<?php
include 'session_check.php';
include 'config.php';

// Validate the user's session using the centralized function
validateSession();

// Redirect user based on their role
if (isAdminUser()) {
    // If the user is an admin, redirect to the admin dashboard
    header("Location: admin_dashboard.php");
} else {
    // If the user is a regular user, redirect to the main attendance page
    header("Location: attendance.php");
}
exit();
?>