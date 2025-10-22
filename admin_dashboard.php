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

// ==================== Date Filtering Logic ====================
$default_days = 30;
$start_date = sanitizeInput($_GET['start_date'] ?? date('Y-m-d', strtotime("-$default_days days")));
$end_date = sanitizeInput($_GET['end_date'] ?? date('Y-m-d'));

// Validate dates to ensure they are in the correct format
$start_date = validateDate($start_date) ? $start_date : date('Y-m-d', strtotime("-$default_days days"));
$end_date = validateDate($end_date) ? $end_date : date('Y-m-d');

// ==================== SECURE Data Fetching ====================
// General stats - USING PREPARED STATEMENTS
$stats_stmt = $conn->prepare("SELECT 
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM attendance WHERE date BETWEEN ? AND ?) as total_records,
    (SELECT COUNT(DISTINCT name) FROM attendance WHERE date = CURDATE()) as present_today,
    (SELECT COUNT(DISTINCT name) FROM attendance WHERE date = CURDATE() AND TIME(time_in) <= '09:00:00') as on_time_today,
    (SELECT COUNT(DISTINCT name) FROM attendance WHERE date = CURDATE() AND TIME(time_in) > '09:00:00') as late_today
");
$stats_stmt->bind_param("ss", $start_date, $end_date);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stats_stmt->close();

