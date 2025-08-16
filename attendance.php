<?php
session_start();
include 'config.php';

// Verify user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Process form data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['Name'];
    $department = $_POST['Department'];
    $current_date = date('Y-m-d');
    $current_time = date('H:i:s');
    
    try {
        // Check if user already has an entry today
        $check_stmt = $conn->prepare("SELECT id FROM attendance WHERE Name = ? AND Date = ?");
        $check_stmt->bind_param("ss", $name, $current_date);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            // Update time out if record exists
            $update_stmt = $conn->prepare("UPDATE attendance SET Time_out = ? WHERE Name = ? AND Date = ?");
            $update_stmt->bind_param("sss", $current_time, $name, $current_date);
            
            if ($update_stmt->execute()) {
                $message = "Logout time has been recorded successfully $name";
            } else {
                $message = "Error in updating logout time.";
            }
        } else {
            // Insert new record if no existing entry
            $insert_stmt = $conn->prepare("INSERT INTO attendance (Name, Department, Date, Time_in) VALUES (?, ?, ?, ?)");
            $insert_stmt->bind_param("ssss", $name, $department, $current_date, $current_time);
            
            if ($insert_stmt->execute()) {
                $message = "Login time has been recorded successfully $name";
            } else {
                $message = "Error in inserting new record.";
            }
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        $message = "An error occurred in the system. Please try again.";
    }
}

// Get all attendance records for display
$attendance_data = [];
$result = $conn->query("SELECT Name, Department, Date, Time_in, Time_out FROM attendance ORDER BY Date DESC, Time_in DESC");
if ($result) {
    $attendance_data = $result->fetch_all(MYSQLI_ASSOC);
}
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
  <?php if ($is_admin): ?>
    <a href="admin_dashboard.php" class="admin-link">Admin Dashboard</a>
  <?php endif; ?>
    </header>

    <section class="attendance-section">
      <form method="POST" action="attendance.php" id="attendance-form" autocomplete="off">
        <div class="form-group">
          <label for="Name">Name</label>
          <input type="text" id="Name" name="Name" placeholder="Enter Name" required />
        </div>
        
        <div class="form-group">
          <label for="Department">Department</label>
          <select id="Department" name="Department" required>
            <option value="">Select Department</option>
            <option value="Employee">Employee</option>
            <option value="Marketing">Marketing</option>
            <option value="Field Student">Field Student</option>
            <option value="Entrance">Entrance</option>
          </select>
        </div>

        <button type="submit" class="btn-primary">Mark Attendance</button>
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
      </div>
    </section>

    <footer style="text-align:center; padding: 20px; font-size: 14px; color: #777;">
      &copy; 2025 Attendance System. All rights reserved.
    </footer>
  </main>
</body>
</html>