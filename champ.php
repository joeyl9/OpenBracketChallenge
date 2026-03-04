<?php
include("admin/database.php");
include("admin/functions.php");
include("header.php");
?>

<div id="main" class="full">
	<div class="content-card" style="max-width:1400px; margin:0 auto; align-items: stretch;">
		<div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-color); padding-bottom:15px; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
			<h2 style="margin:0; color:var(--accent-orange);">👑 Champion Picks</h2>
			<a href="choose.php" style="padding:8px 16px; background:var(--accent-orange); color:var(--accent-text); border-radius:6px; text-decoration:none; font-weight:bold; font-size:0.9em; transition:background 0.2s;"><i class="fa-solid fa-arrow-left"></i> Back to Standings</a>
		</div>
		<p style="color:var(--text-muted); margin-bottom:20px;">The most popular picks to win it all.</p>
		
		<div class="table-container">
			<table style="width:100%; border-collapse:separate; border-spacing:0; background:transparent; border-radius:8px;">
				<thead>
					<tr style="border-bottom: 2px solid var(--border-color);">
						<th style="padding:15px; text-align:left; color:var(--text-light);">Team</th>
						<th style="padding:15px; text-align:center; color:var(--text-light);"># Brackets</th>
					</tr>
				</thead>
				<tbody>
				<?php
					$query = "SELECT `63`, COUNT(*) as count FROM `brackets` GROUP BY `63` ORDER BY `count` DESC, `63` ASC";
					$stmt = $db->query($query);
					
					while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
						if($row['63'] != "") {
							echo "<tr style='border-bottom:1px solid var(--border-color);'>"; 
							echo "<td style='padding:12px; border-bottom:1px solid var(--border-color); font-weight:bold; color:var(--text-light);'>".h($row['63'])."</td>"; 
							echo "<td style='padding:12px; text-align:center; border-bottom:1px solid var(--border-color);'>"; 
							echo "<a href=\"picks.php?team=".urlencode($row['63'])."\" style='display:inline-block; padding:5px 15px; background:var(--accent-orange); color:white; border-radius:20px; text-decoration:none; font-size:0.9em; font-weight:bold;'>{$row['count']}</a>";
							echo "</td></tr>"; 
						}
					}
				?>
				</tbody>
			</table>
		</div>
	</div>
</div>


</div>
</body>
</html>
