<?php
include("functions.php");
// session_start(); handled by functions.php
include("database.php");

// Handle Logout
if(isset($_GET['logout'])) {
    unset($_SESSION['admin_user']);
    session_destroy();
    session_start();
    header("Location: login.php");
    exit();
}

// Redirect if already logged in
if(isset($_SESSION['admin_user'])) {
    header("Location: index.php");
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!CSRF::check($_POST['csrf_token'] ?? '')) {
        $error = "Session expired (CSRF). Please try again.";
    } elseif ($username && $password) {
        $stmt = $db->prepare("SELECT id, username, password_hash, role FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Success
            session_regenerate_id(true);
            $_SESSION['admin_user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ];
            
            // Log successful login? (Optional, requires function access or raw query)
            // We'll skip logging for now to keep this file self-contained or verify functions inclusion later.
            
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid Username or Password";
        }
    } else {
        $error = "Please enter both username and password";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login - Tournament</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="../css/all.min.css">
    <style>
        :root {
            --text-light: #f8fafc;
            --text-muted: #94a3b8;
            --accent-orange: #f97316;
        }
        body {
            background-color: #0f172a;
            color: #ecf0f1;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .login-wrapper {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
        }
        .login-card {
            background: #1e293b;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            width: 100%;
            max-width: 400px;
            text-align: center;
            border: 1px solid #334155;
        }
        h2 {
            margin-top: 0;
            color: #f97316; /* Accent Orange */
            margin-bottom: 20px;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            background: #0f172a;
            border: 1px solid #475569;
            color: white;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 1rem;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #f97316;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #f97316;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }
        button:hover {
            background: #ea580c;
        }
        .error {
            background: #ef4444;
            color: white;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .back-link {
            display: block;
            margin-top: 20px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
        }
        .back-link:hover {
            color: var(--text-light);
            text-decoration: underline;
        }
        /* Footer Styling */
        #footer-attribution {
            margin-top: auto;
            padding: 20px 0;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.75rem;
            background: transparent;
            opacity: 0.6;
            transition: opacity 0.2s;
            width: 100%;
            border-top: 1px solid rgba(255,255,255,0.05);
        }
        #footer-attribution:hover { opacity: 1; }
        #footer-attribution .footer-inner { max-width: 1200px; margin: 0 auto; padding: 0 20px; line-height: 1.6; }
        #footer-attribution a { color: var(--text-muted); text-decoration: none; transition: color 0.15s ease; }
        #footer-attribution a:hover { color: var(--accent-orange); text-decoration: underline; }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        <div style="font-size: 3rem; margin-bottom: 10px; color: #f97316;">
            <i class="fa-solid fa-user-shield"></i>
        </div>
        <h2>Admin Access</h2>
        
        <?php if($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="post">
        <?php echo CSRF::input(); ?>
            <input type="text" name="username" placeholder="Username" required autofocus>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Log In</button>
        </form>
        
        <a href="../index.php" class="back-link">&larr; Back to Main Site</a>
    </div>
</div>

<?php include('footer.php'); ?>
</body>
</html>



