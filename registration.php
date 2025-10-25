<?php
session_start();
require_once 'config.php';

// Set CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error = "Invalid form submission. Please try again.";
    } else {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $department_name = $_POST['department']; // kutoka select

    // Check username or email
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $error = "Username or Email already exists!";
    } else {
        // Get department ID from name
        $dept_stmt = $conn->prepare("SELECT id FROM departments WHERE name = ?");
        $dept_stmt->bind_param("s", $department_name);
        $dept_stmt->execute();
        $dept_stmt->bind_result($department_id);
        $dept_stmt->fetch();
        $dept_stmt->close();

        if (!$department_id) {
            $error = "Invalid department selected.";
        } else {
            // Insert user
            $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password, department) VALUES (?, ?, ?, ?)");
            $insert_stmt->bind_param("sssi", $username, $email, $password, $department_id);

            if ($insert_stmt->execute()) {
                header("Location: login.php?msg=registered");
                exit();
            } else {
                $error = "Failed to register user.";
            }
            $insert_stmt->close();
        }
    }
    }
    $stmt->close();
}

// Fetch departments for the dropdown
$departments = [];
$result = $conn->query("SELECT name FROM departments ORDER BY name ASC");
if ($result) {
    $departments = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Register</title>
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

.wrapper{
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

/* Added pseudo-element for Chrome compatibility */
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

.wrapper h1{
    font-size: 36px;
    text-align: center;
    margin-bottom: 10px;
}

.wrapper .input-box{
    width: 100%;
    height: 50px;
    margin: 20px 0;
    position: relative;
}

.input-box input{
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

/* UNIVERSAL SELECT STYLES FOR ALL BROWSERS */
.select-box {
    position: relative;
    width: 100%;
    height: 50px;
    margin: 20px 0;
}

.select-box select {
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.1);
    border: 2px solid rgba(255, 255, 255, .2);
    border-radius: 40px;
    font-size: 16px;
    color: #fff;
    padding: 0 45px 0 20px;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    cursor: pointer;
    outline: none;
    transition: all 0.3s ease;
}

.select-box select:hover {
    border-color: rgba(255, 255, 255, 0.4);
}

/* Custom arrow using border method - works everywhere */
.select-box::after {
    content: '';
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    width: 0;
    height: 0;
    border-left: 5px solid transparent;
    border-right: 5px solid transparent;
    border-top: 5px solid #fff;
    pointer-events: none;
}

/* Focus states for all browsers */
.input-box input:focus {
    border-color: #ffd700;
    box-shadow: 0 0 8px rgba(255, 215, 0, 0.4);
    background: rgba(255, 255, 255, 0.15);
}

.select-box select:focus {
    border-color: #ffd700;
    box-shadow: 0 0 8px rgba(255, 215, 0, 0.4);
    background: rgba(255, 255, 255, 0.15);
}

/* Icon styles - consistent across all elements */
.input-box i,
.select-box i {
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 20px;
    color: #0c0c0cff;
    opacity: 0.8;
    pointer-events: none;
    transition: color 0.3s ease;
    z-index: 3;
}

.input-box input:focus + i,
.select-box select:focus + i {
    color: #ffd700;
    opacity: 1;
}

.input-box input::placeholder{
    color: rgba(255, 255, 255, 0.7);
}

/* Password toggle icon styles */
.input-box .toggle-password {
    cursor: pointer;
    pointer-events: auto;
}

/* Select placeholder styling */
.select-box select:invalid {
    color: rgba(255, 255, 255, 0.7);
}

.select-box select option {
    background: #4c72ec;
    color: #fff;
    padding: 12px;
    font-size: 15px;
    border: none;
}

/* Hover effect for options in some browsers */
.select-box select option:hover {
    background: #3a5fcf;
}

/* Button styles */
.wrapper .btn{
    width: 100%;
    height: 50px;
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
    margin-top: 10px;
}

.btn:hover{
    background: #4c72ec;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

.btn:active{
    transform: translateY(0);
}

.Register-link {
    margin-top: 25px;
    text-align: center;
    font-size: 14px;
    color: #faf6f6;
}

.Register-link a {
    color: #fafcfd;
    text-decoration: none;
    font-weight: bold;
    transition: color 0.3s ease;
}

.Register-link a:hover {
    color: #ffd700;
    text-decoration: underline;
}

/* MOBILE FIRST RESPONSIVE DESIGN */
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
    
    .input-box,
    .select-box {
        height: 45px;
        margin: 15px 0;
    }
    
    .input-box input,
    .select-box select {
        font-size: 15px;
        padding: 0 40px 0 15px;
    }
    
    .input-box i,
    .select-box i {
        right: 15px;
        font-size: 18px;
    }
    
    .select-box::after {
        right: 15px;
        border-left: 4px solid transparent;
        border-right: 4px solid transparent;
        border-top: 4px solid #fff;
    }
    
    .wrapper .btn {
        height: 45px;
        font-size: 15px;
    }
    
    .Register-link {
        font-size: 13px;
        margin-top: 20px;
    }
}

/* Tablet devices */
@media (min-width: 768px) and (max-width: 1024px) {
    .wrapper {
        max-width: 450px;
        padding: 35px 30px;
    }
}

/* Firefox specific optimizations */
@-moz-document url-prefix() {
    .select-box select {
        background: rgba(0, 0, 0, 0.3);
        text-indent: 0.01px;
    }
    
    .select-box select option {
        background: #4c72ec;
    }
}

/* Safari specific optimizations */
@media not all and (min-resolution:.001dpcm) { 
    @supports (-webkit-appearance:none) {
        .select-box select {
            background: rgba(0, 0, 0, 0.25);
        }
    }
}

/* High contrast support for accessibility */
@media (prefers-contrast: high) {
    .input-box input,
    .select-box select {
        border-width: 3px;
        border-color: #fff;
    }
    
    .input-box input:focus,
    .select-box select:focus {
        border-color: #ffd700;
        box-shadow: 0 0 10px rgba(255, 215, 0, 0.8);
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .input-box input,
    .select-box select,
    .btn,
    .input-box i,
    .select-box i {
        transition: none;
    }
    
    .btn:hover {
        transform: none;
    }
}
</style>
</head>
<body>
<div class="wrapper">
    <h1>Register</h1>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <div class="input-box">
            <input type="text" name="username" placeholder="Username" required />
            <i class='bx bxs-user'></i>
        </div>

        <div class="input-box">
            <input type="email" name="email" placeholder="Email" required />
            <i class='bx bxs-envelope'></i>
        </div>

        <div class="input-box">
            <input type="password" id="password" name="password" placeholder="Password" required />
            <i class='bx bx-hide toggle-password' id="togglePassword"></i>
        </div>

        <!-- CHANGED: Use select-box class instead of input-box for department -->
        <div class="select-box">
            <select name="department" required>
                <option value="" disabled selected>Select Department</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo htmlspecialchars($dept['name']); ?>">
                        <?php echo htmlspecialchars($dept['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <i class='bx bxs-briefcase'></i>
        </div>

        <button type="submit" class="btn">Register</button>
    </form>

    <p class="Register-link">Already have an account? <a href="login.php">Login here</a></p>
</div>
<script>
// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const username = document.querySelector('input[name="username"]').value.trim();
    const email = document.querySelector('input[name="email"]').value.trim();
    const password = document.querySelector('input[name="password"]').value;
    const department = document.querySelector('select[name="department"]').value;
    
    if (!username || !email || !password || !department) {
        e.preventDefault();
        alert('Please fill in all fields');
        return false;
    }
    
    if (username.length < 3) {
        e.preventDefault();
        alert('Username must be at least 3 characters long');
        return false;
    }
    
    if (password.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long');
        return false;
    }
    
    // Basic email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        e.preventDefault();
        alert('Please enter a valid email address');
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