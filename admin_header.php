<?php
// admin_header.php - reusable admin header component ONLY
if (!isset($_SESSION)) {
    session_start();
}

// Security check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

$page_title = $page_title ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Panel - <?php echo htmlspecialchars($page_title); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="<?php echo CSS_PATH; ?>admin.css">
  <style>
    /* Ensure basic styles work even if CSS fails to load */
    body { margin: 0; padding: 0; font-family: Arial, sans-serif; }
    .admin-container { display: flex; min-height: 100vh; }
    .admin-main { flex: 1; }
  </style>
</head>
<body>
  <div class="admin-container">
    <!-- SIDEBAR WILL BE IN INDIVIDUAL PAGES -->
    <main class="admin-main">
      <header class="admin-header">
        <div class="header-left">
          <button class="menu-toggle" id="menuToggle">
            <i class='bx bx-menu'></i>
          </button>
          <h1><?php echo htmlspecialchars($page_title); ?></h1>
        </div>
        <div class="header-right">
          <div class="user-menu">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <div class="user-actions">
              <a href="profile.php"><i class='bx bxs-user'></i> Profile</a>
              <a href="logout.php"><i class='bx bxs-log-out'></i> Logout</a>
            </div>
          </div>
        </div>
      </header>
      <div class="admin-content">