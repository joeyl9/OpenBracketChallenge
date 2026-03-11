<?php
// admin/update.php
// Orchestrator: Handles Bracket Save -> Scoring -> Calculation
// Streams output to browser for real-time progress.

// ==========================================================
// 1. DISABLE BUFFERING 
// ==========================================================
if (function_exists('apache_setenv')) {
    apache_setenv('no-gzip', 1);
}
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);

// Clear all existing buffers
while (ob_get_level() > 0) {
    ob_end_clean();
}

// Send Headers to prevent server buffering
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Accel-Buffering: no'); // Nginx
header('Content-Encoding: none');

// HELPER: STREAM MSG
function streamStatus($status, $substatus = "") {
    echo "<script>
        document.getElementById('status').innerText = '" . addslashes($status) . "';
        document.getElementById('substatus').innerText = '" . addslashes($substatus) . "';
    </script>";
    // Pad output to force flush (4KB)
    echo str_pad("<!-- flush " .  microtime(true) . " -->", 4096);
    flush(); 
}

include 'functions.php';
validatecookie();
include("database.php");
require_once '_calc_runners.php'; // For V2 Engine

check_admin_auth('limited');

// 2. Output HTML Skeleton
?>
<!DOCTYPE html>
<html>
<head>
    <title>Updating Bracket...</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <style>
        body { background: #0f172a; color: white; display: flex; flex-direction: column; justify-content: center; align-items: center; height: 100vh; font-family: sans-serif; margin:0; }
        .loader { text-align: center; background: rgba(255,255,255,0.05); padding: 40px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.3); min-width: 400px; max-width: 800px; }
        .spinner { font-size: 3rem; color: #f97316; animation: spin 1s linear infinite; margin-bottom: 20px; }
        .success-icon { font-size: 3rem; color: #22c55e; margin-bottom: 20px; display: none; }
        .error-icon { font-size: 3rem; color: #ef4444; margin-bottom: 20px; display: none; }
        
        .status { font-size: 1.5rem; font-weight: 600; margin-bottom: 10px; }
        .substatus { color: #94a3b8; font-size: 0.9rem; }
        
        .log-output {
            margin-top: 20px;
            background: rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 15px;
            max-height: 250px;
            overflow-y: auto;
            width: 100%;
            text-align: left;
            font-family: monospace;
            font-size: 0.8rem;
            color: #ccc;
            box-sizing: border-box;
            display: none; /* Hidden until content */
        }
        .log-output:not(:empty) { display: block; }
        @keyframes spin { 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="loader" id="loader">
        <i class="fas fa-basketball-ball spinner" id="spinner"></i>
        <i class="fas fa-check-circle success-icon" id="successIdx"></i>
        <i class="fas fa-exclamation-triangle error-icon" id="errorIdx"></i>
        
        <div class="status" id="status">Initializing...</div>
        <div class="substatus" id="substatus">Preparing update...</div>
        
        <div class="log-output" id="logContainer">
            <!-- Logs will appear here -->
        </div>
    </div>
<?php
// Force Initial Render
echo str_pad("<!-- flush initial -->", 4096);
flush(); 
$startTime = microtime(true);

try {
    // ... (rest of the file until v2_calc_run) ...

    // PHASE 1: SAVE BRACKET
    // ==========================================================
    streamStatus("Saving bracket...", "Processing form data...");
    usleep(300000); // 300ms visual pause
    
    // [LOGIC BLOCK: Same as original update.php]
    if($_GET['id'] == 0) { // MASTER BRACKET
        // Fetch current master bracket (ID=2)
        $stmt = $db->query("SELECT * FROM `master` WHERE id=2");
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $updates = [];
        if($current) {
            for($i=1; $i<=63; $i++) {
                $val = $_POST[$i] ?? '';
                if($val !== ($current[$i] ?? '')) {
                   $old = $current[$i] ?: 'TBD';
                   $new = $val ?: 'Empty';
                   $updates[] = "Game $i: $old -> $new";
                }
            }
        }
        
        $logAction = "UPDATE_MASTER";
        $logMsg = !empty($updates) ? "Updated Master: " . implode(", ", array_slice($updates, 0, 5)) : "Updated Master (No changes)";
        log_admin_action($logAction, $logMsg);

        // create an empty array to use for array composition so all members are always set.
        for( $i=1; $i<=64; $i++ ) {
            $p[$i]="";
        }
        $_POST = $_POST + $p;


        // UPDATE WINNERS (MASTER ID=2)
        $stmt = $db->query("SELECT * FROM `master` WHERE id=2");
        $params = [];
        for($i=1; $i<=63; $i++) $params[] = $_POST[$i];
        
        if(!($stmt->fetch(PDO::FETCH_ASSOC))) {
            $placeholders = implode(',', array_fill(0, 63, '?'));
            $query = "INSERT INTO `master` (`id`,`1`,`2`,`3`,`4`,`5`,`6`,`7`,`8`,`9`,`10`,`11`,`12`,`13`,`14`,`15`,`16`,`17`,`18`,`19`,`20`,`21`,`22`,`23`,`24`,`25`,`26`,`27`,`28`,`29`,`30`,`31`,`32`,`33`,`34`,`35`,`36`,`37`,`38`,`39`,`40`,`41`,`42`,`43`,`44`,`45`,`46`,`47`,`48`,`49`,`50`,`51`,`52`,`53`,`54`,`55`,`56`,`57`,`58`,`59`,`60`,`61`,`62`,`63`) VALUES (2,$placeholders)";
            $stmt = $db->prepare($query);
            $stmt->execute($params);
        } else {
            $setClause = [];
            for($i=1; $i<=63; $i++) $setClause[] = "`$i`=?";
            $query = "UPDATE `master` SET " . implode(',', $setClause) . " WHERE `id`=2";
            $stmt = $db->prepare($query);
            $stmt->execute($params);
        }
        
        // Eliminate conflicting endgames
        $db->exec("UPDATE `end_games` SET `eliminated`=false");
        
        // Build parameterized elimination query 
        $allowedCols = range(49, 63);
        $conditions = [];
        $params = [];
        foreach ($allowedCols as $col) {
            if (!empty($_POST[$col])) {
                $conditions[] = "`" . $col . "` != ?";
                $params[] = $_POST[$col];
            }
        }
        if (!empty($conditions)) {
            $sql = "UPDATE `end_games` SET `eliminated` = true WHERE " . implode(" OR ", $conditions);
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        }
        
        // Update Possible Scores Elimination
        $db->exec("UPDATE possible_scores p, end_games e SET p.eliminated = e.eliminated WHERE e.id = p.outcome_id and `type`='path_to_victory'");
        $db->exec("UPDATE possible_scores_eliminated p, end_games e SET p.eliminated = e.eliminated WHERE e.id = p.outcome_id and `type`='path_to_victory'");
        $db->exec("INSERT into possible_scores_eliminated SELECT * from possible_scores p WHERE p.eliminated = true and `type`='path_to_victory'");
        $db->exec("INSERT into possible_scores SELECT * from possible_scores_eliminated p WHERE p.eliminated = false and `type`='path_to_victory'");
        $db->exec("DELETE from possible_scores WHERE eliminated = true and `type`='path_to_victory'");
        $db->exec("DELETE from possible_scores_eliminated WHERE eliminated = false and `type`='path_to_victory'");

        // Handle Meta (Sweet 16 Status)
        $sweet16 = true;
        for($i=33;$i<=48;++$i) { if( $_POST[$i]=="" ) { $sweet16 = false; break; } }
        $pastSweet16 = false;
        for($i=49;$i<=63;++$i) { if( $_POST[$i]!="" ) { $pastSweet16 = true; break; } }
        
        $meta = $db->query("SELECT * FROM `meta` WHERE `id`=1")->fetch(PDO::FETCH_ASSOC);
        if( $meta['sweet16Competition'] == false ) {
            if($sweet16) $db->exec("UPDATE `meta` SET `closed`=1, `sweet16`=1 WHERE `id`=1");
            else $db->exec("UPDATE `meta` SET `closed`=1, `sweet16`=0 WHERE `id`=1");
        } else {
            if($pastSweet16) $db->exec("UPDATE `meta` SET `closed`=1, `sweet16`=1 WHERE `id`=1");
            else $db->exec("UPDATE `meta` SET `closed`=0, `sweet16`=1 WHERE `id`=1");
        }

        // UPDATE LOSERS (MASTER ID=3)
        $teams = $db->query("SELECT * FROM `master` WHERE `id`=1")->fetch(PDO::FETCH_ASSOC);
        $childGraph = []; $childCounter = 0;
        for( $i=33; $i < 64; $i++ ) {
            $childGraph[$i][0] = ++$childCounter;
            $childGraph[$i][1] = ++$childCounter;
        }
        $losers = $p;
        $j=1;
        for($i=1;$i<=32; ++$i) {
            if($_POST[$i] != NULL) {
                // FIXED: Trim inputs for robust comparison
                $postPick = trim((string)$_POST[$i]);
                $team1 = trim((string)$teams[$j]);
                
                if( $team1 == $postPick ) $losers[$i] = $teams[$j+1];
                else $losers[$i] = $teams[$j];
            }
            $j += 2;
        }
        for($i=33;$i<64; ++$i) {
            if($_POST[$i] != NULL) {
                // FIXED: Trim inputs for robust comparison
                $postPick = trim((string)$_POST[$i]);
                $child1 = trim((string)$_POST[ $childGraph[$i][0] ]);
                
                if( $child1 == $postPick ) $losers[$i] = $_POST[ $childGraph[$i][1] ];
                else $losers[$i] = $_POST[ $childGraph[$i][0] ];
            }
        }
        
        // Save Losers
        $stmt = $db->query("SELECT * FROM `master` WHERE id=3");
        $lParams = [];
        for($i=1; $i<=63; $i++) $lParams[] = $losers[$i];
        
        if(!($stmt->fetch(PDO::FETCH_ASSOC))) {
            $placeholders = implode(',', array_fill(0, 63, '?'));
            $query = "INSERT INTO `master` (`id`,`1`,`2`,`3`,`4`,`5`,`6`,`7`,`8`,`9`,`10`,`11`,`12`,`13`,`14`,`15`,`16`,`17`,`18`,`19`,`20`,`21`,`22`,`23`,`24`,`25`,`26`,`27`,`28`,`29`,`30`,`31`,`32`,`33`,`34`,`35`,`36`,`37`,`38`,`39`,`40`,`41`,`42`,`43`,`44`,`45`,`46`,`47`,`48`,`49`,`50`,`51`,`52`,`53`,`54`,`55`,`56`,`57`,`58`,`59`,`60`,`61`,`62`,`63`) VALUES (3,$placeholders)";
            $stmt = $db->prepare($query);
            $stmt->execute($lParams);
        } else {
            $setClause = [];
            for($i=1; $i<=63; $i++) $setClause[] = "`$i`=?";
            $query = "UPDATE `master` SET " . implode(',', $setClause) . " WHERE `id`=3";
            $stmt = $db->prepare($query);
            $stmt->execute($lParams);
        }
        
        $tiebreaker = (isset($_POST['tiebreaker']) && $_POST['tiebreaker'] !== '') ? (int)$_POST['tiebreaker'] : null;
        $db->prepare("UPDATE `meta` SET `tiebreaker`=:tiebreaker WHERE `id`=1")->execute([':tiebreaker' => $tiebreaker]);
        
    } else {
        // USER BRACKET (ID != 0)
        $bId = $_GET['id'];
        $stmt = $db->prepare("SELECT * FROM `brackets` WHERE `id`=?");
        $stmt->execute([$bId]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $bName = $current['name'] ?? "ID $bId";
        log_admin_action('UPDATE_BRACKET', "Updated Bracket for $bName");

        $bParams = [$_POST['tiebreaker']];
        for($i=1; $i<=63; $i++) $bParams[] = $_POST[$i];
        $bParams[] = $_GET['id'];

        $setClause = "`tiebreaker`=?";
        for($i=1; $i<=63; $i++) $setClause .= ", `$i`=?";

        $query = "UPDATE `brackets` SET $setClause WHERE `id`=?";
        $stmt = $db->prepare($query);
        $stmt->execute($bParams);
    }
    
    streamStatus("Bracket saved!", "Moving to scoring...");
    usleep(300000); 

    // ==========================================================
    // PHASE 2: SCORING
    // ==========================================================
    streamStatus("Updating scores...", "Calculating points & standings...");
    
    // Include Score Logic (it now has run_scoring() function)
    require_once 'score.php';
    run_scoring($db);
    
    streamStatus("Scores updated!", "Leaderboards refined.");
    usleep(300000); 

    // ==========================================================
    // PHASE 3: CALCULATE PATHS
    // ==========================================================
    // Only run if gamesLeft < 11, matching legacy logic
    $gamesLeft = 0;
    $master_data = $db->query("SELECT * FROM `master` WHERE `id`=2")->fetch(PDO::FETCH_ASSOC);
    for( $i=1; $i<64; $i++ ) { if( $master_data[$i] == "" ) $gamesLeft++; }

    if( $gamesLeft < 16 ) {
        streamStatus("Running path calculations...", "Refining 50,000+ possibilities...");
        echo "<script>document.getElementById('icon').className = 'fas fa-cog spinner';</script>"; flush();
        
        // Use 'smart' mode by default, allow truncate for admin speed
        // Wrap output in a script to move it to the log container
        echo "<script>
            const logContainer = document.getElementById('logContainer');
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => {
                        // Check if it's one of our log divs (inline styles usually)
                        if (node.nodeName === 'DIV' && node.style && node.style.fontFamily === 'monospace') {
                            logContainer.appendChild(node);
                            logContainer.scrollTop = logContainer.scrollHeight;
                        }
                    });
                });
            });
            observer.observe(document.body, { childList: true });
        </script>";
        
        $result = v2_calc_run($db, [
            'mode' => 'full',
            'truncate' => true,
            'allow_web_truncate' => true
        ]);
        
        echo "<script>observer.disconnect();</script>";
        
        if (isset($result['error'])) {
            throw new Exception("Calc Failed: " . $result['error']);
        }
        
        $timeStr = number_format($result['runtime_ms'], 2);
        streamStatus("Calculations complete!", "Engine finished in $timeStr ms.");

        // Populate Cache Table to prevent locking on frontend
        streamStatus("Building Summary Cache...", "Optimizing for fast display...");
        $db->exec("CREATE TABLE IF NOT EXISTS `endgame_summary` (
          `bracket_id` int(11) NOT NULL,
          `rank` int(11) NOT NULL,
          `num_paths` int(11) NOT NULL,
          `p_win` float DEFAULT 0,
          PRIMARY KEY (`bracket_id`, `rank`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $db->exec("TRUNCATE TABLE endgame_summary");
        
        $cacheSql = "INSERT INTO endgame_summary (bracket_id, rank, num_paths, p_win)
                SELECT 
                    ps.bracket_id, 
                    ps.rank, 
                    COUNT(*) as num_paths,
                    COALESCE(pow.probability_win, 0) as p_win
                FROM possible_scores ps
                LEFT JOIN probability_of_winning pow ON pow.id = ps.bracket_id AND pow.rank = ps.rank
                WHERE ps.type='path_to_victory' AND ps.eliminated=0
                GROUP BY ps.bracket_id, ps.rank";
        $db->exec($cacheSql);
    } else {
        streamStatus("Skipping deep calculations", "Not needed until Elite 8 (Games Left: $gamesLeft)");
    }
    
    // ==========================================================
    // FINAL SUCCESS
    // ==========================================================
    $totalTime = number_format(microtime(true) - $startTime, 2);
    $_SESSION['msg'] = "Update Complete ($totalTime s)";
    
    echo "<script>
        document.getElementById('spinner').style.display = 'none';
        document.getElementById('successIdx').style.display = 'inline-block';
        document.getElementById('status').innerText = 'All Done!';
        document.getElementById('substatus').innerText = 'Total time: $totalTime s. Redirecting...';
    </script>";
    flush();
    
    echo "<script>setTimeout(function() { window.location.href = 'index.php'; }, 1500);</script>";

} catch (Exception $e) {
    // ERROR HANDLER
    $err = addslashes($e->getMessage());
    echo "<script>
        document.getElementById('spinner').style.display = 'none';
        document.getElementById('errorIdx').style.display = 'inline-block';
        document.getElementById('status').innerText = 'Update Failed';
        document.getElementById('substatus').innerText = '$err';
    </script>";
}

echo "<?php include('footer.php'); ?>
</body>
</html>";
?>


