<?php
$host = 'localhost';
$dbuser = 'root';
$dbpass = '';
$dbname = 'attendance_db';

// Jaribu kuunganisha kwenye database
try {
    $conn = new mysqli($host, $dbuser, $dbpass, $dbname);
    
    // Hakikisha muunganisho umefanikiwa
    if ($conn->connect_error) {
        throw new Exception( "Connection failed:". $conn->connect_error);
    }
    
    // Weka charset kuwa utf8
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    // Kwa mazingira ya uzalishaji, usionyeshe maelezo ya hitilafu kwa mtumiaji
    error_log($e->getMessage());
    die("System error. Please try again later.");
}

function isAdmin($username, $conn) {
    $stmt = $conn->prepare("SELECT is_admin FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($is_admin);
    $stmt->fetch();
    $stmt->close();
    return $is_admin;
}
?>
