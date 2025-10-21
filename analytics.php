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

// Fetch analysis data
$analytics = [
    'late_arrivals' => [],
    'early_departures' => [],
    'average_hours' => []
];

// People who were late (after 9:00 AM)
$result = $conn->query("SELECT name, department, date, time_in 
                        FROM attendance 
                        WHERE TIME(time_in) > '09:00:00'
                        ORDER BY date DESC LIMIT 10");
$analytics['late_arrivals'] = $result->fetch_all(MYSQLI_ASSOC);

// People who left early (before 5:00 PM)
$result = $conn->query("SELECT name, department, date, time_out 
                        FROM attendance 
                        WHERE TIME(time_out) < '17:00:00' AND time_out IS NOT NULL
                        ORDER BY date DESC LIMIT 10");
$analytics['early_departures'] = $result->fetch_all(MYSQLI_ASSOC);

// Average hours for each department
$result = $conn->query("SELECT department, 
                        AVG(TIMESTAMPDIFF(MINUTE, time_in, time_out))/60 as avg_hours
                        FROM attendance 
                        WHERE time_out IS NOT NULL
                        GROUP BY department");
$analytics['average_hours'] = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Analytics</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .analytics-container {
            padding: 20px;
        }
        
        .analytics-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-top: 20px;
            max-width: 100%;
       }
        
        .analytics-card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 25px;
            transition: var(--transition);
            overflow-x: auto;
            max-width: 100%;
            margin-bottom: 20px;
        }
        
        .analytics-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(99,102,241,0.15);
            cursor: pointer;
        }
        
        .analytics-card h3 {
            color: var(--dark);
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary);
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .analytics-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .analytics-table th {
            background: linear-gradient(135deg, var(--dark), #1c2a49ff);
            color: var(--white);
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        
        .analytics-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border);
            color: var(--dark);
        }
        
        .analytics-table tr:hover td {
            background: var(--light);
        }
        
        .hours-badge {
            background: var(--success);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
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
        <li>
          <a href="reports.php"><i class='bx bxs-report'></i><span> Reports</span></a>
        </li>
        <li class="active">
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
            <h1>Attendance Analytics</h1>
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

        <div class="analytics-container">
            <div class="analytics-grid">
                <!-- Late Arrivals -->
                <div class="analytics-card">
                    <h3><i class='bx bx-time' style="color: var(--danger);"></i> Late Arrivals (After 9:00 AM)</h3>
                    <table class="analytics-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Date</th>
                                <th>Time In</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($analytics['late_arrivals'] as $record): ?>
                            <tr>
                                <td><?= htmlspecialchars($record['name']) ?></td>
                                <td><?= htmlspecialchars($record['department']) ?></td>
                                <td><?= $record['date'] ?></td>
                                <td style="color: var(--danger); font-weight: 600;"><?= $record['time_in'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Early Departures -->
                <div class="analytics-card">
                    <h3><i class='bx bx-log-out' style="color: var(--accent);"></i> Early Departures (Before 5:00 PM)</h3>
                    <table class="analytics-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Date</th>
                                <th>Time Out</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($analytics['early_departures'] as $record): ?>
                            <tr>
                                <td><?= htmlspecialchars($record['name']) ?></td>
                                <td><?= htmlspecialchars($record['department']) ?></td>
                                <td><?= $record['date'] ?></td>
                                <td style="color: var(--accent); font-weight: 600;"><?= $record['time_out'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Average Hours -->
                <div class="analytics-card">
                    <h3><i class='bx bx-timer' style="color: var(--success);"></i> Average Working Hours by Department</h3>
                    <table class="analytics-table">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Average Hours</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($analytics['average_hours'] as $record): ?>
                            <tr>
                                <td><?= htmlspecialchars($record['department']) ?></td>
                                <td>
                                    <span class="hours-badge">
                                        <?= number_format($record['avg_hours'], 2) ?> hours
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
      </div>
    </main>
  </div>

<?php include 'admin_footer.php'; ?>
</body>
</html>