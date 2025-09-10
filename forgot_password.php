<?php
session_start();
include 'config.php';

// Initialize variables
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    
    if (!validateEmail($email)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($user_id, $username);
            $stmt->fetch();
            
            // Generate reset token (expires in 1 hour)
            $reset_token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600);
            
            // Store token in database
            $update_stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $update_stmt->bind_param("ssi", $reset_token, $expires, $user_id);
            
            if ($update_stmt->execute()) {
                // In a real application, you would send an email here
                // For demo purposes, we'll show the reset link
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $reset_token;
                $success = "Password reset link: <a href='$reset_link'>$reset_link</a> (This would be sent via email in production)";
            } else {
                $error = "Error generating reset token. Please try again.";
            }
            
            $update_stmt->close();
        } else {
            // Don't reveal whether email exists or not
            $success = "If the email exists in our system, a password reset link has been sent.";
        }
        
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Forgot Password - Attendance System</title>
  <link rel="stylesheet" href="css/form1.css" />
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
  <div class="wrapper">
    <h1>Reset Password</h1>
    <?php 
    if (!empty($error)) {
        echo "<p style='color:red; text-align:center;'>$error</p>";
    }
    if (!empty($success)) {
        echo "<p style='color:green; text-align:center;'>$success</p>";
    }
    ?>
    <form method="POST" action="forgot_password.php">
      <div class="input-box">
        <input type="email" name="email" placeholder="Enter your email" required />
        <i class='bx bxs-envelope'></i>
      </div>
      <button type="submit" class="btn">Send Reset Link</button>
    </form>
    <p style="text-align: center;"><a href="login.php">Back to Login</a></p>
  </div>
</body>
</html>