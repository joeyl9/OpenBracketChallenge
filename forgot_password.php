<?php
session_start();
include("admin/database.php");
include("admin/functions.php");
include("header.php");

$msg = "";
$msgType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $email = trim($_POST['email']);
    
    // Check if email exists in USERS table
    $stmt = $db->prepare("SELECT id, name FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Generate Token
        $raw_token = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $raw_token);
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Save to DB (users table)
        $update = $db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
        $update->execute([$token_hash, $expiry, $email]);

        // Send Email
        $resetLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=$raw_token";
        
        $subject = "Password Reset Request - Bracket Challenge";
        $message = "Hello,\n\nA password reset was requested for your bracket '{$user['name']}'.\n\nClick the link below to reset your password (valid for 1 hour):\n$resetLink\n\nIf you did not request this, please ignore this email.";
        $headers = "From: no-reply@bracketchallenge.com"; // Configure as needed

        // Use standard mail() for now, can be upgraded to SMTP if needed
        mail($email, $subject, $message, $headers);

        $msg = "If that email exists in our system, we have sent a password reset link.";
        $msgType = "success";
    } else {
        // Same message for security (don't reveal valid emails)
        $msg = "If that email exists in our system, we have sent a password reset link.";
        $msgType = "success"; 
    }
}
?>

<div id="main" style="padding:40px 20px;">
    <div style="max-width:500px; margin:0 auto; background:#1e293b; padding:30px; border-radius:8px; border:1px solid #334155;">
        <h2 style="margin-top:0; color:var(--accent-orange); text-align:center;">Forgot Password?</h2>
        <p style="color:var(--text-muted); text-align:center; margin-bottom:20px;">Enter your email address to receive a reset link.</p>
        
        <?php if($msg): ?>
            <div style="background:rgba(16, 185, 129, 0.2); border:1px solid #059669; color:#6ee7b7; padding:10px; border-radius:6px; margin-bottom:20px; text-align:center;">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="forgot_password.php">
            <?php csrf_field(); ?>
            <div style="margin-bottom:20px;">
                <label style="display:block; color:var(--text-light); margin-bottom:8px; font-weight:600;">Email Address</label>
                <div style="position:relative;">
                    <i class="fa-solid fa-envelope" style="position:absolute; left:12px; top:12px; color:#64748b;"></i>
                    <input type="email" name="email" required placeholder="you@example.com" style="width:100%; padding:10px 10px 10px 40px; background:#0f172a; border:1px solid #334155; border-radius:6px; color:white; outline:none;">
                </div>
            </div>

            <button type="submit" name="submit" style="width:100%; padding:12px; background:var(--accent-orange); color:white; border:none; border-radius:6px; font-weight:bold; cursor:pointer; font-size:1rem; transition:background 0.2s;">
                Send Reset Link
            </button>
        </form>

        <div style="text-align:center; margin-top:20px; border-top:1px solid #334155; padding-top:20px;">
            <a href="index.php" style="color:var(--text-muted); text-decoration:none;">&larr; Back to Home</a>
        </div>
    </div>
</div>

<?php include("footer.php"); // Assuming footer exists in index, else just close body ?>
</body>
</html>
