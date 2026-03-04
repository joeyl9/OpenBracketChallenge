<?php
// Wrappers for V1 and V2 engines to ensure parity and easy testing.

include_once __DIR__ . '/_legacy_calc_functions.php';
include_once __DIR__ . '/../includes/CalcEngine.php';
include_once __DIR__ . '/../includes/bracket_helpers.php';

function legacy_calc_run(PDO $db, array $opts = []) {
    $mode = $opts['mode'] ?? 'quick';
    $startTime = microtime(true);
    
    // Mock legacy environment
    $roundMap = getRoundMap();
    $seedMap = getSeedMap($db);
    $scoring = getHistoricalProbabilities();
    $childGraph = getChildGraph();
    $maxScoreRanks = 10;
    
    try {
        if ($mode === 'full') {
            // Replicate Production Full Logic
            $stmt = $db->query("SELECT * FROM `master` WHERE `id`=2");
            $master_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $db->exec("TRUNCATE TABLE `end_games`");
            
            eliminateImpossibleEndGames( $master_data, $db );
            
            enumerateRound( 49, 56, array(), $master_data, $childGraph, $db, 4);
            enumerateLaterRound( 57, 60, 4, $master_data, $db, $childGraph );
            enumerateLaterRound( 61, 62, 5, $master_data, $db, $childGraph );
            enumerateLaterRound( 63, 63, 6, $master_data, $db, $childGraph );
            
            $custompoints = getScoringArray($db, 'main');
            scoreOutcomes( 7, $master_data, $custompoints, $db, "path_to_victory" );
            
            // Full includes probability rebuild
            $db->exec("TRUNCATE TABLE `probability_of_winning`");
            
            // Counts
            $countMain = $db->query("SELECT count(id) FROM brackets WHERE paid<>'0' AND type='main'")->fetchColumn();
            $countS16 = $db->query("SELECT count(id) FROM brackets WHERE paid<>'0' AND type='sweet16'")->fetchColumn();
            
            for( $scoreRank=1; $scoreRank <= $maxScoreRanks; $scoreRank++ ) {
                updateProbabilities( $roundMap, $seedMap, $childGraph, $scoring, $master_data, $scoreRank, $db, 'main' );
                updateProbabilities( $roundMap, $seedMap, $childGraph, $scoring, $master_data, $scoreRank, $db, 'sweet16' );
            }
            
            if($countMain > 0) updateProbabilities( $roundMap, $seedMap, $childGraph, $scoring, $master_data, $countMain, $db, 'main' );
            if($countS16 > 0) updateProbabilities( $roundMap, $seedMap, $childGraph, $scoring, $master_data, $countS16, $db, 'sweet16' );
            
            eliminatecalc($db, $maxScoreRanks);
            
        } else {
            // Quick: Only Probability + Elim
             $stmt = $db->query("SELECT * FROM `master` WHERE `id`=2");
            $master_data = $stmt->fetch(PDO::FETCH_ASSOC);

            $db->exec("DELETE FROM probability_of_winning"); 
        
            
            $countMain = $db->query("SELECT count(id) FROM brackets WHERE paid<>'0' AND type='main'")->fetchColumn();
            $countS16 = $db->query("SELECT count(id) FROM brackets WHERE paid<>'0' AND type='sweet16'")->fetchColumn();

            for( $scoreRank=1; $scoreRank <= $maxScoreRanks; $scoreRank++ ) {
                updateProbabilities( $roundMap, $seedMap, $childGraph, $scoring, $master_data, $scoreRank, $db, 'main' );
                updateProbabilities( $roundMap, $seedMap, $childGraph, $scoring, $master_data, $scoreRank, $db, 'sweet16' );
            }
             if($countMain > 0) updateProbabilities( $roundMap, $seedMap, $childGraph, $scoring, $master_data, $countMain, $db, 'main' );
            if($countS16 > 0) updateProbabilities( $roundMap, $seedMap, $childGraph, $scoring, $master_data, $countS16, $db, 'sweet16' );
            
            eliminatecalc($db, $maxScoreRanks);
        }
        
    } catch (Exception $e) {
        return [
            'ok' => false,
            'error' => $e->getMessage()
        ];
    }
    
    return [
        'ok' => true,
        'mode_requested' => $mode,
        'mode_executed' => 'legacy_' . $mode,
        'runtime_ms' => (microtime(true) - $startTime) * 1000,
        'warnings' => []
    ];
}

function v2_calc_run(PDO $db, array $opts = []) {
    $mode = $opts['mode'] ?? 'quick';
    $startTime = microtime(true);
    
    try {
        global $host, $database, $user, $pass;
        
        // Pass DB Credentials for Reconnect Logic
        $opts['db_config'] = [
            'dsn' => "mysql:host=$host;dbname=$database;charset=utf8mb4",
            'user' => $user,
            'pass' => $pass,
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        ];

        $engine = new CalcEngine($db, $opts);
        
        // Inject Dependencies
        $engine->setRoundMap(getRoundMap());
        $engine->setSeedMap(getSeedMap($db));
        $engine->setChildGraph(getChildGraph());
        $engine->setScoringArray(getScoringArray($db, 'main')); // Points
        $engine->setHistoricalProbabilities(getHistoricalProbabilities()); // Percents
        
        $engine->run();
        
    } catch (Exception $e) {
         return [
            'ok' => false,
            'error' => $e->getMessage()
        ];
    }
    
    return [
        'ok' => true,
        'mode_requested' => $mode,
        'mode_executed' => 'v2_' . $mode,
        'runtime_ms' => (microtime(true) - $startTime) * 1000,
        'warnings' => []
    ];
}

