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

// Chukua idara zote
$departments = [];
$result = $conn->query("SELECT id, name FROM departments");
if ($result) {
    $departments = $result->fetch_all(MYSQLI_ASSOC);
}

// Ongeza idara mpya
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_department'])) {
    $name = $_POST['name'];

    $stmt = $conn->prepare("INSERT INTO departments (name) VALUES (?)");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    header("Location: departments.php");
    exit();
}

// Futa idara
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM departments WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: departments.php");
    exit();
}
// Include admin header if any
include 'admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
 <!-- Badilisha sehemu ya <head> kuwa kama hii -->
<head>
  <meta charset="UTF-8">
  <title>Admin Panel - Departments</title>
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
    
    /* Department Management */
    .department-management {
      display: grid;
      gap: 20px;
    }
    
    .department-form {
      background: var(--white);
      padding: 20px;
      border-radius: var(--radius);
      box-shadow: var(--shadow);
    }
    
    .department-form h3 {
      font-size: 1.2rem;
      font-weight: 600;
      color: var(--dark);
      margin-bottom: 15px;
    }
    
    .department-form input {
      width: 100%;
      padding: 10px 15px;
      border: 1px solid #e5e7eb;
      border-radius: 6px;
      margin-bottom: 10px;
    }
    
    .department-form button {
      background: var(--primary);
      color: white;
      border: none;
      padding: 10px 15px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      transition: var(--transition);
    }
    
    .department-form button:hover {
      background: var(--primary-dark);
    }
    
    .department-table {
      width: 100%;
      border-collapse: collapse;
      background: var(--white);
      border-radius: 5px;
      box-shadow: var(--shadow);
      overflow: hidden;
    }
    
    .department-table th, 
    .department-table td {
      text-align: center;
      padding: 12px 15px;
      border-bottom: 1px solid #e5e7eb;
    }
    
   .department-table th {
      font-size: 0.8rem;
      text-transform: uppercase;
      color: var(--white);
      font-weight: 700;
      background: var(--primary);
      letter-spacing: 1px;
      border-bottom: 2px solid var(--primary-dark);
    }
    
    .department-table tr:last-child td {
      border-bottom: none;
    }
    
    .department-table a {
      color: var(--primary);
      text-decoration: none;
      margin-right: 10px;
    }
    
    .department-table a:hover {
      text-decoration: underline;
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
    <main class="admin-content">
      <header class="admin-header">
        <h1>Department Management</h1>
        <div class="admin-user">
         
        </div>
      </header>

      <div class="department-management">
        <!-- Fomu ya kuongeza idara -->
        <form method="POST" class="department-form">
            <h3>Add New Department</h3>
            <input type="text" name="name" placeholder="Department Name" required>
            <button type="submit" name="add_department">Add Department</button>
        </form>

        <!-- Orodha ya idara -->
        <table class="department-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($departments as $dept): ?>
                <tr>
                    <td><?= $dept['id'] ?></td>
                    <td><?= htmlspecialchars($dept['name']) ?></td>
                    <td>
                        <a href="edit_department.php?id=<?= $dept['id'] ?>">Edit</a> |
                        <a href="departments.php?delete=<?= $dept['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
      </div>
    </main>
  </div>
</body>
</html>