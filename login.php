<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Andaa statement
    $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($hashed_password);

    if ($stmt->fetch()) {
        if (password_verify($password, $hashed_password)) {
            $_SESSION['username'] = $username;
            header("Location: attendance.php");
            exit();
        } else {
            $error = "Password is incorrect!";
        }
    } else {
        $error = "Username not found!";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login</title>
  <link rel="stylesheet" href="css/form1.css" />
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
  <div class="wrapper">
    <h1>Login</h1>
    <?php 
    if (isset($_GET['msg']) && $_GET['msg'] === 'registered') {
        echo "<p style='color:green;'>Registration successful! Please login.</p>";
    }
    if (isset($error)) {
        echo "<p style='color:red;'>$error</p>";
    }
    ?>
    <form method="POST" action="login.php">
      <div class="input-box">
        <input type="text" name="username" placeholder="Username" required />
        <i class='bx bxs-user'></i>
      </div>
      <div class="input-box">
        <input type="password" name="password" placeholder="Password" required />
        <i class='bx bxs-lock-alt'></i>
      </div>
      <button type="submit" class="btn">Login</button>
    </form>
    <p>Don't have an account? <a href="registration.php">Register here</a></p>
    <div class="forget-password">
      <a href="#">Forgot your password?</a>
    </div>
  </div>
  <script src="js/form1.js"></script>
</body>
</html>
