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

// Include admin header if any
include 'admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
 <!-- Change the <head> section to be like this -->
<head>
  <meta charset="UTF-8">
  <title>Admin Panel - Analytics</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="css/admin.css">
  <style>
    :root {
      --primary: #6366f1;           /* Indigo-500 */
      --primary-dark: #4338ca;      /* Indigo-700 */
      --secondary: #06b6d4;         /* Cyan-500 */
      --accent: #fbbf24;            /* Amber-400 */
      --danger: #ef4444;            /* Red-500 */
      --dark: #111827;              /* Gray-900 */
      --gray: #6b7280;              /* Gray-500 */
      --light: #f8fafc;             /* Gray-50 */
      --white: #ffffff;
      --shadow: 0 4px 12px rgba(99,102,241,0.08);
      --radius: 14px;
      --transition: all 0.3s cubic-bezier(.4,0,.2,1);
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
      background: linear-gradient(180deg, var(--primary-dark) 80%, var(--dark) 100%);
      color: var(--white);
      position: fixed;
      height: 100vh;
      padding-top: 20px;
      transition: var(--transition);
      z-index: 100;
      overflow-y: auto;
      box-shadow: 2px 0 12px rgba(99,102,241,0.08);
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
      color: #e0e7ff;
      text-decoration: none;
      border-radius: 8px;
      transition: var(--transition);
    }
    
    .admin-sidebar ul li a:hover {
      background: var(--primary);
      color: #fff;
      box-shadow: 0 2px 8px rgba(99,102,241,0.10);
    }
    
    .admin-sidebar ul li.active a {
      background: var(--accent);
      color: var(--dark);
      font-weight: 700;
      box-shadow: 0 2px 8px rgba(251,191,36,0.10);
    }
    
    .admin-sidebar ul li a i {
      font-size: 1.2rem;
      margin-right: 10px;
    }
    
    /* Main Content */
    .admin-content {
      flex: 1;
      margin-left: 240px;
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
      background: var(--white);
      box-shadow: var(--shadow);
    }
    
    .admin-header h1 {
      font-size: 1.8rem;
      color: var(--dark);
      font-weight: 700;
    }
    
    .admin-user {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .admin-user span {
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
    
    /* Analytics Sections */
    .analytics-system {
      display: grid;
      gap: 20px;
    }
    
    .analytics-section {
      background: var(--white);
      padding: 20px;
      border-radius: var(--radius);
      box-shadow: var(--shadow);
    }
    
    .analytics-section h3 {
      font-size: 1.2rem;
      font-weight: 600;
      color: var(--dark);
      margin-bottom: 15px;
    }
    
    .analytics-table {
      width: 100%;
      border-collapse: collapse;
    }
    
    .analytics-table th, 
    .analytics-table td {
      text-align: left;
      padding: 12px 15px;
      border-bottom: 1px solid #e5e7eb;
      font-size: 0.9rem;
    }
    
    .analytics-table th {
      font-size: 0.8rem;
      text-transform: uppercase;
      color: var(--white);
      font-weight: 700;
      background: var(--primary);
      letter-spacing: 1px;
      border-bottom: 2px solid var(--primary-dark);
    }
    
    .analytics-table tr:last-child td {
      border-bottom: none;
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
      .admin-header {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .admin-user {
        margin-top: 10px;
      }
    }
  </style>
</head>
</head>
<body>
  <main class="admin-content" style="margin-left:0;">
      <header class="admin-header">
        <h1>Attendance Analytics</h1>
        <div class="admin-user">
        
        </div>
      </header>

      <div class="analytics-system">
       <!-- Lateness analysis -->
        <div class="analytics-section">
            <h3>Late Arrivals (After 9:00 AM)</h3>
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
                        <td><?= $record['time_in'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

      <!-- Early departure analysis -->
        <div class="analytics-section">
            <h3>Early Departures (Before 5:00 PM)</h3>
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
                        <td><?= $record['time_out'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

      <!-- Average hours per department -->
        <div class="analytics-section">
            <h3>Average Working Hours by Department</h3>
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
                        <td><?= number_format($record['avg_hours'], 2) ?> hours</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
      </div>
  </main>
</body>
</html>