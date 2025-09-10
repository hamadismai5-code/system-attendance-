<?php
// admin_header.php - reusable admin header component
if (!isset($_SESSION)) {
    session_start();
}

// Security check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Panel - <?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="css/admin.css">
  <style>
    /* Additional styles can be added here */
  </style>
</head>
<body>
  <div class="admin-container">
    <aside class="admin-sidebar">
      <h2>Admin Panel</h2>
      <ul>
        <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'active' : ''; ?>">
          <a href="admin_dashboard.php"><i class='bx bxs-dashboard'></i><span> Dashboard</span></a>
        </li>
        <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'my_attendance.php' ? 'active' : ''; ?>">
          <a href="my_attendance.php"><i class='bx bxs-time'></i><span> My Attendance</span></a>
        </li>
        <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>">
          <a href="users.php"><i class='bx bxs-user'></i><span> Users</span></a>
        </li>
        <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'departments.php' ? 'active' : ''; ?>">
          <a href="departments.php"><i class='bx bxs-building'></i><span> Departments</span></a>
        </li>
        <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>">
          <a href="reports.php"><i class='bx bxs-report'></i><span> Reports</span></a>
        </li>
        <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'analytics.php' ? 'active' : ''; ?>">
          <a href="analytics.php"><i class='bx bxs-analyse'></i><span> Analytics</span></a>
        </li>
        <li><a href="logout.php"><i class='bx bxs-log-out'></i><span> Logout</span></a></li>
      </ul>
    </aside>
    <main class="admin-main">
      <header class="admin-header">
        <div class="header-left">
          <button class="menu-toggle" id="menuToggle">
            <i class='bx bx-menu'></i>
          </button>
          <h1><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h1>
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