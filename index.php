<?php
session_start();
require 'auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: chat.php');
    exit;
}

$error = '';
$success = '';

// Handle login
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    $result = loginUser($username, $password);
    if ($result === true) {
        header('Location: chat.php');
        exit;
    } else {
        $error = $result;
    }
}

// Handle registration
if (isset($_POST['register'])) {
    $username = trim($_POST['reg_username']);
    $email = trim($_POST['reg_email']);
    $password = trim($_POST['reg_password']);
    $confirm_password = trim($_POST['reg_confirm_password']);
    
    if ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        $result = registerUser($username, $email, $password);
        if ($result === true) {
            $success = "Registration successful! Please login.";
        } else {
            $error = $result;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat App - Login/Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-tabs">
            <button class="tab-btn active" data-tab="login">Login</button>
            <button class="tab-btn" data-tab="register">Register</button>
        </div>
        
        <?php if ($error): ?>
            <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <div class="tab-content active" id="login-tab">
            <form method="POST">
                <input type="hidden" name="login" value="1">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn">Login</button>
            </form>
        </div>
        
        <div class="tab-content" id="register-tab">
            <form method="POST">
                <input type="hidden" name="register" value="1">
                <div class="form-group">
                    <label for="reg_username">Username</label>
                    <input type="text" id="reg_username" name="reg_username" required>
                </div>
                <div class="form-group">
                    <label for="reg_email">Email</label>
                    <input type="email" id="reg_email" name="reg_email" required>
                </div>
                <div class="form-group">
                    <label for="reg_password">Password</label>
                    <input type="password" id="reg_password" name="reg_password" required minlength="8">
                </div>
                <div class="form-group">
                    <label for="reg_confirm_password">Confirm Password</label>
                    <input type="password" id="reg_confirm_password" name="reg_confirm_password" required minlength="8">
                </div>
                <button type="submit" class="btn">Register</button>
            </form>
        </div>
    </div>

    <script>
        // Tab switching functionality
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                // Remove active class from all buttons and tabs
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
                
                // Add active class to clicked button
                btn.classList.add('active');
                
                // Show corresponding tab
                const tabId = btn.getAttribute('data-tab') + '-tab';
                document.getElementById(tabId).classList.add('active');
            });
        });
    </script>
</body>
</html>