<?php
session_start();
include("admin/database.php");
include("admin/functions.php");
include("header.php");

$msg = "";
$msgType = "";
$validToken = false;
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $msg = "Invalid link.";
    $msgType = "error";
} else {
    // Validate Token (Check users table)
    $token_hash = hash('sha256', $token);
    $stmt = $db->prepare("SELECT id, email FROM users WHERE reset_token = ? AND reset_expires > NOW() LIMIT 1");
    $stmt->execute([$token_hash]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $validToken = true;
    } else {
        $msg = "This password reset link is invalid or has expired.";
        $msgType = "error";
    }
}

// Handle Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    verify_csrf_token();
    $pass1 = $_POST['pass1'];
    $pass2 = $_POST['pass2'];
    
    if ($pass1 !== $pass2) {
        $msg = "Passwords do not match.";
        $msgType = "error";
    } elseif (strlen($pass1) < 8) {
        $msg = "Password must be at least 8 characters.";
        $msgType = "error";
    } else {
        // Update Password in users table
        $hash = password_hash($pass1, PASSWORD_DEFAULT);
        $update = $db->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $update->execute([$hash, $user['id']]);
        
        $msg = "Password updated successfully! <a href='index.php' style='color:#fff; text-decoration:underline;'>Login now</a>";
        $msgType = "success";
        $validToken = false; // Hide form
    }
}
?>

<div id="main" style="padding:40px 20px;">
    <div style="max-width:500px; margin:0 auto; background:#1e293b; padding:30px; border-radius:8px; border:1px solid #334155;">
        <h2 style="margin-top:0; color:var(--accent-orange); text-align:center;">Reset Password</h2>
        
        <?php if($msg): ?>
            <div style="background:<?php echo $msgType=='success'?'#064e3b':'#7f1d1d'; ?>; color:white; padding:10px; border-radius:4px; margin-bottom:20px; text-align:center;">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <?php if ($validToken): ?>
        <form method="post" action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>">
            <?php csrf_field(); ?>
            <div style="margin-bottom:20px;">
                <label style="display:block; color:#fff; margin-bottom:5px;">New Password</label>
                <input type="password" name="pass1" required style="width:100%; padding:10px; border-radius:4px; border:1px solid #475569; background:#0f172a; color:white;">
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:block; color:#fff; margin-bottom:5px;">Confirm Password</label>
                <input type="password" name="pass2" required style="width:100%; padding:10px; border-radius:4px; border:1px solid #475569; background:#0f172a; color:white;">
            </div>
            <button type="submit" style="width:100%; padding:12px; background:var(--accent-orange); border:none; color:white; font-weight:bold; border-radius:4px; cursor:pointer;">Reset Password</button>
        </form>
        <?php endif; ?>
        
        <div style="text-align:center; margin-top:20px;">
            <a href="index.php" style="color:var(--text-muted); text-decoration:none;">&larr; Back to Home</a>
        </div>
    </div>
</div>

<?php 
// No explicit footer include needed if index.php structure suggests closed tags, but adding closing tags just in case
?>
</body>
</html>
