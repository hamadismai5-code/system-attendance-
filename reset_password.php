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
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: poppins, sans-serif;
    }

   body{
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background: -moz-linear-gradient(45deg, #4c72ec, #1a1a1a);
    background: -webkit-linear-gradient(45deg, #4c72ec, #1a1a1a);
    background: linear-gradient(45deg, #4c72ec, #1a1a1a);
    background-repeat: no-repeat;
    background-attachment: fixed;
    background-size: cover;
    background-position: center;
    padding: 20px;
}

    .wrapper {
        position: relative;
        width: 100%;
        background: rgba(255, 255, 255, 0.1);
        border: 2px solid rgba(255, 255, 255, .2);
        box-shadow: 0 0 20px rgba(0, 0, 0, .2);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        max-width: 400px;
        margin: 0 auto;
        color: #fff;
        border-radius: 18px;
        padding: 30px 25px;
    }

    .wrapper::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: inherit;
        border-radius: inherit;
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        z-index: -1;
    }

    .wrapper h1 {
        font-size: 36px;
        text-align: center;
        margin-bottom: 10px;
    }

    .input-box {
        position: relative;
        background: transparent;
        width: 100%;
        height: 50px;
        margin: 25px 0;
    }

    .input-box input {
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.1);
        outline: none;
        border: 2px solid rgba(255, 255, 255, .2);
        border-radius: 40px;
        font-size: 16px;
        color: #fff;
        padding: 0 45px 0 20px;
        transition: all 0.3s ease;
    }

    .input-box input:hover {
        border-color: rgba(255, 255, 255, 0.4);
    }

    .input-box input:focus {
        border-color: #ffd700;
        box-shadow: 0 0 8px rgba(255, 215, 0, 0.4);
        background: rgba(255, 255, 255, 0.15);
    }

    .input-box input:focus + i {
        color: #ffd700;
    }

    .input-box input::placeholder {
        color: rgba(255, 255, 255, 0.7);
    }

    .input-box i {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 20px;
        color: #0f0f0fff;
        opacity: 0.8;
        pointer-events: none;
        transition: color 0.3s ease;
    }
    
    /* Password toggle icon styles */
    .input-box .toggle-password {
        cursor: pointer;
        pointer-events: auto;
    }

    .wrapper .btn {
        width: 100%;
        height: 45px;
        background: #fff;
        border: none;
        outline: none;
        border-radius: 40px;
        box-shadow: 0 0 10px rgba(0, 0, 0, .1);
        cursor: pointer;
        font-size: 16px;
        color: #333;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn:hover {
        background: #4c72ec;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    .wrapper p a {
        color: #eef0f1;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease, text-decoration 0.3s ease;
    }
    .wrapper p a:hover,
    .wrapper p a:focus {
        color: #ffd700;
        text-decoration: underline;
    }

    .password-strength {
      margin-top: 5px;
      font-size: 12px;
      padding-left: 20px;
      position: absolute;
      bottom: -20px;
    }
    .strength-weak { color: red; }
    .strength-medium { color: orange; }
    .strength-strong { color: green; }

    @media (max-width: 480px) {
        body {
            padding: 15px;
            align-items: flex-start;
            padding-top: 40px;
        }
        
        .wrapper {
            padding: 25px 20px;
            margin: 0;
            max-width: 100%;
        }
        
        .wrapper h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .input-box {
            height: 45px;
            margin: 20px 0;
        }
        
        .input-box input {
            font-size: 15px;
            padding: 0 40px 0 15px;
        }
        
        .input-box i {
            right: 15px;
            font-size: 18px;
        }
        
        .wrapper .btn {
            height: 45px;
            font-size: 15px;
        }
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <h1>Reset Password</h1>
    <?php 
    if (!empty($error)) {
        echo "<p style='color:#ffcccc; text-align:center; background: rgba(255,0,0,0.2); padding: 10px; border-radius: 8px; margin-bottom: 15px;'>$error</p>";
    }
    if (!empty($success)) {
        echo "<p style='color:#ccffcc; text-align:center; background: rgba(0,255,0,0.15); padding: 10px; border-radius: 8px; margin-bottom: 15px;'>$success</p>";
    }
    
    if ($valid_token && empty($success)) {
    ?>
    <form method="POST" action="reset_password.php?token=<?php echo htmlspecialchars($_GET['token']); ?>" id="reset-form">
      <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
      <div class="input-box">
        <input type="password" id="password" name="password" placeholder="New Password" required />
        <i class='bx bx-hide toggle-password' id="togglePassword"></i>
        <div class="password-strength" id="password-strength"></div>
      </div>
      <div class="input-box">
        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required />
        <i class='bx bx-hide toggle-password' id="toggleConfirmPassword"></i>
      </div>
      <button type="submit" class="btn">Reset Password</button>
    </form>
    <?php } ?>
    <p style="text-align: center;"><a href="login.php">Back to Login</a></p>
  </div>
  
  <script>
    // Password visibility toggle
    function setupPasswordToggle(toggleId, passwordId) {
        const toggle = document.getElementById(toggleId);
        const passwordInput = document.getElementById(passwordId);

        if (toggle && passwordInput) {
            toggle.addEventListener('click', function() {
                // Toggle the type attribute
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Toggle the icon
                this.classList.toggle('bx-hide');
                this.classList.toggle('bx-show');
            });
        }
    }
    setupPasswordToggle('togglePassword', 'password');
    setupPasswordToggle('toggleConfirmPassword', 'confirm_password');

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
      const confirmPassword = document.getElementById('confirm_password').value;
      
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