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

// ==================== Optimized Data Fetching ====================
// General stats
$stats_query = "SELECT 
    (SELECT COUNT(DISTINCT name) FROM attendance) as total_users,
    (SELECT COUNT(*) FROM attendance) as total_records,
    (SELECT COUNT(*) FROM attendance WHERE date = CURDATE()) as today_records,
    (SELECT COUNT(DISTINCT name) FROM attendance WHERE date = CURDATE()) as present_today";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Department stats
$dept_stats_query = "SELECT d.name as department, COUNT(a.id) as count 
                    FROM departments d 
                    LEFT JOIN attendance a ON d.id = a.department AND a.date = CURDATE()
                    GROUP BY d.id";
$dept_stats_result = $conn->query($dept_stats_query);
$dept_stats = $dept_stats_result->fetch_all(MYSQLI_ASSOC);

// Recent attendance
$recent_attendance_query = "SELECT a.name, d.name as department, a.date, a.time_in, a.time_out 
                           FROM attendance a
                           JOIN departments d ON a.department = d.id
                           ORDER BY a.date DESC, a.time_in DESC LIMIT 10";
$recent_attendance_result = $conn->query($recent_attendance_query);
$recent_attendance = $recent_attendance_result->fetch_all(MYSQLI_ASSOC);

// Weekly trend
$weekly_trend_query = "SELECT DATE(date) as day, COUNT(DISTINCT name) as users 
                      FROM attendance 
                      WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                      GROUP BY DATE(date)
                      ORDER BY day ASC";
$weekly_trend_result = $conn->query($weekly_trend_query);
$weekly_trend = $weekly_trend_result->fetch_all(MYSQLI_ASSOC);

// Top performers
$top_performers_query = "SELECT name, COUNT(*) as attendance_count 
                         FROM attendance 
                         WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                         GROUP BY name 
                         ORDER BY attendance_count DESC 
                         LIMIT 5";
$top_performers_result = $conn->query($top_performers_query);
$top_performers = $top_performers_result->fetch_all(MYSQLI_ASSOC);

// Notifications
$notifications_query = "SELECT 'Users without department' as message, COUNT(*) as count 
                       FROM attendance 
                       WHERE department = 0 OR department IS NULL
                       UNION ALL
                       SELECT 'Pending attendance' as message, COUNT(*) as count 
                       FROM attendance 
                       WHERE date = CURDATE() AND time_out IS NULL";
$notifications_result = $conn->query($notifications_query);
$notifications = $notifications_result->fetch_all(MYSQLI_ASSOC);

// Free result sets
$stats_result->free();
$dept_stats_result->free();
$recent_attendance_result->free();
$weekly_trend_result->free();
$top_performers_result->free();
$notifications_result->free();

