<?php
include("header.php");


function getRankFormat ($original, $hypothetical)
{
	if( $hypothetical < $original )
	{
		$change = "<span class='right'>+".($original-$hypothetical)."</span>";
	}
	else if( $hypothetical > $original )
	{
		$change = "<span class='wrong'>-".($hypothetical-$original)."</span>";
	}
	else
	{
		$change = "+0";
	}
	
	return $change."&nbsp;(".$hypothetical.")";
}

// get sort style
if( isset($_GET['sort']) && $_GET['sort'] != NULL )
{
	$sortStyle = $_GET['sort'];
}
else
{
	$sortStyle = 'main';
}


// get view filter (moved up so ranking calculation uses it)
$view = isset($_GET['view']) ? $_GET['view'] : 'main';

// get info about scoring systems
$scoringInfo = array();
$scoringDescriptions = "";
$scoringTables = array();

$scoringTypeNames = array();
$scoringTypesQuery = "SELECT scoring_type as name, scoring_info.display_name, description ".
	"FROM scores, scoring_info WHERE scores.scoring_type = scoring_info.type GROUP BY scoring_type ORDER BY display_name";
$stmt = $db->query($scoringTypesQuery);
$scoringTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$additionalSortingSources = "";
$additionalSortingConditions = "";

$j = 0;
foreach($scoringTypes as $scoringType)
{
	// store scoring system ids
	$scoringSystem = $scoringType['name'];
	$scoringTypeNames[$j] = $scoringSystem;
	
	// store display names and descriptions
	$scoringInfo[$scoringType['name']]['name'] = $scoringType['display_name'];
	$scoringInfo[$scoringType['name']]['description'] = $scoringType['description'];
	
	if( $scoringSystem != 'main' && $scoringSystem == $sortStyle )
	{
		// Create a select list for each scoring type for sorting purposes.
		$additionalSortingSources .= ", (SELECT id, score FROM scores WHERE scoring_type = '".$scoringSystem."' ) ".$scoringSystem."";	
		// create a where condition for each scoring type for sorting purposes
		$additionalSortingConditions .= "AND ".$scoringSystem.".id = brackets.id ";
	}
		
	// create html descriptions of each scoring system
	$scoringTables[$j] = $scoringInfo[$scoringType['name']]['description'];
	$descriptionSafe = str_replace(array("\r", "\n"), '', addslashes($scoringTables[$j]));
	$scoringDescriptions .= "\"".$descriptionSafe."\",";
	
	
	// get rankings for each scoring system
    // Fix: Filter by current view ('main' or 'sweet16') so ranks are relative to peers
	$rankingQuery = "SELECT s.id, s.score FROM scores s, brackets b WHERE s.scoring_type = '".$scoringSystem."' AND s.id = b.id AND b.type = '$view' ORDER BY s.score DESC";
	$rankStmt = $db->query($rankingQuery);
	
	$i = 0;
	$rankCounter = 0;
	$prevScore = -1;
	
	while($entry = $rankStmt->fetch(PDO::FETCH_ASSOC))
	{
		if( $rankCounter == 0 )
		{
			$topScore = $entry['score'];
			$prevScore = $topScore;
			$rankCounter = 1;
			$i=1;
		}
		
		if( $entry['score'] != $prevScore )
		{
			$prevScore = $entry['score'];
			$rankCounter = $i;
		}
		
		$rankings[$scoringSystem][$entry['id']] = $rankCounter;
		$i++;
	}
	
	$j++;
}
$scoringDescriptions .= "\"\"";


