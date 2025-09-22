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

// Include admin header if any
include 'admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Panel - Users</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
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
      margin-left: 120px;
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
      border-bottom: 1px solid var(--border);
    }

    .admin-header h1 {
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--dark);
    }

    .user-info {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .user-info span {
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

    /* Cards & Forms */
    .user-form {
      background: var(--white);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 20px;
      margin-bottom: 25px;
    }

    .user-form h3 {
      font-weight: 700;
      color: var(--dark);
      margin-bottom: 15px;
      font-size: 1.2rem;
    }

    .user-form input, .user-form button {
      width: 100%;
      border-radius: 6px;
      padding: 10px 15px;
      border: 1px solid var(--border);
      margin-bottom: 10px;
      font-size: 1rem;
      transition: var(--transition);
      color: var(--dark);
    }

    .user-form label {
      display: flex;
      align-items: center;
      margin: 14px 0 8px 0;
      gap: 10px;
      font-weight: 600;
      color: var(--dark);
      font-size: 1rem;
      cursor: pointer;
      transition: color 0.2s;
      transition: var(--transition);

    }
    .user-form label:hover,
    .user-form label:focus-within {
      color: var(--primary);
    }

    .user-form button {
      background: var(--primary);
      color: var(--white);
      font-weight: 600;
      border: none;
      cursor: pointer;
      transition: var(--transition);
      margin-top: 10px;
    }

    .user-form button:hover {
      background: var(--primary-dark);
    }

    /* Table */
    .table-container {
      overflow-x: auto;
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      background: var(--white);
      padding: 15px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    thead th {
      font-size: 0.8rem;
      text-transform: uppercase;
      color: var(--white);
      font-weight: 700;
      background: var(--primary);
      letter-spacing: 1px;
      border-bottom: 2px solid var(--primary-dark);
    }

    tbody td {
      padding: 12px 15px;
      border-bottom: 1px solid var(--border);
    }

    tbody tr:last-child td {
      border-bottom: none;
    }

    /* Buttons */
    .btn-edit, .btn-delete {
      padding: 6px 12px;
      border-radius: 6px;
      text-decoration: none;
      font-size: 0.85rem;
      font-weight: 500;
      transition: var(--transition);
      display: inline-block;
    }

    .btn-edit {
      background: var(--primary);
      color: var(--white);
      margin-right: 5px;
    }

    .btn-edit:hover {
      background: var(--primary-dark);
    }

    .btn-delete {
      background: var(--danger);
      color: var(--white);
    }

    .btn-delete:hover {
      background: #dc2626;
    }

    /* Alerts */
    .alert {
      padding: 12px 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-weight: 500;
    }

    .alert-success {
      background: #ecfdf5;
      color: #065f46;
      border: 1px solid #a7f3d0;
    }

    .alert-danger {
      background: #fef2f2;
      color: #991b1b;
      border: 1px solid #fecaca;
    }

    /* Role badges */
    .role-badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 999px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .role-admin {
      background: #fffbeb;
      color: #92400e;
      border: 1px solid #fde68a;
    }

    .role-user {
      background: #ecfdf5;
      color: #065f46;
      border: 1px solid #a7f3d0;
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
      
      .user-info {
        margin-top: 10px;
      }
      
      .user-form {
        padding: 15px;
      }
    }
  </style>
</head>
<body>
  <div class="admin-container">
    <main class="admin-content">
      <header class="admin-header">
        <h1>User Management</h1>
        <div class="user-info">
          <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            
        </div>
      </header>
      
      <!-- Notifications -->
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
        <div class="table-container">
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
                <td>
                  <span class="role-badge <?= $user['is_admin'] ? 'role-admin' : 'role-user' ?>">
                    <?= $user['is_admin'] ? 'Admin' : 'User' ?>
                  </span>
                </td>
                <td>
                  <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn-edit">Edit</a>
                  <a href="users.php?delete=<?= $user['id'] ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
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