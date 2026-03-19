<?php

function drawEndGames( $pageMode, $id, $rank, $endgameIds, $db )
{

	$commentMap = getCommentsMap($db);

?>

<style type="text/css">
	.content
	{
		width:100%;
	}
	
	#main
	{
		width:100%;
        max-width: 100%;
	}
	.scoredetail td
	{
		text-align:center;
	}

</style>
<script type="text/javascript" src="js/tooltips.js"></script>
<script type="text/javascript">
				
			
	function showTip( e, val, delay )
	{
		if(!e)
		{
			Tip(val,DELAY,delay, FADEIN, 200, FADEOUT, 200);
			
		}
		else
		{
			// firefox and safari
			Tip(val,DELAY,delay, FADEIN, 200, FADEOUT, 200);
		}
							
		return true;
	}
	
	function clearTip()
	{
		UnTip();
		return true;
	}
	
</script>
<div id="main" class='widetable'>
	<?php
		$roundMap = getRoundMap();
		$seedMap = getSeedMap($db);
		$scoring = getHistoricalProbabilities();
		$childGraph = getChildGraph();
		
		if( $pageMode == "view_all" || $pageMode == "bracket" || $pageMode == "selected_end_games" )
		{ 
			if( $pageMode == "view_all" )
			{
				$bracket_query = "select count(*) num_paths from possible_scores p where `rank`='".$rank."' and p.`type`='path_to_victory'";
				$stmt = $db->query($bracket_query);
				$bracket_data = $stmt->fetch(PDO::FETCH_ASSOC);
			}
			else if( $pageMode == "selected_end_games")
			{
				$bracket_data['num_paths'] = count($endgameIds);
			}
			else
			{
				$probQuery = "SELECT `probability_win` FROM `probability_of_winning` WHERE `id` = '".$id."' and `rank`='".$rank."'";
				$stmt = $db->query($probQuery);
				$prob_data = $stmt->fetch(PDO::FETCH_ASSOC);
				$pBracketWin = $prob_data['probability_win'];
				
				$bracket_query = "select name, count(*) num_paths, id from brackets b, possible_scores p where b.id = p.bracket_id and id ='".$id."' and `rank`='".$rank."' and p.`type`='path_to_victory' group by b.name";
				$stmt = $db->query($bracket_query);
				$bracket_data = $stmt->fetch(PDO::FETCH_ASSOC);
			}
			
						
			if( $pageMode == "view_all" )
			{
				$titleText = "End Game Scenarios For Everyone";
			}
			else if( $pageMode == "selected_end_games")
			{
				$titleText = "Selected End Game Scenarios";
			}
			else
			{
				$titleText = "End Game Scenarios For ".$bracket_data['name'];				
			}
		
		?>
			<div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-color); padding-bottom:15px; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
				<div style="flex:1;">
					<h3 style="margin:0; color:var(--accent-orange); font-size:1.5rem;"><i class="fas fa-flag-checkered"></i> <?php echo $titleText; ?></h3>
					<?php if( $pageMode != "view_all" && $pageMode != "selected_end_games" ) { ?>
						<div style="color:var(--text-muted); font-size:0.95rem; margin-top:5px;">
							<strong style="color:var(--text-light);"><?php echo $bracket_data['num_paths']; ?></strong> paths to #1 
							&nbsp;|&nbsp; 
							Win Probability: <strong style="color:var(--accent-orange);"><?php echo number_format($pBracketWin * 100, 2); ?>%</strong>
						</div>
					<?php } else { ?>
						<div style="color:var(--text-muted); font-size:0.95rem; margin-top:5px;">
							<strong style="color:var(--text-light);"><?php echo $bracket_data['num_paths']; ?></strong> Scenarios
						</div>
					<?php } ?>
				</div>

				<div style="display:flex; gap:10px;">
					<?php if( isset($id) ) { ?>
						<a href="endgamesummary.php?rank=<?php echo $rank; ?>" style="padding:8px 16px; background:var(--secondary-blue); border:1px solid var(--border-color); color:var(--text-muted); border-radius:6px; text-decoration:none; font-weight:bold; font-size:0.9em; transition:all 0.2s;" onmouseover="this.style.borderColor='var(--accent-orange)'; this.style.color='var(--text-light)';" onmouseout="this.style.borderColor='var(--border-color)'; this.style.color='var(--text-muted)';">
							<i class="fas fa-arrow-left"></i> Back to Summary
						</a> 
					<?php } ?>
					
					<?php if( $pageMode == "bracket" ) { ?>
						<a href="view.php?id=<?php echo $bracket_data['id']; ?>" style="padding:8px 16px; background:var(--accent-orange); color:white; border-radius:6px; text-decoration:none; font-weight:bold; font-size:0.9em; transition:background 0.2s;">
							<i class="fas fa-eye"></i> View Bracket
						</a>
					<?php } ?>
				</div>
			</div>
		<?php
			
				$winners_query = "SELECT * FROM `master` WHERE `id`=2"; //winners
				$stmt = $db->query($winners_query);
				$winners_data = $stmt->fetch(PDO::FETCH_ASSOC);
				
				if( $pageMode == "view_all" )
				{
					$end_game_query = 
						"select b.*, e.id eid, score, name, e.`49` as g49, e.`50` as g50, e.`51` as g51, e.`52` as g52, e.`53` as g53, e.`54` as g54, e.`55` as g55, e.`56` as g56, e.`57` as g57, e.`58` as g58, e.`59` as g59, e.`60` as g60, e.`61` as g61, e.`62` as g62, e.`63` as g63 ".
						"from possible_scores p, brackets b, end_games e ".
						"where e.eliminated = false and p.bracket_id = b.id and e.id = outcome_id and e.round='7'  and p.`type`='path_to_victory' and `rank`=".$rank.
						" order by `name`, e.`63`,e.`62`,e.`61`,e.`60`,e.`59`,e.`58`,e.`57`,e.`56`,e.`55`,e.`54`,e.`53`,e.`52`,e.`51`,e.`50`,e.`49`, score";
				
                    // DEBUG
                    error_log("EndGame Query (Rank $rank): " . $end_game_query);
                }
				else if( $pageMode == "selected_end_games")
				{
					$endgameList = "(";
					
					foreach( $endgameIds as $id )
					{
						$endgameList .= $id.",";
					}
					
					$endgameList .= " -1)";
				
					$end_game_query = 
						"select b.*, e.id eid, score, name, e.`49` as g49, e.`50` as g50, e.`51` as g51, e.`52` as g52, e.`53` as g53, e.`54` as g54, e.`55` as g55, e.`56` as g56, e.`57` as g57, e.`58` as g58, e.`59` as g59, e.`60` as g60, e.`61` as g61, e.`62` as g62, e.`63` as g63 ".
						"from possible_scores p, brackets b, end_games e ".
						"where p.bracket_id = b.id and e.id = outcome_id and p.`type`='path_to_victory' and `rank`=".$rank." and outcome_id in ".$endgameList.
						" order by `name`, e.`63`,e.`62`,e.`61`,e.`60`,e.`59`,e.`58`,e.`57`,e.`56`,e.`55`,e.`54`,e.`53`,e.`52`,e.`51`,e.`50`,e.`49`, score";						
				}
				else
				{
					$end_game_query = 
						"select b.*, e.id eid, score, name, e.`49` as g49, e.`50` as g50, e.`51` as g51, e.`52` as g52, e.`53` as g53, e.`54` as g54, e.`55` as g55, e.`56` as g56, e.`57` as g57, e.`58` as g58, e.`59` as g59, e.`60` as g60, e.`61` as g61, e.`62` as g62, e.`63` as g63 ".
						"from possible_scores p, brackets b, end_games e ".
						"where e.eliminated = false and b.id='".$id."'  and p.`type`='path_to_victory'and p.bracket_id = b.id and e.id = outcome_id and e.round='7' and `rank`=".$rank.
						" order by e.`63`,e.`62`,e.`61`,e.`60`,e.`59`,e.`58`,e.`57`,e.`56`,e.`55`,e.`54`,e.`53`,e.`52`,e.`51`,e.`50`,e.`49`";
				}
				
				$stmt = $db->query($end_game_query);
				
                echo "<div style='overflow-x:auto; margin-top:20px; border-radius:8px; border:1px solid var(--border-color);'>";
				echo "<table class='scoredetail' border='1' cellpadding='3' style='width:100%; min-width:1400px;'>";
				echo "<tr class='tableheader'>\n";
				echo "<td>#</td>";
				if( $pageMode == "view_all" || $pageMode == "selected_end_games" )
				{
					echo "<td>Winner</td>";
				}
				echo "<td>Bracket Score</td>";
				echo "<td colspan='8'>Round of 16 Winners</td>";
				echo "<td colspan='4'>Quarterfinals Winners</td>";
				echo "<td colspan='2'>Semifinals Winners</td>";
				echo "<td>Champion</td>";
				echo "<td>P(Win)</td>";
				echo "</tr>";
				
				
				
				$i =1;
				while($bracket = $stmt->fetch(PDO::FETCH_ASSOC))
				{
                    // DEBUG: Check if g49 alias is present and populated
                    if ($i == 1) {
                        error_log("Endgame Row Data (Aliased): " . print_r($bracket, true));
                    }

					echo "<tr>\n";
					echo "<td><a href='if.php?id=".$bracket['eid']."'>".$i."</a></td>\n";
					if( $pageMode == "view_all" || $pageMode == "selected_end_games" )
					{
						echo "<td><a href='view.php?id=".$bracket['id']."'>".h(stripslashes($bracket['name']))."</a>";
						if (isset($commentMap[$bracket['id']]) && $commentMap[$bracket['id']] > 0) {
							echo " <span class=\"recentComment\"><a href='view.php?id=".$bracket['id']."#comments'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</a></span>";
						}
						echo "</td>\n";
					}
					echo "<td>".$bracket['score']."</td>\n";
					
					$probability = 1;
					
					for( $j=49; $j<64; $j++ )
					{
						if( $winners_data[$j] != NULL && $winners_data[$j] == $bracket['g'.$j] )
						{
							echo "<td class='right'>".$bracket['g'.$j]."</td>\n";
						}
						else
						{

							// figure out the seed of the predicted loser
							// check the master bracket for the loser
							$loser = "";
							
							$child[0] = $winners_data[$childGraph[$j][0]];
							$child[1] = $winners_data[$childGraph[$j][1]];
							$child[2] = $bracket['g'.$childGraph[$j][0]] ?? ($bracket[$childGraph[$j][0]] ?? null);
							$child[3] = $bracket['g'.$childGraph[$j][1]] ?? ($bracket[$childGraph[$j][1]] ?? null);

							foreach( $child as $team )
							{
								if( $team != null and $team != $bracket['g'.$j] )
								{
									$loser = $team;		
									break;	
								}
							}									
							
							//echo  $roundMap[$j]." - ".$seedMap[$bracket[$j]].". ".$bracket[$j]." v ".$seedMap[$loser].". ".$loser." = ".$scoring[ $roundMap[$j] ][ $seedMap[$bracket[$j]] ][ $seedMap[$loser] ]."<br>";
							$special = "";
							$round = isset($roundMap[$j]) ? $roundMap[$j] : null;
							$winSeed = isset($seedMap[$bracket['g'.$j]]) ? $seedMap[$bracket['g'.$j]] : null;
							$loseSeed = isset($seedMap[$loser]) ? $seedMap[$loser] : null;
							
							$pWin = ( $round && $winSeed && $loseSeed && isset($scoring[$round][$winSeed][$loseSeed]) ) ? $scoring[$round][$winSeed][$loseSeed] : null;
							if( $pWin == null || $pWin <= 0 || $pWin >= 1 )
							{
								$special = " imputed (original: ".$pWin.")";
								$winSeed = (int)($seedMap[$bracket['g'.$j]] ?? 16);
								$loseSeed = (int)($seedMap[$loser] ?? 16);
                                $totalSeeds = $winSeed + $loseSeed;
								$pWin = ($totalSeeds > 0) ? (1 - ($winSeed / $totalSeeds)) : 0.5;
							}
							
							echo "<td onmouseover=\"showTip(event,'".$pWin.$special."')\"  onmouseout=\"clearTip()\" >".$bracket['g'.$j]."</td>\n";
							
							$probability *= $pWin;
						}
					}
					
					echo "<td onmouseover=\"showTip(event,".$probability.")\"  onmouseout=\"clearTip()\" >".number_format($probability,4)."</td>";

					echo "</tr>\n";
					$i++;
				}
		
				echo "</table></div>\n";
			}
		
		?>
		

	</div>
</div>


<?php

}

?>