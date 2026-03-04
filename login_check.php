<?php
session_start();
include("admin/database.php");

$type = $_GET['type'] ?? '';

if($type == 'logout') {
    // Clear any legacy persistent cookies
    setcookie("useremail", "", mktime(12,0,0,1, 1, 1970), "/"); 
    setcookie("user_id", "", mktime(12,0,0,1, 1, 1970), "/");
    session_destroy();
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include_once("admin/functions.php");
    
    // Phase A: CSRF Check
    $next = $_POST['next'] ?? ($_GET['next'] ?? '');
    if (!CSRF::check($_POST['csrf_token'] ?? '')) {
        $_SESSION['errors'] = "Session expired (CSRF). Please try again.";
        header("Location: login.php?next=" . urlencode($next));
        exit();
    }

    $postemail = strip_tags($_POST['useremail']);
    $password = $_POST['password'] ?? '';
    
    $stmt = $db->prepare("SELECT id, name, password_hash, role FROM `users` WHERE email = :email");
    $stmt->execute([':email' => $postemail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        // Success
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role']; // Unified Login
        $_SESSION['useremail'] = $postemail; // Legacy support
        
        // Persistent cookie authentication was removed for security (non-forgeable sessions only)
        
        header("Location: index.php");
        exit();
    } else {
        // Login failed
        echo "Login failed. Invalid email or password. <a href='login.php'>Try again</a>";
    }
} else {
    header("Location: index.php");
}
?>
