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
// Include admin header if any
include 'admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Attendance Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
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
      --border: #e5e7eb;
      --radius: 12px;
      --transition: all 0.3s ease;
      --shadow: 0 4px 6px rgba(0,0,0,0.05);
 }

        /* ================= RESET ================= */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            outline: none;
            border: none;
            text-decoration: none;
            list-style: none;
        }
        body {
           font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
           background: var(--light);
           color: var(--dark);
           line-height: 1.5;
    }
        /* ================= SIDEBAR ================= */
         /* .admin-sidebar {
            width: 240px;
            background: var(--dark);
            color: var(--white);
            position: fixed;
            height: 100vh;
            padding-top: 20px;
            overflow-y: auto;
            z-index: 1000;
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
        .admin-sidebar ul li a i {
            font-size: 1.3rem;
            margin-right: 10px;
        }
        .admin-sidebar ul li a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }
        .admin-sidebar ul li.active a {
            background: var(--primary);
            color: #ffffff;
        }  */
        /* ================= MAIN CONTENT ================= */
        /* .admin-content {
            margin-left: 230px;
            padding: 10px;
            width: calc(100% - 230px);
            height: 100vh;
            overflow-y: auto;

        } */
        /* ================= HEADER ================= */
       .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border);
        }

        .admin-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .admin-user {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: var(--gray);
        }
        /* ================= MONTH CARDS ================= */
        .month {
            border: 2px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            margin-bottom: 16px;
            background: var(--white);
        }
        .month-header {
            width: 100%;
            text-align: left;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 20px;
            background: var(--light);
            border: none;
            cursor: pointer;
            font-weight: 700;
        }
        .chev {
            transition: transform .2s ease;
            display: inline-block;
            margin-left: 8px;
            vertical-align: middle;
            font-size: 18px;
            
        }
        .month.open .chev {
            transform: rotate(180deg);
        }
        .month-body {
            padding: 16px;
            display: none;
        }
        .month.open .month-body {
            display: block;
        }
        /* ================= WEEKLY GRID ================= */
        .wk-grid {
            display: grid;
            gap: 12px;
            margin-bottom: 12px;
        }
        @media (min-width: 768px) {
            .wk-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        .wk-card {
            border: 1px solid var(--border);
            background: #dbeafe;
            border-radius: 12px;
            padding: 12px;
        }
        /* ================= TABLE ================= */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 12px;
        }
        thead th {
            background: #f9fafb;
            color: #374151;
            font-size: 12px;
            text-transform: uppercase;
            padding: 10px;
            border-bottom: 1px solid var(--border);
        }
        tbody td {
            padding: 10px;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
        }
        tbody tr:last-child td {
            border-bottom: none;
        }
        /* ================= STATUS PILLS ================= */
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 999px;
            padding: 4px 8px;
        }
        .pill.green {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .pill.amber {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .pill.red {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .muted {
            color: var(--gray);
        }
    </style>
</head>
<body>

    <!-- Main Content -->
    <main class="admin-content">
        <!-- <header class="admin-header">
            <h1>Attendance Records</h1>
            <div class="admin-user"><span>Admin: <?php echo htmlspecialchars($username); ?></span></div>
        </header> -->
        
        <?php foreach ($grouped as $monthKey => $data):
            $monthTitle = date('F Y', strtotime($monthKey . '-01'));
            $summary = $data['summary'];
            $weeklyRows = [];
            foreach ($data['weeks'] as $wKey => $records) {
                $present = $late = $hours = 0;
                foreach ($records as $r) {
                    $ti = new DateTime($r['time_in']);
                    $to = $r['time_out'] ? new DateTime($r['time_out']) : null;
                    if ($ti->format('H:i') > '08:15') {
                        $late++;
                    } else {
                        $present++;
                    }
                    if ($to) {
                        $d = $to->diff($ti);
                        $hours += $d->h + ($d->i / 60);
                    }
                }
                $weeklyRows[] = ['label' => 'Week ' . substr($wKey, -2), 'present' => $present, 'late' => $late, 'hours' => $hours];
            }
        ?>
            <div class="month<?php echo ($monthKey === $currentMonthKey) ? ' open' : ''; ?>">
                <button class="month-header">
                    <span><?php echo $monthTitle; ?></span>
                    <span style="display:flex;gap:8px">
                        <span class="pill green">Present: <?php echo $summary['present']; ?></span>
                        <span class="pill amber">Late: <?php echo $summary['late']; ?></span>
                        <span class="pill" style="background:#eef2ff;color:#3730a3;border:1px solid #c7d2fe">Hours: <?php echo number_format($summary['hours'], 1); ?></span>
                        <svg class="chev" width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M6 9l6 6 6-6" stroke="#374151" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </button>
                <div class="month-body">
                    <div class="wk-grid">
                        <?php foreach ($weeklyRows as $wk): ?>
                            <div class="wk-card">
                                <strong><?php echo $wk['label']; ?></strong><br>
                                <span class="pill green">Present: <?php echo $wk['present']; ?></span>
                                <span class="pill amber">Late: <?php echo $wk['late']; ?></span>
                                <span class="pill" style="background:#eef2ff;color:#3730a3;border:1px solid #c7d2fe">Hours: <?php echo number_format($wk['hours'], 1); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Date</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Hours</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['records'] as $rec):
                                $ti = new DateTime($rec['time_in']);
                                $to = $rec['time_out'] ? new DateTime($rec['time_out']) : null;
                                $status = ($ti->format('H:i') > '08:15') ? 'Late' : 'Present';
                                $hoursTxt = '--';
                                if ($to) {
                                    $d = $to->diff($ti);
                                    $hoursTxt = $d->h . 'h ' . $d->i . 'm';
                                }
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($rec['name']); ?></td>
                                    <td><?php echo htmlspecialchars($rec['date']); ?></td>
                                    <td><?php echo htmlspecialchars($rec['time_in']); ?></td>
                                    <td><?php echo $rec['time_out'] ? htmlspecialchars($rec['time_out']) : '--'; ?></td>
                                    <td><?php echo $hoursTxt; ?></td>
                                    <td><?php echo $status; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.month-header').forEach(h => {
                h.addEventListener('click', function() {
                    let m = h.parentElement;
                    document.querySelectorAll('.month').forEach(x => {
                        if (x !== m) x.classList.remove('open');
                    });
                    m.classList.toggle('open');
                });
            });
        });
    </script>
</body>
</html>