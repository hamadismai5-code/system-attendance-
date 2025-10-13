<?php
session_start();
include 'config.php';
include 'session_check.php';

// Check login and admin status
validateSession();
if (!isAdminUser()) {
    header("Location: attendance.php");
    exit();
}

// ==================== SECURE Data Fetching ====================
// General stats - USING PREPARED STATEMENTS
$stats_query = "SELECT 
    (SELECT COUNT(DISTINCT name) FROM attendance) as total_users,
    (SELECT COUNT(*) FROM attendance) as total_records,
    (SELECT COUNT(*) FROM attendance WHERE date = CURDATE()) as today_records,
    (SELECT COUNT(DISTINCT name) FROM attendance WHERE date = CURDATE()) as present_today";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Department stats - SECURE
$dept_stats = [];
$dept_stmt = $conn->prepare("SELECT d.name as department, COUNT(a.id) as count 
                    FROM departments d 
                    LEFT JOIN attendance a ON d.id = a.department AND a.date = CURDATE()
                    GROUP BY d.id");
$dept_stmt->execute();
$dept_result = $dept_stmt->get_result();
$dept_stats = $dept_result->fetch_all(MYSQLI_ASSOC);
$dept_stmt->close();

// Recent attendance - SECURE
$recent_attendance = [];
$recent_stmt = $conn->prepare("SELECT a.name, d.name as department, a.date, a.time_in, a.time_out 
                           FROM attendance a
                           JOIN departments d ON a.department = d.id
                           ORDER BY a.date DESC, a.time_in DESC LIMIT 10");
$recent_stmt->execute();
$recent_result = $recent_stmt->get_result();
$recent_attendance = $recent_result->fetch_all(MYSQLI_ASSOC);
$recent_stmt->close();

// Weekly trend - SECURE
$weekly_trend = [];
$weekly_stmt = $conn->prepare("SELECT DATE(date) as day, COUNT(DISTINCT name) as users 
                      FROM attendance 
                      WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                      GROUP BY DATE(date)
                      ORDER BY day ASC");
$weekly_stmt->execute();
$weekly_result = $weekly_stmt->get_result();
$weekly_trend = $weekly_result->fetch_all(MYSQLI_ASSOC);
$weekly_stmt->close();

// Top performers - SECURE
$top_performers = [];
$top_stmt = $conn->prepare("SELECT name, COUNT(*) as attendance_count 
                         FROM attendance 
                         WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                         GROUP BY name 
                         ORDER BY attendance_count DESC 
                         LIMIT 5");
$top_stmt->execute();
$top_result = $top_stmt->get_result();
$top_performers = $top_result->fetch_all(MYSQLI_ASSOC);
$top_stmt->close();

// Notifications - SECURE
$notifications = [];
$notif_stmt = $conn->prepare("SELECT 'Users without department' as message, COUNT(*) as count 
                       FROM attendance 
                       WHERE department = 0 OR department IS NULL
                       UNION ALL
                       SELECT 'Pending attendance' as message, COUNT(*) as count 
                       FROM attendance 
                       WHERE date = CURDATE() AND time_out IS NULL");
$notif_stmt->execute();
$notif_result = $notif_stmt->get_result();
$notifications = $notif_result->fetch_all(MYSQLI_ASSOC);
$notif_stmt->close();

// Free result sets
$stats_result->free();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
  <div class="admin-container">
    <!-- SIDEBAR IN THIS PAGE -->
    <aside class="admin-sidebar">
      <h2>Admin Panel</h2>
      <ul>
        <li class="active">
          <a href="admin_dashboard.php"><i class='bx bxs-dashboard'></i><span> Dashboard</span></a>
        </li>
        <li>
          <a href="my_attendance.php"><i class='bx bxs-time'></i><span> My Attendance</span></a>
        </li>
        <li>
          <a href="users.php"><i class='bx bxs-user'></i><span> Users</span></a>
        </li>
        <li>
          <a href="departments.php"><i class='bx bxs-building'></i><span> Departments</span></a>
        </li>
        <li>
          <a href="reports.php"><i class='bx bxs-report'></i><span> Reports</span></a>
        </li>
        <li>
          <a href="analytics.php"><i class='bx bxs-analyse'></i><span> Analytics</span></a>
        </li>
        <li><a href="logout.php"><i class='bx bxs-log-out'></i><span> Logout</span></a></li>
      </ul>
    </aside>

    <main class="admin-main">
      <div class="admin-content">
        <header class="admin-header">
          <div class="header-left">
            <button class="menu-toggle" id="menuToggle">
              <i class='bx bx-menu'></i>
            </button>
            <h1>Dashboard Overview</h1>
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
        
        <!-- Stats Cards -->
        <section class="admin-stats">
          <div class="stat-card">
            <div class="stat-icon blue"><i class='bx bxs-user'></i></div>
            <div class="stat-info">
              <h3>Total Users</h3>
              <p><?php echo htmlspecialchars($stats['total_users']); ?></p>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon green"><i class='bx bxs-time-five'></i></div>
            <div class="stat-info">
              <h3>Total Records</h3>
              <p><?php echo htmlspecialchars($stats['total_records']); ?></p>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon amber"><i class='bx bxs-calendar-check'></i></div>
            <div class="stat-info">
              <h3>Present Today</h3>
              <p><?php echo htmlspecialchars($stats['present_today']); ?></p>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon red"><i class='bx bxs-chart'></i></div>
            <div class="stat-info">
              <h3>Today's Records</h3>
              <p><?php echo htmlspecialchars($stats['today_records']); ?></p>
            </div>
          </div>
        </section>
        
        <!-- Charts Section -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
          <section class="dashboard-section">
            <div class="section-header">
              <h3>Weekly Attendance Trend</h3>
              <a href="analytics.php">View Details <i class='bx bx-right-arrow-alt'></i></a>
            </div>
            <div class="chart-container">
              <canvas id="weeklyTrendChart"></canvas>
            </div>
          </section>
          
          <section class="dashboard-section">
            <div class="section-header">
              <h3>Department Distribution</h3>
              <a href="departments.php">Manage <i class='bx bx-right-arrow-alt'></i></a>
            </div>
            <div class="chart-container">
              <canvas id="departmentChart"></canvas>
            </div>
          </section>
        </div>
        
        <!-- Recent Attendance & Top Performers -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
          <section class="dashboard-section">
            <div class="section-header">
              <h3>Recent Attendance</h3>
              <a href="attendance.php">View All <i class='bx bx-right-arrow-alt'></i></a>
            </div>
            <div class="table-container">
              <table>
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Date</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recent_attendance as $record): ?>
                  <tr>
                    <td><?= htmlspecialchars($record['name']) ?></td>
                    <td><?= htmlspecialchars($record['department']) ?></td>
                    <td><?= htmlspecialchars($record['date']) ?></td>
                    <td><?= htmlspecialchars($record['time_in']) ?></td>
                    <td><?= htmlspecialchars($record['time_out'] ?: '--') ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </section>
          
          <section class="dashboard-section">
            <div class="section-header">
              <h3>Top Performers</h3>
              <a href="reports.php">View Report <i class='bx bx-right-arrow-alt'></i></a>
            </div>
            <div class="performers-list">
              <?php foreach ($top_performers as $performer): ?>
              <div class="performer-item">
                <span class="performer-name"><?= htmlspecialchars($performer['name']) ?></span>
                <span class="performer-count"><?= htmlspecialchars($performer['attendance_count']) ?> days</span>
              </div>
              <?php endforeach; ?>
            </div>
          </section>
        </div>
        
        <!-- Notifications -->
        <section class="dashboard-section">
          <div class="section-header">
            <h3>System Notifications</h3>
          </div>
          <div class="notifications">
            <?php foreach ($notifications as $notification): ?>
            <div class="notification-card">
              <i class='bx <?= $notification['count'] > 0 ? 'bx-error' : 'bx-info-circle' ?> notification-icon <?= $notification['count'] > 0 ? 'warning' : 'info' ?>'></i>
              <div class="notification-content">
                <h4><?= htmlspecialchars($notification['message']) ?></h4>
                <p><?= htmlspecialchars($notification['count']) ?> found</p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>  
        </section>
      </div>
    </main>
  </div>

<?php include 'admin_footer.php'; ?>
</body>
</html>