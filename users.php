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

// Chukua watumiaji wote
$users = [];
$result = $conn->query("SELECT id, username, email, is_admin FROM users");
if ($result) {
    $users = $result->fetch_all(MYSQLI_ASSOC);
}

// Ongeza mtumiaji mpya
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $username, $email, $password, $is_admin);
    $stmt->execute();
    header("Location: users.php");
    exit();
}

// Futa mtumiaji
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Anza transaction
    $conn->begin_transaction();
    
    try {
        // 1. Futa rekodi za attendance za mtumiaji
        $stmt = $conn->prepare("DELETE FROM attendance WHERE name IN (SELECT username FROM users WHERE id = ?)");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // 2. Futa mtumiaji
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // Kamiliisha transaction
        $conn->commit();
        
        $_SESSION['message'] = "User and their attendance records deleted successfully";
    } catch (Exception $e) {
        // Rejesha transaction kama kuna hitilafu
        $conn->rollback();
        $_SESSION['error'] = "Failed to delete user: " . $e->getMessage();
    }
    
    header("Location: users.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<!-- Badilisha sehemu ya <head> kuwa kama hii -->
<head>
  <meta charset="UTF-8">
  <title>Admin Panel - Users</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="css/admin.css">
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
      --shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
      --radius: 12px;
      --transition: all 0.3s ease;
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
      background: var(--dark);
      color: var(--white);
      position: fixed;
      height: 100vh;
      padding-top: 20px;
      transition: var(--transition);
      z-index: 100;
      overflow-y: auto;
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
    
    .admin-sidebar ul li a:hover {
      background: rgba(255, 255, 255, 0.1);
      color: var(--white);
    }
    
    .admin-sidebar ul li.active a {
      background: var(--primary);
      color: var(--white);
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
    
    /* User Management */
    .user-management {
      display: grid;
      gap: 20px;
    }
    
    .user-form {
      background: var(--white);
      padding: 20px;
      border-radius: var(--radius);
      box-shadow: var(--shadow);
    }
    
    .user-form h3 {
      font-size: 1.2rem;
      font-weight: 600;
      color: var(--dark);
      margin-bottom: 15px;
    }
    
    .user-form input {
      width: 100%;
      padding: 10px 15px;
      border: 1px solid #3d3e3fff;
      border-radius: 6px;
      margin-bottom: 10px;
    }
    
    .user-form label {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 15px;
      cursor: pointer;
    }
    
    .user-form button {
      background: var(--primary);
      color: white;
      border: none;
      padding: 10px 15px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      transition: var(--transition);
    }
    
    .user-form button:hover {
      background: var(--primary-dark);
    }
    
    .table-responsive {
      overflow-x: auto;
    }
    
    .user-table {
      width: 100%;
      border-collapse: collapse;
      background: var(--white);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      overflow: hidden;
    }
    
    .user-table th, 
    .user-table td {
      text-align: left;
      padding: 12px 15px;
      border-bottom: 1px solid #e5e7eb;
    }
    
    .user-table th {
      font-size: 0.8rem;
      text-transform: uppercase;
      color: var(--gray);
      font-weight: 600;
      background: var(--light);
    }
    
    .user-table tr:last-child td {
      border-bottom: none;
    }
    
    .btn-edit {
      color: var(--primary);
      text-decoration: none;
      margin-right: 10px;
    }
    
    .btn-delete {
      color: var(--danger);
      text-decoration: none;
    }
    
    .btn-edit:hover, 
    .btn-delete:hover {
      text-decoration: underline;
    }
    
    .alert {
      padding: 15px;
      margin-bottom: 20px;
      border-radius: var(--radius);
    }
    
    .alert-success {
      background: #d1fae5;
      color: #065f46;
    }
    
    .alert-danger {
      background: #fee2e2;
      color: #b91c1c;
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
  <div class="admin-container">
    <aside class="admin-sidebar">
      <div class="admin-logo">
        <h2>Admin Panel</h2>
      </div>
      <ul class="admin-menu">
        <li><a href="admin_dashboard.php"><i class='bx bxs-dashboard'></i> Dashboard</a></li>
        <li><a href="attendance.php"><i class='bx bxs-time'></i> Attendance</a></li>
        <li class="active"><a href="users.php"><i class='bx bxs-user'></i> Users</a></li>
        <li><a href="departments.php"><i class='bx bxs-building'></i> Departments</a></li>
        <li><a href="reports.php"><i class='bx bxs-report'></i> Reports</a></li>
        <li><a href="analytics.php"><i class='bx bxs-analyse'></i> Analytics</a></li>
        <li><a href="logout.php"><i class='bx bxs-log-out'></i> Logout</a></li>
      </ul>
    </aside>

    <main class="admin-content">
      <header class="admin-header">
        <h1>User Management</h1>
        <div class="admin-user">
          <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
        </div>
      </header>
      <!-- Sehemu ya juu kabla ya form -->
<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>
      <div class="user-management">
        <!-- Fomu ya kuongeza mtumiaji -->
        <form method="POST" class="user-form">
          <h3>Add New User</h3>
          <input type="text" name="username" placeholder="Username" required>
          <input type="email" name="email" placeholder="Email" required>
          <input type="password" name="password" placeholder="Password" required>
          <label>
            <input type="checkbox" name="is_admin"> Admin User
          </label>
          <button type="submit" name="add_user" class="btn-primary">Add User</button>
        </form>

        <!-- Orodha ya watumiaji -->
        <div class="table-responsive">
          <table class="user-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $user): ?>
              <tr>
                <td><?= $user['id'] ?></td>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= $user['is_admin'] ? 'Admin' : 'User' ?></td>
                <td>
                  <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn-edit">Edit</a>
                  <a href="users.php?delete=<?= $user['id'] ?>" class="btn-delete" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</body>
</html>