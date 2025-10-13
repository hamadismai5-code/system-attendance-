<?php
session_start();
require_once 'config.php';

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
            $stmt = $conn->prepare("SELECT id, username, password, is_admin, department, login_attempts, locked_until FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows === 1) {
                $stmt->bind_result($user_id, $db_username, $hashed_password, $is_admin, $department, $db_login_attempts, $locked_until);
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
                    $_SESSION['department'] = $department;
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
                        $error = "Invalid username or password."; // Generic message
                    } else {
                        $lock_stmt = $conn->prepare("UPDATE users SET login_attempts = ? WHERE id = ?");
                        $lock_stmt->bind_param("ii", $new_attempts, $user_id);
                        $error = "Invalid username or password."; // Generic message
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
        margin: 30px 0;
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
        color: #000000ff;
        opacity: 0.8;
        pointer-events: none;
        transition: color 0.3s ease;
    }

    /* Password toggle icon styles */
    .input-box .toggle-password {
        cursor: pointer;
        pointer-events: auto;
        z-index: 4;
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

    .forget-password {
        margin-top: 12px;
        text-align: left;
        font-size: 14px;
        color: #eef0f1;
    }

    .forget-password a {
        color: #eef0f1;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s ease, text-decoration 0.3s ease;
    }

    .forget-password a:hover,
    .forget-password a:focus {
        color: #ffd700;
        text-decoration: underline;
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
        
        .forget-password, .wrapper p {
            font-size: 13px;
            margin-top: 20px;
        }
    }

    @media (min-width: 768px) and (max-width: 1024px) {
        .wrapper {
            max-width: 450px;
            padding: 35px 30px;
        }
    }
  </style>
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
        <input type="password" id="password" name="password" placeholder="Password" required autocomplete="current-password" />
        <i class='bx bx-hide toggle-password' id="togglePassword"></i>
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

    // Password visibility toggle
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    togglePassword.addEventListener('click', function() {
      // Toggle the type attribute
      const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordInput.setAttribute('type', type);
      
      // Toggle the icon
      this.classList.toggle('bx-hide');
      this.classList.toggle('bx-show');
    });
  </script>
</body>
</html>