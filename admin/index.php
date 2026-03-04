<?php
include 'functions.php';
validatecookie();
include("header.php");
?>

<script type="text/javascript">

function confirmPathsToVictory(truncate)
{
	var message = "Are you sure you want to calculate paths? \n\n" + 
    "Example: php ./mmm/admin/calculate_paths_to_victory.php \n\n" + 
    "Obviously, you can only feasibly calculate this (hours instead of years) after the first round.";

	showConfirm("Calculate Paths?", message, function() {
		window.location.href ="calculate_paths_to_victory.php?truncate="+truncate;
	});
}

</script>

    <div id="main">
        <div class="full">
            <h2>Select a Task</h2>
            <?php
            $role = $_SESSION['admin_user']['role'] ?? 'limited';
            $is_super = ($role == 'super');
            $is_limited = ($role == 'super' || $role == 'limited');
            ?>
            <div class="dashboard-grid">
                
                <!-- --- SECTION 1: GAME OPERATIONS --- -->
                
                <a href="score.php" class="dashboard-card" style="border-color: #22c55e;">
					<h3>Score Brackets</h3>
					<p>Calculate current points</p>
				</a>

                <?php if ($meta['use_live_scoring']): ?>
                <a href="fetch_live.php" class="dashboard-card" style="border-color: #22c55e;">
                    <i class="fa-solid fa-satellite-dish"></i>
                    <h3>Fetch Live Scores</h3>
                    <p>Update Master from Feed</p>
                </a>
                <form method="post" action="toggle_live.php" class="dashboard-card" style="border-color: #f59e0b; position:relative;">
                    <?php csrf_field(); ?>
                    <i class="fa-solid fa-toggle-on" style="color:#22c55e; font-size:2rem;"></i>
                    <h3>Live Scoring: ON</h3>
                    <p>Click to Disable</p>
                    <button type="submit" style="position:absolute; inset:0; width:100%; height:100%; opacity:0; cursor:pointer;"></button>
                </form>
                <?php else: ?>
                <form method="post" action="toggle_live.php" class="dashboard-card" style="border-color: #ef4444; opacity: 0.8; position:relative;">
                    <?php csrf_field(); ?>
                    <i class="fa-solid fa-toggle-off" style="font-size:2rem;"></i>
                    <h3>Live Scoring: OFF</h3>
                    <p>Click to Enable</p>
                    <button type="submit" style="position:absolute; inset:0; width:100%; height:100%; opacity:0; cursor:pointer;"></button>
                </form>
                <?php endif; ?>

                <a href="bracket.php" class="dashboard-card" style="border-color: #06b6d4;">
					<h3>Edit Master</h3>
					<p>Update winners & scores</p>
				</a>
                
                <a href="broadcasts.php" class="dashboard-card" style="border-color: #f59e0b;">
					<h3>Broadcasts</h3>
					<p>Send System Alerts</p>
				</a>

                <!-- --- SECTION 2: USER MANAGEMENT --- -->
                
                <?php if ($is_super): ?>
                <a href="users.php" class="dashboard-card" style="border-color: #3b82f6;">
                    <h3>User Management</h3>
                    <p>Add/Remove Admins</p>
                </a>
                <?php endif; ?>

                <a href="paid.php" class="dashboard-card" style="border-color: #10b981;">
                    <h3>Paid List</h3>
                    <p>Track payments</p>
                </a>

                <?php
                $edit_main_closed = !empty($meta['closed']);
                $edit_s16_enabled = !empty($meta['sweet16Competition']);
                $edit_s16_closed  = !empty($meta['sweet16_closed']);
                $edit_available = !$edit_main_closed || ($edit_s16_enabled && !$edit_s16_closed);
                if ($edit_available): ?>
                <a href="edit.php" class="dashboard-card" style="border-color: #06b6d4;">
					<h3>Edit User Bracket</h3>
					<p>Fix user mistakes</p>
				</a>
                <?php endif; ?>

                <?php if($meta['mail'] != 0 ) { ?>
				<a href="contactall.php" class="dashboard-card" style="border-color: #f59e0b;">
					<h3>Contact Users</h3>
					<p>Send email updates</p>
				</a>
			    <?php } ?>

                <!-- --- SECTION 3: SETUP & CONFIG --- -->
                
                <?php if ($meta['closed'] == 0): ?>
                <form method="post" action="close.php" class="dashboard-card" style="border-color: #ef4444; position:relative;" onsubmit="return confirm('Are you sure you want to close submissions?');">
                    <?php csrf_field(); ?>
                    <h3>Close Submissions</h3>
                    <p>Lock Main Tournament</p>
                    <button type="submit" style="position:absolute; inset:0; width:100%; height:100%; opacity:0; cursor:pointer;"></button>
                </form>
                <?php elseif ( !empty($meta['sweet16Competition']) && empty($meta['sweet16_closed']) ): ?>
                <form method="post" action="close.php" class="dashboard-card" style="border-color: #ef4444; position:relative;" onsubmit="return confirm('Are you sure you want to close Second Chance?');">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="type" value="sweet16">
                    <h3>Close Second Chance</h3>
                    <p>Lock Second Chance</p>
                    <button type="submit" style="position:absolute; inset:0; width:100%; height:100%; opacity:0; cursor:pointer;"></button>
                </form>
                <?php endif; ?>

				<a href="settings.php" class="dashboard-card" style="border-color: #f59e0b;">
					<h3>Settings</h3>
					<p>Edit Deadline, Cost, etc.</p>
				</a>

                <a href="themes.php" class="dashboard-card" style="border-color: #f97316;">
					<h3>Theme Manager</h3>
					<p>Customize look & feel</p>
				</a>

				<a href="rules.php" class="dashboard-card" style="border-color: #8b5cf6;">
					<h3>Edit Rules</h3>
					<p>Update game rules</p>
				</a>
                
                <a href="blog.php" class="dashboard-card" style="border-color: #8b5cf6;">
					<h3>Manage Blog</h3>
					<p>Post announcements</p>
				</a>

                <a href="start_form.php" class="dashboard-card" style="border-color: #06b6d4;">
					<h3>Initialize Bracket</h3>
					<p>Setup first round matchups</p>
				</a>

                <?php if ($is_super): ?>
                <a href="logs.php" class="dashboard-card" style="border-color: #3b82f6;">
                    <h3>Audit Logs</h3>
                    <p>View System Activity</p>
                </a>
                <a href="install_ui.php" class="dashboard-card" style="border-color: #ef4444;">
                    <h3>Re-Configure</h3>
                    <p>Setup database or fix settings</p>
                </a>
                <?php endif; ?>

                <!-- --- SECTION 4: SEASON MANAGEMENT (Moved to Bottom) --- -->

                <?php 
                // Only show if Championship (Game 63) is decided
                $champStmt = $db->query("SELECT `63` FROM `master` WHERE id=2");
                $champion = $champStmt->fetchColumn();
                
                if ($is_super && !empty($champion)): 
                ?>
                <div class="dashboard-card-group" style="grid-column: 1 / -1; display:grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap:15px; background:rgba(255,255,255,0.03); padding:15px; border-radius:12px; border:1px solid rgba(255,255,255,0.05); margin-top: 20px;">
                    <div style="grid-column: 1 / -1; color:var(--text-muted); font-size:0.85em; font-weight:bold; text-transform:uppercase; letter-spacing:1px; margin-bottom:5px;">
                        <i class="fa-solid fa-lock-open"></i> Post-Season Tools
                    </div>

                    <a href="archive_season.php" class="dashboard-card" style="border-color: #8b5cf6;">
                        <h3>Archive Season</h3>
                        <p>Move results to Hall of Fame</p>
                    </a>
                    
                    <a href="reset_tournament.php" class="dashboard-card" style="border-color: #ef4444;">
                        <h3>New Season</h3>
                        <p>Reset brackets for next year</p>
                    </a>
                </div>
                <?php endif; ?>

            </div>
			<p style="color:var(--text-light);"><b>NOTE on Paths To Victory</b>
			   Paths to vistory should not be run before the final 16 games.  A great deal of information is calculated by this process,
			   it is still possible that this may cause a timeout on your web server, which can
			   cause partially computed results.  If this should happen, the paths can be calculated manually on your server using
			   this command line in your install directory: <br/>
			   &nbsp;&nbsp;&nbsp;&nbsp;<code>php ./admin/calculate_paths_to_victory.php truncate</code><br/>
			   The final 'truncate' keyword is only needed if you have partial results calculated, as they must be cleaned up.<br/>
                           Once 14 or fewer games remain, this function should be able to complete successfully from the browser unless you have a
			   huge number of brackets, in which case the command line will still have to be used.
			</p>
		</div>
	</div>
<?php include("footer.php"); ?>
</div>
</body>
</html>

