<?php
include("admin/functions.php");
include("header.php");

$bracketViewLimit = 1024;

include('endgamesummary_view_module.php');

?>


		<div id="main" class="full">
			
			<?php
				$summary_query = "SELECT count(*) num_scenarios FROM `end_games` where eliminated = false and round='7'"; 
				$stmt = $db->query($summary_query);
				$summary_data = $stmt->fetch(PDO::FETCH_ASSOC);
			?>
			
			<div class="content-card" style="max-width:1400px; margin:0 auto; width:100%;">
				
				<div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-color); padding-bottom:15px; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
					<h2 style="margin:0; color:var(--accent-orange);"><i class="fa-solid fa-flag-checkered"></i> End Game Scenarios (<?php echo number_format($summary_data['num_scenarios']); ?> Remaining)</h2>
					<a href="choose.php" style="padding:8px 16px; background:var(--accent-orange); color:var(--accent-text); border-radius:6px; text-decoration:none; font-weight:bold; font-size:0.9em; transition:background 0.2s;"><i class="fa-solid fa-arrow-left"></i> Back to Standings</a>
				</div>
				<p style="color:var(--text-muted); margin-bottom:20px;">Do you still have a chance?</p>

				<div style="margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
				<div style="margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
					<?php
						$rawSort = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : "paths";
						$sort = in_array($rawSort, ['paths', 'pwin']) ? $rawSort : 'paths';

						$rawView = isset($_REQUEST['view']) ? $_REQUEST['view'] : "main";
						$view = in_array($rawView, ['main', 'sweet16']) ? $rawView : 'main';
						
						$pathsClass = ($sort != "pwin") ? "color:white; font-weight:bold; border-bottom:2px solid var(--accent-orange);" : "color:var(--text-muted);";
						$winClass = ($sort == "pwin") ? "color:white; font-weight:bold; border-bottom:2px solid var(--accent-orange);" : "color:var(--text-muted);";
					
						// View Toggle Styles
						$mainStyle = ($view == 'main') ? "background:var(--accent-orange); color:var(--accent-text);" : "color:var(--text-muted);";
						$s16Style = ($view == 'sweet16') ? "background:var(--accent-orange); color:var(--accent-text);" : "color:var(--text-muted);";
					?>
					
					<!-- View Toggle -->
					<div style="font-size:0.9rem; background:var(--primary-blue); padding:4px; border-radius:30px; border:1px solid var(--border-color); display:flex; align-items:center;">
						<a href='endgamesummary.php?view=main&sort=<?php echo $sort; ?>' style='text-decoration:none; padding:5px 15px; border-radius:20px; transition:all 0.2s; <?php echo $mainStyle; ?>'>Main</a>
						<a href='endgamesummary.php?view=sweet16&sort=<?php echo $sort; ?>' style='text-decoration:none; padding:5px 15px; border-radius:20px; transition:all 0.2s; <?php echo $s16Style; ?>'>Second Chance</a>
					</div>
					
					<!-- Sort Toggle -->
					<div style="font-size:0.9rem; background:var(--primary-blue); padding:4px; border-radius:30px; border:1px solid var(--border-color); display:flex; align-items:center;">
						<span style="color:var(--text-muted); margin:0 10px; font-weight:600;">Sort:</span>
						<a href='endgamesummary.php?view=<?php echo $view; ?>' style='text-decoration:none; padding:5px 15px; border-radius:20px; transition:all 0.2s; <?php echo ($sort != "pwin") ? "background:var(--accent-orange); color:var(--accent-text);" : "color:var(--text-muted);"; ?>'>Number of Paths</a>
						<a href='endgamesummary.php?sort=pwin&view=<?php echo $view; ?>' style='text-decoration:none; padding:5px 15px; border-radius:20px; transition:all 0.2s; <?php echo ($sort == "pwin") ? "background:var(--accent-orange); color:var(--accent-text);" : "color:var(--text-muted);"; ?>'>Probability</a>
					</div>
				</div>
				
				<?php
					// Fix: Calculate lowest rank SPECIFIC to the current view (Main vs Sweet 16)
					$last_place_query = "SELECT max(p.rank) rank FROM `possible_scores` p
                                         JOIN `brackets` b ON p.bracket_id = b.id
                                         JOIN `end_games` e ON e.id = p.outcome_id
                                         WHERE p.`type`='path_to_victory' AND b.type = ? AND e.eliminated = false";
					$stmt = $db->prepare($last_place_query);
					$stmt->execute([$view]);
					$last_place_data = $stmt->fetch(PDO::FETCH_ASSOC);
					
					$lowestRank = $last_place_data['rank'] ?? 10;
					$viewAll = ($summary_data['num_scenarios'] <= 1025);
					$maxScoreRanks = isset($maxScoreRanks) ? $maxScoreRanks : 5; 
				?>
				
				<!-- Rank Cards Grid -->
				<div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap:24px; align-items:start; width:100%; box-sizing:border-box; margin-bottom:40px;">
					
					<?php
					for( $showRanks=1; $showRanks <= $maxScoreRanks; $showRanks++ )
					{
						$rankName = ordinal_suffix( $showRanks ) . " Place";
						createSummaryTable($db, $showRanks, $rankName, $viewAll, $summary_data['num_scenarios'], $sort, $view);
					}
					?>
					
				</div>
				
				<!-- Last Place Section -->
				<div style="margin-top:60px; padding-top:40px; border-top:1px solid rgba(255,255,255,0.05); position:relative;">
					<?php createSummaryTable($db, $lowestRank, "Last Place", $viewAll, $summary_data['num_scenarios'], $sort, $view); ?>
				</div>

			</div>
		
		</div>

	</div>
</body>
</html>
