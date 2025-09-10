<?php
session_start();
include 'config.php';

// Set CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Rate limiting for login attempts
$max_attempts = 5;
$lockout_time = 300; // 5 minutes

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error = "Invalid form submission. Please try again.";
    } else {
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];
        
        // Check if user is temporarily locked out
        if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= $max_attempts) {
            if (time() - $_SESSION['last_login_attempt'] < $lockout_time) {
                $remaining_time = $lockout_time - (time() - $_SESSION['last_login_attempt']);
                $error = "Too many failed attempts. Please try again in " . ceil($remaining_time / 60) . " minutes.";
            } else {
                // Reset attempts after lockout time
                unset($_SESSION['login_attempts']);
                unset($_SESSION['last_login_attempt']);
            }
        }
        
        if (!isset($error)) {
            // Prepared statement to check user
            $stmt = $conn->prepare("SELECT id, username, password, is_admin, login_attempts, locked_until FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows === 1) {
                $stmt->bind_result($user_id, $db_username, $hashed_password, $is_admin, $db_login_attempts, $locked_until);
                $stmt->fetch();
                
                // Check if account is locked in database
                if ($locked_until && strtotime($locked_until) > time()) {
                    $error = "Account temporarily locked. Please try again later.";
                } else if (password_verify($password, $hashed_password)) {
                    // Successful login - reset attempts
                    $reset_stmt = $conn->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL WHERE id = ?");
                    $reset_stmt->bind_param("i", $user_id);
                    $reset_stmt->execute();
                    $reset_stmt->close();
                    
                    // Set session
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $db_username;
                    $_SESSION['is_admin'] = $is_admin;
                    $_SESSION['login_time'] = time();
                    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
                    
                    // Regenerate session ID to prevent fixation
                    session_regenerate_id(true);
                    
                    // Reset login attempts in session
                    unset($_SESSION['login_attempts']);
                    unset($_SESSION['last_login_attempt']);
                    
                    // Check user role
                    if ($is_admin == 1) {
                        header("Location: admin_dashboard.php");
                        exit();
                    } else {
                        header("Location: attendance.php");
                        exit();
                    }
                } else {
                    // Failed login - increment attempts
                    $new_attempts = $db_login_attempts + 1;
                    
                    if ($new_attempts >= $max_attempts) {
                        // Lock account for 5 minutes
                        $lock_until = date('Y-m-d H:i:s', time() + $lockout_time);
                        $lock_stmt = $conn->prepare("UPDATE users SET login_attempts = ?, locked_until = ? WHERE id = ?");
                        $lock_stmt->bind_param("isi", $new_attempts, $lock_until, $user_id);
                        $error = "Too many failed attempts. Account locked for 5 minutes.";
                    } else {
                        $lock_stmt = $conn->prepare("UPDATE users SET login_attempts = ? WHERE id = ?");
                        $lock_stmt->bind_param("ii", $new_attempts, $user_id);
                        $remaining = $max_attempts - $new_attempts;
                        $error = "Invalid password. $remaining attempts remaining.";
                    }
                    
                    $lock_stmt->execute();
                    $lock_stmt->close();
                    
                    // Update session attempts
                    $_SESSION['login_attempts'] = $new_attempts;
                    $_SESSION['last_login_attempt'] = time();
                }
            } else {
                // Username not found - but don't reveal that
                $error = "Invalid username or password";
                
                // Still track attempts in session to prevent username enumeration
                if (!isset($_SESSION['login_attempts'])) {
                    $_SESSION['login_attempts'] = 1;
                } else {
                    $_SESSION['login_attempts']++;
                }
                $_SESSION['last_login_attempt'] = time();
                
                // Simulate processing time to prevent timing attacks
                usleep(rand(100000, 2000000)); // 0.1-2 seconds delay
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login - Attendance System</title>
  <link rel="stylesheet" href="css/form1.css" />
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
  <div class="wrapper">
    <h1>Login</h1>
    <?php 
    if (isset($_GET['msg']) && $_GET['msg'] === 'registered') {
        echo "<p style='color:green; text-align:center;'>Registration successful! Please login.</p>";
    }
    if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
        echo "<p style='color:red; text-align:center;'>Session expired. Please login again.</p>";
    }
    if (isset($error)) {
        echo "<p style='color:red; text-align:center;'>$error</p>";
    }
    ?>
    <form method="POST" action="login.php" id="login-form">
      <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
      <div class="input-box">
        <input type="text" name="username" placeholder="Username" required 
               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
               autocomplete="username" />
        <i class='bx bxs-user'></i>
      </div>
      <div class="input-box">
        <input type="password" name="password" placeholder="Password" required autocomplete="current-password" />
        <i class='bx bxs-lock-alt'></i>
      </div>
      <button type="submit" class="btn">Login</button>
    </form>
    <p style="text-align: center;">Don't have an account? <a href="registration.php">Register here</a></p>
    <div class="forget-password" style="text-align: center;">
      <a href="forgot_password.php">Forgot your password?</a>
    </div>
  </div>
  <script>
    // Simple form validation
    document.getElementById('login-form').addEventListener('submit', function(e) {
      const username = document.querySelector('input[name="username"]').value.trim();
      const password = document.querySelector('input[name="password"]').value;
      
      if (!username || !password) {
        e.preventDefault();
        alert('Please fill in all fields');
        return false;
      }
      
      if (username.length < 3) {
        e.preventDefault();
        alert('Username must be at least 3 characters long');
        return false;
      }
      
      return true;
    });
  </script>
</body>
</html>