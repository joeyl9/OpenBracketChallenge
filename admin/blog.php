<?php
include 'functions.php';
validatecookie();
include("header.php");

// Fetch All Posts for Dropdowns
$query = "SELECT id,title,subtitle FROM `blog` ORDER BY id DESC";
$stmt = $db->query($query);
$all_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Defaults (Create Mode)
$formAction = "post.php?action=post";
$formHeader = "Write a New Post";
$submitBtn = "Submit Post";
$p_title = "";
$p_subtitle = "";
$p_content = "";
$p_id = "";

// Check Edit Mode
if(isset($_POST['edit_post_id'])) {
	$edit_id = $_POST['edit_post_id'];
	$q = "SELECT * FROM `blog` WHERE id=?";
	$stmt = $db->prepare($q);
	$stmt->execute([$edit_id]);
	if($post = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$formAction = "post.php?action=edit";
		$formHeader = "Edit Post";
		$submitBtn = "Update Post";
		$p_title = $post['title'];
		$p_subtitle = $post['subtitle'];
		$p_content = $post['content'];
		$p_id = $post['id'];
	}
}
?>
	<div id="main">
		<div class="full">
			
			<!-- Editor Form -->
			<form method="post" action="<?php echo $formAction; ?>">
			<?php csrf_field(); ?>
			<input type="hidden" name="id" value="<?php echo $p_id; ?>">
			<h2><?php echo $formHeader; ?></h2>
            <div style="margin-bottom: 20px;">
                <a href="index.php" class="btn-outline">&larr; Back to Dashboard</a>
            </div>
			
			<?php if($p_id) { ?>
			<div style="margin-bottom:10px; color: var(--accent-orange);">
				<a href="blog.php" style="color:#fff; text-decoration:underline;">&laquo; Cancel Edit (Write New)</a>
			</div>
			<?php } ?>

            <style>
                .blog-editor input[type="text"] {
                    width: 100%;
                    padding: 10px;
                    border: 1px solid #444;
                    background: #222;
                    color: white;
                    border-radius: 4px;
                    box-sizing: border-box;
                    margin-bottom: 5px;
                }
                .blog-editor label {
                    display: block;
                    color: #aaa;
                    margin-bottom: 5px;
                }
                .blog-group {
                    margin-bottom: 15px;
                }
            </style>
            <div class="dashboard-card" style="padding:20px; align-items:flex-start;">
                <div class="blog-group" style="width:100%;">
                    <label style="color:var(--text-light); margin-bottom:5px; display:block; font-weight:bold; text-align:left;">Post Title</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($p_title); ?>" required style="background:rgba(255,255,255,0.05); color:var(--text-light); border:1px solid var(--border-color); padding:10px; width:100%; border-radius:4px;" />
                </div>
                <div class="blog-group" style="width:100%; margin-top:15px;">
                    <label style="color:var(--text-light); margin-bottom:5px; display:block; font-weight:bold; text-align:left;">Post Subtitle</label>
                    <input type="text" name="subtitle" value="<?php echo htmlspecialchars($p_subtitle); ?>" style="background:rgba(255,255,255,0.05); color:var(--text-light); border:1px solid var(--border-color); padding:10px; width:100%; border-radius:4px;" />
                </div>
                <div class="blog-group" style="width:100%; margin-top:15px;">
                    <label style="color:var(--text-light); margin-bottom:5px; display:block; font-weight:bold; text-align:left;">Content</label>
                    <textarea name="content" style="width:100%; height:300px; background:rgba(255,255,255,0.05); color:var(--text-light); border:1px solid var(--border-color); padding:10px; font-family:sans-serif; border-radius:4px; box-sizing: border-box; line-height:1.5; font-size:1rem; resize:vertical;"><?php echo htmlspecialchars($p_content); ?></textarea>
                </div>
                <div style="text-align:right; width:100%; margin-top:20px;">
                    <input type="submit" value="<?php echo $submitBtn; ?>" class="finish-btn" style="padding: 12px 30px; font-size:1.1em; cursor:pointer; background: var(--accent-orange); color:var(--accent-text); border:none; border-radius:4px; font-weight:bold; transition: background 0.2s;" onmouseover="this.style.background='var(--accent-orange-hover)'" onmouseout="this.style.background='var(--accent-orange)'" />
                </div>
            </div>
			</form>
			
			<hr style="border-color:#444; margin: 30px 0;">

			<!-- Edit Selection Form -->
			<form method="post" action="blog.php">
			<h2>Edit an Existing Post</h2>
			<table>
				<tr>
					<td>Select a Post</td>
					<td>
						<select name="edit_post_id">
						<?php
						foreach($all_posts as $post) {
							echo "<option value=\"{$post['id']}\">" . htmlspecialchars($post['title']) . " (" . htmlspecialchars($post['subtitle']) . ")</option>\n";
						}
						?>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan="2" align="center"><input type="submit" value="Load for Editing" /></td>
				</tr>
			</table>
			</form>

			<hr style="border-color:#444; margin: 30px 0;">

			<!-- Delete Form -->
			<form method="post" action="post.php?action=delete">
			<?php csrf_field(); ?>
			<h2>Delete a Post</h2>
			<table>
				<tr>
					<td>Select a Post</td>
					<td>
						<select name="post">
						<?php
						foreach($all_posts as $post) {
							echo "<option value=\"{$post['id']}\">" . htmlspecialchars($post['title']) . " (" . htmlspecialchars($post['subtitle']) . ")</option>\n";
						}
						?>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan="2" align="center"><input type="button" value="Delete Post" onclick="var f=this.form; showConfirm('Delete Post?', 'Are you sure you want to delete this post?', function(){ f.submit(); });" /></td>
				</tr>
			</table>
			</form>
		</div>
	</div>
</div>
<?php include('footer.php'); ?>
</body>
</html>


