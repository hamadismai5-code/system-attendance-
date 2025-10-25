<?php
session_start();
require_once 'config.php';

// Initialize variables
$error = '';
$success = '';
$lockout_time = 300; // 5 minutes

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        $error = "Invalid request. Please try again.";
    } else {
        $email = sanitizeInput($_POST['email']);

        if (!validateEmail($email)) {
            $error = "Please enter a valid email address.";
        } else {
            // Rate limiting check
            if (isset($_SESSION['reset_attempt_time']) && (time() - $_SESSION['reset_attempt_time'] < $lockout_time)) {
                $remaining_time = $lockout_time - (time() - $_SESSION['reset_attempt_time']);
                $error = "Please wait " . ceil($remaining_time / 60) . " more minute(s) before trying again.";
            } else {
            // Check if email exists
            $stmt = $conn->prepare("SELECT id, username, reset_expires FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($user_id, $username, $existing_expiry);
                $stmt->fetch();

                // Prevent spamming if a valid token already exists and hasn't expired
                if ($existing_expiry && strtotime($existing_expiry) > time()) {
                    // A valid token already exists. Show a generic message.
                    $success = "If the email exists in our system, a password reset link has been sent. Please check your inbox and spam folder.";
                } else {

                // Generate reset token (expires in 1 hour)
                $reset_token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 3600);

                // Store token in database
                $update_stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
                $update_stmt->bind_param("ssi", $reset_token, $expires, $user_id);

                if ($update_stmt->execute()) {
                    // In a real application, you would send an email here
                    $reset_link = BASE_URL . "/reset_password.php?token=" . $reset_token;
                    // For demo purposes, show the link. In production, this would be emailed.
                    $success_message = "A password reset link has been generated. In a real application, this would be sent to your email.";
                    $success_link = "<strong>Reset Link:</strong> <a href='" . htmlspecialchars($reset_link) . "'>" . htmlspecialchars($reset_link) . "</a>";
                    $success = $success_message . "<br>" . $success_link;
                } else {
                    $error = "Error generating reset token. Please try again.";
                }

                $update_stmt->close();
                }
                // Log the attempt time for rate limiting only when a valid user is found
                $_SESSION['reset_attempt_time'] = time(); 
            } else {
                // To prevent user enumeration via timing attacks, we show a generic error for invalid emails.
                $error = "Please enter a valid email address.";
            }
            $stmt->close();
        }
    }
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Forgot Password - Attendance System</title>
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
        color: #0a0a0aff;
        opacity: 0.8;
        pointer-events: none;
        transition: color 0.3s ease;
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
        echo "<p style='color:#ffcccc; text-align:center; background: rgba(255,0,0,0.2); padding: 10px; border-radius: 8px;'>$error</p>";
    }
    if (!empty($success)) {
        echo "<p style='color:#ccffcc; text-align:center; background: rgba(0,255,0,0.15); padding: 10px; border-radius: 8px;'>$success</p>";
    }
    ?>
    <form method="POST" action="" id="forgot-password-form">
      <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
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