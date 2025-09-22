
<?php
session_start();
include 'config.php';

// Angalia kama ni admin
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Hakikisha ni admin
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

// Chukua data kwa ajili ya ripoti
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

$attendance_data = [];
$stmt = $conn->prepare("SELECT name, department, date, time_in, time_out 
                        FROM attendance 
                        WHERE date BETWEEN ? AND ?
                        ORDER BY date DESC");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$attendance_data = $result->fetch_all(MYSQLI_ASSOC);

// Include admin header if any
include 'admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<!-- Badilisha sehemu ya <head> kuwa kama hii -->
<head>
  <meta charset="UTF-8">
  <title>Admin Panel - Reports</title>
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
      margin-left: 230px;
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
    
    /* Report System */
    .report-system {
      display: grid;
      gap: 20px;
    }
    
    .date-filter {
      background: var(--white);
      padding: 20px;
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      display: flex;
      align-items: center;
      gap: 15px;
      flex-wrap: wrap;
    }
    
    .date-filter label {
      display: flex;
      flex-direction: column;
      gap: 5px;
      font-size: 0.9rem;
      color: black;
    }
    
    .date-filter input {
      padding: 8px 12px;
      border: 1px solid #e5e7eb;
      border-radius: 6px;
    }
    
    .date-filter button {
      background: var(--primary);
      color: white;
      border: none;
      padding: 8px 15px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      transition: var(--transition);
    }
    
    .date-filter button:hover {
      background: var(--primary-dark);
    }
    
    .export-btn {
      background: var(--secondary);
      color: white;
      text-decoration: none;
      padding: 8px 15px;
      border-radius: 6px;
      font-weight: 600;
      transition: var(--transition);
    }
    
    .export-btn:hover {
      background: #0d9c6f;
    }
    
    .report-table {
      width: 100%;
      border-collapse: collapse;
      background: var(--white);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      overflow: hidden;
    }
    
    .report-table th, 
    .report-table td {
      text-align: left;
      padding: 12px 15px;
      border-bottom: 1px solid #e5e7eb;
      font-size: 0.9rem;
      color: var(--dark);
    }
    
    .report-table th {
      font-size: 0.8rem;
      text-transform: uppercase;
      color: var(--white);
      font-weight: 700;
      background: var(--primary);
      letter-spacing: 1px;
      border-bottom: 2px solid var(--primary-dark);
    }
    
    .report-table tr:last-child td {
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
      
      .date-filter {
        flex-direction: column;
        align-items: flex-start;
      }
    }
  </style>
</head>
</head>
<body>
  <div class="admin-container">
    <main class="admin-content" style="margin-left:0;">
      <header class="admin-header">
        <h1>Attendance Reports</h1>
        <div class="admin-user">
         
        </div>
      </header>

      <div class="report-system">
        <!-- Fomu ya kuchagua tarehe -->
        <form method="GET" class="date-filter">
            <label>Start Date:
                <input type="date" name="start_date" value="<?= $start_date ?>">
            </label>
            <label>End Date:
                <input type="date" name="end_date" value="<?= $end_date ?>">
            </label>
            <button type="submit">Filter</button>
            <a href="export.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="export-btn">Export to Excel</a>
        </form>

        <!-- Matokeo ya ripoti -->
        <table class="report-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Date</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Hours</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attendance_data as $record): 
                    $hours = '--';
                    if ($record['time_out']) {
                        $time_in = new DateTime($record['time_in']);
                        $time_out = new DateTime($record['time_out']);
                        $diff = $time_out->diff($time_in);
                        $hours = $diff->h . 'h ' . $diff->i . 'm';
                    }
                ?>
                <tr>
                    <td><?= htmlspecialchars($record['name']) ?></td>
                    <td><?= htmlspecialchars($record['department']) ?></td>
                    <td><?= $record['date'] ?></td>
                    <td><?= $record['time_in'] ?></td>
                    <td><?= $record['time_out'] ?: '--' ?></td>
                    <td><?= $hours ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
      </div>
    </main>
  </div>
</body>
</html>