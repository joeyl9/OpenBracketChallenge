<?php

class CalcEngine {
    private $db;
    private $opts;
    private $roundMap;
    private $seedMap;
    private $childGraph;
    private $scoringArray; // Points
    private $historicalProbs; // Percents
    private $maxScoreRanks = 10;
    
    // Limits
    private $startTime;
    private $maxRuntime = 600.0; // Default CLI limit
    private $iterationCount = 0;
    private $maxIterations = 10000000;
    
    // Logging State
    private $lastLogTime = 0.0;
    
    // State
    private $lockName;
    private $memCacheMaster = null;

    // Retry Logic
    private $dbDsn;
    private $dbUser;
    private $dbPass;
    private $dbOptions;

    public function __construct(PDO $db, array $opts = []) {
        $this->db = $db;
        $this->opts = $opts;
        $this->startTime = microtime(true);
        
        // Runtime Resilience
        if (function_exists('set_time_limit')) {
            set_time_limit(0);
        }

        // Capture DB connection details for reconnect logic
        if (isset($opts['db_config'])) {
            $this->dbDsn = $opts['db_config']['dsn'];
            $this->dbUser = $opts['db_config']['user'];
            $this->dbPass = $opts['db_config']['pass'];
            $this->dbOptions = $opts['db_config']['options'];
        }
        
        // Definition: Lock Name
        $stmt = $this->db->query("SELECT DATABASE()");
        $dbName = $stmt->fetchColumn();
        $this->lockName = 'mm_calc_' . $dbName;
        
        // Determine Max Runtime
        if (isset($opts['max_runtime'])) {
            $this->maxRuntime = (float)$opts['max_runtime'];
        } else {
            // Defaults based on environment if not provided
            if (php_sapi_name() === 'cli') {
                $this->maxRuntime = 600.0;
            } else {
                // Web Defaults
                $mode = $opts['mode'] ?? 'quick';
                if ($mode === 'smart' || $mode === 'full') {
                    $this->maxRuntime = 900.0;
                } else {
                    $this->maxRuntime = 300.0;
                }
            }
        }
        
        // CLI Override check (if user didn't provide specific override, we might want to ensure CLI is higher, but logic above handles it)
        if (php_sapi_name() === 'cli') {
            $this->maxIterations = 10000000;
        }

        $this->log("Initialized Engine V2. Mode: " . ($opts['mode'] ?? 'unknown') . " | MaxRuntime: " . $this->maxRuntime . "s");
    }

    public function setRoundMap(array $map) { $this->roundMap = $map; }
    public function setSeedMap(array $map) { $this->seedMap = $map; }
    public function setChildGraph(array $graph) { $this->childGraph = $graph; }
    public function setScoringArray(array $scoring) { $this->scoringArray = $scoring; }
    public function setHistoricalProbabilities(array $probs) { $this->historicalProbs = $probs; }

    // --- Hardening: Instrumentation ---
    private function log($msg, $ctx = []) {
        $time = number_format(microtime(true) - $this->startTime, 3);
        $mem = number_format(memory_get_usage() / 1024 / 1024, 2);
        $line = "[$time"."s | {$mem}MB] $msg";
        if (!empty($ctx)) {
            $line .= " " . json_encode($ctx);
        }
        
        // Output to error_log (Always)
        error_log("CalcEngine: $line");
        
        // Output to Browser (if Web) - Throttled
        if (php_sapi_name() !== 'cli') {
            $now = microtime(true);
            
            // Check allow list phrases for immediate output
            $isPriority = (strpos($msg, 'Phase:') !== false 
                        || strpos($msg, 'Starting Run') !== false
                        || strpos($msg, 'Run Complete') !== false
                        || strpos($msg, 'CRITICAL') !== false);
            
            // Throttle: Only echo if priority OR > 2.0s passed
            if ($isPriority || ($now - $this->lastLogTime >= 2.0)) {
                echo "<div style='font-family:monospace; color:#888; font-size:12px; border-bottom:1px solid #333; padding:2px;'>$line</div>";
                
                if (ob_get_level() > 0) ob_flush();
                flush();
                
                $this->lastLogTime = $now;
            }
        }
    }

