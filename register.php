<?php
include("admin/database.php");
include("admin/functions.php");

// If already logged in, redirect to dashboard.
// BUT, allow Admins to access this page even if logged in as a user (or if just Admin).
// This addresses the request: "login are separate".
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$is_admin = isset($_SESSION['admin']) && $_SESSION['admin'] === true;

if($user_id && !$is_admin) {
    header("Location: dashboard.php");
    exit();
}

$error = "";
if(isset($_SESSION['register_error'])) {
    $error = $_SESSION['register_error'];
    unset($_SESSION['register_error']);
}

include("header.php");
?>

<div id="main" style="padding:40px; min-height:600px; display:flex; justify-content:center; align-items:center;">
    <div style="background:#1e293b; padding:40px; border-radius:12px; border:1px solid #334155; width:100%; max-width:450px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);">
        
        <div style="text-align:center; margin-bottom:30px;">
            <i class="fa-solid fa-user-plus" style="font-size:3rem; color:var(--accent-orange); margin-bottom:15px;"></i>
            <h2 style="margin:0; color:white;">Create Account</h2>
            <p style="color:var(--text-muted); margin-top:5px;">Create your bracket today.</p>
        </div>

        <?php if($error): ?>
            <div style="background:rgba(239,68,68,0.2); border:1px solid #ef4444; color:#ef4444; padding:12px; border-radius:6px; margin-bottom:20px; text-align:center;">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo h($error); ?>
            </div>
        <?php endif; ?>

        <form action="register_action.php" method="post">
            <?php csrf_field(); ?>
            
            <div style="margin-bottom:20px;">
                <label style="display:block; color:var(--text-light); margin-bottom:8px; font-weight:600;">Username</label>
                <div style="position:relative;">
                    <i class="fa-solid fa-user" style="position:absolute; left:12px; top:12px; color:#64748b;"></i>
                    <input type="text" name="name" required placeholder="Username" style="width:100%; padding:10px 10px 10px 40px; background:#0f172a; border:1px solid #334155; border-radius:6px; color:white; outline:none;">
                </div>
            </div>

            <div style="margin-bottom:20px;">
                <label style="display:block; color:var(--text-light); margin-bottom:8px; font-weight:600;">Email Address</label>
                <div style="position:relative;">
                    <i class="fa-solid fa-envelope" style="position:absolute; left:12px; top:12px; color:#64748b;"></i>
                    <input type="email" name="email" required placeholder="you@example.com" style="width:100%; padding:10px 10px 10px 40px; background:#0f172a; border:1px solid #334155; border-radius:6px; color:white; outline:none;">
                </div>
            </div>

            <div style="margin-bottom:20px;">
                <label style="display:block; color:var(--text-light); margin-bottom:8px; font-weight:600;">Password</label>
                <div style="position:relative;">
                    <i class="fa-solid fa-lock" style="position:absolute; left:12px; top:12px; color:#64748b;"></i>
                    <input type="password" name="password" required placeholder="••••••••" style="width:100%; padding:10px 10px 10px 40px; background:#0f172a; border:1px solid #334155; border-radius:6px; color:white; outline:none;">
                </div>
            </div>

            <div style="margin-bottom:30px;">
                <label style="display:block; color:var(--text-light); margin-bottom:8px; font-weight:600;">Confirm Password</label>
                <div style="position:relative;">
                    <i class="fa-solid fa-lock" style="position:absolute; left:12px; top:12px; color:#64748b;"></i>
                    <input type="password" name="password_confirm" required placeholder="••••••••" style="width:100%; padding:10px 10px 10px 40px; background:#0f172a; border:1px solid #334155; border-radius:6px; color:white; outline:none;">
                </div>
            </div>

            <button type="submit" style="width:100%; padding:12px; background:var(--accent-orange); color:white; border:none; border-radius:6px; font-weight:bold; font-size:1.1em; cursor:pointer; transition:all 0.2s;">
                Register
            </button>

            <p style="text-align:center; margin-top:20px; color:var(--text-muted);">
                Already have an account? <a href="login.php" style="color:var(--accent-orange); text-decoration:none;">Log In</a>
            </p>

        </form>

    </div>
</div>

<script>
// Simple client-side confirm/pass check
document.querySelector('form').addEventListener('submit', function(e) {
    var p1 = document.querySelector('input[name="password"]').value;
    var p2 = document.querySelector('input[name="password_confirm"]').value;
    if(p1 !== p2) {
        e.preventDefault();
        alert("Passwords do not match!");
    }
});
</script>

<?php include("footer.php"); ?>
