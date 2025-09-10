<?php
// Enhanced attendance.php
include 'session_check.php';
include 'config.php';

// Verify user is logged in
validateSession();

// Process form data with validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'])) {
        $message = "Invalid form submission.";
    } else {
        $name = sanitizeInput($_POST['Name']);
        $department = sanitizeInput($_POST['Department']);
        $current_date = date('Y-m-d');
        $current_time = date('H:i:s');
        
        // Input validation
        if (empty($name) || empty($department)) {
            $message = "Please fill all required fields.";
        } else if (!in_array($department, ['Employee', 'Marketing', 'Field Student', 'Entrance'])) {
            $message = "Invalid department selected.";
        } else {
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
                    $update_stmt->close();
                } else {
                    // Insert new record if no existing entry
                    $insert_stmt = $conn->prepare("INSERT INTO attendance (Name, Department, Date, Time_in) VALUES (?, ?, ?, ?)");
                    $insert_stmt->bind_param("ssss", $name, $department, $current_date, $current_time);
                    
                    if ($insert_stmt->execute()) {
                        $message = "Login time has been recorded successfully $name";
                    } else {
                        $message = "Error in inserting new record.";
                    }
                    $insert_stmt->close();
                }
                $check_stmt->close();
            } catch (Exception $e) {
                error_log($e->getMessage());
                $message = "An error occurred in the system. Please try again.";
            }
        }
    }
}

// Get all attendance records for display with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$attendance_data = [];
$count_result = $conn->query("SELECT COUNT(*) as total FROM attendance");
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

$result = $conn->query("SELECT Name, Department, Date, Time_in, Time_out FROM attendance ORDER BY Date DESC, Time_in DESC LIMIT $limit OFFSET $offset");
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
      <?php if ($_SESSION['is_admin']): ?>
        <a href="admin_dashboard.php" class="admin-link">Admin Dashboard</a>
      <?php endif; ?>
    </header>

    <section class="attendance-section">
      <form method="POST" action="attendance.php" id="attendance-form" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <div class="form-group">
          <label for="Name">Name</label>
          <input type="text" id="Name" name="Name" placeholder="Enter Name" required value="<?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : ''; ?>" />
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