// Include admin header if any
include 'admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>

    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- Your admin CSS -->
     <style>
    :root {
      --primary: #3b82f6;
      --primary-dark: #2563eb;
      --secondary: #10b981;
      --accent: #f59e0b;
      --danger: #ef4444;
      --dark: #1f2937;
      --gray: #6b7280;
      --light: #f3f4f6;
      --white: #ffffff;
      --shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
      --radius: 12px;
      --transition: all 0.3s ease;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background-color: var(--light);
      color: var(--dark);
      line-height: 1.5;
    }
    
    .admin-container {
      display: flex;
      min-height: 100vh;
    }
    
    /* Sidebar */
    .admin-sidebar {
      width: 240px;
      background: var(--dark);
      color: var(--white);
      position: fixed;
      height: 100vh;
      padding-top: 20px;
      transition: var(--transition);
      z-index: 100;
      overflow-y: auto;
    }
    
    .admin-sidebar h2 {
      text-align: center;
      margin-bottom: 30px;
      font-weight: 700;
      font-size: 1.5rem;
    }
    
    .admin-sidebar ul {
      list-style: none;
      padding: 0;
    }
    
    .admin-sidebar ul li {
      margin: 5px 15px;
    }
    
    .admin-sidebar ul li a {
      display: flex;
      align-items: center;
      padding: 12px 15px;
      color: #d1d5db;
      text-decoration: none;
      border-radius: 8px;
      transition: var(--transition);
    }
    
    .admin-sidebar ul li a:hover {
      background: rgba(255, 255, 255, 0.1);
      color: var(--white);
    }
    
    .admin-sidebar ul li.active a {
      background: var(--primary);
      color: var(--white);
    }
    
    .admin-sidebar ul li a i {
      font-size: 1.2rem;
      margin-right: 10px;
    }
    
    /* Main Content */
    .admin-content {
      flex: 1;
      margin-left: 100px;
      padding: 20px;
      transition: var(--transition);
    }
    
    /* Header */
    .admin-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 1px solid #e5e7eb;
    }
    
    .admin-header h1 {
      font-size: 1.8rem;
      color: var(--dark);
      font-weight: 700;
    }
    
    .user-info {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .user-info span {
      font-weight: 500;
      
    }
    
    .user-avatar {
      width: 40px;
      height: 40px;
      background: var(--primary);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 700;
      font-size: 1.1rem;
    }
    
    /* Stats Cards */
    .admin-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    
    .stat-card {
      background: var(--white);
      padding: 20px;
      border-radius: var(--radius);
      display: flex;
      align-items: center;
      box-shadow: var(--shadow);
      transition: var(--transition);
    }
    
    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
    }
    
    .stat-icon {
      width: 50px;
      height: 50px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 15px;
    }
    
    .stat-icon i {
      font-size: 1.8rem;
      color: var(--white);
    }
    
    .stat-icon.blue {
      background: var(--primary);
    }
    
    .stat-icon.green {
      background: var(--secondary);
    }
    
    .stat-icon.amber {
      background: var(--accent);
    }
    
    .stat-icon.red {
      background: var(--danger);
    }
    
    .stat-info h3 {
      font-size: 0.9rem;
      color: var(--gray);
      margin: 0;
      font-weight: 500;
    }
    
    .stat-info p {
      font-size: 1.6rem;
      margin: 5px 0 0;
      font-weight: 700;
      color: var(--dark);
    }
    
    /* Sections */
    .dashboard-section {
      background: var(--white);
      padding: 20px;
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      margin-bottom: 25px;
    }
    
    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }
    
    .section-header h3 {
      font-size: 1.2rem;
      font-weight: 600;
      color: var(--dark);
    }
    
    .section-header a {
      color: var(--primary);
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 500;
      display: flex;
      align-items: center;
    }
    
    .section-header a i {
      margin-left: 5px;
    }
    
    /* Charts */
    .chart-container {
      position: relative;
      height: 300px;
      width: 100%;
    }
    
    /* Table */
    .table-container {
      overflow-x: auto;
      border-radius: 8px;
      box-shadow: var(--shadow);
      background: var(--white);
      padding: 15px;
      margin-top: 10px;
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
    }
    
    table th, table td {
      text-align: left;
      padding: 12px 15px;
      border-bottom: 1px solid #e5e7eb;
    }
    
    table th {
      font-size: 0.8rem;
      text-transform: uppercase;
      color: var(--gray);
      font-weight: 600;
    }
    
    table tr:last-child td {
      border-bottom: none;
    }
    
    /* Top Performers */
    .performers-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    
    .performer-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 15px;
      background: var(--light);
      border-radius: 8px;
    }
    
    .performer-name {
      font-weight: 600;
    }
    
    .performer-count {
      background: var(--primary);
      color: white;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
    }
    
    /* Notifications */
    .notifications {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 15px;
    }
    
    .notification-card {
      display: flex;
      align-items: center;
      background: var(--light);
      border-radius: 8px;
      padding: 15px;
      transition: var(--transition);
    }
    
    .notification-card:hover {
      transform: translateY(-3px);
      box-shadow: var(--shadow);
    }
    
    .notification-icon {
      font-size: 1.5rem;
      margin-right: 12px;
    }
    
    .notification-icon.warning {
      color: var(--danger);
    }
    
    .notification-icon.info {
      color: var(--primary);
    }
    
    .notification-content h4 {
      font-size: 0.95rem;
      font-weight: 600;
      margin-bottom: 5px;
    }
    
    .notification-content p {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--dark);
    }
    
    /* Responsive */
    @media (max-width: 992px) {
      .admin-sidebar {
        width: 80px;
      }
      
      .admin-sidebar h2,
      .admin-sidebar ul li a span {
        display: none;
      }
      
      .admin-content {
        margin-left: 80px;
      }
    }
    
    @media (max-width: 768px) {
      .admin-stats {
        grid-template-columns: 1fr;

      }
      
      .admin-header {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .user-info {
        margin-top: 10px;
      }
    }
    
    /* Loading Animation */
    .loading {
      display: inline-block;
      width: 20px;
      height: 20px;
      border: 3px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      border-top-color: white;
      animation: spin 1s ease-in-out infinite;
    }
    
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
  </style>
</head>
<body>

<main class="admin-content">
  <div class="admin-header">
    <h1>Dashboard Overview</h1>
  </div>
  
  <!-- Stats Cards -->
  <section class="admin-stats">
    <div class="stat-card">
      <div class="stat-icon blue"><i class='bx bxs-user'></i></div>
      <div class="stat-info">
        <h3>Total Users</h3>
        <p><?php echo $stats['total_users']; ?></p>
      </div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon green"><i class='bx bxs-time-five'></i></div>
      <div class="stat-info">
        <h3>Total Records</h3>
        <p><?php echo $stats['total_records']; ?></p>
      </div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon amber"><i class='bx bxs-calendar-check'></i></div>
      <div class="stat-info">
        <h3>Present Today</h3>
        <p><?php echo $stats['present_today']; ?></p>
      </div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon red"><i class='bx bxs-chart'></i></div>
      <div class="stat-info">
        <h3>Today's Records</h3>
        <p><?php echo $stats['today_records']; ?></p>
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
              <td><?= $record['date'] ?></td>
              <td><?= $record['time_in'] ?></td>
              <td><?= $record['time_out'] ?: '--' ?></td>
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
          <span class="performer-count"><?= $performer['attendance_count'] ?> days</span>
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
          <h4><?= $notification['message'] ?></h4>
          <p><?= $notification['count'] ?> found</p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>  
  </section>
</main>

<?php include 'admin_footer.php'; ?>
</body>
</html>
