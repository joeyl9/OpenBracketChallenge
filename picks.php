<?php
include("admin/database.php");
include("admin/functions.php");
include("header.php");

// Extract the team name from the raw query string to handle names with unencoded ampersands (e.g., Texas A&M)
$team = '';
if (isset($_SERVER['QUERY_STRING']) && preg_match('/team=(.*)/i', $_SERVER['QUERY_STRING'], $matches)) {
    $team = urldecode($matches[1]);
} elseif (isset($_GET['team'])) {
    $team = $_GET['team'];
}

// Fetch all brackets picking this team
$stmt = $db->prepare("SELECT * FROM `brackets` WHERE `63` = ? ORDER BY `name` ASC");
$stmt->execute([$team]);
$brackets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div id="main" class="full">
	<div class="content-card" style="max-width:1200px; margin:0 auto; width:100%; min-height:400px; align-items:stretch;">
		
		<div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-color); padding-bottom:15px; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
			<h2 style="margin:0; color:var(--accent-orange); display:flex; align-items:center; gap:10px;">
				<i class="fa-solid fa-basketball"></i> 
				<?php echo htmlspecialchars($team); ?> 
				<span style="font-size:0.6em; color:var(--text-muted); font-weight:normal; margin-top:5px;">(<?php echo count($brackets); ?> Picks)</span>
			</h2>
			<a href="champ.php" style="padding:8px 16px; background:var(--accent-orange); color:white; border-radius:6px; text-decoration:none; font-weight:bold; font-size:0.9em; transition:background 0.2s;">
				<i class="fa-solid fa-arrow-left"></i> Back to Champ Picks
			</a>
		</div>

		<?php if(count($brackets) > 0) { ?>
			
			<div class="table-container">
				<table class="styled-table" style="width:100%; color:var(--text-light); text-align:left;">
					<thead>
						<tr style="border-bottom:2px solid var(--border-color); background:rgba(0,0,0,0.2);">
							<th style="padding:15px; color:var(--accent-orange);">Bracket Name</th>
							<th style="padding:15px; color:var(--text-muted); text-align:center;">Current Score</th>
							<th style="padding:15px; color:var(--text-muted); text-align:center;">Tiebreaker</th>
							<?php if (isset($_SESSION['useremail'])) { ?>
							<th style="padding:15px; color:var(--text-muted);">Owner</th>
							<?php } ?>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach($brackets as $bracket) {
							$name = h($bracket['name']);
							$person = h($bracket['person']);
							$tiebreaker = h($bracket['tiebreaker']);
							

							echo "<tr style='border-bottom:1px solid rgba(255,255,255,0.05); transition:background 0.2s;' onmouseover=\"this.style.background='rgba(255,255,255,0.05)'\" onmouseout=\"this.style.background='transparent'\">";
							
							echo "<td style='padding:12px 15px;'>";
							echo "<a href=\"view.php?id={$bracket['id']}\" style='color:var(--text-light); font-weight:bold; text-decoration:none;'>{$name}</a>";
							echo "</td>";
							
							// Placeholder for calculated score from `scores` table
							echo "<td style='padding:12px 15px; text-align:center; color:var(--text-muted);'>-</td>";

							echo "<td style='padding:12px 15px; text-align:center; font-weight:bold; color:var(--accent-orange);'>{$tiebreaker}</td>";
							
							if (isset($_SESSION['useremail'])) {
								echo "<td style='padding:12px 15px; color:var(--text-muted);'>{$person}</td>";
							}
							
							echo "</tr>";
						}
						?>
					</tbody>
				</table>
			</div>

		<?php } else { ?>
			
			<div style="text-align:center; padding:50px 20px; color:var(--text-muted);">
				<div style="font-size:3em; margin-bottom:20px; opacity:0.3;"><i class="fa-regular fa-face-frown"></i></div>
				<h3 style="margin-top:0;">No one picked <?php echo htmlspecialchars($team); ?></h3>
				<p>That's a bold strategy (or lack thereof).</p>
				<button onclick="history.back()" style="margin-top:20px; background:var(--secondary-blue); border:1px solid var(--border-color); color:var(--text-light); padding:10px 20px; border-radius:6px; cursor:pointer;">Go Back</button>
			</div>

		<?php } ?>
	</div>
</div>


</body>
</html>
