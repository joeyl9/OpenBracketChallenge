<?php
include 'functions.php';
validatecookie();
check_admin_auth('limited');
include("header.php");

$query = "SELECT rules FROM `meta`";
$stmt = $db->query($query);
$rules = $stmt->fetch(PDO::FETCH_NUM);
?>
	<div id="main">
		<div class="full">
			<form method="post" action="post.php?action=rules">
			<?php csrf_field(); ?>
			<h2>Rules</h2>
            <div style="margin-bottom: 20px;">
                <a href="index.php" class="btn-outline">&larr; Back to Dashboard</a>
            </div>
			<p>You may enter whatever rules you would like.  They will appear on the rules page exactly as you type them here.</p>
			<p>I recommend at least including your submission deadline and your contact information.</p>
			<div class="dashboard-card" style="padding:20px; align-items:flex-start;">
                <div class="blog-group" style="width:100%;">
                    <label style="color:var(--text-light); margin-bottom:5px; display:block; font-weight:bold; text-align:left;">Rules Content</label>
                    <textarea name="rules" style="width:100%; height:400px; background:rgba(255,255,255,0.05); color:var(--text-light); border:1px solid var(--border-color); padding:10px; font-family:sans-serif; border-radius:4px; box-sizing: border-box; line-height:1.5; font-size:1rem; resize:vertical;"><?php echo $rules[0]; ?></textarea>
                </div>
                <div style="text-align:right; width:100%; margin-top:20px;">
                    <input type="submit" value="Save Rules" style="padding: 12px 30px; font-size:1.1em; cursor:pointer; background: var(--accent-orange); color:var(--accent-text); border:none; border-radius:4px; font-weight:bold; transition: background 0.2s;" onmouseover="this.style.background='var(--accent-orange-hover)'" onmouseout="this.style.background='var(--accent-orange)'" />
                </div>
            </div>
		</form>
	</div>
</div>
</div>
<?php include('footer.php'); ?>
</body>
</html>


