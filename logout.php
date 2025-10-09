<?php
session_start();

// 1. Unset all of the session variables.
$_SESSION = array();

// 2. Delete the session cookie from the browser.
// This will destroy the session, not just the session data.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Finally, destroy the session on the server.
session_destroy();

// 4. Redirect to the login page with a success message.
header("Location: login.php?logout=success");
exit();
?>
