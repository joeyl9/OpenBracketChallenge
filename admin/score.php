<?php

include("database.php");
include 'functions.php';
validatecookie();

function scoreBrackets( $db, $master_data, $loserMap, $roundMap, $seedMap, $scoringType)
{

	$custompoints = getScoringArray($db, $scoringType);
	$query = "SELECT * FROM `brackets`";
	$stmt = $db->query($query);	
	
	while ($user_bracket = $stmt->fetch(PDO::FETCH_ASSOC))
	{
		$score = 0;
		$bestScore = 0;
		
		for($j=1;$j<64;++$j) 
		{
			// SKIP Rounds 1 & 2 for Sweet 16 brackets
			if( (isset($user_bracket['type']) && $user_bracket['type'] == 'sweet16') && $j <= 48 ) {
			    continue;
			}

            // FIXED: Trim user input to match master input
            $userPick = trim((string)$user_bracket[$j]);
            $masterPick = $master_data[$j];

			$lookupKey = strtolower($userPick);

			if($userPick == $masterPick && $user_bracket[$j] != "" )
			{				
				$seedvalue = $seedMap[ $userPick ] ?? 0;
				if(isset($custompoints[$seedvalue][$roundMap[$j]])) {
				    $score += $custompoints[ $seedvalue ][ $roundMap[$j] ];
				}
			}
			
			// calcualte best score
            // Normalized Lookup: Use lowercase trimmed key
            $lookupKey = strtolower($userPick);
            
			// Check if team exists in loserMap (default to false/not eliminated if missing)
			$isEliminated = $loserMap[ $lookupKey ] ?? false;
			
			if( ( $userPick == $masterPick || $isEliminated == false ) 
				&& $user_bracket[$j] != ""  )
			{				
				$seedvalue = $seedMap[ $userPick ] ?? 0;
				if(isset($custompoints[$seedvalue][$roundMap[$j]])) {
				    $bestScore += $custompoints[ $seedvalue ][ $roundMap[$j] ];
				}
			}
			
		}
		
		if ($user_bracket['paid'] > 0)
		{
			// $user_bracket['name'] already fetched
			
			$score_query = "INSERT INTO `scores` (`id`, `name`, `score`, `scoring_type`) VALUES (:id, :name, :score, :type)";
			$stmtInsert = $db->prepare($score_query);
			$stmtInsert->execute([':id' => $user_bracket['id'], ':name' => $user_bracket['name'], ':score' => $score, ':type' => $scoringType]);

			$best_score_query = "INSERT INTO `best_scores` (`id`, `name`, `score`, `scoring_type`) VALUES (:id, :name, :score, :type)";
			$stmtInsert = $db->prepare($best_score_query);
			$stmtInsert->execute([':id' => $user_bracket['id'], ':name' => $user_bracket['name'], ':score' => $bestScore, ':type' => $scoringType]);
		}
	}

}



// 0. Snapshot History (For Movers & Shakers)
// Logic wrapped in function to be callable from update.php streaming
function run_scoringOLD($db, $meta=null) {
    // Legacy wrapper if needed
    run_scoring($db);
}

function run_scoring($db) {
    // Get Meta Tiebreaker
    $metaStmt = $db->query("SELECT tiebreaker FROM meta WHERE id=1");
    $metaTiebreaker = (int)$metaStmt->fetchColumn();

    // Only snapshot if we haven't done it recently (e.g. within 1 Hour)
    $check = $db->query("SELECT id FROM rank_history WHERE timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR) LIMIT 1");
    if (!$check->fetch()) {
        // Calculate current ranks from existing SCORES table before we wipe it
        // Logic similar to dashboard: Score DESC, Tiebreaker Diff ASC
        $query = "SELECT s.id, s.score, bs.score as best_score, b.tiebreaker
                  FROM scores s
                  JOIN best_scores bs ON s.id = bs.id AND s.scoring_type = bs.scoring_type
                  JOIN brackets b ON b.id = s.id
                  WHERE s.scoring_type = 'main'
                  ORDER BY s.score DESC, ABS(CAST(b.tiebreaker AS SIGNED) - ?) ASC, bs.score DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$metaTiebreaker]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        $rank = 0;
        $prev_score = -1;
        $prev_diff = -1;
        $rank_counter = 1;
        
        $historySql = "INSERT INTO rank_history (bracket_id, rank, score, timestamp) VALUES ";
        $historyParams = [];
        $values = [];
        
        if (count($rows) > 0) {
            foreach ($rows as $row) {
                $diff = abs((int)$row['tiebreaker'] - $metaTiebreaker);
                
                if ($prev_score != $row['score'] || $prev_diff != $diff) {
                    $rank = $rank_counter;
                    $prev_score = $row['score'];
                    $prev_diff = $diff;
                }
                
                $values[] = "(?, ?, ?, NOW())";
                $historyParams[] = $row['id'];
                $historyParams[] = $rank;
                $historyParams[] = $row['score'];
                
                $rank_counter++;
            }
            
            $historySql .= implode(", ", $values);
            $stmtHist = $db->prepare($historySql);
            $stmtHist->execute($historyParams);
        }
    }
    
    //completely clear all scoreboards to be repopulated
    $db->exec("TRUNCATE TABLE `scores`");
    $db->exec("TRUNCATE TABLE `best_scores`");
    
    $master_query = "SELECT * FROM `master` WHERE `id`=2"; //winners
    $stmt = $db->query($master_query);
    $master_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Trim Master Data & Normalize Case (Defensive)
    if(is_array($master_data)) {
        foreach($master_data as $k => $v) $master_data[$k] = trim((string)$v);
    }
    
    $seedMap = getSeedMap($db);
    $roundMap = getRoundMap();
    $loserMap = getLoserMap($db);
    
    // Normalize Loser Map Keys (Trim + Lowercase for lookup)
    $normalizedLoserMap = [];
    foreach($loserMap as $k => $v) {
        $key = strtolower(trim($k));
        if($key) $normalizedLoserMap[$key] = $v;
    }
    $loserMap = $normalizedLoserMap;
    
    scoreBrackets( $db, $master_data, $loserMap, $roundMap, $seedMap, 'main');
    scoreBrackets( $db, $master_data, $loserMap, $roundMap, $seedMap, 'geometric');
    scoreBrackets( $db, $master_data, $loserMap, $roundMap, $seedMap, 'espn');
    //scoreBrackets( $db, $master_data, $loserMap, $roundMap, $seedMap, 'fibonacci');
    //scoreBrackets( $db, $master_data, $loserMap, $roundMap, $seedMap, 'odds');
    //scoreBrackets( $db, $master_data, $loserMap, $roundMap, $seedMap, 'constant');
    
    // Update Badges (Progressive/End-Game)
    include_once("badges.php");
    if (class_exists('BadgeManager')) {
        $bm = new BadgeManager($db);
        $allBrackets = $db->query("SELECT id FROM brackets")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($allBrackets as $bid) {
            $bm->awardEndGameBadges($bid);
        }
    }
}

// MAIN EXECUTION (If called directly)
// Check if included or direct
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    
    run_scoring($db);
    
    $_SESSION['msg'] = "Scores Updated!";
    header( 'Location: index.php' );
}
?>

