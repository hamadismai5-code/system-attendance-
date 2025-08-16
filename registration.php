<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $error = "Username already exists!";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $password);
        if ($stmt->execute()) {
            header("Location: login.php?msg=registered");
            exit();
        } else {
            $error = "Failed to register user.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Register</title>
  <link rel="stylesheet" href="css/register.css" />
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
  <div class="wrapper">
    <h1>Register</h1>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="POST" action="registration.php">
      <div class="input-box">
        <input type="text" name="username" placeholder="Username" required />
        <i class='bx bxs-user'></i>
      </div>
      <div class="input-box">
        <input type="email" name="email" placeholder="Email" required />
        <i class='bx bxs-envelope'></i>
      </div>
      <div class="input-box">
        <input type="password" name="password" placeholder="Password" required />
        <i class='bx bxs-lock-alt'></i>
      </div>
      <button type="submit" class="btn">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login here</a></p>
  </div>
  <script src="js/form1.js"></script>
</body>
</html>