?>
	<script type="text/javascript" src="js/tooltips.js"></script>
	<script type="text/javascript">
		

		scoringDescriptions = new Array( <?php echo $scoringDescriptions; ?> );
		
        var showTimer = null;
        var hideTimer = null;

		function showScoring( e, val, delay )
		{
            // Cancel any pending close so we can switch tips smoothly
            if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
            
            // Cancel any pending show to prevent stacking
            if (showTimer) { clearTimeout(showTimer); }
            
            // Schedule the tooltip
            showTimer = setTimeout(function() {
                Tip(scoringDescriptions[val]);
            }, delay);
		}
		
		function clearScoring()
		{
            // If we mouse out before the show timer fires, cancel it!
            if (showTimer) { clearTimeout(showTimer); showTimer = null; }
            
            // Schedule the close
            hideTimer = setTimeout(function() {
                UnTip();
            }, 200);
		}
		
	</script>
	<style type="text/css">
		/* DataTables Overrides for Theme */
		.dataTables_wrapper { color: var(--text-muted); font-size: 0.95rem; }
		
        /* Modern Filter Box */
        .dataTables_filter { margin-bottom: 15px; }
		.dataTables_length select, .dataTables_filter input {
			background: var(--primary-blue); 
            border: 1px solid var(--border-color); 
            color: var(--text-light); 
            padding: 8px 12px; 
            border-radius: 6px;
            outline: none;
            transition: border-color 0.2s;
		}
        .dataTables_filter input {
             min-width: 250px;
        }
        .dataTables_filter input:focus {
            border-color: var(--accent-orange);
            box-shadow: 0 0 0 2px var(--accent-highlight);
        }

		table.dataTable tbody tr { background-color: transparent; }
		table.dataTable tbody tr.thisuser { background-color: var(--accent-highlight) !important; }
		table.dataTable.hover tbody tr:hover, table.dataTable.display tbody tr:hover {
			background-color: rgba(255,255,255,0.05); /* Generic hover */
		}
		.dataTables_info, .dataTables_paginate { color: var(--text-muted) !important; padding-top:15px !important; }
		.paginate_button { color: var(--text-light) !important; border-radius: 4px !important; }
		.paginate_button.current { background: var(--accent-orange) !important; border:none !important; color:white !important; }
        .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_processing, .dataTables_wrapper .dataTables_paginate {
            color: var(--text-muted);
        }
	</style>

	<div id="main" class="full">
		<div class="content-card" style="width:100%; box-sizing:border-box; overflow-x:auto; max-width:1400px; margin:0 auto; align-items:stretch;">

			<div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-color); padding-bottom:15px; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
				<h2 style="margin:0; color:var(--accent-orange);"><i class="fa-solid fa-trophy"></i> Standings</h2>
				<a href="choose.php" style="padding:8px 16px; background:var(--accent-orange); color:var(--accent-text); border-radius:6px; text-decoration:none; font-weight:bold; font-size:0.9em; transition:background 0.2s;"><i class="fa-solid fa-arrow-left"></i> Back to Standings</a>
			</div>
			
			<div class="recentComment" style="margin-bottom:15px; font-style:italic; color:var(--text-muted);">&nbsp;&nbsp;Recent Smack Talk</div>
			
			<div class="table-container">
			  	<table id="standingsTable" class='styled-table display responsive nowrap' style="width:100%; color:var(--text-light);">
			  		<thead>
					<tr style="background:rgba(0,0,0,0.3); border-bottom:1px solid var(--border-color);">
						<th class="no-sort"><div align="center"><strong>Rank</strong></div></th>
						<th><div align="center"><strong>Name</strong></div></th>
						<th><div align="center"><strong>Score</strong></div></th>
						<th><div align="center"><strong>PPR</strong></div></th>
						<th><div align="center"><strong><a href="standings.php?type=best" style="color:var(--accent-orange); text-decoration:none;">Best</a></strong></div></th>
						<th><div align="center"><strong>Tiebreaker</strong></div></th>
						
						<?php
							for( $i=0; $i<count($scoringTypeNames); $i++ )
							{
								echo "<th onmouseover='showScoring( event, ".$i.",0);' onmouseout='clearScoring();' >";
								if( $scoringTypeNames[$i] == $sortStyle )
								{
									echo "<div align=\"center\" class='selected_sort' style='color:var(--accent-orange);'>";
								}
								else
								{
									echo "<div align=\"center\">";
								}
								echo "<strong><a href=\"standings.php?type=normal&sort=".$scoringTypeNames[$i]."\" style='color:var(--text-muted); text-decoration:none;'>";
								echo $scoringInfo[$scoringTypeNames[$i]]['name']."</a></strong></div></th>";
							}
						?>
					</tr>
					</thead>
					<tbody>
					<?php				
                        // Uses explicit toggle to preserve user preference if Main is closed.

                        // Toggle Buttons
                        if(!empty($meta['sweet16Competition'])) {
                            echo '<div style="display:flex; justify-content:flex-start; margin-bottom:25px;">';
                            echo '<div style="background:rgba(255,255,255,0.05); padding:5px; border-radius:30px; display:flex; gap:5px;">';
                            
                            $mainClass = ($view == 'main') ? 'background:var(--accent-orange); color:white;' : 'color:var(--text-muted); hover:text-white';
                            $s16Class = ($view == 'sweet16') ? 'background:var(--accent-orange); color:white;' : 'color:var(--text-muted); hover:text-white';
                            
                            echo '<a href="standings.php?type=normal&view=main" style="padding:8px 20px; border-radius:25px; text-decoration:none; font-weight:bold; transition:all 0.2s; '.$mainClass.'">Main Tournament</a>';
                            echo '<a href="standings.php?type=normal&view=sweet16" style="padding:8px 20px; border-radius:25px; text-decoration:none; font-weight:bold; transition:all 0.2s; '.$s16Class.'">Second Chance</a>';
                            
                            echo '</div></div>';
                        }

						// Fetch Meta Tiebreaker
                        $metaStmt = $db->query("SELECT tiebreaker FROM meta WHERE id=1");
                        $metaTiebreaker = (int)$metaStmt->fetchColumn();

						$query = "SELECT main.id, main.name, main.score, best_main.score AS b_score, brackets.tiebreaker, brackets.63, brackets.email, brackets.eliminated, brackets.person
								FROM 
								scores main, 
								best_scores best_main, 
								brackets 
								
								".$additionalSortingSources."
								
								WHERE 
								main.scoring_type = best_main.scoring_type AND 
								main.id = best_main.id AND 
								main.scoring_type = 'main' AND 
								main.id = brackets.id AND
                                brackets.type = '$view'
								
								".$additionalSortingConditions."
								
								ORDER BY 
								
								".$sortStyle.".score DESC, 
                                ABS(CAST(brackets.tiebreaker AS SIGNED) - $metaTiebreaker) ASC,
                                best_main.score DESC,
								
								main.name ASC";
						
						$result = $db->query($query);
						$eliminated=0;
						$top_score = -1;
						
						$commentMap = getCommentsMap($db);
                        
                        $rankCounter = 0;
                        $prevScore = -1;
                        $prevDiff = -1;
                        $i = 1;

						while($user = $result->fetch(PDO::FETCH_ASSOC))
						{
                            $diff = abs((int)$user['tiebreaker'] - $metaTiebreaker);
                            
                            if ($rankCounter == 0) {
                                $topScore = $user['score'];
                                $prevScore = $topScore;
                                $prevDiff = $diff;
                                $rankCounter = 1;
                                $i = 1;
                            }
                            
                            if ($user['score'] != $prevScore || $diff != $prevDiff) {
                                $prevScore = $user['score'];
                                $prevDiff = $diff;
                                $rankCounter = $i;
                            }
                            
                            // Update ranking array for display logic
                            $rankings[$sortStyle][$user['id']] = $rankCounter;
							$i++;
                            
							$useremail = $user['email'];
							
							if( $top_score < 0 )
							{
								$top_score = $user['score'];
							}

							if (isset($_SESSION['useremail']) && strtolower($useremail) == strtolower($_SESSION['useremail']) && $useremail != "")
							{
								echo '<tr class="thisuser">';
							}
							else
							{
								if( $user['eliminated'] > 0 )
								{
									echo "<tr class='eliminated' style='opacity:0.6;'>";
									$eliminated=1;
								}
								else
								{
									echo "<tr>";
								}
							}
							
							echo "<td align='right' data-order='".$rankings[$sortStyle][$user['id']]."' style='color:var(--text-muted);'>&nbsp;&nbsp;".$rankings[$sortStyle][$user['id']]."</td><td>";
 							
							// Simply allow anyone who is logged in to see the user name
							if (isset($_SESSION['useremail']) == true)
							{
								echo "<a href=\"view.php?id=$user[id]\" style='color:var(--text-light); font-weight:bold;'>" . h(stripslashes($user['name'])) . "</a>" . " <span style='font-size:0.85em; color:var(--text-muted);'>(" . h(stripslashes($user['person'])) . ")</span>";
							}
							else
							{
								echo "<a href=\"view.php?id=$user[id]\" style='color:var(--text-light); font-weight:bold;'>" . h(stripslashes($user['name'])) . "</a>";
							}
							if ($user['eliminated'] > 0 && isset($_SESSION['useremail']) && strtolower($useremail) == strtolower($_SESSION['useremail'] )) {
								echo " - Eliminated";
							}
							if (isset($commentMap[$user['id']]) && $commentMap[$user['id']] > 0) {
								echo "<span class=\"recentComment\"><a href='view.php?id=".$user['id']."#comments' style='color:var(--accent-orange);'>&nbsp;&nbsp;<i class='fa-solid fa-comments'></i></a></span>";
							}
							echo "</td><td data-order='".$user['score']."' style='font-weight:bold; color:var(--text-light);'>";
							echo $user['score'];
							echo "</td><td data-order='".($user['b_score']-$user['score'])."' style='color:#22c55e;'>"; // Green for PPR
							echo $user['b_score']-$user['score'];
							echo "</td><td data-order='".$user['b_score']."' style='color:var(--text-light);'>";
							echo $user['b_score'];
							echo "</td><td style='color:var(--text-muted);'>";
							echo $user['63'];
							echo " - ";
							echo $user['tiebreaker'];
							echo "</td>";
							
							for( $j=0; $j<count($scoringTypeNames); $j++ )
							{
                                $origRank = $rankings['main'][$user['id']];
                                $hypoRank = $rankings[$scoringTypeNames[$j]][$user['id']];
                                $formatted = getRankFormat( $origRank, $hypoRank );
                                // Sort by the hypothetical rank to ensure comparison columns sort by pure performance in that system.
                                echo "<td onmouseover='showScoring( event, ".$j.",2000);' onmouseout='clearScoring();' data-order='".$hypoRank."'>";
								echo $formatted."</td>";
							}
							echo "</tr>";
						}
					?>
					</tbody>
				</table>
			</div>
			
			<script>
			$(document).ready(function() {
				$('#standingsTable').DataTable({
					"paging": true,
					"lengthChange": true,
					"searching": true,
					"ordering": true,
					"info": true,
					"autoWidth": false,
					"responsive": true,
					"pageLength": 25,
					"language": {
                        "search": "",
                        "searchPlaceholder": "Filter Standings...",
						"paginate": { "previous": "Prev", "next": "Next" }
                    },
					"dom": '<"top"f>rt<"bottom"p><"clear">'
				});
			});
			</script>
				<?php
					if( $eliminated==1 ) {
						echo "<span class='eliminated'>&nbsp;&nbsp;Eliminated&nbsp;&nbsp;</span>";
					}
				?>
		</div>

	</div>
</body>
</html>