    // --- Hardening: Reconnect ---
    private function reconnect() {
        $this->log("Attempting Reconnect (MySQL 2006/2013)...");
        $this->db = null; // Close old
        
        try {
            if ($this->dbDsn && $this->dbUser) {
                $this->db = new PDO($this->dbDsn, $this->dbUser, $this->dbPass, $this->dbOptions);
                $this->log("Reconnect Successful.");
            } else {
                throw new Exception("Reconnect failed: No stored DSN/User credentials.");
            }
        } catch (Exception $e) {
            $this->log("Reconnect Failed: " . $e->getMessage());
            throw $e;
        }
    }

    // --- Hardening: Safe Execute ---
    private function safeExecute($sql, $params = [], $label = '') {
        $stmt = null;
        try {
            // Attempt 1
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $code = $e->errorInfo[1] ?? 0;
            
            // 1205 = Lock Wait Timeout (Abort immediately)
            if ($code == 1205) {
                 $this->log("MySQL Error 1205 (Lock Wait Timeout) during '$label'. Aborting safely.");
                 throw new Exception("Lock wait timeout; another process is using the database. Please try again later.");
            }

            // 2006 = Server gone away, 2013 = Lost connection
            if ($code == 2006 || $code == 2013) {
                
                // Transaction Safety Check
                if ($this->db->inTransaction()) {
                    $this->log("MySQL Error $code inside ACTIVE TRANSACTION during '$label'. Cannot auto-reconnect safely. Aborting.");
                    throw new Exception("Lost connection inside active transaction ($code). Aborting to prevent partial writes. Please retry the entire process.");
                }

                $this->log("MySQL Error $code during '$label'. Retrying...");
                $this->reconnect();
                // Attempt 2 (Reprepare on new DB)
                try {
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute($params);
                    return $stmt;
                } catch (Exception $ex) {
                    $this->log("Retry Failed for '$label'. Aborting.");
                    throw $ex; 
                }
            }
            throw $e; // Rethrow other errors
        }
    }
    
    // --- Hardening: Chunked Delete ---
    private function safeChunkedDelete($table, $whereCond, $params, $label) {
        // Whitelist Check
        $allowed = ['possible_scores', 'possible_scores_eliminated', 'probability_of_winning', 'end_games'];
        if (!in_array($table, $allowed)) {
            throw new Exception("Security Violation: Table '$table' not allowed in safeChunkedDelete");
        }

        // Set lock wait timeout to 5 seconds to fail fast (Try/Catch wrapper)
        try {
            $this->safeExecute("SET SESSION innodb_lock_wait_timeout = 5", [], 'SetLockTimeout');
        } catch (Exception $e) {
            $this->log("Warning: Could not set innodb_lock_wait_timeout. Continuing...");
        }
        
        $totalDeleted = 0;
        
        while (true) {
            $this->checkLimits();
            
            // Build Query with Backticks
            $sql = "DELETE FROM `$table` WHERE $whereCond";
            
            // Deterministic Deletion for high-churn tables
            if ($table === 'possible_scores' || $table === 'possible_scores_eliminated') {
                $sql .= " ORDER BY outcome_id, bracket_id";
            }
            
            $sql .= " LIMIT 5000";
            
            $stmt = $this->safeExecute($sql, $params, "$label (Chunk)");
            $cnt = $stmt->rowCount();
            $totalDeleted += $cnt;
            
            if ($cnt > 0) {
                 $this->log("$label: Deleted $cnt rows (Total: $totalDeleted)...");
            }
            
            // If less than limit, we are done
            if ($cnt < 5000) break;
            
            // Yield briefly
            usleep(10000); // 10ms
        }
    }

