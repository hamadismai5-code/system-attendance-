<?php
// admin_header.php - reusable admin header component

// The including page (e.g., admin_dashboard.php) is responsible for session validation
// using validateSession() and isAdminUser() from session_check.php.
// This keeps the header component clean and focused on presentation.
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Panel - <?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard'; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'> -->
  <link rel="stylesheet" href="css/admin.css">
</head>
<body>
  <div class="admin-container">
    <aside class="admin-sidebar">
      <h2>Admin Panel</h2>
      <ul>
        <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
        <li class="<?php echo $currentPage === 'admin_dashboard.php' ? 'active' : ''; ?>">
          <a href="admin_dashboard.php"><i class='bx bxs-dashboard'></i><span> Dashboard</span></a>
        </li>
        <li class="<?php echo $currentPage === 'my_attendance.php' ? 'active' : ''; ?>">
          <a href="my_attendance.php"><i class='bx bxs-time'></i><span> My Attendance</span></a>
        </li>
        <li class="<?php echo $currentPage === 'users.php' ? 'active' : ''; ?>">
          <a href="users.php"><i class='bx bxs-user'></i><span> Users</span></a>
        </li>
        <li class="<?php echo $currentPage === 'departments.php' ? 'active' : ''; ?>">
          <a href="departments.php"><i class='bx bxs-building'></i><span> Departments</span></a>
        </li>
        <li class="<?php echo $currentPage === 'reports.php' ? 'active' : ''; ?>">
          <a href="reports.php"><i class='bx bxs-report'></i><span> Reports</span></a>
        </li>
        <li class="<?php echo $currentPage === 'analytics.php' ? 'active' : ''; ?>">
          <a href="analytics.php"><i class='bx bxs-analyse'></i><span> Analytics</span></a>
        </li>
        <li><a href="logout.php"><i class='bx bxs-log-out'></i><span> Logout</span></a></li>
      </ul>
    </aside>
    <main class="admin-content">
      <header class="admin-header">
        <div class="header-left">
          <button class="menu-toggle" id="menuToggle">
            <i class='bx bx-menu'></i>
          </button>
          <h1><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard'; ?></h1>
        </div>
        <div class="header-right">
          <div class="user-menu" id="userMenuToggle">
            <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <i class='bx bx-chevron-down'></i>
            <div class="user-actions" id="userActions">
              <a href="my_attendance.php"><i class='bx bxs-time'></i> My Attendance</a>
              <a href="logout.php"><i class='bx bxs-log-out'></i> Logout</a>
            </div>
          </div>
        </div>
      </header>
      <!-- Page-specific content will be rendered here -->
      <div class="page-content">