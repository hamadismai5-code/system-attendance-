<?php
session_start();
include 'config.php';

// Check if user is an admin
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
// Make sure the user is an admin
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE username = ?");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$stmt->bind_result($is_admin);
$stmt->fetch();
$stmt->close();

if (!$is_admin) {
    header("Location: attendance.php");
    exit();
}

// Generate reports
$reports = [
    'daily' => [],
    'monthly' => [],
    'user_summary' => []
];

// Daily report
$result = $conn->query("SELECT date, COUNT(*) as total_entries, 
                        COUNT(DISTINCT name) as unique_users,
                        SUM(CASE WHEN TIME(time_in) > '09:00:00' THEN 1 ELSE 0 END) as late_arrivals
                        FROM attendance 
                        GROUP BY date 
                        ORDER BY date DESC LIMIT 30");
$reports['daily'] = $result->fetch_all(MYSQLI_ASSOC);

// Monthly report
$result = $conn->query("SELECT DATE_FORMAT(date, '%Y-%m') as month,
                        COUNT(*) as total_entries,
                        COUNT(DISTINCT name) as unique_users,
                        AVG(CASE WHEN TIME(time_in) > '09:00:00' THEN 1 ELSE 0 END) * 100 as late_percentage
                        FROM attendance 
                        GROUP BY DATE_FORMAT(date, '%Y-%m')
                        ORDER BY month DESC LIMIT 12");
$reports['monthly'] = $result->fetch_all(MYSQLI_ASSOC);

// User summary
$result = $conn->query("SELECT name, 
                        COUNT(*) as total_days,
                        SUM(CASE WHEN TIME(time_in) > '09:00:00' THEN 1 ELSE 0 END) as late_days,
                        AVG(TIMESTAMPDIFF(MINUTE, time_in, time_out))/60 as avg_hours
                        FROM attendance 
                        WHERE time_out IS NOT NULL
                        GROUP BY name 
                        ORDER BY total_days DESC");
$reports['user_summary'] = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Reports</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .reports-container {
            padding: 20px;
        }
        
        .report-section {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 30px;
            transition: var(--transition);
        }
        
        .report-section:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99,102,241,0.15);
        }
        
        .report-section h3 {
            color: var(--dark);
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary);
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .report-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .report-table th {
            background: linear-gradient(135deg, var(--dark), #1c2a49ff);
            color: var(--white);
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        
        .report-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border);
            color: var(--dark);
        }
        
        .report-table tr:hover td {
            background: var(--light);
        }
        
        .percentage-badge {
            background: var(--success);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .percentage-badge.warning {
            background: var(--accent);
        }
        
        .percentage-badge.danger {
            background: var(--danger);
        }
        
        .export-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .btn-export {
            background: var(--success);
            color: var(--white);
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-export:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        
        .btn-export.secondary {
            background: var(--primary);
        }
        
        .btn-export.secondary:hover {
            background: var(--primary-dark);
        }
    </style>
</head>
<body>
  <div class="admin-container">
    <!-- SIDEBAR IN THIS PAGE -->
    <aside class="admin-sidebar">
      <h2>Admin Panel</h2>
      <ul>
        <li>
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
        <li class="active">
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
            <h1>Attendance Reports</h1>
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

        <div class="reports-container">
            <!-- Export Buttons -->
            <div class="export-buttons">
                <a href="export_csv.php" class="btn-export">
                    <i class='bx bx-download'></i> Export CSV
                </a>
                <a href="export_pdf.php" class="btn-export secondary">
                    <i class='bx bxs-file-pdf'></i> Export PDF
                </a>
            </div>

            <!-- Daily Report -->
            <section class="report-section">
                <h3><i class='bx bx-calendar'></i> Daily Attendance Report (Last 30 Days)</h3>
                <div class="table-container">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Total Entries</th>
                                <th>Unique Users</th>
                                <th>Late Arrivals</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports['daily'] as $daily): ?>
                            <tr>
                                <td><?= $daily['date'] ?></td>
                                <td><?= $daily['total_entries'] ?></td>
                                <td><?= $daily['unique_users'] ?></td>
                                <td>
                                    <span class="percentage-badge <?= $daily['late_arrivals'] > 0 ? 'warning' : '' ?>">
                                        <?= $daily['late_arrivals'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Monthly Report -->
            <section class="report-section">
                <h3><i class='bx bx-stats'></i> Monthly Summary Report</h3>
                <div class="table-container">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Total Entries</th>
                                <th>Unique Users</th>
                                <th>Late Arrival %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports['monthly'] as $monthly): ?>
                            <tr>
                                <td><?= date('F Y', strtotime($monthly['month'] . '-01')) ?></td>
                                <td><?= $monthly['total_entries'] ?></td>
                                <td><?= $monthly['unique_users'] ?></td>
                                <td>
                                    <span class="percentage-badge <?= $monthly['late_percentage'] > 20 ? 'danger' : ($monthly['late_percentage'] > 10 ? 'warning' : '') ?>">
                                        <?= number_format($monthly['late_percentage'], 1) ?>%
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- User Summary -->
            <section class="report-section">
                <h3><i class='bx bx-user'></i> User Performance Summary</h3>
                <div class="table-container">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Total Days</th>
                                <th>Late Days</th>
                                <th>Average Hours</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports['user_summary'] as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['name']) ?></td>
                                <td><?= $user['total_days'] ?></td>
                                <td>
                                    <span class="percentage-badge <?= $user['late_days'] > 0 ? 'warning' : '' ?>">
                                        <?= $user['late_days'] ?>
                                    </span>
                                </td>
                                <td><?= number_format($user['avg_hours'], 2) ?> hours</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
      </div>
    </main>
  </div>
</body>
</html>