<?php
// Enhanced attendance.php
include 'session_check.php';
include 'config.php';

// Verify user is logged in
validateSession();

// Process form data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        $message = "Invalid form submission.";
    } else {
        $name = $_SESSION['username'];
        $department = $_SESSION['department'] ?? 'N/A'; // sasa system inachukua department yenyewe
        $current_date = date('Y-m-d');
        $current_time = date('H:i:s');

        // Check kama user ana record leo
        $check_stmt = $conn->prepare("SELECT id, Time_in, Time_out FROM attendance WHERE Name = ? AND Date = ?");
        $check_stmt->bind_param("ss", $name, $current_date);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $check_stmt->bind_result($att_id, $time_in, $time_out);
            $check_stmt->fetch();

            // Ikiwa user anabonyeza Time Out
            if (isset($_POST['timeout']) && empty($time_out)) {
                $update_stmt = $conn->prepare("UPDATE attendance SET Time_out = ? WHERE id = ?");
                $update_stmt->bind_param("si", $current_time, $att_id);
                if ($update_stmt->execute()) {
                    $message = "Logout time has been recorded successfully $name";
                } else {
                    $message = "Error updating logout time.";
                }
                $update_stmt->close();
            } else {
                $message = "You already recorded attendance today.";
            }
        } else {
            // Ikiwa user anabonyeza Time In
            if (isset($_POST['timein'])) {
                $insert_stmt = $conn->prepare("INSERT INTO attendance (Name, Department, Date, Time_in) VALUES (?, ?, ?, ?)");
                $insert_stmt->bind_param("ssss", $name, $department, $current_date, $current_time);
                if ($insert_stmt->execute()) {
                    $message = "Login time has been recorded successfully $name";
                } else {
                    $message = "Error inserting new record.";
                }
                $insert_stmt->close();
            } else {
                $message = "You need to Time In first.";
            }
        }

        $check_stmt->close();
    }
}

// Fetch attendance log with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$current_user = $_SESSION['username'];

$attendance_data = [];
$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE Name = ?");
$count_stmt->bind_param("s", $current_user);
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);
$count_stmt->close();

$att_stmt = $conn->prepare("SELECT Name, Department, Date, Time_in, Time_out 
                           FROM attendance 
                           WHERE Name = ? 
                           ORDER BY Date DESC, Time_in DESC 
                           LIMIT ? OFFSET ?");
$att_stmt->bind_param("sii", $current_user, $limit, $offset);
$att_stmt->execute();
$attendance_data = $att_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$att_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Attendance System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="css/project.css" />
</head>
<body>
  <main class="container">
    <header>
      <h1>Attendance System</h1>
      <?php if ($_SESSION['is_admin']): ?>
        <a href="admin_dashboard.php" class="admin-link">Admin Dashboard</a>
      <?php endif; ?>
    </header>

    <section class="attendance-section">
      <form method="POST" action="attendance.php" id="attendance-form" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

        <!-- Name from session -->
        <div class="form-group">
          <label for="Name">Name</label>
          <p><strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></p>
        </div>

        <!-- Department from session -->
        <div class="form-group">
          <label>Department</label>
          <p><strong><?php echo htmlspecialchars($_SESSION['department'] ?? 'N/A'); ?></strong></p>
        </div>

        <!-- Buttons -->
        <button type="submit" name="timein" class="btn-primary">Time In</button>
        <button type="submit" name="timeout" class="btn-danger">Time Out</button>
      </form>
    </section>

    <section class="message-section">
      <div id="message"><?php if(isset($message)) echo $message; ?></div>
    </section>

    <section class="log-section">
      <h2>Attendance Log</h2>
      <div class="table-responsive">
        <table id="attendance-table">
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
            <?php foreach ($attendance_data as $record): ?>
            <tr>
                <td><?php echo htmlspecialchars($record['Name']); ?></td>
                <td><?php echo htmlspecialchars($record['Department']); ?></td>
                <td><?php echo htmlspecialchars($record['Date']); ?></td>
                <td><?php echo htmlspecialchars($record['Time_in']); ?></td>
                <td><?php echo htmlspecialchars($record['Time_out'] ?: '--'); ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        
        <!-- Pagination -->
        <div class="pagination">
          <?php if ($total_pages > 1): ?>
            <?php if ($page > 1): ?>
              <a href="?page=<?php echo $page - 1; ?>">&laquo; Previous</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <a href="?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
              <a href="?page=<?php echo $page + 1; ?>">Next &raquo;</a>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <footer style="text-align:center; padding: 20px; font-size: 14px; color: #777;">
      &copy; 2025 Attendance System. All rights reserved.
    </footer>
  </main>
</body>
</html>
