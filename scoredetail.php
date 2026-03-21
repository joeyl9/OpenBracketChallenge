<?php
include("admin/functions.php");
include("header.php");

function drawScoringTable( $db, $winners_data, $custompoints, $seedMap,  $roundStart, $roundEnd, $roundNum, $tableWidth )
{
	$totalPointsForRound = 0;
	$winnerListForRound = array();
	$j = 0;
	// Get the winners for this round
	// Get the total points for this round
	for( $i=$roundStart; $i<= $roundEnd; $i++)
	{
		if( $winners_data[$i] != NULL )
		{
			$seedvalue = $seedMap[$winners_data[$i]];
			$gameValue = $custompoints[ $seedvalue ][ $roundNum ];
			$winnerListForRound[$j] = array( $gameValue, $seedvalue, $i, $winners_data[$i] );
			$totalPointsForRound += $gameValue;
			$j++;
		}
	}
	
	// sort winners by reverse order of seed
	$numWinners = $j;
	
	if( $numWinners > 0 )
	{
		rsort($winnerListForRound);
		
		// print out winners header

		echo "<div id='tab-round-".$roundNum."' class='round-tab' style='display:none;'>
        <div style='margin-bottom:30px; width:100%;'>
			<div class='table-container' style='overflow-x:auto;'>
		<table class='scoredetail' style='width:100%; border-collapse:collapse; background:transparent;'>
		<thead>
		<tr class='tableheader' style='border-bottom:2px solid var(--border-color); color:var(--text-light);'>
            <th style='padding:8px 10px; text-align:left; font-weight:bold; color:var(--accent-orange); font-size:0.95em; width:100px;'>Bracket</th>
            <th style='padding:8px 10px; text-align:center; color:var(--accent-orange); font-size:0.95em; width:40px;'>T</th>
            <th style='padding:8px 10px; text-align:center; color:var(--accent-orange); font-size:0.95em; width:40px;'>R".$roundNum."</th>
            <th style='padding:8px 10px; text-align:center; color:var(--accent-orange); font-size:0.95em; width:40px;'>#</th>";
		for( $i = 0; $i < $numWinners; $i++ )
		{
			echo "<th style='padding:6px 4px; text-align:center; font-size:0.8em; color:var(--text-muted); font-weight:normal;' onmouseover=\"showTeam( event, ".$winnerListForRound[$i][2].");\" onmouseout='clearTeam();'>"
				.$winnerListForRound[$i][1]."<br>".$winnerListForRound[$i][0]."</th>";
		}
		echo "</tr></thead><tbody>";
		
		$commentMap = getCommentsMap($db);
		
		// Now, get the list of brackets in score order
		$bracketsQuery = "SELECT scores.id, scores.name, scores.score, brackets.* FROM scores, 
			brackets WHERE scores.scoring_type='main' AND scores.id = brackets.id ORDER BY scores.score DESC, scores.name ASC";
		$stmt = $db->query($bracketsQuery);
		
		$rank = 0;
		$rankCounter = 1;
		$totalScore = 0;
		$totalPointsEarnedByUsers = 0;
		$totalHitsByUsers = 0;
		while($bracket = $stmt->fetch(PDO::FETCH_ASSOC))
		{
			if( $rank==0 )
			{
				$top_score = $bracket['score'];
				$prev_score = $bracket['score'];
				$rankCounter = 1;
				$rank = $rankCounter;
			}
			if( $bracket['score'] != $prev_score ) {
				$prev_score = $bracket['score'];
				$rank = $rankCounter;
			}
			
			$useremail = $bracket['email'];
		
			if (isset($_SESSION['useremail']) && strtolower($useremail) == strtolower($_SESSION['useremail']) && $useremail != "")
			{
				echo '<tr style="background:var(--accent-highlight) !important;">';
			}
			else
			{
				echo "<tr style='border-bottom:1px solid rgba(255,255,255,0.02);'>";
			}
			
			// Simply allow anyone who is logged in to see the user name
			if (isset($_SESSION['useremail']) == true)
			{
				echo "<td style='padding:8px 10px; width:100px; border-bottom:1px solid rgba(255,255,255,0.02);'><a href='view.php?id=".$bracket['id']."' style='color:var(--text-light); text-decoration:none; font-weight:bold; font-size:0.95em;'>".$rank.". ".h(stripslashes($bracket['name']))."</a>" . " <span style='font-size:0.9em; color:var(--text-muted);'>(" . h(stripslashes($bracket['person'])) . ")</span>";
			}
			else
			{
				echo "<td style='padding:8px 10px; width:100px; border-bottom:1px solid rgba(255,255,255,0.02);'><a href='view.php?id=".$bracket['id']."' style='color:var(--text-light); text-decoration:none; font-weight:bold; font-size:0.95em;'>".$rank.". ".h(stripslashes($bracket['name']))."</a>";
			}
			
			
			if (isset($commentMap[$bracket['id']]) && $commentMap[$bracket['id']] > 0) {
				echo " <a href='view.php?id=".$bracket['id']."#comments' style='color:var(--accent-orange); margin-left:5px;'><i class='fa-regular fa-comments'></i></a>";
			}
			
			// Calculate Round Score first
			$roundScore = 0;
			$numHitsForRound = 0;
            $status = array(); // Reset status array
			for( $i = 0; $i < $numWinners; $i++ )
			{
				if( $winnerListForRound[$i][3] == $bracket[ $winnerListForRound[$i][2] ] )
				{
					$status[$i] = "hit";
					if( !isset($winnerListForRound[$i][4]) )
					{
						$winnerListForRound[$i][4] = 1;
					}
					else
					{
						$winnerListForRound[$i][4]++;
					}
					$roundScore += $winnerListForRound[$i][0];
					$numHitsForRound++;
				}
				else
				{
					$status[$i] = "miss";
				}
			}
            $totalEarnedForRound = $roundScore; // Alias if needed downstream

			echo "</td><td style='padding:8px 10px; text-align:center; color:var(--text-light); font-size:0.95em; width:40px; border-bottom:1px solid rgba(255,255,255,0.02);'>".$bracket['score']."</td>
            <td style='padding:8px 10px; text-align:center; color:#22c55e; font-size:0.95em; width:40px; border-bottom:1px solid rgba(255,255,255,0.02);'>".$roundScore."</td>
            <td style='padding:8px 10px; text-align:center; color:var(--text-muted); font-size:0.95em; width:40px; border-bottom:1px solid rgba(255,255,255,0.02);'>".$numHitsForRound."</td>";
			
			$totalScore += $bracket['score'];
			
			$totalPointsEarnedByUsers += $roundScore;
			$totalHitsByUsers += $numHitsForRound;
			
			for( $i = 0; $i < $numWinners; $i++ )
			{
				$bgStyle = ($status[$i] == "hit") ? "background:#22c55e !important;" : "background:#1a2035 !important;";
				echo "<td style='".$bgStyle." width:14px; min-width:14px; padding:0;' onmouseover=\"showTeam( event,".$winnerListForRound[$i][2].");\" onmouseout='clearTeam();'>&nbsp;</td>";
			}
			echo "</tr>";
			
			$rankCounter++;
		}

		$avgPointsThisRound = round( $totalPointsEarnedByUsers/ ($rankCounter-1), 2 );
		$avgScore = round( $totalScore/ ($rankCounter-1), 2);
		$avgHits = round( $totalHitsByUsers/ ($rankCounter-1), 2);
		
        echo "</tbody><tfoot>";
		echo "<tr class='tablefooter' style='border-top:2px solid var(--border-color); color:var(--accent-orange); font-weight:bold;'>
            <td style='padding:8px 10px;'>Averages/Totals</td>
            <td style='padding:8px 10px; text-align:center;'>".$avgScore."</td>
            <td style='padding:8px 10px; text-align:center;'>".$avgPointsThisRound."</td>
            <td style='padding:8px 10px; text-align:center;'>".$avgHits."</td>";
		for( $i = 0; $i < $numWinners; $i++ )
		{
			if (!isset($winnerListForRound[$i][4])) {
				$winnerListForRound[$i][4] = 0;
			}
			echo "<td style='padding:8px 10px; text-align:center;' onmouseover=\"showTeam( event,".$winnerListForRound[$i][2].");\" onmouseout='clearTeam();'>".$winnerListForRound[$i][4]."</td>";
		}
		echo "</tr>";
		echo "</tfoot></table></div></div><br /></div>\n";
	}
}

