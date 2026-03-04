<?php
session_start();
include("admin/database.php");
include("admin/functions.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $pass = $_POST['password'];
    $confirm = $_POST['password_confirm'];

    // 1. Basic Validation
    if(empty($name) || empty($email) || empty($pass)) {
        $_SESSION['register_error'] = "All fields are required.";
        header("Location: register.php");
        exit();
    }

    if($pass !== $confirm) {
        $_SESSION['register_error'] = "Passwords do not match.";
        header("Location: register.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['register_error'] = "Invalid email format.";
        header("Location: register.php");
        exit();
    }

    try {
        // 2. Duplicate Check
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if($stmt->fetch()) {
            $_SESSION['register_error'] = "Email already registered.";
            header("Location: register.php");
            exit(); 
        }

        // 3. Register User
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $ins = $db->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
        
        if($ins->execute([$name, $email, $hash])) {
            $newUserId = $db->lastInsertId();
            
            // Login immediately
            session_regenerate_id(true);
            $_SESSION['user_id'] = $newUserId;
            $_SESSION['user_name'] = $name;
            $_SESSION['useremail'] = $email; // Maintain legacy compat
            
            // Set Cookie (REMOVED for Security)
            // setcookie('user_id', $newUserId, time() + (86400 * 30), "/");
            // setcookie('useremail', $email, time() + (86400 * 30), "/");

            header("Location: dashboard.php");
            exit();
        } else {
            $_SESSION['register_error'] = "Registration failed. Please try again.";
            header("Location: register.php");
            exit(); 
        }

    } catch (PDOException $e) {
        // Log real error server-side; never expose DB details to the user
        error_log("Registration PDOException: " . $e->getMessage());
        $_SESSION['register_error'] = "Registration failed due to a server error. Please try again.";
        header("Location: register.php");
        exit();
    }

} else {
    header("Location: register.php");
    exit();
}
?>