    public function run() {
        $mode = $this->opts['mode'] ?? 'quick';
        
        // Acquire Lock
        $timeout = (php_sapi_name() === 'cli') ? 10 : 0;
        try {
            $stmt = $this->safeExecute("SELECT GET_LOCK(:name, :timeout)", [':name' => $this->lockName, ':timeout' => $timeout], 'GetLock');
            if (!$stmt->fetchColumn()) {
                throw new Exception("Could not acquire lock: " . $this->lockName);
            }
        } catch (Exception $e) {
             throw new Exception("Lock Error: " . $e->getMessage());
        }

        try {
            $this->log("Starting Run. Mode: $mode");
            
            if ($mode === 'full') {
                $this->runFull();
            } elseif ($mode === 'smart') {
                $this->runSmart();
            } else {
                $this->runQuick();
            }
            
            $this->log("Run Complete.");
            
        } catch (Exception $e) {
            $this->log("CRITICAL ERROR: " . $e->getMessage());
            throw $e;
        } finally {
            try {
                $stmt = $this->db->prepare("SELECT RELEASE_LOCK(:name)");
                $stmt->execute([':name' => $this->lockName]);
            } catch (Exception $e) {
                // Ignore lock release errors on exit
            }
        }
    }

    public function runQuick() {
        // v2_quick: Update Probs + Eliminate Calc Only
        $this->checkLimits();
        
        try {
            $this->log("Phase: Quick/Probabilities");
            
            // Truncate Handling
            $truncate = $this->opts['truncate'] ?? true;
            
            // Web Safety Guard: Avoid TRUNCATE on web to prevent 503s (unless explicitly allowed)
            $allowWebTruncate = !empty($this->opts['allow_web_truncate']);
            $canTruncate = $truncate && (php_sapi_name() === 'cli' || $allowWebTruncate);

            if ($canTruncate) {
                // Safe to Truncate
                $this->safeExecute("SET SESSION lock_wait_timeout = 5", [], 'SetMetaLockTimeout');
                $this->safeExecute("SET SESSION innodb_lock_wait_timeout = 5", [], 'SetInnoLockTimeout');
                $this->safeExecute("TRUNCATE TABLE probability_of_winning", [], 'TruncateProb');
                $this->log("Truncated probability_of_winning");
            } else {
                // Use Chunked Delete for Web Safety
                $this->safeChunkedDelete('probability_of_winning', '1=1', [], 'DeleteProb');
                $this->log("Deleted from probability_of_winning");
            }
            
            // Get Counts
            $stmt = $this->safeExecute("SELECT count(id) as count FROM brackets WHERE paid<>'0' AND type='main'", [], 'CountMain');
            $countMain = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            $stmt = $this->safeExecute("SELECT count(id) as count FROM brackets WHERE paid<>'0' AND type='sweet16'", [], 'CountS16');
            $countS16 = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            $this->log("Processing Ranks 1-{$this->maxScoreRanks}...");
            for ($rank = 1; $rank <= $this->maxScoreRanks; $rank++) {
                $this->updateProbabilities($rank, 'main');
                $this->updateProbabilities($rank, 'sweet16');
            }
            
            if ($countMain > 0) $this->updateProbabilities($countMain, 'main');
            if ($countS16 > 0) $this->updateProbabilities($countS16, 'sweet16');

            // Eliminate Calc
            $this->log("Phase: Elimination");
            $this->eliminateCalc();
            
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function eliminateCalc() {
        // 1. Reset everyone to Eliminated by default
        $this->safeExecute("UPDATE `brackets` SET `eliminated` = '1'", [], 'ResetEliminated');

        // 2. Check Tournament Status (Are all games finished?)
        $master = $this->fetchMasterData();
        $gamesLeft = 0;
        for ($i=1; $i<=63; $i++) {
            if (empty($master[$i])) {
                $gamesLeft++;
            }
        }
        
        $limit = 3; // Strict Limit per user request
        
        // 3. Determine who is "Alive"
        // The possible_scores table holds all potential final rankings for each bracket.
        // We only consider a bracket 'alive' if it has at least one mathematical path 
        // to finish in 1st, 2nd, or 3rd place (rank <= $limit).
        
        $query = "SELECT b.id FROM `brackets` b,`possible_scores` p where p.bracket_id = b.id and p.`rank` <= $limit and p.`type`='path_to_victory' group by b.id";
        $stmt = $this->safeExecute($query, [], 'CheckAlive');

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->db->exec("UPDATE `brackets` SET `eliminated` = '0' WHERE `id` = " . $row['id']);
        }
    }

    public function runSmart() {
        $stmt = $this->safeExecute("SELECT COUNT(*) FROM end_games", [], 'CountEndGames');
        $count = $stmt->fetchColumn();
        $this->log("Smart Mode Check. EndGames Count: $count");
        
        if ($count < 30000) { 
            $this->log("Count low, switching to FULL mode.");
            $this->runFull();
            return;
        }
        
        $masterData = $this->fetchMasterData();
        
        // 1. Reset Eligibility
        $this->log("Resetting Eliminated Flags...");
        $this->safeExecute("UPDATE end_games SET eliminated = 0", [], 'ResetEndGames');
        
        // 2. Eliminate Impossible
        $this->eliminateImpossibleEndGames($masterData);
        
        // 3. Score Outcomes
        $this->scoreOutcomes($masterData, 'path_to_victory');
        
        // 4. Run Probability
        $this->runQuick();
    }

    public function runFull() {
        $masterData = $this->fetchMasterData();
        
        $this->log("Phase: Full/Rebuild. Truncating end_games...");
        
        $truncate = $this->opts['truncate'] ?? true;

        // Web Safety Guard: Avoid TRUNCATE on web to prevent 503s (unless explicitly allowed)
        $allowWebTruncate = !empty($this->opts['allow_web_truncate']);

        if ($truncate && php_sapi_name() !== 'cli' && !$allowWebTruncate) {
            $this->log("Web Environment detected: Forcing Truncate=FALSE to avoid timeouts/503s.");
            $truncate = false;
        } elseif ($truncate && php_sapi_name() !== 'cli' && $allowWebTruncate) {
            $this->log("Web Environment detected: allow_web_truncate=TRUE. Proceeding with TRUNCATE.");
        }
    
        if ($truncate) {
             $this->safeExecute("SET SESSION lock_wait_timeout = 5", [], 'SetMetaLockTimeout');
             $this->safeExecute("SET SESSION innodb_lock_wait_timeout = 5", [], 'SetInnoLockTimeout');
             $this->safeExecute("TRUNCATE TABLE end_games", [], 'TruncateEndGames');
        } else {
             // Use Chunked Delete for Web Safety
             $this->safeChunkedDelete('end_games', '1=1', [], 'DeleteEndGames');
             $this->log("Deleted from end_games");
        }

        // Recursion
        $this->log("Building Recursive Tree...");
        $this->enumerateRound(49, 56, [], $masterData, 4);
        $this->enumerateLaterRound(57, 60, 4, $masterData);
        $this->enumerateLaterRound(61, 62, 5, $masterData);
        $this->enumerateLaterRound(63, 63, 6, $masterData);
        
        $this->eliminateImpossibleEndGames($masterData);
        
        $this->scoreOutcomes($masterData, 'path_to_victory');
        
        $this->runQuick();
    }

    // --- Core Logic ---

    private function fetchMasterData() {
        if ($this->memCacheMaster === null) {
            $stmt = $this->safeExecute("SELECT * FROM `master` WHERE `id`=2", [], 'FetchMaster');
            $this->memCacheMaster = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return $this->memCacheMaster;
    }

    private function fetchMetaTiebreaker() {
        $stmt = $this->safeExecute("SELECT tiebreaker FROM `meta` WHERE `id`=1", [], 'FetchMetaTB');
        return (int)$stmt->fetchColumn();
    }

    private function checkLimits() {
        // ... (UNCHANGED)
        // ALWAYS check Max Runtime (Cheap)
        if (microtime(true) - $this->startTime > $this->maxRuntime) {
            throw new Exception("Time Limit Exceeded");
        }

        // Check Iterations every 1000
        $this->iterationCount++;
        if ($this->iterationCount % 1000 === 0) {
            if ($this->iterationCount > $this->maxIterations) {
                throw new Exception("Iteration Limit Exceeded");
            }
        }
    }
    
    private function eliminateImpossibleEndGames($master_data) {
        $this->log("Eliminating Impossible End Games...");
        
        $firstUpdate = true;
        $finishedGamesCondition = "";
            
        for ($i=49; $i<64; $i++) {
            if (($master_data[$i] ?? null) != NULL) {
                if (!$firstUpdate) {
                    $finishedGamesCondition .= " OR ";
                }
                
                $firstUpdate = false;
                $finishedGamesCondition .= "`".$i."` != " . $this->db->quote($master_data[$i]);
            }
        }

        if ($finishedGamesCondition != "") {
            $this->safeExecute("UPDATE `end_games` SET `eliminated` = true WHERE ".$finishedGamesCondition, [], 'EliminateEndGames');
        }
        
        $this->safeExecute("UPDATE possible_scores p, end_games e SET p.eliminated = e.eliminated WHERE e.id = p.outcome_id and p.`type`='path_to_victory'", [], 'UpdatePossibleStatus');
        
        $this->safeExecute("INSERT into possible_scores_eliminated SELECT * from possible_scores p WHERE p.eliminated = true and p.`type`='path_to_victory'", [], 'ArchiveEliminated');
        
        $this->safeChunkedDelete('possible_scores', "eliminated = true and `type`='path_to_victory'", [], 'DeleteEliminated');
    }

    private function enumerateLaterRound($startGame, $endGame, $previousRound, $winnerData) {
        $stmt = $this->safeExecute("SELECT * FROM `end_games` WHERE `round`=:r", [':r' => $previousRound], 'EnumLaterRound');
        
        while ($possibleGame = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $winnerCombo = [];
            for ($i=1; $i<64; ++$i) {
                if (($winnerData[$i] ?? null) != NULL) {
                    $winnerCombo[$i] = $winnerData[$i];
                }
                else if (($possibleGame[$i] ?? null) != NULL) {
                    $winnerCombo[$i] = $possibleGame[$i];
                }
            }
            // Recurse
            $this->enumerateRound($startGame, $endGame, [], $winnerCombo, $previousRound+1);
        }	
    }

    private function enumerateRound($gameNum, $lastGameNum, $peerGamePicks, $winnerData, $round) {
        $possibleGameResults = [];
        $numGameResults = 0;
        
        if (($winnerData[$gameNum] ?? null) != NULL) {
            $possibleGameResults[0] = $winnerData[$gameNum];
            $numGameResults = 1;
        } else {
            // Child Lookups from Property
            $child0 = $this->childGraph[$gameNum][0] ?? null;
            $child1 = $this->childGraph[$gameNum][1] ?? null;
            
            $possibleGameResults[0] = $winnerData[$child0] ?? null;
            $possibleGameResults[1] = $winnerData[$child1] ?? null;
            $numGameResults = 2;
        }
        
        for ($j=0; $j<$numGameResults; ++$j) {	
            $peerGamePicks[$gameNum] = $possibleGameResults[$j];
            
            if ($gameNum < $lastGameNum) {
                $this->enumerateRound($gameNum+1, $lastGameNum, $peerGamePicks, $winnerData, $round);
            }
            
            // Output Result
            if ($gameNum == $lastGameNum) {
                $fields = "";
                $values = "";
                $i = $lastGameNum;
                
                // Build string backwards from current round
                while (($peerGamePicks[$i] ?? null) != NULL) {
                    $fields .= ",`".$i."`";
                    $values .= "," . $this->db->quote($peerGamePicks[$i]);
                    --$i;
                }
                
                // Fill remaining from winnerData (previous rounds)
                for ($k=$i; $k>=49; --$k) {
                    $fields .= ",`".$k."`";
                    $values .= "," . $this->db->quote($winnerData[$k] ?? null);
                }
                
                $sql = "INSERT INTO `end_games` (`round` ".$fields.") VALUES ( ".$round.$values.") ";
                // Use exec directly for speed/simplicity here inside recursion
                $this->db->exec($sql); 
            }
        }
    }

    private function scoreOutcomes($winnerData, $type) {
        $this->log("Scoring Outcomes ($type)...");
        
        $metaTiebreaker = $this->fetchMetaTiebreaker();
        
        $tablesToClear = ['possible_scores', 'possible_scores_eliminated'];
        
        // ... (Truncate Logic UNCHANGED)
        // Determine if we *can* try to truncate based on config
        $truncate = $this->opts['truncate'] ?? true;
        $allowWebTruncate = !empty($this->opts['allow_web_truncate']);
        $canTruncate = $truncate && (php_sapi_name() === 'cli' || $allowWebTruncate);
        
        foreach ($tablesToClear as $table) {
            $didTruncate = false;
            
            // Fast Path: Attempt Truncate if Safe
            if ($canTruncate && $type === 'path_to_victory') {
                try {
                    // Safety Guard: Check for other types
                    // We can only truncate if NO data of other types exists.
                    $checkSql = "SELECT 1 FROM `$table` WHERE type <> ? LIMIT 1";
                    $stmtCheck = $this->safeExecute($checkSql, [$type], "CheckTruncateSafety_$table");
                    
                    if ($stmtCheck->fetchColumn()) {
                        $this->log("Fast reset skipped for $table: other types detected; using chunked delete");
                    } else {
                        // Safe to Truncate: Use Short Timeouts to fail fast rather than hang
                        $this->safeExecute("SET SESSION lock_wait_timeout = 5", [], 'SetMetaLockTimeout');
                        $this->safeExecute("SET SESSION innodb_lock_wait_timeout = 5", [], 'SetInnoLockTimeout');
                        $this->safeExecute("TRUNCATE TABLE `$table`", [], "Truncate_$table");
                        
                        $this->log("Fast reset: TRUNCATE $table enabled");
                        $didTruncate = true;
                    }
                } catch (Exception $e) {
                    $this->log("Fast reset failed for $table: " . $e->getMessage() . ". Falling back to chunked delete.");
                    // Fallthrough to chunked delete
                }
            }
            
            // Standard Path: Fallback or if Truncate disabled
            if (!$didTruncate) {
                // Use Safe Chunked Deletes to prevent Error 1205 and Big Locks
                $this->safeChunkedDelete($table, 'type = ?', [$type], "Delete_$table");
            }
        }
        
        // Optimization: Select only column IDs needed (id, type, 1..63)
        $cols = ['id', 'type', 'tiebreaker']; // ADDED TIEBREAKER
        for($i=1; $i<64; $i++) $cols[] = "`$i`";
        $colStr = implode(',', $cols);
        $sql = "SELECT $colStr FROM brackets WHERE paid<>'0'";
        $stmtB = $this->safeExecute($sql, [], 'FetchBrackets');
        $brackets = $stmtB->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $this->safeExecute("SELECT * FROM end_games WHERE eliminated = 0 AND round = 7", [], 'FetchEndGames');
        
        $count = 0;
        while ($game = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->checkLimits();
            $winnerCombo = [];
            for ($i=1; $i<64; $i++) {
                 if (!empty($winnerData[$i])) $winnerCombo[$i] = $winnerData[$i];
                 elseif (!empty($game[$i])) $winnerCombo[$i] = $game[$i];
            }
            $this->scoreFinishedGame($brackets, $game['id'], $winnerCombo, $type, $metaTiebreaker);
            $count++;
            if ($count % 500 === 0) $this->log("Scored $count scenarios...");
        }
    }

    private function scoreFinishedGame($brackets, $outcomeId, $winnerData, $type, $metaTiebreaker) {
        $mainScores = [];
        $s16Scores = [];
        
        foreach ($brackets as $b) {
            $bType = $b['type'] ?? 'main';
            $score = 0;
            
            for ($j=1; $j<64; $j++) {
                if ($bType === 'sweet16' && $j <= 48) continue;
                
                if (!empty($b[$j]) && !empty($winnerData[$j]) && $b[$j] == $winnerData[$j]) {
                    $seed = $this->seedMap[$winnerData[$j]] ?? 1;
                    $r = $this->roundMap[$j];
                    $pts = $this->scoringArray[$seed][$r] ?? 0;
                    $score += $pts;
                }
            }
            
            $tb = (int)($b['tiebreaker'] ?? 0);
            
            if ($bType === 'sweet16') {
                $s16Scores[] = ['s'=>$score, 'bid'=>$b['id'], 'tb'=>$tb];
            } else {
                $mainScores[] = ['s'=>$score, 'bid'=>$b['id'], 'tb'=>$tb];
            }
        }
        
        $this->insertRankedScores($outcomeId, $mainScores, $type, $metaTiebreaker);
        $this->insertRankedScores($outcomeId, $s16Scores, $type, $metaTiebreaker);
    }
    
    private function insertRankedScores($outcomeId, $scores, $type, $metaTiebreaker) {
        if (empty($scores)) return;
        
        usort($scores, function($a, $b) use ($metaTiebreaker) {
            if ($a['s'] == $b['s']) {
                // Secondary Sort: Tiebreaker (Closest to actual wins)
                $diffA = abs($a['tb'] - $metaTiebreaker);
                $diffB = abs($b['tb'] - $metaTiebreaker);
                
                if ($diffA == $diffB) {
                    return $b['bid'] <=> $a['bid']; // Fallback to ID
                }
                return $diffA <=> $diffB; // Lower diff is better
            }
            return $b['s'] <=> $a['s'];
        });
        
        $sql = "INSERT INTO possible_scores (outcome_id, bracket_id, `rank`, score, type) VALUES ";
        $params = [];
        $chunks = [];
        
        $currentRank = 1;

        // Pre-calculate meta diffs for ranking check
        foreach ($scores as $k => $item) {
            $scores[$k]['diff'] = abs($item['tb'] - $metaTiebreaker);
        }

        foreach ($scores as $index => $item) {
            // Dense Ranking with Tiebreaker
            if ($index > 0) {
                $prev = $scores[$index-1];
                if ($item['s'] < $prev['s'] || $item['diff'] > $prev['diff']) {
                    $currentRank++;
                }
            }

            $chunks[] = "(?,?,?,?,?)";
            $params[] = $outcomeId;
            $params[] = $item['bid'];
            $params[] = $currentRank; 
            $params[] = $item['s'];
            $params[] = $type;
            
            // Chunk size 50 
            if (count($chunks) >= 50) {
                // Use safeExecute for chunk
                $this->safeExecute($sql . implode(',', $chunks), $params, 'InsertScoresChunk');
                $chunks = [];
                $params = [];
            }
        }
        
        if (!empty($chunks)) {
            $this->safeExecute($sql . implode(',', $chunks), $params, 'InsertScoresChunkFinal');
        }
    }

    private function updateProbabilities($rank, $bracketType) {
        $sql = "SELECT p.bracket_id, e.* 
                FROM possible_scores p 
                JOIN brackets b ON p.bracket_id = b.id 
                JOIN end_games e ON e.id = p.outcome_id 
                WHERE e.eliminated = 0 AND e.round = '7' AND p.type = 'path_to_victory' 
                AND p.`rank` = :rank AND b.type = :bType";
        
        $stmt = $this->safeExecute($sql, [':rank' => $rank, ':bType' => $bracketType], 'SelectProbsSource');
        
        $totalPwin = 0;
        $pWinList = [];
        $winnersData = $this->fetchMasterData();

        $count = 0; // Fixed: Initialized Count
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->checkLimits();
            
            $probability = 1.0;
            
            for ($j=49; $j<64; $j++) {
                $winner = $winnersData[$j] ?? null;
                $pick = $row[$j] ?? null; 
                
                if (!empty($winner) && !empty($pick) && $winner == $pick) {
                    continue; 
                } else {
                    $loser = "";
                    $child0 = $this->fetchTeam($this->childGraph[$j][0], $winnersData, $row);
                    $child1 = $this->fetchTeam($this->childGraph[$j][1], $winnersData, $row);
                    
                    if ($child0 != null && $child0 != $pick) $loser = $child0;
                    elseif ($child1 != null && $child1 != $pick) $loser = $child1;
                    
                    $pWin = 0;
                    
                    // Lookup Win%
                    $winnerSeed = $this->seedMap[$pick] ?? null;
                    $loserSeed = $this->seedMap[$loser] ?? null;
                    $round = $this->roundMap[$j] ?? null;
                    
                    if ($winnerSeed && $loserSeed && $round) {
                        $pWin = $this->historicalProbs[$round][$winnerSeed][$loserSeed] ?? null;
                    }
                    
                    if ($pWin == null || $pWin <= 0 || $pWin >= 1) {
                        $ws = (int)($winnerSeed ?? 0);
                        $ls = (int)($loserSeed ?? 0);
                        if (($ws + $ls) > 0) {
                            $pWin = 1 - ($ws / ($ws + $ls));
                        } else {
                            $pWin = 0.5;
                        }
                    }
                    
                    $probability *= $pWin;
                }
            }
            
            $pWinList[$row['bracket_id']][] = $probability;
            $totalPwin += $probability;
            
            $count++;
            if ($count % 1000 === 0) $this->log("Probabilities: Processed $count scenarios for Rank $rank ($bracketType)...");
        }
        
        // Aggregate and Insert (Buffered)
        $bufferSql = "INSERT INTO probability_of_winning (id, `rank`, probability_win) VALUES ";
        $bufferParams = [];
        $bufferChunks = [];
        
        foreach ($pWinList as $bracketId => $winList) {
            $pBracketWin = 0;
            foreach ($winList as $pEndgame) {
                if ($totalPwin > 0) {
                    $norm = $pEndgame / $totalPwin;
                    $pBracketWin += $norm;
                }
            }
            
            $bufferChunks[] = "(?,?,?)";
            $bufferParams[] = $bracketId;
            $bufferParams[] = $rank;
            $bufferParams[] = $pBracketWin;
            
            if (count($bufferChunks) >= 50) {
                $this->safeExecute($bufferSql . implode(',', $bufferChunks), $bufferParams, 'InsertProbChunk');
                $bufferChunks = [];
                $bufferParams = [];
            }
        }
        
        if (!empty($bufferChunks)) {
            $this->safeExecute($bufferSql . implode(',', $bufferChunks), $bufferParams, 'InsertProbChunkFinal');
        }
    }
    
    private function fetchTeam($gameId, $winners, $endGame) {
        if (isset($winners[$gameId]) && $winners[$gameId] != null) return $winners[$gameId];
        if (isset($endGame[$gameId]) && $endGame[$gameId] != null) return $endGame[$gameId];
        return null;
    }
    

}
?>