?>
		<script type="text/javascript" src="js/tooltips.js"></script>
		<style type="text/css">
			.content
			{
				width: 100%;
			}
			
			#main
			{
				width: 100%;
			}

		</style>
		
		<?php
				$winners_query = "SELECT * FROM `master` WHERE `id`=2"; //winners
				$stmt = $db->query($winners_query);
				$winners_data = $stmt->fetch(PDO::FETCH_ASSOC);
				
				$teamNameTable = "'',";
				for( $i=1; $i< 64; $i++)
				{
					// Replacing mysql_real_escape_string with simple addslashes since it's just for JS array
					// Or preferrably json_encode but keeping legacy format
					$teamNameTable .= "'" . addslashes($winners_data[$i]) . "',";
				}
				$teamNameTable .= "''";
		?>
				
		<script type="text/javascript">
		
		teamNames = new Array( <?php echo $teamNameTable ?> );
		
		function showTeam( e, val )
		{
			selectedIndex = -1;
			columnHeader = "";
			if(!e) {
				// Fallback for older browsers (e.g., IE6)
				e = window.event;
			}
			if( e.srcElement )
			{
				selectedIndex = e.srcElement.cellIndex;
				columnHeader = e.srcElement.parentNode.parentNode.parentNode.rows[0].cells[selectedIndex];
			} else {
				// Standard event target selection
				selectedIndex = e.target.cellIndex;
				columnHeader = e.target.parentNode.parentNode.parentNode.rows[0].cells[selectedIndex];
			}
			teamName = teamNames[val];
			headerTxt = teamName + " " + columnHeader.innerHTML;
			Tip(headerTxt,DELAY,0);			
			return true;
		}
		
		function clearTeam()
		{
			UnTip();
			return true;
		}
		
		</script>
		<div id="main" class="full">
			<div class="content-card" style="max-width:100%; margin:0 auto;">
				<div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-color); padding-bottom:15px; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
					<h2 style="margin:0; color:var(--accent-orange);"><i class="fa-solid fa-magnifying-glass"></i> Scoring Detail</h2>
					<a href="choose.php" style="padding:8px 16px; background:var(--accent-orange); color:var(--accent-text); border-radius:6px; text-decoration:none; font-weight:bold; font-size:0.9em; transition:background 0.2s;"><i class="fa-solid fa-arrow-left"></i> Back to Standings</a>
				</div>
				<p style="color:var(--text-muted); margin-bottom:30px;">Detailed breakdown of points earned per round.</p>
				
				<!-- Tabs -->
                <div style="display:flex; justify-content:center; gap:10px; margin-bottom:20px; flex-wrap:wrap;">
                    <button onclick="showRound(1)" class="tab-btn active" id="btn-1">Round 1</button>
                    <button onclick="showRound(2)" class="tab-btn" id="btn-2">Round 2</button>
                    <button onclick="showRound(3)" class="tab-btn" id="btn-3">Round of 16</button>
                    <button onclick="showRound(4)" class="tab-btn" id="btn-4">Quarterfinals</button>
                    <button onclick="showRound(5)" class="tab-btn" id="btn-5">Semifinals</button>
                    <button onclick="showRound(6)" class="tab-btn" id="btn-6">Finals</button>
                </div>

                <script>
                function showRound(r) {
                    // Hide all
                    var tabs = document.getElementsByClassName('round-tab');
                    for(var i=0; i<tabs.length; i++) tabs[i].style.display = 'none';
                    
                    var btns = document.getElementsByClassName('tab-btn');
                    for(var i=0; i<btns.length; i++) btns[i].classList.remove('active');
                    
                    // Show target
                    document.getElementById('tab-round-'+r).style.display = 'block';
                    document.getElementById('btn-'+r).classList.add('active');
                }
                
                // Init
                window.onload = function() {
                   showRound(1);
                };
                </script>
                
                <style>
                .tab-btn {
                    padding: 10px 20px;
                    border-radius: 20px;
                    border: 1px solid var(--border-color);
                    background: rgba(255, 255, 255, 0.1);
                    color: var(--text-light);
                    cursor: pointer;
                    transition: all 0.2s;
                    font-weight: bold;
                }
                .tab-btn:hover {
                    background: var(--accent-orange) !important;
                    color: var(--accent-text) !important;
                    border-color: var(--accent-text);
                    opacity: 1;
                }
                .tab-btn.active {
                    background: var(--accent-orange) !important;
                    color: var(--accent-text) !important;
                    border-color: var(--accent-text);
                    box-shadow: 0 0 10px var(--accent-orange);
                    opacity: 1;
                }
                </style>

				<div align="left" style="overflow-x:auto;">
					<?php				
					$custompoints = getScoringArray($db, 'main');
					$seedMap = getSeedMap($db);
					
					drawScoringTable( $db, $winners_data, $custompoints, $seedMap, 63, 63, 6, 800);
					drawScoringTable( $db, $winners_data, $custompoints, $seedMap, 61, 62, 5, 800);
					drawScoringTable( $db, $winners_data, $custompoints, $seedMap, 57, 60, 4, 800);
					drawScoringTable( $db, $winners_data, $custompoints, $seedMap, 49, 56, 3, 800);
					drawScoringTable( $db, $winners_data, $custompoints, $seedMap, 33, 48, 2, 800);
					drawScoringTable( $db, $winners_data, $custompoints, $seedMap, 1, 32, 1, 800);
					?>
				</div>
                <!-- Bottom link removed -->
			</div>			
		</div>

	</div>
</body>
</html>
