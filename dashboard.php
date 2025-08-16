<?php
session_start();

// Hakikisha mtumiaji ameingia
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Kuweka muda wa kikao (30 dakika)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    // Kikao kimeisha
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit();
}
$_SESSION['last_activity'] = time();

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>