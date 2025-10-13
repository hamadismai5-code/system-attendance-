<?php
session_start();
include 'config.php';
// Hakikisha admin ame-login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
$username = $_SESSION['username'];
// Angalia kama user ni admin
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($is_admin);
$stmt->fetch();
$stmt->close();
if (!$is_admin) {
    die("Access denied. You are not an admin.");
}
// Chukua attendance zote
$att_stmt = $conn->prepare("SELECT Name, Date, Time_in, Time_out FROM attendance ORDER BY Date DESC");
$att_stmt->execute();
$att_stmt->bind_result($name, $date, $time_in, $time_out);
$attendance_records = [];
while ($att_stmt->fetch()) {
    $attendance_records[] = [
        'name' => $name,
        'date' => $date,
        'time_in' => $time_in,
        'time_out' => $time_out
    ];
}
$att_stmt->close();
// Group kwa mwezi na wiki
$grouped = [];
foreach ($attendance_records as $r) {
    $ts = strtotime($r['date']);
    $monthKey = date('Y-m', $ts);
    $weekKey = date('o-W', $ts);
    if (!isset($grouped[$monthKey])) {
        $grouped[$monthKey] = [
            'records' => [],
            'weeks' => [],
            'summary' => ['present' => 0, 'late' => 0, 'hours' => 0.0],
        ];
    }
    $grouped[$monthKey]['records'][] = $r;
    if (!isset($grouped[$monthKey]['weeks'][$weekKey])) {
        $grouped[$monthKey]['weeks'][$weekKey] = [];
    }
    $grouped[$monthKey]['weeks'][$weekKey][] = $r;
    $ti = new DateTime($r['time_in']);
    $to = $r['time_out'] ? new DateTime($r['time_out']) : null;
    $isLate = ($ti->format('H:i') > '08:15');
    if ($isLate) {
        $grouped[$monthKey]['summary']['late']++;
    } else {
        $grouped[$monthKey]['summary']['present']++;
    }
    if ($to) {
        $diff = $to->diff($ti);
        $hours = $diff->h + ($diff->i / 60);
        $grouped[$monthKey]['summary']['hours'] += $hours;
    }
}
krsort($grouped);
$currentMonthKey = date('Y-m');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Attendance Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .attendance-container {
            padding: 20px;
        }
        
        .month {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            overflow: hidden;
            transition: var(--transition);
        }
        
        .month:hover {
            box-shadow: 0 6px 20px rgba(99,102,241,0.12);
        }
        
        .month-header {
            width: 100%;
            text-align: left;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            background: var(--light);
            border: none;
            cursor: pointer;
            font-weight: 700;
            font-size: 16px;
            color: var(--dark);
            transition: var(--transition);
        }
        
        .month-header:hover {
            background: #f1f5f9;
        }
        
        .month-stats {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .chev {
            transition: transform .3s ease;
            display: inline-block;
            margin-left: 8px;
            vertical-align: middle;
            font-size: 18px;
            color: var(--gray);
        }

        .month.open .chev {
            transform: rotate(180deg);
        }
        
        .month-body {
            padding: 0;
            display: none;
        }
        
        .month.open .month-body {
            display: block;
        }
        
        /* Weekly Grid */
        .wk-grid {
            display: grid;
            gap: 15px;
            margin: 20px;
            padding: 20px;
            background: var(--light);
            border-radius: 8px;
        }
        
        @media (min-width: 768px) {
            .wk-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        .wk-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 15px;
            transition: var(--transition);
        }
        
        .wk-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        /* Status Pills */
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 999px;
            padding: 6px 12px;
            margin: 2px;
        }
        
        .pill.green {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .pill.amber {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fcd34d;
        }
        
        .pill.red {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .pill.gray {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        
        .pill i {
            font-size: 14px;
        }
        
        /* Table Styles */
        .table-container {
            overflow-x: auto;
            margin: 20px;
        }
        
        .table-container table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table-container th {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white);
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        
        .table-container td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border);
            color: var(--dark);
        }
        
        .table-container tr:hover td {
            background: var(--light);
        }
        
        /* Summary Cards */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px;
        }
        
        .summary-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: var(--transition);
        }
        
        .summary-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }
        
        .summary-card h4 {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .summary-card .value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .summary-card.present .value { color: var(--success); }
        .summary-card.late .value { color: var(--accent); }
        .summary-card.hours .value { color: var(--primary); }
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
        <li class="active">
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
            <h1>Attendance Records</h1>
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
        
        <div class="attendance-container">
            <?php foreach ($grouped as $monthKey => $monthData): ?>
            <div class="month <?= $monthKey == $currentMonthKey ? 'open' : '' ?>">
                <button class="month-header">
                    <span>
                        <?= date('F Y', strtotime($monthKey . '-01')) ?>
                        <i class='chev bx bx-chevron-down'></i>
                    </span>
                    <div class="month-stats">
                        <span class="pill green">
                            <i class='bx bx-check-circle'></i> <?= $monthData['summary']['present'] ?> Present
                        </span>
                        <span class="pill amber">
                            <i class='bx bx-time-five'></i> <?= $monthData['summary']['late'] ?> Late
                        </span>
                        <span class="pill gray">
                            <i class='bx bx-timer'></i> <?= number_format($monthData['summary']['hours'], 1) ?>h
                        </span>
                    </div>
                </button>
                <div class="month-body">
                    <!-- Summary Cards -->
                    <div class="summary-cards">
                        <div class="summary-card present">
                            <h4>Present Days</h4>
                            <div class="value"><?= $monthData['summary']['present'] ?></div>
                        </div>
                        <div class="summary-card late">
                            <h4>Late Arrivals</h4>
                            <div class="value"><?= $monthData['summary']['late'] ?></div>
                        </div>
                        <div class="summary-card hours">
                            <h4>Total Hours</h4>
                            <div class="value"><?= number_format($monthData['summary']['hours'], 1) ?></div>
                        </div>
                    </div>

                    <!-- Weekly Breakdown -->
                    <div class="wk-grid">
                        <?php foreach ($monthData['weeks'] as $weekKey => $weekRecords): ?>
                        <div class="wk-card">
                            <h4>Week <?= substr($weekKey, 6) ?> (<?= count($weekRecords) ?> days)</h4>
                            <?php foreach ($weekRecords as $r): ?>
                            <div style="margin-top: 10px; padding: 8px; border-bottom: 1px solid #eee;">
                                <div><strong><?= $r['name'] ?></strong></div>
                                <div style="font-size: 0.9em; color: #666;">
                                    <?= $r['date'] ?> 
                                    | In: <?= substr($r['time_in'], 0, 5) ?>
                                    <?php if ($r['time_out']): ?>
                                    | Out: <?= substr($r['time_out'], 0, 5) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Detailed Table -->
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Date</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monthData['records'] as $r): ?>
                                <?php
                                $ti = new DateTime($r['time_in']);
                                $to = $r['time_out'] ? new DateTime($r['time_out']) : null;
                                $isLate = ($ti->format('H:i') > '08:15');
                                $status = $isLate ? 'Late' : 'Present';
                                $statusClass = $isLate ? 'amber' : 'green';
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['name']) ?></td>
                                    <td><?= $r['date'] ?></td>
                                    <td><?= $r['time_in'] ?></td>
                                    <td><?= $r['time_out'] ?: '--' ?></td>
                                    <td>
                                        <span class="pill <?= $statusClass ?>">
                                            <?= $status ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
      </div>
    </main>
  </div>

  <script>
    document.querySelectorAll('.month-header').forEach(header => {
        header.addEventListener('click', () => {
            const month = header.closest('.month');
            month.classList.toggle('open');
        });
    });
  </script>
</body>
</html>