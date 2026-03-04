<?php
session_start();
include("header.php");
?>

<div id="main" class="login-centered" style="display:flex; justify-content:center; align-items:center; min-height:80vh;">
    <div class="login-inner" style="width:100%; max-width:450px;">
        
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="font-size: 3rem; color: var(--accent-orange); margin-bottom: 10px;"><i class="fa-solid fa-basketball"></i></div>
            <h2 style="color: #fff; margin: 0; font-size: 1.8rem;">Welcome Back</h2>
            <p style="color: #cbd5e1;">Sign in to manage your bracket</p>
        </div>

        <?php if (isset($_SESSION['errors'])): ?>
            <div style="background: #ef4444; color: white; padding: 10px; border-radius: 6px; margin-bottom: 20px;">
                <?php
                $errs = $_SESSION['errors'];
                if (is_array($errs)) {
                    foreach ($errs as $e) {
                        echo h($e) . '<br>';
                    }
                } else {
                    echo h($errs);
                }
                unset($_SESSION['errors']);
                ?>
            </div>
        <?php endif; ?>

        <form action="login_check.php" method="post">
            <?= CSRF::input() ?>
            <input type="hidden" name="next" value="<?= h($_GET['next'] ?? '') ?>">
            <div style="margin-bottom: 20px;">
                <label style="color: var(--text-light); font-weight: bold; font-size: 0.9em; display: block; margin-bottom: 5px;">Email Address</label>
                <div style="position: relative;">
                    <i class="fa-solid fa-envelope" style="position: absolute; left: 15px; top: 12px; color: #64748b;"></i>
                    <input type="email" name="useremail" placeholder="you@example.com" required 
                           style="width: 100%; padding: 12px 12px 12px 40px; border-radius: 6px; border: 1px solid #475569; background: #0f172a; color: white; display: block; box-sizing: border-box;">
                </div>
            </div>

            <div style="margin-bottom: 30px;">
                <label style="color: var(--text-light); font-weight: bold; font-size: 0.9em; display: block; margin-bottom: 5px;">Password</label>
                <div style="position: relative;">
                    <i class="fa-solid fa-lock" style="position: absolute; left: 15px; top: 12px; color: #64748b;"></i>
                    <input type="password" name="password" placeholder="••••••••" required 
                           style="width: 100%; padding: 12px 12px 12px 40px; border-radius: 6px; border: 1px solid #475569; background: #0f172a; color: white; display: block; box-sizing: border-box;">
                </div>
                <div style="text-align: right; margin-top: 5px;">
                    <a href="forgot_password.php" style="color: var(--accent-orange); font-size: 0.85em; text-decoration: none;">Forgot password?</a>
                </div>
            </div>

            <button type="submit" 
                    style="width: 100%; padding: 12px; background: var(--accent-orange); color: white; border: none; border-radius: 6px; font-weight: bold; font-size: 1.1em; cursor: pointer; transition: background 0.2s;">
                Sign In
            </button>
        </form>

        <div style="text-align: center; margin-top: 30px; border-top: 1px solid #334155; padding-top: 20px;">
            <p style="color: var(--text-muted); font-size: 0.9em;">Don't have an account yet?</p>
            <a href="register.php" style="color: #fff; font-weight: bold; text-decoration: none;">Create an Account</a>
        </div>

    </div>
</div>


</body>
</html>