// Department stats - SECURE
$dept_stats = [];
$dept_stmt = $conn->prepare("
    SELECT 
        d.name as department_name,
        SUM(CASE WHEN TIME(a.time_in) <= '09:00:00' THEN 1 ELSE 0 END) as on_time_count,
        SUM(CASE WHEN TIME(a.time_in) > '09:00:00' THEN 1 ELSE 0 END) as late_count
    FROM departments d
    LEFT JOIN attendance a ON d.id = a.department AND a.date BETWEEN ? AND ?
    GROUP BY d.id, d.name
    HAVING on_time_count > 0 OR late_count > 0
    ORDER BY department_name ASC
");
$dept_stmt->bind_param("ss", $start_date, $end_date);
$dept_stmt->execute();
$dept_result = $dept_stmt->get_result();
$dept_stats = $dept_result->fetch_all(MYSQLI_ASSOC);
$dept_stmt->close();

// Recent attendance - SECURE
$recent_attendance = [];
$recent_stmt = $conn->prepare("SELECT a.name, d.name as department, a.date, a.time_in, a.time_out 
                           FROM attendance a
                           LEFT JOIN departments d ON a.department = d.id
                           WHERE a.date BETWEEN ? AND ?
                           ORDER BY a.date DESC, a.time_in DESC LIMIT 10");
$recent_stmt->bind_param("ss", $start_date, $end_date);
$recent_stmt->execute();
$recent_result = $recent_stmt->get_result();
$recent_attendance = $recent_result->fetch_all(MYSQLI_ASSOC);
$recent_stmt->close();

// Weekly trend - SECURE
$weekly_trend = [];
$weekly_stmt = $conn->prepare("
    SELECT
        DATE(date) as day,
        COUNT(DISTINCT CASE WHEN TIME(time_in) <= '09:00:00' THEN name END) as on_time_arrivals,
        COUNT(DISTINCT CASE WHEN TIME(time_in) > '09:00:00' THEN name END) as late_arrivals,
        COUNT(DISTINCT CASE WHEN time_out IS NOT NULL AND TIME(time_out) < '17:00:00' THEN name END) as early_departures
    FROM attendance
    WHERE date BETWEEN ? AND ?
    GROUP BY DATE(date)
    ORDER BY day ASC
");
$weekly_stmt->bind_param("ss", $start_date, $end_date);
$weekly_stmt->execute();
$weekly_result = $weekly_stmt->get_result();
$weekly_trend = $weekly_result->fetch_all(MYSQLI_ASSOC);
$weekly_stmt->close();

// Prepare data for the new chart
$chart_labels = array_map(fn($item) => date('D, M j', strtotime($item['day'])), $weekly_trend);
$chart_on_time = array_column($weekly_trend, 'on_time_arrivals');
$chart_late = array_column($weekly_trend, 'late_arrivals');
$chart_early = array_column($weekly_trend, 'early_departures');

// Top performers - SECURE
$top_performers = [];
$top_stmt = $conn->prepare("
    SELECT
        u.username,
        d.name as department_name,
        COUNT(a.id) as days_present,
        SUM(CASE WHEN TIME(a.time_in) > '09:00:00' THEN 1 ELSE 0 END) as late_days,
        -- Performance Score: +10 for each day present, -5 for each late day.
        (COUNT(a.id) * 10 - SUM(CASE WHEN TIME(a.time_in) > '09:00:00' THEN 1 ELSE 0 END) * 5) as performance_score
    FROM
        users u
    JOIN
        attendance a ON u.username = a.name
    LEFT JOIN
        departments d ON u.department = d.id
    WHERE
        a.date BETWEEN ? AND ?
    GROUP BY
        u.id, u.username, d.name
    ORDER BY
        performance_score DESC, late_days ASC
    LIMIT 5
");
$top_stmt->bind_param("ss", $start_date, $end_date);
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        /* Date Filter Form Styles */
        .date-filter-form {
            background: var(--white);
            padding: 20px 30px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
            margin: 30px;
        }

        .date-filter-form .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .date-filter-form label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--gray);
        }

        .date-filter-form input[type="date"] {
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 0.9rem;
            color: var(--dark);
        }

        .date-filter-form .btn-primary {
            padding: 10px 20px;
            align-self: flex-end;
        }

        .date-filter-form .clear-filter {
            align-self: flex-end;
            color: var(--gray);
            font-size: 0.85rem;
            text-decoration: none;
            margin-left: 10px;
        }
        /* Enhanced Dashboard Grid Layout */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr; /* Default to single column for mobile */
            gap: 24px;
            margin-top: 24px;
        }

        /* Responsive grid for larger screens */
        @media (min-width: 992px) {
            .dashboard-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            .dashboard-grid .grid-col-span-2 {
                grid-column: span 2;
            }
        }

        /* Refined Dashboard Section/Card */
        .dashboard-section {
            background: var(--white);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .dashboard-section:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }

        .section-header h3 {
            font-size: 1.1rem;
            color: var(--dark);
            margin: 0;
        }

        .section-header a {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--primary);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: color 0.2s ease;
        }

        .section-header a:hover {
            color: var(--primary-dark);
        }

        /* Improved Top Performers List */
        .performers-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .performer-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px;
            background: var(--light);
            border-radius: 8px;
            transition: background-color 0.2s ease;
        }

        .performer-item:hover {
            background: #e9ecef;
        }

        .performer-name {
            font-weight: 600;
            color: var(--dark);
        }

        .performer-count {
            font-size: 0.9rem;
            font-weight: 700;
            background: var(--primary);
            color: var(--white);
            padding: 4px 10px;
            border-radius: 16px;
        }

        /* Chart container fix */
        .chart-container {
            position: relative;
            height: 300px; /* Give charts a fixed height */
        }

        /* Enhanced Punctuality Leaderboard */
        .leaderboard-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .leaderboard-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px;
            background: var(--light);
            border-radius: 10px;
            transition: background-color 0.2s ease;
        }

        .leaderboard-item:hover {
            background-color: #e9ecef;
        }

        .leaderboard-rank {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--gray);
            width: 30px;
            text-align: center;
        }
        .leaderboard-rank.rank-1 { color: #d4af37; } /* Gold */
        .leaderboard-rank.rank-2 { color: #c0c0c0; } /* Silver */
        .leaderboard-rank.rank-3 { color: #cd7f32; } /* Bronze */

        .leaderboard-info {
            flex: 1;
        }
        .leaderboard-info .name { font-weight: 600; color: var(--dark); }
        .leaderboard-info .department { font-size: 0.85rem; color: var(--gray); }

        .leaderboard-score {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
        }
    </style>
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

        <!-- Date Filter Form -->
        <section>
            <form method="GET" action="" class="date-filter-form">
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                </div>
                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                </div>
                <button type="submit" class="btn-primary">Apply Filter</button>
                <a href="admin_dashboard.php" class="clear-filter">Clear</a>
            </form>
        </section>
        
        <!-- Stats Cards -->
        <section class="admin-stats">
          <div class="stat-card">
            <div class="stat-icon blue"><i class='bx bxs-group'></i></div>
            <div class="stat-info">
              <h3>Total Users</h3>
              <p><?php echo htmlspecialchars($stats['total_users']); ?></p>
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
            <div class="stat-icon green"><i class='bx bxs-user-check'></i></div>
            <div class="stat-info">
              <h3>On Time Today</h3>
              <p><?php echo htmlspecialchars($stats['on_time_today']); ?></p>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon red"><i class='bx bxs-user-x'></i></div>
            <div class="stat-info">
              <h3>Late Today</h3>
              <p><?php echo htmlspecialchars($stats['late_today']); ?></p>
            </div>
          </div>
        </section>
        
        <!-- Charts Section -->
        <div class="dashboard-grid">
          <section class="dashboard-section grid-col-span-2">
            <div class="section-header">
              <h3>Attendance Trend</h3>
              <a href="analytics.php">View Details <i class='bx bx-right-arrow-alt'></i></a>
            </div>
            <div class="chart-container">
              <canvas id="weeklyTrendChart"></canvas>
            </div>
          </section>
          
          <section class="dashboard-section">
            <div class="section-header">
              <h3>Department Punctuality</h3>
              <a href="departments.php">Manage <i class='bx bx-right-arrow-alt'></i></a>
            </div>
            <div class="chart-container">
              <canvas id="departmentChart"></canvas>
            </div>
          </section>
        </div>
        
        <!-- Recent Attendance & Top Performers -->
        <div class="dashboard-grid">
          <section class="dashboard-section grid-col-span-2">
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
                    <td><?= htmlspecialchars($record['department'] ?? 'N/A') ?></td>
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
              <h3>Punctuality Leaderboard</h3>
              <a href="reports.php">View Report <i class='bx bx-right-arrow-alt'></i></a>
            </div>
            <div class="leaderboard-list">
              <?php 
                $rank = 1; 
                $rank_icons = ['bxs-trophy', 'bxs-medal', 'bxs-medal'];
                foreach ($top_performers as $performer): 
              ?>
              <div class="leaderboard-item">
                <div class="leaderboard-rank rank-<?= $rank ?>">
                  <i class='bx <?= $rank_icons[$rank-1] ?? 'bxs-star' ?>'></i>
                </div>
                <div class="leaderboard-info">
                  <div class="name"><?= htmlspecialchars($performer['username']) ?></div>
                  <div class="department"><?= htmlspecialchars($performer['department_name'] ?? 'N/A') ?></div>
                </div>
                <div class="leaderboard-score" title="Performance Score">
                  <?= htmlspecialchars(round($performer['performance_score'])) ?>
                </div>
              </div>
              <?php 
                $rank++;
                endforeach; 
              ?>
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