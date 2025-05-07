<?php
require_once 'db.php'; // Make sure this is at the top

function registerUser($username, $email, $password) {
    global $pdo; // Now $pdo will be available
    
    // Validate input
    if (empty($username) || empty($email) || empty($password)) {
        return "All fields are required";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Invalid email format";
    }
    
    if (strlen($password) < 8) {
        return "Password must be at least 8 characters";
    }
    
    try {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            return "Username or email already exists";
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        if ($stmt->execute([$username, $email, $hashedPassword])) {
            echo "Successfully instered!";
            return true;

        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return "Registration failed. Please try again.";
    }
    
    return "Registration failed. Please try again.";
}
function loginUser($username, $password) {
    global $pdo;
    
    try {
        // Find user by username or email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Password is correct, start session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['logged_in'] = true;
            
            // Update last login time (only if column exists)
            try {
                $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")
                    ->execute([$user['id']]);
            } catch (PDOException $e) {
                // Silently fail if last_login column doesn't exist
                error_log("Note: last_login update failed - " . $e->getMessage());
            }
            
            return true;
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
    }
    
    return "Invalid username or password";
}

function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function logoutUser() {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
}
?>