<?php
include("header.php");
include("admin/functions.php");
 
$query = "SELECT * FROM `blog` ORDER BY id DESC LIMIT 3";
$blog = $db->query($query);

$query = "SELECT c.time, c.content, c.from, c.bracket, b.name, b.person  FROM `comments` c, `brackets` b WHERE b.id = c.bracket ORDER BY c.time DESC";
$comments = $db->query($query);

if($blog == NULL) {
	echo "Please <a href=\"admin/install_ui.php\">configure the site <br />
               AFTER setting up admin/database.php to point to your database.</a>\n";
	exit();
}
?>
	
	
		<div id="main">
			
        <div class="left_side">
            <?php
            if(isset($_SESSION['success'])) {
            ?>
            <div class="success"><?php echo h($_SESSION['success'])?></div>
            <?php
            }
            if(isset($_SESSION['errors'])) { 
            ?>
            <div class="errors"><p><em>Errors:</em></p><?php echo h($_SESSION['errors'])?></div>
            <?php
            }
            unset($_SESSION['errors']);
            unset($_SESSION['success']);
            
            // Modern Blog Cards (Simplified)
            while ($post = $blog->fetch(PDO::FETCH_NUM)){
                echo "<div class='blog-card'>";
                echo "<h2 style='margin-top:0;'>".h($post[1])."</h2>";
                if(!empty($post[2])) {
                    echo "<h3 style='border:none; margin-bottom:10px; padding-bottom:0;'>".h($post[2])."</h3>";
                }
                // Blog content is admin-authored HTML (TinyMCE), rendered as-is
                echo "<div class='blog-content' style='margin-top:15px; color:var(--text-light); line-height:1.7;'>$post[3]</div>";
                // Force children to inherit color in case of saved inline styles
                echo "<style>.blog-content * { color: inherit !important; }</style>";
                echo "<div class=\"date\" style='margin-top:20px; padding-top:15px; border-top:1px solid rgba(255,255,255,0.1);'>Posted: ".h($post[4])."</div>";
                echo "</div>";
            }
        ?>
            <br />
        </div>
			
			<div class="right_side">
                
                <!-- Latest Chatter (Moved to Sidebar) -->
				<?php include("sidebar.php"); ?>
			</div>
			
		</div>
<?php include("footer.php"); ?>
</div>
</body>
</html>

