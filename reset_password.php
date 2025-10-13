<?php
session_start();
require_once 'config.php';

$error = '';
$success = '';
$valid_token = false;

if (isset($_GET['token'])) {
    $token = sanitizeInput($_GET['token']);
    
    // Check if token is valid and not expired
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows === 1) {
        $valid_token = true;
        $stmt->bind_result($user_id, $username);
        $stmt->fetch();
        
        // Process password reset
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validateCsrfToken($_POST['csrf_token'])) {
                $error = "Invalid request. Please try again.";
            } elseif (empty($_POST['password']) || empty($_POST['confirm_password'])) {
                $error = "Please fill out both password fields.";
            } else {
                $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            if ($password !== $confirm_password) {
                $error = "Passwords do not match.";
            } elseif (!validatePassword($password)) {
                $error = "Password must be at least 8 characters long and include uppercase, lowercase, and numbers.";
            } else {
                // Hash new password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Update password and clear reset token
                $update_stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL, login_attempts = 0 WHERE id = ?");
                $update_stmt->bind_param("si", $hashed_password, $user_id);
                
                if ($update_stmt->execute()) {
                    $success = "Password reset successfully. You can now <a href='login.php'>login</a> with your new password.";
                    $valid_token = false; // Token is now used
                } else {
                    $error = "Error resetting password. Please try again.";
                }
                
                $update_stmt->close();
            }
            }
        }
    } else {
        $error = "Invalid or expired reset token.";
    }
    
    $stmt->close();
} else {
    $error = "No reset token provided.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Reset Password - Attendance System</title>
  <link rel="stylesheet" href="css/form1.css" />
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <style>
    .password-strength {
      margin-top: 5px;
      font-size: 12px;
    }
    .strength-weak { color: red; }
    .strength-medium { color: orange; }
    .strength-strong { color: green; }
  </style>
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
    
    if ($valid_token && empty($success)) {
    ?>
    <form method="POST" action="reset_password.php?token=<?php echo htmlspecialchars($_GET['token']); ?>" id="reset-form">
      <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
      <div class="input-box">
        <input type="password" name="password" id="password" placeholder="New Password" required />
        <i class='bx bxs-lock-alt'></i>
        <div class="password-strength" id="password-strength"></div>
      </div>
      <div class="input-box">
        <input type="password" name="confirm_password" placeholder="Confirm Password" required />
        <i class='bx bxs-lock-alt'></i>
      </div>
      <button type="submit" class="btn">Reset Password</button>
    </form>
    <?php } ?>
    <p style="text-align: center;"><a href="login.php">Back to Login</a></p>
  </div>
  
  <script>
    // Password strength meter
    document.getElementById('password').addEventListener('input', function() {
      const password = this.value;
      const strengthText = document.getElementById('password-strength');
      
      if (password.length === 0) {
        strengthText.textContent = '';
        return;
      }
      
      // Simple strength check
      let strength = 0;
      if (password.length >= 8) strength++;
      if (/[A-Z]/.test(password)) strength++;
      if (/[a-z]/.test(password)) strength++;
      if (/[0-9]/.test(password)) strength++;
      if (/[^A-Za-z0-9]/.test(password)) strength++;
      
      let strengthValue = '';
      let strengthClass = '';
      
      if (strength < 3) {
        strengthValue = 'Weak';
        strengthClass = 'strength-weak';
      } else if (strength < 5) {
        strengthValue = 'Medium';
        strengthClass = 'strength-medium';
      } else {
        strengthValue = 'Strong';
        strengthClass = 'strength-strong';
      }
      
      strengthText.textContent = 'Strength: ' + strengthValue;
      strengthText.className = 'password-strength ' + strengthClass;
    });
    
    // Form validation
    document.getElementById('reset-form').addEventListener('submit', function(e) {
      const password = document.getElementById('password').value;
      const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
      
      if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match');
        return false;
      }
      
      if (password.length < 8) {
        e.preventDefault();
        alert('Password must be at least 8 characters long');
        return false;
      }
      
      return true;
    });
  </script>
</body>
</html>