<?php
include("header.php");
?>
<div id="main" class="full">
	<div class="content-card" style="max-width:1400px; margin:0 auto; align-items: stretch;">
		<div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-color); padding-bottom:15px; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
			<h2 style="margin:0; color:var(--accent-orange);"><i class="fa-solid fa-chart-line"></i> The Best Case Standings</h2>
			<a href="choose.php" style="padding:8px 16px; background:var(--accent-orange); color:var(--accent-text); border-radius:6px; text-decoration:none; font-weight:bold; font-size:0.9em; transition:background 0.2s;"><i class="fa-solid fa-arrow-left"></i> Back to Standings</a>
		</div>
		<p style="color:var(--text-muted); margin-bottom:20px;">Detailed analysis of maximum potential scores.</p>
		
		<style>
		/* DataTables Overrides for Theme */
		.dataTables_wrapper { color: var(--text-muted); font-size: 0.95rem; }
		.dataTables_length select, .dataTables_filter input {
			background: var(--primary-blue); border: 1px solid var(--border-color); color: var(--text-light); padding: 5px; border-radius: 4px;
		}
		table.dataTable tbody tr { background-color: transparent; }
		table.dataTable.hover tbody tr:hover, table.dataTable.display tbody tr:hover {
			background-color: rgba(255,255,255,0.05) !important; /* Generic hover */
		}
		.dataTables_info, .dataTables_paginate { color: var(--text-muted) !important; }
		.paginate_button { color: var(--text-light) !important; }
		.paginate_button.current { background: var(--accent-orange) !important; border:none !important; color:white !important; }
        .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_processing, .dataTables_wrapper .dataTables_paginate {
            color: var(--text-muted);
        }
        table.dataTable.no-footer { border-bottom: 1px solid var(--border-color); }
		</style>
        
        
			<div class="table-container">
				<table id="bestTable" style="width:100%; border-collapse:collapse;">
					<thead>
					<tr style="border-bottom: 2px solid rgba(255,255,255,0.1);">
						<th style="padding:15px; text-align:center; color:var(--accent-orange); font-size:1.1em;">RANK</th>
						<th style="padding:15px; text-align:left; color:var(--accent-orange); font-size:1.1em;">NAME</th>
						<th style="padding:15px; text-align:center; color:var(--accent-orange); font-size:1.1em;">BEST</th>
						<th style="padding:15px; text-align:center;"><a href="standings.php?type=normal" style="color:var(--accent-orange); text-decoration:underline;">ACTUAL</a></th>
						<th style="padding:15px; text-align:center; color:var(--accent-orange); font-size:1.1em;">PPR</th>
						<th style="padding:15px; text-align:center; color:var(--accent-orange); font-size:1.1em;">TIEBREAKER</th>
					</tr>
					</thead>
					<tbody>
					<?php
                        // VIEW FILTERING (Main vs Sweet 16)
                        $view = isset($_GET['view']) ? $_GET['view'] : 'main';

                        // Toggle Buttons (Reuse same style as normal.php for consistency)
                        if(!empty($meta['sweet16Competition'])) {
                            echo '<div style="display:flex; justify-content:flex-start; margin-bottom:25px;">';
                            echo '<div style="background:rgba(255,255,255,0.05); padding:5px; border-radius:30px; display:flex; gap:5px;">';
                            
                            $mainClass = ($view == 'main') ? 'background:var(--accent-orange); color:white;' : 'color:var(--text-muted); hover:text-white';
                            $s16Class = ($view == 'sweet16') ? 'background:var(--accent-orange); color:white;' : 'color:var(--text-muted); hover:text-white';
                            
                            echo '<a href="standings.php?type=best&view=main" style="padding:8px 20px; border-radius:25px; text-decoration:none; font-weight:bold; transition:all 0.2s; '.$mainClass.'">Main Tournament</a>';
                            echo '<a href="standings.php?type=best&view=sweet16" style="padding:8px 20px; border-radius:25px; text-decoration:none; font-weight:bold; transition:all 0.2s; '.$s16Class.'">Second Chance</a>';
                            
                            echo '</div></div>';
                        }
                    
						$query = "SELECT scores.id, scores.name, scores.score, best_scores.score AS b_score, brackets.tiebreaker, brackets.63, brackets.email, brackets.person FROM scores, best_scores, brackets WHERE scores.scoring_type = best_scores.scoring_type and scores.scoring_type = 'main' and scores.id = best_scores.id AND scores.id = brackets.id AND brackets.type = '$view' ORDER BY best_scores.score DESC, scores.score DESC, scores.name ASC";
						$stmt = $db->query($query);
						$rankCounter = 0;
						$rank = 0;
						$prev_score = -1;
						$i = 0;

						while($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
							
							$useremail = $user['email'];
							if( $rankCounter == 0 ) {
								$prev_score = $user['b_score'];
								$rankCounter = 1;
								$rank = 1;
							}
							
							if( $user['b_score'] != $prev_score ) {
								$prev_score = $user['b_score'];
								$rank = $rankCounter;
							}
							
							// Highlight User Row
							$rowStyle = "border-bottom:1px solid rgba(255,255,255,0.05); transition:background 0.2s;";
                            $uNameColor = "var(--text-light)";
							if (isset($_SESSION['useremail']) && strtolower($useremail) == strtolower($_SESSION['useremail']) && $useremail != ""){
								$rowStyle .= " background:var(--accent-highlight); font-weight:bold;"; // Stronger Highlight
                                $uNameColor = "var(--accent-orange)";
							} 

                            echo "<tr style='$rowStyle'>";
                            
							echo "<td align='center' data-order='$rank' style='padding:15px; font-size:1.1em; color:var(--text-muted);'>";
							echo "#" . $rank;
							echo "</td><td style='padding:15px;'>";
                            
							// Name Link
							if (isset($_SESSION['useremail']))
							{
								echo "<a href=\"view.php?id=$user[id]\" style='color:$uNameColor; text-decoration:none; font-size:1.1em;'>" . h(stripslashes($user['name'])) . "</a>";
                                echo "<div style='font-size:0.85em; color:var(--text-muted); margin-top:4px;'>" . h(stripslashes($user['person'])) . "</div>";
							}
							else
							{
								echo "<a href=\"view.php?id=$user[id]\" style='color:$uNameColor; text-decoration:none; font-size:1.1em;'>" . h(stripslashes($user['name'])) . "</a>";
							}

							echo "</td><td align='center' data-order='".$user['b_score']."' style='padding:15px; font-size:1.1em; font-weight:bold; color:var(--text-light);'>";
							echo $user['b_score'];
							echo "</td><td align='center' data-order='".$user['score']."' style='padding:15px; color:var(--text-muted);'>";
							echo $user['score'];
							echo "</td><td align='center' data-order='".($user['b_score']-$user['score'])."' style='padding:15px; color:#22c55e;'>"; // Green for PPR/Potential
							echo $user['b_score']-$user['score'];
							echo "</td><td align='center' style='padding:15px;'>";
							echo $user['63'];
							echo " <span style='color:var(--text-muted);'>- " . $user['tiebreaker'] . "</span>";
							echo "</td></tr>";
							
							$rankCounter++;
						}
					?>
					</tbody>
				</table>
			</div>
			<script>
			$(document).ready(function() {
				$('#bestTable').DataTable({
					"paging": true,
					"lengthChange": true,
					"searching": true,
					"ordering": true,
					"info": true,
					"autoWidth": false,
					"responsive": true,
					"pageLength": 25,
                    "language": {
                        "search": "_INPUT_",
                        "searchPlaceholder": "Search standings..."
                    },
                    "dom": '<"top"f>rt<"bottom"p><"clear">'
				});
			});
			</script>
		</div>
	</div>
	
</div>



</body>
</html>
